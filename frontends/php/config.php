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
	require_once('include/config.inc.php');
	require_once('include/images.inc.php');
	require_once('include/forms.inc.php');

	$page['title'] = "S_CONFIGURATION_OF_ZABBIX";
	$page['file'] = 'config.php';
	$page['hist_arg'] = array('config');

	include_once('include/page_header.php');

?>
<?php
	$fields=array(
//		VAR			TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION

		'config'=>		array(T_ZBX_INT, O_OPT,	NULL,	IN('0,3,5,6,7,8,9'),	NULL),

// other form
		'alert_history'=>	array(T_ZBX_INT, O_NO,	NULL,	BETWEEN(0,65535),		'isset({config})&&({config}==0)&&isset({save})'),
		'event_history'=>	array(T_ZBX_INT, O_NO,	NULL,	BETWEEN(0,65535),		'isset({config})&&({config}==0)&&isset({save})'),
		'work_period'=>		array(T_ZBX_STR, O_NO,	NULL,	NULL,					'isset({config})&&({config}==7)&&isset({save})'),
		'refresh_unsupported'=>	array(T_ZBX_INT, O_NO,	NULL,	BETWEEN(0,65535),	'isset({config})&&({config}==5)&&isset({save})'),
		'alert_usrgrpid'=>	array(T_ZBX_INT, O_NO,	NULL,	DB_ID,					'isset({config})&&({config}==5)&&isset({save})'),

// image form
		'imageid'=>		array(T_ZBX_INT, O_NO,	P_SYS,	DB_ID,						'isset({config})&&({config}==3)&&(isset({form})&&({form}=="update"))'),
		'name'=>		array(T_ZBX_STR, O_NO,	NULL,	NOT_EMPTY,					'isset({config})&&({config}==3)&&isset({save})'),
		'imagetype'=>		array(T_ZBX_INT, O_OPT,	NULL,	IN('1,2'),				'isset({config})&&({config}==3)&&(isset({save}))'),
//value mapping
		'valuemapid'=>		array(T_ZBX_INT, O_NO,	P_SYS,	DB_ID,					'isset({config})&&({config}==6)&&(isset({form})&&({form}=="update"))'),
		'mapname'=>		array(T_ZBX_STR, O_OPT,	NULL,	NOT_EMPTY, 					'isset({config})&&({config}==6)&&isset({save})'),
		'valuemap'=>		array(T_ZBX_STR, O_OPT, NULL,	NULL, 	NULL),
		'rem_value'=>		array(T_ZBX_INT, O_OPT, NULL,	BETWEEN(0,65535), NULL),
		'add_value'=>		array(T_ZBX_STR, O_OPT, NULL,	NOT_EMPTY, 'isset({add_map})'),
		'add_newvalue'=>	array(T_ZBX_STR, O_OPT, NULL,	NOT_EMPTY, 'isset({add_map})'),

/* actions */
		'add_map'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'del_map'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'save'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'delete'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'cancel'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
/* acknowledges */
		'event_ack_enable'=>	array(T_ZBX_INT, O_OPT, P_SYS|P_ACT,	IN('0,1'),	'isset({config})&&({config}==8)&&isset({save})'),
		'event_expire'=> 		array(T_ZBX_INT, O_OPT, P_SYS|P_ACT,	BETWEEN(1,65535),	'isset({config})&&({config}==8)&&isset({save})'),
		'event_show_max'=> 		array(T_ZBX_INT, O_OPT, P_SYS|P_ACT,	BETWEEN(1,65535),	'isset({config})&&({config}==8)&&isset({save})'),
/* Themes */
		'default_theme'=>		array(T_ZBX_STR, O_OPT,	NULL,	NOT_EMPTY,				'isset({config})&&({config}==9)&&isset({save})'),
/* other */
		'form'=>		array(T_ZBX_STR, O_OPT, P_SYS,	NULL,	NULL),
		'form_refresh'=>	array(T_ZBX_INT, O_OPT,	NULL,	NULL,	NULL)
	);
?>
<?php
	$_REQUEST['config'] = get_request('config',get_profile('web.config.config',0));
	
	check_fields($fields);

	update_profile('web.config.config',$_REQUEST['config'],PROFILE_TYPE_INT);

	$result = 0;
	if($_REQUEST['config']==3){
/* IMAGES ACTIONS */
		if(isset($_REQUEST['save'])){
			$file = isset($_FILES['image']) && $_FILES['image']['name'] != '' ? $_FILES['image'] : NULL;
			if(isset($_REQUEST['imageid'])){
	/* UPDATE */
				$result=update_image($_REQUEST['imageid'],$_REQUEST['name'],
					$_REQUEST['imagetype'],$file);

				$msg_ok = S_IMAGE_UPDATED;
				$msg_fail = S_CANNOT_UPDATE_IMAGE;
				$audit_action = 'Image ['.$_REQUEST['name'].'] updated';
			} 
			else {
	/* ADD */
				if(!count(get_accessible_nodes_by_user($USER_DETAILS,PERM_READ_WRITE,PERM_RES_IDS_ARRAY))){
					access_deny();
				}
				$result=add_image($_REQUEST['name'],$_REQUEST['imagetype'],$file);

				$msg_ok = S_IMAGE_ADDED;
				$msg_fail = S_CANNOT_ADD_IMAGE;
				$audit_action = 'Image ['.$_REQUEST['name'].'] added';
			}
			
			show_messages($result, $msg_ok, $msg_fail);
			if($result){
				add_audit(AUDIT_ACTION_UPDATE,AUDIT_RESOURCE_IMAGE,$audit_action);
				unset($_REQUEST['form']);
			}
		} 
		else if(isset($_REQUEST['delete'])&&isset($_REQUEST['imageid'])) {
	/* DELETE */
			$image = get_image_by_imageid($_REQUEST['imageid']);
			
			$result=delete_image($_REQUEST['imageid']);
			show_messages($result, S_IMAGE_DELETED, S_CANNOT_DELETE_IMAGE);
			
			if($result){
				add_audit(AUDIT_ACTION_UPDATE,AUDIT_RESOURCE_IMAGE,'Image ['.$image['name'].'] deleted');
				unset($_REQUEST['form']);
			}
			
			unset($image, $_REQUEST['imageid']);
		}
	}
	else if(isset($_REQUEST['save']) && ($_REQUEST['config']==8)){
		if(!count(get_accessible_nodes_by_user($USER_DETAILS,PERM_READ_WRITE,PERM_RES_IDS_ARRAY)))
			access_deny();

/* OTHER ACTIONS */
		$configs = array(
				'event_ack_enable' => get_request('event_ack_enable'),
				'event_expire' => get_request('event_expire'),
				'event_show_max' => get_request('event_show_max')
			);
		$result=update_config($configs);

		show_messages($result, S_CONFIGURATION_UPDATED, S_CONFIGURATION_WAS_NOT_UPDATED);

		if($result){
			$msg = array();
			if(!is_null($val = get_request('event_ack_enable')))
				$msg[] = S_EVENT_ACKNOWLEDGES.' ['.($val?(S_DISABLED):(S_ENABLED)).']';
			if(!is_null($val = get_request('event_expire')))
				$msg[] = S_SHOW_EVENTS_NOT_OLDER.SPACE.'('.S_DAYS.')'.' ['.$val.']';
			if(!is_null($val = get_request('event_show_max')))
				$msg[] = S_SHOW_EVENTS_MAX.' ['.$val.']';

			add_audit(AUDIT_ACTION_UPDATE,AUDIT_RESOURCE_ZABBIX_CONFIG,implode('; ',$msg));
		}		
	}
	else if(isset($_REQUEST['save']) && ($_REQUEST['config']==9)){
		if(!count(get_accessible_nodes_by_user($USER_DETAILS,PERM_READ_WRITE,PERM_RES_IDS_ARRAY)))
			access_deny();

/* THEME */
		$configs = array(
				'default_theme' => get_request('default_theme')
			);
		$result=update_config($configs);

		show_messages($result, S_CONFIGURATION_UPDATED, S_CONFIGURATION_WAS_NOT_UPDATED);

		if($result){
			$msg = S_DEFAULT_THEME.' ['.get_request('default_theme').']';
			add_audit(AUDIT_ACTION_UPDATE,AUDIT_RESOURCE_ZABBIX_CONFIG,$msg);
		}		
	}
	else if(isset($_REQUEST['save'])&&uint_in_array($_REQUEST['config'],array(0,5,7))){

		if(!count(get_accessible_nodes_by_user($USER_DETAILS,PERM_READ_WRITE,PERM_RES_IDS_ARRAY)))
			access_deny();

/* OTHER ACTIONS */
		$configs = array(
				'event_history' => get_request('event_history'),
				'alert_history' => get_request('alert_history'),
				'refresh_unsupported' => get_request('refresh_unsupported'),
				'work_period' => get_request('work_period'),
				'alert_usrgrpid' => get_request('alert_usrgrpid')
			);
		$result=update_config($configs);

		show_messages($result, S_CONFIGURATION_UPDATED, S_CONFIGURATION_WAS_NOT_UPDATED);
		if($result){
			$msg = array();
			if(!is_null($val = get_request('event_history')))
				$msg[] = S_DO_NOT_KEEP_EVENTS_OLDER_THAN.' ['.$val.']';
			if(!is_null($val = get_request('alert_history')))
				$msg[] = S_DO_NOT_KEEP_ACTIONS_OLDER_THAN.' ['.$val.']';
			if(!is_null($val = get_request('refresh_unsupported')))
				$msg[] = S_REFRESH_UNSUPPORTED_ITEMS.' ['.$val.']';
			if(!is_null($val = get_request('work_period')))
				$msg[] = S_WORKING_TIME.' ['.$val.']';
			if(!is_null($val = get_request('alert_usrgrpid'))){
				if(0 == $val) {
					$val = S_NONE;
				}
				else{
					$val = DBfetch(DBselect('SELECT name FROM usrgrp WHERE usrgrpid='.$val));
					$val = $val['name'];
				}

				$msg[] = S_USER_GROUP_FOR_DATABASE_DOWN_MESSAGE.' ['.$val.']';
			}

			add_audit(AUDIT_ACTION_UPDATE,AUDIT_RESOURCE_ZABBIX_CONFIG,implode('; ',$msg));
		}
	}
// VALUE MAPS
	else if($_REQUEST['config']==6){
		$_REQUEST['valuemap'] = get_request('valuemap',array());
		if(isset($_REQUEST['add_map'])){
			$added = 0;
			$cnt = count($_REQUEST['valuemap']);
			for($i=0; $i < $cnt; $i++){
				if($_REQUEST['valuemap'][$i]['value'] != $_REQUEST['add_value'])	continue;
				$_REQUEST['valuemap'][$i]['newvalue'] = $_REQUEST['add_newvalue'];
				$added = 1;
				break;
			}
			
			if($added == 0){
				if(!ctype_digit($_REQUEST['add_value']) || !is_string($_REQUEST['add_newvalue'])){
					info('Value maps are used to create a mapping between numeric values and string representations');
					show_messages(false,null,S_CANNNOT_ADD_VALUE_MAP);
				}
				else{
					array_push($_REQUEST['valuemap'],array(
						'value'		=> $_REQUEST['add_value'],
						'newvalue'	=> $_REQUEST['add_newvalue']));
				}
			}
		}
		else if(isset($_REQUEST['del_map'])&&isset($_REQUEST['rem_value'])){
		
			$_REQUEST['valuemap'] = get_request('valuemap',array());
			foreach($_REQUEST['rem_value'] as $val)
				unset($_REQUEST['valuemap'][$val]);
		}
		else if(isset($_REQUEST['save'])){
		
			$mapping = get_request('valuemap',array());
			if(isset($_REQUEST['valuemapid'])){
				$result = update_valuemap($_REQUEST['valuemapid'],$_REQUEST['mapname'], $mapping);
				$audit_action	= AUDIT_ACTION_UPDATE;
				$msg_ok		= S_VALUE_MAP_UPDATED;
				$msg_fail	= S_CANNNOT_UPDATE_VALUE_MAP;
				$valuemapid	= $_REQUEST['valuemapid'];
			}
			else{
				if(!count(get_accessible_nodes_by_user($USER_DETAILS,PERM_READ_WRITE,PERM_RES_IDS_ARRAY))){
					access_deny();
				}
				$result = add_valuemap($_REQUEST['mapname'], $mapping);
				$audit_action	= AUDIT_ACTION_ADD;
				$msg_ok		= S_VALUE_MAP_ADDED;
				$msg_fail	= S_CANNNOT_ADD_VALUE_MAP;
				$valuemapid	= $result;
			}
			
			if($result){
				add_audit($audit_action, AUDIT_RESOURCE_VALUE_MAP,
					S_VALUE_MAP.' ['.$_REQUEST['mapname'].'] ['.$valuemapid.']');
				unset($_REQUEST['form']);
			}
			show_messages($result,$msg_ok, $msg_fail);
		}
		else if(isset($_REQUEST['delete']) && isset($_REQUEST['valuemapid'])){
			$result = false;

			$sql = 'SELECT * FROM valuemaps WHERE '.DBin_node('valuemapid').' AND valuemapid='.$_REQUEST['valuemapid'];
			if($map_data = DBfetch(DBselect($sql))){
				$result = delete_valuemap($_REQUEST['valuemapid']);
			}
			
			if($result){
				add_audit(AUDIT_ACTION_DELETE, AUDIT_RESOURCE_VALUE_MAP,
					S_VALUE_MAP.' ['.$map_data['name'].'] ['.$map_data['valuemapid'].']');
				unset($_REQUEST['form']);
			}
			show_messages($result, S_VALUE_MAP_DELETED, S_CANNNOT_DELETE_VALUE_MAP);
		}
	}

?>

<?php

	$form = new CForm('config.php');
	$form->SetMethod('get');
	$cmbConfig = new CCombobox('config',$_REQUEST['config'],'submit()');
	$cmbConfig->AddItem(8,S_EVENTS);
	$cmbConfig->AddItem(0,S_HOUSEKEEPER);
//	$cmbConfig->AddItem(2,S_ESCALATION_RULES);
	$cmbConfig->AddItem(3,S_IMAGES);
	$cmbConfig->AddItem(9,S_THEMES);
//	$cmbConfig->AddItem(4,S_AUTOREGISTRATION);
	$cmbConfig->AddItem(6,S_VALUE_MAPPING);
	$cmbConfig->AddItem(7,S_WORKING_TIME);
	$cmbConfig->AddItem(5,S_OTHER);
	$form->AddItem($cmbConfig);
	switch($_REQUEST['config']){
	case 3:
		$form->AddItem(SPACE.'|'.SPACE);
		$form->AddItem(new CButton('form',S_CREATE_IMAGE));
		break;
	case 6:
		$form->AddItem(SPACE.'|'.SPACE);
		$form->AddItem(new CButton('form',S_CREATE_VALUE_MAP));
		break;
	}
	show_table_header(S_CONFIGURATION_OF_ZABBIX_BIG, $form);
	echo SBR;
?>
<?php

	if($_REQUEST['config']==0){
		insert_housekeeper_form();
	}
	else if($_REQUEST['config']==5){
		insert_other_parameters_form();
	}
	else if($_REQUEST['config']==7){
		insert_work_period_form();
	}
	else if($_REQUEST['config']==8){
		insert_event_ack_form();
	}
	else if($_REQUEST['config']==9){
		insert_themes_form();
	}
	else if($_REQUEST['config']==3){
		if(isset($_REQUEST['form'])){
			insert_image_form();
		}
		else{
			$imagetype = get_request('imagetype',IMAGE_TYPE_ICON);
			
			$r_form = new CForm();
			
			$cmbImg = new CComboBox('imagetype',$imagetype,'submit();');
			$cmbImg->AddItem(IMAGE_TYPE_ICON,S_ICON);
			$cmbImg->AddItem(IMAGE_TYPE_BACKGROUND,S_BACKGROUND);
			
			$r_form->AddItem(S_TYPE.SPACE);
			$r_form->AddItem($cmbImg);

			show_table_header(S_IMAGES_BIG,$r_form);

			$table = new CTableInfo(S_NO_IMAGES_DEFINED);
			$table->setHeader(array(S_NAME,S_TYPE,S_IMAGE));
	
			$result=DBselect('SELECT imageid,imagetype,name '.
						' FROM images'.
						' WHERE '.DBin_node('imageid').
							' AND imagetype='.$imagetype.
						' ORDER BY name');
			while($row=DBfetch($result)){
				if($row['imagetype'] == IMAGE_TYPE_ICON)	$imagetype=S_ICON;
				else if($row['imagetype'] == IMAGE_TYPE_BACKGROUND)	$imagetype=S_BACKGROUND;
				else				$imagetype=S_UNKNOWN;

				$name=new CLink($row['name'],'config.php?form=update'.url_param('config').'&imageid='.$row['imageid'],'action');

				$table->addRow(array(
					$name,
					$imagetype,
					$actions=new CLink(
						new CImg('image.php?height=24&imageid='.$row['imageid'],'no image',NULL),'image.php?imageid='.$row['imageid'])
					));
			}
			$table->show();
		}
	}
	else if($_REQUEST['config']==6){
		if(isset($_REQUEST['form'])){
			insert_value_mapping_form();
		}
		else{
			show_table_header(S_VALUE_MAPPING_BIG);
			
			$table = new CTableInfo();
			$table->SetHeader(array(S_NAME, S_VALUE_MAP));

			$db_valuemaps = DBselect('SELECT * FROM valuemaps WHERE '.DBin_node('valuemapid'));
			while($db_valuemap = DBfetch($db_valuemaps)){
				$mappings_row = array();
				$db_maps = DBselect('SELECT * FROM mappings'.
					' WHERE valuemapid='.$db_valuemap['valuemapid']);
					
				while($db_map = DBfetch($db_maps)){
					array_push($mappings_row, 
						$db_map['value'],
						SPACE.RARR.SPACE,
						$db_map['newvalue'],
						BR());
				}
				$table->AddRow(array(
					new CLink($db_valuemap['name'],'config.php?form=update&'.
						'valuemapid='.$db_valuemap['valuemapid'].url_param('config'),
						'action'),
					empty($mappings_row)?SPACE:$mappings_row
				));
			}
			
			$table->Show();
		}
	}
?>
<?php

include_once 'include/page_footer.php';

?>
