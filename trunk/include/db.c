#include <stdlib.h>
#include <stdio.h>
#include <syslog.h>

#include "db.h"
#include "common.h"

#ifdef	USE_MYSQL
MYSQL	mysql;
#endif

#ifdef	USE_POSTGRESQL
PGconn	*conn;
#endif

void    DBconnect( void )
{
#ifdef	USE_MYSQL
	if( ! mysql_connect( &mysql, NULL, DB_USER, DB_PASSWD ) )
	{
		syslog(LOG_ERR, "Failed to connect to database: Error: %s\n",mysql_error(&mysql) );
		exit( FAIL );
	}
	if( mysql_select_db( &mysql, DB_NAME ) != 0 )
	{
		syslog(LOG_ERR, "Failed to select database: Error: %s\n",mysql_error(&mysql) );
		exit( FAIL );
	}
#endif
#ifdef	USE_POSTGRESQL
/*	conn = PQsetdb(pghost, pgport, pgoptions, pgtty, dbName); */
	conn = PQsetdb(NULL, NULL, NULL, NULL, "zabbix");

/* check to see that the backend connection was successfully made */
	if (PQstatus(conn) == CONNECTION_BAD)
	{
		syslog(LOG_ERR, "Connection to database '%s' failed.\n", "zabbix");
		syslog(LOG_ERR, "%s", PQerrorMessage(conn));
		exit(FAIL);
	}
#endif
}

void	DBexecute(char *query)
{

#ifdef	USE_MYSQL
	syslog( LOG_DEBUG, "Executing query:%s\n",query);

	if( mysql_query(&mysql,query) != 0 )
	{
		syslog(LOG_ERR, "Query failed:%s", mysql_error(&mysql) );
		exit( FAIL );
	}
#endif
#ifdef	USE_POSTGRESQL
	PGresult	*result;

	syslog( LOG_DEBUG, "Executing query:%s\n",query);

	result = PQexec(conn,query);

	if( result==NULL)
	{
		syslog(LOG_ERR, "Query failed:%s", "Result is NULL" );
		exit( FAIL );
	}
	if( PQresultStatus(result) != PGRES_COMMAND_OK)
	{
		syslog(LOG_ERR, "Query failed:%s", PQresStatus(PQresultStatus(result)) );
		exit( FAIL );
	}
	PQclear(result);
#endif
}

DB_RESULT *DBselect(char *query)
{
#ifdef	USE_MYSQL
	syslog( LOG_DEBUG, "Executing query:%s\n",query);

	if( mysql_query(&mysql,query) != 0 )
	{
		syslog(LOG_ERR, "Query failed:%s", mysql_error(&mysql) );
		exit( FAIL );
	}
	return	mysql_store_result(&mysql);
#endif
#ifdef	USE_POSTGRESQL
	PGresult	*result;

	syslog( LOG_DEBUG, "Executing query:%s\n",query);
	result = PQexec(conn,query);

	if( result==NULL)
	{
		syslog(LOG_ERR, "Query failed:%s", "Result is NULL" );
		exit( FAIL );
	}
	if( PQresultStatus(result) != PGRES_TUPLES_OK)
	{
		syslog(LOG_ERR, "Query failed:%s", PQresStatus(PQresultStatus(result)) );
		exit( FAIL );
	}
	return result;
#endif
}

char	*DBget_field(DB_RESULT *result, int rownum, int fieldnum)
{
#ifdef	USE_MYSQL
	MYSQL_ROW	row;

	mysql_data_seek(result, rownum);
	row=mysql_fetch_row(result);
	syslog(LOG_DEBUG, "Got field:%s", row[fieldnum] );
	return row[fieldnum];
#endif
#ifdef	USE_POSTGRESQL
	return PQgetvalue(result, rownum, fieldnum);
#endif
}

int	DBnum_rows(DB_RESULT *result)
{
#ifdef	USE_MYSQL
	return mysql_num_rows(result);
#endif
#ifdef	USE_POSTGRESQL
	return PQntuples(result);
#endif
}

int     DBget_function_result(float *Result,char *FunctionID)
{
	DB_RESULT *result;

        char	c[128];
        int	rows;

	sprintf( c, "select lastvalue from functions where functionid=%s", FunctionID );
	result = DBselect(c);

	if(result == NULL)
	{
        	DBfree_result(result);
		syslog(LOG_WARNING, "Query failed for functionid:[%s]", FunctionID );
		return FAIL;	
	}
        rows = DBnum_rows(result);
	if(rows == 0)
	{
        	DBfree_result(result);
		syslog(LOG_WARNING, "Query failed for functionid:[%s]", FunctionID );
		return FAIL;	
	}
        *Result=atof(DBget_field(result,0,0));
        DBfree_result(result);

        return SUCCEED;
}
