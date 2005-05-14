<?php
/* 
** Zabbix
** Copyright (C) 2000,2001,2002,2003,2004 Alexei Vladishev
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
	include "include/config.inc.php";

	$page["title"] = S_QUEUE_BIG;
	$page["file"] = "queue.php";
	show_header($page["title"],10,0);
?>
 
<?php
	if(!check_anyright("Host","R"))
	{
		show_table_header("<font color=\"AA0000\">".S_NO_PERMISSIONS."</font>");
		show_footer();
		exit;	
	}
?>

<?php
	if(!isset($_GET["show"]))
	{
		$_GET["show"]=0;
	}

	$h1=S_QUEUE_OF_ITEMS_TO_BE_UPDATED_BIG;

#	$h2=S_GROUP."&nbsp;";
	$h2="";
	$h2=$h2."<select class=\"biginput\" name=\"show\" onChange=\"submit()\">";
	$h2=$h2.form_select("show",0,S_OVERVIEW);
	$h2=$h2.form_select("show",1,S_DETAILS);
	$h2=$h2."</select>";

	show_header2($h1, $h2, "<form name=\"selection\" method=\"get\" action=\"queue.php\">", "</form>");
?>

<?php
	$now=time();

	$result=DBselect("select i.itemid, i.nextcheck, i.description, h.host,h.hostid from items i,hosts h where i.status=0 and i.type not in (2) and ((h.status=".HOST_STATUS_MONITORED." and h.available!=".HOST_AVAILABLE_FALSE.") or (h.status=".HOST_STATUS_MONITORED." and h.available=".HOST_AVAILABLE_FALSE." and h.disable_until<=$now)) and i.hostid=h.hostid and i.nextcheck<$now and i.key_ not in ('status','icmpping','icmppingsec','zabbix[log]') order by i.nextcheck");
	table_begin();
	if($_GET["show"]==0)
	{
		$sec_5=0;
		$sec_10=0;
		$sec_30=0;
		$sec_60=0;
		$sec_300=0;
		$sec_rest=0;
		while($row=DBfetch($result))
		{
			if(!check_right("Host","R",$row["hostid"]))
			{
				continue;
			}
			if($now-$row["nextcheck"]<=5)		$sec_5++;
			elseif($now-$row["nextcheck"]<=10)	$sec_10++;
			elseif($now-$row["nextcheck"]<=30)	$sec_30++;
			elseif($now-$row["nextcheck"]<=60)	$sec_60++;
			elseif($now-$row["nextcheck"]<=300)	$sec_300++;
			else					$sec_rest++;

		}
		table_header(array(S_DELAY,S_COUNT));
		$elements=array(S_5_SECONDS,$sec_5);
		table_row($elements,$col++);
		$elements=array(S_10_SECONDS,$sec_10);
		table_row($elements,$col++);
		$elements=array(S_30_SECONDS,$sec_30);
		table_row($elements,$col++);
		$elements=array(S_1_MINUTE,$sec_60);
		table_row($elements,$col++);
		$elements=array(S_5_MINUTES,$sec_300);
		table_row($elements,$col++);
		$elements=array(S_MORE_THAN_5_MINUTES,$sec_rest);
		table_row($elements,$col++);
	}
	else
	{
		table_header(array(S_NEXT_CHECK,S_HOST,S_DESCRIPTION));
		$col=0;
		while($row=DBfetch($result))
		{
			if(!check_right("Host","R",$row["hostid"]))
			{
				continue;
			}
			$elements=array(date("m.d.Y H:i:s",$row["nextcheck"]),$row["host"],$row["description"]);
			table_row($elements,$col++);
		}
		iif_echo(DBnum_rows($result)==0,
			"<TR BGCOLOR=#EEEEEE><TD COLSPAN=3 ALIGN=CENTER>".S_THE_QUEUE_IS_EMPTY."</TD><TR>",
			"");
	}

	table_end();
?>
<?php
	show_table_header(S_TOTAL.":$col");
?>

<?php
	show_footer();
?>
