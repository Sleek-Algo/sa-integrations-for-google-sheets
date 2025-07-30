<?php
/**
 * Handles the Integration Edit Form API.
 *
 * @package SA Integrations For Google Sheets
 * @since 1.0.0
 */

namespace SAIFGS\RestApi;

if ( ! class_exists( '\SAIFGS\RestApi\SAIFGS_Integration_Edit_Form_API' ) ) {

	/**
	 * Class Integration Edit Form API
	 *
	 * Handles the Integration Edit Form API.
	 */
	class SAIFGS_Integration_Edit_Form_API {

		// Traits used inside class.
		use \SAIFGS\Traits\SAIFGS_Singleton;
		use \SAIFGS\Traits\SAIFGS_Helpers;
		use \SAIFGS\Traits\SAIFGS_RestAPI;

		/**
		 * Base URL for the integrated edit form API route.
		 *
		 * @var string
		 */
		private static $saifgs_api_route_base_url = '/integrated-edit-form';

		/**
		 * Constructor method for initializing class.
		 *
		 * This method hooks the `saifgs_register_rest_routes` method into the `rest_api_init`
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
		 * This method registers a POST route for handling Save Integration Edit Form Method.
		 * The route is associated with the `saifgs_save_integration_edit_form_method`
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
					'callback'            => array( $this, 'saifgs_save_integration_edit_form_method' ),
					'permission_callback' => array( $this, 'saifgs_permissions' ),
				)
			);
		}

		/**
		 * Handles saving and editing Google Sheets integration via a REST API request.
		 *
		 * @param \WP_REST_Request $request The REST API request object.
		 *
		 * @throws \Exception If any errors occur during the Google Sheets integration process.
		 */
		public function saifgs_save_integration_edit_form_method( \WP_REST_Request $request ) {
			global $wpdb;

			$get_body = $request->get_body();
			$data     = json_decode( $get_body );
			$form_id  = isset( $data->form_id ) && ! empty( $data->form_id ) ? absint( $data->form_id ) : 0;

			if ( empty( $form_id ) ) {
				wp_send_json_error(
					array( 'error' => esc_html__( 'Form ID is required.', 'sa-integrations-for-google-sheets' ) ),
					400
				);
			}

			// Set cache key and group.
			$cache_key   = 'saifgs_integration_' . $form_id;
			$cache_group = 'saifgs_integrations';

			// Try to get cached data first.
			$results = wp_cache_get( $cache_key, $cache_group );

			// If not found in cache, fetch from database.
			if ( false === $results ) {
				$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
					$wpdb->prepare(
						"SELECT `id`, `title`, `plugin_id`, `source_id`, `order_status`, 
							`google_work_sheet_id`, `google_sheet_tab_id`, `google_sheet_column_map`, 
							`google_sheet_column_range`, `disable_integration`, `created_at` 
						FROM `{$wpdb->prefix}saifgs_integrations` 
						WHERE `id` = %d",
						$form_id
					)
				);

				// Cache the results for 1 hour (adjust as needed).
				wp_cache_set( $cache_key, $results, $cache_group, HOUR_IN_SECONDS );
			}

			if ( empty( $results ) ) {
				wp_send_json_error(
					array( 'error' => esc_html__( 'No integration found for the provided form ID.', 'sa-integrations-for-google-sheets' ) ),
					404
				);
			}
			$result = $results[0];

			// If passed parameter is Array and Not String  || Creating Query URL.
			$request = 'https://sheets.googleapis.com/v4/spreadsheets/' . $result->google_work_sheet_id . '/values/' . $result->google_sheet_tab_id . '!A1:YZ1';

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

			if ( ! empty( $form_id ) ) {
				if ( is_array( $results ) && count( $results ) > 0 ) {
					$formatted_data            = array(
						'id'                   => $result->id,
						'title'                => $result->title,
						'plugin_id'            => $result->plugin_id,
						'source_id'            => absint( $result->source_id ),
						'order_status'         => maybe_unserialize( $result->order_status ),
						'google_work_sheet_id' => $result->google_work_sheet_id,
						'google_sheet_tab_id'  => $result->google_sheet_tab_id,
						'disable_integration'  => $result->disable_integration,
						'created_at'           => $result->created_at,
					);
					$google_sheet_mapping_data = maybe_unserialize( $result->google_sheet_column_map );

					try {
						if ( ! empty( $response ) ) {
							$header_row      = $response['values'][0];
							$count_value     = count( $header_row );
							$formatted_count = count( $google_sheet_mapping_data );
							$is_value_extra  = $count_value - $formatted_count;
							$check           = $is_value_extra > 0;
							if ( $is_value_extra < 0 ) {
								// Trim excess columns from mapping data.
								$formatted_data['google_sheet_column_map'] = array_slice( $google_sheet_mapping_data, 0, $formatted_count + $is_value_extra );
							} elseif ( $check ) {
								// Add missing columns.
								$columns    = array();
								$last_key   = end( $google_sheet_mapping_data )->key;
								$column_map = array_column( $google_sheet_mapping_data, 'google_sheet_index' );

								foreach ( $header_row as $index => $column_name ) {
									$column_location = chr( 65 + $index ) . '1';

									if ( ! in_array( $column_location, $column_map, true ) ) {
										$last_key++;
										$google_sheet_mapping_data[] = (object) array(
											'key' => $last_key,
											'google_sheet_index' => $column_location,
											'source_filed_index' => '',
										);
									}
								}

								$formatted_data['google_sheet_column_map'] = $google_sheet_mapping_data;
							} else {
								$formatted_data['google_sheet_column_map'] = $google_sheet_mapping_data;
							}
						}
					} catch ( \Exception $e ) {
						// Return an error response if the Google API call fails.
						// translators: %s will be replaced with the error message.
						$error_message = sprintf( __( 'Failed to fetch Google Sheet data: %s', 'sa-integrations-for-google-sheets' ), esc_html( $e->getMessage() ) );
						wp_send_json_error( array( 'error' => $error_message ), 500 );
					}
				}

				// Cache the final formatted data for quick retrieval.
				$formatted_cache_key = 'saifgs_formatted_' . $form_id;
				wp_cache_set( $formatted_cache_key, $formatted_data, $cache_group, 30 * MINUTE_IN_SECONDS );

				wp_send_json( $formatted_data );
			}
		}
	}
}
