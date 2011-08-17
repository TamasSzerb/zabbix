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
$this->data['cnf_wdgt']->addHeader(_('Regular expressions'));

$regExpForm = new CForm();
$regExpForm->setName('regularExpressionsForm');
$regExpForm->addVar('config', get_request('config', 10));
$regExpForm->addItem(BR());

$regExpTable = new CTableInfo();
$regExpTable->setHeader(array(new CCheckBox('all_regexps', null, "checkAll('regularExpressionsForm','all_regexps','regexpids');"), _('Name'), _('Expressions')));
$regExpTable->setFooter(new CCol(array(new CButtonQMessage('delete', _('Delete selected'), _('Delete selected regular expressions?')))));

$count = array();
$expressions = array();
foreach($this->data['db_exps'] as $exp){
	if(!isset($expressions[$exp['regexpid']])){
		$count[$exp['regexpid']] = 1;
	}
	else{
		$count[$exp['regexpid']]++;
	}
	if(!isset($expressions[$exp['regexpid']])){
		$expressions[$exp['regexpid']] = new CTable();
	}
	$expressions[$exp['regexpid']]->addRow(array($count[$exp['regexpid']], ' &raquo; ', $exp['expression'], ' ['.expression_type2str($exp['expression_type']).']'));
}
foreach($this->data['regexps'] as $regexpid => $regexp){
	$regExpTable->addRow(array(
		new CCheckBox('regexpids['.$regexp['regexpid'].']', null, null, $regexp['regexpid']),
		new CLink($regexp['name'], 'config.php?form=update'.url_param('config').'&regexpid='.$regexp['regexpid'].'#form'),
		isset($expressions[$regexpid]) ? $expressions[$regexpid] : '-'
	));
}

$regExpForm->addItem($regExpTable);

return $regExpForm;
?>
