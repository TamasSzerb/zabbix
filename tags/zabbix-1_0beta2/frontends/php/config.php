<?
	$page["title"] = "Configuration of Zabbix";
	$page["file"] = "config.php";

	include "include/config.inc";
	show_header($page["title"],0,0);
?>

<?
	if(isset($register) && ($register=="update"))
	{
		if(isset($password_required) && ($password_required=="true"))
		{
			$password_required="1";
		}
		else
		{
			$password_required="0";
		}
		$result=update_config($smtp_server,$smtp_helo,$smtp_email,$password_required,$alarm_history,$alert_history);
		show_messages($result, "Configuration updated", "Configuation was NOT updated");
	}
?>

<?
	show_table_header("CONFIGURATION OF ZABBIX");
	echo "<br>";
?>

<?
	$config=select_config();
?>

<?
	show_table2_header_begin();
	echo "Configuration";

	show_table2_v_delimiter();
	echo "<form method=\"post\" action=\"config.php\">";
	echo "SMTP server";
	show_table2_h_delimiter();
	echo "<input name=\"smtp_server\" value=\"".$config["smtp_server"]."\"size=40>";

	show_table2_v_delimiter();
	echo "Value from SMTP HELO authentification";
	show_table2_h_delimiter();
	echo "<input name=\"smtp_helo\" value=\"".$config["smtp_helo"]."\"size=40>";

	show_table2_v_delimiter();
	echo "ZABBIX email address to send alarms from";
	show_table2_h_delimiter();
	echo "<input name=\"smtp_email\" value=\"".$config["smtp_email"]."\"size=40>";

	show_table2_v_delimiter();
	echo "Password required ?";
	show_table2_h_delimiter();
	echo "<input type=\"checkbox\" ";
	if($config["password_required"]==1) { echo "checked "; }
	echo "name=\"password_required\"  VALUE=\"true\">";

	show_table2_v_delimiter();
	echo "Do not keep alerts older than (in sec)";
	show_table2_h_delimiter();
	echo "<input name=\"alert_history\" value=\"".$config["alert_history"]."\"size=8>";

	show_table2_v_delimiter();
	echo "Do not keep alarms older than (in sec)";
	show_table2_h_delimiter();
	echo "<input name=\"alarm_history\" value=\"".$config["alarm_history"]."\"size=8>";

	show_table2_v_delimiter2();
	echo "<input type=\"submit\" name=\"register\" value=\"update\">";

	show_table2_header_end();
?>

<?
	show_footer();
?>
