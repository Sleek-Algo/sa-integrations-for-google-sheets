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
            // @codingStandardsIgnoreStart
            global $wpdb;

            $table_name = $wpdb->prefix . 'saifgs_contact_form_7_entrymeta';
            $results = '';

            if ( ! empty( $meta_key ) ) {
                $results = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT `meta_key`, `meta_value` FROM `{$table_name}` WHERE `entry_id` = %d AND `meta_key` = %s",
                        $id,
                        $meta_key
                    ),
                    ARRAY_A
                );
            } else {
                $results = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT `meta_key`, `meta_value` FROM `{$table_name}` WHERE `entry_id` = %d",
                        $id
                    ),
                    ARRAY_A
                );                
            }
            // @codingStandardsIgnoreEnd

			return wp_list_pluck( $results, 'meta_value', 'meta_key' );
		}
	}
}
