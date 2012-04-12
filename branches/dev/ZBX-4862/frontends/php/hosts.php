<?php
/*
** Zabbix
** Copyright (C) 2000-2012 Zabbix SIA
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


require_once dirname(__FILE__).'/include/config.inc.php';
require_once dirname(__FILE__).'/include/forms.inc.php';

if (isset($_REQUEST['go']) && $_REQUEST['go'] == 'export' && isset($_REQUEST['hosts'])) {
	$page['file'] = 'zbx_export_hosts.xml';
	$page['type'] = detect_page_type(PAGE_TYPE_XML);

	$EXPORT_DATA = true;
}
else {
	$page['title'] = _('Configuration of hosts');
	$page['file'] = 'hosts.php';
	$page['type'] = detect_page_type(PAGE_TYPE_HTML);
	$page['hist_arg'] = array('groupid');

	$EXPORT_DATA = false;
}

require_once dirname(__FILE__).'/include/page_header.php';


//		VAR			TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION
$fields=array(
//ARRAYS
	'hosts'=>			array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID, NULL),
	'groups'=>			array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID, NULL),
	'hostids'=>			array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID, NULL),
	'groupids'=>		array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID, NULL),
	'applications'=>	array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID, NULL),
// host
	'groupid'=>			array(T_ZBX_INT, O_OPT,	P_SYS, 	DB_ID,				NULL),
	'hostid'=>			array(T_ZBX_INT, O_OPT,	P_SYS,  DB_ID,			'isset({form})&&({form}=="update")'),
	'host'=>	array(T_ZBX_STR, O_OPT,	null,   NOT_EMPTY, 'isset({save})', _('Host name')),
	'visiblename'=>		array(T_ZBX_STR, O_OPT,	null,   null,			'isset({save})'),
	'proxy_hostid'=>	array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID,			'isset({save})'),
	'status'=>			array(T_ZBX_INT, O_OPT,	null,	IN('0,1,3'),		'isset({save})'),

	'newgroup'=>		array(T_ZBX_STR, O_OPT, null,   null,		null),
	'interfaces'=> array(T_ZBX_STR, O_OPT,	null,	NOT_EMPTY, 'isset({save})',
		_('Agent or SNMP or JMX or IPMI interface')),
	'mainInterfaces' => array(T_ZBX_INT, O_OPT,	null,	DB_ID,	null),
	'templates'=>		array(T_ZBX_STR, O_OPT,	null,	NOT_EMPTY,	null),
	'templates_rem'=>	array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,   null,	null),
	'clear_templates'=>	array(T_ZBX_INT, O_OPT,	null,	DB_ID,	null),

	'ipmi_authtype'=>	array(T_ZBX_INT, O_OPT,	NULL, BETWEEN(-1,6),	NULL),
	'ipmi_privilege'=>	array(T_ZBX_INT, O_OPT,	NULL, BETWEEN(0,5),		NULL),
	'ipmi_username'=>	array(T_ZBX_STR, O_OPT,	NULL, NULL,				NULL),
	'ipmi_password'=>	array(T_ZBX_STR, O_OPT,	NULL, NULL,				NULL),

	'mass_clear_tpls'=>		array(T_ZBX_STR, O_OPT, NULL, 			NULL,	NULL),

	'inventory_mode'=>		array(T_ZBX_INT, O_OPT, NULL, IN(HOST_INVENTORY_DISABLED.','.HOST_INVENTORY_MANUAL.','.HOST_INVENTORY_AUTOMATIC),	NULL),
	'host_inventory'=> 	array(T_ZBX_STR, O_OPT, P_UNSET_EMPTY,	NULL,   NULL),

	'macros_rem'=>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,   null,	null),
	'macros'=>				array(T_ZBX_STR, O_OPT, P_SYS,   null,	null),
	'macro_new'=>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,   null,	'isset({macro_add})'),
	'value_new'=>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,   null,	'isset({macro_add})'),
	'macro_add' =>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,   null,	null),
// mass update
	'massupdate'=>			array(T_ZBX_STR, O_OPT, P_SYS,	null,	null),
	'visible'=>			array(T_ZBX_STR, O_OPT,	NULL, 	NULL,	NULL),
// actions
	'go'=>					array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	null,	null),
// form
	'add_to_group'=>		array(T_ZBX_INT, O_OPT, P_SYS|P_ACT,	DB_ID,	null),
	'delete_from_group'=>	array(T_ZBX_INT, O_OPT, P_SYS|P_ACT,	DB_ID,	null),
	'unlink'=>				array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	null,	null),
	'unlink_and_clear'=>	array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	null,	null),
	'save'=>				array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	null,	null),
	'masssave'=>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	null,	null),
	'clone'=>				array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	null,	null),
	'full_clone'=>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	null,	null),
	'delete'=>				array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	null,	null),
	'cancel'=>				array(T_ZBX_STR, O_OPT, P_SYS,			null,	null),
// other
	'form'=>				array(T_ZBX_STR, O_OPT, P_SYS,	null,	null),
	'form_refresh'=>		array(T_ZBX_STR, O_OPT, null,	null,	null),
// Filter
	'filter_set' =>			array(T_ZBX_STR, O_OPT,	P_ACT,	null,	null),

	'filter_host'=>		array(T_ZBX_STR, O_OPT,  null,	null,	null),
	'filter_ip'=>		array(T_ZBX_STR, O_OPT,  null,	null,	null),
	'filter_dns'=>		array(T_ZBX_STR, O_OPT,  null,	null,	null),
	'filter_port'=>		array(T_ZBX_STR, O_OPT,  null,	null,	null),
//ajax
	'favobj'=>		array(T_ZBX_STR, O_OPT, P_ACT,	NULL,			NULL),
	'favref'=>		array(T_ZBX_STR, O_OPT, P_ACT,  NOT_EMPTY,		'isset({favobj})'),
	'favstate'=>	array(T_ZBX_INT, O_OPT, P_ACT,  NOT_EMPTY,		'isset({favobj})&&("filter"=={favobj})')
);

// OUTER DATA
check_fields($fields);
validate_sort_and_sortorder('name', ZBX_SORT_UP);

$_REQUEST['go'] = get_request('go', 'none');

// PERMISSIONS
if(get_request('groupid', 0) > 0){
	$groupids = available_groups($_REQUEST['groupid'], 1);
	if(empty($groupids)) access_deny();
}

if(get_request('hostid', 0) > 0){
	$hostids = available_hosts($_REQUEST['hostid'], 1);
	if(empty($hostids)) access_deny();
}


/* AJAX */
if(isset($_REQUEST['favobj'])){
	if('filter' == $_REQUEST['favobj']){
		CProfile::update('web.hosts.filter.state', $_REQUEST['favstate'], PROFILE_TYPE_INT);
	}
}

if((PAGE_TYPE_JS == $page['type']) || (PAGE_TYPE_HTML_BLOCK == $page['type'])){
	require_once dirname(__FILE__).'/include/page_footer.php';
	exit();
}
//--------

$hostids = get_request('hosts', array());

if ($EXPORT_DATA) {
	$export = new CConfigurationExport(array('hosts' => $hostids));
	$export->setBuilder(new CConfigurationExportBuilder());
	$export->setWriter(CExportWriterFactory::getWriter(CExportWriterFactory::XML));

	print($export->export());
	exit();
}

/* FILTER */
if(isset($_REQUEST['filter_set'])){
	$_REQUEST['filter_ip'] = get_request('filter_ip');
	$_REQUEST['filter_dns'] = get_request('filter_dns');
	$_REQUEST['filter_host'] = get_request('filter_host');
	$_REQUEST['filter_port'] = get_request('filter_port');

	CProfile::update('web.hosts.filter_ip', $_REQUEST['filter_ip'], PROFILE_TYPE_STR);
	CProfile::update('web.hosts.filter_dns', $_REQUEST['filter_dns'], PROFILE_TYPE_STR);
	CProfile::update('web.hosts.filter_host', $_REQUEST['filter_host'], PROFILE_TYPE_STR);
	CProfile::update('web.hosts.filter_port', $_REQUEST['filter_port'], PROFILE_TYPE_STR);
}
else{
	$_REQUEST['filter_ip'] = CProfile::get('web.hosts.filter_ip');
	$_REQUEST['filter_dns'] = CProfile::get('web.hosts.filter_dns');
	$_REQUEST['filter_host'] = CProfile::get('web.hosts.filter_host');
	$_REQUEST['filter_port'] = CProfile::get('web.hosts.filter_port');
}


/************ ACTIONS FOR HOSTS ****************/
// UNLINK HOST
if (isset($_REQUEST['unlink']) || isset($_REQUEST['unlink_and_clear'])) {
	$_REQUEST['clear_templates'] = get_request('clear_templates', array());

	if (isset($_REQUEST['unlink'])) {
		// templates_rem for old style removal in massupdate form
		if (isset($_REQUEST['templates_rem'])) {
			$unlink_templates = array_keys($_REQUEST['templates_rem']);
		}
		else {
			$unlink_templates = array_keys($_REQUEST['unlink']);
		}
	}
	else {
		$unlink_templates = array_keys($_REQUEST['unlink_and_clear']);
		$_REQUEST['clear_templates'] = array_merge($_REQUEST['clear_templates'], $unlink_templates);
	}

	foreach ($unlink_templates as $id) {
		unset($_REQUEST['templates'][$id]);
	}
}
// CLONE HOST
else if(isset($_REQUEST['clone']) && isset($_REQUEST['hostid'])){
	unset($_REQUEST['hostid']);
	$_REQUEST['form'] = 'clone';
}
// FULL CLONE HOST
else if(isset($_REQUEST['full_clone']) && isset($_REQUEST['hostid'])){
	$_REQUEST['form'] = 'full_clone';
}
// HOST MASS UPDATE
else if(isset($_REQUEST['go']) && ($_REQUEST['go'] == 'massupdate') && isset($_REQUEST['masssave'])){
	$hostids = get_request('hosts', array());
	$visible = get_request('visible', array());
	$_REQUEST['newgroup'] = get_request('newgroup', '');
	$_REQUEST['proxy_hostid'] = get_request('proxy_hostid', 0);
	$_REQUEST['templates'] = get_request('templates', array());

	try{
		DBstart();

		$hosts = array('hosts' => zbx_toObject($hostids, 'hostid'));

		$properties = array('proxy_hostid', 'ipmi_authtype',
			'ipmi_privilege', 'ipmi_username', 'ipmi_password', 'status');
		$new_values = array();
		foreach($properties as $property){
			if(isset($visible[$property])){
				$new_values[$property] = $_REQUEST[$property];
			}
		}

// INVENTORIES {{{
		if(isset($visible['inventory_mode'])){
			$new_values['inventory_mode'] = get_request('inventory_mode', HOST_INVENTORY_DISABLED);
			$new_values['inventory'] = $new_values['inventory_mode'] != HOST_INVENTORY_DISABLED ? get_request('host_inventory', array()) : array();
		}
// }}} INVENTORIES

		$newgroup = array();
		if (isset($visible['newgroup']) && !empty($_REQUEST['newgroup'])) {
			if (!$result = API::HostGroup()->create(array('name' => $_REQUEST['newgroup']))) {
				throw new Exception();
			}

			$newgroup = array('groupid' => reset($result['groupids']), 'name' => $_REQUEST['newgroup']);
		}

		$templates = array();
		if(isset($visible['template_table']) || isset($visible['template_table_r'])){
			$tplids = array_keys($_REQUEST['templates']);
			$templates = zbx_toObject($tplids, 'templateid');
		}

		if(isset($visible['groups'])){
			$hosts['groups'] = API::HostGroup()->get(array(
				'groupids' => get_request('groups', array()),
				'editable' => 1,
				'output' => API_OUTPUT_SHORTEN,
			));
			if(!empty($newgroup)){
				$hosts['groups'][] = $newgroup;
			}
		}
		if(isset($visible['template_table_r'])){
			if(isset($_REQUEST['mass_clear_tpls'])){
				$host_templates = API::Template()->get(array('hostids' => $hostids));
				$host_templateids = zbx_objectValues($host_templates, 'templateid');
				$templates_to_del = array_diff($host_templateids, $tplids);
				$hosts['templates_clear'] = zbx_toObject($templates_to_del, 'templateid');
			}
			$hosts['templates'] = $templates;
		}

		$result = API::Host()->massUpdate(array_merge($hosts, $new_values));
		if($result === false) throw new Exception();


		$add = array();
		if(!empty($templates) && isset($visible['template_table'])){
			$add['templates'] = $templates;
		}
		if(!empty($newgroup) && !isset($visible['groups'])){
			$add['groups'][] = $newgroup;
		}
		if(!empty($add)){
			$add['hosts'] = $hosts['hosts'];

			$result = API::Host()->massAdd($add);
			if($result === false) throw new Exception();
		}

		DBend(true);

		show_messages(true, _('Hosts updated'), null);

		unset($_REQUEST['massupdate']);
		unset($_REQUEST['form']);
		unset($_REQUEST['hosts']);

		$url = new CUrl();
		$path = $url->getPath();
		insert_js('cookie.eraseArray("'.$path.'")');
	}
	catch(Exception $e){
		DBend(false);
		show_messages(false, null, _('Cannot update hosts'));
	}

	unset($_REQUEST['save']);
}
// SAVE HOST
elseif (isset($_REQUEST['save'])) {
	if (!count(get_accessible_nodes_by_user($USER_DETAILS, PERM_READ_WRITE, PERM_RES_IDS_ARRAY))) {
		access_deny();
	}

	try {
		DBstart();

		$macros = get_request('macros', array());
		$interfaces = get_request('interfaces', array());
		$templates = get_request('templates', array());
		$templates_clear = get_request('clear_templates', array());
		$groups = get_request('groups', array());

		if (isset($_REQUEST['hostid']) && $_REQUEST['form'] != 'full_clone') {
			$create_new = false;
			$msg_ok = _('Host updated');
			$msg_fail = _('Cannot update host');
		}
		else {
			$create_new = true;
			$msg_ok = _('Host added');
			$msg_fail = _('Cannot add host');
		}

		$clone_hostid = false;
		if ($_REQUEST['form'] == 'full_clone') {
			$create_new = true;
			$clone_hostid = $_REQUEST['hostid'];
		}

		$templates = array_keys($templates);
		$templates = zbx_toObject($templates, 'templateid');
		$templates_clear = zbx_toObject($templates_clear, 'templateid');

		foreach($interfaces as $inum => $interface){
			if (zbx_empty($interface['ip']) && zbx_empty($interface['dns'])) {
				unset($interface[$inum]);
				continue;
			}

			if ($interface['isNew']) {
				unset($interfaces[$inum]['interfaceid']);
			}
			unset($interfaces[$inum]['isNew']);
			$interfaces[$inum]['main'] = 0;
		}

		if (isset($_REQUEST['mainInterfaces'][INTERFACE_TYPE_AGENT])) {
			$mainAgentId = $_REQUEST['mainInterfaces'][INTERFACE_TYPE_AGENT];
			$interfaces[$mainAgentId]['main'] = '1';
		}
		if (isset($_REQUEST['mainInterfaces'][INTERFACE_TYPE_SNMP])) {
			$snmpAgentId = $_REQUEST['mainInterfaces'][INTERFACE_TYPE_SNMP];
			$interfaces[$snmpAgentId]['main'] = '1';
		}
		if (isset($_REQUEST['mainInterfaces'][INTERFACE_TYPE_JMX])) {
			$ipmiAgentId = $_REQUEST['mainInterfaces'][INTERFACE_TYPE_JMX];
			$interfaces[$ipmiAgentId]['main'] = '1';
		}
		if (isset($_REQUEST['mainInterfaces'][INTERFACE_TYPE_IPMI])) {
			$jmxAgentId = $_REQUEST['mainInterfaces'][INTERFACE_TYPE_IPMI];
			$interfaces[$jmxAgentId]['main'] = '1';
		}

		foreach ($macros as $mnum => $macro) {
			if (zbx_empty($macro['macro']) && zbx_empty($macro['value'])) {
				unset($macros[$mnum]);
			}
		}

		$duplicatedMacros = array();
		foreach ($macros as $mnum => $macro) {
			// transform macros to uppercase {$aaa} => {$AAA}
			$macros[$mnum]['macro'] = zbx_strtoupper($macro['macro']);
		}

		// create new group
		if (!zbx_empty($_REQUEST['newgroup'])) {
			if (!$newGroup = API::HostGroup()->create(array('name' => $_REQUEST['newgroup']))) {
				throw new Exception();
			}
			$groups[] = reset($newGroup['groupids']);
		}
		$groups = zbx_toObject($groups, 'groupid');

		$host = array(
			'host' => $_REQUEST['host'],
			'name' => $_REQUEST['visiblename'],
			'status' => $_REQUEST['status'],
			'proxy_hostid' => get_request('proxy_hostid', 0),
			'ipmi_authtype' => get_request('ipmi_authtype'),
			'ipmi_privilege' => get_request('ipmi_privilege'),
			'ipmi_username' => get_request('ipmi_username'),
			'ipmi_password' => get_request('ipmi_password'),
			'groups' => $groups,
			'templates' => $templates,
			'interfaces' => $interfaces,
			'macros' => $macros,
			'inventory' => (get_request('inventory_mode') != HOST_INVENTORY_DISABLED) ? get_request('host_inventory', array()) : array(),
			'inventory_mode' => get_request('inventory_mode')
		);

		if ($create_new) {
			$hostids = API::Host()->create($host);
			if ($hostids) {
				$hostid = reset($hostids['hostids']);
			}
			else {
				throw new Exception();
			}

			add_audit_ext(AUDIT_ACTION_ADD, AUDIT_RESOURCE_HOST,
				$hostid,
				$host['host'],
				null,null,null);
		}
		else {
			$hostid = $host['hostid'] = $_REQUEST['hostid'];
			$host['templates_clear'] = $templates_clear;

			$host_old = API::Host()->get(array(
				'hostids' => $hostid,
				'editable' => true,
				'output' => API_OUTPUT_EXTEND
			));
			$host_old = reset($host_old);

			if (!API::Host()->update($host)) {
				throw new Exception();
			}

			$host_new = API::Host()->get(array(
				'hostids' => $hostid,
				'editable' => true,
				'output' => API_OUTPUT_EXTEND
			));
			$host_new = reset($host_new);

			add_audit_ext(AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_HOST,
				$host['hostid'],
				$host['host'],
				'hosts',
				$host_old,
				$host_new
			);
		}

// FULL CLONE {{{
		if ($clone_hostid && ($_REQUEST['form'] == 'full_clone')) {
			if (!copyApplications($clone_hostid, $hostid)) {
				throw new Exception();
			}

			if (!copyItems($clone_hostid, $hostid)) {
				throw new Exception();
			}

			if (!copyTriggers($clone_hostid, $hostid)) {
				throw new Exception();
			}

			// clone discovery rules
			$discoveryRules = API::DiscoveryRule()->get(array(
				'hostids' => $clone_hostid,
				'inherited' => false
			));
			if ($discoveryRules) {
				$copyDiscoveryRules = API::DiscoveryRule()->copy(array(
					'discoveryids' => zbx_objectValues($discoveryRules, 'itemid'),
					'hostids' => array($hostid)
				));
				if (!$copyDiscoveryRules) {
					throw new Exception();
				}
			}

			$graphs = API::Graph()->get(array(
				'hostids' => $clone_hostid,
				'selectItems' => API_OUTPUT_EXTEND,
				'output' => API_OUTPUT_EXTEND,
				'inherited' => false,
				'selectHosts' => API_OUTPUT_REFER,
				'filter' => array('flags' => ZBX_FLAG_DISCOVERY_NORMAL)
			));
			foreach($graphs as $gnum => $graph){
				if (count($graph['hosts']) > 1) {
					continue;
				}

				if (httpItemExists($graph['items'])) {
					continue;
				}

				if (!copy_graph_to_host($graph['graphid'], $hostid)) {
					throw new Exception();
				}
			}
		}
// }}} FULL CLONE

// }}} SAVE TRANSACTION

		$result = DBend(true);

		show_messages($result, $msg_ok, $msg_fail);

		unset($_REQUEST['form']);
		unset($_REQUEST['hostid']);
	}
	catch (Exception $e) {
		DBend(false);
		show_messages(false, $msg_ok, $msg_fail);
	}

	unset($_REQUEST['save']);
}
// DELETE HOST
else if(isset($_REQUEST['delete']) && isset($_REQUEST['hostid'])){
	DBstart();
	$result = API::Host()->delete(array('hostid' => $_REQUEST['hostid']));
	$result = DBend($result);

	show_messages($result, _('Host deleted'), _('Cannot delete host'));

	if($result){
		unset($_REQUEST['form']);
		unset($_REQUEST['hostid']);
	}
	unset($_REQUEST['delete']);
}
else if(isset($_REQUEST['chstatus']) && isset($_REQUEST['hostid'])){

	DBstart();
		$result = updateHostStatus($_REQUEST['hostid'], $_REQUEST['chstatus']);
	$result = DBend($result);

	show_messages($result, _('Host status updated'), _('Cannot update host status'));

	unset($_REQUEST['chstatus']);
	unset($_REQUEST['hostid']);
}

// -------- GO ---------------
// DELETE HOST
else if($_REQUEST['go'] == 'delete'){
	$hostids = get_request('hosts', array());

	DBstart();
	$go_result = API::Host()->delete(zbx_toObject($hostids,'hostid'));
	$go_result = DBend($go_result);
	show_messages($go_result, _('Host deleted'), _('Cannot delete host'));
}
// ACTIVATE/DISABLE HOSTS
else if(str_in_array($_REQUEST['go'], array('activate', 'disable'))){

	$status = ($_REQUEST['go'] == 'activate') ? HOST_STATUS_MONITORED : HOST_STATUS_NOT_MONITORED;
	$hosts = get_request('hosts', array());

	$act_hosts = available_hosts($hosts, 1);

	DBstart();
	$go_result = updateHostStatus($act_hosts, $status);
	$go_result = DBend($go_result);

	show_messages($go_result, _('Host status updated'), _('Cannot update host status'));
}

if(($_REQUEST['go'] != 'none') && isset($go_result) && $go_result){
	$url = new CUrl();
	$path = $url->getPath();
	insert_js('cookie.eraseArray("'.$path.'")');
}


$hosts_wdgt = new CWidget();

$options = array(
	'groups' => array(
		'real_hosts' => 1,
		'editable' => true
	),
	'groupid' => get_request('groupid', null),
);
$pageFilter = new CPageFilter($options);

$_REQUEST['groupid'] = $pageFilter->groupid;
$_REQUEST['hostid'] = get_request('hostid', 0);

if (($_REQUEST['go'] == 'massupdate') && isset($_REQUEST['hosts'])) {
	$hosts_wdgt->addPageHeader(_('CONFIGURATION OF HOSTS'));

	$hostForm = new CView('configuration.host.massupdate');
	$hosts_wdgt->addItem($hostForm->render());
}
elseif (isset($_REQUEST['form'])) {
	$hosts_wdgt->addPageHeader(_('CONFIGURATION OF HOSTS'));

	if ($hostid = get_request('hostid', 0)) {
		$hosts_wdgt->addItem(get_header_host_table('', $_REQUEST['hostid']));
	}

	$hostForm = new CView('configuration.host.edit');
	$hosts_wdgt->addItem($hostForm->render());
}
else {
	$frmForm = new CForm();
	$frmForm->cleanItems();
	$buttons = new CDiv(array(
		new CSubmit('form', _('Create')),
		new CButton('form', _('Import'), 'redirect("conf.import.php?rules_preset=host")')
	));
	$frmForm->addItem($buttons);
	$frmForm->addItem(new CVar('groupid', $_REQUEST['groupid'], 'filter_groupid_id'));


	$hosts_wdgt->addPageHeader(_('CONFIGURATION OF HOSTS'), $frmForm);

	$frmGroup = new CForm();
	$frmGroup->setMethod('get');

	$frmGroup->addItem(array(_('Group').SPACE, $pageFilter->getGroupsCB()));

	$hosts_wdgt->addHeader(_('Hosts'), $frmGroup);
	$hosts_wdgt->addHeaderRowNumber();

// HOSTS FILTER {{{
	$filter_table = new CTable('', 'filter_config');
	$filter_table->addRow(array(
		array(array(bold(_('Name')), SPACE._('like').': '), new CTextBox('filter_host', $_REQUEST['filter_host'], 20)),
		array(array(bold(_('DNS')), SPACE._('like').': '), new CTextBox('filter_dns', $_REQUEST['filter_dns'], 20)),
		array(array(bold(_('IP')), SPACE._('like').': '), new CTextBox('filter_ip', $_REQUEST['filter_ip'], 20)),
		array(bold(_('Port').': '), new CTextBox('filter_port', $_REQUEST['filter_port'], 20))
	));

	$filter = new CButton('filter', _('Filter'), "javascript: create_var('zbx_filter', 'filter_set', '1', true);");
	$filter->useJQueryStyle('main');

	$reset = new CButton('reset', _('Reset'), "javascript: clearAllForm('zbx_filter');");
	$reset->useJQueryStyle();

	$div_buttons = new CDiv(array($filter, SPACE, $reset));
	$div_buttons->setAttribute('style', 'padding: 4px 0px;');

	$footer_col = new CCol($div_buttons, 'center');
	$footer_col->setColSpan(4);

	$filter_table->addRow($footer_col);

	$filter_form = new CForm('get');
	$filter_form->setAttribute('name', 'zbx_filter');
	$filter_form->setAttribute('id', 'zbx_filter');
	$filter_form->addItem($filter_table);
// }}} HOSTS FILTER
	$hosts_wdgt->addFlicker($filter_form, CProfile::get('web.hosts.filter.state', 0));


// table HOSTS
	$form = new CForm();
	$form->setName('hosts');

	$table = new CTableInfo(_('No hosts defined.'));
	$table->setHeader(array(
		new CCheckBox('all_hosts', null, "checkAll('" . $form->getName() . "','all_hosts','hosts');"),
		make_sorting_header(_('Name'), 'name'),
		_('Applications'),
		_('Items'),
		_('Triggers'),
		_('Graphs'),
		_('Discovery'),
		_('Interface'),
		_('Templates'),
		make_sorting_header(_('Status'), 'status'),
		_('Availability')
	));

// get Hosts
	$hosts = array();

	$sortfield = getPageSortField('name');
	$sortorder = getPageSortOrder();

	if($pageFilter->groupsSelected){
		$options = array(
			'editable' => 1,
			'sortfield' => $sortfield,
			'sortorder' => $sortorder,
			'limit' => ($config['search_limit']+1),
			'search' => array(
				'name' => (empty($_REQUEST['filter_host']) ? null : $_REQUEST['filter_host']),
				'ip' => (empty($_REQUEST['filter_ip']) ? null : $_REQUEST['filter_ip']),
				'dns' => (empty($_REQUEST['filter_dns']) ? null : $_REQUEST['filter_dns']),
			),
			'filter' => array(
				'port' => (empty($_REQUEST['filter_port']) ? null : $_REQUEST['filter_port']),
			)
		);

		if($pageFilter->groupid > 0) $options['groupids'] = $pageFilter->groupid;

		$hosts = API::Host()->get($options);
	}
	else{
		$hosts = array();
	}


// sorting && paging
	order_result($hosts, $sortfield, $sortorder);
	$paging = getPagingLine($hosts);
//---------

	$options = array(
		'hostids' => zbx_objectValues($hosts, 'hostid'),
		'output' => API_OUTPUT_EXTEND,
		'selectParentTemplates' => array('hostid','name'),
		'selectInterfaces' => API_OUTPUT_EXTEND,
		'selectItems' => API_OUTPUT_COUNT,
		'selectDiscoveries' => API_OUTPUT_COUNT,
		'selectTriggers' => API_OUTPUT_COUNT,
		'selectGraphs' => API_OUTPUT_COUNT,
		'selectApplications' => API_OUTPUT_COUNT
	);
	$hosts = API::Host()->get($options);
// sorting && paging
	order_result($hosts, $sortfield, $sortorder);
//---------

// Selecting linked templates to templates linked to hosts
	$templateids = array();
	foreach($hosts as $num => $host){
		$templateids = array_merge($templateids, zbx_objectValues($host['parentTemplates'], 'templateid'));
	}
	$templateids = array_unique($templateids);

	$options = array(
		'templateids' => $templateids,
		'selectParentTemplates' => array('hostid', 'name'),
	);
	$templates = API::Template()->get($options);
	$templates = zbx_toHash($templates, 'templateid');
//---------

	foreach($hosts as $num => $host){
		$interface = reset($host['interfaces']);

		$applications = array(new CLink(_('Applications'), 'applications.php?groupid='.$_REQUEST['groupid'].'&hostid='.$host['hostid']),
			' ('.$host['applications'].')');
		$items = array(new CLink(_('Items'), 'items.php?filter_set=1&hostid='.$host['hostid']),
			' ('.$host['items'].')');
		$triggers = array(new CLink(_('Triggers'), 'triggers.php?groupid='.$_REQUEST['groupid'].'&hostid='.$host['hostid']),
			' ('.$host['triggers'].')');
		$graphs = array(new CLink(_('Graphs'), 'graphs.php?groupid='.$_REQUEST['groupid'].'&hostid='.$host['hostid']),
			' ('.$host['graphs'].')');
		$discoveries = array(new CLink(_('Discovery'), 'host_discovery.php?&hostid='.$host['hostid']),
			' ('.$host['discoveries'].')');

		$description = array();
		if($host['proxy_hostid']){
			$proxy = API::Proxy()->get(array(
				'proxyids' => $host['proxy_hostid'],
				'output' => API_OUTPUT_EXTEND
			));
			$proxy = reset($proxy);
			$description[] = $proxy['host'] . ':';
		}

		$description[] = new CLink($host['name'], 'hosts.php?form=update&hostid='.$host['hostid'].url_param('groupid'));

		$hostIF = ($interface['useip'] == INTERFACE_USE_IP) ? $interface['ip'] : $interface['dns'];
		$hostIF .= empty($interface['port']) ? '' : ': '.$interface['port'];

		$status_script = null;
		switch($host['status']){
			case HOST_STATUS_MONITORED:
				if($host['maintenance_status'] == HOST_MAINTENANCE_STATUS_ON){
					$status_caption = _('In maintenance');
					$status_class = 'orange';
				}
				else{
					$status_caption = _('Monitored');
					$status_class = 'enabled';
				}

				$status_script = 'return Confirm('.zbx_jsvalue(_('Disable host?')).');';
				$status_url = 'hosts.php?hosts%5B%5D='.$host['hostid'].'&go=disable'.url_param('groupid');
				break;
			case HOST_STATUS_NOT_MONITORED:
				$status_caption = _('Not monitored');
				$status_url = 'hosts.php?hosts%5B%5D='.$host['hostid'].'&go=activate'.url_param('groupid');
				$status_script = 'return Confirm('.zbx_jsvalue(_('Enable host?')).');';
				$status_class = 'disabled';
				break;
			default:
				$status_caption = _('Unknown');
				$status_script = 'return Confirm('.zbx_jsvalue(_('Disable host?')).');';
				$status_url = 'hosts.php?hosts%5B%5D='.$host['hostid'].'&go=disable'.url_param('groupid');
				$status_class = 'unknown';
		}

		$status = new CLink($status_caption, $status_url, $status_class, $status_script);

		switch($host['available']){
			case HOST_AVAILABLE_TRUE:
				$zbx_available = new CDiv(SPACE, 'status_icon iconzbxavailable');
				break;
			case HOST_AVAILABLE_FALSE:
				$zbx_available = new CDiv(SPACE, 'status_icon iconzbxunavailable');
				$zbx_available->setHint($host['error'], '', 'on');
				break;
			case HOST_AVAILABLE_UNKNOWN:
				$zbx_available = new CDiv(SPACE, 'status_icon iconzbxunknown');
				break;
		}

		switch($host['snmp_available']){
			case HOST_AVAILABLE_TRUE:
				$snmp_available = new CDiv(SPACE, 'status_icon iconsnmpavailable');
				break;
			case HOST_AVAILABLE_FALSE:
				$snmp_available = new CDiv(SPACE, 'status_icon iconsnmpunavailable');
				$snmp_available->setHint($host['snmp_error'], '', 'on');
				break;
			case HOST_AVAILABLE_UNKNOWN:
				$snmp_available = new CDiv(SPACE, 'status_icon iconsnmpunknown');
				break;
		}

		switch($host['jmx_available']){
			case HOST_AVAILABLE_TRUE:
				$jmx_available = new CDiv(SPACE, 'status_icon iconjmxavailable');
				break;
			case HOST_AVAILABLE_FALSE:
				$jmx_available = new CDiv(SPACE, 'status_icon iconjmxunavailable');
				$jmx_available->setHint($host['jmx_error'], '', 'on');
				break;
			case HOST_AVAILABLE_UNKNOWN:
				$jmx_available = new CDiv(SPACE, 'status_icon iconjmxunknown');
				break;
		}

		switch($host['ipmi_available']){
			case HOST_AVAILABLE_TRUE:
				$ipmi_available = new CDiv(SPACE, 'status_icon iconipmiavailable');
				break;
			case HOST_AVAILABLE_FALSE:
				$ipmi_available = new CDiv(SPACE, 'status_icon iconipmiunavailable');
				$ipmi_available->setHint($host['ipmi_error'], '', 'on');
				break;
			case HOST_AVAILABLE_UNKNOWN:
				$ipmi_available = new CDiv(SPACE, 'status_icon iconipmiunknown');
				break;
		}

		$av_table = new CTable(null, 'invisible');
		$av_table->addRow(array($zbx_available, $snmp_available, $jmx_available, $ipmi_available));

		if(empty($host['parentTemplates'])){
			$hostTemplates = '-';
		}
		else{
			$hostTemplates = array();
			order_result($host['parentTemplates'], 'name');
			foreach($host['parentTemplates'] as $htnum => $template){
				$caption = array();
				$caption[] = new CLink($template['name'],'templates.php?form=update&templateid='.$template['templateid'],'unknown');

				if(!empty($templates[$template['templateid']]['parentTemplates'])){
					order_result($templates[$template['templateid']]['parentTemplates'], 'name');

					$caption[] = ' (';
					foreach($templates[$template['templateid']]['parentTemplates'] as $tnum => $tpl){
						$caption[] = new CLink($tpl['name'],'templates.php?form=update&templateid='.$tpl['templateid'], 'unknown');
						$caption[] = ', ';
					}
					array_pop($caption);

					$caption[] = ')';
				}

				$hostTemplates[] = $caption;
				$hostTemplates[] = ', ';
			}

			if(!empty($hostTemplates)) array_pop($hostTemplates);
		}

		$table->addRow(array(
			new CCheckBox('hosts['.$host['hostid'].']',null,null,$host['hostid']),
			$description,
			$applications,
			$items,
			$triggers,
			$graphs,
			$discoveries,
			$hostIF,
			new CCol($hostTemplates, 'wraptext'),
			$status,
			$av_table
		));
	}

//----- GO ------
	$goBox = new CComboBox('go');
	$goBox->addItem('export', _('Export selected'));
	$goBox->addItem('massupdate', _('Mass update'));

	$goOption = new CComboItem('activate', _('Enable selected'));
	$goOption->setAttribute('confirm', _('Enable selected hosts?'));
	$goBox->addItem($goOption);

	$goOption = new CComboItem('disable', _('Disable selected'));
	$goOption->setAttribute('confirm', _('Disable selected hosts?'));
	$goBox->addItem($goOption);

	$goOption = new CComboItem('delete', _('Delete selected'));
	$goOption->setAttribute('confirm', _('Delete selected hosts?'));
	$goBox->addItem($goOption);

// goButton name is necessary!!!
	$goButton = new CSubmit('goButton', _('Go'));
	$goButton->setAttribute('id', 'goButton');

	zbx_add_post_js('chkbxRange.pageGoName = "hosts";');

	$footer = get_table_header(array($goBox, $goButton));
//----

// PAGING FOOTER
	$table = array($paging, $table, $paging, $footer);
//---------
	$form->addItem($table);
	$hosts_wdgt->addItem($form);
}

$hosts_wdgt->show();


require_once dirname(__FILE__).'/include/page_footer.php';
