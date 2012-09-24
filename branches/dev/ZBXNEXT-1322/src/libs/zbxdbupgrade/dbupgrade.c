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
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

#include "common.h"

#include "db.h"
#include "log.h"

#if defined(HAVE_MYSQL)
#	define ZBX_DB_QUOTE		"`"
#	define ZBX_DB_TABLE_OPTIONS	" engine=innodb"
#else
#	define ZBX_DB_QUOTE		""
#	define ZBX_DB_TABLE_OPTIONS	""
#endif

#if defined(HAVE_POSTGRESQL)
#	define ZBX_DB_ONLY		" only"
#else
#	define ZBX_DB_ONLY		""
#endif

#define ZBX_FIRST_DB_VERSION		2010000

typedef struct
{
	int		(*function)();
	int		version;
	int		duplicates;
	unsigned char	mandatory;
}
zbx_dbpatch_t;

extern unsigned char	daemon_type;

static char	*DBfield_type_string(unsigned char type)
{
	switch (type)
	{
		case ZBX_TYPE_INT:
#if defined(HAVE_ORACLE)
			return "number(10)";
#else
			return "integer";
#endif
		case ZBX_TYPE_UINT:
#if defined(HAVE_IBM_DB2)
			return "bigint";
#elif defined(HAVE_MYSQL)
			return "bigint unsigned";
#elif defined(HAVE_ORACLE)
			return "number(20)";
#elif defined(HAVE_POSTGRESQL)
			return "numeric(20)";
#endif
		default:
			assert(0);
	}
}

static void	DBcreate_table_sql(char **sql, size_t *sql_alloc, size_t *sql_offset, const ZBX_TABLE *table)
{
	char	*default_value_esc;
	int	i;

	zbx_snprintf_alloc(sql, sql_alloc, sql_offset, "create table " ZBX_DB_QUOTE "%s" ZBX_DB_QUOTE " (\n",
			table->table);

	for (i = 0; NULL != table->fields[i].name; i++)
	{
		if (0 != i)
			zbx_strcpy_alloc(sql, sql_alloc, sql_offset, ",\n");
		zbx_snprintf_alloc(sql, sql_alloc, sql_offset, ZBX_DB_QUOTE "%s" ZBX_DB_QUOTE " %s",
				table->fields[i].name, DBfield_type_string(table->fields[i].type));
		if (NULL != table->fields[i].default_value)
		{
			default_value_esc = DBdyn_escape_string(table->fields[i].default_value);
			zbx_snprintf_alloc(sql, sql_alloc, sql_offset, " default '%s'", default_value_esc);
			zbx_free(default_value_esc);
		}
		if (0 != (table->fields[i].flags & ZBX_NOTNULL))
			zbx_strcpy_alloc(sql, sql_alloc, sql_offset, " not null");
	}
	zbx_strcpy_alloc(sql, sql_alloc, sql_offset, "\n)" ZBX_DB_TABLE_OPTIONS);
}

#if defined(HAVE_POSTGRESQL)
static void	DBmodify_field_type_sql(char **sql, size_t *sql_alloc, size_t *sql_offset,
		const char *table_name, const ZBX_FIELD *field)
{
	zbx_snprintf_alloc(sql, sql_alloc, sql_offset,
			"alter table" ZBX_DB_ONLY " " ZBX_DB_QUOTE "%s" ZBX_DB_QUOTE
			" alter " ZBX_DB_QUOTE "%s" ZBX_DB_QUOTE " type %s",
			table_name, field->name, DBfield_type_string(field->type));
}
#endif

static int	DBcreate_dbversion_table()
{
	char		*sql = NULL;
	size_t		sql_alloc = 128, sql_offset = 0;
	const ZBX_TABLE	*table;
	int		ret = FAIL;

	if (NULL == (table = DBget_table("dbversion")))
		assert(0);

	sql = zbx_malloc(sql, sql_alloc);

	DBcreate_table_sql(&sql, &sql_alloc, &sql_offset, table);

	DBbegin();
	if (ZBX_DB_OK <= DBexecute("%s", sql))
	{
		sql_offset = 0;
		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
				"insert into dbversion (mandatory,optional) values (%d,%d)",
				ZBX_FIRST_DB_VERSION, ZBX_FIRST_DB_VERSION);
		if (ZBX_DB_OK <= DBexecute("%s", sql))
			ret = SUCCEED;
	}
	DBend(ret);

	zbx_free(sql);

	return ret;
}

static void	DBget_version(int *mandatory, int *optional)
{
	DB_RESULT	result;
	DB_ROW		row;

	*mandatory = -1;
	*optional = -1;

	result = DBselect("select mandatory,optional from dbversion");

	if (NULL != (row = DBfetch(result)))
	{
		*mandatory = atoi(row[0]);
		*optional = atoi(row[1]);
	}
	DBfree_result(result);

	if (-1 == *mandatory)
	{
		zabbix_log(LOG_LEVEL_CRIT, "Cannot get the database version. Exiting ...");
		exit(EXIT_FAILURE);
	}
}

static int	DBset_version(int version, unsigned char mandatory)
{
	char	sql[64];
	size_t	offset;

	offset = zbx_snprintf(sql, sizeof(sql),  "update dbversion set ");
	if (0 != mandatory)
		offset += zbx_snprintf(sql + offset, sizeof(sql) - offset, "mandatory=%d,", version);
	zbx_snprintf(sql + offset, sizeof(sql) - offset, "optional=%d", version);

	if (ZBX_DB_OK <= DBexecute("%s", sql))
		return SUCCEED;

	return FAIL;
}

static int	DBmodify_proxy_table_id_field(const char *table_name)
{
#if defined(HAVE_POSTGRESQL)
	char		*sql = NULL;
	size_t		sql_alloc = 64, sql_offset = 0;
	const ZBX_FIELD	field = {"id", NULL, NULL, NULL, 0, ZBX_TYPE_UINT, ZBX_NOTNULL, 0};
	int		ret = FAIL;

	sql = zbx_malloc(sql, sql_alloc);

	DBmodify_field_type_sql(&sql, &sql_alloc, &sql_offset, table_name, &field);

	if (ZBX_DB_OK <= DBexecute("%s", sql))
		ret = SUCCEED;

	zbx_free(sql);

	return ret;
#else
	return SUCCEED;
#endif
}

static int	DBpatch_02010001()
{
	return DBmodify_proxy_table_id_field("proxy_autoreg_host");
}

static int	DBpatch_02010002()
{
	return DBmodify_proxy_table_id_field("proxy_dhistory");
}

static int	DBpatch_02010003()
{
	return DBmodify_proxy_table_id_field("proxy_dhistory");
}

static int	DBpatch_02010004()
{
	const char	*strings[] = {"period", "stime", "timelinefixed", NULL};
	int		i;

	for (i = 0; NULL != strings[i]; i++)
	{
		if (ZBX_DB_OK > DBexecute("update profiles set idx='web.screens.%s' where idx='web.charts.%s'",
				strings[i], strings[i]))
		{
			return FAIL;
		}
	}

	return SUCCEED;
}

int	DBcheck_version()
{
	const char	*__function_name = "DBcheck_version";

	zbx_dbpatch_t	patches[] =
	{
		{DBpatch_02010001, 2010001, 0, 1},
		{DBpatch_02010002, 2010002, 0, 1},
		{DBpatch_02010003, 2010003, 0, 1},
		{DBpatch_02010004, 2010004, 0, 0},
		{NULL}
	};
	const char	*dbversion_table_name = "dbversion";
	int		db_mandatory, db_optional, required, optional, i, ret = FAIL,
			total = 0, current = 0, completed, last_completed = -1;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	DBconnect(ZBX_DB_CONNECT_NORMAL);

	if (SUCCEED != DBtable_exists(dbversion_table_name))
	{
		zabbix_log(LOG_LEVEL_DEBUG, "%s() \"%s\" doesn't exist",
				__function_name, dbversion_table_name);

		if (SUCCEED != DBfield_exists("config", "server_check_interval"))
		{
			zabbix_log(LOG_LEVEL_CRIT, "Cannot upgrade database: the database must"
					" correspond to version 2.0 or later. Exiting ...");
			goto out;
		}

		if (SUCCEED != DBcreate_dbversion_table())
			goto out;
	}

	DBget_version(&db_mandatory, &db_optional);

	optional = required = ZBX_FIRST_DB_VERSION;

	for (i = 0; NULL != patches[i].function; i++)
	{
		if (0 != patches[i].mandatory)
			required = patches[i].version;
		optional = patches[i].version;

		if (db_optional < patches[i].version)
			total++;
	}

	if (required < db_mandatory)
	{
		zabbix_log(LOG_LEVEL_CRIT, "The %s does not match Zabbix database."
				" Current database version (mandatory/optional): %08d/%08d."
				" Required mandatory version: %08d.",
				ZBX_DAEMON_TYPE_SERVER == daemon_type ? "server" : "proxy",
				db_mandatory, db_optional, required);
		goto out;
	}

	zabbix_log(LOG_LEVEL_INFORMATION, "Current database version (mandatory/optional): %08d/%08d",
			db_mandatory, db_optional);
	zabbix_log(LOG_LEVEL_INFORMATION, "Required mandatory version: %08d", required);

	ret = SUCCEED;

	if (0 == total)
		goto out;

	zabbix_log(LOG_LEVEL_WARNING, "starting automatic database upgrade");

	for (i = 0; NULL != patches[i].function; i++)
	{
		if (db_optional >= patches[i].version)
			continue;

		DBbegin();

		/* skipping the duplicated patches */
		if ((0 != patches[i].duplicates && patches[i].duplicates <= db_optional) ||
				SUCCEED == (ret = patches[i].function()))
		{
			ret = DBset_version(patches[i].version, patches[i].mandatory);
		}

		DBend(ret);

		if (SUCCEED != ret)
			break;

		current++;
		completed = (int)(100.0 * current / total);
		if (last_completed != completed)
		{
			zabbix_log(LOG_LEVEL_WARNING, "completed %d%% of database upgrade", completed);
			last_completed = completed;
		}
	}

	if (SUCCEED == ret)
		zabbix_log(LOG_LEVEL_WARNING, "database upgrade fully completed");
	else
		zabbix_log(LOG_LEVEL_CRIT, "database upgrade failed");
out:
	DBclose();

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(ret));

	return ret;
}
