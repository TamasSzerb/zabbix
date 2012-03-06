<?php
/*
** Zabbix
** Copyright (C) 2000-2012 Zabbix SIA
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


/**
 * Class for importing configuration data.
 */
class CConfigurationImport {

	/**
	 * @var CImportReader
	 */
	protected $reader;

	/**
	 * @var CImportFormatter
	 */
	protected $formatter;

	/**
	 * @var CImportReferencer
	 */
	protected $referencer;

	/**
	 * @var array
	 */
	protected $options;

	/**
	 * @var string with import data in one of supported formats
	 */
	protected $source;

	/**
	 * @var array with data read from source string
	 */
	protected $data;

	/**
	 * @var array with references to interfaceid (hostid -> reference_name -> interfaceid)
	 */
	protected $interfacesCache = array();


	/**
	 * Constructor.
	 * Source string must be suitable for reader class,
	 * i.e. if string contains json then reader should be able to read json.
	 *
	 * @param string $source
	 * @param array $options
	 */
	public function __construct($source, array $options = array()) {
		$this->options = array(
			'groups' => array('missed' => false),
			'hosts' => array('exist' => false, 'missed' => false),
			'templates' => array('exist' => false, 'missed' => false),
			'applications' => array('exist' => false, 'missed' => false),
			'template_linkages' => array('missed' => false),
			'items' => array('exist' => false, 'missed' => false),
			'discoveryrules' => array('exist' => false, 'missed' => false),
			'triggers' => array('exist' => false, 'missed' => false),
			'graphs' => array('exist' => false, 'missed' => false),
			'screens' => array('exist' => false, 'missed' => false),
			'maps' => array('exist' => false, 'missed' => false),
			'images' => array('exist' => false, 'missed' => false),
		);
		$this->options = array_merge($this->options, $options);

		$this->source = $source;
	}

	/**
	 * Set reader that is used to read data from source string that is passed to constructor.
	 *
	 * @param CImportReader $reader
	 */
	public function setReader(CImportReader $reader) {
		$this->reader = $reader;
	}

	/**
	 * Import configuration data.
	 *
	 * @todo   for 1.8 version import old class zbxXML is used
	 *
	 * @throws Exception
	 * @return bool
	 */
	public function import() {
		try {
			// hack to make api throw exceptions
			// this made to not check all api calls results for false return
			czbxrpc::$useExceptions = true;
			DBstart();

			if (empty($this->reader)) {
				throw new UnexpectedValueException('Reader is not set.');
			}
			$this->data = $this->reader->read($this->source);

			$version = $this->getImportVersion();

			if ($version == '1.8') {
				zbxXML::import($this->source);
				if ($this->options['maps']['exist'] || $this->options['maps']['missed']) {
					zbxXML::parseMap($this->options);
				}
				if ($this->options['screens']['exist'] || $this->options['screens']['missed']) {
					zbxXML::parseScreen($this->options);
				}
				if ($this->options['hosts']['exist'] || $this->options['hosts']['missed']) {
					zbxXML::parseMain($this->options);
				}
			}
			else {
				$this->formatter = $this->getFormatter($version);

				$this->referencer = new CImportReferencer();

				$this->formatter->setData($this->data['zabbix_export']);

				$this->gatherReferences();

				if ($this->options['groups']['missed']) {
					$this->processGroups();
				}
				if ($this->options['templates']['exist'] || $this->options['templates']['missed']) {
					$this->processTemplates();
				}
				if ($this->options['hosts']['exist'] || $this->options['hosts']['missed']) {
					$this->processHosts();
				}
				if ($this->options['templates']['exist']
						|| $this->options['templates']['missed']
						|| $this->options['hosts']['exist']
						|| $this->options['hosts']['missed']) {
					$this->processApplications();
				}

				if ($this->options['items']['exist'] || $this->options['items']['missed']) {
					$this->processItems();
				}
				if ($this->options['discoveryrules']['exist'] || $this->options['discoveryrules']['missed']) {
					$this->processDiscoveryRules();
				}
				if ($this->options['triggers']['exist'] || $this->options['triggers']['missed']) {
					$this->processTriggers();
				}
				if ($this->options['graphs']['exist'] || $this->options['graphs']['missed']) {
					$this->processGraphs();
				}
				if (CWebUser::$data['type'] == USER_TYPE_SUPER_ADMIN
						&& ($this->options['images']['exist'] || $this->options['images']['missed'])) {
					$this->processImages();
				}
				if ($this->options['maps']['exist'] || $this->options['maps']['missed']) {
					$this->processMaps();
				}
				// screens should be created after all other elements
				if ($this->options['templates']['exist'] || $this->options['templates']['missed']) {
					$this->processTemplateScreens();
				}
				if ($this->options['screens']['exist'] || $this->options['screens']['missed']) {
					$this->processScreens();
				}
			}

			// make api not throw exception
			czbxrpc::$useExceptions = false;
			return DBend(true);
		}
		catch (Exception $e) {
			czbxrpc::$useExceptions = false;
			DBend(false);
			throw new Exception($e->getMessage(), $e->getCode());
		}

	}

	/**
	 * Parse all import data and collect references to objcets.
	 * For host objects it collects host names, for items it's host name and item key, etc.
	 * Collected references are resolved in separate referencer object.
	 */
	protected function gatherReferences() {
		$groupsRefs = array();
		$templatesRefs = array();
		$hostsRefs = array();
		$applicationsRefs = array();
		$itemsRefs = array();
		$valueMapsRefs = array();
		$triggersRefs = array();

		if ($this->options['groups']['missed']) {
			$groups = $this->formatter->getGroups();
			foreach ($groups as $group) {
				$groupsRefs[$group['name']] = $group['name'];
			}
		}

		if ($this->options['templates']['exist'] || $this->options['templates']['missed']) {
			$templates = $this->formatter->getTemplates();

			foreach ($templates as $template) {
				$templatesRefs[$template['host']] = $template['host'];

				foreach ($template['groups'] as $group) {
					$groupsRefs[$group['name']] = $group['name'];
				}
				foreach ($template['templates'] as $linkedTemplate) {
					$templatesRefs[$linkedTemplate['name']] = $linkedTemplate['name'];
				}
			}
		}

		if ($this->options['hosts']['exist'] || $this->options['hosts']['missed']) {
			$hosts = $this->formatter->getHosts();

			foreach ($hosts as $host) {
				$hostsRefs[$host['host']] = $host['host'];
				foreach ($host['groups'] as $group) {
					$groupsRefs[$group['name']] = $group['name'];
				}
				foreach ($host['templates'] as $linkedTemplate) {
					$templatesRefs[$linkedTemplate['name']] = $linkedTemplate['name'];
				}
			}
		}

		if ($this->options['templates']['exist']
				|| $this->options['templates']['missed']
				|| $this->options['hosts']['exist']
				|| $this->options['hosts']['missed']) {

			$allApplications = $this->formatter->getApplications();
			foreach ($allApplications as $host => $applications) {
				foreach ($applications as $app) {
					$applicationsRefs[$host][$app['name']] = $app['name'];
				}
			}
		}

		if ($this->options['items']['exist'] || $this->options['items']['missed']) {
			$allItems = $this->formatter->getItems();

			foreach ($allItems as $host => $items) {
				foreach ($items as $item) {
					$itemsRefs[$host][$item['key_']] = $item['key_'];

					foreach ($item['applications'] as $app) {
						$applicationsRefs[$host][$app['name']] = $app['name'];
					}

					if (!empty($item['valuemap'])) {
						$valueMapsRefs[$item['valuemap']['name']] = $item['valuemap']['name'];
					}
				}
			}
		}

		if ($this->options['discoveryrules']['exist'] || $this->options['discoveryrules']['missed']) {
			$allDiscoveryRules = $this->formatter->getDiscoveryRules();

			foreach ($allDiscoveryRules as $host => $discoveryRules) {
				foreach ($discoveryRules as $discoveryRule) {
					$itemsRefs[$host][$discoveryRule['key_']] = $discoveryRule['key_'];

					foreach ($discoveryRule['item_prototypes'] as $itemp) {
						$itemsRefs[$host][$itemp['key_']] = $itemp['key_'];

						foreach ($itemp['applications'] as $app) {
							$applicationsRefs[$host][$app['name']] = $app['name'];
						}

						if (!empty($itemp['valuemap'])) {
							$valueMapsRefs[$itemp['valuemap']['name']] = $itemp['valuemap']['name'];
						}
					}
					foreach ($discoveryRule['trigger_prototypes'] as $trigerp) {
						$triggersRefs[$trigerp['description']][$trigerp['expression']] = $trigerp['expression'];
					}

					foreach ($discoveryRule['graph_prototypes'] as $graph) {
						if ($graph['ymin_item_1']) {
							$itemsRefs[$graph['ymin_item_1']['host']][$graph['ymin_item_1']['key']] = $graph['ymin_item_1']['key'];
						}
						if ($graph['ymax_item_1']) {
							$itemsRefs[$graph['ymax_item_1']['host']][$graph['ymax_item_1']['key']] = $graph['ymax_item_1']['key'];
						}
						foreach ($graph['gitems'] as $gitem) {
							$itemsRefs[$gitem['item']['host']][$gitem['item']['key']] = $gitem['item']['key'];
						}
					}
				}
			}
		}

		if ($this->options['graphs']['exist'] || $this->options['graphs']['missed']) {
			$allGraphs = $this->formatter->getGraphs();

			foreach ($allGraphs as $graph) {
				if ($graph['ymin_item_1']) {
					$hostsRefs[$graph['ymin_item_1']['host']] = $graph['ymin_item_1']['host'];
					$itemsRefs[$graph['ymin_item_1']['host']][$graph['ymin_item_1']['key']] = $graph['ymin_item_1']['key'];
				}
				if ($graph['ymax_item_1']) {
					$hostsRefs[$graph['ymax_item_1']['host']] = $graph['ymax_item_1']['host'];
					$itemsRefs[$graph['ymax_item_1']['host']][$graph['ymax_item_1']['key']] = $graph['ymax_item_1']['key'];
				}
				foreach ($graph['gitems'] as $gitem) {
					$hostsRefs[$gitem['item']['host']] = $gitem['item']['host'];
					$itemsRefs[$gitem['item']['host']][$gitem['item']['key']] = $gitem['item']['key'];
				}
			}
		}

		if ($this->options['triggers']['exist'] || $this->options['triggers']['missed']) {
			$allTriggers = $this->formatter->getTriggers();

			foreach ($allTriggers as $trigger) {
				$triggersRefs[$trigger['description']][$trigger['expression']] = $trigger['expression'];

				foreach ($trigger['dependencies'] as $dependency) {
					$triggersRefs[$dependency['name']][$dependency['expression']] = $dependency['expression'];
				}
			}
		}

		$this->referencer->addGroups($groupsRefs);
		$this->referencer->addTemplates($templatesRefs);
		$this->referencer->addHosts($hostsRefs);
		$this->referencer->addApplications($applicationsRefs);
		$this->referencer->addItems($itemsRefs);
		$this->referencer->addValueMaps($valueMapsRefs);
		$this->referencer->addTriggers($triggersRefs);
	}

	/**
	 * Import groups.
	 */
	protected function processGroups() {
		$groups = $this->formatter->getGroups();
		if (empty($groups)) {
			return;
		}

		foreach ($groups as $gnum => $group) {
			if ($this->referencer->resolveGroup($group['name'])) {
				unset($groups[$gnum]);
			}
		}

		if ($groups) {
			$newGroups = API::HostGroup()->create($groups);
			foreach ($newGroups['groupids'] as $gnum => $groupid) {
				$this->referencer->addGroupRef($groups[$gnum]['name'], $groupid);
			}
		}
	}

	/**
	 * Import templates.
	 *
	 * @throws Exception
	 */
	protected function processTemplates() {
		$templates = $this->formatter->getTemplates();
		if (empty($templates)) {
			return;
		}
		$templates = zbx_toHash($templates, 'host');


		foreach ($templates as &$template) {
			unset($template['screens']);

			// if we don't need to update linkage, unset templates
			if (!$this->options['template_linkages']['missed']) {
				unset($template['templates']);
			}
		}
		unset($template);


		$orderedList = array();
		$templatesInSource = array_keys($templates);
		$parentTemplateRefs = array();
		foreach ($templates as $template) {
			$parentTemplateRefs[$template['host']] = array();

			if (!empty($template['templates'])) {
				foreach ($template['templates'] as $ref) {
					// if template already exists in system, we skip it
					if ($this->referencer->resolveTemplate($ref['name'])) {
						continue;
					}
					else {
						// if template is not in system and not in import, throw error
						if (!in_array($ref['name'], $templatesInSource)) {
							throw new Exception(_s('Template "%1$s" does not exist.', $ref['name']));
						}
						$parentTemplateRefs[$template['host']][$ref['name']] = $ref['name'];
					}
				}
			}
		}

		// we go in cycle through all templates looking for one without parent templates
		// when one fount it's pushed to ordered list and removed from list parent templates of all other templates
		while (!empty($parentTemplateRefs)) {
			$templateWithoutParents = false;
			foreach ($parentTemplateRefs as $template => $refs) {
				if (empty($refs)) {
					$templateWithoutParents = $template;
					$orderedList[] = $template;
					unset($parentTemplateRefs[$template]);
					break;
				}
			}
			if (!$templateWithoutParents) {
				throw new Exception('Circular template reference.');
			}

			foreach ($parentTemplateRefs as $template => $refs) {
				unset($parentTemplateRefs[$template][$templateWithoutParents]);
			}
		}

		foreach ($orderedList as $name) {
			$template = $templates[$name];
			foreach ($template['groups'] as $gnum => $group) {
				if (!$this->referencer->resolveGroup($group['name'])) {
					throw new Exception(_s('Group "%1$s" does not exist.', $group['name']));
				}
				$template['groups'][$gnum] = array('groupid' => $this->referencer->resolveGroup($group['name']));
			}
			if (isset($template['templates'])) {
				foreach ($template['templates'] as $tnum => $parentTemplate) {
					$template['templates'][$tnum] = array('templateid' => $this->referencer->resolveTemplate($parentTemplate['name']));
				}
			}

			if ($this->referencer->resolveTemplate($template['host'])) {
				$template['templateid'] = $this->referencer->resolveTemplate($template['host']);
				API::Template()->update($template);
			}
			else {
				$newHostIds = API::Template()->create($template);
				$templateid = reset($newHostIds['templateids']);
				$this->referencer->addTemplateRef($template['host'], $templateid);
			}
		}
	}

	/**
	 * Import hosts.
	 *
	 * @throws Exception
	 */
	protected function processHosts() {
		$hosts = $this->formatter->getHosts();
		if (empty($hosts)) {
			return;
		}

		// if we don't need to update linkage, unset templates
		if (!$this->options['template_linkages']['missed']) {
			foreach ($hosts as &$host) {
				unset($host['templates']);
			}
			unset($host);
		}

		// list of hostid which were creted or updated to create interfaces cache for that hosts
		$processedHosts = array();
		// create interfaces references
		$hostInterfacesRefsByName = array();
		foreach ($hosts as $host) {
			$hostInterfacesRefsByName[$host['host']] = array();
			foreach ($host['interfaces'] as $interface) {
				$hostInterfacesRefsByName[$host['host']][$interface['interface_ref']] = $interface;
			}
		}

		$hostsToCreate = $hostsToUpdate = array();
		foreach ($hosts as $host) {
			foreach ($host['groups'] as $gnum => $group) {
				if (!$this->referencer->resolveGroup($group['name'])) {
					throw new Exception(_s('Group "%1$s" does not exist.', $group['name']));
				}
				$host['groups'][$gnum] = array('groupid' => $this->referencer->resolveGroup($group['name']));
			}
			if (isset($host['templates'])) {
				foreach ($host['templates'] as $tnum => $template) {
					if (!$this->referencer->resolveTemplate($template['name'])) {
						throw new Exception(_s('Template "%1$s" does not exist.', $template['name']));
					}
					$host['templates'][$tnum] = array('templateid' => $this->referencer->resolveHostOrTemplate($template['name']));
				}
			}

			if ($this->referencer->resolveHost($host['host'])) {
				$host['hostid'] = $this->referencer->resolveHost($host['host']);
				$processedHosts[$host['host']] = $host['hostid'];
				$hostsToUpdate[] = $host;
			}
			else {
				$hostsToCreate[] = $host;
			}
		}


		// for exisitng hosts need to set interfaceid for existing interfaces or they will be added
		$dbInterfaces = API::HostInterface()->get(array(
			'hostids' => zbx_objectValues($hostsToUpdate, 'hostid'),
			'output' => API_OUTPUT_EXTEND,
			'preservekeys' => true
		));
		foreach ($dbInterfaces as $dbInterface) {
			foreach ($hostsToUpdate as $hnum => $host) {
				if (bccomp($host['hostid'], $dbInterface['hostid']) == 0) {
					foreach ($host['interfaces'] as $inum => $interface) {
						if ($dbInterface['ip'] == $interface['ip']
								&& $dbInterface['dns'] == $interface['dns']
								&& $dbInterface['useip'] == $interface['useip']
								&& $dbInterface['port'] == $interface['port']
								&& $dbInterface['type'] == $interface['type']
								&& $dbInterface['main'] == $interface['main']) {
							unset($hostsToUpdate[$hnum]['interfaces'][$inum]);
						}
					}
				}
				if (empty($hostsToUpdate[$hnum]['interfaces'])) {
					unset($hostsToUpdate[$hnum]['interfaces']);
				}
			}
		}

		// create/update hosts and create hash host->hostid
		if ($this->options['hosts']['missed'] && $hostsToCreate) {
			$newHostIds = API::Host()->create($hostsToCreate);
			foreach ($newHostIds['hostids'] as $hnum => $hostid) {
				$processedHosts[$hostsToCreate[$hnum]['host']] = $hostid;
				$this->referencer->addHostRef($hostsToCreate[$hnum]['host'], $hostid);
			}
		}
		if ($this->options['hosts']['exist'] && $hostsToUpdate) {
			API::Host()->update($hostsToUpdate);
		}

		// create interface hash interface_ref->interfaceid
		$dbInterfaces = API::HostInterface()->get(array(
			'hostids' => $processedHosts,
			'output' => API_OUTPUT_EXTEND,
			'preservekeys' => true
		));

		foreach ($dbInterfaces as $dbInterface) {
			foreach ($hostInterfacesRefsByName as $hostName => $interfaceRefs) {
				$hostId = $this->referencer->resolveHost($hostName);
				if (!isset($this->interfacesCache[$processedHosts[$hostName]])) {
					$this->interfacesCache[$hostId] = array();
				}

				foreach ($interfaceRefs as $refName => $interface) {
					if ($hostId == $dbInterface['hostid']
							&& $dbInterface['ip'] == $interface['ip']
							&& $dbInterface['dns'] == $interface['dns']
							&& $dbInterface['useip'] == $interface['useip']
							&& $dbInterface['port'] == $interface['port']
							&& $dbInterface['type'] == $interface['type']
							&& $dbInterface['main'] == $interface['main']) {
						$this->interfacesCache[$hostId][$refName] = $dbInterface['interfaceid'];
					}
				}
			}
		}
	}

	/**
	 * Import applications.
	 */
	protected function processApplications() {
		$allApplciations = $this->formatter->getApplications();
		if (empty($allApplciations)) {
			return;
		}

		$applicationsToCreate = array();
		foreach ($allApplciations as $host => $applications) {
			$hostid = $this->referencer->resolveHostOrTemplate($host);
			if (isset($hostid)) {
				foreach ($applications as $application) {
					$application['hostid'] = $hostid;
					$appId = $this->referencer->resolveApplication($hostid, $application['name']);
					if (!$appId) {
						$applicationsToCreate[] = $application;
					}
				}
			}
		}

		// create applications and create hash hostid->name->applicationid
		$newApplicationsIds = API::Application()->create($applicationsToCreate);
		foreach ($newApplicationsIds['applicationids'] as $anum => $applicationId) {
			$application = $applicationsToCreate[$anum];
			$this->referencer->addApplicationRef($application['hostid'], $application['name'], $applicationId);
		}
	}

	/*
	 * Import items.
	 */
	protected function processItems() {
		$allItems = $this->formatter->getItems();
		if (empty($allItems)) {
			return;
		}

		$itemsToCreate = array();
		$itemsToUpdate = array();
		foreach ($allItems as $host => $items) {
			$hostid = $this->referencer->resolveHostOrTemplate($host);
			if ($hostid) {
				foreach ($items as $item) {
					$item['hostid'] = $hostid;

					if (!empty($item['applications'])) {
						$applicationsIds = array();
						foreach ($item['applications'] as $application) {
							$applicationsIds[] = $this->referencer->resolveApplication($hostid, $application['name']);
						}
						$item['applications'] = $applicationsIds;
					}

					if (isset($item['interface_ref'])) {
						$item['interfaceid'] = $this->interfacesCache[$hostid][$item['interface_ref']];
					}

					if (!empty($item['valuemap'])) {
						$item['valuemapid'] = $this->referencer->resolveValueMap($item['valuemap']['name']);
					}

					$itemsId = $this->referencer->resolveItem($hostid, $item['key_']);
					if ($itemsId) {
						$item['itemid'] = $itemsId;
						$itemsToUpdate[] = $item;
					}
					else {
						$itemsToCreate[] = $item;
					}
				}
			}
		}

		// create/update items and create hash hostid->key_->itemid
		if ($this->options['items']['missed'] && $itemsToCreate) {
			$newItemsIds = API::Item()->create($itemsToCreate);
			foreach ($newItemsIds['itemids'] as $inum => $itemid) {
				$item = $itemsToCreate[$inum];
				$this->referencer->addItemRef($item['hostid'], $item['key_'], $itemid);
			}
		}
		if ($this->options['items']['exist'] && $itemsToUpdate) {
			API::Item()->update($itemsToUpdate);
		}
	}

	/**
	 * Import discovery rules.
	 *
	 * @throws Exception
	 */
	protected function processDiscoveryRules() {
		$allDiscoveryRules = $this->formatter->getDiscoveryRules();
		if (empty($allDiscoveryRules)) {
			return;
		}

		$itemsToCreate = array();
		$itemsToUpdate = array();
		$prototypesToUpdate = array();
		$prototypesToCreate = array();
		foreach ($allDiscoveryRules as $host => $discoveryRules) {
			$hostid = $this->referencer->resolveHostOrTemplate($host);
			if ($hostid) {
				foreach ($discoveryRules as $item) {
					$item['hostid'] = $hostid;

					// prototypes
					foreach ($item['item_prototypes'] as $prototype) {
						$prototype['hostid'] = $hostid;

						$applicationsIds = array();
						foreach ($prototype['applications'] as $application) {
							$applicationsIds[] = $this->referencer->resolveApplication($hostid, $application['name']);
						}
						$prototype['applications'] = $applicationsIds;

						if (isset($prototype['interface_ref'])) {
							$prototype['interfaceid'] = $this->interfacesCache[$hostid][$prototype['interface_ref']];
						}

						if (!empty($prototype['valuemap'])) {
							$prototype['valuemapid'] = $this->referencer->resolveValueMap($item['valuemap']['name']);
						}

						$prototypeId = $this->referencer->resolveItem($hostid, $prototype['key_']);
						$prototype['rule'] = array('hostid' => $hostid, 'key' => $item['key_']);
						if ($prototypeId) {
							$prototype['itemid'] = $prototypeId;
							$prototypesToUpdate[] = $prototype;
						}
						else {
							$prototypesToCreate[] = $prototype;
						}
					}


					if (isset($item['interface_ref'])) {
						$item['interfaceid'] = $this->interfacesCache[$hostid][$item['interface_ref']];
					}
					unset($item['item_prototypes']);
					unset($item['trigger_prototypes']);
					unset($item['graph_prototypes']);

					$itemsId = $this->referencer->resolveItem($hostid, $item['key_']);
					if ($itemsId) {
						$item['itemid'] = $itemsId;
						$itemsToUpdate[] = $item;
					}
					else {
						$itemsToCreate[] = $item;
					}
				}
			}
		}

		// create/update items and create hash hostid->key_->itemid
		if ($this->options['items']['missed'] && $itemsToCreate) {
			$newItemsIds = API::DiscoveryRule()->create($itemsToCreate);
			foreach ($newItemsIds['itemids'] as $inum => $itemid) {
				$item = $itemsToCreate[$inum];
				$this->referencer->addItemRef($item['hostid'], $item['key_'], $itemid);
			}
		}
		if ($this->options['items']['exist'] && $itemsToUpdate) {
			API::DiscoveryRule()->update($itemsToUpdate);
		}


		if ($prototypesToCreate) {
			foreach ($prototypesToCreate as &$prototype) {
				$prototype['ruleid'] = $this->referencer->resolveItem($prototype['rule']['hostid'], $prototype['rule']['key']);
			}
			unset($prototype);
			$newPrototypeIds = API::ItemPrototype()->create($prototypesToCreate);
			foreach ($newPrototypeIds['itemids'] as $inum => $itemid) {
				$item = $prototypesToCreate[$inum];
				$this->referencer->addItemRef($item['hostid'], $item['key_'], $itemid);
			}
		}
		if ($prototypesToUpdate) {
			foreach ($prototypesToCreate as &$prototype) {
				$prototype['ruleid'] = $this->referencer->resolveItem($prototype['rule']['hostid'], $prototype['rule']['key']);
			}
			unset($prototype);

			API::ItemPrototype()->update($prototypesToUpdate);
		}


		// first we need to create item prototypes and only then graph prototypes
		$triggersToCreate = array();
		$triggersToUpdate = array();
		$graphsToCreate = array();
		$graphsToUpdate = array();
		foreach ($allDiscoveryRules as $host => $discoveryRules) {
			$hostid = $this->referencer->resolveHostOrTemplate($host);
			if ($hostid) {
				foreach ($discoveryRules as $item) {
					// trigger prototypes
					foreach ($item['trigger_prototypes'] as $trigger) {
						$triggerId = $this->referencer->resolveTrigger($trigger['description'], $trigger['expression']);

						if ($triggerId) {
							$trigger['triggerid'] = $triggerId;
							$triggersToUpdate[] = $trigger;
						}
						else {
							$triggersToCreate[] = $trigger;
						}
					}

					// graph prototypes
					foreach ($item['graph_prototypes'] as $graph) {
						$graphHostIds = array();
						if (!empty($graph['ymin_item_1'])) {
							$hostId = $this->referencer->resolveHostOrTemplate($graph['ymin_item_1']['host']);
							$graph['ymin_itemid'] = $this->referencer->resolveItem($hostId, $graph['ymin_item_1']['key']);
						}
						if (!empty($graph['ymax_item_1'])) {
							$hostId = $this->referencer->resolveHostOrTemplate($graph['ymax_item_1']['host']);
							$graph['ymax_itemid'] = $this->referencer->resolveItem($hostId, $graph['ymax_item_1']['key']);
						}


						foreach ($graph['gitems'] as &$gitem) {
							$gitemhostId = $this->referencer->resolveHostOrTemplate($gitem['item']['host']);

							$gitem['itemid'] = $this->referencer->resolveItem($gitemhostId, $gitem['item']['key']);

							$graphHostIds[$gitemhostId] = $gitemhostId;
						}
						unset($gitem);


						// TODO: do this for all graphs at once
						$sql = 'SELECT g.graphid
						FROM graphs g, graphs_items gi, items i
						WHERE g.graphid=gi.graphid
							AND gi.itemid=i.itemid
							AND g.name='.zbx_dbstr($graph['name']).'
							AND '.DBcondition('i.hostid', $graphHostIds);
						$graphExists = DBfetch(DBselect($sql));

						if ($graphExists) {
							$dbGraph = API::GraphPrototype()->get(array(
								'graphids' => $graphExists['graphid'],
								'output' => API_OUTPUT_SHORTEN,
								'editable' => true
							));
							if (empty($dbGraph)) {
								throw new Exception(_s('No permission for Graph "%1$s".', $graph['name']));
							}
							$graph['graphid'] = $graphExists['graphid'];
							$graphsToUpdate[] = $graph;
						}
						else {
							$graphsToCreate[] = $graph;
						}
					}
				}
			}
		}

		if ($triggersToCreate) {
			API::TriggerPrototype()->create($triggersToCreate);
		}
		if ($triggersToUpdate) {
			API::TriggerPrototype()->update($triggersToUpdate);
		}

		if ($graphsToCreate) {
			API::GraphPrototype()->create($graphsToCreate);
		}
		if ($graphsToUpdate) {
			API::GraphPrototype()->update($graphsToUpdate);
		}
	}

	/**
	 * Import graphs.
	 *
	 * @throws Exception
	 */
	protected function processGraphs() {
		$allGraphs = $this->formatter->getGraphs();
		if (empty($allGraphs)) {
			return;
		}

		$graphsToCreate = array();
		$graphsToUpdate = array();
		foreach ($allGraphs as $graph) {
			$graphHostIds = array();

			if (!empty($graph['ymin_item_1'])) {
				$hostId = $this->referencer->resolveHostOrTemplate($graph['ymin_item_1']['host']);
				$graph['ymin_itemid'] = $this->referencer->resolveItem($hostId, $graph['ymin_item_1']['key']);
			}
			if (!empty($graph['ymax_item_1'])) {
				$hostId = $this->referencer->resolveHostOrTemplate($graph['ymax_item_1']['host']);
				$graph['ymax_itemid'] = $this->referencer->resolveItem($hostId, $graph['ymax_item_1']['key']);
			}

			foreach ($graph['gitems'] as &$gitem) {
				$gitemhostId = $this->referencer->resolveHostOrTemplate($gitem['item']['host']);

				$gitem['itemid'] = $this->referencer->resolveItem($gitemhostId, $gitem['item']['key']);

				$graphHostIds[$gitemhostId] = $gitemhostId;
			}
			unset($gitem);


			// TODO: do this for all graphs at once
			$sql = 'SELECT g.graphid
			FROM graphs g, graphs_items gi, items i
			WHERE g.graphid=gi.graphid
				AND gi.itemid=i.itemid
				AND g.name='.zbx_dbstr($graph['name']).'
				AND '.DBcondition('i.hostid', $graphHostIds);
			$graphExists = DBfetch(DBselect($sql));

			if ($graphExists) {
				$dbGraph = API::Graph()->get(array(
					'graphids' => $graphExists['graphid'],
					'output' => API_OUTPUT_SHORTEN,
					'editable' => true
				));
				if (empty($dbGraph)) {
					throw new Exception(_s('No permission for Graph "%1$s".', $graph['name']));
				}
				$graph['graphid'] = $graphExists['graphid'];
				$graphsToUpdate[] = $graph;
			}
			else {
				$graphsToCreate[] = $graph;
			}
		}

		// create/update items and create hash hostid->key_->itemid
		if ($this->options['graphs']['missed'] && $graphsToCreate) {
			API::Graph()->create($graphsToCreate);
		}
		if ($this->options['graphs']['exist'] && $graphsToUpdate) {
			API::Graph()->update($graphsToUpdate);
		}
	}

	/**
	 * Import triggers.
	 */
	protected function processTriggers() {
		$allTriggers = $this->formatter->getTriggers();
		if (empty($allTriggers)) {
			return;
		}

		$triggersToCreate = array();
		$triggersToUpdate = array();
		$triggersToCreateDependencies = array();
		foreach ($allTriggers as $trigger) {
			$triggerId = $this->referencer->resolveTrigger($trigger['description'], $trigger['expression']);

			if ($triggerId) {
				$deps = array();
				foreach ($trigger['dependencies'] as $dependency) {
					$deps[] = $this->referencer->resolveTrigger($dependency['name'], $dependency['expression']);
				}

				$trigger['dependencies'] = $deps;
				$trigger['triggerid'] = $triggerId;
				$triggersToUpdate[] = $trigger;
			}
			else {
				$triggersToCreateDependencies[] = $trigger['dependencies'];
				unset($trigger['dependencies']);
				$triggersToCreate[] = $trigger;
			}
		}

		$triggerDependencies = array();
		if ($this->options['triggers']['missed'] && $triggersToCreate) {
			$newTriggerIds = API::Trigger()->create($triggersToCreate);
			foreach ($newTriggerIds['triggerids'] as $tnum => $triggerId) {
				$trigger = $triggersToCreate[$tnum];
				$this->referencer->addTriggerRef($trigger['description'], $trigger['expression'], $triggerId);
			}
		}

		if ($triggersToCreateDependencies) {
			foreach ($newTriggerIds['triggerids'] as $tnum => $triggerId) {
				$deps = array();
				foreach ($triggersToCreateDependencies[$tnum] as $dependency) {
					$deps[] = $this->referencer->resolveTrigger($dependency['name'], $dependency['expression']);
				}

				if (!empty($deps)) {
					$triggerDependencies[] = array(
						'triggerid' => $triggerId,
						'dependencies' => $deps
					);
				}
			}
		}


		if ($this->options['triggers']['exist'] && $triggersToUpdate) {
			API::Trigger()->update($triggersToUpdate);
		}

		if ($triggerDependencies) {
			API::Trigger()->update($triggerDependencies);
		}

	}

	/**
	 * Import images.
	 *
	 * @throws Exception
	 */
	protected function processImages() {
		$allImages = $this->formatter->getImages();

		$imagesToUpdate = array();
		$allImages = zbx_toHash($allImages, 'name');

		$dbImages = DBselect('SELECT i.imageid, i.name FROM images i WHERE '.DBcondition('i.name', array_keys($allImages)));
		while ($dbImage = DBfetch($dbImages)) {
			$dbImage['image'] = $allImages[$dbImage['name']]['image'];
			$imagesToUpdate[] = $dbImage;
			unset($allImages[$dbImage['name']]);
		}

		if ($this->options['images']['missed']) {
			$allImages = array_values($allImages);
			$result = API::Image()->create($allImages);
			if (!$result) {
				throw new Exception(_('Cannot add image.'));
			}
		}

		if ($this->options['images']['exist']) {
			$result = API::Image()->update($imagesToUpdate);
			if (!$result) {
				throw new Exception(_('Cannot update image.'));
			}
		}
	}

	/**
	 * Import maps.
	 *
	 * @throws Exception
	 */
	protected function processMaps() {
		$allMaps = $this->formatter->getMaps();

		$mapsToCreate = array();
		$mapsToUpdate = array();
		$existingMaps = array();
		$allMaps = zbx_toHash($allMaps, 'name');
		$dbMaps = DBselect('SELECT s.sysmapid, s.name FROM sysmaps s WHERE '.DBcondition('s.name', array_keys($allMaps)));
		while ($dbMap = DBfetch($dbMaps)) {
			$existingMaps[$dbMap['sysmapid']] = $dbMap['name'];
			$allMaps[$dbMap['name']]['sysmapid'] = $dbMap['sysmapid'];
		}

		// if we are going to update maps, check for permissions
		if ($existingMaps && $this->options['maps']['exist']) {
			$allowedMaps = API::Map()->get(array(
				'sysmapids' => array_keys($existingMaps),
				'output' => API_OUTPUT_SHORTEN,
				'editable' => true,
				'preservekeys' => true
			));
			foreach ($existingMaps as $existingMapId => $existingMapName) {
				if (!isset($allowedMaps[$existingMapId])) {
					throw new Exception(_s('No permissions for map "%1$s".', $existingMapName));
				}
			}
		}

		foreach ($allMaps as $map) {
			// resolve icon map
			if (!empty($map['iconmap'])) {
				$iconMap = API::IconMap()->get(array(
					'filter' => array('name' => $map['iconmap']),
					'output' => API_OUTPUT_SHORTEN,
					'nopermissions' => true,
					'preservekeys' => true
				));
				$iconMap = reset($iconMap);
				if (!$iconMap) {
					throw new Exception(_s('Cannot find icon map "%1$s" for map "%2$s".', $map['iconmap'], $map['name']));
				}

				$map['iconmapid'] = $iconMap['iconmapid'];
			}


			if (isset($map['backgroundid'])) {
				$image = getImageByIdent($map['backgroundid']);

				if (!$image) {
					throw new Exception(_s('Cannot find background image for map "%1$s.', $map['name']));
				}
				$map['backgroundid'] = $image['imageid'];
			}

			if (!isset($map['selements'])) {
				$map['selements'] = array();
			}
			else {
				$map['selements'] = array_values($map['selements']);
			}

			if (!isset($map['links'])) {
				$map['links'] = array();
			}
			else {
				$map['links'] = array_values($map['links']);
			}

			foreach ($map['selements'] as &$selement) {
				$nodeCaption = isset($selement['elementid']['node']) ? $selement['elementid']['node'].':' : '';

				if (!isset($selement['elementid'])) {
					$selement['elementid'] = 0;
				}

				if (empty($selement['urls'])) {
					unset($selement['urls']);
				}
				switch ($selement['elementtype']) {
					case SYSMAP_ELEMENT_TYPE_MAP:
						$db_sysmaps = API::Map()->getObjects($selement['element']);
						if (empty($db_sysmaps)) {
							throw new Exception(_s('Cannot find map "%1$s" used in map %2$s".',
									$nodeCaption.$selement['element']['name'], $map['name']));
						}

						$tmp = reset($db_sysmaps);
						$selement['elementid'] = $tmp['sysmapid'];
						break;

					case SYSMAP_ELEMENT_TYPE_HOST_GROUP:
						$db_hostgroups = API::HostGroup()->getObjects($selement['element']);
						if (empty($db_hostgroups)) {
							throw new Exception(_s('Cannot find group "%1$s" used in map "$2%s".',
									$nodeCaption.$selement['element']['name'], $map['name']));
						}

						$tmp = reset($db_hostgroups);
						$selement['elementid'] = $tmp['groupid'];
						break;

					case SYSMAP_ELEMENT_TYPE_HOST:
						$db_hosts = API::Host()->getObjects($selement['element']);
						if (empty($db_hosts)) {
							throw new Exception(_s('Cannot find host "%1$s" used in map "$2%s".',
									$nodeCaption.$selement['element']['host'], $map['name']));
						}

						$tmp = reset($db_hosts);
						$selement['elementid'] = $tmp['hostid'];
						break;

					case SYSMAP_ELEMENT_TYPE_TRIGGER:
						$db_triggers = API::Trigger()->getObjects($selement['element']);
						if (empty($db_triggers)) {
							throw new Exception(_s('Cannot find trigger "%1$s" used in map "$2%s".',
									$nodeCaption.$selement['element']['host'], $map['name']));
						}

						$tmp = reset($db_triggers);
						$selement['elementid'] = $tmp['triggerid'];
						break;
					case SYSMAP_ELEMENT_TYPE_IMAGE:
				}

				$icons = array(
					'icon_off' => 'iconid_off',
					'icon_on' => 'iconid_on',
					'icon_disabled' => 'iconid_disabled',
					'icon_maintenance' => 'iconid_maintenance',
				);
				foreach ($icons as $element => $field) {
					if (!empty($selement[$element])) {
						$image = getImageByIdent($selement[$element]);
						if (!$image) {
							throw new Exception(_s('Cannot find icon "%1$s" for map "%2$s".',
								$selement[$element]['name'], $map['name']));
						}
						$selement[$field] = $image['imageid'];
					}
				}
			}
			unset($selement);

			foreach ($map['links'] as &$link) {
				if (empty($link['linktriggers'])) {
					unset($link['linktriggers']);
					continue;
				}

				foreach ($link['linktriggers'] as &$linktrigger) {
					$db_triggers = API::Trigger()->getObjects($linktrigger['trigger']);
					if (empty($db_triggers)) {
						throw new Exception(_s('Cannot find trigger "%1$s" for map "%2$s".',
							$linktrigger['trigger']['description'], $map['name']));
					}

					$tmp = reset($db_triggers);
					$linktrigger['triggerid'] = $tmp['triggerid'];
				}
				unset($linktrigger);
			}
			unset($link);

			if (isset($map['sysmapid'])) {
				$mapsToUpdate[] = $map;
			}
			else {
				$mapsToCreate[] = $map;
			}
		}

		if ($this->options['maps']['missed'] && $mapsToCreate) {
			API::Map()->create($mapsToCreate);
		}
		if ($this->options['maps']['exist'] && $mapsToUpdate) {
			API::Map()->update($mapsToUpdate);
		}
	}

	/**
	 * Import screens.
	 */
	protected function processScreens() {
		$allScreens = $this->formatter->getScreens();

		$existingScreens = array();
		$allScreens = zbx_toHash($allScreens, 'name');
		$dbScreens = DBselect('SELECT s.screenid, s.name FROM screens s WHERE'.
			' s.templateid IS NULL '.
			' AND '.DBcondition('s.name', array_keys($allScreens)));
		while ($dbScreen = DBfetch($dbScreens)) {
			$existingScreens[$dbScreen['screenid']] = $dbScreen['name'];
			$allScreens[$dbScreen['name']]['screenid'] = $dbScreen['screenid'];
		}

		// if we are going to update screens, check for permissions
		if ($existingScreens && $this->options['screens']['exist']) {
			$allowedScreens = API::Screen()->get(array(
				'screenids' => array_keys($existingScreens),
				'output' => API_OUTPUT_SHORTEN,
				'editable' => true,
				'preservekeys' => true
			));
			foreach ($existingScreens as $existingScreenId => $existingScreenName) {
				if (!isset($allowedScreens[$existingScreenId])) {
					throw new Exception(_s('No permissions for screen "%1$s".', $existingScreenName));
				}
			}
		}


		$allScreens = $this->prepareScreenImport($allScreens);
		$screensToCreate = array();
		$screensToUpdate = array();

		foreach ($allScreens as $screen) {
			if (isset($screen['screenid'])) {
				$screensToUpdate[] = $screen;
			}
			else {
				$screensToCreate[] = $screen;
			}
		}

		if ($this->options['screens']['missed'] && $screensToCreate) {
			API::Screen()->create($screensToCreate);
		}
		if ($this->options['screens']['exist'] && $screensToUpdate) {
			API::Screen()->update($screensToUpdate);
		}
	}

	/**
	 * Import template screens.
	 */
	protected function processTemplateScreens() {
		$templates = $this->formatter->getTemplates();

		$screensToCreate = array();
		$screensToUpdate = array();
		foreach ($templates as $template) {
			if (!empty($template['screens'])) {
				$allScreens = zbx_toHash($template['screens'], 'name');

				$existingScreens = array();
				$dbScreens = DBselect('SELECT s.screenid, s.name FROM screens s WHERE '.
						' s.templateid='.zbx_dbstr($this->referencer->resolveTemplate($template['host'])).
						' AND '.DBcondition('s.name', array_keys($allScreens)));
				while ($dbScreen = DBfetch($dbScreens)) {
					$existingScreens[$dbScreen['screenid']] = $dbScreen['name'];
					$allScreens[$dbScreen['name']]['screenid'] = $dbScreen['screenid'];
				}

				// if we are going to update screens, check for permissions
				if ($existingScreens && $this->options['screens']['exist']) {
					$allowedTplScreens = API::TemplateScreen()->get(array(
						'screenids' => array_keys($existingScreens),
						'output' => API_OUTPUT_SHORTEN,
						'editable' => true,
						'preservekeys' => true
					));
					foreach ($existingScreens as $existingScreenId => $existingScreenName) {
						if (!isset($allowedTplScreens[$existingScreenId])) {
							throw new Exception(_s('No permissions for screen "%1$s".', $existingScreenName));
						}
					}
				}

				$screens = $this->prepareScreenImport($allScreens);
				foreach ($screens as $screen) {
					$screen['templateid'] = $this->referencer->resolveTemplate($template['host']);
					if (isset($screen['screenid'])) {
						$screensToUpdate[] = $screen;
					}
					else {
						$screensToCreate[] = $screen;
					}
				}
			}
		}

		if ($screensToCreate) {
			API::TemplateScreen()->create($screensToCreate);
		}
		if ($screensToUpdate) {
			API::TemplateScreen()->update($screensToUpdate);
		}
	}

	/**
	 * Prepare screen data for import.
	 *
	 * @todo: it's copy of old frontend function, should be refactored
	 * @throws Exception
	 *
	 * @param array $allScreens
	 *
	 * @return array
	 */
	protected function prepareScreenImport(array $allScreens) {
		foreach ($allScreens as &$screen) {
			if (!isset($screen['screenitems'])) {
				$screen['screenitems'] = array();
			}
			foreach ($screen['screenitems'] as &$screenitem) {
				$nodeCaption = isset($screenitem['resource']['node']) ? $screenitem['resource']['node'] . ':' : '';

				if (!isset($screenitem['resource'])) {
					$screenitem['resourceid'] = 0;
				}

				if (is_array($screenitem['resource'])) {
					switch ($screenitem['resourcetype']) {
						case SCREEN_RESOURCE_HOSTS_INFO:
						case SCREEN_RESOURCE_TRIGGERS_INFO:
						case SCREEN_RESOURCE_TRIGGERS_OVERVIEW:
						case SCREEN_RESOURCE_DATA_OVERVIEW:
						case SCREEN_RESOURCE_HOSTGROUP_TRIGGERS:
							$db_hostgroups = API::HostGroup()->getObjects($screenitem['resource']);
							if (empty($db_hostgroups)) {
								throw new Exception(_s('Cannot find group "%1$s" used in screen "%2$s".',
										$nodeCaption.$screenitem['resource']['name'], $screen['name']));
							}

							$tmp = reset($db_hostgroups);
							$screenitem['resourceid'] = $tmp['groupid'];
							break;

						case SCREEN_RESOURCE_HOST_TRIGGERS:
							$db_hosts = API::Host()->getObjects($screenitem['resource']);
							if (empty($db_hosts)) {
								throw new Exception(_s('Cannot find host "%1$s" used in screen "%2$s".',
										$nodeCaption.$screenitem['resource']['host'], $screen['name']));
							}

							$tmp = reset($db_hosts);
							$screenitem['resourceid'] = $tmp['hostid'];
							break;

						case SCREEN_RESOURCE_GRAPH:
							$db_graphs = API::Graph()->getObjects($screenitem['resource']);
							if (empty($db_graphs)) {
								throw new Exception(_s('Cannot find graph "%1$s" used in screen "%2$s".',
										$nodeCaption.$screenitem['resource']['name'], $screen['name']));
							}

							$tmp = reset($db_graphs);
							$screenitem['resourceid'] = $tmp['graphid'];
							break;

						case SCREEN_RESOURCE_SIMPLE_GRAPH:
						case SCREEN_RESOURCE_PLAIN_TEXT:
							$db_items = API::Item()->getObjects(array(
								'host' => $screenitem['resource']['host'],
								'key_' => $screenitem['resource']['key']
							));

							if (empty($db_items)) {
								throw new Exception(_s('Cannot find item "%1$s" used in screen "%2$s".',
										$nodeCaption.$screenitem['resource']['host'].':'.$screenitem['resource']['key_'], $screen['name']));
							}

							$tmp = reset($db_items);
							$screenitem['resourceid'] = $tmp['itemid'];
							break;

						case SCREEN_RESOURCE_MAP:
							$db_sysmaps = API::Map()->getObjects($screenitem['resource']);
							if (empty($db_sysmaps)) {
								throw new Exception(_s('Cannot find map "%1$s" used in screen "%2$s".',
										$nodeCaption.$screenitem['resource']['name'], $screen['name']));
							}

							$tmp = reset($db_sysmaps);
							$screenitem['resourceid'] = $tmp['sysmapid'];
							break;

						case SCREEN_RESOURCE_SCREEN:
							$db_screens = API::Screen()->get(array(
								'output' => API_OUTPUT_SHORTEN,
								'preservekeys' => true,
								'editable' => true,
								'filter' => array('name' => $screenitem['resource']['name'])
							));
							if (empty($db_screens)) {
								throw new Exception(_s('Cannot find screen "%1$s" used in screen "%2$s".',
										$nodeCaption.$screenitem['resource']['name'], $screen['name']));
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
		}
		unset($screen);

		return $allScreens;
	}

	/**
	 * Method for creating import formatter for specified import version.
	 *
	 * @throws InvalidArgumentException
	 *
	 * @param string $version
	 *
	 * @return C20ImportFormatter
	 */
	protected function getFormatter($version) {
		switch ($version) {
			case '2.0':
				return new C20ImportFormatter;

			default:
				throw new InvalidArgumentException('Unknown import version.');
		}

	}

	/**
	 * Get configuration import version.
	 *
	 * @return string
	 */
	protected function getImportVersion() {
		if (isset($this->data['zabbix_export']['version'])) {
			return $this->data['zabbix_export']['version'];
		}
		return '1.8';
	}
}
