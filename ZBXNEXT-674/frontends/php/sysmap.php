<?php
/*
** Zabbix
** Copyright (C) 2000-2011 Zabbix SIA
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
require_once('include/maps.inc.php');
require_once('include/forms.inc.php');

$page['title'] = 'S_CONFIGURATION_OF_NETWORK_MAPS';
$page['file'] = 'sysmap.php';
$page['hist_arg'] = array('sysmapid');
$page['scripts'] = array('sysmap.tpl.js','class.cmap.js');
$page['type'] = detect_page_type();

include_once('include/page_header.php');
?>
<?php

//		VAR			TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION
	$fields=array(
		'sysmapid'=>	array(T_ZBX_INT, O_MAND, P_SYS,	DB_ID,NULL),

		'selementid'=>	array(T_ZBX_INT, O_OPT,	 P_SYS,	DB_ID,		NULL),
		'elementid'=>	array(T_ZBX_INT, O_OPT,  NULL, DB_ID,	'isset({save})'),
		'elementtype'=>	array(T_ZBX_INT, O_OPT,  NULL, IN('0,1,2,3,4'),	'isset({save})'),
		'label'=>	array(T_ZBX_STR, O_OPT,  NULL, NOT_EMPTY,	'isset({save})'),
		'x'=>		array(T_ZBX_INT, O_OPT,  NULL,  BETWEEN(0,65535),'isset({save})'),
		'y'=>           array(T_ZBX_INT, O_OPT,  NULL,  BETWEEN(0,65535),'isset({save})'),
		'iconid_off'=>	array(T_ZBX_INT, O_OPT,  NULL, DB_ID,		'isset({save})'),
		'iconid_on'=>	array(T_ZBX_INT, O_OPT,  NULL, DB_ID,		'isset({save})'),
		'iconid_disabled'=>	array(T_ZBX_INT, O_OPT,  NULL, DB_ID,		'isset({save})'),
		'url'=>		array(T_ZBX_STR, O_OPT,  NULL, NULL,		'isset({save})'),
		'label_location'=>array(T_ZBX_INT, O_OPT, NULL,	IN('-1,0,1,2,3'),'isset({save})'),

		'grid_size' => array(T_ZBX_INT, O_OPT,  NULL, IN('20, 40, 50, 75, 100'),'isset({save})'),
		'grid_show' => array(T_ZBX_INT, O_OPT,  NULL, IN('1, 0'),'isset({save})'),
		'grid_align' => array(T_ZBX_INT, O_OPT,  NULL, IN('1, 0'),'isset({save})'),

		'linkid'=>	array(T_ZBX_INT, O_OPT,	 P_SYS,	DB_ID,NULL),
		'selementid1'=>	array(T_ZBX_INT, O_OPT,  NULL, DB_ID.'{}!={selementid2}','isset({save_link})'),
		'selementid2'=> array(T_ZBX_INT, O_OPT,  NULL, DB_ID.'{}!={selementid1}','isset({save_link})'),
		'triggers'=>	array(T_ZBX_STR, O_OPT,  NULL, null,null),
		'drawtype'=>array(T_ZBX_INT, O_OPT,  NULL, IN('0,1,2,3,4'),'isset({save_link})'),
		'color'=>	array(T_ZBX_STR, O_OPT,  NULL, NOT_EMPTY,'isset({save_link})'),


// actions
		'save'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'save_link'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'delete'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'cancel'=>		array(T_ZBX_STR, O_OPT, P_SYS,	NULL,	NULL),

// other
		'form'=>		array(T_ZBX_STR, O_OPT, P_SYS,	NULL,	NULL),
		'form_refresh'=>	array(T_ZBX_INT, O_OPT,	NULL,	NULL,	NULL),

//ajax
		'favobj'=>		array(T_ZBX_STR, O_OPT, P_ACT,	NULL,	NULL),
		'favid'=>		array(T_ZBX_STR, O_OPT, P_ACT,  null,	NULL),
		'favcnt'=>		array(T_ZBX_INT, O_OPT,	null,	null,	null),

		'action'=>		array(T_ZBX_STR, O_OPT, P_ACT, 	NOT_EMPTY,		NULL),
		'state'=>		array(T_ZBX_INT, O_OPT, P_ACT,  NOT_EMPTY,		'isset({favobj}) && ("hat"=={favobj})'),

		'selements'=>	array(T_ZBX_STR, O_OPT,	P_SYS,	DB_ID, NULL),
		'links'=>		array(T_ZBX_STR, O_OPT,	P_SYS,	DB_ID, NULL),
	);

	check_fields($fields);

?>
<?php
// ACTION /////////////////////////////////////////////////////////////////////////////
	if(isset($_REQUEST['favobj'])){
		$json = new CJSON();
		if('sysmap' == $_REQUEST['favobj']){
			$sysmapid = get_request('sysmapid',0);
			$cmapid = get_request('favid',0);

			switch($_REQUEST['action']){
				case 'get':
					$action = '';

					$options = array(
						'sysmapids'=> $sysmapid,
						'editable' => 1,
						'output' => API_OUTPUT_EXTEND,
						'select_selements' => API_OUTPUT_EXTEND,
						'select_links' => API_OUTPUT_EXTEND
					);

					$sysmaps = API::Map()->get($options);
					$db_map = reset($sysmaps);

					expandMapLabels($db_map);
					$map_info = getSelementsInfo($db_map);
					add_elementNames($db_map['selements']);
//SDII($db_map);
					$action .= 'ZBX_SYSMAPS['.$cmapid.'].map.mselement["label_location"]='.$db_map['label_location'].'; '."\n";

					foreach($db_map['selements'] as $snum => $selement){
						$info = $map_info[$selement['selementid']];
//						$element['image'] = get_base64_icon($element);
						$selement['image'] = get_selement_iconid($selement, $info);
						$selement['urls'] = zbx_toHash($selement['urls'], 'name');

						$action .= 'ZBX_SYSMAPS['.$cmapid.'].map.add_selement('.zbx_jsvalue($selement, true).'); '."\n";

					}

					foreach($db_map['links'] as $enum => $link){
						foreach($link as $key => $value){
							if(is_int($key)) unset($link[$key]);
						}

						$link['linktriggers'] = zbx_toHash($link['linktriggers'], 'linktriggerid');
						foreach($link['linktriggers'] as $lnum => $linktrigger){
							$hosts = get_hosts_by_triggerid($linktrigger['triggerid']);
							if($host = DBfetch($hosts)){
								$description = $host['host'].':'.expand_trigger_description($linktrigger['triggerid']);
							}

							$link['linktriggers'][$lnum]['desc_exp'] = $description;
						}
						order_result($link['linktriggers'], 'desc_exp');
						$action .= 'ZBX_SYSMAPS['.$cmapid.'].map.add_link('.zbx_jsvalue($link).'); '."\n";
					}

					unset($db_map['selements']);
					unset($db_map['links']);

					$action .= 'ZBX_SYSMAPS['.$cmapid.'].map.sysmap = '.zbx_jsvalue($db_map, true).";\n";
					$action.= 'ZBX_SYSMAPS['.$cmapid.'].map.updateMapImage(); '."\n";
					$action.= 'ZBX_SYSMAPS['.$cmapid.'].map.updateSelementsIcon(); '."\n";

					print($action);
					break;
				case 'save':
					@ob_start();
					try{
						DBstart();

						$options = array(
							'sysmapids' => $sysmapid,
							'editable' => true,
							'output' => API_OUTPUT_SHORTEN,
						);
						$sysmap = API::Map()->get($options);
						$sysmap = reset($sysmap);
						if($sysmap === false) throw new Exception(_('Access denied!')."\n\r");


						$sysmap_to_update = array(
							'sysmapid' => $sysmap['sysmapid'],
							'grid_size' => $_REQUEST['grid_size'],
							'grid_show' => $_REQUEST['grid_show'],
							'grid_align' => $_REQUEST['grid_align'],
							'links' => $json->decode(get_request('links', '[]'), true),
							'selements' => $json->decode(get_request('selements', '[]'), true)
						);
						$result = API::Map()->update($sysmap_to_update);

						if($result !== false)
							print('if(Confirm("'._('Map is saved! Return?').'")){ location.href = "sysmaps.php"; }');
						else
							throw new Exception(_('Map save operation failed.')."\n\r");

						DBend(true);
					}
					catch(Exception $e){
						DBend(false);
						$msg = array($e->getMessage());
						foreach(clear_messages() as $errMsg) $msg[] = $errMsg['type'].': '.$errMsg['message'];

						ob_clean();

						print('alert('.zbx_jsvalue(implode("\n\r", $msg)).');');
					}
					@ob_flush();
					exit();
					break;
			}
		}

		if('selements' == $_REQUEST['favobj']){
			$sysmapid = get_request('sysmapid',0);
			$cmapid = get_request('favid',0);

			switch($_REQUEST['action']){
				case 'get_img':
					$selements = get_request('selements', '[]');
					$selements = $json->decode($selements, true);

					if(empty($selements)){
						print('ZBX_SYSMAPS['.$cmapid.'].map.info("'.S_GET_IMG_ELEMENT_DATA_NOT_FOUND.'"); ');
						break;
					}

					$selement = reset($selements);
					$selement['sysmapid'] = $sysmapid;

//					$selement['image'] = get_base64_icon($element);
					$selement['image'] = get_selement_iconid($selement);
					$selement['label_expanded'] = expand_map_element_label_by_data($selement);

					$action = '';
					$action.= 'ZBX_SYSMAPS['.$cmapid.'].map.add_selement('.zbx_jsvalue($selement, true).',1);';
//					$action.= 'ZBX_SYSMAPS['.$cmapid.'].map.updateMapImage();';

					print($action);
				break;
				case 'new_selement':
					$default_icon = get_default_image(false);

					$selements = get_request('selements', '[]');
					$selements = $json->decode($selements, true);
					if(!empty($selements)){
						$selement = reset($selements);

						$selement['iconid_off']	= $default_icon['imageid'];

//						$selement['image'] = get_base64_icon($element);
						$selement['image'] = get_selement_iconid($selement);

						$action = '';
						$action.= 'ZBX_SYSMAPS['.$cmapid.'].map.add_selement('.zbx_jsvalue($selement, true).',1);';
						$action.= 'ZBX_SYSMAPS['.$cmapid.'].map.updateMapImage();';
						//$action.= 'ZBX_SYSMAPS['.$cmapid.'].map.show_selement_list();';

						print($action);
					}
					else{
						print('ZBX_SYSMAPS['.$cmapid.'].map.info("'.S_GET_IMG_ELEMENT_DATA_NOT_FOUND.'"); ');
					}
				break;
				case 'create':
					$default_icon = get_default_image(false);

					$selements = get_request('selements', '[]');
					$selements = $json->decode($selements, true);
					if(!empty($selements)){
						$selement = reset($selements);

						$selement['iconid_off']	= $default_icon['imageid'];

//						$selement['image'] = get_base64_icon($element);
						$selement['image'] = get_selement_iconid($selement);

						print(zbx_jsvalue($selement, true));
					}
					else{
						print('ZBX_SYSMAPS['.$cmapid.'].map.info("'.S_GET_IMG_ELEMENT_DATA_NOT_FOUND.'"); ');
					}
				break;
			}
		}

		if('links' == $_REQUEST['favobj']){
			switch($_REQUEST['action']){
			}
		}
	}

	if(PAGE_TYPE_HTML != $page['type']){
		include_once('include/page_footer.php');
		exit();
	}
?>
<?php

	show_table_header(S_CONFIGURATION_OF_NETWORK_MAPS_BIG);

	if(isset($_REQUEST['sysmapid'])){
		$options = array(
			'sysmapids' => $_REQUEST['sysmapid'],
			'editable' => 1,
			'output' => API_OUTPUT_EXTEND,
		);
		$maps = API::Map()->get($options);

		if(empty($maps)) access_deny();
		else $sysmap = reset($maps);
	}

?>
<?php
	echo SBR;

// ELEMENTS
	$el_add = new CIcon(S_ADD_ELEMENT, 'iconplus');
	$el_add->setAttribute('id','selement_add');

	$el_rmv = new CIcon(S_REMOVE_ELEMENT, 'iconminus');
	$el_rmv->setAttribute('id','selement_rmv');
//-----------------

// CONNECTORS
	$cn_add = new CIcon(S_ADD_LINK, 'iconplus');
	$cn_add->setAttribute('id','link_add');

	$cn_rmv = new CIcon(S_REMOVE_LINK, 'iconminus');
	$cn_rmv->setAttribute('id','link_rmv');
//------------------------

// Side Menu
	$elcn_tab = new CTable();
	$elcn_tab->addRow(array(bold('E'),bold('L')));
	$elcn_tab->addRow(array($el_add,$cn_add));
	$elcn_tab->addRow(array($el_rmv,$cn_rmv));

	$td = new CCol($elcn_tab);
	$td->setAttribute('valign','top');
//----
	$save_btn = new CSubmit('save',S_SAVE);
	$save_btn->setAttribute('id','sysmap_save');

	$elcn_tab = new CTable(null,'textwhite');
	$menuRow = array();

	$gridShow = new CSpan(
		$sysmap['grid_show'] == SYSMAP_GRID_SHOW_ON ? S_SHOWN : S_HIDDEN,
		'whitelink'
	);
	$gridShow->setAttribute('id', 'gridshow');

	$gridAutoAlign = new CSpan(
		$sysmap['grid_align'] == SYSMAP_GRID_ALIGN_ON ? S_ON : S_OFF,
		'whitelink'
	);
	$gridAutoAlign->setAttribute('id', 'gridautoalign');


	$gridSize = new CComboBox('gridsize');

	// possible grid sizes, selecting the one saved to DB
	$possibleGridSizes = array(20, 40, 50, 75, 100);
	foreach($possibleGridSizes as $possibleGridSize){

		$gridSize->addItem(
			$possibleGridSize.'x'.$possibleGridSize,
			$possibleGridSize.'x'.$possibleGridSize,
			($sysmap['grid_size'] == $possibleGridSize ? 'yes' : NULL) // is selected
		);
	}

	$gridAlignAll = new CSubmit('gridalignall', S_ALIGN_ICONS);
	$gridAlignAll->setAttribute('id', 'gridalignall');

	$gridForm = new CDiv(array($gridSize, $gridAlignAll));
	$gridForm->setAttribute('id', 'gridalignblock');

	array_push($menuRow, S_MAP . ' "'.$sysmap['name'].'"');
	array_push($menuRow, SPACE.SPACE);
	array_push($menuRow, S_ICON.' [',$el_add,$el_rmv,']');
	array_push($menuRow, SPACE.SPACE);
	array_push($menuRow, S_LINK.' [',$cn_add,$cn_rmv,']');
	array_push($menuRow, SPACE.SPACE);
	array_push($menuRow, S_GRID.' [',$gridShow,'|',$gridAutoAlign,']');
	array_push($menuRow, SPACE, $gridForm);

	$elcn_tab->addRow($menuRow);
//	show_table_header($map['name'], $save_btn);
	show_table_header($elcn_tab, $save_btn);


	$sysmap_img = new CImg('images/general/tree/zero.gif','sysmap');
	$sysmap_img->setAttribute('id', 'sysmap_img');

	$table = new CTable(NULL,'map');
//	$table->addRow(array($td, $sysmap_img));
	$table->addRow($sysmap_img);
	$table->Show();

	$container = new CDiv(null);
	$container->setAttribute('id','sysmap_cnt');
	$container->setAttribute('style','position: absolute;');
	$container->Show();

	insert_js(get_selement_icons());
	insert_show_color_picker_javascript();

	zbx_add_post_js('create_map("sysmap_cnt", "'.$sysmap['sysmapid'].'");');

?>
<?php

include_once('include/page_footer.php');

?>
