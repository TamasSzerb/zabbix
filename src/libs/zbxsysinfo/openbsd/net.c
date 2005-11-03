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

#include "config.h"

#include "common.h"
#include "sysinfo.h"

static struct nlist kernel_symbols[] = 
{
	{"_ifnet", N_UNDF, 0, 0, 0},
	{"_tcbtable", N_UNDF, 0, 0, 0},
	{NULL, 0, 0, 0, 0}
};

#define IFNET_ID 0

static int get_ifdata(const char *device, struct if_data *result)
{
	struct ifnet_head	head;
	struct ifnet 		*ifp;
	
	char 	ifname[IFNAMSIZ+1];
	kvm_t 	*kp;
	int 	ret = SYSINFO_RET_FAIL;
	
	kp = kvm_open(NULL, NULL, NULL, O_RDONLY, NULL);

	if(kp)
	{
		if(kernel_symbols[IFNET_ID].n_type == N_UNDF)
		{
			if(kvm_nlist(kp, &kernel_symbols[0]) != 0)
			{
				kernel_symbols[IFNET_ID].n_type = N_UNDF;
			}
		}
		
		if(kernel_symbols[IFNET_ID].n_type != N_UNDF)
		{
			len = sizeof(struct ifnet_head);
			if(kvm_read(kp, kernel_symbols[IFNET_ID].n_value, &head, len) >= len)
			{
				len = sizeof(struct if_data);
				for(ifp = head.tqh_first; ifp; ifp = result->if_list.tqe_next)
				{
					if(kvm_read(kp, (u_long) ifp, result, len) < len)
						break;
					
					memcpy(
						ifname, 
						result->if_xname, 
						MIN(sizeof(ifname)- 1, IFNAMSIZ)
					);
					ifname[IFNAMSIZ] = '\0';

					if(strcmp(device, ifname) == 0)
					{
						ret = SYSINFO_RET_OK;
						break;
					}
				}
			}
		}
		kvm_close(kp);
	}

   return ret;
}

static int      NET_IF_IN_BYTES(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	struct if_data value;
	char    interface[MAX_STRING_LEN];
	int     ret = SYSINFO_RET_FAIL;

	assert(result);

	clean_result(result);

	if(num_param(param) > 1)
	{
		return SYSINFO_RET_FAIL;
	}

	if(get_param(param, 1, interface, MAX_STRING_LEN) != 0)
	{
		return SYSINFO_RET_FAIL;
	}

	ret = get_ifdata(interface, &value);
	
	if(ret == SYSINFO_RET_OK)
	{
		result->type |= AR_DOUBLE;
		result->dbl = value.if_ibytes;
		ret = SYSINFO_RET_OK;
	}
	
	return ret;
}

static int      NET_IF_IN_PACKETS(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	struct if_data value;
	char    interface[MAX_STRING_LEN];
	int     ret = SYSINFO_RET_FAIL;

	assert(result);

	clean_result(result);

	if(num_param(param) > 1)
	{
		return SYSINFO_RET_FAIL;
	}

	if(get_param(param, 1, interface, MAX_STRING_LEN) != 0)
	{
		return SYSINFO_RET_FAIL;
	}

	ret = get_ifdata(interface, &value);
	
	if(ret == SYSINFO_RET_OK)
	{
		result->type |= AR_DOUBLE;
		result->dbl = value.if_ipackets;
		ret = SYSINFO_RET_OK;
	}
	
	return ret;
}

static int      NET_IF_IN_ERRORS(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	struct if_data value;
	char    interface[MAX_STRING_LEN];
	int     ret = SYSINFO_RET_FAIL;

	assert(result);

	clean_result(result);

	if(num_param(param) > 1)
	{
		return SYSINFO_RET_FAIL;
	}

	if(get_param(param, 1, interface, MAX_STRING_LEN) != 0)
	{
		return SYSINFO_RET_FAIL;
	}

	ret = get_ifdata(interface, &value);
	
	if(ret == SYSINFO_RET_OK)
	{
		result->type |= AR_DOUBLE;
		result->dbl = value.if_ierrors;
		ret = SYSINFO_RET_OK;
	}
	
	return ret;
}

int	NET_IF_IN(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
LIST struct net_fnclist_s
NET_FNCLIST
{
        char *mode;
        int (*function)();
};

        NET_FNCLIST fl[] =
        {
                {"bytes",   NET_IF_IN_BYTES},
                {"packets", NET_IF_IN_PACKETS},
                {"errors",  NET_IF_IN_ERRORS},
                {0,         0}
        };

        char interface[MAX_STRING_LEN];
        char mode[MAX_STRING_LEN];
        int i;

        assert(result);

        clean_result(result);

        if(num_param(param) > 2)
        {
                return SYSINFO_RET_FAIL;
        }

        if(get_param(param, 1, interface, MAX_STRING_LEN) != 0)
        {
                return SYSINFO_RET_FAIL;
        }

        if(get_param(param, 2, mode, MAX_STRING_LEN) != 0)
        {
                mode[0] = '\0';
        }
        if(mode[0] == '\0')
        {
                /* default parameter */
                sprintf(mode, "bytes");
        }

        for(i=0; fl[i].mode!=0; i++)
        {
                if(strncmp(mode, fl[i].mode, MAX_STRING_LEN)==0)
                {
                        return (fl[i].function)(cmd, interface, flags, result);
                }
        }

        return SYSINFO_RET_FAIL;
}

static int      NET_IF_OUT_BYTES(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	struct if_data value;
	char    interface[MAX_STRING_LEN];
	int     ret = SYSINFO_RET_FAIL;

	assert(result);

	clean_result(result);

	if(num_param(param) > 1)
	{
		return SYSINFO_RET_FAIL;
	}

	if(get_param(param, 1, interface, MAX_STRING_LEN) != 0)
	{
		return SYSINFO_RET_FAIL;
	}

	ret = get_ifdata(interface, &value);
	
	if(ret == SYSINFO_RET_OK)
	{
		result->type |= AR_DOUBLE;
		result->dbl = value.if_obytes;
		ret = SYSINFO_RET_OK;
	}
	
	return ret;
}

static int      NET_IF_OUT_PACKETS(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	struct if_data value;
	char    interface[MAX_STRING_LEN];
	int     ret = SYSINFO_RET_FAIL;

	assert(result);

	clean_result(result);

	if(num_param(param) > 1)
	{
		return SYSINFO_RET_FAIL;
	}

	if(get_param(param, 1, interface, MAX_STRING_LEN) != 0)
	{
		return SYSINFO_RET_FAIL;
	}

	ret = get_ifdata(interface, &value);
	
	if(ret == SYSINFO_RET_OK)
	{
		result->type |= AR_DOUBLE;
		result->dbl = value.if_opackets;
		ret = SYSINFO_RET_OK;
	}
	
	return ret;
}

static int      NET_IF_OUT_ERRORS(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	struct if_data value;
	char    interface[MAX_STRING_LEN];
	int     ret = SYSINFO_RET_FAIL;

	assert(result);

	clean_result(result);

	if(num_param(param) > 1)
	{
		return SYSINFO_RET_FAIL;
	}

	if(get_param(param, 1, interface, MAX_STRING_LEN) != 0)
	{
		return SYSINFO_RET_FAIL;
	}

	ret = get_ifdata(interface, &value);
	
	if(ret == SYSINFO_RET_OK)
	{
		result->type |= AR_DOUBLE;
		result->dbl = value.if_oerrors;
		ret = SYSINFO_RET_OK;
	}
	
	return ret;
}

int	NET_IF_OUT(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
LIST struct net_fnclist_s
NET_FNCLIST
{
        char *mode;
        int (*function)();
};

        NET_FNCLIST fl[] =
        {
                {"bytes",   NET_IF_OUT_BYTES},
                {"packets", NET_IF_OUT_PACKETS},
                {"errors",  NET_IF_OUT_ERRORS},
                {0,         0}
        };

        char interface[MAX_STRING_LEN];
        char mode[MAX_STRING_LEN];
        int i;

        assert(result);

        clean_result(result);

        if(num_param(param) > 2)
        {
                return SYSINFO_RET_FAIL;
        }

        if(get_param(param, 1, interface, MAX_STRING_LEN) != 0)
        {
                return SYSINFO_RET_FAIL;
        }

        if(get_param(param, 2, mode, MAX_STRING_LEN) != 0)
        {
                mode[0] = '\0';
        }
        if(mode[0] == '\0')
        {
                /* default parameter */
                sprintf(mode, "bytes");
        }

        for(i=0; fl[i].mode!=0; i++)
        {
                if(strncmp(mode, fl[i].mode, MAX_STRING_LEN)==0)
                {
                        return (fl[i].function)(cmd, interface, flags, result);
                }
        }

        return SYSINFO_RET_FAIL;
}

static int      NET_IF_COLLISIONS(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	struct if_data value;
	char    interface[MAX_STRING_LEN];
	int     ret = SYSINFO_RET_FAIL;

	assert(result);

	clean_result(result);

	if(num_param(param) > 1)
	{
		return SYSINFO_RET_FAIL;
	}

	if(get_param(param, 1, interface, MAX_STRING_LEN) != 0)
	{
		return SYSINFO_RET_FAIL;
	}

	ret = get_ifdata(interface, &value);
	
	if(ret == SYSINFO_RET_OK)
	{
		result->type |= AR_DOUBLE;
		result->dbl = value.if_collisions;
		ret = SYSINFO_RET_OK;
	}
	
	return ret;
}

