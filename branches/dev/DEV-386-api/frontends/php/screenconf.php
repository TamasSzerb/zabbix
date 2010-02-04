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
require_once('include/screens.inc.php');
require_once('include/forms.inc.php');
require_once('include/maps.inc.php');

$page['title'] = 'S_SCREENS';
$page['file'] = 'screenconf.php';
$page['hist_arg'] = array('config');

include_once('include/page_header.php');

?>
<?php
	$_REQUEST['config'] = get_request('config',get_profile('web.screenconf.config',0));

//		VAR			TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION
	$fields=array(
		'config'=>		array(T_ZBX_INT, O_OPT,	P_SYS,	IN('0,1'),	null), // 0 - screens, 1 - slides
		'screens'=>		array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID, NULL),
		'shows'=>		array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID, NULL),

		'screenid'=>		array(T_ZBX_INT, O_NO,	 P_SYS,	DB_ID,		'(isset({config})&&({config}==0))&&(isset({form})&&({form}=="update"))'),
		'hsize'=>		array(T_ZBX_INT, O_OPT,  null,  BETWEEN(1,100),	'(isset({config})&&({config}==0))&&isset({save})'),
		'vsize'=>		array(T_ZBX_INT, O_OPT,  null,  BETWEEN(1,100),	'(isset({config})&&({config}==0))&&isset({save})'),

		'slideshowid'=>		array(T_ZBX_INT, O_NO,	 P_SYS,	DB_ID,		'(isset({config})&&({config}==1))&&(isset({form})&&({form}=="update"))'),
		'name'=>		array(T_ZBX_STR, O_OPT,  null,	NOT_EMPTY,		'isset({save})'),
		'delay'=>		array(T_ZBX_INT, O_OPT,  null,	BETWEEN(1,86400),'(isset({config})&&({config}==1))&&isset({save})'),

		'steps'=>		array(null,	O_OPT,	null,	null,	null),
		'new_step'=>		array(null,	O_OPT,	null,	null,	null),

		'move_up'=>		array(T_ZBX_INT, O_OPT,  P_ACT,  BETWEEN(0,65534), null),
		'move_down'=>		array(T_ZBX_INT, O_OPT,  P_ACT,  BETWEEN(0,65534), null),

		'edit_step'=>		array(T_ZBX_INT, O_OPT,  P_ACT,  BETWEEN(0,65534), null),
		'add_step'=>		array(T_ZBX_STR, O_OPT,  P_ACT,  null, null),
		'cancel_step'=>		array(T_ZBX_STR, O_OPT,  P_ACT,  null, null),

		'sel_step'=>		array(T_ZBX_INT, O_OPT,  P_ACT,  BETWEEN(0,65534), null),
		'del_sel_step'=>		array(T_ZBX_STR, O_OPT,  P_ACT,  null, null),
// actions
		'go'=>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, NULL, NULL),
		'clone'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	null,	null),
		'save'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	null,	null),
		'delete'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	null,	null),
		'cancel'=>		array(T_ZBX_STR, O_OPT, P_SYS,	null,	null),
		'form'=>		array(T_ZBX_STR, O_OPT, P_SYS,	null,	null),
		'form_refresh'=>	array(T_ZBX_INT, O_OPT,	null,	null,	null)
	);

	check_fields($fields);
	validate_sort_and_sortorder('s.name',ZBX_SORT_UP);

	$config_scr = $_REQUEST['config'] = get_request('config', 0);

	update_profile('web.screenconf.config', $_REQUEST['config'],PROFILE_TYPE_INT);
?>
<?php
	$_REQUEST['go'] = get_request('go', 'none');

	if( 0 == $config_scr ){
		if(isset($_REQUEST["screenid"])){
			if(!screen_accessible($_REQUEST["screenid"], PERM_READ_WRITE))
				access_deny();
		}

		if(isset($_REQUEST['clone']) && isset($_REQUEST['screenid'])){
			unset($_REQUEST['screenid']);
			$_REQUEST['form'] = 'clone';
		}
		else if(isset($_REQUEST['save'])){
			if(isset($_REQUEST['screenid'])){
				// TODO check permission by new value.
//				$result=update_screen($_REQUEST['screenid'],$_REQUEST['name'],$_REQUEST['hsize'],$_REQUEST['vsize']);
				$result = API::Screen()->update(array(
					'screenid' => $_REQUEST['screenid'],
					'name' => $_REQUEST['name'],
					'hsize' => $_REQUEST['hsize'],
					'vsize' => $_REQUEST['vsize']
				));
				if(!$result){
					error(API::Screen()->resetErrors());
				}
				$audit_action = AUDIT_ACTION_UPDATE;
				show_messages($result, S_SCREEN_UPDATED, S_CANNOT_UPDATE_SCREEN);
			}
			else{
				if(!count(get_accessible_nodes_by_user($USER_DETAILS,PERM_READ_WRITE,PERM_RES_IDS_ARRAY)))
					access_deny();

//				DBstart();
				$result = API::Screen()->create(array('name' => $_REQUEST['name'], 'hsize' => $_REQUEST['hsize'], 'vsize' => $_REQUEST['vsize']));
				if(!$result){
					error(API::Screen()->resetErrors());
				}
//				add_screen($_REQUEST['name'],$_REQUEST['hsize'],$_REQUEST['vsize']);
//				$result = DBend();

				$audit_action = AUDIT_ACTION_ADD;
				show_messages($result, S_SCREEN_ADDED, S_CANNOT_ADD_SCREEN);
			}
			if($result){
				add_audit($audit_action,AUDIT_RESOURCE_SCREEN,' Name ['.$_REQUEST['name'].'] ');
				unset($_REQUEST['form']);
				unset($_REQUEST['screenid']);
			}
		}
		if(isset($_REQUEST["delete"])&&isset($_REQUEST["screenid"])){
			if($screen = get_screen_by_screenid($_REQUEST["screenid"])){
				DBstart();
					delete_screen($_REQUEST["screenid"]);
				$result = DBend();

				show_messages($result, S_SCREEN_DELETED, S_CANNOT_DELETE_SCREEN);
				add_audit_if($result, AUDIT_ACTION_DELETE,AUDIT_RESOURCE_SCREEN," Name [".$screen['name']."] ");
			}
			unset($_REQUEST["screenid"]);
			unset($_REQUEST["form"]);
		}
		else if($_REQUEST['go'] == 'delete'){
			$go_result = true;
			$screens = get_request('screens', array());

			DBstart();
			foreach($screens as $screenid){
				$go_result &= delete_screen($screenid);
				if(!$go_result) break;
			}
			$go_result = DBend($go_result);

			if($go_result){
				unset($_REQUEST["form"]);
			}
			show_messages($go_result, S_SCREEN_DELETED, S_CANNOT_DELETE_SCREEN);
		}
	}
	else{
		if(isset($_REQUEST['slideshowid'])){
			if(!slideshow_accessible($_REQUEST['slideshowid'], PERM_READ_WRITE))
				access_deny();
		}

		if(isset($_REQUEST['clone']) && isset($_REQUEST['slideshowid'])){
			unset($_REQUEST['slideshowid']);
			$_REQUEST['form'] = 'clone';
		}
		else if(isset($_REQUEST['save'])){
			$slides = get_request('steps', array());

			if(isset($_REQUEST['slideshowid'])){ /* update */
				DBstart();
				update_slideshow($_REQUEST['slideshowid'],$_REQUEST['name'],$_REQUEST['delay'],$slides);
				$result = DBend();

				$audit_action = AUDIT_ACTION_UPDATE;
				show_messages($result, S_SLIDESHOW_UPDATED, S_CANNOT_UPDATE_SLIDESHOW);
			}
			else{ /* add */
				DBstart();
				$slideshowid = add_slideshow($_REQUEST['name'],$_REQUEST['delay'],$slides);
				$result = DBend($slideshowid);

				$audit_action = AUDIT_ACTION_ADD;
				show_messages($result, S_SLIDESHOW_ADDED, S_CANNOT_ADD_SLIDESHOW);
			}

			if($result){
				add_audit($audit_action,AUDIT_RESOURCE_SLIDESHOW," Name [".$_REQUEST['name']."] ");
				unset($_REQUEST['form'], $_REQUEST['slideshowid']);
			}
		}
		else if(isset($_REQUEST['cancel_step'])){
			unset($_REQUEST['add_step'], $_REQUEST['new_step']);
		}
		else if(isset($_REQUEST['add_step'])){
			if(isset($_REQUEST['new_step'])){
				if(isset($_REQUEST['new_step']['sid']))
					$_REQUEST['steps'][$_REQUEST['new_step']['sid']] = $_REQUEST['new_step'];
				else
					$_REQUEST['steps'][] = $_REQUEST['new_step'];

				unset($_REQUEST['add_step'], $_REQUEST['new_step']);
			}
			else{
				$_REQUEST['new_step'] = array();
			}
		}
		else if(isset($_REQUEST['edit_step'])){
			$_REQUEST['new_step'] = $_REQUEST['steps'][$_REQUEST['edit_step']];
			$_REQUEST['new_step']['sid'] = $_REQUEST['edit_step'];
		}
		else if(isset($_REQUEST['del_sel_step'])&&isset($_REQUEST['sel_step'])&&is_array($_REQUEST['sel_step'])){
			foreach($_REQUEST['sel_step'] as $sid)
				if(isset($_REQUEST['steps'][$sid]))
					unset($_REQUEST['steps'][$sid]);
		}
		else if(isset($_REQUEST['move_up']) && isset($_REQUEST['steps'][$_REQUEST['move_up']])){
			$new_id = $_REQUEST['move_up'] - 1;

			if(isset($_REQUEST['steps'][$new_id])){
				$tmp = $_REQUEST['steps'][$new_id];
				$_REQUEST['steps'][$new_id] = $_REQUEST['steps'][$_REQUEST['move_up']];
				$_REQUEST['steps'][$_REQUEST['move_up']] = $tmp;
			}
		}
		else if(isset($_REQUEST['move_down']) && isset($_REQUEST['steps'][$_REQUEST['move_down']])){
			$new_id = $_REQUEST['move_down'] + 1;

			if(isset($_REQUEST['steps'][$new_id])){
				$tmp = $_REQUEST['steps'][$new_id];
				$_REQUEST['steps'][$new_id] = $_REQUEST['steps'][$_REQUEST['move_down']];
				$_REQUEST['steps'][$_REQUEST['move_down']] = $tmp;
			}
		}
		else if(isset($_REQUEST['delete'])&&isset($_REQUEST['slideshowid'])){
			if($slideshow = get_slideshow_by_slideshowid($_REQUEST['slideshowid'])){

				DBstart();
					delete_slideshow($_REQUEST['slideshowid']);
				$result = DBend();

				show_messages($result, S_SLIDESHOW_DELETED, S_CANNOT_DELETE_SLIDESHOW);
				add_audit_if($result, AUDIT_ACTION_DELETE,AUDIT_RESOURCE_SLIDESHOW," Name [".$slideshow['name']."] ");
			}
			unset($_REQUEST['slideshowid']);
			unset($_REQUEST["form"]);
		}
		else if($_REQUEST['go'] == 'delete'){
			$go_result = true;
			$shows = get_request('shows', array());

			DBstart();
			foreach($shows as $showid){
				$go_result &= delete_slideshow($showid);
				if(!$go_result) break;
			}
			$go_result = DBend($go_result);

			if($go_result){
				unset($_REQUEST["form"]);
			}
			show_messages($go_result, S_SLIDESHOW_DELETED, S_CANNOT_DELETE_SLIDESHOW);
		}
	}

	if(($_REQUEST['go'] != 'none') && isset($go_result) && $go_result){
		$url = new CUrl();
		$path = $url->getPath();
		insert_js('cookie.eraseArray("'.$path.'")');
	}
?>
<?php
	$form = new CForm();
	$form->SetMethod('get');

	$cmbConfig = new CComboBox('config', $config_scr, 'submit()');
	$cmbConfig->addItem(0, S_SCREENS);
	$cmbConfig->addItem(1, S_SLIDESHOWS);

	$form->addItem($cmbConfig);
	$form->addItem(new CButton("form", 0 == $config_scr ? S_CREATE_SCREEN : S_SLIDESHOW));

	show_table_header(0 == $config_scr ? S_CONFIGURATION_OF_SCREENS_BIG : S_CONFIGURATION_OF_SLIDESHOWS_BIG, $form);
	echo SBR;

	if(0 == $config_scr){
		if(isset($_REQUEST['form'])){
			insert_screen_form();
		}
		else{
			$screen_wdgt = new CWidget();

			$form = new CForm();
			$form->setName('frm_screens');

			$numrows = new CDiv();
			$numrows->setAttribute('name', 'numrows');

			$screen_wdgt->addHeader(S_SCREENS_BIG);
			$screen_wdgt->addHeader($numrows);

			$table = new CTableInfo(S_NO_SCREENS_DEFINED);
			$table->SetHeader(array(
				new CCheckBox('all_screens', NULL, "checkAll('".$form->getName()."','all_screens','screens');"),
				make_sorting_header(S_NAME, 'name'),
				S_DIMENSION_COLS_ROWS,
				S_SCREEN)
			);

			$sortfield = getPageSortField('name');
			$sortorder = getPageSortOrder();
			$options = array(
				'editable' => 1,
				'extendoutput' => 1,
				'sortfield' => $sortfield,
				'sortorder' => $sortorder,
				'limit' => ($config['search_limit']+1)
			);

			$screens = API::Screen()->get($options);

			order_result($screens, $sortfield, $sortorder);
			$paging = getPagingLine($screens);

			foreach($screens as $num => $screen){
				$table->addRow(array(
					new CCheckBox('screens['.$screen['screenid'].']', NULL, NULL, $screen['screenid']),
					new CLink($screen["name"],'screenedit.php?screenid='.$screen['screenid']),
					$screen['hsize'].' x '.$screen['vsize'],
					new CLink(S_EDIT,'?config=0&form=update&screenid='.$screen['screenid'])
				));
			}

//goBox
			$goBox = new CComboBox('go');
			$goOption = new CComboItem('delete', S_DELETE_SELECTED);
			$goOption->setAttribute('confirm', 'Delete selected screens?');
			$goBox->addItem($goOption);

			// goButton name is necessary!!!
			$goButton = new CButton('goButton', S_GO);
			$goButton->setAttribute('id', 'goButton');

			$jsLocale = array(
				'S_CLOSE',
				'S_NO_ELEMENTS_SELECTES'
			);

			zbx_addJSLocale($jsLocale);

			zbx_add_post_js('chkbxRange.pageGoName = "screens";');
//---------
			$footer = get_table_header(array($goBox, $goButton));

			$table = array($paging, $table, $paging, $footer);
			$form->addItem($table);

			$screen_wdgt->addItem($form);
			$screen_wdgt->show();
		}
	}
	else{
		if(isset($_REQUEST["form"])){
			insert_slideshow_form();
		}
		else{
			$screen_wdgt = new CWidget();

			$form = new CForm();
			$form->setName('frm_shows');

			$numrows = new CDiv();
			$numrows->setAttribute('name','numrows');

			$screen_wdgt->addHeader(S_SLIDESHOWS_BIG);
//			$screen_wdgt->addHeader($numrows);

			$table = new CTableInfo(S_NO_SLIDESHOWS_DEFINED);
			$table->SetHeader(array(
				new CCheckBox('all_shows',NULL,"checkAll('".$form->getName()."','all_shows','shows');"),
				make_sorting_header(S_NAME,'s.name'),
				make_sorting_header(S_DELAY,'s.delay'),
				make_sorting_header(S_COUNT_OF_SLIDES,'cnt')
				));

/* sorting
			order_page_result($applications, 'name');

// PAGING UPPER
			$paging = getPagingLine($applications);
			$screen_wdgt->addItem($paging);
//-------*/
			$screen_wdgt->addItem(BR());

			$sql = 'SELECT s.slideshowid, s.name, s.delay, count(*) as cnt '.
					' FROM slideshows s '.
						' LEFT JOIN slides sl ON sl.slideshowid=s.slideshowid '.
					' WHERE '.DBin_node('s.slideshowid').
					' GROUP BY s.slideshowid,s.name,s.delay '.
					order_by('s.name,s.delay,cnt','s.slideshowid');
			$db_slides = DBselect($sql);
			while($slide_data = DBfetch($db_slides)){
				if(!slideshow_accessible($slide_data['slideshowid'], PERM_READ_WRITE)) continue;

				$table->addRow(array(
					new CCheckBox('shows['.$slide_data['slideshowid'].']', NULL, NULL, $slide_data['slideshowid']),
					new CLink($slide_data['name'],'?config=1&form=update&slideshowid='.$slide_data['slideshowid'],
						'action'),
					$slide_data['delay'],
					$slide_data['cnt']
					));
			}
// PAGING FOOTER
//			$table->addRow(new CCol($paging));
//			$screen_wdgt->addItem($paging);
//---------

// goBox
			$goBox = new CComboBox('go');
			$goOption = new CComboItem('delete', S_DELETE_SELECTED);
			$goOption->setAttribute('confirm',S_DELETE_SELECTED_SLIDESHOWS_Q);
			$goBox->addItem($goOption);

// goButton name is necessary!!!
			$goButton = new CButton('goButton',S_GO);
			$goButton->setAttribute('id','goButton');

            		$jsLocale = array(
                            		'S_CLOSE',
                            		'S_NO_ELEMENTS_SELECTES'
        	        );

            		zbx_addJSLocale($jsLocale);

			zbx_add_post_js('chkbxRange.pageGoName = "shows";');

			$table->setFooter(new CCol(array($goBox, $goButton)));
//---------
			$form->addItem($table);

			$screen_wdgt->addItem($form);
			$screen_wdgt->show();
		}

	}

?>
<?php

include_once('include/page_footer.php');

?>
