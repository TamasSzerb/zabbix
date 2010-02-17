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
#include "zlog.h"
#include "zbxserver.h"

#include "evalfunc.h"

static const char	*get_table_by_value_type(int value_type)
{
	switch (value_type) {
	case ITEM_VALUE_TYPE_FLOAT: return "history"; break;
	case ITEM_VALUE_TYPE_UINT64: return "history_uint"; break;
	case ITEM_VALUE_TYPE_STR: return "history_str"; break;
	case ITEM_VALUE_TYPE_TEXT: return "history_text"; break;
	case ITEM_VALUE_TYPE_LOG: return "history_log"; break;
	default:
		return NULL;
	}
}

static const char	*get_key_by_value_type(int value_type)
{
	switch (value_type) {
	case ITEM_VALUE_TYPE_FLOAT:
	case ITEM_VALUE_TYPE_UINT64:
	case ITEM_VALUE_TYPE_STR: return "clock"; break;
	case ITEM_VALUE_TYPE_TEXT:
	case ITEM_VALUE_TYPE_LOG: return "id"; break;
	default:
		return NULL;
	}
}

static int	get_function_parameter_uint(DB_ITEM *item, const char *parameters, int Nparam, int *value, int *flag)
{
	const char	*__function_name = "get_function_parameter_uint";
	char		*parameter = NULL;
	int		res = FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() parameters:'%s' Nparam:%d", __function_name, parameters, Nparam);

	parameter = zbx_malloc(parameter, FUNCTION_PARAMETER_LEN_MAX);

	if (0 != get_param(parameters, Nparam, parameter, FUNCTION_PARAMETER_LEN_MAX))
		goto clean;

	if (SUCCEED == substitute_simple_macros(NULL, NULL, item, NULL, NULL, NULL, &parameter, MACRO_TYPE_FUNCTION_PARAMETER, NULL, 0))
	{
		if ('#' == *parameter)
		{
			*flag = ZBX_FLAG_VALUES;
			if (SUCCEED == is_uint(parameter + 1))
			{
				sscanf(parameter + 1, "%u", value);
				res = SUCCEED;
			}
		}
		else if (SUCCEED == is_uint(parameter))
		{
			*flag = ZBX_FLAG_SEC;
			sscanf(parameter, "%u", value);
			res = SUCCEED;
		}
	}

	if (res == SUCCEED)
		zabbix_log(LOG_LEVEL_DEBUG, "%s() flag:%d value:%d", __function_name, *flag, *value);
clean:
	zbx_free(parameter);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

static int	get_function_parameter_str(DB_ITEM *item, const char *parameters, int Nparam, char **value)
{
	const char	*__function_name = "get_function_parameter_str";
	int		res = FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() parameters:'%s' Nparam:%d", __function_name, parameters, Nparam);

	*value = zbx_malloc(*value, FUNCTION_PARAMETER_LEN_MAX);

	if (0 != get_param(parameters, Nparam, *value, FUNCTION_PARAMETER_LEN_MAX))
	{
		zbx_free(*value);
		goto clean;
	}

	res = substitute_simple_macros(NULL, NULL, item, NULL, NULL, NULL, value, MACRO_TYPE_FUNCTION_PARAMETER, NULL, 0);

	if (res == SUCCEED)
		zabbix_log(LOG_LEVEL_DEBUG, "%s() value:'%s'", __function_name, *value);
clean:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: evaluate_LOGSOURCE                                               *
 *                                                                            *
 * Purpose: evaluate function 'logsource' for the item                        *
 *                                                                            *
 * Parameters: item - item (performance metric)                               *
 *             parameter - ignored                                            *
 *                                                                            *
 * Return value: SUCCEED - evaluated succesfully, result is stored in 'value' *
 *               FAIL - failed to evaluate function                           *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int	evaluate_LOGSOURCE(char *value, DB_ITEM *item, const char *function, const char *parameters, time_t now)
{
	const char	*__function_name = "evaluate_LOGSOURCE";
	DB_RESULT	result;
	DB_ROW		row;
	char		sql[128], *arg1 = NULL;
	int		res = FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	if (item->value_type != ITEM_VALUE_TYPE_LOG)
		goto clean;

	if (num_param(parameters) > 1)
		goto clean;

	if (FAIL == get_function_parameter_str(item, parameters, 1, &arg1))
		goto clean;

	zbx_snprintf(sql, sizeof(sql),
			"select source"
			" from history_log"
			" where itemid=" ZBX_FS_UI64
			" order by id desc",
			item->itemid);

	result = DBselectN(sql, 1);

	if (NULL == (row = DBfetch(result)) || DBis_null(row[0]) == SUCCEED)
		zabbix_log(LOG_LEVEL_DEBUG, "Result for LOGSOURCE is empty" );
	else
	{
		if (0 == strcmp(row[0], arg1))
			strcpy(value, "1");
		else
			strcpy(value, "0");
		res = SUCCEED;
	}
	DBfree_result(result);

	zbx_free(arg1);
clean:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: evaluate_LOGSEVERITY                                             *
 *                                                                            *
 * Purpose: evaluate function 'logseverity' for the item                      *
 *                                                                            *
 * Parameters: item - item (performance metric)                               *
 *             parameter - ignored                                            *
 *                                                                            *
 * Return value: SUCCEED - evaluated successfully, result is stored in 'value'*
 *               FAIL - failed to evaluate function                           *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int	evaluate_LOGSEVERITY(char *value, DB_ITEM *item, const char *function, const char *parameters, time_t now)
{
	const char	*__function_name = "evaluate_LOGSEVERITY";
	DB_RESULT	result;
	DB_ROW		row;
	char		sql[128];
	int		res = FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	if (item->value_type != ITEM_VALUE_TYPE_LOG)
		goto clean;

	zbx_snprintf(sql, sizeof(sql),
			"select severity"
			" from history_log"
			" where itemid=" ZBX_FS_UI64
			" order by id desc",
			item->itemid);

	result = DBselectN(sql, 1);

	if (NULL == (row = DBfetch(result)) || DBis_null(row[0]) == SUCCEED)
		zabbix_log(LOG_LEVEL_DEBUG, "Result for LOGSEVERITY is empty" );
	else
	{
		strcpy(value, row[0]);
		res = SUCCEED;
	}
	DBfree_result(result);
clean:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: evaluate_COUNT                                                   *
 *                                                                            *
 * Purpose: evaluate function 'count' for the item                            *
 *                                                                            *
 * Parameters: item - item (performance metric)                               *
 *             parameters - up to four comma-separated fields:                *
 *                            (1) number of seconds/values                    *
 *                            (2) value to compare with (optional)            *
 *                            (3) comparison operator (optional)              *
 *                            (4) time shift (optional)                       *
 *                                                                            *
 * Return value: SUCCEED - evaluated successfully, result is stored in 'value'*
 *               FAIL - failed to evaluate function                           *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int	evaluate_COUNT(char *value, DB_ITEM *item, const char *function, const char *parameters, time_t now)
{
#define OP_EQ 0
#define OP_NE 1
#define OP_GT 2
#define OP_GE 3
#define OP_LT 4
#define OP_LE 5
#define OP_MAX 6

	const char	*__function_name = "evaluate_COUNT";
	DB_RESULT	result;
	DB_ROW		row;

	char		tmp[MAX_STRING_LEN];

	int		arg1, flag, op = OP_EQ, offset,
			nparams, count, res = FAIL;
	zbx_uint64_t	value_uint64 = 0, dbvalue_uint64;
	double		value_double = 0, dbvalue_double;
	char		*operators[OP_MAX] = {"=", "<>", ">", ">=", "<", "<="};
	char		*arg2 = NULL, *arg3 = NULL, *arg2_esc;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	nparams = num_param(parameters);
	switch (item->value_type)
	{
	case ITEM_VALUE_TYPE_FLOAT:
	case ITEM_VALUE_TYPE_UINT64:
		if (!(1 <= nparams && nparams <= 4))
			return res;
		break;
	case ITEM_VALUE_TYPE_LOG:
	case ITEM_VALUE_TYPE_STR:
	case ITEM_VALUE_TYPE_TEXT:
		if (!(1 <= nparams && nparams <= 3))
			return res;
		break;
	default:
		return res;
	}

	if (FAIL == get_function_parameter_uint(item, parameters, 1, &arg1, &flag))
		return res;

	if (SUCCEED == get_function_parameter_str(item, parameters, 2, &arg2))
	{
		if ((item->value_type == ITEM_VALUE_TYPE_UINT64 || item->value_type == ITEM_VALUE_TYPE_FLOAT) &&
				SUCCEED == get_function_parameter_str(item, parameters, 3, &arg3))
		{
			if (0 == strcmp(arg3, "eq") || '\0' == *arg3) op = OP_EQ;
			else if (0 == strcmp(arg3, "ne")) op = OP_NE;
			else if (0 == strcmp(arg3, "gt")) op = OP_GT;
			else if (0 == strcmp(arg3, "ge")) op = OP_GE;
			else if (0 == strcmp(arg3, "lt")) op = OP_LT;
			else if (0 == strcmp(arg3, "le")) op = OP_LE;
			else
			{
				zabbix_log(LOG_LEVEL_DEBUG, "Parameter \"%s\" is not supported for function COUNT",
						arg3);
				zbx_free(arg2);
				zbx_free(arg3);
				return res;
			}
			zbx_free(arg3);
		}

		switch (item->value_type) {
		case ITEM_VALUE_TYPE_UINT64:
			ZBX_STR2UINT64(value_uint64, arg2);
			break;
		case ITEM_VALUE_TYPE_FLOAT:
			value_double = atof(arg2);
			break;
		default:
			;	/* nothing */
		}

		if (	(nparams == 4 && (item->value_type == ITEM_VALUE_TYPE_UINT64 ||
					item->value_type == ITEM_VALUE_TYPE_FLOAT)) ||
			(nparams == 3 && (item->value_type == ITEM_VALUE_TYPE_LOG ||
					item->value_type == ITEM_VALUE_TYPE_STR ||
					item->value_type == ITEM_VALUE_TYPE_TEXT)))
		{
			int time_shift, time_shift_flag;

			if (FAIL == get_function_parameter_uint(item, parameters, nparams, &time_shift, &time_shift_flag) ||
				time_shift_flag != ZBX_FLAG_SEC)
			{
				zbx_free(arg2);
				return res;
			}

			now -= time_shift;
		}
	}
	
	if (arg2 != NULL && strcmp(arg2, "") == 0)
		zbx_free(arg2);

	if (flag == ZBX_FLAG_SEC)
	{
		offset = zbx_snprintf(tmp, sizeof(tmp),
				"select count(value)"
				" from %s"
				" where itemid=" ZBX_FS_UI64,
				get_table_by_value_type(item->value_type),
				item->itemid);

		if (NULL != arg2)
		{
			switch (item->value_type) {
			case ITEM_VALUE_TYPE_UINT64:
				offset += zbx_snprintf(tmp + offset, sizeof(tmp) - offset,
						" and value%s" ZBX_FS_UI64,
						operators[op],
						value_uint64);
				break;
			case ITEM_VALUE_TYPE_FLOAT:
				switch (op) {
				case OP_EQ:
					offset += zbx_snprintf(tmp + offset, sizeof(tmp) - offset,
							" and value>" ZBX_FS_DBL
							" and value<" ZBX_FS_DBL,
							value_double - 0.00001,
							value_double + 0.00001);
					break;
				case OP_NE:
					offset += zbx_snprintf(tmp + offset, sizeof(tmp) - offset,
							" and not (value>" ZBX_FS_DBL " and value<" ZBX_FS_DBL ")",
							value_double - 0.00001,
							value_double + 0.00001);
					break;
				default:
					offset += zbx_snprintf(tmp + offset, sizeof(tmp) - offset,
							" and value%s" ZBX_FS_DBL,
							operators[op],
							value_double);
				}
				break;
			default:
				arg2_esc = DBdyn_escape_string(arg2);
				offset += zbx_snprintf(tmp + offset, sizeof(tmp) - offset,
						" and value like '%s'",
						arg2_esc);
				zbx_free(arg2_esc);
			}
		}
		zbx_snprintf(tmp + offset, sizeof(tmp) - offset, " and clock<=%d and clock>%d",
				now, now - arg1);

		result = DBselect("%s", tmp);

		if (NULL == (row = DBfetch(result)) || SUCCEED == DBis_null(row[0]))
			zbx_snprintf(value, MAX_STRING_LEN, "0");
		else
			zbx_snprintf(value, MAX_STRING_LEN, "%s", row[0]);
		res = SUCCEED;

		zabbix_log(LOG_LEVEL_DEBUG, "%s() value:%s", __function_name, value);
	}
	else	/* ZBX_FLAG_VALUES */
	{
		offset = zbx_snprintf(tmp, sizeof(tmp),
				"select value"
				" from %s"
				" where itemid=" ZBX_FS_UI64
					" and clock<=%d",
				get_table_by_value_type(item->value_type),
				item->itemid,
				now);

		switch (item->value_type)
		{
		case ITEM_VALUE_TYPE_FLOAT:
		case ITEM_VALUE_TYPE_UINT64:
		case ITEM_VALUE_TYPE_STR:
			zbx_snprintf(tmp + offset, sizeof(tmp) - offset, " order by clock desc");
			break;
		default:
			zbx_snprintf(tmp + offset, sizeof(tmp) - offset, " order by id desc");
		}

		result = DBselectN(tmp, arg1);
		count = 0;

		while (NULL != (row = DBfetch(result)))
		{
			if (NULL == arg2)
				goto count_inc;

			switch (item->value_type) {
			case ITEM_VALUE_TYPE_UINT64:
				ZBX_STR2UINT64(dbvalue_uint64, row[0]);

				switch (op) {
				case OP_EQ:
					if (dbvalue_uint64 == value_uint64)
						goto count_inc;
					break;
				case OP_NE:
					if (dbvalue_uint64 != value_uint64)
						goto count_inc;
					break;
				case OP_GT:
					if (dbvalue_uint64 > value_uint64)
						goto count_inc;
					break;
				case OP_GE:
					if (dbvalue_uint64 >= value_uint64)
						goto count_inc;
					break;
				case OP_LT:
					if (dbvalue_uint64 < value_uint64)
						goto count_inc;
					break;
				case OP_LE:
					if (dbvalue_uint64 <= value_uint64)
						goto count_inc;
					break;
				}
				break;
			case ITEM_VALUE_TYPE_FLOAT:
				dbvalue_double = atof(row[0]);

				switch (op) {
				case OP_EQ:
					if (dbvalue_double > value_double - 0.00001 && dbvalue_double < value_double + 0.00001)
						goto count_inc;
					break;
				case OP_NE:
					if (!(dbvalue_double > value_double - 0.00001 && dbvalue_double < value_double + 0.00001))
						goto count_inc;
					break;
				case OP_GT:
					if (dbvalue_double > value_double)
						goto count_inc;
					break;
				case OP_GE:
					if (dbvalue_double >= value_double)
						goto count_inc;
					break;
				case OP_LT:
					if (dbvalue_double < value_double)
						goto count_inc;
					break;
				case OP_LE:
					if (dbvalue_double <= value_double)
						goto count_inc;
					break;
				}
				break;
			default:
				if (NULL != strstr(row[0], arg2))
					goto count_inc;
				break;
			}

			continue;
count_inc:
			count++;
		}
		zbx_snprintf(value, MAX_STRING_LEN, "%d", count);
		res = SUCCEED;

		zabbix_log(LOG_LEVEL_DEBUG, "%s() value:%s", __function_name, value);
	}
	DBfree_result(result);
	zbx_free(arg2);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: evaluate_SUM                                                     *
 *                                                                            *
 * Purpose: evaluate function 'sum' for the item                              *
 *                                                                            *
 * Parameters: item - item (performance metric)                               *
 *             parameters - number of seconds/values and time shift (optional)*
 *                                                                            *
 * Return value: SUCCEED - evaluated successfully, result is stored in 'value'*
 *               FAIL - failed to evaluate function                           *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int	evaluate_SUM(char *value, DB_ITEM *item, const char *function, const char *parameters, time_t now)
{
	const char	*__function_name = "evaluate_SUM";
	DB_RESULT	result;
	DB_ROW		row;
	char		sql[MAX_STRING_LEN];
	int		nparams, arg1, flag, rows = 0, res = FAIL;
	double		sum = 0;
	zbx_uint64_t	l, sum_uint64 = 0;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	if (item->value_type != ITEM_VALUE_TYPE_FLOAT && item->value_type != ITEM_VALUE_TYPE_UINT64)
		return res;

	nparams = num_param(parameters);
	if (!(nparams == 1 || nparams == 2))
		return res;

	if (FAIL == get_function_parameter_uint(item, parameters, 1, &arg1, &flag))
		return res;

	if (nparams == 2)
	{
		int time_shift, time_shift_flag;

		if (FAIL == get_function_parameter_uint(item, parameters, 2, &time_shift, &time_shift_flag))
			return res;
		if (time_shift_flag != ZBX_FLAG_SEC)
			return res;

		now -= time_shift;
	}

	if (flag == ZBX_FLAG_SEC)
	{
		result = DBselect(
				"select sum(value)"
				" from %s"
				" where clock<=%d"
					" and clock>%d"
					" and itemid=" ZBX_FS_UI64,
				get_table_by_value_type(item->value_type),
				now,
				now - arg1,
				item->itemid);

		if (NULL == (row = DBfetch(result)) || SUCCEED == DBis_null(row[0]))
			zabbix_log(LOG_LEVEL_DEBUG, "Result for SUM is empty");
		else
		{
			zbx_strlcpy(value, row[0], MAX_STRING_LEN);
			res = SUCCEED;
		}
		DBfree_result(result);
	}
	else if (flag == ZBX_FLAG_VALUES)
	{
		zbx_snprintf(sql, sizeof(sql),
				"select value"
				" from %s"
				" where itemid=" ZBX_FS_UI64
					" and clock<=%d"
				" order by clock desc",
				get_table_by_value_type(item->value_type),
				item->itemid,
				now);

		result = DBselectN(sql, arg1);

		if (item->value_type == ITEM_VALUE_TYPE_UINT64)
		{
			while (NULL != (row = DBfetch(result)))
			{
				ZBX_STR2UINT64(l, row[0]);
				sum_uint64 += l;
				rows++;
			}
		}
		else
		{
			while (NULL != (row = DBfetch(result)))
			{
				sum += atof(row[0]);
				rows++;
			}
		}
		DBfree_result(result);

		if (0 == rows)
			zabbix_log(LOG_LEVEL_DEBUG, "Result for SUM is empty" );
		else
		{
			if (item->value_type == ITEM_VALUE_TYPE_UINT64)
				zbx_snprintf(value, MAX_STRING_LEN, ZBX_FS_UI64, sum_uint64);
			else
				zbx_snprintf(value, MAX_STRING_LEN, ZBX_FS_DBL, sum);
			res = SUCCEED;
		}
	}

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: evaluate_AVG                                                     *
 *                                                                            *
 * Purpose: evaluate function 'avg' for the item                              *
 *                                                                            *
 * Parameters: item - item (performance metric)                               *
 *             parameters - number of seconds/values and time shift (optional)*
 *                                                                            *
 * Return value: SUCCEED - evaluated successfully, result is stored in 'value'*
 *               FAIL - failed to evaluate function                           *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int	evaluate_AVG(char *value, DB_ITEM *item, const char *function, const char *parameters, time_t now)
{
	const char	*__function_name = "evaluate_AVG";
	DB_RESULT	result;
	DB_ROW		row;
	char		sql[MAX_STRING_LEN];
	int		nparams, arg1, flag, rows = 0, res = FAIL;
	double		sum = 0;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	if (item->value_type != ITEM_VALUE_TYPE_FLOAT && item->value_type != ITEM_VALUE_TYPE_UINT64)
		return res;

	nparams = num_param(parameters);
	if (!(nparams == 1 || nparams == 2))
		return res;

	if (FAIL == get_function_parameter_uint(item, parameters, 1, &arg1, &flag))
		return res;

	if (nparams == 2)
	{
		int time_shift, time_shift_flag;

		if (FAIL == get_function_parameter_uint(item, parameters, 2, &time_shift, &time_shift_flag))
			return res;
		if (time_shift_flag != ZBX_FLAG_SEC)
			return res;

		now -= time_shift;
	}

	if (flag == ZBX_FLAG_SEC)
	{
		result = DBselect(
				"select avg(value)"
				" from %s"
				" where clock<=%d"
					" and clock>%d"
					" and itemid=" ZBX_FS_UI64,
				get_table_by_value_type(item->value_type),
				now,
				now - arg1,
				item->itemid);

		if (NULL == (row = DBfetch(result)) || SUCCEED == DBis_null(row[0]))
			zabbix_log(LOG_LEVEL_DEBUG, "Result for AVG is empty");
		else
		{
			zbx_strlcpy(value, row[0], MAX_STRING_LEN);
			res = SUCCEED;
		}
		DBfree_result(result);
	}
	else if (flag == ZBX_FLAG_VALUES)
	{
		zbx_snprintf(sql, sizeof(sql),
				"select value"
				" from %s"
				" where itemid=" ZBX_FS_UI64
					" and clock<=%d"
				" order by clock desc",
				get_table_by_value_type(item->value_type),
				item->itemid,
				now);

		result = DBselectN(sql, arg1);

		while (NULL != (row = DBfetch(result)))
		{
			sum += atof(row[0]);
			rows++;
		}
		DBfree_result(result);

		if (0 == rows)
			zabbix_log(LOG_LEVEL_DEBUG, "Result for AVG is empty" );
		else
		{
			zbx_snprintf(value,MAX_STRING_LEN, ZBX_FS_DBL, sum / (double)rows);
			res = SUCCEED;
		}
	}

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: evaluate_LAST                                                    *
 *                                                                            *
 * Purpose: evaluate functions 'last' and 'prev' for the item                 *
 *                                                                            *
 * Parameters: value - require size 'MAX_STRING_LEN'                          *
 *             item - item (performance metric)                               *
 *             num - Nth last value                                           *
 *                                                                            *
 * Return value: SUCCEED - evaluated successfully, result is stored in 'value'*
 *               FAIL - failed to evaluate function                           *
 *                                                                            *
 * Author: Aleksander Vladishev                                               *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int	evaluate_LAST(char *value, DB_ITEM *item, const char *function, const char *parameters, time_t now)
{
	const char	*__function_name = "evaluate_LAST";
	DB_RESULT	result;
	DB_ROW		row;
	int		arg1, flag, res = FAIL, rows = 0;
	char		sql[128];

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	if (0 == strcmp(function, "last"))
	{
		if (FAIL == get_function_parameter_uint(item, parameters, 1, &arg1, &flag) || flag != ZBX_FLAG_VALUES)
		{
			arg1 = 1;
			flag = ZBX_FLAG_VALUES;
		}

	}
	else if (0 == strcmp(function, "prev"))
	{
		arg1 = 2;
		flag = ZBX_FLAG_VALUES;
	}
	else
		goto clean;

	switch (arg1) {
	case 1:
		if (1 != item->lastvalue_null)
		{
			switch (item->value_type) {
			case ITEM_VALUE_TYPE_FLOAT:
				zbx_snprintf(value, MAX_STRING_LEN, ZBX_FS_DBL, item->lastvalue_dbl);
				break;
			case ITEM_VALUE_TYPE_UINT64:
				zbx_snprintf(value, MAX_STRING_LEN, ZBX_FS_UI64, item->lastvalue_uint64);
				break;
			default:
				zbx_snprintf(value, MAX_STRING_LEN, "%s", item->lastvalue_str);
				break;
			}
			res = SUCCEED;
		}
		break;
	case 2:
		if (1 != item->prevvalue_null)
		{
			switch (item->value_type) {
			case ITEM_VALUE_TYPE_FLOAT:
				zbx_snprintf(value, MAX_STRING_LEN, ZBX_FS_DBL, item->prevvalue_dbl);
				break;
			case ITEM_VALUE_TYPE_UINT64:
				zbx_snprintf(value, MAX_STRING_LEN, ZBX_FS_UI64, item->prevvalue_uint64);
				break;
			default:
				zbx_snprintf(value, MAX_STRING_LEN, "%s", item->prevvalue_str);
				break;
			}
			res = SUCCEED;
		}
		break;
	default:
		zbx_snprintf(sql, sizeof(sql),
				"select value,clock"
				" from %s"
				" where itemid=" ZBX_FS_UI64
				" order by %s desc",
				get_table_by_value_type(item->value_type),
				item->itemid,
				get_key_by_value_type(item->value_type));

		result = DBselectN(sql, arg1);

		while (NULL != (row = DBfetch(result)))
		{
			if (arg1 == ++rows)
			{
				zbx_snprintf(value, MAX_STRING_LEN, "%s", row[0]);
				res = SUCCEED;
			}
		}

		DBfree_result(result);
	}
clean:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: evaluate_MIN                                                     *
 *                                                                            *
 * Purpose: evaluate function 'min' for the item                              *
 *                                                                            *
 * Parameters: item - item (performance metric)                               *
 *             parameters - number of seconds/values and time shift (optional)*
 *                                                                            *
 * Return value: SUCCEED - evaluated successfully, result is stored in 'value'*
 *               FAIL - failed to evaluate function                           *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int	evaluate_MIN(char *value, DB_ITEM *item, const char *function, const char *parameters, time_t now)
{
	const char	*__function_name = "evaluate_MIN";
	DB_RESULT	result;
	DB_ROW		row;
	char		sql[MAX_STRING_LEN];
	int		nparams, arg1, flag, rows = 0, res = FAIL;
	zbx_uint64_t	min_uint64 = 0, l;
	double		min = 0, f;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	if (item->value_type != ITEM_VALUE_TYPE_FLOAT && item->value_type != ITEM_VALUE_TYPE_UINT64)
		return res;

	nparams = num_param(parameters);
	if (!(nparams == 1 || nparams == 2))
		return res;

	if (FAIL == get_function_parameter_uint(item, parameters, 1, &arg1, &flag))
		return res;

	if (nparams == 2)
	{
		int time_shift, time_shift_flag;

		if (FAIL == get_function_parameter_uint(item, parameters, 2, &time_shift, &time_shift_flag))
			return res;
		if (time_shift_flag != ZBX_FLAG_SEC)
			return res;

		now -= time_shift;
	}

	if (flag == ZBX_FLAG_SEC)
	{
		result = DBselect(
				"select min(value)"
				" from %s"
				" where clock<=%d"
					" and clock>%d"
					" and itemid=" ZBX_FS_UI64,
				get_table_by_value_type(item->value_type),
				now,
				now - arg1,
				item->itemid);

		if (NULL == (row = DBfetch(result)) || SUCCEED == DBis_null(row[0]))
			zabbix_log(LOG_LEVEL_DEBUG, "Result for MIN is empty");
		else
		{
			zbx_strlcpy(value, row[0], MAX_STRING_LEN);
			res = SUCCEED;
		}
		DBfree_result(result);
	}
	else if (flag == ZBX_FLAG_VALUES)
	{
		zbx_snprintf(sql, sizeof(sql),
				"select value"
				" from %s"
				" where itemid=" ZBX_FS_UI64
					" and clock<=%d"
				" order by clock desc",
				get_table_by_value_type(item->value_type),
				item->itemid,
				now);

		result = DBselectN(sql, arg1);

		if (item->value_type == ITEM_VALUE_TYPE_UINT64)
		{
			while (NULL != (row = DBfetch(result)))
			{
				ZBX_STR2UINT64(l, row[0]);
				if (0 == rows || l < min_uint64)
					min_uint64 = l;
				rows++;
			}
		}
		else
		{
			while (NULL != (row = DBfetch(result)))
			{
				f = atof(row[0]);
				if (0 == rows || f < min)
					min = f;
				rows++;
			}
		}
		DBfree_result(result);

		if (0 == rows)
			zabbix_log(LOG_LEVEL_DEBUG, "Result for MIN is empty" );
		else
		{
			if (item->value_type == ITEM_VALUE_TYPE_UINT64)
				zbx_snprintf(value, MAX_STRING_LEN, ZBX_FS_UI64, min_uint64);
			else
				zbx_snprintf(value, MAX_STRING_LEN, ZBX_FS_DBL, min);
			res = SUCCEED;
		}
	}

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: evaluate_MAX                                                     *
 *                                                                            *
 * Purpose: evaluate function 'max' for the item                              *
 *                                                                            *
 * Parameters: item - item (performance metric)                               *
 *             parameters - number of seconds/values and time shift (optional)*
 *                                                                            *
 * Return value: SUCCEED - evaluated successfully, result is stored in 'value'*
 *               FAIL - failed to evaluate function                           *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int	evaluate_MAX(char *value, DB_ITEM *item, const char *function, const char *parameters, time_t now)
{
	const char	*__function_name = "evaluate_MAX";
	DB_RESULT	result;
	DB_ROW		row;
	char		sql[MAX_STRING_LEN];
	int		nparams, arg1, flag, rows = 0, res = FAIL;
	zbx_uint64_t	max_uint64 = 0, l;
	double		max = 0, f;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	if (item->value_type != ITEM_VALUE_TYPE_FLOAT && item->value_type != ITEM_VALUE_TYPE_UINT64)
		return res;

	nparams = num_param(parameters);
	if (!(nparams == 1 || nparams == 2))
		return res;

	if (FAIL == get_function_parameter_uint(item, parameters, 1, &arg1, &flag))
		return res;

	if (nparams == 2)
	{
		int time_shift, time_shift_flag;

		if (FAIL == get_function_parameter_uint(item, parameters, 2, &time_shift, &time_shift_flag))
			return res;
		if (time_shift_flag != ZBX_FLAG_SEC)
			return res;

		now -= time_shift;
	}

	if (flag == ZBX_FLAG_SEC)
	{
		result = DBselect(
				"select max(value)"
				" from %s"
				" where clock<=%d"
					" and clock>%d"
					" and itemid=" ZBX_FS_UI64,
				get_table_by_value_type(item->value_type),
				now,
				now - arg1,
				item->itemid);

		if (NULL == (row = DBfetch(result)) || SUCCEED == DBis_null(row[0]))
			zabbix_log(LOG_LEVEL_DEBUG, "Result for MAX is empty");
		else
		{
			zbx_strlcpy(value, row[0], MAX_STRING_LEN);
			res = SUCCEED;
		}
		DBfree_result(result);
	}
	else if (flag == ZBX_FLAG_VALUES)
	{
		zbx_snprintf(sql, sizeof(sql),
				"select value"
				" from %s"
				" where itemid=" ZBX_FS_UI64
					" and clock<=%d"
				" order by clock desc",
				get_table_by_value_type(item->value_type),
				item->itemid,
				now);

		result = DBselectN(sql, arg1);

		if (item->value_type == ITEM_VALUE_TYPE_UINT64)
		{
			while (NULL != (row = DBfetch(result)))
			{
				ZBX_STR2UINT64(l, row[0]);
				if (0 == rows || l > max_uint64)
					max_uint64 = l;
				rows++;
			}
		}
		else
		{
			while (NULL != (row = DBfetch(result)))
			{
				f = atof(row[0]);
				if (0 == rows || f > max)
					max = f;
				rows++;
			}
		}
		DBfree_result(result);

		if (0 == rows)
			zabbix_log(LOG_LEVEL_DEBUG, "Result for MAX is empty" );
		else
		{
			if (item->value_type == ITEM_VALUE_TYPE_UINT64)
				zbx_snprintf(value, MAX_STRING_LEN, ZBX_FS_UI64, max_uint64);
			else
				zbx_snprintf(value, MAX_STRING_LEN, ZBX_FS_DBL, max);
			res = SUCCEED;
		}
	}

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: evaluate_DELTA                                                   *
 *                                                                            *
 * Purpose: evaluate function 'delta' for the item                            *
 *                                                                            *
 * Parameters: item - item (performance metric)                               *
 *             parameters - number of seconds/values and time shift (optional)*
 *                                                                            *
 * Return value: SUCCEED - evaluated successfully, result is stored in 'value'*
 *               FAIL - failed to evaluate function                           *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int	evaluate_DELTA(char *value, DB_ITEM *item, const char *function, const char *parameters, time_t now)
{
	const char	*__function_name = "evaluate_DELTA";
	DB_RESULT	result;
	DB_ROW		row;
	char		sql[MAX_STRING_LEN];
	int		nparams, arg1, flag, rows = 0, res = FAIL;
	zbx_uint64_t	min_uint64 = 0, max_uint64 = 0, l;
	double		min = 0, max = 0, f;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	if (item->value_type != ITEM_VALUE_TYPE_FLOAT && item->value_type != ITEM_VALUE_TYPE_UINT64)
		return res;

	nparams = num_param(parameters);
	if (!(nparams == 1 || nparams == 2))
		return res;

	if (FAIL == get_function_parameter_uint(item, parameters, 1, &arg1, &flag))
		return res;

	if (nparams == 2)
	{
		int time_shift, time_shift_flag;

		if (FAIL == get_function_parameter_uint(item, parameters, 2, &time_shift, &time_shift_flag))
			return res;
		if (time_shift_flag != ZBX_FLAG_SEC)
			return res;

		now -= time_shift;
	}

	if (flag == ZBX_FLAG_SEC)
	{
		result = DBselect(
				"select max(value)-min(value)"
				" from %s"
				" where clock<=%d"
					" and clock>%d"
					" and itemid=" ZBX_FS_UI64,
				get_table_by_value_type(item->value_type),
				now,
				now - arg1,
				item->itemid);

		if (NULL == (row = DBfetch(result)) || SUCCEED == DBis_null(row[0]))
			zabbix_log(LOG_LEVEL_DEBUG, "Result for DELTA is empty");
		else
		{
			zbx_strlcpy(value, row[0], MAX_STRING_LEN);
			res = SUCCEED;
		}
		DBfree_result(result);
	}
	else if (flag == ZBX_FLAG_VALUES)
	{
		zbx_snprintf(sql, sizeof(sql),
				"select value"
				" from %s"
				" where itemid=" ZBX_FS_UI64
					" and clock<=%d"
				" order by clock desc",
				get_table_by_value_type(item->value_type),
				item->itemid,
				now);

		result = DBselectN(sql, arg1);

		if (item->value_type == ITEM_VALUE_TYPE_UINT64)
		{
			while (NULL != (row = DBfetch(result)))
			{
				ZBX_STR2UINT64(l, row[0]);
				if (0 == rows || l < min_uint64)
					min_uint64 = l;
				if (0 == rows || l > max_uint64)
					max_uint64 = l;
				rows++;
			}
		}
		else
		{
			while (NULL != (row = DBfetch(result)))
			{
				f = atof(row[0]);
				if (0 == rows || f < min)
					min = f;
				if (0 == rows || f > max)
					max = f;
				rows++;
			}
		}
		DBfree_result(result);

		if (0 == rows)
			zabbix_log(LOG_LEVEL_DEBUG, "Result for DELTA is empty" );
		else
		{
			if (item->value_type == ITEM_VALUE_TYPE_UINT64)
				zbx_snprintf(value, MAX_STRING_LEN, ZBX_FS_UI64, max_uint64 - min_uint64);
			else
				zbx_snprintf(value, MAX_STRING_LEN, ZBX_FS_DBL, max - min);
			res = SUCCEED;
		}
	}

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: evaluate_NODATA                                                  *
 *                                                                            *
 * Purpose: evaluate function 'nodata' for the item                           *
 *                                                                            *
 * Parameters: item - item (performance metric)                               *
 *             parameter - number of seconds                                  *
 *                                                                            *
 * Return value: SUCCEED - evaluated successfully, result is stored in 'value'*
 *               FAIL - failed to evaluate function                           *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int	evaluate_NODATA(char *value, DB_ITEM *item, const char *function, const char *parameters, time_t now)
{
	const char	*__function_name = "evaluate_NODATA";
	int		arg1, flag, res = FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	if (num_param(parameters) > 1)
		return res;

	if (FAIL == get_function_parameter_uint(item, parameters, 1, &arg1, &flag))
		return res;

	if (flag != ZBX_FLAG_SEC)
		return res;

	if (item->lastclock + arg1 > now)
		strcpy(value,"0");
	else
	{
		if (CONFIG_SERVER_STARTUP_TIME + arg1 > now)
			return FAIL;

		strcpy(value,"1");
	}

	res = SUCCEED;

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: evaluate_ABSCHANGE                                               *
 *                                                                            *
 * Purpose: evaluate function 'abschange' for the item                        *
 *                                                                            *
 * Parameters: item - item (performance metric)                               *
 *             parameter - number of seconds                                  *
 *                                                                            *
 * Return value: SUCCEED - evaluated successfully, result is stored in 'value'*
 *               FAIL - failed to evaluate function                           *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int	evaluate_ABSCHANGE(char *value, DB_ITEM *item, const char *function, const char *parameters, time_t now)
{
	const char	*__function_name = "evaluate_ABSCHANGE";
	int		res = FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	if (item->lastvalue_null == 1 || item->prevvalue_null == 1)
		goto clean;

	switch (item->value_type) {
		case ITEM_VALUE_TYPE_FLOAT:
			zbx_snprintf(value, MAX_STRING_LEN, ZBX_FS_DBL,
					(double)abs(item->lastvalue_dbl - item->prevvalue_dbl));
			break;
		case ITEM_VALUE_TYPE_UINT64:
			/* To avoid overflow */
			if (item->lastvalue_uint64 >= item->prevvalue_uint64)
				zbx_snprintf(value, MAX_STRING_LEN, ZBX_FS_UI64,
						item->lastvalue_uint64 - item->prevvalue_uint64);
			else
				zbx_snprintf(value, MAX_STRING_LEN, ZBX_FS_UI64,
						item->prevvalue_uint64 - item->lastvalue_uint64);
			break;
		default:
			if (0 == strcmp(item->lastvalue_str, item->prevvalue_str))
				zbx_strlcpy(value, "0", MAX_STRING_LEN);
			else
				zbx_strlcpy(value, "1", MAX_STRING_LEN);
			break;
	}

	res = SUCCEED;
clean:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: evaluate_CHANGE                                                  *
 *                                                                            *
 * Purpose: evaluate function 'change' for the item                           *
 *                                                                            *
 * Parameters: item - item (performance metric)                               *
 *             parameter - number of seconds                                  *
 *                                                                            *
 * Return value: SUCCEED - evaluated successfully, result is stored in 'value'*
 *               FAIL - failed to evaluate function                           *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int	evaluate_CHANGE(char *value, DB_ITEM *item, const char *function, const char *parameters, time_t now)
{
	const char	*__function_name = "evaluate_CHANGE";
	int		res = FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	if (item->lastvalue_null == 1 || item->prevvalue_null == 1)
		goto clean;

	switch (item->value_type) {
		case ITEM_VALUE_TYPE_FLOAT:
			zbx_snprintf(value, MAX_STRING_LEN, ZBX_FS_DBL,
					item->lastvalue_dbl - item->prevvalue_dbl);
			break;
		case ITEM_VALUE_TYPE_UINT64:
			/* To avoid overflow */
			if (item->lastvalue_uint64 >= item->prevvalue_uint64)
				zbx_snprintf(value, MAX_STRING_LEN, ZBX_FS_UI64,
						item->lastvalue_uint64 - item->prevvalue_uint64);
			else
				zbx_snprintf(value, MAX_STRING_LEN, "-" ZBX_FS_UI64,
						item->prevvalue_uint64 - item->lastvalue_uint64);
			break;
		default:
			if (0 == strcmp(item->lastvalue_str, item->prevvalue_str))
				zbx_strlcpy(value, "0", MAX_STRING_LEN);
			else
				zbx_strlcpy(value, "1", MAX_STRING_LEN);
			break;
	}

	res = SUCCEED;
clean:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: evaluate_DIFF                                                    *
 *                                                                            *
 * Purpose: evaluate function 'diff' for the item                             *
 *                                                                            *
 * Parameters: item - item (performance metric)                               *
 *             parameter - number of seconds                                  *
 *                                                                            *
 * Return value: SUCCEED - evaluated successfully, result is stored in 'value'*
 *               FAIL - failed to evaluate function                           *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int	evaluate_DIFF(char *value, DB_ITEM *item, const char *function, const char *parameters, time_t now)
{
	const char	*__function_name = "evaluate_DIFF";
	int		res = FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	if (item->lastvalue_null == 1 || item->prevvalue_null == 1)
		goto clean;

	switch (item->value_type) {
		case ITEM_VALUE_TYPE_FLOAT:
			if (0 == cmp_double(item->lastvalue_dbl, item->prevvalue_dbl))
				zbx_strlcpy(value, "0", MAX_STRING_LEN);
			else
				zbx_strlcpy(value, "1", MAX_STRING_LEN);
			break;
		case ITEM_VALUE_TYPE_UINT64:
			if (item->lastvalue_uint64 == item->prevvalue_uint64)
				zbx_strlcpy(value, "0", MAX_STRING_LEN);
			else
				zbx_strlcpy(value, "1", MAX_STRING_LEN);
			break;
		default:
			if (0 == strcmp(item->lastvalue_str, item->prevvalue_str))
				zbx_strlcpy(value, "0", MAX_STRING_LEN);
			else
				zbx_strlcpy(value, "1", MAX_STRING_LEN);
			break;
	}

	res = SUCCEED;
clean:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: evaluate_STR                                                     *
 *                                                                            *
 * Purpose: evaluate function 'str' for the item                              *
 *                                                                            *
 * Parameters: item - item (performance metric)                               *
 *             parameters - <string>[,seconds]                                *
 *                                                                            *
 * Return value: SUCCEED - evaluated successfully, result is stored in 'value'*
 *               FAIL - failed to evaluate function                           *
 *                                                                            *
 * Author: Aleksander Vladishev                                               *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int	evaluate_STR(char *value, DB_ITEM *item, const char *function, const char *parameters, time_t now)
{
#define ZBX_FUNC_STR		1
#define ZBX_FUNC_REGEXP		2
#define ZBX_FUNC_IREGEXP	3

	const char	*__function_name = "evaluate_STR";
	DB_RESULT	result;
	DB_ROW		row;
	char		*arg1 = NULL, *arg1_esc, tmp[128];
	int		arg2, flag, func, rows, res = FAIL;
	ZBX_REGEXP	*regexps = NULL;
	int		regexps_alloc = 0, regexps_num = 0;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	if (item->value_type != ITEM_VALUE_TYPE_STR && item->value_type != ITEM_VALUE_TYPE_TEXT &&
			item->value_type != ITEM_VALUE_TYPE_LOG)
		goto clean;

	if (0 == strcmp(function, "str"))
		func = ZBX_FUNC_STR;
	else if (0 == strcmp(function, "regexp"))
		func = ZBX_FUNC_REGEXP;
	else if (0 == strcmp(function, "iregexp"))
		func = ZBX_FUNC_IREGEXP;
	else
		goto clean;

	if (num_param(parameters) > 2)
		goto clean;

	if (FAIL == get_function_parameter_str(item, parameters, 1, &arg1))
		goto clean;

	if (FAIL == get_function_parameter_uint(item, parameters, 2, &arg2, &flag))
	{
		arg2 = 1;
		flag = ZBX_FLAG_VALUES;
	}

	if ((func == ZBX_FUNC_REGEXP || func == ZBX_FUNC_IREGEXP) && *arg1 == '@')
	{
		arg1_esc = DBdyn_escape_string(arg1 + 1);
		result = DBselect("select r.name,e.expression,e.expression_type,e.exp_delimiter,e.case_sensitive"
				" from regexps r,expressions e"
				" where r.regexpid=e.regexpid"
					" and r.name='%s'",
				arg1_esc);
		zbx_free(arg1_esc);

		while (NULL != (row = DBfetch(result)))
			add_regexp_ex(&regexps, &regexps_alloc, &regexps_num,
					row[0], row[1], atoi(row[2]), row[3][0], atoi(row[4]));
		DBfree_result(result);
	}

	if (flag == ZBX_FLAG_SEC)
	{
		result = DBselect("select value"
				" from %s"
				" where itemid=" ZBX_FS_UI64
					" and clock>%d",
				get_table_by_value_type(item->value_type),
				item->itemid,
				now - arg2);
	}
	else
	{
		zbx_snprintf(tmp, sizeof(tmp),
				"select value"
				" from %s"
				" where itemid=" ZBX_FS_UI64
				" order by %s desc",
				get_table_by_value_type(item->value_type),
				item->itemid,
				get_key_by_value_type(item->value_type));
		result = DBselectN(tmp, arg2);
	}

	rows = 0;
	if (func == ZBX_FUNC_STR)
	{
		while (NULL != (row = DBfetch(result)))
		{
			if (NULL != strstr(row[0], arg1))
			{
				rows = 2;
				break;
			}
			rows = 1;
		}
	}
	else if (func == ZBX_FUNC_REGEXP)
	{
		while (NULL != (row = DBfetch(result)))
		{
			if (SUCCEED == regexp_match_ex(regexps, regexps_num, row[0], arg1, ZBX_CASE_SENSITIVE))
			{
				rows = 2;
				break;
			}
			rows = 1;
		}
	}
	else if (func == ZBX_FUNC_IREGEXP)
	{
		while (NULL != (row = DBfetch(result)))
		{
			if (SUCCEED == regexp_match_ex(regexps, regexps_num, row[0], arg1, ZBX_IGNORE_CASE))
			{
				rows = 2;
				break;
			}
			rows = 1;
		}
	}

	if ((func == ZBX_FUNC_REGEXP || func == ZBX_FUNC_IREGEXP) && *arg1 == '@')
		zbx_free(regexps);

	if (0 == rows)
	{
		zabbix_log(LOG_LEVEL_DEBUG, "Result for STR is empty" );
		res = FAIL;
	}
	else
	{
		if (2 == rows)
			zbx_strlcpy(value, "1", MAX_STRING_LEN);
		else
			zbx_strlcpy(value, "0", MAX_STRING_LEN);
	}
	DBfree_result(result);

	res = SUCCEED;
clean:
	zbx_free(arg1);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: evaluate_FUZZYTIME                                               *
 *                                                                            *
 * Purpose: evaluate function 'fuzzytime' for the item                        *
 *                                                                            *
 * Parameters: item - item (performance metric)                               *
 *             parameter - number of seconds                                  *
 *                                                                            *
 * Return value: SUCCEED - evaluated successfully, result is stored in 'value'*
 *               FAIL - failed to evaluate function                           *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int	evaluate_FUZZYTIME(char *value, DB_ITEM *item, const char *function, const char *parameters, time_t now)
{
	const char	*__function_name = "evaluate_FUZZYTIME";
	int		arg1, flag, fuzlow, fuzhig, res = FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	if (item->value_type != ITEM_VALUE_TYPE_FLOAT && item->value_type != ITEM_VALUE_TYPE_UINT64)
		goto clean;

	if (num_param(parameters) > 1)
		goto clean;

	if (FAIL == get_function_parameter_uint(item, parameters, 1, &arg1, &flag))
		goto clean;

	if (flag != ZBX_FLAG_SEC)
		goto clean;

	if (1 == item->lastvalue_null)
		goto clean;

	fuzlow = (int)(now - arg1);
	fuzhig = (int)(now + arg1);

	if (item->value_type == ITEM_VALUE_TYPE_UINT64)
	{
		if (item->lastvalue_uint64 >= fuzlow && item->lastvalue_uint64 <= fuzhig)
			strcpy(value,"1");
		else
			strcpy(value,"0");
	}
	else
	{
		if (item->lastvalue_dbl >= fuzlow && item->lastvalue_dbl <= fuzhig)
			strcpy(value,"1");
		else
			strcpy(value,"0");
	}

	res = SUCCEED;
clean:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(res));

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: evaluate_function                                                *
 *                                                                            *
 * Purpose: evaluate function                                                 *
 *                                                                            *
 * Parameters: item - item to calculate function for                          *
 *             function - function (for example, 'max')                       *
 *             parameter - parameter of the function)                         *
 *             flag - if EVALUATE_FUNCTION_SUFFIX, then include units and     *
 *                    suffix (K,M,G) into result value (for example, 15GB)    *
 *                                                                            *
 * Return value: SUCCEED - evaluated successfully, value contains its value   *
 *               FAIL - evaluation failed                                     *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
int evaluate_function(char *value, DB_ITEM *item, const char *function, const char *parameter, time_t now)
{
	int	ret;
	struct  tm      *tm;

	zabbix_log(LOG_LEVEL_DEBUG, "In evaluate_function('%s.%s(%s)')",
			zbx_host_key_string_by_item(item),
			function,
			parameter);

	*value = '\0';

	if (0 == strcmp(function, "last") || 0 == strcmp(function, "prev"))
	{
		ret = evaluate_LAST(value, item, function, parameter, now);
	}
	else if (0 == strcmp(function, "min"))
	{
		ret = evaluate_MIN(value, item, function, parameter, now);
	}
	else if (0 == strcmp(function, "max"))
	{
		ret = evaluate_MAX(value, item, function, parameter, now);
	}
	else if (0 == strcmp(function, "avg"))
	{
		ret = evaluate_AVG(value, item, function, parameter, now);
	}
	else if (0 == strcmp(function, "sum"))
	{
		ret = evaluate_SUM(value, item, function, parameter, now);
	}
	else if (0 == strcmp(function, "count"))
	{
		ret = evaluate_COUNT(value, item, function, parameter, now);
	}
	else if (0 == strcmp(function, "delta"))
	{
		ret = evaluate_DELTA(value, item, function, parameter, now);
	}
	else if (0 == strcmp(function, "nodata"))
	{
		ret = evaluate_NODATA(value, item, function, parameter, now);
	}
	else if (0 == strcmp(function, "date"))
	{
		tm = localtime(&now);
		zbx_snprintf(value, MAX_STRING_LEN, "%.4d%.2d%.2d",
				tm->tm_year + 1900,
				tm->tm_mon + 1,
				tm->tm_mday);
		ret = SUCCEED;
	}
	else if (0 == strcmp(function, "dayofweek"))
	{
		tm = localtime(&now);
		/* The number of days since Sunday, in the range 0 to 6. */
		zbx_snprintf(value, MAX_STRING_LEN, "%d",
				0 == tm->tm_wday ? 7 : tm->tm_wday);
		ret = SUCCEED;
	}
	else if (0 == strcmp(function, "time"))
	{
		tm = localtime(&now);
		zbx_snprintf(value, MAX_STRING_LEN, "%.2d%.2d%.2d",
				tm->tm_hour,
				tm->tm_min,
				tm->tm_sec);
		ret = SUCCEED;
	}
	else if (0 == strcmp(function, "abschange"))
	{
		ret = evaluate_ABSCHANGE(value, item, function, parameter, now);
	}
	else if (0 == strcmp(function, "change"))
	{
		ret = evaluate_CHANGE(value, item, function, parameter, now);
	}
	else if (0 == strcmp(function, "diff"))
	{
		ret = evaluate_DIFF(value, item, function, parameter, now);
	}
	else if (0 == strcmp(function, "str") || 0 == strcmp(function, "regexp") || 0 == strcmp(function, "iregexp"))
	{
		ret = evaluate_STR(value, item, function, parameter, now);
	}
	else if (0 == strcmp(function, "now"))
	{
		zbx_snprintf(value, MAX_STRING_LEN, "%d", (int)now);
		ret = SUCCEED;
	}
	else if (0 == strcmp(function, "fuzzytime"))
	{
		ret = evaluate_FUZZYTIME(value, item, function, parameter, now);
	}
	else if (0 == strcmp(function, "logseverity"))
	{
		ret = evaluate_LOGSEVERITY(value, item, function, parameter, now);
	}
	else if (0 == strcmp(function, "logsource"))
	{
		ret = evaluate_LOGSOURCE(value, item, function, parameter, now);
	}
	else
	{
		zabbix_log(LOG_LEVEL_WARNING, "Unsupported function:%s",
				function);
		zabbix_syslog("Unsupported function:%s",
				function);
		ret = FAIL;
	}

	if (SUCCEED == ret)
		del_zeroes(value);

	zabbix_log(LOG_LEVEL_DEBUG, "End of evaluate_function('%s.%s(%s)',value:'%s'):%s",
			zbx_host_key_string_by_item(item),
			function,
			parameter,
			value,
			zbx_result_string(ret));

	return ret;
}

/******************************************************************************
 *                                                                            *
 * Function: add_value_suffix_uptime                                          *
 *                                                                            *
 * Purpose: Process suffix 'uptime'                                           *
 *                                                                            *
 * Parameters: value - value for adjusting                                    *
 *             max_len - max len of the value                                 *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static void	add_value_suffix_uptime(char *value, int max_len)
{
	double	value_double;
	double	days, hours, min;

	zabbix_log( LOG_LEVEL_DEBUG, "In add_value_suffix_uptime(%s)",
		value);

	value_double = atof(value);

	if(value_double <0)	return;

	days=floor(value_double/(24*3600));
	if(cmp_double(days,0) != 0)
	{
		value_double=value_double-days*(24*3600);
	}
	hours=floor(value_double/(3600));
	if(cmp_double(hours,0) != 0)
	{
		value_double=value_double-hours*3600;
	}
	min=floor(value_double/(60));
	if( cmp_double(min,0) !=0)
	{
		value_double=value_double-min*(60);
	}
	if(cmp_double(days,0) == 0)
	{
		zbx_snprintf(value, max_len, "%02d:%02d:%02d",
			(int)hours,
			(int)min,
			(int)value_double);
	}
	else
	{
		zbx_snprintf(value, max_len, "%d days, %02d:%02d:%02d",
			(int)days,
			(int)hours,
			(int)min,
			(int)value_double);
	}
	zabbix_log( LOG_LEVEL_DEBUG, "End of add_value_suffix_uptime(%s)",
		value);
}

/******************************************************************************
 *                                                                            *
 * Function: add_value_suffix_s                                               *
 *                                                                            *
 * Purpose: Process suffix 's'                                                *
 *                                                                            *
 * Parameters: value - value for adjusting                                    *
 *             max_len - max len of the value                                 *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static void	add_value_suffix_s(char *value, int max_len)
{
	double	value_double;
	double	t;
	char	tmp[MAX_STRING_LEN];

	zabbix_log( LOG_LEVEL_DEBUG, "In add_value_suffix_s(%s)",
		value);

	value_double = atof(value);
	if(value_double <0)	return;

	value[0]='\0';

	t=floor(value_double/(365*24*3600));
	if(cmp_double(t,0) != 0)
	{
		zbx_snprintf(tmp, sizeof(tmp), "%dy", (int)t);
		zbx_strlcat(value, tmp, max_len);
		value_double = value_double-t*(365*24*3600);
	}

	t=floor(value_double/(30*24*3600));
	if(cmp_double(t,0) != 0)
	{
		zbx_snprintf(tmp, sizeof(tmp), "%dm", (int)t);
		zbx_strlcat(value, tmp, max_len);
		value_double = value_double-t*(30*24*3600);
	}

	t=floor(value_double/(24*3600));
	if(cmp_double(t,0) != 0)
	{
		zbx_snprintf(tmp, sizeof(tmp), "%dd", (int)t);
		zbx_strlcat(value, tmp, max_len);
		value_double = value_double-t*(24*3600);
	}

	t=floor(value_double/(3600));
	if(cmp_double(t,0) != 0)
	{
		zbx_snprintf(tmp, sizeof(tmp), "%dh", (int)t);
		zbx_strlcat(value, tmp, max_len);
		value_double = value_double-t*(3600);
	}

	t=floor(value_double/(60));
	if(cmp_double(t,0) != 0)
	{
		zbx_snprintf(tmp, sizeof(tmp), "%dm", (int)t);
		zbx_strlcat(value, tmp, max_len);
		value_double = value_double-t*(60);
	}

	zbx_snprintf(tmp, sizeof(tmp), "%02.2f", value_double);
	zbx_rtrim(tmp,"0");
	zbx_rtrim(tmp,".");
	zbx_strlcat(tmp, "s", sizeof(tmp));
	zbx_strlcat(value, tmp, max_len);

	zabbix_log( LOG_LEVEL_DEBUG, "End of add_value_suffix_s(%s)",
		value);
}

/******************************************************************************
 *                                                                            *
 * Function: add_value_suffix_normsl                                          *
 *                                                                            *
 * Purpose: Process normal values and add K,M,G,T                             *
 *                                                                            *
 * Parameters: value - value for adjusting                                    *
 *             max_len - max len of the value                                 *
 *             units - units (bps, b, B, etc)                                  *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static void	add_value_suffix_normal(char *value, int max_len, char *units)
{
	double	base = 1024;
	char	kmgt[MAX_STRING_LEN];

	zbx_uint64_t	value_uint64;
	double		value_double;

	zabbix_log( LOG_LEVEL_DEBUG, "In add_value_normal(value:%s,units:%s)",
		value,
		units);

	ZBX_STR2UINT64(value_uint64, value);

/*      value_uint64 = llabs(zbx_atoui64(value));*/

	/* Special processing for bits */
	if(strcmp(units,"b") == 0 || strcmp(units,"bps") == 0)
	{
		base = 1000;
	}

	if(value_uint64 < base)
	{
		strscpy(kmgt,"");
		value_double = (double)value_uint64;
	}
	else if(value_uint64 < base*base)
	{
		strscpy(kmgt,"K");
		value_double = (double)value_uint64/base;
	}
	else if(value_uint64 < base*base*base)
	{
		strscpy(kmgt,"M");
		value_double = (double)(value_uint64/(base*base));
	}
	else if(value_uint64 < base*base*base*base)
	{
		strscpy(kmgt,"G");
		value_double = (double)value_uint64/(base*base*base);
	}
	else
	{
		strscpy(kmgt,"T");
		value_double = (double)value_uint64/(base*base*base*base);
	}

	if(cmp_double((int)(value_double+0.5), value_double) == 0)
	{
		zbx_snprintf(value, MAX_STRING_LEN, ZBX_FS_DBL_EXT(0) " %s%s",
			value_double,
			kmgt,
			units);
	}
	else
	{
		zbx_snprintf(value, MAX_STRING_LEN, ZBX_FS_DBL_EXT(2) " %s%s",
			value_double,
			kmgt,
			units);
	}

	zabbix_log( LOG_LEVEL_DEBUG, "End of add_value_normal(value:%s)",
		value);
}

/******************************************************************************
 *                                                                            *
 * Function: add_value_suffix                                                 *
 *                                                                            *
 * Purpose: Add suffix for value                                              *
 *                                                                            *
 * Parameters: value - value for replacing                                    *
 *             valuemapid - index of value map                                *
 *                                                                            *
 * Return value: SUCCEED - suffix added successfully, value contains new value*
 *               FAIL - adding failed, value contains old value               *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/

/* Do not forget to keep it in sync with convert_units in config.inc.php */
int	add_value_suffix(char *value, int max_len, char *units, int value_type)
{
	int	ret = FAIL;

	struct  tm *local_time = NULL;
	time_t	time;

	char	tmp[MAX_STRING_LEN];

	zabbix_log( LOG_LEVEL_DEBUG, "In add_value_suffix(value:%s,units:%s)",
		value,
		units);

	switch(value_type)
	{
	case	ITEM_VALUE_TYPE_FLOAT:
		if(strcmp(units,"s") == 0)
		{
			add_value_suffix_s(value, max_len);
			ret = SUCCEED;
		}
		else if(strcmp(units,"uptime") == 0)
		{
			add_value_suffix_uptime(value, max_len);
			ret = SUCCEED;
		}
		else if(strlen(units) != 0)
		{
			add_value_suffix_normal(value, max_len, units);
			ret = SUCCEED;
		}
		else
		{
			/* Do nothing if units not set */
		}
		break;

	case	ITEM_VALUE_TYPE_UINT64:
		if(strcmp(units,"s") == 0)
		{
			add_value_suffix_s(value, max_len);
			ret = SUCCEED;
		}
		else if(strcmp(units,"unixtime") == 0)
		{
			time = (time_t)zbx_atoui64(value);
			local_time = localtime(&time);
			strftime(tmp, MAX_STRING_LEN, "%Y.%m.%d %H:%M:%S",
				local_time);
			zbx_strlcpy(value, tmp, max_len);
			ret = SUCCEED;
		}
		else if(strcmp(units,"uptime") == 0)
		{
			add_value_suffix_uptime(value, max_len);
			ret = SUCCEED;
		}
		else if(strlen(units) != 0)
		{
			add_value_suffix_normal(value, max_len, units);
			ret = SUCCEED;
		}
		else
		{
			/* Do nothing if units not set */
		}
		break;
	default:
		ret = FAIL;
		break;
	}

	zabbix_log(LOG_LEVEL_DEBUG, "End of add_value_suffix(%s)",
		value);

	return ret;
}

/******************************************************************************
 *                                                                            *
 * Function: replace_value_by_map                                             *
 *                                                                            *
 * Purpose: replace value by mapping value                                    *
 *                                                                            *
 * Parameters: value - value for replacing                                    *
 *             valuemapid - index of value map                                *
 *                                                                            *
 * Return value: SUCCEED - evaluated successfully, value contains new value   *
 *               FAIL - evaluation failed, value contains old value           *
 *                                                                            *
 * Author: Eugene Grigorjev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
int	replace_value_by_map(char *value, zbx_uint64_t valuemapid)
{
	DB_RESULT	result;
	DB_ROW		row;
	char		new_value[MAX_STRING_LEN], orig_value[MAX_STRING_LEN], *value_esc;
	int		ret = FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In replace_value_by_map()");

	if (valuemapid == 0)
		return FAIL;

	value_esc = DBdyn_escape_string(value);
	result = DBselect("select newvalue from mappings where valuemapid=" ZBX_FS_UI64 " and value='%s'",
			valuemapid,
			value_esc);
	zbx_free(value_esc);
	if (NULL != (row = DBfetch(result)) && FAIL == DBis_null(row[0]))
	{
		strcpy(new_value, row[0]);

		del_zeroes(new_value);
		zbx_strlcpy(orig_value, value, MAX_STRING_LEN);

		zbx_snprintf(value, MAX_STRING_LEN, "%s (%s)",
				new_value,
				orig_value);
		zabbix_log(LOG_LEVEL_DEBUG, "End replace_value_by_map(result:%s)",
				value);
		ret = SUCCEED;
	}
	DBfree_result(result);

	return ret;
}

/******************************************************************************
 *                                                                            *
 * Function: evaluate_function2                                               *
 *                                                                            *
 * Purpose: evaluate function                                                 *
 *                                                                            *
 * Parameters: host - host the key belongs to                                 *
 *             key - item's key (for example, 'max')                          *
 *             function - function (for example, 'max')                       *
 *             parameter - parameter of the function)                         *
 *                                                                            *
 * Return value: SUCCEED - evaluated successfully, value contains its value   *
 *               FAIL - evaluation failed                                     *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments: Used for evaluation of notification macros                       *
 *                                                                            *
 ******************************************************************************/
int evaluate_function2(char *value,char *host,char *key,char *function,char *parameter)
{
	DB_ITEM	item;
	DB_RESULT result;
	DB_ROW	row;

	char	host_esc[MAX_STRING_LEN];
	char	key_esc[MAX_STRING_LEN];

	int	res;

	zabbix_log(LOG_LEVEL_DEBUG, "In evaluate_function2(%s,%s,%s,%s)",
		host,
		key,
		function,
		parameter);

	DBescape_string(host, host_esc, MAX_STRING_LEN);
	DBescape_string(key, key_esc, MAX_STRING_LEN);

	result = DBselect("select %s where h.host='%s' and h.hostid=i.hostid and i.key_='%s'" DB_NODE,
		ZBX_SQL_ITEM_SELECT,
		host_esc,
		key_esc,
		DBnode_local("h.hostid"));

	row = DBfetch(result);

	if (!row)
	{
		DBfree_result(result);
		zabbix_log(LOG_LEVEL_WARNING, "Function [%s:%s.%s(%s)] not found. Query returned empty result",
				host, key, function, parameter);
		zabbix_syslog("Function [%s:%s.%s(%s)] not found. Query returned empty result",
				host, key, function, parameter);
		return FAIL;
	}

	DBget_item_from_db(&item, row);

	res = evaluate_function(value,&item,function,parameter, time(NULL));

	if(replace_value_by_map(value, item.valuemapid) != SUCCEED)
	{
		add_value_suffix(value, MAX_STRING_LEN, item.units, item.value_type);
	}

/* Cannot call DBfree_result until evaluate_FUNC */
	DBfree_result(result);

	zabbix_log(LOG_LEVEL_DEBUG, "End evaluate_function2(result:%s)",
		value);
	return res;
}
