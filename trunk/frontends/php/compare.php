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
	$page["title"] = "Latest values";
	$page["file"] = "latest.php";
	show_header($page["title"],300,0);
?>

<?php
	if(!check_right("Item","R",$_GET["itemid"]))
	{
		show_table_header("<font color=\"AA0000\">No permissions !</font>");
		show_footer();
		exit;
	}
?>

<?php
	show_table_header_begin();
	$result=DBselect("select i.description,h.host,h.hostid from items i,hosts h where i.hostid=h.hostid and i.itemid=".$_GET["itemid"]);
	$description=DBget_field($result,0,0);
	$host=DBget_field($result,0,1);
	$hostid=DBget_field($result,0,2);

	echo "<A HREF='latest.php?hostid=$hostid'>$host</A> : <a href='history.php?action=showhistory&itemid=".$_GET["itemid"]."'>$description</a>";

	show_table_v_delimiter();

	echo "<font size=2>";

	if(isset($_GET["type"])&&$_GET["type"]=="year")
	{
		echo "<b>[<a href='compare.php?itemid=".$_GET["itemid"]."&type=year'>year</a>]</b> ";
	}
	else
	{
		echo "<a href='compare.php?itemid=".$_GET["itemid"]."&type=year'>year</a> ";
	}
	if(isset($_GET["type"])&&$_GET["type"]=="month")
	{
		echo "<b>[<a href='compare.php?itemid=".$_GET["itemid"]."&type=month'>month</a>]</b> ";
	}
	else
	{
		echo "<a href='compare.php?itemid=".$_GET["itemid"]."&type=month'>month</a> ";
	}
	if(isset($_GET["type"])&&$_GET["type"]=="week")
	{
		echo "<b>[<a href='compare.php?itemid=".$_GET["itemid"]."&type=week'>week</a>]</b> ";
	}
	else
	{
		echo "<a href='compare.php?itemid=".$_GET["itemid"]."&type=week'>week</a> ";
	}
	echo "</font>";


	if(isset($_GET["type"]))
	{
		show_table_v_delimiter();
		echo "<b>[<a href='compare.php?itemid=".$_GET["itemid"]."&type=".$_GET["type"]."&type2=day'>day</a>]</b> ";
	}

	show_table_header_end();
	echo "<br>";
?>

<?php
	if(isset($_GET["itemid"])&&isset($_GET["type"]))
	{
		show_table_header(strtoupper($_GET["type"]));
	}
	else
	{
		show_table_header("Select type of trend");
	}
	echo "<TABLE BORDER=0 align=center COLS=4 WIDTH=100% BGCOLOR=\"#CCCCCC\" cellspacing=1 cellpadding=3>";
	echo "<TR BGCOLOR=#EEEEEE>";
	echo "<TR BGCOLOR=#DDDDDD>";
	echo "<TD ALIGN=CENTER>";
	if(isset($_GET["itemid"])&&isset($_GET["type"]))
	{
		if(isset($_GET["trendavg"]))
		{
			echo "<script language=\"JavaScript\">";
			echo "if (navigator.appName == \"Microsoft Internet Explorer\")";
			echo "{";
			echo " document.write(\"<IMG SRC='chart3.php?itemid=".$_GET["itemid"]."&type=".$_GET["type"]."&trendavg=1&width=\"+(document.body.clientWidth-108)+\"'>\")";
			echo "}";
			echo "else if (navigator.appName == \"Netscape\")";
			echo "{";
			echo " document.write(\"<IMG SRC='chart3.php?itemid=".$_GET["itemid"]."&type=".$_GET["type"]."&trendavg=1&width=\"+(document.width-108)+\"'>\")";
			echo "}";
			echo "else";
			echo "{";
			echo " document.write(\"<IMG SRC='chart3.php?itemid=".$_GET["itemid"]."&type=".$_GET["type"]."&trendavg=1'>\")";
			echo "}";
			echo "</script>";
		}
		else
		{
			echo "<script language=\"JavaScript\">";
			echo "if (navigator.appName == \"Microsoft Internet Explorer\")";
			echo "{";
			echo " document.write(\"<IMG SRC='chart3.php?itemid=".$_GET["itemid"]."&type=".$_GET["type"]."&width=\"+(document.body.clientWidth-108)+\"'>\")";
			echo "}";
			echo "else if (navigator.appName == \"Netscape\")";
			echo "{";
			echo " document.write(\"<IMG SRC='chart3.php?itemid=".$_GET["itemid"]."&type=".$_GET["type"]."&width=\"+(document.width-108)+\"'>\")";
			echo "}";
			echo "else";
			echo "{";
			echo " document.write(\"<IMG SRC='chart3.php?itemid=".$_GET["itemid"]."&type=".$_GET["type"]."'>\")";
			echo "}";
			echo "</script>";
		}
	}
	else
	{
		echo "...";
	}
	echo "</TD>";
	echo "</TR>";
	echo "</TABLE>";

?>

<?php
	show_footer();
?>
