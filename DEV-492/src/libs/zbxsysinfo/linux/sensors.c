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

static int	get_sensor(const char *name, unsigned flags, AGENT_RESULT *result)
{
	DIR		*dir;
	FILE		*f;
	struct dirent	*entries;
	struct stat	buf;
	char		filename[MAX_STRING_LEN];
	char		line[MAX_STRING_LEN];
	double		d1, d2, d3;

	if (NULL == (dir = opendir("/proc/sys/dev/sensors")))
		return SYSINFO_RET_FAIL;

	while (NULL != (entries = readdir(dir)))
	{
		strscpy(filename, "/proc/sys/dev/sensors/");
		zbx_strlcat(filename, entries->d_name, MAX_STRING_LEN);
		zbx_strlcat(filename, name, MAX_STRING_LEN);

		if (0 == stat(filename, &buf))
		{
			if (NULL == (f = fopen(filename, "r")))
				continue;

			if (NULL == fgets(line, MAX_STRING_LEN, f))
			{
				zbx_fclose(f);
				continue;
			}

			zbx_fclose(f);
			closedir(dir);

			if (3 == sscanf(line, "%lf\t%lf\t%lf\n", &d1, &d2, &d3))
			{
				SET_DBL_RESULT(result, d3);
				return SYSINFO_RET_OK;
			}
			else
				return SYSINFO_RET_FAIL;
		}
	}
	closedir(dir);

	return SYSINFO_RET_FAIL;
}

int	GET_SENSOR(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	char	key[MAX_STRING_LEN];
	int	ret;

	if (num_param(param) > 1)
		return SYSINFO_RET_FAIL;

	if (0 != get_param(param, 1, key, MAX_STRING_LEN))
		return SYSINFO_RET_FAIL;

	if (SUCCEED == str_in_list("temp1,temp2,temp3", key, ','))
		ret = get_sensor(key, flags, result);
	else
		ret = SYSINFO_RET_FAIL;

	return ret;
}
