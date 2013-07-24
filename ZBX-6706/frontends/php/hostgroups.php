<?php
/*
** Zabbix
** Copyright (C) 2001-2013 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


require_once dirname(__FILE__).'/include/config.inc.php';
require_once dirname(__FILE__).'/include/hosts.inc.php';

$page['title'] = _('Configuration of host groups');
$page['file'] = 'hostgroups.php';
$page['hist_arg'] = array();

require_once dirname(__FILE__).'/include/page_header.php';

// VAR	TYPE	OPTIONAL	FLAGS	VALIDATION	EXCEPTION
$fields = array(
	'hosts' =>			array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		null),
	'groups' =>			array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		null),
	'hostids' =>		array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		null),
	'groupids' =>		array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		null),
	// group
	'groupid' =>		array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		'isset({form})&&{form}=="update"'),
	'name' =>			array(T_ZBX_STR, O_OPT, null,	NOT_EMPTY,	'isset({save})'),
	'twb_groupid' =>	array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		null),
	// actions
	'go' =>				array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,	null),
	'save' =>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,	null),
	'clone' =>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,	null),
	'delete' =>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,	null),
	'cancel' =>			array(T_ZBX_STR, O_OPT, P_SYS,	null,		null),
	// other
	'form' =>			array(T_ZBX_STR, O_OPT, P_SYS,	null,		null),
	'form_refresh' =>	array(T_ZBX_STR, O_OPT, null,	null,		null)
);
check_fields($fields);
validate_sort_and_sortorder('name', ZBX_SORT_UP);

$_REQUEST['go'] = get_request('go', 'none');

// validate permissions
if (get_request('groupid', 0) > 0) {
	$groupIds = available_groups($_REQUEST['groupid'], 1);
	if (empty($groupIds)) {
		access_deny();
	}
}

/*
 * Actions
 */
if (isset($_REQUEST['clone']) && isset($_REQUEST['groupid'])) {
	unset($_REQUEST['groupid']);
	$_REQUEST['form'] = 'clone';
}
elseif (isset($_REQUEST['save'])) {
	$hostIds = get_request('hosts', array());

	$hosts = API::Host()->get(array(
		'hostids' => $hostIds,
		'output' => array('hostid')
	));

	$templates = API::Template()->get(array(
		'templateids' => $hostIds,
		'output' => array('templateid')
	));

	if (!empty($_REQUEST['groupid'])) {
		DBstart();

		$oldGroup = API::HostGroup()->get(array(
			'groupids' => $_REQUEST['groupid'],
			'output' => API_OUTPUT_EXTEND
		));
		$oldGroup = reset($oldGroup);

		$result = API::HostGroup()->update(array(
			'groupid' => $_REQUEST['groupid'],
			'name' => $_REQUEST['name']
		));

		if ($result) {
			$groups = API::HostGroup()->get(array(
				'groupids' => $result['groupids'],
				'output' => API_OUTPUT_EXTEND
			));

			$result = API::HostGroup()->massUpdate(array(
				'hosts' => $hosts,
				'templates' => $templates,
				'groups' => $groups
			));
		}

		$result = DBend($result);

		if ($result) {
			$group = reset($groups);

			add_audit_ext(AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_HOST_GROUP, $group['groupid'], $group['name'],
				'groups', array('name' => $oldGroup['name']), array('name' => $group['name']));
		}

		$msgOk = _('Group updated');
		$msgFail = _('Cannot update group');
	}
	else {
		DBstart();

		$result = API::HostGroup()->create(array('name' => $_REQUEST['name']));

		if ($result) {
			$groups = API::HostGroup()->get(array(
				'groupids' => $result['groupids'],
				'output' => API_OUTPUT_EXTEND
			));

			$result = API::HostGroup()->massAdd(array(
				'hosts' => $hosts,
				'templates' => $templates,
				'groups' => $groups
			));

			if ($result) {
				$group = reset($groups);

				add_audit_ext(AUDIT_ACTION_ADD, AUDIT_RESOURCE_HOST_GROUP, $group['groupid'], $group['name'], null, null, null);
			}
		}

		$result = DBend($result);

		$msgOk = _('Group added');
		$msgFail = _('Cannot add group');
	}

	show_messages($result, $msgOk, $msgFail);

	if ($result) {
		unset($_REQUEST['form']);
	}
	unset($_REQUEST['save']);
}
elseif (isset($_REQUEST['delete']) && isset($_REQUEST['groupid'])) {
	$result = API::HostGroup()->delete($_REQUEST['groupid']);

	show_messages($result, _('Group deleted'), _('Cannot delete group'));

	if ($result) {
		unset($_REQUEST['form']);
	}
	unset($_REQUEST['delete']);
}
elseif ($_REQUEST['go'] == 'delete') {
	$go_result = API::HostGroup()->delete(get_request('groups', array()));

	show_messages($go_result, _('Group deleted'), _('Cannot delete group'));
}
elseif (str_in_array($_REQUEST['go'], array('activate', 'disable'))) {
	$status = ($_REQUEST['go'] == 'activate') ? HOST_STATUS_MONITORED : HOST_STATUS_NOT_MONITORED;

	$groups = get_request('groups', array());

	if ($groups) {
		DBstart();

		$hosts = API::Host()->get(array(
			'groupids' => $groups,
			'editable' => true,
			'output' => API_OUTPUT_EXTEND
		));

		if ($hosts) {
			$go_result = API::Host()->massUpdate(array(
				'hosts' => $hosts,
				'status' => $status
			));

			if ($go_result) {
				foreach ($hosts as $host) {
					add_audit_ext(
						AUDIT_ACTION_UPDATE,
						AUDIT_RESOURCE_HOST,
						$host['hostid'],
						$host['host'],
						'hosts',
						array('status' => $host['status']),
						array('status' => $status)
					);
				}
			}
		}
		else {
			$go_result = true;
		}

		$go_result = DBend($go_result);

		show_messages($go_result, _('Host status updated'), _('Cannot update host'));
	}
}
if ($_REQUEST['go'] != 'none' && isset($go_result) && $go_result) {
	$url = new CUrl();
	$path = $url->getPath();
	insert_js('cookie.eraseArray("'.$path.'")');
}

/*
 * Display
 */
if (isset($_REQUEST['form'])) {
	$data = array(
		'form' => get_request('form'),
		'groupid' => get_request('groupid', 0),
		'hosts' => get_request('hosts', array()),
		'name' => get_request('name', ''),
		'twb_groupid' => get_request('twb_groupid', -1)
	);

	if ($data['groupid'] > 0) {
		$data['group'] = get_hostgroup_by_groupid($data['groupid']);

		// if first time select all hosts for group from db
		if (!isset($_REQUEST['form_refresh'])) {
			$data['name'] = $data['group']['name'];

			$data['hosts'] = API::Host()->get(array(
				'groupids' => $data['groupid'],
				'templated_hosts' => true,
				'output' => array('hostid')
			));

			$data['hosts'] = zbx_toHash(zbx_objectValues($data['hosts'], 'hostid'), 'hostid');
		}
	}

	// get all possible groups
	$data['db_groups'] = API::HostGroup()->get(array(
		'not_proxy_host' => true,
		'sortfield' => 'name',
		'editable' => true,
		'output' => API_OUTPUT_EXTEND
	));

	if ($data['twb_groupid'] == -1) {
		$gr = reset($data['db_groups']);

		$data['twb_groupid'] = $gr['groupid'];
	}

	// get all possible hosts
	$data['db_hosts'] = API::Host()->get(array(
		'groupids' => $data['twb_groupid'] ? $data['twb_groupid'] : null,
		'templated_hosts' => true,
		'sortfield' => 'name',
		'editable' => true,
		'output' => API_OUTPUT_EXTEND
	));

	// get selected hosts
	$data['r_hosts'] = API::Host()->get(array(
		'hostids' => $data['hosts'],
		'templated_hosts' => true,
		'sortfield' => 'name',
		'output' => API_OUTPUT_EXTEND
	));
	$data['r_hosts'] = zbx_toHash($data['r_hosts'], 'hostid');

	// deletable groups
	if (!empty($data['groupid'])) {
		$data['deletableHostGroups'] = getDeletableHostGroups($data['groupid']);
	}

	// nodes
	if (is_array(get_current_nodeid())) {
		foreach ($data['db_groups'] as $key => $group) {
			$data['db_groups'][$key]['name'] =
				get_node_name_by_elid($group['groupid'], true, NAME_DELIMITER).$group['name'];
		}

		foreach ($data['r_hosts'] as $key => $host) {
			$data['r_hosts'][$key]['name'] = get_node_name_by_elid($host['hostid'], true, NAME_DELIMITER).$host['name'];
		}

		if (!$data['twb_groupid']) {
			foreach ($data['db_hosts'] as $key => $host) {
				$data['db_hosts'][$key]['name'] =
					get_node_name_by_elid($host['hostid'], true, NAME_DELIMITER).$host['name'];
			}
		}
	}

	// render view
	$hostgroupView = new CView('configuration.hostgroups.edit', $data);
	$hostgroupView->render();
	$hostgroupView->show();
}
else {
	$data = array(
		'config' => $config,
		'displayNodes' => is_array(get_current_nodeid())
	);

	$sortfield = getPageSortField('name');

	$groups = API::HostGroup()->get(array(
		'editable' => true,
		'output' => array('groupid'),
		'sortfield' => $sortfield,
		'limit' => $config['search_limit'] + 1
	));

	$data['paging'] = getPagingLine($groups);

	// get hosts and templates count
	$data['groupCounts'] = API::HostGroup()->get(array(
		'groupids' => zbx_objectValues($groups, 'groupid'),
		'selectHosts' => API_OUTPUT_COUNT,
		'selectTemplates' => API_OUTPUT_COUNT,
		'nopermissions' => true
	));
	$data['groupCounts'] = zbx_toHash($data['groupCounts'], 'groupid');

	// get host groups
	$data['groups'] = API::HostGroup()->get(array(
		'groupids' => zbx_objectValues($groups, 'groupid'),
		'selectHosts' => array('hostid', 'name', 'status'),
		'selectTemplates' => array('hostid', 'name', 'status'),
		'output' => API_OUTPUT_EXTEND,
		'nopermissions' => 1,
		'limitSelects' => $config['max_in_table'] + 1
	));
	order_result($data['groups'], $sortfield, getPageSortOrder());

	// nodes
	if ($data['displayNodes']) {
		foreach ($data['groups'] as $key => $group) {
			$data['groups'][$key]['nodename'] = get_node_name_by_elid($group['groupid'], true);
		}
	}

	// render view
	$hostgroupView = new CView('configuration.hostgroups.list', $data);
	$hostgroupView->render();
	$hostgroupView->show();
}

require_once dirname(__FILE__).'/include/page_footer.php';
