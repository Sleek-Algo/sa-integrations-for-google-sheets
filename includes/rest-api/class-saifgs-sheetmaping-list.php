<?php
/**
 * Handles the Sheetmaping List.
 *
 * @package SA Integrations For Google Sheets
 * @since 1.0.0
 */

namespace SAIFGS\RestApi;

if ( ! class_exists( '\SAIFGS\RestApi\SAIFGS_Sheetmaping_List' ) ) {

	/**
	 * Class Sheetmaping List.
	 *
	 * Handles the Sheetmaping List
	 */
	class SAIFGS_Sheetmaping_List {

		// Traits used inside class.
		use \SAIFGS\Traits\SAIFGS_Singleton;
		use \SAIFGS\Traits\SAIFGS_Helpers;
		use \SAIFGS\Traits\SAIFGS_RestAPI;

		/**
		 * Base URL for the integrated form API route.
		 *
		 * @var string
		 */
		private static $saifgs_api_route_base_url = '/integration-list';

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
		 * Registers REST API routes for integration data handling.
		 *
		 * This method registers two REST API routes:
		 * 1. A GET route to retrieve integration data. It uses the 'saifgs_get_integration_data' callback method
		 *    and requires proper permissions as checked by the 'saifgs_permissions' callback method.
		 * 2. A POST route to delete integration data. It uses the 'saifgs_delete_integration_data' callback method
		 *    and also requires proper permissions as checked by the 'saifgs_permissions' callback method.
		 *
		 * The API base URL and route base URL are retrieved from the class properties.
		 *
		 * @return void
		 */
		public function saifgs_register_rest_routes() {
			register_rest_route(
				$this->saifgs_get_api_base_url(),
				self::$saifgs_api_route_base_url,
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'saifgs_get_integration_data' ),
					'permission_callback' => array( $this, 'saifgs_permissions' ),
				)
			);
			register_rest_route(
				$this->saifgs_get_api_base_url(),
				self::$saifgs_api_route_base_url,
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'saifgs_delete_integration_data' ),
					'permission_callback' => array( $this, 'saifgs_permissions' ),
				)
			);
		}

		/**
		 * Handles the deletion of integration data from the database.
		 *
		 * This method deletes a record from the `saifgs_integrations` table based on the provided integration ID.
		 * It reads the ID from the request body and performs a DELETE operation on the database.
		 *
		 * @param \WP_REST_Request $request The REST API request object containing the data for deletion.
		 *
		 * @return void
		 */
		public function saifgs_delete_integration_data( \WP_REST_Request $request ) {
			global $wpdb;

			// Decode the request body and sanitize the data.
			$data           = json_decode( $request->get_body(), true );
			$integration_id = isset( $data['id'] ) ? absint( $data['id'] ) : 0; // Sanitize and validate ID.

			// Check if ID is valid.
			if ( $integration_id <= 0 ) {
				wp_send_json_error( __( 'Invalid integration ID provided.', 'sa-integrations-for-google-sheets' ), 400 );
				return;
			}

			$where_condition = array(
				'id' => $integration_id,
			);

			$result = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"DELETE FROM `{$wpdb->prefix}saifgs_integrations` WHERE `id` = %s",
					$where_condition['id']
				)
			);

			// Check for errors or success.
			if ( false === $result ) {
				wp_send_json_error( __( 'An error occurred while deleting the integration.', 'sa-integrations-for-google-sheets' ) );
			} elseif ( 0 === $result ) {
				wp_send_json_error( __( 'No integration found with the provided ID.', 'sa-integrations-for-google-sheets' ) );
			} else {
				wp_send_json( __( 'Integration deleted successfully.', 'sa-integrations-for-google-sheets' ) );
			}
		}

		/**
		 * Handles the retrieval of integration data based on provided query parameters.
		 *
		 * This function fetches integration data from the database and Google Sheets based on filters applied
		 * through query parameters. It supports pagination and various filters including title, date range,
		 * and plugin type. The function also constructs URLs for Google Sheets and retrieves associated metadata.
		 *
		 * @param \WP_REST_Request $request The request object containing query parameters.
		 *
		 * @return Outputs JSON data with integration details, pagination info, and total count.
		 */
		public function saifgs_get_integration_data( \WP_REST_Request $request ) {
			global $wpdb;
			$param_data = $request->get_query_params();

			// Sanitize input parameters.
			$integrigration_api_limit     = ( isset( $param_data['limit'] ) ) ? absint( $param_data['limit'] ) : 10;
			$integration_api_current_page = ( isset( $param_data['current_page'] ) ) ? absint( $param_data['current_page'] ) : 1;
			$filter_title                 = ( isset( $param_data['filter_title'] ) ) ? sanitize_text_field( $param_data['filter_title'] ) : '';
			$filter_start_date            = ( isset( $param_data['filter_start_date'] ) ) ? sanitize_text_field( $param_data['filter_start_date'] ) : '';
			$filter_end_date              = ( isset( $param_data['filter_end_date'] ) ) ? sanitize_text_field( $param_data['filter_end_date'] ) : '';
			$plugin_filter                = isset( $param_data['plugin_filter'] ) && 'all' !== $param_data['plugin_filter'] ? sanitize_key( $param_data['plugin_filter'] ) : '';

			// Generate cache key based on all parameters.
			$cache_key   = 'saifgs_integration_data_' . md5(
				$integrigration_api_limit . '_' .
				$integration_api_current_page . '_' .
				$filter_title . '_' .
				$filter_start_date . '_' .
				$filter_end_date . '_' .
				$plugin_filter
			);
			$cache_group = 'saifgs_integrations';

			// Try to get cached data first.
			$cached_data = wp_cache_get( $cache_key, $cache_group );

			if ( false !== $cached_data ) {
				return new \WP_REST_Response( $cached_data, 200 );
			}

			$offset = ( $integration_api_current_page - 1 ) * $integrigration_api_limit;

			// Validate dates if provided.
			if ( ! empty( $filter_start_date ) && ! strtotime( $filter_start_date ) ) {
				return new \WP_Error( 'invalid_date', __( 'The start date is invalid.', 'sa-integrations-for-google-sheets' ), array( 'status' => 400 ) );
			}
			if ( ! empty( $filter_end_date ) && ! strtotime( $filter_end_date ) ) {
				return new \WP_Error( 'invalid_date', __( 'The end date is invalid.', 'sa-integrations-for-google-sheets' ), array( 'status' => 400 ) );
			}

			// Ensure limit and current_page are valid.
			if ( $integrigration_api_limit <= 0 || $integration_api_current_page <= 0 ) {
				return new \WP_Error( 'invalid_pagination', __( 'Pagination parameters must be positive integers.', 'sa-integrations-for-google-sheets' ), array( 'status' => 400 ) );
			}

			$table_name              = $wpdb->prefix . 'saifgs_integrations';
			$column_integrated_table = 'id,	title, plugin_id, source_id, order_status, google_work_sheet_id, google_sheet_tab_id, google_sheet_column_map, google_sheet_column_range, disable_integration, created_at, updated_at';

			// Prepare SQL query based on filter parameters.
			$check_run_query = true;
			$sql             = '';
			$query_params    = array();

			if ( '' !== $filter_title && '' === $filter_start_date && '' === $filter_end_date && '' === $plugin_filter ) {
				$check_run_query = false;
				$sql             = "SELECT $column_integrated_table FROM $table_name WHERE title LIKE %s ORDER BY created_at DESC";
				$query_params    = array( '%' . $wpdb->esc_like( $filter_title ) . '%' );
			} elseif ( '' !== $filter_start_date && '' !== $filter_end_date && '' === $filter_title && '' === $plugin_filter ) {
				$check_run_query = false;
				$sql             = "SELECT $column_integrated_table FROM $table_name WHERE created_at BETWEEN %s AND %s ORDER BY created_at DESC";
				$query_params    = array( $filter_start_date, $filter_end_date );
			} elseif ( '' === $filter_title && '' === $filter_start_date && '' === $filter_end_date && '' !== $plugin_filter ) {
				$check_run_query = false;
				$sql             = "SELECT $column_integrated_table FROM $table_name WHERE plugin_id = %s ORDER BY created_at DESC";
				$query_params    = array( $plugin_filter );
			} elseif ( '' !== $filter_start_date && '' !== $filter_end_date && '' !== $filter_title ) {
				$check_run_query = false;
				$sql             = "SELECT $column_integrated_table FROM $table_name WHERE title LIKE %s AND created_at BETWEEN %s AND %s ORDER BY created_at DESC";
				$query_params    = array( '%' . $wpdb->esc_like( $filter_title ) . '%', $filter_start_date, $filter_end_date );
			} elseif ( '' !== $filter_title && '' !== $plugin_filter ) {
				$check_run_query = false;
				$sql             = "SELECT $column_integrated_table FROM $table_name WHERE title LIKE %s AND plugin_id = %s ORDER BY created_at DESC";
				$query_params    = array( '%' . $wpdb->esc_like( $filter_title ) . '%', $plugin_filter );
			} elseif ( '' !== $filter_start_date && '' !== $filter_end_date && '' !== $plugin_filter ) {
				$check_run_query = false;
				$sql             = "SELECT $column_integrated_table FROM $table_name WHERE plugin_id = %s AND created_at BETWEEN %s AND %s ORDER BY created_at DESC";
				$query_params    = array( $plugin_filter, $filter_start_date, $filter_end_date );
			} elseif ( '' !== $filter_start_date && '' !== $filter_end_date && '' !== $filter_title && '' !== $plugin_filter ) {
				$check_run_query = false;
				$sql             = "SELECT $column_integrated_table FROM $table_name WHERE title LIKE %s AND plugin_id = %s AND created_at BETWEEN %s AND %s ORDER BY created_at DESC";
				$query_params    = array( '%' . $wpdb->esc_like( $filter_title ) . '%', $plugin_filter, $filter_start_date, $filter_end_date );
			} else {
				$check_run_query = true;
				$sql             = "SELECT $column_integrated_table FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d";
				$query_params    = array( $integrigration_api_limit, $offset );
			}

			// Prepare and execute the SQL query.
			if ( ! empty( $query_params ) ) {
				$results = $wpdb->get_results( $wpdb->prepare( $sql, $query_params ) ); // @codingStandardsIgnoreLine
			} else {
				$results = $wpdb->get_results( $wpdb->prepare( $sql, $query_params ) ); // @codingStandardsIgnoreLine
			}

			$new_responses = array();
			$filed         = array();

			if ( is_array( $results ) && count( $results ) > 0 ) {
				foreach ( $results as $plugin_index => $data ) {
					// If passed parameter is Array and Not String  || Creating Query URL.
					$request = 'https://sheets.googleapis.com/v4/spreadsheets/' . $data->google_work_sheet_id;

					if ( class_exists( '\SAIFGS\Classes\SAIFGS_Google_Apis_Authenticator' ) ) {
						$client = \SAIFGS\Classes\SAIFGS_Google_Apis_Authenticator::get_instance();
					} else {
						$client = '';
					}
					$response = $client->saifgs_request( $request );
					wp_cache_set( $sheet_cache_key, $sheets_data, $cache_group, 15 * MINUTE_IN_SECONDS );
					$sheets = $response['sheets'];

					if ( 'wpforms' === $data->plugin_id ) {
						$posts = wpforms()->form->get();
						foreach ( $posts as $form ) {
							$gettypewpforms          = gettype( $form->ID );
							$gettypewpformssource_id = gettype( (int) $data->source_id );
							if ( $form->ID === (int) $data->source_id ) {
								$filed[ $plugin_index ] = array(
									'id'          => $data->id,
									'title'       => $data->title,
									'created_at'  => $data->created_at,
									'google_sheet_column_map' => maybe_unserialize( $data->google_sheet_column_map ),
									'plugin_name' => 'Wp Forms',
									'plugin_id'   => $data->plugin_id,
								);
								$google_sheet_tabs      = array();
								foreach ( $sheets as $sheet ) {
									$sheet_title                       = sanitize_text_field( $sheet['properties']['title'] );
									$sheet_id                          = sanitize_text_field( $sheet['properties']['sheetId'] );
									$google_sheet_tabs[ $sheet_title ] = $sheet_id;
								}
								if ( isset( $google_sheet_tabs[ $data->google_sheet_tab_id ] ) ) {
									$google_sheets_url = "https://docs.google.com/spreadsheets/d/{$data->google_work_sheet_id}/edit#gid={$google_sheet_tabs[$data->google_sheet_tab_id]}";
								}
								$filed[ $plugin_index ]['source_name']       = $form->post_title;
								$filed[ $plugin_index ]['google_sheets_url'] = $google_sheets_url;
							}
						}
					} elseif ( 'contact_form_7' === $data->plugin_id ) {
						$posts = get_posts(
							array(
								'post_type'   => 'wpcf7_contact_form',
								'numberposts' => -1,
							)
						);
						foreach ( $posts as $form ) {
							$gettypecontact_form_7          = gettype( $form->ID );
							$gettypecontact_form_7source_id = gettype( (int) $data->source_id );
							if ( $form->ID === (int) $data->source_id ) {
								$filed[ $plugin_index ] = array(
									'id'          => $data->id,
									'title'       => $data->title,
									'created_at'  => $data->created_at,
									'google_sheet_column_map' => maybe_unserialize( $data->google_sheet_column_map ),
									'plugin_name' => 'Contact Forms 7',
									'plugin_id'   => $data->plugin_id,
								);
								$google_sheet_tabs      = array();
								foreach ( $sheets as $sheet ) {
									$sheet_title                       = $sheet['properties']['title'];
									$sheet_id                          = $sheet['properties']['sheetId'];
									$google_sheet_tabs[ $sheet_title ] = $sheet_id;
								}
								if ( isset( $google_sheet_tabs[ $data->google_sheet_tab_id ] ) ) {
									$google_sheets_url = "https://docs.google.com/spreadsheets/d/{$data->google_work_sheet_id}/edit#gid={$google_sheet_tabs[$data->google_sheet_tab_id]}";
								}
								$filed[ $plugin_index ]['source_name']       = $form->post_title;
								$filed[ $plugin_index ]['google_sheets_url'] = $google_sheets_url;
							}
						}
					} elseif ( 'gravityforms' === $data->plugin_id ) {
						$forms = \RGFormsModel::get_forms( null, 'title' );
						foreach ( $forms as $form ) {
							$gettypegravityforms          = gettype( (int) $form->id );
							$gettypegravityformssource_id = gettype( (int) $data->source_id );
							if ( (int) $form->id === (int) $data->source_id ) {
								$filed[ $plugin_index ] = array(
									'id'          => $data->id,
									'title'       => $data->title,
									'created_at'  => $data->created_at,
									'google_sheet_column_map' => maybe_unserialize( $data->google_sheet_column_map ),
									'plugin_name' => 'Gravity Forms',
									'plugin_id'   => $data->plugin_id,
								);
								$google_sheet_tabs      = array();
								foreach ( $sheets as $sheet ) {
									$sheet_title                       = sanitize_text_field( $sheet['properties']['title'] );
									$sheet_id                          = sanitize_text_field( $sheet['properties']['sheetId'] );
									$google_sheet_tabs[ $sheet_title ] = $sheet_id;
								}
								if ( isset( $google_sheet_tabs[ $data->google_sheet_tab_id ] ) ) {
									$google_sheets_url = "https://docs.google.com/spreadsheets/d/{$data->google_work_sheet_id}/edit#gid={$google_sheet_tabs[$data->google_sheet_tab_id]}";
								}
								$filed[ $plugin_index ]['source_name']       = $form->title;
								$filed[ $plugin_index ]['google_sheets_url'] = $google_sheets_url;
							}
						}
					} elseif ( 'woocommerce' === $data->plugin_id ) {
						$filed[ $plugin_index ] = array(
							'id'                      => $data->id,
							'title'                   => $data->title,
							'created_at'              => $data->created_at,
							'google_sheet_column_map' => maybe_unserialize( $data->google_sheet_column_map ),
							'plugin_name'             => 'WooCommerce',
							'plugin_id'               => $data->plugin_id,
						);
						$google_sheet_tabs      = array();
						foreach ( $sheets as $sheet ) {
							$sheet_title                       = sanitize_text_field( $sheet['properties']['title'] );
							$sheet_id                          = sanitize_text_field( $sheet['properties']['sheetId'] );
							$google_sheet_tabs[ $sheet_title ] = $sheet_id;
						}
						if ( isset( $google_sheet_tabs[ $data->google_sheet_tab_id ] ) ) {
							$google_sheets_url = "https://docs.google.com/spreadsheets/d/{$data->google_work_sheet_id}/edit#gid={$google_sheet_tabs[$data->google_sheet_tab_id]}";
						}
						$filed[ $plugin_index ]['source_name']       = 'WooCommerce Order';
						$filed[ $plugin_index ]['google_sheets_url'] = $google_sheets_url;
					}

					if ( $response['spreadsheetId'] === $data->google_work_sheet_id ) {
						$filed[ $plugin_index ]['google_work_sheet_id'] = sanitize_text_field( $response['properties']['title'] );
					}

					foreach ( $sheets as $sheet ) {
						if ( $sheet['properties']['title'] === $data->google_sheet_tab_id ) {
							$filed[ $plugin_index ]['google_sheet_tab_id'] = sanitize_text_field( $sheet['properties']['title'] );
						}
					}
				}
			}

			$count = 10;
			if ( $check_run_query ) {
				$count_query_result = $wpdb->get_results( $wpdb->prepare("SELECT COUNT(*) AS total_count FROM $table_name") );// @codingStandardsIgnoreLine
				$count              = $count_query_result[0]->total_count;
			} else {
				$count = count( $filed );
			}
			$new_responses['data']  = $filed;
			$new_responses['page']  = $integration_api_current_page;
			$new_responses['total'] = $count;

			// Cache the final response.
			wp_cache_set( $cache_key, $new_responses, $cache_group, 30 * MINUTE_IN_SECONDS );

			wp_send_json( array( 'new_response' => $new_responses ) );
		}
	}
}
