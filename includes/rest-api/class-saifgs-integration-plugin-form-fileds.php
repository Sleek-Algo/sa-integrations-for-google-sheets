<?php
/**
 * Handles the Integration Plugin Form Fileds.
 *
 * @package SA Integrations For Google Sheets
 * @since 1.0.0
 */

namespace SAIFGS\RestApi;

use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStoreMeta;

if ( ! class_exists( '\SAIFGS\RestApi\SAIFGS_Integration_Plugin_Form_Fileds' ) ) {
	/**
	 * Class Integration Plugin Form Fileds.
	 *
	 * Handles the Integration Edit Form API.
	 */
	class SAIFGS_Integration_Plugin_Form_Fileds {


		// Traits used inside class.
		use \SAIFGS\Traits\SAIFGS_Singleton;
		use \SAIFGS\Traits\SAIFGS_Helpers;
		use \SAIFGS\Traits\SAIFGS_RestAPI;

		/**
		 * Base URL for the integrated form API route.
		 *
		 * @var string
		 */
		private static $saifgs_api_route_base_url = '/plugins-form-field-data';

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
		 * Registers REST API routes for the plugin.
		 *
		 * This method registers a new REST API route that supports POST requests. It defines the endpoint
		 * for retrieving a list of form fields from various plugins, as specified by the route base URL and
		 * the provided callback function. Additionally, it sets up a permission callback to manage access
		 * control for this endpoint.
		 *
		 * The endpoint is registered with the following settings:
		 * - **Methods**: POST
		 * - **Callback**: `saifgs_get_plugins_form_field_list`
		 * - **Permission Callback**: `permissions`
		 *
		 * The `saifgs_get_plugins_form_field_list` method is responsible for handling the request and
		 * returning the list of form fields based on the plugin specified in the request body.
		 *
		 * @return void
		 */
		public function saifgs_register_rest_routes() {
			register_rest_route(
				$this->saifgs_get_api_base_url(),
				self::$saifgs_api_route_base_url,
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'saifgs_get_plugins_form_field_list' ),
					'permission_callback' => array( $this, 'saifgs_permissions' ),
				)
			);
		}

		/**
		 * Retrieves a list of form fields for specified plugins.
		 *
		 * This method handles a POST request to fetch form fields based on the plugin name and form ID provided
		 * in the request body. It supports multiple plugins including WPForms, Contact Form 7, Gravity Forms,
		 * and WooCommerce. Depending on the plugin specified, it retrieves and formats field data accordingly.
		 *
		 * - For WPForms, it retrieves fields from the form's content.
		 * - For Contact Form 7, it extracts field names from the form tags and the form content.
		 * - For Gravity Forms, it retrieves fields from the form metadata.
		 * - For WooCommerce, it returns a predefined set of order-related fields.
		 *
		 * The method uses `wp_send_json()` to return the list of fields in JSON format.
		 *
		 * @param \WP_REST_Request $request The REST API request object containing the request data.
		 *
		 * @return void
		 */
		public function saifgs_get_plugins_form_field_list( \WP_REST_Request $request ) {
			// Decode the JSON request body.
			$json_data = json_decode( $request->get_body(), true );

			$plugin_name    = ( isset( $json_data['plugin_name'] ) && '' !== $json_data['plugin_name'] ? sanitize_text_field( $json_data['plugin_name'] ) : '' );
			$plugin_form_id = ( isset( $json_data['plugin_form_id'] ) && '' !== $json_data['plugin_form_id'] ? absint( $json_data['plugin_form_id'] ) : '' );
			$order_status   = ( isset( $json_data['order_status'] ) && '' !== $json_data['order_status'] ? sanitize_text_field( $json_data['order_status'] ) : '' );

			$filed = array();

			// Validate the plugin name and process accordingly.
			if ( 'wpforms' === $plugin_name && '' !== $plugin_form_id ) {
				$posts = wpforms()->form->get( $plugin_form_id );
				if ( ! empty( $posts->post_content ) ) {
					$wpforms_encode_data = json_decode( $posts->post_content );
					if ( null !== $wpforms_encode_data && isset( $wpforms_encode_data->fields ) ) {
						$premium_fields = array( 'file-upload' );
						foreach ( $wpforms_encode_data->fields as $index => $data ) {
							$is_premium = isset( $data->type ) && in_array( $data->type, $premium_fields, true );
							$filed[]    = array(
								'label'      => sanitize_text_field( $data->label ),
								'value'      => absint( $data->id ),
								'is_premium' => ( $is_premium ? 'yes' : 'no' ),
							);
						}
					}
				}
				wp_send_json( $filed );
			} elseif ( 'contact_form_7' === $plugin_name ) {
				// Fetch fields for Contact Form 7.
				$wpcf7 = \WPCF7_ContactForm::get_instance( $plugin_form_id );
				if ( $wpcf7 ) {
					// Get the form fields.
					$form_fields    = $wpcf7->scan_form_tags();
					$field_names    = array();
					$premium_fields = array( 'file' ); // Assuming 'file' is a premium field type.

					foreach ( $form_fields as $field ) {
						if ( ! empty( $field->name ) ) {
							$field_names[] = array(
								'label'      => sanitize_text_field( $field->name ),
								'value'      => sanitize_text_field( $field->name ),
								'is_premium' => ( in_array( $field->basetype, $premium_fields, true ) ? 'yes' : 'no' ),
							);
						}
					}
				}
				$posts       = get_posts(
					array(
						'post_type'   => 'wpcf7_contact_form',
						'post__in'    => array( $plugin_form_id ),
						'numberposts' => -1,
					)
				);
				$fields_data = array();
				if ( ! empty( $posts ) ) {
					$filed = array();
					foreach ( $posts as $form ) {
						preg_match_all( '/<label>\\s*(.*?)\\s*\\[([^\\]]+)](?:(?!submit).)*<\\/label>/', $form->post_content, $matches );
						$label_texts = $matches[1];
						$fields_data = $label_texts;
					}
				}
				foreach ( $field_names as $index => $data ) {
					$string_with_underscore = str_replace( ' ', '_', $data );
					$filed[]                = array(
						'label'      => sanitize_text_field( $data['label'] ),
						'value'      => sanitize_text_field( $data['value'] ),
						'is_premium' => sanitize_text_field( $data['is_premium'] ),
					);
				}
				wp_send_json( $filed );
			} elseif ( 'gravityforms' === $plugin_name && '' !== $plugin_form_id ) {
				$form           = \RGFormsModel::get_form_meta( $plugin_form_id );
				$premium_fields = array( 'fileupload', 'list' ); // Premium field types.

				if ( is_array( $form['fields'] ) ) {
					$is_file_ = array();
					foreach ( $form['fields'] as $gravity_form_field ) {
						$is_premium = isset( $gravity_form_field->type ) && in_array( $gravity_form_field->type, $premium_fields, true );
						$filed[]    = array(
							'label'      => sanitize_text_field( $gravity_form_field->label ),
							'value'      => absint( $gravity_form_field->id ),
							'is_premium' => ( $is_premium ? 'yes' : 'no' ),
						);
					}
				}
				wp_send_json( $filed );
			} elseif ( 'woocommerce' === $plugin_name && 1 === (int) $plugin_form_id ) {
				// Define WooCommerce fields in an array.
				$fields = array(
					array(
						'label' => 'Order ID',
						'value' => 'id',
					),
					array(
						'label' => 'Currency',
						'value' => 'currency',
					),
					array(
						'label' => 'Total Fees',
						'value' => 'total_fees',
					),
					array(
						'label' => 'Subtotal',
						'value' => 'subtotal',
					),
					array(
						'label' => 'Tax Total',
						'value' => 'tax_totals',
					),
					array(
						'label' => 'Total Discount',
						'value' => 'total_discount',
					),
					array(
						'label' => 'Total Tax',
						'value' => 'total_tax',
					),
					array(
						'label' => 'Total Refunded',
						'value' => 'total_refunded',
					),
					array(
						'label' => 'Item Count Refunded',
						'value' => 'item_count_refunded',
					),
					array(
						'label' => 'QTY Refunded for Item',
						'value' => 'qty_refunded_for_item',
					),
					array(
						'label' => 'Remaining Refund Amount',
						'value' => 'remaining_refund_amount',
					),
					array(
						'label' => 'Item Count',
						'value' => 'item_count',
					),
					array(
						'label' => 'Item Coupon Code',
						'value' => 'coupon_codes',
					),
					array(
						'label' => 'Shipping Method',
						'value' => 'shipping_method',
					),
					array(
						'label' => 'Date Created',
						'value' => 'date_created',
					),
					array(
						'label' => 'Date Modified',
						'value' => 'date_modified',
					),
					array(
						'label' => 'Date Completed',
						'value' => 'date_completed',
					),
					array(
						'label' => 'Date Paid',
						'value' => 'date_paid',
					),
					array(
						'label' => 'Customer ID',
						'value' => 'customer_id',
					),
					array(
						'label' => 'User ID',
						'value' => 'user_id',
					),
					array(
						'label' => 'Customer IP Address',
						'value' => 'customer_ip_address',
					),
					array(
						'label' => 'Billing First Name',
						'value' => 'billing_first_name',
					),
					array(
						'label' => 'Billing Last Name',
						'value' => 'billing_last_name',
					),
					array(
						'label' => 'Billing Company',
						'value' => 'billing_company',
					),
					array(
						'label' => 'Billing Address 1',
						'value' => 'billing_address_1',
					),
					array(
						'label' => 'Billing Address 2',
						'value' => 'billing_address_2',
					),
					array(
						'label' => 'Billing City',
						'value' => 'billing_city',
					),
					array(
						'label' => 'Billing State',
						'value' => 'billing_state',
					),
					array(
						'label' => 'Billing Postcode',
						'value' => 'billing_postcode',
					),
					array(
						'label' => 'Billing Country',
						'value' => 'billing_country',
					),
					array(
						'label' => 'Billing Email',
						'value' => 'billing_email',
					),
					array(
						'label' => 'Billing Phone',
						'value' => 'billing_phone',
					),
					array(
						'label' => 'Shipping First Name',
						'value' => 'shipping_first_name',
					),
					array(
						'label' => 'Shipping Last Name',
						'value' => 'shipping_last_name',
					),
					array(
						'label' => 'Shipping Company',
						'value' => 'shipping_company',
					),
					array(
						'label' => 'Shipping Address 1',
						'value' => 'shipping_address_1',
					),
					array(
						'label' => 'Shipping Address 2',
						'value' => 'shipping_address_2',
					),
					array(
						'label' => 'Shipping City',
						'value' => 'shipping_city',
					),
					array(
						'label' => 'Shipping Status',
						'value' => 'shipping_state',
					),
					array(
						'label' => 'Shipping Postcode',
						'value' => 'shipping_postcode',
					),
					array(
						'label' => 'Shipping Country',
						'value' => 'shipping_country',
					),
					array(
						'label' => 'Shipping Address Map Url',
						'value' => 'shipping_address_map_url',
					),
					array(
						'label' => 'Payment Method Title',
						'value' => 'payment_method_title',
					),
					array(
						'label' => 'Transaction ID',
						'value' => 'transaction_id',
					),
					array(
						'label' => 'Checkout Payment URL',
						'value' => 'checkout_payment_url',
					),
					array(
						'label' => 'Cancel Order URL',
						'value' => 'cancel_order_url',
					),
					array(
						'label' => 'Cancel Endpoint',
						'value' => 'cancel_endpoint',
					),
					array(
						'label' => 'Status',
						'value' => 'status',
					),
					array(
						'label' => 'Checkout Order Received URL',
						'value' => 'checkout_order_received_url',
					),
					array(
						'label' => 'Product ID',
						'value' => 'product_id',
					),
					array(
						'label' => 'Product Name',
						'value' => 'name',
					),
					array(
						'label' => 'Product Quantity',
						'value' => 'quantity',
					),
					array(
						'label' => 'Product Total',
						'value' => 'total',
					),
					array(
						'label' => 'Product Subtotal Tax',
						'value' => 'subtotal_tax',
					),
					array(
						'label' => 'Product Type',
						'value' => 'type',
					),
				);

				// Loop through each field and set the values (example: pulling from WooCommerce meta).
				$filed = array();
				foreach ( $fields as $field ) {
					$filed[] = array(
						'label'      => sanitize_text_field( $field['label'] ),
						'value'      => sanitize_text_field( $field['value'] ),
						'is_premium' => 'no',
					);
				}

				// For non-premium users, add premium fields with is_premium set to 'yes'.
				$order_meta_keys = wc_get_container()->get( OrdersTableDataStoreMeta::class )->get_meta_keys( 100, 'ASC', false );
				foreach ( $order_meta_keys as $meta_key ) {
					$filed[] = array(
						'label'      => ucfirst( str_replace( '_', ' ', sanitize_text_field( $meta_key ) ) ),
						'value'      => sanitize_text_field( $meta_key ),
						'is_premium' => 'yes',
					);
				}
				// Send the final array as a JSON response.
				wp_send_json( $filed );
			}
			wp_send_json( '' );
		}
	}
}
