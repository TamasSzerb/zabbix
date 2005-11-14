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

#ifndef ZABBIX_COMMON_H
#define ZABBIX_COMMON_H

#include "sysinc.h"

#if defined(WIN32)
#	define zbx_uint64_t __int64
#	define ZBX_FS_UI64 "%llu"

#	ifdef _DEBUG
#		include "crtdbg.h"

#		define LOG_DEBUG_INFO(type, msg) \
			WriteLog(MSG_ACTIVE_CHECKS,EVENTLOG_ERROR_TYPE, type , msg);

#		define REINIT_CHECK_MEMORY(a) \
			_CrtMemCheckpoint(& ## a ## oldMemState)

#		define INIT_CHECK_MEMORY(a) \
			char a ## DumpMessage[0xFFFF]; \
			_CrtMemState  a ## oldMemState, a ## newMemState, a ## diffMemState; \
			REINIT_CHECK_MEMORY(a)

#		define CHECK_MEMORY(a, fncname, msg) \
			_CrtMemCheckpoint(& ## a ## newMemState); \
			if(_CrtMemDifference(& ## a ## diffMemState, & ## a ## oldMemState, & ## a ## newMemState)) \
			{ \
				sprintf(a ## DumpMessage, \
					"%s\n" \
					"free:  %10li bytes in %10li blocks\n" \
					"normal:%10li bytes in %10li blocks\n" \
					"CRT:   %10li bytes in %10li blocks\n" \
					"ignore:%10li bytes in %10li blocks\n" \
					"client:%10li bytes in %10li blocks\n" \
					"max:   %10li bytes in %10li blocks", \
					 \
					fncname ": (" #a ") Memory changed! (" msg ")\n", \
					 \
					(long) a ## diffMemState.lSizes[_FREE_BLOCK], \
					(long) a ## diffMemState.lCounts[_FREE_BLOCK], \
					 \
					(long) a ## diffMemState.lSizes[_NORMAL_BLOCK], \
					(long) a ## diffMemState.lCounts[_NORMAL_BLOCK], \
					 \
					(long) a ## diffMemState.lSizes[_CRT_BLOCK], \
					(long) a ## diffMemState.lCounts[_CRT_BLOCK], \
					 \
					(long) a ## diffMemState.lSizes[_IGNORE_BLOCK], \
					(long) a ## diffMemState.lCounts[_IGNORE_BLOCK], \
					 \
					(long) a ## diffMemState.lSizes[_CLIENT_BLOCK], \
					(long) a ## diffMemState.lCounts[_CLIENT_BLOCK], \
					 \
					(long) a ## diffMemState.lSizes[_MAX_BLOCKS], \
					(long) a ## diffMemState.lCounts[_MAX_BLOCKS]); \
				 LOG_DEBUG_INFO("s", a ## DumpMessage) \
			}
#	else
#		define INIT_CHECK_MEMORY(a) ((void)0)
#		define CHECK_MEMORY(a, fncname, msg) ((void)0)
#		define LOG_DEBUG_INFO(a, b) ((void)0)
#	endif
#else
#	define zbx_uint64_t uint64_t
#	if __WORDSIZE == 64
#		define ZBX_FS_UI64 "%lu"
#	else
#		define ZBX_FS_UI64 "%llu"
#	endif
#endif

#define	ZBX_FS_DBL	"%f"

#define	ZABBIX_VERSION	"1.1beta3"

#define MAX_LOG_FILE_LEN (1024*1024)

#define	SUCCEED		0
#define	FAIL		(-1)
#define	NOTSUPPORTED	(-2)
#define	NETWORK_ERROR	(-3)
#define	TIMEOUT_ERROR	(-4)
#define	AGENT_ERROR	(-5)

/*
 * Default Server ID is 1
 */
#define SERVERD_ID      1

/*
 * Default Server ID is 1
 */
#define	SERVERD_ID	1

#define	MAXFD	64

/* show debug info to stderr */
#define FDI(f, m) fprintf(stderr, "DEBUG INFO: " f "\n" , m)
#define SDI(m) FDI("%s", m)
#define IDI(i) FDI("%i", i)


/*
#define ZBX_POLLER
*/

#ifdef ZBX_POLLER
	#define MAX_STRING_LEN	800
#else
	#define MAX_STRING_LEN	2048
#endif
#define MAX_BUF_LEN	65000

/* Item types */
#define ITEM_TYPE_ZABBIX	0
#define ITEM_TYPE_SNMPv1	1
#define ITEM_TYPE_TRAPPER	2
#define ITEM_TYPE_SIMPLE	3
#define ITEM_TYPE_SNMPv2c	4
#define ITEM_TYPE_INTERNAL	5
#define ITEM_TYPE_SNMPv3	6
#define ITEM_TYPE_ZABBIX_ACTIVE	7

/* Item value types */
#define ITEM_VALUE_TYPE_FLOAT	0
#define ITEM_VALUE_TYPE_STR	1
#define ITEM_VALUE_TYPE_LOG	2

/* Item snmpv3 security levels */
#define ITEM_SNMPV3_SECURITYLEVEL_NOAUTHNOPRIV	0
#define ITEM_SNMPV3_SECURITYLEVEL_AUTHNOPRIV	1
#define ITEM_SNMPV3_SECURITYLEVEL_AUTHPRIV	2

/* Item multiplier types */
#define ITEM_MULTIPLIER_DO_NOT_USE		0
#define ITEM_MULTIPLIER_USE			1

/* Item delta types */
#define ITEM_STORE_AS_IS		0
#define ITEM_STORE_SPEED_PER_SECOND	1
#define ITEM_STORE_SIMPLE_CHANGE	2

/* Recipient types for actions */
#define RECIPIENT_TYPE_USER	0
#define RECIPIENT_TYPE_GROUP	1

/* Escalation actions */
#define ESCALATION_ACTION_NOTHING	0
#define ESCALATION_ACTION_EXEC_ACTION	1
#define ESCALATION_ACTION_INC_SEVERITY	2
#define ESCALATION_ACTION_INC_ADMIN	3

/* Special item key used for storing server status */
#define SERVER_STATUS_KEY	"status"
/* Special item key used for ICMP pings */
#define SERVER_ICMPPING_KEY	"icmpping"
/* Special item key used for ICMP ping latency */
#define SERVER_ICMPPINGSEC_KEY	"icmppingsec"
/* Special item key used for internal ZABBIX log */
#define SERVER_ZABBIXLOG_KEY	"zabbix[log]"

/* Alert types */
#define ALERT_TYPE_EMAIL	0
#define ALERT_TYPE_EXEC		1

/* Alert statuses */
#define ALERT_STATUS_NOT_SENT	0
#define ALERT_STATUS_SENT	1

/* Item statuses */
#define ITEM_STATUS_ACTIVE	0
#define ITEM_STATUS_DISABLED	1
/*#define ITEM_STATUS_TRAPPED	2*/
#define ITEM_STATUS_NOTSUPPORTED	3
#define ITEM_STATUS_DELETED	4
#define ITEM_STATUS_NOTAVAILABLE	5

/* Host statuses */
#define HOST_STATUS_MONITORED	0
#define HOST_STATUS_NOT_MONITORED	1
/*#define HOST_STATUS_UNREACHABLE	2*/
#define HOST_STATUS_TEMPLATE	3
#define HOST_STATUS_DELETED	4

/* Host availability */
#define HOST_AVAILABLE_UNKNOWN	0
#define HOST_AVAILABLE_TRUE	1
#define HOST_AVAILABLE_FALSE	2

/* Use host IP or host name */
#define HOST_USE_HOSTNAME	0
#define HOST_USE_IP		1

/* Trigger statuses */
/*#define TRIGGER_STATUS_FALSE	0
#define TRIGGER_STATUS_TRUE	1
#define TRIGGER_STATUS_DISABLED	2
#define TRIGGER_STATUS_UNKNOWN	3
#define TRIGGER_STATUS_NOTSUPPORTED	4*/

/* Trigger statuses */
#define TRIGGER_STATUS_ENABLED	0
#define TRIGGER_STATUS_DISABLED	1

/* Trigger values */
#define TRIGGER_VALUE_FALSE	0
#define TRIGGER_VALUE_TRUE	1
#define TRIGGER_VALUE_UNKNOWN	2

/* Media statuses */
#define MEDIA_STATUS_ACTIVE	0
#define MEDIA_STATUS_DISABLED	1

/* Algorithms for service status calculation */
#define SERVICE_ALGORITHM_NONE	0
#define SERVICE_ALGORITHM_MAX	1
#define SERVICE_ALGORITHM_MIN	2

/* Scope of action */
#define	ACTION_SCOPE_TRIGGER	0
#define	ACTION_SCOPE_HOST	1
#define	ACTION_SCOPE_HOSTS	2

#define	AGENTD_FORKS	5

#define	TRAPPERD_FORKS	5

#define	SUCKER_FORKS	11
#define	SUCKER_DELAY	60

#define	SUCKER_TIMEOUT	5
/* Delay on network failure*/
#define DELAY_ON_NETWORK_FAILURE 60

#define	AGENT_TIMEOUT	3

#define	SENDER_TIMEOUT		5
#define	TRAPPER_TIMEOUT		5
#define	SNMPTRAPPER_TIMEOUT	5

#ifndef MAX
#	define MAX(a, b) ((a)>(b) ? (a) : (b))
#endif

#ifndef MIN					   
#	define MIN(a, b) ((a)<(b) ? (a) : (b))
#endif
				    
/* Secure string copy */
#define strscpy(x,y) { strncpy(x,y,sizeof(x)); x[sizeof(x)-1]=0; }

/* list structure as item of agent return vaile */					 
#define LIST_ITEM struct list_item_s
LIST_ITEM {
	char name[MAX_STRING_LEN];
};	

#define LIST struct list_s
LIST {
	int 		cnt;
	LIST_ITEM 	*item;
};	
					   
/* agent return value */					 
#define AGENT_RESULT struct result_s
AGENT_RESULT {
	int	 	type;
	zbx_uint64_t	ui64;
	double		dbl;
	char		*str;
	char		*msg;
	LIST		list;
};

/* agent result types */
#define AR_UINT64	1
#define AR_DOUBLE	2
#define AR_STRING	4
#define AR_MESSAGE	8
#define AR_LIST		16

int   	copy_list(LIST *src, LIST *dist);
void 	free_list(LIST *list);
void   	clean_result(AGENT_RESULT *result);
int    	copy_result(AGENT_RESULT *src, AGENT_RESULT *dist);
void   	free_result(AGENT_RESULT *result);

char	*string_replace(char *str, const char *sub_str1, const char *sub_str2);
void	del_zeroes(char *s);
int	get_param(const char *param, int num, char *buf, int maxlen);
int	num_param(const char *param);

int	xml_get_data(char *xml,char *tag, char *data, int maxlen);
int	comms_create_request(char *host, char *key, char *data, char *lastlogsize, char *request,int maxlen);
int	comms_parse_response(char *xml,char *host,char *key, char *data, char *lastlogsize, char *timestamp,
		               char *source, char *severity, int maxlen);

int 	parse_command(const char *command, char *cmd, int cmd_max_len, char *param, int param_max_len);

/* Base64 functions */
void	str_base64_encode(char *p_str, char *p_b64str, int in_size);
void	str_base64_decode(char *p_b64str, char *p_str, int *p_out_size);

/* Regular expressions */
char    *zbx_regexp_match(const char *string, const char *pattern, int *len);

/* Misc functions */
int	cmp_double(double a,double b);

int       SYSTEM_LOCALTIME(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result);
#endif
