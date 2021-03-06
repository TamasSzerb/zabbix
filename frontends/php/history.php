<?php
/*
** Zabbix
** Copyright (C) 2001-2015 Zabbix SIA
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
require_once dirname(__FILE__).'/include/items.inc.php';
require_once dirname(__FILE__).'/include/graphs.inc.php';

$page['file'] = 'history.php';
$page['title'] = _('History');
$page['hist_arg'] = array('itemids', 'period', 'stime', 'action', 'graphtype');
$page['scripts'] = array('class.calendar.js', 'gtlc.js', 'flickerfreescreen.js');
$page['type'] = detect_page_type(PAGE_TYPE_HTML);

if (isset($_REQUEST['plaintext'])) {
	define('ZBX_PAGE_NO_MENU', 1);
}
define('ZBX_PAGE_DO_JS_REFRESH', 1);

require_once dirname(__FILE__).'/include/page_header.php';

// VAR	TYPE	OPTIONAL	FLAGS	VALIDATION	EXCEPTION
$fields = array(
	'itemids' =>		array(T_ZBX_INT, O_MAND, P_SYS,	DB_ID,	'isset({favobj})'),
	'period' =>			array(T_ZBX_INT, O_OPT, null,	null,	null),
	'stime' =>			array(T_ZBX_STR, O_OPT, null,	null,	null),
	'filter_task' =>	array(T_ZBX_STR, O_OPT, null,	IN(FILTER_TASK_SHOW.','.FILTER_TASK_HIDE.','.FILTER_TASK_MARK.','.FILTER_TASK_INVERT_MARK), null),
	'filter' =>			array(T_ZBX_STR, O_OPT, null,	null,	null),
	'mark_color' =>		array(T_ZBX_STR, O_OPT, null,	IN(MARK_COLOR_RED.','.MARK_COLOR_GREEN.','.MARK_COLOR_BLUE), null),
	'cmbitemlist' =>	array(T_ZBX_INT, O_OPT, null,	DB_ID,	null),
	'plaintext' =>		array(T_ZBX_STR, O_OPT, null,	null,	null),
	'action' =>			array(T_ZBX_STR, O_OPT, P_SYS,	IN('"'.HISTORY_GRAPH.'","'.HISTORY_VALUES.'","'.HISTORY_LATEST.'","'.HISTORY_BATCH_GRAPH.'"'), null),
	'graphtype' =>      array(T_ZBX_INT, O_OPT, null,   IN(array(GRAPH_TYPE_NORMAL, GRAPH_TYPE_STACKED)), null),
	// ajax
	'filterState' =>	array(T_ZBX_INT, O_OPT, P_ACT,	null,	null),
	'favobj' =>			array(T_ZBX_STR, O_OPT, P_ACT,	null,	null),
	'favid' =>			array(T_ZBX_INT, O_OPT, P_ACT,	null,	null),
	// actions
	'reset' =>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null, null),
	'cancel' =>			array(T_ZBX_STR, O_OPT, P_SYS,	null,	null),
	'form' =>			array(T_ZBX_STR, O_OPT, P_SYS,	null,	null),
	'form_copy_to' =>	array(T_ZBX_STR, O_OPT, P_SYS,	null,	null),
	'form_refresh' =>	array(T_ZBX_INT, O_OPT, null,	null,	null),
	'fullscreen' =>		array(T_ZBX_INT, O_OPT, P_SYS,	IN('0,1'),	null)
);
check_fields($fields);

/*
 * Ajax
 */
if (hasRequest('filterState')) {
	CProfile::update('web.history.filter.state', getRequest('filterState'), PROFILE_TYPE_INT);
}

if (isset($_REQUEST['favobj'])) {
	if ($_REQUEST['favobj'] == 'timeline') {
		navigation_bar_calc('web.item.graph', $_REQUEST['favid'], true);
	}

	// saving fixed/dynamic setting to profile
	if ($_REQUEST['favobj'] == 'timelinefixedperiod' && isset($_REQUEST['favid'])) {
		CProfile::update('web.history.timelinefixed', $_REQUEST['favid'], PROFILE_TYPE_INT);
	}
}

if ($page['type'] == PAGE_TYPE_JS || $page['type'] == PAGE_TYPE_HTML_BLOCK) {
	require_once dirname(__FILE__).'/include/page_footer.php';
	exit;
}

/*
 * Actions
 */
$_REQUEST['action'] = getRequest('action', HISTORY_GRAPH);

/*
 * Display
 */
$items = API::Item()->get(array(
	'itemids' => getRequest('itemids'),
	'webitems' => true,
	'selectHosts' => array('name'),
	'output' => array('itemid', 'key_', 'name', 'value_type', 'hostid', 'valuemapid'),
	'preservekeys' => true
));

foreach (getRequest('itemids') as $itemid) {
	if (!isset($items[$itemid])) {
		access_deny();
	}
}

$items = CMacrosResolverHelper::resolveItemNames($items);

$item = reset($items);

$data = array(
	'itemids' => getRequest('itemids'),
	'items' => $items,
	'value_type' => $item['value_type'],
	'action' => getRequest('action'),
	'period' => getRequest('period'),
	'stime' => getRequest('stime'),
	'plaintext' => isset($_REQUEST['plaintext']),
	'iv_string' => array(ITEM_VALUE_TYPE_LOG => 1, ITEM_VALUE_TYPE_TEXT => 1),
	'iv_numeric' => array(ITEM_VALUE_TYPE_FLOAT => 1, ITEM_VALUE_TYPE_UINT64 => 1),
	'fullscreen' => $_REQUEST['fullscreen'],
	'graphtype' => getRequest('graphtype', GRAPH_TYPE_NORMAL)
);

// render view
$historyView = new CView('monitoring.history', $data);
$historyView->render();
$historyView->show();

require_once dirname(__FILE__).'/include/page_footer.php';
