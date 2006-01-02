/* 
** ZabbixW32 - Win32 agent for Zabbix
** Copyright (C) 2002 Victor Kirhenshtein
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
**
** $module: comm.cpp
**
**/

#include "zabbixw32.h"


//
// Request structure
//



//
// Global data
//

double statAcceptedRequests=0;
double statRejectedRequests=0;
double statTimedOutRequests=0;
double statAcceptErrors=0;


//
// Validates server's address
//

static BOOL IsValidServerAddr(DWORD addr)
{
   DWORD i;
   BOOL ret= FALSE;

INIT_CHECK_MEMORY(main);
   for(i=0;i<confServerCount;i++)
      if (addr==confServerAddr[i])
         ret = TRUE;

CHECK_MEMORY(main, "IsValidServerAddr", "end");
   return ret;
}


//
// Request processing thread
//

unsigned int __stdcall ProcessingThread(void *arg)
{
INIT_CHECK_MEMORY(main);
	ProcessCommand(((REQUEST *)arg)->cmd,((REQUEST *)arg)->result);
CHECK_MEMORY(main, "ProcessingThread", "end");

	_endthreadex(0);
	return 0;
}


//
// Client communication thread
//

static void CommThread(void *param)
{
	SOCKET sock;
	int rc;
	REQUEST rq;
	struct timeval timeout;
	FD_SET rdfs;
	HANDLE hThread=NULL;
	unsigned int tid;

//LOG_DEBUG_INFO("CommThread start");

INIT_CHECK_MEMORY(main);
   sock=(SOCKET)param;

   // Wait for command from server
	FD_ZERO(&rdfs);
	FD_SET(sock,&rdfs); // ignore warrning
	timeout.tv_sec=COMMAND_TIMEOUT;
	timeout.tv_usec=0;
	if (select(sock+1,&rdfs,NULL,NULL,&timeout)==0)
	{
		WriteLog(MSG_COMMAND_TIMEOUT,EVENTLOG_WARNING_TYPE,NULL);
      goto end_session;
	}

   rc=recv(sock,rq.cmd,MAX_ZABBIX_CMD_LEN,0);
   if (rc<=0)
   {
      WriteLog(MSG_RECV_ERROR,EVENTLOG_ERROR_TYPE,"s",strerror(errno));
      goto end_session;
   }

   rq.cmd[rc-1]=0;

   hThread=(HANDLE)_beginthreadex(NULL,0,ProcessingThread,(void *)&rq,0,&tid);

   if (WaitForSingleObject(hThread,confTimeout)==WAIT_TIMEOUT)
   {
      strcpy(rq.result,"ZBX_ERROR\n");
      WriteLog(MSG_REQUEST_TIMEOUT,EVENTLOG_WARNING_TYPE,"s",rq.cmd);
      statTimedOutRequests++;
   }
   CloseHandle(hThread);

   send(sock,rq.result,strlen(rq.result),0);

   // Terminate session
end_session:
   shutdown(sock,2);
   closesocket(sock);
   _endthread();

CHECK_MEMORY(main, "CommThread", "end");
}


//
// TCP/IP Listener
//

void ListenerThread(void *)
{
   SOCKET sock,sockClient;
   struct sockaddr_in servAddr;
   int iSize,errorCount=0;

INIT_CHECK_MEMORY(main);
//LOG_DEBUG_INFO("s", "ListenerThread start");

   // Create socket
   if ((sock=socket(AF_INET,SOCK_STREAM,0))==-1)
   {
      WriteLog(MSG_SOCKET_ERROR,EVENTLOG_ERROR_TYPE,"e",WSAGetLastError());
	  _endthread();
      exit(1);
   }

   // Fill in local address structure
   memset(&servAddr,0,sizeof(struct sockaddr_in));
   servAddr.sin_family=AF_INET;
   servAddr.sin_addr.s_addr=htonl(INADDR_ANY);
   servAddr.sin_port=htons(confListenPort);

   // Bind socket
   if (bind(sock,(struct sockaddr *)&servAddr,sizeof(struct sockaddr_in))!=0)
   {
      WriteLog(MSG_BIND_ERROR,EVENTLOG_ERROR_TYPE,"e",WSAGetLastError());
	  _endthread();
      exit(1);
   }

   // Set up queue
   listen(sock,SOMAXCONN);

   // Wait for connection requests
   for(;;)
   {
//LOG_DEBUG_INFO("s","ListenerThread while 1");
INIT_CHECK_MEMORY(while);

      iSize=sizeof(struct sockaddr_in);
      if ((sockClient=accept(sock,(struct sockaddr *)&servAddr,&iSize))==-1)
      {
         int error=WSAGetLastError();

         if (error!=WSAEINTR)
            WriteLog(MSG_ACCEPT_ERROR,EVENTLOG_ERROR_TYPE,"e",error);
         errorCount++;
         statAcceptErrors++;
         if (errorCount>1000)
         {
            WriteLog(MSG_TOO_MANY_ERRORS,EVENTLOG_WARNING_TYPE,NULL);
            errorCount=0;
         }
         Sleep(500);
      }

      errorCount=0;     // Reset consecutive errors counter

      if (IsValidServerAddr(servAddr.sin_addr.S_un.S_addr))
      {
         statAcceptedRequests++;

         _beginthread(CommThread,0,(void *)sockClient);

      }
      else     // Unauthorized connection
      {
         statRejectedRequests++;
         shutdown(sockClient,2);
         closesocket(sockClient);
      }
CHECK_MEMORY(while, "ListenerThread", "while");
   }

CHECK_MEMORY(main, "ListenerThread", "end");
//WriteLog(MSG_SOCKET_ERROR,EVENTLOG_ERROR_TYPE,"s","ListenerThread end");

	_endthread();
}
