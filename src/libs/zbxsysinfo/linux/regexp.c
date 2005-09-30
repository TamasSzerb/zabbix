#include "config.h"

#include <stdio.h>
#include <regex.h>
#include <stdio.h>
#include <errno.h>
#include <stdlib.h>


#define MAX_FILE_LEN	1024*1024


char	*zbx_regexp_match(const char *string, const char *pattern, int *len)
{
	int	status;
	char	*c;

	regex_t	re;
	regmatch_t match;

	*len=0;


	if (regcomp(&re, pattern, REG_EXTENDED | REG_ICASE | REG_NEWLINE) != 0)
	{
		return(NULL);
	}


	status = regexec(&re, string, (size_t) 1, &match, 0);

	/* Not matched */
	if (status != 0)
	{
		regfree(&re);
		return(NULL);
	}

	c=string+match.rm_so;
	*len=match.rm_eo - match.rm_so;
	
	regfree(&re);

	return	c;
}
