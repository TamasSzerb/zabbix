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

class testFormHost extends CTest
{
	public $host = "Text host";

	public function testFormHost_Create()
	{
		$this->login('hosts.php');
		$this->dropdown_select('groupid','Zabbix servers');
		$this->button_click('form');
		$this->wait();
		$this->input_type('host',$this->host);
		$this->button_click('save');
		$this->wait();
		$this->ok('Host added');
		$this->logout();
	}

	public function testFormHost_SimpleUpdate()
	{
		$this->login('hosts.php');
		$this->dropdown_select('groupid','Zabbix servers');
		$this->click('link=Zabbix server');
		$this->wait();
		$this->button_click('save');
		$this->wait();
		$this->ok('Host updated');
		$this->logout();
	}

	public function testFormHost_UpdateHostName()
	{
		// Update Host
		$this->login('hosts.php');
		$this->dropdown_select('groupid','all');
		$this->click('link='.$this->host);
		$this->wait();
		$this->input_type('host',$this->host.'2');
		$this->button_click('save');
		$this->wait();
		$this->ok('Host updated');
		$this->logout();
	}

	public function testFormHost_Delete()
	{
		$this->chooseOkOnNextConfirmation();

		// Delete Host
		$this->login('hosts.php');
		$this->dropdown_select('groupid','all');
		$this->click('link='.$this->host.'2');
		$this->wait();
		$this->button_click('delete');
		$this->wait();
		$this->getConfirmation();
		$this->ok('Host deleted');
		$this->logout();
	}

	public function testFormHost_CloneHost()
	{
		// Update Host
		$this->login('hosts.php');
		$this->dropdown_select('groupid','all');
		$this->click('link=Zabbix server');
		$this->wait();
		$this->button_click('clone');
		$this->wait();
		$this->input_type('host',$this->host.'2');
		$this->button_click('save');
		$this->wait();
		$this->ok('Host added');
		$this->logout();
	}

	public function testFormHost_DeleteClonedHost()
	{
		$this->chooseOkOnNextConfirmation();

		// Delete Host
		$this->login('hosts.php');
		$this->dropdown_select('groupid','all');
		$this->click('link='.$this->host.'2');
		$this->wait();
		$this->button_click('delete');
		$this->wait();
		$this->getConfirmation();
		$this->ok('Host deleted');
		$this->logout();
	}
}
?>
