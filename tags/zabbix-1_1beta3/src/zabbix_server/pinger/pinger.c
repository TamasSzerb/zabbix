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

#include "config.h"

#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <sys/socket.h>
#include <netinet/in.h>

#include <sys/wait.h>

#include <string.h>

#ifdef HAVE_NETDB_H
	#include <netdb.h>
#endif

/* Required for getpwuid */
#include <pwd.h>

#include <signal.h>
#include <errno.h>

#include <time.h>

#include "common.h"
#include "cfg.h"
#include "db.h"
#include "../functions.h"
#include "log.h"
#include "zlog.h"

#include "pinger.h"

/******************************************************************************
 *                                                                            *
 * Function: is_ip                                                            *
 *                                                                            *
 * Purpose: is string IP address                                              *
 *                                                                            *
 * Parameters: ip - string                                                    *
 *                                                                            *
 * Return value: SUCCEED - is IP address                                      * 
 *               FAIL - otherwise                                             *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments: could be improved                                                *
 *                                                                            *
 ******************************************************************************/
static int is_ip(char *ip)
{
	int i;
	char c;
	int dots=0;
	int res = SUCCEED;

	zabbix_log( LOG_LEVEL_DEBUG, "In process_ip([%s])", ip);

	for(i=0;ip[i]!=0;i++)
	{
		c=ip[i];
		if( (c>='0') && (c<='9'))
		{
			continue;
		}
		else if(c=='.')
		{
			dots++;
		}
		else
		{
			res = FAIL;
			break;
		}
	}
	if( dots!=3)
	{
		res = FAIL;
	}
	zabbix_log( LOG_LEVEL_DEBUG, "End of process_ip([%d])", res);
	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: process_new_value                                                *
 *                                                                            *
 * Purpose: process new item value                                            *
 *                                                                            *
 * Parameters: key - item key                                                 *
 *             host - host name                                               *
 *             value - new value of the item                                  *
 *                                                                            *
 * Return value: SUCCEED - new value sucesfully processed                     *
 *               FAIL - otherwise                                             *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments: can be done in process_data()                                    *
 *                                                                            *
 ******************************************************************************/
static int process_value(char *key, char *host, AGENT_RESULT *value)
{
	char	sql[MAX_STRING_LEN];

	DB_RESULT       *result;
	DB_ITEM	item;
	char	*s;

	zabbix_log( LOG_LEVEL_DEBUG, "In process_value()");

	/* IP address? */
	if(is_ip(host) == SUCCEED)
	{
		snprintf(sql,sizeof(sql)-1,"select i.itemid,i.key_,h.host,h.port,i.delay,i.description,i.nextcheck,i.type,i.snmp_community,i.snmp_oid,h.useip,h.ip,i.history,i.lastvalue,i.prevvalue,i.value_type,i.trapper_hosts,i.delta,i.units,i.multiplier,i.formula from items i,hosts h where h.status=%d and h.hostid=i.hostid and h.ip='%s' and i.key_='%s' and i.status=%d and i.type=%d", HOST_STATUS_MONITORED, host, key, ITEM_STATUS_ACTIVE, ITEM_TYPE_SIMPLE);
	}
	else
	{
		snprintf(sql,sizeof(sql)-1,"select i.itemid,i.key_,h.host,h.port,i.delay,i.description,i.nextcheck,i.type,i.snmp_community,i.snmp_oid,h.useip,h.ip,i.history,i.lastvalue,i.prevvalue,i.value_type,i.trapper_hosts,i.delta,i.units,i.multiplier,i.formula from items i,hosts h where h.status=%d and h.hostid=i.hostid and h.host='%s' and i.key_='%s' and i.status=%d and i.type=%d", HOST_STATUS_MONITORED, host, key, ITEM_STATUS_ACTIVE, ITEM_TYPE_SIMPLE);
	}
	zabbix_log( LOG_LEVEL_DEBUG, "SQL [%s]", sql);
	result = DBselect(sql);

	if(DBnum_rows(result) == 0)
	{
		DBfree_result(result);
		return  FAIL;
	}

	item.itemid=atoi(DBget_field(result,0,0));
	strscpy(item.key,DBget_field(result,0,1));
	item.host=DBget_field(result,0,2);
	item.port=atoi(DBget_field(result,0,3));
	item.delay=atoi(DBget_field(result,0,4));
	item.description=DBget_field(result,0,5);
	item.nextcheck=atoi(DBget_field(result,0,6));
	item.type=atoi(DBget_field(result,0,7));
	item.snmp_community=DBget_field(result,0,8);
	item.snmp_oid=DBget_field(result,0,9);
	item.useip=atoi(DBget_field(result,0,10));
	item.ip=DBget_field(result,0,11);
	item.history=atoi(DBget_field(result,0,12));
	s=DBget_field(result,0,13);
	if(s==NULL)
	{
		item.lastvalue_null=1;
	}
	else
	{
		item.lastvalue_null=0;
		item.lastvalue_str=s;
		item.lastvalue=atof(s);
	}
	s=DBget_field(result,0,14);
	if(s==NULL)
	{
		item.prevvalue_null=1;
	}
	else
	{
		item.prevvalue_null=0;
		item.prevvalue_str=s;
		item.prevvalue=atof(s);
	}
	item.value_type=atoi(DBget_field(result,0,15));
	item.trapper_hosts=DBget_field(result,0,16);
	item.delta=atoi(DBget_field(result,0,17));

	item.units=DBget_field(result,0,18);
	item.multiplier=atoi(DBget_field(result,0,19));
	item.formula=DBget_field(result,0,20);

	process_new_value(&item,value);
	update_triggers(item.itemid);
 
	DBfree_result(result);

	return SUCCEED;
}

/******************************************************************************
 *                                                                            *
 * Function: create_host_file                                                 *
 *                                                                            *
 * Purpose: creates file which contains list of hosts to ping                 *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value: SUCCEED - the file was created succesfully                   *
 *               FAIL - otherwise                                             *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int create_host_file(void)
{
	char	sql[MAX_STRING_LEN];
	FILE	*f;
	int	i,now;

	DB_HOST	host;
	DB_RESULT	*result;

	zabbix_log( LOG_LEVEL_DEBUG, "In create_host_file()");

	f = fopen("/tmp/zabbix_suckerd.pinger", "w");

	if( f == NULL)
	{
		zabbix_log( LOG_LEVEL_ERR, "Cannot open file [%s] [%s]", "/tmp/zabbix_suckerd.pinger", strerror(errno));
		zabbix_syslog("Cannot open file [%s] [%s]", "/tmp/zabbix_suckerd.pinger", strerror(errno));
		return FAIL;
	}

	now=time(NULL);
	/* Select hosts monitored by IP */
	snprintf(sql,sizeof(sql)-1,"select distinct h.ip from hosts h,items i where i.hostid=h.hostid and (h.status=%d or (h.status=%d and h.available=%d and h.disable_until<=%d)) and (i.key_='%s' or i.key_='%s') and i.type=%d and i.status=%d and h.useip=1", HOST_STATUS_MONITORED, HOST_STATUS_MONITORED, HOST_AVAILABLE_FALSE, now, SERVER_ICMPPING_KEY, SERVER_ICMPPINGSEC_KEY, ITEM_TYPE_SIMPLE, ITEM_STATUS_ACTIVE);
	result = DBselect(sql);

	for(i=0;i<DBnum_rows(result);i++)
	{
		strscpy(host.ip,DBget_field(result,i,0));
/*		host.host=DBget_field(result,i,2);*/

		fprintf(f,"%s\n",host.ip);

		zabbix_log( LOG_LEVEL_DEBUG, "IP [%s]", host.ip);
	}
	DBfree_result(result);

	/* Select hosts monitored by hostname */
	snprintf(sql,sizeof(sql)-1,"select distinct h.host from hosts h,items i where i.hostid=h.hostid and (h.status=%d or (h.status=%d and h.available=%d and h.disable_until<=%d)) and (i.key_='%s' or i.key_='%s') and i.type=%d and i.status=%d and h.useip=0", HOST_STATUS_MONITORED, HOST_STATUS_MONITORED, HOST_AVAILABLE_FALSE, now, SERVER_ICMPPING_KEY, SERVER_ICMPPINGSEC_KEY, ITEM_TYPE_SIMPLE, ITEM_STATUS_ACTIVE);
	result = DBselect(sql);

	for(i=0;i<DBnum_rows(result);i++)
	{
		strscpy(host.host,DBget_field(result,i,0));

		fprintf(f,"%s\n",host.host);

		zabbix_log( LOG_LEVEL_DEBUG, "HOSTNAME [%s]", host.host);
	}
	DBfree_result(result);

	fclose(f);

	return SUCCEED;
}


/******************************************************************************
 *                                                                            *
 * Function: do_ping                                                          *
 *                                                                            *
 * Purpose: ping hosts listed in the host files                               *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value: SUCCEED - successfully processed                             *
 *               FAIL - otherwise                                             *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments: use external binary 'fping' to avoid superuser priviledges       *
 *                                                                            *
 ******************************************************************************/
static int do_ping(void)
{
	FILE	*f;
	char	ip[MAX_STRING_LEN];
	char	str[MAX_STRING_LEN];
	char	tmp[MAX_STRING_LEN];
	double	mseconds;
	char	*c;
	int	alive;
	AGENT_RESULT	value;

	zabbix_log( LOG_LEVEL_DEBUG, "In do_ping()");

	snprintf(str,sizeof(str)-1,"cat /tmp/zabbix_suckerd.pinger | %s -e 2>/dev/null",CONFIG_FPING_LOCATION);
	
	f=popen(str,"r");
	if(f==0)
	{
		zabbix_log( LOG_LEVEL_ERR, "Cannot execute [%s] [%s]", CONFIG_FPING_LOCATION, strerror(errno));
		zabbix_syslog("Cannot execute [%s] [%s]", CONFIG_FPING_LOCATION, strerror(errno));
		return FAIL;
	}

	while(NULL!=fgets(ip,MAX_STRING_LEN,f))
	{
/*		zabbix_log( LOG_LEVEL_WARNING, "PING: [%s]", ip);*/

		ip[strlen(ip)-1]=0;
		zabbix_log( LOG_LEVEL_DEBUG, "Update IP [%s]", ip);

		if(strstr(ip,"alive") != NULL)
		{
			alive=1;
			sscanf(ip,"%s is alive (%lf ms)", tmp, &mseconds);
			zabbix_log( LOG_LEVEL_DEBUG, "Mseconds [%lf]", mseconds);
		}
		else
		{
			alive=0;
		}
		c=strstr(ip," ");
		if(c != NULL)
		{
			*c=0;
			zabbix_log( LOG_LEVEL_DEBUG, "IP [%s] alive [%d]", ip, alive);

			if(0 == alive)
			{
				init_result(&value);
				process_value(SERVER_ICMPPING_KEY,ip,&value);
				free_result(&value);
				
				init_result(&value);
				process_value(SERVER_ICMPPINGSEC_KEY,ip,&value);
				free_result(&value);
			}
			else
			{
				init_result(&value);
				process_value(SERVER_ICMPPING_KEY,ip,&value);
				free_result(&value);
				
				init_result(&value);
				SET_DBL_RESULT(&value, mseconds/1000);
				process_value(SERVER_ICMPPINGSEC_KEY,ip,&value);
				free_result(&value);
			}
		}
	}

	pclose(f);


	return	SUCCEED;
}

/******************************************************************************
 *                                                                            *
 * Function: main_pinger_loop                                                 *
 *                                                                            *
 * Purpose: periodically perform ICMP pings                                   *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments: never returns                                                    *
 *                                                                            *
 ******************************************************************************/
void main_pinger_loop()
{
	int ret = SUCCEED;

	if(1 == CONFIG_DISABLE_PINGER)
	{
		for(;;)
		{
			pause();
		}
	}
	else
	{
		for(;;)
		{
#ifdef HAVE_FUNCTION_SETPROCTITLE
			setproctitle("connecting to the database");
#endif

			DBconnect();
	
/*	zabbix_set_log_level(LOG_LEVEL_DEBUG);*/

			ret = create_host_file();
	
			if( SUCCEED == ret)
			{
#ifdef HAVE_FUNCTION_SETPROCTITLE
				setproctitle("pinging hosts");
#endif

				ret = do_ping();
			}
			unlink("/tmp/zabbix_suckerd.pinger");
	
/*	zabbix_set_log_level(LOG_LEVEL_WARNING); */

			DBclose();

#ifdef HAVE_FUNCTION_SETPROCTITLE
			setproctitle("pinger [sleeping for %d seconds]", CONFIG_PINGER_FREQUENCY);
#endif
			sleep(CONFIG_PINGER_FREQUENCY);
		}
	}
	
	/* Never reached */
}
