#include <stdlib.h>
#include <stdio.h>

#include "db.h"
#include "log.h"
#include "common.h"

#ifdef	HAVE_MYSQL
	MYSQL	mysql;
#endif

#ifdef	HAVE_PGSQL
	PGconn	*conn;
#endif

void	DBclose(void)
{
#ifdef	HAVE_MYSQL
	mysql_close(&mysql);
#endif
#ifdef	HAVE_PGSQL
	PQfinish(conn);
#endif
}

/*
 * Connect to database.
 * If fails, program terminates.
 */ 
void    DBconnect( char *dbname, char *dbuser, char *dbpassword, char *dbsocket)
{
/*	zabbix_log(LOG_LEVEL_ERR, "[%s] [%s] [%s]\n",dbname, dbuser, dbpassword ); */
#ifdef	HAVE_MYSQL
/* For MySQL >3.22.00 */
/*	if( ! mysql_connect( &mysql, NULL, dbuser, dbpassword ) )*/
	if( ! mysql_real_connect( &mysql, NULL, dbuser, dbpassword, dbname, 3306, dbsocket,0 ) )
	{
		zabbix_log(LOG_LEVEL_ERR, "Failed to connect to database: Error: %s\n",mysql_error(&mysql) );
		exit( FAIL );
	}
	if( mysql_select_db( &mysql, dbname ) != 0 )
	{
		zabbix_log(LOG_LEVEL_ERR, "Failed to select database: Error: %s\n",mysql_error(&mysql) );
		exit( FAIL );
	}
#endif
#ifdef	HAVE_PGSQL
/*	conn = PQsetdb(pghost, pgport, pgoptions, pgtty, dbName); */
/*	conn = PQsetdb(NULL, NULL, NULL, NULL, dbname);*/
	conn = PQsetdbLogin(NULL, NULL, NULL, NULL, dbname, dbuser, dbpassword );

/* check to see that the backend connection was successfully made */
	if (PQstatus(conn) == CONNECTION_BAD)
	{
		zabbix_log(LOG_LEVEL_ERR, "Connection to database '%s' failed.\n", dbname);
		zabbix_log(LOG_LEVEL_ERR, "%s", PQerrorMessage(conn));
		exit(FAIL);
	}
#endif
}

/*
 * Execute SQL statement. For non-select statements only.
 * If fails, program terminates.
 */ 
int	DBexecute(char *query)
{

#ifdef	HAVE_MYSQL
	zabbix_log( LOG_LEVEL_DEBUG, "Executing query:%s\n",query); 
/*	zabbix_log( LOG_LEVEL_WARNING, "Executing query:%s\n",query)*/;

	if( mysql_query(&mysql,query) != 0 )
	{
		zabbix_log( LOG_LEVEL_ERR, "Query::%s",query);
		zabbix_log(LOG_LEVEL_ERR, "Query failed:%s", mysql_error(&mysql) );
		return FAIL;
	}
#endif
#ifdef	HAVE_PGSQL
	PGresult	*result;

	zabbix_log( LOG_LEVEL_DEBUG, "Executing query:%s\n",query);

	result = PQexec(conn,query);

	if( result==NULL)
	{
		zabbix_log( LOG_LEVEL_ERR, "Query::%s",query);
		zabbix_log(LOG_LEVEL_ERR, "Query failed:%s", "Result is NULL" );
		PQclear(result);
		return FAIL;
	}
	if( PQresultStatus(result) != PGRES_COMMAND_OK)
	{
		zabbix_log( LOG_LEVEL_ERR, "Query::%s",query);
		zabbix_log(LOG_LEVEL_ERR, "Query failed:%s", PQresStatus(PQresultStatus(result)) );
		PQclear(result);
		return FAIL;
	}
	PQclear(result);
#endif
	return	SUCCEED;
}

/*
 * Execute SQL statement. For select statements only.
 * If fails, program terminates.
 */ 
DB_RESULT *DBselect(char *query)
{
#ifdef	HAVE_MYSQL
	zabbix_log( LOG_LEVEL_DEBUG, "Executing query:%s\n",query);
/*	zabbix_log( LOG_LEVEL_WARNING, "Executing query:%s\n",query);*/

	if( mysql_query(&mysql,query) != 0 )
	{
		zabbix_log( LOG_LEVEL_ERR, "Query::%s",query);
		zabbix_log(LOG_LEVEL_ERR, "Query failed:%s", mysql_error(&mysql) );
		exit( FAIL );
	}
	return	mysql_store_result(&mysql);
#endif
#ifdef	HAVE_PGSQL
	PGresult	*result;

	zabbix_log( LOG_LEVEL_DEBUG, "Executing query:%s\n",query);
	result = PQexec(conn,query);

	if( result==NULL)
	{
		zabbix_log( LOG_LEVEL_ERR, "Query::%s",query);
		zabbix_log(LOG_LEVEL_ERR, "Query failed:%s", "Result is NULL" );
		exit( FAIL );
	}
	if( PQresultStatus(result) != PGRES_TUPLES_OK)
	{
		zabbix_log( LOG_LEVEL_ERR, "Query::%s",query);
		zabbix_log(LOG_LEVEL_ERR, "Query failed:%s", PQresStatus(PQresultStatus(result)) );
		exit( FAIL );
	}
	return result;
#endif
}

/*
 * Get value for given row and field. Must be called after DBselect.
 */ 
char	*DBget_field(DB_RESULT *result, int rownum, int fieldnum)
{
#ifdef	HAVE_MYSQL
	MYSQL_ROW	row;

	mysql_data_seek(result, rownum);
	row=mysql_fetch_row(result);
	zabbix_log(LOG_LEVEL_DEBUG, "Got field:%s", row[fieldnum] );
	return row[fieldnum];
#endif
#ifdef	HAVE_PGSQL
	return PQgetvalue(result, rownum, fieldnum);
#endif
}

/*
 * Return SUCCEED if result conains no records
 */ 
int	DBis_empty(DB_RESULT *result)
{
	if(result == NULL)
	{
		return	SUCCEED;
	}
	if(DBnum_rows(result) == 0)
	{
		return	SUCCEED;
	}
/* This is necessary to exclude situations like
 * atoi(DBget_field(result,0,0). This lead to coredump.
 */
	if(DBget_field(result,0,0) == 0)
	{
		return	SUCCEED;
	}

	return FAIL;
}

/*
 * Get number of selected records.
 */ 
int	DBnum_rows(DB_RESULT *result)
{
#ifdef	HAVE_MYSQL
	return mysql_num_rows(result);
#endif
#ifdef	HAVE_PGSQL
	return PQntuples(result);
#endif
}

/*
 * Get function value.
 */ 
int     DBget_function_result(float *result,char *functionid)
{
	DB_RESULT *res;

        char	sql[MAX_STRING_LEN+1];

	sprintf( sql, "select lastvalue from functions where functionid=%s", functionid );
	res = DBselect(sql);

	if(DBis_empty(res) == SUCCEED)
	{
        	DBfree_result(res);
		zabbix_log(LOG_LEVEL_WARNING, "Query failed for functionid:[%s]", functionid );
		return FAIL;	
	}

        *result=atof(DBget_field(res,0,0));
        DBfree_result(res);

        return SUCCEED;
}

/* SUCCEED if latest alarm with triggerid has this status */
int	latest_alarm(int triggerid, int status)
{
	char	sql[MAX_STRING_LEN+1];
	int	clock;
	DB_RESULT	*result;
	int ret = FAIL;


	zabbix_log(LOG_LEVEL_DEBUG,"In latest_alarm()");

	sprintf(sql,"select max(clock) from alarms where triggerid=%d",triggerid);
		zabbix_log(LOG_LEVEL_DEBUG,"SQL [%s]",sql);
	result = DBselect(sql);

	if(DBis_empty(result) == SUCCEED)
        {
                zabbix_log(LOG_LEVEL_DEBUG, "Result for MIN is empty" );
                ret = FAIL;
        }
	else
	{
	zabbix_log(LOG_LEVEL_DEBUG,"In latest_alarm(0)");
		clock=atoi(DBget_field(result,0,0));
	zabbix_log(LOG_LEVEL_DEBUG,"In latest_alarm(1)");
		DBfree_result(result);

		sprintf(sql,"select istrue from alarms where triggerid=%d and clock=%d",triggerid,clock);
		zabbix_log(LOG_LEVEL_DEBUG,"SQL [%s]",sql);
		result = DBselect(sql);
		if(DBnum_rows(result)==1)
		{
			if(atoi(DBget_field(result,0,0)) == status)
			{
				ret = SUCCEED;
			}
		}
	}

	DBfree_result(result);

	return ret;
}

int	add_alarm(int triggerid, int status)
{
	int	now;
	char	sql[MAX_STRING_LEN+1];

	zabbix_log(LOG_LEVEL_DEBUG,"In add_alarm()");

	if(latest_alarm(triggerid,status) == SUCCEED)
	{
		return SUCCEED;
	}

	now=time(NULL);
	sprintf(sql,"insert into alarms(triggerid,clock,istrue) values(%d,%d,%d)",triggerid,now,status);
	zabbix_log(LOG_LEVEL_DEBUG,"SQL [%s]",sql);
	DBexecute(sql);

	zabbix_log(LOG_LEVEL_DEBUG,"End of add_alarm()");
	
	return SUCCEED;
}

int	update_trigger_status(int triggerid,int status)
{
	char	sql[MAX_STRING_LEN+1];

	zabbix_log(LOG_LEVEL_DEBUG,"In update_trigger_status()");
	add_alarm(triggerid,status);

	sprintf(sql,"update triggers set istrue=%d where triggerid=%d",status,triggerid);
	zabbix_log(LOG_LEVEL_DEBUG,"SQL [%s]",sql);
	DBexecute(sql);

	zabbix_log(LOG_LEVEL_DEBUG,"End of update_trigger_status()");
	return SUCCEED;
}

int update_triggers_status_to_unknown(int hostid)
{
	int	i;
	char	sql[MAX_STRING_LEN+1];
	int	triggerid;

	DB_RESULT	*result;

	zabbix_log(LOG_LEVEL_DEBUG,"In update_triggers_status_to_unknown()");

	sprintf(sql,"select distinct t.triggerid from hosts h,items i,triggers t,functions f where f.triggerid=t.triggerid and f.itemid=i.itemid and h.hostid=i.hostid and h.hostid=%d",hostid);
	zabbix_log(LOG_LEVEL_DEBUG,"SQL [%s]",sql);
	result = DBselect(sql);

	if( DBis_empty(result) == SUCCEED )
	{
		zabbix_log(LOG_LEVEL_DEBUG, "No triggers to update for hostid=[%d]",hostid);
		DBfree_result(result);
		return FAIL; 
	}

	for(i=0;i<DBnum_rows(result);i++)
	{
		triggerid=atoi(DBget_field(result,i,0));
		update_trigger_status(triggerid,TRIGGER_STATUS_UNKNOWN);
	}

	DBfree_result(result);
	zabbix_log(LOG_LEVEL_DEBUG,"End of update_triggers_status_to_unknown()");

	return SUCCEED; 
}

int DBupdate_host_status(int hostid,int status)
{
	DB_RESULT	*result;
	char	sql[MAX_STRING_LEN+1];
	int	now;
	int	disable_until;

	zabbix_log(LOG_LEVEL_DEBUG,"In update_host_status()");

	sprintf(sql,"select status,disable_until from hosts where hostid=%d",hostid);
	zabbix_log(LOG_LEVEL_DEBUG,"SQL [%s]",sql);
	result = DBselect(sql);

	if( DBis_empty(result) == SUCCEED)
	{
		zabbix_log(LOG_LEVEL_WARNING, "No host with hostid=[%d]",hostid);
		DBfree_result(result);
		return FAIL; 
	}

	now=time(NULL);

	disable_until = atoi(DBget_field(result,0,1));

	if(status == atoi(DBget_field(result,0,0)))
	{
		if((status==HOST_STATUS_UNREACHABLE) 
		&&(now+DELAY_ON_NETWORK_FAILURE>atoi(DBget_field(result,0,1))) )
		{
		}
		else
		{
			zabbix_log(LOG_LEVEL_DEBUG, "Host already has status [%d]",status);
			DBfree_result(result);
			return FAIL;
		}
	}

	DBfree_result(result);

	if(status==HOST_STATUS_MONITORED)
	{
		sprintf(sql,"update hosts set status=%d where hostid=%d",HOST_STATUS_MONITORED,hostid);
		zabbix_log(LOG_LEVEL_DEBUG,"SQL [%s]",sql);
		DBexecute(sql);
	}
	else if(status==HOST_STATUS_NOT_MONITORED)
	{
		sprintf(sql,"update hosts set status=%d where hostid=%d",HOST_STATUS_NOT_MONITORED,hostid);
		zabbix_log(LOG_LEVEL_DEBUG,"SQL [%s]",sql);
		DBexecute(sql);
	}
	else if(status==HOST_STATUS_UNREACHABLE)
	{
		if(disable_until+DELAY_ON_NETWORK_FAILURE>now)
		{
			sprintf(sql,"update hosts set status=%d,disable_until=disable_until+%d where hostid=%d",HOST_STATUS_UNREACHABLE,DELAY_ON_NETWORK_FAILURE,hostid);
		}
		else
		{
			sprintf(sql,"update hosts set status=%d,disable_until=%d where hostid=%d",HOST_STATUS_UNREACHABLE,now+DELAY_ON_NETWORK_FAILURE,hostid);
		}
		zabbix_log(LOG_LEVEL_DEBUG,"SQL [%s]",sql);
		DBexecute(sql);
	}
	else
	{
		zabbix_log( LOG_LEVEL_ERR, "Unknown host status [%d]", status);
		return FAIL;
	}

	update_triggers_status_to_unknown(hostid);
	zabbix_log(LOG_LEVEL_DEBUG,"End of update_host_status()");

	return SUCCEED;
}
