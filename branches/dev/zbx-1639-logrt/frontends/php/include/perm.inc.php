<?php
/*
** ZABBIX
** Copyright (C) 2000-2005 SIA Zabbix
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

function zbx_session_start($userid, $name, $password){
	$sessionid = md5(time().$password.$name.rand(0,10000000));
	zbx_setcookie('zbx_sessionid',$sessionid);

	DBexecute('INSERT INTO sessions (sessionid,userid,lastaccess,status) VALUES ('.zbx_dbstr($sessionid).','.$userid.','.time().','.ZBX_SESSION_ACTIVE.')');

return $sessionid;
}

function permission2str($group_permission){
	$str_perm[PERM_READ_WRITE]	= S_READ_WRITE;
	$str_perm[PERM_READ_ONLY]	= S_READ_ONLY;
	$str_perm[PERM_DENY]		= S_DENY;

	if(isset($str_perm[$group_permission]))
		return $str_perm[$group_permission];

	return S_UNKNOWN;
}

/*****************************************
	CHECK USER AUTHORISATION
*****************************************/
function user_login($name, $passwd, $auth_type){
	global $USER_DETAILS, $ZBX_LOCALNODEID;

	$password = md5($passwd);

	$sql = 'SELECT u.userid,u.attempt_failed, u.attempt_clock, u.attempt_ip '.
			' FROM users u '.
			' WHERE u.alias='.zbx_dbstr($name);

//SQL to BLOCK attempts
//					.' AND ( attempt_failed<'.ZBX_LOGIN_ATTEMPTS.
//							' OR (attempt_failed>'.(ZBX_LOGIN_ATTEMPTS-1).
//									' AND ('.time().'-attempt_clock)>'.ZBX_LOGIN_BLOCK.'))';

	$login = $attempt = DBfetch(DBselect($sql));

	if(($name!=ZBX_GUEST_USER) && zbx_empty($passwd)){
		$login = $attempt = false;
	}

	if($login){
		if(($login['attempt_failed'] >= ZBX_LOGIN_ATTEMPTS) && ((time() - $login['attempt_clock']) < ZBX_LOGIN_BLOCK)){
			$_REQUEST['message'] = 'Account is blocked for ' . (ZBX_LOGIN_BLOCK - (time() - $login['attempt_clock'])) .' seconds.';
			return false;
		}

		DBexecute('UPDATE users SET attempt_clock=' . time() . ' WHERE alias='.zbx_dbstr($name));

		switch(get_user_auth($login['userid'])){
			case GROUP_GUI_ACCESS_INTERNAL:
				$auth_type = ZBX_AUTH_INTERNAL;
				break;
			case GROUP_GUI_ACCESS_SYSTEM:
			case GROUP_GUI_ACCESS_DISABLED:
			default:
				break;
		}

		switch($auth_type){
			case ZBX_AUTH_LDAP:
				$login = ldap_authentication($name,$passwd);
				break;
			case ZBX_AUTH_HTTP:
				$login = true;
				break;
			case ZBX_AUTH_INTERNAL:
			default:
				$alt_auth = ZBX_AUTH_INTERNAL;
				$login = true;
		}
	}

	if($login){
		$sql = 'SELECT u.userid,u.alias,u.name,u.surname,u.url,u.refresh,u.passwd '.
					' FROM users u, users_groups ug, usrgrp g '.
					' WHERE u.alias='.zbx_dbstr($name).
						((ZBX_AUTH_INTERNAL==$auth_type)?' AND u.passwd='.zbx_dbstr($password):'').
						' AND '.DBin_node('u.userid', $ZBX_LOCALNODEID);

		$login = $user = DBfetch(DBselect($sql));
	}

/* update internal pass if it's different
	if($login && ($row['passwd']!=$password) && (ZBX_AUTH_INTERNAL!=$auth_type)){
		DBexecute('UPDATE users SET passwd='.zbx_dbstr($password).' WHERE userid='.$row['userid']);
	}
*/
	if($login){
		$login = (check_perm2login($user['userid']) && check_perm2system($user['userid']));
	}

	if($login){
		$sessionid = zbx_session_start($user['userid'], $name, $password);

		add_audit(AUDIT_ACTION_LOGIN,AUDIT_RESOURCE_USER,'Correct login ['.$name.']');

		if(empty($user['url'])){
			$user['url'] = get_profile('web.menu.view.last','index.php');
		}


		$USER_DETAILS = $user;
		$login = $sessionid;
	}
	else{
		$user = NULL;

		$_REQUEST['message'] = 'Login name or password is incorrect';
		add_audit(AUDIT_ACTION_LOGIN,AUDIT_RESOURCE_USER,'Login failed ['.$name.']');

		if($attempt){
			$ip = (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']))?$_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR'];
			$attempt['attempt_failed']++;
			$sql = 'UPDATE users SET attempt_failed='.$attempt['attempt_failed'].
									', attempt_clock='.time().
									', attempt_ip='.zbx_dbstr($ip).
								' WHERE userid='.$attempt['userid'];
			DBexecute($sql);
		}
	}

return $login;
}

function check_authorisation(){
	$sessionid = get_cookie('zbx_sessionid');

	if(!$auth = check_authentication($sessionid)){
		include('index.php');
		exit();
	}
return $auth;
}

function check_authentication($sessionid=null){
	global	$DB;
	global	$page;
	global	$PHP_AUTH_USER,$PHP_AUTH_PW;
	global	$USER_DETAILS;
	global	$ZBX_LOCALNODEID;
	global	$ZBX_NODES;

	$USER_DETAILS = NULL;
	$login = FALSE;

	if(!is_null($sessionid)){
		$sql = 'SELECT u.*,s.* '.
				' FROM sessions s,users u'.
				' WHERE s.sessionid='.zbx_dbstr($sessionid).
					' AND s.status='.ZBX_SESSION_ACTIVE.
					' AND s.userid=u.userid'.
					' AND ((s.lastaccess+u.autologout>'.time().') OR (u.autologout=0))'.
					' AND '.DBin_node('u.userid', $ZBX_LOCALNODEID);

		$login = $USER_DETAILS = DBfetch(DBselect($sql));

		if(!$USER_DETAILS){
			$incorrect_session = true;
		}
		else if($login['attempt_failed']){
			error(new CJSscript(array(
						bold($login['attempt_failed']),
						' failed login attempts logged. Last failed attempt was from ',
						bold($login['attempt_ip']),
						' on ',
						bold(date('d.m.Y H:i',$login['attempt_clock'])),
						'.')));

			DBexecute('UPDATE users SET attempt_failed=0 WHERE userid='.$login['userid']);
		}
	}

	if(!$USER_DETAILS && !isset($_SERVER['PHP_AUTH_USER'])){
		$sql = 'SELECT u.* '.
				' FROM users u '.
				' WHERE u.alias='.zbx_dbstr(ZBX_GUEST_USER).
					' AND '.DBin_node('u.userid', $ZBX_LOCALNODEID);
		$login = $USER_DETAILS = DBfetch(DBselect($sql));
		if(!$USER_DETAILS){
			$missed_user_guest = true;
		}
		else{
			$sessionid = zbx_session_start($USER_DETAILS['userid'], ZBX_GUEST_USER, '');
		}
	}

	if($login){
		$login = (check_perm2login($USER_DETAILS['userid']) && check_perm2system($USER_DETAILS['userid']));
	}

	if(!$login){
		$USER_DETAILS = NULL;
	}

	if($login && $sessionid && !isset($incorrect_session)){
		zbx_setcookie('zbx_sessionid',$sessionid,$USER_DETAILS['autologin']?(time()+86400*31):0);	//1 month
		DBexecute('UPDATE sessions SET lastaccess='.time().' WHERE sessionid='.zbx_dbstr($sessionid));
	}
	else{
		zbx_unsetcookie('zbx_sessionid');
		DBexecute('UPDATE sessions SET status='.ZBX_SESSION_PASSIVE.' WHERE sessionid='.zbx_dbstr($sessionid));
		unset($sessionid);
	}

	if($USER_DETAILS){
//		$USER_DETAILS['node'] = DBfetch(DBselect('SELECT * FROM nodes WHERE nodeid='.id2nodeid($USER_DETAILS['userid'])));
		if(isset($ZBX_NODES[$ZBX_LOCALNODEID])){
			$USER_DETAILS['node'] = $ZBX_NODES[$ZBX_LOCALNODEID];
		}
		else{
			$USER_DETAILS['node'] = array();
			$USER_DETAILS['node']['name'] = '- unknown -';
			$USER_DETAILS['node']['nodeid'] = $ZBX_LOCALNODEID;
		}

		$USER_DETAILS['debug_mode'] = get_user_debug_mode($USER_DETAILS['userid']);
	}
	else{
		$USER_DETAILS = array(
			'alias'	=>ZBX_GUEST_USER,
			'userid'=>0,
			'lang'	=>'en_gb',
			'type'	=>'0',
			'node'	=>array(
				'name'	=>'- unknown -',
				'nodeid'=>0));
	}

	$userip = (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']))?$_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR'];
	$USER_DETAILS['userip'] = $userip;

	if(!$login || isset($incorrect_session) || isset($missed_user_guest)){

		if(isset($incorrect_session))	$message = 'Session terminated, please re-login!';
		else if(isset($missed_user_guest)){
			$row = DBfetch(DBselect('SELECT count(u.userid) as user_cnt FROM users u'));
			if(!$row || $row['user_cnt'] == 0){
				$message = 'Table users is empty. Possible database corruption.';
			}
		}

		if(!isset($_REQUEST['message']) && isset($message)) $_REQUEST['message'] = $message;

	return false;
	}

return true;
}

/*****************************************
	LDAP AUTHENTICATION
*****************************************/
function ldap_authentication($user,$passwd,$cnf=NULL){
	if(is_null($cnf)){
		$config = select_config();
		foreach($config as $id => $value){
			if(strpos($id,'ldap_') !== false){
				$cnf[str_replace('ldap_','',$id)] = $config[$id];
			}
		}
	}

	if(!function_exists('ldap_connect')){
		info('Probably php-ldap module is missing.');
		return false;
	}

	$ldap = new CLdap($cnf);
	$ldap->connect();

	$result = $ldap->checkPass($user,$passwd);

return $result;
}


/***********************************************
	CHECK USER ACCESS TO SYSTEM STATUS
************************************************/
/* Function: check_perm2system()
 *
 * Description:
 * 		Checking user permissions to access system (affects server side: no notification will be sent)
 *
 * Comments:
 *		return true if permission is positive
 *
 * Author: Aly
 */
function  check_perm2system($userid){
	$sql = 'SELECT g.usrgrpid '.
		' FROM usrgrp g, users_groups ug '.
		' WHERE ug.userid = '.$userid.
			' AND g.usrgrpid = ug.usrgrpid '.
			' AND g.users_status = '.GROUP_STATUS_DISABLED;
	if($res = DBfetch(DBselect($sql,1))){
		return false;
	}
return true;
}

/* Function: check_perm2login()
 *
 * Description:
 * 		Checking user permissions to Login in frontend
 *
 * Comments:
 *		return true if permission is positive
 *
 * Author: Aly
 */

function check_perm2login($userid){
	$res = get_user_auth($userid);

return (GROUP_GUI_ACCESS_DISABLED == $res)?false:true;
}

/* Function: get_user_auth()
 *
 * Description:
 * 		Returns user authentication type
 *
 * Comments:
 *		default is SYSTEM auth
 *
 * Author: Aly
 */
function get_user_auth($userid){
	global $USER_DETAILS;

	if(($userid == $USER_DETAILS['userid']) && isset($USER_DETAILS['gui_access'])) return $USER_DETAILS['gui_access'];
	else $result = GROUP_GUI_ACCESS_SYSTEM;

	$sql = 'SELECT MAX(g.gui_access) as gui_access '.
		' FROM usrgrp g, users_groups ug '.
		' WHERE ug.userid='.$userid.
			' AND g.usrgrpid=ug.usrgrpid ';
	$acc = DBfetch(DBselect($sql));

	if(!zbx_empty($acc['gui_access'])){
		$result = $acc['gui_access'];
		$USER_DETAILS['gui_access'] = $acc['gui_access'];
	}

return $result;
}

function get_user_api_access($userid){
	$sql = 'SELECT g.usrgrpid '.
			' FROM usrgrp g, users_groups ug '.
			' WHERE ug.userid = '.$userid.
				' AND g.usrgrpid = ug.usrgrpid '.
				' AND g.api_access = '.GROUP_API_ACCESS_ENABLED;
	if($res = DBfetch(DBselect($sql,1))){
		return true;
	}
return false;
}

function get_user_debug_mode($userid){
	$sql = 'SELECT g.usrgrpid '.
			' FROM usrgrp g, users_groups ug '.
			' WHERE ug.userid = '.$userid.
				' AND g.usrgrpid = ug.usrgrpid '.
				' AND g.debug_mode = '.GROUP_DEBUG_MODE_ENABLED;
	if($res = DBfetch(DBselect($sql,1))){
		return true;
	}
return false;
}

/* Function: get_user_system_auth()
 *
 * Description:
 * 		Returns overal user authentication type in system
 *
 * Comments:
 *		default is INTERNAL auth
 *
 * Author: Aly
 */
function get_user_system_auth($userid){
	$result = ZBX_AUTH_INTERNAL;

	$user_auth = get_user_auth($userid);

	switch($user_auth){
		case GROUP_GUI_ACCESS_SYSTEM:
			$config = select_config();
			$result = $config['authentication_type'];
			break;
		case GROUP_GUI_ACCESS_INTERNAL:
		case GROUP_GUI_ACCESS_DISABLED:
		default:
			break;
	}

return $result;
}

/***********************************************
	GET ACCESSIBLE RESOURCES BY USERID
************************************************/

function available_groups($groupids, $editable=null){
	$options = array();
	$options['groupids'] = $groupids;
	$options['editable'] = $editable;

	$groups = CHostGroup::get($options);
return zbx_objectValues($groups, 'groupid');
}
function available_hosts($hostids, $editable=null){
	$options = array();
	$options['hostids'] = $hostids;
	$options['editable'] = $editable;
	$options['templated_hosts'] = 1;

	$hosts = CHost::get($options);

return zbx_objectValues($hosts, 'hostid');
}

function available_triggers($triggerids, $editable=null){
	$options = array();
	$options['triggerids'] = $triggerids;
	$options['editable'] = $editable;
	$options['nodes'] = get_current_nodeid(true);

	$triggers = CTrigger::get($options);
return zbx_objectValues($triggers, 'triggerid');
}

function available_graphs($graphids, $editable=null){
	$options = array();
	$options['graphids'] = $graphids;
	$options['editable'] = $editable;
	$options['nodes'] = get_current_nodeid(true);

	$graphs = CGraph::get($options);
return zbx_objectValues($graphs, 'graphid');
}

function get_accessible_hosts_by_user(&$user_data,$perm,$perm_res=null,$nodeid=null,$cache=1){
//		global $DB;
	static $available_hosts;

	if(is_null($perm_res)) $perm_res = PERM_RES_IDS_ARRAY;
	if($perm == PERM_READ_LIST)	$perm = PERM_READ_ONLY;

	$result = array();

	$userid =& $user_data['userid'];
	$user_type =& $user_data['type'];

	if(!isset($userid)) fatal_error('Incorrect user data in "get_accessible_hosts_by_user"');
	if(is_null($nodeid)) $nodeid = get_current_nodeid();

	$nodeid_str =(is_array($nodeid))?md5(implode('',$nodeid)):strval($nodeid);

	if($cache && isset($available_hosts[$userid][$perm][$perm_res][$nodeid_str])){
//SDI('Cache!!! '."[$userid][$perm][$perm_res]");
		return $available_hosts[$userid][$perm][$perm_res][$nodeid_str];
	}
//SDI('NOOOO Cache!!!'."[$userid][$perm][$perm_res]");
COpt::counter_up('perm_host['.$userid.','.$perm.','.$perm_res.','.$nodeid.']');
COpt::counter_up('perm');

	$where = array();

	if(!is_null($nodeid))
		array_push($where, DBin_node('h.hostid', $nodeid));

	if(count($where))
		$where = ' WHERE '.implode(' AND ',$where);
	else
		$where = '';

//		$sortorder = (isset($DB['TYPE']) && (($DB['TYPE'] == 'MYSQL') || ($DB['TYPE'] == 'SQLITE3')))?' DESC ':'';
//SDI($sql);
	$sql = 'SELECT DISTINCT n.nodeid, n.name as node_name, h.hostid, h.host, min(r.permission) as permission, ug.userid '.
		' FROM hosts h '.
			' LEFT JOIN hosts_groups hg ON hg.hostid=h.hostid '.
			' LEFT JOIN groups g ON g.groupid=hg.groupid '.
			' LEFT JOIN rights r ON r.id=g.groupid '.
			' LEFT JOIN users_groups ug ON ug.usrgrpid=r.groupid and ug.userid='.$userid.
			' LEFT JOIN nodes n ON '.DBid2nodeid('h.hostid').'=n.nodeid '.
		$where.
		' GROUP BY h.hostid,n.nodeid,n.name,h.host,ug.userid '.
		' ORDER BY n.name,n.nodeid, h.host, permission, ug.userid ';
//SDI($sql);
	$db_hosts = DBselect($sql);

	$processed = array();
	while($host_data = DBfetch($db_hosts)){
		if(zbx_empty($host_data['nodeid'])) $host_data['nodeid'] = id2nodeid($host_data['hostid']);

/* if no rights defined */
		if(USER_TYPE_SUPER_ADMIN == $user_type){
			$host_data['permission'] = PERM_MAX;
		}
		else{
			if(zbx_empty($host_data['permission']) || zbx_empty($host_data['userid'])) continue;

			if(isset($processed[$host_data['hostid']])){
				if(PERM_DENY == $host_data['permission']){
					unset($result[$host_data['hostid']]);
				}
				else if($processed[$host_data['hostid']] > $host_data['permission']){
					unset($processed[$host_data['hostid']]);
				}
				else{
					continue;
				}
			}
		}

		$processed[$host_data['hostid']] = $host_data['permission'];
		if($host_data['permission']<$perm)	continue;

		switch($perm_res){
			case PERM_RES_DATA_ARRAY:
				$result[$host_data['hostid']] = $host_data;
				break;
			default:
				$result[$host_data['hostid']] = $host_data['hostid'];
		}
	}

	unset($processed, $host_data, $db_hosts);

	if(PERM_RES_STRING_LINE == $perm_res){
		if(count($result) == 0)
			$result = '-1';
		else
			$result = implode(',',$result);
	}

	$available_hosts[$userid][$perm][$perm_res][$nodeid_str] = $result;

return $result;
}

function get_accessible_groups_by_user($user_data,$perm,$perm_res=null,$nodeid=null){
	global $ZBX_LOCALNODEID;

	if(is_null($perm_res)) $perm_res = PERM_RES_IDS_ARRAY;
	if(is_null($nodeid)) $nodeid = get_current_nodeid();

	$result = array();

	$userid =& $user_data['userid'];
	if(!isset($userid)) fatal_error('Incorrect user data in "get_accessible_groups_by_user"');
	$user_type =& $user_data['type'];

COpt::counter_up('perm_group['.$userid.','.$perm.','.$perm_res.','.$nodeid.']');
COpt::counter_up('perm');

	$processed = array();
	$where = array();

	if(!is_null($nodeid)){
		array_push($where, DBin_node('hg.groupid', $nodeid));
	}
	$where = count($where)?' WHERE '.implode(' AND ',$where):'';

	$sql = 'SELECT n.nodeid as nodeid,n.name as node_name,hg.groupid,hg.name,min(r.permission) as permission,g.userid'.
		' FROM groups hg '.
			' LEFT JOIN rights r ON r.id=hg.groupid '.
			' LEFT JOIN users_groups g ON r.groupid=g.usrgrpid AND g.userid='.$userid.
			' LEFT JOIN nodes n ON '.DBid2nodeid('hg.groupid').'=n.nodeid '.
		$where.
		' GROUP BY n.nodeid, n.name, hg.groupid, hg.name, g.userid, g.userid '.
		' ORDER BY node_name, hg.name, permission ';
	$db_groups = DBselect($sql);
	while($group_data = DBfetch($db_groups)){
		if(zbx_empty($group_data['nodeid'])) $group_data['nodeid'] = id2nodeid($group_data['groupid']);


/* deny if no rights defined */
		if(USER_TYPE_SUPER_ADMIN == $user_type){
			$group_data['permission'] = PERM_MAX;
		}
		else{
			if(zbx_empty($group_data['permission']) || zbx_empty($group_data['userid'])) continue;

			if(isset($processed[$group_data['groupid']])){
				if(PERM_DENY == $group_data['permission']){
					unset($result[$group_data['groupid']]);
				}
				else if($processed[$group_data['groupid']] > $group_data['permission']){
					unset($processed[$group_data['groupid']]);
				}
				else{
					continue;
				}
			}
		}

		$processed[$group_data['groupid']] = $group_data['permission'];
		if($group_data['permission'] < $perm) continue;

		switch($perm_res){
			case PERM_RES_DATA_ARRAY:
				$result[$group_data['groupid']] = $group_data;
				break;
			default:
				$result[$group_data['groupid']] = $group_data["groupid"];
				break;
		}
	}

	unset($processed, $group_data, $db_groups);

	if($perm_res == PERM_RES_STRING_LINE) {
		if(count($result) == 0)
			$result = '-1';
		else
			$result = implode(',',$result);
	}

return $result;
}

function get_accessible_nodes_by_user(&$user_data,$perm,$perm_res=null,$nodeid=null,$cache=1){
	global $ZBX_LOCALNODEID, $ZBX_NODES_IDS;
	static $available_nodes;

	if(is_null($perm_res)) $perm_res = PERM_RES_IDS_ARRAY;
	if(is_null($nodeid)) $nodeid = $ZBX_NODES_IDS;
	if(!is_array($nodeid)) $nodeid = array($nodeid);

	$userid		=& $user_data['userid'];
	$user_type	=& $user_data['type'];
	if(!isset($userid)) fatal_error('Incorrect user data in "get_accessible_nodes_by_user"');


	$nodeid_str =(is_array($nodeid))?md5(implode('',$nodeid)):strval($nodeid);

	if($cache && isset($available_nodes[$userid][$perm][$perm_res][$nodeid_str])){
//SDI('Cache!!! '."[$userid][$perm][$perm_res]");
		return $available_nodes[$userid][$perm][$perm_res][$nodeid_str];
	}

	$node_data = array();
	$result = array();

//COpt::counter_up('perm');
	if(USER_TYPE_SUPER_ADMIN == $user_type){
		$nodes = DBselect('SELECT nodeid FROM nodes');
		while($node = DBfetch($nodes)){
			$node_data[$node['nodeid']] = $node;
			$node_data[$node['nodeid']]['permission'] = PERM_READ_WRITE;
		}
		if(empty($node_data)) $node_data[0]['nodeid'] = 0;
	}
	else{
		$available_groups = get_accessible_groups_by_user($user_data,$perm,PERM_RES_DATA_ARRAY,$nodeid,$cache);

		foreach($available_groups as $id => $group){
			$nodeid = id2nodeid($group['groupid']);
			$permission = (isset($node_data[$nodeid]) && ($permission < $node_data[$nodeid]['permission']))?$node_data[$nodeid]['permission']:$group['permission'];

			$node_data[$nodeid]['nodeid'] = $nodeid;
			$node_data[$nodeid]['permission'] = $permission;
		}
	}

	foreach($node_data as $nodeid => $node){
		switch($perm_res){
			case PERM_RES_DATA_ARRAY:
				$db_node = DBfetch(DBselect('SELECT * FROM nodes WHERE nodeid='.$nodeid.' ORDER BY name'));

				if(!ZBX_DISTRIBUTED){
					if(!$node){
						$db_node = array(
							'nodeid'	=> $ZBX_LOCALNODEID,
							'name'		=> 'local',
							'permission'	=> PERM_READ_WRITE,
							'userid'	=> null
							);
					}
					else{
						continue;
					}
				}

				$result[$nodeid] = zbx_array_merge($db_node,$node);

				break;
			default:
				$result[$nodeid] = $nodeid;
				break;
		}
	}

	if($perm_res == PERM_RES_STRING_LINE) {
		if(count($result) == 0)
			$result = '-1';
		else
			$result = implode(',',$result);
	}

	$available_nodes[$userid][$perm][$perm_res][$nodeid_str] = $result;

return $result;
}

/***********************************************
	GET ACCESSIBLE RESOURCES BY RIGHTS
************************************************/
	/* NOTE: right structure is

		$rights[i]['type']	= type of resource
		$rights[i]['permission']= permission for resource
		$rights[i]['id']	= resource id

	*/

function get_accessible_hosts_by_rights(&$rights,$user_type,$perm,$perm_res=null,$nodeid=null){
	if(is_null($perm_res))		$perm_res	= PERM_RES_STRING_LINE;
	if($perm == PERM_READ_LIST)	$perm		= PERM_READ_ONLY;

	$result = array();
	$res_perm = array();

	foreach($rights as $id => $right){
		$res_perm[$right['id']] = $right['permission'];
	}

	$host_perm = array();

	$where = array();
	if(!is_null($nodeid))	array_push($where, DBin_node('h.hostid', $nodeid));
	$where = count($where)?$where = ' WHERE '.implode(' AND ',$where):'';

	$sql = 'SELECT n.nodeid as nodeid,n.name as node_name,hg.groupid as groupid,h.hostid, h.host '.
				' FROM hosts h '.
					' LEFT JOIN hosts_groups hg ON hg.hostid=h.hostid '.
					' LEFT JOIN nodes n ON n.nodeid='.DBid2nodeid('h.hostid').
				$where.
				' ORDER BY n.name,h.host';

	$perm_by_host = array();
	$db_hosts = DBselect($sql);
	while($host_data = DBfetch($db_hosts)){
		if(isset($host_data['groupid']) && isset($res_perm[$host_data['groupid']])){
			if(!isset($perm_by_host[$host_data['hostid']])) $perm_by_host[$host_data['hostid']] = array();

			$perm_by_host[$host_data['hostid']][] = $res_perm[$host_data['groupid']];

			$host_perm[$host_data['hostid']][$host_data['groupid']] = $res_perm[$host_data['groupid']];
		}
		$host_perm[$host_data['hostid']]['data'] = $host_data;
	}

	foreach($host_perm as $hostid => $host_data){
		$host_data = $host_data['data'];

// Select Min rights from groups
		if(USER_TYPE_SUPER_ADMIN == $user_type){
			$host_data['permission'] = PERM_MAX;
		}
		else{
			if(isset($perm_by_host[$hostid])){
				$host_data['permission'] = min($perm_by_host[$hostid]);
			}
			else{
				if(is_null($host_data['nodeid'])) $host_data['nodeid'] = id2nodeid($host_data['groupid']);

				$host_data['permission'] = PERM_DENY;
			}
		}

		if($host_data['permission']<$perm) continue;
		switch($perm_res){
			case PERM_RES_DATA_ARRAY:
				$result[$host_data['hostid']] = $host_data;
				break;
			default:
				$result[$host_data['hostid']] = $host_data['hostid'];
		}
	}

	if($perm_res == PERM_RES_STRING_LINE) {
		if(count($result) == 0)
			$result = '-1';
		else
			$result = implode(',',$result);
	}

return $result;
}

function get_accessible_groups_by_rights(&$rights,$user_type,$perm,$perm_res=null,$nodeid=null){
	if(is_null($perm_res))	$perm_res=PERM_RES_STRING_LINE;
	$result= array();

	$where = array();

	if(!is_null($nodeid))
		array_push($where, DBin_node('g.groupid', $nodeid));

	if(count($where)) $where = ' WHERE '.implode(' AND ',$where);
	else $where = '';

	$group_perm = array();
	foreach($rights as $id => $right){
		$group_perm[$right['id']] = $right['permission'];
	}

	$sql = 'SELECT n.nodeid as nodeid,n.name as node_name, g.*, '.PERM_DENY.' as permission '.
						' FROM groups g '.
							' LEFT JOIN nodes n ON '.DBid2nodeid('g.groupid').'=n.nodeid '.
						$where.
						' ORDER BY n.name, g.name';

	$db_groups = DBselect($sql);

	while($group_data = DBfetch($db_groups)){

		if(USER_TYPE_SUPER_ADMIN == $user_type){
			$group_data['permission'] = PERM_MAX;
		}
		else{
			if(isset($group_perm[$group_data['groupid']])){
				$group_data['permission'] = $group_perm[$group_data['groupid']];
			}
			else{
				if(is_null($group_data['nodeid'])) $group_data['nodeid'] = id2nodeid($group_data['groupid']);
				$group_data['permission'] = PERM_DENY;
			}
		}

		if($group_data['permission']<$perm) continue;

		switch($perm_res){
			case PERM_RES_DATA_ARRAY:
				$result[$group_data['groupid']] = $group_data;
				break;
			default:
				$result[$group_data['groupid']] = $group_data['groupid'];
		}
	}

	if($perm_res == PERM_RES_STRING_LINE) {
		if(count($result) == 0)
			$result = '-1';
		else
			$result = implode(',',$result);
	}

return $result;
}

function get_accessible_nodes_by_rights(&$rights,$user_type,$perm,$perm_res=null){
	global $ZBX_LOCALNODEID;

	$nodeid = get_current_nodeid(true);

	if(is_null($perm_res))	$perm_res=PERM_RES_STRING_LINE;
	if(is_null($user_type)) $user_type = USER_TYPE_ZABBIX_USER;

	$node_data = array();
	$result = array();

//COpt::counter_up('perm_nodes['.$userid.','.$perm.','.$perm_mode.','.$perm_res.','.$nodeid.']');
//COpt::counter_up('perm');
//SDI(get_accessible_groups_by_rights($rights,$user_type,$perm,PERM_RES_DATA_ARRAY,$nodeid));
	$available_groups = get_accessible_groups_by_rights($rights,$user_type,$perm,PERM_RES_DATA_ARRAY,$nodeid);
	foreach($available_groups as $id => $group){
		$nodeid = id2nodeid($group['groupid']);
		$permission = $group['permission'];

		if(isset($node_data[$nodeid]) && ($permission < $node_data[$nodeid]['permission'])){
			$permission = $node_data[$nodeid]['permission'];
		}

		$node_data[$nodeid]['nodeid'] = $nodeid;
		$node_data[$nodeid]['permission'] = $permission;
	}

	$available_hosts = get_accessible_hosts_by_rights($rights,$user_type,$perm,PERM_RES_DATA_ARRAY,$nodeid);
	foreach($available_hosts as $id => $host){
		$nodeid = id2nodeid($host['hostid']);
		$permission = $host['permission'];

		if(isset($node_data[$nodeid]) && ($permission < $node_data[$nodeid]['permission'])){
			$permission = $node_data[$nodeid]['permission'];
		}

		$node_data[$nodeid]['nodeid'] = $nodeid;
		$node_data[$nodeid]['permission'] = $permission;
	}

	foreach($node_data as $nodeid => $node){
		switch($perm_res){
			case PERM_RES_DATA_ARRAY:
				$db_node = DBfetch(DBselect('SELECT * FROM nodes WHERE nodeid='.$nodeid));

				if(!ZBX_DISTRIBUTED){
					if(!$node){
						$db_node = array(
							'nodeid'	=> $ZBX_LOCALNODEID,
							'name'		=> 'local',
							'permission'	=> PERM_READ_WRITE,
							'userid'	=> null
							);
					}
					else{
						continue;
					}
				}

				$result[$nodeid] = zbx_array_merge($db_node,$node);

				break;
			default:
				$result[$nodeid] = $nodeid;
				break;
		}
	}

	if($perm_res == PERM_RES_STRING_LINE) {
		if(count($result) == 0)
			$result = '-1';
		else
			$result = implode(',',$result);
	}

return $result;
}
?>
