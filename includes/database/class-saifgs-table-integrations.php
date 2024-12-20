<?php
/**
 * SAIFGS_Table_Integrations class.
 *
 * @package SA Integrations For Google Sheets
 */

namespace SAIFGS\Database;

if ( ! class_exists( '\SAIFGS\Database\SAIFGS_Table_Integrations' ) ) {
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
	class SAIFGS_Table_Integrations {

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
			$table_name      = $wpdb->prefix . 'saifgs_integrations';
			// @codingStandardsIgnoreStart
            if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
                $sql = "CREATE TABLE `{$table_name}` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `title` VARCHAR(50) NOT NULL,
                    `plugin_id` VARCHAR(50) NOT NULL,
                    `source_id` INT(11) NOT NULL,
                    `order_status` LONGTEXT NOT NULL,
                    `google_work_sheet_id` VARCHAR(50) NOT NULL,
                    `google_sheet_tab_id` VARCHAR(50) NOT NULL,
                    `google_sheet_column_map` LONGTEXT NOT NULL,
					`google_sheet_column_range` VARCHAR(50) NOT NULL,
					`disable_integration` VARCHAR(50) NOT NULL,
					`created_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE current_timestamp(),
	                PRIMARY KEY (`id`)
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
			$table_name = $wpdb->prefix . 'saifgs_integrations';
			// @codingStandardsIgnoreStart
            if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) == $table_name ) {
                $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
            }
			// @codingStandardsIgnoreEnd

		}
	}
}
