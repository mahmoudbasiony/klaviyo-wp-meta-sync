<?php
/**
 * The Klaviyo_WP_Meta_Sync_Admin_API_Handler class.
 *
 * @package Klaviyo_WP_Meta_Sync/Admin
 * @author Mahmoud Basiony.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once KLAVIYO_WP_META_SYNC_ROOT_PATH . '/vendor/autoload.php';

use KlaviyoAPI\KlaviyoAPI;

if ( ! class_exists( 'Klaviyo_WP_Meta_Sync_Admin_API_Handler' ) ) :
    /**
     * API handler class.
     *
     * Handles all API requests.
     *
     * @since 1.0.0
     */
    class Klaviyo_WP_Meta_Sync_Admin_API_Handler {
        
        private static $instance = null;
        private $klaviyo;

        private function __construct() {
            $settings = get_option( 'klaviyo_wp_meta_sync_settings', array() );
            $api_key = isset( $settings['klaviyo_api_key'] ) ? sanitize_text_field( $settings['klaviyo_api_key'] ) : '';

            // Initialize Klaviyo API with Exponential Backoff & Rate Limiting
            $this->klaviyo = new KlaviyoAPI(
                $api_key,
                num_retries: 3,
                guzzle_options: [
                    'timeout' => 10,
                    'verify' => 'C:\MAMP\bin\php\cacert.pem'
                ],
                user_agent_suffix: "/Klaviyo_WP_Meta_Sync"
            );
        }

        /**
         * Singleton instance.
         *
         * @since 1.0.0
         *
         * @return Klaviyo_WP_Meta_Sync_Admin_API_Handler
         */
        public static function get_instance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Create or Update a Profile in Klaviyo.
         *
         * @param array $profile_data Profile information
         *
         * @return mixed API response or false on failure
         */
        public function create_or_update_profile( $profile_data ) {
            try {
                return $this->klaviyo->Profiles->createOrUpdateProfile( $profile_data );
            } catch (Exception $e) {
                // Extract error details.
                $error_code = $e->getCode();
                $error_message = $e->getMessage();
                $request_data = json_encode( $profile_data, JSON_PRETTY_PRINT );
                $user_email = isset( $profile_data['data']['attributes']['email']) ? $profile_data['data']['attributes']['email'] : 'Unknown Email';

                // Create an informative log message.
                $log_message = sprintf(
                    "[Klaviyo API Error] User: %s | Code: %d | Message: %s  | Request Data: %s",
                    $user_email,
                    $error_code,
                    $error_message,
                    $request_data
                );

                // Log the error.
                Klaviyo_WP_Meta_Sync_Admin_Log::debug_log($log_message);
            
                return false;
            }            
        }

        /**
         * Generic API Request Wrapper with Safe Retry Logic.
         *
         * @param callable $callback API function
         * @param int $max_attempts Maximum retry attempts
         *
         * @since 1.0.0
         *
         * @return mixed API response or false on failure
         */
        public function safe_api_request($callback, $max_attempts = 5) {
            $attempt = 0;
            while ($attempt < $max_attempts) {
                try {
                    return $callback();
                } catch (\Exception $e) {
                    if (strpos($e->getMessage(), '429') !== false) { // Rate Limit Exceeded
                        sleep(pow(2, $attempt)); // Exponential Backoff (2^attempt seconds)
                    } else {
                        throw $e; // Other errors should not be retried
                    }
                }
                $attempt++;
            }
            return false;
        }
    }
endif;
