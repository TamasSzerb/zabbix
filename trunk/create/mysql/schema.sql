-- 
-- ZABBIX
-- Copyright (C) 2000-2005 SIA Zabbix
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
--

--
-- Table structure for table 'nodes'
--

CREATE TABLE nodes (
	nodeid			bigint		DEFAULT '0' NOT NULL,
	name			varchar(64)	DEFAULT '' NOT NULL,
	timezone		integer		DEFAULT '0' NOT NULL,
	ip			varchar(15)	DEFAULT '' NOT NULL,
	port			integer		DEFAULT '0' NOT NULL,
	slave_history		integer		DEFAULT '0' NOT NULL,
	slave_trends		integer		DEFAULT '0' NOT NULL,
	nodetype		integer		DEFAULT '0' NOT NULL,
	masterid		bigint		DEFAULT '0' NOT NULL,
	PRIMARY KEY (nodeid)
) type=InnoDB;

CREATE TABLE node_cksum (
	cksumid			bigint		DEFAULT '0' NOT NULL,
	nodeid			bigint		DEFAULT '0' NOT NULL,
	tablename		varchar(64)	DEFAULT '' NOT NULL,
	fieldname		varchar(64)	DEFAULT '' NOT NULL,
	recordid		bigint		DEFAULT '0' NOT NULL,
	cksumtype		integer		DEFAULT '0' NOT NULL,
	cksum			char(32)	DEFAULT '' NOT NULL,
	PRIMARY KEY (cksumid)
) type=InnoDB;

CREATE INDEX node_cksum_1 ON node_cksum (nodeid,tablename,fieldname,recordid,cksumtype);

CREATE TABLE node_configlog (
	conflogid		bigint		DEFAULT '0' NOT NULL,
	nodeid			bigint		DEFAULT '0' NOT NULL,
	tablename		varchar(64)	DEFAULT '' NOT NULL,
	recordid		bigint		DEFAULT '0' NOT NULL,
	operation		integer	DEFAULT '0' NOT NULL,
	sync_master		integer		DEFAULT '0' NOT NULL,
	sync_slave		integer		DEFAULT '0' NOT NULL,
	PRIMARY KEY (nodeid,conflogid)
) type=InnoDB;

CREATE INDEX node_configlog_1 ON node_configlog (conflogid);
CREATE INDEX node_configlog_2 ON node_configlog (nodeid,tablename);

CREATE TABLE services (
	serviceid		bigint		DEFAULT '0' NOT NULL,
	name			varchar(128)	DEFAULT '' NOT NULL,
	status			integer		DEFAULT '0' NOT NULL,
	algorithm		integer		DEFAULT '0' NOT NULL,
	triggerid		bigint,
	showsla			integer		DEFAULT '0' NOT NULL,
	goodsla			double(5,2)	DEFAULT '99.9' NOT NULL,
	sortorder		integer		DEFAULT '0' NOT NULL,
	PRIMARY KEY (serviceid)
) type=InnoDB;

CREATE TABLE services_times (
	timeid		bigint		DEFAULT '0' NOT NULL,
	serviceid	bigint		DEFAULT '0' NOT NULL,
	type		integer		DEFAULT '0' NOT NULL,
	ts_from		integer		DEFAULT '0' NOT NULL,
	ts_to		integer		DEFAULT '0' NOT NULL,
	note		varchar(255)	DEFAULT '' NOT NULL,
	PRIMARY KEY (timeid)
) type=InnoDB;

CREATE INDEX services_times_1 ON services_times (serviceid,type,ts_from,ts_to);

CREATE TABLE services_links (
	linkid			bigint		DEFAULT '0' NOT NULL,
	serviceupid		bigint		DEFAULT '0' NOT NULL,
	servicedownid		bigint		DEFAULT '0' NOT NULL,
	soft			integer		DEFAULT '0' NOT NULL,
	PRIMARY KEY (linkid)
) type=InnoDB;

CREATE INDEX services_links_1 ON services_links (servicedownid);
CREATE INDEX services_links_2 ON services_links (serviceupid,servicedownid);

CREATE TABLE graphs_items (
	gitemid		bigint		DEFAULT '0' NOT NULL,
	graphid		bigint		DEFAULT '0' NOT NULL,
	itemid		bigint		DEFAULT '0' NOT NULL,
	drawtype	integer		DEFAULT '0' NOT NULL,
	sortorder	integer		DEFAULT '0' NOT NULL,
	color		varchar(32)	DEFAULT 'Dark Green' NOT NULL,
	yaxisside	integer		DEFAULT '1' NOT NULL,
	calc_fnc	integer		DEFAULT '2' NOT NULL,
	type		integer		DEFAULT '0' NOT NULL,
	periods_cnt	integer		DEFAULT '5' NOT NULL,
	PRIMARY KEY (gitemid)
) type=InnoDB;

CREATE TABLE graphs (
	graphid			bigint		DEFAULT '0' NOT NULL,
	name			varchar(128)	DEFAULT '' NOT NULL,
	width			integer		DEFAULT '0' NOT NULL,
	height			integer		DEFAULT '0' NOT NULL,
	yaxistype		integer		DEFAULT '0' NOT NULL,
	yaxismin		double(16,4)	DEFAULT '0' NOT NULL,
	yaxismax		double(16,4)	DEFAULT '0' NOT NULL,
	templateid		bigint		DEFAULT '0' NOT NULL,
	show_work_period	integer		DEFAULT '1' NOT NULL,
	show_triggers		integer		DEFAULT '1' NOT NULL,
	graphtype		integer		DEFAULT '0' NOT NULL,
	PRIMARY KEY (graphid)
) type=InnoDB;

CREATE INDEX graphs_1 ON graphs (name);

CREATE TABLE sysmaps_links (
	linkid			bigint		DEFAULT '0' NOT NULL,
	sysmapid		bigint		DEFAULT '0' NOT NULL,
	selementid1		bigint		DEFAULT '0' NOT NULL,
	selementid2		bigint		DEFAULT '0' NOT NULL,
	triggerid		bigint,
	drawtype_off		integer		DEFAULT '0' NOT NULL,
	color_off		varchar(32)	DEFAULT 'Black' NOT NULL,
	drawtype_on		integer		DEFAULT '0' NOT NULL,
	color_on		varchar(32)	DEFAULT 'Red' NOT NULL,
	PRIMARY KEY (linkid)
) type=InnoDB;

CREATE TABLE sysmaps_elements (
	selementid		bigint		DEFAULT '0' NOT NULL,
	sysmapid		bigint		DEFAULT '0' NOT NULL,
	elementid		bigint		DEFAULT '0' NOT NULL,
	elementtype		integer		DEFAULT '0' NOT NULL,
	icon			varchar(32)	DEFAULT 'Server' NOT NULL,
	icon_on			varchar(32)	DEFAULT 'Server' NOT NULL,
	label			varchar(128)	DEFAULT '' NOT NULL,
	label_location		integer		DEFAULT NULL,
	x			integer		DEFAULT '0' NOT NULL,
	y			integer		DEFAULT '0' NOT NULL,
	url			varchar(255)	DEFAULT '' NOT NULL,
	PRIMARY KEY (selementid)
) type=InnoDB;

CREATE TABLE sysmaps (
	sysmapid		bigint		DEFAULT '0' NOT NULL,
	name			varchar(128)	DEFAULT '' NOT NULL,
	width			integer		DEFAULT '0' NOT NULL,
	height			integer		DEFAULT '0' NOT NULL,
	background		varchar(64)	DEFAULT '' NOT NULL,
	label_type		integer		DEFAULT '0' NOT NULL,
	label_location		integer		DEFAULT '0' NOT NULL,
	PRIMARY KEY (sysmapid)
) type=InnoDB;

CREATE INDEX sysmaps_1 ON sysmaps (name);

CREATE TABLE config (
	configid		bigint		DEFAULT '0' NOT NULL,
	alert_history		integer		DEFAULT '0' NOT NULL,
	alarm_history		integer		DEFAULT '0' NOT NULL,
	refresh_unsupported	integer		DEFAULT '0' NOT NULL,
	work_period		varchar(100)	DEFAULT '1-5,00:00-24:00' NOT NULL,
	PRIMARY KEY (configid)
) type=InnoDB;

CREATE TABLE groups (
	groupid			bigint		DEFAULT '0' NOT NULL,
	name			varchar(64)	DEFAULT '' NOT NULL,
	PRIMARY KEY (groupid)
) type=InnoDB;

CREATE INDEX groups_1 ON groups (name);

CREATE TABLE hosts_groups (
	hostgroupid		bigint		DEFAULT '0' NOT NULL,
	hostid			bigint		DEFAULT '0' NOT NULL,
	groupid			bigint		DEFAULT '0' NOT NULL,
	PRIMARY KEY (hostgroupid)
) type=InnoDB;

CREATE INDEX hosts_groups_1 ON hosts_groups (hostid,groupid);

CREATE TABLE alerts (
	alertid			bigint		DEFAULT '0' NOT NULL,
	actionid		bigint		DEFAULT '0' NOT NULL,
	triggerid		bigint		DEFAULT '0' NOT NULL,
	userid			bigint		DEFAULT '0' NOT NULL,
	clock			integer		DEFAULT '0' NOT NULL,
	mediatypeid		bigint		DEFAULT '0' NOT NULL,
	sendto			varchar(100)	DEFAULT '' NOT NULL,
	subject			varchar(255)	DEFAULT '' NOT NULL,
	message			blob		DEFAULT '' NOT NULL,
	status			integer		DEFAULT '0' NOT NULL,
	retries			integer		DEFAULT '0' NOT NULL,
	error			varchar(128)	DEFAULT '' NOT NULL,
	repeats			integer		DEFAULT '0' NOT NULL,
	maxrepeats		integer		DEFAULT '0' NOT NULL,
	nextcheck		integer		DEFAULT '0' NOT NULL,
	delay			integer		DEFAULT '0' NOT NULL,

	PRIMARY KEY (alertid)
) type=InnoDB;

CREATE INDEX alerts_1 ON alerts (actionid);
CREATE INDEX alerts_2 ON alerts (clock);
CREATE INDEX alerts_3 ON alerts (triggerid);
CREATE INDEX alerts_4 ON alerts (status, retries);
CREATE INDEX alerts_5 ON alerts (mediatypeid);
CREATE INDEX alerts_6 ON alerts (userid);

CREATE TABLE actions (
	actionid		bigint		DEFAULT '0' NOT NULL,
	userid			bigint		DEFAULT '0' NOT NULL,
	subject			varchar(255)	DEFAULT '' NOT NULL,
	message			blob		DEFAULT '' NOT NULL,
	recipient		integer		DEFAULT '0' NOT NULL,
	maxrepeats		integer		DEFAULT '0' NOT NULL,
	repeatdelay		integer		DEFAULT '600' NOT NULL,
	source			integer		DEFAULT '0' NOT NULL,
	actiontype		integer		DEFAULT '0' NOT NULL,
	status			integer		DEFAULT '0' NOT NULL,
	scripts			blob		DEFAULT '' NOT NULL,
	PRIMARY KEY (actionid)
) type=InnoDB;

CREATE TABLE conditions (
	conditionid		bigint		DEFAULT '0' NOT NULL,
	actionid		bigint		DEFAULT '0' NOT NULL,
	conditiontype		integer		DEFAULT '0' NOT NULL,
	operator		integer		DEFAULT '0' NOT NULL,
	value			varchar(255)	DEFAULT '' NOT NULL,
	PRIMARY KEY (conditionid)
) type=InnoDB;

CREATE INDEX conditions_1 ON conditions (actionid);

CREATE TABLE alarms (
	alarmid			bigint		DEFAULT '0' NOT NULL,
	triggerid		bigint		DEFAULT '0' NOT NULL,
	clock			integer		DEFAULT '0' NOT NULL,
	value			integer		DEFAULT '0' NOT NULL,
	acknowledged		integer		DEFAULT '0' NOT NULL,
	PRIMARY KEY (alarmid)
) type=InnoDB;

CREATE INDEX alarms_1 ON alarms (triggerid,clock);
CREATE INDEX alarms_2 ON alarms (clock);

CREATE TABLE functions (
	functionid		bigint		DEFAULT '0' NOT NULL,
	itemid			bigint		DEFAULT '0' NOT NULL,
	triggerid		bigint		DEFAULT '0' NOT NULL,
	lastvalue		varchar(255),
	function		varchar(12)	DEFAULT '' NOT NULL,
	parameter		varchar(255)	DEFAULT '0' NOT NULL,
	PRIMARY KEY (functionid)
) type=InnoDB;

CREATE INDEX functions_1 ON functions (triggerid);
CREATE INDEX functions_2 ON functions (itemid,function,parameter);

CREATE TABLE history (
	itemid			bigint		DEFAULT '0' NOT NULL,
	clock			integer		DEFAULT '0' NOT NULL,
	value			double(16,4)	DEFAULT '0.0000' NOT NULL
) type=InnoDB;

CREATE INDEX history_1 ON history (itemid, clock);

CREATE TABLE history_uint (
	itemid			bigint		DEFAULT '0' NOT NULL,
	clock			integer		DEFAULT '0' NOT NULL,
	value			bigint unsigned	DEFAULT '0' NOT NULL
) type=InnoDB;

CREATE INDEX history_uint_1 ON history_uint (itemid, clock);

CREATE TABLE history_str (
	itemid			bigint		DEFAULT '0' NOT NULL,
	clock			integer		DEFAULT '0' NOT NULL,
	value			varchar(255)	DEFAULT '' NOT NULL
) type=InnoDB;

CREATE INDEX history_str_1 ON history_str (itemid, clock);

CREATE TABLE hosts (
	hostid			bigint			DEFAULT '0' NOT NULL,
	host			varchar(64)		DEFAULT '' NOT NULL,
	useip			integer			DEFAULT '1' NOT NULL,
	ip			varchar(15)		DEFAULT '127.0.0.1' NOT NULL,
	port			integer			DEFAULT '0' NOT NULL,
	status			integer			DEFAULT '0' NOT NULL,
	disable_until		integer			DEFAULT '0' NOT NULL,
	error			varchar(128)		DEFAULT '' NOT NULL,
	available		integer			DEFAULT '0' NOT NULL,
	errors_from		integer			DEFAULT '0' NOT NULL,
	templateid		bigint			DEFAULT '0' NOT NULL,
	PRIMARY KEY	(hostid),
	KEY		(host),
	KEY		(status)
) type=InnoDB;

CREATE INDEX hosts_1 ON hosts (host);
CREATE INDEX hosts_2 ON hosts (status);

CREATE TABLE items (
	itemid			bigint		DEFAULT '0' NOT NULL,
	type			integer		DEFAULT '0' NOT NULL,
	snmp_community		varchar(64)	DEFAULT '' NOT NULL,
	snmp_oid		varchar(255)	DEFAULT '' NOT NULL,
	snmp_port		integer		DEFAULT '161' NOT NULL,
	hostid			bigint	 	DEFAULT '0' NOT NULL,
	description		varchar(255)	DEFAULT '' NOT NULL,
	key_			varchar(64)	DEFAULT '' NOT NULL,
	delay			integer 	DEFAULT '0' NOT NULL,
	history			integer 	DEFAULT '90' NOT NULL,
	trends			integer		DEFAULT '365' NOT NULL,
	nextcheck		integer		DEFAULT '0' NOT NULL,
	lastvalue		varchar(255)	DEFAULT NULL,
	lastclock		integer		DEFAULT NULL,
	prevvalue		varchar(255)	DEFAULT NULL,
	status			integer		DEFAULT '0' NOT NULL,
	value_type		integer		DEFAULT '0' NOT NULL,
	trapper_hosts		varchar(255)	DEFAULT '' NOT NULL,
	units			varchar(10)	DEFAULT '' NOT NULL,
	multiplier		integer		DEFAULT '0' NOT NULL,
	delta			integer		DEFAULT '0' NOT NULL,
	prevorgvalue		double(16,4)	DEFAULT NULL,
	snmpv3_securityname	varchar(64)	DEFAULT '' NOT NULL,
	snmpv3_securitylevel	integer		DEFAULT '0' NOT NULL,
	snmpv3_authpassphrase	varchar(64)	DEFAULT '' NOT NULL,
	snmpv3_privpassphrase	varchar(64)	DEFAULT '' NOT NULL,

	formula			varchar(255)	DEFAULT '0' NOT NULL,
	error			varchar(128)	DEFAULT '' NOT NULL,

	lastlogsize		integer		DEFAULT '0' NOT NULL,
	logtimefmt		varchar(64)	DEFAULT '' NOT NULL,
	templateid		integer		DEFAULT '0' NOT NULL,
	valuemapid		integer		DEFAULT '0' NOT NULL,
	delay_flex		varchar(255)	DEFAULT '' NOT NULL,

	PRIMARY KEY	(itemid)
) type=InnoDB;

CREATE INDEX items_1 ON items (hostid,key_);
CREATE INDEX items_2 ON items (nextcheck);
CREATE INDEX items_3 ON items (status);

CREATE TABLE media (
	mediaid			bigint		DEFAULT '0' NOT NULL,
	userid			bigint		DEFAULT '0' NOT NULL,
	mediatypeid		bigint		DEFAULT '0' NOT NULL,
	sendto			varchar(100)	DEFAULT '' NOT NULL,
	active			integer		DEFAULT '0' NOT NULL,
	severity		integer		DEFAULT '63' NOT NULL,
	period			varchar(100)	DEFAULT '1-7,00:00-23:59' NOT NULL,
	PRIMARY KEY	(mediaid)
) type=InnoDB;

CREATE INDEX media_1 ON media (userid);
CREATE INDEX media_2 ON media (mediatypeid);

CREATE TABLE media_type (
	mediatypeid		bigint		DEFAULT '0' NOT NULL,
	type			integer		DEFAULT '0' NOT NULL,
	description		varchar(100)	DEFAULT '' NOT NULL,
	smtp_server		varchar(255)	DEFAULT '' NOT NULL,
	smtp_helo		varchar(255)	DEFAULT '' NOT NULL,
	smtp_email		varchar(255)	DEFAULT '' NOT NULL,
	exec_path		varchar(255)	DEFAULT '' NOT NULL,
	gsm_modem		varchar(255)	DEFAULT '' NOT NULL,
	PRIMARY KEY	(mediatypeid)
) type=InnoDB;

CREATE TABLE triggers (
	triggerid		bigint		DEFAULT '0' NOT NULL,
	expression		varchar(255)	DEFAULT '' NOT NULL,
	description		varchar(255)	DEFAULT '' NOT NULL,
	url			varchar(255)	DEFAULT '' NOT NULL,
	status			integer		DEFAULT '0' NOT NULL,
	value			integer		DEFAULT '0' NOT NULL,
	priority		integer		DEFAULT '0' NOT NULL,
	lastchange		integer		DEFAULT '0' NOT NULL,
	dep_level		integer		DEFAULT '0' NOT NULL,
	comments		blob,
	error			varchar(128)	DEFAULT '' NOT NULL,
	templateid		bigint		DEFAULT '0' NOT NULL,
	PRIMARY KEY	(triggerid)
) type=InnoDB;

CREATE INDEX triggers_1 ON triggers (status);
CREATE INDEX triggers_2 ON triggers (value);

CREATE TABLE trigger_depends (
	triggerdepid		bigint		DEFAULT '0' NOT NULL,
	triggerid_down		bigint		DEFAULT '0' NOT NULL,
	triggerid_up		bigint		DEFAULT '0' NOT NULL,
	PRIMARY KEY	(triggerdepid)
) type=InnoDB;

CREATE INDEX trigger_depends_1 ON trigger_depends (triggerid_down, triggerid_up);
CREATE INDEX trigger_depends_2 ON trigger_depends (triggerid_up);

CREATE TABLE users (
	userid			bigint		DEFAULT '0' NOT NULL,
	alias			varchar(100)	DEFAULT '' NOT NULL,
	name			varchar(100)	DEFAULT '' NOT NULL,
	surname			varchar(100)	DEFAULT '' NOT NULL,
	passwd			char(32)	DEFAULT '' NOT NULL,
	url			varchar(255)	DEFAULT '' NOT NULL,
	autologout		integer		DEFAULT '900' NOT NULL,
	lang			varchar(5)	DEFAULT 'en_gb' NOT NULL,
	refresh			integer		DEFAULT '30' NOT NULL,
	PRIMARY KEY (userid)
) type=InnoDB;

CREATE INDEX users_1 ON users (alias);

CREATE TABLE auditlog (
	auditid			bigint		DEFAULT '0' NOT NULL,
	userid			bigint		DEFAULT '0' NOT NULL,
	clock			integer		DEFAULT '0' NOT NULL,
	action			integer		DEFAULT '0' NOT NULL,
	resourcetype		integer		DEFAULT '0' NOT NULL,
	details			varchar(128)	DEFAULT '0' NOT NULL,
	PRIMARY KEY (auditid)
) type=InnoDB;

CREATE INDEX auditlog_1 ON auditlog (userid,clock);
CREATE INDEX auditlog_2 ON auditlog (clock);

CREATE TABLE sessions (
	sessionid		varchar(32)	NOT NULL DEFAULT '',
	userid			bigint		NOT NULL DEFAULT '0',
	lastaccess		integer		NOT NULL DEFAULT '0',
	PRIMARY KEY (sessionid)
) type=InnoDB;

CREATE TABLE rights (
	rightid			bigint		DEFAULT '0' NOT NULL,
	userid			bigint		DEFAULT '0' NOT NULL,
	name			char(255)	DEFAULT '' NOT NULL,
	permission		char(1)		DEFAULT '' NOT NULL,
	id			bigint,
	PRIMARY KEY (rightid)
) type=InnoDB;

CREATE INDEX rights_1 ON rights (userid);

CREATE TABLE service_alarms (
	servicealarmid		bigint		DEFAULT '0' NOT NULL,
	serviceid		bigint		DEFAULT '0' NOT NULL,
	clock			integer		DEFAULT '0' NOT NULL,
	value			integer		DEFAULT '0' NOT NULL,
	PRIMARY KEY (servicealarmid)
) type=InnoDB;

CREATE INDEX service_alarms_1 ON service_alarms (serviceid,clock);
CREATE INDEX service_alarms_2 ON service_alarms (clock);

CREATE TABLE profiles (
	profileid		bigint		DEFAULT '0' NOT NULL,
	userid			bigint		DEFAULT '0' NOT NULL,
	idx			varchar(64)	DEFAULT '' NOT NULL,
	value			varchar(255)	DEFAULT '' NOT NULL,
	valuetype		integer		DEFAULT 0 NOT NULL,
	PRIMARY KEY (profileid)
) type=InnoDB;

CREATE INDEX profiles_1 ON profiles (userid,idx);

CREATE TABLE screens (
	screenid		bigint		DEFAULT '0' NOT NULL,
	name			varchar(255)	DEFAULT 'Screen' NOT NULL,
	hsize			integer		DEFAULT '1' NOT NULL,
	vsize			integer		DEFAULT '1' NOT NULL,
	PRIMARY KEY  (screenid)
) TYPE=InnoDB;

CREATE TABLE screens_items (
	screenitemid	bigint		DEFAULT '0' NOT NULL,
	screenid	bigint		DEFAULT '0' NOT NULL,
	resourcetype	integer		DEFAULT '0' NOT NULL,
	resourceid	bigint		DEFAULT '0' NOT NULL,
	width		integer		DEFAULT '320' NOT NULL,
	height		integer		DEFAULT '200' NOT NULL,
	x		integer		DEFAULT '0' NOT NULL,
	y		integer		DEFAULT '0' NOT NULL,
	colspan		integer		DEFAULT '0' NOT NULL,
	rowspan		integer		DEFAULT '0' NOT NULL,
	elements	integer		DEFAULT '25' NOT NULL,
	valign		integer		DEFAULT '0' NOT NULL,
	halign		integer		DEFAULT '0' NOT NULL,
	style		integer		DEFAULT '0' NOT NULL,
	url		varchar(255)	DEFAULT '' NOT NULL,
	PRIMARY KEY  (screenitemid)
) TYPE=InnoDB;

CREATE TABLE usrgrp (
	usrgrpid		bigint		DEFAULT '0' NOT NULL,
	name			varchar(64)	DEFAULT '' NOT NULL,
	PRIMARY KEY (usrgrpid)
) type=InnoDB;

CREATE INDEX usrgrp_1 ON usrgrp (name);

CREATE TABLE users_groups (
	id			bigint		DEFAULT '0' NOT NULL,
	usrgrpid		bigint		DEFAULT '0' NOT NULL,
	userid			bigint		DEFAULT '0' NOT NULL,
	PRIMARY KEY (id)
) type=InnoDB;

CREATE INDEX users_groups_1 ON users_groups (usrgrpid,userid);

CREATE TABLE trends (
	itemid			bigint		DEFAULT '0' NOT NULL,
	clock			integer		DEFAULT '0' NOT NULL,
	num			integer		DEFAULT '0' NOT NULL,
	value_min		double(16,4)	DEFAULT '0.0000' NOT NULL,
	value_avg		double(16,4)	DEFAULT '0.0000' NOT NULL,
	value_max		double(16,4)	DEFAULT '0.0000' NOT NULL,
	PRIMARY KEY (itemid,clock)
) type=InnoDB;

CREATE TABLE images (
	imageid			bigint		DEFAULT '0' NOT NULL,
	imagetype		integer		DEFAULT '0' NOT NULL,
	name			varchar(64)	DEFAULT '0' NOT NULL,
	image			longblob	DEFAULT '' NOT NULL,
	PRIMARY KEY (imageid)
) type=InnoDB;

CREATE INDEX images_1 ON images (imagetype, name);

CREATE TABLE hosts_templates (
	hosttemplateid		bigint		DEFAULT '0' NOT NULL,
	hostid			bigint		DEFAULT '0' NOT NULL,
	templateid		bigint		DEFAULT '0' NOT NULL,
	items			integer		DEFAULT '0' NOT NULL,
	triggers		integer		DEFAULT '0' NOT NULL,
	graphs			integer		DEFAULT '0' NOT NULL,
	PRIMARY KEY (hosttemplateid)
) type=InnoDB;

CREATE INDEX hosts_templates_1 ON hosts_templates (hostid, templateid);

CREATE TABLE history_log (
	id			bigint		DEFAULT '0' NOT NULL,
	itemid			bigint		DEFAULT '0' NOT NULL,
	clock			integer		DEFAULT '0' NOT NULL,
	timestamp		integer		DEFAULT '0' NOT NULL,
	source			varchar(64)	DEFAULT '' NOT NULL,
	severity		integer		DEFAULT '0' NOT NULL,
	value			text		DEFAULT '' NOT NULL,
	PRIMARY KEY (id)
) type=InnoDB;

CREATE INDEX history_log_1 ON history_log (itemid, clock);

CREATE TABLE history_text (
	id			bigint		DEFAULT '0' NOT NULL,
	itemid			bigint		DEFAULT '0' NOT NULL,
	clock			integer		DEFAULT '0' NOT NULL,
	value			text		DEFAULT '' NOT NULL,
	PRIMARY KEY (id)
) type=InnoDB;

CREATE INDEX history_text_1 ON history_text (itemid, clock);

CREATE TABLE hosts_profiles (
	hostid			bigint		DEFAULT '0' NOT NULL,
	devicetype		varchar(64)	DEFAULT '' NOT NULL,
	name			varchar(64)	DEFAULT '' NOT NULL,
	os			varchar(64)	DEFAULT '' NOT NULL,
	serialno		varchar(64)	DEFAULT '' NOT NULL,
	tag			varchar(64)	DEFAULT '' NOT NULL,
	macaddress		varchar(64)	DEFAULT '' NOT NULL,
	hardware		blob		DEFAULT '' NOT NULL,
	software		blob		DEFAULT '' NOT NULL,
	contact			blob		DEFAULT '' NOT NULL,
	location		blob		DEFAULT '' NOT NULL,
	notes			blob		DEFAULT '' NOT NULL,
	PRIMARY KEY (hostid)
) type=InnoDB;

CREATE TABLE autoreg (
	id		bigint		DEFAULT '0' NOT NULL,
	priority	integer		DEFAULT '0' NOT NULL,
	pattern		varchar(255)	DEFAULT '' NOT NULL,
	hostid		bigint		DEFAULT '0' NOT NULL,
	PRIMARY KEY (id)
) type=InnoDB;

CREATE TABLE valuemaps (
	valuemapid		bigint		DEFAULT '0' NOT NULL,
	name			varchar(64)	DEFAULT '' NOT NULL,
	PRIMARY KEY (valuemapid)
) type=InnoDB;

CREATE INDEX valuemaps_1 ON valuemaps (name);

CREATE TABLE mappings (
	mappingid		bigint		DEFAULT '0' NOT NULL,
	valuemapid		bigint		DEFAULT '0' NOT NULL,
	value			varchar(64)	DEFAULT '' NOT NULL,
	newvalue		varchar(64)	DEFAULT '' NOT NULL,
	PRIMARY KEY (mappingid)
) type=InnoDB;

CREATE INDEX mappings_1 ON mappings (valuemapid);

CREATE TABLE housekeeper (
	housekeeperid		bigint		DEFAULT '0' NOT NULL,
	tablename		varchar(64)	DEFAULT '' NOT NULL,
	field			varchar(64)	DEFAULT '' NOT NULL,
	value			integer		DEFAULT '0' NOT NULL,
	PRIMARY KEY (housekeeperid)
) type=InnoDB;

CREATE TABLE acknowledges (
	acknowledgeid		bigint		DEFAULT '0' NOT NULL,
	userid			bigint		DEFAULT '0' NOT NULL,
	alarmid			bigint		DEFAULT '0' NOT NULL,
	clock			integer		DEFAULT '0' NOT NULL,
	message			varchar(255)	DEFAULT '' NOT NULL,
	PRIMARY KEY (acknowledgeid)
) type=InnoDB;

CREATE INDEX acknowledges_1 ON acknowledges (userid);
CREATE INDEX acknowledges_2 ON acknowledges (alarmid);
CREATE INDEX acknowledges_3 ON acknowledges (clock);

CREATE TABLE applications (
	applicationid		bigint		DEFAULT '0' NOT NULL,
	hostid			bigint		DEFAULT '0' NOT NULL,
	name			varchar(255)	DEFAULT '' NOT NULL,
	templateid		bigint		DEFAULT '0' NOT NULL,
	PRIMARY KEY 	(applicationid)
) type=InnoDB;

CREATE INDEX applications_1 ON applications (templateid);
CREATE INDEX applications_2 ON applications (hostid,name);

CREATE TABLE items_applications (
	itemappid		bigint		DEFAULT '0' NOT NULL,
	applicationid		bigint		DEFAULT '0' NOT NULL,
	itemid			bigint		DEFAULT '0' NOT NULL,
	PRIMARY KEY (itemappid)
) type=InnoDB;

CREATE INDEX items_applications_1 ON items_applications (applicationid,itemid);

CREATE TABLE help_items (
	itemtype		integer          DEFAULT '0' NOT NULL,
	key_			varchar(64)	DEFAULT '' NOT NULL,
	description		varchar(255)	DEFAULT '' NOT NULL,
	PRIMARY KEY (itemtype, key_)
) type=InnoDB;
