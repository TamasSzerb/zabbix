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
?>
<?php
/**
 * File containing CScript class for API.
 * @package API
 */
/**
 * Class containing methods for operations with Scripts
 *
 */
class CScript extends CZBXAPI {

	protected $tableName = 'scripts';

	protected $tableAlias = 's';

	/**
	 * Get Scripts data
	 *
	 * @param array $options
	 * @param array $options['itemids']
	 * @param array $options['hostids'] - depricated (very slow)
	 * @param array $options['groupids']
	 * @param array $options['triggerids']
	 * @param array $options['scriptids']
	 * @param boolean $options['status']
	 * @param boolean $options['editable']
	 * @param boolean $options['count']
	 * @param string $options['pattern']
	 * @param int $options['limit']
	 * @param string $options['order']
	 * @return array|int item data as array or false if error
	 */
	public function get($options = array()) {
		$result = array();
		$user_type = self::$userData['type'];
		$userid = self::$userData['userid'];

		// allowed columns for sorting
		$sort_columns = array('scriptid', 'name');

		// allowed output options for [ select_* ] params
		$subselects_allowed_outputs = array(API_OUTPUT_REFER, API_OUTPUT_EXTEND);

		$sql_parts = array(
			'select'	=> array('scripts' => 's.scriptid'),
			'from'		=> array('scripts s'),
			'where'		=> array(),
			'order'		=> array(),
			'limit'		=> null
		);

		$def_options = array(
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
		$options = zbx_array_merge($def_options, $options);

		if (is_array($options['output'])) {
			unset($sql_parts['select']['scripts']);

			$dbTable = DB::getSchema('scripts');
			$sql_parts['select']['scriptid'] = 's.scriptid';
			foreach ($options['output'] as $field) {
				if (isset($dbTable['fields'][$field])) {
					$sql_parts['select'][$field] = 's.'.$field;
				}
			}
			$options['output'] = API_OUTPUT_CUSTOM;
		}

		// editable + permission check
		if (USER_TYPE_SUPER_ADMIN == $user_type) {
		}
		elseif (!is_null($options['editable'])) {
			return $result;
		}
		else {
			$sql_parts['from']['rights'] = 'rights r';
			$sql_parts['from']['users_groups'] = 'users_groups ug';
			$sql_parts['from']['hosts_groups'] = 'hosts_groups hg';
			$sql_parts['where'][] = 'hg.groupid=r.id';
			$sql_parts['where'][] = 'r.groupid=ug.usrgrpid';
			$sql_parts['where'][] = 'ug.userid='.$userid;
			$sql_parts['where'][] = '(hg.groupid=s.groupid OR s.groupid IS NULL)';
			$sql_parts['where'][] = '(ug.usrgrpid=s.usrgrpid OR s.usrgrpid IS NULL)';
		}

		// nodeids
		$nodeids = !is_null($options['nodeids']) ? $options['nodeids'] : get_current_nodeid();

		// groupids
		if (!is_null($options['groupids'])) {
			zbx_value2array($options['groupids']);
			$options['groupids'][] = 0; // include all groups scripts

			if ($options['output'] != API_OUTPUT_SHORTEN) {
				$sql_parts['select']['scripts'] = 's.scriptid,s.groupid';
			}
			$sql_parts['where'][] = '('.DBcondition('s.groupid', $options['groupids']).' OR s.groupid IS NULL)';
		}

		// usrgrpids
		if (!is_null($options['usrgrpids'])) {
			zbx_value2array($options['usrgrpids']);
			$options['usrgrpids'][] = 0; // include all usrgrps scripts

			if ($options['output'] != API_OUTPUT_SHORTEN) {
				$sql_parts['select']['usrgrpid'] = 's.usrgrpid';
			}
			$sql_parts['where'][] = '('.DBcondition('s.usrgrpid', $options['usrgrpids']).' OR s.usrgrpid IS NULL)';
		}

		// hostids
		if (!is_null($options['hostids'])) {
			zbx_value2array($options['hostids']);

			if ($options['output'] != API_OUTPUT_SHORTEN) {
				$sql_parts['select']['hostid'] = 'hg.hostid';
			}
			$sql_parts['from']['hosts_groups'] = 'hosts_groups hg';
			$sql_parts['where'][] = '(('.DBcondition('hg.hostid', $options['hostids']).' AND hg.groupid=s.groupid)'.
									' OR '.
									'(s.groupid IS NULL))';
		}

		// scriptids
		if (!is_null($options['scriptids'])) {
			zbx_value2array($options['scriptids']);

			$sql_parts['where'][] = DBcondition('s.scriptid', $options['scriptids']);
		}

		// output
		if ($options['output'] == API_OUTPUT_EXTEND) {
			$sql_parts['select']['scripts'] = 's.*';
		}

		// search
		if (is_array($options['search'])) {
			zbx_db_search('scripts s', $options, $sql_parts);
		}

		// filter
		if (is_array($options['filter'])) {
			zbx_db_filter('scripts s', $options, $sql_parts);
		}

		// countOutput
		if (!is_null($options['countOutput'])) {
			$options['sortfield'] = '';

			$sql_parts['select'] = array('count(DISTINCT s.scriptid) as rowscount');
		}

		// sorting
		zbx_db_sorting($sql_parts, $options, $sort_columns, 's');

		// limit
		if (zbx_ctype_digit($options['limit']) && $options['limit']) {
			$sql_parts['limit'] = $options['limit'];
		}

		$scriptids = array();

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

		$sql = 'SELECT '.zbx_db_distinct($sql_parts).' '.$sql_select.
				' FROM '.$sql_from.
				' WHERE '.DBin_node('s.scriptid', $nodeids).
					$sql_where.
					$sql_order;
		$res = DBselect($sql, $sql_limit);
		while ($script = DBfetch($res)) {
			if ($options['countOutput']) {
				$result = $script['rowscount'];
			}
			else {
				$scriptids[$script['scriptid']] = $script['scriptid'];

				if ($options['output'] == API_OUTPUT_SHORTEN) {
					$result[$script['scriptid']] = array('scriptid' => $script['scriptid']);
				}
				else {
					if (!isset($result[$script['scriptid']])) {
						$result[$script['scriptid']] = array();
					}
					if (!is_null($options['selectGroups']) && !isset($result[$script['scriptid']]['groups'])) {
						$result[$script['scriptid']]['groups'] = array();
					}
					if (!is_null($options['selectHosts']) && !isset($result[$script['scriptid']]['hosts'])) {
						$result[$script['scriptid']]['hosts'] = array();
					}

					// groupids
					if (isset($script['groupid']) && is_null($options['selectGroups'])) {
						if (!isset($result[$script['scriptid']]['groups'])) {
							$result[$script['scriptid']]['groups'] = array();
						}
						$result[$script['scriptid']]['groups'][] = array('groupid' => $script['groupid']);
					}

					// hostids
					if (isset($script['hostid']) && is_null($options['selectHosts'])) {
						if (!isset($result[$script['scriptid']]['hosts'])) {
							$result[$script['scriptid']]['hosts'] = array();
						}
						$result[$script['scriptid']]['hosts'][] = array('hostid' => $script['hostid']);
						unset($script['hostid']);
					}
					$result[$script['scriptid']] += $script;
				}
			}
		}

		if (!is_null($options['countOutput'])) {
			return $result;
		}

		/*
		 * Adding objects
		 */
		// adding groups
		if (!is_null($options['selectGroups']) && str_in_array($options['selectGroups'], $subselects_allowed_outputs)) {
			foreach ($result as $scriptid => $script) {
				$obj_params = array(
					'output' => $options['selectGroups'],
				);
				if ($script['host_access'] == PERM_READ_WRITE) {
					$obj_params['editable'] = 1;
				}
				if ($script['groupid'] > 0) {
					$obj_params['groupids'] = $script['groupid'];
				}
				$groups = API::HostGroup()->get($obj_params);
				$result[$scriptid]['groups'] = $groups;
			}
		}

		// adding hosts
		if (!is_null($options['selectHosts']) && str_in_array($options['selectHosts'], $subselects_allowed_outputs)) {
			foreach ($result as $scriptid => $script) {
				$obj_params = array(
					'output' => $options['selectHosts'],
				);
				if ($script['host_access'] == PERM_READ_WRITE) {
					$obj_params['editable'] = 1;
				}
				if ($script['groupid'] > 0) {
					$obj_params['groupids'] = $script['groupid'];
				}
				$hosts = API::Host()->get($obj_params);
				$result[$scriptid]['hosts'] = $hosts;
			}
		}

		// removing keys (hash -> array)
		if (is_null($options['preservekeys'])) {
			$result = zbx_cleanHashes($result);
		}
		return $result;
	}

/**
 * Get Script ID by host.name and item.key
 *
 * @param array $script
 * @param array $script['name']
 * @param array $script['hostid']
 * @return int|boolean
 */
	public function getObjects($script) {
		$result = array();
		$scriptids = array();

		$sql = 'SELECT scriptid '.
				' FROM scripts '.
				' WHERE '.DBin_node('scriptid').
					' AND name='.$script['name'];
		$res = DBselect($sql);
		while ($script = DBfetch($res)) {
			$scriptids[$script['scriptid']] = $script['scriptid'];
		}

		if (!empty($scriptids))
			$result = $this->get(array('scriptids'=>$scriptids, 'output' => API_OUTPUT_EXTEND));

	return $result;
	}

	private function _clearData(&$scripts) {
		foreach ($scripts as $snum => $script) {
			if (isset($script['type']) && $script['type'] == ZBX_SCRIPT_TYPE_IPMI) {
				unset($scripts[$snum]['execute_on']);
			}
		}
	}

/**
 * Add Scripts
 *
 * @param _array $scripts
 * @param array $script['name']
 * @param array $script['hostid']
 * @return boolean
 */
	public function create($scripts) {
		$scripts = zbx_toArray($scripts);

		if (USER_TYPE_SUPER_ADMIN != self::$userData['type']) {
			self::exception(ZBX_API_ERROR_PERMISSIONS, S_NO_PERMISSION);
		}

		$scriptNames = array();
		foreach ($scripts as $script) {
			$script_db_fields = array(
				'name' => null,
				'command' => null,
			);
			if (!check_db_fields($script_db_fields, $script)) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _('Wrong fields for script'));
			}

			if (isset($scriptNames[$script['name']])) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _s('Duplicate script name "%s"', $script['name']));
			}

			$scriptNames[$script['name']] = $script['name'];
		}

		$options = array(
			'output' => API_OUTPUT_EXTEND,
			'preservekeys' => true,
			'filter' => array('name' => $scriptNames),
			'limit' => 1,
		);
		$scriptsDB = $this->get($options);
		if ($exScript = reset($scriptsDB)) {
			self::exception(ZBX_API_ERROR_PARAMETERS, _s('Script "%s" already exists.', $exScript['name']));
		}

		$this->_clearData($scripts);
		$scriptids = DB::insert('scripts', $scripts);

		return array('scriptids' => $scriptids);
	}

/**
 * Update Scripts
 *
 * @param _array $scripts
 * @param array $script['name']
 * @param array $script['hostid']
 * @return boolean
 */
	public function update($scripts) {
		$scripts = zbx_toHash($scripts, 'scriptid');
		$scriptids = array_keys($scripts);

		if (USER_TYPE_SUPER_ADMIN != self::$userData['type']) {
			self::exception(ZBX_API_ERROR_PERMISSIONS, S_NO_PERMISSION);
		}

		$options = array(
			'scriptids' => $scriptids,
			'output' => API_OUTPUT_SHORTEN,
			'preservekeys' => true
		);
		$upd_scripts = $this->get($options);
		$scriptNames = array();
		foreach ($scripts as $script) {
			if (!isset($upd_scripts[$script['scriptid']])) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _s('Script with scriptid "%s" does not exist.', $script['scriptid']));
			}

			if (isset($script['name'])) {
				if (isset($scriptNames[$script['name']])) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _s('Duplicate script name "%s"', $script['name']));
				}

				$scriptNames[$script['name']] = $script['name'];
			}
		}


		if (!empty($scriptNames)) {
			$options = array(
				'output' => API_OUTPUT_EXTEND,
				'preservekeys' => true,
				'filter' => array('name' => $scriptNames),
			);
			$scriptsDB = $this->get($options);
			foreach ($scriptsDB as $exScript) {
				if (!isset($scripts[$exScript['scriptid']]) || (bccomp($scripts[$exScript['scriptid']]['scriptid'],$exScript['scriptid']) != 0)) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _s('Script "%s" already exists.', $exScript['name']));
				}
			}
		}

		$this->_clearData($scripts);
		$update = array();
		foreach ($scripts as $script) {
			$scriptid = $script['scriptid'];
			unset($script['scriptid']);
			$update[] = array(
				'values' => $script,
				'where' => array('scriptid'=>$scriptid),
			);
		}
		DB::update('scripts', $update);

		return array('scriptids' => $scriptids);
	}

/**
 * Delete Scripts
 *
 * @param _array $scriptids
 * @param array $scriptids
 * @return boolean
 */
	public function delete($scriptids) {
		$scriptids = zbx_toArray($scriptids);

		if (USER_TYPE_SUPER_ADMIN != self::$userData['type']) {
			self::exception(ZBX_API_ERROR_PERMISSIONS, S_NO_PERMISSION);
		}

		if (empty($scriptids)) {
			self::exception(ZBX_API_ERROR_PARAMETERS, 'Cannot delete scripts. Empty input parameter "scriptids"');
		}

		$dbScripts = $this->get(array(
			'scriptids' => $scriptids,
			'editable' => true,
			'output' => API_OUTPUT_EXTEND,
			'preservekeys' => true
		));
		foreach ($scriptids as $snum => $scriptid) {
			if (isset($dbScripts[$scriptid])) continue;
			self::exception(ZBX_API_ERROR_PERMISSIONS, _s('Cannot delete scripts. Script with scriptid "%s" does not exist.', $scriptid));
		}

		$scriptActions = API::Action()->get(array(
			'scriptids' => $scriptids,
			'nopermissions' => true,
			'preservekeys' => true,
			'output' => array('actionid','name')
		));

		foreach ($scriptActions as $anum => $action)
			self::exception(ZBX_API_ERROR_PARAMETERS, _s('Cannot delete scripts. Script "%1$s" is used in action operation "%2$s".', $dbScripts[$action['scriptid']]['name'], $action['name']));

		DB::delete('scripts', array('scriptid' => $scriptids));

		return array('scriptids' => $scriptids);
	}

	public function execute($data) {
		global $ZBX_SERVER, $ZBX_SERVER_PORT, $ZBX_MESSAGES;

		$scriptid = $data['scriptid'];
		$hostid = $data['hostid'];

		$options = array(
			'hostids' => $hostid,
			'scriptids' => $scriptid,
			'output' => API_OUTPUT_SHORTEN,
			'preservekeys' => true,
		);
		$alowedScripts = $this->get($options);
		if (!isset($alowedScripts[$scriptid])) {
			self::exception(ZBX_API_ERROR_PERMISSIONS, S_NO_PERMISSION);
		}
		if (!$socket = fsockopen($ZBX_SERVER, $ZBX_SERVER_PORT, $errorCode, $errorMsg, ZBX_SCRIPT_TIMEOUT)) {
			// remove warnings generated by fsockopen
			foreach ($ZBX_MESSAGES as $arrayNum => $fsockErrorCheck) {
				foreach ($fsockErrorCheck as $key => $val) {
					if ($key == 'message' && strpos($val, 'fsockopen()') !== false) {
						unset($ZBX_MESSAGES[$arrayNum]);
					}
				}

			}

			switch ($errorMsg) {
				case 'Connection refused':
					$d_errorMsg = sprintf(S_NOT_RUN, $ZBX_SERVER)." ";
					break;
				case 'No route to host':
					$d_errorMsg = sprintf(S_IP_NOT_AVAILABLE, $ZBX_SERVER)." ";
					break;
				case 'Connection timed out':
					$d_errorMsg = sprintf(S_TIME_OUT, $ZBX_SERVER)." ";
					break;
				case 'php_network_getaddresses: getaddrinfo failed: Name or service not known':
					$d_errorMsg = sprintf(S_WRONG_DNS, $ZBX_SERVER)." ";
					break;
				default:
					$d_errorMsg = '';
			}

			self::exception(ZBX_API_ERROR_INTERNAL, $d_errorMsg.S_SCRIPT_ERROR_DESCRIPTION.': '.$errorMsg);
		}

		$json = new CJSON();
		$array = array(
			'request' => 'command',
			'nodeid' => id2nodeid($hostid),
			'scriptid' => $scriptid,
			'hostid' => $hostid,
		);
		$dataToSend = $json->encode($array, false);

		stream_set_timeout($socket, ZBX_SCRIPT_TIMEOUT);

		if (fwrite($socket, $dataToSend) === false) {
			self::exception(ZBX_API_ERROR_INTERNAL, S_SCRIPT_ERROR_DESCRIPTION.': '.S_SCRIPT_SEND_ERROR);
		}

		$response = '';

		$pbl = ZBX_SCRIPT_BYTES_LIMIT > 8192 ? 8192 : ZBX_SCRIPT_BYTES_LIMIT; // PHP read bytes limit
		$now = time();
		$i = 0;
		while (!feof($socket)) {
			$i++;
			if ((time()-$now) >= ZBX_SCRIPT_TIMEOUT) {
				self::exception(ZBX_API_ERROR_INTERNAL, S_SCRIPT_ERROR_DESCRIPTION.': '.S_SCRIPT_TIMEOUT_ERROR);
			}
			else if ( ($i*$pbl) >= ZBX_SCRIPT_BYTES_LIMIT ) {
				self::exception(ZBX_API_ERROR_INTERNAL, S_SCRIPT_ERROR_DESCRIPTION.': '.S_SCRIPT_BYTES_LIMIT_ERROR);
			}

			if (($out = fread($socket, $pbl)) !== false) {
				$response .= $out;
			}
			else{
				self::exception(ZBX_API_ERROR_INTERNAL, S_SCRIPT_ERROR_DESCRIPTION.': '.S_SCRIPT_READ_ERROR);
			}
		}

		if (strlen($response) > 0) {
			$rcv = $json->decode($response, true);
		}
		else{
			self::exception(ZBX_API_ERROR_INTERNAL, S_SCRIPT_ERROR_DESCRIPTION.': '.S_SCRIPT_ERROR_EMPTY_RESPONSE);
		}

		fclose($socket);
		return $rcv;

/*
		$dataToSend = $json->encode($array, false);


		if (fwrite($socket, $dataToSend) === false) {
			self::exception(ZBX_API_ERROR_INTERNAL, S_SCRIPT_ERROR_DESCRIPTION.': '.S_SCRIPT_SEND_ERROR);
		}

		stream_set_blocking($socket, true);
		stream_set_timeout($socket, ZBX_SCRIPT_TIMEOUT);
		$response = stream_get_contents($socket, ZBX_SCRIPT_BYTES_LIMIT);

		$info = stream_get_meta_data($socket);

		if ($info['timed_out']) {
			self::exception(S_SCRIPT_ERROR_DESCRIPTION.': '.S_SCRIPT_TIMEOUT_ERROR);
		}
		if (false === $response) {
			self::exception(ZBX_API_ERROR_INTERNAL, S_SCRIPT_ERROR_DESCRIPTION.': '.S_SCRIPT_READ_ERROR);
		}
		if (strlen($response) == 0) {
			self::exception(ZBX_API_ERROR_INTERNAL, S_SCRIPT_ERROR_DESCRIPTION.': '.S_SCRIPT_ERROR_EMPTY_RESPONSE);
		}
		if (!feof($socket)) {
			self::exception(ZBX_API_ERROR_INTERNAL, S_SCRIPT_ERROR_DESCRIPTION.': '.S_SCRIPT_BYTES_LIMIT_ERROR);
		}


		fclose($socket);

		return $json->decode($response, true);
*/
	}

	public function getScriptsByHosts($hostids) {
		zbx_value2array($hostids);

		$obj_params = array(
			'hostids' => $hostids,
			'preservekeys' => 1
		);
		$hosts_read_only  = API::Host()->get($obj_params);
		$hosts_read_only = zbx_objectValues($hosts_read_only, 'hostid');

		$obj_params = array(
			'editable' => 1,
			'hostids' => $hostids,
			'preservekeys' => 1
		);
		$hosts_read_write = API::Host()->get($obj_params);
		$hosts_read_write = zbx_objectValues($hosts_read_write, 'hostid');

// initialize array
		$scripts_by_host = array();
		foreach ($hostids as $id => $hostid) {
			$scripts_by_host[$hostid] = array();
		}
//-----


		$options = array(
			'hostids' => $hostids,
			'output' => API_OUTPUT_EXTEND,
			'preservekeys' => 1
		);
		$groups = API::HostGroup()->get($options);

		$obj_params = array(
			'groupids' => zbx_objectValues($groups, 'groupid'),
			'output' => API_OUTPUT_EXTEND,
			'sortfield' => 'name',
			'preservekeys' => 1
		);
		$scripts  = API::Script()->get($obj_params);

		foreach ($scripts as $num => $script) {
			$add_to_hosts = array();
			$hostids = zbx_objectValues($groups[$script['groupid']]['hosts'], 'hostid');

			if (PERM_READ_WRITE == $script['host_access']) {
				if ($script['groupid'] > 0)
					$add_to_hosts = zbx_uint_array_intersect($hosts_read_write, $hostids);
				else
					$add_to_hosts = $hosts_read_write;
			}
			else if (PERM_READ_ONLY == $script['host_access']) {
				if ($script['groupid'] > 0)
					$add_to_hosts = zbx_uint_array_intersect($hosts_read_only, $hostids);
				else
					$add_to_hosts = $hosts_read_only;
			}

			foreach ($add_to_hosts as $id => $hostid) {
				$scripts_by_host[$hostid][] = $script;
			}
		}
//SDII(count($scripts_by_host));
	return $scripts_by_host;
	}

}
?>
