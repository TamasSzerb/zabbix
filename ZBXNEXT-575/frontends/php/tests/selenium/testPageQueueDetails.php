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

class testPageQueueDetails extends CWebTest
{
	public function testPageQueueDetails_SimpleTest()
	{
		$this->login('queue.php?config=2');
		$this->assertTitle('Queue \[refreshed every 30 sec\]');
		$this->ok('Queue');
		$this->ok('QUEUE OF ITEMS TO BE UPDATED');
		// Header
		$this->ok(array('Next check','Delayed by','Host','Description'));
		$this->ok('Total:');
	}

	public function testPageQeueOverviewDetails_VerifyDisplayedNumbers()
	{
// TODO
		$this->markTestIncomplete();
	}
}
?>
