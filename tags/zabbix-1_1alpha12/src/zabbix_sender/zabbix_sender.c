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

#include "config.h"

#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <netinet/in.h>
#include <netdb.h>

#include <string.h>

/* OpenBSD*/
#ifdef HAVE_SYS_SOCKET_H
	#include <sys/socket.h>
#endif

#include <signal.h>
#include <time.h>

#include "common.h"

void    signal_handler( int sig )
{
	if( SIGALRM == sig )
	{
		signal( SIGALRM, signal_handler );
		fprintf(stderr,"Timeout while executing operation.\n");
	}
 
	if( SIGQUIT == sig || SIGINT == sig || SIGTERM == sig )
	{
/*		fprintf(stderr,"\nGot QUIT or INT or TERM signal. Exiting..." ); */
	}
	exit( FAIL );
}

static int send_value(char *server,int port,char *hostname, char *key,char *value)
{
	int	i,s;
	char	tosend[MAX_STRING_LEN];
	char	result[MAX_STRING_LEN];
	char	hostname_b64[3*MAX_STRING_LEN];
	char	key_b64[3*MAX_STRING_LEN];
	char	value_b64[3*MAX_STRING_LEN];
	struct hostent *hp;

	struct sockaddr_in myaddr_in;
	struct sockaddr_in servaddr_in;

/*	struct linger ling;*/

	servaddr_in.sin_family=AF_INET;
	hp=gethostbyname(server);

	if(hp==NULL)
	{
		return	FAIL;
	}

	servaddr_in.sin_addr.s_addr=((struct in_addr *)(hp->h_addr))->s_addr;

	servaddr_in.sin_port=htons(port);

	s=socket(AF_INET,SOCK_STREAM,0);
	if(s == -1)
	{
		return	FAIL;
	}

/*	ling.l_onoff=1;*/
/*	ling.l_linger=0;*/
/*	if(setsockopt(s,SOL_SOCKET,SO_LINGER,&ling,sizeof(ling))==-1)*/
/*	{*/
/* Ignore */
/*	}*/
 
	myaddr_in.sin_family = AF_INET;
	myaddr_in.sin_port=0;
	myaddr_in.sin_addr.s_addr=INADDR_ANY;

	if( connect(s,(struct sockaddr *)&servaddr_in,sizeof(struct sockaddr_in)) == -1 )
	{
		close(s);
		return	FAIL;
	}

/* Send <req><host>SERVER_B64</host><key>KEY_B64</key><data>VALUE_B64</data></req> */

	comms_create_request(hostname, key, value, tosend,sizeof(tosend)-1);

//	snprintf(tosend,sizeof(tosend)-1,"%s:%s\n",shortname,value);
//	snprintf(tosend,sizeof(tosend)-1,"<req><host>%s</host><key>%s</key><data>%s</data></req>",hostname_b64,key_b64,value_b64);

	if( sendto(s,tosend,strlen(tosend),0,(struct sockaddr *)&servaddr_in,sizeof(struct sockaddr_in)) == -1 )
	{
		perror("sendto");
		close(s);
		return	FAIL;
	} 
	i=sizeof(struct sockaddr_in);
/*	i=recvfrom(s,result,1023,0,(struct sockaddr *)&servaddr_in,(size_t *)&i);*/
	i=recvfrom(s,result,MAX_STRING_LEN-1,0,(struct sockaddr *)&servaddr_in,(socklen_t *)&i);
	if(s==-1)
	{
		perror("recfrom");
		close(s);
		return	FAIL;
	}

	result[i-1]=0;

	if(strcmp(result,"OK") == 0)
	{
		printf("OK\n");
	}
 
	if( close(s)!=0 )
	{
		perror("close");
		
	}

	return SUCCEED;
}

int main(int argc, char **argv)
{
	int	port;
	int	ret=SUCCEED;
	char	line[MAX_STRING_LEN];
	char	port_str[MAX_STRING_LEN];
	char	zabbix_server[MAX_STRING_LEN];
	char	server[MAX_STRING_LEN];
	char	key[MAX_STRING_LEN];
	char	value[MAX_STRING_LEN];
	char	*s;

	signal( SIGINT,  signal_handler );
	signal( SIGQUIT, signal_handler );
	signal( SIGTERM, signal_handler );
	signal( SIGALRM, signal_handler );

	if(argc == 6)
	{
		port=atoi(argv[2]);

		alarm(SENDER_TIMEOUT);

		ret = send_value(argv[1],port,argv[3],argv[4],argv[5]);

		alarm(0);
	}
/* No parameters are given */	
	else if(argc == 1)
	{
		while(fgets(line,MAX_STRING_LEN,stdin) != NULL)
		{
/*			printf("[%s]\n",line);*/
			alarm(SENDER_TIMEOUT);

			strscpy(zabbix_server,s);
			s=(char *)strtok(NULL," ");
			strscpy(port_str,s);
			s=(char *)strtok(NULL," ");
			strscpy(server,s);
			s=(char *)strtok(NULL," ");
			strscpy(key,s);
			s=(char *)strtok(NULL," ");
			strscpy(value,s);
			ret = send_value(zabbix_server,atoi(port_str),server,key,value);

			alarm(0);
		}
	}
	else
	{
		printf("Usage: zabbix_sender <Zabbix server> <port> <server> <key> <value>\n");
		printf("If no arguments are given, zabbix_sender expects list of parameters\n");
		printf("from standard input.\n");
		
		ret = FAIL;
	}

	return ret;
}
