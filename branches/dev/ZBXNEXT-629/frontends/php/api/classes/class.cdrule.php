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
 * File containing drule class for API.
 * @package API
 */
/**
 * Class containing methods for operations with discovery rules
 */
class CDRule extends CZBXAPI{
/**
* Get drule data
*
* @param array $options
* @return array
*/
	public function get(array $options=array()){

		$nodeCheck = false;
		$user_type = self::$userData['type'];
		$result = array();

		$sort_columns = array('druleid', 'name'); // allowed columns for sorting
		$subselects_allowed_outputs = array(API_OUTPUT_REFER, API_OUTPUT_EXTEND); // allowed output options for [ select_* ] params

		$sql_parts = array(
			'select' => array('drules' => 'dr.druleid'),
			'from' => array('drules' => 'drules dr'),
			'where' => array(),
			'group' => array(),
			'order' => array(),
			'limit' => null,
		);

		$def_options = array(
			'nodeids'				=> null,
			'druleids'				=> null,
			'dhostids'				=> null,
			'dserviceids'			=> null,
			'dcheckids'				=> null,
			'editable'				=> null,
			'selectDHosts'			=> null,
			'selectDServices'		=> null,
			'selectDChecks'			=> null,

// filter
			'filter'					=> null,
			'search'					=> null,
			'searchByAny'			=> null,
			'startSearch'				=> null,
			'excludeSearch'				=> null,

// output
			'output'				=> API_OUTPUT_REFER,
			'countOutput'			=> null,
			'groupCount'			=> null,
			'preservekeys'			=> null,

			'sortfield'				=> '',
			'sortorder'				=> '',
			'limit'					=> null,
			'limitSelects'			=> null
		);

		$options = zbx_array_merge($def_options, $options);

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

// druleids
		if(!is_null($options['druleids'])){
			zbx_value2array($options['druleids']);
			$sql_parts['where']['druleid'] = DBcondition('dr.druleid', $options['druleids']);

			if(!$nodeCheck){
				$nodeCheck = true;
				$sql_parts['where'][] = DBin_node('dr.druleid', $nodeids);
			}
		}

// dhostids
		if(!is_null($options['dhostids'])){
			zbx_value2array($options['dhostids']);
			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['dhostid'] = 'dh.dhostid';
			}

			$sql_parts['from']['dhosts'] = 'dhosts dh';
			$sql_parts['where']['dhostid'] = DBcondition('dh.dhostid', $options['dhostids']);
			$sql_parts['where']['dhdr'] = 'dh.druleid=dr.druleid';

			if(!is_null($options['groupCount'])){
				$sql_parts['group']['dhostid'] = 'dh.dhostid';
			}

			if(!$nodeCheck){
				$nodeCheck = true;
				$sql_parts['where'][] = DBin_node('dh.dhostid', $nodeids);
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

			$sql_parts['where']['dserviceid'] = DBcondition('ds.dserviceid', $options['dserviceids']);
			$sql_parts['where']['dhdr'] = 'dh.druleid=dr.druleid';
			$sql_parts['where']['dhds'] = 'dh.dhostid=ds.dhostid';

			if(!is_null($options['groupCount'])){
				$sql_parts['group']['dserviceid'] = 'ds.dserviceid';
			}

			if(!$nodeCheck){
				$nodeCheck = true;
				$sql_parts['where'][] = DBin_node('ds.dserviceid', $nodeids);
			}
		}

// node check !!!!!
// should be last, after all ****IDS checks
		if(!$nodeCheck){
			$nodeCheck = true;
			$sql_parts['where'][] = DBin_node('dr.druleid', $nodeids);
		}

// output
		if($options['output'] == API_OUTPUT_EXTEND){
			$sql_parts['select']['drules'] = 'dr.*';
		}

// countOutput
		if(!is_null($options['countOutput'])){
			$options['sortfield'] = '';
			$sql_parts['select'] = array('count(DISTINCT dr.druleid) as rowscount');

//groupCount
			if(!is_null($options['groupCount'])){
				foreach($sql_parts['group'] as $key => $fields){
					$sql_parts['select'][$key] = $fields;
				}
			}
		}

// search
		if(!is_null($options['search'])){
			zbx_db_search('drules dr', $options, $sql_parts);
		}

// filter
		if(is_array($options['filter'])){
			zbx_db_filter('drules dr', $options, $sql_parts);
		}

// search
		if(is_array($options['search'])){
			zbx_db_search('drules dr', $options, $sql_parts);
		}

// order
// restrict not allowed columns for sorting
		$options['sortfield'] = str_in_array($options['sortfield'], $sort_columns) ? $options['sortfield'] : '';
		if(!zbx_empty($options['sortfield'])){
			$sortorder = ($options['sortorder'] == ZBX_SORT_DOWN)?ZBX_SORT_DOWN:ZBX_SORT_UP;

			$sql_parts['order'][] = 'dr.'.$options['sortfield'].' '.$sortorder;

			if(!str_in_array('dr.'.$options['sortfield'], $sql_parts['select']) && !str_in_array('dr.*', $sql_parts['select'])){
				$sql_parts['select'][] = 'dr.'.$options['sortfield'];
			}
		}

// limit
		if(zbx_ctype_digit($options['limit']) && $options['limit']){
			$sql_parts['limit'] = $options['limit'];
		}
//------------

		$druleids = array();

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
		$db_res = DBselect($sql, $sql_limit);
		while($drule = DBfetch($db_res)){
			if(!is_null($options['countOutput'])){
				if(!is_null($options['groupCount']))
					$result[] = $drule;
				else
					$result = $drule['rowscount'];
			}
			else{
				if($options['output'] == API_OUTPUT_SHORTEN){
					$result[$drule['druleid']] = array('druleid' => $drule['druleid']);
				}
				else{
					$druleids[$drule['druleid']] = $drule['druleid'];

					if(!is_null($options['selectDHosts']) && !isset($result[$drule['druleid']]['dhosts'])){
						$result[$drule['druleid']]['dhosts'] = array();
					}
					if(!is_null($options['selectDChecks']) && !isset($result[$drule['druleid']]['dchecks'])){
						$result[$drule['druleid']]['dchecks'] = array();
					}
					if(!is_null($options['selectDServices']) && !isset($result[$drule['druleid']]['dservices'])){
						$result[$drule['druleid']]['dservices'] = array();
					}

// dhostids
					if(isset($drule['dhostid']) && is_null($options['selectDHosts'])){
						if(!isset($result[$drule['druleid']]['dhosts']))
							$result[$drule['druleid']]['dhosts'] = array();

						$result[$drule['druleid']]['dhosts'][] = array('dhostid' => $drule['dhostid']);
						unset($drule['dhostid']);
					}
// dchecks
					if(isset($drule['dcheckid']) && is_null($options['selectDChecks'])){
						if(!isset($result[$drule['druleid']]['dchecks']))
							$result[$drule['druleid']]['dchecks'] = array();

						$result[$drule['druleid']]['dchecks'][] = array('dcheckid' => $drule['dcheckid']);
						unset($drule['dcheckid']);
					}

// dservices
					if(isset($drule['dserviceid']) && is_null($options['selectDServices'])){
						if(!isset($result[$drule['druleid']]['dservices']))
							$result[$drule['druleid']]['dservices'] = array();

						$result[$drule['druleid']]['dservices'][] = array('dserviceid' => $drule['dserviceid']);
						unset($drule['dserviceid']);
					}

					if(!isset($result[$drule['druleid']]))
						$result[$drule['druleid']]= array();

					$result[$drule['druleid']] += $drule;
				}
			}
		}

COpt::memoryPick();
		if(($options['output'] != API_OUTPUT_EXTEND) || !is_null($options['countOutput'])){
			return $result;
		}

// Adding Objects

// Adding Discovery Checks
		if(!is_null($options['selectDChecks'])){
			$obj_params = array(
				'nodeids' => $nodeids,
				'druleids' => $druleids,
				'nopermissions' => true,
				'preservekeys' => true
			);

			if(is_array($options['selectDChecks']) || str_in_array($options['selectDChecks'], $subselects_allowed_outputs)){
				$obj_params['output'] = $options['selectDChecks'];
				$dchecks = API::DCheck()->get($obj_params);

				if(!is_null($options['limitSelects'])) order_result($dchecks, 'name');

				$count = array();
				foreach($dchecks as $dcheckid => $dcheck){
					unset($dchecks[$dcheckid]['drules']);

					if(!is_null($options['limitSelects'])){
						if(!isset($count[$dcheck['druleid']])) $count[$dcheck['druleid']] = 0;
						$count[$dcheck['druleid']]++;

						if($count[$dcheck['druleid']] > $options['limitSelects']) continue;
					}

					$result[$dcheck['druleid']]['dchecks'][] = &$dchecks[$dcheckid];
				}
			}
			else if(API_OUTPUT_COUNT == $options['selectDChecks']){
				$obj_params['countOutput'] = 1;
				$obj_params['groupCount'] = 1;

				$dchecks = API::DCheck()->get($obj_params);
				$dchecks = zbx_toHash($dchecks, 'druleid');
				foreach($result as $druleid => $drule){
					if(isset($dchecks[$druleid]))
						$result[$druleid]['dchecks'] = $dchecks[$druleid]['rowscount'];
					else
						$result[$druleid]['dchecks'] = 0;
				}
			}
		}

// Adding Discovery Hosts
		if(!is_null($options['selectDHosts'])){
			$obj_params = array(
				'nodeids' => $nodeids,
				'druleids' => $druleids,
				'preservekeys' => 1
			);

			if(is_array($options['selectDHosts']) || str_in_array($options['selectDHosts'], $subselects_allowed_outputs)){
				$obj_params['output'] = $options['selectDHosts'];
				$dhosts = API::DHost()->get($obj_params);

				if(!is_null($options['limitSelects'])) order_result($dhosts, 'name');
				foreach($dhosts as $dhostid => $dhost){
					unset($dhosts[$dhostid]['drules']);

					foreach($dhost['drules'] as $dnum => $drule){
						if(!is_null($options['limitSelects'])){
							if(!isset($count[$drule['druleid']])) $count[$drule['druleid']] = 0;
							$count[$drule['druleid']]++;

							if($count[$drule['druleid']] > $options['limitSelects']) continue;
						}

						$result[$drule['druleid']]['dhosts'][] = &$dhosts[$dhostid];
					}
				}
			}
			else if(API_OUTPUT_COUNT == $options['selectDHosts']){
				$obj_params['countOutput'] = 1;
				$obj_params['groupCount'] = 1;

				$dhosts = API::DHost()->get($obj_params);
				$dhosts = zbx_toHash($dhosts, 'druleid');
				foreach($result as $druleid => $drule){
					if(isset($dhosts[$druleid]))
						$result[$druleid]['dhosts'] = $dhosts[$druleid]['rowscount'];
					else
						$result[$druleid]['dhosts'] = 0;
				}
			}
		}


COpt::memoryPick();
// removing keys (hash -> array)
		if(is_null($options['preservekeys'])){
			$result = zbx_cleanHashes($result);
		}

	return $result;
	}


	public function exists(array $object){
		$options = array(
			'filter' => array(),
			'output' => API_OUTPUT_SHORTEN,
			'nopermissions' => 1,
			'limit' => 1
		);
		if(isset($object['name'])) $options['filter']['name'] = $object['name'];
		if(isset($object['hostids'])) $options['druleids'] = zbx_toArray($object['druleids']);

		if(isset($object['node']))
			$options['nodeids'] = getNodeIdByNodeName($object['node']);
		else if(isset($object['nodeids']))
			$options['nodeids'] = $object['nodeids'];

		$objs = $this->get($options);

	return !empty($objs);
	}

	public function checkInput(array &$dRules){
		$dRules = zbx_toArray($dRules);

		if(self::$userData['type'] >= USER_TYPE_ZABBIX_ADMIN){
			if(!count(get_accessible_nodes_by_user(self::$userData, PERM_READ_WRITE, PERM_RES_IDS_ARRAY)))
				self::exception(ZBX_API_ERROR_PARAMETERS, S_NO_PERMISSIONS);
		}

		foreach($dRules as $dRule){
			if(!isset($dRule['iprange']) || !validate_ip_range($dRule['iprange'])){
				self::exception(ZBX_API_ERROR_PARAMETERS, _s('Incorrect IP range "%s".', $dRule['iprange']));
			}
			if(isset($dRule['delay']) && $dRule['delay'] < 0){
				self::exception(ZBX_API_ERROR_PARAMETERS, _('Incorrect delay.'));
			}
			if(empty($dRule['dchecks'])){
				self::exception(ZBX_API_ERROR_PARAMETERS, _('Cannot save discovery rule without checks.'));
			}

			$this->validateDChecks($dRule['dchecks']);
		}
	}

	protected function validateDChecks(array &$dChecks){
		$uniq = 0;
		foreach($dChecks as $dcnum => $dCheck){
			if($dCheck['uniq'] == 1) $uniq++;

			if(isset($dCheck['ports']) && !validate_port_list($dCheck['ports'])){
				self::exception(ZBX_API_ERROR_PARAMETERS, _s('Incorrect port range.'));
			}

			switch($dCheck['type']){
				case SVC_AGENT:
					$itemKey = new CItemKey($dCheck['key_']);
					if(!$itemKey->isValid())
						self::exception(ZBX_API_ERROR_PARAMETERS, _s('Incorrect key: %s', $itemKey->getError()));
					break;

				case SVC_SNMPv1:
				case SVC_SNMPv2:
					if(!isset($dCheck['snmp_community']) || zbx_empty($dCheck['snmp_community']))
						self::exception(ZBX_API_ERROR_PARAMETERS, _('Incorrect SNMP Community.'));
				case SVC_SNMPv3:
					if(!isset($dCheck['key_']) || zbx_empty($dCheck['key_']))
						self::exception(ZBX_API_ERROR_PARAMETERS, _('Incorrect SNMP OID.'));
					break;
			}


			if(!isset($dCheck['snmpv3_securitylevel'])){
				$dCheck['snmpv3_securitylevel'] = ITEM_SNMPV3_SECURITYLEVEL_NOAUTHNOPRIV;
			}
			switch($dCheck['snmpv3_securitylevel']){
				case ITEM_SNMPV3_SECURITYLEVEL_NOAUTHNOPRIV:
					$dChecks[$dcnum]['snmpv3_authpassphrase'] = $dChecks[$dcnum]['snmpv3_privpassphrase'] = '';
					break;
				case ITEM_SNMPV3_SECURITYLEVEL_AUTHNOPRIV:
					$dChecks[$dcnum]['snmpv3_privpassphrase'] = '';
					break;
			}
		}

		if($uniq > 1){
			self::exception(ZBX_API_ERROR_PARAMETERS, _('Only one check can be unique.'));
		}
	}

	protected function validateRequiredFields($dRules, $on){
		if($on == 'update'){
			foreach($dRules as $dRule){
				if(!isset($dRule['druleid']) || zbx_empty($dRule['druleid'])){
					self::exception(ZBX_API_ERROR_PARAMETERS, _('Field "druleid" is required.'));
				}
			}
		}
		else{
			foreach($dRules as $dRule){
				if(!isset($dRule['name']) || zbx_empty($dRule['name'])){
					self::exception(ZBX_API_ERROR_PARAMETERS, _('Field "name" is required.'));
				}
			}
		}
	}

/**
 * Create new discovery rules
 *
 * @param array(
 *  name => string,
 *  proxy_hostid => int,
 *  iprange => string,
 *  delay => string,
 *  status => int,
 *  dchecks => array(
 *  	array(
 *  		type => int,
 *  		ports => string,
 *  		key_ => string,
 *  		snmp_community => string,
 *  		snmpv3_securityname => string,
 *  		snmpv3_securitylevel => int,
 *  		snmpv3_authpassphrase => string,
 *  		snmpv3_privpassphrase => string,
 *  		uniq => int,
 *  	), ...
 *  )
 * ) $drules
 * @return array
 */
	public function create(array $dRules){

		$this->checkInput($dRules);
		$this->validateRequiredFields($dRules, __FUNCTION__);

		$druleids = DB::insert('drules', $dRules);

		$dChecksCreate = array();
		foreach($dRules as $dNum => $dRule){
			foreach($dRule['dchecks'] as $dCheck){
				$dCheck['druleid'] = $druleids[$dNum];
				$dChecksCreate[] = $dCheck;
			}
		}

		DB::insert('dchecks', $dChecksCreate);

		return array('druleids' => $druleids);
	}

/**
 * Update existing drules
 *
 * @param array(
 * 	druleid => int,
 *  name => string,
 *  proxy_hostid => int,
 *  iprange => string,
 *  delay => string,
 *  status => int,
 *  dchecks => array(
 *  	array(
 * 			dcheckid => int,
 *  		type => int,
 *  		ports => string,
 *  		key_ => string,
 *  		snmp_community => string,
 *  		snmpv3_securityname => string,
 *  		snmpv3_securitylevel => int,
 *  		snmpv3_authpassphrase => string,
 *  		snmpv3_privpassphrase => string,
 *  		uniq => int,
 *  	), ...
 *  )
 * ) $drules
 * @return array
 */
	public function update(array $dRules){
		$dRuleids = zbx_objectValues($dRules, 'druleid');

		$this->checkInput($dRules);
		$this->validateRequiredFields($dRules, __FUNCTION__);

		$dRulesDb = API::DRule()->get(array(
			'druleids' => $dRuleids,
			'output' => API_OUTPUT_EXTEND,
			'selectDChecks' => API_OUTPUT_EXTEND,
			'editable' => true,
			'preservekeys' => true,
		));

		$dRulesUpdate = $dChecksUpdate = $dCheckidsDelete = $dChecksCreate = array();
		foreach($dRules as $dRule){

			$dRulesUpdate[] = array(
				'values' => $dRule,
				'where' => array('druleid' => $dRule['druleid'])
			);

			$dChecksDiff = zbx_array_diff($dRule['dchecks'], $dRulesDb[$dRule['druleid']]['dchecks'], 'dcheckid');

			foreach($dChecksDiff['first'] as $dCheck){
				$dCheck['druleid'] = $dRule['druleid'];
				$dChecksCreate[] = $dCheck;
			}

			$dCheckidsDelete = array_merge($dCheckidsDelete, zbx_objectValues($dChecksDiff['second'], 'dcheckid'));


			foreach($dChecksDiff['both'] as $checkUpdate){
				$dChecksUpdate[] = array(
					'values' => array('uniq' => $checkUpdate['uniq']),
					'where' => array('dcheckid' => $checkUpdate['dcheckid'])
				);
			}
		}

		if(!empty($dCheckidsDelete)){
			$this->deleteChecks($dCheckidsDelete);
		}
		DB::update('drules', $dRulesUpdate);
		DB::update('dchecks', $dChecksUpdate);
		DB::insert('dchecks', $dChecksCreate);

		return array('druleids' => $dRuleids);
	}

/**
 * Delete drules
 *
 * @param array $druleids
 * @return boolean
 */
	public function delete(array $druleids){
		$druleids = zbx_toArray($druleids);

		if(self::$userData['type'] >= USER_TYPE_ZABBIX_ADMIN){
			if(!count(get_accessible_nodes_by_user(self::$userData, PERM_READ_WRITE, PERM_RES_IDS_ARRAY)))
				self::exception(ZBX_API_ERROR_PARAMETERS, S_NO_PERMISSIONS);
		}

		$actionids = array();
		$sql = 'SELECT DISTINCT actionid '.
				' FROM conditions '.
				' WHERE conditiontype='.CONDITION_TYPE_DRULE.
				' AND '.DBcondition('value', $druleids);
		$db_actions = DBselect($sql);
		while($db_action = DBfetch($db_actions)){
			$actionids[] = $db_action['actionid'];
		}

		if(!empty($actionids)){
			DB::update('actions', array(
				'values' => array('status' => ACTION_STATUS_DISABLED),
				'where' => array('actionid' => $actionids),
			));

			DB::delete('conditions', array(
				'conditiontype' => CONDITION_TYPE_DRULE,
				'value' => $druleids
			));
		}

		DB::delete('drules', array('druleid' => $druleids));

		return array('druleids' => $druleids);
	}

	protected function deleteChecks(array $checkids){
		$actionids = array();
		// conditions
		$sql = 'SELECT DISTINCT actionid '.
				' FROM conditions '.
				' WHERE conditiontype='.CONDITION_TYPE_DCHECK.
				' AND '.DBcondition('value', $checkids);
		$db_actions = DBselect($sql);
		while($db_action = DBfetch($db_actions))
			$actionids[] = $db_action['actionid'];

		// disabling actions with deleted conditions
		if(!empty($actionids)){
			DBexecute('UPDATE actions '.
					' SET status='.ACTION_STATUS_DISABLED.
					' WHERE '.DBcondition('actionid', $actionids));

			// delete action conditions
			DBexecute('DELETE FROM conditions '.
					' WHERE conditiontype='.CONDITION_TYPE_DCHECK.
					' AND '.DBcondition('value', $checkids));
		}

		DB::delete('dchecks', array('dcheckid' => $checkids));
	}

}
?>
