<?php
/**
 * Handles the Dashboard Page.
 *
 * @package SA Integrations For Google Sheets
 * @since 1.0.0
 */

namespace SAIFGS\Pages;

if ( ! class_exists( '\\SAIFGS\\Pages\\SAIFGS_Dashboard' ) ) {
	/**
	 * Class Dashboard
	 *
	 * Handles the wp-admin dashboard Page.
	 */
	class SAIFGS_Dashboard {
		/**
		 * Traits used inside class
		 */
		use \SAIFGS\Traits\SAIFGS_Singleton;
		use \SAIFGS\Traits\SAIFGS_Helpers;
		use \SAIFGS\Traits\SAIFGS_RestAPI;
		/**
		 * Constructor for initializing the plugin's admin functionalities.
		 *
		 * This constructor sets up actions to add a dashboard menu item and enqueue scripts
		 * for the plugin's admin pages. It hooks into WordPress actions for menu creation
		 * and script loading.
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'saifgs_dashboard_menu' ), 10 );
			add_action(
				'admin_enqueue_scripts',
				array( $this, 'saifgs_dashboard_scripts' ),
				10,
				1
			);
		}

		/**
		 * Adds a custom menu item to the WordPress admin dashboard.
		 *
		 * This function creates a new top-level menu page titled 'G-Sheets Integrations'
		 * in the WordPress admin panel. It provides access to the plugin's main dashboard
		 * page, where users can manage Google Sheets integrations.
		 */
		public function saifgs_dashboard_menu() {
			if ( current_user_can( 'manage_options' ) ) {
				add_menu_page(
					esc_html__( 'G-Sheets Integrations', 'sa-integrations-for-google-sheets' ),
					esc_html__( 'G-Sheets Integrations', 'sa-integrations-for-google-sheets' ),
					'manage_options',
					'saifgs-dashboard',
					array( $this, 'saifgs_dashboard_page' ),
					'dashicons-media-spreadsheet'
				);
			}
		}

		/**
		 * Dashboard page.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function saifgs_dashboard_page() {
			?>
			<div id="saifgs-app"></div>
			<?php
		}

		/**
		 * Load admin script.
		 *
		 * @since 1.0.0
		 *
		 * @param  string $hook temp.
		 * @return void
		 * @throws \Error If there is an error loading the script.
		 */
		public function saifgs_dashboard_scripts( $hook ) {
			if ( 'toplevel_page_saifgs-dashboard' === $hook ) {
				$script_asset_path = SAIFGS_PATH . '/assets/backend/free-app/index.asset.php';
				$script_url        = SAIFGS_URL_ASSETS_BACKEND . '/free-app/index.js';
				$style_url         = SAIFGS_URL_ASSETS_BACKEND . '/free-app/index.css';
				if ( ! file_exists( $script_asset_path ) ) {
					throw new \Error( 'You need to run `npm start` or `npm run build` for the "wholesomecode/wholesome-plugin" block first.' );
				}
				$script_asset = ( require $script_asset_path );
				wp_register_script(
					'saifgs-app-script',
					$script_url,
					$script_asset['dependencies'],
					$script_asset['version'],
					array(
						'strategy'  => 'async',
						'in_footer' => true,
					)
				);
				$premium_url                             = 'https://www.sleekalgo.com/sa-integrations-for-google-sheets/';
				$saifgs_customizations_localized_objects = array(
					'language'             => get_user_locale(),
					'language_dir'         => ( is_rtl() ? 'rtl' : 'ltr' ),
					'text_domain'          => SAIFGS_TEXT_DOMAIN,
					'purchase_premium_url' => $premium_url,
					'nonce'                => wp_create_nonce( 'wp_rest' ),
				);
				wp_localize_script( 'saifgs-app-script', 'saifgs_customizations_localized_objects', apply_filters( 'saifgs_customizations_localized_objects', $saifgs_customizations_localized_objects ) );
				wp_enqueue_script( 'saifgs-app-script' );

				// Set Translations.
				wp_set_script_translations(
					'saifgs-app-script',
					'sa-integrations-for-google-sheets',
					SAIFGS_DIR . 'languages'
				);
				wp_enqueue_style(
					'saifgs-app-style',
					$style_url,
					array( 'wp-components' ),
					$script_asset['version']
				);
			}
		}
	}
}
