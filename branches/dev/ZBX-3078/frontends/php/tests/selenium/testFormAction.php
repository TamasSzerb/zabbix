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

require_once dirname(__FILE__) . '/../include/class.cwebtest.php';

class testFormAction extends CWebTest {

	public static function providerNewActions() {
		$data = array(
			array(array(
				'name' => 'action test 2',
				'esc_period' => '123',
				'def_shortdata' => 'def_shortdata',
				'def_longdata' => 'def_longdata',
				'conditions' => array(
					array(
						'type' => 'Trigger name',
						'value' => 'trigger',
					),
					array(
						'type' => 'Trigger severity',
						'value' => 'Warning',
					),
					array(
						'type' => 'Application',
						'value' => 'application',
					),
				),
				'operations' => array(
					array(
						'type' => 'Send message',
						'media' => 'Email',
					),
					array(
						'type' => 'Remote command',
						'command' => 'command',
					)
				),
			)),
		);
		return $data;
	}

	/**
	 * @dataProvider providerNewActions
	 */
	public function testActionCreateSimple($action) {
		DBsave_tables('actions');

		$this->login('actionconf.php?form=1&eventsource=0');
		$this->checkTitle('Configuration of actions');

		$this->type('name', $action['name']);
		$this->type("def_shortdata", $action['def_shortdata']);
		$this->type("def_longdata", $action['def_longdata']);

		$this->button_click("link=Conditions");
		foreach ($action['conditions'] as $condition) {

			$this->dropdown_select_wait("new_condition_conditiontype", $condition['type']);
			switch ($condition['type']) {
				case 'Trigger name':
					$this->type("new_condition_value", $condition['value']);
					$this->button_click('add_condition');
					$this->wait();
					$this->ok('Trigger name like "'.$condition['value'].'"');
					break;
				case 'Trigger severity':
					$this->dropdown_select('new_condition_value', $condition['value']);
					$this->button_click('add_condition');
					$this->wait();
					$this->ok('Trigger severity = "'.$condition['value'].'"');
					break;
				case 'Application':
					$this->type("new_condition_value", $condition['value']);
					$this->button_click('add_condition');
					$this->wait();
					$this->ok('Application = "'.$condition['value'].'"');
					break;
			}
		}

		$this->button_click("link=Operations");

		foreach ($action['operations'] as $operation) {
			$this->button_click('new_operation');
			$this->wait();
			$this->dropdown_select_wait('new_operation_operationtype', $operation['type']);

			switch ($operation['type']) {
				case 'Send message':
					sleep(1);

					$this->button_click("addusrgrpbtn");
					$this->waitForPopUp("zbx_popup", "30000");
					$this->selectWindow("name=zbx_popup");
					$this->button_click("all_usrgrps");
					$this->button_click("select");
					$this->selectWindow("null");

					sleep(1);

					$this->button_click("adduserbtn");
					$this->waitForPopUp("zbx_popup", "30000");
					$this->selectWindow("name=zbx_popup");
					$this->button_click('all_users');
					$this->button_click('select');
					$this->selectWindow("null");

					$this->select('new_operation_opmessage_mediatypeid', $operation['media']);
					break;
				case 'Remote command':
					$this->button_click("add");
					$this->button_click("//input[@name='save']");
					$this->type('new_operation_opcommand_command', $operation['command']);
					break;
			}
			$this->button_click('add_operation');
			$this->wait();
		}
		$this->type('esc_period', $action['esc_period']);

		sleep(1);
		$this->type('new_condition_value', '');
		sleep(1);

		$this->button_click('save');
		$this->wait();
		$this->ok('Action added');

		DBrestore_tables('actions');
	}

	public function testActionCreate() {
		DBsave_tables('actions');

		$this->login('actionconf.php?form=1&eventsource=0');
		$this->checkTitle('Configuration of actions');

		$this->type("name", "action test");
		$this->type("def_shortdata", "subject");
		$this->type("def_longdata", "message");

// adding conditions
		$this->button_click("link=Conditions");
		$this->type("new_condition_value", "trigger");
		$this->button_click("add_condition");
		$this->wait();
		$this->ok("Trigger name like \"trigger\"");

		$this->select("new_condition_conditiontype", "label=Trigger severity");
		$this->wait();
		$this->select("new_condition_value", "label=Average");
		$this->button_click("add_condition");
		$this->wait();
		$this->ok("Trigger severity = \"Average\"");

		$this->select("new_condition_conditiontype", "label=Application");
		$this->wait();
		$this->type("new_condition_value", "app");
		$this->button_click("add_condition");
		$this->wait();
		$this->ok("Application = \"app\"");

// adding operations
		$this->button_click("link=Operations");
		$this->button_click("new_operation");
		$this->wait();
		$this->button_click("addusrgrpbtn");
		sleep(1);
		$this->waitForPopUp("zbx_popup", "30000");
		$this->selectWindow("name=zbx_popup");
		$this->button_click("usrgrps_7");
		$this->button_click("usrgrps_11");
		$this->button_click("select");
		$this->selectWindow("null");
		sleep(1);
		$this->button_click("adduserbtn");
		$this->waitForPopUp("zbx_popup", "30000");
		$this->selectWindow("name=zbx_popup");
		$this->button_click("users_'1'");
		$this->button_click("select");
		$this->selectWindow("null");
		$this->select("new_operation_opmessage_mediatypeid", "label=Jabber");
		$this->button_click("add_operation");
		$this->wait();
		$this->ok("Send message to users: Admin");
		$this->ok("Send message to user groups: Enabled debug mode, Zabbix administrators");
		$this->button_click("new_operation");
		$this->wait();
		$this->select("new_operation_operationtype", "label=Remote command");
		$this->wait();
// add target current host
		$this->button_click("add");
		$this->button_click("//input[@name='save']");

// add target host Zabbix server
		$this->button_click("add");
		$this->select("opCmdTarget", "label=Host");
		$this->button_click("select");
		$this->waitForPopUp("zbx_popup", "30000");
		$this->selectWindow("name=zbx_popup");
		$this->dropdown_select_wait('groupid', 'Zabbix servers');
		$this->button_click("spanid10053");
		$this->selectWindow("null");
		$this->button_click("//input[@name='save']");

		sleep(1);

// add target group Zabbix servers
		$this->button_click("add");
		$this->select("opCmdTarget", "label=Host group");
		$this->button_click("select");
		$this->waitForPopUp("zbx_popup", "30000");
		$this->selectWindow("name=zbx_popup");
		$this->button_click("spanid4");
		$this->selectWindow("null");

		sleep(1);

		$this->button_click("//input[@name='save']");
		$this->type("new_operation_opcommand_command", "command");
		$this->button_click("add_operation");
		$this->wait();
		$this->ok("Run remote commands on current host");
		// $this->ok("Run remote commands on hosts: ЗАББИКС Сервер");
		$this->ok("Run remote commands on host groups: Zabbix servers");
		$this->button_click("new_operation");
		$this->wait();
		$this->type("new_operation_esc_step_to", "2");
		$this->select("new_operation_operationtype", "label=Remote command");
		$this->wait();
		$this->button_click("add");
		$this->button_click("//input[@name='save']");
		$this->select("new_operation_opcommand_type", "label=SSH");
		$this->type("new_operation_opcommand_username", "user");
		$this->type("new_operation_opcommand_password", "pass");
		$this->type("new_operation_opcommand_port", "123");
		$this->type("new_operation_opcommand_command", "command ssh");
		$this->button_click("add_operation");
		$this->wait();
		$this->type("esc_period", "123");
		$this->ok("Run remote commands on current host");

		sleep(1);
		$this->type('new_condition_value', '');
		sleep(1);

		$this->button_click('save');
		$this->wait();
		$this->ok("Action added");

		DBrestore_tables('actions');
	}
}
