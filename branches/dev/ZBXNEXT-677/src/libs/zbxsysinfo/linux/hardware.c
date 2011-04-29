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

#include "../common/common.h"
#include "sysinfo.h"
#include <sys/mman.h>
#include "hardware.h"

/******************************************************************************
 *                                                                            *
 * Comments: read the string #num from data into buf                          *
 *                                                                            *
 ******************************************************************************/
static size_t	get_dmi_string(char *buf, int bufsize, unsigned char *data, int num)
{
	char	*c = (char *)data;

	if (0 == num)
		return 0;

	c += data[1];	/* skip to string data */

	while (1 < num)
	{
		c += strlen(c);
		c++;
		num--;
	}

	return zbx_snprintf(buf, bufsize, " %s", c);
}

static size_t	get_chassis_type(char *buf, int bufsize, int type)
{
	type = CHASSIS_TYPE_BITS & type;

	if (1 > type || MAX_CHASSIS_TYPE < type)
		return 0;

	return zbx_snprintf(buf, bufsize, " %s", chassis_types[type]);
}

static int	get_dmi_info(char *buf, int bufsize, int flags)
{
	int			ret = SYSINFO_RET_FAIL, fd, offset = 0;
	unsigned char		membuf[SMBIOS_ENTRY_POINT_SIZE], *smbuf = NULL, *data;
	size_t			len, fp;
	void			*mmp = NULL;
	long			pagesize = sysconf(_SC_PAGESIZE);

	if (-1 == (fd = open(DEV_MEM, O_RDONLY)))
		return ret;

	if (SMBIOS_STATUS_UNKNOWN == smbios_status)	/* look for SMBIOS table only once */
	{
		/* find smbios entry point - located between 0xF0000 and 0xFFFFF (according to the specs) */
		for (fp = 0xf0000; 0xfffff > fp; fp += 16)
		{
			memset(membuf, 0, sizeof(membuf));

			len = fp % pagesize;	/* mmp needs to be a multiple of pagesize for munmap */
			if (MAP_FAILED == (mmp = mmap(0, len + SMBIOS_ENTRY_POINT_SIZE, PROT_READ, MAP_SHARED, fd, fp - len)))
				goto close;

			memcpy(membuf, mmp + len, sizeof(membuf));
			munmap(mmp, len + SMBIOS_ENTRY_POINT_SIZE);

			if (0 == strncmp((char *)membuf, "_DMI_", 5))	/* entry point found */
			{
				smbios_len = membuf[7] << 8 | membuf[6];
				smbios = membuf[11] << 24 | membuf[10] << 16 | membuf[9] << 8 | membuf[8];
				smbios_status = SMBIOS_STATUS_OK;
				break;
			}
		}
	}

	if (SMBIOS_STATUS_OK != smbios_status)
	{
		smbios_status = SMBIOS_STATUS_ERROR;
		goto close;
	}

	smbuf = zbx_malloc(smbuf, smbios_len);

	len = smbios % pagesize;	/* mmp needs to be a multiple of pagesize for munmap */
	if (MAP_FAILED == (mmp = mmap(0, len + smbios_len, PROT_READ, MAP_SHARED, fd, smbios - len)))
		goto clean;

	memcpy(smbuf, mmp + len, smbios_len);
	munmap(mmp, len + smbios_len);

	data = smbuf;
	while (data + DMI_HEADER_SIZE <= smbuf + smbios_len)
	{
		if (1 == data[0])	/* system information */
		{
			if (0 != (flags & DMI_GET_VENDOR))
				offset += get_dmi_string(buf + offset, bufsize - offset, data, data[4]);

			if (0 != (flags & DMI_GET_MODEL))
				offset += get_dmi_string(buf + offset, bufsize - offset, data, data[5]);

			if (0 != (flags & DMI_GET_SERIAL))
				offset += get_dmi_string(buf + offset, bufsize - offset, data, data[7]);

			if (0 != (flags & DMI_GET_TYPE))
				flags = DMI_GET_TYPE;
		}
		else if (3 == data[0] && 0 != (flags & DMI_GET_TYPE))	/* chassis */
		{
			offset += get_chassis_type(buf + offset, bufsize - offset, data[5]);
			flags &= ~DMI_GET_TYPE;
		}

		if (0 == flags)
			break;

		data += data[1];	/* skip the main data */
		while (data[0] || data[1])	/* string data ends with two nulls */
		{
			data++;
		}
		data += 2;
	}

	if (0 < offset)
		ret = SYSINFO_RET_OK;
clean:
	zbx_free(smbuf);
close:
	close(fd);

	return ret;
}

int	SYSTEM_HW_CHASSIS(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	char	tmp[8], buf[MAX_STRING_LEN];
	int	ret = SYSINFO_RET_FAIL;

	if (1 < num_param(param))
		return ret;

	if (0 != get_param(param, 1, tmp, sizeof(tmp)))
		*tmp = '\0';

	if ('\0' == *tmp || 0 == strcmp(tmp, "full"))	/* show full info by default */
		ret = get_dmi_info(buf, sizeof(buf), DMI_GET_TYPE | DMI_GET_VENDOR | DMI_GET_MODEL | DMI_GET_SERIAL);
	else if ('\0' == *tmp || 0 == strcmp(tmp, "type"))
		ret = get_dmi_info(buf, sizeof(buf), DMI_GET_TYPE);
	else if (0 == strcmp(tmp, "vendor"))
		ret = get_dmi_info(buf, sizeof(buf), DMI_GET_VENDOR);
	else if (0 == strcmp(tmp, "model"))
		ret = get_dmi_info(buf, sizeof(buf), DMI_GET_MODEL);
	else if (0 == strcmp(tmp, "serial"))
		ret = get_dmi_info(buf, sizeof(buf), DMI_GET_SERIAL);

	if (SYSINFO_RET_OK == ret)
		SET_STR_RESULT(result, zbx_strdup(NULL, buf + 1));	/* buf has a leading space */

	return ret;
}

static int	get_cpu_max_speed(int cpu_num)
{
	int			result = -1;
	char			filename[MAX_STRING_LEN];
	FILE			*f;

	zbx_snprintf(filename, sizeof(filename), CPU_MAX_FREQ_FILE, cpu_num);

	f = fopen(filename, "r");

	if (NULL != f)
	{
		if (1 != fscanf(f, "%d", &result))
			result = -1;

		fclose(f);
	}

	return result;
}

int     SYSTEM_HW_CPU(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	int		ret = SYSINFO_RET_FAIL, filter, val, cpu, cur_cpu = -2, offset = 0;
	char		line[MAX_STRING_LEN], name[MAX_STRING_LEN], tmp[MAX_STRING_LEN], buf[MAX_BUFFER_LEN];
	FILE		*f;

	if (2 < num_param(param))
		return ret;

	if (0 != get_param(param, 1, tmp, sizeof(tmp)) || '\0' == *tmp || 0 == strcmp(tmp, "all"))
		cpu = HW_CPU_ALL_CPUS;	/* show all CPUs by default */
	else if (FAIL == is_uint(tmp))
		return ret;
	else
		cpu = atoi(tmp);

	if (0 != get_param(param, 2, tmp, sizeof(tmp)) || '\0' == *tmp || 0 == strcmp(tmp, "full"))
		filter = HW_CPU_SHOW_ALL;	/* show full info by default */
	else if (0 == strcmp(tmp, "maxspeed"))
		filter = HW_CPU_SHOW_MAXSPEED;
	else if (0 == strcmp(tmp, "vendor"))
		filter = HW_CPU_SHOW_VENDOR;
	else if (0 == strcmp(tmp, "model"))
		filter = HW_CPU_SHOW_MODEL;
	else if (0 == strcmp(tmp, "curspeed"))
		filter = HW_CPU_SHOW_CURSPEED;
	else if (0 == strcmp(tmp, "cores"))
		filter = HW_CPU_SHOW_CORES;
	else
		return ret;

	if (NULL == (f = fopen(HW_CPU_FILE, "r")))
		return ret;

	*buf = '\0';

	while (NULL != fgets(line, sizeof(line), f))
	{
		if (2 != sscanf(line, "%[^:]: %[^\n]", name, tmp))
			continue;

		if (0 == strncmp(name, "processor", 9))
		{
			val = atoi(tmp);
			cur_cpu = val;

			if (HW_CPU_ALL_CPUS != cpu && cpu != cur_cpu)
				continue;

			if (HW_CPU_ALL_CPUS == cpu || HW_CPU_SHOW_ALL == filter)
				offset += zbx_snprintf(buf + offset, sizeof(buf) - offset, "\nprocessor %d:", cur_cpu);


			if ((HW_CPU_SHOW_ALL == filter || HW_CPU_SHOW_MAXSPEED == filter) &&
				-1 != (val = get_cpu_max_speed(cur_cpu)))
			{
				if (HW_CPU_SHOW_ALL != filter && HW_CPU_ALL_CPUS != cpu)
				{
					offset += zbx_snprintf(buf + offset, sizeof(buf) - offset, " %d", val);
					break;
				}

				offset += zbx_snprintf(buf + offset, sizeof(buf) - offset, " %dMHz", val / 1000);
			}
		}

		if (HW_CPU_ALL_CPUS != cpu && cpu != cur_cpu)
			continue;

		if (0 == strncmp(name, "vendor_id", 9) && (HW_CPU_SHOW_ALL == filter || HW_CPU_SHOW_VENDOR == filter))
		{
			offset += zbx_snprintf(buf + offset, sizeof(buf) - offset, " %s", tmp);
		}
		else if (0 == strncmp(name, "model name", 10) && (HW_CPU_SHOW_ALL == filter || HW_CPU_SHOW_MODEL == filter))
		{
			offset += zbx_snprintf(buf + offset, sizeof(buf) - offset, " %s", tmp);
		}
		else if (0 == strncmp(name, "cpu MHz", 7) && (HW_CPU_SHOW_ALL == filter || HW_CPU_SHOW_CURSPEED == filter))
		{
			if (HW_CPU_SHOW_ALL != filter && HW_CPU_ALL_CPUS != cpu)
			{
				offset += zbx_snprintf(buf + offset, sizeof(buf) - offset, " %d", atoi(tmp) * 1000);
				break;
			}

			offset += zbx_snprintf(buf + offset, sizeof(buf) - offset, " %dMHz", atoi(tmp));
		}
		else if (0 == strncmp(name, "cpu cores", 9) && (HW_CPU_SHOW_ALL == filter || HW_CPU_SHOW_CORES == filter))
		{
			offset += zbx_snprintf(buf + offset, sizeof(buf) - offset, " %s", tmp);
		}

	}
	zbx_fclose(f);

	if (0 < offset)
	{
		ret = SYSINFO_RET_OK;
		SET_TEXT_RESULT(result, zbx_strdup(NULL, buf + 1));	/* first symbol is a space or '\n' */
	}

	return ret;
}

int	SYSTEM_HW_DEVICES(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	char	tmp[MAX_STRING_LEN];

	if (1 < num_param(param))
		return SYSINFO_RET_FAIL;

	if (0 != get_param(param, 1, tmp, sizeof(tmp)) || '\0' == *tmp || 0 == strcmp(tmp, "pci"))
		return EXECUTE_STR(cmd, "lspci", flags, result);	/* list PCI devices by default */
	else if (0 == strcmp(tmp, "usb"))
		return EXECUTE_STR(cmd, "lsusb", flags, result);

	return SYSINFO_RET_FAIL;
}

int     SYSTEM_HW_MACADDR(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	int		ret = SYSINFO_RET_FAIL, offset = 0, s, i, show_names;
	char		tmp[MAX_STRING_LEN], regex[MAX_STRING_LEN], buf[MAX_STRING_LEN], buffer[MAX_STRING_LEN];
	struct ifreq	*ifr;
	struct ifconf	ifc;

	if (2 < num_param(param))
		return ret;

	if (0 != get_param(param, 1, regex, sizeof(regex)))
		*regex = '\0';

	if (0 != get_param(param, 2, tmp, sizeof(tmp)) || '\0' == *tmp || 0 == strcmp(tmp, "shownames"))
		show_names = 1;
	else if (0 == strcmp(tmp, "onlymacs"))
		show_names = 0;
	else
		return ret;

	if (-1 == (s = socket(AF_INET, SOCK_DGRAM, 0)))
		return ret;

	/* get the interface list */
	ifc.ifc_len = sizeof(buf);
	ifc.ifc_buf = buf;
	if (-1 == ioctl(s, SIOCGIFCONF, &ifc))
		return ret;
	ifr = ifc.ifc_req;

	/* go through the list */
	for (i = ifc.ifc_len / sizeof(struct ifreq); 0 < i--; ifr++)
	{
		if ('\0' != *regex && NULL == zbx_regexp_match(ifr->ifr_name, regex, NULL))
			continue;

		if (-1 != ioctl(s, SIOCGIFFLAGS, ifr) &&		/* get the interface */
				0 == (ifr->ifr_flags & IFF_LOOPBACK) &&	/* skip loopback interface */
				-1 != ioctl(s, SIOCGIFHWADDR, ifr))	/* get the MAC address */
		{
			if (1 == show_names)
				offset += zbx_snprintf(buffer + offset, sizeof(buffer) - offset, "%s: ", ifr->ifr_name);

			offset += zbx_snprintf(buffer + offset, sizeof(buffer) - offset, "%.2hx:%.2hx:%.2hx:%.2hx:%.2hx:%.2hx, ",
				(unsigned short int)(unsigned char)ifr->ifr_hwaddr.sa_data[0],
				(unsigned short int)(unsigned char)ifr->ifr_hwaddr.sa_data[1],
				(unsigned short int)(unsigned char)ifr->ifr_hwaddr.sa_data[2],
				(unsigned short int)(unsigned char)ifr->ifr_hwaddr.sa_data[3],
				(unsigned short int)(unsigned char)ifr->ifr_hwaddr.sa_data[4],
				(unsigned short int)(unsigned char)ifr->ifr_hwaddr.sa_data[5]);
		}
	}

	close(s);

	if (0 < offset)
	{
		zbx_rtrim(buffer, ", ");
		ret = SYSINFO_RET_OK;
		SET_STR_RESULT(result, zbx_strdup(NULL, buffer));
	}

	return ret;
}
