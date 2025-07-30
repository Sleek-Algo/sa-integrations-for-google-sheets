<?php
/**
 * Handles the integration of Gravity Forms.
 *
 * @package SA Integrations For Google Sheets
 * @since 1.0.0
 */

namespace SAIFGS\Integrations\GravityForms;

// Import necessary Google classes here.
use Google_Service_Exception;

if ( ! class_exists( '\SAIFGS\Integrations\GravityForms\SAIFGS_Listener_Gravity_Forms' ) ) {

	/**
	 * Class Listener Gravity Forms
	 *
	 * Handles the integration of Gravity Forms with Google Sheets.
	 */
	class SAIFGS_Listener_Gravity_Forms {


		// Traits used inside the class for singleton, helper methods, and REST API functionality.
		use \SAIFGS\Traits\SAIFGS_Singleton;
		use \SAIFGS\Traits\SAIFGS_Helpers;

		/**
		 * Constructor method to initialize Google Sheets service and register hooks.
		 */
		public function __construct() {
			// Add filters to handle posted data and submission results.
			add_action( 'gform_after_submission', array( $this, 'saifgs_process_gravity_form' ), 10, 2 );
		}

		/**
		 * Prepare and sanitize form labels.
		 *
		 * Converts field labels to lowercase and replaces spaces with hyphens.
		 *
		 * @param array $form The form data containing fields.
		 * @return array The sanitized and transformed labels.
		 */
		private function saifgs_prepare_form_labels( $form ) {
			// Ensure fields exist and are an array.
			if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
				return array();
			}

			$labels = array();

			// Loop through each field in the form.
			foreach ( $form['fields'] as $field ) {
				// Convert the label to lowercase and replace spaces with hyphens.
				$label    = sanitize_title( strtolower( str_replace( ' ', '-', $field->label ) ) );
				$labels[] = $label;
			}
			return $labels;
		}

		/**
		 * Processes a Gravity Form entry and sends data to Google Sheets.
		 *
		 * @param array $entry The Gravity Forms entry data.
		 * @param array $form  The Gravity Forms metadata.
		 */
		public function saifgs_process_gravity_form( $entry, $form ) {
			// Validate that required parameters exist.
			if ( empty( $entry['form_id'] ) || ! is_array( $entry ) || ! is_array( $form ) ) {
				return;
			}

			// Extract the form ID from the entry data.
			$form_id = $entry['form_id'];

			// Get form data.
			$form_data = $entry;

			// Prepare the form labels for use in the Google Sheets data.
			$prepared_labels = $this->saifgs_prepare_form_labels( $form );
			// Prepare data for Google Sheets.
			$prepared_data = $this->saifgs_prepare_data_for_sheets( $form_id, $form_data );
			// Check if the entry contains user input data.
			if ( $this->saifgs_has_user_input( $prepared_data ) ) {
				// Retrieve integration data for the Gravity Forms.
				$integration_data = $this->saifgs_get_integration_data( 'gravityforms', $form_id );

				// Check if the integration is valid.
				if ( empty( $integration_data ) || ! is_array( $integration_data ) ) {
					return;
				}

				// Insert the prepared data into Google Sheets based on the integration settings.
				foreach ( $integration_data as $integration ) {
					$this->saifgs_insert_data_to_google_sheet( $form_id, $entry['id'], $prepared_data, $prepared_labels, $integration );
				}
			}
		}

		/**
		 * Prepares form entry data for insertion into Google Sheets.
		 *
		 * @param int   $form_id The ID of the form being processed.
		 * @param array $entry   The entry data from the form submission.
		 * @return array Processed data ready for Google Sheets.
		 */
		private function saifgs_prepare_data_for_sheets( $form_id, $entry ) {
			// Initialize an array to store the prepared data.
			$prepared_data = array();

			// Validate that required parameters exist.
			if ( empty( $entry ) || ! is_array( $entry ) ) {
				return array(); // Return an empty array if validation fails.
			}

			// Loop through each field in the entry data.
			foreach ( $entry as $field_key => $field_value ) {
				// Extract the base index of the field key, ignoring any dynamic index numbers.
				$base_index = strtok( $field_key, '.' );

				// Skip fields that do not have a numeric base index.
				if ( ! is_numeric( $base_index ) ) {
					continue;
				}

				// Convert array values to a space-separated string.
				if ( is_array( $field_value ) ) {
					$field_value = implode( ' ', $field_value );
				} elseif ( is_serialized( $field_value ) ) {
					// Unserialize and convert serialized array values to a space-separated string.
					$unserialized_value = maybe_unserialize( $field_value );
					if ( is_array( $unserialized_value ) ) {
						$field_value = implode( ' ', array_map( 'sanitize_text_field', $unserialized_value ) );
					} else {
						$field_value = sanitize_text_field( $field_value );
					}
				} else {
					// Sanitize scalar field values.
					$field_value = sanitize_text_field( $field_value );
				}

				// Ensure that empty field values are represented as a single space or an empty string.
				$field_value = empty( $field_value ) ? ' ' : $field_value;

				// Append field values to existing entries with the same base index.
				if ( isset( $prepared_data[ $base_index ] ) ) {
					$prepared_data[ $base_index ] .= ' ' . $field_value;
				} else {
					// Add new base index with the field value.
					$prepared_data[ $base_index ] = $field_value;
				}
			}

			// Add new base index with the field value.
			return $prepared_data;
		}

		/**
		 * Checks if the provided data array contains user input.
		 *
		 * Ensures the data is a valid array and not empty.
		 *
		 * @param mixed $data The data to check.
		 * @return bool True if the data contains user input, false otherwise.
		 */
		private function saifgs_has_user_input( $data ) {
			// Validate that the data is an array before checking.
			if ( ! is_array( $data ) ) {
				return false; // Return false if the data is not an array.
			}

			// Check if the data array is not empty.
			return ! empty( $data );
		}

		/**
		 * Inserts data into a Google Sheet based on integration settings.
		 *
		 * @param int   $form_id        The ID of the form.
		 * @param int   $entry_id       The ID of the form entry.
		 * @param array $prepared_data  The prepared data to insert.
		 * @param array $prepared_labels The prepared labels for mapping.
		 * @param array $integration    The integration settings for Google Sheets.
		 * @return mixed True on success, WP_Error on failure.
		 */
		private function saifgs_insert_data_to_google_sheet( $form_id, $entry_id, $prepared_data, $prepared_labels, $integration ) {
			global $wpdb;

			// Validate required parameters.
			if ( empty( $integration['google_sheet_tab_id'] ) || empty( $integration['google_sheet_column_range'] ) || empty( $integration['google_work_sheet_id'] ) ) {
				return new \WP_Error( 'invalid_integration_settings', __( 'Invalid Google Sheet integration settings.', 'sa-integrations-for-google-sheets' ), array( 'status' => 400 ) );
			}

			// Define the range to insert data (e.g., 'Sheet1!A:E').
			$range          = sanitize_text_field( $integration['google_sheet_tab_id'] . '!' . $integration['google_sheet_column_range'] );
			$sheet_tab_id   = sanitize_text_field( $integration['google_sheet_tab_id'] );
			$spreadsheet_id = sanitize_text_field( $integration['google_work_sheet_id'] );

			$values_with_key = array();
			$values          = array();

			// Validate that the prepared_data is an array before checking.
			if ( empty( $prepared_data ) || ! is_array( $prepared_data ) ) {
				return;
			}

			// Convert the prepared data array values to strings.
			foreach ( $prepared_data as $key => &$value ) {

				$key_sanitized   = sanitize_text_field( $key );
				$value_sanitized = is_array( $value ) ? implode( ' ', array_map( 'sanitize_text_field', $value ) ) : sanitize_text_field( $value );

				$values_with_key[ $key_sanitized ] = $value_sanitized;
				$values[]                          = $value_sanitized;
			}

			// Fetch integration data specific to the form and Google Sheet.
			$google_sheet_map_data = $this->saifgs_fetch_integration_data( $form_id, $sheet_tab_id, 'insert' );
			$mapped_data           = array();

			// Map prepared data to the appropriate columns in Google Sheets.
			if ( is_array( $google_sheet_map_data ) && count( $google_sheet_map_data ) > 0 ) {
				foreach ( $google_sheet_map_data as $kay => $value ) {
					$value_sanitized = sanitize_text_field( $value );
					$mapped_data[]   = isset( $values_with_key[ $value_sanitized ] ) ? $values_with_key[ $value_sanitized ] : ' ';
				}
			}

			try {
				// Append the new data to Google Sheets.
				if ( class_exists( '\SAIFGS\Classes\SAIFGS_Google_Apis_Authenticator' ) ) {
					$client = \SAIFGS\Classes\SAIFGS_Google_Apis_Authenticator::get_instance();
				} else {
					$client = '';
				}

				// Request link.
				$url = 'https://sheets.googleapis.com/v4/spreadsheets/' . $spreadsheet_id . '/values/' . $sheet_tab_id . '!A:A:append?valueInputOption=USER_ENTERED';

				// Argument Array.
				$args = array(
					'headers' => array(
						'Content-Type' => 'application/json',
					),
					'body'    => '{"range":"' . $sheet_tab_id . '!A:A", "majorDimension":"ROWS", "values":[' . wp_json_encode( array_values( $mapped_data ) ) . ']}',
				);

				$response         = $client->saifgs_request( $url, $args, 'post' );
				$sheet_tab_row_id = $response['updates']['updatedRange'];

				// Insert the row mapping into the integration rows table.
				$wpdb->insert(// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
					$wpdb->prefix . 'saifgs_integrations_rows',
					array(
						'integration_id'      => $integration['id'],
						'sheet_id'            => $spreadsheet_id,
						'sheet_tab_id'        => $sheet_tab_id,
						'sheet_tab_row_range' => $sheet_tab_row_id,
						'source_row_id'       => $entry_id,
					)
				);

			} catch ( \Exception $e ) {
				// Return an error response if the Google API call fails.
				return new \WP_Error( 'google_service_exception', esc_html( $e->getMessage() ), array( 'status' => 500 ) );
			}
		}

		/**
		 * Fetches integration data for a specific form and Google Sheet tab.
		 *
		 * @param int    $form_id       The form ID to fetch integration data for.
		 * @param string $sheet_tab_id  The Google Sheet tab ID.
		 * @param string $form_type     The form type ('insert' or 'update').
		 * @return array|false          An array of integration data, or false if no data found.
		 */
		public function saifgs_fetch_integration_data( $form_id, $sheet_tab_id, $form_type ) {
			global $wpdb;

			// Define the database table name for integrated data.
			$plugin_id    = 'gravityforms'; // Plugin identifier for Gravity Forms.
			$source_id    = intval( $form_id ); // The form ID to fetch integration data for.
			$sheet_tab_id = sanitize_text_field( $sheet_tab_id );
			$form_type    = sanitize_text_field( $form_type );

			// Generate a unique cache key based on all parameters.
			$cache_key = 'saifgs_integration_' . $plugin_id . '_' . $form_id . '_' . $sheet_tab_id . '_' . $form_type;

			// Try to get cached data first.
			$integration_data = wp_cache_get( $cache_key, 'saifgs_integrations' );

			if ( false !== $integration_data ) {
				return $integration_data;
			}

			// Execute the query and fetch results.
			$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prepare(
					"SELECT `google_sheet_column_map` 
					FROM `{$wpdb->prefix}saifgs_integrations`
					WHERE `plugin_id` = %s 
					AND `source_id` = %d 
					AND `google_sheet_tab_id` = %s",
					$plugin_id,
					$form_id,
					$sheet_tab_id
				)
			);

			$integration_data = false;

			// Check if any results were found.
			if ( is_array( $results ) && count( $results ) > 0 ) {
				$integration_data = array();

				// Loop through the results and process the column mapping data.
				foreach ( $results as $result ) {
					$mapping_data = maybe_unserialize( $result->google_sheet_column_map );
					if ( is_array( $mapping_data ) ) {

						// Map the Google Sheet column indexes to the form field indexes.
						foreach ( $mapping_data  as $data ) {
							// Ensure the source field index is set; otherwise, default to an empty string.
							$data->source_filed_index = isset( $data->source_filed_index ) ? sanitize_text_field( $data->source_filed_index ) : '';
							if ( 'update' === $form_type ) {
								$integration_data[] = array(
									'valid' => isset( $data->source_filed_index_toggle ) ? (bool) $data->source_filed_index_toggle : false,
									$data->google_sheet_index => $data->source_filed_index,
								);

							} else {
								$integration_data[ $data->google_sheet_index ] = $data->source_filed_index;
							}
						}
					}
				}

				// Cache the results for 1 hour (adjust as needed).
				wp_cache_set( $cache_key, $integration_data, 'saifgs_integrations', HOUR_IN_SECONDS );

				return $integration_data;
			}
		}
	}
}
