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

#ifndef ZABBIX_PERFMON_H
#define ZABBIX_PERFMON_H

#ifdef _WINDOWS

#	define PCI_SYSTEM			(2)
#	define PCI_PROCESSOR			(238)
#	define PCI_PROCESSOR_TIME		(6)
#	define PCI_PROCESSOR_QUEUE_LENGTH	(44)
#	define PCI_SYSTEM_UP_TIME		(674)
#	define PCI_TERMINAL_SERVICES		(2176)
#	define PCI_TOTAL_SESSIONS		(2178)

	typedef enum
	{
		PERF_COUNTER_NOTSUPPORTED = 0,
		PERF_COUNTER_INITIALIZED,
		PERF_COUNTER_ACTIVE,
	};

	typedef struct perfcounter /* performance Countername structure */
	{
		struct perfcounter	*next;
		unsigned long		pdhIndex;
		TCHAR			name[PDH_MAX_COUNTER_NAME];
		/* must be character array for usage with sizeof function */
	}
	PERFCOUNTER;

	typedef struct zbx_perfs
	{
		struct zbx_perfs	*next;
		char			*name;
		char			*counterpath;
		int			interval;
		PDH_RAW_COUNTER		*rawValueArray;
		HCOUNTER		handle;
		int			CurrentCounter;
		int			CurrentNum;
		int			status;
	}
	PERF_COUNTERS;

	PDH_STATUS	zbx_PdhMakeCounterPath(const char *function, PDH_COUNTER_PATH_ELEMENTS *cpe, char *counterpath);
	PDH_STATUS	zbx_PdhOpenQuery(const char *function, PDH_HQUERY query);
	PDH_STATUS	zbx_PdhAddCounter(const char *function, PERF_COUNTERS *counter, PDH_HQUERY query, const char *counterpath, PDH_HCOUNTER *handle);
	PDH_STATUS	zbx_PdhCollectQueryData(const char *function, const char *counterpath, PDH_HQUERY query);
	PDH_STATUS	zbx_PdhGetRawCounterValue(const char *function, const char *counterpath, PDH_HCOUNTER handle, PPDH_RAW_COUNTER value);
	PDH_STATUS	calculate_counter_value(const char *function, const char *counterpath, DWORD dwFormat, PPDH_FMT_COUNTERVALUE value);
	LPTSTR		get_counter_name(DWORD pdhIndex);
	int		check_counter_path(char *counterPath);

#endif /* _WINDOWS */

#endif /* ZABBIX_PERFMON_H */
