<?php
/**
 * The Klaviyo_WP_Meta_Sync_Admin_Assets class.
 *
 * @package Klaviyo_WP_Meta_Sync/Admin
 * @author Mahmoud Basiony.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Klaviyo_WP_Meta_Sync_Admin_Assets' ) ) :

	/**
	 * Admin assets.
	 *
	 * Handles back-end styles and scripts.
	 *
	 * @since   1.0.0
	 */
	class Klaviyo_WP_Meta_Sync_Admin_Assets {
		/**
		 * The constructor.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ), 20 );
			add_action( 'admin_enqueue_scripts', array( $this, 'styles' ), 20 );
		}

		/**
		 * Enqueues admin scripts.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function scripts() {
			global $pagenow;
			$current_page = isset( $_GET ) && isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( 'klaviyo-wp-meta-sync' !== $current_page && 'users.php' !== $pagenow ) {
				return;
			}

			// Generate and verify nonce for bulk scan.
			if ( ! empty( $_REQUEST['klaviyo_bulk_sync'] ) && check_admin_referer( 'bulk_sync_nonce', 'bulk_sync_nonce' ) ) {
				$total_users = intval( $_REQUEST['klaviyo_bulk_sync'] );
			} else {
				$total_users = 0;
			}

			// Global admin scripts.
			wp_enqueue_script(
				'klaviyo_wp_meta_sync_admin_scripts',
				KLAVIYO_WP_META_SYNC_ROOT_URL . 'assets/dist/js/admin/klaviyo-wp-meta-sync-admin-scripts.min.js',
				array( 'jquery' ),
				KLAVIYO_WP_META_SYNC_PLUGIN_VERSION,
				true
			);

			// Localization variables.
			wp_localize_script(
				'klaviyo_wp_meta_sync_admin_scripts',
				'klaviyo_wp_meta_sync_params',
				array(
					'ajaxUrl'     => esc_url( admin_url( 'admin-ajax.php' ) ),
					'nonce'       => wp_create_nonce( 'klaviyo_wp_meta_sync' ),
					'syncPageUrl' => esc_url( admin_url( 'admin.php?page=klaviyo-wp-meta-sync&tab=sync' ) ),
					'syncNonce' => wp_create_nonce( 'bulk_sync_nonce' ),
					'totalUsers' => $total_users,
				)
			);
		}

		/**
		 * Enqueues admin styles.
		 *
		 * @since   1.0.0
		 *
		 * @return void
		 */
		public function styles() {
			// Global admin styles.
			wp_enqueue_style( 'klaviyo_wp_meta_sync_admin_styles', KLAVIYO_WP_META_SYNC_ROOT_URL . 'assets/dist/css/admin/klaviyo-wp-meta-sync-admin-styles.min.css', array(), KLAVIYO_WP_META_SYNC_PLUGIN_VERSION, 'all' );
		}
	}

	return new Klaviyo_WP_Meta_Sync_Admin_Assets();

endif;
