<?php
/*
** ZABBIX
** Copyright (C) 2000-2007 SIA Zabbix
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
	require_once('include/graphs.inc.php');
	require_once('include/screens.inc.php');
	require_once('include/blocks.inc.php');
	require_once('include/nodes.inc.php');

	$page['title'] = "S_CUSTOM_SCREENS";
	$page['file'] = 'screens.php';
	$page['hist_arg'] = array('config','elementid');
	$page['scripts'] = array('gmenu.js','scrollbar.js','sbox.js','sbinit.js'); //do not change order!!!

	$_REQUEST['config'] = get_request('config',0);
	if($_REQUEST['config'] == 1) redirect('slides.php');

	$page['type'] = detect_page_type(PAGE_TYPE_HTML);

	if((1 != $_REQUEST['config']) && (PAGE_TYPE_HTML == $page['type'])){
		define('ZBX_PAGE_DO_REFRESH', 1);
	}

include_once('include/page_header.php');

?>

<?php
//		VAR			TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION
	$fields=array(
		'config'=>		array(T_ZBX_INT, O_OPT,	P_SYS,	IN('0,1'),	null), // 0 - screens, 1 - slides

		'groupid'=>		array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID, null),
		'hostid'=>		array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID, null),

// STATUS OF TRIGGER
		'tr_groupid'=>	array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		null),
		'tr_hostid'=>	array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		null),


		'elementid'=>	array(T_ZBX_INT, O_OPT,	P_SYS|P_NZERO,	DB_ID,NULL),
		'step'=>		array(T_ZBX_INT, O_OPT,  P_SYS,		BETWEEN(0,65535),NULL),
		'from'=>		array(T_ZBX_INT, O_OPT,  P_SYS, 	BETWEEN(0,65535*65535),NULL),
		'period'=>		array(T_ZBX_INT, O_OPT,  P_SYS, 	null,NULL),
		'stime'=>		array(T_ZBX_STR, O_OPT,  P_SYS, 	NULL,NULL),

		'reset'=>		array(T_ZBX_STR, O_OPT,  P_SYS, 	IN("'reset'"),NULL),
		'fullscreen'=>	array(T_ZBX_INT, O_OPT,	P_SYS,		IN('0,1,2'),		NULL),
//ajax
		'favobj'=>		array(T_ZBX_STR, O_OPT, P_ACT,	NULL,			NULL),
		'favid'=>		array(T_ZBX_STR, O_OPT, P_ACT,  NOT_EMPTY,		'isset({favobj})'),

		'state'=>		array(T_ZBX_INT, O_OPT, P_ACT,  NOT_EMPTY,		NULL),
		'action'=>		array(T_ZBX_STR, O_OPT, P_ACT, 	IN("'add','remove'"),NULL)
	);

	check_fields($fields);

	if(isset($_REQUEST['favobj'])){
		if('hat' == $_REQUEST['favobj']){
			update_profile('web.screens.hats.'.$_REQUEST['favid'].'.state',$_REQUEST['state'], PROFILE_TYPE_INT);
		}
		if(in_array($_REQUEST['favobj'],array('screenid','slideshowid'))){
			$result = false;
			if('add' == $_REQUEST['action']){
				$result = add2favorites('web.favorite.screenids',$_REQUEST['favid'],$_REQUEST['favobj']);
				if($result){
					print('$("addrm_fav").title = "'.S_REMOVE_FROM.' '.S_FAVOURITES.'";'."\n");
					print('$("addrm_fav").onclick = function(){rm4favorites("'.$_REQUEST['favobj'].'","'.$_REQUEST['favid'].'",0);}'."\n");
				}
			}
			else if('remove' == $_REQUEST['action']){
				$result = rm4favorites('web.favorite.screenids',$_REQUEST['favid'],ZBX_FAVORITES_ALL,$_REQUEST['favobj']);

				if($result){
					print('$("addrm_fav").title = "'.S_ADD_TO.' '.S_FAVOURITES.'";'."\n");
					print('$("addrm_fav").onclick = function(){ add2favorites("'.$_REQUEST['favobj'].'","'.$_REQUEST['favid'].'");}'."\n");
				}
			}

			if((PAGE_TYPE_JS == $page['type']) && $result){
				print('switchElementsClass("addrm_fav","iconminus","iconplus");');
			}
		}
	}

	if((PAGE_TYPE_JS == $page['type']) || (PAGE_TYPE_HTML_BLOCK == $page['type'])){
		exit();
	}
?>
<?php
	$config = $_REQUEST['config'];

	$_REQUEST['elementid'] = get_request('elementid',get_profile('web.screens.elementid', null));

	if( 2 != $_REQUEST['fullscreen'] )
		update_profile('web.screens.elementid',$_REQUEST['elementid']);

	$_REQUEST['period'] = get_request('period',get_profile('web.screens.period', ZBX_PERIOD_DEFAULT, $_REQUEST['elementid']));
	if($_REQUEST['period'] >= ZBX_MIN_PERIOD){
		update_profile('web.screens.period',$_REQUEST['period'], PROFILE_TYPE_INT, $_REQUEST['elementid']);
	}
?>
<?php

	$elementid = get_request('elementid', null);
	if($elementid <= 0) $elementid = null;

	$screens_wdgt = new CWidget();

// HEADER
	$text = null;

	$form = new CForm();
	$form->setMethod('get');

	$form->addVar('fullscreen',$_REQUEST['fullscreen']);
	if(isset($_REQUEST['period']))	$form->addVar('period', $_REQUEST['period']);
	if(isset($_REQUEST['stime']))	$form->addVar('stime', $_REQUEST['stime']);

	$cmbConfig = new CComboBox('config', $config, "javascript: redirect('slides.php?config=1');");
	$cmbConfig->addItem(0, S_SCREENS);
	$cmbConfig->addItem(1, S_SLIDESHOWS);

	$form->addItem(array(S_SHOW.SPACE,$cmbConfig));

	$cmbElements = new CComboBox('elementid',$elementid,'submit()');
	unset($screen_correct);
	unset($first_screen);


	$result = DBselect('SELECT screenid as elementid, name '.
			' FROM screens '.
			' WHERE '.DBin_node('screenid').
			' ORDER BY name'
			);
	while($row=DBfetch($result)){
		if(!screen_accessible($row['elementid'], PERM_READ_ONLY))
			continue;

		$cmbElements->addItem(
				$row['elementid'],
				get_node_name_by_elid($row['elementid']).$row['name']
				);
		if((bccomp($elementid , $row['elementid']) == 0)) $element_correct = 1;
		if(!isset($first_element)) $first_element = $row['elementid'];
	}

	if(!isset($element_correct) && isset($first_element)){
		$elementid = $first_element;
	}

	if(isset($elementid)){
		if(!screen_accessible($elementid, PERM_READ_ONLY)) access_deny();
		$element = get_screen_by_screenid($elementid);

		if($element ){
			$text = $element['name'];
		}
	}

	if($cmbElements->ItemsCount() > 0) $form->addItem(array(SPACE.S_SCREENS.SPACE,$cmbElements));

	if((2 != $_REQUEST['fullscreen']) && !empty($elementid) && check_dynamic_items($elementid, 0)){
		if(!isset($_REQUEST['hostid'])){
			$_REQUEST['groupid'] = $_REQUEST['hostid'] = 0;
		}

		$options = array('allow_all_hosts','monitored_hosts','with_items');
		if(!$ZBX_WITH_ALL_NODES)	array_push($options,'only_current_node');

		$params = array();
		foreach($options as  $option) $params[$option] = 1;
		$PAGE_GROUPS = get_viewed_groups(PERM_READ_ONLY, $params);
		$PAGE_HOSTS = get_viewed_hosts(PERM_READ_ONLY, $PAGE_GROUPS['selected'], $params);
//SDI($_REQUEST['groupid'].' : '.$_REQUEST['hostid']);
		validate_group_with_host($PAGE_GROUPS,$PAGE_HOSTS);

		$available_groups = $PAGE_GROUPS['groupids'];
//		$available_hosts = get_accessible_hosts_by_user($USER_DETAILS,PERM_READ_ONLY);
		$available_hosts = $PAGE_HOSTS['hostids'];

		$cmbGroups = new CComboBox('groupid',$PAGE_GROUPS['selected'],'javascript: submit();');
		foreach($PAGE_GROUPS['groups'] as $groupid => $name){
			$cmbGroups->addItem($groupid, get_node_name_by_elid($groupid).$name);
		}
		$form->addItem(array(SPACE.S_GROUP.SPACE,$cmbGroups));


		$PAGE_HOSTS['hosts']['0'] = S_DEFAULT;
		$cmbHosts = new CComboBox('hostid',$PAGE_HOSTS['selected'],'javascript: submit();');
		foreach($PAGE_HOSTS['hosts'] as $hostid => $name){
			$cmbHosts->addItem($hostid, get_node_name_by_elid($hostid).$name);
		}
		$form->addItem(array(SPACE.S_HOST.SPACE,$cmbHosts));
	}
//-------------------
?>
<?php
	if(isset($elementid)){
		$effectiveperiod = navigation_bar_calc();


		$element = get_screen($elementid, 0, $effectiveperiod);

		$_REQUEST['elementid'] = $elementid;

		if( 2 != $_REQUEST['fullscreen'] ){

			$stime = time() - (31536000); // ~1year
			$bstime = time()-$effectiveperiod;

			if(isset($_REQUEST['stime'])){
				$bstime = $_REQUEST['stime'];
				$bstime = mktime(substr($bstime,8,2),substr($bstime,10,2),0,substr($bstime,4,2),substr($bstime,6,2),substr($bstime,0,4));
			}

 			$script = 	'scrollinit(0,'.$effectiveperiod.','.$stime.',0,'.$bstime.');
						 showgraphmenu("iframe");';

			zbx_add_post_js($script);
//			navigation_bar('screens.php',array('config','elementid'));
		}
	}
	else{
		$element = new CTableInfo(S_NO_SCREENS_DEFINED);
	}

	$icon = null;
	$fs_icon = null;
	if(isset($elementid) && $element ){
		if(infavorites('web.favorite.screenids',$elementid,'screenid')){
			$icon = new CDiv(SPACE,'iconminus');
			$icon->setAttribute('title',S_REMOVE_FROM.' '.S_FAVOURITES);
			$icon->addAction('onclick',new CJSscript("javascript: rm4favorites('screenid','".$elementid."',0);"));
		}
		else{
			$icon = new CDiv(SPACE,'iconplus');
			$icon->setAttribute('title',S_ADD_TO.' '.S_FAVOURITES);
			$icon->addAction('onclick',new CJSscript("javascript: add2favorites('screenid','".$elementid."');"));
		}
		$icon->setAttribute('id','addrm_fav');

		$url = '?elementid='.$elementid.($_REQUEST['fullscreen']?'':'&fullscreen=1');
		$url.=url_param('groupid').url_param('hostid');

		$fs_icon = new CDiv(SPACE,'fullscreen');
		$fs_icon->setAttribute('title',$_REQUEST['fullscreen']?S_NORMAL.' '.S_VIEW:S_FULLSCREEN);
		$fs_icon->addAction('onclick',new CJSscript("javascript: document.location = '".$url."';"));
	}

	$screens_wdgt->addPageHeader(S_SCREENS_BIG,array($icon,$fs_icon));

	$screens_wdgt->addHeader($text,$form);
	$screens_wdgt->addItem($element);

	$screens_wdgt->show();

	$scroll_div = new CDiv();
	$scroll_div->setAttribute('id','scroll_cntnr');
	$scroll_div->setAttribute('style','border: 0px #CC0000 solid; height: 25px; width: 800px;');
	$scroll_div->show();

	$jsmenu = new CPUMenu(null,170);
	$jsmenu->InsertJavaScript();
?>
<?php

include_once 'include/page_footer.php';

?>
