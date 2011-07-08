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

class CIconMap extends CZBXAPI{

	/**
	 * Get IconMap data
	 *
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
	public function get(array $options=array()){

		$result = array();
		$sort_columns = array('iconmapid', 'name'); // allowed columns for sorting
		$subselects_allowed_outputs = array(API_OUTPUT_REFER, API_OUTPUT_EXTEND); // allowed output options for [ select_* ] params

		$sql_parts = array(
			'select' => array('icon_map' => 'im.iconmapid'),
			'from' => array('icon_map' => 'icon_map im'),
			'where' => array(),
			'order' => array(),
			'limit' => null,
		);

		$def_options = array(
			'nodeids'				=> null,
			'iconmapids'			=> null,
			'sysmapids'				=> null,
			'nopermissions'			=> null,
			'editable'				=> null,
// filter
			'filter'				=> null,
			'search'				=> null,
			'searchByAny'			=> null,
			'startSearch'			=> null,
			'excludeSearch'			=> null,
// OutPut
			'output'				=> API_OUTPUT_REFER,
			'selectMappings'		=> null,
			'countOutput'			=> null,
			'preservekeys'			=> null,

			'sortfield'				=> '',
			'sortorder'				=> '',
			'limit'					=> null
		);
		$options = zbx_array_merge($def_options, $options);

		if(is_array($options['output'])){
			$dbTable = DB::getSchema('icon_map');

			foreach($options['output'] as $field){
				if(isset($dbTable['fields'][$field])){
					$sql_parts['select'][$field] = 'im.'.$field;
				}
			}
			$options['output'] = API_OUTPUT_CUSTOM;
		}

		// editable + PERMISSION CHECK
		if(USER_TYPE_SUPER_ADMIN == self::$userData['type']){
		}
		else if(is_null($options['editable']) && (self::$userData['type'] == USER_TYPE_ZABBIX_ADMIN)){
		}
		else if(!is_null($options['editable']) || (self::$userData['type']!=USER_TYPE_SUPER_ADMIN)){
			return array();
		}

		// nodeids
		$nodeids = !is_null($options['nodeids']) ? $options['nodeids'] : get_current_nodeid();

		// iconmapids
		if(!is_null($options['iconmapids'])){
			zbx_value2array($options['iconmapids']);

			$sql_parts['where'][] = DBcondition('im.iconmapid', $options['iconmapids']);
		}

		// sysmapids
		if(!is_null($options['sysmapids'])){
			zbx_value2array($options['sysmapids']);

			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['sysmapids'] = 's.sysmapid';
			}

			$sql_parts['from']['sysmaps'] = 'sysmaps s';
			$sql_parts['where'][] = DBcondition('s.sysmapid', $options['sysmapids']);
			$sql_parts['where']['ims'] = 'im.iconmapid=s.iconmapid';
		}

		// filter
		if(is_array($options['filter'])){
			zbx_db_filter('icon_map im', $options, $sql_parts);
		}
		// search
		if(is_array($options['search'])){
			zbx_db_search('icon_map im', $options, $sql_parts);
		}

		// output
		if($options['output'] == API_OUTPUT_EXTEND){
			$sql_parts['select']['icon_map'] = 'im.*';
		}

		// countOutput
		if(!is_null($options['countOutput'])){
			$options['sortfield'] = '';

			$sql_parts['select'] = array('COUNT(DISTINCT im.iconmapid) as rowscount');
		}

		// order
		// restrict not allowed columns for sorting
		$options['sortfield'] = str_in_array($options['sortfield'], $sort_columns) ? $options['sortfield'] : '';
		if(!zbx_empty($options['sortfield'])){
			$sortorder = ($options['sortorder'] == ZBX_SORT_DOWN)?ZBX_SORT_DOWN:ZBX_SORT_UP;

			$sql_parts['order'][] = 'im.'.$options['sortfield'].' '.$sortorder;

			if(!str_in_array('im.'.$options['sortfield'], $sql_parts['select']) && !str_in_array('im.*', $sql_parts['select'])){
				$sql_parts['select'][] = 'im.'.$options['sortfield'];
			}
		}

		// limit
		if(zbx_ctype_digit($options['limit']) && $options['limit']){
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
		if(!empty($sql_parts['select']))	$sql_select.= implode(',',$sql_parts['select']);
		if(!empty($sql_parts['from']))		$sql_from.= implode(',',$sql_parts['from']);
		if(!empty($sql_parts['where']))		$sql_where.= ' AND '.implode(' AND ',$sql_parts['where']);
		if(!empty($sql_parts['order']))		$sql_order.= ' ORDER BY '.implode(',',$sql_parts['order']);
		$sql_limit = $sql_parts['limit'];

		$sql = 'SELECT '.$sql_select.
				' FROM '.$sql_from.
				' WHERE '.DBin_node('im.iconmapid', $nodeids).
				$sql_where.
				$sql_order;
		//SDI($sql);
		$db_res = DBselect($sql, $sql_limit);
		while($iconMap = DBfetch($db_res)){

			if($options['countOutput']){
				$result = $iconMap['rowscount'];
			}
			else{
				$iconMapids[$iconMap['iconmapid']] = $iconMap['iconmapid'];

				if($options['output'] == API_OUTPUT_SHORTEN){
					$result[$iconMap['iconmapid']] = array('iconmapid' => $iconMap['iconmapid']);
				}
				else{
					if(!isset($result[$iconMap['iconmapid']])) $result[$iconMap['iconmapid']]= array();

					if(!is_null($options['selectMappings']) && !isset($result[$iconMap['iconmapid']]['mappings'])){
						$result[$iconMap['iconmapid']]['mappings'] = array();
					}

					$result[$iconMap['iconmapid']] += $iconMap;
				}
			}
		}

		if(!is_null($options['countOutput'])){
			return $result;
		}

		// Adding Objects
		// Adding Conditions
		if(!is_null($options['selectMappings']) && str_in_array($options['selectMappings'], $subselects_allowed_outputs)){
			$sql = 'SELECT imp.* FROM icon_mapping imp WHERE '.DBcondition('imp.iconmapid', $iconMapids);
			$res = DBselect($sql);
			while($mapping = DBfetch($res)){
				$result[$mapping['iconmapid']]['mappings'][$mapping['iconmapid']] = $mapping;
			}
		}

		// removing keys (hash -> array)
		if(is_null($options['preservekeys'])){
			$result = zbx_cleanHashes($result);
		}

		return $result;
	}

	public function exists(array $object){
		$keyFields = array(array('actionid', 'name'));

		$options = array(
			'filter' => zbx_array_mintersect($keyFields, $object),
			'output' => API_OUTPUT_SHORTEN,
			'nopermissions' => 1,
			'limit' => 1
		);

		if(isset($object['node']))
			$options['nodeids'] = getNodeIdByNodeName($object['node']);
		else if(isset($object['nodeids']))
			$options['nodeids'] = $object['nodeids'];

		$objs = $this->get($options);

		return !empty($objs);
	}

	/**
	 * Add IconMap
	 *
	 * @param array $iconMaps
	 * @return array
	 */
	public function create(array $iconMaps){
		$iconMaps = zbx_toArray($iconMaps);

		$iconMapRequiredFields = array(
			'name' => null
		);
		$duplicates = array();
		foreach($iconMaps as $iconMap){
			if(!check_db_fields($iconMapRequiredFields, $iconMap))
				self::exception(ZBX_API_ERROR_PARAMETERS, _s('Incorrect parameter is used for icon map "%s".', $iconMap['name']));

			if(isset($duplicates[$iconMap['name']]))
				self::exception(ZBX_API_ERROR_PARAMETERS, _s('Icon map "%s" already exists.', $iconMap['name']));
			else
				$duplicates[$iconMap['name']] = $iconMap['name'];
		}

		$options = array(
			'filter' => array('name' => $duplicates),
			'output' => array('name'),
			'editable' => true,
			'nopermissions' => true
		);
		$dbIconMaps = $this->get($options);
		foreach($dbIconMaps as $dbIconMap){
			self::exception(ZBX_API_ERROR_PARAMETERS, _s('Action "%s" already exists.', $dbIconMap['name']));
		}

		$iconMapids = DB::insert('icon_map', $iconMaps);


		$conditions = $operations = array();
		foreach($actions as $anum => $action){
			if(isset($action['conditions']) && !empty($action['conditions'])){
				foreach($action['conditions'] as $condition){
					$condition['actionid'] = $actionids[$anum];
					$conditions[] = $condition;
				}
			}

			if(!isset($action['operations']) || empty($action['operations'])){
				self::exception(ZBX_API_ERROR_PARAMETERS, _s('Incorrect parameter used for action "%s".', $action['name']));
			}
			else{
				foreach($action['operations'] as $operation){
					$operation['actionid'] = $actionids[$anum];
					$operations[] = $operation;
				}
			}
		}

		$this->validateConditions($conditions);
		$this->addConditions($conditions);

		return array('iconmapids' => $iconMapids);
	}

	/**
	 * Update IconMap
	 *
	 * @param array $iconmaps
	 * @return array
	 */
	public function update(array $iconmaps){
		//sdii($actions);
		$actions = zbx_toArray($actions);
		$actionids = zbx_objectValues($actions, 'actionid');
		$update = array();


		$options = array(
			'actionids' => $actionids,
			'editable' => true,
			'output' => API_OUTPUT_EXTEND,
			'preservekeys' => true,
			'selectOperations' => API_OUTPUT_EXTEND,
			'selectConditions' => API_OUTPUT_EXTEND,
		);
		$updActions = $this->get($options);
		foreach($actions as $action){
			if(isset($action['actionid']) && !isset($updActions[$action['actionid']])){
				self::exception(ZBX_API_ERROR_PERMISSIONS, S_NO_PERMISSIONS);
			}
		}

		// Check fields
		$duplicates = array();
		foreach($actions as $action){
			if(!check_db_fields(array('actionid' => null), $action)){
				self::exception(ZBX_API_ERROR_PARAMETERS, _s('Incorrect parameters are used for action update method "%s".',$action['name']));
			}

			// check if user change esc_period or eventsource
			if(isset($action['esc_period']) || isset($action['eventsource'])){
				$eventsource = isset($action['eventsource']) ? $action['eventsource']: $updActions[$action['actionid']]['eventsource'];
				$esc_period = isset($action['esc_period']) ? $action['esc_period']: $updActions[$action['actionid']]['esc_period'];

				if(($esc_period < 60) && (EVENT_SOURCE_TRIGGERS == $eventsource))
					self::exception(ZBX_API_ERROR_PARAMETERS, _s('Action "%s" has incorrect value for "esc_period" (minimum 60 seconds).', $action['name']));
			}
			//--
			if(!isset($action['name'])) continue;

			if(isset($duplicates[$action['name']]))
				self::exception(ZBX_API_ERROR_PARAMETERS, _s('Action "%s" already exists.', $action['name']));
			else
				$duplicates[$action['name']] = $action['name'];
		}
		//------

		$operationsCreate = $operationsUpdate = $operationidsDelete = array();
		$conditionsCreate = $conditionsUpdate = $conditionidsDelete = array();
		foreach($actions as $action){
			// Existance
			$options = array(
				'filter' => array( 'name' => $action['name'] ),
				'output' => API_OUTPUT_SHORTEN,
				'editable' => 1,
				'nopermissions' => true,
				'preservekeys' => true,
			);
			$action_exists = $this->get($options);
			if(($action_exist = reset($action_exists)) && (bccomp($action_exist['actionid'],$action['actionid']) != 0)){
				self::exception(ZBX_API_ERROR_PARAMETERS, _s('Action "%s" already exists.', $action['name']));
			}
			//----

			if(isset($action['conditions'])){
				$conditionsDb = isset($updActions[$action['actionid']]['conditions'])
						? $updActions[$action['actionid']]['conditions']
						: array();

				$this->validateConditions($action['conditions']);

				foreach($action['conditions'] as $condition){
					$condition['actionid'] = $action['actionid'];

					if(!isset($condition['conditionid'])){
						$conditionsCreate[] = $condition;
					}
					else if(isset($conditionsDb[$condition['conditionid']])){
						$conditionsUpdate[] = $condition;
						unset($conditionsDb[$condition['conditionid']]);
					}
					else{
						self::exception(ZBX_API_ERROR_PARAMETERS, _('Incorrect action conditionid'));
					}
				}

				$conditionidsDelete = array_merge($conditionidsDelete, array_keys($conditionsDb));
			}

			if(isset($action['operations']) && empty($action['operations'])){
				self::exception(ZBX_API_ERROR_PARAMETERS, _s('Action "%s" no operations defined.', $action['name']));
			}
			else if(isset($action['operations'])){
				$this->validateOperations($action['operations']);

				$operations_db = $updActions[$action['actionid']]['operations'];
				foreach($action['operations'] as $operation){
					$operation['actionid'] = $action['actionid'];

					if(!isset($operation['operationid'])){
						$operationsCreate[] = $operation;
					}
					else if(isset($operations_db[$operation['operationid']])){
						$operationsUpdate[] = $operation;
						unset($operations_db[$operation['operationid']]);
					}
					else{
						self::exception(ZBX_API_ERROR_PARAMETERS, _('Incorrect action operationid'));
					}
				}
				$operationidsDelete = array_merge($operationidsDelete, array_keys($operations_db));
			}

			$actionid = $action['actionid'];
			unset($action['actionid']);
			if(!empty($action)){
				$update[] = array(
					'values' => $action,
					'where' => array('actionid' => $actionid),
				);
			}
		}

		DB::update('actions', $update);

		$this->addConditions($conditionsCreate);
		$this->updateConditions($conditionsUpdate);
		if(!empty($conditionidsDelete))
			$this->deleteConditions($conditionidsDelete);

		$this->addOperations($operationsCreate);
		$this->updateOperations($operationsUpdate, $updActions);
		if(!empty($operationidsDelete))
			$this->deleteOperations($operationidsDelete);


		return array('actionids' => $actionids);
	}

	/**
	 * Delete IconMap
	 *
	 * @param array $iconmapids
	 * @return array
	 */
	public function delete(array $iconmapids){
		$iconmapids = zbx_toArray($iconmapids);

		if(empty($iconmapids)){
			self::exception(ZBX_API_ERROR_PARAMETERS, _('Empty input parameter'));
		}
		if(!$this->isWritable($iconmapids)){
			self::exception(ZBX_API_ERROR_PERMISSIONS, S_NO_PERMISSION);
		}

		DB::delete('icon_map', array('iconmapid' => $iconmapids));

		return array('iconmapids' => $iconmapids);
	}

	public function isReadable($ids){
		if(!is_array($ids)) return false;
		if(empty($ids)) return true;

		$ids = array_unique($ids);

		$count = $this->get(array(
			'nodeids' => get_current_nodeid(true),
			'iconmapids' => $ids,
			'output' => API_OUTPUT_SHORTEN,
			'countOutput' => true
		));

		return (count($ids) == $count);
	}

	public function isWritable($ids){
		if(!is_array($ids)) return false;
		if(empty($ids)) return true;

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

}
?>
