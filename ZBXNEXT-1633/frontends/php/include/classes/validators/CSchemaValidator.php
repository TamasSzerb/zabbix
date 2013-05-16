<?php
/*
** Zabbix
** Copyright (C) 2000-2012 Zabbix SIA
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


class CSchemaValidator extends CValidator {

	/**
	 * Array of validators where keys are object field names and values are either CValidator objects or nulls.
	 *
	 * If the value is set to null, it will not be validated, but no $messageUnsupported error will be triggered.
	 *
	 * @var array
	 */
	public $validators = array();

	/**
	 * Array of validators to validate the whole object.
	 *
	 * @var array
	 */
	public $postValidators = array();

	/**
	 * Array of required field names.
	 *
	 * @var array
	 */
	public $required = array();

	/**
	 * Error message if a required field is missing.
	 *
	 * @var string
	 */
	public $messageRequired;

	/**
	 * Error message if an unsupported field is given.
	 *
	 * @var string
	 */
	public $messageUnsupported;

	/**
	 * Checks each object field against the given validator, and then the whole object against the post validators.
	 *
	 * @param array $value
	 *
	 * @return bool
	 */
	public function validate($value) {
		$required = array_flip($this->required);
		$unvalidatedFields = array_flip(array_keys($value));

		// field validators
		foreach ($this->validators as $field => $validator) {
			unset($unvalidatedFields[$field]);

			// if the value is present
			if (isset($value[$field])) {
				// validate it if a validator is given, skip it otherwise
				if ($validator && !$validator->validate($value[$field])) {
					$this->setError($validator->getError());

					return false;
				}
			}
			// if no value is given, check if it's required
			elseif (isset($required[$field])) {
				$this->error($this->messageRequired, $field);

				return false;
			}
		}

		// check if any unsupported fields remain
		if ($unvalidatedFields) {
			$this->error($this->messageUnsupported);

			return false;
		}

		// post validators
		foreach ($this->postValidators as $validator) {
			if (!$validator->validate($value)) {
				$this->setError($validator->getError());

				return false;
			}
		}

		return true;
	}

	/**
	 * Set the object name for the current validator and all included validators.
	 *
	 * @param string $name
	 */
	public function setObjectName($name) {
		parent::setObjectName($name);

		foreach ($this->validators as $validator) {
			if ($validator) {
				$validator->setObjectName($name);
			}
		}

		foreach ($this->postValidators as $validator) {
			$validator->setObjectName($name);
		}
	}

}
