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

function screenIdents($screenids){
	$idents = array();

	$options = array(
		'screenids' => $screenids,
		'output' => API_OUTPUT_EXTEND,
		'nodeids'=> get_current_nodeid(true)
	);

	$screens = API::Screen()->get($options);
	foreach($screens as $inum => $screen){
		$idents[$screen['screenid']] = array(
			'name' => $screen['name']
		);
	}

return $idents;
}

function sysmapIdents($sysmapids){
	$idents = array();

	$options = array(
		'sysmapids' => $sysmapids,
		'output' => API_OUTPUT_EXTEND,
		'nodeids'=> get_current_nodeid(true)
	);

	$sysmaps = API::Map()->get($options);
	foreach($sysmaps as $sysmap){
		$idents[$sysmap['sysmapid']] = array(
			'name' => $sysmap['name']
		);
	}

	return $idents;
}

function hostgroupIdents($groupids){
	$idents = array();

	$options = array(
		'groupids' => $groupids,
		'output' => API_OUTPUT_EXTEND,
		'nodeids'=> get_current_nodeid(true)
	);

	$groups = API::HostGroup()->get($options);
	foreach($groups as $group){
		$idents[$group['groupid']] = array(
			'name' => $group['name']
		);
	}

	return $idents;
}

function hostIdents($hostids){
	$idents = array();

	$options = array(
		'hostids' => $hostids,
		'output' => API_OUTPUT_EXTEND,
		'nodeids'=> get_current_nodeid(true)
	);

	$hosts = API::Host()->get($options);
	foreach($hosts as $host){
		$idents[$host['hostid']] = array(
			'host' => $host['host']
		);
	}

	return $idents;
}

function itemIdents($itemids){
	$idents = array();

	$options = array(
		'itemids' => $itemids,
		'output' => API_OUTPUT_EXTEND,
		'selectHosts' => array('hostid', 'host'),
		'nodeids'=> get_current_nodeid(true),
		'webitems' => 1,
	);

	$items = API::Item()->get($options);
	foreach($items as $item){
		$host = reset($item['hosts']);

		$idents[$item['itemid']] = array(
			'host' => $host['host'],
			'key' => $item['key_']
		);
	}

	return $idents;
}

function triggerIdents($triggerids){
	$idents = array();

	$options = array(
		'triggerids' => $triggerids,
		'output' => API_OUTPUT_EXTEND,
		'nodeids'=> get_current_nodeid(true)
	);

	$triggers = API::Trigger()->get($options);
	foreach($triggers as $trigger){
		$idents[$trigger['triggerid']] = array(
			'description' => $trigger['description'],
			'expression' => explode_exp($trigger['expression'])
		);
	}

	return $idents;
}

function graphIdents($graphids){
	$idents = array();

	$options = array(
		'graphids' => $graphids,
		'selectHosts' => array('hostid', 'host'),
		'output' => API_OUTPUT_EXTEND,
		'nodeids'=> get_current_nodeid(true)
	);

	$graphs = API::Graph()->get($options);
	foreach($graphs as $graph){
		$host = reset($graph['hosts']);

		$idents[$graph['graphid']] = array(
			'host' => $host['host'],
			'name' => $graph['name']
		);
	}

	return $idents;
}

function imageIdents($imageids){
	$idents = array();

	$options = array(
		'imageids' => $imageids,
		'output' => API_OUTPUT_EXTEND,
		'nodeids'=> get_current_nodeid(true)
	);

	$images = API::Image()->get($options);
	foreach($images as $image){
		$idents[$image['imageid']] = array(
			'name' => $image['name']
		);
	}

	return $idents;
}

function getImageByIdent($ident){
	zbx_value2array($ident);

	if(!isset($ident['name'])) return 0;

	static $images;
	if(is_null($images)){
// get All images
		$images = array();
		$options = array(
			'output' => API_OUTPUT_EXTEND,
			'nodeids' => get_current_nodeid(true)
		);

		$dbImages = API::Image()->get($options);
		foreach($dbImages as $inum => $img){
			if(!isset($images[$img['name']])) $images[$img['name']] = array();

			$nodeName = get_node_name_by_elid($img['imageid'], true);

			if(!is_null($nodeName))
				$images[$img['name']][$nodeName] = $img;
			else
				$images[$img['name']][] = $img;
		}
//------
	}

	$ident['name'] = trim($ident['name'],' ');
	if(!isset($images[$ident['name']])) return 0;

	$sImages = $images[$ident['name']];

	if(!isset($ident['node'])) return reset($sImages);
	else if(isset($sImages[$ident['node']])) return $sImages[$ident['node']];
	else return 0;
}
?>
