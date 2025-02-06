<?php
/**
 * The Klaviyo_WP_Meta_Sync_Utilities class.
 *
 * @package Klaviyo_WP_Meta_Sync
 * @author Mahmoud Basiony
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Klaviyo_WP_Meta_Sync_Utilities' ) ) {
	/**
	 * Utilities.
	 *
	 * @since 1.0.0
	 */
	class Klaviyo_WP_Meta_Sync_Utilities {
		/**
		 * Send an email notification when a Klaviyo API error occurs.
		 *
		 * @param string $user_email The email of the user being synced.
		 * @param int    $error_code The API error code.
		 * @param string $error_message The API error message.
		 * @param string $request_data The request data sent to Klaviyo.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public static function send_error_notification( $user_email, $error_code, $error_message, $request_data ) {
			// Get settings.
			$settings     = get_option( 'klaviyo_wp_meta_sync_settings', array() );
			$enabled      = isset( $settings['email_notifications'] ) ? sanitize_text_field( $settings['email_notifications'] ) : '';
			$admin_emails = isset( $settings['email_addresses'] ) ? sanitize_text_field( $settings['email_addresses'] ) : '';

			// Exit if notifications are disabled or no admin emails provided.
			if ( 'on' !== $enabled || empty( $admin_emails ) ) {
				return;
			}

			// Convert comma-separated emails to an array.
			$email_recipients = array_map( 'trim', explode( ',', $admin_emails ) );

			// Validate email addresses.
			$email_recipients = array_filter( $email_recipients, 'is_email' );

			// If no valid email addresses, exit.
			if ( empty( $email_recipients ) ) {
				return;
			}

			// Get site name and admin email.
			$site_name   = get_bloginfo( 'name' );
			$admin_email = get_option( 'admin_email' );
			if ( ! is_email( $admin_email ) ) {
				$admin_email = 'no-reply@' . parse_url( get_site_url(), PHP_URL_HOST );
			}

			// Email subject.
			$subject = sprintf( '⚠️ Klaviyo API Error - %s', $site_name );

			// Email message in HTML format.
			$message = sprintf(
				'<h2>Klaviyo API Error</h2>
				<p>An error occurred while syncing a user with Klaviyo.</p>
				<hr>
				<h3>Error Details:</h3>
				<ul>
					<li><strong>User Email:</strong> %s</li>
					<li><strong>Error Code:</strong> %d</li>
					<li><strong>Error Message:</strong> %s</li>
				</ul>
				<h3>Request Data:</h3>
				<pre>%s</pre>
				<hr>
				<p>Please check the logs for more details.</p>
				<p>Best Regards,</p>
				<p><strong>%s</strong></p>',
				esc_html( $user_email ),
				intval( $error_code ),
				esc_html( $error_message ),
				esc_html( $request_data ),
				esc_html( $site_name )
			);

			// Headers.
			$headers = array(
				'Content-Type: text/html; charset=UTF-8',
				'From: ' . $site_name . ' <' . $admin_email . '>',
			);

			// Send the email.
			if ( ! wp_mail( $email_recipients, $subject, $message, $headers ) ) {
				// Log the error.
				Klaviyo_WP_Meta_Sync_Admin_Log::debug_log( '[Klaviyo API Error] Email notification failed to send.' );
			}
		}
	}
}
