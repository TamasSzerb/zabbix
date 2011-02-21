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

class czbxrpc{

	private static $transactionStarted = false;


	public static function call($method, $params, $sessionid=null){
// List of methods without params
		$notifications = array(
			'apiinfo.version' => 1
		);
//-----

// list of methods which does not require authentication
		$withoutAuth = array(
			'apiinfo.version' => 1
		);
//-----

		if(is_null($params) && !isset($notifications[$method])){
			return array('error' => ZBX_API_ERROR_PARAMETERS, 'data' => _('Empty parameters'));
		}

// Authentication {{{
		if(!isset($withoutAuth[$method]) || !zbx_empty($sessionid)){
// compatibility mode
			if($method == 'user.authenticate') $method = 'user.login';

			if(zbx_empty($sessionid) && ($method != 'user.login')){
				return array('error' => ZBX_API_ERROR_NO_AUTH, 'data' => _('Not authorized'));
			}
			else if(!zbx_empty($sessionid)){
				$usr = self::callAPI('user.checkAuthentication', $sessionid);
				if(!isset($usr['result'])){
					return array('error' => ZBX_API_ERROR_NO_AUTH, 'data' => _('Not authorized'));
				}
			}
		}
// }}} Authentication

		return self::callAPI($method, $params);
	}

	private static function transactionBegin(){
		global $DB;

		if($DB['TRANSACTIONS'] == 0){
			DBstart();
			self::$transactionStarted = true;
		}
	}

	private static function transactionEnd($result){
		if(self::$transactionStarted){
			self::$transactionStarted = false;
			DBend($result);
		}
	}

	private static function callJSON($method, $params){
		// http bla bla
	}

	private static function callAPI($method, $params){
		if(is_array($params))
			unset($params['nopermissions']);

		list($resource, $action) = explode('.', $method);

		$class_name = 'C'.$resource;
		if(!class_exists($class_name)){
			return array('error' => ZBX_API_ERROR_PARAMETERS, 'data' => 'Resource ('.$resource.') does not exist');
		}

		if(!method_exists($class_name, $action)){
			return array('error' => ZBX_API_ERROR_PARAMETERS, 'data' => 'Action ('.$action.') does not exist');
		}

		try{
			self::transactionBegin();
			API::setReturnAPI();

			$result = call_user_func(array(API::getObject($resource), $action), $params);

			API::setReturnRPC();
			self::transactionEnd(true);

			return array('result' => $result);
		}
		catch(APIException $e){
			API::setReturnRPC();
			$result = ($method === 'user.login');
			self::transactionEnd($result);

			$result = array(
				'error' => $e->getCode(),
				'data' => $e->getMessage(),
			);

			if(isset(CZBXAPI::$userData['debug_mode']) && CZBXAPI::$userData['debug_mode']){
				$result['debug'] = $e->getTrace();
			}

			return $result;
		}
	}

}
?>
