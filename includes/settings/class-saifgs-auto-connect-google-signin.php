<?php
namespace SAIFGS\Settings;

if ( ! class_exists( '\SAIFGS\Settings\SAIFGS_Auto_Connect_Google_Signin' ) ) {

	class SAIFGS_Auto_Connect_Google_Signin {

		// Traits used inside class.
		use \SAIFGS\Traits\SAIFGS_Singleton;
		use \SAIFGS\Traits\SAIFGS_Helpers;
		use \SAIFGS\Traits\SAIFGS_RestAPI;

		private static $saifgs_api_route_base_url_get_auto_connect_status = '/get-auto-connect-status';
		private static $saifgs_api_route_base_url_initiate_auto_connect = '/initiate-auto-connect';
		private static $saifgs_api_route_base_url_deactivate_auto_connect = '/deactivate-auto-connect';
		private static $saifgs_api_route_base_url_save_auth_code = '/save-auth-code';

		// Option names
		private static $option_token = 'saifgs_auto_connect_token';
		private static $option_access_code = 'saifgs_auto_connect_access_code';
		private static $option_verify = 'saifgs_auto_connect_verify';
		private static $option_auth_expired = 'saifgs_auto_connect_auth_expired';
		private static $option_refresh_token = 'saifgs_auto_connect_refresh_token';

		// Bridge URL - Change this to your bridge URL
		private $bridge_url = 'https://dev-testing.wheelsqueue.com/sa-integrations-for-google-sheets.php';

		public function __construct() {
			// error_log('SAIFGS: Plugin initialized with Bridge System');
			// error_log('SAIFGS: Bridge URL: ' . $this->bridge_url);
			
			add_action( 'rest_api_init', array( $this, 'saifgs_register_rest_routes' ) );
			add_action( 'admin_init', array( $this, 'saifgs_handle_auth_callback' ) );
		}

		public function saifgs_register_rest_routes() {
			// error_log('SAIFGS: Registering REST routes for Bridge System');
			
			register_rest_route(
				$this->saifgs_get_api_base_url(),
				self::$saifgs_api_route_base_url_get_auto_connect_status,
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'saifgs_get_auto_connect_status' ),
					'permission_callback' => array( $this, 'saifgs_permissions' ),
				)
			);

			register_rest_route(
				$this->saifgs_get_api_base_url(),
				self::$saifgs_api_route_base_url_initiate_auto_connect,
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'saifgs_initiate_auto_connect' ),
					'permission_callback' => array( $this, 'saifgs_permissions' ),
				)
			);

			register_rest_route(
				$this->saifgs_get_api_base_url(),
				self::$saifgs_api_route_base_url_deactivate_auto_connect,
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'saifgs_deactivate_auto_connect' ),
					'permission_callback' => array( $this, 'saifgs_permissions' ),
				)
			);

			register_rest_route(
				$this->saifgs_get_api_base_url(),
				self::$saifgs_api_route_base_url_save_auth_code,
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'saifgs_save_auth_code' ),
					'permission_callback' => array( $this, 'saifgs_permissions' ),
				)
			);
			
			// error_log('SAIFGS: All REST routes registered successfully for Bridge System');
		}
		
		/**
		 * Handle OAuth callback from Bridge
		 */
		public function saifgs_handle_auth_callback() {
			// error_log('SAIFGS: ===== Bridge OAuth Callback Started =====');
			// error_log('SAIFGS: GET Parameters: ' . print_r($_GET, true));
			
			// Check if this is Bridge callback with code
			if ( isset( $_GET['page'] ) && $_GET['page'] === 'saifgs-dashboard' ) {
				
				if ( isset( $_GET['code'] ) ) {
					// error_log('SAIFGS: ✅ Bridge callback detected with authorization code');
					
					// Get code from Bridge
					$code = sanitize_text_field( $_GET['code'] );
					// error_log('SAIFGS: Authorization code received from Bridge: ' . substr($code, 0, 10) . '...');
					
					// Process the token from Bridge
					$this->saifgs_process_bridge_token( $code );
					
				} elseif ( isset( $_GET['access_token'] ) ) {
					// error_log('SAIFGS: ✅ Bridge callback detected with direct access token');
					
					// Direct token from Bridge (alternative method)
					$access_token = sanitize_text_field( $_GET['access_token'] );
					$refresh_token = isset( $_GET['refresh_token'] ) ? sanitize_text_field( $_GET['refresh_token'] ) : '';
					
					$this->saifgs_save_token_data( $access_token, $refresh_token, 'bridge_direct' );
					
				} elseif ( isset( $_GET['error'] ) ) {
					// error_log('SAIFGS: ❌ Bridge callback detected with error: ' . $_GET['error']);
					wp_redirect( admin_url( 'admin.php?page=saifgs-dashboard&tab=integration&saifgs_auth=error&message=' . urlencode( $_GET['error'] ) ) );
					exit;
				}
			}
			
			// error_log('SAIFGS: ===== Bridge OAuth Callback Ended =====');
		}

		/**
		 * Process token received from Bridge
		 */
		private function saifgs_process_bridge_token( $code ) {
			try {
				// error_log('SAIFGS: 🔄 Processing token from Bridge');
				
				// The bridge returns an authorization code that we need to exchange
				// Bridge should handle the token exchange and return the final token
				// For now, we'll treat the code as the access token (if bridge configured that way)
				
				// If bridge returns actual access token as 'code'
				if ( strlen( $code ) > 100 ) { // Access tokens are usually longer
					// error_log('SAIFGS: Bridge returned what appears to be an access token');
					$this->saifgs_save_token_data( $code, '', 'bridge_access_token' );
				} else {
					// error_log('SAIFGS: Bridge returned authorization code, need to exchange');
					// In a proper implementation, you would exchange this code with your bridge
					// For now, we'll save it and handle exchange separately
					$this->saifgs_handle_bridge_code_exchange( $code );
				}
				
			} catch ( \Exception $e ) {
				// error_log('SAIFGS: ❌ Error processing bridge token: ' . $e->getMessage());
				wp_redirect( admin_url( 'admin.php?page=saifgs-dashboard&tab=integration&saifgs_auth=error&message=' . urlencode( $e->getMessage() ) ) );
				exit;
			}
		}

		/**
		 * Handle code exchange with Bridge
		 */
		private function saifgs_handle_bridge_code_exchange( $code ) {
			// error_log('SAIFGS: 🔄 Exchanging code with Bridge');
			
			// Make a request to bridge to exchange code for token
			$exchange_url = $this->bridge_url . '?action=exchange_token&code=' . urlencode( $code );
			
			// error_log('SAIFGS: Sending code exchange request to: ' . $exchange_url );
			
			$response = wp_remote_get( $exchange_url, array(
				'timeout' => 30,
			) );

			if ( is_wp_error( $response ) ) {
				// error_log('SAIFGS: ❌ Code exchange with Bridge failed: ' . $response->get_error_message());
				throw new \Exception( __( 'Failed to exchange code with bridge server', 'sa-integrations-for-google-sheets' ) );
			}

			$response_code = wp_remote_retrieve_response_code( $response );
			$body = wp_remote_retrieve_body( $response );

			// error_log('SAIFGS: Bridge exchange response code: ' . $response_code);
			// error_log('SAIFGS: Bridge exchange response body: ' . $body);

			$token_data = json_decode( $body, true );

			if ( $response_code === 200 && isset( $token_data['access_token'] ) ) {
				$access_token = $token_data['access_token'];
				$refresh_token = isset( $token_data['refresh_token'] ) ? $token_data['refresh_token'] : '';
				
				$this->saifgs_save_token_data( $access_token, $refresh_token, 'bridge_exchange' );
			} else {
				$error_message = isset( $token_data['error'] ) ? $token_data['error'] : __( 'Unknown error from bridge', 'sa-integrations-for-google-sheets' );
				// error_log('SAIFGS: ❌ Bridge exchange failed: ' . $error_message);
				throw new \Exception( __( 'Bridge exchange failed: ', 'sa-integrations-for-google-sheets' ) . $error_message );
			}
		}

		/**
		 * Save token data
		 */
		private function saifgs_save_token_data( $access_token, $refresh_token = '', $source = 'direct' ) {
			// error_log('SAIFGS: ✅ Saving token data from source: ' . $source);
			
			// Save token data
			update_option( self::$option_token, $access_token );
			update_option( self::$option_verify, 'valid' );
			update_option( self::$option_auth_expired, 'false' );

			// Save refresh token if available
			if ( ! empty( $refresh_token ) ) {
				// error_log('SAIFGS: ✅ Refresh token received and saved');
				update_option( self::$option_refresh_token, $refresh_token );
			} else {
				// error_log('SAIFGS: ⚠️ No refresh token received');
			}

			// Set default expiry (1 hour)
			$expiry_time = time() + 3600;
			update_option( 'saifgs_auto_connect_token_expiry', $expiry_time );
			// error_log('SAIFGS: ✅ Token expiry set to: ' . date('Y-m-d H:i:s', $expiry_time));

			// Get and save email
			$email = $this->saifgs_get_connected_email();
			if ( $email ) {
				update_option( 'saifgs_connected_email', $email );
				// error_log('SAIFGS: ✅ Connected email saved: ' . $email);
			}

			// error_log('SAIFGS: ✅ All token data saved successfully');
			// error_log('SAIFGS: 🔄 Redirecting to success page');
			
			// Redirect to success
			wp_redirect( admin_url( 'admin.php?page=saifgs-dashboard&tab=integration&saifgs_auth=success' ) );
			exit;
		}

		/**
		 * Get auto connect status
		 */
		public function saifgs_get_auto_connect_status() {
			// error_log('SAIFGS: 🔄 Getting auto connect status (Bridge System)');
			try {
				$token = get_option( self::$option_token );
				$status = 'not_connected';
				$email = '';

				// error_log('SAIFGS: Current token status - exists: ' . (!empty($token) ? 'YES' : 'NO'));
				
				if ( ! empty( $token ) ) {
					// Check if token is expired
					$is_expired = $this->saifgs_is_token_expired();
					// error_log('SAIFGS: Token expired check: ' . ($is_expired ? 'YES' : 'NO'));
					
					if ( $is_expired ) {
						// error_log('SAIFGS: 🔄 Token expired, attempting refresh via Bridge');
						// Try to refresh token via Bridge
						if ( $this->saifgs_refresh_token_via_bridge() ) {
							// error_log('SAIFGS: ✅ Token refresh via Bridge successful');
							$status = 'connected';
							$email = $this->saifgs_get_connected_email();
						} else {
							// error_log('SAIFGS: ❌ Token refresh via Bridge failed');
							$status = 'expired';
							update_option( self::$option_auth_expired, 'true' );
						}
					} else {
						// error_log('SAIFGS: ✅ Token valid, getting email');
						$status = 'connected';
						$email = get_option( 'saifgs_connected_email', $this->saifgs_get_connected_email() );
					}
				}

				// error_log('SAIFGS: ✅ Final status - ' . $status . ', email: ' . ($email ?: 'NOT FOUND'));
				return rest_ensure_response( array(
					'success' => true,
					'data' => array(
						'status' => $status,
						'email'  => $email,
					),
				) );

			} catch ( \Exception $e ) {
				// error_log('SAIFGS: ❌ Error getting auto connect status: ' . $e->getMessage());
				return rest_ensure_response( array(
					'success' => false,
					'message' => $e->getMessage(),
				) );
			}
		}

		/**
		 * Initiate auto connect - Generate Bridge OAuth URL
		 */
		public function saifgs_initiate_auto_connect() {
			// error_log('SAIFGS: 🔄 Initiating auto connect via Bridge');
			try {
				$auth_url = $this->saifgs_generate_bridge_oauth_url();
				// error_log('SAIFGS: ✅ Generated Bridge OAuth URL: ' . $auth_url);

				return rest_ensure_response( array(
					'success' => true,
					'data' => array(
						'auth_url' => $auth_url,
					),
				) );

			} catch ( \Exception $e ) {
				// error_log('SAIFGS: ❌ Error initiating auto connect via Bridge: ' . $e->getMessage());
				return rest_ensure_response( array(
					'success' => false,
					'message' => $e->getMessage(),
				) );
			}
		}

		/**
		 * Deactivate auto connect
		 */
		public function saifgs_deactivate_auto_connect() {
			// error_log('SAIFGS: 🔄 Deactivating auto connect (Bridge System)');
			try {
				$token = get_option( self::$option_token );
				// error_log('SAIFGS: Current token exists for deactivation: ' . (!empty($token) ? 'YES' : 'NO'));
				
				// Try to revoke token via Bridge
				if ( ! empty( $token ) ) {
					// error_log('SAIFGS: 🔄 Attempting to revoke token via Bridge');
					$this->saifgs_revoke_token_via_bridge( $token );
				}

				// Clear all options
				// error_log('SAIFGS: 🔄 Clearing all options');
				delete_option( self::$option_token );
				delete_option( self::$option_access_code );
				delete_option( self::$option_verify );
				delete_option( self::$option_auth_expired );
				delete_option( self::$option_refresh_token );
				delete_option( 'saifgs_auto_connect_token_expiry' );
				delete_option( 'saifgs_connected_email' );

				// error_log('SAIFGS: ✅ Auto connect deactivated successfully');
				return rest_ensure_response( array(
					'success' => true,
					'message' => __( 'Successfully disconnected from Google', 'sa-integrations-for-google-sheets' ),
				) );

			} catch ( \Exception $e ) {
				// error_log('SAIFGS: ❌ Error deactivating auto connect: ' . $e->getMessage());
				return rest_ensure_response( array(
					'success' => false,
					'message' => $e->getMessage(),
				) );
			}
		}

		/**
		 * Generate Bridge OAuth URL
		 */
		private function saifgs_generate_bridge_oauth_url() {
			// error_log('SAIFGS: 🔄 Generating Bridge OAuth URL');
			
			// Generate return URL (where bridge should redirect back)
			$return_url = admin_url( 'admin.php?page=saifgs-dashboard' );
			$state = wp_create_nonce('saifgs_google_oauth_bridge');
			
			// error_log('SAIFGS: Return URL for Bridge: ' . $return_url);
			// error_log('SAIFGS: State nonce: ' . $state);
			
			$params = array(
				'action' => 'authorize',
				'return_url' => urlencode( $return_url ),
				'state' => $state,
				'plugin' => 'saifgs',
				'version' => '1.0'
			);

			$auth_url = $this->bridge_url . '?' . http_build_query($params);
			// error_log('SAIFGS: ✅ Final Bridge OAuth URL generated: ' . $auth_url);
			
			return $auth_url;
		}

		/**
		 * Refresh token via Bridge
		 */
		private function saifgs_refresh_token_via_bridge() {
			// error_log('SAIFGS: 🔄 Attempting to refresh token via Bridge');
			$refresh_token = get_option( self::$option_refresh_token );
			
			if ( empty( $refresh_token ) ) {
				// error_log('SAIFGS: ❌ No refresh token available for refresh');
				return false;
			}

			$refresh_url = $this->bridge_url . '?action=refresh_token&refresh_token=' . urlencode( $refresh_token );
			
			// error_log('SAIFGS: Sending refresh request to Bridge: ' . $refresh_url);
			$response = wp_remote_get( $refresh_url, array(
				'timeout' => 30,
			) );

			if ( is_wp_error( $response ) ) {
				// error_log('SAIFGS: ❌ Refresh token request to Bridge failed: ' . $response->get_error_message());
				return false;
			}

			$response_code = wp_remote_retrieve_response_code( $response );
			$body = wp_remote_retrieve_body( $response );

			// error_log('SAIFGS: Bridge refresh response code: ' . $response_code);
			// error_log('SAIFGS: Bridge refresh response body: ' . $body);

			$token_data = json_decode( $body, true );

			if ( $response_code === 200 && isset( $token_data['access_token'] ) ) {
				// error_log('SAIFGS: ✅ Token refresh via Bridge successful');
				// Update access token and expiry
				update_option( self::$option_token, $token_data['access_token'] );
				
				if ( isset( $token_data['expires_in'] ) ) {
					$expiry_time = time() + $token_data['expires_in'];
					update_option( 'saifgs_auto_connect_token_expiry', $expiry_time );
					// error_log('SAIFGS: ✅ New token expiry set to: ' . date('Y-m-d H:i:s', $expiry_time));
				}

				return true;
			}

			// error_log('SAIFGS: ❌ Token refresh via Bridge failed');
			return false;
		}

		/**
		 * Revoke token via Bridge
		 */
		private function saifgs_revoke_token_via_bridge( $token ) {
			// error_log('SAIFGS: 🔄 Revoking token via Bridge');
			$revoke_url = $this->bridge_url . '?action=revoke_token&token=' . urlencode( $token );
			
			$response = wp_remote_get( $revoke_url, array(
				'timeout' => 30,
			) );

			if ( is_wp_error( $response ) ) {
				// error_log('SAIFGS: ❌ Token revocation via Bridge failed: ' . $response->get_error_message());
			} else {
				$response_code = wp_remote_retrieve_response_code( $response );
				// error_log('SAIFGS: ✅ Token revocation via Bridge successful - Response code: ' . $response_code);
			}
		}

		/**
		 * Check if token is expired
		 */
		private function saifgs_is_token_expired() {
			$expiry_time = get_option( 'saifgs_auto_connect_token_expiry' );
			
			if ( empty( $expiry_time ) ) {
				// error_log('SAIFGS: ⚠️ No expiry time found, considering token expired');
				return true;
			}

			$current_time = time();
			$is_expired = ( $current_time > ( $expiry_time - 300 ) ); // 5 minutes buffer
			
			// error_log('SAIFGS: Token expiry check - current: ' . date('Y-m-d H:i:s', $current_time) . ', expiry: ' . date('Y-m-d H:i:s', $expiry_time) . ', expired: ' . ($is_expired ? 'YES' : 'NO'));
			
			return $is_expired;
		}

		/**
		 * Get connected email address
		 */
		private function saifgs_get_connected_email() {
			// error_log('SAIFGS: 🔄 Getting connected email address');
			try {
				$token = get_option( self::$option_token );
				
				if ( empty( $token ) ) {
					// error_log('SAIFGS: ❌ No token available for getting email');
					return '';
				}

				// Get user info from Google
				$user_info_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
				// error_log('SAIFGS: Requesting user info from Google');
				$response = wp_remote_get( $user_info_url, array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $token,
					),
					'timeout' => 15,
				) );

				if ( is_wp_error( $response ) ) {
					// error_log('SAIFGS: ❌ Failed to get user info - ' . $response->get_error_message());
					throw new \Exception( 'Failed to get user info' );
				}

				$response_code = wp_remote_retrieve_response_code( $response );
				$body = wp_remote_retrieve_body( $response );

				// error_log('SAIFGS: User info response code: ' . $response_code);
				// error_log('SAIFGS: User info response body: ' . $body);

				$user_info = json_decode( $body, true );

				if ( isset( $user_info['email'] ) ) {
					// error_log('SAIFGS: ✅ Email retrieved successfully: ' . $user_info['email']);
					return $user_info['email'];
				}

				// error_log('SAIFGS: ❌ No email found in user info');
				return '';

			} catch ( \Exception $e ) {
				// error_log('SAIFGS: ❌ Error getting connected email - ' . $e->getMessage());
				// Mark auth as expired if we can't get email
				update_option( self::$option_auth_expired, 'true' );
				return '';
			}
		}
	}
}
