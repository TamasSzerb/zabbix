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
require_once 'PHPUnit/Extensions/SeleniumTestCase.php';

require_once(dirname(__FILE__).'/../../include/defines.inc.php');
require_once(dirname(__FILE__).'/dbfunc.php');

class CWebTest extends PHPUnit_Extensions_SeleniumTestCase
{
	protected $captureScreenshotOnFailure = TRUE;
	protected $screenshotPath = '/home/hudson/public_html/screenshots';
	protected $screenshotUrl = 'http://192.168.3.32/~hudson/screenshots';

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
		"Missing argument",
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

		$this->setHost(PHPUNIT_FRONTEND_HOST);
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

	public function login($url = NULL)
	{
		$this->open('index.php');
		$this->wait();
		// Login if not logged in already
		if($this->isElementPresent('id=password'))
		{
			$this->input_type('name',PHPUNIT_LOGIN_NAME);
			$this->input_type('password',PHPUNIT_LOGIN_PWD);
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
		foreach($strings as $string)
			$this->assertTextPresent($string,"Chuck Norris: I expect string '$string' here");
	}

	public function nok($strings)
	{
		if(!is_array($strings))	$strings=array($strings);
		foreach($strings as $string)
			$this->assertTextNotPresent($string,"Chuck Norris: I do not expect string '$string' here");
	}

	public function button_click($a)
	{
		$this->click($a);
	}

	public function href_click($a)
	{
		$this->click("xpath=//a[contains(@href,'$a')]");
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

	public function dropdown_select_wait($id,$str)
	{
		$selected = $this->getSelectedLabel($id);
		$this->dropdown_select($id, $str);
		// Wait only if drop down selection was changed
		if($selected != $str)	$this->wait();
	}

	public function wait()
	{
		$this->waitForPageToLoad();
		$this->checkFatalErrors();
	}

	public function tab_switch($tab){
		// switches tab by receiving tab title text
		$this->click("xpath=//div[@id='tabs']/ul/li/a[text()='$tab']");
		$this->waitForElementPresent("xpath=//li[contains(@class, 'ui-tabs-selected')]/a[text()='$tab']");
		$this->checkFatalErrors();
	}

	public function template_unlink_and_clear($template){
		// WARNING: not tested yet
		// clicks button named "Unlink and clear" next to template named $template
		$this->click("xpath=//div[text()='$template']/../div[@class='dd']/input[@value='Unlink']/../input[@value='Unlink and clear']");
	}

	public function templateLink($host,$template){
		// $template = "Template_Linux";
		// $host = "Zabbix server";
		$sql = "select hostid from hosts where host='".$host."' and status in (".HOST_STATUS_MONITORED.",".HOST_STATUS_NOT_MONITORED.")";
		$this->assertEquals(1,DBcount($sql),"Chuck Norris: No such host:$host");
		$row = DBfetch(DBselect($sql));
		$hostid = $row['hostid'];

		// using template by name for now only. id will be needed for linkage tests etc
		// $sql = "select hostid from hosts where host='".$template."'";
		// $this->assertEquals(1,DBcount($sql),"Chuck Norris: No such template:$template");
		// $row = DBfetch(DBselect($sql));
		// $templateid = $row['hostid'];

		// Link a template to a host from host properties page
		$this->login('hosts.php');
		$this->dropdown_select_wait('groupid','all');
		$this->click("link=$host");
		$this->wait();
		$this->tab_switch("Templates");
		$this->nok("$template");

		// adds template $template to the list of linked templates
		// for now, ignores the fact that template might be already linked
		// $this->button_click('add');
		// the above does not seem to work, thus this ugly method has to be used - at least until buttons get unique names...
		$this->click("//input[@id='add' and @name='add' and @value='Add' and @type='button' and contains(@onclick, 'return PopUp')]");
		// zbx_popup is the default opened window id if none is passed
		$this->waitForPopUp('zbx_popup',6000);
		$this->selectWindow('zbx_popup');
		$this->checkFatalErrors();
		$this->dropdown_select_wait('groupid','Templates');
		$this->check("//input[@value='$template' and @type='checkbox']");
		$this->button_click('select');
		$this->selectWindow();
		$this->wait();
		$this->button_click('save');
		$this->wait();
		$this->assertTitle('Hosts');
		$this->ok('Host updated');
		// no entities should be deleted, they all should be updated
		$this->nok('deleted');
		$this->nok('created');

		// linking finished, checks proceed
		// should check that items, triggers, graphs and applications exist on the host and are linked to the template
		// should do that by looking in the db
		// currently doing something very brutal - just looking whether Template_Linux is present on entity pages
		$this->href_click("items.php?filter_set=1&hostid=$hostid&sid=");
		$this->wait();
		$this->ok("$template:");
		// using "host navigation bar" at the top of entity list
		$this->href_click("triggers.php?hostid=$hostid&sid=");
		$this->wait();
		$this->ok("$template:");
		// default data.sql has a problem - graphs are not present in the template
		// $this->href_click("graphs.php?hostid=$hostid&sid=");
		// $this->wait();
		// $this->ok("$template:");
		$this->href_click("applications.php?hostid=$hostid&sid=");
		$this->wait();
		$this->ok("$template:");

		// tests that items that should have interfaceid don't have it set to NULL
		// checks all items on enabled and disabled hosts (types 0 and 1) except:
		// ITEM_TYPE_TRAPPER, ITEM_TYPE_INTERNAL, ITEM_TYPE_ZABBIX_ACTIVE, ITEM_TYPE_AGGREGATE, ITEM_TYPE_CALCULATED, ITEM_TYPE_HTTPTEST
		// if any found, something's wrong
		$this->assertEquals(0,DBcount("select itemid from items left join hosts on items.hostid=hosts.hostid where hosts.status in (0,1) and interfaceid is NULL and type not in (2,5,7,8,9,15);"),"Chuck Norris: There are items with interfaceid NULL not of types 2, 5, 7, 8, 9, 15");

	}
}
?>
