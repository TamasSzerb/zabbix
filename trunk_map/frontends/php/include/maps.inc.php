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
	require_once('include/images.inc.php');
	require_once('include/hosts.inc.php');
	require_once('include/triggers.inc.php');
	require_once('include/scripts.inc.php');

/*
 * Function: map_link_drawtypes
 *
 * Description:
 *     Return available drawing types for links
 *
 * Author:
 *     Eugene Grigorjev
 *
 */
	function map_link_drawtypes(){
		return array(
				MAP_LINK_DRAWTYPE_LINE,
				MAP_LINK_DRAWTYPE_BOLD_LINE,
				(function_exists('imagesetstyle') ? MAP_LINK_DRAWTYPE_DOT : null),
				MAP_LINK_DRAWTYPE_DASHED_LINE
			    );
	}

/*
 * Function: map_link_drawtype2str
 *
 * Description:
 *     Represent integer value of links drawing type into the string
 *
 * Author:
 *     Eugene Grigorjev
 *
 */
	function map_link_drawtype2str($drawtype){
		switch($drawtype){
			case MAP_LINK_DRAWTYPE_LINE:		$drawtype = S_LINE;			break;
			case MAP_LINK_DRAWTYPE_BOLD_LINE:	$drawtype = S_BOLD_LINE;	break;
			case MAP_LINK_DRAWTYPE_DOT:			$drawtype = S_DOT;			break;
			case MAP_LINK_DRAWTYPE_DASHED_LINE:	$drawtype = S_DASHED_LINE;	break;
			default: $drawtype = S_UNKNOWN;		break;
		}
	return $drawtype;
	}

/*
 * Function: sysmap_accessible
 *
 * Description: Check permission for map
 *
 * Return: true on success
 *
 * Author: Aly
 */
	function sysmap_accessible($sysmapid,$perm){
		global $USER_DETAILS;

		$nodes = get_current_nodeid(null,$perm);
		$result = (bool) count($nodes);

		$sql = 'SELECT * '.
				' FROM sysmaps_elements '.
				' WHERE sysmapid='.$sysmapid.
					' AND '.DBin_node('sysmapid', $nodes);
		$db_result = DBselect($sql);
		$available_hosts = get_accessible_hosts_by_user($USER_DETAILS,$perm,PERM_RES_IDS_ARRAY,get_current_nodeid(true));
//SDI($available_hosts);
		while(($se_data = DBfetch($db_result)) && $result){
			switch($se_data['elementtype']){
				case SYSMAP_ELEMENT_TYPE_HOST:
					if(!isset($available_hosts[$se_data['elementid']])){
						$result = false;
					}
					break;
				case SYSMAP_ELEMENT_TYPE_MAP:
					$result = sysmap_accessible($se_data['elementid'], $perm);
					break;
				case SYSMAP_ELEMENT_TYPE_TRIGGER:
					$available_triggers = get_accessible_triggers($perm, array(), PERM_RES_IDS_ARRAY);
					if(!isset($available_triggers[$se_data['elementid']])){
						$result = false;
					}
					break;
				case SYSMAP_ELEMENT_TYPE_HOST_GROUP:
					$available_groups = get_accessible_groups_by_user($USER_DETAILS,$perm);
					if(!isset($available_groups[$se_data['elementid']])){
						$result = false;
					}
					break;
			}
		}
//SDI($se_data['elementid']);

	return $result;
	}

	function get_sysmap_by_sysmapid($sysmapid){
		$row = DBfetch(DBselect('SELECT * FROM sysmaps WHERE sysmapid='.$sysmapid));
		if($row){
			return	$row;
		}
		error('No system map with sysmapid=['.$sysmapid.']');
		return false;
	}

	function get_sysmaps_element_by_selementid($selementid){
		$sql='select * FROM sysmaps_elements WHERE selementid='.$selementid;
		$result=DBselect($sql);
		$row=DBfetch($result);
		if($row){
			return	$row;
		}
		else{
			error('No sysmap element with selementid=['.$selementid.']');
		}
	return	$result;
	}

// Add System Map

	function add_sysmap($name,$width,$height,$backgroundid,$label_type,$label_location){
		$sysmapid=get_dbid('sysmaps','sysmapid');

		$result=DBexecute('insert into sysmaps (sysmapid,name,width,height,backgroundid,label_type,label_location)'.
			" values ($sysmapid,".zbx_dbstr($name).",$width,$height,".$backgroundid.",$label_type,$label_location)");

		if(!$result)
			return $result;

	return $sysmapid;
	}

// Update System Map

	function update_sysmap($sysmapid,$name,$width,$height,$backgroundid,$label_type,$label_location){
		return	DBexecute('update sysmaps set name='.zbx_dbstr($name).",width=$width,height=$height,".
			"backgroundid=".$backgroundid.",label_type=$label_type,".
			"label_location=$label_location WHERE sysmapid=$sysmapid");
	}

// Delete System Map

	function delete_sysmap($sysmapids){
		zbx_value2array($sysmapids);

		$result = delete_sysmaps_elements_with_sysmapid($sysmapids);
		if(!$result)	return	$result;

		$res=DBselect('SELECT linkid FROM sysmaps_links WHERE '.DBcondition('sysmapid',$sysmapids));
		while($rows = DBfetch($res)){
			$result&=delete_link($rows['linkid']);
		}

		$result = DBexecute('DELETE FROM sysmaps_elements WHERE '.DBcondition('sysmapid',$sysmapids));
		$result &= DBexecute("DELETE FROM profiles WHERE idx='web.favorite.sysmapids' AND source='sysmapid' AND ".DBcondition('value_id',$sysmapids));
		$result &= DBexecute('DELETE FROM screens_items WHERE '.DBcondition('resourceid',$sysmapids).' AND resourcetype='.SCREEN_RESOURCE_MAP);
		$result &= DBexecute('DELETE FROM sysmaps WHERE '.DBcondition('sysmapid',$sysmapids));

	return $result;
	}

// LINKS

	function add_link($sysmapid,$selementid1,$selementid2,$triggers,$drawtype,$color){
		$linkid=get_dbid("sysmaps_links","linkid");

		$result=TRUE;
		foreach($triggers as $id => $trigger){
			if(empty($trigger['triggerid'])) continue;
			$result&=add_link_trigger($linkid,$trigger['triggerid'],$trigger['drawtype'],$trigger['color']);
		}

		if(!$result){
			return $result;
		}

		$result&=DBexecute("insert into sysmaps_links".
			" (linkid,sysmapid,selementid1,selementid2,drawtype,color)".
			" values ($linkid,$sysmapid,$selementid1,$selementid2,$drawtype,".zbx_dbstr($color).")");

		if(!$result)
			return $result;

	return $linkid;
	}

	function update_link($linkid,$sysmapid,$selementid1,$selementid2,$triggers,$drawtype,$color){

		$result=delete_all_link_triggers($linkid);;

		foreach($triggers as $id => $trigger){
			if(empty($trigger['triggerid'])) continue;
			$result&=add_link_trigger($linkid,$trigger['triggerid'],$trigger['drawtype'],$trigger['color']);
		}

		if(!$result){
			return $result;
		}

		$result&=DBexecute('UPDATE sysmaps_links SET '.
							" sysmapid=$sysmapid,selementid1=$selementid1,selementid2=$selementid2,".
							" drawtype=$drawtype,color=".zbx_dbstr($color).
						" WHERE linkid=$linkid");
	return	$result;
	}

	function delete_link($linkid){
		$result = delete_all_link_triggers($linkid);
		$result&= DBexecute("delete FROM sysmaps_links WHERE linkid=$linkid");
	return	$result;
	}

	function get_link_triggers($linkid){
		$triggers = array();

		$sql = 'SELECT * FROM sysmaps_link_triggers WHERE linkid='.$linkid;
		$res = DBselect($sql);

		while($rows = DBfetch($res)){
			$triggers[] = $rows;
		}
	return $triggers;
	}

	function add_link_trigger($linkid,$triggerid,$drawtype,$color){
		$linktriggerid=get_dbid("sysmaps_link_triggers","linktriggerid");
		$sql = 'INSERT INTO sysmaps_link_triggers (linktriggerid,linkid,triggerid,drawtype,color) '.
					" VALUES ($linktriggerid,$linkid,$triggerid,$drawtype,".zbx_dbstr($color).')';
	return DBexecute($sql);
	}

	function update_link_trigger($linkid,$triggerid,$drawtype,$color){
		$result=delete_link_trigger($linkid,$triggerid);
		$result&=add_link_trigger($linkid,$triggerid,$drawtype,$color);
	return $result;
	}

	function delete_link_trigger($linkid,$triggerid){
	return DBexecute('DELETE FROM sysmaps_link_triggers WHERE linkid='.$linkid.' AND triggerid='.$triggerid);
	}

	function delete_all_link_triggers($linkid){
	return DBexecute('DELETE FROM sysmaps_link_triggers WHERE linkid='.$linkid);
	}

/*
 * Function: check_circle_elements_link
 *
 * Description:
 *     Check for circular map creation
 *
 * Author:
 *     Eugene Grigorjev
 *
 */
	function check_circle_elements_link($sysmapid,$elementid,$elementtype){
		if($elementtype!=SYSMAP_ELEMENT_TYPE_MAP)	return false;

		if(bccomp($sysmapid ,$elementid)==0)	return TRUE;

		$db_elements = DBselect('SELECT elementid, elementtype '.
						' FROM sysmaps_elements '.
						' WHERE sysmapid='.$elementid);

		while($element = DBfetch($db_elements))
		{
			if(check_circle_elements_link($sysmapid,$element["elementid"],$element["elementtype"]))
				return TRUE;
		}
		return false;
	}

	# Add Element to system map

	function add_element_to_sysmap($sysmapid,$elementid,$elementtype,
						$label,$x,$y,$iconid_off,$iconid_unknown,$iconid_on,$iconid_disabled,$url,$label_location)
	{
		if($label_location<0) $label_location='null';
		if(check_circle_elements_link($sysmapid,$elementid,$elementtype))
		{
			error("Circular link can't be created");
			return false;
		}

		$selementid = get_dbid("sysmaps_elements","selementid");

		$result=DBexecute('INSERT INTO sysmaps_elements '.
							" (selementid,sysmapid,elementid,elementtype,label,x,y,iconid_off,url,iconid_on,label_location,iconid_unknown,iconid_disabled)".
						" VALUES ($selementid,$sysmapid,$elementid,$elementtype,".zbx_dbstr($label).
							",$x,$y,$iconid_off,".zbx_dbstr($url).
							",$iconid_on,$label_location,$iconid_unknown,$iconid_disabled)");

		if(!$result)
			return $result;

		return $selementid;
	}

	# Update Element FROM system map

	function update_sysmap_element($selementid,$sysmapid,$elementid,$elementtype,
						$label,$x,$y,$iconid_off,$iconid_unknown,$iconid_on,$iconid_disabled,$url,$label_location)
	{
		if($label_location<0) $label_location='null';
		if(check_circle_elements_link($sysmapid,$elementid,$elementtype))
		{
			error("Circular link can't be created");
			return false;
		}

		return	DBexecute('UPDATE sysmaps_elements '.
					"SET elementid=$elementid,elementtype=$elementtype,".
						"label=".zbx_dbstr($label).",x=$x,y=$y,iconid_off=$iconid_off,".
						"url=".zbx_dbstr($url).",iconid_on=$iconid_on,".
						"label_location=$label_location,iconid_unknown=$iconid_unknown,".
						"iconid_disabled=$iconid_disabled".
					" WHERE selementid=$selementid");
	}

	/******************************************************************************
	 *                                                                            *
	 * Purpose: Delete Element FROM sysmap definition                             *
	 *                                                                            *
	 * Comments: !!! Don't forget sync code with C !!!                            *
	 *                                                                            *
	 ******************************************************************************/
	function delete_sysmaps_element($selementids){
		zbx_value2array($selementids);
		if(empty($selementids)) return true;

		$result=TRUE;
		$sql = 'SELECT linkid FROM sysmaps_links '.
				' WHERE '.DBcondition('selementid1',$selementids).
					' OR '.DBcondition('selementid2',$selementids);

		$res=DBselect($sql);
		while($rows = DBfetch($res)){
			$result&=delete_link($rows['linkid']);
		}
//		$result=DBexecute('DELETE FROM sysmaps_links WHERE selementid1=$selementid OR selementid2=$selementid');

		if(!$result) return	$result;

	return	DBexecute('DELETE FROM sysmaps_elements WHERE '.DBcondition('selementid',$selementids));
	}

	/******************************************************************************
	 *                                                                            *
	 * Comments: !!! Don't forget sync code with C !!!                            *
	 *                                                                            *
	 ******************************************************************************/
	function delete_sysmaps_elements_with_hostid($hostids){
		zbx_value2array($hostids);
		if(empty($hostids)) return true;

		$db_elements = DBselect('SELECT selementid '.
					' FROM sysmaps_elements '.
					' WHERE '.DBcondition('elementid',$hostids).
						' AND elementtype='.SYSMAP_ELEMENT_TYPE_HOST);

		$selementids = array();
		while($db_element = DBfetch($db_elements)){
			$selementids[$db_element['selementid']] = $db_element['selementid'];
		}
		delete_sysmaps_element($selementids);

	return TRUE;
	}

	function delete_sysmaps_elements_with_sysmapid($sysmapids){
		zbx_value2array($sysmapids);
		if(empty($sysmapids)) return true;

		$db_elements = DBselect('SELECT selementid '.
					' FROM sysmaps_elements '.
					' WHERE '.DBcondition('elementid',$sysmapids).
						' AND elementtype='.SYSMAP_ELEMENT_TYPE_MAP);
		$selementids = array();
		while($db_element = DBfetch($db_elements)){
			$selementids[$db_element['selementid']] = $db_element['selementid'];
		}
		delete_sysmaps_element($selementids);

	return TRUE;
	}

/******************************************************************************
 *                                                                            *
 * Comments: !!! Don't forget sync code with C !!!                            *
 *                                                                            *
 ******************************************************************************/
	function delete_sysmaps_elements_with_triggerid($triggerids){
		zbx_value2array($triggerids);
		if(empty($triggerids)) return true;

		$db_elements = DBselect('SELECT selementid '.
					' FROM sysmaps_elements '.
					' WHERE '.DBcondition('elementid',$triggerids).
						' AND elementtype='.SYSMAP_ELEMENT_TYPE_TRIGGER);
		$selementids = array();
		while($db_element = DBfetch($db_elements)){
			$selementids[$db_element['selementid']] = $db_element['selementid'];
		}
		delete_sysmaps_element($selementids);
	return TRUE;
	}

	function delete_sysmaps_elements_with_groupid($groupids){
		zbx_value2array($groupids);
		if(empty($groupids)) return true;

		$db_elements = DBselect('SELECT selementid '.
						' FROM sysmaps_elements '.
						' WHERE '.DBcondition('elementid',$groupids).
							' AND elementtype='.SYSMAP_ELEMENT_TYPE_HOST_GROUP);
		$selementids = array();
		while($db_element = DBfetch($db_elements)){
			$selementids[$db_element['selementid']] = $db_element['selementid'];
		}
		delete_sysmaps_element($selementids);

	return TRUE;
	}

	function get_info_by_selementid($selementid,$view_status=0){
		$db_element = get_sysmaps_element_by_selementid($selementid);
		$info = get_info_by_selement($db_element,$view_status);
		
	return $info;
	}

/*
 * Function: get_info_by_selement
 *
 * Description:
 *     Retrive selement 
 *
 * Author:
 *     Aly
 *
 */
	function get_info_by_selement($selement,$view_status=0){
		global $colors;
		$config=select_config();
		
		$el_name = '';
		$out = array();
		$tr_info = array();
		$maintenance = array('status'=>false, 'maintenanceid'=>0);
				
		$el_type = &$selement['elementtype'];

		$sql = array(
			SYSMAP_ELEMENT_TYPE_TRIGGER => 'SELECT DISTINCT t.triggerid,t.priority,t.value,t.description '.
						',t.expression, t.type, h.host,h.status as h_status,i.status as i_status,t.status as t_status '.
				' FROM triggers t, items i, functions f, hosts h '.
				' WHERE t.triggerid='.$selement['elementid'].
					' AND h.hostid=i.hostid '.
					' AND i.itemid=f.itemid '.
					' AND f.triggerid=t.triggerid ',
			SYSMAP_ELEMENT_TYPE_HOST_GROUP => 'SELECT DISTINCT t.triggerid, t.priority, t.value, t.type, '.
						' t.description, t.expression, h.host, g.name as el_name '.
				' FROM items i,functions f,triggers t,hosts h,hosts_groups hg,groups g '.
				' WHERE h.hostid=i.hostid '.
					' AND hg.groupid=g.groupid '.
					' AND g.groupid='.$selement['elementid'].
					' AND hg.hostid=h.hostid '.
					' AND i.itemid=f.itemid '.
					' AND f.triggerid=t.triggerid '.
					' AND t.status='.TRIGGER_STATUS_ENABLED.
					' AND h.status='.HOST_STATUS_MONITORED.
					' AND i.status='.ITEM_STATUS_ACTIVE,
			SYSMAP_ELEMENT_TYPE_HOST => 'SELECT DISTINCT t.triggerid, t.priority, t.value, t.type, '.
						' t.description, t.expression, h.host, h.host as el_name, h.maintenanceid, h.maintenance_status '.
				' FROM items i,functions f,triggers t,hosts h '.
				' WHERE h.hostid=i.hostid '.
					' AND i.hostid='.$selement['elementid'].
					' AND i.itemid=f.itemid '.
					' AND f.triggerid=t.triggerid '.
					' AND t.status='.TRIGGER_STATUS_ENABLED.
					' AND h.status='.HOST_STATUS_MONITORED.
					' AND i.status='.ITEM_STATUS_ACTIVE
			);

		$out['triggers'] = array();

		if( isset($sql[$el_type]) ){
		
			$db_triggers = DBselect($sql[$el_type]);
			$trigger = DBfetch($db_triggers);
			
			if($trigger){
				if(isset($trigger['el_name'])){
					$el_name = $trigger['el_name'];
				}
				else if($el_type == SYSMAP_ELEMENT_TYPE_TRIGGER){
					$el_name = expand_trigger_description_by_data($trigger);
				}

				if(isset($trigger['maintenance_status']) && ($trigger['maintenance_status'] == MAINTENANCE_TYPE_NODATA)){
					$maintenance['status'] = true;
					$maintenance['maintenanceid'] = $trigger['maintenanceid'];
				}
				
				do{
					if ($el_type == SYSMAP_ELEMENT_TYPE_TRIGGER && (
							$trigger['h_status'] != HOST_STATUS_MONITORED ||
							$trigger['i_status'] != ITEM_STATUS_ACTIVE ||
							$trigger['t_status'] != TRIGGER_STATUS_ENABLED))
					{
						$type = TRIGGER_VALUE_UNKNOWN;
						$out['disabled'] = 1;
					}
					else
						$type =& $trigger['value'];

					if(!isset($tr_info[$type]))
						$tr_info[$type] = array('count' => 0);

					$tr_info[$type]['count']++;
					if(!isset($tr_info[$type]['priority']) || ($tr_info[$type]['priority'] < $trigger['priority'])){
					
						$tr_info[$type]['priority']	= $trigger['priority'];
						if(($el_type != SYSMAP_ELEMENT_TYPE_TRIGGER) && ($type!=TRIGGER_VALUE_UNKNOWN)){
							$tr_info[$type]['info']		= expand_trigger_description_by_data($trigger);
						}
					}

					if($type == TRIGGER_VALUE_TRUE){
						array_push($out['triggers'], $trigger['triggerid']);
					}
				} while ($trigger = DBfetch($db_triggers));
			}
		}
		else if($el_type==SYSMAP_ELEMENT_TYPE_MAP){
			$triggers = array();

			$sql = 'SELECT name FROM sysmaps WHERE sysmapid='.$selement['elementid'];
			$db_map = DBfetch(DBselect($sql));
			$el_name = $db_map['name'];

			$sql = 'SELECT selementid '.
					' FROM sysmaps_elements '.
					' WHERE sysmapid='.$selement['elementid'];
			$db_subselements = DBselect($sql);
			while($db_subselement = DBfetch($db_subselements)){
// recursion
				$inf = get_info_by_selementid($db_subselement['selementid']);

				foreach($inf['triggers'] as $id => $triggerid){
					$triggers[$triggerid] = $triggerid;
				}

				$type = $inf['type'];

				if(!isset($tr_info[$type]['count'])) $tr_info[$type]['count'] = 0;
				$tr_info[$type]['count'] += isset($inf['count']) ? $inf['count'] : 1;

				if(!isset($tr_info[$type]['priority']) || ($tr_info[$type]['priority'] < $inf['priority'])){
					$tr_info[$type]['priority'] = $inf['priority'];
					$tr_info[$type]['info'] = $inf['info'];
				}
			}

			$count = count($triggers);
			if ($count > 0){

				$tr_info[TRIGGER_VALUE_TRUE]['count'] = $count;

				if ($tr_info[TRIGGER_VALUE_TRUE]['count'] == 1){
					$tr1 = reset($triggers);
					$sql = 'SELECT DISTINCT t.triggerid,t.priority,t.value,t.description,t.expression,h.host '.
							' FROM triggers t, items i, functions f, hosts h'.
							' WHERE t.triggerid='.$tr1.
								' AND h.hostid=i.hostid'.
								' AND i.itemid=f.itemid '.
								' AND f.triggerid=t.triggerid';
					$db_trigger = DBfetch(DBselect($sql));
					$tr_info[TRIGGER_VALUE_TRUE]['info'] = expand_trigger_description_by_data($db_trigger);
				}
			}
		}

		if($el_type == SYSMAP_ELEMENT_TYPE_HOST){
			$host = get_host_by_hostid($selement['elementid']);
			$el_name = $host['host'];

			if($host['status'] == HOST_STATUS_TEMPLATE){
				$tr_info[TRIGGER_VALUE_UNKNOWN]['count']	= 0;
				$tr_info[TRIGGER_VALUE_UNKNOWN]['priority']	= 0;
				$tr_info[TRIGGER_VALUE_UNKNOWN]['info']		=  S_TEMPLATE_SMALL;
			}
			else if($host['status'] == HOST_STATUS_NOT_MONITORED){
				$tr_info[TRIGGER_VALUE_UNKNOWN]['count']	= 0;
				$tr_info[TRIGGER_VALUE_UNKNOWN]['priority']	= 0;
				$out['disabled'] = 1;
			}
			else if(!isset($tr_info[TRIGGER_VALUE_FALSE])){
				$tr_info[TRIGGER_VALUE_FALSE]['count']		= 0;
				$tr_info[TRIGGER_VALUE_FALSE]['priority']	= 0;
				$tr_info[TRIGGER_VALUE_FALSE]['info']		= S_OK_BIG;
			}
		}
		else if($el_type == SYSMAP_ELEMENT_TYPE_HOST_GROUP){
			$group = get_hostgroup_by_groupid($selement['elementid']);
			$el_name = $group['name'];

			if(!isset($tr_info[TRIGGER_VALUE_FALSE])){
				$tr_info[TRIGGER_VALUE_FALSE]['count']		= 0;
				$tr_info[TRIGGER_VALUE_FALSE]['priority']	= 0;
				$tr_info[TRIGGER_VALUE_FALSE]['info']		= S_OK_BIG;
			}
		}
		else if($el_type == SYSMAP_ELEMENT_TYPE_MAP){
			$db_map = DBfetch(DBselect('SELECT name FROM sysmaps WHERE sysmapid='.$selement['elementid']));
			$el_name = $db_map['name'];

			if(!isset($tr_info[TRIGGER_VALUE_FALSE])){
				$tr_info[TRIGGER_VALUE_FALSE]['count']		= 0;
				$tr_info[TRIGGER_VALUE_FALSE]['priority']	= 0;
				$tr_info[TRIGGER_VALUE_FALSE]['info']		= S_OK_BIG;
			}
		}

		if(isset($tr_info[TRIGGER_VALUE_TRUE])){
			$inf =& $tr_info[TRIGGER_VALUE_TRUE];

			$out['type'] = TRIGGER_VALUE_TRUE;
			$out['info'] = S_PROBLEM_BIG;

			if(($inf['count'] > 1) || ($view_status == 1))
				$out['info'] = $inf['count'].' problems';
			else if(isset($inf['info']))
				$out['info'] = $inf['info'];

			if(isset($inf['priority']) && $inf['priority'] > 3)
				$out['color'] = $colors['Red'];
			else
				$out['color'] = $colors['Dark Red'];

			$out['iconid'] = $selement['iconid_on'];
			$out['icon_type'] = SYSMAP_ELEMENT_ICON_ON;
		}
		else if(isset($tr_info[TRIGGER_VALUE_UNKNOWN]) && !isset($tr_info[TRIGGER_VALUE_FALSE])){
			$inf =& $tr_info[TRIGGER_VALUE_UNKNOWN];

			$out['type'] = TRIGGER_VALUE_UNKNOWN;
			$out['info'] = S_UNKNOWN_BIG;

			$out['color'] = $colors['Gray'];

			if(isset($out['disabled']) && $out['disabled'] == 1)
				$out['iconid'] = $selement['iconid_disabled'];
			else
				$out['iconid'] = $selement['iconid_unknown'];

			if(isset($inf['info']))
				$out['info'] = $inf['info'];
				
			$out['icon_type'] = SYSMAP_ELEMENT_ICON_UNKNOWN;
		}
		else if(isset($tr_info[TRIGGER_VALUE_FALSE])){
			$inf =& $tr_info[TRIGGER_VALUE_FALSE];

			$out['type'] = TRIGGER_VALUE_FALSE;
			$out['info'] = S_OK_BIG;

			if(isset($inf['info']))
				$out['info'] = S_OK_BIG;

			$out['color'] = $colors['Dark Green'];
			$out['iconid'] = $selement['iconid_off'];
			$out['icon_type'] = SYSMAP_ELEMENT_ICON_OFF;
		}
		else{
// UNDEFINED ELEMENT
			$inf['count'] = 0;
			$inf['priority'] = 0;
			
			$out['type'] = TRIGGER_VALUE_TRUE;
			$out['info'] = '';

			$out['color'] = $colors['Green'];

			$out['iconid'] = $selement['iconid_off'];
			$out['icon_type'] = SYSMAP_ELEMENT_ICON_OFF;
		}

// Host in maintenance
		if($maintenance['status']){
			$out['type'] = TRIGGER_VALUE_UNKNOWN;
			$out['info'] = S_IN_MAINTENANCE;
			if($maintenance['maintenanceid'] > 0){
				$mnt = get_maintenance_by_maintenanceid($maintenance['maintenanceid']);
				$out['info'].='['.$mnt['name'].']';
			}
			
			$out['color'] = $colors['Gray'];
			$out['iconid'] = $selement['iconid_maintenance'];
			$out['icon_type'] = SYSMAP_ELEMENT_ICON_MAINTENANCE;
		}
//---

// No label for Images
		if($el_type == SYSMAP_ELEMENT_TYPE_IMAGE){
			$out['info'] = '';
		}

		$out['count'] = $inf['count'];
		$out['priority'] = isset($inf['priority']) ? $inf['priority'] : 0;
		$out['name'] = $el_name;

	return $out;
	}

/*
 * Function: get_action_map_by_sysmapid
 *
 * Description:
 *     Retrive action for map element
 *
 * Author:
 *     Eugene Grigorjev
 *
 */
	function get_action_map_by_sysmapid($sysmapid){
		$action_map = new CAreaMap('links'.$sysmapid);

		$sql = 'SELECT * FROM sysmaps_elements WHERE sysmapid='.$sysmapid;
		$db_elements = DBselect($sql);
		while($db_element = DBfetch($db_elements)){
			$url = $db_element['url'];
			$alt = 'Label: '.$db_element['label'];
			$scripts_by_hosts = null;

			if($db_element['elementtype'] == SYSMAP_ELEMENT_TYPE_HOST){
				$host = get_host_by_hostid($db_element['elementid']);
				if($host['status'] != HOST_STATUS_MONITORED)	continue;

				$scripts_by_hosts = CScript::getScriptsByHosts(array($db_element['elementid']));

				if(empty($url))	$url='tr_status.php?hostid='.$db_element['elementid'].'&noactions=true&onlytrue=true&compact=true';

				$alt = 'Host: '.$host['host'].' '.$alt;
			}
			else if($db_element['elementtype'] == SYSMAP_ELEMENT_TYPE_MAP){
				$map = get_sysmap_by_sysmapid($db_element['elementid']);

				if(empty($url))
					$url='maps.php?sysmapid='.$db_element['elementid'];

				$alt = 'Host: '.$map['name'].' '.$alt;
			}
			else if($db_element['elementtype'] == SYSMAP_ELEMENT_TYPE_TRIGGER){
				if(empty($url) && $db_element['elementid']!=0)
					$url='events.php?triggerid='.$db_element['elementid'];
			}
			else if($db_element['elementtype'] == SYSMAP_ELEMENT_TYPE_HOST_GROUP){
				if(empty($url) && $db_element['elementid']!=0)
					$url='events.php?hostid=0&groupid='.$db_element['elementid'];
			}

			if(empty($url))	continue;

			$back = get_png_by_selementid($db_element['selementid']);
			if(!$back)	continue;

			$x1_ = $db_element['x'];
			$y1_ = $db_element['y'];
			$x2_ = $db_element['x'] + imagesx($back);
			$y2_ = $db_element['y'] + imagesy($back);

			$r_area = new CArea(array($x1_,$y1_,$x2_,$y2_),$url,$alt,'rect');
			if(!empty($scripts_by_hosts)){
				$menus = '';

				$host_nodeid = id2nodeid($db_element['elementid']);
				foreach($scripts_by_hosts[$db_element['elementid']] as $id => $script){
					$script_nodeid = id2nodeid($script['scriptid']);
					if( (bccomp($host_nodeid ,$script_nodeid ) == 0))
						$menus.= "['".$script['name']."',\"javascript: openWinCentered('scripts_exec.php?execute=1&hostid=".$db_element["elementid"]."&scriptid=".$script['scriptid']."','".S_TOOLS."',760,540,'titlebar=no, resizable=yes, scrollbars=yes, dialog=no');\", null,{'outer' : ['pum_o_item'],'inner' : ['pum_i_item']}],";
				}

				$menus.= '['.zbx_jsvalue(S_LINKS).",null,null,{'outer' : ['pum_oheader'],'inner' : ['pum_iheader']}],";

				$menus.= "['".S_STATUS_OF_TRIGGERS."',\"javascript: redirect('tr_status.php?groupid=0&hostid=".$db_element['elementid']."&noactions=true&onlytrue=true&compact=true');\", null,{'outer' : ['pum_o_item'],'inner' : ['pum_i_item']}],";

				if(!empty($db_element['url'])){
					$menus.= "['".S_MAP.SPACE.S_URL."',\"javascript: location.replace('".$url."');\", null,{'outer' : ['pum_o_item'],'inner' : ['pum_i_item']}],";
				}

				$menus = trim($menus,',');
				$menus="show_popup_menu(event,[[".zbx_jsvalue(S_TOOLS).",null,null,{'outer' : ['pum_oheader'],'inner' : ['pum_iheader']}],".$menus."],180); cancelEvent(event);";

				$r_area->AddAction('onclick','javascript: '.$menus);
			}
			$action_map->AddItem($r_area);//AddRectArea($x1_,$y1_,$x2_,$y2_, $url, $alt);
		}

		$jsmenu = new CPUMenu(null,170);
		$jsmenu->InsertJavaScript();
		return $action_map;
	}

	function get_icon_center_by_selementid($selementid){
		$element = get_sysmaps_element_by_selementid($selementid);
	return get_icon_center_by_selement($element);
	}
	
	function get_icon_center_by_selement($element){

		$x = $element['x'];
		$y = $element['y'];

		$image = get_png_by_selement($element);
		if($image){
			$x += imagesx($image) / 2;
			$y += imagesy($image) / 2;
		}

	return array($x, $y);
	}

	function MyDrawLine($image,$x1,$y1,$x2,$y2,$color,$drawtype){
		if($drawtype == MAP_LINK_DRAWTYPE_BOLD_LINE){
			imageline($image,$x1,$y1,$x2,$y2,$color);
			if(abs($x1-$x2) < abs($y1-$y2)){
				$x1++;
				$x2++;
			}
			else{
				$y1++;
				$y2++;
			}

			imageline($image,$x1,$y1,$x2,$y2,$color);
		}
		else if($drawtype == MAP_LINK_DRAWTYPE_DASHED_LINE){
			if(function_exists('imagesetstyle')){
/* Use imagesetstyle+imageline instead of bugged ImageDashedLine */
				$style = array(
					$color, $color, $color, $color,
					IMG_COLOR_TRANSPARENT, IMG_COLOR_TRANSPARENT, IMG_COLOR_TRANSPARENT, IMG_COLOR_TRANSPARENT
					);

				imagesetstyle($image, $style);
				imageline($image,$x1,$y1,$x2,$y2,IMG_COLOR_STYLED);
			}
			else{
				ImageDashedLine($image,$x1,$y1,$x2,$y2,$color);
			}
		}
		else if($drawtype == MAP_LINK_DRAWTYPE_DOT && function_exists('imagesetstyle')){
			$style = array($color,IMG_COLOR_TRANSPARENT, IMG_COLOR_TRANSPARENT, IMG_COLOR_TRANSPARENT);
			imagesetstyle($image, $style);
			imageline($image,$x1,$y1,$x2,$y2,IMG_COLOR_STYLED);
		}
		else{
			imageline($image,$x1,$y1,$x2,$y2,$color);
		}
	}

	function get_png_by_selementid($selementid){
		$selement = DBfetch(DBselect('SELECT * FROM sysmaps_elements WHERE selementid='.$selementid));
		if(!$element)	return FALSE;

	return get_png_by_selement($selement);
	}
	
	function get_png_by_selement($selement){
		$info = get_info_by_selement($selement);
		
//SDII($selement); exit;

		switch($info['icon_type']){
			case SYSMAP_ELEMENT_ICON_ON:
				$info['iconid'] = $selement['iconid_on'];
				break;
			case SYSMAP_ELEMENT_ICON_UNKNOWN:
				$info['iconid'] = $selement['iconid_unknown'];
				break;
			case SYSMAP_ELEMENT_ICON_MAINTENANCE:
				$info['iconid'] = $selement['iconid_maintenance'];
				break;
			case SYSMAP_ELEMENT_ICON_OFF:
			default:
// element image
				$info['iconid'] = $selement['iconid_off'];
				break;
		}

		$image = get_image_by_imageid($info['iconid']);

		if(!$image){
			return FALSE;
		}

	return imagecreatefromstring($image['image']);
	}
	
	function get_base64_icon($element){	
		return base64_encode(get_element_icon($element));
	}

	function get_selement_iconid($selement){
		if($selement['selementid'] > 0){
			$info = get_info_by_selement($selement);
//SDI($info);
			switch($info['icon_type']){
				case SYSMAP_ELEMENT_ICON_ON:
					$info['iconid'] = $selement['iconid_on'];
					break;
				case SYSMAP_ELEMENT_ICON_OFF:
					$info['iconid'] = $selement['iconid_off'];
					break;
				case SYSMAP_ELEMENT_ICON_UNKNOWN:
					$info['iconid'] = $selement['iconid_unknown'];
					break;
				case SYSMAP_ELEMENT_ICON_MAINTENANCE:
					$info['iconid'] = $selement['iconid_maintenance'];
					break;
			}
		}
		else{
			$info['iconid'] = $selement['iconid_off'];
		}
		
	return $info['iconid'];
	}
	
	function get_element_icon($element){
		$iconid = get_element_iconid($element);
		
		$image = get_image_by_imageid($iconid);
		$img = imagecreatefromstring($image['image']);
		
		unset($image);
		
		$w=imagesx($img); 
		$h=imagesy($img);
		
		if(function_exists('imagecreatetruecolor') && @imagecreatetruecolor(1,1)){
			$im = imagecreatetruecolor($w,$h);
		}
		else{
			$im = imagecreate($w,$h);
		}

		imagefilledrectangle($im,0,0,$w,$h, imagecolorallocate($im,255,255,255));

		imagecopy($im,$img,0,0,0,0,$w,$h);
		imagedestroy($img);
		
		ob_start();
		imagepng($im);
		$image_txt = ob_get_contents();
		ob_end_clean();
		
	return $image_txt;
	}
	
	function get_selement_form_menu(){
		global $USER_DETAILS;

		$menu = '';
		$cmapid = get_request('favid',0);
		
		$el_menu = array(
				array('form_key'=>'elementtype',		'value'=> S_TYPE),				
				array('form_key'=>'label', 				'value'=> S_LABEL),
				array('form_key'=>'label_location', 	'value'=> S_LABEL_LOCATION),
				array('form_key'=>'iconid_off',	 		'value'=> S_ICON_OK),
				array('form_key'=>'iconid_on',	 		'value'=> S_ICON_PROBLEM),
				array('form_key'=>'iconid_unknown',	 	'value'=> S_ICON_UNKNOWN),
				array('form_key'=>'iconid_maintenance',	'value'=> S_ICON_MAINTENANCE),
				array('form_key'=>'iconid_disabled',	'value'=> S_ICON_DISABLED),
				array('form_key'=>'url', 				'value'=> S_URL),
			);
		
		$menu.= 'var zbx_selement_menu = '.zbx_jsvalue($el_menu).';'."\n";
		
		$el_form_menu = array();
// Element type
		$el_form_menu['elementtype'] = array();

		$el_form_menu['elementtype'][] = array('key'=> SYSMAP_ELEMENT_TYPE_HOST,	'value'=> S_HOST);

		$db_maps = DBselect('SELECT sysmapid FROM sysmaps WHERE sysmapid!='.$_REQUEST['sysmapid']);
		if(DBfetch($db_maps))
			$el_form_menu['elementtype'][] = array('key'=> SYSMAP_ELEMENT_TYPE_MAP,	'value'=> S_MAP);

		$el_form_menu['elementtype'][] = array('key'=> SYSMAP_ELEMENT_TYPE_TRIGGER,		'value'=> S_TRIGGER);
		$el_form_menu['elementtype'][] = array('key'=> SYSMAP_ELEMENT_TYPE_HOST_GROUP,	'value'=> S_HOST_GROUP);
		$el_form_menu['elementtype'][] = array('key'=> SYSMAP_ELEMENT_TYPE_IMAGE,		'value'=> S_IMAGE);
		$el_form_menu['elementtype'][] = array('key'=> SYSMAP_ELEMENT_TYPE_UNDEFINED,	'value'=> S_UNDEFINED);
		

// ELEMENTID by TYPE
		$el_form_menu['elementid'] = array();
// HOST		
		$host_link = new CLink(S_SELECT);
		$host_link->addAction('onclick',"return PopUp('popup.php?dstfrm=".'FORM'.
								"&dstfld1=elementid&dstfld2=host&srctbl=hosts&srcfld1=hostid&srcfld2=host',450,450);");
		$el_form_menu['hostid_hosts'][] = array('key'=>SYSMAP_ELEMENT_TYPE_HOST, 'value'=> unpack_object($host_link));
// MAP
		$maps = array();
		$db_maps = DBselect('SELECT DISTINCT n.name as node_name,s.sysmapid,s.name '.
							' FROM sysmaps s '.
								' LEFT JOIN nodes n on n.nodeid='.DBid2nodeid('s.sysmapid').
							' ORDER BY node_name,s.name');
		while($db_map = DBfetch($db_maps)){
			if(!sysmap_accessible($db_map['sysmapid'],PERM_READ_ONLY)) continue;
			
			$node_name = isset($db_map['node_name']) ? '('.$db_map['node_name'].') ' : '';
			$maps[] = array($db_map['sysmapid'],$node_name.$db_map['name']);
		}
		$el_form_menu['sysmapid_sysmaps'][] = array('key'=>SYSMAP_ELEMENT_TYPE_MAP, 'value'=> $maps);
		
// TRIGGER
		$trigger_link = new CLink(S_SELECT);
		$trigger_link->addAction('onclick',"return PopUp('popup.php?dstfrm=".'FORM'.
					"&dstfld1=elementid&dstfld2=trigger&srctbl=triggers&srcfld1=triggerid&srcfld2=description');");
		$el_form_menu['triggerid_triggers'][] = array('key'=>SYSMAP_ELEMENT_TYPE_TRIGGER, 'value'=> unpack_object($trigger_link));
		
// HOST GROUP
		$hg_link = new CLink(S_SELECT);
		$hg_link->addAction('onclick',"return PopUp('popup.php?dstfrm=".'FORM'.
					"&dstfld1=elementid&dstfld2=group&srctbl=host_group&srcfld1=groupid&srcfld2=name',450,450);");
		$el_form_menu['groupid_host_group'][] = array('key'=>SYSMAP_ELEMENT_TYPE_HOST_GROUP, 'value'=> unpack_object($hg_link));

// LABEL
		$el_form_menu['label'][] = array('key'=> 'unknown',	'value'=> 'unknown');
		

// LABEL Location
		$el_form_menu['label_location'] = array();
		
		$el_form_menu['label_location'][] = array('key'=> -1, 'value'=> '-');
		$el_form_menu['label_location'][] = array('key'=> 0, 'value'=> S_BOTTOM);
		$el_form_menu['label_location'][] = array('key'=> 1, 'value'=> S_LEFT);
		$el_form_menu['label_location'][] = array('key'=> 2, 'value'=> S_RIGHT);
		$el_form_menu['label_location'][] = array('key'=> 3, 'value'=> S_TOP);
// ICONS 
		$el_form_menu['iconid_off'] = array();
		$el_form_menu['iconid_on'] = array();
		$el_form_menu['iconid_unknown'] = array();
		$el_form_menu['iconid_maintenance'] = array();
		$el_form_menu['iconid_disabled'] = array();
		
		$result = DBselect('SELECT * FROM images WHERE imagetype=1 AND '.DBin_node('imageid').' ORDER BY name');
		while($row=DBfetch($result)){
			$row['name'] = get_node_name_by_elid($row['imageid']).$row['name'];
			$el_form_menu['iconid_off'][] = array('key'=>$row['imageid'], 'value'=>$row['name']);
			$el_form_menu['iconid_on'][] = array('key'=>$row['imageid'], 'value'=>$row['name']);
			$el_form_menu['iconid_unknown'][] = array('key'=>$row['imageid'], 'value'=>$row['name']);
			$el_form_menu['iconid_maintenance'][] = array('key'=>$row['imageid'], 'value'=>$row['name']);
			$el_form_menu['iconid_disabled'][] = array('key'=>$row['imageid'], 'value'=>$row['name']);
		}
		
// URL
		$el_form_menu['url'][] = array('key'=> '',	'value'=> '');

		$menu.= 'var zbx_selement_form_menu = '.zbx_jsvalue($el_form_menu).';';
	
	return $menu;
	}
	
	function get_link_form_menu(){
		global $USER_DETAILS;

		$menu = '';
		$cmapid = get_request('favid',0);
		
		$ln_menu = array('selementid1' => S_ELEMENT_1,
						'selementid2' => S_ELEMENT_2,
						'triggers' => S_TRIGGERS,
						'drawtype' => S_TYPE,
						'color' => S_COLOR
				);
		
		$menu.= 'var zbx_link_menu = '.zbx_jsvalue($ln_menu).';'."\n";
		
		$ln_form_menu = array();
		
		$ln_form_menu['triggers'][] = array('key'=> '0',	'value'=> S_SELECT);
// LINK draw type
		$ln_form_menu['drawtype'] = array();
//		$ln_form_menu['drawtype_on'] = array();
		
		foreach(map_link_drawtypes() as $i){		
			$value = map_link_drawtype2str($i);
			
			$ln_form_menu['drawtype'][] = array('key'=> $i,	'value'=> $value);
//			$ln_form_menu['drawtype_on'][] = array('key'=> $i,	'value'=> $value);
		}

		
		$ln_form_menu['color'] = array();
//		$ln_form_menu['color_on'] = array();
		$colors = array('Black','Blue','Cyan','Dark Blue','Dark Green','Dark Red','Dark Yellow','Gray','Green','Red','White','Yellow');
		foreach($colors as $id => $value){
			$ln_form_menu['color'][] = array('key'=> $value,'value'=> $value);
//			$ln_form_menu['color_on'][] = array('key'=> $value,	'value'=> $value);
		}
		
		$menu.= 'var zbx_link_form_menu = '.zbx_jsvalue($ln_form_menu).';';
	
	return $menu;
	}

	function convertColor($im,$color){

		$RGB = array(
			hexdec('0x'.substr($color, 0,2)),
			hexdec('0x'.substr($color, 2,2)),
			hexdec('0x'.substr($color, 4,2))
			);


	return imagecolorallocate($im,$RGB[0],$RGB[1],$RGB[2]);
	}

/*
 * Function: expand_map_element_label_by_data
 *
 * Description:
 *     substitute simple macros {HOSTNAME}, {HOST.CONN}, {HOST.DNS}, {IPADDRESS} and
 *     functions {hostname:key.min/max/avg/last(...)}
 *     in data string with real values
 *
 * Author:
 *     Aleksander Vladishev
 *
 */
	function expand_map_element_label_by_data($db_element){
		$label = $db_element['label'];

		switch($db_element['elementtype']){
		case SYSMAP_ELEMENT_TYPE_HOST:
		case SYSMAP_ELEMENT_TYPE_TRIGGER:
			while(zbx_strstr($label, '{HOSTNAME}') || 
					zbx_strstr($label, '{HOST.DNS}') || 
					zbx_strstr($label, '{IPADDRESS}') || 
					zbx_strstr($label, '{HOST.CONN}'))
			{
				if($db_element['elementtype'] == SYSMAP_ELEMENT_TYPE_HOST){
					$sql =' SELECT * FROM hosts WHERE hostid='.$db_element['elementid'];
				}
				else if($db_element['elementtype'] == SYSMAP_ELEMENT_TYPE_TRIGGER)
					$sql =	'SELECT h.* '.
						' FROM hosts h,items i,functions f '.
						' WHERE h.hostid=i.hostid '.
							' AND i.itemid=f.itemid '.
							' AND f.triggerid='.$db_element['elementid'];
				else{
// Should never be here
				}

				$db_hosts = DBselect($sql);

				if($db_host = DBfetch($db_hosts)){
					if(zbx_strstr($label, '{HOSTNAME}')){
						$label = str_replace('{HOSTNAME}', $db_host['host'], $label);
					}

					if(zbx_strstr($label, '{HOST.DNS}')){
						$label = str_replace('{HOST.DNS}', $db_host['dns'], $label);
					}

					if(zbx_strstr($label, '{IPADDRESS}')){
						$label = str_replace('{IPADDRESS}', $db_host['ip'], $label);
					}

					if(zbx_strstr($label, '{HOST.CONN}')){
						$label = str_replace('{HOST.CONN}', $db_host['useip'] ? $db_host['ip'] : $db_host['dns'], $label);
					}
				}
			}
			break;
		}

		switch($db_element['elementtype']){
			case SYSMAP_ELEMENT_TYPE_HOST:
			case SYSMAP_ELEMENT_TYPE_MAP:
			case SYSMAP_ELEMENT_TYPE_TRIGGER:
			case SYSMAP_ELEMENT_TYPE_HOST_GROUP:
				while(zbx_strstr($label, '{TRIGGERS.UNACK}')){
					$label = str_replace('{TRIGGERS.UNACK}', get_triggers_unacknowledged($db_element), $label);
				}
				break;
		}

		while(false !== ($pos = strpos($label, '{'))){
			$expr = substr($label, $pos);

			if(false === ($pos = strpos($expr, '}'))) break;

			$expr = substr($expr, 1, $pos - 1);

			if(false === ($pos = strpos($expr, ':'))){
				$label = str_replace('{'.$expr.'}', '???', $label);
				continue;
			}

			$host = substr($expr, 0, $pos);
			$key = substr($expr, $pos + 1);

			if(false === ($pos = strrpos($key, '.'))){
				$label = str_replace('{'.$expr.'}', '???', $label);
				continue;
			}

			$function = substr($key, $pos + 1);
			$key = substr($key, 0, $pos);

			if(false === ($pos = strpos($function, '('))){
				$label = str_replace('{'.$expr.'}', '???', $label);
				continue;
			}

			$parameter = substr($function, $pos + 1);
			$function = substr($function, 0, $pos);

			if(false === ($pos = strrpos($parameter, ')'))){
				$label = str_replace('{'.$expr.'}', '???', $label);
				continue;
			}

			$parameter = substr($parameter, 0, $pos);

			$sql = 'SELECT itemid,value_type,units '.
					' FROM items i,hosts h '.
					' WHERE i.hostid=h.hostid '.
						' AND h.host='.zbx_dbstr($host).
						' AND i.key_='.zbx_dbstr($key);
			$db_items = DBselect($sql);
			if(NULL == ($db_item = DBfetch($db_items))){
				$label = str_replace('{'.$expr.'}', '???', $label);
				continue;
			}

			switch($db_item['value_type']){
				case ITEM_VALUE_TYPE_FLOAT:
					$history_table = 'history';
					$order_field = 'clock';
					break;
				case ITEM_VALUE_TYPE_UINT64:
					$history_table = 'history_uint';
					$order_field = 'clock';
					break;
				case ITEM_VALUE_TYPE_TEXT:
					$history_table = 'history_text';
					$order_field = 'id';
					break;
				case ITEM_VALUE_TYPE_LOG:
					$history_table = 'history_log';
					$order_field = 'id';
					break;
				default:
// ITEM_VALUE_TYPE_STR
					$history_table = 'history_str';
					$order_field = 'clock';
			}

			if(0 == strcmp($function, 'last')){
				$sql = 'SELECT value '.
						' FROM '.$history_table.
						' WHERE itemid='.$db_item['itemid'].
						' ORDER BY '.$order_field.' DESC';

				$result = DBselect($sql, 1);
				if(NULL == ($row = DBfetch($result)))
					$label = str_replace('{'.$expr.'}', '('.S_NO_DATA_SMALL.')', $label);
				else{
					switch($db_item['value_type']){
						case ITEM_VALUE_TYPE_FLOAT:
						case ITEM_VALUE_TYPE_UINT64:
							$value = convert_units($row['value'], $db_item['units']);
							break;
						default:
							$value = $row['value'];
					}

					$label = str_replace('{'.$expr.'}', $value, $label);
				}
			}
			else if((0 == strcmp($function, 'min')) || (0 == strcmp($function, 'max')) || (0 == strcmp($function, 'avg'))){

				if($db_item['value_type'] != ITEM_VALUE_TYPE_FLOAT && $db_item['value_type'] != ITEM_VALUE_TYPE_UINT64){
					$label = str_replace('{'.$expr.'}', '???', $label);
					continue;
				}

				$now = time(NULL) - $parameter;
				$sql = 'SELECT '.$function.'(value) as value '.
						' FROM '.$history_table.
						' WHERE clock>'.$now.
							' AND itemid='.$db_item['itemid'];

				$result = DBselect($sql);
				if(NULL == ($row = DBfetch($result)) || is_null($row['value']))
					$label = str_replace('{'.$expr.'}', '('.S_NO_DATA_SMALL.')', $label);
				else
					$label = str_replace('{'.$expr.'}', convert_units($row['value'], $db_item['units']), $label);
			}
			else{
				$label = str_replace('{'.$expr.'}', '???', $label);
				continue;
			}
		}

	return $label;
	}
	
	function get_triggers_unacknowledged($db_element){
		$elements = array('hosts' => array(), 'hosts_groups' => array(), 'triggers' => array());

		get_map_elements($db_element, $elements);

		$elements['hosts_groups'] = array_unique($elements['hosts_groups']);

		/* select all hosts linked to host groups */
		if (!empty($elements['hosts_groups'])){
			$db_hgroups = DBselect(
					'select distinct hostid'.
					' from hosts_groups'.
					' where '.DBcondition('groupid', $elements['hosts_groups']));
			while (NULL != ($db_hgroup = DBfetch($db_hgroups)))
				$elements['hosts'][] = $db_hgroup['hostid'];
		}

		$elements['hosts'] = array_unique($elements['hosts']);
		$elements['triggers'] = array_unique($elements['triggers']);

/* select all triggers linked to hosts */
		if (!empty($elements['hosts']) && !empty($elements['triggers']))
			$cond = '('.DBcondition('h.hostid', $elements['hosts']).
				' or '.DBcondition('t.triggerid', $elements['triggers']).')';
		else if (!empty($elements['hosts']))
			$cond = DBcondition('h.hostid', $elements['hosts']);
		else if (!empty($elements['triggers']))
			$cond = DBcondition('t.triggerid', $elements['triggers']);
		else
			return '0';


		$cnt = 0;
		$sql = 'SELECT DISTINCT t.triggerid '.
				' FROM triggers t,functions f,items i,hosts h '.
				' WHERE t.triggerid=f.triggerid '.
					' AND f.itemid=i.itemid '.
					' AND i.hostid=h.hostid '.
					' AND i.status='.ITEM_STATUS_ACTIVE.
					' AND h.status='.HOST_STATUS_MONITORED.
					' AND t.status='.TRIGGER_STATUS_ENABLED.
					' AND t.value='.TRIGGER_VALUE_TRUE.
					' AND '.$cond;
		$db_triggers = DBselect($sql);
		while($db_trigger = DBfetch($db_triggers)){
			$sql = 'SELECT eventid,value,acknowledged '.
					' FROM events'.
					' WHERE object='.EVENT_OBJECT_TRIGGER.
						' AND objectid='.$db_trigger['triggerid'].
					' ORDER BY eventid DESC';
			$db_events = DBselect($sql, 1);
			if($db_event= DBfetch($db_events))
				if(($db_event['value'] == TRIGGER_VALUE_TRUE) && ($db_event['acknowledged'] == 0)){
					$cnt++;
				}
		}

	return $cnt;
	}
	
	function get_map_elements($db_element, &$elements){
		switch ($db_element['elementtype']){
		case SYSMAP_ELEMENT_TYPE_HOST_GROUP:
			$elements['hosts_groups'][] = $db_element['elementid'];
			break;
		case SYSMAP_ELEMENT_TYPE_HOST:
			$elements['hosts'][] = $db_element['elementid'];
			break;
		case SYSMAP_ELEMENT_TYPE_TRIGGER:
			$elements['triggers'][] = $db_element['elementid'];
			break;
		case SYSMAP_ELEMENT_TYPE_MAP:
			$sql = 'SELECT DISTINCT elementtype,elementid'.
					' FROM sysmaps_elements'.
					' WHERE sysmapid='.$db_element['elementid'];
			$db_mapselements = DBselect($sql);
			while($db_mapelement = DBfetch($db_mapselements)){
				get_map_elements($db_mapelement, $elements);
			}
			break;
		}
	}
?>