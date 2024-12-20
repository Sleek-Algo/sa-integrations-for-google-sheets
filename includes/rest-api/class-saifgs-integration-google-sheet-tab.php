<?php
/**
 * Handles the Integration Google Sheet Tab.
 *
 * @package SA Integrations For Google Sheets
 * @since 1.0.0
 */

namespace SAIFGS\RestApi;

if ( ! class_exists( '\SAIFGS\RestApi\SAIFGS_Integration_Google_Sheet_Tab' ) ) {

	/**
	 * Class Integration Google Sheet TabI
	 *
	 * Handles the Integration Edit Form API.
	 */
	class SAIFGS_Integration_Google_Sheet_Tab {

		// Traits used inside class.
		use \SAIFGS\Traits\SAIFGS_Singleton;
		use \SAIFGS\Traits\SAIFGS_Helpers;
		use \SAIFGS\Traits\SAIFGS_RestAPI;

		/**
		 * Base URL for the integrated form API route.
		 *
		 * @var string
		 */
		private static $saifgs_api_route_base_url = '/google-sheet-tab';

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
		 * Registers custom REST API routes for the plugin.
		 *
		 * This method registers a POST route for handling Get Google Sheets Tabs Coulmn.
		 * The route is associated with the `saifgs_get_google_sheets_tabs_coulmn`
		 * callback method and uses the `permissions` method to check for necessary permissions.
		 *
		 * The route URL is constructed using the base URL provided by `saifgs_get_api_base_url()`
		 * and the specific route defined by `self::$saifgs_api_route_base_url`.
		 *
		 * @return void
		 */
		public function saifgs_register_rest_routes() {

			register_rest_route(
				$this->saifgs_get_api_base_url(),
				self::$saifgs_api_route_base_url,
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'saifgs_get_google_sheets_tabs_coulmn' ),
					'permission_callback' => array( $this, 'saifgs_permissions' ),
				)
			);

		}

		/**
		 * Fetches columns of a specified Google Sheets tab.
		 *
		 * @param \WP_REST_Request $request REST API request object.
		 */
		public function saifgs_get_google_sheets_tabs_coulmn( \WP_REST_Request $request ) {
			// Fetch the columns of the specified Google Sheets tab.
			$google_sheet_column_list = $this->saifgs_get_google_sheets_tabs_column( $request );

			// Send the fetched columns as a JSON response.
			wp_send_json( $google_sheet_column_list );
		}

		/**
		 * Retrieves column headers from a specific Google Sheet tab.
		 *
		 * This function fetches the column headers from the specified range
		 * in a Google Sheet using the Google Sheets API.
		 *
		 * @param \WP_REST_Request $request The REST API request object containing
		 *                                   Google Sheet ID and selected tab.
		 *
		 * void Sends a JSON response with column data or an error message.
		 */
		public function saifgs_get_google_sheets_tabs_column( $request ) {

			$json_data = json_decode( $request->get_body(), true );

			$google_sheet_id           = sanitize_text_field( $json_data['google_sheet_data'] ) ?? '';
			$google_sheet_tab_selected = sanitize_text_field( $json_data['google_sheet_tab_selected'] ) ?? '';
			if ( empty( $google_sheet_id ) || empty( $google_sheet_tab_selected ) ) {
				wp_send_json_error( __( 'Missing required parameters: Google Sheet ID or range.', 'text-domain' ), 400 );
			}

			// Initialize Google Sheets API client.
			try {
				// If passed parameter is Array and Not String  || Creating Query URL.
				$request = 'https://sheets.googleapis.com/v4/spreadsheets/' . $google_sheet_id . '/values/' . $google_sheet_tab_selected . '!A1:YZ1';

				if ( class_exists( '\SAIFGS\Classes\SAIFGS_Google_Apis_Authenticator' ) ) {
					$client = \SAIFGS\Classes\SAIFGS_Google_Apis_Authenticator::get_instance();
				} else {
					$client = '';
				}
				$response = $client->saifgs_request( $request );

				// If There are no column title or First ROW is Empty Then Send a Arry with key without value.
				if ( ! isset( $response['values'][0] ) ) {
					return array(
						true,
						array(
							'A' => '',
							'B' => '',
							'C' => '',
							'D' => '',
							'E' => '',
							'F' => '',
							'G' => '',
							'H' => '',
							'I' => '',
							'J' => '',
							'K' => '',
							'L' => '',
							'M' => '',
							'N' => '',
							'O' => '',
							'P' => '',
							'Q' => '',
							'R' => '',
							'S' => '',
							'T' => '',
							'U' => '',
							'V' => '',
							'W' => '',
							'X' => '',
							'Y' => '',
							'Z' => '',
						),
					);
				}

				if ( empty( $response ) || empty( $response['values'][0] ) ) {
					wp_send_json_error( __( 'No data found in the specified range.', 'sa-integrations-for-google-sheets' ), 404 );
				}

				if ( ! empty( $response['values'][0] ) ) {
					$columns = $this->saifgs_map_google_sheet_columns( $response['values'][0] );
					wp_send_json( $columns );
				}
			} catch ( \Exception $e ) {
				// translators: %s will be replaced with the error message.
				$error_message = sprintf( __( 'Failed to fetch Google Sheets columns: %s', 'sa-integrations-for-google-sheets' ), esc_html( $e->getMessage() ) );
				wp_send_json_error( array( 'error' => $error_message ), 500 );
			}
		}

		/**
		 * Maps Google Sheets column headers to a structured array.
		 *
		 * @param array $header_row The first row of the sheet (column headers).
		 * @return array Structured column data.
		 */
		private function saifgs_map_google_sheet_columns( $header_row ) {
			$columns = array();

			if ( is_array( $header_row ) && count( $header_row ) > 0 ) {
				foreach ( $header_row as $index => $column_name ) {
					$column_location = $this->saifgs_get_column_location( $index );
					$columns[]       = array(
						'key'   => $index,
						'label' => $column_location . ' : ' . $column_name,
						'value' => $column_location,
					);
				}
			}
			return $columns;
		}

		/**
		 * Converts a column index to its Google Sheets column location (e.g., A, B, AA).
		 *
		 * @param int $index Zero-based column index.
		 * @return string Column location (e.g., A, B, Z, AA).
		 */
		private function saifgs_get_column_location( $index ) {
			$column = '';
			while ( $index >= 0 ) {
				$column = chr( $index % 26 + 65 ) . $column;
				$index  = intval( $index / 26 ) - 1;
			}

			// Append row number for A1 notation.
			return $column . '1';
		}
	}
}
