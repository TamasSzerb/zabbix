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
 * File containing CEvent class for API.
 * @package API
 */
/**
 * Class containing methods for operations with events
 *
 */
class CEvent extends CZBXAPI{
/**
 * Get events data
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
 * @param array $options['eventids']
 * @param array $options['applicationids']
 * @param array $options['status']
 * @param array $options['templated_items']
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

		$sort_columns = array('eventid','clock'); // allowed columns for sorting


		$sql_parts = array(
			'select' => array('events' => 'e.eventid'),
			'from' => array('events e'),
			'where' => array(),
			'order' => array(),
			'limit' => null
		);

		$def_options = array(
			'nodeids'				=> null,
			'groupids'				=> null,
			'hostids'				=> null,
			'triggerids'			=> null,
			'eventids'				=> null,
			'editable'				=> null,
			'object'				=> null,
			'source'				=> null,
			'acknowledged'			=> null,
			'nopermissions'			=> null,

// filter
			'hide_unknown'			=> null,
			'acknowledged'			=> null,
			'value'					=> null,
			'time_from'				=> null,
			'time_till'				=> null,

// OutPut
			'extendoutput'			=> null,
			'select_hosts'			=> null,
			'select_items'			=> null,
			'select_triggers'		=> null,
			'count'					=> null,
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

		if(is_null($options['source']) && is_null($options['object'])){
			$options['object'] = EVENT_OBJECT_TRIGGER;
		}

		if((USER_TYPE_SUPER_ADMIN == $user_type) || $options['nopermissions']){
		}
		else if(($options['object'] == EVENT_OBJECT_TRIGGER) || ($options['source'] == EVENT_SOURCE_TRIGGER)){

			$triggers = CTrigger::get();
			$triggerids = zbx_objectValues($triggers, 'triggerid');

			if(!is_null($options['triggerids']))
				$options['triggerids'] = array_intersect($options['triggerids'], $triggerids);
			else
				$options['triggerids'] = $triggerids;

/*
			$permission = $options['editable']?PERM_READ_WRITE:PERM_READ_ONLY;

			$sql_parts['from']['f'] = 'functions f';
			$sql_parts['from']['i'] = 'items i';
			$sql_parts['from']['hg'] = 'hosts_groups hg';
			$sql_parts['from']['r'] = 'rights r';
			$sql_parts['from']['ug'] = 'users_groups ug';
			$sql_parts['where']['e'] = 'e.object='.EVENT_OBJECT_TRIGGER;
			$sql_parts['where']['fe'] = 'f.triggerid=e.objectid';
			$sql_parts['where']['fi'] = 'f.itemid=i.itemid';
			$sql_parts['where']['hgi'] = 'hg.hostid=i.hostid';
			$sql_parts['where'][] = 'r.id=hg.groupid ';
			$sql_parts['where'][] = 'r.groupid=ug.usrgrpid';
			$sql_parts['where'][] = 'ug.userid='.$userid;
			$sql_parts['where'][] = 'r.permission>='.$permission;
			$sql_parts['where'][] = 'NOT EXISTS( '.
											' SELECT ff.triggerid '.
											' FROM functions ff, items ii '.
											' WHERE ff.triggerid=e.objectid '.
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
		$nodeids = $options['nodeids'] ? $options['nodeids'] : get_current_nodeid(false);

// Permission hack

// groupids

		if(!is_null($options['groupids'])){
			zbx_value2array($options['groupids']);

			if(!is_null($options['extendoutput'])){
				$sql_parts['select']['groupid'] = 'hg.groupid';
			}

			$sql_parts['from']['f'] = 'functions f';
			$sql_parts['from']['i'] = 'items i';
			$sql_parts['from']['hg'] = 'hosts_groups hg';
			$sql_parts['where']['hg'] = DBcondition('hg.groupid', $options['groupids']);
			$sql_parts['where']['hgi'] = 'hg.hostid=i.hostid';
			$sql_parts['where']['fe'] = 'f.triggerid=e.objectid';
			$sql_parts['where']['fi'] = 'f.itemid=i.itemid';
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
			$sql_parts['where']['ft'] = 'f.triggerid=e.objectid';
			$sql_parts['where']['fi'] = 'f.itemid=i.itemid';
		}

// eventids
		if(!is_null($options['eventids'])){
			zbx_value2array($options['eventids']);

			$sql_parts['where'][] = DBcondition('e.eventid', $options['eventids']);
		}

// triggerids
		if(!is_null($options['triggerids']) && ($options['object'] == EVENT_OBJECT_TRIGGER)){
			zbx_value2array($options['triggerids']);

			$sql_parts['where']['e'] = '(e.object-0)='.EVENT_OBJECT_TRIGGER;
			$sql_parts['where'][] = DBcondition('e.objectid', $options['triggerids']);
		}

// source
		if(!is_null($options['source'])){
			$sql_parts['where'][] = 'e.source='.$options['source'];
		}

// object
		if(!is_null($options['object'])){
			$sql_parts['where'][] = 'e.object='.$options['object'];
		}

// acknowledged
		if(!is_null($options['acknowledged'])){
			$sql_parts['where'][] = 'e.acknowledged='.($options['acknowledged']?1:0);
		}

// hide_unknown
		if(!is_null($options['hide_unknown'])){
			$sql_parts['where'][] = 'e.value<>'.TRIGGER_VALUE_UNKNOWN;
		}

// time_from
		if(!is_null($options['time_from'])){
			$sql_parts['where'][] = 'e.clock>='.$options['time_from'];
		}

// time_till
		if(!is_null($options['time_till'])){
			$sql_parts['where'][] = 'e.clock<='.$options['time_till'];
		}

// value
		if(!is_null($options['value'])){
			zbx_value2array($options['value']);

			$sql_parts['where'][] = DBcondition('e.value', $options['value']);
		}

// extendoutput
		if(!is_null($options['extendoutput'])){
			$sql_parts['select']['events'] = 'e.*';
		}

// count
		if(!is_null($options['count'])){
			$options['sortfield'] = '';

			$sql_parts['select']['events'] = 'COUNT(DISTINCT e.eventid) as rowscount';
		}

// order
// restrict not allowed columns for sorting
		$options['sortfield'] = str_in_array($options['sortfield'], $sort_columns) ? $options['sortfield'] : '';
		if(!zbx_empty($options['sortfield'])){
			$sortorder = ($options['sortorder'] == ZBX_SORT_DOWN)?ZBX_SORT_DOWN:ZBX_SORT_UP;

			$sql_parts['order'][] = 'e.'.$options['sortfield'].' '.$sortorder;

			if(!str_in_array('e.'.$options['sortfield'], $sql_parts['select']) && !str_in_array('e.*', $sql_parts['select'])){
				$sql_parts['select'][] = 'e.'.$options['sortfield'];
			}
		}

// limit
		if(zbx_ctype_digit($options['limit']) && $options['limit']){
			$sql_parts['limit'] = $options['limit'];
		}
//---------------

		$eventids = array();
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
				' WHERE '.DBin_node('e.eventid', $nodeids).
					$sql_where.
				$sql_order;
		$db_res = DBselect($sql, $sql_limit);
		while($event = DBfetch($db_res)){
			if($options['count'])
				$result = $event;
			else{
				$eventids[$event['eventid']] = $event['eventid'];

				if($event['object'] == EVENT_OBJECT_TRIGGER)
					$triggerids[$event['objectid']] = $event['objectid'];

				if(is_null($options['extendoutput'])){
					$result[$event['eventid']] = array('eventid' => $event['eventid']);
				}
				else{
					if(!isset($result[$event['eventid']])) $result[$event['eventid']]= array();

					if($options['select_hosts'] && !isset($result[$event['eventid']]['hostids'])){
						$result[$event['eventid']]['hostids'] = array();
						$result[$event['eventid']]['hosts'] = array();
					}

					if($options['select_triggers'] && !isset($result[$event['eventid']]['triggerids'])){
						$result[$event['eventid']]['triggerids'] = array();
						$result[$event['eventid']]['triggers'] = array();
					}

// hostids
					if(isset($event['hostid'])){
						if(!isset($result[$event['eventid']]['hostids'])) $result[$event['eventid']]['hostids'] = array();

						$result[$event['eventid']]['hostids'][$event['hostid']] = $event['hostid'];
						unset($event['hostid']);
					}

// triggerids
					if(isset($event['triggerid'])){
						if(!isset($result[$event['eventid']]['triggerids'])) $result[$event['eventid']]['triggerids'] = array();

						$result[$event['eventid']]['triggerids'][$event['triggerid']] = $event['triggerid'];
						unset($event['triggerid']);
					}

// itemids
					if(isset($event['itemid'])){
						if(!isset($result[$event['eventid']]['itemids'])) $result[$event['eventid']]['itemids'] = array();

						$result[$event['eventid']]['itemids'][$event['itemid']] = $event['itemid'];
						unset($event['itemid']);
					}

					$result[$event['eventid']] += $event;
				}
			}
		}

		if(is_null($options['extendoutput']) || !is_null($options['count'])){
			if(is_null($options['preservekeys'])) $result = zbx_cleanHashes($result);
			return $result;
		}

// Adding Objects
// Adding hosts
		if($options['select_hosts']){
			$obj_params = array('extendoutput' => 1, 'triggerids' => $triggerids, 'nopermissions' => 1, 'preservekeys' => 1);
			$hosts = CHost::get($obj_params);

			$triggers = array();
			foreach($hosts as $hostid => $host){
				foreach($host['triggerids'] as $num => $triggerid){
					if(!isset($triggers[$triggerid])) $triggers[$triggerid] = array('hostids'=> array(), 'hosts' => array());

					$triggers[$triggerid]['hostids'][$hostid] = $hostid;
					$triggers[$triggerid]['hosts'][$hostid] = $host;
				}
			}

			foreach($result as $eventid => $event){
				if(isset($triggers[$event['objectid']])){
					$result[$eventid]['hostids'] = $triggers[$event['objectid']]['hostids'];
					$result[$eventid]['hosts'] = $triggers[$event['objectid']]['hosts'];
				}
				else{
					$result[$eventid]['hostids'] = array();
					$result[$eventid]['hosts'] = array();
				}
			}
		}

// Adding triggers
		if($options['select_triggers']){
			$obj_params = array('extendoutput' => 1, 'triggerids' => $triggerids, 'nopermissions' => 1, 'preservekeys' => 1);
			$triggers = CTrigger::get($obj_params);

			foreach($result as $eventid => $event){
				if(isset($triggers[$event['objectid']])){
					$result[$eventid]['triggerids'][$event['objectid']] = $event['objectid'];
					$result[$eventid]['triggers'][$event['objectid']] = $triggers[$event['objectid']];
				}
				else{
					$result[$eventid]['triggerids'] = array();
					$result[$eventid]['triggers'] = array();
				}
			}
		}

// Adding items
		if($options['select_items']){
			$obj_params = array('extendoutput' => 1, 'triggerids' => $triggerids, 'nopermissions' => 1, 'preservekeys' => 1);
			$db_items = CItem::get($obj_params);
			$items = array();

			$items_evnt = array();
			foreach($db_items as $itemid => $item){
				foreach($item['triggerids'] as $num => $triggerid){
					if(!isset($items[$triggerid])) $items[$triggerid] = array('itemids'=> array(), 'items' => array());

					$items[$triggerid]['itemids'][$itemid] = $itemid;
					$items[$triggerid]['items'][$itemid] = $item;
				}
			}

			foreach($result as $eventid => $event){
				if(isset($items[$event['objectid']])){
					$result[$eventid]['itemids'] = $items[$event['objectid']]['itemids'];
					$result[$eventid]['items'] = $items[$event['objectid']]['items'];
				}
				else{
					$result[$eventid]['itemids'] = array();
					$result[$eventid]['items'] = array();
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
 * Add events ( without alerts )
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param _array $events multidimensional array with events data
 * @param array $events[0,...]['source']
 * @param array $events[0,...]['object']
 * @param array $events[0,...]['objectid']
 * @param array $events[0,...]['clock'] OPTIONAL
 * @param array $events[0,...]['value'] OPTIONAL
 * @param array $events[0,...]['acknowledged'] OPTIONAL
 * @return boolean
 */
	public static function add($events){
		$events = zbx_toArray($events);
		$eventids = array();
		
		$result = false;
		$triggers = array();
		
		self::BeginTransaction(__METHOD__);
		foreach($events as $num => $event){
			$event_db_fields = array(
				'source'		=> null,
				'object'		=> null,
				'objectid'		=> null,
				'clock'			=> time(),
				'value'			=> 0,
				'acknowledged'	=> 0
			);

			if(!check_db_fields($event_db_fields, $event)){
				$result = false;
				break;
			}

			$eventid = get_dbid('events','eventid');
			$sql = 'INSERT INTO events (eventid, source, object, objectid, clock, value, acknowledged) '.
					' VALUES ('.$eventid.','.
								$event['source'].','.
								$event['object'].','.
								$event['objectid'].','.
								$event['clock'].','.
								$event['value'].','.
								$event['acknowledged'].
							')';
			$result = DBexecute($sql);
			if(!$result) break;

			$triggers[] = array('triggerid' => $event['objectid'], 'value'=> $event['value'], 'lastchange'=> $event['clock']);

			$eventids[$eventid] = $eventid;
		}

		if($result){
			$result = CTrigger::update($triggers);
		}

		$result = self::EndTransaction($result, __METHOD__);
		if($result){
			return $eventids;
		}
		else{
			self::$error[] = array('error' => ZBX_API_ERROR_INTERNAL, 'data' => 'Internal zabbix error');
			return false;
		}
	}

/**
 * Delete events by eventids
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param _array $eventids
 * @param array $eventids['eventids']
 * @return boolean
 */
	public static function delete($eventids){
		$eventids = isset($eventids['eventids']) ? $eventids['eventids'] : array();
		zbx_value2array($eventids);

		if(!empty($eventids)){
			$sql = 'DELETE FROM events WHERE '.DBcondition('eventid', $eventids);
			$result = DBexecute($sql);
		}
		else{
			self::setError(__METHOD__, ZBX_API_ERROR_PARAMETERS, 'Empty input parameter [ eventids ]');
			$result = false;
		}

		if($result)
			return true;
		else{
			self::setError(__METHOD__);
			return false;
		}
	}

	/**
	 * Delete events by triggerids
	 *
	 * {@source}
	 * @access public
	 * @static
	 * @since 1.8
	 * @version 1
	 *
	 * @param _array $triggerids
	 * @return boolean
	 */
	public static function deleteByTriggerIDs($triggerids){
		zbx_value2array($triggerids);
		$sql = 'DELETE FROM events e WHERE e.object='.EVENT_OBJECT_TRIGGER.' AND '.DBcondition('e.objectid', $triggerids);
		$result = DBexecute($sql);

		if($result)
			return true;
		else{
			self::$error[] = array('error' => ZBX_API_ERROR_INTERNAL, 'data' => 'Internal zabbix error');
			return false;
		}
	}
}
?>
