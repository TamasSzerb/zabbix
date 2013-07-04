/*
** Zabbix
** Copyright (C) 2001-2013 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

#include "common.h"
#include "sysinfo.h"
#include <stdio.h>

#define DO_ONE	0
#define DO_AVG	1
#define DO_MAX	2
#define DO_MIN	3

#if defined(KERNEL_2_4)
#define DEVICE_DIR	"/proc/sys/dev/sensors"
#else
#define DEVICE_DIR	"/sys/class/hwmon"
#define	EXTRA		"device"
#define ATTR_MAX	128
#endif

static char	*sysfs_read_attr(const char *device);
static void	get_device_sensors(int do_task, const char *device, const char *name, double *aggr, int *cnt);
int	get_device_info(const char *dev_path, const char *dev_name, char *device_info);

static void	count_sensor(int do_task, const char *filename, double *aggr, int *cnt)
{
	FILE	*f;
	char	line[MAX_STRING_LEN];
	double	value;

	if (NULL == (f = fopen(filename, "r")))
		return;

	if (NULL == fgets(line, sizeof(line), f))
	{
		zbx_fclose(f);
		return;
	}

	zbx_fclose(f);

#if defined(KERNEL_2_4)
	if (1 == sscanf(line, "%*f\t%*f\t%lf\n", &value)){
#else
	if (1 == sscanf(line, "%lf", &value)){
		if(NULL == strstr(filename, "fan"))
			value = value / 1000;
#endif

		(*cnt)++;

		switch (do_task)
		{
			case DO_ONE:
				*aggr = value;
				printf("DO_ONE\n");
				break;
			case DO_AVG:
				printf("DO_AVG\n");
				*aggr += value;
				break;
			case DO_MAX:
				printf("DO_MAX\n");
				*aggr = (1 == *cnt ? value : MAX(*aggr, value));
				break;
			case DO_MIN:
				printf("DO_MIN\n");
				*aggr = (1 == *cnt ? value : MIN(*aggr, value));
				break;
		}
	}
	printf("Value from file %s: %f, aggr: %f, cnt: %d\n", filename, value, *aggr, *cnt);
}

static void	get_device_sensors(int do_task, const char *device, const char *name, double *aggr, int *cnt)
{

#if defined(KERNEL_2_4)
	char	sensorname[MAX_STRING_LEN];

	if (DO_ONE == do_task)
	{
		zbx_snprintf(sensorname, sizeof(sensorname), "%s/%s/%s", DEVICE_DIR, device, name);
		count_sensor(do_task, sensorname, aggr, cnt);
	}
	else
	{
		DIR		*devicedir = NULL, *sensordir = NULL;
		struct dirent	*deviceent, *sensorent;
		char		devicename[MAX_STRING_LEN];

		if (NULL == (devicedir = opendir(DEVICE_DIR)))
			return;

		while (NULL != (deviceent = readdir(devicedir)))
		{
			if (0 == strcmp(deviceent->d_name, ".") || 0 == strcmp(deviceent->d_name, ".."))
				continue;

			if (NULL == zbx_regexp_match(deviceent->d_name, device, NULL))
				continue;

			zbx_snprintf(devicename, sizeof(devicename), "%s/%s", DEVICE_DIR, deviceent->d_name);

			if (NULL == (sensordir = opendir(devicename)))
				continue;

			while (NULL != (sensorent = readdir(sensordir)))
			{
				if (0 == strcmp(sensorent->d_name, ".") || 0 == strcmp(sensorent->d_name, ".."))
					continue;

				if (NULL == zbx_regexp_match(sensorent->d_name, name, NULL))
					continue;

				zbx_snprintf(sensorname, sizeof(sensorname), "%s/%s", devicename, sensorent->d_name);
				count_sensor(do_task, sensorname, aggr, cnt);
			}
			closedir(sensordir);
		}
		closedir(devicedir);
	}
#else
	DIR		*sensordir = NULL;
	struct dirent	*sensorent;
	struct		dirent* deviceent;
	char		sensorname[MAX_STRING_LEN];
	char		hwmon_dir[MAX_STRING_LEN];
	char		devicepath[MAX_STRING_LEN];
	char		deviced[MAX_STRING_LEN];
	char		device_info[MAX_STRING_LEN];
	int		dev_len;
	char		*device_p;
	int		err;

	zbx_snprintf(hwmon_dir, sizeof(hwmon_dir), "%s", DEVICE_DIR);

	DIR* devicedir = opendir(hwmon_dir);

	if (devicedir == NULL)
	{
		perror("opendir");
		return;
	}

	while(NULL != (deviceent = readdir(devicedir)))
	{
		if(strcmp(deviceent->d_name, ".") == 0 || strcmp(deviceent->d_name, "..") == 0)
			continue;

		zbx_snprintf(devicepath, sizeof(devicepath), "%s/%s/device", DEVICE_DIR, deviceent->d_name);
		dev_len = readlink(devicepath, deviced, MAX_STRING_LEN - 1);

		if (dev_len < 0)
		{
			/* No device link? Treat device as virtual */
			zbx_snprintf(devicepath, sizeof(devicepath), "%s/%s", DEVICE_DIR, deviceent->d_name);
			err = get_device_info(devicepath, NULL, device_info);
		} else
		{
			deviced[dev_len] = '\0';
			device_p = strrchr(deviced, '/') + 1;

			err = get_device_info(devicepath, device_p, device_info);
		}

		if(0 == err && NULL != zbx_regexp_match(device_info, device, NULL))
		{
			if (DO_ONE == do_task)
			{
				zbx_snprintf(sensorname, sizeof(sensorname), "%s/%s_input", devicepath, name);
				printf("Filename: %s\n", sensorname);
				count_sensor(do_task, sensorname, aggr, cnt);
			}
			else
			{
				if (NULL == (sensordir = opendir(devicepath)))
					return;

				while (NULL != (sensorent = readdir(sensordir)))
				{
					if (0 == strcmp(sensorent->d_name, ".") ||
							(0 == strcmp(sensorent->d_name, "..")))
						continue;

					if (NULL == zbx_regexp_match(sensorent->d_name, name, NULL))
						continue;

					zbx_snprintf(sensorname, sizeof(sensorname), "%s/%s", devicepath,
							sensorent->d_name);
					count_sensor(do_task, sensorname, aggr, cnt);
				}
				closedir(sensordir);
			}
		}
	}
	closedir(devicedir);
#endif
}

int	get_device_info(const char *dev_path, const char *dev_name, char *device_info)
{
	char		bus_path[MAX_STRING_LEN], linkpath[MAX_STRING_LEN], subsys_path[MAX_STRING_LEN];
	char		*subsys, *prefix;
	int		domain, bus, slot, fn, addr, vendor, product;
	short int	bus_spi, bus_i2c;
	char		*bus_attr;
	int		sub_len;

	/* ignore any device without name attribute */
	if (!(prefix = sysfs_read_attr(dev_path)))
		return -1;

	if (NULL == dev_name)
	{
		/* Virtual device */
		/* Assuming that virtual devices are unique */
		addr=0;
		zbx_snprintf(device_info, MAX_STRING_LEN, "%s-virtual-%x", prefix, addr);
		return 0;
	}

	/* Find bus type */
	zbx_snprintf(linkpath, MAX_STRING_LEN, "%s/subsystem", dev_path);

	sub_len = readlink(linkpath, subsys_path, MAX_STRING_LEN - 1);
	if (0 > sub_len && ENOENT == errno)
	{
		/* Fallback to "bus" link for kernels <= 2.6.17 */
		zbx_snprintf(linkpath, MAX_STRING_LEN, "%s/bus", dev_path);
		sub_len = readlink(linkpath, subsys_path, MAX_STRING_LEN - 1);
	}
	if (0 > sub_len)
	{
		/* Older kernels (<= 2.6.11) have neither the subsystem symlink nor the bus symlink */
		if (errno == ENOENT)
			subsys = NULL;
		else
			return -1;
	}
	else
	{
		subsys_path[sub_len] = '\0';
		subsys = strrchr(subsys_path, '/') + 1;
	}
	if ((!subsys || !strcmp(subsys, "i2c")) && sscanf(dev_name, "%hd-%x", &bus_i2c, &addr) == 2)
	{
		/* find out if legacy ISA or not */
		if (9191 == bus)
		{
			zbx_snprintf(device_info, MAX_STRING_LEN, "%s-isa-%04x", prefix, addr);
		}
		else
		{
			zbx_snprintf(bus_path, sizeof(bus_path), "/sys/class/i2c-adapter/i2c-%d/device", bus_i2c);

			if ((bus_attr = sysfs_read_attr(bus_path)))
			{
				if (!strncmp(bus_attr, "ISA ", 4))
				{
					zbx_snprintf(device_info, MAX_STRING_LEN, "%s-isa-%04x", prefix, addr);
				}

				free(bus_attr);
			}
			else zbx_snprintf(device_info, MAX_STRING_LEN, "%s-i2c-%hd-%02x", prefix, bus_i2c, addr);
		}
	}
	else if ((!subsys || !strcmp(subsys, "spi")) && sscanf(dev_name, "spi%hd.%d", &bus_spi, &addr) == 2)
	{
		/* SPI */
		zbx_snprintf(device_info, MAX_STRING_LEN, "%s-spi-%hd-%x", prefix, bus_spi, addr);
	}
	else if ((!subsys || !strcmp(subsys, "pci")) &&
			(sscanf(dev_name, "%x:%x:%x.%x", &domain, &bus, &slot, &fn) == 4))
	{
		/* PCI */
		addr = (domain << 16) + (bus << 8) + (slot << 3) + fn;
		zbx_snprintf(device_info, MAX_STRING_LEN, "%s-pci-%04x", prefix, addr);
	}
	else if ((!subsys || !strcmp(subsys, "platform") || !strcmp(subsys, "of_platform")))
	{
		/* must be new ISA (platform driver) */
		if (sscanf(dev_name, "%*[a-z0-9_].%d", &addr) != 1)
			addr = 0;
		zbx_snprintf(device_info, MAX_STRING_LEN, "%s-isa-%04x", prefix, addr);
	}
	else if (subsys && !strcmp(subsys, "acpi"))
	{
		/* Assuming that acpi devices are unique */
		addr = 0;
		zbx_snprintf(device_info, MAX_STRING_LEN, "%s-acpi-%x", prefix, addr);
	}
	else if (subsys && !strcmp(subsys, "hid") &&
			(sscanf(dev_name, "%x:%x:%x.%x", &bus, &vendor, &product, &addr) == 4))
	{
		/* As of kernel 2.6.32, the hid device names do not look good */
		zbx_snprintf(device_info, MAX_STRING_LEN, "%s-hid-%hd-%x", prefix, bus, addr);
	}
	else
	{
		/* Ignore unknown device */
		free(prefix);
		return -1;
	}
	free(prefix);

	return 0;
}

/*Read a name attribute of a sensor from sysfs*/
static char	*sysfs_read_attr(const char *device)
{
	char	path[MAX_STRING_LEN];
	char	buf[ATTR_MAX], *p;
	FILE	*f;

	zbx_snprintf(path, MAX_STRING_LEN, "%s/%s", device, "name");

	if (!(f = fopen(path, "r")))
		return NULL;
	p = fgets(buf, ATTR_MAX, f);
	fclose(f);
	if (!p)
		return NULL;

	/* Last byte is a '\n'; chop that off */
	p = strndup(buf, strlen(buf) - 1);
	if (!p)
		return NULL;

	return p;
}

int	GET_SENSOR(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*device, *name, *function;
	int	do_task, cnt = 0;
	double	aggr = 0;

	if (3 < request->nparam)
		return SYSINFO_RET_FAIL;

	device = get_rparam(request, 0);
	name = get_rparam(request, 1);
	function = get_rparam(request, 2);

	if (NULL == device || '\0' == *device)
		return SYSINFO_RET_FAIL;

	if (NULL == name || '\0' == *name)
		return SYSINFO_RET_FAIL;

	if (NULL == function || '\0' == *function)
		do_task = DO_ONE;
	else if (0 == strcmp(function, "avg"))
		do_task = DO_AVG;
	else if (0 == strcmp(function, "max"))
		do_task = DO_MAX;
	else if (0 == strcmp(function, "min"))
		do_task = DO_MIN;
	else
		return SYSINFO_RET_FAIL;

	get_device_sensors(do_task, device, name, &aggr, &cnt);

	printf("aggr: %f, cnt: %d\n", aggr, cnt);

	if (0 == cnt)
		return SYSINFO_RET_FAIL;

	if (DO_AVG == do_task)
		SET_DBL_RESULT(result, aggr / cnt);
	else
		SET_DBL_RESULT(result, aggr);

	return SYSINFO_RET_OK;
}
