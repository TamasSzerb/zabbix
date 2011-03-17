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
class CComboBox extends CTag{
	public $value;

	public function __construct($name='combobox', $value=null, $action=null, $items=null){
		parent::__construct('select', 'yes');
		$this->tag_end = '';

		$this->setAttribute('id', $name);
		$this->setAttribute('name', $name);

		$this->setAttribute('class', 'input select');
		$this->setAttribute('size', 1);

		$this->value = $value;
		$this->setAttribute('onchange',$action);

		if(is_array($items)) $this->addItems($items);
	}

	public function setValue($value=NULL){
		$this->value = $value;
	}

	public function addItems($items){
		foreach($items as $value => $caption){
			$selected = (int) ($value == $this->value);
			parent::addItem(new CComboItem($value, $caption, $selected));
		}
	}

	public function addItemsInGroup($label, $items){
		$group = new COptGroup($label);
		foreach($items as $value => $caption){
			$selected = (int) ($value == $this->value);
			$group->addItem(new CComboItem($value, $caption, $selected));
		}
		parent::addItem($group);
	}


	public function addItem($value, $caption='', $selected=NULL, $enabled='yes'){
		if(is_object($value) && (zbx_strtolower(get_class($value)) == 'ccomboitem')){
			parent::addItem($value);
		}
		else{
			$title = false;

			if(zbx_strlen($caption) > 44){
				$this->setAttribute('class', $this->getAttribute('class').' selectShorten');
				$title = true;
			}

			if(is_null($selected)){
				$selected = 'no';
				if(is_array($this->value)) {
					if(str_in_array($value,$this->value))
						$selected = 'yes';
				}
				else if(strcmp($value,$this->value) == 0){
					$selected = 'yes';
				}
			}
			else{
				$selected = 'yes';
			}

			$citem = new CComboItem($value, $caption, $selected, $enabled);
			if($title) $citem->setTitle($caption);
			parent::addItem($citem);
		}
	}
}

class COptGroup extends CTag{
	public function __construct($label){
		parent::__construct('optgroup', 'yes');

		$this->setAttribute('label', $label);
	}
}

?>
