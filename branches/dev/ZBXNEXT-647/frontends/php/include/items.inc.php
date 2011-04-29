<?php
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
?>
<?php

/*
 * Function: get_item_logtype_description
 *
 * Description:
 *	 convert windows events type constant in to the string representation
 *
 * Author:
 *	 Alexander Vladishev
 *
 * Comments:
 *
 */
	function get_item_logtype_description($logtype){
		switch ($logtype){
			case ITEM_LOGTYPE_INFORMATION:		return S_INFORMATION;
			case ITEM_LOGTYPE_WARNING:		return S_WARNING;
			case ITEM_LOGTYPE_ERROR:		return S_ERROR;
			case ITEM_LOGTYPE_FAILURE_AUDIT:	return S_FAILURE_AUDIT;
			case ITEM_LOGTYPE_SUCCESS_AUDIT:	return S_SUCCESS_AUDIT;
			default:				return S_UNKNOWN;
		}
	}

/*
 * Function: get_item_logtype_style
 *
 * Description:
 *	 convert windows events type constant in to the CSS style name
 *
 * Author:
 *	 Alexander Vladishev
 *
 * Comments:
 *
 */
	function get_item_logtype_style($logtype){
		switch($logtype){
			case ITEM_LOGTYPE_INFORMATION:		return 'information';
			case ITEM_LOGTYPE_WARNING:		return 'warning';
			case ITEM_LOGTYPE_ERROR:		return 'high';
			case ITEM_LOGTYPE_FAILURE_AUDIT:	return 'high';
			case ITEM_LOGTYPE_SUCCESS_AUDIT:	return 'information';
			default:				return 'normal';
		}
	}

	function item_type2str($type=null){
		$types = array(
			ITEM_TYPE_ZABBIX => S_ZABBIX_AGENT,
			ITEM_TYPE_ZABBIX_ACTIVE => S_ZABBIX_AGENT_ACTIVE,
			ITEM_TYPE_SIMPLE => S_SIMPLE_CHECK,
			ITEM_TYPE_SNMPV1 => S_SNMPV1_AGENT,
			ITEM_TYPE_SNMPV2C => S_SNMPV2_AGENT,
			ITEM_TYPE_SNMPV3 => S_SNMPV3_AGENT,
			ITEM_TYPE_INTERNAL => S_ZABBIX_INTERNAL,
			ITEM_TYPE_TRAPPER => S_ZABBIX_TRAPPER,
			ITEM_TYPE_AGGREGATE => S_ZABBIX_AGGREGATE,
			ITEM_TYPE_EXTERNAL => S_EXTERNAL_CHECK,
			ITEM_TYPE_DB_MONITOR => S_ZABBIX_DATABASE_MONITOR,
			ITEM_TYPE_IPMI => S_IPMI_AGENT,
			ITEM_TYPE_SSH => S_SSH_AGENT,
			ITEM_TYPE_TELNET => S_TELNET_AGENT,
			ITEM_TYPE_JMX => S_JMX_AGENT,
			ITEM_TYPE_CALCULATED => S_CALCULATED,
			ITEM_TYPE_HTTPTEST => S_WEB_MONITORING,
		);

		if(is_null($type)){
			return $types;
		}
		else if(isset($types[$type]))
			return $types[$type];
		else
			return S_UNKNOWN;
	}

	function item_value_type2str($type=null){
		$types = array(
			ITEM_VALUE_TYPE_UINT64 => S_NUMERIC_UNSIGNED,
			ITEM_VALUE_TYPE_FLOAT => S_NUMERIC_FLOAT,
			ITEM_VALUE_TYPE_STR => S_CHARACTER,
			ITEM_VALUE_TYPE_LOG => S_LOG,
			ITEM_VALUE_TYPE_TEXT => S_TEXT,
		);

		if(is_null($type)){
			natsort($types);
			return $types;
		}
		else if(isset($types[$type]))
			return $types[$type];
		else
			return S_UNKNOWN;
	}

	function item_data_type2str($type=null){
		$types = array(
			ITEM_DATA_TYPE_BOOLEAN => S_BOOLEAN,
			ITEM_DATA_TYPE_OCTAL => S_OCTAL,
			ITEM_DATA_TYPE_DECIMAL => S_DECIMAL,
			ITEM_DATA_TYPE_HEXADECIMAL => S_HEXADECIMAL,
		);

		if(is_null($type)){
			return $types;
		}
		else if(isset($types[$type]))
			return $types[$type];
		else
			return S_UNKNOWN;
	}

	function item_status2str($type=null){
		$types = array(
			ITEM_STATUS_ACTIVE => S_ACTIVE,
			ITEM_STATUS_DISABLED => S_DISABLED,
			ITEM_STATUS_NOTSUPPORTED => S_NOT_SUPPORTED,
		);

		if(is_null($type)){
			natsort($types);
			return $types;
		}
		else if(isset($types[$type]))
			return $types[$type];
		else
			return S_UNKNOWN;
	}

	function item_status2style($status){
		switch($status){
			case ITEM_STATUS_ACTIVE:	$status = 'off';	break;
			case ITEM_STATUS_DISABLED:	$status = 'on';		break;
			case ITEM_STATUS_NOTSUPPORTED:
			default:
				$status = 'unknown';	break;
		}
	return $status;
	}

	function update_item_in_group($groupid,$itemid,$item){
/*		$description,$key,$hostid,$delay,$history,$status,$type,$snmp_community,$snmp_oid,$value_type,$trapper_hosts,$snmp_port,$units,$multiplier,$delta,$snmpv3_securityname,$snmpv3_securitylevel,$snmpv3_authpassphrase,$snmpv3_privpassphrase,$formula,$trends,$logtimefmt,$valuemapid,$delay_flex,$params,$ipmi_sensor,$applications)
//*/
		$sql='SELECT i.itemid,i.hostid '.
				' FROM hosts_groups hg,items i '.
				' WHERE hg.groupid='.$groupid.
					' and i.key_='.zbx_dbstr($item['key_']).
					' and hg.hostid=i.hostid';
		$result=DBSelect($sql);
		while($row=DBfetch($result)){
			$item['hostid'] = $row['hostid'];
			API::Item()->update($row['itemid'],$item);
		}
	return true;
	}

	function getInterfaceTypeByItem($item){
		if(!isset($item['type'])) return null;

		switch($item['type']){
			case ITEM_TYPE_SNMPV1: $type = INTERFACE_TYPE_SNMP; break;
			case ITEM_TYPE_SNMPV2C: $type = INTERFACE_TYPE_SNMP; break;
			case ITEM_TYPE_SNMPV3: $type = INTERFACE_TYPE_SNMP; break;
			case ITEM_TYPE_IPMI: $type = INTERFACE_TYPE_IPMI; break;
			case ITEM_TYPE_JMX: $type = INTERFACE_TYPE_JMX; break;
			case ITEM_TYPE_ZABBIX:
			default: $type = INTERFACE_TYPE_AGENT;
		}
	return $type;
	}

// Delete Item definition from selected group
	function delete_item_from_group($groupid,$itemid){
		if(!isset($itemid)){
			return 0;
		}

		$item=get_item_by_itemid($itemid);
		if(!$item){
			return 0;
		}

		$del_items = array();
		$sql='SELECT i.itemid '.
			' FROM hosts_groups hg,items i'.
			' WHERE hg.groupid='.$groupid.
				' AND i.key_='.zbx_dbstr($item["key_"]).
				' AND hg.hostid=i.hostid';
		$result=DBSelect($sql);
		while($row=DBfetch($result)){
			$del_items[$row['itemid']] = $row['itemid'];
		}
		if(!empty($del_items)){
			API::Item()->delete($del_items);
		}
	return 1;
	}

	# Add Item definition to selected group

	function add_item_to_group($groupid,$item){
/*	$description,$key,$hostid,$delay,$history,$status,$type,$snmp_community,$snmp_oid,$value_type,$trapper_hosts,$snmp_port,$units,$multiplier,$delta,$snmpv3_securityname,$snmpv3_securitylevel,$snmpv3_authpassphrase,$snmpv3_privpassphrase,$formula,$trends,$logtimefmt,$valuemapid,$delay_flex,$params,$ipmi_sensor,$applications)
//*/
		$sql='SELECT hostid FROM hosts_groups WHERE groupid='.$groupid;
		$result=DBSelect($sql);
		while($row=DBfetch($result)){
			$item['hostid'] = $row['hostid'];
			API::Item()->create($item);
		}
	return true;
	}


// Update Item status

	function update_item_status($itemids, $status){
		zbx_value2array($itemids);
		$result = true;

		$db_items = DBselect('SELECT * FROM items WHERE '.DBcondition('itemid',$itemids));
		while($row = DBfetch($db_items)){
			$old_status=$row['status'];

			if($status != $old_status){
/*				unset($itemids[$row['itemid']]);*/
				if ($status==ITEM_STATUS_ACTIVE)
					$sql='UPDATE items SET status='.$status.",error='' ".
						' WHERE itemid='.$row['itemid'];
				else
					$sql='UPDATE items SET status='.$status.
						' WHERE itemid='.$row['itemid'];

				$result &= DBexecute($sql);
				if ($result){
					$host=get_host_by_hostid($row['hostid']);
					$item_new = get_item_by_itemid($row['itemid']);
					add_audit_ext(AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_ITEM, $row['itemid'], $host['host'].':'.$row['name'], 'items', $row, $item_new);
				}
			}
		}
/*		if(!empty($itemids)){
			update_trigger_value_to_unknown_by_itemid($itemids);

			if($status==ITEM_STATUS_ACTIVE)
				$sql='UPDATE items SET status='.$status.",error='' ".
					' WHERE '.DBcondition('itemid',$itemids);
			else
				$sql='UPDATE items SET status='.$status.
					' WHERE '.DBcondition('itemid',$itemids);

			$result = DBexecute($sql);
		}*/

	return $result;
	}

	function copy_item_to_host($itemid, $hostid){
		$db_tmp_item = get_item_by_itemid_limited($itemid);
		$applications = get_same_applications_for_host(get_applications_by_itemid($db_tmp_item['itemid']),$hostid);

		$hosts = API::Host()->get(array(
			'output' => array('hostid', 'host', 'status'),
			'selectInterfaces' => API_OUTPUT_EXTEND,
			'hostids' => $hostid,
			'preservekeys' => 1,
			'nopermissions' => 1,
			'templated_hosts' => 1
		));
		if(empty($hosts)) return false;
		$host = reset($hosts);

		unset($db_tmp_item['interfaceid']);
		if($host['status'] != HOST_STATUS_TEMPLATE){
			$type = getInterfaceTypeByItem($db_tmp_item);
			foreach($host['interfaces'] as $hinum => $interface){
				if(($interface['type'] == $type) && ($interface['main'] == 1)){
					$db_tmp_item['interfaceid'] = $interface['interfaceid'];
				}
			}

			if(!isset($db_tmp_item['interfaceid'])){
				error('Cannot find needed host interface on ['.$host['host'].']');
				return false;
			}
		}

		$db_tmp_item['hostid'] = $hostid;
		$db_tmp_item['applications'] = $applications;
		$db_tmp_item['templateid'] = 0;

		$result = API::Item()->create($db_tmp_item);

	return $result;
}

	function copyItems($srcid, $destid){
		$result = true;

		$options = array(
			'hostids' => $srcid,
			'output' => API_OUTPUT_EXTEND,
			'inherited' => false,
			'filter' => array('flags' => ZBX_FLAG_DISCOVERY_NORMAL),
			'select_applications' => API_OUTPUT_REFER,
		);
		$srcItems = API::Item()->get($options);

		foreach($srcItems as $item){

			$hosts = API::Host()->get(array(
				'output' => array('hostid', 'host', 'status'),
				'selectInterfaces' => API_OUTPUT_EXTEND,
				'hostids' => $destid,
				'preservekeys' => 1,
				'nopermissions' => 1,
				'templated_hosts' => 1
			));
			$host = reset($hosts);

			unset($item['interfaceid']);
			if($host['status'] != HOST_STATUS_TEMPLATE){
				$type = getInterfaceTypeByItem($item);
				foreach($host['interfaces'] as $hinum => $interface){
					if(($interface['type'] == $type) && ($interface['main'] == 1)){
						$item['interfaceid'] = $interface['interfaceid'];
					}
				}

				if(!isset($item['interfaceid'])){
					error(_s('Item [%1$s:%2$s] cannot find interface on host [%3$s]', $item['name'], $item['key_'], $host['host']));
					return false;
				}
			}

			$item['hostid'] = $destid;
			$item['applications'] = get_same_applications_for_host(zbx_objectValues($item['applications'], 'applicationid'), $destid);
			$item['templateid'] = 0;

			$result = API::Item()->create($item);
			if(!$result) break;
		}

		return $result;
	}

	function copy_applications($srcid, $destid){
		$options = array(
			'hostids' => $srcid,
			'output' => API_OUTPUT_EXTEND,
			'inherited' => false,
		);
		$apps_to_clone = API::Application()->get($options);
		foreach($apps_to_clone as $num => $app){
			$app['hostid'] = $destid;
			unset($app['applicationid']);
			$apps_to_clone[$num] = $app;
		}

		return API::Application()->create($apps_to_clone);
	}


// Activate Item

	function activate_item($itemids){
		zbx_value2array($itemids);

// first update status for child items
		$chd_items = array();
		$db_tmp_items = DBselect('SELECT itemid, hostid FROM items WHERE '.DBcondition('templateid',$itemids));
		while($db_tmp_item = DBfetch($db_tmp_items)){
			$chd_items[$db_tmp_item['itemid']] = $db_tmp_item['itemid'];
		}
		if(!empty($chd_items)){
			activate_item($chd_items);  // Recursion !!!
		}

	return update_item_status($itemids, ITEM_STATUS_ACTIVE);
	}

// Disable Item
	function disable_item($itemids){
		zbx_value2array($itemids);

// first update status for child items
		$chd_items = array();
		$db_tmp_items = DBselect('SELECT itemid, hostid FROM items WHERE '.DBcondition('templateid',$itemids));
		while($db_tmp_item = DBfetch($db_tmp_items)){
			$chd_items[$db_tmp_item['itemid']] = $db_tmp_item['itemid'];
		}
		if(!empty($chd_items)){
			disable_item($chd_items);  // Recursion !!!
		}

		return update_item_status($itemids, ITEM_STATUS_DISABLED);
	}

	function get_items_by_hostid($hostids){
		zbx_value2array($hostids);
	return DBselect('SELECT * FROM items WHERE '.DBcondition('hostid',$hostids));
	}

	function get_item_by_key($key,$host=''){
		$item = false;

		$sql_from = '';
		$sql_where = '';
		if(!empty($host)){
			$sql_from = ',hosts h ';
			$sql_where = ' AND h.host='.zbx_dbstr($host).' AND i.hostid=h.hostid ';
		}
		$sql = 'SELECT DISTINCT i.* '.
			' FROM items i '.$sql_from.
			' WHERE i.key_='.zbx_dbstr($key).
				$sql_where;
		if($item = DBfetch(DBselect($sql))){
			$item = $item;
		}
	return $item;
	}

	function get_item_by_itemid($itemid){
		$row = DBfetch(DBselect('select * from items where itemid='.$itemid));
		if($row){
			return	$row;
		}
		error(S_NO_ITEM_WITH.SPACE.'itemid=['.$itemid.']');
	return	FALSE;
	}

	function get_item_by_itemid_limited($itemid){
		$sql = 'SELECT itemid,interfaceid,name,key_,hostid,delay,history,status,type,'.
					'snmp_community,snmp_oid,value_type,data_type,trapper_hosts,port,units,multiplier,delta,'.
					'snmpv3_securityname,snmpv3_securitylevel,snmpv3_authpassphrase,snmpv3_privpassphrase,'.
					'formula,trends,logtimefmt,valuemapid,delay_flex,params,ipmi_sensor,templateid,'.
					'authtype,username,password,publickey,privatekey,flags,filter,description,profile_link'.
			' FROM items '.
			' WHERE itemid='.$itemid;
		$row = DBfetch(DBselect($sql));
		if($row){
			return	$row;
		}
		error(S_NO_ITEM_WITH.SPACE.'itemid=['.$itemid.']');
	return	FALSE;
	}

/*
 * Function: get_same_items_for_host
 *
 * Description:
 *		Replace items for specified host
 *
 * Author:
 *		Aly
 *
 * Comments:
 *		$error= true : rise Error if item doesn't exist (error generated), false: special processing (NO error generated)
 */
	function get_same_item_for_host($item,$dest_hostids){
		$return_array = is_array($dest_hostids);
		zbx_value2array($dest_hostids);

		if(!is_array($item)){
			$itemid = $item;
		}
		else if(isset($item['itemid'])){
			$itemid = $item['itemid'];
		}

		$same_item = null;
		$same_items = array();
		if(isset($itemid)){
			$sql = 'SELECT src.* '.
							' FROM items src, items dest '.
							' WHERE dest.itemid='.$itemid.
								' AND src.key_=dest.key_ '.
								' AND '.DBcondition('src.hostid',$dest_hostids);

			$res = DBselect($sql);
			while($db_item = DBfetch($res)){
				if(is_array($item)){
					$same_item = $db_item;
					$same_items[$db_item['itemid']] = $db_item;
				}
				else{
					$same_item = $db_item['itemid'];
					$same_items[$db_item['itemid']] = $db_item['itemid'];
				}
			}

			if($return_array)
				return $same_items;
			else
				return $same_item;
		}
	return false;
	}


	function expand_item_key_by_data($item){
		$key =& $item['key_'];
		$macStack = array();

		$macros = array('{HOSTNAME}', '{IPADDRESS}', '{HOST.IP}', '{HOST.DNS}', '{HOST.CONN}', '{HOST.HOST}', '{HOST.NAME}');

		foreach($macros as $macro){
			$pos = 0;
			while($pos = zbx_strpos($key, $macro, $pos)){
				$pos++;
				$macStack[] = $macro;
			}
		}

		if(!empty($macStack)){
			$dbItems = API::Item()->get(array(
				'itemids' => $item['itemid'],
				'selectInterfaces' => array('ip', 'dns', 'useip'),
				'selectHosts' => array('host', 'name'),
				'output' => API_OUTPUT_REFER
			));
			$dbItem = reset($dbItems);

			$host = reset($dbItem['hosts']);
			$interface = reset($dbItem['interfaces']);

			foreach($macStack as $macro){
				switch($macro){
					case '{HOST.NAME}':
						$key = str_replace('{HOST.NAME}', $host['name'], $key);
						break;
					case '{HOSTNAME}':	/* deprecated */
						$key = str_replace('{HOSTNAME}', $host['name'], $key);
						break;
					case '{HOST.HOST}':
						$key = str_replace('{HOST.HOST}', $host['host'], $key);
						break;
					case '{HOST.IP}':
						$key = str_replace('{HOST.IP}', $interface['ip'], $key);
						break;
					case '{IPADDRESS}':	/* deprecated */
						$key = str_replace('{IPADDRESS}', $interface['ip'], $key);
						break;
					case '{HOST.DNS}':
						$key = str_replace('{HOST.DNS}', $interface['dns'], $key);
						break;
					case '{HOST.CONN}':
						$key = str_replace('{HOST.CONN}', $interface['useip'] ? $interface['ip'] : $interface['dns'], $key);
						break;
				}
			}
		}

		$item = API::UserMacro()->resolveItem($item);

	return $item['key_'];
	}

	/**
	 * Expand macros inside key name and return it
	 * Example:
	 *   key: 'test.key[a, b, "{HOSTNAME}"]'
	 *   name: 'Test item $1, $2, $3'
	 *   result: 'Test item a, b, Zabbix-server'
	 *
	 * @author Konstantin Buravcov
	 * @see ZBX-3503
	 * @param array $item
	 * @return string
	 */
	function itemName($item){
		$name = $item['name'];
		// if item name contains $1..$9 macros, we need to expand them
		if(preg_match('/\$[1-9]/', $name)){
			$key = expand_item_key_by_data($item);

			// parsing key to get the parameters out of it
			$ItemKey = new cItemKey($key);

			if($ItemKey->isValid()){
				$keyParameters = $ItemKey->getParameters();
				// according to zabbix docs we must replace $1 to $9 macros with item key parameters
				for($paramNo = 9; $paramNo > 0; $paramNo--){
					$replaceTo = isset($keyParameters[$paramNo - 1]) ? $keyParameters[$paramNo - 1] : '';
					$name = str_replace('$'.$paramNo, $replaceTo, $name);
				}
			}
		}

		return $name;
	}

	function get_realhost_by_itemid($itemid){
		$item = get_item_by_itemid($itemid);

		if($item['templateid'] <> 0)
			return get_realhost_by_itemid($item['templateid']);

	return get_host_by_itemid($itemid);
	}

// kostilek //
	function get_realrule_by_itemid_and_hostid($itemid, $hostid){
		$item = get_item_by_itemid($itemid);
		if(bccomp($hostid,$item['hostid']) == 0)
			return $item['itemid'];

		if($item['templateid'] <> 0)
			return get_realrule_by_itemid_and_hostid($item['templateid'], $hostid);

		return $item['itemid'];
	}

/*
 * Function: get_items_data_overview
 *
 * Description:
 *     Retrieve overview table object for items
 *
 * Author:
 *     Eugene Grigorjev (eugene.grigorjev@zabbix.com)
 *
 * Comments:
 *
 */
	function get_items_data_overview($hostids,$view_style=null){
		global $USER_DETAILS;

		if(is_null($view_style)) $view_style = CProfile::get('web.overview.view.style',STYLE_TOP);

		$table = new CTableInfo(S_NO_ITEMS_DEFINED);

// COpt::profiling_start('prepare_data');
		$result = DBselect('SELECT DISTINCT h.hostid, h.name as hostname,i.itemid, i.key_, i.value_type, i.lastvalue, i.units, i.lastclock, '.
				' i.name, t.priority, i.valuemapid, t.value as tr_value, t.triggerid '.
			' FROM hosts h, items i '.
				' LEFT JOIN functions f on f.itemid=i.itemid '.
				' LEFT JOIN triggers t on t.triggerid=f.triggerid and t.status='.TRIGGER_STATUS_ENABLED.
			' WHERE '.DBcondition('h.hostid',$hostids).
				' AND h.status='.HOST_STATUS_MONITORED.
				' AND h.hostid=i.hostid '.
				' AND i.status='.ITEM_STATUS_ACTIVE.
				' AND '.DBcondition('i.flags', array(ZBX_FLAG_DISCOVERY_NORMAL, ZBX_FLAG_DISCOVERY_CREATED)).
			' ORDER BY i.name,i.itemid');

		unset($items);
		unset($hosts);
// get rid of warnings about $triggers undefined
		$items = array();
		while($row = DBfetch($result)){
			$descr = itemName($row);
			$row['hostname'] = get_node_name_by_elid($row['hostid'], null, ': ').$row['hostname'];
			$hosts[zbx_strtolower($row['hostname'])] = $row['hostname'];

// A little tricky check for attempt to overwrite active trigger (value=1) with
// inactive or active trigger with lower priority.
			if (!isset($items[$descr][$row['hostname']]) ||
				(
					(($items[$descr][$row['hostname']]['tr_value'] == TRIGGER_VALUE_FALSE) && ($row['tr_value'] == TRIGGER_VALUE_TRUE)) ||
					(
						(($items[$descr][$row['hostname']]['tr_value'] == TRIGGER_VALUE_FALSE) || ($row['tr_value'] == TRIGGER_VALUE_TRUE)) &&
						($row['priority'] > $items[$descr][$row['hostname']]['severity'])
					)
				)
			)
			{
				$items[$descr][$row['hostname']] = array(
					'itemid'	=> $row['itemid'],
					'value_type'=> $row['value_type'],
					'lastvalue'	=> $row['lastvalue'],
					'lastclock'	=> $row['lastclock'],
					'units'		=> $row['units'],
					'name'=> $row['name'],
					'valuemapid' => $row['valuemapid'],
					'severity'	=> $row['priority'],
					'tr_value'	=> $row['tr_value'],
					'triggerid'	=> $row['triggerid']
				);
			}
		}

		if(!isset($hosts)){
			return $table;
		}

		ksort($hosts, SORT_STRING);
// COpt::profiling_stop('prepare_data');
// COpt::profiling_start('prepare_table');

		$css = getUserTheme($USER_DETAILS);
		if($view_style == STYLE_TOP){
			$header=array(new CCol(S_ITEMS,'center'));
			foreach($hosts as $hostname){
				$header = array_merge($header,array(new CImg('vtext.php?text='.$hostname.'&theme='.$css)));
			}

			$table->SetHeader($header,'vertical_header');

			foreach($items as $descr => $ithosts){
				$table_row = array(nbsp($descr));
				foreach($hosts as $hostname){
					$table_row = get_item_data_overview_cells($table_row,$ithosts,$hostname);
				}
				$table->AddRow($table_row);
			}
		}
		else{
			$header=array(new CCol(S_HOSTS,'center'));
			foreach($items as $descr => $ithosts){
				$header = array_merge($header,array(new CImg('vtext.php?text='.$descr.'&theme='.$css)));
			}

			$table->SetHeader($header,'vertical_header');

			foreach($hosts as $hostname){
				$table_row = array(nbsp($hostname));
				foreach($items as $descr => $ithosts){
					$table_row = get_item_data_overview_cells($table_row,$ithosts,$hostname);
				}
				$table->AddRow($table_row);
			}
		}
// COpt::profiling_stop('prepare_table');

	return $table;
	}

	function get_item_data_overview_cells(&$table_row,&$ithosts,$hostname){
		$css_class = '';
		unset($it_ov_menu);

		$value = '-';
		$ack = null;
		if(isset($ithosts[$hostname])){
			if($ithosts[$hostname]['tr_value'] == TRIGGER_VALUE_TRUE){
				$css_class = getSeverityStyle($ithosts[$hostname]['severity']);
				$ack = get_last_event_by_triggerid($ithosts[$hostname]['triggerid']);
				if ( 1 == $ack['acknowledged'] )
					$ack = array(SPACE, new CImg('images/general/tick.png','ack'));
				else
					$ack = null;
			}

			$value = format_lastvalue($ithosts[$hostname]);

			$it_ov_menu = array(
				array(S_VALUES,	null, null,
					array('outer'=> array('pum_oheader'), 'inner'=>array('pum_iheader'))),
				array(S_500_LATEST_VALUES, 'history.php?action=showlatest&itemid='.$ithosts[$hostname]['itemid'],
					array('tw'=>'_blank'))
				);

			switch($ithosts[$hostname]['value_type']){
				case ITEM_VALUE_TYPE_UINT64:
				case ITEM_VALUE_TYPE_FLOAT:
					$it_ov_menu = array_merge(array(
						/* name, url, (target [tw], statusbar [sb]), css, submenu */
						array(S_GRAPHS, null,  null,
							array('outer'=> array('pum_oheader'), 'inner'=>array('pum_iheader'))
							),
						array(S_LAST_HOUR_GRAPH, 'history.php?period=3600&action=showgraph&itemid='.
							$ithosts[$hostname]['itemid'], array('tw'=>'_blank')),
						array(S_LAST_WEEK_GRAPH, 'history.php?period=604800&action=showgraph&itemid='.
							$ithosts[$hostname]['itemid'], array('tw'=>'_blank')),
						array(S_LAST_MONTH_GRAPH, 'history.php?period=2678400&action=showgraph&itemid='.
							$ithosts[$hostname]['itemid'], array('tw'=>'_blank'))
						), $it_ov_menu);
					break;
				default:
					break;
			}
		}

		if($value != '-')	$value = new CSpan($value,'link');
		$value_col = new CCol(array($value,$ack),$css_class);

		if(isset($it_ov_menu)){
			$it_ov_menu  = new CPUMenu($it_ov_menu,170);
			$value_col->onClick($it_ov_menu->getOnActionJS());
			unset($it_ov_menu);
		}

		array_push($table_row,$value_col);
	return $table_row;
	}

	/******************************************************************************
	 *                                                                            *
	 * Comments: !!! Don't forget sync code with C !!!                            *
	 *                                                                            *
	 ******************************************************************************/
	function get_same_applications_for_host($applications, $hostid){
		$child_applications = array();

		foreach($applications as $appid){
			$db_apps = DBselect("select a1.applicationid from applications a1, applications a2".
					" where a1.name=a2.name and a1.hostid=".$hostid." and a2.applicationid=".$appid);
			$db_app = DBfetch($db_apps);
			if(!$db_app) continue;
			array_push($child_applications,$db_app["applicationid"]);
		}
	return $child_applications;
	}

	/******************************************************************************
	 *                                                                            *
	 * Comments: !!! Don't forget sync code with C !!!                            *
	 *                                                                            *
	 ******************************************************************************/
	function get_applications_by_itemid($itemids, $field='applicationid'){
		zbx_value2array($itemids);

		$result = array();

		$db_applications = DBselect('SELECT DISTINCT app.'.$field.' as result '.
										' FROM applications app, items_applications ia '.
										' WHERE app.applicationid=ia.applicationid '.
											' AND '.DBcondition('ia.itemid',$itemids));
		while($db_application = DBfetch($db_applications))
			array_push($result,$db_application['result']);

	return $result;
	}

	/******************************************************************************
	 *                                                                            *
	 * Comments: !!! Don't forget sync code with C !!!                            *
	 *                                                                            *
	 ******************************************************************************/
	function delete_history_by_itemid($itemids, $use_housekeeper=0){
		zbx_value2array($itemids);

		$result = delete_trends_by_itemid($itemids,$use_housekeeper);
		if(!$result)	return $result;

		if($use_housekeeper){
			foreach($itemids as $id => $itemid){
				$housekeeperid = get_dbid('housekeeper','housekeeperid');
				$sql = 'INSERT INTO housekeeper (housekeeperid,tablename,field,value)'.
							" VALUES ($housekeeperid,'history_text','itemid',$itemid)";
				DBexecute($sql);

				$housekeeperid = get_dbid('housekeeper','housekeeperid');
				$sql = 'INSERT INTO housekeeper (housekeeperid,tablename,field,value)'.
							" VALUES ($housekeeperid,'history_log','itemid',$itemid)";
				DBexecute($sql);

				$housekeeperid = get_dbid('housekeeper','housekeeperid');
				$sql = 'INSERT INTO housekeeper (housekeeperid,tablename,field,value)'.
							" VALUES ($housekeeperid,'history_uint','itemid',$itemid)";
				DBexecute($sql);

				$housekeeperid = get_dbid('housekeeper','housekeeperid');
				$sql = 'INSERT INTO housekeeper (housekeeperid,tablename,field,value)'.
							" VALUES ($housekeeperid,'history_str','itemid',$itemid)";
				DBexecute($sql);

				$housekeeperid = get_dbid('housekeeper','housekeeperid');
				$sql = 'INSERT INTO housekeeper (housekeeperid,tablename,field,value)'.
							" VALUES ($housekeeperid,'history','itemid',$itemid)";
				DBexecute($sql);
			}
			return TRUE;
		}

		DBexecute('DELETE FROM history_text WHERE '.DBcondition('itemid',$itemids));
		DBexecute('DELETE FROM history_log WHERE '.DBcondition('itemid',$itemids));
		DBexecute('DELETE FROM history_uint WHERE '.DBcondition('itemid',$itemids));
		DBexecute('DELETE FROM history_str WHERE '.DBcondition('itemid',$itemids));
		DBexecute('DELETE FROM history WHERE '.DBcondition('itemid',$itemids));
	return TRUE;
	}

/******************************************************************************
 *                                                                            *
 * Comments: !!! Don't forget sync code with C !!!                            *
 *                                                                            *
 ******************************************************************************/
	function delete_trends_by_itemid($itemids, $use_housekeeper=0){
		zbx_value2array($itemids);

		if($use_housekeeper){
			foreach($itemids as $id => $itemid){
				$housekeeperid = get_dbid('housekeeper','housekeeperid');
				DBexecute('INSERT INTO housekeeper (housekeeperid,tablename,field,value)'.
					" VALUES ($housekeeperid, 'trends','itemid',$itemid)");
				$housekeeperid = get_dbid('housekeeper','housekeeperid');
				DBexecute('INSERT INTO housekeeper (housekeeperid,tablename,field,value)'.
					" VALUES ($housekeeperid, 'trends_uint','itemid',$itemid)");
			}
			return TRUE;
		}
	return	DBexecute('DELETE FROM trends WHERE '.DBcondition('itemid',$itemids));
	}

	function format_lastvalue($db_item){
		if(!isset($db_item["lastvalue"]) || ($db_item["lastclock"] == 0)){
			return '-';
		}

		if(($db_item["value_type"] == ITEM_VALUE_TYPE_FLOAT) ||
				($db_item["value_type"] == ITEM_VALUE_TYPE_UINT64))
		{
			$lastvalue=convert_units($db_item["lastvalue"],$db_item["units"]);
		}
		else if($db_item["value_type"] == ITEM_VALUE_TYPE_STR ||
				$db_item["value_type"] == ITEM_VALUE_TYPE_TEXT ||
				$db_item["value_type"] == ITEM_VALUE_TYPE_LOG)
		{
			$lastvalue = $db_item["lastvalue"];
			if(zbx_strlen($lastvalue) > 20)
				$lastvalue = zbx_substr($lastvalue,0,20)." ...";
			$lastvalue = nbsp(htmlspecialchars($lastvalue));
		}
		else{
			$lastvalue=_('Unknown value type');
		}
		if($db_item["valuemapid"] > 0);
			$lastvalue = replace_value_by_map($lastvalue, $db_item["valuemapid"]);

	return $lastvalue;
	}

/*
 * Function: item_get_history
 *
 * Description:
 *     Get value from history
 *
 * Parameters:
 *     itemid - item ID
 *     last  - 0 - last value (clock is used), 1 - last value
 *
 * Author:
 *     Alexei Vladishev
 *
 * Comments:
 *
 */
	function item_get_history($db_item, $last = 1, $clock = 0, $ns = 0){
		$value = NULL;

		switch($db_item["value_type"]){
			case ITEM_VALUE_TYPE_FLOAT:
				$table = "history";
				break;
			case ITEM_VALUE_TYPE_UINT64:
				$table = "history_uint";
				break;
			case ITEM_VALUE_TYPE_TEXT:
				$table = "history_text";
				break;
			case ITEM_VALUE_TYPE_STR:
				$table = "history_str";
				break;
			case ITEM_VALUE_TYPE_LOG:
			default:
				$table = "history_log";
				break;
		}

		$config = select_config();

		if($last == 0){
			if (0 != $config['ns_support']){
				$sql = 'select value'.
					' from '.$table.
					' where itemid='.$db_item['itemid'].
						' and clock='.$clock.
						' and ns='.$ns;
				if(NULL != ($row = DBfetch(DBselect($sql, 1))))
					$value = $row["value"];

				if(NULL != $value)
					return $value;

				$max_clock = 0;

				$sql = 'select distinct clock'.
					' from '.$table.
					' where itemid='.$db_item['itemid'].
						' and clock='.$clock.
						' and ns<'.$ns;
				if(NULL != ($row = DBfetch(DBselect($sql))))
					$max_clock = $row['clock'];

				if(0 == $max_clock){
					$sql = 'select max(clock) as clock'.
						' from '.$table.
						' where itemid='.$db_item['itemid'].
							' and clock<'.$clock;
					if(NULL != ($row = DBfetch(DBselect($sql))))
						$max_clock = $row['clock'];
				}

				if (0 == $max_clock)
					return $value;

				if ($clock == $max_clock){
					$sql = 'select value'.
						' from '.$table.
						' where itemid='.$db_item['itemid'].
							' and clock='.$clock.
							' and ns<'.$ns;
				}
				else{
					$sql = 'select value'.
						' from '.$table.
						' where itemid='.$db_item['itemid'].
							' and clock='.$max_clock.
						' order by itemid,clock desc,ns desc';
				}

				if(NULL != ($row = DBfetch(DBselect($sql, 1))))
					$value = $row["value"];
			}
			else{
				$sql = 'select value from '.$table.' where itemid='.$db_item['itemid'].' and clock<='.$clock.
						' order by itemid,clock desc';
				if(NULL != ($row = DBfetch(DBselect($sql, 1))))
					$value = $row["value"];
			}
		}
		else{
			$sql = "select max(clock) as clock from $table where itemid=".$db_item["itemid"];
			$row = DBfetch(DBselect($sql));
			if($row && !is_null($row["clock"])){
				$clock = $row["clock"];
				if (0 != $config['ns_support']){
					$sql = "select value from $table where itemid=".$db_item["itemid"]." and clock=$clock order by ns desc";
				}
				else{
					$sql = "select value from $table where itemid=".$db_item["itemid"]." and clock=$clock";
				}
				$row = DBfetch(DBselect($sql, 1));
				if($row)
					$value = $row["value"];
			}
		}
	return $value;
	}

/*
 * Function: check_time_period
 *
 * Purpose: check if current time is within given period
 *
 * Parameters: period - [IN] time period in format [wd[-wd2],hh:mm-hh:mm]
 *             now    - [IN] timestamp for comparison
 *
 * Return value: 0 - out of period, 1 - within the period
 *
 * Author: Alexander Vladishev
 *
 * Comments:
 *        !!! Don't forget sync code with C !!!
 */
	function check_time_period($period, $now){
		$tm = localtime($now, true);
		$day = (0 == $tm['tm_wday']) ? 7 : $tm['tm_wday'];
		$sec = 3600 * $tm['tm_hour'] + 60 * $tm['tm_min'] + $tm['tm_sec'];

		$flag = (6 == sscanf($period, "%d-%d,%d:%d-%d:%d", $d1, $d2, $h1, $m1, $h2, $m2));

		if(!$flag){
			$flag = (5 == sscanf($period, "%d,%d:%d-%d:%d", $d1, $h1, $m1, $h2, $m2));
			$d2 = $d1;
		}

		if(!$flag){
			/* Delay period format is wrong - skip */;
		}
		else{
			if(($day >= $d1) &&
				($day <= $d2) &&
				($sec >= (3600*$h1+60*$m1)) &&
				($sec <= (3600*$h2+60*$m2)))
			{
				return true;
			}
		}

	return false;
	}

	function getItemDelay($delay, $flexIntervals){
		if(!empty($delay) || zbx_empty($flexIntervals)) return $delay;

		$minDelay = SEC_PER_YEAR;
		$flexIntervals = explode(';', $flexIntervals);
		foreach($flexIntervals as $fnum => $flexInterval){
			if(2 != sscanf($flexInterval, "%d/%29s", $flexDelay, $flexPeriod)) continue;

			$minDelay = min($minDelay, $flexDelay);
		}

	return $minDelay;
	}
/*
 * Function: get_current_delay
 *
 * Purpose: return delay value that is currently applicable
 *
 * Parameters: delay          - [IN] default delay
 *             flex_intervals - [IN] separated flexible intervals
 *
 *                                   +------------[;]<----------+
 *                                   |                          |
 *                                 ->+-[d/wd[-wd2],hh:mm-hh:mm]-+
 *
 *                                 d       - delay (0-n)
 *                                 wd, wd2 - day of week (1-7)
 *                                 hh      - hours (0-24)
 *                                 mm      - minutes (0-59)
 *
 *             now            - [IN] current time
 *
 * Return value: delay value - either default or minimum delay value
 *                             out of all applicable intervals
 *
 * Author: Alexander Vladishev
 */
	function get_current_delay($delay, $flex_intervals, $now){
		if(zbx_empty($flex_intervals)) return $delay;

		$current_delay = SEC_PER_YEAR;

		$arr_of_flex_intervals = explode(';', $flex_intervals);

		foreach($arr_of_flex_intervals as $fnum => $flex_interval){
			if(2 != sscanf($flex_interval, "%d/%29s", $flex_delay, $flex_period)) continue;

			if(($flex_delay < $current_delay) && check_time_period($flex_period, $now)){
				$current_delay = $flex_delay;
			}
		}

		if($current_delay == SEC_PER_YEAR) return $delay;

	return ($current_delay == 0) ? SEC_PER_YEAR : $current_delay;
	}

/*
 * Function: get_next_delay_interval
 *
 * Purpose: return time of next flexible interval
 *
 * Parameters: flex_intervals - [IN] separated flexible intervals
	 *
 *                                   +------------[;]<----------+
 *                                   |                          |
 *                                 ->+-[d/wd[-wd2],hh:mm-hh:mm]-+
	 *
 *                                 d       - delay (0-n)
 *                                 wd, wd2 - day of week (1-7)
 *                                 hh      - hours (0-24)
 *                                 mm      - minutes (0-59)
	 *
 *             now            - [IN] current time
 *
 * Return value: start of next interval
 *
 * Author: Alexei Vladishev, Alexander Vladishev
 */
	function get_next_delay_interval($flex_intervals, $now, &$next_interval){
		if(zbx_empty($flex_intervals)) return false;

		$next = 0;
		$tm = localtime($now, true);
		$day = (0 == $tm['tm_wday']) ? 7 : $tm['tm_wday'];
		$sec = 3600 * $tm['tm_hour'] + 60 * $tm['tm_min'] + $tm['tm_sec'];

		$arr_of_flex_intervals = explode(';', $flex_intervals);

		foreach($arr_of_flex_intervals as $flex_interval){
			if(7 != sscanf($flex_interval, "%d/%d-%d,%d:%d-%d:%d", $delay, $d1, $d2, $h1, $m1, $h2, $m2)){
				if(6 != sscanf($flex_interval, "%d/%d,%d:%d-%d:%d", $delay, $d1, $h1, $m1, $h2, $m2)) continue;

				$d2 = $d1;
			}

			$sec1 = 3600 * $h1 + 60 * $m1;
			$sec2 = 3600 * $h2 + 60 * $m2;

			if(($day >= $d1) && ($day <= $d2) && ($sec >= $sec1) && ($sec <= $sec2)){
// current period
				if(($next == 0) || ($next > ($now - $sec + $sec2)))	$next = $now - $sec + $sec2;
			}
			else if(($day >= $d1) && ($day <= $d2) && ($sec < $sec1)){
// will be active today
				if (($next == 0) || ($next > ($now - $sec + $sec1))) $next = $now - $sec + $sec1;
			}
			else{
				$next_day = (($day + 1 <= 7) ? ($day + 1) : 1);

				if(($next_day >= $d1) && ($next_day <= $d2)){
// will be active tomorrow
					if(($next == 0) || ($next > ($now - $sec + SEC_PER_DAY + $sec1)))
						$next = $now - $sec + SEC_PER_DAY + $sec1;
				}
				else{
					if($day < $d1) $day_diff = $d1 - $day;
					if($day >= $d2) $day_diff = ($d1 + 7) - $day;
					if(($day >= $d1) && ($day < $d2)){
// should never happen
// Could not deduce day difference
						$day_diff = -1;
					}

					if($day_diff != -1){
						if(($next == 0) || ($next > ($now - $sec + SEC_PER_DAY * $day_diff + $sec1)))
							$next = $now - $sec + SEC_PER_DAY * $day_diff + $sec1;
					}
				}
			}
		}

		if($next != 0) $next_interval = $next;

	return $next;
	}

/*
 * Function: calculate_item_nextcheck
 *
 * Description:
 *     calculate nextcheck timestamp for item
 *
 * Parameters:
 *     itemid - item ID
 *     item_type - item type
 *     delay - item's refresh rate in sec
 *     flex_intervals - item's flexible refresh rate
 *     now - current timestamp
 *
 * Author:
 *     Alexander Vladishev
 *
 * Comments:
 *     !!! Don't forget sync code with C !!!
 */
	function calculate_item_nextcheck($interfaceid, $itemid, $item_type, $delay, $flex_intervals, $now){
		if(0 == $delay) $delay = SEC_PER_YEAR;

// Special processing of active items to see better view in queue
		if($item_type == ITEM_TYPE_ZABBIX_ACTIVE){
			$nextcheck = $now + $delay;
		}
		else{
			$current_delay = get_current_delay($delay, $flex_intervals, $now);

			if(get_next_delay_interval($flex_intervals, $now, $next_interval) && ($now + $current_delay) > $next_interval){
// next check falls out of the current interval
				do{
					$current_delay = get_current_delay($delay, $flex_intervals, $next_interval + 1);

					/* as soon as item check in the interval is not forbidden with delay=0, use it */
					if (SEC_PER_YEAR != $current_delay)
						break;

					get_next_delay_interval($flex_intervals, $next_interval + 1, $next_interval);
				}
				while($next_interval - $now < SEC_PER_WEEK);
// checking the nearest week for delay!=0

				$now = $next_interval;
			}

			$delay = $current_delay;
			$shift = ($item_type == ITEM_TYPE_JMX ? $interfaceid : $itemid);
			$nextcheck = $delay * floor($now / $delay) + ($shift % $delay);

			while($nextcheck <= $now) $nextcheck += $delay;
		}

	return array('nextcheck' => $nextcheck, 'delay' => $delay);
	}


/**
 * Check if given character is a valid key id char
 * this function is a copy of is_key_char() from /src/libs/zbxcommon/misc.c
 * don't forget to take look in there before changing anything
 *
 * @author Konstantin Buravcov
 * @param string $char
 * @return bool
 */
	function isKeyIdChar($char){
		return (
			($char >= 'a' && $char <= 'z')
			|| ($char == '.' || $char == ',' || $char == '_' || $char == '-')
			|| ($char >= 'A' && $char <= 'Z')
			|| ($char >= '0' && $char <= '9')
		);
	}

/*
 * Function: httpitemExists
 *
 * Description:
 *     Function returns true if http items exists in the $items array.
 *     The array should contain a field 'type'
 *
 * Author:
 *     Alexander Vladishev
 *
 * Comments:
 *
 */
	function httpitemExists($items){
		foreach($items as $item)
			if ($item['type'] == ITEM_TYPE_HTTPTEST)
				return true;

		return false;
	}
?>
