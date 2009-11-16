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
 * File containing CUser class for API.
 * @package API
 */
/**
 * Class containing methods for operations with Users
 */
class CUser extends CZBXAPI{
/**
 * Get Users data
 *
 * First part of parameters are filters which limits the output result set, these filters are set only if appropriate parameter is set.
 * For example if "type" is set, then method returns only users of given type.
 * Second part of parameters extends result data, adding data about others objects that are related to objects we get.
 * For example if "select_usrgrps" parameter is set, resulting objects will have additional property 'usrgrps' containing object with
 * data about User UserGroups.
 * Third part of parameters affect output. For example "sortfield" will be set to 'alias', result will be sorted by User alias.
 * All Parameters are optional!
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param _array $options
 * @param array $options['nodeids'] filter by Node IDs
 * @param array $options['usrgrpids'] filter by UserGroup IDs
 * @param array $options['userids'] filter by User IDs
 * @param boolean $options['type'] filter by User type [ USER_TYPE_ZABBIX_USER: 1, USER_TYPE_ZABBIX_ADMIN: 2, USER_TYPE_SUPER_ADMIN: 3 ]
 * @param boolean $options['status'] filter by User status [ 0, 1 ]
 * @param boolean $options['with_gui_access'] filter only with GUI access
 * @param boolean $options['with_api_access'] filter only with API access
 * @param boolean $options['select_usrgrps'] extend with UserGroups data for each User
 * @param boolean $options['get_access'] extend with access data for each User
 * @param boolean $options['extendoutput'] output only User IDs if not set.
 * @param boolean $options['count'] output only count of objects in result. ( ruselt returned in property 'rowscount' )
 * @param string $options['pattern'] filter by Host name containing only give pattern
 * @param int $options['limit'] output will be limited to given number
 * @param string $options['sortfield'] output will be sorted by given property [ 'userid', 'alias' ]
 * @param string $options['sortorder'] output will be sorted in given order [ 'ASC', 'DESC' ]
 * @return array
 */
	public static function get($options=array()){
		global $USER_DETAILS;

		$result = array();
		$user_type = $USER_DETAILS['type'];
		$userid = $USER_DETAILS['userid'];

		$sort_columns = array('userid', 'alias'); // allowed columns for sorting


		$sql_parts = array(
			'select' => array('users' => 'u.userid'),
			'from' => array('users u'),
			'where' => array(),
			'order' => array(),
			'limit' => null);

		$def_options = array(
			'nodeids'					=> null,
			'usrgrpids'					=> null,
			'userids'					=> null,
			'users' 					=> null,
			'type'						=> null,
			'status'					=> null,
			'with_gui_access'			=> null,
			'with_api_access'			=> null,
// filter
			'pattern'					=> '',

// OutPut
			'extendoutput'				=> null,
			'editable'					=> null,
			'select_usrgrps'			=> null,
			'get_access'				=> null,
			'count'						=> null,
			'preservekeys'				=> null,

			'sortfield'					=> '',
			'sortorder'					=> '',
			'limit'						=> null
		);

		$options = zbx_array_merge($def_options, $options);

// PERMISSION CHECK
		if(USER_TYPE_SUPER_ADMIN == $user_type){

		}
		else if($options['editable']){
			return $result();
		}

// nodeids
		$nodeids = $options['nodeids'] ? $options['nodeids'] : get_current_nodeid(false);

// usrgrpids
		if(!is_null($options['usrgrpids'])){
			zbx_value2array($options['usrgrpids']);
			if(!is_null($options['extendoutput'])){
				$sql_parts['select']['usrgrpid'] = 'ug.usrgrpid';
			}
			$sql_parts['from']['ug'] = 'users_groups ug';
			$sql_parts['where'][] = DBcondition('ug.usrgrpid', $options['usrgrpids']);
			$sql_parts['where']['uug'] = 'u.userid=ug.userid';

		}

// userids
		if(!is_null($options['userids'])){
			zbx_value2array($options['userids']);
			$sql_parts['where'][] = DBcondition('u.userid', $options['userids']);
		}

// users
		if(!is_null($options['users'])){
			zbx_value2array($options['users']);
			$sql_parts['where'][] = DBcondition('u.alias', $options['users'], false, true);
		}

// type
		if(!is_null($options['status'])){
			$sql_parts['where'][] = 'g.users_status='.$options['status'];
		}
// status
		if(!is_null($options['status'])){
			$sql_parts['where'][] = 'g.users_status='.$options['status'];
		}

// with_gui_access
		if(!is_null($options['with_gui_access'])){
			$sql_parts['where'][] = 'g.gui_access='.GROUP_GUI_ACCESS_ENABLED;
		}
// with_api_access
		if(!is_null($options['with_api_access'])){
			$sql_parts['where'][] = 'g.api_access='.GROUP_API_ACCESS_ENABLED;
		}

// extendoutput
		if(!is_null($options['extendoutput'])){
			$sql_parts['select']['users'] = 'u.*';
		}

// count
		if(!is_null($options['count'])){
			$options['sortfield'] = '';

			$sql_parts['select'] = array('count(u.userid) as rowscount');
		}

// pattern
		if(!zbx_empty($options['pattern'])){
			$sql_parts['where'][] = ' UPPER(u.alias) LIKE '.zbx_dbstr('%'.strtoupper($options['pattern']).'%');
		}

// order
// restrict not allowed columns for sorting
		$options['sortfield'] = str_in_array($options['sortfield'], $sort_columns) ? $options['sortfield'] : '';
		if(!zbx_empty($options['sortfield'])){
			$sortorder = ($options['sortorder'] == ZBX_SORT_DOWN)?ZBX_SORT_DOWN:ZBX_SORT_UP;

			$sql_parts['order'][] = 'u.'.$options['sortfield'].' '.$sortorder;

			if(!str_in_array('u.'.$options['sortfield'], $sql_parts['select']) && !str_in_array('u.*', $sql_parts['select'])){
				$sql_parts['select'][] = 'u.'.$options['sortfield'];
			}
		}

// limit
		if(zbx_ctype_digit($options['limit']) && $options['limit']){
			$sql_parts['limit'] = $options['limit'];
		}
//-------
		$userids = array();

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

		$sql = 'SELECT '.$sql_select.'
				FROM '.$sql_from.'
				WHERE '.DBin_node('u.userid', $nodeids).
				$sql_where.
				$sql_order;
		$res = DBselect($sql, $sql_limit);
		while($user = DBfetch($res)){
			if($options['count'])
				$result = $user;
			else{
				$userids[$user['userid']] = $user['userid'];

				if(is_null($options['extendoutput'])){
					$result[$user['userid']] = $user['userid'];
				}
				else{
					if(!isset($result[$user['userid']])) $result[$user['userid']]= array();

					if($options['select_usrgrps'] && !isset($result[$user['userid']]['usrgrpids'])){
						$result[$user['userid']]['usrgrpids'] = array();
						$result[$user['userid']]['usrgrps'] = array();
					}

					// usrgrpids
					if(isset($user['usrgrpid'])){
						if(!isset($result[$user['userid']]['usrgrpids']))
							$result[$user['userid']]['usrgrpids'] = array();

						$result[$user['userid']]['usrgrpids'][$user['usrgrpid']] = $user['usrgrpid'];
						unset($user['usrgrpid']);
					}

					$result[$user['userid']] += $user;
				}
			}
		}

		if(is_null($options['extendoutput']) || !is_null($options['count'])){
			if(is_null($options['preservekeys'])) $result = zbx_cleanHashes($result);
			return $result;
		}

// Adding Objects
		if($options['get_access'] != 0){
			foreach($result as $userid => $user){
				$result[$userid] += array('api_access' => 0, 'gui_access' => 0, 'debug_mode' => 0, 'users_status' => 0);
			}

			$sql = 'SELECT ug.userid, MAX(g.api_access) as api_access,  MAX(g.gui_access) as gui_access,
						MAX(g.debug_mode) as debug_mode, MAX(g.users_status) as users_status'.
					' FROM usrgrp g, users_groups ug '.
					' WHERE '.DBcondition('ug.userid', $userids).
						' AND g.usrgrpid=ug.usrgrpid '.
					' GROUP BY ug.userid';
			$access = DBselect($sql);
			while($useracc = DBfetch($access)){
				$result[$useracc['userid']] = zbx_array_merge($result[$useracc['userid']], $useracc);
			}
		}
// Adding Objects
// Adding usergroups
		if($options['select_usrgrps']){
			$obj_params = array('extendoutput' => 1, 'userids' => $userids, 'preservekeys' => 1);
			$usrgrps = CUserGroup::get($obj_params);
			foreach($usrgrps as $usrgrpid => $usrgrp){
				foreach($usrgrp['userids'] as $num => $userid){
					$result[$userid]['usrgrpids'][$usrgrpid] = $usrgrpid;
					$result[$userid]['usrgrps'][$usrgrpid] = $usrgrp;
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
 * Authenticate user
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param _array $user
 * @param array $user['user'] User alias
 * @param array $user['password'] User password
 * @return string session ID
 */
	public static function authenticate($user){

		$login = user_login($user['user'], $user['password'], ZBX_AUTH_INTERNAL);

		if($login){
			self::checkAuth($login);
			return $login;
		}
		else{
			self::$error[] = array('error' => ZBX_API_ERROR_PARAMETERS, 'data' => $_REQUEST['message']);
			return false;
		}
	}

/**
 * Check if session ID is authenticated
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param _array $session
 * @param array $session['sessionid'] Session ID
 * @return boolean
 */
	public static function checkAuth($session){
		return check_authentication($session['sessionid']);
	}

/**
 * Get User ID by User alias
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param _array $user_data
 * @param array $user_data['alias'] User alias
 * @return string|boolean
 */
	public static function getObjects($user_data){
		$result = array();
		$userids = array();

		$sql = 'SELECT u.userid '.
				' FROM users u '.
				' WHERE u.alias='.zbx_dbstr($user_data['alias']).
					' AND '.DBin_node('u.userid', false);
		$res = DBselect($sql);
		while($user = DBfetch($res)){
			$userids[$user['userid']] = $user['userid'];
		}
		
		if(!empty($userids))
			$result = self::get(array('userids'=>$userids, 'extendoutput'=>1));

	return $result;
	}

/**
 * Add Users
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param _array $users multidimensional array with Users data
 * @param string $users['name']
 * @param string $users['surname']
 * @param array $users['alias']
 * @param string $users['passwd']
 * @param string $users['url']
 * @param int $users['autologin']
 * @param int $users['autologout']
 * @param string $users['lang']
 * @param string $users['theme']
 * @param int $users['refresh']
 * @param int $users['rows_per_page']
 * @param int $users['type']
 * @param array $users['user_medias']
 * @param string $users['user_medias']['mediatypeid']
 * @param string $users['user_medias']['address']
 * @param int $users['user_medias']['severity']
 * @param int $users['user_medias']['active']
 * @param string $users['user_medias']['period']
 * @return array|boolean
 */
	public static function add($users){
		global $USER_DETAILS;
		if(USER_TYPE_SUPER_ADMIN != $USER_DETAILS['type']){
			self::setError(__METHOD__, ZBX_API_ERROR_PERMISSIONS, 'Only Super Admins can create Users');
			return false;
		}

		$users = zbx_toArray($users);
		$userids = array();
		
		$error = 'Unknown ZABBIX internal error';
		$result = false;
		
		self::BeginTransaction(__METHOD__);
		foreach($users as $unum => $user){
// copy from frontend {
			$sql = 'SELECT * '.
					' FROM users '.
					' WHERE alias='.zbx_dbstr($user['alias']).
						' AND '.DBin_node('userid', false);
			if(DBfetch(DBselect($sql))){
				$error = 'User [ '.$user['alias'].' ] already exists';
				$result = false;
				break;
			}

			$user_db_fields = array(
				'name' => 'ZABBIX',
				'surname' => 'USER',
				'alias' => null,
				'passwd' => 'zabbix',
				'url' => '',
				'autologin' => 0,
				'autologout' => 900,
				'lang' => 'en_gb',
				'theme' => 'default.css',
				'refresh' => 30,
				'rows_per_page' => 50,
				'type' => USER_TYPE_ZABBIX_USER,
				'user_groups' => array(),
				'user_medias' => array(),
			);

			if(!check_db_fields($user_db_fields, $user)){
				$error = 'Incorrect parameters pasted to method [ user.add ]';
				$result = false;
				break;
			}

			$userid = get_dbid('users', 'userid');

			$result = DBexecute('INSERT INTO users (userid,name,surname,alias,passwd,url,autologin,autologout,lang,theme,refresh,rows_per_page,type) VALUES ('.
				$userid.','.
				zbx_dbstr($user['name']).','.
				zbx_dbstr($user['surname']).','.
				zbx_dbstr($user['alias']).','.
				zbx_dbstr(md5($user['passwd'])).','.
				zbx_dbstr($user['url']).','.
				$user['autologin'].','.
				$user['autologout'].','.
				zbx_dbstr($user['lang']).','.
				zbx_dbstr($user['theme']).','.
				$user['refresh'].','.
				$user['rows_per_page'].','.
				$user['type'].
				')');

			if($result){
	//			$result = DBexecute('DELETE FROM users_groups WHERE userid='.$userid);
				foreach($user['user_groups'] as $groupid){
					if(!$result) break;
					$users_groups_id = get_dbid("users_groups","id");
					$result = DBexecute('INSERT INTO users_groups (id,usrgrpid,userid)'.
						'values('.$users_groups_id.','.$groupid.','.$userid.')');
				}
			}

			if($result){
	//			$result = DBexecute('DELETE FROM media WHERE userid='.$userid);
				foreach($user['user_medias'] as $media_data){
					if(!$result) break;
					$mediaid = get_dbid("media","mediaid");
					$result = DBexecute('INSERT INTO media (mediaid,userid,mediatypeid,sendto,active,severity,period)'.
						' VALUES ('.$mediaid.','.$userid.','.$media_data['mediatypeid'].','.
						zbx_dbstr($media_data['sendto']).','.$media_data['active'].','.$media_data['severity'].','.
						zbx_dbstr($media_data['period']).')');
				}
			}

// } copy from frontend
			if(!$result) break;
			
			$userids[] = $userid;
		}
		$result = self::EndTransaction($result, __METHOD__);

		if($result){
			$upd_users = self::get(array('userids'=>$userids, 'extendoutput'=>1, 'nopermissions'=>1));
			return $upd_users;
		}
		else{
			self::$error[] = array('error' => ZBX_API_ERROR_INTERNAL, 'data' => $error);
			return false;
		}
	}

/**
 * Update Users
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param _array $users multidimensional array with Users data
 * @param string $users['userid']
 * @param string $users['name']
 * @param string $users['surname']
 * @param array $users['alias']
 * @param string $users['passwd']
 * @param string $users['url']
 * @param int $users['autologin']
 * @param int $users['autologout']
 * @param string $users['lang']
 * @param string $users['theme']
 * @param int $users['refresh']
 * @param int $users['rows_per_page']
 * @param int $users['type']
 * @param array $users['user_medias']
 * @param string $users['user_medias']['mediatypeid']
 * @param string $users['user_medias']['address']
 * @param int $users['user_medias']['severity']
 * @param int $users['user_medias']['active']
 * @param string $users['user_medias']['period']
 * @return boolean
 */
	public static function update($users){
		global $USER_DETAILS;
		if(USER_TYPE_SUPER_ADMIN != $USER_DETAILS['type']){
			self::setError(__METHOD__, ZBX_API_ERROR_PERMISSIONS, 'Only Super Admins can update Users');
			return false;
		}

		$users = zbx_toArray($users);
		$userids = array();
		$result = false;
		
		$upd_users = self::get(array('userids'=>zbx_objectValues($users, 'userid'), 
											'extendoutput'=>1, 
											'preservekeys'=>1));
		foreach($users as $gnum => $user){
			if($user['alias'] != $upd_users[$user['userid']]['alias']){
				self::setError(__METHOD__, ZBX_API_ERROR_PARAMETERS, 'Cannot update user alias'.'[ '.$user['alias'].' ]');
				$result = false;
			}
			
			$userids[] = $user['userid'];
			//add_audit(AUDIT_ACTION_DELETE, AUDIT_RESOURCE_USER, 'User ['.$user['alias'].']');
		}

		$error = 'Unknown ZABBIX internal error';

		self::BeginTransaction(__METHOD__);
		foreach($users as $unum => $user){
			$user_db_fields = $upd_users[$user['userid']];

			if(!check_db_fields($user_db_fields, $user)){
				self::setError(__METHOD__, ZBX_API_ERROR_PARAMETERS, 'Incorrect arguments pasted to function [CUser::update]');
				$result = false;
				break;
			}

// copy from frontend {
			$result = true;

			$sql = 'SELECT userid '.
					' FROM users '.
					' WHERE alias='.zbx_dbstr($user['alias']).
						' AND '.DBin_node('userid', id2nodeid($user['userid']));
			$db_user = DBfetch(DBselect($sql));
			if($db_user['userid'] != $user['userid']){
				$error = 'User [ '.$user['alias'].' ] already exists';
				$result = false;
				break;
			}

			if(isset($user['passwd'])) {
				$user['passwd'] = md5($user['passwd']);
			}

			$sql = 'UPDATE users SET '.
					' name='.zbx_dbstr($user['name']).
					' ,surname='.zbx_dbstr($user['surname']).
					' ,alias='.zbx_dbstr($user['alias']).
					' ,passwd='.zbx_dbstr($user['passwd']).
					' ,url='.zbx_dbstr($user['url']).
					' ,autologin='.$user['autologin'].
					' ,autologout='.$user['autologout'].
					' ,lang='.zbx_dbstr($user['lang']).
					' ,theme='.zbx_dbstr($user['theme']).
					' ,refresh='.$user['refresh'].
					' ,rows_per_page='.$user['rows_per_page'].
					' ,type='.$user['type'].
					' WHERE userid='.$userid;
			$result = DBexecute($sql);

			if($result && !is_null($user['user_groups'])){
				DBexecute('DELETE FROM users_groups WHERE userid='.$userid);

				$user['user_groups'] = CUserGroup::get(array('usrgrpids' => $user['user_groups'], 'extendoutput' => 1));
				foreach($user['user_groups'] as $groupid => $group){
					if(!$result) break;

					if($group['gui_access'] == GROUP_GUI_ACCESS_DISABLED){
						$error = 'User cannot restrict access to GUI to him self. Group "'.$group['name'].'"';
						$result = false;
						continue;
					}

					if($group['users_status'] == GROUP_STATUS_DISABLED){
						$error = 'User cannot disable him self. Group "'.$group['name'].'"';
						$result = false;
						continue;
					}

					$users_groups_id = get_dbid('users_groups', 'id');
					$result = DBexecute('INSERT INTO users_groups (id, usrgrpid, userid) VALUES ('.$users_groups_id.','.$groupid.','.$userid.')');
				}
			}

			if($result && !is_null($user['user_medias'])){
				$result = DBexecute('DELETE FROM media WHERE userid='.$userid);
				foreach($user['user_medias'] as $media_data){
					if(!$result) break;
					$mediaid = get_dbid('media', 'mediaid');
					$result = DBexecute('INSERT INTO media (mediaid, userid, mediatypeid, sendto, active, severity, period)'.
						' VALUES ('.$mediaid.','.$userid.','.$media_data['mediatypeid'].','.
							zbx_dbstr($media_data['sendto']).','.$media_data['active'].','.$media_data['severity'].','.
							zbx_dbstr($media_data['period']).')');
				}
			}

// } copy from frontend
			if(!$result) break;
		}

		$result = self::EndTransaction($result, __METHOD__);

		if($result){
			$upd_users = self::get(array('userids'=>$userids, 'extendoutput'=>1, 'nopermissions'=>1));
			return $upd_users;
		}
		else{
			self::$error[] = array('error' => ZBX_API_ERROR_INTERNAL, 'data' => $error);
			return false;
		}
	}


/**
 * Delete Users
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param array $users
 * @param array $users[0,...]['userids']
 * @return boolean
 */
	public static function delete($users){
		global $USER_DETAILS;
		if(USER_TYPE_SUPER_ADMIN != $USER_DETAILS['type']){
			self::setError(__METHOD__, ZBX_API_ERROR_PERMISSIONS, 'Only Super Admins can delete Users');
			return false;
		}

		$users = zbx_toArray($users);
		$userids = array();
		$result = false;

		$del_users = self::get(array('userids'=>zbx_objectValues($users, 'userid'), 
											'extendoutput'=>1, 
											'preservekeys'=>1));
		foreach($users as $gnum => $user){
			if(bccomp($USER_DETAILS['userid'],$user['userid']) == 0){
				self::setError(__METHOD__, ZBX_API_ERROR_PARAMETERS, S_USER_CANNOT_DELETE_ITSELF);
				$result = false;
			}

			if($del_users[$user['userid']]['alias'] == ZBX_GUEST_USER){
				self::setError(__METHOD__, ZBX_API_ERROR_PARAMETERS, S_CANNOT_DELETE_USER.'[ '.ZBX_GUEST_USER.' ]');
				$result = false;
			}
			
			$userids[] = $user['userid'];
			//add_audit(AUDIT_ACTION_DELETE, AUDIT_RESOURCE_USER, 'User ['.$user['alias'].']');
		}
		
		self::BeginTransaction(__METHOD__);
		if(empty($userids)){
			$result = DBexecute('DELETE FROM operations WHERE '.OPERATION_OBJECT_USER.' AND '.DBcondition('objectid', $userids));
			$result = DBexecute('DELETE FROM media WHERE '.DBcondition('userid', $userids));
			$result = DBexecute('DELETE FROM profiles WHERE '.DBcondition('userid', $userids));
			$result = DBexecute('DELETE FROM users_groups WHERE '.DBcondition('userid', $userids));
			$result = DBexecute('DELETE FROM users WHERE '.DBcondition('userid', $userids));
		}
		
		$result = self::EndTransaction($result, __METHOD__);

		if($result){
			return zbx_cleanHashes($del_users);
		}
		else{
			self::setError(__METHOD__);
			return false;
		}
	}

/**
 * Add Medias for User
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param array $media_data
 * @param string $media_data['userid']
 * @param string $media_data['medias']['mediatypeid']
 * @param string $media_data['medias']['address']
 * @param int $media_data['medias']['severity']
 * @param int $media_data['medias']['active']
 * @param string $media_data['medias']['period']
 * @return boolean
 */
	public static function addMedia($media_data){
		$result = true;
		$mediaids = array();
		$userid = $media_data['userid'];

		foreach($media_data['medias'] as $media){
			$result = add_media( $userid, $media['mediatypeid'], $media['sendto'], $media['severity'], $media['active'], $media['period']);
			if(!$result) break;
			$mediaids[$result] = $result;
		}

		if($result){
			return $mediaids;
		}
		else{
			self::$error[] = array('error' => ZBX_API_ERROR_INTERNAL, 'data' => 'Internal zabbix error');
			return false;
		}
	}

/**
 * Delete User Medias
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param array $media_data
 * @param string $media_data['userid']
 * @param array $media_data['mediaids']
 * @return boolean
 */
	public static function deleteMedia($media_data){
		$sql = 'DELETE FROM media WHERE userid='.$media_data['userid'].' AND '.DBcondition('mediaid', $media_data['mediaids']);
		$result = DBexecute($sql);

		if($result){
			return true;
		}
		else{
			self::$error[] = array('error' => ZBX_API_ERROR_INTERNAL, 'data' => 'Internal zabbix error');
			return false;
		}
	}

/**
 * Update Medias for User
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param array $media_data
 * @param string $media_data['userid']
 * @param array $media_data['medias']
 * @param array $media_data['medias']['mediaid']
 * @param string $media_data['medias']['mediatypeid']
 * @param string $media_data['medias']['sendto']
 * @param int $media_data['medias']['severity']
 * @param int $media_data['medias']['active']
 * @param string $media_data['medias']['period']
 * @return boolean
 */
	public static function updateMedia($media_data){
		$result = false;
		$userid = $media_data['userid'];

		foreach($media_data['medias'] as $media){
			$result = update_media($media['mediaid'], $userid, $media['mediatypeid'], $media['sendto'], $media['severity'], $media['active'], $media['period']);
			if(!$result) break;
		}

		if($result){
			return true;
		}
		else{
			self::$error[] = array('error' => ZBX_API_ERROR_INTERNAL, 'data' => 'Internal zabbix error');
			return false;
		}

	}

}
?>