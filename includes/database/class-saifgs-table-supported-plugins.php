<?php
/**
 * SAIFGS_Table_Supported_Plugins class.
 *
 * @package SA Integrations For Google Sheets
 */

namespace SAIFGS\Database;

if ( ! class_exists( '\SAIFGS\Database\SAIFGS_Table_Supported_Plugins' ) ) {
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
	class SAIFGS_Table_Supported_Plugins {

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
			$table_name      = $wpdb->prefix . 'saifgs_supported_plugins';
            // @codingStandardsIgnoreStart
			if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
                $sql = "CREATE TABLE `{$table_name}` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `title` VARCHAR(50) NOT NULL,
                    `key` VARCHAR(50) NOT NULL,
                    `usability_status` VARCHAR(50) NOT NULL,
                    `availability_status` VARCHAR(50) NOT NULL,
                    `image_url` VARCHAR(200) NOT NULL,
					`url` VARCHAR(200) NOT NULL,
                    `discription` LONGTEXT NOT NULL,
	                PRIMARY KEY (`id`)
                     ) $charset_collate;";
                require_once ABSPATH . 'wp-admin/includes/upgrade.php';
                dbDelta( $sql );
				$wpdb->insert(
					$table_name,
					array(
						'title' => 'Contact Form 7',
						'key' => 'contact_form_7',
						'usability_status' => 'no',
						'availability_status' => 'no',
						'image_url' => SAIFGS_URL_ASSETS_IMAGES.'/Contact-Form-7-logo.png',
						'url' => 'https://wordpress.org/plugins/contact-form-7/',
						'discription' => 'Contact Form 7 allows you to create and manage multiple forms easily, with customizable form fields and mail contents using simple markup. It supports features like Ajax-powered submissions, CAPTCHA for spam prevention, and Akismet filtering. This plugin is ideal for building functional and flexible contact forms for your WordPress site.',
					)
				);
				
				$wpdb->insert(
					$table_name,
					array(
						'title' => 'WooCommerce',
						'key' => 'woocommerce',
						'usability_status' => 'no',
						'availability_status' => 'no',
						'image_url' => SAIFGS_URL_ASSETS_IMAGES.'/woocommerce-logo.png',
						'url' => 'https://wordpress.org/plugins/woocommerce/',
						'discription' => 'WooCommerce powers online stores with ease, letting you manage products, orders, and payments efficiently. It supports Ajax-powered carts, multiple shipping methods, and various payment gateways. The platform is fully customizable and integrates with numerous extensions, enabling store owners to tailor their eCommerce experience to meet their needs.',
					)
				);
				
				$wpdb->insert(
					$table_name,
					array(
						'title' => 'wpforms',
						'key' => 'wpforms',
						'usability_status' => 'no',
						'availability_status' => 'no',
						'image_url' => SAIFGS_URL_ASSETS_IMAGES.'/wp-form-logo.png',
						'url' => 'https://wordpress.org/plugins/wpforms-lite/',
						'discription' => 'wpForms can manage various forms effortlessly, allowing you to build them using a beginner-friendly drag-and-drop interface. You can customize form fields and email notifications flexibly. The plugin supports responsive designs, file uploads, CAPTCHA, and integration with marketing tools like Constant Contact and payment systems like Stripe or PayPal.',
					)
				);
				
				$wpdb->insert(
					$table_name,
					array(
						'title' => 'Gravity Forms',
						'key' => 'gravityforms',
						'usability_status' => 'no',
						'availability_status' => 'no',
						'image_url' => SAIFGS_URL_ASSETS_IMAGES.'/gravity-forms-logo.png',
						'url' => 'https://www.gravityforms.com/',
						'discription' => 'Gravity Forms can handle multiple types of forms, and you can customize them along with their notification contents using a simple and intuitive interface. The plugin supports features like conditional logic, multi-page forms, file uploads, and more. It also integrates seamlessly with third-party tools like PayPal and Mailchimp. Spam protection is enabled through CAPTCHA and Akismet filtering.',
					)
				);
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
			$table_name = $wpdb->prefix . 'saifgs_supported_plugins';
            // @codingStandardsIgnoreStart
			if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) == $table_name ) {
                $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
            }
			// @codingStandardsIgnoreEnd
		}
	}
}
