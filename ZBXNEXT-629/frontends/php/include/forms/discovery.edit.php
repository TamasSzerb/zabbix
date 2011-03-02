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
// include JS + templates
require_once('include/templates/discovery.js.php');
?>
<?php
	$data = $data;
	$inputLength = 60;

	$divTabs = new CTabView(array('remember'=>1));
	if(!isset($_REQUEST['form_refresh'])) $divTabs->setSelected(0);

	$formDsc = new CForm();
	$formDsc->setName('discovery.edit');

	$from_rfr = get_request('form_refresh',0);
	$formDsc->addVar('form_refresh', $from_rfr+1);
	$formDsc->addVar('form', get_request('form', 1));

	if(isset($_REQUEST['druleid'])) $formDsc->addVar('druleid', $_REQUEST['druleid']);

	if(isset($data['druleid']))
		$formDsc->setTitle(_s('Discovery rule "%s"',$data['name']));
	else
		$formDsc->setTitle(_('Discovery rule'));

	$discoveryList = new CFormList('actionlist');
	$discoveryList->addRow(_('Name'), new CTextBox('name', $data['name'], $inputLength));


	$cmbProxy = new CComboBox('proxy_hostid', $data['proxy_hostid']);
	$cmbProxy->addItem(0, _('No proxy'));

	$proxies = API::Proxy()->get(array(
		'output' => API_OUTPUT_EXTEND
	));

	order_result($proxies,'host');
	foreach($proxies as $pnum => $proxy){
		$cmbProxy->addItem($proxy['proxyid'], $proxy['host']);
	}

	$discoveryList->addRow(_('Discovery by proxy'), $cmbProxy);


	$discoveryList->addRow(_('IP range'), new CTextBox('iprange', $data['iprange'], 27));
	$discoveryList->addRow(_('Delay (seconds)'), new CNumericBox('delay', $data['delay'], 8));

// DChecks
	$dcheckList = new CTable(null, "formElementTable");
	$addDCheckBtn = new CButton('newCheck', _('New'), null, 'link_menu');

	$col = new CCol($addDCheckBtn);
	$col->setAttribute('colspan', 2);

	$buttonRow = new CRow($col);
	$buttonRow->setAttribute('id', 'dcheckListFooter');

	$dcheckList->addRow($buttonRow);

// Add Discovery Checks
	foreach($data['dchecks'] as $id => $dcheck)
		$data['dchecks'][$id]['name'] = discovery_check2str($data['dchecks'][$id]['type'], $data['dchecks'][$id]['snmp_community'], $data['dchecks'][$id]['key_'], $data['dchecks'][$id]['ports']);

	order_result($data['dchecks'], 'name');

	$jsInsert = '';
	$jsInsert.= 'addPopupValues('.zbx_jsvalue(array('object'=>'dcheckid', 'values'=>array_values($data['dchecks']))).');';

	$discoveryList->addRow(_('Checks'), new CDiv($dcheckList, 'objectgroup inlineblock border_dotted ui-corner-all', 'dcheckList'));
// -------

	$cmbUniquenessCriteria = new CRadioButton('uniqueness_criteria', $data['uniqueness_criteria']);
	$cmbUniquenessCriteria->addValue(_('IP address'), -1);

	$discoveryList->addRow(_('Device uniqueness criteria'), new CDiv($cmbUniquenessCriteria, 'objectgroup inlineblock border_dotted ui-corner-all', 'uniqList'));

	$jsInsert.= 'jQuery("input:radio[name=uniqueness_criteria][value='.zbx_jsvalue($data['uniqueness_criteria']).']").attr("checked", "checked");';

	$cmbStatus = new CComboBox('status', $data['status']);
	foreach(array(DRULE_STATUS_ACTIVE, DRULE_STATUS_DISABLED) as $st)
		$cmbStatus->addItem($st, discovery_status2str($st));

	$discoveryList->addRow(_('Status'), $cmbStatus);

	$divTabs->addTab('druleTab', _('Discovery rule'), $discoveryList);
	$formDsc->addItem($divTabs);

// Footer
	$main = array(new CSubmit('save', _('Save')));
	$others = array();
	if(isset($_REQUEST['druleid'])){
		$others[] = new CButton('clone', _('Clone'));
		$others[] = new CButtonDelete(_('Delete discovery rule?'), url_param('form').url_param('druleid'));
	}
	$others[] = new CButtonCancel();

	$footer = makeFormFooter($main, $others);
	$formDsc->addItem($footer);

	zbx_add_post_js($jsInsert);

	return $formDsc;
?>
