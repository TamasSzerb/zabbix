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
require_once('include/config.inc.php');
require_once('include/hosts.inc.php');
require_once('include/items.inc.php');
require_once('include/forms.inc.php');

$page['title'] = 'S_CONFIGURATION_OF_DISCOVERY';
$page['file'] = 'host_discovery.php';
$page['scripts'] = array('class.cviewswitcher.js');
$page['hist_arg'] = array('hostid');

include_once('include/page_header.php');
?>
<?php
// needed type to know which field name to use
$itemType = get_request('type', 0);
switch($itemType) {
	case ITEM_TYPE_SSH: case ITEM_TYPE_TELNET: case ITEM_TYPE_JMX: $paramsFieldName = S_EXECUTED_SCRIPT; break;
	case ITEM_TYPE_DB_MONITOR: $paramsFieldName = S_PARAMS; break;
	case ITEM_TYPE_CALCULATED: $paramsFieldName = S_FORMULA; break;
	default: $paramsFieldName = 'params';
}
//		VAR			TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION
	$fields=array(
		'hostid'=>			array(T_ZBX_INT, O_OPT,  P_SYS,	DB_ID,			'!isset({form})'),
		'itemid'=>			array(T_ZBX_INT, O_NO,	 P_SYS,	DB_ID,			'(isset({form})&&({form}=="update"))'),
		'interfaceid'=>		array(T_ZBX_INT, O_OPT,  P_SYS,	DB_ID,	null, S_INTERFACE),

		'name'=>		array(T_ZBX_STR, O_OPT,  null,	NOT_EMPTY,		'isset({save})'),
		'description'=>		array(T_ZBX_STR, O_OPT,  null,	null,		'isset({save})'),
		'item_filter_macro'=>		array(T_ZBX_STR, O_OPT,  null,	null,		'isset({save})'),
		'item_filter_value'=>		array(T_ZBX_STR, O_OPT,  null,	null,		'isset({save})'),
		'key'=>				array(T_ZBX_STR, O_OPT,  null,  NOT_EMPTY,		'isset({save})'),
		'delay'=>			array(T_ZBX_INT, O_OPT,  null,  '(('.BETWEEN(1,86400).
				'(!isset({delay_flex}) || !({delay_flex}) || is_array({delay_flex}) && !count({delay_flex}))) ||'.
				'('.BETWEEN(0,86400).'isset({delay_flex})&&is_array({delay_flex})&&count({delay_flex})>0))&&',
				'isset({save})&&(isset({type})&&({type}!='.ITEM_TYPE_TRAPPER.'))'),
		'new_delay_flex'=>		array(T_ZBX_STR, O_OPT,  NOT_EMPTY,  '',	'isset({add_delay_flex})&&(isset({type})&&({type}!=2))'),
		'rem_delay_flex'=>	array(T_ZBX_INT, O_OPT,  null,  BETWEEN(0,86400),null),
		'delay_flex'=>		array(T_ZBX_STR, O_OPT,  null,  '',null),
		'status'=>			array(T_ZBX_INT, O_OPT,  null,  BETWEEN(0,65535),'isset({save})'),
		'type'=>			array(T_ZBX_INT, O_OPT,  null,
				IN(array(-1,ITEM_TYPE_ZABBIX,ITEM_TYPE_SNMPV1,ITEM_TYPE_TRAPPER,ITEM_TYPE_SIMPLE,
					ITEM_TYPE_SNMPV2C,ITEM_TYPE_INTERNAL,ITEM_TYPE_SNMPV3,ITEM_TYPE_ZABBIX_ACTIVE,
					ITEM_TYPE_AGGREGATE,ITEM_TYPE_EXTERNAL,ITEM_TYPE_DB_MONITOR,
					ITEM_TYPE_IPMI,ITEM_TYPE_SSH,ITEM_TYPE_TELNET,ITEM_TYPE_JMX,ITEM_TYPE_CALCULATED,ITEM_TYPE_SNMPTRAP)),'isset({save})'),
		'authtype'=>		array(T_ZBX_INT, O_OPT,  NULL,	IN(ITEM_AUTHTYPE_PASSWORD.','.ITEM_AUTHTYPE_PUBLICKEY),
											'isset({save})&&isset({type})&&({type}=='.ITEM_TYPE_SSH.')'),
		'username'=>		array(T_ZBX_STR, O_OPT,  NULL,	NULL,		'isset({save})&&isset({type})&&'.IN(
												ITEM_TYPE_SSH.','.
												ITEM_TYPE_JMX.','.
												ITEM_TYPE_TELNET, 'type')),
		'password'=>		array(T_ZBX_STR, O_OPT,  NULL,	NULL,		'isset({save})&&isset({type})&&'.IN(
												ITEM_TYPE_SSH.','.
												ITEM_TYPE_JMX.','.
												ITEM_TYPE_TELNET, 'type')),
		'publickey'=>		array(T_ZBX_STR, O_OPT,  NULL,	NULL,		'isset({save})&&isset({type})&&({type})=='.ITEM_TYPE_SSH.'&&({authtype})=='.ITEM_AUTHTYPE_PUBLICKEY),
		'privatekey'=>		array(T_ZBX_STR, O_OPT,  NULL,	NULL,		'isset({save})&&isset({type})&&({type})=='.ITEM_TYPE_SSH.'&&({authtype})=='.ITEM_AUTHTYPE_PUBLICKEY),
		'params'=>		array(T_ZBX_STR, O_OPT,  NULL,	NOT_EMPTY,	'isset({save})&&isset({type})&&'.IN(
												ITEM_TYPE_SSH.','.
												ITEM_TYPE_DB_MONITOR.','.
												ITEM_TYPE_TELNET.','.
												ITEM_TYPE_CALCULATED,'type'), $paramsFieldName),
//hidden fields for better gui
		'params_script'=>	array(T_ZBX_STR, O_OPT, NULL, NULL, NULL),
		'params_dbmonitor'=>	array(T_ZBX_STR, O_OPT, NULL, NULL, NULL),
		'params_calculted'=>	array(T_ZBX_STR, O_OPT, NULL, NULL, NULL),

		'snmp_community'=>	array(T_ZBX_STR, O_OPT,  null,  NOT_EMPTY,		'isset({save})&&isset({type})&&'.IN(
													ITEM_TYPE_SNMPV1.','.
													ITEM_TYPE_SNMPV2C,'type')),
		'snmp_oid'=>		array(T_ZBX_STR, O_OPT,  null,  NOT_EMPTY,		'isset({save})&&isset({type})&&'.IN(
													ITEM_TYPE_SNMPV1.','.
													ITEM_TYPE_SNMPV2C.','.
													ITEM_TYPE_SNMPV3,'type')),
		'port'=>		array(T_ZBX_INT, O_OPT,  null,  BETWEEN(0,65535),	'isset({save})&&isset({type})&&'.IN(
													ITEM_TYPE_SNMPV1.','.
													ITEM_TYPE_SNMPV2C.','.
													ITEM_TYPE_SNMPV3,'type')),
		'snmpv3_securitylevel'=>array(T_ZBX_INT, O_OPT,  null,  IN('0,1,2'),	'isset({save})&&(isset({type})&&({type}=='.ITEM_TYPE_SNMPV3.'))'),
		'snmpv3_securityname'=>	array(T_ZBX_STR, O_OPT,  null,  null,		'isset({save})&&(isset({type})&&({type}=='.ITEM_TYPE_SNMPV3.'))'),
		'snmpv3_authpassphrase'=>array(T_ZBX_STR, O_OPT,  null,  null,		'isset({save})&&(isset({type})&&({type}=='.ITEM_TYPE_SNMPV3.')&&({snmpv3_securitylevel}=='.ITEM_SNMPV3_SECURITYLEVEL_AUTHPRIV.'||{snmpv3_securitylevel}=='.ITEM_SNMPV3_SECURITYLEVEL_AUTHNOPRIV.'))'),
		'snmpv3_privpassphrase'=>array(T_ZBX_STR, O_OPT,  null,  null,		'isset({save})&&(isset({type})&&({type}=='.ITEM_TYPE_SNMPV3.')&&({snmpv3_securitylevel}=='.ITEM_SNMPV3_SECURITYLEVEL_AUTHPRIV.'))'),

		'ipmi_sensor'=>		array(T_ZBX_STR, O_OPT,  null,  NOT_EMPTY,	'isset({save})&&(isset({type})&&({type}=='.ITEM_TYPE_IPMI.'))', S_IPMI_SENSOR),
		'trapper_hosts'=>	array(T_ZBX_STR, O_OPT,  null,  null,			'isset({save})&&isset({type})&&({type}==2)'),

		'add_delay_flex'=>	array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	null,	null),
		'del_delay_flex'=>	array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	null,	null),
// Actions
		'go'=>				array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, NULL, NULL),
		'group_itemid'=>	array(T_ZBX_INT, O_OPT,	null,	DB_ID, null),
// form
		'save'=>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	null,	null),
		'clone'=>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	null,	null),
		'update'=>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	null,	null),
		'delete'=>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	null,	null),
		'cancel'=>			array(T_ZBX_STR, O_OPT, P_SYS,	null,	null),
		'form'=>			array(T_ZBX_STR, O_OPT, P_SYS,	null,	null),
		'form_refresh'=>	array(T_ZBX_INT, O_OPT,	null,	null,	null),
//ajax
		'favobj'=>		array(T_ZBX_STR, O_OPT, P_ACT,	NULL,			NULL),
		'favref'=>		array(T_ZBX_STR, O_OPT, P_ACT,  NOT_EMPTY,		'isset({favobj})'),
		'state'=>		array(T_ZBX_INT, O_OPT, P_ACT,  NOT_EMPTY,		'isset({favobj}) && ("filter"=={favobj})'),

		'item_filter' => array(T_ZBX_STR, O_OPT, P_SYS,	null,	null),
	);

	check_fields($fields);
	validate_sort_and_sortorder('name', ZBX_SORT_UP);

	$_REQUEST['go'] = get_request('go', 'none');

// PERMISSIONS
	if(get_request('itemid', false)){
		$options = array(
			'itemids' => $_REQUEST['itemid'],
			'output' => API_OUTPUT_EXTEND,
			'editable' => 1
		);
		$item = API::DiscoveryRule()->get($options);
		$item = reset($item);
		if(!$item) access_deny();
		$_REQUEST['hostid'] = $item['hostid'];
	}
	else if(get_request('hostid', 0) > 0){
		$options = array(
			'hostids' => $_REQUEST['hostid'],
			'output' => API_OUTPUT_EXTEND,
			'templated_hosts' => 1,
			'editable' => 1
		);
		$hosts = API::Host()->get($options);
		if(empty($hosts)) access_deny();
	}
?>
<?php
/* AJAX */
	if(isset($_REQUEST['favobj'])){
		if('filter' == $_REQUEST['favobj']){
			CProfile::update('web.host_discovery.filter.state',$_REQUEST['state'], PROFILE_TYPE_INT);
		}
	}

	if((PAGE_TYPE_JS == $page['type']) || (PAGE_TYPE_HTML_BLOCK == $page['type'])){
		include_once('include/page_footer.php');
		exit();
	}
//--------

?>
<?php
	if(isset($_REQUEST['del_delay_flex']) && isset($_REQUEST['rem_delay_flex'])){
		$_REQUEST['delay_flex'] = get_request('delay_flex',array());
		foreach($_REQUEST['rem_delay_flex'] as $val){
			unset($_REQUEST['delay_flex'][$val]);
		}
	}
	else if(isset($_REQUEST['add_delay_flex'])&&isset($_REQUEST['new_delay_flex'])){
		$_REQUEST['delay_flex'] = get_request('delay_flex', array());
		array_push($_REQUEST['delay_flex'],$_REQUEST['new_delay_flex']);
	}
	else if(isset($_REQUEST['delete'])&&isset($_REQUEST['itemid'])){
		$result = API::DiscoveryRule()->delete($_REQUEST['itemid']);
		show_messages($result, _('Discovery rule deleted'), _('Cannot delete discovery rule'));

		unset($_REQUEST['itemid']);
		unset($_REQUEST['form']);
	}
	else if(isset($_REQUEST['clone']) && isset($_REQUEST['itemid'])){
		unset($_REQUEST['itemid']);
		$_REQUEST['form'] = 'clone';
	}
	else if(isset($_REQUEST['save'])){
		$delay_flex = get_request('delay_flex', array());

		$db_delay_flex = '';
		foreach($delay_flex as $num => $val){
			$db_delay_flex .= $val['delay'].'/'.$val['period'].';';
		}
		$db_delay_flex = trim($db_delay_flex,';');

		$ifm = get_request('item_filter_macro');
		$ifv = get_request('item_filter_value');
		$filter = isset($ifm, $ifv) ? $ifm.':'.$ifv : '';

		$item = array(
			'interfaceid' => get_request('interfaceid'),
			'name' => get_request('name'),
			'description' => get_request('description'),
			'key_' => get_request('key'),
			'hostid' => get_request('hostid'),
			'delay' => get_request('delay'),
			'status' => get_request('status'),
			'type' => get_request('type'),
			'snmp_community' => get_request('snmp_community'),
			'snmp_oid' => get_request('snmp_oid'),
			'port' => get_request('port'),
			'snmpv3_securityname' => get_request('snmpv3_securityname'),
			'snmpv3_securitylevel' => get_request('snmpv3_securitylevel'),
			'snmpv3_authpassphrase' => get_request('snmpv3_authpassphrase'),
			'snmpv3_privpassphrase' => get_request('snmpv3_privpassphrase'),
			'delay_flex' => $db_delay_flex,
			'authtype' => get_request('authtype'),
			'username' => get_request('username'),
			'password' => get_request('password'),
			'publickey' => get_request('publickey'),
			'privatekey' => get_request('privatekey'),
			'params' => get_request('params'),
			'ipmi_sensor' => get_request('ipmi_sensor'),
			'filter' => $filter,
		);

		if(isset($_REQUEST['itemid'])){
			DBstart();

			$db_item = get_item_by_itemid_limited($_REQUEST['itemid']);
			foreach($item as $field => $value){
				if($item[$field] == $db_item[$field]) unset($item[$field]);
			}

			$item['itemid'] = $_REQUEST['itemid'];

			$result = API::DiscoveryRule()->update($item);
			$result = DBend($result);
			show_messages($result, _('Discovery rule updated'), _('Cannot update discovery rule'));
		}
		else{
			$result = API::DiscoveryRule()->create(array($item));
			show_messages($result, _('Discovery rule created'), _('Cannot add discovery rule'));
		}

		if($result){
			unset($_REQUEST['itemid']);
			unset($_REQUEST['form']);
		}
	}

// ----- GO -----
	else if((($_REQUEST['go'] == 'activate') || ($_REQUEST['go'] == 'disable')) && isset($_REQUEST['group_itemid'])){
		$group_itemid = $_REQUEST['group_itemid'];

		DBstart();
		$go_result = ($_REQUEST['go'] == 'activate') ? activate_item($group_itemid) : disable_item($group_itemid);
		$go_result = DBend($go_result);
		show_messages($go_result, ($_REQUEST['go'] == 'activate') ? _('Discovery rules activated') : _('Discovery rules disabled'), null);
	}
	else if(($_REQUEST['go'] == 'delete') && isset($_REQUEST['group_itemid'])){
		$go_result = API::DiscoveryRule()->delete($_REQUEST['group_itemid']);
		show_messages($go_result, _('Discovery rule deleted'), _('Cannot delete discovery rule'));
	}

	if(($_REQUEST['go'] != 'none') && isset($go_result) && $go_result){
		$url = new CUrl();
		$path = $url->getPath();
		insert_js('cookie.eraseArray("'.$path.'")');
	}
?>
<?php
	$items_wdgt = new CWidget();

	$form = null;
	if(!isset($_REQUEST['form'])){
		$form = new CForm('get');
		$form->addVar('hostid', $_REQUEST['hostid']);
		$form->addItem(new CSubmit('form', S_CREATE_RULE));
	}

	$items_wdgt->addPageHeader(S_CONFIGURATION_OF_DISCOVERY_RULES_BIG, $form);


	if(isset($_REQUEST['form'])){
		$frmItem = new CFormTable();
		$frmItem->setName('items');
		$frmItem->setTitle(S_RULE);
		$frmItem->setAttribute('style', 'visibility: hidden;');

		$hostid = get_request('hostid');
		$frmItem->addVar('hostid', $hostid);

		$limited = false;


		$name = get_request('name', '');
		$description = get_request('description', '');
		$key = get_request('key', '');
		$delay = get_request('delay', 30);
		$status = get_request('status', 0);
		$type = get_request('type', 0);
		$snmp_community = get_request('snmp_community', 'public');
		$snmp_oid = get_request('snmp_oid', 'interfaces.ifTable.ifEntry.ifInOctets.1');
		$port = get_request('port', 161);
		$params = get_request('params', '');
		$delay_flex = get_request('delay_flex', array());
		$trapper_hosts = get_request('trapper_hosts', '');
		$item_filter = get_request('filter', '');

		$snmpv3_securityname = get_request('snmpv3_securityname', '');
		$snmpv3_securitylevel = get_request('snmpv3_securitylevel', 0);
		$snmpv3_authpassphrase = get_request('snmpv3_authpassphrase', '');
		$snmpv3_privpassphrase = get_request('snmpv3_privpassphrase', '');
		$ipmi_sensor = get_request('ipmi_sensor', '');
		$authtype = get_request('authtype', 0);
		$username = get_request('username', '');
		$password = get_request('password', '');
		$publickey = get_request('publickey', '');
		$privatekey = get_request('privatekey', '');

		$interfaceid = get_request('interfaceid', 0);

		$formula = get_request('formula', '1');
		$logtimefmt = get_request('logtimefmt', '');

		if(isset($_REQUEST['itemid'])){
			$frmItem->addVar('itemid', $_REQUEST['itemid']);

			$options = array(
				'hostids' => $hostid,
				'itemids' => $_REQUEST['itemid'],
				'filter' => array('flags' => ZBX_FLAG_DISCOVERY),
				'output' => API_OUTPUT_EXTEND,
				'editable' => 1,
			);
			$item_data = API::Item()->get($options);
			$item_data = reset($item_data);

			$limited = ($item_data['templateid'] != 0);
		}

		if((isset($_REQUEST['itemid']) && !isset($_REQUEST['form_refresh']))){
			$interfaceid	= $item_data['interfaceid'];

			$name = $item_data['name'];
			$description = $item_data['description'];
			$key = $item_data['key_'];
			$type = $item_data['type'];
			$snmp_community = $item_data['snmp_community'];
			$snmp_oid = $item_data['snmp_oid'];
			$port = $item_data['port'];
			$params = $item_data['params'];
			$item_filter = $item_data['filter'];

			$snmpv3_securityname = $item_data['snmpv3_securityname'];
			$snmpv3_securitylevel = $item_data['snmpv3_securitylevel'];
			$snmpv3_authpassphrase = $item_data['snmpv3_authpassphrase'];
			$snmpv3_privpassphrase = $item_data['snmpv3_privpassphrase'];

			$ipmi_sensor = $item_data['ipmi_sensor'];
			$trapper_hosts = $item_data['trapper_hosts'];

			$authtype = $item_data['authtype'];
			$username = $item_data['username'];
			$password = $item_data['password'];
			$publickey = $item_data['publickey'];
			$privatekey = $item_data['privatekey'];

			$formula = $item_data['formula'];
			$logtimefmt = $item_data['logtimefmt'];


			if(!isset($limited) || !isset($_REQUEST['form_refresh'])){
				$delay = $item_data['delay'];
				$status = $item_data['status'];
				$db_delay_flex = $item_data['delay_flex'];

				if(isset($db_delay_flex)){
					$arr_of_dellays = explode(';',$db_delay_flex);
					foreach($arr_of_dellays as $one_db_delay){
						$arr_of_delay = explode('/',$one_db_delay);
						if(!isset($arr_of_delay[0]) || !isset($arr_of_delay[1])) continue;

						array_push($delay_flex, array('delay'=> $arr_of_delay[0], 'period'=> $arr_of_delay[1]));
					}
				}
			}
		}

		$authTypeVisibility = array();
		$typeVisibility = array();
		$delay_flex_el = array();

		$types = item_type2str();
		unset($types[ITEM_TYPE_HTTPTEST]);

		$type_keys = array_keys($types);

		$i = 0;
		foreach($delay_flex as $val){
			if(!isset($val['delay']) && !isset($val['period'])) continue;

			array_push($delay_flex_el,
				array(
					new CCheckBox('rem_delay_flex['.$i.']', 'no', null,$i),
					$val['delay'],
					' sec at ',
					$val['period']),
				BR());
			$frmItem->addVar('delay_flex['.$i.'][delay]', $val['delay']);
			$frmItem->addVar('delay_flex['.$i.'][period]', $val['period']);
			foreach($type_keys as $it) {
				if($it == ITEM_TYPE_TRAPPER || $it == ITEM_TYPE_ZABBIX_ACTIVE) continue;
				zbx_subarray_push($typeVisibility, $it, 'delay_flex['.$i.'][delay]');
				zbx_subarray_push($typeVisibility, $it, 'delay_flex['.$i.'][period]');
				zbx_subarray_push($typeVisibility, $it, 'rem_delay_flex['.$i.']');
			}
			$i++;
// limit count of intervals.  7 intervals by 30 symbols = 210 characters, db storage field is 256
			if($i >= 7) break;
		}

		array_push($delay_flex_el, count($delay_flex_el)==0 ? S_NO_FLEXIBLE_INTERVALS : new CSubmit('del_delay_flex',S_DELETE_SELECTED));


// Interfaces
		$interfaces = API::HostInterface()->get(array(
			'hostids' => $hostid,
			'output' => API_OUTPUT_EXTEND,
		));
		if(!empty($interfaces)){
			$sbIntereaces = new CComboBox('interfaceid', $interfaceid);
			foreach($interfaces as $ifnum => $interface){
				$caption = $interface['useip'] ? $interface['ip'] : $interface['dns'];
				$caption.= ' : '.$interface['port'];

				$sbIntereaces->addItem($interface['interfaceid'], $caption);
			}
			$frmItem->addRow(S_HOST_INTERFACE, $sbIntereaces, null, 'interface_row');
			zbx_subarray_push($typeVisibility, ITEM_TYPE_ZABBIX, 'interface_row');
			zbx_subarray_push($typeVisibility, ITEM_TYPE_ZABBIX, 'interfaceid');
			zbx_subarray_push($typeVisibility, ITEM_TYPE_SIMPLE, 'interface_row');
			zbx_subarray_push($typeVisibility, ITEM_TYPE_SIMPLE, 'interfaceid');
			zbx_subarray_push($typeVisibility, ITEM_TYPE_SNMPV1, 'interface_row');
			zbx_subarray_push($typeVisibility, ITEM_TYPE_SNMPV1, 'interfaceid');
			zbx_subarray_push($typeVisibility, ITEM_TYPE_SNMPV2C, 'interface_row');
			zbx_subarray_push($typeVisibility, ITEM_TYPE_SNMPV2C, 'interfaceid');
			zbx_subarray_push($typeVisibility, ITEM_TYPE_SNMPV3, 'interface_row');
			zbx_subarray_push($typeVisibility, ITEM_TYPE_SNMPV3, 'interfaceid');
			zbx_subarray_push($typeVisibility, ITEM_TYPE_EXTERNAL, 'interface_row');
			zbx_subarray_push($typeVisibility, ITEM_TYPE_EXTERNAL, 'interfaceid');
			zbx_subarray_push($typeVisibility, ITEM_TYPE_IPMI, 'interface_row');
			zbx_subarray_push($typeVisibility, ITEM_TYPE_IPMI, 'interfaceid');
			zbx_subarray_push($typeVisibility, ITEM_TYPE_SSH, 'interface_row');
			zbx_subarray_push($typeVisibility, ITEM_TYPE_SSH, 'interfaceid');
			zbx_subarray_push($typeVisibility, ITEM_TYPE_TELNET, 'interface_row');
			zbx_subarray_push($typeVisibility, ITEM_TYPE_TELNET, 'interfaceid');
			zbx_subarray_push($typeVisibility, ITEM_TYPE_JMX, 'interface_row');
			zbx_subarray_push($typeVisibility, ITEM_TYPE_JMX, 'interfaceid');
			zbx_subarray_push($typeVisibility, ITEM_TYPE_SNMPTRAP, 'interface_row');
			zbx_subarray_push($typeVisibility, ITEM_TYPE_SNMPTRAP, 'interfaceid');
		}

// Name
		$frmItem->addRow(_('Name'), new CTextBox('name', $name, 40, $limited));

// Type
		if($limited){
			$cmbType = new CTextBox('typename', item_type2str($type), 40, 'yes');
			$frmItem->addVar('type', $type);
		}
		else{
			$cmbType = new CComboBox('type', $type);
			$cmbType->addItems($types);
		}
		$frmItem->addRow(S_TYPE, $cmbType);

// Key
		$frmItem->addRow(S_KEY, new CTextBox('key', $key, 40, $limited));

// SNMP OID
		$frmItem->addRow(S_SNMP_OID, new CTextBox('snmp_oid',$snmp_oid,40,$limited), null, 'row_snmp_oid');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_SNMPV1, 'snmp_oid');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_SNMPV2C, 'snmp_oid');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_SNMPV3, 'snmp_oid');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_SNMPV1, 'row_snmp_oid');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_SNMPV2C, 'row_snmp_oid');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_SNMPV3, 'row_snmp_oid');

// SNMP community
		$frmItem->addRow(S_SNMP_COMMUNITY, new CTextBox('snmp_community',$snmp_community,16), null, 'row_snmp_community');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_SNMPV1, 'snmp_community');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_SNMPV2C, 'snmp_community');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_SNMPV1, 'row_snmp_community');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_SNMPV2C, 'row_snmp_community');

// SNMPv3 security name
		$frmItem->addRow(S_SNMPV3_SECURITY_NAME, new CTextBox('snmpv3_securityname',$snmpv3_securityname,64), null, 'row_snmpv3_securityname');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_SNMPV3, 'snmpv3_securityname');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_SNMPV3, 'row_snmpv3_securityname');

// SNMPv3 security level
		$cmbSecLevel = new CComboBox('snmpv3_securitylevel', $snmpv3_securitylevel);
		$cmbSecLevel->addItem(ITEM_SNMPV3_SECURITYLEVEL_NOAUTHNOPRIV, 'noAuthNoPriv');
		$cmbSecLevel->addItem(ITEM_SNMPV3_SECURITYLEVEL_AUTHNOPRIV, 'authNoPriv');
		$cmbSecLevel->addItem(ITEM_SNMPV3_SECURITYLEVEL_AUTHPRIV, 'authPriv');
		$frmItem->addRow(S_SNMPV3_SECURITY_LEVEL, $cmbSecLevel, null, 'row_snmpv3_securitylevel');

		zbx_subarray_push($typeVisibility, ITEM_TYPE_SNMPV3, 'snmpv3_securitylevel');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_SNMPV3, 'row_snmpv3_securitylevel');

// SNMPv3 auth passphrase
		$frmItem->addRow(S_SNMPV3_AUTH_PASSPHRASE, new CTextBox('snmpv3_authpassphrase',$snmpv3_authpassphrase,64), null, 'row_snmpv3_authpassphrase');

// SNMPv3 priv passphrase
		$frmItem->addRow(S_SNMPV3_PRIV_PASSPHRASE, new CTextBox('snmpv3_privpassphrase',$snmpv3_privpassphrase,64), null, 'row_snmpv3_privpassphrase');

// SNMP port
		$frmItem->addRow(S_PORT, new CNumericBox('port',$port,5), null, 'row_port');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_SNMPV1, 'port');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_SNMPV2C, 'port');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_SNMPV3, 'port');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_SNMPV1, 'row_port');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_SNMPV2C, 'row_port');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_SNMPV3, 'row_port');

// IPMI sensor
		$frmItem->addRow(S_IPMI_SENSOR, new CTextBox('ipmi_sensor', $ipmi_sensor, 64, $limited), null, 'row_ipmi_sensor');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_IPMI, 'ipmi_sensor');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_IPMI, 'row_ipmi_sensor');

// Authentication method
		$cmbAuthType = new CComboBox('authtype', $authtype);
		$cmbAuthType->addItem(ITEM_AUTHTYPE_PASSWORD, _('Password'));
		$cmbAuthType->addItem(ITEM_AUTHTYPE_PUBLICKEY,S_PUBLIC_KEY);

		$frmItem->addRow(S_AUTHENTICATION_METHOD, $cmbAuthType, null, 'row_authtype');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_SSH, 'authtype');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_SSH, 'row_authtype');

// User name
		$frmItem->addRow(S_USER_NAME, new CTextBox('username',$username,16), null, 'row_username');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_SSH, 'username');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_SSH, 'row_username');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_TELNET, 'username');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_TELNET, 'row_username');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_JMX, 'username');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_JMX, 'row_username');

// Public key
		$frmItem->addRow(S_PUBLIC_KEY_FILE, new CTextBox('publickey',$publickey,16), null, 'row_publickey');
		zbx_subarray_push($authTypeVisibility, ITEM_AUTHTYPE_PUBLICKEY, 'publickey');
		zbx_subarray_push($authTypeVisibility, ITEM_AUTHTYPE_PUBLICKEY, 'row_publickey');

// Private key
		$frmItem->addRow(S_PRIVATE_KEY_FILE, new CTextBox('privatekey',$privatekey,16), null, 'row_privatekey');
		zbx_subarray_push($authTypeVisibility, ITEM_AUTHTYPE_PUBLICKEY, 'privatekey');
		zbx_subarray_push($authTypeVisibility, ITEM_AUTHTYPE_PUBLICKEY, 'row_privatekey');

// Password
		$frmItem->addRow(_('Password'), new CTextBox('password', $password, 16), null, 'row_password');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_SSH, 'password');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_SSH, 'row_password');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_TELNET, 'password');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_TELNET, 'row_password');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_JMX, 'password');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_JMX, 'row_password');

		$spanEC = new CSpan(S_EXECUTED_SCRIPT);
		$spanEC->setAttribute('id', 'label_executed_script');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_SSH, 'label_executed_script');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_TELNET, 'label_executed_script');

		$spanP = new CSpan(S_PARAMS);
		$spanP->setAttribute('id', 'label_params');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_DB_MONITOR, 'label_params');

		$spanF = new CSpan(S_FORMULA);
		$spanF->setAttribute('id', 'label_formula');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_CALCULATED, 'label_formula');

// Params / DBmonitor / Formula
		$params_script = new CTextArea('params', $params, 60, 4);
		$params_script->setAttribute('id', 'params_script');
		$params_dbmonitor = new CTextArea('params', $params, 60, 4);
		$params_dbmonitor->setAttribute('id', 'params_dbmonitor');
		$params_calculted = new CTextArea('params', $params, 60, 4);
		$params_calculted->setAttribute('id', 'params_calculted');

		$frmItem->addRow(array($spanEC, $spanP, $spanF), array($params_script, $params_dbmonitor, $params_calculted), null, 'row_params');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_SSH, 'params_script');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_SSH, 'row_params');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_TELNET, 'params_script');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_TELNET, 'row_params');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_DB_MONITOR, 'params_dbmonitor');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_DB_MONITOR, 'row_params');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_CALCULATED, 'params_calculted');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_CALCULATED, 'row_params');

// Filter

		$item_filter_macro = $item_filter_value = '';
		if(!empty($item_filter)){
			//Exploding filter to two parts: before first ':' and after
			$col_position = zbx_strpos($item_filter, ':');
			$item_filter_macro = zbx_substr($item_filter, 0, $col_position); //before first ':'
			$item_filter_value = zbx_substr($item_filter, $col_position+1); //after first ':'
		}

		$frmItem->addRow(S_FILTER, array(
			S_MACRO, SPACE, new CTextBox('item_filter_macro',$item_filter_macro,20),
			S_REGEXP, SPACE, new CTextBox('item_filter_value',$item_filter_value,40)
		), null);

// Update interval (in sec)
		$frmItem->addRow(_('Update interval (in sec)'), new CNumericBox('delay', $delay, 5), null, 'row_delay');
		foreach($type_keys as $it) {
			if($it == ITEM_TYPE_TRAPPER) continue;
			zbx_subarray_push($typeVisibility, $it, 'delay');
			zbx_subarray_push($typeVisibility, $it, 'row_delay');
		}

// Flexible intervals (sec)
		$frmItem->addRow(S_FLEXIBLE_INTERVALS, $delay_flex_el, null, 'row_flex_intervals');

// New flexible interval
		$frmItem->addRow(S_NEW_FLEXIBLE_INTERVAL, array(
			S_DELAY, SPACE,	new CNumericBox('new_delay_flex[delay]', '50', 5),
			S_PERIOD, SPACE, new CTextBox('new_delay_flex[period]', ZBX_DEFAULT_INTERVAL, 27),
			BR(),
			new CSubmit('add_delay_flex', S_ADD)
		), 'new', 'row_new_delay_flex');

		foreach($type_keys as $it) {
			if($it == ITEM_TYPE_TRAPPER || $it == ITEM_TYPE_ZABBIX_ACTIVE) continue;
			zbx_subarray_push($typeVisibility, $it, 'row_flex_intervals');
			zbx_subarray_push($typeVisibility, $it, 'row_new_delay_flex');
			zbx_subarray_push($typeVisibility, $it, 'new_delay_flex[delay]');
			zbx_subarray_push($typeVisibility, $it, 'new_delay_flex[period]');
			zbx_subarray_push($typeVisibility, $it, 'add_delay_flex');
		}

// Status
		$cmbStatus = new CComboBox('status', $status);
		$cmbStatus->addItems(item_status2str());
		$frmItem->addRow(S_STATUS, $cmbStatus);

// allowed hosts
		$frmItem->addRow(S_ALLOWED_HOSTS, new CTextBox('trapper_hosts',$trapper_hosts,40), null, 'row_trapper_hosts');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_TRAPPER, 'trapper_hosts');
		zbx_subarray_push($typeVisibility, ITEM_TYPE_TRAPPER, 'row_trapper_hosts');

		$frmItem->addRow(_('Description'), new CTextArea('description', $description));

		$frmRow = array(new CSubmit('save', S_SAVE));
		if(isset($_REQUEST['itemid'])){
			$frmRow[] = new CSubmit('clone', S_CLONE);
			$frmRow[] = new CButtonDelete(_('Delete selected discovery rules?'), url_param('form').url_param('groupid').url_param('itemid'));
		}
		$frmRow[] = new CButtonCancel(url_param('groupid').url_param('hostid'));
		$frmItem->addItemToBottomRow($frmRow);

		// adding javascript, so that auth fields would be hidden if they are not used in specific auth type
		$securityLevelVisibility = array();
		zbx_subarray_push($securityLevelVisibility, ITEM_SNMPV3_SECURITYLEVEL_AUTHNOPRIV, 'row_snmpv3_authpassphrase');
		zbx_subarray_push($securityLevelVisibility, ITEM_SNMPV3_SECURITYLEVEL_AUTHNOPRIV, 'snmpv3_authpassphrase');

		zbx_subarray_push($securityLevelVisibility, ITEM_SNMPV3_SECURITYLEVEL_AUTHPRIV, 'row_snmpv3_authpassphrase');
		zbx_subarray_push($securityLevelVisibility, ITEM_SNMPV3_SECURITYLEVEL_AUTHPRIV, 'snmpv3_authpassphrase');
		zbx_subarray_push($securityLevelVisibility, ITEM_SNMPV3_SECURITYLEVEL_AUTHPRIV, 'row_snmpv3_privpassphrase');
		zbx_subarray_push($securityLevelVisibility, ITEM_SNMPV3_SECURITYLEVEL_AUTHPRIV, 'snmpv3_privpassphrase');

		zbx_add_post_js("var securityLevelSwitcher = new CViewSwitcher('snmpv3_securitylevel', 'change', ".zbx_jsvalue($securityLevelVisibility, true).");");
		zbx_add_post_js("var authTypeSwitcher = new CViewSwitcher('authtype', 'change', ".zbx_jsvalue($authTypeVisibility, true).");");
		zbx_add_post_js("var typeSwitcher = new CViewSwitcher('type', 'change', ".zbx_jsvalue($typeVisibility, true).(isset($_REQUEST['itemid'])? ', true': '').');');
		zbx_add_post_js("var mnFrmTbl = document.getElementById('".$frmItem->getName()."'); if(mnFrmTbl) mnFrmTbl.style.visibility = 'visible';");


		$items_wdgt->addItem($frmItem);
	}
	else{
// Items Header
		$numrows = new CDiv();
		$numrows->setAttribute('name', 'numrows');

		$items_wdgt->addHeader(S_DISCOVERY_RULES_BIG);
		$items_wdgt->addHeader($numrows);

		$items_wdgt->addItem(get_header_host_table($_REQUEST['hostid'], 'discoveries'));
// ----------------

		$form = new CForm();
		$form->addVar('hostid', $_REQUEST['hostid']);
		$form->setName('items');

		$sortlink = new Curl();
		$sortlink->setArgument('hostid', $_REQUEST['hostid']);
		$sortlink = $sortlink->getUrl();
		$table = new CTableInfo();
		$table->setHeader(array(
			new CCheckBox('all_items',null,"checkAll('".$form->GetName()."','all_items','group_itemid');"),
			make_sorting_header(S_NAME, 'name', $sortlink),
			S_ITEMS,
			S_TRIGGERS,
			S_GRAPHS,
			make_sorting_header(S_KEY,'key_', $sortlink),
			make_sorting_header(S_INTERVAL,'delay', $sortlink),
			make_sorting_header(S_TYPE,'type', $sortlink),
			make_sorting_header(S_STATUS,'status', $sortlink),
			S_ERROR
		));

		$sortfield = getPageSortField('name');
		$sortorder = getPageSortOrder();
		$options = array(
			'hostids' => $_REQUEST['hostid'],
			'output' => API_OUTPUT_EXTEND,
			'editable' => 1,
			'selectPrototypes' => API_OUTPUT_COUNT,
			'selectGraphs' => API_OUTPUT_COUNT,
			'selectTriggers' => API_OUTPUT_COUNT,
			'sortfield' => $sortfield,
			'sortorder' => $sortorder,
			'limit' => ($config['search_limit']+1)
		);
		$items = API::DiscoveryRule()->get($options);

		order_result($items, $sortfield, $sortorder);
		$paging = getPagingLine($items);

		foreach($items as $inum => $item){
			$description = array();
			if($item['templateid']){
				$template_host = get_realhost_by_itemid($item['templateid']);
				$description[] = new CLink($template_host['name'],'?hostid='.$template_host['hostid'], 'unknown');
				$description[] = ':';
			}
			$item['name_expanded'] = itemName($item);
			$description[] = new CLink($item['name_expanded'], '?form=update&itemid='.$item['itemid']);


			$status = new CCol(new CLink(item_status2str($item['status']), '?hostid='.$_REQUEST['hostid'].'&group_itemid='.$item['itemid'].'&go='.
				($item['status']? 'activate':'disable'), item_status2style($item['status'])));


			if(zbx_empty($item['error'])){
				$error = new CDiv(SPACE, 'status_icon iconok');
			}
			else{
				$error = new CDiv(SPACE, 'status_icon iconerror');
				$error->setHint($item['error'], '', 'on');
			}

			$prototypes = array(new CLink(S_ITEMS, 'disc_prototypes.php?&parent_discoveryid='.$item['itemid']),
				' ('.$item['prototypes'].')');

			$graphs_count = isset($graphs[$item['itemid']]['rowscount']) ? $graphs[$item['itemid']]['rowscount'] : 0;
			$protographs = array(new CLink(S_GRAPHS, 'graph_prototypes.php?&parent_discoveryid='.$item['itemid']),
				' ('.$item['graphs'].')');

			$triggers_count = isset($triggers[$item['itemid']]['rowscount']) ? $triggers[$item['itemid']]['rowscount'] : 0;
			$prototriggers = array(new CLink(S_TRIGGERS, 'trigger_prototypes.php?&parent_discoveryid='.$item['itemid']),
				' ('.$item['triggers'].')');

			$table->addRow(array(
				new CCheckBox('group_itemid['.$item['itemid'].']',null,null,$item['itemid']),
				$description,
				$prototypes,
				$prototriggers,
				$protographs,
				$item['key_'],
				$item['delay'],
				item_type2str($item['type']),
				$status,
				$error
			));
		}

// GO{
		$goBox = new CComboBox('go');
		$goOption = new CComboItem('activate',S_ACTIVATE_SELECTED);
		$goOption->setAttribute('confirm',S_ENABLE_SELECTED_ITEMS_Q);
		$goBox->addItem($goOption);

		$goOption = new CComboItem('disable',S_DISABLE_SELECTED);
		$goOption->setAttribute('confirm',S_DISABLE_SELECTED_ITEMS_Q);
		$goBox->addItem($goOption);

		$goOption = new CComboItem('delete',S_DELETE_SELECTED);
		$goOption->setAttribute('confirm',S_DELETE_SELECTED_ITEMS_Q);
		$goBox->addItem($goOption);

// goButton name is necessary!!!
		$goButton = new CSubmit('goButton',S_GO);
		$goButton->setAttribute('id','goButton');

		zbx_add_post_js('chkbxRange.pageGoName = "group_itemid";');

		$footer = get_table_header(array($goBox, $goButton));
// }GO

		$form->addItem(array($paging, $table, $paging, $footer));
		$items_wdgt->addItem($form);
	}

	$items_wdgt->show();

?>
<?php

include_once('include/page_footer.php');

?>
