<?php
namespace SAIFGS\Settings;

if ( ! class_exists( '\SAIFGS\Settings\SAIFGS_Integrated_Auth_Handler' ) ) {

    class SAIFGS_Integrated_Auth_Handler {

        use \SAIFGS\Traits\SAIFGS_Singleton;
        use \SAIFGS\Traits\SAIFGS_Helpers;

        private $auto_connect_handler;
        private $client_credentials_handler;

        public function __construct() {
            error_log('SAIFGS INTEGRATED AUTH: Initializing integrated auth handler');
            
            // Initialize handlers
            $this->auto_connect_handler = SAIFGS_Auto_Connect_Google_Signin::get_instance();
            $this->client_credentials_handler = SAIFGS_Client_Credentials_Handler::get_instance();
            
            // Remove their individual admin_init callbacks
            remove_action('admin_init', array($this->auto_connect_handler, 'saifgs_handle_auth_callback'));
            remove_action('admin_init', array($this->client_credentials_handler, 'saifgs_handle_client_credentials_callback'));
            
            // Add our unified callback
            add_action('admin_init', array($this, 'saifgs_handle_unified_auth_callback'), 5);
        }

        /**
         * Unified callback handler for all auth methods
         */
        public function saifgs_handle_unified_auth_callback() {
            error_log('SAIFGS INTEGRATED AUTH: ===== Unified Callback Started =====');
            error_log('SAIFGS INTEGRATED AUTH: GET Parameters: ' . print_r($_GET, true));
            
            // Check if this is our dashboard page
            if ( !isset( $_GET['page'] ) || $_GET['page'] !== 'saifgs-dashboard' ) {
                error_log('SAIFGS INTEGRATED AUTH: Not our dashboard page, exiting');
                return;
            }

            // Determine which auth method is being used
            $auth_method = $this->saifgs_determine_auth_method();
            error_log('SAIFGS INTEGRATED AUTH: Detected auth method: ' . $auth_method);

            switch ($auth_method) {
                case 'client_credentials':
                    error_log('SAIFGS INTEGRATED AUTH: Processing as client credentials');
                    $this->saifgs_handle_client_credentials_callback();
                    break;
                    
                case 'auto_connect':
                    error_log('SAIFGS INTEGRATED AUTH: Processing as auto connect');
                    $this->saifgs_handle_auto_connect_callback();
                    break;
                    
                case 'error':
                    error_log('SAIFGS INTEGRATED AUTH: Processing error');
                    $this->saifgs_handle_auth_error();
                    break;
                    
                default:
                    error_log('SAIFGS INTEGRATED AUTH: No auth method detected');
                    break;
            }
            
            error_log('SAIFGS INTEGRATED AUTH: ===== Unified Callback Ended =====');
        }

        /**
         * Determine which auth method is being used
         */
        private function saifgs_determine_auth_method() {
            // Check for error first
            if ( isset( $_GET['error'] ) ) {
                return 'error';
            }
            
            // Check for client credentials (has code and state)
            if ( isset( $_GET['code'] ) && isset( $_GET['state'] ) ) {
                // Verify if state is for client credentials
                $state = sanitize_text_field( $_GET['state'] );
                if ( wp_verify_nonce( $state, 'saifgs_client_credentials_oauth' ) ) {
                    return 'client_credentials';
                }
            }
            
            // Check for auto connect (has code but no valid client credentials state)
            if ( isset( $_GET['code'] ) ) {
                return 'auto_connect';
            }
            
            // Check for auth success/error parameters
            if ( isset( $_GET['saifgs_auth'] ) ) {
                return 'auto_connect';
            }
            
            if ( isset( $_GET['client_auth'] ) || isset( $_GET['client_auth_error'] ) ) {
                return 'client_credentials';
            }
            
            return 'none';
        }

        /**
         * Handle client credentials callback
         */
        private function saifgs_handle_client_credentials_callback() {
            error_log('SAIFGS INTEGRATED AUTH: Handling client credentials callback');
            
            // Remove the tab parameter from GET to avoid conflicts
            $get_params = $_GET;
            unset($get_params['tab']);
            
            // Call the client credentials handler method
            $method = new \ReflectionMethod($this->client_credentials_handler, 'saifgs_process_oauth_callback');
            $method->setAccessible(true);
            
            $code = sanitize_text_field( $_GET['code'] );
            $state = sanitize_text_field( $_GET['state'] );
            
            try {
                $method->invoke($this->client_credentials_handler, $code, $state);
            } catch (\Exception $e) {
                error_log('SAIFGS INTEGRATED AUTH: Client credentials callback error: ' . $e->getMessage());
                $this->saifgs_redirect_with_error($e->getMessage(), 'client');
            }
        }

        /**
         * Handle auto connect callback
         */
        private function saifgs_handle_auto_connect_callback() {
            error_log('SAIFGS INTEGRATED AUTH: Handling auto connect callback');
            
            // Check if it's a success/error redirect
            if ( isset( $_GET['saifgs_auth'] ) ) {
                if ( $_GET['saifgs_auth'] === 'success' ) {
                    error_log('SAIFGS INTEGRATED AUTH: Auto connect success');
                    wp_redirect( admin_url( 'admin.php?page=saifgs-dashboard&tab=integration&auth_message=success' ) );
                    exit;
                } elseif ( $_GET['saifgs_auth'] === 'error' ) {
                    $message = isset( $_GET['message'] ) ? urldecode( $_GET['message'] ) : 'Unknown error';
                    error_log('SAIFGS INTEGRATED AUTH: Auto connect error: ' . $message);
                    $this->saifgs_redirect_with_error($message, 'auto');
                }
                return;
            }
            
            // Handle code from bridge
            if ( isset( $_GET['code'] ) ) {
                $code = sanitize_text_field( $_GET['code'] );
                error_log('SAIFGS INTEGRATED AUTH: Processing bridge code: ' . substr($code, 0, 20) . '...');
                
                // Call the auto connect handler method
                $method = new \ReflectionMethod($this->auto_connect_handler, 'saifgs_process_bridge_token');
                $method->setAccessible(true);
                
                try {
                    $method->invoke($this->auto_connect_handler, $code);
                } catch (\Exception $e) {
                    error_log('SAIFGS INTEGRATED AUTH: Auto connect callback error: ' . $e->getMessage());
                    $this->saifgs_redirect_with_error($e->getMessage(), 'auto');
                }
            }
        }

        /**
         * Handle auth errors
         */
        private function saifgs_handle_auth_error() {
            error_log('SAIFGS INTEGRATED AUTH: Handling auth error');
            
            $error = sanitize_text_field( $_GET['error'] );
            $error_description = isset( $_GET['error_description'] ) ? 
                urldecode( $_GET['error_description'] ) : 'Unknown error';
            
            error_log('SAIFGS INTEGRATED AUTH: Google error: ' . $error);
            error_log('SAIFGS INTEGRATED AUTH: Error description: ' . $error_description);
            
            $this->saifgs_redirect_with_error($error . ': ' . $error_description, 'auto');
        }

        /**
         * Redirect with error message
         */
        private function saifgs_redirect_with_error($message, $type = 'auto') {
            error_log('SAIFGS INTEGRATED AUTH: Redirecting with error: ' . $message);
            
            $param = ($type === 'client') ? 'client_auth_error' : 'saifgs_auth';
            $redirect_url = admin_url( 'admin.php?page=saifgs-dashboard&tab=integration&' . $param . '=' . urlencode($message) );
            
            error_log('SAIFGS INTEGRATED AUTH: Redirect URL: ' . $redirect_url);
            wp_redirect( $redirect_url );
            exit;
        }
    }
}