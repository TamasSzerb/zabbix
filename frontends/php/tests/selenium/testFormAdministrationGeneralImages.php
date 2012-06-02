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

class testFormAdministrationGeneralImages extends CWebTest {
		public $icon_image_name = '1image1';
		public $icon_image_name2 = '2image2';
		public $bg_image_name = '1bgimage1';
		public $bg_image_name2 = '2bgimage2';

	public function testFormAdministrationGeneralImages_CheckLayout() {

		$this->login('adm.gui.php');
		$this->assertElementPresent('configDropDown');
		$this->dropdown_select_wait('configDropDown', 'Images');
		$this->assertTitle('Configuration of images');
		$this->ok(array('CONFIGURATION OF IMAGES', 'Images', 'Type'));
		$this->assertElementPresent("//select[@id='imagetype']/option[text()='Icon']");
		$this->assertElementPresent("//select[@id='imagetype']/option[text()='Background']");
		$this->assertElementPresent('form');
		$this->button_click('form');
		$this->wait();

		$this->assertElementPresent('name');
		$this->assertAttribute("//input[@id='name']/@maxlength", '64');
		$this->assertAttribute("//input[@id='name']/@size", '64');
		$this->assertElementPresent("//select[@id='imagetype']/option[text()='Icon']");
		$this->assertElementPresent("//select[@id='imagetype']/option[text()='Background']");
		$this->assertElementPresent('image');
		$this->assertElementPresent('save');
		$this->assertElementPresent('cancel');

	}

	public function testFormAdministrationGeneralImages_AddImage() {

		$this->login('adm.images.php');
		$this->button_click('form');
		$this->wait();

		$this->assertElementPresent('name');
		$this->input_type('name', $this->icon_image_name);
		$this->dropdown_select_wait('imagetype', 'Icon');
		$this->type('image', '/home/hudson/public_html/trunk-FRONTEND-MYSQL/frontends/php/images/sysmaps/UPS.png');
		$this->click('save');
		$this->wait();
		$this->assertTitle('Configuration of images');
		$this->ok(array('CONFIGURATION OF IMAGES', 'Images', 'Type', 'Image added'));

		// $sql = 'SELECT * FROM images WHERE name=\''.$this->icon_image_name.'\'';
		$sql = 'SELECT * FROM images WHERE imagetype=1 AND name=\''.$this->icon_image_name.'\'';
		$this->assertEquals(1, DBcount($sql), 'Chuck Norris: Image with such name has not been added to the DB');

	}

	public function testFormAdministrationGeneralImages_CancelIconImageChanges() {

		$sqlIcons = 'SELECT * FROM images WHERE imagetype=1';
		$oldHashIcons=DBhash($sqlIcons);

		$this->login('adm.images.php');
		$this->click('link='.$this->icon_image_name);
		$this->wait();
		$this->input_type('name', $this->icon_image_name2);
		$this->type('image', '/home/hudson/public_html/trunk-FRONTEND-MYSQL/frontends/php/images/sysmaps/Workstation.png');
		$this->button_click("cancel");

		// checking that image has not been changed after clicking on the "Cancel" button in the confirm dialog box
		$this->assertEquals($oldHashIcons, DBhash($sqlIcons), 'Chuck Norris: No-change images update should not update data in table "images"');

	}

	public function testFormAdministrationGeneralImages_UpdateImage() {

		$this->login('adm.images.php');
		$this->click('link='.$this->icon_image_name);
		$this->wait();
		$this->input_type('name', $this->icon_image_name2);
		$this->type('image', '/home/hudson/public_html/trunk-FRONTEND-MYSQL/frontends/php/images/sysmaps/Notebook.png');
		$this->click('save');
		$this->wait();
		$this->assertTitle('Configuration of images');
		$this->ok(array('CONFIGURATION OF IMAGES', 'Images', 'Image updated'));

		// SELECT * FROM images WHERE name='...' AND imagetype=1;
		$sql = 'SELECT * FROM images WHERE imagetype=1 AND name=\''.$this->icon_image_name2.'\'';
		$this->assertEquals(1, DBcount($sql), 'Chuck Norris: Image with such name does not exist in the DB');
	}

	public function testFormAdministrationGeneralImages_DeleteImage() {

		$this->login('adm.images.php');
		$this->chooseOkOnNextConfirmation();
		$this->click('link='.$this->icon_image_name2);
		$this->wait();
		$this->button_click('delete');
		$this->waitForConfirmation();
		$this->wait();
		$this->assertTitle('Configuration of images');
		$this->ok(array('CONFIGURATION OF IMAGES', 'Images', 'Image deleted'));

		$sql = 'SELECT * FROM images WHERE imagetype=1 AND name=\''.$this->icon_image_name2.'\'';
		$this->assertEquals(0, DBcount($sql), 'Chuck Norris: Image with such name still exist in the DB');
	}

	public function testFormAdministrationGeneralImages_AddBgImage() {

		$this->login('adm.images.php');
		$this->button_click('form');
		$this->wait();
		$this->input_type('name', $this->bg_image_name);
		$this->dropdown_select('imagetype', 'Background');
		$this->type('image', '/home/hudson/public_html/trunk-FRONTEND-MYSQL/frontends/php/images/sysmaps/Printer.png');
		$this->click('save');
		$this->wait();
		$this->assertTitle('Configuration of images');
		$this->ok(array('CONFIGURATION OF IMAGES', 'Images', 'Type', 'Image added'));

		// SELECT * FROM images WHERE name='...' AND imagetype=2;
		$sql = 'SELECT * FROM images WHERE imagetype=2 AND name=\''.$this->bg_image_name.'\'';
		$this->assertEquals(1, DBcount($sql), 'Chuck Norris: Image with such name has not been added to the DB');
	}

	public function testFormAdministrationGeneralImages_UpdateBgImage() {

		$this->login('adm.images.php');
		$this->dropdown_select_wait('imagetype', 'Background');
		$this->ok('Type');
		$this->click("//form[@name='imageForm']//table//a[text()='".$this->bg_image_name."']");
		$this->wait();
		$this->input_type('name', $this->bg_image_name2);
		$this->type('image', '/home/hudson/igor/trunk/frontends/php/images/sysmaps/Satellite.png');
		$this->click('save');
		$this->wait();
		$this->assertTitle('Configuration of images');
		$this->ok(array('CONFIGURATION OF IMAGES', 'Images', 'Image updated'));

		$sql = 'SELECT * FROM images WHERE imagetype=2 AND name=\''.$this->bg_image_name2.'\'';
		$this->assertEquals(1, DBcount($sql), 'Chuck Norris: Image with such name does not exist in the DB');
	}

	public function testFormAdministrationGeneralImages_DeleteBgImage() {

		$this->login('adm.images.php');
		$this->dropdown_select_wait('imagetype', 'Background');
		$this->chooseOkOnNextConfirmation();
		$this->click("//form[@name='imageForm']//table//a[text()='".$this->bg_image_name2."']");
		$this->wait();
		$this->button_click('delete');
		$this->waitForConfirmation();
		$this->wait();
		$this->assertTitle('Configuration of images');
		$this->ok(array('CONFIGURATION OF IMAGES', 'Images', 'Image deleted'));

		$sql = 'SELECT * FROM images WHERE imagetype=2 AND name=\''.$this->bg_image_name2.'\'';
		$this->assertEquals(0, DBcount($sql), 'Chuck Norris: Image with such name still exist in the DB');
	}

}
?>
