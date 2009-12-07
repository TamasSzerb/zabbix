<?php
/*
** ZABBIX
** Copyright (C) 2000-2009 SIA Zabbix
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
	require_once('include/config.inc.php');
	require_once('include/maps.inc.php');

	$page['title'] = 'S_MAP';
	$page['file'] = 'map.php';
	$page['type'] = detect_page_type(PAGE_TYPE_IMAGE);

include_once('include/page_header.php');

?>
<?php
//		VAR			TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION
	$fields=array(
		'sysmapid'=>		array(T_ZBX_INT, O_MAND,P_SYS,	DB_ID,		NULL),

		'selements'=>		array(T_ZBX_STR, O_OPT,	P_SYS,	DB_ID, NULL),
		'links'=>			array(T_ZBX_STR, O_OPT,	P_SYS,	DB_ID, NULL),
		'noselements'=>		array(T_ZBX_INT, O_OPT,	NULL,	IN("0,1"),	NULL),
		'nolinks'=>			array(T_ZBX_INT, O_OPT,	NULL,	IN("0,1"),	NULL),

		'show_triggers'=>	array(T_ZBX_INT, O_OPT,	P_SYS,		IN("0,1,2,3"),	NULL),
		'noedit'=>			array(T_ZBX_INT, O_OPT,	NULL,	IN('0,1'),	NULL),
		'border'=>			array(T_ZBX_INT, O_OPT,	NULL,	IN('0,1'),	NULL)
	);

	check_fields($fields);
?>
<?php

	$options = array(
		'sysmapids' => $_REQUEST['sysmapid'],
		'select_selements' => 1,
		'select_links' => 1,
		'extendoutput' => 1
	);

	$maps = CMap::get($options);

	if(empty($maps)) access_deny();
	else $map = reset($maps);

	$name		= $map['name'];
	$width		= $map['width'];
	$height		= $map['height'];
	$backgroundid	= $map['backgroundid'];
	$highlight	= $map['highlight'];
	$label_type	= $map['label_type'];
	$status_view = 0;// $map['status_view'];

	if(function_exists('imagecreatetruecolor')&&@imagecreatetruecolor(1,1)){
		$im = imagecreatetruecolor($width,$height);
	}
	else{
		$im = imagecreate($width,$height);
	}

	$red		= imagecolorallocate($im,255,0,0);
	$darkred	= imagecolorallocate($im,150,0,0);
	$green		= imagecolorallocate($im,0,255,0);
	$darkgreen	= imagecolorallocate($im,0,150,0);
	$blue		= imagecolorallocate($im,0,0,255);
	$yellow		= imagecolorallocate($im,255,255,0);
	$darkyellow	= imagecolorallocate($im,150,127,0);
	$cyan		= imagecolorallocate($im,0,255,255);
	$white		= imagecolorallocate($im,255,255,255);
	$black		= imagecolorallocate($im,0,0,0);
	$gray		= imagecolorallocate($im,150,150,150);

	$colors['Red']		= imagecolorallocate($im,255,0,0);
	$colors['Dark Red']	= imagecolorallocate($im,150,0,0);
	$colors['Green']	= imagecolorallocate($im,0,255,0);
	$colors['Dark Green']	= imagecolorallocate($im,0,150,0);
	$colors['Blue']		= imagecolorallocate($im,0,0,255);
	$colors['Dark Blue']	= imagecolorallocate($im,0,0,150);
	$colors['Yellow']	= imagecolorallocate($im,255,255,0);
	$colors['Dark Yellow']	= imagecolorallocate($im,150,150,0);
	$colors['Cyan']		= imagecolorallocate($im,0,255,255);
	$colors['Black']	= imagecolorallocate($im,0,0,0);
	$colors['Gray']		= imagecolorallocate($im,150,150,150);
	$colors['White']	= imagecolorallocate($im,255,255,255);
	$colors['Orange']	= imagecolorallocate($im,238,96,0);


	$x=imagesx($im);
	$y=imagesy($im);

	imagefilledrectangle($im,0,0,$width,$height,$white);

	if(($db_image = get_image_by_imageid($backgroundid))){
		$back = imagecreatefromstring($db_image['image']);
		imagecopy($im,$back,0,0,0,0,imagesx($back),imagesy($back));
	}
	else{
		$x=imagesx($im)/2-ImageFontWidth(4)*strlen($name)/2;
		imagetext($im, 10, 0, $x, 25, $darkred, $name);
	}
	unset($db_image);

	$str = date('m.d.Y H:i:s',time(NULL));
	imagestring($im, 0,imagesx($im)-120,imagesy($im)-12,$str, $gray);

	if(!isset($_REQUEST['noedit'])){
		$grid = 50;

		$dims = imageTextSize(8, 0, '11');
		for($x=$grid; $x<$width; $x+=$grid){
			MyDrawLine($im,$x,0,$x,$height,$black, MAP_LINK_DRAWTYPE_DASHED_LINE);
			imageText($im, 8, 0, $x+3, $dims['height']+3, $black,$x);
		}
		for($y=$grid;$y<$height;$y+=$grid){
			MyDrawLine($im,0,$y,$width,$y,$black, MAP_LINK_DRAWTYPE_DASHED_LINE);
			imageText($im, 8, 0, 3, $y+$dims['height']+3, $black, $y);
		}

		imageText($im, 8, 0, 2, $dims['height']+3, $black, 'Y X:');
	}
// ACTION /////////////////////////////////////////////////////////////////////////////

	$json = new CJSON();

	if(isset($_REQUEST['selements']) || isset($_REQUEST['noselements'])){
		$selements = get_request('selements', '[]');
		$selements = $json->decode($selements, true);
	}
	else{
		$selements = zbx_toHash($map['selements'], 'selementid');
	}

	if(isset($_REQUEST['links']) || isset($_REQUEST['nolinks'])){
		$links = get_request('links', '[]');
		$links = $json->decode($links, true);
	}
	else{
		$links = zbx_toHash($map['links'],'linkid');
	}

//SDI($links); exit;
// Draw connectors
	foreach($links as $lnum => $link){
		if(empty($link)) continue;
		$linkid = $link['linkid'];

		$selement = $selements[$link['selementid1']];
		list($x1, $y1) = get_icon_center_by_selement($selement);

		$selement = $selements[$link['selementid2']];
		list($x2, $y2) = get_icon_center_by_selement($selement);

		$drawtype = $link['drawtype'];
		$color = convertColor($im,$link['color']);

		$linktriggers = $link['linktriggers'];
		if(!empty($linktriggers)){
			$max_severity=0;
			$options = array();
			$options['nopermissions'] = 1;
			$options['extendoutput'] = 1;
			$options['triggerids'] = array();

			$triggers = array();
			foreach($linktriggers as $lt_num => $link_trigger){
				if($link_trigger['triggerid'] == 0) continue;
				$id = $link_trigger['linktriggerid'];

				$triggers[$id] = zbx_array_merge($link_trigger,get_trigger_by_triggerid($link_trigger['triggerid']));
				if(($triggers[$id]['status'] == TRIGGER_STATUS_ENABLED) && ($triggers[$id]['value'] == TRIGGER_VALUE_TRUE)){
					if($triggers[$id]['priority'] >= $max_severity){
						$drawtype = $triggers[$id]['drawtype'];
						$color = convertColor($im,$triggers[$id]['color']);
						$max_severity = $triggers[$id]['priority'];
					}
				}
			}
		}
		MyDrawLine($im,$x1,$y1,$x2,$y2,$color,$drawtype);

// Link Label
		if(empty($link['label'])) continue;

		$label = $link['label'];

		$label = str_replace("\r", '', $label);
		$strings = explode("\n", $label);

		$box_width = 0;
		$box_height = 0;

		foreach($strings as $snum => $str)
			$strings[$snum] = expand_map_element_label_by_data(null, $str); 

		foreach($strings as $snum => $str){
			$dims = imageTextSize(8,0,$str);

			$box_width = ($box_width > $dims['width'])?$box_width:$dims['width'];
			$box_height+= $dims['height']+2;
		}

		$boxX_left = round(($x1 + $x2) / 2 - ($box_width/2) - 6);
		$boxX_right = round(($x1 + $x2) / 2 + ($box_width/2) + 6);

		$boxY_top = round(($y1 + $y2) / 2 - ($box_height/2) - 4);
		$boxY_bottom = round(($y1 + $y2) / 2 + ($box_height/2) + 2);

		switch($drawtype){
			case MAP_LINK_DRAWTYPE_DASHED_LINE:
			case MAP_LINK_DRAWTYPE_DOT:
				dashedrectangle($im, $boxX_left, $boxY_top, $boxX_right, $boxY_bottom, $color);
				break;
			case MAP_LINK_DRAWTYPE_BOLD_LINE:
				imagerectangle($im, $boxX_left-1, $boxY_top-1, $boxX_right+1, $boxY_bottom+1, $color);
			case MAP_LINK_DRAWTYPE_LINE:
			default:
				imagerectangle($im, $boxX_left, $boxY_top, $boxX_right, $boxY_bottom, $color);
		}

		imagefilledrectangle($im, $boxX_left+1, $boxY_top+1, $boxX_right-1, $boxY_bottom-1, $white);


		$increasey = 4;
		foreach($strings as $snum => $str){
			$dims = imageTextSize(8,0,$str);

			$labelx = ($x1 + $x2) / 2 - ($dims['width']/2);
			$labely = $boxY_top + $increasey;

			imagetext($im, 8, 0, $labelx, $labely+$dims['height'], $black, $str);

			$increasey += $dims['height']+2;
		}
	}
//-----------------------

// Draws elements
	$icons=array();
	foreach($selements as $selementid => $selement){
		if(empty($selement)) continue;

//		$info = get_info_by_selement($selement);
		$el_info = get_info_by_selement($selement,$status_view);
		$img = get_png_by_selement($selement, $el_info);

		$iconX = imagesx($img);
		$iconY = imagesy($img);

		if(isset($_REQUEST['noedit']) && ($highlight == SYSMAP_HIGHLIGH_ON)){
			$hl_color = null;
			if($el_info['icon_type'] == SYSMAP_ELEMENT_ICON_ON){
				switch($el_info['priority']){
					case TRIGGER_SEVERITY_DISASTER: 	$hl_color = hex2rgb('FF0000'); break;
					case TRIGGER_SEVERITY_HIGH:  		$hl_color = hex2rgb('FF8888'); break;
					case TRIGGER_SEVERITY_AVERAGE:  	$hl_color = hex2rgb('DDAAAA'); break;
					case TRIGGER_SEVERITY_WARNING:  	$hl_color = hex2rgb('EFEFCC'); break;
					case TRIGGER_SEVERITY_INFORMATION:  $hl_color = hex2rgb('CCE2CC'); break;
					case TRIGGER_SEVERITY_NOT_CLASSIFIED:
					default:
				}
			}

			if($el_info['icon_type'] == SYSMAP_ELEMENT_ICON_UNKNOWN)	$hl_color = hex2rgb('CCCCCC');
			if(isset($el_info['maintenance']))	$hl_color = hex2rgb('EE6000');
			if(isset($el_info['disabled'])) $hl_color = hex2rgb('AA0000');

			if(!is_null($hl_color)){
				$r = $hl_color[0];
				$g = $hl_color[1];
				$b = $hl_color[2];

				imagefilledellipse($im,
						$selement['x'] + ($iconX / 2),
						$selement['y'] + ($iconY / 2),
						$iconX+20,
						$iconX+20,
						imagecolorallocatealpha($im,$r,$g,$b, 30)
					);
/*
				for($radius=$iconX; $radius > 0; $radius-=1){
					$uradius = $iconX-$radius +1;
					$w = ($uradius * pow(2, $uradius/30)) + 50;
					$w = ($w > 255)?255:$w;

//SDI($radius.': '.$w.' '.$r.' '.$g.' '.$b.'  -  '.($uradius * pow(1.9, $uradius/40)));

					$d = 0;
					$dr = $w - $r;
					$dg = $w - $g;
					$db = $w - $b;
					$d = max(array($d, $dr, $dg, $db));

					$r = (($r+$d)>255)?255:($r+$d);
					$g = (($g+$d)>255)?255:($g+$d);
					$b = (($b+$d)>255)?255:($b+$d);

					imagefilledellipse($im,
							$selement['x'] + ($iconX / 2),
							$selement['y'] + ($iconY / 2),
							$radius,
							$radius,
							imagecolorallocatealpha($im,$r,$g,$b, 0)
						);
				}
//*/
			}
		}

		if(!isset($_REQUEST['noselements'])){
			imagecopy($im,$img,$selement['x'],$selement['y'],0,0,$iconX,$iconY);
		}

		if($label_type == MAP_LABEL_TYPE_NOTHING) continue;

		$color	= $darkgreen;
		$label_color = $black;

		$info_line	= '';

		$label_location = $selement['label_location'];
		if(is_null($label_location)) $label_location = $map['label_location'];

		$label_line = expand_map_element_label_by_data($selement);


		$info_line	= $el_info['info'];
		$color		= $el_info['color'];

		if($label_type == MAP_LABEL_TYPE_STATUS){
			$label_line = '';
		}
		else if($label_type == MAP_LABEL_TYPE_NAME){
			$label_line = $el_info['name'];
		}

		if(isset($el_info['disabled']) && $el_info['disabled'] == 1){
			$info_line = 'DISABLED';
			$label_color = $gray;
		}

		unset($el_info);

		if($selement['elementtype'] == SYSMAP_ELEMENT_TYPE_HOST){
			$host = get_host_by_hostid($selement['elementid']);

			if($label_type==MAP_LABEL_TYPE_IP) $label_line = $host['ip'];

			if($host['status'] == HOST_STATUS_NOT_MONITORED) $label_color = $darkred;
		}

		if($selement['elementtype'] == SYSMAP_ELEMENT_TYPE_IMAGE){
			$label_line = expand_map_element_label_by_data($selement);
		}

// LABEL
		if($label_line=='' && $info_line=='') continue;

		$label_line = str_replace("\r", '', $label_line);
		$strings = explode("\n", $label_line);
		array_push($strings, $info_line);
		$cnt = count($strings);
		$num = 0;

		$x = $selement['x'];
		$y = $selement['y'];
		$h = ImageFontHeight(2);

		$x_info = $selement['x'];
		$y_info = $selement['y'];

		if($label_location == MAP_LABEL_LOC_TOP)
			$y -= $h * $cnt;
		else if ($label_location == MAP_LABEL_LOC_LEFT || $label_location == MAP_LABEL_LOC_RIGHT)
			$y += imagesy($img) / 2 - $h * $cnt / 2;
		else	/* MAP_LABEL_LOC_BOTTOM */
			$y += imagesy($img);

		$increasey = 1;
		foreach($strings as $str){
			$num++;
			$dims = imageTextSize(8,0,$str);

			if ($label_location == MAP_LABEL_LOC_TOP || $label_location == MAP_LABEL_LOC_BOTTOM)
				$x_label = $x + $iconX / 2 - $dims['width'] / 2;
			else if ($label_location == MAP_LABEL_LOC_LEFT)
				$x_label = $x - $dims['width'];
			else	/* MAP_LABEL_LOC_RIGHT */
				$x_label = $x + $iconX;

			imagefilledrectangle($im, $x_label-1, $y+$dims['height']+$increasey+1, $x_label + $dims['width']+1, $y+$increasey, $white);
			imagetext($im, 8, 0, $x_label, $y+$increasey+$dims['height'], ($num == $cnt)?$color:$label_color, $str);

			$increasey+= $dims['height']+2;
		}
	}

	imagestringup($im,0,imagesx($im)-10,imagesy($im)-50, S_ZABBIX_URL, $gray);

	if(!isset($_REQUEST['border'])){
		imagerectangle($im,0,0,$width-1,$height-1,$colors['Black']);
	}

	imageOut($im);

	imagedestroy($im);
?>
<?php

include_once('include/page_footer.php');

?>
