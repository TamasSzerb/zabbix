<?php
/*
** Zabbix
** Copyright (C) 2001-2013 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


/**
 * Class is used to validate and parse item keys.
 *
 * Example of usage:
 *		$itemKey = new CItemKey('test.key[a, b, c]');
 *		echo $itemKey->isValid(); // true
 *		echo $itemKey->getKeyId(); // test.key
 *		print_r($itemKey->parameters()); // array('a', 'b', 'c')
 *
 * @package API
 */
class CItemKey {

	private $keyId = ''; // main part of the key (for 'key[1, 2, 3]' key id would be 'key')
	private $parameters = array();
	private $isValid = true;
	private $error = '';

	const STATE_NEW = 0;
	const STATE_END = 1;
	const STATE_UNQUOTED = 2;
	const STATE_QUOTED = 3;

	/**
	 * Parse key and determine if it is valid
	 * @param string $key
	 */
	public function __construct($key) {
		$this->parseKey($key);
	}

	/**
	 * Parse key and parameters and put them into $this->parameters array
	 *
	 * @param string $key
	 * @return void
	 */
	private function parseKey($key) {
		$pos = 0;

		// checking every byte, one by one, until first 'not key_id' char is reached
		while (isset($key[$pos])) {
			if (!isKeyIdChar($key[$pos])) {
				break; // $pos now points to a first 'not a key name' char
			}
			$this->keyId .= $key[$pos++];
		}

		// checking if key is empty
		if ($pos == 0) {
			$this->isValid = false;
			$this->error = _('key is empty');
			return;
		}

		// invalid symbol instead of '[', which would be the beginning of params
		if (isset($key[$pos]) && $key[$pos] != '[') {
			$this->isValid = false;
			$this->error = _s('error at position %1$s', $pos);
			return;
		}

		$state = self::STATE_END;
		$level = 0;
		$num = 0;

		while (isset($key[$pos])) {
			if ($level == 0) {
				// first square bracket + Zapcat compatibility
				if ($state == 1 && $key[$pos] == '[') {
					$state = self::STATE_NEW;
				}
				else {
					break;
				}
			}

			switch ($state) {
				// a new parameter started
				case self::STATE_NEW:
					switch ($key[$pos]) {
						case ' ':
							break;

						case ',':
							if ($level == 1) {
								if (!isset($this->parameters[$num])) {
									$this->parameters[$num] = '';
								}
								$num++;
							}
							break;

						case '[':
							$level++;
							if ($level == 2) {
								$l = $pos;
							}
							break;

						case ']':
							if ($level == 1) {
								if (!isset($this->parameters[$num])) {
									$this->parameters[$num] = '';
								}
								$num++;
							}
							elseif ($level == 2) {
								$this->parameters[$num] = '';
								for ($l++; $l < $pos; $l++) {
									$this->parameters[$num] .= $key[$l];
								}
							}
							$level--;
							$state = self::STATE_END;
							break;

						case '"':
							$state = self::STATE_QUOTED;
							if ($level == 1) {
								$l = $pos;
							}
							break;

						default:
							$state = self::STATE_UNQUOTED;
							if ($level == 1) {
								$l = $pos;
							}
					}
					break;

				// end of parameter
				case self::STATE_END:
					switch ($key[$pos]) {
						case ' ':
							break;

						case ',':
							$state = self::STATE_NEW;
							if ($level == 1) {
								if (!isset($this->parameters[$num])) {
									$this->parameters[$num] = '';
								}
								$num++;
							}
							break;

						case ']':
							if ($level == 1) {
								if (!isset($this->parameters[$num])) {
									$this->parameters[$num] = '';
								}
								$num++;
							}
							elseif ($level == 2) {
								$this->parameters[$num] = '';
								for ($l++; $l < $pos; $l++) {
									$this->parameters[$num] .= $key[$l];
								}
							}
							$level--;
							break;

						default:
							break 3;
					}
					break;

				// an unquoted parameter
				case self::STATE_UNQUOTED:
					if ($key[$pos] == ']' || $key[$pos] == ',') {
						if ($level == 1) {
							$this->parameters[$num] = '';
							for (; $l < $pos; $l++) {
								$this->parameters[$num] .= $key[$l];
							}
						}
						$pos--;
						$state = self::STATE_END;
					}
					break;

				// a quoted parameter
				case self::STATE_QUOTED:
					if ($key[$pos] == '"' && $key[$pos - 1] != '\\') {
						if ($level == 1) {
							$this->parameters[$num] = '';
							for ($l++; $l < $pos; $l++) {
								if ($key[$l] != '\\' || $key[$l + 1] != '"') {
									$this->parameters[$num] .= $key[$l];
								}
							}
						}
						$state = self::STATE_END;
					}
					break;
			}

			$pos++;
		}

		if ($pos == 0 || isset($key[$pos]) || $level != 0) {
			$this->isValid = false;
			$this->error = _s('error at position %1$s', $pos);
		}
	}

	/**
	 * Is key valid?
	 * @return bool
	 */
	public function isValid() {
		return $this->isValid;
	}

	/**
	 * Get the error if key is invalid
	 * @return string
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * Get the list of key parameters
	 * @return array
	 */
	public function getParameters() {
		return $this->parameters;
	}

	/**
	 * Get the key id (first part of the key)
	 * @return string
	 */
	public function getKeyId() {
		return $this->keyId;
	}
}
