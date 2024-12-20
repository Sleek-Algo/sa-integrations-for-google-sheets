<?php
/**
 * Handles the Integration Form API.
 *
 * @package SA Integrations For Google Sheets
 * @since 1.0.0
 */

namespace SAIFGS\RestApi;

if ( ! class_exists( '\SAIFGS\RestApi\SAIFGS_Integration_Form_API' ) ) {

	/**
	 * Class Integration Form API
	 *
	 * Handles the Integration Edit Form API.
	 */
	class SAIFGS_Integration_Form_API {

		// Traits used inside class.
		use \SAIFGS\Traits\SAIFGS_Singleton;
		use \SAIFGS\Traits\SAIFGS_Helpers;
		use \SAIFGS\Traits\SAIFGS_RestAPI;

		/**
		 * Base URL for the integrated form API route.
		 *
		 * @var string
		 */
		private static $saifgs_api_route_base_url = '/integrated-form';

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
		 * This method registers a POST route for handling save and update integrations.
		 * The route is associated with the `saifgs_save_and_update_integration_method`
		 * callback method and uses the `saifgs_permissions` method to check for necessary permissions.
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
					'callback'            => array( $this, 'saifgs_save_and_update_integration_method' ),
					'permission_callback' => array( $this, 'saifgs_permissions' ),
				)
			);
		}

		/**
		 * Saves or updates the integration data for Google Sheets mapping.
		 *
		 * This method handles both the saving of new integration data and updating existing
		 * integrations. It processes the incoming request, serializes the integration map,
		 * calculates the column range for the Google Sheets integration, and interacts
		 * with the database to either insert or update the relevant records.
		 *
		 * @param \WP_REST_Request $request The REST API request object.
		 *
		 * @return void
		 */
		public function saifgs_save_and_update_integration_method( \WP_REST_Request $request ) {
			global $wpdb;

			$data = json_decode( $request->get_body() );

			$title                   = isset( $data->saifgs_title ) && ! empty( $data->saifgs_title ) ? sanitize_text_field( $data->saifgs_title ) : '';
			$source_plugin           = isset( $data->saifgs_source_plugin ) && ! empty( $data->saifgs_source_plugin ) ? sanitize_text_field( $data->saifgs_source_plugin ) : '';
			$selected_plugin_id_data = isset( $data->selected_plugin_id_data ) && ! empty( $data->selected_plugin_id_data ) ? sanitize_text_field( $data->selected_plugin_id_data ) : '';
			$spreadsheet_worksheet   = isset( $data->saifgs_spreadsheet_worksheet ) && ! empty( $data->saifgs_spreadsheet_worksheet ) ? sanitize_text_field( $data->saifgs_spreadsheet_worksheet ) : '';
			$source_intity           = isset( $data->saifgs_source_intity ) && ! empty( $data->saifgs_source_intity ) ? sanitize_text_field( $data->saifgs_source_intity ) : '';
			$order_status            = isset( $data->saifgs_order_status ) && ! empty( $data->saifgs_order_status ) ? maybe_serialize( $data->saifgs_order_status ) : '';
			$is_form                 = isset( $data->add_integration_form ) && ! empty( $data->add_integration_form ) ? sanitize_text_field( $data->add_integration_form ) : '';
			$spreadsheet_tab         = isset( $data->saifgs_spreadsheet_tab ) ? sanitize_text_field( $data->saifgs_spreadsheet_tab ) : '';
			$is_map_fieled_changed   = isset( $data->is_mapping_fields_changed ) ? sanitize_text_field( $data->is_mapping_fields_changed ) : false;
			$disable_integration     = isset( $data->saifgs_disable_integration ) && ! empty( $data->saifgs_disable_integration ) ? sanitize_text_field( $data->saifgs_disable_integration ) : 'no';
			$formid                  = isset( $data->formid ) ? intval( $data->formid ) : '';

			if ( empty( $title ) || empty( $source_plugin ) || empty( $spreadsheet_worksheet ) ) {
				wp_send_json_error( __( 'Missing required fields.', 'sa-integrations-for-google-sheets' ) );
			}

			if ( '' !== $title && '' !== $selected_plugin_id_data || '' !== $source_plugin && '' !== $spreadsheet_worksheet && '' !== $source_intity && '' !== $spreadsheet_tab ) {

				$integration_map = maybe_serialize( $data->integration_map );
				$sheet_indices   = array_column( $data->integration_map, 'google_sheet_index' );
				$first_index     = reset( $sheet_indices );
				$last_index      = end( $sheet_indices );
				$column_range    = preg_replace( '/\d+/', '', $first_index ) . ':' . preg_replace( '/\d+/', '', $last_index );
				$table_name      = $wpdb->prefix . 'saifgs_integrations';
				if ( 'add_integration_Form' !== $is_form ) {
						// @codingStandardsIgnoreStart
						$return = $wpdb->update(
							$table_name,
							array(
								'title'                   	=> $title,
								'plugin_id'               	=> $source_plugin,
								'source_id'              	=> $source_intity,
								'order_status'            	=> $order_status,
								'google_work_sheet_id'    	=> $spreadsheet_worksheet,
								'google_sheet_tab_id'     	=> $spreadsheet_tab,
								'google_sheet_column_map' 	=> $integration_map,
								'google_sheet_column_range' => $column_range, 
								'disable_integration' 		=> $disable_integration, 
							),
							array( 'id' => $formid )
						);
						// @codingStandardsIgnoreEnd
				} else {
					// @codingStandardsIgnoreStart
					$return = $wpdb->insert(
						$table_name,
						array(
							'title'                     => $title,
							'plugin_id'                 => $selected_plugin_id_data,
							'source_id'                 => $source_intity,
							'order_status'              => $order_status,
							'google_work_sheet_id'      => $spreadsheet_worksheet,
							'google_sheet_tab_id'       => $spreadsheet_tab,
							'google_sheet_column_map'   => $integration_map,
							'google_sheet_column_range' => $column_range,
							'disable_integration' 		=> $disable_integration,
						)
					);
					// @codingStandardsIgnoreEnd
				}

				if ( false === $return ) {
					wp_send_json_error( __( 'Failed to save integration.', 'sa-integrations-for-google-sheets' ) );
				}
			}
			// API Response.
			wp_send_json( __( 'Integration saved successfully.', 'sa-integrations-for-google-sheets' ) );
		}
	}
}
