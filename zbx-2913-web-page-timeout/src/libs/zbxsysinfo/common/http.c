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
#include "sysinfo.h"

#include "log.h"
#include "comms.h"
#include "cfg.h"

#include "http.h"

#define ZABBIX_MAX_WEBPAGE_SIZE	1*1024*1024

static int	get_http_page(const char *host, const char *path, unsigned short port, char *buffer, int max_buf_len)
{
	int		ret;
	char		*buf, request[MAX_STRING_LEN];
	zbx_sock_t	s;

	assert(buffer);

	if (SUCCEED == (ret = zbx_tcp_connect(&s, CONFIG_SOURCE_IP, host, port, MAX(CONFIG_TIMEOUT - 1, 1))))
	{
		zbx_snprintf(request, sizeof(request),
				"GET /%s HTTP/1.1\r\n"
				"Host: %s\r\n"
				"Connection: close\r\n"
				"\r\n",
			path,
			host);

		if (SUCCEED == (ret = zbx_tcp_send_raw(&s, request)))
		{
			if (SUCCEED == (ret = zbx_tcp_recv_ext(&s, &buf, ZBX_TCP_READ_UNTIL_CLOSE, 0)))
			{
				zbx_rtrim(buf, "\r\n");

				zbx_snprintf(buffer, max_buf_len, "%s", buf);
			}
		}
	}

	zbx_tcp_close(&s);

	if (FAIL == ret)
	{
		zabbix_log(LOG_LEVEL_DEBUG, "HTTP get error: %s", zbx_tcp_strerror());
		return SYSINFO_RET_FAIL;
	}

	return SYSINFO_RET_OK;
}

int	WEB_PAGE_GET(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	char	hostname[MAX_STRING_LEN];
	char	path[MAX_STRING_LEN];
	char	port_str[MAX_STRING_LEN];

	char	*buffer = NULL;

	assert(result);

	init_result(result);

        if (num_param(param) > 3)
                return SYSINFO_RET_FAIL;

	if (0 != get_param(param, 1, hostname, sizeof(hostname)))
                return SYSINFO_RET_FAIL;

	if (0 != get_param(param, 2, path, sizeof(path)))
		path[0] = '\0';

	if (0 != get_param(param, 3, port_str, sizeof(port_str)) || '\0' == port_str[0])
		zbx_snprintf(port_str, sizeof(port_str), "%d", ZBX_DEFAULT_HTTP_PORT);
	else if (FAIL == is_uint(port_str))
		return SYSINFO_RET_FAIL;

	buffer = zbx_malloc(buffer, ZABBIX_MAX_WEBPAGE_SIZE);

	if (SYSINFO_RET_OK == get_http_page(hostname, path, (unsigned short)atoi(port_str), buffer, ZABBIX_MAX_WEBPAGE_SIZE))
	{
		SET_TEXT_RESULT(result, buffer);
	}
	else
	{
		zbx_free(buffer);
		SET_TEXT_RESULT(result, strdup("EOF"));
	}

	return SYSINFO_RET_OK;
}

int	WEB_PAGE_PERF(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	char	hostname[MAX_STRING_LEN];
	char	path[MAX_STRING_LEN];
	char	port_str[MAX_STRING_LEN];

	char	*buffer = NULL;

	double	start_time;

        assert(result);

        init_result(result);

        if (num_param(param) > 3)
                return SYSINFO_RET_FAIL;

	if (0 != get_param(param, 1, hostname, sizeof(hostname)))
                return SYSINFO_RET_FAIL;

	if (0 != get_param(param, 2, path, sizeof(path)))
		path[0] = '\0';

	if (0 != get_param(param, 3, port_str, sizeof(port_str)) || '\0' == port_str[0])
		zbx_snprintf(port_str, sizeof(port_str), "%d", ZBX_DEFAULT_HTTP_PORT);
	else if (FAIL == is_uint(port_str))
		return SYSINFO_RET_FAIL;

	buffer = zbx_malloc(buffer, ZABBIX_MAX_WEBPAGE_SIZE);

	start_time = zbx_time();

	if (SYSINFO_RET_OK == get_http_page(hostname, path, (unsigned short)atoi(port_str), buffer, ZABBIX_MAX_WEBPAGE_SIZE))
	{
		SET_DBL_RESULT(result, zbx_time() - start_time);
	}
	else
		SET_DBL_RESULT(result, 0.0);

	zbx_free(buffer);

	return SYSINFO_RET_OK;
}

int	WEB_PAGE_REGEXP(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	char	hostname[MAX_STRING_LEN];
	char	path[MAX_STRING_LEN];
	char	port_str[MAX_STRING_LEN];
	char	regexp[MAX_STRING_LEN];
	char	len_str[MAX_STRING_LEN];
	char	back[MAX_STRING_LEN];

	char	*buffer = NULL;
	char	*found;

	int	len, found_len;

        assert(result);

        init_result(result);

        if (num_param(param) > 5)
                return SYSINFO_RET_FAIL;

	if (0 != get_param(param, 1, hostname, sizeof(hostname)))
                return SYSINFO_RET_FAIL;

	if (0 != get_param(param, 2, path, sizeof(path)))
		path[0] = '\0';

	if (0 != get_param(param, 3, port_str, sizeof(port_str)) || '\0' == port_str[0])
		zbx_snprintf(port_str, sizeof(port_str), "%d", ZBX_DEFAULT_HTTP_PORT);
	else if (FAIL == is_uint(port_str))
		return SYSINFO_RET_FAIL;

	if (0 != get_param(param, 4, regexp, sizeof(regexp)))
                return SYSINFO_RET_FAIL;

	if (0 != get_param(param, 5, len_str, sizeof(len_str)) || '\0' == len_str[0])
		zbx_snprintf(len_str, sizeof(len_str), "%d", ZABBIX_MAX_WEBPAGE_SIZE);
	else if (FAIL == is_uint(len_str))
		return SYSINFO_RET_FAIL;

	buffer = zbx_malloc(buffer, ZABBIX_MAX_WEBPAGE_SIZE);

	if (SYSINFO_RET_OK == get_http_page(hostname, path, (unsigned short)atoi(port_str), buffer, ZABBIX_MAX_WEBPAGE_SIZE))
	{
		if (NULL != (found = zbx_regexp_match(buffer, regexp, &found_len)))
		{
			len = atoi(len_str) + 1;
			len = MIN(len, found_len + 1);
			len = MIN(len, sizeof(back));

			zbx_strlcpy(back, found, len);
			SET_STR_RESULT(result, strdup(back));
		}
		else
			SET_STR_RESULT(result, strdup("EOF"));
	}
	else
		SET_STR_RESULT(result, strdup("EOF"));

	zbx_free(buffer);

	return SYSINFO_RET_OK;
}
