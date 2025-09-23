<?php
/**
 * Handles the SAIFGS_RestAPI Trait
 *
 * @package SA Integrations For Google Sheets
 * @since 1.0.0
 */

namespace SAIFGS\Traits;

if ( ! trait_exists( '\SAIFGS\Traits\SAIFGS_RestAPI' ) ) {

	/**
	 * SAIFGS_RestAPI Trait
	 *
	 * This trait provides base functionality for REST API integration. It includes methods
	 * for defining the API version, namespace, and base URL, as well as managing permissions.
	 *
	 * Properties:
	 * - $saifgs_api_version: The version of the API (default: 'v1').
	 * - $saifgs_api_namespace: The namespace for the API (default: 'saifgs').
	 *
	 * Methods:
	 * - saifgs_get_api_base_url(): Returns the base URL for the API.
	 * - permissions(): Checks if the current user has the required permissions.
	 */
	trait SAIFGS_RestAPI {

		/**
		 * $saifgs_api_version The version of the API (default: 'v1').
		 *
		 * @var string
		 */
		private $saifgs_api_version = 'v1';

		/**
		 * $saifgs_api_namespace The namespace for the API (default: 'saifgs').
		 *
		 * @var string
		 */
		private $saifgs_api_namespace = 'saifgs';

		/**
		 * Retrieves the base URL for the API, combining namespace and version.
		 *
		 * @return string The full API base URL.
		 */
		public function saifgs_get_api_base_url() {
			$saifgs_api_base_url = $this->saifgs_api_namespace . '/' . $this->saifgs_api_version;

			return $saifgs_api_base_url;
		}

		/**
		 * Checks if the current user has permissions to access the API.
		 *
		 * @return bool True if the user has permissions, otherwise false.
		 */
		public function saifgs_permissions() {
			// For REST API, check X-WP-Nonce header.
			$nonce = '';

			// Check for X-WP-Nonce header.
			if ( isset( $_SERVER['HTTP_X_WP_NONCE'] ) ) {
				$nonce = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) );
			} elseif ( isset( $_REQUEST['_wpnonce'] ) ) { // Fallback to POST/GET parameter.
				$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) );
			}

			if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				return false;
			}

			return true;
		}
	}
}
