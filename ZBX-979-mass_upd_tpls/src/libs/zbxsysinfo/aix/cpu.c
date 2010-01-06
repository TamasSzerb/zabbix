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
#include "stats.h"

int	SYSTEM_CPU_NUM(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
#if defined(HAVE_LIBPERFSTAT)
	char			mode[MAX_STRING_LEN];
	perfstat_cpu_total_t	ps_cpu_total;
#endif

	assert(result);

	init_result(result);

#if defined(HAVE_LIBPERFSTAT)
	if (num_param(param) > 1)
		return SYSINFO_RET_FAIL;

	if (0 != get_param(param, 1, mode, sizeof(mode)))
		*mode = '\0';

	/* default parameter */
	if (*mode == '\0')
		zbx_snprintf(mode, sizeof(mode), "online");

	if (0 != strcmp(mode, "online"))
		return SYSINFO_RET_FAIL;

	if (-1 == perfstat_cpu_total( NULL, &ps_cpu_total, sizeof(ps_cpu_total), 1))
		return SYSINFO_RET_FAIL;

	SET_UI64_RESULT(result, ps_cpu_total.ncpus);

	return SYSINFO_RET_OK;
#else
 	return SYSINFO_RET_FAIL;
#endif
}

int	SYSTEM_CPU_UTIL(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	char	tmp[32], type[32];
	int	cpu_num, mode;

        assert(result);

        init_result(result);

	if (!CPU_COLLECTOR_STARTED(collector))
	{
		SET_MSG_RESULT(result, strdup("Collector is not started!"));
		return SYSINFO_RET_OK;
	}

	if (num_param(param) > 3)
		return SYSINFO_RET_FAIL;

	if (0 != get_param(param, 1, tmp, sizeof(tmp)))
		*tmp = '\0';

	if ('\0' == *tmp || 0 == strcmp(tmp, "all"))	/* default parameter */
		cpu_num = 0;
	else
	{
		cpu_num = atoi(tmp) + 1;
		if (cpu_num < 1 || cpu_num > collector->cpus.count)
			return SYSINFO_RET_FAIL;
	}

	if (0 != get_param(param, 2, type, sizeof(type)))
		*type = '\0';

	if (0 != get_param(param, 3, tmp, sizeof(tmp)))
		*tmp = '\0';

	if ('\0' == *tmp || 0 == strcmp(tmp, "avg1"))   /* default parameter */
		mode = ZBX_AVG1;
	else if (0 == strcmp(tmp, "avg5"))
		mode = ZBX_AVG5;
	else if (0 == strcmp(tmp, "avg15"))
		mode = ZBX_AVG15;
	else
		return SYSINFO_RET_FAIL;

	if ('\0' == *type || 0 == strcmp(type, "user"))	/* default parameter */
		SET_DBL_RESULT(result, collector->cpus.cpu[cpu_num].user[mode])
	else if (0 == strcmp(type, "system"))
		SET_DBL_RESULT(result, collector->cpus.cpu[cpu_num].system[mode])
	else if (0 == strcmp(type, "idle"))
		SET_DBL_RESULT(result, collector->cpus.cpu[cpu_num].idle[mode])
	else if (0 == strcmp(type, "iowait"))
		SET_DBL_RESULT(result, collector->cpus.cpu[cpu_num].iowait[mode])
	else
		return SYSINFO_RET_FAIL;

	return SYSINFO_RET_OK;
}

static int	get_cpuload(double *load1, double *load5, double *load15)
{
#if defined(HAVE_LIBPERFSTAT)
	perfstat_cpu_total_t	ps_cpu_total;
#ifndef SBITS
#	define SBITS 16
#endif

	if (-1 == perfstat_cpu_total(NULL, &ps_cpu_total, sizeof(ps_cpu_total), 1))
		return SYSINFO_RET_FAIL;

	if (NULL != load1)
		*load1 = (double)ps_cpu_total.loadavg[0] / (1 << SBITS);
	if (NULL != load5)
		*load5 = (double)ps_cpu_total.loadavg[1] / (1 << SBITS);
	if (NULL != load15)
		*load15 = (double)ps_cpu_total.loadavg[2] / (1 << SBITS);

	return SYSINFO_RET_OK;
#else
	return SYSINFO_RET_FAIL;
#endif
}

int	SYSTEM_CPU_LOAD1(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	double	value;

	if (SYSINFO_RET_OK != get_cpuload(&value, NULL, NULL))
		return SYSINFO_RET_FAIL;

	SET_DBL_RESULT(result, value);

	return SYSINFO_RET_OK;
}

int	SYSTEM_CPU_LOAD5(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	double	value;

	if (SYSINFO_RET_OK != get_cpuload(NULL, &value, NULL))
		return SYSINFO_RET_FAIL;

	SET_DBL_RESULT(result, value);

	return SYSINFO_RET_OK;
}

int	SYSTEM_CPU_LOAD15(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	double	value;

	if (SYSINFO_RET_OK != get_cpuload(NULL, NULL, &value))
		return SYSINFO_RET_FAIL;

	SET_DBL_RESULT(result, value);

	return SYSINFO_RET_OK;
}

int	SYSTEM_CPU_LOAD(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{

#define CPU_FNCLIST struct cpu_fnclist_s
CPU_FNCLIST
{
	char *mode;
	int (*function)();
};

	CPU_FNCLIST fl[] =
	{
		{"avg1" ,	SYSTEM_CPU_LOAD1},
		{"avg5" ,	SYSTEM_CPU_LOAD5},
		{"avg15",	SYSTEM_CPU_LOAD15},
		{0,		0}
	};

	char	cpuname[MAX_STRING_LEN],
		mode[MAX_STRING_LEN];
	int	i;

        assert(result);

        init_result(result);

	if (num_param(param) > 2)
		return SYSINFO_RET_FAIL;

	if (0 != get_param(param, 1, cpuname, sizeof(cpuname)))
		*cpuname = '\0';

	/* default parameter */
	if (*cpuname == '\0')
		zbx_snprintf(cpuname, sizeof(cpuname), "all");

	if (0 != strcmp(cpuname, "all"))
		return SYSINFO_RET_FAIL;

	if (0 != get_param(param, 2, mode, sizeof(mode)))
		*mode = '\0';

	/* default parameter */
	if (*mode == '\0')
		zbx_snprintf(mode, sizeof(mode), "avg1");

	for (i = 0; fl[i].mode != 0; i++)
		if (0 == strncmp(mode, fl[i].mode, MAX_STRING_LEN))
			return (fl[i].function)(cmd, param, flags, result);

	return SYSINFO_RET_FAIL;
}

int     SYSTEM_CPU_SWITCHES(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
#if defined(HAVE_LIBPERFSTAT)
	perfstat_cpu_total_t	ps_cpu_total;
#endif

	assert(result);

	init_result(result);

#if defined(HAVE_LIBPERFSTAT)
	if (-1 == perfstat_cpu_total(NULL, &ps_cpu_total, sizeof(ps_cpu_total), 1))
		return SYSINFO_RET_FAIL;

	SET_UI64_RESULT(result, (zbx_uint64_t)ps_cpu_total.pswitch);

	return SYSINFO_RET_OK;
#else
	return SYSINFO_RET_FAIL;
#endif
}

int     SYSTEM_CPU_INTR(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
#if defined(HAVE_LIBPERFSTAT)
	perfstat_cpu_total_t	ps_cpu_total;
#endif

	assert(result);

	init_result(result);

#if defined(HAVE_LIBPERFSTAT)
	if (-1 == perfstat_cpu_total(NULL, &ps_cpu_total, sizeof(ps_cpu_total), 1))
		return SYSINFO_RET_FAIL;

	SET_UI64_RESULT(result, (zbx_uint64_t)(ps_cpu_total.devintrs + ps_cpu_total.softintrs));

	return SYSINFO_RET_OK;
#else
	return SYSINFO_RET_FAIL;
#endif
}
