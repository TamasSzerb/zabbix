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
 * File containing CHostGroup class for API.
 * @package API
 */
/**
 * Class containing methods for operations with HostGroups
 */
class CHostGroup extends CZBXAPI{
/**
 * Get HostGroups
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param array $params
 * @return array
 */
	public static function get($params){
		global $USER_DETAILS;

		$result = array();
		$user_type = $USER_DETAILS['type'];
		$userid = $USER_DETAILS['userid'];

		$sort_columns = array('groupid', 'name'); // allowed columns for sorting


		$sql_parts = array(
			'select'	=> array('groups' => 'g.groupid'),
			'from' 		=> array('groups g'),
			'where' 	=> array(),
			'order' 	=> array(),
			'limit' 	=> null);

		$def_options = array(
			'nodeids'					=> null,
			'groupids'					=> null,
			'hostids'					=> null,
			'monitored_hosts'			=> null,
			'templated_hosts' 			=> null,
			'real_hosts' 				=> null,
			'not_proxy_hosts'			=> null,
			'with_items'				=> null,
			'with_monitored_items' 		=> null,
			'with_historical_items'		=> null,
			'with_triggers'				=> null,
			'with_monitored_triggers' 	=> null,
			'with_httptests' 			=> null,
			'with_monitored_httptests'	=> null,
			'with_graphs'				=> null,
			'only_current_node'			=> null,
			'editable'					=> null,
			'nopermissions'				=> null,

// filter
			'pattern' 					=> '',

// output
			'extendoutput'				=> null,
			'select_hosts'				=> null,
			'count'						=> null,
			'preservekeys'				=> null,

			'sortfield'					=> '',
			'sortorder'					=> '',
			'limit'						=> null
		);

		$options = zbx_array_merge($def_options, $params);

// editable + PERMISSION CHECK
		if(defined('ZBX_API_REQUEST')){
			$options['nopermissions'] = false;
		}

		if((USER_TYPE_SUPER_ADMIN == $user_type) || $options['nopermissions']){
		}
		else{
			$permission = $options['editable'] ? PERM_READ_WRITE : PERM_READ_ONLY;

			$sql_parts['from']['r'] = 'rights r';
			$sql_parts['from']['ug'] = 'users_groups ug';
			$sql_parts['where'][] = 'r.id=g.groupid';
			$sql_parts['where'][] = 'r.groupid=ug.usrgrpid';
			$sql_parts['where'][] = 'ug.userid='.$userid;
			$sql_parts['where'][] = 'r.permission>='.$permission;
			$sql_parts['where'][] = 'NOT EXISTS( '.
									' SELECT gg.groupid '.
										' FROM groups gg, rights rr, users_groups ugg '.
										' WHERE rr.id=g.groupid '.
											' AND rr.groupid=ugg.usrgrpid '.
											' AND ugg.userid='.$userid.
											' AND rr.permission<'.$permission.')';
		}

// nodeids
		$nodeids = $options['nodeids'] ? $options['nodeids'] : get_current_nodeid(false);

// groupids
		if(!is_null($options['groupids'])){
			zbx_value2array($options['groupids']);
			$sql_parts['where'][] = DBcondition('g.groupid', $options['groupids']);
		}

// hostids
		if(!is_null($options['hostids'])){
			zbx_value2array($options['hostids']);
			if(!is_null($options['extendoutput'])){
				$sql_parts['select']['hostid'] = 'hg.hostid';
			}

			$sql_parts['from']['hg'] = 'hosts_groups hg';
			$sql_parts['where'][] = DBcondition('hg.hostid', $options['hostids']);
			$sql_parts['where']['hgg'] = 'hg.groupid=g.groupid';
		}

// monitored_hosts, real_hosts, templated_hosts, not_proxy_hosts
		if(!is_null($options['monitored_hosts'])){
			$sql_parts['from']['hg'] = 'hosts_groups hg';
			$sql_parts['from']['h'] = 'hosts h';
			$sql_parts['where']['hgg'] = 'hg.groupid=g.groupid';
			$sql_parts['where'][] = 'h.hostid=hg.hostid';
			$sql_parts['where'][] = 'h.status='.HOST_STATUS_MONITORED;
		}
		else if(!is_null($options['real_hosts'])){
			$sql_parts['from']['hg'] = 'hosts_groups hg';
			$sql_parts['from']['h'] = 'hosts h';
			$sql_parts['where']['hgg'] = 'hg.groupid=g.groupid';
			$sql_parts['where'][] = 'h.hostid=hg.hostid';
			$sql_parts['where'][] = 'h.status IN('.HOST_STATUS_MONITORED.','.HOST_STATUS_NOT_MONITORED.')';
		}
		else if(!is_null($options['templated_hosts'])){
			$sql_parts['from']['hg'] = 'hosts_groups hg';
			$sql_parts['from']['h'] = 'hosts h';
			$sql_parts['where']['hgg'] = 'hg.groupid=g.groupid';
			$sql_parts['where'][] = 'h.hostid=hg.hostid';
			$sql_parts['where'][] = 'h.status='.HOST_STATUS_TEMPLATE;
		}
		else if(!is_null($options['not_proxy_hosts'])){
			$sql_parts['from']['hg'] = 'hosts_groups hg';
			$sql_parts['from']['h'] = 'hosts h';
			$sql_parts['where']['hgg'] = 'hg.groupid=g.groupid';
			$sql_parts['where'][] = 'h.hostid=hg.hostid';
			$sql_parts['where'][] = 'h.status<>'.HOST_STATUS_PROXY;
		}

// with_items, with_monitored_items, with_historical_items
		if(!is_null($options['with_items'])){
			$sql_parts['from']['hg'] = 'hosts_groups hg';
			$sql_parts['where']['hgg'] = 'hg.groupid=g.groupid';
			$sql_parts['where'][] = 'EXISTS (SELECT i.hostid FROM items i WHERE hg.hostid=i.hostid )';
		}
		else if(!is_null($options['with_monitored_items'])){
			$sql_parts['from']['hg'] = 'hosts_groups hg';
			$sql_parts['where']['hgg'] = 'hg.groupid=g.groupid';
			$sql_parts['where'][] = 'EXISTS (SELECT i.hostid FROM items i WHERE hg.hostid=i.hostid AND i.status='.ITEM_STATUS_ACTIVE.')';
		}
		else if(!is_null($options['with_historical_items'])){
			$sql_parts['from']['hg'] = 'hosts_groups hg';
			$sql_parts['where']['hgg'] = 'hg.groupid=g.groupid';
			$sql_parts['where'][] = 'EXISTS (SELECT i.hostid FROM items i WHERE hg.hostid=i.hostid AND (i.status='.ITEM_STATUS_ACTIVE.' OR i.status='.ITEM_STATUS_NOTSUPPORTED.') AND i.lastvalue IS NOT NULL)';
		}

// with_triggers, with_monitored_triggers
		if(!is_null($options['with_triggers'])){
			$sql_parts['from']['hg'] = 'hosts_groups hg';
			$sql_parts['where']['hgg'] = 'hg.groupid=g.groupid';
			$sql_parts['where'][] = 'EXISTS( SELECT t.triggerid '.
										' FROM items i, functions f, triggers t'.
										' WHERE i.hostid=hg.hostid '.
											' AND f.itemid=i.itemid '.
											' AND t.triggerid=f.triggerid)';
		}
		else if(!is_null($options['with_monitored_triggers'])){
			$sql_parts['from']['hg'] = 'hosts_groups hg';
			$sql_parts['where']['hgg'] = 'hg.groupid=g.groupid';
			$sql_parts['where'][] = 'EXISTS( SELECT t.triggerid '.
										' FROM items i, functions f, triggers t'.
										' WHERE i.hostid=hg.hostid '.
											' AND i.status='.ITEM_STATUS_ACTIVE.
											' AND i.itemid=f.itemid '.
											' AND f.triggerid=t.triggerid '.
											' AND t.status='.TRIGGER_STATUS_ENABLED.')';
		}

// with_httptests, with_monitored_httptests
		if(!is_null($options['with_httptests'])){
			$sql_parts['from']['hg'] = 'hosts_groups hg';
			$sql_parts['where']['hgg'] = 'hg.groupid=g.groupid';
			$sql_parts['where'][] = 'EXISTS( SELECT a.applicationid '.
									' FROM applications a, httptest ht '.
									' WHERE a.hostid=hg.hostid '.
										' AND ht.applicationid=a.applicationid)';
		}
		else if(!is_null($options['with_monitored_httptests'])){
			$sql_parts['from']['hg'] = 'hosts_groups hg';
			$sql_parts['where']['hgg'] = 'hg.groupid=g.groupid';
			$sql_parts['where'][] = 'EXISTS( SELECT a.applicationid '.
									' FROM applications a, httptest ht '.
									' WHERE a.hostid=hg.hostid '.
										' AND ht.applicationid=a.applicationid '.
										' AND ht.status='.HTTPTEST_STATUS_ACTIVE.')';
		}

// with_graphs
		if(!is_null($options['with_graphs'])){
			$sql_parts['from']['hg'] = 'hosts_groups hg';
			$sql_parts['where']['hgg'] = 'hg.groupid=g.groupid';
			$sql_parts['where'][] = 'EXISTS( SELECT DISTINCT i.itemid '.
										' FROM items i, graphs_items gi '.
										' WHERE i.hostid=hg.hostid '.
											' AND i.itemid=gi.itemid)';
		}

// extendoutput
		if(!is_null($options['extendoutput'])){
			$sql_parts['select']['groups'] = 'g.*';
		}

// count
		if(!is_null($options['count'])){
			$options['select_hosts'] = 0;
			$options['sortfield'] = '';

			$sql_parts['select']['groups'] = 'COUNT(DISTINCT g.groupid) as rowscount';
		}

// pattern
		if(!zbx_empty($options['pattern'])){
			$sql_parts['where'][] = ' UPPER(g.name) LIKE '.zbx_dbstr('%'.strtoupper($options['pattern']).'%');
		}

// order
// restrict not allowed columns for sorting
		$options['sortfield'] = str_in_array($options['sortfield'], $sort_columns) ? $options['sortfield'] : '';
		if(!zbx_empty($options['sortfield'])){
			$sortorder = ($options['sortorder'] == ZBX_SORT_DOWN)?ZBX_SORT_DOWN:ZBX_SORT_UP;

			$sql_parts['order'][] = 'g.'.$options['sortfield'].' '.$sortorder;

			if(!str_in_array('g.'.$options['sortfield'], $sql_parts['select']) && !str_in_array('g.*', $sql_parts['select'])){
				$sql_parts['select'][] = 'g.'.$options['sortfield'];
			}
		}

// limit
		if(zbx_ctype_digit($options['limit']) && $options['limit']){
			$sql_parts['limit'] = $options['limit'];
		}
//-----------

		$groupids = array();

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
				' WHERE '.DBin_node('g.groupid', $nodeids).
					$sql_where.
				$sql_order;
		$res = DBselect($sql, $sql_limit);
		while($group = DBfetch($res)){
			if($options['count'])
				$result = $group;
			else{
				if(is_null($options['extendoutput'])){
					$result[$group['groupid']] = $group['groupid'];
				}
				else{
					$groupids[$group['groupid']] = $group['groupid'];

					if(!isset($result[$group['groupid']])) $result[$group['groupid']]= array();

					if($options['select_hosts'] && !isset($result[$group['groupid']]['hostids'])){
						$result[$group['groupid']]['hostids'] = array();
						$result[$group['groupid']]['hosts'] = array();
					}

					// hostids
					if(isset($group['hostid'])){
						if(!isset($result[$group['groupid']]['hostids']))
							$result[$group['groupid']]['hostids'] = array();

						$result[$group['groupid']]['hostids'][$group['hostid']] = $group['hostid'];
						unset($group['hostid']);
					}

					$result[$group['groupid']] += $group;
				}
			}
		}

		if(is_null($options['extendoutput']) || !is_null($options['count'])){
			if(is_null($options['preservekeys'])) $result = zbx_cleanHashes($result);
			return $result;
		}

// Adding hosts
		if($options['select_hosts']){
			$obj_params = array('extendoutput' => 1, 'groupids' => $groupids, 'templated_hosts' => 1, 'preservekeys' => 1);
			$hosts = CHost::get($obj_params);
			foreach($hosts as $hostid => $host){
				foreach($host['groupids'] as $num => $groupid){
					$result[$groupid]['hostids'][$hostid] = $hostid;
					$result[$groupid]['hosts'][$hostid] = $host;
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
 * Get HostGroup ID by name
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param array $data
 * @param array $data['name']
 * @return string|boolean HostGroup ID or false if error
 */
	public static function getObjects($data){
		$result = array();
		$groupids = array();

		$sql = 'SELECT groupid '.
				' FROM groups '.
				' WHERE name='.zbx_dbstr($data['name']).
					' AND '.DBin_node('groupid', false);
		$res = DBselect($sql);
		while($group=DBfetch($res)){
			$groupids[$group['groupid']] = $group['groupid'];
		}

		if(!empty($groupids))
			$result = self::get(array('groupids'=>$groupids, 'extendoutput'=>1));
		
	return $result;
	}

/**
 * Add HostGroups
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param array $groups array with HostGroup names
 * @param array $groups['name']
 * @return array
 */
	public static function add($groups){
		global $USER_DETAILS;
		$errors = array();
		
		if(USER_TYPE_SUPER_ADMIN != $USER_DETAILS['type']){
			self::setError(__METHOD__, ZBX_API_ERROR_PERMISSIONS, 'Only Super Admins can create HostGroups');
			return false;
		}

		$groups = zbx_toArray($groups);
		$groupids = array();
		
		$result = true;

		self::BeginTransaction(__METHOD__);
		foreach($groups as $num => $group){
			if(!is_array($group) || !isset($group['name']) || empty($group['name'])){
				$errors[] = array('errno' => ZBX_API_ERROR_PARAMETERS, 'error' => 'Empty input parameter [ name ]');
				$result = false;
				break;
			}
			$group = $group['name'];

			$group_exist = self::getObjects(array('name' => $group));
			if(!empty($group_exist)){
				$errors[] = array('errno' => ZBX_API_ERROR_PARAMETERS, 'error' => "HostGroup [ $group ] already exists");
				$result = false;
				break;
			}

			$groupid = get_dbid('groups', 'groupid');
			$sql = 'INSERT INTO groups (groupid, name, internal) VALUES ('.$groupid.', '.zbx_dbstr($group).', '.ZBX_NOT_INTERNAL_GROUP.')';
			$result = DBexecute($sql);
			if(!$result) break;

			$groupids[] = $groupid;
		}
		$result = self::EndTransaction($result, __METHOD__);

		if($result){
			$new_groups = self::get(array('groupids'=>$groupids, 'extendoutput'=>1, 'nopermissions'=>1));
			return $new_groups;
		}
		else{
			self::setMethodErrors(__METHOD__, $errors);
			return false;
		}
	}

/**
 * Update HostGroup
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param array $groups
 * @param array $groups[0]['name'], ...
 * @param array $groups[0]['groupid'], ...
 * @return boolean
 */
	public static function update($groups){
		$groups = zbx_toArray($groups);
		$groupids = array();
		
		$upd_groups = self::get(array('groupids'=>zbx_objectValues($groups, 'groupid'),
										'editable'=>1, 
										'extendoutput'=>1, 
										'preservekeys'=>1));
		foreach($groups as $gnum => $group){
			if(!isset($upd_groups[$group['groupid']])){
				self::setError(__METHOD__, ZBX_API_ERROR_PERMISSIONS, 'You have not enough rights for operation');
				return false;
			}
			$groupids[] = $group['groupid'];
		}

		$result = true;

		if(empty($groups)){
			self::setError(__METHOD__, ZBX_API_ERROR_PARAMETERS, 'Empty input parameter');
			return false;
		}

		self::BeginTransaction(__METHOD__);
		foreach($groups as $num => $group){

			$group_exist = self::getObjects(array('name' => $group['name']));
			$group_exist = reset($group_exist);
			if(!$group_exist && ($group_exist['groupid'] != $group['groupid'])){
				self::setError(__METHOD__, ZBX_API_ERROR_PARAMETERS, 'HostGroup [ '.$group['name'].' ] already exists');
				$result = false;
				break;
			}

			$sql = 'UPDATE groups SET name='.zbx_dbstr($group['name']).' WHERE groupid='.$group['groupid'];
			if(!DBexecute($sql)){
				$result = false;
				break;
			}

		}

		$result = self::EndTransaction($result, __METHOD__);

		if($result){
			$upd_groups = self::get(array('groupids'=>$groupids, 'extendoutput'=>1, 'nopermissions'=>1));
			return $upd_groups;
		}
		else{
			self::setError(__METHOD__);
			return false;
		}
	}

/**
 * Delete HostGroups
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param array $groups
 * @param array $groups[0,..]['groupid']
 * @return boolean
 */
	public static function delete($groups){
		$groups = zbx_toArray($groups);
		$groupids = array();
		
		$del_groups = self::get(array('groupids'=>zbx_objectValues($groups, 'groupid'), 
											'editable'=>1, 
											'extendoutput'=>1, 
											'preservekeys'=>1));
		foreach($groups as $gnum => $group){
			if(!isset($del_groups[$group['groupid']])){
				self::setError(__METHOD__, ZBX_API_ERROR_PERMISSIONS, 'You have not enough rights for operation');
				return false;
			}
			$groupids[] = $group['groupid'];
			//add_audit(AUDIT_ACTION_DELETE, AUDIT_RESOURCE_HOSTGROUP, 'Group ['.$group['name'].']');
		}

		if(empty($groupids)){
			self::setError(__METHOD__, ZBX_API_ERROR_PARAMETERS, 'Empty input parameter');
			return false;
		}
/*
// TODO: PEREDELATJ iz frontenda ->
		$dlt_groupids = getDeletableHostGroups($groupids);
		if(count($groupids) != count($dlt_groupids)){
			foreach($groupids as $groupid){
				if(!isset($dlt_groupids[$groupid])){
					$group = self::get(array('groupids' => $groupid,  'extendoutput' => 1));
					$group = reset($group);
					if($group['internal'] == ZBX_INTERNAL_GROUP)
						self::setError(__METHOD__, ZBX_API_ERROR_PARAMETERS, "HostGroup [ {$group['name']} ] is internal and can not be deleted");
					else
						self::setError(__METHOD__, ZBX_API_ERROR_PARAMETERS, "HostGroup [ {$group['name']} ] can not be deleted, due to inner hosts can not be unlinked");
				}
			}
			return false;
		}


		self::BeginTransaction(__METHOD__);

// delete sysmap element
		if(!delete_sysmaps_elements_with_groupid($groupids))
			return false;

// delete host from maintenances
		DBexecute('DELETE FROM maintenances_groups WHERE '.DBcondition('groupid',$groupids));

// disable actions
		$actionids = array();

// conditions
		$sql = 'SELECT DISTINCT c.actionid '.
				' FROM conditions c '.
				' WHERE c.conditiontype='.CONDITION_TYPE_HOST_GROUP.
					' AND '.DBcondition('c.value',$groupids, false, true);
		$db_actions = DBselect($sql);
		while($db_action = DBfetch($db_actions)){
			$actionids[$db_action['actionid']] = $db_action['actionid'];
		}

// operations
		$sql = 'SELECT DISTINCT o.actionid '.
				' FROM operations o '.
				' WHERE o.operationtype IN ('.OPERATION_TYPE_GROUP_ADD.','.OPERATION_TYPE_GROUP_REMOVE.') '.
					' AND '.DBcondition('o.objectid',$groupids);
		$db_actions = DBselect($sql);
		while($db_action = DBfetch($db_actions)){
			$actionids[$db_action['actionid']] = $db_action['actionid'];
		}

		if(!empty($actionids)){
			DBexecute('UPDATE actions '.
					' SET status='.ACTION_STATUS_DISABLED.
					' WHERE '.DBcondition('actionid',$actionids));
		}

// delete action conditions
		DBexecute('DELETE FROM conditions'.
					' WHERE conditiontype='.CONDITION_TYPE_HOST_GROUP.
						' AND '.DBcondition('value',$groupids, false, true));

// delete action operations
		DBexecute('DELETE FROM operations '.
					' WHERE operationtype IN ('.OPERATION_TYPE_GROUP_ADD.','.OPERATION_TYPE_GROUP_REMOVE.') '.
						' AND '.DBcondition('objectid',$groupids));


		DBexecute('DELETE FROM hosts_groups WHERE '.DBcondition('groupid',$groupids));

		foreach ($groupids as $id) {	// The section should be improved
			$hostgroup_old = get_hostgroup_by_groupid($id);
			$result = DBexecute('DELETE FROM groups WHERE groupid='.$id);
			if ($result)
				//add_audit_ext(AUDIT_ACTION_DELETE, AUDIT_RESOURCE_HOST_GROUP, $id, $hostgroup_old['name'], 'groups', NULL, NULL);
			else
				break;
		}

*/
		self::BeginTransaction(__METHOD__);
		
		$result = delete_host_group($groupids);
		
		$result = self::EndTransaction($result, __METHOD__);

		if($result){
			return zbx_cleanHashes($del_groups);
		}
		else{
			self::setError(__METHOD__);
			return false;
		}
	}

/**
 * Add Hosts to HostGroups. All Hosts are added to all HostGroups.
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param array $data
 * @param array $data['groups']
 * @param array $data['hosts']
 * @return boolean
 */
	public static function addHosts($data){
		
		$result = true;
		$groups = zbx_toArray($data['groups']);
		$hosts = zbx_toArray($data['hosts']);
		$groupids = array();
		$hostids = array();
		
// PERMISSION {{{
		$allowed_groups = self::get(array(
			'groupids' => zbx_objectValues($groups, 'groupid'),
			'editable' => 1, 
			'extendoutput' => 1, 
			'preservekeys' => 1)
		);
		foreach($groups as $num => $group){
			if(!isset($allowed_groups[$group['groupid']])){
				self::setError(__METHOD__, ZBX_API_ERROR_PERMISSIONS, 'You have not enough rights for operation');
				return false;
			}
			$groupids[] = $group['groupid'];
		}
		
		$allowed_hosts = CHost::get(array(
			'hostids' => zbx_objectValues($hosts, 'hostid'),
			'editable' => 1, 
			'templated_hosts' => 1,
			'extendoutput' => 1, 
			'preservekeys' => 1)
		);
		foreach($hosts as $num => $host){
			if(!isset($allowed_hosts[$host['hostid']])){
				self::setError(__METHOD__, ZBX_API_ERROR_PERMISSIONS, 'You have not enough rights for operation');
				return false;
			}
			$hostids[] = $host['hostid'];
		}
// }}} PERMISSION	
		

		self::BeginTransaction(__METHOD__);
		$sql = 'SELECT hostid, groupid FROM hosts_groups WHERE '.DBcondition('hostid', $hostids).' AND '.DBcondition('groupid', $groupids);
		$linked_db = DBexecute($sql);
		while($pair = DBfetch($linked_db)){
			$linked[$pair['groupid']] = array($pair['hostid'] => $pair['hostid']);
		}

		foreach($groupids as $gnum => $groupid){
			foreach($hostids as $hostid){
				if(isset($linked[$groupid]) && isset($linked[$groupid][$hostid])) continue;

				$hostgroupid = get_dbid('hosts_groups', 'hostgroupid');
				$result = DBexecute("INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES ($hostgroupid, $hostid, $groupid)");
				if(!$result){
					break 2;
				}
			}
		}
		$result = self::EndTransaction($result, __METHOD__);

		if($result){
			$result = self::get(array(
				'groupids' => $groupids, 
				'extendoutput' => 1, 
				'select_hosts' => 1,
				'nopermission' => 1));
			return $result;
		}
		else{
			self::setError(__METHOD__);
			return false;
		}
	}

/**
 * Remove Hosts from HostGroups
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param array $data
 * @param array $data['groups']
 * @param array $data['hosts']
 * @return boolean
 */
	public static function removeHosts($data){

		$result = true;
		$groups = zbx_toArray($data['groups']);
		$hosts = zbx_toArray($data['hosts']);
		$groupids = array();
		$hostids = array();
		
// PERMISSION {{{
		$allowed_groups = self::get(array(
			'groupids' => zbx_objectValues($groups, 'groupid'),
			'editable' => 1, 
			'extendoutput' => 1, 
			'preservekeys' => 1)
		);
		foreach($groups as $num => $group){
			if(!isset($allowed_groups[$group['groupid']])){
				self::setError(__METHOD__, ZBX_API_ERROR_PERMISSIONS, 'You have not enough rights for operation');
				return false;
			}
			$groupids[] = $group['groupid'];
		}
		$groupids = array_unique($groupids);
		
		$allowed_hosts = CHost::get(array(
			'hostids' => zbx_objectValues($hosts, 'hostid'),
			'editable' => 1, 
			'templated_hosts' => 1,
			'extendoutput' => 1, 
			'preservekeys' => 1)
		);
		foreach($hosts as $num => $host){
			if(!isset($allowed_hosts[$host['hostid']])){
				self::setError(__METHOD__, ZBX_API_ERROR_PERMISSIONS, 'You have not enough rights for operation');
				return false;
			}
			$hostids[] = $host['hostid'];
		}
		$hostids = array_unique($hostids);
// }}} PERMISSION	


		self::BeginTransaction(__METHOD__);

		$unlinkable = getUnlinkableHosts($groupids, $hostids);
		if(count($hostids) != count($unlinkable)){
			self::setError(__METHOD__, ZBX_API_ERROR_PARAMETERS, 'One of the Hosts is left without Hostgroup');
			return false;
		}

		self::BeginTransaction(__METHOD__);

		$sql = 'DELETE FROM hosts_groups WHERE '.DBcondition('hostid', $hostids).' AND '.DBcondition('groupid', $groupids);
		$result = DBexecute($sql);

		$result = self::EndTransaction($result, __METHOD__);

		if($result){
			$result = self::get(array(
				'groupids' => $groupids, 
				'extendoutput' => 1, 
				'select_hosts' => 1,
				'nopermission' => 1));
			return $result;
		}
		else{
			self::setError(__METHOD__);
			return false;
		}
	}

/**
 * Update HostGroups with new Hosts (rewrite)
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param array $data
 * @param array $data['groups']
 * @param array $data['hosts']
 * @return boolean
 */
	public static function updateHosts($data){

		$result = true;
		$groups = zbx_toArray($data['groups']);
		$hosts = zbx_toArray($data['hosts']);
		$groupids = array();
		$hostids = array();
		
// PERMISSION {{{
		$allowed_groups = self::get(array(
			'groupids' => zbx_objectValues($groups, 'groupid'),
			'editable' => 1, 
			'extendoutput' => 1, 
			'preservekeys' => 1)
		);
		foreach($groups as $num => $group){
			if(!isset($allowed_groups[$group['groupid']])){
				self::setError(__METHOD__, ZBX_API_ERROR_PERMISSIONS, 'You have not enough rights for operation');
				return false;
			}
			$groupids[] = $group['groupid'];
		}
		
		$allowed_hosts = CHost::get(array(
			'hostids' => zbx_objectValues($hosts, 'hostid'),
			'editable' => 1, 
			'templated_hosts' => 1,
			'extendoutput' => 1, 
			'preservekeys' => 1)
		);
		foreach($hosts as $num => $host){
			if(!isset($allowed_hosts[$host['hostid']])){
				self::setError(__METHOD__, ZBX_API_ERROR_PERMISSIONS, 'You have not enough rights for operation');
				return false;
			}
			$hostids[] = $host['hostid'];
		}
// }}} PERMISSION


		self::BeginTransaction(__METHOD__);
		$sql = 'DELETE FROM hosts_groups WHERE '.DBcondition('hostid', $hostids);
		$result = DBexecute($sql);
// TODO mozhno poprobovatj otsjuda ubratj perm. check, t.k v addHosts tozhe budet proverjatsja
// poidee vozvrashsatj mozhno result ot add hosts
		$result = self::addHosts($data);
		$result = self::EndTransaction($result, __METHOD__);

		if($result){
			$result = self::get(array(
				'groupids' => $groupids, 
				'extendoutput' => 1, 
				'select_hosts' => 1,
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