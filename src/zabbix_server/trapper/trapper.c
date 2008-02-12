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

#include "cfg.h"
#include "comms.h"
#include "pid.h"
#include "db.h"
#include "log.h"
#include "zlog.h"
#include "zbxjson.h"

#include "../functions.h"
#include "../expression.h"

#include "../nodewatcher/nodecomms.h"
#include "../nodewatcher/nodesender.h"
#include "nodesync.h"
#include "nodehistory.h"
#include "trapper.h"
#include "active.h"
#include "nodecommand.h"
#include "proxyconfig.h"

#include "daemon.h"

static zbx_process_t	zbx_process;

static int	process_trap(zbx_sock_t	*sock,char *s, int max_len)
{
	char	*line,*host;
	char	*server,*key,*value_string, *data;
	char	copy[MAX_STRING_LEN];
	char	host_dec[MAX_STRING_LEN],key_dec[MAX_STRING_LEN],value_dec[MAX_STRING_LEN];
	char	lastlogsize[MAX_STRING_LEN];
	char	timestamp[MAX_STRING_LEN];
	char	source[MAX_STRING_LEN];
	char	severity[MAX_STRING_LEN];
	int	sender_nodeid, nodeid;
	char	*answer;

	int	ret=SUCCEED, res;
	size_t	datalen;

	struct 		zbx_json_parse jp;
	const char 	*p;
	char		value[MAX_STRING_LEN];

	zbx_rtrim(s, " \r\n\0");

	datalen = strlen(s);
	zabbix_log( LOG_LEVEL_DEBUG, "Trapper got [%s] len %zd",
		s,
		datalen);

/* Request for list of active checks */
	if (strncmp(s,"ZBX_GET_ACTIVE_CHECKS", 21) == 0) {
		line=strtok(s,"\n");
		host=strtok(NULL,"\n");
		if(host == NULL)
		{
			zabbix_log( LOG_LEVEL_WARNING, "ZBX_GET_ACTIVE_CHECKS: host is null. Ignoring.");
		}
		else
		{
			ret = send_list_of_active_checks(sock, host);
		}
/* Request for last ids */
	} else if (strncmp(s,"ZBX_GET_HISTORY_LAST_ID", 23) == 0) {
		send_history_last_id(sock, s);
		return ret;
	} else if (strncmp(s,"ZBX_GET_TRENDS_LAST_ID", 22) == 0) {
		send_trends_last_id(sock, s);
		return ret;
/* Process information sent by zabbix_sender */
	} else {
		/* Command? */
		if(strncmp(s,"Command",7) == 0)
		{
			node_process_command(sock, s);
			return ret;
		}
		/* Node data exchange? */
		if(strncmp(s,"Data",4) == 0)
		{
			node_sync_lock(0);

/*			zabbix_log( LOG_LEVEL_WARNING, "Node data received [len:%d]", strlen(s)); */
			res = node_sync(s, &sender_nodeid, &nodeid);
			if (FAIL == res)
				send_data_to_node(sender_nodeid, sock, "FAIL");
			else {
				res = calculate_checksums(nodeid, NULL, 0);
				if (SUCCEED == res && NULL != (data = get_config_data(nodeid, ZBX_NODE_SLAVE))) {
					res = send_data_to_node(sender_nodeid, sock, data);
					zbx_free(data);
					if (SUCCEED == res)
						res = recv_data_from_node(sender_nodeid, sock, &answer);
					if (SUCCEED == res && 0 == strcmp(answer, "OK"))
						res = update_checksums(nodeid, ZBX_NODE_SLAVE, SUCCEED, NULL, 0, NULL);
				}
			}

			node_sync_unlock(0);

			return ret;
		}
		/* Slave node history ? */
		if(strncmp(s,"History",7) == 0)
		{
/*			zabbix_log( LOG_LEVEL_WARNING, "Slave node history received [len:%d]", strlen(s)); */
			if (node_history(s, datalen) == SUCCEED) {
				if (zbx_tcp_send_raw(sock,"OK") != SUCCEED) {
					zabbix_log( LOG_LEVEL_WARNING, "Error sending confirmation to node");
					zabbix_syslog("Trapper: error sending confirmation to node");
				}
			}
			return ret;
		}
		/* JSON protocol? */
		else if (SUCCEED == zbx_json_open(s, &jp))
		{
			if (NULL != (p = zbx_json_pair_by_name(&jp, "request"))
					&& NULL != zbx_json_decodevalue(p, value, sizeof(value))) {
				if (0 == strcmp(value, "ZBX_PROXY_CONFIG") && zbx_process == ZBX_PROCESS_SERVER)
					send_proxyconfig(sock, &jp);
			}
			return ret;
		}
		/* New XML protocol? */
		else if(s[0]=='<')
		{
			zabbix_log( LOG_LEVEL_DEBUG, "XML received [%s]", s);

			comms_parse_response(s,host_dec,key_dec,value_dec,lastlogsize,timestamp,source,severity,sizeof(host_dec)-1);

			server=host_dec;
			value_string=value_dec;
			key=key_dec;
		}
		else
		{
			strscpy(copy,s);

			server=(char *)strtok(s,":");
			if(NULL == server)
			{
				return FAIL;
			}

			key=(char *)strtok(NULL,":");
			if(NULL == key)
			{
				return FAIL;
			}
	
			value_string=strchr(copy,':');
			value_string=strchr(value_string+1,':');

			if(NULL == value_string)
			{
				return FAIL;
			}
			/* It points to ':', so have to increment */
			value_string++;
			lastlogsize[0]=0;
			timestamp[0]=0;
			source[0]=0;
			severity[0]=0;
		}
		zabbix_log( LOG_LEVEL_DEBUG, "Value [%s]", value_string);

		DBbegin();
		ret=process_data(sock,server,key,value_string,lastlogsize,timestamp,source,severity);
		DBcommit();
		
		if( zbx_tcp_send_raw(sock, SUCCEED == ret ? "OK" : "NOT OK") != SUCCEED)
		{
			zabbix_log( LOG_LEVEL_WARNING, "Error sending result back");
			zabbix_syslog("Trapper: error sending result back");
		}
		zabbix_log( LOG_LEVEL_DEBUG, "After write()");
	}	
	return ret;
}

void	process_trapper_child(zbx_sock_t *sock)
{
	char	*data;

/* suseconds_t is not defined under HP-UX */
/*	struct timeval tv;
	suseconds_t    msec;
	gettimeofday(&tv, NULL);
	msec = tv.tv_usec;*/

/*	alarm(CONFIG_TIMEOUT);*/

	if(zbx_tcp_recv(sock, &data) != SUCCEED)
	{
/*		alarm(0);*/
		return;
	}

	process_trap(sock, data, sizeof(data));
/*	alarm(0);*/

/*	gettimeofday(&tv, NULL);
	zabbix_log( LOG_LEVEL_DEBUG, "Trap processed in " ZBX_FS_DBL " seconds",
		(double)(tv.tv_usec-msec)/1000000 );*/
}

void	child_trapper_main(zbx_process_t p, zbx_sock_t *s)
{
	zabbix_log( LOG_LEVEL_DEBUG, "In child_trapper_main()");

	zbx_process = p;

	DBconnect(ZBX_DB_CONNECT_NORMAL);

	for (;;) {
		zbx_setproctitle("waiting for connection");
		zbx_tcp_accept(s);

		zbx_setproctitle("processing data");
		process_trapper_child(s);

		zbx_tcp_unaccept(s);
	}
	DBclose();
}

/*
pid_t	child_trapper_make(int i,int listenfd, int addrlen)
{
	pid_t	pid;

	if((pid = zbx_fork()) >0)
	{
		return (pid);
	}

	child_trapper_main(i, listenfd, addrlen);

	return 0;
}*/
