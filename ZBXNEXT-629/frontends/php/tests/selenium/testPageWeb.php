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
require_once(dirname(__FILE__).'/../include/class.cwebtest.php');

class testPageWeb extends CWebTest
{
	public function testPageWeb_SimpleTest()
	{
		$this->login('httpmon.php');
		$this->assertTitle('Status of Web monitoring');
		$this->ok('STATUS OF WEB MONITORING');
		$this->ok('WEB CHECKS');
		$this->ok(array('Group','Host'));
		$this->ok(array('Host','Name','Number of steps','State','Last check','Status'));
	}

// Check that no real host or template names displayed
	public function testPageWeb_NoHostNames()
	{
		$this->login('httpmon.php');
		$this->assertTitle('Status of Web monitoring');
		$this->checkNoRealHostnames();
	}
}
?>
