/*
** ZABBIX
** Copyright (C) 2000-2005 SIA Zabbix
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
#include "zbxconf.h"

#include "cfg.h"
#include "log.h"
#include "alias.h"
#include "sysinfo.h"
#include "stats.h"

#if defined(ZABBIX_DAEMON)
#	include "daemon.h"
#endif	/* ZABBIX_DAEMON */

char	*CONFIG_HOSTS_ALLOWED		= NULL;
char	*CONFIG_HOSTNAME		= NULL;

int	CONFIG_DISABLE_ACTIVE		= 0;
int	CONFIG_DISABLE_PASSIVE		= 0;
int	CONFIG_ENABLE_REMOTE_COMMANDS	= 0;
int	CONFIG_LOG_REMOTE_COMMANDS	= 0;
int	CONFIG_UNSAFE_USER_PARAMETERS	= 0;
int	CONFIG_LISTEN_PORT		= 10050;
int	CONFIG_SERVER_PORT		= 10051;
int	CONFIG_REFRESH_ACTIVE_CHECKS	= 120;
char	*CONFIG_LISTEN_IP		= NULL;
char	*CONFIG_SOURCE_IP		= NULL;
int	CONFIG_LOG_LEVEL		= LOG_LEVEL_WARNING;

int	CONFIG_BUFFER_SIZE		= 100;
int	CONFIG_BUFFER_SEND		= 5;

int	CONFIG_MAX_LINES_PER_SECOND	= 100;

char	**CONFIG_ALIASES                = NULL;
char	**CONFIG_USER_PARAMETERS        = NULL;
char	**CONFIG_PERF_COUNTERS          = NULL;

static void	set_defaults();

void	load_config()
{
	struct cfg_line	cfg[] =
	{
		/* PARAMETER,				VAR,					FUNC,
			TYPE,			MANDATORY,	MIN,			MAX */
		{"Server",				&CONFIG_HOSTS_ALLOWED,			NULL,
			TYPE_STRING,		PARM_MAND,	0,			0},
		{"Hostname",				&CONFIG_HOSTNAME,			NULL,
			TYPE_STRING,		PARM_OPT,	0,			0},
		{"BufferSize",				&CONFIG_BUFFER_SIZE,			NULL,
			TYPE_INT,		PARM_OPT,	2,			65535},
		{"BufferSend",				&CONFIG_BUFFER_SEND,			NULL,
			TYPE_INT,		PARM_OPT,	1,			SEC_PER_HOUR},
#ifdef USE_PID_FILE
		{"PidFile",				&CONFIG_PID_FILE,			NULL,
			TYPE_STRING,		PARM_OPT,	0,			0},
#endif	/* USE_PID_FILE */
		{"LogFile",				&CONFIG_LOG_FILE,			NULL,
			TYPE_STRING,		PARM_OPT,	0,			0},
		{"LogFileSize",				&CONFIG_LOG_FILE_SIZE,			NULL,
			TYPE_INT,		PARM_OPT,	0,			1024},
		{"DisableActive",			&CONFIG_DISABLE_ACTIVE,			NULL,
			TYPE_INT,		PARM_OPT,	0,			1},
		{"DisablePassive",			&CONFIG_DISABLE_PASSIVE,		NULL,
			TYPE_INT,		PARM_OPT,	0,			1},
		{"Timeout",				&CONFIG_TIMEOUT,			NULL,
			TYPE_INT,		PARM_OPT,	1,			30},
		{"ListenPort",				&CONFIG_LISTEN_PORT,			NULL,
			TYPE_INT,		PARM_OPT,	1024,			32767},
		{"ServerPort",				&CONFIG_SERVER_PORT,			NULL,
			TYPE_INT,		PARM_OPT,	1024,			32767},
		{"ListenIP",				&CONFIG_LISTEN_IP,			NULL,
			TYPE_STRING,		PARM_OPT,	0,			0},
		{"SourceIP",				&CONFIG_SOURCE_IP,			NULL,
			TYPE_STRING,		PARM_OPT,	0,			0},
		{"DebugLevel",				&CONFIG_LOG_LEVEL,			NULL,
			TYPE_INT,		PARM_OPT,	0,			4},
		{"StartAgents",				&CONFIG_ZABBIX_FORKS,			NULL,
			TYPE_INT,		PARM_OPT,	1,			100},
		{"RefreshActiveChecks",			&CONFIG_REFRESH_ACTIVE_CHECKS,		NULL,
			TYPE_INT,		PARM_OPT,	SEC_PER_MIN,		SEC_PER_HOUR},
		{"MaxLinesPerSecond",			&CONFIG_MAX_LINES_PER_SECOND,		NULL,
			TYPE_INT,		PARM_OPT,	1,			1000},
		{"AllowRoot",				&CONFIG_ALLOW_ROOT,			NULL,
			TYPE_INT,		PARM_OPT,	0,			1},
		{"EnableRemoteCommands",		&CONFIG_ENABLE_REMOTE_COMMANDS,		NULL,
			TYPE_INT,		PARM_OPT,	0,			1},
		{"LogRemoteCommands",			&CONFIG_LOG_REMOTE_COMMANDS,		NULL,
			TYPE_INT,		PARM_OPT,	0,			1},
		{"UnsafeUserParameters",		&CONFIG_UNSAFE_USER_PARAMETERS,		NULL,
			TYPE_INT,		PARM_OPT,	0,			1},
		{"Alias",				&CONFIG_ALIASES,			NULL,
			TYPE_MULTISTRING,	PARM_OPT,	0,			0},
		{"UserParameter",			&CONFIG_USER_PARAMETERS,		NULL,
			TYPE_MULTISTRING,	PARM_OPT,	0,			0},
#ifdef _WINDOWS
		{"PerfCounter",				&CONFIG_PERF_COUNTERS,			NULL,
			TYPE_MULTISTRING,	PARM_OPT,	0,			0},
#endif	/* _WINDOWS */
		{NULL}
	};

	/* initialize these before parsing configuration */
	init_metrics();
	init_collector_data();

	/* initialize multistrings */
	zbx_strarr_init(&CONFIG_ALIASES);
	zbx_strarr_init(&CONFIG_USER_PARAMETERS);
	zbx_strarr_init(&CONFIG_PERF_COUNTERS);

	set_defaults();

	parse_cfg_file(CONFIG_FILE, cfg);

#ifdef USE_PID_FILE
	if (NULL == CONFIG_PID_FILE)
	{
		CONFIG_PID_FILE = "/tmp/zabbix_agentd.pid";
	}
#endif	/* USE_PID_FILE */

	if (1 == CONFIG_DISABLE_ACTIVE && 1 == CONFIG_DISABLE_PASSIVE)
	{
		zabbix_log(LOG_LEVEL_CRIT, "Either active or passive checks must be enabled");
		exit(1);
	}
}

/******************************************************************************
 *                                                                            *
 * Function: free_config                                                      *
 *                                                                            *
 * Purpose: free configuration memory                                         *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Vladimir Levijev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
void	free_config()
{
	zbx_strarr_free(CONFIG_ALIASES);
	zbx_strarr_free(CONFIG_USER_PARAMETERS);
	zbx_strarr_free(CONFIG_PERF_COUNTERS);

	free_metrics();
	alias_list_free();
	free_collector_data();
}

/******************************************************************************
 *                                                                            *
 * Function: add_aliases_from_config                                          *
 *                                                                            *
 * Purpose: initialize aliases from configuration                             *
 *                                                                            *
 * Parameters: lines - aliase entries from configuration file                 *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Vladimir Levijev                                                   *
 *                                                                            *
 * Comments: calls add_alias() for each entry                                 *
 *                                                                            *
 ******************************************************************************/
static void	add_aliases_from_config(char **lines)
{
	char	*value, **pline;

	for (pline = lines; NULL != *pline; pline++)
	{
		if (NULL == (value = strchr(*pline, ':')))
		{
			zabbix_log(LOG_LEVEL_CRIT, "Alias \"%s\" FAILED: not colon-separated", *pline);
			exit(FAIL);
		}
		*value++ = '\0';

		add_alias(*pline, value);
	}
}

/******************************************************************************
 *                                                                            *
 * Function: add_parameters_from_config                                       *
 *                                                                            *
 * Purpose: initialize user parameters from configuration                     *
 *                                                                            *
 * Parameters: lines - user parameter entries from configuration file         *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Vladimir Levijev                                                   *
 *                                                                            *
 * Comments: calls add_user_parameter() for each entry                        *
 *                                                                            *
 ******************************************************************************/
static void	add_parameters_from_config(char **lines)
{
	char	*command, **pline;

	for (pline = lines; NULL != *pline; pline++)
	{
		if (NULL == (command = strchr(*pline, ',')))
		{
			zabbix_log(LOG_LEVEL_CRIT, "UserParameter \"%s\" FAILED: not comma-separated", *pline);
			exit(FAIL);
		}
		*command++ = '\0';

		add_user_parameter(*pline, command);
	}
}

#ifdef _WINDOWS
/******************************************************************************
 *                                                                            *
 * Function: add_perf_counters_from_config                                    *
 *                                                                            *
 * Purpose: add performance counters from configuration                       *
 *                                                                            *
 * Parameters: lines - array of PerfCounter configuration entries             *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Vladimir Levijev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static void	add_perf_counters_from_config(const char **lines)
{
	char		name[MAX_STRING_LEN], counterpath[PDH_MAX_COUNTER_PATH], interval[8];
	const char	**pline, *msg;
	LPTSTR		wcounterPath;

#define PC_FAIL(_msg) {msg = _msg; goto pc_fail;}

	for (pline = lines; NULL != *pline; pline++)
	{
		if (3 < num_param(*pline))
			PC_FAIL("line too short");

		if (0 != get_param(*pline, 1, name, sizeof(name)))
			PC_FAIL("could not parse key");

		if (0 != get_param(*pline, 2, counterpath, sizeof(counterpath)))
			PC_FAIL("could not parse counter path");

		if (0 != get_param(*pline, 3, interval, sizeof(interval)))
			PC_FAIL("could not parse interval");

		wcounterPath = zbx_acp_to_unicode(counterpath);
		zbx_unicode_to_utf8_static(wcounterPath, counterpath, PDH_MAX_COUNTER_PATH);
		zbx_free(wcounterPath);

		if (FAIL == check_counter_path(counterpath))
			PC_FAIL("invalid counter path");

		if (NULL == add_perf_counter(name, counterpath, atoi(interval)))
			PC_FAIL("could not add counter");

		continue;
pc_fail:
		zabbix_log(LOG_LEVEL_CRIT, "PerfCounter '%s' FAILED: %s", pc, err);
		exit(FAIL);
	}
#undef PC_FAIL
}
#endif	/* _WINDOWS */

/******************************************************************************
 *                                                                            *
 * Function: activate_user_config                                             *
 *                                                                            *
 * Purpose: activate user specific parameters specified in configuration file *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Vladimir Levijev                                                   *
 *                                                                            *
 * Comments: activates next parameters:                                       *
 *           - UserParameter                                                  *
 *           - Alias                                                          *
 *           - PerfCounter                                                    *
 *                                                                            *
 ******************************************************************************/
void	activate_user_config()
{
	/* parameters */
	add_parameters_from_config(CONFIG_USER_PARAMETERS);

	/* aliases */
	add_aliases_from_config(CONFIG_ALIASES);

#if defined(_WINDOWS)
	/* performance counters */
	add_perf_counters_from_config(CONFIG_PERF_COUNTERS);
#endif	/* _WINDOWS */
}

/******************************************************************************
 *                                                                            *
 * Function: set_defaults                                                     *
 *                                                                            *
 * Purpose: set non-static configuration defaults                             *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Vladimir Levijev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static void	set_defaults()
{
	AGENT_RESULT	result;
	char		**value = NULL;

	memset(&result, 0, sizeof(AGENT_RESULT));

	/* hostname */
	if (NULL != CONFIG_HOSTNAME)
		zbx_free(CONFIG_HOSTNAME);

	if (SUCCEED == process("system.hostname", 0, &result))
	{
		if (NULL == (value = GET_STR_RESULT(&result)))
		{
			zabbix_log(LOG_LEVEL_CRIT, "Failed to get default hostname (system.hostname)");
			exit(FAIL);
		}

		assert(*value);

		CONFIG_HOSTNAME = zbx_strdup(CONFIG_HOSTNAME, *value);

		/* If auto registration is used, our CONFIG_HOSTNAME will make it into the  */
		/* server's database, where it is limited by HOST_HOST_LEN (currently, 64), */
		/* so to make it work properly we need to truncate our hostname.            */
		if (strlen(CONFIG_HOSTNAME) > 64)
			CONFIG_HOSTNAME[64] = '\0';
	}
	free_result(&result);
}

#ifdef _AIX
void	tl_version()
{
#ifdef _AIXVERSION_610
#	define ZBX_AIX_TL	"6100 and above"
#elif _AIXVERSION_530
#	ifdef HAVE_AIXOSLEVEL_530006
#		define ZBX_AIX_TL	"5300-06 and above"
#	else
#		define ZBX_AIX_TL	"5300-00,01,02,03,04,05"
#	endif
#elif _AIXVERSION_520
#	define ZBX_AIX_TL	"5200"
#elif _AIXVERSION_510
#	define ZBX_AIX_TL	"5100"
#endif
#ifdef ZBX_AIX_TL
	printf("Supported technology levels: %s\n", ZBX_AIX_TL);
#endif /* ZBX_AIX_TL */
#undef ZBX_AIX_TL
}
#endif /* _AIX */
