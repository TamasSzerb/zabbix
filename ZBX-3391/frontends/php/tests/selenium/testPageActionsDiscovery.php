<?php
/*
** ZABBIX
** Copyright (C) 2000-2010 SIA Zabbix
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
require_once(dirname(__FILE__).'/class.ctest.php');


class testPageActionsDiscovery extends CTest
{
	// Returns all trigger actions
	public static function allActions()
	{
		return DBdata("select * from actions where eventsource=".EVENT_SOURCE_DISCOVERY." order by actionid");
	}

	/**
	* @dataProvider allActions
	*/
	public function testPageActionsDiscovery_SimpleTest($action)
	{
		$name=$action['name'];

		$this->login('actionconf.php?eventsource='.EVENT_SOURCE_DISCOVERY);
		$this->assertTitle('Configuration of actions');

// eventsource is used for a hidden field, so it does not work. See above: ?eventsource=0 is used instead
//		$this->dropdown_select('eventsource','Discovery');

		$this->ok('CONFIGURATION OF ACTIONS');
		$this->ok('Event source');
		$this->ok('Displaying');
		// Header
		$this->ok(array('Name','Conditions','Operations','Status'));
		// Data
		$this->ok(array($action['name']));
		$this->dropdown_select('go','Enable selected');
		$this->dropdown_select('go','Disable selected');
		$this->dropdown_select('go','Delete selected');
	}

	/**
	* @dataProvider allActions
	*/
	public function testPageActionsDiscovery_SimpleUpdate($action)
	{
		$actionid=$action['actionid'];
		$name=$action['name'];

		$sql1="select * from actions where actionid=$actionid order by actionid";
		$oldHashAction=$this->DBhash($sql1);
		$sql2="select * from operations where actionid=$actionid order by operationid";
		$oldHashOperations=$this->DBhash($sql2);
		$sql3="select * from conditions where actionid=$actionid order by conditionid";
		$oldHashConditions=$this->DBhash($sql3);

		$this->login('actionconf.php?eventsource='.EVENT_SOURCE_DISCOVERY);
		$this->assertTitle('Configuration of actions');
		$this->click("link=$name");
		$this->wait();
		$this->button_click('save');
		$this->wait();
		$this->assertTitle('Configuration of actions');
		$this->ok('Action updated');
		$this->ok("$name");
		$this->ok('CONFIGURATION OF ACTIONS');

		$this->assertEquals($oldHashAction,$this->DBhash($sql1),"Chuck Norris: Action update changed data in table 'actions'.");
		$this->assertEquals($oldHashOperations,$this->DBhash($sql2),"Chuck Norris: Action update changed data in table 'operations'");
		$this->assertEquals($oldHashConditions,$this->DBhash($sql3),"Chuck Norris: Action update changed data in table 'conditions'");
	}

	/**
	* @dataProvider allActions
	*/
	public function testPageActionsDiscovery_SingleEnable($action)
	{
// TODO
		$this->markTestIncomplete();
	}

	public function testPageActionsDiscovery_Create()
	{
// TODO
		$this->markTestIncomplete();
	}


	/**
	* @dataProvider allActions
	*/
	public function testPageActionsDiscovery_MassDisable($action)
	{
		$actionid=$action['actionid'];
		$name=$action['name'];

		$this->chooseOkOnNextConfirmation();

		$this->login('actionconf.php?eventsource='.EVENT_SOURCE_DISCOVERY);
		$this->assertTitle('Configuration of actions');
		$this->checkbox_select("g_actionid[$actionid]");
		$this->dropdown_select('go','Disable selected');
		$this->button_click('goButton');
		$this->wait();

		$this->getConfirmation();

		$this->assertTitle('Configuration of actions');
		$this->ok('Status updated');
		$this->ok('Disabled');
		$this->ok('CONFIGURATION OF ACTIONS');

		$sql="select * from actions where actionid=$actionid and status=1";
		$this->assertEquals(1,$this->DBcount($sql));
	}

	/**
	* @dataProvider allActions
	*/
	public function testPageActionsDiscovery_MassEnable($action)
	{
		$actionid=$action['actionid'];
		$name=$action['name'];

		$this->chooseOkOnNextConfirmation();

		$this->login('actionconf.php?eventsource='.EVENT_SOURCE_DISCOVERY);
		$this->assertTitle('Configuration of actions');
		$this->checkbox_select("g_actionid[$actionid]");
		$this->dropdown_select('go','Enable selected');
		$this->button_click('goButton');
		$this->wait();

		$this->getConfirmation();

		$this->assertTitle('Configuration of actions');
		$this->ok('Status updated');
		$this->ok('Enabled');
		$this->ok('CONFIGURATION OF ACTIONS');

		$sql="select * from actions where actionid=$actionid and status=0";
		$this->assertEquals(1,$this->DBcount($sql));
	}

	/**
	* @dataProvider allActions
	*/
	public function testPageActionsDiscovery_MassDelete($action)
	{
		$actionid=$action['actionid'];
		$name=$action['name'];

		$this->chooseOkOnNextConfirmation();

		$this->DBsave_tables(array('actions','operations','conditions'));

		$this->login('actionconf.php?eventsource='.EVENT_SOURCE_DISCOVERY);
		$this->assertTitle('Configuration of actions');
		$this->checkbox_select("g_actionid[$actionid]");
		$this->dropdown_select('go','Delete selected');
		$this->button_click('goButton');
		$this->wait();

		$this->getConfirmation();

		$this->assertTitle('Configuration of actions');
		$this->ok('Action deleted');
		$this->ok('CONFIGURATION OF ACTIONS');

		$sql="select * from actions where actionid=$actionid";
		$this->assertEquals(0,$this->DBcount($sql));
		$sql="select * from operations where actionid=$actionid";
		$this->assertEquals(0,$this->DBcount($sql));
		$sql="select * from conditions where actionid=$actionid";
		$this->assertEquals(0,$this->DBcount($sql));

		$this->DBrestore_tables(array('actions','operations','conditions'));
	}
}
?>
