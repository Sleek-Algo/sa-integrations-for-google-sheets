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
		 * @param \WP_REST_Request $request REST request object
		 * @return \WP_REST_Response|\WP_Error Response with file data or error
		 */
		public function saifgs_save_settings( $request ) {
			// Add JSON to allowed upload mime types.
			add_filter('upload_mimes', function($mimes) {
				$mimes['json'] = 'application/json';
				return $mimes;
			});
			
			$json_file = isset($_FILES['json_file']) ? $_FILES['json_file'] : null;
		
			if ( $json_file && UPLOAD_ERR_OK === $json_file['error'] ) {
				
				$upload_overrides = array(
					'test_form' => false,
					'mimes' => array('json' => 'application/json'),
					'action' => 'saifgs_upload' // Custom action for security.
				);
				
				$uploaded_file = wp_handle_upload($json_file, $upload_overrides);
				
				if ($uploaded_file && !isset($uploaded_file['error'])) {
					$json_content = json_decode(file_get_contents($uploaded_file['file']));
					
					update_option(
						'saifgs_google_credentials_file',
						array(
							'name' => basename($uploaded_file['file']),
							'path' => $uploaded_file['file'],
							'data' => $json_content,
						)
					);
		
					wp_send_json_success(array(
						'message' => 'Settings saved successfully.',
						'uploadedFile' => array(
							'name' => basename($uploaded_file['file']),
							'path' => $uploaded_file['file'],
							'jsonContent' => $json_content,
						),
					));
				} else {
					$error_message = $uploaded_file['error'] ?? 'Failed to upload file.';
					return new \WP_Error('file_upload_failed', $error_message, array('status' => 500));
				}
			}
		
			return new \WP_Error('no_file_uploaded', 'No file uploaded or invalid file.', array('status' => 400));
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
