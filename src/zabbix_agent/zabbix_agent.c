/* 
** Zabbix
** Copyright (C) 2000,2001,2002,2003 Alexei Vladishev
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

#include "config.h"

#include <stdlib.h>
#include <stdio.h>

#include <unistd.h>
#include <signal.h>

#include <errno.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>

/* For bcopy */
#include <string.h>

/* For config file operations */
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>

#include "common.h"
#include "cfg.h"
#include "log.h"
#include "sysinfo.h"
#include "zabbix_agent.h"

static	char	*CONFIG_HOSTS_ALLOWED	= NULL;
static	int	CONFIG_TIMEOUT		= AGENT_TIMEOUT;

void	signal_handler( int sig )
{
	if( SIGALRM == sig )
	{
		signal( SIGALRM, signal_handler );
	}
 
	if( SIGQUIT == sig || SIGINT == sig || SIGTERM == sig )
	{
	}
	exit( FAIL );
}

int	add_parameter(char *value)
{
	char	*value2;

	value2=strstr(value,",");
	if(NULL == value2)
	{
		return	FAIL;
	}
	value2[0]=0;
	value2++;
	add_user_parameter(value, value2);
	return	SUCCEED;
}

void    init_config(void)
{
	struct cfg_line cfg[]=
	{
/*               PARAMETER      ,VAR    ,FUNC,  TYPE(0i,1s),MANDATORY,MIN,MAX
*/
		{"Server",&CONFIG_HOSTS_ALLOWED,0,TYPE_STRING,PARM_MAND,0,0},
		{"Timeout",&CONFIG_TIMEOUT,0,TYPE_INT,PARM_OPT,1,30},
		{"UserParameter",0,&add_parameter,0,0,0,0},
		{0}
	};

	parse_cfg_file("/etc/zabbix/zabbix_agent.conf",cfg);
}
/*
int	check_security(void)
{
	char	*sname;
	struct	sockaddr_in name;
	int	i;
	char	*s;
	char	*tmp;

	i=sizeof(name);
	if(getpeername(0,  (struct sockaddr *)&name, (size_t *)&i) == 0)
	{
		i=sizeof(struct sockaddr_in);

		sname=inet_ntoa(name.sin_addr);

		tmp=strdup(CONFIG_HOSTS_ALLOWED);
                s=(char *)strtok(tmp,",");
                while(s!=NULL)
                {
                        if(strcmp(sname, s)==0)
                        {
                                return  SUCCEED;
                        }
                        s=(char *)strtok(NULL,",");
                }
	}
        else
	{
		return FAIL;
	}
	return	FAIL;
}
*/

int	main()
{
	char	s[MAX_STRING_LEN+1];
	char	value[MAX_STRING_LEN+1];

#ifdef	TEST_PARAMETERS
	init_config();
	test_parameters();
	return	SUCCEED;
#endif

	signal( SIGINT,  signal_handler );
	signal( SIGQUIT, signal_handler );
	signal( SIGTERM, signal_handler );
	signal( SIGALRM, signal_handler );

	init_config();

/* Do not create debug files */
	zabbix_open_log(LOG_TYPE_SYSLOG,LOG_LEVEL_EMPTY,NULL);

	alarm(CONFIG_TIMEOUT);

	if(check_security(0,CONFIG_HOSTS_ALLOWED,0) == FAIL)
	{
		exit(FAIL);
	}

	fgets(s,MAX_STRING_LEN,stdin);
	process(s,value);

	printf("%s\n",value);

	fflush(stdout);

	alarm(0);

	return SUCCEED;
}
