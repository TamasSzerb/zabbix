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
#include "events.h"

/******************************************************************************
 *                                                                            *
 * Functions: discovery_add_event                                             *
 *                                                                            *
 * Purpose: generate UP/DOWN event if required                                *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static void	discovery_add_event(int object, zbx_uint64_t objectid, int now, int value)
{
	DB_EVENT	event;

	memset(&event, 0, sizeof(DB_EVENT));

	event.source	= EVENT_SOURCE_DISCOVERY;
	event.object	= object;
	event.objectid	= objectid;
	event.clock 	= now;
	event.value 	= value;

	process_event(&event, 1);
}

static DB_RESULT	discovery_get_dhost_by_value(zbx_uint64_t dcheckid, const char *value)
{
	DB_RESULT	result;
	char		*value_esc;

	value_esc = DBdyn_escape_string_len(value, DSERVICE_VALUE_LEN);

	result = DBselect(
			"select dh.dhostid,dh.status,dh.lastup,dh.lastdown"
			" from dhosts dh,dservices ds"
			" where ds.dhostid=dh.dhostid"
				" and ds.dcheckid=" ZBX_FS_UI64
				" and ds.value" ZBX_SQL_STRCMP
			" order by dh.dhostid",
			dcheckid,
			ZBX_SQL_STRVAL_EQ(value_esc));

	zbx_free(value_esc);

	return result;
}

static DB_RESULT	discovery_get_dhost_by_ip(zbx_uint64_t druleid, const char *ip)
{
	DB_RESULT	result;
	char		*ip_esc;

	ip_esc = DBdyn_escape_string_len(ip, INTERFACE_IP_LEN);

	result = DBselect(
			"select dh.dhostid,dh.status,dh.lastup,dh.lastdown"
			" from dhosts dh,dservices ds"
			" where ds.dhostid=dh.dhostid"
				" and dh.druleid=" ZBX_FS_UI64
				" and ds.ip" ZBX_SQL_STRCMP
			" order by dh.dhostid",
			druleid,
			ZBX_SQL_STRVAL_EQ(ip_esc));

	zbx_free(ip_esc);

	return result;
}

/******************************************************************************
 *                                                                            *
 * Function: discovery_separate_host                                          *
 *                                                                            *
 * Purpose: separate multiple-IP hosts                                        *
 *                                                                            *
 * Parameters: host ip address                                                *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static void	discovery_separate_host(DB_DRULE *drule, DB_DHOST *dhost, const char *ip)
{
	const char	*__function_name = "discovery_separate_host";

	DB_RESULT	result;
	DB_ROW		row;
	char		*ip_esc, *sql = NULL;
	zbx_uint64_t	dhostid;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() ip:'%s'", __function_name, ip);

	ip_esc = DBdyn_escape_string_len(ip, INTERFACE_IP_LEN);
	sql = zbx_dsprintf(sql,
			"select dserviceid"
			" from dservices"
			" where dhostid=" ZBX_FS_UI64
				" and ip" ZBX_SQL_STRCMP,
			dhost->dhostid,
			ZBX_SQL_STRVAL_NE(ip_esc));

	result = DBselectN(sql, 1);

	if (NULL != (row = DBfetch(result)))
	{
		dhostid = DBget_maxid("dhosts");

		DBexecute("insert into dhosts (dhostid,druleid)"
				" values (" ZBX_FS_UI64 "," ZBX_FS_UI64 ")",
				dhostid,
				drule->druleid);

		DBexecute("update dservices"
				" set dhostid=" ZBX_FS_UI64
				" where dhostid=" ZBX_FS_UI64
					" and ip" ZBX_SQL_STRCMP,
				dhostid,
				dhost->dhostid,
				ZBX_SQL_STRVAL_EQ(ip_esc));

		dhost->dhostid	= dhostid;
		dhost->status	= DOBJECT_STATUS_DOWN;
		dhost->lastup	= 0;
		dhost->lastdown	= 0;
	}
	DBfree_result(result);

	zbx_free(sql);
	zbx_free(ip_esc);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

/******************************************************************************
 *                                                                            *
 * Function: discovery_register_host                                          *
 *                                                                            *
 * Purpose: register host if one does not exist                               *
 *                                                                            *
 * Parameters: host ip address                                                *
 *                                                                            *
 * Return value: dhostid or 0 if we didn't add host                           *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static void	discovery_register_host(DB_DRULE *drule, DB_DCHECK *dcheck, DB_DHOST *dhost,
		const char *ip, int status, const char *value)
{
	const char	*__function_name = "discovery_register_host";

	DB_RESULT	result;
	DB_ROW		row;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() ip:'%s' status:%d value:'%s'",
			__function_name, ip, status, value);

	if (drule->unique_dcheckid == dcheck->dcheckid)
	{
		result = discovery_get_dhost_by_value(dcheck->dcheckid, value);

		if (NULL == (row = DBfetch(result)))
		{
			DBfree_result(result);

			result = discovery_get_dhost_by_ip(drule->druleid, ip);
			row = DBfetch(result);
		}
	}
	else
	{
		result = discovery_get_dhost_by_ip(drule->druleid, ip);
		row = DBfetch(result);
	}

	if (NULL == row)
	{
		/* Add host only if service is up */
		if (status == DOBJECT_STATUS_UP)
		{
			zabbix_log(LOG_LEVEL_DEBUG, "New host discovered at %s",
					ip);

			dhost->dhostid	= DBget_maxid("dhosts");
			dhost->status	= DOBJECT_STATUS_DOWN;
			dhost->lastup	= 0;
			dhost->lastdown	= 0;

			DBexecute("insert into dhosts (dhostid,druleid)"
					" values (" ZBX_FS_UI64 "," ZBX_FS_UI64 ")",
					dhost->dhostid,
					drule->druleid);
		}
	}
	else
	{
		zabbix_log(LOG_LEVEL_DEBUG, "Host at %s is already in database",
				ip);

		ZBX_STR2UINT64(dhost->dhostid, row[0]);
		dhost->status	= atoi(row[1]);
		dhost->lastup	= atoi(row[2]);
		dhost->lastdown	= atoi(row[3]);

		if (0 == drule->unique_dcheckid)
			discovery_separate_host(drule, dhost, ip);
	}
	DBfree_result(result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

/******************************************************************************
 *                                                                            *
 * Function: discovery_register_service                                       *
 *                                                                            *
 * Purpose: register service if one does not exist                            *
 *                                                                            *
 * Parameters: host ip address                                                *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static void	discovery_register_service(DB_DRULE *drule, DB_DCHECK *dcheck,
		DB_DHOST *dhost, DB_DSERVICE *dservice, const char *ip, const char *dns,
		int port, int status, int now)
{
	const char	*__function_name = "discovery_register_service";

	DB_RESULT	result;
	DB_ROW		row;
	char		*key_esc, *ip_esc, *dns_esc;

	zbx_uint64_t	dhostid;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() ip:'%s' port:%d key:'%s'",
			__function_name, ip, port, dcheck->key_);

	key_esc = DBdyn_escape_string_len(dcheck->key_, DSERVICE_KEY_LEN);
	ip_esc = DBdyn_escape_string_len(ip, INTERFACE_IP_LEN);

	result = DBselect(
			"select dserviceid,dhostid,status,lastup,lastdown,value,dns"
			" from dservices"
			" where dcheckid=" ZBX_FS_UI64
				" and type=%d"
				" and key_" ZBX_SQL_STRCMP
				" and ip" ZBX_SQL_STRCMP
				" and port=%d",
			dcheck->dcheckid,
			dcheck->type,
			ZBX_SQL_STRVAL_EQ(key_esc),
			ZBX_SQL_STRVAL_EQ(ip_esc),
			port);

	if (NULL == (row = DBfetch(result)))
	{
		/* Add host only if service is up */
		if (status == DOBJECT_STATUS_UP)
		{
			zabbix_log(LOG_LEVEL_DEBUG, "New service discovered on port %d", port);

			dservice->dserviceid = DBget_maxid("dservices");
			dservice->status = DOBJECT_STATUS_DOWN;

			dns_esc = DBdyn_escape_string_len(dns, INTERFACE_DNS_LEN);

			DBexecute("insert into dservices (dserviceid,dhostid,dcheckid,type,key_,ip,dns,port,status)"
					" values (" ZBX_FS_UI64 "," ZBX_FS_UI64 "," ZBX_FS_UI64 ",%d,'%s','%s','%s',%d,%d)",
					dservice->dserviceid,
					dhost->dhostid,
					dcheck->dcheckid,
					dcheck->type,
					key_esc,
					ip_esc,
					dns_esc,
					port,
					dservice->status);

			zbx_free(dns_esc);
		}
	}
	else
	{
		zabbix_log(LOG_LEVEL_DEBUG, "Service is already in database");

		ZBX_STR2UINT64(dservice->dserviceid, row[0]);
		ZBX_STR2UINT64(dhostid, row[1]);
		dservice->status = atoi(row[2]);
		dservice->lastup = atoi(row[3]);
		dservice->lastdown = atoi(row[4]);
		strscpy(dservice->value, row[5]);

		if (dhostid != dhost->dhostid)
		{
			DBexecute("update dservices"
					" set dhostid=" ZBX_FS_UI64
					" where dhostid=" ZBX_FS_UI64,
					dhost->dhostid,
					dhostid);
			DBexecute("delete from dhosts"
					" where dhostid=" ZBX_FS_UI64,
					dhostid);
		}

		if (0 != strcmp(row[6], dns))
		{
			dns_esc = DBdyn_escape_string_len(dns, INTERFACE_DNS_LEN);

			DBexecute("update dservices"
					" set dns='%s'"
					" where dserviceid=" ZBX_FS_UI64,
					dns_esc, dservice->dserviceid);

			zbx_free(dns_esc);
		}
	}
	DBfree_result(result);

	zbx_free(ip_esc);
	zbx_free(key_esc);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

/******************************************************************************
 *                                                                            *
 * Function: discovery_update_dservice                                        *
 *                                                                            *
 * Purpose: update discovered service details                                 *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static void	discovery_update_dservice(DB_DSERVICE *service)
{
	char	*value_esc;

	value_esc = DBdyn_escape_string_len(service->value, DSERVICE_VALUE_LEN);

	DBexecute("update dservices set status=%d,lastup=%d,lastdown=%d,value='%s' where dserviceid=" ZBX_FS_UI64,
			service->status,
			service->lastup,
			service->lastdown,
			value_esc,
			service->dserviceid);

	zbx_free(value_esc);
}

/******************************************************************************
 *                                                                            *
 * Function: discovery_update_dservice_value                                  *
 *                                                                            *
 * Purpose: update discovered service details                                 *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static void	discovery_update_dservice_value(DB_DSERVICE *service)
{
	char	*value_esc;

	value_esc = DBdyn_escape_string_len(service->value, DSERVICE_VALUE_LEN);

	DBexecute("update dservices set value='%s' where dserviceid=" ZBX_FS_UI64,
			value_esc,
			service->dserviceid);

	zbx_free(value_esc);
}

/******************************************************************************
 *                                                                            *
 * Function: discovery_update_service_status                                  *
 *                                                                            *
 * Purpose: process new service status                                        *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static void	discovery_update_service_status(DB_DSERVICE *dservice, int status, const char *value, int now)
{
	/* Update service status */
	if (status == DOBJECT_STATUS_UP)
	{
		if (dservice->status == DOBJECT_STATUS_DOWN || dservice->lastup == 0)
		{
			dservice->status	= status;
			dservice->lastdown	= 0;
			dservice->lastup	= now;

			strcpy(dservice->value, value);
			discovery_update_dservice(dservice);
			discovery_add_event(EVENT_OBJECT_DSERVICE, dservice->dserviceid, now, DOBJECT_STATUS_DISCOVER);
		}
		else if (0 != strcmp(dservice->value, value))
		{
			strcpy(dservice->value, value);
			discovery_update_dservice_value(dservice);
		}
	}
	else	/* DOBJECT_STATUS_DOWN */
	{
		if (dservice->status == DOBJECT_STATUS_UP || dservice->lastdown == 0)
		{
			dservice->status	= status;
			dservice->lastdown	= now;
			dservice->lastup	= 0;

			discovery_update_dservice(dservice);
			discovery_add_event(EVENT_OBJECT_DSERVICE, dservice->dserviceid, now, DOBJECT_STATUS_LOST);
		}
	}
	discovery_add_event(EVENT_OBJECT_DSERVICE, dservice->dserviceid, now, status);
}

/******************************************************************************
 *                                                                            *
 * Function: discovery_update_dhost                                           *
 *                                                                            *
 * Purpose: update discovered host details                                    *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static void	discovery_update_dhost(DB_DHOST *dhost)
{
	DBexecute("update dhosts set status=%d,lastup=%d,lastdown=%d where dhostid=" ZBX_FS_UI64,
			dhost->status,
			dhost->lastup,
			dhost->lastdown,
			dhost->dhostid);
}

/******************************************************************************
 *                                                                            *
 * Function: discovery_update_host_status                                     *
 *                                                                            *
 * Purpose: update new host status                                            *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static void	discovery_update_host_status(DB_DHOST *dhost, int status, int now)
{
	/* Update host status */
	if (status == DOBJECT_STATUS_UP)
	{
		if (dhost->status == DOBJECT_STATUS_DOWN || dhost->lastup == 0)
		{
			dhost->status	= status;
			dhost->lastdown	= 0;
			dhost->lastup	= now;

			discovery_update_dhost(dhost);
			discovery_add_event(EVENT_OBJECT_DHOST, dhost->dhostid, now, DOBJECT_STATUS_DISCOVER);
		}
	}
	else	/* DOBJECT_STATUS_DOWN */
	{
		if (dhost->status == DOBJECT_STATUS_UP || dhost->lastdown == 0)
		{
			dhost->status	= status;
			dhost->lastdown	= now;
			dhost->lastup	= 0;

			discovery_update_dhost(dhost);
			discovery_add_event(EVENT_OBJECT_DHOST, dhost->dhostid, now, DOBJECT_STATUS_LOST);
		}
	}
	discovery_add_event(EVENT_OBJECT_DHOST, dhost->dhostid, now, status);
}

/******************************************************************************
 *                                                                            *
 * Function: discovery_update_host                                            *
 *                                                                            *
 * Purpose: process new host status                                           *
 *                                                                            *
 * Parameters: host - host info                                               *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
void	discovery_update_host(DB_DHOST *dhost, const char *ip, int status, int now)
{
	const char	*__function_name = "discovery_update_host";

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	if (0 != dhost->dhostid)
		discovery_update_host_status(dhost, status, now);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

/******************************************************************************
 *                                                                            *
 * Function: discovery_update_service                                         *
 *                                                                            *
 * Purpose: process new service status                                        *
 *                                                                            *
 * Parameters: service - service info                                         *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
void	discovery_update_service(DB_DRULE *drule, DB_DCHECK *dcheck, DB_DHOST *dhost,
		const char *ip, const char *dns, int port, int status, const char *value, int now)
{
	const char	*__function_name = "discovery_update_service";

	DB_DSERVICE	dservice;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() ip:'%s' port:%d status:%d",
			__function_name, ip, port, status);

	memset(&dservice, 0, sizeof(dservice));

	/* Register host if is not registered yet */
	if (0 == dhost->dhostid)
		discovery_register_host(drule, dcheck, dhost, ip, status, value);

	/* Register service if is not registered yet */
	if (0 != dhost->dhostid)
		discovery_register_service(drule, dcheck, dhost, &dservice, ip, dns, port, status, now);

	/* Service wasn't registered because we do not add down service */
	if (0 != dservice.dserviceid)
		discovery_update_service_status(&dservice, status, value, now);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}
