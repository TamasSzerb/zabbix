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
class CButtonQMessage extends CSubmit{
 public $vars;
 public $msg;
 public $name;

	public function __construct($name, $caption, $msg=NULL, $vars=NULL, $class=null){
		$this->vars = null;
		$this->msg = null;
		$this->name = $name;

		parent::__construct($name,$caption,null,$class);

		$this->setMessage($msg);
		$this->setVars($vars);
		$this->setAction(NULL);
	}

	public function setVars($value=NULL){
		if(!is_string($value) && !is_null($value)){
			return $this->error('Incorrect value for setVars ['.$value.']');
		}
		$this->vars = $value;
		$this->setAction(NULL);
	}

	public function setMessage($value=NULL){
		if(is_null($value))
			$value = 'Are You Sure?';

		if(!is_string($value)){
			return $this->error('Incorrect value for setMessage ['.$value.']');
		}
		$this->msg = $value;
		$this->setAction(NULL);
	}

	public function setAction($value=null){
		if(!is_null($value))
			return parent::setAttribute('onclick', $value);

		global $page;

		$confirmation = "Confirm('".$this->msg."')";

		if(isset($this->vars)){
			$link = $page['file'].'?'.$this->name.'=1'.$this->vars;
			$url = new Curl($link);

			$action = "redirect('".$url->getUrl()."')";
		}
		else{
			$action = 'true';
		}

		return parent::setAttribute('onclick', 'if('.$confirmation.') return '.$action.'; else return false;');
	}
}
?>
