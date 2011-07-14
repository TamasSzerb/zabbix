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
#include "db.h"
#include "log.h"
#include "zlog.h"
#include "sysinfo.h"
#include "zbxalgo.h"
#include "zbxserver.h"

#include "proxy.h"
#include "dbcache.h"
#include "discovery.h"

typedef struct
{
	const char		*field;
	const char		*tag;
	zbx_json_type_t		jt;
	char			*default_value;
}
ZBX_HISTORY_FIELD;

typedef struct
{
	const char		*table, *lastfieldname;
	const char		*from, *where;
	ZBX_HISTORY_FIELD	fields[ZBX_MAX_FIELDS];
}
ZBX_HISTORY_TABLE;

static ZBX_HISTORY_TABLE ht = {
	"proxy_history", "history_lastid", "hosts h,items i,",
	"h.hostid=i.hostid and i.itemid=p.itemid and ",
		{
		{"h.host",	ZBX_PROTO_TAG_HOST,		ZBX_JSON_TYPE_STRING,	NULL},
		{"i.key_",	ZBX_PROTO_TAG_KEY,		ZBX_JSON_TYPE_STRING,	NULL},
		{"p.clock",	ZBX_PROTO_TAG_CLOCK,		ZBX_JSON_TYPE_INT,	NULL},
		{"p.ns",	ZBX_PROTO_TAG_NS,		ZBX_JSON_TYPE_INT,	NULL},
		{"p.timestamp",	ZBX_PROTO_TAG_LOGTIMESTAMP,	ZBX_JSON_TYPE_INT,	"0"},
		{"p.source",	ZBX_PROTO_TAG_LOGSOURCE,	ZBX_JSON_TYPE_STRING,	""},
		{"p.severity",	ZBX_PROTO_TAG_LOGSEVERITY,	ZBX_JSON_TYPE_INT,	"0"},
		{"p.value",	ZBX_PROTO_TAG_VALUE,		ZBX_JSON_TYPE_STRING,	NULL},
		{"p.logeventid",ZBX_PROTO_TAG_LOGEVENTID,	ZBX_JSON_TYPE_INT,	"0"},
		{NULL}
		}
};

static ZBX_HISTORY_TABLE dht = {
	"proxy_dhistory", "dhistory_lastid", "", "",
		{
		{"p.clock",	ZBX_PROTO_TAG_CLOCK,		ZBX_JSON_TYPE_INT,	NULL},
		{"p.druleid",	ZBX_PROTO_TAG_DRULE,		ZBX_JSON_TYPE_INT,	NULL},
		{"p.dcheckid",	ZBX_PROTO_TAG_DCHECK,		ZBX_JSON_TYPE_INT,	NULL},
		{"p.type",	ZBX_PROTO_TAG_TYPE,		ZBX_JSON_TYPE_INT,	NULL},
		{"p.ip",	ZBX_PROTO_TAG_IP,		ZBX_JSON_TYPE_STRING,	NULL},
		{"p.dns",	ZBX_PROTO_TAG_DNS,		ZBX_JSON_TYPE_STRING,	NULL},
		{"p.port",	ZBX_PROTO_TAG_PORT,		ZBX_JSON_TYPE_INT,	"0"},
		{"p.key_",	ZBX_PROTO_TAG_KEY,		ZBX_JSON_TYPE_STRING,	""},
		{"p.value",	ZBX_PROTO_TAG_VALUE,		ZBX_JSON_TYPE_STRING,	""},
		{"p.status",	ZBX_PROTO_TAG_STATUS,		ZBX_JSON_TYPE_INT,	"0"},
		{NULL}
		}
};

static ZBX_HISTORY_TABLE areg = {
	"proxy_autoreg_host", "autoreg_host_lastid", "", "",
		{
		{"p.clock",	ZBX_PROTO_TAG_CLOCK,		ZBX_JSON_TYPE_INT,	NULL},
		{"p.host",	ZBX_PROTO_TAG_HOST,		ZBX_JSON_TYPE_STRING,	NULL},
		{"p.listen_ip",	ZBX_PROTO_TAG_IP,		ZBX_JSON_TYPE_STRING,	""},
		{"p.listen_dns",ZBX_PROTO_TAG_DNS,		ZBX_JSON_TYPE_STRING,	""},
		{"p.listen_port",ZBX_PROTO_TAG_PORT,		ZBX_JSON_TYPE_STRING,	"0"},
		{NULL}
		}
};

/******************************************************************************
 *                                                                            *
 * Function: get_proxy_id                                                     *
 *                                                                            *
 * Purpose:                                                                   *
 *                                                                            *
 * Parameters: host - [IN] require size 'HOST_HOST_LEN_MAX'                   *
 *                                                                            *
 * Return value:  SUCCEED - processed successfully                            *
 *                FAIL - an error occurred                                    *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
int	get_proxy_id(struct zbx_json_parse *jp, zbx_uint64_t *hostid, char *host, char *error, int max_error_len)
{
	DB_RESULT	result;
	DB_ROW		row;
	char		*host_esc;
	int		ret = FAIL;

	if (SUCCEED == zbx_json_value_by_name(jp, ZBX_PROTO_TAG_HOST, host, HOST_HOST_LEN_MAX))
	{
		if (FAIL == zbx_check_hostname(host))
		{
			zbx_snprintf(error, max_error_len, "invalid proxy name [%s]", host);
			return ret;
		}

		host_esc = DBdyn_escape_string(host);

		result = DBselect(
				"select hostid"
				" from hosts"
				" where host='%s'"
					" and status in (%d)"
					DB_NODE,
				host_esc, HOST_STATUS_PROXY_ACTIVE, DBnode_local("hostid"));

		zbx_free(host_esc);

		if (NULL != (row = DBfetch(result)) && FAIL == DBis_null(row[0]))
		{
			ZBX_STR2UINT64(*hostid, row[0]);
			ret = SUCCEED;
		}
		else
			zbx_snprintf(error, max_error_len, "proxy [%s] not found", host);

		DBfree_result(result);
	}
	else
		zbx_snprintf(error, max_error_len, "missing name of proxy");

	return ret;
}

/******************************************************************************
 *                                                                            *
 * Function: update_proxy_lastaccess                                          *
 *                                                                            *
 * Purpose:                                                                   *
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
void	update_proxy_lastaccess(const zbx_uint64_t hostid)
{
	DBexecute("update hosts set lastaccess=%d where hostid=" ZBX_FS_UI64, time(NULL), hostid);
}

/******************************************************************************
 *                                                                            *
 * Function: get_proxyconfig_table                                            *
 *                                                                            *
 * Purpose: prepare proxy configuration data                                  *
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
static void	get_proxyconfig_table(zbx_uint64_t proxy_hostid, struct zbx_json *j, const ZBX_TABLE *table,
		const char *condition)
{
	const char	*__function_name = "get_proxyconfig_table";
	char		sql[MAX_STRING_LEN];
	int		offset = 0, f, fld;
	DB_RESULT	result;
	DB_ROW		row;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() proxy_hostid:" ZBX_FS_UI64 " table:'%s'",
			__function_name, proxy_hostid, table->table);

	zbx_json_addobject(j, table->table);
	zbx_json_addarray(j, "fields");

	offset += zbx_snprintf(sql + offset, sizeof(sql) - offset, "select t.%s", table->recid);

	zbx_json_addstring(j, NULL, table->recid, ZBX_JSON_TYPE_STRING);

	for (f = 0; 0 != table->fields[f].name; f++)
	{
		if (0 == (table->fields[f].flags & ZBX_PROXY))
			continue;

		offset += zbx_snprintf(sql + offset, sizeof(sql) - offset, ",t.%s", table->fields[f].name);

		zbx_json_addstring(j, NULL, table->fields[f].name, ZBX_JSON_TYPE_STRING);
	}

	zbx_json_close(j);	/* fields */

	offset += zbx_snprintf(sql + offset, sizeof(sql) - offset, " from %s t%s", table->table, condition);

	offset += zbx_snprintf(sql + offset, sizeof(sql) - offset, " order by t.%s", table->recid);

	zbx_json_addarray(j, "data");

	result = DBselect("%s", sql);

	while (NULL != (row = DBfetch(result)))
	{
		fld = 0;
		zbx_json_addarray(j, NULL);
		zbx_json_addstring(j, NULL, row[fld++], ZBX_JSON_TYPE_INT);

		for (f = 0; 0 != table->fields[f].name; f ++)
		{
			if (0 == (table->fields[f].flags & ZBX_PROXY))
				continue;

			switch (table->fields[f].type)
			{
				case ZBX_TYPE_INT:
				case ZBX_TYPE_UINT:
				case ZBX_TYPE_ID:
					zbx_json_addstring(j, NULL, row[fld++], ZBX_JSON_TYPE_INT);
					break;
				default:
					zbx_json_addstring(j, NULL, row[fld++], ZBX_JSON_TYPE_STRING);
					break;
			}
		}
		zbx_json_close(j);
	}
	DBfree_result(result);

	zbx_json_close(j);	/* data */
	zbx_json_close(j);	/* table->table */

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

void	get_proxy_monitored_hostids(zbx_uint64_t proxy_hostid, zbx_uint64_t **hostids, int *hostids_alloc, int *hostids_num)
{
	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	hostid, *ids = NULL;
	int		ids_alloc = 0, ids_num = 0;
	char		*sql = NULL;
	int		sql_alloc = 612, sql_offset;

	sql = zbx_malloc(sql, sql_alloc * sizeof(char));

	result = DBselect(
			"select hostid"
			" from hosts"
			" where proxy_hostid=" ZBX_FS_UI64
				" and status in (%d,%d)",
			proxy_hostid,
			HOST_STATUS_MONITORED, HOST_STATUS_NOT_MONITORED);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(hostid, row[0]);

		uint64_array_add(hostids, hostids_alloc, hostids_num, hostid, 64);
		uint64_array_add(&ids, &ids_alloc, &ids_num, hostid, 64);
	}
	DBfree_result(result);

	while (0 != ids_num)
	{
		sql_offset = 0;
		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 45,
				"select templateid"
				" from hosts_templates"
				" where");
		DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset,
				"hostid", ids, ids_num);

		ids_num = 0;

		result = DBselect("%s", sql);

		while (NULL != (row = DBfetch(result)))
		{
			ZBX_STR2UINT64(hostid, row[0]);

			uint64_array_add(hostids, hostids_alloc, hostids_num, hostid, 64);
			uint64_array_add(&ids, &ids_alloc, &ids_num, hostid, 64);
		}
		DBfree_result(result);
	}

	zbx_free(ids);
	zbx_free(sql);
}

/******************************************************************************
 *                                                                            *
 * Function: get_proxyconfig_data                                             *
 *                                                                            *
 * Purpose: prepare proxy configuration data                                  *
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
void	get_proxyconfig_data(zbx_uint64_t proxy_hostid, struct zbx_json *j)
{
	struct proxytable_t
	{
		const char	*table;
	};

	static const struct proxytable_t pt[] =
	{
		{"globalmacro"},
		{"hosts"},
		{"interface"},
		{"hosts_templates"},
		{"hostmacro"},
		{"items"},
		{"drules"},
		{"dchecks"},
		{"regexps"},
		{"expressions"},
		{"groups"},
		{"config"},
		{NULL}
	};

	const char	*__function_name = "get_proxyconfig_data";
	int		i;
	const ZBX_TABLE	*table;
	char		*condition = NULL;
	int		condition_alloc = 512, condition_offset;
	zbx_uint64_t	*hostids = NULL;
	int		hostids_alloc = 0, hostids_num = 0;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() proxy_hostid:" ZBX_FS_UI64, __function_name, proxy_hostid);

	assert(proxy_hostid);

	condition = zbx_malloc(condition, condition_alloc * sizeof(char));

	get_proxy_monitored_hostids(proxy_hostid, &hostids, &hostids_alloc, &hostids_num);

	for (i = 0; NULL != pt[i].table; i++)
	{
		if (NULL == (table = DBget_table(pt[i].table)))
			continue;

		condition_offset = 0;

		if (0 == strcmp(pt[i].table, "hosts"))
		{
			zbx_snprintf_alloc(&condition, &condition_alloc, &condition_offset, 256,
					" where%s", 0 == hostids_num ? " 0=1" : "");
			DBadd_condition_alloc(&condition, &condition_alloc, &condition_offset,
					"t.hostid", hostids, hostids_num);
		}
		else if (0 == strcmp(pt[i].table, "items"))
		{
			zbx_snprintf_alloc(&condition, &condition_alloc, &condition_offset, 256,
					",hosts r where t.hostid=r.hostid"
						" and r.proxy_hostid=" ZBX_FS_UI64
						" and r.status in (%d,%d)"
						" and t.status in (%d,%d,%d)"
						" and t.type in (%d,%d,%d,%d,%d,%d,%d,%d,%d,%d,%d,%d,%d,%d)",
					proxy_hostid,
					HOST_STATUS_MONITORED, HOST_STATUS_NOT_MONITORED,
					ITEM_STATUS_ACTIVE, ITEM_STATUS_DISABLED, ITEM_STATUS_NOTSUPPORTED,
					ITEM_TYPE_ZABBIX, ITEM_TYPE_ZABBIX_ACTIVE,
					ITEM_TYPE_SNMPv1, ITEM_TYPE_SNMPv2c, ITEM_TYPE_SNMPv3,
					ITEM_TYPE_IPMI, ITEM_TYPE_TRAPPER, ITEM_TYPE_SIMPLE,
					ITEM_TYPE_HTTPTEST, ITEM_TYPE_EXTERNAL, ITEM_TYPE_DB_MONITOR,
					ITEM_TYPE_SSH, ITEM_TYPE_TELNET, ITEM_TYPE_JMX);
		}
		else if (0 == strcmp(pt[i].table, "hosts_templates"))
		{
			zbx_snprintf_alloc(&condition, &condition_alloc, &condition_offset, 256,
					",hosts r where t.hostid=r.hostid"
						" and r.proxy_hostid=" ZBX_FS_UI64
						" and r.status in (%d,%d)",
					proxy_hostid,
					HOST_STATUS_MONITORED, HOST_STATUS_NOT_MONITORED);
		}
		else if (0 == strcmp(pt[i].table, "drules"))
		{
			zbx_snprintf_alloc(&condition, &condition_alloc, &condition_offset, 256,
					" where t.proxy_hostid=" ZBX_FS_UI64
						" and t.status=%d",
					proxy_hostid,
					DRULE_STATUS_MONITORED);
		}
		else if (0 == strcmp(pt[i].table, "dchecks"))
		{
			zbx_snprintf_alloc(&condition, &condition_alloc, &condition_offset, 256,
					",drules r where t.druleid=r.druleid"
						" and r.proxy_hostid=" ZBX_FS_UI64
						" and r.status=%d",
					proxy_hostid,
					DRULE_STATUS_MONITORED);
		}
		else if (0 == strcmp(pt[i].table, "hostmacro"))
		{
			zbx_snprintf_alloc(&condition, &condition_alloc, &condition_offset, 256,
					" where%s", 0 == hostids_num ? " 0=1" : "");
			DBadd_condition_alloc(&condition, &condition_alloc, &condition_offset,
					"t.hostid", hostids, hostids_num);
		}
		else if (0 == strcmp(pt[i].table, "interface"))
		{
			zbx_snprintf_alloc(&condition, &condition_alloc, &condition_offset, 256,
					" where%s", 0 == hostids_num ? " 0=1" : "");
			DBadd_condition_alloc(&condition, &condition_alloc, &condition_offset,
					"t.hostid", hostids, hostids_num);
		}
		else if (0 == strcmp(pt[i].table, "groups"))
		{
			zbx_snprintf_alloc(&condition, &condition_alloc, &condition_offset, 256,
					",config r where t.groupid=r.discovery_groupid");
		}
		else
			*condition = '\0';

		get_proxyconfig_table(proxy_hostid, j, table, condition);
	}

	zbx_free(condition);

	zabbix_log(LOG_LEVEL_DEBUG, "%s", j->buffer);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

/******************************************************************************
 *                                                                            *
 * Function: process_proxyconfig_table                                        *
 *                                                                            *
 * Purpose: update configuration table                                        *
 *                                                                            *
 * Return value: SUCCESS - processed successfully                             *
 *               FAIL - an error occurred                                     *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 ******************************************************************************/
static int	process_proxyconfig_table(struct zbx_json_parse *jp, const char *tablename, struct zbx_json_parse *jp_obj)
{
	const char		*__function_name = "process_proxyconfig_table";
	int			f, field_count, insert, is_null, ret = FAIL;
	const ZBX_TABLE		*table = NULL;
	const ZBX_FIELD		*fields[ZBX_MAX_FIELDS];
	struct zbx_json_parse	jp_data, jp_row;
	char			buf[MAX_STRING_LEN], *esc;
	const char		*p, *pf;
	zbx_uint64_t		recid, *new = NULL, *old = NULL;
	int			new_alloc = 100, new_num = 0, old_alloc = 100, old_num = 0;
	char			*sql = NULL, *sq2 = NULL;
	int			sql_alloc = 4096, sql_offset, sq2_alloc = 512, sq2_offset;
	DB_RESULT		result;
	DB_ROW			row;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() tablename:'%s'", __function_name, tablename);

	if (NULL == (table = DBget_table(tablename)))
	{
		zabbix_log(LOG_LEVEL_WARNING, "Invalid table name \"%s\"", tablename);
		goto exit;
	}

	new = zbx_malloc(new, new_alloc * sizeof(zbx_uint64_t));
	old = zbx_malloc(old, old_alloc * sizeof(zbx_uint64_t));
	sql = zbx_malloc(sql, sql_alloc * sizeof(char));
	sq2 = zbx_malloc(sq2, sq2_alloc * sizeof(char));

	result = DBselect("select %s from %s", table->recid, table->table);
	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(recid, row[0]);
		uint64_array_add(&old, &old_alloc, &old_num, recid, 64);
	}
	DBfree_result(result);

	/************************************************************************************/
	/* T1. RECEIVED JSON (jp_obj) DATA FORMAT                                           */
	/************************************************************************************/
	/* Line |                  Data                     | Corresponding structure in DB */
	/* -----+-------------------------------------------+------------------------------ */
	/*   1  | {                                         |                               */
	/*   2  |         "hosts": {                        | first table                   */
	/*   3  |                 "fields": [               | list of table's columns       */
	/*   4  |                         "hostid",         | first column                  */
	/*   5  |                         "host",           | second column                 */
	/*   6  |                         ...               | ...columns                    */
	/*   7  |                 ],                        |                               */
	/*   8  |                 "data": [                 | the table data                */
	/*   9  |                         [                 | first entry                   */
	/*  10  |                               1,          | value for first column        */
	/*  11  |                               "zbx01",    | value for second column       */
	/*  12  |                               ...         | ...values                     */
	/*  13  |                         ],                |                               */
	/*  14  |                         [                 | second entry                  */
	/*  15  |                               2,          | value for first column        */
	/*  16  |                               "zbx02",    | value for second column       */
	/*  17  |                               ...         | ...values                     */
	/*  18  |                         ],                |                               */
	/*  19  |                         ...               | ...entries                    */
	/*  20  |                 ]                         |                               */
	/*  21  |         },                                |                               */
	/*  22  |         "items": {                        | second table                  */
	/*  23  |                 ...                       | ...                           */
	/*  24  |         },                                |                               */
	/*  25  |         ...                               | ...tables                     */
	/*  26  | }                                         |                               */
	/************************************************************************************/

	if (FAIL == zbx_json_brackets_by_name(jp_obj, "fields", &jp_data))	/* get table columns (line 3 in T1) */
		goto json_error;

	p = NULL;
	field_count = 0;
	while (NULL != (p = zbx_json_next_value(&jp_data, p, buf, sizeof(buf), NULL)))	/* iterate column names (lines 4-6 in T1) */
	{
		if (NULL == (fields[field_count] = DBget_field(table, buf)))
		{
			zabbix_log(LOG_LEVEL_WARNING, "Invalid field name \"%s\"", buf);
			goto db_error;
		}

		field_count++;
	}

	/* get the entries (line 8 in T1) */
	if (FAIL == zbx_json_brackets_by_name(jp_obj, ZBX_PROTO_TAG_DATA, &jp_data))
		goto json_error;

	/* special preprocessing for 'items' table */
	/* in order to eliminate the conflicts in the 'hostid,key_' unique index */
	if (0 == strcmp(tablename, "items"))
	{
#ifdef HAVE_MYSQL
		if (ZBX_DB_OK > DBexecute("update items set key_=concat('#',itemid)"))
#else
		if (ZBX_DB_OK > DBexecute("update items set key_='#'||itemid"))
#endif
			goto db_error;
	}
	else if (0 == strcmp(tablename, "groups"))
	{
		if (ZBX_DB_OK > DBexecute("delete from config"))
			goto db_error;
	}

	p = NULL;
	sql_offset = 0;

#ifdef HAVE_ORACLE
	zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 8, "begin\n");
#endif

	while (NULL != (p = zbx_json_next(&jp_data, p)))	/* iterate the entries (lines 9, 14 and 19 in T1) */
	{
		if (FAIL == zbx_json_brackets_open(p, &jp_row))
			goto json_error;

		pf = NULL;
		if (NULL == (pf = zbx_json_next_value(&jp_row, pf, buf, sizeof(buf), NULL)))
			goto json_error;

		/* check whether we need to insert a new entry or update an existing */
		ZBX_STR2UINT64(recid, buf);
		insert = (SUCCEED == uint64_array_exists(old, old_num, recid) ? 0 : 1);
		uint64_array_add(&new, &new_alloc, &new_num, recid, 64);

		if (0 != insert)
		{
			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 128, "insert into %s (", table->table);

			for (f = 0; f < field_count; f++)
				zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 128, "%s,", fields[f]->name);

			sql_offset--;
			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 128, ") values (" ZBX_FS_UI64 ",", recid);
		}
		else if (1 == field_count)	/* only primary key given, no update needed */
		{
			continue;
		}
		else
			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 128, "update %s set ", table->table);

		f = 1;
		while (NULL != (pf = zbx_json_next_value(&jp_row, pf, buf, sizeof(buf), &is_null)))
		{
			/* parse values for the entry (lines 10-12 in T1) */

			if (f == field_count)
			{
				zabbix_log(LOG_LEVEL_WARNING, "Invalid number of fields \"%.*s\"",
						jp_row.end - jp_row.start + 1, jp_row.start);
				goto db_error;
			}

			if (0 == insert)
			{
				zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, ZBX_FIELDNAME_LEN + 2,
						"%s=", fields[f]->name);
			}

			if (0 != is_null)
			{
				if (0 != (fields[f]->flags & ZBX_NOTNULL))
				{
					zabbix_log(LOG_LEVEL_WARNING, "Column '%s' cannot be null", fields[f]->name);
					goto db_error;
				}

				zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 6, "null,");
			}
			else
			{
				switch (fields[f]->type)
				{
					case ZBX_TYPE_INT:
					case ZBX_TYPE_UINT:
					case ZBX_TYPE_ID:
					case ZBX_TYPE_FLOAT:
						zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, strlen(buf) + 2,
								"%s,", buf);
						break;
					default:
						esc = DBdyn_escape_string(buf);
						zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, strlen(esc) + 4,
								"'%s',", esc);
						zbx_free(esc);
				}
			}

			f++;
		}

		if (f != field_count)
		{
			zabbix_log(LOG_LEVEL_WARNING, "Invalid number of fields \"%.*s\"",
					jp_row.end - jp_row.start + 1, jp_row.start);
			goto db_error;
		}

		sql_offset--;
		if (0 != insert)
		{
			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 4, ");\n");
		}
		else
		{
			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 256, " where %s=" ZBX_FS_UI64 ";\n",
					table->recid, recid);
		}

		if (ZBX_MAX_SQL_SIZE < sql_offset)
		{
#ifdef HAVE_ORACLE
			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 8, "end;\n");
#endif
			if (ZBX_DB_OK > DBexecute("%s", sql))
				goto db_error;

			sql_offset = 0;
#ifdef HAVE_ORACLE
			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 8, "begin\n");
#endif
		}
	}

#ifdef HAVE_ORACLE
	zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 8, "end;\n");
#endif

	uint64_array_remove(old, &old_num, new, new_num);

	if (0 < old_num)
	{
		sq2_offset = 0;
		zbx_snprintf_alloc(&sq2, &sq2_alloc, &sq2_offset, 128, "delete from %s where", table->table);
		DBadd_condition_alloc(&sq2, &sq2_alloc, &sq2_offset, table->recid, old, old_num);
		if (ZBX_DB_OK > DBexecute("%s", sq2))
			goto db_error;
	}

	if (sql_offset > 16)	/* In ORACLE always present begin..end; */
		if (ZBX_DB_OK > DBexecute("%s", sql))
			goto db_error;

	ret = SUCCEED;
json_error:
	if (SUCCEED != ret)
		zabbix_log(LOG_LEVEL_DEBUG, "cannot process table \"%s\": %s", tablename, zbx_json_strerror());
db_error:
	zbx_free(sq2);
	zbx_free(sql);
	zbx_free(new);
	zbx_free(old);
exit:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(ret));

	return ret;
}

/******************************************************************************
 *                                                                            *
 * Function: process_proxyconfig                                              *
 *                                                                            *
 * Purpose: update configuration                                              *
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
void	process_proxyconfig(struct zbx_json_parse *jp_data)
{
	const char		*__function_name = "process_proxyconfig";
	char			buf[MAX_STRING_LEN];
	size_t			len = sizeof(buf);
	const char		*p = NULL;
	struct zbx_json_parse	jp_obj;
	int			ret = SUCCEED;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	DBbegin();

	/* iterate the tables (lines 2, 22 and 25 in T1) */
	while (NULL != (p = zbx_json_pair_next(jp_data, p, buf, len)) && SUCCEED == ret)
	{
		if (FAIL == zbx_json_brackets_open(p, &jp_obj))
		{
			zabbix_log(LOG_LEVEL_DEBUG, "cannot process table \"%s\". %s", buf, zbx_json_strerror());
			ret = FAIL;
			break;
		}

		ret = process_proxyconfig_table(jp_data, buf, &jp_obj);
	}

	if (ret == SUCCEED)
	{
		DBcommit();
		DCsync_configuration();
	}
	else
		DBrollback();

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

/******************************************************************************
 *                                                                            *
 * Function: get_host_availability_data                                       *
 *                                                                            *
 * Purpose:                                                                   *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value:  SUCCEED - processed successfully                            *
 *                FAIL - an error occurred                                    *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
int	get_host_availability_data(struct zbx_json *j)
{
	typedef struct
	{
		zbx_uint64_t	hostid;
		char		*error, *snmp_error, *ipmi_error, *jmx_error;
		unsigned char	available, snmp_available, ipmi_available, jmx_available;
	}
	zbx_host_availability_t;

	const char			*__function_name = "get_host_availability_data";
	zbx_uint64_t			hostid;
	size_t				sz;
	DB_RESULT			result;
	DB_ROW				row;
	static zbx_host_availability_t	*ha = NULL;
	static int			ha_alloc = 0, ha_num = 0;
	int				index, new, ret = FAIL;
	unsigned char			available, snmp_available, ipmi_available, jmx_available;
	char				*error, *snmp_error, *ipmi_error, *jmx_error;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	zbx_json_addarray(j, ZBX_PROTO_TAG_DATA);

	result = DBselect(
			"select hostid,available,error,snmp_available,snmp_error,"
				"ipmi_available,ipmi_error,jmx_available,jmx_error"
			" from hosts");

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(hostid, row[0]);

		new = 0;

		index = get_nearestindex(ha, sizeof(zbx_host_availability_t), ha_num, hostid);

		if (index == ha_num || ha[index].hostid != hostid)
		{
			if (ha_num == ha_alloc)
			{
				ha_alloc += 8;
				ha = zbx_realloc(ha, sizeof(zbx_host_availability_t) * ha_alloc);
			}

			if (0 != (sz = sizeof(zbx_host_availability_t) * (ha_num - index)))
				memmove(&ha[index + 1], &ha[index], sz);
			ha_num++;

			ha[index].hostid = hostid;
			ha[index].available = HOST_AVAILABLE_UNKNOWN;
			ha[index].snmp_available = HOST_AVAILABLE_UNKNOWN;
			ha[index].ipmi_available = HOST_AVAILABLE_UNKNOWN;
			ha[index].jmx_available = HOST_AVAILABLE_UNKNOWN;
			ha[index].error = NULL;
			ha[index].snmp_error = NULL;
			ha[index].ipmi_error = NULL;
			ha[index].jmx_error = NULL;

			new = 1;
		}

		available = (unsigned char)atoi(row[1]);
		error = row[2];
		snmp_available = (unsigned char)atoi(row[3]);
		snmp_error = row[4];
		ipmi_available = (unsigned char)atoi(row[5]);
		ipmi_error = row[6];
		jmx_available = (unsigned char)atoi(row[7]);
		jmx_error = row[8];

		if (0 == new && ha[index].available == available &&
				ha[index].snmp_available == snmp_available &&
				ha[index].ipmi_available == ipmi_available &&
				ha[index].jmx_available == jmx_available &&
				0 == strcmp(ha[index].error, error) &&
				0 == strcmp(ha[index].snmp_error, snmp_error) &&
				0 == strcmp(ha[index].ipmi_error, ipmi_error) &&
				0 == strcmp(ha[index].jmx_error, jmx_error))
			continue;

		zbx_json_addobject(j, NULL);

		zbx_json_adduint64(j, ZBX_PROTO_TAG_HOSTID, hostid);

		if (1 == new || ha[index].available != available)
		{
			zbx_json_adduint64(j, ZBX_PROTO_TAG_AVAILABLE, available);
			ha[index].available = available;
		}

		if (1 == new || ha[index].snmp_available != snmp_available)
		{
			zbx_json_adduint64(j, ZBX_PROTO_TAG_SNMP_AVAILABLE, snmp_available);
			ha[index].snmp_available = snmp_available;
		}

		if (1 == new || ha[index].ipmi_available != ipmi_available)
		{
			zbx_json_adduint64(j, ZBX_PROTO_TAG_IPMI_AVAILABLE, ipmi_available);
			ha[index].ipmi_available = ipmi_available;
		}

		if (1 == new || ha[index].jmx_available != jmx_available)
		{
			zbx_json_adduint64(j, ZBX_PROTO_TAG_JMX_AVAILABLE, jmx_available);
			ha[index].jmx_available = jmx_available;
		}

		if (1 == new || 0 != strcmp(ha[index].error, error))
		{
			zbx_json_addstring(j, ZBX_PROTO_TAG_ERROR, error, ZBX_JSON_TYPE_STRING);
			ZBX_STRDUP(ha[index].error, error);
		}

		if (1 == new || 0 != strcmp(ha[index].snmp_error, snmp_error))
		{
			zbx_json_addstring(j, ZBX_PROTO_TAG_SNMP_ERROR, snmp_error, ZBX_JSON_TYPE_STRING);
			ZBX_STRDUP(ha[index].snmp_error, snmp_error);
		}

		if (1 == new || 0 != strcmp(ha[index].ipmi_error, ipmi_error))
		{
			zbx_json_addstring(j, ZBX_PROTO_TAG_IPMI_ERROR, ipmi_error, ZBX_JSON_TYPE_STRING);
			ZBX_STRDUP(ha[index].ipmi_error, ipmi_error);
		}

		if (1 == new || 0 != strcmp(ha[index].jmx_error, jmx_error))
		{
			zbx_json_addstring(j, ZBX_PROTO_TAG_JMX_ERROR, jmx_error, ZBX_JSON_TYPE_STRING);
			ZBX_STRDUP(ha[index].jmx_error, jmx_error);
		}

		zbx_json_close(j);

		ret = SUCCEED;
	}
	DBfree_result(result);

	zbx_json_close(j);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(ret));

	return ret;
}

/******************************************************************************
 *                                                                            *
 * Function: process_host_availability                                        *
 *                                                                            *
 * Purpose: update proxy hosts availability                                   *
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
void	process_host_availability(struct zbx_json_parse *jp)
{
	const char		*__function_name = "process_host_availability";
	zbx_uint64_t		hostid;
	struct zbx_json_parse	jp_data, jp_row;
	const char		*p = NULL;
	char			tmp[HOST_ERROR_LEN_MAX], *sql = NULL, *error_esc;
	int			sql_alloc = 4096, sql_offset = 0, tmp_offset, no_data;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	/* "data" tag lists the hosts */
	if (SUCCEED != zbx_json_brackets_by_name(jp, ZBX_PROTO_TAG_DATA, &jp_data))
	{
		zabbix_log(LOG_LEVEL_WARNING, "Received invalid host availability data. %s", zbx_json_strerror());
		goto exit;
	}

	if (SUCCEED == zbx_json_object_is_empty(&jp_data))
		goto exit;

	sql = zbx_malloc(sql, sql_alloc);

	DBbegin();

#ifdef HAVE_ORACLE
	zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 8, "begin\n");
#endif

	while (NULL != (p = zbx_json_next(&jp_data, p)))	/* iterate the host entries */
	{
		if (SUCCEED != zbx_json_brackets_open(p, &jp_row))
		{
			zabbix_log(LOG_LEVEL_WARNING, "Invalid host availability data. %s", zbx_json_strerror());
			continue;
		}

		tmp_offset = sql_offset;
		no_data = 1;

		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 32, "update hosts set ");

		if (SUCCEED == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_AVAILABLE, tmp, sizeof(tmp)))
		{
			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 32, "available=%d,", atoi(tmp));
			no_data = 0;
		}

		if (SUCCEED == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_SNMP_AVAILABLE, tmp, sizeof(tmp)))
		{
			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 32, "snmp_available=%d,", atoi(tmp));
			no_data = 0;
		}

		if (SUCCEED == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_IPMI_AVAILABLE, tmp, sizeof(tmp)))
		{
			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 32, "ipmi_available=%d,", atoi(tmp));
			no_data = 0;
		}

		if (SUCCEED == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_JMX_AVAILABLE, tmp, sizeof(tmp)))
		{
			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 32, "jmx_available=%d,", atoi(tmp));
			no_data = 0;
		}

		if (SUCCEED == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_ERROR, tmp, sizeof(tmp)))
		{
			error_esc = DBdyn_escape_string_len(tmp, HOST_ERROR_LEN);
			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, strlen(error_esc) + 16,
					"error='%s',", error_esc);
			zbx_free(error_esc);
			no_data = 0;
		}

		if (SUCCEED == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_SNMP_ERROR, tmp, sizeof(tmp)))
		{
			error_esc = DBdyn_escape_string_len(tmp, HOST_ERROR_LEN);
			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, strlen(error_esc) + 16,
					"snmp_error='%s',", error_esc);
			zbx_free(error_esc);
			no_data = 0;
		}

		if (SUCCEED == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_IPMI_ERROR, tmp, sizeof(tmp)))
		{
			error_esc = DBdyn_escape_string_len(tmp, HOST_ERROR_LEN);
			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, strlen(error_esc) + 16,
					"ipmi_error='%s',", error_esc);
			zbx_free(error_esc);
			no_data = 0;
		}

		if (SUCCEED == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_JMX_ERROR, tmp, sizeof(tmp)))
		{
			error_esc = DBdyn_escape_string_len(tmp, HOST_ERROR_LEN);
			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, strlen(error_esc) + 16,
					"jmx_error='%s',", error_esc);
			zbx_free(error_esc);
			no_data = 0;
		}

		if (SUCCEED != zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_HOSTID, tmp, sizeof(tmp)))
		{
			zabbix_log(LOG_LEVEL_WARNING, "Invalid host availability data. %s", zbx_json_strerror());
			sql_offset = tmp_offset;
			continue;
		}

		if (SUCCEED != is_uint64(tmp, &hostid) || 1 == no_data)
		{
			zabbix_log(LOG_LEVEL_WARNING, "Invalid host availability data.");
			sql_offset = tmp_offset;
			continue;
		}

		sql_offset--;
		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 40, " where hostid=" ZBX_FS_UI64 ";\n", hostid);

		DBexecute_overflowed_sql(&sql, &sql_alloc, &sql_offset);
	}

#ifdef HAVE_ORACLE
	zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 8, "end;\n");
#endif

	if (sql_offset > 16) /* In ORACLE always present begin..end; */
		DBexecute("%s", sql);

	DBcommit();

	zbx_free(sql);
exit:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

/******************************************************************************
 *                                                                            *
 * Function: proxy_get_lastid                                                 *
 *                                                                            *
 * Purpose:                                                                   *
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
static void	proxy_get_lastid(const ZBX_HISTORY_TABLE *ht, zbx_uint64_t *lastid)
{
	const char	*__function_name = "proxy_get_lastid";
	DB_RESULT	result;
	DB_ROW		row;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() [%s.%s]", __function_name, ht->table, ht->lastfieldname);

	result = DBselect("select nextid from ids where table_name='%s' and field_name='%s'",
			ht->table,
			ht->lastfieldname);

	if (NULL == (row = DBfetch(result)))
		*lastid = 0;
	else
		ZBX_STR2UINT64(*lastid, row[0]);

	DBfree_result(result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():" ZBX_FS_UI64,	__function_name, *lastid);
}

/******************************************************************************
 *                                                                            *
 * Function: proxy_set_lastid                                                 *
 *                                                                            *
 * Purpose:                                                                   *
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
static void	proxy_set_lastid(const ZBX_HISTORY_TABLE *ht, const zbx_uint64_t lastid)
{
	const char	*__function_name = "proxy_set_lastid";
	DB_RESULT	result;
	DB_ROW		row;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() [%s.%s:" ZBX_FS_UI64 "]",
			__function_name, ht->table, ht->lastfieldname, lastid);

	result = DBselect("select 1 from ids where table_name='%s' and field_name='%s'",
			ht->table,
			ht->lastfieldname);

	if (NULL == (row = DBfetch(result)))
	{
		DBexecute("insert into ids (nodeid,table_name,field_name,nextid)"
				"values (0,'%s','%s'," ZBX_FS_UI64 ")",
				ht->table,
				ht->lastfieldname,
				lastid);
	}
	else
	{
		DBexecute("update ids set nextid=" ZBX_FS_UI64
				" where table_name='%s' and field_name='%s'",
				lastid,
				ht->table,
				ht->lastfieldname);
	}

	DBfree_result(result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

void	proxy_set_hist_lastid(const zbx_uint64_t lastid)
{
	proxy_set_lastid(&ht, lastid);
}

void	proxy_set_dhis_lastid(const zbx_uint64_t lastid)
{
	proxy_set_lastid(&dht, lastid);
}

void	proxy_set_areg_lastid(const zbx_uint64_t lastid)
{
	proxy_set_lastid(&areg, lastid);
}

/******************************************************************************
 *                                                                            *
 * Function: proxy_get_history_data                                           *
 *                                                                            *
 * Purpose:                                                                   *
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
static int	proxy_get_history_data(struct zbx_json *j, const ZBX_HISTORY_TABLE *ht, zbx_uint64_t *lastid)
{
	const char	*__function_name = "proxy_get_history_data";
	int		offset = 0, f, records = 0;
	char		sql[MAX_STRING_LEN];
	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	id;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() table:'%s'", __function_name, ht->table);

	*lastid = 0;

	proxy_get_lastid(ht, &id);

	offset += zbx_snprintf(sql + offset, sizeof(sql) - offset, "select p.id");

	for (f = 0; NULL != ht->fields[f].field; f++)
		offset += zbx_snprintf(sql + offset, sizeof(sql) - offset, ",%s", ht->fields[f].field);

	offset += zbx_snprintf(sql + offset, sizeof(sql) - offset, " from %s%s p"
			" where %sp.id>" ZBX_FS_UI64 " order by p.id",
			ht->from, ht->table,
			ht->where,
			id);

	result = DBselectN(sql, ZBX_MAX_HRECORDS);

	while (NULL != (row = DBfetch(result)))
	{
		zbx_json_addobject(j, NULL);

		ZBX_STR2UINT64(*lastid, row[0]);

		for (f = 0; NULL != ht->fields[f].field; f++)
		{
			if (NULL != ht->fields[f].default_value && 0 == strcmp(row[f + 1], ht->fields[f].default_value))
				continue;

			zbx_json_addstring(j, ht->fields[f].tag, row[f + 1], ht->fields[f].jt);
		}

		records++;

		zbx_json_close(j);
	}

	DBfree_result(result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%d lastid:" ZBX_FS_UI64, __function_name, records, *lastid);

	return records;
}

int	proxy_get_hist_data(struct zbx_json *j, zbx_uint64_t *lastid)
{
	return proxy_get_history_data(j, &ht, lastid);
}

int	proxy_get_dhis_data(struct zbx_json *j, zbx_uint64_t *lastid)
{
	return proxy_get_history_data(j, &dht, lastid);
}

int	proxy_get_areg_data(struct zbx_json *j, zbx_uint64_t *lastid)
{
	return proxy_get_history_data(j, &areg, lastid);
}

static void	calc_timestamp(char *line, int *timestamp, char *format)
{

	const char	*__function_name = "calc_timestamp";
	int		hh, mm, ss, yyyy, dd, MM;
	int		hhc = 0, mmc = 0, ssc = 0, yyyyc = 0, ddc = 0, MMc = 0;
	int		i, num;
	struct tm	tm;
	time_t		t;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	hh = mm = ss = yyyy = dd = MM = 0;

	for (i = 0; '\0' != format[i] && '\0' != line[i]; i++)
	{
		if (0 == isdigit(line[i]))
			continue;

		num = (int)line[i] - 48;

		switch ((char)format[i])
		{
			case 'h':
				hh = 10 * hh + num;
				hhc++;
				break;
			case 'm':
				mm = 10 * mm + num;
				mmc++;
				break;
			case 's':
				ss = 10 * ss + num;
				ssc++;
				break;
			case 'y':
				yyyy = 10 * yyyy + num;
				yyyyc++;
				break;
			case 'd':
				dd = 10 * dd + num;
				ddc++;
				break;
			case 'M':
				MM = 10 * MM + num;
				MMc++;
				break;
		}
	}

	zabbix_log(LOG_LEVEL_DEBUG, "%s() %02d:%02d:%02d %02d/%02d/%04d",
			__function_name, hh, mm, ss, MM, dd, yyyy);

	/* seconds can be ignored, no ssc here */
	if (0 != hhc && 0 != mmc && 0 != yyyyc && 0 != ddc && 0 != MMc)
	{
		tm.tm_sec = ss;
		tm.tm_min = mm;
		tm.tm_hour = hh;
		tm.tm_mday = dd;
		tm.tm_mon = MM - 1;
		tm.tm_year = yyyy - 1900;
		tm.tm_isdst = -1;

		if (0 < (t = mktime(&tm)))
			*timestamp = t;
	}

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s() timestamp:%d",	__function_name, *timestamp);
}

/******************************************************************************
 *                                                                            *
 * Function: process_mass_data                                                *
 *                                                                            *
 * Purpose: process new item value                                            *
 *                                                                            *
 * Parameters: sock         - [IN] descriptor of agent-server socket          *
 *                                 connection. NULL for proxy connection      *
 *             proxy_hostid - [IN] proxy identificator from database          *
 *             values       - [IN] array of incoming values                   *
 *             value_num    - [IN] number of elements in array                *
 *             processed    - [OUT] number of processed elements              *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
void	process_mass_data(zbx_sock_t *sock, zbx_uint64_t proxy_hostid,
		AGENT_VALUE *values, int value_num, int *processed)
{
	const char	*__function_name = "process_mass_data";
	AGENT_RESULT	agent;
	DC_ITEM		item;
	int		i;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	for (i = 0; i < value_num; i++)
	{
		if (SUCCEED != DCconfig_get_item_by_key(&item, proxy_hostid, values[i].host_name, values[i].key))
			continue;

		if (HOST_MAINTENANCE_STATUS_ON == item.host.maintenance_status &&
				MAINTENANCE_TYPE_NODATA == item.host.maintenance_type &&
				item.host.maintenance_from <= values[i].ts.sec)
			continue;

		if (ITEM_TYPE_INTERNAL == item.type || ITEM_TYPE_AGGREGATE == item.type || ITEM_TYPE_CALCULATED == item.type)
			continue;

		if (0 == proxy_hostid && ITEM_TYPE_TRAPPER != item.type && ITEM_TYPE_ZABBIX_ACTIVE != item.type)
			continue;

		if (ITEM_TYPE_TRAPPER == item.type && 0 == proxy_hostid &&
				FAIL == zbx_tcp_check_security(sock, item.trapper_hosts, 1))
		{
			zabbix_log(LOG_LEVEL_WARNING, "Process data failed: %s", zbx_tcp_strerror());
			continue;
		}

		if (0 == strcmp(values[i].value, "ZBX_NOTSUPPORTED"))
		{
			dc_add_history(item.itemid, item.value_type, item.flags, NULL, &values[i].ts,
					ITEM_STATUS_NOTSUPPORTED, values[i].value, 0, NULL, 0, 0, 0, 0);

			if (NULL != processed)
				(*processed)++;
		}
		else
		{
			init_result(&agent);

			if (SUCCEED == set_result_type(&agent, item.value_type,
					proxy_hostid ? ITEM_DATA_TYPE_DECIMAL : item.data_type, values[i].value))
			{
				if (ITEM_VALUE_TYPE_LOG == item.value_type)
					calc_timestamp(values[i].value, &values[i].timestamp, item.logtimefmt);

				if (NULL != values[i].source)
					zbx_replace_invalid_utf8(values[i].source);

				dc_add_history(item.itemid, item.value_type, item.flags, &agent, &values[i].ts,
						ITEM_STATUS_ACTIVE, NULL, values[i].timestamp, values[i].source,
						values[i].severity, values[i].logeventid, values[i].lastlogsize,
						values[i].mtime);

				if (NULL != processed)
					(*processed)++;
			}
			else if (ISSET_MSG(&agent))
			{
				zabbix_log(LOG_LEVEL_DEBUG, "Item [%s:%s] error: %s",
						item.host.host, item.key_orig, agent.msg);

				dc_add_history(item.itemid, item.value_type, item.flags, NULL, &values[i].ts,
						ITEM_STATUS_NOTSUPPORTED, agent.msg, 0, NULL, 0, 0, 0, 0);
			}
			else
				THIS_SHOULD_NEVER_HAPPEN; /* set_result_type() always sets MSG result if not SUCCEED */

			free_result(&agent);
		}
	}

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

static void	clean_agent_values(AGENT_VALUE *values, int value_num)
{
	int	i;

	for (i = 0; i < value_num; i++)
	{
		zbx_free(values[i].value);
		zbx_free(values[i].source);
	}
}

/******************************************************************************
 *                                                                            *
 * Function: process_hist_data                                                *
 *                                                                            *
 * Purpose: process values sent by proxies, active agents and senders         *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value:  SUCCEED - processed successfully                            *
 *                FAIL - an error occurred                                    *
 *                                                                            *
 * Author: Alexander Vladishev, Alexei Vladishev                              *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
int	process_hist_data(zbx_sock_t *sock, struct zbx_json_parse *jp,
		const zbx_uint64_t proxy_hostid, char *info, int max_info_size)
{
#define VALUES_MAX	256
	const char		*__function_name = "process_hist_data";
	struct zbx_json_parse	jp_data, jp_row;
	const char		*p;
	char			tmp[MAX_BUFFER_LEN];
	int			ret = FAIL, processed = 0, value_num = 0, total_num = 0;
	double			sec;
	zbx_timespec_t		ts, proxy_timediff;
	static AGENT_VALUE	*values = NULL, *av;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	sec = zbx_time();

	zbx_timespec(&ts);
	proxy_timediff.sec = 0;
	proxy_timediff.ns = 0;

	if (NULL == values)
		values = zbx_malloc(values, VALUES_MAX * sizeof(AGENT_VALUE));

	if (SUCCEED == zbx_json_value_by_name(jp, ZBX_PROTO_TAG_CLOCK, tmp, sizeof(tmp)))
	{
		proxy_timediff.sec = ts.sec - atoi(tmp);

		if (SUCCEED == zbx_json_value_by_name(jp, ZBX_PROTO_TAG_NS, tmp, sizeof(tmp)))
		{
			proxy_timediff.ns = ts.ns - atoi(tmp);

			if (proxy_timediff.ns < 0)
			{
				proxy_timediff.sec--;
				proxy_timediff.ns += 1000000000;
			}
		}
	}

	/* "data" tag lists the item keys */
	if (NULL == (p = zbx_json_pair_by_name(jp, ZBX_PROTO_TAG_DATA)))
		zabbix_log(LOG_LEVEL_WARNING, "cannot find \"data\" pair");
	else if (FAIL == zbx_json_brackets_open(p, &jp_data))
		zabbix_log(LOG_LEVEL_WARNING, "cannot process json request: %s", zbx_json_strerror());
	else
		ret = SUCCEED;

	p = NULL;
	while (SUCCEED == ret && NULL != (p = zbx_json_next(&jp_data, p)))	/* iterate the item key entries */
	{
		if (FAIL == (ret = zbx_json_brackets_open(p, &jp_row)))
			break;

		av = &values[value_num];

		memset(av, 0, sizeof(AGENT_VALUE));

		if (SUCCEED == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_CLOCK, tmp, sizeof(tmp)))
		{
			av->ts.sec = atoi(tmp) + proxy_timediff.sec;

			if (SUCCEED == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_NS, tmp, sizeof(tmp)))
			{
				av->ts.ns = atoi(tmp) + proxy_timediff.ns;

				if (av->ts.ns > 999999999)
				{
					av->ts.sec++;
					av->ts.ns -= 1000000000;
				}
			}
			else
				av->ts.ns = -1;
		}
		else
			zbx_timespec(&av->ts);

		if (FAIL == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_HOST, av->host_name, sizeof(av->host_name)))
			continue;

		if (FAIL == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_KEY, av->key, sizeof(av->key)))
			continue;

		if (FAIL == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_VALUE, tmp, sizeof(tmp)))
			continue;

		av->value = strdup(tmp);

		if (SUCCEED == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_LOGLASTSIZE, tmp, sizeof(tmp)))
			av->lastlogsize = atoi(tmp);

		if (SUCCEED == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_MTIME, tmp, sizeof(tmp)))
			av->mtime = atoi(tmp);

		if (SUCCEED == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_LOGTIMESTAMP, tmp, sizeof(tmp)))
			av->timestamp = atoi(tmp);

		if (SUCCEED == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_LOGSOURCE, tmp, sizeof(tmp)))
			av->source = strdup(tmp);

		if (SUCCEED == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_LOGSEVERITY, tmp, sizeof(tmp)))
			av->severity = atoi(tmp);

		if (SUCCEED == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_LOGEVENTID, tmp, sizeof(tmp)))
			av->logeventid = atoi(tmp);

		value_num++;

		if (VALUES_MAX == value_num)
		{
			process_mass_data(sock, proxy_hostid, values, value_num, &processed);

			clean_agent_values(values, value_num);
			total_num += value_num;
			value_num = 0;
		}
	}

	if (0 < value_num)
		process_mass_data(sock, proxy_hostid, values, value_num, &processed);

	clean_agent_values(values, value_num);
	total_num += value_num;

	if (NULL != info)
	{
		zbx_snprintf(info, max_info_size, "Processed %d Failed %d Total %d Seconds spent " ZBX_FS_DBL,
				processed, total_num - processed, total_num, zbx_time() - sec);
	}

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(ret));

	return ret;
}

/******************************************************************************
 *                                                                            *
 * Function: process_dhis_data                                                *
 *                                                                            *
 * Purpose: update discovery data, received from proxy                        *
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
void	process_dhis_data(struct zbx_json_parse *jp)
{
	const char		*__function_name = "process_dhis_data";
	DB_RESULT		result;
	DB_ROW			row;
	DB_DRULE		drule;
	DB_DCHECK		dcheck;
	DB_DHOST		dhost;
	zbx_uint64_t		last_druleid = 0;
	struct zbx_json_parse	jp_data, jp_row;
	int			port, status, ret;
	const char		*p = NULL;
	char			last_ip[INTERFACE_IP_LEN_MAX], ip[INTERFACE_IP_LEN_MAX], key_[ITEM_KEY_LEN_MAX],
				tmp[MAX_STRING_LEN], value[DSERVICE_VALUE_LEN_MAX], dns[INTERFACE_DNS_LEN_MAX];
	time_t			now, hosttime, itemtime;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	now = time(NULL);

	if (SUCCEED != (ret = zbx_json_value_by_name(jp, ZBX_PROTO_TAG_CLOCK, tmp, sizeof(tmp))))
		goto exit;

	if (SUCCEED != (ret = zbx_json_brackets_by_name(jp, ZBX_PROTO_TAG_DATA, &jp_data)))
		goto exit;

	hosttime = atoi(tmp);

	memset(&drule, 0, sizeof(drule));
	*last_ip = '\0';

	while (NULL != (p = zbx_json_next(&jp_data, p)))
	{
		if (FAIL == (ret = zbx_json_brackets_open(p, &jp_row)))
			break;

		memset(&dcheck, 0, sizeof(dcheck));
		*key_ = '\0';
		*value = '\0';
		*dns = '\0';
		port = 0;
		status = 0;
		dcheck.key_ = key_;

		if (FAIL == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_CLOCK, tmp, sizeof(tmp)))
			goto json_parse_error;
		itemtime = now - (hosttime - atoi(tmp));

		if (FAIL == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_DRULE, tmp, sizeof(tmp)))
			goto json_parse_error;
		ZBX_STR2UINT64(drule.druleid, tmp);

		if (FAIL == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_DCHECK, tmp, sizeof(tmp)))
			goto json_parse_error;
		ZBX_STR2UINT64(dcheck.dcheckid, tmp);

		if (FAIL == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_TYPE, tmp, sizeof(tmp)))
			goto json_parse_error;
		dcheck.type = atoi(tmp);

		if (FAIL == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_IP, ip, sizeof(ip)))
			goto json_parse_error;

		if (SUCCEED == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_PORT, tmp, sizeof(tmp)))
			port = atoi(tmp);

		zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_KEY, key_, sizeof(key_));
		zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_VALUE, value, sizeof(value));
		zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_DNS, dns, sizeof(dns));

		if (SUCCEED == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_STATUS, tmp, sizeof(tmp)))
			status = atoi(tmp);

		if (0 == last_druleid || drule.druleid != last_druleid)
		{
			result = DBselect(
					"select dcheckid"
					" from dchecks"
					" where druleid=" ZBX_FS_UI64
						" and uniq=1",
					drule.druleid);

			if (NULL != (row = DBfetch(result)))
			{
				ZBX_STR2UINT64(drule.unique_dcheckid, row[0]);
			}
			DBfree_result(result);

			last_druleid = drule.druleid;
		}

		if ('\0' == *last_ip || 0 != strcmp(ip, last_ip))
		{
			memset(&dhost, 0, sizeof(dhost));
			strscpy(last_ip, ip);
		}

		zabbix_log(LOG_LEVEL_DEBUG, "%s() druleid:" ZBX_FS_UI64 " dcheckid:" ZBX_FS_UI64 " unique_dcheckid:"
				ZBX_FS_UI64 " type:%d time:'%s %s' ip:'%s' dns:'%s' port:%d key:'%s' value:'%s'",
				__function_name, drule.druleid, dcheck.dcheckid, drule.unique_dcheckid, dcheck.type,
				zbx_date2str(itemtime), zbx_time2str(itemtime), ip, dns, port, dcheck.key_, value);

		DBbegin();
		if (dcheck.type == -1)
			discovery_update_host(&dhost, ip, status, itemtime);
		else
			discovery_update_service(&drule, &dcheck, &dhost, ip, dns, port, status, value, itemtime);
		DBcommit();

		continue;
json_parse_error:
		zabbix_log(LOG_LEVEL_WARNING, "Invalid discovery data. %s", zbx_json_strerror());
		zabbix_syslog("Invalid discovery data. %s", zbx_json_strerror());
	}
exit:
	if (SUCCEED != ret)
	{
		zabbix_log(LOG_LEVEL_WARNING, "Invalid discovery data. %s", zbx_json_strerror());
		zabbix_syslog("Invalid discovery data. %s", zbx_json_strerror());
	}

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(ret));
}

/******************************************************************************
 *                                                                            *
 * Function: process_areg_data                                                *
 *                                                                            *
 * Purpose: update auto-registration data, received from proxy                *
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
void	process_areg_data(struct zbx_json_parse *jp, zbx_uint64_t proxy_hostid)
{
	const char		*__function_name = "process_areg_data";

	struct zbx_json_parse	jp_data, jp_row;
	int			ret;
	const char		*p = NULL;
	time_t			now, hosttime, itemtime;
	char			host[HOST_HOST_LEN_MAX], ip[INTERFACE_IP_LEN_MAX],
				dns[INTERFACE_DNS_LEN_MAX], tmp[MAX_STRING_LEN];
	unsigned short		port;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	now = time(NULL);

	if (SUCCEED != (ret = zbx_json_value_by_name(jp, ZBX_PROTO_TAG_CLOCK, tmp, sizeof(tmp))))
		goto exit;

	if (SUCCEED != (ret = zbx_json_brackets_by_name(jp, ZBX_PROTO_TAG_DATA, &jp_data)))
		goto exit;

	hosttime = atoi(tmp);

	while (NULL != (p = zbx_json_next(&jp_data, p)))
	{
		if (FAIL == (ret = zbx_json_brackets_open(p, &jp_row)))
			break;

		if (FAIL == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_CLOCK, tmp, sizeof(tmp)))
			goto json_parse_error;
		itemtime = now - (hosttime - atoi(tmp));

		if (FAIL == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_HOST, host, sizeof(host)))
			goto json_parse_error;

		if (FAIL == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_IP, ip, sizeof(ip)))
			*ip = '\0';

		if (FAIL == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_DNS, dns, sizeof(dns)))
			*dns = '\0';

		if (FAIL == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_PORT, tmp, sizeof(tmp)))
			*tmp = '\0';

		if (FAIL == is_ushort(tmp, &port))
			port = ZBX_DEFAULT_AGENT_PORT;

		DBbegin();
		DBregister_host(proxy_hostid, host, ip, dns, port, itemtime);
		DBcommit();

		continue;
json_parse_error:
		zabbix_log(LOG_LEVEL_WARNING, "Invalid auto registration data. %s", zbx_json_strerror());
		zabbix_syslog("Invalid auto registration data. %s", zbx_json_strerror());
	}
exit:
	if (SUCCEED != ret)
	{
		zabbix_log(LOG_LEVEL_WARNING, "Invalid auto registration data. %s", zbx_json_strerror());
		zabbix_syslog("Invalid auto registration data. %s", zbx_json_strerror());
	}

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(ret));
}

/******************************************************************************
 *                                                                            *
 * Function: DBget_applications_by_itemid                                     *
 *                                                                            *
 * Purpose: retrieve applications for specified item                          *
 *                                                                            *
 * Parameters:  itemid - item identificator from database                     *
 *              appids - result buffer                                        *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static void	DBget_applications_by_itemid(zbx_uint64_t itemid,
		zbx_uint64_t **appids, int *appids_alloc, int *appids_num)
{
	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	applicationid;

	result = DBselect(
			"select applicationid"
			" from items_applications"
			" where itemid=" ZBX_FS_UI64,
			itemid);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(applicationid, row[0]);
		uint64_array_add(appids, appids_alloc, appids_num, applicationid, 4);
	}
	DBfree_result(result);
}

/******************************************************************************
 *                                                                            *
 * Function: DBlld_expand_trigger_expression                                  *
 *                                                                            *
 * Purpose: expand trigger expression                                         *
 *                                                                            *
 * Parameters: triggerid  - [IN] trigger identificator from database          *
 *             expression - [IN] trigger short expression                     *
 *             jp_row     - [IN] received discovery record                    *
 *                                                                            *
 * Return value: pointer to expanded expression                               *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static char	*DBlld_expand_trigger_expression(zbx_uint64_t triggerid, const char *expression,
		struct zbx_json_parse *jp_row)
{
	const char	*__function_name = "DBlld_expand_trigger_expression";

	DB_RESULT	result;
	DB_ROW		row;
	char		search[23], *expr = NULL, *old_expr,
			*key = NULL, *replace = NULL;
	size_t		sz_h, sz_k, sz_f, sz_p;
	int		replace_alloc = 1024, replace_offset;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() expression:'%s'", __function_name, expression);

	expr = zbx_strdup(expr, expression);
	replace = zbx_malloc(replace, replace_alloc);

	result = DBselect(
			"select f.functionid,h.host,i.key_,f.function,f.parameter,i.flags"
			" from functions f,items i,hosts h"
			" where f.itemid=i.itemid"
				" and i.hostid=h.hostid"
				" and f.triggerid=" ZBX_FS_UI64,
			triggerid);

	while (NULL != (row = DBfetch(result)))
	{
		key = zbx_strdup(key, row[2]);

		if (NULL != jp_row && 0 != (ZBX_FLAG_DISCOVERY_CHILD & (unsigned char)atoi(row[5])))
			substitute_discovery_macros(&key, jp_row);

		sz_h = strlen(row[1]);
		sz_k = strlen(key);
		sz_f = strlen(row[3]);
		sz_p = strlen(row[4]);
		replace_offset = 0;

		zbx_snprintf(search, sizeof(search), "{%s}", row[0]);
		zbx_snprintf_alloc(&replace, &replace_alloc, &replace_offset, sz_h + sz_k + sz_f + sz_p + 7,
				"{%s:%s.%s(%s)}", row[1], key, row[3], row[4]);

		old_expr = expr;
		expr = string_replace(old_expr, search, replace);
		zbx_free(old_expr);
	}
	DBfree_result(result);

	zbx_free(key);
	zbx_free(replace);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s() expr:'%s'", __function_name, expr);

	return expr;
}

/******************************************************************************
 *                                                                            *
 * Function: DBlld_compare_triggers                                           *
 *                                                                            *
 * Purpose: compare two triggers                                              *
 *                                                                            *
 * Parameters: triggerid1  - [IN] first trigger identificator from database   *
 *             expression1 - [IN] first trigger short expression              *
 *             triggerid2  - [IN] second trigger identificator from database  *
 *             expression2 - [IN] second trigger short expression             *
 *                                                                            *
 * Return value: SUCCEED - if triggers coincide                               *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int	DBlld_compare_triggers(zbx_uint64_t triggerid1, const char *expression1,
		zbx_uint64_t triggerid2, const char *expression2, struct zbx_json_parse *jp_row)
{
	const char	*__function_name = "DBlld_compare_triggers";

	char		*expr1, *expr2;
	int		res = SUCCEED;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	expr1 = DBlld_expand_trigger_expression(triggerid1, expression1, jp_row);
	expr2 = DBlld_expand_trigger_expression(triggerid2, expression2, NULL);

	if (0 != strcmp(expr1, expr2))
		res = FAIL;

	zbx_free(expr2);
	zbx_free(expr1);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

static int	DBlld_compare_trigger_items(zbx_uint64_t triggerid, struct zbx_json_parse *jp_row)
{
	const char	*__function_name = "DBlld_compare_trigger_items";
	DB_RESULT	result;
	DB_ROW		row;
	char		*old_key = NULL;
	int		res = FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	result = DBselect(
			"select id.key_,i.key_"
			" from functions f,items i,item_discovery id"
			" where f.itemid=i.itemid"
				" and i.itemid=id.itemid"
				" and f.triggerid=" ZBX_FS_UI64,
			triggerid);

	while (NULL != (row = DBfetch(result)))
	{
		old_key = zbx_strdup(old_key, row[0]);
		substitute_discovery_macros(&old_key, jp_row);

		if (0 == strcmp(old_key, row[1]))
		{
			res = SUCCEED;
			break;
		}
	}
	DBfree_result(result);

	zbx_free(old_key);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: DBlld_get_item                                                   *
 *                                                                            *
 * Purpose:                                                                   *
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
static int	DBlld_get_item(zbx_uint64_t hostid, const char *tmpl_key,
		struct zbx_json_parse *jp_row, zbx_uint64_t *itemid)
{
	const char	*__function_name = "DBlld_get_item";

	DB_RESULT	result;
	DB_ROW		row;
	char		*key = NULL, *key_esc;
	int		res = SUCCEED;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	key = zbx_strdup(key, tmpl_key);
	substitute_discovery_macros(&key, jp_row);
	key_esc = DBdyn_escape_string(key);

	result = DBselect(
			"select itemid"
			" from items"
			" where hostid=" ZBX_FS_UI64
				" and key_='%s'",
			hostid, key_esc);

	zbx_free(key_esc);
	zbx_free(key);

	if (NULL == (row = DBfetch(result)))
	{
		zabbix_log(LOG_LEVEL_DEBUG, "%s() Can't find item [%s] on the host",
				__function_name, key);
		res = FAIL;
	}
	else
		ZBX_STR2UINT64(*itemid, row[0]);

	DBfree_result(result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: DBlld_update_trigger                                             *
 *                                                                            *
 * Purpose: copy specified trigger to host                                    *
 *                                                                            *
 * Parameters: hostid - host identificator from database                      *
 *             parent_triggerid - trigger identificator from database         *
 *             description_proto - trigger description                        *
 *             expression - trigger expression                                *
 *             status - trigger status                                        *
 *             type - trigger type                                            *
 *             priority - trigger priority                                    *
 *             comments - trigger comments                                    *
 *             url - trigger url                                              *
 *                                                                            *
 * Return value: upon successful completion return SUCCEED                    *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int	DBlld_update_trigger(zbx_uint64_t hostid, zbx_uint64_t parent_triggerid, const char *description_proto,
		const char *expression, unsigned char status, unsigned char type, unsigned char priority,
		const char *comments, const char *url, struct zbx_json_parse *jp_row, char **error)
{
	const char	*__function_name = "DBlld_update_trigger";

	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	new_triggerid = 0, h_itemid, h_triggerid, functionid, triggerdiscoveryid;
	char		search[23], replace[23], *description = NULL,
			*description_esc, *comments_esc, *url_esc,
			*description_proto_esc, *error_esc;
	int		update_expression = 1, res = SUCCEED;
	char		*sql = NULL;
	int		sql_alloc = 1024, sql_offset = 0;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() jp_row:'%.*s'", __function_name,
			jp_row->end - jp_row->start + 1, jp_row->start);

	description = zbx_strdup(description, description_proto);
	substitute_discovery_macros(&description, jp_row);
	description_esc = DBdyn_escape_string(description);
	description_proto_esc = DBdyn_escape_string(description_proto);
	comments_esc = DBdyn_escape_string(comments);
	url_esc = DBdyn_escape_string(url);

	result = DBselect(
			"select distinct t.triggerid,t.expression"
			" from triggers t,trigger_discovery td"
			" where t.triggerid=td.triggerid"
				" and td.parent_triggerid=" ZBX_FS_UI64
				" and t.description='%s'",
			parent_triggerid, description_esc);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(h_triggerid, row[0]);

		if (SUCCEED != DBlld_compare_triggers(parent_triggerid, expression, h_triggerid, row[1], jp_row))
			continue;

		update_expression = 0;
		new_triggerid = h_triggerid;
		zabbix_log(LOG_LEVEL_DEBUG, "%s() new_triggerid:" ZBX_FS_UI64,
				__function_name, new_triggerid);
	}
	DBfree_result(result);

	if (0 == new_triggerid)
	{
		result = DBselect(
				"select distinct t.triggerid,td.name,t.description"
				" from triggers t,trigger_discovery td"
				" where t.triggerid=td.triggerid"
					" and td.parent_triggerid=" ZBX_FS_UI64,
				parent_triggerid);

		while (NULL != (row = DBfetch(result)))
		{
			char	*old_name = NULL;

			ZBX_STR2UINT64(h_triggerid, row[0]);

			old_name = zbx_strdup(old_name, row[1]);
			substitute_discovery_macros(&old_name, jp_row);

			if (0 == strcmp(old_name, row[2]))
			{
				if (SUCCEED == DBlld_compare_triggers(parent_triggerid, expression, h_triggerid, row[1], jp_row))
				{
					update_expression = 0;
					new_triggerid = h_triggerid;
				}
				else if (SUCCEED == DBlld_compare_trigger_items(h_triggerid, jp_row))
					new_triggerid = h_triggerid;
			}

			zbx_free(old_name);

			if (0 != new_triggerid)
			{
				zabbix_log(LOG_LEVEL_DEBUG, "%s() new_triggerid:" ZBX_FS_UI64,
						__function_name, new_triggerid);
				break;
			}
		}
		DBfree_result(result);
	}

	sql = zbx_malloc(sql, sql_alloc);

	sql_offset = 0;
	zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 192 + strlen(description_esc),
			"select distinct t.triggerid,t.expression"
			" from triggers t,functions f,items i"
			" where t.triggerid=f.triggerid"
				" and f.itemid=i.itemid"
				" and i.hostid=" ZBX_FS_UI64
				" and t.description='%s'",
			hostid, description_esc);

	if (0 != new_triggerid)
		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 64,
				" and t.triggerid<>" ZBX_FS_UI64,
				new_triggerid);

	result = DBselect("%s", sql);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(h_triggerid, row[0]);

		if (SUCCEED != DBlld_compare_triggers(parent_triggerid, expression, h_triggerid, row[1], jp_row))
			continue;

		*error = zbx_strdcatf(*error, "Can't %s trigger [%s]: trigger already exists\n",
				0 != new_triggerid ? "update" : "create", description);
		res = FAIL;
		break;
	}
	DBfree_result(result);

	if (FAIL == res)
		goto out;

	sql_offset = 0;
#ifdef HAVE_ORACLE
	zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 8, "begin\n");
#endif

	if (0 != new_triggerid)
	{
		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 192 +
				strlen(description_esc) +
				strlen(comments_esc) + strlen(url_esc),
				"update triggers"
				" set description='%s',"
					"priority=%d,"
					"comments='%s',"
					"url='%s',"
					"type=%d,"
					"flags=%d"
				" where triggerid=" ZBX_FS_UI64 ";\n",
				description_esc, (int)priority,
				comments_esc, url_esc, (int)type,
				ZBX_FLAG_DISCOVERY_CREATED, new_triggerid);

		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 256 + strlen(description_proto_esc),
				"update trigger_discovery"
				" set name='%s'"
				" where triggerid=" ZBX_FS_UI64
					" and parent_triggerid=" ZBX_FS_UI64 ";\n",
				description_proto_esc, new_triggerid, parent_triggerid);

		if (1 == update_expression)
		{
			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 192 + strlen(description_proto_esc),
					"delete from functions"
					" where triggerid=" ZBX_FS_UI64 ";\n",
					new_triggerid);
		}
	}
	else
	{
		new_triggerid = DBget_maxid("triggers");
		triggerdiscoveryid = DBget_maxid("trigger_discovery");

		error_esc = DBdyn_escape_string("Trigger just added. No status update so far.");

		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 256 + strlen(description_esc) +
				strlen(comments_esc) + strlen(url_esc) + strlen(error_esc),
				"insert into triggers"
					" (triggerid,description,priority,status,"
						"comments,url,type,value,value_flags,flags,error)"
					" values (" ZBX_FS_UI64 ",'%s',%d,%d,"
						"'%s','%s',%d,%d,%d,%d,'%s');\n",
					new_triggerid, description_esc, (int)priority,
					(int)status, comments_esc, url_esc, (int)type,
					TRIGGER_VALUE_FALSE, TRIGGER_VALUE_FLAG_UNKNOWN,
					ZBX_FLAG_DISCOVERY_CREATED, error_esc);

		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 256 + strlen(description_proto_esc),
				"insert into trigger_discovery"
					" (triggerdiscoveryid,triggerid,parent_triggerid,name)"
				" values"
					" (" ZBX_FS_UI64 "," ZBX_FS_UI64 "," ZBX_FS_UI64 ",'%s');\n",
				triggerdiscoveryid, new_triggerid, parent_triggerid, description_proto_esc);

		zbx_free(error_esc);
	}

	if (1 == update_expression)
	{
		char	*new_expression = NULL;

		new_expression = zbx_strdup(new_expression, expression);

		result = DBselect(
				"select f.itemid,f.functionid,f.function,f.parameter,i.key_,i.flags"
				" from functions f,items i"
				" where f.itemid=i.itemid"
					" and f.triggerid=" ZBX_FS_UI64,
				parent_triggerid);

		while (NULL != (row = DBfetch(result)))
		{
			char	*function_esc, *parameter_esc, *old_expression;

			if (0 != (ZBX_FLAG_DISCOVERY_CHILD & (unsigned char)atoi(row[5])))
			{
				if (FAIL == (res = DBlld_get_item(hostid, row[4], jp_row, &h_itemid)))
					break;
			}
			else
				ZBX_STR2UINT64(h_itemid, row[0]);

			functionid = DBget_maxid("functions");

			zbx_snprintf(search, sizeof(search), "{%s}", row[1]);
			zbx_snprintf(replace, sizeof(replace), "{" ZBX_FS_UI64 "}", functionid);

			function_esc = DBdyn_escape_string(row[2]);
			parameter_esc = DBdyn_escape_string(row[3]);

			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 192 +
					strlen(function_esc) + strlen(parameter_esc),
					"insert into functions"
					" (functionid,itemid,triggerid,function,parameter)"
					" values (" ZBX_FS_UI64 "," ZBX_FS_UI64 ","
						ZBX_FS_UI64 ",'%s','%s');\n",
					functionid, h_itemid, new_triggerid,
					function_esc, parameter_esc);

			old_expression = new_expression;
			new_expression = string_replace(old_expression, search, replace);

			zbx_free(old_expression);
			zbx_free(parameter_esc);
			zbx_free(function_esc);
		}
		DBfree_result(result);

		if (SUCCEED == res)
		{
			char	*expression_esc;

			expression_esc = DBdyn_escape_string_len(new_expression, TRIGGER_EXPRESSION_LEN);

			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 128 + strlen(expression_esc),
					"update triggers set expression='%s' where triggerid=" ZBX_FS_UI64 ";\n",
					expression_esc, new_triggerid);

			zbx_free(expression_esc);
		}

		zbx_free(new_expression);
	}

#ifdef HAVE_ORACLE
	zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 8, "end;\n");
#endif

	if (SUCCEED == res)
		DBexecute("%s", sql);
out:
	zbx_free(sql);
	zbx_free(url_esc);
	zbx_free(comments_esc);
	zbx_free(description_proto_esc);
	zbx_free(description_esc);
	zbx_free(description);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

static int	DBlld_check_record(struct zbx_json_parse *jp_row, const char *f_macro,
		const char *f_regexp, ZBX_REGEXP *regexps, int regexps_num)
{
	const char	*__function_name = "DBlld_check_record";

	char		*value = NULL;
	size_t		value_alloc = 0;
	int		res = SUCCEED;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() jp_row:'%.*s'", __function_name,
			jp_row->end - jp_row->start + 1, jp_row->start);

	if (NULL == f_macro || NULL == f_regexp)
		goto out;

	if (SUCCEED == zbx_json_value_by_name_dyn(jp_row, f_macro, &value, &value_alloc))
		res = regexp_match_ex(regexps, regexps_num, value, f_regexp, ZBX_CASE_SENSITIVE);

	zbx_free(value);
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: DBlld_update_triggers                                            *
 *                                                                            *
 * Purpose: add or update triggers for discovered items                       *
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
static void	DBlld_update_triggers(zbx_uint64_t hostid, zbx_uint64_t discovery_itemid,
		struct zbx_json_parse *jp_data, char **error, const char *f_macro,
		const char *f_regexp, ZBX_REGEXP *regexps, int regexps_num)
{
	const char		*__function_name = "DBlld_update_triggers";

	DB_RESULT		result;
	DB_ROW			row;
	struct zbx_json_parse	jp_row;
	const char		*p;
	zbx_uint64_t		triggerid;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	result = DBselect(
			"select distinct t.triggerid,t.description,t.expression,"
				"t.status,t.type,t.priority,t.comments,t.url"
			" from triggers t,functions f,items i,item_discovery d"
			" where f.triggerid=t.triggerid"
				" and f.itemid=i.itemid"
				" and i.itemid=d.itemid"
				" and d.parent_itemid=" ZBX_FS_UI64,
			discovery_itemid);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(triggerid, row[0]);

		p = NULL;
/* {"net.if.discovery":[{"{#IFNAME}":"eth0"},{"{#IFNAME}":"lo"},...]}
 *                      ^
 */		while (NULL != (p = zbx_json_next(jp_data, p)))
		{
/* {"net.if.discovery":[{"{#IFNAME}":"eth0"},{"{#IFNAME}":"lo"},...]}
 *                      ^------------------^
 */			if (FAIL == zbx_json_brackets_open(p, &jp_row))
				continue;

			if (SUCCEED != DBlld_check_record(&jp_row, f_macro, f_regexp, regexps, regexps_num))
				continue;

			DBlld_update_trigger(hostid, triggerid,
					row[1],				/* description */
					row[2],				/* expression */
					(unsigned char)atoi(row[3]),	/* status */
					(unsigned char)atoi(row[4]),	/* type */
					(unsigned char)atoi(row[5]),	/* priority */
					row[6],				/* comments */
					row[7],				/* url */
					&jp_row,
					error);
		}
	}
	DBfree_result(result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

/******************************************************************************
 *                                                                            *
 * Function: DBlld_update_item                                                *
 *                                                                            *
 * Purpose: add or update discovered item                                     *
 *                                                                            *
 * Parameters: parent_itemid - discovery rule identificator from database     *
 *             key - new key descriptor with substituted macros               *
 *                                                                            *
 * Return value: upon successful completion return SUCCEED                    *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int	DBlld_update_item(zbx_uint64_t hostid, zbx_uint64_t parent_itemid, const char *name_proto,
		const char *key_proto, unsigned char type, unsigned char value_type, unsigned char data_type,
		int delay, const char *delay_flex_esc, int history, int trends, unsigned char status,
		const char *trapper_hosts_esc, const char *units_esc, int multiplier, int delta,
		const char *formula_esc, const char *logtimefmt_esc, zbx_uint64_t valuemapid,
		const char *params_esc, const char *ipmi_sensor_esc, const char *snmp_community_esc,
		const char *snmp_oid_proto, const char *port_esc, const char *snmpv3_securityname_esc,
		unsigned char snmpv3_securitylevel, const char *snmpv3_authpassphrase_esc,
		const char *snmpv3_privpassphrase_esc, unsigned char authtype, const char *username_esc,
		const char *password_esc, const char *publickey_esc, const char *privatekey_esc,
		const char *description_esc, zbx_uint64_t interfaceid, struct zbx_json_parse *jp_row, char **error)
{
	const char	*__function_name = "DBlld_update_item";

	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	new_itemid = 0, itemdiscoveryid, itemappid;
	char		*key = NULL, *key_esc, *key_proto_esc,
			*name = NULL, *name_esc,
			*snmp_oid = NULL, *snmp_oid_esc,
			*sql = NULL;
	int		sql_offset = 0, sql_alloc = 16384,
			i, res = SUCCEED;
	zbx_uint64_t	*appids = NULL, *rmids = NULL;
	int		appids_alloc = 0, appids_num,
			rmids_alloc = 0, rmids_num;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() jp_row:'%.*s'", __function_name,
			jp_row->end - jp_row->start + 1, jp_row->start);

	key = zbx_strdup(key, key_proto);
	substitute_discovery_macros(&key, jp_row);
	key_esc = DBdyn_escape_string(key);
	key_proto_esc = DBdyn_escape_string(key_proto);

	name = zbx_strdup(name, name_proto);
	substitute_discovery_macros(&name, jp_row);
	name_esc = DBdyn_escape_string(name);

	snmp_oid = zbx_strdup(snmp_oid, snmp_oid_proto);
	substitute_discovery_macros(&snmp_oid, jp_row);
	snmp_oid_esc = DBdyn_escape_string(snmp_oid);

	result = DBselect(
			"select distinct i.itemid"
			" from items i,item_discovery id"
			" where i.itemid=id.itemid"
				" and id.parent_itemid=" ZBX_FS_UI64
				" and i.key_='%s'",
			parent_itemid, key_esc);

	if (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(new_itemid, row[0]);
		zabbix_log(LOG_LEVEL_DEBUG, "%s() new_itemid:" ZBX_FS_UI64,
				__function_name, new_itemid);
	}
	DBfree_result(result);

	if (0 == new_itemid)
	{
		result = DBselect(
				"select distinct i.itemid,id.key_,i.key_"
				" from items i,item_discovery id"
				" where i.itemid=id.itemid"
					" and id.parent_itemid=" ZBX_FS_UI64,
				parent_itemid);

		while (NULL != (row = DBfetch(result)))
		{
			char	*old_key = NULL;

			old_key = zbx_strdup(old_key, row[1]);
			substitute_discovery_macros(&old_key, jp_row);

			if (0 == strcmp(old_key, row[2]))
				ZBX_STR2UINT64(new_itemid, row[0]);

			zbx_free(old_key);

			if (0 != new_itemid)
			{
				zabbix_log(LOG_LEVEL_DEBUG, "%s() new_itemid:" ZBX_FS_UI64,
						__function_name, new_itemid);
				break;
			}
		}
		DBfree_result(result);
	}

	sql = zbx_malloc(sql, sql_alloc);

	sql_offset = 0;
	zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 256,
			"select itemid"
			" from items"
			" where hostid=" ZBX_FS_UI64
				" and key_='%s'",
			hostid, key_esc);

	if (0 != new_itemid)
		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 64,
				" and itemid<>" ZBX_FS_UI64,
				new_itemid);

	result = DBselect("%s", sql);

	if (NULL != (row = DBfetch(result)))
	{
		*error = zbx_strdcatf(*error, "Can't %s item [%s]: item already exists\n",
				0 != new_itemid ? "update" : "create", key);
		res = FAIL;
	}
	DBfree_result(result);

	if (FAIL == res)
		goto out;

	appids_num = rmids_num = 0;
	DBget_applications_by_itemid(parent_itemid, &appids, &appids_alloc, &appids_num);

	sql_offset = 0;
#ifdef HAVE_ORACLE
	zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 8, "begin\n");
#endif

	if (0 == new_itemid)
	{
		new_itemid = DBget_maxid("items");
		itemdiscoveryid = DBget_maxid("item_discovery");

		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 8192,
				"insert into items"
					" (itemid,name,key_,hostid,type,value_type,data_type,"
					"delay,delay_flex,history,trends,status,trapper_hosts,units,"
					"multiplier,delta,formula,logtimefmt,valuemapid,params,"
					"ipmi_sensor,snmp_community,snmp_oid,port,"
					"snmpv3_securityname,snmpv3_securitylevel,"
					"snmpv3_authpassphrase,snmpv3_privpassphrase,"
					"authtype,username,password,publickey,privatekey,"
					"description,interfaceid,flags)"
				" values"
					" (" ZBX_FS_UI64 ",'%s','%s'," ZBX_FS_UI64 ",%d,%d,%d,"
					"%d,'%s',%d,%d,%d,'%s','%s',%d,%d,'%s','%s',%s,'%s','%s',"
					"'%s','%s','%s','%s',%d,'%s','%s',%d,'%s','%s','%s',"
					"'%s','%s'," ZBX_FS_UI64 ",%d);\n",
				new_itemid, name_esc, key_esc, hostid, (int)type, (int)value_type, (int)data_type,
				delay, delay_flex_esc, history, trends, (int)status, trapper_hosts_esc, units_esc,
				multiplier, delta, formula_esc, logtimefmt_esc, DBsql_id_ins(valuemapid), params_esc,
				ipmi_sensor_esc, snmp_community_esc, snmp_oid_esc, port_esc,
				snmpv3_securityname_esc, (int)snmpv3_securitylevel, snmpv3_authpassphrase_esc,
				snmpv3_privpassphrase_esc, (int)authtype, username_esc, password_esc, publickey_esc,
				privatekey_esc, description_esc, interfaceid, ZBX_FLAG_DISCOVERY_CREATED);

		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 256 + strlen(key_proto_esc),
				"insert into item_discovery"
					" (itemdiscoveryid,itemid,parent_itemid,key_)"
				" values"
					" (" ZBX_FS_UI64 "," ZBX_FS_UI64 "," ZBX_FS_UI64 ",'%s');\n",
				itemdiscoveryid, new_itemid, parent_itemid, key_proto_esc);
	}
	else
	{
		DBget_applications_by_itemid(new_itemid, &rmids, &rmids_alloc, &rmids_num);

		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 8192,
				"update items"
					" set name='%s',"
					"key_='%s',"
					"type=%d,"
					"value_type=%d,"
					"data_type=%d,"
					"delay=%d,"
					"delay_flex='%s',"
					"history=%d,"
					"trends=%d,"
					"trapper_hosts='%s',"
					"units='%s',"
					"multiplier=%d,"
					"delta=%d,"
					"formula='%s',"
					"logtimefmt='%s',"
					"valuemapid=%s,"
					"params='%s',"
					"ipmi_sensor='%s',"
					"snmp_community='%s',"
					"snmp_oid='%s',"
					"port='%s',"
					"snmpv3_securityname='%s',"
					"snmpv3_securitylevel=%d,"
					"snmpv3_authpassphrase='%s',"
					"snmpv3_privpassphrase='%s',"
					"authtype=%d,"
					"username='%s',"
					"password='%s',"
					"publickey='%s',"
					"privatekey='%s',"
					"description='%s',"
					"interfaceid=" ZBX_FS_UI64 ","
					"flags=%d"
				" where itemid=" ZBX_FS_UI64 ";\n",
				name_esc, key_esc, (int)type, (int)value_type, (int)data_type,
				delay, delay_flex_esc, history, trends, trapper_hosts_esc, units_esc,
				multiplier, delta, formula_esc, logtimefmt_esc, DBsql_id_ins(valuemapid), params_esc,
				ipmi_sensor_esc, snmp_community_esc, snmp_oid_esc, port_esc,
				snmpv3_securityname_esc, (int)snmpv3_securitylevel, snmpv3_authpassphrase_esc,
				snmpv3_privpassphrase_esc, (int)authtype, username_esc, password_esc, publickey_esc,
				privatekey_esc, description_esc, interfaceid, ZBX_FLAG_DISCOVERY_CREATED, new_itemid);

		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 256 + strlen(key_proto_esc),
				"update item_discovery"
				" set key_='%s'"
				" where itemid=" ZBX_FS_UI64
					" and parent_itemid=" ZBX_FS_UI64 ";\n",
				key_proto_esc, new_itemid, parent_itemid);

		uint64_array_remove_both(appids, &appids_num, rmids, &rmids_num);

		if (0 != rmids_num)
		{
			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 160,
					"delete from items_applications"
					" where itemid=" ZBX_FS_UI64
						" and",
					new_itemid);
			DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset,
				"applicationid", rmids, rmids_num);
			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 3, ";\n");
		}
	}

	if (0 != appids_num)
	{
		itemappid = DBget_maxid_num("items_applications", appids_num);

		for (i = 0; i < appids_num; i++)
		{
			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 160,
					"insert into items_applications"
					" (itemappid,itemid,applicationid)"
					" values"
					" (" ZBX_FS_UI64 "," ZBX_FS_UI64 "," ZBX_FS_UI64 ");\n",
					itemappid++, new_itemid, appids[i]);
		}
	}

	zbx_free(rmids);
	zbx_free(appids);

#ifdef HAVE_ORACLE
	zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 8, "end;\n");
#endif

	if (sql_offset > 16)	/* In ORACLE always present begin..end; */
		DBexecute("%s", sql);
out:
	zbx_free(sql);
	zbx_free(snmp_oid_esc);
	zbx_free(snmp_oid);
	zbx_free(name_esc);
	zbx_free(name);
	zbx_free(key_proto_esc);
	zbx_free(key_esc);
	zbx_free(key);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: DBlld_update_items                                               *
 *                                                                            *
 * Purpose: add or update items for discovered items                          *
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
static void	DBlld_update_items(zbx_uint64_t hostid, zbx_uint64_t discovery_itemid,
		struct zbx_json_parse *jp_data, char **error, const char *f_macro,
		const char *f_regexp, ZBX_REGEXP *regexps, int regexps_num,
		const char *snmp_community_esc, const char *port_esc,
		const char *snmpv3_securityname_esc, unsigned char snmpv3_securitylevel,
		const char *snmpv3_authpassphrase_esc, const char *snmpv3_privpassphrase_esc,
		zbx_uint64_t interfaceid)
{
	const char		*__function_name = "DBlld_update_items";

	struct zbx_json_parse	jp_row;
	const char		*p;
	DB_RESULT		result;
	DB_ROW			row;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	result = DBselect(
			"select i.itemid,i.name,i.key_,i.lastvalue,i.type,"
				"i.value_type,i.data_type,i.delay,i.delay_flex,"
				"i.history,i.trends,i.status,i.trapper_hosts,"
				"i.units,i.multiplier,i.delta,i.formula,"
				"i.logtimefmt,i.valuemapid,i.params,"
				"i.ipmi_sensor,i.snmp_oid,"
				"i.authtype,i.username,"
				"i.password,i.publickey,i.privatekey,"
				"i.description"
			" from items i,item_discovery d"
			" where i.itemid=d.itemid"
				" and i.hostid=" ZBX_FS_UI64
				" and d.parent_itemid=" ZBX_FS_UI64,
			hostid, discovery_itemid);

	while (NULL != (row = DBfetch(result)))
	{
		zbx_uint64_t	itemid, valuemapid;
		char		*delay_flex_esc, *trapper_hosts_esc, *units_esc, *formula_esc,
				*logtimefmt_esc, *params_esc, *ipmi_sensor_esc, *username_esc,
				*password_esc, *publickey_esc, *privatekey_esc, *description_esc;

		ZBX_STR2UINT64(itemid, row[0]);
		ZBX_DBROW2UINT64(valuemapid, row[18]);

		delay_flex_esc		= DBdyn_escape_string(row[8]);
		trapper_hosts_esc	= DBdyn_escape_string(row[12]);
		units_esc		= DBdyn_escape_string(row[13]);
		formula_esc		= DBdyn_escape_string(row[16]);
		logtimefmt_esc		= DBdyn_escape_string(row[17]);
		params_esc		= DBdyn_escape_string(row[19]);
		ipmi_sensor_esc		= DBdyn_escape_string(row[20]);
		username_esc		= DBdyn_escape_string(row[23]);
		password_esc		= DBdyn_escape_string(row[24]);
		publickey_esc		= DBdyn_escape_string(row[25]);
		privatekey_esc		= DBdyn_escape_string(row[26]);
		description_esc		= DBdyn_escape_string(row[27]);

		p = NULL;
/* {"net.if.discovery":[{"{#IFNAME}":"eth0"},{"{#IFNAME}":"lo"},...]}
 *                      ^
 */		while (NULL != (p = zbx_json_next(jp_data, p)))
		{
/* {"net.if.discovery":[{"{#IFNAME}":"eth0"},{"{#IFNAME}":"lo"},...]}
 *                      ^------------------^
 */			if (FAIL == zbx_json_brackets_open(p, &jp_row))
				continue;

			if (SUCCEED != DBlld_check_record(&jp_row, f_macro, f_regexp, regexps, regexps_num))
				continue;

			DBlld_update_item(hostid, itemid,
					row[1],				/* name */
					row[2],				/* key */
					(unsigned char)atoi(row[4]),	/* type */
					(unsigned char)atoi(row[5]),	/* value_type */
					(unsigned char)atoi(row[6]),	/* data_type */
					atoi(row[7]),			/* delay */
					delay_flex_esc,
					atoi(row[9]),			/* history */
					atoi(row[10]),			/* trends */
					(unsigned char)atoi(row[11]),	/* status */
					trapper_hosts_esc,
					units_esc,
					atoi(row[14]),			/* multiplier */
					atoi(row[15]),			/* delta */
					formula_esc,
					logtimefmt_esc,
					valuemapid,
					params_esc,
					ipmi_sensor_esc,
					snmp_community_esc,
					row[21],			/* snmp_oid */
					port_esc,
					snmpv3_securityname_esc,
					snmpv3_securitylevel,
					snmpv3_authpassphrase_esc,
					snmpv3_privpassphrase_esc,
					(unsigned char)atoi(row[22]),	/* authtype */
					username_esc,
					password_esc,
					publickey_esc,
					privatekey_esc,
					description_esc,
					interfaceid,
					&jp_row,
					error);
		}

		zbx_free(description_esc);
		zbx_free(privatekey_esc);
		zbx_free(publickey_esc);
		zbx_free(password_esc);
		zbx_free(username_esc);
		zbx_free(ipmi_sensor_esc);
		zbx_free(params_esc);
		zbx_free(logtimefmt_esc);
		zbx_free(formula_esc);
		zbx_free(units_esc);
		zbx_free(trapper_hosts_esc);
		zbx_free(delay_flex_esc);
	}
	DBfree_result(result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

/******************************************************************************
 *                                                                            *
 * Function: DBlld_update_graph                                               *
 *                                                                            *
 * Purpose: add or update graph                                               *
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
static int	DBlld_update_graph(zbx_uint64_t hostid, zbx_uint64_t parent_graphid,
		const char *name_proto, int width, int height, double yaxismin,
		double yaxismax, unsigned char show_work_period,
		unsigned char show_triggers, unsigned char graphtype,
		unsigned char show_legend, unsigned char show_3d,
		double percent_left, double percent_right,
		unsigned char ymin_type, unsigned char ymax_type,
		zbx_uint64_t ymin_itemid, zbx_uint64_t ymax_itemid,
		struct zbx_json_parse *jp_row, char **error)
{
	const char		*__function_name = "DBlld_update_graph";

	char			*sql = NULL, *key = NULL, *color_esc;
	int			sql_alloc = 1024, sql_offset, i;
	ZBX_GRAPH_ITEMS		*gitems = NULL, *chd_gitems = NULL;
	int			gitems_alloc = 0, gitems_num = 0,
				chd_gitems_alloc = 0, chd_gitems_num = 0,
				res = SUCCEED;
	DB_RESULT		result;
	DB_ROW			row;
	zbx_uint64_t		new_graphid = 0, graphdiscoveryid;
	char			*name = NULL, *name_esc, *name_proto_esc;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() jp_row:'%.*s'", __function_name,
			jp_row->end - jp_row->start + 1, jp_row->start);

	name = zbx_strdup(name, name_proto);
	substitute_discovery_macros(&name, jp_row);
	name_esc = DBdyn_escape_string(name);
	name_proto_esc = DBdyn_escape_string(name_proto);

	result = DBselect(
			"select distinct g.graphid"
			" from graphs g,graph_discovery gd"
			" where g.graphid=gd.graphid"
				" and gd.parent_graphid=" ZBX_FS_UI64
				" and g.name='%s'",
			parent_graphid, name_esc);

	if (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(new_graphid, row[0]);
		zabbix_log(LOG_LEVEL_DEBUG, "%s() new_graphid:" ZBX_FS_UI64,
				__function_name, new_graphid);
	}
	DBfree_result(result);

	if (0 == new_graphid)
	{
		result = DBselect(
				"select distinct g.graphid,gd.name,g.name"
				" from graphs g,graph_discovery gd"
				" where g.graphid=gd.graphid"
					" and gd.parent_graphid=" ZBX_FS_UI64,
				parent_graphid);

		while (NULL != (row = DBfetch(result)))
		{
			char	*old_name = NULL;

			old_name = zbx_strdup(old_name, row[1]);
			substitute_discovery_macros(&old_name, jp_row);

			if (0 == strcmp(old_name, row[2]))
				ZBX_STR2UINT64(new_graphid, row[0]);

			zbx_free(old_name);

			if (0 != new_graphid)
			{
				zabbix_log(LOG_LEVEL_DEBUG, "%s() new_graphid:" ZBX_FS_UI64,
						__function_name, new_graphid);
				break;
			}
		}
		DBfree_result(result);
	}

	sql = zbx_malloc(sql, sql_alloc);

	sql_offset = 0;
	zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 256,
			"select distinct g.graphid"
			" from graphs g,graphs_items gi,items i"
			" where g.graphid=gi.graphid"
				" and gi.itemid=i.itemid"
				" and i.hostid=" ZBX_FS_UI64
				" and g.name='%s'",
			hostid, name_esc);

	if (0 != new_graphid)
		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 64,
				" and g.graphid<>" ZBX_FS_UI64,
				new_graphid);

	result = DBselect("%s", sql);

	if (NULL != (row = DBfetch(result)))
	{
		*error = zbx_strdcatf(*error, "Can't %s graph [%s]: graph already exists\n",
				0 != new_graphid ? "update" : "create", name);
		res = FAIL;
	}
	DBfree_result(result);

	if (FAIL == res)
		goto out;

	sql_offset = 0;
	zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 256,
			"select 0,i.itemid,i.key_,gi.drawtype,gi.sortorder,gi.color,"
				"gi.yaxisside,gi.calc_fnc,gi.type,gi.periods_cnt,i.flags"
			" from graphs_items gi,items i"
			" where gi.itemid=i.itemid"
				" and gi.graphid=" ZBX_FS_UI64,
			parent_graphid);

	DBget_graphitems(sql, &gitems, &gitems_alloc, &gitems_num);

	for (i = 0; i < gitems_num; i++)
	{
		if (0 != (ZBX_FLAG_DISCOVERY_CHILD & gitems[i].flags))
			if (FAIL == (res = DBlld_get_item(hostid, gitems[i].key, jp_row, &gitems[i].itemid)))
				break;
	}

	if (FAIL == res)
		goto out;

	qsort(gitems, gitems_num, sizeof(ZBX_GRAPH_ITEMS), ZBX_DEFAULT_UINT64_COMPARE_FUNC);

	if (0 != new_graphid)
	{
		size_t		sz;
		ZBX_GRAPH_ITEMS	*gitem;

		sql_offset = 0;
		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 256,
				"select gi.gitemid,i.itemid,i.key_,gi.drawtype,gi.sortorder,gi.color,"
					"gi.yaxisside,gi.calc_fnc,gi.type,gi.periods_cnt,i.flags"
				" from graphs_items gi,items i"
				" where gi.itemid=i.itemid"
					" and gi.graphid=" ZBX_FS_UI64
				" order by i.itemid",
				new_graphid);

		DBget_graphitems(sql, &chd_gitems, &chd_gitems_alloc, &chd_gitems_num);

		for (i = chd_gitems_num - 1; i >= 0; i--)
		{
			if (NULL != (gitem = bsearch(&chd_gitems[i].itemid, gitems, gitems_num, sizeof(ZBX_GRAPH_ITEMS), ZBX_DEFAULT_UINT64_COMPARE_FUNC)))
			{
				gitem->gitemid = chd_gitems[i].gitemid;

				chd_gitems_num--;

				if (0 != (sz = (chd_gitems_num - i) * sizeof(ZBX_GRAPH_ITEMS)))
					memmove(&chd_gitems[i], &chd_gitems[i + 1], sz);
			}
		}
	}

	sql_offset = 0;
#ifdef HAVE_ORACLE
	zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 8, "begin\n");
#endif

	if (0 != new_graphid)
	{
		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 1024,
				"update graphs"
				" set name='%s',"
					"width=%d,"
					"height=%d,"
					"yaxismin=" ZBX_FS_DBL ","
					"yaxismax=" ZBX_FS_DBL ","
					"show_work_period=%d,"
					"show_triggers=%d,"
					"graphtype=%d,"
					"show_legend=%d,"
					"show_3d=%d,"
					"percent_left=" ZBX_FS_DBL ","
					"percent_right=" ZBX_FS_DBL ","
					"ymin_type=%d,"
					"ymax_type=%d,"
					"ymin_itemid=%s,"
					"ymax_itemid=%s,"
					"flags=%d"
				" where graphid=" ZBX_FS_UI64 ";\n",
				name_esc, width, height, yaxismin, yaxismax,
				(int)show_work_period, (int)show_triggers,
				(int)graphtype, (int)show_legend, (int)show_3d,
				percent_left, percent_right, (int)ymin_type, (int)ymax_type,
				DBsql_id_ins(ymin_itemid), DBsql_id_ins(ymax_itemid),
				ZBX_FLAG_DISCOVERY_CREATED, new_graphid);

		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 256 + strlen(name_proto_esc),
				"update graph_discovery"
				" set name='%s'"
				" where graphid=" ZBX_FS_UI64
					" and parent_graphid=" ZBX_FS_UI64 ";\n",
				name_proto_esc, new_graphid, parent_graphid);
	}
	else
	{
		new_graphid = DBget_maxid("graphs");
		graphdiscoveryid = DBget_maxid("graph_discovery");

		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 1024,
				"insert into graphs"
					" (graphid,name,width,height,yaxismin,yaxismax,show_work_period,"
					"show_triggers,graphtype,show_legend,show_3d,percent_left,"
					"percent_right,ymin_type,ymax_type,ymin_itemid,ymax_itemid,flags)"
				" values"
					" (" ZBX_FS_UI64 ",'%s',%d,%d," ZBX_FS_DBL ","
					ZBX_FS_DBL ",%d,%d,%d,%d,%d," ZBX_FS_DBL ","
					ZBX_FS_DBL ",%d,%d,%s,%s,%d);\n",
				new_graphid, name_esc, width, height, yaxismin, yaxismax,
				(int)show_work_period, (int)show_triggers,
				(int)graphtype, (int)show_legend, (int)show_3d,
				percent_left, percent_right, (int)ymin_type, (int)ymax_type,
				DBsql_id_ins(ymin_itemid), DBsql_id_ins(ymax_itemid), ZBX_FLAG_DISCOVERY_CREATED);

		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 256 + strlen(name_proto_esc),
				"insert into graph_discovery"
					" (graphdiscoveryid,graphid,parent_graphid,name)"
				" values"
					" (" ZBX_FS_UI64 "," ZBX_FS_UI64 "," ZBX_FS_UI64 ",'%s');\n",
				graphdiscoveryid, new_graphid, parent_graphid, name_proto_esc);
	}

	for (i = 0; i < gitems_num; i++)
	{
		color_esc = DBdyn_escape_string(gitems[i].color);

		if (0 != gitems[i].gitemid)
		{
			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 256,
					"update graphs_items"
					" set drawtype=%d,"
						"sortorder=%d,"
						"color='%s',"
						"yaxisside=%d,"
						"calc_fnc=%d,"
						"type=%d,"
						"periods_cnt=%d"
					" where gitemid=" ZBX_FS_UI64 ";\n",
					gitems[i].drawtype,
					gitems[i].sortorder,
					color_esc,
					gitems[i].yaxisside,
					gitems[i].calc_fnc,
					gitems[i].type,
					gitems[i].periods_cnt,
					gitems[i].gitemid);
		}
		else
		{
			gitems[i].gitemid = DBget_maxid("graphs_items");

			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 256,
					"insert into graphs_items"
						" (gitemid,graphid,itemid,drawtype,sortorder,color,"
						"yaxisside,calc_fnc,type,periods_cnt)"
					" values"
						" (" ZBX_FS_UI64 "," ZBX_FS_UI64 "," ZBX_FS_UI64
						",%d,%d,'%s',%d,%d,%d,%d);\n",
					gitems[i].gitemid, new_graphid, gitems[i].itemid,
					gitems[i].drawtype, gitems[i].sortorder, color_esc,
					gitems[i].yaxisside, gitems[i].calc_fnc, gitems[i].type,
					gitems[i].periods_cnt);
		}

		zbx_free(color_esc);
	}

	for (i = 0; i < chd_gitems_num; i++)
	{
		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 256,
				"delete from graphs_items"
				" where gitemid=" ZBX_FS_UI64 ";\n",
				chd_gitems[i].gitemid);
	}

#ifdef HAVE_ORACLE
	zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, 8, "end;\n");
#endif
	DBexecute("%s", sql);
out:
	zbx_free(key);
	zbx_free(sql);
	zbx_free(gitems);
	zbx_free(chd_gitems);
	zbx_free(name_proto_esc);
	zbx_free(name_esc);
	zbx_free(name);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: DBlld_update_graphs                                              *
 *                                                                            *
 * Purpose: add or update graphs for discovery item                           *
 *                                                                            *
 * Parameters: hostid  - [IN] host identificator from database                *
 *             agent   - [IN] discovery item identificator from database      *
 *             jp_data - [IN] received data                                   *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static void	DBlld_update_graphs(zbx_uint64_t hostid, zbx_uint64_t discovery_itemid,
		struct zbx_json_parse *jp_data, char **error, const char *f_macro,
		const char *f_regexp, ZBX_REGEXP *regexps, int regexps_num)
{
	const char		*__function_name = "DBlld_update_graphs";

	DB_RESULT		result;
	DB_ROW			row;
	struct zbx_json_parse	jp_row;
	const char		*p;
	zbx_uint64_t		graphid, ymin_itemid, ymax_itemid;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	result = DBselect(
			"select distinct g.graphid,g.name,g.width,g.height,g.yaxismin,"
				"g.yaxismax,g.show_work_period,g.show_triggers,"
				"g.graphtype,g.show_legend,g.show_3d,g.percent_left,"
				"g.percent_right,g.ymin_type,g.ymax_type,g.ymin_itemid,"
				"g.ymax_itemid"
			" from graphs g,graphs_items gi,items i,item_discovery d"
			" where g.graphid=gi.graphid"
				" and gi.itemid=i.itemid"
				" and i.itemid=d.itemid"
				" and d.parent_itemid=" ZBX_FS_UI64,
			discovery_itemid);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(graphid, row[0]);
		ZBX_DBROW2UINT64(ymin_itemid, row[15]);
		ZBX_DBROW2UINT64(ymax_itemid, row[16]);

		p = NULL;
/* {"net.if.discovery":[{"{#IFNAME}":"eth0"},{"{#IFNAME}":"lo"},...]}
 *                      ^
 */		while (NULL != (p = zbx_json_next(jp_data, p)))
		{
/* {"net.if.discovery":[{"{#IFNAME}":"eth0"},{"{#IFNAME}":"lo"},...]}
 *                      ^------------------^
 */			if (FAIL == zbx_json_brackets_open(p, &jp_row))
				continue;

			if (SUCCEED != DBlld_check_record(&jp_row, f_macro, f_regexp, regexps, regexps_num))
				continue;

			DBlld_update_graph(hostid, graphid,
					row[1],				/* name */
					atoi(row[2]),			/* width */
					atoi(row[3]),			/* height */
					atof(row[4]),			/* yaxismin */
					atof(row[5]),			/* yaxismax */
					(unsigned char)atoi(row[6]),	/* show_work_period */
					(unsigned char)atoi(row[7]),	/* show_triggers */
					(unsigned char)atoi(row[8]),	/* graphtype */
					(unsigned char)atoi(row[9]),	/* show_legend */
					(unsigned char)atoi(row[10]),	/* show_3d */
					atof(row[11]),			/* percent_left */
					atof(row[12]),			/* percent_right */
					(unsigned char)atoi(row[13]),	/* ymin_type */
					(unsigned char)atoi(row[14]),	/* ymax_type */
					ymin_itemid,
					ymax_itemid,
					&jp_row,
					error);
		}
	}
	DBfree_result(result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

/******************************************************************************
 *                                                                            *
 * Function: DBlld_process_discovery_rule                                     *
 *                                                                            *
 * Purpose: add or update items, triggers and graphs for discovery item       *
 *                                                                            *
 * Parameters: discovery_itemid - [IN] discovery item identificator           *
 *                                     from database                          *
 *             value            - [IN] received value from agent              *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
void	DBlld_process_discovery_rule(zbx_uint64_t discovery_itemid, char *value)
{
	const char		*__function_name = "DBlld_process_discovery_rule";

	DB_RESULT		result;
	DB_ROW			row;
	zbx_uint64_t		hostid = 0, interfaceid = 0;
	struct zbx_json_parse	jp, jp_data;
	char			*snmp_community_esc = NULL, *snmpv3_securityname_esc = NULL,
				*snmpv3_authpassphrase_esc = NULL, *snmpv3_privpassphrase_esc = NULL,
				*discovery_key = NULL, *filter = NULL, *error = NULL, *db_error = NULL,
				*error_esc, *port_esc = NULL;
	unsigned char		status = 0, snmpv3_securitylevel = 0;
	char			*f_macro = NULL, *f_regexp = NULL;
	ZBX_REGEXP		*regexps = NULL;
	int			regexps_alloc = 0, regexps_num = 0;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() itemid:" ZBX_FS_UI64, __function_name, discovery_itemid);

	result = DBselect(
			"select hostid,key_,status,filter,snmp_community,port,snmpv3_securityname,"
				"snmpv3_securitylevel,snmpv3_authpassphrase,snmpv3_privpassphrase,error,"
				"interfaceid"
			" from items"
			" where itemid=" ZBX_FS_UI64,
			discovery_itemid);

	if (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(hostid, row[0]);
		discovery_key = zbx_strdup(discovery_key, row[1]);
		status = (unsigned char)atoi(row[2]);
		filter = zbx_strdup(filter, row[3]);
		snmp_community_esc = DBdyn_escape_string(row[4]);
		port_esc = DBdyn_escape_string(row[5]);
		snmpv3_securityname_esc = DBdyn_escape_string(row[6]);
		snmpv3_securitylevel = (unsigned char)atoi(row[7]);
		snmpv3_authpassphrase_esc = DBdyn_escape_string(row[8]);
		snmpv3_privpassphrase_esc = DBdyn_escape_string(row[9]);
		db_error = zbx_strdup(db_error, row[10]);
		ZBX_DBROW2UINT64(interfaceid, row[11]);
	}
	else
		zabbix_log(LOG_LEVEL_WARNING, "Invalid discovery rule ID [" ZBX_FS_UI64 "]", discovery_itemid);
	DBfree_result(result);

	if (0 == hostid)
		goto clean;

	DBbegin();

	error = zbx_strdup(error, "");

	if (SUCCEED != zbx_json_open(value, &jp))
	{
		error = zbx_dsprintf(error, "Value should be JSON object");
		goto error;
	}

/* {"net.if.discovery":[{"{#IFNAME}":"eth0"},{"{#IFNAME}":"lo"},...]}
 *                     ^-------------------------------------------^
 */	if (SUCCEED != zbx_json_brackets_by_name(&jp, discovery_key, &jp_data))
	{
		error = zbx_dsprintf(error, "Wrong data in JSON object");
		goto error;
	}

	if (NULL != (f_regexp = strchr(filter, ':')))
	{
		f_macro = filter;
		*f_regexp++ = '\0';

		if ('@' == *f_regexp)
		{
			DB_RESULT	result;
			DB_ROW		row;
			char		*f_regexp_esc;

			f_regexp_esc = DBdyn_escape_string(f_regexp + 1);

			result = DBselect("select r.name,e.expression,e.expression_type,e.exp_delimiter,e.case_sensitive"
					" from regexps r,expressions e"
					" where r.regexpid=e.regexpid"
						" and r.name='%s'",
					f_regexp_esc);

			zbx_free(f_regexp_esc);

			while (NULL != (row = DBfetch(result)))
				add_regexp_ex(&regexps, &regexps_alloc, &regexps_num,
						row[0], row[1], atoi(row[2]), row[3][0], atoi(row[4]));
			DBfree_result(result);
		}

		zabbix_log(LOG_LEVEL_DEBUG, "%s() f_macro:'%s' f_regexp:'%s'",
				__function_name, f_macro, f_regexp);
	}

	DBlld_update_items(hostid, discovery_itemid, &jp_data, &error,
			f_macro, f_regexp, regexps, regexps_num,
			snmp_community_esc, port_esc, snmpv3_securityname_esc,
			snmpv3_securitylevel, snmpv3_authpassphrase_esc,
			snmpv3_privpassphrase_esc, interfaceid);
	DBlld_update_triggers(hostid, discovery_itemid, &jp_data, &error,
			f_macro, f_regexp, regexps, regexps_num);
	DBlld_update_graphs(hostid, discovery_itemid, &jp_data, &error,
			f_macro, f_regexp, regexps, regexps_num);

	zbx_free(regexps);

	if (ITEM_STATUS_NOTSUPPORTED == status)
	{
		char	*message = NULL;

		message = zbx_dsprintf(message, "Parameter [" ZBX_FS_UI64 "][%s] became supported",
				discovery_itemid, zbx_host_key_string(discovery_itemid));
		zabbix_log(LOG_LEVEL_WARNING, "%s", message);
		zabbix_syslog("%s", message);
		zbx_free(message);

		DBexecute("update items set status=%d where itemid=" ZBX_FS_UI64,
				ITEM_STATUS_ACTIVE, discovery_itemid);
	}
error:
	if (NULL != error && 0 != strcmp(error, db_error))
	{
		error_esc = DBdyn_escape_string_len(error, ITEM_ERROR_LEN);

		DBexecute("update items set error='%s' where itemid=" ZBX_FS_UI64,
				error_esc, discovery_itemid);

		zbx_free(error_esc);
	}

	DBcommit();
clean:
	zbx_free(port_esc);
	zbx_free(error);
	zbx_free(db_error);
	zbx_free(snmpv3_privpassphrase_esc);
	zbx_free(snmpv3_authpassphrase_esc);
	zbx_free(snmpv3_securityname_esc);
	zbx_free(snmp_community_esc);
	zbx_free(discovery_key);
	zbx_free(filter);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}
