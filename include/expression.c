#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <math.h>
#include <syslog.h>

#include "common.h"
#include "db.h"

int	is_float(char *c)
{
	int i;

	syslog(LOG_DEBUG, "Starting IsFloat:%s", c );
	for(i=0;i<=strlen(c);i++)
	{
		if((c[i]=='(')||(c[i]==')')||(c[i]=='{')||(c[i]=='<')||(c[i]=='>')||(c[i]=='='))
		{
			return FAIL;
		}
	}
	return SUCCEED;
}

void	delete_spaces(char *c)
{
	int i,j;

	syslog( LOG_DEBUG, "Before deleting spaces:%s", c );

	j=0;
	for(i=0;i<strlen(c);i++)
	{
		if( c[i] != ' ')
		{
			c[j]=c[i];
			j++;
		}
	}
	c[j]=0;

	syslog(LOG_DEBUG, "After deleting spaces:%s", c );

}

int	find_char(char *str,char c)
{
	int i;

	syslog( LOG_DEBUG, "Before find_char:%s[%c]", str, c );

	for(i=0;i<strlen(str);i++)
	{
		if(str[i]==c) return i;
	}
	return	FAIL;
}

int	evaluate_simple (float *result,char *exp)
{
	float	value1,value2;
	char	first[1024],second[1024];
	int	i,j,l;

	syslog( LOG_DEBUG, "Evaluating simple expression [%s]", exp );

	if( is_float(exp) == SUCCEED )
	{
		*result=atof(exp);
		return SUCCEED;
	}

	if( find_char(exp,'|') != FAIL )
	{
		syslog( LOG_DEBUG, "| is found" );
		l=find_char(exp,'|');
		strcpy( first, exp );
		first[l]=0;
		j=0;
		for(i=l+1;i<strlen(exp);i++)
		{
			second[j]=exp[i];
			j++;
		}
		second[j]=0;
		if( evaluate_simple(&value1,first) == FAIL )
		{
			syslog(LOG_DEBUG, "Cannot evaluate expression [%s]", first );
			return FAIL;
		}
		if( value1 == 1)
		{
			*result=value1;
			return SUCCEED;
		}
		if( evaluate_simple(&value2,second) == FAIL )
		{
			syslog(LOG_DEBUG, "Cannot evaluate expression [%s]", second );
			return FAIL;
		}
		if( value2 == 1)
		{
			*result=value2;
			return SUCCEED;
		}
		*result=0;
		return SUCCEED;
	}
	else if( find_char(exp,'&') != FAIL )
	{
		syslog(LOG_DEBUG, "& is found" );
		l=find_char(exp,'&');
		strcpy( first, exp );
		first[l]=0;
		j=0;
		for(i=l+1;i<strlen(exp);i++)
		{
			second[j]=exp[i];
			j++;
		}
		second[j]=0;
		if( evaluate_simple(&value1,first) == FAIL )
		{
			syslog(LOG_WARNING, "Cannot evaluate expression [%s]", first );
			return FAIL;
		}
		if( evaluate_simple(&value2,second) == FAIL )
		{
			syslog(LOG_WARNING, "Cannot evaluate expression [%s]", second );
			return FAIL;
		}
		if( (value1 == 1) && (value2 == 1) )
		{
			*result=1;
		}
		else
		{
			*result=0;
		}
		return SUCCEED;
	}
	else if( find_char(exp,'>') != FAIL )
	{
		syslog(LOG_DEBUG, "> is found" );
		l=find_char(exp,'>');
		strcpy(first, exp);
		first[l]=0;
		j=0;
		for(i=l+1;i<strlen(exp);i++)
		{
			second[j]=exp[i];
			j++;
		}
		second[j]=0;
		if( evaluate_simple(&value1,first) == FAIL )
		{
			syslog(LOG_WARNING, "Cannot evaluate expression [%s]", first );
			return FAIL;
		}
		if( evaluate_simple(&value2,second) == FAIL )
		{
			syslog(LOG_WARNING, "Cannot evaluate expression [%s]", second );
			return FAIL;
		}
		if( value1 > value2 )
		{
			*result=1;
		}
		else
		{
			*result=0;
		}
		return SUCCEED;
	}
	else if( find_char(exp,'<') != FAIL )
	{
		syslog(LOG_DEBUG, "< is found" );
		l=find_char(exp,'<');
		strcpy(first, exp);
		first[l]=0;
		j=0;
		for(i=l+1;i<strlen(exp);i++)
		{
			second[j]=exp[i];
			j++;
		}
		second[j]=0;
		if( evaluate_simple(&value1,first) == FAIL )
		{
			syslog(LOG_WARNING, "Cannot evaluate expression [%s]", first );
			return FAIL;
		}
		if( evaluate_simple(&value2,second) == FAIL )
		{
			syslog(LOG_WARNING, "Cannot evaluate expression [%s]", second );
			return FAIL;
		}
		if( value1 < value2 )
		{
			*result=1;
		}
		else
		{
			*result=0;
		}
		return SUCCEED;
	}
	else
	{
			syslog( LOG_WARNING, "Format error or unsupported operator.  Exp: [%s]", exp );
			return FAIL;
	}
	return SUCCEED;
}

int	evaluate(int *result,char *exp)
{
	float	value;
	char	res[1024];
	char	simple[1024];
	int	i,l,r;

	strcpy( res,exp );

	while( find_char( exp, ')' ) != FAIL )
	{
		l=-1;
		r=find_char(exp,')');
		for(i=r;i>=0;i--)
		{
			if( exp[i] == '(' )
			{
				l=i;
				break;
			}
		}
		if( r == -1 )
		{
			syslog(LOG_WARNING, "Cannot find left bracket [(]. Expression:%s", exp );
			return	FAIL;
		}
		for(i=l+1;i<r;i++)
		{
			simple[i-l-1]=exp[i];
		} 
		simple[r-l-1]=0;

		if( evaluate_simple( &value, simple ) != SUCCEED )
		{
			syslog( LOG_WARNING, "Unable to evaluate simple expression [%s]", simple );
			return	FAIL;
		}

		syslog(LOG_DEBUG, "Expression1:%s", exp );

		exp[l]='%';
		exp[l+1]='f';
		exp[l+2]=' ';

		syslog(LOG_DEBUG, "Expression2:%s", exp );

		for(i=l+3;i<=r;i++) exp[i]=' ';

		syslog(LOG_DEBUG, "Expression3:%s", exp );

		sprintf(res,exp,value);
		strcpy(exp,res);
		delete_spaces(res);
		syslog(LOG_DEBUG, "Expression4:%s", res );
	}
	if( evaluate_simple( &value, res ) != SUCCEED )
	{
		syslog(LOG_WARNING, "Unable to evaluate simple expression [%s]", simple );
		return	FAIL;
	}
	syslog( LOG_DEBUG, "Evaluate end:[%f]", value );
	*result=value;
	return SUCCEED;
}

int	substitute_functions(char *exp)
{
	float	value;
	char	functionid[1024];
	char	res[1024];
	int	i,l,r;

	syslog(LOG_DEBUG, "BEGIN substitute_functions" );

	while( find_char(exp,'{') != FAIL )
	{
		l=find_char(exp,'{');
		r=find_char(exp,'}');
		if( r == FAIL )
		{
			syslog( LOG_WARNING, "Cannot find right bracket. Expression:%s", exp );
			return	FAIL;
		}
		if( r < l )
		{
			syslog( LOG_WARNING, "Right bracket is before left one. Expression:%s", exp );
			return	FAIL;
		}

		for(i=l+1;i<r;i++)
		{
			functionid[i-l-1]=exp[i];
		} 
		functionid[r-l-1]=0;

		if( DBget_function_result( &value, functionid ) != SUCCEED )
		{
			syslog( LOG_WARNING, "Unable to get value by functionid [%s]", functionid );
			return	FAIL;
		}

		syslog( LOG_DEBUG, "Expression1:%s", exp );

		exp[l]='%';
		exp[l+1]='f';
		exp[l+2]=' ';

		syslog( LOG_DEBUG, "Expression2:%s", exp );

		for(i=l+3;i<=r;i++) exp[i]=' ';

		syslog( LOG_DEBUG, "Expression3:%s", exp );

		sprintf(res,exp,value);
		strcpy(exp,res);
		delete_spaces(exp);
		syslog( LOG_DEBUG, "Expression4:%s", exp );
	}
	syslog( LOG_DEBUG, "Expression:%s", exp );
	syslog( LOG_DEBUG, "END substitute_functions" );
	return SUCCEED;
}

int	evaluate_expression (int *result,char *expression)
{
	delete_spaces(expression);
	if( substitute_functions(expression) == SUCCEED)
	{
		if( evaluate(result, expression) == SUCCEED)
		{
			return SUCCEED;
		}
	}
	return FAIL;
}
