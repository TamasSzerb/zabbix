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

class zbxXML{
	private static $xml = null;
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
				'port'			=> '',
				'snmp_port' => '',
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

	protected static function createDOMDocument(){
		$doc = new DOMDocument('1.0', 'UTF-8');
		$doc->preserveWhiteSpace = false;
		$doc->formatOutput = true;

		$root = $doc->appendChild(new DOMElement('zabbix_export'));
		$root->setAttributeNode(new DOMAttr('version', '1.0'));
		$root->setAttributeNode(new DOMAttr('date', zbx_date2str(S_EXPORT_DATE_ATTRIBUTE_DATE_FORMAT)));
		$root->setAttributeNode(new DOMAttr('time', zbx_date2str(S_EXPORT_TIME_ATTRIBUTE_DATE_FORMAT)));

		return $root;
	}

	protected static function outputXML($doc){
//		return preg_replace_callback('/^( {2,})/m', array('zbxXML', 'space2tab'), $doc->ownerDocument->saveXML());
		return $doc->ownerDocument->saveXML();
	}

	private static function space2tab($matches){
		return str_repeat("\t", zbx_strlen($matches[0]) / 2 );
	}

	public static function arrayToXML($array){
		$xml = self::createDOMDocument();

		self::arrayToDOM($xml, $array);

		return self::outputXML($xml);
	}

	public static function arrayToDOM(&$dom, $array, $parentKey=null){
		if(!is_null($parentKey)){
			$parentNode = $dom->appendChild(new DOMElement($parentKey));
		}
		else{
			$parentNode = $dom;
		}

 		foreach($array as $key => $value){
			if(is_numeric($key)) $key = rtrim($parentKey, 's');

			if(is_array($value)){
				$child = self::arrayToDOM($dom, $value, $key);
				$parentNode->appendChild($child);
//SDI($dom->saveXML($parentNode));
			}
			else if(!zbx_empty($value)){
				$n = $parentNode->appendChild(new DOMElement($key));
				$n->appendChild(new DOMText($value));
			}
		}

		return $parentNode;
	}

	public static function XMLtoArray($parentNode){
		$array = array();

		foreach($parentNode->childNodes as $node){
			if($node->nodeType == 3){
				if($node->nextSibling) continue;
				if(!$node->isWhitespaceInElementContent()) return $node->nodeValue;
			}

			if($node->hasChildNodes()){
				$nodeName = $node->nodeName;

				if(isset($array[$nodeName])) $nodeName.= count($array);
				$array[$nodeName] = self::XMLtoArray($node);
			}
		}

	return $array;
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

	public static function import($file){

		libxml_use_internal_errors(true);

		$xml = new DOMDocument();
		if(!$xml->load($file)){
			foreach(libxml_get_errors() as $error){
				$text = '';

				switch($error->level){
					case LIBXML_ERR_WARNING:
						$text .= S_XML_FILE_CONTAINS_ERRORS.'. Warning '.$error->code.': ';
					break;
					case LIBXML_ERR_ERROR:
						$text .= S_XML_FILE_CONTAINS_ERRORS.'. Error '.$error->code.': ';
					break;
					case LIBXML_ERR_FATAL:
						$text .= S_XML_FILE_CONTAINS_ERRORS.'. Fatal Error '.$error->code.': ';
					break;
				}

				$text .= trim($error->message) . ' [ Line: '.$error->line.' | Column: '.$error->column.' ]';
				error($text);
				break;
			}

			libxml_clear_errors();
			return false;
		}

		if($xml->childNodes->item(0)->nodeName != 'zabbix_export'){
			$xml2 = self::createDOMDocument();
			$xml2->appendChild($xml2->ownerDocument->importNode($xml->childNodes->item(0), true));
			self::$xml = $xml2->ownerDocument;
		}
		else
			self::$xml = $xml;

	return true;
	}

	private static function validate($schema){
		libxml_use_internal_errors(true);

		$result = self::$xml->relaxNGValidate($schema);

		if(!$result){
			$errors = libxml_get_errors();
			libxml_clear_errors();

			foreach($errors as $error){
				$text = '';

				switch($error->level){
					case LIBXML_ERR_WARNING:
						$text .= S_XML_FILE_CONTAINS_ERRORS.'. Warning '.$error->code.': ';
					break;
					case LIBXML_ERR_ERROR:
						$text .= S_XML_FILE_CONTAINS_ERRORS.'. Error '.$error->code.': ';
					break;
					case LIBXML_ERR_FATAL:
						$text .= S_XML_FILE_CONTAINS_ERRORS.'. Fatal Error '.$error->code.': ';
					break;
				}

				$text .= trim($error->message) . ' [ Line: '.$error->line.' | Column: '.$error->column.' ]';
				throw new Exception($text);
			}
		}
		return true;
	}

	public static function parseScreen($rules, $xml=null){
		try{
			self::validate(dirname(__FILE__).'/xmlschemas/screens.rng');

			$xml = is_null($xml) ? self::$xml : $xml;
			$importScreens = self::XMLtoArray($xml);
			$importScreens = $importScreens['zabbix_export']['screens'];

			$result = true;
			$screens = array();

			foreach($importScreens as $mnum => &$screen){
				unset($screen['screenid']);
				$exists = CScreen::exists(array('name' => $screen['name']));

				if($exists && isset($rules['screens']['exist'])){
					$db_screens = CScreen::get(array('filter' => array('name' => $screen['name'])));
					if(empty($db_screens)) throw new Exception(S_NO_PERMISSIONS_FOR_SCREEN.' "'.$screen['name'].'" import');

					$db_screen = reset($db_screens);

					$screen['screenid'] = $db_screen['screenid'];
				}
				else if($exists || !isset($rules['screens']['missed'])){
					info('Screen ['.$screen['name'].'] skipped - user rule');
					unset($importScreens[$mnum]);
					continue; // break if not update exist
				}

				if(!isset($screen['screenitems'])) $screen['screenitems'] = array();

				foreach($screen['screenitems'] as $snum => &$screenitem){
					$nodeCaption = isset($screenitem['resourceid']['node'])?$screenitem['resourceid']['node'].':':'';

					if(!isset($screenitem['resourceid'])) $screenitem['resourceid'] = 0;
					if(is_array($screenitem['resourceid'])){
						switch($screenitem['resourcetype']){
							case SCREEN_RESOURCE_HOSTS_INFO:
							case SCREEN_RESOURCE_TRIGGERS_INFO:
							case SCREEN_RESOURCE_TRIGGERS_OVERVIEW:
							case SCREEN_RESOURCE_DATA_OVERVIEW:
							case SCREEN_RESOURCE_HOSTGROUP_TRIGGERS:
								if(is_array($screenitem['resourceid'])){
									$db_hostgroups = CHostgroup::getObjects($screenitem['resourceid']);
									if(empty($db_hostgroups)){
										$error = S_CANNOT_FIND_HOSTGROUP.' "'.$nodeCaption.$screenitem['resourceid']['name'].'" '.S_USED_IN_EXPORTED_SCREEN_SMALL.' "'.$screen['name'].'"';
										throw new Exception($error);
									}

									$tmp = reset($db_hostgroups);
									$screenitem['resourceid'] = $tmp['groupid'];
								}
							break;
							case SCREEN_RESOURCE_HOST_TRIGGERS:
								$db_hosts = CHost::getObjects($screenitem['resourceid']);
								if(empty($db_hosts)){
									$error = S_CANNOT_FIND_HOST.' "'.$nodeCaption.$screenitem['resourceid']['host'].'" '.S_USED_IN_EXPORTED_SCREEN_SMALL.' "'.$screen['name'].'"';
									throw new Exception($error);
								}

								$tmp = reset($db_hosts);
								$screenitem['resourceid'] = $tmp['hostid'];
							break;
							case SCREEN_RESOURCE_GRAPH:
								$db_graphs = CGraph::getObjects($screenitem['resourceid']);
								if(empty($db_graphs)){
									$error = S_CANNOT_FIND_GRAPH.' "'.$nodeCaption.$screenitem['resourceid']['host'].':'.$screenitem['resourceid']['name'].'" '.S_USED_IN_EXPORTED_SCREEN_SMALL.' "'.$screen['name'].'"';
									throw new Exception($error);
								}

								$tmp = reset($db_graphs);
								$screenitem['resourceid'] = $tmp['graphid'];
							break;
							case SCREEN_RESOURCE_SIMPLE_GRAPH:
							case SCREEN_RESOURCE_PLAIN_TEXT:
								$db_items = CItem::getObjects($screenitem['resourceid']);
//SDII($db_items);
								if(empty($db_items)){
									$error = S_CANNOT_FIND_ITEM.' "'.$nodeCaption.$screenitem['resourceid']['host'].':'.$screenitem['resourceid']['key_'].'" '.S_USED_IN_EXPORTED_SCREEN_SMALL.' "'.$screen['name'].'"';
									throw new Exception($error);
								}

								$tmp = reset($db_items);
								$screenitem['resourceid'] = $tmp['itemid'];
							break;
							case SCREEN_RESOURCE_MAP:
								$db_sysmaps = CMap::getObjects($screenitem['resourceid']);
								if(empty($db_sysmaps)){
									$error = S_CANNOT_FIND_MAP.' "'.$nodeCaption.$screenitem['resourceid']['name'].'" '.S_USED_IN_EXPORTED_SCREEN_SMALL.' "'.$screen['name'].'"';
									throw new Exception($error);
								}

								$tmp = reset($db_sysmaps);
								$screenitem['resourceid'] = $tmp['sysmapid'];
							break;
							case SCREEN_RESOURCE_SCREEN:
								$db_screens = CScreen::get(array('screenids' => $screenitem['resourceid']));
								if(empty($db_screens)){
									$error = S_CANNOT_FIND_SCREEN.' "'.$nodeCaption.$screenitem['resourceid']['name'].'" '.S_USED_IN_EXPORTED_SCREEN_SMALL.' "'.$screen['name'].'"';
									throw new Exception($error);
								}

								$tmp = reset($db_screens);
								$screenitem['resourceid'] = $tmp['screenid'];
							break;
							default:
								$screenitem['resourceid'] = 0;
							break;
						}
					}
				}
				unset($screenitem);

				$screens[] = $screen;
			}
			unset($screen);

			$importScreens = $screens;

			foreach($importScreens as $mnum => $screen){
				if(isset($screen['screenid'])){
					$result = CScreen::update($screen);
				}
				else{
					$result = CScreen::create($screen);
				}

				if(isset($screen['screenid'])){
					info(S_SCREEN.' ['.$screen['name'].'] '.S_UPDATED_SMALL);
				}
				else{
					info(S_SCREEN.' ['.$screen['name'].'] '.S_ADDED_SMALL);
				}

			}
		}
		catch(Exception $e){
			error($e->getMessage());
			return false;
		}

		return $result;
	}

	public static function parseMap($rules){
		global $USER_DETAILS;
		$importMaps = self::XMLtoArray(self::$xml);

		if(!isset($importMaps['zabbix_export'])){
			$importMaps['zabbix_export'] = $importMaps;
		}

		try{
			if($USER_DETAILS['type'] == USER_TYPE_SUPER_ADMIN){
				$images = $importMaps['zabbix_export']['images'];
				$images_to_add = array();
				$images_to_update = array();
				foreach($images as $inum => $image){
					if(CImage::exists($image)){
						if((($image['imagetype'] == IMAGE_TYPE_ICON) && isset($rules['icons']['exist']))
							|| (($image['imagetype'] == IMAGE_TYPE_BACKGROUND) && (isset($rules['background']['exist']))))
						{

							$options = array(
								'filter' => array('name' => $image['name']),
								'output' => API_OUTPUT_SHORTEN
							);
							$imgs = CImage::get($options);
							$img = reset($imgs);

							$image['imageid'] = $img['imageid'];
							$image['image'] = $image['encodedImage'];
							unset($image['encodedImage']);

							$images_to_update[] = $image;
						}
					}
					else{
						if((($image['imagetype'] == IMAGE_TYPE_ICON) && isset($rules['icons']['missed']))
							|| (($image['imagetype'] == IMAGE_TYPE_BACKGROUND) && isset($rules['background']['missed'])))
						{

// No need to decode_base64
							$image['image'] = $image['encodedImage'];

							unset($image['encodedImage']);
							$images_to_add[] = $image;
						}
					}
				}
//sdi($images_to_add);
				if(!empty($images_to_add)){
					$result = CImage::create($images_to_add);
					if(!$result) throw new Exception(S_CANNOT_ADD_IMAGE);
				}
//sdi($images_to_update);
				if(!empty($images_to_update)){
					$result = CImage::update($images_to_update);
					if(!$result) throw new Exception(S_CANNOT_UPDATE_IMAGE);
				}
			}


			$importMaps = $importMaps['zabbix_export']['sysmaps'];
			$sysmaps = array();
			foreach($importMaps as $mnum => &$sysmap){
				unset($sysmap['sysmapid']);
				$exists = CMap::exists(array('name' => $sysmap['name']));

				if($exists && isset($rules['maps']['exist'])){
					$db_maps = CMap::getObjects(array('name' => $sysmap['name']));
					if(empty($db_maps)) throw new Exception(S_NO_PERMISSIONS_FOR_MAP.' ['.$sysmap['name'].'] import');

					$db_map = reset($db_maps);
					$sysmap['sysmapid'] = $db_map['sysmapid'];
				}
				else if($exists || !isset($rules['maps']['missed'])){
					info('Map ['.$sysmap['name'].'] skipped - user rule');
					unset($importMaps[$mnum]);
					continue; // break if not update exist
				}

				if(isset($sysmap['backgroundid'])){
					$image = getImageByIdent($sysmap['backgroundid']);

					if(!$image){
						error(S_CANNOT_FIND_BACKGROUND_IMAGE.' "'.$sysmap['backgroundid']['name'].'" '.S_USED_IN_EXPORTED_MAP_SMALL.' "'.$sysmap['name'].'"');
						$sysmap['backgroundid'] = 0;
					}
					else{
						$sysmap['backgroundid'] = $image['imageid'];
					}
				}
				else{
					$sysmap['backgroundid'] = 0;
				}

				if(!isset($sysmap['selements'])) $sysmap['selements'] = array();
				if(!isset($sysmap['links'])) $sysmap['links'] = array();

				foreach($sysmap['selements'] as $snum => &$selement){
					$nodeCaption = isset($selement['elementid']['node'])?$selement['elementid']['node'].':':'';

					if(!isset($selement['elementid'])) $selement['elementid'] = 0;
					switch($selement['elementtype']){
						case SYSMAP_ELEMENT_TYPE_MAP:
							$db_sysmaps = CMap::getObjects($selement['elementid']);
							if(empty($db_sysmaps)){
								$error = S_CANNOT_FIND_MAP.' "'.$nodeCaption.$selement['elementid']['name'].'" '.S_USED_IN_EXPORTED_MAP_SMALL.' "'.$sysmap['name'].'"';
								throw new Exception($error);
							}

							$tmp = reset($db_sysmaps);
							$selement['elementid'] = $tmp['sysmapid'];
						break;
						case SYSMAP_ELEMENT_TYPE_HOST_GROUP:
							$db_hostgroups = CHostgroup::getObjects($selement['elementid']);
							if(empty($db_hostgroups)){
								$error = S_CANNOT_FIND_HOSTGROUP.' "'.$nodeCaption.$selement['elementid']['name'].'" '.S_USED_IN_EXPORTED_MAP_SMALL.' "'.$sysmap['name'].'"';
								throw new Exception($error);
							}

							$tmp = reset($db_hostgroups);
							$selement['elementid'] = $tmp['groupid'];
						break;
						case SYSMAP_ELEMENT_TYPE_HOST:
							$db_hosts = CHost::getObjects($selement['elementid']);
							if(empty($db_hosts)){
								$error = S_CANNOT_FIND_HOST.' "'.$nodeCaption.$selement['elementid']['host'].'" '.S_USED_IN_EXPORTED_MAP_SMALL.' "'.$sysmap['name'].'"';
								throw new Exception($error);
							}

							$tmp = reset($db_hosts);
							$selement['elementid'] = $tmp['hostid'];
						break;
						case SYSMAP_ELEMENT_TYPE_TRIGGER:
							$db_triggers = CTrigger::getObjects($selement['elementid']);
							if(empty($db_triggers)){
								$error = S_CANNOT_FIND_TRIGGER.' "'.$nodeCaption.$selement['elementid']['host'].':'.$selement['elementid']['description'].'" '.S_USED_IN_EXPORTED_MAP_SMALL.' "'.$sysmap['name'].'"';
								throw new Exception($error);
							}

							$tmp = reset($db_triggers);
							$selement['elementid'] = $tmp['triggerid'];
						break;
						case SYSMAP_ELEMENT_TYPE_IMAGE:
						default:
						break;
					}

					$icons = array('iconid_off','iconid_on','iconid_disabled','iconid_maintenance');
					foreach($icons as $icon){
						if(isset($selement[$icon])){
							$image = getImageByIdent($selement[$icon]);
							if(!$image){
								$error = S_CANNOT_FIND_IMAGE.' "'.$selement[$icon]['name'].'" '.S_USED_IN_EXPORTED_MAP_SMALL.' "'.$sysmap['name'].'"';
								throw new Exception($error);
							}
							$selement[$icon] = $image['imageid'];
						}
						else{
							$selement[$icon] = 0;
						}
					}
				}
				unset($selement);

				foreach($sysmap['links'] as $lnum => &$link){
					if(!isset($link['linktriggers'])) continue;

					foreach($link['linktriggers'] as $ltnum => &$linktrigger){
						$nodeCaption = isset($linktrigger['triggerid']['node'])?$linktrigger['triggerid']['node'].':':'';

						$db_triggers = CTrigger::getObjects($linktrigger['triggerid']);
						if(empty($db_triggers)){
							$error = S_CANNOT_FIND_TRIGGER.' "'.$nodeCaption.$linktrigger['triggerid']['host'].':'.$linktrigger['triggerid']['description'].'" '.S_USED_IN_EXPORTED_MAP_SMALL.' "'.$sysmap['name'].'"';
							throw new Exception($error);
						}

						$tmp = reset($db_triggers);
						$linktrigger['triggerid'] = $tmp['triggerid'];
					}
					unset($linktrigger);
				}
				unset($link);

				$sysmaps[] = $sysmap;
			}
			unset($sysmap);

			$importMaps = $sysmaps;
			foreach($importMaps as $mnum => $importMap){
				$sysmap = $importMap;
				if(isset($importMap['sysmapid'])){
					$result = CMap::update($importMap);
					$sysmapids = $result['sysmapids'];

// Deleting all selements (with links)
					$db_selementids = array();
					$res = DBselect('SELECT selementid FROM sysmaps_elements WHERE sysmapid='.$sysmap['sysmapid']);
					while($db_selement = DBfetch($res)){
						$db_selementids[$db_selement['selementid']] = $db_selement['selementid'];
					}
					delete_sysmaps_element($db_selementids);
//----
				}
				else{
					$result = CMap::create($importMap);
					$sysmapids = $result['sysmapids'];
					$sysmap['sysmapid'] = reset($sysmapids);
				}

				$selements = $importMap['selements'];
				$links = $importMap['links'];

				foreach($selements as $id => $selement){
					if(!isset($selement['elementid']) || ($selement['elementid'] == 0)){
						$selement['elementid'] = 0;
						$selement['elementtype'] = SYSMAP_ELEMENT_TYPE_IMAGE;
					}

					if(!isset($selement['iconid_off']) || ($selement['iconid_off'] == 0)){
						throw new Exception(S_NO_ICON_FOR_MAP_ELEMENT.' '.$sysmap['name'].':'.$selement['label']);
					}

					$selement['sysmapid'] = $sysmap['sysmapid'];
					$selementids = CMap::addElements($selement);
					$selementid = reset($selementids);

					foreach($links as $id => &$link){
						if($link['selementid1'] == $selement['selementid']) $links[$id]['selementid1'] = $selementid;
						else if($link['selementid2'] == $selement['selementid']) $links[$id]['selementid2'] = $selementid;
					}
					unset($link);
				}

				foreach($links as $id => $link){
					if(!isset($link['linktriggers'])) $link['linktriggers'] = array();
					$link['sysmapid'] = $sysmap['sysmapid'];

					$result = CMap::addLinks($link);
				}

				if(isset($importMap['sysmapid'])){
					info(S_MAP.' ['.$sysmap['name'].'] '.S_UPDATED_SMALL);
				}
				else{
					info(S_MAP.' ['.$sysmap['name'].'] '.S_ADDED_SMALL);
				}
			}

			return true;
		}
		catch(Exception $e){
			error($e->getMessage());
			return false;
		}
	}

	public static function parseMain($rules){
		$triggers_for_dependencies = array();

		try{

			if(isset($rules['host']['exist']) || isset($rules['host']['missed'])){
				$xpath = new DOMXPath(self::$xml);

				$hosts = $xpath->query('hosts/host');

				foreach($hosts as $hnum => $host){
					$host_db = self::mapXML2arr($host, XML_TAG_HOST);

					if(!isset($host_db['status'])) $host_db['status'] = HOST_STATUS_TEMPLATE;
					$current_host = ($host_db['status'] == HOST_STATUS_TEMPLATE) ? CTemplate::exists($host_db) : CHost::exists($host_db);
					if(!$current_host && !isset($rules['host']['missed'])){
						info('Host ['.$host_db['host'].'] skipped - user rule');
						continue; // break if update nonexist
					}
					if($current_host && !isset($rules['host']['exist'])){
						info('Host ['.$host_db['host'].'] skipped - user rule');
						continue; // break if not update exist
					}


					// host will have no interfaces - we will be creating them seperatly
					$host_db['interfaces'] = null;

					// it is possible, that data is imported from 1.8, where there was only one network interface per host
					/**
					 * @todo when new XML format will be introduced, this check should be changed to XML version check
					 */
					$old_version_input = $host_db['status'] != HOST_STATUS_TEMPLATE;
					if($old_version_input){
						// rearranging host structure, so it would look more like 2.0 host
						$interfaces = array();

						// the main interface is always "agent" type
						if(!is_null($host_db['ip'])){
							$interfaces[] = array(
								'main' => INTERFACE_PRIMARY,
								'type' => INTERFACE_TYPE_AGENT,
								'useip' => $host_db['useip'],
								'ip' =>  $host_db['ip'],
								'dns' => $host_db['dns'],
								'port' => $host_db['port']
							);
						}

						// did host use ipmi? if so, adding another interface of "ipmi" type
						if(isset($host_db['useipmi']) && $host_db['useipmi']){
							// when saving host in 1.8, it's possible to set use_ipmi=1 and not to fill an IP address
							//  we were not really sure what to do with this host, and decided to take host ip address instead and show info message about this
							if($host_db['ipmi_ip'] == ''){
								$ipmi_ip = $host_db['ip'];
								info(_s('Host "%s" has "use_ipmi" parameter checked, but has no "ipmi_ip" parameter! Using host IP address as an address for IPMI interface.', $host_db['host']));
							}
							else{
								$ipmi_ip = $host_db['ipmi_ip'];
							}
							$interfaces[] = array(
								'main' => INTERFACE_SECONDARY,
								'type' => INTERFACE_TYPE_IPMI,
								'useip' => INTERFACE_USE_DNS,
								'ip' =>  '',
								'dns' => $ipmi_ip,
								'port' => $host_db['ipmi_port']
							);
						}


						// now we need to check if host had SNMP items. If it had, we need and SNMP interface for every different port.
						$items = $xpath->query('items/item', $host);
						$snmp_interface_ports_created = array();
						foreach($items as $item){
							$item_db = self::mapXML2arr($item, XML_TAG_ITEM);
							if(($item_db['type'] == ITEM_TYPE_SNMPV1
								|| $item_db['type'] == ITEM_TYPE_SNMPV2C
								|| $item_db['type'] == ITEM_TYPE_SNMPV3)
								&& !isset($snmp_interface_ports_created[$item_db['snmp_port']])
							){

								$interfaces[] = array(
									'main' => INTERFACE_SECONDARY,
									'type' => INTERFACE_TYPE_SNMP,
									'useip' => $host_db['useip'],
									'ip' =>  $host_db['ip'],
									'dns' => $host_db['dns'],
									'port' => $item_db['snmp_port']
								);
								$snmp_interface_ports_created[$item_db['snmp_port']] = 1;
							}
						}
						unset($snmp_interface_ports_created); // it was a temporary variable
					}


					if($current_host){
						$options = array(
							'filter' => array('host' => $host_db['host']),
							'output' => API_OUTPUT_EXTEND,
							'editable' => 1,
							'selectInterfaces' => API_OUTPUT_EXTEND
						);
						if($host_db['status'] == HOST_STATUS_TEMPLATE)
							$current_host = CTemplate::get($options);
						else
							$current_host = CHost::get($options);

						if(empty($current_host)){
							throw new Exception('No permission for host ['.$host_db['host'].']');
						}
						else{
							$current_host = reset($current_host);
						}


						// checking if host already exists - then some of the interfaces may not need to be created
						if($host_db['status'] != HOST_STATUS_TEMPLATE){
							// for every interface we got based on XML
							foreach($interfaces as $i => $interface_db){
								// checking every interface of current host
								foreach($current_host['interfaces'] as $interface){
									// if all parameters of interface are identical
									if(
										$interface['type'] == $interface_db['type']
										&& $interface['ip'] == $interface_db['ip']
										&& $interface['dns'] == $interface_db['dns']
										&& $interface['port'] == $interface_db['port']
										&& $interface['useip'] == $interface_db['useip']
									){
										// this interface is the same as existing one!
										$interfaces[$i]['interfaceid'] = $interface['interfaceid'];
										break;
									}
								}
							}

						}
						$interfaces_created_with_host = false;
					}
					else{
						$host_db['interfaces'] = $interfaces;
						$interfaces_created_with_host = true;
					}

// HOST GROUPS {{{
					$groups = $xpath->query('groups/group', $host);

					$host_db['groups'] = array();
					$groups_to_parse = array();
					foreach($groups as $gnum => $group){
						$groups_to_parse[] = array('name' => $group->nodeValue);
					}
					if(empty($groups_to_parse)){
						$groups_to_parse[] = array('name' => ZBX_DEFAULT_IMPORT_HOST_GROUP);
					}

					foreach($groups_to_parse as $group){
						$current_group = CHostGroup::exists($group);

						if($current_group){
							$options = array(
								'filter' => $group,
								'output' => API_OUTPUT_EXTEND,
								'editable' => 1
							);
							$current_group = CHostGroup::get($options);
							if(empty($current_group)){
								throw new Exception('No permissions for group '. $group['name']);
							}

							$host_db['groups'][] = reset($current_group);
						}
						else{
							$result = CHostGroup::create($group);
							if(!$result){
								throw new Exception();
							}

							$options = array(
								'groupids' => $result['groupids'],
								'output' => API_OUTPUT_EXTEND
							);
							$new_group = CHostgroup::get($options);

							$host_db['groups'][] = reset($new_group);
						}
					}
// }}} HOST GROUPS


// MACROS
					$macros = $xpath->query('macros/macro', $host);
					if($macros->length > 0){
						$host_db['macros'] = array();
						foreach($macros as $macro){
							$host_db['macros'][] = self::mapXML2arr($macro, XML_TAG_MACRO);
						}
					}
// }}} MACROS


// TEMPLATES {{{
					if(isset($rules['template']['exist'])){
						$templates = $xpath->query('templates/template', $host);

						$host_db['templates'] = array();
						foreach($templates as $tnum => $template){

							$options = array(
								'filter' => array('host' => $template->nodeValue),
								'output' => API_OUTPUT_EXTEND,
								'editable' => 1
							);
							$current_template = CTemplate::get($options);

							if(empty($current_template)){
								throw new Exception('No permission for Template ['.$template->nodeValue.']');
							}

							$current_template = reset($current_template);


							if(!$current_template && !isset($rules['template']['missed'])){
								info('Template ['.$template->nodeValue.'] skipped - user rule');
								continue;
							}
							if($current_template && !isset($rules['template']['exist'])){
								info('Template ['.$template->nodeValue.'] skipped - user rule');
								continue;
							}

							$host_db['templates'][] = $current_template;
						}
					}
// }}} TEMPLATES


// HOST PROFILES {{{
					$profile_node = $xpath->query('host_profile/*', $host);
					if($profile_node->length > 0){
						$host_db['profile'] = array();
						foreach($profile_node as $num => $field){
							$host_db['profile'][$field->nodeName] = $field->nodeValue;
						}
					}

					$profile_ext_node = $xpath->query('host_profiles_ext/*', $host);
					if($profile_ext_node->length > 0){
						$host_db['extendedProfile'] = array();
						foreach($profile_ext_node as $num => $field){
							$host_db['extendedProfile'][$field->nodeName] = $field->nodeValue;
						}
					}
// }}} HOST PROFILES

// HOSTS
					if(isset($host_db['proxy_hostid'])){
						$proxy_exists = CProxy::get(array('proxyids' => $host_db['proxy_hostid']));
						if(empty($proxy_exists))
							$host_db['proxy_hostid'] = 0;
					}

					if($current_host && isset($rules['host']['exist'])){
						if($host_db['status'] == HOST_STATUS_TEMPLATE){
							$host_db['templateid'] = $current_host['hostid'];
							$result = CTemplate::update($host_db);
						}
						else{
							$host_db['hostid'] = $current_host['hostid'];
							$result = CHost::update($host_db);
						}
						if(!$result)
							throw new Exception();
						$current_hostid = $current_host['hostid'];
					}

					if(!$current_host && isset($rules['host']['missed'])){
						if($host_db['status'] == HOST_STATUS_TEMPLATE){
							$result = CTemplate::create($host_db);
							if(!$result)
								throw new Exception();
							$current_hostid = reset($result['templateids']);
						}
						else{
							$result = CHost::create($host_db);
							if(!$result)
								throw new Exception();
							$current_hostid = reset($result['hostids']);
						}
					}
					$current_hostname = $host_db['host'];

// ITEMS {{{
					if(isset($rules['item']['exist']) || isset($rules['item']['missed'])){
						$items = $xpath->query('items/item', $host);

						// if this is an export from 1.8, we need to make some adjustments to items
						if($old_version_input){
							if(!$interfaces_created_with_host){
								// if host had another interfaces, we are not touching them: they remain as is
								foreach($interfaces as $i => $interface){
									// interface was not already created
									if(!isset($interface['interfaceid'])){
										// creating interface
										$interface['hostid'] = $current_hostid;
										$ids = CHostInterface::create($interface);
										if($ids === false) throw new Exception();
										$interfaces[$i]['interfaceid'] = reset($ids['interfaceids']);
									}
								}
							}
							else{
								$options = array(
									'hostids' => $current_hostid,
									'output' => API_OUTPUT_EXTEND
								);
								$interfaces = CHostInterface::get($options);
							}



							// we must know interface ids to assign them to items
							$agent_interface_id = null;
							$ipmi_interface_id = null;
							$snmp_interfaces = array(); //hash 'port' => 'iterfaceid'

							foreach($interfaces as $interface){
								switch($interface['type']){
									case INTERFACE_TYPE_AGENT:
										$agent_interface_id = $interface['interfaceid'];
									break;
									case INTERFACE_TYPE_IPMI:
										$ipmi_interface_id = $interface['interfaceid'];
									break;
									case INTERFACE_TYPE_SNMP:
										$snmp_interfaces[$interface['port']] = $interface['interfaceid'];
									break;
								}
							}
						}

						foreach($items as $inum => $item){
							$item_db = self::mapXML2arr($item, XML_TAG_ITEM);
							$item_db['hostid'] = $current_hostid;

							// item needs interfaces
							if($old_version_input){
								// 'snmp_port' column was renamed to 'port'
								if($item_db['snmp_port'] != 0){
									// zabbix agent items have no ports
									$item_db['port'] = $item_db['snmp_port'];
								}
								unset($item_db['snmp_port']);

								// assigning appropriate interface depending on item type
								switch($item_db['type']){
									// zabbix agent interface
									case ITEM_TYPE_ZABBIX:
									case ITEM_TYPE_SIMPLE:
									case ITEM_TYPE_EXTERNAL:
									case ITEM_TYPE_DB_MONITOR:
									case ITEM_TYPE_SSH:
									case ITEM_TYPE_TELNET:
										$item_db['interfaceid'] = $agent_interface_id;
										break;
									// snmp interface
									case ITEM_TYPE_SNMPV1:
									case ITEM_TYPE_SNMPV2C:
									case ITEM_TYPE_SNMPV3:
										// for an item with different port - different interface
										$item_db['interfaceid'] = $snmp_interfaces[$item_db['port']];
										break;
									case ITEM_TYPE_IPMI:
										$item_db['interfaceid'] = $ipmi_interface_id;
										break;
									// no interfaces required for these item types
									case ITEM_TYPE_HTTPTEST:
									case ITEM_TYPE_CALCULATED:
									case ITEM_TYPE_AGGREGATE:
									case ITEM_TYPE_INTERNAL:
									case ITEM_TYPE_ZABBIX_ACTIVE:
									case ITEM_TYPE_TRAPPER:
										$item_db['interfaceid'] = null;
										break;
								}

							}

							$options = array(
								'filter' => array(
									'hostid' => $item_db['hostid'],
									'key_' => $item_db['key_'],
									'flags' => array(ZBX_FLAG_DISCOVERY_NORMAL, ZBX_FLAG_DISCOVERY_CREATED),
								),
								'webitems' => 1,
								'output' => API_OUTPUT_EXTEND,
								'editable' => 1
							);
							$current_item = CItem::get($options);
							$current_item = reset($current_item);

							if(!$current_item && !isset($rules['item']['missed'])){
								info('Item ['.$item_db['key_'].'] skipped - user rule');
								continue; // break if not update exist
							}
							if($current_item && !isset($rules['item']['exist'])){
								info('Item ['.$item_db['key_'].'] skipped - user rule');
								continue; // break if not update exist
							}


// ITEM APPLICATIONS {{{
							$applications = $xpath->query('applications/application', $item);

							$item_applications = array();
							$applications_to_add = array();

							foreach($applications as $application){
								$application_db = array(
									'name' => $application->nodeValue,
									'hostid' => $current_hostid
								);

								$current_application = CApplication::get(array(
									'filter' => $application_db,
									'output' => API_OUTPUT_EXTEND
								));


								if($current_application){
									$item_applications = array_unique(array_merge($item_applications, $current_application));
								}
								else
									$applications_to_add[] = $application_db;
							}

							if(!empty($applications_to_add)){
								$result = CApplication::create($applications_to_add);
								if(!$result){
									throw new Exception();
								}

								$options = array(
									'applicationids' => $result['applicationids'],
									'output' => API_OUTPUT_EXTEND
								);
								$new_applications = CApplication::get($options);

								$item_applications = array_merge($item_applications, $new_applications);
							}
// }}} ITEM APPLICATIONS

							if($current_item && isset($rules['item']['exist'])){
								$item_db['itemid'] = $current_item['itemid'];
								$result = CItem::update($item_db);
								if(!$result){
									throw new Exception();
								}

								$options = array(
									'itemids' => $result['itemids'],
									'webitems' => 1,
									'output' => API_OUTPUT_EXTEND
								);
								$current_item = CItem::get($options);
							}


							if(!$current_item && isset($rules['item']['missed'])){
								$result = CItem::create($item_db);
								if(!$result){
									throw new Exception();
								}

								$options = array(
									'itemids' => $result['itemids'],
									'webitems' => 1,
									'output' => API_OUTPUT_EXTEND
								);
								$current_item = CItem::get($options);
							}

							if(!empty($item_applications)){
								$r = CApplication::massAdd(array(
									'applications' => $item_applications,
									'items' => $current_item
								));
								if($r === false){
									throw new Exception();
								}
							}
						}
					}
// }}} ITEMS


// TRIGGERS {{{
					if(isset($rules['trigger']['exist']) || isset($rules['trigger']['missed'])){
						$triggers = $xpath->query('triggers/trigger', $host);

						$triggers_to_add = array();
						$triggers_to_upd = array();

						foreach($triggers as $trigger){
							$trigger_db = self::mapXML2arr($trigger, XML_TAG_TRIGGER);

							$trigger_db['expression'] = str_replace('{{HOSTNAME}:', '{'.$host_db['host'].':', $trigger_db['expression']);
							$trigger_db['hostid'] = $current_hostid;

							if($current_trigger = CTrigger::exists($trigger_db)){
								$ctriggers = CTrigger::get(array(
									'filter' => array(
										'description' => $trigger_db['description']
									),
									'hostids' => $current_hostid,
									'output' => API_OUTPUT_EXTEND,
									'editable' => 1
								));

								$current_trigger = false;
								foreach($ctriggers as $tnum => $ct){
									$tmp_exp = explode_exp($ct['expression'], false);
									if(strcmp($trigger_db['expression'], $tmp_exp) == 0){
										$current_trigger = $ct;
										break;
									}
								}
								if(!$current_trigger){
									throw new Exception(_s('No permission for trigger "%s"', $trigger_db['description']));
								}
							}


							if(!$current_trigger && !isset($rules['trigger']['missed'])){
								info('Trigger ['.$trigger_db['description'].'] skipped - user rule');
								continue; // break if not update exist
							}
							if($current_trigger && !isset($rules['trigger']['exist'])){
								info('Trigger ['.$trigger_db['description'].'] skipped - user rule');
								continue; // break if not update exist
							}

							if($current_trigger && isset($rules['trigger']['exist'])){
								$trigger_db['triggerid'] = $current_trigger['triggerid'];
								$triggers_to_upd[] = $trigger_db;
							}
							if(!$current_trigger && isset($rules['trigger']['missed'])){
								$triggers_to_add[] = $trigger_db;
							}
						}

						if(!empty($triggers_to_upd)){
							$result = CTrigger::update($triggers_to_upd);
							if(!$result){
								throw new Exception();
							}

							$options = array(
								'triggerids' => $result['triggerids'],
								'output' => API_OUTPUT_EXTEND
							);
							$r = CTrigger::get($options);

							$triggers_for_dependencies = array_merge($triggers_for_dependencies, $r);
						}
						if(!empty($triggers_to_add)){
							$result = CTrigger::create($triggers_to_add);
							if(!$result){
								throw new Exception();
							}

							$options = array(
								'triggerids' => $result['triggerids'],
								'output' => API_OUTPUT_EXTEND
							);
							$r = CTrigger::get($options);
							$triggers_for_dependencies = array_merge($triggers_for_dependencies, $r);
						}
					}
// }}} TRIGGERS


// GRAPHS {{{
					if(isset($rules['graph']['exist']) || isset($rules['graph']['missed'])){
						$graphs = $xpath->query('graphs/graph', $host);

						$graphs_to_add = array();
						$graphs_to_upd = array();
						foreach($graphs as $gnum => $graph){
// GRAPH ITEMS {{{
							$gitems = $xpath->query('graph_elements/graph_element', $graph);

							$graph_hostids = array();
							$graph_items = array();
							foreach($gitems as $ginum => $gitem){
								$gitem_db = self::mapXML2arr($gitem, XML_TAG_GRAPH_ELEMENT);

								$data = explode(':', $gitem_db['host_key_']);
								$gitem_host = array_shift($data);
								$gitem_db['host'] = ($gitem_host == '{HOSTNAME}') ? $host_db['host'] : $gitem_host;
								$gitem_db['key_'] = implode(':', $data);

								if($current_item = CItem::exists($gitem_db)){
									$current_item = CItem::get(array(
										'filter' => array(
											'key_' => $gitem_db['key_'],
											'flags' => array(ZBX_FLAG_DISCOVERY_NORMAL, ZBX_FLAG_DISCOVERY_CREATED),
										),
										'webitems' => 1,
										'host' => $gitem_db['host'],
										'output' => API_OUTPUT_EXTEND,
										'editable' => 1
									));
									if(empty($current_item)){
										throw new Exception('No permission for Item ['.$gitem_db['key_'].']');
									}
									$current_item = reset($current_item);

									$graph_hostids[] = $current_item['hostid'];
									$gitem_db['itemid'] = $current_item['itemid'];
									$graph_items[] = $gitem_db;
								}
								else{
									throw new Exception('Item ['.$gitem_db['host_key_'].'] does not exists');
								}
							}
// }}} GRAPH ITEMS

							$graph_db = self::mapXML2arr($graph, XML_TAG_GRAPH);
							$graph_db['hostids'] = $graph_hostids;

							$current_graph = CGraph::exists($graph_db);

							if($current_graph){
								$current_graph = CGraph::get(array(
									'filter' => array('name' => $graph_db['name']),
									'hostids' => $graph_db['hostids'],
									'output' => API_OUTPUT_EXTEND,
									'editable' => 1
								));

								if(empty($current_graph)){
									throw new Exception('No permission for Graph ['.$graph_db['name'].']');
								}
								$current_graph = reset($current_graph);
							}

							if(!$current_graph && !isset($rules['graph']['missed'])){
								info('Graph ['.$graph_db['name'].'] skipped - user rule');
								continue; // break if not update exist
							}
							if($current_graph && !isset($rules['graph']['exist'])){
								info('Graph ['.$graph_db['name'].'] skipped - user rule');
								continue; // break if not update exist
							}

							if(!isset($graph_db['ymin_type'])) {
								throw new APIException(1, _s('No "ymin_type" field for graph "%s"', $graph_db['name']));
							}

							if(!isset($graph_db['ymax_type'])) {
								throw new APIException(1, _s('No "ymax_type" field for graph "%s"', $graph_db['name']));
							}

							if($graph_db['ymin_type'] == GRAPH_YAXIS_TYPE_ITEM_VALUE){
								$item_data = explode(':', $graph_db['ymin_item_key'], 2);
								if(count($item_data) < 2){
									throw new APIException(1, 'Incorrect y min item for graph ['.$graph_db['name'].']');
								}

								if(!$item = get_item_by_key($item_data[1], $item_data[0])){
									throw new APIException(1, 'Missed item ['.$graph_db['ymin_item_key'].'] for host ['.$host_db['host'].']');
								}

								$graph_db['ymin_itemid'] = $item['itemid'];
							}

							if($graph_db['ymax_type'] == GRAPH_YAXIS_TYPE_ITEM_VALUE){
								$item_data = explode(':', $graph_db['ymax_item_key'], 2);
								if(count($item_data) < 2){
									throw new APIException(1, 'Incorrect y max item for graph ['.$graph_db['name'].']');
								}

								if(!$item = get_item_by_key($item_data[1], $item_data[0])){
									throw new APIException(1, 'Missed item ['.$graph_db['ymax_item_key'].'] for host ['.$host_db['host'].']');
								}

								$graph_db['ymax_itemid'] = $item['itemid'];
							}


							$graph_db['gitems'] = $graph_items;
							if($current_graph){
								$graph_db['graphid'] = $current_graph['graphid'];
								$graphs_to_upd[] = $graph_db;
							}
							else{
								$graphs_to_add[] = $graph_db;
							}
						}


						if(!empty($graphs_to_add)){
							$r = CGraph::create($graphs_to_add);
							if($r === false){
								throw new Exception();
							}
						}
						if(!empty($graphs_to_upd)){
							$r = CGraph::update($graphs_to_upd);
							if($r === false){
								throw new Exception();
							}
						}
					}

// SCREENS
					if(isset($rules['screens']['exist']) || isset($rules['screens']['missed'])){
						$screens_node = $xpath->query('screens', $host);

						if($screens_node->length > 0){
							$importScreens = self::XMLtoArray($screens_node->item(0));

							foreach($importScreens as $mnum => $screen){

								$current_screen = CTemplateScreen::get(array(
									'filter' => array('name' => $screen['name']),
									'templateids' => $current_hostid,
									'output' => API_OUTPUT_EXTEND,
									'editable' => 1,
								));
								$current_screen = reset($current_screen);

								if(!$current_screen && !isset($rules['screens']['missed'])){
									info('Screen ['.$screen['name'].'] skipped - user rule');
									continue;
								}
								if($current_screen && !isset($rules['screens']['exist'])){
									info('Screen ['.$screen['name'].'] skipped - user rule');
									continue;
								}

								if(isset($screen['screenitems'])){
									foreach($screen['screenitems'] as $snum => &$screenitem){
										$nodeCaption = isset($screenitem['resourceid']['node'])?$screenitem['resourceid']['node'].':':'';

										if(!isset($screenitem['resourceid']))
											$screenitem['resourceid'] = 0;

										if(is_array($screenitem['resourceid'])){
											switch($screenitem['resourcetype']){
												case SCREEN_RESOURCE_GRAPH:
													$db_graphs = CGraph::getObjects($screenitem['resourceid']);

													if(empty($db_graphs)){
														$error = S_CANNOT_FIND_GRAPH.' "'.$nodeCaption.$screenitem['resourceid']['host'].':'.$screenitem['resourceid']['name'].'" '.S_USED_IN_EXPORTED_SCREEN_SMALL.' "'.$screen['name'].'"';
														throw new Exception($error);
													}

													$tmp = reset($db_graphs);
													$screenitem['resourceid'] = $tmp['graphid'];
												break;
												case SCREEN_RESOURCE_SIMPLE_GRAPH:
												case SCREEN_RESOURCE_PLAIN_TEXT:
													$db_items = CItem::getObjects($screenitem['resourceid']);

													if(empty($db_items)){
														$error = S_CANNOT_FIND_ITEM.' "'.$nodeCaption.$screenitem['resourceid']['host'].':'.$screenitem['resourceid']['key_'].'" '.S_USED_IN_EXPORTED_SCREEN_SMALL.' "'.$screen['name'].'"';
														throw new Exception($error);
													}

													$tmp = reset($db_items);
													$screenitem['resourceid'] = $tmp['itemid'];
												break;
												default:
													$screenitem['resourceid'] = 0;
												break;
											}
										}
									}
								}

								$screen['templateid'] = $current_hostid;
								if($current_screen){
									$screen['screenid'] = $current_screen['screenid'];

									$result = CTemplateScreen::update($screen);
									if(!$result) throw new Exception('Cannot update screen');

									info('['.$current_hostname.'] '.S_SCREEN.' ['.$screen['name'].'] '.S_UPDATED_SMALL);
								}
								else{
									$result = CTemplateScreen::create($screen);
									if(!$result) throw new Exception('Cannot create screen');

									info('['.$current_hostname.'] '.S_SCREEN.' ['.$screen['name'].'] '.S_ADDED_SMALL);
								}
							}
						}
					}

				}

// DEPENDENCIES
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
							$depends_on_list = $xpath->query('depends', $dependency);

							foreach($depends_on_list as $depends_on){
								$depends_triggerid = get_trigger_by_description($depends_on->nodeValue);;
// sdi('<b>depends on description: </b>'.$depends_on->nodeValue.' | <b>depends_triggerid: </b>'. $depends_triggerid['triggerid']);
								if($depends_triggerid['triggerid']){
									$triggers_to_add_dep[] = $depends_triggerid['triggerid'];
								}
							}
							$r = CTrigger::update(array(
								'triggerid' => $current_triggerid['triggerid'],
								'dependencies' => $triggers_to_add_dep,
							));
							if($r === false){
								throw new Exception();
							}
						}
					}
				}
			}

			return true;
		}
		catch(Exception $e){
			$mes = $e->getMessage();
			if(!empty($mes)) error($mes);
			return false;
		}
	}

	public static function export($data){
		$root = self::createDOMDocument();

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
						$n = $groups_node->appendChild(new DOMElement(XML_TAG_GROUP));
						$n->appendChild(new DOMText($group['name']));
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
									$n = $applications_node->appendChild(new DOMElement(XML_TAG_APPLICATION));
									$n->appendChild(new DOMText($application['name']));
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
						$n = $templates_node->appendChild(new DOMElement(XML_TAG_TEMPLATE));
						$n->appendChild(new DOMText($template['host']));
					}
				}
			}

// SCREENS
			if(isset($data['screens'])){
				$screens_node = $host_node->appendChild(new DOMElement(XML_TAG_SCREENS));

				foreach($data['screens'] as $screen){
					if($screen['templateid'] == $host['hostid']){
						unset($screen['screenid'], $screen['templateid']);
						self::arrayToDOM($screens_node, $screen, XML_TAG_SCREEN);
					}
				}
			}

// GRAPHS
			if(isset($data['graphs'])){
				$graphs_node = $host_node->appendChild(new DOMElement(XML_TAG_GRAPHS));
				$itemminmaxids = array();

				foreach($data['graphs'] as $num => $graph){
					if($graph['ymin_itemid'] && ($graph['ymin_type'] == GRAPH_YAXIS_TYPE_ITEM_VALUE))
						$itemminmaxids[$graph['ymin_itemid']] = $graph['ymin_itemid'];
					if($graph['ymax_itemid'] && ($graph['ymax_type'] == GRAPH_YAXIS_TYPE_ITEM_VALUE))
						$itemminmaxids[$graph['ymax_itemid']] = $graph['ymax_itemid'];
				}

				$options = array(
					'itemids' => $itemminmaxids,
					'output' => API_OUTPUT_EXTEND,
					'templated_hosts' => 1,
					'nopermissions' => 1
				);
				$itemminmaxs = CItem::get($options);
				$itemminmaxs = zbx_toHash($itemminmaxs, 'itemid');


				$hostminmaxs = CHost::get($options);
				$hostminmaxs = zbx_toHash($hostminmaxs, 'hostid');


				foreach($data['graphs'] as $num => $graph){
					$graph['hosts'] = zbx_toHash($graph['hosts'], 'hostid');

					if(isset($graph['hosts'][$host['hostid']])){

						if($graph['ymin_type'] == GRAPH_YAXIS_TYPE_ITEM_VALUE){
							$graph['ymin_item_key'] = $hostminmaxs[$itemminmaxs[$graph['ymin_itemid']]['hostid']]['host'].':'.
									$itemminmaxs[$graph['ymin_itemid']]['key_'];
						}
						else{
							$graph['ymin_item_key'] = '';
						}

						if($graph['ymax_type'] == GRAPH_YAXIS_TYPE_ITEM_VALUE){
							$graph['ymax_item_key'] = $hostminmaxs[$itemminmaxs[$graph['ymax_itemid']]['hostid']]['host'].':'.
									$itemminmaxs[$graph['ymax_itemid']]['key_'];
						}
						else{
							$graph['ymax_item_key'] = '';
						}

						$graph_node = self::addChildData($graphs_node, XML_TAG_GRAPH, $graph);

						if(isset($data['graphs_items'])){
							$graph_elements_node = $graph_node->appendChild(new DOMElement(XML_TAG_GRAPH_ELEMENTS));
							foreach($data['graphs_items'] as $ginum => $gitem){
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
						$n = $dependeny_node->appendChild(new DOMElement('depends'));
						$n->appendChild(new DOMText($dep_trigger['host_description']));
					};
				}
			}

		return self::outputXML($root);
	}


}

?>
