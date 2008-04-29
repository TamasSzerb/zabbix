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
	require_once "include/config.inc.php";
	require_once "include/forms.inc.php";

	$page["title"]	= "S_ZABBIX_BIG";
	$page["file"]	= "index.php";
	
?>
<?php
//		VAR			TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION
	$fields=array(
		"name"=>			array(T_ZBX_STR, O_NO,	NULL,	NOT_EMPTY,	'isset({enter})'),
		"password"=>		array(T_ZBX_STR, O_OPT,	NULL,	NULL,		'isset({enter})'),
		"sessionid"=>		array(T_ZBX_STR, O_OPT,	NULL,	NULL,		NULL),
		"message"=>			array(T_ZBX_STR, O_OPT,	NULL,	NULL,		NULL),
		"reconnect"=>		array(T_ZBX_INT, O_OPT,	P_ACT, BETWEEN(0,65535),NULL),
		"enter"=>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,    NULL,   NULL),
		"form"=>			array(T_ZBX_STR, O_OPT, P_SYS,  NULL,   	NULL),
		"form_refresh"=>	array(T_ZBX_INT, O_OPT, NULL,   NULL,   	NULL)
	);
	check_fields($fields);
?>
<?php
	$sessionid = get_cookie('zbx_sessionid', null);
	
	if(isset($_REQUEST["reconnect"]) && isset($sessionid)){
		add_audit(AUDIT_ACTION_LOGOUT,AUDIT_RESOURCE_USER,"Manual Logout");
		
		zbx_unsetcookie('zbx_sessionid');
		DBexecute("delete from sessions where sessionid=".zbx_dbstr($sessionid));
		unset($sessionid);

		Redirect("index.php");
		die();
//		return;
	}

	if(isset($_REQUEST["enter"])&&($_REQUEST["enter"]=="Enter")){
	
		$config = select_config();
		
		$name = get_request('name','');
		$password = md5(get_request('password',''));
		
		$sql = 'SELECT u.userid,u.attempt_failed, u.attempt_clock, u.attempt_ip '.
				' FROM users u '.
				' WHERE u.alias='.zbx_dbstr($name).
					' AND ( attempt_failed<'.ZBX_LOGIN_ATTEMPTS.
							' OR (attempt_failed>'.(ZBX_LOGIN_ATTEMPTS-1).
									' AND ('.time().'-attempt_clock)>'.ZBX_LOGIN_BLOCK.'))';
					
		$login = $attempt = DBfetch(DBselect($sql));
		
		if($login){
			switch($config['authentication_type']){
				case ZBX_AUTH_LDAP:
					$login = ldap_authentication($name,get_request('password',''));
					break;
				case ZBX_AUTH_INTERNAL:
				default:
					$alt_auth = ZBX_AUTH_INTERNAL;
					$login = true;
			}
		}
		
		if($login){
			$login = $row = DBfetch(DBselect('SELECT u.userid,u.alias,u.name,u.surname,u.url,u.refresh,u.passwd '.
						' FROM users u, users_groups ug, usrgrp g '.
 						' WHERE u.alias='.zbx_dbstr($name).
							((ZBX_AUTH_INTERNAL==$config['authentication_type'])?' AND u.passwd='.zbx_dbstr($password):'').
							' AND '.DBin_node('u.userid', $ZBX_LOCALNODEID)));
		}
		
// update internal pass if it's different
		if($login && ($row['passwd']!=$password) && (ZBX_AUTH_INTERNAL!=$config['authentication_type'])){
			DBexecute('UPDATE users SET passwd='.zbx_dbstr($password).' WHERE userid='.zbx_dbstr($row['userid']));
		}
		
		if($login){
			$login = (check_perm2login($row['userid']) && check_perm2system($row['userid']));
		}
		
		if($login){
			$sessionid = md5(time().$password.$name.rand(0,10000000));
			zbx_setcookie('zbx_sessionid',$sessionid);
			
			DBexecute('INSERT INTO sessions (sessionid,userid,lastaccess) VALUES ('.zbx_dbstr($sessionid).','.$row['userid'].','.time().')');

			add_audit(AUDIT_ACTION_LOGIN,AUDIT_RESOURCE_USER,"Correct login [".$name."]");
			
			if(empty($row["url"])){
				$USER_DETAILS['alias'] = $row['alias'];
				$USER_DETAILS['userid'] = $row['userid'];
				
				$row["url"] = get_profile('web.menu.view.last','index.php');
				unset($USER_DETAILS);
			}
			Redirect($row["url"]);
			die();
//			return;
		}
		else{
			$row = NULL;
			
			$_REQUEST['message'] = 'Login name or password is incorrect';
			add_audit(AUDIT_ACTION_LOGIN,AUDIT_RESOURCE_USER,'Login failed ['.$name.']');
			
			if($attempt){
				$ip = (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']))?$_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR'];			
				$attempt['attempt_failed']++;
				$sql = 'UPDATE users SET attempt_failed='.zbx_dbstr($attempt['attempt_failed']).
										', attempt_clock='.time().
										', attempt_ip='.zbx_dbstr($ip).
									' WHERE userid='.zbx_dbstr($attempt['userid']);
				DBexecute($sql);
			}
		}
	}

include_once "include/page_header.php";
	
	if(isset($_REQUEST['message'])) show_error_message($_REQUEST['message']);

	if(!isset($sessionid)){
		insert_login_form();
	}
	else{
		$logoff = new CLink('here', '?reconnect=1', 'styled');
		echo '<div align="center" class="textcolorstyles">Press '.$logoff->ToString().' to disconnect/reconnect</div>';
	}	
?>
<?php

include_once "include/page_footer.php";

?>
