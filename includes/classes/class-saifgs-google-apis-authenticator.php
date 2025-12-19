<?php
/**
 * SAIFGS_Google_Apis_Authenticator class.
 *
 * @package SA Integrations For Google Sheets
 */

namespace SAIFGS\Classes;

use \SAIFGS\Libraries\Firebase\JWT\JWT;

if ( ! class_exists( '\SAIFGS\Classes\SAIFGS_Google_Apis_Authenticator' ) ) {

	/**
	 * Google API's authenticator function.
	 *
	 * This class content Google API's authenticator functions.
	 *
	 * @copyright  sleekalgo
	 * @version    Release: 1.0.0
	 * @link       https://www.sleekalgo.com
	 * @package    SA Integrations For Google Sheets
	 * @since      Class available since Release 1.0.0
	 */
	class SAIFGS_Google_Apis_Authenticator {


		/**
		 * Traits used inside class.
		 */
		use \SAIFGS\Traits\SAIFGS_Singleton;
		use \SAIFGS\Traits\SAIFGS_Helpers;

		/**
		 * Option key for storing Google API access token.
		 *
		 * @var string
		 */
		private $saifgs_token_option_key = 'saifgs_google_api_access_token';

		/**
		 * Holds the Google credentials data.
		 *
		 * @var string
		 */
		public $saifgs_google_credentials;

		/**
		 * A constructor to prevent this class from being loaded more than once.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function __construct() {
			$google_credentials_file = get_option( 'saifgs_google_credentials_file', null );
			if ( ! empty( $google_credentials_file['data'] ) ) {
				$credentials                     = $google_credentials_file['data'];
				$this->saifgs_google_credentials = $credentials;
			}
		}

		/**
		 * Exchange authorization code for an access token.
		 *
		 * @return bool
		 */
		public function saifgs_fetch_access_token() {
			if ( ! isset( $this->saifgs_google_credentials->client_email ) || ! isset( $this->saifgs_google_credentials->private_key ) ) {
				return $this->saifgs_get_stored_access_token();
			}

			// Creating payload.
			$payload = array(
				'iss'   => $this->saifgs_google_credentials->client_email,
				'scope' => 'https://www.googleapis.com/auth/drive https://www.googleapis.com/auth/spreadsheets',
				'aud'   => 'https://oauth2.googleapis.com/token',
				'exp'   => time() + 3600,
				'iat'   => time(),
			);

			$jwt = JWT::encode( $payload, $this->saifgs_google_credentials->private_key, 'RS256' );

			$args = array(
				'headers' => array(),
				'body'    => array(
					'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
					'assertion'  => $jwt,
				),
			);

			// Token url Remote request.
			$response = wp_remote_post( 'https://oauth2.googleapis.com/token', $args );

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( isset( $body['access_token'] ) ) {

				// Token Expiry Tiem is updated before Saving and it is necessary.
				$body['expires_in'] = time() + $body['expires_in'];
				$this->saifgs_store_access_token( $body );
			}

			return $this->saifgs_get_stored_access_token();
		}

		/**
		 * Get the stored access token.
		 *
		 * @return array|null
		 */
		private function saifgs_get_stored_access_token() {
			$token_data = get_option( $this->saifgs_token_option_key );
			if ( $token_data ) {
				$token_data = json_decode( $token_data, true );
			}
			return $token_data;
		}

		/**
		 * Stores the access token in the WordPress database.
		 *
		 * @param array $token_data Token data to store.
		 */
		private function saifgs_store_access_token( $token_data ) {
			update_option( $this->saifgs_token_option_key, wp_json_encode( $token_data ), false ); // Set autoload to false to avoid unnecessary database queries on every page load.
		}

		/**
		 * Get the access token for API requests.
		 *
		 * @return string|null
		 */
		public function saifgs_get_access_token() {
			$token_data = $this->saifgs_get_stored_access_token();

			if ( $token_data && isset( $token_data['expires_in'] ) && time() < $token_data['expires_in'] ) {
				return $token_data['access_token'];
			}

			// Generate a new token if expired or not stored.
			$token_data = $this->saifgs_fetch_access_token();

			return $token_data['access_token'] ?? null;
		}

		/**
		 * Makes an authenticated request to the Google API.
		 *
		 * @param string $request_url  The URL to make the request to.
		 * @param array  $request_args Optional. Arguments for the request.
		 * @param string $request_type Optional. Type of request ('get' or 'post'). Default 'get'.
		 *
		 * @return array|null Decoded JSON response or null on failure.
		 */
		public function saifgs_request( $request_url, $request_args = array(), $request_type = 'get' ) {
			$access_token = $this->saifgs_get_access_token();
			if ( ! $access_token ) {
				return null;
			}

			$args = array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
				),
			);

			// if Headers provided han append Authorization.
			if ( isset( $request_args['headers'] ) ) {
				$request_args['headers']['Authorization'] = 'Bearer ' . $access_token;
			}

			// if optional headers provided than will be merged with default headers.
			if ( is_array( $request_args ) && count( $request_args ) > 0 ) {
				$args = array_merge( $args, $request_args );
			}

			$response = null;
			if ( 'post' === $request_type ) {
				$response = wp_remote_post( $request_url, $args );
			} else {
				$response = wp_remote_get( $request_url, $args );
			}

			if ( ! is_null( $response ) && is_wp_error( $response ) ) {
				return null;
			}

			// Handle expired token error and retry.
			if ( ! is_null( $response ) && wp_remote_retrieve_response_code( $response ) === 401 ) {
				// Retry request.
				return $this->saifgs_request( $request_url, $request_args, $request_type );
			}
			return json_decode( wp_remote_retrieve_body( $response ), true );
		}

		/**
		 * Get active access token (supports all methods)
		 */
		public function saifgs_get_active_access_token() {
			error_log('SAIFGS AUTHENTICATOR: Getting active access token');
			
			// Priority 1: Client Credentials OAuth token
			$client_credentials_token = get_option( 'saifgs_client_credentials_access_token' );
			$client_credentials_expiry = get_option( 'saifgs_client_credentials_token_expiry' );
			
			error_log('SAIFGS AUTHENTICATOR: Client credentials token exists: ' . (!empty($client_credentials_token) ? 'YES' : 'NO'));
			error_log('SAIFGS AUTHENTICATOR: Client credentials expiry: ' . ($client_credentials_expiry ? date('Y-m-d H:i:s', $client_credentials_expiry) : 'NOT SET'));
			error_log('SAIFGS AUTHENTICATOR: Current time: ' . date('Y-m-d H:i:s', time()));
			
			if ( ! empty( $client_credentials_token ) && $client_credentials_expiry > time() ) {
				error_log( 'SAIFGS AUTHENTICATOR: Using client credentials OAuth token' );
				return $client_credentials_token;
			} else {
				error_log('SAIFGS AUTHENTICATOR: Client credentials token expired or not available');
			}
			
			// Priority 2: Auto Connect OAuth token
			$auto_connect_token = get_option( 'saifgs_auto_connect_token' );
			$auto_connect_expired = get_option( 'saifgs_auto_connect_auth_expired', 'false' );
			
			error_log('SAIFGS AUTHENTICATOR: Auto connect token exists: ' . (!empty($auto_connect_token) ? 'YES' : 'NO'));
			error_log('SAIFGS AUTHENTICATOR: Auto connect expired status: ' . $auto_connect_expired);
			
			if ( ! empty( $auto_connect_token ) && $auto_connect_expired === 'false' ) {
				$expiry_time = get_option( 'saifgs_auto_connect_token_expiry', 0 );
				error_log('SAIFGS AUTHENTICATOR: Auto connect token expiry: ' . ($expiry_time ? date('Y-m-d H:i:s', $expiry_time) : 'NOT SET'));
				
				if ( $expiry_time > ( time() + 300 ) ) {
					error_log( 'SAIFGS AUTHENTICATOR: Using auto connect OAuth token' );
					return $auto_connect_token;
				} else {
					error_log('SAIFGS AUTHENTICATOR: Auto connect token expired');
				}
			}
			
			// // Priority 3: Service Account token
			// error_log( 'SAIFGS AUTHENTICATOR: Falling back to Service Account token' );
			// return $this->saifgs_get_access_token(); // Your existing method

			// If no valid OAuth token is available, return null
			error_log('SAIFGS AUTHENTICATOR: No valid OAuth token available.');
			return null;
		}
	}
}
