<?php

/**
 * Abstract class to extend for all import formatters.
 * For each different version of configuration import new formatter should be defined. All forammetters must return
 * data in one format, so that forther processing is independent from configuration omport version.
 */
abstract class CImportFormatter {

	/**
	 * @var array configuration import data
	 */
	protected $data;

	/**
	 * Data property setter.
	 *
	 * @param array $data
	 */
	public function setData(array $data) {
		$this->data = $data;
	}

	/**
	 * Renames array elements keys according to given map.
	 *
	 * @param array $data
	 * @param array $fieldMap
	 *
	 * @return array
	 */
	protected function renameData(array $data, array $fieldMap) {
		foreach ($data as $key => $value) {
			if (isset($fieldMap[$key])) {
				$data[$fieldMap[$key]] = $value;
				unset($data[$key]);
			}
		}
		return $data;
	}

	/**
	 * @abstract
	 * @return array
	 */
	abstract public function getGroups();

	abstract public function getTemplates();

	abstract public function getHosts();

	abstract public function getApplications();

	abstract public function getItems();

	abstract public function getDiscoveryRules();

	abstract public function getGraphs();

	abstract public function getTriggers();

	abstract public function getImages();

	abstract public function getMaps();

	abstract public function getScreens();
}
