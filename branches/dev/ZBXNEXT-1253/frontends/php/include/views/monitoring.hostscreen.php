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


$screenWidget = new CWidget();
$screenWidget->addFlicker(new CDiv(null, null, 'scrollbar_cntr'), CProfile::get('web.hostscreen.filter.state', 1));

$form = new CForm('get');
$form->addVar('fullscreen', $_REQUEST['fullscreen']);
$screenWidget->addItem($form);

if (empty($this->data['screen'])) {
	$screenWidget->addPageHeader(_('SCREENS'));
	$screenWidget->addItem(BR());
	$screenWidget->addItem(new CTableInfo(_('No screens defined.')));
	$screenWidget->show();
}
else {
	$screenWidget->addPageHeader(_('SCREENS'), array(get_icon('fullscreen', array('fullscreen' => $this->data['fullscreen']))));
	$screenWidget->addItem(BR());

	// host screen list
	$screenList = new CList(null, 'objectlist');
	foreach ($this->data['screens'] as $screen) {
		$screenName = get_node_name_by_elid($screen['screenid'], null, ': ').$screen['name'];

		if (count($this->data['screens']) > 1) {
			if (bccomp($screen['screenid'], $this->data['screenid']) == 0) {
				$screenList->addItem($screenName, 'selected');
			}
			else {
				$screenList->addItem(new CLink($screenName, 'host_screen.php?screenid='.$screen['screenid'].'&hostid='.$this->data['hostid']));
			}
		}
		else {
			$screenList->addItem($screenName);
		}
	}
	$screenWidget->addHeader($screenList);

	$period = navigation_bar_calc('web.screens', $this->data['screen']['screenid'], true);

	$screenBuilder = new CScreenBuilder(array(
		'screen' => $this->data['screen'],
		'mode' => SCREEN_MODE_PREVIEW,
		'period' => $period,
		'profile_idx' => 'web.screens'
	));

	$timeline = array(
		'period' => $period,
		'starttime' => date('YmdHis', time() - ZBX_MAX_PERIOD)
	);
	if (!empty($this->data['stime'])) {
		$timeline['usertime'] = date('YmdHis', zbxDateToTime($this->data['stime']) + $timeline['period']);
	}

	$screenWidget->addItem($screenBuilder->show());

	CScreenBuilder::insertScreenScrollJs($this->data['screen']['screenid'], $timeline);
	CScreenBuilder::insertProcessObjectsJs();
}

return $screenWidget;
