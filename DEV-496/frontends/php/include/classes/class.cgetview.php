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
class CGetView{
	private $file;
	private $data;
	private $form;
	private $scripts;

	public function __construct($file, $data=array()){
		$this->assign($file, $data);
	}

	public function assign($file, $data){
		if(!preg_match("/[a-z\.]+/", $file)){
			error('Invalid form name given ['.$file.']');
			return false;
		}

		$this->file = './include/views/'.$file.'.php';
		$this->data = $data;
	}

	public function set($var, $value){
		$this->data[$var] = $value;
	}

	public function get($var){
		return isset($this->data[$var]) ? $this->data[$var] : '';
	}

	public function getArray($var){
		return isset($this->data[$var]) && is_array($this->data[$var]) ? $this->data[$var] : array();
	}

	public function render(){
		$data = $this->data;

		ob_start();
		$this->form = include($this->file);
		$this->scripts = ob_get_clean();

		print($this->scripts);
		return $this->form;
	}
	/**
	 * The method outputs HTML code based on rendered template.
	 * It calls render() if not called already.
	 * @return NULL
	 */
	public function show(){
		if(!isset($this->form)){
			$this->render();
		}
		$this->form->show();
	}
}
?>