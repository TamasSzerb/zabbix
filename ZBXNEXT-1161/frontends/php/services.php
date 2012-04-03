<?php
/*
** Zabbix
** Copyright (C) 2001-2011 Zabbix SIA
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
require_once dirname(__FILE__).'/include/config.inc.php';
require_once dirname(__FILE__).'/include/services.inc.php';
require_once dirname(__FILE__).'/include/triggers.inc.php';
require_once dirname(__FILE__).'/include/html.inc.php';

$page['title'] = _('Configuration of IT services');
$page['file'] = 'services.php';
$page['scripts'] = array('class.calendar.js');
$page['hist_arg'] = array();

if (isset($_REQUEST['pservices']) || isset($_REQUEST['cservices'])) {
	define('ZBX_PAGE_NO_MENU', 1);
}

include_once('include/page_header.php');
?>
<?php
//	VAR		TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION
$fields = array(
	'serviceid' =>			array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		null),
	'group_serviceid' =>	array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		null),
	'name' =>				array(T_ZBX_STR, O_OPT, null,	NOT_EMPTY,	'isset({save_service})'),
	'algorithm' =>			array(T_ZBX_INT, O_OPT, null,	IN('0,1,2'),'isset({save_service})'),
	'showsla' =>			array(T_ZBX_INT, O_OPT, null,	IN('0,1'),	null),
	'goodsla' =>			array(T_ZBX_DBL, O_OPT, null,	BETWEEN(0, 100), null),
	'sortorder' =>			array(T_ZBX_INT, O_OPT, null,	BETWEEN(0, 999), null),
	'times' =>		array(T_ZBX_STR, O_OPT, null,	null,		null),
	'triggerid' =>			array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		null),
	'trigger' =>			array(T_ZBX_STR, O_OPT, null,	null,		null),
	'new_service_time' =>	array(T_ZBX_STR, O_OPT, null,	null,		null),
	'children' =>				array(T_ZBX_STR, O_OPT, P_SYS,	DB_ID,		null),
	'parentid' =>			array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		null),
	'parentname' =>			array(T_ZBX_STR, O_OPT, null,	null,		null),
	// actions
	'save_service' =>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,	null),
	'add_service_time' =>	array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,	null),
	'delete' =>				array(T_ZBX_STR, O_OPT, P_SYS,	null,		null),
	// ajax
	'favobj' =>				array(T_ZBX_STR, O_OPT, P_ACT,	IN("'hat'"), null),
	'favref' =>				array(T_ZBX_STR, O_OPT, P_ACT,	NOT_EMPTY,	'isset({favobj})'),
	'favstate' =>			array(T_ZBX_INT, O_OPT, P_ACT,	NOT_EMPTY,	'isset({favobj})'),
	// others
	'form' =>				array(T_ZBX_STR, O_OPT, P_SYS,	null,		null),
	'form_refresh' =>		array(T_ZBX_INT, O_OPT, null,	null,		null),
	'pservices' =>			array(T_ZBX_INT, O_OPT, null,	null,		null),
	'cservices' =>			array(T_ZBX_INT, O_OPT, null,	null,		null)
);
check_fields($fields);

/*
 * AJAX
 */
if (isset($_REQUEST['favobj'])) {
	if ($_REQUEST['favobj'] == 'hat') {
		CProfile::update('web.services.hats.'.$_REQUEST['favref'].'.state', $_REQUEST['favstate'], PROFILE_TYPE_INT);
	}
}
if (PAGE_TYPE_JS == $page['type'] || PAGE_TYPE_HTML_BLOCK == $page['type']) {
	include_once('include/page_footer.php');
	exit();
}

// get triggers and check permissions
$available_triggers = get_accessible_triggers(PERM_READ_ONLY, array());
if (!empty($_REQUEST['serviceid'])) {
	$service = API::Service()->get(array(
		'output' => API_OUTPUT_EXTEND,
		'selectParent' => array('serviceid', 'name'),
		'selectDependencies' => API_OUTPUT_EXTEND,
		'selectTimes' => API_OUTPUT_EXTEND,
		'serviceids' => $_REQUEST['serviceid'],
		'limit' => 1
	));
	$service = reset($service);
	if (!$service) {
		access_deny();
	}
}

/*
 * Actions
 */
if (isset($_REQUEST['form'])) {
	$_REQUEST['showsla'] = get_request('showsla', 0);
	$result = false;

	// delete
	if (isset($_REQUEST['delete']) && isset($_REQUEST['serviceid'])) {
		$result = delete_service($service['serviceid']);
		show_messages($result, _('Service deleted'), _('Cannot delete service'));
		add_audit_if($result, AUDIT_ACTION_DELETE, AUDIT_RESOURCE_IT_SERVICE, 'Name ['.$service['name'].'] id ['.$service['serviceid'].']');
		unset($service);
		if ($result) {
			unset($_REQUEST['form']);
		}
	}
	// save
	elseif (isset($_REQUEST['save_service'])) {
		DBstart();
		if (isset($service['serviceid'])) {
			$result = update_service(
				$service['serviceid'],
				get_request('name', null),
				get_request('triggerid', null),
				get_request('algorithm', null),
				get_request('showsla', 0),
				get_request('goodsla', null),
				get_request('sortorder', null),
				get_request('times', array()),
				get_request('parentid', null),
				get_request('children', array())
			);
		}
		else {
			$result = add_service(
				get_request('name', null),
				get_request('triggerid', null),
				get_request('algorithm', SERVICE_ALGORITHM_MAX),
				get_request('showsla', 0),
				get_request('goodsla', SERVICE_SLA),
				get_request('sortorder', 0),
				get_request('times', array()),
				get_request('parentid', null),
				get_request('children', array())
			);
		}
		$result = DBend() ? $result : false;

		if (isset($service['serviceid'])) {
			show_messages($result, _('Service updated'), _('Cannot update service'));
			$serviceid = $service['serviceid'];
			$audit_acrion = AUDIT_ACTION_UPDATE;
		}
		else {
			show_messages($result, _('Service added'), _('Cannot add service'));
			$serviceid = $result;
			$audit_acrion = AUDIT_ACTION_ADD;
		}

		add_audit_if($result, $audit_acrion, AUDIT_RESOURCE_IT_SERVICE, ' Name ['.$_REQUEST['name'].'] id ['.$serviceid.']');
		if ($result) {
			unset($_REQUEST['form']);
		}
	}
	// validate and get service times
	elseif (isset($_REQUEST['add_service_time']) && isset($_REQUEST['new_service_time'])) {
		$_REQUEST['times'] = get_request('times', array());
		$new_service_time['type'] = $_REQUEST['new_service_time']['type'];

		if ($_REQUEST['new_service_time']['type'] == SERVICE_TIME_TYPE_ONETIME_DOWNTIME) {
			$new_service_time['from'] = zbxDateToTime($_REQUEST['new_service_time']['from']);
			$new_service_time['to'] = zbxDateToTime($_REQUEST['new_service_time']['to']);
			$new_service_time['note'] = $_REQUEST['new_service_time']['note'];
		}
		else {
			$new_service_time['from'] = dowHrMinToSec($_REQUEST['new_service_time']['from_week'], $_REQUEST['new_service_time']['from_hour'], $_REQUEST['new_service_time']['from_minute']);
			$new_service_time['to'] = dowHrMinToSec($_REQUEST['new_service_time']['to_week'], $_REQUEST['new_service_time']['to_hour'], $_REQUEST['new_service_time']['to_minute']);
			$new_service_time['note'] = $_REQUEST['new_service_time']['note'];
		}

		try {
			checkServiceTime(array(
				'type' => $new_service_time['type'],
				'ts_from' => ($new_service_time['from']) ? $new_service_time['from'] : null,
				'ts_to' => ($new_service_time['to']) ? $new_service_time['to'] : null,
			));

			// if this time is not already there, adding it for inserting
			if (!str_in_array($_REQUEST['times'], $new_service_time)) {
				array_push($_REQUEST['times'], $new_service_time);

				unset($_REQUEST['new_service_time']['from_week']);
				unset($_REQUEST['new_service_time']['to_week']);
				unset($_REQUEST['new_service_time']['from_hour']);
				unset($_REQUEST['new_service_time']['to_hour']);
				unset($_REQUEST['new_service_time']['from_minute']);
				unset($_REQUEST['new_service_time']['to_minute']);
			}
		}
		catch (APIException $e) {
			error($e->getMessage());
		}

		show_messages();
	}
	else {
		unset($_REQUEST['new_service_time']['from_week']);
		unset($_REQUEST['new_service_time']['to_week']);
		unset($_REQUEST['new_service_time']['from_hour']);
		unset($_REQUEST['new_service_time']['to_hour']);
		unset($_REQUEST['new_service_time']['from_minute']);
		unset($_REQUEST['new_service_time']['to_minute']);
	}
}

/*
 * Display parent services list
 */
if (isset($_REQUEST['pservices'])) {
	$data = array();
	if (!empty($service)) {
		$data['service'] = $service;
	}
	if (!empty($data['service'])) {
		$childs_str = implode(',', get_service_childs($data['service']['serviceid'], 1));
		if (!empty($childs_str)) {
			$childs_str .= ',';
		}
		$sql = 'SELECT DISTINCT s.*'.
				' FROM services s'.
				' WHERE '.DBin_node('s.serviceid').
					' AND (s.triggerid IS NULL OR '.DBcondition('s.triggerid', $available_triggers).') '.
					' AND s.serviceid NOT IN ('.$childs_str.$data['service']['serviceid'].') '.
				' ORDER BY s.sortorder,s.name';
	}
	else {
		$sql = 'SELECT DISTINCT s.*'.
				' FROM services s'.
				' WHERE '.DBin_node('s.serviceid').
					' AND (s.triggerid IS NULL OR '.DBcondition('s.triggerid', $available_triggers).')'.
				' ORDER BY s.sortorder,s.name';
	}
	$data['db_pservices'] = DBfetchArray(DBselect($sql));
	foreach ($data['db_pservices'] as $key => $db_service) {
		$data['db_pservices'][$key]['trigger'] = !empty($db_service['triggerid']) ? expand_trigger_description($db_service['triggerid']) : '-';
	}

	// render view
	$servicesView = new CView('configuration.services.parent.list', $data);
	$servicesView->render();
	$servicesView->show();
	include_once('include/page_footer.php');
}

/*
 * Display child services list
 */
if (isset($_REQUEST['cservices'])) {
	$data = array();
	if (!empty($service)) {
		$data['service'] = $service;
	}
	if (!empty($data['service'])) {
		$childs_str = implode(',', get_service_childs($data['service']['serviceid'], 1));
		if (!empty($childs_str)) {
			$childs_str .= ',';
		}
		$sql = 'SELECT DISTINCT s.*'.
				' FROM services s'.
				' WHERE '.DBin_node('s.serviceid').
					' AND (s.triggerid IS NULL OR '.DBcondition('s.triggerid', $available_triggers).')'.
					' AND s.serviceid NOT IN ('.$childs_str.$data['service']['serviceid'].')'.
				' ORDER BY s.sortorder,s.name';

	}
	else {
		$sql = 'SELECT DISTINCT s.*'.
				' FROM services s'.
				' WHERE '.DBin_node('s.serviceid').
					' AND (s.triggerid IS NULL OR '.DBcondition('s.triggerid', $available_triggers).')'.
				' ORDER BY s.sortorder,s.name';
	}
	$data['db_cservices'] = DBfetchArray(DBselect($sql));
	foreach ($data['db_cservices'] as $key => $db_service) {
		$data['db_cservices'][$key]['trigger'] = !empty($db_service['triggerid']) ? expand_trigger_description($db_service['triggerid']) : '-';
	}

	// render view
	$servicesView = new CView('configuration.services.child.list', $data);
	$servicesView->render();
	$servicesView->show();
	include_once('include/page_footer.php');
}

/*
 * Display
 */
if (isset($_REQUEST['form'])) {
	$data = array();
	$data['form'] = get_request('form');
	$data['form_refresh'] = get_request('form_refresh', 0);
	$data['service'] = !empty($service) ? $service : null;

	$data['times'] = get_request('times', array());
	$data['new_service_time'] = get_request('new_service_time', array('type' => SERVICE_TIME_TYPE_UPTIME));

	// populate the form from the object from the database
	if (isset($data['service']['serviceid']) && !isset($_REQUEST['form_refresh'])) {
		$data['name'] = $data['service']['name'];
		$data['algorithm'] = $data['service']['algorithm'];
		$data['showsla'] = $data['service']['showsla'];
		$data['goodsla'] = $data['service']['goodsla'];
		$data['sortorder'] = $data['service']['sortorder'];
		$data['triggerid'] = isset($data['service']['triggerid']) ? $data['service']['triggerid'] : 0;

		// get services times
		foreach ($service['times'] as $serviceTime) {
			$data['times'][] = array(
				'type' => $serviceTime['type'],
				'from' => $serviceTime['ts_from'],
				'to' => $serviceTime['ts_to'],
				'note' => $serviceTime['note']
			);
		}

		// parent
		if ($parent = $service['parent']) {
			$data['parentid'] = $parent['serviceid'];
			$data['parentname'] = $parent['name'];
		}
		else {
			$data['parentid'] = 0;
			$data['parentname'] = 'root';
		}

		// get children
		$childServices = API::Service()->get(array(
			'serviceids' => zbx_objectValues($service['dependencies'], 'servicedownid'),
			'output' => array('name', 'triggerid'),
			'preservekeys' => true,
		));
		$data['children'] = array();
		foreach ($service['dependencies'] as $dependency) {
			$childService = $childServices[$dependency['servicedownid']];
			$data['children'][] = array(
				'name' => $childService['name'],
				'triggerid' => $childService['triggerid'],
				'trigger' => !empty($childService['triggerid']) ? expand_trigger_description($childService['triggerid']) : '-',
				'serviceid' => $dependency['servicedownid'],
				'soft' => $dependency['soft'],
			);
		}
	}
	// populate the form from a submitted request
	else {
		$data['name'] = get_request('name', '');
		$data['algorithm'] = get_request('algorithm', SERVICE_ALGORITHM_MAX);
		$data['showsla'] = get_request('showsla', 0);
		$data['goodsla'] = get_request('goodsla', SERVICE_SLA);
		$data['sortorder'] = get_request('sortorder', 0);
		$data['triggerid'] = get_request('triggerid', 0);
		$data['parentid'] = get_request('parentid', 0);
		$data['parentname'] = get_request('parentname', '');
		$data['children'] = get_request('children', array());
	}

	// get trigger
	if ($data['triggerid'] > 0) {
		$trigger = API::Trigger()->get(array(
			'triggerids' => $data['triggerid'],
			'output' => array('description'),
			'selectHosts' => array('name'),
			'expandDescription' => true
		));
		$trigger = reset($trigger);
		$host = reset($trigger['hosts']);
		$data['trigger'] = $host['name'].':'.$trigger['description'];
	}
	else {
		$data['trigger'] = '';
	}

	// render view
	$servicesView = new CView('configuration.services.edit', $data);
	$servicesView->render();
	$servicesView->show();
}
else {
	$services = array();
	$row = array(
		'id' => 0,
		'serviceid' => 0,
		'serviceupid' => 0,
		'caption' => _('root'),
		'status' => SPACE,
		'algorithm' => SPACE,
		'description' => SPACE,
		'soft' => 0,
		'linkid' => '',
		'add' => SPACE,
		'remove' => SPACE
	);
	$services[] = $row;

	$db_services = DBSelect(
		'SELECT DISTINCT s.serviceid,sl.servicedownid,sl_p.serviceupid AS serviceupid,s.triggerid,'.
			's.name AS caption,s.algorithm,t.description,t.expression,s.sortorder,sl.linkid,s.showsla,s.goodsla,s.status'.
		' FROM services s'.
			' LEFT JOIN triggers t ON s.triggerid=t.triggerid'.
			' LEFT JOIN services_links sl ON s.serviceid=sl.serviceupid AND sl.soft=1'.
			' LEFT JOIN services_links sl_p ON s.serviceid=sl_p.servicedownid AND sl_p.soft=0'.
		' WHERE '.DBin_node('s.serviceid').
			' AND (t.triggerid IS NULL OR '.DBcondition('t.triggerid', get_accessible_triggers(PERM_READ_ONLY, array())).')'.
		' ORDER BY s.sortorder,sl_p.serviceupid,s.serviceid'
	);
	while ($row = DBFetch($db_services)) {
		$row['id'] = $row['serviceid'];
		$row['description'] = empty($row['triggerid']) ? _('None') : expand_trigger_description($row['triggerid']);
		empty($row['serviceupid']) ? $row['serviceupid'] = '0' : '';

		if (isset($services[$row['serviceid']])) {
			$services[$row['serviceid']] = zbx_array_merge($services[$row['serviceid']], $row);
		}
		else {
			$services[$row['serviceid']] = $row;
		}

		if (isset($row['serviceupid'])) {
			$services[$row['serviceupid']]['childs'][] = array('id' => $row['serviceid'], 'soft' => 0, 'linkid' => 0);
		}
		if (isset($row['servicedownid'])) {
			$services[$row['serviceid']]['childs'][] = array('id' => $row['servicedownid'], 'soft' => 1, 'linkid' => $row['linkid']);
		}
	}

	// create tree
	$treeServ = array();
	createServiceTree($services, $treeServ);
	$treeServ = del_empty_nodes($treeServ);
	$tree = new CTree('service_conf_tree', $treeServ, array(
		'caption' => _('Service'),
		'algorithm' => _('Status calculation'),
		'description' => _('Trigger')
	));
	if (empty($tree)) {
		error(_('Can\'t format Tree'));
	}

	$trigerMenu[] = array('test1', null, null, array('outer' => array('pum_oheader'), 'inner' => array('pum_iheader')));
	$trigerMenu[] = array('test2', null, null, array('outer' => array('pum_oheader'), 'inner' => array('pum_iheader')));

	$jsmenu = new CPUMenu($trigerMenu, 170);

	$data = array('tree' => $tree);

	// render view
	$servicesView = new CView('configuration.services.list', $data);
	$servicesView->render();
	$servicesView->show();
}

include_once('include/page_footer.php');
?>
