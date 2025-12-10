<?php
/**
 * Handles the SAIFGS_Helpers Trait
 *
 * @package SA Integrations For Google Sheets
 * @since 1.0.0
 */

namespace SAIFGS\Traits;

if ( ! trait_exists( '\SAIFGS\Traits\SAIFGS_Helpers' ) ) {

	/**
	 * SAIFGS_Helpers Trait
	 *
	 * Provides various utility methods for working with the WordPress filesystem,
	 * logging, Google Sheets integration, and database interactions.
	 */
	trait SAIFGS_Helpers {

		/**
		 * Retrieves the WordPress filesystem object.
		 *
		 * This method initializes the WordPress filesystem if it is not already
		 * set up. It includes the necessary file handling functions from WordPress
		 * and ensures the global `$wp_filesystem` object is available for use.
		 *
		 * @return \WP_Filesystem|false The WordPress filesystem object, or false on failure.
		 */
		public function saifgs_filesystem() {
			global $wp_filesystem;

			require_once ABSPATH . 'wp-admin/includes/file.php';

			if ( ! $wp_filesystem ) {
				WP_Filesystem();
			}

			return $wp_filesystem;
		}

		/**
		 * Fetches integration data from the database based on plugin and form ID.
		 *
		 * This method queries the `saifgs_integrations` table to retrieve integration
		 * details associated with the provided plugin and form ID. It returns an array
		 * of integration records, including Google Sheets-related information. If no
		 * records are found, it returns `false`.
		 *
		 * @param string $plugin The identifier for the plugin.
		 * @param int    $form_id The ID of the form for which integration data is retrieved.
		 *
		 * @return array|false An array of integration data if found, otherwise `false`.
		 */
		public function saifgs_get_integration_data( $plugin, $form_id ) {
			global $wpdb;

			// Generate cache key.
			$cache_key   = 'saifgs_integration_' . $plugin . '_' . $form_id;
			$cache_group = 'saifgs_integrations';

			// Try to get cached data first.
			$integration_data = wp_cache_get( $cache_key, $cache_group );

			if ( false !== $integration_data ) {
				return $integration_data;
			}

			// Query database if not found in cache.
			$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT `id`, `google_work_sheet_id`, `google_sheet_tab_id`, 
						`google_sheet_column_range`, `google_sheet_column_map` 
					FROM `{$wpdb->prefix}saifgs_integrations` 
					WHERE `source_id` = %d AND `Plugin_id` = %s",
					$form_id,
					$plugin
				)
			);

			$integration_data = false;

			if ( $results ) {
				$integration_data = array();
				foreach ( $results as $result ) {
					$integration_data[] = array(
						'id'                        => $result->id,
						'google_work_sheet_id'      => $result->google_work_sheet_id,
						'google_sheet_tab_id'       => $result->google_sheet_tab_id,
						'google_sheet_column_range' => $result->google_sheet_column_range,
						'google_sheet_column_map'   => maybe_unserialize( $result->google_sheet_column_map ),
					);

					// Cache the results for 1 hour (adjust as needed).
					wp_cache_set( $cache_key, $integration_data, $cache_group, HOUR_IN_SECONDS );
				}
				return $integration_data;
			} else {
				return false;
			}
		}

		/**
		 * Converts a column index to its Google Sheets column location (e.g., A, B, AA).
		 *
		 * @param int $index Zero-based column index.
		 * @return string Column location (e.g., A, B, Z, AA).
		 */
		private function saifgs_get_column_location( $index ) {
			$column = '';
			while ( $index >= 0 ) {
				$column = chr( $index % 26 + 65 ) . $column;
				$index  = intval( $index / 26 ) - 1;
			}

			// Append row number for A1 notation.
			return $column . '1';
		}

		/**
		 * Determines the correct active access token to use.
		 * Priority: 1. Valid User OAuth Token, 2. Service Account Token.
		 *
		 * @return string|null The access token, or null if none found/valid.
		 */
		private function saifgs_get_active_access_token() {
			// 1. Check for a valid OAuth token from the auto-connect system
			$oauth_token = get_option( 'saifgs_auto_connect_token' );
			$oauth_expired = get_option( 'saifgs_auto_connect_auth_expired', 'false' );

			if ( ! empty( $oauth_token ) && $oauth_expired === 'false' ) {
				// Optional: Perform a lightweight validation (e.g., check expiry time)
				$expiry_time = get_option( 'saifgs_auto_connect_token_expiry', 0 );
				if ( $expiry_time > ( time() + 300 ) ) { // 5-minute buffer
					error_log( 'SAIFGS: Using valid OAuth access token for integration list.' );
					return $oauth_token;
				} else {
					error_log( 'SAIFGS: OAuth token appears expired. Marking as expired.' );
					update_option( 'saifgs_auto_connect_auth_expired', 'true' );
				}
			}

			// 2. Fall back to the Service Account token
			error_log( 'SAIFGS: Falling back to Service Account token for integration list.' );
			if ( class_exists( '\SAIFGS\Classes\SAIFGS_Google_Apis_Authenticator' ) ) {
				$client = \SAIFGS\Classes\SAIFGS_Google_Apis_Authenticator::get_instance();
				return $client->saifgs_get_access_token(); // This generates/fetches the service account token
			}

			error_log( 'SAIFGS: No access token available for integration list.' );
			return null;
		}

		/**
		 * Utility function to check if the currently used token is from OAuth.
		 * (Simplistic check based on the source option).
		 *
		 * @return bool
		 */
		private function saifgs_is_oauth_token() {
			$token = get_option( 'saifgs_auto_connect_token' );
			return ! empty( $token );
		}
	}
}
