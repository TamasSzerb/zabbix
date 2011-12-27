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

class testPageStatusOfTriggers extends CWebTest
{
	public function testPageStatusOfTriggers_CheckLayout()
	{
		$this->login('tr_status.php');
		$this->assertTitle('Status of triggers \[refreshed every 30 sec\]');
		$this->ok('STATUS OF TRIGGERS');
		$this->ok('Triggers');
		$this->ok('Displaying');
		$this->ok(array('Group','Host'));
		$this->ok(array('Severity','Status','Info','Last change','Age','Acknowledged','Host','Name','Comments'));
	}

// Check that no real host or template names displayed
	public function testPageStatusOfTriggers_NoHostNames()
	{
		$this->login('tr_status.php');
		$this->assertTitle('Status of triggers \[refreshed every 30 sec\]');
		$this->checkNoRealHostnames();
	}
}
?>
