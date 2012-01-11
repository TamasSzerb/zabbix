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

class testFormAdministrationGeneralValuemap extends CWebTest {
		private $valuemap1 = '1valuemap1';
		private $valuemap2 = '2valuemap2';
		private $valuemapWithMultipleMappings = '1valuemapWithMultipleMappings1';

	public function testFormAdministrationGeneralValuemap_Layout() {

		$this->login('config.php');
		$this->assertElementPresent('configDropDown');
		$this->dropdown_select_wait('configDropDown', 'Value mapping');
		$this->assertTitle('Configuration of Zabbix');
		$this->ok('CONFIGURATION OF ZABBIX');
		$this->ok('Value mapping');
		$this->assertElementPresent('form');
		$this->button_click('form');
		$this->wait();
		$this->ok('Name');
		$this->assertElementPresent('mapname');
		$this->assertAttribute("//input[@id='mapname']/@maxlength", '64');
		$this->assertAttribute("//input[@id='mapname']/@size", '40');

		$this->ok(array('Mapping', 'New mapping'));
		$this->assertElementPresent('add_value');
		$this->assertAttribute("//input[@id='add_value']/@maxlength", '64');
		$this->assertAttribute("//input[@id='add_value']/@size", '20');
		$this->assertElementPresent('add_newvalue');
		$this->assertAttribute("//input[@id='add_newvalue']/@maxlength", '64');
		$this->assertAttribute("//input[@id='add_newvalue']/@size", '20');
		$this->assertElementPresent('add_map');
		$this->assertAttribute("//input[@id='save']/@aria-disabled", 'true');
	}

	public static function dataCreate() {

		return array(
			array('1valuemap1', '1', 'one'),
			array('2valuemap2', '2', 'two')
		);
	}

	public static function dataUpdate() {

		return array(
			array('1valuemap1', '1valuemap_updated'),
			array('2valuemap2', '2valuemap_updated')
		);
	}

	/**
	* @dataProvider dataCreate
	*/
	public function testFormAdministrationGeneralValuemap_AddValueMap($mapname, $value, $newvalue) {

		$this->login('config.php');
		$this->dropdown_select_wait('configDropDown', 'Value mapping');
		$this->assertTitle('Configuration of Zabbix');
		$this->ok('CONFIGURATION OF ZABBIX');
		$this->ok('Value mapping');
		$this->button_click('form');
		$this->wait();
		$this->ok(array('Name', 'Mapping', 'New mapping'));

		$this->assertElementPresent('add_map');
		$this->assertElementPresent('save');
		$this->assertElementPresent('cancel');

		$this->input_type('mapname', $mapname);
		$this->input_type('add_value', $value);
		$this->input_type('add_newvalue', $newvalue);
		$this->click("id=add_map");
		$this->wait();

		$this->click("id=save");
		$this->wait();
		$this->ok('Value map added');
		$this->ok('CONFIGURATION OF ZABBIX');
		$this->ok('Value mapping');
		$this->ok('Name');
		$this->ok('Value map');

		// checking that valuemap with such name has been created in the DB
		$sqlValuemap = 'SELECT * FROM valuemaps WHERE name ='.zbx_dbstr($mapname);
		$this->assertEquals(1, DBcount($sqlValuemap), 'Chuck Norris: Value map with such name has not been created in the DB');
		$valuemap = DBfetch(DBselect($sqlValuemap));

		// checking that mappings for this valuemap has been created in the DB
		$sqlMappingid = 'SELECT mappingid FROM mappings WHERE valuemapid=\''.$valuemap['valuemapid'].'\'';
		$result2 = DBcount($sqlMappingid);

		$sqlMappings = 'SELECT count(mappingid) FROM mappings WHERE valuemapid=\''.$valuemap['valuemapid'].'\'';
		$mappings_amount = DBcount($sqlMappings);
		$this->assertEquals($result2, $mappings_amount, 'Chuck Norris: Incorrect amount of mappings for this value map"');

	}

	public function testFormAdministrationGeneralValuemap_AddValueMapWithMultipleMappings() {

		$value1 = '1';
		$newvalue1 = 'one';
		$value2 = '2';
		$newvalue2 = 'two';
		$value3 = '3';
		$newvalue3 = 'three';

		$this->login('config.php');
		$this->dropdown_select_wait('configDropDown','Value mapping');
		$this->assertTitle('Configuration of Zabbix');
		$this->ok('CONFIGURATION OF ZABBIX');
		$this->ok('Value mapping');
		$this->button_click('form');
		$this->wait();
		$this->ok(array('Name', 'Mapping', 'New mapping'));

		$this->input_type('mapname', $this->valuemapWithMultipleMappings);
		$this->input_type('add_value', $value1);
		$this->input_type('add_newvalue', $newvalue1);
		$this->click("id=add_map");
		$this->wait();

		$this->input_type('add_value', $value2);
		$this->input_type('add_newvalue', $newvalue2);
		$this->click("id=add_map");
		$this->wait();

		$this->input_type('add_value', $value3);
		$this->input_type('add_newvalue', $newvalue3);
		$this->click("id=add_map");
		$this->wait();

		$this->click("id=save");
		$this->wait();
		$this->ok('Value map added');
		$this->ok('CONFIGURATION OF ZABBIX');
		$this->ok('Value mapping');
		$this->ok('Name');
		$this->ok('Value map');
	}

	/**
	* @dataProvider dataUpdate
	*/
	public function testFormAdministrationGeneralValuemap_UpdateValueMap($oldVmName, $newVmName) {

		$this->login('config.php');
		$this->dropdown_select_wait('configDropDown', 'Value mapping');
		$this->assertTitle('Configuration of Zabbix');
		$this->ok('CONFIGURATION OF ZABBIX');
		$this->ok('Value mapping');
		$this->click('link='.$oldVmName);
		$this->wait();
		$this->input_type("mapname", $newVmName);
		$this->click("save");
		$this->wait();

		$sql = 'SELECT * FROM valuemaps WHERE name=\''.$newVmName.'\'';
		$this->assertEquals(1, DBcount($sql), 'Chuck Norris: Value map name has not been updated in the DB');
	}

	public function testFormAdministrationGeneralValuemap_IncorrectValueMap() {

		$this->login('config.php');
		$this->dropdown_select_wait('configDropDown', 'Value mapping');
		$this->assertTitle('Configuration of Zabbix');
		$this->ok('CONFIGURATION OF ZABBIX');
		$this->ok('Value mapping');
		$this->button_click('form');
		$this->wait();
		$this->ok('Name');
		$this->ok('Mapping');
		$this->ok('New mapping');
		$this->input_type('mapname', 'incorrect_valuemap');
		$this->input_type('add_value', 'abc');
		$this->input_type('add_newvalue', '123');
		$this->click("add_map");
		$this->wait();
		$this->ok('ERROR: Cannot add value map');
		$this->ok('Value maps are used to create a mapping between numeric values and string representations');

		// trying to create already existing valuemap
		$this->input_type('mapname', $this->valuemapWithMultipleMappings);
		$this->input_type('add_value', '6');
		$this->input_type('add_newvalue', 'six');
		$this->click("add_map");
		$this->wait();
		$this->click("save");
		$this->wait();
		$this->ok(array('ERROR: Cannot add or update value map.', 'Map with name', 'already exists'));
	}

	/**
	* @dataProvider dataUpdate
	*/
	public function testFormAdministrationGeneralValuemap_DeleteValueMap($oldVmName, $newVmName) {

		$this->login('config.php');
		$this->dropdown_select_wait('configDropDown','Value mapping');
		$this->assertTitle('Configuration of Zabbix');
		$this->ok('CONFIGURATION OF ZABBIX');
		$this->ok('Value mapping');
		$this->ok('Name');
		$this->ok('Value map');
		$this->click('link='.$newVmName);
		$this->wait();
		$this->chooseOkOnNextConfirmation();
		$this->click("delete");
		$this->waitForConfirmation();
		$this->wait();
		$this->ok('Value map deleted');

		$sql = 'SELECT * FROM valuemaps WHERE name=\''.$newVmName.'\'';
		$this->assertEquals(0, DBcount($sql), 'Chuck Norris: Value map with such name has not been deleted from the DB');

	}

	public function testFormAdministrationGeneralValuemap_CancelDeleteValueMap() {

		$this->login('config.php');
		$this->dropdown_select_wait('configDropDown','Value mapping');
		$this->assertTitle('Configuration of Zabbix');
		$this->ok('CONFIGURATION OF ZABBIX');
		$this->ok('Value mapping');
		$this->ok('Name','Value map');
		$this->click('link='.$this->valuemapWithMultipleMappings);
		$this->wait();
		$this->button_click("cancel");

		// checking that valuemap was not deleted after clicking on the "Cancel" button in the confirm dialog box
		$sql = 'SELECT * FROM valuemaps WHERE name=\''.$this->valuemapWithMultipleMappings.'\'';
		$this->assertEquals(1, DBcount($sql), 'Chuck Norris: Value map with such name has been deleted from the DB');
	}

	public function testFormAdministrationGeneralValuemap_DeleteRemainingValueMaps() {

		// finally deleting remaining value maps
		$this->login('config.php');
		$this->dropdown_select_wait('configDropDown','Value mapping');
		$this->assertTitle('Configuration of Zabbix');
		$this->ok('CONFIGURATION OF ZABBIX');
		$this->ok('Value mapping');
		$this->ok('Name','Value map');
		$this->click('link='.$this->valuemapWithMultipleMappings);
		$this->wait();
		$this->chooseOkOnNextConfirmation();
		$this->click("delete");
		$this->waitForConfirmation();
		$this->wait();
		$this->ok('Value map deleted');

		$sql = 'SELECT * FROM valuemaps WHERE name=\''.$this->valuemapWithMultipleMappings.'\'';
		$this->assertEquals(0, DBcount($sql), 'Chuck Norris: Value map with such name has not been deleted from the DB');

	}
}
?>
