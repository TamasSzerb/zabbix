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
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/
?>
<?php
require_once(dirname(__FILE__) . '/../include/class.cwebtest.php');

class testPageHistory extends CWebTest{
	// Returns all enabled items that belong to enabled hosts
	public static function allEnabledItems(){
		return DBdata('select * from items left join hosts on hosts.hostid=items.hostid where hosts.status='.HOST_STATUS_MONITORED.' and items.status='.ITEM_STATUS_ACTIVE);
	}

	/**
	* @dataProvider allEnabledItems
	*/

	public function testPageItems_SimpleTest($item){

		// should switch to graph for numeric items, should check filter for history & text items
		// also different header for log items (different for eventlog items ?)
		$itemid=$item['itemid'];
		$this->login("history.php?action=showvalues&itemid=$itemid");
		$this->assertTitle('History');
		// Header
		$this->ok(array('Timestamp', 'Value'));
		$this->dropdown_select_wait('action','500 latest values');
		$this->assertTitle('History');
		$this->button_click('plaintext');
		$this->wait();

		// there surely is a better way to get out of the plaintext page than just clicking 'back'...
		$this->goBack();
		$this->wait();
		$this->dropdown_select_wait('action','Values');
		$this->assertTitle('History');
		$this->button_click('plaintext');
		$this->wait();

	}
}
?>
