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
function condition_operator2str($operator){
	$str_op[CONDITION_OPERATOR_EQUAL] 	= '=';
	$str_op[CONDITION_OPERATOR_NOT_EQUAL]	= '<>';
	$str_op[CONDITION_OPERATOR_LIKE]	= S_LIKE_SMALL;
	$str_op[CONDITION_OPERATOR_NOT_LIKE]	= S_NOT_LIKE_SMALL;
	$str_op[CONDITION_OPERATOR_IN]		= S_IN_SMALL;
	$str_op[CONDITION_OPERATOR_MORE_EQUAL]	= '>=';
	$str_op[CONDITION_OPERATOR_LESS_EQUAL]	= '<=';
	$str_op[CONDITION_OPERATOR_NOT_IN]	= S_NOT_IN_SMALL;

	if(isset($str_op[$operator]))
		return $str_op[$operator];

	return S_UNKNOWN;
}

function condition_type2str($conditiontype){
	$str_type[CONDITION_TYPE_HOST_GROUP]		= _('Host group');
	$str_type[CONDITION_TYPE_HOST_TEMPLATE]		= _('Host template');
	$str_type[CONDITION_TYPE_TRIGGER]		= _('Trigger');
	$str_type[CONDITION_TYPE_HOST]			= _('Host');
	$str_type[CONDITION_TYPE_TRIGGER_NAME]		= _('Trigger name');
	$str_type[CONDITION_TYPE_TRIGGER_VALUE]		= _('Trigger value');
	$str_type[CONDITION_TYPE_TRIGGER_SEVERITY]	= _('Trigger severity');
	$str_type[CONDITION_TYPE_TIME_PERIOD]		= _('Time period');
	$str_type[CONDITION_TYPE_MAINTENANCE]		= _('Maintenance status');
	$str_type[CONDITION_TYPE_NODE]			= _('Node');
	$str_type[CONDITION_TYPE_DRULE]			= _('Discovery rule');
	$str_type[CONDITION_TYPE_DCHECK]		= _('Discovery check');
	$str_type[CONDITION_TYPE_DOBJECT]		= _('Discovery object');
	$str_type[CONDITION_TYPE_DHOST_IP]		= _('Host IP');
	$str_type[CONDITION_TYPE_DSERVICE_TYPE]		= _('Service type');
	$str_type[CONDITION_TYPE_DSERVICE_PORT]		= _('Service port');
	$str_type[CONDITION_TYPE_DSTATUS]		= _('Discovery status');
	$str_type[CONDITION_TYPE_DUPTIME]		= _('Uptime/Downtime');
	$str_type[CONDITION_TYPE_DVALUE]		= _('Received value');
	$str_type[CONDITION_TYPE_EVENT_ACKNOWLEDGED]	= _('Event acknowledged');
	$str_type[CONDITION_TYPE_APPLICATION]		= _('Application');
	$str_type[CONDITION_TYPE_PROXY]			= _('Proxy');
	$str_type[CONDITION_TYPE_HOST_NAME]		= _('Host name');

	if(isset($str_type[$conditiontype]))
		return $str_type[$conditiontype];

return S_UNKNOWN;
}

function discovery_object2str($object){
	$str_object[EVENT_OBJECT_DHOST]		= S_DEVICE;
	$str_object[EVENT_OBJECT_DSERVICE]	= S_SERVICE;

	if(isset($str_object[$object]))
		return $str_object[$object];

return S_UNKNOWN;
}

function condition_value2str($conditiontype, $value){
	switch($conditiontype){
		case CONDITION_TYPE_HOST_GROUP:
			$groups = API::HostGroup()->get(array(
				'groupids' => $value,
				'output' => API_OUTPUT_EXTEND,
				'nodeids' => get_current_nodeid(true),
				'limit' => 1
			));

			if(!$group = reset($groups))
				error(S_NO_HOST_GROUPS_WITH.' groupid "'.$value.'"');

			$str_val = '';
			if(id2nodeid($value) != get_current_nodeid())
				$str_val = get_node_name_by_elid($value, true, ': ');

			$str_val.= $group['name'];
			break;
		case CONDITION_TYPE_TRIGGER:
			$trigs = API::Trigger()->get(array(
				'triggerids' => $value,
				'expandTriggerDescriptions' => true,
				'output' => API_OUTPUT_EXTEND,
				'selectHosts' => array('host'),
				'nodeids' => get_current_nodeid(true),
				'limit' => 1
			));
			$trig = reset($trigs);
			$host = reset($trig['hosts']);
			$str_val = '';
			if(id2nodeid($value) != get_current_nodeid())
				$str_val = get_node_name_by_elid($value, true, ': ');

			$str_val .= $host['host'].':'.$trig['description'];
			break;
		case CONDITION_TYPE_HOST:
		case CONDITION_TYPE_HOST_TEMPLATE:
			$host = get_host_by_hostid($value);
			$str_val = '';
			if(id2nodeid($value) != get_current_nodeid()) $str_val = get_node_name_by_elid($value, true, ': ');
			$str_val.= $host['name'];
			break;
		case CONDITION_TYPE_TRIGGER_NAME:
		case CONDITION_TYPE_HOST_NAME:
			$str_val = $value;
			break;
		case CONDITION_TYPE_TRIGGER_VALUE:
			$str_val = trigger_value2str($value);
			break;
		case CONDITION_TYPE_TRIGGER_SEVERITY:
			$str_val = getSeverityCaption($value);
			break;
		case CONDITION_TYPE_TIME_PERIOD:
			$str_val = $value;
			break;
		case CONDITION_TYPE_MAINTENANCE:
			$str_val = S_MAINTENANCE_SMALL;
			break;
		case CONDITION_TYPE_NODE:
			$node = get_node_by_nodeid($value);
			$str_val = $node['name'];
			break;
		case CONDITION_TYPE_DRULE:
			$drule = get_discovery_rule_by_druleid($value);
			$str_val = $drule['name'];
			break;
		case CONDITION_TYPE_DCHECK:
			$sql = 'SELECT DISTINCT dr.name,c.dcheckid,c.type,c.key_,c.snmp_community,c.ports'.
					' FROM drules dr,dchecks c '.
					' WHERE dr.druleid=c.druleid '.
						' AND c.dcheckid='.$value;
			$row = DBfetch(DBselect($sql));
			$str_val = $row['name'].':'.discovery_check2str($row['type'],
					$row['snmp_community'], $row['key_'], $row['ports']);
			break;
		case CONDITION_TYPE_DOBJECT:
			$str_val = discovery_object2str($value);
			break;
		case CONDITION_TYPE_PROXY:
			$host = get_host_by_hostid($value);
			$str_val = $host['host'];
			break;
		case CONDITION_TYPE_DHOST_IP:
			$str_val = $value;
			break;
		case CONDITION_TYPE_DSERVICE_TYPE:
			$str_val = discovery_check_type2str($value);
			break;
		case CONDITION_TYPE_DSERVICE_PORT:
			$str_val = $value;
			break;
		case CONDITION_TYPE_DSTATUS:
			$str_val = discovery_object_status2str($value);
			break;
		case CONDITION_TYPE_DUPTIME:
			$str_val = $value;
			break;
		case CONDITION_TYPE_DVALUE:
			$str_val = $value;
			break;
		case CONDITION_TYPE_EVENT_ACKNOWLEDGED:
			$str_val = ($value)?S_ACK:S_NOT_ACK;
			break;
		case CONDITION_TYPE_APPLICATION:
			$str_val = $value;
			break;
		default:
			return S_UNKNOWN;
			break;
	}
	return '"'.$str_val.'"';
}

function get_condition_desc($conditiontype, $operator, $value){
	return condition_type2str($conditiontype).' '.
		condition_operator2str($operator).' '.
		condition_value2str($conditiontype, $value);
}

define('LONG_DESCRIPTION', 0);
define('SHORT_DESCRIPTION', 1);

function get_operation_desc($type, $data){
	$result = array();

	if($type == SHORT_DESCRIPTION){
		switch($data['operationtype']){
			case OPERATION_TYPE_MESSAGE:
				if(!isset($data['opmessage_usr'])) $data['opmessage_usr'] = array();
				if(!isset($data['opmessage_grp'])) $data['opmessage_grp'] = array();

				$users = API::User()->get(array(
					'userids' => zbx_objectValues($data['opmessage_usr'],'userid'),
					'output' => array('userid', 'alias')
				));
				if(!empty($users)){
					order_result($users, 'alias');

					$result[] = bold(array(S_SEND_MESSAGE_TO,SPACE,S_USERS,': ' ));
					$result[] = array(implode(', ', zbx_objectValues($users,'alias')), BR());
				}


				$usrgrps = API::UserGroup()->get(array(
					'usrgrpids' => zbx_objectValues($data['opmessage_grp'],'usrgrpid'),
					'output' => API_OUTPUT_EXTEND
				));
				if(!empty($usrgrps)){
					order_result($usrgrps, 'name');

					$result[] = bold(array(S_SEND_MESSAGE_TO,SPACE,S_GROUPS,': ' ));
					$result[] = array(implode(', ', zbx_objectValues($usrgrps,'name')), BR());
				}
				break;
			case OPERATION_TYPE_COMMAND:
				if(!isset($data['opcommand_grp'])) $data['opcommand_grp'] = array();
				if(!isset($data['opcommand_hst'])) $data['opcommand_hst'] = array();

				$hosts = API::Host()->get(array(
					'hostids' => zbx_objectValues($data['opcommand_hst'],'hostid'),
					'output' => array('hostid', 'name')
				));

				foreach($data['opcommand_hst'] as $num => $cmd){
					if($cmd['hostid'] != 0) continue;

					$result[] = array(bold(_('Run remote command on current host')), BR());
					break;
				}

				if(!empty($hosts)){
					order_result($hosts, 'name');

					$result[] = bold(_('Run remote command on hosts: '));
					$result[] = array(implode(', ', zbx_objectValues($hosts,'name')), BR());
				}


				$groups = API::HostGroup()->get(array(
					'groupids' => zbx_objectValues($data['opcommand_grp'],'groupid'),
					'output' => array('groupid', 'name')
				));

				if(!empty($groups)){
					order_result($groups, 'name');

					$result[] = bold(_('Run remote command on host groups: '));
					$result[] = array(implode(', ', zbx_objectValues($groups,'name')), BR());
				}
				break;
			case OPERATION_TYPE_HOST_ADD:
				$result[] = array(bold(_('Add host')), BR());
				break;
			case OPERATION_TYPE_HOST_REMOVE:
				$result[] = array(bold(_('Remove host')), BR());
				break;
			case OPERATION_TYPE_HOST_ENABLE:
				$result[] = array(bold(_('Enable host')), BR());
				break;
			case OPERATION_TYPE_HOST_DISABLE:
				$result[] = array(bold(_('Disable host')), BR());
				break;
			case OPERATION_TYPE_GROUP_ADD:
			case OPERATION_TYPE_GROUP_REMOVE:
				if(!isset($data['opgroup'])) $data['opgroup'] = array();

				$groups = API::HostGroup()->get(array(
					'groupids' => zbx_objectValues($data['opgroup'],'groupid'),
					'output' => array('groupid', 'name')
				));

				if(!empty($groups)){
					order_result($groups, 'name');

					if(OPERATION_TYPE_GROUP_ADD == $data['operationtype'])
						$result[] = bold(_('Add to host groups: '));
					else
						$result[] = bold(_('Remove from host groups: '));

					$result[] = array(implode(', ', zbx_objectValues($groups,'name')), BR());
				}
				break;
			case OPERATION_TYPE_TEMPLATE_ADD:
			case OPERATION_TYPE_TEMPLATE_REMOVE:
				if(!isset($data['optemplate'])) $data['optemplate'] = array();

				$templates = API::Template()->get(array(
					'templateids' => zbx_objectValues($data['optemplate'],'templateid'),
					'output' => array('hostid', 'host')
				));

				if(!empty($templates)){
					order_result($templates, 'host');

					if(OPERATION_TYPE_TEMPLATE_ADD == $data['operationtype'])
						$result[] = bold(_('Link to templates: '));
					else
						$result[] = bold(_('Unlink from templates: '));

					$result[] = array(implode(', ', zbx_objectValues($templates,'host')), BR());
				}
				break;
			default:
				break;
		}
	}
	else{
		switch($data['operationtype']){
			case OPERATION_TYPE_MESSAGE:
				if(isset($data['opmessage']['default_msg']) && !empty($data['opmessage']['default_msg'])){
					if(isset($_REQUEST['def_shortdata']) && isset($_REQUEST['def_longdata'])){
						$result[] = array(bold(S_SUBJECT.': '),BR(),zbx_nl2br($_REQUEST['def_shortdata']));
						$result[] = array(bold(S_MESSAGE.':'),BR(),zbx_nl2br($_REQUEST['def_longdata']));
					}
					else if(isset($data['opmessage']['operationid'])){
						$sql = 'SELECT a.def_shortdata,a.def_longdata '.
								' FROM actions a, operations o '.
								' WHERE a.actionid=o.actionid '.
									' AND o.operationid='.$data['operationid'];
						if($rows = DBfetch(DBselect($sql,1))){
							$result[] = array(bold(S_SUBJECT.': '), BR(),zbx_nl2br($rows['def_shortdata']));
							$result[] = array(bold(S_MESSAGE.':'), BR(),zbx_nl2br($rows['def_longdata']));
						}
					}
				}
				else{
					$result[] = array(bold(S_SUBJECT.': '), BR(), zbx_nl2br($data['opmessage']['subject']));
					$result[] = array(bold(S_MESSAGE.':'), BR(), zbx_nl2br($data['opmessage']['message']));
				}

				break;
			case OPERATION_TYPE_COMMAND:
				if(!isset($data['opcommand_grp'])) $data['opcommand_grp'] = array();
				if(!isset($data['opcommand_hst'])) $data['opcommand_hst'] = array();

				$hosts = API::Host()->get(array(
					'hostids' => zbx_objectValues($data['opcommand_hst'],'hostid'),
					'output' => array('hostid', 'host'),
					'preservekeys' => true
				));
				order_result($hosts, 'host');
				foreach($data['opcommand_hst'] as $cnum => $command){
					if($command['hostid'] > 0) continue;
					$result[] = _s('Current host: ');

					$result[] = italic(zbx_nl2br($command['command']));
				}

				foreach($data['opcommand_hst'] as $cnum => $command){
					if($command['hostid'] == 0) continue;
					$result[] = _s('Host "%1$s": ', $hosts[$command['hostid']]['host']);

					$result[] = italic(zbx_nl2br($command['command']));
				}

				$groups = API::HostGroup()->get(array(
					'groupids' => zbx_objectValues($data['opcommand_grp'],'groupid'),
					'output' => array('groupid', 'name'),
					'preservekeys' => true
				));
				order_result($groups, 'name');
				foreach($data['opcommand_grp'] as $cnum => $command){
					$result[] = _s('Host group "%1$s": ', $groups[$command['groupid']]['name']);
					$result[] = italic(zbx_nl2br($command['command']));
				}
				break;
			default:
		}
	}

	return $result;
}

function get_conditions_by_eventsource($eventsource){
	$conditions[EVENT_SOURCE_TRIGGERS] = array(
			CONDITION_TYPE_APPLICATION,
//			CONDITION_TYPE_EVENT_ACKNOWLEDGED,
			CONDITION_TYPE_HOST_GROUP,
			CONDITION_TYPE_HOST_TEMPLATE,
			CONDITION_TYPE_HOST,
			CONDITION_TYPE_TRIGGER,
			CONDITION_TYPE_TRIGGER_NAME,
			CONDITION_TYPE_TRIGGER_SEVERITY,
			CONDITION_TYPE_TRIGGER_VALUE,
			CONDITION_TYPE_TIME_PERIOD,
			CONDITION_TYPE_MAINTENANCE
		);
	$conditions[EVENT_SOURCE_DISCOVERY] = array(
			CONDITION_TYPE_DHOST_IP,
			CONDITION_TYPE_DSERVICE_TYPE,
			CONDITION_TYPE_DSERVICE_PORT,
			CONDITION_TYPE_DRULE,
			CONDITION_TYPE_DCHECK,
			CONDITION_TYPE_DOBJECT,
			CONDITION_TYPE_DSTATUS,
			CONDITION_TYPE_DUPTIME,
			CONDITION_TYPE_DVALUE,
			CONDITION_TYPE_PROXY
		);
	$conditions[EVENT_SOURCE_AUTO_REGISTRATION] = array(
			CONDITION_TYPE_HOST_NAME,
			CONDITION_TYPE_PROXY
		);

	if (ZBX_DISTRIBUTED)
		array_push($conditions[EVENT_SOURCE_TRIGGERS], CONDITION_TYPE_NODE);

	if(isset($conditions[$eventsource]))
		return $conditions[$eventsource];

	return $conditions[EVENT_SOURCE_TRIGGERS];
}

function get_opconditions_by_eventsource($eventsource){
	$conditions = array(
		EVENT_SOURCE_TRIGGERS => array(
			CONDITION_TYPE_EVENT_ACKNOWLEDGED
		),
		EVENT_SOURCE_DISCOVERY => array(),
		);

	if(isset($conditions[$eventsource]))
		return $conditions[$eventsource];

}

function get_operations_by_eventsource($eventsource){
	$operations[EVENT_SOURCE_TRIGGERS] = array(
			OPERATION_TYPE_MESSAGE,
			OPERATION_TYPE_COMMAND
		);
	$operations[EVENT_SOURCE_DISCOVERY] = array(
			OPERATION_TYPE_MESSAGE,
			OPERATION_TYPE_COMMAND,
			OPERATION_TYPE_HOST_ADD,
			OPERATION_TYPE_HOST_REMOVE,
			OPERATION_TYPE_HOST_ENABLE,
			OPERATION_TYPE_HOST_DISABLE,
			OPERATION_TYPE_GROUP_ADD,
			OPERATION_TYPE_GROUP_REMOVE,
			OPERATION_TYPE_TEMPLATE_ADD,
			OPERATION_TYPE_TEMPLATE_REMOVE
		);
	$operations[EVENT_SOURCE_AUTO_REGISTRATION] = array(
			OPERATION_TYPE_MESSAGE,
			OPERATION_TYPE_COMMAND,
			OPERATION_TYPE_HOST_ADD,
			OPERATION_TYPE_HOST_DISABLE,
			OPERATION_TYPE_GROUP_ADD,
			OPERATION_TYPE_TEMPLATE_ADD
		);

	if(isset($operations[$eventsource]))
		return $operations[$eventsource];

	return $operations[EVENT_SOURCE_TRIGGERS];
}

function operation_type2str($type=null){
	$types = array(
		OPERATION_TYPE_MESSAGE => S_SEND_MESSAGE,
		OPERATION_TYPE_COMMAND => S_REMOTE_COMMAND,
		OPERATION_TYPE_HOST_ADD => S_ADD_HOST,
		OPERATION_TYPE_HOST_REMOVE => S_REMOVE_HOST,
		OPERATION_TYPE_HOST_ENABLE => S_ENABLE_HOST,
		OPERATION_TYPE_HOST_DISABLE => S_DISABLE_HOST,
		OPERATION_TYPE_GROUP_ADD => S_ADD_TO_GROUP,
		OPERATION_TYPE_GROUP_REMOVE => S_DELETE_FROM_GROUP,
		OPERATION_TYPE_TEMPLATE_ADD => S_LINK_TO_TEMPLATE,
		OPERATION_TYPE_TEMPLATE_REMOVE => S_UNLINK_FROM_TEMPLATE,
	);

	if(is_null($type))
		return order_result($types);
	else if(isset($types[$type]))
		return $types[$type];
	else return S_UNKNOWN;
}

function get_operators_by_conditiontype($conditiontype){
	$operators[CONDITION_TYPE_HOST_GROUP] = array(
			CONDITION_OPERATOR_EQUAL,
			CONDITION_OPERATOR_NOT_EQUAL
		);
	$operators[CONDITION_TYPE_HOST_TEMPLATE] = array(
			CONDITION_OPERATOR_EQUAL,
			CONDITION_OPERATOR_NOT_EQUAL
		);
	$operators[CONDITION_TYPE_HOST] = array(
			CONDITION_OPERATOR_EQUAL,
			CONDITION_OPERATOR_NOT_EQUAL
		);
	$operators[CONDITION_TYPE_TRIGGER] = array(
			CONDITION_OPERATOR_EQUAL,
			CONDITION_OPERATOR_NOT_EQUAL
		);
	$operators[CONDITION_TYPE_TRIGGER_NAME] = array(
			CONDITION_OPERATOR_LIKE,
			CONDITION_OPERATOR_NOT_LIKE
		);
	$operators[CONDITION_TYPE_TRIGGER_SEVERITY] = array(
			CONDITION_OPERATOR_EQUAL,
			CONDITION_OPERATOR_NOT_EQUAL,
			CONDITION_OPERATOR_MORE_EQUAL,
			CONDITION_OPERATOR_LESS_EQUAL
		);
	$operators[CONDITION_TYPE_TRIGGER_VALUE] = array(
			CONDITION_OPERATOR_EQUAL
		);
	$operators[CONDITION_TYPE_TIME_PERIOD] = array(
			CONDITION_OPERATOR_IN,
			CONDITION_OPERATOR_NOT_IN
		);
	$operators[CONDITION_TYPE_MAINTENANCE] = array(
			CONDITION_OPERATOR_IN,
			CONDITION_OPERATOR_NOT_IN
		);
	$operators[CONDITION_TYPE_NODE] = array(
			CONDITION_OPERATOR_EQUAL,
			CONDITION_OPERATOR_NOT_EQUAL
		);
	$operators[CONDITION_TYPE_DRULE] = array(
			CONDITION_OPERATOR_EQUAL,
			CONDITION_OPERATOR_NOT_EQUAL
		);
	$operators[CONDITION_TYPE_DCHECK] = array(
			CONDITION_OPERATOR_EQUAL,
			CONDITION_OPERATOR_NOT_EQUAL
		);
	$operators[CONDITION_TYPE_DOBJECT] = array(
			CONDITION_OPERATOR_EQUAL,
		);
	$operators[CONDITION_TYPE_PROXY] = array(
			CONDITION_OPERATOR_EQUAL,
			CONDITION_OPERATOR_NOT_EQUAL
		);
	$operators[CONDITION_TYPE_DHOST_IP] = array(
			CONDITION_OPERATOR_EQUAL,
			CONDITION_OPERATOR_NOT_EQUAL
		);
	$operators[CONDITION_TYPE_DSERVICE_TYPE] = array(
			CONDITION_OPERATOR_EQUAL,
			CONDITION_OPERATOR_NOT_EQUAL
		);
	$operators[CONDITION_TYPE_DSERVICE_PORT] = array(
			CONDITION_OPERATOR_EQUAL,
			CONDITION_OPERATOR_NOT_EQUAL
		);
	$operators[CONDITION_TYPE_DSTATUS] = array(
			CONDITION_OPERATOR_EQUAL,
		);
	$operators[CONDITION_TYPE_DUPTIME] = array(
			CONDITION_OPERATOR_MORE_EQUAL,
			CONDITION_OPERATOR_LESS_EQUAL
		);
	$operators[CONDITION_TYPE_DVALUE] = array(
			CONDITION_OPERATOR_EQUAL,
			CONDITION_OPERATOR_NOT_EQUAL,
			CONDITION_OPERATOR_MORE_EQUAL,
			CONDITION_OPERATOR_LESS_EQUAL,
			CONDITION_OPERATOR_LIKE,
			CONDITION_OPERATOR_NOT_LIKE
		);
	$operators[CONDITION_TYPE_EVENT_ACKNOWLEDGED] = array(
			CONDITION_OPERATOR_EQUAL
		);
	$operators[CONDITION_TYPE_APPLICATION] = array(
			CONDITION_OPERATOR_EQUAL,
			CONDITION_OPERATOR_LIKE,
			CONDITION_OPERATOR_NOT_LIKE
		);
	$operators[CONDITION_TYPE_HOST_NAME] = array(
			CONDITION_OPERATOR_LIKE,
			CONDITION_OPERATOR_NOT_LIKE
		);

	if(isset($operators[$conditiontype]))
		return $operators[$conditiontype];

	return array();
}

function validate_condition($conditiontype, $value){
	switch($conditiontype){
		case CONDITION_TYPE_HOST_GROUP:
			$groups = API::HostGroup()->get(array(
				'groupids' => $value,
				'output' => API_OUTPUT_SHORTEN,
				'nodeids' => get_current_nodeid(true),
			));
			if(empty($groups)){
				error(S_INCORRECT_GROUP);
				return false;
			}
			break;
		case CONDITION_TYPE_HOST_TEMPLATE:
			$templates = API::Template()->get(array(
				'templateids' => $value,
				'output' => API_OUTPUT_SHORTEN,
				'nodeids' => get_current_nodeid(true),
			));
			if(empty($templates)){
				error(S_INCORRECT_HOST);
				return false;
			}
			break;
		case CONDITION_TYPE_TRIGGER:
			$triggers = API::Trigger()->get(array(
				'triggerids' => $value,
				'output' => API_OUTPUT_SHORTEN,
				'nodeids' => get_current_nodeid(true),
			));
			if(empty($triggers)){
				error(S_INCORRECT_TRIGGER);
				return false;
			}
			break;
		case CONDITION_TYPE_HOST:
			$hosts = API::Host()->get(array(
				'hostids' => $value,
				'output' => API_OUTPUT_SHORTEN,
				'nodeids' => get_current_nodeid(true),
			));
			if(empty($hosts)){
				error(S_INCORRECT_HOST);
				return false;
			}
			break;
		case CONDITION_TYPE_PROXY:
			$proxyes = API::Proxy()->get(array(
				'proxyids' => $value,
				'output' => API_OUTPUT_SHORTEN,
				'nodeids' => get_current_nodeid(true),
			));
			if(empty($proxyes)){
				error(_('Incorrect proxy.'));
				return false;
			}
			break;
		case CONDITION_TYPE_TIME_PERIOD:
			if( !validate_period($value) ){
				error(S_INCORRECT_PERIOD.' ['.$value.']');
				return false;
			}
			break;
		case CONDITION_TYPE_DHOST_IP:
			if( !validate_ip_range($value) ){
				error(S_INCORRECT_IP.' ['.$value.']');
				return false;
			}
			break;
		case CONDITION_TYPE_DSERVICE_TYPE:
			if( S_UNKNOWN == discovery_check_type2str($value) ){
				error(S_INCORRECT_DISCOVERY_CHECK);
				return false;
			}
			break;
		case CONDITION_TYPE_DSERVICE_PORT:
			if( !validate_port_list($value) ){
				error(S_INCORRECT_PORT.' ['.$value.']');
				return false;
			}
			break;
		case CONDITION_TYPE_DSTATUS:
			if( S_UNKNOWN == discovery_object_status2str($value) ){
				error(S_INCORRECT_DISCOVERY_STATUS);
				return false;
			}
			break;
		case CONDITION_TYPE_EVENT_ACKNOWLEDGED:
			if(S_UNKNOWN == condition_value2str($conditiontype,$value)){
				error(S_INCORRECT_DISCOVERY_STATUS);
				return false;
			}
			break;

		case CONDITION_TYPE_TRIGGER_NAME:
		case CONDITION_TYPE_TRIGGER_VALUE:
		case CONDITION_TYPE_TRIGGER_SEVERITY:
		case CONDITION_TYPE_MAINTENANCE:
		case CONDITION_TYPE_NODE:
		case CONDITION_TYPE_DRULE:
		case CONDITION_TYPE_DCHECK:
		case CONDITION_TYPE_DOBJECT:
		case CONDITION_TYPE_DUPTIME:
		case CONDITION_TYPE_DVALUE:
		case CONDITION_TYPE_APPLICATION:
		case CONDITION_TYPE_HOST_NAME:
			break;
		default:
			error(S_INCORRECT_CONDITION_TYPE);
			return false;
			break;
	}
	return true;
}


function count_operations_delay($operations, $def_period=0){
	$delays = array(0,0);
	$periods = array();
	$max_step = 0;
	foreach($operations as $num => $operation){
		$step_from = $operation['esc_step_from']?$operation['esc_step_from']:1;
		$step_to = $operation['esc_step_to']?$operation['esc_step_to']:9999;
		$esc_period = $operation['esc_period']?$operation['esc_period']:$def_period;

		$max_step = ($max_step>$step_from)?$max_step:$step_from;

		for($i=$step_from; $i<$step_to; $i++){
			if(isset($periods[$i]) && ($periods[$i] < $esc_period)){
			}
			else{
				$periods[$i]= $esc_period;
			}
		}
	}

	for($i=1; $i<=$max_step; $i++){
		$esc_period = isset($periods[$i])?$periods[$i]:$def_period;
		$delays[$i+1] = $delays[$i] + $esc_period;
	}

return $delays;
}

function get_history_of_actions($limit,&$last_clock=null,$sql_cond=''){
	validate_sort_and_sortorder('clock', ZBX_SORT_DOWN);
	$available_triggers = get_accessible_triggers(PERM_READ_ONLY, array());

	$alerts = array();
	$clock = array();
	$table = new CTableInfo(S_NO_ACTIONS_FOUND);
	$table->setHeader(array(
			is_show_all_nodes() ? make_sorting_header(S_NODES,'a.alertid') : null,
			make_sorting_header(S_TIME,'clock'),
			make_sorting_header(S_TYPE,'description'),
			make_sorting_header(S_STATUS,'status'),
			make_sorting_header(S_RETRIES_LEFT,'retries'),
			make_sorting_header(S_RECIPIENTS,'sendto'),
			S_MESSAGE,
			S_ERROR
			));

	$sql = 'SELECT a.alertid,a.clock,mt.description,a.sendto,a.subject,a.message,a.status,a.retries,a.error '.
			' FROM events e, alerts a '.
				' LEFT JOIN media_type mt ON mt.mediatypeid=a.mediatypeid '.
			' WHERE e.eventid = a.eventid '.
				' AND alerttype IN ('.ALERT_TYPE_MESSAGE.') '.
				$sql_cond.
				' AND '.DBcondition('e.objectid',$available_triggers).
				' AND '.DBin_node('a.alertid').
			' ORDER BY a.clock DESC';
	$result = DBselect($sql,$limit);
	while($row=DBfetch($result)){
		$alerts[] = $row;
		$clock[] = $row['clock'];
	}

	$last_clock = !empty($clock)?min($clock):null;

	$sortfield = getPageSortField('clock');
	$sortorder = getPageSortOrder();

	order_result($alerts, $sortfield, $sortorder);

	foreach($alerts as $num => $row){
		$time=zbx_date2str(S_HISTORY_OF_ACTIONS_DATE_FORMAT,$row['clock']);

		if($row['status'] == ALERT_STATUS_SENT){
			$status=new CSpan(S_SENT,'green');
			$retries=new CSpan(SPACE,'green');
		}
		else if($row['status'] == ALERT_STATUS_NOT_SENT){
			$status=new CSpan(S_IN_PROGRESS,'orange');
			$retries=new CSpan(ALERT_MAX_RETRIES - $row['retries'],'orange');
		}
		else{
			$status=new CSpan(S_NOT_SENT,'red');
			$retries=new CSpan(0,'red');
		}
		$sendto=$row['sendto'];

		$message = array(bold(S_SUBJECT.': '),br(),$row['subject'],br(),br(),bold(S_MESSAGE.': '),br(),$row['message']);

		if(empty($row['error'])){
			$error=new CSpan(SPACE,'off');
		}
		else{
			$error=new CSpan($row['error'],'on');
		}

		$table->addRow(array(
			get_node_name_by_elid($row['alertid']),
			new CCol($time, 'top'),
			new CCol($row['description'], 'top'),
			new CCol($status, 'top'),
			new CCol($retries, 'top'),
			new CCol($sendto, 'top'),
			new CCol($message, 'top'),
			new CCol($error, 'wraptext top')));
	}
return $table;
}

// Author: Aly
function get_action_msgs_for_event($event){

	$table = new CTableInfo(S_NO_ACTIONS_FOUND);
	$table->setHeader(array(
		is_show_all_nodes() ? S_NODES:null,
		S_TIME,
		S_TYPE,
		S_STATUS,
		S_RETRIES_LEFT,
		S_RECIPIENTS,
		S_MESSAGE,
		S_ERROR
	));


	$alerts = $event['alerts'];
	foreach($alerts as $alertid => $alert){
		if($alert['alerttype'] != ALERT_TYPE_MESSAGE) continue;

// mediatypes
		$mediatype = array_pop($alert['mediatypes']);

		$time=zbx_date2str(S_EVENT_ACTION_MESSAGES_DATE_FORMAT,$alert["clock"]);
		if($alert['esc_step'] > 0){
			$time = array(bold(S_STEP.': '),$alert["esc_step"],br(),bold(S_TIME.': '),br(),$time);
		}

		if($alert["status"] == ALERT_STATUS_SENT){
			$status=new CSpan(S_SENT,"green");
			$retries=new CSpan(SPACE,"green");
		}
		else if($alert["status"] == ALERT_STATUS_NOT_SENT){
			$status=new CSpan(S_IN_PROGRESS,"orange");
			$retries=new CSpan(ALERT_MAX_RETRIES - $alert["retries"],"orange");
		}
		else{
			$status=new CSpan(S_NOT_SENT,"red");
			$retries=new CSpan(0,"red");
		}
		$sendto=$alert["sendto"];

		$message = array(bold(S_SUBJECT.':'),br(),$alert["subject"],br(),br(),bold(S_MESSAGE.':'));
		array_push($message, BR(), zbx_nl2br($alert['message']));

		if(empty($alert["error"])){
			$error=new CSpan(SPACE,"off");
		}
		else{
			$error=new CSpan($alert["error"],"on");
		}

		$table->addRow(array(
			get_node_name_by_elid($alert['alertid']),
			new CCol($time, 'top'),
			new CCol((!empty($mediatype['description']) ? $mediatype['description'] : ''), 'top'),
			new CCol($status, 'top'),
			new CCol($retries, 'top'),
			new CCol($sendto, 'top'),
			new CCol($message, 'wraptext top'),
			new CCol($error, 'wraptext top')));
	}

return $table;
}

// Author: Aly
function get_action_cmds_for_event($event){

	$table = new CTableInfo(S_NO_ACTIONS_FOUND);
	$table->setHeader(array(
		is_show_all_nodes()?S_NODES:null,
		S_TIME,
		S_STATUS,
		S_COMMAND,
		S_ERROR
	));

	$alerts = $event['alerts'];
	foreach($alerts as $alertid => $alert){
		if($alert['alerttype'] != ALERT_TYPE_COMMAND) continue;

		$time = zbx_date2str(S_EVENT_ACTION_CMDS_DATE_FORMAT, $alert['clock']);
		if($alert['esc_step'] > 0){
			$time = array(bold(S_STEP.': '), $alert['esc_step'], br(), bold(S_TIME.': '), br(), $time);
		}

		switch($alert['status']){
			case ALERT_STATUS_SENT:
				$status = new CSpan(S_EXECUTED, 'green');
			break;
			case ALERT_STATUS_NOT_SENT:
				$status = new CSpan(S_IN_PROGRESS, 'orange');
			break;
			default:
				$status = new CSpan(S_NOT_SENT, 'red');
			break;
		}

		$message = array(bold(S_COMMAND.':'));
		array_push($message, BR(), zbx_nl2br($alert['message']));

		$error = empty($alert['error']) ? new CSpan(SPACE, 'off') : new CSpan($alert['error'], 'on');

		$table->addRow(array(
			get_node_name_by_elid($alert['alertid']),
			new CCol($time, 'top'),
			new CCol($status, 'top'),
			new CCol($message, 'wraptext top'),
			new CCol($error, 'wraptext top')
		));
	}

return $table;
}

// Author: Aly
function get_actions_hint_by_eventid($eventid,$status=NULL){
	$hostids = array();
	$sql = 'SELECT DISTINCT i.hostid '.
			' FROM events e, functions f, items i '.
			' WHERE e.eventid='.$eventid.
				' AND e.object='.EVENT_SOURCE_TRIGGERS.
				' AND f.triggerid=e.objectid '.
				' AND i.itemid=f.itemid';
	if($host = DBfetch(DBselect($sql,1))){
		$hostids[$host['hostid']] = $host['hostid'];
	}
	$available_triggers = get_accessible_triggers(PERM_READ_ONLY, $hostids);

	$tab_hint = new CTableInfo(S_NO_ACTIONS_FOUND);
	$tab_hint->setAttribute('style', 'width: 300px;');
	$tab_hint->SetHeader(array(
			is_show_all_nodes() ? S_NODES : null,
			S_USER,
			S_DETAILS,
			S_STATUS
			));

	$sql = 'SELECT DISTINCT a.alertid,mt.description,u.alias,a.subject,a.message,a.sendto,a.status,a.retries,a.alerttype '.
			' FROM events e,alerts a '.
				' LEFT JOIN users u ON u.userid=a.userid '.
				' LEFT JOIN media_type mt ON mt.mediatypeid=a.mediatypeid'.
			' WHERE a.eventid='.$eventid.
				(is_null($status)?'':' AND a.status='.$status).
				' AND e.eventid = a.eventid'.
				' AND a.alerttype IN ('.ALERT_TYPE_MESSAGE.','.ALERT_TYPE_COMMAND.')'.
				' AND '.DBcondition('e.objectid',$available_triggers).
				' AND '.DBin_node('a.alertid').
			' ORDER BY a.alertid';
	$result=DBselect($sql,30);

	while($row=DBfetch($result)){

		if($row["status"] == ALERT_STATUS_SENT){
			$status=new CSpan(S_SENT,"green");
		}
		else if($row["status"] == ALERT_STATUS_NOT_SENT){
			$status=new CSpan(S_IN_PROGRESS,"orange");
		}
		else{
			$status=new CSpan(S_NOT_SENT,"red");
		}

		switch($row['alerttype']){
			case ALERT_TYPE_MESSAGE:
				$message = empty($row['description'])?'-':$row['description'];
				break;
			case ALERT_TYPE_COMMAND:
				$message = array(bold(S_COMMAND.':'));
				$msg = explode("\n",$row['message']);
				foreach($msg as $m){
					array_push($message, BR(), $m);
				}
				break;
			default:
				$message = '-';
		}

		$tab_hint->addRow(array(
			get_node_name_by_elid($row['alertid']),
			empty($row['alias'])?' - ':$row['alias'],
			$message,
			$status
		));
	}

return $tab_hint;
}

function get_event_actions_status($eventid){
// Actions
	$actions= new CTable(' - ');

	$sql='SELECT COUNT(a.alertid) as cnt_all'.
			' FROM alerts a '.
			' WHERE a.eventid='.$eventid.
				' AND a.alerttype in ('.ALERT_TYPE_MESSAGE.','.ALERT_TYPE_COMMAND.')';

	$alerts=DBfetch(DBselect($sql));

	if(isset($alerts['cnt_all']) && ($alerts['cnt_all'] > 0)){
		$mixed = 0;
// Sent
		$sql='SELECT COUNT(a.alertid) as sent '.
				' FROM alerts a '.
				' WHERE a.eventid='.$eventid.
					' AND a.alerttype in ('.ALERT_TYPE_MESSAGE.','.ALERT_TYPE_COMMAND.')'.
					' AND a.status='.ALERT_STATUS_SENT;

		$tmp=DBfetch(DBselect($sql));
		$alerts['sent'] = $tmp['sent'];
		$mixed+=($alerts['sent'])?ALERT_STATUS_SENT:0;
// In progress
		$sql='SELECT COUNT(a.alertid) as inprogress '.
				' FROM alerts a '.
				' WHERE a.eventid='.$eventid.
					' AND a.alerttype in ('.ALERT_TYPE_MESSAGE.','.ALERT_TYPE_COMMAND.')'.
					' AND a.status='.ALERT_STATUS_NOT_SENT;

		$tmp=DBfetch(DBselect($sql));
		$alerts['inprogress'] = $tmp['inprogress'];
// Failed
		$sql='SELECT COUNT(a.alertid) as failed '.
				' FROM alerts a '.
				' WHERE a.eventid='.$eventid.
					' AND a.alerttype in ('.ALERT_TYPE_MESSAGE.','.ALERT_TYPE_COMMAND.')'.
					' AND a.status='.ALERT_STATUS_FAILED;

		$tmp=DBfetch(DBselect($sql));
		$alerts['failed'] = $tmp['failed'];
		$mixed+=($alerts['failed'])?ALERT_STATUS_FAILED:0;

		if($alerts['inprogress']){
			$status = new CSpan(S_IN_PROGRESS,'orange');
		}
		else if(ALERT_STATUS_SENT == $mixed){
			$status = new CSpan(S_OK,'green');
		}
		else if(ALERT_STATUS_FAILED == $mixed){
			$status = new CSpan(S_FAILED,'red');
		}
		else{
			$tdl = new CCol(($alerts['sent'])?(new CSpan($alerts['sent'],'green')):SPACE);
			$tdl->setAttribute('width','10');

			$tdr = new CCol(($alerts['failed'])?(new CSpan($alerts['failed'],'red')):SPACE);
			$tdr->setAttribute('width','10');

			$status = new CRow(array($tdl,$tdr));
		}

		$actions->addRow($status);
	}

return $actions;
}

function get_event_actions_stat_hints($eventid){
	$actions= new CTable(' - ');

	$sql='SELECT COUNT(a.alertid) as cnt '.
			' FROM alerts a '.
			' WHERE a.eventid='.$eventid.
				' AND a.alerttype in ('.ALERT_TYPE_MESSAGE.','.ALERT_TYPE_COMMAND.')';


	$alerts=DBfetch(DBselect($sql));

	if(isset($alerts['cnt']) && ($alerts['cnt'] > 0)){
		$sql='SELECT COUNT(a.alertid) as sent '.
				' FROM alerts a '.
				' WHERE a.eventid='.$eventid.
					' AND a.alerttype in ('.ALERT_TYPE_MESSAGE.','.ALERT_TYPE_COMMAND.')'.
					' AND a.status='.ALERT_STATUS_SENT;

		$alerts=DBfetch(DBselect($sql));

		$alert_cnt = new CSpan($alerts['sent'],'green');
		if($alerts['sent']){
			$hint=get_actions_hint_by_eventid($eventid,ALERT_STATUS_SENT);
			$alert_cnt->SetHint($hint);
		}
		$tdl = new CCol(($alerts['sent'])?$alert_cnt:SPACE);
		$tdl->setAttribute('width','10');

		$sql='SELECT COUNT(a.alertid) as inprogress '.
				' FROM alerts a '.
				' WHERE a.eventid='.$eventid.
					' AND a.alerttype in ('.ALERT_TYPE_MESSAGE.','.ALERT_TYPE_COMMAND.')'.
					' AND a.status='.ALERT_STATUS_NOT_SENT;

		$alerts=DBfetch(DBselect($sql));

		$alert_cnt = new CSpan($alerts['inprogress'],'orange');
		if($alerts['inprogress']){
			$hint=get_actions_hint_by_eventid($eventid,ALERT_STATUS_NOT_SENT);
			$alert_cnt->setHint($hint);
		}
		$tdc = new CCol(($alerts['inprogress'])?$alert_cnt:SPACE);
		$tdc->setAttribute('width','10');

		$sql='SELECT COUNT(a.alertid) as failed '.
				' FROM alerts a '.
				' WHERE a.eventid='.$eventid.
					' AND a.alerttype in ('.ALERT_TYPE_MESSAGE.','.ALERT_TYPE_COMMAND.')'.
					' AND a.status='.ALERT_STATUS_FAILED;

		$alerts=DBfetch(DBselect($sql));

		$alert_cnt = new CSpan($alerts['failed'],'red');
		if($alerts['failed']){
			$hint=get_actions_hint_by_eventid($eventid,ALERT_STATUS_FAILED);
			$alert_cnt->setHint($hint);
		}

		$tdr = new CCol(($alerts['failed'])?$alert_cnt:SPACE);
		$tdr->setAttribute('width','10');

		$actions->addRow(array($tdl,$tdc,$tdr));
	}
return $actions;
}

?>
