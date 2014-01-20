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

$page['title'] = _('Test result from particular proxy');
$page['file'] = 'dnstest.particularproxys.php';
$page['hist_arg'] = array('groupid', 'hostid');

require_once dirname(__FILE__).'/include/page_header.php';

//		VAR			TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION
$fields = array(
	'host' =>		array(T_ZBX_STR, O_MAND,	P_SYS,	null,	null),
	'type' =>		array(T_ZBX_INT, O_MAND,	null,	IN('0,1'),	null),
	'probe' =>		array(T_ZBX_STR, O_MAND,	P_SYS,	null,	null),
	'time' =>		array(T_ZBX_INT, O_MAND,	P_SYS,	DB_ID,	null),
	'slvItemId' =>	array(T_ZBX_INT, O_MAND,	P_SYS,	DB_ID,	null)
);
check_fields($fields);

$data = array();
$data['proxys'] = array();
$data['host'] = get_request('host');
$data['time'] = get_request('time');
$data['slvItemId'] = get_request('slvItemId');
$data['probe'] = get_request('probe');
$data['type'] = get_request('type');

// check
if (!$data['slvItemId'] || !$data['host'] || !$data['time'] || !$data['probe']) {
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

$testTimeTill = $testTimeFrom + 59;

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
	'output' => array('name', 'lastvalue')
));

$data['slvItem'] = reset($slvItems);

// get probe
$probe = API::Host()->get(array(
	'output' => array('hostid', 'host', 'name'),
	'filter' => array(
		'host' => $data['probe']
	)
));

$data['probe'] = reset($probe);

// get probe host
$hostName = $data['tld']['host'].' '.$data['probe']['host'];

$host = API::Host()->get(array(
	'output' => array('hostid'),
	'filter' => array(
		'host' => $hostName
	)
));

$host = reset($host);

// get host with calculated items
$dnstest = API::Host()->get(array(
	'output' => array('hostid'),
	'filter' => array(
		'host' => DNSTEST_HOST
	)
));

if ($dnstest) {
	$dnstest = reset($dnstest);
}
else {
	show_error_message(_s('No permissions to referred host "%1$s" or it does not exist!', DNSTEST_HOST));
	require_once dirname(__FILE__).'/include/page_footer.php';
	exit;
}

$macroItemKey[] = CALCULATED_ITEM_DNS_UDP_RTT;

if ($data['type'] == 0) {
	$macroItemKey[] = CALCULATED_ITEM_DNS_AVAIL_MINNS;
}

// get macros old value
$macroItems = API::Item()->get(array(
	'hostids' => $dnstest['hostid'],
	'output' => array('itemid', 'value_type', 'key_'),
	'filter' => array(
		'key_' => $macroItemKey
	)
));

foreach ($macroItems as $macroItem) {
	$macroItemValue = API::History()->get(array(
		'itemids' => $macroItem['itemid'],
		'time_from' => $testTimeFrom,
		'time_till' => $testTimeTill,
		'history' => $macroItem['value_type'],
		'output' => API_OUTPUT_EXTEND
	));

	$macroItemValue = reset($macroItemValue);

	if ($macroItem['key_'] == CALCULATED_ITEM_DNS_UDP_RTT) {
		$dnsUdpRtt = $macroItemValue['value'];
	}
	else {
		$minNs = $macroItemValue['value'];
	}
}
if (!isset($dnsUdpRtt)) {
	show_error_message(_s(
		'No permissions to referred item with key "%1$s" or it does not exist!',
		CALCULATED_ITEM_DNS_UDP_RTT
	));
	require_once dirname(__FILE__).'/include/page_footer.php';
	exit;
}
if ($data['type'] == 0 && !isset($minNs)) {
	show_error_message(_s(
		'No permissions to referred item with key "%1$s" or it does not exist!',
		CALCULATED_ITEM_DNS_AVAIL_MINNS
	));
	require_once dirname(__FILE__).'/include/page_footer.php';
	exit;
}

// get items
$probeItems = API::Item()->get(array(
	'hostids' => $host['hostid'],
	'output' => array('itemid', 'key_', 'hostid', 'valuemapid', 'units', 'value_type'),
	'search' => array(
		'key_' => PROBE_DNS_UDP_ITEM_RTT
	),
	'startSearch' => true,
	'preservekeys' => true
));

$totalNs = array();
$data['positiveNs'] = 0;
foreach ($probeItems as $probeItem) {
	preg_match('/^[^\[]+\[([^\]]+)]$/', $probeItem['key_'], $matches);
	$nsValues = explode(',', $matches[1]);

	// get NS values
	$itemValue = API::History()->get(array(
		'itemids' => $probeItem['itemid'],
		'time_from' => $testTimeFrom,
		'time_till' => $testTimeTill,
		'history' => $probeItem['value_type'],
		'output' => API_OUTPUT_EXTEND
	));

	$itemValue = reset($itemValue);

	$ms = convert_units($itemValue['value'], $probeItem['units']);
	$ms = $itemValue ? applyValueMap($ms, $probeItem['valuemapid']) : null;

	$data['proxys'][$probeItem['itemid']] = array(
		'ns' => $nsValues[1],
		'ip' => $nsValues[2],
		'ms' => $ms
	);

	$totalNs[$nsValues[1]] = true;

	if ($itemValue['value'] > 0 && $itemValue['value'] <= $dnsUdpRtt) {
		$data['positiveNs']++;
	}
}

$data['totalNs'] = count($totalNs);

if ($data['tld'] && $data['slvItem'] && $data['probe']) {
	$data['slv'] = sprintf('%.3f', $data['slvItem']['lastvalue']);
	$data['minMs'] = $dnsUdpRtt;

	if ($data['type'] == 0) {
		$data['probe']['test'] = ($data['positiveNs'] >= $minNs) ? true : false;
	}
	else {
		$data['probe']['test'] = ($data['totalNs'] / 2 <= $data['positiveNs']) ? true : false;
	}
}
else {
	access_deny();
}

$dnsTestView = new CView('dnstest.particularproxys.list', $data);

$dnsTestView->render();
$dnsTestView->show();

require_once dirname(__FILE__).'/include/page_footer.php';
