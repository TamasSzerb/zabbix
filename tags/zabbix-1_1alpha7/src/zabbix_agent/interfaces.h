/* 
** Zabbix
** Copyright (C) 2000,2001,2002,2003,2004 Alexei Vladishev
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

#ifndef ZABBIX_INTERFACES_H
#define ZABBIX_INTERFACES_H

#define	MAX_INTERFACE	16

#define INTERFACE struct interface_type
INTERFACE
{
	char    *interface;
	int	clock[60*15];
	float	sent[60*15];
	float	received[60*15];
};

void	collect_stats_interfaces(FILE *outfile);

#endif
