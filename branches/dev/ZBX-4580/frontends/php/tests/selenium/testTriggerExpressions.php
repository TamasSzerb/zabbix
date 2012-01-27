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

class testTriggerExpression extends CWebTest {

	/**
	* @dataProvider testTriggerExpression_SimpleTestProvider
	*/
	public function testTriggerExpression_SimpleTest($where, $what, $expected) {
		$this->login('index.php');
		$this->open("tr_testexpr.php?expression={Zabbix%20server%3Avm.memory.size[total].last%280%29}%3C".$where);
		$this->assertTitle('Test');
		$this->input_type("//input[@type='text']", $what);

		$this->button_click("//input[@value='Test']");
		$this->button_click("//input[@id='test_expression']");
		$this->button_click("//input[@name='test_expression']");
		$this->ok($expected);
	}

	public function testTriggerExpression_SimpleTestProvider() {
		return array (
		array('10M', '20M', 'FALSE'),
		array('10T', '2G', 'TRUE'),
		array('10T', '2T', 'TRUE')
		);
	}
}
?>
