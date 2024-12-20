<?php
/**
 * Handles the Plugins Form Data API.
 *
 * @package SA Integrations For Google Sheets
 * @since 1.0.0
 */

namespace SAIFGS\RestAPI;

if ( ! class_exists( '\SAIFGS\RestAPI\SAIFGS_Plugins_Form_Data_API' ) ) {

	/**
	 * Class Plugins Form Data API.
	 *
	 * Handles the Plugins Form Data API.
	 */
	class SAIFGS_Plugins_Form_Data_API {

		// Traits used inside class.
		use \SAIFGS\Traits\SAIFGS_Singleton;
		use \SAIFGS\Traits\SAIFGS_Helpers;
		use \SAIFGS\Traits\SAIFGS_RestAPI;

		/**
		 * Base URL for the integrated form API route.
		 *
		 * @var string
		 */
		private static $saifgs_api_route_base_url = '/plugins-form-data';

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
		 * Registers REST API routes.
		 *
		 * @return void
		 */
		public function saifgs_register_rest_routes() {
			// Register a route to get the list of forms from supported plugins.
			register_rest_route(
				$this->saifgs_get_api_base_url(),
				self::$saifgs_api_route_base_url,
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'saifgs_get_plugins_form_list' ),
					'permission_callback' => array( $this, 'saifgs_permissions' ),
				)
			);
		}

		/**
		 * Retrieves a list of forms from specified plugins.
		 *
		 * @param \WP_REST_Request $request The request object.
		 * @return void
		 */
		public function saifgs_get_plugins_form_list( \WP_REST_Request $request ) {

			// Retrieve and decode the request body.
			$json_data = json_decode( $request->get_body(), true );

			// Sanitize the plugin name.
			$plugin_name = isset( $json_data['pluginName'] ) ? sanitize_text_field( $json_data['pluginName'] ) : '';
			$field       = array();

			// Validate the plugin name and fetch corresponding forms or data.
			switch ( $plugin_name ) {
				case 'wpforms':
					if ( function_exists( 'wpforms' ) ) {
						$posts = wpforms()->form->get();
						foreach ( $posts as $form ) {
							$field[] = array(
								'value' => (int) $form->ID,
								'label' => sanitize_text_field( $form->post_title ),
							);
						}
					}
					break;

				case 'contact_form_7':
					if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
						$posts = get_posts(
							array(
								'post_type'   => 'wpcf7_contact_form',
								'numberposts' => -1,
							)
						);
						foreach ( $posts as $form ) {
							$field[] = array(
								'value' => (int) $form->ID,
								'label' => sanitize_text_field( $form->post_title ),
							);
						}
					}
					break;

				case 'gravityforms':
					if ( is_plugin_active( 'gravityforms/gravityforms.php' ) ) {
						$forms = \RGFormsModel::get_forms( null, 'title' );
						foreach ( $forms as $form ) {
							$field[] = array(
								'value' => (int) $form->id,
								'label' => sanitize_text_field( $form->title ),
							);
						}
					}
					break;

				case 'woocommerce':
					if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
						$field[] = array(
							'value' => 1,
							'label' => __( 'WooCommerce Order', 'sa-integrations-for-google-sheets' ),
						);
					}
					break;

				default:
					// If the plugin name is unrecognized, return an empty array.
					$field = array();
					break;
			}

			// Send a JSON response with the collected data.
			wp_send_json( $field );
		}
	}
}
