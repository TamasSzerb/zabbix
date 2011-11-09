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
require_once('include/hosts.inc.php');
require_once('include/triggers.inc.php');
require_once('include/forms.inc.php');

$page['title'] = 'S_CONFIGURATION_OF_TRIGGERS';
$page['file'] = 'trigger_prototypes.php';
$page['hist_arg'] = array('parent_discoveryid');

require_once('include/page_header.php');

?>
<?php
//		VAR			TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION
	$fields=array(
		'parent_discoveryid'=>	array(T_ZBX_INT, O_MAND,	 P_SYS,	DB_ID,	NULL),

		'triggerid'=>		array(T_ZBX_INT, O_OPT,  P_SYS,	DB_ID,'(isset({form})&&({form}=="update"))'),

		'copy_type'	=>		array(T_ZBX_INT, O_OPT,	 P_SYS,	IN('0,1'),'isset({copy})'),
		'copy_mode'	=>		array(T_ZBX_INT, O_OPT,	 P_SYS,	IN('0'),NULL),

		'type'=>			array(T_ZBX_INT, O_OPT,  NULL, 		IN('0,1'),	'isset({save})'),
		'description'=>		array(T_ZBX_STR, O_OPT,  NULL,	NOT_EMPTY,'isset({save})', _('Name')),
		'expression'=>		array(T_ZBX_STR, O_OPT,  NULL,	NOT_EMPTY,'isset({save})'),
		'priority'=>		array(T_ZBX_INT, O_OPT,  NULL,  IN('0,1,2,3,4,5'),'isset({save})'),
		'comments'=>		array(T_ZBX_STR, O_OPT,  NULL,	NULL,'isset({save})'),
		'url'=>				array(T_ZBX_STR, O_OPT,  NULL,	NULL,'isset({save})'),
		'status'=>			array(T_ZBX_STR, O_OPT,  NULL,	NULL, NULL),

        'input_method'=>	array(T_ZBX_INT, O_OPT,  NULL,  NOT_EMPTY,'isset({toggle_input_method})'),
        'expr_temp'=>		array(T_ZBX_STR, O_OPT,  NULL,  NOT_EMPTY,'(isset({add_expression})||isset({and_expression})||isset({or_expression})||isset({replace_expression}))'),
        'expr_target_single'=>		array(T_ZBX_STR, O_OPT,  NULL,  NOT_EMPTY,'(isset({and_expression})||isset({or_expression})||isset({replace_expression}))'),

		'dependencies'=>	array(T_ZBX_INT, O_OPT,  NULL,	DB_ID, NULL),
		'new_dependence'=>	array(T_ZBX_INT, O_OPT,  NULL,	DB_ID.'{}>0','isset({add_dependence})'),
		'rem_dependence'=>	array(T_ZBX_INT, O_OPT,  NULL,	DB_ID, NULL),

		'g_triggerid'=>		array(T_ZBX_INT, O_OPT,  NULL,	DB_ID, NULL),
		'copy_targetid'=>	array(T_ZBX_INT, O_OPT,	NULL,	DB_ID, NULL),
		'filter_groupid'=>	array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID, 'isset({copy})&&(isset({copy_type})&&({copy_type}==0))'),

		'showdisabled'=>	array(T_ZBX_INT, O_OPT, P_SYS, IN('0,1'),	NULL),

// mass update
		'massupdate'=>		array(T_ZBX_STR, O_OPT, P_SYS,	NULL,	NULL),
		'visible'=>			array(T_ZBX_STR, O_OPT,	null, 	null,	null),

// Actions
		'go'=>					array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, NULL, NULL),

// form
        'toggle_input_method'=>	array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,    NULL,   NULL),
        'add_expression'=> 		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,    NULL,   NULL),
        'and_expression'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,    NULL,   NULL),
        'or_expression'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,    NULL,   NULL),
        'replace_expression'=>	array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,    NULL,   NULL),
        'remove_expression'=>	array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,    NULL,   NULL),
        'test_expression'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,    NULL,   NULL),

		'add_dependence'=>	array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'del_dependence'=>	array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'group_enable'=>	array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'group_disable'=>	array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'group_delete'=>	array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'copy'=>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'clone'=>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'save'=>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'mass_save'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'delete'=>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'cancel'=>			array(T_ZBX_STR, O_OPT, P_SYS,	NULL,	NULL),

// other
		'form'=>			array(T_ZBX_STR, O_OPT, P_SYS,	NULL,	NULL),
		'form_refresh'=>	array(T_ZBX_INT, O_OPT,	NULL,	NULL,	NULL)
	);

	$_REQUEST['showdisabled'] = get_request('showdisabled', CProfile::get('web.triggers.showdisabled', 0));

	check_fields($fields);
	validate_sort_and_sortorder('description',ZBX_SORT_UP);

	$_REQUEST['go'] = get_request('go','none');

// PERMISSIONS
	if (get_request('parent_discoveryid')) {
		$options = array(
			'itemids' => $_REQUEST['parent_discoveryid'],
			'output' => API_OUTPUT_EXTEND,
			'editable' => true,
			'preservekeys' => true
		);
		$discovery_rule = API::DiscoveryRule()->get($options);
		$discovery_rule = reset($discovery_rule);
		if(!$discovery_rule) {
			access_deny();
		}
		$_REQUEST['hostid'] = $discovery_rule['hostid'];

		if (isset($_REQUEST['triggerid'])) {
			$options = array(
				'triggerids' => $_REQUEST['triggerid'],
				'output' => API_OUTPUT_SHORTEN,
				'editable' => true,
				'preservekeys' => true
			);
			$triggerPrototype = API::TriggerPrototype()->get($options);
			if (empty($triggerPrototype)) {
				access_deny();
			}
		}
	}
	else{
		access_deny();
	}


	$showdisabled = get_request('showdisabled', 0);
	CProfile::update('web.triggers.showdisabled', $showdisabled, PROFILE_TYPE_INT);

// EXPRESSION ACTIONS
	if(isset($_REQUEST['add_expression'])){
		$_REQUEST['expression'] = $_REQUEST['expr_temp'];
		$_REQUEST['expr_temp'] = '';
	}
	else if(isset($_REQUEST['and_expression'])){
		$_REQUEST['expr_action'] = '&';
	}
	else if(isset($_REQUEST['or_expression'])){
		$_REQUEST['expr_action'] = '|';
	}
	else if(isset($_REQUEST['replace_expression'])){
		$_REQUEST['expr_action'] = 'r';
	}
	else if(isset($_REQUEST['remove_expression']) && zbx_strlen($_REQUEST['remove_expression'])){
		$_REQUEST['expr_action'] = 'R';
		$_REQUEST['expr_target_single'] = $_REQUEST['remove_expression'];
	}
/* FORM ACTIONS */
	else if(isset($_REQUEST['clone']) && isset($_REQUEST['triggerid'])){
		unset($_REQUEST['triggerid']);
		$_REQUEST['form'] = 'clone';
	}
	else if(isset($_REQUEST['save'])){
		$trigger = array(
			'expression' => $_REQUEST['expression'],
			'description' => $_REQUEST['description'],
			'type' => $_REQUEST['type'],
			'priority' => $_REQUEST['priority'],
			'status' => isset($_REQUEST['status']) ? TRIGGER_STATUS_DISABLED : TRIGGER_STATUS_ENABLED,
			'comments' => $_REQUEST['comments'],
			'url' => $_REQUEST['url'],
			'flags' => ZBX_FLAG_DISCOVERY_CHILD,
		);

		if(isset($_REQUEST['triggerid'])){
			$trigger['triggerid'] = $_REQUEST['triggerid'];
			$result = API::TriggerPrototype()->update($trigger);

			show_messages($result, S_TRIGGER_UPDATED, S_CANNOT_UPDATE_TRIGGER);
		}
		else{
			$result = API::TriggerPrototype()->create($trigger);

			show_messages($result, S_TRIGGER_ADDED, S_CANNOT_ADD_TRIGGER);
		}
		if($result)
			unset($_REQUEST['form']);
	}
	else if(isset($_REQUEST['delete']) && isset($_REQUEST['triggerid'])){
		$result = API::TriggerPrototype()->delete($_REQUEST['triggerid']);

		show_messages($result, S_TRIGGER_DELETED, S_CANNOT_DELETE_TRIGGER);
		if($result){
			unset($_REQUEST['form']);
			unset($_REQUEST['triggerid']);
		}
	}
// ------- GO ---------
	else if(($_REQUEST['go'] == 'massupdate') && isset($_REQUEST['mass_save']) && isset($_REQUEST['g_triggerid'])){
		$visible = get_request('visible');

		if(isset($visible['priority'])){
			$priority = get_request('priority');
			foreach($_REQUEST['g_triggerid'] as $triggerid){
				$result = API::TriggerPrototype()->update(array(
					'triggerid' => $triggerid,
					'priority' => $priority,
				));
				if(!$result) break;
			}
		}
		else{
			$result = true;
		}

		show_messages($result, S_TRIGGER_UPDATED, S_CANNOT_UPDATE_TRIGGER);
		if($result){
			unset($_REQUEST['massupdate']);
			unset($_REQUEST['form']);
		}

		$go_result = $result;
	}
	elseif (str_in_array($_REQUEST['go'], array('activate', 'disable')) && isset($_REQUEST['g_triggerid'])) {
		$go_result = true;

		if ($_REQUEST['go'] == 'activate') {
			$status = TRIGGER_STATUS_ENABLED;
			$statusOld = array('status' => TRIGGER_STATUS_DISABLED);
			$statusNew = array('status' => TRIGGER_STATUS_ENABLED);
		}
		else {
			$status = TRIGGER_STATUS_DISABLED;
			$statusOld = array('status' => TRIGGER_STATUS_ENABLED);
			$statusNew = array('status' => TRIGGER_STATUS_DISABLED);
		}

		DBstart();

		// get requested triggers with permission check
		$options = array(
			'triggerids' => $_REQUEST['g_triggerid'],
			'editable' => true,
			'output' => array('triggerid', 'status'),
			'preservekeys' => true
		);
		$triggers = API::TriggerPrototype()->get($options);

		// triggerids to gather child triggers
		$childTriggerIds = array_keys($triggers);

		// triggerids which status must be changed
		$triggerIdsToUpdate = array();
		foreach ($triggers as $triggerid => $trigger){
			if ($trigger['status'] != $status) {
				$triggerIdsToUpdate[] = $triggerid;
			}
		}


		do {
			// gather all triggerids which status should be changed including child triggers
			$options = array(
				'filter' => array('templateid' => $childTriggerIds),
				'output' => array('triggerid', 'status'),
				'preservekeys' => true,
				'nopermissions' => true
			);
			$triggers = API::TriggerPrototype()->get($options);

			$childTriggerIds = array_keys($triggers);

			foreach ($triggers as $triggerid => $trigger) {
				if ($trigger['status'] != $status) {
					$triggerIdsToUpdate[] = $triggerid;
				}
			}

		} while (!empty($childTriggerIds));

		DB::update('triggers', array(
			'values' => array('status' => $status),
			'where' => array('triggerid' => $triggerIdsToUpdate)
		));

		// get updated triggers with additional data
		$options = array(
			'triggerids' => $triggerIdsToUpdate,
			'output' => array('triggerid', 'description'),
			'preservekeys' => true,
			'selectHosts' => API_OUTPUT_EXTEND,
			'nopermissions' => true
		);
		$triggers = API::TriggerPrototype()->get($options);
		foreach ($triggers as $triggerid => $trigger) {
			$host = reset($trigger['hosts']);
			add_audit_ext(AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_TRIGGER_PROTOTYPE, $triggerid,
					$host['host'].':'.$trigger['description'], 'triggers', $statusOld, $statusNew);
		}

		$go_result = DBend($go_result);
		show_messages($go_result, _('Status updated'), _('Cannot update status'));
	}
	elseif (($_REQUEST['go'] == 'delete') && isset($_REQUEST['g_triggerid'])) {
		$go_result = API::TriggerPrototype()->delete($_REQUEST['g_triggerid']);
		show_messages($go_result, S_TRIGGERS_DELETED, S_CANNOT_DELETE_TRIGGERS);
	}

	if(($_REQUEST['go'] != 'none') && isset($go_result) && $go_result){
		$url = new CUrl();
		$path = $url->getPath();
		insert_js('cookie.eraseArray("'.$path.'")');
		$_REQUEST['go'] = 'none';
	}



	$triggers_wdgt = new CWidget();

	$form = new CForm('get');
	$form->addVar('parent_discoveryid', $_REQUEST['parent_discoveryid']);

// Config
	if(!isset($_REQUEST['form'])){
		$form->addItem(new CSubmit('form', S_CREATE_TRIGGER));
	}

	$triggers_wdgt->addPageHeader(S_CONFIGURATION_OF_TRIGGERS_PROTOTYPES_BIG, $form);


	if(($_REQUEST['go'] == 'massupdate') && isset($_REQUEST['g_triggerid'])){
		$triggers_wdgt->addItem(insert_mass_update_trigger_form());
	}
	else if(isset($_REQUEST['form'])){
		$triggers_wdgt->addItem(insert_trigger_form());
	}
	else{
// Triggers Header
		$numrows = new CDiv();
		$numrows->setAttribute('name','numrows');

		$tr_link = new CLink($showdisabled?S_HIDE_DISABLED_TRIGGERS : S_SHOW_DISABLED_TRIGGERS,
				'trigger_prototypes.php?showdisabled='.($showdisabled?0:1).'&parent_discoveryid='.$_REQUEST['parent_discoveryid']);


		$triggers_wdgt->addHeader(array(S_TRIGGER_PROTOTYPES_OF_BIG.SPACE, new CSpan($discovery_rule['name'], 'gold')));
		$triggers_wdgt->addHeader($numrows, array('[ ',$tr_link,' ]'));

		$triggers_wdgt->addItem(get_header_host_table($_REQUEST['hostid']));
// ----------------

		$form = new CForm();
		$form->setName('triggers');
		$form->addVar('parent_discoveryid', $_REQUEST['parent_discoveryid']);

// get Triggers
		$sortfield = getPageSortField('description');
		$sortorder = getPageSortOrder();

		$options = array(
			'editable' => 1,
			'output' => API_OUTPUT_SHORTEN,
			'discoveryids' => $_REQUEST['parent_discoveryid'],
			'filter' => array('flags' => ZBX_FLAG_DISCOVERY_CHILD),
			'sortfield' => $sortfield,
			'sortorder' => $sortorder,
			'limit' => ($config['search_limit']+1)
		);
		if($showdisabled == 0) $options['filter']['status'] = TRIGGER_STATUS_ENABLED;

		$triggers = API::Trigger()->get($options);

// sorting && paging
		order_result($triggers, $sortfield, $sortorder);
		$paging = getPagingLine($triggers);

		$options = array(
			'triggerids' => zbx_objectValues($triggers, 'triggerid'),
			'output' => API_OUTPUT_EXTEND,
			'filter' => array('flags' => ZBX_FLAG_DISCOVERY_CHILD),
			'selectHosts' => API_OUTPUT_EXTEND,
			'selectItems' => API_OUTPUT_EXTEND,
			'selectFunctions' => API_OUTPUT_EXTEND,
		);
		$triggers = API::Trigger()->get($options);
		order_result($triggers, $sortfield, $sortorder);

		$realHosts = getParentHostsByTriggers($triggers);

		$table = new CTableInfo(S_NO_TRIGGERS_DEFINED);

		$link = new Curl();
		$link->setArgument('parent_discoveryid', $_REQUEST['parent_discoveryid']);

		$table->setHeader(array(
			new CCheckBox('all_triggers',NULL,"checkAll('".$form->getName()."','all_triggers','g_triggerid');"),
			make_sorting_header(S_SEVERITY,'priority', $link->getUrl()),
			make_sorting_header(S_STATUS,'status', $link->getUrl()),
			make_sorting_header(S_NAME,'description', $link->getUrl()),
			S_EXPRESSION,
		));

		foreach($triggers as $tnum => $trigger){
			$triggerid = $trigger['triggerid'];

			$trigger['hosts'] = zbx_toHash($trigger['hosts'], 'hostid');
			$trigger['items'] = zbx_toHash($trigger['items'], 'itemid');
			$trigger['functions'] = zbx_toHash($trigger['functions'], 'functionid');
			$trigger['discoveryRuleid'] = $_REQUEST['parent_discoveryid'];

			$description = array();
			if($trigger['templateid'] > 0){
				if(!isset($realHosts[$triggerid])){
					$description[] = new CSpan(S_TEMPLATE,'unknown');
					$description[] = ':';
				}
				else{
					$real_hosts = $realHosts[$triggerid];
					$real_host = reset($real_hosts);
					$tpl_disc_ruleid = get_realrule_by_itemid_and_hostid($_REQUEST['parent_discoveryid'], $real_host['hostid']);

					$description[] = new CLink($real_host['host'], 'trigger_prototypes.php?parent_discoveryid='.$tpl_disc_ruleid, 'unknown');
					$description[] = ':';
				}
			}

			$description[] = new CLink($trigger['description'], 'trigger_prototypes.php?form=update'.
					'&parent_discoveryid='.$_REQUEST['parent_discoveryid'].'&triggerid='.$triggerid);

			if($trigger['value'] != TRIGGER_VALUE_UNKNOWN) $trigger['error'] = '';

			$templated = false;
			foreach($trigger['hosts'] as $hostid => $host){
				$templated |= (HOST_STATUS_TEMPLATE == $host['status']);
			}

			$status_link = 'trigger_prototypes.php?go='.(($trigger['status'] == TRIGGER_STATUS_DISABLED) ? 'activate' : 'disable').
				'&g_triggerid%5B%5D='.$triggerid.'&parent_discoveryid='.$_REQUEST['parent_discoveryid'];
			if($trigger['status'] == TRIGGER_STATUS_DISABLED){
				$status = new CLink(S_DISABLED, $status_link, 'disabled');
			}
			else if($trigger['status'] == TRIGGER_STATUS_ENABLED){
				$status = new CLink(S_ENABLED, $status_link, 'enabled');
			}

			$table->addRow(array(
				new CCheckBox('g_triggerid['.$triggerid.']', NULL, NULL, $triggerid),
				getSeverityCell($trigger['priority']),
				$status,
				$description,
				triggerExpression($trigger, 1),
			));
		}

//----- GO ------
		$goBox = new CComboBox('go');
		$goOption = new CComboItem('activate',S_ACTIVATE_SELECTED);
		$goOption->setAttribute('confirm',S_ENABLE_SELECTED_TRIGGERS_Q);
		$goBox->addItem($goOption);

		$goOption = new CComboItem('disable',S_DISABLE_SELECTED);
		$goOption->setAttribute('confirm',S_DISABLE_SELECTED_TRIGGERS_Q);
		$goBox->addItem($goOption);

		$goOption = new CComboItem('massupdate',S_MASS_UPDATE);
		$goBox->addItem($goOption);

		$goOption = new CComboItem('delete',S_DELETE_SELECTED);
		$goOption->setAttribute('confirm',S_DELETE_SELECTED_TRIGGERS_Q);
		$goBox->addItem($goOption);

		$goButton = new CSubmit('goButton',S_GO);
		$goButton->setAttribute('id','goButton');

		zbx_add_post_js('chkbxRange.pageGoName = "g_triggerid";');

		$footer = get_table_header(array($goBox, $goButton));

		$form->addItem(array($paging,$table,$paging,$footer));
		$triggers_wdgt->addItem($form);
	}

	$triggers_wdgt->show();



require_once('include/page_footer.php');

?>
