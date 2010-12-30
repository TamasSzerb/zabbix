<?php
/*
** ZABBIX
** Copyright (C) 2000-2010 SIA Zabbix
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
// include JS + templates
	include('include/templates/script.js.php');
	include('include/templates/scriptConfirm.js.php');
?>
<?php
	$scriptTab = new CFormList('script');
	$scriptTab->addClass('min-width ui-tabs ui-widget ui-widget-content ui-corner-all');

	$frmScr = new CForm();
	$frmScr->setName('scripts');
	$frmScr->addVar('form', get_request('form', 1));

	$from_rfr = get_request('form_refresh',0);
	$frmScr->addVar('form_refresh', $from_rfr+1);


	if(isset($_REQUEST['scriptid'])) $frmScr->addVar('scriptid', $_REQUEST['scriptid']);

	if(!isset($_REQUEST['scriptid']) || isset($_REQUEST['form_refresh'])){
		$name = get_request('name', '');
		$command  = get_request('command', '');
		$description  = get_request('description', '');
		$usrgrpid = get_request('usrgrpid',	0);
		$groupid = get_request('groupid', 0);
		$access = get_request('access',	PERM_READ_ONLY);
		$question = get_request('question',	'');
		$enableQuestion = get_request('enableQuestion', false);
	}

	if(isset($_REQUEST['scriptid']) && !isset($_REQUEST['form_refresh'])){
		$frmScr->addVar('form_refresh', get_request('form_refresh',1));

		$options = array(
			'scriptids' => $_REQUEST['scriptid'],
			'output' => API_OUTPUT_EXTEND,
		);
		$script = CScript::get($options);
		$script = reset($script);

		if($script){
			$name = $script['name'];
			$command  = $script['command'];
			$description = $script['description'];
			$usrgrpid = $script['usrgrpid'];
			$groupid = $script['groupid'];
			$access = $script['host_access'];
			$question = $script['question'];
			$enableQuestion = !empty($question);
		}
		else{
			$name = $command = $description = $usrgrpid = $groupid = $access = $question = $enableQuestion = '';
		}
	}

// NAME
	$nameTB = new CTextBox('name', $name);
	$nameTB->setAttribute('maxlength', 255);
	$nameTB->addStyle('width: 425px');
	$scriptTab->addRow(S_NAME, $nameTB);

// COMMAND
	$commandTB = new CTextBox('command', $command);
	$commandTB->setAttribute('maxlength', 255);
	$commandTB->addStyle('width: 425px');
	$scriptTab->addRow(S_COMMAND, $commandTB);

// DESCRIPTION
	$description_ta = new CTextArea('description', $description);
	$description_ta->addStyle('width: 425px; padding: 0;');
	$scriptTab->addRow(_('Description'), $description_ta);

// USER GROUPS
	$usr_groups = new CCombobox('usrgrpid', $usrgrpid);
	$usr_groups->addItem(0, S_ALL_S);
	$usrgrps = CUserGroup::get(array(
		'output' => API_OUTPUT_EXTEND,
	));
	order_result($usrgrps, 'name');
	foreach($usrgrps as $ugnum => $usr_group){
		$usr_groups->addItem($usr_group['usrgrpid'], $usr_group['name']);
	}
	$scriptTab->addRow(S_USER_GROUPS, $usr_groups);

// HOST GROUPS
	$host_groups = new CCombobox('groupid', $groupid);
	$host_groups->addItem(0,S_ALL_S);
	$groups = CHostGroup::get(array(
		'output' => API_OUTPUT_EXTEND,
	));
	order_result($groups, 'name');
	foreach($groups as $gnum => $group){
		$host_groups->addItem($group['groupid'], $group['name']);
	}
	$scriptTab->addRow(S_HOST_GROUPS, $host_groups);

// PERMISSIONS
	$select_acc = new CCombobox('access', $access);
	$select_acc->addItem(PERM_READ_ONLY, S_READ);
	$select_acc->addItem(PERM_READ_WRITE, S_WRITE);
	$scriptTab->addRow(_('Required host permissions'), $select_acc);

// QUESTION
	$enableQuestCB = new CCheckBox('enableQuestion', $enableQuestion);
	$enableQuestCB->setAttribute('id', 'enableQuestion');
	// SPACE for layout bug in chrome8
	$scriptTab->addRow(new CLabel(_('Enable question'), 'enableQuestion'), array($enableQuestCB, SPACE));

	$questionTB = new CTextBox('question', $question, 65);
	$questionTB->setAttribute('id', 'question');
	$questionTB->setAttribute('maxlength', 255);
	$questionTB->setAttribute('autocomplete', 'off');

	$testSpan = new CSpan(_('Test question'), 'link_menu');
	$testSpan->setAttribute('id', 'testQuestion');

	$scriptTab->addRow(SPACE, array($questionTB, SPACE, $testSpan));

	$scriptTab->setHeader(_('Script'));

	$frmScr->addItem($scriptTab);


// Footer
	$main = array(new CSubmit('save', S_SAVE));
	$others = array();
	if(isset($_REQUEST['scriptid'])){
		$others[] = new CButtonDelete(S_DELETE_SCRIPTS_Q, url_param('form').url_param('scriptid'));
	}
	$others[] = new CButtonCancel();
	$frmScr->addItem(makeFormFooter($main, $others));


	return $frmScr;

?>
