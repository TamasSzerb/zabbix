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

$page['title'] = 'S_HOST_GROUPS';
$page['file'] = 'hostgroups.php';
$page['hist_arg'] = array();

include_once('include/page_header.php');
?>
<?php
//		VAR			TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION
	$fields=array(
/* ARRAYS */
		'hosts'=>				array(T_ZBX_INT, O_OPT,	P_SYS,		DB_ID, 	NULL),
		'groups'=>				array(T_ZBX_INT, O_OPT,	P_SYS,		DB_ID, 	NULL),
		'hostids'=>				array(T_ZBX_INT, O_OPT,	P_SYS,		DB_ID, 	NULL),
		'groupids'=>			array(T_ZBX_INT, O_OPT,	P_SYS,		DB_ID, 	NULL),
/* group */
		'groupid'=>				array(T_ZBX_INT, O_OPT,	P_SYS,		DB_ID,		'(isset({form})&&({form}=="update"))'),
		'gname'=>				array(T_ZBX_STR, O_OPT,	NULL,		NOT_EMPTY,	'isset({save})'),
		'twb_groupid'=> 		array(T_ZBX_INT, O_OPT,	P_SYS,			DB_ID,		NULL),
/* actions */
		'go'=>					array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
// form
		'save'=>				array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'clone'=>				array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'delete'=>				array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'cancel'=>				array(T_ZBX_STR, O_OPT, P_SYS,			NULL,	NULL),
/* other */
		'form'=>				array(T_ZBX_STR, O_OPT, P_SYS,			NULL,	NULL),
		'form_refresh'=>		array(T_ZBX_STR, O_OPT, NULL,			NULL,	NULL)
	);
	check_fields($fields);
	validate_sort_and_sortorder('name',ZBX_SORT_UP);

	$_REQUEST['go'] = get_request('go','none');
	
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
/*** <--- ACTIONS ---> ***/
	if(isset($_REQUEST['clone']) && isset($_REQUEST['groupid'])){
		unset($_REQUEST['groupid']);
		$_REQUEST['form'] = 'clone';
	}
	else if(isset($_REQUEST['save'])){
		$hosts = get_request('hosts', array());
		$hosts = available_hosts($hosts, 1);

		if(isset($_REQUEST['groupid'])){
			DBstart();
			$result = update_host_group($_REQUEST['groupid'], $_REQUEST['gname'], $hosts);
			$result = DBend($result);

			$msg_ok		= S_GROUP_UPDATED;
			$msg_fail	= S_CANNOT_UPDATE_GROUP;
		}
		else{
			if(!count(get_accessible_nodes_by_user($USER_DETAILS,PERM_READ_WRITE,PERM_RES_IDS_ARRAY)))
				access_deny();

			DBstart();
			$groupid = add_host_group($_REQUEST['gname'], $hosts);
			$result = DBend($groupid);

			$msg_ok		= S_GROUP_ADDED;
			$msg_fail	= S_CANNOT_ADD_GROUP;
		}
		show_messages($result, $msg_ok, $msg_fail);
		if($result){
			unset($_REQUEST['form']);
		}
		unset($_REQUEST['save']);
	}
	else if(isset($_REQUEST['delete']) && isset($_REQUEST['groupid'])){
		$result = false;
		DBstart();
		$result = delete_host_group($_REQUEST['groupid']);
		$result = DBend($result);

		unset($_REQUEST['form']);
		show_messages($result, S_GROUP_DELETED, S_CANNOT_DELETE_GROUP);
		unset($_REQUEST['groupid']);
	}
// --------- GO  ----------
	else if($_REQUEST['go'] == 'delete'){
/* group operations */
			$go_result = true;

			$groups = get_request('groups', array());

			DBstart();
			$sql = 'SELECT g.groupid, g.name '.
					' FROM groups g '.
					' WHERE '.DBin_node('g.groupid').
						' AND '.DBcondition('g.groupid', $groups);
			$db_groups = DBselect($sql);
			while($db_group=DBfetch($db_groups)){
				if(!isset($groups[$db_group['groupid']])) continue;

/*				if(!$group = get_hostgroup_by_groupid($db_group['groupid'])) continue;*/
				$go_result &= delete_host_group($db_group['groupid']);

/*
				if($result){
					add_audit(AUDIT_ACTION_DELETE,AUDIT_RESOURCE_HOST_GROUP,
					S_HOST_GROUP.' ['.$group['name'].' ] ['.$group['groupid'].']');
				}
//*/
			}
			$go_result = DBend($go_result);
			show_messages($go_result, S_GROUP_DELETED, S_CANNOT_DELETE_GROUP);
		}
	else if(str_in_array($_REQUEST['go'], array('activate', 'disable'))){

		$status = ($_REQUEST['go'] == 'activate') ? HOST_STATUS_MONITORED : HOST_STATUS_NOT_MONITORED;
		$groups = get_request('groups', array());
		if(!empty($groups)){
			DBstart();
			$hosts = CHost::get(array('groupids' => $groups, 'editable' => 1));

			$go_result = CHost::massUpdate(array('hosts' => $hosts, 'status' => $status));
			if(!$go_result)
				error(CHost::resetErrors());

			$go_result = DBend($go_result);

			show_messages($go_result, S_HOST_STATUS_UPDATED, S_CANNOT_UPDATE_HOST);
		}
	}

	if(($_REQUEST['go'] != 'none') && isset($go_result) && $go_result){
		$url = new CUrl();
		$path = $url->getPath();
		insert_js('cookie.eraseArray("'.$path.'")');
	}


	if(isset($_REQUEST['form'])){
		$frmForm = null;
	}
	else{
		$frmForm = new CForm();
		$frmForm->addItem(new CButton('form', S_CREATE_GROUP));
	}
	show_table_header(S_CONFIGURATION_OF_GROUPS, $frmForm);

	echo SBR;

	if(isset($_REQUEST['form'])){
		global $USER_DETAILS;

		$groupid = get_request('groupid', 0);
		$hosts = get_request('hosts', array());

		$group_name = get_request('gname', '');
		$frm_title = S_HOST_GROUP;
		if($groupid > 0){
			$group = get_hostgroup_by_groupid($groupid);
			$frm_title .= ' ['.$group['name'].']';

// if first time select all hosts for group from db
			if(!isset($_REQUEST['form_refresh'])){
				$group_name = $group['name'];

				$params = array(
					'groupids' => $groupid,
					'editable' => 1,
					'sortfield' => 'host',
					'templated_hosts' => 1);
				$db_hosts = CHost::get($params);
				$hosts = zbx_toHash($db_hosts, 'hostid');
			}
		}

		$frmHostG = new CFormTable($frm_title, 'hostgroups.php');
		$frmHostG->setName('hg_form');

		if($groupid > 0){
			$frmHostG->addVar('groupid', $groupid);
		}

		$frmHostG->addRow(S_GROUP_NAME, new CTextBox('gname', $group_name, 48));

// select all possible groups
		$params = array(
			'not_proxy_host' => 1,
			'sortfield' => 'name',
			'editable' => 1,
			'extendoutput' => 1);
		$db_groups = CHostGroup::get($params);
		$twb_groupid = get_request('twb_groupid', 0);
		if($twb_groupid == 0){
			$gr = reset($db_groups);
			$twb_groupid = $gr['groupid'];
		}

		$cmbGroups = new CComboBox('twb_groupid', $twb_groupid, 'submit()');
		foreach($db_groups as $row){
			$cmbGroups->addItem($row['groupid'], $row['name']);
		}

		$cmbHosts = new CTweenBox($frmHostG, 'hosts', $hosts, 25);

// get hosts from selected twb_groupid combo
		$params = array(
			'groupids' => $twb_groupid,
			'templated_hosts' => 1,
			'sortfield' => 'host',
			'editable' => 1,
			'extendoutput' => 1);
		$db_hosts = CHost::get($params);
		foreach($db_hosts as $num => $db_host){
// add all except selected hosts
			if(!isset($hosts[$db_host['hostid']]))
				$cmbHosts->addItem($db_host['hostid'], $db_host['host']);
		}

// select selected hosts and add them
		$params = array(
			'hostids' => $hosts,
			'templated_hosts' =>1 ,
			'sortfield' => 'host',
			'editable' => 1,
			'extendoutput' => 1);
		$db_hosts = CHost::get($params);
		foreach($db_hosts as $num => $db_host){
			$cmbHosts->addItem($db_host['hostid'], $db_host['host']);
		}

		$frmHostG->addRow(S_HOSTS, $cmbHosts->Get(S_HOSTS.SPACE.S_IN,array(S_OTHER.SPACE.S_HOSTS.SPACE.'|'.SPACE.S_GROUP.SPACE, $cmbGroups)));

		$frmHostG->addItemToBottomRow(new CButton('save',S_SAVE));
		if($groupid > 0){
			$frmHostG->addItemToBottomRow(SPACE);
			$frmHostG->addItemToBottomRow(new CButton('clone',S_CLONE));
			$frmHostG->addItemToBottomRow(SPACE);

			$dltButton = new CButtonDelete('Delete selected group?', url_param('form').url_param('groupid'));
			$dlt_groups = getDeletableHostGroups($_REQUEST['groupid']);

			if(empty($dlt_groups)) $dltButton->setAttribute('disabled', 'disabled');

			$frmHostG->addItemToBottomRow($dltButton);
		}
		$frmHostG->addItemToBottomRow(SPACE);
		$frmHostG->addItemToBottomRow(new CButtonCancel(url_param('config')));
		$frmHostG->show();
	}
	else{
		$groups_wdgt = new CWidget();

		$numrows = new CDiv();
		$numrows->setAttribute('name','numrows');

		$groups_wdgt->addHeader(S_HOST_GROUPS_BIG);
		$groups_wdgt->addHeader($numrows);

// Host Groups table
		$form = new CForm('hostgroups.php');
		$form->setName('form_groups');

		$table = new CTableInfo(S_NO_HOST_GROUPS_DEFINED);
		$table->setHeader(array(
			new CCheckBox('all_groups', NULL, "checkAll('".$form->getName()."','all_groups','groups');"),
			make_sorting_header(S_NAME, 'name'),
			' # ',
			S_MEMBERS
		));

		$sortfield = getPageSortField('name');
		$sortorder =  getPageSortOrder();
		$options = array(
			'editable' => 1,
			'extendoutput' => 1,
			'sortfield' => $sortfield,
			'sortorder' => $sortorder,
			'limit' => ($config['search_limit']+1)
		);
		$groups = CHostGroup::get($options);

		order_result($groups, $sortfield, $sortorder);
		$paging = getPagingLine($groups);
//-----

		$options = array(
			'groupids' => zbx_objectValues($groups, 'groupid'),
			'extendoutput' => 1,
			'select_hosts' => 1,
			'nopermissions' => 1
		);

// sorting && paging
		$groups = CHostGroup::get($options);
		order_result($groups, $sortfield, $sortorder);
//---------

		foreach($groups as $num => $group){
			$tpl_count = 0;
			$host_count = 0;
			$i = 0;
			$hosts_output = array();

			order_result($group['hosts'], 'host');
			foreach($group['hosts'] as $host){
				$i++;

				if($i > $config['max_in_table']){
					$hosts_output[] = '...';
					$hosts_output[] = '//empty for array_pop';
					break;
				}

				switch($host['status']){
					case HOST_STATUS_NOT_MONITORED:
						$style = 'on';
						$url = 'hosts.php?form=update&hostid='.$host['hostid'].'&groupid='.$group['groupid'];
					break;
					case HOST_STATUS_TEMPLATE:
						$style = 'unknown';
						$url = 'templates.php?form=update&templateid='.$host['hostid'].'&groupid='.$group['groupid'];
					break;
					default:
						$style = null;
						$url = 'hosts.php?form=update&hostid='.$host['hostid'].'&groupid='.$group['groupid'];
					break;
				}
				$hosts_output[] = new CLink($host['host'], $url, $style);
				$hosts_output[] = ', ';

			}
			array_pop($hosts_output);

			foreach($group['hosts'] as $host){
				$host['status'] == HOST_STATUS_TEMPLATE ? $tpl_count++ : $host_count++;
			}

			$table->addRow(array(
				new CCheckBox('groups['.$group['groupid'].']', NULL, NULL, $group['groupid']),
				new CLink($group['name'], 'hostgroups.php?form=update&groupid='.$group['groupid']),
				array(
					array(new CLink(S_HOSTS, 'hosts.php?groupid='.$group['groupid']),' ('.$host_count.')'),
					BR(),
					array(new CLink(S_TEMPLATES, 'templates.php?groupid='.$group['groupid'], 'unknown'), ' ('.$tpl_count.')'),
				),
				new CCol((empty($hosts_output) ? '-' : $hosts_output), 'wraptext')
			));
		}

//----- GO ------
		$goBox = new CComboBox('go');
		$goOption = new CComboItem('activate',S_ACTIVATE_SELECTED);
		$goOption->setAttribute('confirm','Enable selected Host Groups?');
		$goBox->addItem($goOption);

		$goOption = new CComboItem('disable',S_DISABLE_SELECTED);
		$goOption->setAttribute('confirm','Disable selected Host Groups?');
		$goBox->addItem($goOption);

		$goOption = new CComboItem('delete',S_DELETE_SELECTED);
		$goOption->setAttribute('confirm','Delete selected Host Groups?');
		$goBox->addItem($goOption);

// goButton name is necessary!!!
		$goButton = new CButton('goButton',S_GO);
		$goButton->setAttribute('id','goButton');
		zbx_add_post_js('chkbxRange.pageGoName = "groups";');

		$footer = get_table_header(array($goBox, $goButton));
//----

// PAGING FOOTER
		$table = array($paging, $table, $paging, $footer);
//---------

		$form->addItem($table);

		$groups_wdgt->addItem($form);
		$groups_wdgt->show();
	}

include_once('include/page_footer.php');
?>
