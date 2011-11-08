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
	global $TRANSLATION;

	$TRANSLATION=array(

	'S_DATE_FORMAT_YMDHMS'=>		_('d M Y H:i:s'),
	'S_HTML_CHARSET'=>			_('UTF-8'),

	'S_YEAR_SHORT'=>			_('y'),
	'S_MONTH_SHORT'=>			_('m'),
	'S_WEEK_SHORT'=>			_('w'),
	'S_DAY_SHORT'=>				_('d'),
	'S_HOUR_SHORT' =>			_('h'),
	'S_MINUTE_SHORT' =>			_('m'),

	'S_ACTIVATE_SELECTED'=>			_('Activate selected'),
	'S_DISABLE_SELECTED'=>			_('Disable selected'),
	'S_ACTIVATE_SELECTED_HOSTS'=>		_('Activate selected hosts'),
	'S_DISABLE_SELECTED_HOSTS'=>		_('Disable selected hosts'),
	'S_DELETE_SELECTED'=>			_('Delete selected'),
	'S_COPY_SELECTED_TO'=>			_('Copy selected to ...'),

//	dicoveryconf.php
	'S_CREATED_SMALL' => _('created'),
	'S_DISCOVERY_RULE'=>			_('Discovery rule'),
	'S_DISCOVERY'=>				_('Discovery'),
	'S_CONFIGURATION_OF_DISCOVERY'=>	_('Configuration of discovery'),
	'S_CREATE_RULE'=>			_('Create rule'),
	'S_NO_PROXY'=>				_('(no proxy)'),
	'S_SSH'=>				_('SSH'),
	'S_SMTP'=>				_('SMTP'),
	'S_FTP'=>				_('FTP'),
	'S_POP'=>				_('POP'),
	'S_NNTP'=>				_('NNTP'),
	'S_IMAP'=>				_('IMAP'),
	'S_TCP'=>				_('TCP'),
	'S_ICMPPING'=>				_('ICMP ping'),

	'S_STATUS_OF_DISCOVERY_BIG'=>		_('STATUS OF DISCOVERY'),
	'S_STATUS_OF_DISCOVERY'=>		_('Status of discovery'),
	'S_DISCOVERED'=>			_('Discovered'),
	'S_LOST'=>				_('Lost'),

//	discovery.php
	'S_UP_TIME'=>				_('Uptime'),
	'S_DOWN_TIME'=>				_('Downtime'),
	'S_DISCOVERED_DEVICE'=>			_('Discovered device'),
	'S_MONITORED_HOST'=>			_('Monitored host'),

//	auditacts.php
	'S_AUDITACTS_DESCRIPTION_DATE_FORMAT'=>	_('d M Y H:i:s'),

//	auditlogs.php
	'S_AUDITLOGS_RECORD_DATE_FORMAT'=>	_('d M Y H:i:s'),

//	httpdetails.php
	'S_DETAILS_OF_SCENARIO'=>		_('Details of scenario'),
	'S_DETAILS_OF_SCENARIO_BIG'=>		_('DETAILS OF SCENARIO'),
	'S_SPEED'=>				_('Speed'),
	'S_RESPONSE_CODE'=>			_('Response code'),
	'S_TOTAL_BIG'=>				_('TOTAL'),
	'S_RESPONSE_TIME'=>			_('Response time'),
	'S_IN_PROGRESS'=>			_('In progress'),
	'S_OF_SMALL'=>				_('of'),
	'S_IN_CHECK'=>				_('In check'),
	'S_IDLE_TILL'=>				_('Idle till'),
	'S_FAILED_ON'=>				_('Failed on'),
	'S_FAILED'=>				_('Failed'),

//	httpmon.php
	'S_STATUS_OF_WEB_MONITORING'=>		_('Status of Web monitoring'),
	'S_STATUS_OF_WEB_MONITORING_BIG'=>	_('STATUS OF WEB MONITORING'),
	'S_STATE'=>				_('State'),
	'S_WEB_SCENARIO_DATE_FORMAT'=>		_('d M Y H:i:s'),
	'S_WEB_SCENARIO_IDLE_DATE_FORMAT'=>	_('d M Y H:i:s'),

//	httpconf.php
	'S_SORT'=>					_('Sort'),
	'S_STATUS_CODES'=>				_('Status codes'),
	'S_CONFIGURATION_OF_WEB_MONITORING'=>		_('Configuration of Web monitoring'),
	'S_CONFIGURATION_OF_WEB_MONITORING_BIG'=>	_('CONFIGURATION OF WEB MONITORING'),
	'S_SCENARIO'=>					_('Scenario'),
	'S_SCENARIOS'=>					_('Scenarios'),
	'S_SCENARIOS_BIG'=>				_('SCENARIOS'),
	'S_CREATE_SCENARIO'=>				_('Create scenario'),
	'S_HIDE_DISABLED_SCENARIOS'=>			_('Hide disabled scenarios'),
	'S_SHOW_DISABLED_SCENARIOS'=>			_('Show disabled scenarios'),
	'S_NUMBER_OF_STEPS'=>				_('Number of steps'),
	'S_SCENARIO_DELETED'=>				_('Scenario deleted'),
	'S_SCENARIO_ACTIVATED'=>			_('Scenario activated'),
	'S_SCENARIO_DISABLED'=>				_('Scenario disabled'),
	'S_DELETE_SCENARIO_Q'=>				_('Delete scenario?'),
	'S_SCENARIO_UPDATED'=>				_('Scenario updated'),
	'S_CANNOT_UPDATE_SCENARIO'=>			_('Cannot update scenario'),
	'S_SCENARIO_ADDED'=>				_('Scenario added'),
	'S_CANNOT_ADD_SCENARIO'=>			_('Cannot add scenario'),
	'S_CANNOT_DELETE_SCENARIO'=>			_('Cannot delete scenario'),
	'S_AGENT'=>					_('Agent'),
	'S_VARIABLES'=>					_('Variables'),
	'S_STEP'=>					_('Step'),
	'S_STEPS'=>					_('Steps'),
	'S_TIMEOUT'=>					_('Timeout'),
	'S_POST'=>					_('Post'),
	'S_REQUIRED'=>					_('Required'),
	'S_STEP_OF_SCENARIO'=>				_('Step of scenario'),
	'S_BASIC_AUTHENTICATION'=>			_('Basic authentication'),
	'S_NTLM_AUTHENTICATION'=>			_('NTLM authentication'),
	'S_ENABLE_SELECTED_WEB_SCENARIOS_Q'=>		_('Enable selected WEB scenarios?'),
	'S_DISABLE_SELECTED_WEB_SCENARIOS_Q'=>		_('Disable selected WEB scenarios?'),
	'S_DELETE_HISTORY_SELECTED_WEB_SCENARIOS_Q'=>	_('Delete history of selected WEB scenarios?'),
	'S_DELETE_SELECTED_WEB_SCENARIOS_Q'=>		_('Delete selected WEB scenarios?'),
	'S_SELECT_HOST_FIRST' => _('(select host first)'),

//	exp_imp.php
	'S_ELEMENT'=>				_('Element'),
	'S_IMPORT_FILE'=>			_('Import file'),
	'S_IMPORT'=>				_('Import'),
	'S_EXPORT_SELECTED'=>		_('Export selected'),
	'S_PREVIEW'=>				_('Preview'),
	'S_NO_DATA_SMALL'=>			_('no data'),
	'S_RULES'=>				_('Rules'),
	'S_EXISTING'=>				_('Existing'),
	'S_MISSING'=>				_('Missing'),
	'S_LOCAL_BIG'=>				_('LOCAL'),
	'S_SERVER_BIG'=>			_('SERVER'),

//	export.inc.php
	'S_EXPORT_DATE_ATTRIBUTE_DATE_FORMAT'=>	_('d.m.y'),
	'S_EXPORT_TIME_ATTRIBUTE_DATE_FORMAT'=>	_('H.i'),

//	admin.php
	'S_PREVIOUS'=>				_('Previous'),
	'S_NEXT'=>				_('Next'),
	'S_RETRY'=>				_('Retry'),
	'S_FINISH'=>				_('Finish'),
	'S_FAIL'=>				_('Fail'),
	'S_UPDATE_BIG'=>			_('UPDATE'),
	'S_INSTALLATION'=>			_('Installation'),
	'S_NEW_INSTALLATION'=>			_('New installation'),
	'S_NEW_INSTALLATION_BIG'=>		_('NEW INSTALLATION'),
	'S_INSTALLATION_UPDATE'=>		_('Installation/Update'),
	'S_ZABBIX_IS_UNAVAILABLE'=>		_('Zabbix is temporarily unavailable'),

//	actions.inc.php
	'S_HISTORY_OF_ACTIONS_DATE_FORMAT'=>	_('d M Y H:i:s'),
	'S_EVENT_ACTION_MESSAGES_DATE_FORMAT'=>	_('d M Y H:i:s'),
	'S_EVENT_ACTION_CMDS_DATE_FORMAT'=>	_('Y.M.d H:i:s'),

//	node.php
	'S_MASTER_NODE'=>			_('Master node'),
	'S_CHILD'=>				_('Child'),
	'S_MASTER'=>				_('Master'),
	'S_NODE_UPDATED'=>			_('Node updated'),
	'S_CANNOT_UPDATE_NODE'=>		_('Cannot update node'),
	'S_NODE_ADDED'=>			_('Node added'),
	'S_CANNOT_ADD_NODE'=>			_('Cannot add node'),
	'S_NODE_DELETED'=>			_('Node deleted'),
	'S_CANNOT_DELETE_NODE'=>		_('Cannot delete node'),
	'S_SELECT_NODES'=>			_('Select Nodes'),

//	acknow.php
	'S_BULK_ACKNOWLEDGE'=>			_('Bulk acknowledge'),

//	actionconf.php
	'S_CONFIGURATION_OF_ACTIONS'=>		_('Configuration of actions'),
	'S_FILTER'=>				_('Filter'),
	'S_TIME_PERIOD'=>			_('Time period'),
	'S_MAX_VALUE_SMALL'=>			_('max value'),
	'S_MIN_VALUE_SMALL'=>			_('min value'),
	'S_CONDITION'=>				_('Condition'),

//	actions.php
	'S_ACTIONS'=>				_('Actions'),
	'S_LOGS'=>				_('Logs'),
	'S_LOGS_BIG'=>				_('LOGS'),
	'S_DELAY'=>				_('Delay'),
	'S_ON_BIG'=>				_('ON'),
	'S_ON'=>					_('On'),
	'S_OFF'=>					_('Off'),
	'S_GROUP'=>				_('Group'),
	'S_INFORMATION'=>			_('Information'),
	'S_WARNING'=>				_('Warning'),
	'S_AND_BIG'=>				_('AND'),
	'S_OR_BIG'=>				_('OR'),
	'S_OR'=>				_('or'),
	'S_LIKE_SMALL'=>			_('like'),
	'S_RETRIES_LEFT'=>			_('Retries left'),

//	alarms.php
	'S_SHOW_ALL'=>				_('Show all'),
	'S_TIME'=>				_('Time'),
	'S_STATUS'=>				_('Status'),
	'S_TRUE_BIG'=>				_('TRUE'),
	'S_FALSE_BIG'=>				_('FALSE'),
	'S_UNKNOWN_BIG'=>			_('UNKNOWN'),

//	actions.php
	'S_TYPE'=>				_('Type'),
	'S_RECIPIENTS'=>			_('Recipient(s)'),
	'S_RECIPIENT'=>				_('Recipient'),
	'S_ERROR'=>				_('Error'),
	'S_SENT'=>				_('sent'),
	'S_EXECUTED'=>				_('executed'),
	'S_NO_ACTIONS_FOUND'=>			_('No actions found'),

// Lines
	'S_LINE'=>				_('Line'),
	'S_FILLED_REGION'=>			_('Filled region'),
	'S_BOLD_LINE'=>				_('Bold line'),
	'S_DOT'=>				_('Dot'),
	'S_DASHED_LINE'=>			_('Dashed line'),
	'S_GRADIENT_LINE'=>			_('Gradient line'),

//	charts.php
	'S_CUSTOM_GRAPHS'=>			_('Custom graphs'),
	'S_GRAPHS_BIG'=>			_('GRAPHS'),
	'S_PERIOD'=>				_('Period'),

//	class.cchart.php
	'S_CCHARTS_TIMELINE_HOURS_FORMAT'=>			_('H:i'),
	'S_CCHARTS_TIMELINE_DAYS_FORMAT'=>			_('D'),
	'S_CCHARTS_TIMELINE_MONTHDAYS_FORMAT'=>			_('d.m'),
	'S_CCHARTS_TIMELINE_START_DATE_FORMAT'=>		_('d.m H:i'),
	'S_CCHARTS_TIMELINE_END_DATE_FORMAT'=>			_('d.m H:i'),
	'S_CCHARTS_TIMELINE_MAINPERIOD_HOURS_FORMAT'=>		_('H:i'),
	'S_CCHARTS_TIMELINE_MAINPERIOD_FULL_DAY_TIME_FORMAT'=>	_('d.m H:i'),
	'S_CCHARTS_TIMELINE_MAINPERIOD_MONTHDAYS_FORMAT'=>	_('d.m'),

// Colours

//	config.php
	'S_DROPDOWN_FIRST_ENTRY'=>			_('Dropdown first entry'),
	'S_DROPDOWN_REMEMBER_SELECTED'=>		_('remember selected'),
	'S_MAX_IN_TABLE' =>				_('Max count of elements to show inside table cell'),
	'S_DEFAULT_THEME'=>				_('Default theme'),
	'S_EVENT_ACKNOWLEDGES'=>			_('Event acknowledges'),
	'S_SHOW_EVENTS_MAX'=>				_('Show events max'),
	'S_CANNNOT_UPDATE_VALUE_MAP'=>			_('Cannot update value map'),
	'S_VALUE_MAP_ADDED'=>				_('Value map added'),
	'S_CANNNOT_ADD_VALUE_MAP'=>			_('Cannot add value map'),
	'S_VALUE_MAP_DELETED'=>				_('Value map deleted'),
	'S_CANNNOT_DELETE_VALUE_MAP'=>			_('Cannot delete value map'),
	'S_VALUE_MAP_UPDATED'=>				_('Value map updated'),
	'S_CONFIGURATION_OF_ZABBIX_BIG'=>		_('CONFIGURATION OF ZABBIX'),
	'S_CONFIGURATION_UPDATED'=>			_('Configuration updated'),
	'S_CONFIGURATION_WAS_NOT_UPDATED'=>		_('Configuration was not updated'),
	'S_DEFAULT'=>					_('Default'),
	'S_IMAGE'=>					_('Image'),
	'S_OTHER'=>					_('Other'),
	'S_NOTHING_TO_DO'=>				_('Nothing to do'),
	'S_EXPRESSION'=>				_('Expression'),

	'S_CHARACTER_STRING_INCLUDED'=>			_('Character string included'),
	'S_ANY_CHARACTER_STRING_INCLUDED'=>		_('Any character string included'),
	'S_CHARACTER_STRING_NOT_INCLUDED'=>		_('Character string not included'),
	'S_RESULT_IS_TRUE'=>				_('Result is TRUE'),
	'S_RESULT_IS_FALSE'=>				_('Result is FALSE'),

	'S_TEST'=>					_('Test'),
	'S_TEST_STRING'=>				_('Test string'),
	'S_INCORRECT_EXPRESSION'=>			_('Incorrect expression'),

	'S_REGULAR_EXPRESSION_ADDED'=>			_('Regular expression added'),
	'S_CANNOT_ADD_REGULAR_EXPRESSION'=>		_('Cannot add regular expression'),

	'S_REGULAR_EXPRESSION_UPDATED'=>		_('Regular expression updated'),
	'S_CANNOT_UPDATE_REGULAR_EXPRESSION'=>		_('Cannot update regular expression'),

	'S_REGULAR_EXPRESSION_DELETED'=>		_('Regular expression deleted'),
	'S_CANNOT_DELETE_REGULAR_EXPRESSION'=>		_('Cannot delete regular expression'),

	'S_DELETE_REGULAR_EXPRESSION_Q'=>		_('Delete regular expression?'),

	'S_VALUE_MAPS_CREATE_NUM_STRING'=>		_('Value maps are used to create a mapping between numeric values and string representations'),

//	nodes.php
	'S_NOT_DM_SETUP'=>			_('Your setup is not configured for distributed monitoring'),
	'S_CONFIGURATION_OF_NODES'=>		_('CONFIGURATION OF NODES'),
	'S_NODE'=>				_('Node'),
	'S_NODES'=>				_('Nodes'),
	'S_NODES_BIG'=>				_('NODES'),
	'S_NEW_NODE'=>				_('New node'),
	'S_NO_NODES_DEFINED'=>			_('No nodes defined.'),
	'S_ALL_NODES'=>				_('All nodes'),
	'S_DELETE_SELECTED_NODE_Q'=>		_('Delete selected node?'),

// proxies.php
	'S_ENABLE_SELECTED_PROXIES' =>	_('Enable hosts monitored by selected proxies?'),
	'S_DISABLE_SELECTED_PROXIES' =>	_('Disable hosts monitored by selected proxies?'),
	'S_DELETE_SELECTED_PROXIES' =>	_('Delete selected proxies?'),
	'S_CONFIGURATION_OF_PROXIES'=>	_('CONFIGURATION OF PROXIES'),
	'S_DELETE_SELECTED_PROXY_Q'=>	_('Delete selected proxy?'),
	'S_HOST_COUNT' => _('Host count'),
	'S_ITEM_COUNT' => _('Item count'),
	'S_REQUIRED_PERFORMANCE' => _('Required performance (vps)'),
	'S_PROXY_MODE' => _('Proxy mode'),
	'S_PROXY_PASSIVE' => _('Passive'),
	'S_PROXY_ACTIVE' => _('Active'),

//	Latest values
	'S_NO_PERMISSIONS'=>			_('No permissions to referred object or it does not exist!'),
	'S_LATEST_DATA_BIG'=>			_('LATEST DATA'),
	'S_ALL_S'=>						_('All'),
	'S_ALL_SMALL'=>					_('all'),
	'S_MINUS_OTHER_MINUS'=>			_('- other -'),
	'S_NOT_SELECTED_SMALL'=>		_('not selected'),
	'S_GRAPH'=>						_('Graph'),

//	Footer
	'S_ZABBIX'=>				_('Zabbix'),

//	graph.php
	'S_GRAPHS_COPIED'=>			_('Graphs copied'),
	'S_CANNOT_COPY_GRAPHS'=>	_('Cannot copy graphs'),
	'S_ITEM_ADDED'=>			_('Item added'),
	'S_ITEM_UPDATED'=>			_('Item updated'),
	'S_ITEMS_UPDATED'=>			_('Items updated'),
	'S_ITEM_DOES_NOT_EXIST'=>	_('Item does not exist'),
	'S_SORT_BY'=>				_('Sort by'),
	'S_PARAMETER'=>				_('Parameter'),
	'S_COLOR'=>					_('Colour'),
	'S_UP'=>					_('Up'),
	'S_DOWN'=>					_('Down'),
	'S_NEW_ITEM_FOR_THE_GRAPH'=>		_('New item for the graph'),
	'S_UPD_ITEM_FOR_THE_GRAPH'=>		_('Update item for the graph'),
	'S_SORT_ORDER_0_100'=>			_('Sort order (0->100)'),
	'S_YAXIS_SIDE'=>			_('Y axis side'),
	'S_AXIS_SIDE'=>				_('Axis side'),
	'S_LEFT'=>					_('Left'),
	'S_FUNCTION'=>				_('Function'),
	'S_MIN_SMALL'=>				_('min'),
	'S_AVG_SMALL'=>				_('avg'),
	'S_MAX_SMALL'=>				_('max'),
	'S_LST_SMALL'=>				_('last'),
	'S_DRAW_STYLE'=>			_('Draw style'),
	'S_SIMPLE'=>				_('Simple'),
	'S_GRAPH_TYPE'=>			_('Graph type'),
	'S_STACKED'=>				_('Stacked'),
	'S_NORMAL'=>				_('Normal'),
	'S_PIE'=>				_('Pie'),
	'S_EXPLODED'=>				_('Exploded'),
	'S_AGGREGATED'=>			_('Aggregated'),
	'S_AGGREGATED_PERIODS_COUNT'=>		_('Aggregated periods count'),

//	graphs.php
	'S_ADD_GRAPH_ITEMS' => _('add graph items first'),
	'S_TITLE'=>				_('Title'),
	'S_PERCENTILE_LINE'=>			_('Percentile line'),
	'S_CONFIGURATION_OF_GRAPHS'=>		_('Configuration of graphs'),
	'S_CONFIGURATION_OF_GRAPHS_BIG'=>	_('CONFIGURATION OF GRAPHS'),
	'S_GRAPH_ADDED'=>			_('Graph added'),
	'S_GRAPH_UPDATED'=>			_('Graph updated'),
	'S_CANNOT_UPDATE_GRAPH'=>		_('Cannot update graph'),
	'S_GRAPH_DELETED'=>			_('Graph deleted'),
	'S_CANNOT_DELETE_GRAPH'=>		_('Cannot delete graph'),
	'S_GRAPHS_DELETED'=>			_('Graphs deleted'),
	'S_CANNOT_DELETE_GRAPHS'=>		_('Cannot delete graphs'),
	'S_CANNOT_ADD_GRAPH'=>			_('Cannot add graph'),
	'S_ANOTHER_ITEM_SUM'=>			_('Cannot add more than one item with type "Graph sum"'),
	'S_ID'=>				_('ID'),
	'S_NO_GRAPHS_DEFINED'=>			_('No graphs defined.'),
	'S_DELETE_GRAPH_Q'=>			_('Delete graph?'),
	'S_YAXIS_MIN_VALUE'=>			_('Y axis MIN value'),
	'S_YAXIS_MAX_VALUE'=>			_('Y axis MAX value'),
	'S_CALCULATED'=>			_('Calculated'),
	'S_FIXED'=>				_('Fixed'),
	'S_CREATE_GRAPH'=>			_('Create graph'),
	'S_SHOW_WORKING_TIME'=>			_('Show working time'),
	'S_SHOW_TRIGGERS'=>			_('Show triggers'),
	'S_3D_VIEW'=>				_('3D view'),
	'S_LEGEND'=>				_('Legend'),
	'S_SHOW_LEGEND'=>			_('Show legend'),
	'S_GRAPH_SUM'=>				_('Graph sum'),
	'S_GRAPH_ITEM'=>			_('Graph item'),
	'S_REQUIRED_ITEMS_FOR_GRAPH'=>		_('Items required for graph'),
	'S_NO_TARGET_SELECTED'=>		_('No target selected'),
	'S_DELETE_SELECTED_GRAPHS'=>		_('Delete selected graphs?'),

//	history.php
	'S_SELECT_ROWS_WITH_VALUE_LIKE'=>	_('Select rows with value like'),
	'S_LAST_HOUR_GRAPH'=>			_('Last hour graph'),
	'S_LAST_WEEK_GRAPH'=>			_('Last week graph'),
	'S_LAST_MONTH_GRAPH'=>			_('Last month graph'),
	'S_500_LATEST_VALUES'=>			_('500 latest values'),
	'S_TIMESTAMP'=>				_('Timestamp'),
	'S_LOCAL'=>				_('Local'),
	'S_SOURCE'=>				_('Source'),
	'S_INFO'=>					_('Info'),
	'S_EVENT_ID'=>				_('Event ID'),
	'S_SHOW_UNKNOWN_EVENTS'=>			_('Show unknown events'),
	'S_REMOVE_SELECTED'=>			_('Remove selected'),
	'S_ITEMS_LIST'=>				_('Items list'),
	'S_SHOW_SELECTED'=>			_('Show selected'),
	'S_HIDE_SELECTED'=>			_('Hide selected'),
	'S_MARK_SELECTED'=>			_('Mark selected'),
	'S_MARK_OTHERS'=>			_('Mark others'),

	'S_AS_RED'=>				_('as Red'),
	'S_AS_GREEN'=>				_('as Green'),
	'S_AS_BLUE'=>				_('as Blue'),

	'S_FAILURE_AUDIT'=>			_('Failure Audit'),
	'S_SUCCESS_AUDIT'=>			_('Success Audit'),

	'S_HISTORY_LOG_ITEM_DATE_FORMAT'=>	_('[Y.M.d H:i:s]'),
	'S_HISTORY_LOG_LOCALTIME_DATE_FORMAT'=>	_('Y.M.d H:i:s'),
	'S_HISTORY_LOG_ITEM_PLAINTEXT'=>	_('Y-m-d H:i:s'),
	'S_HISTORY_PLAINTEXT_DATE_FORMAT'=>	_('Y-m-d H:i:s'),
	'S_HISTORY_ITEM_DATE_FORMAT'=>		_('Y.M.d H:i:s'),

	'S_DATE'=>				_('Date'),

	'S_JANUARY'=>				_('January'),
	'S_FEBRUARY'=>				_('February'),
	'S_MARCH'=>				_('March'),
	'S_APRIL'=>				_('April'),
	'S_MAY'=>				_('May'),
	'S_JUNE'=>				_('June'),
	'S_JULY'=>				_('July'),
	'S_AUGUST'=>				_('August'),
	'S_SEPTEMBER'=>				_('September'),
	'S_OCTOBER'=>				_('October'),
	'S_NOVEMBER'=>				_('November'),
	'S_DECEMBER'=>				_('December'),

// hostgroups.php
	'S_CONFIGURATION_OF_GROUPS'=>		_('CONFIGURATION OF HOST GROUPS'),
	'S_DELETE_SELECTED_GROUP'=>		_('Delete selected group?'),
	'S_ENABLE_SELECTED_HOST_GROUPS'=>	_('Enable selected host groups?'),
	'S_DISABLE_SELECTED_HOST_GROUPS'=>	_('Disable selected host groups?'),
	'S_DELETE_SELECTED_HOST_GROUPS'=>	_('Delete selected host groups?'),
	'S_DELETE_SELECTED_GROUPS'=>		_('Delete selected groups'),

//	hosts.php
	'S_HOST_INTERFACE' =>			_('Host interface'),
	'S_IPMI'=>			_('IPMI'),
	'S_MACROS'=>					_('Macros'),
	'S_MACRO'=>					_('Macro'),
	'S_WRONG_MACRO'=>				_('Wrong macro'),
	'S_MACRO_TOO_LONG'=>				_('Macro name is too long, should not exceed 64 chars.'),
	'S_MACRO_VALUE_TOO_LONG'=>			_('Macro value is too long, should not exceed 255 chars.'),
	'S_EMPTY_MACRO_VALUE'=>				_('Empty macro value'),
	'S_IN'=>					_('In'),
	'S_APPLICATION'=>				_('Application'),
	'S_APPLICATIONS'=>				_('Applications'),
	'S_APPLICATIONS_BIG'=>				_('APPLICATIONS'),
	'S_CREATE_APPLICATION'=>			_('Create application'),
	'S_APPLICATION_UPDATED'=>			_('Application updated'),
	'S_CANNOT_UPDATE_APPLICATION'=>			_('Cannot update application'),
	'S_APPLICATION_ADDED'=>				_('Application added'),
	'S_CANNOT_ADD_APPLICATION'=>			_('Cannot add application'),
	'S_APPLICATION_DELETED'=>			_('Application deleted'),
	'S_CANNOT_DELETE_APPLICATION'=>			_('Cannot delete application'),
	'S_NO_APPLICATIONS_DEFINED'=>			_('No applications defined.'),
	'S_HOSTS'=>					_('Hosts'),
	'S_ITEMS'=>					_('Items'),
	'S_ITEMS_BIG'=>					_('ITEMS'),
	'S_TRIGGERS'=>					_('Triggers'),
	'S_GRAPHS'=>					_('Graphs'),
	'S_CANNOT_UPDATE_HOST'=>			_('Cannot update host'),
	'S_HOST_STATUS_UPDATED'=>			_('Host status updated'),
	'S_HOST_GROUPS_BIG'=>				_('HOST GROUPS'),
	'S_NO_HOST_GROUPS_DEFINED'=>			_('No host groups defined.'),
	'S_NO_HOSTS_DEFINED'=>				_('No hosts defined.'),
	'S_NO_TEMPLATES_DEFINED'=>			_('No templates defined.'),
	'S_NO_PROXIES_DEFINED'=>			_('No proxies defined.'),
	'S_HOST'=>					_('Host'),
	'S_CONNECT_TO'=>				_('Connect to'),
	'S_DNS'=>					_('DNS'),
	'S_IP'=>					_('IP'),
	'S_PORT'=>					_('Port'),
	'S_TEMPLATE'=>					_('Template'),
	'S_DELETED'=>					_('Deleted'),
	'S_UNKNOWN'=>					_('Unknown'),
	'S_GROUPS'=>					_('Groups'),
	'S_ALL_GROUPS'=>				_('All groups'),
	'S_DNS_NAME'=>					_('DNS name'),
	'S_IP_ADDRESS'=>				_('IP address'),
	'S_LINK_ADDITIONAL_TEMPLATES'=>			_('Link additional templates'),
	'S_RELINK_TEMPLATES'=>			_('Replace linked templates'),
	'S_IPMI_AUTHTYPE'=>				_('IPMI authentication algorithm'),
	'S_AUTHTYPE_DEFAULT'=>				_('Default'),
	'S_AUTHTYPE_NONE'=>				_('None'),
	'S_AUTHTYPE_MD2'=>				_('MD2'),
	'S_AUTHTYPE_MD5'=>				_('MD5'),
	'S_AUTHTYPE_STRAIGHT'=>				_('Straight'),
	'S_AUTHTYPE_OEM'=>				_('OEM'),
	'S_AUTHTYPE_RMCP_PLUS'=>			_('RMCP+'),
	'S_IPMI_PRIVILEGE'=>				_('IPMI privilege level'),
	'S_PRIVILEGE_CALLBACK'=>			_('Callback'),
	'S_PRIVILEGE_USER'=>				_('User'),
	'S_PRIVILEGE_OPERATOR'=>			_('Operator'),
	'S_PRIVILEGE_ADMIN'=>				_('Admin'),
	'S_PRIVILEGE_OEM'=>				_('OEM'),
	'S_IPMI_USERNAME'=>				_('IPMI username'),
	'S_IPMI_PASSWORD'=>				_('IPMI password'),
	'S_DELETE_SELECTED_HOST_Q'=>			_('Delete selected host?'),
	'S_DELETE_SELECTED_WITH_LINKED_ELEMENTS'=>	_('Delete selected with linked elements'),
	'S_HOST_GROUP'=>				_('Host group'),
	'S_HOST_GROUPS'=>				_('Host groups'),
	'S_UPDATE'=>					_('Update'),
	'S_AVAILABILITY'=>				_('Availability'),
	'S_AVAILABLE'=>					_('Available'),
	'S_NOT_AVAILABLE'=>				_('Not available'),
	'S_PROXIES'=>					_('Proxies'),
	'S_PROXIES_BIG'=>				_('PROXIES'),
	'S_PROXY'=>					_('Proxy'),
	'S_CREATE_PROXY'=>				_('Create proxy'),
	'S_PROXY_NAME'=>				_('Proxy name'),
	'S_LASTSEEN_AGE'=>				_('Last seen (age)'),
	'S_UPDATED_STATUS_OF_HOST' =>		_('Updated status of host'),
	'S_CLEAR_WHEN_UNLINKING' => _('Clear when unlinking'),
	'S_SNMP' =>						_('SNMP'),
	'S_INTERFACES' =>				_('Interfaces'),
	'S_INTERFACE' =>				_('Interface'),
	'S_JMX' =>						_('JMX'),

// templates.php
	'S_TEMPLATE_ADDED'=>			_('New template added'),
	'S_CANNOT_ADD_TEMPLATE'=>		_('Cannot add template'),
	'S_TEMPLATE_UPDATED'=>			_('Template updated'),
	'S_CANNOT_UPDATE_TEMPLATE'=>		_('Cannot update template'),
	'S_LINKED_TEMPLATES'=>			_('Linked templates'),
	'S_LINKED_TO'=>				_('Linked to'),
	'S_DELETE_AND_CLEAR'=>			_('Delete and clear'),
	'S_DELETE_SELECTED_TEMPLATES_Q'=>	_('Delete selected templates?'),
	'S_WARNING_THIS_DELETE_TEMPLATES_AND_CLEAR'=>_('Delete and clear selected templates? (Warning: all linked hosts will be cleared!)'),
	'S_DELETE_TEMPLATE_Q' => _('Delete template?'),
	'S_DELETE_AND_CLEAR_TEMPLATE_Q' => _('Delete and clear template? (Warning: all linked hosts will be cleared!)'),

	'S_TEMPLATE_LINKAGE'=>			_('Template linkage'),
	'S_TEMPLATES'=>				_('Templates'),
	'S_UNLINK'=>				_('Unlink'),
	'S_CANNOT_UNLINK_TEMPLATE'=>	_('Cannot unlink template'),
	'S_CANNOT_LINK_TEMPLATE'=>	_('Cannot link template'),
	'S_UNLINK_AND_CLEAR'=>			_('Unlink and clear'),
	'S_MONITORED_BY_PROXY'=>		_('Monitored by proxy'),

//	items.php
	'S_TEMPLATED_ITEMS'=>					_('Templated items'),
	'S_NOT_TEMPLATED_ITEMS'=>				_('Not Templated items'),
	'S_WITH_TRIGGERS'=>					_('With triggers'),
	'S_WITHOUT_TRIGGERS'=>					_('Without triggers'),
	'S_TYPES'=>						_('Types'),
	'S_NO_ITEMS_DEFINED'=>					_('No items defined.'),
	'S_HISTORY_CLEARED'=>					_('History cleared'),
	'S_CLEAR_HISTORY_FOR_SELECTED'=>			_('Clear history for selected'),
	'S_CLEAR_HISTORY'=>					_('Clear history'),
	'S_CANNOT_CLEAR_HISTORY'=>				_('Cannot clear history'),
	'S_CONFIGURATION_OF_ITEMS'=>				_('Configuration of items'),
	'S_CONFIGURATION_OF_ITEMS_BIG'=>			_('CONFIGURATION OF ITEMS'),
	'S_CANNOT_UPDATE_ITEM'=>				_('Cannot update item'),
	'S_CANNOT_ADD_ITEM'=>					_('Cannot add item'),
	'S_ITEM_DELETED'=>					_('Item deleted'),
	'S_CANNOT_DELETE_ITEM'=>				_('Cannot delete item'),
	'S_ITEMS_DELETED'=>					_('Items deleted'),
	'S_CANNOT_DELETE_ITEMS'=>				_('Cannot delete items'),
	'S_ITEMS_ACTIVATED'=>					_('Items activated'),
	'S_ITEMS_DISABLED'=>					_('Items disabled'),
	'S_KEY'=>						_('Key'),
	'S_DESCRIPTION'=>					_('Description'),
	'S_UPDATE_INTERVAL'=>					_('Update interval'),
	'S_INTERVAL'=>						_('Interval'),
	'S_HISTORY'=>						_('History'),
	'S_TRENDS'=>						_('Trends'),
	'S_WEB_CHECKS_BIG'=>					_('WEB CHECKS'),
	'S_ACTIVE'=>						_('Active'),
	'S_NOT_SUPPORTED'=>					_('Not supported'),
	'S_UNITS'=>						_('Units'),
	'S_UPDATE_INTERVAL_IN_SEC'=>				_('Update interval (in sec)'),
	'S_KEEP_HISTORY_IN_DAYS'=>				_('Keep history (in days)'),
	'S_KEEP_TRENDS_IN_DAYS'=>				_('Keep trends (in days)'),
	'S_TYPE_OF_INFORMATION'=>				_('Type of information'),
	'S_DATA_TYPE'=>						_('Data type'),
	'S_DECIMAL'=>						_('Decimal'),
	'S_OCTAL'=>						_('Octal'),
	'S_HEXADECIMAL'=>					_('Hexadecimal'),
	'S_BOOLEAN'=>					_('Boolean'),
	'S_STORE_VALUE'=>					_('Store value'),
	'S_SHOW_VALUE'=>					_('Show value'),
	'S_NUMERIC_UINT64'=>					_('Numeric (integer 64bit)'),
	'S_NUMERIC_UNSIGNED'=>					_('Numeric (unsigned)'),
	'S_NUMERIC_FLOAT'=>					_('Numeric (float)'),
	'S_CHARACTER'=>						_('Character'),
	'S_WIZARD'=>						_('Wizard'),
	'S_LOG'=>						_('Log'),
	'S_TEXT'=>						_('Text'),
	'S_AS_IS'=>						_('As is'),
	'S_DELTA_SPEED_PER_SECOND'=>				_('Delta (speed per second)'),
	'S_DELTA_SIMPLE_CHANGE'=>				_('Delta (simple change)'),
	'S_ITEM'=>						_('Item'),
	'S_SNMP_COMMUNITY'=>					_('SNMP community'),
	'S_SNMP_OID'=>						_('SNMP OID'),
	'S_ALLOWED_HOSTS'=>					_('Allowed hosts'),
	'S_SNMPV3_SECURITY_NAME'=>				_('SNMPv3 security name'),
	'S_SNMPV3_SECURITY_LEVEL'=>				_('SNMPv3 security level'),
	'S_SNMPV3_AUTH_PASSPHRASE'=>				_('SNMPv3 auth passphrase'),
	'S_SNMPV3_PRIV_PASSPHRASE'=>				_('SNMPv3 priv passphrase'),
	'S_CUSTOM_MULTIPLIER'=>					_('Custom multiplier'),
	'S_USE_CUSTOM_MULTIPLIER'=>				_('Use custom multiplier'),
	'S_LOG_TIME_FORMAT'=>					_('Log time format'),
	'S_CREATE_ITEM'=>					_('Create item'),
	'S_X_ELEMENTS_COPY_TO_DOT_DOT_DOT'=>			_('elements copy to ...'),
	'S_MODE'=>						_('Mode'),
	'S_TARGET'=>						_('Target'),
	'S_TARGET_TYPE'=>					_('Target type'),
	'S_COPY'=>						_('Copy'),
	'S_HISTORY_CLEARING_CAN_TAKE_A_LONG_TIME_CONTINUE_Q'=>	_('History clearing can take a long time. Continue?'),
	'S_MASS_UPDATE'=>					_('Mass update'),
	'S_SEARCH'=>						_('Search'),
	'S_ORIGINAL'=>						_('Original'),
	'S_NEW_FLEXIBLE_INTERVAL'=>				_('New flexible interval'),
	'S_FLEXIBLE_INTERVALS'=>				_('Flexible intervals (sec)'),
	'S_NO_FLEXIBLE_INTERVALS'=>				_('No flexible intervals'),
	'S_PARAMS'=>						_('Additional parameters'),
	'S_NEW_APPLICATION'=>					_('New application'),
	'S_IPMI_SENSOR'=>					_('IPMI sensor'),
	'S_TEMPLATED_ITEM'=>					_('Templated item'),
	'S_WEB_ITEM'=>						_('Web item'),
	'S_EXECUTED_SCRIPT'=>					_('Executed script'),
	'S_USER_NAME'=>						_('User name'),
	'S_PUBLIC_KEY_FILE'=>					_('Public key file'),
	'S_PRIVATE_KEY_FILE'=>					_('Private key file'),
	'S_PUBLIC_KEY'=>					_('Public key'),
	'S_AUTHENTICATION_METHOD'=>				_('Authentication method'),
	'S_EDIT_TRIGGER'=>					_('Edit trigger'),
	'S_ENABLE_SELECTED_ITEMS_Q'=>				_('Enable selected items?'),
	'S_DISABLE_SELECTED_ITEMS_Q'=>				_('Disable selected items?'),
	'S_MASS_UPDATE_SELECTED_ITEMS_Q'=>			_('Mass update selected items?'),
	'S_COPY_SELECTED_ITEMS_Q'=>				_('Copy selected items?'),
	'S_DELETE_HISTORY_SELECTED_ITEMS_Q'=>			_('Delete history of selected items?'),
	'S_DELETE_SELECTED_ITEMS_Q'=>				_('Delete selected items?'),
	'S_FORMULA'=>						_('Formula'),
	'S_RULE'=>						_('Rule'),

// host_discovery.php
	'S_CONFIGURATION_OF_DISCOVERY_RULES_BIG' => _('CONFIGURATION OF DISCOVERY RULES'),
	'S_REGEXP' => _('Regexp'),

// disc_prototypes.php
	'S_CONFIGURATION_OF_ITEM_PROTOTYPES_BIG' => _('CONFIGURATION OF ITEM PROTOTYPES'),
	'S_ITEM_PROTOTYPES_OF_BIG' => _('ITEM PROTOTYPES OF'),
	'S_ADD_PROTOTYPE' => _('Add prototype'),
	'S_SELECT_PROTOTYPE' => _('Select prototype'),
	'S_ITEM_PROTOTYPE' => _('Item prototype'),

	'S_CONFIGURATION_OF_GRAPHS_PROTOTYPES_BIG' => _('CONFIGURATION OF GRAPH PROTOTYPES'),

// trigger_prototypes.php
	'S_CONFIGURATION_OF_TRIGGERS_PROTOTYPES_BIG' => _('CONFIGURATION OF TRIGGER PROTOTYPES'),
	'S_TRIGGER_PROTOTYPES_OF_BIG' => _('TRIGGER PROTOTYPES OF'),

// graph_prototypes.php
	'S_GRAPH_PROTOTYPES_OF_BIG' => _('GRAPH PROTOTYPES OF'),

//	events.php
	'S_EVENT'=>				_('Event'),
	'S_LIST'=>				_('List'),
	'S_LATEST_EVENTS'=>			_('Latest events'),
	'S_HISTORY_OF_EVENTS_BIG'=>		_('HISTORY OF EVENTS'),
	'S_NO_EVENTS_FOUND'=>			_('No events found'),
	'S_EVENTS_DATE_FORMAT'=>		_('d M Y H:i:s'),
	'S_EVENTS_DISCOVERY_TIME_FORMAT'=>	_('d M Y H:i:s'),
	'S_EVENTS_ACTION_TIME_FORMAT'=>		_('d M Y H:i:s'),
	'S_UP_BIG'=>		_('UP'),
	'S_DOWN_BIG'=>		_('DOWN'),
	'S_DISCOVERED_BIG'=>		_('DISCOVERED'),
	'S_LOST_BIG'=>		_('LOST'),

//	events.inc.php
	'S_EVENTS_TRIGGERS_EVENTS_HISTORY_LIST_DATE_FORMAT'=>	_('d M Y H:i:s'),

//	latest.php
	'S_LAST_CHECK'=>			_('Last check'),
	'S_LAST_VALUE'=>			_('Last value'),
	'S_LATEST_ITEMS_TRIGGERS_DATE_FORMAT'=>	_('d M Y H:i:s'),

//	sysmap.php
	'S_HIDDEN'=>			_('Hidden'),
	'S_SHOWN'=>				_('Shown'),
	'S_URLS'=>				_('URLs'),
	'S_LABEL'=>				_('Label'),
	'S_GO_TO'=>				_('Go to'),
	'S_X'=>					_('X'),
	'S_CONFIGURATION_OF_NETWORK_MAPS'=>	_('Configuration of network maps'),
	'S_NO_IMAGES' => 'You need to have at least one image uploaded to create map element. Images can be uploaded in Administration->General->Images section.',

	'S_MAINTENANCE_BIG'=>			_('MAINTENANCE'),

//	sysmaps.php
	'S_MAP_DELETED'=>			_('Network map deleted'),
	'S_CANNOT_DELETE_MAP'=>			_('Cannot delete network map'),
	'S_MAPS_BIG'=>					_('MAPS'),
	'S_NO_MAPS_DEFINED'=>			_('No maps defined.'),
	'S_CREATE_MAP'=>				_('Create map'),
	'S_IMPORT_MAP'=>				_('Import map'),
	'S_DELETE_SELECTED_MAPS_Q'=>		_('Delete selected maps?'),
	'S_MAP_ADDED'=>					_('Network map added'),
	'S_CANNOT_ADD_MAP'=>			_('Cannot add network map'),
	'S_MAP_UPDATED'=>				_('Network map updated'),
	'S_CANNOT_UPDATE_MAP'=>			_('Cannot update network map'),
	'S_TWO_ELEMENTS_SHOULD_BE_SELECTED'=>		_('Two elements should be selected'),
	'S_DELETE_SELECTED_ELEMENTS_Q'=>		_('Delete selected elements?'),
	'S_PLEASE_SELECT_TWO_ELEMENTS'=>		_('Please select two elements'),
	'S_DELETE_LINKS_BETWEEN_SELECTED_ELEMENTS_Q'=>	_('Delete links between selected elements?'),
	'S_NEW_ELEMENT'=>				_('New element'),

	'S_BOTTOM'=>					_('Bottom'),
	'S_TOP'=>						_('Top'),

	'S_CANNOT_FIND_IMAGE'=>			_('Cannot find image'),
	'S_CANNOT_FIND_BACKGROUND_IMAGE'=>	_('Cannot find background image'),
	'S_CANNOT_FIND_TRIGGER'=>		_('Cannot find trigger'),
	'S_CANNOT_FIND_HOST'=>			_('Cannot find host'),
	'S_CANNOT_FIND_HOSTGROUP'=>		_('Cannot find hostgroup'),
	'S_CANNOT_FIND_MAP'=>			_('Cannot find map'),
	'S_CANNOT_FIND_SCREEN'=>		_('Cannot find screen'),
	'S_USED_IN_EXPORTED_MAP_SMALL'=>_('used in exported map'),

	'S_INCORRECT_ELEMENT_MAP_LINK' => _('All links should have "Name" and "URL" specified'),
	'S_EACH_URL_SHOULD_HAVE_UNIQUE' => _('Each URL should have a unique name. Please make sure there is only one URL named'),

//	map.php
	'S_OK_BIG'=>			_('OK'),
	'S_PROBLEM_BIG'=>		_('PROBLEM'),
	'S_ZABBIX_URL'=>		_('http://www.zabbix.com'),
	'S_UNACKNOWLEDGED' => _('Unacknowledged'),
	'S_EVENT_ACKNOWLEDGING_DISABLED' => _('Event acknowledging disabled'),

//	maps.php
	'S_NETWORK_MAPS'=>		_('Network maps'),
	'S_MAPS_DATE_FORMAT'=>	_('Y.m.d H:i:s'),

//	media.php
	'S_MEDIA'=>				_('Media'),
	'S_SEND_TO'=>				_('Send to'),
	'S_WHEN_ACTIVE'=>			_('When active'),
	'S_NEW_MEDIA'=>				_('New media'),
	'S_USE_IF_SEVERITY'=>			_('Use if severity'),
	'S_SAVE'=>				_('Save'),
	'S_CANCEL'=>				_('Cancel'),

//	Menu

//	dashboard.php
	'S_AGE'=>				_('Age'),
	'S_FAVOURITES'=>			_('Favourites'),
	'S_MENU'=>				_('Menu'),
	'S_RESET'=>				_('Reset'),
	'S_OF' => _('of'),

// dashconf.php
	'S_DASHBOARD_CONFIGURATION' =>	_('Dashboard configuration'),
	'S_DASHBOARD_CONFIGURATION_BIG' =>	_('DASHBOARD CONFIGURATION'),
	'S_SELECTED' => _('Selected'),
	'S_SHOW_HOSTS_IN_MAINTENANCE' => _('Show hosts in maintenance'),
	'S_TRIGGERS_WITH_SEVERITY' => _('Triggers with severity'),
	'S_DASHBOARD_FILTER' => _('Dashboard filter'),
	'S_O_ALL' => _('All'),
	'S_O_UNACKNOWLEDGED_ONLY' => _('Unacknowledged only'),
	'S_O_SEPARATED' => _('Separated'),
	'S_PROBLEM_DISPLAY' => _('Problem display'),

//	overview.php
	'S_OVERVIEW'=>				_('Overview'),
	'S_OVERVIEW_BIG'=>			_('OVERVIEW'),
	'S_HOSTS_LOCATION'=>			_('Hosts location'),
	'S_DATA'=>				_('Data'),
	'S_SHOW_GRAPH_OF_ITEM'=>		_('Show graph of item'),
	'S_SHOW_VALUES_OF_ITEM'=>		_('Show values of item'),
	'S_VALUES'=>				_('Values'),

//	queue.php
	'S_TOTAL'=>				_('Total'),
	'S_COUNT'=>				_('Count'),
	'S_QUEUE_NODES_DATE_FORMAT'=>		_('d M Y H:i:s'),

//	report1.php
	'S_REPORT_BIG'=>			_('REPORT'),
	'S_STATUS_OF_ZABBIX'=>			_('Status of Zabbix'),
	'S_STATUS_OF_ZABBIX_BIG'=>		_('STATUS OF ZABBIX'),
	'S_VALUE'=>				_('Value'),
	'S_VALUES_STORED'=>			_('Values stored'),
	'S_TRENDS_STORED'=>			_('Trends stored'),
	'S_NUMBER_OF_TRIGGERS_SHORT'=>		_('Triggers (e/d)[p/u/o]'),
	'S_NUMBER_OF_ITEMS_SHORT'=>		_('Items (m/d/n)'),
	'S_NUMBER_OF_USERS_SHORT'=>		_('Users (online)'),
	'S_NUMBER_OF_HOSTS_SHORT'=>		_('Hosts (m/n/t)'),
	'S_YES'=>				_('Yes'),
	'S_NO'=>				_('No'),
	'S_RUNNING'=>				_('running'),
	'S_NOT_RUNNING'=>			_('not running'),

//	report2.php
	'S_AVAILABILITY_REPORT'=>		_('Availability report'),
	'S_AVAILABILITY_REPORT_BIG'=>		_('AVAILABILITY REPORT'),
	'S_SHOW'=>				_('Show'),
	'S_BY_HOST'=>				_('By host'),
	'S_BY_TRIGGER_TEMPLATE'=>		_('By trigger template'),

//	chart4.php
	'S_CHART4_TIMELINE_DATE_FORMAT'=>	_('d.M'),

//	chart_bar.php
	'S_CHARTBAR_HOURLY_DATE_FORMAT'=>		_('Y.m.d H:i'),
	'S_CHARTBAR_DAILY_DATE_FORMAT'=>		_('Y.m.d'),

//	report3.php
	'S_IT_SERVICES_AVAILABILITY_REPORT'=>		_('IT services availability report'),
	'S_IT_SERVICES_AVAILABILITY_REPORT_BIG'=>	_('IT SERVICES AVAILABILITY REPORT'),
	'S_FROM'=>					_('From'),
	'S_TILL'=>					_('Till'),
	'S_OK'=>					_('Ok'),
	'S_RESOLVED'=>				_('Resolved'),
	'S_PROBLEM'=>					_('Problem'),
	'S_PROBLEMS'=>					_('Problems'),
	'S_PERCENTAGE'=>				_('Percentage'),
	'S_SLA'=>					_('SLA'),
	'S_DAY'=>					_('Day'),
	'S_MONTH'=>					_('Month'),
	'S_YEAR'=>					_('Year'),
	'S_HOURLY'=>					_('Hourly'),
	'S_DAILY'=>					_('Daily'),
	'S_WEEKLY'=>					_('Weekly'),
	'S_MONTHLY'=>					_('Monthly'),
	'S_YEARLY'=>					_('Yearly'),
	'S_REPORT3_ANNUALLY_DATE_FORMAT'=>		_('Y'),
	'S_REPORT3_MONTHLY_DATE_FORMAT'=>		_('M Y'),
	'S_REPORT3_DAILY_DATE_FORMAT'=>			_('d M Y'),
	'S_REPORT3_WEEKLY_DATE_FORMAT'=>		_('d M Y H:i'),

//	locales.php
	'S_CREATE'=>				_('Create'),


//	report4.php
	'S_NOTIFICATIONS_BIG'=>			_('NOTIFICATIONS'),
	'S_IT_NOTIFICATIONS'=>			_('Notification report'),
	'S_REPORT4_ANNUALLY_DATE_FORMAT'=>	_('Y'),
	'S_REPORT4_MONTHLY_DATE_FORMAT'=>	_('M Y'),
	'S_REPORT4_DAILY_DATE_FORMAT'=>		_('d M Y'),
	'S_REPORT4_WEEKLY_DATE_FORMAT'=>	_('d M Y H:i'),

//	report5.php
	'S_TRIGGERS_TOP_100'=>			_('Most busy triggers top 100'),
	'S_TRIGGERS_TOP_100_BIG'=>		_('MOST BUSY TRIGGERS TOP 100'),
	'S_NUMBER_OF_STATUS_CHANGES'=>		_('Number of status changes'),
	'S_WEEK'=>				_('Week'),

//	report6.php
	'S_BAR_REPORTS'=>			_('Bar reports'),
	'S_BAR_REPORT_1'=>			_('Distribution of values for multiple periods'),
	'S_BAR_REPORT_2'=>			_('Distribution of values for multiple items'),
	'S_BAR_REPORT_3'=>			_('Compare values for multiple periods'),

	'S_SELECTED_HOSTS'=>			_('Selected hosts'),
	'S_SELECTED_GROUPS'=>			_('Selected groups'),
	'S_SCALE'=>				_('Scale'),
	'S_AVERAGE_BY'=>			_('Average by'),
	'S_PALETTE'=>				_('Palette'),
	'S_DARKEN'=>				_('Darken'),
	'S_BRIGHTEN'=>				_('Brighten'),

//	reports.inc.php
	'S_REPORTS_BAR_REPORT_DATE_FORMAT'=>	_('d M Y H:i:s'),

//	screenconf.php
	'S_SCREENS'=>				_('Screens'),
	'S_SCREEN'=>				_('Screen'),
	'S_CONFIGURATION_OF_SCREENS_BIG'=>	_('CONFIGURATION OF SCREENS'),
	'S_CONFIGURATION_OF_SCREENS'=>		_('Configuration of screens'),
	'S_SCREEN_ADDED'=>			_('Screen added'),
	'S_CANNOT_ADD_SCREEN'=>			_('Cannot add screen'),
	'S_SCREEN_UPDATED'=>			_('Screen updated'),
	'S_CANNOT_UPDATE_SCREEN'=>		_('Cannot update screen'),
	'S_SCREEN_DELETED'=>			_('Screen deleted'),
	'S_CANNOT_DELETE_SCREEN'=>		_('Cannot delete screen'),
	'S_COLUMNS'=>				_('Columns'),
	'S_ROWS'=>				_('Rows'),
	'S_NO_SCREENS_DEFINED'=>		_('No screens defined.'),
	'S_DELETE_SCREEN_Q'=>			_('Delete screen?'),
	'S_SCREEN_CELL_CONFIGURATION'=>		_('Screen cell configuration'),
	'S_RESOURCE'=>					_('Resource'),
	'S_NO_RESOURCES_DEFINED'=>		_('No resources defined.'),
	'S_SIMPLE_GRAPH'=>				_('Simple graph'),
	'S_SIMPLE_GRAPHS'=>				_('Simple graphs'),
	'S_HISTORY_AND_SIMPLE_GRAPHS'=> _('History and simple graphs'),
	'S_GRAPH_NAME'=>				_('Graph name'),
	'S_WIDTH'=>						_('Width'),
	'S_HEIGHT'=>					_('Height'),
	'S_CREATE_SCREEN'=>				_('Create screen'),
	'S_CREATE_SLIDESHOW'=>			_('Create slide show'),
	'S_EDIT'=>						_('Edit'),
	'S_DYNAMIC_ITEM'=>				_('Dynamic item'),
	'S_DIMENSION_COLS_ROWS'=>		_('Dimension (cols x rows)'),
	'S_DELETE_SELECTED_SLIDESHOWS_Q'=>	_('Delete selected slideshows?'),

	'S_SLIDESHOW_MUST_CONTAIN_SLIDES' => _('Slideshow must contain slides'),
	'S_SLIDESHOWS'=>				_('Slide shows'),
	'S_SLIDESHOW'=>					_('Slide show'),
	'S_CONFIGURATION_OF_SLIDESHOWS_BIG'=>	_('CONFIGURATION OF SLIDE SHOWS'),
	'S_CONFIGURATION_OF_SLIDESHOWS'=>	_('Configuration of slideshows'),
	'S_SLIDESHOWS_BIG'=>			_('SLIDE SHOWS'),
	'S_NO_SLIDESHOWS_DEFINED'=>		_('No slide shows defined.'),
	'S_COUNT_OF_SLIDES'=>			_('Count of slides'),
	'S_NO_SLIDES_DEFINED'=>			_('No slides defined.'),
	'S_SLIDES'=>					_('Slides'),
	'S_NEW_SLIDE'=>					_('New slide'),
	'S_SHOW_TEXT_AS_HTML'=>			_('Show text as HTML'),

	'S_IMPORT_SCREEN'=>				_('Import screen'),

	'S_CANNOT_FIND_GRAPH'=>			_('Cannot find graph'),
	'S_CANNOT_FIND_ITEM'=>			_('Cannot find item'),
	'S_USED_IN_EXPORTED_SCREEN_SMALL'=>_('used in exported screen'),

//	screenedit.php
	'S_MAP'=>					_('Map'),
	'S_AS_PLAIN_TEXT'=>			_('As plain text'),
	'S_PLAIN_TEXT'=>			_('Plain text'),
	'S_COLUMN_SPAN'=>			_('Column span'),
	'S_ROW_SPAN'=>				_('Row span'),
	'S_SHOW_LINES'=>			_('Show lines'),
	'S_HOSTS_INFO'=>			_('Hosts info'),
	'S_TRIGGERS_INFO'=>			_('Triggers info'),
	'S_SERVER_INFO'=>			_('Server info'),
	'S_CLOCK'=>				_('Clock'),
	'S_TRIGGERS_OVERVIEW'=>			_('Triggers overview'),
	'S_DATA_OVERVIEW'=>			_('Data overview'),
	'S_HISTORY_OF_ACTIONS'=>		_('History of actions'),
	'S_HISTORY_OF_EVENTS'=>			_('History of events'),

	'S_TIME_TYPE'=>				_('Time type'),
	'S_SERVER_TIME'=>			_('Server time'),
	'S_LOCAL_TIME'=>			_('Local time'),
	'S_HOST_TIME'=>				_('Host time'),

	'S_STYLE'=>				_('Style'),
	'S_VERTICAL'=>				_('Vertical'),
	'S_HORIZONTAL'=>			_('Horizontal'),

	'S_HORIZONTAL_ALIGN'=>			_('Horizontal align'),
	'S_CENTRE'=>				_('Centre'),
	'S_RIGHT'=>				_('Right'),

	'S_VERTICAL_ALIGN'=>			_('Vertical align'),
	'S_MIDDLE'=>				_('Middle'),

//	screens.php
	'S_SCREENS_BIG'=>				_('SCREENS'),
	'S_HOST_SCREENS'=>				_('Host screens'),

	'S_SLIDESHOW_UPDATED'=>			_('Slideshow updated'),
	'S_CANNOT_UPDATE_SLIDESHOW'=>	_('Cannot update slideshow'),
	'S_SLIDESHOW_ADDED'=>			_('Slideshow added'),
	'S_CANNOT_ADD_SLIDESHOW'=>		_('Cannot add slideshow'),
	'S_SLIDESHOW_DELETED'=>			_('Slideshow deleted'),
	'S_CANNOT_DELETE_SLIDESHOW'=>	_('Cannot delete slideshow'),
	'S_DELETE_SLIDESHOW_Q'=>		_('Delete slideshow?'),

	'S_ERROR_SCREEN_WITH_ID_DOES_NOT_EXIST' => _('Screen with id "%d" does not exist'),

// slides.php
	'S_CUSTOM_SLIDES' =>			_('Custom slides'),

//	services.php
	'S_NO_IT_SERVICE_DEFINED'=>		_('No IT services defined.'),
	'S_NONE'=>				_('None'),
	'S_TRIGGER'=>				_('Trigger'),
	'S_DELETE'=>				_('Delete'),
	'S_CLONE'=>				_('Clone'),
	'S_FULL_CLONE'=>			_('Full clone'),
	'S_REMOVE'=>				_('Remove'),

//	triggers.php
	'S_NO_TRIGGER'=>			_('No trigger'),
	'S_NO_TRIGGERS_DEFINED'=>		_('No triggers defined.'),
	'S_CONFIGURATION_OF_TRIGGERS'=>		_('Configuration of triggers'),
	'S_CONFIGURATION_OF_TRIGGERS_BIG'=>	_('CONFIGURATION OF TRIGGERS'),
	'S_TRIGGERS_DELETED'=>			_('Triggers deleted'),
	'S_CANNOT_DELETE_TRIGGERS'=>		_('Cannot delete triggers'),
	'S_TRIGGER_DELETED'=>			_('Trigger deleted'),
	'S_CANNOT_DELETE_TRIGGER'=>		_('Cannot delete trigger'),
	'S_SEVERITY'=>				_('Severity'),
	'S_MIN_SEVERITY'=>			_('Min severity'),
	'S_DISABLED'=>				_('Disabled'),
	'S_DISABLED_BIG'=>			_('DISABLED'),
	'S_ENABLED'=>				_('Enabled'),
	'S_DISABLE'=>				_('Disable'),
	'S_ENABLE'=>				_('Enable'),
	'S_CHANGE'=>				_('Change'),
	'S_TRIGGER_UPDATED'=>			_('Trigger updated'),
	'S_CANNOT_UPDATE_TRIGGER'=>		_('Cannot update trigger'),
	'S_DEPENDENT'=>				_('Dependent'),
	'S_URL'=>				_('URL'),
	'S_CREATE_TRIGGER'=>			_('Create trigger'),
	'S_INSERT'=>				_('Insert'),
	'S_LAST_OF'=>				_('Last of'),
	'S_TIME_SHIFT'=>			_('Time shift'),
	'S_MULTIPLE_PROBLEM_EVENTS'=>		_('Multiple PROBLEM events'),
	'S_SHOW_DISABLED_TRIGGERS'=>		_('Show disabled triggers'),
	'S_HIDE_DISABLED_TRIGGERS'=>		_('Hide disabled triggers'),
	'S_THE_TRIGGER_DEPENDS_ON'=>		_('The trigger depends on'),
	'S_NO_DEPENDENCES_DEFINED'=>		_('No dependencies defined.'),
	'S_NEW_DEPENDENCY'=>			_('New dependency'),

	'S_EVENT_GENERATION'=>			_('Event generation'),

	'S_TRIGGERS_MASSUPDATE'=>		_('Triggers massupdate'),
	'S_TRIGGER_DEPENDENCIES'=>		_('Trigger dependencies'),
	'S_INCORRECT_DEPENDENCY'=>		_('Incorrect dependency'),

	'S_TOGGLE_INPUT_METHOD'=>		_('Toggle input method'),
	'S_INSERT_MACRO'=>			_('Insert macro'),
	'S_REPLACE'=>				_('Replace'),

	'S_ENABLE_SELECTED_TRIGGERS_Q'=>	_('Enable selected triggers?'),
	'S_DISABLE_SELECTED_TRIGGERS_Q'=>	_('Disable selected triggers?'),
	'S_MASS_UPDATE_SELECTED_TRIGGERS_Q'=>	_('Mass update selected triggers?'),
	'S_COPY_SELECTED_TRIGGERS_Q'=>		_('Copy selected triggers?'),
	'S_DELETE_SELECTED_TRIGGERS_Q'=>	_('Delete selected triggers?'),

	'S_TRIGGER_LOG_FORM'=>	_('Trigger form'),

//	tr_comments.php
	'S_TRIGGER_COMMENTS'=>			_('Trigger comments'),
	'S_TRIGGER_COMMENTS_BIG'=>		_('TRIGGER COMMENTS'),
	'S_COMMENT_UPDATED'=>			_('Comment updated'),
	'S_CANNOT_UPDATE_COMMENT'=>		_('Cannot update comment'),
	'S_ADD'=>						_('Add'),

//	tr_status.php
	'S_STATUS_OF_TRIGGERS'=>			_('Status of triggers'),
	'S_STATUS_OF_TRIGGERS_BIG'=>		_('STATUS OF TRIGGERS'),
	'S_STATUS_OF_HOSTGROUP_TRIGGERS'=>	_('Status of hostgroup triggers'),
	'S_STATUS_OF_HOST_TRIGGERS'=>		_('Status of host triggers'),
	'S_HIDE_ALL'=>				_('Hide all'),
	'S_SHOW_UNACKNOWLEDGED'=>		_('Show unacknowledged'),
	'S_SHOW_DETAILS'=>			_('Show details'),
	'S_FILTER_BY_NAME'=>				_('Filter by name'),
	'S_TRIGGERS_BIG'=>			_('TRIGGERS'),
	'S_COMMENTS'=>				_('Comments'),
	'S_ACK'=>				_('Ack'),
	'S_ACKNOWLEDGE_STATUS' => _('Acknowledge status'),
	'S_ANY' => _('Any'),
	'S_WITH_UNACKNOWLEDGED_EVENTS' => _('With unacknowledged events'),
	'S_WITH_LAST_EVENT_UNACKNOWLEDGED' => _('With last event unacknowledged'),
	'S_TRIGGERS_STATUS' => _('Triggers status'),
	'S_AGE_LESS_THAN' => _('Age less than'),
	'S_SELECT' => _('Select'),

//	users.php
	'S_USER'=>					_('User'),
	'S_PROXY_ADDED'=>			_('Proxy added'),
	'S_CANNOT_ADD_PROXY'=>		_('Cannot add proxy'),
	'S_PROXY_UPDATED'=>			_('Proxy updated'),
	'S_CANNOT_UPDATE_PROXY'=>	_('Cannot update proxy'),
	'S_PROXY_DELETED'=>			_('Proxy deleted'),
	'S_CANNOT_DELETE_PROXY'=>	_('Cannot delete proxy'),
	'S_NAME'=>					_('Name'),
	'S_DEBUG'=>					_('Debug'),
	'S_DENY'=>					_('Deny'),
	'S_HIDE'=>					_('Hide'),
	'S_PASSWORD'=>				_('Password'),
	'S_ADD_TO'=>				_('Add to'),
	'S_REMOVE_FROM'=>			_('Remove from'),

//scripts.php
	'S_SCRIPTS'=>				_('Scripts'),
	'S_COMMAND'=>				_('Command'),
	'S_RESULT'=>				_('Result'),
	'S_CLOSE'=>					_('Close'),

	'S_SCRIPT_ERROR'=>					_('SCRIPT ERROR'),
	'S_SCRIPT_ERROR_DESCRIPTION'=>		_('Error description'),
	'S_SCRIPT_SEND_ERROR'=>				_('Can\'t send command, check connection'),
	'S_SCRIPT_READ_ERROR'=>				_('Can\'t read script response, check connection'),
	'S_SCRIPT_TIMEOUT_ERROR'=>			_('Defined in "include/defines.inc.php" constant ZBX_SCRIPT_TIMEOUT timeout is reached. You can try to increase this value'),
	'S_SCRIPT_BYTES_LIMIT_ERROR'=>		_('Defined in "include/defines.inc.php" constant ZBX_SCRIPT_BYTES_LIMIT read bytes limit is reached. You can try to increase this value'),
	'S_SCRIPT_ERROR_EMPTY_RESPONSE'=>	_('Empty response received'),

//	audit.php
	'S_AUDIT'=>					_('Audit'),
	'S_AUDIT_LOGS_BIG'=>		_('AUDIT LOGS'),
	'S_AUDIT_ACTIONS_BIG'=>		_('AUDIT ACTIONS'),
	'S_ACTION'=>				_('Action'),
	'S_DETAILS'=>				_('Details'),
	'S_UNKNOWN_ACTION'=>		_('Unknown action'),
	'S_ADDED'=>					_('Added'),
	'S_UPDATED'=>				_('Updated'),
	'S_ALREADY_EXISTS_SMALL'=>	_('already exists'),

//	profile.php
	'S_CLEAR' =>				_('Clear'),
	'S_MOVE'=>					_('Move'),
	'S_UNMUTE'=>				_('Unmute'),
	'S_MUTE'=>					_('Mute'),
	'S_SNOOZE'=>				_('Snooze'),
	'S_MESSAGES'=>				_('Messages'),
	'S_PROBLEM_ON'=>			_('Problem on'),

//	index.php
	'S_ZABBIX_BIG'=>			_('ZABBIX'),

//	hostinventoriesoverview.php
	'S_HOST_INVENTORY_OVERVIEW'=>	_('Host inventory overview'),

//	search.php
	'S_EDIT_HOSTS'=>			_('Edit hosts'),
	'S_SEARCH_BIG'=>			_('SEARCH'),
	'S_GO'=>					_('Go'),
	'S_FOUND_SMALL'=>			_('found'),
	'S_DISPLAYING'=>			_('Displaying'),
	'S_SEARCH_PATTERN_EMPTY'=>	_('Search pattern is empty'),

//	popup.php
	'S_CAPTION'=>				_('Caption'),
	'S_EMPTY'=>					_('Empty'),
	'S_NO_ITEMS'=>				_('No items'),
	'S_DISCOVERY_RULES_BIG'=>	_('DISCOVERY RULES'),
	'S_CANNOT_SWITCH_HOSTS'=>	_('You can not switch hosts for current selection'),

//	popup_period.php
	'S_POPUP_PERIOD_CAPTION_DATE_FORMAT'=>	_('d M Y H:i:s'),

//	forms.inc.php
	'S_EXPRESSION_PART_ERROR'=>			_('Error'),
	'S_EXPRESSION_PART_NO_ERROR'=>			_('No errors found'),
	'S_EXPRESSION_SYNTAX_ERROR'=> 			_('Expression Syntax Error'),
	'S_EXPRESSION_UNEXPECTED_END_OF_ELEMENT_ERROR'=>_('Unexpected end of element'),
	'S_CHECK_EXPRESSION_PART_STARTING_FROM_PART1'=>	_('Check expression part starting from \''),
	'S_CHECK_EXPRESSION_PART_STARTING_FROM_PART2'=>	_('\''),
	'S_EXPRESSION_NOT_ALLOWED_SYMBOLS_OR_SEQUENCE_ERROR'=>_('Not allowed symbols or sequence of symbols in expression element detected'),
	'S_EXPRESSION_NOT_ALLOWED_VALUE_IN_ELEMENT_ERROR'=>_('Not allowed value detected in element'),
	'S_EXPRESSION_UNNECESSARY_SYMBOLS_DETECTED_ERROR'=>_('Unnecessary symbols detected'),
	'S_EXPRESSION_NOT_ALLOWED_SYMBOLS_AFTER_ERROR'=>_('Not allowed symbols detected after element'),
	'S_EXPRESSION_NOT_ALLOWED_SYMBOLS_BEFORE_ERROR'=>_('Not allowed symbols detected before element'),

//	tr_logform.php
	'S_INCLUDE_S'=>		_('Include'),
	'S_EXCLUDE'=>		_('Exclude'),
	'S_KEYWORD'=>		_('Keyword'),
	'S_POSITION'=>		_('Position'),
	'S_DELETE_EXPRESSION_Q'=>	_('Delete expression?'),
	'S_DELETE_KEYWORD_Q'=>		_('Delete keyword?'),

//  tr_testexpr.php
	'S_TEST_DATA'=>		_('Test data'),
	'S_EXPRESSION_VARIABLE_ELEMENTS'=>	_('Expression Variable Elements'),
	'S_EXPRESSION_VALUE_TYPE_UNKNOWN'=>	_('Unknown variable type, testing not available'),
	'S_EXPRESSION_HOST_UNKNOWN'=>		_('Unknown host, no such host present in system'),
	'S_EXPRESSION_HOST_ITEM_UNKNOWN'=>	_('Unknown host item, no such item in selected host'),
	'S_EXPRESSION_NOT_A_MACRO_ERROR'=>	_('Given expression is not a macro'),
	'S_RESULT_TYPE'=>	_('Result type'),
	'S_COMBINED_RESULT'=>	_('Combined result'),

//  applications.php
	'S_DELETE_APPLICATION'=>	_('Delete this application?'),
	'S_ACTIVATE_SELECTED_APPLICATIONS' => _('Activate selected applications?'),
	'S_DISABLE_SELECTED_APPLICATIONS' => _('Disable selected applications?'),
	'S_DELETE_SELECTED_APPLICATIONS'  => _('Delete selected applications?'),

// popup_media.php
	'S_INCORRECT_TIME_PERIOD'=>	_('Incorrect time period'),

// main.js
	'S_NO_ELEMENTS_SELECTED'=>	_('No elements selected!'),

// page_header.php

//	copt.inc.php
	'S_STATS_FOR'=>			_('Stats for'),
	'S_TOTAL_TIME'=>		_('Total time'),
	'S_MEMORY_LIMIT'=>		_('Memory limit'),
	'S_MEMORY_USAGE'=>		_('Memory usage'),
	'S_SQL_SELECTS_COUNT'=>		_('SQL selects count'),
	'S_SQL_EXECUTES_COUNT'=>	_('SQL executes count'),
	'S_SQL_REQUESTS_COUNT'=>	_('SQL requests count'),
	'S_TOTAL_TIME_SPENT_ON_SQL'=>	_('Total time spent on SQL'),
	'S_END_OF'=>			_('End of'),
	'S_MEMORY_LIMIT_REACHED'=>	_('MEMORY LIMIT REACHED! Profiling was stopped to save memory for script processing.'),

// 	func.inc.php
	'S_E'=>			_('E'),
	'S_Y'=>			_('Y'),

//	forms.inc.php
	'S_SUBFILTER'=>				_('Subfilter'),
	'S_AFFECTS_ONLY_FILTERED_DATA_SMALL'=>	_('affects only filtered data!'),
	'S_SHOW_VALUE_MAPPINGS'=>			_('show value mappings'),
	'S_DELETE_SELECTED_ITEM_Q'=>		_('Delete selected item?'),
	'S_DO'=>				_('Do'),
	'S_INCORRECT_LIST_OF_ITEMS'=>		_('Incorrect list of items.'),
	'S_DELETE_TRIGGER_Q'=>			_('Delete trigger?'),

//	items.inc.php
	'S_INCORRECT_ARGUMENTS_PASSED_TO_FUNCTION'=>	_('Incorrect arguments passed to function'),
	'S_ALREADY_EXISTS_FOR_HOST_SMALL'=>		_('already exists for host'),
	'S_UPDATED_SMALL'=>				_('updated'),
	'S_NO_ITEM_WITH'=>				_('No item with'),

//	httptest.inc.php
	'S_CANNOT_ADD_NEW_APPLICATION'=>		_('Cannot add new application'),
	'S_ADDED_SMALL'=>				_('added'),

//	hosts.inc.php
	'S_INTERNAL_AND_CANNOT_DELETED_SMALL'=>		_('is internal and can not be deleted'),
	'S_NO_HOST_WITH'=>				_('No host with'),
	'S_USED_BY_SCENARIO_SMALL'=>			_('used by scenario'),
	'S_NO_APPLICATION_WITH'=>			_('No application with'),
	'S_TEMPLATE_WITH_ITEM_KEY'=>			_('Template with item key'),
	'S_TEMPLATE_WITH_APPLICATION'=>			_('Template with application'),
	'S_ALREADY_LINKED_TO_HOST_SMALL'=>		_('already linked to host'),
	'S_HOST_HAS_BEEN_DELETED_MSG_PART1'=>		_('Host'),
	'S_HOST_HAS_BEEN_DELETED_MSG_PART2'=>		_('has been deleted from the system'),
	'S_AND_CANT_BE_DELETED' => _('and can\'t be deleted'),

//	triggers.inc.php
	'S_NO_TRIGGER_WITH'=>					_('No trigger with'),
	'S_EXPRESSION_CANNOT_BE_EMPTY'=>			_('Expression cannot be empty'),
	'S_INCORRECT_VALUE_TYPE'=>				_('Incorrect value type'),
	'S_FOR_FUNCTION_SMALL'=>				_('for function'),
	'S_AVAILABLE_ONLY_FOR_ITEMS_WITH_VALUE_TYPES_SMALL'=>	_('available only for items with value types'),
	'S_MISSING_MANDATORY_PARAMETER_FOR_FUNCTION'=>		_('Missing mandatory parameter for function'),
	'S_NOT_FLOAT_OR_MACRO_FOR_FUNCTION_SMALL'=> _('is not a float or macro for function'),
	'S_NOT_FLOAT_OR_MACRO_OR_COUNTER_FOR_FUNCTION_SMALL'=>	_('is not a float or counter or macro for function'),
	'S_INCORRECT_TRIGGER_EXPRESSION'=>			_('Incorrect trigger expression'),
	'S_YOU_CAN_NOT_USE_TEMPLATE_HOSTS_MIXED_EXPR'=>		_('You can not use template hosts in mixed expressions.'),
	'S_INCORRECT_FUNCTION_IS_USED'=>			_('Incorrect function is used'),
	'S_0_OR_1'=>						_('0 or 1'),
	'S_TRIGGER_EXPRESSION_HOST_DOES_NOT_EXISTS_ERROR'=>	_('At least one item must be present in the trigger expression.'),
	'S_EXPRESSION_HOST_DOES_NOT_EXISTS_ERROR'=>		_('Host does not exist.'),
	'S_EXPRESSION_HOST_KEY_DOES_NOT_ERROR'=>		_('Host key does not exist.'),
	'S_EXPRESSION_FUNCTION_DOES_NOT_ACCEPTS_PARAMS_ERROR_PART1'=>	_('Function \''),
	'S_EXPRESSION_FUNCTION_DOES_NOT_ACCEPTS_PARAMS_ERROR_PART2'=>	_('\' does not accept parameters.'),

//	maps.inc.php
	'S_SUBMAP'=>			_('Submap'),

//	screens.inc.php
	'S_NO_ROWS_IN_SCREEN'=>				_('No rows in screen'),
	'S_DELETE_IT_Q'=>				_('Delete it?'),
	'S_THIS_SCREEN_ROW_NOT_EMPTY'=>			_('This screen-row is not empty'),
	'S_THIS_SCREEN_COLUMN_NOT_EMPTY'=>		_('This screen-column is not empty'),
	'S_SCREENS_PLAIN_TEXT_DATE_FORMAT'=>		_('d M Y H:i:s'),
	'S_SCREENS_TRIGGER_FORM_DATE_FORMAT'=>		_('[H:i:s]'),

//	graphs.inc.php
	'S_NO_GRAPH_WITH'=>					_('No graph item with'),
	'S_MISSING_ITEMS_FOR_GRAPH'=>				_('Missing items for graph'),
	'S_GRAPH_TEMPLATE_HOST_CANNOT_OTHER_ITEMS_HOSTS_SMALL'=>_('with template host cannot contain items from other hosts.'),

//	profiles.inc.php
	'S_UNABLE_TO_SELECT_CONFIGURATION'=>	_('Unable to select configuration'),

//	perm.inc.php
	'S_INCORRECT_USER_DATA_IN'=>		_('Incorrect user data in'),

//	images.inc.php
	'S_COULD_NOT_SAVE_IMAGE'=>		_('Could not save image!'),
	'S_EXECUTE_SQL_ERROR'=>			_('Execute SQL error'),
	'S_PARSE_SQL_ERROR'=>			_('Parse SQL error'),

//	nodes.inc.php
	'S_INCORRECT_CHARACTERS_USED_FOR_NODE_NAME'=>		_('Incorrect characters used for Node name'),
	'S_MASTER_NODE_ALREADY_EXISTS'=>			_('Master node already exists'),
	'S_INCORRECT_NODE_TYPE'=>				_('Incorrect node type'),
	'S_NODE_WITH_SAME_ID_ALREADY_EXISTS'=>			_('Node with same ID already exists'),
	'S_UNABLE_TO_REMOVE_LOCAL_NODE'=>			_('Unable to remove local node'),
	'S_DATABASE_STILL_CONTAINS_DATA_RELATED_DELETED_NODE'=>	_('Please be aware that database still contains data related to the deleted Node'),

//	class.cuser.php
	'S_CUSER_ERROR_ONLY_ADMIN_CAN_ADD_USER_MEDIAS'=>	_('Only Zabbix Admins can add user Medias'),
	'S_CUSER_ERROR_ONLY_ADMIN_CAN_REMOVE_USER_MEDIAS'=>	_('Only Zabbix Admins can remove user Medias'),
	'S_CUSER_ERROR_ONLY_ADMIN_CAN_CHANGE_USER_MEDIAS'=>	_('Only Zabbix Admins can change user Medias'),
	'S_CUSER_ERROR_CANT_DELETE_USER_MEDIAS'=>		_('Can\'t delete user medias'),
	'S_CUSER_ERROR_CANT_UPDATE_USER_MEDIAS'=>		_('Can\'t update user medias'),
	'S_CUSER_ERROR_CANT_INSERT_USER_MEDIAS'=>		_('Can\'t insert user medias'),
	'S_CUSER_ERROR_CANT_RENAME_GUEST_USER'=>		_('Cannot rename guest user'),
	'S_CUSER_ERROR_INCORRECT_TIME_PERIOD'=>			_('Incorrect time period'),
	'S_CUSER_ERROR_WRONG_PERIOD_PART1'=>			_('Wrong period ['),
	'S_CUSER_ERROR_WRONG_PERIOD_PART2'=>			_(']'),

// class.cmediatype.php
	'S_CMEDIATYPE_ERROR_WRONG_FIELD_FOR_MEDIATYPE' =>	_('Wrong fields for media type'),
	'S_CMEDIATYPE_ERROR_PASSWORD_REQUIRED' =>	_('Password required for media type'),
	'S_MEDIA_TYPE_ALREADY_EXISTS'=>				_('Media type already exists:'),
	'S_MEDIA_TYPES_USED_BY_ACTIONS'=>	_('Media types used by action:'),
	'S_CMEDIATYPE_ERROR_ONLY_SUPER_ADMIN_CAN_CREATE_MEDIATYPES'=>_('Only Super Admins can create media types'),
	'S_CMEDIATYPE_ERROR_ONLY_SUPER_ADMIN_CAN_DELETE_MEDIATYPES'=>_('Only Super Admins can delete media types'),

//	Menu

	'S_QUEUE'=>				_('Queue'),
	'S_EVENTS'=>				_('Events'),
	'S_MAPS'=>				_('Maps'),
	'S_REPORT'=>				_('Report'),
	'S_REPORTS'=>				_('Reports'),
	'S_LOGOUT'=>				_('Logout'),
	'S_LATEST_DATA'=>			_('Latest data'),

//	Errors
	'S_DOES_NOT_EXIST_SMALL'=>		_('does not exist'),
	'S_NO_PERMISSION'=>				_('You do not have permission to perform this operation'),
	'S_NO_PERMISSIONS_FOR_SCREEN'=>	_('No permissions for screen'),
	'S_NO_PERMISSIONS_FOR_MAP'=>	_('No permissions for map'),
	'S_XML_FILE_CONTAINS_ERRORS'=>	_('XML file contains errors'),

//	class.calendar.js
	'S_MONDAY_SHORT_BIG'=>		_('M'),
	'S_TUESDAY_SHORT_BIG'=>		_('T'),
	'S_WEDNESDAY_SHORT_BIG'=>	_('W'),
	'S_THURSDAY_SHORT_BIG'=>	_('T'),
	'S_FRIDAY_SHORT_BIG'=>		_('F'),
	'S_SATURDAY_SHORT_BIG'=>	_('S'),
	'S_SUNDAY_SHORT_BIG'=>		_('S'),
	'S_NOW'=>	_('Now'),
	'S_DONE'=>	_('Done'),

//	gtlc.js
	'S_ZOOM'=>			_('Zoom'),
	'S_FIXED_SMALL'=>		_('fixed'),
	'S_DYNAMIC_SMALL'=>		_('dynamic'),
	'S_NOW_SMALL'=>			_('now'),

//	functions.js
	'S_CREATE_LOG_TRIGGER'=>			_('Create trigger'),
	'DO_YOU_REPLACE_CONDITIONAL_EXPRESSION_Q'=>	_('Do you wish to replace the conditional expression?'),
	'S_ADD_SERVICE'=>				_('Add service'),
	'S_EDIT_SERVICE'=>				_('Edit service'),
	'S_DELETE_SERVICE'=>				_('Delete service'),
	'S_DELETE_SELECTED_SERVICES_Q'=>		_('Delete selected services?'),

// class.cookie.js
	'S_MAX_COOKIE_SIZE_REACHED'=>		_('We are sorry, the maximum possible number of elements to remember has been reached.'),

	'S_ICONMAP_IS_NOT_ENABLED' => _('Iconmap is not enabled'),
);
?>
