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
		public function saifgs_get_google_sheets_tabs( \WP_REST_Request $request ) {
			// Decode and sanitize input.
			$json_data = json_decode( $request->get_body(), true );

			if ( empty( $json_data['google_sheet_data'] ) ) {
				wp_send_json_error( __( 'Missing Google Sheet ID.', 'sa-integrations-for-google-sheets' ), 400 );
			}

			$google_sheet_id = sanitize_text_field( $json_data['google_sheet_data'] );
			if ( class_exists( '\SAIFGS\Classes\SAIFGS_Google_Apis_Authenticator' ) ) {
				$client = \SAIFGS\Classes\SAIFGS_Google_Apis_Authenticator::get_instance();
			} else {
				$client = '';
			}

			$url      = 'https://sheets.googleapis.com/v4/spreadsheets/' . $google_sheet_id;
			$response = $client->saifgs_request( $url );

			// Initialize Google Sheets API client.
			try {
				$google_sheet_tabs = array();
				$sheets            = $response['sheets'];
				foreach ( $sheets as $sheet ) {
					$google_sheet_tabs[] = array(
						'value' => sanitize_text_field( $sheet['properties']['title'] ),
						'label' => sanitize_text_field( $sheet['properties']['title'] ),
					);
				}
				wp_send_json( $google_sheet_tabs );
			} catch ( \Exception $e ) {
				// Handle API exceptions gracefully.
				// translators: %s will be replaced with the error message.
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
		private function saifgs_google_sheets_service() {
			// Initialize the Google Drive service.
			$google_sheets = array();

			try {
				// Fetch the list of spreadsheets from Google Drive.
				if ( class_exists( '\SAIFGS\Classes\SAIFGS_Google_Apis_Authenticator' ) ) {
					$client = \SAIFGS\Classes\SAIFGS_Google_Apis_Authenticator::get_instance();
				} else {
					$client = '';
				}

				$url      = 'https://www.googleapis.com/drive/v3/files';
				$args     = array();
				$response = $client->saifgs_request( $url );
				$files    = $response['files'];

				// Check if files not array or less then 0 then return.
				if ( ! is_array( $files ) && count( $files ) > 0 ) {
					return;
				}
				foreach ( $files as $file ) {
					// Add each spreadsheet to the result array.
					$google_sheets[] = array(
						'value' => $file['id'],
						'label' => $file['name'],
					);
				}
			} catch ( \Exception $e ) {
				// Handle API exceptions gracefully.
				// translators: %s will be replaced with the error message.
				$error_message = sprintf( __( 'Failed to fetch Google Sheets: %s', 'sa-integrations-for-google-sheets' ), esc_html( $e->getMessage() ) );
				wp_send_json_error( array( 'error' => $error_message ), 500 );
			}
			// Return the list of Google Sheets.
			return $google_sheets;
		}
	}
}
