<?php
/*
** Zabbix
** Copyright (C) 2000-2012 Zabbix SIA
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
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
**/


/**
 * File containing CEvent class for API.
 *
 * @package API
 */
class CEvent extends CZBXAPI {

	protected $tableName = 'events';
	protected $tableAlias = 'e';
	protected $sortColumns = array('eventid', 'object', 'objectid');

	/**
	 * Array of supported objects where keys are object IDs and values are translated object names.
	 *
	 * @var array
	 */
	protected $objects = array();

	/**
	 * Array of supported sources where keys are source IDs and values are translated source names.
	 *
	 * @var array
	 */
	protected $sources = array();

	public function __construct() {
		parent::__construct();

		$this->sources = array(
			EVENT_SOURCE_TRIGGERS => _('trigger'),
			EVENT_SOURCE_DISCOVERY => _('discovery'),
			EVENT_SOURCE_AUTO_REGISTRATION => _('auto registration'),
			EVENT_SOURCE_INTERNAL => _('internal'),
		);

		$this->objects = array(
			EVENT_OBJECT_TRIGGER => _('trigger'),
			EVENT_OBJECT_DHOST => _('discovered host'),
			EVENT_OBJECT_DSERVICE => _('discovered service'),
			EVENT_OBJECT_AUTOREGHOST => _('auto-registered host'),
			EVENT_OBJECT_ITEM => _('item'),
			EVENT_OBJECT_LLDRULE => _('low-level discovery rule'),
		);
	}

	/**
	 * Get events data.
	 *
	 * @param _array $options
	 * @param array $options['itemids']
	 * @param array $options['hostids']
	 * @param array $options['groupids']
	 * @param array $options['eventids']
	 * @param array $options['applicationids']
	 * @param array $options['status']
	 * @param array $options['editable']
	 * @param array $options['count']
	 * @param array $options['pattern']
	 * @param array $options['limit']
	 * @param array $options['order']
	 *
	 * @return array|int item data as array or false if error
	 */
	public function get($options = array()) {
		$result = array();
		$nodeCheck = false;
		$userType = self::$userData['type'];
		$userid = self::$userData['userid'];

		$sqlParts = array(
			'select'	=> array($this->fieldId('eventid')),
			'from'		=> array('events' => 'events e'),
			'where'		=> array(),
			'order'		=> array(),
			'group'		=> array(),
			'limit'		=> null
		);

		$defOptions = array(
			'nodeids'					=> null,
			'groupids'					=> null,
			'hostids'					=> null,
			'triggerids'				=> null,
			'eventids'					=> null,
			'editable'					=> null,
			'object'					=> EVENT_OBJECT_TRIGGER,
			'source'					=> EVENT_SOURCE_TRIGGERS,
			'acknowledged'				=> null,
			'nopermissions'				=> null,
			// filter
			'value'						=> null,
			'time_from'					=> null,
			'time_till'					=> null,
			'eventid_from'				=> null,
			'eventid_till'				=> null,
			// filter
			'filter'					=> null,
			'search'					=> null,
			'searchByAny'				=> null,
			'startSearch'				=> null,
			'excludeSearch'				=> null,
			'searchWildcardsEnabled'	=> null,
			// output
			'output'					=> API_OUTPUT_REFER,
			'selectHosts'				=> null,
			'selectItems'				=> null,
			'selectTriggers'			=> null,
			'select_alerts'				=> null,
			'select_acknowledges'		=> null,
			'countOutput'				=> null,
			'groupCount'				=> null,
			'preservekeys'				=> null,
			'sortfield'					=> '',
			'sortorder'					=> '',
			'limit'						=> null
		);
		$options = zbx_array_merge($defOptions, $options);

		$this->validateGet($options);

		// editable + PERMISSION CHECK
		if ($userType != USER_TYPE_SUPER_ADMIN && !$options['nopermissions']) {
			if ($options['object'] == EVENT_OBJECT_TRIGGER) {
				if (!is_null($options['triggerids'])) {
					$triggers = API::Trigger()->get(array(
						'triggerids' => $options['triggerids'],
						'editable' => $options['editable']
					));
					$options['triggerids'] = zbx_objectValues($triggers, 'triggerid');
				}
				else {
					$permission = $options['editable'] ? PERM_READ_WRITE : PERM_READ;

					$userGroups = getUserGroupsByUserId($userid);

					$sqlParts['where'][] = 'EXISTS ('.
							'SELECT NULL'.
							' FROM functions f,items i,hosts_groups hgg'.
								' JOIN rights r'.
									' ON r.id=hgg.groupid'.
										' AND '.dbConditionInt('r.groupid', $userGroups).
							' WHERE e.objectid=f.triggerid'.
								' AND f.itemid=i.itemid'.
								' AND i.hostid=hgg.hostid'.
								' AND e.object='.EVENT_OBJECT_TRIGGER.
							' GROUP BY f.triggerid'.
							' HAVING MIN(r.permission)>'.PERM_DENY.
								' AND MAX(r.permission)>='.$permission.
							')';
				}
			}
		}

		// nodeids
		$nodeids = !is_null($options['nodeids']) ? $options['nodeids'] : get_current_nodeid();

		// eventids
		if (!is_null($options['eventids'])) {
			zbx_value2array($options['eventids']);
			$sqlParts['where'][] = dbConditionInt('e.eventid', $options['eventids']);

			if (!$nodeCheck) {
				$nodeCheck = true;
				$sqlParts['where'] = sqlPartDbNode($sqlParts['where'], 'e.objectid', $nodeids);
			}
		}

		// triggerids
		if (!is_null($options['triggerids']) && $options['object'] == EVENT_OBJECT_TRIGGER) {
			zbx_value2array($options['triggerids']);
			$sqlParts['where'][] = dbConditionInt('e.objectid', $options['triggerids']);

			if (!is_null($options['groupCount'])) {
				$sqlParts['group']['objectid'] = 'e.objectid';
			}

			if (!$nodeCheck) {
				$nodeCheck = true;
				$sqlParts['where'] = sqlPartDbNode($sqlParts['where'], 'e.objectid', $nodeids);
			}
		}

		// groupids
		if (!is_null($options['groupids'])) {
			zbx_value2array($options['groupids']);

			$sqlParts = $this->addQuerySelect('hg.groupid', $sqlParts);
			$sqlParts['from']['functions'] = 'functions f';
			$sqlParts['from']['items'] = 'items i';
			$sqlParts['from']['hosts_groups'] = 'hosts_groups hg';
			$sqlParts['where']['hg'] = dbConditionInt('hg.groupid', $options['groupids']);
			$sqlParts['where']['hgi'] = 'hg.hostid=i.hostid';
			$sqlParts['where']['fe'] = 'f.triggerid=e.objectid';
			$sqlParts['where']['fi'] = 'f.itemid=i.itemid';
		}

		// hostids
		if (!is_null($options['hostids'])) {
			zbx_value2array($options['hostids']);

			$sqlParts = $this->addQuerySelect('i.hostid', $sqlParts);
			$sqlParts['from']['functions'] = 'functions f';
			$sqlParts['from']['items'] = 'items i';
			$sqlParts['where']['i'] = dbConditionInt('i.hostid', $options['hostids']);
			$sqlParts['where']['ft'] = 'f.triggerid=e.objectid';
			$sqlParts['where']['fi'] = 'f.itemid=i.itemid';
		}

		// should last, after all ****IDS checks
		if (!$nodeCheck) {
			$sqlParts['where'] = sqlPartDbNode($sqlParts['where'], 'e.eventid', $nodeids);
		}

		// object
		if (!is_null($options['object'])) {
			$sqlParts['where']['o'] = 'e.object='.$options['object'];
		}

		// source
		if (!is_null($options['source'])) {
			$sqlParts['where'][] = 'e.source='.$options['source'];
		}

		// acknowledged
		if (!is_null($options['acknowledged'])) {
			$sqlParts['where'][] = 'e.acknowledged='.($options['acknowledged'] ? 1 : 0);
		}

		// time_from
		if (!is_null($options['time_from'])) {
			$sqlParts['where'][] = 'e.clock>='.$options['time_from'];
		}

		// time_till
		if (!is_null($options['time_till'])) {
			$sqlParts['where'][] = 'e.clock<='.$options['time_till'];
		}

		// eventid_from
		if (!is_null($options['eventid_from'])) {
			$sqlParts['where'][] = 'e.eventid>='.$options['eventid_from'];
		}

		// eventid_till
		if (!is_null($options['eventid_till'])) {
			$sqlParts['where'][] = 'e.eventid<='.$options['eventid_till'];
		}

		// value
		if (!is_null($options['value'])) {
			zbx_value2array($options['value']);
			$sqlParts['where'][] = dbConditionInt('e.value', $options['value']);
		}

		// search
		if (is_array($options['search'])) {
			zbx_db_search('events e', $options, $sqlParts);
		}

		// filter
		if (is_array($options['filter'])) {
			$this->dbFilter('events e', $options, $sqlParts);
		}

		// limit
		if (zbx_ctype_digit($options['limit']) && $options['limit']) {
			$sqlParts['limit'] = $options['limit'];
		}

		// selectHosts, selectTriggers, selectItems
		if ($options['output'] != API_OUTPUT_EXTEND && (!is_null($options['selectHosts']) || !is_null($options['selectTriggers']) || !is_null($options['selectItems']))) {
			$sqlParts = $this->addQuerySelect($this->fieldId('object'), $sqlParts);
			$sqlParts = $this->addQuerySelect($this->fieldId('objectid'), $sqlParts);
		}

		$sqlParts = $this->applyQueryOutputOptions($this->tableName(), $this->tableAlias(), $options, $sqlParts);
		$sqlParts = $this->applyQuerySortOptions($this->tableName(), $this->tableAlias(), $options, $sqlParts);
		$sqlParts = $this->applyQueryNodeOptions($this->tableName(), $this->tableAlias(), $options, $sqlParts);
		$res = DBselect($this->createSelectQueryFromParts($sqlParts), $sqlParts['limit']);
		while ($event = DBfetch($res)) {
			if (!is_null($options['countOutput'])) {
				if (!is_null($options['groupCount'])) {
					$result[] = $event;
				}
				else {
					$result = $event['rowscount'];
				}
			}
			else {
				if (!isset($result[$event['eventid']])) {
					$result[$event['eventid']]= array();
				}

				// hostids
				if (isset($event['hostid']) && is_null($options['selectHosts'])) {
					if (!isset($result[$event['eventid']]['hosts'])) {
						$result[$event['eventid']]['hosts'] = array();
					}
					$result[$event['eventid']]['hosts'][] = array('hostid' => $event['hostid']);
					unset($event['hostid']);
				}

				// triggerids
				if (isset($event['triggerid']) && is_null($options['selectTriggers'])) {
					if (!isset($result[$event['eventid']]['triggers'])) {
						$result[$event['eventid']]['triggers'] = array();
					}
					$result[$event['eventid']]['triggers'][] = array('triggerid' => $event['triggerid']);
					unset($event['triggerid']);
				}

				// itemids
				if (isset($event['itemid']) && is_null($options['selectItems'])) {
					if (!isset($result[$event['eventid']]['items'])) {
						$result[$event['eventid']]['items'] = array();
					}
					$result[$event['eventid']]['items'][] = array('itemid' => $event['itemid']);
					unset($event['itemid']);
				}
				$result[$event['eventid']] += $event;
			}
		}

		if (!is_null($options['countOutput'])) {
			return $result;
		}

		if ($result) {
			$result = $this->addRelatedObjects($options, $result);
			$result = $this->unsetExtraFields($result, array('object', 'objectid'), $options['output']);
		}

		// removing keys (hash -> array)
		if (is_null($options['preservekeys'])) {
			$result = zbx_cleanHashes($result);
		}

		return $result;
	}

	/**
	 * Validates the input parameters for the get() method.
	 *
	 * @throws APIException     if the input is invalid
	 *
	 * @param array     $options
	 *
	 * @return void
	 */
	protected function validateGet(array $options) {
		$this->checkSource($options);
		$this->checkObject($options);
		$this->checkSourceObject($options);
	}

	public function acknowledge($data) {
		$eventids = isset($data['eventids']) ? zbx_toArray($data['eventids']) : array();
		$eventids = zbx_toHash($eventids);

		$allowedEvents = $this->get(array(
			'eventids' => $eventids,
			'output' => API_OUTPUT_REFER,
			'preservekeys' => true
		));
		foreach ($eventids as $eventid) {
			if (!isset($allowedEvents[$eventid])) {
				self::exception(ZBX_API_ERROR_PERMISSIONS, _('No permissions to referred object or it does not exist!'));
			}
		}

		$sql = 'UPDATE events SET acknowledged=1 WHERE '.dbConditionInt('eventid', $eventids);
		if (!DBexecute($sql)) {
			self::exception(ZBX_API_ERROR_PARAMETERS, 'DBerror');
		}

		$time = time();
		$dataInsert = array();
		foreach ($eventids as $eventid) {
			$dataInsert[] = array(
				'userid' => self::$userData['userid'],
				'eventid' => $eventid,
				'clock' => $time,
				'message'=> $data['message']
			);
		}

		DB::insert('acknowledges', $dataInsert);

		return array('eventids' => array_values($eventids));
	}

	protected function applyQueryOutputOptions($tableName, $tableAlias, array $options, array $sqlParts) {
		$sqlParts = parent::applyQueryOutputOptions($tableName, $tableAlias, $options, $sqlParts);

		if ($options['countOutput'] === null) {
			if ($options['selectTriggers'] !== null) {
				$sqlParts = $this->addQuerySelect('e.object', $sqlParts);
				$sqlParts = $this->addQuerySelect('e.objectid', $sqlParts);
			}
		}

		return $sqlParts;
	}

	protected function addRelatedObjects(array $options, array $result) {
		$result = parent::addRelatedObjects($options, $result);

		$eventIds = array_keys($result);

		// adding hosts
		if ($options['selectHosts'] !== null && $options['selectHosts'] != API_OUTPUT_COUNT) {
			$relationMap = new CRelationMap();
			// discovered items
			$dbRules = DBselect(
				'SELECT e.eventid,i.hostid'.
					' FROM events e,functions f,items i'.
					' WHERE '.dbConditionInt('e.eventid', $eventIds).
					' AND e.objectid=f.triggerid'.
					' AND f.itemid=i.itemid'.
					' AND e.object='.EVENT_OBJECT_TRIGGER
			);
			while ($relation = DBfetch($dbRules)) {
				$relationMap->addRelation($relation['eventid'], $relation['hostid']);
			}

			$hosts = API::Host()->get(array(
				'nodeids' => $options['nodeids'],
				'output' => $options['selectHosts'],
				'hostids' => $relationMap->getRelatedIds(),
				'nopermissions' => true,
				'preservekeys' => true
			));
			$result = $relationMap->mapMany($result, $hosts, 'hosts');
		}

		// adding triggers
		if ($options['selectTriggers'] !== null && $options['selectTriggers'] != API_OUTPUT_COUNT) {
			$relationMap = new CRelationMap();
			foreach ($result as $event) {
				if ($event['object'] == EVENT_OBJECT_TRIGGER) {
					$relationMap->addRelation($event['eventid'], $event['objectid']);
				}
			}

			$triggers = API::Trigger()->get(array(
				'nodeids' => $options['nodeids'],
				'output' => $options['selectTriggers'],
				'triggerids' => $relationMap->getRelatedIds(),
				'nopermissions' => true,
				'preservekeys' => true
			));
			$result = $relationMap->mapMany($result, $triggers, 'triggers');
		}

		// adding items
		if ($options['selectItems'] !== null && $options['selectItems'] != API_OUTPUT_COUNT) {
			$relationMap = new CRelationMap();
			// discovered items
			$dbRules = DBselect(
				'SELECT e.eventid,f.itemid'.
					' FROM events e,functions f'.
					' WHERE '.dbConditionInt('e.eventid', $eventIds).
					' AND e.objectid=f.triggerid'.
					' AND e.object='.EVENT_OBJECT_TRIGGER
			);
			while ($relation = DBfetch($dbRules)) {
				$relationMap->addRelation($relation['eventid'], $relation['itemid']);
			}

			$items = API::Item()->get(array(
				'nodeids' => $options['nodeids'],
				'output' => $options['selectItems'],
				'itemids' => $relationMap->getRelatedIds(),
				'webitems' => true,
				'nopermissions' => true,
				'preservekeys' => true
			));
			$result = $relationMap->mapMany($result, $items, 'items');
		}

		// adding alerts
		if ($options['select_alerts'] !== null && $options['select_alerts'] != API_OUTPUT_COUNT) {
			$relationMap = $this->createRelationMap($result, 'eventid', 'alertid', 'alerts');
			$alerts = API::Alert()->get(array(
				'output' => $options['select_alerts'],
				'selectMediatypes' => API_OUTPUT_EXTEND,
				'nodeids' => $options['nodeids'],
				'alertids' => $relationMap->getRelatedIds(),
				'nopermissions' => true,
				'preservekeys' => true,
				'sortfield' => 'clock',
				'sortorder' => ZBX_SORT_DOWN
			));
			$result = $relationMap->mapMany($result, $alerts, 'alerts');
		}

		// adding acknowledges
		if ($options['select_acknowledges'] !== null) {
			if ($options['select_acknowledges'] != API_OUTPUT_COUNT) {
				// create the base query
				$sqlParts = API::getApi()->createSelectQueryParts('acknowledges', 'a', array(
					'output' => $this->outputExtend('acknowledges',
						array('acknowledgeid', 'eventid', 'clock'), $options['select_acknowledges']
					),
					'filter' => array('eventid' => $eventIds)
				));
				$sqlParts['order'][] = 'a.clock DESC';

				// if the user alias is requested, join the users table
				if ($this->outputIsRequested('alias', $options['select_acknowledges'])) {
					$sqlParts = $this->addQuerySelect('u.alias', $sqlParts);
					$sqlParts['from'][] = 'users u';
					$sqlParts['where'][] = 'a.userid=u.userid';
				}

				$acknowledges = DBFetchArrayAssoc(DBselect($this->createSelectQueryFromParts($sqlParts)), 'acknowledgeid');
				$relationMap = $this->createRelationMap($acknowledges, 'eventid', 'acknowledgeid');

				$acknowledges = $this->unsetExtraFields($acknowledges, array('eventid', 'acknowledgeid', 'clock'),
					$options['select_acknowledges']
				);
				$result = $relationMap->mapMany($result, $acknowledges, 'acknowledges');
			}
			else {
				$acknowledges = DBFetchArrayAssoc(DBselect(
					'SELECT COUNT(a.acknowledgeid) AS rowscount,a.eventid'.
						' FROM acknowledges a'.
						' WHERE '.dbConditionInt('a.eventid', $eventIds).
						' GROUP BY a.eventid'
				), 'eventid');
				foreach ($result as &$event) {
					if ((isset($acknowledges[$event['eventid']]))) {
						$event['acknowledges'] = $acknowledges[$event['eventid']]['rowscount'];
					}
					else {
						$event['acknowledges'] = 0;
					}
				}
				unset($event);
			}
		}

		return $result;
	}

	/**
	 * Validates the "source" parameter.
	 *
	 * @throws APIException     if the source is incorrect
	 *
	 * @param array $object
	 *
	 * @return void
	 */
	protected function checkSource(array $object) {
		if (!isset($this->sources[$object['source']])) {
			self::exception(ZBX_API_ERROR_PARAMETERS, _('Incorrect source value.'));
		}
	}

	/**
	 * Validates the "object" parameter.
	 *
	 * @throws APIException     if the object is incorrect
	 *
	 * @param array $object
	 *
	 * @return void
	 */
	protected function checkObject(array $object) {
		if (!isset($this->objects[$object['object']])) {
			self::exception(ZBX_API_ERROR_PARAMETERS, _('Incorrect object value.'));
		}
	}

	/**
	 * Validates the given object is supported by the source.
	 *
	 * @throws APIException     if the object is not supported by the source
	 *
	 * @param array $object
	 *
	 * @return void
	 */
	protected function checkSourceObject(array $object) {
		$pairs = array(
			EVENT_SOURCE_TRIGGERS => array(
				EVENT_OBJECT_TRIGGER => 1
			),
			EVENT_SOURCE_DISCOVERY => array(
				EVENT_OBJECT_DHOST => 1,
				EVENT_OBJECT_DSERVICE => 1
			),
			EVENT_SOURCE_AUTO_REGISTRATION => array(
				EVENT_OBJECT_AUTOREGHOST => 1
			),
			EVENT_SOURCE_INTERNAL => array(
				EVENT_OBJECT_TRIGGER => 1,
				EVENT_OBJECT_ITEM => 1,
				EVENT_OBJECT_LLDRULE => 1
			)
		);

		$objects = $pairs[$object['source']];
		if (!isset($objects[$object['object']])) {
			$supportedObjects = '';
			foreach ($objects as $object => $i) {
				$supportedObjects .= $object.' - '.$this->objects[$object].', ';
			}

			self::exception(ZBX_API_ERROR_PARAMETERS,
				_s('Incorrect object "%1$s" (%2$s) for source "%3$s" (%4$s), only the following objects are supported: %5$s.',
					$object['object'],
					$this->objects[$object['object']],
					$object['source'],
					$this->sources[$object['source']],
					rtrim($supportedObjects, ', ')
				)
			);
		}
	}
}
