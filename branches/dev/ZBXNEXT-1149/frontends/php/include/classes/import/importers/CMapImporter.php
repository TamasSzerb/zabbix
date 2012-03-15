<?php

class CMapImporter extends CImporter {

	/**
	 * Import maps.
	 *
	 * @param array $maps
	 *
	 * @return void
	 */
	public function import(array $maps) {
		$maps = zbx_toHash($maps, 'name');

		$existingMaps = $this->findExistingMaps(array_keys($maps));
		$this->checkUpdatePermissions($existingMaps);

		do {
			$im = $this->getIndependentMaps($maps);

			$mapsToCreate = array();
			$mapsToUpdate = array();
			foreach ($im as $name) {
				$map = $maps[$name];
				unset($maps[$name]);

				$map = $this->resolveMapReferences($map);

				if (isset($existingMaps[$map['name']])) {
					$map['sysmapid'] = $existingMaps[$map['name']];
					$mapsToUpdate[] = $map;
				}
				else {
					$mapsToCreate[] = $map;
				}
			}

			if ($this->options['maps']['createMissing'] && $mapsToCreate) {
				$newMapIds = API::Map()->create($mapsToCreate);
				foreach ($mapsToCreate as $num => $map) {
					$mapId = $newMapIds['sysmapids'][$num];
					$this->referencer->addMapRef($map['name'], $mapId);
				}
			}
			if ($this->options['maps']['updateExisting'] && $mapsToUpdate) {
				API::Map()->update($mapsToUpdate);
			}


		} while (!empty($im));


		// if any dependent maps left, we try to resolve their references to find unresoled ones
		foreach ($maps as $map) {
			$this->resolveMapReferences($map);
		}
	}

	/**
	 * Get maps that don't have map elements that reference not existing map i.e. map elements references can be resolved.
	 * Returns array with map names.
	 *
	 * @param array $maps
	 *
	 * @return array
	 */
	protected function getIndependentMaps(array $maps) {
		foreach ($maps as $num => $map) {
			if (!isset($map['selements'])) {
				continue;
			}

			foreach ($map['selements'] as $selement) {
				if ($selement['elementtype'] == SYSMAP_ELEMENT_TYPE_MAP) {
					if (!$this->referencer->resolveMap($selement['element']['name'])) {
						unset($maps[$num]);
						continue 2;
					}
				}
			}
		}

		return zbx_objectValues($maps, 'name');
	}

	/**
	 * Check if user has permissions for maps that already exist in database.
	 * Permissions are checked only if import updates existing maps.
	 *
	 * @throws Exception
	 *
	 * @param array $existingMaps hash with name as key and sysmapid as value
	 *
	 * @return void
	 */
	protected function checkUpdatePermissions(array $existingMaps) {
		if ($existingMaps && $this->options['maps']['updateExisting']) {
			$allowedMaps = API::Map()->get(array(
				'sysmapids' => $existingMaps,
				'output' => API_OUTPUT_SHORTEN,
				'editable' => true,
				'preservekeys' => true
			));
			foreach ($existingMaps as $existingMapName => $existingMapId ) {
				if (!isset($allowedMaps[$existingMapId])) {
					throw new Exception(_s('No permissions for map "%1$s".', $existingMapName));
				}
			}
		}
	}

	/**
	 * Change all references in map to database ids.
	 *
	 * @throws Exception
	 *
	 * @param array $map
	 *
	 * @return array
	 */
	protected function resolveMapReferences(array $map) {
		// resolve icon map
		if (!empty($map['iconmap'])) {
			$map['iconmapid'] = $this->referencer->resolveIconMap($map['iconmap']['name']);
			if (!$map['iconmapid']) {
				throw new Exception(_s('Cannot find icon map "%1$s" for map "%2$s".', $map['iconmap']['name'], $map['name']));
			}
		}

		if (!empty($map['background'])) {
			$image = getImageByIdent($map['background']);

			if (!$image) {
				throw new Exception(_s('Cannot find background image for map "%1$s.', $map['name']));
			}
			$map['backgroundid'] = $image['imageid'];
		}


		if (isset($map['selements'])) {
			foreach ($map['selements'] as &$selement) {
				switch ($selement['elementtype']) {
					case SYSMAP_ELEMENT_TYPE_MAP:
						$selement['elementid'] = $this->referencer->resolveMap($selement['element']['name']);
						if (!$selement['elementid']) {
							throw new Exception(_s('Cannot find map "%1$s" used in map %2$s".',
								$selement['element']['name'], $map['name']));
						}
						break;

					case SYSMAP_ELEMENT_TYPE_HOST_GROUP:
						$selement['elementid'] = $this->referencer->resolveGroup($selement['element']['name']);
						if (!$selement['elementid']) {
							throw new Exception(_s('Cannot find group "%1$s" used in map %2$s".',
								$selement['element']['name'], $map['name']));
						}
						break;

					case SYSMAP_ELEMENT_TYPE_HOST:
						$selement['elementid'] = $this->referencer->resolveHost($selement['element']['host']);
						if (!$selement['elementid']) {
							throw new Exception(_s('Cannot find host "%1$s" used in map %2$s".',
								$selement['element']['host'], $map['name']));
						}
						break;

					case SYSMAP_ELEMENT_TYPE_TRIGGER:
						$el = $selement['element'];
						$selement['elementid'] = $this->referencer->resolveHost($el['description'], $el['expression']);
						if (!$selement['elementid']) {
							throw new Exception(_s('Cannot find trigger "%1$s" used in map %2$s".',
								$selement['element']['description'], $map['name']));
						}
						break;
				}

				$icons = array(
					'icon_off' => 'iconid_off',
					'icon_on' => 'iconid_on',
					'icon_disabled' => 'iconid_disabled',
					'icon_maintenance' => 'iconid_maintenance',
				);
				foreach ($icons as $element => $field) {
					if (!empty($selement[$element])) {
						$image = getImageByIdent($selement[$element]);
						if (!$image) {
							throw new Exception(_s('Cannot find icon "%1$s" for map "%2$s".',
								$selement[$element]['name'], $map['name']));
						}
						$selement[$field] = $image['imageid'];
					}
				}
			}
			unset($selement);
		}


		if (isset($map['links'])) {
			foreach ($map['links'] as &$link) {
				if (empty($link['linktriggers'])) {
					unset($link['linktriggers']);
					continue;
				}

				foreach ($link['linktriggers'] as &$linktrigger) {
					$dbTriggers = API::Trigger()->getObjects($linktrigger['trigger']);
					if (empty($dbTriggers)) {
						throw new Exception(_s('Cannot find trigger "%1$s" for map "%2$s".',
							$linktrigger['trigger']['description'], $map['name']));
					}

					$tmp = reset($dbTriggers);
					$linktrigger['triggerid'] = $tmp['triggerid'];
				}
				unset($linktrigger);
			}
			unset($link);
		}

		return $map;
	}

	/**
	 * Get maps that exist in database.
	 * Return array with name as key and sysmapid as value.
	 *
	 * @param array $mapNames
	 *
	 * @return array
	 */
	protected function findExistingMaps(array $mapNames) {
		$existingMaps = array();
		$dbMaps = DBselect('SELECT s.sysmapid, s.name FROM sysmaps s WHERE '.DBcondition('s.name', $mapNames));
		while ($dbMap = DBfetch($dbMaps)) {
			$existingMaps[$dbMap['name']] = $dbMap['sysmapid'];
		}

		return $existingMaps;
	}
}
