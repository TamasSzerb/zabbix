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
#include <stdlib.h>
#include <unistd.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <netinet/in.h>
#include <netdb.h>

#include <signal.h>

#include <string.h>

#include <time.h>

#include <sys/socket.h>
#include <errno.h>

#include "common.h"
#include "log.h"

#include "email.h"

/*
 * Send email
 */ 
int	send_email(char *smtp_server,char *smtp_helo,char *smtp_email,char *mailto,char *mailsubject,char *mailbody)
{
	int	s;
	int	i,e;
	char	c[MAX_STRING_LEN];
	struct hostent *hp;

	struct sockaddr_in myaddr_in;
	struct sockaddr_in servaddr_in;

	char	*OK_220="220";
	char	*OK_250="250";
	char	*OK_251="251";
	char	*OK_354="354";

	zabbix_log( LOG_LEVEL_DEBUG, "SENDING MAIL");

	servaddr_in.sin_family=AF_INET;
	hp=gethostbyname(smtp_server);
	zabbix_log( LOG_LEVEL_DEBUG, "SENDING MAIL2");
	if(hp==NULL)
	{
		zabbix_log(LOG_LEVEL_ERR, "Cannot get IP for mailserver [%s]",smtp_server);
		return FAIL;
	}

	servaddr_in.sin_addr.s_addr=((struct in_addr *)(hp->h_addr))->s_addr;
	servaddr_in.sin_port=htons(25);

	zabbix_log( LOG_LEVEL_DEBUG, "SENDING MAIL3");

/*	if(hp==NULL)
	{
		zabbix_log(LOG_LEVEL_ERR, "Cannot get IP for mailserver [%s]",smtp_server);
		return FAIL;
	}

	servaddr_in.sin_addr.s_addr=((struct in_addr *)(hp->h_addr))->s_addr;
	servaddr_in.sin_port=htons(25);*/

	s=socket(AF_INET,SOCK_STREAM,0);
	zabbix_log( LOG_LEVEL_DEBUG, "SENDING MAIL4");
	if(s == -1)
	{
		zabbix_log(LOG_LEVEL_ERR, "Cannot create socket");
		return FAIL;
	}
	
	myaddr_in.sin_family = AF_INET;
	myaddr_in.sin_port=0;
	myaddr_in.sin_addr.s_addr=INADDR_ANY;

	if( connect(s,(struct sockaddr *)&servaddr_in,sizeof(struct sockaddr_in)) == -1 )
	{
		zabbix_log(LOG_LEVEL_ERR, "Cannot connect to SMTP server [%s]",smtp_server);
		close(s);
		return FAIL;
	}
	zabbix_log( LOG_LEVEL_DEBUG, "SENDING MAIL5");

	memset(c,0,MAX_STRING_LEN);
/*	i=sizeof(struct sockaddr_in);
	i=recvfrom(s,c,MAX_STRING_LEN,0,(struct sockaddr *)&servaddr_in,&i);*/
	i=read(s,c,MAX_STRING_LEN);
	zabbix_log( LOG_LEVEL_DEBUG, "SENDING MAIL6");
	if(i == -1)
	{
		zabbix_log(LOG_LEVEL_ERR, "Error receiving initial string from SMTP server [%m]");
		close(s);
		return FAIL;
	}
	if(strncmp(OK_220,c,strlen(OK_220)) != 0)
	{
		zabbix_log(LOG_LEVEL_ERR, "No welcome message 220* [%s]", c);
		close(s);
		return FAIL;
	}

	if(strlen(smtp_helo) != 0)
	{
		memset(c,0,MAX_STRING_LEN);
		snprintf(c,sizeof(c)-1,"HELO %s\r\n",smtp_helo);
/*		e=sendto(s,c,strlen(c),0,(struct sockaddr *)&servaddr_in,sizeof(struct sockaddr_in)); */
		e=write(s,c,strlen(c)); 
		zabbix_log( LOG_LEVEL_DEBUG, "SENDING MAIL7");
		if(e == -1)
		{
			zabbix_log(LOG_LEVEL_ERR, "Error sending HELO to mailserver.");
			close(s);
			return FAIL;
		}
				
		memset(c,0,MAX_STRING_LEN);
/*		i=sizeof(struct sockaddr_in);
		i=recvfrom(s,c,MAX_STRING_LEN,0,(struct sockaddr *)&servaddr_in,&i);*/
		i=read(s,c,MAX_STRING_LEN);
		zabbix_log( LOG_LEVEL_DEBUG, "SENDING MAIL8");
		if(i == -1)
		{
			zabbix_log(LOG_LEVEL_ERR, "Error receiving answer on HELO request [%m]");
			close(s);
			return FAIL;
		}
		if(strncmp(OK_250,c,strlen(OK_250)) != 0)
		{
			zabbix_log(LOG_LEVEL_ERR, "Wrong answer on HELO [%s]",c);
			close(s);
			return FAIL;
		}
	}
			
	memset(c,0,MAX_STRING_LEN);
/*	sprintf(c,"MAIL FROM: %s\r\n",smtp_email);*/
	snprintf(c,sizeof(c)-1,"MAIL FROM: <%s>\r\n",smtp_email);
/*	e=sendto(s,c,strlen(c),0,(struct sockaddr *)&servaddr_in,sizeof(struct sockaddr_in)); */
	e=write(s,c,strlen(c)); 
	zabbix_log( LOG_LEVEL_DEBUG, "SENDING MAIL9");
	if(e == -1)
	{
		zabbix_log(LOG_LEVEL_ERR, "Error sending MAIL FROM to mailserver.");
		close(s);
		return FAIL;
	}

	memset(c,0,MAX_STRING_LEN);
/*	i=sizeof(struct sockaddr_in);
	i=recvfrom(s,c,MAX_STRING_LEN,0,(struct sockaddr *)&servaddr_in,&i);*/
	i=read(s,c,MAX_STRING_LEN);
	zabbix_log( LOG_LEVEL_DEBUG, "SENDING MAIL10");
	if(i == -1)
	{
		zabbix_log(LOG_LEVEL_ERR, "Error receiving answer on MAIL FROM request [%m]");
		close(s);
		return FAIL;
	}
	if(strncmp(OK_250,c,strlen(OK_250)) != 0)
	{
		zabbix_log(LOG_LEVEL_ERR, "Wrong answer on MAIL FROM [%s]", c);
		close(s);
		return FAIL;
	}
			
	memset(c,0,MAX_STRING_LEN);
	snprintf(c,sizeof(c)-1,"RCPT TO: <%s>\r\n",mailto);
/*	e=sendto(s,c,strlen(c),0,(struct sockaddr *)&servaddr_in,sizeof(struct sockaddr_in)); */
	e=write(s,c,strlen(c)); 
	zabbix_log( LOG_LEVEL_DEBUG, "SENDING MAIL11");
	if(e == -1)
	{
		zabbix_log(LOG_LEVEL_ERR, "Error sending RCPT TO to mailserver.");
		close(s);
		return FAIL;
	}
	memset(c,0,MAX_STRING_LEN);
/*	i=sizeof(struct sockaddr_in);
	i=recvfrom(s,c,MAX_STRING_LEN,0,(struct sockaddr *)&servaddr_in,&i);*/
	i=read(s,c,MAX_STRING_LEN);
	zabbix_log( LOG_LEVEL_DEBUG, "SENDING MAIL12");
	if(i == -1)
	{
		zabbix_log(LOG_LEVEL_ERR, "Error receiving answer on RCPT TO request [%m]");
		close(s);
		return FAIL;
	}
	/* May return 251 as well: User not local; will forward to <forward-path>. See RFC825 */
	if( strncmp(OK_250,c,strlen(OK_250)) != 0 && strncmp(OK_251,c,strlen(OK_251)) != 0)
	{
		zabbix_log(LOG_LEVEL_ERR, "Wrong answer on RCPT TO [%s]", c);
		close(s);
		return FAIL;
	}
	
	memset(c,0,MAX_STRING_LEN);
	snprintf(c,sizeof(c)-1,"DATA\r\n");
/*	e=sendto(s,c,strlen(c),0,(struct sockaddr *)&servaddr_in,sizeof(struct sockaddr_in)); */
	e=write(s,c,strlen(c)); 
	zabbix_log( LOG_LEVEL_DEBUG, "SENDING MAIL13");
	if(e == -1)
	{
		zabbix_log(LOG_LEVEL_ERR, "Error sending DATA to mailserver.");
		close(s);
		return FAIL;
	}
	memset(c,0,MAX_STRING_LEN);
/*	i=sizeof(struct sockaddr_in);
	i=recvfrom(s,c,MAX_STRING_LEN,0,(struct sockaddr *)&servaddr_in,&i);*/
	i=read(s,c,MAX_STRING_LEN);
	zabbix_log( LOG_LEVEL_DEBUG, "SENDING MAIL14");
	if(i == -1)
	{
		zabbix_log(LOG_LEVEL_ERR, "Error receivng answer on DATA request [%m]");
		close(s);
		return FAIL;
	}
	if(strncmp(OK_354,c,strlen(OK_354)) != 0)
	{
		zabbix_log(LOG_LEVEL_ERR, "Wrong answer on DATA [%s]", c);
		close(s);
		return FAIL;
	}

	memset(c,0,MAX_STRING_LEN);
/*	sprintf(c,"Subject: %s\r\n%s",mailsubject, mailbody);*/
	snprintf(c,sizeof(c)-1,"From:<%s>\r\nTo:<%s>\r\nSubject: %s\r\n\r\n%s",smtp_email,mailto,mailsubject, mailbody);
/*	e=sendto(s,c,strlen(c),0,(struct sockaddr *)&servaddr_in,sizeof(struct sockaddr_in)); */
	e=write(s,c,strlen(c)); 
	if(e == -1)
	{
		zabbix_log(LOG_LEVEL_ERR, "Error sending mail subject and body to mailserver.");
		close(s);
		return FAIL;
	}

	memset(c,0,MAX_STRING_LEN);
	snprintf(c,sizeof(c)-1,"\r\n.\r\n");
/*	e=sendto(s,c,strlen(c),0,(struct sockaddr *)&servaddr_in,sizeof(struct sockaddr_in)); */
	e=write(s,c,strlen(c)); 
	zabbix_log( LOG_LEVEL_DEBUG, "SENDING MAIL15");
	if(e == -1)
	{
		zabbix_log(LOG_LEVEL_ERR, "Error sending . to mailserver.");
		close(s);
		return FAIL;
	}
	memset(c,0,MAX_STRING_LEN);
/*	i=sizeof(struct sockaddr_in);
	i=recvfrom(s,c,MAX_STRING_LEN,0,(struct sockaddr *)&servaddr_in,&i);*/
	i=read(s,c,MAX_STRING_LEN);
	zabbix_log( LOG_LEVEL_DEBUG, "SENDING MAIL16");
	if(i == -1)
	{
		zabbix_log(LOG_LEVEL_ERR, "Error receivng answer on . request [%m]");
		close(s);
		return FAIL;
	}
	if(strncmp(OK_250,c,strlen(OK_250)) != 0)
	{
		zabbix_log(LOG_LEVEL_ERR, "Wrong answer on end of data [%s]", c);
		close(s);
		return FAIL;
	}
	
	memset(c,0,MAX_STRING_LEN);
	snprintf(c,sizeof(c)-1,"QUIT\r\n");
/*	e=sendto(s,c,strlen(c),0,(struct sockaddr *)&servaddr_in,sizeof(struct sockaddr_in)); */
	e=write(s,c,strlen(c)); 
	zabbix_log( LOG_LEVEL_DEBUG, "SENDING MAIL18");
	if(e == -1)
	{
		zabbix_log(LOG_LEVEL_ERR, "Error sending QUIT to mailserver.");
		close(s);
		return FAIL;
	}

	zabbix_log( LOG_LEVEL_DEBUG, "SENDING MAIL19");
	close(s);

	zabbix_log( LOG_LEVEL_DEBUG, "SENDING MAIL. END.");
	
	return SUCCEED;
}
