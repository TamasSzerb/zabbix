<?php
/*
** ZABBIX
** Copyright (C) 2000-2009 SIA Zabbix
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
/**
 * Class containing methods for operations with Triggers
 *
 */
class CTrigger extends CZBXAPI{

	public static $error = array();

/**
 * Get Triggers data
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param _array $options
 * @param array $options['itemids']
 * @param array $options['hostids']
 * @param array $options['groupids']
 * @param array $options['triggerids']
 * @param array $options['applicationids']
 * @param array $options['status']
 * @param array $options['editable']
 * @param array $options['extendoutput']
 * @param array $options['count']
 * @param array $options['pattern']
 * @param array $options['limit']
 * @param array $options['order']
 * @return array|int item data as array or false if error
 */
	public static function get($options=array()){
		global $USER_DETAILS;

		$result = array();
		$user_type = $USER_DETAILS['type'];
		$userid = $USER_DETAILS['userid'];

		$sort_columns = array('triggerid', 'description', 'status', 'priority', 'lastchange'); // allowed columns for sorting


		$sql_parts = array(
			'select' => array('triggers' => 't.triggerid'),
			'from' => array('t' => 'triggers t'),
			'where' => array(),
			'order' => array(),
			'limit' => null,
			);

		$def_options = array(
			'nodeids'				=> null,
			'groupids'				=> null,
			'hostids'				=> null,
			'triggerids'			=> null,
			'itemids'				=> null,
			'applicationids'		=> null,
			'status'				=> null,
			'monitored' 			=> null,
			'severity'				=> null,
			'templated'				=> null,
			'inherited'				=> null,
			'not_templated_triggers'	=> null,
			'editable'				=> null,
			'nopermissions'			=> null,
			'only_problems'			=> null,
// filter
			'filter'				=> null,
			'group'					=> null,
			'host'					=> null,
			'only_true'				=> null,
			'min_severity'			=> null,
//
			'pattern'				=> '',

// OutPut
			'extendoutput'			=> null,
			'select_hosts'			=> null,
			'select_items'			=> null,
			'select_dependencies'	=> null,
			'count'					=> null,
			'expand_data'			=> null,
			'preservekeys'			=> null,

			'sortfield'				=> '',
			'sortorder'				=> '',
			'limit'					=> null
		);

		$options = zbx_array_merge($def_options, $options);


// editable + PERMISSION CHECK
		if(defined('ZBX_API_REQUEST')){
			$options['nopermissions'] = false;
		}

		if((USER_TYPE_SUPER_ADMIN == $user_type) || $options['nopermissions']){
		}
		else{
			$permission = $options['editable']?PERM_READ_WRITE:PERM_READ_ONLY;

			$sql_parts['from']['f'] = 'functions f';
			$sql_parts['from']['i'] = 'items i';
			$sql_parts['from']['hg'] = 'hosts_groups hg';
			$sql_parts['from']['r'] = 'rights r';
			$sql_parts['from']['ug'] = 'users_groups ug';
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
		}

// nodeids
		$nodeids = $options['nodeids'] ? $options['nodeids'] : get_current_nodeid();

// groupids
		if(!is_null($options['groupids'])){
			zbx_value2array($options['groupids']);

			if(!is_null($options['extendoutput'])){
				$sql_parts['select']['groupid'] = 'hg.groupid';
			}

			$sql_parts['from']['f'] = 'functions f';
			$sql_parts['from']['i'] = 'items i';
			$sql_parts['from']['hg'] = 'hosts_groups hg';
			$sql_parts['where']['hgi'] = 'hg.hostid=i.hostid';
			$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
			$sql_parts['where']['fi'] = 'f.itemid=i.itemid';
			$sql_parts['where']['hg'] = DBcondition('hg.groupid', $options['groupids']);
		}

// hostids
		if(!is_null($options['hostids'])){
			zbx_value2array($options['hostids']);

			if(!is_null($options['extendoutput'])){
				$sql_parts['select']['hostid'] = 'i.hostid';
			}

			$sql_parts['from']['f'] = 'functions f';
			$sql_parts['from']['i'] = 'items i';
			$sql_parts['where']['i'] = DBcondition('i.hostid', $options['hostids']);
			$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
			$sql_parts['where']['fi'] = 'f.itemid=i.itemid';
		}

// triggerids
		if(!is_null($options['triggerids'])){
			zbx_value2array($options['triggerids']);

			$sql_parts['where'][] = DBcondition('t.triggerid', $options['triggerids']);
		}

// itemids
		if(!is_null($options['itemids'])){
			zbx_value2array($options['itemids']);

			if(!is_null($options['extendoutput'])){
				$sql_parts['select']['itemid'] = 'f.itemid';
			}

			$sql_parts['from']['f'] = 'functions f';
			$sql_parts['where']['f'] = DBcondition('f.itemid', $options['itemids']);
			$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
		}

// applicationids
		if(!is_null($options['applicationids'])){
			zbx_value2array($options['applicationids']);

			if(!is_null($options['extendoutput'])){
				$sql_parts['select']['applicationid'] = 'a.applicationid';
			}

			$sql_parts['from']['f'] = 'functions f';
			$sql_parts['from']['i'] = 'items i';
			$sql_parts['from']['a'] = 'applications a';
			$sql_parts['where']['a'] = DBcondition('a.applicationid', $options['applicationids']);
			$sql_parts['where']['ia'] = 'i.hostid=a.hostid';
			$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
			$sql_parts['where']['fi'] = 'f.itemid=i.itemid';
		}

// status
		if(!is_null($options['status'])){
			$sql_parts['where'][] = 't.status='.$options['status'];
		}

// monitored
		if(!is_null($options['monitored'])){
			$sql_parts['from']['f'] = 'functions f';
			$sql_parts['from']['i'] = 'items i';
			$sql_parts['from']['h'] = 'hosts h';
			$sql_parts['where']['ih'] = 'i.hostid=h.hostid';
			$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
			$sql_parts['where']['fi'] = 'f.itemid=i.itemid';
			$sql_parts['where'][] = 'i.status='.ITEM_STATUS_ACTIVE;
			$sql_parts['where'][] = 't.status='.TRIGGER_STATUS_ENABLED;
			$sql_parts['where'][] = 'h.status='.HOST_STATUS_MONITORED;
		}

// severity
		if(!is_null($options['severity'])){
			$sql_parts['where'][] = 't.priority='.$options['severity'];
		}
// only_problems
		if(!is_null($options['only_problems'])){
			$sql_parts['where']['ot'] = 't.value='.TRIGGER_VALUE_TRUE;
		}

// templated
		if(!is_null($options['templated'])){
			$sql_parts['from']['f'] = 'functions f';
			$sql_parts['from']['i'] = 'items i';
			$sql_parts['from']['h'] = 'hosts h';
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
				$sql_parts['where'][] = 't.templateid<>0';
			}
			else{
				$sql_parts['where'][] = 't.templateid=0';
			}
		}


// extendoutput
		if(!is_null($options['extendoutput'])){
			$sql_parts['select']['triggers'] = 't.*';
		}

// expand_data
		if(!is_null($options['expand_data'])){
			$sql_parts['select']['host'] = 'h.host';
			$sql_parts['from']['f'] = 'functions f';
			$sql_parts['from']['i'] = 'items i';
			$sql_parts['from']['h'] = 'hosts h';
			$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
			$sql_parts['where']['fi'] = 'f.itemid=i.itemid';
			$sql_parts['where']['hi'] = 'h.hostid=i.hostid';
		}

// pattern
		if(!zbx_empty($options['pattern'])){
			$sql_parts['where'][] = ' UPPER(t.description) LIKE '.zbx_dbstr('%'.strtoupper($options['pattern']).'%');
		}


// --- FILTER ---
		if(!is_null($options['filter'])){
// group
			if(!is_null($options['group'])){
				if(!is_null($options['extendoutput'])){
					$sql_parts['select']['name'] = 'g.name';
				}

				$sql_parts['from']['f'] = 'functions f';
				$sql_parts['from']['i'] = 'items i';
				$sql_parts['from']['hg'] = 'hosts_groups hg';
				$sql_parts['from']['g'] = 'groups g';
				$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
				$sql_parts['where']['fi'] = 'f.itemid=i.itemid';
				$sql_parts['where']['hgi'] = 'hg.hostid=i.hostid';
				$sql_parts['where']['ghg'] = 'g.groupid = hg.groupid';
				$sql_parts['where'][] = ' UPPER(g.name)='.zbx_dbstr(strtoupper($options['group']));
			}

// host
			if(!is_null($options['host'])){
				if(!is_null($options['extendoutput'])){
					$sql_parts['select']['host'] = 'h.host';
				}

				$sql_parts['from']['f'] = 'functions f';
				$sql_parts['from']['i'] = 'items i';
				$sql_parts['from']['h'] = 'hosts h';
				$sql_parts['where']['i'] = DBcondition('i.hostid', $options['hostids']);
				$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
				$sql_parts['where']['fi'] = 'f.itemid=i.itemid';
				$sql_parts['where']['hi'] = 'h.hostid=i.hostid';
				$sql_parts['where'][] = ' UPPER(h.host)='.zbx_dbstr(strtoupper($options['host']));
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
		}

// count
		if(!is_null($options['count'])){
			$options['sortfield'] = '';

			$sql_parts['select'] = array('COUNT(DISTINCT t.triggerid) as rowscount');
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
		$sql_parts['order'] = array_unique($sql_parts['order']);

		$sql_select = '';
		$sql_from = '';
		$sql_where = '';
		$sql_order = '';
		if(!empty($sql_parts['select']))	$sql_select.= implode(',',$sql_parts['select']);
		if(!empty($sql_parts['from']))		$sql_from.= implode(',',$sql_parts['from']);
		if(!empty($sql_parts['where']))		$sql_where.= ' AND '.implode(' AND ',$sql_parts['where']);
		if(!empty($sql_parts['order']))		$sql_order.= ' ORDER BY '.implode(',',$sql_parts['order']);
		$sql_limit = $sql_parts['limit'];

		$sql = 'SELECT '.$sql_select.
				' FROM '.$sql_from.
				' WHERE '.DBin_node('t.triggerid', $nodeids).
					$sql_where.
				$sql_order;

		$db_res = DBselect($sql, $sql_limit);
		while($trigger = DBfetch($db_res)){
			if($options['count'])
				$result = $trigger;
			else{
				$triggerids[$trigger['triggerid']] = $trigger['triggerid'];

				if(is_null($options['extendoutput'])){
					$result[$trigger['triggerid']] = array('triggerid' => $trigger['triggerid']);
				}
				else{
					if(!isset($result[$trigger['triggerid']])) $result[$trigger['triggerid']]= array();

					if($options['select_hosts'] && !isset($result[$trigger['triggerid']]['hosts'])){
						$result[$trigger['triggerid']]['hosts'] = array();
					}
					if($options['select_items'] && !isset($result[$trigger['triggerid']]['items'])){
						$result[$trigger['triggerid']]['items'] = array();
					}
					if($options['select_dependencies'] && !isset($result[$trigger['triggerid']]['dependencies'])){
						$result[$trigger['triggerid']]['dependencies'] = array();
					}

// groups
					if(isset($trigger['groupid'])){
						if(!isset($result[$trigger['triggerid']]['groups'])) $result[$trigger['triggerid']]['groups'] = array();

						$result[$trigger['triggerid']]['groups'][$trigger['groupid']] = array('groupid' => $trigger['groupid']);
						unset($trigger['groupid']);
					}

// hostids
					if(isset($trigger['hostid'])){
						if(!isset($result[$trigger['triggerid']]['hosts'])) $result[$trigger['triggerid']]['hosts'] = array();

						$result[$trigger['triggerid']]['hosts'][$trigger['hostid']] = array('hostid' => $trigger['hostid']);
						unset($trigger['hostid']);
					}
// itemids
					if(isset($trigger['itemid'])){
						if(!isset($result[$trigger['triggerid']]['items']))
							$result[$trigger['triggerid']]['items'] = array();

						$result[$trigger['triggerid']]['items'][$trigger['itemid']] = array('itemid' => $trigger['itemid']);
						unset($trigger['itemid']);
					}

					$result[$trigger['triggerid']] += $trigger;
				}
			}
		}

		if(is_null($options['extendoutput']) || !is_null($options['count'])){
			if(is_null($options['preservekeys'])) $result = zbx_cleanHashes($result);
			return $result;
		}

// Adding Objects
// Adding trigger dependencies
		if($options['select_dependencies']){
			$deps = array();
			$depids = array();

			$sql = 'SELECT triggerid_up, triggerid_down FROM trigger_depends WHERE '.DBcondition('triggerid_down', $triggerids);
			$db_deps = DBselect($sql);
			while($db_dep = DBfetch($db_deps)){
				if(!isset($deps[$db_dep['triggerid_down']])) $deps[$db_dep['triggerid_down']] = array();
				$deps[$db_dep['triggerid_down']][$db_dep['triggerid_up']] = $db_dep['triggerid_up'];
				$depids[] = $db_dep['triggerid_up'];
			}

			$obj_params = array('triggerids' => $depids, 'extendoutput' => 1, 'expand_data' => 1, 'preservekeys' => 1);
			$allowed = self::get($obj_params); //allowed triggerids

			foreach($deps as $triggerid => $deptriggers){
				foreach($deptriggers as $num => $deptriggerid){
					if(isset($allowed[$deptriggerid])){
						$result[$triggerid]['dependencies'][$deptriggerid] = $allowed[$deptriggerid];
					}
				}
			}
		}

// Adding hosts
		if($options['select_hosts']){
			$obj_params = array(
				'nodeids' => $nodeids,
				'templated_hosts' => 1,
				'extendoutput' => 1,
				'triggerids' => $triggerids,
				'nopermissions' => 1,
				'preservekeys' => 1
			);
			$hosts = CHost::get($obj_params);
			foreach($hosts as $hostid => $host){
				foreach($host['triggers'] as $num => $trigger){
					$result[$trigger['triggerid']]['hosts'][$hostid] = $host;
				}
			}
		}

// Adding Items
		if($options['select_items']){
			$obj_params = array(
				'nodeids' => $nodeids,
				'extendoutput' => 1,
				'triggerids' => $triggerids,
				'nopermissions' => 1,
				'preservekeys' => 1
			);
			$items = CItem::get($obj_params);
			foreach($items as $itemid => $item){
				foreach($item['triggers'] as $num => $trigger){
					$result[$trigger['triggerid']]['items'][$itemid] = $item;
				}
			}
		}

// removing keys (hash -> array)
		if(is_null($options['preservekeys'])){
			$result = zbx_cleanHashes($result);
		}

	return $result;
	}

/**
 * Get triggerid by host.host and trigger.expression
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param _array $triggers multidimensional array with trigger objects
 * @param array $triggers[0,...]['expression']
 * @param array $triggers[0,...]['host']
 * @param array $triggers[0,...]['hostid'] OPTIONAL
 * @param array $triggers[0,...]['description'] OPTIONAL
 */
	public static function getObjects($trigger){
		$result = array();
		$triggerids = array();

		$sql_where = '';
		$sql_from = '';

		if(isset($trigger['hostid']) || isset($trigger['host'])){
			$sql_from .= ', functions f, items i, hosts h ';

			$sql_where .= ' f.itemid=i.itemid '.
				' AND f.triggerid=t.triggerid'.
				' AND i.hostid=h.hostid';

			if(isset($trigger['hostid']))
				$sql_where .= ' AND h.hostid='.$trigger['hostid'];

			if(isset($trigger['host']))
				$sql_where .= ' AND h.host='.zbx_dbstr($trigger['host']);
		}

		if(isset($trigger['description'])) {
			$sql_where .= ' AND t.description='.zbx_dbstr($trigger['description']);
		}

		$sql = 'SELECT DISTINCT t.triggerid, t.expression '.
				' FROM triggers t'.$sql_from.
				' WHERE '.$sql_where.
					' AND '.DBin_node('t.triggerid', false);
		if($db_triggers = DBselect($sql)){
//			$result = true;

			while($tmp_trigger = DBfetch($db_triggers)) {
				$tmp_exp = explode_exp($tmp_trigger['expression'], false);
				if(strcmp($tmp_exp, $trigger['expression']) == 0) {
					$triggerids[] = $tmp_trigger['triggerid'];
					break;
				}
			}
		}

		if(!empty($triggerids))
			$result = self::get(array('triggerids'=>$triggerids, 'extendoutput'=>1));

	return $result;
	}

/**
 * Add triggers
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param _array $triggers multidimensional array with triggers data
 * @param array $triggers[0,...]['expression']
 * @param array $triggers[0,...]['description']
 * @param array $triggers[0,...]['type'] OPTIONAL
 * @param array $triggers[0,...]['priority'] OPTIONAL
 * @param array $triggers[0,...]['status'] OPTIONAL
 * @param array $triggers[0,...]['comments'] OPTIONAL
 * @param array $triggers[0,...]['url'] OPTIONAL
 * @param array $triggers[0,...]['templateid'] OPTIONAL
 * @return boolean
 */
	public static function add($triggers){
		$triggers = zbx_toArray($triggers);
		$triggerids = array();

		$result = true;

		self::BeginTransaction(__METHOD__);
		foreach($triggers as $num => $trigger){
			$trigger_db_fields = array(
				'expression'	=> null,
				'description'	=> null,
				'type'		=> 0,
				'priority'	=> 1,
				'status'	=> TRIGGER_STATUS_DISABLED,
				'comments'	=> '',
				'url'		=> '',
				'templateid'=> 0
			);

			if(!check_db_fields($trigger_db_fields, $trigger)){
				self::setError(__METHOD__, ZBX_API_ERROR_PARAMETERS, 'Wrong field for trigger');
				$result = false;
				break;
			}

			$result = add_trigger(
				$trigger['expression'],
				$trigger['description'],
				$trigger['type'],
				$trigger['priority'],
				$trigger['status'],
				$trigger['comments'],
				$trigger['url'],
				array(),
				$trigger['templateid']
			);

			if(!$result) break;

			$triggerids[] = $result;
		}

		$result = self::EndTransaction($result, __METHOD__);
		if($result){
			$new_triggers = self::get(array('triggerids'=>$triggerids, 'extendoutput'=>1, 'nopermissions'=>1));
			return $new_triggers;
		}
		else{
			self::setError(__METHOD__);
			return false;
		}
	}

/**
 * Update triggers
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param _array $triggers multidimensional array with triggers data
 * @param array $triggers[0,...]['expression']
 * @param array $triggers[0,...]['description'] OPTIONAL
 * @param array $triggers[0,...]['type'] OPTIONAL
 * @param array $triggers[0,...]['priority'] OPTIONAL
 * @param array $triggers[0,...]['status'] OPTIONAL
 * @param array $triggers[0,...]['comments'] OPTIONAL
 * @param array $triggers[0,...]['url'] OPTIONAL
 * @param array $triggers[0,...]['templateid'] OPTIONAL
 * @return boolean
 */
	public static function update($triggers){
		$triggers = zbx_toArray($triggers);
		$triggerids = array();

		$upd_triggers = self::get(array('triggerids'=>zbx_objectValues($triggers, 'triggerid'),
											'editable'=>1,
											'extendoutput'=>1,
											'preservekeys'=>1));
		foreach($triggers as $gnum => $trigger){
			if(!isset($upd_triggers[$trigger['triggerid']])){
				self::setError(__METHOD__, ZBX_API_ERROR_PERMISSIONS, S_NO_PERMISSION);
				return false;
			}
			$triggerids[] = $trigger['triggerid'];
		}

		$result = true;

		self::BeginTransaction(__METHOD__);
		foreach($triggers as $tnum => $trigger){
			$trigger_db_fields = $upd_triggers[$trigger['triggerid']];

			if(!check_db_fields($trigger_db_fields, $trigger)){
				self::setError(__METHOD__, ZBX_API_ERROR_PERMISSIONS, 'Incorrect arguments pasted to function [CTrigger::update]');
				$result = false;
				break;
			}

			//$trigger['expression'] = explode_exp($trigger['expression'], false);
			$result = update_trigger($trigger['triggerid'],
									$trigger['expression'],
									$trigger['description'],
									$trigger['type'],
									$trigger['priority'],
									$trigger['status'],
									$trigger['comments'],
									$trigger['url'],
									array(),
									$trigger['templateid']);
			if(!$result) break;
		}
		$result = self::EndTransaction($result, __METHOD__);

		if($result){
			$upd_triggers = self::get(array('triggerids'=>$triggerids, 'extendoutput'=>1, 'nopermissions'=>1));
			return $upd_triggers;
		}
		else{
			self::$error[] = array('error' => ZBX_API_ERROR_INTERNAL, 'data' => 'Internal zabbix error');
			return false;
		}
	}

/**
 * Delete triggers
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param _array $triggers multidimensional array with trigger objects
 * @param array $triggers[0,...]['triggerid']
 * @return deleted triggers
 */
	public static function delete($triggers){
		$triggers = zbx_toArray($triggers);
		$triggerids = array();

		$del_triggers = self::get(array('triggerids'=>zbx_objectValues($triggers, 'triggerid'),
											'editable'=>1,
											'extendoutput'=>1,
											'preservekeys'=>1));
		foreach($triggers as $gnum => $trigger){
			if(!isset($del_triggers[$trigger['triggerid']])){
				self::setError(__METHOD__, ZBX_API_ERROR_PERMISSIONS, S_NO_PERMISSION);
				return false;
			}

			$triggerids[] = $trigger['triggerid'];
			//add_audit(AUDIT_ACTION_DELETE, AUDIT_RESOURCE_TRIGGER, 'Trigger ['.$trigger['description'].']');
		}

		self::BeginTransaction(__METHOD__);
		if(!empty($triggerids)){
			$result = delete_trigger($triggerids);
		}
		else{
			self::setError(__METHOD__, ZBX_API_ERROR_PARAMETERS, 'Empty input parameter [ triggerids ]');
			$result = false;
		}

		$result = self::EndTransaction($result, __METHOD__);

		if($result){
			return zbx_cleanHashes($del_triggers);
		}
		else{
			self::setError(__METHOD__);
			return false;
		}
	}

/**
 * Add dependency for trigger
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param _array $triggers_data
 * @param array $triggers_data['triggerid]
 * @param array $triggers_data['depends_on_triggerid']
 * @return boolean
 */
	public static function addDependencies($triggers_data){
		$triggers_data = zbx_toArray($triggers_data);

		$result = true;

		self::BeginTransaction(__METHOD__);

		foreach($triggers_data as $num => $dep){
			$result &= (bool) insert_dependency($dep['triggerid'], $dep['depends_on_triggerid']);
		}

		$result = self::EndTransaction($result, __METHOD__);

		if($result)
			return true;
		else{
			self::$error[] = array('error' => ZBX_API_ERROR_INTERNAL, 'data' => 'Internal zabbix error');
			return false;
		}
	}

/**
 * Delete trigger dependencis
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param _array $triggers multidimensional array with trigger objects
 * @param array $triggers[0,...]['triggerid']
 * @return boolean
 */
	public static function deleteDependencies($triggers){
		$triggers = zbx_toArray($triggers);

		$triggerids = array();
		foreach($triggers as $num => $trigger){
			$triggerids[] = $trigger['triggerid'];
		}

		self::BeginTransaction(__METHOD__);

		$result = delete_dependencies_by_triggerid($triggerids);

		$result = self::EndTransaction($result, __METHOD__);
		if($result)
			return true;
		else{
			self::$error[] = array('error' => ZBX_API_ERROR_INTERNAL, 'data' => 'Internal zabbix error');
			return false;
		}
	}
}

?>
