<?php
/**
 * Handles the Integration API.
 *
 * @package SA Integrations For Google Sheets
 * @since 1.0.0
 */

namespace SAIFGS\RestApi;

if ( ! class_exists( '\SAIFGS\RestApi\SAIFGS_Integrations_API' ) ) {

	/**
	 * Class Integration API.
	 *
	 * Handles the Integration API.
	 */
	class SAIFGS_Integrations_API {

		// Traits used inside class.
		use \SAIFGS\Traits\SAIFGS_Singleton;
		use \SAIFGS\Traits\SAIFGS_Helpers;
		use \SAIFGS\Traits\SAIFGS_RestAPI;

		/**
		 * Base URL for the integrated form API route.
		 *
		 * @var string
		 */
		private static $saifgs_api_route_base_url = '/plugin-integrations';

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
		 */
		public function saifgs_register_rest_routes() {
			// Register the route for checking plugin status.
			register_rest_route(
				$this->saifgs_get_api_base_url(),
				self::$saifgs_api_route_base_url,
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'saifgs_check_plugin_status' ),
					'permission_callback' => array( $this, 'saifgs_permissions' ),
				)
			);

		}

		/**
		 * Checks the status of a specified plugin based on the request parameters.
		 *
		 * @param \WP_REST_Request $request The REST API request object.
		 * @return void
		 */
		public function saifgs_check_plugin_status( \WP_REST_Request $request ) {
			// Extract query parameters from the request.
			$data = $request->get_query_params();

			// Retrieve and sanitize input parameters.
			$plugin_checked = isset( $data['checked']['checked'] ) ? sanitize_text_field( $data['checked']['checked'] ) : '';
			$plugin_name    = isset( $data['plugin_name']['key'] ) ? sanitize_text_field( $data['plugin_name']['key'] ) : '';

			// Initialize default validation status.
			$checking_validation = false;

			// Check if the plugin is marked as "checked".
			if ( 'true' === $plugin_checked ) {
				// Determine plugin status based on the provided plugin name.
				switch ( $plugin_name ) {
					case 'contact_form_7':
						$checking_validation = is_plugin_active( 'contact-form-7/wp-contact-form-7.php' );
						break;

					case 'woocommerce':
						$checking_validation = is_plugin_active( 'woocommerce/woocommerce.php' );
						break;

					case 'wpforms':
						$checking_validation = function_exists( 'wpforms' );
						break;

					case 'gravityforms':
						$checking_validation = is_plugin_active( 'gravityforms/gravityforms.php' );
						break;

					default:
						// For unknown plugin names, validation remains false.
						$checking_validation = false;
						break;
				}

				// Send a JSON response with the validation status.
				wp_send_json( $checking_validation );
			}
		}
	}
}
