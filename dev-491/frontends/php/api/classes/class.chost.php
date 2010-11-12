<?php
/*
** ZABBIX
** Copyright (C) 2000-2010 SIA Zabbix
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
/**
 * File containing CHost class for API.
 * @package API
 */
/**
 * Class containing methods for operations with Hosts
 */
class CHost extends CZBXAPI{
/**
 * Get Host data
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param _array $options
 * @param array $options['nodeids'] Node IDs
 * @param array $options['groupids'] HostGroup IDs
 * @param array $options['hostids'] Host IDs
 * @param boolean $options['monitored_hosts'] only monitored Hosts
 * @param boolean $options['templated_hosts'] include templates in result
 * @param boolean $options['with_items'] only with items
 * @param boolean $options['with_monitored_items'] only with monitored items
 * @param boolean $options['with_historical_items'] only with historical items
 * @param boolean $options['with_triggers'] only with triggers
 * @param boolean $options['with_monitored_triggers'] only with monitored triggers
 * @param boolean $options['with_httptests'] only with http tests
 * @param boolean $options['with_monitored_httptests'] only with monitored http tests
 * @param boolean $options['with_graphs'] only with graphs
 * @param boolean $options['editable'] only with read-write permission. Ignored for SuperAdmins
 * @param int $options['extendoutput'] return all fields for Hosts
 * @param boolean $options['select_groups'] select HostGroups
 * @param boolean $options['select_templates'] select Templates
 * @param boolean $options['selectItems'] select Items
 * @param boolean $options['select_triggers'] select Triggers
 * @param boolean $options['select_graphs'] select Graphs
 * @param boolean $options['select_applications'] select Applications
 * @param boolean $options['selectMacros'] select Macros
 * @param boolean $options['select_profile'] select Profile
 * @param int $options['count'] count Hosts, returned column name is rowscount
 * @param string $options['pattern'] search hosts by pattern in Host name
 * @param string $options['extendPattern'] search hosts by pattern in Host name, ip and DNS
 * @param int $options['limit'] limit selection
 * @param string $options['sortfield'] field to sort by
 * @param string $options['sortorder'] sort order
 * @return array|boolean Host data as array or false if error
 */
	public static function get($options=array()){
		global $USER_DETAILS;

		$result = array();
		$nodeCheck = false;
		$user_type = $USER_DETAILS['type'];
		$userid = $USER_DETAILS['userid'];

		$sort_columns = array('hostid', 'host', 'status', 'dns', 'ip'); // allowed columns for sorting
		$subselects_allowed_outputs = array(API_OUTPUT_REFER, API_OUTPUT_EXTEND, API_OUTPUT_CUSTOM); // allowed output options for [ select_* ] params


		$sql_parts = array(
			'select' => array('hosts' => 'h.hostid'),
			'from' => array('hosts' => 'hosts h'),
			'where' => array(),
			'group' => array(),
			'order' => array(),
			'limit' => null
		);

		$def_options = array(
			'nodeids'					=> null,
			'groupids'					=> null,
			'hostids'					=> null,
			'proxyids'					=> null,
			'templateids'				=> null,
			'interfaceids'				=> null,
			'itemids'					=> null,
			'triggerids'				=> null,
			'maintenanceids'			=> null,
			'graphids'					=> null,
			'dhostids'					=> null,
			'dserviceids'				=> null,
			'webcheckids'				=> null,
			'monitored_hosts'			=> null,
			'templated_hosts'			=> null,
			'proxy_hosts'				=> null,
			'with_items'				=> null,
			'with_monitored_items'		=> null,
			'with_historical_items'		=> null,
			'with_triggers'				=> null,
			'with_monitored_triggers'	=> null,
			'with_httptests'			=> null,
			'with_monitored_httptests'	=> null,
			'with_graphs'				=> null,
			'editable'					=> null,
			'nopermissions'				=> null,

// filter
			'filter'					=> null,
			'search'					=> null,
			'startSearch'				=> null,
			'excludeSearch'				=> null,

// OutPut
			'output'					=> API_OUTPUT_REFER,
			'select_groups'				=> null,
			'selectParentTemplates'		=> null,
			'selectItems'				=> null,
			'selectDiscoveries'		=> null,
			'select_triggers'			=> null,
			'select_graphs'				=> null,
			'select_dhosts'				=> null,
			'select_dservices'			=> null,
			'select_applications'		=> null,
			'selectMacros'				=> null,
			'selectScreens'				=> null,
			'selectInterfaces'			=> null,
			'select_profile'			=> null,
			'countOutput'				=> null,
			'groupCount'				=> null,
			'preservekeys'				=> null,

			'sortfield'					=> '',
			'sortorder'					=> '',
			'limit'						=> null,
			'limitSelects'				=> null
		);

		$options = zbx_array_merge($def_options, $options);

		if(is_array($options['output'])){
			unset($sql_parts['select']['hosts']);
			$sql_parts['select']['hostid'] = ' h.hostid';
			foreach($options['output'] as $key => $field){
				$sql_parts['select'][$field] = ' h.'.$field;
			}

			$options['output'] = API_OUTPUT_CUSTOM;
		}

// editable + PERMISSION CHECK
		if((USER_TYPE_SUPER_ADMIN == $user_type) || $options['nopermissions']){
		}
		else{
			$permission = $options['editable'] ? PERM_READ_WRITE : PERM_READ_ONLY;

			$sql_parts['where'][] = 'EXISTS ('.
							' SELECT hh.hostid '.
							' FROM hosts hh, hosts_groups hgg, rights r, users_groups ug '.
							' WHERE hh.hostid=h.hostid '.
								' AND hh.hostid=hgg.hostid '.
								' AND r.id=hgg.groupid '.
								' AND r.groupid=ug.usrgrpid '.
								' AND ug.userid='.$userid.
								' AND r.permission>='.$permission.
								' AND NOT EXISTS( '.
									' SELECT hggg.groupid '.
									' FROM hosts_groups hggg, rights rr, users_groups gg '.
									' WHERE hggg.hostid=hgg.hostid '.
										' AND rr.id=hggg.groupid '.
										' AND rr.groupid=gg.usrgrpid '.
										' AND gg.userid='.$userid.
										' AND rr.permission<'.$permission.
								' )) ';
		}

// nodeids
		$nodeids = !is_null($options['nodeids']) ? $options['nodeids'] : get_current_nodeid();

// hostids
		if(!is_null($options['hostids'])){
			zbx_value2array($options['hostids']);
			$sql_parts['where']['hostid'] = DBcondition('h.hostid', $options['hostids']);

			if(!$nodeCheck){
				$nodeCheck = true;
				$sql_parts['where'][] = DBin_node('h.hostid', $nodeids);
			}
		}

// groupids
		if(!is_null($options['groupids'])){
			zbx_value2array($options['groupids']);
			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['groupid'] = 'hg.groupid';
			}

			$sql_parts['from']['hosts_groups'] = 'hosts_groups hg';
			$sql_parts['where'][] = DBcondition('hg.groupid', $options['groupids']);
			$sql_parts['where']['hgh'] = 'hg.hostid=h.hostid';

			if(!is_null($options['groupCount'])){
				$sql_parts['group']['groupid'] = 'hg.groupid';
			}

			if(!$nodeCheck){
				$nodeCheck = true;
				$sql_parts['where'][] = DBin_node('hg.groupid', $nodeids);
			}
		}


// proxyids
		if(!is_null($options['proxyids'])){
			zbx_value2array($options['proxyids']);
			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['proxy_hostid'] = 'h.proxy_hostid';
			}
			$sql_parts['where'][] = DBcondition('h.proxy_hostid', $options['proxyids']);
		}

// templateids
		if(!is_null($options['templateids'])){
			zbx_value2array($options['templateids']);
			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['templateid'] = 'ht.templateid';
			}

			$sql_parts['from']['hosts_templates'] = 'hosts_templates ht';
			$sql_parts['where'][] = DBcondition('ht.templateid', $options['templateids']);
			$sql_parts['where']['hht'] = 'h.hostid=ht.hostid';

			if(!is_null($options['groupCount'])){
				$sql_parts['group']['templateid'] = 'ht.templateid';
			}

			if(!$nodeCheck){
				$nodeCheck = true;
				$sql_parts['where'][] = DBin_node('ht.templateid', $nodeids);
			}
		}

// interfaceids
		if(!is_null($options['interfaceids'])){
			zbx_value2array($options['interfaceids']);
			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['interfaceid'] = 'hi.interfaceid';
			}

			$sql_parts['from']['interfaces'] = 'interface hi';
			$sql_parts['where'][] = DBcondition('hi.interfaceid', $options['interfaceids']);
			$sql_parts['where']['hi'] = 'h.hostid=hi.hostid';

			if(!$nodeCheck){
				$nodeCheck = true;
				$sql_parts['where'][] = DBin_node('hi.interfaceid', $nodeids);
			}
		}

// itemids
		if(!is_null($options['itemids'])){
			zbx_value2array($options['itemids']);
			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['itemid'] = 'i.itemid';
			}

			$sql_parts['from']['items'] = 'items i';
			$sql_parts['where'][] = DBcondition('i.itemid', $options['itemids']);
			$sql_parts['where']['hi'] = 'h.hostid=i.hostid';

			if(!$nodeCheck){
				$nodeCheck = true;
				$sql_parts['where'][] = DBin_node('i.itemid', $nodeids);
			}
		}

// triggerids
		if(!is_null($options['triggerids'])){
			zbx_value2array($options['triggerids']);
			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['triggerid'] = 'f.triggerid';
			}

			$sql_parts['from']['functions'] = 'functions f';
			$sql_parts['from']['items'] = 'items i';
			$sql_parts['where'][] = DBcondition('f.triggerid', $options['triggerids']);
			$sql_parts['where']['hi'] = 'h.hostid=i.hostid';
			$sql_parts['where']['fi'] = 'f.itemid=i.itemid';

			if(!$nodeCheck){
				$nodeCheck = true;
				$sql_parts['where'][] = DBin_node('f.triggerid', $nodeids);
			}
		}

// webcheckids
		if(!is_null($options['webcheckids'])){
			zbx_value2array($options['webcheckids']);
			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['webcheckid'] = 'ht.httptestid';
			}

			$sql_parts['from']['applications'] = 'applications a';
			$sql_parts['from']['httptest'] = 'httptest ht';
			$sql_parts['where'][] = DBcondition('ht.httptestid', $options['webcheckids']);
			$sql_parts['where']['aht'] = 'a.applicationid=ht.applicationid';
			$sql_parts['where']['ah'] = 'a.hostid=h.hostid';

			if(!$nodeCheck){
				$nodeCheck = true;
				$sql_parts['where'][] = DBin_node('ht.httptestid', $nodeids);
			}
		}

// graphids
		if(!is_null($options['graphids'])){
			zbx_value2array($options['graphids']);
			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['graphid'] = 'gi.graphid';
			}

			$sql_parts['from']['graphs_items'] = 'graphs_items gi';
			$sql_parts['from']['items'] = 'items i';
			$sql_parts['where'][] = DBcondition('gi.graphid', $options['graphids']);
			$sql_parts['where']['igi'] = 'i.itemid=gi.itemid';
			$sql_parts['where']['hi'] = 'h.hostid=i.hostid';

			if(!$nodeCheck){
				$nodeCheck = true;
				$sql_parts['where'][] = DBin_node('gi.graphid', $nodeids);
			}
		}

// dhostids
		if(!is_null($options['dhostids'])){
			zbx_value2array($options['dhostids']);
			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['dhostid'] = 'ds.dhostid';
			}

			$sql_parts['from']['dservices'] = 'dservices ds';
			$sql_parts['where'][] = DBcondition('ds.dhostid', $options['dhostids']);
			$sql_parts['where']['dsh'] = 'ds.ip=h.ip';

			if(!is_null($options['groupCount'])){
				$sql_parts['group']['dhostid'] = 'ds.dhostid';
			}
		}

// dserviceids
		if(!is_null($options['dserviceids'])){
			zbx_value2array($options['dserviceids']);
			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['dserviceid'] = 'ds.dserviceid';
			}

			$sql_parts['from']['dservices'] = 'dservices ds';
			$sql_parts['where'][] = DBcondition('ds.dserviceid', $options['dserviceids']);
			$sql_parts['where']['dsh'] = 'ds.ip=h.ip';

			if(!is_null($options['groupCount'])){
				$sql_parts['group']['dserviceid'] = 'ds.dserviceid';
			}
		}
// maintenanceids
		if(!is_null($options['maintenanceids'])){
			zbx_value2array($options['maintenanceids']);
			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['maintenanceid'] = 'mh.maintenanceid';
			}

			$sql_parts['from']['maintenances_hosts'] = 'maintenances_hosts mh';
			$sql_parts['where'][] = DBcondition('mh.maintenanceid', $options['maintenanceids']);
			$sql_parts['where']['hmh'] = 'h.hostid=mh.hostid';

			if(!is_null($options['groupCount'])){
				$sql_parts['group']['maintenanceid'] = 'mh.maintenanceid';
			}
		}

// node check !!!!!
// should last, after all ****IDS checks
		if(!$nodeCheck){
			$nodeCheck = true;
			$sql_parts['where'][] = DBin_node('h.hostid', $nodeids);
		}

// monitored_hosts, templated_hosts
		if(!is_null($options['monitored_hosts'])){
			$sql_parts['where']['status'] = 'h.status='.HOST_STATUS_MONITORED;
		}
		else if(!is_null($options['templated_hosts'])){
			$sql_parts['where']['status'] = 'h.status IN ('.HOST_STATUS_MONITORED.','.HOST_STATUS_NOT_MONITORED.','.HOST_STATUS_TEMPLATE.')';
		}
		else if(!is_null($options['proxy_hosts'])){
			$sql_parts['where']['status'] = 'h.status IN ('.HOST_STATUS_PROXY_ACTIVE.','.HOST_STATUS_PROXY_PASSIVE.')';
		}
		else{
			$sql_parts['where']['status'] = 'h.status IN ('.HOST_STATUS_MONITORED.','.HOST_STATUS_NOT_MONITORED.')';
		}

// with_items, with_monitored_items, with_historical_items
		if(!is_null($options['with_items'])){
			$sql_parts['where'][] = 'EXISTS (SELECT i.hostid FROM items i WHERE h.hostid=i.hostid )';
		}
		else if(!is_null($options['with_monitored_items'])){
			$sql_parts['where'][] = 'EXISTS (SELECT i.hostid FROM items i WHERE h.hostid=i.hostid AND i.status='.ITEM_STATUS_ACTIVE.')';
		}
		else if(!is_null($options['with_historical_items'])){
			$sql_parts['where'][] = 'EXISTS (SELECT i.hostid FROM items i WHERE h.hostid=i.hostid AND (i.status='.ITEM_STATUS_ACTIVE.' OR i.status='.ITEM_STATUS_NOTSUPPORTED.') AND i.lastvalue IS NOT NULL)';
		}

// with_triggers, with_monitored_triggers
		if(!is_null($options['with_triggers'])){
			$sql_parts['where'][] = 'EXISTS( '.
					' SELECT i.itemid '.
					' FROM items i, functions f, triggers t '.
					' WHERE i.hostid=h.hostid '.
						' AND i.itemid=f.itemid '.
						' AND f.triggerid=t.triggerid)';
		}
		else if(!is_null($options['with_monitored_triggers'])){
			$sql_parts['where'][] = 'EXISTS( '.
					' SELECT i.itemid '.
					' FROM items i, functions f, triggers t '.
					' WHERE i.hostid=h.hostid '.
						' AND i.status='.ITEM_STATUS_ACTIVE.
						' AND i.itemid=f.itemid '.
						' AND f.triggerid=t.triggerid '.
						' AND t.status='.TRIGGER_STATUS_ENABLED.')';
		}

// with_httptests, with_monitored_httptests
		if(!is_null($options['with_httptests'])){
			$sql_parts['where'][] = 'EXISTS( '.
					' SELECT a.applicationid '.
					' FROM applications a, httptest ht '.
					' WHERE a.hostid=h.hostid '.
						' AND ht.applicationid=a.applicationid)';
		}
		else if(!is_null($options['with_monitored_httptests'])){
			$sql_parts['where'][] = 'EXISTS( '.
					' SELECT a.applicationid '.
					' FROM applications a, httptest ht '.
					' WHERE a.hostid=h.hostid '.
						' AND ht.applicationid=a.applicationid '.
						' AND ht.status='.HTTPTEST_STATUS_ACTIVE.')';
		}

// with_graphs
		if(!is_null($options['with_graphs'])){
			$sql_parts['where'][] = 'EXISTS( '.
					' SELECT DISTINCT i.itemid '.
					' FROM items i, graphs_items gi '.
					' WHERE i.hostid=h.hostid '.
						' AND i.itemid=gi.itemid)';
		}

// output
		if($options['output'] == API_OUTPUT_EXTEND){
			$sql_parts['select']['hosts'] = 'h.*';
		}

// countOutput
		if(!is_null($options['countOutput'])){
			$options['sortfield'] = '';
			$sql_parts['select'] = array('count(DISTINCT h.hostid) as rowscount');

//groupCount
			if(!is_null($options['groupCount'])){
				foreach($sql_parts['group'] as $key => $fields){
					$sql_parts['select'][$key] = $fields;
				}
			}
		}

// search
		if(is_array($options['search'])){
			zbx_db_search('hosts h', $options, $sql_parts);
		}

// filter
		if(is_array($options['filter'])){
			zbx_db_filter('hosts h', $options, $sql_parts);
		}

// order
// restrict not allowed columns for sorting
		$options['sortfield'] = str_in_array($options['sortfield'], $sort_columns) ? $options['sortfield'] : '';
		if(!zbx_empty($options['sortfield'])){
			$sortorder = ($options['sortorder'] == ZBX_SORT_DOWN)?ZBX_SORT_DOWN:ZBX_SORT_UP;

			$sql_parts['order'][$options['sortfield']] = 'h.'.$options['sortfield'].' '.$sortorder;

			if(!str_in_array('h.'.$options['sortfield'], $sql_parts['select']) && !str_in_array('h.*', $sql_parts['select'])){
				$sql_parts['select'][$options['sortfield']] = 'h.'.$options['sortfield'];
			}
		}

// limit
		if(zbx_ctype_digit($options['limit']) && $options['limit']){
			$sql_parts['limit'] = $options['limit'];
		}
//-------


		$hostids = array();

		$sql_parts['select'] = array_unique($sql_parts['select']);
		$sql_parts['from'] = array_unique($sql_parts['from']);
		$sql_parts['where'] = array_unique($sql_parts['where']);
		$sql_parts['group'] = array_unique($sql_parts['group']);
		$sql_parts['order'] = array_unique($sql_parts['order']);

		$sql_select = '';
		$sql_from = '';
		$sql_where = '';
		$sql_group = '';
		$sql_order = '';
		if(!empty($sql_parts['select']))	$sql_select.= implode(',',$sql_parts['select']);
		if(!empty($sql_parts['from']))		$sql_from.= implode(',',$sql_parts['from']);
		if(!empty($sql_parts['where']))		$sql_where.= implode(' AND ',$sql_parts['where']);
		if(!empty($sql_parts['group']))		$sql_where.= ' GROUP BY '.implode(',',$sql_parts['group']);
		if(!empty($sql_parts['order']))		$sql_order.= ' ORDER BY '.implode(',',$sql_parts['order']);
		$sql_limit = $sql_parts['limit'];

		$sql = 'SELECT '.zbx_db_distinct($sql_parts).' '.$sql_select.
				' FROM '.$sql_from.
				' WHERE '.$sql_where.
				$sql_group.
				$sql_order;
//SDI($sql);
		$res = DBselect($sql, $sql_limit);
		while($host = DBfetch($res)){
			if(!is_null($options['countOutput'])){
				if(!is_null($options['groupCount']))
					$result[] = $host;
				else
					$result = $host['rowscount'];
			}
			else{
				$hostids[$host['hostid']] = $host['hostid'];

				if($options['output'] == API_OUTPUT_SHORTEN){
					$result[$host['hostid']] = array('hostid' => $host['hostid']);
				}
				else{
					if(!isset($result[$host['hostid']])) $result[$host['hostid']] = array();

					if(!is_null($options['select_groups']) && !isset($result[$host['hostid']]['groups'])){
						$result[$host['hostid']]['groups'] = array();
					}
					if(!is_null($options['selectParentTemplates']) && !isset($result[$host['hostid']]['parentTemplates'])){
						$result[$host['hostid']]['parentTemplates'] = array();
					}
					if(!is_null($options['selectItems']) && !isset($result[$host['hostid']]['items'])){
						$result[$host['hostid']]['items'] = array();
					}
					if(!is_null($options['selectDiscoveries']) && !isset($result[$host['hostid']]['discoveries'])){
						$result[$host['hostid']]['discoveries'] = array();
					}
					if(!is_null($options['select_profile']) && !isset($result[$host['hostid']]['profile'])){
						$result[$host['hostid']]['profile'] = array();
						$result[$host['hostid']]['profile_ext'] = array();
					}
					if(!is_null($options['select_triggers']) && !isset($result[$host['hostid']]['triggers'])){
						$result[$host['hostid']]['triggers'] = array();
					}
					if(!is_null($options['select_graphs']) && !isset($result[$host['hostid']]['graphs'])){
						$result[$host['hostid']]['graphs'] = array();
					}
					if(!is_null($options['select_dhosts']) && !isset($result[$host['hostid']]['dhosts'])){
						$result[$host['hostid']]['dhosts'] = array();
					}
					if(!is_null($options['select_dservices']) && !isset($result[$host['hostid']]['dservices'])){
						$result[$host['hostid']]['dservices'] = array();
					}
					if(!is_null($options['select_applications']) && !isset($result[$host['hostid']]['applications'])){
						$result[$host['hostid']]['applications'] = array();
					}
					if(!is_null($options['selectMacros']) && !isset($result[$host['hostid']]['macros'])){
						$result[$host['hostid']]['macros'] = array();
					}

					if(!is_null($options['selectScreens']) && !isset($result[$host['hostid']]['screens'])){
						$result[$host['hostid']]['screens'] = array();
					}

					if(!is_null($options['selectInterfaces']) && !isset($result[$host['hostid']]['interfaces'])){
						$result[$host['hostid']]['interfaces'] = array();
					}

// groupids
					if(isset($host['groupid']) && is_null($options['select_groups'])){
						if(!isset($result[$host['hostid']]['groups']))
							$result[$host['hostid']]['groups'] = array();

						$result[$host['hostid']]['groups'][] = array('groupid' => $host['groupid']);
						unset($host['groupid']);
					}

// templateids
					if(isset($host['templateid'])){
						if(!isset($result[$host['hostid']]['templates']))
							$result[$host['hostid']]['templates'] = array();

						$result[$host['hostid']]['templates'][] = array(
							'templateid' => $host['templateid'],
							'hostid' => $host['templateid']
						);
						unset($host['templateid']);
					}

// triggerids
					if(isset($host['triggerid']) && is_null($options['select_triggers'])){
						if(!isset($result[$host['hostid']]['triggers']))
							$result[$host['hostid']]['triggers'] = array();

						$result[$host['hostid']]['triggers'][] = array('triggerid' => $host['triggerid']);
						unset($host['triggerid']);
					}

// interfaceids
					if(isset($host['interfaceid']) && is_null($options['selectInterfaces'])){
						if(!isset($result[$host['hostid']]['interfaces']))
							$result[$host['hostid']]['interfaces'] = array();

						$result[$host['hostid']]['interfaces'][] = array('interfaceid' => $host['interfaceid']);
						unset($host['interfaceid']);
					}

// itemids
					if(isset($host['itemid']) && is_null($options['selectItems'])){
						if(!isset($result[$host['hostid']]['items']))
							$result[$host['hostid']]['items'] = array();

						$result[$host['hostid']]['items'][] = array('itemid' => $host['itemid']);
						unset($host['itemid']);
					}

// graphids
					if(isset($host['graphid']) && is_null($options['select_graphs'])){
						if(!isset($result[$host['hostid']]['graphs']))
							$result[$host['hostid']]['graphs'] = array();

						$result[$host['hostid']]['graphs'][] = array('graphid' => $host['graphid']);
						unset($host['graphid']);
					}

// webcheckids
					if(isset($host['httptestid'])){
						if(!isset($result[$host['hostid']]['webchecks']))
							$result[$host['hostid']]['webchecks'] = array();

						$result[$host['hostid']]['webchecks'][] = array('webcheckid' => $host['httptestid']);
						unset($host['httptestid']);
					}

// dhostids
					if(isset($host['dhostid']) && is_null($options['select_dhosts'])){
						if(!isset($result[$host['hostid']]['dhosts']))
							$result[$host['hostid']]['dhosts'] = array();

						$result[$host['hostid']]['dhosts'][] = array('dhostid' => $host['dhostid']);
						unset($host['dhostid']);
					}

// dserviceids
					if(isset($host['dserviceid']) && is_null($options['select_dservices'])){
						if(!isset($result[$host['hostid']]['dservices']))
							$result[$host['hostid']]['dservices'] = array();

						$result[$host['hostid']]['dservices'][] = array('dserviceid' => $host['dserviceid']);
						unset($host['dserviceid']);
					}
// maintenanceids
					if(isset($host['maintenanceid'])){
						if(!isset($result[$host['hostid']]['maintenances']))
							$result[$host['hostid']]['maintenances'] = array();

						if($host['maintenanceid'] > 0)
							$result[$host['hostid']]['maintenances'][] = array('maintenanceid' => $host['maintenanceid']);
//						unset($host['maintenanceid']);
					}
//---

					$result[$host['hostid']] += $host;
				}
			}
		}

Copt::memoryPick();
		if(!is_null($options['countOutput'])){
			if(is_null($options['preservekeys'])) $result = zbx_cleanHashes($result);
			return $result;
		}

// Adding Objects
// Adding Groups
		if(!is_null($options['select_groups']) && str_in_array($options['select_groups'], $subselects_allowed_outputs)){
			$obj_params = array(
					'nodeids' => $nodeids,
					'output' => $options['select_groups'],
					'hostids' => $hostids,
					'preservekeys' => 1
				);
			$groups = CHostgroup::get($obj_params);

			foreach($groups as $groupid => $group){
				$ghosts = $group['hosts'];
				unset($group['hosts']);
				foreach($ghosts as $num => $host){
					$result[$host['hostid']]['groups'][] = $group;
				}
			}
		}

// Adding Profiles
		if(!is_null($options['select_profile'])){
			$sql = 'SELECT hp.* '.
				' FROM hosts_profiles hp '.
				' WHERE '.DBcondition('hp.hostid', $hostids);
			$db_profile = DBselect($sql);
			while($profile = DBfetch($db_profile))
				$result[$profile['hostid']]['profile'] = $profile;


			$sql = 'SELECT hpe.* '.
				' FROM hosts_profiles_ext hpe '.
				' WHERE '.DBcondition('hpe.hostid', $hostids);
			$db_profile_ext = DBselect($sql);
			while($profile_ext = DBfetch($db_profile_ext))
				$result[$profile_ext['hostid']]['profile_ext'] = $profile_ext;
		}

// Adding Templates
		if(!is_null($options['selectParentTemplates'])){
			$obj_params = array(
				'nodeids' => $nodeids,
				'hostids' => $hostids,
				'preservekeys' => 1
			);

			if(is_array($options['selectParentTemplates']) || str_in_array($options['selectParentTemplates'], $subselects_allowed_outputs)){
				$obj_params['output'] = $options['selectParentTemplates'];
				$templates = CTemplate::get($obj_params);

				if(!is_null($options['limitSelects'])) order_result($templates, 'host');
				$count = array();
				foreach($templates as $templateid => $template){
					unset($templates[$templateid]['hosts']);
					$count = array();
					foreach($template['hosts'] as $hnum => $host){
						if(!is_null($options['limitSelects'])){
							if(!isset($count[$host['hostid']])) $count[$host['hostid']] = 0;
							$count[$host['hostid']]++;

							if($count[$host['hostid']] > $options['limitSelects']) continue;
						}

						$result[$host['hostid']]['parentTemplates'][] = &$templates[$templateid];
					}
				}
			}
			else if(API_OUTPUT_COUNT == $options['selectParentTemplates']){
				$obj_params['countOutput'] = 1;
				$obj_params['groupCount'] = 1;

				$templates = CTemplate::get($obj_params);
				$templates = zbx_toHash($templates, 'hostid');
				foreach($result as $hostid => $host){
					if(isset($templates[$hostid]))
						$result[$hostid]['templates'] = $templates[$hostid]['rowscount'];
					else
						$result[$hostid]['templates'] = 0;
				}
			}
		}

// Adding HostInterfaces
		if(!is_null($options['selectInterfaces'])){
			$obj_params = array(
				'nodeids' => $nodeids,
				'hostids' => $hostids,
				'nopermissions' => 1,
				'preservekeys' => 1
			);
			if(is_array($options['selectInterfaces']) || str_in_array($options['selectInterfaces'], $subselects_allowed_outputs)){
				$obj_params['output'] = $options['selectInterfaces'];
				$interfaces = CHostInterface::get($obj_params);

				if(!is_null($options['limitSelects']))
					order_result($interfaces, 'interfaceid', ZBX_SORT_UP);

				$count = array();
				foreach($interfaces as $interfaceid => $interface){
					if(!is_null($options['limitSelects'])){
						if(!isset($count[$interface['hostid']])) $count[$interface['hostid']] = 0;
						$count[$interface['hostid']]++;

						if($count[$interface['hostid']] > $options['limitSelects']) continue;
					}

					$result[$interface['hostid']]['interfaces'][] = &$interfaces[$interfaceid];
				}
			}
			else if(API_OUTPUT_COUNT == $options['selectInterfaces']){
				$obj_params['countOutput'] = 1;
				$obj_params['groupCount'] = 1;

				$interfaces = CHostInterface::get($obj_params);
				$interfaces = zbx_toHash($interfaces, 'hostid');
				foreach($result as $hostid => $host){
					if(isset($interfaces[$hostid]))
						$result[$hostid]['interfaces'] = $interfaces[$hostid]['rowscount'];
					else
						$result[$hostid]['interfaces'] = 0;
				}
			}
		}

// Adding Items
		if(!is_null($options['selectItems'])){
			$obj_params = array(
				'nodeids' => $nodeids,
				'hostids' => $hostids,
				'filter' => array('flags' => array(ZBX_FLAG_DISCOVERY_NORMAL, ZBX_FLAG_DISCOVERY_CREATED)),
				'nopermissions' => 1,
				'preservekeys' => 1
			);

			if(is_array($options['selectItems']) || str_in_array($options['selectItems'], $subselects_allowed_outputs)){
				$obj_params['output'] = $options['selectItems'];
				$items = CItem::get($obj_params);

				if(!is_null($options['limitSelects'])) order_result($items, 'description');
				$count = array();
				foreach($items as $itemid => $item){
					if(!is_null($options['limitSelects'])){
						if(!isset($count[$item['hostid']])) $count[$item['hostid']] = 0;
						$count[$item['hostid']]++;

						if($count[$item['hostid']] > $options['limitSelects']) continue;
					}

					$result[$item['hostid']]['items'][] = &$items[$itemid];
				}
			}
			else if(API_OUTPUT_COUNT == $options['selectItems']){
				$obj_params['countOutput'] = 1;
				$obj_params['groupCount'] = 1;

				$items = CItem::get($obj_params);
				$items = zbx_toHash($items, 'hostid');
				foreach($result as $hostid => $host){
					if(isset($items[$hostid]))
						$result[$hostid]['items'] = $items[$hostid]['rowscount'];
					else
						$result[$hostid]['items'] = 0;
				}
			}
		}

// Adding Discoveries
		if(!is_null($options['selectDiscoveries'])){
			$obj_params = array(
				'nodeids' => $nodeids,
				'hostids' => $hostids,
				'nopermissions' => 1,
				'preservekeys' => 1,
			);

			if(is_array($options['selectDiscoveries']) || str_in_array($options['selectDiscoveries'], $subselects_allowed_outputs)){
				$obj_params['output'] = $options['selectDiscoveries'];
				$items = CDiscoveryRule::get($obj_params);

				if(!is_null($options['limitSelects'])) order_result($items, 'description');

				$count = array();
				foreach($items as $itemid => $item){
					unset($items[$itemid]['hosts']);
					foreach($item['hosts'] as $hnum => $host){
						if(!is_null($options['limitSelects'])){
							if(!isset($count[$host['hostid']])) $count[$host['hostid']] = 0;
							$count[$host['hostid']]++;

							if($count[$host['hostid']] > $options['limitSelects']) continue;
						}

						$result[$host['hostid']]['discoveries'][] = &$items[$itemid];
					}
				}
			}
			else if(API_OUTPUT_COUNT == $options['selectDiscoveries']){
				$obj_params['countOutput'] = 1;
				$obj_params['groupCount'] = 1;

				$items = CDiscoveryRule::get($obj_params);
				$items = zbx_toHash($items, 'hostid');
				foreach($result as $hostid => $host){
					if(isset($items[$hostid]))
						$result[$hostid]['discoveries'] = $items[$hostid]['rowscount'];
					else
						$result[$hostid]['discoveries'] = 0;
				}
			}
		}

// Adding triggers
		if(!is_null($options['select_triggers'])){
			$obj_params = array(
				'nodeids' => $nodeids,
				'hostids' => $hostids,
				'nopermissions' => 1,
				'preservekeys' => 1
			);

			if(is_array($options['select_triggers']) || str_in_array($options['select_triggers'], $subselects_allowed_outputs)){
				$obj_params['output'] = $options['select_triggers'];
				$triggers = CTrigger::get($obj_params);

				if(!is_null($options['limitSelects'])) order_result($triggers, 'description');

				$count = array();
				foreach($triggers as $triggerid => $trigger){
					unset($triggers[$triggerid]['hosts']);

					foreach($trigger['hosts'] as $hnum => $host){
						if(!is_null($options['limitSelects'])){
							if(!isset($count[$host['hostid']])) $count[$host['hostid']] = 0;
							$count[$host['hostid']]++;

							if($count[$host['hostid']] > $options['limitSelects']) continue;
						}

						$result[$host['hostid']]['triggers'][] = &$triggers[$triggerid];
					}
				}
			}
			else if(API_OUTPUT_COUNT == $options['select_triggers']){
				$obj_params['countOutput'] = 1;
				$obj_params['groupCount'] = 1;

				$triggers = CTrigger::get($obj_params);
				$triggers = zbx_toHash($triggers, 'hostid');
				foreach($result as $hostid => $host){
					if(isset($triggers[$hostid]))
						$result[$hostid]['triggers'] = $triggers[$hostid]['rowscount'];
					else
						$result[$hostid]['triggers'] = 0;
				}
			}
		}

// Adding graphs
		if(!is_null($options['select_graphs'])){
			$obj_params = array(
				'nodeids' => $nodeids,
				'hostids' => $hostids,
				'nopermissions' => 1,
				'preservekeys' => 1
			);

			if(is_array($options['select_graphs']) || str_in_array($options['select_graphs'], $subselects_allowed_outputs)){
				$obj_params['output'] = $options['select_graphs'];
				$graphs = CGraph::get($obj_params);

				if(!is_null($options['limitSelects'])) order_result($graphs, 'name');

				$count = array();
				foreach($graphs as $graphid => $graph){
					unset($graphs[$graphid]['hosts']);

					foreach($graph['hosts'] as $hnum => $host){
						if(!is_null($options['limitSelects'])){
							if(!isset($count[$host['hostid']])) $count[$host['hostid']] = 0;
							$count[$host['hostid']]++;

							if($count[$host['hostid']] > $options['limitSelects']) continue;
						}

						$result[$host['hostid']]['graphs'][] = &$graphs[$graphid];
					}
				}
			}
			else if(API_OUTPUT_COUNT == $options['select_graphs']){
				$obj_params['countOutput'] = 1;
				$obj_params['groupCount'] = 1;

				$graphs = CGraph::get($obj_params);
				$graphs = zbx_toHash($graphs, 'hostid');
				foreach($result as $hostid => $host){
					if(isset($graphs[$hostid]))
						$result[$hostid]['graphs'] = $graphs[$hostid]['rowscount'];
					else
						$result[$hostid]['graphs'] = 0;
				}
			}
		}

// Adding discovery hosts
		if(!is_null($options['select_dhosts'])){
			$obj_params = array(
				'nodeids' => $nodeids,
				'hostids' => $hostids,
				'nopermissions' => 1,
				'preservekeys' => 1
			);

			if(is_array($options['select_dhosts']) || str_in_array($options['select_dhosts'], $subselects_allowed_outputs)){
				$obj_params['output'] = $options['select_dhosts'];
				$dhosts = CDHost::get($obj_params);

				if(!is_null($options['limitSelects'])) order_result($dhosts, 'dhostid');

				$count = array();
				foreach($dhosts as $dhostid => $dhost){
					unset($dhosts[$dhostid]['hosts']);

					foreach($dhost['hosts'] as $hnum => $host){
						if(!is_null($options['limitSelects'])){
							if(!isset($count[$host['hostid']])) $count[$host['hostid']] = 0;
							$count[$host['hostid']]++;

							if($count[$host['hostid']] > $options['limitSelects']) continue;
						}

						$result[$host['hostid']]['dhosts'][] = &$dhosts[$dhostid];
					}
				}
			}
			else if(API_OUTPUT_COUNT == $options['select_dhosts']){
				$obj_params['countOutput'] = 1;
				$obj_params['groupCount'] = 1;

				$dhosts = CDHost::get($obj_params);
				$dhosts = zbx_toHash($dhosts, 'hostid');
				foreach($result as $hostid => $host){
					if(isset($dhosts[$hostid]))
						$result[$hostid]['dhosts'] = $dhosts[$hostid]['rowscount'];
					else
						$result[$hostid]['dhosts'] = 0;
				}
			}
		}

// Adding applications
		if(!is_null($options['select_applications'])){
			$obj_params = array(
				'nodeids' => $nodeids,
				'hostids' => $hostids,
				'nopermissions' => 1,
				'preservekeys' => 1
			);

			if(is_array($options['select_applications']) || str_in_array($options['select_applications'], $subselects_allowed_outputs)){
				$obj_params['output'] = $options['select_applications'];
				$applications = CApplication::get($obj_params);

				if(!is_null($options['limitSelects'])) order_result($applications, 'name');

				$count = array();
				foreach($applications as $applicationid => $application){
					unset($applications[$applicationid]['hosts']);

					foreach($application['hosts'] as $hnum => $host){
						if(!is_null($options['limitSelects'])){
							if(!isset($count[$host['hostid']])) $count[$host['hostid']] = 0;
							$count[$host['hostid']]++;

							if($count[$host['hostid']] > $options['limitSelects']) continue;
						}

						$result[$host['hostid']]['applications'][] = &$applications[$applicationid];
					}
				}
			}
			else if(API_OUTPUT_COUNT == $options['select_applications']){
				$obj_params['countOutput'] = 1;
				$obj_params['groupCount'] = 1;

				$applications = CApplication::get($obj_params);

				$applications = zbx_toHash($applications, 'hostid');
				foreach($result as $hostid => $host){
					if(isset($applications[$hostid]))
						$result[$hostid]['applications'] = $applications[$hostid]['rowscount'];
					else
						$result[$hostid]['applications'] = 0;
				}
			}
		}

// Adding macros
		if(!is_null($options['selectMacros']) && str_in_array($options['selectMacros'], $subselects_allowed_outputs)){
			$obj_params = array(
				'nodeids' => $nodeids,
				'output' => $options['selectMacros'],
				'hostids' => $hostids,
				'preservekeys' => 1
			);

			$macros = CUserMacro::get($obj_params);
			foreach($macros as $macroid => $macro){
				$mhosts = $macro['hosts'];
				unset($macro['hosts']);
				foreach($mhosts as $num => $host){
					$result[$host['hostid']]['macros'][] = $macro;
				}
			}
		}

// Adding screens
		if(!is_null($options['selectScreens'])){
			$obj_params = array(
				'nodeids' => $nodeids,
				'hostids' => $hostids,
				'editable' => $options['editable'],
				'nopermissions' => 1,
				'preservekeys' => 1
			);

			if(is_array($options['selectScreens']) || str_in_array($options['selectScreens'], $subselects_allowed_outputs)){
				$obj_params['output'] = $options['selectScreens'];

				$screens = CTemplateScreen::get($obj_params);
				if(!is_null($options['limitSelects'])) order_result($screens, 'name');

				foreach($screens as $snum => $screen){
					if(!is_null($options['limitSelects'])){
						if(count($result[$screen['hostid']]['screens']) >= $options['limitSelects']) continue;
					}

					unset($screens[$snum]['hosts']);
					$result[$screen['hostid']]['screens'][] = &$screens[$snum];
				}
			}
			else if(API_OUTPUT_COUNT == $options['selectScreens']){
				$obj_params['countOutput'] = 1;
				$obj_params['groupCount'] = 1;

				$screens = CTemplateScreen::get($obj_params);
				$screens = zbx_toHash($screens, 'hostid');

				foreach($result as $hostid => $host){
					if(isset($screens[$hostid]))
						$result[$hostid]['screens'] = $screens[$hostid]['rowscount'];
					else
						$result[$hostid]['screens'] = 0;
				}
			}
		}

Copt::memoryPick();
// removing keys (hash -> array)
		if(is_null($options['preservekeys'])){
			$result = zbx_cleanHashes($result);
		}

	return $result;
	}

/**
 * Get Host ID by Host name
 *
 * @param _array $host_data
 * @param string $host_data['host']
 * @return int|boolean
 */
	public static function getObjects($hostData){
		$options = array(
			'filter' => $hostData,
			'output'=>API_OUTPUT_EXTEND
		);

		if(isset($hostData['node']))
			$options['nodeids'] = getNodeIdByNodeName($hostData['node']);
		else if(isset($hostData['nodeids']))
			$options['nodeids'] = $hostData['nodeids'];

		$result = self::get($options);

	return $result;
	}

	public static function exists($object){
		$keyFields = array(array('hostid', 'host'));

		$options = array(
			'filter' => zbx_array_mintersect($keyFields, $object),
			'output' => API_OUTPUT_SHORTEN,
			'nopermissions' => 1,
			'limit' => 1
		);

		if(isset($object['node']))
			$options['nodeids'] = getNodeIdByNodeName($object['node']);
		else if(isset($object['nodeids']))
			$options['nodeids'] = $object['nodeids'];

		$objs = self::get($options);

	return !empty($objs);
	}

	protected static function checkInput(&$hosts, $method){
		$create = ($method == 'create');
		$update = ($method == 'update');
		$delete = ($method == 'delete');

// permissions
		$groupids = array();
		foreach($hosts as $hnum => $host){
			if(!isset($host['groups'])) continue;
			$groupids = array_merge($groupids, zbx_objectValues($host['groups'], 'groupid'));
		}

		if($update || $delete){
			$hostDBfields = array('hostid'=> null);
			$dbHosts = self::get(array(
				'output' => array('hostid', 'host'),
				'hostids' => zbx_objectValues($hosts, 'hostid'),
				'editable' => 1,
				'preservekeys' => 1
			));
		}
		else{
			$hostDBfields = array('host'=>null);
		}

		if(!empty($groupids)){
			$dbGroups = CHostGroup::get(array(
				'output' => API_OUTPUT_EXTEND,
				'groupids' => $groupids,
				'editable' => 1,
				'preservekeys' => 1
			));

		}

		foreach($hosts as $inum => &$host){
			if(!check_db_fields($hostDBfields, $host)){
				self::exception(ZBX_API_ERROR_PARAMETERS, 'Wrong fields for host [ '.$host['host'].' ]');
			}

			if($update || $delete){
				if(!isset($dbHosts[$host['hostid']]))
					self::exception(ZBX_API_ERROR_PARAMETERS, S_NO_PERMISSIONS);

				$host['host'] = $dbHosts[$host['hostid']]['host'];
			}
			else{
				if(!isset($host['groups']))
					self::exception(ZBX_API_ERROR_PARAMETERS, 'No groups for host [ '.$host['host'].' ]');

				if(!isset($host['interfaces']))
					self::exception(ZBX_API_ERROR_PARAMETERS, 'No interfaces for host [ '.$host['host'].' ]');
			}

			if(isset($host['groups'])){
				if(!is_array($host['groups']) || empty($host['groups']))
					self::exception(ZBX_API_ERROR_PARAMETERS, 'No groups for host [ '.$host['host'].' ]');

				foreach($host['groups'] as $gnum => $group){
					if(!isset($dbGroups[$group['groupid']])){
						self::exception(ZBX_API_ERROR_PERMISSIONS, S_NO_PERMISSIONS);
					}
				}
			}

			if(isset($host['interfaces'])){
				if(!is_array($host['interfaces']) || empty($host['interfaces']))
					self::exception(ZBX_API_ERROR_PARAMETERS, 'No interfaces for host [ '.$host['host'].' ]');
			}

			if(isset($host['host'])){
				if(!preg_match('/^'.ZBX_PREG_HOST_FORMAT.'$/i', $host['host'])){
					self::exception(ZBX_API_ERROR_PARAMETERS, 'Incorrect characters used for Hostname [ '.$host['host'].' ]');
				}

				$hostsExists = self::get(array(
					'filter' => array('host' => $host['host'])
				));
				foreach($hostsExists as $exnum => $hostExists){
					if(!$update || ($hostExists['hostid'] != $host['hostid'])){
						self::exception(ZBX_API_ERROR_PARAMETERS, S_HOST.' [ '.$host['host'].' ] '.S_ALREADY_EXISTS_SMALL);
					}
				}

				$templatesExists = CTemplate::get(array(
					'filter' => array('host' => $host['host'])
				));
				foreach($templatesExists as $exnum => $templatesExists){
					if(!$update || ($templatesExists['hostid'] != $host['hostid'])){
						self::exception(ZBX_API_ERROR_PARAMETERS, S_TEMPLATE.' [ '.$host['host'].' ] '.S_ALREADY_EXISTS_SMALL);
					}
				}
			}
		}
		unset($host);
	}

/**
 * Add Host
 *
 * @param _array $hosts multidimensional array with Hosts data
 * @param string $hosts['host'] Host name.
 * @param array $hosts['groups'] array of HostGroup objects with IDs add Host to.
 * @param int $hosts['port'] Port. OPTIONAL
 * @param int $hosts['status'] Host Status. OPTIONAL
 * @param int $hosts['useip'] Use IP. OPTIONAL
 * @param string $hosts['dns'] DNS. OPTIONAL
 * @param string $hosts['ip'] IP. OPTIONAL
 * @param int $hosts['proxy_hostid'] Proxy Host ID. OPTIONAL
 * @param int $hosts['useipmi'] Use IPMI. OPTIONAL
 * @param string $hosts['ipmi_ip'] IPMAI IP. OPTIONAL
 * @param int $hosts['ipmi_port'] IPMI port. OPTIONAL
 * @param int $hosts['ipmi_authtype'] IPMI authentication type. OPTIONAL
 * @param int $hosts['ipmi_privilege'] IPMI privilege. OPTIONAL
 * @param string $hosts['ipmi_username'] IPMI username. OPTIONAL
 * @param string $hosts['ipmi_password'] IPMI password. OPTIONAL
 * @return boolean
 */
	public static function create($hosts){
		$hosts = zbx_toArray($hosts);
		$hostids = array();

		try{
			self::BeginTransaction(__METHOD__);

			self::checkInput($hosts, __FUNCTION__);

			foreach($hosts as $num => $host){
				$hostid = DB::insert('hosts', array($host));
				$hostids[] = $hostid = reset($hostid);

				$host['hostid'] = $hostid;

				$options = array();
				$options['hosts'] = $host;

				if(isset($host['groups']) && !is_null($host['groups']))
					$options['groups'] = $host['groups'];

				if(isset($host['templates']) && !is_null($host['templates']))
					$options['templates'] = $host['templates'];

				if(isset($host['macros']) && !is_null($host['macros']))
					$options['macros'] = $host['macros'];

				if(isset($host['interfaces']) && !is_null($host['interfaces']))
					$options['interfaces'] = $host['interfaces'];

				$result = CHost::massAdd($options);
				if(!$result){
					self::exception();
				}

				if(isset($host['profile']) && !empty($host['extendedProfile'])){
					$fields = array_keys($host['profile']);
					$fields = implode(', ', $fields);

					$values = array_map('zbx_dbstr', $host['profile']);
					$values = implode(', ', $values);

					DBexecute('INSERT INTO hosts_profiles (hostid, '.$fields.') VALUES ('.$hostid.', '.$values.')');
				}

				if(isset($host['extendedProfile']) && !empty($host['extendedProfile'])){
					$fields = array_keys($host['extendedProfile']);
					$fields = implode(', ', $fields);

					$values = array_map('zbx_dbstr', $host['extendedProfile']);
					$values = implode(', ', $values);

					DBexecute('INSERT INTO hosts_profiles_ext (hostid, '.$fields.') VALUES ('.$hostid.', '.$values.')');
				}
			}

			self::EndTransaction(true, __METHOD__);
			return array('hostids' => $hostids);
		}
		catch(APIException $e){
			self::EndTransaction(false, __METHOD__);
			$error = $e->getErrors();
			$error = reset($error);
			self::setError(__METHOD__, $e->getCode(), $error);
			return false;
		}
	}

/**
 * Update Host
 *
 * @param _array $hosts multidimensional array with Hosts data
 * @param string $hosts['host'] Host name.
 * @param int $hosts['port'] Port. OPTIONAL
 * @param int $hosts['status'] Host Status. OPTIONAL
 * @param int $hosts['useip'] Use IP. OPTIONAL
 * @param string $hosts['dns'] DNS. OPTIONAL
 * @param string $hosts['ip'] IP. OPTIONAL
 * @param int $hosts['proxy_hostid'] Proxy Host ID. OPTIONAL
 * @param int $hosts['useipmi'] Use IPMI. OPTIONAL
 * @param string $hosts['ipmi_ip'] IPMAI IP. OPTIONAL
 * @param int $hosts['ipmi_port'] IPMI port. OPTIONAL
 * @param int $hosts['ipmi_authtype'] IPMI authentication type. OPTIONAL
 * @param int $hosts['ipmi_privilege'] IPMI privilege. OPTIONAL
 * @param string $hosts['ipmi_username'] IPMI username. OPTIONAL
 * @param string $hosts['ipmi_password'] IPMI password. OPTIONAL
 * @param string $hosts['groups'] groups
 * @return boolean
 */
	public static function update($hosts){
		$hosts = zbx_toArray($hosts);
		$hostids = zbx_objectValues($hosts, 'hostid');

		try{
			self::BeginTransaction(__METHOD__);

			self::checkInput($hosts, __FUNCTION__);

			foreach($hosts as $hnum => $host){
				$interfaces = null;
				if(isset($host['interfaces'])){
					$interfaces = $host['interfaces'];
					unset($host['interfaces']);
				}
				$result = self::massUpdate(array('hosts' => $host));

// INTERFACES
				if(!is_null($interfaces)){
					$interfacesToDelete = CHostInterface::get(array(
						'hostids' => $host['hostid'],
						'output' => API_OUTPUT_EXTEND,
						'preservekeys' => true,
						'nopermissions' => 1
					));
// Add
					$interfacesToAdd = array();
					$interfacesToUpdate = array();
					foreach($interfaces as $hinum => $interface){
						$interface['hostid'] = $host['hostid'];

						if(!isset($interface['interfaceid'])){
							$interfacesToAdd[] = $interface;
						}
						else if(isset($interfacesToDelete[$interface['interfaceid']])){
							$interfacesToUpdate[] = $interface;
							unset($interfacesToDelete[$interface['interfaceid']]);
						}
					}
//----

					if(!empty($interfacesToDelete))
						$result = CHostInterface::delete(zbx_objectValues($interfacesToDelete, 'interfaceid'));
						if(!$result) self::exception(ZBX_API_ERROR_INTERNAL, 'Host update failed');

					if(!empty($interfacesToUpdate))
						$result = CHostInterface::update($interfacesToUpdate);
						if(!$result) self::exception(ZBX_API_ERROR_INTERNAL, 'Host update failed');

					if(!empty($interfacesToAdd))
						$result = CHostInterface::create($interfacesToAdd);
						if(!$result) self::exception(ZBX_API_ERROR_INTERNAL, 'Host update failed');
				}

				if(!$result) self::exception(ZBX_API_ERROR_INTERNAL, 'Host update failed');
			}

			self::EndTransaction(true, __METHOD__);
			return array('hostids' => $hostids);
		}
		catch(APIException $e){
			self::EndTransaction(false, __METHOD__);
			$error = $e->getErrors();
			$error = reset($error);
			self::setError(__METHOD__, $e->getCode(), $error);
			return false;
		}
	}

/**
 * Add Hosts to HostGroups. All Hosts are added to all HostGroups.
 *
 * @param array $data
 * @param array $data['groups']
 * @param array $data['templates']
 * @param array $data['macros']
 * @return array
 */
	public static function massAdd(&$data){
		$data['hosts'] = zbx_toArray($data['hosts']);

		try{
			self::BeginTransaction(__METHOD__);

			$options = array(
				'hostids' => zbx_objectValues($data['hosts'], 'hostid'),
				'editable' => 1,
				'preservekeys' => 1
			);
			$upd_hosts = self::get($options);
			foreach($data['hosts'] as $hnum => $host){
				if(!isset($upd_hosts[$host['hostid']])){
					self::exception(ZBX_API_ERROR_PERMISSIONS, 'You do not have enough rights for operation');
				}
			}

			if(isset($data['groups']) && !empty($data['groups'])){
				$data['groups'] = zbx_toArray($data['groups']);

				$options = array(
					'hosts' => &$data['hosts'],
					'groups' => &$data['groups']
				);
				$result = CHostGroup::massAdd($options);
				if(!$result) self::exception();
			}

			if(isset($data['templates']) && !empty($data['templates'])){
				$data['templates'] = zbx_toArray($data['templates']);

				$options = array(
					'hosts' => &$data['hosts'],
					'templates' => &$data['templates']
				);
				$result = CTemplate::massAdd($options);
				if(!$result) self::exception();
			}

			if(isset($data['macros']) && !empty($data['macros'])){
				$data['macros'] = zbx_toArray($data['macros']);

				$options = array(
					'hosts' => &$data['hosts'],
					'macros' => &$data['macros']
				);

				$result = CUserMacro::massAdd($options);
				if(!$result) self::exception();
			}

			if(isset($data['interfaces']) && !empty($data['interfaces'])){
				$data['interfaces'] = zbx_toArray($data['interfaces']);

				$options = array(
					'hosts' => &$data['hosts'],
					'interfaces' => &$data['interfaces']
				);

				$result = CHostInterface::massAdd($options);
				if(!$result) self::exception();
			}

			self::EndTransaction(true, __METHOD__);
			return array('hostids' => zbx_objectValues($data['hosts'], 'hostid'));
		}
		catch(APIException $e){
			self::EndTransaction(false, __METHOD__);
			$error = $e->getErrors();
			$error = reset($error);
			self::setError(__METHOD__, $e->getCode(), $error);
			return false;
		}
	}

/**
 * Mass update hosts
 *
 * @param _array $hosts multidimensional array with Hosts data
 * @param array $hosts['hosts'] Array of Host objects to update
 * @param string $hosts['fields']['host'] Host name.
 * @param array $hosts['fields']['groupids'] HostGroup IDs add Host to.
 * @param int $hosts['fields']['port'] Port. OPTIONAL
 * @param int $hosts['fields']['status'] Host Status. OPTIONAL
 * @param int $hosts['fields']['useip'] Use IP. OPTIONAL
 * @param string $hosts['fields']['dns'] DNS. OPTIONAL
 * @param string $hosts['fields']['ip'] IP. OPTIONAL
 * @param int $hosts['fields']['proxy_hostid'] Proxy Host ID. OPTIONAL
 * @param int $hosts['fields']['useipmi'] Use IPMI. OPTIONAL
 * @param string $hosts['fields']['ipmi_ip'] IPMAI IP. OPTIONAL
 * @param int $hosts['fields']['ipmi_port'] IPMI port. OPTIONAL
 * @param int $hosts['fields']['ipmi_authtype'] IPMI authentication type. OPTIONAL
 * @param int $hosts['fields']['ipmi_privilege'] IPMI privilege. OPTIONAL
 * @param string $hosts['fields']['ipmi_username'] IPMI username. OPTIONAL
 * @param string $hosts['fields']['ipmi_password'] IPMI password. OPTIONAL
 * @return boolean
 */
	public static function massUpdate($data){
		$hosts = zbx_toArray($data['hosts']);
		$hostids = zbx_objectValues($hosts, 'hostid');

		try{
			self::BeginTransaction(__METHOD__);

			$options = array(
				'hostids' => $hostids,
				'editable' => 1,
				'output' => API_OUTPUT_EXTEND,
				'preservekeys' => 1,
			);
			$upd_hosts = self::get($options);
			foreach($hosts as $hnum => $host){
				if(!isset($upd_hosts[$host['hostid']])){
					self::exception(ZBX_API_ERROR_PERMISSIONS, S_NO_PERMISSION);
				}
			}

// CHECK IF HOSTS HAVE AT LEAST 1 GROUP {{{
			if(isset($data['groups']) && empty($data['groups'])){
				self::exception(ZBX_API_ERROR_PARAMETERS, 'No groups for hosts');
			}
// }}} CHECK IF HOSTS HAVE AT LEAST 1 GROUP


// UPDATE HOSTS PROPERTIES {{{
			if(isset($data['host'])){
				if(count($hosts) > 1){
					self::exception(ZBX_API_ERROR_PARAMETERS, 'Cannot mass update host name');
				}

				$cur_host = reset($hosts);

				$options = array(
					'filter' => array(
						'host' => $cur_host['host']),
					'output' => API_OUTPUT_SHORTEN,
					'editable' => 1,
					'nopermissions' => 1
				);
				$host_exists = self::get($options);
				$host_exist = reset($host_exists);
				if($host_exist && ($host_exist['hostid'] != $cur_host['hostid'])){
					self::exception(ZBX_API_ERROR_PARAMETERS, S_HOST.' [ '.$data['host'].' ] '.S_ALREADY_EXISTS_SMALL);
				}

//can't add host with the same name as existing template
				if(CTemplate::exists(array('host' => $cur_host['host'])))
					self::exception(ZBX_API_ERROR_PARAMETERS, S_TEMPLATE.' [ '.$cur_host['host'].' ] '.S_ALREADY_EXISTS_SMALL);
			}

			if(isset($data['host']) && !preg_match('/^'.ZBX_PREG_HOST_FORMAT.'$/i', $data['host'])){
				self::exception(ZBX_API_ERROR_PARAMETERS, 'Incorrect characters used for Hostname [ '.$data['host'].' ]');
			}

			$update = array(
				'values' => $data,
				'where' => array(DBcondition('hostid', $hostids))
			);
			DB::update('hosts', $update);
			if(isset($data['status']))
				update_host_status($hostids, $data['status']);

// }}} UPDATE HOSTS PROPERTIES


// UPDATE HOSTGROUPS LINKAGE {{{
			if(isset($data['groups']) && !is_null($data['groups'])){
				$data['groups'] = zbx_toArray($data['groups']);

				$host_groups = CHostGroup::get(array('hostids' => $hostids));
				$host_groupids = zbx_objectValues($host_groups, 'groupid');
				$new_groupids = zbx_objectValues($data['groups'], 'groupid');

				$groups_to_add = array_diff($new_groupids, $host_groupids);

				if(!empty($groups_to_add)){
					$result = self::massAdd(array(
						'hosts' => $hosts,
						'groups' => zbx_toObject($groups_to_add, 'groupid')
					));
					if(!$result){
						self::exception(ZBX_API_ERROR_PARAMETERS, 'Can\'t add group');
					}
				}

				$groupids_to_del = array_diff($host_groupids, $new_groupids);

				if(!empty($groupids_to_del)){
					$result = self::massRemove(array('hostids' => $hostids, 'groupids' => $groupids_to_del));
					if(!$result){
						self::exception(ZBX_API_ERROR_PARAMETERS, 'Can\'t remove group');
					}
				}
			}
// }}} UPDATE HOSTGROUPS LINKAGE


			$data['templates_clear'] = isset($data['templates_clear']) ? zbx_toArray($data['templates_clear']) : array();
			$templateids_clear = zbx_objectValues($data['templates_clear'], 'templateid');

			if(!empty($data['templates_clear'])){
				$result = self::massRemove(array(
					'hostids' => $hostids,
					'templateids_clear' => $templateids_clear,
				));
			}


// UPDATE TEMPLATE LINKAGE {{{
			if(isset($data['templates']) && !is_null($data['templates'])){
				$opt = array(
					'hostids' => $hostids,
					'output' => API_OUTPUT_SHORTEN,
					'preservekeys' => true,
				);
				$host_templates = CTemplate::get($opt);

				$host_templateids = array_keys($host_templates);
				$new_templateids = zbx_objectValues($data['templates'], 'templateid');

				$templates_to_del = array_diff($host_templateids, $new_templateids);
				$templates_to_del = array_diff($templates_to_del, $templateids_clear);

				if(!empty($templates_to_del)){
					$result = self::massRemove(array('hostids' => $hostids, 'templateids' => $templates_to_del));
					if(!$result){
						self::exception(ZBX_API_ERROR_PARAMETERS, S_CANNOT_UNLINK_TEMPLATE);
					}
				}

				$data = array('hosts' => $hosts, 'templates' => $data['templates']);
				$result = self::massAdd($data);
				if(!$result){
					self::exception(ZBX_API_ERROR_PARAMETERS, S_CANNOT_LINK_TEMPLATE);
				}
			}
// }}} UPDATE TEMPLATE LINKAGE


// UPDATE INTERFACES {{{
			if(isset($data['interfaces']) && !is_null($data['interfaces'])){
				$hostInterfaces = CHostInterface::get(array(
					'hostids' => $hostids,
					'output' => API_OUTPUT_EXTEND,
					'preservekeys' => true,
					'nopermissions' => 1
				));

				self::massRemove(array('hosts' => $hosts, 'interfaces' => $hostInterfaces));
				self::massAdd(array('hosts' => $hosts, 'interfaces' => $data['interfaces']));
			}
// }}} UPDATE INTERFACES

// UPDATE MACROS {{{
			if(isset($data['macros']) && !is_null($data['macros'])){
				$macrosToAdd = zbx_toHash($data['macros'], 'macro');

				$hostMacros = CUserMacro::get(array(
					'hostids' => $hostids,
					'output' => API_OUTPUT_EXTEND,
				));
				$hostMacros = zbx_toHash($hostMacros, 'macro');

// Delete
				$macrosToDelete = array();
				foreach($hostMacros as $hmnum => $hmacro){
					if(!isset($macrosToAdd[$hmacro['macro']])){
						$macrosToDelete[] = $hmacro['macro'];
					}
				}
// Update
				$macrosToUpdate = array();
				foreach($macrosToAdd as $nhmnum => $nhmacro){
					if(isset($hostMacros[$nhmacro['macro']])){
						$macrosToUpdate[] = $nhmacro;
						unset($macrosToAdd[$nhmnum]);
					}
				}
//----

				if(!empty($macrosToDelete)){
					$result = self::massRemove(array('hostids' => $hostids, 'macros' => $macrosToDelete));
					if(!$result){
						self::exception(ZBX_API_ERROR_PARAMETERS, 'Can\'t remove macro');
					}
				}

				if(!empty($macrosToUpdate)){
					$result = CUsermacro::massUpdate(array('hosts' => $hosts, 'macros' => $macrosToUpdate));
					if(!$result){
						self::exception(ZBX_API_ERROR_PARAMETERS, 'Cannot update macro');
					}
				}

				if(!empty($macrosToAdd)){
					$result = self::massAdd(array('hosts' => $hosts, 'macros' => $macrosToAdd));
					if(!$result){
						self::exception(ZBX_API_ERROR_PARAMETERS, 'Cannot add macro');
					}
				}
			}
// }}} UPDATE MACROS


// PROFILE {{{
			if(isset($data['profile']) && !is_null($data['profile'])){
				if(empty($data['profile'])){
					$sql = 'DELETE FROM hosts_profiles WHERE '.DBcondition('hostid', $hostids);
					if(!DBexecute($sql))
						self::exception(ZBX_API_ERROR_PARAMETERS, 'Cannot delete profile');
				}
				else{
					$existing_profiles = array();
					$existing_profiles_db = DBselect('SELECT hostid FROM hosts_profiles WHERE '.DBcondition('hostid', $hostids));
					while($existing_profile = DBfetch($existing_profiles_db)){
						$existing_profiles[] = $existing_profile['hostid'];
					}

					$hostids_without_profile = array_diff($hostids, $existing_profiles);

					$fields = array_keys($data['profile']);
					$fields = implode(', ', $fields);

					$values = array_map('zbx_dbstr', $data['profile']);
					$values = implode(', ', $values);

					foreach($hostids_without_profile as $hostid){
						$sql = 'INSERT INTO hosts_profiles (hostid, '.$fields.') VALUES ('.$hostid.', '.$values.')';
						if(!DBexecute($sql))
							self::exception(ZBX_API_ERROR_PARAMETERS, 'Cannot create profile');
					}

					if(!empty($existing_profiles)){
						$host_profile_fields = array('devicetype', 'name', 'os', 'serialno', 'tag','macaddress', 'hardware', 'software',
							'contact', 'location', 'notes');
						$sql_set = array();
						foreach($host_profile_fields as $field){
							if(isset($data['profile'][$field])) $sql_set[] = $field.'='.zbx_dbstr($data['profile'][$field]);
						}

						$sql = 'UPDATE hosts_profiles SET ' . implode(', ', $sql_set) . ' WHERE '.DBcondition('hostid', $existing_profiles);
						if(!DBexecute($sql))
							self::exception(ZBX_API_ERROR_PARAMETERS, 'Cannot update profile');
					}
				}
			}
// }}} PROFILE


// EXTENDED PROFILE {{{
			if(isset($data['extendedProfile']) && !is_null($data['extendedProfile'])){
				if(empty($data['extendedProfile'])){
					$sql = 'DELETE FROM hosts_profiles_ext WHERE '.DBcondition('hostid', $hostids);
					if(!DBexecute($sql))
						self::exception(ZBX_API_ERROR_PARAMETERS, 'Cannot delete extended profile');
				}
				else{
					$existing_profiles = array();
					$existing_profiles_db = DBselect('SELECT hostid FROM hosts_profiles_ext WHERE '.DBcondition('hostid', $hostids));
					while($existing_profile = DBfetch($existing_profiles_db)){
						$existing_profiles[] = $existing_profile['hostid'];
					}

					$hostids_without_profile = array_diff($hostids, $existing_profiles);

					$fields = array_keys($data['extendedProfile']);
					$fields = implode(', ', $fields);

					$values = array_map('zbx_dbstr', $data['extendedProfile']);
					$values = implode(', ', $values);

					foreach($hostids_without_profile as $hostid){
						$sql = 'INSERT INTO hosts_profiles_ext (hostid, '.$fields.') VALUES ('.$hostid.', '.$values.')';
						if(!DBexecute($sql))
							self::exception(ZBX_API_ERROR_PARAMETERS, 'Cannot create extended profile');
					}

					if(!empty($existing_profiles)){

						$host_profile_ext_fields = array('device_alias','device_type','device_chassis','device_os','device_os_short',
							'device_hw_arch','device_serial','device_model','device_tag','device_vendor','device_contract',
							'device_who','device_status','device_app_01','device_app_02','device_app_03','device_app_04',
							'device_app_05','device_url_1','device_url_2','device_url_3','device_networks','device_notes',
							'device_hardware','device_software','ip_subnet_mask','ip_router','ip_macaddress','oob_ip',
							'oob_subnet_mask','oob_router','date_hw_buy','date_hw_install','date_hw_expiry','date_hw_decomm','site_street_1',
							'site_street_2','site_street_3','site_city','site_state','site_country','site_zip','site_rack','site_notes',
							'poc_1_name','poc_1_email','poc_1_phone_1','poc_1_phone_2','poc_1_cell','poc_1_screen','poc_1_notes','poc_2_name',
							'poc_2_email','poc_2_phone_1','poc_2_phone_2','poc_2_cell','poc_2_screen','poc_2_notes');

						$sql_set = array();
						foreach($host_profile_ext_fields as $field){
							if(isset($data['extendedProfile'][$field])) $sql_set[] = $field.'='.zbx_dbstr($data['extendedProfile'][$field]);
						}

						$sql = 'UPDATE hosts_profiles_ext SET ' . implode(', ', $sql_set) . ' WHERE '.DBcondition('hostid', $existing_profiles);
						if(!DBexecute($sql))
							self::exception(ZBX_API_ERROR_PARAMETERS, 'Cannot update extended profile');
					}
				}
			}
// }}} EXTENDED PROFILE

			self::EndTransaction(true, __METHOD__);
			return array('hostids' => $hostids);
		}
		catch(APIException $e){
			self::EndTransaction(false, __METHOD__);
			$error = $e->getErrors();
			$error = reset($error);
			self::setError(__METHOD__, $e->getCode(), $error);
			return false;
		}
	}

/**
 * remove Hosts from HostGroups. All Hosts are removed from all HostGroups.
 *
 * @param array $data
 * @param array $data['hostids']
 * @param array $data['groupids']
 * @param array $data['templateids']
 * @param array $data['macroids']
 * @return array
 */
	public static function massRemove($data){
		$hostids = zbx_toArray($data['hostids']);

		try{
			self::BeginTransaction(__METHOD__);

			$options = array(
				'hostids' => $hostids,
				'editable' => 1,
				'preservekeys' => 1,
				'output' => API_OUTPUT_SHORTEN,
			);
			$upd_hosts = self::get($options);
			foreach($hostids as $hostid){
				if(!isset($upd_hosts[$hostid])){
					self::exception(ZBX_API_ERROR_PERMISSIONS, S_NO_PERMISSION);
				}
			}

			if(isset($data['groupids'])){
				$options = array(
					'hostids' => $hostids,
					'groupids' => zbx_toArray($data['groupids'])
				);
				$result = CHostGroup::massRemove($options);
				if(!$result) self::exception();
			}

			if(isset($data['templateids'])){
				$options = array(
					'hostids' => $hostids,
					'templateids' => zbx_toArray($data['templateids'])
				);
				$result = CTemplate::massRemove($options);
				if(!$result) self::exception();
			}

			if(isset($data['templateids_clear'])){
				$options = array(
					'templateids' => $hostids,
					'templateids_clear' => zbx_toArray($data['templateids_clear'])
				);
				$result = CTemplate::massRemove($options);
				if(!$result) self::exception();
			}

			if(isset($data['macros'])){
				$options = array(
					'hostids' => $hostids,
					'macros' => zbx_toArray($data['macros'])
				);
				$result = CUserMacro::massRemove($options);
				if(!$result) self::exception();
			}

			if(isset($data['interfaces'])){
				$options = array(
					'hostids' => $hostids,
					'interfaces' => zbx_toArray($data['interfaces'])
				);
				$result = CHostInterface::massRemove($options);
				if(!$result) self::exception();
			}

			self::EndTransaction(true, __METHOD__);
			return array('hostids' => $hostids);
		}
		catch(APIException $e){
			self::EndTransaction(false, __METHOD__);
			$error = $e->getErrors();
			$error = reset($error);
			self::setError(__METHOD__, $e->getCode(), $error);
			return false;
		}
	}

/**
 * Delete Host
 *
 * @param array $hosts
 * @param array $hosts[0, ...]['hostid'] Host ID to delete
 * @return array|boolean
 */
	public static function delete($hosts){
		if(empty($hosts)) return true;

		$hosts = zbx_toArray($hosts);
		$hostids = zbx_objectValues($hosts, 'hostid');

		try{
			self::BeginTransaction(__METHOD__);

			self::checkInput($hosts, __FUNCTION__);

// delete items -> triggers -> graphs
			$delItems = CItem::get(array(
				'hostids' => $hostids,
				'nopermissions' => 1,
				'preservekeys' => 1
			));

			CItem::delete($delItems, true);

// delete host interfaces
			DB::delete('interface', array('hostid'=>$hostids));

// delete web tests
			$del_httptests = array();
			$db_httptests = get_httptests_by_hostid($hostids);
			while($db_httptest = DBfetch($db_httptests)){
				$del_httptests[$db_httptest['httptestid']] = $db_httptest['httptestid'];
			}
			if(!empty($del_httptests)){
				CWebCheck::delete($del_httptests);
			}


// delete screen items
			DB::delete('screens_items', array(
					'resourceid'=>$hostids,
					'resourcetype'=>SCREEN_RESOURCE_HOST_TRIGGERS
			));

// delete host from maps
			delete_sysmaps_elements_with_hostid($hostids);

// delete host from maintenances
			DB::delete('maintenances_hosts', array('hostid'=>$hostids));

// delete host from group
			DB::delete('hosts_groups', array('hostid'=>$hostids));

// delete host from template linkages
			DB::delete('hosts_templates', array('hostid'=>$hostids));

// disable actions
			$actionids = array();

// conditions
			$sql = 'SELECT DISTINCT actionid '.
					' FROM conditions '.
					' WHERE conditiontype='.CONDITION_TYPE_HOST.
						' AND '.DBcondition('value',$hostids, false, true);		// FIXED[POSIBLE value type violation]!!!
			$db_actions = DBselect($sql);
			while($db_action = DBfetch($db_actions)){
				$actionids[$db_action['actionid']] = $db_action['actionid'];
			}

// operations
			$sql = 'SELECT DISTINCT o.actionid '.
					' FROM operations o '.
					' WHERE o.operationtype IN ('.OPERATION_TYPE_GROUP_ADD.','.OPERATION_TYPE_GROUP_REMOVE.') '.
						' AND '.DBcondition('o.objectid',$hostids);
			$db_actions = DBselect($sql);
			while($db_action = DBfetch($db_actions)){
				$actionids[$db_action['actionid']] = $db_action['actionid'];
			}


			if(!empty($actionids)){
				$update = array();
				$update[] = array(
					'values' => array('status' => ACTION_STATUS_DISABLED),
					'where' => array(DBcondition('actionid',$actionids))
				);
				DB::update('actions', $update);
			}

// delete action conditions
			DB::delete('conditions', array(
				'conditiontype'=>CONDITION_TYPE_HOST,
				'value'=>$hostids
			));

// delete action operations
			DB::delete('operations', array(
				'operationtype'=>array(OPERATION_TYPE_TEMPLATE_ADD, OPERATION_TYPE_TEMPLATE_REMOVE),
				'objectid'=>$hostids
			));

// delete host profile
			DB::delete('hosts_profiles', array('hostid'=>$hostids));
			DB::delete('hosts_profiles_ext', array('hostid'=>$hostids));

// delete host applications
			DB::delete('applications', array('hostid'=>$hostids));

// delete host
			DB::delete('hosts', array('hostid'=>$hostids));

// TODO: remove info from API
			foreach($hosts as $hnum => $host) {
				info(S_HOST_HAS_BEEN_DELETED_MSG_PART1.SPACE.$host['host'].SPACE.S_HOST_HAS_BEEN_DELETED_MSG_PART2);
				add_audit_ext(AUDIT_ACTION_DELETE, AUDIT_RESOURCE_HOST, $host['hostid'], $host['host'], 'hosts', NULL, NULL);
			}

			self::EndTransaction(true, __METHOD__);
			return array('hostids' => $hostids);
		}
		catch(APIException $e){
			self::EndTransaction(false, __METHOD__);
			$error = $e->getErrors();
			$error = reset($error);
			self::setError(__METHOD__, $e->getCode(), $error);
			return false;
		}
	}
}
?>
