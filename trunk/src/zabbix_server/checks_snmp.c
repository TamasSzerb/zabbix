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

#include "checks_snmp.h"

#ifdef HAVE_SNMP
/*int	get_value_SNMP(int version,double *result,char *result_str,DB_ITEM *item)*/
int	get_value_snmp(double *result,char *result_str,DB_ITEM *item)
{

	#define NEW_APPROACH

	struct snmp_session session, *ss;
	struct snmp_pdu *pdu;
	struct snmp_pdu *response;

	#ifdef NEW_APPROACH
	char temp[MAX_STRING_LEN];
	#endif

	oid anOID[MAX_OID_LEN];
	size_t anOID_len = MAX_OID_LEN;

	struct variable_list *vars;
	int status;

	unsigned char *ip;

	int ret=SUCCEED;

	zabbix_log( LOG_LEVEL_DEBUG, "In get_value_SNMP()");

	assert((item->type == ITEM_TYPE_SNMPv1)||(item->type == ITEM_TYPE_SNMPv2c));
	assert((item->type == ITEM_TYPE_SNMPv1)||(item->type == ITEM_TYPE_SNMPv2c)||(item->type == ITEM_TYPE_SNMPv3));

	snmp_sess_init( &session );
/*	session.version = version;*/
	if(item->type == ITEM_TYPE_SNMPv1)
	{
		session.version = SNMP_VERSION_1;
	}
	else if(item->type == ITEM_TYPE_SNMPv2c)
	{
		session.version = SNMP_VERSION_2c;
	}
	else
	{
		zabbix_log( LOG_LEVEL_ERR, "Error in get_value_SNMP. Wrong item type [%d]. Must be SNMP.", item->type);
		return FAIL;
	}


	if(item->useip == 1)
	{
	#ifdef NEW_APPROACH
		snprintf(temp,sizeof(temp)-1,"%s:%d", item->ip, item->snmp_port);
		session.peername = temp;
	#else
		session.peername = item->ip;
	#endif
	}
	else
	{
	#ifdef NEW_APPROACH
		snprintf(temp, sizeof(temp)-1, "%s:%d", item->host, item->snmp_port);
		session.peername = temp;
	#else
		session.peername = item->host;
	#endif
	}

	if( (session.version == SNMP_VERSION_1) || (item->type == ITEM_TYPE_SNMPv2c))
	{
		session.community = item->snmp_community;
		session.community_len = strlen(session.community);
	}
	else if(session.version == SNMP_VERSION_3)
	{
		/* set the SNMPv3 user name */
		session.securityName = item->snmpv3_securityname;
		session.securityNameLen = strlen(session.securityName);

		/* set the security level to authenticated, but not encrypted */

		if(item->snmpv3_securitylevel == ITEM_SNMPV3_SECURITYLEVEL_NOAUTHNOPRIV)
		{
			session.securityLevel = SNMP_SEC_LEVEL_NOAUTH;
		}
		else if(item->snmpv3_securitylevel == ITEM_SNMPV3_SECURITYLEVEL_AUTHNOPRIV)
		{
			session.securityLevel = SNMP_SEC_LEVEL_AUTHNOPRIV;
		}
		else if(item->snmpv3_securitylevel == ITEM_SNMPV3_SECURITYLEVEL_AUTHPRIV)
		{
			session.securityLevel = SNMP_SEC_LEVEL_AUTHPRIV;
		}
		else
		{
			zabbix_log( LOG_LEVEL_ERR, "Unsupported SNMPv3 security level [%d]", item->snmpv3_securitylevel);
			return FAIL;
		}

		/* set the authentication method to MD5 */
		session.securityAuthProto = usmHMACMD5AuthProtocol;
		session.securityAuthProtoLen = sizeof(usmHMACMD5AuthProtocol)/sizeof(oid);
		session.securityAuthKeyLen = USM_AUTH_KU_LEN;

		/* set the authentication key to a MD5 hashed version of our
		passphrase "The UCD Demo Password" (which must be at least 8
		characters long) */

		/* Where item->snmpv3_privpassphrase has to be used? */
		if (generate_Ku(session.securityAuthProto,
				session.securityAuthProtoLen,
				(u_char *) item->snmpv3_authpassphrase, strlen(item->snmpv3_authpassphrase),
				session.securityAuthKey,
				&session.securityAuthKeyLen) != SNMPERR_SUCCESS)
		{
			zabbix_log( LOG_LEVEL_ERR, "Error generating Ku from authentication pass phrase.");
			return FAIL;
		}
	}
	else
	{
		zabbix_log( LOG_LEVEL_ERR, "Error in get_value_SNMP. Unsupported session.version [%d]", session.version);
		return FAIL;
	}

	zabbix_log( LOG_LEVEL_DEBUG, "SNMP [%s@%s:%d]",session.community, session.peername, session.remote_port);
	zabbix_log( LOG_LEVEL_DEBUG, "OID [%s]", item->snmp_oid);

	SOCK_STARTUP;
	ss = snmp_open(&session);

	if(ss == NULL)
	{
		SOCK_CLEANUP;
		zabbix_log( LOG_LEVEL_WARNING, "Error: snmp_open()");
		return FAIL;
	}
	zabbix_log( LOG_LEVEL_DEBUG, "In get_value_SNMP() 0.2");

	pdu = snmp_pdu_create(SNMP_MSG_GET);
	read_objid(item->snmp_oid, anOID, &anOID_len);

#if OTHER_METHODS
	get_node("sysDescr.0", anOID, &anOID_len);
	read_objid(".1.3.6.1.2.1.1.1.0", anOID, &anOID_len);
	read_objid("system.sysDescr.0", anOID, &anOID_len);
#endif

	snmp_add_null_var(pdu, anOID, anOID_len);
	zabbix_log( LOG_LEVEL_DEBUG, "In get_value_SNMP() 0.3");
  
	status = snmp_synch_response(ss, pdu, &response);
	zabbix_log( LOG_LEVEL_DEBUG, "Status send [%d]", status);
	zabbix_log( LOG_LEVEL_DEBUG, "In get_value_SNMP() 0.4");

	zabbix_log( LOG_LEVEL_DEBUG, "In get_value_SNMP() 1");

	if (status == STAT_SUCCESS && response->errstat == SNMP_ERR_NOERROR)
	{

	zabbix_log( LOG_LEVEL_DEBUG, "In get_value_SNMP() 2");
/*		for(vars = response->variables; vars; vars = vars->next_variable)
		{
			print_variable(vars->name, vars->name_length, vars);
		}*/

		for(vars = response->variables; vars; vars = vars->next_variable)
		{
			int count=1;
			zabbix_log( LOG_LEVEL_DEBUG, "AV loop()");

			if(	(vars->type == ASN_INTEGER) ||
				(vars->type == ASN_UINTEGER)||
				(vars->type == ASN_COUNTER) ||
				(vars->type == ASN_TIMETICKS) ||
				(vars->type == ASN_GAUGE)
			)
			{
				*result=(long)*vars->val.integer;
				/*
				 * This solves situation when large numbers are stored as negative values
				 * http://sourceforge.net/tracker/index.php?func=detail&aid=700145&group_id=23494&atid=378683
				 */ 
				/*sprintf(result_str,"%ld",(long)*vars->val.integer);*/
				snprintf(result_str,MAX_STRING_LEN-1,"%lu",(long)*vars->val.integer);
			}
			else if(vars->type == ASN_OCTET_STR)
			{
				memcpy(result_str,vars->val.string,vars->val_len);
				result_str[vars->val_len] = '\0';
				if(item->type == 0)
				{
					ret = NOTSUPPORTED;
				}
			}
			else if(vars->type == ASN_IPADDRESS)
			{
				ip = vars->val.string;
				snprintf(result_str,MAX_STRING_LEN-1,"%d.%d.%d.%d",ip[0],ip[1],ip[2],ip[3]);
				if(item->type == 0)
				{
					ret = NOTSUPPORTED;
				}
			}
			else
			{
				zabbix_log( LOG_LEVEL_WARNING,"value #%d has unknow type", count++);
				ret  = NOTSUPPORTED;
			}
		}
	}
	else
	{
		if (status == STAT_SUCCESS)
		{
			zabbix_log( LOG_LEVEL_WARNING, "Error in packet\nReason: %s\n",
				snmp_errstring(response->errstat));
			if(response->errstat == SNMP_ERR_NOSUCHNAME)
			{
				ret=NOTSUPPORTED;
			}
			else
			{
				ret=FAIL;
			}
		}
		else if(status == STAT_TIMEOUT)
		{
			zabbix_log( LOG_LEVEL_WARNING, "Timeout while connecting to [%s]",
					session.peername);
			snmp_sess_perror("snmpget", ss);
			ret = NETWORK_ERROR;
		}
		else
		{
			zabbix_log( LOG_LEVEL_WARNING, "Error [%d]",
					status);
			snmp_sess_perror("snmpget", ss);
			ret=FAIL;
		}
	}

	if (response)
	{
		snmp_free_pdu(response);
	}
	snmp_close(ss);

	SOCK_CLEANUP;
	return ret;
}
#endif
