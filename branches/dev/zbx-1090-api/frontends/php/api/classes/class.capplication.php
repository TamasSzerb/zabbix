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
 * File containing CApplication class for API.
 * @package API
 */
/**
 * Class containing methods for operations with Applications
 *
 */
class CApplication extends CZBXAPI{
/**
 * Get Applications data
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

		$sort_columns = array('applicationid', 'name'); // allowed columns for sorting

		$sql_parts = array(
			'select' => array('apps' => 'a.applicationid'),
			'from' => array('applications a'),
			'where' => array(),
			'order' => array(),
			'limit' => null);

		$def_options = array(
			'nodeids'				=> null,
			'groupids'				=> null,
			'hostids'				=> null,
			'itemids'				=> null,
			'applicationids'		=> null,
			'editable'				=> null,
			'nopermissions'			=> null,
// Filter
			'pattern'				=> '',

// OutPut
			'extendoutput'			=> null,
			'expand_data'			=> null,
			'select_items'			=> null,
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

		if((USER_TYPE_SUPER_ADMIN == $user_type) || $options['nopermissions']){
		}
		else{
			$permission = $options['editable']?PERM_READ_WRITE:PERM_READ_ONLY;

			$sql_parts['from']['hg'] = 'hosts_groups hg';
			$sql_parts['from']['r'] = 'rights r';
			$sql_parts['from']['ug'] = 'users_groups ug';
			$sql_parts['where'][] = 'hg.hostid=a.hostid';
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
		$nodeids = $options['nodeids'] ? $options['nodeids'] : get_current_nodeid(false);
// groupids
		if(!is_null($options['groupids'])){
			zbx_value2array($options['groupids']);
			if(!is_null($options['extendoutput'])){
				$sql_parts['select']['groupid'] = 'hg.groupid';
			}
			$sql_parts['from']['hg'] = 'hosts_groups hg';
			$sql_parts['where']['ahg'] = 'a.hostid=hg.hostid';
			$sql_parts['where'][] = DBcondition('hg.groupid', $options['groupids']);
		}
// hostids
		if(!is_null($options['hostids'])){
			zbx_value2array($options['hostids']);

			$sql_parts['where'][] = DBcondition('a.hostid', $options['hostids']);
		}
// expand_data
		if(!is_null($options['expand_data'])){
			$sql_parts['select']['host'] = 'h.host';
			$sql_parts['from']['h'] = 'hosts h';
			$sql_parts['where']['ah'] = 'a.hostid=h.hostid';
		}
// itemids
		if(!is_null($options['itemids'])){
			zbx_value2array($options['itemids']);

			if(!is_null($options['extendoutput'])){
				$sql_parts['select']['itemid'] = 'ia.itemid';
			}
			$sql_parts['from']['ia'] = 'items_applications ia';
			$sql_parts['where'][] = DBcondition('ia.itemid', $options['itemids']);
			$sql_parts['where']['aia'] = 'a.applicationid=ia.applicationid';

		}

// applicationids
		if(!is_null($options['applicationids'])){
			zbx_value2array($options['applicationids']);

			if(!is_null($options['extendoutput'])){
				$sql_parts['select']['applicationid'] = 'a.applicationid';
			}
			$sql_parts['where'][] = DBcondition('a.applicationid', $options['applicationids']);

		}

// extendoutput
		if(!is_null($options['extendoutput'])){
			$sql_parts['select']['apps'] = 'a.*';
		}

// count
		if(!is_null($options['count'])){
			$options['select_items'] = 0;
			$options['sortfield'] = '';

			$sql_parts['select'] = array('count(a.applicationid) as rowscount');
		}

// pattern
		if(!zbx_empty($options['pattern'])){
			$sql_parts['where'][] = ' UPPER(a.name) LIKE '.zbx_dbstr('%'.strtoupper($options['pattern']).'%');
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
//----------

		$applicationids = array();

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
				' WHERE '.DBin_node('a.applicationid', $nodeids).
					$sql_where.
				$sql_order;
		$res = DBselect($sql, $sql_limit);
		while($application = DBfetch($res)){
			if($options['count'])
				$result = $application;
			else{
				$applicationids[$application['applicationid']] = $application['applicationid'];

				if(is_null($options['extendoutput'])){
					$result[$application['applicationid']] = $application['applicationid'];
				}
				else{
					if(!isset($result[$application['applicationid']]))
						$result[$application['applicationid']]= array();

					if($options['select_items'] && !isset($result[$application['applicationid']]['itemids'])){
						$result[$application['applicationid']]['itemids'] = array();
						$result[$application['applicationid']]['items'] = array();
					}

					// hostids
					if(isset($application['hostid']) && !is_null($options['hostids'])){
						if(!isset($result[$application['applicationid']]['hostids']))
							$result[$application['applicationid']]['hostids'] = array();

						$result[$application['applicationid']]['hostids'][$application['hostid']] = $application['hostid'];
						unset($application['hostid']);
					}
					// itemids
					if(isset($application['itemid'])){
						if(!isset($result[$application['applicationid']]['itemids']))
							$result[$application['applicationid']]['itemids'] = array();

						$result[$application['applicationid']]['itemids'][$application['itemid']] = $application['itemid'];
						unset($application['itemid']);
					}

					$result[$application['applicationid']] += $application;
				}
			}
		}

		if(is_null($options['extendoutput']) || !is_null($options['count'])){
			if(is_null($options['preservekeys'])) $result = zbx_cleanHashes($result);
			return $result;
		}

// Adding Objects
// Adding items
		if($options['select_items']){
			$obj_params = array('extendoutput' => 1, 'applicationids' => $applicationids, 'nopermissions' => 1, 'preservekeys' => 1);
			$items = CItem::get($obj_params);
			foreach($items as $itemid => $item){
				foreach($item['applicationids'] as $num => $applicationid){
					$result[$applicationid]['itemids'][$itemid] = $itemid;
					$result[$applicationid]['items'][$itemid] = $item;
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
 * Get Application ID by host.name and item.key
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param array $app_data
 * @param array $app_data['name']
 * @param array $app_data['hostid']
 * @return int|boolean
 */
	public static function getObjects($app_data){
		$result = array();
		$applicationids = array();
				
		$sql = 'SELECT applicationid '.
				' FROM applications '.
				' WHERE hostid='.$app_data['hostid'].
					' AND name='.zbx_dbstr($app_data['name']);
		$res = DBselect($sql);
		while($app = DBfetch($res)){
			$applicationids[$app['applicationid']] = $app['applicationid'];
		}
	
		if(!empty($applicationids))
			$result = self::get(array('applicationids'=>$applicationids, 'extendoutput'=>1));
		
	return $result;
	}

/**
 * Add Applications
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param _array $applications
 * @param array $app_data['name']
 * @param array $app_data['hostid']
 * @return boolean
 */
	public static function add($applications){
		$applications = zbx_toArray($applications);
		$applicationids = array();
		
		$result = false;

		self::BeginTransaction(__METHOD__);
		foreach($applications as $anum => $application){
			$result = add_application($application['name'], $application['hostid']);
			
			if(!$result) break;
			$applicationids[] = $result;
		}
		$result = self::EndTransaction($result, __METHOD__);

		if($result){
			$new_applications = self::get(array('applicationids'=>$applicationids, 'extendoutput'=>1, 'nopermissions'=>1));			
			return $new_applications;
		}
		else{
			self::$error[] = array('error' => ZBX_API_ERROR_INTERNAL, 'data' => 'Internal zabbix error');
			return false;
		}
	}

/**
 * Update Applications
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param _array $applications
 * @param array $app_data['name']
 * @param array $app_data['hostid']
 * @return boolean
 */
	public static function update($applications){
		$applications = zbx_toArray($applications);
		$applicationids = array();
		
		$upd_applications = self::get(array('applicationids'=>zbx_objectValues($applications, 'applicationid'), 
											'editable'=>1, 
											'extendoutput'=>1, 
											'preservekeys'=>1));
		foreach($applications as $anum => $application){
			if(!isset($upd_applications[$application['applicationid']])){
				self::setError(__METHOD__, ZBX_API_ERROR_PERMISSIONS, 'You have not enough rights for operation');
				return false;
			}
			$applicationids[] = $application['applicationid'];
		}
		
		$result = false;

		self::BeginTransaction(__METHOD__);
		foreach($applications as $anum => $application){
			$application_db_fields = $upd_applications[$application['applicationid']];

			if(!check_db_fields($application_db_fields, $application)){
				error('Incorrect arguments pasted to function [CApplication::update]');
				$result = false;
				break;
			}
			
			$result = update_application($application['applicationid'], $application['name'], $application['hostid']);
			
			if(!$result) break;
		}
		$result = self::EndTransaction($result, __METHOD__);

		if($result){
			$upd_applications = self::get(array('applicationids'=>$applicationids, 'extendoutput'=>1, 'nopermissions'=>1));			
			return $upd_applications;
		}
		else{
			self::$error[] = array('error' => ZBX_API_ERROR_INTERNAL, 'data' => 'Internal zabbix error');
			return false;
		}
	}

/**
 * Delete Applications
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param _array $applications
 * @param array $applications[0,...]['applicationid']
 * @return boolean
 */
	public static function delete($applications){
		$applications = zbx_toArray($applications);		
		$applicationids = array();
		
		$del_applications = self::get(array('applicationids'=>zbx_objectValues($applications, 'applicationid'), 
											'editable'=>1, 
											'extendoutput'=>1, 
											'preservekeys'=>1));
		foreach($applications as $anum => $application){
			if(!isset($del_applications[$application['applicationid']])){
				self::setError(__METHOD__, ZBX_API_ERROR_PERMISSIONS, 'You have not enough rights for operation');
				return false;
			}

			$applicationids[] = $application['applicationid'];
			//add_audit(AUDIT_ACTION_DELETE, AUDIT_RESOURCE_APPLICATION, 'application ['.$application['name'].']');
		}

		if(!empty($applicationids)){
			$result = delete_application($applicationids);
		}
		else{
			self::setError(__METHOD__, ZBX_API_ERROR_PARAMETERS, 'Empty input parameter [ applicationids ]');
			$result = false;
		}

		if($result){
			return zbx_cleanHashes($del_applications);
		}
		else{
			self::setError(__METHOD__);
			return false;
		}
	}

	
/**
 * Add Items to applications 
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param array $data
 * @param array $data['applications']
 * @param array $data['items']
 * @return boolean
 */
	public static function addItems($data){
	
		$result = true;
		
		$applications = zbx_toArray($data['applications']);
		$items = zbx_toArray($data['items']);
		$applicationids = array();
		$itemids = array();
		
// PERMISSION {{{
		$allowed_applications = self::get(array(
			'applicationids' => zbx_objectValues($applications, 'applicationid'),
			'editable' => 1, 
			'extendoutput' => 1, 
			'preservekeys' => 1)
		);
		foreach($applications as $num => $application){
			if(!isset($allowed_applications[$application['applicationid']])){
				self::setError(__METHOD__, ZBX_API_ERROR_PERMISSIONS, 'You have not enough rights for operation');
				return false;
			}
			$applicationids[] = $application['applicationid'];
		}
		
		$allowed_items = CItem::get(array(
			'itemids' => zbx_objectValues($items, 'itemid'),
			'editable' => 1, 
			'extendoutput' => 1, 
			'preservekeys' => 1)
		);
		foreach($items as $num => $item){
			if(!isset($allowed_items[$item['itemid']])){
				self::setError(__METHOD__, ZBX_API_ERROR_PERMISSIONS, 'You have not enough rights for operation');
				return false;
			}
			$itemids[] = $item['itemid'];
		}
// }}} PERMISSION
	
		
		self::BeginTransaction(__METHOD__);
		
		$sql = 'SELECT itemid, applicationid FROM items_applications WHERE '.
			DBcondition('itemid', $itemids).' AND '.DBcondition('applicationid', $applicationids);
		$linked_db = DBexecute($sql);
		while($pair = DBfetch($linked_db)){
			$linked[$pair['applicationid']] = array($pair['itemid'] => $pair['itemid']);
		}

		foreach($applicationids as $anum => $applicationid){
			foreach($itemids as $inum => $itemid){
				if(isset($linked[$applicationid]) && isset($linked[$applicationid][$itemid])) continue;

				$itemappid = get_dbid('items_applications', 'itemappid');
				$result = DBexecute("INSERT INTO items_applications (itemappid, itemid, applicationid) VALUES ($itemappid, $itemid, $applicationid)");
				if(!$result){
					break 2;
				}
			}
		}
		
		if($result){
			foreach($itemids as $itemid){
				$db_childs = DBselect('SELECT itemid, hostid FROM items WHERE templateid='.$itemid);
				
				if($child = DBfetch($db_childs)){
					$db_apps = DBselect('SELECT a1.applicationid FROM applications a1, applications a2'.
						" WHERE a1.name=a2.name AND a1.hostid={$child['hostid']} AND ".DBcondition('a2.applicationid', $applicationids));
					while($app = DBfetch($db_apps)){
						$child_applications[] = $app;					
					}
					$result = self::addItems(array('items' => $child, 'applications' => $child_applications));
					if(!$result){
						break;
					}
				}
			}
		}
		
		
		$result = self::EndTransaction($result, __METHOD__);	
	
		if($result){
			$result = self::get(array(
				'applicationids' => $applicationids, 
				'extendoutput' => 1, 
				'select_items' => 1,
				'nopermission' => 1));
			return $result;
		}
		else{
			self::setError(__METHOD__);
			return false;
		}
	}
 
 
}

?>
