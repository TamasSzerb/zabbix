/*
** Zabbix
** Copyright (C) 2001-2013 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

#include "common.h"
#include "db.h"
#include "dbcache.h"
#include "log.h"
#include "daemon.h"
#include "zbxself.h"
#include "zbxalgo.h"

#include "housekeeper.h"

extern unsigned char	process_type;

#define	HK_OPTION_DISABLED		0
#define	HK_OPTION_ENABLED		1

#define	HK_INITIAL_DELETE_QUEUE_SIZE	4096

/* the maximum number of housekeeping periods to be removed per single housekeeping cycle */
#define	HK_MAX_DELETE_PERIODS		4

/* housekeeping configuration */
typedef struct
{
	unsigned int	events_mode;
	unsigned int	events_trigger;
	unsigned int	events_internal;
	unsigned int	events_discovery;
	unsigned int	events_autoreg;

	unsigned int	services_mode;
	unsigned int	services;

	unsigned int	audit_mode;
	unsigned int	audit;

	unsigned int	sessions_mode;
	unsigned int	sessions;

	unsigned int	history_mode;
	unsigned int	history_global;
	unsigned int	history;

	unsigned int	trends_mode;
	unsigned int	trends_global;
	unsigned int	trends;
}
zbx_hk_config_t;

/* Housekeeping configuration field definition.                  */
/* This structure is used to map database config table fields to */
/* housekeeper configuration data.                               */
typedef struct
{
	/* a reference to the housekeeping configuration value */
	unsigned int	*pvalue;

	/* the configuration field name in database */
	const char	*field;

	/* the acceptable value range */
	unsigned int	min_value;
	unsigned int	max_value;
}
zbx_hk_db_config_t;

/* the housekeeping configuration */
static zbx_hk_config_t	hk_config;

/* the housekeeping configuration field definition */
#define	HK_FIELD_MAP(field, min, max) \
		{&hk_config.field, "hk_" # field, min, max}

/* mapping of housekeeping configuration fields from config table to configuration data */
static zbx_hk_db_config_t	hk_db_fields[] = {
		HK_FIELD_MAP(events_mode, 0, 1),
		HK_FIELD_MAP(events_trigger, 1, 99999),
		HK_FIELD_MAP(events_internal, 1, 99999),
		HK_FIELD_MAP(events_discovery, 1, 99999),
		HK_FIELD_MAP(events_autoreg, 1, 99999),
		HK_FIELD_MAP(services_mode, 0, 1),
		HK_FIELD_MAP(services, 1, 99999),
		HK_FIELD_MAP(audit_mode, 0, 1),
		HK_FIELD_MAP(audit, 1, 99999),
		HK_FIELD_MAP(sessions_mode, 0, 1),
		HK_FIELD_MAP(sessions, 1, 99999),
		HK_FIELD_MAP(history_mode, 0, 1),
		HK_FIELD_MAP(history_global, 0, 1),
		HK_FIELD_MAP(history, 0, 99999),
		HK_FIELD_MAP(trends_mode, 0, 1),
		HK_FIELD_MAP(trends_global, 0, 1),
		HK_FIELD_MAP(trends, 0, 99999),
		{0}
};

/* Housekeeping rule definition.                                */
/* A housekeeping rule describes table from which records older */
/* than history setting must be removed according to optional   */
/* filter.                                                      */
typedef struct
{
	/* target table name */
	char		*table;

	/* Optional filter, must be empty string if not used. Only the records matching */
	/* filter are subject to housekeeping procedures.                               */
	char		*filter;

	/* The oldest record in table (with filter in effect). The min_clock value is   */
	/* read from the database when accessed for the first time and then during      */
	/* housekeeping procedures updated to the last 'cutoff' value.                  */
	unsigned int	min_clock;

	/* a reference to the settings value specifying number of days the records must be kept */
	unsigned int	*phistory;
}
zbx_hk_rule_t;

/* housekeeper table => configuration data mapping.                       */
/* This structure is used to map table names used in housekeeper table to */
/* configuration data.                                                    */
typedef struct
{
	/* housekeeper table name */
	char		*name;

	/* a reference to housekeeping configuration enable value for this table */
	unsigned int	*poption_mode;
}
zbx_hk_cleanup_table_t;

/* Housekeeper table mapping to housekeeping configuration values.    */
/* This mapping is used to exclude disabled tables from housekeeping  */
/* cleanup procedure.                                                 */
static zbx_hk_cleanup_table_t hk_cleanup_tables[] = {
	{"history", &hk_config.history_mode},
	{"history_log", &hk_config.history_mode},
	{"history_str", &hk_config.history_mode},
	{"history_text", &hk_config.history_mode},
	{"history_uint", &hk_config.history_mode},

	{"trends", &hk_config.trends_mode},
	{"trends_uint", &hk_config.trends_mode},

	{NULL, NULL}
};

/* Trends table offsets in the hk_cleanup_tables[] mapping  */
#define	HK_UPADTE_CACHE_OFFSET_TREND_FLOAT	ITEM_VALUE_TYPE_COUNT
#define	HK_UPADTE_CACHE_OFFSET_TREND_UINT	(HK_UPADTE_CACHE_OFFSET_TREND_FLOAT + 1)

/* the oldest record timestamp cache for items in history tables */
typedef struct
{
	zbx_uint64_t	itemid;
	unsigned int	min_clock;
}
zbx_hk_item_cache_t;

/* Delete queue item definition.                                     */
/* The delete queue item defines an item that should be processed by */
/* housekeeping procedure (records older than min_clock seconds      */
/* must be removed from database).                                   */
typedef struct
{
	zbx_uint64_t	itemid;
	unsigned int	min_clock;
}
zbx_hk_delete_queue_t;

/* the item rule state  */
typedef enum
{
	HK_ITEM_RULE_NOT_INITIALIZED		= 0,
	HK_ITEM_RULE_ITEM_CACHE_PREPARED	= 1,
	HK_ITEM_RULE_DELETE_QUEUE_PREPARED	= 2
}
zbx_hk_item_rule_state_t;

/* This structure is used to remove old records from history (trends) tables */
typedef struct
{
	/* the target table name */
	char			*table;

	/* history setting field name in items table (history|trends) */
	char			*history;

	/* the cache status, see zbx_hk_item_rule_state_t enum */
	int			state;

	/* a reference to the housekeeping configuration mode (enable) option for this table */
	int			*poption_mode;

	/* a reference to the housekeeping configuration overwrite option for this table */
	int			*poption_global;

	/* a reference to the housekeeping configuration history value for this table */
	int			*poption;

	/* the oldest item record timestamp cache for target table */
	zbx_hashset_t		item_cache;

	/* the item delete queue */
	zbx_vector_ptr_t	delete_queue;
}
zbx_hk_history_rule_t;

/* The history item rules, used for housekeeping history and trends tables */
static zbx_hk_history_rule_t	hk_history_rules[] = {
	{"history", "history", 0, &hk_config.history_mode, &hk_config.history_global, &hk_config.history},
	{"history_str", "history", 0, &hk_config.history_mode, &hk_config.history_global, &hk_config.history},
	{"history_log", "history", 0, &hk_config.history_mode, &hk_config.history_global, &hk_config.history},
	{"history_uint", "history", 0, &hk_config.history_mode, &hk_config.history_global, &hk_config.history},
	{"history_text", "history", 0, &hk_config.history_mode, &hk_config.history_global, &hk_config.history},

	{"trends", "trends", 0, &hk_config.trends_mode, &hk_config.trends_global, &hk_config.trends},
	{"trends_uint","trends", 0, &hk_config.trends_mode, &hk_config.trends_global, &hk_config.trends},

	{0}
};

/******************************************************************************
 *                                                                            *
 * Function: hk_item_update_cache_compare                                     *
 *                                                                            *
 * Purpose: compare two delete queue items by their itemid                    *
 *                                                                            *
 * Parameters: d1 - [IN] the first delete queue item to compare               *
 *             d2 - [IN] the second delete queue item to compare              *
 *                                                                            *
 * Return value: <0 - the first item is less than the second                  *
 *               >0 - the first item is greater than the second               *
 *               =0 - the items are the same                                  *
 *                                                                            *
 * Author: Andris Zeila                                                       *
 *                                                                            *
 * Comments: this function is used to sort delete queue by itemids            *
 *                                                                            *
 ******************************************************************************/
static int	hk_item_update_cache_compare(const void *d1, const void *d2)
{
	zbx_hk_delete_queue_t *r1 = *(zbx_hk_delete_queue_t**)d1;
	zbx_hk_delete_queue_t *r2 = *(zbx_hk_delete_queue_t**)d2;

	if (r1->itemid > r2->itemid) return 1;
	if (r1->itemid < r2->itemid) return -1;
	return 0;
}

/******************************************************************************
 *                                                                            *
 * Function: hk_history_delete_queue_append                                   *
 *                                                                            *
 * Purpose: add item to the delete queue if necessary                         *
 *                                                                            *
 * Parameters: rule        - [IN/OUT] the history housekeeping rule           *
 *             now         - [IN] the current timestmap                       *
 *             item_record - [IN/OUT] the record from item cache containing   *
 *                           item to process and its oldest record timestamp  *
 *             history     - [IN] a number of days the history data for       *
 *                           item_record must be kept.                        *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Andris Zeila                                                       *
 *                                                                            *
 * Comments: If item is added to delete queue, its oldest record timestamp    *
 *           (min_clock) is updated to the calculated 'cutoff' value.         *
 *                                                                            *
 ******************************************************************************/
static void	hk_history_delete_queue_append(zbx_hk_history_rule_t *rule, unsigned int now,
			zbx_hk_item_cache_t *item_record, unsigned int history)
{
	unsigned int keep_from = now - history * SEC_PER_DAY;

	if (keep_from > item_record->min_clock)
	{
		zbx_hk_delete_queue_t	*update_record;

		/* update oldest timestamp in item cache */
		item_record->min_clock = MIN(keep_from, item_record->min_clock +
			HK_MAX_DELETE_PERIODS * CONFIG_HOUSEKEEPING_FREQUENCY * SEC_PER_HOUR);

		update_record = zbx_malloc(NULL, sizeof(zbx_hk_delete_queue_t));
		update_record->itemid = item_record->itemid;
		update_record->min_clock = item_record->min_clock;
		zbx_vector_ptr_append(&rule->delete_queue, update_record);
	}
}


/******************************************************************************
 *                                                                            *
 * Function: hk_history_prepare                                               *
 *                                                                            *
 * Purpose: prepares history housekeeping rule                                *
 *                                                                            *
 * Parameters: rule        - [IN/OUT] the history housekeeping rule           *
 *             now         - [IN] the current timestmap                       *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Andris Zeila                                                       *
 *                                                                            *
 * Comments: This function is called to initialize history rule data either   *
 *           at start or when housekeeping is enabled for this rule.          *
 *           It caches item history data and also prepares delete queue to be *
 *           processed during the first run.                                  *
 *                                                                            *
 ******************************************************************************/
static void	hk_history_prepare(zbx_hk_history_rule_t *rule, unsigned int now)
{
	DB_RESULT       result;
	DB_ROW          row;

	zbx_hashset_create(&rule->item_cache, 1024, zbx_default_uint64_hash_func, zbx_default_uint64_compare_func);

	zbx_vector_ptr_create(&rule->delete_queue);
	zbx_vector_ptr_reserve(&rule->delete_queue, HK_INITIAL_DELETE_QUEUE_SIZE);

	result = DBselect("select i.itemid,min(t.clock),i.%s from %s t,items i where"
			" t.itemid=i.itemid group by itemid",
			rule->history, rule->table);

	while (NULL != (row = DBfetch(result)))
	{
		zbx_uint64_t			itemid;
		unsigned int			min_clock, history;
		zbx_hk_item_cache_t		item_record;

		if (FAIL == is_uint64(row[0], &itemid) || FAIL == is_uint32(row[1], &min_clock))
			continue;

		if (HK_OPTION_ENABLED == *rule->poption_global)
			history = *rule->poption;
		else if (FAIL == is_uint32(row[2], &history))
			continue;

		item_record.itemid = itemid;
		item_record.min_clock = min_clock;

		hk_history_delete_queue_append(rule, now, &item_record, history);

		zbx_hashset_insert(&rule->item_cache, &item_record, sizeof(zbx_hk_item_cache_t));
	}

	rule->state = HK_ITEM_RULE_ITEM_CACHE_PREPARED | HK_ITEM_RULE_DELETE_QUEUE_PREPARED;
}

/******************************************************************************
 *                                                                            *
 * Function: hk_history_release                                               *
 *                                                                            *
 * Purpose: releases history housekeeping rule                                *
 *                                                                            *
 * Parameters: rule  - [IN/OUT] the history housekeeping rule                 *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Andris Zeila                                                       *
 *                                                                            *
 * Comments: This function is called to release resources allocated by        *
 *           history housekeeping rule after housekeeping was disabled        *
 *           for the table referred by this rule.                             *
 *                                                                            *
 ******************************************************************************/
static void	hk_history_release(zbx_hk_history_rule_t *rule)
{
	if (0 == rule->state) return;

	zbx_hashset_destroy(&rule->item_cache);
	zbx_vector_ptr_destroy(&rule->delete_queue);

	rule->state = HK_ITEM_RULE_NOT_INITIALIZED;
}

/******************************************************************************
 *                                                                            *
 * Function: hk_history_item_update                                           *
 *                                                                            *
 * Purpose: updates history housekeeping rule with item history setting and   *
 *          adds item to the delete queue if necessary                        *
 *                                                                            *
 * Parameters: rule    - [IN/OUT] the history housekeeping rule               *
 *             now     - [IN] the current timestamp                           *
 *             itemid  - [IN] the item to update                              *
 *             history - [IN] the number of days the item data should be kept *
 *                       in history                                           *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Andris Zeila                                                       *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static void	hk_history_item_update(zbx_hk_history_rule_t *rule, unsigned int now, zbx_uint64_t itemid,
		unsigned int history)
{
	zbx_hk_item_cache_t		*item_record;

	if (HK_OPTION_DISABLED == *rule->poption_mode)
		return;

	item_record = zbx_hashset_search(&rule->item_cache, &itemid);

	if (NULL == item_record)
	{
		zbx_hk_item_cache_t	item_data = {itemid, now};

		item_record = zbx_hashset_insert(&rule->item_cache, &item_data, sizeof(zbx_hk_item_cache_t));
		if (NULL == item_record)
			return;
	}

	hk_history_delete_queue_append(rule, now, item_record, history);
}

/******************************************************************************
 *                                                                            *
 * Function: hk_history_update                                                *
 *                                                                            *
 * Purpose: updates history housekeeping rule with the latest item history    *
 *          settings and prepares delete queue                                *
 *                                                                            *
 * Parameters: rule  - [IN/OUT] the history housekeeping rule                 *
 *             now   - [IN] the current timestamp                             *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Andris Zeila                                                       *
 *                                                                            *
 * Comments: This function is called to release resources allocated by        *
 *           history housekeeping rule after housekeeping was disabled        *
 *           for the table referred by this rule.                             *
 *                                                                            *
 ******************************************************************************/
static void	hk_history_update(zbx_hk_history_rule_t *rules, unsigned int now)
{
	DB_RESULT       	result;
	DB_ROW          	row;

	result = DBselect("select i.itemid,i.value_type,i.history,i.trends from items i,hosts h"
			" where i.hostid=h.hostid and h.status in (%d,%d)",
			HOST_STATUS_MONITORED, HOST_STATUS_NOT_MONITORED);

	while (NULL != (row = DBfetch(result)))
	{
		zbx_uint64_t		itemid;
		unsigned int		history, trends, value_type;
		zbx_hk_history_rule_t 	*rule;

		if (FAIL == is_uint64(row[0], &itemid) ||  FAIL == is_uint32(row[1], &value_type) ||
				FAIL == is_uint32(row[2], &history) || FAIL == is_uint32(row[3], &trends))
			continue;

		if (value_type < ITEM_VALUE_TYPE_COUNT)
		{
			rule = rules + value_type;
			if (0 == (rule->state & HK_ITEM_RULE_DELETE_QUEUE_PREPARED))
			{
				if (HK_OPTION_ENABLED == *rule->poption_global)
					history = *rule->poption;
				hk_history_item_update(rule, now, itemid, history);
			}
		}
		if (value_type == ITEM_VALUE_TYPE_FLOAT)
		{
			rule = rules + HK_UPADTE_CACHE_OFFSET_TREND_FLOAT;
			if (0 == (rule->state & HK_ITEM_RULE_DELETE_QUEUE_PREPARED))
			{
				if (HK_OPTION_ENABLED == *rule->poption_global)
					trends = *rule->poption;
				hk_history_item_update(rule, now, itemid, trends);
			}
		}
		else if (value_type == ITEM_VALUE_TYPE_UINT64)
		{
			rule = rules + HK_UPADTE_CACHE_OFFSET_TREND_UINT;

			if (0 == (rule->state & HK_ITEM_RULE_DELETE_QUEUE_PREPARED))
			{
				if (HK_OPTION_ENABLED == *rule->poption_global)
					trends = *rule->poption;
				hk_history_item_update(rule, now, itemid, trends);
			}

		}
	}
}


/******************************************************************************
 *                                                                            *
 * Function: hk_history_delete_queue_prepare_all                              *
 *                                                                            *
 * Purpose: prepares history housekeeping delete queues for all defined       *
 *          history rules.                                                    *
 *                                                                            *
 * Parameters: rules  - [IN/OUT] the history housekeeping rules               *
 *             now    - [IN] the current timestamp                            *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Andris Zeila                                                       *
 *                                                                            *
 * Comments: This function also handles history rule initializing/releasing   *
 *           when the rule just became enabled/disabled.                      *
 *                                                                            *
 ******************************************************************************/
static void	hk_history_delete_queue_prepare_all(zbx_hk_history_rule_t *rules, unsigned int now)
{
	int			update_cache = 0;
	zbx_hk_history_rule_t 	*rule;
	const char	*__function_name = "hk_history_delete_queue_prepare_all";

	/* prepare history item cache (hashset containing itemid:min_clock values) */
	for (rule = rules; NULL != rule->table; rule++)
	{
		if (HK_OPTION_ENABLED == *rule->poption_mode)
		{
			if (HK_ITEM_RULE_NOT_INITIALIZED == rule->state)
				hk_history_prepare(rule, now);
			else
				update_cache = 1;
		}
		else if (rule->state & HK_ITEM_RULE_ITEM_CACHE_PREPARED)
			hk_history_release(rule);
	}

	if (1 == update_cache)
	{
		/* cache update is requested because already initialized rule was found */
		hk_history_update(rules, now);
	}

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

/******************************************************************************
 *                                                                            *
 * Function: hk_history_delete_queue_clear                                    *
 *                                                                            *
 * Purpose: clears the history housekeeping delete queue                      *
 *                                                                            *
 * Parameters: rule   - [IN/OUT] the history housekeeping rule                *
 *             now    - [IN] the current timestamp                            *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Andris Zeila                                                       *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static void	hk_history_delete_queue_clear(zbx_hk_history_rule_t *rule)
{
	int	i;

	for (i = 0; i < rule->delete_queue.values_num; i++)
	{
		free(rule->delete_queue.values[i]);
	}
	rule->delete_queue.values_num = 0;
	rule->state &= (~HK_ITEM_RULE_DELETE_QUEUE_PREPARED);
}


/******************************************************************************
 *                                                                            *
 * Function: housekeeping_history_and_trends                                  *
 *                                                                            *
 * Purpose: performs housekeeping for history and trends tables               *
 *                                                                            *
 * Parameters: now    - [IN] the current timestamp                            *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Andris Zeila                                                       *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
int	housekeeping_history_and_trends(unsigned int now)
{
	const char	*__function_name = "housekeeping_history_and_trends";
	int			deleted = 0, i;
	zbx_hk_history_rule_t 	*rule;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() now:%d", __function_name, now);

	/* prepare delete queues for all history housekeeping rules */
	hk_history_delete_queue_prepare_all(hk_history_rules, now);

	for (rule = hk_history_rules; NULL != rule->table; rule++)
	{
		if (HK_OPTION_DISABLED == *rule->poption_mode) continue;

		/* process housekeeping rule */

		zbx_vector_ptr_sort(&rule->delete_queue, hk_item_update_cache_compare);

		for (i = 0; i < rule->delete_queue.values_num; i++)
		{
			zbx_hk_delete_queue_t	*item_record = rule->delete_queue.values[i];
			deleted += DBexecute("delete from %s where itemid=" ZBX_FS_UI64 " and clock<%d",
					rule->table, item_record->itemid, item_record->min_clock);
		}

		/* clear history rule delete queue so it's ready for the next housekeeping cycle */
		hk_history_delete_queue_clear(rule);
	}

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%d", __function_name, deleted);

	return deleted;
}

/******************************************************************************
 *                                                                            *
 * Function: housekeeping_read_config                                         *
 *                                                                            *
 * Purpose: read housekeeping configuration from database                     *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value: SUCCEED - the configuration was read successfully            *
 *               FAIL    - failed to read configuration. This function fails  *
 *                         if it can't read the housekeeping parameters from  *
 *                         database or if the parameters are out of the       *
 *                         defined range.                                     *
 *                                                                            *
 * Author: Andris Zeila                                                       *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int	housekeeping_read_config()
{
	const char		*__function_name = "housekeeping_read_config";
	DB_RESULT       	result;
	DB_ROW          	row;
	int			ret = FAIL, i;
	char			*sql_fields = NULL;
	size_t			sql_size = 0, sql_offset = 0;
	zbx_hk_db_config_t	*pfield;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	/* assemble field list for select statement */
	pfield = hk_db_fields;
	zbx_strcpy_alloc(&sql_fields, &sql_size, &sql_offset, pfield->field);

	for (pfield++; NULL != pfield->pvalue; pfield++)
	{
		zbx_chrcpy_alloc(&sql_fields, &sql_size, &sql_offset, ',');
		zbx_strcpy_alloc(&sql_fields, &sql_size, &sql_offset, pfield->field);
	}

	result = DBselect("select %s from config", sql_fields);
	zbx_free(sql_fields);

	if (NULL != (row = DBfetch(result)) && SUCCEED != DBis_null(row[0]))
	{
		for (i =  0; NULL != hk_db_fields[i].pvalue; i++)
		{
			pfield = &hk_db_fields[i];
			if (FAIL == is_uint_range(row[i], pfield->pvalue, pfield->min_value, pfield->max_value))
			{
				zabbix_log(LOG_LEVEL_WARNING, "failed to read housekeeper configuration, invalid field value: hk_%s=%s",
						pfield->field, row[i]);
			}
		}
		ret = SUCCEED;
	}

	DBfree_result(result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);

	return SUCCEED;
}

/******************************************************************************
 *                                                                            *
 * Function: housekeeping_process_rule                                        *
 *                                                                            *
 * Purpose: removes old records from a table according to the specified rule  *
 *                                                                            *
 * Parameters: now  - [IN] the current time in seconds                        *
 *             rule - [IN/OUT] the housekeeping rule specifying table to      *
 *                    clean and the required data (fields, filters, time)     *
 *                                                                            *
 * Return value: the number of deleted records                                *
 *                                                                            *
 * Author: Andris Zeila                                                       *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int	housekeeping_process_rule(unsigned int now, zbx_hk_rule_t *rule)
{
	DB_RESULT		result;
	DB_ROW			row;
	unsigned int	keep_from, deleted = 0;

	/* initialize min_clock with the oldest record timestamp from database */
	if (0 == rule->min_clock)
	{
		result = DBselect("select min(clock) from %s%s%s", rule->table,
				('\0' != *rule->filter ? " where " : ""), rule->filter);
		if (NULL != (row = DBfetch(result)) && SUCCEED != DBis_null(row[0]))
			is_uint32(row[0], &rule->min_clock);
		else
			rule->min_clock = now;

		DBfree_result(result);
	}

	/* Delete the old records from database. Don't remove more than 4 x housekeeping */
	/* periods worth of data to prevent database stalling.                           */
	keep_from = now - *rule->phistory * SEC_PER_DAY;
	if (keep_from > rule->min_clock)
	{
		rule->min_clock = MIN(keep_from, rule->min_clock +
				HK_MAX_DELETE_PERIODS * CONFIG_HOUSEKEEPING_FREQUENCY * SEC_PER_HOUR);

		deleted = DBexecute("delete from %s where %s%sclock<%d", rule->table, rule->filter,
				('\0' != *rule->filter ? " and " : ""), rule->min_clock);
	}

	return deleted;
}


/******************************************************************************
 *                                                                            *
 * Function: housekeeping_cleanup                                             *
 *                                                                            *
 * Purpose: remove deleted items data                                         *
 *                                                                            *
 * Return value: number of rows deleted                                       *
 *                                                                            *
 * Author: Alexei Vladishev, Dmitry Borovikov                                 *
 *                                                                            *
 * Comments: sqlite3 does not use CONFIG_MAX_HOUSEKEEPER_DELETE, deletes all  *
 *                                                                            *
 ******************************************************************************/
static int	housekeeping_cleanup()
{
	const char		*__function_name = "housekeeping_cleanup";
	DB_HOUSEKEEPER		housekeeper;
	DB_RESULT		result;
	DB_ROW			row;
	int			d, deleted = 0;
	zbx_vector_uint64_t	housekeeperids;
	char			*sql_tables = NULL;
	size_t			sql_size = 0, sql_offset = 0;
	zbx_hk_cleanup_table_t *table;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	/* first handle the trivial case when history and trend housekeeping is disabled */
	if (HK_OPTION_DISABLED == hk_config.history_mode && HK_OPTION_DISABLED == hk_config.trends_mode)
		goto out;

	zbx_vector_uint64_create(&housekeeperids);

	/* assemble list of tables excluded from housekeeping procedure */
	for (table = hk_cleanup_tables; NULL != table->name; table++)
	{
		if (HK_OPTION_ENABLED == *table->poption_mode)
			continue;

		zbx_strcpy_alloc(&sql_tables, &sql_size, &sql_offset, (NULL == sql_tables ? " where" : " and"));
		zbx_snprintf_alloc(&sql_tables, &sql_size, &sql_offset, " tablename<>'%s'", table->name);
	}

	/* order by tablename to effectively use DB cache */
	result = DBselect(
			"select housekeeperid,tablename,field,value"
			" from housekeeper%s"
			" order by tablename", (NULL != sql_tables ? sql_tables : ""));

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(housekeeper.housekeeperid, row[0]);
		housekeeper.tablename = row[1];
		housekeeper.field = row[2];
		ZBX_STR2UINT64(housekeeper.value, row[3]);

		if (0 == CONFIG_MAX_HOUSEKEEPER_DELETE)
		{
			d = DBexecute(
					"delete from %s"
					" where %s=" ZBX_FS_UI64,
					housekeeper.tablename,
					housekeeper.field,
					housekeeper.value);
		}
		else
		{
#if defined(HAVE_IBM_DB2) || defined(HAVE_ORACLE)
			d = DBexecute(
					"delete from %s"
					" where %s=" ZBX_FS_UI64
						" and rownum<=%d",
					housekeeper.tablename,
					housekeeper.field,
					housekeeper.pvalue,
					CONFIG_MAX_HOUSEKEEPER_DELETE);
#elif defined(HAVE_MYSQL)
			d = DBexecute(
					"delete from %s"
					" where %s=" ZBX_FS_UI64 " limit %d",
					housekeeper.tablename,
					housekeeper.field,
					housekeeper.value,
					CONFIG_MAX_HOUSEKEEPER_DELETE);
#elif defined(HAVE_POSTGRESQL)
			d = DBexecute(
					"delete from %s"
					" where ctid = any(array(select ctid from %s"
						" where %s=" ZBX_FS_UI64 " limit %d))",
					housekeeper.tablename,
					housekeeper.tablename,
					housekeeper.field,
					housekeeper.pvalue,
					CONFIG_MAX_HOUSEKEEPER_DELETE);
#elif defined(HAVE_SQLITE3)
			d = 0;
#endif
		}

		if (0 == d || 0 == CONFIG_MAX_HOUSEKEEPER_DELETE || CONFIG_MAX_HOUSEKEEPER_DELETE > d)
			zbx_vector_uint64_append(&housekeeperids, housekeeper.housekeeperid);

		deleted += d;
	}
	DBfree_result(result);

	if (0 != housekeeperids.values_num)
	{
		char	*sql = NULL;
		size_t	sql_alloc = 512, sql_offset = 0;

		sql = zbx_malloc(sql, sql_alloc);

		zbx_vector_uint64_sort(&housekeeperids, ZBX_DEFAULT_UINT64_COMPARE_FUNC);

		zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset, "delete from housekeeper where");
		DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "housekeeperid",
				housekeeperids.values, housekeeperids.values_num);

		DBexecute("%s", sql);

		zbx_free(sql);
	}

	zbx_vector_uint64_destroy(&housekeeperids);

out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%d", __function_name, deleted);

	return deleted;
}

static int	housekeeping_sessions(int now)
{
	const char	*__function_name = "housekeeping_sessions";
	int		deleted = 0;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() now:%d", __function_name, now);

	if (HK_OPTION_ENABLED == hk_config.sessions_mode)
		deleted = DBexecute("delete from sessions where lastaccess<%d", now - SEC_PER_DAY * hk_config.sessions);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%d", __function_name, deleted);

	return deleted;
}

static int	housekeeping_services(int now)
{
	const char		*__function_name = "housekeeping_services";
	int			deleted = 0;
	static zbx_hk_rule_t	rule = {"service_alarms", "", 0, &hk_config.services};

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() now:%d", __function_name, now);

	if (HK_OPTION_ENABLED == hk_config.services_mode)
		deleted = housekeeping_process_rule(now, &rule);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%d", __function_name, deleted);

	return deleted;
}

static int	housekeeping_audit(int now)
{
	const char		*__function_name = "housekeeping_audit";
	int			deleted = 0;
	static zbx_hk_rule_t	rule = {"auditlog", "", 0, &hk_config.audit};

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() now:%d", __function_name, now);

	if (HK_OPTION_ENABLED == hk_config.audit_mode)
		deleted = housekeeping_process_rule(now, &rule);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%d", __function_name, deleted);

	return deleted;
}

static int	housekeeping_events(int now)
{
	const char		*__function_name = "housekeeping_events";
	int			deleted = 0;
	zbx_hk_rule_t		*rule;
	static zbx_hk_rule_t 	rules[] = {
		{"events", "source="ZBX_STR(EVENT_SOURCE_TRIGGERS), 0, &hk_config.events_trigger},
		{"events", "source="ZBX_STR(EVENT_SOURCE_INTERNAL), 0, &hk_config.events_internal},
		{"events", "source="ZBX_STR(EVENT_SOURCE_DISCOVERY), 0, &hk_config.events_discovery},
		{"events", "source="ZBX_STR(EVENT_SOURCE_AUTO_REGISTRATION), 0, &hk_config.events_autoreg},
		{0}
	};

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() now:%d", __function_name, now);

	if (HK_OPTION_ENABLED == hk_config.events_mode)
		for (rule = rules; NULL != rule->table; rule++)
			deleted += housekeeping_process_rule(now, rule);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%d", __function_name, deleted);

	return deleted;
}

void	main_housekeeper_loop()
{
	int	now, d_history_and_trends, d_cleanup, d_events, d_sessions;
	int	d_services, d_audit;

	for (;;)
	{
		zabbix_log(LOG_LEVEL_WARNING, "executing housekeeper");
		now = time(NULL);

		zbx_setproctitle("%s [connecting to the database]", get_process_type_string(process_type));
		DBconnect(ZBX_DB_CONNECT_NORMAL);

		housekeeping_read_config();

		zbx_setproctitle("%s [removing old history and trends]", get_process_type_string(process_type));
		d_history_and_trends = housekeeping_history_and_trends(now);

		zbx_setproctitle("%s [removing deleted items data]", get_process_type_string(process_type));
		d_cleanup = housekeeping_cleanup();

		zbx_setproctitle("%s [removing old events]", get_process_type_string(process_type));
		d_events = housekeeping_events(now);

		zbx_setproctitle("%s [removing old sessions]", get_process_type_string(process_type));
		d_sessions = housekeeping_sessions(now);

		zbx_setproctitle("%s [removing old service alarms]", get_process_type_string(process_type));
		d_services = housekeeping_services(now);

		zbx_setproctitle("%s [removing old audit log items]", get_process_type_string(process_type));
		d_audit = housekeeping_audit(now);

		zabbix_log(LOG_LEVEL_WARNING, "housekeeper deleted: %d records from history and trends,"
				" %d records of deleted items, %d events, %d sessions,"
				" %d service alarms, %d audit items",
				d_history_and_trends, d_cleanup, d_events, d_sessions, d_services, d_audit);

		DBclose();

		zbx_sleep_loop(CONFIG_HOUSEKEEPING_FREQUENCY * SEC_PER_HOUR);
	}
}
