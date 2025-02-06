<?php
/**
 * The Klaviyo_WP_Meta_Sync_Admin_Log class.
 *
 * @package Klaviyo_WP_Meta_Sync/Admin
 * @author Mahmoud Basiony.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Klaviyo_WP_Meta_Sync_Admin_Log' ) ) :
	/**
	 * Admin log.
	 *
	 * Logs data to a file.
	 *
	 * @since 1.0.0
	 */
	class Klaviyo_WP_Meta_Sync_Admin_Log {

		/**
		 * Name for log file.
		 */
		const LOG_ALIAS = 'klaviyo_wp_meta_sync';

		/**
		 * Path to the log file.
		 *
		 * @var $file_path
		 */
		private static $file_path;

		/**
		 * Initialize the file path for the log file.
		 */
		private static function initialize_file_path() {
			if ( ! isset( self::$file_path ) ) {
				// Get the uploads directory.
				$upload_dir = wp_upload_dir();

				// Create a subdirectory for plugin logs.
				$log_dir = trailingslashit( $upload_dir['basedir'] ) . 'klaviyo-wp-meta-sync';

				// Ensure the directory exists.
				if ( ! file_exists( $log_dir ) ) {
					wp_mkdir_p( $log_dir ); // Create the directory if it doesn't exist.
				}

				// Set the full path for the log file.
				self::$file_path = trailingslashit( $log_dir ) . 'klaviyo-sync-errors.log';
			}
		}

		/**
		 * Opens the file handle for logging purposes.
		 *
		 * Uses WP_Filesystem for compatibility and security.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function open() {
			global $wp_filesystem;

			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();

			self::initialize_file_path();

			// Check if the log file exists, and create it if not.
			if ( ! $wp_filesystem->exists( self::$file_path ) ) {
				$wp_filesystem->put_contents( self::$file_path, '', FS_CHMOD_FILE );
			}
		}

		/**
		 * Writes a log message to the file handle, if it exists and is a valid resource.
		 *
		 * @param string $message The log message to be written.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function debug_log( $message ) {
			global $wp_filesystem;

			self::open();

			if ( isset( self::$file_path ) && $wp_filesystem->exists( self::$file_path ) ) {
				// Read the existing content.
				$existing_content = $wp_filesystem->get_contents( self::$file_path );

				// Append the new log entry.
				$log_message = gmdate( 'Y-m-d H:i:s' ) . ": {$message}\n";
				$new_content = $existing_content . $log_message;

				// Write the updated content back to the file.
				$wp_filesystem->put_contents( self::$file_path, $new_content, FS_CHMOD_FILE );
			}
		}
	}

endif;
