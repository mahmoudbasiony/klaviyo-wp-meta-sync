<?php
/**
 * Uninstall Klaviyo WP Meta Sync plugin.
 *
 * @package Klaviyo_WP_Meta_Sync
 * @author  Reviewshake
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit; // Exit if uninstall not called from WordPress.
}

/*
 * Only remove plugin data if the WP_UNINSTALL_PLUGIN constant is set to true in user's
 * wp-config.php. This is to prevent data loss when deleting the plugin from the backend
 * and to ensure only the site owner can perform this action.
 */
if ( defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	/*
	 * Delete plugin options.
	 */
	delete_option( 'klaviyo_wp_meta_sync_settings' );
}
