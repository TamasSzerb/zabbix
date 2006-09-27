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
	require_once "include/maps.inc.php";
	require_once "include/forms.inc.php";

	$page["title"] = "S_NETWORK_MAPS";
	$page["file"] = "sysmaps.php";
	show_header($page["title"],0,0);
	insert_confirm_javascript();
?>
<?php
//		VAR			TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION
	$fields=array(
		"sysmapid"=>		array(T_ZBX_INT, O_OPT,	 P_SYS,	DB_ID,NULL),

		"name"=>		array(T_ZBX_STR, O_OPT,	 NULL,	NOT_EMPTY,		"isset({save})"),
		"width"=>		array(T_ZBX_INT, O_OPT,	 NULL,	BETWEEN(0,65535),	"isset({save})"),
		"height"=>		array(T_ZBX_INT, O_OPT,	 NULL,	BETWEEN(0,65535),	"isset({save})"),
		"backgroundid"=>	array(T_ZBX_INT, O_OPT,	 NULL,	DB_ID,			"isset({save})"),
		"label_type"=>		array(T_ZBX_INT, O_OPT,	 NULL,	BETWEEN(0,4),		"isset({save})"),
		"label_location"=>	array(T_ZBX_INT, O_OPT,	 NULL,	BETWEEN(0,3),		"isset({save})"),

/* Actions */
		"save"=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		"delete"=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		"cancel"=>		array(T_ZBX_STR, O_OPT, P_SYS,	NULL,	NULL),

/* Form */
		"form"=>		array(T_ZBX_STR, O_OPT, P_SYS,	NULL,	NULL),
		"form_refresh"=>	array(T_ZBX_INT, O_OPT,	NULL,	NULL,	NULL)

	);
	check_fields($fields);
?>
<?php
	if(isset($_REQUEST["save"]))
	{
		if(isset($_REQUEST["sysmapid"]))
		{
			$result=update_sysmap($_REQUEST["sysmapid"],$_REQUEST["name"],$_REQUEST["width"],
				$_REQUEST["height"],$_REQUEST["backgroundid"],$_REQUEST["label_type"],
				$_REQUEST["label_location"]);

			show_messages($result,"Network map updated","Cannot update network map");
		} else {
			$result=add_sysmap($_REQUEST["name"],$_REQUEST["width"],$_REQUEST["height"],
				$_REQUEST["backgroundid"],$_REQUEST["label_type"],$_REQUEST["label_location"]);

			show_messages($result,"Network map added","Cannot add network map");
		}
		if($result){
			unset($_REQUEST["form"]);
		}
	}
	elseif(isset($_REQUEST["delete"])&&isset($_REQUEST["sysmapid"]))
	{
		$result=delete_sysmap($_REQUEST["sysmapid"]);
		show_messages($result,"Network map deleted","Cannot delete network map");
		if($result){
			unset($_REQUEST["form"]);
		}
	}
?>

<?php
	$form = new CForm();
	$form->AddItem(new CButton("form",S_CREATE_MAP));
	show_header2(S_CONFIGURATION_OF_NETWORK_MAPS, $form);
	echo BR;
?>

<?php
	if(isset($_REQUEST["form"]))
	{
		insert_map_form();
	}
	else
	{
		show_header2(S_MAPS_BIG);
		$table = new CTableInfo(S_NO_MAPS_DEFINED);
		$table->setHeader(array(S_ID,S_NAME,S_WIDTH,S_HEIGHT,S_MAP));

		$result=DBselect("select sysmapid,name,width,height from sysmaps where mod(sysmapid,100)=$ZBX_CURNODEID order by name");
		while($row=DBfetch($result))
		{
			$table->addRow(array(
				$row["sysmapid"],
				new CLink($row["name"], "sysmaps.php?form=update".
					"&sysmapid=".$row["sysmapid"]."#form",'action'),
				$row["width"],
				$row["height"],
				new CLink(S_EDIT,"sysmap.php?sysmapid=".$row["sysmapid"])
				));
		}
		$table->show();
	}
?>

<?php
	show_page_footer();
?>
