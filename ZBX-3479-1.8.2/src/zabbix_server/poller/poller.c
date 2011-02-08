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

#include "zlog.h"
#include "db.h"
#include "sysinfo.h"
#include "daemon.h"
#include "zbxserver.h"
#include "dbcache.h"

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
#include "checks_calculated.h"

#define MAX_ITEMS	64

AGENT_RESULT    result;

static zbx_process_t	zbx_process;
int			poller_type;
int			poller_num;

static int	get_value(DC_ITEM *item, AGENT_RESULT *result)
{
	const char	*__function_name = "get_value";
	int		res = FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() key:'%s'", __function_name, item->key_orig);

	switch (item->type) {
		case ITEM_TYPE_ZABBIX:
			alarm(CONFIG_TIMEOUT);
			res = get_value_agent(item, result);
			alarm(0);

			if (SUCCEED != res && GET_MSG_RESULT(result))
			{
				zabbix_log(LOG_LEVEL_WARNING, "Item [%s:%s] error: %s",
						item->host.host, item->key_orig, result->msg);
				zabbix_syslog("Item [%s:%s] error: %s",
						item->host.host, item->key_orig, result->msg);
			}
			break;
		case ITEM_TYPE_SNMPv1:
		case ITEM_TYPE_SNMPv2c:
		case ITEM_TYPE_SNMPv3:
#ifdef HAVE_SNMP
			alarm(CONFIG_TIMEOUT);
			res = get_value_snmp(item, result);
			alarm(0);
#else
			SET_MSG_RESULT(result, strdup("Support of SNMP parameters was not compiled in"));
			res = NOTSUPPORTED;
#endif
			if (SUCCEED != res && GET_MSG_RESULT(result))
			{
				zabbix_log(LOG_LEVEL_WARNING, "Item [%s:%s] error: %s",
						item->host.host, item->key_orig, result->msg);
				zabbix_syslog("Item [%s:%s] error: %s",
						item->host.host, item->key_orig, result->msg);
			}
			break;
		case ITEM_TYPE_IPMI:
#ifdef HAVE_OPENIPMI
			res = get_value_ipmi(item, result);
#else
			SET_MSG_RESULT(result, strdup("Support of IPMI parameters was not compiled in"));
			res = NOTSUPPORTED;
#endif
			break;
		case ITEM_TYPE_SIMPLE:
			/* simple checks use their own timeouts */
			res = get_value_simple(item, result);
			break;
		case ITEM_TYPE_INTERNAL:
			alarm(CONFIG_TIMEOUT);
			res = get_value_internal(item, result);
			alarm(0);
			break;
		case ITEM_TYPE_DB_MONITOR:
			alarm(CONFIG_TIMEOUT);
			res = get_value_db(item, result);
			alarm(0);

			if (SUCCEED != res && GET_MSG_RESULT(result))
			{
				zabbix_log(LOG_LEVEL_WARNING, "Item [%s:%s] error: %s",
						item->host.host, item->key_orig, result->msg);
				zabbix_syslog("Item [%s:%s] error: %s",
						item->host.host, item->key_orig, result->msg);
			}
			break;
		case ITEM_TYPE_AGGREGATE:
			res = get_value_aggregate(item, result);

			if (SUCCEED != res && GET_MSG_RESULT(result))
			{
				zabbix_log(LOG_LEVEL_WARNING, "Item [%s:%s] error: %s",
						item->host.host, item->key_orig, result->msg);
				zabbix_syslog("Item [%s:%s] error: %s",
						item->host.host, item->key_orig, result->msg);
			}
			break;
		case ITEM_TYPE_EXTERNAL:
			/* external checks use their own timeouts */
			res = get_value_external(item, result);

			if (SUCCEED != res && GET_MSG_RESULT(result))
			{
				zabbix_log(LOG_LEVEL_WARNING, "Item [%s:%s] error: %s",
						item->host.host, item->key_orig, result->msg);
				zabbix_syslog("Item [%s:%s] error: %s",
						item->host.host, item->key_orig, result->msg);
			}
			break;
		case ITEM_TYPE_SSH:
#ifdef HAVE_SSH2
			/* Cannot use "alarming" since it breaks down libssh2 and our process terminates. */
			/* libssh2 has its own default timeout == 60 and it should not hang on under usual circumstances. */
			/* alarm(CONFIG_TIMEOUT); */
			res = get_value_ssh(item, result);
			/* alarm(0); */
#else
			SET_MSG_RESULT(result, strdup("Support of SSH parameters was not compiled in"));
			res = NOTSUPPORTED;
#endif	/* HAVE_SSH2 */

			if (SUCCEED != res && GET_MSG_RESULT(result))
			{
				zabbix_log(LOG_LEVEL_WARNING, "Item [%s:%s] error: %s",
						item->host.host, item->key_orig, result->msg);
				zabbix_syslog("Item [%s:%s] error: %s",
						item->host.host, item->key_orig, result->msg);
			}
			break;
		case ITEM_TYPE_TELNET:
			alarm(CONFIG_TIMEOUT);
			res = get_value_telnet(item, result);
			alarm(0);

			if (SUCCEED != res && GET_MSG_RESULT(result))
			{
				zabbix_log(LOG_LEVEL_WARNING, "Item [%s:%s] error: %s",
						item->host.host, item->key_orig, result->msg);
				zabbix_syslog("Item [%s:%s] error: %s",
						item->host.host, item->key_orig, result->msg);
			}
			break;
		case ITEM_TYPE_CALCULATED:
			res = get_value_calculated(item, result);

			if (SUCCEED != res && GET_MSG_RESULT(result))
			{
				zabbix_log(LOG_LEVEL_WARNING, "Item [%s:%s] error: %s",
						item->host.host, item->key_orig, result->msg);
				zabbix_syslog("Item [%s:%s] error: %s",
						item->host.host, item->key_orig, result->msg);
			}
			break;
		default:
			zabbix_log(LOG_LEVEL_WARNING, "Not supported item type:%d",
					item->type);
			zabbix_syslog("Not supported item type:%d",
					item->type);
			res = NOTSUPPORTED;
	}

	/* remove formatting symbols from the end of the result */
	/* so it could be checked by "is_uint64" and "is_double" functions */
	/* when we try to get "int" or "float" values from "string" result */
	if (ISSET_STR(result))
	{
		zbx_rtrim(result->str, " \r\n");
	}
	if (ISSET_TEXT(result))
	{
		zbx_rtrim(result->text, " \r\n");
	}

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

/* Update special host's item - "status" */
static void	update_key_status(zbx_uint64_t hostid, int host_status, time_t now)
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

		dc_add_history(items[i].itemid, items[i].value_type, &agent, now, 0, NULL, 0, 0, 0, 0);

		free_result(&agent);
	}

	zbx_free(items);
}

static void	activate_host(DC_ITEM *item, int now)
{
	char		sql[MAX_STRING_LEN], error_msg[MAX_STRING_LEN];
	int		offset = 0, *errors_from, *disable_until;
	unsigned char	*available;
	const char	*fld_errors_from, *fld_available, *fld_disable_until,
			*fld_error, *type;

	switch (item->type) {
	case ITEM_TYPE_ZABBIX:
		errors_from = &item->host.errors_from;
		available = &item->host.available;
		disable_until = &item->host.disable_until;

		fld_errors_from = "errors_from";
		fld_available = "available";
		fld_disable_until = "disable_until";
		fld_error = "error";
		type = "ZABBIX";
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
		type = "SNMP";
		break;
	case ITEM_TYPE_IPMI:
		errors_from = &item->host.ipmi_errors_from;
		available = &item->host.ipmi_available;
		disable_until = &item->host.ipmi_disable_until;

		fld_errors_from = "ipmi_errors_from";
		fld_available = "ipmi_available";
		fld_disable_until = "ipmi_disable_until";
		fld_error = "ipmi_error";
		type = "IPMI";
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
				type, item->host.host);

		zabbix_log(LOG_LEVEL_WARNING, "%s", error_msg);
		zabbix_syslog("%s", error_msg);

		*available = HOST_AVAILABLE_TRUE;
		offset += zbx_snprintf(sql + offset, sizeof(sql) - offset, "%s=%d,",
				fld_available, *available);

		if (available == &item->host.available)
			update_key_status(item->host.hostid, HOST_STATUS_MONITORED, now); /* 0 */
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

static void	update_triggers_status_to_unknown(zbx_uint64_t hostid, int now, char *reason)
{
	const char	*__function_name = "update_triggers_status_to_unknown";
	DB_RESULT	result;
	DB_ROW		row;
	DB_TRIGGER	trigger;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	result = DBselect(
			"select distinct t.triggerid,t.expression,t.description,t.status,t.priority,"
				"t.value,t.url,t.comments"
			" from hosts h,items i,triggers t,functions f"
			" where f.triggerid=t.triggerid"
				" and f.itemid=i.itemid"
				" and h.hostid=i.hostid"
				" and h.hostid=" ZBX_FS_UI64
				" and not i.key_ like '%s'"
				" and not i.key_ like '%s%%'",
			hostid, SERVER_STATUS_KEY, SERVER_ICMPPING_KEY);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(trigger.triggerid, row[0]);
		strscpy(trigger.expression, row[1]);
		strscpy(trigger.description, row[2]);
		trigger.status		= atoi(row[3]);
		trigger.priority	= atoi(row[4]);
		trigger.value		= atoi(row[5]);
		trigger.url		= row[6];
		trigger.comments	= row[7];
		DBupdate_trigger_value(&trigger, TRIGGER_VALUE_UNKNOWN, now, reason);
	}
	DBfree_result(result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);

	return;
}

static void	deactivate_host(DC_ITEM *item, int now, const char *error)
{
	char		sql[MAX_STRING_LEN], *error_esc, error_msg[MAX_STRING_LEN];
	int		offset = 0, *errors_from, *disable_until;
	unsigned char	*available;
	const char	*fld_errors_from, *fld_available, *fld_disable_until,
			*fld_error, *type;

	switch (item->type) {
	case ITEM_TYPE_ZABBIX:
		errors_from = &item->host.errors_from;
		available = &item->host.available;
		disable_until = &item->host.disable_until;

		fld_errors_from = "errors_from";
		fld_available = "available";
		fld_disable_until = "disable_until";
		fld_error = "error";
		type = "ZABBIX";
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
		type = "SNMP";
		break;
	case ITEM_TYPE_IPMI:
		errors_from = &item->host.ipmi_errors_from;
		available = &item->host.ipmi_available;
		disable_until = &item->host.ipmi_disable_until;

		fld_errors_from = "ipmi_errors_from";
		fld_available = "ipmi_available";
		fld_disable_until = "ipmi_disable_until";
		fld_error = "ipmi_error";
		type = "IPMI";
		break;
	default:
		return;
	}

	if (SUCCEED != DCconfig_deactivate_host(item, now))
		return;

	*error_msg = '\0';

	offset += zbx_snprintf(sql + offset, sizeof(sql) - offset, "update hosts set ");

	/* First error */
	if (0 == *errors_from)
	{
		zbx_snprintf(error_msg, sizeof(error_msg), "%s Host [%s]: first network error, wait for %d seconds",
				type, item->host.host, CONFIG_UNREACHABLE_DELAY);

		*errors_from = now;
		*disable_until = now + CONFIG_UNREACHABLE_DELAY;
		offset += zbx_snprintf(sql + offset, sizeof(sql) - offset, "%s=%d,",
				fld_errors_from, *errors_from);
	}
	else
	{
		if (now - *errors_from <= CONFIG_UNREACHABLE_PERIOD)
		{
			/* Still unavailable, but won't change status to UNAVAILABLE yet */
			zbx_snprintf(error_msg, sizeof(error_msg), "%s Host [%s]: another network error, wait for %d seconds",
					type, item->host.host, CONFIG_UNREACHABLE_DELAY);

			*disable_until = now + CONFIG_UNREACHABLE_DELAY;
		}
		else
		{
			*disable_until = now + CONFIG_UNAVAILABLE_DELAY;

			if (HOST_AVAILABLE_FALSE != *available)
			{
				zbx_snprintf(error_msg, sizeof(error_msg), "Disabling %s host [%s]",
						type, item->host.host);

				*available = HOST_AVAILABLE_FALSE;

				offset += zbx_snprintf(sql + offset, sizeof(sql) - offset, "%s=%d,",
						fld_available, *available);

				if (available == &item->host.available)
					update_key_status(item->host.hostid, HOST_AVAILABLE_FALSE, now); /* 2 */

				update_triggers_status_to_unknown(item->host.hostid, now, "Host is unavailable.");
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

/******************************************************************************
 *                                                                            *
 * Function: get_values                                                       *
 *                                                                            *
 * Purpose: retrieve values of metrics from monitored hosts                   *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments: always SUCCEED                                                   *
 *                                                                            *
 ******************************************************************************/
static int	get_values(int now)
{
	const char	*__function_name = "get_values";
	DC_ITEM		items[MAX_ITEMS];
	AGENT_RESULT	agent;
	zbx_uint64_t	*ids = NULL, *snmpids = NULL, *ipmiids = NULL;
	int		ids_alloc = 0, snmpids_alloc = 0, ipmiids_alloc = 0,
			ids_num = 0, snmpids_num = 0, ipmiids_num = 0,
			i, num, res;
	static char	*key = NULL, *ipmi_ip = NULL, *params = NULL,
			*username = NULL, *publickey = NULL, *privatekey = NULL,
			*password = NULL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	DCinit_nextchecks();

	num = DCconfig_get_poller_items(poller_type, poller_num, now, items, MAX_ITEMS);

	for (i = 0; i < num; i++)
	{
		zbx_free(key);
		key = strdup(items[i].key_orig);
		substitute_simple_macros(NULL, NULL, NULL, NULL, &items[i], NULL,
				&key, MACRO_TYPE_ITEM_KEY, NULL, 0);
		items[i].key = key;

		switch (items[i].type) {
		case ITEM_TYPE_IPMI:
			zbx_free(ipmi_ip);
			ipmi_ip = strdup(items[i].host.ipmi_ip_orig);
			substitute_simple_macros(NULL, NULL, NULL, NULL, &items[i], NULL,
					&ipmi_ip, MACRO_TYPE_HOST_IPMI_IP, NULL, 0);
			items[i].host.ipmi_ip = ipmi_ip;
			break;
		case ITEM_TYPE_DB_MONITOR:
			items[i].params = items[i].params_orig;
			break;
		case ITEM_TYPE_SSH:
			zbx_free(username);
			zbx_free(publickey);
			zbx_free(privatekey);
			zbx_free(password);
			zbx_free(params);

			username = strdup(items[i].username_orig);
			publickey = strdup(items[i].publickey_orig);
			privatekey = strdup(items[i].privatekey_orig);
			password = strdup(items[i].password_orig);
			params = strdup(items[i].params_orig);

			substitute_simple_macros(NULL, NULL, NULL, NULL, &items[i], NULL,
					&username, MACRO_TYPE_ITEM_USERNAME, NULL, 0);
			substitute_simple_macros(NULL, NULL, NULL, NULL, &items[i], NULL,
					&publickey, MACRO_TYPE_ITEM_PUBLICKEY, NULL, 0);
			substitute_simple_macros(NULL, NULL, NULL, NULL, &items[i], NULL,
					&privatekey, MACRO_TYPE_ITEM_PRIVATEKEY, NULL, 0);
			substitute_simple_macros(NULL, NULL, NULL, NULL, &items[i], NULL,
					&password, MACRO_TYPE_ITEM_PASSWORD, NULL, 0);
			substitute_simple_macros(NULL, NULL, NULL, NULL, &items[i], NULL,
					&params, MACRO_TYPE_ITEM_SCRIPT, NULL, 0);

			items[i].username = username;
			items[i].publickey = publickey;
			items[i].privatekey = privatekey;
			items[i].password = password;
			items[i].params = params;
			break;
		case ITEM_TYPE_TELNET:
			zbx_free(username);
			zbx_free(password);
			zbx_free(params);

			username = strdup(items[i].username_orig);
			password = strdup(items[i].password_orig);
			params = strdup(items[i].params_orig);

			substitute_simple_macros(NULL, NULL, NULL, NULL, &items[i], NULL,
					&username, MACRO_TYPE_ITEM_USERNAME, NULL, 0);
			substitute_simple_macros(NULL, NULL, NULL, NULL, &items[i], NULL,
					&password, MACRO_TYPE_ITEM_PASSWORD, NULL, 0);
			substitute_simple_macros(NULL, NULL, NULL, NULL, &items[i], NULL,
					&params, MACRO_TYPE_ITEM_SCRIPT, NULL, 0);

			items[i].username = username;
			items[i].password = password;
			items[i].params = params;
			break;
		}

		/* Skip unreachable hosts but do not break the loop. */
		switch (items[i].type) {
		case ITEM_TYPE_ZABBIX:
			if (SUCCEED == uint64_array_exists(ids, ids_num, items[i].host.hostid))
			{
				zabbix_log(LOG_LEVEL_DEBUG, "Zabbix Host " ZBX_FS_UI64 " is unreachable. Skipping [%s]",
						items[i].host.hostid, items[i].key_orig);
				continue;
			}
			break;
		case ITEM_TYPE_SNMPv1:
		case ITEM_TYPE_SNMPv2c:
		case ITEM_TYPE_SNMPv3:
			if (SUCCEED == uint64_array_exists(snmpids, snmpids_num, items[i].host.hostid))
			{
				zabbix_log(LOG_LEVEL_DEBUG, "SNMP Host " ZBX_FS_UI64 " is unreachable. Skipping [%s]",
						items[i].host.hostid, items[i].key_orig);
				continue;
			}
			break;
		case ITEM_TYPE_IPMI:
			if (SUCCEED == uint64_array_exists(ipmiids, ipmiids_num, items[i].host.hostid))
			{
				zabbix_log(LOG_LEVEL_DEBUG, "IPMI Host " ZBX_FS_UI64 " is unreachable. Skipping [%s]",
						items[i].host.hostid, items[i].key_orig);
				continue;
			}
			break;
		default:
			/* nothink to do */;
		}

		init_result(&agent);

		res = get_value(&items[i], &agent);
		now = time(NULL);

		if (res == SUCCEED)
		{
			activate_host(&items[i], now);

			dc_add_history(items[i].itemid, items[i].value_type, &agent, now, 0, NULL, 0, 0, 0, 0);

			DCconfig_update_item(items[i].itemid, ITEM_STATUS_ACTIVE, now);
		}
		else if (res == NOTSUPPORTED || res == AGENT_ERROR)
		{
			if (ITEM_STATUS_NOTSUPPORTED != items[i].status)
			{
				zabbix_log(LOG_LEVEL_WARNING, "Parameter [%s:%s] is not supported by agent"
						" Old status [%d]",
						items[i].host.host, items[i].key_orig, items[i].status);
				zabbix_syslog("Parameter [%s:%s] is not supported by agent",
						items[i].host.host, items[i].key_orig);
			}

			activate_host(&items[i], now);

			DCadd_nextcheck(&items[i], now, agent.msg);	/* update error & status field in items table */
			DCconfig_update_item(items[i].itemid, ITEM_STATUS_NOTSUPPORTED, now);
		}
		else if (res == NETWORK_ERROR)
		{
			deactivate_host(&items[i], now, agent.msg);

			switch (items[i].type) {
			case ITEM_TYPE_ZABBIX:
				uint64_array_add(&ids, &ids_alloc, &ids_num, items[i].host.hostid, 1);
				break;
			case ITEM_TYPE_SNMPv1:
			case ITEM_TYPE_SNMPv2c:
			case ITEM_TYPE_SNMPv3:
				uint64_array_add(&snmpids, &snmpids_alloc, &snmpids_num, items[i].host.hostid, 1);
				break;
			case ITEM_TYPE_IPMI:
				uint64_array_add(&ipmiids, &ipmiids_alloc, &ipmiids_num, items[i].host.hostid, 1);
				break;
			default:
				/* nothink to do */;
			}
		}
		else
		{
			zbx_error("Unknown response code returned.");
			assert(0 == 1);
		}

		free_result(&agent);
	}

	zbx_free(key);
	zbx_free(ipmi_ip);
	zbx_free(params);
	zbx_free(username);
	zbx_free(publickey);
	zbx_free(privatekey);
	zbx_free(password);

	zbx_free(ids);
	zbx_free(snmpids);
	zbx_free(ipmiids);

	DCflush_nextchecks();

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);

	return num;
}

void main_poller_loop(zbx_process_t p, int type, int num)
{
	struct	sigaction phan;
	int	now, nextcheck, sleeptime, processed;
	double	sec;

	zabbix_log(LOG_LEVEL_DEBUG, "In main_poller_loop() poller_type:%d poller_num:%d", type, num);

	phan.sa_sigaction = child_signal_handler;
	sigemptyset(&phan.sa_mask);
	phan.sa_flags = SA_SIGINFO;
	sigaction(SIGALRM, &phan, NULL);

	zbx_process	= p;
	poller_type	= type;
	poller_num	= num - 1;

	DBconnect(ZBX_DB_CONNECT_NORMAL);

	for (;;) {
		zbx_setproctitle("poller [getting values]");

		now = time(NULL);
		sec = zbx_time();
		processed = get_values(now);
		sec = zbx_time() - sec;

		if (FAIL == (nextcheck = DCconfig_get_poller_nextcheck(poller_type, poller_num, now)))
			sleeptime = POLLER_DELAY;
		else
		{
			sleeptime = nextcheck - time(NULL);
			if (sleeptime < 0)
				sleeptime = 0;
			if (sleeptime > POLLER_DELAY)
				sleeptime = POLLER_DELAY;
		}

		zabbix_log(LOG_LEVEL_DEBUG, "Poller #%d spent " ZBX_FS_DBL " seconds while updating %3d values."
				" Sleeping for %d seconds", poller_num, sec, processed, sleeptime);

		if (sleeptime > 0)
		{
			zbx_setproctitle("poller [sleeping for %d seconds]", sleeptime);
			sleep(sleeptime);
		}
	}
}
