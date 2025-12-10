<?php
/**
 * Handles the Integration Google Sheets.
 *
 * @package SA Integrations For Google Sheets
 * @since 1.0.0
 */

namespace SAIFGS\RestApi;

if ( ! class_exists( '\SAIFGS\RestApi\SAIFGS_Integration_Google_Sheets' ) ) {

	/**
	 * Class Integration Google Sheets.
	 *
	 * Handles the Integration Edit Form API.
	 */
	class SAIFGS_Integration_Google_Sheets {

		// Traits used inside class.
		use \SAIFGS\Traits\SAIFGS_Singleton;
		use \SAIFGS\Traits\SAIFGS_Helpers;
		use \SAIFGS\Traits\SAIFGS_RestAPI;

		/**
		 * Base URL for the integrated form API route.
		 *
		 * @var string
		 */
		private static $saifgs_api_route_base_url = '/google-drive-sheets';

		/**
		 * Constructor method for initializing class.
		 *
		 * This method hooks the `register_rest_routes` method into the `rest_api_init`
		 * action, which registers the custom REST API routes when the REST API is initialized.
		 *
		 * @return void
		 */
		public function __construct() {
			add_action( 'rest_api_init', array( $this, 'saifgs_register_rest_routes' ) );
		}

		/**
		 * Registers REST API routes for the Google Sheets integration.
		 *
		 * This method registers two REST API routes:
		 * - A GET route to fetch Google Sheets data.
		 * - A POST route to fetch Google Sheets tabs.
		 *
		 * @return void
		 */
		public function saifgs_register_rest_routes() {
			// Route for GET requests to fetch Google Sheets data.
			register_rest_route(
				$this->saifgs_get_api_base_url(),
				self::$saifgs_api_route_base_url,
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'saifgs_get_google_sheets' ),
					'permission_callback' => array( $this, 'saifgs_permissions' ),
				)
			);

			// Route for POST requests to fetch Google Sheets tabs.
			register_rest_route(
				$this->saifgs_get_api_base_url(),
				self::$saifgs_api_route_base_url,
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'saifgs_get_google_sheets_tabs' ),
					'permission_callback' => array( $this, 'saifgs_permissions' ),
				)
			);
		}

		/**
		 * Fetches all tabs (sheets) from a given Google Sheets document.
		 *
		 * @param \WP_REST_Request $request REST API request object.
		 */
		// public function saifgs_get_google_sheets_tabs( \WP_REST_Request $request ) {
		// 	// Decode and sanitize input.
		// 	$json_data = json_decode( $request->get_body(), true );

		// 	if ( empty( $json_data['google_sheet_data'] ) ) {
		// 		wp_send_json_error( __( 'Missing Google Sheet ID.', 'sa-integrations-for-google-sheets' ), 400 );
		// 	}

		// 	$google_sheet_id = sanitize_text_field( $json_data['google_sheet_data'] );
		// 	if ( class_exists( '\SAIFGS\Classes\SAIFGS_Google_Apis_Authenticator' ) ) {
		// 		$client = \SAIFGS\Classes\SAIFGS_Google_Apis_Authenticator::get_instance();
		// 	} else {
		// 		$client = '';
		// 	}

		// 	$url      = 'https://sheets.googleapis.com/v4/spreadsheets/' . $google_sheet_id;
		// 	$response = $client->saifgs_request( $url );

		// 	// Initialize Google Sheets API client.
		// 	try {
		// 		$google_sheet_tabs = array();
		// 		$sheets            = $response['sheets'];
		// 		foreach ( $sheets as $sheet ) {
		// 			$google_sheet_tabs[] = array(
		// 				'value' => sanitize_text_field( $sheet['properties']['title'] ),
		// 				'label' => sanitize_text_field( $sheet['properties']['title'] ),
		// 			);
		// 		}
		// 		wp_send_json( $google_sheet_tabs );
		// 	} catch ( \Exception $e ) {
		// 		// Handle API exceptions gracefully.
		// 		// translators: %s will be replaced with the error message.
		// 		$error_message = sprintf( __( 'Failed to fetch tabs from Google Sheets: %s', 'sa-integrations-for-google-sheets' ), esc_html( $e->getMessage() ) );
		// 		wp_send_json_error( array( 'error' => $error_message ), 500 );
		// 	}
		// }

		public function saifgs_get_google_sheets_tabs( \WP_REST_Request $request ) {
			// Decode and sanitize input.
			$json_data = json_decode( $request->get_body(), true );
		
			if ( empty( $json_data['google_sheet_data'] ) ) {
				wp_send_json_error( __( 'Missing Google Sheet ID.', 'sa-integrations-for-google-sheets' ), 400 );
			}
		
			$google_sheet_id = sanitize_text_field( $json_data['google_sheet_data'] );
			
			// =========== YEH LINE CHANGE HOGI ===========
			// Old code:
			// if ( class_exists( '\SAIFGS\Classes\SAIFGS_Google_Apis_Authenticator' ) ) {
			//     $client = \SAIFGS\Classes\SAIFGS_Google_Apis_Authenticator::get_instance();
			// } else {
			//     $client = '';
			// }
			
			// New code: Get active access token
			$access_token = $this->saifgs_get_active_access_token();
			if ( empty( $access_token ) ) {
				wp_send_json_error( __( 'Authentication failed. Please check your Google connection.', 'sa-integrations-for-google-sheets' ), 401 );
			}
			// =========== CHANGE END ===========
		
			$url = 'https://sheets.googleapis.com/v4/spreadsheets/' . $google_sheet_id;
			
			// =========== YEH LINE CHANGE HOGI ===========
			// Old code:
			// $response = $client->saifgs_request( $url );
			
			// New code: Use the access token directly
			$response = wp_remote_get( $url, array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
				),
				'timeout' => 15,
			) );
			
			if ( is_wp_error( $response ) ) {
				error_log( 'SAIFGS: Failed to fetch sheet tabs - ' . $response->get_error_message() );
				$error_message = sprintf( __( 'Failed to fetch tabs from Google Sheets: %s', 'sa-integrations-for-google-sheets' ), esc_html( $response->get_error_message() ) );
				wp_send_json_error( array( 'error' => $error_message ), 500 );
				return;
			}
			
			$response_code = wp_remote_retrieve_response_code( $response );
			$body = wp_remote_retrieve_body( $response );
			// =========== CHANGE END ===========
		
			// Handle authentication errors
			if ( $response_code === 401 ) {
				error_log( 'SAIFGS: Authentication failed (401) when fetching sheet tabs' );
				// If this was an OAuth token, mark it as expired
				if ( $this->saifgs_is_oauth_token() ) {
					update_option( 'saifgs_auto_connect_auth_expired', 'true' );
				}
				wp_send_json_error( __( 'Authentication expired. Please reconnect your Google account.', 'sa-integrations-for-google-sheets' ), 401 );
				return;
			}
		
			// Initialize Google Sheets API client.
			try {
				// =========== YEH LINE CHANGE HOGI ===========
				// Old code used $response directly
				// New code: Parse the JSON response
				$data = json_decode( $body, true );
				
				if ( ! isset( $data['sheets'] ) ) {
					error_log( 'SAIFGS: Invalid response structure when fetching tabs: ' . print_r( $data, true ) );
					wp_send_json_error( __( 'Invalid response from Google Sheets API.', 'sa-integrations-for-google-sheets' ), 500 );
					return;
				}
				
				$google_sheet_tabs = array();
				$sheets = $data['sheets'];
				// =========== CHANGE END ===========
				
				foreach ( $sheets as $sheet ) {
					$google_sheet_tabs[] = array(
						'value' => sanitize_text_field( $sheet['properties']['title'] ),
						'label' => sanitize_text_field( $sheet['properties']['title'] ),
					);
				}
				
				error_log( 'SAIFGS: Successfully fetched ' . count( $google_sheet_tabs ) . ' tabs from sheet: ' . $google_sheet_id );
				wp_send_json( $google_sheet_tabs );
				
			} catch ( \Exception $e ) {
				// Handle API exceptions gracefully.
				error_log( 'SAIFGS: Exception in saifgs_get_google_sheets_tabs - ' . $e->getMessage() );
				$error_message = sprintf( __( 'Failed to fetch tabs from Google Sheets: %s', 'sa-integrations-for-google-sheets' ), esc_html( $e->getMessage() ) );
				wp_send_json_error( array( 'error' => $error_message ), 500 );
			}
		}

		/**
		 * Retrieves a list of Google Sheets documents from Google Drive.
		 *
		 * This method initializes the Google Sheets service and fetches a list of Google Sheets documents
		 * from Google Drive. It sends this data as a JSON response.
		 *
		 * @param \WP_REST_Request $request The incoming REST request.
		 * @return void Sends a JSON response with the list of Google Sheets documents.
		 */
		public function saifgs_get_google_sheets( \WP_REST_Request $request ) {
			// Fetch the list of Google Sheets from the authenticated user's account.
			$google_sheet_list = $this->saifgs_google_sheets_service();

			// Send the list as a JSON response.
			wp_send_json( $google_sheet_list );
		}

		/**
		 * Initializes the Google Sheets service and retrieves a list of Google Sheets documents from Google Drive.
		 *
		 * This method sets up the Google Sheets API client and Google Drive service, then fetches a list of
		 * Google Sheets documents from Google Drive. It returns the list as an array.
		 *
		 * @return array An array of Google Sheets documents with their IDs and names.
		 */
		// private function saifgs_google_sheets_service() {
		// 	error_log( 'Run saifgs_google_sheets_service Function ' );
		// 	// Initialize the Google Drive service.
		// 	$google_sheets = array();

		// 	try {
		// 		// Fetch the list of spreadsheets from Google Drive.
		// 		if ( class_exists( '\SAIFGS\Classes\SAIFGS_Google_Apis_Authenticator' ) ) {
		// 			$client = \SAIFGS\Classes\SAIFGS_Google_Apis_Authenticator::get_instance();
		// 		} else {
		// 			$client = '';
		// 		}
		// 		error_log( 'saifgs_google_sheets_service client: ' . print_r( $client, true ) );

		// 		// Check if client is properly initialized.
		// 		if ( ! $client || ! method_exists( $client, 'saifgs_request' ) ) {
		// 			// Return empty array if client is not available.
		// 			return $google_sheets;
		// 		}

		// 		$url      = 'https://www.googleapis.com/drive/v3/files';
		// 		$args     = array();
		// 		$response = $client->saifgs_request( $url );

		// 		// Debug: Check response structure
		// 		error_log( 'Google Drive API Response: ' . print_r( $response, true ) );
        
		// 		// Check if response is valid and has 'files' key.
		// 		if ( ! is_array( $response ) || ! isset( $response['files'] ) ) {
		// 			// Return empty array if no files found or invalid response.
		// 			return $google_sheets;
		// 		}

		// 		$files    = $response['files'];

		// 		// Check if files not array or less then 0 then return.
		// 		if ( ! is_array( $files ) && count( $files ) > 0 ) {
		// 			return;
		// 		}
		// 		foreach ( $files as $file ) {
		// 			// Add each spreadsheet to the result array.
		// 			$google_sheets[] = array(
		// 				'value' => $file['id'],
		// 				'label' => $file['name'],
		// 			);
		// 		}
		// 	} catch ( \Exception $e ) {
		// 		// Handle API exceptions gracefully.
		// 		// translators: %s will be replaced with the error message.
		// 		$error_message = sprintf( __( 'Failed to fetch Google Sheets: %s', 'sa-integrations-for-google-sheets' ), esc_html( $e->getMessage() ) );
		// 		wp_send_json_error( array( 'error' => $error_message ), 500 );
		// 	}
		// 	// Return the list of Google Sheets.
		// 	return $google_sheets;
		// }

		private function saifgs_google_sheets_service() {
			error_log( 'SAIFGS: Run saifgs_google_sheets_service Function' );
			
			$google_sheets = array();
			$access_token = $this->saifgs_get_active_access_token(); // New method to get the right token
		
			if ( empty( $access_token ) ) {
				error_log( 'SAIFGS: No active access token found.' );
				return $google_sheets;
			}
		
			try {
				$url = 'https://www.googleapis.com/drive/v3/files';
				$args = array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $access_token,
					),
					'timeout' => 15,
				);
		
				// Add query parameters to fetch only Sheets files
				$url .= '?q=' . urlencode( "mimeType='application/vnd.google-apps.spreadsheet' and trashed=false" );
				$url .= '&fields=files(id, name)';
				$url .= '&pageSize=1000';
		
				$response = wp_remote_get( $url, $args );
		
				if ( is_wp_error( $response ) ) {
					error_log( 'SAIFGS: Google Drive API request failed - ' . $response->get_error_message() );
					return $google_sheets;
				}
		
				$response_code = wp_remote_retrieve_response_code( $response );
				$body = wp_remote_retrieve_body( $response );
		
				error_log( "SAIFGS: Google Drive API Response Code: $response_code" );
		
				if ( $response_code === 401 ) {
					// Token might be expired. If this was an OAuth token, mark it as expired.
					error_log( 'SAIFGS: Access token expired (401).' );
					if ( $this->saifgs_is_oauth_token() ) {
						update_option( 'saifgs_auto_connect_auth_expired', 'true' );
					}
					return $google_sheets;
				}
		
				if ( $response_code !== 200 ) {
					error_log( 'SAIFGS: Google Drive API Error Body: ' . $body );
					return $google_sheets;
				}
		
				$data = json_decode( $body, true );
		
				// Debug: Check response structure
				error_log( 'SAIFGS: Google Drive API Files Response: ' . print_r( $data, true ) );
		
				// Check if response is valid and has 'files' key.
				if ( ! is_array( $data ) || ! isset( $data['files'] ) ) {
					return $google_sheets;
				}
		
				$files = $data['files'];
		
				foreach ( $files as $file ) {
					// Add each spreadsheet to the result array.
					$google_sheets[] = array(
						'value' => sanitize_text_field( $file['id'] ),
						'label' => sanitize_text_field( $file['name'] ),
					);
				}
		
				error_log( 'SAIFGS: Successfully fetched ' . count( $google_sheets ) . ' spreadsheets.' );
		
			} catch ( \Exception $e ) {
				error_log( 'SAIFGS: Exception in saifgs_google_sheets_service - ' . $e->getMessage() );
			}
			
			// Return the list of Google Sheets.
			return $google_sheets;
		}
		
		// /**
		//  * Determines the correct active access token to use.
		//  * Priority: 1. Valid User OAuth Token, 2. Service Account Token.
		//  *
		//  * @return string|null The access token, or null if none found/valid.
		//  */
		// private function saifgs_get_active_access_token() {
		// 	// 1. Check for a valid OAuth token from the auto-connect system
		// 	$oauth_token = get_option( 'saifgs_auto_connect_token' );
		// 	$oauth_expired = get_option( 'saifgs_auto_connect_auth_expired', 'false' );
		
		// 	if ( ! empty( $oauth_token ) && $oauth_expired === 'false' ) {
		// 		// Optional: Perform a lightweight validation (e.g., check expiry time)
		// 		$expiry_time = get_option( 'saifgs_auto_connect_token_expiry', 0 );
		// 		if ( $expiry_time > ( time() + 300 ) ) { // 5-minute buffer
		// 			error_log( 'SAIFGS: Using valid OAuth access token.' );
		// 			return $oauth_token;
		// 		} else {
		// 			error_log( 'SAIFGS: OAuth token appears expired. Marking as expired.' );
		// 			update_option( 'saifgs_auto_connect_auth_expired', 'true' );
		// 		}
		// 	}
		
		// 	// 2. Fall back to the Service Account token
		// 	error_log( 'SAIFGS: Falling back to Service Account token.' );
		// 	if ( class_exists( '\SAIFGS\Classes\SAIFGS_Google_Apis_Authenticator' ) ) {
		// 		$client = \SAIFGS\Classes\SAIFGS_Google_Apis_Authenticator::get_instance();
		// 		return $client->saifgs_get_access_token(); // This generates/fetches the service account token
		// 	}
		
		// 	error_log( 'SAIFGS: No access token available.' );
		// 	return null;
		// }
		
		// /**
		//  * Utility function to check if the currently used token is from OAuth.
		//  * (Simplistic check based on the source option).
		//  *
		//  * @return bool
		//  */
		// private function saifgs_is_oauth_token() {
		// 	$token = get_option( 'saifgs_auto_connect_token' );
		// 	return ! empty( $token );
		// }
	}
}
