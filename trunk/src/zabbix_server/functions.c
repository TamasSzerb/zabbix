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

#include <ctype.h>

/* Functions: pow(), round() */
#include <math.h>

#include "common.h"
#include "db.h"
#include "log.h"
#include "zlog.h"
#include "security.h"

#include "evalfunc.h"
#include "functions.h"
#include "expression.h"
#include "trapper/autoregister.h"

/******************************************************************************
 *                                                                            *
 * Function: update_functions                                                 *
 *                                                                            *
 * Purpose: re-calculate and updates values of functions related to the item  *
 *                                                                            *
 * Parameters: item - item to update functions for                            *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
void	update_functions(DB_ITEM *item)
{
	DB_FUNCTION	function;
	DB_RESULT	result;
	DB_ROW		row;
	char		value[MAX_STRING_LEN];
	char		value_esc[MAX_STRING_LEN];
	char		*lastvalue;
	int		ret=SUCCEED;

	zabbix_log( LOG_LEVEL_DEBUG, "In update_functions(" ZBX_FS_UI64 ")",
		item->itemid);

/* Oracle does'n support this */
/*	zbx_snprintf(sql,sizeof(sql),"select function,parameter,itemid,lastvalue from functions where itemid=%d group by function,parameter,itemid order by function,parameter,itemid",item->itemid);*/
	result = DBselect("select distinct function,parameter,itemid,lastvalue from functions where itemid=" ZBX_FS_UI64,
		item->itemid);

	while((row=DBfetch(result)))
	{
		function.function=row[0];
		function.parameter=row[1];
		ZBX_STR2UINT64(function.itemid,row[2]);
/*		function.itemid=atoi(row[2]); */
		lastvalue=row[3];

		zabbix_log( LOG_LEVEL_DEBUG, "ItemId:" ZBX_FS_UI64 " Evaluating %s(%d)",
			function.itemid,function.function,function.parameter);

		ret = evaluate_FUNCTION(value,item,function.function,function.parameter);
		if( FAIL == ret)	
		{
			zabbix_log( LOG_LEVEL_DEBUG, "Evaluation failed for function:%s",function.function);
			continue;
		}
		zabbix_log( LOG_LEVEL_DEBUG, "Result of evaluate_FUNCTION [%s]",value);
		if (ret == SUCCEED)
		{
			/* Update only if lastvalue differs from new one */
			if( (lastvalue == NULL) || (strcmp(lastvalue,value) != 0))
			{
				DBescape_string(value,value_esc,MAX_STRING_LEN);
				DBexecute("update functions set lastvalue='%s' where itemid=" ZBX_FS_UI64 " and function='%s' and parameter='%s'",
					value_esc, function.itemid, function.function, function.parameter );
			}
			else
			{
				zabbix_log( LOG_LEVEL_DEBUG, "Do not update functions, same value");
			}
		}
	}

	DBfree_result(result);
}

/******************************************************************************
 *                                                                            *
 * Function: update_services_rec                                              *
 *                                                                            *
 * Purpose: re-calculate and updates status of the service and its childs     *
 *                                                                            *
 * Parameters: serviceid - item to update services for                        *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments: recursive function                                               *
 *                                                                            *
 ******************************************************************************/
void	update_services_rec(zbx_uint64_t serviceid)
{
	int	status;
	zbx_uint64_t	serviceupid;
	int	algorithm;
	time_t	now;

	DB_RESULT result;
	DB_RESULT result2;
	DB_ROW	row;
	DB_ROW	row2;

	result = DBselect("select l.serviceupid,s.algorithm from services_links l,services s where s.serviceid=l.serviceupid and l.servicedownid=" ZBX_FS_UI64,
		serviceid);
	status=0;
	while((row=DBfetch(result)))
	{
		ZBX_STR2UINT64(serviceupid,row[0]);
		algorithm=atoi(row[1]);
		if(SERVICE_ALGORITHM_NONE == algorithm)
		{
/* Do nothing */
		}
		else if((SERVICE_ALGORITHM_MAX == algorithm)
			||
			(SERVICE_ALGORITHM_MIN == algorithm))
		{
			/* Why it was so complex ?
			result2 = DBselect("select status from services s,services_links l where l.serviceupid=%d and s.serviceid=l.servicedownid",serviceupid);
			for(j=0;j<DBnum_rows(result2);j++)
			{
				if(atoi(DBget_field(result2,j,0))>status)
				{
					status=atoi(DBget_field(result2,j,0));
				}
			}
			DBfree_result(result2);*/

			if(SERVICE_ALGORITHM_MAX == algorithm)
			{
				result2 = DBselect("select count(*),max(status) from services s,services_links l where l.serviceupid=" ZBX_FS_UI64 " and s.serviceid=l.servicedownid",
					serviceupid);
			}
			/* MIN otherwise */
			else
			{
				result2 = DBselect("select count(*),min(status) from services s,services_links l where l.serviceupid=" ZBX_FS_UI64 " and s.serviceid=l.servicedownid",
					serviceupid);
			}
			row2=DBfetch(result2);
			if(row2 && DBis_null(row2[0]) != SUCCEED && DBis_null(row2[1]) != SUCCEED)
			{
				if(atoi(row2[0])!=0)
				{
					status=atoi(row2[1]);
				}
			}
			DBfree_result(result2);

			now=time(NULL);
			DBadd_service_alarm(serviceupid,status,now);
			DBexecute("update services set status=%d where serviceid=" ZBX_FS_UI64,
				status,serviceupid);
		}
		else
		{
			zabbix_log( LOG_LEVEL_ERR, "Unknown calculation algorithm of service status [%d]", algorithm);
			zabbix_syslog("Unknown calculation algorithm of service status [%d]", algorithm);
		}
	}
	DBfree_result(result);

	result = DBselect("select serviceupid from services_links where servicedownid=" ZBX_FS_UI64,
		serviceid);

	while((row=DBfetch(result)))
	{
		ZBX_STR2UINT64(serviceupid,row[0]);
		update_services_rec(serviceupid);
	}
	DBfree_result(result);
}

/******************************************************************************
 *                                                                            *
 * Function: update_services                                                  *
 *                                                                            *
 * Purpose: re-calculate and updates status of the service and its childs     *
 *                                                                            *
 * Parameters: serviceid - item to update services for                        *
 *             status - new status of the service                             *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
void	update_services(zbx_uint64_t triggerid, int status)
{
	DB_ROW	row;
	zbx_uint64_t	serviceid;

	DB_RESULT result;

	DBexecute("update services set status=%d where triggerid=" ZBX_FS_UI64,
		status,triggerid);

	result = DBselect("select serviceid from services where triggerid=" ZBX_FS_UI64,
		triggerid);

	while((row=DBfetch(result)))
	{
		ZBX_STR2UINT64(serviceid,row[0]);
		update_services_rec(serviceid);
	}

	DBfree_result(result);
	return;
}

/******************************************************************************
 *                                                                            *
 * Function: update_triggers                                                  *
 *                                                                            *
 * Purpose: re-calculate and updates values of triggers related to the item   *
 *                                                                            *
 * Parameters: itemid - item to update trigger values for                     *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
void	update_triggers(zbx_uint64_t itemid)
{
	char	exp[MAX_STRING_LEN];
	char	error[MAX_STRING_LEN];
	int	exp_value;
	DB_TRIGGER	trigger;
	DB_RESULT	result;
	DB_ROW		row;

	zabbix_log( LOG_LEVEL_DEBUG, "In update_triggers [itemid:" ZBX_FS_UI64 "]", itemid);

	result = DBselect("select distinct t.triggerid,t.expression,t.status,t.dep_level,t.priority,t.value,t.description from triggers t,functions f,items i where i.status<>%d and i.itemid=f.itemid and t.status=%d and f.triggerid=t.triggerid and f.itemid=" ZBX_FS_UI64,ITEM_STATUS_NOTSUPPORTED, TRIGGER_STATUS_ENABLED, itemid);

	while((row=DBfetch(result)))
	{
		ZBX_STR2UINT64(trigger.triggerid,row[0]);
		strscpy(trigger.expression,row[1]);
		strscpy(trigger.description,row[6]);
		trigger.status		= atoi(row[2]);
		trigger.priority	= atoi(row[4]);
		trigger.value		= atoi(row[5]);
		trigger.url		= row[6];
		trigger.comments	= row[7];

		/* NOTE: function 'evaluate_expression' require 'exp' with 'MAX_STRING_LEN' length*/
		memset(exp, 0, MAX_STRING_LEN);
		zbx_strlcpy(exp, trigger.expression, MAX_STRING_LEN-1);
		if( evaluate_expression(&exp_value, exp, error, sizeof(error)) != 0 )
		{
			zabbix_log( LOG_LEVEL_WARNING, "Expression [%s] cannot be evaluated [%s]",trigger.expression, error);
			zabbix_syslog("Expression [%s] cannot be evaluated [%s]",trigger.expression, error);
/*			DBupdate_trigger_value(&trigger, exp_value, time(NULL), error);*//* We shouldn't update triggervalue if expressions failed */
		}
		else
		{
			DBupdate_trigger_value(&trigger, exp_value, time(NULL), NULL);
		}
	}
	DBfree_result(result);
	zabbix_log( LOG_LEVEL_DEBUG, "End of update_triggers [%d]", itemid);
}

void	calc_timestamp(char *line,int *timestamp, char *format)
{
	int hh=0,mm=0,ss=0,yyyy=0,dd=0,MM=0;
	int hhc=0,mmc=0,ssc=0,yyyyc=0,ddc=0,MMc=0;
	int i,num;
	struct  tm      tm;
	time_t t;

	zabbix_log( LOG_LEVEL_DEBUG, "In calc_timestamp()");

	hh=mm=ss=yyyy=dd=MM=0;

	for(i=0;(format[i]!=0)&&(line[i]!=0);i++)
	{
		if(isdigit(line[i])==0)	continue;
		num=(int)line[i]-48;

		switch ((char) format[i]) {
			case 'h':
				hh=10*hh+num;
				hhc++;
				break;
			case 'm':
				mm=10*mm+num;
				mmc++;
				break;
			case 's':
				ss=10*ss+num;
				ssc++;
				break;
			case 'y':
				yyyy=10*yyyy+num;
				yyyyc++;
				break;
			case 'd':
				dd=10*dd+num;
				ddc++;
				break;
			case 'M':
				MM=10*MM+num;
				MMc++;
				break;
		}
	}

	zabbix_log( LOG_LEVEL_DEBUG, "hh [%d] mm [%d] ss [%d] yyyy [%d] dd [%d] MM [%d]",hh,mm,ss,yyyy,dd,MM);

	if(hh!=0&&mm!=0&&ss!=0&&yyyy!=0&&dd!=0&&MM!=0)
	{
		tm.tm_sec=ss;
		tm.tm_min=mm;
		tm.tm_hour=hh;
		tm.tm_mday=dd;
		tm.tm_mon=MM;
		tm.tm_year=yyyy-1900;

		t=mktime(&tm);
		if(t>0)
		{
			*timestamp=t;
		}
	}
	zabbix_log( LOG_LEVEL_DEBUG, "end timestamp [%d]", t);
	zabbix_log( LOG_LEVEL_DEBUG, "end timestamp [%d]", *timestamp);
}

/******************************************************************************
 *                                                                            *
 * Function: process_data                                                     *
 *                                                                            *
 * Purpose: process new item value                                            *
 *                                                                            *
 * Parameters: sockfd - descriptor of agent-server socket connection          *
 *             server - server name                                           *
 *             key - item's key                                               *
 *             value - new value of server:key                                *
 *             lastlogsize - if key=log[*], last size of log file             *
 *                                                                            *
 * Return value: SUCCEED - new value processed sucesfully                     *
 *               FAIL - otherwise                                             *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments: for trapper server process                                       *
 *                                                                            *
 ******************************************************************************/
int	process_data(zbx_sock_t *sock,char *server,char *key,char *value,char *lastlogsize, char *timestamp,
			char *source, char *severity)
{
	AGENT_RESULT	agent;

	DB_RESULT       result;
	DB_ROW	row;
	DB_ITEM	item;
	char	*s;

	char	server_esc[MAX_STRING_LEN];
	char	key_esc[MAX_STRING_LEN];

	zabbix_log( LOG_LEVEL_DEBUG, "In process_data([%s],[%s],[%s],[%s])",server,key,value,lastlogsize);

	init_result(&agent);

	DBescape_string(server, server_esc, MAX_STRING_LEN);
	DBescape_string(key, key_esc, MAX_STRING_LEN);

	result = DBselect("select %s where h.status=%d and h.hostid=i.hostid and h.host='%s' and i.key_='%s' and i.status=%d and i.type in (%d,%d) and" ZBX_COND_NODEID, ZBX_SQL_ITEM_SELECT, HOST_STATUS_MONITORED, server_esc, key_esc, ITEM_STATUS_ACTIVE, ITEM_TYPE_TRAPPER, ITEM_TYPE_ZABBIX_ACTIVE, LOCAL_NODE("h.hostid"));

	row=DBfetch(result);

	if(!row)
	{
		zabbix_log( LOG_LEVEL_DEBUG, "Before checking autoregistration for [%s]",server);

		if(autoregister(server) == SUCCEED)
		{
			DBfree_result(result);

			/* Same SQL */
			result = DBselect("select %s where h.status=%d and h.hostid=i.hostid and h.host='%s' and i.key_='%s' and i.status=%d and i.type in (%d,%d) and" ZBX_COND_NODEID, ZBX_SQL_ITEM_SELECT, HOST_STATUS_MONITORED, server_esc, key_esc, ITEM_STATUS_ACTIVE, ITEM_TYPE_TRAPPER, ITEM_TYPE_ZABBIX_ACTIVE, LOCAL_NODE("h.hostid"));
			row = DBfetch(result);
			if(!row)
			{
				DBfree_result(result);
				return  FAIL;
			}
		}
		else
		{
			DBfree_result(result);
			return  FAIL;
		}
	}

	DBget_item_from_db(&item,row);

	if( (item.type==ITEM_TYPE_ZABBIX_ACTIVE) && (check_security(sock->socket,item.trapper_hosts,1) == FAIL))
	{
		DBfree_result(result);
		return  FAIL;
	}

	zabbix_log( LOG_LEVEL_DEBUG, "Processing [%s]", value);

	if(strcmp(value,"ZBX_NOTSUPPORTED") ==0)
	{
			zabbix_log( LOG_LEVEL_WARNING, "Active parameter [%s] is not supported by agent on host [%s]", item.key, item.host_name);
			zabbix_syslog("Active parameter [%s] is not supported by agent on host [%s]", item.key, item.host_name);
			DBupdate_item_status_to_notsupported(item.itemid, "Not supported by agent");
	}
	
	s=value;
	if(	(strncmp(item.key,"log[",4)==0) ||
		(strncmp(item.key,"eventlog[",9)==0)
	)
	{
		item.lastlogsize=atoi(lastlogsize);
		item.timestamp=atoi(timestamp);

		calc_timestamp(value,&item.timestamp,item.logtimefmt);

		item.eventlog_severity=atoi(severity);
		item.eventlog_source=source;
		zabbix_log(LOG_LEVEL_DEBUG, "Value [%s] Lastlogsize [%s] Timestamp [%s]", value, lastlogsize, timestamp);
	}

	if(set_result_type(&agent, item.value_type, value) == SUCCEED)
	{
		process_new_value(&item,&agent);
		update_triggers(item.itemid);
	}
	else
	{
		zabbix_log( LOG_LEVEL_WARNING, "Type of received value [%s] is not suitable for [%s@%s]", value, item.key, item.host_name);
		zabbix_syslog("Type of received value [%s] is not suitable for [%s@%s]", value, item.key, item.host_name);
	}
 
	DBfree_result(result);

	free_result(&agent);

	return SUCCEED;
}

/******************************************************************************
 *                                                                            *
 * Function: add_history                                                      *
 *                                                                            *
 * Purpose: add new value to history                                          *
 *                                                                            *
 * Parameters: item - item data                                               *
 *             value - new value of the item                                  *
 *             now   - new value of the item                                  *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int	add_history(DB_ITEM *item, AGENT_RESULT *value, int now)
{
	int ret = SUCCEED;

	zabbix_log( LOG_LEVEL_DEBUG, "In add_history(%s,,%X,%X)", item->key, item->value_type,value->type);

	if(value->type & AR_UINT64)
		zabbix_log( LOG_LEVEL_DEBUG, "In add_history(%d,UINT64:" ZBX_FS_UI64 ")", item->itemid, value->ui64);
	if(value->type & AR_STRING)
		zabbix_log( LOG_LEVEL_DEBUG, "In add_history(%d,STRING:%s)", item->itemid, value->str);
	if(value->type & AR_DOUBLE)
		zabbix_log( LOG_LEVEL_DEBUG, "In add_history(%d,DOUBLE:" ZBX_FS_DBL ")", item->itemid, value->dbl);
	if(value->type & AR_TEXT)
		zabbix_log( LOG_LEVEL_DEBUG, "In add_history(%d,TEXT:[%s])", item->itemid, value->text);

	if(item->history>0)
	{
		if( (item->value_type==ITEM_VALUE_TYPE_FLOAT) || (item->value_type==ITEM_VALUE_TYPE_UINT64))
		{
			/* Should we store delta or original value? */
			if(item->delta == ITEM_STORE_AS_IS)
			{
				if(item->value_type==ITEM_VALUE_TYPE_UINT64)
				{
					if(value->type & AR_UINT64)
						DBadd_history_uint(item->itemid,value->ui64,now);
				}
				else if(item->value_type==ITEM_VALUE_TYPE_FLOAT)
				{
					if(value->type & AR_DOUBLE)
						DBadd_history(item->itemid,value->dbl,now);
					else if(value->type & AR_UINT64)
						DBadd_history(item->itemid,(double)value->ui64,now);
				}
			}
			/* Delta as speed of change */
			else if(item->delta == ITEM_STORE_SPEED_PER_SECOND)
			{
				/* Save delta */
				if( (item->value_type==ITEM_VALUE_TYPE_FLOAT) && (value->type & AR_DOUBLE))
				{
					if((item->prevorgvalue_null == 0) && (item->prevorgvalue_dbl <= value->dbl))
					{
						DBadd_history(item->itemid, (value->dbl - item->prevorgvalue_dbl)/(now-item->lastclock), now);
					}
				}
				else if( (item->value_type==ITEM_VALUE_TYPE_FLOAT) && (value->type & AR_UINT64))
				{
					if((item->prevorgvalue_null == 0) && (item->prevorgvalue_dbl <= (double)value->ui64))
					{
						DBadd_history(item->itemid, ((double)value->ui64 - item->prevorgvalue_dbl)/(now-item->lastclock), now);
					}
				}
				else if((item->value_type==ITEM_VALUE_TYPE_UINT64) && (value->type & AR_UINT64))
				{
					if((item->prevorgvalue_null == 0) && (item->prevorgvalue_uint64 <= value->ui64))
					{
						DBadd_history_uint(item->itemid, (zbx_uint64_t)(value->ui64 - item->prevorgvalue_uint64)/(now-item->lastclock), now);
					}
				}
			}
			/* Real delta: simple difference between values */
			else if(item->delta == ITEM_STORE_SIMPLE_CHANGE)
			{
				/* Save delta */
				if((item->value_type==ITEM_VALUE_TYPE_FLOAT) && (value->type & AR_DOUBLE))
				{
					if((item->prevorgvalue_null == 0) && (item->prevorgvalue_dbl <= value->dbl) )
					{
						DBadd_history(item->itemid, (value->dbl - item->prevorgvalue_dbl), now);
					}
				}
				else if((item->value_type==ITEM_VALUE_TYPE_FLOAT) && (value->type & AR_UINT64))
				{
					if((item->prevorgvalue_null == 0) && (item->prevorgvalue_dbl <= (double)value->ui64) )
					{
						DBadd_history(item->itemid, ((double)value->ui64 - item->prevorgvalue_dbl), now);
					}
				}
				else if((item->value_type==ITEM_VALUE_TYPE_UINT64) && (value->type & AR_UINT64))
				{
					if((item->prevorgvalue_null == 0) && (item->prevorgvalue_uint64 <= value->ui64) )
					{
						DBadd_history_uint(item->itemid, value->ui64 - item->prevorgvalue_uint64, now);
					}
				}
			}
			else
			{
				zabbix_log(LOG_LEVEL_ERR, "Value not stored for itemid [%d]. Unknown delta [%d]", item->itemid, item->delta);
				zabbix_syslog("Value not stored for itemid [%d]. Unknown delta [%d]", item->itemid, item->delta);
				ret = FAIL;
			}
		}
		else if(item->value_type==ITEM_VALUE_TYPE_STR)
		{
			if(value->type & AR_STRING)
				DBadd_history_str(item->itemid,value->str,now);
		}
		else if(item->value_type==ITEM_VALUE_TYPE_LOG)
		{
			if(value->type & AR_STRING)
				DBadd_history_log(item->itemid,value->str,now,item->timestamp,item->eventlog_source,item->eventlog_severity);
			DBexecute("update items set lastlogsize=%d where itemid=" ZBX_FS_UI64,
				item->lastlogsize,item->itemid);
		}
		else if(item->value_type==ITEM_VALUE_TYPE_TEXT)
		{
			if(value->type & AR_TEXT)
				DBadd_history_text(item->itemid,value->text,now);
		}
		else
		{
			zabbix_log(LOG_LEVEL_ERR, "Unknown value type [%d] for itemid [" ZBX_FS_UI64 "]",
				item->value_type,item->itemid);
		}
	}

	zabbix_log( LOG_LEVEL_DEBUG, "End of add_history");

	return ret;
}

/******************************************************************************
 *                                                                            *
 * Function: update_item                                                      *
 *                                                                            *
 * Purpose: update item info after new value is received                      *
 *                                                                            *
 * Parameters: item - item data                                               *
 *             value - new value of the item                                  *
 *             now   - current timestamp                                      * 
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int	update_item(DB_ITEM *item, AGENT_RESULT *value, time_t now)
{
	char		value_esc[MAX_STRING_LEN];
	char		value_str[MAX_STRING_LEN];
	int ret = SUCCEED;

	zabbix_log( LOG_LEVEL_DEBUG, "In update_item()");

	value_str[0]	= '\0';
	value_esc[0]	= '\0';
	
	if(value->type & AR_UINT64)
	{
		zbx_snprintf(value_str, sizeof(value_str),ZBX_FS_UI64, value->ui64);
	}
	if(value->type & AR_DOUBLE)
	{
		zbx_snprintf(value_str,sizeof(value_str),"%f", value->dbl);
	}
	if(value->type & AR_STRING)
	{
		strscpy(value_str, value->str);
	}
	if(value->type & AR_TEXT)
	{
		strscpy(value_str, value->text);
	}

	if(item->delta == ITEM_STORE_AS_IS)
	{
		switch(value->type) {
			case AR_DOUBLE:
				DBexecute("update items set nextcheck=%d,prevvalue=lastvalue,lastvalue='%f',lastclock=%d where itemid=" ZBX_FS_UI64,
					calculate_item_nextcheck(item->itemid, item->type, item->delay, item->delay_flex, now),
					value->dbl,
					(int)now,
					item->itemid);
				break;
			case AR_UINT64:
				DBexecute("update items set nextcheck=%d,prevvalue=lastvalue,lastvalue='" ZBX_FS_UI64 "',lastclock=%d where itemid=" ZBX_FS_UI64,
					calculate_item_nextcheck(item->itemid, item->type, item->delay, item->delay_flex, now),
					value->ui64,
					(int)now,
					item->itemid);
				break;
			case AR_STRING:
				DBescape_string(value_str,value_esc,MAX_STRING_LEN);
				DBexecute("update items set nextcheck=%d,prevvalue=lastvalue,lastvalue='%s',lastclock=%d where itemid=" ZBX_FS_UI64,
					calculate_item_nextcheck(item->itemid, item->type, item->delay, item->delay_flex, now),
					value_esc,
					(int)now,
					item->itemid);
				break;
			default:
				break;
		}
	}
	/* Logic for delta as speed of change */
	else if(item->delta == ITEM_STORE_SPEED_PER_SECOND)
	{
		if((value->type & AR_DOUBLE) && (item->value_type == ITEM_VALUE_TYPE_FLOAT))
		{
			if((item->prevorgvalue_null == 0) && (item->prevorgvalue_dbl <= value->dbl) )
			{
				DBexecute("update items set nextcheck=%d,prevvalue=lastvalue,prevorgvalue='%f',lastvalue='%f',lastclock=%d where itemid=" ZBX_FS_UI64,
					calculate_item_nextcheck(item->itemid, item->type, item->delay,item->delay_flex,now),
					value->dbl,
					(value->dbl - item->prevorgvalue_dbl)/(now-item->lastclock),
					(int)now,
					item->itemid);
			}
			else
			{
				DBexecute("update items set nextcheck=%d,prevorgvalue='%f',lastclock=%d where itemid=" ZBX_FS_UI64,
					calculate_item_nextcheck(item->itemid, item->type, item->delay,item->delay_flex,now),
					value->dbl,
					(int)now,
					item->itemid);
			}
		}
		else if((value->type & AR_DOUBLE) && (item->value_type == ITEM_VALUE_TYPE_UINT64))
		{
			if((item->prevorgvalue_null == 0) && ((double)item->prevorgvalue_uint64 <= value->dbl) )
			{
				DBexecute("update items set nextcheck=%d,prevvalue=lastvalue,prevorgvalue='%f',lastvalue='%f',lastclock=%d where itemid=" ZBX_FS_UI64,
					calculate_item_nextcheck(item->itemid, item->type, item->delay,item->delay_flex,now),
					value->dbl,
					(value->dbl - (double)(item->prevorgvalue_uint64))/(now-item->lastclock),
					(int)now,
					item->itemid);
			}
			else
			{
				DBexecute("update items set nextcheck=%d,prevorgvalue='%f',lastclock=%d where itemid=" ZBX_FS_UI64,
					calculate_item_nextcheck(item->itemid, item->type, item->delay,item->delay_flex,now),
					value->dbl,
					(int)now,
					item->itemid);
			}
		}
		else if((value->type & AR_UINT64) && (item->value_type == ITEM_VALUE_TYPE_UINT64))
		{
			if((item->prevorgvalue_null == 0) && (item->prevorgvalue_uint64 <= value->ui64) )
			{
				DBexecute("update items set nextcheck=%d,prevvalue=lastvalue,prevorgvalue='" ZBX_FS_UI64 "',lastvalue='%f',lastclock=%d where itemid=" ZBX_FS_UI64,
					calculate_item_nextcheck(item->itemid, item->type, item->delay,item->delay_flex,now),
					value->ui64,
					((double)(value->ui64 - item->prevorgvalue_uint64))/(now-item->lastclock),
					(int)now,
					item->itemid);
			}
			else
			{
				DBexecute("update items set nextcheck=%d,prevorgvalue='" ZBX_FS_UI64 "',lastclock=%d where itemid=" ZBX_FS_UI64,
					calculate_item_nextcheck(item->itemid, item->type, item->delay,item->delay_flex,now),
					value->ui64,
					(int)now,
					item->itemid);
			}
		}
	}
	/* Real delta: simple difference between values */
	else if(item->delta == ITEM_STORE_SIMPLE_CHANGE)
	{
		if((value->type & AR_DOUBLE) && (item->value_type == ITEM_VALUE_TYPE_FLOAT))
		{
			if((item->prevorgvalue_null == 0) && (item->prevorgvalue_dbl <= value->dbl))
			{
				DBexecute("update items set nextcheck=%d,prevvalue=lastvalue,prevorgvalue='%f',lastvalue='%f',lastclock=%d where itemid=" ZBX_FS_UI64,
					calculate_item_nextcheck(item->itemid, item->type, item->delay,item->delay_flex,now),
					value->dbl,
					(value->dbl - item->prevorgvalue_dbl),
					(int)now,
					item->itemid);
			}
			else
			{
				DBexecute("update items set nextcheck=%d,prevorgvalue='%f',lastclock=%d where itemid=" ZBX_FS_UI64,
					calculate_item_nextcheck(item->itemid, item->type, item->delay,item->delay_flex, now),
					value->dbl,
					(int)now,
					item->itemid);
			}
		}
		else if((value->type & AR_DOUBLE) && (item->value_type == ITEM_VALUE_TYPE_UINT64))
		{
			if((item->prevorgvalue_null == 0) && ((double)item->prevorgvalue_uint64 <= value->dbl))
			{
				DBexecute("update items set nextcheck=%d,prevvalue=lastvalue,prevorgvalue='%f',lastvalue='%f',lastclock=%d where itemid=" ZBX_FS_UI64,
					calculate_item_nextcheck(item->itemid, item->type, item->delay,item->delay_flex,now),
					value->dbl,
					(value->dbl - (double)item->prevorgvalue_uint64),
					(int)now,
					item->itemid);
			}
			else
			{
				DBexecute("update items set nextcheck=%d,prevorgvalue='%f',lastclock=%d where itemid=" ZBX_FS_UI64,
					calculate_item_nextcheck(item->itemid, item->type, item->delay,item->delay_flex, now),
					value->dbl,
					(int)now,
					item->itemid);
			}
		}
		else if((value->type & AR_UINT64) && (item->value_type == ITEM_VALUE_TYPE_UINT64))
		{
			if((item->prevorgvalue_null == 0) && (item->prevorgvalue_uint64 <= value->ui64))
			{
				DBexecute("update items set nextcheck=%d,prevvalue=lastvalue,prevorgvalue='" ZBX_FS_UI64 "',lastvalue='" ZBX_FS_UI64 "',lastclock=%d where itemid=" ZBX_FS_UI64,
					calculate_item_nextcheck(item->itemid, item->type, item->delay,item->delay_flex,now),
					value->ui64,
					(value->ui64 - item->prevorgvalue_uint64),
					(int)now,
					item->itemid);
			}
			else
			{
				DBexecute("update items set nextcheck=%d,prevorgvalue='" ZBX_FS_UI64 "',lastclock=%d where itemid=" ZBX_FS_UI64,
					calculate_item_nextcheck(item->itemid, item->type, item->delay,item->delay_flex, now),
					value->ui64,
					(int)now,
					item->itemid);
			}
		}
		else if((value->type & AR_UINT64) && (item->value_type == ITEM_VALUE_TYPE_FLOAT))
		{
			if((item->prevorgvalue_null == 0) && (item->prevorgvalue_uint64 <= value->ui64))
			{
				DBexecute("update items set nextcheck=%d,prevvalue=lastvalue,prevorgvalue='" ZBX_FS_UI64 "',lastvalue='%f',lastclock=%d where itemid=" ZBX_FS_UI64,
					calculate_item_nextcheck(item->itemid, item->type, item->delay,item->delay_flex,now),
					value->ui64,
					((double)value->ui64 - item->prevorgvalue_uint64),
					(int)now,
					item->itemid);
			}
			else
			{
				DBexecute("update items set nextcheck=%d,prevorgvalue='" ZBX_FS_UI64 "',lastclock=%d where itemid=" ZBX_FS_UI64,
					calculate_item_nextcheck(item->itemid, item->type, item->delay,item->delay_flex, now),
					value->ui64,
					(int)now,
					item->itemid);
			}
		}
	}

	item->prevvalue_str=item->lastvalue_str;
	item->prevvalue_dbl=item->lastvalue_dbl;
	item->prevvalue_uint64=item->lastvalue_uint64;
	item->prevvalue_null=item->lastvalue_null;

	item->lastvalue_uint64=value->ui64;
	item->lastvalue_dbl=value->dbl;
	item->lastvalue_str=value->str;
	item->lastvalue_null=0;

/* Update item status if required */
	if(item->status == ITEM_STATUS_NOTSUPPORTED)
	{
		zabbix_log( LOG_LEVEL_WARNING, "Parameter [%s] became supported by agent on host [%s]", item->key, item->host_name);
		zabbix_syslog("Parameter [%s] became supported by agent on host [%s]", item->key, item->host_name);
		item->status = ITEM_STATUS_ACTIVE;
		DBexecute("update items set status=%d where itemid=" ZBX_FS_UI64,
			ITEM_STATUS_ACTIVE, item->itemid);
	}

	/* Required for nodata() */
	item->lastclock = now;

	return ret;
}

/******************************************************************************
 *                                                                            *
 * Function: process_new_value                                                *
 *                                                                            *
 * Purpose: process new item value                                            *
 *                                                                            *
 * Parameters: item - item data                                               *
 *             value - new value of the item                                  *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments: for trapper poller process                                       *
 *                                                                            *
 ******************************************************************************/
void	process_new_value(DB_ITEM *item, AGENT_RESULT *value)
{
	time_t 	now;
	double	multiplier;
	char	*e;

	zabbix_log( LOG_LEVEL_DEBUG, "In process_new_value()");

	now = time(NULL);

	if(item->multiplier == ITEM_MULTIPLIER_USE)
	{
		if( (item->value_type==ITEM_VALUE_TYPE_FLOAT) && (value->type & AR_DOUBLE))
		{
			multiplier = strtod(item->formula,&e);
			SET_DBL_RESULT(value, value->dbl * multiplier);
		}
		if( (item->value_type==ITEM_VALUE_TYPE_FLOAT) && (value->type & AR_UINT64))
		{
			multiplier = strtod(item->formula,&e);
			UNSET_UI64_RESULT(value);
			SET_DBL_RESULT(value, (double)value->ui64 * multiplier);
		}
		if( (item->value_type==ITEM_VALUE_TYPE_UINT64) && (value->type & AR_UINT64))
		{
			if(is_uint(item->formula) == SUCCEED)
			{
#ifdef HAVE_ATOLL
				SET_UI64_RESULT(value, value->ui64 * (zbx_uint64_t)atoll(item->formula));
#else
				SET_UI64_RESULT(value, value->ui64 * (zbx_uint64_t)atol(item->formula));
#endif
			}
			else
			{
				multiplier = strtod(item->formula,&e);
				SET_UI64_RESULT(value, (zbx_uint64_t)((double)value->ui64 * multiplier));
			}
		}
	}

	add_history(item, value, now);
	update_item(item, value, now);

	update_functions( item );
}
