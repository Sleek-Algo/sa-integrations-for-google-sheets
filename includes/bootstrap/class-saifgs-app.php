<?php
/**
 * App class.
 *
 * @package SA Integrations For Google Sheets
 */

namespace SAIFGS\Bootstrap;

if ( ! class_exists( '\SAIFGS\Bootstrap\SAIFGS_App' ) ) {
	/**
	 * Load Plugin functionality
	 *
	 * This class call all the core functionality
	 *
	 * @copyright  sleekalgo
	 * @version    Release: 1.0.0
	 * @link       https://www.sleekalgo.com
	 * @package    SA Integrations For Google Sheets
	 * @since      Class available since Release 1.0.0
	 */
	class SAIFGS_App {

		/**
		 * Traits used inside class.
		 */
		use \SAIFGS\Traits\SAIFGS_Singleton;
		use \SAIFGS\Traits\SAIFGS_Helpers;

		/**
		 * A constructor to prevent this class from being loaded more than once.
		 *
		 * @see Plugin::instance()
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function __construct() {
			add_action( 'saifgs_plugin_loaded', array( $this, 'saifgs_init_localization' ), 10 );
			add_action( 'init', array( $this, 'saifgs_init_integrations' ), 10 );
			add_action( 'init', array( $this, 'saifgs_init_pages' ), 10 );
			add_action( 'init', array( $this, 'saifgs_init_settings' ), 10 );
			add_action( 'init', array( $this, 'saifgs_init_rest_api' ), 10 );
			add_action( 'init', array( $this, 'saifgs_init_create_attachments_folder' ), 10 );
			add_action( 'init', array( $this, 'saifgs_init_create_credentials_folder' ), 10 );
		}

		/**
		 * Manage activation plugin
		 *
		 * @return void
		 */
		public function saifgs_init_localization() {
			load_plugin_textdomain( SAIFGS_TEXT_DOMAIN, false, SAIFGS_TEXT_DOMAIN_PATH );
		}

		/**
		 * Init GoogleSheet Integrations Listers Intsance.
		 *
		 * @since 1.0.0
		 *
		 * @see GoogleSheet Lister::instance()
		 * @return void.
		 */
		public function saifgs_init_integrations() {
			\SAIFGS\Integrations\ContactForm7\SAIFGS_Listner_CF7::get_instance();
			\SAIFGS\Integrations\WPForms\SAIFGS_Listener_WP_Forms::get_instance();
			\SAIFGS\Integrations\GravityForms\SAIFGS_Listener_Gravity_Forms::get_instance();
			\SAIFGS\Integrations\WooCommerce\SAIFGS_Listener_Woo_Commerce::get_instance();
		}

		/**
		 * Init GoogleSheet Settings Intsance.
		 *
		 * @since 1.0.0
		 *
		 * @see General Settings::instance()
		 * @return void.
		 */
		public function saifgs_init_settings() {
			\SAIFGS\Settings\SAIFGS_General_Settings::get_instance();
		}

		/**
		 * Init GoogleSheet Dashboard Pages Intsance.
		 *
		 * @since 1.0.0
		 *
		 * @see Dashboard::instance()
		 * @return void.
		 */
		public function saifgs_init_pages() {
			\SAIFGS\Pages\SAIFGS_Dashboard::get_instance();
		}

		/**
		 * Init API Intsance.
		 *
		 * @since 1.0.0
		 *
		 * @see API::instance()
		 * @return void.
		 */
		public function saifgs_init_rest_api() {
			\SAIFGS\RestApi\SAIFGS_Contact_Form_7_Entries_API::get_instance();
			\SAIFGS\RestApi\SAIFGS_Integration_Edit_Form_API::get_instance();
			\SAIFGS\RestApi\SAIFGS_Integration_Form_API::get_instance();
			\SAIFGS\RestApi\SAIFGS_Integration_Google_Sheet_Tab::get_instance();
			\SAIFGS\RestApi\SAIFGS_Integration_Google_Sheets::get_instance();
			\SAIFGS\RestApi\SAIFGS_Integration_Plugin_Form_Fileds::get_instance();
			\SAIFGS\RestApi\SAIFGS_Integration_Plugin_List_API::get_instance();
			\SAIFGS\RestApi\SAIFGS_Integrations_API::get_instance();
			\SAIFGS\RestApi\SAIFGS_Sheetmaping_List::get_instance();
			\SAIFGS\RestAPI\SAIFGS_Plugins_Form_Data_API::get_instance();
		}

		/**
		 * Init Attachement Folder.
		 *
		 * @since 1.0.0
		 *
		 * @return void.
		 */
		public function saifgs_init_create_attachments_folder() {
			$wp_filesystem = $this->saifgs_filesystem();

			if ( ! $wp_filesystem->exists( SAIFGS_PATH_ATTACHMENTS ) ) {
				$wp_filesystem->mkdir( SAIFGS_PATH_ATTACHMENTS );
			}
		}

		/**
		 * Init Credentials Folder.
		 *
		 * @since 1.0.0
		 *
		 * @return void.
		 */
		public function saifgs_init_create_credentials_folder() {
			$wp_filesystem = $this->saifgs_filesystem();

			if ( ! $wp_filesystem->exists( SAIFGS_PATH_CREDENTIALS ) ) {
				$wp_filesystem->mkdir( SAIFGS_PATH_CREDENTIALS );
			}
		}
	}
}
