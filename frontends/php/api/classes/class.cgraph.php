<?php
/**
 * File containing graph class for API.
 * @package API
 */
/**
 * Class containing methods for operations with graphs
 */
class CGraph {

	public static $error;

	/**
	* Get graph data
	*
	* <code>
	* $options = array(
	*	array 'graphids'				=> array(graphid1, graphid2, ...),
	*	array 'itemids'					=> array(itemid1, itemid2, ...),
	*	array 'hostids'					=> array(hostid1, hostid2, ...),
	*	int 'type'					=> 'graph type, chart/pie'
	*	boolean 'templated_graphs'			=> 'only templated graphs',
	*	int 'count'					=> 'count',
	*	string 'pattern'				=> 'search hosts by pattern in graph names',
	*	integer 'limit'					=> 'limit selection',
	*	string 'order'					=> 'depricated parameter (for now)'
	* );
	* </code>
	*
	* @static
	* @param array $options
	* @return array|boolean host data as array or false if error
	*/
	public static function get($options=array()){

		$result = array();

		$sort_columns = array('graphid'); // allowed columns for sorting

		$sql_parts = array(
			'select' => array('g.graphid, g.name'),
			'from' => array('graphs g'),
			'where' => array(),
			'order' => array(),
			'limit' => null,
			);

		$def_options = array(
			'graphids' 			=> array(),
			'itemids' 			=> array(),
			'hostids' 			=> array(),
			'type' 				=> false,
			'templated_graphs'		=> false,
			'count'				=> false,
			'pattern'			=> '',
			'limit'				=> false,
			'order'				=> ''
			);

		$options = array_merge($def_options, $options);

		// restrict not allowed columns for sorting
		$options['order'] = in_array($options['order'], $sort_columns) ? $options['order'] : '';

// count
		if($options['count']){
			$sql_parts['select'] = array('count(g.graphid) as count');
		}
// graphids
		if($options['graphids']){
			$sql_parts['where'][] = DBcondition('g.graphid', $options['graphids']);
		}
// itemids
		if($options['itemids']){
			$sql_parts['from']['gi'] = 'graphs_items gi';
			$sql_parts['where']['gig'] = 'gi.graphid=g.graphid';
			$sql_parts['where'][] = DBcondition('gi.itemid', $options['itemids']);
		}
// hostids
		if($options['hostids']){
			$sql_parts['from']['gi'] = 'graphs_items gi';
			$sql_parts['from'][] = 'hosts h, items i';
			$sql_parts['where'][] = DBcondition('h.hostid', $options['hostids']);
			$sql_parts['where']['gig'] = 'gi.graphid=g.graphid';
			$sql_parts['where'][] = 'i.itemid=gi.itemid';
			$sql_parts['where'][] = 'h.hostid=i.hostid';
		}
// type
		if($options['type'] !== false){
			$sql_parts['where'][] = 'g.type='.$options['type'];
		}
// templated_graphs
		if($options['templated_graphs']){
			$sql_parts['where'][] = 'g.templateid<>0';
		}
// pattern
		if(!zbx_empty($options['pattern'])){
			$sql_parts['where'][] = ' g.name LIKE '.zbx_dbstr('%'.$options['pattern'].'%');
		}
// order
		if(!zbx_empty($options['order'])){
			$sql_parts['order'][] = 'g.'.$options['order'];
		}
// limit
		if(zbx_ctype_digit($options['limit']) && $options['limit']){
			$sql_parts['limit'] = $options['limit'];
		}


		$sql_select = implode(',', $sql_parts['select']);
		$sql_from = implode(',', $sql_parts['from']);
		$sql_where = implode(' AND ', $sql_parts['where']);
		$sql_order = zbx_empty($options['order']) ? '' : ' ORDER BY '.implode(',', $sql_parts['order']);
		$sql_limit = $sql_parts['limit'];


		$sql = 'SELECT DISTINCT '.$sql_select.
			' FROM '.$sql_from.
			($sql_where ? ' WHERE '.$sql_where : '').
			$sql_order;
		$db_res = DBselect($sql, $sql_limit);

		while($graph = DBfetch($db_res)){
			if($options['count'])
				$result = $graph;
			else
				$result[$graph['graphid']] = $graph;
		}

	return $result;
	}

	/**
	 * Gets all graph data from DB by graphid
	 *
	 * <code>
	 * $graph_data = array(
	 * 	*string 'graphid' => 'graphid'
	 * )
	 * </code>
	 *
	 * @static
	 * @param array $graph_data
	 * @return array|boolean host data as array or false if error
	 */
	public static function getById($graph_data){
		$graph = get_graph_by_graphid($graph_data['graphid']);

		$result = $graph ? true : false;
		if($result)
			return $graph;
		else{
			self::$error = array('error' => ZBX_API_ERROR_INTERNAL, 'data' => 'Graph with id: '.$graph_data['graphid'].' doesn\'t exists.');
			return false;
		}
	}

	/**
	 * Get graphid by graph name
	 *
	 * <code>
	 * $graph_data = array(
	 * 	*string 'graph' => 'graph name'
	 * );
	 * </code>
	 *
	 * @static
	 * @param array $graph_data
	 * @return string|boolean graphid
	 */
	public static function getId($graph_data){
		$result = false;

		$sql = 'SELECT g.graphid '.
				' FROM graphs g '.
				' WHERE g.name='.zbx_dbstr($graph_data['name']).
					' AND '.DBin_node('graphid', get_current_nodeid(false));
		$db_res = DBselect($sql);
		if($graph = DBfetch($db_res))
			$result = $graph['graphid'];
		else{
			self::$error = array('error' => ZBX_API_ERROR_INTERNAL, 'data' => 'Host with name: "'.$graph_data['name'].'" doesn\'t exists.');
		}

	return $result;
	}

	/**
	 * Add graph
	 *
	 * <code>
	 * $graphs = array(
	 * 	*string 'name'			=> null,
	 * 	int 'width'			=> 900,
	 * 	int 'height'			=> 200,
	 * 	int 'ymin_type'			=> 0,
	 * 	int 'ymax_type'			=> 0,
	 * 	int 'yaxismin'			=> 0,
	 * 	int 'yaxismax'			=> 100,
	 * 	int 'ymin_itemid'		=> 0,
	 * 	int 'ymax_itemid'		=> 0,
	 * 	int 'show_work_period'		=> 1,
	 * 	int 'show_triggers'		=> 1,
	 * 	int 'graphtype'			=> 0,
	 * 	int 'show_legend'		=> 0,
	 * 	int 'show_3d'			=> 0,
	 * 	int 'percent_left'		=> 0,
	 * 	int 'percent_right'		=> 0
	 * );
	 * </code>
	 *
	 * @static
	 * @param array $graphs multidimensional array with graphs data
	 * @return boolean
	 */
	public static function add($graphs){

		$error = 'Unknown ZABBIX internal error';
		$result_ids = array();
		$result = false;

		DBstart(false);

		foreach($graphs as $graph){

			$graph_db_fields = array(
				'name'			=> null,
				'width'			=> 900,
				'height'		=> 200,
				'ymin_type'		=> 0,
				'ymax_type'		=> 0,
				'yaxismin'		=> 0,
				'yaxismax'		=> 100,
				'ymin_itemid'		=> 0,
				'ymax_itemid'		=> 0,
				'showworkperiod'	=> 1,
				'showtriggers'		=> 1,
				'graphtype'		=> 0,
				'legend'		=> 0,
				'graph3d'		=> 0,
				'percent_left'		=> 0,
				'percent_right'		=> 0,
				'templateid'		=> 0,
			);

			if(!check_db_fields($graph_db_fields, $graph)){
				$result = false;
				$error = 'Wrong fields for graph [ '.$graph['name'].' ]';
				break;
			}

			$result = add_graph($graph['name'],$graph['width'],$graph['height'],$graph['ymin_type'],$graph['ymax_type'],$graph['yaxismin'],
				$graph['yaxismax'],$graph['ymin_itemid'],$graph['ymax_itemid'],$graph['showworkperiod'],$graph['showtriggers'],$graph['graphtype'],
				$graph['legend'],$graph['graph3d'],$graph['percent_left'],$graph['percent_right'],$graph['templateid']);
			if(!$result) break;
			$result_ids[$result] = $result;
		}
		$result = DBend($result);

		if($result){
			return $result_ids;
		}
		else{
			self::$error = array('error' => ZBX_API_ERROR_INTERNAL, 'data' => $error);//'Internal zabbix error');
			return false;
		}
	}

	/**
	 * Update graphs
	 *
	 * @static
	 * @param array $graphs multidimensional array with graphs data
	 * @return boolean
	 */
	public static function update($graphs){

		$result_ids = array();
		$result = false;

		DBstart(false);
		foreach($graphs as $graph){

			$host_db_fields = self::getById(array('graphid' => $graph['graphid']));

			if(!$host_db_fields) {
				$result = false;
				break;
			}

			if(!check_db_fields($host_db_fields, $graph)){
				$result = false;
				break;
			}

			$result = update_graph($graph['graphid'],$graph['name'],$graph['width'],$graph['height'],$graph['ymin_type'],$graph['ymax_type'],$graph['yaxismin'],
				$graph['yaxismax'],$graph['ymin_itemid'],$graph['ymax_itemid'],$graph['show_work_period'],$graph['show_triggers'],$graph['graphtype'],
				$graph['show_legend'],$graph['show_3d'],$graph['percent_left'],$graph['percent_right'],$graph['templateid']);
			if(!$result) break;
			$result_ids[$graph['graphid']] = $result;
		}
		$result = DBend($result);

		if($result){
			return $result_ids;
		}
		else{
			self::$error = array('error' => ZBX_API_ERROR_INTERNAL, 'data' => 'Internal zabbix error');
			return false;
		}
	}

	/**
	 * Add items to graph
	 *
	 * <code>
	 * $items = array(
	 * 	*string 'graphid'		=> null,
	 * 	array 'items' 			=> (
	 *		'item1' => array(
	 * 			*int 'itemid'			=> null,
	 * 			int 'color'			=> '000000',
	 * 			int 'drawtype'			=> 0,
	 * 			int 'sortorder'			=> 0,
	 * 			int 'yaxisside'			=> 1,
	 * 			int 'calc_fnc'			=> 2,
	 * 			int 'type'			=> 0,
	 * 			int 'periods_cnt'		=> 5,
	 *		), ... )
	 * );
	 * </code>
	 *
	 * @static
	 * @param array $items multidimensional array with items data
	 * @return boolean
	 */
	public static function addItems($items){

		$error = 'Unknown ZABBIX internal error';
		$result_ids = array();
		$result = false;
		$tpl_graph = false;

		$graphid = $items['graphid'];
		$items_tmp = $items['items'];
		$items = array();
		$itemids = array();

		foreach($items_tmp as $item){

			$graph_db_fields = array(
				'itemid'	=> null,
				'color'		=> '000000',
				'drawtype'	=> 0,
				'sortorder'	=> 0,
				'yaxisside'	=> 1,
				'calc_fnc'	=> 2,
				'type'		=> 0,
				'periods_cnt'	=> 5
			);

			if(!check_db_fields($graph_db_fields, $item)){
				self::$error = array('error' => ZBX_API_ERROR_INTERNAL, 'data' => 'Wrong fields for item [ '.$item['itemid'].' ]');
				return false;
			}
			$items[$item['itemid']] = $item;
			$itemids[$item['itemid']] = $item['itemid'];
		}

		// check if graph is templated graph, then items cannot be added
		$graph = CGraph::getById(array('graphid' => $graphid));
		if($graph['templateid'] != 0){
			self::$error = array('error' => ZBX_API_ERROR_INTERNAL, 'data' => 'Cannot edit templated graph : '.$graph['name']);
			return false;
		}

		// check if graph belongs to template, if so, only items from same template can be added
		$tmp_hosts = get_hosts_by_graphid($graphid);
		$host = DBfetch($tmp_hosts); // if graph belongs to template, only one host is possible

		if($host["status"] == HOST_STATUS_TEMPLATE ){
			$sql = 'SELECT DISTINCT count(i.hostid) as count
					FROM items i
					WHERE i.hostid<>'.$host['hostid'].
						' AND '.DBcondition('i.itemid', $itemids);

			$host_count = DBfetch(DBselect($sql));
			if ($host_count['count']){
				self::$error = array('error' => ZBX_API_ERROR_INTERNAL, 'data' => 'You must use items only from host : '.$host['host'].' for template graph : '.$graph['name']);
				return false;
			}
			$tpl_graph = true;
		}

		DBstart(false);
		$result = self::addItems_rec($graphid, $items, $tpl_graph);
		$result = DBend($result);

		if($result){
			return $result;
		}
		else{
			self::$error = array('error' => ZBX_API_ERROR_INTERNAL, 'data' => $error);//'Internal zabbix error');
			return false;
		}
	}

	protected static function addItems_rec($graphid, $items, $tpl_graph=false){

		if($tpl_graph){
			$chd_graphs = get_graphs_by_templateid($graphid);
			while($chd_graph = DBfetch($chd_graphs)){
				$result = self::addItems_rec($chd_graph['graphid'], $items, $tpl_graph);
				if(!$result) return false;
			}

			$tmp_hosts = get_hosts_by_graphid($graphid);
			$graph_host = DBfetch($tmp_hosts);
			if(!$items = get_same_graphitems_for_host($items, $graph_host['hostid'])){
				self::$error = array('error' => ZBX_API_ERROR_INTERNAL, 'data' => 'Can not update graph "'.$chd_graph['name'].'" for host "'.$graph_host['host'].'"');
				return false;
			}
		}

		foreach($items as $item){
			$result = add_item_to_graph($graphid,$item['itemid'],$item['color'],$item['drawtype'],$item['sortorder'],$item['yaxisside'],
						$item['calc_fnc'],$item['type'],$item['periods_cnt']);
			if(!$result) return false;
		}

		return true;
	}

	/**
	 * Delete graph items
	 *
	 * @static
	 * @param array $items
	 * @return boolean
	 */
	public static function deleteItems($item_list, $force=false){
		$error = 'Unknown ZABBIX internal error';
		$result = true;

		$graphid = $item_list['graphid'];
		$items = $item_list['items'];

		if(!$force){
			// check if graph is templated graph, then items cannot be added
			$graph = CGraph::getById(array('graphid' => $graphid));
			if($graph['templateid'] != 0){
				self::$error = array('error' => ZBX_API_ERROR_INTERNAL, 'data' => 'Cannot edit templated graph : '.$graph['name']);
				return false;
			}
		}

		$chd_graphs = get_graphs_by_templateid($graphid);
		while($chd_graph = DBfetch($chd_graphs)){
			$item_list['graphid'] = $chd_graph['graphid'];
			$result = self::deleteItems($item_list, true);
			if(!$result) return false;
		}


		$sql = 'SELECT curr.itemid
				FROM graphs_items gi, items curr, items src
				WHERE gi.graphid='.$graphid.
					' AND gi.itemid=curr.itemid
					AND curr.key_=src.key_
					AND '.DBcondition('src.itemid', $items);
		$db_items = DBselect($sql);
		while($curr_item = DBfetch($db_items)){
			$gitems[$curr_item['itemid']] = $curr_item['itemid'];
		}

		$sql = 'DELETE
				FROM graphs_items
				WHERE graphid='.$graphid.
					' AND '.DBcondition('itemid', $gitems);
		$result = DBselect($sql);

		return $result;
	}

	/**
	 * Delete graphs
	 *
	 * @static
	 * @param array $graphids
	 * @return boolean
	 */
	public static function delete($graphids){
		$result = delete_graph($graphids);
		if($result)
			return true;
		else{
			self::$error = array('error' => ZBX_API_ERROR_INTERNAL, 'data' => 'Internal zabbix error');
			return false;
		}
	}

}
?>
