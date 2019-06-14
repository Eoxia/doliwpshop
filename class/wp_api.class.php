<?php
/**
 * API client for the WordPress JSON REST API
 *
 * @package WordPress API Client
 */

dol_include_once('wpshop/vendor/autoload.php');

/**
 * API client for the WordPress JSON REST API
 *
 * @package WordPress API Client
 */
class WPAPI {
	/**
	 * API base URL
	 *
	 * @var string
	 */
	public $base = '';

	/**
	 * Available collections
	 *
	 * @var array
	 */
	protected $collections = array();

	/**
	 * Authentication bits
	 * @var array
	 */
	protected $auth = array();
	
	private $provider;

	// This is very un-HATEOAS, but it also means one less request
	const ROUTE_INDEX = '/';

	/**
	 * Constructor
	 * @param string $base Base URL for the API
	 * @param string|null $username Username to connect as, empty to skip authentication
	 * @param string|null $password Password for the user
	 */
	public function __construct() {
		global $conf;
		
		$this->base = $conf->global->WPSHOP_URL_WORDPRESS;
		
		$this->provider = new \League\OAuth2\Client\Provider\GenericProvider([
			'clientId'                => $conf->global->WPSHOP_CLIENT_ID,    // The client ID assigned to you by the provider
			'clientSecret'            => $conf->global->WPSHOP_CLIENT_SECRET,   // The client password assigned to you by the provider
			'redirectUri'             => 'http://127.0.0.1/dolibarr/custom/wpshop/admin/setup.php?check=true',
			'urlAuthorize'            => $this->base . '/oauth/authorize',
			'urlAccessToken'          => $this->base . '/oauth/token',
			'urlResourceOwnerDetails' => $this->base . '/oauth/me'
		]);
		
		if ( $_GET['check'] == true ) {
			$this->handleOauth();
		}
	}
	
	public function handleOauth() {
		if (!isset($_GET['code'])) {
				$authorizationUrl = $this->provider->getAuthorizationUrl();
				$_SESSION['oauth2state'] = $this->provider->getState();
				header('Location: ' . $authorizationUrl);
		} elseif (empty($_GET['state']) || (isset($_SESSION['oauth2state']) && $_GET['state'] !== $_SESSION['oauth2state'])) {

    if (isset($_SESSION['oauth2state'])) {
        unset($_SESSION['oauth2state']);
    }
    
    exit('Invalid state');
		} else {
			try {
				$accessToken = $this->provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);
				$_SESSION['oauth_token'] = $accessToken->getToken();
			} catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
				// Failed to get the access token or user details.
				unset( $_SESSION['oauth_token'] );
			}
		}
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
	 * Get the default Requests options
	 *
	 * @return array Options to pass to Requests
	 */
	public function getDefaultOptions() {
		$options = array(
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
			)
			
		);
		if ( ! empty( $this->auth ) )
			$options['auth'] = $this->auth;

		return $options;
	}

	/**
	 * Set authentication parameter
	 */
	public function setAuth( $auth ) {
		$this->auth = $auth;
	}

	/**
	 * Send a HTTP request
	 */
	public function request($endpoint, $headers = array(), $data = array(), $type = Requests::GET, $options = array()) {
		$url = $this->base . $endpoint;
		$options = array_merge($this->getDefaultOptions(), $options);
		$options['body'] = json_encode( $data );
		$request = $this->provider->getAuthenticatedRequest( $type, $url, $_SESSION['oauth_token'], $options );
		return $this->provider->getParsedResponse($request);
	}
	/**#@-*/
}
