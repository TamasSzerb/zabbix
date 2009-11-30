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
require_once('include/graphs.inc.php');
require_once('include/forms.inc.php');

$page['title'] = 'S_CONFIGURATION_OF_GRAPHS';
$page['file'] = 'graphs.php';
$page['hist_arg'] = array();
$page['scripts'] = array();

include_once('include/page_header.php');

?>
<?php
//		VAR			TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION
	$fields = array(
//  NEW  templates.php; hosts.php; items.php; triggers.php; graphs.php; maintenances.php;
// 	OLD  0 - hosts; 1 - groups; 2 - linkages; 3 - templates; 4 - applications; 5 - Proxies; 6 - maintenance
		'groupid'=>	array(T_ZBX_INT, O_OPT,	 NULL,	DB_ID,	NULL),
		'hostid'=>	array(T_ZBX_INT, O_OPT,	 NULL,	DB_ID,	NULL),

		'copy_type'	=>array(T_ZBX_INT, O_OPT,	 P_SYS,	IN('0,1'),'isset({copy})'),
		'copy_mode'	=>array(T_ZBX_INT, O_OPT,	 P_SYS,	IN('0'),NULL),

		'graphid'=>	array(T_ZBX_INT, O_OPT,	 P_SYS,	DB_ID,			'(isset({form})&&({form}=="update"))'),
		'name'=>	array(T_ZBX_STR, O_OPT,  NULL,	NOT_EMPTY,		'isset({save})'),
		'width'=>	array(T_ZBX_INT, O_OPT,	 NULL,	BETWEEN(0,65535),	'isset({save})'),
		'height'=>	array(T_ZBX_INT, O_OPT,	 NULL,	BETWEEN(0,65535),	'isset({save})'),

		'ymin_type'=>	array(T_ZBX_INT, O_OPT,	 NULL,	IN('0,1,2'),		null),
		'ymax_type'=>	array(T_ZBX_INT, O_OPT,	 NULL,	IN('0,1,2'),		null),
		'graphtype'=>	array(T_ZBX_INT, O_OPT,	 NULL,	IN('0,1,2,3'),		'isset({save})'),
		'yaxismin'=>	array(T_ZBX_DBL, O_OPT,	 NULL,	null,	'isset({save})&&(({graphtype} == 0) || ({graphtype} == 1))'),
		'yaxismax'=>	array(T_ZBX_DBL, O_OPT,	 NULL,	null,	'isset({save})&&(({graphtype} == 0) || ({graphtype} == 1))'),
		'graph3d'=>	array(T_ZBX_INT, O_OPT,	P_NZERO,	IN('0,1'),		null),
		'legend'=>	array(T_ZBX_INT, O_OPT,	P_NZERO,	IN('0,1'),		null),
		"ymin_itemid"=>	array(T_ZBX_INT, O_OPT,	 NULL,	DB_ID,	'isset({save})&&isset({ymin_type})&&({ymin_type}==3)'),		"ymax_itemid"=>	array(T_ZBX_INT, O_OPT,	 NULL,	DB_ID,	'isset({save})&&isset({ymax_type})&&({ymax_type}==3)'),				'percent_left'=>	array(T_ZBX_DBL, O_OPT,	 NULL,	BETWEEN(0,100),	null),
		'percent_right'=>	array(T_ZBX_DBL, O_OPT,	 NULL,	BETWEEN(0,100),	null),
		'visible'=>			array(T_ZBX_INT, O_OPT,	 NULL,	BETWEEN(0,1),	null),
		'items'=>		array(T_ZBX_STR, O_OPT,  NULL,	null,		null),
		'new_graph_item'=>	array(T_ZBX_STR, O_OPT,  NULL,	null,		null),
		'group_gid'=>		array(T_ZBX_STR, O_OPT,  NULL,	null,		null),
		'move_up'=>		array(T_ZBX_INT, O_OPT,  NULL,	null,		null),
		'move_down'=>		array(T_ZBX_INT, O_OPT,  NULL,	null,		null),

		'showworkperiod'=>	array(T_ZBX_INT, O_OPT,	 NULL,	IN('1'),	NULL),
		'showtriggers'=>	array(T_ZBX_INT, O_OPT,	 NULL,	IN('1'),	NULL),

		'group_graphid'=>	array(T_ZBX_INT, O_OPT,	NULL,	DB_ID, NULL),
		'copy_targetid'=>	array(T_ZBX_INT, O_OPT,	NULL,	DB_ID, NULL),
		'filter_groupid'=>	array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID, 'isset({copy})&&(isset({copy_type})&&({copy_type}==0))'),
// Actions
		'go'=>					array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, NULL, NULL),
// form
		'add_item'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'delete_item'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),

		'save'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'clone'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'copy'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'delete'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'cancel'=>		array(T_ZBX_STR, O_OPT, P_SYS,	NULL,	NULL),
/* other */
		'form'=>		array(T_ZBX_STR, O_OPT, P_SYS,	NULL,	NULL),
		'form_refresh'=>	array(T_ZBX_INT, O_OPT,	NULL,	NULL,	NULL)
	);

	check_fields($fields);
	validate_sort_and_sortorder('name', ZBX_SORT_UP);

	$_REQUEST['go'] = get_request('go', 'none');
	
// PERMISSIONS
	if(get_request('graphid',0) > 0){
		$graphs = available_graphs($_REQUEST['graphid'], 1);
		if(empty($graphs)) access_deny();
	}
?>
<?php

	$_REQUEST['items'] = get_request('items', array());
	$_REQUEST['group_gid'] = get_request('group_gid', array());
	$_REQUEST['graph3d'] = get_request('graph3d', 0);
	$_REQUEST['legend'] = get_request('legend', 0);

// ---- <ACTIONS> ----
	if(isset($_REQUEST['clone']) && isset($_REQUEST['graphid'])){
		unset($_REQUEST['graphid']);
		$_REQUEST['form'] = 'clone';
	}
	else if(isset($_REQUEST['save'])){

		$items = get_request('items', array());
		$itemids = array();
		foreach($items as $inum => $gitem){
			$itemids[$gitem['itemid']] = $gitem['itemid'];
		}

		if(!empty($itemids)){
			$options = array('itemids'=>$itemids, 'editable'=>1, 'nodes'=>get_current_nodeid(true));
			$db_items = CItem::get($options);
			$db_items = zbx_toHash($db_items, 'itemid');
			
			foreach($itemids as $inum => $itemid){
				if(!isset($db_items[$itemid])){
					access_deny();
				}
			}
		}

		if(empty($items)){
			info(S_REQUIRED_ITEMS_FOR_GRAPH);
			$result = false;
		}
		else{
			isset($_REQUEST["ymin_type"])?(''):($_REQUEST["ymin_type"]=0);
			isset($_REQUEST["ymax_type"])?(''):($_REQUEST["ymax_type"]=0);

			isset($_REQUEST['yaxismin'])?(''):($_REQUEST['yaxismin']=0);
			isset($_REQUEST['yaxismax'])?(''):($_REQUEST['yaxismax']=0);

			$showworkperiod	= isset($_REQUEST['showworkperiod']) ? 1 : 0;
			$showtriggers	= isset($_REQUEST['showtriggers']) ? 1 : 0;

			$visible = get_request('visible');
			$percent_left  = 0;
			$percent_right = 0;

			if(isset($visible['percent_left'])) 	$percent_left  = get_request('percent_left', 0);
			if(isset($visible['percent_right']))	$percent_right = get_request('percent_right', 0);

			if(($_REQUEST['ymin_itemid'] != 0) && ($_REQUEST['ymax_type'] == GRAPH_YAXIS_TYPE_ITEM_VALUE)){
				$_REQUEST['yaxismin']=0;
			}

			if(($_REQUEST['ymax_itemid'] != 0)  && ($_REQUEST['ymax_type'] == GRAPH_YAXIS_TYPE_ITEM_VALUE)){
				$_REQUEST['yaxismax']=0;
			}

			if(isset($_REQUEST['graphid'])){

				DBstart();
				update_graph_with_items($_REQUEST['graphid'],
					$_REQUEST['name'],$_REQUEST['width'],$_REQUEST['height'],
					$_REQUEST['ymin_type'],$_REQUEST['ymax_type'],$_REQUEST['yaxismin'],$_REQUEST['yaxismax'],
					$_REQUEST['ymin_itemid'],$_REQUEST['ymax_itemid'],
					$showworkperiod,$showtriggers,$_REQUEST['graphtype'],$_REQUEST['legend'],
					$_REQUEST['graph3d'],$percent_left,$percent_right,$items);
				$result = DBend();

				if($result){
					add_audit(AUDIT_ACTION_UPDATE,AUDIT_RESOURCE_GRAPH,'Graph ID ['.$_REQUEST['graphid'].'] Graph ['.$_REQUEST['name'].']');
				}
			}
			else{
				DBstart();
				add_graph_with_items($_REQUEST['name'],$_REQUEST['width'],$_REQUEST['height'],
					$_REQUEST['ymin_type'],$_REQUEST['ymax_type'],$_REQUEST['yaxismin'],$_REQUEST['yaxismax'],
					$_REQUEST['ymin_itemid'],$_REQUEST['ymax_itemid'],
					$showworkperiod,$showtriggers,$_REQUEST['graphtype'],$_REQUEST['legend'],
					$_REQUEST['graph3d'],$percent_left,$percent_right,$items);
				$result = DBend();

				if($result){
					add_audit(AUDIT_ACTION_ADD,AUDIT_RESOURCE_GRAPH,'Graph ['.$_REQUEST['name'].']');
				}
			}
			if($result){
				unset($_REQUEST['form']);
			}
		}
		if(isset($_REQUEST['graphid'])){
			show_messages($result, S_GRAPH_UPDATED, S_CANNOT_UPDATE_GRAPH);
		}
		else {
			show_messages($result, S_GRAPH_ADDED, S_CANNOT_ADD_GRAPH);
		}
	}
	else if(isset($_REQUEST['delete']) && isset($_REQUEST['graphid'])){		
		$options = array('graphids'=>$_REQUEST['graphid'], 'extendoutput'=>1, 'editable'=>1, 'nopermissions'=>1);
		$graphs = CGraph::get($options);
		$graph = reset($graphs);

		DBstart();
			$result = CGraph::delete($graphs);
		$result = DBend($result);

		if($result){
			add_audit(AUDIT_ACTION_DELETE,AUDIT_RESOURCE_GRAPH,'Graph ['.$graph['name'].']');
			unset($_REQUEST['form']);
		}
		show_messages($result, S_GRAPH_DELETED, S_CANNOT_DELETE_GRAPH);
	}
	else if(isset($_REQUEST['delete_item']) && isset($_REQUEST['group_gid'])){

		foreach($_REQUEST['items'] as $gid => $data){
			if(!isset($_REQUEST['group_gid'][$gid])) continue;
			unset($_REQUEST['items'][$gid]);
		}
		unset($_REQUEST['delete_item'], $_REQUEST['group_gid']);
	}
	else if(isset($_REQUEST['new_graph_item'])){
		$new_gitem = get_request('new_graph_item', array());

		foreach($_REQUEST['items'] as $gid => $data){
			if(	(bccomp($new_gitem['itemid'] , $data['itemid'])==0) &&
				$new_gitem['yaxisside'] == $data['yaxisside'] &&
				$new_gitem['calc_fnc'] == $data['calc_fnc'] &&
				$new_gitem['type'] == $data['type'] &&
				$new_gitem['periods_cnt'] == $data['periods_cnt'])
			{
				$already_exist = true;
				break;
			}
		}

		if(!isset($already_exist)){
			array_push($_REQUEST['items'], $new_gitem);
		}
	}
	else if(isset($_REQUEST['move_up']) && isset($_REQUEST['items'])){
		if(isset($_REQUEST['items'][$_REQUEST['move_up']])){
			$tmp = $_REQUEST['items'][$_REQUEST['move_up']];
			$_REQUEST['items'][$_REQUEST['move_up']] = $_REQUEST['items'][$_REQUEST['move_up'] - 1];
			$_REQUEST['items'][$_REQUEST['move_up'] - 1] = $tmp;

		}
	}
	else if(isset($_REQUEST['move_down']) && isset($_REQUEST['items'])){
		if(isset($_REQUEST['items'][$_REQUEST['move_down']])){
			$tmp = $_REQUEST['items'][$_REQUEST['move_down']];
			$_REQUEST['items'][$_REQUEST['move_down']] = $_REQUEST['items'][$_REQUEST['move_down'] + 1];
			$_REQUEST['items'][$_REQUEST['move_down'] + 1] = $tmp;

		}
	}
//------ GO -------
	else if(($_REQUEST['go'] == 'delete') && isset($_REQUEST['group_graphid'])){
		$options = array('graphids'=>$_REQUEST['group_graphid'], 'extendoutput'=>1, 'editable'=>1);
		$graphs = CGraph::get($options);

		$go_result = false;

		DBstart();
		foreach($graphs as $gnum => $graph){
			if($graph['templateid'] != 0){
				unset($graphs[$gnum]);
				error('Cannot delete graph [ '.$graph['name'].' ] (Templated graph)');
				continue;
			}
			
			add_audit(AUDIT_ACTION_DELETE,AUDIT_RESOURCE_GRAPH,'Graph ['.$graph['name'].']');
		}
		if(!empty($graphs)){
			$go_result = CGraph::delete($graphs);
		}

		$go_result = DBend($go_result);

		show_messages($go_result, S_GRAPHS_DELETED, S_CANNOT_DELETE_GRAPHS);
	}
	else if(($_REQUEST['go'] == 'copy_to') && isset($_REQUEST['copy'])&&isset($_REQUEST['group_graphid'])){
		if(isset($_REQUEST['copy_targetid']) && $_REQUEST['copy_targetid'] > 0 && isset($_REQUEST['copy_type'])){
			if(0 == $_REQUEST['copy_type']){ /* hosts */
				$hosts_ids = $_REQUEST['copy_targetid'];
			}
			else{ 
// groups
				zbx_value2array($_REQUEST['copy_targetid']);

				$options = array('groupids'=>$_REQUEST['copy_targetid'], 'editable'=>1, 'nodes'=>get_current_nodeid(true));
				$db_groups = CHostGroup::get($options);
				$db_groups = zbx_toHash($db_groups, 'groupid');

				foreach($_REQUEST['copy_targetid'] as $gnum => $groupid){
					if(!isset($db_groups[$groupid])){
						access_deny();
					}
				}
				
				$options = array('groupids'=>$_REQUEST['copy_targetid'], 'editable'=>1, 'nodes'=>get_current_nodeid(true));
				$db_hosts = CHost::get($options);
			}
			DBstart();
			foreach($_REQUEST['group_graphid'] as $gnum => $graph_id){
				foreach($db_hosts as $hnum => $host){
					copy_graph_to_host($graph_id, $host['hostid'], true);
				}
			}
			$go_result = DBend();
			$_REQUEST['go'] = 'none2';
		}
		else{
			error('No target selection.');
		}
		show_messages();
	}

	if(($_REQUEST['go'] != 'none') && isset($go_result) && $go_result){
		$url = new CUrl();
		$path = $url->getPath();
		insert_js('cookie.eraseArray("'.$path.'")');
	}
// ----</ACTIONS>----
?>
<?php
	if(isset($_REQUEST['hostid']) && !isset($_REQUEST['groupid']) && !isset($_REQUEST['graphid'])){
		$sql = 'SELECT DISTINCT hg.groupid '.
				' FROM hosts_groups hg '.
				' WHERE hg.hostid='.$_REQUEST['hostid'];
		if($group=DBfetch(DBselect($sql, 1))){
			$_REQUEST['groupid'] = $group['groupid'];
		}
	}

	if(isset($_REQUEST['graphid']) && ($_REQUEST['graphid']>0)){
		$sql_from = '';
		$sql_where = '';
		if(isset($_REQUEST['groupid']) && ($_REQUEST['groupid'] > 0)){
			$sql_where.= ' AND hg.groupid='.$_REQUEST['groupid'];
		}

		if(isset($_REQUEST['hostid']) && ($_REQUEST['hostid'] > 0)){
			$sql_where.= ' AND hg.hostid='.$_REQUEST['hostid'];
		}
		$sql = 'SELECT DISTINCT hg.groupid, hg.hostid '.
				' FROM hosts_groups hg '.
				' WHERE EXISTS( SELECT DISTINCT i.itemid '.
									' FROM items i, graphs_items gi '.
									' WHERE i.hostid=hg.hostid '.
										' AND i.itemid=gi.itemid '.
										' AND gi.graphid='.$_REQUEST['graphid'].')'.
						$sql_where;
		if($host_group = DBfetch(DBselect($sql,1))){
			if(!isset($_REQUEST['groupid']) || !isset($_REQUEST['hostid'])){
				$_REQUEST['groupid'] = $host_group['groupid'];
				$_REQUEST['hostid'] = $host_group['hostid'];
			}
			else if(($_REQUEST['groupid']!=$host_group['groupid']) || ($_REQUEST['hostid']!=$host_group['hostid'])){
				$_REQUEST['graphid'] = 0;
			}
		}
		else{
//			$_REQUEST['graphid'] = 0;
		}
	}

	$params = array();
	$options = array('only_current_node', 'not_proxy_hosts');
	foreach($options as $option) $params[$option] = 1;

	$PAGE_GROUPS = get_viewed_groups(PERM_READ_WRITE, $params);
	$PAGE_HOSTS = get_viewed_hosts(PERM_READ_WRITE, $PAGE_GROUPS['selected'], $params);

	validate_group_with_host($PAGE_GROUPS,$PAGE_HOSTS);
?>
<?php
	$form = new CForm();
	$form->setMethod('get');

// Config
	$cmbConf = new CComboBox('config','graphs.php','javascript: submit();');
	$cmbConf->setAttribute('onchange','javascript: redirect(this.options[this.selectedIndex].value);');
		$cmbConf->addItem('templates.php',S_TEMPLATES);
		$cmbConf->addItem('hosts.php',S_HOSTS);
		$cmbConf->addItem('items.php',S_ITEMS);
		$cmbConf->addItem('triggers.php',S_TRIGGERS);
		$cmbConf->addItem('graphs.php',S_GRAPHS);
		$cmbConf->addItem('applications.php',S_APPLICATIONS);

	$form->addItem($cmbConf);

	if(!isset($_REQUEST['form']))
		$form->addItem(new CButton('form',S_CREATE_GRAPH));

	show_table_header(S_CONFIGURATION_OF_GRAPHS_BIG,$form);
	echo SBR;

	if(($_REQUEST['go'] == 'copy_to') && isset($_REQUEST['group_graphid'])){
		insert_copy_elements_to_forms('group_graphid');
	}
	else if(isset($_REQUEST['form'])){
		insert_graph_form();
		echo SBR;
		$table = new CTable(NULL,'graph');
		if(($_REQUEST['graphtype'] == GRAPH_TYPE_PIE) || ($_REQUEST['graphtype'] == GRAPH_TYPE_EXPLODED)){
			$table->addRow(new CImg('chart7.php?period=3600'.url_param('items').
				url_param('name').url_param('legend').url_param('graph3d').url_param('width').url_param('height').url_param('graphtype')));
			$table->Show();
		}
		else{
			$table->addRow(new CImg('chart3.php?period=3600'.url_param('items').
				url_param('name').url_param('width').url_param('height').
				url_param('ymin_type').url_param('ymax_type').url_param('yaxismin').url_param('yaxismax').
				url_param('ymin_itemid').url_param('ymax_itemid').
				url_param('show_work_period').url_param('show_triggers').url_param('graphtype').
				url_param('percent_left').url_param('percent_right')));
			$table->Show();
		}
	}
	else {
/* Table HEADER */
		$graphs_wdgt = new CWidget();

		if(isset($_REQUEST['graphid']) && ($_REQUEST['graphid']==0)){
			unset($_REQUEST['graphid']);
		}

		$r_form = new CForm();
		$r_form->setMethod('get');

		$cmbGroups = new CComboBox('groupid',$PAGE_GROUPS['selected'],'javascript: submit();');
		$cmbHosts = new CComboBox('hostid',$PAGE_HOSTS['selected'],'javascript: submit();');

		foreach($PAGE_GROUPS['groups'] as $groupid => $name){
			$cmbGroups->addItem($groupid, get_node_name_by_elid($groupid, null, ': ').$name);
		}
		foreach($PAGE_HOSTS['hosts'] as $hostid => $name){
			$cmbHosts->addItem($hostid, get_node_name_by_elid($hostid, null, ': ').$name);
		}

		$r_form->addItem(array(S_GROUP.SPACE,$cmbGroups));
		$r_form->addItem(array(SPACE.S_HOST.SPACE,$cmbHosts));

		$numrows = new CDiv();
		$numrows->setAttribute('name','numrows');

		$graphs_wdgt->addHeader(S_GRAPHS_BIG, $r_form);
		$graphs_wdgt->addHeader($numrows);

// Header Host
		if($PAGE_HOSTS['selected'] > 0){
			$tbl_header_host = get_header_host_table($PAGE_HOSTS['selected'], array('items', 'triggers', 'applications'));
			$graphs_wdgt->addItem($tbl_header_host);
		}

/* TABLE */
		$form = new CForm();
		$form->setName('graphs');
		$form->addVar('hostid',$_REQUEST['hostid']);

		$table = new CTableInfo(S_NO_GRAPHS_DEFINED);
		$table->setHeader(array(
			new CCheckBox('all_graphs',NULL,"checkAll('".$form->getName()."','all_graphs','group_graphid');"),
			$_REQUEST['hostid'] != 0 ? NULL : S_HOSTS,
			make_sorting_header(S_NAME,'name'),
			S_WIDTH,
			S_HEIGHT,
			make_sorting_header(S_GRAPH_TYPE,'graphtype')));


		$sortfield = getPageSortField('description');
		$sortorder = getPageSortOrder();
		$options = array(
			'editable' => 1,
			'extendoutput' => 1,
			'select_hosts' => 1,
			'sortfield' => $sortfield,
			'sortorder' => $sortorder,
			'limit' => ($config['search_limit']+1));
		if($PAGE_HOSTS['selected'] > 0){
			$options['hostids'] = $PAGE_HOSTS['selected'];
		}
		else if($PAGE_GROUPS['selected'] > 0){
			$options['groupids'] = $PAGE_GROUPS['selected'];
		}

		$graphs = CGraph::get($options);

// Change graphtype from numbers to names, for correct sorting
		foreach($graphs as $gnum => $graph){
			switch($graph['graphtype']){
				case GRAPH_TYPE_STACKED:
					$graphtype = S_STACKED;
				break;
				case GRAPH_TYPE_PIE:
					$graphtype = S_PIE;
				break;
				case GRAPH_TYPE_EXPLODED:
					$graphtype = S_EXPLODED;
				break;
				default:
					$graphtype = S_NORMAL;
				break;
			}

			$graphs[$gnum]['graphtype'] = $graphtype;
		}

// sorting
		order_result($graphs, $sortfield, $sortorder);
		$paging = getPagingLine($graphs);
//---------

		foreach($graphs as $gnum => $graph){
			$graphid = $graph['graphid'];

			$host_list = NULL;
			if($_REQUEST['hostid'] == 0){
				$host_list = array();
				foreach($graph['hosts'] as $host){
					$host_list[] = $host['host'];
				}
				$host_list = implode(', ', $host_list);
			}

			$name = array();
			if($graph['templateid'] != 0){
				$real_hosts = get_realhosts_by_graphid($graph['templateid']);
				$real_host = DBfetch($real_hosts);
				$name[] = new CLink($real_host['host'], 'graphs.php?'.'hostid='.$real_host['hostid'], 'unknown');
				$name[] = ':';
			}
			$name[] = new CLink($graph['name'], 'graphs.php?graphid='.$graphid.'&form=update');


			$chkBox = new CCheckBox('group_graphid['.$graphid.']', NULL, NULL, $graphid);
			if($graph['templateid'] > 0) $chkBox->setEnabled(false);

			$table->addRow(array(
				$chkBox,
				$host_list,
				$name,
				$graph['width'],
				$graph['height'],
				$graph['graphtype']
			));
		}

//----- GO ------
		$goBox = new CComboBox('go');
		$goBox->addItem('copy_to',S_COPY_SELECTED_TO);

		$goOption = new CComboItem('delete',S_DELETE_SELECTED);
		$goOption->setAttribute('confirm','Delete selected graphs?');
		$goBox->addItem($goOption);

// goButton name is necessary!!!
		$goButton = new CButton('goButton',S_GO);
		$goButton->setAttribute('id','goButton');
		zbx_add_post_js('chkbxRange.pageGoName = "group_graphid";');

		$footer = get_table_header(new CCol(array($goBox, $goButton)));
//----

// PAGING FOOTER
		$table = array($paging, $table, $paging, $footer);
//---------

		$form->addItem($table);
		$graphs_wdgt->addItem($form);
		$graphs_wdgt->show();
	}

include_once 'include/page_footer.php';
?>
