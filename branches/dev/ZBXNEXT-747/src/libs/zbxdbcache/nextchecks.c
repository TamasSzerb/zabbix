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
#include "log.h"

#include "db.h"
#include "dbcache.h"

typedef struct
{
	zbx_uint64_t	itemid;
	time_t		now;
	char		*error_msg;	/* for not supported items */
}
ZBX_DC_NEXTCHECK;

static ZBX_DC_NEXTCHECK	*nextchecks = NULL;
static int		nextcheck_allocated = 64;
static int		nextcheck_num;

/******************************************************************************
 *                                                                            *
 * Function: DCinit_nextchecks                                                *
 *                                                                            *
 * Purpose: initialize nextchecks array                                       *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 ******************************************************************************/
void	DCinit_nextchecks()
{
	const char	*__function_name = "DCinit_nextchecks";

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	if (NULL == nextchecks)
		nextchecks = zbx_malloc(nextchecks, nextcheck_allocated * sizeof(ZBX_DC_NEXTCHECK));

	nextcheck_num = 0;

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

/******************************************************************************
 *                                                                            *
 * Function: DCrelease_nextchecks                                             *
 *                                                                            *
 * Purpose: free memory allocated for `error_msg'es                           *
 *                                                                            *
 * Author: Dmitry Borovikov                                                   *
 *                                                                            *
 ******************************************************************************/
static void	DCrelease_nextchecks()
{
	const char	*__function_name = "DCrelease_nextchecks";

	int	i;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	for (i = 0; i < nextcheck_num; i++)
	{
		if (nextchecks[i].error_msg != NULL)
			zbx_free(nextchecks[i].error_msg);
	}

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

/******************************************************************************
 *                                                                            *
 * Function: DCadd_nextcheck                                                  *
 *                                                                            *
 * Purpose: add item nextcheck to the array                                   *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 ******************************************************************************/
void	DCadd_nextcheck(zbx_uint64_t itemid, time_t now, const char *error_msg)
{
	const char	*__function_name = "DCadd_nextcheck";

	int	i;
	size_t	sz;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	if (NULL == error_msg)
		return;

	sz = sizeof(ZBX_DC_NEXTCHECK);

	i = get_nearestindex(nextchecks, sizeof(ZBX_DC_NEXTCHECK), nextcheck_num, itemid);
	if (i < nextcheck_num && nextchecks[i].itemid == itemid)	/* item exists? */
	{
		if (nextchecks[i].now < now)
		{
			/* delete item */
			memmove(&nextchecks[i], &nextchecks[i + 1], sz * (nextcheck_num - (i + 1)));
			nextcheck_num--;
		}
		else
			return;
	}

	if (nextcheck_allocated == nextcheck_num)
	{
		nextcheck_allocated += 64;
		nextchecks = zbx_realloc(nextchecks, nextcheck_allocated * sz);
	}

	/* insert new item */
	memmove(&nextchecks[i + 1], &nextchecks[i], sz * (nextcheck_num - i));

	nextchecks[i].itemid = itemid;
	nextchecks[i].now = now;
	nextchecks[i].error_msg = (NULL != error_msg ? zbx_strdup(NULL, error_msg) : NULL);

	nextcheck_num++;

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

/******************************************************************************
 *                                                                            *
 * Function: DCflush_nextchecks                                               *
 *                                                                            *
 * Purpose: add item nextcheck to the array                                   *
 *                                                                            *
 * Author: Alexander Vladishev, Dmitry Borovikov                              *
 *                                                                            *
 ******************************************************************************/
void	DCflush_nextchecks()
{
	struct event_objectid_clock
	{
		zbx_uint64_t	objectid;	/* triggerid */
		time_t		clock;		/* `now' from items */
	};

	const char	*__function_name = "DCflush_nextchecks";

	int		i, sql_offset = 0, sql_allocated = 4096;
	char		*sql = NULL;

	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	triggerid;
	zbx_uint64_t	itemid;
	zbx_uint64_t	events_maxid = 0;
	char		*error_msg_esc = NULL;

	char		*sql_select = NULL;
	int		sql_select_offset = 0, sql_select_allocated = 512;

	/* a crutch for the function `DBadd_condition_alloc' */
	zbx_uint64_t	*ids = NULL;
	int		ids_allocated = 0, ids_num = 0;

	zbx_uint64_t	*triggerids = NULL;
	int		triggerids_allocated = 0, triggerids_num = 0;

	struct event_objectid_clock 	*events = NULL;
	int				events_num = 0, events_allocated = 32;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	if (0 == nextcheck_num)
		return;

	sql = zbx_malloc(sql, sql_allocated);

	DBbegin();

#ifdef HAVE_ORACLE
	zbx_snprintf_alloc(&sql, &sql_allocated, &sql_offset, 8, "begin\n");
#endif

	/* dealing with items */
	for (i = 0; i < nextcheck_num; i++)
	{
		if (NULL == nextchecks[i].error_msg)
			continue;

		uint64_array_add(&ids, &ids_allocated, &ids_num, nextchecks[i].itemid, 64);

		error_msg_esc = DBdyn_escape_string_len(nextchecks[i].error_msg, ITEM_ERROR_LEN);
		zbx_snprintf_alloc(&sql, &sql_allocated, &sql_offset, 128 + strlen(error_msg_esc),
				"update items set status=%d,lastclock=%d,error='%s' where itemid=" ZBX_FS_UI64 ";\n",
				ITEM_STATUS_NOTSUPPORTED,
				(int)nextchecks[i].now,
				error_msg_esc,
				nextchecks[i].itemid);
		zbx_free(error_msg_esc);

		DBexecute_overflowed_sql(&sql, &sql_allocated, &sql_offset);
	}

	/* dealing with notsupported items */
	if (ids_num > 0)
	{
		sql_select = zbx_malloc(sql_select, sql_select_allocated);
		events = zbx_malloc(events, events_allocated * sizeof(struct event_objectid_clock));

		/* preparing triggers */
		zbx_snprintf_alloc(&sql_select, &sql_select_allocated, &sql_select_offset, 256,
				"select t.triggerid,i.itemid"
				" from triggers t,functions f,items i"
				" where t.triggerid=f.triggerid"
					" and f.itemid=i.itemid"
					" and t.status=%d"
					" and t.value_flags<>%d"
					" and",
				TRIGGER_STATUS_ENABLED,
				TRIGGER_VALUE_FLAG_UNKNOWN);
		DBadd_condition_alloc(&sql_select, &sql_select_allocated, &sql_select_offset,
				"i.itemid", ids, ids_num);
		result = DBselect("%s", sql_select);

		zbx_free(sql_select);

		/* processing triggers */
		while (NULL != (row = DBfetch(result)))
		{
			ZBX_STR2UINT64(triggerid, row[0]);
			ZBX_STR2UINT64(itemid, row[1]);

			/* do not generate multiple unknown events for a trigger */
			if (SUCCEED == uint64_array_exists(triggerids, triggerids_num, triggerid))
				continue;

			uint64_array_add(&triggerids, &triggerids_allocated, &triggerids_num, triggerid, 64);

			/* index `i' will surely contain necessary itemid */
			i = get_nearestindex(nextchecks, sizeof(ZBX_DC_NEXTCHECK), nextcheck_num, itemid);

			if (i == nextcheck_num || nextchecks[i].itemid != itemid)
			{
				THIS_SHOULD_NEVER_HAPPEN;
				continue;
			}

			error_msg_esc = DBdyn_escape_string_len(nextchecks[i].error_msg, TRIGGER_ERROR_LEN);
			zbx_snprintf_alloc(&sql, &sql_allocated, &sql_offset, 128 + strlen(error_msg_esc),
					"update triggers set value_flags=%d,lastchange=%d,error='%s' where triggerid=" ZBX_FS_UI64";\n",
							TRIGGER_VALUE_FLAG_UNKNOWN,
							nextchecks[i].now,
							error_msg_esc,
							triggerid);
			zbx_free(error_msg_esc);

			if (events_num == events_allocated)
			{
				events_allocated += 32;
				events = zbx_realloc(events, events_allocated * sizeof(struct event_objectid_clock));
			}
			events[events_num].objectid = triggerid;
			events[events_num].clock = nextchecks[i].now;
			events_num++;

			DBexecute_overflowed_sql(&sql, &sql_allocated, &sql_offset);
		}
		DBfree_result(result);

		/* dealing with events */
		for (i = 0; i < events_num; i++)
		{
			events_maxid = DBget_maxid("events");

			zbx_snprintf_alloc(&sql, &sql_allocated, &sql_offset, 256,
					"insert into events (eventid,source,object,objectid,clock,value,value_changed) "
					"values (" ZBX_FS_UI64 ",%d,%d," ZBX_FS_UI64 ",%d,%d,%d);\n",
					events_maxid,
					EVENT_SOURCE_TRIGGERS,
					EVENT_OBJECT_TRIGGER,
					events[i].objectid,
					events[i].clock,
					TRIGGER_VALUE_UNKNOWN,
					TRIGGER_VALUE_CHANGED_NO);

			DBexecute_overflowed_sql(&sql, &sql_allocated, &sql_offset);
		}

		zbx_free(events);
	}

#ifdef HAVE_ORACLE
	zbx_snprintf_alloc(&sql, &sql_allocated, &sql_offset, 8, "end;\n");
#endif

	if (sql_offset > 16)	/* In ORACLE always present begin..end; */
		DBexecute("%s", sql);

	zbx_free(sql);
	zbx_free(ids);
	zbx_free(triggerids);

	DCrelease_nextchecks();

	DBcommit();

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}
