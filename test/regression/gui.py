#!/usr/bin/python

### ZABBIX
### Copyright (C) 2000-2005 SIA Zabbix
###
### This program is free software; you can redistribute it and/or modify
### it under the terms of the GNU General Public License as published by
### the Free Software Foundation; either version 2 of the License, or
### (at your option) any later version.
###
### This program is distributed in the hope that it will be useful,
### but WITHOUT ANY WARRANTY; without even the implied warranty of
### MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
### GNU General Public License for more details.
###
### You should have received a copy of the GNU General Public License
### along with this program; if not, write to the Free Software
### Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

import re, httplib, sys, time, os, MySQLdb

connection = MySQLdb.connect(host="localhost",
            user="root", passwd="", db="test" )   

def TestGUI(name, page, gets, expect):
	conn = httplib.HTTPConnection("localhost")
	print "\t"+name
	url = '/~test/'+page+'?'+gets
	conn.request("GET", url)
	r1 = conn.getresponse()
	###print page, '\t\t', r1.status, r1.reason
	data = r1.read()
	p = re.compile('.*'+expect+'.*', re.DOTALL)
	m = p.match(data)
	if m:
		print '\t\tGUI: OK'
	else:
		print '\t\tGUI: NOT OK'
	conn.close()

def InitDB():
###	os.system('rm -rf /home/test/zabbix')
###	os.system('cd /home/test; . ./env')
	print "Init DB"
	os.system('echo "drop database test"|mysql -uroot')
	os.system('echo "create database test"|mysql -uroot')
	os.system('cat /home/test/zabbix/create/mysql/schema.sql|mysql -uroot test')
	os.system('cat /home/test/zabbix/create/data/data.sql|mysql -uroot test')
	os.system('echo "update rights set userid=2;"|mysql -uroot test')

def TestDBCount(table, condition, num):
	cursor = connection.cursor()
	cursor.execute("SELECT count(*) FROM "+table+" where "+condition)
	row = cursor.fetchone()

	if row[0]==num:
		print '\t\tDB: OK'
	else:
		print '\t\tDB: NOT OK'

def DBGetID(table, condition, column):
	cursor = connection.cursor()
	cursor.execute("SELECT " + column + " FROM "+table+" where "+condition)
	row = cursor.fetchone()

	return row[0]

def GUI_Login():
	print "GUI_Login"
	TestGUI('Logging in', "index.php", "name=Admin&register=Enter&password=", "disconnect")
	TestDBCount("sessions","1=1", 1)
	TestGUI('Logging out', "index.php", "reconnect=1", "Login name")
	TestDBCount("sessions","1=1", 0)

def GUI_Config_General_Housekeeper():
	print "GUI_Config_General_Mediatype"
	TestGUI('General->Housekeeper', "config.php", "alert_history=360&alarm_history=360&register=update", "Configuration updated")
	TestDBCount("config","alert_history=360 and alarm_history=360", 1)

def GUI_Config_General_Mediatype():
	print "GUI_Config_General_Mediatype"
	TestGUI('General->Media type->Add', "config.php", "config=1&description=Zzz&type=0&exec_path=&smtp_server=localhost&smtp_helo=localhost&smtp_email=zabbix%40localhost&register=add", "Added new media type")
	TestDBCount("media_type","description='Zzz' and type=0", 1)
	TestGUI('General->Media type->Delete', "config.php", "mediatypeid=2&config=1&description=Zzz&type=0&exec_path=&smtp_server=localhost&smtp_helo=localhost&smtp_email=zabbix%40localhost&register=delete", "Media type deleted")
	TestDBCount("media_type","description='Zzz'", 0)

def GUI_Config_Hosts():
	print "GUI_Config_Hosts"
	TestGUI('Hosts->Add', "hosts.php", "host=regression&newgroup=&useip=on&ip=127.0.0.1&port=10050&status=0&host_templateid=0&register=add#form", "Host added")
	TestDBCount("hosts","host='regression'", 1)
	id = DBGetID("hosts","host='regression'", "hostid")

	TestGUI('Hosts->Add (duplicate)', "hosts.php", "host=regression&newgroup=&useip=on&ip=127.0.0.1&port=10050&status=0&host_templateid=0&register=add#form", "already exists")
	TestDBCount("hosts","host='regression' and useip=1 and port=10050", 1)

	TestGUI('Hosts->Delete', "hosts.php", "?host=regression&newgroup=&useip=on&ip=127.0.0.1&port=10050&status=0&host_templateid=0&register=delete&config=0&hostid="+str(id)+"&devicetype=&name=&os=&serialno=&tag=&macaddress=&hardware=&software=&contact=&location=&notes=#form", "Host deleted")
	TestDBCount("hosts","host='regression'", 0)

def GUI_Config_Hosts_Groups():
	print "GUI_Config_Hosts_Groups"
	TestGUI('Hosts->Host groups->Add', "hosts.php", "name=Regression&register=add+group", "Group added")
	TestDBCount("groups","name='Regression'", 1)
	id = DBGetID("groups","name='regression'", "groupid")

	TestGUI('Hosts->Host groups->Add (duplicate)', "hosts.php", "name=Regression&register=add+group", "already exists")
	TestDBCount("groups","name='Regression'", 1)

	TestGUI('Hosts->Host groups->Delete', "hosts.php", "groupid="+str(id)+"&name=Regression&register=delete+group", "Group deleted")
	TestDBCount("groups","name='Regression'", 0)

def GUI_Config_Maps():
	print "GUI_Config_Maps"
	TestGUI('Configuration->Maps->Add', "sysmaps.php", "name=Regression&width=800&height=600&background=&label_type=0&register=add", "Network map added")
	TestGUI('Maps->Add (duplicate)', "sysmaps.php", "name=Regression&width=800&height=600&background=&label_type=0&register=add", "Cannot add network map")
	TestGUI('Maps->Delete', "sysmaps.php", "sysmapid=1&name=Regression&width=800&height=600&background=&label_type=0&register=delete", "Network map deleted")

def GUI_Config_Maps():
	print "GUI_Config_Maps"
	TestGUI('Graphs->Add', "graphs.php", "name=Regression&width=900&height=200&yaxistype=0&yaxismin=0&yaxismax=100&register=add", "Graph added")
	TestDBCount("graphs","name='Regression'", 1)
	TestGUI('Graphs->Delete', "graphs.php", "graphid=1&name=Regression&width=900&height=200&yaxistype=0&yaxismin=0.0000&yaxismax=100.0000&register=delete", "Graph deleted")
	TestDBCount("graphs","name='Regression'", 0)

def GUI_Config_Screens():
	print "GUI_Config_Screens"
	TestGUI('Screens->Add', "screenconf.php", "name=Regression&cols=2&rows=2&register=add", "Screen added")
	TestDBCount("screens","name='Regression'", 1)
	id = DBGetID("screens","name='Regression'", "screenid")

	TestGUI('Screens->Update', "screenconf.php", "screenid="+str(id)+"&name=Yo&cols=1&rows=1&register=update", "Screen updated")
	TestDBCount("screens","screenid="+str(id), 1)

	TestGUI('Screens->Delete', "screenconf.php", "screenid="+str(id)+"&name=Regression&cols=2&rows=2&register=delete", "Screen deleted")
	TestDBCount("screens","screenid="+str(id), 0)

def GUI_Config_Services():
	print "GUI_Config_Services"
	TestGUI('Service->Add', "services.php", "name=IT+Services&algorithm=1&showsla=on&goodsla=99.05&groupid=0&hostid=0&sortorder=0&register=add&softlink=true", "Service added")
	TestDBCount("services","name='IT Services'", 1)
	id = DBGetID("services","name='IT Services'", "serviceid")

	TestGUI('Service->Update', "services.php", "serviceid="+str(id)+"&name=IT+Services+new&algorithm=1&showsla=on&goodsla=99.10&groupid=0&hostid=0&sortorder=33&register=update&serviceupid=1&servicedownid=1&softlink=true&serviceid=1&serverid=10003", "Service updated")
	TestDBCount("services","serviceid="+str(id), 1)

	TestGUI('Service->Delete', "services.php", "serviceid="+str(id)+"&name=IT+Services+new&algorithm=1&showsla=on&goodsla=99.10&groupid=0&hostid=0&sortorder=33&register=delete&serviceupid=1&servicedownid=1&softlink=true&serviceid=1&serverid=10003", "Service deleted")
	TestDBCount("services","serviceid="+str(id), 0)

InitDB()

GUI_Login()
GUI_Config_General_Housekeeper()
GUI_Config_General_Mediatype()
GUI_Config_Hosts()
GUI_Config_Hosts_Groups()
GUI_Config_Maps()
GUI_Config_Screens()
GUI_Config_Services()
