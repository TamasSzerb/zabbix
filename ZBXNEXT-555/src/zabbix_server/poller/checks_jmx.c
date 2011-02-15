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
#include "comms.h"
#include "log.h"

#include "zbxjson.h"

#include "checks_jmx.h"

static int	parse_response(DC_ITEM *items, AGENT_RESULT *results, int *errcodes, zbx_timespec_t *timespecs, int num,
			char *response, char *error, int max_error_len)
{
	const char		*p;
	struct zbx_json_parse	jp, jp_data, jp_row;
	char			value[MAX_BUFFER_LEN];
	int			i, ret = PROXY_ERROR;

	if (SUCCEED == zbx_json_open(response, &jp))
	{
		if (SUCCEED != zbx_json_value_by_name(&jp, ZBX_PROTO_TAG_RESPONSE, value, sizeof(value)))
		{
			zbx_snprintf(error, max_error_len, "No '%s' tag in received JSON", ZBX_PROTO_TAG_RESPONSE);
			goto exit;
		}

		if (0 == strcmp(value, ZBX_PROTO_VALUE_SUCCESS))
		{
			if (SUCCEED != zbx_json_brackets_by_name(&jp, ZBX_PROTO_TAG_DATA, &jp_data))
			{
				zbx_snprintf(error, max_error_len, "Cannot open data array in received JSON");
				goto exit;
			}

			p = NULL;

			for (i = 0; i < num; i++)
			{
				if (NULL == (p = zbx_json_next(&jp_data, p)))
				{
					zbx_snprintf(error, max_error_len, "Not all values included in received JSON");
					goto exit;
				}

				if (SUCCEED != zbx_json_brackets_open(p, &jp_row))
				{
					zbx_snprintf(error, max_error_len, "Cannot open value object in received JSON");
					goto exit;
				}

				if (SUCCEED == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_VALUE, value, sizeof(value)))
				{
					if (SUCCEED == set_result_type(&results[i],
								items[i].value_type, items[i].data_type, value))
						errcodes[i] = SUCCEED;
					else
						errcodes[i] = NOTSUPPORTED;
				}
				else if (SUCCEED == zbx_json_value_by_name(&jp_row, ZBX_PROTO_TAG_ERROR, value, sizeof(value)))
				{
					SET_MSG_RESULT(&results[i], zbx_strdup(NULL, value));
					errcodes[i] = NOTSUPPORTED;
				}
				else
				{
					SET_MSG_RESULT(&results[i], zbx_strdup(NULL, "Cannot get item value nor error message"));
					errcodes[i] = AGENT_ERROR;
				}
			}

			ret = SUCCEED;
		}
		else if (0 == strcmp(value, ZBX_PROTO_VALUE_FAILED))
		{
			if (SUCCEED == zbx_json_value_by_name(&jp, ZBX_PROTO_TAG_ERROR, error, max_error_len))
				ret = NETWORK_ERROR;
			else
				zbx_snprintf(error, max_error_len, "Cannot get error message describing reasons for failure");

			goto exit;
		}
		else
		{
			zbx_snprintf(error, max_error_len, "Bad '%s' tag value '%s' in received JSON",
					ZBX_PROTO_TAG_RESPONSE, value);
			goto exit;
		}
	}
	else
	{
		zbx_snprintf(error, max_error_len, "Cannot open received JSON");
		goto exit;
	}
exit:
	return ret;
}

int	get_value_jmx(DC_ITEM *item, AGENT_RESULT *result)
{
	int		res;
	zbx_timespec_t	ts;

	get_values_jmx(item, result, &res, &ts, 1);

	return res;
}

void	get_values_jmx(DC_ITEM *items, AGENT_RESULT *results, int *errcodes, zbx_timespec_t *timespecs, int num)
{
	const char	*__function_name = "get_values_jmx";

	zbx_sock_t	s;
	struct zbx_json	json;
	char		error[MAX_STRING_LEN];
	char		*buffer = NULL;
	int		i, err = SUCCEED;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() host:'%s' addr:'%s' num:%d",
			__function_name, items[0].host.host, items[0].interface.addr, num);

	zbx_json_init(&json, ZBX_JSON_STAT_BUF_LEN);

	if (NULL == CONFIG_JAVA_PROXY || '\0' == *CONFIG_JAVA_PROXY)
	{
		err = PROXY_ERROR;
		zbx_snprintf(error, sizeof(error), "JavaProxy configuration parameter not set or empty");
		goto exit;
	}

	for (i = 1; i < num; i++)
	{
		if (0 != strcmp(items[0].interface.addr, items[i].interface.addr) ||
				items[0].interface.port != items[i].interface.port ||
				0 != strcmp(items[0].username, items[i].username) ||
				0 != strcmp(items[0].password, items[i].password))
		{
			err = PROXY_ERROR;
			zbx_snprintf(error, sizeof(error), "Java poller received items with different connection parameters");
			goto exit;
		}
	}

	zbx_json_addstring(&json, ZBX_PROTO_TAG_REQUEST, ZBX_PROTO_VALUE_JAVA_PROXY_JMX_ITEMS, ZBX_JSON_TYPE_STRING);

	zbx_json_addstring(&json, ZBX_PROTO_TAG_CONN, items[0].interface.addr, ZBX_JSON_TYPE_STRING);
	zbx_json_adduint64(&json, ZBX_PROTO_TAG_PORT, items[0].interface.port);
	if ('\0' != items[0].username)
		zbx_json_addstring(&json, ZBX_PROTO_TAG_USERNAME, items[0].username, ZBX_JSON_TYPE_STRING);
	if ('\0' != items[0].password)
		zbx_json_addstring(&json, ZBX_PROTO_TAG_PASSWORD, items[0].password, ZBX_JSON_TYPE_STRING);

	zbx_json_addarray(&json, ZBX_PROTO_TAG_KEYS);
	for (i = 0; i < num; i++)
		zbx_json_addstring(&json, NULL, items[i].key, ZBX_JSON_TYPE_STRING);
	zbx_json_close(&json);

	if (SUCCEED == (err = zbx_tcp_connect(&s, CONFIG_SOURCE_IP,
					CONFIG_JAVA_PROXY, CONFIG_JAVA_PROXY_PORT, CONFIG_TIMEOUT)))
	{
		zabbix_log(LOG_LEVEL_DEBUG, "JSON before sending [%s]", json.buffer);

		if (SUCCEED == (err = zbx_tcp_send(&s, json.buffer)))
		{
			if (SUCCEED == (err = zbx_tcp_recv(&s, &buffer)))
			{
				zabbix_log(LOG_LEVEL_DEBUG, "JSON back [%s]", buffer);

				err = parse_response(items, results, errcodes, timespecs, num, buffer, error, sizeof(error));
			}
		}

		zbx_tcp_close(&s);
	}

	zbx_json_free(&json);

	if (FAIL == err)
	{
		strlcpy(error, zbx_tcp_strerror(), sizeof(error));
		err = PROXY_ERROR;
	}
exit:
	if (NETWORK_ERROR == err || PROXY_ERROR == err)
	{
		zabbix_log(LOG_LEVEL_DEBUG, "Getting JMX values failed: %s", error);

		for (i = 0; i < num; i++)
		{
			if (!ISSET_MSG(&results[i]))
			{
				SET_MSG_RESULT(&results[i], zbx_strdup(NULL, error));
				errcodes[i] = err;
			}
		}
	}

	zbx_timespec(&timespecs[0]);

	for (i = 1; i < num; i++)
		timespecs[i] = timespecs[0];

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}
