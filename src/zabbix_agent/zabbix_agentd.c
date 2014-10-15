/*
** Zabbix
** Copyright (C) 2001-2014 Zabbix SIA
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
#include "sysinfo.h"

#include "cfg.h"
#include "log.h"
#include "zbxconf.h"
#include "zbxgetopt.h"
#include "zbxself.h"

#ifndef _WINDOWS
#	include "../libs/zbxnix/control.h"
#	include "zbxmodules.h"
#endif

#include "comms.h"
#include "alias.h"

#include "stats.h"
#ifdef _WINDOWS
#	include "perfstat.h"
#else
#	include "sighandler.h"
#endif
#include "active.h"
#include "listener.h"

#include "symbols.h"

#if defined(ZABBIX_SERVICE)
#	include "service.h"
#elif defined(ZABBIX_DAEMON)
#	include "daemon.h"
#endif

#include "setproctitle.h"

const char	*progname = NULL;

/* default config file location */
#ifdef _WINDOWS
	static char	DEFAULT_CONFIG_FILE[]	= "C:\\zabbix_agentd.conf";
#else
	static char	DEFAULT_CONFIG_FILE[]	= SYSCONFDIR "/zabbix_agentd.conf";
#endif

/* application TITLE */
const char	title_message[] = "zabbix_agentd"
#if defined(_WIN64)
				" Win64"
#elif defined(WIN32)
				" Win32"
#endif
#if defined(ZABBIX_SERVICE)
				" (service)"
#elif defined(ZABBIX_DAEMON)
				" (daemon)"
#endif
	;
/* end of application TITLE */

const char	syslog_app_name[] = "zabbix_agentd";

/* application USAGE message */
const char	*usage_message[] = {
	"[-c config-file]",
	"[-c config-file] -p",
	"[-c config-file] -t item-key",
#ifdef _WINDOWS
	"[-c config-file] -i [-m]",
	"[-c config-file] -d [-m]",
	"[-c config-file] -s [-m]",
	"[-c config-file] -x [-m]",
#else
	"[-c config-file] -R runtime-option",
#endif
	"-h",
	"-V",
	NULL	/* end of text */
};
/* end of application USAGE message */

/* application HELP message */
const char	*help_message[] = {
	"A Zabbix daemon for monitoring of various server parameters.",
	"",
	"Options:",
	"  -c --config config-file               Absolute path to the configuration file",
	"  -p --print                            Print known items and exit",
	"  -t --test item-key                    Test specified item and exit",
#ifdef _WINDOWS
	"",
	"Functions:",
	"",
	"  -i --install                          Install Zabbix agent as service",
	"  -d --uninstall                        Uninstall Zabbix agent from service",

	"  -s --start                            Start Zabbix agent service",
	"  -x --stop                             Stop Zabbix agent service",

	"  -m --multiple-agents                  Service name will include hostname",
#else
	"  -R --runtime-control runtime-option   Perform administrative functions",
	"",
	"    Runtime control options:",
	"      " ZBX_LOG_LEVEL_INCREASE "=target         Increase log level, affects all processes if target is not specified",
	"      " ZBX_LOG_LEVEL_DECREASE "=target         Decrease log level, affects all processes if target is not specified",
	"",
	"      Log level control targets:",
	"        pid                             Process identifier",
	"        process-type                    All processes of specified type (e.g., listener)",
	"        process-type,N                  Process type and number (e.g., listener,3)",
#endif
	"",
	"  -h --help                             Display this help message",
	"  -V --version                          Display version number",
	"",
	"Example: zabbix_agentd -c /etc/zabbix/zabbix_agentd.conf",
	NULL	/* end of text */
};
/* end of application HELP message */

/* COMMAND LINE OPTIONS */
static struct zbx_option	longopts[] =
{
	{"config",		1,	NULL,	'c'},
	{"help",		0,	NULL,	'h'},
	{"version",		0,	NULL,	'V'},
	{"print",		0,	NULL,	'p'},
	{"test",		1,	NULL,	't'},
#ifndef _WINDOWS
	{"runtime-control",	1,	NULL,	'R'},
#else
	{"install",		0,	NULL,	'i'},
	{"uninstall",		0,	NULL,	'd'},

	{"start",		0,	NULL,	's'},
	{"stop",		0,	NULL,	'x'},

	{"multiple-agents",	0,	NULL,	'm'},
#endif
	{NULL}
};

static char	shortopts[] =
	"c:hVpt:"
#ifndef _WINDOWS
	"R:"
#else
	"idsxm"
#endif
	;
/* end of COMMAND LINE OPTIONS */

static char		*TEST_METRIC = NULL;
int			threads_num = 0;
ZBX_THREAD_HANDLE	*threads = NULL;

unsigned char	daemon_type = ZBX_DAEMON_TYPE_AGENT;

ZBX_THREAD_LOCAL unsigned char process_type	= 255;	/* ZBX_PROCESS_TYPE_UNKNOWN */
ZBX_THREAD_LOCAL int process_num;
ZBX_THREAD_LOCAL int server_num			= 0;

ZBX_THREAD_ACTIVECHK_ARGS	*CONFIG_ACTIVE_ARGS = NULL;

int	CONFIG_ALERTER_FORKS		= 0;
int	CONFIG_DISCOVERER_FORKS		= 0;
int	CONFIG_HOUSEKEEPER_FORKS	= 0;
int	CONFIG_PINGER_FORKS		= 0;
int	CONFIG_POLLER_FORKS		= 0;
int	CONFIG_UNREACHABLE_POLLER_FORKS	= 0;
int	CONFIG_HTTPPOLLER_FORKS		= 0;
int	CONFIG_IPMIPOLLER_FORKS		= 0;
int	CONFIG_TIMER_FORKS		= 0;
int	CONFIG_TRAPPER_FORKS		= 0;
int	CONFIG_SNMPTRAPPER_FORKS	= 0;
int	CONFIG_JAVAPOLLER_FORKS		= 0;
int	CONFIG_ESCALATOR_FORKS		= 0;
int	CONFIG_SELFMON_FORKS		= 0;
int	CONFIG_WATCHDOG_FORKS		= 0;
int	CONFIG_DATASENDER_FORKS		= 0;
int	CONFIG_HEARTBEAT_FORKS		= 0;
int	CONFIG_PROXYPOLLER_FORKS	= 0;
int	CONFIG_HISTSYNCER_FORKS		= 0;
int	CONFIG_CONFSYNCER_FORKS		= 0;
int	CONFIG_VMWARE_FORKS		= 0;
int	CONFIG_COLLECTOR_FORKS		= 1;
int	CONFIG_PASSIVE_FORKS		= 3;	/* number of listeners for processing passive checks */
int	CONFIG_ACTIVE_FORKS		= 0;

char	*opt = NULL;

#ifdef _WINDOWS
void	zbx_co_uninitialize();
#endif

int	get_process_info_by_thread(int local_server_num, unsigned char *local_process_type, int *local_process_num);

int	get_process_info_by_thread(int local_server_num, unsigned char *local_process_type, int *local_process_num)
{
	int	server_count = 0;

	if (0 == local_server_num)
	{
		/* fail if the main process is queried */
		return FAIL;
	}
	else if (local_server_num <= (server_count += CONFIG_COLLECTOR_FORKS))
	{
		*local_process_type = ZBX_PROCESS_TYPE_COLLECTOR;
		*local_process_num = local_server_num - server_count + CONFIG_COLLECTOR_FORKS;
	}
	else if (local_server_num <= (server_count += CONFIG_PASSIVE_FORKS))
	{
		*local_process_type = ZBX_PROCESS_TYPE_LISTENER;
		*local_process_num = local_server_num - server_count + CONFIG_PASSIVE_FORKS;

	}
	else if (local_server_num <= (server_count += CONFIG_ACTIVE_FORKS))
	{
		*local_process_type = ZBX_PROCESS_TYPE_ACTIVE_CHECKS;
		*local_process_num = local_server_num - server_count + CONFIG_ACTIVE_FORKS;
	}
	else
		return FAIL;

	return SUCCEED;
}

static int	parse_commandline(int argc, char **argv, ZBX_TASK_EX *t)
{
	char		ch = '\0';
	int		opt_c = 0, opt_p = 0, opt_t = 0, opt_r = 0, ret = SUCCEED;
#ifdef _WINDOWS
	int		opt_i = 0, opt_d = 0, opt_s = 0, opt_x = 0, opt_m = 0;
	unsigned int	opt_mask = 0;
#endif

	t->task = ZBX_TASK_START;

	/* parse the command-line */
	while ((char)EOF != (ch = (char)zbx_getopt_long(argc, argv, shortopts, longopts, NULL)))
	{
		switch (ch)
		{
			case 'c':
				opt_c++;
				if (NULL == CONFIG_FILE)
					CONFIG_FILE = strdup(zbx_optarg);
				break;
#ifndef _WINDOWS
			case 'R':
				opt_r++;
				if (SUCCEED != parse_rtc_options(zbx_optarg, daemon_type, &t->flags))
					exit(EXIT_FAILURE);

				t->task = ZBX_TASK_RUNTIME_CONTROL;
				break;
#endif
			case 'h':
				t->task = ZBX_TASK_SHOW_HELP;
				goto out;
			case 'V':
				t->task = ZBX_TASK_SHOW_VERSION;
				goto out;
			case 'p':
				opt_p++;
				if (ZBX_TASK_START == t->task)
					t->task = ZBX_TASK_PRINT_SUPPORTED;
				break;
			case 't':
				opt_t++;
				if (ZBX_TASK_START == t->task)
				{
					t->task = ZBX_TASK_TEST_METRIC;
					TEST_METRIC = strdup(zbx_optarg);
				}
				break;
#ifdef _WINDOWS
			case 'i':
				opt_i++;
				t->task = ZBX_TASK_INSTALL_SERVICE;
				break;
			case 'd':
				opt_d++;
				t->task = ZBX_TASK_UNINSTALL_SERVICE;
				break;
			case 's':
				opt_s++;
				t->task = ZBX_TASK_START_SERVICE;
				break;
			case 'x':
				opt_x++;
				t->task = ZBX_TASK_STOP_SERVICE;
				break;
			case 'm':
				opt_m++;
				t->flags = ZBX_TASK_FLAG_MULTIPLE_AGENTS;
				break;
#endif
			default:
				t->task = ZBX_TASK_SHOW_USAGE;
				goto out;
		}
	}

	/* every option may be specified only once */
	if (1 < opt_c || 1 < opt_p || 1 < opt_t || 1 < opt_r)
	{
		if (1 < opt_c)
			zbx_error("option \"-c\" or \"--config\" specified multiple times");
		if (1 < opt_p)
			zbx_error("option \"-p\" or \"--print\" specified multiple times");
		if (1 < opt_t)
			zbx_error("option \"-t\" or \"--test\" specified multiple times");
		if (1 < opt_r)
			zbx_error("option \"-R\" or \"--runtime-control\" specified multiple times");

		ret = FAIL;
		goto out;
	}
#ifdef _WINDOWS
	if (1 < opt_i || 1 < opt_d || 1 < opt_s || 1 < opt_x || 1 < opt_m)
	{
		if (1 < opt_i)
			zbx_error("option \"-i\" or \"--install\" specified multiple times");
		if (1 < opt_d)
			zbx_error("option \"-d\" or \"--uninstall\" specified multiple times");
		if (1 < opt_s)
			zbx_error("option \"-s\" or \"--start\" specified multiple times");
		if (1 < opt_x)
			zbx_error("option \"-x\" or \"--stop\" specified multiple times");
		if (1 < opt_m)
			zbx_error("option \"-m\" or \"--multiple-agents\" specified multiple times");

		ret = FAIL;
		goto out;
	}

	/* check for mutually exclusive options */
	/* Allowed option combinations.		*/
	/* Option 'c' is always optional.	*/
	/*   p  t  i  d  s  x  m    opt_mask	*/
	/* ---------------------    --------	*/
	/*   -  -  -  -  -  -  - 	0x00	*/
	/*   p  -  -  -  -  -  -	0x40	*/
	/*   -  t  -  -  -  -  -	0x20	*/
	/*   -  -  i  -  -  -  -	0x10	*/
	/*   -  -  -  d  -  -  -	0x08	*/
	/*   -  -  -  -  s  -  -	0x04	*/
	/*   -  -  -  -  -  x  -	0x02	*/
	/*   -  -  i  -  -  -  m	0x11	*/
	/*   -  -  -  d  -  -  m	0x09	*/
	/*   -  -  -  -  s  -  m	0x05	*/
	/*   -  -  -  -  -  x  m	0x03	*/
	/*   -  -  -  -  -  -  m	0x01 special case required for starting as a service with '-m' option */

	if (0 < opt_p)
		opt_mask |= 0x40;
	if (0 < opt_t)
		opt_mask |= 0x20;
	if (0 < opt_i)
		opt_mask |= 0x10;
	if (0 < opt_d)
		opt_mask |= 0x08;
	if (0 < opt_s)
		opt_mask |= 0x04;
	if (0 < opt_x)
		opt_mask |= 0x02;
	if (0 < opt_m)
		opt_mask |= 0x01;

	switch (opt_mask)
	{
		case 0x00:
		case 0x01:
		case 0x02:
		case 0x03:
		case 0x04:
		case 0x05:
		case 0x08:
		case 0x09:
		case 0x10:
		case 0x11:
		case 0x20:
		case 0x40:
			break;
		default:
			zbx_error("mutually exclusive options used");
			usage();
			ret = FAIL;
			goto out;
	}
#else
	/* check for mutually exclusive options */
	if (1 < opt_p + opt_t + opt_r)
	{
		zbx_error("only one of options \"-p\", \"--print\", \"-t\", \"--test\", \"-R\" or \"--runtime-control\""
				" can be used");
		ret = FAIL;
		goto out;
	}
#endif
	/* Parameters which are not option values are invalid. The check relies on zbx_getopt_internal() which */
	/* always permutes command line arguments regardless of POSIXLY_CORRECT environment variable. */
	if (argc > zbx_optind)
	{
		int	i;

		for (i = zbx_optind; i < argc; i++)
			zbx_error("invalid parameter \"%s\"", argv[i]);

		ret = FAIL;
		goto out;
	}

	if (NULL == CONFIG_FILE)
		CONFIG_FILE = DEFAULT_CONFIG_FILE;
out:
	if (FAIL == ret)
	{
		zbx_free(TEST_METRIC);

		if (DEFAULT_CONFIG_FILE != CONFIG_FILE)
			zbx_free(CONFIG_FILE);
	}

	return ret;
}

/******************************************************************************
 *                                                                            *
 * Function: set_defaults                                                     *
 *                                                                            *
 * Purpose: set configuration defaults                                        *
 *                                                                            *
 * Author: Vladimir Levijev, Rudolfs Kreicbergs                               *
 *                                                                            *
 ******************************************************************************/
static void	set_defaults(void)
{
	AGENT_RESULT	result;
	char		**value = NULL;

	if (NULL == CONFIG_HOSTNAME)
	{
		if (NULL == CONFIG_HOSTNAME_ITEM)
			CONFIG_HOSTNAME_ITEM = zbx_strdup(CONFIG_HOSTNAME_ITEM, "system.hostname");

		init_result(&result);

		if (SUCCEED == process(CONFIG_HOSTNAME_ITEM, PROCESS_LOCAL_COMMAND, &result) &&
				NULL != (value = GET_STR_RESULT(&result)))
		{
			assert(*value);

			if (MAX_ZBX_HOSTNAME_LEN < strlen(*value))
			{
				(*value)[MAX_ZBX_HOSTNAME_LEN] = '\0';
				zabbix_log(LOG_LEVEL_WARNING, "hostname truncated to [%s])", *value);
			}

			CONFIG_HOSTNAME = zbx_strdup(CONFIG_HOSTNAME, *value);
		}
		else
			zabbix_log(LOG_LEVEL_WARNING, "failed to get system hostname from [%s])", CONFIG_HOSTNAME_ITEM);

		free_result(&result);
	}
	else if (NULL != CONFIG_HOSTNAME_ITEM)
		zabbix_log(LOG_LEVEL_WARNING, "both Hostname and HostnameItem defined, using [%s]", CONFIG_HOSTNAME);

	if (NULL != CONFIG_HOST_METADATA && NULL != CONFIG_HOST_METADATA_ITEM)
	{
		zabbix_log(LOG_LEVEL_WARNING, "both HostMetadata and HostMetadataItem defined, using [%s]",
				CONFIG_HOST_METADATA);
	}

#ifndef _WINDOWS
	if (NULL == CONFIG_LOAD_MODULE_PATH)
		CONFIG_LOAD_MODULE_PATH = zbx_strdup(CONFIG_LOAD_MODULE_PATH, LIBDIR "/modules");

	if (NULL == CONFIG_PID_FILE)
		CONFIG_PID_FILE = "/tmp/zabbix_agentd.pid";
#endif
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_validate_config                                              *
 *                                                                            *
 * Purpose: validate configuration parameters                                 *
 *                                                                            *
 * Author: Vladimir Levijev                                                   *
 *                                                                            *
 ******************************************************************************/
static void	zbx_validate_config(void)
{
	char	*ch_error;

	if (NULL == CONFIG_HOSTNAME)
	{
		zabbix_log(LOG_LEVEL_CRIT, "\"Hostname\" configuration parameter is not defined");
		exit(EXIT_FAILURE);
	}

	if (FAIL == zbx_check_hostname(CONFIG_HOSTNAME, &ch_error))
	{
		zabbix_log(LOG_LEVEL_CRIT, "invalid \"Hostname\" configuration parameter: '%s': %s", CONFIG_HOSTNAME,
				ch_error);
		zbx_free(ch_error);
		exit(EXIT_FAILURE);
	}

	if (NULL != CONFIG_HOST_METADATA && HOST_METADATA_LEN < zbx_strlen_utf8(CONFIG_HOST_METADATA))
	{
		zabbix_log(LOG_LEVEL_CRIT, "the value of \"HostMetadata\" configuration parameter cannot be longer than"
				" %d characters", HOST_METADATA_LEN);
		exit(EXIT_FAILURE);
	}

	/* make sure active or passive check is enabled */
	if (0 == CONFIG_ACTIVE_FORKS && 0 == CONFIG_PASSIVE_FORKS)
	{
		zabbix_log(LOG_LEVEL_CRIT, "either active or passive checks must be enabled");
		exit(EXIT_FAILURE);
	}

	if (NULL != CONFIG_SOURCE_IP && ('\0' == *CONFIG_SOURCE_IP || SUCCEED != is_ip(CONFIG_SOURCE_IP)))
	{
		zabbix_log(LOG_LEVEL_CRIT, "invalid \"SourceIP\" configuration parameter: '%s'", CONFIG_SOURCE_IP);
		exit(EXIT_FAILURE);
	}
}

static int	add_activechk_host(const char *host, unsigned short port)
{
	int	i;

	for (i = 0; i < CONFIG_ACTIVE_FORKS; i++)
	{
		if (0 == strcmp(CONFIG_ACTIVE_ARGS[i].host, host) && CONFIG_ACTIVE_ARGS[i].port == port)
			return FAIL;
	}

	CONFIG_ACTIVE_FORKS++;
	CONFIG_ACTIVE_ARGS = zbx_realloc(CONFIG_ACTIVE_ARGS, sizeof(ZBX_THREAD_ACTIVECHK_ARGS) * CONFIG_ACTIVE_FORKS);
	CONFIG_ACTIVE_ARGS[CONFIG_ACTIVE_FORKS - 1].host = zbx_strdup(NULL, host);
	CONFIG_ACTIVE_ARGS[CONFIG_ACTIVE_FORKS - 1].port = port;

	return SUCCEED;
}

/******************************************************************************
 *                                                                            *
 * Function: get_serveractive_hosts                                           *
 *                                                                            *
 * Purpose: parse string like IP<:port>,[IPv6]<:port>                         *
 *                                                                            *
 ******************************************************************************/
static void	get_serveractive_hosts(char *active_hosts)
{
	char	*l = active_hosts, *r;
	int	rc = SUCCEED;

	do
	{
		char		*host = NULL;
		unsigned short	port;

		if (NULL != (r = strchr(l, ',')))
			*r = '\0';

		if (SUCCEED != parse_serveractive_element(l, &host, &port, (unsigned short)ZBX_DEFAULT_SERVER_PORT))
			goto fail;

		rc = add_activechk_host(host, port);

		zbx_free(host);

		if (SUCCEED != rc)
			goto fail;

		if (NULL != r)
		{
			*r = ',';
			l = r + 1;
		}
	}
	while (NULL != r);

	return;
fail:
	if (SUCCEED != rc)
		zbx_error("error parsing a \"ServerActive\" option: address \"%s\" specified more than once", l);
	else
		zbx_error("error parsing a \"ServerActive\" option: address \"%s\" is invalid", l);

	if (NULL != r)
		*r = ',';

	exit(EXIT_FAILURE);
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_load_config                                                  *
 *                                                                            *
 * Purpose: load configuration from config file                               *
 *                                                                            *
 * Parameters: requirement - produce error if config file missing or not      *
 *                                                                            *
 ******************************************************************************/
static void	zbx_load_config(int requirement)
{
	char	*active_hosts = NULL;

	struct cfg_line	cfg[] =
	{
		/* PARAMETER,			VAR,					TYPE,
			MANDATORY,	MIN,			MAX */
		{"Server",			&CONFIG_HOSTS_ALLOWED,			TYPE_STRING_LIST,
			PARM_OPT,	0,			0},
		{"ServerActive",		&active_hosts,				TYPE_STRING_LIST,
			PARM_OPT,	0,			0},
		{"Hostname",			&CONFIG_HOSTNAME,			TYPE_STRING,
			PARM_OPT,	0,			0},
		{"HostnameItem",		&CONFIG_HOSTNAME_ITEM,			TYPE_STRING,
			PARM_OPT,	0,			0},
		{"HostMetadata",		&CONFIG_HOST_METADATA,			TYPE_STRING,
			PARM_OPT,	0,			0},
		{"HostMetadataItem",		&CONFIG_HOST_METADATA_ITEM,		TYPE_STRING,
			PARM_OPT,	0,			0},
		{"BufferSize",			&CONFIG_BUFFER_SIZE,			TYPE_INT,
			PARM_OPT,	2,			65535},
		{"BufferSend",			&CONFIG_BUFFER_SEND,			TYPE_INT,
			PARM_OPT,	1,			SEC_PER_HOUR},
#ifndef _WINDOWS
		{"PidFile",			&CONFIG_PID_FILE,			TYPE_STRING,
			PARM_OPT,	0,			0},
#endif
		{"LogFile",			&CONFIG_LOG_FILE,			TYPE_STRING,
			PARM_OPT,	0,			0},
		{"LogFileSize",			&CONFIG_LOG_FILE_SIZE,			TYPE_INT,
			PARM_OPT,	0,			1024},
		{"Timeout",			&CONFIG_TIMEOUT,			TYPE_INT,
			PARM_OPT,	1,			30},
		{"ListenPort",			&CONFIG_LISTEN_PORT,			TYPE_INT,
			PARM_OPT,	1024,			32767},
		{"ListenIP",			&CONFIG_LISTEN_IP,			TYPE_STRING_LIST,
			PARM_OPT,	0,			0},
		{"SourceIP",			&CONFIG_SOURCE_IP,			TYPE_STRING,
			PARM_OPT,	0,			0},
		{"DebugLevel",			&CONFIG_LOG_LEVEL,			TYPE_INT,
			PARM_OPT,	0,			4},
		{"StartAgents",			&CONFIG_PASSIVE_FORKS,			TYPE_INT,
			PARM_OPT,	0,			100},
		{"RefreshActiveChecks",		&CONFIG_REFRESH_ACTIVE_CHECKS,		TYPE_INT,
			PARM_OPT,	SEC_PER_MIN,		SEC_PER_HOUR},
		{"MaxLinesPerSecond",		&CONFIG_MAX_LINES_PER_SECOND,		TYPE_INT,
			PARM_OPT,	1,			1000},
		{"EnableRemoteCommands",	&CONFIG_ENABLE_REMOTE_COMMANDS,		TYPE_INT,
			PARM_OPT,	0,			1},
		{"LogRemoteCommands",		&CONFIG_LOG_REMOTE_COMMANDS,		TYPE_INT,
			PARM_OPT,	0,			1},
		{"UnsafeUserParameters",	&CONFIG_UNSAFE_USER_PARAMETERS,		TYPE_INT,
			PARM_OPT,	0,			1},
		{"Alias",			&CONFIG_ALIASES,			TYPE_MULTISTRING,
			PARM_OPT,	0,			0},
		{"UserParameter",		&CONFIG_USER_PARAMETERS,		TYPE_MULTISTRING,
			PARM_OPT,	0,			0},
#ifndef _WINDOWS
		{"LoadModulePath",		&CONFIG_LOAD_MODULE_PATH,		TYPE_STRING,
			PARM_OPT,	0,			0},
		{"LoadModule",			&CONFIG_LOAD_MODULE,			TYPE_MULTISTRING,
			PARM_OPT,	0,			0},
		{"AllowRoot",			&CONFIG_ALLOW_ROOT,			TYPE_INT,
			PARM_OPT,	0,			1},
		{"User",			&CONFIG_USER,				TYPE_STRING,
			PARM_OPT,	0,			0},
#endif
#ifdef _WINDOWS
		{"PerfCounter",			&CONFIG_PERF_COUNTERS,			TYPE_MULTISTRING,
			PARM_OPT,	0,			0},
#endif
		{NULL}
	};

	/* initialize multistrings */
	zbx_strarr_init(&CONFIG_ALIASES);
	zbx_strarr_init(&CONFIG_USER_PARAMETERS);
#ifndef _WINDOWS
	zbx_strarr_init(&CONFIG_LOAD_MODULE);
#endif
#ifdef _WINDOWS
	zbx_strarr_init(&CONFIG_PERF_COUNTERS);
#endif

	parse_cfg_file(CONFIG_FILE, cfg, requirement, ZBX_CFG_STRICT);

	set_defaults();

	if (ZBX_CFG_FILE_REQUIRED == requirement && NULL == CONFIG_HOSTS_ALLOWED && 0 != CONFIG_PASSIVE_FORKS)
	{
		zbx_error("StartAgents is not 0, parameter Server must be defined");
		exit(EXIT_FAILURE);
	}

	if (NULL != active_hosts && '\0' != *active_hosts)
		get_serveractive_hosts(active_hosts);

	zbx_free(active_hosts);

	if (ZBX_CFG_FILE_REQUIRED == requirement)
		zbx_validate_config();
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_free_config                                                  *
 *                                                                            *
 * Purpose: free configuration memory                                         *
 *                                                                            *
 * Author: Vladimir Levijev                                                   *
 *                                                                            *
 ******************************************************************************/
static void	zbx_free_config(void)
{
	zbx_strarr_free(CONFIG_ALIASES);
	zbx_strarr_free(CONFIG_USER_PARAMETERS);
#ifndef _WINDOWS
	zbx_strarr_free(CONFIG_LOAD_MODULE);
#endif
#ifdef _WINDOWS
	zbx_strarr_free(CONFIG_PERF_COUNTERS);
#endif
}

#ifdef _WINDOWS
static int	zbx_exec_service_task(const char *name, const ZBX_TASK_EX *t)
{
	int	ret;

	switch (t->task)
	{
		case ZBX_TASK_INSTALL_SERVICE:
			ret = ZabbixCreateService(name, t->flags & ZBX_TASK_FLAG_MULTIPLE_AGENTS);
			break;
		case ZBX_TASK_UNINSTALL_SERVICE:
			ret = ZabbixRemoveService();
			break;
		case ZBX_TASK_START_SERVICE:
			ret = ZabbixStartService();
			break;
		case ZBX_TASK_STOP_SERVICE:
			ret = ZabbixStopService();
			break;
		default:
			/* there can not be other choice */
			assert(0);
	}

	return ret;
}
#endif	/* _WINDOWS */

int	MAIN_ZABBIX_ENTRY()
{
	zbx_sock_t	listen_sock;
	int		i, j = 0;
#ifdef _WINDOWS
	DWORD		res;
#endif
	if (NULL == CONFIG_LOG_FILE || '\0' == *CONFIG_LOG_FILE)
		zabbix_open_log(LOG_TYPE_SYSLOG, CONFIG_LOG_LEVEL, NULL);
	else
		zabbix_open_log(LOG_TYPE_FILE, CONFIG_LOG_LEVEL, CONFIG_LOG_FILE);

	zabbix_log(LOG_LEVEL_INFORMATION, "Starting Zabbix Agent [%s]. Zabbix %s (revision %s).",
			CONFIG_HOSTNAME, ZABBIX_VERSION, ZABBIX_REVISION);

	zabbix_log(LOG_LEVEL_INFORMATION, "using configuration file: %s", CONFIG_FILE);

#ifndef _WINDOWS
	if (FAIL == load_modules(CONFIG_LOAD_MODULE_PATH, CONFIG_LOAD_MODULE, CONFIG_TIMEOUT, 1))
	{
		zabbix_log(LOG_LEVEL_CRIT, "loading modules failed, exiting...");
		exit(EXIT_FAILURE);
	}
#endif
	if (0 != CONFIG_PASSIVE_FORKS)
	{
		if (FAIL == zbx_tcp_listen(&listen_sock, CONFIG_LISTEN_IP, (unsigned short)CONFIG_LISTEN_PORT))
		{
			zabbix_log(LOG_LEVEL_CRIT, "listener failed: %s", zbx_tcp_strerror());
			exit(EXIT_FAILURE);
		}
	}

	init_collector_data();

#ifdef _WINDOWS
	init_perf_collector(1);
	load_perf_counters(CONFIG_PERF_COUNTERS);
#endif
	zbx_free_config();

	/* --- START THREADS ---*/

	/* allocate memory for a collector, all listeners and active checks */
	threads_num = CONFIG_COLLECTOR_FORKS + CONFIG_PASSIVE_FORKS + CONFIG_ACTIVE_FORKS;

#ifdef _WINDOWS
	if (MAXIMUM_WAIT_OBJECTS < threads_num)
	{
		zabbix_log(LOG_LEVEL_CRIT, "Too many agent threads. Please reduce the StartAgents configuration"
				" parameter or the number of active servers in ServerActive configuration parameter.");
		exit(EXIT_FAILURE);
	}
#endif
	threads = zbx_calloc(threads, threads_num, sizeof(ZBX_THREAD_HANDLE));

	zabbix_log(LOG_LEVEL_INFORMATION, "agent #0 started [main process]");

	for (i = 0; i < threads_num; i++)
	{
		zbx_thread_args_t	*thread_args;

		thread_args = (zbx_thread_args_t *)zbx_malloc(NULL, sizeof(zbx_thread_args_t));

		if (FAIL == get_process_info_by_thread(i + 1, &thread_args->process_type, &thread_args->process_num))
		{
			THIS_SHOULD_NEVER_HAPPEN;
			exit(EXIT_FAILURE);
		}

		thread_args->server_num = i + 1;
		thread_args->args = NULL;

		switch (thread_args->process_type)
		{
			case ZBX_PROCESS_TYPE_COLLECTOR:
				threads[i] = zbx_thread_start(collector_thread, thread_args);
				break;
			case ZBX_PROCESS_TYPE_LISTENER:
				thread_args->args = &listen_sock;
				threads[i] = zbx_thread_start(listener_thread, thread_args);
				break;
			case ZBX_PROCESS_TYPE_ACTIVE_CHECKS:
				thread_args->args = &CONFIG_ACTIVE_ARGS[j++];
				threads[i] = zbx_thread_start(active_checks_thread, thread_args);
				break;
		}
	}

#ifdef _WINDOWS
	set_parent_signal_handler();	/* must be called after all threads are created */

	/* wait for an exiting thread */
	res = WaitForMultipleObjectsEx(threads_num, threads, FALSE, INFINITE, FALSE);

	if (ZBX_IS_RUNNING())
	{
		/* Zabbix agent service should either be stopped by the user in ServiceCtrlHandler() or */
		/* crash. If some thread has terminated normally, it means something is terribly wrong. */

		zabbix_log(LOG_LEVEL_CRIT, "One thread has terminated unexpectedly (code:%lu). Exiting ...", res);
		THIS_SHOULD_NEVER_HAPPEN;

		/* notify other threads and allow them to terminate */
		ZBX_DO_EXIT();
		zbx_sleep(1);
	}
	else
	{
		/* wait for the service worker thread to terminate us */
		zbx_sleep(3);

		THIS_SHOULD_NEVER_HAPPEN;
	}
#else
	while (-1 == wait(&i))	/* wait for any child to exit */
	{
		if (EINTR != errno)
		{
			zabbix_log(LOG_LEVEL_ERR, "failed to wait on child processes: %s", zbx_strerror(errno));
			break;
		}
	}

	/* all exiting child processes should be caught by signal handlers */
	THIS_SHOULD_NEVER_HAPPEN;
#endif
	zbx_on_exit();

	return SUCCEED;
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_free_service_resources                                       *
 *                                                                            *
 * Purpose: free service resources allocated by main thread                   *
 *                                                                            *
 ******************************************************************************/
void	zbx_free_service_resources(void)
{
	if (NULL != threads)
	{
		int		i;
#if !defined(_WINDOWS)
		sigset_t	set;

		/* ignore SIGCHLD signals in order for zbx_sleep() to work */
		sigemptyset(&set);
		sigaddset(&set, SIGCHLD);
		sigprocmask(SIG_BLOCK, &set, NULL);
#else
		/* wait for threads to finish first. although listener threads will never end */
		WaitForMultipleObjectsEx(threads_num, threads, TRUE, 1000, FALSE);
#endif
		for (i = 0; i < threads_num; i++)
		{
			if (threads[i])
				zbx_thread_kill(threads[i]);
		}

		for (i = 0; i < threads_num; i++)
		{
			if (threads[i])
				zbx_thread_wait(threads[i]);

			threads[i] = ZBX_THREAD_HANDLE_NULL;
		}

		zbx_free(threads);
	}

	free_metrics();
	alias_list_free();
	free_collector_data();
#ifdef _WINDOWS
	free_perf_collector();
	zbx_co_uninitialize();
#else
	unload_modules();
#endif
	zabbix_log(LOG_LEVEL_INFORMATION, "Zabbix Agent stopped. Zabbix %s (revision %s).",
			ZABBIX_VERSION, ZABBIX_REVISION);

	zabbix_close_log();
}

void	zbx_on_exit(void)
{
	zabbix_log(LOG_LEVEL_DEBUG, "zbx_on_exit() called");

	zbx_free_service_resources();

#if defined(PS_OVERWRITE_ARGV)
	setproctitle_free_env();
#endif

	exit(EXIT_SUCCESS);
}

#if defined(HAVE_SIGQUEUE)
void	zbx_sigusr_handler(int flags)
{
}
#endif

int	main(int argc, char **argv)
{
	ZBX_TASK_EX	t = {ZBX_TASK_START};
#ifdef _WINDOWS
	int		ret;

	/* Provide, so our process handles errors instead of the system itself. */
	/* Attention!!! */
	/* The system does not display the critical-error-handler message box. */
	/* Instead, the system sends the error to the calling process.*/
	SetErrorMode(SEM_FAILCRITICALERRORS);
#endif
#if defined(PS_OVERWRITE_ARGV) || defined(PS_PSTAT_ARGV)
	argv = setproctitle_save_env(argc, argv);
#endif
	progname = get_program_name(argv[0]);

	if (SUCCEED != parse_commandline(argc, argv, &t))
		exit(EXIT_FAILURE);

	import_symbols();

	/* this is needed to set default hostname in zbx_load_config() */
	init_metrics();

	switch (t.task)
	{
		case ZBX_TASK_SHOW_USAGE:
			usage();
			exit(EXIT_FAILURE);
			break;
#ifndef _WINDOWS
		case ZBX_TASK_RUNTIME_CONTROL:
			zbx_load_config(ZBX_CFG_FILE_REQUIRED);
			exit(SUCCEED == zbx_sigusr_send(t.flags) ? EXIT_SUCCESS : EXIT_FAILURE);
			break;
#else
		case ZBX_TASK_INSTALL_SERVICE:
		case ZBX_TASK_UNINSTALL_SERVICE:
		case ZBX_TASK_START_SERVICE:
		case ZBX_TASK_STOP_SERVICE:
			if (t.flags & ZBX_TASK_FLAG_MULTIPLE_AGENTS)
			{
				zbx_load_config(ZBX_CFG_FILE_REQUIRED);

				zbx_snprintf(ZABBIX_SERVICE_NAME, sizeof(ZABBIX_SERVICE_NAME), "%s [%s]",
						APPLICATION_NAME, CONFIG_HOSTNAME);
				zbx_snprintf(ZABBIX_EVENT_SOURCE, sizeof(ZABBIX_EVENT_SOURCE), "%s [%s]",
						APPLICATION_NAME, CONFIG_HOSTNAME);
			}
			else
				zbx_load_config(ZBX_CFG_FILE_OPTIONAL);

			zbx_free_config();

			ret = zbx_exec_service_task(argv[0], &t);
			free_metrics();
			exit(SUCCEED == ret ? EXIT_SUCCESS : EXIT_FAILURE);
			break;
#endif
		case ZBX_TASK_TEST_METRIC:
		case ZBX_TASK_PRINT_SUPPORTED:
			zbx_load_config(ZBX_CFG_FILE_OPTIONAL);
#ifdef _WINDOWS
			init_perf_collector(0);
			load_perf_counters(CONFIG_PERF_COUNTERS);
#else
			zbx_set_common_signal_handlers();
#endif
#ifndef _WINDOWS
			if (FAIL == load_modules(CONFIG_LOAD_MODULE_PATH, CONFIG_LOAD_MODULE, CONFIG_TIMEOUT, 0))
			{
				zabbix_log(LOG_LEVEL_CRIT, "loading modules failed, exiting...");
				exit(EXIT_FAILURE);
			}
#endif
			load_user_parameters(CONFIG_USER_PARAMETERS);
			load_aliases(CONFIG_ALIASES);
			zbx_free_config();
			if (ZBX_TASK_TEST_METRIC == t.task)
				test_parameter(TEST_METRIC);
			else
				test_parameters();
#ifdef _WINDOWS
			free_perf_collector();	/* cpu_collector must be freed before perf_collector is freed */
#endif
#ifndef _WINDOWS
			unload_modules();
#endif
			free_metrics();
			alias_list_free();
			exit(EXIT_SUCCESS);
			break;
		case ZBX_TASK_SHOW_VERSION:
			version();
#ifdef _AIX
			printf("\n");
			tl_version();
#endif
			exit(EXIT_SUCCESS);
			break;
		case ZBX_TASK_SHOW_HELP:
			help();
			exit(EXIT_SUCCESS);
			break;
		default:
			zbx_load_config(ZBX_CFG_FILE_REQUIRED);
			load_user_parameters(CONFIG_USER_PARAMETERS);
			load_aliases(CONFIG_ALIASES);
			break;
	}

	START_MAIN_ZABBIX_ENTRY(CONFIG_ALLOW_ROOT, CONFIG_USER);

	exit(EXIT_SUCCESS);
}
