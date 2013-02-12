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

require_once dirname(__FILE__).'/../include/class.cwebtest.php';

class testPageDashboard extends CWebTest {
	public function testPageDashboard_CheckLayout() {
		$this->login('dashboard.php');
		$this->checkTitle('Dashboard');
		$this->ok('PERSONAL DASHBOARD');
		$this->ok('Favourite graphs');
		$this->ok('Favourite screens');
		$this->ok('Favourite maps');
		$this->ok('Status of Zabbix');
		$this->ok('System status');
		$this->ok('Host status');
		$this->ok('Last 20 issues');
		$this->ok('Web monitoring');
		$this->ok('Updated:');
	}

// Check that no real host or template names displayed
	public function testPageDashboard_NoHostNames() {
		$this->login('dashboard.php');
		$this->checkTitle('Dashboard');
		$this->checkNoRealHostnames();
	}
}
