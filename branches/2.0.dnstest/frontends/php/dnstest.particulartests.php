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


require_once dirname(__FILE__).'/include/config.inc.php';
require_once dirname(__FILE__).'/include/incidentdetails.inc.php';

$page['title'] = _('Details of particular test');
$page['file'] = 'dnstest.particulartests.php';
$page['hist_arg'] = array('groupid', 'hostid');

require_once dirname(__FILE__).'/include/page_header.php';

//		VAR			TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION
$fields = array(
	'host' =>		array(T_ZBX_STR, O_MAND,	P_SYS,	null,			null),
	'type' =>		array(T_ZBX_INT, O_MAND,	null,	IN('0,1,2'),	null),
	'time' =>		array(T_ZBX_INT, O_MAND,	P_SYS,	DB_ID,			null),
	'slvItemId' =>	array(T_ZBX_INT, O_MAND,	P_SYS,	DB_ID,			null)
);
check_fields($fields);

$data = array();
$data['probes'] = array();
$data['host'] = get_request('host');
$data['time'] = get_request('time');
$data['slvItemId'] = get_request('slvItemId');
$data['type'] = get_request('type');

// check
if (!$data['slvItemId'] || !$data['host'] || !$data['time'] || $data['type'] === null) {
	access_deny();
}

$testTimeFrom = mktime(
	date('H', $data['time']),
	date('i', $data['time']),
	0,
	date('n', $data['time']),
	date('j', $data['time']),
	date('Y', $data['time'])
);

// macro
if ($data['type'] == 0 || $data['type'] == 1) {
	$macro[] = DNSTEST_DNS_UDP_DELAY;
	if ($data['type'] == 0) {
		$data['availProbes'] = 0;
		$data['totalProbes'] = 0;
	}
	else {
		$data['availTests'] = 0;
		$data['totalTests'] = 0;
	}
}
else {
	$macro[] = DNSTEST_RDDS_DELAY;
}

if ($data['type'] == 0) {
	$macro[] = DNSTEST_MIN_DNS_COUNT;
	$macro[] = DNSTEST_DNS_UDP_RTT;
}

// get global macros
$macros = API::UserMacro()->get(array(
	'globalmacro' => true,
	'output' => API_OUTPUT_EXTEND,
	'filter' => array(
		'macro' => $macro
	)
));

if ($data['type'] == 0) {
	foreach ($macros as $macro) {
		if ($macro['macro'] == DNSTEST_MIN_DNS_COUNT) {
			$minDnsCount = $macro['value'];
		}
		elseif ($macro['macro'] == DNSTEST_DNS_UDP_RTT) {
			$udpRtt = $macro['value'];
		}
		else {
			$macroTime = $macro['value'] - 1;
		}
	}
}
else {
	$macro = reset($macros);
	$macroTime = $macro['value'] - 1;
}

// time calculation
$timeFrom = $macroTime - 59;
$testTimeTill = $testTimeFrom + 59;
$testTimeFrom -= $timeFrom;

// get TLD
$tld = API::Host()->get(array(
	'tlds' => true,
	'output' => array('hostid', 'host', 'name'),
	'filter' => array(
		'host' => $data['host']
	)
));

$data['tld'] = reset($tld);

// get slv item
$slvItems = API::Item()->get(array(
	'itemids' => $data['slvItemId'],
	'output' => array('name', 'key_', 'lastvalue')
));

$data['slvItem'] = reset($slvItems);

// get "Probes" groupId
$groups = API::HostGroup()->get(array(
	'filter' => array(
		'name' => 'Probes'
	),
	'output' => array('groupid')
));

$group = reset($groups);

// get probes
$hosts = API::Host()->get(array(
	'groupids' => $group['groupid'],
	'output' => array('hostid', 'host', 'name'),
	'preservekeys' => true
));

$hostIds = array();
foreach ($hosts as $host) {
	$hostIds[] = $host['hostid'];
}

// get probes items
$probeItems = API::Item()->get(array(
	'hostids' => $hostIds,
	'output' => array('itemid', 'key_', 'hostid'),
	'filter' => array(
		'key_' => array(PROBE_STATUS_MANUAL, PROBE_STATUS_AUTOMATIC)
	),
	'preservekeys' => true
));

foreach ($probeItems as $probeItem) {
	// manual items
	if ($probeItem['key_'] != PROBE_STATUS_MANUAL) {
		$manualItemIds[] = $probeItem['itemid'];
	}
	// automatic items
	if ($probeItem['key_'] != PROBE_STATUS_AUTOMATIC) {
		$automaticItemIds[$probeItem['itemid']] = $probeItem['hostid'];
	}
}

// probe main data generation
foreach ($hosts as $host) {
	$data['probes'][$host['hostid']] = array(
		'host' => $host['host'],
		'name' => $host['name']
	);
}

// get manual data
$ignoredHostIds = array();

foreach ($manualItemIds as $itemId) {
	$itemValue = DBfetch(DBselect(DBaddLimit(
		'SELECT h.value'.
		' FROM history_uint h'.
		' WHERE h.itemid='.$itemId.
			' AND h.clock>='.$testTimeFrom.
			' AND h.clock<='.$testTimeTill,
		1
	)));

	if ($itemValue && $itemValue['value'] == PROBE_DOWN) {
		$data['probes'][$probeItems[$itemId]['hostid']]['status'] = PROBE_DOWN;
		$ignoredHostIds[] = $probeItems[$itemId]['hostid'];
	}
}

// get automatic data
foreach ($automaticItemIds as $itemId => $hostId) {
	if (!in_array($hostId, $ignoredHostIds)) {
		$itemValue = DBfetch(DBselect(DBaddLimit(
			'SELECT h.value'.
			' FROM history_uint h'.
			' WHERE h.itemid='.$itemId.
				' AND h.clock>='.$testTimeFrom.
				' AND h.clock<='.$testTimeTill,
			1
		)));

		if ($itemValue && $itemValue['value'] == PROBE_DOWN) {
			$data['probes'][$hostId]['status'] = PROBE_DOWN;
		}
	}
}

// get probes data hosts
foreach ($data['probes'] as $hostId => $probe) {
	if (!isset($probe['status'])) {
		$hostNames[] = $data['tld']['host'].' '.$probe['host'];
	}
}

$hosts = API::Host()->get(array(
	'output' => array('hostid', 'host', 'name'),
	'filter' => array(
		'host' => $hostNames
	),
	'preservekeys' => true
));

$hostIds = array();
foreach ($hosts as $host) {
	$hostIds[] = $host['hostid'];
}

$data['totalProbes'] = count($hostIds);

// get only used items
if ($data['type'] == 0 || $data['type'] == 1) {
	$probeItemKey = ' AND (i.key_ LIKE ('.zbx_dbstr(PROBE_DNS_UDP_ITEM_RTT.'%').') OR i.key_='.zbx_dbstr(PROBE_DNS_UDP_ITEM).')';
}
else {
	$probeItemKey = ' AND i.key_ LIKE ('.zbx_dbstr(PROBE_RDDS_ITEM.'%').')';
}

// SQL
$items = DBselect(
	'SELECT i.itemid,i.key_,i.hostid'.
	' FROM items i'.
	' WHERE '.dbConditionInt('i.hostid', $hostIds).
		$probeItemKey
);

$nsArray = array();

// get items value
while ($item = DBfetch($items)) {
	if ($data['type'] == 0 || $data['type'] == 1) {
		$itemValue = DBfetch(DBselect(DBaddLimit(
			'SELECT h.value'.
			' FROM history_uint h'.
			' WHERE h.itemid='.$item['itemid'].
				' AND h.clock>='.$testTimeFrom.
				' AND h.clock<='.$testTimeTill.
			' ORDER BY h.clock DESC',
			1
		)));
	}
	if ($data['type'] == 0 && $item['key_'] == PROBE_DNS_UDP_ITEM_RTT) {
		preg_match('/^[^\[]+\[([^\]]+)]$/', $item['key_'], $matches);
		$nsValues = explode(',', $matches[1]);

		if (!$itemValue) {
			$nsArray[$item['hostid']][$nsValues[1]]['value'][] = null;
		}
		elseif ($itemValue['value'] < $udpRtt * 5 && $itemValue['value'] > DNSTEST_NO_REPLY_ERROR_CODE) {
			$nsArray[$item['hostid']][$nsValues[1]]['value'][] = true;
		}
		else {
			$nsArray[$item['hostid']][$nsValues[1]]['value'][] = false;
		}
	}
	elseif ($data['type'] == 0 && $item['key_'] == PROBE_DNS_UDP_ITEM) {
		// avail probes
		if ($itemValue['value'] == 1) {
			$data['availProbes']++;
		}
	}
	elseif ($data['type'] == 1 && $item['key_'] == PROBE_DNS_UDP_ITEM_RTT) {
		if (!isset($hosts[$item['hostid']]['value'])) {
			$hosts[$item['hostid']]['value']['ok'] = 0;
			$hosts[$item['hostid']]['value']['fail'] = 0;
			$hosts[$item['hostid']]['value']['total'] = 0;
			$hosts[$item['hostid']]['value']['noResult'] = 0;
		}

		if ($itemValue) {
			if ($itemValue['value'] != DNSSEC_FAIL_ERROR_CODE) {
				$hosts[$item['hostid']]['value']['ok']++;
			}
			else {
				$hosts[$item['hostid']]['value']['fail']++;
			}
		}
		else {
			$hosts[$item['hostid']]['value']['noResult']++;
		}

		$hosts[$item['hostid']]['value']['total']++;
	}
	elseif ($data['type'] == 2) {
		$itemValue = DBfetch(DBselect(DBaddLimit(
			'SELECT h.value'.
			' FROM history_uint h'.
			' WHERE h.itemid='.$item['itemid'].
				' AND h.clock>='.$testTimeFrom.
				' AND h.clock<='.$testTimeTill.
			' ORDER BY h.clock DESC',
			1
		)));

		if (!$itemValue) {
			$itemValue['value'] = null;
		}
		$hosts[$item['hostid']]['value'] = $itemValue['value'];
	}
}

if ($data['type'] == 0) {
	foreach ($nsArray as $hostId => $nss) {
		$failNs = 0;

		foreach ($nss as $nsName => $nsValue) {
			if (in_array(false, $nsValue)) {
				$failNs++;
			}
		}

		if (count($nss) - $failNs >= $minDnsCount) {
			$hosts[$hostId]['value'] = true;
		}
		else {
			$hosts[$hostId]['value'] = false;
		}
	}
}

foreach ($hosts as $host) {
	foreach ($data['probes'] as $hostId => $probe) {
		if (zbx_strtoupper($host['host']) == zbx_strtoupper($data['tld']['host'].' '.$probe['host'])
				&& isset($host['value'])) {
			$data['probes'][$hostId]['value'] = $host['value'];
			break;
		}
	}
}

if ($data['tld'] && $data['slvItem']) {
	$data['slv'] = round($data['slvItem']['lastvalue'], ZBX_UNITS_ROUNDOFF_DNSTEST_LIMIT);
}
else {
	access_deny();
}

CArrayHelper::sort($data['probes'], array('name'));

$dnsTestView = new CView('dnstest.particulartests.list', $data);

$dnsTestView->render();
$dnsTestView->show();

require_once dirname(__FILE__).'/include/page_footer.php';
