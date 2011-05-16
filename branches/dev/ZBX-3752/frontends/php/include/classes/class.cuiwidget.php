<?php
/*
** Zabbix
** Copyright (C) 2000-2011 Zabbix SIA
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

class CUIWidget extends CDiv{

public $domid;
public $state;
public $css_class;

private $header;
private $body;
private $footer;

	public function __construct($id, $body=null, $state=null){
		$this->domid = $id;
		$this->state = $state;		// 0 - closed, 1 - opened

		$this->css_class = 'header';

		$this->header = null;
		$this->body = array($body);
		$this->footer = null;

		parent::__construct(null, 'ui-widget ui-widget-content ui-helper-clearfix ui-corner-all widget');
		$this->setAttribute('id', $id.'_widget');
	}

	public function addItem($item){
		if(!is_null($item)) $this->body[] = $item;
	}

	public function setHeader($caption=null, $icons = SPACE){
		zbx_value2array($icons);

		if(is_null($caption) && !is_null($icons)) $caption = SPACE;

		$this->header = new CDiv(null, 'nowrap ui-corner-all ui-widget-header '.$this->css_class);

		if(!is_null($this->state)){
			$icon = new CIcon(
				S_SHOW.'/'.S_HIDE,
				$this->state?'arrowup':'arrowdown',
				"changeHatStateUI(this,'".$this->domid."');"
			);
			$icon->setAttribute('id',$this->domid.'_icon');
			$this->header->addItem($icon);
		}

		$this->header->addItem($icons);
		$this->header->addItem($caption);

	return $this->header;
	}

	public function setFooter($footer, $right=false){
		$this->footer = new CDiv($footer, 'nowrap ui-corner-all ui-widget-header footer '.($right?' right':' left'));

	return $this->footer;
	}

	public function get(){
		$this->cleanItems();
		parent::addItem($this->header);

		if(is_null($this->state)){
			$this->state = true;
		}

		$div = new CDiv($this->body, 'body');
		$div->setAttribute('id',$this->domid);

		if(!$this->state){
			$div->setAttribute('style','display: none;');
			$this->footer->setAttribute('style','display: none;');
		}

		parent::addItem($div);
		parent::addItem($this->footer);

	return $this;
	}

	public function toString($destroy=true){
		$this->get();
	return parent::toString($destroy);
	}
}
?>