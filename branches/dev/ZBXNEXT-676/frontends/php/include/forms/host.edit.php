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
// include JS + templates
	include('include/templates/host.js.php');
	include('include/templates/macros.js.php');
?>
<?php
	$divTabs = new CTabView(array('remember'=>1));
	if(!isset($_REQUEST['form_refresh']))
		$divTabs->setSelected(0);

	$host_groups = get_request('groups', array());
	if(isset($_REQUEST['groupid']) && ($_REQUEST['groupid']>0) && empty($host_groups)){
		array_push($host_groups, $_REQUEST['groupid']);
	}

	$newgroup	= get_request('newgroup','');

	$host 		= get_request('host',	'');
	$status		= get_request('status',	HOST_STATUS_MONITORED);
	$proxy_hostid	= get_request('proxy_hostid','');

	$ipmi_authtype	= get_request('ipmi_authtype',-1);
	$ipmi_privilege	= get_request('ipmi_privilege',2);
	$ipmi_username	= get_request('ipmi_username','');
	$ipmi_password	= get_request('ipmi_password','');

	$_REQUEST['hostid'] = get_request('hostid', 0);

	$useprofile		= get_request('useprofile', 'no');
	$host_profile	= get_request('host_profile',array());

	$macros = get_request('macros',array());
	$interfaces = get_request('interfaces',array());
	$templates = get_request('templates',array());
	$clear_templates = get_request('clear_templates', array());

	$frm_title = _('Host');
	if($_REQUEST['hostid']>0){
		$dbHosts = API::Host()->get(array(
			'hostids' => $_REQUEST['hostid'],
			'selectGroups' => API_OUTPUT_EXTEND,
			'selectParentTemplates' => API_OUTPUT_EXTEND,
			'selectMacros' => API_OUTPUT_EXTEND,
			'select_profile' => API_OUTPUT_EXTEND,
			'output' => API_OUTPUT_EXTEND
		));
		$dbHost = reset($dbHosts);

		$dbHost['interfaces'] = API::HostInterface()->get(array(
			'hostids' => $dbHost['hostid'],
			'output' => API_OUTPUT_EXTEND,
			'selectItems' => API_OUTPUT_COUNT,
			'preserveKeys' => true,
		));

		morder_result($dbHost['interfaces'], array('type', 'interfaceid'));

		$frm_title	.= SPACE.' ['.$dbHost['host'].']';
		$original_templates = $dbHost['parentTemplates'];
		$original_templates = zbx_toHash($original_templates, 'templateid');

		if(!empty($interfaces)){
			foreach($interfaces as $hinum => $interface){
				$interfaces[$hinum]['items'] = 0;

				if($interface['new'] == 'create') continue;
				if(!isset($dbHost['interfaces'][$interface['interfaceid']])) continue;

				$interfaces[$hinum]['items'] = $dbHost['interfaces'][$interface['interfaceid']]['items'];
			}
		}
	}
	else{
		$original_templates = array();
	}

	if(($_REQUEST['hostid']>0) && !isset($_REQUEST['form_refresh'])){
		$proxy_hostid	= $dbHost['proxy_hostid'];
		$host			= $dbHost['host'];
		$status			= $dbHost['status'];

		$ipmi_authtype		= $dbHost['ipmi_authtype'];
		$ipmi_privilege		= $dbHost['ipmi_privilege'];
		$ipmi_username		= $dbHost['ipmi_username'];
		$ipmi_password		= $dbHost['ipmi_password'];

		$macros = $dbHost['macros'];
		$interfaces = $dbHost['interfaces'];
		$host_groups = zbx_objectValues($dbHost['groups'], 'groupid');


		$host_profile = $dbHost['profile'];
		$useprofile = empty($host_profile) ? 'no' : 'yes';


		$templates = array();
		foreach($original_templates as $tnum => $tpl){
			$templates[$tpl['templateid']] = $tpl['host'];
		}
	}

	$clear_templates = array_intersect($clear_templates, array_keys($original_templates));
	$clear_templates = array_diff($clear_templates,array_keys($templates));
	natcasesort($templates);

	$frmHost = new CForm();
	$frmHost->setName('web.hosts.host.php.');
	$frmHost->addVar('form', get_request('form', 1));

	$from_rfr = get_request('form_refresh',0);
	$frmHost->addVar('form_refresh', $from_rfr+1);
	$frmHost->addVar('clear_templates', $clear_templates);

// HOST WIDGET {

	$hostList = new CFormList('hostlist');

	if($_REQUEST['hostid']>0) $frmHost->addVar('hostid', $_REQUEST['hostid']);
	if($_REQUEST['groupid']>0) $frmHost->addVar('groupid', $_REQUEST['groupid']);

	$hostList->addRow(S_NAME, new CTextBox('host',$host,54));

	$grp_tb = new CTweenBox($frmHost, 'groups', $host_groups, 10);
	$all_groups = API::HostGroup()->get(array(
		'editable' => 1,
		'output' => API_OUTPUT_EXTEND
	));
	order_result($all_groups, 'name');
	foreach($all_groups as $group){
		$grp_tb->addItem($group['groupid'], $group['name']);
	}

	$hostList->addRow(S_GROUPS,$grp_tb->get(S_IN_GROUPS, S_OTHER_GROUPS));
	$hostList->addRow(array(
			new CLabel(S_NEW_GROUP, 'newgroup'), BR(),
			new CTextBox('newgroup',$newgroup)
		));

// interfaces
	if(empty($interfaces)){
		$interfaces = array(array(
			'ip' => '127.0.0.1',
			'dns' => '',
			'port' => 10050,
			'useip' => 1,
			'type' => 1,
			'items' => 0
		));
	}

	$ifTab = new CTable(null, 'formElementTable');
	$ifTab->addRow(array(S_IP_ADDRESS,S_DNS_NAME,S_CONNECT_TO,S_PORT,S_TYPE));
	$ifTab->setAttribute('id', 'hostInterfaces');

	$jsInsert = '';
	foreach($interfaces as $inum => $interface){
		$jsInsert.= 'addInterfaceRow('.zbx_jsvalue($interface).');';
	}
	zbx_add_post_js('setTimeout(function(){'.$jsInsert.'}, 1);');

	$addButton = new CButton('add', S_ADD, 'javascript: addInterfaceRow({});');
	$addButton->setAttribute('class', 'link_menu');

	$col = new CCol(array($addButton));
	$col->setAttribute('colspan', 5);

	$buttonRow = new CRow($col);
	$buttonRow->setAttribute('id', 'hostIterfacesFooter');

	$ifTab->addRow($buttonRow);

	$hostList->addRow(S_INTERFACES, new CDiv($ifTab, 'objectgroup inlineblock border_dotted ui-corner-all'));

//Proxy
	$cmbProxy = new CComboBox('proxy_hostid', $proxy_hostid);
	$cmbProxy->addItem(0, S_NO_PROXY);

	$options = array('output' => API_OUTPUT_EXTEND);
	$db_proxies = API::Proxy()->get($options);
	order_result($db_proxies, 'host');

	foreach($db_proxies as $proxy){
		$cmbProxy->addItem($proxy['proxyid'], $proxy['host']);
	}

	$hostList->addRow(S_MONITORED_BY_PROXY, $cmbProxy);
//----------

	$cmbStatus = new CComboBox('status',$status);
	$cmbStatus->addItem(HOST_STATUS_MONITORED,	S_MONITORED);
	$cmbStatus->addItem(HOST_STATUS_NOT_MONITORED,	S_NOT_MONITORED);

	$hostList->addRow(S_STATUS,$cmbStatus);

	if($_REQUEST['form'] == 'full_clone'){
// Items
		$hostItems = API::Item()->get(array(
			'hostids' => $_REQUEST['hostid'],
			'inherited' => false,
			'filter' => array('flags' => ZBX_FLAG_DISCOVERY_NORMAL),
			'output' => API_OUTPUT_EXTEND,
		));
		if(!empty($hostItems)){
			$itemsList = array();
			foreach($hostItems as $hostItem){
				$itemsList[$hostItem['itemid']] = item_description($hostItem);
			}
			order_result($itemsList);

			$listBox = new CListBox('items', null, 8);
			$listBox->setAttribute('disabled', 'disabled');
			$listBox->addItems($itemsList);

			$hostList->addRow(_('Items'), $listBox);
		}

// Triggers
		$hostTriggers = API::Trigger()->get(array(
			'inherited' => false,
			'hostids' => $_REQUEST['hostid'],
			'output' => API_OUTPUT_EXTEND,
			'filter' => array('flags' => array(ZBX_FLAG_DISCOVERY_NORMAL)),
			'expandDescription' => true,
		));
		if(!empty($hostTriggers)){
			$triggersList = array();
			foreach($hostTriggers as $hostTrigger){
				$triggersList[$hostTrigger['triggerid']] = $hostTrigger['description'];
			}
			order_result($triggersList);

			$listBox = new CListBox('triggers', null, 8);
			$listBox->setAttribute('disabled', 'disabled');
			$listBox->addItems($triggersList);

			$hostList->addRow(_('Triggers'), $listBox);
		}

// Graphs
		$hostGraphs = API::Graph()->get(array(
			'inherited' => false,
			'hostids' => $_REQUEST['hostid'],
			'filter' => array('flags' => array(ZBX_FLAG_DISCOVERY_NORMAL)),
			'selectHosts' => API_OUTPUT_REFER,
			'output' => API_OUTPUT_EXTEND,
		));
		if(!empty($hostGraphs)){
			$graphsList = array();
			foreach($hostGraphs as $hostGraph){
				if(count($hostGraph['hosts']) == 1){
					$graphsList[$hostGraph['graphid']] = $hostGraph['name'];
				}
			}
			order_result($graphsList);

			$listBox = new CListBox('graphs', null, 8);
			$listBox->setAttribute('disabled', 'disabled');
			$listBox->addItems($graphsList);

			$hostList->addRow(_('Graphs'), $listBox);
		}

// Discovery rules
		$hostDiscoveryRuleids = array();

		$hostDiscoveryRules = API::DiscoveryRule()->get(array(
			'inherited' => false,
			'hostids' => $_REQUEST['hostid'],
			'output' => API_OUTPUT_EXTEND,
		));
		if(!empty($hostDiscoveryRules)){
			$discoveryRuleList = array();
			foreach($hostDiscoveryRules as $discoveryRule){
				$discoveryRuleList[$discoveryRule['itemid']] = item_description($discoveryRule);
			}
			order_result($discoveryRuleList);
			$hostDiscoveryRuleids = array_keys($discoveryRuleList);

			$listBox = new CListBox('discoveryRules', null, 8);
			$listBox->setAttribute('disabled', 'disabled');
			$listBox->addItems($discoveryRuleList);

			$hostList->addRow(_('Discovery rules'), $listBox);
		}

// Item prototypes
		$hostItemPrototypes = API::Itemprototype()->get(array(
			'hostids' => $_REQUEST['hostid'],
			'discoveryids' => $hostDiscoveryRuleids,
			'inherited' => false,
			'output' => API_OUTPUT_EXTEND,
		));
		if(!empty($hostItemPrototypes)){
			$prototypeList = array();
			foreach($hostItemPrototypes as $itemPrototype){
				$prototypeList[$itemPrototype['itemid']] = item_description($itemPrototype);
			}
			order_result($prototypeList);

			$listBox = new CListBox('itemsPrototypes', null, 8);
			$listBox->setAttribute('disabled', 'disabled');
			$listBox->addItems($prototypeList);

			$hostList->addRow(_('Item prototypes'), $listBox);
		}

// Trigger prototypes
		$hostTriggerPrototypes = API::TriggerPrototype()->get(array(
			'hostids' => $_REQUEST['hostid'],
			'discoveryids' => $hostDiscoveryRuleids,
			'inherited' => false,
			'output' => API_OUTPUT_EXTEND,
			'expandDescription' => true,
		));
		if(!empty($hostTriggerPrototypes)){
			$prototypeList = array();
			foreach($hostTriggerPrototypes as $triggerPrototype){
				$prototypeList[$triggerPrototype['triggerid']] = $triggerPrototype['description'];
			}
			order_result($prototypeList);

			$listBox = new CListBox('triggerprototypes', null, 8);
			$listBox->setAttribute('disabled', 'disabled');
			$listBox->addItems($prototypeList);

			$hostList->addRow(_('Trigger prototypes'), $listBox);
		}

// Graph prototypes
		$hostGraphPrototypes = API::GraphPrototype()->get(array(
			'hostids' => $_REQUEST['hostid'],
			'discoveryids' => $hostDiscoveryRuleids,
			'inherited' => false,
			'selectHosts' => API_OUTPUT_EXTEND,
			'output' => API_OUTPUT_EXTEND,
		));
		if(!empty($hostGraphPrototypes)){
			$prototypeList = array();
			foreach($hostGraphPrototypes as $graphPrototype){
				if(count($graphPrototype['hosts']) == 1){
					$prototypeList[$graphPrototype['graphid']] = $graphPrototype['name'];
				}
			}
			order_result($prototypeList);

			$listBox = new CListBox('graphPrototypes', null, 8);
			$listBox->setAttribute('disabled', 'disabled');
			$listBox->addItems($prototypeList);

			$hostList->addRow(_('Graph prototypes'), $listBox);
		}
	}

	$divTabs->addTab('hostTab', S_HOST, $hostList);
// } HOST WIDGET

// TEMPLATES{
	$tmplList = new CFormList('tmpllist');

	foreach($templates as $tid => $temp_name){
		$frmHost->addVar('templates['.$tid.']', $temp_name);
		$tmplList->addRow($temp_name, array(
			new CSubmit('unlink['.$tid.']', S_UNLINK, null, 'link_menu'),
			SPACE, SPACE,
			isset($original_templates[$tid]) ? new CSubmit('unlink_and_clear['.$tid.']', S_UNLINK_AND_CLEAR, null, 'link_menu') : SPACE
		));
	}

	$tmplAdd = new CButton('add', S_ADD, "return PopUp('popup.php?dstfrm=".$frmHost->getName().
			"&dstfld1=new_template&srctbl=templates&srcfld1=hostid&srcfld2=host".
			url_param($templates,false,'existed_templates')."',450,450)",
			'link_menu');

	$tmplList->addRow($tmplAdd, SPACE);

	$divTabs->addTab('templateTab', S_TEMPLATES, $tmplList);
// } TEMPLATES

// IPMI TAB {
	$ipmiList = new CFormList('ipmilist');

	$cmbIPMIAuthtype = new CComboBox('ipmi_authtype', $ipmi_authtype);
	$cmbIPMIAuthtype->addItem(IPMI_AUTHTYPE_DEFAULT,	S_AUTHTYPE_DEFAULT);
	$cmbIPMIAuthtype->addItem(IPMI_AUTHTYPE_NONE,		S_AUTHTYPE_NONE);
	$cmbIPMIAuthtype->addItem(IPMI_AUTHTYPE_MD2,		S_AUTHTYPE_MD2);
	$cmbIPMIAuthtype->addItem(IPMI_AUTHTYPE_MD5,		S_AUTHTYPE_MD5);
	$cmbIPMIAuthtype->addItem(IPMI_AUTHTYPE_STRAIGHT,	S_AUTHTYPE_STRAIGHT);
	$cmbIPMIAuthtype->addItem(IPMI_AUTHTYPE_OEM,		S_AUTHTYPE_OEM);
	$cmbIPMIAuthtype->addItem(IPMI_AUTHTYPE_RMCP_PLUS,	S_AUTHTYPE_RMCP_PLUS);
	$cmbIPMIAuthtype->setAttribute('size', 7);
	$cmbIPMIAuthtype->addStyle('width: 170px;');
	$ipmiList->addRow(_('Authentication algorithm'), $cmbIPMIAuthtype);

	$cmbIPMIPrivilege = new CComboBox('ipmi_privilege', $ipmi_privilege);
	$cmbIPMIPrivilege->addItem(IPMI_PRIVILEGE_CALLBACK,	S_PRIVILEGE_CALLBACK);
	$cmbIPMIPrivilege->addItem(IPMI_PRIVILEGE_USER,		S_PRIVILEGE_USER);
	$cmbIPMIPrivilege->addItem(IPMI_PRIVILEGE_OPERATOR,	S_PRIVILEGE_OPERATOR);
	$cmbIPMIPrivilege->addItem(IPMI_PRIVILEGE_ADMIN,	S_PRIVILEGE_ADMIN);
	$cmbIPMIPrivilege->addItem(IPMI_PRIVILEGE_OEM,		S_PRIVILEGE_OEM);
	$cmbIPMIPrivilege->setAttribute('size', 5);
	$cmbIPMIPrivilege->addStyle('width: 170px;');
	$ipmiList->addRow(_('Privilege level'), $cmbIPMIPrivilege);

	$ipmiList->addRow(_('Username'), new CTextBox('ipmi_username', $ipmi_username, 20));
	$ipmiList->addRow(_('Password'), new CTextBox('ipmi_password', $ipmi_password, 20));

	$divTabs->addTab('ipmiTab', S_IPMI, $ipmiList);

// } IPMI TAB


// MACROS WIDGET {
// macros

	if(empty($macros)){
		$macros = array(array(
			'macro' => '',
			'value' => ''
		));
	}

	$macroTab = new CTable(null,'formElementTable');
	$macroTab->addRow(array(S_MACRO, SPACE, S_VALUE));
	$macroTab->setAttribute('id', 'userMacros');

	$jsInsert = '';
	foreach($macros as $inum => $macro){
		if(!empty($jsInsert) && zbx_empty($macro['macro']) && zbx_empty($macro['value'])) continue;

		$jsInsert.= 'addMacroRow('.zbx_jsvalue($macro).');';
	}
	zbx_add_post_js($jsInsert);

	$addButton = new CButton('add', S_ADD, 'javascript: addMacroRow({});');
	$addButton->setAttribute('class', 'link_menu');

	$col = new CCol(array($addButton));
	$col->setAttribute('colspan', 4);

	$buttonRow = new CRow($col);
	$buttonRow->setAttribute('id', 'userMacroFooter');

	$macroTab->addRow($buttonRow);

	$macrolist = new CFormList('macrolist');
	$macrolist->addRow($macroTab);

	$divTabs->addTab('macroTab', S_MACROS, $macrolist);
// } MACROS WIDGET


// PROFILE WIDGET {
	$profileList = new CFormList('profilelist');
	$profileList->addRow(array(new CLabel(SPACE, 'useprofile'), new CCheckBox('useprofile', $useprofile)));


	$host_profile_fields = getHostProfiles();
	foreach($host_profile_fields as $profileName => $profileCaption){
		if(!isset($host_profile[$profileName])){
			$host_profile[$profileName] = '';
		}
		$profileList->addRow($profileCaption, new CTextBox('host_profile['.$profileName.']', $host_profile[$profileName], 80));
	}

	$divTabs->addTab('profileTab', S_HOST_PROFILE, $profileList);
// } PROFILE WIDGET

	$frmHost->addItem($divTabs);

// Footer
	$main = array(new CSubmit('save', S_SAVE));
	$others = array();
	if(($_REQUEST['hostid']>0) && ($_REQUEST['form'] != 'full_clone')){
		$others[] = new CSubmit('clone', S_CLONE);
		$others[] = new CSubmit('full_clone', S_FULL_CLONE);
		$others[] = new CButtonDelete(S_DELETE_SELECTED_HOST_Q, url_param('form').url_param('hostid').url_param('groupid'));
	}
	$others[] = new CButtonCancel(url_param('groupid'));

	$frmHost->addItem(makeFormFooter($main, $others));

return $frmHost;
?>
