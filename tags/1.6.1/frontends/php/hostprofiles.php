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
		'groupid'=>		array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID,	NULL),
		'hostid'=>		array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID,	NULL),
		'prof_type'=>	array(T_ZBX_INT, O_OPT,	P_SYS,	null,	NULL),
	);

	check_fields($fields);
	validate_sort_and_sortorder('h.host',ZBX_SORT_UP);
	validate_group(PERM_READ_ONLY, array("allow_all_hosts","always_select_first_host","monitored_hosts","with_items"));
	
	$prof_type = get_request('prof_type',0);
?>
<?php
	$available_hosts = get_accessible_hosts_by_user($USER_DETAILS,PERM_READ_LIST,PERM_RES_IDS_ARRAY);
	
	$r_form = new CForm();
	$r_form->SetMethod('get');

	$cmbGroup = new CComboBox("groupid",$_REQUEST["groupid"],"submit()");
	$cmbGroup->AddItem(0,S_ALL_SMALL);
		
	$result=DBselect('SELECT DISTINCT g.groupid,g.name '.
		' FROM groups g, hosts_groups hg, hosts h '.
		' WHERE '.DBcondition('h.hostid',$available_hosts).
			' AND hg.groupid=g.groupid '.
			' AND h.status='.HOST_STATUS_MONITORED.
			' AND hg.hostid=h.hostid '.
			' AND EXISTS (SELECT i.hostid FROM items i WHERE i.hostid=h.hostid) '.
		' ORDER BY g.name');
	while($row=DBfetch($result)){
		$cmbGroup->AddItem(
				$row['groupid'],
				get_node_name_by_elid($row['groupid']).$row['name']
				);
	}
	
	$r_form->AddItem(array(S_GROUP.SPACE,$cmbGroup));
	
	$cmbProf = new CComboBox('prof_type',$prof_type,'javascript: submit();');
	$cmbProf->Additem(0, S_NORMAL);
	$cmbProf->Additem(1, S_EXTENDED);
	
	$r_form->AddItem(array(SPACE.S_HOST_PROFILES.SPACE,$cmbProf));
	
	show_table_header(S_HOST_PROFILES_BIG, $r_form);
?>

<?php
	if(isset($_REQUEST["hostid"])){
		echo SBR;

		if($prof_type){
			insert_host_profile_ext_form();
		}
		else{
			insert_host_profile_form();
		}
	}
	else{
		$table = new CTableInfo();
		if($prof_type){
			$table->setHeader(array(
				is_show_subnodes() ? make_sorting_link(S_NODE,'h.hostid') : null,
				make_sorting_link(S_HOST,'h.host'),
			   ($_REQUEST["groupid"] > 0)?null:make_sorting_link(S_GROUP,'g.name'),
				make_sorting_link(S_DEVICE_OS_SHORT,'hpe.device_os_short'),
				make_sorting_link(S_DEVICE_HW_ARCH,'hpe.device_hw_arch'),
				make_sorting_link(S_DEVICE_TYPE,'hpe.device_type'),
				make_sorting_link(S_DEVICE_STATUS,'hpe.device_status'))
			);
			
			if($_REQUEST["groupid"] > 0){
				$sql='SELECT DISTINCT h.hostid,h.host,hpe.device_os_short,hpe.device_hw_arch,hpe.device_type,hpe.device_status'.
					' FROM hosts h,hosts_profiles_ext hpe,hosts_groups hg,groups g '.
					' WHERE h.hostid=hpe.hostid '.
						' AND h.hostid=hg.hostid '.
						' AND hg.groupid='.$_REQUEST['groupid'].
						' AND '.DBcondition('h.hostid',$available_hosts).
					order_by('h.host,h.hostid,g.name,hpe.device_os_short,hpe.device_hw_arch,hpe.device_type,hpe.device_status');
			}
			else{
				$sql='SELECT h.hostid,h.host,g.name,hpe.device_os_short,hpe.device_hw_arch,hpe.device_type,hpe.device_status'.
					' FROM hosts h,hosts_profiles_ext hpe,hosts_groups hg,groups g '.
					' WHERE h.hostid=hpe.hostid'.
						' AND h.hostid=hg.hostid '.
						' AND hg.groupid=g.groupid'.
						' AND '.DBcondition('h.hostid',$available_hosts).
					order_by('h.host,h.hostid,g.name,hpe.device_os_short,hpe.device_hw_arch,hpe.device_type,hpe.device_status');
			}
			$result=DBselect($sql);
			while($row=DBfetch($result)){
				$table->AddRow(array(
					get_node_name_by_elid($row['hostid']),
					new CLink($row["host"],"?hostid=".$row["hostid"].url_param("groupid").'&prof_type='.$prof_type,"action"),
					($_REQUEST["groupid"] > 0)?null:$row["name"],
					$row["device_os_short"],
					$row["device_hw_arch"],
					$row["device_type"],
					$row["device_status"]
				));
			}

		}
		else{
			$table->setHeader(array(
				is_show_subnodes() ? make_sorting_link(S_NODE,'h.hostid') : null,
				make_sorting_link(S_HOST,'h.host'),
				make_sorting_link(S_NAME,'p.name'),
				make_sorting_link(S_OS,'p.os'),
				make_sorting_link(S_SERIALNO,'p.serialno'),
				make_sorting_link(S_TAG,'p.tag'),
				make_sorting_link(S_MACADDRESS,'p.macaddress'))
			);
			if($_REQUEST["groupid"] > 0){
				$sql='SELECT h.hostid,h.host,p.name,p.os,p.serialno,p.tag,p.macaddress'.
					' FROM hosts h,hosts_profiles p,hosts_groups hg '.
					' WHERE h.hostid=p.hostid'.
						' and h.hostid=hg.hostid '.
						' and hg.groupid='.$_REQUEST['groupid'].
						' and '.DBcondition('h.hostid',$available_hosts).
					order_by('h.host,h.hostid,p.name,p.os,p.serialno,p.tag,p.macaddress');
			}
			else{
				$sql='SELECT h.hostid,h.host,p.name,p.os,p.serialno,p.tag,p.macaddress'.
					' FROM hosts h,hosts_profiles p '.
					' WHERE h.hostid=p.hostid'.
						' AND '.DBcondition('h.hostid',$available_hosts).
					order_by('h.host,h.hostid,p.name,p.os,p.serialno,p.tag,p.macaddress');
			}
			$result=DBselect($sql);
			while($row=DBfetch($result)){
				$table->AddRow(array(
					get_node_name_by_elid($row['hostid']),
					new CLink($row["host"],'?hostid='.$row['hostid'].url_param('groupid').'&prof_type='.$prof_type,"action"),
					$row["name"],
					$row["os"],
					$row["serialno"],
					$row["tag"],
					$row["macaddress"]
				));
			}

		}

		$table->show();
	}
?>
<?php

include_once "include/page_footer.php";

?>