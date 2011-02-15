/*
** ZABBIX
** Copyright (C) 2000-2011 SIA Zabbix
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

#include "zlog.h"
#include "db.h"
#include "dbcache.h"
#include "sysinfo.h"
#include "daemon.h"
#include "zbxserver.h"

#include "poller.h"

#include "checks_agent.h"
#include "checks_aggregate.h"
#include "checks_external.h"
#include "checks_internal.h"
#include "checks_simple.h"
#include "checks_snmp.h"
#include "checks_ipmi.h"
#include "checks_db.h"
#ifdef HAVE_SSH2
#include "checks_ssh.h"
#endif	/* HAVE_SSH2 */
#include "checks_telnet.h"
#include "checks_jmx.h"
#include "checks_calculated.h"

#define MAX_BUNCH_ITEMS	32

static unsigned char	zbx_process;
static int		poller_type;
static int		poller_num;

static int	is_bunch_poller(int poller_type)
{
	return ZBX_POLLER_TYPE_JAVA == poller_type ? SUCCEED : FAIL;
}

static void	update_key_status(zbx_uint64_t hostid, int host_status, zbx_timespec_t *ts)
{
	const char	*__function_name = "update_key_status";
	DC_ITEM		*items = NULL;
	int		i, num;
	AGENT_RESULT	agent;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() hostid:" ZBX_FS_UI64 " status:%d",
			__function_name, hostid, host_status);

	num = DCconfig_get_items(hostid, SERVER_STATUS_KEY, &items);
	for (i = 0; i < num; i++)
	{
		init_result(&agent);
		SET_UI64_RESULT(&agent, host_status);

		dc_add_history(items[i].itemid, items[i].value_type, items[i].flags, &agent, ts, 0, NULL, 0, 0, 0, 0);

		free_result(&agent);
	}

	zbx_free(items);
}

static void	update_triggers_status_to_unknown(zbx_uint64_t hostid, zbx_timespec_t *ts, char *reason)
{
	const char	*__function_name = "update_triggers_status_to_unknown";
	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	triggerid;
	int		trigger_type, trigger_value, trigger_flags;
	const char	*trigger_error;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() hostid:" ZBX_FS_UI64,
			__function_name, hostid);

	result = DBselect(
			"select distinct t.triggerid,t.type,t.value,t.value_flags,t.error"
			" from hosts h,items i,functions f,triggers t"
			" where h.hostid=i.hostid"
				" and i.itemid=f.itemid"
				" and f.triggerid=t.triggerid"
				" and t.status=%d"
				" and i.status=%d"
				" and not i.key_ like '%s'"
				" and not i.key_ like '%s%%'"
				" and h.hostid=" ZBX_FS_UI64,
			TRIGGER_STATUS_ENABLED,
			ITEM_STATUS_ACTIVE,
			SERVER_STATUS_KEY,
			SERVER_ICMPPING_KEY,
			hostid);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(triggerid, row[0]);
		trigger_type = atoi(row[1]);
		trigger_value = atoi(row[2]);
		trigger_flags = atoi(row[3]);
		trigger_error = row[4];

		DBupdate_trigger_value(triggerid, trigger_type, trigger_value, trigger_flags,
				trigger_error, trigger_value, TRIGGER_VALUE_FLAG_UNKNOWN, ts, reason);
	}
	DBfree_result(result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

static void	activate_host(DC_ITEM *item, zbx_timespec_t *ts)
{
	char		sql[MAX_STRING_LEN], error_msg[MAX_STRING_LEN];
	int		offset = 0, *errors_from, *disable_until;
	unsigned char	*available;
	const char	*fld_errors_from, *fld_available, *fld_disable_until, *fld_error;

	switch (item->type)
	{
		case ITEM_TYPE_ZABBIX:
			errors_from = &item->host.errors_from;
			available = &item->host.available;
			disable_until = &item->host.disable_until;

			fld_errors_from = "errors_from";
			fld_available = "available";
			fld_disable_until = "disable_until";
			fld_error = "error";
			break;
		case ITEM_TYPE_SNMPv1:
		case ITEM_TYPE_SNMPv2c:
		case ITEM_TYPE_SNMPv3:
			errors_from = &item->host.snmp_errors_from;
			available = &item->host.snmp_available;
			disable_until = &item->host.snmp_disable_until;

			fld_errors_from = "snmp_errors_from";
			fld_available = "snmp_available";
			fld_disable_until = "snmp_disable_until";
			fld_error = "snmp_error";
			break;
		case ITEM_TYPE_IPMI:
			errors_from = &item->host.ipmi_errors_from;
			available = &item->host.ipmi_available;
			disable_until = &item->host.ipmi_disable_until;

			fld_errors_from = "ipmi_errors_from";
			fld_available = "ipmi_available";
			fld_disable_until = "ipmi_disable_until";
			fld_error = "ipmi_error";
			break;
		case ITEM_TYPE_JMX:
			errors_from = &item->host.jmx_errors_from;
			available = &item->host.jmx_available;
			disable_until = &item->host.jmx_disable_until;

			fld_errors_from = "jmx_errors_from";
			fld_available = "jmx_available";
			fld_disable_until = "jmx_disable_until";
			fld_error = "jmx_error";
			break;
		default:
			return;
	}

	if (0 == *errors_from && HOST_AVAILABLE_TRUE == *available)
		return;

	if (SUCCEED != DCconfig_activate_host(item))
		return;

	offset += zbx_snprintf(sql + offset, sizeof(sql) - offset, "update hosts set ");

	if (HOST_AVAILABLE_TRUE != *available)
	{
		zbx_snprintf(error_msg, sizeof(error_msg), "Enabling %s host [%s]",
				zbx_host_type_string(item->type), item->host.host);

		zabbix_log(LOG_LEVEL_WARNING, "%s", error_msg);
		zabbix_syslog("%s", error_msg);

		*available = HOST_AVAILABLE_TRUE;
		offset += zbx_snprintf(sql + offset, sizeof(sql) - offset, "%s=%d,",
				fld_available, *available);

		if (available == &item->host.available)
			update_key_status(item->host.hostid, HOST_STATUS_MONITORED, ts);
	}

	*errors_from = 0;
	*disable_until = 0;
	offset += zbx_snprintf(sql + offset, sizeof(sql) - offset,
			"%s=%d,%s=%d,%s='' where hostid=" ZBX_FS_UI64,
			fld_errors_from, *errors_from,
			fld_disable_until, *disable_until,
			fld_error,
			item->host.hostid);

	DBbegin();
	DBexecute("%s", sql);
	DBcommit();
}

static void	deactivate_host(DC_ITEM *item, zbx_timespec_t *ts, const char *error)
{
	char		sql[MAX_STRING_LEN], *error_esc, error_msg[MAX_STRING_LEN];
	int		offset = 0, *errors_from, *disable_until;
	unsigned char	*available;
	const char	*fld_errors_from, *fld_available, *fld_disable_until, *fld_error;

	switch (item->type)
	{
		case ITEM_TYPE_ZABBIX:
			errors_from = &item->host.errors_from;
			available = &item->host.available;
			disable_until = &item->host.disable_until;

			fld_errors_from = "errors_from";
			fld_available = "available";
			fld_disable_until = "disable_until";
			fld_error = "error";
			break;
		case ITEM_TYPE_SNMPv1:
		case ITEM_TYPE_SNMPv2c:
		case ITEM_TYPE_SNMPv3:
			errors_from = &item->host.snmp_errors_from;
			available = &item->host.snmp_available;
			disable_until = &item->host.snmp_disable_until;

			fld_errors_from = "snmp_errors_from";
			fld_available = "snmp_available";
			fld_disable_until = "snmp_disable_until";
			fld_error = "snmp_error";
			break;
		case ITEM_TYPE_IPMI:
			errors_from = &item->host.ipmi_errors_from;
			available = &item->host.ipmi_available;
			disable_until = &item->host.ipmi_disable_until;

			fld_errors_from = "ipmi_errors_from";
			fld_available = "ipmi_available";
			fld_disable_until = "ipmi_disable_until";
			fld_error = "ipmi_error";
			break;
		case ITEM_TYPE_JMX:
			errors_from = &item->host.jmx_errors_from;
			available = &item->host.jmx_available;
			disable_until = &item->host.jmx_disable_until;

			fld_errors_from = "jmx_errors_from";
			fld_available = "jmx_available";
			fld_disable_until = "jmx_disable_until";
			fld_error = "jmx_error";
			break;
		default:
			return;
	}

	if (SUCCEED != DCconfig_deactivate_host(item, ts->sec))
		return;

	*error_msg = '\0';

	offset += zbx_snprintf(sql + offset, sizeof(sql) - offset, "update hosts set ");

	if (0 == *errors_from)
	{
		zbx_snprintf(error_msg, sizeof(error_msg), "%s host [%s]: first network error, wait for %d seconds",
				zbx_host_type_string(item->type), item->host.host, CONFIG_UNREACHABLE_DELAY);

		*errors_from = ts->sec;
		*disable_until = ts->sec + CONFIG_UNREACHABLE_DELAY;
		offset += zbx_snprintf(sql + offset, sizeof(sql) - offset, "%s=%d,",
				fld_errors_from, *errors_from);
	}
	else
	{
		if (ts->sec - *errors_from <= CONFIG_UNREACHABLE_PERIOD)
		{
			zbx_snprintf(error_msg, sizeof(error_msg), "%s host [%s]: another network error, wait for %d seconds",
					zbx_host_type_string(item->type), item->host.host, CONFIG_UNREACHABLE_DELAY);

			*disable_until = ts->sec + CONFIG_UNREACHABLE_DELAY;
		}
		else
		{
			*disable_until = ts->sec + CONFIG_UNAVAILABLE_DELAY;

			if (HOST_AVAILABLE_FALSE != *available)
			{
				zbx_snprintf(error_msg, sizeof(error_msg), "Disabling %s host [%s]",
						zbx_host_type_string(item->type), item->host.host);

				*available = HOST_AVAILABLE_FALSE;

				offset += zbx_snprintf(sql + offset, sizeof(sql) - offset, "%s=%d,",
						fld_available, *available);

				if (available == &item->host.available)
					update_key_status(item->host.hostid, HOST_AVAILABLE_FALSE, ts);

				update_triggers_status_to_unknown(item->host.hostid, ts, "Host is unavailable.");
			}

			error_esc = DBdyn_escape_string_len(error, HOST_ERROR_LEN);
			offset += zbx_snprintf(sql + offset, sizeof(sql) - offset, "%s='%s',",
					fld_error, error_esc);
			zbx_free(error_esc);
		}
	}

	offset += zbx_snprintf(sql + offset, sizeof(sql) - offset, "%s=%d where hostid=" ZBX_FS_UI64,
			fld_disable_until, *disable_until, item->host.hostid);

	DBbegin();
	DBexecute("%s", sql);
	DBcommit();

	if ('\0' != *error_msg)
	{
		zabbix_log(LOG_LEVEL_WARNING, "%s", error_msg);
		zabbix_syslog("%s", error_msg);
	}
}

static int	get_value(DC_ITEM *item, AGENT_RESULT *result)
{
	const char	*__function_name = "get_value";
	int		res = FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() key:'%s'", __function_name, item->key_orig);

	switch (item->type)
	{
		case ITEM_TYPE_ZABBIX:
			alarm(CONFIG_TIMEOUT);
			res = get_value_agent(item, result);
			alarm(0);
			break;
		case ITEM_TYPE_SNMPv1:
		case ITEM_TYPE_SNMPv2c:
		case ITEM_TYPE_SNMPv3:
#ifdef HAVE_SNMP
			alarm(CONFIG_TIMEOUT);
			res = get_value_snmp(item, result);
			alarm(0);
#else
			SET_MSG_RESULT(result, zbx_strdup(NULL, "Support of SNMP parameters was not compiled in"));
			res = NOTSUPPORTED;
#endif
			break;
		case ITEM_TYPE_IPMI:
#ifdef HAVE_OPENIPMI
			res = get_value_ipmi(item, result);
#else
			SET_MSG_RESULT(result, zbx_strdup(NULL, "Support of IPMI parameters was not compiled in"));
			res = NOTSUPPORTED;
#endif
			break;
		case ITEM_TYPE_SIMPLE:
			/* simple checks use their own timeouts */
			res = get_value_simple(item, result);
			break;
		case ITEM_TYPE_INTERNAL:
			res = get_value_internal(item, result);
			break;
		case ITEM_TYPE_DB_MONITOR:
			alarm(CONFIG_TIMEOUT);
			res = get_value_db(item, result);
			alarm(0);
			break;
		case ITEM_TYPE_AGGREGATE:
			res = get_value_aggregate(item, result);
			break;
		case ITEM_TYPE_EXTERNAL:
			/* external checks use their own timeouts */
			res = get_value_external(item, result);
			break;
		case ITEM_TYPE_SSH:
#ifdef HAVE_SSH2
			/* Cannot use "alarming" since it breaks down libssh2 and our process terminates. */
			/* libssh2 has its own default timeout == 60 and it should not hang on under usual circumstances. */
			/* alarm(CONFIG_TIMEOUT); */
			res = get_value_ssh(item, result);
			/* alarm(0); */
#else
			SET_MSG_RESULT(result, zbx_strdup(NULL, "Support of SSH parameters was not compiled in"));
			res = NOTSUPPORTED;
#endif	/* HAVE_SSH2 */
			break;
		case ITEM_TYPE_TELNET:
			alarm(CONFIG_TIMEOUT);
			res = get_value_telnet(item, result);
			alarm(0);
			break;
		case ITEM_TYPE_JMX:
			alarm(CONFIG_TIMEOUT);
			res = get_value_jmx(item, result);
			alarm(0);
			break;
		case ITEM_TYPE_CALCULATED:
			res = get_value_calculated(item, result);
			break;
		default:
			zabbix_log(LOG_LEVEL_WARNING, "Not supported item type:%d", item->type);
			zabbix_syslog("Not supported item type:%d", item->type);
			res = NOTSUPPORTED;
	}

	if (SUCCEED != res && ISSET_MSG(result))
	{
		zabbix_log(LOG_LEVEL_DEBUG, "Item [%s:%s] error: %s", item->host.host, item->key_orig, result->msg);
		zabbix_syslog("Item [%s:%s] error: %s", item->host.host, item->key_orig, result->msg);
	}

	/* remove formatting symbols from the end of the result */
	/* so it could be checked by "is_uint64" and "is_double" functions */
	/* when we try to get "int" or "float" values from "string" result */
	if (ISSET_STR(result))
		zbx_rtrim(result->str, ZBX_WHITESPACE);
	if (ISSET_TEXT(result))
		zbx_rtrim(result->text, ZBX_WHITESPACE);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: get_values                                                       *
 *                                                                            *
 * Purpose: retrieve values of metrics from monitored hosts                   *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value: number of items processed                                    *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int	get_values()
{
	const char	*__function_name = "get_values";
	DC_ITEM		items[MAX_BUNCH_ITEMS];
	AGENT_RESULT	results[MAX_BUNCH_ITEMS];
	int		errcodes[MAX_BUNCH_ITEMS];
	zbx_timespec_t	timespecs[MAX_BUNCH_ITEMS];
	int		i, num, res;
	char		*addr = NULL, *port = NULL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	num = (SUCCEED == is_bunch_poller(poller_type) ? MAX_BUNCH_ITEMS : 1);
	num = DCconfig_get_poller_items(poller_type, items, num);

	if (0 == num)
		goto exit;

	DCinit_nextchecks();

	/* prepare items */

	for (i = 0; i < num; i++)
	{
		init_result(&results[i]);
		errcodes[i] = SUCCEED;

		ZBX_STRDUP(items[i].key, items[i].key_orig);
		substitute_simple_macros(NULL, NULL, &items[i].host, NULL, &items[i].key, MACRO_TYPE_ITEM_KEY, NULL, 0);

		items[i].interface.addr = (items[i].interface.useip ? items[i].interface.ip_orig : items[i].interface.dns_orig);

		if (INTERFACE_TYPE_AGENT != items[i].interface.type || 1 != items[i].interface.main)
		{
			addr = zbx_strdup(NULL, items[i].interface.addr);
			substitute_simple_macros(NULL, NULL, &items[i].host, NULL,
					&addr, MACRO_TYPE_INTERFACE_ADDR, NULL, 0);
			items[i].interface.addr = addr;
		}

		switch (items[i].type)
		{
			case ITEM_TYPE_ZABBIX:
			case ITEM_TYPE_SNMPv1:
			case ITEM_TYPE_SNMPv2c:
			case ITEM_TYPE_SNMPv3:
			case ITEM_TYPE_IPMI:
			case ITEM_TYPE_JMX:
				port = zbx_strdup(NULL, items[i].interface.port_orig);
				substitute_simple_macros(NULL, &items[i].host.hostid, NULL, NULL,
						&port, MACRO_TYPE_INTERFACE_PORT, NULL, 0);
				if (FAIL == is_ushort(port, &items[i].interface.port))
				{
					SET_MSG_RESULT(&results[i], zbx_dsprintf(NULL, "Invalid port number [%s]",
								items[i].interface.port_orig));
					errcodes[i] = NETWORK_ERROR;
					zbx_timespec(&timespecs[i]);
				}
				zbx_free(port);
				break;
		}

		switch (items[i].type)
		{
			case ITEM_TYPE_SNMPv3:
				ZBX_STRDUP(items[i].snmpv3_securityname, items[i].snmpv3_securityname_orig);
				ZBX_STRDUP(items[i].snmpv3_authpassphrase, items[i].snmpv3_authpassphrase_orig);
				ZBX_STRDUP(items[i].snmpv3_privpassphrase, items[i].snmpv3_privpassphrase_orig);

				substitute_simple_macros(NULL, NULL, &items[i].host, NULL,
						&items[i].snmpv3_securityname, MACRO_TYPE_ITEM_FIELD, NULL, 0);
				substitute_simple_macros(NULL, NULL, &items[i].host, NULL,
						&items[i].snmpv3_authpassphrase, MACRO_TYPE_ITEM_FIELD, NULL, 0);
				substitute_simple_macros(NULL, NULL, &items[i].host, NULL,
						&items[i].snmpv3_privpassphrase, MACRO_TYPE_ITEM_FIELD, NULL, 0);
			case ITEM_TYPE_SNMPv1:
			case ITEM_TYPE_SNMPv2c:
				ZBX_STRDUP(items[i].snmp_community, items[i].snmp_community_orig);
				ZBX_STRDUP(items[i].snmp_oid, items[i].snmp_oid_orig);

				substitute_simple_macros(NULL, NULL, &items[i].host, NULL,
						&items[i].snmp_community, MACRO_TYPE_ITEM_FIELD, NULL, 0);
				substitute_simple_macros(NULL, NULL, &items[i].host, NULL,
						&items[i].snmp_oid, MACRO_TYPE_ITEM_FIELD, NULL, 0);
				break;
			case ITEM_TYPE_DB_MONITOR:
				ZBX_STRDUP(items[i].params, items[i].params_orig);
				substitute_simple_macros(NULL, NULL, &items[i].host, NULL,
						&items[i].params, MACRO_TYPE_ITEM_FIELD, NULL, 0);
				break;
			case ITEM_TYPE_SSH:
				ZBX_STRDUP(items[i].username, items[i].username_orig);
				ZBX_STRDUP(items[i].publickey, items[i].publickey_orig);
				ZBX_STRDUP(items[i].privatekey, items[i].privatekey_orig);
				ZBX_STRDUP(items[i].password, items[i].password_orig);
				ZBX_STRDUP(items[i].params, items[i].params_orig);

				substitute_simple_macros(NULL, NULL, &items[i].host, NULL,
						&items[i].username, MACRO_TYPE_ITEM_FIELD, NULL, 0);
				substitute_simple_macros(NULL, NULL, &items[i].host, NULL,
						&items[i].publickey, MACRO_TYPE_ITEM_FIELD, NULL, 0);
				substitute_simple_macros(NULL, NULL, &items[i].host, NULL,
						&items[i].privatekey, MACRO_TYPE_ITEM_FIELD, NULL, 0);
				substitute_simple_macros(NULL, NULL, &items[i].host, NULL,
						&items[i].password, MACRO_TYPE_ITEM_FIELD, NULL, 0);
				substitute_simple_macros(NULL, NULL, &items[i].host, NULL,
						&items[i].params, MACRO_TYPE_ITEM_FIELD, NULL, 0);
				break;
			case ITEM_TYPE_TELNET:
				ZBX_STRDUP(items[i].username, items[i].username_orig);
				ZBX_STRDUP(items[i].password, items[i].password_orig);
				ZBX_STRDUP(items[i].params, items[i].params_orig);

				substitute_simple_macros(NULL, NULL, &items[i].host, NULL,
						&items[i].username, MACRO_TYPE_ITEM_FIELD, NULL, 0);
				substitute_simple_macros(NULL, NULL, &items[i].host, NULL,
						&items[i].password, MACRO_TYPE_ITEM_FIELD, NULL, 0);
				substitute_simple_macros(NULL, NULL, &items[i].host, NULL,
						&items[i].params, MACRO_TYPE_ITEM_FIELD, NULL, 0);
				break;
			case ITEM_TYPE_JMX:
				ZBX_STRDUP(items[i].username, items[i].username_orig);
				ZBX_STRDUP(items[i].password, items[i].password_orig);

				substitute_simple_macros(NULL, NULL, &items[i].host, NULL,
						&items[i].username, MACRO_TYPE_ITEM_FIELD, NULL, 0);
				substitute_simple_macros(NULL, NULL, &items[i].host, NULL,
						&items[i].password, MACRO_TYPE_ITEM_FIELD, NULL, 0);
				break;
		}
	}

	/* retrieve item values */

	if (SUCCEED != is_bunch_poller(poller_type))
	{
		if (SUCCEED == errcodes[0])
		{
			errcodes[0] = get_value(&items[0], &results[0]);
			zbx_timespec(&timespecs[0]);
		}
	}
	else if (ZBX_POLLER_TYPE_JAVA == poller_type)
	{
		alarm(CONFIG_TIMEOUT);
		get_values_jmx(items, results, errcodes, timespecs, num);
		alarm(0);
	}

	/* process item values */

	for (i = 0; i < num; i++)
	{
		switch (errcodes[i])
		{
			case SUCCEED:
			case NOTSUPPORTED:
			case AGENT_ERROR:
				activate_host(&items[i], &timespecs[i]);
				break;
			case NETWORK_ERROR:
			case PROXY_ERROR:
				deactivate_host(&items[i], &timespecs[i], results[i].msg);
				break;
			default:
				zbx_error("Unknown response code returned: %d", errcodes[i]);
				assert(0);
		}

		if (SUCCEED == errcodes[i])
		{
			dc_add_history(items[i].itemid, items[i].value_type, items[i].flags, &results[i],
					&timespecs[i], 0, NULL, 0, 0, 0, 0);

			DCrequeue_reachable_item(items[i].itemid, ITEM_STATUS_ACTIVE, timespecs[i].sec);
		}
		else if (NOTSUPPORTED == errcodes[i] || AGENT_ERROR == errcodes[i])
		{
			if (ITEM_STATUS_NOTSUPPORTED != items[i].status)
			{
				zabbix_log(LOG_LEVEL_WARNING, "Parameter [%s:%s] is not supported, old status [%d]",
						items[i].host.host, items[i].key_orig, items[i].status);
				zabbix_syslog("Parameter [%s:%s] is not supported",
						items[i].host.host, items[i].key_orig);
			}

			DCadd_nextcheck(items[i].itemid, timespecs[i].sec, results[i].msg);
			DCrequeue_reachable_item(items[i].itemid, ITEM_STATUS_NOTSUPPORTED, timespecs[i].sec);
		}
		else if (NETWORK_ERROR == errcodes[i] || PROXY_ERROR == errcodes[i])
		{
			DCrequeue_unreachable_item(items[i].itemid);
		}

		if (items[i].interface.ip_orig != items[i].interface.addr &&
				items[i].interface.dns_orig != items[i].interface.addr)
			zbx_free(items[i].interface.addr);

		switch (items[i].type)
		{
			case ITEM_TYPE_SNMPv3:
				zbx_free(items[i].snmpv3_securityname);
				zbx_free(items[i].snmpv3_authpassphrase);
				zbx_free(items[i].snmpv3_privpassphrase);
			case ITEM_TYPE_SNMPv1:
			case ITEM_TYPE_SNMPv2c:
				zbx_free(items[i].snmp_community);
				zbx_free(items[i].snmp_oid);
				break;
			case ITEM_TYPE_DB_MONITOR:
				zbx_free(items[i].params);
				break;
			case ITEM_TYPE_SSH:
				zbx_free(items[i].username);
				zbx_free(items[i].publickey);
				zbx_free(items[i].privatekey);
				zbx_free(items[i].password);
				zbx_free(items[i].params);
				break;
			case ITEM_TYPE_TELNET:
				zbx_free(items[i].username);
				zbx_free(items[i].password);
				zbx_free(items[i].params);
				break;
			case ITEM_TYPE_JMX:
				zbx_free(items[i].username);
				zbx_free(items[i].password);
				break;
		}

		free_result(&results[i]);
	}

	DCflush_nextchecks();
exit:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%d", __function_name, num);

	return num;
}

void	main_poller_loop(unsigned char p, int type, int num)
{
	struct sigaction	phan;
	int			nextcheck, sleeptime, processed;
	double			sec;

	zabbix_log(LOG_LEVEL_DEBUG, "In main_poller_loop() poller_type:%d poller_num:%d", type, num);

	phan.sa_sigaction = child_signal_handler;
	sigemptyset(&phan.sa_mask);
	phan.sa_flags = SA_SIGINFO;
	sigaction(SIGALRM, &phan, NULL);

	zbx_process = p;
	poller_type = type;
	poller_num = num - 1;

	DBconnect(ZBX_DB_CONNECT_NORMAL);

	for (;;)
	{
		zbx_setproctitle("%s [getting values]", zbx_poller_type_string(poller_type));

		sec = zbx_time();
		processed = get_values();
		sec = zbx_time() - sec;

		if (FAIL == (nextcheck = DCconfig_get_poller_nextcheck(poller_type)))
			sleeptime = POLLER_DELAY;
		else
		{
			sleeptime = nextcheck - time(NULL);
			if (sleeptime < 0)
				sleeptime = 0;
			if (sleeptime > POLLER_DELAY)
				sleeptime = POLLER_DELAY;
		}

		zabbix_log(LOG_LEVEL_DEBUG, "%s #%d spent " ZBX_FS_DBL " seconds while updating %d values."
				" Sleeping for %d seconds",
				zbx_poller_type_string(poller_type), poller_num, sec, processed, sleeptime);

		if (sleeptime > 0)
		{
			zbx_setproctitle("%s [sleeping for %d seconds]",
					zbx_poller_type_string(poller_type), sleeptime);
			sleep(sleeptime);
		}
	}
}
