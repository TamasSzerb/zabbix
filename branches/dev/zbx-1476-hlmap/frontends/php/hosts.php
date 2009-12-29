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
require_once('include/hosts.inc.php');
require_once('include/maintenances.inc.php');
require_once('include/forms.inc.php');

$page['title'] = 'S_HOSTS';
$page['file'] = 'hosts.php';
$page['hist_arg'] = array('groupid','hostid');

include_once('include/page_header.php');
?>
<?php
//		VAR			TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION
	$fields=array(
//ARRAYS
		'hosts'=>		array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID, NULL),
		'groups'=>		array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID, NULL),
		'hostids'=>		array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID, NULL),
		'groupids'=>	array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID, NULL),
		'applications'=>array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID, NULL),
// host
		'groupid'=>			array(T_ZBX_INT, O_OPT,	P_SYS,  DB_ID,			null),
		'hostid'=>			array(T_ZBX_INT, O_OPT,	P_SYS,  DB_ID,			'isset({form})&&({form}=="update")'),
		'host'=>			array(T_ZBX_STR, O_OPT,	NULL,   NOT_EMPTY,		'isset({save})&&isset({go})&&({go}!="massupdate")'),
		'proxy_hostid'=>	array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID,			'isset({save})&&isset({go})&&({go}!="massupdate")'),
		'dns'=>				array(T_ZBX_STR, O_OPT,	NULL,	NULL,			'isset({save})&&isset({go})&&({go}!="massupdate")'),
		'useip'=>			array(T_ZBX_STR, O_OPT, NULL,	IN('0,1'),		'isset({save})&&isset({go})&&({go}!="massupdate")'),
		'ip'=>				array(T_ZBX_IP,  O_OPT, NULL,	NULL,			'isset({save})&&isset({go})&&({go}!="massupdate")'),
		'port'=>			array(T_ZBX_INT, O_OPT,	NULL,	BETWEEN(0,65535),	'isset({save})&&isset({go})&&({go}!="massupdate")'),
		'status'=>			array(T_ZBX_INT, O_OPT,	NULL,	IN('0,1,3'),		'isset({save})&&isset({go})&&({go}!="massupdate")'),

		'newgroup'=>		array(T_ZBX_STR, O_OPT, NULL,   NULL,	NULL),
		'templates'=>		array(T_ZBX_STR, O_OPT,	NULL,	NOT_EMPTY,	NULL),
		'templates_rem'=>	array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,   NULL,	NULL),
		'clear_templates'=>	array(T_ZBX_INT, O_OPT,	NULL,	DB_ID,	NULL),

		'useipmi'=>			array(T_ZBX_STR, O_OPT,	NULL,	NULL,				NULL),
		'ipmi_ip'=>			array(T_ZBX_STR, O_OPT,	NULL,	NULL,				'isset({useipmi})&&isset({go})&&({go}!="massupdate")'),
		'ipmi_port'=>		array(T_ZBX_INT, O_OPT,	NULL,	BETWEEN(0,65535),	'isset({useipmi})&&isset({go})&&({go}!="massupdate")'),
		'ipmi_authtype'=>	array(T_ZBX_INT, O_OPT,	NULL,	BETWEEN(-1,6),		'isset({useipmi})&&isset({go})&&({go}!="massupdate")'),
		'ipmi_privilege'=>	array(T_ZBX_INT, O_OPT,	NULL,	BETWEEN(1,5),		'isset({useipmi})&&isset({go})&&({go}!="massupdate")'),
		'ipmi_username'=>	array(T_ZBX_STR, O_OPT,	NULL,	NULL,				'isset({useipmi})&&isset({go})&&({go}!="massupdate")'),
		'ipmi_password'=>	array(T_ZBX_STR, O_OPT,	NULL,	NULL,				'isset({useipmi})&&isset({go})&&({go}!="massupdate")'),

		'useprofile'=>		array(T_ZBX_STR, O_OPT, NULL,   NULL,	NULL),
		'devicetype'=>		array(T_ZBX_STR, O_OPT, NULL,   NULL,	'isset({useprofile})&&isset({go})&&({go}!="massupdate")'),
		'name'=>			array(T_ZBX_STR, O_OPT, NULL,   NULL,	'isset({useprofile})&&isset({go})&&({go}!="massupdate")'),
		'os'=>				array(T_ZBX_STR, O_OPT, NULL,   NULL,	'isset({useprofile})&&isset({go})&&({go}!="massupdate")'),
		'serialno'=>		array(T_ZBX_STR, O_OPT, NULL,   NULL,	'isset({useprofile})&&isset({go})&&({go}!="massupdate")'),
		'tag'=>				array(T_ZBX_STR, O_OPT, NULL,   NULL,	'isset({useprofile})&&isset({go})&&({go}!="massupdate")'),
		'macaddress'=>		array(T_ZBX_STR, O_OPT, NULL,   NULL,	'isset({useprofile})&&isset({go})&&({go}!="massupdate")'),
		'hardware'=>		array(T_ZBX_STR, O_OPT, NULL,   NULL,	'isset({useprofile})&&isset({go})&&({go}!="massupdate")'),
		'software'=>		array(T_ZBX_STR, O_OPT, NULL,   NULL,	'isset({useprofile})&&isset({go})&&({go}!="massupdate")'),
		'contact'=>			array(T_ZBX_STR, O_OPT, NULL,   NULL,	'isset({useprofile})&&isset({go})&&({go}!="massupdate")'),
		'location'=>		array(T_ZBX_STR, O_OPT, NULL,   NULL,	'isset({useprofile})&&isset({go})&&({go}!="massupdate")'),
		'notes'=>			array(T_ZBX_STR, O_OPT, NULL,   NULL,	'isset({useprofile})&&isset({go})&&({go}!="massupdate")'),

		'useprofile_ext'=>		array(T_ZBX_STR, O_OPT, NULL,   NULL,	NULL),
		'ext_host_profiles'=> 	array(T_ZBX_STR, O_OPT, P_UNSET_EMPTY,   NULL,   NULL),

		'macros_rem'=>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,   NULL,	NULL),
		'macros'=>				array(T_ZBX_STR, O_OPT, P_SYS,   NULL,	NULL),
		'macro_new'=>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,   NULL,	'isset({macro_add})'),
		'value_new'=>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,   NULL,	'isset({macro_add})'),
		'macro_add' =>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,   NULL,	NULL),
		'macros_del' =>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,   NULL,	NULL),
// mass update
		'massupdate'=>		array(T_ZBX_STR, O_OPT, P_SYS,	NULL,	NULL),
		'visible'=>			array(T_ZBX_STR, O_OPT,	null, 	null,	null),
// actions
		'go'=>					array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, NULL, NULL),
// form
		'add_to_group'=>		array(T_ZBX_INT, O_OPT, P_SYS|P_ACT, DB_ID, NULL),
		'delete_from_group'=>	array(T_ZBX_INT, O_OPT, P_SYS|P_ACT, DB_ID, NULL),
		'unlink'=>				array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,   NULL,	NULL),
		'unlink_and_clear'=>	array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,   NULL,	NULL),
		'save'=>				array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'clone'=>				array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'full_clone'=>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'delete'=>				array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'cancel'=>				array(T_ZBX_STR, O_OPT, P_SYS,			NULL,	NULL),
// other
		'form'=>	array(T_ZBX_STR, O_OPT, P_SYS,	NULL,	NULL),
		'form_refresh'=>array(T_ZBX_STR, O_OPT, NULL,	NULL,	NULL)
	);

// OUTER DATA
	check_fields($fields);
	validate_sort_and_sortorder('host', ZBX_SORT_UP);

	$_REQUEST['go'] = get_request('go', 'none');


// PERMISSIONS
	if(get_request('groupid', 0) > 0){
		$groupids = available_groups($_REQUEST['groupid'], 1);
		if(empty($groupids)) access_deny();
	}

	if(get_request('hostid', 0) > 0){
		$hostids = available_hosts($_REQUEST['hostid'], 1);
		if(empty($hostids)) access_deny();
	}
?>
<?php

/************ ACTIONS FOR HOSTS ****************/
// REMOVE MACROS
	if(isset($_REQUEST['macros_del']) && isset($_REQUEST['macros_rem'])){
		$macros_rem = get_request('macros_rem', array());
		foreach($macros_rem as $macro)
			unset($_REQUEST['macros'][$macro]);
	}
// ADD MACRO
	if(isset($_REQUEST['macro_add'])){
		$macro_new = get_request('macro_new');
		$value_new = get_request('value_new', null);

		$currentmacros = array_keys(get_request('macros', array()));

		if(!CUserMacro::validate(zbx_toObject($macro_new, 'macro'))){
			error(S_WRONG_MACRO.' : '.$macro_new);
			show_messages(false, '', S_CANNOT_ADD_MACRO);
		}
		else if(zbx_empty($value_new)){
			error(S_EMPTY_MACRO_VALUE);
			show_messages(false, '', S_CANNOT_ADD_MACRO);
		}
		else if(str_in_array($macro_new, $currentmacros)){
			error(S_MACRO_EXISTS.' : '.$macro_new);
			show_messages(false, '', S_CANNOT_ADD_MACRO);
		}
		else if(strlen($macro_new) > 64){
			error(S_MACRO_TOO_LONG.' : '.$macro_new);
			show_messages(false, '', S_CANNOT_ADD_MACRO);
		}
		else if(strlen($value_new) > 255){
			error(S_MACRO_VALUE_TOO_LONG.' : '.$value_new);
			show_messages(false, '', S_CANNOT_ADD_MACRO);
		}
		else{
			$_REQUEST['macros'][$macro_new]['macro'] = $macro_new;
			$_REQUEST['macros'][$macro_new]['value'] = $value_new;
			unset($_REQUEST['macro_new']);
			unset($_REQUEST['value_new']);
		}
	}
// UNLINK HOST
	if(isset($_REQUEST['templates_rem']) && (isset($_REQUEST['unlink']) || isset($_REQUEST['unlink_and_clear']))){
		$_REQUEST['clear_templates'] = get_request('clear_templates', array());
		$unlink_templates = array_keys($_REQUEST['templates_rem']);
		if(isset($_REQUEST['unlink_and_clear'])){
			$_REQUEST['clear_templates'] = zbx_array_merge($_REQUEST['clear_templates'], $unlink_templates);
		}

		foreach($unlink_templates as $id)
			unset($_REQUEST['templates'][$id]);
	}
// CLONE HOST
	else if(isset($_REQUEST['clone']) && isset($_REQUEST['hostid'])){
		unset($_REQUEST['hostid']);
		$_REQUEST['form'] = 'clone';
	}
// FULL CLONE HOST
	else if(isset($_REQUEST['full_clone']) && isset($_REQUEST['hostid'])){
		$_REQUEST['form'] = 'full_clone';
	}
// HOST MASS UPDATE
	else if(isset($_REQUEST['go']) && ($_REQUEST['go'] == 'massupdate') && isset($_REQUEST['save'])){
		$hosts = get_request('hosts', array());
		$visible = get_request('visible', array());
		$_REQUEST['groups'] = get_request('groups', array());
		$_REQUEST['newgroup'] = get_request('newgroup', '');
		$_REQUEST['proxy_hostid'] = get_request('proxy_hostid', 0);
		$_REQUEST['templates'] = get_request('templates', array());

		if(count($_REQUEST['groups']) > 0){
			$accessible_groups = get_accessible_groups_by_user($USER_DETAILS,PERM_READ_WRITE,PERM_RES_IDS_ARRAY);
			foreach($_REQUEST['groups'] as $gid){
				if(!isset($accessible_groups[$gid])) access_deny();
			}
		}

		$result = true;

		DBstart();

		// if(isset($visible['groups'])){
			// $groupids = get_request('groups', array());
			// CHostGroup::updateHosts(array('hostids' => $hostids, 'groupids' => $groupids));
		// }

		// if(isset($visible['newgroup'])){
			// $newgroup = get_request('newgroup', '');
			// $newgroupid = CHostGroup::create(array('name' => $newgroup));
			// $newgroupid = reset($newgroupid);
			// CHostGroup::createHosts(array('hostids' => $hostids, 'groupids' => $newgroupid));
		// }

		foreach($hosts as $id => $hostid){

			$db_host = get_host_by_hostid($hostid);
			$db_templates = get_templates_by_hostid($hostid);

			foreach($db_host as $key => $value){
				if(isset($visible[$key])){
					if ($key == 'useipmi')
						$db_host[$key] = get_request('useipmi', 'no');
					else
						$db_host[$key] = $_REQUEST[$key];
				}
			}

			if(isset($visible['groups'])){
				$db_host['groups'] = $_REQUEST['groups'];
			}
			else{
				$db_host['groups'] = get_groupids_by_host($hostid);
			}

			if(isset($visible['template_table'])){
				foreach($db_templates as $templateid => $name){
					$result &= unlink_template($hostid, $templateid, false);
				}
				$db_host['templates'] = $_REQUEST['templates'];
			}
			else{
				$db_host['templates'] = $db_templates;
			}

			$result &= (bool) update_host($hostid,
				$db_host['host'],$db_host['port'],$db_host['status'],$db_host['useip'],$db_host['dns'],
				$db_host['ip'],$db_host['proxy_hostid'],$db_host['templates'],$db_host['useipmi'],$db_host['ipmi_ip'],
				$db_host['ipmi_port'],$db_host['ipmi_authtype'],$db_host['ipmi_privilege'],$db_host['ipmi_username'],
				$db_host['ipmi_password'],$_REQUEST['newgroup'],$db_host['groups']);


			if($result && isset($visible['useprofile'])){

				$host_profile = DBfetch(DBselect('SELECT * FROM hosts_profiles WHERE hostid='.$hostid));
				$host_profile_fields = array('devicetype', 'name', 'os', 'serialno', 'tag','macaddress', 'hardware', 'software', 'contact', 'location', 'notes');

				delete_host_profile($hostid);

				if(get_request('useprofile','no') == 'yes'){
					foreach($host_profile_fields as $field){
						if(isset($visible[$field]))
							$host_profile[$field] = $_REQUEST[$field];
						elseif(!isset($host_profile[$field]))
							$host_profile[$field] = '';
					}

					$result &= add_host_profile($hostid,
						$host_profile['devicetype'],$host_profile['name'],$host_profile['os'],
						$host_profile['serialno'],$host_profile['tag'],$host_profile['macaddress'],
						$host_profile['hardware'],$host_profile['software'],$host_profile['contact'],
						$host_profile['location'],$host_profile['notes']);
				}
			}

//HOSTS PROFILE EXTANDED Section
			if($result && isset($visible['useprofile_ext'])){

				$host_profile_ext=DBfetch(DBselect('SELECT * FROM hosts_profiles_ext WHERE hostid='.$hostid));
				$host_profile_ext_fields = array('device_alias','device_type','device_chassis','device_os','device_os_short',
					'device_hw_arch','device_serial','device_model','device_tag','device_vendor','device_contract',
					'device_who','device_status','device_app_01','device_app_02','device_app_03','device_app_04',
					'device_app_05','device_url_1','device_url_2','device_url_3','device_networks','device_notes',
					'device_hardware','device_software','ip_subnet_mask','ip_router','ip_macaddress','oob_ip',
					'oob_subnet_mask','oob_router','date_hw_buy','date_hw_install','date_hw_expiry','date_hw_decomm','site_street_1',
					'site_street_2','site_street_3','site_city','site_state','site_country','site_zip','site_rack','site_notes',
					'poc_1_name','poc_1_email','poc_1_phone_1','poc_1_phone_2','poc_1_cell','poc_1_screen','poc_1_notes','poc_2_name',
					'poc_2_email','poc_2_phone_1','poc_2_phone_2','poc_2_cell','poc_2_screen','poc_2_notes');

				delete_host_profile_ext($hostid);
//ext_host_profiles
				$useprofile_ext = get_request('useprofile_ext',false);
				$ext_host_profiles = get_request('ext_host_profiles',array());

				if($useprofile_ext && !empty($ext_host_profiles)){
					$ext_host_profiles = get_request('ext_host_profiles',array());

					foreach($host_profile_ext_fields as $field){
						if(isset($visible[$field])){
							$host_profile_ext[$field] = $ext_host_profiles[$field];
						}
					}

					$result &= add_host_profile_ext($hostid,$host_profile_ext);
				}
			}
		}

		$result = DBend($result);

		$msg_ok 	= S_HOSTS.SPACE.S_UPDATED;
		$msg_fail 	= S_CANNOT_UPDATE.SPACE.S_HOSTS;

		show_messages($result, $msg_ok, $msg_fail);

		if($result){
			unset($_REQUEST['massupdate']);
			unset($_REQUEST['form']);
			unset($_REQUEST['hosts']);

			$url = new CUrl();
			$path = $url->getPath();
			insert_js('cookie.eraseArray("'.$path.'")');
		}

		unset($_REQUEST['save']);
	}
// SAVE HOST
	else if(isset($_REQUEST['save'])){
		$useipmi = isset($_REQUEST['useipmi']) ? 1 : 0;
		$templates = get_request('templates', array());
		$templates_clear = get_request('clear_templates', array());
		$proxy_hostid = get_request('proxy_hostid', 0);
		$groups = get_request('groups', array());

		$result = true;

		if(!count(get_accessible_nodes_by_user($USER_DETAILS,PERM_READ_WRITE,PERM_RES_IDS_ARRAY))) access_deny();


		$clone_hostid = false;
		if($_REQUEST['form'] == 'full_clone'){
			$clone_hostid = $_REQUEST['hostid'];
			unset($_REQUEST['hostid']);
		}

		$templates = array_keys($templates);
		$templates = zbx_toObject($templates, 'templateid');
		$templates_clear = zbx_toObject($templates_clear, 'templateid');

// START SAVE TRANSACTION {{{
		DBstart();

		$groups = zbx_toObject($groups, 'groupid');
		if(!empty($_REQUEST['newgroup'])){
			if($newgroup = CHostGroup::create(array('name' => $_REQUEST['newgroup']))){
				$groups = array_merge($groups, $newgroup);
			}
			else{
				$result = false;
			}
		}

		if($result){
			if(isset($_REQUEST['hostid'])){
				if($result){
					$result = CHost::update(array(
						'hostid' => $_REQUEST['hostid'],
						'host' => $_REQUEST['host'],
						'port' => $_REQUEST['port'],
						'status' => $_REQUEST['status'],
						'useip' => $_REQUEST['useip'],
						'dns' => $_REQUEST['dns'],
						'ip' => $_REQUEST['ip'],
						'proxy_hostid' => $proxy_hostid,
						'useipmi' => $useipmi,
						'ipmi_ip' => $_REQUEST['ipmi_ip'],
						'ipmi_port' => $_REQUEST['ipmi_port'],
						'ipmi_authtype' => $_REQUEST['ipmi_authtype'],
						'ipmi_privilege' => $_REQUEST['ipmi_privilege'],
						'ipmi_username' => $_REQUEST['ipmi_username'],
						'ipmi_password' => $_REQUEST['ipmi_password'],
						'groups' => $groups,
						'templates' => $templates,
						'templates_clear' => $templates_clear,
						'macros' => get_request('macros', array()),
					));

					$msg_ok = S_HOST_UPDATED;
					$msg_fail = S_CANNOT_UPDATE_HOST;

					$hostid = $_REQUEST['hostid'];
				}
			}
			else{
				$host = CHost::create(array(
					'host' => $_REQUEST['host'],
					'port' => $_REQUEST['port'],
					'status' => $_REQUEST['status'],
					'useip' => $_REQUEST['useip'],
					'dns' => $_REQUEST['dns'],
					'ip' => $_REQUEST['ip'],
					'proxy_hostid' => $proxy_hostid,
					'templates' => $templates,
					'useipmi' => $useipmi,
					'ipmi_ip' => $_REQUEST['ipmi_ip'],
					'ipmi_port' => $_REQUEST['ipmi_port'],
					'ipmi_authtype' => $_REQUEST['ipmi_authtype'],
					'ipmi_privilege' => $_REQUEST['ipmi_privilege'],
					'ipmi_username' => $_REQUEST['ipmi_username'],
					'ipmi_password' => $_REQUEST['ipmi_password'],
					'groups' => $groups,
					'templates' => $templates,
					'macros' => get_request('macros', array()),
				));

				$result &= (bool) $host;
				if($result){
					$host = reset($host);
					$hostid = $host['hostid'];
				}

				$msg_ok = S_HOST_ADDED;
				$msg_fail = S_CANNOT_ADD_HOST;
			}

		}

// FULL CLONE {{{
		if($result && $clone_hostid && ($_REQUEST['form'] == 'full_clone')){
// Host applications
			$sql = 'SELECT * FROM applications WHERE hostid='.$clone_hostid.' AND templateid=0';
			$res = DBselect($sql);
			while($db_app = DBfetch($res)){
				add_application($db_app['name'], $hostid, 0);
			}

// Host items
			$sql = 'SELECT DISTINCT i.itemid, i.description '.
					' FROM items i '.
					' WHERE i.hostid='.$clone_hostid.
						' AND i.templateid=0 '.
					' ORDER BY i.description';

			$res = DBselect($sql);
			while($db_item = DBfetch($res)){
				$result &= (bool) copy_item_to_host($db_item['itemid'], $hostid, true);
			}

// Host triggers
			$triggers = CTrigger::get(array('hostids' => $clone_hostid, 'inherited' => 0));
			$triggers = zbx_objectValues($triggers, 'triggerid');
			foreach($triggers as $trigger){
				$result &= (bool) copy_trigger_to_host($trigger, $hostid, true);
			}

// Host graphs
			$graphs = CGraph::get(array('hostids' => $clone_hostid, 'inherited' => 0));

			foreach($graphs as $graph){
				$result &= (bool) copy_graph_to_host($graph['graphid'], $hostid, true);
			}

			$_REQUEST['hostid'] = $clone_hostid;
		}

// }}} FULL CLONE

//HOSTS PROFILE Section
		if($result){
			update_profile('HOST_PORT', $_REQUEST['port'], PROFILE_TYPE_INT);

			if(isset($_REQUEST['hostid'])){
				delete_host_profile($hostid);
			}

			if(get_request('useprofile', 'no') == 'yes'){
				$result = add_host_profile($hostid,
					$_REQUEST['devicetype'],$_REQUEST['name'],$_REQUEST['os'],
					$_REQUEST['serialno'],$_REQUEST['tag'],$_REQUEST['macaddress'],
					$_REQUEST['hardware'],$_REQUEST['software'],$_REQUEST['contact'],
					$_REQUEST['location'],$_REQUEST['notes']);
			}
		}

//HOSTS PROFILE EXTANDED Section
		if($result){
			if(isset($_REQUEST['hostid'])){
				delete_host_profile_ext($hostid);
			}

			$ext_host_profiles = get_request('ext_host_profiles', array());
			if((get_request('useprofile_ext', 'no') == 'yes') && !empty($ext_host_profiles)){
				$result = add_host_profile_ext($hostid, $ext_host_profiles);
			}
		}

// }}} START SAVE TRANSACTION
		$result	= DBend($result);

		show_messages($result, $msg_ok, $msg_fail);

		if($result){
			unset($_REQUEST['form']);
			unset($_REQUEST['hostid']);
		}

		unset($_REQUEST['save']);
	}
// DELETE HOST
	else if(isset($_REQUEST['delete']) && isset($_REQUEST['hostid'])){

		DBstart();
			$result = delete_host($_REQUEST['hostid']);
		$result = DBend($result);

		show_messages($result, S_HOST_DELETED, S_CANNOT_DELETE_HOST);

		if($result){
			unset($_REQUEST['form']);
			unset($_REQUEST['hostid']);
		}
		unset($_REQUEST['delete']);
	}
	else if(isset($_REQUEST['chstatus']) && isset($_REQUEST['hostid'])){

		DBstart();
			$result = update_host_status($_REQUEST['hostid'], $_REQUEST['chstatus']);
		$result = DBend($result);

		show_messages($result,S_HOST_STATUS_UPDATED,S_CANNOT_UPDATE_HOST_STATUS);

		unset($_REQUEST['chstatus']);
		unset($_REQUEST['hostid']);
	}

// -------- GO ---------------
// DELETE HOST
	else if($_REQUEST['go'] == 'delete'){
		$hosts = get_request('hosts', array());
		$hosts = zbx_toObject($hosts,'hostid');

		$go_result = true;
		DBstart();
		foreach($hosts as $num => $host){
			$go_result = CHost::delete($host);

			if(!$go_result) break;
			$go_result = reset($go_result);
			add_audit(AUDIT_ACTION_DELETE, AUDIT_RESOURCE_HOST, 'Host ['.$go_result['host'].']');
		}
		$go_result = DBend($go_result);

		if(!$go_result){
			error(CHost::resetErrors());
		}

		show_messages($go_result, S_HOST_DELETED, S_CANNOT_DELETE_HOST);
	}
// ACTIVATE/DISABLE HOSTS
	else if(str_in_array($_REQUEST['go'], array('activate', 'disable'))){

		$status = ($_REQUEST['go'] == 'activate') ? HOST_STATUS_MONITORED : HOST_STATUS_NOT_MONITORED;
		$hosts = get_request('hosts', array());

		$act_hosts = available_hosts($hosts, 1);

		DBstart();
		$go_result = update_host_status($act_hosts, $status);
		$go_result = DBend($go_result);

		show_messages($go_result, S_HOST_STATUS_UPDATED, S_CANNOT_UPDATE_HOST);
	}

	if(($_REQUEST['go'] != 'none') && isset($go_result) && $go_result){
		$url = new CUrl();
		$path = $url->getPath();
		insert_js('cookie.eraseArray("'.$path.'")');
	}
?>
<?php

	$frmForm = new CForm();
	$cmbConf = new CComboBox('config', 'hosts.php', 'javascript: redirect(this.options[this.selectedIndex].value);');
		$cmbConf->addItem('templates.php', S_TEMPLATES);
		$cmbConf->addItem('hosts.php', S_HOSTS);
		$cmbConf->addItem('items.php', S_ITEMS);
		$cmbConf->addItem('triggers.php', S_TRIGGERS);
		$cmbConf->addItem('graphs.php', S_GRAPHS);
		$cmbConf->addItem('applications.php', S_APPLICATIONS);
	$frmForm->addItem($cmbConf);

	if(!isset($_REQUEST['form'])){
		$frmForm->addItem(new CButton('form',S_CREATE_HOST));
	}

	show_table_header(S_CONFIGURATION_OF_HOSTS, $frmForm);

// TODO: neponjatno pochemu hostid sbrasivaetsja no on nuzhen dlja formi
$thid = get_request('hostid', 0);

	$params=array();
	$options = array('only_current_node');
	foreach($options as $option) $params[$option] = 1;

	$PAGE_GROUPS = get_viewed_groups(PERM_READ_WRITE, $params);
	$PAGE_HOSTS = get_viewed_hosts(PERM_READ_WRITE, $PAGE_GROUPS['selected'], $params);

	validate_group($PAGE_GROUPS,$PAGE_HOSTS);

$_REQUEST['hostid'] = $thid;
?>
<?php
	echo SBR;

	if(($_REQUEST['go'] == 'massupdate') && isset($_REQUEST['hosts'])){
		insert_mass_update_host_form();
	}
	else if(isset($_REQUEST['form'])){
		insert_host_form(false);
	}
	else{
		$hosts_wdgt = new CWidget();

		$frmForm = new CForm();
		$frmForm->setMethod('get');

		$groups = CHostGroup::get(array('editable' => 1, 'extendoutput' => 1));
		order_result($groups, 'name');

		$cmbGroups = new CComboBox('groupid', $PAGE_GROUPS['selected'], 'javascript: submit();');
		foreach($PAGE_GROUPS['groups'] as $groupid => $name){
			$cmbGroups->addItem($groupid, $name);
		}
		$frmForm->addItem(array(S_GROUP.SPACE, $cmbGroups));

		$numrows = new CDiv();
		$numrows->setAttribute('name', 'numrows');

		$hosts_wdgt->addHeader(S_HOSTS_BIG, $frmForm);
		$hosts_wdgt->addHeader($numrows);

// table HOSTS
		$form = new CForm();
		$form->setName('hosts');

		$table = new CTableInfo(S_NO_HOSTS_DEFINED);
		$table->setHeader(array(
			new CCheckBox('all_hosts', NULL, "checkAll('" . $form->getName() . "','all_hosts','hosts');"),
			make_sorting_header(S_NAME, 'host'),
			S_APPLICATIONS,
			S_ITEMS,
			S_TRIGGERS,
			S_GRAPHS,
			make_sorting_header(S_DNS, 'dns'),
			make_sorting_header(S_IP, 'ip'),
			S_PORT,
			S_TEMPLATES,
			make_sorting_header(S_STATUS, 'status'),
			S_AVAILABILITY
		));


		$sortfield = getPageSortField('host');
		$sortorder = getPageSortOrder();
		$options = array(
			'extendoutput' => 1,
			'editable' => 1,
			'sortfield' => $sortfield,
			'sortorder' => $sortorder,
			'limit' => ($config['search_limit']+1)
		);

		if(($PAGE_GROUPS['selected'] > 0) || empty($PAGE_GROUPS['groupids'])){
			$options['groupids'] = $PAGE_GROUPS['selected'];
		}

		$hosts = CHost::get($options);

// sorting && paging
		order_result($hosts, $sortfield, $sortorder);
		$paging = getPagingLine($hosts);
//---------

		$options = array(
			'hostids' => zbx_objectValues($hosts, 'hostid'),
			'extendoutput' => 1,
			'select_templates' => 1,
			'select_items' => 1,
			'select_triggers' => 1,
			'select_graphs' => 1,
			'select_applications' => 1,
			'nopermissions' => 1
		);
		$hosts = CHost::get($options);
// sorting && paging
		order_result($hosts, $sortfield, $sortorder);
//---------

		foreach($hosts as $num => $host){
			$applications = array(new CLink(S_APPLICATIONS, 'applications.php?groupid='.$PAGE_GROUPS['selected'].'&hostid='.$host['hostid']),
				' ('.count($host['applications']).')');
			$items = array(new CLink(S_ITEMS, 'items.php?filter_set=1&hostid='.$host['hostid']),
				' ('.count($host['items']).')');
			$triggers = array(new CLink(S_TRIGGERS, 'triggers.php?groupid='.$PAGE_GROUPS['selected'].'&hostid='.$host['hostid']),
				' ('.count($host['triggers']).')');
			$graphs = array(new CLink(S_GRAPHS, 'graphs.php?groupid='.$PAGE_GROUPS['selected'].'&hostid='.$host['hostid']),
				' ('.count($host['graphs']).')');

			$description = array();
			if($host['proxy_hostid']){
				$proxy = CHost::get(array('hostids' => $host['proxy_hostid'], 'extendoutput' => 1, 'nopermissions' => 1, 'proxy_hosts' => 1));
				$proxy = reset($proxy);
				$description[] = $proxy['host'] . ':';
			}
			$description[] = new CLink($host['host'], 'hosts.php?form=update&hostid='.$host['hostid'].url_param('groupid'));

			$dns = empty($host['dns']) ? '-' : $host['dns'];
			$ip = empty($host['ip']) ? '-' : $host['ip'];
			$use = (1 == $host['useip']) ? 'ip' : 'dns';
			$$use = bold($$use);

			switch($host['status']){
				case HOST_STATUS_MONITORED:
					$status = new CLink(S_MONITORED, 'hosts.php?hosts%5B%5D='.$host['hostid'].'&go=disable'.url_param('groupid'), 'off');
					break;
				case HOST_STATUS_NOT_MONITORED:
					$status = new CLink(S_NOT_MONITORED, 'hosts.php?hosts%5B%5D='.$host['hostid'].'&go=activate'.url_param('groupid'), 'on');
					break;
				default:
					$status = S_UNKNOWN;
			}

			switch($host['available']){
				case HOST_AVAILABLE_TRUE:
					$zbx_available = new CDiv(SPACE, 'iconzbxavailable');
					break;
				case HOST_AVAILABLE_FALSE:
					$zbx_available = new CDiv(SPACE, 'iconzbxunavailable');
					$zbx_available->setHint($host['error'], '', 'on');
					break;
				case HOST_AVAILABLE_UNKNOWN:
					$zbx_available = new CDiv(SPACE, 'iconzbxunknown');
					break;
			}

			switch($host['snmp_available']){
				case HOST_AVAILABLE_TRUE:
					$snmp_available = new CDiv(SPACE, 'iconsnmpavailable');
					break;
				case HOST_AVAILABLE_FALSE:
					$snmp_available = new CDiv(SPACE, 'iconsnmpunavailable');
					$snmp_available->setHint($host['snmp_error'], '', 'on');
					break;
				case HOST_AVAILABLE_UNKNOWN:
					$snmp_available = new CDiv(SPACE, 'iconsnmpunknown');
					break;
			}

			switch($host['ipmi_available']){
				case HOST_AVAILABLE_TRUE:
					$ipmi_available = new CDiv(SPACE, 'iconipmiavailable');
					break;
				case HOST_AVAILABLE_FALSE:
					$ipmi_available = new CDiv(SPACE, 'iconipmiunavailable');
					$ipmi_available->setHint($host['ipmi_error'], '', 'on');
					break;
				case HOST_AVAILABLE_UNKNOWN:
					$ipmi_available = new CDiv(SPACE, 'iconipmiunknown');
					break;
			}

			$av_table = new CTable(null, 'invisible');
			$av_table->addRow(array($zbx_available, $snmp_available, $ipmi_available));

			if(empty($host['templates'])){
				$templates = '-';
			}
			else{
				$templates = array();
				foreach($host['templates'] as $templateid => $template){
					$templates[] = $template['host'];
				}
				order_result($templates, 'host');
				$templates = implode(', ', $templates);
			}

			$table->addRow(array(
				new CCheckBox('hosts['.$host['hostid'].']',NULL,NULL,$host['hostid']),
				$description,
				$applications,
				$items,
				$triggers,
				$graphs,
				$dns,
				$ip,
				empty($host['port']) ? '-' : $host['port'],
				new CCol($templates, 'wraptext'),
				$status,
				$av_table
			));
		}

//----- GO ------
		$goBox = new CComboBox('go');
		$goBox->addItem('massupdate',S_MASS_UPDATE);

		$goOption = new CComboItem('activate',S_ACTIVATE_SELECTED);
		$goOption->setAttribute('confirm','Enable selected host?');
		$goBox->addItem($goOption);

		$goOption = new CComboItem('disable',S_DISABLE_SELECTED);
		$goOption->setAttribute('confirm','Disable selected hosts?');
		$goBox->addItem($goOption);

		$goOption = new CComboItem('delete',S_DELETE_SELECTED);
		$goOption->setAttribute('confirm','Delete selected hosts?');
		$goBox->addItem($goOption);

// goButton name is necessary!!!
		$goButton = new CButton('goButton', S_GO);
		$goButton->setAttribute('id', 'goButton');
		zbx_add_post_js('chkbxRange.pageGoName = "hosts";');

		$footer = get_table_header(array($goBox, $goButton));
//----

// PAGING FOOTER
		$table = array($paging, $table, $paging, $footer);
//---------
		$form->addItem($table);
		$hosts_wdgt->addItem($form);
		$hosts_wdgt->show();
	}

include_once('include/page_footer.php');
?>
