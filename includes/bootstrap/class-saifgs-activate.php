<?php
/**
 * Handles the plugin activation process.
 *
 * @package SA Integrations For Google Sheets
 * @since 1.0.0
 */

namespace SAIFGS\BootStrap;

if ( ! class_exists( '\SAIFGS\BootStrap\SAIFGS_Activate' ) ) {
	/**
	 * Manages plugin activation actions.
	 *
	 * This class handles tasks needed to set up the plugin during activation,
	 * such as creating database tables and ensuring necessary configurations.
	 *
	 * @since 1.0.0
	 */
	class SAIFGS_Activate {

		/**
		 * Traits used inside class.
		 *
		 * @since 1.0.0
		 */
		use \SAIFGS\Traits\SAIFGS_Singleton;
		use \SAIFGS\Traits\SAIFGS_Helpers;

		/**
		 * Constructor function.
		 *
		 * Initializes the class. Can be used to set up properties or execute
		 * code when the class is instantiated.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {}

		/**
		 * Manages plugin activation tasks.
		 *
		 * This function is responsible for creating necessary database tables
		 * and setting up any other configurations required when the plugin is activated.
		 *
		 * @since 1.0.0
		 */
		public function saifgs_plugin_activation_manager() {
			\SAIFGS\Database\SAIFGS_Table_Integrations::create();
			\SAIFGS\Database\SAIFGS_Table_Supported_Plugins::create();
			\SAIFGS\Database\SAIFGS_Table_Integrations_Rows::create();
			\SAIFGS\Database\SAIFGS_Table_Contact_Form_7_Entrymeta::create();
			\SAIFGS\Database\SAIFGS_Table_Contact_Form_7_Entries::create();
		}
	}
}
