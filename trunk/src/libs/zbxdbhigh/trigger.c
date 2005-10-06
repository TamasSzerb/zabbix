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


#include <stdlib.h>
#include <stdio.h>

#include <string.h>
#include <strings.h>

#include "db.h"
#include "log.h"
#include "zlog.h"
#include "common.h"

int	DBadd_trigger_to_linked_hosts(int triggerid,int hostid)
{
	DB_TRIGGER	trigger;
	DB_RESULT	*result,*result2,*result3;
	char	sql[MAX_STRING_LEN];
	char	old[MAX_STRING_LEN];
	char	new[MAX_STRING_LEN];
	int	ret = SUCCEED;
	int	i,j;
	int	functionid, triggerid_new;
	char	expression_old[TRIGGER_EXPRESSION_LEN_MAX];
	char	*expression;
	char	comments_esc[TRIGGER_COMMENTS_LEN_MAX];
	char	url_esc[TRIGGER_URL_LEN_MAX];
	char	description_esc[TRIGGER_DESCRIPTION_LEN_MAX];

	zabbix_log( LOG_LEVEL_WARNING, "In DBadd_trigger_to_linked_hosts(%d,%d)",triggerid, hostid);

	snprintf(sql,sizeof(sql)-1,"select description, priority,status,comments,url,value,expression,prevvalue from triggers where triggerid=%d", triggerid);
	result2=DBselect(sql);
	if(DBnum_rows(result2)==0)
	{
		DBfree_result(result2);
		return FAIL;
	}

	trigger.triggerid = triggerid;
	strscpy(trigger.description, DBget_field(result2,0,0));
	trigger.priority=atoi(DBget_field(result2,0,1));
	trigger.status=atoi(DBget_field(result2,0,2));
	strscpy(trigger.comments, DBget_field(result2,0,3));
	strscpy(trigger.url, DBget_field(result2,0,4));
	trigger.value=atoi(DBget_field(result2,0,5));
	strscpy(trigger.expression, DBget_field(result2,0,6));
	trigger.prevvalue=atoi(DBget_field(result2,0,7));

	DBfree_result(result2);

	snprintf(sql,sizeof(sql)-1,"select distinct h.hostid from hosts h,functions f, items i where i.itemid=f.itemid and h.hostid=i.hostid and f.triggerid=%d", triggerid);
	result=DBselect(sql);

	if(DBnum_rows(result)!=1)
	{
		return FAIL;
	}

	if(hostid==0)
	{
		snprintf(sql,sizeof(sql)-1,"select hostid,templateid,triggers from hosts_templates where templateid=%d", atoi(DBget_field(result,0,0)));
	}
	/* Link to one host only */
	else
	{
		snprintf(sql,sizeof(sql)-1,"select hostid,templateid,triggers from hosts_templates where hostid=%d and templateid=%d", hostid, atoi(DBget_field(result,0,0)));
	}
	DBfree_result(result);

	result=DBselect(sql);

	/* Loop: linked hosts */
	for(i=0;i<DBnum_rows(result);i++)
	{
		strscpy(expression_old, trigger.expression);

		if(atoi(DBget_field(result,i,2))&1 == 0)	continue;

		DBescape_string(trigger.description,description_esc,TRIGGER_DESCRIPTION_LEN_MAX);
		DBescape_string(trigger.comments,description_esc,TRIGGER_COMMENTS_LEN_MAX);
		DBescape_string(trigger.url,url_esc,TRIGGER_URL_LEN_MAX);

		snprintf(sql,sizeof(sql)-1,"insert into triggers  (description,priority,status,comments,url,value,expression) values ('%s',%d,%d,'%s','%s',2,'%s')",description_esc, trigger.priority, trigger.status, comments_esc, url_esc, expression_old);

		DBexecute(sql);
		triggerid_new=DBinsert_id();

		snprintf(sql,sizeof(sql)-1,"select i.key_,f.parameter,f.function,f.functionid from functions f,items i where i.itemid=f.itemid and f.triggerid=%d", triggerid);
		result2=DBselect(sql);
		// Loop: functions
		for(j=0;j<DBnum_rows(result2);j++)
		{
			snprintf(sql,sizeof(sql)-1,"select itemid from items where key_='%s' and hostid=%d", DBget_field(result2,j,0), atoi(DBget_field(result,i,0)));
			result3=DBselect(sql);
			if(DBnum_rows(result3)!=1)
			{
				snprintf(sql,sizeof(sql)-1,"delete from triggers where triggerid=%d", triggerid_new);
				DBexecute(sql);
				snprintf(sql,sizeof(sql)-1,"delete from functions where triggerid=%d", triggerid_new);
				DBexecute(sql);
				break;
			}

			snprintf(sql,sizeof(sql)-1,"insert into functions (itemid,triggerid,function,parameter) values (%d,%d,'%s','%s')", atoi(DBget_field(result3,0,0)), triggerid_new, DBget_field(result2,j,1), DBget_field(result2,j,2));

			DBexecute(sql);
			functionid=DBinsert_id();

			snprintf(sql,sizeof(sql)-1,"update triggers set expression='%s' where triggerid=%d", expression_old, triggerid_new );
			DBexecute(sql);

			snprintf(old, sizeof(old)-1,"{%d}", atoi(DBget_field(result2,j,3)));
			snprintf(new, sizeof(new)-1,"{%d}", functionid);

			/* Possible memory leak here as expression can be malloced */
			expression=string_replace(expression_old, old, new);

			strscpy(expression_old, expression);

			snprintf(sql,sizeof(sql)-1,"update triggers set expression='%s' where triggerid=%d", expression, triggerid_new );
			DBexecute(sql);
		}
		DBfree_result(result2);
	}
	DBfree_result(result);

	return SUCCEED;
}


/*-----------------------------------------------------------------------------
 *
 * Function   : DBget_trigger_by_triggerid 
 *
 * Purpose    : get trigger data from DBby triggerid
 *
 * Parameters : triggerid - ID of the trigger
 *
 * Returns    : SUCCEED - trigger data retrieved sucesfully
 *              FAIL - otherwise
 *
 * Author     : Alexei Vladishev
 *
 * Comments   :
 *
 ----------------------------------------------------------------------------*/
int	DBget_trigger_by_triggerid(int triggerid,DB_TRIGGER *trigger)
{
	DB_RESULT	*result;
	char	sql[MAX_STRING_LEN];
	int	ret = SUCCEED;

	zabbix_log( LOG_LEVEL_WARNING, "In DBget_trigger_by_triggerid(%d)", triggerid);

	snprintf(sql,sizeof(sql)-1,"select triggerid, expression,description,url,comments,status,value,prevvalue,priority from triggers where triggerid=%d", triggerid);
	result=DBselect(sql);

	if(DBnum_rows(result)==0)
	{
		ret = FAIL;
	}
	else
	{
		trigger->triggerid=atoi(DBget_field(result,0,0));
		strscpy(trigger->expression,DBget_field(result,0,1));
		strscpy(trigger->description,DBget_field(result,0,2));
		strscpy(trigger->url,DBget_field(result,0,3));
		strscpy(trigger->comments,DBget_field(result,0,4));
		trigger->status=atoi(DBget_field(result,0,5));
		trigger->value=atoi(DBget_field(result,0,6));
		trigger->prevvalue=atoi(DBget_field(result,0,7));
		trigger->priority=atoi(DBget_field(result,0,8));
	}

	DBfree_result(result);

	return ret;
}
