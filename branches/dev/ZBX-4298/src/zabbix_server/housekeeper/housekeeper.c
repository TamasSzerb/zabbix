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
#include "db.h"
#include "log.h"
#include "daemon.h"
#include "zbxself.h"

#include "housekeeper.h"

extern unsigned char	process_type;

/******************************************************************************
 *                                                                            *
 * Function: housekeeping_cleanup                                             *
 *                                                                            *
 * Purpose: remove deleted items data                                         *
 *                                                                            *
 * Return value: number of rows deleted                                       *
 *                                                                            *
 * Author: Alexei Vladishev, Dmitry Borovikov                                 *
 *                                                                            *
 * Comments: sqlite3 does not use CONFIG_MAX_HOUSEKEEPER_DELETE, deletes all  *
 *                                                                            *
 ******************************************************************************/
static int	housekeeping_cleanup()
{
	const char	*__function_name = "housekeeping_cleanup";
	DB_HOUSEKEEPER	housekeeper;
	DB_RESULT	result;
	DB_ROW		row;
	int		sql_alloc = 512, sql_offset = 0, ids_alloc = 0, ids_num = 0, d, deleted = 0;
	char		*sql = NULL;
	zbx_uint64_t	*ids = NULL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	/* order by tablename to effectively use DB cache */
	result = DBselect(
			"select housekeeperid,tablename,field,value"
			" from housekeeper"
			" order by tablename");

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(housekeeper.housekeeperid, row[0]);
		housekeeper.tablename = row[1];
		housekeeper.field = row[2];
		ZBX_STR2UINT64(housekeeper.value, row[3]);

		if (0 == CONFIG_MAX_HOUSEKEEPER_DELETE)
		{
			d = DBexecute(
					"delete from %s"
					" where %s=" ZBX_FS_UI64,
					housekeeper.tablename,
					housekeeper.field,
					housekeeper.value);
		}
		else
		{
#if defined(HAVE_IBM_DB2) || defined(HAVE_ORACLE)
			d = DBexecute(
					"delete from %s"
					" where %s=" ZBX_FS_UI64
						" and rownum<=%d",
					housekeeper.tablename,
					housekeeper.field,
					housekeeper.value,
					CONFIG_MAX_HOUSEKEEPER_DELETE);
#elif defined(HAVE_MYSQL)
			d = DBexecute(
					"delete from %s"
					" where %s=" ZBX_FS_UI64 " limit %d",
					housekeeper.tablename,
					housekeeper.field,
					housekeeper.value,
					CONFIG_MAX_HOUSEKEEPER_DELETE);
#elif defined(HAVE_POSTGRESQL)
			extern int	ZBX_PG_SVERSION;

			/* PostgreSQL array constructors are available since version 7.4 */
			if (70400 > ZBX_PG_SVERSION)
			{
				d = DBexecute(
						"delete from %s"
						" where %s=" ZBX_FS_UI64
							" and clock in (select clock from %s"
								" where %s=" ZBX_FS_UI64 " limit %d)",
						housekeeper.tablename,
						housekeeper.field,
						housekeeper.value,
						housekeeper.tablename,
						housekeeper.field,
						housekeeper.value,
						CONFIG_MAX_HOUSEKEEPER_DELETE);
			}
			else
			{
				d = DBexecute(
						"delete from %s"
						" where ctid = any(array(select ctid from %s"
							" where %s=" ZBX_FS_UI64 " limit %d))",
						housekeeper.tablename,
						housekeeper.tablename,
						housekeeper.field,
						housekeeper.value,
						CONFIG_MAX_HOUSEKEEPER_DELETE);
			}
#elif defined(HAVE_SQLITE3)
			d = 0;
#endif
		}

		if (0 == d || 0 == CONFIG_MAX_HOUSEKEEPER_DELETE || CONFIG_MAX_HOUSEKEEPER_DELETE > d)
			uint64_array_add(&ids, &ids_alloc, &ids_num, housekeeper.housekeeperid, 64);

		deleted += d;
	}
	DBfree_result(result);

	if (NULL != ids)
	{
		sql = zbx_malloc(sql, sql_alloc);

		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 32, "delete from housekeeper where");
		DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "housekeeperid", ids, ids_num);

		DBexecute("%s", sql);

		zbx_free(sql);
		zbx_free(ids);
	}

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%d", __function_name, deleted);

	return deleted;
}

static int	housekeeping_sessions(int now)
{
	const char	*__function_name = "housekeeping_sessions";
	int		deleted;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() now:%d", __function_name, now);

	deleted = DBexecute("delete from sessions where lastaccess<%d", now - SEC_PER_YEAR);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%d", __function_name, deleted);

	return deleted;
}

static int	housekeeping_alerts(int now)
{
	const char	*__function_name = "housekeeping_alerts";
	int		alert_history;
	DB_RESULT	result;
	DB_ROW		row;
	int		deleted = 0;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() now:%d", __function_name, now);

	result = DBselect("select alert_history from config");

	if (NULL == (row = DBfetch(result)) || SUCCEED == DBis_null(row[0]))
	{
		zabbix_log(LOG_LEVEL_ERR, "no records in table 'config'");
	}
	else
	{
		alert_history = atoi(row[0]);

		deleted = DBexecute("delete from alerts where clock<%d", now - alert_history * SEC_PER_DAY);
	}
	DBfree_result(result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%d", __function_name, deleted);

	return deleted;
}

static int	housekeeping_events(int now)
{
	const char	*__function_name = "housekeeping_events";
	int		event_history, deleted = 0;
	DB_RESULT	result;
	DB_RESULT	result2;
	DB_ROW		row1;
	DB_ROW		row2;
	zbx_uint64_t	eventid;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() now:%d", __function_name, now);

	result = DBselect("select event_history from config");

	if (NULL == (row1 = DBfetch(result)) || SUCCEED == DBis_null(row1[0]))
	{
		zabbix_log(LOG_LEVEL_ERR, "no records in table 'config'");
	}
	else
	{
		event_history = atoi(row1[0]);

		result2 = DBselect("select eventid from events where clock<%d", now - event_history * SEC_PER_DAY);

		while (NULL != (row2 = DBfetch(result2)))
		{
			ZBX_STR2UINT64(eventid, row2[0]);

			DBexecute("delete from acknowledges where eventid=" ZBX_FS_UI64, eventid);
			deleted = DBexecute("delete from events where eventid=" ZBX_FS_UI64, eventid);
		}
		DBfree_result(result2);
	}
	DBfree_result(result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%d", __function_name, deleted);

	return deleted;
}

/******************************************************************************
 *                                                                            *
 * Function: delete_history                                                   *
 *                                                                            *
 * Purpose: remove outdated information from historical table                 *
 *                                                                            *
 * Parameters: now - current timestamp                                        *
 *                                                                            *
 * Return value: number of rows deleted                                       *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int	delete_history(const char *table, zbx_uint64_t itemid, int keep_history, int now)
{
	const char	*__function_name = "delete_history";
	DB_RESULT       result;
	DB_ROW          row;
	int             min_clock, deleted;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() table:'%s' itemid:" ZBX_FS_UI64 " keep_history:%d now:%d",
		__function_name, table, itemid, keep_history, now);

	result = DBselect("select min(clock) from %s where itemid=" ZBX_FS_UI64, table, itemid);

	if (NULL == (row = DBfetch(result)) || SUCCEED == DBis_null(row[0]))
	{
		DBfree_result(result);
		return 0;
	}

	min_clock = atoi(row[0]);
	min_clock = MIN(now - keep_history * SEC_PER_DAY, min_clock + 4 * CONFIG_HOUSEKEEPING_FREQUENCY * SEC_PER_HOUR);
	DBfree_result(result);

	deleted = DBexecute("delete from %s where itemid=" ZBX_FS_UI64 " and clock<%d", table, itemid, min_clock);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%d", __function_name, deleted);

	return deleted;
}

/******************************************************************************
 *                                                                            *
 * Function: housekeeping_history_and_trends                                  *
 *                                                                            *
 * Purpose: remove outdated information from history and trends               *
 *                                                                            *
 * Parameters: now - current timestamp                                        *
 *                                                                            *
 * Return value: number of rows deleted                                       *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int	housekeeping_history_and_trends(int now)
{
	const char	*__function_name = "housekeeping_history_and_trends";
	DB_ITEM         item;
	DB_RESULT       result;
	DB_ROW          row;
	int             deleted = 0;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() now:%d", __function_name, now);

	result = DBselect("select itemid,history,trends from items");

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(item.itemid, row[0]);
		item.history = atoi(row[1]);
		item.trends = atoi(row[2]);

		deleted += delete_history("history", item.itemid, item.history, now);
		deleted += delete_history("history_uint", item.itemid, item.history, now);
		deleted += delete_history("history_str", item.itemid, item.history, now);
		deleted += delete_history("history_text", item.itemid, item.history, now);
		deleted += delete_history("history_log", item.itemid, item.history, now);
		deleted += delete_history("trends", item.itemid, item.trends, now);
		deleted += delete_history("trends_uint", item.itemid, item.trends, now);
	}
	DBfree_result(result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%d", __function_name, deleted);

	return deleted;
}

void	main_housekeeper_loop()
{
#define ZBX_QNT_ENDING(quantity)	(1 == quantity ? "" : "s")

	int	now, d_history_and_trends, d_clenup, d_events, d_alerts, d_sessions;

	for (;;)
	{
		zabbix_log(LOG_LEVEL_WARNING, "executing housekeeper");
		now = time(NULL);

		zbx_setproctitle("%s [connecting to the database]", get_process_type_string(process_type));

		DBconnect(ZBX_DB_CONNECT_NORMAL);

		zbx_setproctitle("%s [removing old history and trends]", get_process_type_string(process_type));
		d_history_and_trends = housekeeping_history_and_trends(now);

		zbx_setproctitle("%s [removing deleted items data]", get_process_type_string(process_type));
		d_clenup = housekeeping_cleanup(now);

		zbx_setproctitle("%s [removing old events]", get_process_type_string(process_type));
		d_events = housekeeping_events(now);

		zbx_setproctitle("%s [removing old alerts]", get_process_type_string(process_type));
		d_alerts = housekeeping_alerts(now);

		zbx_setproctitle("%s [removing old sessions]", get_process_type_string(process_type));
		d_sessions = housekeeping_sessions(now);

		zabbix_log(LOG_LEVEL_WARNING, "housekeeper deleted: %d record%s from history and trends,"
				" %d record%s of deleted items, %d event%s, %d alert%s, %d session%s",
				d_history_and_trends, ZBX_QNT_ENDING(d_history_and_trends),
				d_clenup, ZBX_QNT_ENDING(d_clenup),
				d_events, ZBX_QNT_ENDING(d_events),
				d_alerts, ZBX_QNT_ENDING(d_alerts),
				d_sessions, ZBX_QNT_ENDING(d_alerts));

		DBclose();

		zbx_sleep_loop(CONFIG_HOUSEKEEPING_FREQUENCY * SEC_PER_HOUR);
	}

#undef ZBX_QNT_ENDING
}
