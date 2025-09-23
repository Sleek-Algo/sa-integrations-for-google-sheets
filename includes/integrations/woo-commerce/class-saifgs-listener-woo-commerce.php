<?php
/**
 * Handles the integration of Woo Commerce.
 *
 * @package SA Integrations For Google Sheets
 * @since 1.0.0
 */

namespace SAIFGS\Integrations\WooCommerce;

if ( ! class_exists( '\SAIFGS\Integrations\WooCommerce\SAIFGS_Listener_Woo_Commerce' ) ) {

	/**
	 * Class Listner Woo Commerce
	 *
	 * Handles the integration of Woo Commerce with Google Sheets.
	 */
	class SAIFGS_Listener_Woo_Commerce {

		// Traits used inside the class for singleton, helper methods, and REST API functionality.
		use \SAIFGS\Traits\SAIFGS_Singleton;
		use \SAIFGS\Traits\SAIFGS_Helpers;

		/**
		 * Constructor method to initialize Google Sheets service and register hooks.
		 */
		public function __construct() {
			add_action( 'woocommerce_new_order', array( $this, 'saifgs_handle_new_order' ), 9999, 2 );
		}

		/**
		 * Handle new WooCommerce order and sync data to Google Sheets.
		 *
		 * @param int            $order_id The ID of the WooCommerce order.
		 * @param WC_Order|false $order    The WooCommerce order object.
		 */
		public function saifgs_handle_new_order( $order_id, $order ) {

			$order_data       = $order->get_data();
			$integration_data = $this->saifgs_get_integration_data();

			// Check if integration data exists.
			if ( empty( $integration_data ) || ! is_array( $integration_data ) ) {
				return;
			}

			// Loop through each integration configuration.
			foreach ( $integration_data as $data ) {
				$order_status = maybe_unserialize( $data->order_status );
				if ( is_array( $order_status ) && in_array( $order->get_status(), $order_status, true ) ) {
					// Map order data to Google Sheets format.
					$google_sheet_prepare_data = $this->saifgs_google_sheet_data_mapping( $data, $order, $order_data, 'new_order' );

					// Build the Google Sheet range.
					$sheet_tab_id = sanitize_text_field( $data->google_sheet_tab_id );
					$range        = $sheet_tab_id . '!' . sanitize_text_field( $data->google_sheet_column_range );

					// Insert the prepared data into Google Sheets.
					$this->saifgs_insert_data_to_google_sheet( $google_sheet_prepare_data, sanitize_text_field( $data->google_work_sheet_id ), $data->google_sheet_tab_id, $order_id, $range, $data, 'new_order' );
				}
			}
		}

		/**
		 * Fetch integration data for WooCommerce from the database.
		 *
		 * @return array Integration data results.
		 */
		public function saifgs_get_integration_data() {
			global $wpdb;

			// Set cache key and group.
			$cache_key   = 'saifgs_woocommerce_integrations';
			$cache_group = 'saifgs_integrations';

			// Try to get cached data first.
			$results = wp_cache_get( $cache_key, $cache_group );

			// If not found in cache, fetch from database.
			if ( false === $results ) {

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$results = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT `id`, `title`, `plugin_id`, `source_id`, `order_status`, 
						`google_work_sheet_id`, `google_sheet_tab_id`, `google_sheet_column_map`, 
						`google_sheet_column_range`, `disable_integration` 
						FROM `{$wpdb->prefix}saifgs_integrations` 
						WHERE `plugin_id` = %s",
						'woocommerce'
					)
				);

				// Cache the results for 12 hours (adjust as needed).
				wp_cache_set( $cache_key, $results, $cache_group, 12 * HOUR_IN_SECONDS );
			}

			return $results;
		}

		/**
		 * Map WooCommerce order data to Google Sheets columns.
		 *
		 * @param object $integration_data Integration data object containing column mappings.
		 * @param object $order WooCommerce order object.
		 * @param array  $order_data Array of order data.
		 * @param string $order_type Type of order operation ('new_order' or 'update_order').
		 * @return array Mapped data prepared for Google Sheets.
		 */
		public function saifgs_google_sheet_data_mapping( $integration_data, $order, $order_data, $order_type ) {
			$google_sheet_prepare_data = array();

			// Unserialize column mapping data.
			$integrations = maybe_unserialize( $integration_data->google_sheet_column_map );
			if ( ! empty( $integrations ) && is_array( $integrations ) ) {

				foreach ( $integrations as $data ) {
					// Ensure the required properties exist and are valid.
					if ( empty( $data->source_filed_index ) || empty( $data->google_sheet_index ) ) {
						continue; // Skip invalid mapping data.
					}

					$source_field_index = sanitize_text_field( $data->source_filed_index );
					$google_sheet_index = sanitize_text_field( $data->google_sheet_index );
					$method_name        = 'get_' . $source_field_index;

					if ( 'new_order' === $order_type ) {
						if ( method_exists( $order, $method_name ) ) {
							if ( 'get_coupon_codes' === $method_name ) {
								$coupon_code                                      = implode( ', ', array_map( 'sanitize_text_field', $order->$method_name() ) );
								$google_sheet_prepare_data[ $google_sheet_index ] = $coupon_code;
							} else {
								$google_sheet_prepare_data[ $google_sheet_index ] = ! is_array( $order->$method_name() ) ? sanitize_text_field( $order->$method_name() ) : null;
							}
						} elseif ( isset( $order_data[ $source_field_index ] ) ) {
							$google_sheet_prepare_data[ $google_sheet_index ] = ! is_array( $order_data[ $source_field_index ] ) ? sanitize_text_field( $order_data[ $source_field_index ] ) : null;
						} else {
							$google_sheet_prepare_data = array_merge(
								$google_sheet_prepare_data,
								$this->saifgs_map_order_items( $order, $source_field_index, $google_sheet_index )
							);
						}
					}
				}
			}

			return $google_sheet_prepare_data;
		}

		/**
		 * Map WooCommerce order items to Google Sheets data.
		 *
		 * @param object $order WooCommerce order object.
		 * @param string $source_field_index Source field index for item data.
		 * @param string $google_sheet_index Google Sheet column index.
		 * @param bool   $valid_toggle Whether the data is valid for mapping.
		 * @return array Mapped order item data.
		 */
		private function saifgs_map_order_items( $order, $source_field_index, $google_sheet_index, $valid_toggle = false ) {
			$mapped_data = array();
			$item_values = array();

			foreach ( $order->get_items() as $item ) {
				$value = null;
				if ( 'name' === $source_field_index ) {
					$value = $item->get_name();
				} elseif ( 'quantity' === $source_field_index ) {
					$value = $item->get_quantity();
				} elseif ( 'product_id' === $source_field_index ) {
					$value = $item->get_product_id();
				} elseif ( 'subtotal_tax' === $source_field_index ) {
					$value = $item->get_subtotal_tax();
				}

				if ( ! is_null( $value ) ) {
					$item_values[] = sanitize_text_field( $value );
				}
			}

			if ( ! empty( $item_values ) ) {
				$mapped_data[] = array(
					'valid'             => $valid_toggle,
					$google_sheet_index => implode( ' | ', $item_values ),
				);
			}
			return $mapped_data;
		}

		/**
		 * Inserts data into a Google Sheet.
		 *
		 * @param array  $data               The data to be inserted.
		 * @param string $google_work_sheet_id The Google worksheet ID.
		 * @param string $google_sheet_tab_id The Google sheet tab ID.
		 * @param int    $order_id           The order ID associated with the data.
		 * @param string $range              The cell range for insertion.
		 * @param array  $integration_data   Integration mapping data.
		 * @param string $status             The status of the operation.
		 *
		 * @throws \Exception                If the insertion process fails.
		 */
		private function saifgs_insert_data_to_google_sheet( $data, $google_work_sheet_id, $google_sheet_tab_id, $order_id, $range, $integration_data, $status ) {
			global $wpdb;
			if ( ! is_array( $data ) || empty( $google_work_sheet_id ) || empty( $google_sheet_tab_id ) || empty( $range ) || empty( $integration_data ) || empty( $order_id ) ) {
				throw new \Exception( esc_html__( 'Invalid or missing input data.', 'sa-integrations-for-google-sheets' ) );
			}

			$set_values = array();
			foreach ( $data as $get_value ) {
				if ( is_array( $get_value ) ) {
					$get_value = implode( ', ', array_map( 'sanitize_text_field', $get_value ) );
				} else {
					$get_value = sanitize_text_field( $get_value );
				}
				$set_values[] = strval( $get_value );
			}
			try {
				// Append the new data to Google Sheets.
				if ( class_exists( '\SAIFGS\Classes\SAIFGS_Google_Apis_Authenticator' ) ) {
					$client = \SAIFGS\Classes\SAIFGS_Google_Apis_Authenticator::get_instance();
				} else {
					$client = '';
				}

				// Request link.
				$url = 'https://sheets.googleapis.com/v4/spreadsheets/' . $google_work_sheet_id . '/values/' . $google_sheet_tab_id . '!A:A:append?valueInputOption=USER_ENTERED';

				// Argument Array.
				$args = array(
					'headers' => array(
						'Content-Type' => 'application/json',
					),
					'body'    => '{"range":"' . $google_sheet_tab_id . '!A:A", "majorDimension":"ROWS", "values":[' . wp_json_encode( array_values( $set_values ) ) . ']}',
				);

				$response = $client->saifgs_request( $url, $args, 'post' );

				$sheet_tab_row_id = $response['updates']['updatedRange'];

				// Insert the row mapping into the integration rows table.
				$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
					$wpdb->prefix . 'saifgs_integrations_rows',
					array(
						'integration_id'      => $integration_data->id,
						'sheet_id'            => $google_work_sheet_id,
						'sheet_tab_id'        => $google_sheet_tab_id,
						'sheet_tab_row_range' => $sheet_tab_row_id,
						'source_row_id'       => $order_id,
					)
				);
			} catch ( \Exception $e ) {
				// translators: %s will be replaced with the error message.
				throw new \Exception( esc_html( sprintf( __( 'Failed to insert data into Google Sheets: %s', 'sa-integrations-for-google-sheets' ), $e->getMessage() ) ) );
			}
		}
	}
}
