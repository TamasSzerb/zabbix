/* 
** Zabbix
** Copyright (C) 2000,2001,2002,2003,2004 Alexei Vladishev
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
#include <string.h>
#include <stdarg.h>
#include <syslog.h>

#include <sys/types.h>
#include <sys/stat.h>
#include <unistd.h>

#include <time.h>

#include "common.h"
#include "functions.h"
#include "log.h"
#include "zlog.h"

/* Update special host's item - "zabbix[log]" */
void zabbix_syslog(const char *fmt, ...)
{
	int		i;
	va_list		ap;
	char		sql[MAX_STRING_LEN];
	char		value_str[MAX_STRING_LEN];

	DB_ITEM		item;
	DB_RESULT	*result;

	zabbix_log(LOG_LEVEL_DEBUG, "In zabbix_log()");

	snprintf(sql,sizeof(sql)-1,"select i.itemid,i.key_,h.host,h.port,i.delay,i.description,i.nextcheck,i.type,i.snmp_community,i.snmp_oid,h.useip,h.ip,i.history,i.lastvalue,i.prevvalue,i.hostid,h.status,i.value_type,h.network_errors,i.snmp_port,i.delta,i.prevorgvalue,i.lastclock,i.units,i.multiplier,i.snmpv3_securityname,i.snmpv3_securitylevel,i.snmpv3_authpassphrase,i.snmpv3_privpassphrase,i.formula,h.available from items i,hosts h where h.hostid=i.hostid and i.key_='%s' and i.value_type=%d", SERVER_ZABBIXLOG_KEY,ITEM_VALUE_TYPE_STR);
	result = DBselect(sql);

	if( DBnum_rows(result) == 0)
	{
		zabbix_log( LOG_LEVEL_DEBUG, "No zabbix[log] to update.");
	}
	else
	{
		for(i=0;i<DBnum_rows(result);i++)
		{
			DBget_item_from_db(&item,result, i);
	
			va_start(ap,fmt);
			vsprintf(value_str,fmt,ap);
			value_str[MAX_STRING_LEN]=0;
			va_end(ap);

			process_new_value(&item,value_str);
			update_triggers(item.itemid);
		}
	}

	DBfree_result(result);
}
