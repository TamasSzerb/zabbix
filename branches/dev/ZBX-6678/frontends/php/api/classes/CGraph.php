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
 * File containing graph class for API.
 * @package API
 */
/**
 * Class containing methods for operations with graphs
 */
class CGraph extends CGraphGeneral {

	protected $tableName = 'graphs';
	protected $tableAlias = 'g';
	protected $sortColumns = array('graphid', 'name', 'graphtype');

	public function __construct() {
		parent::__construct();

		$this->errorMessages = array_merge($this->errorMessages, array(
			self::ERROR_TEMPLATE_HOST_MIX => _('Graph "%1$s" with templated items cannot contain items from other hosts.')
		));
	}

	/**
	* Get graph data
	*
	* @param array $options
	* @return array
	*/
	public function get($options = array()) {
		$result = array();
		$userType = self::$userData['type'];
		$userid = self::$userData['userid'];

		$sqlParts = array(
			'select'	=> array('graphs' => 'g.graphid'),
			'from'		=> array('graphs' => 'graphs g'),
			'where'		=> array(),
			'group'		=> array(),
			'order'		=> array(),
			'limit'		=> null
		);

		$defOptions = array(
			'nodeids'					=> null,
			'groupids'					=> null,
			'templateids'				=> null,
			'hostids'					=> null,
			'graphids'					=> null,
			'itemids'					=> null,
			'templated'					=> null,
			'inherited'					=> null,
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
			'selectGroups'				=> null,
			'selectTemplates'			=> null,
			'selectHosts'				=> null,
			'selectItems'				=> null,
			'selectGraphItems'			=> null,
			'selectDiscoveryRule'		=> null,
			'countOutput'				=> null,
			'groupCount'				=> null,
			'preservekeys'				=> null,
			'sortfield'					=> '',
			'sortorder'					=> '',
			'limit'						=> null
		);
		$options = zbx_array_merge($defOptions, $options);

		// permission check
		if ($userType != USER_TYPE_SUPER_ADMIN && !$options['nopermissions']) {
			$permission = $options['editable'] ? PERM_READ_WRITE : PERM_READ;

			$userGroups = getUserGroupsByUserId($userid);

			$sqlParts['where'][] = 'NOT EXISTS ('.
				'SELECT NULL'.
				' FROM graphs_items gi,items i,hosts_groups hgg'.
					' LEFT JOIN rights r'.
						' ON r.id=hgg.groupid'.
							' AND '.dbConditionInt('r.groupid', $userGroups).
				' WHERE (g.graphid=gi.graphid'.
						' AND gi.itemid=i.itemid'.
						' OR g.ymin_type='.GRAPH_YAXIS_TYPE_ITEM_VALUE.
						' AND g.ymin_itemid=i.itemid'.
						' OR g.ymax_type='.GRAPH_YAXIS_TYPE_ITEM_VALUE.
						' AND g.ymax_itemid=i.itemid'.
					')'.
					' AND i.hostid=hgg.hostid'.
				' GROUP BY i.hostid'.
				' HAVING MAX(permission)<'.$permission.
					' OR MIN(permission) IS NULL'.
					' OR MIN(permission)='.PERM_DENY.
				')';
		}

		// groupids
		if (!is_null($options['groupids'])) {
			zbx_value2array($options['groupids']);

			$sqlParts['select']['groupid'] = 'hg.groupid';
			$sqlParts['from']['graphs_items'] = 'graphs_items gi';
			$sqlParts['from']['items'] = 'items i';
			$sqlParts['from']['hosts_groups'] = 'hosts_groups hg';
			$sqlParts['where'][] = dbConditionInt('hg.groupid', $options['groupids']);
			$sqlParts['where'][] = 'hg.hostid=i.hostid';
			$sqlParts['where']['gig'] = 'gi.graphid=g.graphid';
			$sqlParts['where']['igi'] = 'i.itemid=gi.itemid';
			$sqlParts['where']['hgi'] = 'hg.hostid=i.hostid';

			if (!is_null($options['groupCount'])) {
				$sqlParts['group']['hg'] = 'hg.groupid';
			}
		}

		// templateids
		if (!is_null($options['templateids'])) {
			zbx_value2array($options['templateids']);

			if (!is_null($options['hostids'])) {
				zbx_value2array($options['hostids']);
				$options['hostids'] = array_merge($options['hostids'], $options['templateids']);
			}
			else {
				$options['hostids'] = $options['templateids'];
			}
		}

		// hostids
		if (!is_null($options['hostids'])) {
			zbx_value2array($options['hostids']);

			$sqlParts['select']['hostid'] = 'i.hostid';
			$sqlParts['from']['graphs_items'] = 'graphs_items gi';
			$sqlParts['from']['items'] = 'items i';
			$sqlParts['where'][] = dbConditionInt('i.hostid', $options['hostids']);
			$sqlParts['where']['gig'] = 'gi.graphid=g.graphid';
			$sqlParts['where']['igi'] = 'i.itemid=gi.itemid';

			if (!is_null($options['groupCount'])) {
				$sqlParts['group']['i'] = 'i.hostid';
			}
		}

		// graphids
		if (!is_null($options['graphids'])) {
			zbx_value2array($options['graphids']);

			$sqlParts['where'][] = dbConditionInt('g.graphid', $options['graphids']);
		}

		// itemids
		if (!is_null($options['itemids'])) {
			zbx_value2array($options['itemids']);

			$sqlParts['select']['itemid'] = 'gi.itemid';
			$sqlParts['from']['graphs_items'] = 'graphs_items gi';
			$sqlParts['where']['gig'] = 'gi.graphid=g.graphid';
			$sqlParts['where'][] = dbConditionInt('gi.itemid', $options['itemids']);

			if (!is_null($options['groupCount'])) {
				$sqlParts['group']['gi'] = 'gi.itemid';
			}
		}

		// templated
		if (!is_null($options['templated'])) {
			$sqlParts['from']['graphs_items'] = 'graphs_items gi';
			$sqlParts['from']['items'] = 'items i';
			$sqlParts['from']['hosts'] = 'hosts h';
			$sqlParts['where']['igi'] = 'i.itemid=gi.itemid';
			$sqlParts['where']['ggi'] = 'g.graphid=gi.graphid';
			$sqlParts['where']['hi'] = 'h.hostid=i.hostid';

			if ($options['templated']) {
				$sqlParts['where'][] = 'h.status='.HOST_STATUS_TEMPLATE;
			}
			else {
				$sqlParts['where'][] = 'h.status<>'.HOST_STATUS_TEMPLATE;
			}
		}

		// inherited
		if (!is_null($options['inherited'])) {
			if ($options['inherited']) {
				$sqlParts['where'][] = 'g.templateid IS NOT NULL';
			}
			else {
				$sqlParts['where'][] = 'g.templateid IS NULL';
			}
		}

		// search
		if (is_array($options['search'])) {
			zbx_db_search('graphs g', $options, $sqlParts);
		}

		// filter
		if (is_null($options['filter'])) {
			$options['filter'] = array();
		}

		if (is_array($options['filter'])) {
			if (!array_key_exists('flags', $options['filter'])) {
				$options['filter']['flags'] = array(ZBX_FLAG_DISCOVERY_NORMAL, ZBX_FLAG_DISCOVERY_CREATED);
			}

			$this->dbFilter('graphs g', $options, $sqlParts);

			if (isset($options['filter']['host'])) {
				zbx_value2array($options['filter']['host']);

				$sqlParts['from']['graphs_items'] = 'graphs_items gi';
				$sqlParts['from']['items'] = 'items i';
				$sqlParts['from']['hosts'] = 'hosts h';
				$sqlParts['where']['gig'] = 'gi.graphid=g.graphid';
				$sqlParts['where']['igi'] = 'i.itemid=gi.itemid';
				$sqlParts['where']['hi'] = 'h.hostid=i.hostid';
				$sqlParts['where']['host'] = dbConditionString('h.host', $options['filter']['host']);
			}

			if (isset($options['filter']['hostid'])) {
				zbx_value2array($options['filter']['hostid']);

				$sqlParts['from']['graphs_items'] = 'graphs_items gi';
				$sqlParts['from']['items'] = 'items i';
				$sqlParts['where']['gig'] = 'gi.graphid=g.graphid';
				$sqlParts['where']['igi'] = 'i.itemid=gi.itemid';
				$sqlParts['where']['hostid'] = dbConditionInt('i.hostid', $options['filter']['hostid']);
			}
		}

		// limit
		if (zbx_ctype_digit($options['limit']) && $options['limit']) {
			$sqlParts['limit'] = $options['limit'];
		}

		$graphids = array();

		$sqlParts = $this->applyQueryOutputOptions($this->tableName(), $this->tableAlias(), $options, $sqlParts);
		$sqlParts = $this->applyQuerySortOptions($this->tableName(), $this->tableAlias(), $options, $sqlParts);
		$sqlParts = $this->applyQueryNodeOptions($this->tableName(), $this->tableAlias(), $options, $sqlParts);
		$dbRes = DBselect($this->createSelectQueryFromParts($sqlParts), $sqlParts['limit']);
		while ($graph = DBfetch($dbRes)) {
			if (!is_null($options['countOutput'])) {
				if (!is_null($options['groupCount'])) {
					$result[] = $graph;
				}
				else {
					$result = $graph['rowscount'];
				}
			}
			else {
				$graphids[$graph['graphid']] = $graph['graphid'];

				if (!isset($result[$graph['graphid']])) {
					$result[$graph['graphid']] = array();
				}

				// hostids
				if (isset($graph['hostid']) && is_null($options['selectHosts'])) {
					if (!isset($result[$graph['graphid']]['hosts'])) {
						$result[$graph['graphid']]['hosts'] = array();
					}
					$result[$graph['graphid']]['hosts'][] = array('hostid' => $graph['hostid']);
					unset($graph['hostid']);
				}

				// itemids
				if (isset($graph['itemid']) && is_null($options['selectItems'])) {
					if (!isset($result[$graph['graphid']]['items'])) {
						$result[$graph['graphid']]['items'] = array();
					}
					$result[$graph['graphid']]['items'][] = array('itemid' => $graph['itemid']);
					unset($graph['itemid']);
				}
				$result[$graph['graphid']] += $graph;
			}
		}

		if (!is_null($options['countOutput'])) {
			return $result;
		}

		if (isset($options['expandName'])) {
			$result = CMacrosResolverHelper::resolveGraphNameByIds($result);
		}

		if ($result) {
			$result = $this->addRelatedObjects($options, $result);
		}

		// removing keys (hash -> array)
		if (is_null($options['preservekeys'])) {
			$result = zbx_cleanHashes($result);
		}

		return $result;
	}

	protected function inherit($graph, $hostids = null) {
		$graphTemplates = API::Template()->get(array(
			'itemids' => zbx_objectValues($graph['gitems'], 'itemid'),
			'output' => array('templateid'),
			'nopermissions' => true
		));

		if (empty($graphTemplates)) {
			return true;
		}

		$graphTemplate = reset($graphTemplates);

		$chdHosts = API::Host()->get(array(
			'templateids' => $graphTemplate['templateid'],
			'output' => array('hostid', 'host'),
			'preservekeys' => true,
			'hostids' => $hostids,
			'nopermissions' => true,
			'templated_hosts' => true
		));

		$graph = $this->get(array(
			'graphids' => $graph['graphid'],
			'nopermissions' => true,
			'selectItems' => API_OUTPUT_EXTEND,
			'selectGraphItems' => API_OUTPUT_EXTEND,
			'output' => API_OUTPUT_EXTEND
		));
		$graph = reset($graph);

		foreach ($chdHosts as $chdHost) {
			$tmpGraph = $graph;
			$tmpGraph['templateid'] = $graph['graphid'];

			if (!$tmpGraph['gitems'] = get_same_graphitems_for_host($tmpGraph['gitems'], $chdHost['hostid'])) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _s('Graph "%1$s" cannot inherit. No required items on "%2$s".', $tmpGraph['name'], $chdHost['host']));
			}

			if ($tmpGraph['ymax_itemid'] > 0) {
				$ymaxItemid = get_same_graphitems_for_host(array(array('itemid' => $tmpGraph['ymax_itemid'])), $chdHost['hostid']);
				if (!$ymaxItemid) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _s('Graph "%1$s" cannot inherit. No required items on "%2$s" (Ymax value item).', $tmpGraph['name'], $chdHost['host']));
				}
				$ymaxItemid = reset($ymaxItemid);
				$tmpGraph['ymax_itemid'] = $ymaxItemid['itemid'];
			}
			if ($tmpGraph['ymin_itemid'] > 0) {
				$yminItemid = get_same_graphitems_for_host(array(array('itemid' => $tmpGraph['ymin_itemid'])), $chdHost['hostid']);
				if (!$yminItemid) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _s('Graph "%1$s" cannot inherit. No required items on "%2$s" (Ymin value item).', $tmpGraph['name'], $chdHost['host']));
				}
				$yminItemid = reset($yminItemid);
				$tmpGraph['ymin_itemid'] = $yminItemid['itemid'];
			}

			// check if templated graph exists
			$chdGraphs = $this->get(array(
				'filter' => array('templateid' => $tmpGraph['graphid'], 'flags' => array(ZBX_FLAG_DISCOVERY_CHILD, ZBX_FLAG_DISCOVERY_NORMAL)),
				'output' => API_OUTPUT_EXTEND,
				'selectGraphItems' => API_OUTPUT_EXTEND,
				'preservekeys' => true,
				'hostids' => $chdHost['hostid']
			));

			if ($chdGraph = reset($chdGraphs)) {
				if (zbx_strtolower($tmpGraph['name']) != zbx_strtolower($chdGraph['name'])
						&& $this->exists(array('name' => $tmpGraph['name'], 'hostids' => $chdHost['hostid']))){
					self::exception(ZBX_API_ERROR_PARAMETERS, _s('Graph "%1$s" already exists on "%2$s".', $tmpGraph['name'], $chdHost['host']));
				}
				elseif ($chdGraph['flags'] != $tmpGraph['flags']) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _('Graph with same name but other type exist.'));
				}

				$tmpGraph['graphid'] = $chdGraph['graphid'];
				$this->updateReal($tmpGraph, $chdGraph);
			}
			// check if graph with same name and items exists
			else {
				$chdGraph = $this->get(array(
					'filter' => array('name' => $tmpGraph['name'], 'flags' => null),
					'output' => API_OUTPUT_EXTEND,
					'selectGraphItems' => API_OUTPUT_EXTEND,
					'preservekeys' => true,
					'nopermissions' => true,
					'hostids' => $chdHost['hostid']
				));
				if ($chdGraph = reset($chdGraph)) {
					if ($chdGraph['templateid'] != 0) {
						self::exception(ZBX_API_ERROR_PARAMETERS, _s('Graph "%1$s" already exists on "%2$s" (inherited from another template).', $tmpGraph['name'], $chdHost['host']));
					}
					elseif ($chdGraph['flags'] != $tmpGraph['flags']) {
						self::exception(ZBX_API_ERROR_PARAMETERS, _('Graph with same name but other type exist.'));
					}

					$chdGraphItems = API::GraphItem()->get(array(
						'graphids' => $chdGraph['graphid'],
						'output' => API_OUTPUT_EXTEND,
						'preservekeys' => true,
						'expandData' => true,
						'nopermissions' => true
					));

					if (count($chdGraphItems) == count($tmpGraph['gitems'])) {
						foreach ($tmpGraph['gitems'] as $gitem) {
							foreach ($chdGraphItems as $chdItem) {
								if ($gitem['key_'] == $chdItem['key_'] && bccomp($chdHost['hostid'], $chdItem['hostid']) == 0) {
									continue 2;
								}
							}
							self::exception(ZBX_API_ERROR_PARAMETERS, _s('Graph "%1$s" already exists on "%2$s" (items are not identical).', $tmpGraph['name'], $chdHost['host']));
						}

						$tmpGraph['graphid'] = $chdGraph['graphid'];
						$this->updateReal($tmpGraph, $chdGraph);
					}
					else {
						self::exception(ZBX_API_ERROR_PARAMETERS, _s('Graph "%1$s" already exists on "%2$s" (items are not identical).', $tmpGraph['name'], $chdHost['host']));
					}
				}
				else {
					$graphid = $this->createReal($tmpGraph);
					$tmpGraph['graphid'] = $graphid;
				}
			}
			$this->inherit($tmpGraph);
		}
	}

	/**
	 * Inherit template graphs from template to host.
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public function syncTemplates($data) {
		$data['templateids'] = zbx_toArray($data['templateids']);
		$data['hostids'] = zbx_toArray($data['hostids']);

		$dbLinks = DBSelect(
			'SELECT ht.hostid,ht.templateid'.
			' FROM hosts_templates ht'.
			' WHERE '.dbConditionInt('ht.hostid', $data['hostids']).
				' AND '.dbConditionInt('ht.templateid', $data['templateids'])
		);
		$linkage = array();
		while ($link = DBfetch($dbLinks)) {
			if (!isset($linkage[$link['templateid']])) {
				$linkage[$link['templateid']] = array();
			}
			$linkage[$link['templateid']][$link['hostid']] = 1;
		}

		$graphs = $this->get(array(
			'hostids' => $data['templateids'],
			'preservekeys' => true,
			'output' => API_OUTPUT_EXTEND,
			'selectGraphItems' => API_OUTPUT_EXTEND
		));

		foreach ($graphs as $graph) {
			foreach ($data['hostids'] as $hostid) {
				if (isset($linkage[$graph['hosts'][0]['hostid']][$hostid])) {
					$this->inherit($graph, $hostid);
				}
			}
		}

		return true;
	}

	/**
	 * Delete graphs
	 *
	 * @param array $graphids
	 * @param bool $nopermissions
	 * @return array
	 */
	public function delete($graphids, $nopermissions = false) {
		if (empty($graphids)) {
			self::exception(ZBX_API_ERROR_PARAMETERS, _('Empty input parameter.'));
		}

		$graphids = zbx_toArray($graphids);

		$delGraphs = $this->get(array(
			'graphids' => $graphids,
			'editable' => true,
			'output' => API_OUTPUT_EXTEND,
			'preservekeys' => true,
			'selectHosts' => array('name')
		));

		if (!$nopermissions) {
			foreach ($graphids as $graphid) {
				if (!isset($delGraphs[$graphid])) {
					self::exception(ZBX_API_ERROR_PERMISSIONS, _('You do not have permission to perform this operation.'));
				}

				if (!empty($delGraphs[$graphid]['templateid'])) {
					self::exception(ZBX_API_ERROR_PERMISSIONS, _('Cannot delete templated graphs.'));
				}
			}
		}

		$parentGraphids = $graphids;
		do {
			$dbGraphs = DBselect('SELECT g.graphid FROM graphs g WHERE '.dbConditionInt('g.templateid', $parentGraphids));
			$parentGraphids = array();
			while ($dbGraph = DBfetch($dbGraphs)) {
				$parentGraphids[] = $dbGraph['graphid'];
				$graphids[] = $dbGraph['graphid'];
			}
		} while (!empty($parentGraphids));

		$graphids = array_unique($graphids);

		DB::delete('screens_items', array(
			'resourceid' => $graphids,
			'resourcetype' => SCREEN_RESOURCE_GRAPH
		));

		DB::delete('profiles', array(
			'idx' => 'web.favorite.graphids',
			'source' => 'graphid',
			'value_id' => $graphids
		));

		DB::delete('graphs', array(
			'graphid' => $graphids
		));

		foreach ($delGraphs as $graph) {
			$host = reset($graph['hosts']);
			info(_s('Deleted: Graph "%1$s" on "%2$s".', $graph['name'], $host['name']));
		}

		return array('graphids' => $graphids);
	}

	/**
	 * Check graph data
	 *
	 * @param array $graphs
	 * @param boolean $update
	 *
	 * @return void
	 */
	protected function checkInput($graphs, $update = false) {
		$itemids = array();
		foreach ($graphs as $graph) {
			// no items
			if (!isset($graph['gitems']) || !is_array($graph['gitems']) || empty($graph['gitems'])) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _s('Missing items for graph "%1$s".', $graph['name']));
			}

			$fields = array('itemid' => null);
			foreach ($graph['gitems'] as $gitem) {
				if (!check_db_fields($fields, $gitem)) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _('Missing "itemid" field for item.'));
				}

				// assigning with key preserves unique itemids
				$itemids[$gitem['itemid']] = $gitem['itemid'];
			}

			// add Y axis item IDs for persmission validation
			if (isset($graph['ymin_type']) && $graph['ymin_type'] == GRAPH_YAXIS_TYPE_ITEM_VALUE) {
				$itemids[$graph['ymin_itemid']] = $graph['ymin_itemid'];
			}
			if (isset($graph['ymax_type']) && $graph['ymax_type'] == GRAPH_YAXIS_TYPE_ITEM_VALUE) {
				$itemids[$graph['ymax_itemid']] = $graph['ymax_itemid'];
			}
		}

		$allowedItems = API::Item()->get(array(
			'nodeids' => get_current_nodeid(true),
			'itemids' => $itemids,
			'webitems' => true,
			'editable' => true,
			'output' => API_OUTPUT_EXTEND,
			'preservekeys' => true
		));

		// check permissions only for non super admins
		if (CUser::$userData['type'] != USER_TYPE_SUPER_ADMIN) {
			foreach ($itemids as $itemid) {
				if (!isset($allowedItems[$itemid])) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _('No permissions to referred object or it does not exist!'));
				}
			}
		}

		parent::checkInput($graphs, $update);

		$allowedValueTypes = array(ITEM_VALUE_TYPE_FLOAT, ITEM_VALUE_TYPE_UINT64);

		// get value type and name for these items
		foreach ($allowedItems as $item) {
			if (!in_array($item['value_type'], $allowedValueTypes)) {
				self::exception(
					ZBX_API_ERROR_PARAMETERS,
					_s('Cannot add a non-numeric item "%1$s" to graph "%2$s".', $item['name'], $graph['name'])
				);
			}
		}
	}

	protected function applyQueryNodeOptions($tableName, $tableAlias, array $options, array $sqlParts) {
		// only apply the node option if no specific ids are given
		if ($options['graphids'] === null &&
				$options['templateids'] === null &&
				$options['hostids'] === null &&
				$options['groupids'] === null &&
				$options['itemids'] === null) {

			$sqlParts = parent::applyQueryNodeOptions($tableName, $tableAlias, $options, $sqlParts);
		}

		return $sqlParts;
	}

	protected function addRelatedObjects(array $options, array $result) {
		$result = parent::addRelatedObjects($options, $result);

		$graphids = array_keys($result);

		// adding Items
		if ($options['selectItems'] !== null && $options['selectItems'] !== API_OUTPUT_COUNT) {
			$relationMap = $this->createRelationMap($result, 'graphid', 'itemid', 'graphs_items');
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

		// adding discoveryRule
		if ($options['selectDiscoveryRule'] !== null) {
			$dbRules = DBselect(
				'SELECT id.parent_itemid,gd.graphid'.
					' FROM graph_discovery gd,item_discovery id,graphs_items gi'.
					' WHERE '.dbConditionInt('gd.graphid', $graphids).
					' AND gd.parent_graphid=gi.graphid'.
					' AND gi.itemid=id.itemid'
			);
			$relationMap = new CRelationMap();
			while ($relation = DBfetch($dbRules)) {
				$relationMap->addRelation($relation['graphid'], $relation['parent_itemid']);
			}

			$discoveryRules = API::DiscoveryRule()->get(array(
				'output' => $options['selectDiscoveryRule'],
				'nodeids' => $options['nodeids'],
				'itemids' => $relationMap->getRelatedIds(),
				'nopermissions' => true,
				'preservekeys' => true
			));
			$result = $relationMap->mapOne($result, $discoveryRules, 'discoveryRule');
		}

		return $result;
	}
}
