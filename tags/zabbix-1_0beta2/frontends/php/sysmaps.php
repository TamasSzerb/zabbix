<?
	include "include/config.inc";
	$page["title"] = "Network maps";
	$page["file"] = "sysmaps.php";
	show_header($page["title"],0,0);
?>

<?
	if(isset($register))
	{
		if($register=="add")
		{
			$result=add_sysmap($name,$width,$height);
			show_messages($result,"Network map added","Cannot add network map");
		}
		if($register=="update")
		{
			$result=update_sysmap($sysmapid,$name,$width,$height);
			show_messages($result,"Network map updated","Cannot update network map");
		}
		if($register=="delete")
		{
			$result=delete_sysmap($sysmapid);
			show_messages($result,"Network map deleted","Cannot delete network map");
			unset($sysmapid);
		}
	}
?>

<?
	show_table_header("CONFIGURATION OF NETWORK MAPS");
	echo "<br>";
?>

<?
	show_table_header("NETWORK MAPS");
	echo "<TABLE BORDER=0 COLS=4 WIDTH=\"100%\" BGCOLOR=\"#CCCCCC\" cellspacing=1 cellpadding=3>";
	echo "<TD WIDTH=\"10%\" NOSAVE><B>Name</B></TD>";
	echo "<TD WIDTH=\"10%\" NOSAVE><B>Width</B></TD>";
	echo "<TD WIDTH=\"10%\" NOSAVE><B>Height</B></TD>";
	echo "<TD WIDTH=\"10%\" NOSAVE><B>Actions</B></TD>";
	echo "</TR>";

	$result=DBselect("select s.sysmapid,s.name,s.width,s.height from sysmaps s order by s.name");
	echo "<CENTER>";
	$col=0;
	for($i=0;$i<DBnum_rows($result);$i++)
	{
		if($col==1)
		{
			echo "<TR BGCOLOR=#EEEEEE>";
			$col=0;
		} else
		{
			echo "<TR BGCOLOR=#DDDDDD>";
			$col=1;
		}
	
		$sysmapid_=DBget_field($result,$i,0);
		$name_=DBget_field($result,$i,1);
		$width_=DBget_field($result,$i,2);
		$height_=DBget_field($result,$i,3);
		echo "<TD><a href=\"sysmap.php?sysmapid=$sysmapid_\">$name_</a></TD>";
		echo "<TD>$width_</TD>";
		echo "<TD>$height_</TD>";
		echo "<TD><A HREF=\"sysmaps.php?sysmapid=$sysmapid_#form\">Change</A> - <A HREF=\"sysmaps.php?register=delete&sysmapid=$sysmapid_\">Delete</A></TD>";
		echo "</TR>";
	}
	echo "</TABLE>";
?>

<?
	echo "<a name=\"form\"></a>";

	if(isset($sysmapid))
	{
		$result=DBselect("select s.sysmapid,s.name,s.width,s.height from sysmaps s where sysmapid=$sysmapid");
		$name=DBget_field($result,0,1);
		$width=DBget_field($result,0,2);
		$height=DBget_field($result,0,3);
	}
	else
	{
		$name="";
		$width=800;
		$height=600;
	}

	echo "<br>";
	show_table2_header_begin();
	echo "New system map";

	show_table2_v_delimiter();
	echo "<form method=\"post\" action=\"sysmaps.php\">";
	if(isset($sysmapid))
	{
		echo "<input name=\"sysmapid\" type=\"hidden\" value=$sysmapid>";
	}
	echo "Name";
	show_table2_h_delimiter();
	echo "<input name=\"name\" value=\"$name\" size=32>";

	show_table2_v_delimiter();
	echo "Width";
	show_table2_h_delimiter();
	echo "<input name=\"width\" size=5 value=\"$width\">";

	show_table2_v_delimiter();
	echo "Height";
	show_table2_h_delimiter();
	echo "<input name=\"height\" size=5 value=\"$height\">";

	show_table2_v_delimiter2();
	echo "<input type=\"submit\" name=\"register\" value=\"add\">";
	if(isset($sysmapid))
	{
		echo "<input type=\"submit\" name=\"register\" value=\"update\">";
	}

	show_table2_header_end();
?>

<?
	show_footer();
?>
