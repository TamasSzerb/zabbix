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
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/
?>
<?php
class CTag extends CObject {
	public function __construct($tagname = null, $paired = 'no', $body = null, $class = null) {
		parent::__construct();
		$this->attributes = array();

		if (!is_string($tagname)) {
			return $this->error('Incorrect tagname for CTag ['.$tagname.']');
		}

		$this->tagname = $tagname;
		$this->paired = $paired;
		$this->tag_start = $this->tag_end = $this->tag_body_start = $this->tag_body_end = '';

		if (is_null($body)) {
			$this->tag_end = $this->tag_body_start = '';
		}
		else {
			$this->addItem($body);
		}
		$this->addClass($class);
	}

	public function showStart() {
		echo $this->startToString();
	}

	public function showBody() {
		echo $this->bodyToString();
	}

	public function showEnd() {
		echo $this->endToString();
	}

	// Do not put new line symbol(\n) before or after html tags,
	// it adds spaces in unwanted places
	public function startToString() {
		$res = $this->tag_start.'<'.$this->tagname;
		foreach ($this->attributes as $key => $value) {
			$value = str_replace(array("\r", "\n"), '', strval($value));
			$res .= ' '.$key.'="'.$this->sanitize($value).'"';
		}
		$res .= ($this->paired === 'yes') ? '>' : ' />';
		return $res;
	}

	public function bodyToString() {
		$res = $this->tag_body_start;
		return $res.parent::toString(false);
	}

	public function endToString() {
		$res = ($this->paired === 'yes') ? $this->tag_body_end.'</'.$this->tagname.'>' : '';
		$res .= $this->tag_end;
		return $res;
	}

	public function toString($destroy = true) {
		$res  = $this->startToString();
		$res .= $this->bodyToString();
		$res .= $this->endToString();
		if ($destroy) {
			$this->destroy();
		}
		return $res;
	}

	public function setName($value) {
		if (is_null($value)) {
			return $value;
		}
		if (!is_string($value)) {
			return $this->error('Incorrect value for SetName ['.$value.']');
		}
		return $this->setAttribute('name', $value);
	}

	public function getName() {
		if (isset($this->attributes['name'])) {
			return $this->attributes['name'];
		}
		return null;
	}

	public function addClass($cssClass) {
		if (!isset($this->attributes['class']) || zbx_empty($this->attributes['class'])) {
			$this->attributes['class'] = $cssClass;
		}
		else {
			$this->attributes['class'] .= ' '.$cssClass;
		}
		return $this->attributes['class'];
	}

	public function attr($name, $value = null) {
		if (is_null($value)) {
			$this->getAttribute($name);
		}
		else {
			$this->setAttribute($name, $value);
		}
	}

	public function getAttribute($name) {
		if (isset($this->attributes[$name])) {
			return $this->attributes[$name];
		}
		else {
			return null;
		}
	}

	public function setAttribute($name, $value) {
		if (is_object($value)) {
			$value = unpack_object($value);
		}
		if (!is_null($value)) {
			$this->attributes[$name] = $value;
		}
		else {
			$this->removeAttribute($name);
		}
	}

	public function removeAttr($name) {
		$this->removeAttribute($name);
	}

	public function removeAttribute($name) {
		unset($this->attributes[$name]);
	}

	public function addAction($name, $value) {
		$this->attributes[$name] = $value;
	}

	public function setHint($text, $width = '', $class = '', $byClick = true, $updateBlinking = false) {
		if (empty($text)) {
			return false;
		}
		encodeValues($text);
		$text = unpack_object($text);

		// if there are OK/PROBLEM statuses in hint, we might want them to blink
		$blinkUpdate = $updateBlinking ? ' jqBlink.findObjects();' : '';

		$this->addAction('onmouseover', 'javascript: hintBox.showOver(this, '.zbx_jsvalue($text).', \''.$width.'\', \''.$class.'\');'.$blinkUpdate);
		$this->addAction('onmouseout', 'javascript: hintBox.hideOut(this);');
		if ($byClick) {
			$this->addAction('onclick', 'javascript: hintBox.onClick(this, '.zbx_jsvalue($text).', \''.$width.'\', \''.$class.'\');'.$blinkUpdate);
		}
		return true;
	}

	public function onClick($handle_code) {
		$this->addAction('onclick', $handle_code);
	}

	public function addStyle($value) {
		if (!isset($this->attributes['style'])) {
			$this->attributes['style'] = '';
		}
		if (isset($value)) {
			$this->attributes['style'] .= htmlspecialchars(strval($value));
		}
		else {
			unset($this->attributes['style']);
		}
	}

	public function setEnabled($value = 'yes') {
		if ((is_string($value) && ($value == 'yes' || $value == 'enabled' || $value == 'on') || $value == '1') || (is_int($value) && $value <> 0)) {
			unset($this->attributes['disabled']);
		}
		elseif ((is_string($value) && ($value == 'no' || $value == 'disabled' || $value == 'off') || $value == '0') || (is_int($value) && $value == 0)) {
			$this->attributes['disabled'] = 'disabled';
		}
	}

	public function error($value) {
		error('class('.get_class($this).') - '.$value);
		return 1;
	}

	public function getForm($method = 'post', $action = null, $enctype = null) {
		$form = new CForm($method, $action, $enctype);
		$form->addItem($this);
		return $form;
	}

	public function setTitle($value = 'title') {
		$this->setAttribute('title', $value);
	}
}
?>
