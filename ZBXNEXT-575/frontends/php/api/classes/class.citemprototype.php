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
 * @package API
 */

class CItemprototype extends CZBXAPI{

/**
 * Get Itemprototype data
 */
	public static function get($options=array()){
		global $USER_DETAILS;

		$result = array();
		$user_type = $USER_DETAILS['type'];
		$userid = $USER_DETAILS['userid'];

		$sort_columns = array('itemid','description','key_','delay','type','status'); // allowed columns for sorting
		$subselects_allowed_outputs = array(API_OUTPUT_REFER, API_OUTPUT_EXTEND, API_OUTPUT_CUSTOM); // allowed output options for [ select_* ] params

		$sql_parts = array(
			'select' => array('items' => 'i.itemid'),
			'from' => array('items' => 'items i'),
			'where' => array('i.flags='.ZBX_FLAG_DISCOVERY_CHILD),
			'group' => array(),
			'order' => array(),
			'limit' => null);

		$def_options = array(
			'nodeids'				=> null,
			'groupids'				=> null,
			'templateids'			=> null,
			'hostids'				=> null,
			'itemids'				=> null,
			'discoveryids'			=> null,
			'inherited'				=> null,
			'templated'				=> null,
			'monitored'				=> null,
			'editable'				=> null,
			'nopermissions'			=> null,

// filter
			'filter'				=> null,
			'search'				=> null,
			'searchByAny'			=> null,
			'startSearch'				=> null,
			'excludeSearch'			=> null,

// OutPut
			'output'				=> API_OUTPUT_REFER,
			'selectHosts'			=> null,
			'select_applications'	=> null,
			'select_triggers'		=> null,
			'select_graphs'			=> null,
			'countOutput'			=> null,
			'groupCount'			=> null,
			'preservekeys'			=> null,

			'sortfield'				=> '',
			'sortorder'				=> '',
			'limit'					=> null,
			'limitSelects'			=> null
		);
		$options = zbx_array_merge($def_options, $options);


		if(is_array($options['output'])){
			unset($sql_parts['select']['items']);

			$dbTable = DB::getSchema('items');
			$sql_parts['select']['itemid'] = ' i.itemid';
			foreach($options['output'] as $key => $field){
				if(isset($dbTable['fields'][$field]))
					$sql_parts['select'][$field] = ' i.'.$field;
			}

			$options['output'] = API_OUTPUT_CUSTOM;
		}

// editable + PERMISSION CHECK
		if((USER_TYPE_SUPER_ADMIN == $user_type) || $options['nopermissions']){
		}
		else{
			$permission = $options['editable']?PERM_READ_WRITE:PERM_READ_ONLY;

			$sql_parts['from']['hosts_groups'] = 'hosts_groups hg';
			$sql_parts['from']['rights'] = 'rights r';
			$sql_parts['from']['users_groups'] = 'users_groups ug';
			$sql_parts['where'][] = 'hg.hostid=i.hostid';
			$sql_parts['where'][] = 'r.id=hg.groupid ';
			$sql_parts['where'][] = 'r.groupid=ug.usrgrpid';
			$sql_parts['where'][] = 'ug.userid='.$userid;
			$sql_parts['where'][] = 'r.permission>='.$permission;
			$sql_parts['where'][] = 'NOT EXISTS( '.
								' SELECT hgg.groupid '.
								' FROM hosts_groups hgg, rights rr, users_groups gg '.
								' WHERE hgg.hostid=hg.hostid '.
									' AND rr.id=hgg.groupid '.
									' AND rr.groupid=gg.usrgrpid '.
									' AND gg.userid='.$userid.
									' AND rr.permission<'.$permission.')';
		}

// nodeids
		$nodeids = !is_null($options['nodeids']) ? $options['nodeids'] : get_current_nodeid();

// templateids
		if(!is_null($options['templateids'])){
			zbx_value2array($options['templateids']);

			if(!is_null($options['hostids'])){
				zbx_value2array($options['hostids']);
				$options['hostids'] = array_merge($options['hostids'], $options['templateids']);
			}
			else{
				$options['hostids'] = $options['templateids'];
			}
		}

// hostids
		if(!is_null($options['hostids'])){
			zbx_value2array($options['hostids']);

			if($options['output'] != API_OUTPUT_EXTEND){
				$sql_parts['select']['hostid'] = 'i.hostid';
			}

			$sql_parts['where']['hostid'] = DBcondition('i.hostid', $options['hostids']);

			if(!is_null($options['groupCount'])){
				$sql_parts['group']['i'] = 'i.hostid';
			}
		}

// itemids
		if(!is_null($options['itemids'])){
			zbx_value2array($options['itemids']);

			$sql_parts['where']['itemid'] = DBcondition('i.itemid', $options['itemids']);
		}

// discoveryids
		if(!is_null($options['discoveryids'])){
			zbx_value2array($options['discoveryids']);

			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['discoveryid'] = 'id.parent_itemid';
			}

			$sql_parts['from']['item_discovery'] = 'item_discovery id';
			$sql_parts['where'][] = DBcondition('id.parent_itemid', $options['discoveryids']);
			$sql_parts['where']['idi'] = 'i.itemid=id.itemid';

			if(!is_null($options['groupCount'])){
				$sql_parts['group']['id'] = 'id.parent_itemid';
			}
		}

// inherited
		if(!is_null($options['inherited'])){
			if($options['inherited'])
				$sql_parts['where'][] = 'i.templateid IS NOT NULL';
			else
				$sql_parts['where'][] = 'i.templateid IS NULL';
		}

// templated
		if(!is_null($options['templated'])){
			$sql_parts['from']['hosts'] = 'hosts h';
			$sql_parts['where']['hi'] = 'h.hostid=i.hostid';

			if($options['templated'])
				$sql_parts['where'][] = 'h.status='.HOST_STATUS_TEMPLATE;
			else
				$sql_parts['where'][] = 'h.status<>'.HOST_STATUS_TEMPLATE;
		}

// monitored
		if(!is_null($options['monitored'])){
			$sql_parts['from']['hosts'] = 'hosts h';
			$sql_parts['where']['hi'] = 'h.hostid=i.hostid';

			if($options['monitored']){
				$sql_parts['where'][] = 'h.status='.HOST_STATUS_MONITORED;
				$sql_parts['where'][] = 'i.status='.ITEM_STATUS_ACTIVE;
			}
			else{
				$sql_parts['where'][] = '(h.status<>'.HOST_STATUS_MONITORED.' OR i.status<>'.ITEM_STATUS_ACTIVE.')';
			}
		}


// search
		if(is_array($options['search'])){
			zbx_db_search('items i', $options, $sql_parts);
		}

// --- FILTER ---
		if(is_array($options['filter'])){
			zbx_db_filter('items i', $options, $sql_parts);

			if(isset($options['filter']['host'])){
				zbx_value2array($options['filter']['host']);

				$sql_parts['from']['hosts'] = 'hosts h';
				$sql_parts['where']['hi'] = 'h.hostid=i.hostid';
				$sql_parts['where']['h'] = DBcondition('h.host', $options['filter']['host']);
			}
		}

// output
		if($options['output'] == API_OUTPUT_EXTEND){
			$sql_parts['select']['items'] = 'i.*';
		}

// countOutput
		if(!is_null($options['countOutput'])){
			$options['sortfield'] = '';
			$sql_parts['select'] = array('count(DISTINCT i.itemid) as rowscount');

//groupCount
			if(!is_null($options['groupCount'])){
				foreach($sql_parts['group'] as $key => $fields){
					$sql_parts['select'][$key] = $fields;
				}
			}
		}

// order
// restrict not allowed columns for sorting
		$options['sortfield'] = str_in_array($options['sortfield'], $sort_columns) ? $options['sortfield'] : '';
		if(!zbx_empty($options['sortfield'])){
			$sortorder = ($options['sortorder'] == ZBX_SORT_DOWN)?ZBX_SORT_DOWN:ZBX_SORT_UP;

			$sql_parts['order'][] = 'i.'.$options['sortfield'].' '.$sortorder;

			if(!str_in_array('i.'.$options['sortfield'], $sql_parts['select'])
					&& !str_in_array('i.*', $sql_parts['select'])){
				$sql_parts['select'][] = 'i.'.$options['sortfield'];
			}
		}

// limit
		if(zbx_ctype_digit($options['limit']) && $options['limit']){
			$sql_parts['limit'] = $options['limit'];
		}
//----------

		$itemids = array();

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
		if(!empty($sql_parts['where']))		$sql_where.= ' AND '.implode(' AND ',$sql_parts['where']);
		if(!empty($sql_parts['group']))		$sql_where.= ' GROUP BY '.implode(',',$sql_parts['group']);
		if(!empty($sql_parts['order']))		$sql_order.= ' ORDER BY '.implode(',',$sql_parts['order']);
		$sql_limit = $sql_parts['limit'];

		$sql = 'SELECT '.zbx_db_distinct($sql_parts).' '.$sql_select.
				' FROM '.$sql_from.
				' WHERE '.DBin_node('i.itemid', $nodeids).
					$sql_where.
				$sql_group.
				$sql_order;
//SDI($sql);
		$res = DBselect($sql, $sql_limit);
		while($item = DBfetch($res)){
			if(!is_null($options['countOutput'])){
				if(!is_null($options['groupCount']))
					$result[] = $item;
				else
					$result = $item['rowscount'];
			}
			else{
				$itemids[$item['itemid']] = $item['itemid'];

				if($options['output'] == API_OUTPUT_SHORTEN){
					$result[$item['itemid']] = array('itemid' => $item['itemid']);
				}
				else{
					if(!isset($result[$item['itemid']]))
						$result[$item['itemid']]= array();

					if(!is_null($options['selectHosts']) && !isset($result[$item['itemid']]['hosts'])){
						$result[$item['itemid']]['hosts'] = array();
					}
					if(!is_null($options['select_applications']) && !isset($result[$item['itemid']]['applications'])){
						$result[$item['itemid']]['applications'] = array();
					}
					if(!is_null($options['select_triggers']) && !isset($result[$item['itemid']]['triggers'])){
						$result[$item['itemid']]['triggers'] = array();
					}
					if(!is_null($options['select_graphs']) && !isset($result[$item['itemid']]['graphs'])){
						$result[$item['itemid']]['graphs'] = array();
					}

// hostids
					if(isset($item['hostid']) && is_null($options['selectHosts'])){
						if(!isset($result[$item['itemid']]['hosts'])) $result[$item['itemid']]['hosts'] = array();
						$result[$item['itemid']]['hosts'][] = array('hostid' => $item['hostid']);
					}

// triggerids
					if(isset($item['triggerid']) && is_null($options['select_triggers'])){
						if(!isset($result[$item['itemid']]['triggers']))
							$result[$item['itemid']]['triggers'] = array();

						$result[$item['itemid']]['triggers'][] = array('triggerid' => $item['triggerid']);
						unset($item['triggerid']);
					}
// graphids
					if(isset($item['graphid']) && is_null($options['select_graphs'])){
						if(!isset($result[$item['itemid']]['graphs']))
							$result[$item['itemid']]['graphs'] = array();

						$result[$item['itemid']]['graphs'][] = array('graphid' => $item['graphid']);
						unset($item['graphid']);
					}
// discoveryids
					if(isset($item['discoveryids'])){
						if(!isset($result[$item['itemid']]['discovery']))
							$result[$item['itemid']]['discovery'] = array();

						$result[$item['itemid']]['discovery'][] = array('ruleid' => $item['item_parentid']);
						unset($item['item_parentid']);
					}

					$result[$item['itemid']] += $item;
				}
			}
		}

COpt::memoryPick();
		if(!is_null($options['countOutput'])){
			if(is_null($options['preservekeys'])) $result = zbx_cleanHashes($result);
			return $result;
		}

// Adding Objects
// Adding hosts
		if(!is_null($options['selectHosts'])){
			if(is_array($options['selectHosts']) || str_in_array($options['selectHosts'], $subselects_allowed_outputs)){
				$obj_params = array(
					'nodeids' => $nodeids,
					'itemids' => $itemids,
					'templated_hosts' => 1,
					'output' => $options['selectHosts'],
					'nopermissions' => 1,
					'preservekeys' => 1
				);
				$hosts = CHost::get($obj_params);

				foreach($hosts as $hostid => $host){
					$hitems = $host['items'];
					unset($host['items']);
					foreach($hitems as $inum => $item){
						$result[$item['itemid']]['hosts'][] = $host;
					}
				}

				$templates = CTemplate::get($obj_params);
				foreach($templates as $templateid => $template){
					$titems = $template['items'];
					unset($template['items']);
					foreach($titems as $inum => $item){
						$result[$item['itemid']]['hosts'][] = $template;
					}
				}
			}
		}

// Adding triggers
		if(!is_null($options['select_triggers'])){
			$obj_params = array(
				'nodeids' => $nodeids,
				'discoveryids' => $itemids,
				'preservekeys' => 1,
				'filter' => array('flags' => ZBX_FLAG_DISCOVERY_CHILD),
			);

			if(in_array($options['select_triggers'], $subselects_allowed_outputs)){
				$obj_params['output'] = $options['select_triggers'];
				$triggers = CTrigger::get($obj_params);

				if(!is_null($options['limitSelects'])) order_result($triggers, 'name');
				foreach($triggers as $triggerid => $trigger){
					unset($triggers[$triggerid]['items']);
					$count = array();
					foreach($trigger['items'] as $item){
						if(!is_null($options['limitSelects'])){
							if(!isset($count[$item['itemid']])) $count[$item['itemid']] = 0;
							$count[$item['itemid']]++;

							if($count[$item['itemid']] > $options['limitSelects']) continue;
						}

						$result[$item['itemid']]['triggers'][] = &$triggers[$triggerid];
					}
				}
			}
			else if(API_OUTPUT_COUNT == $options['select_triggers']){
				$obj_params['countOutput'] = 1;
				$obj_params['groupCount'] = 1;

				$triggers = CTrigger::get($obj_params);

				$triggers = zbx_toHash($triggers, 'parent_itemid');
				foreach($result as $itemid => $item){
					if(isset($triggers[$itemid]))
						$result[$itemid]['triggers'] = $triggers[$itemid]['rowscount'];
					else
						$result[$itemid]['triggers'] = 0;
				}
			}
		}

// Adding applications
		if(!is_null($options['select_applications']) && str_in_array($options['select_applications'], $subselects_allowed_outputs)){
			$obj_params = array(
				'nodeids' => $nodeids,
				'output' => $options['select_applications'],
				'itemids' => $itemids,
				'preservekeys' => 1
			);
			$applications = CApplication::get($obj_params);
			foreach($applications as $applicationid => $application){
				$aitems = $application['items'];
				unset($application['items']);
				foreach($aitems as $inum => $item){
					$result[$item['itemid']]['applications'][] = $application;
				}
			}
		}

// Adding graphs
		if(!is_null($options['select_graphs'])){
			$obj_params = array(
				'nodeids' => $nodeids,
				'discoveryids' => $itemids,
				'preservekeys' => 1,
				'filter' => array('flags' => ZBX_FLAG_DISCOVERY_CHILD),
			);

			if(in_array($options['select_graphs'], $subselects_allowed_outputs)){
				$obj_params['output'] = $options['select_graphs'];
				$graphs = CGraph::get($obj_params);

				if(!is_null($options['limitSelects'])) order_result($graphs, 'name');
				foreach($graphs as $graphid => $graph){
					unset($graphs[$graphid]['discoveries']);
					$count = array();
					foreach($graph['discoveries'] as $item){
						if(!is_null($options['limitSelects'])){
							if(!isset($count[$item['itemid']])) $count[$item['itemid']] = 0;
							$count[$item['itemid']]++;

							if($count[$item['itemid']] > $options['limitSelects']) continue;
						}

						$result[$item['itemid']]['graphs'][] = &$graphs[$graphid];
					}
				}
			}
			else if(API_OUTPUT_COUNT == $options['select_graphs']){
				$obj_params['countOutput'] = 1;
				$obj_params['groupCount'] = 1;

				$graphs = CGraph::get($obj_params);

				$graphs = zbx_toHash($graphs, 'parent_itemid');
				foreach($result as $itemid => $item){
					if(isset($graphs[$itemid]))
						$result[$itemid]['graphs'] = $graphs[$itemid]['rowscount'];
					else
						$result[$itemid]['graphs'] = 0;
				}
			}
		}

COpt::memoryPick();
		if(is_null($options['preservekeys'])){
			$result = zbx_cleanHashes($result);
		}

		return $result;
	}

	public static function exists($object){
		$options = array(
			'filter' => array('key_' => $object['key_']),
			'output' => API_OUTPUT_SHORTEN,
			'nopermissions' => 1,
			'limit' => 1
		);

		if(isset($object['hostid'])) $options['hostids'] = $object['hostid'];
		if(isset($object['host'])) $options['filter']['host'] = $object['host'];

		if(isset($object['node']))
			$options['nodeids'] = getNodeIdByNodeName($object['node']);
		else if(isset($object['nodeids']))
			$options['nodeids'] = $object['nodeids'];

		$objs = self::get($options);

		return !empty($objs);
	}

	public static function checkInput(&$items, $update=false){
// permissions
		if($update){
			$item_db_fields = array('itemid'=> null);
			$dbItems = self::get(array(
				'output' => API_OUTPUT_EXTEND,
				'itemids' => zbx_objectValues($items, 'itemid'),
				'editable' => 1,
				'preservekeys' => 1
			));
		}
		else{
			$item_db_fields = array('description'=>null, 'key_'=>null, 'hostid'=>null);
			$dbHosts = CHost::get(array(
				'hostids' => zbx_objectValues($items, 'hostid'),
				'templated_hosts' => 1,
				'editable' => 1,
				'preservekeys' => 1
			));
		}

		foreach($items as $inum => &$item){
			$current_item = $items[$inum];

			if(!check_db_fields($item_db_fields, $item)){
				self::exception(ZBX_API_ERROR_PARAMETERS, S_INCORRECT_ARGUMENTS_PASSED_TO_FUNCTION);
			}

			unset($item['templateid']);
			unset($item['lastvalue']);
			unset($item['prevvalue']);
			unset($item['lastclock']);
			unset($item['prevorgvalue']);
			unset($item['lastns']);

			//validating item key
			if(isset($item['key_'])){
				list($item_key_is_valid, $check_result) = check_item_key($item['key_']);
				if(!$item_key_is_valid){
					self::exception(ZBX_API_ERROR_PARAMETERS, _s('Error in item key: %s', $check_result));
				}
			}

			if($update){
				check_db_fields($dbItems[$item['itemid']], $current_item);

				if(!isset($dbItems[$item['itemid']]))
					self::exception(ZBX_API_ERROR_PARAMETERS, S_NO_PERMISSIONS);

				$restoreRules = array(
					'description'		=> array(),
					'key_'			=> array(),
					'hostid'		=> array(),
					'delay'			=> array('template' => 1),
					'history'		=> array('template' => 1),
					'status'		=> array('template' => 1),
					'type'			=> array(),
					'snmp_community'	=> array('template' => 1),
					'snmp_oid'		=> array(),
					'port'		=> array('template' => 1),
					'snmpv3_securityname'	=> array('template' => 1),
					'snmpv3_securitylevel'	=> array('template' => 1),
					'snmpv3_authpassphrase'	=> array('template' => 1),
					'snmpv3_privpassphrase'	=> array('template' => 1),
					'value_type'		=> array(),
					'data_type'		=> array(),
					'trapper_hosts'		=> array('template' =>1 ),
					'units'			=> array(),
					'multiplier'		=> array(),
					'delta'			=> array('template' => 1),
					'formula'		=> array(),
					'trends'		=> array('template' => 1),
					'logtimefmt'		=> array(),
					'valuemapid'		=> array('httptest' => 1),
					'authtype'		=> array('template' => 1),
					'username'		=> array('template' => 1),
					'password'		=> array('template' => 1),
					'publickey'		=> array('template' => 1),
					'privatekey'		=> array('template' => 1),
					'params'		=> array('template' => 1),
					'delay_flex'		=> array('template' => 1),
					'ipmi_sensor'		=> array()
				);

				foreach($restoreRules as $var_name => $info){
					if(!isset($info['template']) && (0 != $dbItems[$item['itemid']]['templateid'])){
						unset($item[$var_name]);
					}
				}

				if(!isset($item['key_'])) $item['key_'] = $dbItems[$item['itemid']]['key_'];
				if(!isset($item['hostid'])) $item['hostid'] = $dbItems[$item['itemid']]['hostid'];
			}
			else{
				if(!isset($dbHosts[$item['hostid']]))
					self::exception(ZBX_API_ERROR_PARAMETERS, S_NO_PERMISSIONS);
			}

			if((isset($item['port']) && !zbx_empty($item['port']))
				&& !((zbx_ctype_digit($item['port']) && ($item['port']>0) && ($item['port']<65535))
				|| preg_match('/^'.ZBX_PREG_EXPRESSION_USER_MACROS.'$/u', $item['port']))
			){
				self::exception(ZBX_API_ERROR_PARAMETERS,
					_s('Item [%1$s:%2$s] has invalid port: "%3$s".', $current_item['description'], $current_item['key_'], $item['port']));
			}

			if(isset($item['value_type'])){
				if($item['value_type'] == ITEM_VALUE_TYPE_STR) $item['delta']=0;
				if($item['value_type'] != ITEM_VALUE_TYPE_UINT64) $item['data_type'] = 0;
			}

			if(isset($item['key_'])){
				if(!preg_match('/^'.ZBX_PREG_ITEM_KEY_FORMAT.'$/u', $item['key_'])){
					self::exception(ZBX_API_ERROR_PARAMETERS, S_INCORRECT_KEY_FORMAT.SPACE."'key_name[param1,param2,...]'");
				}

				if(isset($item['type'])){
					if(($item['type'] == ITEM_TYPE_DB_MONITOR && $item['key_'] == 'db.odbc.select[<unique short description>]') ||
					   ($item['type'] == ITEM_TYPE_SSH && $item['key_'] == 'ssh.run[<unique short description>,<ip>,<port>,<encoding>]') ||
					   ($item['type'] == ITEM_TYPE_TELNET && $item['key_'] == 'telnet.run[<unique short description>,<ip>,<port>,<encoding>]'))
					{
						self::exception(ZBX_API_ERROR_PARAMETERS, S_ITEMS_CHECK_KEY_DEFAULT_EXAMPLE_PASSED);
					}

					if(isset($item['delay']) && isset($item['delay_flex'])){
						$res = calculate_item_nextcheck(0, $item['type'], $item['delay'], $item['delay_flex'], time());
						if($res['delay'] == SEC_PER_YEAR && $item['type'] != ITEM_TYPE_ZABBIX_ACTIVE && $item['type'] != ITEM_TYPE_TRAPPER){
							self::exception(ZBX_API_ERROR_PARAMETERS, S_ITEM_WILL_NOT_BE_REFRESHED_PLEASE_ENTER_A_CORRECT_UPDATE_INTERVAL);
						}
					}

					if($item['type'] == ITEM_TYPE_AGGREGATE){
						/* grpfunc['group','key','itemfunc','numeric param'] */
						if(preg_match('/^((.)*)(\[\"((.)*)\"\,\"((.)*)\"\,\"((.)*)\"\,\"([0-9]+)\"\])$/i', $item['key_'], $arr)){
							$g=$arr[1];
							if(!str_in_array($g,array("grpmax","grpmin","grpsum","grpavg"))){
								self::exception(ZBX_API_ERROR_PARAMETERS, S_GROUP_FUNCTION.SPACE."[$g]".SPACE.S_IS_NOT_ONE_OF.SPACE."[grpmax, grpmin, grpsum, grpavg]");
							}
							// Group
							$g=$arr[4];
							// Key
							$g=$arr[6];
							// Item function
							$g=$arr[8];
							if(!str_in_array($g, array('last', 'min', 'max', 'avg', 'sum','count'))){
								self::exception(ZBX_API_ERROR_PARAMETERS, S_ITEM_FUNCTION.SPACE.'['.$g.']'.SPACE.S_IS_NOT_ONE_OF.SPACE.'[last, min, max, avg, sum, count]');
							}
							// Parameter
							$g=$arr[10];
						}
						else{
							self::exception(ZBX_API_ERROR_PARAMETERS, S_KEY_DOES_NOT_MATCH.SPACE.'grpfunc["group","key","itemfunc","numeric param"]');
						}
					}
				}

				if(isset($item['value_type'])){
					if(preg_match('/^(log|logrt|eventlog)\[/', $item['key_']) && ($item['value_type'] != ITEM_VALUE_TYPE_LOG)){
						self::exception(ZBX_API_ERROR_PARAMETERS, S_TYPE_INFORMATION_BUST_LOG_FOR_LOG_KEY);
					}

					if(($item['type'] == ITEM_TYPE_AGGREGATE) && ($item['value_type'] != ITEM_VALUE_TYPE_FLOAT)){
						self::exception(ZBX_API_ERROR_PARAMETERS, S_VALUE_TYPE_MUST_FLOAT_FOR_AGGREGATE_ITEMS);
					}
				}
			}
		}
		unset($item);
	}
/**
 * Add Itemprototype
 *
 * @param array $items
 * @return array|boolean
 */
	public static function create($items){
		$items = zbx_toArray($items);

		try{
			self::BeginTransaction(__METHOD__);

			self::checkInput($items);

			self::createReal($items);

			self::inherit($items);

			self::EndTransaction(true, __METHOD__);

			return array('itemids' => zbx_objectValues($items, 'itemid'));
		}
		catch(APIException $e){
			self::EndTransaction(false, __METHOD__);
			$error = $e->getErrors();
			$error = reset($error);
			self::setError(__METHOD__, $e->getCode(), $error);
			return false;
		}
	}

	protected static function createReal(&$items){
		foreach($items as $key => $item){
			$itemsExists = CItem::get(array(
				'output' => API_OUTPUT_SHORTEN,
				'filter' => array(
					'hostid' => $item['hostid'],
					'key_' => $item['key_']
				),
				'nopermissions' => 1
			));
			foreach($itemsExists as $inum => $itemExists){
				self::exception(ZBX_API_ERROR_PARAMETERS, 'Host with item ['.$item['key_'].'] already exists');
			}
		}

		$itemids = DB::insert('items', $items);

		$itemApplications = $insert_item_discovery = array();
		foreach($items as $key => $item){
			$items[$key]['itemid'] = $itemids[$key];

			$insert_item_discovery[] = array(
				'itemid' => $items[$key]['itemid'],
				'parent_itemid' => $item['ruleid']
			);

			if(isset($item['applications'])){
				foreach($item['applications'] as $anum => $appid){
					if($appid == 0) continue;

					$itemApplications[] = array(
						'applicationid' => $appid,
						'itemid' => $items[$key]['itemid']
					);
				}
			}
		}

		DB::insert('item_discovery', $insert_item_discovery);

		if(!empty($itemApplications)){
			DB::insert('items_applications', $itemApplications);
		}

// TODO: REMOVE info
		$itemHosts = self::get(array(
			'itemids' => $itemids,
			'output' => array('key_'),
			'selectHosts' => array('host'),
			'nopermissions' => 1
		));
		foreach($itemHosts as $item){
			$host = reset($item['hosts']);
			info(S_ITEM_PROTOTYPE.' ['.$host['host'].':'.$item['key_'].'] '.S_CREATED_SMALL);
		}
	}

	protected static function updateReal($items){
		$items = zbx_toArray($items);

		$data = array();
		foreach($items as $inum => $item){
			$itemsExists = CItem::get(array(
				'output' => API_OUTPUT_SHORTEN,
				'filter' => array(
					'hostid' => $item['hostid'],
					'key_' => $item['key_']
				),
				'nopermissions' => 1
			));
			foreach($itemsExists as $inum => $itemExists){
				if($itemExists['itemid'] != $item['itemid']){
					self::exception(ZBX_API_ERROR_PARAMETERS, 'Host with item [ '.$item['key_'].' ] already exists');
				}
			}

			$data[] = array('values' => $item, 'where'=> array('itemid='.$item['itemid']));
		}
		$result = DB::update('items', $data);
		if(!$result) self::exception(ZBX_API_ERROR_PARAMETERS, 'DBerror');

		$itemids = array();
		$itemApplications = array();
		foreach($items as $key => $item){
			$itemids[] = $item['itemid'];

			if(!isset($item['applications'])) continue;
			foreach($item['applications'] as $anum => $appid){
				$itemApplications[] = array(
					'applicationid' => $appid,
					'itemid' => $item['itemid']
				);
			}
		}

		if(!empty($itemids)){
			DB::delete('items_applications', array('itemid'=>$itemids));
			DB::insert('items_applications', $itemApplications);
		}

// TODO: REMOVE info
		$itemHosts = self::get(array(
			'itemids' => $itemids,
			'output' => array('key_'),
			'selectHosts' => array('host'),
			'nopermissions' => 1,
		));
		foreach($itemHosts as $item){
			$host = reset($item['hosts']);
			info(S_ITEM_PROTOTYPE." [".$host['host'].':'.$item['key_']."] ".S_UPDATED_SMALL);
		}
	}

/**
 * Update Itemprototype
 *
 * @param array $items
 * @return boolean
 */
	public static function update($items){
		$items = zbx_toArray($items);

		try{
			self::BeginTransaction(__METHOD__);

			self::checkInput($items, true);

			self::updateReal($items);

			self::inherit($items);

			self::EndTransaction(true, __METHOD__);

			return array('itemids' => zbx_objectValues($items, 'itemid'));
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
 * Delete Itemprototypes
 *
 * @param array $ruleids
 * @return
 */
	public static function delete($prototypeids, $nopermissions=false){

		try{
			self::BeginTransaction(__METHOD__);

			if(empty($prototypeids)) self::exception(ZBX_API_ERROR_PARAMETERS, _('Empty input parameter'));

			$prototypeids = zbx_toHash($prototypeids);

			$options = array(
				'itemids' => $prototypeids,
				'editable' => 1,
				'preservekeys' => 1,
				'output' => API_OUTPUT_EXTEND,
			);
			$del_itemPrototypes = self::get($options);

// TODO: remove $nopermissions hack
			if(!$nopermissions){
				foreach($prototypeids as $prototypeid){
					if(!isset($del_itemPrototypes[$prototypeid])){
						self::exception(ZBX_API_ERROR_PERMISSIONS, S_NO_PERMISSIONS);
					}
					if($del_itemPrototypes[$prototypeid]['templateid'] != 0){
						self::exception(ZBX_API_ERROR_PARAMETERS, 'Cannot delete templated items');
					}
				}
			}

// first delete child items
			$parent_itemids = $prototypeids;
			$child_prototypeids = array();
			do{
				$db_items = DBselect('SELECT itemid FROM items WHERE ' . DBcondition('templateid', $parent_itemids));
				$parent_itemids = array();
				while($db_item = DBfetch($db_items)){
					$parent_itemids[$db_item['itemid']] = $db_item['itemid'];
					$child_prototypeids[$db_item['itemid']] = $db_item['itemid'];
				}
			}while(!empty($parent_itemids));

			$options = array(
				'output' => API_OUTPUT_EXTEND,
				'itemids' => $child_prototypeids,
				'nopermissions' => true,
				'preservekeys' => true,
			);
			$del_itemPrototypes_childs = self::get($options);

			$del_itemPrototypes = array_merge($del_itemPrototypes, $del_itemPrototypes_childs);
			$prototypeids = array_merge($prototypeids, $child_prototypeids);


// CREATED ITEMS
			$created_items = array();
			$sql = 'SELECT itemid FROM item_discovery WHERE '.DBcondition('parent_itemid', $prototypeids);
			$db_items = DBselect($sql);
			while($item = DBfetch($db_items)){
				$created_items[$item['itemid']] = $item['itemid'];
			}
			$result = CItem::delete($created_items, true);
			if(!$result) self::exception(ZBX_API_ERROR_PARAMETERS, 'Cannot delete item prototype');


// GRAPH PROTOTYPES
			$del_graphPrototypes = CGraphPrototype::get(array(
				'itemids' => $prototypeids,
				'output' => API_OUTPUT_SHORTEN,
				'nopermissions' => true,
				'preservekeys' => true,
			));
			$result = CGraphPrototype::delete(zbx_objectValues($del_graphPrototypes, 'graphid'), true);
			if(!$result) self::exception(ZBX_API_ERROR_PARAMETERS, 'Cannot delete graph prototype');


// TRIGGER PROTOTYPES
			$del_triggerPrototypes = CTriggerPrototype::get(array(
				'itemids' => $prototypeids,
				'output' => API_OUTPUT_SHORTEN,
				'nopermissions' => true,
				'preservekeys' => true,
			));
			$result = CTriggerPrototype::delete(zbx_objectValues($del_triggerPrototypes, 'triggerid'), true);
			if(!$result) self::exception(ZBX_API_ERROR_PARAMETERS, 'Cannot delete trigger prototype');


// ITEM PROTOTYPES
			DB::delete('items', array('itemid' => $prototypeids));


// HOUSEKEEPER {{{
			$item_data_tables = array(
				'trends',
				'trends_uint',
				'history_text',
				'history_log',
				'history_uint',
				'history_str',
				'history',
			);

			$insert = array();
			foreach($prototypeids as $id => $prototypeid){
				foreach($item_data_tables as $table){
					$insert[] = array(
						'tablename' => $table,
						'field' => 'itemid',
						'value' => $prototypeid,
					);
				}
			}
			DB::insert('housekeeper', $insert);
// }}} HOUSEKEEPER

// TODO: remove info from API
			foreach($del_itemPrototypes as $item){
				info(_s('Item prototype [%1$s:%2$s] deleted.', $item['description'], $item['key_']));
			}

			self::EndTransaction(true, __METHOD__);
			return array('prototypeids' => $prototypeids);
		}
		catch(APIException $e){
			self::EndTransaction(false, __METHOD__);
			$error = $e->getErrors();
			$error = reset($error);
			self::setError(__METHOD__, $e->getCode(), $error);
			return false;
		}
	}


	public static function syncTemplates($data){
		try{
			self::BeginTransaction(__METHOD__);

			$data['templateids'] = zbx_toArray($data['templateids']);
			$data['hostids'] = zbx_toArray($data['hostids']);

			$options = array(
				'hostids' => $data['hostids'],
				'editable' => 1,
				'preservekeys' => 1,
				'templated_hosts' => 1,
				'output' => API_OUTPUT_SHORTEN
			);
			$allowedHosts = CHost::get($options);
			foreach($data['hostids'] as $hostid){
				if(!isset($allowedHosts[$hostid])){
					self::exception(ZBX_API_ERROR_PERMISSIONS, S_NO_PERMISSION);
				}
			}
			$options = array(
				'templateids' => $data['templateids'],
				'preservekeys' => 1,
				'output' => API_OUTPUT_SHORTEN
			);
			$allowedTemplates = CTemplate::get($options);
			foreach($data['templateids'] as $templateid){
				if(!isset($allowedTemplates[$templateid])){
					self::exception(ZBX_API_ERROR_PERMISSIONS, S_NO_PERMISSION);
				}
			}

			$options = array(
				'hostids' => $data['templateids'],
				'preservekeys' => 1,
				'select_applications' => API_OUTPUT_REFER,
				'output' => API_OUTPUT_EXTEND,
			);
			$items = self::get($options);

			foreach($items as $inum => $item){
				$items[$inum]['applications'] = zbx_objectValues($item['applications'], 'applicationid');
			}

			self::inherit($items, $data['hostids']);

			self::EndTransaction(true, __METHOD__);
			return true;
		}
		catch(APIException $e){
			self::EndTransaction(false, __METHOD__);
			$error = $e->getErrors();
			$error = reset($error);
			self::setError(__METHOD__, $e->getCode(), $error);
			return false;
		}
	}

	protected static function inherit($items, $hostids=null){
		if(empty($items)) return $items;

		$items = zbx_toHash($items, 'itemid');

		$chdHosts = CHost::get(array(
			'templateids' => zbx_objectValues($items, 'hostid'),
			'hostids' => $hostids,
			'output' => array('hostid', 'host'),
			'preservekeys' => 1,
			'nopermissions' => 1,
			'templated_hosts' => 1
		));
		if(empty($chdHosts)) return true;

		$insertItems = array();
		$updateItems = array();
		foreach($chdHosts as $hostid => $host){
			$templateids = zbx_toHash($host['templates'], 'templateid');

// skip items not from parent templates of current host
			$parentItems = array();
			foreach($items as $itemid => $item){
				if(isset($templateids[$item['hostid']]))
					$parentItems[$itemid] = $item;
			}
//----

// check existing items to decide insert or update
			$exItems = self::get(array(
				'output' => array('itemid', 'key_', 'flags', 'templateid'),
				'hostids' => $hostid,
				'filter' => array('flags' => null),
				'preservekeys' => 1,
				'nopermissions' => 1
			));
			$exItemsKeys = zbx_toHash($exItems, 'key_');
			$exItemsTpl = zbx_toHash($exItems, 'templateid');

			foreach($parentItems as $itemid => $item){
				$exItem = null;

// update by tempalteid
				if(isset($exItemsTpl[$item['itemid']])){
					$exItem = $exItemsTpl[$item['itemid']];
				}

// update by key
				if(isset($item['key_']) && isset($exItemsKeys[$item['key_']])){
					$exItem = $exItemsKeys[$item['key_']];

					if($exItem['flags'] != ZBX_FLAG_DISCOVERY_CHILD){
						self::exception(ZBX_API_ERROR_PARAMETERS, S_AN_ITEM_WITH_THE_KEY.SPACE.'['.$exItem['key_'].']'.SPACE.S_ALREADY_EXISTS_FOR_HOST_SMALL.SPACE.'['.$host['host'].'].'.SPACE.S_THE_KEY_MUST_BE_UNIQUE);
					}
					else if(($exItem['templateid'] > 0) && ($exItem['templateid'] != $item['itemid'])){
						self::exception(ZBX_API_ERROR_PARAMETERS, S_AN_ITEM_WITH_THE_KEY.SPACE.'['.$exItem['key_'].']'.SPACE.S_ALREADY_EXISTS_FOR_HOST_SMALL.SPACE.'['.$host['host'].'].'.SPACE.S_THE_KEY_MUST_BE_UNIQUE);
					}
				}

// coping item
				$newItem = $item;
				$newItem['hostid'] = $host['hostid'];
				$newItem['templateid'] = $item['itemid'];

// setting item application
				if(isset($item['applications'])){
					$newItem['applications'] = get_same_applications_for_host($item['applications'], $host['hostid']);
				}
//--

				if($exItem){
					$newItem['itemid'] = $exItem['itemid'];
					$updateItems[] = $newItem;
				}
				else{
					$insertItems[$item['itemid']] = $newItem;
				}
			}
		}

		$sql = 'SELECT i.itemid ruleid, id.itemid itemid'.
				' FROM items i, item_discovery id'.
				' WHERE i.templateid=id.parent_itemid'.
					' AND '.DBcondition('id.itemid', array_keys($insertItems));
		$db_result = DBselect($sql);
		while($rule = DBfetch($db_result)){
			$insertItems[$rule['itemid']]['ruleid'] = $rule['ruleid'];
		}

		self::createReal($insertItems);
		self::updateReal($updateItems);

		$inheritedItems = array_merge($insertItems, $updateItems);

		self::inherit($inheritedItems);
	}
}
?>
