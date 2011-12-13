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
#include "dbcache.h"
#include "zbxserver.h"

/******************************************************************************
 *                                                                            *
 * Function: validate_template                                                *
 *                                                                            *
 * Description: Check collisions between templates                            *
 *                                                                            *
 * Parameters: templateids - array of templates identificators from database  *
 *             templateids_num - templates count in templateids array         *
 *                                                                            *
 * Return value: SUCCEED if no collisions found                               *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static int	validate_template(zbx_uint64_t *templateids, int templateids_num, char *error, size_t max_error_len)
{
	DB_RESULT	result;
	DB_ROW		row;
	char		*sql = NULL;
	size_t		sql_alloc = 256, sql_offset;
	zbx_uint64_t	*ids = NULL, id;
	int		ids_alloc = 0, ids_num, ret = SUCCEED;

	if (templateids_num < 2)
		return ret;

	sql = zbx_malloc(sql, sql_alloc);

	/* applications */
	if (SUCCEED == ret)
	{
		sql_offset = 0;
		zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset,
				"select name,count(*)"
				" from applications"
				" where");
		DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "hostid", templateids, templateids_num);
		zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset,
				" group by name"
				" having count(*)>1");

		result = DBselect("%s", sql);

		if (NULL != (row = DBfetch(result)))
		{
			ret = FAIL;
			zbx_snprintf(error, max_error_len,
					"Template with application [%s]"
					" already linked to the host", row[0]);
		}
		DBfree_result(result);
	}

	/* items */
	if (SUCCEED == ret)
	{
		sql_offset = 0;
		zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset,
				"select key_,count(*)"
				" from items"
				" where");
		DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "hostid", templateids, templateids_num);
		zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset,
				" group by key_"
				" having count(*)>1");

		result = DBselect("%s", sql);

		if (NULL != (row = DBfetch(result)))
		{
			ret = FAIL;
			zbx_snprintf(error, max_error_len,
					"Template with item key [%s]"
					" already linked to the host", row[0]);
		}
		DBfree_result(result);
	}

	/* graphs */
	if (SUCCEED == ret)
	{
		/* select all linked graphs */
		sql_offset = 0;
		zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset,
				"select distinct gi.graphid"
				" from graphs_items gi,items i"
				" where gi.itemid=i.itemid"
					" and");
		DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "i.hostid", templateids, templateids_num);

		result = DBselect("%s", sql);

		ids_num = 0;

		while (NULL != (row = DBfetch(result)))
		{
			ZBX_STR2UINT64(id, row[0]);
			uint64_array_add(&ids, &ids_alloc, &ids_num, id, 4);
		}
		DBfree_result(result);

		/* check for names */
		if (ids_num > 1)
		{
			sql_offset = 0;
			zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset,
					"select name,count(*)"
					" from graphs"
					" where");
			DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "graphid", ids, ids_num);
			zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset,
					" group by name"
					" having count(*)>1");

			result = DBselect("%s", sql);

			if (NULL != (row = DBfetch(result)))
			{
				ret = FAIL;
				zbx_snprintf(error, max_error_len,
						"Template with graph [%s]"
						" already linked to the host",
						row[0]);
			}
			DBfree_result(result);
		}
	}

	zbx_free(ids);
	zbx_free(sql);

	return ret;
}

/******************************************************************************
 *                                                                            *
 * Function: DBcmp_triggers                                                   *
 *                                                                            *
 * Purpose: compare two triggers                                              *
 *                                                                            *
 * Parameters: triggerid1 - first trigger identificator from database         *
 *             triggerid2 - second trigger identificator from database        *
 *                                                                            *
 * Return value: SUCCEED - if triggers coincide                               *
 *                                                                            *
 * Author: Eugene Grigorjev                                                   *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static int	DBcmp_triggers(zbx_uint64_t triggerid1, const char *expression1,
		zbx_uint64_t triggerid2, const char *expression2)
{
	DB_RESULT	result;
	DB_ROW		row;
	char		*search = NULL,
			*replace = NULL,
			*old_expr = NULL,
			*expr = NULL;
	int		res = SUCCEED;

	expr = strdup(expression2);

	result = DBselect(
			"select f1.functionid,f2.functionid"
			" from functions f1,functions f2,items i1,items i2"
			" where f1.function=f2.function"
				" and f1.parameter=f2.parameter"
				" and i1.key_=i2.key_"
				" and i1.itemid=f1.itemid"
				" and i2.itemid=f2.itemid"
				" and f1.triggerid=" ZBX_FS_UI64
				" and f2.triggerid=" ZBX_FS_UI64,
				triggerid1, triggerid2);

	while (NULL != (row = DBfetch(result)))
	{
		search = zbx_dsprintf(NULL, "{%s}", row[1]);
		replace = zbx_dsprintf(NULL, "{%s}", row[0]);

		old_expr = expr;
		expr = string_replace(old_expr, search, replace);
		zbx_free(old_expr);

		zbx_free(replace);
		zbx_free(search);
	}
	DBfree_result(result);

	if (0 != strcmp(expression1, expr))
		res = FAIL;

	zbx_free(expr);

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: validate_inventory_links                                         *
 *                                                                            *
 * Description: Check collisions in item inventory links                      *
 *                                                                            *
 * Parameters: hostid     - [IN] host identificator from database             *
 *             templateid - [IN] template identificator from database         *
 *                                                                            *
 * Return value: SUCCEED if no collisions found                               *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static int	validate_inventory_links(zbx_uint64_t hostid, zbx_uint64_t templateid, char *error, size_t max_error_len)
{
	DB_RESULT	result;
	DB_ROW		row;
	char		sql[320];
	int		ret = SUCCEED;

	zbx_snprintf(sql, sizeof(sql),
			"select ti.itemid"
			" from items ti,items i"
			" where ti.key_<>i.key_"
				" and ti.inventory_link=i.inventory_link"
				" and ti.hostid=" ZBX_FS_UI64
				" and i.hostid=" ZBX_FS_UI64
				" and ti.inventory_link<>0"
				" and not exists ("
					"select *"
					" from items"
					" where items.hostid=" ZBX_FS_UI64
						" and items.key_=i.key_"
					")",
			templateid, hostid, templateid);

	result = DBselectN(sql, 1);

	if (NULL != (row = DBfetch(result)))
	{
		zbx_strlcpy(error, "Two items cannot populate one host inventory field", max_error_len);
		ret = FAIL;
	}
	DBfree_result(result);

	return ret;
}

void	DBget_graphitems(const char *sql, ZBX_GRAPH_ITEMS **gitems, size_t *gitems_alloc, size_t *gitems_num)
{
	const char	*__function_name = "DBget_graphitems";
	DB_RESULT	result;
	DB_ROW		row;
	ZBX_GRAPH_ITEMS	*gitem;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	*gitems_num = 0;

	result = DBselect("%s", sql);

	while (NULL != (row = DBfetch(result)))
	{
		if (*gitems_alloc == *gitems_num)
		{
			*gitems_alloc += 16;
			*gitems = zbx_realloc(*gitems, *gitems_alloc * sizeof(ZBX_GRAPH_ITEMS));
		}

		gitem = &(*gitems)[*gitems_num];

		ZBX_STR2UINT64(gitem->gitemid, row[0]);
		ZBX_STR2UINT64(gitem->itemid, row[1]);
		zbx_strlcpy(gitem->key, row[2], sizeof(gitem->key));
		gitem->drawtype = atoi(row[3]);
		gitem->sortorder = atoi(row[4]);
		zbx_strlcpy(gitem->color, row[5], sizeof(gitem->color));
		gitem->yaxisside = atoi(row[6]);
		gitem->calc_fnc = atoi(row[7]);
		gitem->type = atoi(row[8]);
		gitem->flags = (unsigned char)atoi(row[9]);

		zabbix_log(LOG_LEVEL_DEBUG, "%s() [%d] itemid:" ZBX_FS_UI64 " key:'%s'",
				__function_name, *gitems_num, gitem->itemid, gitem->key);

		(*gitems_num)++;
	}
	DBfree_result(result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

/******************************************************************************
 *                                                                            *
 * Function: DBcmp_graphitems                                                 *
 *                                                                            *
 * Purpose: Compare graph items from two graphs                               *
 *                                                                            *
 * Parameters: gitems1     - [IN] first graph items, sorted by itemid         *
 *             gitems1_num - [IN] number of first graph items                 *
 *             gitems2     - [IN] second graph items, sorted by itemid        *
 *             gitems2_num - [IN] number of second graph items                *
 *                                                                            *
 * Return value: SUCCEED if graph items coincide                              *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static int	DBcmp_graphitems(ZBX_GRAPH_ITEMS *gitems1, int gitems1_num,
		ZBX_GRAPH_ITEMS *gitems2, int gitems2_num)
{
	const char	*__function_name = "DBcmp_graphitems";
	int		res = FAIL, i;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	if (gitems1_num != gitems2_num)
		goto clean;

	for (i = 0; i < gitems1_num; i++)
		if (0 != strcmp(gitems1[i].key, gitems2[i].key))
			goto clean;

	res = SUCCEED;
clean:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s",
			__function_name, zbx_result_string(res));

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: validate_host                                                    *
 *                                                                            *
 * Description: Check collisions between host and linked template             *
 *                                                                            *
 * Parameters: hostid - host identificator from database                      *
 *             templateid - template identificator from database              *
 *                                                                            *
 * Return value: SUCCEED if no collisions found                               *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static int	validate_host(zbx_uint64_t hostid, zbx_uint64_t templateid, char *error, size_t max_error_len)
{
	DB_RESULT	tresult;
	DB_RESULT	hresult;
	DB_ROW		trow;
	DB_ROW		hrow;
	char		*sql = NULL, *name_esc;
	size_t		sql_alloc = 256, sql_offset;
	ZBX_GRAPH_ITEMS *gitems = NULL, *chd_gitems = NULL;
	size_t		gitems_alloc = 0, gitems_num = 0,
			chd_gitems_alloc = 0, chd_gitems_num = 0;
	int		res;
	zbx_uint64_t	graphid;
	unsigned char	t_flags, h_flags, type;
	zbx_uint64_t	interfaceids[4];

	if (SUCCEED != (res = validate_inventory_links(hostid, templateid, error, max_error_len)))
		return res;

	sql = zbx_malloc(sql, sql_alloc);

	tresult = DBselect(
			"select distinct g.graphid,g.name,g.flags"
			" from graphs g,graphs_items gi,items i"
			" where g.graphid=gi.graphid"
				" and gi.itemid=i.itemid"
				" and i.hostid=" ZBX_FS_UI64,
			templateid);

	while (SUCCEED == res && NULL != (trow = DBfetch(tresult)))
	{
		ZBX_STR2UINT64(graphid, trow[0]);
		t_flags = (unsigned char)atoi(trow[2]);

		sql_offset = 0;
		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
				"select 0,0,i.key_,gi.drawtype,gi.sortorder,gi.color,gi.yaxisside,gi.calc_fnc,"
					"gi.type,i.flags"
				" from graphs_items gi,items i"
				" where gi.itemid=i.itemid"
					" and gi.graphid=" ZBX_FS_UI64
				" order by i.key_",
				graphid);

		DBget_graphitems(sql, &gitems, &gitems_alloc, &gitems_num);

		name_esc = DBdyn_escape_string(trow[1]);

		hresult = DBselect(
				"select distinct g.graphid,g.flags"
				" from graphs g,graphs_items gi,items i"
				" where g.graphid=gi.graphid"
					" and gi.itemid=i.itemid"
					" and i.hostid=" ZBX_FS_UI64
					" and g.name='%s'"
					" and g.templateid is null",
				hostid, name_esc);

		zbx_free(name_esc);

		/* compare graphs */
		while (NULL != (hrow = DBfetch(hresult)))
		{
			ZBX_STR2UINT64(graphid, hrow[0]);
			h_flags = (unsigned char)atoi(hrow[1]);

			if (t_flags != h_flags)
			{
				res = FAIL;
				zbx_snprintf(error, max_error_len,
						"Graph prototype and real graph [%s] have the same name",
						trow[1]);
				break;
			}

			sql_offset = 0;
			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
					"select gi.gitemid,i.itemid,i.key_,gi.drawtype,gi.sortorder,gi.color,"
						"gi.yaxisside,gi.calc_fnc,gi.type,i.flags"
					" from graphs_items gi,items i"
					" where gi.itemid=i.itemid"
						" and gi.graphid=" ZBX_FS_UI64
					" order by i.key_",
					graphid);

			DBget_graphitems(sql, &chd_gitems, &chd_gitems_alloc, &chd_gitems_num);

			if (SUCCEED != DBcmp_graphitems(gitems, gitems_num, chd_gitems, chd_gitems_num))
			{
				res = FAIL;
				zbx_snprintf(error, max_error_len,
						"Graph [%s] already exists on the host (items are not identical)",
						trow[1]);
				break;	/* found graph with equal name, but items are not identical */
			}
		}
		DBfree_result(hresult);
	}
	DBfree_result(tresult);

	if (SUCCEED == res)
	{
		tresult = DBselect(
				"select i.key_"
				" from items i,items t"
				" where i.key_=t.key_"
					" and i.flags<>t.flags"
					" and i.hostid=" ZBX_FS_UI64
					" and t.hostid=" ZBX_FS_UI64,
				hostid, templateid);

		if (NULL != (trow = DBfetch(tresult)))
		{
			res = FAIL;
			zbx_snprintf(error, max_error_len,
					"Item prototype and real item [%s] have the same key",
					trow[0]);
		}
		DBfree_result(tresult);
	}

	/* interfaces */
	if (SUCCEED == res)
	{
		memset(&interfaceids, 0, sizeof(interfaceids));

		tresult = DBselect(
				"select type,interfaceid"
				" from interface"
				" where hostid=" ZBX_FS_UI64
					" and type in (%d,%d,%d,%d)"
					" and main=1"
					DB_NODE,
				hostid, INTERFACE_TYPE_AGENT, INTERFACE_TYPE_SNMP,
				INTERFACE_TYPE_IPMI, INTERFACE_TYPE_JMX, DBnode_local("interfaceid"));

		while (NULL != (trow = DBfetch(tresult)))
		{
			type = (unsigned char)atoi(trow[0]);
			ZBX_STR2UINT64(interfaceids[type - 1], trow[1]);
		}
		DBfree_result(tresult);

		tresult = DBselect(
				"select distinct type"
				" from items"
				" where hostid=" ZBX_FS_UI64
					" and type not in (%d,%d,%d,%d,%d,%d)",
				templateid, ITEM_TYPE_TRAPPER, ITEM_TYPE_INTERNAL,
				ITEM_TYPE_ZABBIX_ACTIVE, ITEM_TYPE_AGGREGATE,
				ITEM_TYPE_DB_MONITOR, ITEM_TYPE_CALCULATED);

		while (SUCCEED == res && NULL != (trow = DBfetch(tresult)))
		{
			type = (unsigned char)atoi(trow[0]);
			type = get_interface_type_by_item_type(type);

			if (INTERFACE_TYPE_ANY != type && 0 == interfaceids[type - 1])
			{
				res = FAIL;
				zbx_snprintf(error, max_error_len, "Cannot find %s host interface",
						zbx_interface_type_string((zbx_interface_type_t)type));
			}
		}
		DBfree_result(tresult);
	}

	zbx_free(sql);
	zbx_free(gitems);
	zbx_free(chd_gitems);

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: DBget_same_applications_by_itemid                                *
 *                                                                            *
 * Purpose: retrieve same applications for specified templated item           *
 *                                                                            *
 * Parameters:  hostid - host identificator from database                     *
 *              template_itemid - template item identificator from database   *
 *              appids - result buffer                                        *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static int	DBget_same_applications_by_itemid(zbx_uint64_t hostid,
		zbx_uint64_t itemid, zbx_uint64_t template_itemid,
		zbx_uint64_t **appids, int *appids_alloc, int *appids_num)
{
	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	applicationid;

	result = DBselect(
			"select hi.itemappid,ha.applicationid"
			" from applications ha"
			" join applications ta"
				" on ta.name=ha.name"
			" join items_applications ti"
				" on ti.applicationid=ta.applicationid"
					" and ti.itemid=" ZBX_FS_UI64
			" left join items_applications hi"
				" on hi.applicationid=ha.applicationid"
					" and hi.itemid=" ZBX_FS_UI64
			" where ha.hostid=" ZBX_FS_UI64
				" and hi.itemappid is null",
			template_itemid, itemid, hostid);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(applicationid, row[1]);
		uint64_array_add(appids, appids_alloc, appids_num, applicationid, 4);
	}
	DBfree_result(result);

	return SUCCEED;
}

/******************************************************************************
 *                                                                            *
 * Function: DBclear_parents_from_trigger                                     *
 *                                                                            *
 * Purpose: removes any links between trigger and service if service          *
 *          is not leaf (treenode)                                            *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Author: Eugene Grigorjev                                                   *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static void	DBclear_parents_from_trigger()
{
	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	serviceid;

	result = DBselect("select s.serviceid"
				" from services s,services_links sl"
				" where s.serviceid=sl.serviceupid"
					" and s.triggerid is not null"
				" group by s.serviceid");

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(serviceid, row[0]);

		DBexecute("update services"
				" set triggerid=null"
				" where serviceid=" ZBX_FS_UI64, serviceid);
	}
	DBfree_result(result);
}

/******************************************************************************
 *                                                                            *
 * Function: DBget_service_status                                             *
 *                                                                            *
 * Purpose: retrieve true status                                              *
 *                                                                            *
 * Author: Eugene Grigorjev                                                   *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static int	DBget_service_status(zbx_uint64_t serviceid, int algorithm, zbx_uint64_t triggerid)
{
	DB_RESULT	result;
	DB_ROW		row;

	int		status = 0;
	char		sort_order[MAX_STRING_LEN];
	char		sql[MAX_STRING_LEN];

	if (0 != triggerid)
	{
		result = DBselect("select priority"
					" from triggers"
					" where triggerid=" ZBX_FS_UI64
						" and status=0"
						" and value=%d",
					triggerid,
					TRIGGER_VALUE_TRUE);
		row = DBfetch(result);
		if (NULL != row && SUCCEED != DBis_null(row[0]))
		{
			status = atoi(row[0]);
		}
		DBfree_result(result);
	}

	if (SERVICE_ALGORITHM_MAX == algorithm || SERVICE_ALGORITHM_MIN == algorithm)
	{
		zbx_strlcpy(sort_order, (SERVICE_ALGORITHM_MAX == algorithm ? "desc" : "asc"), sizeof(sort_order));

		zbx_snprintf(sql, sizeof(sql), "select s.status"
						" from services s,services_links l"
						" where l.serviceupid=" ZBX_FS_UI64
							" and s.serviceid=l.servicedownid"
						" order by s.status %s",
						serviceid,
						sort_order);

		result = DBselectN(sql, 1);
		row = DBfetch(result);
		if (NULL != row && SUCCEED != DBis_null(row[0]))
		{
			if (atoi(row[0]) != 0)
			{
				status = atoi(row[0]);
			}
		}
		DBfree_result(result);
	}

	return status;
}

/* SUCCEED if latest service alarm has this status */
/* Rewrite required to simplify logic ?*/
static int	latest_service_alarm(zbx_uint64_t serviceid, int status)
{
	const char	*__function_name = "latest_service_alarm";
	DB_RESULT	result;
	DB_ROW		row;
	int		ret = FAIL;
	char		sql[MAX_STRING_LEN];

	zabbix_log(LOG_LEVEL_DEBUG, "In %s(): serviceid [" ZBX_FS_UI64 "] status [%d]",
			__function_name, serviceid, status);

	zbx_snprintf(sql, sizeof(sql), "select servicealarmid,value"
					" from service_alarms"
					" where serviceid=" ZBX_FS_UI64
					" order by servicealarmid desc", serviceid);

	result = DBselectN(sql, 1);
	row = DBfetch(result);

	if (NULL != row && FAIL == DBis_null(row[1]) && status == atoi(row[1]))
	{
		ret = SUCCEED;
	}

	DBfree_result(result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(ret));

	return ret;
}

static void	DBadd_service_alarm(zbx_uint64_t serviceid, int status, int clock)
{
	const char	*__function_name = "DBadd_service_alarm";

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	if (SUCCEED != latest_service_alarm(serviceid, status))
	{
		DBexecute("insert into service_alarms (servicealarmid,serviceid,clock,value)"
			" values(" ZBX_FS_UI64 "," ZBX_FS_UI64 ",%d,%d)",
			DBget_maxid("service_alarms"), serviceid, clock, status);
	}

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

/******************************************************************************
 *                                                                            *
 * Function: DBupdate_services_rec                                            *
 *                                                                            *
 * Purpose: re-calculate and update status of the service and its children    *
 *                                                                            *
 * Parameters: serviceid - item to update services for                        *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments: recursive function                                               *
 *           !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static void	DBupdate_services_rec(zbx_uint64_t serviceid, int clock)
{
	int		algorithm, status = 0;
	zbx_uint64_t	serviceupid;
	DB_RESULT	result;
	DB_ROW		row;

	result = DBselect("select l.serviceupid,s.algorithm"
			" from services_links l,services s"
			" where s.serviceid=l.serviceupid"
				" and l.servicedownid=" ZBX_FS_UI64,
			serviceid);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(serviceupid, row[0]);
		algorithm = atoi(row[1]);

		if (SERVICE_ALGORITHM_MAX == algorithm || SERVICE_ALGORITHM_MIN == algorithm)
		{
			status = DBget_service_status(serviceupid, algorithm, 0);

			DBadd_service_alarm(serviceupid, status, clock);
			DBexecute("update services set status=%d where serviceid=" ZBX_FS_UI64, status, serviceupid);
		}
		else if (SERVICE_ALGORITHM_NONE != algorithm)
			zabbix_log(LOG_LEVEL_ERR, "unknown calculation algorithm of service status [%d]", algorithm);
	}
	DBfree_result(result);

	result = DBselect("select serviceupid"
			" from services_links"
			" where servicedownid=" ZBX_FS_UI64,
			serviceid);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(serviceupid, row[0]);
		DBupdate_services_rec(serviceupid, clock);
	}
	DBfree_result(result);
}

/******************************************************************************
 *                                                                            *
 * Function: DBupdate_services_status_all                                     *
 *                                                                            *
 * Purpose: Cleaning parent nodes from triggers, updating ALL services status.*
 *                                                                            *
 * Author: Eugene Grigorjev                                                   *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static void	DBupdate_services_status_all()
{
	DB_RESULT	result;
	DB_ROW		row;

	zbx_uint64_t	serviceid = 0, triggerid = 0;
	int		status = 0, clock;

	DBclear_parents_from_trigger();

	clock = time(NULL);

	result = DBselect(
			"select serviceid,algorithm,triggerid"
			" from services"
			" where serviceid not in (select distinct serviceupid from services_links)");

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(serviceid, row[0]);
		if (SUCCEED == DBis_null(row[2]))
			triggerid = 0;
		else
			ZBX_STR2UINT64(triggerid, row[2]);

		status = DBget_service_status(serviceid, atoi(row[1]), triggerid);

		DBexecute("update services"
				" set status=%d"
				" where serviceid=" ZBX_FS_UI64,
				status, serviceid);

		DBadd_service_alarm(serviceid, status, clock);
	}
	DBfree_result(result);

	result = DBselect(
			"select max(servicedownid),serviceupid"
			" from services_links"
			" where servicedownid not in (select distinct serviceupid from services_links)"
			" group by serviceupid");

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(serviceid, row[0]);
		DBupdate_services_rec(serviceid, clock);
	}
	DBfree_result(result);
}

/******************************************************************************
 *                                                                            *
 * Function: DBupdate_services                                                *
 *                                                                            *
 * Purpose: re-calculate and update status of the service and its children    *
 *                                                                            *
 * Parameters: serviceid - item to update services for                        *
 *             status - new status of the service                             *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
void	DBupdate_services(zbx_uint64_t triggerid, int status, int clock)
{
	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	serviceid;

	result = DBselect("select serviceid from services where triggerid=" ZBX_FS_UI64, triggerid);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(serviceid, row[0]);

		DBexecute("update services set status=%d where serviceid=" ZBX_FS_UI64, status, serviceid);

		DBadd_service_alarm(serviceid, status, clock);
		DBupdate_services_rec(serviceid, clock);
	}

	DBfree_result(result);
}

/******************************************************************************
 *                                                                            *
 * Function: DBdelete_services_by_triggerids                                  *
 *                                                                            *
 * Purpose: delete triggers from service                                      *
 *                                                                            *
 * Parameters: triggerids     - [IN] trigger identificators from database     *
 *             triggerids_num - [IN] number of triggers                       *
 *                                                                            *
 * Author: Eugene Grigorjev                                                   *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static void	DBdelete_services_by_triggerids(zbx_uint64_t *triggerids, int triggerids_num)
{
	char	*sql = NULL;
	size_t	sql_alloc = 256, sql_offset = 0;

	if (0 == triggerids_num)
		return;

	sql = zbx_malloc(sql, sql_alloc);

	zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset,
			"delete from services"
			" where");
	DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "triggerid", triggerids, triggerids_num);

	DBexecute("%s", sql);

	zbx_free(sql);

	DBupdate_services_status_all();
}

/******************************************************************************
 *                                                                            *
 * Function: DBdelete_sysmaps_elements                                        *
 *                                                                            *
 * Purpose: delete elements from map by elementtype and elementid             *
 *                                                                            *
 * Author: Eugene Grigorjev                                                   *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static void	DBdelete_sysmaps_elements(int elementtype, zbx_uint64_t *elementids, int elementids_num)
{
	char	*sql = NULL;
	size_t	sql_alloc = 256, sql_offset = 0;

	if (0 == elementids_num)
		return;

	sql = zbx_malloc(sql, sql_alloc);

	zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
			"delete from sysmaps_elements"
			" where elementtype=%d"
				" and",
			elementtype);
	DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "elementid", elementids, elementids_num);

	DBexecute("%s", sql);

	zbx_free(sql);
}

/******************************************************************************
 *                                                                            *
 * Function: DBdelete_action_conditions                                       *
 *                                                                            *
 * Purpose: delete action conditions by condition type and id                 *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 ******************************************************************************/
static void DBdelete_action_conditions(int conditiontype, zbx_uint64_t elementid)
{
	DB_RESULT	result;
	DB_ROW		row;

/* disable actions */
	result = DBselect("select distinct actionid from conditions where conditiontype=%d and value='" ZBX_FS_UI64 "'",
			conditiontype, elementid);

	while (NULL != (row = DBfetch(result)))
		DBexecute("update actions set status=%d where actionid=%s", ACTION_STATUS_DISABLED, row[0]);

	DBfree_result(result);

/* delete action conditions */
	DBexecute("delete from conditions where conditiontype=%d and value='" ZBX_FS_UI64 "'",
			conditiontype, elementid);
}

/******************************************************************************
 *                                                                            *
 * Function: DBdelete_triggers                                                *
 *                                                                            *
 * Purpose: delete trigger from database                                      *
 *                                                                            *
 * Parameters: triggerids     - [IN] trigger identificators from database     *
 *             triggerids_num - [IN] number of triggers                       *
 *                                                                            *
 * Author: Eugene Grigorjev                                                   *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static void	DBdelete_triggers(zbx_uint64_t **triggerids, int *triggerids_alloc, int *triggerids_num)
{
	char		*sql = NULL;
	size_t		sql_alloc = 256, sql_offset;
	int		num, i;
	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	triggerid;

	if (0 == *triggerids_num)
		return;

	sql = zbx_malloc(sql, sql_alloc);

	do /* add child triggers (auto-created) */
	{
		num = *triggerids_num;
		sql_offset = 0;
		zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset,
				"select triggerid"
				" from trigger_discovery"
				" where");
		DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "parent_triggerid", *triggerids, *triggerids_num);

		result = DBselect("%s", sql);

		while (NULL != (row = DBfetch(result)))
		{
			ZBX_STR2UINT64(triggerid, row[0]);
			uint64_array_add(triggerids, triggerids_alloc, triggerids_num, triggerid, 64);
		}
		DBfree_result(result);
	}
	while (num != *triggerids_num);

	DBdelete_services_by_triggerids(*triggerids, *triggerids_num);
	DBdelete_sysmaps_elements(SYSMAP_ELEMENT_TYPE_TRIGGER, *triggerids, *triggerids_num);

	for (i = 0; i < *triggerids_num; i++)
		DBdelete_action_conditions(CONDITION_TYPE_TRIGGER, (*triggerids)[i]);

	sql_offset = 0;
	DBbegin_multiple_update(&sql, &sql_alloc, &sql_offset);

	zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
			"delete from events"
			" where source=%d"
				" and object=%d"
				" and",
			EVENT_SOURCE_TRIGGERS, EVENT_OBJECT_TRIGGER);
	DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "objectid", *triggerids, *triggerids_num);
	zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset, ";\n");

	zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset,
			"delete from triggers"
			" where");
	DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "triggerid", *triggerids, *triggerids_num);
	zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, ";\n");

	DBend_multiple_update(&sql, &sql_alloc, &sql_offset);

	DBexecute("%s", sql);

	zbx_free(sql);
}

/******************************************************************************
 *                                                                            *
 * Function: DBdelete_triggers_by_itemids                                     *
 *                                                                            *
 * Purpose: delete triggers by itemid                                         *
 *                                                                            *
 * Parameters: itemids - [IN] item identificators from database               *
 *                                                                            *
 * Author: Eugene Grigorjev                                                   *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static void	DBdelete_triggers_by_itemids(zbx_vector_uint64_t *itemids)
{
	const char	*__function_name = "DBdelete_triggers_by_itemids";
	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	*triggerids = NULL, triggerid;
	int		triggerids_alloc = 0, triggerids_num = 0;
	char		*sql = NULL;
	size_t		sql_alloc = 512, sql_offset = 0;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() values_num:%d", __function_name, itemids->values_num);

	if (0 == itemids->values_num)
		goto out;

	sql = zbx_malloc(sql, sql_alloc);

	zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset,
			"select distinct triggerid"
			" from functions"
			" where");
	DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "itemid", itemids->values, itemids->values_num);

	result = DBselect("%s", sql);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(triggerid, row[0]);
		uint64_array_add(&triggerids, &triggerids_alloc, &triggerids_num, triggerid, 64);
	}
	DBfree_result(result);

	DBdelete_triggers(&triggerids, &triggerids_alloc, &triggerids_num);

	zbx_free(triggerids);
	zbx_free(sql);
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

/******************************************************************************
 *                                                                            *
 * Function: DBdelete_history_by_itemids                                      *
 *                                                                            *
 * Purpose: delete item history                                               *
 *                                                                            *
 * Parameters: itemids - [IN] item identificators from database               *
 *                                                                            *
 * Author: Eugene Grigorjev, Alexander Vladishev                              *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static void	DBdelete_history_by_itemids(zbx_vector_uint64_t *itemids)
{
	const char	*__function_name = "DBdelete_history_by_itemids";
	char		*sql = NULL;
	size_t		sql_alloc = 64 * ZBX_KIBIBYTE, sql_offset = 0;
	int		i, j;
	zbx_uint64_t	housekeeperid;
	const char	*ins_housekeeper_sql = "insert into housekeeper (housekeeperid,tablename,field,value) values ";
#ifdef HAVE_MULTIROW_INSERT
	const char	*row_dl = ",";
#else
	const char	*row_dl = ";\n";
#endif
#define	ZBX_HISTORY_TABLES_COUNT	7
	const char	*tables[ZBX_HISTORY_TABLES_COUNT] = {"history", "history_str", "history_uint", "history_log",
			"history_text", "trends", "trends_uint"};

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() values_num:%d", __function_name, itemids->values_num);

	if (0 == itemids->values_num)
		return;

	housekeeperid = DBget_maxid_num("housekeeper", ZBX_HISTORY_TABLES_COUNT * itemids->values_num);

	sql = zbx_malloc(sql, sql_alloc);
	DBbegin_multiple_update(&sql, &sql_alloc, &sql_offset);
#ifdef HAVE_MULTIROW_INSERT
	zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset, ins_housekeeper_sql);
#endif
	for (i = 0; i < itemids->values_num; i++)
	{
		for (j = 0; j < ZBX_HISTORY_TABLES_COUNT; j++)
		{
#ifndef HAVE_MULTIROW_INSERT
			zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset, ins_housekeeper_sql);
#endif
			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
					"(" ZBX_FS_UI64 ",'%s','itemid'," ZBX_FS_UI64 ")%s",
					housekeeperid++, tables[j], itemids->values[i], row_dl);
		}
	}

	DBend_multiple_update(&sql, &sql_alloc, &sql_offset);

	if (sql_offset > 16)	/* In ORACLE always present begin..end; */
	{
#ifdef HAVE_MULTIROW_INSERT
		sql_offset--;
		zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset, ";\n");
#endif
		DBexecute("%s", sql);
	}

	zbx_free(sql);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

/******************************************************************************
 *                                                                            *
 * Function: DBdelete_graphs                                                  *
 *                                                                            *
 * Purpose: delete graph from database                                        *
 *                                                                            *
 * Parameters: graphids - [IN] array of graph id's from database              *
 *                                                                            *
 * Return value: upon successful completion return SUCCEED                    *
 *                                                                            *
 * Author: Eugene Grigorjev, Alexander Vladishev                              *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static void	DBdelete_graphs(zbx_vector_uint64_t *graphids)
{
	const char	*__function_name = "DBdelete_graphs";
	char		*sql = NULL;
	size_t		sql_alloc = 256, sql_offset;
	int		num;
	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	graphid;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() values_num:%d", __function_name, graphids->values_num);

	if (0 == graphids->values_num)
		goto out;

	sql = zbx_malloc(sql, sql_alloc);

	do	/* add child graphs (auto-created) */
	{
		num = graphids->values_num;
		sql_offset = 0;
		zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset,
				"select graphid"
				" from graph_discovery"
				" where");
		DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "parent_graphid",
				graphids->values, graphids->values_num);

		result = DBselect("%s", sql);

		while (NULL != (row = DBfetch(result)))
		{
			ZBX_STR2UINT64(graphid, row[0]);
			zbx_vector_uint64_append(graphids, graphid);
		}
		DBfree_result(result);

		zbx_vector_uint64_sort(graphids, ZBX_DEFAULT_UINT64_COMPARE_FUNC);
		zbx_vector_uint64_uniq(graphids, ZBX_DEFAULT_UINT64_COMPARE_FUNC);
	}
	while (num != graphids->values_num);

	sql_offset = 0;
	DBbegin_multiple_update(&sql, &sql_alloc, &sql_offset);

	/* delete from screens_items */
	zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
			"delete from screens_items"
			" where resourcetype=%d"
				" and",
			SCREEN_RESOURCE_GRAPH);
	DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "resourceid", graphids->values, graphids->values_num);
	zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset, ";\n");

	/* delete from profiles */
	zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset,
			"delete from profiles"
			" where idx='web.favorite.graphids'"
				" and source='graphid'"
				" and");
	DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "value_id", graphids->values, graphids->values_num);
	zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset, ";\n");

	/* delete from graphs */
	zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset, "delete from graphs where");
	DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "graphid", graphids->values, graphids->values_num);
	zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset, ";\n");

	DBend_multiple_update(&sql, &sql_alloc, &sql_offset);

	DBexecute("%s", sql);

	zbx_free(sql);
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

/******************************************************************************
 *                                                                            *
 * Function: DBdelete_graphs_by_itemids                                       *
 *                                                                            *
 * Parameters: itemids - [IN] item identificators from database               *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 ******************************************************************************/
static void	DBdelete_graphs_by_itemids(zbx_vector_uint64_t *itemids)
{
	const char		*__function_name = "DBdelete_graphs_by_itemids";
	char			*sql = NULL;
	size_t			sql_alloc = 256, sql_offset;
	DB_RESULT		result;
	DB_ROW			row;
	zbx_uint64_t		graphid;
	zbx_vector_uint64_t	graphids;
	int			index;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() values_num:%d", __function_name, itemids->values_num);

	if (0 == itemids->values_num)
		goto out;

	sql = zbx_malloc(sql, sql_alloc);
	zbx_vector_uint64_create(&graphids);

	/* select all graphs with items */
	sql_offset = 0;
	zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, "select distinct graphid from graphs_items where");
	DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "itemid", itemids->values, itemids->values_num);
	result = DBselect("%s", sql);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(graphid, row[0]);
		zbx_vector_uint64_append(&graphids, graphid);
	}
	DBfree_result(result);

	zbx_vector_uint64_sort(&graphids, ZBX_DEFAULT_UINT64_COMPARE_FUNC);

	/* select graphs with other items */
	sql_offset = 0;
	zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
			"select distinct graphid"
			" from graphs_items"
			" where");
	DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "graphid", graphids.values, graphids.values_num);
	zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, " and not");
	DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "itemid", itemids->values, itemids->values_num);
	result = DBselect("%s", sql);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(graphid, row[0]);
		if (FAIL != (index = zbx_vector_uint64_bsearch(&graphids, graphid, ZBX_DEFAULT_UINT64_COMPARE_FUNC)))
			zbx_vector_uint64_remove(&graphids, index);
	}
	DBfree_result(result);

	DBdelete_graphs(&graphids);

	zbx_vector_uint64_destroy(&graphids);
	zbx_free(sql);
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

/******************************************************************************
 *                                                                            *
 * Function: DBdelete_items                                                   *
 *                                                                            *
 * Purpose: delete items from database                                        *
 *                                                                            *
 * Parameters: itemids - [IN] array of item identificators from database      *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
void	DBdelete_items(zbx_vector_uint64_t *itemids)
{
	const char	*__function_name = "DBdelete_items";
	char		*sql = NULL;
	size_t		sql_alloc = 256, sql_offset;
	int		num;
	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	itemid;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() values_num:%d", __function_name, itemids->values_num);

	if (0 == itemids->values_num)
		goto out;

	sql = zbx_malloc(sql, sql_alloc);

	do	/* add child items (auto-created and prototypes) */
	{
		num = itemids->values_num;
		sql_offset = 0;
		zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset,
				"select itemid"
				" from item_discovery"
				" where");
		DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "parent_itemid",
				itemids->values, itemids->values_num);

		result = DBselect("%s", sql);

		while (NULL != (row = DBfetch(result)))
		{
			ZBX_STR2UINT64(itemid, row[0]);
			zbx_vector_uint64_append(itemids, itemid);
		}
		DBfree_result(result);

		zbx_vector_uint64_sort(itemids, ZBX_DEFAULT_UINT64_COMPARE_FUNC);
		zbx_vector_uint64_uniq(itemids, ZBX_DEFAULT_UINT64_COMPARE_FUNC);
	}
	while (num != itemids->values_num);

	DBdelete_graphs_by_itemids(itemids);
	DBdelete_triggers_by_itemids(itemids);
	DBdelete_history_by_itemids(itemids);

	sql_offset = 0;
	DBbegin_multiple_update(&sql, &sql_alloc, &sql_offset);

	/* delete from screens_items */
	zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
			"delete from screens_items"
				" where resourcetype in (%d,%d)"
					" and",
			SCREEN_RESOURCE_PLAIN_TEXT,
			SCREEN_RESOURCE_SIMPLE_GRAPH);
	DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "resourceid", itemids->values, itemids->values_num);
	zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset, ";\n");

	/* delete from profiles */
	zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset,
			"delete from profiles"
			" where idx='web.favorite.graphids'"
				" and source='itemid'"
				" and");
	DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "value_id", itemids->values, itemids->values_num);
	zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset, ";\n");

	/* delete from items */
	zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset, "delete from items where");
	DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "itemid", itemids->values, itemids->values_num);
	zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset, ";\n");

	DBend_multiple_update(&sql, &sql_alloc, &sql_offset);

	DBexecute("%s", sql);

	zbx_free(sql);
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

/******************************************************************************
 *                                                                            *
 * Function: DBdelete_httptests                                               *
 *                                                                            *
 * Purpose: delete web tests from database                                    *
 *                                                                            *
 * Parameters: htids - [IN] array of httptest id's from database              *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static void	DBdelete_httptests(zbx_vector_uint64_t *htids)
{
	const char		*__function_name = "DBdelete_httptests";
	DB_RESULT		result;
	DB_ROW			row;
	char			*sql = NULL;
	size_t			sql_alloc = 256, sql_offset;
	zbx_uint64_t		elementid;
	zbx_vector_uint64_t	itemids, hsids;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() values_num:%d", __function_name, htids->values_num);

	if (0 == htids->values_num)
		goto out;

	sql = zbx_malloc(sql, sql_alloc);
	zbx_vector_uint64_create(&itemids);
	zbx_vector_uint64_create(&hsids);

	/* httpsteps */
	sql_offset = 0;
	zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset,
			"select httpstepid"
			" from httpstep"
			" where");
	DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "httptestid", htids->values, htids->values_num);

	result = DBselect("%s", sql);

	if (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(elementid, row[0]);
		zbx_vector_uint64_append(&hsids, elementid);
	}
	DBfree_result(result);

	zbx_vector_uint64_sort(&hsids, ZBX_DEFAULT_UINT64_COMPARE_FUNC);

	/* httpstepitems */
	sql_offset = 0;
	zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset,
			"select itemid"
			" from httpstepitem"
			" where");
	DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "httpstepid", hsids.values, hsids.values_num);

	result = DBselect("%s", sql);

	if (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(elementid, row[0]);
		zbx_vector_uint64_append(&itemids, elementid);
	}
	DBfree_result(result);

	/* httptestitems */
	sql_offset = 0;
	zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset,
			"select itemid"
			" from httptestitem"
			" where");
	DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "httptestid", htids->values, htids->values_num);

	result = DBselect("%s", sql);

	if (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(elementid, row[0]);
		zbx_vector_uint64_append(&itemids, elementid);
	}
	DBfree_result(result);

	zbx_vector_uint64_sort(&itemids, ZBX_DEFAULT_UINT64_COMPARE_FUNC);
	DBdelete_items(&itemids);

	sql_offset = 0;
	zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset, "delete from httptest where");
	DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "httptestid", htids->values, htids->values_num);
	DBexecute("%s", sql);

	zbx_vector_uint64_destroy(&hsids);
	zbx_vector_uint64_destroy(&itemids);
	zbx_free(sql);
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

/******************************************************************************
 *                                                                            *
 * Function: DBdelete_application                                             *
 *                                                                            *
 * Purpose: delete application                                                *
 *                                                                            *
 * Parameters: applicationid - [IN] application identificator from database   *
 *                                                                            *
 * Author: Eugene Grigorjev, Alexander Vladishev                              *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static void	DBdelete_applications(zbx_uint64_t *applicationids, int applicationids_num)
{
	DB_RESULT	result;
	DB_ROW		row;
	char		*sql = NULL;
	size_t		sql_alloc = 256, sql_offset = 0;
	zbx_uint64_t	*ids = NULL, applicationid;
	int		ids_alloc = 0, ids_num = 0;

	if (0 == applicationids_num)
		return;

	sql = zbx_malloc(sql, sql_alloc);

	zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset,
			"select applicationid,name"
			" from httptest"
			" where");
	DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "applicationid", applicationids, applicationids_num);

	result = DBselect("%s", sql);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(applicationid, row[0]);
		uint64_array_add(&ids, &ids_alloc, &ids_num, applicationid, 4);

		zabbix_log(LOG_LEVEL_DEBUG, "Application [" ZBX_FS_UI64 "] used by scenario '%s'",
				applicationid, row[1]);
	}
	DBfree_result(result);

	uint64_array_remove(applicationids, &applicationids_num, ids, ids_num);

	sql_offset = 0;
	DBbegin_multiple_update(&sql, &sql_alloc, &sql_offset);

	if (0 != applicationids_num)
	{
		zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset, "delete from applications where");
		DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset,
				"applicationid", applicationids, applicationids_num);
		zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset, ";\n");
	}

	if (0 != ids_num)
	{
		zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset, "update applications set templateid=null where");
		DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "applicationid", ids, ids_num);
		zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset, ";\n");
	}

	DBend_multiple_update(&sql, &sql_alloc, &sql_offset);

	DBexecute("%s", sql);

	zbx_free(ids);
	zbx_free(sql);
}

/******************************************************************************
 *                                                                            *
 * Function: DBdelete_template_graphs                                         *
 *                                                                            *
 * Purpose: delete template graphs from host                                  *
 *                                                                            *
 * Parameters: hostid - host identificator from database                      *
 *             templateid - template identificator from database              *
 *                                                                            *
 * Author: Eugene Grigorjev                                                   *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static void	DBdelete_template_graphs(zbx_uint64_t hostid, zbx_uint64_t templateid)
{
	const char		*__function_name = "DBdelete_template_graphs";
	DB_RESULT		result;
	DB_ROW			row;
	zbx_vector_uint64_t	graphids;
	zbx_uint64_t		graphid;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	zbx_vector_uint64_create(&graphids);

	result = DBselect(
			"select distinct gi.graphid"
			" from graphs_items gi,items i,items ti"
			" where gi.itemid=i.itemid"
				" and i.templateid=ti.itemid"
				" and i.hostid=" ZBX_FS_UI64
				" and ti.hostid=" ZBX_FS_UI64,
			hostid, templateid);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(graphid, row[0]);
		zbx_vector_uint64_append(&graphids, graphid);
	}
	DBfree_result(result);

	zbx_vector_uint64_sort(&graphids, ZBX_DEFAULT_UINT64_COMPARE_FUNC);
	DBdelete_graphs(&graphids);

	zbx_vector_uint64_destroy(&graphids);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

/******************************************************************************
 *                                                                            *
 * Function: DBdelete_template_triggers                                       *
 *                                                                            *
 * Purpose: delete template triggers from host                                *
 *                                                                            *
 * Parameters: hostid - host identificator from database                      *
 *             templateid - template identificator from database              *
 *                                                                            *
 * Author: Eugene Grigorjev                                                   *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static void	DBdelete_template_triggers(zbx_uint64_t hostid, zbx_uint64_t templateid)
{
	const char	*__function_name = "DBdelete_template_triggers";
	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	*triggerids = NULL, triggerid;
	int		triggerids_alloc = 0, triggerids_num = 0;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	result = DBselect(
			"select distinct f.triggerid"
			" from functions f,items i,items ti"
			" where f.itemid=i.itemid"
				" and i.templateid=ti.itemid"
				" and i.hostid=" ZBX_FS_UI64
				" and ti.hostid=" ZBX_FS_UI64,
			hostid, templateid);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(triggerid, row[0]);
		uint64_array_add(&triggerids, &triggerids_alloc, &triggerids_num, triggerid, 64);
	}
	DBfree_result(result);

	DBdelete_triggers(&triggerids, &triggerids_alloc, &triggerids_num);

	zbx_free(triggerids);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

/******************************************************************************
 *                                                                            *
 * Function: DBdelete_template_items                                          *
 *                                                                            *
 * Purpose: delete template items from host                                   *
 *                                                                            *
 * Parameters: hostid - host identificator from database                      *
 *             templateid - template identificator from database              *
 *                                                                            *
 * Author: Eugene Grigorjev                                                   *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static void	DBdelete_template_items(zbx_uint64_t hostid, zbx_uint64_t templateid)
{
	DB_RESULT		result;
	DB_ROW			row;
	zbx_uint64_t		itemid;
	zbx_vector_uint64_t	itemids;

	zbx_vector_uint64_create(&itemids);

	result = DBselect(
			"select distinct i.itemid"
			" from items i,items ti"
			" where i.templateid=ti.itemid"
				" and i.hostid=" ZBX_FS_UI64
				" and ti.hostid=" ZBX_FS_UI64,
			hostid, templateid);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(itemid, row[0]);
		zbx_vector_uint64_append(&itemids, itemid);
	}
	DBfree_result(result);

	zbx_vector_uint64_sort(&itemids, ZBX_DEFAULT_UINT64_COMPARE_FUNC);
	DBdelete_items(&itemids);

	zbx_vector_uint64_destroy(&itemids);
}

/******************************************************************************
 *                                                                            *
 * Function: DBdelete_template_applications                                   *
 *                                                                            *
 * Purpose: delete application                                                *
 *                                                                            *
 * Parameters: hostid - host identificator from database                      *
 *             templateid - template identificator from database              *
 *                                                                            *
 * Author: Eugene Grigorjev                                                   *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static void	DBdelete_template_applications(zbx_uint64_t hostid,
		zbx_uint64_t templateid)
{
	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	*applicationids = NULL, applicationid;
	int		applicationids_alloc = 0, applicationids_num = 0;

	result = DBselect(
			"select distinct a.applicationid"
			" from applications a,applications ta"
			" where a.templateid=ta.applicationid"
				" and a.hostid=" ZBX_FS_UI64
				" and ta.hostid=" ZBX_FS_UI64,
			hostid, templateid);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(applicationid, row[0]);
		uint64_array_add(&applicationids, &applicationids_alloc, &applicationids_num,
				applicationid, 64);
	}
	DBfree_result(result);

	DBdelete_applications(applicationids, applicationids_num);

	zbx_free(applicationids);
}

/******************************************************************************
 *                                                                            *
 * Function: DBcopy_trigger_to_host                                           *
 *                                                                            *
 * Purpose: copy specified trigger to host                                    *
 *                                                                            *
 * Parameters: hostid - host identificator from database                      *
 *             triggerid - trigger identificator from database                *
 *             description - trigger description                              *
 *             expression - trigger expression                                *
 *             status - trigger status                                        *
 *             type - trigger type                                            *
 *             priority - trigger priority                                    *
 *             comments - trigger comments                                    *
 *             url - trigger url                                              *
 *                                                                            *
 * Return value: upon successful completion return SUCCEED                    *
 *                                                                            *
 * Author: Eugene Grigorjev, Alexander Vladishev                              *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static int	DBcopy_trigger_to_host(zbx_uint64_t *new_triggerid, zbx_uint64_t hostid,
		zbx_uint64_t triggerid, const char *description, const char *expression,
		unsigned char status, unsigned char type, unsigned char priority,
		const char *comments, const char *url, unsigned char flags)
{
	DB_RESULT	result;
	DB_ROW		row;
	char		*sql = NULL;
	size_t		sql_alloc = 256, sql_offset = 0;
	zbx_uint64_t	itemid,	h_triggerid, functionid;
	char		*old_expression = NULL,
			*new_expression = NULL,
			*expression_esc = NULL,
			*search = NULL,
			*replace = NULL,
			*description_esc = NULL,
			*comments_esc = NULL,
			*url_esc = NULL,
			*function_esc = NULL,
			*parameter_esc = NULL;
	int		res = FAIL;

	sql = zbx_malloc(sql, sql_alloc);

	DBbegin_multiple_update(&sql, &sql_alloc, &sql_offset);

	description_esc = DBdyn_escape_string(description);

	result = DBselect(
			"select distinct t.triggerid,t.expression"
			" from triggers t,functions f,items i"
			" where t.triggerid=f.triggerid"
				" and f.itemid=i.itemid"
				" and t.templateid is null"
				" and i.hostid=" ZBX_FS_UI64
				" and t.description='%s'",
			hostid, description_esc);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(h_triggerid, row[0]);

		if (SUCCEED != DBcmp_triggers(triggerid, expression,
				h_triggerid, row[1]))
			continue;

		/* link not linked trigger with same description and expression */
		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
				"update triggers"
				" set templateid=" ZBX_FS_UI64 ","
					"flags=%d"
				" where triggerid=" ZBX_FS_UI64 ";\n",
				triggerid, (int)flags, h_triggerid);

		res = SUCCEED;
		break;
	}
	DBfree_result(result);

	/* create trigger if no updated triggers */
	if (SUCCEED != res)
	{
		res = SUCCEED;

		*new_triggerid = DBget_maxid("triggers");
		new_expression = strdup(expression);

		comments_esc = DBdyn_escape_string(comments);
		url_esc = DBdyn_escape_string(url);

		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
				"insert into triggers"
					" (triggerid,description,priority,status,"
						"comments,url,type,value,value_flags,templateid,flags)"
					" values (" ZBX_FS_UI64 ",'%s',%d,%d,"
						"'%s','%s',%d,%d,%d," ZBX_FS_UI64 ",%d);\n",
					*new_triggerid, description_esc, (int)priority,
					(int)status, comments_esc, url_esc, (int)type,
					TRIGGER_VALUE_FALSE, TRIGGER_VALUE_FLAG_UNKNOWN, triggerid, (int)flags);

		zbx_free(url_esc);
		zbx_free(comments_esc);

		/* Loop: functions */
		result = DBselect(
				"select hi.itemid,tf.functionid,tf.function,tf.parameter,ti.key_"
				" from functions tf,items ti"
				" left join items hi"
					" on hi.key_=ti.key_"
						" and hi.hostid=" ZBX_FS_UI64
				" where tf.itemid=ti.itemid"
					" and tf.triggerid=" ZBX_FS_UI64,
				hostid, triggerid);

		while (SUCCEED == res && NULL != (row = DBfetch(result)))
		{
			if (SUCCEED != DBis_null(row[0]))
			{
				ZBX_STR2UINT64(itemid, row[0]);

				functionid = DBget_maxid("functions");

				search = zbx_dsprintf(NULL, "{%s}", row[1]);
				replace = zbx_dsprintf(NULL, "{" ZBX_FS_UI64 "}", functionid);

				function_esc = DBdyn_escape_string(row[2]);
				parameter_esc = DBdyn_escape_string(row[3]);

				zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
						"insert into functions"
						" (functionid,itemid,triggerid,function,parameter)"
						" values (" ZBX_FS_UI64 "," ZBX_FS_UI64 ","
							ZBX_FS_UI64 ",'%s','%s');\n",
						functionid, itemid, *new_triggerid,
						function_esc, parameter_esc);

				old_expression = new_expression;
				new_expression = string_replace(old_expression, search, replace);

				zbx_free(old_expression);
				zbx_free(parameter_esc);
				zbx_free(function_esc);
				zbx_free(replace);
				zbx_free(search);
			}
			else
			{
				zabbix_log(LOG_LEVEL_DEBUG, "Missing similar key '%s'"
						" for host [" ZBX_FS_UI64 "]",
						row[4], hostid);
				res = FAIL;
			}
		}
		DBfree_result(result);

		if (SUCCEED == res)
		{
			expression_esc = DBdyn_escape_string_len(new_expression, TRIGGER_EXPRESSION_LEN);

			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
					"update triggers set expression='%s' where triggerid=" ZBX_FS_UI64 ";\n",
					expression_esc, *new_triggerid);

			zbx_free(expression_esc);
		}

		zbx_free(new_expression);
	}
	else
		*new_triggerid = 0;

	DBend_multiple_update(&sql, &sql_alloc, &sql_offset);

	if (sql_offset > 16)	/* In ORACLE always present begin..end; */
		DBexecute("%s", sql);

	zbx_free(sql);
	zbx_free(description_esc);

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: DBadd_template_dependencies_for_new_triggers                     *
 *                                                                            *
 * Purpose: update trigger dependencies for specified host                    *
 *                                                                            *
 * Parameters: trids - array of trigger identifiers from database             *
 *             trids_num - trigger count in trids array                       *
 *                                                                            *
 * Return value: upon successful completion return SUCCEED                    *
 *                                                                            *
 * Author: Eugene Grigorjev                                                   *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static int	DBadd_template_dependencies_for_new_triggers(zbx_uint64_t *trids, int trids_num)
{
	DB_RESULT	result;
	DB_ROW		row;
	int		alloc = 16, count = 0, i;
	zbx_uint64_t	*hst_triggerids = NULL, *tpl_triggerids = NULL,
			templateid, triggerid,
			templateid_down, templateid_up,
			triggerid_down, triggerid_up,
			triggerdepid;
	char		*sql = NULL;
	size_t		sql_alloc = 512, sql_offset;

	if (0 == trids_num)
		return SUCCEED;

	sql = zbx_malloc(sql, sql_alloc * sizeof(char));
	tpl_triggerids = zbx_malloc(tpl_triggerids, alloc * sizeof(zbx_uint64_t));
	hst_triggerids = zbx_malloc(hst_triggerids, alloc * sizeof(zbx_uint64_t));

	sql_offset = 0;
	zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset,
			"select triggerid,templateid"
			" from triggers"
			" where");
	DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "triggerid", trids, trids_num);

	result = DBselect("%s", sql);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(triggerid, row[0]);
		ZBX_DBROW2UINT64(templateid, row[1]);

		if (alloc == count)
		{
			alloc += 16;
			hst_triggerids = zbx_realloc(hst_triggerids, alloc * sizeof(zbx_uint64_t));
			tpl_triggerids = zbx_realloc(tpl_triggerids, alloc * sizeof(zbx_uint64_t));
		}

		hst_triggerids[count] = triggerid;
		tpl_triggerids[count] = templateid;
		count++;
	}
	DBfree_result(result);

	sql_offset = 0;
	zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset,
			"select distinct td.triggerid_down,td.triggerid_up"
			" from triggers t,trigger_depends td"
			" where t.templateid in (td.triggerid_up,td.triggerid_down)"
				" and");
	DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "t.triggerid", trids, trids_num);

	result = DBselect("%s", sql);

	sql_offset = 0;
	DBbegin_multiple_update(&sql, &sql_alloc, &sql_offset);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(templateid_down, row[0]);
		ZBX_STR2UINT64(templateid_up, row[1]);

		triggerid_down = 0;
		triggerid_up = templateid_up;

		for (i = 0; i < count; i++)
		{
			if (tpl_triggerids[i] == templateid_down)
				triggerid_down = hst_triggerids[i];
			if (tpl_triggerids[i] == templateid_up)
				triggerid_up = hst_triggerids[i];
		}

		if (0 != triggerid_down)
		{
			triggerdepid = DBget_maxid("trigger_depends");

			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
					"insert into trigger_depends"
					" (triggerdepid,triggerid_down,triggerid_up)"
					" values (" ZBX_FS_UI64 "," ZBX_FS_UI64 "," ZBX_FS_UI64 ");\n",
					triggerdepid, triggerid_down, triggerid_up);
		}
	}
	DBfree_result(result);

	DBend_multiple_update(&sql, &sql_alloc, &sql_offset);

	if (sql_offset > 16)	/* In ORACLE always present begin..end; */
		DBexecute("%s", sql);

	zbx_free(hst_triggerids);
	zbx_free(tpl_triggerids);
	zbx_free(sql);

	return SUCCEED;
}

/******************************************************************************
 *                                                                            *
 * Function: DBdelete_template_elements                                       *
 *                                                                            *
 * Purpose: delete template elements from host                                *
 *                                                                            *
 * Parameters: hostid - host identificator from database                      *
 *             templateid - template identificator from database              *
 *                                                                            *
 * Author: Eugene Grigorjev                                                   *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
int	DBdelete_template_elements(zbx_uint64_t hostid, zbx_uint64_t templateid)
{
	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	hosttemplateid = 0;

	result = DBselect("select hosttemplateid from hosts_templates"
			" where hostid=" ZBX_FS_UI64
				" and templateid=" ZBX_FS_UI64,
			hostid, templateid);

	if (NULL != (row = DBfetch(result)))
		ZBX_STR2UINT64(hosttemplateid, row[0]);
	DBfree_result(result);

	if (0 == hosttemplateid)
		return SUCCEED;

	DBdelete_template_graphs(hostid, templateid);
	DBdelete_template_triggers(hostid, templateid);
	DBdelete_template_items(hostid, templateid);
	DBdelete_template_applications(hostid, templateid);

	DBexecute("delete from hosts_templates"
			" where hosttemplateid=" ZBX_FS_UI64,
			hosttemplateid);

	return SUCCEED;
}

/******************************************************************************
 *                                                                            *
 * Function: DBcopy_template_applications                                     *
 *                                                                            *
 * Purpose: copy applications from template to host                           *
 *                                                                            *
 * Parameters: hostid - host identificator from database                      *
 *             templateid - template identificator from database              *
 *                                                                            *
 * Return value: upon successful completion return SUCCEED                    *
 *                                                                            *
 * Author: Eugene Grigorjev                                                   *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static int	DBcopy_template_applications(zbx_uint64_t hostid,
		zbx_uint64_t templateid)
{
	const char	*__function_name = "DBcopy_template_applications";
	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	template_applicationid, applicationid;
	char		*name_esc;
	char		*sql = NULL;
	size_t		sql_alloc = ZBX_KIBIBYTE, sql_offset = 0;
	int		res = SUCCEED;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	result = DBselect(
			"select ta.applicationid,ta.name,ha.applicationid"
			" from applications ta"
			" left join applications ha"
				" on ha.name=ta.name"
					" and ha.hostid=" ZBX_FS_UI64
			" where ta.hostid=" ZBX_FS_UI64,
			hostid, templateid);

	sql = zbx_malloc(sql, sql_alloc);

	DBbegin_multiple_update(&sql, &sql_alloc, &sql_offset);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(template_applicationid, row[0]);

		if (SUCCEED != DBis_null(row[2]))
		{
			ZBX_STR2UINT64(applicationid, row[2]);

			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
					"update applications"
					" set templateid=" ZBX_FS_UI64
					" where applicationid=" ZBX_FS_UI64 ";\n",
					template_applicationid,
					applicationid);
		}
		else
		{
			applicationid = DBget_maxid("applications");

			name_esc = DBdyn_escape_string(row[1]);

			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
					"insert into applications"
						" (applicationid,hostid,name,templateid)"
					" values"
						" (" ZBX_FS_UI64 "," ZBX_FS_UI64 ",'%s'," ZBX_FS_UI64 ");\n",
					applicationid, hostid, name_esc, template_applicationid);

			zbx_free(name_esc);
		}
	}
	DBfree_result(result);

	DBend_multiple_update(&sql, &sql_alloc, &sql_offset);

	if (sql_offset > 16)	/* In ORACLE always present begin..end; */
		DBexecute("%s", sql);

	zbx_free(sql);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: DBcopy_template_items                                            *
 *                                                                            *
 * Purpose: copy template items to host                                       *
 *                                                                            *
 * Parameters: hostid - host identificator from database                      *
 *             templateid - template identificator from database              *
 *                                                                            *
 * Return value: upon successful completion return SUCCEED                    *
 *                                                                            *
 * Author: Eugene Grigorjev                                                   *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static int	DBcopy_template_items(zbx_uint64_t hostid, zbx_uint64_t templateid)
{
	const char	*__function_name = "DBcopy_template_items";
	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	template_itemid, itemid, itemappid, valuemapid;
	char		*name_esc, *key_esc, *delay_flex_esc, *trapper_hosts_esc,
			*units_esc, *formula_esc, *logtimefmt_esc, *params_esc,
			*ipmi_sensor_esc, *snmp_community_esc, *snmp_oid_esc,
			*snmpv3_securityname_esc, *snmpv3_authpassphrase_esc,
			*snmpv3_privpassphrase_esc, *username_esc, *password_esc,
			*publickey_esc, *privatekey_esc, *filter_esc, *description_esc;
	char		*sql = NULL;
	size_t		sql_alloc = 16 * ZBX_KIBIBYTE, sql_offset = 0;
	int		i, res = SUCCEED;
	zbx_uint64_t	*appids = NULL, *protoids = NULL;
	int		appids_alloc = 0, appids_num,
			protoids_alloc = 0, protoids_num = 0;
	unsigned char	flags, type, itype;
	zbx_uint64_t	interfaceids[4], interfaceid;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	memset(&interfaceids, 0, sizeof(interfaceids));

	result = DBselect(
			"select type,interfaceid"
			" from interface"
			" where hostid=" ZBX_FS_UI64
				" and type in (%d,%d,%d,%d)"
				" and main=1"
				DB_NODE,
			hostid, INTERFACE_TYPE_AGENT, INTERFACE_TYPE_SNMP,
			INTERFACE_TYPE_IPMI, INTERFACE_TYPE_JMX, DBnode_local("interfaceid"));

	while (NULL != (row = DBfetch(result)))
	{
		type = (unsigned char)atoi(row[0]);
		ZBX_STR2UINT64(interfaceids[type - 1], row[1]);
	}
	DBfree_result(result);

	result = DBselect(
			"select ti.itemid,ti.name,ti.key_,ti.type,ti.value_type,"
				"ti.data_type,ti.delay,ti.delay_flex,ti.history,ti.trends,"
				"ti.status,ti.trapper_hosts,ti.units,ti.multiplier,"
				"ti.delta,ti.formula,ti.logtimefmt,ti.valuemapid,"
				"ti.params,ti.ipmi_sensor,ti.snmp_community,ti.snmp_oid,"
				"ti.snmpv3_securityname,ti.snmpv3_securitylevel,"
				"ti.snmpv3_authpassphrase,ti.snmpv3_privpassphrase,"
				"ti.authtype,ti.username,ti.password,ti.publickey,"
				"ti.privatekey,ti.flags,ti.filter,ti.description,"
				"ti.inventory_link,hi.itemid"
			" from items ti"
			" left join items hi on hi.key_=ti.key_"
				" and hi.hostid=" ZBX_FS_UI64
			" where ti.hostid=" ZBX_FS_UI64,
			hostid, templateid);

	sql = zbx_malloc(sql, sql_alloc);

	DBbegin_multiple_update(&sql, &sql_alloc, &sql_offset);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(template_itemid, row[0]);
		type = (unsigned char)atoi(row[3]);
		ZBX_DBROW2UINT64(valuemapid, row[17]);

		name_esc			= DBdyn_escape_string(row[1]);
		delay_flex_esc			= DBdyn_escape_string(row[7]);
		trapper_hosts_esc		= DBdyn_escape_string(row[11]);
		units_esc			= DBdyn_escape_string(row[12]);
		formula_esc			= DBdyn_escape_string(row[15]);
		logtimefmt_esc			= DBdyn_escape_string(row[16]);
		params_esc			= DBdyn_escape_string(row[18]);
		ipmi_sensor_esc			= DBdyn_escape_string(row[19]);
		snmp_community_esc		= DBdyn_escape_string(row[20]);
		snmp_oid_esc			= DBdyn_escape_string(row[21]);
		snmpv3_securityname_esc		= DBdyn_escape_string(row[22]);
		snmpv3_authpassphrase_esc	= DBdyn_escape_string(row[24]);
		snmpv3_privpassphrase_esc	= DBdyn_escape_string(row[25]);
		username_esc			= DBdyn_escape_string(row[27]);
		password_esc			= DBdyn_escape_string(row[28]);
		publickey_esc			= DBdyn_escape_string(row[29]);
		privatekey_esc			= DBdyn_escape_string(row[30]);
		flags				= (unsigned char)atoi(row[31]);
		filter_esc			= DBdyn_escape_string(row[32]);
		description_esc			= DBdyn_escape_string(row[33]);

		switch (itype = get_interface_type_by_item_type(type))
		{
			case INTERFACE_TYPE_UNKNOWN:
				interfaceid = 0;
				break;
			case INTERFACE_TYPE_ANY:
				for (i = 0; INTERFACE_TYPE_COUNT > i; i++)
				{
					if (0 != interfaceids[INTERFACE_TYPE_PRIORITY[i] - 1])
						break;
				}

				interfaceid = interfaceids[INTERFACE_TYPE_PRIORITY[i] - 1];

				break;
			default:
				interfaceid = interfaceids[itype - 1];
		}

		if (SUCCEED != (DBis_null(row[35])))
		{
			ZBX_STR2UINT64(itemid, row[35]);

			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
					"update items"
						" set name='%s',"
						"type=%d,"
						"value_type=%s,"
						"data_type=%s,"
						"delay=%s,"
						"delay_flex='%s',"
						"history=%s,"
						"trends=%s,"
						"status=%s,"
						"trapper_hosts='%s',"
						"units='%s',"
						"multiplier=%s,"
						"delta=%s,"
						"formula='%s',"
						"logtimefmt='%s',"
						"valuemapid=%s,"
						"params='%s',"
						"ipmi_sensor='%s',"
						"snmp_community='%s',"
						"snmp_oid='%s',"
						"snmpv3_securityname='%s',"
						"snmpv3_securitylevel=%s,"
						"snmpv3_authpassphrase='%s',"
						"snmpv3_privpassphrase='%s',"
						"authtype=%s,"
						"username='%s',"
						"password='%s',"
						"publickey='%s',"
						"privatekey='%s',"
						"templateid=" ZBX_FS_UI64 ","
						"flags=%d,"
						"filter='%s',"
						"description='%s',"
						"inventory_link=%s,"
						"interfaceid=%s"
					" where itemid=" ZBX_FS_UI64 ";\n",
					name_esc,
					(int)type,
					row[4],		/* value_type */
					row[5],		/* data_type */
					row[6],		/* delay */
					delay_flex_esc,
					row[8],		/* history */
					row[9],		/* trends */
					row[10],	/* status */
					trapper_hosts_esc,
					units_esc,
					row[13],	/* multiplier */
					row[14],	/* delta */
					formula_esc,
					logtimefmt_esc,
					DBsql_id_ins(valuemapid),
					params_esc,
					ipmi_sensor_esc,
					snmp_community_esc,
					snmp_oid_esc,
					snmpv3_securityname_esc,
					row[23],	/* snmpv3_securitylevel */
					snmpv3_authpassphrase_esc,
					snmpv3_privpassphrase_esc,
					row[26],	/* authtype */
					username_esc,
					password_esc,
					publickey_esc,
					privatekey_esc,
					template_itemid,
					(int)flags,
					filter_esc,
					description_esc,
					row[34],	/* inventory_link */
					DBsql_id_ins(interfaceid),
					itemid);
		}
		else
		{
			itemid = DBget_maxid("items");

			key_esc = DBdyn_escape_string(row[2]);

			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
					"insert into items"
						" (itemid,name,key_,hostid,type,value_type,data_type,"
						"delay,delay_flex,history,trends,status,trapper_hosts,units,"
						"multiplier,delta,formula,logtimefmt,valuemapid,params,"
						"ipmi_sensor,snmp_community,snmp_oid,"
						"snmpv3_securityname,snmpv3_securitylevel,"
						"snmpv3_authpassphrase,snmpv3_privpassphrase,"
						"authtype,username,password,publickey,privatekey,templateid,"
						"flags,filter,description,inventory_link,interfaceid)"
					" values"
						" (" ZBX_FS_UI64 ",'%s','%s'," ZBX_FS_UI64 ",%d,%s,%s,"
						"%s,'%s',%s,%s,%s,'%s','%s',%s,%s,'%s','%s',%s,'%s','%s',"
						"'%s','%s','%s',%s,'%s','%s',%s,'%s','%s','%s',"
						"'%s'," ZBX_FS_UI64 ",%d,'%s','%s',%s,%s);\n",
					itemid,
					name_esc,
					key_esc,
					hostid,
					(int)type,
					row[4],		/* value_type */
					row[5],		/* data_type */
					row[6],		/* delay */
					delay_flex_esc,
					row[8],		/* history */
					row[9],		/* trends */
					row[10],	/* status */
					trapper_hosts_esc,
					units_esc,
					row[13],	/* multiplier */
					row[14],	/* delta */
					formula_esc,
					logtimefmt_esc,
					DBsql_id_ins(valuemapid),
					params_esc,
					ipmi_sensor_esc,
					snmp_community_esc,
					snmp_oid_esc,
					snmpv3_securityname_esc,
					row[23],	/* snmpv3_securitylevel */
					snmpv3_authpassphrase_esc,
					snmpv3_privpassphrase_esc,
					row[26],	/* authtype */
					username_esc,
					password_esc,
					publickey_esc,
					privatekey_esc,
					template_itemid,
					(int)flags,
					filter_esc,
					description_esc,
					row[34],	/* inventory_link */
					DBsql_id_ins(interfaceid));

			zbx_free(key_esc);

			if (0 != (ZBX_FLAG_DISCOVERY_CHILD & flags))
				uint64_array_add(&protoids, &protoids_alloc, &protoids_num, itemid, 64);
		}

		DBexecute_overflowed_sql(&sql, &sql_alloc, &sql_offset);

		zbx_free(description_esc);
		zbx_free(filter_esc);
		zbx_free(privatekey_esc);
		zbx_free(publickey_esc);
		zbx_free(password_esc);
		zbx_free(username_esc);
		zbx_free(snmpv3_privpassphrase_esc);
		zbx_free(snmpv3_authpassphrase_esc);
		zbx_free(snmpv3_securityname_esc);
		zbx_free(snmp_oid_esc);
		zbx_free(snmp_community_esc);
		zbx_free(ipmi_sensor_esc);
		zbx_free(params_esc);
		zbx_free(logtimefmt_esc);
		zbx_free(formula_esc);
		zbx_free(units_esc);
		zbx_free(trapper_hosts_esc);
		zbx_free(delay_flex_esc);
		zbx_free(name_esc);

		appids_num = 0;

		if (SUCCEED == DBget_same_applications_by_itemid(hostid, itemid, template_itemid,
				&appids, &appids_alloc, &appids_num))
		{
			itemappid = DBget_maxid_num("items_applications", appids_num);

			for (i = 0; i < appids_num; i++)
			{
				zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
						"insert into items_applications"
						" (itemappid,itemid,applicationid)"
						" values"
						" (" ZBX_FS_UI64 "," ZBX_FS_UI64 "," ZBX_FS_UI64 ");\n",
						itemappid++, itemid, appids[i]);

				DBexecute_overflowed_sql(&sql, &sql_alloc, &sql_offset);
			}
		}
	}
	DBfree_result(result);

	DBend_multiple_update(&sql, &sql_alloc, &sql_offset);

	if (sql_offset > 16)	/* In ORACLE always present begin..end; */
		DBexecute("%s", sql);

	if (0 != protoids_num)
	{
		zbx_uint64_t	itemdiscoveryid;

		sql_offset = 0;
		zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset,
				"select i.itemid,r.itemid"
				" from items i,item_discovery id,items r"
				" where i.templateid=id.itemid"
					" and id.parent_itemid=r.templateid"
					" and");
		DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "i.itemid", protoids, protoids_num);

		result = DBselect("%s", sql);

		sql_offset = 0;
		DBbegin_multiple_update(&sql, &sql_alloc, &sql_offset);

		while (NULL != (row = DBfetch(result)))
		{
			itemdiscoveryid = DBget_maxid("item_discovery");

			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
					"insert into item_discovery"
						" (itemdiscoveryid,itemid,parent_itemid)"
					" values"
						" (" ZBX_FS_UI64 ",%s,%s);\n",
					itemdiscoveryid, row[0], row[1]);

			DBexecute_overflowed_sql(&sql, &sql_alloc, &sql_offset);
		}
		DBfree_result(result);

		DBend_multiple_update(&sql, &sql_alloc, &sql_offset);

		if (sql_offset > 16)	/* In ORACLE always present begin..end; */
			DBexecute("%s", sql);
	}

	zbx_free(sql);
	zbx_free(appids);
	zbx_free(protoids);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: DBcopy_template_triggers                                         *
 *                                                                            *
 * Purpose: Copy template triggers to host                                    *
 *                                                                            *
 * Parameters: hostid - host identificator from database                      *
 *             templateid - host template identificator from database         *
 *                                                                            *
 * Return value: upon successful completion return SUCCEED                    *
 *                                                                            *
 * Author: Eugene Grigorjev                                                   *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static int	DBcopy_template_triggers(zbx_uint64_t hostid, zbx_uint64_t templateid)
{
	const char	*__function_name = "DBcopy_template_triggers";
	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	triggerid, new_triggerid;
	int		res = SUCCEED;
	zbx_uint64_t	*trids = NULL;
	int		trids_alloc = 0, trids_num = 0;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	result = DBselect(
			"select distinct t.triggerid,t.description,t.expression,t.status,"
				"t.type,t.priority,t.comments,t.url,t.flags"
			" from triggers t,functions f,items i"
			" where i.hostid=" ZBX_FS_UI64
				" and f.itemid=i.itemid"
				" and f.triggerid=t.triggerid",
			templateid);

	while (SUCCEED == res && NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(triggerid, row[0]);

		res = DBcopy_trigger_to_host(&new_triggerid, hostid, triggerid,
				row[1],				/* description */
				row[2],				/* expression */
				(unsigned char)atoi(row[3]),	/* status */
				(unsigned char)atoi(row[4]),	/* type */
				(unsigned char)atoi(row[5]),	/* priority */
				row[6],				/* comments */
				row[7],				/* url */
				(unsigned char)atoi(row[8]));	/* flags */

		if (0 != new_triggerid)				/* new trigger added */
			uint64_array_add(&trids, &trids_alloc, &trids_num, new_triggerid, 64);
	}
	DBfree_result(result);

	if (SUCCEED == res)
		res = DBadd_template_dependencies_for_new_triggers(trids, trids_num);

	zbx_free(trids);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: DBget_same_itemid                                                *
 *                                                                            *
 * Purpose: get same itemid for selected host by itemid from template         *
 *                                                                            *
 * Parameters: hostid - host identificator from database                      *
 *             itemid - item identificator from database (from template)      *
 *                                                                            *
 * Return value: new item identificator or zero if item not found             *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static zbx_uint64_t	DBget_same_itemid(zbx_uint64_t hostid, zbx_uint64_t titemid)
{
	const char	*__function_name = "DBget_same_itemid";
	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	itemid;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() hostid:" ZBX_FS_UI64
			" titemid:" ZBX_FS_UI64,
			__function_name, hostid, titemid);

	result = DBselect(
			"select hi.itemid"
			" from items hi,items ti"
			" where hi.key_=ti.key_"
				" and hi.hostid=" ZBX_FS_UI64
				" and ti.itemid=" ZBX_FS_UI64,
			hostid, titemid);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(itemid, row[0]);
	}
	DBfree_result(result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():" ZBX_FS_UI64,
			__function_name, itemid);

	return itemid;
}

/******************************************************************************
 *                                                                            *
 * Function: DBcopy_graph_to_host                                             *
 *                                                                            *
 * Purpose: copy specified graph to host                                      *
 *                                                                            *
 * Parameters: graphid - graph identificator from database                    *
 *             hostid - host identificator from database                      *
 *                                                                            *
 * Return value: upon successful completion return SUCCEED                    *
 *                                                                            *
 * Author: Eugene Grigorjev, Alexander Vladishev                              *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static int	DBcopy_graph_to_host(zbx_uint64_t hostid, zbx_uint64_t graphid,
		const char *name, int width, int height, double yaxismin,
		double yaxismax, unsigned char show_work_period,
		unsigned char show_triggers, unsigned char graphtype,
		unsigned char show_legend, unsigned char show_3d,
		double percent_left, double percent_right,
		unsigned char ymin_type, unsigned char ymax_type,
		zbx_uint64_t ymin_itemid, zbx_uint64_t ymax_itemid,
		unsigned char flags)
{
	const char	*__function_name = "DBcopy_graph_to_host";
	DB_RESULT	result;
	DB_ROW		row;
	ZBX_GRAPH_ITEMS *gitems = NULL, *chd_gitems = NULL;
	size_t		gitems_alloc = 0, gitems_num = 0,
			chd_gitems_alloc = 0, chd_gitems_num = 0;
	int		i, res = SUCCEED;
	zbx_uint64_t	hst_graphid, hst_gitemid;
	char		*sql = NULL, *name_esc, *color_esc;
	size_t		sql_alloc = ZBX_KIBIBYTE, sql_offset;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	sql = zbx_malloc(sql, sql_alloc * sizeof(char));

	name_esc = DBdyn_escape_string(name);

	sql_offset = 0;
	zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
			"select 0,dst.itemid,dst.key_,gi.drawtype,gi.sortorder,gi.color,gi.yaxisside,gi.calc_fnc,"
				"gi.type,i.flags"
			" from graphs_items gi,items i,items dst"
			" where gi.itemid=i.itemid"
				" and i.key_=dst.key_"
				" and gi.graphid=" ZBX_FS_UI64
				" and dst.hostid=" ZBX_FS_UI64
			" order by dst.key_",
			graphid, hostid);

	DBget_graphitems(sql, &gitems, &gitems_alloc, &gitems_num);

	result = DBselect(
			"select distinct g.graphid"
			" from graphs g,graphs_items gi,items i"
			" where g.graphid=gi.graphid"
				" and gi.itemid=i.itemid"
				" and i.hostid=" ZBX_FS_UI64
				" and g.name='%s'"
				" and g.templateid is null",
			hostid, name_esc);

	/* compare graphs */
	hst_graphid = 0;
	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(hst_graphid, row[0]);

		sql_offset = 0;
		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
				"select gi.gitemid,i.itemid,i.key_,gi.drawtype,gi.sortorder,gi.color,gi.yaxisside,"
					"gi.calc_fnc,gi.type,i.flags"
				" from graphs_items gi,items i"
				" where gi.itemid=i.itemid"
					" and gi.graphid=" ZBX_FS_UI64
				" order by i.key_",
				hst_graphid);

		DBget_graphitems(sql, &chd_gitems, &chd_gitems_alloc, &chd_gitems_num);

		if (SUCCEED == DBcmp_graphitems(gitems, gitems_num, chd_gitems, chd_gitems_num))
			break;	/* found equal graph */

		hst_graphid = 0;
	}
	DBfree_result(result);

	sql_offset = 0;
	DBbegin_multiple_update(&sql, &sql_alloc, &sql_offset);

	if (GRAPH_YAXIS_TYPE_ITEM_VALUE == ymin_type)
		ymin_itemid = DBget_same_itemid(hostid, ymin_itemid);
	else
		ymin_itemid = 0;

	if (GRAPH_YAXIS_TYPE_ITEM_VALUE == ymax_type)
		ymax_itemid = DBget_same_itemid(hostid, ymax_itemid);
	else
		ymax_itemid = 0;

	if (0 != hst_graphid)
	{
		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
				"update graphs"
				" set name='%s',"
					"width=%d,"
					"height=%d,"
					"yaxismin=" ZBX_FS_DBL ","
					"yaxismax=" ZBX_FS_DBL ","
					"templateid=" ZBX_FS_UI64 ","
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
				graphid, (int)show_work_period, (int)show_triggers,
				(int)graphtype, (int)show_legend, (int)show_3d, 
				percent_left, percent_right, (int)ymin_type, (int)ymax_type,
				DBsql_id_ins(ymin_itemid), DBsql_id_ins(ymax_itemid), (int)flags,
				hst_graphid);

		for (i = 0; i < gitems_num; i++)
		{
			color_esc = DBdyn_escape_string(gitems[i].color);

			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
					"update graphs_items"
					" set drawtype=%d,"
						"sortorder=%d,"
						"color='%s',"
						"yaxisside=%d,"
						"calc_fnc=%d,"
						"type=%d"
					" where gitemid=" ZBX_FS_UI64 ";\n",
					gitems[i].drawtype,
					gitems[i].sortorder,
					color_esc,
					gitems[i].yaxisside,
					gitems[i].calc_fnc,
					gitems[i].type,
					chd_gitems[i].gitemid);

			zbx_free(color_esc);
		}
	}
	else
	{
		hst_graphid = DBget_maxid("graphs");

		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
				"insert into graphs"
				" (graphid,name,width,height,yaxismin,yaxismax,templateid,"
				"show_work_period,show_triggers,graphtype,show_legend,"
				"show_3d,percent_left,percent_right,ymin_type,ymax_type,"
				"ymin_itemid,ymax_itemid,flags)"
				" values (" ZBX_FS_UI64 ",'%s',%d,%d," ZBX_FS_DBL ","
				ZBX_FS_DBL "," ZBX_FS_UI64 ",%d,%d,%d,%d,%d," ZBX_FS_DBL ","
				ZBX_FS_DBL ",%d,%d,%s,%s,%d);\n",
				hst_graphid, name_esc, width, height, yaxismin, yaxismax,
				graphid, (int)show_work_period, (int)show_triggers,
				(int)graphtype, (int)show_legend, (int)show_3d,
				percent_left, percent_right, (int)ymin_type, (int)ymax_type,
				DBsql_id_ins(ymin_itemid), DBsql_id_ins(ymax_itemid), (int)flags);

		hst_gitemid = DBget_maxid_num("graphs_items", gitems_num);

		for (i = 0; i < gitems_num; i++)
		{
			color_esc = DBdyn_escape_string(gitems[i].color);

			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
					"insert into graphs_items (gitemid,graphid,itemid,drawtype,"
					"sortorder,color,yaxisside,calc_fnc,type)"
					" values (" ZBX_FS_UI64 "," ZBX_FS_UI64 "," ZBX_FS_UI64
					",%d,%d,'%s',%d,%d,%d);\n",
					hst_gitemid, hst_graphid, gitems[i].itemid,
					gitems[i].drawtype, gitems[i].sortorder, color_esc,
					gitems[i].yaxisside, gitems[i].calc_fnc, gitems[i].type);
			hst_gitemid++;

			zbx_free(color_esc);
		}
	}

	zbx_free(name_esc);

	DBend_multiple_update(&sql, &sql_alloc, &sql_offset);

	if (sql_offset > 16)	/* In ORACLE always present begin..end; */
		DBexecute("%s", sql);

	zbx_free(gitems);
	zbx_free(chd_gitems);
	zbx_free(sql);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s",
			__function_name, zbx_result_string(res));

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: DBcopy_template_graphs                                           *
 *                                                                            *
 * Purpose: copy graphs from template to host                                 *
 *                                                                            *
 * Parameters: hostid - host identificator from database                      *
 *             templateid - template identificator from database              *
 *                                                                            *
 * Return value: upon successful completion return SUCCEED                    *
 *                                                                            *
 * Author: Eugene Grigorjev                                                   *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
static int	DBcopy_template_graphs(zbx_uint64_t hostid, zbx_uint64_t templateid)
{
	const char	*__function_name = "DBcopy_template_graphs";
	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	graphid, ymin_itemid, ymax_itemid;
	int		res = SUCCEED;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	result = DBselect(
			"select distinct g.graphid,g.name,g.width,g.height,g.yaxismin,"
				"g.yaxismax,g.show_work_period,g.show_triggers,"
				"g.graphtype,g.show_legend,g.show_3d,g.percent_left,"
				"g.percent_right,g.ymin_type,g.ymax_type,g.ymin_itemid,"
				"g.ymax_itemid,g.flags"
			" from graphs g,graphs_items gi,items i"
			" where g.graphid=gi.graphid"
				" and gi.itemid=i.itemid"
				" and i.hostid=" ZBX_FS_UI64,
			templateid);

	while (SUCCEED == res && NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(graphid, row[0]);
		ZBX_DBROW2UINT64(ymin_itemid, row[15]);
		ZBX_DBROW2UINT64(ymax_itemid, row[16]);

		res = DBcopy_graph_to_host(hostid, graphid,
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
				(unsigned char)atoi(row[17]));	/* flags */
	}
	DBfree_result(result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s",
			__function_name, zbx_result_string(res));

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: get_templates_by_hostid                                          *
 *                                                                            *
 * Description: Retrieve already linked templates for specified host          *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static void	get_templates_by_hostid(zbx_uint64_t hostid,
		zbx_uint64_t **templateids, int *templateids_alloc,
		int *templateids_num)
{
	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	templateid;

	result = DBselect(
			"select templateid"
			" from hosts_templates"
			" where hostid=" ZBX_FS_UI64,
			hostid);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(templateid, row[0]);
		uint64_array_add(templateids, templateids_alloc,
				templateids_num, templateid, 16);
	}
	DBfree_result(result);
}

/******************************************************************************
 *                                                                            *
 * Function: DBcopy_template_elements                                         *
 *                                                                            *
 * Purpose: copy elements from specified template                             *
 *                                                                            *
 * Parameters: hostid - host identificator from database                      *
 *             templateid - template identificator from database              *
 *                                                                            *
 * Return value: upon successful completion return SUCCEED                    *
 *                                                                            *
 * Author: Eugene Grigorjev                                                   *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
int	DBcopy_template_elements(zbx_uint64_t hostid, zbx_uint64_t templateid)
{
	const char	*__function_name = "DBcopy_template_elements";
	zbx_uint64_t	*templateids = NULL, hosttemplateid;
	int		templateids_alloc = 0, templateids_num = 0,
			res = SUCCEED;
	char		error[MAX_STRING_LEN];

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	get_templates_by_hostid(hostid, &templateids, &templateids_alloc, &templateids_num);

	if (SUCCEED == uint64_array_exists(templateids, templateids_num, templateid))
		goto clean;	/* template already linked */

	uint64_array_add(&templateids, &templateids_alloc, &templateids_num, templateid, 1);

	if (SUCCEED != (res = validate_template(templateids, templateids_num, error, sizeof(error))))
	{
		zabbix_log(LOG_LEVEL_WARNING, "cannot link template '%s': %s", zbx_host_string(templateid), error);
		goto clean;
	}

	if (SUCCEED != (res = validate_host(hostid, templateid, error, sizeof(error))))
	{
		zabbix_log(LOG_LEVEL_WARNING, "cannot link template '%s': %s", zbx_host_string(templateid), error);
		goto clean;
	}

	hosttemplateid = DBget_maxid("hosts_templates");

	DBexecute("insert into hosts_templates (hosttemplateid,hostid,templateid)"
			" values (" ZBX_FS_UI64 "," ZBX_FS_UI64 "," ZBX_FS_UI64 ")",
			hosttemplateid, hostid, templateid);

	if (SUCCEED == (res = DBcopy_template_applications(hostid, templateid)))
		if (SUCCEED == (res = DBcopy_template_items(hostid, templateid)))
			if (SUCCEED == (res = DBcopy_template_triggers(hostid, templateid)))
				res = DBcopy_template_graphs(hostid, templateid);

clean:
	zbx_free(templateids);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: DBdelete_host                                                    *
 *                                                                            *
 * Purpose: delete host from database with all elements                       *
 *                                                                            *
 * Parameters: hostid - host identificator from database                      *
 *                                                                            *
 * Author: Eugene Grigorjev                                                   *
 *                                                                            *
 * Comments: !!! Don't forget sync code with PHP !!!                          *
 *                                                                            *
 ******************************************************************************/
void	DBdelete_host(zbx_uint64_t hostid)
{
	const char		*__function_name = "DBdelete_host";
	DB_RESULT		result;
	DB_ROW			row;
	zbx_uint64_t		elementid;
	zbx_vector_uint64_t	itemids, htids;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	zbx_vector_uint64_create(&htids);
	zbx_vector_uint64_create(&itemids);

	/* delete web tests */
	result = DBselect(
			"select distinct ht.httptestid"
			" from httptest ht,applications a"
			" where ht.applicationid=a.applicationid"
				" and a.hostid=" ZBX_FS_UI64,
			hostid);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(elementid, row[0]);
		zbx_vector_uint64_append(&htids, elementid);
	}
	DBfree_result(result);

	zbx_vector_uint64_sort(&htids, ZBX_DEFAULT_UINT64_COMPARE_FUNC);
	DBdelete_httptests(&htids);

	/* delete items -> triggers -> graphs */
	result = DBselect(
			"select itemid"
			" from items"
			" where hostid=" ZBX_FS_UI64,
			hostid);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(elementid, row[0]);
		zbx_vector_uint64_append(&itemids, elementid);
	}
	DBfree_result(result);

	zbx_vector_uint64_sort(&itemids, ZBX_DEFAULT_UINT64_COMPARE_FUNC);
	DBdelete_items(&itemids);

	zbx_vector_uint64_destroy(&itemids);
	zbx_vector_uint64_destroy(&htids);

	/* delete host from maps */
	DBdelete_sysmaps_elements(SYSMAP_ELEMENT_TYPE_HOST, &hostid, 1);

	/* delete action conditions */
	DBdelete_action_conditions(CONDITION_TYPE_HOST, hostid);

	/* delete host */
	DBexecute("delete from hosts where hostid=" ZBX_FS_UI64, hostid);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

/******************************************************************************
 *                                                                            *
 * Function: DBadd_interface                                                  *
 *                                                                            *
 * Purpose: add new interface to specified host                               *
 *                                                                            *
 * Parameters: hostid - [IN] host identificator from database                 *
 *             type   - [IN] new interface type                               *
 *             useip  - [IN] how to connect to the host 0/1 - DNS/IP          *
 *             ip     - [IN] IP address                                       *
 *             dns    - [IN] DNS address                                      *
 *             port   - [IN] port                                             *
 *                                                                            *
 * Return value: upon successful completion return interface identificator    *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
zbx_uint64_t	DBadd_interface(zbx_uint64_t hostid, unsigned char type,
		unsigned char useip, const char *ip, const char *dns, unsigned short port)
{
	const char	*__function_name = "DBadd_interface";

	DB_RESULT	result;
	DB_ROW		row;
	char		*ip_esc, *dns_esc, *tmp = NULL;
	zbx_uint64_t	interfaceid = 0;
	unsigned char	main_ = 1, db_main, db_useip;
	unsigned short	db_port;
	const char	*db_ip, *db_dns;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	result = DBselect(
			"select interfaceid,useip,ip,dns,port,main"
			" from interface"
			" where hostid=" ZBX_FS_UI64
				" and type=%d",
			hostid, (int)type);

	while (NULL != (row = DBfetch(result)))
	{
		db_useip = (unsigned char)atoi(row[1]);
		db_ip = row[2];
		db_dns = row[3];
		db_main = (unsigned char)atoi(row[5]);
		if (1 == db_main)
			main_ = 0;

		if (db_useip != useip)
			continue;

		if (useip && 0 != strcmp(db_ip, ip))
			continue;

		if (!useip && 0 != strcmp(db_dns, dns))
			continue;

		zbx_free(tmp);
		tmp = strdup(row[4]);
		substitute_simple_macros(NULL, &hostid, NULL, NULL,
				&tmp, MACRO_TYPE_INTERFACE_PORT, NULL, 0);
		if (FAIL == is_ushort(tmp, &db_port) || db_port != port)
			continue;

		ZBX_STR2UINT64(interfaceid, row[0]);
		break;
	}
	DBfree_result(result);

	zbx_free(tmp);

	if (0 != interfaceid)
		goto out;

	ip_esc = DBdyn_escape_string_len(ip, INTERFACE_IP_LEN);
	dns_esc = DBdyn_escape_string_len(dns, INTERFACE_DNS_LEN);

	interfaceid = DBget_maxid("interface");

	DBexecute("insert into interface"
			" (interfaceid,hostid,main,type,useip,ip,dns,port)"
		" values"
			" (" ZBX_FS_UI64 "," ZBX_FS_UI64 ",%d,%d,%d,'%s','%s',%d)",
		interfaceid, hostid, (int)main_, (int)type, (int)useip, ip_esc, dns_esc, (int)port);

	zbx_free(dns_esc);
	zbx_free(ip_esc);
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():" ZBX_FS_UI64, __function_name, interfaceid);

	return interfaceid;
}
