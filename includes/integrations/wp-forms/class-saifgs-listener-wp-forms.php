<?php
/**
 * Handles the integration of WP Forms.
 *
 * @package SA Integrations For Google Sheets
 * @since 1.0.0
 */

namespace SAIFGS\Integrations\WPForms;

if ( ! class_exists( '\SAIFGS\Integrations\WPForms\SAIFGS_Listener_WP_Forms ' ) ) {

	/**
	 * Class Listner WP Forms
	 *
	 * Handles the integration of WP Forms with Google Sheets.
	 */
	class SAIFGS_Listener_WP_Forms {

		// Traits used inside the class for singleton, helper methods, and REST API functionality.
		use \SAIFGS\Traits\SAIFGS_Singleton;
		use \SAIFGS\Traits\SAIFGS_Helpers;

		/**
		 * Constructor method to initialize Google Sheets service and register hooks.
		 */
		public function __construct() {
			// Add filters to handle posted data and submission results.
			add_action( 'wpforms_process_complete', array( $this, 'saifgs_process_wp_forms' ), 10, 4 );
		}

		/**
		 * Inserts data from WPForms into Google Sheets via API.
		 *
		 * @param array  $posted_data          Data submitted through the form.
		 * @param int    $form_id              ID of the WPForms form.
		 * @param int    $entry_id             Entry ID of the submission.
		 * @param string $posted_data_field_name Field name to fetch data from.
		 * @param array  $integration          Integration settings for Google Sheets.
		 *
		 * @throws \Exception If the integration or Google Sheets API fails.
		 */
		public function saifgs_insert_data_to_google_sheet( $posted_data, $form_id, $entry_id, $posted_data_field_name, $integration ) {
			global $wpdb;

			// Ensure integration settings are valid.
			if ( empty( $integration['google_work_sheet_id'] ) || empty( $integration['google_sheet_tab_id'] ) || empty( $integration['google_sheet_column_range'] ) ) {
				throw new \Exception( esc_html__( 'Integration configuration is incomplete. Please check the Google Sheets integration settings.', 'sa-integrations-for-google-sheets' ) );
			}

			$spreadsheet_id = sanitize_text_field( $integration['google_work_sheet_id'] );
			$range          = sanitize_text_field( $integration['google_sheet_tab_id'] . '!' . $integration['google_sheet_column_range'] );
			$sheet_tab_id   = sanitize_text_field( $integration['google_sheet_tab_id'] );
			$values         = array();

			// Initialize array for meta_key fields.
			$values_metakey_field = array();
			if ( is_array( $posted_data ) && count( $posted_data ) > 0 ) {
				foreach ( $posted_data as $wpforms_data_key => $wpforms_data_value ) {
					// Replace line breaks with commas and sanitize values.
					$sanitized_value = sanitize_textarea_field( $wpforms_data_value );
					if ( strpos( $sanitized_value, "\n" ) !== false ) {
						$sanitized_value = str_replace( "\n", ', ', $sanitized_value );
					}
					$values_metakey_field[ $wpforms_data_key ] = $sanitized_value;
					$values[]                                  = $sanitized_value;
				}
			}
			$google_sheet_map_data = $this->saifgs_fetch_integration_data( $form_id, $sheet_tab_id, 'insert' );
			$mapped_values         = array();

			if ( is_array( $google_sheet_map_data ) && count( $google_sheet_map_data ) > 0 ) {
				// Loop through the mapping data.
				foreach ( $google_sheet_map_data as $google_sheet_column_index => $form_field_index ) {
					// Check if the field ID exists in the $values_metakey_field array.
					$mapped_values[] = isset( $values_metakey_field[ $form_field_index ] )
						? $values_metakey_field[ $form_field_index ]
						: '';
				}
			}

			// Check if there are valid mapped values.
			if ( empty( $mapped_values ) ) {
				throw new \Exception( esc_html__( 'No valid data mapped to Google Sheets columns. Please check your field mappings.', 'sa-integrations-for-google-sheets' ) );
			}

			// Check if there are values to insert.
			if ( ! empty( $mapped_values ) ) {
				try {
					// Append the new data to Google Sheets.
					// =========== YEH SECTION CHANGE HOGI ===========
					// Old code:
					// if ( class_exists( '\SAIFGS\Classes\SAIFGS_Google_Apis_Authenticator' ) ) {
					//     $client = \SAIFGS\Classes\SAIFGS_Google_Apis_Authenticator::get_instance();
					// } else {
					//     $client = '';
					// }
					// 
					// // Request link.
					// $url = 'https://sheets.googleapis.com/v4/spreadsheets/' . $spreadsheet_id . '/values/' . $sheet_tab_id . '!A:A:append?valueInputOption=USER_ENTERED';
					// 
					// // Argument Array.
					// $args = array(
					//     'headers' => array(
					//         'Content-Type' => 'application/json',
					//     ),
					//     'body'    => '{"range":"' . $sheet_tab_id . '!A:A", "majorDimension":"ROWS", "values":[' . wp_json_encode( array_values( $final_data ) ) . ']}',
					// );
					// 
					// $response         = $client->saifgs_request( $url, $args, 'post' );

					// New code: Use the active access token
					$access_token = $this->saifgs_get_active_access_token();
					if ( empty( $access_token ) ) {
						error_log( 'SAIFGS WP Form: No access token available for Google Sheets insertion.' );
						return new \WP_Error( 'no_token', __( 'No valid Google access token found. Please check your Google connection.', 'sa-integrations-for-google-sheets' ), array( 'status' => 401 ) );
					}
					
					// Request link.
					$url = 'https://sheets.googleapis.com/v4/spreadsheets/' . $spreadsheet_id . '/values/' . $sheet_tab_id . '!A:A:append?valueInputOption=USER_ENTERED';
					
					// Argument Array.
					$args = array(
						'headers' => array(
							'Content-Type'  => 'application/json',
							'Authorization' => 'Bearer ' . $access_token,
						),
						'body'    => wp_json_encode( array(
							'range'          => $sheet_tab_id . '!A:A',
							'majorDimension' => 'ROWS',
							'values'         => array( $mapped_values ),
						) ),
						'timeout' => 30,
					);
					
					$response = wp_remote_post( $url, $args );
					
					if ( is_wp_error( $response ) ) {
						error_log( 'SAIFGS WP Form: Google Sheets API request failed - ' . $response->get_error_message() );
						return new \WP_Error( 'google_api_error', $response->get_error_message(), array( 'status' => 500 ) );
					}
					
					$response_code = wp_remote_retrieve_response_code( $response );
					$body = wp_remote_retrieve_body( $response );
					
					if ( $response_code === 401 ) {
						error_log( 'SAIFGS WP Form: Authentication failed (401) when inserting data to Google Sheets.' );
						// If this was an OAuth token, mark it as expired
						if ( $this->saifgs_is_oauth_token() ) {
							update_option( 'saifgs_auto_connect_auth_expired', 'true' );
						}
						return new \WP_Error( 'auth_expired', __( 'Authentication expired. Please reconnect your Google account.', 'sa-integrations-for-google-sheets' ), array( 'status' => 401 ) );
					}
					
					if ( $response_code !== 200 ) {
						error_log( 'SAIFGS WP Form: Google Sheets API returned error code: ' . $response_code . ' - Body: ' . $body );
						return new \WP_Error( 'google_api_error', __( 'Google Sheets API returned an error: ' . $response_code, 'sa-integrations-for-google-sheets' ), array( 'status' => $response_code ) );
					}
					
					$response_data = json_decode( $body, true );
					// =========== CHANGE END ===========

					// $sheet_tab_row_id = $response['updates']['updatedRange'];
					$sheet_tab_row_id = $response_data['updates']['updatedRange'];

					// Insert the row mapping into the integration rows table.
					$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
						$wpdb->prefix . 'saifgs_integrations_rows',
						array(
							'integration_id'      => $integration['id'],
							'sheet_id'            => $spreadsheet_id,
							'sheet_tab_id'        => $sheet_tab_id,
							'sheet_tab_row_range' => $sheet_tab_row_id,
							'source_row_id'       => $entry_id,
						)
					);
					error_log( 'SAIFGS WP Form: Successfully inserted data to Google Sheet. Row ID: ' . $sheet_tab_row_id );

				} catch ( \Exception $e ) {
					// translators: %s will be replaced with the error message.
					throw new \Exception( esc_html( sprintf( __( 'Failed to insert data into Google Sheets:  %s', 'sa-integrations-for-google-sheets' ), $e->getMessage() ) ) );
				}
			}
		}

		/**
		 * Processes WPForms data and inserts it into Google Sheets.
		 *
		 * @param array $fields    Array of form fields and their data.
		 * @param array $entry     Entry data submitted by the form.
		 * @param array $form_data Metadata about the form.
		 * @param int   $entry_id  Unique ID for the form entry.
		 *
		 * @throws \Exception If integration fails or required data is missing.
		 */
		public function saifgs_process_wp_forms( $fields, $entry, $form_data, $entry_id ) {
			// Validate and sanitize entry data.
			if ( empty( $entry['id'] ) || empty( $fields ) ) {
				throw new \Exception( 'Invalid form data: Form ID or fields are missing.' );
			}

			$form_id                = intval( $entry['id'] );
			$posted_data            = array();
			$posted_data_field_name = array();

			if ( is_array( $fields ) && count( $fields ) > 0 ) {
				foreach ( $fields as $field_index => $field ) {
					// Check if the field has a 'value' key.
					if ( isset( $field['value'] ) ) {
						// Extract the 'value' and add it to the $posted_data array.
						$posted_data[ $field_index ] = sanitize_text_field( $field['value'] );
					}
					// Check if the field has a 'name' key.
					if ( isset( $field['name'] ) ) {
						// Extract the 'value' and add it to the $posted_data array.
						$field_name               = strtolower( str_replace( ' ', '-', $field['name'] ) );
						$posted_data_field_name[] = $field_name;
					}
				}
			}
			// Check if we have valid posted data and field names.
			if ( ! empty( $posted_data ) && ! empty( $posted_data_field_name ) ) {
				if ( $posted_data && $posted_data_field_name ) {
					$integration_data = $this->saifgs_get_integration_data( 'wpforms', $form_id );
					if ( ! empty( $integration_data ) ) {
						foreach ( $integration_data as $integration ) {
							$this->saifgs_insert_data_to_google_sheet( $posted_data, $form_id, $entry_id, $posted_data_field_name, $integration );
						}
					}
				}
			} else {
				throw new \Exception( esc_html__( 'Form data is empty or incomplete. Cannot process Google Sheets integration.', 'sa-integrations-for-google-sheets' ) );
			}
		}

		/**
		 * Fetch integration data from the database.
		 *
		 * @param int    $form_id      The form ID.
		 * @param string $sheet_tab_id The Google Sheet tab ID.
		 * @param string $form_type    Type of form action (e.g., 'update').
		 *
		 * @return array|false Array of integration data or false if none found.
		 */
		public function saifgs_fetch_integration_data( $form_id, $sheet_tab_id, $form_type ) {
			global $wpdb;

			$plugin_id    = 'wpforms';
			$source_id    = intval( $form_id );
			$sheet_tab_id = sanitize_text_field( $sheet_tab_id ); // Sanitize tab ID.

			// Generate unique cache key.
			$cache_key   = 'saifgs_wpforms_integration_' . $source_id . '_' . $sheet_tab_id . '_' . $form_type;
			$cache_group = 'saifgs_integrations';

			// Try to get cached data first.
			$integration_data = wp_cache_get( $cache_key, $cache_group );

			if ( false !== $integration_data ) {
				return $integration_data;
			}

			// Execute the query if not found in cache.
			$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prepare(
					"SELECT `google_sheet_column_map` 
					FROM `{$wpdb->prefix}saifgs_integrations` 
					WHERE `plugin_id` = %s 
					AND `source_id` = %d 
					AND `google_sheet_tab_id` = %s",
					$plugin_id,
					$source_id,
					$sheet_tab_id
				)
			);

			$integration_data = false;

			if ( ! empty( $results ) ) {
				$integration_data = array();
				foreach ( $results as $result ) {
					$mapping_data = maybe_unserialize( $result->google_sheet_column_map );
					if ( is_array( $mapping_data ) ) {
						foreach ( $mapping_data as $data ) {
							// Ensure `source_filed_index` is set.
							$source_field_index = isset( $data->source_filed_index ) ? $data->source_filed_index : '';

							// Process integration data based on the form type.
							if ( 'update' === $form_type ) {
								if ( isset( $data->source_filed_index_toggle ) ) {
									$integration_data[] = array(
										'valid' => $data->source_filed_index_toggle,
										$data->google_sheet_index => $source_field_index,
									);
								}
							} else {
								$integration_data[ $data->google_sheet_index ] = $source_field_index;
							}
						}
					}

					// Cache the results for 1 hour (adjust as needed).
					wp_cache_set( $cache_key, $integration_data, $cache_group, HOUR_IN_SECONDS );
				}
				return $integration_data;
			} else {
				return false;
			}
		}
	}
}
