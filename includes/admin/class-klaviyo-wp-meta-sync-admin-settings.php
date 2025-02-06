<?php
/**
 * The Klaviyo_WP_Meta_Sync_Admin_Settings class.
 *
 * @package Klaviyo_WP_Meta_Sync/Admin
 * @author Mahmoud Basiony.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Klaviyo_WP_Meta_Sync_Admin_Settings' ) ) :

	/**
	 * Admin menus.
	 *
	 * Adds menu and sub-menus pages.
	 *
	 * @since 1.0.0
	 */
	class Klaviyo_WP_Meta_Sync_Admin_Settings {
		/**
		 * The settings.
		 *
		 * @var array
		 */
		private $settings;

		/**
		 * The constructor.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function __construct() {
			// Get settings.
			$this->settings = get_option( 'klaviyo_wp_meta_sync_settings', array() );

			// Actions.
			add_action( 'admin_menu', array( $this, 'menu' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
		}

		/**
		 * Adds menu and sub-menus pages.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function menu() {
			$hook = add_menu_page(
				esc_html__( 'Klaviyo WP Meta Sync', 'klaviyo-wp-meta-sync' ),
				esc_html__( 'Klaviyo WP Meta Sync', 'klaviyo-wp-meta-sync' ),
				'manage_options',
				'klaviyo-wp-meta-sync',
				array( $this, 'menu_page' ),
				'dashicons-email-alt'
			);
		}

		/**
		 * Renders menu page content.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function menu_page() {
			include_once KLAVIYO_WP_META_SYNC_TEMPLATES_PATH . 'admin/settings.php';
		}

		/**
		 * Registers settings.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function register_settings() {
			// Ignore the phpcs warning here as we are dynamically registering the setting.
			register_setting( // phpcs:ignore PluginCheck.CodeAnalysis.SettingSanitization.register_settingDynamic
				'klaviyo_wp_meta_sync_settings',
				'klaviyo_wp_meta_sync_settings',
				array(
					'type'              => 'array',
					'description'       => esc_html__( 'Settings for the Klaviyo WP Meta Sync plugin.', 'klaviyo-wp-meta-sync' ),
					'sanitize_callback' => array( $this, 'sanitize_settings' ),
					'show_in_rest'      => false,
					'default'           => array(),
				)
			);

			add_settings_section(
				'klaviyo_wp_meta_sync_general_settings_section',
				esc_html__( 'General', 'klaviyo-wp-meta-sync' ),
				null,
				'klaviyo-wp-meta-sync'
			);

			// API Key Field.
			add_settings_field(
				'klaviyo_api_key',
				esc_html__( 'Klaviyo API Key', 'klaviyo-wp-meta-sync' ),
				array( $this, 'settings_api_key' ),
				'klaviyo-wp-meta-sync',
				'klaviyo_wp_meta_sync_general_settings_section'
			);

			add_settings_field(
				'email_notifications',
				esc_html__( 'Email Notifications', 'klaviyo-wp-meta-sync' ),
				array( $this, 'settings_email_notifications' ),
				'klaviyo-wp-meta-sync',
				'klaviyo_wp_meta_sync_general_settings_section'
			);

			add_settings_field(
				'email_addresses',
				esc_html__( 'Email Address(es)', 'klaviyo-wp-meta-sync' ),
				array( $this, 'settings_email_addresses' ),
				'klaviyo-wp-meta-sync',
				'klaviyo_wp_meta_sync_general_settings_section'
			);
		}

		/**
		 * Sanitization callback for plugin settings.
		 *
		 * @since 1.0.0
		 *
		 * @param array $input The raw input values.
		 *
		 * @return array $sanitized_input The sanitized input values.
		 */
		public function sanitize_settings( $input ) {
			$sanitized_input = array();

			// Ensure API Key is sanitized.
			if ( isset( $input['klaviyo_api_key'] ) ) {
				$sanitized_input['klaviyo_api_key'] = sanitize_text_field( $input['klaviyo_api_key'] );
			}

			// Ensure email_notifications saves correctly (Checkbox).
			$sanitized_input['email_notifications'] = isset( $input['email_notifications'] ) ? 'on' : '';

			// Proper email sanitization (Comma-separated emails).
			if ( isset( $input['email_addresses'] ) ) {
				$emails                             = explode( ',', $input['email_addresses'] );
				$sanitized_emails                   = array_filter(
					$emails,
					function ( $email ) {
						return is_email( trim( $email ) );
					}
				);
				$sanitized_input['email_addresses'] = implode( ',', $sanitized_emails );
			}

			return $sanitized_input;
		}

		/**
		 * Render the API Key field.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function settings_api_key() {
			$api_key = isset( $this->settings['klaviyo_api_key'] ) ? esc_attr( $this->settings['klaviyo_api_key'] ) : '';
			
			include_once KLAVIYO_WP_META_SYNC_TEMPLATES_PATH . 'admin/views/sections/fields/api-key.php';
		}

		/**
		 * Render the email addresses field.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function settings_email_addresses() {
			$email_notifications = isset( $this->settings['email_notifications'] ) ? $this->settings['email_notifications'] : '';
			$email_addresses     = isset( $this->settings['email_addresses'] ) ? $this->settings['email_addresses'] : '';

			include_once KLAVIYO_WP_META_SYNC_TEMPLATES_PATH . 'admin/views/sections/fields/email-addresses.php';
		}

		/**
		 * Renders the email notifications field.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function settings_email_notifications() {
			$email_notifications = isset( $this->settings['email_notifications'] ) ? $this->settings['email_notifications'] : '';
			$email_addresses     = isset( $this->settings['email_addresses'] ) ? $this->settings['email_addresses'] : '';

			include_once KLAVIYO_WP_META_SYNC_TEMPLATES_PATH . 'admin/views/sections/fields/email-notifications.php';
		}
	}

	return new Klaviyo_WP_Meta_Sync_Admin_Settings();

endif;
