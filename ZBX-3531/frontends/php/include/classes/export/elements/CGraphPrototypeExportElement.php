<?php

class CGraphPrototypeExportElement extends CExportElement{

	public function __construct($graph) {
		parent::__construct('graph_prototype', $graph);

		$this->addYMinItemId($graph['ymin_itemid']);
		$this->addYMaxItemId($graph['ymax_itemid']);
		$this->addGraphItems($graph['gitems']);
	}

	protected function requiredFields() {
		return array('name', 'width', 'height', 'yaxismin', 'yaxismax', 'show_work_period', 'show_triggers',
			'graphtype', 'show_legend', 'show_3d', 'percent_left', 'percent_left', 'percent_right', 'ymin_type', 'ymax_type');
	}

	protected function addYMinItemId(array $yMinItemId) {
		$this->addElement(new CExportElement('ymin_item', $yMinItemId));
	}

	protected function addYMaxItemId(array $yMaxItemId) {
		$this->addElement(new CExportElement('ymax_item', $yMaxItemId));
	}

	protected function addGraphItems(array $graphItems) {
		$graphItemsElement = new CExportElement('graph_items');
		foreach ($graphItems as $graphItem) {
			$graphItemsElement->addElement(new CGraphItemExportElement($graphItem));
		}
		$this->addElement($graphItemsElement);
	}

}
