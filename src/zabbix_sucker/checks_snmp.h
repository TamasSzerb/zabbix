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

#ifndef ZABBIX_CHECKS_SNMP_H
#define ZABBIX_CHECKS_SNMP_H

#include <string.h>
#include <stdio.h>
#include <stdlib.h>
#include <assert.h>

#include "common.h"
#include "config.h"
#include "log.h"
#include "db.h"

/* NET-SNMP is used */
#ifdef HAVE_NETSNMP
	#include <net-snmp/net-snmp-config.h>
	#include <net-snmp/net-snmp-includes.h>
#endif

/* Required for SNMP support*/
#ifdef HAVE_UCDSNMP
	#include <ucd-snmp/ucd-snmp-config.h>
	#include <ucd-snmp/ucd-snmp-includes.h>
	#include <ucd-snmp/system.h>
#endif


/*int	get_value_SNMP(int version,double *result,char *result_str,DB_ITEM *item);*/
int	get_value_snmp(double *result,char *result_str,DB_ITEM *item);

#endif
