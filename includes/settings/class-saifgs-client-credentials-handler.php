<?php
namespace SAIFGS\Settings;

if ( ! class_exists( '\SAIFGS\Settings\SAIFGS_Client_Credentials_Handler' ) ) {

    class SAIFGS_Client_Credentials_Handler {

        use \SAIFGS\Traits\SAIFGS_Singleton;
        use \SAIFGS\Traits\SAIFGS_Helpers;
        use \SAIFGS\Traits\SAIFGS_RestAPI;

        private static $option_client_id = 'saifgs_client_credentials_client_id';
        private static $option_client_secret = 'saifgs_client_credentials_client_secret';
        private static $option_access_token = 'saifgs_client_credentials_access_token';
        private static $option_refresh_token = 'saifgs_client_credentials_refresh_token';
        private static $option_token_expiry = 'saifgs_client_credentials_token_expiry';
        private static $option_connected_email = 'saifgs_client_credentials_connected_email';
        private static $option_status = 'saifgs_client_credentials_status';

        private static $saifgs_api_route_base_url_get_client_credentials = '/get-client-credentials';
        private static $saifgs_api_route_base_url_save_client_credentials = '/save-client-credentials';
        private static $saifgs_api_route_base_url_revoke_client_credentials = '/revoke-client-credentials';

        // Scopes for the OAuth request
        private $scopes = [
            'https://www.googleapis.com/auth/spreadsheets',
            'https://www.googleapis.com/auth/drive',
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/drive.metadata.readonly'
        ];

        public function __construct() {
            error_log('SAIFGS CLIENT CREDENTIALS: Class initialized');
            add_action( 'rest_api_init', array( $this, 'saifgs_register_client_credentials_routes' ) );
        }

        public function saifgs_register_client_credentials_routes() {
            error_log('SAIFGS CLIENT CREDENTIALS: Registering REST routes');
            
            // Get client credentials status
            register_rest_route(
                $this->saifgs_get_api_base_url(),
                self::$saifgs_api_route_base_url_get_client_credentials,
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'saifgs_get_client_credentials_status' ),
                    'permission_callback' => array( $this, 'saifgs_permissions' ),
                )
            );

            // Save client credentials and initiate OAuth
            register_rest_route(
                $this->saifgs_get_api_base_url(),
                self::$saifgs_api_route_base_url_save_client_credentials,
                array(
                    'methods'             => 'POST',
                    'callback'            => array( $this, 'saifgs_save_client_credentials' ),
                    'permission_callback' => array( $this, 'saifgs_permissions' ),
                )
            );

            // Revoke client credentials
            register_rest_route(
                $this->saifgs_get_api_base_url(),
                self::$saifgs_api_route_base_url_revoke_client_credentials,
                array(
                    'methods'             => 'POST',
                    'callback'            => array( $this, 'saifgs_revoke_client_credentials' ),
                    'permission_callback' => array( $this, 'saifgs_permissions' ),
                )
            );

            // In your register_routes() method, add:
            register_rest_route(
                $this->saifgs_get_api_base_url(),
                '/check-token-status',
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'saifgs_check_token_status' ),
                    'permission_callback' => array( $this, 'saifgs_permissions' ),
                )
            );

            // Also register this route in saifgs_register_client_credentials_routes():
            register_rest_route(
                $this->saifgs_get_api_base_url(),
                '/refresh-client-token', // Add this route
                array(
                    'methods'             => 'POST',
                    'callback'            => array( $this, 'saifgs_refresh_client_token' ),
                    'permission_callback' => array( $this, 'saifgs_permissions' ),
                )
            );
            
            error_log('SAIFGS CLIENT CREDENTIALS: All REST routes registered');
        }

        /**
         * Get client credentials status
         */
        public function saifgs_get_client_credentials_status() {
            error_log('SAIFGS CLIENT CREDENTIALS: Getting client credentials status');
            try {
                $client_id = get_option( self::$option_client_id );
                $status = get_option( self::$option_status, 'not_connected' );
                $email = get_option( self::$option_connected_email, '' );

                error_log("SAIFGS CLIENT CREDENTIALS: Status check - Client ID exists: " . (!empty($client_id) ? 'YES' : 'NO'));
                error_log("SAIFGS CLIENT CREDENTIALS: Status check - Current status: " . $status);
                error_log("SAIFGS CLIENT CREDENTIALS: Status check - Connected email: " . ($email ?: 'NOT FOUND'));

                return rest_ensure_response( array(
                    'success' => true,
                    'data' => array(
                        'client_id' => $client_id,
                        'status' => $status,
                        'email' => $email,
                        'has_credentials' => !empty($client_id)
                    ),
                ) );

            } catch ( \Exception $e ) {
                error_log('SAIFGS CLIENT CREDENTIALS: Error getting status - ' . $e->getMessage());
                return rest_ensure_response( array(
                    'success' => false,
                    'message' => $e->getMessage(),
                ) );
            }
        }

        /**
         * Save client credentials and initiate OAuth
         */
        public function saifgs_save_client_credentials( $request ) {
            error_log('SAIFGS CLIENT CREDENTIALS: Saving client credentials');
            try {
                $params = $request->get_json_params();
                $client_id = sanitize_text_field( $params['client_id'] ?? '' );
                $client_secret = sanitize_text_field( $params['client_secret'] ?? '' );

                error_log("SAIFGS CLIENT CREDENTIALS: Received Client ID: " . substr($client_id, 0, 20) . '...');
                error_log("SAIFGS CLIENT CREDENTIALS: Received Client Secret: " . (!empty($client_secret) ? substr($client_secret, 0, 10) . '...' : 'EMPTY'));

                if ( empty( $client_id ) || empty( $client_secret ) ) {
                    $error_msg = 'Client ID and Client Secret are required';
                    error_log('SAIFGS CLIENT CREDENTIALS: ' . $error_msg);
                    throw new \Exception( __( $error_msg, 'sa-integrations-for-google-sheets' ) );
                }

                // Validate client ID format
                if ( !preg_match('/.+\.apps\.googleusercontent\.com$/', $client_id ) ) {
                    $error_msg = 'Invalid Client ID format: ' . $client_id;
                    error_log('SAIFGS CLIENT CREDENTIALS: ' . $error_msg);
                    throw new \Exception( __( 'Invalid Client ID format', 'sa-integrations-for-google-sheets' ) );
                }

                // Save credentials
                update_option( self::$option_client_id, $client_id );
                update_option( self::$option_client_secret, $this->saifgs_encrypt_data( $client_secret ) );
                error_log('SAIFGS CLIENT CREDENTIALS: Credentials saved successfully');

                // Generate OAuth URL
                $auth_url = $this->saifgs_generate_client_oauth_url( $client_id );
                error_log('SAIFGS CLIENT CREDENTIALS: Generated OAuth URL: ' . $auth_url);

                return rest_ensure_response( array(
                    'success' => true,
                    'data' => array(
                        'auth_url' => $auth_url,
                        'message' => __( 'Credentials saved. Redirecting to Google...', 'sa-integrations-for-google-sheets' )
                    ),
                ) );

            } catch ( \Exception $e ) {
                error_log('SAIFGS CLIENT CREDENTIALS: Error saving credentials - ' . $e->getMessage());
                return rest_ensure_response( array(
                    'success' => false,
                    'message' => $e->getMessage(),
                ) );
            }
        }

        /**
         * Generate OAuth URL for client credentials
         */
        private function saifgs_generate_client_oauth_url( $client_id ) {
            error_log('SAIFGS CLIENT CREDENTIALS: Generating OAuth URL for client: ' . substr($client_id, 0, 20) . '...');
            
            // IMPORTANT: Use the exact redirect URI registered in Google Cloud Console
            // This must match exactly what's in your Google Cloud Console
            $redirect_uri = admin_url( 'admin.php?page=saifgs-dashboard' );
            $state = wp_create_nonce( 'saifgs_client_credentials_oauth' );
            
            error_log('SAIFGS CLIENT CREDENTIALS: Redirect URI: ' . $redirect_uri);
            error_log('SAIFGS CLIENT CREDENTIALS: State nonce: ' . $state);

            $params = array(
                'client_id' => $client_id,
                'redirect_uri' => $redirect_uri,
                'response_type' => 'code',
                'scope' => implode( ' ', $this->scopes ),
                'access_type' => 'offline',
                'prompt' => 'consent',
                'state' => $state,
                'include_granted_scopes' => 'true'
            );

            $auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query( $params );
            error_log('SAIFGS CLIENT CREDENTIALS: Final OAuth URL generated');
            
            return $auth_url;
        }

        /**
         * Process OAuth callback (called by integrated handler)
         */
        public function saifgs_process_oauth_callback( $code, $state ) {
            error_log('SAIFGS CLIENT CREDENTIALS: Processing OAuth callback');
            try {
                // Verify state
                error_log('SAIFGS CLIENT CREDENTIALS: Verifying state nonce');
                if ( ! wp_verify_nonce( $state, 'saifgs_client_credentials_oauth' ) ) {
                    $error_msg = 'Invalid state parameter. State: ' . $state;
                    error_log('SAIFGS CLIENT CREDENTIALS: ' . $error_msg);
                    throw new \Exception( __( 'Invalid state parameter', 'sa-integrations-for-google-sheets' ) );
                }
                error_log('SAIFGS CLIENT CREDENTIALS: State verified successfully');

                $client_id = get_option( self::$option_client_id );
                $encrypted_secret = get_option( self::$option_client_secret );
                
                error_log('SAIFGS CLIENT CREDENTIALS: Retrieved Client ID: ' . ($client_id ? substr($client_id, 0, 20) . '...' : 'NOT FOUND'));
                error_log('SAIFGS CLIENT CREDENTIALS: Encrypted secret exists: ' . (!empty($encrypted_secret) ? 'YES' : 'NO'));

                if ( empty( $client_id ) || empty( $encrypted_secret ) ) {
                    $error_msg = 'Client credentials not found in database';
                    error_log('SAIFGS CLIENT CREDENTIALS: ' . $error_msg);
                    throw new \Exception( __( 'Client credentials not found', 'sa-integrations-for-google-sheets' ) );
                }

                $client_secret = $this->saifgs_decrypt_data( $encrypted_secret );
                error_log('SAIFGS CLIENT CREDENTIALS: Client secret decrypted successfully');

                // Exchange code for tokens
                error_log('SAIFGS CLIENT CREDENTIALS: Exchanging authorization code for tokens');
                $token_data = $this->saifgs_exchange_code_for_tokens( $code, $client_id, $client_secret );

                if ( $token_data && isset( $token_data['access_token'] ) ) {
                    error_log('SAIFGS CLIENT CREDENTIALS: Token exchange successful');
                    error_log('SAIFGS CLIENT CREDENTIALS: Access token received: ' . substr($token_data['access_token'], 0, 20) . '...');
                    
                    if ( isset($token_data['refresh_token']) ) {
                        error_log('SAIFGS CLIENT CREDENTIALS: Refresh token received: YES');
                    } else {
                        error_log('SAIFGS CLIENT CREDENTIALS: Refresh token received: NO');
                    }
                    
                    $this->saifgs_save_token_data( $token_data );
                    
                    // Get user email
                    error_log('SAIFGS CLIENT CREDENTIALS: Getting user email');
                    $email = $this->saifgs_get_user_email( $token_data['access_token'] );
                    if ( $email ) {
                        update_option( self::$option_connected_email, $email );
                        error_log('SAIFGS CLIENT CREDENTIALS: Connected email saved: ' . $email);
                    } else {
                        error_log('SAIFGS CLIENT CREDENTIALS: Could not retrieve user email');
                    }

                    update_option( self::$option_status, 'connected' );
                    error_log('SAIFGS CLIENT CREDENTIALS: Status updated to connected');

                    // Redirect to success
                    error_log('SAIFGS CLIENT CREDENTIALS: Redirecting to success page');
                    wp_redirect( admin_url( 'admin.php?page=saifgs-dashboard&tab=integration&auth_message=success' ) );
                    exit;
                } else {
                    $error_msg = 'Failed to get access token from Google';
                    error_log('SAIFGS CLIENT CREDENTIALS: ' . $error_msg);
                    throw new \Exception( __( 'Failed to get access token', 'sa-integrations-for-google-sheets' ) );
                }

            } catch ( \Exception $e ) {
                error_log('SAIFGS CLIENT CREDENTIALS: OAuth callback error - ' . $e->getMessage());
                wp_redirect( admin_url( 'admin.php?page=saifgs-dashboard&tab=integration&client_auth_error=' . urlencode( $e->getMessage() ) ) );
                exit;
            }
        }

        /**
         * Exchange authorization code for tokens
         */
        private function saifgs_exchange_code_for_tokens( $code, $client_id, $client_secret ) {
            error_log('SAIFGS CLIENT CREDENTIALS: Exchanging code for tokens');
            
            $redirect_uri = admin_url( 'admin.php?page=saifgs-dashboard' );
            error_log('SAIFGS CLIENT CREDENTIALS: Using redirect URI: ' . $redirect_uri);
            
            $post_data = array(
                'code' => $code,
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'redirect_uri' => $redirect_uri,
                'grant_type' => 'authorization_code'
            );
            
            error_log('SAIFGS CLIENT CREDENTIALS: Token exchange request data (excluding secret)');
            
            $response = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
                'headers' => array(
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ),
                'body' => $post_data,
                'timeout' => 30
            ) );

            if ( is_wp_error( $response ) ) {
                error_log('SAIFGS CLIENT CREDENTIALS: Token exchange WP Error - ' . $response->get_error_message());
                throw new \Exception( $response->get_error_message() );
            }

            $response_code = wp_remote_retrieve_response_code( $response );
            $body = wp_remote_retrieve_body( $response );
            
            error_log('SAIFGS CLIENT CREDENTIALS: Token exchange response code: ' . $response_code);
            error_log('SAIFGS CLIENT CREDENTIALS: Token exchange response body: ' . $body);

            $body_data = json_decode( $body, true );

            if ( isset( $body_data['error'] ) ) {
                $error_msg = 'Google API Error: ' . ($body_data['error_description'] ?? $body_data['error']);
                error_log('SAIFGS CLIENT CREDENTIALS: ' . $error_msg);
                throw new \Exception( $error_msg );
            }

            if ( $response_code !== 200 ) {
                $error_msg = 'HTTP Error ' . $response_code . ' from Google API';
                error_log('SAIFGS CLIENT CREDENTIALS: ' . $error_msg);
                throw new \Exception( $error_msg );
            }

            return $body_data;
        }

        /**
         * Save token data
         */
        private function saifgs_save_token_data( $token_data ) {
            error_log('SAIFGS CLIENT CREDENTIALS: Saving token data');
            
            update_option( self::$option_access_token, $token_data['access_token'] );
            error_log('SAIFGS CLIENT CREDENTIALS: Access token saved');
            
            if ( isset( $token_data['refresh_token'] ) ) {
                update_option( self::$option_refresh_token, $this->saifgs_encrypt_data( $token_data['refresh_token'] ) );
                error_log('SAIFGS CLIENT CREDENTIALS: Refresh token saved (encrypted)');
            }

            if ( isset( $token_data['expires_in'] ) ) {
                $expiry = time() + $token_data['expires_in'];
                update_option( self::$option_token_expiry, $expiry );
                error_log('SAIFGS CLIENT CREDENTIALS: Token expiry set to: ' . date('Y-m-d H:i:s', $expiry));
            }
            
            error_log('SAIFGS CLIENT CREDENTIALS: All token data saved successfully');
        }

        /**
         * Get user email
         */
        private function saifgs_get_user_email( $access_token ) {
            error_log('SAIFGS CLIENT CREDENTIALS: Getting user email from Google');
            
            $response = wp_remote_get( 'https://www.googleapis.com/oauth2/v2/userinfo', array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token
                ),
                'timeout' => 15
            ) );

            if ( is_wp_error( $response ) ) {
                error_log('SAIFGS CLIENT CREDENTIALS: Failed to get user email - ' . $response->get_error_message());
                return '';
            }

            $response_code = wp_remote_retrieve_response_code( $response );
            $body = wp_remote_retrieve_body( $response );
            
            error_log('SAIFGS CLIENT CREDENTIALS: User info response code: ' . $response_code);
            
            if ( $response_code === 200 ) {
                $body_data = json_decode( $body, true );
                if ( isset( $body_data['email'] ) ) {
                    error_log('SAIFGS CLIENT CREDENTIALS: User email found: ' . $body_data['email']);
                    return $body_data['email'];
                }
            } else {
                error_log('SAIFGS CLIENT CREDENTIALS: Failed to get user info, response: ' . $body);
            }

            return '';
        }

        /**
         * Revoke client credentials
         */
        public function saifgs_revoke_client_credentials() {
            error_log('SAIFGS CLIENT CREDENTIALS: Revoking client credentials');
            try {
                $access_token = get_option( self::$option_access_token );
                
                error_log('SAIFGS CLIENT CREDENTIALS: Current access token exists: ' . (!empty($access_token) ? 'YES' : 'NO'));
                
                // Revoke token if exists
                if ( $access_token ) {
                    error_log('SAIFGS CLIENT CREDENTIALS: Revoking token at Google');
                    $revoke_response = wp_remote_post( 'https://oauth2.googleapis.com/revoke', array(
                        'body' => array( 'token' => $access_token ),
                        'timeout' => 15
                    ) );
                    
                    if ( is_wp_error( $revoke_response ) ) {
                        error_log('SAIFGS CLIENT CREDENTIALS: Token revocation failed - ' . $revoke_response->get_error_message());
                    } else {
                        $revoke_code = wp_remote_retrieve_response_code( $revoke_response );
                        error_log('SAIFGS CLIENT CREDENTIALS: Token revocation response code: ' . $revoke_code);
                    }
                }

                // Clear all options
                error_log('SAIFGS CLIENT CREDENTIALS: Clearing all client credential options');
                delete_option( self::$option_client_id );
                delete_option( self::$option_client_secret );
                delete_option( self::$option_access_token );
                delete_option( self::$option_refresh_token );
                delete_option( self::$option_token_expiry );
                delete_option( self::$option_connected_email );
                delete_option( self::$option_status );

                error_log('SAIFGS CLIENT CREDENTIALS: All options cleared successfully');

                return rest_ensure_response( array(
                    'success' => true,
                    'message' => __( 'Successfully disconnected and removed credentials', 'sa-integrations-for-google-sheets' ),
                ) );

            } catch ( \Exception $e ) {
                error_log('SAIFGS CLIENT CREDENTIALS: Error revoking - ' . $e->getMessage());
                return rest_ensure_response( array(
                    'success' => false,
                    'message' => $e->getMessage(),
                ) );
            }
        }

        /**
         * Encrypt sensitive data
         */
        private function saifgs_encrypt_data( $data ) {
            error_log('SAIFGS CLIENT CREDENTIALS: Encrypting data');
            
            if ( empty( $data ) ) {
                error_log('SAIFGS CLIENT CREDENTIALS: No data to encrypt');
                return $data;
            }

            $key = $this->saifgs_get_encryption_key();
            $iv = openssl_random_pseudo_bytes( openssl_cipher_iv_length( 'AES-256-CBC' ) );
            $encrypted = openssl_encrypt( $data, 'AES-256-CBC', $key, 0, $iv );
            
            error_log('SAIFGS CLIENT CREDENTIALS: Data encrypted successfully');
            return base64_encode( $iv . $encrypted );
        }

        /**
         * Decrypt data
         */
        private function saifgs_decrypt_data( $data ) {
            error_log('SAIFGS CLIENT CREDENTIALS: Decrypting data');
            
            if ( empty( $data ) ) {
                error_log('SAIFGS CLIENT CREDENTIALS: No data to decrypt');
                return $data;
            }

            $data = base64_decode( $data );
            $iv_length = openssl_cipher_iv_length( 'AES-256-CBC' );
            $iv = substr( $data, 0, $iv_length );
            $encrypted = substr( $data, $iv_length );

            $key = $this->saifgs_get_encryption_key();
            $decrypted = openssl_decrypt( $encrypted, 'AES-256-CBC', $key, 0, $iv );
            
            error_log('SAIFGS CLIENT CREDENTIALS: Data decrypted successfully');
            return $decrypted;
        }

        /**
         * Get encryption key
         */
        private function saifgs_get_encryption_key() {
            $key = defined( 'AUTH_KEY' ) ? AUTH_KEY : 'saifgs_default_key';
            error_log('SAIFGS CLIENT CREDENTIALS: Using encryption key from: ' . (defined('AUTH_KEY') ? 'AUTH_KEY' : 'default'));
            return substr( hash( 'sha256', $key ), 0, 32 );
        }

        /**
         * Refresh client token via REST API
         */
        public function saifgs_refresh_client_token() {
            error_log('SAIFGS CLIENT CREDENTIALS: Refreshing client token via REST');
            try {
                // First check if we're connected via OAuth (Client ID/Secret)
                $is_oauth_connected = get_option(self::$option_status) === 'connected';
                $has_client_id = !empty(get_option(self::$option_client_id));
                
                error_log('SAIFGS CLIENT CREDENTIALS: Is OAuth connected: ' . ($is_oauth_connected ? 'YES' : 'NO'));
                error_log('SAIFGS CLIENT CREDENTIALS: Has Client ID: ' . ($has_client_id ? 'YES' : 'NO'));
                
                // If not connected via OAuth (Client ID/Secret), return success
                if (!$is_oauth_connected || !$has_client_id) {
                    error_log('SAIFGS CLIENT CREDENTIALS: Not connected via OAuth (Client ID/Secret). No refresh needed.');
                    return rest_ensure_response(array(
                        'success' => true,
                        'message' => 'Connected via other method (JSON/Auto-Connect). Token refresh not required.',
                        'is_oauth_connection' => false,
                    ));
                }

                
                $refresh_token = $this->saifgs_decrypt_data(get_option(self::$option_refresh_token));
                $client_id = get_option(self::$option_client_id);
                $client_secret = $this->saifgs_decrypt_data(get_option(self::$option_client_secret));

                if (empty($refresh_token) || empty($client_id) || empty($client_secret)) {
                    error_log('SAIFGS CLIENT CREDENTIALS: Missing credentials for token refresh');
                    return rest_ensure_response(array(
                        'success' => false,
                        'message' => 'Missing credentials for token refresh',
                    ));
                }

                $response = wp_remote_post('https://oauth2.googleapis.com/token', array(
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
                ));

                if (is_wp_error($response)) {
                    error_log('SAIFGS CLIENT CREDENTIALS: WP Error refreshing token - ' . $response->get_error_message());
                    return rest_ensure_response(array(
                        'success' => false,
                        'message' => $response->get_error_message(),
                    ));
                }

                $response_code = wp_remote_retrieve_response_code($response);
                $body = wp_remote_retrieve_body($response);
                
                error_log('SAIFGS CLIENT CREDENTIALS: Token refresh response code: ' . $response_code);
                error_log('SAIFGS CLIENT CREDENTIALS: Token refresh response body: ' . $body);

                $body_data = json_decode($body, true);

                if ($response_code === 200 && isset($body_data['access_token'])) {
                    // Save the new token data
                    $this->saifgs_save_token_data($body_data);
                    
                    error_log('SAIFGS CLIENT CREDENTIALS: Token refreshed and saved successfully');
                    
                    return rest_ensure_response(array(
                        'success' => true,
                        'message' => 'Token refreshed successfully',
                        'data' => array(
                            'token_expiry' => date('Y-m-d H:i:s', get_option(self::$option_token_expiry))
                        )
                    ));
                } else {
                    $error_msg = isset($body_data['error_description']) ? $body_data['error_description'] : 'Failed to refresh token';
                    error_log('SAIFGS CLIENT CREDENTIALS: Google API error - ' . $error_msg);
                    
                    return rest_ensure_response(array(
                        'success' => false,
                        'message' => $error_msg,
                        'requires_reconnect' => true,
                    ));
                }

            } catch (\Exception $e) {
                error_log('SAIFGS CLIENT CREDENTIALS: Exception refreshing token - ' . $e->getMessage());
                return rest_ensure_response(array(
                    'success' => false,
                    'message' => $e->getMessage(),
                ));
            }
        }
        /**
         * Check Google OAuth token status
         */
        public function saifgs_check_token_status() {
            error_log('SAIFGS CLIENT CREDENTIALS: Checking token status');
            try {
                // Get the stored token data
                $access_token = get_option(self::$option_access_token);
                $refresh_token_encrypted = get_option(self::$option_refresh_token);
                $token_expiry = get_option(self::$option_token_expiry);
                $current_time = time();

                error_log('SAIFGS CLIENT CREDENTIALS: Token status check - Access token exists: ' . (!empty($access_token) ? 'YES' : 'NO'));
                error_log('SAIFGS CLIENT CREDENTIALS: Token status check - Refresh token exists: ' . (!empty($refresh_token_encrypted) ? 'YES' : 'NO'));
                error_log('SAIFGS CLIENT CREDENTIALS: Token status check - Token expiry: ' . ($token_expiry ? date('Y-m-d H:i:s', $token_expiry) : 'NOT SET'));
                error_log('SAIFGS CLIENT CREDENTIALS: Token status check - Current time: ' . date('Y-m-d H:i:s', $current_time));

                // Check if connected via OAuth (Client ID/Secret)
                $is_oauth_connected = get_option(self::$option_status) === 'connected';
                $has_client_id = !empty(get_option(self::$option_client_id));
                
                error_log('SAIFGS CLIENT CREDENTIALS: Is OAuth connected: ' . ($is_oauth_connected ? 'YES' : 'NO'));
                error_log('SAIFGS CLIENT CREDENTIALS: Has Client ID: ' . ($has_client_id ? 'YES' : 'NO'));
                
                // If not connected via OAuth (Client ID/Secret), then we don't need to check token status
                if (!$is_oauth_connected || !$has_client_id) {
                    error_log('SAIFGS CLIENT CREDENTIALS: Not connected via OAuth (Client ID/Secret). Skipping token check.');
                    return rest_ensure_response(array(
                        'success' => true,
                        'requires_refresh' => false,
                        'has_token' => false,
                        'is_oauth_connection' => false,
                        'message' => 'Connected via other method (JSON/Auto-Connect). Token check not required.',
                    ));
                }

                // If no access token, token is invalid
                if (empty($access_token)) {
                    error_log('SAIFGS CLIENT CREDENTIALS: No access token found');
                    return rest_ensure_response(array(
                        'success' => false,
                        'requires_refresh' => true,
                        'has_token' => false,
                        'message' => 'No access token found. Please connect to Google.',
                    ));
                }

                // If we have a refresh token, we can always try to refresh
                // But we should check if token is expired or about to expire
                if ($token_expiry) {
                    // Add a buffer of 5 minutes (300 seconds) before actual expiry
                    $buffer_time = 300;
                    $expires_at = $token_expiry;
                    
                    error_log('SAIFGS CLIENT CREDENTIALS: Token expires at: ' . date('Y-m-d H:i:s', $expires_at));
                    error_log('SAIFGS CLIENT CREDENTIALS: Time left: ' . ($expires_at - $current_time) . ' seconds');
                    
                    // Check if token is expired or about to expire
                    if ($current_time > ($expires_at - $buffer_time)) {
                        error_log('SAIFGS CLIENT CREDENTIALS: Token requires refresh (expired or about to expire)');
                        return rest_ensure_response(array(
                            'success' => true,
                            'requires_refresh' => true,
                            'has_token' => true,
                            'expired' => true,
                            'time_left' => max(0, $expires_at - $current_time),
                            'message' => 'Token expired or about to expire',
                        ));
                    }
                }

                // Token is still valid
                error_log('SAIFGS CLIENT CREDENTIALS: Token is valid');
                return rest_ensure_response(array(
                    'success' => true,
                    'requires_refresh' => false,
                    'has_token' => true,
                    'expired' => false,
                    'time_left' => $token_expiry ? ($token_expiry - $current_time) : 0,
                    'expires_in_human' => $token_expiry ? $this->seconds_to_time($token_expiry - $current_time) : 'Unknown',
                    'message' => 'Token is valid',
                ));

            } catch (\Exception $e) {
                error_log('SAIFGS CLIENT CREDENTIALS: Error checking token status - ' . $e->getMessage());
                return rest_ensure_response(array(
                    'success' => false,
                    'requires_refresh' => true,
                    'message' => 'Error checking token status: ' . $e->getMessage(),
                ));
            }
        }

        /**
         * Convert seconds to human readable time
         */
        private function seconds_to_time($seconds) {
            if ($seconds <= 0) {
                return 'Expired';
            }
            
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            $seconds = $seconds % 60;
            
            if ($hours > 0) {
                return sprintf('%d hours, %d minutes', $hours, $minutes);
            } elseif ($minutes > 0) {
                return sprintf('%d minutes, %d seconds', $minutes, $seconds);
            } else {
                return sprintf('%d seconds', $seconds);
            }
        }
    }
}
