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
 * File containing CTrigger class for API.
 * @package API
 */


class CTrigger extends CZBXAPI{
/**
 * Get Triggers data
 *
 * @param _array $options
 * @param array $options['itemids']
 * @param array $options['hostids']
 * @param array $options['groupids']
 * @param array $options['triggerids']
 * @param array $options['applicationids']
 * @param array $options['status']
 * @param array $options['editable']
 * @param array $options['count']
 * @param array $options['pattern']
 * @param array $options['limit']
 * @param array $options['order']
 * @return array|int item data as array or false if error
 */
	public function get($options=array()){
		$result = array();
		$user_type = self::$userData['type'];
		$userid = self::$userData['userid'];

		$sort_columns = array('triggerid', 'description', 'status', 'priority', 'lastchange'); // allowed columns for sorting
		$subselects_allowed_outputs = array(API_OUTPUT_REFER, API_OUTPUT_EXTEND); // allowed output options for [ select_* ] params


		$sql_parts = array(
			'select' => array('triggers' => 't.triggerid'),
			'from' => array('t' => 'triggers t'),
			'where' => array(),
			'group' => array(),
			'order' => array(),
			'limit' => null,
		);

		$def_options = array(
			'nodeids'				=> null,
			'groupids'				=> null,
			'templateids'			=> null,
			'hostids'				=> null,
			'triggerids'			=> null,
			'itemids'				=> null,
			'applicationids'		=> null,
			'discoveryids'			=> null,
			'functions'				=> null,
			'inherited'				=> null,
			'templated'				=> null,
			'monitored' 			=> null,
			'active' 				=> null,
			'maintenance'			=> null,

			'withUnacknowledgedEvents'		=>	null,
			'withAcknowledgedEvents'		=>	null,
			'withLastEventUnacknowledged'	=>	null,

			'skipDependent'			=> null,
			'nopermissions'			=> null,
			'editable'				=> null,
// timing
			'lastChangeSince'		=> null,
			'lastChangeTill'		=> null,
// filter
			'group'					=> null,
			'host'					=> null,
			'only_true'				=> null,
			'min_severity'			=> null,

			'filter'					=> null,
			'search'					=> null,
			'searchByAny'			=> null,
			'startSearch'				=> null,
			'excludeSearch'				=> null,

// OutPut
			'expandData'			=> null,
			'expandDescription'		=> null,
			'output'				=> API_OUTPUT_REFER,
			'selectGroups'			=> null,
			'selectHosts'			=> null,
			'selectItems'			=> null,
			'select_functions'		=> null,
			'select_dependencies'	=> null,
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
			unset($sql_parts['select']['triggers']);

			$dbTable = DB::getSchema('triggers');
			$sql_parts['select']['triggerid'] = ' t.triggerid';
			foreach($options['output'] as $key => $field){
				if(isset($dbTable['fields'][$field]))
					$sql_parts['select'][$field] = 't.'.$field;
			}

			$options['output'] = API_OUTPUT_CUSTOM;
		}

// editable + PERMISSION CHECK

		if((USER_TYPE_SUPER_ADMIN == $user_type) || $options['nopermissions']){
		}
		else{
			$permission = $options['editable']?PERM_READ_WRITE:PERM_READ_ONLY;
/*/
			$sql_parts['where'][] = ' EXISTS(  '.
						' SELECT tt.triggerid  '.
						' FROM triggers tt,functions ff,items ii,hosts_groups hgg,rights rr,users_groups ugg '.
						' WHERE t.triggerid=tt.triggerid  '.
							' AND ff.triggerid=tt.triggerid  '.
							' AND ff.itemid=ii.itemid  '.
							' AND hgg.hostid=ii.hostid  '.
							' AND rr.id=hgg.groupid  '.
							' AND rr.groupid=ugg.usrgrpid  '.
							' AND ugg.userid='.$userid.
							' AND rr.permission>='.$permission.
							' AND NOT EXISTS(  '.
								' SELECT fff.triggerid  '.
								' FROM functions fff, items iii  '.
								' WHERE fff.triggerid=tt.triggerid '.
									' AND fff.itemid=iii.itemid '.		'    '.
									' AND EXISTS( '.
										' SELECT hggg.groupid '.
										' FROM hosts_groups hggg, rights rrr, users_groups uggg '.
										' WHERE hggg.hostid=iii.hostid '.
											' AND rrr.id=hggg.groupid '.
											' AND rrr.groupid=uggg.usrgrpid '.
											' AND uggg.userid='.$userid.
											' AND rrr.permission<'.$permission.
										' ) '.
								' ) '.
						' ) ';
//*/
//*/
			$sql_parts['from']['functions'] = 'functions f';
			$sql_parts['from']['items'] = 'items i';
			$sql_parts['from']['hosts_groups'] = 'hosts_groups hg';
			$sql_parts['from']['rights'] = 'rights r';
			$sql_parts['from']['users_groups'] = 'users_groups ug';
			$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
			$sql_parts['where']['fi'] = 'f.itemid=i.itemid';
			$sql_parts['where']['hgi'] = 'hg.hostid=i.hostid';
			$sql_parts['where'][] = 'r.id=hg.groupid ';
			$sql_parts['where'][] = 'r.groupid=ug.usrgrpid';
			$sql_parts['where'][] = 'ug.userid='.$userid;
			$sql_parts['where'][] = 'r.permission>='.$permission;
			$sql_parts['where'][] = 'NOT EXISTS( '.
											' SELECT ff.triggerid '.
											' FROM functions ff, items ii '.
											' WHERE ff.triggerid=t.triggerid '.
												' AND ff.itemid=ii.itemid '.
												' AND EXISTS( '.
													' SELECT hgg.groupid '.
													' FROM hosts_groups hgg, rights rr, users_groups gg '.
													' WHERE hgg.hostid=ii.hostid '.
														' AND rr.id=hgg.groupid '.
														' AND rr.groupid=gg.usrgrpid '.
														' AND gg.userid='.$userid.
														' AND rr.permission<'.$permission.'))';
//*/
		}

// nodeids
		$nodeids = !is_null($options['nodeids']) ? $options['nodeids'] : get_current_nodeid();

// groupids
		if(!is_null($options['groupids'])){
			zbx_value2array($options['groupids']);

			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['groupid'] = 'hg.groupid';
			}

			$sql_parts['from']['functions'] = 'functions f';
			$sql_parts['from']['items'] = 'items i';
			$sql_parts['from']['hosts_groups'] = 'hosts_groups hg';
			$sql_parts['where']['hgi'] = 'hg.hostid=i.hostid';
			$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
			$sql_parts['where']['fi'] = 'f.itemid=i.itemid';
			$sql_parts['where']['groupid'] = DBcondition('hg.groupid', $options['groupids']);

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

			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['hostid'] = 'i.hostid';
			}

			$sql_parts['from']['functions'] = 'functions f';
			$sql_parts['from']['items'] = 'items i';
			$sql_parts['where']['hostid'] = DBcondition('i.hostid', $options['hostids']);
			$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
			$sql_parts['where']['fi'] = 'f.itemid=i.itemid';

			if(!is_null($options['groupCount'])){
				$sql_parts['group']['i'] = 'i.hostid';
			}
		}

// triggerids
		if(!is_null($options['triggerids'])){
			zbx_value2array($options['triggerids']);

			$sql_parts['where']['triggerid'] = DBcondition('t.triggerid', $options['triggerids']);
		}

// itemids
		if(!is_null($options['itemids'])){
			zbx_value2array($options['itemids']);

			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['itemid'] = 'f.itemid';
			}

			$sql_parts['from']['functions'] = 'functions f';
			$sql_parts['where']['itemid'] = DBcondition('f.itemid', $options['itemids']);
			$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';

			if(!is_null($options['groupCount'])){
				$sql_parts['group']['f'] = 'f.itemid';
			}
		}

// applicationids
		if(!is_null($options['applicationids'])){
			zbx_value2array($options['applicationids']);

			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['applicationid'] = 'a.applicationid';
			}

			$sql_parts['from']['functions'] = 'functions f';
			$sql_parts['from']['items'] = 'items i';
			$sql_parts['from']['applications'] = 'applications a';
			$sql_parts['where']['a'] = DBcondition('a.applicationid', $options['applicationids']);
			$sql_parts['where']['ia'] = 'i.hostid=a.hostid';
			$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
			$sql_parts['where']['fi'] = 'f.itemid=i.itemid';
		}

// discoveryids
		if(!is_null($options['discoveryids'])){
			zbx_value2array($options['discoveryids']);

			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['itemid'] = 'id.parent_itemid';
			}
			$sql_parts['from']['functions'] = 'functions f';
			$sql_parts['from']['item_discovery'] = 'item_discovery id';
			$sql_parts['where']['fid'] = 'f.itemid=id.itemid';
			$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
			$sql_parts['where'][] = DBcondition('id.parent_itemid', $options['discoveryids']);

			if(!is_null($options['groupCount'])){
				$sql_parts['group']['id'] = 'id.parent_itemid';
			}
		}

// functions
		if(!is_null($options['functions'])){
			zbx_value2array($options['functions']);

			$sql_parts['from']['functions'] = 'functions f';
			$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
			$sql_parts['where'][] = DBcondition('f.function', $options['functions']);
		}

// monitored
		if(!is_null($options['monitored'])){
			$sql_parts['where']['monitored'] = ''.
				' NOT EXISTS ('.
					' SELECT ff.functionid'.
					' FROM functions ff'.
					' WHERE ff.triggerid=t.triggerid'.
						' AND EXISTS ('.
								' SELECT ii.itemid'.
								' FROM items ii, hosts hh'.
								' WHERE ff.itemid=ii.itemid'.
									' AND hh.hostid=ii.hostid'.
									' AND ('.
										' ii.status<>'.ITEM_STATUS_ACTIVE.
										' OR hh.status<>'.HOST_STATUS_MONITORED.
									' )'.
						' )'.
				' )';
			$sql_parts['where']['status'] = 't.status='.TRIGGER_STATUS_ENABLED;
		}

// active
		if(!is_null($options['active'])){
			$sql_parts['where']['active'] = ''.
				' NOT EXISTS ('.
					' SELECT ff.functionid'.
					' FROM functions ff'.
					' WHERE ff.triggerid=t.triggerid'.
						' AND EXISTS ('.
							' SELECT ii.itemid'.
							' FROM items ii, hosts hh'.
							' WHERE ff.itemid=ii.itemid'.
								' AND hh.hostid=ii.hostid'.
								' AND  hh.status<>'.HOST_STATUS_MONITORED.
						' )'.
				' )';
			$sql_parts['where']['status'] = 't.status='.TRIGGER_STATUS_ENABLED;
		}

// maintenance
		if(!is_null($options['maintenance'])){
			$sql_parts['where'][] = (($options['maintenance'] == 0) ? ' NOT ':'').
				' EXISTS ('.
					' SELECT ff.functionid'.
					' FROM functions ff'.
					' WHERE ff.triggerid=t.triggerid'.
						' AND EXISTS ('.
								' SELECT ii.itemid'.
								' FROM items ii, hosts hh'.
								' WHERE ff.itemid=ii.itemid'.
									' AND hh.hostid=ii.hostid'.
									' AND hh.maintenance_status=1'.
						' )'.
				' )';
			$sql_parts['where'][] = 't.status='.TRIGGER_STATUS_ENABLED;
		}

// lastChangeSince
		if(!is_null($options['lastChangeSince'])){
			$sql_parts['where']['lastchangesince'] = 't.lastchange>'.$options['lastChangeSince'];
		}

// lastChangeTill
		if(!is_null($options['lastChangeTill'])){
			$sql_parts['where']['lastchangetill'] = 't.lastchange<'.$options['lastChangeTill'];
		}

// withUnacknowledgedEvents
		if(!is_null($options['withUnacknowledgedEvents'])){
			$sql_parts['where']['unack'] = ' EXISTS('.
				' SELECT e.eventid'.
				' FROM events e'.
				' WHERE e.objectid=t.triggerid'.
					' AND e.object='.EVENT_OBJECT_TRIGGER.
					' AND e.value_changed='.TRIGGER_VALUE_CHANGED_YES.
					' AND e.value='.TRIGGER_VALUE_TRUE.
					' AND e.acknowledged=0)';
		}
// withAcknowledgedEvents
		if(!is_null($options['withAcknowledgedEvents'])){
			$sql_parts['where']['ack'] = 'NOT EXISTS('.
				' SELECT e.eventid'.
				' FROM events e'.
				' WHERE e.objectid=t.triggerid'.
					' AND e.object='.EVENT_OBJECT_TRIGGER.
					' AND e.value_changed='.TRIGGER_VALUE_CHANGED_YES.
					' AND e.value='.TRIGGER_VALUE_TRUE.
					' AND e.acknowledged=0)';
		}

// templated
		if(!is_null($options['templated'])){
			$sql_parts['from']['functions'] = 'functions f';
			$sql_parts['from']['items'] = 'items i';
			$sql_parts['from']['hosts'] = 'hosts h';
			$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
			$sql_parts['where']['fi'] = 'f.itemid=i.itemid';
			$sql_parts['where']['hi'] = 'h.hostid=i.hostid';

			if($options['templated']){
				$sql_parts['where'][] = 'h.status='.HOST_STATUS_TEMPLATE;
			}
			else{
				$sql_parts['where'][] = 'h.status<>'.HOST_STATUS_TEMPLATE;
			}
		}

// inherited
		if(!is_null($options['inherited'])){
			if($options['inherited']){
				$sql_parts['where'][] = 't.templateid IS NOT NULL';
			}
			else{
				$sql_parts['where'][] = 't.templateid IS NULL';
			}
		}

// search
		if(is_array($options['search'])){
			zbx_db_search('triggers t', $options, $sql_parts);
		}

// --- FILTER ---
		if(is_null($options['filter']))
			$options['filter'] = array();

		if(is_array($options['filter'])){
			if(!array_key_exists('flags', $options['filter']))
				$options['filter']['flags'] = array(ZBX_FLAG_DISCOVERY_NORMAL, ZBX_FLAG_DISCOVERY_CREATED);

			zbx_db_filter('triggers t', $options, $sql_parts);

			if(isset($options['filter']['host']) && !is_null($options['filter']['host'])){
				zbx_value2array($options['filter']['host']);

				$sql_parts['from']['functions'] = 'functions f';
				$sql_parts['from']['items'] = 'items i';
				$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
				$sql_parts['where']['fi'] = 'f.itemid=i.itemid';

				$sql_parts['from']['hosts'] = 'hosts h';
				$sql_parts['where']['hi'] = 'h.hostid=i.hostid';
				$sql_parts['where']['host'] = DBcondition('h.host', $options['filter']['host']);
			}

			if(isset($options['filter']['hostid']) && !is_null($options['filter']['hostid'])){
				zbx_value2array($options['filter']['hostid']);

				$sql_parts['from']['functions'] = 'functions f';
				$sql_parts['from']['items'] = 'items i';
				$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
				$sql_parts['where']['fi'] = 'f.itemid=i.itemid';

				$sql_parts['where']['hostid'] = DBcondition('i.hostid', $options['filter']['hostid']);
			}
		}

// group
		if(!is_null($options['group'])){
			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['name'] = 'g.name';
			}

			$sql_parts['from']['functions'] = 'functions f';
			$sql_parts['from']['items'] = 'items i';
			$sql_parts['from']['hosts_groups'] = 'hosts_groups hg';
			$sql_parts['from']['groups'] = 'groups g';
			$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
			$sql_parts['where']['fi'] = 'f.itemid=i.itemid';
			$sql_parts['where']['hgi'] = 'hg.hostid=i.hostid';
			$sql_parts['where']['ghg'] = 'g.groupid = hg.groupid';
			$sql_parts['where']['group'] = ' UPPER(g.name)='.zbx_dbstr(zbx_strtoupper($options['group']));
		}

// host
		if(!is_null($options['host'])){
			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['host'] = 'h.host';
			}

			$sql_parts['from']['functions'] = 'functions f';
			$sql_parts['from']['items'] = 'items i';
			$sql_parts['from']['hosts'] = 'hosts h';
			$sql_parts['where']['i'] = DBcondition('i.hostid', $options['hostids']);
			$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
			$sql_parts['where']['fi'] = 'f.itemid=i.itemid';
			$sql_parts['where']['hi'] = 'h.hostid=i.hostid';
			$sql_parts['where']['host'] = ' UPPER(h.host)='.zbx_dbstr(zbx_strtoupper($options['host']));
		}

// only_true
		if(!is_null($options['only_true'])){

			$sql_parts['where']['ot'] = '((t.value='.TRIGGER_VALUE_TRUE.')'.
									' OR '.
									'((t.value='.TRIGGER_VALUE_FALSE.') AND (t.lastchange>'.(time() - TRIGGER_FALSE_PERIOD).')))';
		}

// min_severity
		if(!is_null($options['min_severity'])){
			$sql_parts['where'][] = 't.priority>='.$options['min_severity'];
		}

// output
		if($options['output'] == API_OUTPUT_EXTEND){
			$sql_parts['select']['triggers'] = 't.*';
		}

// expandData
		if(!is_null($options['expandData'])){
			$sql_parts['select']['host'] = 'h.host';
			$sql_parts['select']['hostid'] = 'h.hostid';
			$sql_parts['from']['functions'] = 'functions f';
			$sql_parts['from']['items'] = 'items i';
			$sql_parts['from']['hosts'] = 'hosts h';
			$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
			$sql_parts['where']['fi'] = 'f.itemid=i.itemid';
			$sql_parts['where']['hi'] = 'h.hostid=i.hostid';
		}

// countOutput
		if(!is_null($options['countOutput'])){
			$options['sortfield'] = '';
			$sql_parts['select'] = array('COUNT(DISTINCT t.triggerid) as rowscount');

// groupCount
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

			$sql_parts['order'][] = 't.'.$options['sortfield'].' '.$sortorder;

			if(!str_in_array('t.'.$options['sortfield'], $sql_parts['select']) && !str_in_array('t.*', $sql_parts['select'])){
				$sql_parts['select'][] = 't.'.$options['sortfield'];
			}
		}

// limit
		if(zbx_ctype_digit($options['limit']) && $options['limit']){
			$sql_parts['limit'] = $options['limit'];
		}
//---------------

		$triggerids = array();

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
				' WHERE '.DBin_node('t.triggerid', $nodeids).
					$sql_where.
				$sql_group.
				$sql_order;
//SDI($sql);
		$db_res = DBselect($sql, $sql_limit);
		while($trigger = DBfetch($db_res)){
			if(!is_null($options['countOutput'])){
				if(!is_null($options['groupCount']))
					$result[] = $trigger;
				else
					$result = $trigger['rowscount'];
			}
			else{
				$triggerids[$trigger['triggerid']] = $trigger['triggerid'];

				if($options['output'] == API_OUTPUT_SHORTEN){
					$result[$trigger['triggerid']] = array('triggerid' => $trigger['triggerid']);
				}
				else{
					if(!isset($result[$trigger['triggerid']])) $result[$trigger['triggerid']]= array();

					if(!is_null($options['selectHosts']) && !isset($result[$trigger['triggerid']]['hosts'])){
						$result[$trigger['triggerid']]['hosts'] = array();
					}
					if(!is_null($options['selectItems']) && !isset($result[$trigger['triggerid']]['items'])){
						$result[$trigger['triggerid']]['items'] = array();
					}
					if(!is_null($options['select_functions']) && !isset($result[$trigger['triggerid']]['functions'])){
						$result[$trigger['triggerid']]['functions'] = array();
					}
					if(!is_null($options['select_dependencies']) && !isset($result[$trigger['triggerid']]['dependencies'])){
						$result[$trigger['triggerid']]['dependencies'] = array();
					}
					if(!is_null($options['selectDiscoveryRule']) && !isset($result[$trigger['triggerid']]['discoveryRule'])){
						$result[$trigger['triggerid']]['discoveryRule'] = array();
					}

// groups
					if(isset($trigger['groupid']) && is_null($options['selectGroups'])){
						if(!isset($result[$trigger['triggerid']]['groups'])) $result[$trigger['triggerid']]['groups'] = array();

						$result[$trigger['triggerid']]['groups'][] = array('groupid' => $trigger['groupid']);
						unset($trigger['groupid']);
					}

// hostids
					if(isset($trigger['hostid']) && is_null($options['selectHosts'])){
						if(!isset($result[$trigger['triggerid']]['hosts'])) $result[$trigger['triggerid']]['hosts'] = array();

						$result[$trigger['triggerid']]['hosts'][] = array('hostid' => $trigger['hostid']);

						if(is_null($options['expandData'])) unset($trigger['hostid']);
					}
// itemids
					if(isset($trigger['itemid']) && is_null($options['selectItems'])){
						if(!isset($result[$trigger['triggerid']]['items']))
							$result[$trigger['triggerid']]['items'] = array();

						$result[$trigger['triggerid']]['items'][] = array('itemid' => $trigger['itemid']);
						unset($trigger['itemid']);
					}

					$result[$trigger['triggerid']] += $trigger;
				}
			}
		}

Copt::memoryPick();
		if(!is_null($options['countOutput'])){
			return $result;
		}

// skipDependent
		if(!is_null($options['skipDependent'])){
			$tids = $triggerids;
			$map = array();

			do{
				$sql = 'SELECT d.triggerid_down, d.triggerid_up, t.value '.
						' FROM trigger_depends d, triggers t '.
						' WHERE '.DBcondition('d.triggerid_down', $tids).
							' AND d.triggerid_up=t.triggerid';
				$db_result = DBselect($sql);

				$tids = array();
				while($row = DBfetch($db_result)){
					if(TRIGGER_VALUE_TRUE == $row['value']){
						if(isset($map[$row['triggerid_down']])){
							foreach($map[$row['triggerid_down']] as $triggerid => $state){
								unset($result[$triggerid]);
								unset($triggerids[$triggerid]);
							}
						}
						else{
							unset($result[$row['triggerid_down']]);
							unset($triggerids[$row['triggerid_down']]);
						}
					}
					else{
						if(isset($map[$row['triggerid_down']])){
							if(!isset($map[$row['triggerid_up']]))
								$map[$row['triggerid_up']] = array();

							$map[$row['triggerid_up']] += $map[$row['triggerid_down']];
						}
						else{
							if(!isset($map[$row['triggerid_up']]))
								$map[$row['triggerid_up']] = array();

							$map[$row['triggerid_up']][$row['triggerid_down']] = 1;
						}
						$tids[] = $row['triggerid_up'];
					}
				}
			}while(!empty($tids));
		}

// withLastEventUnacknowledged
		if(!is_null($options['withLastEventUnacknowledged'])){
			$eventids = array();
			$sql = 'SELECT max(e.eventid) as eventid, e.objectid'.
					' FROM events e '.
					' WHERE e.object='.EVENT_OBJECT_TRIGGER.
						' AND '.DBcondition('e.objectid', $triggerids).
						' AND '.DBcondition('e.value', array(TRIGGER_VALUE_TRUE)).
						' AND e.value_changed='.TRIGGER_VALUE_CHANGED_YES.
					' GROUP BY e.objectid';
			$events_db = DBselect($sql);
			while($event = DBfetch($events_db)){
				$eventids[] = $event['eventid'];
			}

			$correct_triggerids = array();
			$sql = 'SELECT e.objectid'.
					' FROM events e '.
					' WHERE '.DBcondition('e.eventid', $eventids).
						' AND e.acknowledged=0';
			$triggers_db = DBselect($sql);
			while($trigger = DBfetch($triggers_db)){
				$correct_triggerids[$trigger['objectid']] = $trigger['objectid'];
			}
			foreach($result as $triggerid => $trigger){
				if(!isset($correct_triggerids[$triggerid])){
					unset($result[$triggerid]);
					unset($triggerids[$triggerid]);
				}

			}
		}

// Adding Objects
// Adding trigger dependencies
		if(!is_null($options['select_dependencies']) && str_in_array($options['select_dependencies'], $subselects_allowed_outputs)){
			$deps = array();
			$depids = array();

			$sql = 'SELECT triggerid_up, triggerid_down '.
				' FROM trigger_depends '.
				' WHERE '.DBcondition('triggerid_down', $triggerids);
			$db_deps = DBselect($sql);
			while($db_dep = DBfetch($db_deps)){
				if(!isset($deps[$db_dep['triggerid_down']])) $deps[$db_dep['triggerid_down']] = array();
				$deps[$db_dep['triggerid_down']][$db_dep['triggerid_up']] = $db_dep['triggerid_up'];
				$depids[] = $db_dep['triggerid_up'];
			}

			$obj_params = array(
				'triggerids' => $depids,
				'output' => $options['select_dependencies'],
				'expandData' => 1,
				'preservekeys' => 1
			);
			$allowed = $this->get($obj_params); //allowed triggerids

			foreach($deps as $triggerid => $deptriggers){
				foreach($deptriggers as $num => $deptriggerid){
					if(isset($allowed[$deptriggerid])){
						$result[$triggerid]['dependencies'][] = $allowed[$deptriggerid];
					}
				}
			}
		}

// Adding groups
		if(!is_null($options['selectGroups']) && str_in_array($options['selectGroups'], $subselects_allowed_outputs)){
			$obj_params = array(
					'nodeids' => $nodeids,
					'output' => $options['selectGroups'],
					'triggerids' => $triggerids,
					'preservekeys' => 1
				);
			$groups = API::HostGroup()->get($obj_params);
			foreach($groups as $groupid => $group){
				$gtriggers = $group['triggers'];
				unset($group['triggers']);

				foreach($gtriggers as $num => $trigger){
					$result[$trigger['triggerid']]['groups'][] = $group;
				}
			}
		}
// Adding hosts
		if(!is_null($options['selectHosts'])){

			$obj_params = array(
				'nodeids' => $nodeids,
				'triggerids' => $triggerids,
				'templated_hosts' => 1,
				'nopermissions' => 1,
				'preservekeys' => 1
			);

			if(is_array($options['selectHosts']) || str_in_array($options['selectHosts'], $subselects_allowed_outputs)){
				$obj_params['output'] = $options['selectHosts'];
				$hosts = API::Host()->get($obj_params);

				if(!is_null($options['limitSelects'])) order_result($hosts, 'host');
				foreach($hosts as $hostid => $host){
					unset($hosts[$hostid]['triggers']);

					$count = array();
					foreach($host['triggers'] as $tnum => $trigger){
						if(!is_null($options['limitSelects'])){
							if(!isset($count[$trigger['triggerid']])) $count[$trigger['triggerid']] = 0;
							$count[$trigger['triggerid']]++;

							if($count[$trigger['triggerid']] > $options['limitSelects']) continue;
						}

						$result[$trigger['triggerid']]['hosts'][] = &$hosts[$hostid];
					}
				}
			}
			else if(API_OUTPUT_COUNT == $options['selectHosts']){
				$obj_params['countOutput'] = 1;
				$obj_params['groupCount'] = 1;

				$hosts = API::Host()->get($obj_params);
				$hosts = zbx_toHash($hosts, 'hostid');
				foreach($result as $triggerid => $trigger){
					if(isset($hosts[$triggerid]))
						$result[$triggerid]['hosts'] = $hosts[$triggerid]['rowscount'];
					else
						$result[$triggerid]['hosts'] = 0;
				}
			}
		}

// Adding Functions
		if(!is_null($options['select_functions']) && str_in_array($options['select_functions'], $subselects_allowed_outputs)){

			if($options['select_functions'] == API_OUTPUT_EXTEND)
				$sql_select = 'f.*';
			else
				$sql_select = 'f.functionid, f.triggerid';

			$sql = 'SELECT '.$sql_select.
					' FROM functions f '.
					' WHERE '.DBcondition('f.triggerid',$triggerids);
			$res = DBselect($sql);
			while($function = DBfetch($res)){
				$triggerid = $function['triggerid'];
				unset($function['triggerid']);

				$result[$triggerid]['functions'][] = $function;
			}
		}

// Adding Items
		if(!is_null($options['selectItems']) && str_in_array($options['selectItems'], $subselects_allowed_outputs)){
			$obj_params = array(
				'nodeids' => $nodeids,
				'output' => $options['selectItems'],
				'triggerids' => $triggerids,
				'webitems' => 1,
				'nopermissions' => 1,
				'preservekeys' => 1
			);
			$items = API::Item()->get($obj_params);
			foreach($items as $itemid => $item){
				$itriggers = $item['triggers'];
				unset($item['triggers']);
				foreach($itriggers as $num => $trigger){
					$result[$trigger['triggerid']]['items'][] = $item;
				}
			}
		}

// Adding discoveryRule
		if(!is_null($options['selectDiscoveryRule'])){
			$ruleids = $rule_map = array();

			$sql = 'SELECT id.parent_itemid, td.triggerid'.
					' FROM trigger_discovery td, item_discovery id, functions f'.
					' WHERE '.DBcondition('td.triggerid', $triggerids).
						' AND td.parent_triggerid=f.triggerid'.
						' AND f.itemid=id.itemid';
			$db_rules = DBselect($sql);
			while($rule = DBfetch($db_rules)){
				$ruleids[$rule['parent_itemid']] = $rule['parent_itemid'];
				$rule_map[$rule['triggerid']] = $rule['parent_itemid'];
			}

			$obj_params = array(
				'nodeids' => $nodeids,
				'itemids' => $ruleids,
				'nopermissions' => 1,
				'preservekeys' => 1,
			);

			if(is_array($options['selectDiscoveryRule']) || str_in_array($options['selectDiscoveryRule'], $subselects_allowed_outputs)){
				$obj_params['output'] = $options['selectDiscoveryRule'];
				$discoveryRules = API::Item()->get($obj_params);

				foreach($result as $triggerid => $trigger){
					if(isset($rule_map[$triggerid]) && isset($discoveryRules[$rule_map[$triggerid]])){
						$result[$triggerid]['discoveryRule'] = $discoveryRules[$rule_map[$triggerid]];
					}
				}
			}
		}

// expandDescription
		if(!is_null($options['expandDescription'])){
// Function compare values {{{
			foreach($result as $tnum => $trigger){
				preg_match_all('/\$([1-9])/u', $trigger['description'], $numbers);
				preg_match_all('~{[0-9]+}[+\-\*/<>=#]?[\(]*(?P<val>[+\-0-9]+)[\)]*~u', $trigger['expression'], $matches);

				foreach($numbers[1] as $i){
					$rep = isset($matches['val'][$i-1]) ? $matches['val'][$i-1] : '';
					$result[$tnum]['description'] = str_replace('$'.($i), $rep, $result[$tnum]['description']);
				}
			}
// }}}

			$functionids = array();
			$triggers_to_expand_hosts = array();
			$triggers_to_expand_items = array();
			$triggers_to_expand_items2 = array();
			foreach($result as $tnum => $trigger){

				preg_match_all('/{HOSTNAME([1-9]?)}/u', $trigger['description'], $hnums);
				if(!empty($hnums[1])){
					preg_match_all('/{([0-9]+)}/u', $trigger['expression'], $funcs);
					$funcs = $funcs[1];

					foreach($hnums[1] as $fnum){
						$fnum = $fnum ? $fnum : 1;
						if(isset($funcs[$fnum-1])){
							$functionid = $funcs[$fnum-1];
							$functionids[$functionid] = $functionid;
							$triggers_to_expand_hosts[$trigger['triggerid']][$functionid] = $fnum;
						}
					}
				}

				preg_match_all('/{ITEM.LASTVALUE([1-9]?)}/u', $trigger['description'], $inums);
				if(!empty($inums[1])){
					preg_match_all('/{([0-9]+)}/u', $trigger['expression'], $funcs);
					$funcs = $funcs[1];

					foreach($inums[1] as $fnum){
						$fnum = $fnum ? $fnum : 1;
						if(isset($funcs[$fnum-1])){
							$functionid = $funcs[$fnum-1];
							$functionids[$functionid] = $functionid;
							$triggers_to_expand_items[$trigger['triggerid']][$functionid] = $fnum;
						}
					}
				}

				preg_match_all('/{ITEM.VALUE([1-9]?)}/u', $trigger['description'], $inums);
				if(!empty($inums[1])){
					preg_match_all('/{([0-9]+)}/u', $trigger['expression'], $funcs);
					$funcs = $funcs[1];

					foreach($inums[1] as $fnum){
						$fnum = $fnum ? $fnum : 1;
						if(isset($funcs[$fnum-1])){
							$functionid = $funcs[$fnum-1];
							$functionids[$functionid] = $functionid;
							$triggers_to_expand_items2[$trigger['triggerid']][$functionid] = $fnum;
						}
					}
				}
			}

			if(!empty($functionids)){
				$sql = 'SELECT DISTINCT f.triggerid, f.functionid, h.host, i.lastvalue'.
						' FROM functions f,items i,hosts h'.
						' WHERE f.itemid=i.itemid'.
							' AND i.hostid=h.hostid'.
							' AND h.status<>'.HOST_STATUS_TEMPLATE.
							' AND '.DBcondition('f.functionid', $functionids);
				$db_funcs = DBselect($sql);
				while($func = DBfetch($db_funcs)){
					if(isset($triggers_to_expand_hosts[$func['triggerid']][$func['functionid']])){

						$fnum = $triggers_to_expand_hosts[$func['triggerid']][$func['functionid']];
						if($fnum == 1)
							$result[$func['triggerid']]['description'] = str_replace('{HOSTNAME}', $func['host'], $result[$func['triggerid']]['description']);

						$result[$func['triggerid']]['description'] = str_replace('{HOSTNAME'.$fnum.'}', $func['host'], $result[$func['triggerid']]['description']);
					}

					if(isset($triggers_to_expand_items[$func['triggerid']][$func['functionid']])){
						$fnum = $triggers_to_expand_items[$func['triggerid']][$func['functionid']];
						if($fnum == 1)
							$result[$func['triggerid']]['description'] = str_replace('{ITEM.LASTVALUE}', $func['lastvalue'], $result[$func['triggerid']]['description']);

						$result[$func['triggerid']]['description'] = str_replace('{ITEM.LASTVALUE'.$fnum.'}', $func['lastvalue'], $result[$func['triggerid']]['description']);
					}

					if(isset($triggers_to_expand_items2[$func['triggerid']][$func['functionid']])){
						$fnum = $triggers_to_expand_items2[$func['triggerid']][$func['functionid']];
						if($fnum == 1)
							$result[$func['triggerid']]['description'] = str_replace('{ITEM.VALUE}', $func['lastvalue'], $result[$func['triggerid']]['description']);

						$result[$func['triggerid']]['description'] = str_replace('{ITEM.VALUE'.$fnum.'}', $func['lastvalue'], $result[$func['triggerid']]['description']);
					}
				}
			}

			foreach($result as $tnum => $trigger){
				if($res = preg_match_all('/'.ZBX_PREG_EXPRESSION_USER_MACROS.'/', $trigger['description'], $arr)){
					$macros = API::UserMacro()->getMacros(array('macros' => $arr[1], 'triggerid' => $trigger['triggerid']));

					$search = array_keys($macros);
					$values = array_values($macros);

					$result[$tnum]['description'] = str_replace($search, $values, $trigger['description']);
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
 * Get triggerid by host.host and trigger.expression
 *
 * @param _array $triggers multidimensional array with trigger objects
 * @param array $triggers[0,...]['expression']
 * @param array $triggers[0,...]['host']
 * @param array $triggers[0,...]['hostid'] OPTIONAL
 * @param array $triggers[0,...]['description'] OPTIONAL
 */
	public function getObjects($triggerData){
		$options = array(
			'filter' => $triggerData,
			'output'=>API_OUTPUT_EXTEND
		);

		if(isset($triggerData['node']))
			$options['nodeids'] = getNodeIdByNodeName($triggerData['node']);
		else if(isset($triggerData['nodeids']))
			$options['nodeids'] = $triggerData['nodeids'];

// expression is checked later
		unset($options['filter']['expression']);
		$result = $this->get($options);
		if(isset($triggerData['expression'])){
			foreach($result as $tnum => $trigger){
				$tmp_exp = explode_exp($trigger['expression'], false);

				if(strcmp(trim($tmp_exp,' '), trim($triggerData['expression'],' ')) != 0) {
					unset($result[$tnum]);
				}
			}
		}

	return $result;
	}

	public function exists($object){
		$keyFields = array(array('hostid', 'host'), 'description');

		$result = false;

		if(!isset($object['hostid']) && !isset($object['host'])){
			$expr = new CTriggerExpression($object);

			if(!empty($expr->errors)) return false;
			if(empty($expr->data['hosts'])) return false;

			$object['host'] = reset($expr->data['hosts']);
		}

		$options = array(
			'filter' => array_merge(zbx_array_mintersect($keyFields, $object), array('flags' => null)),
			'output' => API_OUTPUT_EXTEND,
			'nopermissions' => 1,
		);

		if(isset($object['node']))
			$options['nodeids'] = getNodeIdByNodeName($object['node']);
		else if(isset($object['nodeids']))
			$options['nodeids'] = $object['nodeids'];

		$triggers = $this->get($options);
		foreach($triggers as $tnum => $trigger){
			$tmp_exp = explode_exp($trigger['expression'], false);
			if(strcmp($tmp_exp, $object['expression']) == 0){
				$result = true;
				break;
			}
		}

	return $result;
	}

	public function checkInput(&$triggers, $method){
		$create = ($method == 'create');
		$update = ($method == 'update');
		$delete = ($method == 'delete');

// permissions
		if($delete){
			$trigger_db_fields = array('triggerid'=> null);
			$dbTriggers = $this->get(array(
				'triggerids' => zbx_objectValues($triggers, 'triggerid'),
				'output' => API_OUTPUT_EXTEND,
				'editable' => true,
				'preservekeys' => true,
			));
		}
		else if($update){
			$trigger_db_fields = array('triggerid'=> null);
			$dbTriggers = $this->get(array(
				'triggerids' => zbx_objectValues($triggers, 'triggerid'),
				'output' => API_OUTPUT_EXTEND,
				'editable' => true,
				'preservekeys' => true,
				'select_dependencies' => API_OUTPUT_REFER, // on update we also must check if dependencies have changed
			));
		}
		else{
			$trigger_db_fields = array(
				'description' => null,
				'expression' => null,
				'error' => _('Trigger just added. No status update so far.'),
				'value'	=> 2,
			);
		}


		foreach($triggers as $tnum => &$trigger){
			$currentTrigger = $triggers[$tnum];

			if(!check_db_fields($trigger_db_fields, $trigger)){
				self::exception(ZBX_API_ERROR_PARAMETERS, _s('Incorrect fields for trigger'));
			}

			if($update){
				if(!isset($dbTriggers[$trigger['triggerid']]))
					self::exception(ZBX_API_ERROR_PARAMETERS, S_NO_PERMISSIONS);

				$dbTrigger = $dbTriggers[$trigger['triggerid']];
				$currentTrigger['description'] = $dbTrigger['description'];
			}
			else if($delete){
				if(!isset($dbTriggers[$trigger['triggerid']]))
					self::exception(ZBX_API_ERROR_PARAMETERS, S_NO_PERMISSIONS);

				if($dbTriggers[$trigger['triggerid']]['templateid'] != 0){
					self::exception(
						ZBX_API_ERROR_PARAMETERS,
						_s('Cannot delete templated trigger [%1$s:%2$s]', $dbTriggers[$trigger['triggerid']]['description'], explode_exp($dbTriggers[$trigger['triggerid']]['expression'], false))
					);
				}

				continue;
			}

			if($update){
				if(isset($trigger['expression'])){
					$expression_full = explode_exp($dbTrigger['expression']);
					if(strcmp($trigger['expression'], $expression_full) == 0){
						unset($trigger['expression']);
					}
				}

				if(isset($trigger['description']) && strcmp($trigger['description'], $dbTrigger['description']) == 0)
					unset($trigger['description']);

				if(isset($trigger['priority']) && ($trigger['priority'] == $dbTrigger['priority']))
					unset($trigger['priority']);

				if(isset($trigger['type']) && ($trigger['type'] == $dbTrigger['type']))
					unset($trigger['type']);

				if(isset($trigger['comments']) && strcmp($trigger['comments'], $dbTrigger['comments']) == 0)
					unset($trigger['comments']);

				if(isset($trigger['url']) && strcmp($trigger['url'], $dbTrigger['url']) == 0)
					unset($trigger['url']);

				if(isset($trigger['status']) && ($trigger['status'] == $dbTrigger['status']))
					unset($trigger['status']);

				// checking if dependencies have changed
				if(isset($trigger['dependencies']) && count($trigger['dependencies']) == count($dbTrigger['dependencies'])){
					$equal = true;
					foreach($trigger['dependencies'] as $i => $trDep){
						// if at least one of the elements is different
						if (bccomp($trigger['dependencies'][$i], $dbTrigger['dependencies'][$i]['triggerid']) != 0){
							// we will not unset dependencies array
							$equal = false;
							break;
						}
					}
					if($equal){
						unset($trigger['dependencies']);
					}

				}
			}

// if some of the properties are unchanged, no need to update them in DB

// validating trigger expression
			if(isset($trigger['expression'])){
// expression permissions
				$expressionData = new CTriggerExpression($trigger);
				if(!empty($expressionData->errors)){
					self::exception(ZBX_API_ERROR_PARAMETERS, implode(' ', $expressionData->errors));
				}

				$hosts = API::Host()->get(array(
					'filter' => array('host' => $expressionData->data['hosts']),
					'editable' => true,
					'output' => array('hostid', 'host'),
					'templated_hosts' => true,
					'preservekeys' => true
				));
				$hosts = zbx_toHash($hosts, 'host');
				foreach($expressionData->data['hosts'] as $host){
					if(!isset($hosts[$host]))
						self::exception(ZBX_API_ERROR_PARAMETERS, _s('Incorrect trigger expression. Host "%s" does not exist or you have no access to this host.', $host));
				}
			}

// check existing

			if($create){
				$existTrigger = API::Trigger()->exists(array(
					'description' => $trigger['description'],
					'expression' => $trigger['expression'])
				);

				if($existTrigger){
					self::exception(ZBX_API_ERROR_PARAMETERS, _s('Trigger [%1$s:%2$s] already exists.', $trigger['description'], $trigger['expression']));
				}
			}
		}
		unset($trigger);
	}
/**
 * Add triggers
 *
 * Trigger params: expression, description, type, priority, status, comments, url, templateid
 *
 * @param array $triggers
 * @return boolean
 */
	public function create($triggers){
		$triggers = zbx_toArray($triggers);

			$this->checkInput($triggers, __FUNCTION__);

			$this->createReal($triggers);

			foreach($triggers as $trigger)
				$this->inherit($trigger);

			return array('triggerids' => zbx_objectValues($triggers, 'triggerid'));
	}

/**
 * Update triggers
 *
 * @param array $triggers
 * @return boolean
 */
	public function update($triggers){
		$triggers = zbx_toArray($triggers);
		$triggerids = zbx_objectValues($triggers, 'triggerid');

			$this->checkInput($triggers, __FUNCTION__);

			$this->updateReal($triggers);

			foreach($triggers as $trigger)
				$this->inherit($trigger);

			return array('triggerids' => $triggerids);
	}

/**
 * Delete triggers
 *
 * @param array $triggerids array with trigger ids
 * @return array
 */
	public function delete($triggerids, $nopermissions=false){
		$triggerids = zbx_toArray($triggerids);
		$triggers = zbx_toObject($triggerids, 'triggerid');

			if(empty($triggerids)) self::exception(ZBX_API_ERROR_PARAMETERS, _('Empty input parameter'));

// TODO: remove $nopermissions hack
			if(!$nopermissions){
				$this->checkInput($triggers, __FUNCTION__);
			}

// get child triggers
			$parent_triggerids = $triggerids;
			do{
				$db_items = DBselect('SELECT triggerid FROM triggers WHERE ' . DBcondition('templateid', $parent_triggerids));
				$parent_triggerids = array();
				while($db_trigger = DBfetch($db_items)){
					$parent_triggerids[] = $db_trigger['triggerid'];
					$triggerids[$db_trigger['triggerid']] = $db_trigger['triggerid'];
				}
			} while(!empty($parent_triggerids));


// select all triggers which are deleted (include childs)
			$options = array(
				'triggerids' => $triggerids,
				'output' => API_OUTPUT_EXTEND,
				'nopermissions' => true,
				'preservekeys' => true,
			);
			$del_triggers = $this->get($options);

			DB::delete('events', array(
				'objectid' => $triggerids,
				'object' => EVENT_OBJECT_TRIGGER,
			));

			DB::delete('sysmaps_elements', array(
				'elementid' => $triggerids,
				'elementtype' => SYSMAP_ELEMENT_TYPE_TRIGGER,
			));

// disable actions
			$actionids = array();
			$sql = 'SELECT DISTINCT actionid '.
					' FROM conditions '.
					' WHERE conditiontype='.CONDITION_TYPE_TRIGGER.
						' AND '.DBcondition('value', $triggerids, false, true);   // FIXED[POSIBLE value type violation]!!!
			$db_actions = DBselect($sql);
			while($db_action = DBfetch($db_actions)){
				$actionids[$db_action['actionid']] = $db_action['actionid'];
			}

			DBexecute('UPDATE actions '.
					' SET status='.ACTION_STATUS_DISABLED.
					' WHERE '.DBcondition('actionid', $actionids));

// delete action conditions
			DB::delete('conditions', array('conditiontype' => CONDITION_TYPE_TRIGGER,'value' => $triggerids));

// TODO: REMOVE info
			foreach($del_triggers as $triggerid => $trigger){
				info(_s('Trigger [%1$s:%2$s] deleted.', $trigger['description'], explode_exp($trigger['expression'], false)));
				add_audit_ext(AUDIT_ACTION_DELETE, AUDIT_RESOURCE_TRIGGER, $trigger['triggerid'], $trigger['description'].':'.$trigger['expression'], NULL, NULL, NULL);
			}


			DB::delete('triggers', array('triggerid' => $triggerids));

			update_services_status_all();

			return array('triggerids' => $triggerids);
	}

/**
 * Add dependency for trigger
 *
 * @param array $triggersData
 * @param array $triggers_data['triggerid]
 * @param array $triggers_data['dependsOnTriggerid']
 * @return boolean
 */
	public function addDependencies($triggersData){
		$triggersData = zbx_toArray($triggersData);
		$triggerids = array();

			foreach($triggersData as $num => $dep){
				$triggerids[$dep['triggerid']] = $dep['triggerid'];

				$result = (bool) insert_dependency($dep['triggerid'], $dep['dependsOnTriggerid']);
				if(!$result)
					self::exception(ZBX_API_ERROR_PARAMETERS, 'Cannot create dependency');
			}

			return array('triggerids' => $triggerids);
	}

/**
 * Delete trigger dependencis
 *
 * @param array $triggersData
 * @param array $triggers[0,...]['triggerid']
 * @return boolean
 */
	public function deleteDependencies($triggersData){
		$triggersData = zbx_toArray($triggersData);

		$triggerids = array();
		foreach($triggersData as $num => $trigger){
			$triggerids[] = $trigger['triggerid'];
		}

			$result = delete_dependencies_by_triggerid($triggerids);
			if(!$result)
				self::exception(ZBX_API_ERROR_PARAMETERS, 'Cannot delete dependency');

			return array('triggerids' => zbx_objectValues($triggersData, 'triggerid'));
	}

	protected function createReal(&$triggers){
		$triggers = zbx_toArray($triggers);

		$triggerids = DB::insert('triggers', $triggers);

		foreach($triggers as $tnum => $trigger){
			$triggerid = $triggers[$tnum]['triggerid'] = $triggerids[$tnum];

			addEvent($triggerid, TRIGGER_VALUE_UNKNOWN);

			$expression = implode_exp($trigger['expression'], $triggerid);
			if(is_null($expression)){
				self::exception(ZBX_API_ERROR_PARAMETERS, _s('Cannot implode expression "%s".', $trigger['expression']));
			}

			DB::update('triggers', array(
				'values' => array('expression' => $expression),
				'where' => array('triggerid='.$triggerid)
			));

			if (isset($trigger['dependencies'])){

				foreach($trigger['dependencies'] as $triggerid_up){
					$depsToInsert = array(
						'triggerid_down' => $triggerid,
						'triggerid_up' => is_array($triggerid_up) ? $triggerid_up['triggerid'] : $triggerid_up
					);
					DB::insert('trigger_depends', array($depsToInsert));
				}
			}

			$this->validateDependencies($triggers);

			info(_s('Trigger [%1$s:%2$s] created.', $trigger['description'], $trigger['expression']));
		}

	}

	protected function updateReal($triggers){
		$triggers = zbx_toArray($triggers);

		$options = array(
			'triggerids' => zbx_objectValues($triggers, 'triggerid'),
			'output' => API_OUTPUT_EXTEND,
			'preservekeys' => true,
			'nopermissions' => true,
		);
		$dbTriggers = $this->get($options);

		$description_changed = $expression_changed = false;
		$messages = array();
		foreach($triggers as &$trigger){
			$dbTrigger = $dbTriggers[$trigger['triggerid']];

			if(isset($trigger['description']) && (strcmp($dbTrigger['description'], $trigger['description']) != 0)){
				$description_changed = true;
			}
			else{
				$trigger['description'] = $dbTrigger['description'];
			}

			$expression_full = explode_exp($dbTrigger['expression'], 0);
			if(isset($trigger['expression']) && (strcmp($expression_full, $trigger['expression']) != 0)){
				$expression_changed = true;
				$expression_full = $trigger['expression'];
				$trigger['error'] = 'Trigger expression updated. No status update so far.';
			}

			if($description_changed || $expression_changed){
				$expressionData = new CTriggerExpression(array('expression' => $expression_full));

				if(!empty($expressionData->errors)){
					self::exception(ZBX_API_ERROR_PARAMETERS, $expressionData->errors);
				}

				$host = reset($expressionData->data['hosts']);

				$options = array(
					'filter' => array('description' => $trigger['description'], 'host' => $host),
					'output' => API_OUTPUT_EXTEND,
					'editable' => true,
					'nopermissions' => true,
				);
				$triggers_exist = API::Trigger()->get($options);

				$trigger_exist = false;
				foreach($triggers_exist as $tnum => $tr){
					$tmp_exp = explode_exp($tr['expression'], false);
					if(strcmp($tmp_exp, $expression_full) == 0){
						$trigger_exist = $tr;
						break;
					}
				}
				if($trigger_exist && (bccomp($trigger_exist['triggerid'],$trigger['triggerid']) != 0)){
					self::exception(ZBX_API_ERROR_PARAMETERS, _s('Trigger "%s" already exists.',$trigger['description']));
				}
			}


			if($expression_changed){
				delete_function_by_triggerid($trigger['triggerid']);

				$trigger['expression'] = implode_exp($expression_full, $trigger['triggerid']);

				if(isset($trigger['status']) && ($trigger['status'] != TRIGGER_STATUS_ENABLED)){
					if($trigger['value_flags'] == TRIGGER_VALUE_FLAG_NORMAL){
						addEvent($trigger['triggerid'], TRIGGER_VALUE_UNKNOWN);

						$trigger['value_flags'] = TRIGGER_VALUE_FLAG_UNKNOWN;
					}
				}
			}

			$trigger_update = $trigger;
			if(!$description_changed)
				unset($trigger_update['description']);
			if(!$expression_changed)
				unset($trigger_update['expression']);

			DB::update('triggers', array(
				'values' => $trigger_update,
				'where' => array('triggerid='.$trigger['triggerid'])
			));


			if(isset($trigger['dependencies'])){
				// deleting existing ones
				DB::delete('trigger_depends', array('triggerid_down' => $trigger['triggerid']));

				foreach($trigger['dependencies'] as $triggerid_up){
					DB::insert('trigger_depends', array(array(
						'triggerid_down' => $trigger['triggerid'],
						'triggerid_up' => $triggerid_up
					)));
				}
			}

			$description = isset($trigger['description']) ? $trigger['description'] : $dbTrigger['description'];
			$expression = isset($trigger['expression']) ? $trigger['expression'] : explode_exp($dbTrigger['expression'], false);
			$trigger['expression'] = $expression;
			$messages[] = _s('Trigger "%1$s:%2$s" updated.', $description, $expression);

		}
		unset($trigger);
		// now, check if current situation with dependencies is valid
		$this->validateDependencies($triggers);
		foreach($messages as $message){
			info($message);
		}

	}

	protected function inherit($trigger, $hostids=null){

		$triggerTemplate = API::Template()->get(array(
			'triggerids' => $trigger['triggerid'],
			'output' => API_OUTPUT_EXTEND,
			'nopermissions' => 1,
		));
		$triggerTemplate = reset($triggerTemplate);
		if(!$triggerTemplate) return true;

		if(!isset($trigger['expression']) || !isset($trigger['description'])){
			$options = array(
				'triggerids' => $trigger['triggerid'],
				'output' => API_OUTPUT_EXTEND,
				'preservekeys' => true,
				'nopermissions' => true,
			);
			$dbTrigger = $this->get($options);
			$dbTrigger = reset($dbTrigger);

			if(!isset($trigger['description']))
				$trigger['description'] = $dbTrigger['description'];
			if(!isset($trigger['expression']))
				$trigger['expression'] = explode_exp($dbTrigger['expression'], 0);
		}


		$options = array(
			'templateids' => $triggerTemplate['templateid'],
			'output' => array('hostid', 'host'),
			'preservekeys' => 1,
			'hostids' => $hostids,
			'nopermissions' => 1,
			'templated_hosts' => 1,
		);
		$chd_hosts = API::Host()->get($options);

		foreach($chd_hosts as $chd_host){
			$newTrigger = $trigger;

			if(isset($trigger['dependencies']) && !is_null($trigger['dependencies'])){
				$newTrigger['dependencies'] = replace_template_dependencies($trigger['dependencies'], $chd_host['hostid']);
			}
			$newTrigger['templateid'] = $trigger['triggerid'];

			$newTrigger['expression'] = str_replace('{'.$triggerTemplate['host'].':', '{'.$chd_host['host'].':', $trigger['expression']);

// check if templated trigger exists
			$childTriggers = $this->get(array(
				'filter' => array('templateid' => $newTrigger['triggerid']),
				'output' => API_OUTPUT_EXTEND,
				'preservekeys' => 1,
				'hostids' => $chd_host['hostid']
			));

			if($childTrigger = reset($childTriggers)){
				$childTrigger['expression'] = explode_exp($childTrigger['expression'], 0);

				if((strcmp($childTrigger['expression'], $newTrigger['expression']) != 0)
					|| (strcmp($childTrigger['description'], $newTrigger['description']) != 0)
				){
					$exists = $this->exists(array(
						'description' => $newTrigger['description'],
						'expression' => $newTrigger['expression'],
						'hostids' => $chd_host['hostid']
					));
					if($exists){
						self::exception(ZBX_API_ERROR_PARAMETERS,
							_s('Trigger [%1$s] already exists on [%2$s]', $newTrigger['description'], $chd_host['host']));
					}
				}
				else if($childTrigger['flags'] != ZBX_FLAG_DISCOVERY_NORMAL){
					self::exception(ZBX_API_ERROR_PARAMETERS, _s('Trigger with same name but other type exists'));
				}

				$newTrigger['triggerid'] = $childTrigger['triggerid'];
				$this->updateReal($newTrigger);
			}
			else{
				$options = array(
					'filter' => array(
						'description' => $newTrigger['description'],
						'flags' => null
					),
					'output' => API_OUTPUT_EXTEND,
					'preservekeys' => 1,
					'nopermissions' => 1,
					'hostids' => $chd_host['hostid']
				);
				$childTriggers = $this->get($options);

				$childTrigger = false;
				foreach($childTriggers as $tnum => $tr){
					$tmp_exp = explode_exp($tr['expression'], false);
					if(strcmp($tmp_exp, $newTrigger['expression']) == 0){
						$childTrigger = $tr;
						break;
					}
				}

				if($childTrigger){
					if($childTrigger['templateid'] != 0){
						self::exception(ZBX_API_ERROR_PARAMETERS,
							_s('Trigger [%1$s] already exists on [%2$s]', $childTrigger['description'], $chd_host['host']));
					}
					else if($childTrigger['flags'] != $newTrigger['flags']){
						self::exception(ZBX_API_ERROR_PARAMETERS, _s('Trigger with same name but other type exists'));
					}

					$newTrigger['triggerid'] = $childTrigger['triggerid'];
					$this->updateReal($newTrigger);
				}
				else{
					$this->createReal($newTrigger);
					$newTrigger = reset($newTrigger);
				}
			}
			$this->inherit($newTrigger);
		}
	}

	public function syncTemplates($data){

			$data['templateids'] = zbx_toArray($data['templateids']);
			$data['hostids'] = zbx_toArray($data['hostids']);

			$options = array(
				'hostids' => $data['hostids'],
				'editable' => true,
				'preservekeys' => true,
				'templated_hosts' => true,
				'output' => API_OUTPUT_SHORTEN
			);
			$allowedHosts = API::Host()->get($options);
			foreach($data['hostids'] as $hostid){
				if(!isset($allowedHosts[$hostid])){
					self::exception(ZBX_API_ERROR_PERMISSIONS, S_NO_PERMISSION);
				}
			}
			$options = array(
				'templateids' => $data['templateids'],
				'preservekeys' => true,
				'editable' => true,
				'output' => API_OUTPUT_SHORTEN
			);
			$allowedTemplates = API::Template()->get($options);
			foreach($data['templateids'] as $templateid){
				if(!isset($allowedTemplates[$templateid])){
					self::exception(ZBX_API_ERROR_PERMISSIONS, S_NO_PERMISSION);
				}
			}

			$options = array(
				'hostids' => $data['templateids'],
				'preservekeys' => 1,
				'output' => API_OUTPUT_EXTEND,
				'select_dependencies' => API_OUTPUT_REFER
			);
			$triggers = $this->get($options);

			foreach($triggers as $trigger){
				// we must do this, because validateDependencies() expects plain array of ids
				$currTriggerDependencies = array();
				foreach($trigger['dependencies'] as $dep){
					$currTriggerDependencies[] = $dep['triggerid'];
				}
				$trigger['dependencies'] = $currTriggerDependencies;

				$trigger['expression'] = explode_exp($trigger['expression'], false);
				$this->inherit($trigger, $data['hostids']);
			}

			return true;
	}

	protected function validateDependencies($triggers){

		foreach($triggers as $trigger){
			if(!isset($trigger['dependencies']) || empty($trigger['dependencies'])) continue;

// check circular dependency {{{
			$triggeridDown = $trigger['dependencies'];

			do{
				$sql = 'SELECT triggerid_up, description '.
						' FROM trigger_depends, triggers'.
						' WHERE trigger_depends.triggerid_down = triggers.triggerid AND '.DBcondition('trigger_depends.triggerid_down', $triggeridDown);
				$dbTriggersUp = DBselect($sql);
				$triggeridsUp = array();
				while($triggerUp = DBfetch($dbTriggersUp)){
					if(bccomp($triggerUp['triggerid_up'],$trigger['triggerid']) == 0){
						self::exception(
							ZBX_API_ERROR_PARAMETERS,
							$triggerUp['description'] === $trigger['description']
							? _s('Cannot add circular dependency: trigger "%1$s" cannot depend on itself.', $trigger['description'])
							: _s('Cannot add circular dependency: trigger "%1$s" depends on trigger "%2$s".', $triggerUp['description'] , $trigger['description'])
						);
					}
					$triggeridsUp[] = $triggerUp['triggerid_up'];
				}
				$triggeridDown = $triggeridsUp;
			} while(!empty($triggeridsUp));
// }}} check circular dependency


			$expr = new CTriggerExpression($trigger);

			$templates = API::Template()->get(array(
				'output' => array('hostid','host'),
				'filter' => array('host' => $expr->data['hosts']),
				'nopermissions' => true,
				'preservekeys' => true
			));
			$templateids = array_keys($templates);
			$templateids = zbx_toHash($templateids);

			$templated_trigger = !empty($templateids);

			// which templates contain triggers that we are about to add dependencies to?
			$dep_templateids = array();
			$db_dephosts = get_hosts_by_triggerid($trigger['dependencies']);
			while($dephost = DBfetch($db_dephosts)) {
				if($dephost['status'] == HOST_STATUS_TEMPLATE){ //template
					$dep_templateids[$dephost['hostid']] = $dephost['hostid'];
				}

				//we have a host trigger added to template trigger or otherwise
				if($templated_trigger && $dephost['status'] != HOST_STATUS_TEMPLATE){
					self::exception(ZBX_API_ERROR_PARAMETERS, _s('Cannot add dependency on trigger inside host "%s", only other templated triggers can be added as dependencies to templated trigger.', $dephost['host']));
				}
				if(!$templated_trigger && $dephost['status'] == HOST_STATUS_TEMPLATE){
					self::exception(ZBX_API_ERROR_PARAMETERS, _s('Cannot add dependency on trigger inside template "%s", only other host triggers can be added as dependencies to host trigger.', $dephost['host']));
				}
			}

			// new dependencies are about to be added?
			$tdiff = array_diff($templateids, $dep_templateids);
			if(!empty($templateids) && !empty($dep_templateids) && !empty($tdiff)){
				$tpls = zbx_array_merge($templateids, $dep_templateids);
				$sql = 'SELECT DISTINCT ht.templateid, ht.hostid, h.host'.
					' FROM hosts_templates ht, hosts h'.
					' WHERE h.hostid=ht.hostid'.
						' AND '.DBcondition('ht.templateid', $tpls);

				$db_lowlvltpl = DBselect($sql);
				$map = array();
				while($lowlvltpl = DBfetch($db_lowlvltpl)){
					if(!isset($map[$lowlvltpl['hostid']])) $map[$lowlvltpl['hostid']] = array();
					$map[$lowlvltpl['hostid']][$lowlvltpl['templateid']] = $lowlvltpl['host'];
				}

				foreach($map as $hostid => $templates){
					$set_with_dep = false;

					foreach($templateids as $tplid){
						if(isset($templates[$tplid])){
							$set_with_dep = true;
							break;
						}
					}
					foreach($dep_templateids as $dep_tplid){
						if(!isset($templates[$dep_tplid]) && $set_with_dep){
							self::exception(ZBX_API_ERROR_PARAMETERS, _s('Not all templates are linked to host "%s"', reset($templates)));
						}
					}
				}
			}
		}
	}

	public function isReadable($ids){
		if(!is_array($ids)) return false;
		if(empty($ids)) return true;

		$ids = array_unique($ids);

		$count = $this->get(array(
			'nodeids' => get_current_nodeid(true),
			'triggerids' => $ids,
			'output' => API_OUTPUT_SHORTEN,
			'countOutput' => true
		));

		return (count($ids) == $count);
	}

	public function isWritable($ids){
		if(!is_array($ids)) return false;
		if(empty($ids)) return true;

		$ids = array_unique($ids);

		$count = $this->get(array(
			'nodeids' => get_current_nodeid(true),
			'triggerids' => $ids,
			'output' => API_OUTPUT_SHORTEN,
			'editable' => true,
			'countOutput' => true
		));

		return (count($ids) == $count);
	}

}
?>
