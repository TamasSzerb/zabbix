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


class CScreenScreen extends CScreenBase {

	/**
	 * Process screen.
	 *
	 * @return CDiv (screen inside container)
	 */
	public function get() {
		$screen = API::Screen()->get(array(
			'screenids' => $this->screenitem['resourceid'],
			'output' => API_OUTPUT_EXTEND,
			'selectScreenItems' => API_OUTPUT_EXTEND
		));
		$screen = reset($screen);

		$screenBuilder = new CScreenBuilder(array(
			'isFlickerfree' => $this->isFlickerfree,
			'mode' => ($this->mode == SCREEN_MODE_EDIT) ? SCREEN_MODE_VIEW : SCREEN_MODE_PREVIEW,
			'screen' => $screen,
			'period' => $this->timeline['period'],
			'stime' => $this->timeline['stime'],
			'profileIdx' => $this->profileIdx
		));

		return $this->getOutput($screenBuilder->show(), false);
	}
}
