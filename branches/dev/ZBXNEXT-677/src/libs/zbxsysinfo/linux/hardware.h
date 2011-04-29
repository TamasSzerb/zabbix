/*
** Zabbix
** Copyright (C) 2000-2011 Zabbix SIA
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

#ifndef ZABBIX_HARDWARE_H
#define ZABBIX_HARDWARE_H

#include "../common/common.h"
#include "sysinfo.h"
#include <sys/mman.h>
#include "hardware.h"

#define SMBIOS_STATUS_UNKNOWN	1	/* bits 0-6 represent the chassis type */
#define SMBIOS_STATUS_ERROR	2
#define SMBIOS_STATUS_OK	3

#define DEV_MEM			"/dev/mem"
#define SMBIOS_ENTRY_POINT_SIZE	0x20
#define DMI_HEADER_SIZE		4

#define CHASSIS_TYPE_BITS	0x7f	/* bits 0-6 represent the chassis type */
#define MAX_CHASSIS_TYPE	0x1d

#define DMI_GET_TYPE		0x01
#define DMI_GET_VENDOR		0x02
#define DMI_GET_MODEL		0x04
#define DMI_GET_SERIAL		0x08

#define CPU_MAX_FREQ_FILE	"/sys/devices/system/cpu/cpu%d/cpufreq/cpuinfo_max_freq"

#define HW_CPU_FILE		"/proc/cpuinfo"
#define HW_CPU_ALL_CPUS		-1
#define HW_CPU_SHOW_ALL		1
#define HW_CPU_SHOW_MAXSPEED	2
#define HW_CPU_SHOW_VENDOR	3
#define HW_CPU_SHOW_MODEL	4
#define HW_CPU_SHOW_CURSPEED	5
#define HW_CPU_SHOW_CORES	6

static int		smbios_status = SMBIOS_STATUS_UNKNOWN;
static size_t		smbios_len, smbios;	/* length and address of SMBIOS table (if found) */

/* from System Management BIOS (SMBIOS) Reference Specification v2.7.1 */
static const char	*chassis_types[] =
{
	"",			/* 0x00 */
	"Other",
	"Unknown",
	"Desktop",
	"Low Profile Desktop",
	"Pizza Box",
	"Mini Tower",
	"Tower",
	"Portable",
	"LapTop",
	"Notebook",
	"Hand Held",
	"Docking Station",
	"All in One",
	"Sub Notebook",
	"Space-saving",
	"Lunch Box",
	"Main Server Chassis",
	"Expansion Chassis",
	"SubChassis",
	"Bus Expansion Chassis",
	"Peripheral Chassis",
	"RAID Chassis",
	"Rack Mount Chassis",
	"Sealed-case PC",
	"Multi-system chassis",
	"Compact PCI",
	"Advanced TCA",
	"Blade",
	"Blade Enclosure",	/* 0x1d */
};

#endif	/* ZABBIX_HARDWARE_H */
