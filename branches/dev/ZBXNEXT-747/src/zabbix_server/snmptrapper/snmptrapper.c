/*
** Zabbix
** Copyright (C) 2000-2011 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
**/

#include "common.h"
#include "dbcache.h"
#include "zbxself.h"
#include "daemon.h"
#include "log.h"

#include "snmptrapper.h"

static int	trap_fd = -1;
static int	trap_lastsize;
static ino_t	trap_ino = 0;

static void	DBget_lastsize()
{
	DB_RESULT	result;
	DB_ROW		row;

	result = DBselect("select snmp_lastsize from globalvars");

	if (NULL != (row = DBfetch(result)))
		trap_lastsize = atoi(row[0]);
	else
		trap_lastsize = 0;

	DBfree_result(result);
}

static void	DBupdate_lastsize()
{
	DBexecute("update globalvars set snmp_lastsize=%d", trap_lastsize);
}

static zbx_uint64_t	get_fallback_interface()
{
	return FAIL;
}

/******************************************************************************
 *                                                                            *
 * Function: process_trap_for_interface                                       *
 *                                                                            *
 * Purpose: add trap to all matching items for the specified interface        *
 *                                                                            *
 * Return value: SUCCEED - a matching item was found                          *
 *               FAIL - no matching item was found (including fallback items) *
 *                                                                            *
 * Author: Rudolfs Kreicbergs                                                 *
 *                                                                            *
 ******************************************************************************/
static int process_trap_for_interface(zbx_uint64_t interfaceid, char *trap, zbx_timespec_t *ts, AGENT_RESULT *value)
{
	DC_ITEM	*items = NULL;
	char	cmd[MAX_STRING_LEN], params[MAX_STRING_LEN], regex[MAX_STRING_LEN];
	int	count, i, ret = FAIL, fallback = -1;

	count = DCconfig_get_snmp_items_by_interface(interfaceid, &items);

	for (i = 0; i < count; i++)
	{

		if (2 != parse_command(items[i].key_orig, cmd, sizeof(cmd), params, sizeof(params)))
			continue;

		if (0 == strcmp(cmd, "snmptrap.fallback"))
		{
			fallback = i;
			continue;
		}

		if (0 != strcmp(cmd, "snmptrap") || 0 != get_param(params, 1, regex, sizeof(regex)))
			continue;

		if (NULL == zbx_regexp_match(trap, regex, NULL))
			continue;

		ret = SUCCEED;
		dc_add_history(items[i].itemid, items[i].value_type, items[i].flags, value, ts, 0, NULL, 0, 0, 0, 0);
	}

	if (FAIL == ret && -1 != fallback)
	{
		ret = SUCCEED;
		dc_add_history(items[fallback].itemid, items[fallback].value_type, items[fallback].flags, value, ts, 0, NULL, 0, 0, 0, 0);
	}

	zbx_free(items);

	return ret;
}

/******************************************************************************
 *                                                                            *
 * Function: process_trap                                                     *
 *                                                                            *
 * Purpose: process a single trap                                             *
 *                                                                            *
 * Parameters: ip - [IN] ip address of the target interface(s)                *
 *             begin - [IN] beginning of the trap message                     *
 *             end - [IN] end of the trap message                             *
 *                                                                            *
 * Author: Rudolfs Kreicbergs                                                 *
 *                                                                            *
 ******************************************************************************/
static void process_trap(char *ip, char *begin, char *end)
{
	AGENT_RESULT	value;
	zbx_timespec_t	ts;
	zbx_uint64_t	*interfaceids = NULL, fallback_interfaceid;
	int		count, i, ret = FAIL;
	char		*trap;

	/* prepare the value */
	zbx_timespec(&ts);
	trap = zbx_dsprintf(NULL, "%s%s", begin, end);
	init_result(&value);
	SET_STR_RESULT(&value, trap);

	count = DCconfig_get_snmp_interfaceids(ip, &interfaceids);

	for (i = 0; i < count; i++)
	{
		if (SUCCEED == process_trap_for_interface(interfaceids[i], trap, &ts, &value))
			ret = SUCCEED;
	}

	free_result(&value);

	if (FAIL == ret && FAIL != (fallback_interfaceid = get_fallback_interface()))
	{
		init_result(&value);
		SET_STR_RESULT(&value, zbx_dsprintf(trap, "%s: %s%s", ip, begin, end));
		process_trap_for_interface(fallback_interfaceid, trap, &ts, &value);
		free_result(&value);
	}

	zbx_free(interfaceids);
}

/******************************************************************************
 *                                                                            *
 * Function: parse_traps                                                      *
 *                                                                            *
 * Purpose: split traps and process them with process_trap()                  *
 *                                                                            *
 * Author: Rudolfs Kreicbergs                                                 *
 *                                                                            *
 ******************************************************************************/
static void parse_traps(char *buffer)
{
	char	*c, *line, *begin = NULL, *end = NULL, *ip;

	c = buffer;
	line = buffer;

	for (; '\0' != *c; c++)
	{
		if ('\n' == *c)
			line = c + 1;

		if (0 != strncmp(c, "ZBXTRAP ", 8))
			continue;

		*c = '\0';
		c += 8;	/* c now points to the IP address */

		/* process the previos trap */
		if (NULL != begin)
		{
			*(line - 1) = '\0';
			process_trap(ip, begin, end);
		}

		/* parse the current trap */
		begin = line;
		ip = c;

		if (NULL == (c = strchr(c, ' ')))
		{
			zabbix_log(LOG_LEVEL_ERR, "invalid trap format");
			return;
		}

		*c++ = '\0';
		end = c;	/* the rest of the trap */
	}

	/* process the last trap */
	if (NULL != end)
		process_trap(ip, begin, end);
}

/******************************************************************************
 *                                                                            *
 * Function: read_traps                                                       *
 *                                                                            *
 * Purpose: read the traps and then parse them with parse_traps()             *
 *                                                                            *
 * Author: Rudolfs Kreicbergs                                                 *
 *                                                                            *
 ******************************************************************************/
static void read_traps()
{
	const char	*__function_name = "process_traps";
	int		nbytes;
	char		buffer[MAX_BUFFER_LEN];

	zabbix_log(LOG_LEVEL_DEBUG, "In %s(), lastsize [%lu]", __function_name, trap_lastsize);

	*buffer = 0;


	if ((off_t)-1 == lseek(trap_fd, (off_t)trap_lastsize, SEEK_SET))
	{
		zabbix_log(LOG_LEVEL_WARNING, "%s(): cannot set position to [%li]: %s",
				__function_name, trap_lastsize, zbx_strerror(errno));
		goto exit;
	}

	if (FAIL == (nbytes = read(trap_fd, buffer, sizeof(buffer))))
	{
		zabbix_log(LOG_LEVEL_WARNING, "%s(): cannot read from [%s]: %s",
				__function_name, CONFIG_SNMPTRAP_FILE, zbx_strerror(errno));
		goto exit;
	}

	buffer[nbytes] = '\0';
	zbx_rtrim(buffer + MAX(nbytes - 3, 0), " \r\n");

	trap_lastsize += (off_t)nbytes;
	DBupdate_lastsize();

	parse_traps(buffer);
exit:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

/******************************************************************************
 *                                                                            *
 * Function: close_trap_file                                                  *
 *                                                                            *
 * Purpose: close trap file and reset lastsize ()                             *
 *                                                                            *
 * Author: Rudolfs Kreicbergs                                                 *
 *                                                                            *
 * Comments: !!! do not reset lastsize elsewhere !!!                          *
 *                                                                            *
 ******************************************************************************/
static void	close_trap_file()
{
	if (-1 != trap_fd)
		close(trap_fd);

	trap_fd = -1;
	trap_lastsize = 0;
	DBupdate_lastsize();
}

/******************************************************************************
 *                                                                            *
 * Function: open_trap_file                                                   *
 *                                                                            *
 * Purpose: open the trap file and get it's node number                       *
 *                                                                            *
 * Author: Rudolfs Kreicbergs                                                 *
 *                                                                            *
 ******************************************************************************/
static int	open_trap_file()
{
	const char	*__function_name = "open_trap_file";
	struct stat	file_buf;

	if(-1 == (trap_fd = open(CONFIG_SNMPTRAP_FILE, O_RDONLY)))
	{
		if (errno != ENOENT)	/* file exists but cannot be opened */
		{
			zabbix_log(LOG_LEVEL_CRIT, "%s(): cannot open [%s]: %s",
					__function_name, CONFIG_SNMPTRAP_FILE, zbx_strerror(errno));
		}
	}
	else if (FAIL == stat(CONFIG_SNMPTRAP_FILE, &file_buf))
	{
		zabbix_log(LOG_LEVEL_CRIT, "%s(): cannot stat [%s]: %s",
				__function_name, CONFIG_SNMPTRAP_FILE, zbx_strerror(errno));
		close_trap_file();
	}
	else
		trap_ino = file_buf.st_ino;	/* a new file was opened */

	return trap_fd;
}

/******************************************************************************
 *                                                                            *
 * Function: get_latest_data                                                  *
 *                                                                            *
 * Purpose: open the latest trap file, if the current file has been rotated,  *
 *          process that and then open the latest file                        *
 *                                                                            *
 * Author: Rudolfs Kreicbergs                                                 *
 *                                                                            *
 ******************************************************************************/
static int	get_latest_data()
{
	const char	*__function_name = "get_latest_data";
	struct stat	file_buf;

	if (-1 != trap_fd)	/* a trap file is already open */
	{
		if (FAIL == stat(CONFIG_SNMPTRAP_FILE, &file_buf))
		{
			/* file might have been renamed or deleted, process the current file */

			if  (errno != ENOENT)
			{
				zabbix_log(LOG_LEVEL_CRIT, "%s(): cannot stat [%s]: %s",
						__function_name, CONFIG_SNMPTRAP_FILE, zbx_strerror(errno));
			}
			read_traps();
			close_trap_file();
		}
		else if (file_buf.st_ino != trap_ino || file_buf.st_size < trap_lastsize)
		{
			/* file has been rotated, process the current file */

			read_traps();
			close_trap_file();
		}
		else if (file_buf.st_size == trap_lastsize)
			return FAIL;	/* no new traps */
	}

	if (-1 == trap_fd && -1 == open_trap_file())
		return FAIL;

	return SUCCEED;
}

/******************************************************************************
 *                                                                            *
 * Function: main_snmptrapper_loop                                            *
 *                                                                            *
 * Purpose: SNMP trap reader's entry point                                    *
 *                                                                            *
 * Author: Rudolfs Kreicbergs                                                 *
 *                                                                            *
 ******************************************************************************/
void	main_snmptrapper_loop(int server_num)
{
	const char	*__function_name = "main_snmptrapper_loop";

	zabbix_log(LOG_LEVEL_ERR, "In %s(), trapfile [%s]", __function_name, CONFIG_SNMPTRAP_FILE);

	set_child_signal_handler();

	zbx_setproctitle("%s [connecting to the database]", get_process_type_string(process_type));

	DBconnect(ZBX_DB_CONNECT_NORMAL);

	DBget_lastsize();

	while (ZBX_IS_RUNNING())
	{
		update_selfmon_counter(ZBX_PROCESS_STATE_BUSY);
		zbx_setproctitle("%s [processing data]", get_process_type_string(process_type));

		while (SUCCEED == get_latest_data())	/* there are new traps */
			read_traps();

		update_selfmon_counter(ZBX_PROCESS_STATE_IDLE);
		zbx_setproctitle("snmptrapper [sleeping for 1 second]");
		zbx_sleep(1);
	}

	if (-1 != trap_fd)
		close(trap_fd);
}
