<?php
/**
 * SAIFGS_Table_Contact_Form_7_Entrymeta class.
 *
 * @package SA Integrations For Google Sheets
 */

namespace SAIFGS\Database;

if ( ! class_exists( '\SAIFGS\Database\SAIFGS_Table_Contact_Form_7_Entrymeta' ) ) {

	/**
	 * Create database
	 *
	 * This class Create table
	 *
	 * @copyright  sleekalgo
	 * @version    Release: 1.0.0
	 * @link       https://www.sleekalgo.com
	 * @package    SA Integrations For Google Sheets
	 * @since      Class available since Release 1.0.0
	 */
	class SAIFGS_Table_Contact_Form_7_Entrymeta {

		/**
		 * Create database function.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function create() {
			global $wpdb;
			$charset_collate = $wpdb->get_charset_collate();
			$table_name      = $wpdb->prefix . 'saifgs_contact_form_7_entrymeta';
            // @codingStandardsIgnoreStart
            if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
                $sql = "CREATE TABLE `{$table_name}` (
                    `meta_id` bigint(20) NOT NULL AUTO_INCREMENT,
                    `entry_id` bigint(20) NOT NULL DEFAULT '0',
                    `meta_key` varchar(255) DEFAULT NULL,
                    `meta_value` longtext,
	                PRIMARY KEY (`meta_id`)
                     ) $charset_collate;";
                require_once ABSPATH . 'wp-admin/includes/upgrade.php';
                dbDelta( $sql );
				
            }
            // @codingStandardsIgnoreEnd
		}

		/**
		 * Drop table function.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function drop() {
			global $wpdb;
			$table_name = $wpdb->prefix . 'saifgs_contact_form_7_entrymeta';
            // @codingStandardsIgnoreStart
            if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) == $table_name ) {
                $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
            }
            // @codingStandardsIgnoreEnd
		}
	}
}
