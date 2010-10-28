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
 * File containing CItem class for API.
 * @package API
 */
/**
 * Class containing methods for operations with Items
 *
 */
class CItem extends CZBXAPI{
/**
 * Get items data
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param array $options
 * @param array $options['itemids']
 * @param array $options['hostids']
 * @param array $options['groupids']
 * @param array $options['triggerids']
 * @param array $options['applicationids']
 * @param boolean $options['status']
 * @param boolean $options['templated_items']
 * @param boolean $options['editable']
 * @param boolean $options['count']
 * @param string $options['pattern']
 * @param int $options['limit']
 * @param string $options['order']
 * @return array|int item data as array or false if error
 */
	public static function get($options=array()){
		global $USER_DETAILS;

		$result = array();
		$user_type = $USER_DETAILS['type'];
		$userid = $USER_DETAILS['userid'];

		$sort_columns = array('itemid','description','key_','delay','history','trends','type','status'); // allowed columns for sorting
		$subselects_allowed_outputs = array(API_OUTPUT_REFER, API_OUTPUT_EXTEND, API_OUTPUT_CUSTOM); // allowed output options for [ select_* ] params

		$sql_parts = array(
			'select' => array('items' => 'i.itemid'),
			'from' => array('items' => 'items i'),
			'where' => array('webtype' => 'i.type<>9'),
			'group' => array(),
			'order' => array(),
			'limit' => null);

		$def_options = array(
			'nodeids'				=> null,
			'groupids'				=> null,
			'templateids'			=> null,
			'hostids'				=> null,
			'proxyids'				=> null,
			'itemids'				=> null,
			'graphids'				=> null,
			'triggerids'			=> null,
			'applicationids'		=> null,
			'discoveryids'			=> null,
			'webitems'				=> null,
			'inherited'				=> null,
			'templated'				=> null,
			'monitored'				=> null,
			'editable'				=> null,
			'nopermissions'			=> null,
// filter
			'filter'				=> null,

			'group'					=> null,
			'host'					=> null,
			'application'			=> null,

			'belongs'				=> null,
			'with_triggers'			=> null,
// filter
			'filter'				=> null,
			'search'				=> null,
			'startSearch'			=> null,
			'excludeSearch'			=> null,

// OutPut
			'output'				=> API_OUTPUT_REFER,
			'extendoutput'			=> null,
			'select_hosts'			=> null,
			'select_triggers'		=> null,
			'select_graphs'			=> null,
			'select_applications'	=> null,
			'select_prototypes'		=> null,
			'selectDiscoveryRule'	=> null,
			'countOutput'			=> null,
			'groupCount'			=> null,
			'preservekeys'			=> null,

			'sortfield'				=> '',
			'sortorder'				=> '',
			'limit'					=> null,
			'limitSelects'			=> null
		);


		$options = zbx_array_merge($def_options, $options);


		if(!is_null($options['extendoutput'])){
			$options['output'] = API_OUTPUT_EXTEND;

			if(!is_null($options['select_hosts'])){
				$options['select_hosts'] = API_OUTPUT_EXTEND;
			}
			if(!is_null($options['select_triggers'])){
				$options['select_triggers'] = API_OUTPUT_EXTEND;
			}
			if(!is_null($options['select_graphs'])){
				$options['select_graphs'] = API_OUTPUT_EXTEND;
			}
			if(!is_null($options['select_applications'])){
				$options['select_applications'] = API_OUTPUT_EXTEND;
			}
		}


		if(is_array($options['output'])){
			unset($sql_parts['select']['items']);
			foreach($options['output'] as $key => $field){
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

// groupids
		if(!is_null($options['groupids'])){
			zbx_value2array($options['groupids']);

			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['groupid'] = 'hg.groupid';
			}

			$sql_parts['from']['hosts_groups'] = 'hosts_groups hg';
			$sql_parts['where'][] = DBcondition('hg.groupid', $options['groupids']);
			$sql_parts['where'][] = 'hg.hostid=i.hostid';

			if(!is_null($options['groupCount'])){
				$sql_parts['group']['hg'] = 'hg.groupid';
			}
		}

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

// proxyids
		if(!is_null($options['proxyids'])){
			zbx_value2array($options['proxyids']);

			if($options['output'] != API_OUTPUT_EXTEND){
				$sql_parts['select']['proxyid'] = 'h.proxy_hostid';
			}

			$sql_parts['from']['hosts'] = 'hosts h';
			$sql_parts['where'][] = DBcondition('h.proxy_hostid', $options['proxyids']);
			$sql_parts['where'][] = 'h.hostid=i.hostid';

			if(!is_null($options['groupCount'])){
				$sql_parts['group']['h'] = 'h.proxy_hostid';
			}
		}

// itemids
		if(!is_null($options['itemids'])){
			zbx_value2array($options['itemids']);

			$sql_parts['where']['itemid'] = DBcondition('i.itemid', $options['itemids']);
		}

// triggerids
		if(!is_null($options['triggerids'])){
			zbx_value2array($options['triggerids']);

			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['triggerid'] = 'f.triggerid';
			}

			$sql_parts['from']['functions'] = 'functions f';
			$sql_parts['where'][] = DBcondition('f.triggerid', $options['triggerids']);
			$sql_parts['where']['if'] = 'i.itemid=f.itemid';
		}

// applicationids
		if(!is_null($options['applicationids'])){
			zbx_value2array($options['applicationids']);

			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['applicationid'] = 'ia.applicationid';
			}

			$sql_parts['from']['items_applications'] = 'items_applications ia';
			$sql_parts['where'][] = DBcondition('ia.applicationid', $options['applicationids']);
			$sql_parts['where']['ia'] = 'ia.itemid=i.itemid';
		}

// graphids
		if(!is_null($options['graphids'])){
			zbx_value2array($options['graphids']);

			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['graphid'] = 'gi.graphid';
			}

			$sql_parts['from']['graphs_items'] = 'graphs_items gi';
			$sql_parts['where'][] = DBcondition('gi.graphid', $options['graphids']);
			$sql_parts['where']['igi'] = 'i.itemid=gi.itemid';
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

// webitems
		if(!is_null($options['webitems'])){
			unset($sql_parts['where']['webtype']);
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
		if(is_null($options['filter']))
			$options['filter'] = array();

		if(is_array($options['filter'])){
			if(!array_key_exists('flags', $options['filter']))
    			$options['filter']['flags'] = array(ZBX_FLAG_DISCOVERY_NORMAL, ZBX_FLAG_DISCOVERY_CREATED);

			zbx_db_filter('items i', $options, $sql_parts);

			if(isset($options['filter']['host'])){
				zbx_value2array($options['filter']['host']);

				$sql_parts['from']['hosts'] = 'hosts h';
				$sql_parts['where']['hi'] = 'h.hostid=i.hostid';
				$sql_parts['where']['h'] = DBcondition('h.host', $options['filter']['host'], false, true);
			}
		}

// group
		if(!is_null($options['group'])){
			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['name'] = 'g.name';
			}

			$sql_parts['from']['groups'] = 'groups g';
			$sql_parts['from']['hosts_groups'] = 'hosts_groups hg';

			$sql_parts['where']['ghg'] = 'g.groupid = hg.groupid';
			$sql_parts['where']['hgi'] = 'hg.hostid=i.hostid';
			$sql_parts['where'][] = ' UPPER(g.name)='.zbx_dbstr(zbx_strtoupper($options['group']));
		}

// host
		if(!is_null($options['host'])){
			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['host'] = 'h.host';
			}

			$sql_parts['from']['hosts'] = 'hosts h';
			$sql_parts['where']['hi'] = 'h.hostid=i.hostid';
			$sql_parts['where'][] = ' UPPER(h.host)='.zbx_dbstr(zbx_strtoupper($options['host']));
		}

// application
		if(!is_null($options['application'])){
			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['application'] = 'a.name as application';
			}

			$sql_parts['from']['applications'] = 'applications a';
			$sql_parts['from']['items_applications'] = 'items_applications ia';

			$sql_parts['where']['aia'] = 'a.applicationid = ia.applicationid';
			$sql_parts['where']['iai'] = 'ia.itemid=i.itemid';
			$sql_parts['where'][] = ' UPPER(a.name)='.zbx_dbstr(zbx_strtoupper($options['application']));
		}


// with_triggers
		if(!is_null($options['with_triggers'])){
			if($options['with_triggers'] == 1)
				$sql_parts['where'][] = ' EXISTS ( SELECT functionid FROM functions ff WHERE ff.itemid=i.itemid )';
			else
				$sql_parts['where'][] = 'NOT EXISTS ( SELECT functionid FROM functions ff WHERE ff.itemid=i.itemid )';
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

			if(!str_in_array('i.'.$options['sortfield'], $sql_parts['select']) && !str_in_array('i.*', $sql_parts['select'])){
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

					if(!is_null($options['select_hosts']) && !isset($result[$item['itemid']]['hosts'])){
						$result[$item['itemid']]['hosts'] = array();
					}
					if(!is_null($options['select_triggers']) && !isset($result[$item['itemid']]['triggers'])){
						$result[$item['itemid']]['triggers'] = array();
					}
					if(!is_null($options['select_graphs']) && !isset($result[$item['itemid']]['graphs'])){
						$result[$item['itemid']]['graphs'] = array();
					}
					if(!is_null($options['select_applications']) && !isset($result[$item['itemid']]['applications'])){
						$result[$item['itemid']]['applications'] = array();
					}
					if(!is_null($options['select_prototypes']) && !isset($result[$item['itemid']]['prototypes'])){
						$result[$item['itemid']]['prototypes'] = array();
					}
					if(!is_null($options['selectDiscoveryRule']) && !isset($result[$item['itemid']]['discoveryRule'])){
						$result[$item['itemid']]['discoveryRule'] = array();
					}

// hostids
					if(isset($item['hostid']) && is_null($options['select_hosts'])){
						if(!isset($result[$item['itemid']]['hosts'])) $result[$item['itemid']]['hosts'] = array();

						$result[$item['itemid']]['hosts'][] = array('hostid' => $item['hostid']);
//						unset($item['hostid']);
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
// applicationids
					if(isset($item['applicationid']) && is_null($options['select_applications'])){
						if(!isset($result[$item['itemid']]['applications']))
							$result[$item['itemid']]['applications'] = array();

						$result[$item['itemid']]['applications'][] = array('applicationid' => $item['applicationid']);
						unset($item['applicationid']);
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
		if(!is_null($options['select_hosts'])){
			if(is_array($options['select_hosts']) || str_in_array($options['select_hosts'], $subselects_allowed_outputs)){
				$obj_params = array(
					'nodeids' => $nodeids,
					'itemids' => $itemids,
					'templated_hosts' => 1,
					'output' => $options['select_hosts'],
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
				'itemids' => $itemids,
				'preservekeys' => 1
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

				$triggers = zbx_toHash($triggers, 'itemid');
				foreach($result as $itemid => $item){
					if(isset($triggers[$itemid]))
						$result[$itemid]['triggers'] = $triggers[$itemid]['rowscount'];
					else
						$result[$itemid]['triggers'] = 0;
				}
			}
		}

// Adding graphs
		if(!is_null($options['select_graphs'])){
			$obj_params = array(
				'nodeids' => $nodeids,
				'itemids' => $itemids,
				'preservekeys' => 1
			);

			if(in_array($options['select_graphs'], $subselects_allowed_outputs)){
				$obj_params['output'] = $options['select_graphs'];
				$graphs = CGraph::get($obj_params);

				if(!is_null($options['limitSelects'])) order_result($graphs, 'name');
				foreach($graphs as $graphid => $graph){
					unset($graphs[$graphid]['items']);
					$count = array();
					foreach($graph['items'] as $item){
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

				$graphs = zbx_toHash($graphs, 'itemid');
				foreach($result as $itemid => $item){
					if(isset($graphs[$itemid]))
						$result[$itemid]['graphs'] = $graphs[$itemid]['rowscount'];
					else
						$result[$itemid]['graphs'] = 0;
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

// Adding prototypes
		if(!is_null($options['select_prototypes'])){
			$obj_params = array(
				'nodeids' => $nodeids,
				'discoveryids' => $itemids,
				'filter' => array('flags' => null),
				'nopermissions' => 1,
				'preservekeys' => 1,
			);

			if(is_array($options['select_prototypes']) || str_in_array($options['select_prototypes'], $subselects_allowed_outputs)){
				$obj_params['output'] = $options['select_prototypes'];
				$prototypes = self::get($obj_params);

				if(!is_null($options['limitSelects'])) order_result($prototypes, 'description');
				foreach($prototypes as $itemid => $subrule){
					unset($prototypes[$itemid]['discoveries']);
					$count = array();
					foreach($subrule['discoveries'] as $discovery){
						if(!is_null($options['limitSelects'])){
							if(!isset($count[$discovery['itemid']])) $count[$discovery['itemid']] = 0;
							$count[$discovery['itemid']]++;

							if($count[$discovery['itemid']] > $options['limitSelects']) continue;
						}

						$result[$discovery['itemid']]['prototypes'][] = &$prototypes[$itemid];
					}
				}
			}
			else if(API_OUTPUT_COUNT == $options['select_prototypes']){
				$obj_params['countOutput'] = 1;
				$obj_params['groupCount'] = 1;

				$prototypes = self::get($obj_params);

				$prototypes = zbx_toHash($prototypes, 'parent_itemid');
				foreach($result as $itemid => $item){
					if(isset($prototypes[$itemid]))
						$result[$itemid]['prototypes'] = $prototypes[$itemid]['rowscount'];
					else
						$result[$itemid]['prototypes'] = 0;
				}
			}
		}

// Adding discoveryRule
		if(!is_null($options['selectDiscoveryRule'])){
			$ruleids = $rule_map = array();

			$sql = 'SELECT id1.itemid, id2.parent_itemid'.
					' FROM item_discovery id1, item_discovery id2, items i'.
					' WHERE '.DBcondition('id1.itemid', $itemids).
						' AND id1.parent_itemid=id2.itemid'.
						' AND i.itemid=id1.itemid'.
						' AND i.flags='.ZBX_FLAG_DISCOVERY_CREATED;
			$db_rules = DBselect($sql);
			while($rule = DBfetch($db_rules)){
				$ruleids[$rule['parent_itemid']] = $rule['parent_itemid'];
				$rule_map[$rule['itemid']] = $rule['parent_itemid'];
			}

			$sql = 'SELECT id.parent_itemid, id.itemid'.
					' FROM item_discovery id, items i'.
					' WHERE '.DBcondition('id.itemid', $itemids).
						' AND i.itemid=id.itemid'.
						' AND i.flags='.ZBX_FLAG_DISCOVERY_CHILD;
			$db_rules = DBselect($sql);
			while($rule = DBfetch($db_rules)){
				$ruleids[$rule['parent_itemid']] = $rule['parent_itemid'];
				$rule_map[$rule['itemid']] = $rule['parent_itemid'];
			}

			$obj_params = array(
				'nodeids' => $nodeids,
				'itemids' => $ruleids,
				'filter' => array('flags' => null),
				'nopermissions' => 1,
				'preservekeys' => 1,
			);

			if(is_array($options['selectDiscoveryRule']) || str_in_array($options['selectDiscoveryRule'], $subselects_allowed_outputs)){
				$obj_params['output'] = $options['selectDiscoveryRule'];
				$discoveryRules = self::get($obj_params);

				foreach($result as $itemid => $item){
					if(isset($rule_map[$itemid]) && isset($discoveryRules[$rule_map[$itemid]])){
						$result[$itemid]['discoveryRule'] = $discoveryRules[$rule_map[$itemid]];
					}
				}
			}
		}

COpt::memoryPick();
// removing keys (hash -> array)
		if(is_null($options['preservekeys'])){
			$result = zbx_cleanHashes($result);
		}

	return $result;
	}


/**
 * Get itemid by host.name and item.key
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param array $item_data
 * @param array $item_data['key_']
 * @param array $item_data['hostid']
 * @return int|boolean
 */

	public static function getObjects($itemData){
		$options = array(
			'filter' => $itemData,
			'output'=>API_OUTPUT_EXTEND,
			'webitems' => 1,
		);

		if(isset($itemData['node']))
			$options['nodeids'] = getNodeIdByNodeName($itemData['node']);
		else if(isset($itemData['nodeids']))
			$options['nodeids'] = $itemData['nodeids'];

		$result = self::get($options);

	return $result;
	}

	public static function exists($object){
		$options = array(
			'filter' => array('key_' => $object['key_']),
			'webitems' => 1,
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

/**
 * Add item
 *
 * @param array $items
 * @return array|boolean
 */
	public static function create($items){
		$items = zbx_toArray($items);
		$itemids = array();

		try{
			self::BeginTransaction(__METHOD__);

			foreach($items as $inum => $item){
				$result = add_item($item);

				if(!$result) self::exception(ZBX_API_ERROR_PARAMETERS, 'Cannot create Item');
				$itemids[] = $result;
			}

			self::EndTransaction(true, __METHOD__);

			return array('itemids' => $itemids);
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
 * Update item
 *
 * @param array $items
 * @return boolean
 */
	public static function update($items){
		$items = zbx_toArray($items);
		$itemids = zbx_objectValues($items, 'itemid');

		try{
			self::BeginTransaction(__METHOD__);

			$options = array(
				'itemids' => $itemids,
				'editable' => 1,
				'webitems' => 1,
				'extendoutput' => 1,
				'preservekeys' => 1
			);
			$upd_items = self::get($options);
			foreach($items as $gnum => $item){
				if(!isset($upd_items[$item['itemid']])){
					self::exception(ZBX_API_ERROR_PERMISSIONS, S_NO_PERMISSIONS);
				}
			}

			foreach($items as $inum => $item){
				$item_db_fields = $upd_items[$item['itemid']];

				unset($item_db_fields['lastvalue']);
				unset($item_db_fields['prevvalue']);
				unset($item_db_fields['lastclock']);
				unset($item_db_fields['prevorgvalue']);
				unset($item_db_fields['lastns']);
				if(!check_db_fields($item_db_fields, $item)){
					self::exception(ZBX_API_ERROR_PARAMETERS, 'Incorrect parameters used for Item');
				}

				$result = update_item($item['itemid'], $item);
				if(!$result)
					self::exception(ZBX_API_ERROR_PARAMETERS, 'Cannot update item');
			}

			self::EndTransaction(true, __METHOD__);

			return array('itemids' => $itemids);
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
 * Delete items
 *
 * @param array $itemids
 * @return
 */
	public static function delete($itemids){
		if(empty($itemids)) return true;

		$itemids = zbx_toArray($itemids);
		$insert = $discovery_items = $prototype_items = array();

		try{
			self::BeginTransaction(__METHOD__);

			$options = array(
				'itemids' => $itemids,
				'editable' => 1,
				'filter' => array('flags' => null),
				'preservekeys' => 1,
				'output' => API_OUTPUT_EXTEND,
			);
			$del_items = self::get($options);
			foreach($itemids as $itemid){
				if(!isset($del_items[$itemid])){
					self::exception(ZBX_API_ERROR_PERMISSIONS, S_NO_PERMISSIONS);
				}
				if($del_items[$itemid]['templateid'] != 0){
					self::exception(ZBX_API_ERROR_PARAMETERS, 'Cannot delete templated items');
				}
				if($del_items[$itemid]['type'] == ITEM_TYPE_HTTPTEST){
					self::exception(ZBX_API_ERROR_PARAMETERS, 'Cannot delete web items');
				}

				if($del_items[$itemid]['flags'] == ZBX_FLAG_DISCOVERY){
					$discovery_items[$itemid] = $itemid;
				}
				else if($del_items[$itemid]['flags'] == ZBX_FLAG_DISCOVERY_CHILD){
					$prototype_items[$itemid] = $itemid;
				}
			}

// first delete child items
			$parent_itemids = $itemids;
			do{
				$db_items = DBselect('SELECT itemid FROM items WHERE ' . DBcondition('templateid', $parent_itemids));
				$parent_itemids = array();
				while($db_item = DBfetch($db_items)){
					$parent_itemids[] = $db_item['itemid'];
					$itemids[] = $db_item['itemid'];
				}
			} while(!empty($parent_itemids));

// delete graphs
			$del_graphs = array();
			$sql = 'SELECT gi.graphid' .
					' FROM graphs_items gi' .
					' WHERE ' . DBcondition('gi.itemid', $itemids) .
					' AND NOT EXISTS (' .
						' SELECT gii.gitemid' .
						' FROM graphs_items gii' .
						' WHERE gii.graphid=gi.graphid' .
						' AND ' . DBcondition('gii.itemid', $itemids, true, false) .
					' )';
			$db_graphs = DBselect($sql);
			while($db_graph = DBfetch($db_graphs)){
				$del_graphs[$db_graph['graphid']] = $db_graph['graphid'];
			}

			if(!empty($del_graphs)){
				$result = CGraph::delete($del_graphs);
				if(!$result) self::exception(ZBX_API_ERROR_PARAMETERS, 'Cannot delete item');
			}
//--

// discovery rules/prototypes
			if(!empty($discovery_items)){
				$sql = 'SELECT itemid FROM item_discovery WHERE '.DBcondition('parent_itemid', $discovery_items);
				$db_prototypes = DBselect($sql);
				while($prototype = DBfetch($db_prototypes)){
					$prototype_items[$prototype['itemid']] = $prototype['itemid'];
					$itemids[] = $prototype['itemid'];
				}
			}

			if(!empty($prototype_items)){
				$sql = 'SELECT itemid FROM item_discovery WHERE '.DBcondition('parent_itemid', $prototype_items);
				$db_items = DBselect($sql);
				while($item = DBfetch($db_items)){
					$itemids[] = $item['itemid'];
				}
			}
// ---


			$itemids_condition = DBcondition('itemid', $itemids);

			DB::delete('screens_items', array(
				DBcondition('resourceid', $itemids),
				DBcondition('resourcetype', array(SCREEN_RESOURCE_SIMPLE_GRAPH, SCREEN_RESOURCE_PLAIN_TEXT)),
			));
			DB::delete('items', array($itemids_condition));
			DB::delete('profiles', array(
				'idx='.zbx_dbstr('web.favorite.graphids'),
				'source='.zbx_dbstr('itemid'),
				DBcondition('value_id', $itemids)
			));


			$item_data_tables = array(
				'trends',
				'trends_uint',
				'history_text',
				'history_log',
				'history_uint',
				'history_str',
				'history',
			);

			foreach($itemids as $id => $itemid){
				foreach($item_data_tables as $table){
					$insert[] = array(
						'tablename' => $table,
						'field' => 'itemid',
						'value' => $itemid,
					);
				}
			}
			DB::insert('housekeeper', $insert);

			self::EndTransaction(true, __METHOD__);
			return array('itemids' => $itemids);
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
