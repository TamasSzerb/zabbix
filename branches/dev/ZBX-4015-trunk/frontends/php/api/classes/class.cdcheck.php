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
 * File containing CDCheck class for API.
 * @package API
 */
/**
 * Class containing methods for operations with Discovery checks for discovery rules
 */
class CDCheck extends CZBXAPI {
	public function get($options) {
		$result = array();
		$nodeCheck = false;
		$user_type = self::$userData['type'];

		// allowed columns for sorting
		$sort_columns = array('dcheckid', 'druleid');

		// allowed output options for [ select_* ] params
		$subselects_allowed_outputs = array(API_OUTPUT_REFER, API_OUTPUT_EXTEND, API_OUTPUT_CUSTOM);

		$sql_parts = array(
			'select'	=> array('dchecks' => 'dc.dcheckid'),
			'from'		=> array('dchecks' => 'dchecks dc'),
			'where'		=> array(),
			'group'		=> array(),
			'order'		=> array(),
			'limit'		=> null
		);

		$def_options = array(
			'nodeids'					=> null,
			'dcheckids'					=> null,
			'druleids'					=> null,
			'dhostids'					=> null,
			'dserviceids'				=> null,
			'editable'					=> null,
			'nopermissions'				=> null,
			// filter
			'filter'					=> null,
			'search'					=> null,
			'searchByAny'				=> null,
			'startSearch'				=> null,
			'excludeSearch'				=> null,
			'searchWildcardsEnabled'	=> null,
			// output
			'output'					=> API_OUTPUT_REFER,
			'selectDRules'				=> null,
			'selectDHosts'				=> null,
			'selectHosts'				=> null,
			'countOutput'				=> null,
			'groupCount'				=> null,
			'preservekeys'				=> null,
			'sortfield'					=> '',
			'sortorder'					=> '',
			'limit'						=> null,
			'limitSelects'				=> null
		);
		$options = zbx_array_merge($def_options, $options);

		if (is_array($options['output'])) {
			unset($sql_parts['select']['dchecks']);

			$dbTable = DB::getSchema('dchecks');
			foreach ($options['output'] as $field) {
				if (isset($dbTable['fields'][$field])) {
					$sql_parts['select'][$field] = 'dc.'.$field;
				}
			}
			$options['output'] = API_OUTPUT_CUSTOM;
		}

// editable + PERMISSION CHECK
		if(USER_TYPE_SUPER_ADMIN == $user_type){
		}
		else if(is_null($options['editable']) && (self::$userData['type'] == USER_TYPE_ZABBIX_ADMIN)){
		}
		else if(!is_null($options['editable']) && (self::$userData['type']!=USER_TYPE_SUPER_ADMIN)){
			return array();
		}

// nodeids
		$nodeids = !is_null($options['nodeids']) ? $options['nodeids'] : get_current_nodeid();

// dcheckids
		if(!is_null($options['dcheckids'])){
			zbx_value2array($options['dcheckids']);
			$sql_parts['where']['dcheckid'] = DBcondition('dc.dcheckid', $options['dcheckids']);

			if(!$nodeCheck){
				$nodeCheck = true;
				$sql_parts['where'][] = DBin_node('dc.dcheckid', $nodeids);
			}
		}

// druleids
		if(!is_null($options['druleids'])){
			zbx_value2array($options['druleids']);
			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['druleid'] = 'dc.druleid';
			}

			$sql_parts['where'][] = DBcondition('dc.druleid', $options['druleids']);

			if(!is_null($options['groupCount'])){
				$sql_parts['group']['druleid'] = 'dc.druleid';
			}

			if(!$nodeCheck){
				$nodeCheck = true;
				$sql_parts['where'][] = DBin_node('dc.druleid', $nodeids);
			}
		}

// dhostids
		if(!is_null($options['dhostids'])){
			zbx_value2array($options['dhostids']);
			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['dservices'] = 'dh.dhostid';
			}

			$sql_parts['from']['dhosts'] = 'dhosts dh';

			$sql_parts['where']['dh'] = DBcondition('dh.dhostid', $options['dhostids']);
			$sql_parts['where']['dcdh'] = 'dc.druleid=dh.druleid';

			if(!is_null($options['groupCount'])){
				$sql_parts['group']['dhostid'] = 'dh.dhostid';
			}
		}


// dserviceids
		if(!is_null($options['dserviceids'])){
			zbx_value2array($options['dserviceids']);
			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['dserviceid'] = 'ds.dserviceid';
			}

			$sql_parts['from']['dhosts'] = 'dhosts dh';
			$sql_parts['from']['dservices'] = 'dservices ds';

			$sql_parts['where']['ds'] = DBcondition('ds.dserviceid', $options['dserviceids']);
			$sql_parts['where']['dcdh'] = 'dc.druleid=dh.druleid';
			$sql_parts['where']['dhds'] = 'dh.hostid=ds.hostid';

			if(!is_null($options['groupCount'])){
				$sql_parts['group']['dserviceid'] = 'ds.dserviceid';
			}
		}

// node check !!!!!
// should last, after all ****IDS checks
		if(!$nodeCheck){
			$nodeCheck = true;
			$sql_parts['where'][] = DBin_node('dc.dcheckid', $nodeids);
		}


// output
		if($options['output'] == API_OUTPUT_EXTEND){
			$sql_parts['select']['dchecks'] = 'dc.*';
		}

// countOutput
		if(!is_null($options['countOutput'])){
			$options['sortfield'] = '';
			$sql_parts['select'] = array('count(DISTINCT dc.dcheckid) as rowscount');

//groupCount
			if(!is_null($options['groupCount'])){
				foreach($sql_parts['group'] as $key => $fields){
					$sql_parts['select'][$key] = $fields;
				}
			}
		}

// filter
		if(is_array($options['filter'])){
			zbx_db_filter('dchecks dc', $options, $sql_parts);
		}

// search
		if(is_array($options['search'])){
			zbx_db_search('dchecks dc', $options, $sql_parts);
		}

		// sorting
		zbx_db_sorting($sql_parts, $options, $sort_columns, 'dc');

// limit
		if(zbx_ctype_digit($options['limit']) && $options['limit']){
			$sql_parts['limit'] = $options['limit'];
		}
//-------


		$sql_parts['select'] = array_unique($sql_parts['select']);
		$sql_parts['from'] = array_unique($sql_parts['from']);
		$sql_parts['where'] = array_unique($sql_parts['where']);
		$sql_parts['group'] = array_unique($sql_parts['group']);
		$sql_parts['order'] = array_unique($sql_parts['order']);

		$sql_select = '';
		$sql_from = '';
		$sql_where = '';
		$sql_group = '';
		$sql_order = '';
		if(!empty($sql_parts['select']))	$sql_select.= implode(',',$sql_parts['select']);
		if(!empty($sql_parts['from']))		$sql_from.= implode(',',$sql_parts['from']);
		if(!empty($sql_parts['where']))		$sql_where.= implode(' AND ',$sql_parts['where']);
		if(!empty($sql_parts['group']))		$sql_where.= ' GROUP BY '.implode(',',$sql_parts['group']);
		if(!empty($sql_parts['order']))		$sql_order.= ' ORDER BY '.implode(',',$sql_parts['order']);
		$sql_limit = $sql_parts['limit'];

		$sql = 'SELECT '.zbx_db_distinct($sql_parts).' '.$sql_select.
				' FROM '.$sql_from.
				' WHERE '.$sql_where.
				$sql_group.
				$sql_order;
 //SDI($sql);
		$res = DBselect($sql, $sql_limit);
		while($dcheck = DBfetch($res)){
			if(!is_null($options['countOutput'])){
				if(!is_null($options['groupCount']))
					$result[] = $dcheck;
				else
					$result = $dcheck['rowscount'];
			}
			else{
				$dcheckids[$dcheck['dcheckid']] = $dcheck['dcheckid'];

				if($options['output'] == API_OUTPUT_SHORTEN){
					$result[$dcheck['dcheckid']] = array('dcheckid' => $dcheck['dcheckid']);
				}
				else{
					if(!isset($result[$dcheck['dcheckid']])) $result[$dcheck['dcheckid']]= array();

					if(!is_null($options['selectDRules']) && !isset($result[$dcheck['dcheckid']]['drules'])){
						$result[$dcheck['dcheckid']]['drules'] = array();
					}

					if(!is_null($options['selectDHosts']) && !isset($result[$dcheck['dcheckid']]['dhosts'])){
						$result[$dcheck['dcheckid']]['dhosts'] = array();
					}

					if(!is_null($options['selectHosts']) && !isset($result[$dcheck['dcheckid']]['hosts'])){
						$result[$dcheck['dcheckid']]['hosts'] = array();
					}
// druleids
					if(isset($dcheck['druleid']) && is_null($options['selectDRules'])){
						if(!isset($result[$dcheck['dcheckid']]['drules']))
							$result[$dcheck['dcheckid']]['drules'] = array();

						$result[$dcheck['dcheckid']]['drules'][] = array('druleid' => $dcheck['druleid']);
					}
// dhostids
					if(isset($dcheck['dhostid']) && is_null($options['selectDHosts'])){
						if(!isset($result[$dcheck['dcheckid']]['dhosts']))
							$result[$dcheck['dcheckid']]['dhosts'] = array();

						$result[$dcheck['dcheckid']]['dhosts'][] = array('dhostid' => $dcheck['dhostid']);
					}

					$result[$dcheck['dcheckid']] += $dcheck;
				}
			}
		}

Copt::memoryPick();
		if(!is_null($options['countOutput'])){
			return $result;
		}

// Adding Objects
// select_drules
		if(!is_null($options['selectDRules'])){
			$obj_params = array(
				'nodeids' => $nodeids,
				'dcheckids' => $dcheckids,
				'preservekeys' => 1
			);

			if(is_array($options['selectDRules']) || str_in_array($options['selectDRules'], $subselects_allowed_outputs)){
				$obj_params['output'] = $options['selectDRules'];
				$drules = API::DRule()->get($obj_params);

				if(!is_null($options['limitSelects'])) order_result($drules, 'name');
				foreach($drules as $druleid => $drule){
					unset($drules[$druleid]['dchecks']);
					$count = array();
					foreach($drule['dchecks'] as $dnum => $dcheck){
						if(!is_null($options['limitSelects'])){
							if(!isset($count[$dcheck['dcheckid']])) $count[$dcheck['dcheckid']] = 0;
							$count[$dcheck['dcheckid']]++;

							if($count[$dcheck['dcheckid']] > $options['limitSelects']) continue;
						}

						$result[$dcheck['dcheckid']]['drules'][] = &$drules[$druleid];
					}
				}
			}
			else if(API_OUTPUT_COUNT == $options['selectDRules']){
				$obj_params['countOutput'] = 1;
				$obj_params['groupCount'] = 1;

				$drules = API::DRule()->get($obj_params);
				$drules = zbx_toHash($drules, 'dcheckid');
				foreach($result as $dcheckid => $dcheck){
					if(isset($drules[$dcheckid]))
						$result[$dcheckid]['drules'] = $drules[$dcheckid]['rowscount'];
					else
						$result[$dcheckid]['drules'] = 0;
				}
			}
		}

// selectDHosts
		if(!is_null($options['selectDHosts'])){
			$obj_params = array(
				'nodeids' => $nodeids,
				'dcheckids' => $dcheckids,
				'preservekeys' => 1
			);

			if(is_array($options['selectDHosts']) || str_in_array($options['selectDHosts'], $subselects_allowed_outputs)){
				$obj_params['output'] = $options['selectDHosts'];
				$dhosts = API::DHost()->get($obj_params);

				if(!is_null($options['limitSelects'])) order_result($dhosts, 'dhostid');
				foreach($dhosts as $dhostid => $dhost){
					unset($dhosts[$dhostid]['dchecks']);
					foreach($dhost['dchecks'] as $snum => $dcheck){
						if(!is_null($options['limitSelects'])){
							if(!isset($count[$dcheck['dcheckid']])) $count[$dcheck['dcheckid']] = 0;
							$count[$dcheck['dcheckid']]++;

							if($count[$dcheck['dcheckid']] > $options['limitSelects']) continue;
						}

						$result[$dcheck['dcheckid']]['dhosts'][] = &$dhosts[$dhostid];
					}
				}
			}
			else if(API_OUTPUT_COUNT == $options['selectDHosts']){
				$obj_params['countOutput'] = 1;
				$obj_params['groupCount'] = 1;

				$dhosts = API::DHost()->get($obj_params);
				$dhosts = zbx_toHash($dhosts, 'dhostid');
				foreach($result as $dcheckid => $dcheck){
					if(isset($dhosts[$dcheckid]))
						$result[$dcheckid]['dhosts'] = $dhosts[$dcheckid]['rowscount'];
					else
						$result[$dcheckid]['dhosts'] = 0;
				}
			}
		}

// selectHosts
		if(!is_null($options['selectHosts'])){
			$obj_params = array(
				'nodeids' => $nodeids,
				'dcheckids' => $dcheckids,
				'preservekeys' => 1,
				'sortfield' => 'status'
			);

			if(is_array($options['selectHosts']) || str_in_array($options['selectHosts'], $subselects_allowed_outputs)){
				$obj_params['output'] = $options['selectHosts'];
				$hosts = API::Host()->get($obj_params);

				if(!is_null($options['limitSelects'])) order_result($hosts, 'hostid');

				foreach($hosts as $hostid => $host){
					unset($hosts[$hostid]['dchecks']);
					foreach($host['dchecks'] as $dnum => $dcheck){
						if(!is_null($options['limitSelects'])){
							if(!isset($count[$dcheck['dcheckid']])) $count[$dcheck['dcheckid']] = 0;
							$count[$dcheck['dcheckid']]++;

							if($count[$dcheck['dcheckid']] > $options['limitSelects']) continue;
						}

						$result[$dcheck['dcheckid']]['hosts'][] = &$hosts[$hostid];
					}
				}
			}
			else if(API_OUTPUT_COUNT == $options['selectHosts']){
				$obj_params['countOutput'] = 1;
				$obj_params['groupCount'] = 1;

				$hosts = API::Host()->get($obj_params);
				$hosts = zbx_toHash($hosts, 'hostid');
				foreach($result as $dcheckid => $dcheck){
					if(isset($hosts[$dcheckid]))
						$result[$dcheckid]['hosts'] = $hosts[$dcheckid]['rowscount'];
					else
						$result[$dcheckid]['hosts'] = 0;
				}
			}
		}

Copt::memoryPick();
// removing keys (hash -> array)
		if(is_null($options['preservekeys'])){
			$result = zbx_cleanHashes($result);
		}

	return $result;
	}

	/**
	 * Check if user has read permissions for discovery checks.
	 *
	 * @param array $ids
	 * @return bool
	 */
	public function isReadable(array $ids) {
		if (empty($ids)) {
			return true;
		}

		$ids = array_unique($ids);

		$count = $this->get(array(
			'nodeids' => get_current_nodeid(true),
			'dcheckids' => $ids,
			'output' => API_OUTPUT_SHORTEN,
			'countOutput' => true
		));

		return (count($ids) == $count);
	}

	/**
	 * Check if user has write permissions for discovery checks.
	 *
	 * @param array $ids
	 * @return bool
	 */
	public function isWritable(array $ids) {
		if (empty($ids)) {
			return true;
		}

		$ids = array_unique($ids);

		$count = $this->get(array(
			'nodeids' => get_current_nodeid(true),
			'dcheckids' => $ids,
			'output' => API_OUTPUT_SHORTEN,
			'editable' => true,
			'countOutput' => true
		));

		return (count($ids) == $count);
	}
}
?>
