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

require_once 'PHPUnit/Extensions/SeleniumTestCase.php';

require_once(dirname(__FILE__).'/../../include/defines.inc.php');
require_once(dirname(__FILE__).'/../../conf/zabbix.conf.php');
require_once(dirname(__FILE__).'/../../include/copt.lib.php');
require_once(dirname(__FILE__).'/../../include/func.inc.php');
require_once(dirname(__FILE__).'/../../include/db.inc.php');

// Returns database data suitable for PHPUnit data provider functions
function DBdata($query)
{
	DBconnect($error);

	$objects=array();

	$result=DBselect($query);
	while($object=DBfetch($result))
	{
		$objects[]=array($object);
	}

	DBclose();
	return $objects;
}

class CTest extends PHPUnit_Extensions_SeleniumTestCase
{
	protected $captureScreenshotOnFailure = TRUE;
	protected $screenshotPath = '/home/hudson/public_html/screenshots';
	protected $screenshotUrl = 'http://hudson/~hudson/screenshots';

	// List of strings that should NOT appear on any page
	public $failIfExists = array (
		"pg_query",
		"Error in",
		"expects parameter",
		"Undefined index",
		"Undefined variable",
		"Undefined offset",
		"Fatal error",
		"Call to undefined method",
		"Invalid argument supplied",
		"Warning:",
		"PHP notice",
		"PHP warning",
		"Use of undefined",
		"You must login",
		"DEBUG INFO",
		"Cannot modify header"
	);

	// List of strings that SHOULD appear on every page
	public $failIfNotExists = array (
		"Help",
		"Get support",
		"Print",
		"Profile",
		"Logout",
		"Connected",
		"Admin"
	);

	protected function setUp()
	{
		global $DB;

		$this->setHost('localhost');
		$this->setBrowser('*firefox');
		if(strstr(PHPUNIT_URL,'http://'))
		{
			$this->setBrowserUrl(PHPUNIT_URL);
		} else {
			$this->setBrowserUrl('http://hudson/~hudson/'.PHPUNIT_URL.'/frontends/php/');
		}

/*		if(!DBConnect($error))
		{
			$this->assertTrue(FALSE,'Unable to connect to the database:'.$error);
			exit;
		}*/

		if(!isset($DB['DB'])) DBConnect($error);
	}

	protected function tearDown()
	{
		DBclose();
	}

	protected function DBsave_tables($tables)
	{
		global $DB;

		if(!is_array($tables))	$tables=array($tables);

		foreach($tables as $table)
		{
			switch($DB['TYPE']) {
			case 'MYSQL':
				DBexecute("drop table if exists ${table}_tmp");
				DBexecute("create table ${table}_tmp like $table");
				DBexecute("insert into ${table}_tmp select * from $table");
				break;
			default:
				DBexecute("drop table if exists ${table}_tmp");
				DBexecute("select * into temp table ${table}_tmp from $table");
			}
		}
	}

	protected function DBrestore_tables($tables)
	{
		global $DB;

		if(!is_array($tables))	$tables=array($tables);

		foreach($tables as $table)
		{
			DBexecute("delete from $table");
		}

		foreach($tables as $table)
		{
			DBexecute("insert into $table select * from ${table}_tmp");
			DBexecute("drop table ${table}_tmp");
		}
	}

	protected function DBhash($sql)
	{
		global $DB;

		$hash = '';

		$result=DBselect($sql);
		while($row = DBfetch($result))
		{
			foreach($row as $key => $value)
			{
				$hash = md5($hash.$value);
			}
		}

		return $hash;
	}

	protected function DBcount($sql)
	{
		global $DB;
		$cnt=0;

		$result=DBselect($sql);
		while($row = DBfetch($result))
		{
			$cnt++;
		}

		return $cnt;
	}

	public function login($url = NULL)
	{
		$this->open('index.php');
		$this->wait();
		// Login if not logged in already
		if($this->isElementPresent('link=Login'))
		{
			$this->click('link=Login');
			$this->wait();
			$this->input_type('name','Admin');
			$this->input_type('password','zabbix');
			$this->click('enter');
			$this->wait();
		}
		if(isset($url))
		{
			$this->open($url);
			$this->wait();
		}
		$this->ok('Admin');
		$this->nok('Login name or password is incorrect');
	}

	public function logout()
	{
		$this->click('link=Logout');
		$this->wait();
	}

	public function checkFatalErrors()
	{
		foreach($this->failIfExists as $str)
		{
			$this->assertTextNotPresent($str,"Chuck Norris: I do not expect string '$str' here.");
		}
	}

	public function ok($strings)
	{
		if(!is_array($strings))	$strings=array($strings);
		foreach($strings as $string) $this->assertTextPresent($string,"Chuck Norris: I expect string '$string' here");
	}

	public function nok($strings)
	{
		if(!is_array($strings))	$strings=array($strings);
		foreach($strings as $string) $this->assertTextNotPresent($string,"Chuck Norris: I do not expect string '$string' here");
	}

	public function button_click($a)
	{
		$this->click($a);
	}

	public function checkbox_select($a)
	{
		if(!$this->isChecked($a)) $this->click($a);
	}

	public function checkbox_unselect($a)
	{
		if($this->isChecked($a)) $this->click($a);
	}

	public function input_type($id,$str)
	{
		$this->type($id,$str);
	}

	public function dropdown_select($id,$str)
	{
		$this->assertSelectHasOption($id,$str);
		$this->select($id,$str);
	}

	public function wait()
	{
		$this->waitForPageToLoad();
		$this->checkFatalErrors();
	}
}
?>
