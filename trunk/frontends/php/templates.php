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
require_once('include/forms.inc.php');

	$page['title'] = "S_TEMPLATES";
	$page['file'] = 'templates.php';
	$page['hist_arg'] = array('groupid', 'config');

	include_once('include/page_header.php');

	$_REQUEST['config'] = get_request('config','hosts.php');

	$available_groups = get_accessible_groups_by_user($USER_DETAILS,PERM_READ_WRITE);
	$available_hosts = get_accessible_hosts_by_user($USER_DETAILS,PERM_READ_WRITE);

	if(isset($_REQUEST['groupid']) && ($_REQUEST['groupid']>0) && !isset($available_groups[$_REQUEST['groupid']])){
	access_deny();
	}

	if(isset($_REQUEST['templateid']) && ($_REQUEST['templateid']>0) && !isset($available_hosts[$_REQUEST['templateid']])) {
	access_deny();
}


//		VAR						TYPE		OPTIONAL FLAGS			VALIDATION	EXCEPTION
	$fields=array(
		'config'			=> array(T_ZBX_STR, O_OPT,	P_SYS,			NULL,		NULL),

		'hosts'			=> array(T_ZBX_INT,	O_OPT,	P_SYS,			DB_ID, 		NULL),
		'groups'		=> array(T_ZBX_INT, O_OPT,	P_SYS,			DB_ID, 		NULL),
		'clear_templates'	=> array(T_ZBX_INT, O_OPT,	P_SYS,		DB_ID, 		NULL),
		'templates'		=> array(T_ZBX_STR, O_OPT,	NULL,			NULL,		NULL),
		'templateid'		=> array(T_ZBX_INT,	O_OPT,	P_SYS,		DB_ID,		'isset({form})&&({form}=="update")'),
		'template_name'		=> array(T_ZBX_STR,	O_OPT,	NOT_EMPTY,	NULL,		'isset({save})'),
		'groupid'		=> array(T_ZBX_INT, O_OPT,	P_SYS,			DB_ID,		NULL),
		'twb_groupid'		=> array(T_ZBX_INT, O_OPT,	P_SYS,		DB_ID,		NULL),
		'newgroup'		=> array(T_ZBX_STR, O_OPT,	NULL,			NULL,		NULL),

		'macros_rem'=>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'macros'=>				array(T_ZBX_STR, O_OPT, P_SYS,			NULL,	NULL),
		'macro_new'=>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	'isset({macro_add})'),
		'value_new'=>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	'isset({macro_add})'),
		'macro_add' =>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'macros_del' =>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),

// actions
		'go'			=> array(T_ZBX_STR, O_OPT,	P_SYS|P_ACT,	NULL,		NULL),

//form
		'unlink'		=> array(T_ZBX_STR, O_OPT,	P_SYS|P_ACT,	NULL,		NULL),
		'unlink_and_clear'	=> array(T_ZBX_STR, O_OPT,	P_SYS|P_ACT,	NULL,		NULL),
		'save'			=> array(T_ZBX_STR, O_OPT,	P_SYS|P_ACT,	NULL,		NULL),
		'clone'			=> array(T_ZBX_STR, O_OPT,	P_SYS|P_ACT,	NULL,		NULL),
		'full_clone'		=> array(T_ZBX_STR, O_OPT,	P_SYS|P_ACT,	NULL,		NULL),
		'delete'		=> array(T_ZBX_STR, O_OPT,	P_SYS|P_ACT,	NULL,		NULL),
		'delete_and_clear'	=> array(T_ZBX_STR, O_OPT,	P_SYS|P_ACT,	NULL,		NULL),
		'cancel'		=> array(T_ZBX_STR, O_OPT,	P_SYS,			NULL,		NULL),


// other
		'form'			=> array(T_ZBX_STR, O_OPT,	P_SYS,			NULL,		NULL),
		'form_refresh'		=> array(T_ZBX_STR, O_OPT,	NULL,			NULL,		NULL)
	);

	check_fields($fields);
	validate_sort_and_sortorder('host',ZBX_SORT_UP);

	$_REQUEST['go'] = get_request('go','none');
?>
<?php
/**********************************/
/* <<<--- TEMPLATE ACTIONS --->>> */
/**********************************/
/* REMOVE MACROS */
	if(isset($_REQUEST['macros_del']) && isset($_REQUEST['macros_rem'])){
		$macros_rem = get_request('macros_rem', array());
		foreach($macros_rem as $macro)
			unset($_REQUEST['macros'][$macro]);
	}
/* ADD MACRO */
	if(isset($_REQUEST['macro_add'])){
		$macro_new = get_request('macro_new');
		$value_new = get_request('value_new', null);

		$currentmacros = array_keys(get_request('macros', array()));

		if(!CUserMacro::validate($macro_new)){
			error(S_WRONG_MACRO.' : '.$macro_new);
			show_messages(false, '', S_MACROS);
		}
		else if(zbx_empty($value_new)){
			error(S_EMPTY_MACRO_VALUE);
			show_messages(false, '', S_MACROS);
		}
		else if(str_in_array($macro_new, $currentmacros)){
			error(S_MACRO_EXISTS.' : '.$macro_new);
			show_messages(false, '', S_MACROS);
		}
		else if(strlen($macro_new) > 64){
			error(S_MACRO_TOO_LONG.' : '.$macro_new);
			show_messages(false, '', S_MACROS);
		}
		else if(strlen($value_new) > 255){
			error(S_MACRO_VALUE_TOO_LONG.' : '.$value_new);
			show_messages(false, '', S_MACROS);
		}
		else{
			$_REQUEST['macros'][$macro_new]['macro'] = $macro_new;
			$_REQUEST['macros'][$macro_new]['value'] = $value_new;
			unset($_REQUEST['macro_new']);
			unset($_REQUEST['value_new']);
		}
	}// unlink, unlink_and_clear
	if((isset($_REQUEST['unlink']) || isset($_REQUEST['unlink_and_clear']))){
		$_REQUEST['clear_templates'] = get_request('clear_templates', array());
		if(isset($_REQUEST['unlink'])){
			$unlink_templates = array_keys($_REQUEST['unlink']);
		}
		else{
			$unlink_templates = array_keys($_REQUEST['unlink_and_clear']);
			$_REQUEST['clear_templates'] = zbx_array_merge($_REQUEST['clear_templates'],$unlink_templates);
		}
		foreach($unlink_templates as $id) unset($_REQUEST['templates'][$id]);
	}

// clone
	else if(isset($_REQUEST['clone']) && isset($_REQUEST['templateid'])){
		unset($_REQUEST['templateid']);
		$_REQUEST['form'] = 'clone';
	}

// full_clone
	else if(isset($_REQUEST['full_clone']) && isset($_REQUEST['templateid'])){
		$_REQUEST['form'] = 'full_clone';
	}

// save
	else if(isset($_REQUEST['save'])){

		$groups = get_request('groups', array());
		$hosts = get_request('hosts', array());
		$templates = get_request('templates', array());
		$templates = array_keys($templates);
		$templateid = get_request('templateid', 0);
		$newgroup = get_request('newgroup', 0);
		$template_name = get_request('template_name', '');

		$result = true;


		if(count($groups) > 0){
			$accessible_groups = get_accessible_groups_by_user($USER_DETAILS,PERM_READ_WRITE,PERM_RES_IDS_ARRAY);
			foreach($groups as $gid){
				if(isset($accessible_groups[$gid])) continue;
				access_deny();
			}
		}
		else{
			if(!count(get_accessible_nodes_by_user($USER_DETAILS,PERM_READ_WRITE,PERM_RES_IDS_ARRAY)))
				access_deny();
		}

		$clone_templateid = false;
		if($_REQUEST['form'] == 'full_clone'){
			$clone_templateid = $templateid;
			$templateid = null;
		}

		DBstart();

// CREATE NEW GROUP
		if(!empty($newgroup)){
			if($groupid = CHostGroup::add(array($newgroup))){
				$groups += $groupid;
			}
			else{
				$result = false;
			}
		}

// <<<--- CREATE|UPDATE TEMPLATE WITH GROUPS AND LINKED TEMPLATES --->>>
		if($templateid){
			if(isset($_REQUEST['clear_templates'])) {
				foreach($_REQUEST['clear_templates'] as $id){
					$result &= unlink_template($_REQUEST['templateid'], $id, false);
				}
			}

			$result = CTemplate::update(array(array('hostid' => $templateid, 'host' => $template_name)));
			$result &= CHostGroup::updateGroupsToHost(array('hostid' => $templateid, 'groupids' => $groups));
			$msg_ok 	= S_TEMPLATE_UPDATED;
			$msg_fail 	= S_CANNOT_UPDATE_TEMPLATE;
		}
		else{
			if($result = CTemplate::add(array(array('host' => $template_name, 'groupids' => $groups)))){
				$templateid = reset($result);
			}
			else{
				error(CTemplate::$error['data']);
				$result = false;
			}
			$msg_ok 	= S_TEMPLATE_ADDED;
			$msg_fail 	= S_CANNOT_ADD_TEMPLATE;
		}
		if($result){
			$original_templates = get_templates_by_hostid($templateid);
			$original_templates = array_keys($original_templates);
			$templates_to_link = array_diff($templates, $original_templates);
			$result &= CTemplate::linkTemplates(array('hostid' => $templateid, 'templateids' => $templates_to_link));
		}
// --->>> <<<---
// <<<--- FULL_CLONE --->>>
		if(!zbx_empty($templateid) && $templateid && $clone_templateid && ($_REQUEST['form'] == 'full_clone')){
// Host applications
			$sql = 'SELECT * FROM applications WHERE hostid='.$clone_templateid.' AND templateid=0';
			$res = DBselect($sql);
			while($db_app = DBfetch($res)){
				add_application($db_app['name'], $templateid, 0);
			}

// Host items
			$sql = 'SELECT DISTINCT i.itemid, i.description '.
					' FROM items i '.
					' WHERE i.hostid='.$clone_templateid.
						' AND i.templateid=0 '.
					' ORDER BY i.description';

			$res = DBselect($sql);
			while($db_item = DBfetch($res)){
				$result &= copy_item_to_host($db_item['itemid'], $templateid, true);
			}

// Host triggers
			$available_triggers = get_accessible_triggers(PERM_READ_ONLY, array($clone_templateid), PERM_RES_IDS_ARRAY);

			$sql = 'SELECT DISTINCT t.triggerid, t.description '.
					' FROM triggers t, items i, functions f'.
					' WHERE i.hostid='.$clone_templateid.
						' AND f.itemid=i.itemid '.
						' AND t.triggerid=f.triggerid '.
						' AND '.DBcondition('t.triggerid', $available_triggers).
						' AND t.templateid=0 '.
					' ORDER BY t.description';

			$res = DBselect($sql);
			while($db_trig = DBfetch($res)){
				$result &= copy_trigger_to_host($db_trig['triggerid'], $templateid, true);
			}

// Host graphs
			$available_graphs = get_accessible_graphs(PERM_READ_ONLY, array($clone_templateid), PERM_RES_IDS_ARRAY);

			$sql = 'SELECT DISTINCT g.graphid, g.name '.
						' FROM graphs g, graphs_items gi,items i '.
						' WHERE '.DBcondition('g.graphid',$available_graphs).
							' AND gi.graphid=g.graphid '.
							' AND g.templateid=0 '.
							' AND i.itemid=gi.itemid '.
							' AND i.hostid='.$clone_templateid.
						' ORDER BY g.name';

			$res = DBselect($sql);
			while($db_graph = DBfetch($res)){
				$result &= copy_graph_to_host($db_graph['graphid'], $templateid, true);
			}
		}
// --->>> <<<---
// <<<--- LINK/UNLINK HOSTS --->>>
		if($result){
			$hosts = array_intersect($hosts, $available_hosts);
//-- unlink --
			$linked_hosts = array();
			$db_childs = get_hosts_by_templateid($templateid);
			while($db_child = DBfetch($db_childs)){
				$linked_hosts[$db_child['hostid']] = $db_child['hostid'];
			}

			$unlink_hosts = array_diff($linked_hosts, $hosts);

			foreach($unlink_hosts as $id => $value){
				$result &= unlink_template($value, $templateid, false);
			}

//-- link --
			$link_hosts = array_diff($hosts, $linked_hosts);

			$template_name = DBfetch(DBselect('SELECT host FROM hosts WHERE hostid='.$templateid));

			foreach($link_hosts as $id => $hostid){

				$host_groups=array();
				$db_hosts_groups = DBselect('SELECT groupid FROM hosts_groups WHERE hostid='.$hostid);
				while($hg = DBfetch($db_hosts_groups)) $host_groups[] = $hg['groupid'];

				$host=get_host_by_hostid($hostid);

				$templates_tmp=get_templates_by_hostid($hostid);
				$templates_tmp[$templateid]=$template_name['host'];

				$result &= update_host($hostid,
					$host['host'],$host['port'],$host['status'],$host['useip'],$host['dns'],
					$host['ip'],$host['proxy_hostid'],$templates_tmp,$host['useipmi'],$host['ipmi_ip'],
					$host['ipmi_port'],$host['ipmi_authtype'],$host['ipmi_privilege'],$host['ipmi_username'],
					$host['ipmi_password'],null,$host_groups);
			}
		}
// --->>> <<<---
// MACROS {
		if($result){
			$macros = get_request('macros', array());

			$macrostoadd = array('hostid' => $templateid, 'macros' => array());

			foreach($macros as $macro){
				if(!CUserMacro::validate($macro['macro'])){
					$result = false;
					break;
				}
				$macrostoadd['macros'][] = $macro;
			}

			$result = CUserMacro::update($macrostoadd);

			if(!$result)
				error('S_ERROR_ADDING_MACRO');
		}
// } MACROS
		$result = DBend($result);

		show_messages($result, $msg_ok, $msg_fail);

		if($result){
			unset($_REQUEST['form']);
			unset($_REQUEST['templateid']);
		}
		unset($_REQUEST['save']);
	}

// delete, delete_and_clear
	else if((isset($_REQUEST['delete']) || isset($_REQUEST['delete_and_clear'])) && isset($_REQUEST['templateid'])){
		$unlink_mode = false;
		if(isset($_REQUEST['delete'])){
			$unlink_mode =  true;
		}


		$host=get_host_by_hostid($_REQUEST['templateid']);

		DBstart();
		$result = delete_host($_REQUEST['templateid'], $unlink_mode);
		$result=DBend($result);

		show_messages($result, S_HOST_DELETED, S_CANNOT_DELETE_HOST);
		if($result){
/*				add_audit(AUDIT_ACTION_DELETE,AUDIT_RESOURCE_HOST,'Host ['.$host['host'].']');*/

			unset($_REQUEST['form']);
			unset($_REQUEST['templateid']);
		}

		unset($_REQUEST['delete']);
	}
// ---------- GO ---------
	else if(str_in_array($_REQUEST['go'],array('delete','delete_and_clear')) && isset($_REQUEST['templates'])){
		$unlink_mode = false;
		if(isset($_REQUEST['delete'])){
			$unlink_mode =  true;
		}

		$result = true;
		$hosts = get_request('templates',array());
		$del_hosts = array();
		$sql = 'SELECT host,hostid '.
				' FROM hosts '.
				' WHERE '.DBin_node('hostid').
					' AND '.DBcondition('hostid',$hosts).
					' AND '.DBcondition('hostid',$available_hosts);
		$db_hosts=DBselect($sql);

		DBstart();
		while($db_host=DBfetch($db_hosts)){
			$del_hosts[$db_host['hostid']] = $db_host['hostid'];
	/*				add_audit(AUDIT_ACTION_DELETE,AUDIT_RESOURCE_HOST,'Host ['.$db_host['host'].']');*/
		}

		$result = delete_host($del_hosts, $unlink_mode);
		$result = DBend($result);

		show_messages($result, S_HOST_DELETED, S_CANNOT_DELETE_HOST);
	}
/**********************************/
/* --->>> TEMPLATE ACTIONS <<<--- */
/**********************************/
?>
<?php

	$params = array();
	$options = array('only_current_node');
	if(isset($_REQUEST['form']) || isset($_REQUEST['massupdate'])) array_push($options,'do_not_select_if_empty');

	foreach($options as $option) $params[$option] = 1;
	$PAGE_GROUPS = get_viewed_groups(PERM_READ_WRITE, $params);
	$PAGE_HOSTS = get_viewed_hosts(PERM_READ_WRITE, $PAGE_GROUPS['selected'], $params);

	validate_group($PAGE_GROUPS, $PAGE_HOSTS, false);

	$available_groups = $PAGE_GROUPS['groupids'];
	$available_hosts = $PAGE_HOSTS['hostids'];
/****************************************/
/* <<<--- TEMPLATE LIST AND FORM --->>> */
/****************************************/

	$frmForm = new CForm();
	$frmForm->setMethod('get');
	$frmForm->addVar('groupid', get_request('groupid',0));

	$cmbConf = new CComboBox('config', 'templates.php', 'javascript: redirect(this.options[this.selectedIndex].value);');
	$cmbConf->addItem('templates.php',S_TEMPLATES);
	$cmbConf->addItem('hosts.php',S_HOSTS);
	$cmbConf->addItem('items.php',S_ITEMS);
	$cmbConf->addItem('triggers.php',S_TRIGGERS);
	$cmbConf->addItem('graphs.php',S_GRAPHS);
	$cmbConf->addItem('applications.php',S_APPLICATIONS);

	$frmForm->addItem($cmbConf);

	if(!isset($_REQUEST['form'])){
		$frmForm->addItem(new CButton('form', S_CREATE_TEMPLATE));
	}

	show_table_header(S_CONFIGURATION_OF_TEMPLATES, $frmForm);
	echo SBR;

	if(isset($_REQUEST['form'])){

		$templateid = get_request('templateid', 0);
		$template_name = get_request('template_name', '');
		$newgroup = get_request('newgroup', '');
		$templates = get_request('templates', array());
		$clear_templates = get_request('clear_templates', array());

		$frm_title = S_TEMPLATE;

		if($templateid>0){
			$db_host=get_host_by_hostid($templateid);
			$template_name = $db_host['host'];
			$frm_title	.= SPACE.' ['.$template_name.']';

			$original_templates = get_templates_by_hostid($templateid);
		}
		else{
			$original_templates = array();
		}

		$frmHost = new CForm('templates.php', 'post');
		$frmHost->setName('tpl_for');

		$frmHost->addVar('form', get_request('form', 1));
		$from_rfr = get_request('form_refresh',0);
		$frmHost->addVar('form_refresh', $from_rfr+1);
		$frmHost->addVar('clear_templates',$clear_templates);

		if($templateid){
			$frmHost->addVar('templateid', $templateid);
		}

		if(($templateid > 0) && !isset($_REQUEST['form_refresh'])){
// get template groups from db
			$options = array('hostids' => $templateid, 'editable' => 1);
			$groups = CHostGroup::get($options);

// get template hosts from db
			$params = array('templateids' => $templateid,
							'editable' => 1,
							'templated_hosts' => 1,
							'order' => 'host');
			$hosts_linked_to = CHost::get($params);


			$templates = $original_templates;
		}
		else{
			$groups = get_request('groups', array());
			$hosts_linked_to = get_request('hosts', array());
		}

		$clear_templates = array_intersect($clear_templates, array_keys($original_templates));
		$clear_templates = array_diff($clear_templates, array_keys($templates));
		asort($templates);
		$frmHost->addVar('clear_templates',$clear_templates);

// TEMPLATE WIDGET {
		$template_tbl = new CTableInfo();
// FORM ITEM : Template name text box [  ]
		$template_tbl->addRow(array(S_NAME, new CTextBox('template_name', $template_name, 54)));

// FORM ITEM : Groups tween box [  ] [  ]
// get all Groups
		$group_tb = new CTweenBox($frmHost, 'groups', $groups, 10);
		$options = array('editable' => 1, 'extendoutput' => 1);
		$all_groups = CHostGroup::get($options);
		foreach($all_groups as $groupid => $group){
			$group_tb->addItem($groupid, $group['name']);
		}
		$template_tbl->addRow(array(S_GROUPS, $group_tb->get(S_IN.SPACE.S_GROUPS,S_OTHER.SPACE.S_GROUPS)));


// FORM ITEM : new group text box [  ]
		$template_tbl->addRow(array(S_NEW_GROUP, new CTextBox('newgroup', $newgroup)));

// FORM ITEM : linked Hosts tween box [  ] [  ]
		$options = array('editable' => 1, 'extendoutput' => 1);
		$twb_groups = CHostGroup::get($options);
		$twb_groupid = get_request('twb_groupid', 0);
		if($twb_groupid == 0){
			$gr = reset($twb_groups);
			$twb_groupid = $gr['groupid'];
		}
		$cmbGroups = new CComboBox('twb_groupid', $twb_groupid, 'submit()');
		foreach($twb_groups as $groupid => $group){
			$cmbGroups->addItem($groupid, $group['name']);
		}

		$host_tb = new CTweenBox($frmHost, 'hosts', $hosts_linked_to, 25);

// get hosts from selected twb_groupid combo
		$params = array('groupids'=>$twb_groupid,
						'order'=>'host',
						'templated_hosts' => 1,
						'editable' => 1,
						'extendoutput' => 1);
		$db_hosts = CHost::get($params);
		foreach($db_hosts as $db_hostid => $db_host){
			if(!isset($hosts_linked_to[$db_hostid])) // add all except selected hosts
			$host_tb->addItem($db_hostid, get_node_name_by_elid($db_hostid).$db_host['host']);
		}

// select selected hosts and add them
		$params = array('hostids' => $hosts_linked_to,
						'order' => 'host',
						'templated_hosts' => 1,
						'editable' => 1,
						'extendoutput' => 1);
		$db_hosts = CHost::get($params);
		foreach($db_hosts as $hostid => $db_host){
			$host_tb->addItem($hostid, get_node_name_by_elid($hostid).$db_host['host']);
		}

		$template_tbl->addRow(array(S_HOSTS.'|'.S_TEMPLATES, $host_tb->Get(S_IN,array(S_OTHER.SPACE.'|'.SPACE.S_GROUP.SPACE,$cmbGroups))));

// FORM ITEM : linked Template table
		$template_table = new CTable();
		$template_table->SetCellPadding(0);
		$template_table->SetCellSpacing(0);
		foreach($templates as $tid => $tname){
			$frmHost->addVar('templates['.$tid.']', $tname);
			$template_table->addRow(array(
				$tname,
				new CButton('unlink['.$tid.']', S_UNLINK),
				isset($original_templates[$tid]) ? new CButton('unlink_and_clear['.$tid.']', S_UNLINK_AND_CLEAR) : SPACE
			));
		}

		$template_tbl->addRow(array(S_LINK_WITH_TEMPLATE, array(
			$template_table,
			new CButton('add_template', S_ADD,
				"return PopUp('popup.php?dstfrm=".$frmHost->GetName().
				"&dstfld1=new_template&srctbl=templates&srcfld1=hostid&srcfld2=host".
				url_param($templates,false,'existed_templates')."',450,450)", 'T')
		)));

// <<<--- FULL CLONE --->>>
		if($_REQUEST['form'] == 'full_clone'){
// FORM ITEM : Template items
			$items_lbx = new CListBox('items', null, 8);
			$items_lbx->setAttribute('disabled', 'disabled');

			$options = array('editable' => 1, 'hostids' => $templateid, 'extendoutput' => 1);
			$template_items = CItem::get($options);

			if(empty($template_items)){
				$items_lbx->setAttribute('style', 'width: 200px;');
			}
			else{
				foreach($template_items as $titemid => $titem){
					$item_description = item_description($titem);
					$items_lbx->addItem($titemid, $item_description);
				}
			}
			$template_tbl->addRow(array(S_ITEMS, $items_lbx));


// FORM ITEM : Template triggers
			$trig_lbx = new CListBox('triggers', null, 8);
			$trig_lbx->setAttribute('disabled', 'disabled');

			$options = array('editable' => 1, 'hostids' => $templateid, 'extendoutput' => 1);
			$template_triggers = CTrigger::get($options);

			if(empty($template_triggers)){
				$trig_lbx->setAttribute('style','width: 200px;');
			}
			else{
				foreach($template_triggers as $ttriggerid => $ttrigger){
					$trigger_description = expand_trigger_description($ttriggerid);
					$trig_lbx->addItem($ttriggerid, $trigger_description);
				}
			}
			$template_tbl->addRow(array(S_TRIGGERS, $trig_lbx));


// FORM ITEM : Host graphs
			$graphs_lbx = new CListBox('graphs', null, 8);
			$graphs_lbx->setAttribute('disabled', 'disabled');

			$options = array('editable' => 1, 'hostids' => $templateid, 'extendoutput' => 1);
			$template_graphs = CGraph::get($options);

			if(empty($template_graphs)){
				$graphs_lbx->setAttribute('style','width: 200px;');
			}
			else{
				foreach($template_graphs as $tgraphid => $tgraph){
					$graphs_lbx->addItem($tgraphid, $tgraph['name']);
				}
			}
			$template_tbl->addRow(array(S_GRAPHS, $graphs_lbx));
		}
// --->>> FULL CLONE <<<---

		$host_footer = array();
		$host_footer[] = new CButton('save', S_SAVE);
		if(($_REQUEST['hostid']>0) && ($_REQUEST['form'] != 'full_clone')){
			array_push($host_footer, SPACE, new CButton("clone",S_CLONE), SPACE, new CButton("full_clone",S_FULL_CLONE), SPACE,
				new CButtonDelete(S_DELETE_SELECTED_HOST_Q, url_param('form').url_param('hostid').url_param('groupid')),
				SPACE,
				new CButtonQMessage('delete_and_clear', 'Delete AND clear', S_DELETE_SELECTED_HOSTS_Q, url_param('form').url_param('hostid').
					url_param('groupid')
				)
			);
		}
		array_push($host_footer, SPACE, new CButtonCancel(url_param('groupid')));

		$host_footer = new CCol($host_footer);
		$host_footer->setColSpan(2);
		$template_tbl->setFooter($host_footer);
		$template_wdgt = new CWidget();
		$template_wdgt->setClass('header');
		$template_wdgt->addHeader($frm_title);
		$template_wdgt->addItem($template_tbl);
// } TEMPLATE WIDGET


// MACROS WIDGET {
		$macros_wdgt = get_macros_widget($templateid);
// } MACROS WIDGET

		$left_table = new CTable();
		$left_table->setCellPadding(4);
		$left_table->setCellSpacing(4);
		$left_table->addRow($template_wdgt);

		$right_table = new CTable();
		$right_table->setCellPadding(4);
		$right_table->setCellSpacing(4);
		$right_table->addRow($macros_wdgt);

		$td_l = new CCol($left_table);
		$td_l->setAttribute('valign','top');
		$td_r = new CCol($right_table);
		$td_r->setAttribute('valign','top');

		$outer_table = new CTable();
		$outer_table->addRow(array($td_l, $td_r));

		$frmHost->addItem($outer_table);
		$frmHost->show();
	}
	else{
// TABLE WITH TEMPLATES

		$template_wdgt = new CWidget();

		$frmForm = new CForm();
		$frmForm->setMethod('get');

// combo for group selection
		$cmbGroups = new CComboBox('groupid', $PAGE_GROUPS['selected'], 'javascript: submit();');
		foreach($PAGE_GROUPS['groups'] as $groupid => $group){
			$cmbGroups->addItem($groupid, $group);
		}
		$frmForm->addItem(array(S_GROUP.SPACE, $cmbGroups));

// table header
		$numrows = new CDiv();
		$numrows->setAttribute('name', 'numrows');

		$template_wdgt->addHeader(S_TEMPLATES_BIG, $frmForm);
		$template_wdgt->addHeader($numrows);
//------


		$config = select_config();

// get templates
		$options = array('editable' => 1,
						'extendoutput' => 1,
						'select_templates' => 1,
						'select_hosts' => 1,
						'select_triggers' => 1,
						'select_items' => 1,
						'select_graphs' => 1,
						'select_applications' => 1,
						'order' => 'host',
						'limit' => ($config['search_limit']+1));

		if(($PAGE_GROUPS['selected'] > 0) || empty($PAGE_GROUPS['groupids'])){
			$options['groupids'] = $PAGE_GROUPS['selected'];
		}

		$templates = CTemplate::get($options);
//-----

		$form = new CForm();
		$form->setName('templates');

		$table = new CTableInfo(S_NO_HOSTS_DEFINED);
		$table->setHeader(array(
			new CCheckBox('all_templates', NULL, "checkAll('".$form->getName()."', 'all_templates', 'templates');"),
			make_sorting_header(S_TEMPLATES,'host'),
			S_APPLICATIONS,
			S_ITEMS,
			S_TRIGGERS,
			S_GRAPHS,
			S_LINKED_TEMPLATES,
			S_LINKED_TO
		));

// sorting && paging
		order_page_result($templates, getPageSortField('host'), getPageSortOrder());
		$paging = getPagingLine($templates);
//------

		foreach($templates as $templateid => $template){
			$templates_output = array();
			if($template['proxy_hostid']){
				$proxy = get_host_by_hostid($template['proxy_hostid']);
				$templates_output[] = $proxy['host'].':';
			}
			$templates_output[] = new CLink($template['host'], 'templates.php?form=update&templateid='.$templateid.url_param('groupid'));

			$applications = array(new CLink(S_APPLICATIONS,'applications.php?groupid='.$PAGE_GROUPS['selected'].'&hostid='.$templateid),
				' ('.count($template['applications']).')');
			$items = array(new CLink(S_ITEMS,'items.php?groupid='.$PAGE_GROUPS['selected'].'&hostid='.$templateid),
				' ('.count($template['itemids']).')');
			$triggers = array(new CLink(S_TRIGGERS,'triggers.php?groupid='.$PAGE_GROUPS['selected'].'&hostid='.$templateid),
				' ('.count($template['triggerids']).')');
			$graphs = array(new CLink(S_GRAPHS,'graphs.php?groupid='.$PAGE_GROUPS['selected'].'&hostid='.$templateid),
				' ('.count($template['graphids']).')');

			$i = 0;
			$linked_templates_output = array();

			order_result($template['templates'], 'host', null, true);
			foreach($template['templates'] as $linked_templateid => $linked_template){
				$i++;
				if($i > $config['max_in_table']){
					$linked_templates_output[] = '...';
					$linked_templates_output[] = '//empty element for array_pop';
					break;
				}

				$url = 'templates.php?form=update&templateid='.$linked_templateid.url_param('groupid');
				$linked_templates_output[] = new CLink($linked_template['host'], $url, 'unknown');
				$linked_templates_output[] = ', ';
			}
			array_pop($linked_templates_output);


			$i = 0;
			$linked_to_hosts_output = array();

			order_result($template['hosts'], 'host', null, true);
			foreach($template['hosts'] as $linked_to_hostid => $linked_to_host){
				$i++;
				if($i > $config['max_in_table']){
					$linked_to_hosts_output[] = '...';
					$linked_to_hosts_output[] = '//empty element for array_pop';
					break;
				}

				switch($linked_to_host['status']){
					case HOST_STATUS_NOT_MONITORED:
						$style = 'on';
						$url = 'hosts.php?form=update&hostid='.$linked_to_hostid.'&groupid='.$PAGE_GROUPS['selected'];
					break;
					case HOST_STATUS_TEMPLATE:
						$style = 'unknown';
						$url = 'templates.php?form=update&templateid='.$linked_to_hostid;
					break;
					default:
						$style = null;
						$url = 'hosts.php?form=update&hostid='.$linked_to_hostid.'&groupid='.$PAGE_GROUPS['selected'];
					break;
				}

				$linked_to_hosts_output[] = new CLink($linked_to_host['host'], $url, $style);
				$linked_to_hosts_output[] = ', ';
			}
			array_pop($linked_to_hosts_output);


			$table->addRow(array(
				new CCheckBox('templates['.$templateid.']', NULL, NULL, $templateid),
				$templates_output,
				$applications,
				$items,
				$triggers,
				$graphs,
				(empty($linked_templates_output) ? '-' : new CCol($linked_templates_output,'wraptext')),
				(empty($linked_to_hosts_output) ? '-' : new CCol($linked_to_hosts_output,'wraptext'))
			));
		}
// }GENERATE OUTPUTS

// GO{
		$goBox = new CComboBox('go');
		$goBox->addItem('delete',S_DELETE_SELECTED);
		$goBox->addItem('delete_and_clear',S_DELETE_SELECTED_WITH_LINKED_ELEMENTS);

		// goButton name is necessary!!!
		$goButton = new CButton('goButton',S_GO.' (0)');
		$goButton->setAttribute('id','goButton');
		zbx_add_post_js('chkbxRange.pageGoName = "templates";');

		$footer = get_table_header(new CCol(array($goBox, $goButton)));
// }GO

// PAGING FOOTER
		$table = array($paging,$table,$paging,$footer);
//---------

		$form->addItem($table);

		$template_wdgt->addItem($form);
		$template_wdgt->show();
	}
//**********************************************
// }TEMPLATE LIST AND FORM
//**********************************************
?>
<?php

include_once('include/page_footer.php');

?>
