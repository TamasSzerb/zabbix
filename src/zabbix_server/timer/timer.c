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
			" and i.itemid=f.itemid" DB_NODE,
			ZBX_SQL_ITEM_SELECT,
			HOST_STATUS_MONITORED,
			ITEM_STATUS_ACTIVE,
			DBnode_local("h.hostid"));

	while (NULL != (row = DBfetch(result)))
	{
		DBget_item_from_db(&item, row);

		DBbegin();
		update_functions(&item);
		update_triggers(item.itemid);
		DBcommit();
	}

	DBfree_result(result);
}

typedef struct zbx_host_maintenance_s {
	zbx_uint64_t	hostid;
	time_t		start_timestamp;
	zbx_uint64_t	maintenanceid;
	zbx_uint64_t	db_maintenanceid;
	int		db_maintenance_status;
} zbx_host_maintenance_t;

static int	get_host_maintenance_nearestindex(zbx_host_maintenance_t *hm, int hm_count,
		zbx_uint64_t hostid, time_t start_timestamp, zbx_uint64_t maintenanceid)
{
	int	first_index, last_index, index;

	if (hm_count == 0)
		return 0;

	first_index = 0;
	last_index = hm_count - 1;
	while (1)
	{
		index = first_index + (last_index - first_index) / 2;

		if (hm[index].hostid == hostid && hm[index].start_timestamp == start_timestamp && hm[index].maintenanceid == maintenanceid)
			return index;
		else if (last_index == first_index)
		{
			if (hm[index].hostid < hostid ||
					(hm[index].hostid == hostid && hm[index].start_timestamp < start_timestamp) ||
					(hm[index].hostid == hostid && hm[index].start_timestamp == start_timestamp  && hm[index].maintenanceid < maintenanceid))
				index++;
			return index;
		}
		else if (hm[index].hostid < hostid ||
				(hm[index].hostid == hostid && hm[index].start_timestamp < start_timestamp) ||
				(hm[index].hostid == hostid && hm[index].start_timestamp == start_timestamp  && hm[index].maintenanceid < maintenanceid))
			first_index = index + 1;
		else
			last_index = index;
	}
}

static zbx_host_maintenance_t *get_host_maintenance(zbx_host_maintenance_t **hm, int *hm_alloc, int *hm_count,
		zbx_uint64_t hostid, time_t start_timestamp, zbx_uint64_t maintenanceid,
		zbx_uint64_t db_maintenanceid, int db_maintenance_status)
{
	int	hm_index;

	hm_index = get_host_maintenance_nearestindex(*hm, *hm_count, hostid, start_timestamp, maintenanceid);
	if (hm_index < *hm_count && (*hm)[hm_index].hostid == hostid && (*hm)[hm_index].start_timestamp == start_timestamp &&
			(*hm)[hm_index].maintenanceid == maintenanceid)
		return &(*hm)[hm_index];

	if (*hm_alloc == *hm_count)
	{
		*hm_alloc += 4;
		*hm = zbx_realloc(*hm, *hm_alloc * sizeof(zbx_host_maintenance_t));
	}

	memmove(&(*hm)[hm_index + 1], &(*hm)[hm_index], sizeof(zbx_host_maintenance_t) * (*hm_count - hm_index));

	(*hm)[hm_index].hostid = hostid;
	(*hm)[hm_index].start_timestamp = start_timestamp;
	(*hm)[hm_index].maintenanceid = maintenanceid;
	(*hm)[hm_index].db_maintenanceid = db_maintenanceid;
	(*hm)[hm_index].db_maintenance_status = db_maintenance_status;
	(*hm_count)++;

	return &(*hm)[hm_index];
}

static void	process_maintenance_hosts(zbx_host_maintenance_t **hm, int *hm_alloc, int *hm_count,
		time_t start_timestamp, zbx_uint64_t maintenanceid)
{
	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	db_hostid, db_maintenanceid;
	int		db_maintenance_status;

	zabbix_log(LOG_LEVEL_DEBUG, "In process_maintenance_hosts()");

	result = DBselect(
			"select h.hostid,h.maintenanceid,h.maintenance_status "
			"from maintenances_hosts mh,hosts h "
			"where mh.hostid=h.hostid and "
				"h.status=%d and "
				"mh.maintenanceid=" ZBX_FS_UI64,
			HOST_STATUS_MONITORED,
			maintenanceid);

	while (NULL != (row = DBfetch(result)))
	{
		db_hostid = zbx_atoui64(row[0]);
		db_maintenanceid = zbx_atoui64(row[1]);
		db_maintenance_status = atoi(row[2]);

		get_host_maintenance(hm, hm_alloc, hm_count, db_hostid, start_timestamp, maintenanceid,
				db_maintenanceid, db_maintenance_status);
	}

	DBfree_result(result);

	result = DBselect(
			"select h.hostid,h.maintenanceid,h.maintenance_status "
			"from maintenances_groups mg,hosts_groups hg,hosts h "
			"where mg.groupid=hg.groupid and "
				"hg.hostid=h.hostid and "
				"h.status=%d and "
				"mg.maintenanceid=" ZBX_FS_UI64,
			HOST_STATUS_MONITORED,
			maintenanceid);

	while (NULL != (row = DBfetch(result)))
	{
		db_hostid = zbx_atoui64(row[0]);
		db_maintenanceid = zbx_atoui64(row[1]);
		db_maintenance_status = atoi(row[2]);

		get_host_maintenance(hm, hm_alloc, hm_count, db_hostid, start_timestamp, maintenanceid,
				db_maintenanceid, db_maintenance_status);
	}

	DBfree_result(result);
}

static void	update_maintenance_hosts(zbx_host_maintenance_t *hm, int hm_count)
{
	int		i;
/*	struct tm	*tm;*/
	static char	*hosts = NULL;
	static int	hosts_alloc = 32;
	int		hosts_offset = 0;
	DB_RESULT	result;
	DB_ROW		row;

	zabbix_log(LOG_LEVEL_DEBUG, "In update_maintenance_hosts()");

	if (NULL == hosts)
		hosts = zbx_malloc(hosts, hosts_alloc);
	*hosts = '\0';
	
	for (i = 0; i < hm_count; i ++)
	{
		if (SUCCEED == uint64_in_list(hosts, hm[i].hostid))
			continue;

/*		tm = localtime(&hm[i].start_timestamp);
		zabbix_log(LOG_LEVEL_DEBUG, "===> %02d%02d%04d %02d:%02d:%02d " ZBX_FS_UI64 " " ZBX_FS_UI64, tm->tm_mday, tm->tm_mon+1, tm->tm_year + 1900, tm->tm_hour, tm->tm_min, tm->tm_sec,
				hm[i].hostid, hm[i].maintenanceid);
*/
		if (hm[i].db_maintenanceid != hm[i].maintenanceid || hm[i].db_maintenance_status != HOST_MAINTENANCE_STATUS_ON)
		{
			DBexecute("update hosts "
					"set maintenanceid=" ZBX_FS_UI64 ",maintenance_status=%d "
					"where hostid=" ZBX_FS_UI64,
					hm[i].maintenanceid,
					HOST_MAINTENANCE_STATUS_ON,
					hm[i].hostid);
		}

		zbx_snprintf_alloc(&hosts, &hosts_alloc, &hosts_offset, 32, "%s" ZBX_FS_UI64,
				0 == hosts_offset ? "" : ",",
				hm[i].hostid);
	}

	if (0 == hosts_offset)
		result = DBselect(
				"select hostid "
				"from hosts "
				"where status=%d and "
					"maintenance_status=%d",
				HOST_STATUS_MONITORED,
				HOST_MAINTENANCE_STATUS_ON);
	else
		result = DBselect(
				"select hostid "
				"from hosts "
				"where status=%d and "
					"maintenance_status=%d and "
					"not hostid in (%s)",
				HOST_STATUS_MONITORED,
				HOST_MAINTENANCE_STATUS_ON,
				hosts);

	while (NULL != (row = DBfetch(result)))
	{
		DBexecute("update hosts "
				"set maintenanceid=0,maintenance_status=%d "
				"where hostid=%s",
				HOST_MAINTENANCE_STATUS_OFF,
				row[0]);
	}

	DBfree_result(result);
}

static void	process_maintenance()
{
	DB_RESULT			result;
	DB_ROW				row;
	int				day, wday, mon, mday, sec;
	struct tm			*tm;
	zbx_uint64_t			db_maintenanceid;
	time_t				now, db_active_since, start_timestamp;
	zbx_timeperiod_type_t		db_timeperiod_type;
	int				db_every, db_month, db_dayofweek, db_day, db_start_time,
					db_period, db_date;
	static zbx_host_maintenance_t	*hm = NULL;
	static int			hm_alloc = 4;
	int				hm_count = 0;

	zabbix_log(LOG_LEVEL_DEBUG, "In process_maintenance()");

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
			"select m.maintenanceid,m.active_since,tp.timeperiod_type,tp.every,"
				"tp.month,tp.dayofweek,tp.day,tp.start_time,tp.period,tp.date "
			"from maintenances m,maintenances_windows mw,timeperiods tp "
			"where m.maintenanceid=mw.maintenanceid and "
				"mw.timeperiodid=tp.timeperiodid and "
				"%d between m.active_since and m.active_till",
			now);

	while (NULL != (row = DBfetch(result)))
	{
		db_maintenanceid	= zbx_atoui64(row[0]);
		db_active_since		= (time_t)atoi(row[1]);
		db_timeperiod_type	= atoi(row[2]);
		db_every		= atoi(row[3]);
		db_month		= atoi(row[4]);
		db_dayofweek		= atoi(row[5]);
		db_day			= atoi(row[6]);
		db_start_time		= atoi(row[7]);
		db_period		= atoi(row[8]);
		db_date			= atoi(row[9]);

		switch (db_timeperiod_type) {
		case TIMEPERIOD_TYPE_ONETIME:
			if (db_date > now || now > db_date + db_period)
				continue;
			start_timestamp = db_date;
			break;
		case TIMEPERIOD_TYPE_DAILY:
			day = now - (int)db_active_since;
			day = day / 86400 + ((day % 86400) ? 1 : 0);
			if (0 != (day % db_every))
				continue;

			if (db_start_time > sec || sec > db_start_time + db_period)
				continue;
			start_timestamp = now - sec + db_start_time;
			break;
		case TIMEPERIOD_TYPE_WEEKLY:
			if (0 == (db_dayofweek & (1 << wday)))
				continue;

			day = now - (int)db_active_since;
			day = day / 86400 + ((day % 86400) ? 1 : 0);
			if (0 != ((day / 7 + ((day % 7) ? 1 : 0)) % db_every))
				continue;

			if (db_start_time > sec || sec > db_start_time + db_period)
				continue;
			start_timestamp = now - sec + db_start_time;
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

			if (db_start_time > sec || sec > db_start_time + db_period)
				continue;
			start_timestamp = now - sec + db_start_time;
			break;
		default:
			continue;
		}

		process_maintenance_hosts(&hm, &hm_alloc, &hm_count, start_timestamp, db_maintenanceid);
	}

	update_maintenance_hosts(hm, hm_count);

	DBfree_result(result);
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
	DBconnect(ZBX_DB_CONNECT_NORMAL);

	for (;;) {
		process_time_functions();
		process_maintenance();

		zbx_setproctitle("timer [sleeping for %d seconds]", TIMER_DELAY);
		sleep(TIMER_DELAY);
	}

	DBclose();
}
