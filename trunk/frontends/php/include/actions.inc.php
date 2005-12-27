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
	function	get_action_by_actionid($actionid)
	{
		$sql="select * from actions where actionid=$actionid"; 
		$result=DBselect($sql);
		if(DBnum_rows($result) == 1)
		{
			return	DBfetch($result);	
		}
		else
		{
			error("No action with actionid=[$actionid]");
		}
		return	$result;
	}

	# Update Action

	function	update_action( $actionid, $filter_triggerid, $userid, $good, $delay, $subject, $message, $severity, $recipient, $usrgrpid, $maxrepeats, $repeatdelay)
	{
		if(!check_right_on_trigger("A",$triggerid))
		{
                        error("Insufficient permissions");
                        return 0;
		}

		if($recipient == RECIPIENT_TYPE_USER)
		{
			$id = $userid;
		}
		else
		{
			$id = $usrgrpid;
		}
		$subject=addslashes($subject);
		$message=addslashes($message);

		$sql="update actions set filter_triggerid=$filter_triggerid,userid=$id,good=$good,delay=$delay,nextcheck=0,subject='$subject',message='$message',severity=$severity,recipient=$recipient,maxrepeats=$maxrepeats,repeatdelay=$repeatdelay where actionid=$actionid";
		$result=DBexecute($sql);
		return $result;
	}

	# Add Action

	function	add_action( $userid, $good, $delay, $subject, $message, $recipient, $usrgrpid, $maxrepeats, $repeatdelay)
	{
//		if(!check_right_on_trigger("A",$triggerid))
//		{
//                      error("Insufficient permissions");
//                      return 0;
//		}

		if($recipient == RECIPIENT_TYPE_USER)
		{
			$id = $userid;
		}
		else
		{
			$id = $usrgrpid;
		}

		$sql="insert into actions (userid,good,delay,nextcheck,subject,message,recipient,maxrepeats,repeatdelay) values ($id,$good,$delay,0,'$subject','$message',$recipient,$maxrepeats,$repeatdelay)";
		$result=DBexecute($sql);
		return DBinsert_id($result,"actions","actionid");
	}

	# Delete Action by userid

	function	delete_actions_by_userid( $userid )
	{
		$sql="select actionid from actions where userid=$userid";
		$result=DBexecute($sql);
		while($row=DBfetch($result))
		{
			delete_alert_by_actionid($row["actionid"]);
		}

		$sql="delete from actions where userid=$userid";
		return	DBexecute($sql);
	}

	# Delete Action

	function	delete_action( $actionid )
	{
		$sql="delete from actions where actionid=$actionid";
		$result=DBexecute($sql);

		return delete_alert_by_actionid($actionid);
	}

	# Add action to hardlinked hosts

	function	add_action_to_linked_hosts($actionid,$hostid=0)
	{
		if($actionid<=0)
		{
			return;
		}

		$action=get_action_by_actionid($actionid);
		$trigger=get_trigger_by_triggerid($action["triggerid"]);

		$sql="select distinct h.hostid from hosts h,functions f, items i where i.itemid=f.itemid and h.hostid=i.hostid and f.triggerid=".$action["triggerid"];
		$result=DBselect($sql);
		if(DBnum_rows($result)!=1)
		{
			return;
		}
		$row=DBfetch($result);

		$host_template=get_host_by_hostid($row["hostid"]);

		if($hostid==0)
		{
			$sql="select hostid,templateid,actions from hosts_templates where templateid=".$row["hostid"];
		}
		else
		{
			$sql="select hostid,templateid,actions from hosts_templates where hostid=$hostid and templateid=".$row["hostid"];
		}
		$result=DBselect($sql);
		while($row=DBfetch($result))
		{
			if($row["actions"]&1 == 0)	continue;

			$sql="select distinct f.triggerid from functions f,items i,triggers t where t.description='".addslashes($trigger["description"])."' and t.triggerid=f.triggerid and i.itemid=f.itemid and i.hostid=".$row["hostid"];
			$result2=DBselect($sql);
			while($row2=DBfetch($result2))
			{
				$host=get_host_by_hostid($row["hostid"]);
				$message=str_replace("{".$host_template["host"].":", "{".$host["host"].":", $action["message"]);
				add_action($row2["triggerid"], $action["userid"], $action["good"], $action["delay"], $action["subject"], $message, $action["scope"], $action["severity"], $action["recipient"], $action["userid"], $action["maxrepeats"],$action["repeatdelay"]);
			}
		}
	}

	# Delete action from hardlinked hosts

	function	delete_action_from_templates($actionid)
	{
		if($actionid<=0)
		{
			return;
		}

		$action=get_action_by_actionid($actionid);
		$trigger=get_trigger_by_triggerid($action["triggerid"]);

		$sql="select distinct h.hostid from hosts h,functions f, items i where i.itemid=f.itemid and h.hostid=i.hostid and f.triggerid=".$action["triggerid"];
		$result=dbselect($sql);
		if(dbnum_rows($result)!=1)
		{
			return;
		}

		$row=dbfetch($result);

		$hostid=$row["hostid"];

		$sql="select hostid,templateid,actions from hosts_templates where templateid=$hostid";
		$result=dbselect($sql);
		#enumerate hosts
		while($row=dbfetch($result))
		{
			if($row["actions"]&4 == 0)	continue;

			$sql="select distinct f.triggerid from functions f,items i,triggers t where t.description='".addslashes($trigger["description"])."' and t.triggerid=f.triggerid and i.itemid=f.itemid and i.hostid=".$row["hostid"];
			$result2=dbselect($sql);
			#enumerate triggers
			while($row2=dbfetch($result2))
			{
				$sql="select actionid from actions where triggerid=".$row2["triggerid"]." and subject='".addslashes($action["subject"])."' and userid=".$action["userid"]." and good=".$action["good"]." and scope=".$action["scope"]." and recipient=".$action["recipient"]." and severity=".$action["severity"];
				$result3=dbselect($sql);
				#enumerate actions
				while($row3=dbfetch($result3))
				{
					delete_action($row3["actionid"]);
				}
			}
		}
	}

	# Update action from hardlinked hosts

	function	update_action_from_linked_hosts($actionid)
	{
		if($actionid<=0)
		{
			return;
		}

		$action=get_action_by_actionid($actionid);
		$trigger=get_trigger_by_triggerid($action["triggerid"]);

		$sql="select distinct h.hostid from hosts h,functions f, items i where i.itemid=f.itemid and h.hostid=i.hostid and f.triggerid=".$action["triggerid"];
		$result=dbselect($sql);
		if(dbnum_rows($result)!=1)
		{
			return;
		}

		$row=dbfetch($result);

		$hostid=$row["hostid"];
		$host_template=get_host_by_hostid($hostid);

		$sql="select hostid,templateid,actions from hosts_templates where templateid=$hostid";
		$result=dbselect($sql);
		#enumerate hosts
		while($row=dbfetch($result))
		{
			if($row["actions"]&2 == 0)	continue;

			$sql="select distinct f.triggerid from functions f,items i,triggers t where t.description='".addslashes($trigger["description"])."' and t.triggerid=f.triggerid and i.itemid=f.itemid and i.hostid=".$row["hostid"];
			$result2=dbselect($sql);
			#enumerate triggers
			while($row2=dbfetch($result2))
			{
				$sql="select actionid from actions where triggerid=".$row2["triggerid"]." and subject='".addslashes($action["subject"])."'";
				$result3=dbselect($sql);
				#enumerate actions
				while($row3=dbfetch($result3))
				{
					$host=get_host_by_hostid($row["hostid"]);
					$message=str_replace("{".$host_template["host"].":", "{".$host["host"].":", $action["message"]);
					update_action($row3["actionid"], $row2["triggerid"], $action["userid"], $action["good"], $action["delay"], $action["subject"], $message, $action["scope"], $action["severity"], $action["recipient"], $action["userid"], $action["maxrepeats"],$action["repeatdelay"]);

				}
			}
		}
	}

	function	get_source_description($source)
	{
		$desc="Unknown";
		if($source==1)
		{
			$desc="IT Service";
		}
		elseif($source==0)
		{
			$desc="Trigger";
		}
		return $desc;
	}

	function	get_condition_desc($conditiontype, $operator, $value)
	{
		if($operator == CONDITION_OPERATOR_EQUAL)
		{
			$op="=";
		}
		else if($operator == CONDITION_OPERATOR_NOT_EQUAL)
		{
			$op="<>";
		}

		$desc=S_UNKNOWN;
		if($conditiontype==CONDITION_TYPE_GROUP)
		{
			$group=get_group_by_groupid($value);
			if($group) $desc=S_HOST_GROUP." $op "."\"".$group["name"]."\"";
		}
		else if($conditiontype==CONDITION_TYPE_TRIGGER_NAME)
		{
			$desc=S_TRIGGER_DESCRIPTION." $op "."\"".$value."\"";
		}
		else
		{
		}
		return $desc;
	}

	# Add Action's condition

	function	add_action_condition($actionid, $conditiontype, $operator, $value)
	{
		$value=addslashes($value);
		$sql="insert into conditions (actionid,conditiontype,operator,value) values ($actionid,$conditiontype,$operator,'$value')";
		$result=DBexecute($sql);
		return DBinsert_id($result,"conditions","conditionid");
	}
?>
