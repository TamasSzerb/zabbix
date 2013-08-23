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
 * Class containing methods for operations with scripts.
 *
 * @package API
 */
class CScript extends CZBXAPI {

	protected $tableName = 'scripts';
	protected $tableAlias = 's';
	protected $sortColumns = array('scriptid', 'name');

	/**
	 * Get scripts data.
	 *
	 * @param array  $options
	 * @param array  $options['itemids']
	 * @param array  $options['hostids']	deprecated (very slow)
	 * @param array  $options['groupids']
	 * @param array  $options['triggerids']
	 * @param array  $options['scriptids']
	 * @param bool   $options['status']
	 * @param bool   $options['editable']
	 * @param bool   $options['count']
	 * @param string $options['pattern']
	 * @param int    $options['limit']
	 * @param string $options['order']
	 *
	 * @return array
	 */
	public function get($options = array()) {
		$result = array();
		$userType = self::$userData['type'];
		$userid = self::$userData['userid'];

		$sqlParts = array(
			'select'	=> array('scripts' => 's.scriptid'),
			'from'		=> array('scripts s'),
			'where'		=> array(),
			'order'		=> array(),
			'limit'		=> null
		);

		$defOptions = array(
			'nodeids'				=> null,
			'groupids'				=> null,
			'hostids'				=> null,
			'scriptids'				=> null,
			'usrgrpids'				=> null,
			'editable'				=> null,
			'nopermissions'			=> null,
			// filter
			'filter'				=> null,
			'search'				=> null,
			'searchByAny'			=> null,
			'startSearch'			=> null,
			'excludeSearch'			=> null,
			'searchWildcardsEnabled'=> null,
			// output
			'output'				=> API_OUTPUT_REFER,
			'selectGroups'			=> null,
			'selectHosts'			=> null,
			'countOutput'			=> null,
			'preservekeys'			=> null,
			'sortfield'				=> '',
			'sortorder'				=> '',
			'limit'					=> null
		);
		$options = zbx_array_merge($defOptions, $options);

		// editable + permission check
		if ($userType == USER_TYPE_SUPER_ADMIN) {
		}
		elseif (!is_null($options['editable'])) {
			return $result;
		}
		else {
			$sqlParts['from']['rights'] = 'rights r';
			$sqlParts['from']['users_groups'] = 'users_groups ug';
			$sqlParts['from']['hosts_groups'] = 'hosts_groups hg';
			$sqlParts['where'][] = 'hg.groupid=r.id';
			$sqlParts['where'][] = 'r.groupid=ug.usrgrpid';
			$sqlParts['where'][] = 'ug.userid='.$userid;
			$sqlParts['where'][] = '(hg.groupid=s.groupid OR s.groupid IS NULL)';
			$sqlParts['where'][] = '(ug.usrgrpid=s.usrgrpid OR s.usrgrpid IS NULL)';
		}

		// groupids
		if (!is_null($options['groupids'])) {
			zbx_value2array($options['groupids']);
			$options['groupids'][] = 0; // include all groups scripts

			$sqlParts['select']['scripts'] = 's.scriptid,s.groupid';
			$sqlParts['where'][] = '('.dbConditionInt('s.groupid', $options['groupids']).' OR s.groupid IS NULL)';
		}

		// hostids
		if (!is_null($options['hostids'])) {
			zbx_value2array($options['hostids']);

			// only fetch scripts from the same nodes as the hosts
			$hostNodeIds = array();
			foreach ($options['hostids'] as $hostId) {
				$hostNodeIds[] = id2nodeid($hostId);
			}
			$hostNodeIds = array_unique($hostNodeIds);

			// return scripts that are assigned to the hosts' groups or to no group
			$hostGroups = API::HostGroup()->get(array(
				'output' => array('groupid'),
				'hostids' => $options['hostids'],
				'nodeids' => $hostNodeIds
			));
			$hostGroupIds = zbx_objectValues($hostGroups, 'groupid');

			$sqlParts['select']['hostid'] = 'hg.hostid';
			$sqlParts['from']['hosts_groups'] = 'hosts_groups hg';
			$sqlParts['where'][] = '(('.dbConditionInt('hg.groupid', $hostGroupIds).' AND hg.groupid=s.groupid)'.
				' OR '.
				'(s.groupid IS NULL'.andDbNode('s.scriptid', $hostNodeIds).'))';
		}

		// usrgrpids
		if (!is_null($options['usrgrpids'])) {
			zbx_value2array($options['usrgrpids']);
			$options['usrgrpids'][] = 0; // include all usrgrps scripts

			$sqlParts['select']['usrgrpid'] = 's.usrgrpid';
			$sqlParts['where'][] = '('.dbConditionInt('s.usrgrpid', $options['usrgrpids']).' OR s.usrgrpid IS NULL)';
		}

		// scriptids
		if (!is_null($options['scriptids'])) {
			zbx_value2array($options['scriptids']);

			$sqlParts['where'][] = dbConditionInt('s.scriptid', $options['scriptids']);
		}

		// search
		if (is_array($options['search'])) {
			zbx_db_search('scripts s', $options, $sqlParts);
		}

		// filter
		if (is_array($options['filter'])) {
			$this->dbFilter('scripts s', $options, $sqlParts);
		}

		// limit
		if (zbx_ctype_digit($options['limit']) && $options['limit']) {
			$sqlParts['limit'] = $options['limit'];
		}

		$sqlParts = $this->applyQueryOutputOptions($this->tableName(), $this->tableAlias(), $options, $sqlParts);
		$sqlParts = $this->applyQuerySortOptions($this->tableName(), $this->tableAlias(), $options, $sqlParts);
		$sqlParts = $this->applyQueryNodeOptions($this->tableName(), $this->tableAlias(), $options, $sqlParts);
		$res = DBselect($this->createSelectQueryFromParts($sqlParts), $sqlParts['limit']);
		while ($script = DBfetch($res)) {
			if ($options['countOutput']) {
				$result = $script['rowscount'];
			}
			else {
				if (!isset($result[$script['scriptid']])) {
					$result[$script['scriptid']] = array();
				}

				$result[$script['scriptid']] += $script;
			}
		}

		if (!is_null($options['countOutput'])) {
			return $result;
		}

		if ($result) {
			$result = $this->addRelatedObjects($options, $result);
			$result = $this->unsetExtraFields($result, array('groupid', 'host_access'), $options['output']);
		}

		// removing keys (hash -> array)
		if (is_null($options['preservekeys'])) {
			$result = zbx_cleanHashes($result);
		}

		return $result;
	}

	/**
	 * Add scripts.
	 *
	 * @param array $scripts
	 * @param array $scripts['name']
	 * @param array $scripts['hostid']
	 *
	 * @return array
	 */
	public function create($scripts) {
		$scripts = zbx_toArray($scripts);

		$this->checkInput($scripts, __FUNCTION__);
		$this->clearData($scripts);

		$scriptIds = DB::insert('scripts', $scripts);

		return array('scriptids' => $scriptIds);
	}

	/**
	 * Update scripts.
	 *
	 * @param array $scripts
	 * @param array $scripts['name']
	 * @param array $scripts['hostid']
	 *
	 * @return array
	 */
	public function update($scripts) {
		$scripts = zbx_toHash($scripts, 'scriptid');

		$this->checkInput($scripts, __FUNCTION__);
		$this->clearData($scripts);

		$update = array();

		foreach ($scripts as $script) {
			$scriptId = $script['scriptid'];
			unset($script['scriptid']);

			$update[] = array(
				'values' => $script,
				'where' => array('scriptid' => $scriptId)
			);
		}

		DB::update('scripts', $update);

		return array('scriptids' => array_keys($scripts));
	}

	/**
	 * Delete scripts.
	 *
	 * @param array $scriptIds
	 *
	 * @return array
	 */
	public function delete($scriptIds) {
		$scriptIds = zbx_toArray($scriptIds);

		$this->checkInput($scriptIds, __FUNCTION__);

		DB::delete('scripts', array('scriptid' => $scriptIds));

		return array('scriptids' => $scriptIds);
	}

	/**
	 * Execute script.
	 *
	 * @param string $data['scriptid']
	 * @param string $data['hostid']
	 *
	 * @return array
	 */
	public function execute($data) {
		global $ZBX_SERVER, $ZBX_SERVER_PORT;

		$scriptId = $data['scriptid'];
		$hostId = $data['hostid'];

		$scripts = $this->get(array(
			'hostids' => $hostId,
			'scriptids' => $scriptId,
			'output' => array('scriptid'),
			'preservekeys' => true
		));

		if (!isset($scripts[$scriptId])) {
			self::exception(ZBX_API_ERROR_PERMISSIONS, _('You do not have permission to perform this operation.'));
		}

		// execute script
		$zabbixServer = new CZabbixServer($ZBX_SERVER, $ZBX_SERVER_PORT, ZBX_SCRIPT_TIMEOUT, ZBX_SOCKET_BYTES_LIMIT);
		$result = $zabbixServer->executeScript($scriptId, $hostId);
		if ($result !== false) {
			// return the result in a backwards-compatible format
			return array(
				'response' => 'success',
				'value' => $result
			);
		}
		else {
			self::exception(ZBX_API_ERROR_INTERNAL, $zabbixServer->getError());
		}
	}

	/**
	 * Returns all the scripts that are available on each given host.
	 *
	 * @param $hostIds
	 *
	 * @return array (an array of scripts in the form of array($hostId => array($script1, $script2, ...), ...) )
	 */
	public function getScriptsByHosts($hostIds) {
		zbx_value2array($hostIds);

		$scriptsByHost = array();
		foreach ($hostIds as $hostId) {
			$scriptsByHost[$hostId] = array();
		}

		$scripts = $this->get(array(
			'output' => API_OUTPUT_EXTEND,
			'selectHosts' => API_OUTPUT_REFER,
			'hostids' => $hostIds,
			'sortfield' => 'name',
			'preservekeys' => true
		));

		if ($scripts) {
			// resolve macros
			$macrosData = array();
			foreach ($scripts as $script) {
				if (!empty($script['confirmation'])) {
					foreach ($script['hosts'] as $host) {
						if (isset($scriptsByHost[$host['hostid']])) {
							$macrosData[$host['hostid']][] = $script['confirmation'];
						}
					}
				}
			}
			if ($macrosData) {
				$macrosData = CMacrosResolverHelper::resolve(array(
					'config' => 'scriptConfirmation',
					'data' => $macrosData
				));
			}

			$i = 0;
			foreach ($scripts as $script) {
				$hosts = $script['hosts'];
				unset($script['hosts']);

				// set script to host
				foreach ($hosts as $host) {
					$hostId = $host['hostid'];

					if (isset($scriptsByHost[$hostId])) {
						$size = count($scriptsByHost[$hostId]);
						$scriptsByHost[$hostId][$size] = $script;

						// set confirmation text with resolved macros
						if (!empty($macrosData[$hostId]) && !empty($script['confirmation'])) {
							$scriptsByHost[$hostId][$size]['confirmation'] = $macrosData[$hostId][$i];
						}
					}
				}

				if (!empty($script['confirmation'])) {
					$i++;
				}
			}
		}

		return $scriptsByHost;
	}

	protected function checkInput(&$scripts, $method) {
		if (self::$userData['type'] != USER_TYPE_SUPER_ADMIN) {
			self::exception(ZBX_API_ERROR_PERMISSIONS, _('You do not have permission to perform this operation.'));
		}

		$create = ($method == 'create');
		$update = ($method == 'update');
		$delete = ($method == 'delete');

		if ($create || $update) {
			foreach ($scripts as $script) {
				if (!isset($script['name']) || zbx_empty($script['name'])) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _('Empty name for script.'));
				}

				$items = preg_split('/(?<!\\\)\//', $script['name'], PREG_SPLIT_OFFSET_CAPTURE);

				for ($i = 0, $size = count($items); $i < $size; $i++) {
					$item = strtr($items[$i], array('\\' => '', '\\\\' => '\\'));

					if (zbx_empty($item)) {
						// menu1/menu2/{empty}
						if ($i == $size - 1) {
							self::exception(ZBX_API_ERROR_PARAMETERS, _s('Empty name for script "%1$s".', $script['name']));
						}
						// menu1/{empty}/name
						else {
							self::exception(ZBX_API_ERROR_PARAMETERS, _s('Incorrect menu path for script "%1$s".', $script['name']));
						}
					}
				}
			}
		}

		// create
		if ($create) {
			$dbFields = array('command' => null);
			$names = array();

			foreach ($scripts as $script) {
				if (!check_db_fields($dbFields, $script)) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _('Wrong fields for script.'));
				}

				if (isset($names[$script['name']])) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _s('Duplicate script name "%1$s".', $script['name']));
				}

				$names[$script['name']] = $script['name'];
			}

			$dbScripts = $this->get(array(
				'output' => API_OUTPUT_EXTEND,
				'preservekeys' => true,
				'filter' => array('name' => $names),
				'limit' => 1
			));
			if ($dbScript = reset($dbScripts)) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _s('Script "%1$s" already exists.', $dbScript['name']));
			}
		}

		// update
		elseif ($update) {
			$scriptIds = array_keys($scripts);

			$dbScripts = $this->get(array(
				'scriptids' => $scriptIds,
				'output' => array('scriptid'),
				'preservekeys' => true
			));

			$names = array();

			foreach ($scripts as $script) {
				if (!isset($dbScripts[$script['scriptid']])) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _s('Script with scriptid "%1$s" does not exist.', $script['scriptid']));
				}

				if (isset($script['name'])) {
					if (isset($names[$script['name']])) {
						self::exception(ZBX_API_ERROR_PARAMETERS, _s('Duplicate script name "%1$s".', $script['name']));
					}

					$names[$script['name']] = $script['name'];
				}
			}

			if ($names) {
				$dbScripts = $this->get(array(
					'output' => API_OUTPUT_EXTEND,
					'preservekeys' => true,
					'filter' => array('name' => $names)
				));

				foreach ($dbScripts as $dbScript) {
					if (!isset($scripts[$dbScript['scriptid']])
							|| bccomp($scripts[$dbScript['scriptid']]['scriptid'], $dbScript['scriptid']) != 0) {
						self::exception(ZBX_API_ERROR_PARAMETERS, _s('Script "%1$s" already exists.', $dbScript['name']));
					}
				}
			}
		}

		// delete
		elseif ($delete) {
			$scriptIds = $scripts;

			if (empty($scriptIds)) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _('Cannot delete scripts. Empty input parameter "scriptids".'));
			}

			$dbScripts = $this->get(array(
				'scriptids' => $scriptIds,
				'editable' => true,
				'output' => API_OUTPUT_EXTEND,
				'preservekeys' => true
			));

			foreach ($scriptIds as $scriptId) {
				if (isset($dbScripts[$scriptId])) {
					continue;
				}

				self::exception(ZBX_API_ERROR_PERMISSIONS, _s('Cannot delete scripts. Script with scriptid "%1$s" does not exist.', $scriptId));
			}

			$actions = API::Action()->get(array(
				'scriptids' => $scriptIds,
				'nopermissions' => true,
				'preservekeys' => true,
				'output' => array('actionid', 'name')
			));

			foreach ($actions as $action) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _s('Cannot delete scripts. Script "%1$s" is used in action operation "%2$s".',
					$dbScripts[$action['scriptid']]['name'], $action['name']));
			}
		}
	}

	private function clearData(&$scripts) {
		foreach ($scripts as $key => $script) {
			if (isset($script['type']) && $script['type'] == ZBX_SCRIPT_TYPE_IPMI) {
				unset($scripts[$key]['execute_on']);
			}
		}
	}

	protected function applyQueryNodeOptions($tableName, $tableAlias, array $options, array $sqlParts) {
		// only apply the node option if no specific ids are given
		if ($options['scriptids'] === null && $options['hostids'] === null && $options['groupids'] === null) {
			$sqlParts = parent::applyQueryNodeOptions($tableName, $tableAlias, $options, $sqlParts);
		}

		return $sqlParts;
	}

	protected function applyQueryOutputOptions($tableName, $tableAlias, array $options, array $sqlParts) {
		$sqlParts = parent::applyQueryOutputOptions($tableName, $tableAlias, $options, $sqlParts);

		if ($options['output'] != API_OUTPUT_COUNT) {
			if ($options['selectGroups'] !== null || $options['selectHosts'] !== null) {
				$sqlParts = $this->addQuerySelect($this->fieldId('groupid'), $sqlParts);
				$sqlParts = $this->addQuerySelect($this->fieldId('host_access'), $sqlParts);
			}
		}

		return $sqlParts;
	}

	protected function addRelatedObjects(array $options, array $result) {
		$result = parent::addRelatedObjects($options, $result);

		// adding groups
		if ($options['selectGroups'] !== null && $options['selectGroups'] != API_OUTPUT_COUNT) {
			foreach ($result as $scriptId => $script) {
				$result[$scriptId]['groups'] = API::HostGroup()->get(array(
					'output' => $options['selectGroups'],
					'groupids' => $script['groupid'] ? $script['groupid'] : null,
					'editable' => ($script['host_access'] == PERM_READ_WRITE) ? true : null
				));
			}
		}

		// adding hosts
		if ($options['selectHosts'] !== null && $options['selectHosts'] != API_OUTPUT_COUNT) {
			$processedGroups = array();

			foreach ($result as $scriptId => $script) {
				if (isset($processedGroups[$script['groupid'].'_'.$script['host_access']])) {
					$result[$scriptId]['hosts'] = $result[$processedGroups[$script['groupid'].'_'.$script['host_access']]]['hosts'];
				}
				else {
					$result[$scriptId]['hosts'] = API::Host()->get(array(
						'output' => $options['selectHosts'],
						'groupids' => $script['groupid'] ? $script['groupid'] : null,
						'hostids' => $options['hostids'] ? $options['hostids'] : null,
						'editable' => ($script['host_access'] == PERM_READ_WRITE) ? true : null,
						'nodeids' => id2nodeid($script['scriptid'])
					));

					$processedGroups[$script['groupid'].'_'.$script['host_access']] = $scriptId;
				}
			}
		}

		return $result;
	}
}
