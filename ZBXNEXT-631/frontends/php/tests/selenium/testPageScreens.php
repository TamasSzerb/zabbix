<?php
/*
** ZABBIX
** Copyright (C) 2000-2011 SIA Zabbix
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


class testPageScreens extends CWebTest
{
	// Returns all screens
	public static function allScreens()
	{
		return DBdata("select * from screens order by screenid");
	}

	/**
	* @dataProvider allScreens
	*/
	public function testPageScreens_SimpleTest($screen)
	{
		$this->login('screenconf.php');
		$this->assertTitle('Configuration of screens');

		$this->ok('CONFIGURATION OF SCREENS');
		$this->ok('SCREENS');
		$this->ok('Displaying');
		$this->nok('Displaying 0');
		// Header
		$this->ok(array('Name','Dimension (cols x rows)','Screens'));
		// Data
		$this->ok(array($screen['name']));
		$this->dropdown_select('go','Export selected');
		$this->dropdown_select('go','Delete selected');
	}

	/**
	* @dataProvider allScreens
	*/
	public function testPageScreens_SimpleEdit($screen)
	{
		$screenid=$screen['screenid'];
		$name=$screen['name'];

		$this->login('screenconf.php');
		$this->assertTitle('Configuration of screens');
		$this->click("link=$name");
		$this->wait();
		$this->assertTitle('Configuration of screens');
		$this->ok("$name");
		$this->ok('Change');
		$this->ok('CONFIGURATION OF SCREEN');
	}

	/**
	* @dataProvider allScreens
	*/
	public function testPageScreens_SimpleUpdate($screen)
	{
		$screenid=$screen['screenid'];
		$name=$screen['name'];

		$sql1="select * from screens where screenid=$screenid order by screenid";
		$oldHashScreen=DBhash($sql1);
		$sql2="select * from screens_items where screenid=$screenid order by screenitemid";
		$oldHashScreenItems=DBhash($sql2);

		DBsave_tables(array('screens','screens_items','slides'));

		$this->login('screenconf.php');
		$this->assertTitle('Configuration of screens');
		$this->href_click("?form=update&screenid=$screenid&sid=");
		$this->wait();

		$this->assertTitle('Configuration of screens');
		$this->ok($name);
		$this->ok('Screens');
		$this->ok('Name');
		$this->ok('Columns');
		$this->ok('Rows');

		$this->button_click('save');
		$this->wait();

		$this->assertTitle('Configuration of screens');
		$this->ok('Screen updated');

		$this->assertEquals($oldHashScreen,DBhash($sql1));
		$this->assertEquals($oldHashScreenItems,DBhash($sql2));

		DBrestore_tables(array('screens','screens_items','slides'));
	}

	public function testPageScreens_Create()
	{
		$this->login('screenconf.php');
		$this->assertTitle('Configuration of screens');
		$this->button_click('form');
		$this->wait();

		$this->assertTitle('Configuration of screens');
		$this->ok('Screens');
		$this->ok('Name');
		$this->ok('Columns');
		$this->ok('Rows');

		$this->button_click('cancel');
		$this->wait();

		$this->assertTitle('Configuration of screens');
		$this->nok('Columns');
	}

	public function testPageScreens_Import()
	{
// TODO
		$this->markTestIncomplete();
	}

	public function testPageScreens_MassExportAll()
	{
// TODO
		$this->markTestIncomplete();
	}

	/**
	* @dataProvider allScreens
	*/
	public function testPageScreens_MassExport($action)
	{
// TODO
		$this->markTestIncomplete();
	}

	public function testPageScreens_MassDeleteAll()
	{
// TODO
		$this->markTestIncomplete();
	}

	/**
	* @dataProvider allScreens
	*/
	public function testPageScreens_MassDelete($screen)
	{
		$screenid=$screen['screenid'];
		$name=$screen['name'];

		$this->chooseOkOnNextConfirmation();

		DBsave_tables(array('screens','screens_items','slides'));

		$this->login('screenconf.php');
		$this->assertTitle('Configuration of screens');
		$this->checkbox_select("screens[$screenid]");
		$this->dropdown_select('go','Delete selected');
		$this->button_click('goButton');
		$this->wait();

		$this->getConfirmation();

		$this->assertTitle('Configuration of screens');
		$this->ok('Screen deleted');
		$this->ok('CONFIGURATION OF SCREENS');

		$sql="select * from screens where screenid=$screenid";
		$this->assertEquals(0,DBcount($sql));
		$sql="select * from screens_items where screenid=$screenid";
		$this->assertEquals(0,DBcount($sql));
		$sql="select * from slides where screenid=$screenid";
		$this->assertEquals(0,DBcount($sql));

		DBrestore_tables(array('screens','screens_items','slides'));
	}

	public function testPageScreens_Sorting()
	{
// TODO
		$this->markTestIncomplete();
	}
}
?>
