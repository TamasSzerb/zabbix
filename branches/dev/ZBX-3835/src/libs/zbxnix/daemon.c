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
#include "daemon.h"

#include "pid.h"
#include "cfg.h"
#include "log.h"
#include "zbxself.h"

#include "fatal.h"

char		*CONFIG_PID_FILE = NULL;

static int	parent = 0;
static int	parent_pid = -1;
static int	exiting = 0;

#define CHECKED_FIELD(siginfo, field)			(NULL == siginfo ? -1 : siginfo->field)
#define CHECKED_FIELD_TYPE(siginfo, field, type)	(NULL == siginfo ? (type)-1 : siginfo->field)

static void	child_signal_handler(int sig, siginfo_t *siginfo, void *context)
{
	if (NULL == siginfo)
	{
		zabbix_log(LOG_LEVEL_DEBUG, "received [signal:%d(%s)] with NULL siginfo",
				sig, get_signal_name(sig));
	}

	if (NULL == context)
	{
		zabbix_log(LOG_LEVEL_DEBUG, "received [signal:%d(%s)] with NULL context",
				sig, get_signal_name(sig));
	}

	switch (sig)
	{
		case SIGALRM:
			zabbix_log(LOG_LEVEL_DEBUG, "timeout while answering request");
			break;
		case SIGILL:
		case SIGFPE:
		case SIGSEGV:
		case SIGBUS:
			zabbix_log(LOG_LEVEL_CRIT, "Got signal [signal:%d(%s),reason:%d,refaddr:%p]. Crashing ...",
					sig, get_signal_name(sig),
					CHECKED_FIELD(siginfo, si_code),
					CHECKED_FIELD_TYPE(siginfo, si_addr, void *));
			print_fatal_info(sig, siginfo, context);
			exit(FAIL);
			break;
		case SIGUSR1:
			zabbix_log(LOG_LEVEL_DEBUG, "Got signal [signal:%d(%s),sender_pid:%d,sender_uid:%d,value_int:%d].",
					sig, get_signal_name(sig),
					CHECKED_FIELD(siginfo, si_pid),
					CHECKED_FIELD(siginfo, si_uid),
					CHECKED_FIELD(siginfo, si_value.sival_int));
#ifdef HAVE_SIGQUEUE
			if (1 == parent)
			{
				if (ZBX_TASK_CONFIG_CACHE_RELOAD == CHECKED_FIELD(siginfo, si_value.sival_int))
				{
					extern unsigned char	daemon_type;

					if (0 != (daemon_type & ZBX_DAEMON_TYPE_PROXY_PASSIVE))
					{
						zabbix_log(LOG_LEVEL_WARNING, "forced reloading of the"
								" configuration cache cannot be"
								" performed for passive proxy");
					}
					else
					{
						union sigval	s;
						extern pid_t	*threads;

						s.sival_int = ZBX_TASK_CONFIG_CACHE_RELOAD;

						if (-1 != sigqueue(threads[1], SIGUSR1, s))
							zabbix_log(LOG_LEVEL_DEBUG,
									"the signal is redirected to"
									" the configuration syncer");
						else
							zabbix_log(LOG_LEVEL_ERR,
									"failed to redirect signal: %s",
									zbx_strerror(errno));
					}
				}
			}
			else
			{
				extern void	zbx_sigusr_handler(zbx_task_t task);

				zbx_sigusr_handler(CHECKED_FIELD(siginfo, si_value.sival_int));
			}
#endif
			break;
		case SIGQUIT:
		case SIGINT:
		case SIGTERM:
			zabbix_log(parent_pid == CHECKED_FIELD(siginfo, si_pid) ? LOG_LEVEL_DEBUG : LOG_LEVEL_WARNING,
					"Got signal [signal:%d(%s),sender_pid:%d,sender_uid:%d,reason:%d]. Exiting ...",
					sig, get_signal_name(sig),
					CHECKED_FIELD(siginfo, si_pid),
					CHECKED_FIELD(siginfo, si_uid),
					CHECKED_FIELD(siginfo, si_code));

			if (1 == parent)
			{
				if (0 == exiting)
				{
					exiting = 1;
					zbx_on_exit();
				}
			}
			else
				exit(FAIL);
			break;
		case SIGPIPE:
			zabbix_log(LOG_LEVEL_DEBUG, "Got signal [signal:%d(%s),sender_pid:%d]. Ignoring ...",
					sig, get_signal_name(sig),
					CHECKED_FIELD(siginfo, si_pid));
			break;
		default:
			zabbix_log(LOG_LEVEL_WARNING, "Got signal [signal:%d(%s),sender_pid:%d,sender_uid:%d]. Ignoring ...",
					sig, get_signal_name(sig),
					CHECKED_FIELD(siginfo, si_pid),
					CHECKED_FIELD(siginfo, si_uid));
	}
}

static void	parent_signal_handler(int sig, siginfo_t *siginfo, void *context)
{
	switch (sig)
	{
		case SIGCHLD:
			if (1 == parent)
			{
				if (0 == exiting)
				{
					int		i, found = 0;
					extern int	threads_num;
					extern pid_t	*threads;

					for (i = 1; i < threads_num && !found; i++)
						found = (threads[i] == CHECKED_FIELD(siginfo, si_pid));

					if (0 == found)	/* we should not worry too much about non-Zabbix child */
						return;	/* processes, like watchdog alert scripts, terminating */

					zabbix_log(LOG_LEVEL_CRIT, "One child process died (PID:%d,exitcode/signal:%d). Exiting ...",
							CHECKED_FIELD(siginfo, si_pid),
							CHECKED_FIELD(siginfo, si_status));
					exiting = 1;
					zbx_on_exit();
				}
			}
			else
				exit(FAIL);
			break;
		default:
			child_signal_handler(sig, siginfo, context);
	}
}

/******************************************************************************
 *                                                                            *
 * Function: daemon_start                                                     *
 *                                                                            *
 * Purpose: init process as daemon                                            *
 *                                                                            *
 * Parameters: allow_root - allow root permission for application             *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments: it doesn't allow running under 'root' if allow_root is zero      *
 *                                                                            *
 ******************************************************************************/
int	daemon_start(int allow_root)
{
	pid_t			pid;
	struct passwd		*pwd;
	struct sigaction	phan;
	char			user[7] = "zabbix";

	/* running as root ? */
	if((0 == allow_root) && (0 == getuid() || 0 == getgid()))
	{
		pwd = getpwnam(user);
		if (NULL == pwd)
		{
			zbx_error("user %s does not exist", user);
			zbx_error("Cannot run as root!");
			exit(FAIL);
		}

		if (-1 == setgid(pwd->pw_gid))
		{
			zbx_error("cannot setgid to %s: %s", user, zbx_strerror(errno));
			exit(FAIL);
		}

#ifdef HAVE_FUNCTION_INITGROUPS
		if (-1 == initgroups(user, pwd->pw_gid))
		{
			zbx_error("cannot initgroups to %s: %s", user, zbx_strerror(errno));
			exit(FAIL);
		}
#endif

		if (-1 == setuid(pwd->pw_uid))
		{
			zbx_error("cannot setuid to %s: %s", user, zbx_strerror(errno));
			exit(FAIL);
		}

#ifdef HAVE_FUNCTION_SETEUID
		if (-1 == setegid(pwd->pw_gid) || -1 == seteuid(pwd->pw_uid))
		{
			zbx_error("cannot setegid or seteuid to %s: %s", user, zbx_strerror(errno));
			exit(FAIL);
		}
#endif
	}

	if (0 != (pid = zbx_fork()))
		exit(0);

	setsid();

	signal(SIGHUP, SIG_IGN);

	if (0 != (pid = zbx_fork()))
		exit( 0 );

	/* this is to eliminate warning: ignoring return value of chdir */
	if (-1 == chdir("/"))
		assert(0);

	umask(0002);

	redirect_std(CONFIG_LOG_FILE);

#ifdef HAVE_SYS_RESOURCE_SETPRIORITY
	if (0 != setpriority(PRIO_PROCESS, 0, 5))
		zbx_error("Unable to set process priority to 5. Leaving default.");
#endif

/*------------------------------------------------*/

	if (FAIL == create_pid_file(CONFIG_PID_FILE))
		exit(FAIL);

	parent_pid = (int)getpid();

	phan.sa_sigaction = child_signal_handler;
	sigemptyset(&phan.sa_mask);
	phan.sa_flags = SA_SIGINFO;

	sigaction(SIGINT, &phan, NULL);
	sigaction(SIGQUIT, &phan, NULL);
	sigaction(SIGTERM, &phan, NULL);
	sigaction(SIGPIPE, &phan, NULL);

	sigaction(SIGILL, &phan, NULL);
	sigaction(SIGFPE, &phan, NULL);
	sigaction(SIGSEGV, &phan, NULL);
	sigaction(SIGBUS, &phan, NULL);

	sigaction(SIGUSR1, &phan, NULL);

	zbx_setproctitle("main process");

	return MAIN_ZABBIX_ENTRY();
}

void	daemon_stop()
{
	drop_pid_file(CONFIG_PID_FILE);
}

void	set_parent_signal_handler()
{
	struct sigaction	phan;

	parent = 1;	/* signalize signal handler that this process is a PARENT process */

	phan.sa_sigaction = parent_signal_handler;
	sigemptyset(&phan.sa_mask);
	phan.sa_flags = SA_SIGINFO;
	sigaction(SIGCHLD, &phan, NULL);	/* for parent only, to avoid problems with EXECUTE_INT/DBL/STR and others */
}

void	set_child_signal_handler()
{
	struct sigaction	phan;

	phan.sa_sigaction = child_signal_handler;
	sigemptyset(&phan.sa_mask);
	phan.sa_flags = SA_SIGINFO;
	sigaction(SIGALRM, &phan, NULL);
}

int	zbx_sigusr_send(zbx_task_t task)
{
	int	ret = FAIL;
	char	error[256];
#ifdef HAVE_SIGQUEUE
	pid_t	pid;

	if (SUCCEED == read_pid_file(CONFIG_PID_FILE, &pid, error, sizeof(error)))
	{
		union sigval	s;

		s.sival_int = task;

		if (-1 != sigqueue(pid, SIGUSR1, s))
		{
			printf("command sent successfully\n");
			ret = SUCCEED;
		}
		else
			zbx_snprintf(error, sizeof(error), "cannot send command to PID [%d]: %s",
					(int)pid, zbx_strerror(errno));
	}
#else
	zbx_snprintf(error, sizeof(error), "operation is not supported on the given operating system");
#endif

	if (SUCCEED != ret)
		printf("%s\n", error);

	return ret;
}
