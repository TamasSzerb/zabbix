/* 
** Zabbix
** Copyright (C) 2000,2001,2002,2003,2004 Alexei Vladishev
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


#include <stdio.h>
#include <string.h>
#include <stdarg.h>
#include <syslog.h>

#include <sys/types.h>
#include <sys/stat.h>
#include <unistd.h>

#include <time.h>

#include "log.h"
#include "common.h"

static	FILE *log_file = NULL;
static	char log_filename[MAX_STRING_LEN+1];

static	int log_type = LOG_TYPE_UNDEFINED;
static	int log_level;

int zabbix_open_log(int type,int level, const char *filename)
{
/* Just return if we do not want to write debug */
	log_level = level;
	if(level == LOG_LEVEL_EMPTY)
	{
		return	SUCCEED;
	}

	if(type == LOG_TYPE_SYSLOG)
	{
        	openlog("zabbix_suckerd",LOG_PID,LOG_USER);
        	setlogmask(LOG_UPTO(LOG_WARNING));
		log_type = LOG_TYPE_SYSLOG;
	}
	else if(type == LOG_TYPE_FILE)
	{
		log_file = fopen(filename,"a+");
		if(log_file == NULL)
		{
			fprintf(stderr, "Unable to open debug file [%s] [%m]\n", filename);
			return	FAIL;
		}
		log_type = LOG_TYPE_FILE;
		strncpy(log_filename,filename,MAX_STRING_LEN);
		fclose(log_file);
	}
	else
	{
/* Not supported logging type */
		fprintf(stderr, "Not supported loggin type [%d]\n", type);
		return	FAIL;
	}
	return	SUCCEED;
}

void zabbix_set_log_level(int level)
{
	log_level = level;
}

void zabbix_log(int level, const char *fmt, ...)
{
	char	str[MAX_STRING_LEN+1];
	char	str2[MAX_STRING_LEN+1];
	time_t	t;
	struct	tm	*tm;
	va_list ap;

	struct stat	buf;
	char	filename_old[MAX_STRING_LEN+1];

	if( (level>log_level) || (level == LOG_LEVEL_EMPTY))
	{
		return;
	}

	if(log_type == LOG_TYPE_SYSLOG)
	{
		va_start(ap,fmt);
		vsprintf(str,fmt,ap);
		strncat(str,"\n",MAX_STRING_LEN);
		str[MAX_STRING_LEN]=0;
		syslog(LOG_DEBUG,str);
		va_end(ap);
	}
	else if(log_type == LOG_TYPE_FILE)
	{
		t=time(NULL);
		tm=localtime(&t);
		sprintf(str2,"%.6d:%.4d%.2d%.2d:%.2d%.2d%.2d ",(int)getpid(),tm->tm_year+1900,tm->tm_mon+1,tm->tm_mday,tm->tm_hour,tm->tm_min,tm->tm_sec);

		va_start(ap,fmt);
		vsnprintf(str,MAX_STRING_LEN,fmt,ap);

		log_file = fopen(log_filename,"a+");
		if(log_file == NULL)
		{
			return;
		}
		fprintf(log_file,"%s",str2);
		fprintf(log_file,"%s",str);
		fprintf(log_file,"\n");
		fclose(log_file);
		va_end(ap);


		if(stat(log_filename,&buf) == 0)
		{
			if(buf.st_size>1024*1024)
			{
				strncpy(filename_old,log_filename,MAX_STRING_LEN);
				strcat(filename_old,".old");
				if(rename(log_filename,filename_old) != 0)
				{
/*					exit(1);*/
				}
			}
		}
	}
	else
	{
		/* Log is not opened */
	}	
        return;
}
