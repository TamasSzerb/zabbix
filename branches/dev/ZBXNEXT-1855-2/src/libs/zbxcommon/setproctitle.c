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

/*
** Ideas from PostgreSQL implementation (src/backend/utils/misc/ps_status.c)
** were used in development of this file. Thanks to PostgreSQL developers!
**/

#include "common.h"
#include "setproctitle.h"

#if defined(PS_OVERWRITE_ARGV)
/* external environment we got on startup */
extern char	**environ;
static int	argc_ext = 0;
static int	argc_ext_copied_first = 0, argc_ext_copied_last = 0, environ_ext_copied = 0;
static char	**argv_ext = NULL;

/* internal copy of argv[] and environment variables */
static char	**argv_int = NULL, **environ_int = NULL;
static char	*empty_str = '\0';

/* ps display buffer */
static char	*ps_buf = NULL;
static size_t	ps_buf_size = 0;

/******************************************************************************
 *                                                                            *
 * Function: setproctitle_save_env                                            *
 *                                                                            *
 * Purpose: make a copy of argc, argv[] and environment variables to enable   *
 *          overwriting original argv[] with changing process status messages *
 *          (i.e. to emulate setproctitle()).                                 *
 *                                                                            *
 * Comments: call this function soon after main process start, before using   *
 *           argv[] and environment variables.                                *
 *                                                                            *
 ******************************************************************************/
char **	setproctitle_save_env(int argc, char **argv)
{
	int	i = 0, copy_first, copy_last;
	char	*arg_end = NULL;

	argc_ext = argc;
	argv_ext = argv;

	if (NULL == argv || 0 == argc)
		return argv;

	/* measure a size of continuous argv[] area and make a copy */

	argv_int = zbx_malloc(argv_int, ((unsigned int)argc + 1) * sizeof(char *));

#if defined(PS_APPEND_ARGV)
	copy_first = argc - 1;
#else
	copy_first = 0;
#endif
	copy_last = argc - 1;

	for (i = 0; i < copy_first; i++)
		argv_int[i] = argv[i];

	for (i = copy_first; i <= copy_last; i++)
	{
		if (copy_first == i)
			argc_ext_copied_first = i;

		if (copy_first == i || arg_end + 1 == argv[i])
		{
			arg_end = argv[i] + strlen(argv[i]);
			argv_int[i] = zbx_strdup(NULL, argv[i]);
			argc_ext_copied_last = i;

			/* argv[copy_first] will be used to display status messages. The rest of arguments can be */
			/* overwritten and their argv[] pointers will point to wrong strings. */
			if (copy_first < i)
				argv[i] = empty_str;
		}
		else
			break;
	}

	for (; i < argc; i++)
		argv_int[i] = argv[i];

	argv_int[argc] = NULL;	/* C standard: "argv[argc] shall be a null pointer" */

	if (argc_ext_copied_last == argc - 1)
	{
		int	envc = 0, copy_arg = 1;

		for (i = 0; NULL != environ[i]; i++)
			envc++;

		/* measure a size of continuous environment area and make a copy */

		environ_int = zbx_malloc(environ_int, ((unsigned int)envc + 1) * sizeof(char *));

		for (i = 0; i < envc; i++)
		{
			if (1 == copy_arg && arg_end + 1 == environ[i])
			{
				arg_end = environ[i] + strlen(environ[i]);
				environ_int[i] = zbx_strdup(NULL, environ[i]);
				environ_ext_copied++;

				/* environment variables can be overwritten by status messages in argv[0] */
				/* and environ[] pointers will point to wrong strings */
				environ[i] = empty_str;
			}
			else
			{
				copy_arg = 0;
				environ_int[i] = environ[i];
			}
		}
		environ_int[envc] = NULL;
	}

	ps_buf_size = (size_t)(arg_end - argv[copy_first] + 1);
	ps_buf = argv[copy_first];

	environ = environ_int;		/* switch environment to internal copy */

	return argv_int;
}

/******************************************************************************
 *                                                                            *
 * Function: setproctitle_set_status                                          *
 *                                                                            *
 * Purpose: set a process command line displayed by "ps" command.             *
 *                                                                            *
 * Comments: call this function when a process starts some interesting task.  *
 *           Program name argv[0] will be displayed "as-is" followed by ": "  *
 *           and a status message.                                            *
 *                                                                            *
 ******************************************************************************/
void	setproctitle_set_status(const char *status)
{
	static int	initialized = 0;

	if (1 == initialized)
	{
		zbx_strlcpy(ps_buf, status, ps_buf_size);
	}
	else if (NULL != ps_buf)
	{
		size_t	start_pos = strlen(ps_buf);	/* argv[copy_first] */

		if (start_pos + 2 < ps_buf_size)	/* is there space for ": " ? */
		{
			zbx_strlcpy(ps_buf + start_pos, ": ", (size_t)3);
			ps_buf += start_pos + 2;
			ps_buf_size -= start_pos + 2;	/* space after "argv[copy_first]: " for status message */
			memset(ps_buf, ' ', ps_buf_size - 1);
			memset(ps_buf + ps_buf_size - 1, '\0', (size_t)1);
			initialized = 1;
			zbx_strlcpy(ps_buf, status, ps_buf_size);
		}
	}
}

/******************************************************************************
 *                                                                            *
 * Function: setproctitle_free_env                                            *
 *                                                                            *
 * Purpose: release memory allocated in setproctitle_save_env().              *
 *                                                                            *
 * Comments: call this function when process terminates and argv[] and        *
 *           environment variables are not used anymore.                      *
 *                                                                            *
 ******************************************************************************/
void	setproctitle_free_env(void)
{
	int	i;

	for (i = argc_ext_copied_first; i <= argc_ext_copied_last; i++)
		zbx_free(argv_int[i]);

	for (i = 0; i <= environ_ext_copied; i++)
		zbx_free(environ_int[i]);

	zbx_free(argv_int);
	zbx_free(environ_int);
}
#endif	/* PS_OVERWRITE_ARGV */
