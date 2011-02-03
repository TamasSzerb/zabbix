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

class testPageQueueOverview extends CWebTest
{
	public function testPageQueueOverview_SimpleTest()
	{
		$this->login('queue.php?config=0');
		$this->assertTitle('Queue \[refreshed every 30 sec\]');
		$this->ok('Queue');
		$this->ok('QUEUE OF ITEMS TO BE UPDATED');
		// Header
		$this->ok(array('Items','5 seconds','10 seconds','30 seconds','1 minute','5 minutes','More than 10 minutes'));
		// Data
		$this->ok(array('Zabbix agent','Zabbix agent (active)','SNMPv2 agent','SNMPv2 agent','SNMPv3 agent','IPMI agent','SSH agent','TELNET agent','Simple check','Zabbix internal','Zabbix aggregate','External check','Calculated'));
	}

	public function testPageQeueOverview_VerifyDisplayedNumbers()
	{
// TODO
		$this->markTestIncomplete();
	}
}
?>
