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
require_once(dirname(__FILE__).'/class_cItemKey.php');
require_once(dirname(__FILE__).'/function_DBcommit.php');
require_once(dirname(__FILE__).'/function_DBcondition.php');
require_once(dirname(__FILE__).'/function_DBconnect.php');
require_once(dirname(__FILE__).'/function_DBclose.php');
require_once(dirname(__FILE__).'/function_DBend.php');
require_once(dirname(__FILE__).'/function_DBexecute.php');
require_once(dirname(__FILE__).'/function_DBfetch.php');
require_once(dirname(__FILE__).'/function_DBid2nodeid.php');
require_once(dirname(__FILE__).'/function_DBin_node.php');
require_once(dirname(__FILE__).'/function_DBloadfile.php');
require_once(dirname(__FILE__).'/function_DBrollback.php');
require_once(dirname(__FILE__).'/function_DBselect.php');
require_once(dirname(__FILE__).'/function_DBstart.php');

class GeneralTests
{
	public static function suite()
	{
		$suite = new PHPUnit_Framework_TestSuite('general');

		$suite->addTestSuite('class_cItemKey');
		$suite->addTestSuite('function_DBcommit');
		$suite->addTestSuite('function_DBcondition');
		$suite->addTestSuite('function_DBconnect');
		$suite->addTestSuite('function_DBclose');
		$suite->addTestSuite('function_DBend');
		$suite->addTestSuite('function_DBexecute');
		$suite->addTestSuite('function_DBfetch');
		$suite->addTestSuite('function_DBid2nodeid');
		$suite->addTestSuite('function_DBin_node');
		$suite->addTestSuite('function_DBloadfile');
		$suite->addTestSuite('function_DBrollback');
		$suite->addTestSuite('function_DBselect');
		$suite->addTestSuite('function_DBstart');

		return $suite;
	}
}
?>
