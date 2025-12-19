<?php
/**
 * Handles the SAIFGS_Helpers Trait
 *
 * @package SA Integrations For Google Sheets
 * @since 1.0.0
 */

namespace SAIFGS\Traits;

if ( ! trait_exists( '\SAIFGS\Traits\SAIFGS_Helpers' ) ) {

	/**
	 * SAIFGS_Helpers Trait
	 *
	 * Provides various utility methods for working with the WordPress filesystem,
	 * logging, Google Sheets integration, and database interactions.
	 */
	trait SAIFGS_Helpers {

		/**
		 * Retrieves the WordPress filesystem object.
		 *
		 * This method initializes the WordPress filesystem if it is not already
		 * set up. It includes the necessary file handling functions from WordPress
		 * and ensures the global `$wp_filesystem` object is available for use.
		 *
		 * @return \WP_Filesystem|false The WordPress filesystem object, or false on failure.
		 */
		public function saifgs_filesystem() {
			global $wp_filesystem;

			require_once ABSPATH . 'wp-admin/includes/file.php';

			if ( ! $wp_filesystem ) {
				WP_Filesystem();
			}

			return $wp_filesystem;
		}

		/**
		 * Fetches integration data from the database based on plugin and form ID.
		 *
		 * This method queries the `saifgs_integrations` table to retrieve integration
		 * details associated with the provided plugin and form ID. It returns an array
		 * of integration records, including Google Sheets-related information. If no
		 * records are found, it returns `false`.
		 *
		 * @param string $plugin The identifier for the plugin.
		 * @param int    $form_id The ID of the form for which integration data is retrieved.
		 *
		 * @return array|false An array of integration data if found, otherwise `false`.
		 */
		public function saifgs_get_integration_data( $plugin, $form_id ) {
			global $wpdb;

			// Generate cache key.
			$cache_key   = 'saifgs_integration_' . $plugin . '_' . $form_id;
			$cache_group = 'saifgs_integrations';

			// Try to get cached data first.
			$integration_data = wp_cache_get( $cache_key, $cache_group );

			if ( false !== $integration_data ) {
				return $integration_data;
			}

			// Query database if not found in cache.
			$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT `id`, `google_work_sheet_id`, `google_sheet_tab_id`, 
						`google_sheet_column_range`, `google_sheet_column_map` 
					FROM `{$wpdb->prefix}saifgs_integrations` 
					WHERE `source_id` = %d AND `Plugin_id` = %s",
					$form_id,
					$plugin
				)
			);

			$integration_data = false;

			if ( $results ) {
				$integration_data = array();
				foreach ( $results as $result ) {
					$integration_data[] = array(
						'id'                        => $result->id,
						'google_work_sheet_id'      => $result->google_work_sheet_id,
						'google_sheet_tab_id'       => $result->google_sheet_tab_id,
						'google_sheet_column_range' => $result->google_sheet_column_range,
						'google_sheet_column_map'   => maybe_unserialize( $result->google_sheet_column_map ),
					);

					// Cache the results for 1 hour (adjust as needed).
					wp_cache_set( $cache_key, $integration_data, $cache_group, HOUR_IN_SECONDS );
				}
				return $integration_data;
			} else {
				return false;
			}
		}

		/**
		 * Converts a column index to its Google Sheets column location (e.g., A, B, AA).
		 *
		 * @param int $index Zero-based column index.
		 * @return string Column location (e.g., A, B, Z, AA).
		 */
		private function saifgs_get_column_location( $index ) {
			$column = '';
			while ( $index >= 0 ) {
				$column = chr( $index % 26 + 65 ) . $column;
				$index  = intval( $index / 26 ) - 1;
			}

			// Append row number for A1 notation.
			return $column . '1';
		}

		/**
		 * Determines the correct active access token to use.
		 * Priority: 1. Valid User OAuth Token, 2. Service Account Token.
		 *
		 * @return string|null The access token, or null if none found/valid.
		 */
		// private function saifgs_get_active_access_token() {
		// 	// 1. Check for a valid OAuth token from the auto-connect system
		// 	$oauth_token = get_option( 'saifgs_auto_connect_token' );
		// 	$oauth_expired = get_option( 'saifgs_auto_connect_auth_expired', 'false' );

		// 	if ( ! empty( $oauth_token ) && $oauth_expired === 'false' ) {
		// 		// Optional: Perform a lightweight validation (e.g., check expiry time)
		// 		$expiry_time = get_option( 'saifgs_auto_connect_token_expiry', 0 );
		// 		if ( $expiry_time > ( time() + 300 ) ) { // 5-minute buffer
		// 			error_log( 'SAIFGS: Using valid OAuth access token for integration list.' );
		// 			return $oauth_token;
		// 		} else {
		// 			error_log( 'SAIFGS: OAuth token appears expired. Marking as expired.' );
		// 			update_option( 'saifgs_auto_connect_auth_expired', 'true' );
		// 		}
		// 	}

		// 	// 2. Fall back to the Service Account token
		// 	error_log( 'SAIFGS: Falling back to Service Account token for integration list.' );
		// 	if ( class_exists( '\SAIFGS\Classes\SAIFGS_Google_Apis_Authenticator' ) ) {
		// 		$client = \SAIFGS\Classes\SAIFGS_Google_Apis_Authenticator::get_instance();
		// 		return $client->saifgs_get_access_token(); // This generates/fetches the service account token
		// 	}

		// 	error_log( 'SAIFGS: No access token available for integration list.' );
		// 	return null;
		// }

		private function saifgs_get_active_access_token() {
            error_log('SAIFGS: Getting active access token with refresh capability');
            
            // Priority 1: Client Credentials OAuth token
            $client_credentials_token = get_option( 'saifgs_client_credentials_access_token' );
            $client_credentials_expiry = get_option( 'saifgs_client_credentials_token_expiry' );
            $client_refresh_token = $this->saifgs_decrypt_data( get_option( 'saifgs_client_credentials_refresh_token' ) );
            
            error_log('SAIFGS: Client credentials token exists: ' . (!empty($client_credentials_token) ? 'YES' : 'NO'));
            error_log('SAIFGS: Client credentials expiry: ' . ($client_credentials_expiry ? date('Y-m-d H:i:s', $client_credentials_expiry) : 'NOT SET'));
            error_log('SAIFGS: Current time: ' . date('Y-m-d H:i:s', time()));
            
            if ( ! empty( $client_credentials_token ) ) {
                // Check if token is expired or about to expire (5 minute buffer)
                if ( $client_credentials_expiry > ( time() + 300 ) ) {
                    error_log( 'SAIFGS: Using valid client credentials OAuth token' );
                    return $client_credentials_token;
                } else {
                    error_log( 'SAIFGS: Client credentials token expired or about to expire' );
                    
                    // Try to refresh the token
                    if ( ! empty( $client_refresh_token ) ) {
                        error_log( 'SAIFGS: Attempting to refresh client credentials token' );
                        $refreshed_token = $this->saifgs_refresh_client_token( $client_refresh_token );
                        if ( $refreshed_token ) {
                            error_log( 'SAIFGS: Successfully refreshed client credentials token' );
                            return $refreshed_token;
                        }
                    } else {
                        error_log( 'SAIFGS: No refresh token available for client credentials' );
                    }
                }
            }
            
            // Priority 2: Auto Connect OAuth token
            $auto_connect_token = get_option( 'saifgs_auto_connect_token' );
            $auto_connect_expired = get_option( 'saifgs_auto_connect_auth_expired', 'false' );
            
            error_log('SAIFGS: Auto connect token exists: ' . (!empty($auto_connect_token) ? 'YES' : 'NO'));
            error_log('SAIFGS: Auto connect expired status: ' . $auto_connect_expired);
            
            if ( ! empty( $auto_connect_token ) && $auto_connect_expired === 'false' ) {
                $expiry_time = get_option( 'saifgs_auto_connect_token_expiry', 0 );
                error_log('SAIFGS: Auto connect token expiry: ' . ($expiry_time ? date('Y-m-d H:i:s', $expiry_time) : 'NOT SET'));
                
                if ( $expiry_time > ( time() + 300 ) ) {
                    error_log( 'SAIFGS: Using auto connect OAuth token' );
                    return $auto_connect_token;
                } else {
                    error_log('SAIFGS: Auto connect token expired');
                    
                    // Try to refresh auto connect token via bridge
                    $auto_refresh_token = get_option( 'saifgs_auto_connect_refresh_token' );
                    if ( ! empty( $auto_refresh_token ) ) {
                        error_log( 'SAIFGS: Attempting to refresh auto connect token via bridge' );
                        // You need to implement bridge refresh similar to client credentials
                    }
                }
            }
            
            error_log('SAIFGS: No valid OAuth token available');
            
            // Check if there's a service account token (JSON file upload)
            if ( class_exists( '\SAIFGS\Classes\SAIFGS_Google_Apis_Authenticator' ) ) {
                $authenticator = \SAIFGS\Classes\SAIFGS_Google_Apis_Authenticator::get_instance();
                $service_token = $authenticator->saifgs_get_access_token();
                if ( ! empty( $service_token ) ) {
                    error_log( 'SAIFGS: Falling back to Service Account token' );
                    return $service_token;
                }
            }
            
            error_log('SAIFGS: No access token available from any source');
            return null;
        }

		/**
         * Refresh client credentials token using refresh token
         */
        private function saifgs_refresh_client_token( $refresh_token ) {
            error_log( 'SAIFGS: Refreshing client credentials token' );
            
            $client_id = get_option( 'saifgs_client_credentials_client_id' );
            $encrypted_secret = get_option( 'saifgs_client_credentials_client_secret' );
            
            if ( empty( $client_id ) || empty( $encrypted_secret ) ) {
                error_log( 'SAIFGS: Missing client credentials for refresh' );
                return false;
            }
            
            $client_secret = $this->saifgs_decrypt_data( $encrypted_secret );
            
            $response = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
                'headers' => array(
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ),
                'body' => array(
                    'refresh_token' => $refresh_token,
                    'client_id' => $client_id,
                    'client_secret' => $client_secret,
                    'grant_type' => 'refresh_token'
                ),
                'timeout' => 30
            ) );

            if ( is_wp_error( $response ) ) {
                error_log( 'SAIFGS: Token refresh WP Error - ' . $response->get_error_message() );
                return false;
            }

            $response_code = wp_remote_retrieve_response_code( $response );
            $body = wp_remote_retrieve_body( $response );
            
            error_log( 'SAIFGS: Token refresh response code: ' . $response_code );
            
            if ( $response_code !== 200 ) {
                error_log( 'SAIFGS: Token refresh failed with response: ' . $body );
                return false;
            }

            $body_data = json_decode( $body, true );

            if ( isset( $body_data['access_token'] ) ) {
                // Save the new token data
                update_option( 'saifgs_client_credentials_access_token', $body_data['access_token'] );
                
                if ( isset( $body_data['expires_in'] ) ) {
                    $expiry = time() + $body_data['expires_in'];
                    update_option( 'saifgs_client_credentials_token_expiry', $expiry );
                    error_log( 'SAIFGS: New token expiry set to: ' . date( 'Y-m-d H:i:s', $expiry ) );
                }
                
                // If a new refresh token is provided, save it
                if ( isset( $body_data['refresh_token'] ) ) {
                    update_option( 'saifgs_client_credentials_refresh_token', 
                        $this->saifgs_encrypt_data( $body_data['refresh_token'] ) );
                    error_log( 'SAIFGS: New refresh token saved' );
                }
                
                return $body_data['access_token'];
            }
            
            return false;
        }

        /**
         * Encrypt data
         */
        private function saifgs_encrypt_data( $data ) {
            if ( empty( $data ) ) {
                return $data;
            }

            $key = defined( 'AUTH_KEY' ) ? AUTH_KEY : 'saifgs_default_key';
            $key = substr( hash( 'sha256', $key ), 0, 32 );
            
            $iv = openssl_random_pseudo_bytes( openssl_cipher_iv_length( 'AES-256-CBC' ) );
            $encrypted = openssl_encrypt( $data, 'AES-256-CBC', $key, 0, $iv );
            
            return base64_encode( $iv . $encrypted );
        }

        /**
         * Decrypt data
         */
        private function saifgs_decrypt_data( $data ) {
            if ( empty( $data ) ) {
                return $data;
            }

            $data = base64_decode( $data );
            $iv_length = openssl_cipher_iv_length( 'AES-256-CBC' );
            $iv = substr( $data, 0, $iv_length );
            $encrypted = substr( $data, $iv_length );

            $key = defined( 'AUTH_KEY' ) ? AUTH_KEY : 'saifgs_default_key';
            $key = substr( hash( 'sha256', $key ), 0, 32 );
            
            return openssl_decrypt( $encrypted, 'AES-256-CBC', $key, 0, $iv );
        }

		/**
		 * Utility function to check if the currently used token is from OAuth.
		 * (Simplistic check based on the source option).
		 *
		 * @return bool
		 */
		private function saifgs_is_oauth_token() {
			$token = get_option( 'saifgs_auto_connect_token' );
			return ! empty( $token );
		}
	}
}
