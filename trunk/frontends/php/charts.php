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
	$page["title"] = S_CUSTOM_GRAPHS;
	$page["file"] = "charts.php";

	$nomenu=0;
	if(isset($_GET["fullscreen"]))
	{
		$nomenu=1;
	}
	if(isset($_GET["graphid"]) && !isset($_GET["period"]) && !isset($_GET["stime"]))
	{
		show_header($page["title"],30,$nomenu);
	}
	else
	{
		show_header($page["title"],0,$nomenu);
	}

?>

<?php
	if(!isset($_GET["from"]))
	{
		$_GET["from"]=0;
	}
	if(!isset($_GET["period"]))
	{
		$_GET["period"]=3600;
	}

	if(!isset($_GET["keep"]))
	{
		$_GET["keep"]=1;
	}


	if(isset($_GET["graphid"])&&($_GET["graphid"]==0))
	{
		unset($_GET["graphid"]);
	}

	if(isset($_GET["graphid"]))
	{
		$result=DBselect("select name from graphs where graphid=".$_GET["graphid"]);
		$graph=DBget_field($result,0,0);
		$h1=iif(isset($_GET["fullscreen"]),
			"<a href=\"charts.php?graphid=".$_GET["graphid"]."\">".$graph."</a>",
			"<a href=\"charts.php?graphid=".$_GET["graphid"]."&fullscreen=1\">".$graph."</a>");
	}
	else
	{
		$h1=S_SELECT_GRAPH_TO_DISPLAY;
	}

	$h1=S_GRAPHS_BIG.nbsp(" / ").$h1;

	$h2=S_GROUP."&nbsp;";
	$h2=$h2."<select class=\"biginput\" name=\"groupid\" onChange=\"submit()\">";
	$h2=$h2."<option value=\"0\" ".iif(!isset($_GET["groupid"]),"selected","").">".S_ALL_SMALL;
	$result=DBselect("select groupid,name from groups order by name");
	while($row=DBfetch($result))
	{
// Check if at least one host with read permission exists for this group
		$result2=DBselect("select h.hostid,h.host from hosts h,items i,hosts_groups hg where h.status=".HOST_STATUS_MONITORED." and h.hostid=i.hostid and hg.groupid=".$row["groupid"]." and hg.hostid=h.hostid group by h.hostid,h.host order by h.host");
		$cnt=0;
		while($row2=DBfetch($result2))
		{
			if(!check_right("Host","R",$row2["hostid"]))
			{
				continue;
			}
			$cnt=1; break;
		}
		if($cnt!=0)
		{
			$h2=$h2."<option value=\"".$row["groupid"]."\" ".iif(isset($_GET["groupid"])&&($_GET["groupid"]==$row["groupid"]),"selected","").">".$row["name"];
		}
	}
	$h2=$h2."</select>";

	$h2=$h2."&nbsp;".S_HOST."&nbsp;";
	$h2=$h2."<select class=\"biginput\" name=\"hostid\" onChange=\"submit()\">";
	$h2=$h2."<option value=\"0\"".iif(!isset($_GET["hostid"])||($_GET["hostid"]==0),"selected","").">".S_SELECT_HOST_DOT_DOT_DOT;

	if(isset($_GET["groupid"]))
	{
		$sql="select h.hostid,h.host from hosts h,items i,hosts_groups hg where h.status=".HOST_STATUS_MONITORED." and h.hostid=i.hostid and hg.groupid=".$_GET["groupid"]." and hg.hostid=h.hostid group by h.hostid,h.host order by h.host";
	}
	else
	{
		$sql="select h.hostid,h.host from hosts h,items i where h.status=".HOST_STATUS_MONITORED." and h.hostid=i.hostid group by h.hostid,h.host order by h.host";
	}

	$result=DBselect($sql);
	while($row=DBfetch($result))
	{
		if(!check_right("Host","R",$row["hostid"]))
		{
			continue;
		}
		$h2=$h2."<option value=\"".$row["hostid"]."\"".iif(isset($_GET["hostid"])&&($_GET["hostid"]==$row["hostid"]),"selected","").">".$row["host"];
	}
	$h2=$h2."</select>";

	if(isset($_GET["fullscreen"]))
	{
		$h2="<input name=\"fullscreen\" type=\"hidden\" value=".$_GET["fullscreen"].">";
	}

	if(isset($_GET["graphid"])&&($_GET["graphid"]==0))
	{
		unset($_GET["graphid"]);
	}

	$h2=$h2."&nbsp;".S_GRAPH."&nbsp;";
	$h2=$h2."<select class=\"biginput\" name=\"graphid\" onChange=\"submit()\">";
	$h2=$h2."<option value=\"0\" ".iif(!isset($_GET["graphid"]),"selected","").">".S_SELECT_GRAPH_DOT_DOT_DOT;

	$result=DBselect("select graphid,name from graphs order by name");
	while($row=DBfetch($result))
	{
		if(!check_right("Graph","R",$row["graphid"]))
		{
			continue;
		}
		$h2=$h2."<option value=\"".$row["graphid"]."\" ".iif(isset($_GET["graphid"])&&($_GET["graphid"]==$row["graphid"]),"selected","").">".$row["name"];
	}
	$h2=$h2."</select>";

	show_header2($h1,$h2,"<form name=\"form2\" method=\"get\" action=\"charts.php\">","</form>");
?>

<?php
	echo "<TABLE BORDER=0 align=center COLS=4 WIDTH=100% BGCOLOR=\"#CCCCCC\" cellspacing=1 cellpadding=3>";
	echo "<TR BGCOLOR=#DDDDDD>";
	echo "<TD ALIGN=CENTER>";
	if(isset($_GET["graphid"]))
	{
		echo "<script language=\"JavaScript\">";
		echo "document.write(\"<IMG SRC='chart2.php?graphid=".$_GET["graphid"].url_param("stime")."&period=".$_GET["period"]."&from=".$_GET["from"]."&width=\"+(document.width-108)+\"'>\")";
		echo "</script>";
	}
	else
	{
		echo "...";
	}
	echo "</TD>";
	echo "</TR>";
	echo "</TABLE>";

	if(isset($_GET["graphid"])/*&&(!isset($_GET["fullscreen"]))*/)
	{
// BEGIN - IGMI - just another way of navigation
	echo "<TABLE BORDER=0 align=center COLS=2 WIDTH=100% BGCOLOR=\"#CCCCCC\" cellspacing=1 cellpadding=1>";
	echo "<TR BGCOLOR=#FFFFFF>";
	echo "<TD ALIGN=LEFT>";

	echo "<div align=left>";
	echo "<b>".S_PERIOD.":</b>&nbsp;";

	$hour=3600;
		
		$a=array(S_1H=>3600,S_2H=>2*3600,S_4H=>4*3600,S_8H=>8*3600,S_12H=>12*3600,
			S_24H=>24*3600,S_WEEK_SMALL=>7*24*3600,S_MONTH_SMALL=>31*24*3600,S_YEAR_SMALL=>365*24*3600);
		foreach($a as $label=>$sec)
		{
			echo "[";
			if($_GET["period"]>$sec)
			{
				$tmp=$_GET["period"]-$sec;
				echo("<A HREF=\"charts.php?period=$tmp".url_param("graphid").url_param("stime").url_param("from").url_param("keep").url_param("fullscreen")."\">-</A>");
			}
			else
			{
				echo "-";
			}

			echo("<A HREF=\"charts.php?period=$sec".url_param("graphid").url_param("stime").url_param("from").url_param("keep").url_param("fullscreen")."\">");
			echo($label."</A>");

			$tmp=$_GET["period"]+$sec;
			echo("<A HREF=\"charts.php?period=$tmp".url_param("graphid").url_param("stime").url_param("from").url_param("keep").url_param("fullscreen")."\">+</A>");

			echo "]&nbsp;";
		}

		echo("</div>");

	echo "</TD>";
	echo "<TD BGCOLOR=#FFFFFF WIDTH=15% ALIGN=RIGHT>";
	echo "<b>".nbsp(S_KEEP_PERIOD).":</b>&nbsp;";
		if($_GET["keep"] == 1)
		{
			echo("[<A HREF=\"charts.php?keep=0".url_param("graphid").url_param("from").url_param("period").url_param("fullscreen")."\">".S_ON_C."</a>]");
		}
		else
		{
			echo("[<A HREF=\"charts.php?keep=1".url_param("graphid").url_param("from").url_param("period").url_param("fullscreen")."\">".S_OFF_C."</a>]");
		}
	echo "</TD>";
	echo "</TR>";
	echo "<TR BGCOLOR=#FFFFFF>";
	echo "<TD>";
	if(isset($_GET["stime"]))
	{
		echo "<div align=left>" ;
		echo "<b>".S_MOVE.":</b>&nbsp;" ;

		$day=24;
// $a already defined
		$a=array("1h"=>1,"2h"=>2,"4h"=>4,"8h"=>8,"12h"=>12,
			"24h"=>24,"week"=>7*24,"month"=>31*24,"year"=>365*24);
		foreach($a as $label=>$hours)
		{
			echo "[";

			$stime=$_GET["stime"];
			$tmp=mktime(substr($stime,8,2),substr($stime,10,2),0,substr($stime,4,2),substr($stime,6,2),substr($stime,0,4));
			$tmp=$tmp-3600*$hours;
			$tmp=date("YmdHi",$tmp);
			echo("<A HREF=\"charts.php?stime=$tmp".url_param("graphid").url_param("period").url_param("keep").url_param("fullscreen")."\">-</A>");

			echo($label);

			$stime=$_GET["stime"];
			$tmp=mktime(substr($stime,8,2),substr($stime,10,2),0,substr($stime,4,2),substr($stime,6,2),substr($stime,0,4));
			$tmp=$tmp+3600*$hours;
			$tmp=date("YmdHi",$tmp);
			echo("<A HREF=\"charts.php?stime=$tmp".url_param("graphid").url_param("period").url_param("keep").url_param("fullscreen")."\">+</A>");

			echo "]&nbsp;";
		}
		echo("</div>");
	}
	else
	{
		echo "<div align=left>";
		echo "<b>".S_MOVE.":</b>&nbsp;";

		$day=24;
// $a already defined
		$a=array("1h"=>1,"2h"=>2,"4h"=>4,"8h"=>8,"12h"=>12,
			"24h"=>24,"week"=>7*24,"month"=>31*24,"year"=>365*24);
		foreach($a as $label=>$hours)
		{
			echo "[";
			$tmp=$_GET["from"]+$hours;
			echo("<A HREF=\"charts.php?from=$tmp".url_param("graphid").url_param("period").url_param("keep").url_param("fullscreen")."\">-</A>");

			echo($label);

			if($_GET["from"]>=$hours)
			{
				$tmp=$_GET["from"]-$hours;
				echo("<A HREF=\"charts.php?from=$tmp".url_param("graphid").url_param("period").url_param("keep").url_param("fullscreen")."\">+</A>");
			}
			else
			{
				echo "+";
			}

			echo "]&nbsp;";
		}
		echo("</div>");
	}
	echo "</TD>";
	echo "<TD BGCOLOR=#FFFFFF WIDTH=15% ALIGN=RIGHT>";
//		echo("<div align=left>");
		echo "<form method=\"put\" action=\"charts.php\">";
		echo "<input name=\"graphid\" type=\"hidden\" value=\"".$_GET["graphid"]."\" size=12>";
		echo "<input name=\"period\" type=\"hidden\" value=\"".(9*3600)."\" size=12>";
		if(isset($_GET["stime"]))
		{
			echo "<input name=\"stime\" class=\"biginput\" value=\"".$_GET["stime"]."\" size=12>";
		}
		else
		{
			echo "<input name=\"stime\" class=\"biginput\" value=\"yyyymmddhhmm\" size=12>";
		}
		echo "&nbsp;";
		echo "<input class=\"button\" type=\"submit\" name=\"action\" value=\"go\">";
		echo "</form>";
//		echo("</div>");
	echo "</TD>";
	echo "</TR>";
	echo "</TABLE>";

// END - IGMI - just another way of navigation
	}
	
?>

<?php
	show_footer();
?>
