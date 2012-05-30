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
require_once dirname(__FILE__).'/../include/class.cwebtest.php';

class testPopup extends CWebTest {
	private $urlPopupTemplates =
			'popup.php?srctbl=templates&srcfld1=hostid&srcfld2=host&dstfrm=form&dstfld1=fld1&templated_hosts=1';
	private $urlPopupHostTemplates =
			'popup.php?srctbl=host_templates&srcfld1=templateid&srcfld2=name&dstfrm=form&dstfld1=fld1&dstfld2=fld2&templated_hosts=1';
	private $urlPopupHostsAndTemplates =
			'popup.php?srctbl=hosts_and_templates&srcfld1=hostid&srcfld2=name&dstfrm=form&dstfld1=fld1&dstfld2=fld2';
	private $urlPopupHosts =
			'popup.php?srctbl=hosts&srcfld1=hostid&srcfld2=name&dstfrm=form&dstfld1=fld1&dstfld2=fld2&real_hosts=1&writeonly=1';
	private $urlPopupProxies =
			'popup.php?srctbl=proxies&srcfld1=hostid&srcfld2=host&dstfrm=form&dstfld1=fld1&dstfld2=fld2';
	private $urlPopupApplications =
			'popup.php?srctbl=applications&srcfld1=name&dstfrm=form&dstfld1=fld1';

	public function testPopupTemplates_CheckLayout() {
		$this->login();
		$this->open($this->urlPopupTemplates);
		$this->assertTitle('Templates');
		$this->ok('Templates');
		$this->ok('Group');
		$this->ok('Name');
		$this->assertElementPresent('groupid');
		$this->assertElementPresent('select');
		$this->assertSomethingSelected('groupid');

		$ddGroups = $this->zbxGetDropDownElements('groupid');
		$dbGroups = array();

		// checking order of dropdown entries

		$result = DBselect(
			'SELECT g.groupid,g.name'.
			' FROM groups g'.
			' WHERE g.groupid IN ('.
				'SELECT hg.groupid'.
				' FROM hosts_groups hg,hosts h'.
				' WHERE hg.hostid=h.hostid'.
					' AND h.status IN ('.HOST_STATUS_TEMPLATE.')'.
			')'
		);
		while ($row = DBfetch($result)) {
			$dbGroups[] = $row;
		}

		order_result($dbGroups, 'name');
		$dbGroups = array_values($dbGroups);

		$countDdGroups = count($ddGroups);
		$countDbGroups = count($dbGroups);

		$this->assertEquals($countDdGroups, $countDbGroups);

		for ($i = 0; $i < $countDbGroups; $i++) {
			$this->assertEquals($dbGroups[$i]['groupid'], $ddGroups[$i]['id']);
			$this->assertEquals($dbGroups[$i]['name'], $ddGroups[$i]['content']);
		}

		// checking window content

		foreach ($ddGroups as $ddGroup) {
			$this->dropdown_select_wait('groupid', $ddGroup['content']);

			$result = DBselect(
				'SELECT h.hostid,h.name'.
				' FROM hosts h,hosts_groups hg,groups g'.
				' WHERE h.hostid=hg.hostid'.
					' AND hg.groupid=g.groupid'.
					' AND h.status IN ('.HOST_STATUS_TEMPLATE.')'.
					' AND g.groupid='.$ddGroup['id']
			);
			while ($row = DBfetch($result)) {
				$this->assertElementPresent('templates_'.$row['hostid']);

				$this->assertAttribute('//*[@id="templates_'.$row['hostid'].'"]/@value', $row['name']);

				$this->ok($row['name']);
			}
		}
	}

	public function testPopupHostTemplates_CheckLayout() {
		$this->login();
		$this->open($this->urlPopupHostTemplates);
		$this->assertTitle('Templates');
		$this->ok('Templates');
		$this->ok('Group');
		$this->ok('Name');
		$this->assertElementPresent('groupid');
		$this->assertSomethingSelected('groupid');

		$ddGroups = $this->zbxGetDropDownElements('groupid');
		$dbGroups = array();

		// checking order of dropdown entries

		$result = DBselect(
			'SELECT g.groupid,g.name'.
			' FROM groups g'.
			' WHERE g.groupid IN ('.
				'SELECT hg.groupid'.
				' FROM hosts_groups hg,hosts h'.
				' WHERE hg.hostid=h.hostid'.
					' AND h.status IN ('.HOST_STATUS_TEMPLATE.')'.
			')'
		);
		while ($row = DBfetch($result)) {
			$dbGroups[] = $row;
		}

		order_result($dbGroups, 'name');
		$dbGroups = array_values($dbGroups);

		$countDdGroups = count($ddGroups);
		$countDbGroups = count($dbGroups);

		$this->assertEquals($countDdGroups, $countDbGroups);

		for ($i = 0; $i < $countDbGroups; $i++) {
			$this->assertEquals($dbGroups[$i]['groupid'], $ddGroups[$i]['id']);
			$this->assertEquals($dbGroups[$i]['name'], $ddGroups[$i]['content']);
		}

		// checking window content

		foreach ($ddGroups as $ddGroup) {
			$this->dropdown_select_wait('groupid', $ddGroup['content']);

			$result = DBselect(
				'SELECT h.name'.
				' FROM hosts h,hosts_groups hg,groups g'.
				' WHERE h.hostid=hg.hostid'.
					' AND hg.groupid=g.groupid'.
					' AND h.status IN ('.HOST_STATUS_TEMPLATE.')'.
					' AND g.groupid='.$ddGroup['id']
			);
			while ($row = DBfetch($result)) {
				$this->ok($row['name']);
			}
		}
	}

	public function testPopupHostsAndTemplates_CheckLayout() {
		$this->login();
		$this->open($this->urlPopupHostsAndTemplates);
		$this->assertTitle('Hosts and templates');
		$this->ok('Hosts and templates');
		$this->ok('Group');
		$this->ok('Name');
		$this->assertElementPresent('groupid');
		$this->assertElementPresent('empty');
		$this->assertSomethingSelected('groupid');

		$ddGroups = $this->zbxGetDropDownElements('groupid');
		$dbGroups = array();

		// checking order of dropdown entries

		$result = DBselect(
			'SELECT g.groupid,g.name'.
			' FROM groups g'.
			' WHERE g.groupid IN ('.
				'SELECT hg.groupid'.
				' FROM hosts_groups hg,hosts h'.
				' WHERE hg.hostid=h.hostid'.
					' AND h.status IN ('.HOST_STATUS_MONITORED.','.HOST_STATUS_NOT_MONITORED.','.HOST_STATUS_TEMPLATE.')'.
			')'
		);
		while ($row = DBfetch($result)) {
			$dbGroups[] = $row;
		}

		order_result($dbGroups, 'name');
		$dbGroups = array_values($dbGroups);

		$countDdGroups = count($ddGroups);
		$countDbGroups = count($dbGroups);

		$this->assertEquals($countDdGroups, $countDbGroups);

		for ($i = 0; $i < $countDbGroups; $i++) {
			$this->assertEquals($dbGroups[$i]['groupid'], $ddGroups[$i]['id']);
			$this->assertEquals($dbGroups[$i]['name'], $ddGroups[$i]['content']);
		}

		// checking window content

		foreach ($ddGroups as $ddGroup) {
			$this->dropdown_select_wait('groupid', $ddGroup['content']);

			$result = DBselect(
				'SELECT h.name'.
				' FROM hosts h,hosts_groups hg,groups g'.
				' WHERE h.hostid=hg.hostid'.
					' AND hg.groupid=g.groupid'.
					' AND h.status IN ('.HOST_STATUS_MONITORED.','.HOST_STATUS_NOT_MONITORED.','.HOST_STATUS_TEMPLATE.')'.
					' AND g.groupid='.$ddGroup['id']
			);
			while ($row = DBfetch($result)) {
				$this->ok($row['name']);
			}
		}
	}

	public function testPopupHosts_CheckLayout() {
		$this->login();
		$this->open($this->urlPopupHosts);
		$this->assertTitle('Hosts');
		$this->ok('Hosts');
		$this->ok('Group');
		$this->ok(array('Name', 'DNS', 'IP', 'Port', 'Status', 'Availability'));
		$this->assertElementPresent('groupid');
		$this->assertElementPresent('empty');
		$this->assertSomethingSelected('groupid');

		$ddGroups = $this->zbxGetDropDownElements('groupid');
		$dbGroups = array();

		// checking order of dropdown entries

		$result = DBselect(
			'SELECT g.groupid,g.name'.
			' FROM groups g'.
			' WHERE g.groupid IN ('.
				'SELECT hg.groupid'.
				' FROM hosts_groups hg,hosts h'.
				' WHERE hg.hostid=h.hostid'.
					' AND h.status IN ('.HOST_STATUS_MONITORED.','.HOST_STATUS_NOT_MONITORED.')'.
			')'
		);
		while ($row = DBfetch($result)) {
			$dbGroups[] = $row;
		}

		order_result($dbGroups, 'name');
		$dbGroups = array_values($dbGroups);

		$countDdGroups = count($ddGroups);
		$countDbGroups = count($dbGroups);

		$this->assertEquals($countDdGroups, $countDbGroups);

		for ($i = 0; $i < $countDbGroups; $i++) {
			$this->assertEquals($dbGroups[$i]['groupid'], $ddGroups[$i]['id']);
			$this->assertEquals($dbGroups[$i]['name'], $ddGroups[$i]['content']);
		}

		// checking window content

		foreach ($ddGroups as $ddGroup) {
			$this->dropdown_select_wait('groupid', $ddGroup['content']);

			$result = DBselect(
				'SELECT h.name'.
				' FROM hosts h,hosts_groups hg,groups g'.
				' WHERE h.hostid=hg.hostid'.
					' AND hg.groupid=g.groupid'.
					' AND h.status IN ('.HOST_STATUS_MONITORED.','.HOST_STATUS_NOT_MONITORED.')'.
					' AND g.groupid='.$ddGroup['id']
			);
			while ($row = DBfetch($result)) {
				$this->ok($row['name']);
			}
		}
	}

	public function testPopupProxies_CheckLayout() {
		$this->login();
		$this->open($this->urlPopupProxies);
		$this->assertTitle('Proxies');
		$this->ok('Proxies');
		$this->ok(array('Name'));

		$result = DBselect(
			'SELECT host'.
			' FROM hosts'.
			' WHERE status IN ('.HOST_STATUS_PROXY_ACTIVE.','.HOST_STATUS_PROXY_PASSIVE.')'
		);
		while ($row = DBfetch($result)) {
			$this->ok($row['host']);
		}
	}

	public function testPopupApplications_CheckLayout() {
		$this->login();
		$this->open($this->urlPopupApplications);
		$this->assertTitle('Applications');
		$this->ok('Applications');
		$this->ok(array('Group', 'Host'));
		$this->ok('Name');
		$this->assertElementPresent('groupid');
		$this->assertElementPresent('hostid');
		$this->assertSomethingSelected('groupid');
		$this->assertSomethingSelected('hostid');

		$ddGroups = $this->zbxGetDropDownElements('groupid');
		$dbGroups = array();

		// checking order of dropdown entries

		$result = DBselect(
			'SELECT g.groupid,g.name'.
			' FROM groups g'.
			' WHERE g.groupid IN ('.
				'SELECT hg.groupid'.
				' FROM hosts_groups hg,hosts h,applications a'.
				' WHERE hg.hostid=h.hostid'.
					' AND h.hostid=a.hostid'.
					' AND h.status IN ('.HOST_STATUS_MONITORED.','.HOST_STATUS_NOT_MONITORED.','.HOST_STATUS_TEMPLATE.')'.
			')'
		);
		while ($row = DBfetch($result)) {
			$dbGroups[] = $row;
		}

		order_result($dbGroups, 'name');
		$dbGroups = array_values($dbGroups);

		$countDdGroups = count($ddGroups);
		$countDbGroups = count($dbGroups);

		$this->assertEquals($countDdGroups, $countDbGroups);

		for ($i = 0; $i < $countDbGroups; $i++) {
			$this->assertEquals($dbGroups[$i]['groupid'], $ddGroups[$i]['id']);
			$this->assertEquals($dbGroups[$i]['name'], $ddGroups[$i]['content']);
		}

		// checking window content

		// TODO checkind windows content and hosts dropdown
	}
}
?>
