<?php
/*
** Zabbix
** Copyright (C) 2000-2013 Zabbix SIA
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

require_once dirname(__FILE__).'/../include/class.cwebtest.php';

/**
 * Test the creation of inheritance of new objects on a previously linked template.
 */
class testInheritanceGraphPrototype extends CWebTest {

	/**
	 * The name of the test template created in the test data set.
	 *
	 * @var string
	 */
	protected $template = 'Inheritance test template';

	/**
	 * The name of the test host created in the test data set.
	 *
	 * @var string
	 */
	protected $host = 'Template inheritance test host';

	/**
	 * The name of the test discovery rule created in the test data set.
	 *
	 * @var string
	 */
	protected $discoveryRule = 'testInheritanceDiscoveryRule';

	/**
	 * The name of the test discovery rule key created in the test data set.
	 *
	 * @var string
	 */
	protected $discoveryKey = 'inheritance-discovery-rule';

	/**
	 * The name of the test item created in the test data set.
	 *
	 * @var string
	 */
	protected $itemSimple = 'itemInheritance';

	/**
	 * The name of the test item key created in the test data set.
	 *
	 * @var string
	 */
	protected $itemKeySimple = 'key-item-inheritance';

	/**
	 * The name of the test item prototype within test discovery rule created in the test data set.
	 *
	 * @var string
	 */
	protected $item = 'itemDiscovery';

	/**
	 * The name of the test item prototype key within test discovery rule created in the test data set.
	 *
	 * @var string
	 */
	protected $itemKey = 'item-discovery-prototype';

	/**
	 * The value of the yaxismin field to be created in the test data set.
	 *
	 * @var int
	 */
	protected $yaxismin = 100;

	/**
	 * The value of the yaxismax field to be created in the test data set.
	 *
	 * @var int
	 */
	protected $yaxismax = 500;


	/**
	 * Backup the tables that will be modified during the tests.
	 */
	public function testInheritanceGraphPrototype_Setup() {
		DBsave_tables('graphs');
	}

	// Returns update data
	public static function update() {
		return DBdata("select * from graphs g left join graphs_items gi on gi.graphid=g.graphid where gi.itemid='23600'");
	}

	/**
	 * @dataProvider update
	 */
	public function testInheritanceGraphPrototype_SimpleUpdate($data) {
		$sqlGraphs = "select * from graphs ORDER BY graphid";
		$oldHashGraphs = DBhash($sqlGraphs);

		$this->zbxTestLogin('graphs.php?form=update&graphid='.$data['graphid'].'&parent_discoveryid=23500&hostid=30000');
		$this->zbxTestClickWait('save');
		$this->zbxTestCheckTitle('Configuration of graph prototypes');
		$this->zbxTestTextPresent(array(
			'CONFIGURATION OF GRAPH PROTOTYPES',
			'Graph prototypes of '.$this->discoveryRule,
			'Graph updated',
			$data['name']
		));

		$this->assertEquals($oldHashGraphs, DBhash($sqlGraphs));
	}

	// Returns create data
	public static function create() {
		return array(
			array(
				array('expected' => TEST_GOOD,
					'graphName' => 'graphSimple',
					'hostCheck' => true,
					'dbCheck' => true
				)
			),
			array(
				array('expected' => TEST_GOOD,
					'graphName' => 'graphName',
					'hostCheck' => true
				)
			),
			array(
				array('expected' => TEST_GOOD,
					'graphName' => 'graphRemove',
					'hostCheck' => true,
					'dbCheck' => true,
					'remove' => true
				)
			),
			array(
				array('expected' => TEST_GOOD,
					'graphName' => 'graphNotRemove',
					'hostCheck' => true,
					'dbCheck' => true,
					'hostRemove' => true,
					'remove' => true
				)
			),
			array(
				array('expected' => TEST_GOOD,
					'graphName' => 'graphNormal1',
					'graphtype' => 'Normal',
					'hostCheck' => true,
					'dbCheck' => true,
					'hostRemove' => true,
					'remove' => true
				)
			),
			array(
				array('expected' => TEST_GOOD,
					'graphName' => 'graphNormal2',
					'graphtype' => 'Normal',
					'ymin_type' => 'Fixed',
					'ymax_type' => 'Calculated',
					'hostCheck' => true,
					'dbCheck' => true,
					'hostRemove' => true,
					'remove' => true
				)
			),
			array(
				array('expected' => TEST_GOOD,
					'graphName' => 'graphNormal3',
					'ymin_type' => 'Item',
					'ymax_type' => 'Fixed',
					'hostCheck' => true,
					'dbCheck' => true,
					'hostRemove' => true,
					'remove' => true
				)
			),
			array(
				array('expected' => TEST_GOOD,
					'graphName' => 'graphNormal4',
					'ymin_type' => 'Fixed',
					'ymax_type' => 'Item',
					'hostCheck' => true,
					'dbCheck' => true,
					'hostRemove' => true,
					'remove' => true
				)
			),
			array(
				array('expected' => TEST_GOOD,
					'graphName' => 'graphNormal5',
					'ymin_type' => 'Item',
					'ymax_type' => 'Item',
					'hostCheck' => true,
					'dbCheck' => true,
					'hostRemove' => true,
					'remove' => true
				)
			),
			array(
				array('expected' => TEST_GOOD,
					'graphName' => 'graphStacked1',
					'graphtype' => 'Stacked',
					'ymin_type' => 'Item',
					'ymax_type' => 'Fixed',
					'hostCheck' => true,
					'dbCheck' => true,
					'hostRemove' => true,
					'remove' => true
				)
			),
			array(
				array('expected' => TEST_GOOD,
					'graphName' => 'graphStacked2',
					'graphtype' => 'Stacked',
					'ymin_type' => 'Fixed',
					'ymax_type' => 'Fixed',
					'hostCheck' => true,
					'dbCheck' => true,
					'hostRemove' => true,
					'remove' => true
				)
			),
			array(
				array('expected' => TEST_GOOD,
					'graphName' => 'graphStacked3',
					'graphtype' => 'Stacked',
					'ymax_type' => 'Fixed',
					'hostCheck' => true,
					'dbCheck' => true,
					'hostRemove' => true,
					'remove' => true
				)
			),
			array(
				array('expected' => TEST_GOOD,
					'graphName' => 'graphStacked4',
					'graphtype' => 'Stacked',
					'ymax_type' => 'Item',
					'hostCheck' => true,
					'dbCheck' => true,
					'hostRemove' => true,
					'remove' => true
				)
			),
			array(
				array('expected' => TEST_GOOD,
					'graphName' => 'graphStacked5',
					'graphtype' => 'Stacked',
					'hostCheck' => true,
					'dbCheck' => true,
					'hostRemove' => true,
					'remove' => true
				)
			),
			array(
				array('expected' => TEST_GOOD,
					'graphName' => 'graphPie',
					'graphtype' => 'Pie',
					'hostCheck' => true,
					'dbCheck' => true,
					'hostRemove' => true,
					'remove' => true
				)
			),
			array(
				array('expected' => TEST_GOOD,
					'graphName' => 'graphExploded',
					'graphtype' => 'Exploded',
					'hostCheck' => true,
					'dbCheck' => true,
					'hostRemove' => true,
					'remove' => true
				)
			),
			array(
				array('expected' => TEST_GOOD,
					'graphName' => 'graphSomeRemove',
					'hostCheck' => true,
					'dbCheck' => true,
					'hostRemove' => true,
					'remove' => true
				)
			),
			array(
				array('expected' => TEST_BAD,
					'graphName' => 'graphSimple',
					'errors' => array(
						'ERROR: Cannot add graph',
						'Graph with name "graphSimple" already exists in graphs or graph prototypes'
					)
				)
			),
			array(
				array(
					'expected' => TEST_GOOD,
					'graphName' => 'graph!@#$%^&*()><>?:"|{},./;',
					'graphtype' => 'Exploded',
					'formCheck' => true,
					'dbCheck' => true
				)
			),
			array(
				array(
					'expected' => TEST_BAD,
					'graphName' => 'graphSaveCheck',
					'noItem' => true,
					'errors' => array(
						'ERROR: Cannot add graph',
						'Missing items for graph prototype "graphSaveCheck".'
					)
				)
			),
			array(
				array(
					'expected' => TEST_BAD,
					'errors' => array(
						'ERROR: Page received incorrect data',
						'Incorrect value for field "Name": cannot be empty.'
					)
				)
			),
			array(
				array(
					'expected' => TEST_GOOD,
					'graphName' => 'graphRemoveAddItem',
					'removeItem' => true,
					'dbCheck' => true,
					'formCheck' => true
				)
			),
			array(
				array(
					'expected' => TEST_BAD,
					'graphName' => 'graphStackedNoMinAxisItem',
					'graphtype' => 'Stacked',
					'noAxisItem' => true,
					'ymin_type' => 'Item',
					'ymax_type' => 'Fixed',
					'errors' => array(
						'ERROR: Cannot add graph',
						'No permissions to referred object or it does not exist!'
					)
				)
			),
			array(
				array(
					'expected' => TEST_BAD,
					'graphName' => 'graphStackedNoMaxAxisItem',
					'graphtype' => 'Stacked',
					'noAxisItem' => true,
					'ymin_type' => 'Fixed',
					'ymax_type' => 'Item',
					'errors' => array(
						'ERROR: Cannot add graph',
						'No permissions to referred object or it does not exist!'
					)
				)
			),
			array(
				array(
					'expected' => TEST_BAD,
					'graphName' => 'graphStackedMore',
					'width' => 'name',
					'height' => 'name',
					'graphtype' => 'Stacked',
					'ymin_type' => 'Fixed',
					'yaxismin' => 'name',
					'ymax_type' => 'Fixed',
					'yaxismax' => 'name',
					'errors' => array(
						'ERROR: Page received incorrect data',
						'Incorrect value for field "Width (min:20, max:65535)": must be between 20 and 65535.',
						'Incorrect value for field "Height (min:20, max:65535)": must be between 20 and 65535.'
					)
				)
			),
			array(
				array(
					'expected' => TEST_BAD,
					'graphName' => 'graphStackedError',
					'width' => '65536',
					'height' => '-22',
					'graphtype' => 'Stacked',
					'ymin_type' => 'Fixed',
					'ymax_type' => 'Fixed',
					'errors' => array(
						'ERROR: Page received incorrect data',
						'Incorrect value for field "Width (min:20, max:65535)": must be between 20 and 65535.',
						'Incorrect value for field "Height (min:20, max:65535)": must be between 20 and 65535.'
					)
				)
			)
		);
	}

	/**
	 * @dataProvider create
	 */
	public function testInheritanceGraphPrototype_SimpleCreate($data) {
		$itemName = $this->item;
		$this->zbxTestLogin('graphs.php?parent_discoveryid=23500&form=Create+graph+prototype');

		$this->zbxTestCheckTitle('Configuration of graph prototypes');
		$this->zbxTestTextPresent('CONFIGURATION OF GRAPH PROTOTYPES');
		$this->assertElementPresent("//a[@id='tab_graphTab' and text()='Graph prototype']");

		if (isset($data['graphtype'])) {
			$this->zbxTestDropdownSelectWait('graphtype', $data['graphtype']);
		}
		$graphtype = $this->getSelectedLabel('graphtype');

		if (isset($data['ymin_type'])) {
			$this->zbxTestDropdownSelectWait('ymin_type', $data['ymin_type']);
		}

		if (isset($data['ymax_type'])) {
			$this->zbxTestDropdownSelectWait('ymax_type', $data['ymax_type']);
		}

		if (!isset($data['noItem'])) {
		$this->zbxTestLaunchPopup('add_item');
		$this->zbxTestClick('link='.$this->itemSimple);
		$this->selectWindow(null);
		sleep(1);

		$this->zbxTestLaunchPopup('add_protoitem');
		$this->zbxTestClick("//span[text()='"."$this->item"."']");
		$this->selectWindow(null);
		sleep(1);

			if(isset($data['removeItem'])) {
				$this->zbxTestClick('items_0_remove');
				sleep(1);
				$this->zbxTestClick('items_0_remove');
				sleep(1);

				$this->zbxTestLaunchPopup('add_item');
				$this->zbxTestClick('link='.$this->itemSimple);
				$this->selectWindow(null);
				sleep(1);

				$this->zbxTestLaunchPopup('add_protoitem');
				$this->zbxTestClick("//span[text()='"."$this->item"."']");
				$this->selectWindow(null);
				sleep(1);
			}
		}
		if (isset($data['width'])) {
			$this->input_type('width', $data['width']);
		}

		if (isset($data['height'])) {
			$this->input_type('height', $data['height']);
		}

		if (isset($data['graphName'])) {
			$graphName = $data['graphName'];
			$this->input_type('name', $graphName);
		}
		else {
			$graphName = null;
		}


		if ($graphtype == 'Normal' || $graphtype == 'Stacked') {

			$ymin_type = $this->getSelectedLabel('ymin_type');
			$ymax_type = $this->getSelectedLabel('ymax_type');

			switch($ymin_type) {
				case 'Fixed':
					$this->input_type('yaxismin', $this->yaxismin);
					break;
				case 'Item':
					if (!isset($data['noAxisItem'])) {
						$this->zbxTestLaunchPopup('yaxis_min_prototype', 'zbx_popup_item');
						$this->zbxTestClick("//span[text()='".$this->item."']");
						$this->selectWindow(null);
						sleep(1);
					}
					break;
				case 'Calculated':
					break;
			}

			switch($ymax_type) {
				case 'Fixed':
					$this->input_type('yaxismax', $this->yaxismax);
					break;
				case 'Item':
					if (!isset($data['noAxisItem'])) {
						$this->zbxTestLaunchPopup('yaxis_max_prototype', 'zbx_popup_item');
						$this->zbxTestClick("//span[text()='".$this->item."']");
						$this->selectWindow(null);
						sleep(1);
					}
					break;
				case 'Calculated':
					break;
			}
		}


		$this->zbxTestClickWait('save');

		switch ($data['expected']) {
			case TEST_GOOD:
				$this->zbxTestTextPresent('Graph added');
				$this->zbxTestCheckTitle('Configuration of graph prototypes');
				$this->zbxTestTextPresent(array('CONFIGURATION OF GRAPH PROTOTYPES', "Graph prototypes of ".$this->discoveryRule));
				break;

			case TEST_BAD:
				$this->zbxTestCheckTitle('Configuration of graph prototypes');
				$this->zbxTestTextPresent(array('CONFIGURATION OF GRAPH PROTOTYPES', 'Graph prototype'));
				foreach ($data['errors'] as $msg) {
					$this->zbxTestTextPresent($msg);
				}
				break;
		}

		if (isset($data['hostCheck'])) {
			$this->zbxTestOpenWait('hosts.php');
			$this->zbxTestClickWait('link='.$this->host);
			$this->zbxTestClickWait("link=Discovery rules");
			$this->zbxTestClickWait('link='.$this->discoveryRule);
			$this->zbxTestClickWait("link=Graph prototypes");

			$this->zbxTestTextPresent($this->template.": $graphName");
			$this->zbxTestClickWait("link=$graphName");

			$this->zbxTestTextPresent('Parent graphs');
			$this->assertElementValue('name', $graphName);
			$this->assertElementPresent("//span[text()='".$this->host.": ".$this->itemSimple."']");
			$this->assertElementPresent("//span[text()='".$this->host.": ".$this->item."']");
		}

		if (isset($data['dbCheck'])) {
			// template
			$result = DBselect("SELECT name, graphid FROM graphs where name = '".$graphName."' limit 1");
			while ($row = DBfetch($result)) {
				$this->assertEquals($row['name'], $graphName);
				$templateid = $row['graphid'];
			}

			// host
			$result = DBselect("SELECT name FROM graphs where name = '".$graphName."' AND templateid = ".$templateid."");
			while ($row = DBfetch($result)) {
				$this->assertEquals($row['name'], $graphName);
			}
		}

		if (isset($data['hostRemove'])) {
			$result = DBselect("SELECT graphid FROM graphs where name = '".$graphName."' limit 1");
			while ($row = DBfetch($result)) {
				$templateid = $row['graphid'];
			}

			$result = DBselect("SELECT graphid FROM graphs where name = '".$graphName."' AND templateid = ".$templateid."");
			while ($row = DBfetch($result)) {
				$graphId = $row['graphid'];
			}
			$this->zbxTestOpenWait('hosts.php');
			$this->zbxTestClickWait('link='.$this->host);
			$this->zbxTestClickWait("link=Discovery rules");
			$this->zbxTestClickWait('link='.$this->discoveryRule);
			$this->zbxTestClickWait("link=Graph prototypes");

			$this->assertElementPresent("group_graphid_$graphId");
			$this->assertAttribute("//input[@id='group_graphid_$graphId']/@disabled", 'disabled');
		}

		if (isset($data['remove'])) {
			$result = DBselect("SELECT graphid FROM graphs where name = '".$graphName."' limit 1");
			while ($row = DBfetch($result)) {
				$graphid = $row['graphid'];
			}

			$this->zbxTestOpenWait('templates.php');
			$this->zbxTestClickWait('link='.$this->template);
			$this->zbxTestClickWait("link=Discovery rules");
			$this->zbxTestClickWait('link='.$this->discoveryRule);
			$this->zbxTestClickWait("link=Graph prototypes");

			$this->zbxTestCheckboxSelect("group_graphid_$graphid");
			$this->zbxTestDropdownSelect('go', 'Delete selected');
			$this->zbxTestClick('goButton');

			$this->getConfirmation();
			$this->wait();
			$this->zbxTestTextPresent('Graphs deleted');
			$this->zbxTestTextNotPresent($this->template.": $graphName");
		}
	}

	/**
	 * Restore the original tables.
	 */
	public function testInheritanceGraphPrototype_Teardown() {
		DBrestore_tables('graphs');
	}
}
