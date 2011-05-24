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
require_once('include/config.inc.php');
require_once('include/hosts.inc.php');
require_once('include/forms.inc.php');

$page['title'] = 'S_HOST_PROFILE_OVERVIEW';
$page['file'] = 'hostprofilesoverview.php';
$page['hist_arg'] = array('groupid', 'hostid');

require_once('include/page_header.php');
?>
<?php
//		VAR			TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION
$fields=array(
	'groupid' =>	array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID,	NULL),
	'groupby' =>	array(T_ZBX_STR, O_OPT,	P_SYS,	DB_ID,	NULL),
);

check_fields($fields);
validate_sort_and_sortorder('host_count', ZBX_SORT_DOWN);

if((PAGE_TYPE_JS == $page['type']) || (PAGE_TYPE_HTML_BLOCK == $page['type'])){
	include_once('include/page_footer.php');
	exit();
}
?>
<?php

$options = array(
	'groups' => array(
		'real_hosts' => 1,
	),
	'groupid' => get_request('groupid', null),
);
$pageFilter = new CPageFilter($options);
$_REQUEST['groupid'] = $pageFilter->groupid;
$_REQUEST['groupby'] = get_request('groupby', '');
$groupFieldTitle = '';

$hostprof_wdgt = new CWidget();
$hostprof_wdgt->addPageHeader(_('HOST PROFILE OVERVIEW'));

// getting profile fields to make a drop down
$profileFields = getHostProfiles(true); // 'true' means list should be ordered by title
$profileFieldsComboBox = new CComboBox('groupby', $_REQUEST['groupby'], 'submit()');
$profileFieldsComboBox->addItem('', _('not selected'));
foreach($profileFields as $profileField){
	$profileFieldsComboBox->addItem(
		$profileField['db_field'],
		$profileField['title'],
		$_REQUEST['groupby'] === $profileField['db_field'] ? 'yes' : null // selected?
	);
	if($_REQUEST['groupby'] === $profileField['db_field']){
		$groupFieldTitle = $profileField['title'];
	}
}

$r_form = new CForm('get');
$r_form->addItem(array(_('Group'), $pageFilter->getGroupsCB(true)));
$r_form->addItem(array(_('Grouping by'), $profileFieldsComboBox));
$hostprof_wdgt->addHeader(_('HOSTS'), $r_form);

$table = new CTableInfo();
$table->setHeader(
	array(
		make_sorting_header($groupFieldTitle === '' ? _('Field') : $groupFieldTitle, 'profile_field'),
		make_sorting_header(_('Host count'), 'host_count'),
	)
);

// to show a report, we will need a host group and a field to aggregate
if($pageFilter->groupsSelected && $groupFieldTitle !== ''){

	$options = array(
		'output' => array('hostid', 'name'),
		'selectProfile' => array($_REQUEST['groupby']), // only one field is required
		'withProfiles' => true
	);
	if($pageFilter->groupid > 0)
		$options['groupids'] = $pageFilter->groupid;

	$hosts = API::Host()->get($options);

	// aggregating data by chosen field value
	$report = array();
	foreach($hosts as $host){
		if($host['profile'][$_REQUEST['groupby']] !== ''){
			$lowerValue = zbx_strtolower($host['profile'][$_REQUEST['groupby']]);
			if(!isset($report[$lowerValue])){
				$report[$lowerValue] = array(
					'profile_field' => $host['profile'][$_REQUEST['groupby']],
					'host_count' => 1
				);
			}
			else{
				$report[$lowerValue]['host_count'] += 1;
			}
		}
	}

	order_result($report, getPageSortField('host_count'), getPageSortOrder());

	foreach($report as $rep){
		$row = array(
			$rep['profile_field'],
			new CLink($rep['host_count'],'hostprofiles.php?filter_field='.$_REQUEST['groupby'].'&filter_field_value='.urlencode($rep['profile_field']).'&filter_set=1&filter_exact=1'.url_param('groupid')),
		);
		$table->addRow($row);
	}
}

$hostprof_wdgt->addItem($table);
$hostprof_wdgt->show();

include_once('include/page_footer.php');
?>
