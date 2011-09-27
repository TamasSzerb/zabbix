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
#include "../common/common.h"

static int	get_cpu_num()
{
#ifdef HAVE_FUNCTION_SYSCTL_HW_NCPU
	size_t	len;
	int	mib[] = {CTL_HW, HW_NCPU}, ncpu;

	len = sizeof(ncpu);

	if (-1 == sysctl(mib, 2, &ncpu, &len, NULL, 0))
		return -1;

	return ncpu;
#else
	return -1;
#endif
}

int	SYSTEM_CPU_NUM(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	char	tmp[16];
	int	cpu_num;

	if (1 < num_param(param))
		return SYSINFO_RET_FAIL;

	/* only "online" (default) for parameter "type" is supported */
	if (0 == get_param(param, 1, tmp, sizeof(tmp)) && '\0' != *tmp && 0 != strcmp(tmp, "online"))
		return SYSINFO_RET_FAIL;

	if (-1 == (cpu_num = get_cpu_num()))
		return SYSINFO_RET_FAIL;

	SET_UI64_RESULT(result, cpu_num);

	return SYSINFO_RET_OK;
}

int	SYSTEM_CPU_UTIL(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	char	tmp[16];
	int	cpu_num, state, mode;

	if (3 < num_param(param))
		return SYSINFO_RET_FAIL;

	if (0 != get_param(param, 1, tmp, sizeof(tmp)) || '\0' == *tmp || 0 == strcmp(tmp, "all"))
		cpu_num = 0;
	else if (1 > (cpu_num = atoi(tmp) + 1))
		return SYSINFO_RET_FAIL;

	if (0 != get_param(param, 2, tmp, sizeof(tmp)) || '\0' == *tmp || 0 == strcmp(tmp, "user"))
		state = ZBX_CPU_STATE_USER;
	else if (0 == strcmp(tmp, "nice"))
		state = ZBX_CPU_STATE_NICE;
	else if (0 == strcmp(tmp, "system"))
		state = ZBX_CPU_STATE_SYSTEM;
	else if (0 == strcmp(tmp, "idle"))
		state = ZBX_CPU_STATE_IDLE;
	else
		return SYSINFO_RET_FAIL;

	if (0 != get_param(param, 3, tmp, sizeof(tmp)) || '\0' == *tmp || 0 == strcmp(tmp, "avg1"))
		mode = ZBX_AVG1;
	else if (0 == strcmp(tmp, "avg5"))
		mode = ZBX_AVG5;
	else if (0 == strcmp(tmp, "avg15"))
		mode = ZBX_AVG15;
	else
		return SYSINFO_RET_FAIL;

	return get_cpustat(result, cpu_num, state, mode);
}

static double	get_system_load(int mode)
{
#if defined(HAVE_GETLOADAVG)
	double	load[3];

	if (mode >= getloadavg(load, 3))
		return -1;

	return load[mode];
#elif defined(HAVE_SYS_PSTAT_H)
	struct pst_dynamic	dyn;

	if (-1 == pstat_getdynamic(&dyn, sizeof(dyn), 1, 0))
		return -1;

	if (ZBX_AVG1 == mode)
		return dyn.psd_avg_1_min;

	if (ZBX_AVG5 == mode)
		return dyn.psd_avg_5_min;

	return dyn.psd_avg_15_min;
#elif defined(HAVE_PROC_LOADAVG)
	return getPROC("/proc/loadavg", 1, mode + 1);
#elif defined(HAVE_KSTAT_H)
	static const char	*keys[] =
	{
		"avenrun_1min",
		"avenrun_5min",
		"avenrun_15min"
	};

	static kstat_ctl_t	*kc = NULL;
	kstat_t			*ks;
	kstat_named_t		*kn;

	if (NULL == kc && NULL == (kc = kstat_open()))
		return -1;

	if (NULL == (ks = kstat_lookup(kc, "unix", 0, "system_misc")) || -1 == kstat_read(kc, ks, 0) ||
			NULL == (kn = kstat_data_lookup(ks, keys[mode])))
	{
		return -1;
	}

	return (double)kn->value.ul / 256;
#elif defined(HAVE_KNLIST_H)
	struct nlist	nl;
	int		kmem;
	long		load[3];

	nl.n_name = "avenrun";
	nl.n_value = 0;

	if (-1 == knlist(&nl, 1, sizeof(nl)))
		return -1;

	if (0 >= (kmem = open("/dev/kmem", 0, 0)))
		return -1;

	if (pread(kmem, load, sizeof(load), nl.n_value) < sizeof(load))
		return -1;

	return (double)load[mode] / 65535;
#else
	return -1;
#endif
}

int	SYSTEM_CPU_LOAD(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	char	tmp[16];
	int	mode, per_cpu = 1, cpu_num;
	double	value;

	if (2 < num_param(param))
		return SYSINFO_RET_FAIL;

	if (0 != get_param(param, 1, tmp, sizeof(tmp)) || '\0' == *tmp || 0 == strcmp(tmp, "all"))
		per_cpu = 0;
	else if (0 != strcmp(tmp, "percpu"))
		return SYSINFO_RET_FAIL;

	if (0 != get_param(param, 2, tmp, sizeof(tmp)) || '\0' == *tmp || 0 == strcmp(tmp, "avg1"))
		mode = ZBX_AVG1;
	else if (0 == strcmp(tmp, "avg5"))
		mode = ZBX_AVG5;
	else if (0 == strcmp(tmp, "avg15"))
		mode = ZBX_AVG15;
	else
		return SYSINFO_RET_FAIL;

	if (-1 == (value = get_system_load(mode)))
		return SYSINFO_RET_FAIL;

	if (1 == per_cpu)
	{
		if (0 >= (cpu_num = get_cpu_num()))
			return SYSINFO_RET_FAIL;
		value /= cpu_num;
	}

	SET_DBL_RESULT(result, value);

	return SYSINFO_RET_OK;
}
