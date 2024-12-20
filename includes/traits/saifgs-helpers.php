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
			$table_name = $wpdb->prefix . 'saifgs_integrations';
            // @codingStandardsIgnoreStart
			$sql        = $wpdb->prepare( "SELECT * FROM $table_name WHERE source_id = %d AND Plugin_id = %s", $form_id, $plugin );
			$results    = $wpdb->get_results( $sql );
            // @codingStandardsIgnoreEnd

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
				}
				return $integration_data;
			} else {
				return false;
			}
		}
	}
}
