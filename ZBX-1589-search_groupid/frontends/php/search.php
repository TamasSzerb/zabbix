<?php
/*
** ZABBIX
** Copyright (C) 2001-2009 SIA Zabbix
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
require_once('include/config.inc.php');
require_once('include/hosts.inc.php');
require_once('include/html.inc.php');

$page["title"] = "S_SEARCH";
$page['file'] = 'search.php';
$page['hist_arg'] = array();
$page['scripts'] = array('class.pmaster.js','scriptaculous.js?load=effects');

$page['type'] = detect_page_type(PAGE_TYPE_HTML);

include_once('include/page_header.php');

//		VAR				TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION
	$fields=array(
		'type'=>		array(T_ZBX_INT, O_OPT,	P_SYS,	IN('0,1'),		NULL),
		'search'=>		array(T_ZBX_STR, O_OPT, P_SYS,	NULL,			NULL),

//ajax
		'favobj'=>		array(T_ZBX_STR, O_OPT, P_ACT,	NULL,			NULL),
		'favid'=>		array(T_ZBX_STR, O_OPT, P_ACT,  NOT_EMPTY,		'isset({favobj})'),
		'favcnt'=>		array(T_ZBX_INT, O_OPT,	null,	null,			NULL),

		'action'=>		array(T_ZBX_STR, O_OPT, P_ACT, 	IN("'add','remove'"),NULL),
		'state'=>		array(T_ZBX_INT, O_OPT, P_ACT,  NOT_EMPTY,		'isset({favobj}) && ("hat"=={favobj})'),
	);

	check_fields($fields);

// ACTION /////////////////////////////////////////////////////////////////////////////
	if(isset($_REQUEST['favobj'])){
		$_REQUEST['pmasterid'] = get_request('pmasterid','mainpage');

		if('hat' == $_REQUEST['favobj']){
			update_profile('web.dashboard.hats.'.$_REQUEST['favid'].'.state',$_REQUEST['state'], PROFILE_TYPE_INT);
		}

		if('refresh' == $_REQUEST['favobj']){
			switch($_REQUEST['favid']){
				case 'hat_syssum':
					$syssum = make_system_summary();
					$syssum->show();
					break;
				case 'hat_stszbx':
					$stszbx = make_status_of_zbx();
					$stszbx->show();
					break;
			}
		}
	}

	if((PAGE_TYPE_JS == $page['type']) || (PAGE_TYPE_HTML_BLOCK == $page['type'])){
		exit();
	}
?>
<?php

	$admin = uint_in_array($USER_DETAILS['type'], array(USER_TYPE_ZABBIX_ADMIN, USER_TYPE_SUPER_ADMIN));
	$rows_per_page = $USER_DETAILS['rows_per_page'];

	$search_wdgt = new CWidget('search_wdgt');

	$search = get_request('search', '');

// Header
	if(zbx_empty($search)){
		$search = 'Search pattern is empty';
	}
	$search_wdgt->setClass('header');
	$search_wdgt->addHeader(array(S_SEARCH_BIG.': ',bold($search)), SPACE);

//-------------
	$left_tab = new CTable();
	$left_tab->setCellPadding(3);
	$left_tab->setCellSpacing(3);

	$left_tab->setAttribute('border',0);

	$right_tab = new CTable();
	$right_tab->setCellPadding(3);
	$right_tab->setCellSpacing(3);

	$right_tab->setAttribute('border',0);

// FIND Hosts
	$params = array(
		'nodeids'=> get_current_nodeid(),
		'extendoutput' => true,
		'pattern' => $search,
		'extend_pattern' => true,
		'limit' => $rows_per_page,
		'select_groups' => 1,
	);
	$db_hosts = CHost::get($params);
	order_result($db_hosts, 'host', null, true);

	$hosts = selectByPattern($db_hosts, 'host', $search, $rows_per_page);

	$params = array(
		'nodeids'=> get_current_nodeid(),
		'pattern' => $search,
		'extend_pattern' => true,
		'count' => 1,
	);
	$hosts_count = CHost::get($params);
	$overalCount = $hosts_count['rowscount'];
	$viewCount = count($hosts);

	$header = array(
		is_show_all_nodes()?new CCol(S_NODE):null,
		new CCol(S_HOSTS),
		new CCol(S_IP),
		new CCol(S_DNS),
		new CCol(S_LATEST_DATA),
		new CCol(S_TRIGGERS),
		new CCol(S_EVENTS),
		$admin?new CCol(S_EDIT, 'center'):null,
	);

	$table  = new CTableInfo();
	$table->setHeader($header);

	foreach($hosts as $gnum => $host){
		$hostid = $host['hostid'];
		
		$group = reset($host['groups']);
		$link = 'groupid='.$group['groupid'].'&hostid='.$hostid;

		if($admin){
			$pageBox = new CComboBox('hostpages_'.$hostid);
				$pageBox->addItem('hosts.php?form=update&'.$link, S_HOST);
				$pageBox->addItem('items.php?'.$link, S_ITEMS);
				$pageBox->addItem('triggers.php?'.$link, S_TRIGGERS);
				$pageBox->addItem('graphs.php?'.$link, S_GRAPHS);

			$pageGo = new CButton('pagego', S_GO, "javascript: ".
							" redirect(\$('hostpages_$hostid').options[\$('hostpages_$hostid').selectedIndex].value);");

			$pageSelect = array($pageBox,SPACE,$pageGo);
		}
		else{
			$pageSelect = null;
		}

		$caption = make_decoration($host['host'], $search);
		$hostip = make_decoration($host['ip'], $search);
		$hostdns = make_decoration($host['dns'], $search);

		$table->addRow(array(
			get_node_name_by_elid($hostid),
			$caption,
			$hostip,
			$hostdns,
			new CLink(S_GO,'latest.php?'.$link),
			new CLink(S_GO,'tr_status.php?'.$link),
			new CLink(S_GO,'events.php?'.$link),
			$pageSelect
		));
	}
	$table->setFooter(new CCol(S_DISPLAYING.SPACE.$viewCount.SPACE.S_OF_SMALL.SPACE.$overalCount.SPACE.S_FOUND_SMALL));


	$wdgt_hosts = new CWidget('search_hosts',$table);
	$wdgt_hosts->setClass('header');
	$wdgt_hosts->addHeader(S_HOSTS, SPACE);

	$left_tab->addRow($wdgt_hosts);
//----------------


// Find Host groups
	$params = array(
		'nodeids'=> get_current_nodeid(),
		'extendoutput' => 1,
		'pattern' => $search,
		'limit' => $rows_per_page,
	);

	$db_hostGroups = CHostGroup::get($params);
	order_result($db_hostGroups, 'name', null, true);
	$hostGroups = selectByPattern($db_hostGroups, 'name', $search, $rows_per_page);

	$params = array(
		'nodeids'=> get_current_nodeid(),
		'pattern' => $search,
		'count' => 1,
	);
	$groups_count = CHostGroup::get($params);

	$overalCount = $groups_count['rowscount'];
	$viewCount = count($hostGroups);

	$header = array(
		is_show_all_nodes()?new CCol(S_NODE):null,
		new CCol(S_HOST_GROUP),
		new CCol(S_LATEST_DATA),
		new CCol(S_TRIGGERS),
		new CCol(S_EVENTS),
		$admin?new CCol(S_EDIT):null,
		);

	$table  = new CTableInfo();
	$table->setHeader($header);

	foreach($hostGroups as $hnum => $group){
		$hostgroupid = $group['groupid'];

		$caption = make_decoration($group['name'], $search);
		$admin_link = $admin?new CLink(S_GO,'hosts.php?config=1&groupid='.$hostgroupid.'&hostid=0'):null;

		$table->addRow(array(
			get_node_name_by_elid($hostgroupid),
			$caption,
			new CLink(S_GO,'latest.php?groupid='.$hostgroupid.'&hostid=0'),
			new CLink(S_GO,'tr_status.php?groupid='.$hostgroupid.'&hostid=0'),
			new CLink(S_GO,'events.php?groupid='.$hostgroupid.'&hostid=0'),
			$admin_link,
		));
	}
	$table->setFooter(new CCol(S_DISPLAYING.SPACE.$viewCount.SPACE.S_OF_SMALL.SPACE.$overalCount.SPACE.S_FOUND_SMALL));

	$wdgt_hgroups = new CWidget('search_hostgroup',$table);
	$wdgt_hgroups->setClass('header');
	$wdgt_hgroups->addHeader(S_HOST_GROUPS, SPACE);
	$right_tab->addRow($wdgt_hgroups);
//----------------

// FIND Templates
	if($admin){
		$params = array(
			'nodeid'=> get_current_nodeid(),
			'extendoutput' => 1,
			'select_groups' => 1,
			'pattern' => $search,
			'limit' => $rows_per_page,
			'order' => 'host',
			'editable' => 1
		);

		$db_templates = CTemplate::get($params);
		order_result($db_templates, 'host', null, true);

		$templates = selectByPattern($db_templates, 'host', $search, $rows_per_page);

		$params = array(
					'nodeids'=> get_current_nodeid(),
					'pattern' => $search,
					'count' => 1,
					'editable' => 1
					);
		$hosts_count = CTemplate::get($params);

		$overalCount = $hosts_count['rowscount'];
		$viewCount = count($templates);

		$header = array(
			is_show_all_nodes()?new CCol(S_NODE):null,
			new CCol(S_TEMPLATES),
			new CCol(S_ITEMS),
			new CCol(S_TRIGGERS),
			new CCol(S_GRAPHS),
			);

		$table  = new CTableInfo();
		$table->setHeader($header);

		foreach($templates as $tnum => $template){
			$templateid = $template['hostid'];

			$group = reset($template['groups']);
			$link = 'groupid='.$group['groupid'].'&hostid='.$templateid;

			$caption = make_decoration($template['host'], $search);

			$table->addRow(array(
				get_node_name_by_elid($templateid),
				new CLink($caption,'hosts.php?hostid='.$templateid),
				new CLink(S_GO,'items.php?'.$link),
				new CLink(S_GO,'triggers.php?'.$link),
				new CLink(S_GO,'graphs.php?'.$link)
			));
		}
		$table->setFooter(new CCol(S_DISPLAYING.SPACE.$viewCount.SPACE.S_OF_SMALL.SPACE.$overalCount.SPACE.S_FOUND_SMALL));


		$wdgt_templates = new CWidget('search_templates',$table);
		$wdgt_templates->setClass('header');
		$wdgt_templates->addHeader(S_TEMPLATES, SPACE);
		$right_tab->addRow($wdgt_templates);
	}
//----------------

	$td_l = new CCol($left_tab);
	$td_l->setAttribute('valign','top');

	$td_r = new CCol($right_tab);
	$td_r->setAttribute('valign','top');

	$outer_table = new CTable();
	$outer_table->setAttribute('border',0);
	$outer_table->setCellPadding(1);
	$outer_table->setCellSpacing(1);
	$outer_table->addRow(array($td_l,$td_r));

	$search_wdgt->addItem($outer_table);

	$search_wdgt->show();
?>
<?php

include_once('include/page_footer.php');

?>
