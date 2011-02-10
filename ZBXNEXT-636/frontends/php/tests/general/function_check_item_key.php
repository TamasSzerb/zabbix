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
require_once 'PHPUnit/Framework.php';

require_once(dirname(__FILE__).'/../../include/func.inc.php');
require_once(dirname(__FILE__).'/../../include/items.inc.php');
require_once(dirname(__FILE__).'/../../include/defines.inc.php');
require_once(dirname(__FILE__).'/../../include/locales.inc.php');

class function_check_item_key extends PHPUnit_Framework_TestCase
{
	public static function provider()
	{
		return array(
	// Correct item key
			array('key[a]',true),
			array('key["a"]',true),
			array('key[a, b, c]',true),
			array('key["a", "b", "c"]',true),
			array('key[a, b, "c"]',true),
			array('key["a", "b", c]',true),
			array('key[abc[]',true),
			array('key["a[][][]]],\"!@$#$^%*&*)"]',true),
			array('key[["a"],b]',true),
			array('complex.key[a, b, c]',true),
			array('complex.key[[a, b], c]',true),
			array('complex.key[abc"efg"h]',true),
			array('complex.key[a][b]',true),
			array('complex.key["a"]["b"]',true),
			array('complex.key["a"][b]',true),
			array('complex.key[a, b][c, d]',true),
			array('complex.key["a", "b"]["c", "d"]',true),
			array('more.complex.key[1, 2, [A, B, [a, b], C], 3]',true),
			array('more.complex.key["1", "2", ["A", "B", ["a", "b"], "C"], "3"]',true),
			array('more.complex.key[["1"]]',true),
			array('key[,,]',true),
			array('key[a"]',true),
			array('key[a\"]',true),
			array('key["\""]',true),
			array('key[a,]',true),
			array('key["a",]',true),
			array('system.run["echo \'a\"b\' | cut -d\'\"\' -f1"]',true),
			// Only digits
			array('012345',true),
			// UTF8 chars in params
			array('key[ГУГЛ]',true),
			array('key["ГУГЛ"]',true),
	// Incorrect item keys
			array('key[["a",]',false),
			array('key[a]654',false),
			array('key["a"]654',false),
			array('key[a][[b]',false),
			array('key["a"][["b"]',false),
			array('key(a)',false),
			array('key[a]]',false),
			array('key["a"]]',false),
			array('key["a]',false),
			array('abc:def',false),
			// 256 char long key
			array('0000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000',false),
			// UTF8 chars
			array('ГУГЛ',false),
			array('',false)
		);
	}

	/**
	* @dataProvider provider
	*/
	public function test_check_item_key($a, $b){

		$itemCheck = check_item_key($a);
		if($itemCheck['valid'])
			$this->assertEquals($itemCheck['valid'],$b);
		else
			$this->assertEquals($itemCheck['valid'],$b,$itemCheck['description']);
	}

}
?>
