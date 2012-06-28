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


class CFlickerfreeScreenGraph extends CFlickerfreeScreenItem {

	public function __construct(array $options = array()) {
		parent::__construct($options);
	}

	public function get() {
		$resourceid = !empty($this->screenitem['real_resourceid']) ? $this->screenitem['real_resourceid'] : $this->screenitem['resourceid'];
		$domGraphid = 'graph_'.$this->screenitem['screenitemid'].'_'.$this->screenitem['screenid'];
		$containerid = 'graph_container_'.$this->screenitem['screenitemid'].'_'.$this->screenitem['screenid'];
		$graphDims = getGraphDims($resourceid);
		$graphDims['graphHeight'] = $this->screenitem['height'];
		$graphDims['width'] = $this->screenitem['width'];
		$graph = get_graph_by_graphid($resourceid);
		$graphid = $graph['graphid'];
		$legend = $graph['show_legend'];
		$graph3d = $graph['show_3d'];

		if ($this->screenitem['dynamic'] == SCREEN_DYNAMIC_ITEM && !empty($this->hostid)) {
			// get host
			$hosts = API::Host()->get(array(
				'hostids' => $this->hostid,
				'output' => array('hostid', 'host')
			));
			$host = reset($hosts);

			// get graph
			$graph = API::Graph()->get(array(
				'graphids' => $resourceid,
				'output' => API_OUTPUT_EXTEND,
				'selectHosts' => API_OUTPUT_REFER,
				'selectGraphItems' => API_OUTPUT_EXTEND
			));
			$graph = reset($graph);

			// if items from one host we change them, or set calculated if not exist on that host
			if (count($graph['hosts']) == 1) {
				if ($graph['ymax_type'] == GRAPH_YAXIS_TYPE_ITEM_VALUE && $graph['ymax_itemid']) {
					$newDinamic = get_same_graphitems_for_host(
						array(array('itemid' => $graph['ymax_itemid'])),
						$this->hostid,
						false
					);
					$newDinamic = reset($newDinamic);

					if (isset($newDinamic['itemid']) && $newDinamic['itemid'] > 0) {
						$graph['ymax_itemid'] = $newDinamic['itemid'];
					}
					else {
						$graph['ymax_type'] = GRAPH_YAXIS_TYPE_CALCULATED;
					}
				}

				if ($graph['ymin_type'] == GRAPH_YAXIS_TYPE_ITEM_VALUE && $graph['ymin_itemid']) {
					$newDinamic = get_same_graphitems_for_host(
						array(array('itemid' => $graph['ymin_itemid'])),
						$this->hostid,
						false
					);
					$newDinamic = reset($newDinamic);

					if (isset($newDinamic['itemid']) && $newDinamic['itemid'] > 0) {
						$graph['ymin_itemid'] = $newDinamic['itemid'];
					}
					else {
						$graph['ymin_type'] = GRAPH_YAXIS_TYPE_CALCULATED;
					}
				}
			}

			// get url
			$this->screenitem['url'] = ($graph['graphtype'] == GRAPH_TYPE_PIE || $graph['graphtype'] == GRAPH_TYPE_EXPLODED)
				? 'chart7.php'
				: 'chart3.php';
			$this->screenitem['url'] = new CUrl($this->screenitem['url']);

			foreach ($graph as $name => $value) {
				if ($name == 'width' || $name == 'height') {
					continue;
				}
				$this->screenitem['url']->setArgument($name, $value);
			}

			$newGraphItems = get_same_graphitems_for_host($graph['gitems'], $this->hostid, false);
			foreach ($newGraphItems as $newGraphItem) {
				unset($newGraphItem['gitemid'], $newGraphItem['graphid']);

				foreach ($newGraphItem as $name => $value) {
					$this->screenitem['url']->setArgument('items['.$newGraphItem['itemid'].']['.$name.']', $value);
				}
			}

			$this->screenitem['url']->setArgument('name', $host['host'].': '.$graph['name']);
			$this->screenitem['url'] = $this->screenitem['url']->getUrl();
		}

		// get time control
		$timeControlData = array(
			'id' => $this->screenitem['screenitemid'].'_'.$this->screenitem['screenid'],
			'domid' => $domGraphid,
			'containerid' => $containerid,
			'objDims' => $graphDims,
			'loadSBox' => 0,
			'loadImage' => 1,
			'loadScroll' => 0,
			'dynamic' => 0,
			'periodFixed' => CProfile::get('web.screens.timelinefixed', 1),
			'sliderMaximumTimePeriod' => ZBX_MAX_PERIOD
		);

		$isDefault = false;
		if ($graphDims['graphtype'] == GRAPH_TYPE_PIE || $graphDims['graphtype'] == GRAPH_TYPE_EXPLODED) {
			if ($this->screenitem['dynamic'] == SCREEN_SIMPLE_ITEM || empty($this->screenitem['url'])) {
				$this->screenitem['url'] = 'chart6.php?graphid='.$resourceid;
				$isDefault = true;
			}

			$timeline = array(
				'period' => $this->period,
				'starttime' => date('YmdHis', get_min_itemclock_by_graphid($resourceid))
			);

			if (!empty($this->stime)) {
				$timeline['usertime'] = date('YmdHis', zbxDateToTime($this->stime) + $timeline['period']);
			}

			$src = $this->screenitem['url'].'&width='.$this->screenitem['width'].'&height='.$this->screenitem['height']
				.'&legend='.$legend.'&graph3d='.$graph3d.'&period='.$this->period.url_param('stime');

			$timeControlData['src'] = $src;
		}
		else {
			if ($this->screenitem['dynamic'] == SCREEN_SIMPLE_ITEM || empty($this->screenitem['url'])) {
				$this->screenitem['url'] = 'chart2.php?graphid='.$resourceid;
				$isDefault = true;
			}

			$src = $this->screenitem['url'].'&width='.$this->screenitem['width'].'&height='.$this->screenitem['height']
				.'&period='.$this->period.url_param('stime');

			$timeline = array(
				'period' => $this->period
			);

			if ($this->mode != SCREEN_MODE_EDIT && !empty($graphid)) {
				$timeline['starttime'] = date('YmdHis', time() - ZBX_MAX_PERIOD);

				if (!empty($this->stime)) {
					$timeline['usertime'] = date('YmdHis', zbxDateToTime($this->stime) + $timeline['period']);
				}
				if ($this->mode == SCREEN_MODE_PREVIEW) {
					$timeControlData['loadSBox'] = 1;
				}
			}
			$timeControlData['src'] = $src;
		}

		// output
		if ($this->mode == SCREEN_MODE_JS) {
			return 'timeControl.addObject("'.$domGraphid.'", '.zbx_jsvalue($timeline).', '.zbx_jsvalue($timeControlData).')';
		}
		else {
			if ($this->mode == SCREEN_MODE_VIEW) { // used is slide shows
				insert_js('timeControl.addObject("'.$domGraphid.'", '.zbx_jsvalue($timeline).', '.zbx_jsvalue($timeControlData).');');
			}
			else {
				zbx_add_post_js('timeControl.addObject("'.$domGraphid.'", '.zbx_jsvalue($timeline).', '.zbx_jsvalue($timeControlData).');');
			}

			if (($this->mode == SCREEN_MODE_EDIT || $this->mode == SCREEN_MODE_VIEW) || !$isDefault) {
				$item = new CDiv();
			}
			elseif ($this->mode == SCREEN_MODE_PREVIEW) {
				$item = new CLink(null, 'charts.php?graphid='.$resourceid.url_params(array('period', 'stime')));
			}
			$item->setAttribute('id', $containerid);

			return $this->getOutput($item);
		}
	}
}
