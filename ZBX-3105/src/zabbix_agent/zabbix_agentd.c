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

#include "cfg.h"
#include "log.h"
#include "zbxconf.h"
#include "zbxgetopt.h"
#include "comms.h"
#include "alias.h"

#include "stats.h"
#include "perfstat.h"
#include "active.h"
#include "listener.h"

#include "symbols.h"

#if defined(ZABBIX_SERVICE)
#	include "service.h"
#elif defined(ZABBIX_DAEMON) /* ZABBIX_SERVICE */
#	include "daemon.h"
#endif /* ZABBIX_DAEMON */

const char	*progname = NULL;

/* Default config file location */
#ifdef _WINDOWS
	static char	DEFAULT_CONFIG_FILE[]	= "C:\\zabbix_agentd.conf";
#else
	static char	DEFAULT_CONFIG_FILE[]	= "/etc/zabbix/zabbix_agentd.conf";
#endif

/* application TITLE */

const char	title_message[] = APPLICATION_NAME
#if defined(_WIN64)
				" Win64"
#elif defined(WIN32)
				" Win32"
#endif /* WIN32 */
#if defined(ZABBIX_SERVICE)
				" (service)"
#elif defined(ZABBIX_DAEMON)
				" (daemon)"
#endif /* ZABBIX_SERVICE */
	;
/* end of application TITLE */


/* application USAGE message */

const char	usage_message[] =
	"[-Vhp]"
#ifdef _WINDOWS
	" [-idsx] [-m]"
#endif
	" [-c <file>] [-t <item>]";

/*end of application USAGE message */



/* application HELP message */

const char	*help_message[] = {
	"Options:",
	"",
	"  -c --config <file>    absolute path to the configuration file",
	"  -h --help             give this help",
	"  -V --version          display version number",
	"  -p --print            print supported items and exit",
	"  -t --test <item>      test specified item and exit",
/*	"  -u --usage <item>     test specified item and exit",	*/ /* !!! TODO - print item usage !!! */

#ifdef _WINDOWS

	"",
	"Functions:",
	"",
	"  -i --install          install Zabbix agent as service",
	"  -d --uninstall        uninstall Zabbix agent from service",

	"  -s --start            start Zabbix agent service",
	"  -x --stop             stop Zabbix agent service",

	"  -m --multiple-agents  service name will include hostname",

#endif

	0 /* end of text */
};

/* end of application HELP message */



/* COMMAND LINE OPTIONS */

/* long options */

static struct zbx_option longopts[] =
{
	{"config",		1,	0,	'c'},
	{"help",		0,	0,	'h'},
	{"version",		0,	0,	'V'},
	{"print",		0,	0,	'p'},
	{"test",		1,	0,	't'},

#ifdef _WINDOWS

	{"install",		0,	0,	'i'},
	{"uninstall",		0,	0,	'd'},

	{"start",		0,	0,	's'},
	{"stop",		0,	0,	'x'},

	{"multiple-agents",	0,	0,	'm'},

#endif

	{0,0,0,0}
};

/* short options */

static char	shortopts[] =
	"c:hVpt:"
#ifdef _WINDOWS
	"idsxm"
#endif
	;

/* end of COMMAND LINE OPTIONS*/

static char	*TEST_METRIC = NULL;

int			threads_num = 0;
ZBX_THREAD_HANDLE	*threads = NULL;

static void	parse_commandline(int argc, char **argv, ZBX_TASK_EX *t)
{
	char	ch = '\0';

	t->task = ZBX_TASK_START;

	/* Parse the command-line. */
	while ((ch = (char)zbx_getopt_long(argc, argv, shortopts, longopts, NULL)) != (char)EOF)
	{
		switch (ch) {
		case 'c':
			CONFIG_FILE = strdup(zbx_optarg);
			break;
		case 'h':
			help();
			exit(FAIL);
			break;
		case 'V':
			version();
#ifdef _AIX
			tl_version();
#endif
			exit(FAIL);
			break;
		case 'p':
			if(t->task == ZBX_TASK_START)
				t->task = ZBX_TASK_PRINT_SUPPORTED;
			break;
		case 't':
			if(t->task == ZBX_TASK_START)
			{
				t->task = ZBX_TASK_TEST_METRIC;
				TEST_METRIC = strdup(zbx_optarg);
			}
			break;
#ifdef _WINDOWS
		case 'i':
			t->task = ZBX_TASK_INSTALL_SERVICE;
			break;
		case 'd':
			t->task = ZBX_TASK_UNINSTALL_SERVICE;
			break;
		case 's':
			t->task = ZBX_TASK_START_SERVICE;
			break;
		case 'x':
			t->task = ZBX_TASK_STOP_SERVICE;
			break;
		case 'm':
			t->flags = ZBX_TASK_FLAG_MULTIPLE_AGENTS;
			break;
#endif
		default:
			t->task = ZBX_TASK_SHOW_USAGE;
			break;
		}
	}

	if(CONFIG_FILE == NULL)
	{
		CONFIG_FILE = DEFAULT_CONFIG_FILE;
	}
}

/******************************************************************************
 *                                                                            *
 * Function: set_defaults                                                     *
 *                                                                            *
 * Purpose: set non-static configuration defaults                             *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Vladimir Levijev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static void	set_defaults()
{
	AGENT_RESULT	result;
	char		**value = NULL;

	memset(&result, 0, sizeof(AGENT_RESULT));

	/* hostname */
	if (SUCCEED == process("system.hostname", 0, &result))
	{
		if (NULL == (value = GET_STR_RESULT(&result)))
		{
			zabbix_log(LOG_LEVEL_WARNING, "failed to get system hostname (system.hostname)");
		}
		else
		{
			assert(*value);

			CONFIG_HOSTNAME = zbx_strdup(CONFIG_HOSTNAME, *value);

			/* If auto registration is used, our CONFIG_HOSTNAME will make it into the  */
			/* server's database, where it is limited by HOST_HOST_LEN (currently, 64), */
			/* so to make it work properly we need to truncate our hostname.            */
			if (strlen(CONFIG_HOSTNAME) > 64)
				CONFIG_HOSTNAME[64] = '\0';
		}
	}
	free_result(&result);
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_load_config                                                  *
 *                                                                            *
 * Purpose: load configuration from config file                               *
 *                                                                            *
 * Parameters: optional - do not produce error if config file missing         *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static void	zbx_load_config(int optional)
{
	struct cfg_line	cfg[] =
	{
		/* PARAMETER,			VAR,					TYPE,
			MANDATORY,	MIN,			MAX */
		{"Server",			&CONFIG_HOSTS_ALLOWED,			TYPE_STRING,
			PARM_MAND,	0,			0},
		{"Hostname",			&CONFIG_HOSTNAME,			TYPE_STRING,
			PARM_OPT,	0,			0},
		{"BufferSize",			&CONFIG_BUFFER_SIZE,			TYPE_INT,
			PARM_OPT,	2,			65535},
		{"BufferSend",			&CONFIG_BUFFER_SEND,			TYPE_INT,
			PARM_OPT,	1,			SEC_PER_HOUR},
#ifdef USE_PID_FILE
		{"PidFile",			&CONFIG_PID_FILE,			TYPE_STRING,
			PARM_OPT,	0,			0},
#endif
		{"LogFile",			&CONFIG_LOG_FILE,			TYPE_STRING,
			PARM_OPT,	0,			0},
		{"LogFileSize",			&CONFIG_LOG_FILE_SIZE,			TYPE_INT,
			PARM_OPT,	0,			1024},
		{"DisableActive",		&CONFIG_DISABLE_ACTIVE,			TYPE_INT,
			PARM_OPT,	0,			1},
		{"DisablePassive",		&CONFIG_DISABLE_PASSIVE,		TYPE_INT,
			PARM_OPT,	0,			1},
		{"Timeout",			&CONFIG_TIMEOUT,			TYPE_INT,
			PARM_OPT,	1,			30},
		{"ListenPort",			&CONFIG_LISTEN_PORT,			TYPE_INT,
			PARM_OPT,	1024,			32767},
		{"ServerPort",			&CONFIG_SERVER_PORT,			TYPE_INT,
			PARM_OPT,	1024,			32767},
		{"ListenIP",			&CONFIG_LISTEN_IP,			TYPE_STRING,
			PARM_OPT,	0,			0},
		{"SourceIP",			&CONFIG_SOURCE_IP,			TYPE_STRING,
			PARM_OPT,	0,			0},
		{"DebugLevel",			&CONFIG_LOG_LEVEL,			TYPE_INT,
			PARM_OPT,	0,			4},
		{"StartAgents",			&CONFIG_ZABBIX_FORKS,			TYPE_INT,
			PARM_OPT,	1,			100},
		{"RefreshActiveChecks",		&CONFIG_REFRESH_ACTIVE_CHECKS,		TYPE_INT,
			PARM_OPT,	SEC_PER_MIN,		SEC_PER_HOUR},
		{"MaxLinesPerSecond",		&CONFIG_MAX_LINES_PER_SECOND,		TYPE_INT,
			PARM_OPT,	1,			1000},
		{"AllowRoot",			&CONFIG_ALLOW_ROOT,			TYPE_INT,
			PARM_OPT,	0,			1},
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
#ifdef _WINDOWS
		{"PerfCounter",			&CONFIG_PERF_COUNTERS,			TYPE_MULTISTRING,
			PARM_OPT,	0,			0},
#endif
		{NULL}
	};

	/* initialize multistrings */
	zbx_strarr_init(&CONFIG_ALIASES);
	zbx_strarr_init(&CONFIG_USER_PARAMETERS);
#ifdef _WINDOWS
	zbx_strarr_init(&CONFIG_PERF_COUNTERS);
#endif

	set_defaults();

	parse_cfg_file(CONFIG_FILE, cfg, optional, ZBX_CFG_STRICT);

#ifdef USE_PID_FILE
	if (NULL == CONFIG_PID_FILE)
		CONFIG_PID_FILE = "/tmp/zabbix_agentd.pid";
#endif
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_free_config                                                  *
 *                                                                            *
 * Purpose: free configuration memory                                         *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Vladimir Levijev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static void	zbx_free_config()
{
	zbx_strarr_free(CONFIG_ALIASES);
	zbx_strarr_free(CONFIG_USER_PARAMETERS);
#ifdef _WINDOWS
	zbx_strarr_free(CONFIG_PERF_COUNTERS);
#endif
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_validate_config                                              *
 *                                                                            *
 * Purpose: validate configuration parameters                                 *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Vladimir Levijev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static void	zbx_validate_config()
{
	/* hostname */
	if (NULL == CONFIG_HOSTNAME)
	{
		zabbix_log(LOG_LEVEL_CRIT, "hostname is not defined");
		exit(FAIL);
	}

	/* make sure active or passive check is enabled */
	if (1 == CONFIG_DISABLE_ACTIVE && 1 == CONFIG_DISABLE_PASSIVE)
	{
		zabbix_log(LOG_LEVEL_CRIT, "either active or passive checks must be enabled");
		exit(FAIL);
	}
}

#ifdef _WINDOWS
static int	zbx_exec_service_task(const char *name, const ZBX_TASK_EX *t)
{
	int	r;

	switch (t->task)
	{
		case ZBX_TASK_INSTALL_SERVICE:
			r = ZabbixCreateService(name, t->flags & ZBX_TASK_FLAG_MULTIPLE_AGENTS);
			break;
		case ZBX_TASK_UNINSTALL_SERVICE:
			r = ZabbixRemoveService();
			break;
		case ZBX_TASK_START_SERVICE:
			r = ZabbixStartService();
			break;
		case ZBX_TASK_STOP_SERVICE:
			r = ZabbixStopService();
			break;
		default:
			/* there can not be other choice */
			assert(0);
	}

	return r;
}
#endif	/* _WINDOWS */

int	MAIN_ZABBIX_ENTRY()
{
	zbx_thread_args_t		*thread_args;
	ZBX_THREAD_ACTIVECHK_ARGS	activechk_args;
	zbx_sock_t			listen_sock;
	int				i, thread_num = 0;

	if (NULL == CONFIG_LOG_FILE || '\0' == *CONFIG_LOG_FILE)
		zabbix_open_log(LOG_TYPE_SYSLOG, CONFIG_LOG_LEVEL, NULL);
	else
		zabbix_open_log(LOG_TYPE_FILE, CONFIG_LOG_LEVEL, CONFIG_LOG_FILE);

	zabbix_log(LOG_LEVEL_INFORMATION, "Starting Zabbix Agent. Zabbix %s (revision %s).",
			ZABBIX_VERSION, ZABBIX_REVISION);

	if (0 == CONFIG_DISABLE_PASSIVE)
	{
		if (FAIL == zbx_tcp_listen(&listen_sock, CONFIG_LISTEN_IP, (unsigned short)CONFIG_LISTEN_PORT))
		{
			zabbix_log(LOG_LEVEL_CRIT, "Listener failed with error: %s.", zbx_tcp_strerror());
			exit(1);
		}
	}

	/* collector data must be initiated by user 'zabbix' */
	init_collector_data();

#ifdef _WINDOWS
	load_perf_counters(CONFIG_PERF_COUNTERS);
#endif
	load_user_parameters(CONFIG_USER_PARAMETERS);
	load_aliases(CONFIG_ALIASES);

	zbx_free_config();

	/* --- START THREADS ---*/

	if (1 == CONFIG_DISABLE_PASSIVE)
	{
		/* Only main process and active checks will be started */
		CONFIG_ZABBIX_FORKS = 0;/* Listeners won't be needed for passive checks. */
	}

	/* Allocate memory for a collector, all listeners and an active check. */
	threads_num = 1 + CONFIG_ZABBIX_FORKS + (0 == CONFIG_DISABLE_ACTIVE ? 1 : 0);
	threads = calloc(threads_num, sizeof(ZBX_THREAD_HANDLE));

	/* Start the collector thread. */
	thread_args = (zbx_thread_args_t *)zbx_malloc(NULL, sizeof(zbx_thread_args_t));
	thread_args->thread_num = thread_num;
	thread_args->args = NULL;
	threads[thread_num++] = zbx_thread_start(collector_thread, thread_args);

	/* start listeners */
	for (i = 0; i < CONFIG_ZABBIX_FORKS; i++)
	{
		thread_args = (zbx_thread_args_t *)zbx_malloc(NULL, sizeof(zbx_thread_args_t));
		thread_args->thread_num = thread_num;
		thread_args->args = &listen_sock;
		threads[thread_num++] = zbx_thread_start(listener_thread, thread_args);
	}

	/* start active check */
	if (0 == CONFIG_DISABLE_ACTIVE)
	{
		activechk_args.host = CONFIG_HOSTS_ALLOWED;
		activechk_args.port = (unsigned short)CONFIG_SERVER_PORT;

		thread_args = (zbx_thread_args_t *)zbx_malloc(NULL, sizeof(zbx_thread_args_t));
		thread_args->thread_num = thread_num;
		thread_args->args = &activechk_args;
		threads[thread_num++] = zbx_thread_start(active_checks_thread, thread_args);
	}

	/* Must be called after all child processes loading. */
	set_parent_signal_handler();

	/* wait for all threads exiting */
	for (i = 0; i < 1 + CONFIG_ZABBIX_FORKS + (0 == CONFIG_DISABLE_ACTIVE ? 1 : 0); i++)
	{
		if (threads[i])
		{
			zbx_thread_wait(threads[i]);
			zabbix_log(LOG_LEVEL_DEBUG, "thread [%d] has terminated", i);

			ZBX_DO_EXIT();
		}
	}

	zbx_on_exit();

	return SUCCEED;
}

void	zbx_on_exit()
{
	zabbix_log(LOG_LEVEL_DEBUG, "zbx_on_exit() called");

	ZBX_DO_EXIT();

	if (threads != NULL)
	{
		int	i;

		for (i = 0; i < 1 + CONFIG_ZABBIX_FORKS + (0 == CONFIG_DISABLE_ACTIVE ? 1 : 0); i++)
		{
			if (threads[i])
			{
				zbx_thread_kill(threads[i]);
				threads[i] = ZBX_THREAD_HANDLE_NULL;
			}
		}

		zbx_free(threads);
	}

#ifdef USE_PID_FILE

	daemon_stop();

#endif

	zbx_sleep(2); /* wait for all threads closing */

	zabbix_log(LOG_LEVEL_INFORMATION, "Zabbix Agent stopped. Zabbix %s (revision %s).",
			ZABBIX_VERSION, ZABBIX_REVISION);

	zabbix_close_log();

	free_metrics();
	alias_list_free();
	free_collector_data();

	exit(SUCCEED);
}

int	main(int argc, char **argv)
{
	ZBX_TASK_EX	t;
#ifdef _WINDOWS
	int		r;
#endif

#ifdef _WINDOWS
	/* Provide, so our process handles errors instead of the system itself. */
	/* Attention!!! */
	/* The system does not display the critical-error-handler message box. */
	/* Instead, the system sends the error to the calling process.*/
	SetErrorMode(SEM_FAILCRITICALERRORS);
#endif

	memset(&t, 0, sizeof(t));
	t.task = ZBX_TASK_START;

	progname = get_program_name(argv[0]);

	parse_commandline(argc, argv, &t);

	import_symbols();

	/* this is needed to set default hostname in zbx_load_config() */
	init_metrics();

	switch (t.task)
	{
		case ZBX_TASK_SHOW_USAGE:
			usage();
			exit(FAIL);
			break;
#ifdef _WINDOWS
		case ZBX_TASK_INSTALL_SERVICE:
		case ZBX_TASK_UNINSTALL_SERVICE:
		case ZBX_TASK_START_SERVICE:
		case ZBX_TASK_STOP_SERVICE:
			zbx_load_config(0);
			zbx_validate_config();
			zbx_free_config();

			if (t.flags & ZBX_TASK_FLAG_MULTIPLE_AGENTS)
			{
				zbx_snprintf(ZABBIX_SERVICE_NAME, sizeof(ZABBIX_SERVICE_NAME), "%s [%s]",
						APPLICATION_NAME, CONFIG_HOSTNAME);
				zbx_snprintf(ZABBIX_EVENT_SOURCE, sizeof(ZABBIX_EVENT_SOURCE), "%s [%s]",
						APPLICATION_NAME, CONFIG_HOSTNAME);
			}

			r = zbx_exec_service_task(argv[0], &t);
			free_metrics();
			exit(r);
			break;
#endif
		case ZBX_TASK_TEST_METRIC:
		case ZBX_TASK_PRINT_SUPPORTED:
			zbx_load_config(1);	/* optional */
#ifdef _WINDOWS
			init_collector_data();	/* required for reading PerfCounter */
			load_perf_counters(CONFIG_PERF_COUNTERS);
#endif
			load_user_parameters(CONFIG_USER_PARAMETERS);
			load_aliases(CONFIG_ALIASES);
			zbx_free_config();
			if (ZBX_TASK_TEST_METRIC == t.task)
				test_parameter(TEST_METRIC, PROCESS_TEST);
			else
				test_parameters();
#ifdef _WINDOWS
			free_collector_data();
#endif
			free_metrics();
			alias_list_free();
			exit(SUCCEED);
			break;
		default:
			zbx_load_config(0);
			zbx_validate_config();
			break;
	}

	START_MAIN_ZABBIX_ENTRY(CONFIG_ALLOW_ROOT);

	exit(SUCCEED);
}
