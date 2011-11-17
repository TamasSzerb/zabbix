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
require_once(dirname(__FILE__).'/../include/class.cwebtest.php');

class testFormTemplate extends CWebTest{
	public $template = "Test template";

	public function testFormTemplate_Create(){
		$this->login('templates.php');
		$this->dropdown_select_wait('groupid','Templates');
		$this->button_click('form');
		$this->wait();
		$this->input_type('template_name',$this->template);
		$this->button_click('save');
		$this->wait();
		$this->assertTitle('Templates');
		$this->ok('New template added');
		$this->ok($this->template);
	}

	public function testFormTemplate_CreateLongTemplateName(){
// 64 character long template name
		$template="000000000011111111112222222222333333333344444444445555555555666";
		$this->login('templates.php');
		$this->dropdown_select_wait('groupid','Templates');
		$this->button_click('form');
		$this->wait();
		$this->input_type('template_name',$template);
		$this->button_click('save');
		$this->wait();
		$this->assertTitle('Templates');
		$this->ok('New template added');
		$this->ok($template);
	}

	public function testFormTemplate_SimpleUpdate(){
		$this->login('templates.php');
		$this->dropdown_select_wait('groupid','Templates');
		$this->click('link=Template_Linux');
		$this->wait();
		$this->button_click('save');
		$this->wait();
		$this->assertTitle('Templates');
		$this->ok('Template updated');
		$this->ok($this->template);
	}

	public function testFormTemplate_UpdateTemplateName(){
		// Update template
		$this->login('templates.php');
		$this->dropdown_select_wait('groupid','all');
		$this->click('link='.$this->template);
		$this->wait();
		$this->input_type('template_name',$this->template.'2');
		$this->button_click('save');
		$this->wait();
		$this->assertTitle('Templates');
		$this->ok('Template updated');
	}

	public function testFormTemplate_CreateExistingTemplateNoGroups(){
	// Attempt to create a template with a name that already exists and not add it to any groups
	// In future should also check these conditions individually
	$this->login('templates.php');
	$this->dropdown_select_wait('groupid','all');
	$this->button_click('form');
	$this->wait();
	$this->input_type('template_name','Template_Linux');
	$this->button_click('save');
	$this->wait();
	$this->assertTitle('Templates');
	$this->ok('No groups for template');
	$this->assertEquals(1,DBcount("select * from hosts where host='Template_Linux'"));
	}

	public function testFormTemplate_Delete(){
		$this->chooseOkOnNextConfirmation();
		// Delete template
		$this->login('templates.php');
		$this->dropdown_select_wait('groupid','all');
		$this->click('link='.$this->template.'2');
		$this->wait();
		$this->button_click('delete');
		$this->waitForConfirmation();
		$this->wait();
		$this->assertTitle('Templates');
		$this->ok('Template deleted');
	}

	public function testFormTemplate_CloneTemplate(){
		// Clone template
		$this->login('templates.php');
		$this->dropdown_select_wait('groupid','all');
		$this->click('link=Template_Linux');
		$this->wait();
		$this->button_click('clone');
		$this->wait();
		$this->input_type('template_name',$this->template.'2');
		$this->button_click('save');
		$this->wait();
		$this->assertTitle('Templates');
		$this->ok('New template added');
	}

	public function testFormTemplate_DeleteClonedTemplate(){
		$this->chooseOkOnNextConfirmation();

		// Delete template
		$this->login('templates.php');
		$this->dropdown_select_wait('groupid','all');
		$this->click('link='.$this->template.'2');
		$this->wait();
		$this->button_click('delete');
		$this->wait();
		$this->getConfirmation();
		$this->assertTitle('Templates');
		$this->ok('Template deleted');
	}

	public function testFormTemplate_FullCloneTemplate(){
		// Full clone template
		$this->login('templates.php');
		$this->dropdown_select_wait('groupid','all');
		$this->click('link=Template_Linux');
		$this->wait();
		$this->button_click('full_clone');
		$this->wait();
		$this->input_type('template_name',$this->template.'_fullclone');
		$this->button_click('save');
		$this->wait();
		$this->assertTitle('Templates');
		$this->ok('New template added');
	}

	public function testFormTemplate_DeleteFullClonedTemplate(){
		$this->chooseOkOnNextConfirmation();

		// Delete full cloned template
		$this->login('templates.php');
		$this->dropdown_select_wait('groupid','all');
		$this->click('link='.$this->template.'_fullclone');
		$this->wait();
		$this->button_click('delete');
		$this->wait();
		$this->getConfirmation();
		$this->assertTitle('Templates');
		$this->ok('Template deleted');
	}
}
?>
