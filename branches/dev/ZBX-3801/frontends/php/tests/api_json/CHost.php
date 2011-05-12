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
require_once 'PHPUnit/Framework.php';

require_once(dirname(__FILE__).'/../include/class.czabbixtest.php');

class API_JSON_Host extends CZabbixTest
{
	public static function host_names()
	{
		return array(
			array('Test host', true),
			array('Fake host', false),
		);
	}

	public static function dup_template_ids()
	{
		return array(
			array(
				array(
				'host' => 'Host to test dup ids 1',
				'interfaces'=> array(
						array(
							"type" => 1,
							"useip" => 1,
							"ip" => "192.168.3.1",
							"dns" => "",
							"port" => 567
						)
					),
					"groups" => array(
						array("groupid" => 5)
					),
					"templates" => array(
						array("templateid" => 10025)
					)
				),
				true
			),
			array(
				array(
				'host' => 'Host to test dup ids 2',
				'interfaces'=> array(
						array(
							"type" => 1,
							"useip" => 1,
							"ip" => "192.168.3.1",
							"dns" => "",
							"port" => 567
						)
					),
					"groups" => array(
						array("groupid" => 5)
					),
					"templates" => array(
						array("templateid" => 10025),
						array("templateid" => 10025)
					)
				),
				false
			),
			array(
				array(
				'host' => 'Host to test dup ids 2',
				'interfaces'=> array(
						array(
							"type" => 1,
							"useip" => 1,
							"ip" => "192.168.3.1",
							"dns" => "",
							"port" => 567
						)
					),
					"groups" => array(
						array("groupid" => 5)
					),
					"templates" => array(
						array("templateid" => 10024),
						array("templateid" => 10027),
						array("templateid" => 10026),
						array("templateid" => 10025),
						array("templateid" => 10025),
						array("templateid" => 10023),
					)
				),
				false
			),
		);
	}

	/**
	* @dataProvider host_names
	*/
	public function testCHost_exists($name, $exists)
	{
		$debug = null;

		$result = $this->api_acall(
			'host.exists',
			array('host'=>$name),
			&$debug
		);

		$this->assertTrue(!array_key_exists('error', $result),"Chuck Norris: Exists method returned an error. Result is: ".print_r($result, true)."\nDebug: ".print_r($debug, true));

		$this->assertFalse(
			($result['result'] != $exists),
			"Chuck Norris: Exists method returned wrong result. Result is: ".print_r($result, true)."\nDebug: ".print_r($debug, true)
		);
	}


	/**
	* @dataProvider dup_template_ids
	*/
	public function testCHostDuplicateTemplateIds($request, $successExpected)
	{
		$debug = null;

		$result = $this->api_acall(
			'host.create',
			$request,
			&$debug
		);

		if($successExpected){
			$this->assertTrue(
				!array_key_exists('error', $result) || strpos($result['error']['data'], 'Cannot pass duplicate template ids') === false,
				"Chuck Norris: I was expecting that host.create would not complain on duplicate ids. Result is: ".print_r($result, true)."\nDebug: ".print_r($debug, true)
			);
		}
		else{
			$this->assertTrue(
				array_key_exists('error', $result) && strpos($result['error']['data'], 'Cannot pass duplicate template ids') !== false,
				"Chuck Norris: I was expecting that host.create to complain on duplicate ids. Result is: ".print_r($result, true)."\nDebug: ".print_r($debug, true)
			);
		}

	}
}
?>
