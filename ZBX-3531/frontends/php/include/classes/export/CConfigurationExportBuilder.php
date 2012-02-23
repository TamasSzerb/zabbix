<?php

class CConfigurationExportBuilder {

	const EXPORT_VERSION = '2.0';

	private $data = array();

	public function __construct() {
		$this->data['version'] = self::EXPORT_VERSION;
		$this->data['date'] = date('Y-m-d\TH:i:s\Z', time() - date('Z'));
	}

	/**
	 * @return array
	 */
	public function getExport() {
		return array('zabbix_export' => $this->data);
	}

	public function buildGroups(array $groups) {
		$this->data['groups'] = $this->formatGroups($groups);
	}

	public function buildTemplates(array $templates) {
		order_result($templates, 'host');
		$this->data['templates'] = array();

		foreach ($templates as $template) {
			$this->data['templates'][] = array(
				'template' => $template['host'],
				'name' => $template['name'],
				'groups' => $this->formatGroups($template['groups']),
				'applications' => $this->formatApplications($template['applications']),
				'items' => $this->formatItems($template['items']),
				'discovery_rules' => $this->formatDiscoveryRules($template['discoveryRules']),
				'macros' => $this->formatMacros($template['macros']),
				'templates' => $template['templates'],
				'screens' => $this->formatScreens($template['screens']),
			);
		}
	}

	public function buildHosts($hosts) {
		order_result($hosts, 'host');
		$this->data['hosts'] = array();

		foreach ($hosts as $host) {
			$references = array(
				'num' => 1,
				'refs' => array()
			);

			foreach ($host['interfaces'] as &$interface) {
				$refNum = $references['num']++;
				$referenceKey = 'if'.$refNum;
				$interface['interface_ref'] = $referenceKey;
				$references['refs'][$interface['interfaceid']] = $referenceKey;
			}
			unset($interface);

			foreach ($host['items'] as &$item) {
				$item['interface_ref'] = $references['refs'][$item['interfaceid']];
			}
			unset($item);

			foreach ($host['discoveryRules'] as &$discoveryRule) {
				$discoveryRule['interface_ref'] = $references['refs'][$discoveryRule['interfaceid']];

				foreach ($discoveryRule['itemPrototypes'] as &$prototype) {
					$prototype['interface_ref'] = $references['refs'][$prototype['interfaceid']];
				}
				unset($prototype);
			}
			unset($discoveryRule);

			$this->data['hosts'][] = array(
				'host' => $host['host'],
				'name' => $host['name'],
				'proxyid' => $host['proxy_hostid'],
				'status' => $host['status'],
				'ipmi_authtype' => $host['ipmi_authtype'],
				'ipmi_privilege' => $host['ipmi_privilege'],
				'ipmi_username' => $host['ipmi_username'],
				'ipmi_password' => $host['ipmi_password'],
				'templates' => $this->formatTemplateLinkage($host['parentTemplates']),
				'groups' => $this->formatGroups($host['groups']),
				'interfaces' => $this->formatHostInterfaces($host['interfaces']),
				'applications' => $this->formatApplications($host['applications']),
				'items' => $this->formatItems($host['items']),
				'discovery_rules' => $this->formatDiscoveryRules($host['discoveryRules']),
				'macros' => $this->formatMacros($host['macros']),
				'inventory' => $this->formatHostInventory($host['inventory'])
			);
		}
	}

	public function buildGraphs(array $graphs) {
		$this->data['graphs'] = $this->formatGraphs($graphs);
	}

	public function buildTriggers(array $triggers) {
		$this->data['triggers'] = $this->formatTriggers($triggers);
	}

	public function buildScreens(array $screens) {
		$this->data['groups'] = $this->formatScreens($screens);
	}

	public function buildImages(array $images) {
		$this->data['images'] = array();

		foreach ($images as $image) {
			$this->data['images'][] = array(
				'name' => $image['name'],
				'imagetype' => $image['imagetype'],
				'encodedImage' => $image['encodedImage'],
			);
		}
	}

	public function buildMaps(array $maps) {
		order_result($maps, 'name');
		$this->data['maps'] = array();

		foreach ($maps as $map) {
			$this->data['maps'][] = array(
				'name' => $map['name'],
				'width' => $map['width'],
				'height' => $map['height'],
				'label_type' => $map['label_type'],
				'label_location' => $map['label_location'],
				'highlight' => $map['highlight'],
				'expandproblem' => $map['expandproblem'],
				'markelements' => $map['markelements'],
				'show_unack' => $map['show_unack'],
				'grid_size' => $map['grid_size'],
				'grid_show' => $map['grid_show'],
				'grid_align' => $map['grid_align'],
				'label_format' => $map['label_format'],
				'label_type_host' => $map['label_type_host'],
				'label_type_hostgroup' => $map['label_type_hostgroup'],
				'label_type_trigger' => $map['label_type_trigger'],
				'label_type_map' => $map['label_type_map'],
				'label_type_image' => $map['label_type_image'],
				'label_string_host' => $map['label_string_host'],
				'label_string_hostgroup' => $map['label_string_hostgroup'],
				'label_string_trigger' => $map['label_string_trigger'],
				'label_string_map' => $map['label_string_map'],
				'label_string_image' => $map['label_string_image'],
				'expand_macros' => $map['expand_macros'],
				'iconmap' => $map['iconmap'],
				'urls' => $this->formatMapUrls($map['urls']),
				'selements' => $this->formatMapElements($map['selements']),
				'links' => $this->formatMapLinks($map['links'])
			);
		}
	}

	private function formatDiscoveryRules(array $discoveryRules) {
		$result = array();
		order_result($discoveryRules, 'name');

		foreach ($discoveryRules as $discoveryRule) {
			$data = array(
				'name' => $discoveryRule['name'],
				'type' => $discoveryRule['type'],
				'multiplier' => $discoveryRule['multiplier'],
				'snmp_community' => $discoveryRule['snmp_community'],
				'snmp_oid' => $discoveryRule['snmp_oid'],
				'key' => $discoveryRule['key_'],
				'delay' => $discoveryRule['delay'],
				'status' => $discoveryRule['status'],
				'allowed_hosts' => $discoveryRule['trapper_hosts'],
				'snmpv3_securityname' => $discoveryRule['snmpv3_securityname'],
				'snmpv3_securitylevel' => $discoveryRule['snmpv3_securitylevel'],
				'snmpv3_authpassphrase' => $discoveryRule['snmpv3_authpassphrase'],
				'snmpv3_privpassphrase' => $discoveryRule['snmpv3_privpassphrase'],
				'delay_flex' => $discoveryRule['delay_flex'],
				'params' => $discoveryRule['params'],
				'ipmi_sensor' => $discoveryRule['ipmi_sensor'],
				'authtype' => $discoveryRule['authtype'],
				'username' => $discoveryRule['username'],
				'password' => $discoveryRule['password'],
				'publickey' => $discoveryRule['publickey'],
				'privatekey' => $discoveryRule['privatekey'],
				'port' => $discoveryRule['port'],
				'description' => $discoveryRule['description'],
				'item_prototypes' => $this->formatItems($discoveryRule['itemPrototypes']),
				'trigger_prototypes' => $this->formatTriggers($discoveryRule['triggerPrototypes']),
				'graph_prototypes' => $this->formatGraphs($discoveryRule['graphPrototypes']),
			);
			if (isset($discoveryRule['interface_ref'])) {
				$data['interface_ref'] = $discoveryRule['interface_ref'];
			}
			$result[] = $data;
		}
		return $result;
	}

	private function formatHostInventory(array $inventory) {
		unset($inventory['hostid']);
		return $inventory;
	}

	private function formatGraphs(array $graphs) {
		$result = array();
		order_result($graphs, 'name');

		foreach ($graphs as $graph) {
			$result[] = array(
				'name' => $graph['name'],
				'width' => $graph['width'],
				'height' => $graph['height'],
				'yaxismin' => $graph['yaxismin'],
				'yaxismax' => $graph['yaxismax'],
				'show_work_period' => $graph['show_work_period'],
				'show_triggers' => $graph['show_triggers'],
				'type' => $graph['graphtype'],
				'show_legend' => $graph['show_legend'],
				'show_3d' => $graph['show_3d'],
				'percent_left' => $graph['percent_left'],
				'percent_right' => $graph['percent_right'],
				'ymin_type_1' => $graph['ymin_type'],
				'ymax_type_1' => $graph['ymax_type'],
				'ymin_item_1' => $graph['ymin_itemid'],
				'ymax_item_1' => $graph['ymax_itemid'],
				'graph_items' => $this->formatGraphItems($graph['gitems'])
			);
		}

		return $result;
	}

	private function formatTemplateLinkage(array $templates) {
		$result = array();
		order_result($templates, 'host');

		foreach ($templates as $template) {
			$result[] = array(
				'name' => $template['host'],
			);
		}
		return $result;
	}

	private function formatTriggers(array $triggers) {
		$result = array();
		order_result($triggers, 'description');

		foreach ($triggers as $trigger) {
			$result[] = array(
				'expression' => $trigger['expression'],
				'name' => $trigger['description'],
				'url' => $trigger['url'],
				'status' => $trigger['status'],
				'priority' => $trigger['priority'],
				'description' => $trigger['comments'],
				'type' => $trigger['type'],
				'dependencies' => $this->formatDependencies($trigger['dependencies'])
			);
		}
		return $result;
	}

	private function formatHostInterfaces(array $interfaces) {
		$result = array();
		order_result($interfaces, 'ip');

		foreach ($interfaces as $interface) {
			$result[] = array(
				'default' => $interface['main'],
				'type' => $interface['type'],
				'useip' => $interface['useip'],
				'ip' => $interface['ip'],
				'dns' => $interface['dns'],
				'port' => $interface['port'],
				'interface_ref' => $interface['interface_ref']
			);
		}
		return $result;
	}

	private function formatGroups(array $groups) {
		$result = array();
		order_result($groups, 'name');

		foreach ($groups as $group) {
			$result[] = array(
				'name' => $group['name']
			);
		}
		return $result;
	}

	private function formatItems(array $items) {
		$result = array();
		order_result($items, 'name');

		foreach ($items as $item) {
			$data = array(
				'name' => $item['name'],
				'type' => $item['type'],
				'snmp_community' => $item['snmp_community'],
				'multiplier' => $item['multiplier'],
				'snmp_oid' => $item['snmp_oid'],
				'key' => $item['key_'],
				'delay' => $item['delay'],
				'history' => $item['history'],
				'trends' => $item['trends'],
				'status' => $item['status'],
				'value_type' => $item['value_type'],
				'allowed_hosts' => $item['trapper_hosts'],
				'units' => $item['units'],
				'delta' => $item['delta'],
				'snmpv3_securityname' => $item['snmpv3_securityname'],
				'snmpv3_securitylevel' => $item['snmpv3_securitylevel'],
				'snmpv3_authpassphrase' => $item['snmpv3_authpassphrase'],
				'snmpv3_privpassphrase' => $item['snmpv3_privpassphrase'],
				'formula' => $item['formula'],
				'delay_flex' => $item['delay_flex'],
				'params' => $item['params'],
				'ipmi_sensor' => $item['ipmi_sensor'],
				'data_type' => $item['data_type'],
				'authtype' => $item['authtype'],
				'username' => $item['username'],
				'password' => $item['password'],
				'publickey' => $item['publickey'],
				'privatekey' => $item['privatekey'],
				'port' => $item['port'],
				'description' => $item['description'],
				'inventory_link' => $item['inventory_link'],
				'applications' => $this->formatApplications($item['applications']),
				'valuemap' => $item['valuemap'],
			);
			if (isset($item['interface_ref'])) {
				$data['interface_ref'] = $item['interface_ref'];
			}
			$result[] = $data;
		}
		return $result;
	}

	private function formatApplications(array $applications) {
		$result = array();
		order_result($applications, 'name');

		foreach ($applications as $application) {
			$result[] = array(
				'name' => $application['name']
			);
		}
		return $result;
	}

	private function formatMacros(array $macros) {
		$result = array();
		order_result($macros, 'macro');

		foreach ($macros as $macro) {
			$result[] = array(
				'macro' => $macro['macro'],
				'value' => $macro['value']
			);
		}
		return $result;
	}

	private function formatScreens(array $screens) {
		$result = array();
		order_result($screens, 'name');

		foreach ($screens as $screen) {
			$result[] = array(
				'name' => $screen['name'],
				'hsize' => $screen['hsize'],
				'vsize' => $screen['vsize'],
				'screen_items' => $this->formatScreenItems($screen['screenitems'])
			);
		}
		return $result;
	}

	private function formatDependencies(array $dependencies) {
		$result = array();

		foreach ($dependencies as $dependency) {
			$result[] = array(
				'name' => $dependency['description'],
				'expression' => $dependency['expression']
			);
		}
		return $result;
	}

	private function formatScreenItems(array $screenItems) {
		$result = array();

		foreach ($screenItems as $screenItem) {
			$result[] = array(
				'resourcetype'=> $screenItem['resourcetype'],
				'width'=> $screenItem['width'],
				'height'=> $screenItem['height'],
				'x'=> $screenItem['x'],
				'y'=> $screenItem['y'],
				'colspan'=> $screenItem['colspan'],
				'rowspan'=> $screenItem['rowspan'],
				'elements'=> $screenItem['elements'],
				'valign'=> $screenItem['valign'],
				'halign'=> $screenItem['halign'],
				'style'=> $screenItem['style'],
				'colspan'=> $screenItem['colspan'],
				'url'=> $screenItem['url'],
				'dynamic'=> $screenItem['dynamic'],
				'sort_triggers'=> $screenItem['sort_triggers'],
				'resource'=> $screenItem['resource']
			);
		}
		return $result;
	}

	private function formatGraphItems(array $graphItems) {
		$result = array();

		foreach ($graphItems as $graphItem) {
			$result[] = array(
				'sortorder'=> $graphItem['sortorder'],
				'drawtype'=> $graphItem['drawtype'],
				'color'=> $graphItem['color'],
				'yaxisside'=> $graphItem['yaxisside'],
				'calc_fnc'=> $graphItem['calc_fnc'],
				'type'=> $graphItem['type'],
				'item'=> $graphItem['itemid']
			);
		}
		return $result;
	}

	private function formatMapUrls(array $urls) {
		$result = array();
		foreach ($urls as $url) {
			$result[] = array(
				'name' => $url['name'],
				'url' => $url['url'],
				'elementtype' => $url['elementtype']
			);
		}
		return $result;
	}

	private function formatMapElementUrls(array $urls) {
		$result = array();

		foreach ($urls as $url) {
			$result[] = array(
				'name' => $url['name'],
				'url' => $url['url'],
			);
		}
		return $result;
	}

	private function formatMapLinks(array $links) {
		$result = array();

		foreach ($links as $link) {
			$result[] = array(
				'drawtype' => $link['drawtype'],
				'color' => $link['color'],
				'label' => $link['label'],
				'selementid1' => $link['selementid1'],
				'selementid2' => $link['selementid2'],
				'linktriggers' => $this->formatMapLinkTriggers($link['linktriggers'])
			);
		}
		return $result;
	}

	private function formatMapLinkTriggers(array $linktriggers) {
		$result = array();

		foreach ($linktriggers as $linktrigger) {
			$result[] = array(
				'drawtype' => $linktrigger['drawtype'],
				'color' => $linktrigger['color'],
				'trigger' => $linktrigger['triggerid']
			);
		}
		return $result;
	}

	private function formatMapElements(array $elements) {
		$result = array();
		foreach ($elements as $element) {
			$result[] = array(
				'elementtype' => $element['elementtype'],
				'label' => $element['label'],
				'label_location' => $element['label_location'],
				'x' => $element['x'],
				'y' => $element['y'],
				'elementsubtype' => $element['elementsubtype'],
				'areatype' => $element['areatype'],
				'width' => $element['width'],
				'height' => $element['height'],
				'viewtype' => $element['viewtype'],
				'use_iconmap' => $element['use_iconmap'],
				'selementid' => $element['selementid'],
				'element' => $element['elementid'],
				'icon_off' => $element['iconid_off'],
				'icon_on' => $element['iconid_on'],
				'icon_disabled' => $element['iconid_disabled'],
				'icon_maintenance' => $element['iconid_maintenance'],
				'urls' => $this->formatMapElementUrls($element['urls'])
			);
		}
		return $result;
	}
}
