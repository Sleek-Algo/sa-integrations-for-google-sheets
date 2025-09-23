<?php
/**
 * Handles the Contact Form 7 Entries API.
 *
 * @package SA Integrations For Google Sheets
 * @since 1.0.0
 */

namespace SAIFGS\RestApi;

if ( ! class_exists( '\SAIFGS\RestApi\SAIFGS_Contact_Form_7_Entries_API' ) ) {

	/**
	 * Class Contact Form 7 Entries API.
	 *
	 * Handles the Contact Form 7 Entries API.
	 */
	class SAIFGS_Contact_Form_7_Entries_API {

		// Traits used inside class.
		use \SAIFGS\Traits\SAIFGS_Singleton;
		use \SAIFGS\Traits\SAIFGS_Helpers;
		use \SAIFGS\Traits\SAIFGS_RestAPI;

		/**
		 * The base URL for Contact Forms API routes.
		 *
		 * @var string
		 */
		private static $saifgs_api_route_base_url_contact_forms = '/contact-forms';

		/**
		 * The base URL for Contact Form 7 entries API routes.
		 *
		 * This route includes a placeholder for form ID to fetch entries for a specific form.
		 *
		 * @var string
		 */
		private static $saifgs_api_route_base_url_cf7_entries = '/contact-forms/(?P<form_id>\d+)/entries';

		/**
		 * Constructor for initializing hooks and actions.
		 *
		 * Registers REST API routes and adds a submenu for entries in the WordPress admin dashboard.
		 */
		public function __construct() {
			// Register REST API routes for the plugin.
			add_action( 'rest_api_init', array( $this, 'saifgs_register_rest_routes' ) );

			// Add a submenu under the main menu for managing entries.
			add_action( 'admin_menu', array( $this, 'saifgs_add_entries_submenu' ) );
		}

		/**
		 * Adds a submenu page under the Contact Form 7 menu for managing form entries.
		 *
		 * Registers the submenu page with WordPress admin, specifying the parent menu, page title, menu title, and the callback function for rendering the page content.
		 */
		public function saifgs_add_entries_submenu() {
			add_submenu_page(
				'wpcf7', // Parent slug (Contact Form 7 menu).
				esc_html__( 'Form Entries', 'sa-integrations-for-google-sheets' ), // Page title.
				esc_html__( 'Entries', 'sa-integrations-for-google-sheets' ), // Menu title.
				'manage_options', // Capability required to access the submenu.
				'saifgs', // Menu slug.
				array( $this, 'saifgs_render_entries_page' ) // Callback function to display the page content.
			);
			// Enqueue React script and CSS here.
			add_action( 'admin_enqueue_scripts', array( $this, 'saifgs_enqueue_react_scripts' ) );
		}

		/**
		 * Renders the Contact Form 7 Entries page in the WordPress admin.
		 *
		 * Displays the page title, description, and the container for the React application that will handle the entries.
		 */
		public function saifgs_render_entries_page() {
			?>
				<div class="wrap">
					<h1><?php esc_html_e( 'Contact Form 7 Entries', 'sa-integrations-for-google-sheets' ); ?></h1>
					<p><?php esc_html_e( 'Here you can view and manage entries submitted through Contact Form 7.', 'sa-integrations-for-google-sheets' ); ?></p>
					<!-- Content for managing and displaying entries can be added here. -->
					<div id="saifgs-cf7-app"></div>
				</div>
				<?php
		}

		/**
		 * Enqueues React scripts and styles for the Contact Form 7 Entries page.
		 *
		 * Registers and enqueues the script and style files needed for the Contact Form 7 Entries page,
		 * and sets up localization and translation for the script.
		 *
		 * @param string $hook The current admin page hook.
		 */
		public function saifgs_enqueue_react_scripts( $hook ) {
			if ( 'contact_page_saifgs' === $hook ) {
				$script_asset_path = SAIFGS_PATH . '/assets/backend/free-app/contact-form-7-entries.asset.php';
				$script_url        = SAIFGS_URL_ASSETS_BACKEND . '/free-app/contact-form-7-entries.js';
				$style_url         = SAIFGS_URL_ASSETS_BACKEND . '/free-app/contact-form-7-entries.css';

				if ( ! file_exists( $script_asset_path ) ) {
					return;
				}
				$script_asset = require $script_asset_path;
				wp_register_script(
					'saifgs-contact-form-7-entries-script',
					$script_url,
					$script_asset['dependencies'],
					$script_asset['version'],
					array(
						'strategy'  => 'async',
						'in_footer' => true,
					)
				);

				$saifgs_contact_form_7_entries_localized_objects = array( 'text_domain' => SAIFGS_TEXT_DOMAIN );

				wp_localize_script(
					'saifgs-contact-form-7-entries-script',
					'saifgs_contact_form_7_entries_localized_objects',
					apply_filters( 'saifgs_contact_form_7_entries_localized_objects', $saifgs_contact_form_7_entries_localized_objects )
				);
				wp_enqueue_script( 'saifgs-contact-form-7-entries-script' );

				// Set Translations.
				wp_set_script_translations( 'saifgs-contact-form-7-entries-script', 'sa-integrations-for-google-sheets', SAIFGS_DIR . 'languages' );

				wp_enqueue_style(
					'saifgs-contact-form-7-entries-style',
					$style_url,
					array( 'wp-components' ),
					$script_asset['version']
				);
			}
		}

		/**
		 * Registers REST API routes for Contact Forms and Contact Form 7 entries.
		 *
		 * Defines the routes and associated callback functions for handling GET requests related to
		 * Contact Forms and Contact Form 7 entries.
		 */
		public function saifgs_register_rest_routes() {
			register_rest_route(
				$this->saifgs_get_api_base_url(),
				self::$saifgs_api_route_base_url_contact_forms,
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'saifgs_get_contact_forms' ),
					'permission_callback' => array( $this, 'saifgs_permissions' ),
				)
			);

			register_rest_route(
				$this->saifgs_get_api_base_url(),
				self::$saifgs_api_route_base_url_cf7_entries,
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'saifgs_handle_contact_form_entries' ),
					'permission_callback' => array( $this, 'saifgs_permissions' ),
				)
			);
		}

		/**
		 * Retrieves a list of Contact Form 7 forms.
		 *
		 * Checks if Contact Form 7 is installed and activated. If it is, it fetches all Contact Form 7 forms
		 * and returns a list of form IDs and titles. If Contact Form 7 is not available, it returns an error.
		 *
		 * @return \WP_Error|\WP_REST_Response
		 */
		public function saifgs_get_contact_forms() {
			// Check if Contact Form 7 is active.
			if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
				return new \WP_Error(
					'no_contact_form_7',
					esc_html__( 'Contact Form 7 is not installed or activated.', 'sa-integrations-for-google-sheets' ),
					array( 'status' => 404 )
				);
			}

			// Retrieve all contact forms.
			$forms = \WPCF7_ContactForm::find(
				array(
					'posts_per_page' => -1, // Fetch all forms without pagination.
				)
			);

			// Initialize an array to store form data.
			$form_list = array();

			// Loop through forms and build the list with ID and title.
			foreach ( $forms as $form ) {
				$form_list[] = array(
					'id'    => intval( $form->id() ),
					'title' => sanitize_text_field( $form->title() ),
				);
			}

			return rest_ensure_response( $form_list );
		}

		/**
		 * Handles fetching Contact Form 7 entries with optional filters and pagination.
		 *
		 * This function retrieves submitted entries for a specified Contact Form 7 form,
		 * along with associated metadata and form field details.
		 *
		 * @param \WP_REST_Request $request The REST API request object.
		 * @return \WP_REST_Response The response containing form entries or an error.
		 */
		public function saifgs_handle_contact_form_entries( \WP_REST_Request $request ) {
			global $wpdb;

			// Validate and sanitize input parameters.
			$form_id = absint( $request->get_param( 'form_id' ) );

			// If form_id is not found, handle the error.
			if ( ! $form_id ) {
				return new \WP_REST_Response(
					array(
						'error' => __( 'Form ID is required.', 'sa-integrations-for-google-sheets' ),
					),
					400
				);
			}

			// Get pagination parameters with defaults.
			$limit = absint( $request->get_param( 'limit' ) );
			$limit = ( 0 === $limit ) ? 10 : $limit;

			$current_page = absint( $request->get_param( 'current_page' ) );
			$current_page = ( 0 === $current_page ) ? 1 : $current_page;

			$offset = ( $current_page - 1 ) * $limit;

			// Sanitize filter parameters.
			$filter_start_date = sanitize_text_field( $request->get_param( 'filter_start_date' ) );
			$filter_end_date   = sanitize_text_field( $request->get_param( 'filter_end_date' ) );
			$search_entry      = sanitize_text_field( $request->get_param( 'search_entry' ) );

			// Generate cache keys.
			$entries_cache_key = 'saifgs_cf7_entries_' . $form_id . '_' . $limit . '_' . $offset;
			$total_cache_key   = 'saifgs_cf7_total_' . $form_id;

			// Try to get cached entries.
			$entries = wp_cache_get( $entries_cache_key, 'saifgs_entries' );
			$total   = wp_cache_get( $total_cache_key, 'saifgs_entries' );

			if ( false === $entries ) {
				// Get entries from database with proper prepare.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
				$entries = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT id, form_id, created_at, updated_at 
						FROM {$wpdb->prefix}saifgs_contact_form_7_entries 
						WHERE form_id = %d 
						ORDER BY id DESC 
						LIMIT %d OFFSET %d",
						$form_id,
						$limit,
						$offset
					),
					ARRAY_A
				);

				// Cache entries for 5 minutes.
				wp_cache_set( $entries_cache_key, $entries, 'saifgs_entries', 5 * MINUTE_IN_SECONDS );
			}

			if ( false === $total ) {
				// Get total count with proper prepare.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
				$total = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(id) 
						FROM {$wpdb->prefix}saifgs_contact_form_7_entries 
						WHERE form_id = %d",
						$form_id
					)
				);

				// Cache total for longer (15 minutes) as it changes less frequently.
				wp_cache_set( $total_cache_key, $total, 'saifgs_entries', 15 * MINUTE_IN_SECONDS );
			}

			// Retrieve the form object.
			$contact_form = wpcf7_contact_form( $form_id );
			$form_fields  = array();

			if ( $contact_form ) {
				$form_fields = $contact_form->scan_form_tags();
			}

			// Process entries if we have any.
			if ( ! empty( $entries ) ) {
				$entry_ids = wp_list_pluck( $entries, 'id' );

				$entry_meta = array();
				if ( ! empty( $entry_ids ) ) {
					// Most secure WPCS compliant solution.
					$entry_ids_clean = array_map( 'absint', $entry_ids );
					$placeholders    = implode( ',', $entry_ids_clean );

					$query = "SELECT `meta_id`, `entry_id`, `meta_key`, `meta_value` 
							FROM `{$wpdb->prefix}saifgs_contact_form_7_entrymeta` 
							WHERE `entry_id` IN ($placeholders)";

					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
					$entry_meta = $wpdb->get_results( $wpdb->prepare( $query ), ARRAY_A );
				}

				foreach ( $entries as $index => $entry ) {
					$entry_meta_values               = \SAIFGS\Classes\SAIFGS_Contact_Form_7_Meta_Query::saifgs_get_meta( $entry['id'] );
					$entries[ $index ]['meta']       = $entry_meta_values;
					$entries[ $index ]['fieldsmeta'] = $form_fields;
				}
			}

			return new \WP_REST_Response(
				array(
					'data'  => $entries,
					'total' => (int) $total,
				),
				200
			);
		}
	}
}
