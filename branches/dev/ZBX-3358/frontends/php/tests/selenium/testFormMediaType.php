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
require_once(dirname(__FILE__).'/../include/class.cwebtest.php');

class testFormMediaType extends CWebTest
{
	public $affectedTables = array('media_type','media','opmessage');

	// Returns all media types
	public static function allMediaTypes()
	{
		return DBdata('select * from media_type');
	}

	// Data for media type creation
	public static function newMediaTypes()
	{
		$data=array(
			array('Email', array('Description'=>'Email2','SMTP server'=>'mail.zabbix.com',
				'SMTP helo'=>'zabbix.com','SMTP email'=>'zabbix@zabbix.com')),
			array('Email',array('Description'=>'Email3','SMTP server'=>'mail2.zabbix.com',
				'SMTP helo'=>'zabbix.com','SMTP email'=>'zabbix2@zabbix.com')),
			array('Script',array('Description'=>'Skype message','Script'=>'/usr/local/bin/skype.sh')),
			array('Script',array('Description'=>'Skype message2','Script'=>'/usr/local/bin/skyp2.sh')),
			array('SMS',array('Description'=>'Direct SMS messaging','GSM modem'=>'/dev/ttyS3')),
			array('Jabber',array('Description'=>'Jabber messages','Jabber identifier'=>'zabbix@jabber.com','Password'=>'Secret password')),
		);
		return $data;
	}

	public function testFormMediaType_SimpleTest()
	{
		$this->login('media_types.php');
		$this->assertTitle('Media types');

		$this->click('form');
		$this->wait();

		$this->ok('Media types');
		$this->ok('CONFIGURATION OF MEDIA TYPES');
		$this->nok('Displaying');
		$this->ok(array('Description','Type','SMTP server','SMTP helo','SMTP email'));

		$this->click('cancel');
		$this->wait();

		$this->assertTitle('Media types');
	}

	/**
	* @dataProvider newMediaTypes
	*/
	public function testFormMediaType_Create($type,$data)
	{
		$this->login('media_types.php');
		$this->assertTitle('Media types');
		$this->button_click('form');
		$this->wait();

		switch($type) {
			case 'Email':
				$this->dropdown_select('type',$type);
				$this->input_type('description',$data['Description']);
				$this->input_type('smtp_server',$data['SMTP server']);
				$this->input_type('smtp_helo',$data['SMTP helo']);
				$this->input_type('smtp_email',$data['SMTP email']);
			break;
			case 'Script':
				$this->dropdown_select('type',$type);
				$this->wait();
				$this->input_type('description',$data['Description']);
				$this->input_type('exec_path',$data['Script']);
			break;
			case 'SMS':
				$this->dropdown_select('type',$type);
				$this->wait();
				$this->input_type('description',$data['Description']);
				$this->input_type('gsm_modem',$data['GSM modem']);
			break;
			case 'Jabber':
				$this->dropdown_select('type',$type);
				$this->wait();
				$this->input_type('description',$data['Description']);
				$this->input_type('username',$data['Jabber identifier']);
				$this->input_type('password',$data['Password']);
			break;
			case 'Ez Texting':
				$this->dropdown_select('type',$type);
				$this->wait();
				$this->input_type('description',$data['Description']);
				$this->input_type('username',$data['Username']);
				$this->input_type('password',$data['Password']);
			break;
		}

		$this->click('save');
		$this->wait();

		$this->assertTitle('Media types');
		$this->nok('ERROR');
		$this->ok($data['Description']);
		$this->ok('CONFIGURATION OF MEDIA TYPES');
	}

	/**
	* @dataProvider allMediaTypes
	*/
	public function testFormMediaType_SimpleCancel($mediatype)
	{
		$name=$mediatype['description'];

		$sql="select * from media_type order by mediatypeid";
		$oldHash=DBhash($sql);

		$this->login('media_types.php');
		$this->assertTitle('Media types');
		$this->click("link=$name");
		$this->wait();
		$this->button_click('cancel');
		$this->wait();
		$this->assertTitle('Media types');
		$this->ok("$name");
		$this->ok('CONFIGURATION OF MEDIA TYPES');

		$this->assertEquals($oldHash,DBhash($sql));
	}

	/**
	* @dataProvider allMediaTypes
	*/
	public function testFormMediaType_SimpleDelete($mediatype)
	{
		$name=$mediatype['description'];
		$id=$mediatype['mediatypeid'];

		$row=DBfetch(DBselect("select count(*) as cnt from opmessage where mediatypeid=$id"));
		$used_by_operations = ($row['cnt'] > 0);

		DBsave_tables($this->affectedTables);

		$this->chooseOkOnNextConfirmation();

		$this->login('media_types.php');
		$this->assertTitle('Media types');
		$this->click("link=$name");
		$this->wait();

		$this->button_click('delete');

		$this->getConfirmation();
		$this->wait();
		$this->assertTitle('Media types');
		switch($used_by_operations){
			case true:
				$this->nok('Media type deleted');
				$this->ok('Media type was not deleted');
				$this->ok('Mediatypes used by action');
			break;
			case false:
				$this->ok('Media type deleted');
				$sql="select * from media_type where mediatypeid=$id";
				$this->assertEquals(0,DBcount($sql));
			break;
		}

		DBrestore_tables($this->affectedTables);
	}
}
?>
