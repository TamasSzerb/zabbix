#include "config.h"

#include <string.h>

#include <netdb.h>

#include <stdlib.h>
#include <stdio.h>

#include <time.h>

#include <unistd.h>
#include <signal.h>

#include <errno.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>

/* For config file operations */
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>

#include <pwd.h>

#include "common.h"
#include "db.h"
#include "log.h"
#include "cfg.h"
#include "functions.h"

#define	LISTENQ 1024

static pid_t *pids;

int	parent=0;

int	CONFIG_TRAPPERD_FORKS		= TRAPPERD_FORKS;
int	CONFIG_LISTEN_PORT		= 10001;
int	CONFIG_TIMEOUT			= TRAPPER_TIMEOUT;
int	CONFIG_LOG_LEVEL		= LOG_LEVEL_WARNING;
int	CONFIG_NOTIMEWAIT		= 0;
char	*CONFIG_PID_FILE		= NULL;
char	*CONFIG_LOG_FILE		= NULL;
char	*CONFIG_DBNAME			= NULL;
char	*CONFIG_DBUSER			= NULL;
char	*CONFIG_DBPASSWORD		= NULL;
char	*CONFIG_DBSOCKET		= NULL;

void	uninit(void)
{
	int i;

	if(parent == 1)
	{
		if(pids != NULL)
		{
			for(i = 0; i<CONFIG_TRAPPERD_FORKS; i++)
			{
				kill(pids[i],SIGTERM);
			}
		}

		if( unlink(CONFIG_PID_FILE) != 0)
		{
			zabbix_log( LOG_LEVEL_WARNING, "Cannot remove PID file [%s]",
				CONFIG_PID_FILE);
		}
	}
}

void	create_pid_file(void)
{
	FILE	*f;

/* Check if PID file already exists */
	f = fopen(CONFIG_PID_FILE, "r");
	if(f != NULL)
	{
		zabbix_log( LOG_LEVEL_CRIT, "File [%s] exists. Is zabbix_agentd already running ?",
			CONFIG_PID_FILE);
		fclose(f);
		exit(-1);
	}

	f = fopen(CONFIG_PID_FILE, "w");

	if( f == NULL)
	{
		zabbix_log( LOG_LEVEL_CRIT, "Cannot create PID file [%s]. Errno [%d]",
			CONFIG_PID_FILE, errno);
		uninit();
		exit(-1);
	}

	fprintf(f,"%d",getpid());
	fclose(f);
}

void	signal_handler( int sig )
{
	if( SIGALRM == sig )
	{
		signal( SIGALRM, signal_handler );
		zabbix_log( LOG_LEVEL_WARNING, "Timeout while answering request");
	}
	else if( SIGQUIT == sig || SIGINT == sig || SIGTERM == sig )
	{
		zabbix_log( LOG_LEVEL_WARNING, "Got signal. Exiting ...");
		uninit();
		exit( FAIL );
	}
	else if( SIGCHLD == sig )
	{
		zabbix_log( LOG_LEVEL_WARNING, "One child process died. Exiting ...");
		uninit();
		exit( FAIL );
	}
	else
	{
		zabbix_log( LOG_LEVEL_WARNING, "Got signal [%d]. Ignoring ...", sig);
	}
}

void    init_config(void)
{
	static struct cfg_line cfg[]=
	{
/*               PARAMETER      ,VAR    ,FUNC,  TYPE(0i,1s),MANDATORY,MIN,MAX
*/
		{"StartTrappers",&CONFIG_TRAPPERD_FORKS,0,TYPE_INT,PARM_OPT,2,255},
		{"Timeout",&CONFIG_TIMEOUT,0,TYPE_INT,PARM_OPT,1,30},
		{"ListenPort",&CONFIG_LISTEN_PORT,0,TYPE_INT,PARM_OPT,1024,32768},
		{"DebugLevel",&CONFIG_LOG_LEVEL,0,TYPE_INT,PARM_OPT,0,5},
		{"PidFile",&CONFIG_PID_FILE,0,TYPE_STRING,PARM_OPT,0,0},
		{"LogFile",&CONFIG_LOG_FILE,0,TYPE_STRING,PARM_OPT,0,0},
		{"DBName",&CONFIG_DBNAME,0,TYPE_STRING,PARM_MAND,0,0},
		{"DBUser",&CONFIG_DBUSER,0,TYPE_STRING,PARM_OPT,0,0},
		{"DBPassword",&CONFIG_DBPASSWORD,0,TYPE_STRING,PARM_OPT,0,0},
		{"DBSocket",&CONFIG_DBSOCKET,0,TYPE_STRING,PARM_OPT,0,0},
		{"NoTimeWait",&CONFIG_NOTIMEWAIT,0,TYPE_INT,PARM_OPT,0,1},
		{0}
	};

	parse_cfg_file("/etc/zabbix/zabbix_trapperd.conf",cfg);
	if(CONFIG_DBNAME == NULL)
	{
		zabbix_log( LOG_LEVEL_CRIT, "DBName not in config file");
		exit(1);
	}
	if(CONFIG_PID_FILE == NULL)
	{
		CONFIG_PID_FILE=strdup("/tmp/zabbix_trapperd.pid");
	}
}

int	process(char *s)
{
	char	*p;
	char	*server,*key,*value_string;

	int	ret=SUCCEED;

	for( p=s+strlen(s)-1; p>s && ( *p=='\r' || *p =='\n' || *p == ' ' ); --p );
	p[1]=0;

	server=(char *)strtok(s,":");
	if(NULL == server)
	{
		return FAIL;
	}

	key=(char *)strtok(NULL,":");
	if(NULL == key)
	{
		return FAIL;
	}

	value_string=(char *)strtok(NULL,":");
	if(NULL == value_string)
	{
		return FAIL;
	}
/*	???
	value=atof(value_string);*/

	ret=process_data(server,key,value_string);

	return ret;
}

void    daemon_init(void)
{
	int     i;
	pid_t   pid;
	struct passwd *pwd;

	/* running as root ?*/
	if((getuid()==0) || (getuid()==0))
	{
		pwd = getpwnam("zabbix");
		if ( pwd == NULL )
		{
			fprintf(stderr,"User zabbix does not exist.\n");
			fprintf(stderr, "Cannot run as root !\n");
			exit(FAIL);
		}

		if( (setgid(pwd->pw_gid) ==-1) || (setuid(pwd->pw_uid) == -1) )
		{
			fprintf(stderr,"Cannot setgid or setuid to zabbix");
			exit(FAIL);
		}

#ifdef HAVE_FUNCTION_SETEUID
		if( (setegid(pwd->pw_gid) ==-1) || (seteuid(pwd->pw_uid) == -1) )
		{
			fprintf(stderr,"Cannot setegid or seteuid to zabbix");
			exit(FAIL);
		}
#endif

	}

	if( (pid = fork()) != 0 )
	{
		exit( 0 );
	}

	setsid();
	
	signal( SIGHUP, SIG_IGN );

	if( (pid = fork()) !=0 )
	{
		exit( 0 );
	}

	chdir("/");
	umask(0);

	for(i=0;i<MAXFD;i++)
	{
		close(i);
	}

/*	openlog("zabbix_trapperd",LOG_PID,LOG_USER);
	setlogmask(LOG_UPTO(LOG_DEBUG));*
	setlogmask(LOG_UPTO(LOG_WARNING));*/


}

void	process_child(int sockfd)
{
	ssize_t	nread;
	char	line[1024];
	char	result[1024];
	static struct  sigaction phan;

	phan.sa_handler = &signal_handler;
	sigemptyset(&phan.sa_mask);
	phan.sa_flags = 0;
	sigaction(SIGALRM, &phan, NULL);

	alarm(CONFIG_TIMEOUT);

	zabbix_log( LOG_LEVEL_DEBUG, "Before read()");
	if( (nread = read(sockfd, line, 1024)) < 0)
	{
		if(errno == EINTR)
		{
			zabbix_log( LOG_LEVEL_DEBUG, "Read timeout");
		}
		else
		{
			zabbix_log( LOG_LEVEL_DEBUG, "read() failed");
		}
		zabbix_log( LOG_LEVEL_DEBUG, "After read() 1");
		alarm(0);
		return;
	}

	zabbix_log( LOG_LEVEL_DEBUG, "After read() 2 [%d]",nread);

	line[nread-1]=0;

	zabbix_log( LOG_LEVEL_DEBUG, "Got line:%s", line);
	if( SUCCEED == process(line) )
	{
		sprintf(result,"OK\n");
	}
	else
	{
		sprintf(result,"NOT OK\n");
	}
	zabbix_log( LOG_LEVEL_DEBUG, "Sending back:%s", result);
	write(sockfd,result,strlen(result));
	alarm(0);
}

int	tcp_listen(const char *host, int port, socklen_t *addrlenp)
{
	int	sockfd;
	struct	sockaddr_in      serv_addr;
	struct linger ling;

	if ( (sockfd = socket(AF_INET, SOCK_STREAM, 0)) < 0)
	{
		zabbix_log( LOG_LEVEL_CRIT, "Cannot create socket");
		exit(1);
	}

	if(CONFIG_NOTIMEWAIT == 1)
	{
		ling.l_onoff=1;
		ling.l_linger=0;
		if(setsockopt(sockfd,SOL_SOCKET,SO_LINGER,&ling,sizeof(ling))==-1)
		{
			zabbix_log(LOG_LEVEL_WARNING, "Cannot setsockopt SO_LINGER [%s]", strerror(errno));
		}
	}

	bzero((char *) &serv_addr, sizeof(serv_addr));
	serv_addr.sin_family      = AF_INET;
	serv_addr.sin_addr.s_addr = htonl(INADDR_ANY);
	serv_addr.sin_port        = htons(port);

	if (bind(sockfd, (struct sockaddr *) &serv_addr, sizeof(serv_addr)) < 0)
	{
		zabbix_log( LOG_LEVEL_CRIT, "Cannot bind to port %d. Another zabbix_trapperd running ?", port);
		exit(1);
	}
	
	if(listen(sockfd, LISTENQ) !=0 )
	{
		zabbix_log( LOG_LEVEL_CRIT, "listen() failed");
		exit(1);
	}

	*addrlenp = sizeof(serv_addr);

	return  sockfd;
}

void	child_main(int i,int listenfd, int addrlen)
{
	int	connfd;
	socklen_t	clilen;
	struct sockaddr cliaddr;

	zabbix_log( LOG_LEVEL_WARNING, "zabbix_trapperd %ld started",(long)getpid());

	DBconnect(CONFIG_DBNAME, CONFIG_DBUSER, CONFIG_DBPASSWORD, CONFIG_DBSOCKET);

	for(;;)
	{
		clilen = addrlen;
#ifdef HAVE_FUNCTION_SETPROCTITLE
		setproctitle("waiting for connection");
#endif
		connfd=accept(listenfd,&cliaddr, &clilen);
#ifdef HAVE_FUNCTION_SETPROCTITLE
		setproctitle("processing data");
#endif

		process_child(connfd);

		close(connfd);
	}
}

pid_t	child_make(int i,int listenfd, int addrlen)
{
	pid_t	pid;

	if((pid = fork()) >0)
	{
			return (pid);
	}

	/* never returns */
	child_main(i, listenfd, addrlen);

	/* avoid compilator warning */
	return 0;
}

int	main()
{
	int		listenfd;
	socklen_t	addrlen;
	int		i;

	char		host[128];

	static struct  sigaction phan;

	init_config();
	daemon_init();

	if(CONFIG_LOG_FILE == NULL)
	{
		zabbix_open_log(LOG_TYPE_FILE,CONFIG_LOG_LEVEL,NULL);
	}
	else
	{
		zabbix_open_log(LOG_TYPE_FILE,CONFIG_LOG_LEVEL,CONFIG_LOG_FILE);
	}

	create_pid_file();

	phan.sa_handler = &signal_handler;
	sigemptyset(&phan.sa_mask);
	phan.sa_flags = 0;
	sigaction(SIGINT, &phan, NULL);
	sigaction(SIGQUIT, &phan, NULL);
	sigaction(SIGTERM, &phan, NULL);
	sigaction(SIGCHLD, &phan, NULL);

	zabbix_log( LOG_LEVEL_WARNING, "zabbix_trapperd started");

	if(gethostname(host,127) != 0)
	{
		zabbix_log( LOG_LEVEL_CRIT, "gethostname() failed");
		exit(FAIL);
	}

	listenfd = tcp_listen(host,CONFIG_LISTEN_PORT,&addrlen);

	pids = calloc(CONFIG_TRAPPERD_FORKS, sizeof(pid_t));

	for(i = 0; i< CONFIG_TRAPPERD_FORKS; i++)
	{
		pids[i] = child_make(i, listenfd, addrlen);
/*		zabbix_log( LOG_LEVEL_WARNING, "zabbix_trapperd #%d started", pids[i]);*/
	}

#ifdef HAVE_FUNCTION_SETPROCTITLE
		setproctitle("main process");
#endif

	parent=1;

	for(;;)
	{
			pause();
	}

	return SUCCEED;
}
