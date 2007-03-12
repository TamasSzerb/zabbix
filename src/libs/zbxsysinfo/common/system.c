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

#include "common.h"
#include "sysinfo.h"

#include "system.h"

int	SYSTEM_LOCALTIME(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	assert(result);

        init_result(result);
	
	SET_UI64_RESULT(result, time(NULL));

	return SYSINFO_RET_OK;
}

int     SYSTEM_UNUM(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
#if defined(_WINDOWS)
#	ifdef TODO
#		error Realize function SYSTEM_UNUM!!!
#	endif /* todo */
	return SYSINFO_RET_FAIL;
#else
        assert(result);

        init_result(result);

        return EXECUTE_INT(cmd, "who|wc -l", flags, result);
#endif /* _WINDOWS */
}

int     SYSTEM_UNAME(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
#if defined(_WINDOWS)
#	ifdef TODO
#		error Realize function SYSTEM_UNAME!!!
#	endif /* todo */
	return SYSINFO_RET_FAIL;
#else
        assert(result);

        init_result(result);

        return EXECUTE_STR(cmd, "uname -a", flags, result);
#endif /* _WINDOWS */
}

int     SYSTEM_HOSTNAME(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
#if defined(_WINDOWS)
#	ifdef TODO
#		error Realize function SYSTEM_HOSTNAME!!!
#	endif /* todo */
	return SYSINFO_RET_FAIL;
#else
        assert(result);

        init_result(result);

        return EXECUTE_STR(cmd, "hostname", flags, result);
#endif /* _WINDOWS */
}
