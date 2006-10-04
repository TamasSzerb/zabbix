<?php
/* 
** ZABBIX
** Copyright (C) 2000-2005 SIA Zabbix
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
	require_once "include/config.inc.php";
	require_once "include/triggers.inc.php";

	$page["title"] = "S_TRIGGERS_TOP_100";
	$page["file"] = "report5.php";
	show_header($page["title"],0,0);
?>
<?php
//		VAR			TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION
	$fields=array(
		"period"=>		array(T_ZBX_STR, O_OPT,	P_SYS|P_NZERO,	IN('"day","week","month","year"'),		NULL)
	);

	check_fields($fields);
?>
<?php
	$_REQUEST["period"] = get_request("period", "day");

	$form = new CForm();
	
	$cmbPeriod = new CComboBox("period",$_REQUEST["period"],"submit()");
	$cmbPeriod->AddItem("day",S_DAY);
	$cmbPeriod->AddItem("week",S_WEEK);
	$cmbPeriod->AddItem("month",S_MONTH);
	$cmbPeriod->AddItem("year",S_YEAR);

	$form->AddItem($cmbPeriod);

	show_header2(S_TRIGGERS_TOP_100_BIG, $form);
?>
<?php
	$table = new CTableInfo();
	$table->setHeader(array(S_HOST,S_TRIGGER,S_SEVERITY,S_NUMBER_OF_STATUS_CHANGES));

	switch($_REQUEST["period"])
	{
		case "week":	$time_dif=7*24*3600;	break;
		case "month":	$time_dif=10*24*3600;	break;
		case "year":	$time_dif=365*24*3600;	break;
	/* day */ default:	$time_dif=24*3600;	break;
	}

	$denyed_hosts = get_accessible_hosts_by_userid($USER_DETAILS['userid'],PERM_READ_LIST, PERM_MODE_LE, null, $ZBX_CURNODEID);
	
        $result=DBselect("select h.host, t.triggerid, t.description, t.priority, count(a.eventid) as count ".
		" from hosts h, triggers t, functions f, items i, events a where ".
		" h.hostid = i.hostid and i.itemid = f.itemid and t.triggerid=f.triggerid and ".
		" t.triggerid=a.triggerid and a.clock>".(time()-$time_dif).
		" and h.hostid not in (".$denyed_hosts.") ".
		" group by h.host,t.triggerid,t.description,t.priority order by 5 desc,1,3", 100);

        while($row=DBfetch($result))
        {
            	$table->addRow(array(
			$row["host"],
			expand_trigger_description_by_data($row),
			new CCol(get_severity_description($row["priority"]),get_severity_style($row["priority"])),
			$row["count"],
			));
	}
	$table->show();

	show_page_footer();
?>
