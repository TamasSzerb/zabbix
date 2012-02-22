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
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/
?>
<?php
require_once('include/config.inc.php');
require_once('include/maps.inc.php');
require_once('include/ident.inc.php');
require_once('include/forms.inc.php');

if (isset($_REQUEST['go']) && ($_REQUEST['go'] == 'export') && isset($_REQUEST['maps'])) {
	$EXPORT_DATA = true;
	$page['type'] = detect_page_type(PAGE_TYPE_XML);
	$page['file'] = 'zbx_maps_export.xml';
}
else {
	$EXPORT_DATA = false;

	$page['type'] = detect_page_type(PAGE_TYPE_HTML);
	$page['title'] = 'S_NETWORK_MAPS';
	$page['file'] = 'sysmaps.php';
	$page['hist_arg'] = array();
}

require_once('include/page_header.php');


//		VAR			TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION
	$fields=array(
		'maps'=>			array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID, NULL),
		'sysmapid'=>		array(T_ZBX_INT, O_OPT,	 P_SYS,	DB_ID,NULL),
		'name'=>			array(T_ZBX_STR, O_OPT,	 NULL,	NOT_EMPTY,			'isset({save})'),
		'width'=>			array(T_ZBX_INT, O_OPT,	 NULL,	BETWEEN(0,65535),	'isset({save})'),
		'height'=>			array(T_ZBX_INT, O_OPT,	 NULL,	BETWEEN(0,65535),	'isset({save})'),
		'backgroundid'=>	array(T_ZBX_INT, O_OPT,	 NULL,	DB_ID,				'isset({save})'),
		'iconmapid'=>		array(T_ZBX_INT, O_OPT,	 NULL,	DB_ID,				'isset({save})'),
		'expandproblem'=>	array(T_ZBX_INT, O_OPT,	 NULL,	BETWEEN(0,1),		null),
		'markelements'=>	array(T_ZBX_INT, O_OPT,	 NULL,	BETWEEN(0,1),		null),
		'show_unack'=>		array(T_ZBX_INT, O_OPT,	 NULL,	BETWEEN(0,2),		null),
		'highlight'=>		array(T_ZBX_INT, O_OPT,	 NULL,	BETWEEN(0,1),		null),
		'label_format'=>	array(T_ZBX_INT, O_OPT,	 NULL,	BETWEEN(0,1),		null),
		'label_type_host'=>			array(T_ZBX_INT, O_OPT,	 NULL,	BETWEEN(MAP_LABEL_TYPE_LABEL,MAP_LABEL_TYPE_CUSTOM),		'isset({save})'),
		'label_type_hostgroup'=>	array(T_ZBX_INT, O_OPT,	 NULL,	BETWEEN(MAP_LABEL_TYPE_LABEL,MAP_LABEL_TYPE_CUSTOM),		'isset({save})'),
		'label_type_trigger'=>		array(T_ZBX_INT, O_OPT,	 NULL,	BETWEEN(MAP_LABEL_TYPE_LABEL,MAP_LABEL_TYPE_CUSTOM),		'isset({save})'),
		'label_type_map'=>			array(T_ZBX_INT, O_OPT,	 NULL,	BETWEEN(MAP_LABEL_TYPE_LABEL,MAP_LABEL_TYPE_CUSTOM),		'isset({save})'),
		'label_type_image'=>		array(T_ZBX_INT, O_OPT,	 NULL,	BETWEEN(MAP_LABEL_TYPE_LABEL,MAP_LABEL_TYPE_CUSTOM),		'isset({save})'),
		'label_string_host'=>		array(T_ZBX_STR, O_OPT,	 NULL,	null,		'isset({save})'),
		'label_string_hostgroup'=>	array(T_ZBX_STR, O_OPT,	 NULL,	null,		'isset({save})'),
		'label_string_trigger'=>	array(T_ZBX_STR, O_OPT,	 NULL,	null,		'isset({save})'),
		'label_string_map'=>		array(T_ZBX_STR, O_OPT,	 NULL,	null,		'isset({save})'),
		'label_string_image'=>		array(T_ZBX_STR, O_OPT,	 NULL,	null,		'isset({save})'),
		'label_type'=>		array(T_ZBX_INT, O_OPT,	 NULL,	BETWEEN(MAP_LABEL_TYPE_LABEL,MAP_LABEL_TYPE_CUSTOM),		'isset({save})'),
		'label_location'=>	array(T_ZBX_INT, O_OPT,	 NULL,	BETWEEN(0,3),		'isset({save})'),
		'urls'=>			array(T_ZBX_STR, O_OPT,	 NULL,	null,		null),
// Actions
		'save'=>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'delete'=>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'cancel'=>			array(T_ZBX_STR, O_OPT, P_SYS, NULL,	NULL),
		'go'=>				array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, NULL, NULL),
// Form
		'form'=>			array(T_ZBX_STR, O_OPT, P_SYS,	NULL,	NULL),
		'form_refresh'=>	array(T_ZBX_INT, O_OPT,	NULL,	NULL,	NULL),
	);

	check_fields($fields);
	validate_sort_and_sortorder('name', ZBX_SORT_UP);

	if(isset($_REQUEST['sysmapid'])){
		$options = array(
			'sysmapids' => $_REQUEST['sysmapid'],
			'editable' => true,
			'output' => API_OUTPUT_EXTEND,
		);
		$maps = API::Map()->get($options);

		if(empty($maps)){
			access_deny();
		}
		else{
			$sysmap = reset($maps);
		}
	}


	if($EXPORT_DATA){
		$maps = get_request('maps', array());

		$export = new CConfigurationExport(array('maps' => $maps));
		$export->setBuilder(new CConfigurationExportBuilder());
		$export->setWriter(CExportWriterFactory::getWriter('XMLWriter'));

		print($export->export());
		exit();
	}


	$_REQUEST['go'] = get_request('go', 'none');

	if(isset($_REQUEST['save'])){

		$map = array(
			'name' => $_REQUEST['name'],
			'width' => $_REQUEST['width'],
			'height' => $_REQUEST['height'],
			'backgroundid' => $_REQUEST['backgroundid'],
			'iconmapid' => $_REQUEST['iconmapid'],
			'highlight' => get_request('highlight', 0),
			'markelements' => get_request('markelements', 0),
			'expandproblem' => get_request('expandproblem', 0),
			'label_format' => get_request('label_format',0),
			'label_type_host' => get_request('label_type_host',2),
			'label_type_hostgroup' => get_request('label_type_hostgroup',2),
			'label_type_trigger' => get_request('label_type_trigger',2),
			'label_type_map' => get_request('label_type_map',2),
			'label_type_image' => get_request('label_type_image',2),
			'label_string_host' => get_request('label_string_host',''),
			'label_string_hostgroup' => get_request('label_string_hostgroup',''),
			'label_string_trigger' => get_request('label_string_trigger',''),
			'label_string_map' => get_request('label_string_map',''),
			'label_string_image' => get_request('label_string_image',''),
			'label_type' => $_REQUEST['label_type'],
			'label_location' => $_REQUEST['label_location'],
			'show_unack' => get_request('show_unack', 0),
			'urls' => get_request('urls', array())
		);

		foreach($map['urls'] as $unum => $url){
			if(zbx_empty($url['name']) && zbx_empty($url['url']))
				unset($map['urls'][$unum]);
		}

		if(isset($_REQUEST['sysmapid'])){
// TODO check permission by new value.
			$map['sysmapid'] = $_REQUEST['sysmapid'];
			$result = API::Map()->update($map);

			add_audit_if($result, AUDIT_ACTION_UPDATE,AUDIT_RESOURCE_MAP, 'Name ['.$_REQUEST['name'].']');
			show_messages($result, S_MAP_UPDATED, S_CANNOT_UPDATE_MAP);
		}
		else{
			if(!count(get_accessible_nodes_by_user($USER_DETAILS, PERM_READ_WRITE, PERM_RES_IDS_ARRAY)))
				access_deny();

			$result = API::Map()->create($map);

			add_audit_if($result, AUDIT_ACTION_ADD,AUDIT_RESOURCE_MAP, 'Name ['.$_REQUEST['name'].']');
			show_messages($result, S_MAP_ADDED, S_CANNOT_ADD_MAP);
		}

		if($result){
			unset($_REQUEST['form']);
		}
	}
	else if((isset($_REQUEST['delete']) && isset($_REQUEST['sysmapid'])) || ($_REQUEST['go'] == 'delete')){
		$sysmapids = get_request('maps', array());
		if(isset($_REQUEST['sysmapid'])){
			$sysmapids[] = $_REQUEST['sysmapid'];
		}

		$maps = API::Map()->get(array(
			'sysmapids' => $sysmapids,
			'output' => array('name'),
			'editable' => true
		));
		$go_result = API::Map()->delete($sysmapids);

		show_messages($go_result, S_MAP_DELETED, S_CANNOT_DELETE_MAP);
		if($go_result){
			unset($_REQUEST['form']);
			foreach($maps as $map){
				add_audit_ext(AUDIT_ACTION_DELETE, AUDIT_RESOURCE_MAP,
					$map['sysmapid'],
					$map['name'],
					null,null,null);
			}
		}
	}

	if(($_REQUEST['go'] != 'none') && isset($go_result) && $go_result){
		$url = new CUrl();
		$path = $url->getPath();
		insert_js('cookie.eraseArray("'.$path.'")');
	}


	$form = new CForm('get');
	$form->cleanItems();
	$form->addItem(new CSubmit('form', S_CREATE_MAP));
	$form->addItem(new CSubmit('form', S_IMPORT_MAP));

	$map_wdgt = new CWidget();
	$map_wdgt->addPageHeader(S_CONFIGURATION_OF_NETWORK_MAPS, $form);


	if(isset($_REQUEST['form'])){
		$sysmap = array();

		if(isset($_REQUEST['sysmapid'])){
			$sysmaps = API::Map()->get(array(
				'sysmapids' => $_REQUEST['sysmapid'],
				'output' => API_OUTPUT_EXTEND,
				'editable' => true
			));
			$sysmap = reset($sysmaps);
		}

		if(!isset($_REQUEST['sysmapid']) || isset($_REQUEST['form_refresh'])){
			$sysmap['name'] = get_request('name', '');
			$sysmap['width'] = get_request('width', 800);
			$sysmap['height'] = get_request('height', 600);
			$sysmap['backgroundid'] = get_request('backgroundid', 0);
			$sysmap['iconmapid'] = get_request('iconmapid', 0);
			$sysmap['label_format'] = get_request('label_format', 0);
			$sysmap['label_type_host'] = get_request('label_type_host', 2);
			$sysmap['label_type_hostgroup'] = get_request('label_type_hostgroup', 2);
			$sysmap['label_type_trigger'] = get_request('label_type_trigger', 2);
			$sysmap['label_type_map'] = get_request('label_type_map', 2);
			$sysmap['label_type_image'] = get_request('label_type_image', 2);
			$sysmap['label_string_host'] = get_request('label_string_host', '');
			$sysmap['label_string_hostgroup'] = get_request('label_string_hostgroup', '');
			$sysmap['label_string_trigger'] = get_request('label_string_trigger', '');
			$sysmap['label_string_map'] = get_request('label_string_map', '');
			$sysmap['label_string_image'] = get_request('label_string_image', '');
			$sysmap['label_type'] = get_request('label_type', 0);
			$sysmap['label_location'] = get_request('label_location', 0);
			$sysmap['highlight'] = get_request('highlight', 0);
			$sysmap['markelements'] = get_request('markelements', 0);
			$sysmap['expandproblem'] = get_request('expandproblem', 0);
			$sysmap['show_unack'] = get_request('show_unack', 0);

			$sysmap['urls'] = get_request('urls', array());
		}

		$formLoad = new CView('configuration.sysmap.edit', $sysmap);
		$map_wdgt->addItem($formLoad->render());
	}
	else{
		$form = new CForm();
		$form->setName('frm_maps');

		$numrows = new CDiv();
		$numrows->setAttribute('name','numrows');

		$map_wdgt->addHeader(S_MAPS_BIG);
		$map_wdgt->addHeader($numrows);

		$table = new CTableInfo(_('No maps defined.'));
		$table->setHeader(array(
			new CCheckBox('all_maps',NULL,"checkAll('".$form->getName()."','all_maps','maps');"),
			make_sorting_header(S_NAME,'name'),
			make_sorting_header(S_WIDTH,'width'),
			make_sorting_header(S_HEIGHT,'height'),
			S_EDIT
		));


		$sortfield = getPageSortField('name');
		$sortorder = getPageSortOrder();
		$options = array(
			'editable' => 1,
			'output' => API_OUTPUT_EXTEND,
			'sortfield' => $sortfield,
			'sortorder' => $sortorder,
			'limit' => ($config['search_limit']+1)
		);
		$maps = API::Map()->get($options);

		order_result($maps, $sortfield, $sortorder);
		$paging = getPagingLine($maps);

		foreach($maps as $mnum => $map){
			$table->addRow(array(
				new CCheckBox('maps['.$map['sysmapid'].']', NULL, NULL, $map['sysmapid']),
				new CLink($map['name'], 'sysmap.php?sysmapid='.$map['sysmapid']),
				$map['width'],
				$map['height'],
				new CLink(S_EDIT, 'sysmaps.php?form=update&sysmapid='.$map['sysmapid'].'#form')
			));
		}

// goBox
		$goBox = new CComboBox('go');
		$goBox->addItem('export', S_EXPORT_SELECTED);
		$goOption = new CComboItem('delete', _('Delete selected'));
		$goOption->setAttribute('confirm',S_DELETE_SELECTED_MAPS_Q);

		$goBox->addItem($goOption);

// goButton name is necessary!!!
		$goButton = new CSubmit('goButton',S_GO);
		$goButton->setAttribute('id','goButton');

		zbx_add_post_js('chkbxRange.pageGoName = "maps";');

		$footer = get_table_header(array($goBox, $goButton));
		$table = array($paging, $table, $paging, $footer);

		$form->addItem($table);
		$map_wdgt->addItem($form);
	}

	$map_wdgt->show();


require_once('include/page_footer.php');
