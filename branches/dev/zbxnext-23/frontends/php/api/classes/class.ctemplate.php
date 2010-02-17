<?php
/*
** ZABBIX
** Copyright (C) 2000-2010 SIA Zabbix
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
 * File containing CTemplate class for API.
 * @package API
 */
/**
 * Class containing methods for operations with Templates
 *
 */
class CTemplate extends CZBXAPI{
/**
 * Get Template data
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @static
 * @param array $options
 * @return array|boolean Template data as array or false if error
 */
	public static function get($options = array()) {
		global $USER_DETAILS;

		$result = array();
		$user_type = $USER_DETAILS['type'];
		$userid = $USER_DETAILS['userid'];

		$sort_columns = array('hostid', 'host'); // allowed columns for sorting
		$subselects_allowed_outputs = array(API_OUTPUT_REFER, API_OUTPUT_EXTEND); // allowed output options for [ select_* ] params

		$sql_parts = array(
			'select' => array('templates' => 'h.hostid'),
			'from' => array('hosts h'),
			'where' => array('h.status='.HOST_STATUS_TEMPLATE),
			'order' => array(),
			'limit' => null);

		$def_options = array(
			'nodeids'					=> null,
			'groupids'					=> null,
			'templateids'				=> null,
			'hostids'					=> null,
			'graphids'					=> null,
			'itemids'					=> null,
			'with_items'				=> null,
			'with_triggers'				=> null,
			'with_graphs'				=> null,
			'editable' 					=> null,
			'nopermissions'				=> null,
// filter
			'filter'					=> null,
			'pattern'					=> '',
// OutPut
			'output'					=> API_OUTPUT_REFER,
			'extendoutput'				=> null,
			'select_groups'				=> null,
			'select_hosts'				=> null,
			'select_templates'			=> null,
			'select_items'				=> null,
			'select_triggers'			=> null,
			'select_graphs'				=> null,
			'select_applications'		=> null,
			'select_macros'				=> null,
			'count'						=> null,
			'preservekeys'				=> null,

			'sortfield'					=> '',
			'sortorder'					=> '',
			'limit'						=> null
		);

		$options = zbx_array_merge($def_options, $options);


		if(!is_null($options['extendoutput'])){
			$options['output'] = API_OUTPUT_EXTEND;

			if(!is_null($options['select_groups'])){
				$options['select_groups'] = API_OUTPUT_EXTEND;
			}
			if(!is_null($options['select_templates'])){
				$options['select_templates'] = API_OUTPUT_EXTEND;
			}
			if(!is_null($options['select_hosts'])){
				$options['select_hosts'] = API_OUTPUT_EXTEND;
			}
			if(!is_null($options['select_items'])){
				$options['select_items'] = API_OUTPUT_EXTEND;
			}
			if(!is_null($options['select_triggers'])){
				$options['select_triggers'] = API_OUTPUT_EXTEND;
			}
			if(!is_null($options['select_graphs'])){
				$options['select_graphs'] = API_OUTPUT_EXTEND;
			}
			if(!is_null($options['select_applications'])){
				$options['select_applications'] = API_OUTPUT_EXTEND;
			}
			if(!is_null($options['select_macros'])){
				$options['select_macros'] = API_OUTPUT_EXTEND;
			}
		}


// editable + PERMISSION CHECK

		if((USER_TYPE_SUPER_ADMIN == $user_type) || $options['nopermissions']){
		}
		else{
			$permission = $options['editable']?PERM_READ_WRITE:PERM_READ_ONLY;

			$sql_parts['from']['hg'] = 'hosts_groups hg';
			$sql_parts['from']['r'] = 'rights r';
			$sql_parts['from']['ug'] = 'users_groups ug';
			$sql_parts['where'][] = 'hg.hostid=h.hostid';
			$sql_parts['where'][] = 'r.id=hg.groupid ';
			$sql_parts['where'][] = 'r.groupid=ug.usrgrpid';
			$sql_parts['where'][] = 'ug.userid='.$userid;
			$sql_parts['where'][] = 'r.permission>='.$permission;
			$sql_parts['where'][] = 'NOT EXISTS( '.
								' SELECT hgg.groupid '.
								' FROM hosts_groups hgg, rights rr, users_groups gg '.
								' WHERE hgg.hostid=hg.hostid '.
									' AND rr.id=hgg.groupid '.
									' AND rr.groupid=gg.usrgrpid '.
									' AND gg.userid='.$userid.
									' AND rr.permission<'.$permission.')';
		}

// nodeids
		$nodeids = !is_null($options['nodeids']) ? $options['nodeids'] : get_current_nodeid(false);

// groupids
		if(!is_null($options['groupids'])){
			zbx_value2array($options['groupids']);

			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['groupid'] = 'hg.groupid';
			}

			$sql_parts['from']['hg'] = 'hosts_groups hg';
			$sql_parts['where'][] = DBcondition('hg.groupid', $options['groupids']);
			$sql_parts['where']['hgh'] = 'hg.hostid=h.hostid';
		}

// templateids
		if(!is_null($options['templateids'])){
			zbx_value2array($options['templateids']);

			$sql_parts['where']['templateid'] = DBcondition('h.hostid', $options['templateids']);
		}

// hostids
		if(!is_null($options['hostids'])){
			zbx_value2array($options['hostids']);

			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['linked_hostid'] = 'ht.hostid as linked_hostid';
			}

			$sql_parts['from']['ht'] = 'hosts_templates ht';
			$sql_parts['where'][] = DBcondition('ht.hostid', $options['hostids']);
			$sql_parts['where']['hht'] = 'h.hostid=ht.templateid';
		}

// itemids
		if(!is_null($options['itemids'])){
			zbx_value2array($options['itemids']);

			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['itemid'] = 'i.itemid';
			}

			$sql_parts['from']['i'] = 'items i';
			$sql_parts['where'][] = DBcondition('i.itemid', $options['itemids']);
			$sql_parts['where']['hi'] = 'h.hostid=i.hostid';
		}

// graphids
		if(!is_null($options['graphids'])){
			zbx_value2array($options['graphids']);

			if($options['output'] != API_OUTPUT_SHORTEN){
				$sql_parts['select']['graphid'] = 'gi.graphid';
			}

			$sql_parts['from']['gi'] = 'graphs_items gi';
			$sql_parts['from']['i'] = 'items i';
			$sql_parts['where'][] = DBcondition('gi.graphid', $options['graphids']);
			$sql_parts['where']['igi'] = 'i.itemid=gi.itemid';
			$sql_parts['where']['hi'] = 'h.hostid=i.hostid';
		}

// with_items
		if(!is_null($options['with_items'])){
			$sql_parts['where'][] = 'EXISTS (SELECT i.hostid FROM items i WHERE h.hostid=i.hostid )';
		}

// with_triggers
		if(!is_null($options['with_triggers'])){
			$sql_parts['where'][] = 'EXISTS(
					SELECT i.itemid
					FROM items i, functions f, triggers t
					WHERE i.hostid=h.hostid
						AND i.itemid=f.itemid
						AND f.triggerid=t.triggerid)';
		}

// with_graphs
		if(!is_null($options['with_graphs'])){
			$sql_parts['where'][] = 'EXISTS(
					SELECT DISTINCT i.itemid
					FROM items i, graphs_items gi
					WHERE i.hostid=h.hostid
						AND i.itemid=gi.itemid)';
		}

// extendoutput
		if($options['output'] == API_OUTPUT_EXTEND){
			$sql_parts['select']['templates'] = 'h.*';
		}

// count
		if(!is_null($options['count'])){
			$options['sortfield'] = '';

			$sql_parts['select'] = array('count(h.hostid) as rowscount');
		}

// pattern
		if(!zbx_empty($options['pattern'])){
			$sql_parts['where']['host'] = ' UPPER(h.host) LIKE '.zbx_dbstr('%'.zbx_strtoupper($options['pattern']).'%');
		}

// filter
		if(!is_null($options['filter'])){
			zbx_value2array($options['filter']);

			if(isset($options['filter']['templateid'])){
				$sql_parts['where']['templateid'] = 'h.hostid='.$options['filter']['templateid'];
			}
			if(isset($options['filter']['host'])){
				$sql_parts['where']['host'] = 'h.host='.zbx_dbstr($options['filter']['host']);
			}
		}

// order
// restrict not allowed columns for sorting
		$options['sortfield'] = str_in_array($options['sortfield'], $sort_columns) ? $options['sortfield'] : '';
		if(!zbx_empty($options['sortfield'])){
			$sortorder = ($options['sortorder'] == ZBX_SORT_DOWN)?ZBX_SORT_DOWN:ZBX_SORT_UP;

			$sql_parts['order'][] = 'h.'.$options['sortfield'].' '.$sortorder;

			if(!str_in_array('h.'.$options['sortfield'], $sql_parts['select']) && !str_in_array('h.*', $sql_parts['select'])){
				$sql_parts['select'][] = 'h.'.$options['sortfield'];
			}
		}

// limit
		if(zbx_ctype_digit($options['limit']) && $options['limit']){
			$sql_parts['limit'] = $options['limit'];
		}
//-------------

		$templateids = array();

		$sql_parts['select'] = array_unique($sql_parts['select']);
		$sql_parts['from'] = array_unique($sql_parts['from']);
		$sql_parts['where'] = array_unique($sql_parts['where']);
		$sql_parts['order'] = array_unique($sql_parts['order']);

		$sql_select = '';
		$sql_from = '';
		$sql_where = '';
		$sql_order = '';
		if(!empty($sql_parts['select']))	$sql_select.= implode(',', $sql_parts['select']);
		if(!empty($sql_parts['from']))		$sql_from.= implode(',', $sql_parts['from']);
		if(!empty($sql_parts['where']))		$sql_where.= ' AND '.implode(' AND ', $sql_parts['where']);
		if(!empty($sql_parts['order']))		$sql_order.= ' ORDER BY '.implode(',', $sql_parts['order']);
		$sql_limit = $sql_parts['limit'];

		$sql = 'SELECT '.$sql_select.'
				FROM '.$sql_from.'
				WHERE '.DBin_node('h.hostid', $nodeids).
					$sql_where.
				$sql_order;
		$res = DBselect($sql, $sql_limit);
		while($template = DBfetch($res)){
			if($options['count'])
				$result = $template;
			else{
				$template['templateid'] = $template['hostid'];
				$templateids[$template['templateid']] = $template['templateid'];

				if($options['output'] == API_OUTPUT_SHORTEN){
					$result[$template['templateid']] = array('templateid' => $template['templateid']);
				}
				else{
					if(!isset($result[$template['templateid']])) $result[$template['templateid']]= array();

					if(!is_null($options['select_groups']) && !isset($result[$template['templateid']]['groups'])){
						$template['groups'] = array();
					}

					if(!is_null($options['select_templates']) && !isset($result[$template['templateid']]['templates'])){
						$template['templates'] = array();
					}

					if(!is_null($options['select_hosts']) && !isset($result[$template['templateid']]['hosts'])){
						$template['hosts'] = array();
					}

					if(!is_null($options['select_items']) && !isset($result[$template['templateid']]['items'])){
						$template['items'] = array();
					}

					if(!is_null($options['select_triggers']) && !isset($result[$template['templateid']]['triggers'])){
						$template['triggers'] = array();
					}

					if(!is_null($options['select_graphs']) && !isset($result[$template['templateid']]['graphs'])){
						$template['graphs'] = array();
					}
					if(!is_null($options['select_applications']) && !isset($result[$template['templateid']]['applications'])){
						$template['applications'] = array();
					}
					if(!is_null($options['select_macros']) && !isset($result[$host['hostid']]['macros'])){
						$template['macros'] = array();
					}

// groupids
					if(isset($template['groupid']) && is_null($options['select_groups'])){
						if(!isset($result[$template['templateid']]['groups']))
							$result[$template['templateid']]['groups'] = array();

						$result[$template['templateid']]['groups'][] = array('groupid' => $template['groupid']);
						unset($template['groupid']);
					}

// hostids
					if(isset($template['linked_hostid']) && is_null($options['select_hosts'])){
						if(!isset($result[$template['templateid']]['hosts']))
							$result[$template['templateid']]['hosts'] = array();

						$result[$template['templateid']]['hosts'][] = array('hostid' => $template['linked_hostid']);
						unset($template['linked_hostid']);
					}

// itemids
					if(isset($template['itemid']) && is_null($options['select_items'])){
						if(!isset($result[$template['templateid']]['items']))
							$result[$template['templateid']]['items'] = array();

						$result[$template['templateid']]['items'][] = array('itemid' => $template['itemid']);
						unset($template['itemid']);
					}

// graphids
					if(isset($template['graphid']) && is_null($options['select_graphs'])){
						if(!isset($result[$template['templateid']]['graphs'])) $result[$template['templateid']]['graphs'] = array();

						$result[$template['templateid']]['graphs'][] = array('graphid' => $template['graphid']);
						unset($template['graphid']);
					}

					$result[$template['templateid']] += $template;
				}
			}

		}

		if(($options['output'] != API_OUTPUT_EXTEND) || !is_null($options['count'])){
			if(is_null($options['preservekeys'])) $result = zbx_cleanHashes($result);
			return $result;
		}

// Adding Objects
// Adding Groups
		if(!is_null($options['select_groups']) && str_in_array($options['select_groups'], $subselects_allowed_outputs)){
			$obj_params = array(
				'nodeids' => $nodeids,
				'output' => $options['select_groups'],
				'hostids' => $templateids,
				'preservekeys' => 1
			);
			$groups = CHostgroup::get($obj_params);
			foreach($groups as $groupid => $group){
				$ghosts = $group['hosts'];
				unset($group['hosts']);
				foreach($ghosts as $hnum => $template){
					$result[$template['hostid']]['groups'][] = $group;
				}
			}
		}

// Adding Templates
		if(!is_null($options['select_templates']) && str_in_array($options['select_templates'], $subselects_allowed_outputs)){
			$obj_params = array(
				'nodeids' => $nodeids,
				'output' => $options['select_templates'],
				'hostids' => $templateids,
				'preservekeys' => 1
			);
			$templates = self::get($obj_params);
			foreach($templates as $templateid => $template){
				$thosts = $template['hosts'];
				unset($template['hosts']);
				foreach($thosts as $hnum => $host){
					$result[$host['hostid']]['templates'][] = $template;
				}
			}
		}

// Adding Hosts
		if(!is_null($options['select_hosts']) && str_in_array($options['select_hosts'], $subselects_allowed_outputs)){
			$obj_params = array(
				'nodeids' => $nodeids,
				'output' => $options['select_hosts'],
				'templateids' => $templateids,
				'templated_hosts' => 1,
				'preservekeys' => 1
			);
			$hosts = CHost::get($obj_params);
			foreach($hosts as $hostid => $host){
				$htemplates = $host['templates'];
				unset($host['templates']);
				foreach($htemplates as $tnum => $template){
					$result[$template['templateid']]['hosts'][] = $host;
				}
			}
		}

// Adding Items
		if(!is_null($options['select_items']) && str_in_array($options['select_items'], $subselects_allowed_outputs)){
			$obj_params = array(
				'nodeids' => $nodeids,
				'output' => $options['select_items'],
				'hostids' => $templateids,
				'nopermissions' => 1,
				'preservekeys' => 1
			);
			$items = CItem::get($obj_params);

			foreach($items as $itemid => $item){
				$ihosts = $item['hosts'];
				unset($item['hosts']);
				foreach($ihosts as $hnum => $host){
					$result[$host['hostid']]['items'][] = $item;
				}
			}
		}

// Adding triggers
		if(!is_null($options['select_triggers']) && str_in_array($options['select_triggers'], $subselects_allowed_outputs)){
			$obj_params = array(
				'nodeids' => $nodeids,
				'output' => $options['select_triggers'],
				'hostids' => $templateids,
				'preservekeys' => 1
			);
			$triggers = CTrigger::get($obj_params);
			foreach($triggers as $triggerid => $trigger){
				$thosts = $trigger['hosts'];
				unset($trigger['hosts']);
				foreach($thosts as $hnum => $host){
					$result[$host['hostid']]['triggers'][] = $trigger;
				}
			}
		}

// Adding graphs
		if(!is_null($options['select_graphs']) && str_in_array($options['select_graphs'], $subselects_allowed_outputs)){
			$obj_params = array(
				'nodeids' => $nodeids,
				'output' => $options['select_graphs'],
				'hostids' => $templateids,
				'preservekeys' => 1
			);
			$graphs = CGraph::get($obj_params);
			foreach($graphs as $graphid => $graph){
				$ghosts = $graph['hosts'];
				unset($graph['hosts']);
				foreach($ghosts as $hnum => $host){
					$result[$host['hostid']]['graphs'][] = $graph;
				}
			}
		}

// Adding applications
		if(!is_null($options['select_applications']) && str_in_array($options['select_applications'], $subselects_allowed_outputs)){
			$obj_params = array(
				'nodeids' => $nodeids,
				'output' => $options['select_applications'],
				'hostids' => $templateids,
				'preservekeys' => 1
			);
			$applications = Capplication::get($obj_params);
			foreach($applications as $applicationid => $application){
				$ahosts = $application['hosts'];
				unset($application['hosts']);
				foreach($ahosts as $hnum => $host){
					$result[$host['hostid']]['applications'][] = $application;
				}
			}
		}

// Adding macros
		if(!is_null($options['select_macros']) && str_in_array($options['select_macros'], $subselects_allowed_outputs)){
			$obj_params = array(
				'nodeids' => $nodeids,
				'output' => $options['select_macros'],
				'hostids' => $hostids,
				'preservekeys' => 1
			);
			$macros = CUserMacro::get($obj_params);
			foreach($macros as $macroid => $macro){
				$mhosts = $macro['hosts'];
				unset($macro['hosts']);
				foreach($mhosts as $hnum => $host){
					$result[$host['hostid']]['macros'][] = $macro;
				}
			}
		}

// removing keys (hash -> array)
		if(is_null($options['preservekeys'])){
//			$result = zbx_cleanHashes($result);
		}

	return $result;
	}

/**
 * Get Template ID by Template name
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param array $template_data
 * @param array $template_data['host']
 * @param array $template_data['templateid']
 * @return string templateid
 */

	public static function getObjects($templateData){
		$options = array(
			'filter' => $templateData,
			'output'=>API_OUTPUT_EXTEND
		);

		if(isset($templateData['node']))
			$options['nodeids'] = getNodeIdByNodeName($templateData['node']);
		else if(isset($templateData['nodeids']))
			$options['nodeids'] = $templateData['nodeids'];

		$result = self::get($options);

	return $result;
	}

	public static function checkObjects($templatesData){

		$result = array();
		foreach($templatesData as $tnum => $templateData){
			$options = array(
				'filter' => $templateData,
				'output' => API_OUTPUT_SHORTEN,
				'nopermissions' => 1
			);

			if(isset($templateData['node']))
				$options['nodeids'] = getNodeIdByNodeName($templateData['node']);
			else if(isset($templateData['nodeids']))
				$options['nodeids'] = $templateData['nodeids'];

			$templates = self::get($options);

			$result+= $templates;
		}

	return $result;
	}

/**
 * Add Template
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param _array $templates multidimensional array with templates data
 * @param string $templates['host']
 * @param string $templates['port']
 * @param string $templates['status']
 * @param string $templates['useip']
 * @param string $templates['dns']
 * @param string $templates['ip']
 * @param string $templates['proxy_hostid']
 * @param string $templates['useipmi']
 * @param string $templates['ipmi_ip']
 * @param string $templates['ipmi_port']
 * @param string $templates['ipmi_authtype']
 * @param string $templates['ipmi_privilege']
 * @param string $templates['ipmi_username']
 * @param string $templates['ipmi_password']
 * @return boolean
 */
	public static function create($templates){
		$transaction = false;

		$templates = zbx_toArray($templates);
		$templateids = array();

		$result = false;

		try{
// CHECK IF HOSTS HAVE AT LEAST 1 GROUP {{{
			foreach($templates as $tnum => $template){
				if(empty($template['groups'])){
					throw new APIException(ZBX_API_ERROR_PARAMETERS, 'No groups for template [ '.$template['host'].' ]');
				}
				$templates[$tnum]['groups'] = zbx_toArray($templates[$tnum]['groups']);

				foreach($templates[$tnum]['groups'] as $gnum => $group){
					$groupids[$group['groupid']] = $group['groupid'];
				}
			}
// }}} CHECK IF HOSTS HAVE AT LEAST 1 GROUP


// PERMISSIONS {{{
			$upd_groups = CHostGroup::get(array(
				'groupids' => $groupids,
				'editable' => 1,
				'preservekeys' => 1));
			foreach($groupids as $gnum => $groupid){
				if(!isset($upd_groups[$groupid])){
					throw new APIException(ZBX_API_ERROR_PERMISSIONS, S_NO_PERMISSION);
				}
			}
// }}} PERMISSIONS

			$transaction = self::BeginTransaction(__METHOD__);

			foreach($templates as $tnum => $template){

	 			$template_db_fields = array(
					'host' => null
				);

				if(!check_db_fields($template_db_fields, $template)){
					throw new APIException(ZBX_API_ERROR_PARAMETERS, 'Field "host" is mandatory');
				}

				if(!preg_match('/^'.ZBX_PREG_HOST_FORMAT.'$/i', $template['host'])){
					throw new APIException(ZBX_API_ERROR_PARAMETERS, 'Incorrect characters used for Template name [ '.$template['host'].' ]');
				}

				$template_exists = self::checkObjects(array('host' => $template['host']));
				if(!empty($template_exists)){
					$result = false;
					throw new APIException(ZBX_API_ERROR_PARAMETERS, S_TEMPLATE.' [ '.$template['host'].' ] '.S_ALREADY_EXISTS_SMALL);
				}

				$host_exists = CHost::checkObjects(array('host' => $template['host']));
				if(!empty($host_exists)){
					$result = false;
					$errors[] = array('errno' => ZBX_API_ERROR_PARAMETERS, 'error' => S_HOST.' [ '.$template['host'].' ] '.S_ALREADY_EXISTS_SMALL);
					break;
				}

				$templateid = get_dbid('hosts', 'hostid');
				$templateids[] = $templateid;

				$sql = 'INSERT INTO hosts (hostid, host, status) VALUES ('.$templateid.','.zbx_dbstr($template['host']).','.HOST_STATUS_TEMPLATE.')';
				$result = DBexecute($sql);

				if(!$result) throw new APIException(ZBX_API_ERROR_PARAMETERS, 'DBError');

				$template['templateid'] = $templateid;
				$options = array();
				$options['templates'] = $template;
				$options['groups'] = $template['groups'];
				if(isset($template['templates']) && !is_null($template['templates']))
					$options['templates_link'] = $template['templates'];
				if(isset($template['macros']) && !is_null($template['macros']))
					$options['macros'] = $template['macros'];
				if(isset($template['hosts']) && !is_null($template['hosts']))
					$options['hosts'] = $template['hosts'];

				$result = self::massAdd($options);
				if(!$result) throw new APIException(ZBX_API_ERROR_PARAMETERS);
			}

			self::EndTransaction(true, __METHOD__);

			$new_templates = self::get(array('templateids' => $templateids, 'extendoutput' => 1, 'nopermissions' => 1));
			return $new_templates;

		}
		catch(APIException $e){
			if($transaction) self::EndTransaction(false, __METHOD__);

			$error = $e->getErrors();
			$error = reset($error);
			self::setError(__METHOD__, $e->getCode(), $error);
			return false;
		}
	}

/**
 * Update Template
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param array $templates multidimensional array with templates data
 * @return boolean
 */
	public static function update($templates){

		$templates = zbx_toArray($templates);
		$templateids = zbx_objectValues($templates, 'templateid');

		try{
			$upd_templates = self::get(array(
				'templateids' => $templateids,
				'editable' => 1,
				'extendoutput' => 1,
				'preservekeys' => 1
			));
			foreach($templates as $tnum => $template){
	// PERMISSIONS {{{
				if(!isset($upd_templates[$template['templateid']])){
					throw new APIException(ZBX_API_ERROR_PERMISSIONS, S_NO_PERMISSION);
				}
	// }}} PERMISSIONS
			}

			self::BeginTransaction(__METHOD__);

			foreach($templates as $tnum => $template){
				$template['templates_link'] = isset($template['templates']) ? $template['templates'] : null;
				$template['templates'] = $template;

				$result = self::massUpdate($template);
				if(!$result) throw new APIException(ZBX_API_ERROR_PARAMETERS, 'Failed to update template');
			}

			self::EndTransaction(true, __METHOD__);
			$upd_templates = self::get(array('templateids'=>$templateids, 'extendoutput'=>1, 'nopermissions'=>1));
			return $upd_templates;

		}
		catch(APIException $e){
			self::EndTransaction(false, __METHOD__);

			$error = $e->getErrors();
			$error = reset($error);
			self::setError(__METHOD__, $e->getCode(), $error);
			return false;
		}
	}

/**
 * Delete Template
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param array $templateids
 * @param array $templateids['templateids']
 * @return boolean
 */
	public static function delete($templates){
		$templates = zbx_toArray($templates);
		$templateids = array();

		$del_templates = self::get(array('templateids'=>zbx_objectValues($templates, 'templateid'),
											'editable'=>1,
											'extendoutput'=>1,
											'preservekeys'=>1));
		foreach($templates as $gnum => $template){
			if(!isset($del_templates[$template['templateid']])){
				self::setError(__METHOD__, ZBX_API_ERROR_PERMISSIONS, S_NO_PERMISSION);
				return false;
			}
			$templateids[] = $template['templateid'];
			//add_audit(AUDIT_ACTION_DELETE, AUDIT_RESOURCE_HOST, 'Template ['.$template['host'].']');
		}

		if(!empty($templateids)){
			$result = delete_host($templateids, false);
		}
		else{
			self::setError(__METHOD__, ZBX_API_ERROR_PARAMETERS, 'Empty input parameter [ templateids ]');
			$result = false;
		}

		if($result){
			return zbx_cleanHashes($del_templates);
		}
		else{
			self::setError(__METHOD__);
			return false;
		}
	}


/**
 * Link Template to Hosts
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param array $data
 * @param string $data['templates']
 * @param string $data['hosts']
 * @param string $data['groups']
 * @param string $data['templates_link']
 * @return boolean
 */
	public static function massAdd($data){
		$transaction = false;

		$templates = isset($data['templates']) ? zbx_toArray($data['templates']) : null;
		$templateids = is_null($templates) ? array() : zbx_objectValues($templates, 'templateid');


		$transaction = self::BeginTransaction(__METHOD__);

		try{
			if(isset($data['groups'])){
				$options = array('groups' => $data['groups'], 'templates' => $templates);
				$result = CHostGroup::massAdd($options);
				if(!$result) throw new APIException(ZBX_API_ERROR_PARAMETERS, 'Can\'t link groups');
			}

			if(isset($data['hosts'])){
				$hostids = zbx_objectValues($data['hosts'], 'hostid');
				self::link($templateids, $hostids);
			}

			if(isset($data['templates_link'])){
				$templates_linkids = zbx_objectValues($data['templates_link'], 'templateid');
				self::link($templates_linkids, $templateids);
			}

			if(isset($data['macros'])){
				$options = array('templates' => zbx_toArray($data['templates']), 'macros' => $data['macros']);
				$result = CUserMacro::massAdd($options);
				if(!$result) throw new APIException(ZBX_API_ERROR_PARAMETERS, 'Can\'t link macros');
			}

			$result = self::EndTransaction(true, __METHOD__);
			return true;
		}
		catch(APIException $e){
			if($transaction) self::EndTransaction(false, __METHOD__);
			$error = $e->getErrors();
			$error = reset($error);
			self::setError(__METHOD__, $e->getCode(), $error);
			return false;
		}
	}

/**
 * Mass update hosts
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param _array $hosts multidimensional array with Hosts data
 * @param array $hosts['hosts'] Array of Host objects to update
 * @param string $hosts['fields']['host'] Host name.
 * @param array $hosts['fields']['groupids'] HostGroup IDs add Host to.
 * @param int $hosts['fields']['port'] Port. OPTIONAL
 * @param int $hosts['fields']['status'] Host Status. OPTIONAL
 * @param int $hosts['fields']['useip'] Use IP. OPTIONAL
 * @param string $hosts['fields']['dns'] DNS. OPTIONAL
 * @param string $hosts['fields']['ip'] IP. OPTIONAL
 * @param int $hosts['fields']['proxy_hostid'] Proxy Host ID. OPTIONAL
 * @param int $hosts['fields']['useipmi'] Use IPMI. OPTIONAL
 * @param string $hosts['fields']['ipmi_ip'] IPMAI IP. OPTIONAL
 * @param int $hosts['fields']['ipmi_port'] IPMI port. OPTIONAL
 * @param int $hosts['fields']['ipmi_authtype'] IPMI authentication type. OPTIONAL
 * @param int $hosts['fields']['ipmi_privilege'] IPMI privilege. OPTIONAL
 * @param string $hosts['fields']['ipmi_username'] IPMI username. OPTIONAL
 * @param string $hosts['fields']['ipmi_password'] IPMI password. OPTIONAL
 * @return boolean
 */
	public static function massUpdate($data){
		$transaction = false;

		$templates = zbx_toArray($data['templates']);
		$templateids = zbx_objectValues($templates, 'templateid');

		try{
			$options = array(
				'templateids' => $templateids,
				'editable' => 1,
				'extendoutput' => 1,
				'preservekeys' => 1,
			);
			$upd_templates = self::get($options);

			foreach($templates as $tnum => $template){
				if(!isset($upd_templates[$template['templateid']])){
					throw new APIException(ZBX_API_ERROR_PERMISSIONS, S_NO_PERMISSION);
				}
			}

// CHECK IF TEMPLATES HAVE AT LEAST 1 GROUP {{{
			if(isset($data['groups']) && empty($data['groups'])){
				throw new APIException(ZBX_API_ERROR_PARAMETERS, 'No groups for template');
			}
			$data['groups'] = zbx_toArray($data['groups']);
// }}} CHECK IF TEMPLATES HAVE AT LEAST 1 GROUP

			$transaction = self::BeginTransaction(__METHOD__);


// UPDATE TEMPLATES PROPERTIES {{{
			if(isset($data['host'])){
				if(count($templates) > 1){
					throw new APIException(ZBX_API_ERROR_PARAMETERS, 'Wrong fields');
				}

				$template_exists = self::checkObjects(array('host' => $data['host']));
				$template_exists = reset($template_exists);
				$cur_template = reset($templates);

				if(!empty($template_exists) && ($template_exists['templateid'] != $cur_template['templateid'])){
					throw new APIException(ZBX_API_ERROR_PARAMETERS, S_TEMPLATE.' [ '.$data['host'].' ] '.S_ALREADY_EXISTS_SMALL);
				}
				
				$host_exists = CHost::checkObjects(array('host' => $data['host']));
				if(!empty($host_exists)){
					throw new APIException(ZBX_API_ERROR_PARAMETERS, S_HOST.' [ '.$data['host'].' ] '.S_ALREADY_EXISTS_SMALL);
				}
			}

			if(isset($data['host']) && !preg_match('/^'.ZBX_PREG_HOST_FORMAT.'$/i', $data['host'])){
				throw new APIException(ZBX_API_ERROR_PARAMETERS, 'Incorrect characters used for Hostname [ '.$data['host'].' ]');
			}

			$sql_set = array();
			if(isset($data['host'])) $sql_set[] = 'host='.zbx_dbstr($data['host']);

			if(!empty($sql_set)){
				$sql = 'UPDATE hosts SET ' . implode(', ', $sql_set) . ' WHERE '.DBcondition('hostid', $templateids);
				$result = DBexecute($sql);
			}
// }}} UPDATE TEMPLATES PROPERTIES


// UPDATE HOSTGROUPS LINKAGE {{{
			if(isset($data['groups']) && !is_null($data['groups'])){
				$template_groups = CHostGroup::get(array('hostids' => $templateids));
				$template_groupids = zbx_objectValues($template_groups, 'groupid');
				$new_groupids = zbx_objectValues($data['groups'], 'groupid');

				$groups_to_add = array_diff($new_groupids, $template_groupids);

				if(!empty($groups_to_add)){
					$result = self::massAdd(array('templates' => $templates, 'groups' => $groups_to_add));
					if(!$result){
						throw new APIException(ZBX_API_ERROR_PARAMETERS, 'Can\'t add group');
					}
				}

				$groups_to_del = array_diff($template_groupids, $new_groupids);

				if(!empty($groups_to_del)){
					$result = self::massRemove(array('templates' => $templates, 'groups' => $groups_to_del));
					if(!$result){
						throw new APIException(ZBX_API_ERROR_PARAMETERS, 'Can\'t remove group');
					}
				}
			}
// }}} UPDATE HOSTGROUPS LINKAGE


			$data['templates_clear'] = isset($data['templates_clear']) ? zbx_toArray($data['templates_clear']) : array();
			$cleared_templateids = array();
			foreach($templateids as $templateid){
				foreach($data['templates_clear'] as $tpl){
					$result = unlink_template($templateid, $tpl['templateid'], false);
					if(!$result){
						throw new APIException(ZBX_API_ERROR_PARAMETERS, 'Cannot unlink template [ '.$tpl['templateid'].' ]');
					}
					$cleared_templateids[] = $tpl['templateid'];
				}
			}


// UPDATE TEMPLATE LINKAGE {{{
// firstly need to unlink all things, to correctly check circulars

			if(isset($data['hosts']) && !is_null($data['hosts'])){
				$template_hosts = CHost::get(array('templateids' => $templateids, 'templated_hosts' => 1));
				$template_hostids = zbx_objectValues($template_hosts, 'hostid');
				$new_hostids = zbx_objectValues($data['hosts'], 'hostid');

				$hosts_to_del = array_diff($template_hostids, $new_hostids);
				$hosts_to_del = array_diff($hosts_to_del, $cleared_templateids);

				if(!empty($hosts_to_del)){
					$result = self::massRemove(array('hosts' => $hosts_to_del, 'templates' => $templates));
					if(!$result){
						throw new APIException(ZBX_API_ERROR_PARAMETERS, 'Can\'t unlink template');
					}
				}			
				}
			
			if(isset($data['templates_link']) && !is_null($data['templates_link'])){
				$template_templates = CTemplate::get(array('hostids' => $templateids));
				$template_templateids = zbx_objectValues($template_templates, 'templateid');
				$new_templateids = zbx_objectValues($data['templates_link'], 'templateid');
				
				$templates_to_del = array_diff($template_templateids, $new_templateids);
				$templates_to_del = array_diff($templates_to_del, $cleared_templateids);
				if(!empty($templates_to_del)){
					$result = self::massRemove(array('templates' => $templates, 'templates_link' => $templates_to_del));
					if(!$result){
						throw new APIException(ZBX_API_ERROR_PARAMETERS, 'Can\'t unlink template');
					}
				}	
			}

			if(isset($data['hosts']) && !is_null($data['hosts'])){
			
				$hosts_to_add = array_diff($new_hostids, $template_hostids);
				if(!empty($hosts_to_add)){
					$result = self::massAdd(array('templates' => $templates, 'hosts' => $hosts_to_add));
					if(!$result){
						throw new APIException(ZBX_API_ERROR_PARAMETERS, 'Can\'t link template');
					}
				}
			}

			if(isset($data['templates_link']) && !is_null($data['templates_link'])){
			
				$templates_to_add = array_diff($new_templateids, $template_templateids);
				if(!empty($templates_to_add)){
					$result = self::massAdd(array('templates' => $templates, 'templates_link' => $templates_to_add));
					if(!$result){
						throw new APIException(ZBX_API_ERROR_PARAMETERS, 'Can\'t link template');
					}
				}
			}
// }}} UPDATE TEMPLATE LINKAGE


// UPDATE MACROS {{{
			if(isset($data['macros']) && !is_null($data['macros'])){
				$host_macros = CUserMacro::get(array('hostids' => $templateids, 'extendoutput' => 1));

				$result = self::massAdd(array('templates' => $templates, 'macros' => $data['macros']));
				if(!$result){
					throw new APIException(ZBX_API_ERROR_PARAMETERS, 'Can\'t add macro');
				}

				$macros_to_del = array();
				foreach($host_macros as $hmacro){
					$del = true;
					foreach($data['macros'] as $nmacro){
						if($hmacro['macro'] == $nmacro['macro']){
							$del = false;
							break;
						}
					}
					if($del){
						$macros_to_del[] = $hmacro;
					}
				}

				if(!empty($macros_to_del)){
					$result = self::massRemove(array('templates' => $templates, 'macros' => $macros_to_del));
					if(!$result){
						throw new APIException(ZBX_API_ERROR_PARAMETERS, 'Can\'t remove macro');
					}
				}
			}
// }}} UPDATE MACROS

			self::EndTransaction(true, __METHOD__);

			$upd_hosts = self::get(array('templateids' => $templateids, 'extendoutput' => 1, 'nopermissions' => 1));
			return $upd_hosts;

		}
		catch(APIException $e){
			if($transaction) self::EndTransaction(false, __METHOD__);

			$error = $e->getErrors();
			$error = reset($error);
			self::setError(__METHOD__, $e->getCode(), $error);
			return false;
		}
	}

/**
 * remove Hosts to HostGroups. All Hosts are added to all HostGroups.
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param array $data
 * @param array $data['templates']
 * @param array $data['groups']
 * @param array $data['hosts']
 * @return boolean
 */
	public static function massRemove($data){
		$errors = array();
		$result = true;

		$templates = isset($data['templates']) ? zbx_toArray($data['templates']) : null;
		$templateids = is_null($templates) ? array() : zbx_objectValues($templates, 'templateid');

		if(isset($data['groups'])){
			$options = array('groups' => $data['groups'], 'templates' => $templates);
			$result = CHostGroup::massRemove($options);
		}

		if(isset($data['hosts'])){
			$hostids = zbx_objectValues($data['hosts'], 'hostid');
			foreach($hostids as $hostid){
				foreach($templateids as $templateid){
					unlink_template($hostid, $templateid, true);
				}
			}
		}

		if(isset($data['templates_link'])){
			$templateids_link = zbx_objectValues($data['templates_link'], 'templateid');
			foreach($templateids_link as $templateid_link){
				foreach($templateids as $templateid){
					unlink_template($templateid, $templateid_link, true);
				}
			}
		}

		if(isset($data['macros'])){
			$options = array('templates' => zbx_toArray($data['templates']), 'macros' => $data['macros']);
			$result = CUserMacro::massRemove($options);
		}


		if($result){
			return $result;
		}
		else{
			self::setMethodErrors(__METHOD__, $errors);
			return false;
		}
	}


/**
 * Link Host to Templates
 *
 * {@source}
 * @access public
 * @static
 * @since 1.8
 * @version 1
 *
 * @param array $data
 * @param string $data['hosts']
 * @param array $data['templats']
 * @return boolean
 */
	public static function linkTemplates($data){
		$result = true;
		$errors = array();

		$hosts = zbx_toArray($data['hosts']);
		$hostids = zbx_objectValues($hosts, 'hostid');

		$templates = zbx_toArray($data['templates']);
		$templateids = zbx_objectValues($templates, 'templateid');

		self::BeginTransaction(__METHOD__);

		$sql = 'SELECT hostid, templateid FROM hosts_templates WHERE '.DBcondition('hostid', $hostids).' AND '.DBcondition('templateid', $templateids);
		$linked_db = DBselect($sql);
		$linked = array();
		while($pair = DBfetch($linked_db)){
			$linked[$pair['templateid']] = array($pair['hostid'] => $pair['hostid']);
		}

		foreach($templates as $tnum => $template){
			$templateid = $template['templateid'];

			$hosttemplateid = get_dbid('hosts_templates', 'hosttemplateid');
			foreach($hosts as $hnum => $host){

				if(isset($linked[$templateid]) && isset($linked[$templateid][$host['hostid']])) continue;

				if(!$result = DBexecute('INSERT INTO hosts_templates VALUES ('.$hosttemplateid.','.$host['hostid'].','.$templateid.')'))
				$result = false;
				break;
			}
			if(!$result) break;
		}

		if($result){
			foreach($templates as $tnum => $template){
				foreach($hosts as $hnum => $host){
//					$result = sync_host_with_templates($hostid, $templateid);
					sync_host_with_templates($host['hostid'], $template['templateid']);
				}
//				if(!$result) break;
			}
		}

		$result = self::EndTransaction($result, __METHOD__);

		if($result){
			return true;
		}
		else{
			self::setMethodErrors(__METHOD__, $errors);
			return false;
		}
	}


	private static function link($templateids, $targetids){
		if(empty($templateids)) return true;
		
		try{
			self::BeginTransaction(__METHOD__);

// CHECK TEMPLATE TRIGGERS DEPENDENCIES {{{
			foreach($templateids as $tnum => $templateid){
				$triggerids = array();
				$db_triggers = get_triggers_by_hostid($templateid);
				while($trigger = DBfetch($db_triggers)){
					$triggerids[$trigger['triggerid']] = $trigger['triggerid'];
				}

				$sql = 'SELECT DISTINCT h.hostid, h.host '.
						' FROM trigger_depends td, functions f, items i, hosts h '.
						' WHERE (('.DBcondition('td.triggerid_down',$triggerids).' AND f.triggerid=td.triggerid_up) '.
							' OR ('.DBcondition('td.triggerid_up',$triggerids).' AND f.triggerid=td.triggerid_down)) '.
							' AND i.itemid=f.itemid '.
							' AND h.hostid=i.hostid '.
							' AND '.DBcondition('h.hostid', $templateids, true).
							' AND h.status='.HOST_STATUS_TEMPLATE;

				if($db_dephost = DBfetch(DBselect($sql))){
					$options = array(
							'templateids' => $templateid,
							'output'=> API_OUTPUT_EXTEND
						);

					$tmp_tpls = self::get($options);
					$tmp_tpl = reset($tmp_tpls);

					throw new APIException(ZBX_API_ERROR_PARAMETERS,
						'Trigger in template [ '.$tmp_tpl['host'].' ] has dependency with trigger in template [ '.$db_dephost['host'].' ]');
				}
			}
// }}} CHECK TEMPLATE TRIGGERS DEPENDENCIES


			$linked = array();
			$sql = 'SELECT hostid, templateid '.
					' FROM hosts_templates '.
					' WHERE '.DBcondition('hostid', $targetids).
						' AND '.DBcondition('templateid', $templateids);
			$linked_db = DBselect($sql);
			while($pair = DBfetch($linked_db)){
				$linked[] = array($pair['hostid'] => $pair['templateid']);
			}

// add template linkages, if problems rollback later
			foreach($targetids as $targetid){
				foreach($templateids as $tnum => $templateid){
					foreach($linked as $lnum => $link){
						if(isset($link[$targetid]) && ($link[$targetid] == $templateid)) continue 2;
					}

					$values = array(get_dbid('hosts_templates', 'hosttemplateid'), $targetid, $templateid);
					$sql = 'INSERT INTO hosts_templates VALUES ('. implode(', ', $values) .')';
					$result = DBexecute($sql);

					if(!$result) throw new APIException(ZBX_API_ERROR_PARAMETERS, 'DBError');
				}
			}

// CHECK CIRCULAR LINKAGE {{{

// get template linkage graph
			$graph = array();
			$sql = 'SELECT ht.hostid, ht.templateid'.
				' FROM hosts_templates ht, hosts h'.
				' WHERE ht.hostid=h.hostid'.
					' AND h.status='.HOST_STATUS_TEMPLATE;
			$db_graph = DBselect($sql);
			while($branch = DBfetch($db_graph)){
				if(!isset($graph[$branch['hostid']])) $graph[$branch['hostid']] = array();
				$graph[$branch['hostid']][$branch['templateid']] = $branch['templateid'];
			}

// get points that have more than one parent templates			
			$start_points = array();
			$sql = 'SELECT max(ht.hostid) as hostid, ht.templateid'.
				' FROM('.
					' SELECT count(htt.templateid) as ccc, htt.hostid'.
					' FROM hosts_templates htt'.
					' WHERE htt.hostid NOT IN ( SELECT httt.templateid FROM hosts_templates httt )'.
					' GROUP BY htt.hostid'.
					' ) ggg, hosts_templates ht'.
				' WHERE ggg.ccc>1'.
					' AND ht.hostid=ggg.hostid'.
				' GROUP BY ht.templateid';
			$db_start_points = DBselect($sql);
			while($start_point = DBfetch($db_start_points)){				
				$start_points[] = $start_point['hostid'];
				$graph[$start_point['hostid']][$start_point['templateid']] = $start_point['templateid'];
			}

// add to the start points also points which we add current templates
			$start_points = array_merge($start_points, $targetids);
			$start_points = array_unique($start_points);

			foreach($start_points as $spnum => $start){
				$path = array();
				if(!self::checkCircularLink($graph, $start, $path)){
					throw new APIException(ZBX_API_ERROR_PARAMETERS, 'Circular link can not be created');
				}
			}

// }}} CHECK CIRCULAR LINKAGE


			foreach($targetids as $targetid){
				foreach($templateids as $tnum => $templateid){
					foreach($linked as $lnum => $link){
						if(isset($link[$targetid]) && ($link[$targetid] == $templateid)){
							unset($linked[$lnum]);
							continue 2;
						}
					}
					sync_host_with_templates($targetid, $templateid);
				}
			}

			self::EndTransaction(true, __METHOD__);
			
			return true;
		}
		catch(APIException $e){
			self::EndTransaction(false, __METHOD__);
			throw new APIException($e->getCode(), $e->getErrors());
			
			return false;
		}
	}

	private static function checkCircularLink(&$graph, $current, &$path){
		
		if(isset($path[$current])) return false;
		$path[$current] = $current;
		if(!isset($graph[$current])) return true;
		
		foreach($graph[$current] as $step){
			if(!self::checkCircularLink($graph, $step, $path)) return false;
		}
		
		return true;
	}
}
?>