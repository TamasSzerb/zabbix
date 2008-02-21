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

#ifndef ZABBIX_PINGER_H
#define ZABBIX_PINGER_H

#include "common.h"

extern	int	CONFIG_PINGER_FORKS;
extern	int	CONFIG_PINGER_FREQUENCY;
extern	char	*CONFIG_FPING_LOCATION;
#ifdef HAVE_IPV6
extern	char	*CONFIG_FPING6_LOCATION;
#endif /* HAVE_IPV6 */

void	main_pinger_loop(zbx_process_t p, int num);

#endif
