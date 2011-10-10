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
class CButtonCancel extends CButton {
	public function __construct($vars = null, $action = null, $class = null) {
		parent::__construct('cancel', _('Cancel'), $action, $class);
		if (is_null($action)) {
			$this->setVars($vars);
		}
	}

	public function setVars($value = null) {
		$url = '?cancel=1';
		if (!is_null($value)) {
			$url.= $value;
		}
		$uri = new Curl($url);
		$url = $uri->getUrl();
		return $this->setAttribute('onclick', "javascript: return redirect('".$url."');");
	}
}
?>
