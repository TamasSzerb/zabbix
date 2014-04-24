<?php
/*
** Zabbix
** Copyright (C) 2001-2013 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
**/


$rsmWidget = new CWidget(null, 'particular-test');

// header
$rsmWidget->addPageHeader(_('Details of particular test'), SPACE);
$rsmWidget->addHeader(_('Details of particular test'));

if ($this->data['type'] == RSM_DNS || $this->data['type'] == RSM_DNSSEC) {
	$headers = array(
		_('Probe ID'),
		_('Row result')
	);
}
elseif ($this->data['type'] == RSM_RDDS) {
	$headers = array(
		_('Probe ID'),
		_('Row result'),
		_('RDDS43'),
		_('RDDS43 IP'),
		_('RDDS43 RTT'),
		_('RDDS43 UPD'),
		_('RDDS80'),
		_('RDDS80 IP'),
		_('RDDS80 RTT'),
	);
}
else {
	$headers = array(
		_('Probe ID'),
		_('Row result'),
		_('IP'),
		_('Login'),
		_('Update'),
		_('Info')
	);
}

$noData = _('No particular test found.');

$particularTestsInfoTable = new CTable(null, 'filter info-block');

$particularTestsTable = new CTableInfo($noData);
$particularTestsTable->setHeader($headers);

$down = new CSpan(_('Down'), 'red');
$offline = new CSpan(_('Offline'), 'red');
$noResult = _('No result');
$up = new CSpan(_('Up'), 'green');

foreach ($this->data['probes'] as $probe) {
	$status = null;
	if (isset($probe['status']) && $probe['status'] === PROBE_DOWN) {
		if ($this->data['type'] == RSM_DNS || $this->data['type'] == RSM_DNSSEC) {
			$link = $offline;
		}
		elseif ($this->data['type'] == RSM_RDDS) {
			$rdds = $offline;
			$rdds43 = $offline;
			$rdds80 = $offline;
		}
		else {
			$epp = $offline;
		}
	}
	else {
		if ($this->data['type'] == RSM_DNS) {
			// DNS
			if (isset($probe['value'])) {
				if ($probe['value']) {
					$status = $up;
				}
				else {
					$status = $down;
				}
				$link = new CLink(
					$status,
					'rsm.particularproxys.php?slvItemId='.$this->data['slvItemId'].'&host='.$this->data['tld']['host'].
						'&time='.$this->data['time'].'&probe='.$probe['host'].'&type='.$this->data['type']
				);
			}
			else {
				$link = new CSpan(_('Not monitored'), 'red');
			}
		}
		elseif ($this->data['type'] == RSM_DNSSEC) {
			// DNSSEC
			if (isset($probe['value']['ok'])) {
				$values = array();
				if ($probe['value']['ok']) {
					$values[] = $probe['value']['ok'].' OK';
				}
				if ($probe['value']['fail']) {
					$values[] = $probe['value']['fail'].' FAILED';
				}
				if ($probe['value']['noResult']) {
					$values[] = $probe['value']['noResult'].' NO RESULT';
				}
				$link = new CLink(
					implode(', ', $values),
					'rsm.particularproxys.php?slvItemId='.$this->data['slvItemId'].'&host='.$this->data['tld']['host'].
						'&time='.$this->data['time'].'&probe='.$probe['host'].'&type='.$this->data['type']
				);
			}
			else {
				$link = new CSpan(_('Not monitored'), 'red');
			}
		}
		elseif ($this->data['type'] == RSM_RDDS) {
			// RDDS
			if (!isset($probe['value']) || $probe['value'] === null) {
				$rdds43 = $noResult;
				$rdds80 = $noResult;
				$rdds = $noResult;
			}
			elseif ($probe['value'] == 0) {
				$rdds43 = $down;
				$rdds80 = $down;
				$rdds = $down;
			}
			elseif ($probe['value'] == 1) {
				$rdds43 = $up;
				$rdds80 = $up;
				$rdds = $up;
			}
			elseif ($probe['value'] == 2) {
				$rdds43 = $up;
				$rdds80 = $down;
				$rdds = $down;
			}
			elseif ($probe['value'] == 3) {
				$rdds43 = $down;
				$rdds80 = $up;
				$rdds = $down;
			}
		}
		else {
			// EPP
			if (!isset($probe['value']) || $probe['value'] === null) {
				$epp = $noResult;
			}
			elseif ($probe['value'] == 0) {
				$epp = $down;
			}
			elseif ($probe['value'] == 1) {
				$epp = $up;
			}
		}
	}

	if ($this->data['type'] == RSM_DNS || $this->data['type'] == RSM_DNSSEC) {
		$row = array(
			$probe['name'],
			$link
		);
	}
	elseif ($this->data['type'] == RSM_RDDS) {
		$row = array(
			$probe['name'],
			$rdds,
			$rdds43,
			(isset($probe['rdds43']['ip']) && $probe['rdds43']['ip']) ? $probe['rdds43']['ip'] : '-',
			(isset($probe['rdds43']['rtt']) && $probe['rdds43']['rtt']) ? $probe['rdds43']['rtt'] : '-',
			(isset($probe['rdds43']['upd']) && $probe['rdds43']['upd']) ? $probe['rdds43']['upd'] : '-',
			$rdds80,
			(isset($probe['rdds80']['ip']) && $probe['rdds80']['ip']) ? $probe['rdds80']['ip'] : '-',
			(isset($probe['rdds80']['rtt']) && $probe['rdds80']['rtt']) ? $probe['rdds80']['rtt'] : '-'
		);
	}
	else {
		$row = array(
			$probe['name'],
			$epp,
			(isset($probe['ip']) && $probe['ip']) ? $probe['ip'] : '-',
			(isset($probe['login']) && $probe['login']) ? $probe['login'] : '-',
			(isset($probe['update']) && $probe['update']) ? $probe['update'] : '-',
			(isset($probe['info']) && $probe['info']) ? $probe['info'] : '-'
		);
	}

	$particularTestsTable->addRow($row);
}
if ($this->data['type'] == RSM_DNS) {
	if ($this->data['totalAvailProbes'] != 0) {
		$availProbes = round($this->data['availProbes'] / $this->data['totalAvailProbes'] * 100, ZBX_UNITS_ROUNDOFF_UPPER_LIMIT);
	}
	else {
		$availProbes = 0;
	}

	$additionInfo = array(
		new CSpan(array(bold(_('Probes total')), ':', SPACE, $this->data['totalProbes'])),
		BR(),
		new CSpan(array(bold(_('Probes offline')), ':', SPACE, $this->data['offlineProbes'])),
		BR(),
		new CSpan(array(bold(_('Probes with No Result')), ':', SPACE, $this->data['noResultProbes'])),
		BR(),
		new CSpan(array(bold(_('Probes with Result')), ':', SPACE, $this->data['resultProbes'])),
		BR(),
		new CSpan(array(bold(_('Probes Up')), ':', SPACE, $this->data['availProbes']))
	);
}
elseif ($this->data['type'] == RSM_DNSSEC) {
	$additionInfo = array(
		new CSpan(bold(_s(
			'%1$s out of %2$s tests reported availability of service',
			round($this->data['availTests'] / $this->data['totalTests'] * 100, ZBX_UNITS_ROUNDOFF_UPPER_LIMIT).'%',
			$this->data['totalTests']
		)))
	);
}

$tldTriggersLink = new CLink($this->data['tld']['name'], 'tr_status.php?groupid=0&hostid='.$this->data['tld']['hostid']);
$tldTriggersLink->setTarget('_blank');

if ($this->data['testResult'] === null) {
	$testResult = $noResult;
}
elseif ($this->data['testResult'] == PROBE_UP) {
	$testResult = $up;
}
else {
	$testResult = $down;
}

$particularTests = array(
	new CSpan(array(bold(_('TLD')), ':', SPACE, $tldTriggersLink)),
	BR(),
	new CSpan(array(bold(_('Service')), ':', SPACE, $this->data['slvItem']['name'])),
	BR(),
	new CSpan(array(bold(_('Test time')), ':', SPACE, date('d.m.Y H:i:s', $this->data['time']))),
	BR(),
	new CSpan(array(bold(_('Test result')), ':', SPACE, $testResult))
);

$tableHeader[] = $particularTests;
if ($this->data['type'] == RSM_DNS || $this->data['type'] == RSM_DNSSEC) {
	$tableHeader[] = $additionInfo;
}

$particularTestsInfoTable->addRow($tableHeader);

$rsmWidget->additem($particularTestsInfoTable);

$rsmWidget->additem($particularTestsTable);

return $rsmWidget;
