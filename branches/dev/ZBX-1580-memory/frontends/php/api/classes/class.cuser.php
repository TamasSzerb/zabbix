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
 * @param boolean $options['select_usrgrps'] extend with UserGroups data for each User
 * @param boolean $options['get_access'] extend with access data for each User
 * @param boolean $options['extendoutput'] output only User IDs if not set.
 * @param boolean $options['count'] output only count of objects in result. ( result returned in property 'rowscount' )
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
		$subselects_allowed_outputs = array(API_OUTPUT_REFER, API_OUTPUT_EXTEND); // allowed output options for [ select_* ] params


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
// filter
			'pattern'					=> '',
// OutPut
			'extendoutput'				=> null,
			'output'				=> API_OUTPUT_REFER,
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

		
		if(!is_null($options['extendoutput'])){
			$options['output'] = API_OUTPUT_EXTEND;
			
			if(!is_null($options['select_usrgrps'])){
				$options['select_usrgrps'] = API_OUTPUT_EXTEND;
			}
		}
		
		
// PERMISSION CHECK
		if(USER_TYPE_SUPER_ADMIN == $user_type){

		}
		else if(!is_null($options['editable']) && ($USER_DETAILS['type']!=USER_TYPE_SUPER_ADMIN)){
			return array();
		}

// nodeids
		$nodeids = $options['nodeids'] ? $options['nodeids'] : get_current_nodeid(false);

// usrgrpids
		if(!is_null($options['usrgrpids'])){
			zbx_value2array($options['usrgrpids']);
			if($options['output'] != API_OUTPUT_SHORTEN){
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
		if(!is_null($options['type'])){
			$sql_parts['where'][] = 'u.type='.$options['type'];
		}


// extendoutput
		if($options['output'] == API_OUTPUT_EXTEND){
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

				if($options['output'] == API_OUTPUT_SHORTEN){
					$result[$user['userid']] = array('userid' => $user['userid']);
				}
				else{
					if(!isset($result[$user['userid']])) $result[$user['userid']]= array();

					if($options['select_usrgrps'] && !isset($result[$user['userid']]['usrgrps'])){
						$result[$user['userid']]['usrgrps'] = array();
					}

// usrgrpids
					if(isset($user['usrgrpid'])){
						if(!isset($result[$user['userid']]['usrgrps']))
							$result[$user['userid']]['usrgrps'] = array();

						$result[$user['userid']]['usrgrps'][$user['usrgrpid']] = array('usrgrpid' => $user['usrgrpid']);
						unset($user['usrgrpid']);
					}

					$result[$user['userid']] += $user;
				}
			}
		}

		if(($options['output'] != API_OUTPUT_EXTEND) || !is_null($options['count'])){
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
		if(!is_null($options['select_usrgrps']) && str_in_array($options['select_usrgrps'], $subselects_allowed_outputs)){
			$obj_params = array(
				'output' => $options['select_usrgrps'],
				'userids' => $userids,
				'preservekeys' => 1
			);
			$usrgrps = CUserGroup::get($obj_params);
			foreach($usrgrps as $usrgrpid => $usrgrp){
				$uusers = $usrgrp['users'];
				unset($usrgrp['users']);
				foreach($uusers as $num => $user){
					$result[$user['userid']]['usrgrps'][$usrgrpid] = $usrgrp;
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
			$userids[] = $user['userid'];
		}

		if(!empty($userids))
			$result = self::get(array('userids' => $userids, 'extendoutput' => 1));

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
		$result = false;
		$errors = array();

		if(USER_TYPE_SUPER_ADMIN != $USER_DETAILS['type']){
			self::setError(__METHOD__, ZBX_API_ERROR_PERMISSIONS, 'Only Super Admins can create Users');
			return false;
		}

		$users = zbx_toArray($users);
		$userids = array();

		self::BeginTransaction(__METHOD__);
		foreach($users as $unum => $user){

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
				'user_medias' => array(),
			);
			if(!check_db_fields($user_db_fields, $user)){
				$errors[] = array('errno' => ZBX_API_ERROR_PARAMETERS, 'error' => 'Wrong fields for user');
				$result = false;
				break;
			}


			$user_exist = self::getObjects(array('alias' => $user['alias']));
			if(!empty($user_exist)){
				$errors[] = array('errno' => ZBX_API_ERROR_PARAMETERS, 'error' => 'User [ '.$user_exist[0]['alias'].' ] already exists');
				$result = false;
				break;
			}

			$userid = get_dbid('users', 'userid');
			$result = DBexecute('INSERT INTO users (userid, name, surname, alias, passwd, url, autologin, autologout, lang, theme,
				refresh, rows_per_page, type) '.
				' VALUES ('.
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
				$usrgrps = zbx_objectValues($user['usrgrps'], 'usrgrpid');
				foreach($usrgrps as $groupid){
					if(!$result) break;
					$users_groups_id = get_dbid("users_groups","id");
					$result = DBexecute('INSERT INTO users_groups (id,usrgrpid,userid)'.
						'values('.$users_groups_id.','.$groupid.','.$userid.')');
				}
			}

			if($result){
				foreach($user['user_medias'] as $media_data){
					if(!$result) break;
					$mediaid = get_dbid('media', 'mediaid');
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
			$upd_users = self::get(array('userids' => $userids, 'extendoutput' => 1));
			return $upd_users;
		}
		else{
			self::setMethodErrors(__METHOD__, $errors);
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
		$errors = array();
		$result = true;
		$self = false;

		if(USER_TYPE_SUPER_ADMIN != $USER_DETAILS['type']){
			self::setError(__METHOD__, ZBX_API_ERROR_PERMISSIONS, 'Only Super Admins can update Users');
			return false;
		}

		$users = zbx_toArray($users);
		$userids = zbx_objectValues($users, 'userid');

		$upd_users = self::get(array(
			'userids' => zbx_objectValues($users, 'userid'),
			'extendoutput' => 1,
			'preservekeys' => 1));
		foreach($users as $gnum => $user){
			//add_audit(AUDIT_ACTION_DELETE, AUDIT_RESOURCE_USER, 'User ['.$user['alias'].']');
		}

		self::BeginTransaction(__METHOD__);

		if(bccomp($USER_DETAILS['userid'], $user['userid']) == 0){
			$self = true;
		}

		foreach($users as $unum => $user){
			$user_db_fields = $upd_users[$user['userid']];

// unset if not changed passwd
			if(isset($user['passwd']) && !is_null($user['passwd'])){
				$user['passwd'] = md5($user['passwd']);
			}
			else{
				unset($user['passwd']);
			}
//---------

			if(!check_db_fields($user_db_fields, $user)){
				$errors[] = array('errno' => ZBX_API_ERROR_PARAMETERS, 'error' => 'Wrong fields for user');
				$result = false;
				break;
			}

// copy from frontend {
			$sql = 'SELECT userid '.
					' FROM users '.
					' WHERE alias='.zbx_dbstr($user['alias']).
						' AND '.DBin_node('userid', id2nodeid($user['userid']));
			$db_user = DBfetch(DBselect($sql));
			if($db_user && ($db_user['userid'] != $user['userid'])){
				$errors[] = array('errno' => ZBX_API_ERROR_PARAMETERS, 'error' => 'User ['.$user['alias'].'] already exists');
				$result = false;
				break;
			}

			$sql = 'UPDATE users SET '.
						' name='.zbx_dbstr($user['name']).', '.
						' surname='.zbx_dbstr($user['surname']).', '.
						' alias='.zbx_dbstr($user['alias']).', '.
						' passwd='.zbx_dbstr($user['passwd']).', '.
						' url='.zbx_dbstr($user['url']).', '.
						' autologin='.$user['autologin'].', '.
						' autologout='.$user['autologout'].', '.
						' lang='.zbx_dbstr($user['lang']).', '.
						' theme='.zbx_dbstr($user['theme']).', '.
						' refresh='.$user['refresh'].', '.
						' rows_per_page='.$user['rows_per_page'].', '.
						' type='.$user['type'].
					' WHERE userid='.$user['userid'];

			$result = DBexecute($sql);

			// if(isset($user['usrgrps']) && !is_null($user['usrgrps'])){
				// $user_groups = CHostGroup::get(array('userids' => $user['userid']));
				// $user_groupids = zbx_objectValues($user_groups, 'usrgrpid');
				// $new_groupids = zbx_objectValues($user['usrgrps'], 'usrgrpid');

				// $groups_to_add = array_diff($new_groupids, $user_groupids);

				// if(!empty($groups_to_add)){
					// $result &= self::massAdd(array('users' => $user, 'usrgrps' => $groups_to_add));
				// }

				// $groups_to_del = array_diff($user_groupids, $new_groupids);
				// if(!empty($groups_to_del)){
					// $result &= self::massRemove(array('users' => $user, 'usrgrps' => $groups_to_del));
				// }
			// }



			if($result && isset($user['usrgrps']) && !is_null($user['usrgrps'])){
				DBexecute('DELETE FROM users_groups WHERE userid='.$user['userid']);

				$usrgrps = CUserGroup::get(array(
					'usrgrpids' => zbx_objectValues($user['usrgrps'], 'usrgrpid'),
					'extendoutput' => 1,
					'preservekeys' => 1));

				foreach($usrgrps as $groupid => $group){
					if(!$result) break;

					if(($group['gui_access'] == GROUP_GUI_ACCESS_DISABLED) && $self){
						$errors[] = array('errno' => ZBX_API_ERROR_PARAMETERS, 'error' => 'User cannot restrict access to GUI to him self. Group "'.$group['name'].'"');
						$result = false;
						break;
					}

					if(($group['users_status'] == GROUP_STATUS_DISABLED) && $self){
						$errors[] = array('errno' => ZBX_API_ERROR_PARAMETERS, 'error' => 'User cannot disable him self. Group "'.$group['name'].'"');
						$result = false;
						break;
					}

					$users_groups_id = get_dbid('users_groups', 'id');
					$result = DBexecute('INSERT INTO users_groups (id, usrgrpid, userid) VALUES ('.$users_groups_id.','.$groupid.','.$user['userid'].')');
				}
			}
/*
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
//*/
// } copy from frontend
		}

		$result = self::EndTransaction($result, __METHOD__);

		if($result){
			$upd_users = self::get(array('userids' => $userids, 'extendoutput' => 1, 'nopermissions' => 1));
			return $upd_users;
		}
		else{
			self::setMethodErrors(__METHOD__, $errors);
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
	public static function updateProfile($user){
		global $USER_DETAILS;
		$errors = array();
		$result = true;

		$upd_users = self::get(array(
			'userids' => $USER_DETAILS['userid'],
			'extendoutput' => 1,
			'preservekeys' => 1));

		$upd_user = reset($upd_users);
		//add_audit(AUDIT_ACTION_DELETE, AUDIT_RESOURCE_USER, 'User ['.$user['alias'].']');

		self::BeginTransaction(__METHOD__);


		$user_db_fields = $upd_user;

// unset if not changed passwd
		if(isset($user['passwd']) && !is_null($user['passwd'])){
			$user['passwd'] = md5($user['passwd']);
		}
		else{
			unset($user['passwd']);
		}
//---------

		if(!check_db_fields($user_db_fields, $user)){
			$errors[] = array('errno' => ZBX_API_ERROR_PARAMETERS, 'error' => 'Wrong fields for user');
			$result = false;
			break;
		}

// copy from frontend {

		$sql = 'UPDATE users SET '.
					' passwd='.zbx_dbstr($user['passwd']).', '.
					' url='.zbx_dbstr($user['url']).', '.
					' autologin='.$user['autologin'].', '.
					' autologout='.$user['autologout'].', '.
					' lang='.zbx_dbstr($user['lang']).', '.
					' theme='.zbx_dbstr($user['theme']).', '.
					' refresh='.$user['refresh'].', '.
					' rows_per_page='.$user['rows_per_page'].
				' WHERE userid='.$user['userid'];

		$result = DBexecute($sql);


		$result = self::EndTransaction($result, __METHOD__);

		if($result){
			$upd_users = self::get(array('userids' => $USER_DETAILS['userid'], 'extendoutput' => 1, 'nopermissions' => 1));
			return $upd_users;
		}
		else{
			self::setMethodErrors(__METHOD__, $errors);
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
		$errors = array();

		if(USER_TYPE_SUPER_ADMIN != $USER_DETAILS['type']){
			self::setError(__METHOD__, ZBX_API_ERROR_PERMISSIONS, 'Only Super Admins can delete Users');
			return false;
		}

		$users = zbx_toArray($users);
		$userids = array();
		$result = false;

		$del_users = self::get(array(
			'userids'=>zbx_objectValues($users, 'userid'),
			'extendoutput'=>1,
			'preservekeys'=>1));

		foreach($del_users as $gnum => $user){
			if(bccomp($USER_DETAILS['userid'], $user['userid']) == 0){
				$errors[] = array('errno' => ZBX_API_ERROR_PARAMETERS, 'error' => S_USER_CANNOT_DELETE_ITSELF);
				$result = false;
			}

			if($del_users[$user['userid']]['alias'] == ZBX_GUEST_USER){
				$errors[] = array('errno' => ZBX_API_ERROR_PARAMETERS, 'error' => S_CANNOT_DELETE_USER.'[ '.ZBX_GUEST_USER.' ]');
				$result = false;
			}

			$userids[] = $user['userid'];
			//add_audit(AUDIT_ACTION_DELETE, AUDIT_RESOURCE_USER, 'User ['.$user['alias'].']');
		}

		self::BeginTransaction(__METHOD__);
		if(!empty($userids)){
			$result = DBexecute('DELETE FROM operations WHERE object='.OPERATION_OBJECT_USER.' AND '.DBcondition('objectid', $userids));
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
			self::setMethodErrors(__METHOD__, $errors);
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
 * @param array $media_data['medias']
 * @return boolean
 */
	public static function deleteMedia($media_data){
		$medias = zbx_toArray($media_data['medias']);
		$mediaids = zbx_objectValues($medias, 'mediaid');

		$sql = 'DELETE FROM media WHERE userid='.$media_data['userid'].' AND '.DBcondition('mediaid', $mediaids);
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
 * @param string $media_data['medias']['mediatypeid']
 * @param string $media_data['medias']['sendto']
 * @param int $media_data['medias']['severity']
 * @param int $media_data['medias']['active']
 * @param string $media_data['medias']['period']
 * @return boolean
 */
	public static function updateMedia($data){
		$errors = array();

		$result = false;
		$users = zbx_toArray($data['users']);
		$userids = zbx_objectValues($users, 'userid');

		$medias = zbx_toArray($data['medias']);

		self::BeginTransaction(__METHOD__);

		$result = DBexecute('DELETE FROM media WHERE '.DBcondition('userid', $userids));
		if($result){
			foreach($userids as $userid){
				foreach($medias as $media){

					if(!validate_period($media['period'])){
						$errors[] = array('errno' => ZBX_API_ERROR_PARAMETERS, 'error' => 'Wrong period ['.$media['period'].' ]');
						$result = false;
						break 2;
					}

					$mediaid = get_dbid('media', 'mediaid');
					$sql = 'INSERT INTO media (mediaid, userid, mediatypeid, sendto, active, severity, period)'.
							' VALUES ('.$mediaid.','.$userid.','.$media['mediatypeid'].','.
								zbx_dbstr($media['sendto']).','.$media['active'].','.$media['severity'].','.
								zbx_dbstr($media['period']).')';
					$result = DBexecute($sql);
					if(!$result){
						break 2;
					}
				}
			}
		}

		$result = self::EndTransaction($result, __METHOD__);

		if($result){
			return true;
		}
		else{
			self::setMethodErrors(__METHOD__, $errors);
			return false;
		}

	}

}
?>
