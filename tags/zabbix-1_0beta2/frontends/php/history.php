<?
	$page["file"]="history.php";

	include "include/config.inc";

	$now=time();

	$result=DBselect("select h.host,i.description,i.nextcheck-$now,h.hostid from items i,hosts h where i.itemid=$itemid and h.hostid=i.hostid");
	$host=DBget_field($result,0,0);
	$description=DBget_field($result,0,1);
	$beforenextcheck=DBget_field($result,0,2)+5;
	if($beforenextcheck<=0)
	{
		$beforenextcheck=5;
	}
	$hostid=DBget_field($result,0,3);

	if($action=="showhistory")
	{
		show_header("$host:$description",$beforenextcheck,0);
	}
	if($action=="showvalues")
	{
		show_header("$host:$description",0,0);
	}
	if($action=="showfreehist")
	{
		show_header("$host:$description",0,0);
	}
	if($action=="showplaintxt")
	{
		show_header("$host:$description",0,0);
	}
?>

<?
	if($action=="plaintext")
	{
		$from=mktime($fromhour,$frommin,0,$frommonth,$fromday,$fromyear);
		$till=mktime($tillhour,$tillmin,0,$tillmonth,$tillday,$tillyear);
		show_plaintext($itemid, $from, $till);
		exit;
	}

?>

<?
	show_table_header_begin();
	echo "<A HREF='latest.php?hostid=$hostid'>$host</A> : <a href='trends.php?itemid=$itemid'>$description</a>";
	show_table_v_delimiter();
	echo("<DIV ALIGN=CENTER>");
	if($action =="showhistory")
	{
		echo("<b>[<A HREF=\"history.php?action=showhistory&itemid=$itemid\">Last hour graph</A>]</b> ");
	}
	else
	{
		echo("<A HREF=\"history.php?action=showhistory&itemid=$itemid\">Last hour graph</A> ");
	}
	if($action =="showvalues")
	{
		echo("<b>[<A HREF=\"history.php?action=showvalues&itemid=$itemid&period=3600\">Values of last hour</A>]</b> ");
	}
	else
	{
		echo("<A HREF=\"history.php?action=showvalues&itemid=$itemid&period=3600\">Values of last hour</A> ");
	}
	if($action =="showfreehist")
	{
		echo("<b>[<A HREF=\"history.php?action=showfreehist&itemid=$itemid\">Values of specified period</A>]</b> ");
	}
	else
	{
		echo("<A HREF=\"history.php?action=showfreehist&itemid=$itemid\">Values of specified period</A> ");
	}
	if($action =="showplaintxt")
	{
		echo("<b>[<A HREF=\"history.php?action=showplaintxt&itemid=$itemid\">Values in plaint text format</A>]</b> ");
	}
	else
	{
		echo("<A HREF=\"history.php?action=showplaintxt&itemid=$itemid\">Values in plaint text format</A> ");
	}
	echo("</DIV>\n");
	show_table_header_end();
	echo("<br>");

	if($action=="showfreehist")
	{
		if(!isset($period))
		{
			show_freehist($itemid,$period);
		} 
		exit;
 
	}

	if($action=="showplaintxt")
	{
		if(!isset($period))
		{
			show_plaintxt($itemid,$period);
		} 
		exit;
   
	}

	if($action=="showvalues")
	{
		if(!isset($from))
		{
			$from=0;
		}
		if(!isset($period))
		{
			$period=3600;
		}
		$time=time(NULL)-$period-$from*3600;
		$till=time(NULL)-$from*3600;
		$hours=$period/3600;

		show_table_header("Showing history of $period seconds($hours h)<BR>[from: ".date("d M - H:i:s",$time)."] [till: ".date("d M - H:i:s",$till)."]");

		echo "<TABLE BORDER=0 COLS=2 ALIGN=CENTER WIDTH=\"100%\" BGCOLOR=\"#CCCCCC\" cellspacing=1 cellpadding=3>";
		echo "<TR>";
		echo "<TD><B>Clock</B></TD>";
		echo "<TD><B>Value</B></TD>";
		echo "</TR>";


		$result=DBselect("select clock,value from history where itemid=$itemid and clock>$time and clock<$till order by clock desc");
		$col=0;
		for($i=0;$i<DBnum_rows($result);$i++)
		{
			if($col==1)
			{
				echo "<TR BGCOLOR=#DDDDDD>";
				$col=0;
			} else
			{
				echo "<TR BGCOLOR=#EEEEEE>";
				$col=1;
			}
			$clock=DBget_field($result,$i,0);
			$value=DBget_field($result,$i,1);
			$clock=date("d M - H:i:s",$clock);
			echo "<TD>$clock</TD>";
			echo "<TD>$value</TD>";
			echo "</TR>";
		}
		echo "</TABLE><CENTER><BR>";        
 
		echo("</CENTER></BODY></HTML>\n");

		show_footer();
		exit;
	}

	if($action=="showhistory")
	{
		@show_history($itemid,$from,$period);
		show_footer();
		exit;
	}
?>
