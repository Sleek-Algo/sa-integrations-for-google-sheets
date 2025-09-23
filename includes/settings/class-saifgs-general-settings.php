<?php
/**
 * Handles the General Settings.
 *
 * @package SA Integrations For Google Sheets
 * @since 1.0.0
 */

namespace SAIFGS\Settings;

if ( ! class_exists( '\SAIFGS\Settings\SAIFGS_General_Settings' ) ) {

	/**
	 * Class General Settings.
	 *
	 * Handles the General Settings.
	 */
	class SAIFGS_General_Settings {

		// Traits used inside class.
		use \SAIFGS\Traits\SAIFGS_Singleton;
		use \SAIFGS\Traits\SAIFGS_Helpers;
		use \SAIFGS\Traits\SAIFGS_RestAPI;

		/**
		 * API route for fetching integration settings.
		 *
		 * @var string
		 */
		private static $saifgs_api_route_base_url = '/get-integration-setting';

		/**
		 * API route for removing a file.
		 *
		 * @var string
		 */
		private static $saifgs_api_route_base_remove_file = '/remove-file';

		/**
		 * API route for saving settings.
		 *
		 * @var string
		 */
		private static $saifgs_api_route_base_save_file = '/save-settings';

		/**
		 * Constructor for the saifgs class.
		 *
		 * This constructor initializes the class by setting up the general settings and registering custom REST API endpoints.
		 *
		 * - **Register General Settings:** Calls `saifgs_register_general_settings` to register plugin-specific settings with WordPress.
		 * - **Register Custom Endpoints:** Hooks the `register_custom_endpoint` method to the `rest_api_init` action, which ensures that
		 *   custom REST API routes are registered when the REST API is initialized.
		 *
		 * This setup ensures that the plugin's settings and API endpoints are correctly registered and available for use.
		 */
		public function __construct() {
			$this->saifgs_register_general_settings();
			add_action( 'rest_api_init', array( $this, 'saifgs_register_custom_endpoint' ) );
		}

		/**
		 * Registers custom REST API endpoints for the saifgs_ plugin.
		 *
		 * This method registers three custom REST API routes with WordPress. Each route handles a specific API request
		 * related to the plugin's functionality:
		 *
		 * 1. **Save Settings Endpoint**
		 *    - **Route:** POST request to `self::$saifgs_api_route_base_save_file`
		 *    - **Callback:** `saifgs_save_settings`
		 *    - **Description:** Handles the upload and saving of settings files. The callback function processes the file upload,
		 *      saves the file to a designated directory, and updates plugin settings accordingly.
		 *
		 * 2. **Get Integration Settings Endpoint**
		 *    - **Route:** GET request to `self::$saifgs_api_route_base_url`
		 *    - **Callback:** `get_integration_setting`
		 *    - **Description:** Retrieves the currently uploaded settings file and its contents. Returns information about the
		 *      uploaded file and its JSON data.
		 *
		 * 3. **Remove File Endpoint**
		 *    - **Route:** POST request to `self::$saifgs_api_route_base_remove_file`
		 *    - **Callback:** `saifgs_remove_file`
		 *    - **Description:** Handles the removal of the uploaded settings file. Deletes the file from the server and clears
		 *      the associated plugin option.
		 *
		 * Each endpoint requires appropriate permissions to ensure that only authorized users can access or modify the data.
		 *
		 * @return void
		 */
		public function saifgs_register_custom_endpoint() {
			register_rest_route(
				$this->saifgs_get_api_base_url(),
				self::$saifgs_api_route_base_save_file,
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'saifgs_save_settings' ),
					'permission_callback' => array( $this, 'saifgs_permissions' ),
					'args'                => array(
						'_wpnonce' => array(
							'required'          => true,
							'validate_callback' => function( $param ) {
								return ! empty( $param );
							},
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				)
			);
			register_rest_route(
				$this->saifgs_get_api_base_url(),
				self::$saifgs_api_route_base_url,
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'saifgs_get_integration_setting' ),
					'permission_callback' => array( $this, 'saifgs_permissions' ),
				)
			);
			register_rest_route(
				$this->saifgs_get_api_base_url(),
				self::$saifgs_api_route_base_remove_file,
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'saifgs_remove_file' ),
					'permission_callback' => array( $this, 'saifgs_permissions' ),
					'args'                => array(
						'_wpnonce' => array(
							'required'          => true,
							'validate_callback' => function( $param ) {
								return ! empty( $param );
							},
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				)
			);
		}

		/**
		 * Retrieves the integration settings from the uploaded credential file.
		 *
		 * This method checks if the uploaded credential file exists and is valid. If so, it returns:
		 * - The details of the uploaded file (e.g., path).
		 * - The JSON-decoded content of the file.
		 *
		 * If the file does not exist or is not valid, it returns:
		 * - `null` for the uploaded file.
		 * - `null` for the JSON-decoded content.
		 *
		 * The response is structured as an associative array and wrapped in a REST API response using `rest_ensure_response`.
		 *
		 * @return \WP_REST_Response Response object containing the uploaded file details and its JSON data.
		 */
		public function saifgs_get_integration_setting() {
			$google_credentials_file = get_option( 'saifgs_google_credentials_file', null );
			if ( isset( $google_credentials_file['path'] ) && file_exists( $google_credentials_file['path'] ) ) {
				return rest_ensure_response(
					array(
						'uploadedFile' => $google_credentials_file,
						'json_data'    => $google_credentials_file['data'],
					)
				);
			} else {
				return rest_ensure_response(
					array(
						'uploadedFile' => null,
						'json_data'    => null,
					)
				);
			}
		}

		/**
		 * Handles JSON file upload and saves Google credentials.
		 *
		 * - Allows JSON file uploads via WordPress media handler
		 * - Validates and processes the uploaded file
		 * - Stores file info and parsed JSON in options
		 * - Returns success/error response
		 *
		 * @param \WP_REST_Request $request REST request object.
		 * @return \WP_REST_Response|\WP_Error Response with file data or error
		 */
		public function saifgs_save_settings( $request ) {
			// VALIDATION - Check if file was uploaded.
			if ( ! isset( $_FILES['json_file'] ) || empty( $_FILES['json_file'] ) ) {
				return new \WP_Error( 'no_file_uploaded', esc_html__( 'No file uploaded or upload error occurred.', 'sa-integrations-for-google-sheets' ), array( 'status' => 400 ) );
			}

			// SANITIZE - File type and size information.
			$file_type = isset( $_FILES['json_file']['type'] ) ? sanitize_mime_type( wp_unslash( $_FILES['json_file']['type'] ) ) : '';
			$file_size = isset( $_FILES['json_file']['size'] ) ? intval( $_FILES['json_file']['size'] ) : 0;

			// Check if file is JSON.
			if ( 'application/json' !== $file_type ) {
				return new \WP_Error( 'invalid_file_type', esc_html__( 'Only JSON files are allowed.', 'sa-integrations-for-google-sheets' ), array( 'status' => 400 ) );
			}

			// Check file size (max 2MB).
			$max_size = 2 * 1024 * 1024; // 2MB in bytes.
			if ( $file_size > $max_size ) {
				return new \WP_Error( 'file_too_large', esc_html__( 'File size must be less than 2MB.', 'sa-integrations-for-google-sheets' ), array( 'status' => 400 ) );
			}

			if ( 0 === $file_size ) {
				return new \WP_Error( 'file_empty', esc_html__( 'Uploaded file is empty.', 'sa-integrations-for-google-sheets' ), array( 'status' => 400 ) );
			}

			// Add JSON to allowed upload mime types.
			add_filter(
				'upload_mimes',
				function( $mimes ) {
					$mimes['json'] = 'application/json';
					return $mimes;
				}
			);

			// Handle file upload with WordPress security.
			$upload_overrides = array(
				'test_form' => false,
				'test_type' => true, // Check file type.
				'mimes'     => array( 'json' => 'application/json' ),
				'action'    => 'saifgs_upload',
			);

			$uploaded_file = wp_handle_upload( $_FILES['json_file'], $upload_overrides ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput

			// Check for upload errors.
			if ( isset( $uploaded_file['error'] ) ) {
				return new \WP_Error(
					'file_upload_failed',
					esc_html( $uploaded_file['error'] ),
					array( 'status' => 500 )
				);
			}

			if ( ! $uploaded_file || ! file_exists( $uploaded_file['file'] ) ) {
				return new \WP_Error( 'file_upload_failed', esc_html__( 'Failed to upload file.', 'sa-integrations-for-google-sheets' ), array( 'status' => 500 ) );
			}

			// Read and validate JSON content.
			$json_content = wp_remote_get( $uploaded_file['url'] );
			if ( false === $json_content ) {
				wp_delete_file( $uploaded_file['url'] ); // Clean up.
				return new \WP_Error( 'file_read_error', esc_html__( 'Failed to read file contents.', 'sa-integrations-for-google-sheets' ), array( 'status' => 500 ) );
			}

			$json_data = json_decode( $json_content['body'] );

			// Validate JSON structure.
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				wp_delete_file( $uploaded_file['file'] ); // Clean up invalid file.
				return new \WP_Error(
					'invalid_json',
					sprintf(
						/* translators: %s: JSON error message */
						__( 'Invalid JSON file. Error: %s', 'sa-integrations-for-google-sheets' ),
						esc_html( json_last_error_msg() )
					),
					array( 'status' => 400 )
				);
			}

			// Additional validation for Google credentials structure.
			if ( ! $this->validate_google_credentials( $json_data ) ) {
				wp_delete_file( $uploaded_file['file'] );
				return new \WP_Error(
					'invalid_credentials',
					esc_html__( 'The JSON file does not contain valid Google service account credentials.', 'sa-integrations-for-google-sheets' ),
					array( 'status' => 400 )
				);
			}

			// Sanitize file data before saving.
			$file_data = array(
				'name' => sanitize_file_name( basename( $uploaded_file['file'] ) ),
				'path' => $uploaded_file['file'], // This is already handled by WordPress.
				'data' => $json_data,
			);

			// Save to options.
			update_option(
				'saifgs_google_credentials_file',
				$file_data
			);

			// Prepare response with escaped data.
			$response_data = array(
				'message'      => esc_html__( 'Settings saved successfully.', 'sa-integrations-for-google-sheets' ),
				'uploadedFile' => array(
					'name'        => esc_html( $file_data['name'] ),
					'path'        => $uploaded_file['file'],
					'jsonContent' => $json_data,
				),
			);

			return wp_send_json_success( $response_data );
		}

		/**
		 * Validate Google service account credentials structure
		 *
		 * @param mixed $json_data Decoded JSON data.
		 * @return bool True if valid, false otherwise
		 */
		private function validate_google_credentials( $json_data ) {
			// Check if it's an object.
			if ( ! is_object( $json_data ) ) {
				return false;
			}

			// Required fields for Google service account credentials.
			$required_fields = array(
				'type',
				'project_id',
				'private_key_id',
				'private_key',
				'client_email',
				'client_id',
				'auth_uri',
				'token_uri',
				'auth_provider_x509_cert_url',
				'client_x509_cert_url',
			);

			foreach ( $required_fields as $field ) {
				if ( ! isset( $json_data->$field ) || empty( $json_data->$field ) ) {
					return false;
				}
			}

			// Additional validation for specific fields.
			if ( 'service_account' !== $json_data->type ) {
				return false;
			}

			if ( ! filter_var( $json_data->client_email, FILTER_VALIDATE_EMAIL ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Removes the uploaded JSON configuration file and deletes its record from the WordPress options.
		 *
		 * This method performs the following actions:
		 * - Retrieves the path of the uploaded file from the WordPress options table.
		 * - Checks if the file exists at the specified path.
		 * - Attempts to delete the file using `unlink()`.
		 * - If successful, deletes the file record from the WordPress options table and returns a success response.
		 * - If the file could not be deleted, returns a WP_Error with a 'file_not_removed' code and a 500 status.
		 * - If the file does not exist, returns a WP_Error with a 'file_not_found' code and a 404 status.
		 *
		 * @return \WP_REST_Response|\WP_Error Response object containing success or error information.
		 */
		public function saifgs_remove_file() {
			$google_credentials_file = get_option( 'saifgs_google_credentials_file', null );

			if ( $google_credentials_file && isset( $google_credentials_file['path'] ) && file_exists( $google_credentials_file['path'] ) ) {
				if ( unlink( $google_credentials_file['path'] ) ) { // @codingStandardsIgnoreLine
					delete_option( 'saifgs_google_credentials_file' );

					return rest_ensure_response( array( 'message' => 'File removed successfully.' ) );
				} else {
					return new \WP_Error( 'file_not_removed', 'File could not be removed.', array( 'status' => 500 ) );
				}
			} else {
				return new \WP_Error( 'file_not_found', 'File not found.', array( 'status' => 404 ) );
			}
		}

		/**
		 * Registers general settings for the saifgs_ plugin.
		 *
		 * This method registers a setting with WordPress that stores general configuration options for the plugin.
		 * The settings are stored as an array of objects, each representing a supported plugin with specific attributes.
		 * The registered setting includes:
		 * - `saifgs_supported_plugins`: The option name where the settings are stored.
		 *
		 * Settings options include:
		 * - `default`: Default value for the setting, which is an array of one object with empty values.
		 * - `single`: Indicates that the setting value is a single value rather than an array of values.
		 * - `type`: The type of the setting value, which is an array.
		 * - `show_in_rest`: Configuration to expose this setting in the WordPress REST API, including the schema for validation.
		 *   - `schema`: The schema that defines the structure of the setting when accessed via REST API.
		 *     - `items`: The type and properties of each item in the array.
		 *       - `id`: The plugin identifier, represented as a string.
		 *       - `title`: The plugin title, represented as a string.
		 *       - `usability_status`: The plugin's usability status, represented as a string.
		 *       - `availability_status`: The plugin's availability status, represented as a string.
		 *
		 * @return void
		 */
		public function saifgs_register_general_settings() {
			register_setting(
				'saifgs_general_settings',
				'saifgs_supported_plugins',
				array(
					'default'           => array(
						array(
							'id'                  => '',
							'title'               => '',
							'usability_status'    => '',
							'availability_status' => '',
						),
					),
					'sanitize_callback' => array( $this, 'saifgs_sanitize_supported_plugins' ),
					'single'            => true,
					'type'              => 'array',
					'show_in_rest'      => array(
						'schema' => array(
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'                  => array(
										'type' => 'string',
									),
									'title'               => array(
										'type' => 'string',
									),
									'usability_status'    => array(
										'type' => 'string',
									),
									'availability_status' => array(
										'type' => 'string',
									),
								),
							),
						),
					),
				)
			);
		}

		/**
		 * Sanitizes the supported plugins setting.
		 *
		 * @param mixed $value The unsanitized setting value.
		 * @return array The sanitized array of supported plugins.
		 */
		public function saifgs_sanitize_supported_plugins( $value ) {
			if ( ! is_array( $value ) ) {
				return array(
					array(
						'id'                  => '',
						'title'               => '',
						'usability_status'    => '',
						'availability_status' => '',
					),
				);
			}

			$sanitized = array();

			foreach ( $value as $plugin ) {
				$sanitized_plugin = array();

				// Sanitize each field.
				$sanitized_plugin['id'] = isset( $plugin['id'] )
					? sanitize_text_field( $plugin['id'] )
					: '';

				$sanitized_plugin['title'] = isset( $plugin['title'] )
					? sanitize_text_field( $plugin['title'] )
					: '';

				$sanitized_plugin['usability_status'] = isset( $plugin['usability_status'] )
					? sanitize_text_field( $plugin['usability_status'] )
					: '';

				$sanitized_plugin['availability_status'] = isset( $plugin['availability_status'] )
					? sanitize_text_field( $plugin['availability_status'] )
					: '';

				$sanitized[] = $sanitized_plugin;
			}

			return $sanitized;
		}
	}
}
