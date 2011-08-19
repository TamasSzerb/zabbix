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

$valueMappingForm = new CForm();
$valueMappingForm->setName('valueMappingForm');
$valueMappingForm->addVar('form', $this->data['form']);
$valueMappingForm->addVar('form_refresh', $this->data['form_refresh'] + 1);
$valueMappingForm->addVar('config', get_request('config', 6));
$valueMappingForm->addVar('valuemapid', $this->data['valuemapid']);

$i = 0;
$valuemapElements = array();
foreach ($this->data['valuemap'] as $valuemap) {
	array_push($valuemapElements, array(new CCheckBox('rem_value[]', 'no', null, $i), $valuemap['value'].SPACE.RARR.SPACE.$valuemap['newvalue']), BR());
	$valueMappingForm->addVar('valuemap['.$i.'][value]', $valuemap['value']);
	$valueMappingForm->addVar('valuemap['.$i.'][newvalue]', $valuemap['newvalue']);
	$i++;
}
if (empty($valuemapElements)) {
	array_push($valuemapElements, _('No mapping defined.'));
}
else {
	array_push($valuemapElements, new CSubmit('del_map', _('Delete selected')));
}

// append form list
$valueMappingFormList = new CFormList('valueMappingFormList');
$valueMappingFormList->addRow(_('Name'), array(new CTextBox('mapname', $this->data['mapname'], 40)));
$valueMappingFormList->addRow(_('Mapping'), $valuemapElements);
$valueMappingFormList->addRow(_('New mapping'), array(new CTextBox('add_value', '', 10), new CSpan(RARR, 'rarr'), new CTextBox('add_newvalue', '', 10), SPACE, new CSubmit('add_map', _('Add'))));

// append tab
$valueMappingTab = new CTabView();
$valueMappingTab->addTab('valuemapping', _('Value mapping').$this->data['title'], $valueMappingFormList);
$valueMappingForm->addItem($valueMappingTab);

// append buttons
$saveButton = new CSubmit('save', _('Save'));
if (empty($valuemapElements)) {
	$saveButton->setAttribute('disabled', 'true');
}
if (!empty($this->data['valuemapid'])) {
	$valueMappingForm->addItem(makeFormFooter(array($saveButton, new CButtonDelete($this->data['confirmMessage'], url_param('form').url_param('valuemapid').url_param('config')), new CButtonCancel(url_param('config')))));
}
else {
	$valueMappingForm->addItem(makeFormFooter(array($saveButton, new CButtonCancel(url_param('config')))));
}

return $valueMappingForm;
?>
