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


#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <netinet/in.h>
#include <netdb.h>

#include <signal.h>

#include <string.h>

#include <time.h>

#include <sys/socket.h>
#include <errno.h>

/* Functions: pow(), round() */
#include <math.h>

#include "common.h"
#include "db.h"
#include "log.h"
#include "zlog.h"

#include "actions.h"
#include "events.h"

/******************************************************************************
 *                                                                            *
 * Function: add_trigger_info                                                 *
 *                                                                            *
 * Purpose: add trigger info to event if required                             *
 *                                                                            *
 * Parameters: event - event data (event.triggerid)                           *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static void	add_trigger_info(DB_EVENT *event)
{
	DB_RESULT	result;
	DB_ROW		row;

	if(event->triggerid == 0)	return;

	result = DBselect("select description,priority,comments,url from triggers where triggerid=" ZBX_FS_UI64,
		event->triggerid);
	row = DBfetch(result);

	event->trigger_description[0]=0;
	event->trigger_comments[0]=0;
	event->trigger_url[0]=0;

	if(row)
	{
		strscpy(event->trigger_description, row[0]);
		event->trigger_priority = atoi(row[1]);
		strscpy(event->trigger_comments, row[2]);
		strscpy(event->trigger_url, row[3]);
	}

	DBfree_result(result);
}

/******************************************************************************
 *                                                                            *
 * Function: process_event                                                    *
 *                                                                            *
 * Purpose: process new event                                                 *
 *                                                                            *
 * Parameters: event - event data (event.eventid - new event)                 *
 *                                                                            *
 * Return value: SUCCESS - event added                                        *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments: Cannot use action->userid as it may also be groupid              *
 *                                                                            *
 ******************************************************************************/
int	process_event(DB_EVENT *event)
{
	DB_RESULT	result;
	DB_ROW		row;

	zabbix_log(LOG_LEVEL_DEBUG,"In process_event(eventid:" ZBX_FS_UI64 ",triggerid:" ZBX_FS_UI64 ")",
			event->eventid, event->triggerid);

	add_trigger_info(event);

	if(event->eventid == 0)
	{
		event->eventid = DBget_maxid("event_maxid");
/*
		result = DBselect("select event_maxid from nodes where nodeid=%d", CONFIG_NODEID);
		row = DBfetch(result);

		if(!row || DBis_null(row[0])==SUCCEED)
		{
			event->eventid = CONFIG_NODEID*(zbx_uint64_t)__UINT64_C(100000000000000) + 1;
		}
		else
		{
			ZBX_STR2UINT64(event->eventid, row[0]);
			event->eventid = CONFIG_NODEID*(zbx_uint64_t)__UINT64_C(100000000000000) + event->eventid;
			event->eventid++;
		}
		DBexecute("update nodes set event_maxid=event_maxid+1 where nodeid=%d",
				CONFIG_NODEID);
		DBfree_result(result);
		*/
	}
	DBexecute("insert into events(eventid,triggerid,clock,value) values(" ZBX_FS_UI64 "," ZBX_FS_UI64 ",%d,%d)",
		event->eventid,event->triggerid, event->clock, event->value);

	/* Cancel currently active alerts */
	if(event->value == TRIGGER_VALUE_FALSE || event->value == TRIGGER_VALUE_TRUE)
	{
		DBexecute("update alerts set retries=3,error='Trigger changed its status. WIll not send repeats.' where triggerid=" ZBX_FS_UI64 " and repeats>0 and status=%d",
			event->triggerid, ALERT_STATUS_NOT_SENT);
	}

	apply_actions(event);

	if(event->value == TRIGGER_VALUE_TRUE)
	{
//		update_services(trigger->triggerid, trigger->priority);
		update_services(event->triggerid, event->trigger_priority);
	}
	else
	{
		update_services(event->triggerid, 0);
	}

	zabbix_log(LOG_LEVEL_DEBUG,"End of add_event()");
	
	return SUCCEED;
}
