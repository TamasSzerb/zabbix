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
	include "include/classes.inc.php";

	$graph=new Graph();
	if(isset($_GET["period"]))
	{
		$graph->setPeriod($_GET["period"]);
	}
	if(isset($_GET["from"]))
	{
		$graph->setFrom($_GET["from"]);
	}
	if(isset($_GET["stime"]))
	{
		$graph->setSTime($_GET["stime"]);
	}
	if(isset($_GET["border"]))
	{
		$graph->setBorder(0);
	}

	$result=DBselect("select name,width,height from graphs where graphid=".$_GET["graphid"]);

	$name=DBget_field($result,0,0);
	if(isset($_GET["width"])&&$_GET["width"]>0)
	{
		$width=$_GET["width"];
	}
	else
	{
		$width=DBget_field($result,0,1);
	}
	if(isset($_GET["height"])&&$_GET["height"]>0)
	{
		$height=$_GET["height"];
	}
	else
	{
		$height=DBget_field($result,0,2);
	}

	$graph->setWidth($width);
	$graph->setHeight($height);
	$graph->setHeader(DBget_field($result,0,0));

	$result=DBselect("select gi.itemid,i.description,gi.color,h.host,gi.drawtype from graphs_items gi,items i,hosts h where gi.itemid=i.itemid and gi.graphid=".$_GET["graphid"]." and i.hostid=h.hostid order by gi.sortorder");

	for($i=0;$i<DBnum_rows($result);$i++)
	{
		$graph->addItem(DBget_field($result,$i,0));
		$graph->setColor(DBget_field($result,$i,0), DBget_field($result,$i,2));
		$graph->setDrawtype(DBget_field($result,$i,0), DBget_field($result,$i,4));
	}

	$graph->Draw();
?>

