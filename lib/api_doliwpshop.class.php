<?php
/* Copyright (C) 2020 Eoxia
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/custom/doliwpshop/class/api_doliwpshop.class.php
 * \ingroup doliwpshop
 * \brief   File for API management of WPshop object.
 */

/**
 * Class WPshopAPI
 */
class WPshopAPI {
	
	public static $base = '';

	/**
	 * Constructor
	 */
	 public function __construct() {
			global $conf;
		
			self::$base = $conf->global->WPSHOP_URL_WORDPRESS;
		
	 }
		 
	 /**
	 * Autoload a WPshopAPI class
	 *
	 * @param string $class Class name
	 */
	public static function autoloader($class) {
		// Check that the class starts with "Requests"
		if (strpos($class, 'WPshopAPI') !== 0) {
			return;
		}
		$file = str_replace('_', '/', $class);
		if (file_exists(dirname(__FILE__) . '/' . $file . '.php')) {
			require_once(dirname(__FILE__) . '/' . $file . '.php');
		}
	}

	/**
	 * Register the standard WPshopAPI autoloader
	 */
	public static function register_autoloader() {
		spl_autoload_register(array('WPshopAPI', 'autoloader'));
	}

	/**
	 * POST Request
	 *
	 * @param  string $end_point The url called.
	 * @param  array  $data      The form data.
	 * @param  string $method    the type of method, default POST.
	 *
	 * @return array|boolean   Returns the query data or false.
	 */
	public static function post( $end_point, $data = array(), $method = 'POST' ) {
		$data = json_encode( $data );
		global $conf;
		
		$api_url = $conf->global->WPSHOP_URL_WORDPRESS . $end_point;

		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $api_url); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'WPAPIKEY: ' . $conf->global->WPSHOP_TOKEN,
			'Content-Length: ' . strlen( $data ),
		) ); 
		
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
		$output = curl_exec($ch);

		curl_close($ch);

		if ($output === NULL) {
			return array(
				'status' => NULL,
			);
		}
		
		$output = json_decode( $output, true );

		if (json_last_error() != JSON_ERROR_NONE) {
			return array(
				'status' => false,
				'error_code' => json_last_error(),
				'error_message' => json_last_error_msg(),
			);
		}

		return array(
			'status' => true,
			'data' => $output,
		);
	}

	/**
	 * GET Request
	 *
	 * @param  string $end_point The url called.
	 *
	 * @return array|boolean   Returns the query data or false.
	 */
	public static function get( $end_point ) {
		global $conf;
		
		$api_url = $conf->global->WPSHOP_URL_WORDPRESS . $end_point;
		
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $api_url); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'WPAPIKEY: ' . $conf->global->WPSHOP_TOKEN,
		) ); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
		$output = curl_exec($ch); 
		curl_close($ch); 
		
		$output = json_decode( $output );
		
		return $output;
	}
}
