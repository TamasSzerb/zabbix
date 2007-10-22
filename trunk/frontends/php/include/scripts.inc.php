<?php

function get_script_by_scriptid($scriptid){
	$sql = 'SELECT * FROM scripts WHERE scriptid='.$scriptid;
	
	$rows = false;
	if($res = DBSelect($sql)){
		$rows = DBfetch($res);
	}
return $rows;
}

function add_script($name,$command,$access){
	$scriptid = get_dbid('scripts','scriptid');
	$sql = 'INSERT INTO scripts (scriptid,name,command,host_access) '.
				" VALUES ('$scriptid','$name',".zbx_dbstr($command).",$access)";
	$result = DBexecute($sql);
	if($result){
		$result = $scriptid;
	}
return $result;
}

function delete_script($scriptid){
	$sql = 'DELETE FROM scripts WHERE scriptid='.$scriptid;
	$result = DBexecute($sql);
return $result;
}

function update_script($scriptid,$name,$command,$access){

	$sql = 'UPDATE scripts SET '.
				' name='.zbx_dbstr($name).
				' ,command='.zbx_dbstr($command).
				' ,host_access='.$access.
			' WHERE scriptid='.$scriptid;
			
	$result = DBexecute($sql);
return $result;
}

function execute_script($scriptid,$hostid){
	$res = array();
	$res["flag"]=1;

	$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	if(!$socket)
	{
		$res["flag"] = 1;
	}
	if($res)
	{
		$res = socket_connect($socket, '127.0.0.1', 10051);
	}
	if($res)
	{
		$send = "Command\255$scriptid\255$hostid\n";
		socket_write($socket,$send);
	}
	if($res)
	{
		$res = socket_read($socket,65535);
	}
	if($res)
	{
		list($flag,$msg)=split("\255",$res);
		$message["flag"]=$flag;
		$message["message"]=$msg;
	}
	else
	{
		$message = socket_strerror(socket_last_error());
	}
return $message;
}


function get_accessible_scripts_by_hosts($hosts){
	global $USER_DETAILS;
	
	if(!is_array($hosts)){
		$hosts = array('0' => hosts);
	}
	
	$hosts_read_only  = explode(',',get_accessible_hosts_by_user($USER_DETAILS,PERM_READ_ONLY,null,null,get_current_nodeid()));
	$hosts_read_write = explode(',',get_accessible_hosts_by_user($USER_DETAILS,PERM_READ_WRITE,null,null,get_current_nodeid()));

	$scripts_by_host = array();
	
	$sql = 'SELECT * FROM scripts '.
			' WHERE '.DBin_node('scriptid').
			' ORDER BY scriptid ASC';
	
	$res=DBselect($sql);
	
	while($script = DBfetch($res)){
		foreach($hosts as $id => $hostid){
			if($script['host_access'] == SCRIPT_HOST_ACCESS_WRITE){
				if(in_array($hostid,$hosts_read_write)){
					$scripts_by_host[$hostid][] = $script;
				}
			}
			else{
				if(in_array($hostid,$hosts_read_only)){
					$scripts_by_host[$hostid][] = $script;
				}
			}
		}
	}

return $scripts_by_host;
}
?>
