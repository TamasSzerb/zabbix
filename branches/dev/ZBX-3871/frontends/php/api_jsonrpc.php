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
header('Access-Control-Allow-Origin: *');
if($_SERVER['REQUEST_METHOD'] == "OPTIONS"){
	header('Access-Control-Allow-Methods: POST, OPTIONS');
	header('Access-Control-Allow-Headers: Content-Type');
	header('Access-Control-Allow-Credentials: false');
	header('Access-Control-Max-Age: 1728000'); // 20 days
	header('Content-Length: 0');
	header('Content-Type: text/plain');
	exit();
}

define('ZBX_RPC_REQUEST', 1);
require_once('include/config.inc.php');

$allowed_content = array(
	'application/json-rpc'		=> 'json-rpc',
	'application/json'			=> 'json-rpc',
	'application/jsonrequest'	=> 'json-rpc',
//	'application/xml-rpc'		=> 'xml-rpc',
//	'application/xml'			=> 'xml-rpc',
//	'application/xmlrequest'	=> 'xml-rpc'
				);
?>
<?php

$http_request = new CHTTP_request();
$content_type = $http_request->header('Content-Type');
$content_type = explode(';', $content_type);
$content_type = $content_type[0];


if(!isset($allowed_content[$content_type])){
	header('HTTP/1.0 412 Precondition Failed');
	exit();
}

$data = $http_request->body();

if($allowed_content[$content_type] == 'json-rpc'){
	header('Content-Type: application/json');

	$jsonRpc = new CJSONrpc($data);

	print($jsonRpc->execute());
}
else if($allowed_content[$content_type] == 'xml-rpc'){

}
?>
