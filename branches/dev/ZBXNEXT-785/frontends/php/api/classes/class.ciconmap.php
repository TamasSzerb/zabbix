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
/**
 * @package API
 */

/**
 * Class for operating icon maps.
 * Icon maps work only with map elements that represent hosts, and used for automatic changing
 * icons based on host inventory data.
 * Icon maps has read access for everyone and write access only for superamins
 */
class CIconMap extends CZBXAPI {

	/**
	 * Get IconMap data.
	 * @param array $options
	 * @param array $options['nodeids']
	 * @param array $options['iconmapids']
	 * @param array $options['sysmapids']
	 * @param array $options['editable']
	 * @param array $options['count']
	 * @param array $options['limit']
	 * @param array $options['order']
	 * @return array
	 */
	public function get(array $options = array()) {
		$result = array();

		// allowed columns for sorting
		$sort_columns = array('iconmapid', 'name');

		// allowed output options for [ select_* ] params
		$subselects_allowed_outputs = array(API_OUTPUT_REFER, API_OUTPUT_EXTEND);

		$sql_parts = array(
			'select' => array('icon_map' => 'im.iconmapid'),
			'from' => array('icon_map' => 'icon_map im'),
			'where' => array(),
			'order' => array(),
			'limit' => null,
		);

		$def_options = array(
			'nodeids' => null,
			'iconmapids' => null,
			'sysmapids' => null,
			'nopermissions' => null,
			'editable' => null,
// filter
			'filter' => null,
			'search' => null,
			'searchByAny' => null,
			'startSearch' => null,
			'excludeSearch' => null,
			'searchWildcardsEnabled' => null,
// OutPut
			'output' => API_OUTPUT_REFER,
			'selectMappings' => null,
			'countOutput' => null,
			'preservekeys' => null,

			'sortfield' => '',
			'sortorder' => '',
			'limit' => null
		);
		$options = zbx_array_merge($def_options, $options);

		if (is_array($options['output'])) {
			$dbTable = DB::getSchema('icon_map');

			foreach ($options['output'] as $field) {
				if (isset($dbTable['fields'][$field])) {
					$sql_parts['select'][$field] = 'im.'.$field;
				}
			}
			$options['output'] = API_OUTPUT_CUSTOM;
		}

		// editable + PERMISSION CHECK
		if (USER_TYPE_SUPER_ADMIN == self::$userData['type']) {
		}
		elseif (is_null($options['editable']) && (self::$userData['type'] == USER_TYPE_ZABBIX_ADMIN)) {
		}
		elseif (!is_null($options['editable']) || (self::$userData['type'] != USER_TYPE_SUPER_ADMIN)) {
			return array();
		}

		// nodeids
		$nodeids = !is_null($options['nodeids']) ? $options['nodeids'] : get_current_nodeid();

		// iconmapids
		if (!is_null($options['iconmapids'])) {
			zbx_value2array($options['iconmapids']);

			$sql_parts['where'][] = DBcondition('im.iconmapid', $options['iconmapids']);
		}

		// sysmapids
		if (!is_null($options['sysmapids'])) {
			zbx_value2array($options['sysmapids']);

			if ($options['output'] != API_OUTPUT_SHORTEN) {
				$sql_parts['select']['sysmapids'] = 's.sysmapid';
			}

			$sql_parts['from']['sysmaps'] = 'sysmaps s';
			$sql_parts['where'][] = DBcondition('s.sysmapid', $options['sysmapids']);
			$sql_parts['where']['ims'] = 'im.iconmapid=s.iconmapid';
		}

		// filter
		if (is_array($options['filter'])) {
			zbx_db_filter('icon_map im', $options, $sql_parts);
		}
		// search
		if (is_array($options['search'])) {
			zbx_db_search('icon_map im', $options, $sql_parts);
		}

		// output
		if ($options['output'] == API_OUTPUT_EXTEND) {
			$sql_parts['select']['icon_map'] = 'im.*';
		}

		// countOutput
		if (!is_null($options['countOutput'])) {
			$options['sortfield'] = '';

			$sql_parts['select'] = array('COUNT(DISTINCT im.iconmapid) as rowscount');
		}

		// order
		// restrict not allowed columns for sorting
		$options['sortfield'] = str_in_array($options['sortfield'], $sort_columns) ? $options['sortfield'] : '';
		if (!zbx_empty($options['sortfield'])) {
			$sortorder = ($options['sortorder'] == ZBX_SORT_DOWN) ? ZBX_SORT_DOWN : ZBX_SORT_UP;

			$sql_parts['order'][] = 'im.'.$options['sortfield'].' '.$sortorder;

			if (!str_in_array('im.'.$options['sortfield'], $sql_parts['select']) && !str_in_array('im.*', $sql_parts['select'])) {
				$sql_parts['select'][] = 'im.'.$options['sortfield'];
			}
		}

		// limit
		if (zbx_ctype_digit($options['limit']) && $options['limit']) {
			$sql_parts['limit'] = $options['limit'];
		}
		//---------------

		$iconMapids = array();

		$sql_parts['select'] = array_unique($sql_parts['select']);
		$sql_parts['from'] = array_unique($sql_parts['from']);
		$sql_parts['where'] = array_unique($sql_parts['where']);
		$sql_parts['order'] = array_unique($sql_parts['order']);

		$sql_select = '';
		$sql_from = '';
		$sql_where = '';
		$sql_order = '';
		if (!empty($sql_parts['select'])) {
			$sql_select .= implode(',', $sql_parts['select']);
		}
		if (!empty($sql_parts['from'])) {
			$sql_from .= implode(',', $sql_parts['from']);
		}
		if (!empty($sql_parts['where'])) {
			$sql_where .= ' AND '.implode(' AND ', $sql_parts['where']);
		}
		if (!empty($sql_parts['order'])) {
			$sql_order .= ' ORDER BY '.implode(',', $sql_parts['order']);
		}
		$sql_limit = $sql_parts['limit'];

		$sql = 'SELECT '.$sql_select.
				' FROM '.$sql_from.
				' WHERE '.DBin_node('im.iconmapid', $nodeids).
				$sql_where.
				$sql_order;
		//SDI($sql);
		$db_res = DBselect($sql, $sql_limit);
		while ($iconMap = DBfetch($db_res)) {

			if ($options['countOutput']) {
				$result = $iconMap['rowscount'];
			}
			else {
				$iconMapids[$iconMap['iconmapid']] = $iconMap['iconmapid'];

				if ($options['output'] == API_OUTPUT_SHORTEN) {
					$result[$iconMap['iconmapid']] = array('iconmapid' => $iconMap['iconmapid']);
				}
				else {
					if (!isset($result[$iconMap['iconmapid']])) {
						$result[$iconMap['iconmapid']] = array();
					}

					if (isset($iconMap['sysmapid'])) {
						if (!isset($result[$iconMap['iconmapid']]['sysmaps'])) {
							$result[$iconMap['iconmapid']]['sysmaps'] = array();
						}

						$result[$iconMap['iconmapid']]['sysmaps'][] = array('sysmapid' => $iconMap['sysmapid']);
					}

					if (!is_null($options['selectMappings']) && !isset($result[$iconMap['iconmapid']]['mappings'])) {
						$result[$iconMap['iconmapid']]['mappings'] = array();
					}

					$result[$iconMap['iconmapid']] += $iconMap;
				}
			}
		}

		if (!is_null($options['countOutput'])) {
			return $result;
		}

		// Adding Objects
		// Adding Conditions
		if (!is_null($options['selectMappings']) && str_in_array($options['selectMappings'], $subselects_allowed_outputs)) {
			$sql = 'SELECT imp.* FROM icon_mapping imp WHERE '.DBcondition('imp.iconmapid', $iconMapids);
			$res = DBselect($sql);
			while ($mapping = DBfetch($res)) {
				$result[$mapping['iconmapid']]['mappings'][$mapping['iconmappingid']] = $mapping;
			}
		}

		// removing keys (hash -> array)
		if (is_null($options['preservekeys'])) {
			$result = zbx_cleanHashes($result);
		}

		return $result;
	}

	/**
	 * Add IconMap.
	 * @param array $iconMaps
	 * @return array
	 */
	public function create(array $iconMaps) {
		if (USER_TYPE_SUPER_ADMIN != self::$userData['type']) {
			self::exception(ZBX_API_ERROR_PERMISSIONS, _('Only super admins can create icon maps.'));
		}

		$iconMaps = zbx_toArray($iconMaps);

		$iconMapRequiredFields = array(
			'name' => null
		);
		$duplicates = array();
		foreach ($iconMaps as $iconMap) {
			if (!check_db_fields($iconMapRequiredFields, $iconMap)) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _s('Incorrect parameter is used for icon map "%s".', $iconMap['name']));
			}

			if (isset($duplicates[$iconMap['name']])) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _s('Cannot create icon maps with identical name "%s".', $iconMap['name']));
			}
			else {
				$duplicates[$iconMap['name']] = $iconMap['name'];
			}
		}

		$this->validateMappings($iconMaps);

		$options = array(
			'filter' => array('name' => $duplicates),
			'output' => array('name'),
			'editable' => true,
			'nopermissions' => true
		);
		$dbIconMaps = $this->get($options);
		foreach ($dbIconMaps as $dbIconMap) {
			self::exception(ZBX_API_ERROR_PARAMETERS, _s('Icon map "%s" already exists.', $dbIconMap['name']));
		}

		$iconMapids = DB::insert('icon_map', $iconMaps);


		$mappings = array();
		foreach ($iconMaps as $imnum => $iconMap) {
			foreach ($iconMap['mappings'] as $mapping) {
				$mapping['iconmapid'] = $iconMapids[$imnum];
				$mappings[] = $mapping;
			}
		}
		DB::insert('icon_mapping', $mappings);

		return array('iconmapids' => $iconMapids);
	}

	/**
	 * Update IconMap.
	 * @param array $iconMaps
	 * @return array
	 */
	public function update(array $iconMaps) {
		if (USER_TYPE_SUPER_ADMIN != self::$userData['type']) {
			self::exception(ZBX_API_ERROR_PERMISSIONS, _('Only super admins can create icon maps.'));
		}

		$iconMaps = zbx_toArray($iconMaps);

		$iconMapIds = zbx_objectValues($iconMaps, 'iconmapid');
		$updates = array();

		$duplicates = array();
		foreach ($iconMaps as $iconMap) {
			if (!check_db_fields(array('iconmapid' => null), $iconMap)) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _s('Incorrect parameters for icon map update method "%s".', $iconMap['name']));
			}

			if (isset($iconMap['name'])) {
				if (zbx_empty($iconMap['name'])) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _s('Icon map name cannot be empty.'));
				}
				elseif (isset($duplicates[$iconMap['name']])) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _s('Cannot create icon maps with identical name "%s".', $iconMap['name']));
				}
				else {
					$duplicates[$iconMap['name']] = $iconMap['name'];
				}
			}
		}

		$this->validateMappings($iconMaps, false);


		$iconMapsUpd = API::IconMap()->get(array(
			'iconmapids' => $iconMapIds,
			'output' => API_OUTPUT_EXTEND,
			'preservekeys' => true,
			'selectMappings' => API_OUTPUT_EXTEND,
		));

		$mappingsCreate = $mappingsUpdate = $mappingIdsDelete = array();
		foreach ($iconMaps as $iconMap) {
			if (!isset($iconMapsUpd[$iconMap['iconmapid']])) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _s('Icon map with iconmapid "%s" does not exist.', $iconMap['iconmapid']));
			}

			// Existence
			if (isset($iconMap['name'])) {
				$options = array(
					'filter' => array('name' => $iconMap['name']),
					'output' => API_OUTPUT_SHORTEN,
					'editable' => 1,
					'nopermissions' => true,
					'preservekeys' => true,
				);
				$iconMapExists = $this->get($options);
				if (($iconMapExists = reset($iconMapExists)) && (bccomp($iconMapExists['iconmapid'], $iconMap['iconmapid']) != 0)) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _s('Icon map "%s" already exists.', $iconMap['name']));
				}
			}

			if (isset($iconMap['mappings'])) {
				$mappingsDb = $iconMapsUpd[$iconMap['iconmapid']]['mappings'];

				foreach ($iconMap['mappings'] as $mapping) {
					$mapping['iconmapid'] = $iconMap['iconmapid'];

					if (isset($mapping['iconmappingid']) && isset($mappingsDb[$mapping['iconmappingid']])) {
						$iconmappingid = $mapping['iconmappingid'];
						unset($mapping['iconmappingid']);
						$mappingsUpdate[] = array(
							'values' => $mapping,
							'where' => array('iconmappingid' => $iconmappingid),
						);
						unset($mappingsDb[$iconmappingid]);
					}
					else {
						$mappingsCreate[] = $mapping;
					}
				}

				$mappingIdsDelete = array_merge($mappingIdsDelete, array_keys($mappingsDb));
			}

			$iconMapid = $iconMap['iconmapid'];
			unset($iconMap['iconmapid']);
			if (!empty($iconMap)) {
				$updates[] = array(
					'values' => $iconMap,
					'where' => array('iconmapid' => $iconMapid),
				);
			}
		}

		DB::update('icon_map', $updates);
		DB::insert('icon_mapping', $mappingsCreate);
		DB::update('icon_mapping', $mappingsUpdate);
		if (!empty($mappingIdsDelete)) {
			DB::delete('icon_mapping', array('iconmappingid' => $mappingIdsDelete));
		}

		return array('iconmapids' => $iconMapIds);
	}

	/**
	 * Delete IconMap.
	 * @param array $iconmapids
	 * @return array
	 */
	public function delete($iconmapids) {
		$iconmapids = zbx_toArray($iconmapids);

		if (empty($iconmapids)) {
			self::exception(ZBX_API_ERROR_PARAMETERS, _('Empty input parameter'));
		}
		if (!$this->isWritable($iconmapids)) {
			self::exception(ZBX_API_ERROR_PERMISSIONS, _('No permissions to referred object or it does not exist!'));
		}

		$sql = 'SELECT m.name as mapname, im.name as iconmapname'.
				' FROM sysmaps m, icon_map im'.
				' WHERE m.iconmapid=im.iconmapid'.
					' AND '.DBcondition('m.iconmapid', $iconmapids);
		if ($names = DBfetch(DBselect($sql))) {
			self::exception(ZBX_API_ERROR_PARAMETERS,
				_s('Icon map "%1$s" cannot be deleted. Used in map "%2$s"',	$names['iconmapname'], $names['mapname'])
			);
		}

		DB::delete('icon_map', array('iconmapid' => $iconmapids));

		return array('iconmapids' => $iconmapids);
	}

	/**
	 * Check if user has read permissions for given icon map IDs.
	 * @param $ids
	 * @return bool
	 */
	public function isReadable($ids) {
		if (!is_array($ids)) {
			return false;
		}
		if (empty($ids)) {
			return true;
		}

		$ids = array_unique($ids);

		$count = $this->get(array(
			'nodeids' => get_current_nodeid(true),
			'iconmapids' => $ids,
			'output' => API_OUTPUT_SHORTEN,
			'countOutput' => true
		));

		return (count($ids) == $count);
	}

	/**
	 * Check if user has write permissions for given icon map IDs.
	 * @param $ids
	 * @return bool
	 */
	public function isWritable($ids) {
		if (!is_array($ids)) {
			return false;
		}
		if (empty($ids)) {
			return true;
		}

		$ids = array_unique($ids);

		$count = $this->get(array(
				'nodeids' => get_current_nodeid(true),
				'iconmapids' => $ids,
				'output' => API_OUTPUT_SHORTEN,
				'editable' => true,
				'countOutput' => true
			));

		return (count($ids) == $count);
	}

	/**
	 * Checks icon maps.
	 * @throws APIException
	 * @param $iconMaps
	 * @param bool $mustExist if icon map should be checked against having at least one mapping
	 * @return void
	 */
	protected function validateMappings($iconMaps, $mustExist = true) {
		$inventoryFields = getHostInventories();
		$imageIds = API::Image()->get(array(
			'output' => API_OUTPUT_SHORTEN,
			'preservekeys' => true,
			'filter' => array('imagetype' => IMAGE_TYPE_ICON),
		));

		foreach ($iconMaps as $iconMap) {
			if (isset($iconMap['mappings']) && empty($iconMap['mappings'])) {
				self::exception(ZBX_API_ERROR_PARAMETERS,
					_s('Icon map "%s" must have at least one mapping.', $iconMap['name']));
			}
			elseif (!isset($iconMap['mappings'])) {
				if ($mustExist) {
					self::exception(ZBX_API_ERROR_PARAMETERS,
						_s('Icon map "%s" must have at least one mapping.', $iconMap['name']));
				}
				else {
					continue;
				}
			}

			$uniqField = array();
			foreach ($iconMap['mappings'] as $mapping) {
				if (!isset($mapping['expression'])) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _('Required field "expression" is missing in icon mapping.'));
				}
				elseif (!isset($mapping['inventory_link'])) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _('Required field "inventory_link" is missing in icon mapping.'));
				}
				elseif (!isset($mapping['iconid'])) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _('Required field "iconid" is missing in icon mapping.'));
				}
				elseif (!isset($inventoryFields[$mapping['inventory_link']])) {
					self::exception(ZBX_API_ERROR_PARAMETERS,
						_s('Icon map "%1$s" has mapping with incorrect inventory link "%2$s".', $iconMap['name'], $mapping['inventory_link']));
				}
				elseif (!isset($imageIds[$mapping['iconid']])) {
					self::exception(ZBX_API_ERROR_PARAMETERS,
						_s('Icon map "%1$s" has mapping with incorrect iconid "%2$s".', $iconMap['name'], $mapping['iconid']));
				}

				try {
					GlobalRegExp::isValid($mapping['expression']);
				}
				catch(Exception $e) {
					switch ($e->getCode()) {
						case GlobalRegExp::ERROR_REGEXP_EMPTY:
							self::exception(ZBX_API_ERROR_PARAMETERS,
								_s('Icon map "%s" cannot have mapping with empty expression.', $iconMap['name']));
							break;
						case GlobalRegExp::ERROR_REGEXP_NOT_EXISTS:
							self::exception(ZBX_API_ERROR_PARAMETERS,
								_s('Icon map "%s" cannot have mapping with global expression that does not exist.', $iconMap['name']));
							break;
						default:
							self::exception(ZBX_API_ERROR_PARAMETERS,
								_s('Icon map "%s" has incorrect expression.', $iconMap['name']));
					}
				}

				if (isset($uniqField[$mapping['inventory_link'].$mapping['expression']])) {
					self::exception(ZBX_API_ERROR_PARAMETERS,
						_s('Icon mapping entry "%1$s" against "%2$s" already exists.',
							$mapping['expression'],
							$inventoryFields[$mapping['inventory_link']]['title'])
					);
				}
				$uniqField[$mapping['inventory_link'].$mapping['expression']] = true;
			}
		}
	}

}

?>
