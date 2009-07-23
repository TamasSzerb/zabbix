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
	require_once('include/config.inc.php');
	require_once('include/triggers.inc.php');
	require_once('include/media.inc.php');
	require_once('include/users.inc.php');
	require_once('include/forms.inc.php');
	require_once('include/js.inc.php');

	$page['title'] = 'S_USERS';
	$page['file'] = 'users.php';
	$page['hist_arg'] = array('config');
	$page['scripts'] = array('menu_scripts.js');

include_once('include/page_header.php');

?>
<?php
//		VAR			TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION
	$fields=array(
		'config'=>			array(T_ZBX_STR, O_OPT, P_SYS,	NULL,		NULL),
		'perm_details'=>	array(T_ZBX_INT, O_OPT,	null,	IN('0,1'),	null),
/* user */
		'userid'=>			array(T_ZBX_INT, O_NO,	P_SYS,	DB_ID,'(isset({form})&&({form}=="update"))'),
		'group_userid'=>	array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID,		null),
		'filter_usrgrpid'=>	array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID,		null),

		'alias'=>			array(T_ZBX_STR, O_OPT,	null,	NOT_EMPTY,	'isset({save})'),
		'name'=>			array(T_ZBX_STR, O_OPT,	null,	NOT_EMPTY,	'isset({save})'),
		'surname'=>			array(T_ZBX_STR, O_OPT,	null,	NOT_EMPTY,	'isset({save})'),

		'password1'=>		array(T_ZBX_STR, O_OPT,	null,	null,		'isset({save})&&(isset({form})&&({form}!="update"))&&isset({change_password})'),
		"password2"=>		array(T_ZBX_STR, O_OPT,	null,	null,		'isset({save})&&(isset({form})&&({form}!="update"))&&isset({change_password})'),

		'user_type'=>			array(T_ZBX_INT, O_OPT,	null,	IN('1,2,3'),	'isset({save})'),
		'user_groups'=>			array(T_ZBX_STR, O_OPT,	null,	NOT_EMPTY,	null),//'isset({save})'),
		'user_groups_to_del'=>	array(T_ZBX_INT, O_OPT,	null,	DB_ID,	null),
		'user_medias'=>			array(T_ZBX_STR, O_OPT,	null,	NOT_EMPTY,	null),
		'user_medias_to_del'=>	array(T_ZBX_STR, O_OPT,	null,	DB_ID,	null),

		'new_group'=>		array(T_ZBX_STR, O_OPT,	null,	null,	null),
		'new_media'=>		array(T_ZBX_STR, O_OPT,	null,	null,	null),
		'enable_media'=>	array(T_ZBX_INT, O_OPT,	null,	null,		null),
		'disable_media'=>	array(T_ZBX_INT, O_OPT,null,	null,		null),
		'lang'=>			array(T_ZBX_STR, O_OPT,	null,	NOT_EMPTY,	'isset({save})'),
		'theme'=>			array(T_ZBX_STR, O_OPT,	null,	NOT_EMPTY,	'isset({save})'),
		'autologin'=>		array(T_ZBX_INT, O_OPT,	null,	IN('0,1'),	null),
		'autologout'=>		array(T_ZBX_INT, O_OPT,	null,	BETWEEN(90,10000), null),
		'url'=>				array(T_ZBX_STR, O_OPT,	null,	null,		'isset({save})'),
		'refresh'=>			array(T_ZBX_INT, O_OPT,	null,	BETWEEN(0,3600),'isset({save})'),
		'rows_per_page'=>	array(T_ZBX_INT, O_OPT,	null,	BETWEEN(0,1000),'isset({save})'),
// Actions
		'go'=>					array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, NULL, NULL),

// form
		'register'=>	array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, IN('"add permission","delete permission"'), null),

		'save'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	null,	null),
		'delete'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	null,	null),
		'delete_selected'=>	array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	null,	null),
		'del_user_group'=>	array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	null,	null),
		'del_user_media'=>	array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	null,	null),

		'del_group_user'=>	array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	null,	null),

		'change_password'=>	array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	null,	null),
		'cancel'=>	array(T_ZBX_STR, O_OPT, P_SYS,	null,	null),
/* other */
		'form'=>	array(T_ZBX_STR, O_OPT, P_SYS,	null,	null),
		'form_refresh'=>array(T_ZBX_STR, O_OPT, null,	null,	null)
	);

	check_fields($fields);
	validate_sort_and_sortorder('u.alias',ZBX_SORT_UP);

	$_REQUEST['go'] = get_request('go','none');
?>
<?php
	if(isset($_REQUEST['new_media'])){
		$_REQUEST['user_medias'] = get_request('user_medias', array());
		array_push($_REQUEST['user_medias'], $_REQUEST['new_media']);
	}
	else if(isset($_REQUEST['user_medias']) && isset($_REQUEST['enable_media'])){
		if(isset($_REQUEST['user_medias'][$_REQUEST['enable_media']])){
			$_REQUEST['user_medias'][$_REQUEST['enable_media']]['active'] = 0;
		}
	}
	else if(isset($_REQUEST['user_medias']) && isset($_REQUEST['disable_media'])){
		if(isset($_REQUEST['user_medias'][$_REQUEST['disable_media']])){
			$_REQUEST['user_medias'][$_REQUEST['disable_media']]['active'] = 1;
		}
	}
	else if(isset($_REQUEST['save'])){
		$config = select_config();

		$_REQUEST['password1'] = get_request('password1', null);
		$_REQUEST['password2'] = get_request('password2', null);

		if(($config['authentication_type'] != ZBX_AUTH_INTERNAL) && zbx_empty($_REQUEST['password1'])){
			if(($config['authentication_type'] == ZBX_AUTH_LDAP) && isset($_REQUEST['userid'])){
				if(GROUP_GUI_ACCESS_INTERNAL != get_user_auth($_REQUEST['userid'])){
//						$_REQUEST['password1'] = $_REQUEST['password2'] = 'zabbix';
				}
			}
			else{
				$_REQUEST['password1'] = $_REQUEST['password2'] = 'zabbix';
			}
		}
		if($_REQUEST['password1']!=$_REQUEST['password2']){
			if(isset($_REQUEST['userid']))
				show_error_message(S_CANNOT_UPDATE_USER_BOTH_PASSWORDS);
			else
				show_error_message(S_CANNOT_ADD_USER_BOTH_PASSWORDS_MUST);
		}
		else if(isset($_REQUEST['password1']) && ($_REQUEST['alias']==ZBX_GUEST_USER) && !zbx_empty($_REQUEST['password1'])){
			show_error_message(S_FOR_GUEST_PASSWORD_MUST_BE_EMPTY);
		}
		else if(isset($_REQUEST['password1']) && ($_REQUEST['alias']!=ZBX_GUEST_USER) && zbx_empty($_REQUEST['password1'])){
			show_error_message(S_PASSWORD_SHOULD_NOT_BE_EMPTY);
		}
		else {
			$user = array();
			$user['name'] = get_request('name');
			$user['surname'] = get_request('surname');
			$user['alias'] = get_request('alias');
			$user['passwd'] = get_request('password1');
			$user['url'] = get_request('url');
			$user['autologin'] = get_request('autologin', 0);
			$user['autologout'] = get_request('autologout', 0);
			$user['lang'] = get_request('lang');
			$user['theme'] = get_request('theme');
			$user['refresh'] = get_request('refresh');
			$user['rows_per_page'] = get_request('rows_per_page');
			$user['type'] = get_request('user_type');
			$user['user_groups'] = get_request('user_groups', array());
			$user['user_medias'] = get_request('user_medias', array());

			if(isset($_REQUEST['userid'])){
				$action = AUDIT_ACTION_UPDATE;

				DBstart();
				$result = update_user($_REQUEST['userid'], $user);
				$result = DBend($result);

				show_messages($result, S_USER_UPDATED, S_CANNOT_UPDATE_USER);
			}
			else {
				$action = AUDIT_ACTION_ADD;

				DBstart();
				$result = add_user($user);
				$result = DBend($result);

				show_messages($result, S_USER_ADDED, S_CANNOT_ADD_USER);
			}
			if($result){
				add_audit($action,AUDIT_RESOURCE_USER,'User alias ['.$_REQUEST['alias'].'] name ['.$_REQUEST['name'].'] surname ['.$_REQUEST['surname'].']');
				unset($_REQUEST['form']);
			}
		}
	}
	else if(isset($_REQUEST['del_user_media'])){
		$user_medias_to_del = get_request('user_medias_to_del', array());
		foreach($user_medias_to_del as $mediaid){
			if(isset($_REQUEST['user_medias'][$mediaid]))
				unset($_REQUEST['user_medias'][$mediaid]);
		}

	}
	else if(isset($_REQUEST['del_user_group'])){
		$user_groups_to_del = get_request('user_groups_to_del', array());
		foreach($user_groups_to_del as $groupid){
			if(isset($_REQUEST['user_groups'][$groupid]))
				unset($_REQUEST['user_groups'][$groupid]);
		}

	}
	else if(isset($_REQUEST['delete'])&&isset($_REQUEST['userid'])){
		$user=get_user_by_userid($_REQUEST['userid']);

		DBstart();
		$result = delete_user($_REQUEST['userid']);
		$result = DBend($result);

		show_messages($result, S_USER_DELETED, S_CANNOT_DELETE_USER);
		if($result){
			add_audit(AUDIT_ACTION_DELETE,AUDIT_RESOURCE_USER,
				'User alias ['.$user['alias'].'] name ['.$user['name'].'] surname ['.$user['surname'].']');

			unset($_REQUEST['userid']);
			unset($_REQUEST['form']);
		}
	}
// Add USER to GROUP
	else if(isset($_REQUEST['grpaction'])&&isset($_REQUEST['usrgrpid'])&&isset($_REQUEST['userid'])&&($_REQUEST['grpaction']==1)){
		$user=get_user_by_userid($_REQUEST['userid']);
		$group=get_group_by_usrgrpid($_REQUEST['usrgrpid']);

		DBstart();
		$result = add_user_to_group($_REQUEST['userid'],$_REQUEST['usrgrpid']);
		$result = DBend($result);

		show_messages($result, S_USER_UPDATED, S_CANNOT_UPDATE_USER);
		if($result){
			add_audit(AUDIT_ACTION_ADD,AUDIT_RESOURCE_USER_GROUP,
				'User alias ['.$user['alias'].'] name ['.$user['name'].'] surname ['.$user['surname'].']');

			unset($_REQUEST['usrgrpid']);
			unset($_REQUEST['userid']);
		}
		unset($_REQUEST['grpaction']);
		unset($_REQUEST['form']);
	}
// Remove USER from GROUP
	else if(isset($_REQUEST['grpaction'])&&isset($_REQUEST['usrgrpid'])&&isset($_REQUEST['userid'])&&($_REQUEST['grpaction']==0)){
		$user=get_user_by_userid($_REQUEST['userid']);
		$group=get_group_by_usrgrpid($_REQUEST['usrgrpid']);

		DBstart();
		$result = remove_user_from_group($_REQUEST['userid'],$_REQUEST['usrgrpid']);
		$result = DBend($result);

		show_messages($result, S_USER_UPDATED, S_CANNOT_UPDATE_USER);
		if($result){
			add_audit(AUDIT_ACTION_DELETE,AUDIT_RESOURCE_USER_GROUP,
				'User alias ['.$user['alias'].'] name ['.$user['name'].'] surname ['.$user['surname'].']');

			unset($_REQUEST['usrgrpid']);
			unset($_REQUEST['userid']);
		}
		unset($_REQUEST['grpaction']);
		unset($_REQUEST['form']);
	}
// ----- GO -----
	else if(($_REQUEST['go'] == 'delete') && isset($_REQUEST['group_userid'])){
		$result = false;

		$group_userid = get_request('group_userid', array());

		DBstart();
		foreach($group_userid as $userid){
			if(!($user_data = get_user_by_userid($userid))) continue;

			$result |= delete_user($userid);

			if($result){
				add_audit(AUDIT_ACTION_DELETE,AUDIT_RESOURCE_USER,
					'User alias ['.$user_data['alias'].'] name ['.$user_data['name'].'] surname ['.
					$user_data['surname'].']');
			}
		}

		$result = DBend($result);
		show_messages($result, S_USER_DELETED,S_CANNOT_DELETE_USER);
	}

?>
<?php

	$_REQUEST['filter_usrgrpid'] = get_request('filter_usrgrpid',get_profile('web.users.filter.usrgrpid',0));
	update_profile('web.users.filter.usrgrpid', $_REQUEST['filter_usrgrpid'], PROFILE_TYPE_ID);

	$frmForm = new CForm();
	$frmForm->setMethod('get');

// Config
	$cmbConf = new CComboBox('config', 'users.php', 'javascript: submit()');
	$cmbConf->setAttribute('onchange', 'javascript: redirect(this.options[this.selectedIndex].value);');
		$cmbConf->addItem('usergrps.php', S_USER_GROUPS);
		$cmbConf->addItem('users.php', S_USERS);

	$frmForm->addItem($cmbConf);

	$cmbUGrp = new CComboBox('filter_usrgrpid',$_REQUEST['filter_usrgrpid'],'submit()');
	$cmbUGrp->addItem(0, S_ALL_S);

	$options = array('extendoutput' => 1, 'order' => 'name');
	$usrgrps = CUserGroup::get($options);
	foreach($usrgrps as $usrgrpid => $usrgrp){
		$cmbUGrp->addItem($usrgrpid, $usrgrp['name']);
	}

	$frmForm->addItem(array(SPACE.SPACE,S_USER_GROUP,$cmbUGrp));

	$frmForm->addItem(SPACE.'|'.SPACE);
	$frmForm->addItem($btnNew = new CButton('form',S_CREATE_USER));
	show_table_header(S_CONFIGURATION_OF_USERS_AND_USER_GROUPS, $frmForm);
	echo SBR;
?>
<?php
	if(isset($_REQUEST['form'])){
		insert_user_form(get_request('userid',null));
	}
	else{

		$options = array('extendoutput' => 1, 'order' => 'alias', 'select_usrgrps' => 1, 'get_access' => 1);
		if($_REQUEST['filter_usrgrpid'] > 0){
			$options += array('usrgrpids' => $_REQUEST['filter_usrgrpid']);
		}
		$users = CUser::get($options);
		$userids = array_keys($users);

		$numrows = count($users);

		$header = get_table_header(array(
			S_USERS_BIG,
			new CSpan(SPACE.SPACE.'|'.SPACE.SPACE, 'divider'),
			S_FOUND.': ',
			new CSpan($numrows,'info')
		));
		show_table_header($header);

		$form = new CForm(null,'post');
		$form->setName('users');

		$table=new CTableInfo(S_NO_USERS_DEFINED);
		$table->setHeader(array(
			new CCheckBox('all_users',NULL,"checkAll('".$form->GetName()."','all_users','group_userid');"),
			make_sorting_link(S_ALIAS,'u.alias'),
			make_sorting_link(S_NAME,'u.name'),
			make_sorting_link(S_SURNAME,'u.surname'),
			make_sorting_link(S_USER_TYPE,'u.type'),
			S_GROUPS,
			S_IS_ONLINE_Q,
			S_GUI_ACCESS,
			S_API_ACCESS,
			S_DEBUG_MODE,
			S_STATUS
		));

		// set default lastaccess time to 0.
		foreach($users as $userid => $user){
			$usessions[$userid] = array('lastaccess' => 0);
		}
		$sql = 'SELECT s.userid, MAX(s.lastaccess) as lastaccess, s.status '.
				' FROM sessions s'.
				' WHERE '.DBcondition('s.userid', $userids).
				' GROUP BY s.userid, s.status';
		$db_sessions = DBselect($sql);
		while($session = DBfetch($db_sessions)){
			if($usessions[$session['userid']]['lastaccess'] < $session['lastaccess']){
				$usessions[$session['userid']] = $session;
			}
		}

		foreach($users as $userid => $user){
			$session = $usessions[$userid];

			// Online time
			$online_time = (($user['autologout'] == 0) || (ZBX_USER_ONLINE_TIME<$user['autologout']))
				? ZBX_USER_ONLINE_TIME : $user['autologout'];
			if($session['lastaccess']){
				$online = (($session['lastaccess'] + $online_time) >= time())
					? new CCol(S_YES.' ('.date('r', $session['lastaccess']).')', 'enabled')
					: new CCol(S_NO.' ('.date('r', $session['lastaccess']).')', 'disabled');
			}
			else{
				$online = new CCol(S_NO, 'disabled');
			}


			// UserGroups
			$users_groups = array();
			foreach($user['usrgrps'] as $usrgrpid => $usrgrp){
				$users_groups[] = new CLink($usrgrp['name'],'usergrps.php?form=update&usrgrpid='.$usrgrpid);
				$users_groups[] = BR();
			}
			array_pop($users_groups);


			$gui_access = user_auth_type2str($user['gui_access']);
			$gui_access = new CSpan($gui_access, ($user['gui_access'] == GROUP_GUI_ACCESS_DISABLED) ? 'orange' : 'green');
			$users_status = ($user['users_status'] == 1) ? new CSpan(S_DISABLED, 'red') : new CSpan(S_ENABLED, 'green');
			$api_access = ($user['api_access'] == GROUP_API_ACCESS_ENABLED) ? new CSpan(S_ENABLED, 'orange') : new CSpan(S_DISABLED, 'green');
			$debug_mode = ($user['debug_mode'] == GROUP_DEBUG_MODE_ENABLED) ? new CSpan(S_ENABLED, 'orange') : new CSpan(S_DISABLED, 'green');

			$table->addRow(array(
				new CCheckBox('group_userid['.$userid.']', NULL, NULL, $userid),
				new CLink($user['alias'], 'users.php?form=update&userid='.$userid),
				$user['name'],
				$user['surname'],
				user_type2str($user['type']),
				$users_groups,
				$online,
				$gui_access,
				$api_access,
				$debug_mode,
				$users_status
			));
		}

/* <<<--- GO button --->>> */
		$goBox = new CComboBox('go');
		$goBox->addItem('delete',S_DELETE_SELECTED);

		// goButton name is necessary!!!
		$goButton = new CButton('goButton', S_GO.' (0)');
		$goButton->setAttribute('id','goButton');
		zbx_add_post_js('chkbxRange.pageGoName = "group_userid";');

		$table->setFooter(new CCol(array($goBox, $goButton)));
/* --->>> GO button <<<--- */

		$form->addItem($table);
		$form->show();
	}

include_once('include/page_footer.php');

?>
