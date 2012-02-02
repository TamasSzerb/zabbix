<?php

class CExportElement {

	/**
	 * @var array
	 */
	protected $data;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var array
	 */
	protected $childElements = array();


	/**
	 * @param string $name element name
	 * @param array  $data
	 */
	public function __construct($name, array $data = array()) {
		$this->name = $name;

		$this->data = $data;

		$this->cleanData();
		$this->renameData();
	}

	/**
	 * Add child element.
	 *
	 * @param CExportElement $element
	 */
	public function addElement(CExportElement $element) {
		$this->childElements[] = $element;
	}

	/**
	 * Get child elements.
	 *
	 * @return array
	 */
	public function getChilds() {
		return $this->childElements;
	}

	/**
	 * Get element name.
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Get element data.
	 *
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Convert element to array representation.
	 *
	 * @return array
	 */
	public function toArray() {
		$array = $this->getData();
		$childs = $this->getChilds();

		$namesList = array();
		$duplicateNames = false;
		foreach ($childs as $child) {
			if (isset($namesList[$child->getName()])) {
				$duplicateNames = true;
				break;
			}
			$namesList[$child->getName()] = 1;
		}

		if (count($childs) <= 1 && empty($array)) {
			$duplicateNames = true;
		}

		foreach ($childs as $child) {
			if ($duplicateNames) {
				$array[] = $child->toArray();
			}
			else {
				$array[$child->getName()] = $child->toArray();
			}
		}

		return $array;
	}

	/**
	 * Remove not needed field values from element's data.
	 */
	protected function cleanData() {
		$requiredFields = $this->requiredFields();
		$referenceFields = $this->referenceFields();
		foreach ($referenceFields as $field) {
			if (isset($this->data[$field])) {
				$requiredFields[] = $field;
			}
		}
		if ($requiredFields) {
			$this->data = ArrayHelper::getByKeys($this->data, $requiredFields);
		}
	}

	/**
	 * Rename some fields according to value map.
	 */
	protected function renameData() {
		$fieldMap = $this->fieldNameMap();
		foreach ($this->data as $key => $value) {
			if (isset($fieldMap[$key])) {
				$this->data[$fieldMap[$key]] = $value;
				unset($this->data[$key]);
			}
		}
	}

	protected function requiredFields() {
		return array();
	}

	protected function referenceFields() {
		return array();
	}

	protected function fieldNameMap() {
		return array();
	}

}
