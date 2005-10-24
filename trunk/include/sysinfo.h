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


#ifndef ZABBIX_SYSINFO_H
#define ZABBIX_SYSINFO_H

/* #define TEST_PARAMETERS */

#define	SYSINFO_RET_OK		0
#define	SYSINFO_RET_FAIL	1
#define	SYSINFO_RET_TIMEOUT	2

#define COMMAND struct command_type
COMMAND
{
	char	*key;
	int	(*function)();
        int	(*function_str)();
	char	*parameter;
};

int	process(char *command, char *value, int test);
void	init_metrics();

void    add_user_parameter(char *key,char *command);
void	test_parameters(void);
int	getPROC(char *file,int lineno,int fieldno, double *value, const char *msg, int mlen_max);
char	*zbx_regexp_match(const char *string, char *pattern, int *len);

#endif
