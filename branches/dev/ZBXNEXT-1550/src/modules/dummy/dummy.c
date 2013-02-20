/*
** Zabbix
** Copyright (C) 2000-2013 Zabbix SIA
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
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

#include "sysinfo.h"
#include "module.h"

/* the variable keeps timeout setting for item processing */
static int item_timeout = 0;

int zbx_module_dummy_echo(AGENT_REQUEST *request, AGENT_RESULT *result);
int zbx_module_dummy_ping(AGENT_REQUEST *request, AGENT_RESULT *result);
int zbx_module_dummy_random(AGENT_REQUEST *request, AGENT_RESULT *result);

static ZBX_METRIC keys[] =
/*      KEY                     FLAG		FUNCTION        	TEST PARAMETERS */
{
	{"dummy.ping",		0,		zbx_module_dummy_ping,	0},
	{"dummy.echo",		CF_HAVEPARAMS,	zbx_module_dummy_echo, 	"a message"},
	{"dummy.random",	CF_HAVEPARAMS,	zbx_module_dummy_random,"1,1000"},
	{0}
};

/******************************************************************************
 *                                                                            *
 * Function: zbx_api_module_version                                           *
 *                                                                            *
 * Purpose: returns version number of the module interface                    *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value: ZBX_MODULE_API_VERSION_ONE - the only version supported by   *
 *               Zabbix currently                                             *
 *                                                                            *
 ******************************************************************************/
int zbx_module_api_version()
{
	return ZBX_MODULE_API_VERSION_ONE;
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_module_item_timeout                                          *
 *                                                                            *
 * Purpose: set timeout value for processing of items                         *
 *                                                                            *
 * Parameters: timeout - timeout in seconds, 0 - no timeout set               *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 ******************************************************************************/
void zbx_module_item_timeout(int timeout)
{
	item_timeout = timeout;
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_module_item_list                                             *
 *                                                                            *
 * Purpose: returns list of item keys supported by the module                 *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value: list of item keys                                            *
 *                                                                            *
 * Comment: item keys that accept optional parameters must have [*] included  *
 *                                                                            *
 ******************************************************************************/
ZBX_METRIC *zbx_module_item_list()
{
	return keys;
}

int zbx_module_dummy_ping(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	SET_UI64_RESULT(result, 1);

	return	SYSINFO_RET_OK;
}

int zbx_module_dummy_echo(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	if (request->nparam != 1)
	{
		/* set optional error message */
		SET_MSG_RESULT(result, strdup("Incorrect number of parameters, expected one parameter."));
		return SYSINFO_RET_FAIL;
	}

	SET_STR_RESULT(result, strdup(get_rparam(request,0)));

	return	SYSINFO_RET_OK;
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_module_dummy_random                                          *
 *                                                                            *
 * Purpose: a main entry point for processing of an item                      *
 *                                                                            *
 * Parameters: request - structure that contains item key and parameters      *
 *              request->key - item key without parameters                    *
 *              request->nparam - number of parameters                        *
 *              request->timeout - processing should not take longer than     *
 *                                 this number of seconds                     *
 *              request->params[N-1] - pointers to item key parameters        *
 *                                                                            *
 *             result - structure that will contain result                    *
 *                                                                            *
 * Return value: SYSINFO_RET_FAIL - function failed, item will be marked      *
 *                                 as not supported by zabbix                 *
 *               SYSINFO_RET_OK - success                                     *
 *                                                                            *
 * Comment: get_param(request, N-1) can be used to get a pointer to the Nth   *
 *          parameter starting from 0 (first parameter). Make sure it exists  *
 *          by checking value of request->nparam.                             *
 *                                                                            *
 ******************************************************************************/
int zbx_module_dummy_random(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	int	from, to;

	if (request->nparam != 2)
	{
		/* set optional error message */
		SET_MSG_RESULT(result, strdup("Incorrect number of parameters, expected two parameters."));
		return SYSINFO_RET_FAIL;
	}

	/* there is no strict validation of parameters for simplicity sake */
	from = atoi(get_rparam(request, 0));
	to = atoi(get_rparam(request, 1));

	if (from > to)
	{
		SET_MSG_RESULT(result, strdup("Incorrect range given."));
		return SYSINFO_RET_FAIL;
	}

	SET_UI64_RESULT(result, from + rand() % (to - from+1));

	return	SYSINFO_RET_OK;
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_module_init                                                  *
 *                                                                            *
 * Purpose: the function is called on agent startup                           *
 *          It should be used to call any initialization routines             *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value: ZBX_MODULE_OK - success                                      *
 *               ZBX_MODULE_FAIL - module initialization failed               *
 *                                                                            *
 * Comment: the module won't be loaded in case of ZBX_MODULE_FAIL             *
 *                                                                            *
 ******************************************************************************/
int zbx_module_init()
{
	/* Initialization for dummy.random */
	srand(time(NULL));

	return ZBX_MODULE_OK;
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_module_uninit                                                *
 *                                                                            *
 * Purpose: the function is called on agent shutdown                          *
 *          It should be used to cleanup used resources if there are any      *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value: ZBX_MODULE_OK - success                                      *
 *               ZBX_MODULE_FAIL - function failed                            *
 *                                                                            *
 ******************************************************************************/
int zbx_module_uninit()
{
	return ZBX_MODULE_OK;
}
