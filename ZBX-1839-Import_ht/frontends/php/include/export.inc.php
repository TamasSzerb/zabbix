<?php
/*
** ZABBIX
** Copyright (C) 2000-2009 SIA Zabbix
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

class zbxXML{

	private static $ZBX_EXPORT_MAP = array(
		XML_TAG_HOST => array(
			'attributes' => array(
				'host' 				=> 'name'
			),
			'elements' => array(
				'proxy_hostid'		=> '',
				'useip'				=> '',
				'dns'				=> '',
				'ip'				=> '',
				'port'				=> '',
				'status'			=> '',
				'useipmi'			=> '',
				'ipmi_ip'			=> '',
				'ipmi_port'			=> '',
				'ipmi_authtype'		=> '',
				'ipmi_privilege'	=> '',
				'ipmi_username'		=> '',
				'ipmi_password'		=> '',
			)
		),
		XML_TAG_MACRO => array(
			'attributes' => array(),
			'elements' => array(
				'value' 			=> '',
				'macro' 			=> 'name'
			)
		),
		XML_TAG_HOSTPROFILE => array(
			'attributes' => array(),
			'elements' => array(
				'devicetype'		=> '',
				'name'				=> '',
				'os'				=> '',
				'serialno'			=> '',
				'tag'				=> '',
				'macaddress'		=> '',
				'hardware'			=> '',
				'software'			=> '',
				'contact'			=> '',
				'location'			=> '',
				'notes'				=> ''
			)
		),
		XML_TAG_HOSTPROFILE_EXT => array(
			'attributes' => array(),
			'elements' => array(
				'device_alias'		=> '',
				'device_type'		=> '',
				'device_chassis'	=> '',
				'device_os'			=> '',
				'device_os_short'	=> '',
				'device_hw_arch'	=> '',
				'device_serial'		=> '',
				'device_model'		=> '',
				'device_tag'		=> '',
				'device_vendor'		=> '',
				'device_contract'	=> '',
				'device_who'		=> '',
				'device_status'		=> '',
				'device_app_01'		=> '',
				'device_app_02'		=> '',
				'device_app_03'		=> '',
				'device_app_04'		=> '',
				'device_app_05'		=> '',
				'device_url_1'		=> '',
				'device_url_2'		=> '',
				'device_url_3'		=> '',
				'device_networks'	=> '',
				'device_notes'		=> '',
				'device_hardware'	=> '',
				'device_software'	=> '',
				'ip_subnet_mask'	=> '',
				'ip_router'			=> '',
				'ip_macaddress'		=> '',
				'oob_ip'			=> '',
				'oob_subnet_mask'	=> '',
				'oob_router'		=> '',
				'date_hw_buy'		=> '',
				'date_hw_install'	=> '',
				'date_hw_expiry'	=> '',
				'date_hw_decomm'	=> '',
				'site_street_1'		=> '',
				'site_street_2'		=> '',
				'site_street_3'		=> '',
				'site_city'			=> '',
				'site_state'		=> '',
				'site_country'		=> '',
				'site_zip'			=> '',
				'site_rack'			=> '',
				'site_notes'		=> '',
				'poc_1_name'		=> '',
				'poc_1_email'		=> '',
				'poc_1_phone_1'		=> '',
				'poc_1_phone_2'		=> '',
				'poc_1_cell'		=> '',
				'poc_1_screen'		=> '',
				'poc_1_notes'		=> '',
				'poc_2_name'		=> '',
				'poc_2_email'		=> '',
				'poc_2_phone_1'		=> '',
				'poc_2_phone_2'		=> '',
				'poc_2_cell'		=> '',
				'poc_2_screen'		=> '',
				'poc_2_notes'		=> ''
			)
		),
		XML_TAG_DEPENDENCY => array(
			'attributes' => array(
				'host_trigger'		=> 'description'),
			'elements' => array(
				'depends'			=> ''
			)
		),
		XML_TAG_ITEM => array(
			'attributes' => array(
				'type'				=> '',
				'key_'				=> 'key',
				'value_type'		=> ''
			),
			'elements' => array(
				'description'		=> '',
				'ipmi_sensor'		=> '',
				'delay'				=> '',
				'history'			=> '',
				'trends'			=> '',
				'status'			=> '',
				'data_type'			=> '',
				'units'				=> '',
				'multiplier'		=> '',
				'delta'				=> '',
				'formula'			=> '',
				'lastlogsize'		=> '',
				'logtimefmt'		=> '',
				'delay_flex'		=> '',
				'authtype'		=> '',
				'username'		=> '',
				'password'		=> '',
				'publickey'		=> '',
				'privatekey'		=> '',
				'params'			=> '',
				'trapper_hosts'		=> '',
				'snmp_community'	=> '',
				'snmp_oid'			=> '',
				'snmp_port'			=> '',
				'snmpv3_securityname'	=> '',
				'snmpv3_securitylevel'	=> '',
				'snmpv3_authpassphrase'	=> '',
				'snmpv3_privpassphrase'	=> ''
			)
		),
		XML_TAG_TRIGGER => array(
			'attributes' => array(),
			'elements' => array(
				'description'		=> '',
				'type'				=> '',
				'expression'		=> '',
				'url'				=> '',
				'status'			=> '',
				'priority'			=> '',
				'comments'			=> ''
			)
		),
		XML_TAG_GRAPH => array(
			'attributes' => array(
				'name'				=> '',
				'width'				=> '',
				'height'			=> ''
			),
			'elements' => array(
				'ymin_type'			=> '',
				'ymax_type'			=> '',
				'ymin_item_key'		=> '',
				'ymax_item_key'		=> '',
				'show_work_period'	=> '',
				'show_triggers'		=> '',
				'graphtype'			=> '',
				'yaxismin'			=> '',
				'yaxismax'			=> '',
				'show_legend'		=> '',
				'show_3d'			=> '',
				'percent_left'		=> '',
				'percent_right'		=> ''
			)
		),
		XML_TAG_GRAPH_ELEMENT => array(
			'attributes' => array(
				'host_key_'			=> 'item'
			),
			'elements' => array(
				'drawtype'			=> '',
				'sortorder'			=> '',
				'color'				=> '',
				'yaxisside'			=> '',
				'calc_fnc'			=> '',
				'type'				=> '',
				'periods_cnt'		=> ''
			)
		)
	);

	private static function space2tab($matches){
		return str_repeat("\t", strlen($matches[0]) / 2 );
	}

	public static function arrayToXML($array, $root = 'root', $xml = null){

		if($xml == null){
			$xml = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><$root />");
		}

 		foreach($array as $key => $value){

			if(is_numeric($key)){
				$key = 'node_'. $key;
			}
			if(is_array($value)){
				$node = $xml->addChild($key);
				self::toXml($value, $root, $node);
			}
			else{
				$value = htmlentities($value);
				$xml->addChild($key, $value);
			}
		}

		$doc = new DOMDocument();
		$doc->preserveWhiteSpace = false;
		$doc->loadXML($xml->asXML());
		$doc->formatOutput = true;

		return preg_replace_callback('/^( {2,})/m', 'self::callback', $doc->saveXML());
	}

	private static function addChildData($node, $child_name, $data){
		$child_node = $node->appendChild(new DOMElement($child_name));

		foreach(self::$ZBX_EXPORT_MAP[$child_name]['attributes'] as $attr => $name){
			if($name == '') $name = $attr;
			$child_node->setAttributeNode(new DOMAttr($name, $data[$attr]));
		}
		foreach(self::$ZBX_EXPORT_MAP[$child_name]['elements'] as $el => $name){
			if($name == '') $name = $el;
			$n = $child_node->appendChild(new DOMElement($name));
			$n->appendChild(new DOMText($data[$el]));
		}

	return $child_node;
	}

	public static function export($data){

		$doc = new DOMDocument('1.0', 'UTF-8');
		$root = $doc->appendChild(new DOMElement('zabbix_export'));
		$root->setAttributeNode(new DOMAttr('version', '1.0'));
		$root->setAttributeNode(new DOMAttr('date', date('d.m.y')));
		$root->setAttributeNode(new DOMAttr('time', date('H.i')));


		$hosts_node = $root->appendChild(new DOMElement(XML_TAG_HOSTS));

		foreach($data['hosts'] as $host){
// HOST
			$host_node = self::addChildData($hosts_node, XML_TAG_HOST, $host);
// HOST PROFILE
			if(!empty($host['profile']))
				self::addChildData($host_node, XML_TAG_HOSTPROFILE, $host['profile']);
			if(!empty($host['profile_ext']))
				self::addChildData($host_node, XML_TAG_HOSTPROFILE_EXT, $host['profile_ext']);
// GROUPS
			if(isset($data['hosts_groups'])){
				$groups_node = $host_node->appendChild(new DOMElement(XML_TAG_GROUPS));
				foreach($data['hosts_groups'] as $gnum => $group){
					$group['hosts'] = zbx_toHash($group['hosts'], 'hostid');
					if(isset($group['hosts'][$host['hostid']])){
						$groups_node->appendChild(new DOMElement(XML_TAG_GROUP, $group['name']));
					}
				}
			}

// TRIGGERS
			if(isset($data['triggers'])){
				$triggers_node = $host_node->appendChild(new DOMElement(XML_TAG_TRIGGERS));
				foreach($data['triggers'] as $tnum => $trigger){
					$trigger['hosts'] = zbx_toHash($trigger['hosts'], 'hostid');
					if(isset($trigger['hosts'][$host['hostid']])){
						self::addChildData($triggers_node, XML_TAG_TRIGGER, $trigger);
					}
				}
			}

// ITEMS
			if(isset($data['items'])){
				$items_node = $host_node->appendChild(new DOMElement(XML_TAG_ITEMS));
				foreach($data['items'] as $item){
					$item['hosts'] = zbx_toHash($item['hosts'], 'hostid');
					if(isset($item['hosts'][$host['hostid']])){
						$item_node = self::addChildData($items_node, XML_TAG_ITEM, $item);
//sdi('Item: '. date('H i s u'));
						if(isset($data['items_applications'])){
							$applications_node = $item_node->appendChild(new DOMElement(XML_TAG_APPLICATIONS));
							foreach($data['items_applications'] as $application){
								$application['items'] = zbx_toHash($application['items'], 'itemid');
								if(isset($application['items'][$item['itemid']])){
									$applications_node->appendChild(new DOMElement(XML_TAG_APPLICATION, $application['name']));
								}
							}
						}
					}
				}
			}

// TEMPLATES
			if(isset($data['templates'])){
				$templates_node = $host_node->appendChild(new DOMElement(XML_TAG_TEMPLATES));
				foreach($data['templates'] as $template){
					$template['hosts'] = zbx_toHash($template['hosts'], 'hostid');
					if(isset($template['hosts'][$host['hostid']])){
						$templates_node->appendChild(new DOMElement(XML_TAG_TEMPLATE, $template['host']));
					}
				}
			}

// GRAPHS
			if(isset($data['graphs'])){
				$graphs_node = $host_node->appendChild(new DOMElement(XML_TAG_GRAPHS));
						$itemminmaxids = array();

				foreach($data['graphs'] as $num => $graph){
					$itemminmaxids[$graph['ymin_itemid']] = $graph['ymin_itemid'];
					$itemminmaxids[$graph['ymax_itemid']] = $graph['ymax_itemid'];
				}

				$options = array(
					'itemids' => $itemminmaxids,
					'extendoutput' => 1,
					'nopermissions' => 1
				);

				$itemminmaxs = CItem::get($options);
				$itemminmaxs = zbx_toHash($itemminmaxs, 'itemid');


				$hostminmaxs = CHost::get($options);
				$hostminmaxs = zbx_toHash($hostminmaxs, 'hostid');

				foreach($data['graphs'] as $num => $graph){
					$graph['hosts'] = zbx_toHash($graph['hosts'], 'hostid');
					if(isset($graph['hosts'][$host['hostid']])){

						$graph['ymin_item_key'] = '';
						$graph['ymax_item_key'] = '';

						if($graph['ymin_itemid'] > 0){
							$graph['ymin_item_key'] = $hostminmaxs[$itemminmaxs[$graph['ymin_itemid']]['hostid']]['host'].':'.
														$itemminmaxs[$graph['ymin_itemid']]['key_'];
						}

						if($graph['ymax_itemid'] > 0){
							$graph['ymax_item_key'] = $hostminmaxs[$itemminmaxs[$graph['ymax_itemid']]['hostid']]['host'].':'.
														$itemminmaxs[$graph['ymax_itemid']]['key_'];
						}


						$graph_node = self::addChildData($graphs_node, XML_TAG_GRAPH, $graph);


						if(isset($data['graphs_items'])){
							$graph_elements_node = $graph_node->appendChild(new DOMElement(XML_TAG_GRAPH_ELEMENTS));
							foreach($data['graphs_items'] as $ginum => $gitem){
								$tmp_item = get_item_by_itemid($gitem['itemid']);

								$gitem['graphs'] = zbx_toHash($gitem['graphs'], 'graphid');
								if(isset($gitem['graphs'][$graph['graphid']])){
									self::addChildData($graph_elements_node, XML_TAG_GRAPH_ELEMENT, $gitem);
								}
							}
						}
					}
				}
			}

// MACROS
			if(isset($data['macros'])){
				$macros_node = $host_node->appendChild(new DOMElement(XML_TAG_MACROS));
				foreach($data['macros'] as $mnum => $macro){
					$macro['hosts'] = zbx_toHash($macro['hosts'], 'hostid');
					if(isset($macro['hosts'][$host['hostid']])){
						self::addChildData($macros_node, XML_TAG_MACRO, $macro);
					}
				}
			}

		}
// DEPENDENCIES
			if(isset($data['dependencies'])){
				$dependencies_node = $root->appendChild(new DOMElement(XML_TAG_DEPENDENCIES));
				foreach($data['dependencies'] as $ddnum => $dep_data){
					$dependeny_node = $dependencies_node->appendChild(new DOMElement(XML_TAG_DEPENDENCY));
					$dependeny_node->setAttributeNode(new DOMAttr('description', $dep_data['trigger']['host_description']));
					foreach($dep_data['depends_on'] as $dtnum => $dep_trigger){
						$dependeny_node->appendChild(new DOMElement('depends', $dep_trigger['host_description']));
					};
				}
			}

		$doc->preserveWhiteSpace = false;
		$doc->formatOutput = true;

		return preg_replace_callback('/^( {2,})/m', array('zbxXML', 'space2tab'), $doc->saveXML());
	}

	private static function mapXML2arr($xml, $tag){
		$array = array();

		foreach(self::$ZBX_EXPORT_MAP[$tag]['attributes'] as $attr => $value){
			if($value == '') $value = $attr;

			if($xml->getAttribute($value) != ''){
				$array[$attr] = $xml->getAttribute($value);
			}
		}

// fill empty values with key if empty
		$map = self::$ZBX_EXPORT_MAP[$tag]['elements'];
		foreach($map as $db_name => $xml_name){
			if($xml_name == '')
				$map[$db_name] = $db_name;
			else
				$map[$xml_name] = $db_name;
		}

		foreach($xml->childNodes as $node){
			if(isset($map[$node->nodeName]))
				$array[$map[$node->nodeName]] = $node->nodeValue;
		}

		return $array;
	}

	public static function import($rules, $file){

		$result = true;

		libxml_use_internal_errors(true);

		$xml = new DOMDocument();
		$result = $xml->load($file);

		if(!$result){
			foreach(libxml_get_errors() as $error){
				$text = '';

				switch($error->level){
					case LIBXML_ERR_WARNING:
						$text .= 'Warning $error->code: ';
					break;
					case LIBXML_ERR_ERROR:
						$text .= 'Error $error->code: ';
					break;
					case LIBXML_ERR_FATAL:
						$text .= 'Fatal Error $error->code: ';
					break;
				}

				$text .= trim($error->message) . ' [ Line: '.$error->line.' | Column: '.$error->column.' ]';
				error($text);
			}

			libxml_clear_errors();
			return false;
		}

		$triggers_for_dependencies = array();

		if(isset($rules['host']['exist']) || isset($rules['host']['missed'])){
			$xpath = new DOMXPath($xml);
			$hosts = $xpath->query('hosts/host');

			foreach($hosts as $hnum => $host){
// IMPORT RULES
				$host_db = self::mapXML2arr($host, XML_TAG_HOST);

				if(isset($host_db['proxy_hostid'])){
					$proxy_exists = CHost::get(array('hostids' => $host_db['proxy_hostid']));
					if(empty($proxy_exists))
						$host_db['proxy_hostid'] = 0;
				}

				if(!isset($host_db['status'])) $host_db['status'] = HOST_STATUS_TEMPLATE;
				if($host_db['status'] == HOST_STATUS_TEMPLATE){
					$current_host = CTemplate::getObjects(array('template' => $host_db['host']));
				}
				else{
					$current_host = CHost::getObjects(array('host' => $host_db['host']));
				}

				$current_host = reset($current_host);

				if(!$current_host && !isset($rules['host']['missed'])) continue; // break if update nonexist
				if($current_host && !isset($rules['host']['exist'])) continue; // break if not update exist


// HOST GROUPS {{{
				$xpath = new DOMXPath($xml);
				$groups = $xpath->query('groups/group', $host);
				$host_groups = array();
				if($groups->length == 0){
					$default_group = CHostGroup::getObjects(array('name' => ZBX_DEFAULT_IMPORT_HOST_GROUP));

					if(empty($default_group)){
						$default_group = CHostGroup::create(array('name' => ZBX_DEFAULT_IMPORT_HOST_GROUP));
						if($default_group === false){
							error(CHostGroup::resetErrors());
							$result = false;
							break;
						}
					}
					$host_groups = $default_group;
				}
				else{
					$groups_to_add = array();
					foreach($groups as $gnum => $group){
						$current_group = CHostGroup::getObjects(array('name' => $group->nodeValue));

						if(empty($current_group)){
							$groups_to_add[] = array('name' => $group->nodeValue);
						}
						else{
							$host_groups = array_merge($host_groups, $current_group);
						}
					}

					if(!empty($groups_to_add)){
						$new_groups = CHostGroup::create($groups_to_add);
						if($new_groups === false){
							error(CHostGroup::resetErrors());
							$result = false;
							break;
						}

						$host_groups = array_merge($host_groups, $new_groups);
					}
				}
// }}} HOST GROUPS


// MACROS
				$xpath = new DOMXPath($xml);
				$macros = $xpath->query('macros/macro', $host);

				$host_macros = array();
				if($macros->length > 0){
					foreach($macros as $macro){
						$host_macros[] = self::mapXML2arr($macro, XML_TAG_MACRO);
					}
				}
// }}} MACROS


// TEMPLATES {{{
				if(isset($rules['template']['exist'])){
					$xpath = new DOMXPath($xml);
					$templates = $xpath->query('templates/template', $host);

					$host_templates = array();
					foreach($templates as $template){
						$current_template = CTemplate::getObjects(array('template' => $template->nodeValue));
						$current_template = reset($current_template);

						if(!$current_template && !isset($rules['template']['missed'])) continue; // break if update nonexist
						if($current_template && !isset($rules['template']['exist'])) continue; // break if not update exist

						$host_templates[] = $current_template;
					}
					
					if($host_db['status'] == HOST_STATUS_TEMPLATE){
						$host_db['templates_link'] = $host_templates;
					}
					else{
						$host_db['templates'] = $host_templates;
					}

					// $r = CTemplate::massAdd(array('templates' => $current_host, 'templates_link' => $host_templates));
					// if($r === false){
						// error(CTemplate::resetErrors());
						// $result = false;
						// break;
					// }
				}
// }}} TEMPLATES


// HOSTS
				$host_db['groups'] = $host_groups;
				$host_db['macros'] = $host_macros;

				if($current_host && isset($rules['host']['exist'])){

					$current_host = array_merge($current_host, $host_db);

					if($host_db['status'] == HOST_STATUS_TEMPLATE){
						$r = CTemplate::update($current_host);
					}
					else{
						$r = CHost::update($current_host);
					}
					if($r === false){
						error(CHost::resetErrors());
						$result = false;
						break;
					}
				}

				if(!$current_host && isset($rules['host']['missed'])){

					if($host_db['status'] == HOST_STATUS_TEMPLATE){
						$current_host = CTemplate::create($host_db);
					}
					else{
						$current_host = CHost::create($host_db);
					}

					if(empty($current_host)){
						error(CHostGroup::resetErrors());
						$result = false;
						break;
					}

					$current_host = reset($current_host);
				}


// HOST PROFILES {{{
				$xpath = new DOMXPath($xml);
				$profile_node = $xpath->query('host_profile/*', $host);

				if($profile_node->length > 0){
					$profile = array();
					foreach($profile_node as $num => $field){
						$profile[$field->nodeName] = $field->nodeValue;
					}

					delete_host_profile($current_host['hostid']);
					add_host_profile($current_host['hostid'],
						$profile['devicetype'],
						$profile['name'],
						$profile['os'],
						$profile['serialno'],
						$profile['tag'],
						$profile['macaddress'],
						$profile['hardware'],
						$profile['software'],
						$profile['contact'],
						$profile['location'],
						$profile['notes']
					);
				}

				$xpath = new DOMXPath($xml);
				$profile_ext_node = $xpath->query('host_profiles_ext/*', $host);

				if($profile_ext_node->length > 0){
					$profile_ext = array();
					foreach($profile_ext_node as $num => $field){
						$profile_ext[$field->nodeName] = $field->nodeValue;
					}

					delete_host_profile_ext($current_host['hostid']);
					add_host_profile_ext($current_host['hostid'], $profile_ext);
				}
// }}} HOST PROFILES


// ITEMS {{{
				if(isset($rules['item']['exist']) || isset($rules['item']['missed'])){
					$xpath = new DOMXPath($xml);
					$items = $xpath->query('items/item', $host);


					$items_to_add = array();
					$items_to_upd = array();
					foreach($items as $inum => $item){
						$item_db = self::mapXML2arr($item, XML_TAG_ITEM);

						$item_db['hostid'] = $current_host['hostid'];

						$current_item = CItem::getObjects($item_db);
						$current_item = reset($current_item);

						if(!$current_item && !isset($rules['item']['missed'])) continue; // break if update nonexist
						if($current_item && !isset($rules['item']['exist'])) continue; // break if not update exist


// ITEM APPLICATIONS {{{
						$xpath = new DOMXPath($xml);
						$applications = $xpath->query('applications/application', $item);

						$item_applications = array();
						$applications_to_add = array();

						foreach($applications as $application){
							$application_name = $application->nodeValue;
							$application_db = array('name' => $application_name, 'hostid' => $current_host['hostid']);

							$current_application = CApplication::getObjects($application_db);

							if(empty($current_application)){
								$applications_to_add = array_merge($applications_to_add, $application_db);
							}
							else{
								$item_applications = array_merge($item_applications, $current_application);
							}
						}

						if(!empty($applications_to_add)){
							$new_applications = CApplication::create($applications_to_add);
							if($new_applications === false){
								error(CApplication::resetErrors());
								$result = false;
								break 2;
							}
							$item_applications = array_merge($item_applications, $new_applications);
						}
// }}} ITEM APPLICATIONS

						if($current_item && isset($rules['item']['exist'])){
							$current_item = CItem::update($current_item);
							if($current_item === false){
								error(CItem::resetErrors());
								$result = false;
								break;
							}
						}
						if(!$current_item && isset($rules['item']['missed'])){
							$item_db['hostid'] = $current_host['hostid'];

							$current_item = CItem::create($item_db);
							if($current_item === false){
								error(CItem::resetErrors());
								$result = false;
								break;
							}
						}

						$r = CApplication::addItems(array('applications' => $item_applications, 'items' => $current_item));
						if($r === false){
							error(CApplication::resetErrors());
							$result = false;
							break;
						}
					}
				}
// }}} ITEMS


// TRIGGERS {{{
				if(isset($rules['trigger']['exist']) || isset($rules['trigger']['missed'])){
					$xpath = new DOMXPath($xml);
					$triggers = $xpath->query('triggers/trigger', $host);

					$added_triggers = array();
					$triggers_to_add = array();
					$triggers_to_upd = array();

					foreach($triggers as $trigger){
						$trigger_db = self::mapXML2arr($trigger, XML_TAG_TRIGGER);
						$trigger_db['expression'] = str_replace('{{HOSTNAME}:', '{'.$host_db['host'].':', $trigger_db['expression']);
						$trigger_db['hostid'] = $current_host['hostid'];

						$current_trigger = CTrigger::getObjects($trigger_db);
						$current_trigger = reset($current_trigger);


						if(!$current_trigger && !isset($rules['trigger']['missed'])) continue; // break if update nonexist
						if($current_trigger && !isset($rules['trigger']['exist'])) continue; // break if not update exist


						if($current_trigger && isset($rules['trigger']['exist'])){
							$triggers_for_dependencies[] = $current_trigger;
							$current_trigger['expression'] = explode_exp($current_trigger['expression'], false);
							$triggers_to_upd[] = $current_trigger;
						}
						if(!$current_trigger && isset($rules['trigger']['missed'])){
							$trigger_db['hostid'] = $current_host['hostid'];
							$triggers_to_add[] = $trigger_db;
						}
					}

					if(!empty($triggers_to_add)){
						$added_triggers = CTrigger::create($triggers_to_add);
						if($added_triggers === false){
							error(CTrigger::resetErrors());
							$result = false;
							break;
						}
					}
					if(!empty($triggers_to_upd)){
						$r = CTrigger::update($triggers_to_upd);
						if($r === false){
							error(CTrigger::resetErrors());
							$result = false;
							break;
						}
					}

					$triggers_for_dependencies = array_merge($triggers_for_dependencies, $added_triggers);
				}
// }}} TRIGGERS


// GRAPHS {{{
				if(isset($rules['graph']['exist']) || isset($rules['graph']['missed'])){
					$xpath = new DOMXPath($xml);
					$graphs = $xpath->query('graphs/graph', $host);

					$graphs_to_add = array();
					foreach($graphs as $gnum=> $graph){
						$graph_db = self::mapXML2arr($graph, XML_TAG_GRAPH);
						$graph_db['hostid'] = $current_host['hostid'];

						$current_graph = CGraph::getObjects($graph_db);
						$current_graph = reset($current_graph);

						if(!$current_graph && !isset($rules['graph']['missed'])) continue; // break if update nonexist
						if($current_graph && !isset($rules['graph']['exist'])) continue; // break if not update exist

						if($current_graph){
							if(!empty($graph_db['ymin_item_key'])){
								$graph_db['ymin_item_key'] = explode(':', $graph_db['ymin_item_key']);
								if(count($graph_db['ymin_item_key']) < 2){
									error('Incorrect y min item for graph ['.$graph_db['name'].']');
								}

								$current_graph['host']	= array_shift($graph_db['ymin_item_key']);
								$current_graph['ymin_item_key']	= implode(':', $graph_db['ymin_item_key']);

								if(!$item = get_item_by_key($current_graph['ymin_item_key'], $current_graph['host'])){
									error('Missed item ['.$current_graph['ymin_item_key'].'] for host ['.$current_graph['host'].']');
								}

								$current_graph['ymin_itemid'] = $item['itemid'];
							}

							if(!empty($graph_db['ymax_item_key'])){
								$graph_db['ymax_item_key'] = explode(':', $graph_db['ymax_item_key']);
								if(count($graph_db['ymax_item_key']) < 2){
									error('Incorrect y max item for graph ['.$graph_db['name'].']');
								}

								$current_graph['host']	= array_shift($graph_db['ymax_item_key']);
								$current_graph['ymax_item_key']	= implode(':', $graph_db['ymax_item_key']);

								if(!$item = get_item_by_key($current_graph['ymax_item_key'], $current_graph['host'])){
									error('Missed item ['.$current_graph['ymax_item_key'].'] for host ['.$current_graph['host'].']');
								}

								$current_graph['ymax_itemid'] = $item['itemid'];
							}
						}
						if($current_graph){ // if exists, delete graph to add then new
							CGraph::delete($current_graph);
						}
// GRAPH ITEMS {{{
						$xpath = new DOMXPath($xml);
						$gitems = $xpath->query('graph_elements/graph_element', $graph);

						$gitems_to_add = array();
						foreach($gitems as $ginum => $gitem){
							$gitem_db = self::mapXML2arr($gitem, XML_TAG_GRAPH_ELEMENT);

							$data = explode(':', $gitem_db['host_key_']);
							$gitem_host = array_shift($data);
							if($gitem_host == '{HOSTNAME}'){
								$gitem_host = $host_db['host'];
							}

							$gitem_hostid = CHost::getObjects(array('host' => $gitem_host));
							$gitem_templateid = CTemplate::getObjects(array('template' => $gitem_host));
							$gitem_hostid = array_merge($gitem_hostid, $gitem_templateid);

							if(!empty($gitem_hostid)){

								$gitem_hostid = reset($gitem_hostid);

								$gitem_db['hostid'] = $gitem_hostid['hostid'];
								$gitem_db['key_'] = implode(':', $data);

								$current_gitem = CItem::getObjects($gitem_db);
								$current_gitem = reset($current_gitem);
								if($current_gitem){ // if item exists, add graph item to graph
									$gitem_db['itemid'] = $current_gitem['itemid'];
									$graph_db['gitems'][$current_gitem['itemid']] = $gitem_db;
								}
							}
						}

						$graphs_to_add[] = $graph_db;
					}
					$r = CGraph::create($graphs_to_add);
					if($r === false){
						error(CGraph::resetErrors());
						$result = false;
						break;
					}
				}
			}

			if(!$result) return false;
// DEPENDENCIES
			$xpath = new DOMXPath($xml);
			$dependencies = $xpath->query('dependencies/dependency');

			if($dependencies->length > 0){
				$triggers_for_dependencies = zbx_objectValues($triggers_for_dependencies, 'triggerid');
				$triggers_for_dependencies = array_flip($triggers_for_dependencies);

				foreach($dependencies as $dependency){
					$triggers_to_add_dep = array();

					$trigger_description = $dependency->getAttribute('description');
					$current_triggerid = get_trigger_by_description($trigger_description);

// sdi('<b><u>Trigger Description: </u></b>'.$trigger_description.' | <b>Current_triggerid: </b>'. $current_triggerid['triggerid']);

					if($current_triggerid && isset($triggers_for_dependencies[$current_triggerid['triggerid']])){
						$xpath = new DOMXPath($xml);
						$depends_on_list = $xpath->query('depends', $dependency);

						foreach($depends_on_list as $depends_on){
							$depends_triggerid = get_trigger_by_description($depends_on->nodeValue);;
// sdi('<b>depends on description: </b>'.$depends_on->nodeValue.' | <b>depends_triggerid: </b>'. $depends_triggerid['triggerid']);
							if($depends_triggerid['triggerid']){
								$triggers_to_add_dep[] = $depends_triggerid['triggerid'];
							}
						}
						$r = update_trigger($current_triggerid['triggerid'],null,null,null,null,null,null,null,$triggers_to_add_dep,null);
						if($r === false){
							$result = false;
							break;
						}
					}
				}
			}

			if(!$result) return false;
			else return true;
		}
	}

}

?>
