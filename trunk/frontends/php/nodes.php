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
	require_once "include/nodes.inc.php";

        $page["title"] = "S_NODES";
        $page["file"] = "nodes.php";

include_once "include/page_header.php";

	insert_confirm_javascript();
?>
<?php
	$fields=array(
//		VAR			TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION

// media form
		"nodeid"=>		array(T_ZBX_INT, O_NO,	null,	DB_ID,		'{form}=="update"'),
		
		"name"=>		array(T_ZBX_STR, O_OPT,	null,	NOT_EMPTY,	'isset({save})'),
		"timezone"=>		array(T_ZBX_INT, O_OPT,	null,	BETWEEN(-12,+13),'isset({save})'),
		"ip"=>			array(T_ZBX_IP,	 O_OPT,	null,	null,		'isset({save})'),
		"port"=>		array(T_ZBX_INT, O_OPT,	null,	BETWEEN(1,65535),'isset({save})'),
		"slave_history"=>	array(T_ZBX_INT, O_OPT,	null,	BETWEEN(0,65535),'isset({save})'),
		"slave_trends"=>	array(T_ZBX_INT, O_OPT,	null,	BETWEEN(0,65535),'isset({save})'),
/* actions */
		"save"=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		"delete"=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		"cancel"=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
/* other */
		"form"=>		array(T_ZBX_STR, O_OPT, P_SYS,	NULL,	NULL),
		"form_refresh"=>	array(T_ZBX_INT, O_OPT,	NULL,	NULL,	NULL)
	);

	check_fields($fields);
	
	$accessible_nodes = get_accessible_nodes_by_user($USER_DETAILS,PERM_READ_LIST);

	if(isset($_REQUEST["nodeid"]) && !in_array($_REQUEST["nodeid"], explode(',',$accessible_nodes)))
	{
		access_deny();
	}
?>
<?php
	if(isset($_REQUEST['save']))
	{
		$result = false;
		if(isset($_REQUEST['nodeid']))
		{ /* update */
			$audit_action = AUDIT_ACTION_UPDATE;
			$result = update_node($_REQUEST['nodeid'],
				$_REQUEST['name'], $_REQUEST['timezone'], $_REQUEST['ip'], $_REQUEST['port'],
				$_REQUEST['slave_history'], $_REQUEST['slave_trends']);
			$nodeid = $_REQUEST['nodeid'];
			show_messages($result, S_NODE_UPDATED, S_CANNOT_UPDATE_NODE);
		}
		else
		{ /* add */
			$audit_action = AUDIT_ACTION_ADD;
			$result = add_node(
				$_REQUEST['name'], $_REQUEST['timezone'], $_REQUEST['ip'], $_REQUEST['port'],
				$_REQUEST['slave_history'], $_REQUEST['slave_trends']);
			$nodeid = $result;
			
			show_messages($result, S_NODE_ADDED, S_CANNOT_ADD_NODE);
		}
		add_audit_if($result,$audit_action,AUDIT_RESOURCE_NODE,'Node ['.$_REQUEST['name'].'] id ['.$nodeid.']');
		if($result)
		{
			unset($_REQUEST['form']);
		}
	}
	elseif(isset($_REQUEST['delete']))
	{
		$node_data = get_node_by_nodeid($_REQUEST['nodeid']);
		$result = delete_node($_REQUEST['nodeid']);
		show_messages($result, S_NODE_DELETED, S_CANNOT_DELETE_NODE);
		add_audit_if($result,AUDIT_ACTION_DELETE,AUDIT_RESOURCE_NODE,'Node ['.$node_data['name'].'] id ['.$node_data['nodeid'].']');
		if($result)
		{
			unset($_REQUEST['form'],$node_data);
		}
	}
?>
<?php
	if(isset($_REQUEST["form"]))
	{
		insert_node_form();
	}
	else
	{
		$form = new CForm();
		$form->AddItem(new CButton('form',S_NEW_NODE));
		show_table_header(S_NODES_BIG,$form);

		$table=new CTableInfo(S_NO_NODES_DEFINED);
		$table->SetHeader(array(S_NAME,S_TYPE,S_TIME_ZONE,S_IP.':'.S_PORT));

		$db_nodes = DBselect('select * from nodes where nodeid in ('.
			get_accessible_nodes_by_user($USER_DETAILS,PERM_READ_LIST).') '.
			' order by nodetype desc, masterid, name ');
		while($row=DBfetch($db_nodes))
		{

			$table->AddRow(array(
				array(
					get_node_path($row['masterid']),
					new CLink(
						($row['nodetype'] ? new CSpan($row["name"], 'bold') : $row["name"]),
						"?&form=update&nodeid=".$row["nodeid"],'action')),
				$row['nodetype'] ? new CSpan(S_LOCAL,'bold') : S_REMOTE,
				new CSpan("GMT".sprintf("%+03d:00", $row['timezone']),	$row['nodetype'] ? 'bold' : null),
				new CSpan($row['ip'].':'.$row['port'], 			$row['nodetype'] ? 'bold' : null)
				));
		}
		$table->Show();
	}
?>
<?php

include_once "include/page_footer.php";

?>
