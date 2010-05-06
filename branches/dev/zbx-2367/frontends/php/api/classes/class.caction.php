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
 * File containing CAction class for API.
 * @package API
 */
/**
 * Class containing methods for operations with Actions
 *
 */
class CAction extends CZBXAPI{
/**
 * Get Actions data
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
 * @param array $options['actionids']
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

		$sort_columns = array('actionid','name'); // allowed columns for sorting
		$subselects_allowed_outputs = array(API_OUTPUT_REFER, API_OUTPUT_EXTEND); // allowed output options for [ select_* ] params


		$sql_parts = array(
			'select' => array('actions' => 'a.actionid'),
			'from' => array('actions' => 'actions a'),
			'where' => array(),
			'order' => array(),
			'limit' => null,
			);

		$def_options = array(
			'nodeids'				=> null,
			'groupids'				=> null,
			'hostids'				=> null,
			'actionids'				=> null,
			'triggerids'			=> null,
			'mediatypeids'			=> null,
			'userids'				=> null,
			'nopermissions'			=> null,

// filter
			'eventsource'			=> null,
			'evaltype'				=> null,
			'status'				=> null,
			'esc_period'			=> null,
			'recovery_msg'			=> null,
			'pattern'				=> '',

// OutPut
			'extendoutput'			=> null,
			'output'				=> API_OUTPUT_REFER,
			'select_conditions'		=> null,
			'select_operations'		=> null,
			'count'					=> null,
			'preservekeys'			=> null,

			'sortfield'				=> '',
			'sortorder'				=> '',
			'limit'					=> null
		);

		$options = zbx_array_merge($def_options, $options);


		if(!is_null($options['extendoutput'])){
			$options['output'] = API_OUTPUT_EXTEND;

			if(!is_null($options['select_conditions'])){
				$options['select_conditions'] = API_OUTPUT_EXTEND;
			}
			if(!is_null($options['select_operations'])){
				$options['select_operations'] = API_OUTPUT_EXTEND;
			}
		}


// editable + PERMISSION CHECK
		if((USER_TYPE_SUPER_ADMIN == $user_type) || !is_null($options['nopermissions'])){
		}
		else{
			$permission = $options['editable']?PERM_READ_WRITE:PERM_READ_ONLY;

			$sql_parts['from']['conditions'] = 'conditions c';
			$sql_parts['where']['ac'] = 'a.actionid=c.actionid';

// condition hostgroup
			$sql_parts['where'][] =
				' NOT EXISTS('.
					' SELECT cc.conditionid'.
					' FROM conditions cc'.
					' WHERE cc.conditiontype='.CONDITION_TYPE_HOST_GROUP.
						' AND cc.actionid=c.actionid'.
						' AND ('.
							' NOT EXISTS('.
								' SELECT rr.id'.
								' FROM rights rr, users_groups ug'.
								' WHERE rr.id='.zbx_dbcast_2bigint('cc.value').
									' AND rr.groupid=ug.usrgrpid'.
									' AND ug.userid='.$userid.
									' AND rr.permission>='.$permission.
							' )'.
							' OR EXISTS('.
								' SELECT rr.id'.
								' FROM rights rr, users_groups ugg'.
								' WHERE rr.id='.zbx_dbcast_2bigint('cc.value').
									' AND rr.groupid=ugg.usrgrpid'.
									' AND ugg.userid='.$userid.
									' AND rr.permission<'.$permission.
							' )'.
						' )'.
				' )';

// condition host or template
			$sql_parts['where'][] =
				' NOT EXISTS('.
					' SELECT cc.conditionid'.
					' FROM conditions cc'.
					' WHERE (cc.conditiontype='.CONDITION_TYPE_HOST.' OR cc.conditiontype='.CONDITION_TYPE_HOST_TEMPLATE.')'.
						' AND cc.actionid=c.actionid'.
						' AND ('.
							' NOT EXISTS('.
								' SELECT hgg.hostid'.
								' FROM hosts_groups hgg, rights r,users_groups ug'.
								' WHERE hgg.hostid='.zbx_dbcast_2bigint('cc.value').
									' AND r.id=hgg.groupid'.
									' AND ug.userid='.$userid.
									' AND r.permission>='.$permission.
									' AND r.groupid=ug.usrgrpid)'.
							' OR EXISTS('.
								' SELECT hgg.hostid'.
									' FROM hosts_groups hgg, rights rr, users_groups gg'.
									' WHERE hgg.hostid='.zbx_dbcast_2bigint('cc.value').
										' AND rr.id=hgg.groupid'.
										' AND rr.groupid=gg.usrgrpid'.
										' AND gg.userid='.$userid.
										' AND rr.permission<'.$permission.')'.
							' )'.
				' )';

// condition trigger
			$sql_parts['where'][] =
				' NOT EXISTS('.
					' SELECT cc.conditionid '.
					' FROM conditions cc '.
					' WHERE cc.conditiontype='.CONDITION_TYPE_TRIGGER.
						' AND cc.actionid=c.actionid'.
						' AND ('.
							' NOT EXISTS('.
								' SELECT f.triggerid'.
								' FROM functions f, items i,hosts_groups hg, rights r, users_groups ug'.
								' WHERE ug.userid='.$userid.
									' AND r.groupid=ug.usrgrpid'.
									' AND r.permission>='.$permission.
									' AND hg.groupid=r.id'.
									' AND i.hostid=hg.hostid'.
									' AND f.itemid=i.itemid'.
									' AND f.triggerid='.zbx_dbcast_2bigint('cc.value').')'.
							' OR EXISTS('.
								' SELECT ff.functionid'.
								' FROM functions ff, items ii'.
								' WHERE ff.triggerid='.zbx_dbcast_2bigint('cc.value').
									' AND ii.itemid=ff.itemid'.
									' AND EXISTS('.
										' SELECT hgg.groupid'.
										' FROM hosts_groups hgg, rights rr, users_groups ugg'.
										' WHERE hgg.hostid=ii.hostid'.
											' AND rr.id=hgg.groupid'.
											' AND rr.groupid=ugg.usrgrpid'.
											' AND ugg.userid='.$userid.
											' AND rr.permission<'.$permission.'))'.
					  ' )'.
				' )';
// condition users
			$sql_parts['where'][] =
				' NOT EXISTS('.
					' SELECT o.operationid '.
					' FROM operations o '.
					' WHERE o.operationtype='.OPERATION_TYPE_MESSAGE.
						' AND o.actionid=a.actionid'.
						' AND (('.
								' o.object='.OPERATION_OBJECT_USER.
								' AND o.objectid NOT IN ('.
									' SELECT DISTINCT ug.userid'.
									' FROM users_groups ug'.
									' WHERE ug.usrgrpid IN ('.
										' SELECT uug.usrgrpid'.
										' FROM users_groups uug'.
										' WHERE uug.userid='.$USER_DETAILS['userid'].
										' )'.
									' )'.
							' ) OR ('. 
								' o.object='.OPERATION_OBJECT_GROUP.
								' AND o.objectid NOT IN ('.
									' SELECT ug.usrgrpid'.
									' FROM users_groups ug'.
									' WHERE ug.userid='.$USER_DETAILS['userid'].
									' )'.
								' )'.
						' )'.
				' )';
						
				
		}

// nodeids
		$nodeids = !is_null($options['nodeids']) ? $options['nodeids'] : get_current_nodeid(false);

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
			$sql_parts['where']['e'] = 'e.object='.EVENT_OBJECT_TRIGGER;
			$sql_parts['where']['ef'] = 'e.objectid=f.triggerid';
			$sql_parts['where']['fi'] = 'f.itemid=i.itemid';
			$sql_parts['where']['hg'] = DBcondition('hg.groupid', $options['groupids']);
		}

// hostids
		if(!is_null($options['hostids'])){
			zbx_value2array($options['hostids']);

			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['hostid'] = 'i.hostid';
			}

			$sql_parts['from']['functions'] = 'functions f';
			$sql_parts['from']['items'] = 'items i';

			$sql_parts['where']['i'] = DBcondition('i.hostid', $options['hostids']);
			$sql_parts['where']['e'] = 'e.object='.EVENT_OBJECT_TRIGGER;
			$sql_parts['where']['ef'] = 'e.objectid=f.triggerid';
			$sql_parts['where']['fi'] = 'f.itemid=i.itemid';
		}

// triggerids
		if(!is_null($options['triggerids'])){
			zbx_value2array($options['triggerids']);

			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['actionid'] = 'a.actionid';
			}

			$sql_parts['where']['ae'] = 'a.eventid=e.eventid';
			$sql_parts['where']['e'] = 'e.object='.EVENT_OBJECT_TRIGGER;
			$sql_parts['where'][] = DBcondition('e.objectid', $options['triggerids']);
		}

// actionids
		if(!is_null($options['actionids'])){
			zbx_value2array($options['actionids']);

			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['actionid'] = 'a.actionid';
			}

			$sql_parts['where'][] = DBcondition('a.actionid', $options['actionids']);
		}

// userids
		if(!is_null($options['userids'])){
			zbx_value2array($options['userids']);

			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['userid'] = 'a.userid';
			}

			$sql_parts['where'][] = DBcondition('a.userid', $options['userids']);
		}

// mediatypeids
		if(!is_null($options['mediatypeids'])){
			zbx_value2array($options['mediatypeids']);

			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['mediatypeid'] = 'a.mediatypeid';
			}

			$sql_parts['where'][] = DBcondition('a.mediatypeid', $options['mediatypeids']);
		}

// eventsource
		if(!is_null($options['eventsource'])){
			$sql_parts['where'][] = 'a.eventsource='.$options['eventsource'];
		}

// evaltype
		if(!is_null($options['evaltype'])){
			$sql_parts['where'][] = 'a.evaltype='.$options['evaltype'];
		}

// status
		if(!is_null($options['status'])){
			$sql_parts['where'][] = 'a.status='.$options['status'];
		}

// esc_period
		if(!is_null($options['esc_period'])){
			$sql_parts['where'][] = 'a.esc_period>'.$options['esc_period'];
		}

// recovery_msg
		if(!is_null($options['recovery_msg'])){
			$sql_parts['where'][] = 'a.recovery_msg<'.$options['recovery_msg'];
		}

// extendoutput
		if($options['output'] == API_OUTPUT_EXTEND){
			$sql_parts['select']['actions'] = 'a.*';
		}

// count
		if(!is_null($options['count'])){
			$options['sortfield'] = '';

			$sql_parts['select'] = array('COUNT(DISTINCT a.actionid) as rowscount');
		}

// pattern
		if(!zbx_empty($options['pattern'])){
			$sql_parts['where'][] = ' UPPER(a.name) LIKE '.zbx_dbstr('%'.zbx_strtoupper($options['pattern']).'%');
		}


// order
// restrict not allowed columns for sorting
		$options['sortfield'] = str_in_array($options['sortfield'], $sort_columns) ? $options['sortfield'] : '';
		if(!zbx_empty($options['sortfield'])){
			$sortorder = ($options['sortorder'] == ZBX_SORT_DOWN)?ZBX_SORT_DOWN:ZBX_SORT_UP;

			$sql_parts['order'][] = 'a.'.$options['sortfield'].' '.$sortorder;

			if(!str_in_array('a.'.$options['sortfield'], $sql_parts['select']) && !str_in_array('a.*', $sql_parts['select'])){
				$sql_parts['select'][] = 'a.'.$options['sortfield'];
			}
		}

// limit
		if(zbx_ctype_digit($options['limit']) && $options['limit']){
			$sql_parts['limit'] = $options['limit'];
		}
//---------------

		$actionids = array();
		$userids = array();
		$mediatypeids = array();

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
				' WHERE '.DBin_node('a.actionid', $nodeids).
					$sql_where.
				$sql_order;
//SDI($sql);
		$db_res = DBselect($sql, $sql_limit);
		while($action = DBfetch($db_res)){

			if($options['count']){
				$result = $action;
			}
			else{
				$actionids[$action['actionid']] = $action['actionid'];

				if($options['output'] == API_OUTPUT_SHORTEN){
					$result[$action['actionid']] = array('actionid' => $action['actionid']);
				}
				else{
					if(!isset($result[$action['actionid']])) $result[$action['actionid']]= array();

					if(!is_null($options['select_conditions']) && !isset($result[$action['actionid']]['conditions'])){
						$result[$action['actionid']]['conditions'] = array();
					}

					if(!is_null($options['select_operations']) && !isset($result[$action['actionid']]['operations'])){
						$result[$action['actionid']]['operations'] = array();
					}

					$result[$action['actionid']] += $action;
				}
			}
		}

COpt::memoryPick();
		if(($options['output'] != API_OUTPUT_EXTEND) || !is_null($options['count'])){
			if(is_null($options['preservekeys'])) $result = zbx_cleanHashes($result);
			return $result;
		}

// Adding Objects
// Adding Conditions
		if(!is_null($options['select_conditions']) && str_in_array($options['select_conditions'], $subselects_allowed_outputs)){
			$sql = 'SELECT c.* FROM conditions c WHERE '.DBcondition('c.actionid', $actionids);
			$res = DBselect($sql);
			while($condition = DBfetch($res)){
				$result[$condition['actionid']]['conditions'][] = $condition;
			}
		}

// Adding Operations
		if(!is_null($options['select_operations']) && str_in_array($options['select_operations'], $subselects_allowed_outputs)){
			$operations = array();
			$operationids = array();
			$sql = 'SELECT o.* '.
					' FROM operations o '.
					' WHERE '.DBcondition('o.actionid', $actionids);
			$res = DBselect($sql);
			while($operation = DBfetch($res)){
				$operation['opconditions'] = array();

				$operations[$operation['operationid']] = $operation;
				$operationids[$operation['operationid']] = $operation['operationid'];
			}

			$sql = 'SELECT op.* FROM opconditions op WHERE '.DBcondition('op.operationid', $operationids);
			$res = DBselect($sql);
			while($opcondition = DBfetch($res)){
				$operations[$opcondition['operationid']]['opconditions'][$opcondition['opconditionid']] = $opcondition;
			}

			foreach($operations as $operationd => $operation){
				$result[$operation['actionid']]['operations'][] = $operation;
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
 * Add actions
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param _array $actions multidimensional array with actions data
 * @param array $actions[0,...]['expression']
 * @param array $actions[0,...]['description']
 * @param array $actions[0,...]['type'] OPTIONAL
 * @param array $actions[0,...]['priority'] OPTIONAL
 * @param array $actions[0,...]['status'] OPTIONAL
 * @param array $actions[0,...]['comments'] OPTIONAL
 * @param array $actions[0,...]['url'] OPTIONAL
 * @return boolean
 */
	public static function create($actions){
		$actions = zbx_toArray($actions);
		$actionids = array();

		$result = false;

		try{

			foreach($actions as $anum => $action){
				if(!isset($action['operations']) || !is_array($action['operations']) || count($action['operations']) == 0){
					throw new APIException(ZBX_API_ERROR_PARAMETERS, S_NO_OPERATIONS_DEFINED);
				}

				if(!isset($action['conditions'])){
					$action['conditions'] = array();
					continue;
				}

				if(!check_permission_for_action_conditions($action['conditions'])){
					throw new APIException(ZBX_API_ERROR_PERMISSIONS, 'You do not have enough rights for operation');
				}

				foreach($action['conditions'] as $condition)
					if(!validate_condition($condition['type'], $condition['value'])){
						throw new APIException(ZBX_API_ERROR_PARAMETERS, 'Action condition validation failed');
					}
			}

			$transaction = self::BeginTransaction(__METHOD__);
			foreach($actions as $anum => $action){
				$action_db_fields = array(
					'name'				=> null,
					'eventsource'		=> null,
					'evaltype'			=> null,
					'status'			=> 0,
					'esc_period'		=> 0,
					'def_shortdata'		=> '',
					'def_longdata'		=> '',
					'recovery_msg'		=> 0,
					'r_shortdata'		=> '',
					'r_longdata'		=> '',
				);

				if(!check_db_fields($action_db_fields, $action)){
					throw new APIException(ZBX_API_ERROR_PARAMETERS, 'Incorrect parameters used for Action [ '.$action['name'].' ]');
				}

				$actionid = get_dbid('actions', 'actionid');
				$values = array(
					'actionid' => $actionid,
					'name' => zbx_dbstr($action['name']),
					'eventsource' => $action['eventsource'],
					'esc_period' => $action['esc_period'],
					'def_shortdata' => zbx_dbstr($action['def_shortdata']),
					'def_longdata' => zbx_dbstr($action['def_longdata']),
					'recovery_msg' => zbx_dbstr($action['recovery_msg']),
					'r_shortdata' => zbx_dbstr($action['r_shortdata']),
					'r_longdata' => zbx_dbstr($action['r_longdata']),
					'evaltype' => $action['evaltype'],
					'status' => $action['status']
				);


				$sql = 'INSERT INTO actions ('.implode(',', array_keys($values)).')'.
						' VALUES ('.implode(',', array_values($values)).')';
				$result = DBexecute($sql);

				if(!$result) throw new APIException(ZBX_API_ERROR_INTERNAL, 'Failed to add Action [ '.$action['name'].' ]');
				$actionids[] = $actionid;

				foreach($action['operations'] as $onum => &$operation) $operation['actionid'] = $actionid;
				unset($operation);

				$result = self::addOperations($action['operations']);
				if(!$result) 
					throw new APIException(ZBX_API_ERROR_INTERNAL, 'Failed to add Action operation [ '.$action['name'].' ]');

				foreach($action['conditions'] as $cnum => &$condition) $condition['actionid'] = $actionid;
				unset($condition);

				if(!empty($action['conditions'])){
					$result = self::addConditions($action['conditions']);
					if(!$result)
						throw new APIException(ZBX_API_ERROR_INTERNAL, 'Failed to add Action condition [ '.$action['name'].' ]');
				}
			}

			$result = self::EndTransaction($result, __METHOD__);
			return array('actionids'=>$actionids);
		}
		catch(APIException $e){
			if(isset($transaction)) self::EndTransaction(false, __METHOD__);
//SDII($e);
			$errors = $e->getErrors();
			$error = reset($errors);
			self::$error[] = array('error' => $e->getCode(), 'data' => $error);
			return false;
		}
	}

/**
 * Update actions
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param _array $actions multidimensional array with actions data
 * @param array $actions[0,...]['actionid']
 * @param array $actions[0,...]['expression']
 * @param array $actions[0,...]['description']
 * @param array $actions[0,...]['type'] OPTIONAL
 * @param array $actions[0,...]['priority'] OPTIONAL
 * @param array $actions[0,...]['status'] OPTIONAL
 * @param array $actions[0,...]['comments'] OPTIONAL
 * @param array $actions[0,...]['url'] OPTIONAL
 * @return boolean
 */
	public static function update($actions){
		$actions = zbx_toArray($actions);
		$actionids = array();

		$options = array(
			'actionids'=>zbx_objectValues($actions, 'actionid'),
			'editable'=>1,
			'output'=> API_OUTPUT_EXTEND,
			'preservekeys'=>1
		);
		$upd_actions = CAction::get($options);
		foreach($actions as $anum => $action){
			if(!isset($upd_actions[$action['actionid']])){
				self::setError(__METHOD__, ZBX_API_ERROR_PERMISSIONS, S_NO_PERMISSION);
				return false;
			}
			$actionids[] = $action['actionid'];
		}

		$result = true;

		self::BeginTransaction(__METHOD__);
		foreach($actions as $anum => $action){
			$action_db_fields = $upd_actions[$action['actionid']];

			if(!check_db_fields($action_db_fields, $action)){
				$result = false;
				break;
			}

			$result = update_action($action['actionid'], $action['name'], $action['eventsource'], $action['esc_period'],
				$action['def_shortdata'], $action['def_longdata'], $action['recovery_msg'], $action['r_shortdata'],
				$action['r_longdata'], $action['evaltype'], $action['status'], $action['conditions'], $action['operations']);

			if(!$result) break;
		}

		$result = self::EndTransaction($result, __METHOD__);

		if($result){
			return array('actionids'=>$actionids);
		}
		else{
			self::$error[] = array('error' => ZBX_API_ERROR_INTERNAL, 'data' => 'Internal zabbix error');
			return false;
		}
	}

/**
 * add conditions
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param _array $conditions multidimensional array with conditions data
 * @param array $conditions[0,...]['actionid']
 * @param array $conditions[0,...]['type']
 * @param array $conditions[0,...]['value']
 * @param array $conditions[0,...]['operator']
 * @return boolean
 */
	public static function addConditions($conditions){
		$conditions = zbx_toArray($conditions);
		$result = true;

		if(!check_permission_for_action_conditions($conditions)){
			self::$error[] = array('error' => ZBX_API_ERROR_PERMISSIONS, 'data' => 'You do not have enough rights for operation');
			return false;
		}

		foreach($conditions as $cnum => $condition){
			if(!validate_condition($condition['type'],$condition['value']) ){
				self::$error[] = array('error' => ZBX_API_ERROR_PARAMETERS, 'data' => 'Action condition validation failed');
				return false;
			}
		}

		self::BeginTransaction(__METHOD__);
		foreach($conditions as $cnum => $condition){

			$conditionid = get_dbid('conditions','conditionid');
			$values = array(
				'conditionid' => $conditionid,
				'actionid' => $condition['actionid'],
				'conditiontype' => $condition['conditiontype'],
				'operator' => $condition['operator'],
				'value' => zbx_dbstr($condition['value'])
			);

			$result = DBexecute('INSERT INTO conditions ('.implode(',', array_keys($values)).')'.
					' VALUES ('.implode(',', array_values($values)).')');

			if(!$result) break;
		}
		$result = self::EndTransaction($result, __METHOD__);

		if($result){
			return $conditions;
		}
		else{
			self::$error[] = array('error' => ZBX_API_ERROR_INTERNAL, 'data' => 'Internal zabbix error');
			return false;
		}
	}

/**
 * add operations
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param _array $operations multidimensional array with operations data
 * @param array $operations[0,...]['actionid']
 * @param array $operations[0,...]['operationtype']
 * @param array $operations[0,...]['object']
 * @param array $operations[0,...]['objectid']
 * @param array $operations[0,...]['shortdata']
 * @param array $operations[0,...]['longdata']
 * @param array $operations[0,...]['esc_period']
 * @param array $operations[0,...]['esc_step_from']
 * @param array $operations[0,...]['esc_step_to']
 * @param array $operations[0,...]['default_msg']
 * @param array $operations[0,...]['evaltype']
 * @param array $operations[0,...]['mediatypeid']
 * @param array $operations[0,...]['opconditions']
 * @param array $operations[0,...]['opconditions']['conditiontype']
 * @param array $operations[0,...]['opconditions']['operator']
 * @param array $operations[0,...]['opconditions']['value']
 * @return boolean
 */
	public static function addOperations($operations){
		$operations = zbx_toArray($operations);
		$result = true;

		foreach($operations as $onum => $operation){
			if(!validate_operation($operation)){
				self::$error[] = array('error' => ZBX_API_ERROR_PARAMETERS, 'data' => 'Action operation validation failed');
				return false;
			}
		}

		self::BeginTransaction(__METHOD__);
		foreach($operations as $onum => $operation){
			$operation_db_fields = array(
				'operationid' => get_dbid('operations','operationid'),
				'actionid' => null,
				'operationtype' => null,
				'object' => 0,
				'objectid' => 0,
				'shortdata' => '',
				'longdata' => '',
				'esc_period' => 0,
				'esc_step_from' => 0,
				'esc_step_to' => 0,
				'default_msg' => 0,
				'evaltype' => 0,
				'mediatypeid' => 0,
				'opconditions' => array()
			);

			if(!check_db_fields($operation_db_fields, $operation)){
				$result = false;
				break;
			}

			$operationid = get_dbid('operations','operationid');
			$values = array(
				'operationid' => $operationid,
				'actionid' => $operation['actionid'],
				'operationtype' => $operation['operationtype'],
				'object' => $operation['object'],
				'objectid' => $operation['objectid'],
				'shortdata' => zbx_dbstr($operation['shortdata']),
				'longdata' => zbx_dbstr($operation['longdata']),
				'esc_period' => $operation['esc_period'],
				'esc_step_from' => $operation['esc_step_from'],
				'esc_step_to' => $operation['esc_step_to'],
				'default_msg' => $operation['default_msg'],
				'evaltype' => $operation['evaltype']
			);

			$result = DBexecute('INSERT INTO operations ('.implode(',', array_keys($values)).')'.
					' VALUES ('.implode(',', array_values($values)).')');

			if(!$result) return $result;

			foreach($operation['opconditions'] as $num => $opcondition){
				$opconditionid = get_dbid("opconditions","opconditionid");

				$result &= (bool) DBexecute('INSERT INTO opconditions (opconditionid,operationid,conditiontype,operator,value)'.
					' values ('.$opconditionid.','.
						$operationid.','.
						$opcondition['conditiontype'].','.
						$opcondition['operator'].','.
						zbx_dbstr($opcondition['value']).
					')');
			}

			if($operation['mediatypeid'] > 0){
				$opmediatypeid = get_dbid('opmediatypes', 'opmediatypeid');

				$result &= (bool) DBexecute('INSERT INTO opmediatypes (opmediatypeid,operationid,mediatypeid)'.
					' VALUES ('.$opmediatypeid.','.$operationid.','.$operation['mediatypeid'].')');
			}

			if(!$result) break;
		}

		$result = self::EndTransaction($result, __METHOD__);

		if($result){
			return $operations;
		}
		else{
			self::$error[] = array('error' => ZBX_API_ERROR_INTERNAL, 'data' => 'Internal zabbix error');
			return false;
		}
	}

/**
 * Delete actions
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param _array $actionids
 * @param array $actionids['actionids']
 * @return boolean
 */
	public static function delete($actions){
		$actions = zbx_toArray($actions);
		$actionids = array();

		$options = array(
			'actionids'=>zbx_objectValues($actions, 'actionid'),
			'editable'=>1,
			'extendoutput'=>1,
			'preservekeys'=>1
		);
		$del_actions = Caction::get($options);
		foreach($actions as $anum => $action){
			if(!isset($del_actions[$action['actionid']])){
				self::setError(__METHOD__, ZBX_API_ERROR_PERMISSIONS, S_NO_PERMISSION);
				return false;
			}

			$actionids[] = $action['actionid'];
			//add_audit(AUDIT_ACTION_DELETE, AUDIT_RESOURCE_ACTION, 'Action ['.$action['name'].']');
		}

		self::BeginTransaction(__METHOD__);
		if(!empty($actionids)){
			$sql = 'DELETE FROM actions WHERE '.DBcondition('actionid', $actionids);
			$result = DBexecute($sql);
		}
		else{
			self::setError(__METHOD__, ZBX_API_ERROR_PARAMETERS, 'Empty input parameter [ actionids ]');
			$result = false;
		}

		$result = self::EndTransaction($result, __METHOD__);

		if($result){
			return array('actionids'=>$actionids);
		}
		else{
			self::setError(__METHOD__);
			return false;
		}
	}
}
?>
