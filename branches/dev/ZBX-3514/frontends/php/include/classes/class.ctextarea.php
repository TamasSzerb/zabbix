<?php
/*
** ZABBIX
** Copyright (C) 2000-2011 SIA Zabbix
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
class CTextArea extends CTag{
/* public */
	public function __construct($name='textarea',$value='',$cols=77,$rows=7, $readonly=false){
		parent::__construct('textarea','yes');
		$this->attributes['class'] = 'input';

		$this->attributes['id'] = $name;
		$this->attributes['name'] = $name;

		$this->attributes['rows'] = $rows;
		$this->attributes['cols'] = $cols;
		$this->setReadonly($readonly);

		$this->addItem($value);
	}

	public function setReadonly($value=true){
		if($value)
			$this->attributes['readonly'] = 'readonly';
		else
			$this->removeAttribute('readonly');
	}

	public function setValue($value=''){
		return $this->addItem($value);
	}

	public function setRows($value){
		return $this->attributes['rows'] = $value;
	}

	public function setCols($value){
		return $this->attributes['cols'] = $value;

	}
}
?>
