<?php

/**
 * Plugin Name: SA Integrations For Google Sheets
 * Plugin URI: https://www.sleekalgo.com/sa-integrations-for-google-sheets/
 * Description: This plugin connects your WordPress website with Google Sheets, enabling automatic synchronization of form submissions and WooCommerce order data.
 * Version: 1.0.0
 * Requires at least: 5.1
 * Requires PHP: 7.0
 * Author: SleekAlgo
 * Author URI: https://www.sleekalgo.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sa-integrations-for-google-sheets
 * Domain Path: /languages 
 */

/* Exit if accessed directly. */
defined( 'ABSPATH' ) || exit;


if ( !function_exists( 'get_plugin_data' ) ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
$saifgs_plugin_data = get_plugin_data( __FILE__ );
$saifgs_wp_upload_dir = wp_get_upload_dir();
/* Define constants. */
!defined( 'SAIFGS_VERSION' )                        && define( 'SAIFGS_VERSION', $saifgs_plugin_data['Version'] );
!defined( 'SAIFGS_NAME' )                           && define( 'SAIFGS_NAME', $saifgs_plugin_data['Name'] );
!defined( 'SAIFGS_BASE' )                           && define( 'SAIFGS_BASE', basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ) );
!defined( 'SAIFGS_TEXT_DOMAIN' )                    && define( 'SAIFGS_TEXT_DOMAIN', $saifgs_plugin_data['TextDomain'] );
!defined( 'SAIFGS_TEXT_DOMAIN_PATH' )               && define( 'SAIFGS_TEXT_DOMAIN_PATH', dirname( SAIFGS_BASE ) . $saifgs_plugin_data['DomainPath'] );
!defined( 'SAIFGS_ASSETS_SUFFIX' )                  && define( 'SAIFGS_ASSETS_SUFFIX', '' );
!defined( 'SAIFGS_FILE' )                           && define( 'SAIFGS_FILE', __FILE__ );
!defined( 'SAIFGS_DIR' )                            && define( 'SAIFGS_DIR', plugin_dir_path( SAIFGS_FILE ) );
!defined( 'SAIFGS_URL' )                            && define( 'SAIFGS_URL', plugins_url( '', SAIFGS_FILE ) );
!defined( 'SAIFGS_URL_ASSETS_FRONTEND' )            && define( 'SAIFGS_URL_ASSETS_FRONTEND', SAIFGS_URL . '/assets/frontend' );
!defined( 'SAIFGS_URL_ASSETS_FRONTEND_CSS' )        && define( 'SAIFGS_URL_ASSETS_FRONTEND_CSS', SAIFGS_URL_ASSETS_FRONTEND . '/css' );
!defined( 'SAIFGS_URL_ASSETS_FRONTEND_JS' )         && define( 'SAIFGS_URL_ASSETS_FRONTEND_JS', SAIFGS_URL_ASSETS_FRONTEND . '/js' );
!defined( 'SAIFGS_URL_ASSETS_FRONTEND_IMAGES' )     && define( 'SAIFGS_URL_ASSETS_FRONTEND_IMAGES', SAIFGS_URL_ASSETS_FRONTEND . '/images' );
!defined( 'SAIFGS_URL_ASSETS_BACKEND' )             && define( 'SAIFGS_URL_ASSETS_BACKEND', SAIFGS_URL . '/assets/backend' );
!defined( 'SAIFGS_URL_ASSETS_BACKEND_CSS' )         && define( 'SAIFGS_URL_ASSETS_BACKEND_CSS', SAIFGS_URL_ASSETS_BACKEND . '/css' );
!defined( 'SAIFGS_URL_ASSETS_BACKEND_JS' )          && define( 'SAIFGS_URL_ASSETS_BACKEND_JS', SAIFGS_URL_ASSETS_BACKEND . '/js' );
!defined( 'SAIFGS_URL_ASSETS_IMAGES' )              && define( 'SAIFGS_URL_ASSETS_IMAGES', SAIFGS_URL . '/assets/images' );
!defined( 'SAIFGS_PATH' )                           && define( 'SAIFGS_PATH', dirname( SAIFGS_FILE ) );
!defined( 'SAIFGS_PATH_TEMPLATES' )                 && define( 'SAIFGS_PATH_TEMPLATES', SAIFGS_PATH . '/templates' );
/**
 * File uploading directory
 */
!defined( 'SAIFGS_WP_UPLOAD_DIR' )                  && define( 'SAIFGS_WP_UPLOAD_DIR', $saifgs_wp_upload_dir['basedir'] );
!defined( 'SAIFGS_PATH_ATTACHMENTS' )               && define( 'SAIFGS_PATH_ATTACHMENTS', SAIFGS_WP_UPLOAD_DIR . '/saifgs-attachments' );
!defined( 'SAIFGS_URL_ATTACHMENTS' )                && define( 'SAIFGS_URL_ATTACHMENTS', $saifgs_wp_upload_dir['baseurl'] . '/saifgs-attachments' );
!defined( 'SAIFGS_PATH_CREDENTIALS' )               && define( 'SAIFGS_PATH_CREDENTIALS', SAIFGS_WP_UPLOAD_DIR . '/saifgs-credentials' );
!defined( 'SAIFGS_URL_CREDENTIALS' )                && define( 'SAIFGS_URL_CREDENTIALS', $saifgs_wp_upload_dir['baseurl'] . '/saifgs-credentials' );

/**
 * PSR-4 Composer Autoloader
 */
if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
    require SAIFGS_DIR . '/vendor/autoload.php';
}


/**
 * Activation Hook
 */
register_activation_hook( __FILE__, [\SAIFGS\BootStrap\SAIFGS_Activate::get_instance(), 'saifgs_plugin_activation_manager'] );


function saifgs_init_plugin() {
    \SAIFGS\Bootstrap\SAIFGS_App::get_instance();
    do_action( 'saifgs_plugin_loaded' );
}
add_action( 'plugins_loaded', 'saifgs_init_plugin', 30 );
