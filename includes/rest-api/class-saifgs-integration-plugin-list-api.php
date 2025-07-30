<?php
/**
 * Handles the Integration Plugin List API.
 *
 * @package SA Integrations For Google Sheets
 * @since 1.0.0
 */

namespace SAIFGS\RestApi;

if ( ! class_exists( '\SAIFGS\RestApi\SAIFGS_Integration_Plugin_List_API' ) ) {

	/**
	 * Class Integration Plugin List API.
	 *
	 * Handles the Integration Plugin List API
	 */
	class SAIFGS_Integration_Plugin_List_API {

		// Traits used inside class.
		use \SAIFGS\Traits\SAIFGS_Singleton;
		use \SAIFGS\Traits\SAIFGS_Helpers;
		use \SAIFGS\Traits\SAIFGS_RestAPI;

		/**
		 * Base URL for the integrated form API route.
		 *
		 * @var string
		 */
		private static $saifgs_api_route_base_url = '/integrated-plugins-list';

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
		 * Registers REST API routes for the plugin.
		 *
		 * - `GET` request to fetch the list of supported plugins using the `saifgs_get_plugins_list` callback.
		 * - `POST` request to update supported plugin data using the `saifgs_update_supported_plugin_data_method` callback.
		 *
		 * Both routes require the `permissions` method to check authorization.
		 *
		 * @return void
		 */
		public function saifgs_register_rest_routes() {

			register_rest_route(
				$this->saifgs_get_api_base_url(),
				self::$saifgs_api_route_base_url,
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'saifgs_get_plugins_list' ),
					'permission_callback' => array( $this, 'saifgs_permissions' ),
				)
			);

			register_rest_route(
				$this->saifgs_get_api_base_url(),
				self::$saifgs_api_route_base_url,
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'saifgs_update_supported_plugin_data_method' ),
					'permission_callback' => array( $this, 'saifgs_permissions' ),
				)
			);

		}

		/**
		 * Retrieves and returns the list of supported plugins from the database.
		 *
		 * @return void
		 */
		public function saifgs_get_plugins_list() {
			global $wpdb;

			// Set cache key and group.
			$cache_key   = 'saifgs_supported_plugins_list';
			$cache_group = 'saifgs_plugins';

			// Try to get cached data first.
			$results = wp_cache_get( $cache_key, $cache_group );

			// If not found in cache, fetch from database.
			if ( false === $results ) {
				// Execute the query and fetch results.
				$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					"SELECT `id`, `title`, `key`, `usability_status`, 
						`availability_status`, `image_url`, `url`, `discription` 
					FROM `{$wpdb->prefix}saifgs_supported_plugins` "
				);

				// Check for errors.
				if ( $wpdb->last_error ) {
					wp_send_json_error( array( 'message' => __( 'An error occurred while fetching plugin data.', 'sa-integrations-for-google-sheets' ) ) );
					return;
				}

				// Cache the results for 12 hours (adjust as needed).
				wp_cache_set( $cache_key, $results, $cache_group, 12 * HOUR_IN_SECONDS );
			}

			// Send JSON response.
			wp_send_json( $results );
		}

		/**
		 * Update Supported Plugin Data via REST API.
		 *
		 * This function updates the `usability_status` and `availability_status`
		 * for a plugin based on the input data. If no specific plugin data is provided,
		 * it sets all plugins' statuses to "no".
		 *
		 * @param \WP_REST_Request $request The REST API request object.
		 */
		public function saifgs_update_supported_plugin_data_method( \WP_REST_Request $request ) {
			// Initialize the WordPress database global.
			global $wpdb;

			// Extract query parameters.
			$data = $request->get_query_params();

			$plugin_data = ( isset( $data['data'] ) ) ? $data['data'] : '';
			if ( is_array( $plugin_data ) && count( $plugin_data ) > 0 ) {

				// Validate the 'checked' field and determine status.
				if ( isset( $plugin_data['checked'] ) && 'true' === sanitize_text_field( $plugin_data['checked'] ) ) {
					$usability_status    = 'yes';
					$availability_status = 'yes';
				} else {
					$usability_status    = 'no';
					$availability_status = 'no';
				}

				// Ensure the ID field is present and valid.
				if ( isset( $plugin_data['id'] ) && is_numeric( $plugin_data['id'] ) ) {
					// Update the database record for the specified plugin.
					$wpdb->update(// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prefix . 'saifgs_supported_plugins',
						array(
							'usability_status'    => $usability_status,
							'availability_status' => $availability_status,
						),
						array(
							'id' => $plugin_data['id'],
						),
						array( '%s', '%s' ), // Format for the values.
						array( '%d' )        // Format for the condition.
					);
				}
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query(
					"UPDATE `{$wpdb->prefix}saifgs_supported_plugins` 
					SET `usability_status` = 'no', `availability_status` = 'no' "
				);
			}

			// API Response.
			wp_send_json( 'updated ..' );
		}
	}
}
