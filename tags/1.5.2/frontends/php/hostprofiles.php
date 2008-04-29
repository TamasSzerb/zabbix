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
	require_once "include/hosts.inc.php";
	require_once "include/forms.inc.php";

	$page["title"] = "S_HOST_PROFILES";
	$page["file"] = "hostprofiles.php";
	$page['hist_arg'] = array('groupid','hostid');
	
include_once "include/page_header.php";

?>
<?php
//		VAR			TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION
	$fields=array(
		"groupid"=>		array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID,	NULL),
		"hostid"=>		array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID,	NULL)
	);

	check_fields($fields);
	validate_sort_and_sortorder('h.host',ZBX_SORT_UP);
	
	validate_group(PERM_READ_ONLY, array("allow_all_hosts","always_select_first_host","monitored_hosts","with_items"));
?>
<?php
	$r_form = new CForm();
	$r_form->SetMethod('get');

	$cmbGroup = new CComboBox("groupid",$_REQUEST["groupid"],"submit()");

	$cmbGroup->AddItem(0,S_ALL_SMALL);
	
	$availiable_hosts = get_accessible_hosts_by_user($USER_DETAILS,PERM_READ_LIST);

	$result=DBselect('SELECT DISTINCT g.groupid,g.name '.
		' FROM groups g, hosts_groups hg, hosts h, items i '.
		' WHERE h.hostid IN ('.$availiable_hosts.') '.
			' AND hg.groupid=g.groupid '.
			' AND h.status='.HOST_STATUS_MONITORED.
			' AND h.hostid=i.hostid '.
			' AND hg.hostid=h.hostid '.
		' ORDER BY g.name');
	while($row=DBfetch($result)){
		$cmbGroup->AddItem(
				$row['groupid'],
				get_node_name_by_elid($row['groupid']).$row['name']
				);
	}
	$r_form->AddItem(array(S_GROUP.SPACE,$cmbGroup));
	
	show_table_header(S_HOST_PROFILES_BIG, $r_form);
?>

<?php
	if(isset($_REQUEST["hostid"])){
		echo SBR;
		insert_host_profile_form();
	}
	else{
		$table = new CTableInfo();
		$table->setHeader(array(
			is_show_subnodes() ? make_sorting_link(S_NODE,'h.hostid') : null,
			make_sorting_link(S_HOST,'h.host'),
			make_sorting_link(S_NAME,'p.name'),
			make_sorting_link(S_OS,'p.os'),
			make_sorting_link(S_SERIALNO,'p.serialno'),
			make_sorting_link(S_TAG,'p.tag'),
			make_sorting_link(S_MACADDRESS,'p.macaddress'))
		);

		if($_REQUEST["groupid"] > 0)
		{
			$sql='SELECT h.hostid,h.host,p.name,p.os,p.serialno,p.tag,p.macaddress'.
				' FROM hosts h,hosts_profiles p,hosts_groups hg '.
				' WHERE h.hostid=p.hostid'.
					' and h.hostid=hg.hostid '.
					' and hg.groupid='.$_REQUEST['groupid'].
					' and h.hostid in ('.$availiable_hosts.') '.
				order_by('h.host,h.hostid,p.name,p.os,p.serialno,p.tag,p.macaddress');
		}
		else{
		
			$sql='SELECT h.hostid,h.host,p.name,p.os,p.serialno,p.tag,p.macaddress'.
				' FROM hosts h,hosts_profiles p '.
				' WHERE h.hostid=p.hostid'.
					' AND h.hostid in ('.$availiable_hosts.') '.
				order_by('h.host,h.hostid,p.name,p.os,p.serialno,p.tag,p.macaddress');
		}

		$result=DBselect($sql);
		while($row=DBfetch($result))
		{
			$table->AddRow(array(
				get_node_name_by_elid($row['hostid']),
				new CLink($row["host"],"?hostid=".$row["hostid"].url_param("groupid"),"action"),
				$row["name"],
				$row["os"],
				$row["serialno"],
				$row["tag"],
				$row["macaddress"]
				));
		}
		$table->show();
	}
?>
<?php

include_once "include/page_footer.php";

?>
