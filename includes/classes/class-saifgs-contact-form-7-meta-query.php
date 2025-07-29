<?php
/**
 * SAIFGS_Contact_Form_7_Meta_Query class.
 *
 * @package SA Integrations For Google Sheets
 */

namespace SAIFGS\Classes;

if ( ! class_exists( '\SAIFGS\Classes\SAIFGS_Contact_Form_7_Meta_Query' ) ) {
	/**
	 * Load metadata function.
	 *
	 * This class content metadata functions.
	 *
	 * @copyright  sleekalgo
	 * @version    Release: 1.0.0
	 * @link       https://www.sleekalgo.com
	 * @package    SA Integrations For Google Sheets
	 * @since      Class available since Release 1.0.0
	 */
	class SAIFGS_Contact_Form_7_Meta_Query {

		/**
		 * Traits used inside class
		 */
		use \SAIFGS\Traits\SAIFGS_Singleton;
		use \SAIFGS\Traits\SAIFGS_Helpers;

		/**
		 * A constructor to prevent this class from being loaded more than once.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function __construct() {
		}

		/**
		 * Get entries meta values.
		 *
		 * @since 1.0.0
		 *
		 * @param  int     $id entries id.
		 * @param  string  $meta_key entries meta key.
		 * @param  boolean $single true or false.
		 * @return array meta_data meta values.
		 */
		public static function saifgs_get_meta( $id, $meta_key = '', $single = true ) {
			$cache_key = 'saifgs_get_meta_' . $id . '_' . $meta_key;
			$results   = wp_cache_get( $cache_key );

			if ( false === $results ) {
				global $wpdb;

				if ( ! empty( $meta_key ) ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table requires direct access.
					$results = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT `meta_key`, `meta_value` FROM `{$wpdb->prefix}saifgs_contact_form_7_entrymeta` WHERE `entry_id` = %d AND `meta_key` = %s",
							$id,
							$meta_key
						),
						ARRAY_A
					);
				} else {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table requires direct access.
					$results = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT `meta_key`, `meta_value` FROM `{$wpdb->prefix}saifgs_contact_form_7_entrymeta` WHERE `entry_id` = %d",
							$id
						),
						ARRAY_A
					);
				}

				// Update Data into Chache just for 10 minute.
				wp_cache_set( $cache_key, $results, 'saifgs_get_meta', ( 10 * MINUTE_IN_SECONDS ) );
			}

			return wp_list_pluck( $results, 'meta_value', 'meta_key' );
		}
	}
}
