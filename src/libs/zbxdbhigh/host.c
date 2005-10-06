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

int	DBadd_host(char *server, int port, int status, int useip, char *ip, int disable_until, int available)
{
	char	sql[MAX_STRING_LEN];
	int	hostid;

	snprintf(sql, sizeof(sql)-1,"insert into hosts (host,port,status,useip,ip,disable_until,available) values ('%s',%d,%d,%d,'%s',%d,%d)", server, port, status, useip, ip, disable_until, available);
	if(FAIL == DBexecute(sql))
	{
		return FAIL;
	}

	hostid=DBinsert_id();

	if(hostid==0)
	{
		return FAIL;
	}

	return hostid;
}

int	DBhost_exists(char *server)
{
	DB_RESULT	*result;
	char	sql[MAX_STRING_LEN];
	int	ret = SUCCEED;

	snprintf(sql,sizeof(sql)-1,"select hostid from hosts where host='%s'", server);
	result = DBselect(sql);

	if(DBnum_rows(result) == 0)
	{
		ret = FAIL;
	}
	DBfree_result(result);

	return ret;
}

int	DBadd_templates_to_host(int hostid,int host_templateid)
{
	DB_RESULT	*result;
	char	sql[MAX_STRING_LEN];
	int	i;

	zabbix_log( LOG_LEVEL_WARNING, "In DBadd_templates_to_host(%d,%d)", hostid, host_templateid);

	snprintf(sql,sizeof(sql)-1,"select templateid,items,triggers,actions,graphs,screens from hosts_templates where hostid=%d", host_templateid);
	result = DBselect(sql);

	for(i=0;i<DBnum_rows(result);i++)
	{
		DBadd_template_linkage(hostid,atoi(DBget_field(result,i,0)),atoi(DBget_field(result,i,1)),
					atoi(DBget_field(result,i,2)), atoi(DBget_field(result,i,3)),
					atoi(DBget_field(result,i,4)), atoi(DBget_field(result,i,5)));
	}

	DBfree_result(result);

	return SUCCEED;
}

int	DBadd_template_linkage(int hostid,int templateid,int items,int triggers,int actions,int graphs,int screens)
{
	char	sql[MAX_STRING_LEN];

	zabbix_log( LOG_LEVEL_WARNING, "In DBadd_template_linkage(%d)", hostid);

	snprintf(sql,sizeof(sql)-1,"insert into hosts_templates (hostid,templateid,items,triggers,actions,graphs,screens) values (%d,%d,%d,%d,%d,%d,%d)",hostid, templateid, items, triggers, actions, graphs, screens);

	return DBexecute(sql);
}

int	DBsync_host_with_templates(int hostid)
{
	DB_RESULT	*result;
	char	sql[MAX_STRING_LEN];
	int	i;

	zabbix_log( LOG_LEVEL_WARNING, "In DBsync_host_with_templates(%d)", hostid);

	snprintf(sql,sizeof(sql)-1,"select templateid,items,triggers,actions,graphs,screens from hosts_templates where hostid=%d", hostid);
	result = DBselect(sql);

	for(i=0;i<DBnum_rows(result);i++)
	{
		DBsync_host_with_template(hostid,atoi(DBget_field(result,i,0)),atoi(DBget_field(result,i,1)),
					atoi(DBget_field(result,i,2)), atoi(DBget_field(result,i,3)),
					atoi(DBget_field(result,i,4)), atoi(DBget_field(result,i,5)));
	}

	DBfree_result(result);

	return SUCCEED;
}

int	DBsync_host_with_template(int hostid,int templateid,int items,int triggers,int actions,int graphs,int screens)
{
	DB_RESULT	*result;
	char	sql[MAX_STRING_LEN];
	int	i;

	zabbix_log( LOG_LEVEL_WARNING, "In DBsync_host_with_template(%d,%d)", hostid, templateid);

	/* Sync items */
	snprintf(sql,sizeof(sql)-1,"select itemid from items where hostid=%d", templateid);
	result = DBselect(sql);
	for(i=0;i<DBnum_rows(result);i++)
	{
		DBadd_item_to_linked_hosts(atoi(DBget_field(result,i,0)), hostid);
	}
	DBfree_result(result);

	/* Sync triggers */
	snprintf(sql,sizeof(sql)-1,"select distinct t.triggerid from hosts h, items i,triggers t,functions f where h.hostid=%d and h.hostid=i.hostid and t.triggerid=f.triggerid and i.itemid=f.itemid", templateid);
	result = DBselect(sql);
	for(i=0;i<DBnum_rows(result);i++)
	{
		DBadd_trigger_to_linked_hosts(atoi(DBget_field(result,i,0)),hostid);
	}
	DBfree_result(result);

	/* Sync actions */
	snprintf(sql,sizeof(sql)-1,"select distinct a.actionid from actions a,hosts h, items i,triggers t,functions f where h.hostid=%d and h.hostid=i.hostid and t.triggerid=f.triggerid and i.itemid=f.itemid", templateid);
	result = DBselect(sql);
	for(i=0;i<DBnum_rows(result);i++)
	{
		DBadd_action_to_linked_hosts(atoi(DBget_field(result,i,0)),hostid);
	}
	DBfree_result(result);

	/* Sync graphs */
	snprintf(sql,sizeof(sql)-1,"select distinct gi.gitemid from graphs g,graphs_items gi,items i where i.itemid=gi.itemid and i.hostid=%d and g.graphid=gi.graphid", templateid);
	result = DBselect(sql);
	for(i=0;i<DBnum_rows(result);i++)
	{
		DBadd_graph_item_to_linked_hosts(atoi(DBget_field(result,i,0)),hostid);
	}
	DBfree_result(result);

	return SUCCEED;
}

int	DBget_host_by_hostid(int hostid,DB_HOST *host)
{
	DB_RESULT	*result;
	char	sql[MAX_STRING_LEN];
	int	ret = SUCCEED;

	zabbix_log( LOG_LEVEL_WARNING, "In DBget_host_by_hostid(%d)", hostid);

	snprintf(sql,sizeof(sql)-1,"select hostid,host,useip,ip,port,status,disable_until,network_errors,error,available from hosts where hostid=%d", hostid);
	result=DBselect(sql);

	if(DBnum_rows(result)==0)
	{
		ret = FAIL;
	}
	else
	{
		host->hostid=atoi(DBget_field(result,0,0));
		strscpy(host->host,DBget_field(result,0,1));
		host->useip=atoi(DBget_field(result,0,2));
		strscpy(host->ip,DBget_field(result,0,3));
		host->port=atoi(DBget_field(result,0,4));
		host->status=atoi(DBget_field(result,0,5));
		host->disable_until=atoi(DBget_field(result,0,6));
		host->network_errors=atoi(DBget_field(result,0,7));
		strscpy(host->error,DBget_field(result,0,8));
		host->available=atoi(DBget_field(result,0,9));
	}

	DBfree_result(result);

	zabbix_log( LOG_LEVEL_WARNING, "End of DBget_host_by_hostid");

	return ret;
}
