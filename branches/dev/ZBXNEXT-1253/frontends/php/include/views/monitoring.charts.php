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


$chartsWidget = new CWidget('hat_charts');

$chartForm = new CForm('get');
$chartForm->addVar('fullscreen', $this->data['fullscreen']);
$chartForm->addItem(array(_('Group').SPACE, $this->data['pageFilter']->getGroupsCB(true)));
$chartForm->addItem(array(SPACE._('Host').SPACE, $this->data['pageFilter']->getHostsCB(true)));
$chartForm->addItem(array(SPACE._('Graph').SPACE, $this->data['pageFilter']->getGraphsCB(true)));

$icons = array();
if ($this->data['pageFilter']->graphsSelected) {
	$chartsWidget->addFlicker(new CDiv(null, null, 'scrollbar_cntr'), CProfile::get('web.charts.filter.state', 1));

	$icons[] = get_icon('favourite', array('fav' => 'web.favorite.graphids', 'elname' => 'graphid', 'elid' => $this->data['graphid']));
	$icons[] = SPACE;
	$icons[] = get_icon('reset', array('id' => $this->data['graphid']));
	$icons[] = SPACE;
	$icons[] = get_icon('fullscreen', array('fullscreen' => $this->data['fullscreen']));
}

$chartsWidget->addPageHeader(_('Graphs'), $icons);
$chartsWidget->addHeader(
	!empty($this->data['pageFilter']->graphs[$this->data['pageFilter']->graphid])
		? $this->data['pageFilter']->graphs[$this->data['pageFilter']->graphid]
		: null,
	$chartForm
);
$chartsWidget->addItem(BR());

// append chart to widget
$screen = CScreenBuilder::getScreen(array(
	'resourcetype' => SCREEN_RESOURCE_CHART,
	'profileIdx' => 'web.charts',
	'graphid' => $this->data['graphid'],
	'period' => navigation_bar_calc('web.charts', $this->data['graphid'], true),
	'stime' => get_request('stime')
));
$chartsWidget->addItem($screen->get());
CScreenBuilder::insertProcessObjectsJs();

return $chartsWidget;
