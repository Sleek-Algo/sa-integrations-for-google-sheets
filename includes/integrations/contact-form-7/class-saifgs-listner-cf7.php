<?php
/**
 * Handles the integration of Contact Form 7.
 *
 * @package SA Integrations For Google Sheets
 * @since 1.0.0
 */

namespace SAIFGS\Integrations\ContactForm7;

if ( ! class_exists( '\SAIFGS\Integrations\ContactForm7\SAIFGS_Listner_CF7' ) ) {

	/**
	 * Class Listner CF7
	 *
	 * Handles the integration of Contact Form 7 with Google Sheets.
	 */
	class SAIFGS_Listner_CF7 {


		// Traits used inside the class for singleton, helper methods, and REST API functionality.
		use \SAIFGS\Traits\SAIFGS_Singleton;
		use \SAIFGS\Traits\SAIFGS_Helpers;

		/**
		 * Constructor method to initialize Google Sheets service and register hooks.
		 */
		public function __construct() {
			// Add filters to handle posted data and submission results.
			add_filter( 'wpcf7_posted_data', array( $this, 'saifgs_wpcf7_posted_file_data_callback' ), 10, 1 );
			add_filter( 'wpcf7_submission_result', array( $this, 'saifgs_wpcf7_posted_data_callback' ), 10, 2 );
			// Add the nonce bypass filter.
			add_filter( 'wpcf7_verify_nonce', array( $this, 'saifgs_bypass_wpcf7_nonce_verification' ) );
		}

		/**
		 * Bypass Contact Form 7 nonce verification
		 *
		 * @return bool Always returns true to bypass nonce verification
		 */
		public function saifgs_bypass_wpcf7_nonce_verification() {
			return true;
		}

		/**
		 * Callback to handle file uploads in the Contact Form 7 posted data.
		 *
		 * @param array $posted_data The posted data from Contact Form 7.
		 * @return array The modified posted data.
		 */
		public function saifgs_wpcf7_posted_file_data_callback( $posted_data ) {
			// Verify nonce for security.
			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'wp_rest' ) ) {
				return $posted_data;
			}

			$uploaded_file_url = '';

			// Check if files were uploaded.
			if ( isset( $_FILES ) && ! empty( $_FILES ) ) {

				// Get the name of the file input field dynamically from the $_FILES array.
				$file_input_name = key( wp_unslash( $_FILES ) );

				// Access the file upload data.
				if ( isset( $_FILES[ $file_input_name ] ) ) {
					// Unslash and properly extract the $_FILES data. & Sanitize the input field name (optional if the index is validated elsewhere).
					$file = sanitize_text_field( wp_unslash( $_FILES[ $file_input_name ] ) );

					// Ensure the file upload has no errors.
					if ( isset( $file['error'] ) && UPLOAD_ERR_OK !== $file['error'] ) {
						return $posted_data; // Return early if there's an upload error.
					}

					// Get the WordPress filesystem.
					global $wp_filesystem;
					if ( empty( $wp_filesystem ) ) {
						require_once ABSPATH . '/wp-admin/includes/file.php';
						WP_Filesystem();
					}

					// Check if the filesystem is initialized.
					if ( is_wp_error( $wp_filesystem ) ) {
						return $posted_data; // If there's an error initializing the filesystem, return the original data.
					}

					// Ensure the file was uploaded successfully.
					if ( ! empty( $file['tmp_name'] ) && is_uploaded_file( $file['tmp_name'] ) ) {

						// Define the source and destination paths.
						$source_file = sanitize_text_field( $file['tmp_name'] );

						// Change this to your custom folder path.
						$destination_folder = trailingslashit( SAIFGS_PATH_ATTACHMENTS );

						// Sanitize the file name.
						$file_name = time() . '-' . sanitize_file_name( $file['name'] );

						if ( ! file_exists( $destination_folder ) ) {
							wp_mkdir_p( $destination_folder );
						}

						// Append the file name to the destination folder.
						$destination_file = $destination_folder . $file_name;

						// Move the file to the destination folder.
						if ( $wp_filesystem->copy( $source_file, $destination_file ) ) {
							// File was successfully copied.
							$uploaded_file_url               = esc_url_raw( trailingslashit( SAIFGS_URL_ATTACHMENTS ) . $file_name );
							$posted_data[ $file_input_name ] = $uploaded_file_url;
						} else {
							return new \WP_Error( 'file_move_failed', __( 'Failed to move uploaded file.', 'sa-integrations-for-google-sheets' ) );
						}
					}
				}
			}
			return $posted_data;
		}

		/**
		 * Callback to handle posted data after the submission is complete.
		 *
		 * @param array  $result The submission result.
		 * @param object $class_object The Contact Form 7 form object.
		 * @return array The modified result.
		 */
		public function saifgs_wpcf7_posted_data_callback( $result, $class_object ) {
			// If validation failed, return early.
			if ( 'validation_failed' === $result['status'] ) {
				return $result;
			}

			// Retrieve the posted data safely.
			$posted_data = $class_object->get_posted_data();
			if ( empty( $posted_data ) || ! is_array( $posted_data ) ) {
				return $result; // Return early if the posted data is invalid.
			}

			// Retrieve the current contact form instance and validate it.
			$wpcf7 = \WPCF7_ContactForm::get_current();
			if ( ! $wpcf7 instanceof \WPCF7_ContactForm ) {
				return $result; // Return early if the form instance is invalid.
			}

			// Sanitize the form ID.
			$form_id = intval( $wpcf7->id );
			if ( $form_id <= 0 ) {
				return $result; // Return early if the form ID is invalid.
			}

			// Insert into database first.
			$get_entry_id = $this->saifgs_insert_data_to_database( $form_id, $this->saifgs_sanitize_posted_data( $posted_data ) );

			// Then handle Google Sheets integration.
			$integration_data = $this->saifgs_get_integration_data( 'contact_form_7', $form_id );

			if ( is_array( $integration_data ) && ! empty( $integration_data ) ) {
				// Insert data into Google Sheets.
				foreach ( $integration_data as $integration ) {
					$sanitized_integration = $this->saifgs_sanitize_integration_data( $integration );

					// Execute the insert data function regardless of file upload status.
					$this->saifgs_insert_data_to_google_sheet(
						$this->saifgs_sanitize_posted_data( $posted_data ),
						intval( $get_entry_id ),
						$form_id,
						$sanitized_integration
					);
				}
			}

			// Return the modified $posted_data array.
			return $result;
		}

		/**
		 * Sanitize the posted data array.
		 *
		 * @param array $posted_data The posted data.
		 * @return array The sanitized posted data.
		 */
		protected function saifgs_sanitize_posted_data( $posted_data ) {
			$sanitized_data = array();
			foreach ( $posted_data as $key => $value ) {
				if ( is_array( $value ) ) {
					$sanitized_data[ $key ] = array_map( 'sanitize_text_field', $value );
				} else {
					$sanitized_data[ $key ] = sanitize_text_field( $value );
				}
			}
			return $sanitized_data;
		}

		/**
		 * Sanitize integration data.
		 *
		 * @param array|object $integration The integration data.
		 * @return array|object The sanitized integration data.
		 */
		protected function saifgs_sanitize_integration_data( $integration ) {
			if ( is_array( $integration ) ) {
				return array_map( 'sanitize_text_field', $integration );
			} elseif ( is_object( $integration ) ) {
				foreach ( $integration as $key => $value ) {
					$integration->$key = is_string( $value ) ? sanitize_text_field( $value ) : $value;
				}
			}
			return $integration;
		}

		/**
		 * Insert Contact Form 7 entry data into custom database tables.
		 *
		 * @param int   $form_id The ID of the Contact Form 7 form.
		 * @param array $posted_data The data posted by the form submission.
		 * @return int The ID of the last inserted entry.
		 */
		public function saifgs_insert_data_to_database( $form_id, $posted_data ) {
			global $wpdb;

			// Define the table names.
			$entries_table = $wpdb->prefix . 'saifgs_contact_form_7_entries';
			$meta_table    = $wpdb->prefix . 'saifgs_contact_form_7_entrymeta';

			// Sanitize the form ID.
			$form_id = absint( $form_id );
			if ( $form_id <= 0 ) {
				return false; // Invalid form ID.
			}

			// Prepare the data to insert into the entries table.
			$entry_data = array(
				'form_id' => absint( $form_id ),
			);

			// Insert the entry into the entries table and check for errors.
			$inserted = $wpdb->insert( $entries_table, $entry_data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table requires direct access.

			if ( false === $inserted ) {
				// Log the error if needed (optional).
				return false; // Return false to indicate failure.
			}

			// Get the last inserted entry ID.
			$entry_id = $wpdb->insert_id;

			// Insert the meta data into the meta table.
			foreach ( $posted_data as $key => $value ) {
				// Sanitize the meta key and value.
				$meta_key   = sanitize_text_field( $key );
				$meta_value = is_array( $value ) ? implode( ', ', array_map( 'sanitize_text_field', $value ) ) : sanitize_text_field( $value );

				$meta_data = array(
					'entry_id'   => $entry_id,
					'meta_key'   => $meta_key,
					'meta_value' => $meta_value,
				);

				// Insert each meta entry and check for errors.
				$meta_inserted = $wpdb->insert( $meta_table, $meta_data );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table requires direct access.
			}

			// Return the last inserted entry ID.
			return $entry_id;
		}

		/**
		 * Insert Contact Form 7 data into Google Sheets.
		 *
		 * @param array $data The data from the Contact Form 7 submission.
		 * @param int   $get_entry_id The ID of the entry to insert.
		 * @param int   $form_id The ID of the Contact Form 7 form.
		 * @param array $integration The integration configuration for Google Sheets.
		 * @throws \Exception If there is an issue inserting the Contact Form 7 entry.
		 */
		public function saifgs_insert_data_to_google_sheet( array $data, $get_entry_id, $form_id, $integration ) {
			global $wpdb;

			// Validate integration details.
			$spreadsheet_id       = $integration['google_work_sheet_id'] ?? null;
			$range                = $integration['google_sheet_tab_id'] . '!' . $integration['google_sheet_column_range'] ?? null;
			$sheet_tab_id         = $integration['google_sheet_tab_id'] ?? null;
			$values_metakey_field = array();

			if ( ! $spreadsheet_id || ! $sheet_tab_id || ! $range ) {
				return new \WP_Error( 'missing_integration_details', __( 'Missing required integration details.', 'sa-integrations-for-google-sheets' ), array( 'status' => 400 ) );
			}

			// Prepare the data values for Google Sheets.
			foreach ( $data as $cf7_data_key => $cf7_data_value ) {
				if ( is_array( $cf7_data_value ) ) {
					$cf7_data_value = implode( ', ', $cf7_data_value );
				}

				$values_metakey_field[ $cf7_data_key ] = strval( $cf7_data_value );
			}

			// Fetch the mapped integration data.
			$google_sheet_map_data = $this->saifgs_fetch_integration_data( $form_id, $sheet_tab_id, 'insert_form' );

			if ( empty( $google_sheet_map_data ) ) {
				return new \WP_Error( 'empty_google_sheet_map_data', __( 'No data available for Google Sheets integration map data.', 'sa-integrations-for-google-sheets' ), array( 'status' => 400 ) );
			}

			// Prepare the final data array for Google Sheets.
			$final_data = array();
			foreach ( $google_sheet_map_data as $kay => $value ) {
				$final_data[] = $values_metakey_field[ $value ];
			}

			if ( empty( $final_data ) ) {
				return new \WP_Error( 'empty_final_data', __( 'No data available for Google Sheets integration.', 'sa-integrations-for-google-sheets' ), array( 'status' => 400 ) );
			}

			if ( ! empty( $final_data ) ) {
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
						'body'    => '{"range":"' . $sheet_tab_id . '!A:A", "majorDimension":"ROWS", "values":[' . wp_json_encode( array_values( $final_data ) ) . ']}',
					);

					$response         = $client->saifgs_request( $url, $args, 'post' );
					$sheet_tab_row_id = $response['updates']['updatedRange'];

					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table requires direct access.
					$wpdb->insert(
						$wpdb->prefix . 'saifgs_integrations_rows',
						array(
							'integration_id'      => $integration['id'],
							'sheet_id'            => $spreadsheet_id,
							'sheet_tab_id'        => $sheet_tab_id,
							'sheet_tab_row_range' => $sheet_tab_row_id,
							'source_row_id'       => $get_entry_id,
						)
					);

				} catch ( \Exception $e ) {
					// Return an error response if the Google API call fails.
					return new \WP_Error( 'google_service_exception', esc_html( $e->getMessage() ), array( 'status' => 500 ) );
				}
			}
		}

		/**
		 * Fetches integration data for a specific form and Google Sheet tab.
		 *
		 * @param int    $form_id      The ID of the form.
		 * @param string $sheet_tab_id The ID of the Google Sheet tab.
		 * @param string $form_type    The type of form (e.g., 'update_form').
		 *
		 * @return array|false An array of integration data or false on failure.
		 */
		public function saifgs_fetch_integration_data( $form_id, $sheet_tab_id, $form_type ) {
			// Generate unique cache key based on parameters.
			$cache_key = 'saifgs_integration_data_' . $form_id . '_' . $sheet_tab_id . '_' . $form_type;
			$results   = wp_cache_get( $cache_key, 'saifgs_integrations' );

			// Return cached data if available.
			if ( false !== $results ) {
				return $results;
			}

			global $wpdb;

			$plugin_id    = 'contact_form_7';
			$source_id    = absint( $form_id );
			$sheet_tab_id = sanitize_text_field( $sheet_tab_id );

			// Fetch results from the database.
			$db_results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table requires direct access.
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

			// If no results are found, cache and return empty array.
			if ( empty( $db_results ) ) {
				wp_cache_set( $cache_key, array(), 'saifgs_integrations', ( 10 * MINUTE_IN_SECONDS ) );
				return array();
			}

			// Initialize integration data.
			$integration_data = array();

			foreach ( $db_results  as $result ) {
				$maping_data = maybe_unserialize( $result->google_sheet_column_map );
				foreach ( $maping_data as $data ) {
					if ( 'update_form' === $form_type ) {
						if ( isset( $data->source_filed_index ) ) {
							$integration_data[ $data->google_sheet_index ] = $data->source_filed_index;

							// Include additional validation toggle if available.
							if ( isset( $data->source_filed_index_toggle ) ) {
								$integration_data[] = array(
									'valid' => (bool) $data->source_filed_index_toggle,
									$data->google_sheet_index => $data->source_filed_index,
								);
							}
						}
					} else {
						if ( isset( $data->source_filed_index ) ) {
							$integration_data[ $data->google_sheet_index ] = $data->source_filed_index;
						}
					}
				}
			}

			// Cache the results for 10 minutes.
			wp_cache_set( $cache_key, $integration_data, 'saifgs_integrations', ( 10 * MINUTE_IN_SECONDS ) );

			return $integration_data;
		}
	}
}
