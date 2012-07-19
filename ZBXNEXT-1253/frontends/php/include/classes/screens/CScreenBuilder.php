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


class CScreenBuilder {

	/**
	 * Switch on/off flicker-free screens auto refresh.
	 *
	 * @var boolean
	 */
	public $isFlickerfree;

	/**
	 * Detect is screen is template or real
	 *
	 * @var boolean
	 */
	public $isTemplate;

	/**
	 * Screen data
	 *
	 * @var array
	 */
	public $screen;

	/**
	 * Display mode
	 *
	 * @var int
	 */
	public $mode;

	/**
	 * Profile table entity name #1
	 *
	 * @var string
	 */
	public $profileIdx;

	/**
	 * Profile table record id belongs to #1
	 *
	 * @var int
	 */
	public $profileIdx2;

	/**
	 * Init screen data.
	 *
	 * @param array		$options
	 * @param boolean	$options['isFlickerfree']
	 * @param int		$options['mode']
	 * @param boolean	$options['isTemplate']
	 * @param string	$options['profileIdx']
	 * @param int		$options['profileIdx2']
	 * @param array		$options['screen']
	 */
	public function __construct(array $options = array()) {
		$this->isFlickerfree = isset($options['isFlickerfree']) ? $options['isFlickerfree'] : true;
		$this->mode = isset($options['mode']) ? $options['mode'] : SCREEN_MODE_VIEW;
		$this->isTemplate = isset($options['isTemplate']) ? $options['isTemplate'] : false;
		$this->profileIdx = !empty($options['profileIdx']) ? $options['profileIdx'] : '';
		$this->profileIdx2 = !empty($options['profileIdx2']) ? $options['profileIdx2'] : '';

		// get screen
		if (!empty($options['screen'])) {
			$this->screen = $options['screen'];
		}
		elseif (!empty($options['screenid'])) {
			$options = array(
				'screenids' => $options['screenid'],
				'output' => API_OUTPUT_EXTEND,
				'selectScreenItems' => API_OUTPUT_EXTEND
			);
			if (in_array($this->mode, array(SCREEN_MODE_PREVIEW, SCREEN_MODE_EDIT))) {
				$options['editable'] = true;
			}

			$this->screen = $this->isTemplate
				? API::TemplateScreen()->get($options)
				: API::Screen()->get($options);
			if (empty($this->screen) && !$this->isTemplate) {
				$this->screen = API::TemplateScreen()->get($options);
				if (empty($this->screen)) {
					access_deny();
				}
			}
			$this->screen = reset($this->screen);
		}
	}

	/**
	 * Get particular screen object.
	 *
	 * @param array		$options
	 * @param int		$options['resourcetype']
	 * @param int		$options['screenitemid']
	 * @param int		$options['hostid']
	 *
	 * @return CScreenBase
	 */
	public static function getScreen(array $options = array()) {
		if (!empty($options['screenitemid']) && empty($options['screenitem'])) {
			$options['screenitem'] = API::ScreenItem()->get(array(
				'screenitemids' => $options['screenitemid'],
				'hostids' => !empty($options['hostid']) ? $options['hostid'] : 0,
				'output' => API_OUTPUT_EXTEND
			));
			$options['screenitem'] = reset($options['screenitem']);
		}

		if (zbx_empty($options['resourcetype']) && !zbx_empty($options['screenitem']['resourcetype'])) {
			$options['resourcetype'] = $options['screenitem']['resourcetype'];
		}

		if (zbx_empty($options['resourcetype'])) {
			return null;
		}

		switch ($options['resourcetype']) {
			case SCREEN_RESOURCE_GRAPH:
				return new CScreenGraph($options);

			case SCREEN_RESOURCE_SIMPLE_GRAPH:
				return new CScreenSimpleGraph($options);

			case SCREEN_RESOURCE_MAP:
				return new CScreenMap($options);

			case SCREEN_RESOURCE_PLAIN_TEXT:
				return new CScreenPlainText($options);

			case SCREEN_RESOURCE_HOSTS_INFO:
				return new CScreenHostsInfo($options);

			case SCREEN_RESOURCE_TRIGGERS_INFO:
				return new CScreenTriggersInfo($options);

			case SCREEN_RESOURCE_SERVER_INFO:
				return new CScreenServerInfo($options);

			case SCREEN_RESOURCE_CLOCK:
				return new CScreenClock($options);

			case SCREEN_RESOURCE_SCREEN:
				return new CScreenScreen($options);

			case SCREEN_RESOURCE_TRIGGERS_OVERVIEW:
				return new CScreenTriggersOverview($options);

			case SCREEN_RESOURCE_DATA_OVERVIEW:
				return new CScreenDataOverview($options);

			case SCREEN_RESOURCE_URL:
				return new CScreenUrl($options);

			case SCREEN_RESOURCE_ACTIONS:
				return new CScreenActions($options);

			case SCREEN_RESOURCE_EVENTS:
				return new CScreenEvents($options);

			case SCREEN_RESOURCE_HOSTGROUP_TRIGGERS:
				return new CScreenHostgroupTriggers($options);

			case SCREEN_RESOURCE_SYSTEM_STATUS:
				return new CScreenSystemStatus($options);

			case SCREEN_RESOURCE_HOST_TRIGGERS:
				return new CScreenHostTriggers($options);

			case SCREEN_RESOURCE_HISTORY:
				return new CScreenHistory($options);

			case SCREEN_RESOURCE_CHART:
				return new CScreenChart($options);

			default:
				return null;
		}
	}

	/**
	 * Process screen with particular screen objects.
	 *
	 * @return CTable
	 */
	public function show() {
		if (empty($this->screen)) {
			return new CTableInfo(_('No screens defined.'));
		}

		$skipedFields = array();
		$screenitems = array();
		$emptyScreenColumns = array();

		// calculate table columns and rows
		foreach ($this->screen['screenitems'] as $screenitem) {
			$screenitems[] = $screenitem;

			for ($i = 0; $i < $screenitem['rowspan'] || $i == 0; $i++) {
				for ($j = 0; $j < $screenitem['colspan'] || $j == 0; $j++) {
					if ($i != 0 || $j != 0) {
						if (!isset($skipedFields[$screenitem['y'] + $i])) {
							$skipedFields[$screenitem['y'] + $i] = array();
						}
						$skipedFields[$screenitem['y'] + $i][$screenitem['x'] + $j] = 1;
					}
				}
			}
		}

		// create screen table
		$screenTable = new CTable(
			new CLink(
				_('No rows in screen.').SPACE.$this->screen['name'],
				'screenconf.php?config=0&form=update&screenid='.$this->screen['screenid']),
				($this->mode == SCREEN_MODE_PREVIEW || $this->mode == SCREEN_MODE_VIEW) ? 'screen_view' : 'screen_edit'
		);
		$screenTable->setAttribute('id', 'iframe');

		// action top row
		if ($this->mode == SCREEN_MODE_EDIT) {
			$newColumns = array(new CCol(new CImg('images/general/zero.png', 'zero', 1, 1)));

			for ($i = 0, $size = $this->screen['hsize'] + 1; $i < $size; $i++) {
				$icon = new CImg('images/general/plus.png', null, null, null, 'pointer');
				$icon->addAction('onclick', 'javascript: location.href = "screenedit.php?config=1&screenid='.$this->screen['screenid'].'&add_col='.$i.'";');

				array_push($newColumns, new CCol($icon));
			}

			$screenTable->addRow($newColumns);
		}

		for ($r = 0; $r < $this->screen['vsize']; $r++) {
			$newColumns = array();
			$emptyScreenRow = true;

			// action left cell
			if ($this->mode == SCREEN_MODE_EDIT) {
				$icon = new CImg('images/general/plus.png', null, null, null, 'pointer');
				$icon->addAction('onclick', 'javascript: location.href = "screenedit.php?config=1&screenid='.$this->screen['screenid'].'&add_row='.$r.'";');

				array_push($newColumns, new CCol($icon));
			}

			for ($c = 0; $c < $this->screen['hsize']; $c++) {
				if (isset($skipedFields[$r][$c])) {
					continue;
				}

				// screen item
				$isEditForm = false;
				$screenitem = array();

				foreach ($screenitems as $tmprow) {
					if ($tmprow['x'] == $c && $tmprow['y'] == $r) {
						$screenitem = $tmprow;
						break;
					}
				}

				if (empty($screenitem)) {
					$screenitem = array(
						'screenitemid' => 0,
						'resourcetype' => 0,
						'resourceid' => 0,
						'width' => 0,
						'height' => 0,
						'colspan' => 1,
						'rowspan' => 1,
						'elements' => 0,
						'valign' => VALIGN_DEFAULT,
						'halign' => HALIGN_DEFAULT,
						'style' => 0,
						'url' => '',
						'dynamic' => 0,
						'sort_triggers' => SCREEN_SORT_TRIGGERS_DATE_DESC
					);
				}

				if (!empty($screenitem['screenitemid'])) {
					$emptyScreenRow = false;
					$emptyScreenColumns[$c] = 1;
				}

				// action
				if ($this->mode == SCREEN_MODE_EDIT && $screenitem['screenitemid'] != 0) {
					$action = 'screenedit.php?form=update'.url_param('screenid').'&screenitemid='.$screenitem['screenitemid'];
				}
				elseif ($this->mode == SCREEN_MODE_EDIT && $screenitem['screenitemid'] == 0) {
					$action = 'screenedit.php?form=update'.url_param('screenid').'&x='.$c.'&y='.$r;
				}
				else {
					$action = null;
				}

				// edit form cell
				if ($this->mode == SCREEN_MODE_EDIT
						&& (isset($_REQUEST['form']) && $_REQUEST['form'] == 'update')
						&& ((isset($_REQUEST['x']) && $_REQUEST['x'] == $c && isset($_REQUEST['y']) && $_REQUEST['y'] == $r)
								|| (isset($_REQUEST['screenitemid']) && bccomp($_REQUEST['screenitemid'], $screenitem['screenitemid']) == 0))) {
					$screenView = new CView('configuration.screen.constructor.edit', array('screen' => $this->screen));
					$item = $screenView->render();
					$isEditForm = true;
				}
				// screen cell
				elseif (!empty($screenitem['screenitemid']) && isset($screenitem['resourcetype'])) {
					$screenBase = CScreenBuilder::getScreen(array(
						'isFlickerfree' => $this->isFlickerfree,
						'mode' => $this->mode,
						'profileIdx' => $this->profileIdx,
						'profileIdx2' => $this->profileIdx2,
						'resourcetype' => $screenitem['resourcetype'],
						'screenitem' => $screenitem
					));

					if (!empty($screenBase)) {
						if ($this->mode == SCREEN_MODE_EDIT && !empty($screenitem['screenitemid'])) {
							$screenBase->action = 'screenedit.php?form=update'.url_param('screenid').'&screenitemid='.$screenitem['screenitemid'];
						}
						elseif ($this->mode == SCREEN_MODE_EDIT && empty($screenitem['screenitemid'])) {
							$screenBase->action = 'screenedit.php?form=update'.url_param('screenid').'&x='.$c.'&y='.$r;
						}

						$item = $screenBase->get();
					}
					else {
						$item = null;
					}
				}
				// change/empty cell
				else {
					$item = array(SPACE);
					if ($this->mode == SCREEN_MODE_EDIT) {
						array_push($item, BR(), new CLink(_('Change'), $action, 'empty_change_link'));
					}
				}

				// align
				$halign = 'def';
				if ($screenitem['halign'] == HALIGN_CENTER) {
					$halign = 'cntr';
				}
				if ($screenitem['halign'] == HALIGN_LEFT) {
					$halign = 'left';
				}
				if ($screenitem['halign'] == HALIGN_RIGHT) {
					$halign = 'right';
				}

				$valign = 'def';
				if ($screenitem['valign'] == VALIGN_MIDDLE) {
					$valign = 'mdl';
				}
				if ($screenitem['valign'] == VALIGN_TOP) {
					$valign = 'top';
				}
				if ($screenitem['valign'] == VALIGN_BOTTOM) {
					$valign = 'bttm';
				}

				if ($this->mode == SCREEN_MODE_EDIT && !$isEditForm) {
					$item = new CDiv($item, 'draggable');
					$item->setAttribute('id', 'position_'.$r.'_'.$c);
					$item->setAttribute('data-xcoord', $c);
					$item->setAttribute('data-ycoord', $r);
				}

				// colspan/rowspan
				$newColumn = new CCol($item, $halign.'_'.$valign.' screenitem');
				if (!empty($screenitem['colspan'])) {
					$newColumn->setColSpan($screenitem['colspan']);
				}
				if (!empty($screenitem['rowspan'])) {
					$newColumn->setRowSpan($screenitem['rowspan']);
				}
				array_push($newColumns, $newColumn);
			}

			// action right cell
			if ($this->mode == SCREEN_MODE_EDIT) {
				$icon = new CImg('images/general/minus.png', null, null, null, 'pointer');
				if ($emptyScreenRow) {
					$removeRowLink = 'javascript: location.href = "screenedit.php?screenid='.$this->screen['screenid'].'&rmv_row='.$r.'";';
				}
				else {
					$removeRowLink = 'javascript: if (Confirm("'._('This screen-row is not empty. Delete it?').'")) {'.
						' location.href = "screenedit.php?screenid='.$this->screen['screenid'].'&rmv_row='.$r.'"; }';
				}
				$icon->addAction('onclick', $removeRowLink);
				array_push($newColumns, new CCol($icon));
			}
			$screenTable->addRow(new CRow($newColumns));
		}

		// action bottom row
		if ($this->mode == SCREEN_MODE_EDIT) {
			$icon = new CImg('images/general/plus.png', null, null, null, 'pointer');
			$icon->addAction('onclick', 'javascript: location.href = "screenedit.php?screenid='.$this->screen['screenid'].'&add_row='.$this->screen['vsize'].'";');
			$newColumns = array(new CCol($icon));

			for ($i = 0; $i < $this->screen['hsize']; $i++) {
				$icon = new CImg('images/general/minus.png', null, null, null, 'pointer');
				if (isset($emptyScreenColumns[$i])) {
					$removeColumnLink = 'javascript: if (Confirm("'._('This screen-column is not empty. Delete it?').'")) {'.
						' location.href = "screenedit.php?screenid='.$this->screen['screenid'].'&rmv_col='.$i.'"; }';
				}
				else {
					$removeColumnLink = 'javascript: location.href = "screenedit.php?config=1&screenid='.$this->screen['screenid'].'&rmv_col='.$i.'";';
				}
				$icon->addAction('onclick', $removeColumnLink);

				array_push($newColumns, new CCol($icon));
			}

			array_push($newColumns, new CCol(new CImg('images/general/zero.png', 'zero', 1, 1)));
			$screenTable->addRow($newColumns);
		}

		return $screenTable;
	}

	/**
	 * Insert javascript to create scroll in time control.
	 *
	 * @param string	$id
	 * @param array		$timeline
	 */
	public function insertScreenScrollJs($id, $timeline) {
		$timeControlData = array(
			'id' => $id,
			'domid' => 'screen_scroll',
			'loadSBox' => 0,
			'loadImage' => 0,
			'loadScroll' => 1,
			'scrollWidthByImage' => 0,
			'dynamic' => 0,
			'mainObject' => 1,
			'periodFixed' => CProfile::get($this->profileIdx.'.timelinefixed', 1),
			'sliderMaximumTimePeriod' => ZBX_MAX_PERIOD
		);

		zbx_add_post_js('timeControl.addObject("screen_scroll", '.zbx_jsvalue($timeline).', '.zbx_jsvalue($timeControlData).');');
	}

	/**
	 * Insert javascript to init screens.
	 *
	 * @param string $screenid
	 */
	public static function insertInitScreenJs($screenid) {
		zbx_add_post_js('init_screen("'.$screenid.'", "iframe", "'.$screenid.'");');
	}

	/**
	 * Insert javascript to start time control rendering.
	 */
	public static function insertProcessObjectsJs() {
		zbx_add_post_js('timeControl.processObjects();');
	}
}
