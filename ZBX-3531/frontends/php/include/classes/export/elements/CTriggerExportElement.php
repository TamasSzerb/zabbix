<?php

class CTriggerExportElement extends CNodeExportElement{

	public function __construct($trigger) {
		$requiredField = array('expression', 'description', 'url', 'status', 'value', 'priority', 'comments',
			'type', 'comments');
		$trigger = ArrayHelper::getByKeys($trigger, $requiredField);
		parent::__construct('trigger', $trigger);
	}

}
