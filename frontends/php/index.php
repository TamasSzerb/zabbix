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
	$page["title"]="S_ZABBIX_BIG";
	$page["file"]="index.php";

	include "include/config.inc.php";
	include "include/forms.inc.php";

	if(isset($_REQUEST["password"]))
	{
		$password=$_REQUEST["password"];
	}
	else
	{
		unset($password);
	}
	if(isset($_REQUEST["name"]))
	{
		$name=$_REQUEST["name"];
	}
	else
	{
		unset($name);
	}
	if(isset($_REQUEST["register"]))
	{
		$register=$_REQUEST["register"];
	}
	else
	{
		unset($register);
	}
	if(isset($_REQUEST["reconnect"]))
	{
		$reconnect=$_REQUEST["reconnect"];
	}
	else
	{
		unset($reconnect);
	}
	if(isset($_COOKIE["sessionid"]))
	{
		$sessionid=$_COOKIE["sessionid"];
	}
	else
	{
		unset($sessionid);
	}


	if(isset($reconnect))
	{
		$sql="delete from sessions where sessionid='$sessionid'";
		DBexecute($sql);
		setcookie("sessionid",$sessionid,time()-3600);
		unset($sessionid);
	}

	if(isset($register)&&($register=="Enter"))
	{
		$password=md5($password);
		$sql="select u.userid,u.alias,u.name,u.surname,u.url,u.refresh from users u where u.alias='$name' and u.passwd='$password'";
		$result=DBselect($sql);
		if(DBnum_rows($result)==1)
		{
			$row=DBfetch($result);
			$USER_DETAILS["userid"]=$row["userid"];
			$USER_DETAILS["alias"]=$row["alias"];
			$USER_DETAILS["name"]=$row["name"];
			$USER_DETAILS["surname"]=$row["surname"];
			$USER_DETAILS["url"]=$row["url"];
			$USER_DETAILS["refresh"]=$row["refresh"];
			$sessionid=md5(time().$password.$name.rand(0,10000000));
			setcookie("sessionid",$sessionid,time()+3600);
// Required !
			$_COOKIE["sessionid"]=$sessionid;
			$sql="insert into sessions (sessionid,userid,lastaccess) values ('$sessionid',".$USER_DETAILS["userid"].",".time().")";
			DBexecute($sql);

			if($USER_DETAILS["url"] != '')
			{
				echo "<HTML><HEAD>";
        			echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=".$USER_DETAILS["url"]."\">";
				echo "</HEAD></HTML>";
				return;
			}
		}
	}

	show_header($page["title"],0,0);
?>

<?php
	if(!isset($sessionid))
	{
//		echo "-",$_COOKIE["sessionid"],"-<br>";
		insert_login_form();
		show_messages();
	}
	else
	{
		echo "<div align=center>";
		echo "Press <a href=\"index.php?reconnect=1\">here</a> to disconnect/reconnect";
		echo "</div>";
	}	
?>

<?php
	show_footer();
?>
