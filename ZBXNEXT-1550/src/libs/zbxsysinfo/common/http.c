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
#include "sysinfo.h"

#include "log.h"
#include "comms.h"
#include "cfg.h"

#include "http.h"

#define ZBX_MAX_WEBPAGE_SIZE	(1 * 1024 * 1024)

static int	get_http_page(const char *host, const char *path, unsigned short port, char *buffer, int max_buffer_len)
{
	int		ret;
	char		*recv_buffer;
	char		request[MAX_STRING_LEN];
	zbx_sock_t	s;

	if (SUCCEED == (ret = zbx_tcp_connect(&s, CONFIG_SOURCE_IP, host, port, CONFIG_TIMEOUT)))
	{
		zbx_snprintf(request, sizeof(request),
				"GET /%s HTTP/1.1\r\n"
				"Host: %s\r\n"
				"Connection: close\r\n"
				"\r\n",
				path, host);

		if (SUCCEED == (ret = zbx_tcp_send_raw(&s, request)))
		{
			if (SUCCEED == (ret = SUCCEED_OR_FAIL(zbx_tcp_recv_ext(&s, &recv_buffer, ZBX_TCP_READ_UNTIL_CLOSE, 0))))
			{
				if (NULL != buffer)
					zbx_strlcpy(buffer, recv_buffer, max_buffer_len);
			}
		}

		zbx_tcp_close(&s);
	}

	if (FAIL == ret)
	{
		zabbix_log(LOG_LEVEL_DEBUG, "HTTP get error: %s", zbx_tcp_strerror());
		return SYSINFO_RET_FAIL;
	}

	return SYSINFO_RET_OK;
}

int	WEB_PAGE_GET(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*hostname, *path_str, *port_str;
	char	buffer[MAX_BUFFER_LEN], path[MAX_STRING_LEN];
	unsigned short	port;

	if (3 < request->nparam)
		return SYSINFO_RET_FAIL;

	hostname = get_rparam(request, 0);
	path_str = get_rparam(request, 1);
	port_str = get_rparam(request, 2);

	if (NULL == hostname || '\0' == *hostname)
                return SYSINFO_RET_FAIL;

	if (NULL == path_str)
		*path = '\0';
	else
		strscpy(path, path_str);

	if (NULL == port_str || '\0' == *port_str)
		port = ZBX_DEFAULT_HTTP_PORT;
	else if (FAIL != is_uint(port_str))
		port = (unsigned int)atoi(port_str);
	else
		return SYSINFO_RET_FAIL;

	if (SYSINFO_RET_OK == get_http_page(hostname, path, port, buffer, sizeof(buffer)))
	{
		zbx_rtrim(buffer, "\r\n");
		SET_TEXT_RESULT(result, strdup(buffer));
	}
	else
		SET_TEXT_RESULT(result, strdup("EOF"));

	return SYSINFO_RET_OK;
}

int	WEB_PAGE_PERF(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*hostname;
	char	path[MAX_STRING_LEN];
	char	*port_str, *path_str;
	double	start_time;
	unsigned int	port;

	if (3 < request->nparam)
		return SYSINFO_RET_FAIL;

	hostname = get_rparam(request, 0);
	path_str = get_rparam(request, 1);
	port_str = get_rparam(request, 2);

	if (NULL == hostname)
                return SYSINFO_RET_FAIL;

	if (NULL == path_str || '\0' == *path_str)
		*path = '\0';
	else
		strscpy(path, path_str);

	if (NULL == port_str || '\0' == *port_str)
		port = ZBX_DEFAULT_HTTP_PORT;
	else if (FAIL != is_uint(port_str))
		port = (unsigned int)atoi(port_str);
	else
		return SYSINFO_RET_FAIL;

	start_time = zbx_time();

	if (SYSINFO_RET_OK == get_http_page(hostname, path, port, NULL, 0))
	{
		SET_DBL_RESULT(result, zbx_time() - start_time);
	}
	else
		SET_DBL_RESULT(result, 0.0);

	return SYSINFO_RET_OK;
}

int	WEB_PAGE_REGEXP(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*hostname, *path_str, *port_str, *regexp, *length_str;
	char	path[MAX_STRING_LEN];
	char	back[MAX_BUFFER_LEN];
	char	*buffer = NULL, *found;
	int	length, len, found_len;
	unsigned int	port;

	if (5 < request->nparam)
		return SYSINFO_RET_FAIL;

	hostname = get_rparam(request, 0);
	path_str = get_rparam(request, 1);
	port_str = get_rparam(request, 2);
	regexp = get_rparam(request, 3);
	length_str = get_rparam(request, 4);

	if (NULL == hostname)
                return SYSINFO_RET_FAIL;

	if (NULL == path_str || '\0' == *path_str)
		*path = '\0';
	else
		strscpy(path, path_str);

	if (NULL == port_str || '\0' == *port_str)
		port = ZBX_DEFAULT_HTTP_PORT;
	else if (FAIL != is_uint(port_str))
		port = (unsigned int)atoi(port_str);
	else
		return SYSINFO_RET_FAIL;

	if (NULL == regexp)
                return SYSINFO_RET_FAIL;

	if (NULL == length_str || '\0' == *length_str)
		length = MAX_BUFFER_LEN - 1;
	else if (FAIL != is_uint(length_str))
		length = atoi(length_str);
	else
		return SYSINFO_RET_FAIL;

	buffer = zbx_malloc(buffer, ZBX_MAX_WEBPAGE_SIZE);

	if (SYSINFO_RET_OK == get_http_page(hostname, path, port, buffer, ZBX_MAX_WEBPAGE_SIZE))
	{
		if (NULL != (found = zbx_regexp_match(buffer, regexp, &found_len)))
		{
			len = length + 1;
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
