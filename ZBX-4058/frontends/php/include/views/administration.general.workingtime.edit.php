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

$workingTimeTab = new CFormList('scriptsTab');
$periodTextBox = new CTextBox('work_period', $this->data['config']['work_period'], 35);
$periodTextBox->addStyle('text-align: right;');
$workingTimeTab->addRow(_('Working time'), $periodTextBox);

$workingTimeView = new CTabView();
$workingTimeView->addTab('workingTime', _('Working time'), $workingTimeTab);

$workingTimeForm = new CForm();
$workingTimeForm->setName('workingTimeForm');

$workingTimeForm->addVar('form', $this->data['form']);
$workingTimeForm->addVar('form_refresh', $this->data['form_refresh'] + 1);
$workingTimeForm->addVar('config', get_request('config', 7));
$workingTimeForm->addItem($workingTimeView);
$workingTimeForm->addItem(makeFormFooter(array(new CSubmit('save', _('Save')))));

return $workingTimeForm;
?>
