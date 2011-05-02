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
/**
 * @package API
 */

class CItem extends CItemGeneral{

	public function __construct(){
		parent::__construct();
	}

/**
 * Get items data
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
	public function get($options=array()){

		$result = array();
		$user_type = self::$userData['type'];
		$userid = self::$userData['userid'];

		$sort_columns = array('itemid','name','key_','delay','history','trends','type','status'); // allowed columns for sorting
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
			'interfaceids'			=> null,
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
			'searchByAny'			=> null,
			'startSearch'				=> null,
			'excludeSearch'			=> null,

// OutPut
			'output'				=> API_OUTPUT_REFER,
			'selectHosts'			=> null,
			'selectInterfaces'		=> null,
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


		if(is_array($options['output'])){
			unset($sql_parts['select']['items']);

			$dbTable = DB::getSchema('items');
			$sql_parts['select']['itemid'] = 'i.itemid';
			foreach($options['output'] as $key => $field){
				if(isset($dbTable['fields'][$field]))
					$sql_parts['select'][$field] = 'i.'.$field;
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


// itemids
		if(!is_null($options['itemids'])){
			zbx_value2array($options['itemids']);

			$sql_parts['where']['itemid'] = DBcondition('i.itemid', $options['itemids']);
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

// interfaceids
		if(!is_null($options['interfaceids'])){
			zbx_value2array($options['interfaceids']);

			if($options['output'] != API_OUTPUT_EXTEND){
				$sql_parts['select']['interfaceid'] = 'i.interfaceid';
			}

			$sql_parts['where']['interfaceid'] = DBcondition('i.interfaceid', $options['interfaceids']);

			if(!is_null($options['groupCount'])){
				$sql_parts['group']['i'] = 'i.interfaceid';
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
			$sql_parts['where'][] = 'hg.hostid=i.hostid';

			if(!is_null($options['groupCount'])){
				$sql_parts['group']['hg'] = 'hg.groupid';
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
		if(is_array($options['filter'])){
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

					if(!is_null($options['selectHosts']) && !isset($result[$item['itemid']]['hosts'])){
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
				$hosts = API::Host()->get($obj_params);

				foreach($hosts as $hostid => $host){
					$hitems = $host['items'];
					unset($host['items']);
					foreach($hitems as $inum => $item){
						$result[$item['itemid']]['hosts'][] = $host;
					}
				}

				$templates = API::Template()->get($obj_params);
				foreach($templates as $templateid => $template){
					$titems = $template['items'];
					unset($template['items']);
					foreach($titems as $inum => $item){
						$result[$item['itemid']]['hosts'][] = $template;
					}
				}
			}
		}

		// Adding interfaces
		if(!is_null($options['selectInterfaces'])){
			if(is_array($options['selectInterfaces']) || str_in_array($options['selectInterfaces'], $subselects_allowed_outputs)){
				$obj_params = array(
					'nodeids' => $nodeids,
					'itemids' => $itemids,
					'output' => $options['selectInterfaces'],
					'nopermissions' => 1,
					'preservekeys' => 1
				);
				$interfaces = API::HostInterface()->get($obj_params);
				foreach($interfaces as $interfaceid => $interface){
					$hitems = $interface['items'];
					unset($interface['items']);
					foreach($hitems as $inum => $item){
						$result[$item['itemid']]['interfaces'][] = $interface;
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
				$triggers = API::Trigger()->get($obj_params);

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

				$triggers = API::Trigger()->get($obj_params);

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
				$graphs = API::Graph()->get($obj_params);

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

				$graphs = API::Graph()->get($obj_params);

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
			$applications = API::Application()->get($obj_params);
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
				$prototypes = $this->get($obj_params);

				if(!is_null($options['limitSelects'])) order_result($prototypes, 'name');
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

				$prototypes = $this->get($obj_params);

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
				$discoveryRules = $this->get($obj_params);

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
	 * @param array $item_data
	 * @param array $item_data['key_']
	 * @param array $item_data['hostid']
	 * @return int|boolean
	 */
	public function getObjects($itemData){
		$options = array(
			'filter' => $itemData,
			'output'=>API_OUTPUT_EXTEND,
			'webitems' => 1,
		);

		if(isset($itemData['node']))
			$options['nodeids'] = getNodeIdByNodeName($itemData['node']);
		else if(isset($itemData['nodeids']))
			$options['nodeids'] = $itemData['nodeids'];

		$result = $this->get($options);

	return $result;
	}

	public function exists($object){
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

		$objs = $this->get($options);

	return !empty($objs);
	}

	protected function checkInput(&$items, $update=false){
		foreach($items as $inum => $item){
			$items[$inum]['flags'] = ZBX_FLAG_DISCOVERY_NORMAL;
		}
		// validate if everything is ok with 'item->profile fields' linkage
		self::validateProfileLinks($items, $update);
		parent::checkInput($items, $update);
	}

	/**
	 * Add item
	 *
	 * @param array $items
	 * @return array|boolean
	 */
	public function create($items){
		$items = zbx_toArray($items);

		$this->checkInput($items);

		$this->createReal($items);

		$this->inherit($items);

		return array('itemids' => zbx_objectValues($items, 'itemid'));
	}

	protected function createReal(&$items){
		foreach($items as $key => $item){
			$itemsExists = API::Item()->get(array(
				'output' => API_OUTPUT_SHORTEN,
				'filter' => array(
					'hostid' => $item['hostid'],
					'key_' => $item['key_']
				),
				'nopermissions' => 1
			));
			foreach($itemsExists as $inum => $itemExists){
				self::exception(ZBX_API_ERROR_PARAMETERS, 'Item with key "'.$item['key_'].'" already exists on given host.');
			}
		}

		$itemids = DB::insert('items', $items);

		$itemApplications = array();
		foreach($items as $key => $item){
			$items[$key]['itemid'] = $itemids[$key];

			if(!isset($item['applications'])) continue;

			foreach($item['applications'] as $anum => $appid){
				if($appid == 0) continue;

				$itemApplications[] = array(
					'applicationid' => $appid,
					'itemid' => $items[$key]['itemid']
				);
			}
		}

		if(!empty($itemApplications)){
			DB::insert('items_applications', $itemApplications);
		}

// TODO: REMOVE info
		$itemHosts = $this->get(array(
			'itemids' => $itemids,
			'output' => array('key_'),
			'selectHosts' => array('host'),
			'nopermissions' => 1
		));
		foreach($itemHosts as $item){
			$host = reset($item['hosts']);
			info(S_ITEM." [".$host['host'].':'.$item['key_']."] ".S_CREATED_SMALL);
		}
	}

	protected function updateReal($items){
		$items = zbx_toArray($items);

		$itemids = array();
		$data = array();
		foreach($items as $inum => $item){
			$itemsExists = API::Item()->get(array(
				'output' => API_OUTPUT_SHORTEN,
				'filter' => array(
					'hostid' => $item['hostid'],
					'key_' => $item['key_']
				),
				'nopermissions' => 1
			));
			foreach($itemsExists as $inum => $itemExists){
				if(bccomp($itemExists['itemid'],$item['itemid']) != 0){
					self::exception(ZBX_API_ERROR_PARAMETERS, 'Host with item [ '.$item['key_'].' ] already exists');
				}
			}

			$data[] = array('values' => $item, 'where'=> array('itemid='.$item['itemid']));
			$itemids[] = $item['itemid'];
		}
		$result = DB::update('items', $data);
		if(!$result) self::exception(ZBX_API_ERROR_PARAMETERS, 'DBerror');


		$itemApplications = $aids = array();
		foreach($items as $key => $item){
			if(!isset($item['applications'])) continue;
			$aids[] = $item['itemid'];

			foreach($item['applications'] as $anum => $appid){
				$itemApplications[] = array(
					'applicationid' => $appid,
					'itemid' => $item['itemid']
				);
			}
		}

		if(!empty($itemids)){
			DB::delete('items_applications', array('itemid' => $aids));
			DB::insert('items_applications', $itemApplications);
		}

// TODO: REMOVE info
		$itemHosts = $this->get(array(
			'itemids' => $itemids,
			'output' => array('key_'),
			'selectHosts' => array('host'),
			'nopermissions' => 1,
		));
		foreach($itemHosts as $item){
			$host = reset($item['hosts']);
			info(S_ITEM." [".$host['host'].':'.$item['key_']."] ".S_UPDATED_SMALL);
		}

	}

	/**
	 * Update item
	 *
	 * @param array $items
	 * @return boolean
	 */
	public function update($items){
		$items = zbx_toArray($items);

			$this->checkInput($items, true);

			$this->updateReal($items);

			$this->inherit($items);

			return array('itemids' => zbx_objectValues($items, 'itemid'));
	}

	/**
	 * Delete items
	 *
	 * @param array $itemids
	 * @return
	 */
	public function delete($itemids, $nopermissions=false){
			if(empty($itemids))
				self::exception(ZBX_API_ERROR_PARAMETERS, _('Empty input parameter'));

			$itemids = zbx_toHash($itemids);

			$options = array(
				'itemids' => $itemids,
				'editable' => true,
				'preservekeys' => true,
				'output' => API_OUTPUT_EXTEND,
			);
			$del_items = $this->get($options);

// TODO: remove $nopermissions hack
			if(!$nopermissions){
				foreach($itemids as $itemid){
					if(!isset($del_items[$itemid])){
						self::exception(ZBX_API_ERROR_PERMISSIONS, S_NO_PERMISSIONS);
					}
					if($del_items[$itemid]['templateid'] != 0){
						self::exception(ZBX_API_ERROR_PARAMETERS, 'Cannot delete templated items');
					}
				}
			}
// first delete child items
			$parent_itemids = $itemids;
			do{
				$db_items = DBselect('SELECT itemid FROM items WHERE ' . DBcondition('templateid', $parent_itemids));
				$parent_itemids = array();
				while($db_item = DBfetch($db_items)){
					$parent_itemids[] = $db_item['itemid'];
					$itemids[$db_item['itemid']] = $db_item['itemid'];
				}
			} while(!empty($parent_itemids));


// delete graphs, leave if graph still have item
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
				$result = API::Graph()->delete($del_graphs, true);
				if(!$result) self::exception(ZBX_API_ERROR_PARAMETERS, _s('Cannot delete graph'));
			}
//--

			$triggers = API::Trigger()->get(array(
				'itemids' => $itemids,
				'output' => API_OUTPUT_SHORTEN,
				'nopermissions' => true,
				'preservekeys' => true,
			));
			if(!empty($triggers))
				DB::delete('triggers', array('triggerid' => zbx_objectValues($triggers, 'triggerid')));


			$itemids_condition = array('itemid'=>$itemids);
			DB::delete('screens_items', array(
				'resourceid'=>$itemids,
				'resourcetype'=>array(SCREEN_RESOURCE_SIMPLE_GRAPH, SCREEN_RESOURCE_PLAIN_TEXT),
			));
			DB::delete('items', $itemids_condition);
			DB::delete('profiles', array(
				'idx'=>'web.favorite.graphids',
				'source'=>'itemid',
				'value_id'=>$itemids
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
			$insert = array();
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

// TODO: remove info from API
			foreach($del_items as $item){
				info(_s('Item "%1$s:%2$s" deleted.', $item['name'], $item['key_']));
			}

			return array('itemids' => $itemids);
	}

	public function syncTemplates($data){
		$data['templateids'] = zbx_toArray($data['templateids']);
		$data['hostids'] = zbx_toArray($data['hostids']);

		if(!API::Host()->isWritable($data['hostids'])){
			self::exception(ZBX_API_ERROR_PERMISSIONS, S_NO_PERMISSION);
		}
		if(!API::Template()->isReadable($data['templateids'])){
			self::exception(ZBX_API_ERROR_PERMISSIONS, S_NO_PERMISSION);
		}

		$selectFields = array();
		foreach($this->fieldRules as $key => $rules){
			if(!isset($rules['system']) && !isset($rules['host'])){
				$selectFields[] = $key;
			}
		}
		$options = array(
			'hostids' => $data['templateids'],
			'preservekeys' => true,
			'select_applications' => API_OUTPUT_REFER,
			'output' => $selectFields,
			'filter' => array('flags' => ZBX_FLAG_DISCOVERY_NORMAL),
		);
		$items = $this->get($options);

		foreach($items as $inum => $item){
			$items[$inum]['applications'] = zbx_objectValues($item['applications'], 'applicationid');
		}

		$this->inherit($items, $data['hostids']);

		return true;
	}

	protected function inherit($items, $hostids=null){
		if(empty($items)) return true;

		$chdHosts = API::Host()->get(array(
			'output' => array('hostid', 'host', 'status'),
			'selectInterfaces' => API_OUTPUT_EXTEND,
			'templateids' => zbx_objectValues($items, 'hostid'),
			'hostids' => $hostids,
			'preservekeys' => true,
			'nopermissions' => true,
			'templated_hosts' => true
		));
		if(empty($chdHosts)) return true;

		$insertItems = array();
		$updateItems = array();
		$inheritedItems = array();
		foreach($chdHosts as $hostid => $host){
			$interfaceids = array();
			foreach($host['interfaces'] as $interface){
				if($interface['main'] == 1)
					$interfaceids[$interface['type']] = $interface['interfaceid'];
			}

			$templateids = zbx_toHash($host['templates'], 'templateid');

// skip items not from parent templates of current host
			$parentItems = array();
			foreach($items as $inum => $item){
				if(isset($templateids[$item['hostid']]))
					$parentItems[$inum] = $item;
			}
//----

// check existing items to decide insert or update
			$exItems = $this->get(array(
				'output' => array('itemid', 'type', 'key_', 'flags', 'templateid'),
				'hostids' => $hostid,
				'filter' => array('flags' => null),
				'preservekeys' => true,
				'nopermissions' => true,
			));
			$exItemsKeys = zbx_toHash($exItems, 'key_');
			$exItemsTpl = zbx_toHash($exItems, 'templateid');

			foreach($parentItems as $item){
				$exItem = null;

// update by templateid
				if(isset($exItemsTpl[$item['itemid']])){
					$exItem = $exItemsTpl[$item['itemid']];
				}

// update by key
				if(isset($item['key_']) && isset($exItemsKeys[$item['key_']])){
					$exItem = $exItemsKeys[$item['key_']];

					if($exItem['flags'] != ZBX_FLAG_DISCOVERY_NORMAL){
						$this->errorInheritFlags($exItem['flags'], $exItem['key_'], $host['host']);
					}
					else if(($exItem['templateid'] > 0) && (bccomp($exItem['templateid'],$item['itemid']) != 0)){
						self::exception(ZBX_API_ERROR_PARAMETERS, _s('Item "%1$s:%2$s" already exists, inherited from another template.', $host['host'], $item['key_']));
					}
				}


				if(($host['status'] == HOST_STATUS_TEMPLATE) || !isset($item['type'])){
					unset($item['interfaceid']);
				}
				else if(isset($item['type']) && ($item['type'] != $exItem['type'])){
					if($type = $this->itemTypeInterface($item['type'])){
						if(!isset($interfaceids[$type]))
							self::exception(ZBX_API_ERROR_PARAMETERS, _s('Cannot find host interface on host "%1$s" for item key "%2$s".', $host['host'], $exItem['key_']));

						$item['interfaceid'] = $interfaceids[$type];
					}
					else{
						$item['interfaceid'] = 0;
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
					$inheritedItems[] = $newItem;

					$updateItems[] = $newItem;
				}
				else{
					$inheritedItems[] = $newItem;
					$insertItems[] = $newItem;
				}
			}
		}

		if(!zbx_empty($insertItems)){
			self::validateProfileLinks($insertItems, false); // false means 'create'
			$this->createReal($insertItems);
		}

		if(!zbx_empty($updateItems)){
			self::validateProfileLinks($updateItems, true); // true means 'update'
			$this->updateReal($updateItems);
		}

		$this->inherit($inheritedItems);
	}



	/**
	 * Check, if items that are about to be inserted or updated violate the rule:
	 * only one item can be linked to a profile filed.
	 * If everything is ok, function return true or throws Exception otherwise
	 * @static
	 * @param array $items
	 * @param bool $update whether this is update operation
	 * @return bool
	 */
	public static function validateProfileLinks($items, $update=false){
		if(zbx_empty($items)){
			return true;
		}
		$possibleHostProfiles = getHostProfiles();
		$hostIds = array();
		// when we are updating item, we might not have a host id
		if($update){
			// some of the items (which changed host) can already have a host id
			// we should find out, which do not
			$itemsWithNoHostId = array();
			$itemsWithHostIdButNoProfileLink = array();
			foreach($items as $i=>$item){
				if(isset($item['hostid']) && isset($item['profile_link'])){
					$hostIds[] = $item['hostid'];
				}
				else if(isset($item['hostid'])){
					$hostIds[] = $item['hostid'];
					$itemsWithHostIdButNoProfileLink[] = $item['itemid'];
				}
				else if(isset($item['profile_link'])){
					$itemsWithNoHostId[] = $item['itemid'];
				}
				else{
					unset($items[$i]); // profile link field is not being updated, so why bother?
				}
			}
			$itemsToFind = array_merge($itemsWithNoHostId, $itemsWithHostIdButNoProfileLink);
			// are there any items with no hostids or profile_links?
			if(!zbx_empty($itemsToFind)){
				// getting the host ids for those items
				$options = array(
					'output' => array('hostid', 'profile_link'),
					'filter' => array(
						'itemid' => $itemsToFind
					),
					'nopermissions' => true
				);
				$missingInfo = API::Item()->get($options);
				$missingInfo = zbx_toHash($missingInfo, 'itemid');
				// appending host ids and profile_links where they are needed
				foreach($items as $i=>$item){
					if (isset($missingInfo[$item['itemid']])){
						if(isset($items[$i]['hostid'])){
							$items[$i]['profile_link'] = $missingInfo[$item['itemid']]['profile_link'];
						}
						else{
							$items[$i]['hostid'] = $missingInfo[$item['itemid']]['hostid'];
							$hostIds[] = $items[$i]['hostid'];
						}
					}
				}
			}
		}
		else{
			$hostIds = zbx_objectValues($items, 'hostid');
		}

		// getting all profile links on every affected host
		$options = array(
			'output' => array('profile_link', 'hostid'),
			'filter' => array(
				'hostid' => $hostIds
			),
			'nopermissions' => true
		);
		$profileLinksAndHostIds = API::Item()->get($options);

		// now, changing array: 'hostid' => 'array of profile links'
		$linksOnHosts = array();
		foreach($profileLinksAndHostIds as $linkAndHostId){
			// 0 means no link - we are not interested in those ones
			if($linkAndHostId['profile_link'] != 0){
				if(!isset($linksOnHosts[$linkAndHostId['hostid']])){
					$linksOnHosts[$linkAndHostId['hostid']] = array($linkAndHostId['profile_link']);
				}
				else{
					$linksOnHosts[$linkAndHostId['hostid']][] = $linkAndHostId['profile_link'];
				}
			}
		}

		// now, when we have all required info, checking it against every item
		foreach($items as $item){
			if(
				($update && $item['profile_link'] != 0)
				|| ($item['profile_link'] != 0 && $item['value_type'] != ITEM_VALUE_TYPE_LOG) // for log items check is not needed
			){
				// does profile field with provided number exists?
				if(!isset($possibleHostProfiles[$item['profile_link']])){
					self::exception(
						ZBX_API_ERROR_PARAMETERS,
						_s('Wrong value for profile_link. Item "%s" cannot populate field that does not exist.', $item['name'])
					);
				}

				// is this field already populated by another item on this host?
				if(
					isset($linksOnHosts[$item['hostid']])
					&& in_array($item['profile_link'], $linksOnHosts[$item['hostid']])
					&& (!$update || $item['templateid'] == 0) // when linking template, we need to check only those items, that are not present in template, others will be overwritten anyway
				){
					self::exception(
						ZBX_API_ERROR_PARAMETERS,
						_('Two items cannot populate one host profile field, this would lead to a conflict. Chosen field is already being populated by another item.')
					);
				}
			}
		}

		return true;
	}
}
?>
