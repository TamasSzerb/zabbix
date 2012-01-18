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
require_once(dirname(__FILE__).'/../include/class.cwebtest.php');

class testPageNodes extends CWebTest {
	// Returns all nodes
	public static function allNodes() {
		return DBdata("select * from nodes order by nodeid");
	}

	public function testPageNodes_StandaloneSetup() {
		$this->login('nodes.php');
		$this->assertTitle('Nodes');
		$this->ok('DM');
		$this->ok('CONFIGURATION OF NODES');
		if (0 == DBcount("select * from nodes order by nodeid")) {
			$this->ok('Your setup is not configured for distributed monitoring');
		}
	}

	/**
	* @dataProvider allNodes
	*/
	public function testPageNodes_CheckLayout($node) {
		// TODO
		$this->markTestIncomplete();
/*		$this->login('proxies.php');
		$this->assertTitle('Proxies');
		$this->ok('CONFIGURATION OF PROXIES');
		$this->ok('Displaying');
		$this->nok('Displaying 0');
		// Header
		$this->ok(array('Name', 'Mode', 'Last seen (age)', 'Host count', 'Item count', 'Required performance (vps)', 'Hosts'));
		// Data
		$this->ok(array($proxy['host']));
		$this->dropdown_select('go', 'Activate selected');
		$this->dropdown_select('go', 'Disable selected');
		$this->dropdown_select('go', 'Delete selected');*/
	}

	/**
	* @dataProvider allNodes
	*/
	public function testPageNodes_SimpleUpdate($node) {
		// TODO
		$this->markTestIncomplete();
	}

	public function testPageNodes_MassDeleteAll() {
// TODO
		$this->markTestIncomplete();
	}

	/**
	* @dataProvider allNodes
	*/
	public function testPageNodes_MassDelete($node) {
// TODO
		$this->markTestIncomplete();
	}

	public function testPageNodes_Sorting() {
// TODO
		$this->markTestIncomplete();
	}
}
?>
