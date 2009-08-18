<?php
/**
 * File containing CScript class for API.
 * @package API
 */
/**
 * Class containing methods for operations with Scripts
 *
 */
class Cscript {

	public static $error;

	/**
	 * Get Scripts data
	 *
	 * {@source}
	 * @access public
	 * @static
	 * @since 1.8
	 * @version 1
	 *
	 * @param array $options
	 * @param array $options['itemids']
	 * @param array $options['hostids']
	 * @param array $options['groupids']
	 * @param array $options['triggerids']
	 * @param array $options['scriptids']
	 * @param boolean $options['status']
	 * @param boolean $options['templated_items']
	 * @param boolean $options['editable']
	 * @param boolean $options['count']
	 * @param string $options['pattern']
	 * @param int $options['limit']
	 * @param string $options['order']
	 * @return array|int item data as array or false if error
	 */
	public static function get($options=array()){
		global $USER_DETAILS;

		$result = array();
		$user_type = $USER_DETAILS['type'];
		$userid = $USER_DETAILS['userid'];

		$sort_columns = array('scriptid', 'name'); // allowed columns for sorting

		$sql_parts = array(
			'select' => array('scripts' => 's.*'),
			'from' => array('scripts s'),
			'where' => array(),
			'order' => array(),
			'limit' => null);

		$def_options = array(
			'nodeids'				=> null,
			'groupids'				=> null,
			'hostids'				=> null,
			'scriptids'				=> null,
			'editable'				=> null,
// OutPut
			'extendoutput'			=> null,
			'select_groups'			=> null,
			'select_hosts'			=> null,
			'count'					=> null,
			'pattern'				=> '',
			'sortfield'				=> '',
			'sortorder'				=> '',
			'limit'					=> null
		);

		$options = array_merge($def_options, $options);

// editable + PERMISSION CHECK
		if((USER_TYPE_SUPER_ADMIN == $user_type) && !is_null($options['editable'])){

		}
		else if(!is_null($options['editable'])){
			return $result();
		}
		else{
// Filtering
			$sql_parts['from']['r'] = 'rights r';
			$sql_parts['from']['ug'] = 'users_groups ug';
			$sql_parts['from']['hg'] = 'hosts_groups hg';


			$sql_parts['where'][] = 'hg.groupid=r.id';
			$sql_parts['where'][] = 'r.groupid=ug.usrgrpid';
			$sql_parts['where'][] = 'ug.userid='.$userid;
			$sql_parts['where'][] = '(hg.groupid=s.groupid OR s.groupid=0)';
			$sql_parts['where'][] = '(ug.usrgrpid=s.usrgrpid OR s.usrgrpid=0)';
		}

// nodeids
		$nodeids = $options['nodeids'] ? $options['nodeids'] : get_current_nodeid(false);

// groupids
		if(!is_null($options['groupids'])){
			zbx_value2array($options['groupids']);

			$options['groupids'][0] = 0;		// include ALL groups scripts

			if(!is_null($options['extendoutput'])){
				$sql_parts['select']['groupid'] = 's.groupid';
			}

			$sql_parts['where'][] = DBcondition('s.groupid', $options['groupids']);
		}

// hostids
		if(!is_null($options['hostids'])){
			zbx_value2array($options['hostids']);

			if(!is_null($options['extendoutput'])){
				$sql_parts['select']['hostid'] = 'hg.hostid';
			}

			$sql_parts['from']['hg'] = 'hosts_groups hg';
			$sql_parts['where'][] = '(('.DBcondition('hg.hostid', $options['hostids']).' AND hg.groupid=s.groupid)'.
									' OR '.
									'(s.groupid=0))';
		}

// scriptids
		if(!is_null($options['scriptids'])){
			zbx_value2array($options['scriptids']);

			$sql_parts['where'][] = DBcondition('s.scriptid', $options['scriptids']);
		}

// extendoutput
		if(!is_null($options['extendoutput'])){
			$sql_parts['select']['scripts'] = 's.*';
		}

// count
		if(!is_null($options['count'])){
			$options['sortfield'] = '';

			$sql_parts['select'] = array('count(s.scriptid) as rowscount');
		}

// pattern
		if(!zbx_empty($options['pattern'])){
			$sql_parts['where'][] = ' UPPER(s.name) LIKE '.zbx_dbstr('%'.strtoupper($options['pattern']).'%');
		}

// order
// restrict not allowed columns for sorting
		$options['sortfield'] = str_in_array($options['sortfield'], $sort_columns) ? $options['sortfield'] : '';
		if(!zbx_empty($options['sortfield'])){
			$sortorder = ($options['sortorder'] == ZBX_SORT_DOWN)?ZBX_SORT_DOWN:ZBX_SORT_UP;

			$sql_parts['order'][] = 's.'.$options['sortfield'].' '.$sortorder;

			if(!str_in_array('s.'.$options['sortfield'], $sql_parts['select']) && !str_in_array('s.*', $sql_parts['select'])){
				$sql_parts['select'][] = 's.'.$options['sortfield'];
			}
		}

// limit
		if(zbx_ctype_digit($options['limit']) && $options['limit']){
			$sql_parts['limit'] = $options['limit'];
		}
//----------

		$scriptids = array();

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
				' WHERE '.DBin_node('s.scriptid', $nodeids).
					$sql_where.
				$sql_order;
		$res = DBselect($sql, $sql_limit);
		while($script = DBfetch($res)){
			if($options['count'])
				$result = $script;
			else{
				$scriptids[$script['scriptid']] = $script['scriptid'];

				if(is_null($options['extendoutput'])){
					$result[$script['scriptid']] = $script['scriptid'];
				}
				else{
					if(!isset($result[$script['scriptid']]))
						$result[$script['scriptid']] = array();

					if($options['select_groups'] && !isset($result[$script['scriptid']]['groupids'])){
						$result[$script['scriptid']]['groupids'] = array();
						$result[$script['scriptid']]['groups'] = array();
					}

					if($options['select_hosts'] && !isset($result[$script['scriptid']]['hostids'])){
						$result[$script['scriptid']]['hostids'] = array();
						$result[$script['scriptid']]['hosts'] = array();
					}

// groupids
					if(isset($script['groupid'])){
						if(!isset($result[$script['scriptid']]['groupids']))
							$result[$script['scriptid']]['groupids'] = array();

						$result[$script['scriptid']]['groupids'][$script['groupid']] = $script['groupid'];
					}

// hostids
					if(isset($script['hostid'])){
						if(!isset($result[$script['scriptid']]['hostids']))
							$result[$script['scriptid']]['hostids'] = array();

						$result[$script['scriptid']]['hostids'][$script['hostid']] = $script['hostid'];
						unset($script['hostid']);
					}

					$result[$script['scriptid']] += $script;
				}
			}
		}

		if(is_null($options['extendoutput']) || !is_null($options['count'])) return $result;

// Adding Objects
// Adding groups
		if($options['select_groups']){
			foreach($result as $scriptid => $script){
				$obj_params = array('extendoutput' => 1);

				if($script['host_access'] == PERM_READ_WRITE){
					$obj_params['editable'] = 1;
				}

				if($script['groupid'] > 0){
					$obj_params['groupids'] = $script['groupid'];
				}

				$groups = CHostGroup::get($obj_params);

				$result[$scriptid]['groups'] = $groups;
				$result[$scriptid]['groupids'] = array_keys($groups);
			}
		}

// Adding hosts
		if($options['select_hosts']){
			foreach($result as $scriptid => $script){
				$obj_params = array('extendoutput' => 1);

				if($script['host_access'] == PERM_READ_WRITE){
					$obj_params['editable'] = 1;
				}

				if($script['groupid'] > 0){
					$obj_params['groupids'] = $script['groupid'];
				}

				$hosts = CHost::get($obj_params);

				$result[$scriptid]['hosts'] = $hosts;
				$result[$scriptid]['hostids'] = array_keys($hosts);
			}
		}

	return $result;
	}

	/**
	 * Gets all Script data from DB by Script ID
	 *
	 * {@source}
	 * @access public
	 * @static
	 * @since 1.8
	 * @version 1
	 *
	 * @param int $script
	 * @param int $script['scriptid']
	 * @return array|boolean script data || false if error
	 */
	public static function getById($script){
		$item = get_script_by_scriptid($script['scriptid']);
		$result = $item ? true : false;
		if($result)
			return $item;
		else{
			self::$error = array('error' => ZBX_API_ERROR_NO_HOST, 'data' => 'Script with id: '.$script['scriptid'].' doesn\'t exists.');
			return false;
		}
	}

	/**
	 * Get Script ID by host.name and item.key
	 *
	 * {@source}
	 * @access public
	 * @static
	 * @since 1.8
	 * @version 1
	 *
	 * @param array $script
	 * @param array $script['name']
	 * @param array $script['hostid']
	 * @return int|boolean
	 */
	public static function getId($script){

		$sql = 'SELECT scriptid '.
				' FROM scripts '.
				' WHERE '.DBin_node('scriptid').
					' AND name='.$script['name'];
		$script = DBfetch(DBselect($sql));
		$result = $script ? true : false;
		if($result)
			return $script['scriptid'];
		else{
			self::$error = array('error' => ZBX_API_ERROR_NO_HOST, 'data' => 'Script doesn\'t exists.');
			return false;
		}
	}

/**
 * Add Scripts
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param _array $scripts
 * @param array $script['name']
 * @param array $script['hostid']
 * @return boolean
 */
	public static function add($scripts){

		$result = false;

		DBstart(false);
		foreach($scripts as $num => $script){
			$script_db_fields = array(
				'name' => null,
				'command' => null,
				'usrgrpid' => 0,
				'groupid' => 0,
				'host_access' => 2,
			);

			if(!check_db_fields($script_db_fields, $script)){
				$result = false;
				$error = 'Wrong fields for host [ '.$host['host'].' ]';
				break;
			}

			$result = add_script($script['name'],$script['command'],$script['usrgrpid'],$script['groupid'],$script['host_access']);
			if(!$result) break;
		}

		$result = DBend($result);

		if($result)
			return true;
		else{
			self::$error = array('error' => ZBX_API_ERROR_INTERNAL, 'data' => 'Internal zabbix error');
			return false;
		}
	}

/**
 * Update Scripts
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param _array $scripts
 * @param array $script['name']
 * @param array $script['hostid']
 * @return boolean
 */
	public static function update($scripts){

		$result = false;

		DBstart(false);
		foreach($scripts as $num => $script){

			$script_db_fields = CHost::getById($script);

			if(!$script_db_fields){
				$result = false;
				break;
			}

			if(!check_db_fields($script_db_fields, $script)){
				$result = false;
				$error = 'Wrong fields for host [ '.$host['host'].' ]';
				break;
			}

			$result = update_script($script['scriptid'], $script['name'],$script['command'],$script['usrgrpid'],$script['groupid'],$script['host_access']);
			if(!$result) break;
		}
		$result = DBend($result);

		if($result)
			return true;
		else{
			self::$error = array('error' => ZBX_API_ERROR_INTERNAL, 'data' => 'Internal zabbix error');
			return false;
		}
	}

/**
 * Delete Scripts
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param _array $scriptids
 * @return boolean
 */
	public static function delete($scriptids){
		$result = delete_script($scriptids);
		if($result)
			return true;
		else{
			self::$error = array('error' => ZBX_API_ERROR_INTERNAL, 'data' => 'Internal zabbix error');
			return false;
		}
	}

	public static function execute($scriptid,$hostid){
		return execute_script($scriptid,$hostid);
	}

	public static function getCommand($scriptid,$hostid){
		return script_make_command($scriptid,$hostid);
	}


	public static function getScriptsByHosts($hostids){
		global $USER_DETAILS;

		zbx_value2array($hostids);

		$obj_params = array('hostids' => $hostids);
		$hosts_read_only  = CHost::get($obj_params);

		$obj_params = array('editable' => 1, 'hostids' => $hostids);
		$hosts_read_write = CHost::get($obj_params);

		$scripts_by_host = array();

// initialize array
		foreach($hostids as $id => $hostid){
			$scripts_by_host[$hostid] = array();
		}
//-----

		$obj_params = array('extendoutput' =>1, 'hostids' => $hostids);
		$scripts  = CScript::get($obj_params);

		foreach($scripts as $scriptid => $script){
			$add_to_hosts = array();
			if(PERM_READ_WRITE == $script['host_access']){
				if($script['groupid'] > 0)
					$add_to_hosts = zbx_uint_array_intersect($hosts_read_write, $groups[$script['groupid']]['hostids']);
				else
					$add_to_hosts = $hosts_read_write;
			}
			else if(PERM_READ_ONLY == $script['host_access']){
				if($script['groupid'] > 0)
					$add_to_hosts = zbx_uint_array_intersect($hosts_read_only, $groups[$script['groupid']]['hostids']);
				else
					$add_to_hosts = $hosts_read_only;
			}

			foreach($add_to_hosts as $id => $hostid){
				$scripts_by_host[$hostid][] = $script;
			}
		}

	//SDI($scripts_by_host);
	return $scripts_by_host;
	}
}
?>
