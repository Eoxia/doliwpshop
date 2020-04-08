<?php
/**
 * Gestion des requêtes.
 *
 * @author    Eoxia <dev@eoxia.com>
 * @copyright (c) 2011-2019 Eoxia <dev@eoxia.com>.
 *
 * @license   AGPLv3 <https://spdx.org/licenses/AGPL-3.0-or-later.html>
 *
 * @package   doli_wpshop
 *
 * @since     0.2.0
 */

/**
 * Gestion des requêtes.
 */
class WPAPI {
	
	public static $base = '';

	/**
	 * Le constructeur
	 *
	 * @since 0.2.0
	 */
	 public function __construct() {
			global $conf;
		
			self::$base = $conf->global->WPSHOP_URL_WORDPRESS;
		
	 }
		 
	 /**
	 * Autoload a WPAPI class
	 *
	 * @param string $class Class name
	 */
	public static function autoloader($class) {
		// Check that the class starts with "Requests"
		if (strpos($class, 'WPAPI') !== 0) {
			return;
		}
		$file = str_replace('_', '/', $class);
		if (file_exists(dirname(__FILE__) . '/' . $file . '.php')) {
			require_once(dirname(__FILE__) . '/' . $file . '.php');
		}
	}
	/**
	 * Register the standard WPAPI autoloader
	 */
	public static function register_autoloader() {
		spl_autoload_register(array('WPAPI', 'autoloader'));
	}

	/**
	 * Requête POST.
	 *
	 * @since 0.2.0
	 *
	 * @param  string $end_point L'url a appeler.
	 * @param  array  $data      Les données du formulaire.
	 * @param  string $method    le type de la méthode.
	 *
	 * @return array|boolean   Retournes les données de la requête ou false.
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
	 * Appel la méthode PUT
	 *
	 * @since 2.0.0
	 *
	 * @param  string $end_point L'url a appeler.
	 * @param  array  $data      Les données du formulaire.
	 *
	 * @return array|boolean   Retournes les données de la requête ou false.
	 */
	public static function put( $end_point, $data ) {
		return Request_Util::post( $end_point, $data, 'PUT' );
	}

	/**
	 * Requête GET.
	 *
	 * @since 2.0.0
	 *
	 * @param string $end_point L'url a appeler.
	 *
	 * @return array|boolean    Retournes les données de la requête ou false.
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
