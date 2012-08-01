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


class CScreenSimpleGraph extends CScreenBase {

	/**
	 * Process screen.
	 *
	 * @return CDiv (screen inside container)
	 */
	public function get() {
		$this->dataId = 'graph_'.$this->screenitem['screenitemid'].'_'.$this->screenitem['screenid'];
		$resourceid = !empty($this->screenitem['real_resourceid']) ? $this->screenitem['real_resourceid'] : $this->screenitem['resourceid'];
		$containerid = 'graph_container_'.$this->screenitem['screenitemid'].'_'.$this->screenitem['screenid'];
		$graphDims = getGraphDims();
		$graphDims['graphHeight'] = $this->screenitem['height'];
		$graphDims['width'] = $this->screenitem['width'];

		// get time control
		$timeControlData = array(
			'id' => $resourceid,
			'domid' => $this->getDataId(),
			'containerid' => $containerid,
			'objDims' => $graphDims,
			'loadSBox' => 0,
			'loadImage' => 1,
			'loadScroll' => 0,
			'dynamic' => 0,
			'periodFixed' => CProfile::get('web.screens.timelinefixed', 1),
			'sliderMaximumTimePeriod' => ZBX_MAX_PERIOD
		);

		// host feature
		if ($this->screenitem['dynamic'] == SCREEN_DYNAMIC_ITEM && !empty($this->hostid)) {
			$newitemid = get_same_item_for_host($resourceid, $this->hostid);
			$resourceid = !empty($newitemid) ? $newitemid : '';
		}

		if ($this->mode == SCREEN_MODE_PREVIEW && !empty($resourceid)) {
			$this->action = 'history.php?action=showgraph&itemid='.$resourceid.'&period='.$this->timeline['period'].'&stime='.$this->timeline['stimeNow'];
		}

		if (!zbx_empty($resourceid) && $this->mode != SCREEN_MODE_EDIT) {
			if ($this->mode == SCREEN_MODE_PREVIEW) {
				$timeControlData['loadSBox'] = 1;
			}
		}

		$timeControlData['src'] = zbx_empty($resourceid)
			? 'chart3.php?'
			: 'chart.php?itemid='.$resourceid.'&'.$this->screenitem['url'].'&width='.$this->screenitem['width'].'&height='.$this->screenitem['height'];

		// output
		if ($this->mode == SCREEN_MODE_JS) {
			return 'timeControl.addObject("'.$this->getDataId().'", '.zbx_jsvalue($this->timeline).', '.zbx_jsvalue($timeControlData).')';
		}
		else {
			if ($this->mode == SCREEN_MODE_VIEW) { // used in slide shows
				insert_js('timeControl.addObject("'.$this->getDataId().'", '.zbx_jsvalue($this->timeline).', '.zbx_jsvalue($timeControlData).');');
			}
			else {
				zbx_add_post_js('timeControl.addObject("'.$this->getDataId().'", '.zbx_jsvalue($this->timeline).', '.zbx_jsvalue($timeControlData).');');
			}

			if ($this->mode == SCREEN_MODE_EDIT || $this->mode == SCREEN_MODE_VIEW) {
				$item = new CDiv();
			}
			elseif ($this->mode == SCREEN_MODE_PREVIEW) {
				$item = new CLink(null, 'charts.php?graphid='.$resourceid.'&period='.$this->timeline['period'].'&stime='.$this->timeline['stimeNow']);
			}
			$item->setAttribute('id', $containerid);

			return $this->getOutput($item);
		}
	}
}
