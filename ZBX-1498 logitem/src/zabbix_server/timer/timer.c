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

#include "cfg.h"
#include "pid.h"
#include "db.h"
#include "log.h"
#include "../events.h"
#include "dbcache.h"
#include "zlog.h"
#include "zbxserver.h"

#include "timer.h"

#define TIMER_DELAY 30

static void process_time_functions()
{
	DB_RESULT	result;
	DB_ROW		row;
	DB_ITEM		item;

	zbx_setproctitle("timer [updating functions]");

	result = DBselect("select distinct %s, functions f where h.hostid=i.hostid and h.status=%d"
			" and i.status=%d and f.function in ('nodata','date','dayofweek','time','now')"
			" and i.itemid=f.itemid and (h.maintenance_status=%d or h.maintenance_type=%d)" DB_NODE,
			ZBX_SQL_ITEM_SELECT,
			HOST_STATUS_MONITORED,
			ITEM_STATUS_ACTIVE,
			HOST_MAINTENANCE_STATUS_OFF, MAINTENANCE_TYPE_NORMAL,
			DBnode_local("h.hostid"));

	while (NULL != (row = DBfetch(result)))
	{
		DBget_item_from_db(&item, row);

		DBbegin();
		update_functions(&item, (int)time(NULL));
		update_triggers(item.itemid);
		DBcommit();
	}

	DBfree_result(result);
}

typedef struct zbx_host_maintenance_s {
	zbx_uint64_t	hostid;
	time_t		maintenance_from;
	zbx_uint64_t	maintenanceid;
	int		maintenance_type;
	zbx_uint64_t	host_maintenanceid;
	int		host_maintenance_status;
	int		host_maintenance_type;
	int		host_maintenance_from;
} zbx_host_maintenance_t;

static int	get_host_maintenance_nearestindex(zbx_host_maintenance_t *hm, int hm_count,
		zbx_uint64_t hostid, time_t maintenance_from, zbx_uint64_t maintenanceid)
{
	int	first_index, last_index, index;

	if (hm_count == 0)
		return 0;

	first_index = 0;
	last_index = hm_count - 1;
	while (1)
	{
		index = first_index + (last_index - first_index) / 2;

		if (hm[index].hostid == hostid && hm[index].maintenance_from == maintenance_from && hm[index].maintenanceid == maintenanceid)
			return index;
		else if (last_index == first_index)
		{
			if (hm[index].hostid < hostid ||
					(hm[index].hostid == hostid && hm[index].maintenance_from < maintenance_from) ||
					(hm[index].hostid == hostid && hm[index].maintenance_from == maintenance_from &&
					 	hm[index].maintenanceid < maintenanceid))
				index++;
			return index;
		}
		else if (hm[index].hostid < hostid ||
				(hm[index].hostid == hostid && hm[index].maintenance_from < maintenance_from) ||
				(hm[index].hostid == hostid && hm[index].maintenance_from == maintenance_from &&
				 	hm[index].maintenanceid < maintenanceid))
			first_index = index + 1;
		else
			last_index = index;
	}
}

static zbx_host_maintenance_t *get_host_maintenance(zbx_host_maintenance_t **hm, int *hm_alloc, int *hm_count,
		zbx_uint64_t hostid, time_t maintenance_from, zbx_uint64_t maintenanceid, int maintenance_type,
		zbx_uint64_t host_maintenanceid, int host_maintenance_status, int host_maintenance_type,
		int host_maintenance_from)
{
	int	hm_index;

	hm_index = get_host_maintenance_nearestindex(*hm, *hm_count, hostid, maintenance_from, maintenanceid);
	if (hm_index < *hm_count && (*hm)[hm_index].hostid == hostid && (*hm)[hm_index].maintenance_from == maintenance_from &&
			(*hm)[hm_index].maintenanceid == maintenanceid)
		return &(*hm)[hm_index];

	if (*hm_alloc == *hm_count)
	{
		*hm_alloc += 4;
		*hm = zbx_realloc(*hm, *hm_alloc * sizeof(zbx_host_maintenance_t));
	}

	memmove(&(*hm)[hm_index + 1], &(*hm)[hm_index], sizeof(zbx_host_maintenance_t) * (*hm_count - hm_index));

	(*hm)[hm_index].hostid = hostid;
	(*hm)[hm_index].maintenance_from = maintenance_from;
	(*hm)[hm_index].maintenanceid = maintenanceid;
	(*hm)[hm_index].maintenance_type = maintenance_type;
	(*hm)[hm_index].host_maintenanceid = host_maintenanceid;
	(*hm)[hm_index].host_maintenance_status = host_maintenance_status;
	(*hm)[hm_index].host_maintenance_type = host_maintenance_type;
	(*hm)[hm_index].host_maintenance_from = host_maintenance_from;
	(*hm_count)++;

	return &(*hm)[hm_index];
}

static void	process_maintenance_hosts(zbx_host_maintenance_t **hm, int *hm_alloc, int *hm_count,
		time_t maintenance_from, zbx_uint64_t maintenanceid, int maintenance_type)
{
	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	host_hostid, host_maintenanceid;
	int		host_maintenance_status, host_maintenance_type, host_maintenance_from;

	zabbix_log(LOG_LEVEL_DEBUG, "In process_maintenance_hosts()");

	result = DBselect(
			"select h.hostid,h.maintenanceid,h.maintenance_status,h.maintenance_type,h.maintenance_from "
			"from maintenances_hosts mh,hosts h "
			"where mh.hostid=h.hostid and "
				"h.status=%d and "
				"mh.maintenanceid=" ZBX_FS_UI64,
			HOST_STATUS_MONITORED,
			maintenanceid);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(host_hostid, row[0]);
		ZBX_STR2UINT64(host_maintenanceid, row[1]);
		host_maintenance_status = atoi(row[2]);
		host_maintenance_type = atoi(row[3]);
		host_maintenance_from = atoi(row[4]);

		get_host_maintenance(hm, hm_alloc, hm_count, host_hostid, maintenance_from, maintenanceid,
				maintenance_type, host_maintenanceid, host_maintenance_status, host_maintenance_type,
				host_maintenance_from);
	}
	DBfree_result(result);

	result = DBselect(
			"select h.hostid,h.maintenanceid,h.maintenance_status,h.maintenance_type,h.maintenance_from "
			"from maintenances_groups mg,hosts_groups hg,hosts h "
			"where mg.groupid=hg.groupid and "
				"hg.hostid=h.hostid and "
				"h.status=%d and "
				"mg.maintenanceid=" ZBX_FS_UI64,
			HOST_STATUS_MONITORED,
			maintenanceid);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(host_hostid, row[0]);
		ZBX_STR2UINT64(host_maintenanceid, row[1]);
		host_maintenance_status = atoi(row[2]);
		host_maintenance_type = atoi(row[3]);
		host_maintenance_from = atoi(row[4]);

		get_host_maintenance(hm, hm_alloc, hm_count, host_hostid, maintenance_from, maintenanceid,
				maintenance_type, host_maintenanceid, host_maintenance_status, host_maintenance_type,
				host_maintenance_from);
	}
	DBfree_result(result);
}

/******************************************************************************
 *                                                                            *
 * Function: get_trigger_value                                                *
 *                                                                            *
 * Purpose: get trigger value for specified time                              *
 *                                                                            *
 * Parameters: triggerid - trigger identificator from database                *
 *             now -                                                          *
 *                                                                            *
 * Return value: SUCCEED if found event with OK or PROBLEM stauses            *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int	get_trigger_value(zbx_uint64_t triggerid, time_t now, int *value)
{
	DB_RESULT	result;
	DB_ROW		row;
	char		sql[MAX_STRING_LEN];
	int		ret = FAIL;

	zbx_snprintf(sql, sizeof(sql),
			"select value"
			" from events"
			" where source=%d"
				" and object=%d"
				" and objectid=" ZBX_FS_UI64
				" and clock<%d"
				" and value in(%d,%d)"
			" order by clock desc",
			EVENT_SOURCE_TRIGGERS,
			EVENT_OBJECT_TRIGGER,
			triggerid,
			(int)now,
			TRIGGER_VALUE_FALSE, TRIGGER_VALUE_TRUE);

	result = DBselectN(sql, 1);

	if (NULL != (row = DBfetch(result)))
	{
		*value = atoi(row[0]);
		ret = SUCCEED;
	}
	DBfree_result(result);

	return ret;
}

/******************************************************************************
 *                                                                            *
 * Function: generate_events                                                  *
 *                                                                            *
 * Purpose: generate events for triggers after maintenance period             *
 *          The events will be generated only in case of trigger was FALSE    *
 *          before maintenance and became TRUE after maintenance, also in     *
 *          case if it was TRUE before and FALSE after.                       *
 *                                                                            *
 * Parameters: hostid - host identificator from database                      *
 *             maintenance_from -                                             *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static void	generate_events(zbx_uint64_t hostid, int maintenance_from)
{
	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	triggerid;
	int		value_before, value_after;
	DB_EVENT	event;
	time_t		now;

	now = time(NULL);

	result = DBselect(
			"select t.triggerid"
			" from triggers t,functions f,items i"
			" where t.triggerid=f.triggerid"
				" and f.itemid=i.itemid"
				" and t.status=%d"
				" and i.status=%d"
				" and i.hostid=" ZBX_FS_UI64,
			TRIGGER_STATUS_ENABLED,
			ITEM_STATUS_ACTIVE,
			hostid);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(triggerid, row[0]);

		if (SUCCEED != get_trigger_value(triggerid, maintenance_from, &value_before))
			continue;

		if (SUCCEED != get_trigger_value(triggerid, now, &value_after))
			continue;

		if (value_before == value_after)
			continue;

		/* Preparing event for processing */
		memset(&event,0,sizeof(DB_EVENT));
		event.eventid = 0;
		event.source = EVENT_SOURCE_TRIGGERS;
		event.object = EVENT_OBJECT_TRIGGER;
		event.objectid = triggerid;
		event.clock = now;
		event.value = value_after;
		event.acknowledged = 0;

		process_event(&event);
	}
	DBfree_result(result);
}

static void	update_maintenance_hosts(zbx_host_maintenance_t *hm, int hm_count)
{
	const char	*__function_name = "update_maintenance_hosts";
	int		i;
	zbx_uint64_t	*ids = NULL, hostid;
	int		ids_alloc = 0, ids_num = 0,
			maintenance_type, maintenance_from;
	DB_RESULT	result;
	DB_ROW		row;
	char		*sql = NULL;
	int		sql_alloc = 1024, sql_offset;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	sql = zbx_malloc(sql, sql_alloc);

	DBbegin();

	for (i = 0; i < hm_count; i ++)
	{
		if (SUCCEED == uint64_array_exists(ids, ids_num, hm[i].hostid))
			continue;

		if (hm[i].host_maintenanceid != hm[i].maintenanceid || hm[i].host_maintenance_status != HOST_MAINTENANCE_STATUS_ON ||
				hm[i].host_maintenance_type != hm[i].maintenance_type || hm[i].host_maintenance_from == 0)
		{
			sql_offset = 0;
			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 128,
					"update hosts"
					" set maintenanceid=" ZBX_FS_UI64 ","
						"maintenance_status=%d,"
						"maintenance_type=%d",
					hm[i].maintenanceid,
					HOST_MAINTENANCE_STATUS_ON,
					hm[i].maintenance_type);

			if (hm[i].host_maintenance_from == 0)
				zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 64,
						",maintenance_from=%d",
						hm[i].maintenance_from);

			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 64,
					" where hostid=" ZBX_FS_UI64,
					hm[i].hostid);

			DBexecute("%s", sql);

			DCconfig_set_maintenance(hm[i].hostid, HOST_MAINTENANCE_STATUS_ON,
					hm[i].maintenance_type, hm[i].maintenance_from);
		}

		uint64_array_add(&ids, &ids_alloc, &ids_num, hm[i].hostid, 4);
	}

	sql_offset = 0;
	zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 128,
			"select hostid,maintenance_type,maintenance_from"
			" from hosts"
			" where status=%d"
			" and maintenance_status=%d",
			HOST_STATUS_MONITORED,
			HOST_MAINTENANCE_STATUS_ON);

	if (NULL != ids && 0 != ids_num)
	{
		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 16, " and not");
		DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "hostid", ids, ids_num);
	}

	result = DBselect("%s", sql);

	ids_num = 0;
	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(hostid, row[0]);

		uint64_array_add(&ids, &ids_alloc, &ids_num, hostid, 4);

		if (MAINTENANCE_TYPE_NORMAL != (maintenance_type = atoi(row[1])))
			continue;

		maintenance_from = atoi(row[2]);

		generate_events(hostid, maintenance_from);
	}
	DBfree_result(result);

	sql_offset = 0;
	zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 128,
			"update hosts"
			" set maintenanceid=0,"
				"maintenance_status=%d,"
				"maintenance_type=0,"
				"maintenance_from=0"
			" where",
			HOST_MAINTENANCE_STATUS_OFF);

	if (NULL != ids && 0 != ids_num)
	{
		DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "hostid", ids, ids_num);
		DBexecute("%s", sql);
	}

	DBcommit();

	zbx_free(sql);
	zbx_free(ids);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

static void	process_maintenance()
{
	const char			*__function_name = "process_maintenance";
	DB_RESULT			result;
	DB_ROW				row;
	int				day, wday, mon, mday, sec;
	struct tm			*tm;
	zbx_uint64_t			db_maintenanceid;
	time_t				now, db_active_since, maintenance_from;
	zbx_timeperiod_type_t		db_timeperiod_type;
	int				db_every, db_month, db_dayofweek, db_day, db_start_time,
					db_period, db_start_date, db_maintenance_type;
	static zbx_host_maintenance_t	*hm = NULL;
	static int			hm_alloc = 4;
	int				hm_count = 0;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	zbx_setproctitle("timer [processing maintenance periods]");

	if (NULL == hm)
		hm = zbx_malloc(hm, sizeof(zbx_host_maintenance_t) * hm_alloc);

	now = time(NULL);
	tm = localtime(&now);
	sec = tm->tm_hour * 3600 + tm->tm_min * 60 + tm->tm_sec;
	wday = (tm->tm_wday == 0 ? 7 : tm->tm_wday) - 1;	/* The number of days since Sunday, in the range 0 to 6. */
	mon = tm->tm_mon;					/* The number of months since January, in the range 0 to 11 */
	mday = tm->tm_mday;					/* The day of the month, in the range 1 to 31. */

	result = DBselect(
			"select m.maintenanceid,m.maintenance_type,m.active_since,"
				"tp.timeperiod_type,tp.every,tp.month,tp.dayofweek,"
				"tp.day,tp.start_time,tp.period,tp.start_date"
			" from maintenances m,maintenances_windows mw,timeperiods tp"
			" where m.maintenanceid=mw.maintenanceid"
				" and mw.timeperiodid=tp.timeperiodid"
				" and %d between m.active_since and m.active_till",
			now);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(db_maintenanceid, row[0]);
		db_maintenance_type	= atoi(row[1]);
		db_active_since		= (time_t)atoi(row[2]);
		db_timeperiod_type	= atoi(row[3]);
		db_every		= atoi(row[4]);
		db_month		= atoi(row[5]);
		db_dayofweek		= atoi(row[6]);
		db_day			= atoi(row[7]);
		db_start_time		= atoi(row[8]);
		db_period		= atoi(row[9]);
		db_start_date		= atoi(row[10]);

		switch (db_timeperiod_type) {
		case TIMEPERIOD_TYPE_ONETIME:
			if (db_start_date > now || now >= db_start_date + db_period)
				continue;
			maintenance_from = db_start_date;
			break;
		case TIMEPERIOD_TYPE_DAILY:
			day = now - (int)db_active_since;
			day = day / 86400 + ((day % 86400) ? 1 : 0);
			if (0 != (day % db_every))
				continue;

			if (db_start_time > sec || sec >= db_start_time + db_period)
				continue;
			maintenance_from = now - sec + db_start_time;
			break;
		case TIMEPERIOD_TYPE_WEEKLY:
			if (0 == (db_dayofweek & (1 << wday)))
				continue;

			day = now - (int)db_active_since;
			day = day / 86400 + ((day % 86400) ? 1 : 0);
			if (0 != ((day / 7 + ((day % 7) ? 1 : 0)) % db_every))
				continue;

			if (db_start_time > sec || sec >= db_start_time + db_period)
				continue;
			maintenance_from = now - sec + db_start_time;
			break;
		case TIMEPERIOD_TYPE_MONTHLY:
			if (0 == (db_month & (1 << mon)))
				continue;

			if (0 != db_day)
			{
				if (mday != db_day)
					continue;
			}
			else
			{
				if (0 == (db_dayofweek & (1 << wday)))
					continue;

				if (0 != ((mday / 7 + ((mday % 7) ? 1 : 0)) % db_every))
					continue;
			}

			if (db_start_time > sec || sec >= db_start_time + db_period)
				continue;
			maintenance_from = now - sec + db_start_time;
			break;
		default:
			continue;
		}

		process_maintenance_hosts(&hm, &hm_alloc, &hm_count, maintenance_from, db_maintenanceid, db_maintenance_type);
	}
	DBfree_result(result);

	update_maintenance_hosts(hm, hm_count);
}

/******************************************************************************
 *                                                                            *
 * Function: main_timer_loop                                                  *
 *                                                                            *
 * Purpose: periodically updates time-related triggers                        *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments: does update once per 30 seconds (hardcoded)                      *
 *                                                                            *
 ******************************************************************************/
void main_timer_loop()
{
	int	now, nextcheck, sleeptime,
		maintenance = 1;

	DBconnect(ZBX_DB_CONNECT_NORMAL);

	for (;;) {
		process_time_functions();
		if (1 == maintenance)
			process_maintenance();

		now = time(NULL);
		nextcheck = now + TIMER_DELAY - (now % TIMER_DELAY);
		sleeptime = nextcheck - now;

		/* process maintenance every minute */
		maintenance = (0 == (nextcheck % 60)) ? 1 : 0;

		zbx_setproctitle("timer [sleeping for %d seconds]", sleeptime);
		sleep(sleeptime);
	}

	DBclose();
}
