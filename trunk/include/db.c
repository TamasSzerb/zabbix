#include <stdlib.h>
#include <stdio.h>
#include <syslog.h>

#include "db.h"
#include "common.h"

MYSQL mysql;

void    DBconnect( void )
{
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
}

void	DBexecute( char *query )
{
	syslog( LOG_DEBUG, "Executing query:%s\n",query);

	if( mysql_query(&mysql,query) != 0 )
	{
		syslog(LOG_ERR, "Query failed:%s", mysql_error(&mysql) );
		exit( FAIL );
	}
}

DB_RESULT *DBget_result( void  )
{
	return	mysql_store_result(&mysql);
}

DB_ROW	DBfetch_row(DB_RESULT *result)
{
	return mysql_fetch_row(result);	
}

int	DBnum_rows(DB_RESULT *result)
{
	return mysql_num_rows(result);
}

int     DBget_function_result(float *Result,char *FunctionID)
{
	DB_RESULT *result;
        DB_ROW	row;

        char	c[128];

	sprintf( c, "select lastvalue from functions where functionid=%s", FunctionID );
	DBexecute(c);

        result = DBget_result();
 
        row = DBfetch_row(result);
	if(row == NULL)
	{
        	DBfree_result(result);
		syslog(LOG_WARNING, "Query failed for functionid:[%s]", FunctionID );
		return FAIL;	
	}
        *Result=atof(row[0]);
        DBfree_result(result);

        return SUCCEED;
}
