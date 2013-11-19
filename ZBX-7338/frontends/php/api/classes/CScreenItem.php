<?php
/*
** Zabbix
** Copyright (C) 2001-2013 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


/**
 * Class containing methods for operations with screen items.
 *
 * @package API
 */
class CScreenItem extends CZBXAPI {

	protected $tableName = 'screens_items';
	protected $tableAlias = 'si';

	/**
	 * Supported values for the resourcetype column.
	 *
	 * @var array
	 */
	protected static $resourceTypes = array(
		SCREEN_RESOURCE_GRAPH,
		SCREEN_RESOURCE_SIMPLE_GRAPH,
		SCREEN_RESOURCE_MAP,
		SCREEN_RESOURCE_PLAIN_TEXT,
		SCREEN_RESOURCE_HOSTS_INFO,
		SCREEN_RESOURCE_TRIGGERS_INFO,
		SCREEN_RESOURCE_SERVER_INFO,
		SCREEN_RESOURCE_CLOCK,
		SCREEN_RESOURCE_SCREEN,
		SCREEN_RESOURCE_TRIGGERS_OVERVIEW,
		SCREEN_RESOURCE_DATA_OVERVIEW,
		SCREEN_RESOURCE_URL,
		SCREEN_RESOURCE_ACTIONS,
		SCREEN_RESOURCE_EVENTS,
		SCREEN_RESOURCE_HOSTGROUP_TRIGGERS,
		SCREEN_RESOURCE_SYSTEM_STATUS,
		SCREEN_RESOURCE_HOST_TRIGGERS
	);

	protected $sortColumns = array(
		'screenitemid',
		'screenid'
	);

	public function __construct() {
		parent::__construct();

		$this->getOptions = zbx_array_merge($this->getOptions, array(
			'screenitemids'	=> null,
			'screenids'		=> null,
			'editable'		=> null,
			'sortfield'		=> '',
			'sortorder'		=> '',
			'preservekeys'	=> null,
			'countOutput'	=> null
		));
	}

	/**
	 * Get screem item data.
	 *
	 * @param array $options
	 * @param array $options['nodeids']			Node IDs
	 * @param array $options['screenitemids']	Search by screen item IDs
	 * @param array $options['screenids']		Search by screen IDs
	 * @param array $options['filter']			Result filter
	 * @param array $options['limit']			The size of the result set
	 *
	 * @return array|bool						Host data as array or false if error
	 */
	public function get(array $options = array()) {
		$options = zbx_array_merge($this->getOptions, $options);

		// build and execute query
		$sql = $this->createSelectQuery($this->tableName(), $options);
		$res = DBselect($sql, $options['limit']);

		// fetch results
		$result = array();
		while ($row = DBfetch($res)) {
			// count query, return a single result
			if ($options['countOutput'] !== null) {
				$result = $row['rowscount'];
			}
			// normal select query
			else {
				if ($options['preservekeys'] !== null) {
					$result[$row['screenitemid']] = $row;
				}
				else {
					$result[] = $row;
				}
			}
		}

		return $result;
	}

	/**
	 * Create screen items.
	 *
	 * @param array $screenItems	An array of screen items
	 *
	 * @return array
	 */
	public function create(array $screenItems) {
		$screenItems = zbx_toArray($screenItems);

		$this->validateCreate($screenItems);

		$screenItemIds = DB::insert($this->tableName(), $screenItems);

		return array('screenitemids' => $screenItemIds);
	}

	/**
	 * Validates the input parameters for the create() method.
	 *
	 * @throws APIException if the input is invalid
	 *
	 * @param array $screenItems
	 */
	protected function validateCreate(array $screenItems) {
		$screenItemDBfields = array(
			'screenid' => null,
			'resourceid' => null,
			'resourcetype' => null
		);

		foreach ($screenItems as $screenItem) {
			if (!check_db_fields($screenItemDBfields, $screenItem)) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _('Invalid method parameters.'));
			}
		}

		$screenIds = array_keys(array_flip(zbx_objectValues($screenItems, 'screenid')));

		$dbScreens = API::Screen()->get(array(
			'screenids' => $screenIds,
			'output' => array('screenid', 'hsize', 'vsize'),
			'editable' => true,
			'preservekeys' => true
		));

		$dbScreenItems = $this->get(array(
			'screenids' => $screenIds,
			'output' => array('screenitemid', 'screenid', 'x', 'y', 'rowspan', 'colspan'),
			'editable' => true,
			'preservekeys' => true
		));

		foreach ($screenItems as $screenItem) {
			$this->checkDuplicateResourceInCell($screenItem, $dbScreenItems);
			$this->checkSpans($screenItem);
			$this->checkSpansInBounds($screenItem, $dbScreens[$screenItem['screenid']]);
		}

		// check duplicate resource in cell for new screen items
		foreach ($screenItems as $key => $screenItem) {
			$screenItemsTmp = $screenItems;
			unset($screenItemsTmp[$key]);

			$this->checkDuplicateResourceInCell($screenItem, $screenItemsTmp);
		}

		$this->checkInput($screenItems, $dbScreenItems);
	}

	/**
	 * Updates screen items.
	 *
	 * @param array $screenItems	An array of screen items
	 *
	 * @return array
	 */
	public function update(array $screenItems) {
		$screenItems = zbx_toArray($screenItems);

		$this->validateUpdate($screenItems);

		$update = array();
		$screenItemIds = array();

		foreach ($screenItems as $screenItem) {
			$screenItemId = $screenItem['screenitemid'];
			unset($screenItem['screenitemid']);

			$update[] = array(
				'values' => $screenItem,
				'where' => array('screenitemid' => $screenItemId)
			);

			$screenItemIds[] = $screenItemId;
		}

		DB::update($this->tableName(), $update);

		return array('screenitemids' => $screenItemIds);
	}

	/**
	 * Validates the input parameters for the update() method.
	 *
	 * @throws APIException
	 *
	 * @param array $screenItems
	 */
	protected function validateUpdate(array $screenItems) {
		foreach ($screenItems as $screenItem) {
			if (empty($screenItem['screenitemid'])) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _('Invalid method parameters.'));
			}
		}

		$screenItemIds = zbx_objectValues($screenItems, 'screenitemid');

		$dbScreens = API::Screen()->get(array(
			'screenitemids' => $screenItemIds,
			'output' => array('screenid', 'hsize', 'vsize'),
			'editable' => true,
			'preservekeys' => true
		));

		$dbScreenItems = $this->get(array(
			'screenitemids' => $screenItemIds,
			'output' => array('screenitemid', 'screenid', 'x', 'y', 'rowspan', 'colspan'),
			'editable' => true,
			'preservekeys' => true
		));

		$screenItems = $this->extendObjects($this->tableName(), $screenItems, array('screenid', 'x', 'y', 'rowspan', 'colspan'));

		foreach ($screenItems as $screenItem) {
			$this->checkDuplicateResourceInCell($screenItem, $dbScreenItems);
			$this->checkSpans($screenItem);
			$this->checkSpansInBounds($screenItem, $dbScreens[$screenItem['screenid']]);
		}

		$this->checkInput($screenItems, $dbScreenItems);
	}

	/**
	 * Update screen items using the given 'x' and 'y' parameters.
	 * If the given cell is free, a new screen item will be created.
	 *
	 * @param array $screenItems	An array of screen items with the given X and Y coordinates
	 *
	 * @return array
	 */
	public function updateByPosition(array $screenItems) {
		$screenItems = zbx_toArray($screenItems);

		$screenItemDBfields = array(
			'screenid' => null,
			'x' => null,
			'y' => null
		);

		foreach ($screenItems as $screenItem) {
			if (!check_db_fields($screenItemDBfields, $screenItem)) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _('Invalid method parameters.'));
			}
		}

		$dbScreenItems = $this->get(array(
			'screenids' => zbx_objectValues($screenItems, 'screenid'),
			'output' => array('screenitemid', 'screenid', 'x', 'y'),
			'editable' => true,
			'preservekeys' => true
		));

		$screens = array();

		foreach ($screenItems as $screenItem) {
			$found = false;

			foreach ($dbScreenItems as $dbScreenItem) {
				if ($screenItem['screenid'] == $dbScreenItem['screenid']
						&& $screenItem['x'] == $dbScreenItem['x'] && $screenItem['y'] == $dbScreenItem['y']) {
					$found = true;

					$screenItem['screenitemid'] = $dbScreenItem['screenitemid'];
					$screens[$screenItem['screenid']]['screenid'] = $screenItem['screenid'];
					$screens[$screenItem['screenid']]['screenitems'][] = $screenItem;

					break;
				}
			}

			if (!$found) {
				$screens[$screenItem['screenid']]['screenid'] = $screenItem['screenid'];
				$screens[$screenItem['screenid']]['screenitems'][] = $screenItem;
			}
		}

		API::Screen()->update($screens);

		return array('screenitemids' => zbx_objectValues($dbScreenItems, 'screenitemid'));
	}

	/**
	 * Deletes screen items.
	 *
	 * @param array $screenItemIds
	 *
	 * @return array
	 */
	public function delete($screenItemIds) {
		$screenItemIds = zbx_toArray($screenItemIds);

		// check permissions
		$dbScreenItems = $this->get(array(
			'screenitemids' => $screenItemIds,
			'preservekeys' => true
		));
		foreach ($screenItemIds as $screenItemId) {
			if (!isset($dbScreenItems[$screenItemId])) {
				self::exception(ZBX_API_ERROR_PERMISSIONS, _('No permissions to referred object or it does not exist!'));
			}
		}

		// delete screen items
		DB::delete($this->tableName(), array(
			'screenitemid' => $screenItemIds
		));

		return array('screenitemids' => $screenItemIds);
	}

	/**
	 * Returns true if the given screen items exist and are available for reading.
	 *
	 * @param array $screenItemIds
	 *
	 * @return bool
	 */
	public function isReadable(array $screenItemIds) {
		if (!is_array($screenItemIds)) {
			return false;
		}
		elseif (empty($screenItemIds)) {
			return true;
		}

		$screenItemIds = array_unique($screenItemIds);

		$count = $this->get(array(
			'screenitemids' => $screenItemIds,
			'countOutput' => true
		));

		return (count($screenItemIds) == $count);
	}

	/**
	 * Returns true if the given screen items exist and are available for writing.
	 *
	 * @param array $screenItemIds	An array if screen item IDs
	 *
	 * @return bool
	 */
	public function isWritable(array $screenItemIds) {
		if (!is_array($screenItemIds)) {
			return false;
		}
		elseif (empty($screenItemIds)) {
			return true;
		}

		$screenItemIds = array_unique($screenItemIds);

		$count = $this->get(array(
			'screenitemids' => $screenItemIds,
			'editable' => true,
			'countOutput' => true
		));

		return (count($screenItemIds) == $count);
	}

	/**
	 * Validates screen items.
	 *
	 * If the $dbScreenItems parameter is given, the screen items will be matched
	 * against the ones given in $dbScreenItems. If a screen item is not present in
	 * $dbScreenItems, a ZBX_API_ERROR_PERMISSIONS exception will be thrown.
	 *
	 * @throws APIException if a validation error occurred.
	 *
	 * @param array $screenItems	An array of screen items to validate
	 * @param array $dbScreenItems	An array of screen items $screenItems should be matched against
	 */
	protected function checkInput(array $screenItems, array $dbScreenItems = array()) {
		$hostGroupsIds = $hostIds = $graphIds = $itemIds = $mapIds = $screenIds = array();

		foreach ($screenItems as $screenItem) {
			// check if the item is editable
			if (isset($screenItem['screenitemid']) && !isset($dbScreenItems[$screenItem['screenitemid']])) {
				self::exception(ZBX_API_ERROR_PERMISSIONS, _('No permissions to referred object or it does not exist!'));
			}

			// check resource type
			if (isset($screenItem['resourcetype'])) {
				if (!$this->isValidResourceType($screenItem['resourcetype'])) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _('Incorrect resource type provided for screen item.'));
				}

				// host groups
				$hostGroupResourceTypes = array(
					SCREEN_RESOURCE_HOSTS_INFO,
					SCREEN_RESOURCE_TRIGGERS_INFO,
					SCREEN_RESOURCE_TRIGGERS_OVERVIEW,
					SCREEN_RESOURCE_HOSTGROUP_TRIGGERS,
					SCREEN_RESOURCE_DATA_OVERVIEW
				);
				if (in_array($screenItem['resourcetype'], $hostGroupResourceTypes)) {
					if (in_array($screenItem['resourcetype'], array(SCREEN_RESOURCE_TRIGGERS_OVERVIEW, SCREEN_RESOURCE_DATA_OVERVIEW))) {
						if (!isset($screenItem['resourceid']) || zbx_empty($screenItem['resourceid'])) {
							self::exception(ZBX_API_ERROR_PARAMETERS, _('No host group ID provided for screen element.'));
						}
					}
					else {
						if (isset($screenItem['resourceid']) && $screenItem['resourceid']) {
							$hostGroupsIds[$screenItem['resourceid']] = $screenItem['resourceid'];
						}
					}
				}

				// host triggers
				elseif ($screenItem['resourcetype'] == SCREEN_RESOURCE_HOST_TRIGGERS
							&& isset($screenItem['resourceid']) && $screenItem['resourceid']) {
					$hostIds[$screenItem['resourceid']] = $screenItem['resourceid'];
				}

				// graphs
				elseif ($screenItem['resourcetype'] == SCREEN_RESOURCE_GRAPH) {
					if (!isset($screenItem['resourceid']) || zbx_empty($screenItem['resourceid'])) {
						self::exception(ZBX_API_ERROR_PARAMETERS, _('No graph ID provided for screen element.'));
					}

					$graphIds[$screenItem['resourceid']] = $screenItem['resourceid'];
				}

				// simple graphs
				// plain texts
				// clocks
				// hosts
				elseif ((in_array($screenItem['resourcetype'], array(SCREEN_RESOURCE_SIMPLE_GRAPH, SCREEN_RESOURCE_PLAIN_TEXT))
							|| $screenItem['resourcetype'] == SCREEN_RESOURCE_CLOCK)
								&& $screenItem['style'] == TIME_TYPE_HOST) {
					if (!isset($screenItem['resourceid']) || zbx_empty($screenItem['resourceid'])) {
						self::exception(ZBX_API_ERROR_PARAMETERS, _('No item ID provided for screen element.'));
					}

					$itemIds[$screenItem['resourceid']] = $screenItem['resourceid'];
				}

				// maps
				elseif ($screenItem['resourcetype'] == SCREEN_RESOURCE_MAP) {
					if (!isset($screenItem['resourceid']) || zbx_empty($screenItem['resourceid'])) {
						self::exception(ZBX_API_ERROR_PARAMETERS, _('No map ID provided for screen element.'));
					}

					$mapIds[$screenItem['resourceid']] = $screenItem['resourceid'];
				}

				// screens
				elseif ($screenItem['resourcetype'] == SCREEN_RESOURCE_SCREEN) {
					if (!isset($screenItem['resourceid']) || zbx_empty($screenItem['resourceid'])) {
						self::exception(ZBX_API_ERROR_PARAMETERS, _('No screen ID provided for screen element.'));
					}

					$screenIds[$screenItem['resourceid']] = $screenItem['resourceid'];
				}

				// urls
				elseif ($screenItem['resourcetype'] == SCREEN_RESOURCE_URL) {
					if (!isset($screenItem['url']) || zbx_empty($screenItem['url'])) {
						self::exception(ZBX_API_ERROR_PARAMETERS, _('No URL provided for screen element.'));
					}
				}

				// check fields specific to each resource type
				// check "Show lines" field
				if (isset($screenItem['elements'])) {
					switch ($screenItem['resourcetype']) {
						case SCREEN_RESOURCE_ACTIONS:
						case SCREEN_RESOURCE_EVENTS:
						case SCREEN_RESOURCE_HOSTGROUP_TRIGGERS:
						case SCREEN_RESOURCE_HOST_TRIGGERS:
						case SCREEN_RESOURCE_PLAIN_TEXT:
						case SCREEN_RESOURCE_URL:
							if ($screenItem['elements'] < 1 || $screenItem['elements'] > 100) {
								self::exception(ZBX_API_ERROR_PARAMETERS,
									_s('Incorrect value "%1$s" for "%2$s" field: must be between %3$s and %4$s.',
										$screenItem['elements'], 'elements', 1, 100
									)
								);
							}
							break;
					}
				}
			}
		}

		// check host groups
		if ($hostGroupsIds) {
			$dbHostGroups = API::HostGroup()->get(array(
				'groupids' => $hostGroupsIds,
				'output' => array('groupid'),
				'preservekeys' => true
			));
			foreach ($hostGroupsIds as $hostGroupsId) {
				if (!isset($dbHostGroups[$hostGroupsId])) {
					self::exception(ZBX_API_ERROR_PERMISSIONS,
						_s('Incorrect host group ID "%s" provided for screen element.', $hostGroupsId));
				}
			}
		}

		// check hosts
		if ($hostIds) {
			$dbHosts = API::Host()->get(array(
				'hostids' => $hostIds,
				'output' => array('hostid'),
				'preservekeys' => true
			));
			foreach ($hostIds as $hostId) {
				if (!isset($dbHosts[$hostId])) {
					self::exception(ZBX_API_ERROR_PERMISSIONS,
						_s('Incorrect host ID "%s" provided for screen element.', $hostId));
				}
			}
		}

		// check graphs
		if ($graphIds) {
			$dbGraphs = API::Graph()->get(array(
				'graphids' => $graphIds,
				'output' => array('graphid'),
				'preservekeys' => true
			));
			foreach ($graphIds as $graphId) {
				if (!isset($dbGraphs[$graphId])) {
					self::exception(ZBX_API_ERROR_PERMISSIONS,
						_s('Incorrect graph ID "%s" provided for screen element.', $graphId));
				}
			}
		}

		// check items
		if ($itemIds) {
			$dbItems = API::Item()->get(array(
				'itemids' => $itemIds,
				'output' => array('itemid'),
				'preservekeys' => true,
				'webitems' => true
			));
			foreach ($itemIds as $itemId) {
				if (!isset($dbItems[$itemId])) {
					self::exception(ZBX_API_ERROR_PERMISSIONS,
						_s('Incorrect item ID "%s" provided for screen element.', $itemId));
				}
			}
		}

		// check maps
		if ($mapIds) {
			$dbMaps = API::Map()->get(array(
				'sysmapids' => $mapIds,
				'output' => array('sysmapid'),
				'preservekeys' => true
			));
			foreach ($mapIds as $mapId) {
				if (!isset($dbMaps[$mapId])) {
					self::exception(ZBX_API_ERROR_PERMISSIONS,
						_s('Incorrect map ID "%s" provided for screen element.', $mapId));
				}
			}
		}

		// check screens
		if ($screenIds) {
			$dbScreens = API::Screen()->get(array(
				'screenids' => $screenIds,
				'output' => array('screenid'),
				'preservekeys' => true
			));
			if (empty($dbScreens)) {
				self::exception(ZBX_API_ERROR_PERMISSIONS,
					_s('Incorrect screen ID "%s" provided for screen element.', reset($screenIds)));
			}
		}
	}

	/**
	 * Returns true if the given resource type is supported.
	 *
	 * @param int $resourceType
	 *
	 * @return bool
	 */
	protected function isValidResourceType($resourceType) {
		return in_array($resourceType, self::$resourceTypes);
	}

	/**
	 * Checks that the row and column spans are valid.
	 *
	 * @throws APIException if the any of the spans is not an integer or missing
	 *
	 * @param array $screenItem
	 */
	protected function checkSpans(array $screenItem) {
		if (isset($screenItem['rowspan'])) {
			if (!zbx_is_int($screenItem['rowspan']) || $screenItem['rowspan'] < 0) {
				self::exception(ZBX_API_ERROR_PARAMETERS,
					_s('Incorrect row span provided for screen element located at X - %1$s and Y - %2$s.',
						$screenItem['x'], $screenItem['y'])
				);
			}
		}

		if (isset($screenItem['colspan'])) {
			if (!zbx_is_int($screenItem['colspan']) || $screenItem['colspan'] < 0) {
				self::exception(ZBX_API_ERROR_PARAMETERS,
					_s('Incorrect column span provided for screen element located at X - %1$s and Y - %2$s.',
						$screenItem['x'], $screenItem['y'])
				);
			}
		}
	}

	/**
	 * Checks that the row and column spans fit into the size of the screen.
	 *
	 * @throws APIException if the any of the spans is bigger then the free space on the screen
	 *
	 * @param array $screenItem
	 * @param array $screen
	 */
	protected function checkSpansInBounds(array $screenItem, array $screen) {
		if (isset($screenItem['rowspan']) && isset($screen['vsize']) && isset($screenItem['y'])
				&& $screenItem['rowspan'] > $screen['vsize'] - $screenItem['y']) {
			self::exception(ZBX_API_ERROR_PARAMETERS,
				_s('Row span of screen element located at X - %1$s and Y - %2$s is too big.',
					$screenItem['x'], $screenItem['y'])
			);
		}

		if (isset($screenItem['colspan']) && isset($screen['hsize']) && isset($screenItem['x'])
				&& $screenItem['colspan'] > $screen['hsize'] - $screenItem['x']) {
			self::exception(ZBX_API_ERROR_PARAMETERS,
				_s('Column span of screen element located at X - %1$s and Y - %2$s is too big.',
					$screenItem['x'], $screenItem['y'])
			);
		}
	}

	/**
	 * Check duplicates screen items in one cell.
	 *
	 * @throws APIException
	 *
	 * @param array $screenItem
	 * @param array $dbScreenItems
	 */
	protected function checkDuplicateResourceInCell($screenItem, $dbScreenItems) {
		if (isset($screenItem['x']) && isset($screenItem['y'])) {
			foreach ($dbScreenItems as $dbScreenItem) {
				if ($dbScreenItem['screenid'] == $screenItem['screenid']
						&& strcmp($dbScreenItem['x'], $screenItem['x']) == 0
						&& strcmp($dbScreenItem['y'], $screenItem['y']) == 0) {
					if (!(isset($screenItem['screenitemid'])
							&& bccomp($dbScreenItem['screenitemid'], $screenItem['screenitemid']) == 0)) {
						self::exception(ZBX_API_ERROR_PARAMETERS,
							_s('Screen item in same cell X - %1$s Y - %2$s already exists.', $screenItem['x'], $screenItem['y']));
					}
				}
			}
		}
	}

	protected function applyQueryNodeOptions($tableName, $tableAlias, array $options, array $sqlParts) {
		// only apply the node option if no specific screen ids are given
		if ($options['screenids'] === null) {
			$sqlParts = parent::applyQueryNodeOptions($tableName, $tableAlias, $options, $sqlParts);
		}

		return $sqlParts;
	}

	protected function applyQueryFilterOptions($tableName, $tableAlias, array $options, array $sqlParts) {
		$sqlParts = parent::applyQueryFilterOptions($tableName, $tableAlias, $options, $sqlParts);

		// screens
		if ($options['screenids'] !== null) {
			zbx_value2array($options['screenids']);
			$sqlParts = $this->addQuerySelect($this->fieldId('screenid'), $sqlParts);
			$sqlParts['where'][] = dbConditionInt($this->fieldId('screenid'), $options['screenids']);
		}

		return $sqlParts;
	}
}
