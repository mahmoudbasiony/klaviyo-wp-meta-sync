<?php
/**
 * Settings - Admin - Views - Sections - Fields.
 *
 * @package Klaviyo_WP_Meta_Sync/Templates/Admin/Views/Sections/Fields
 * @author Mahmoud Basiony.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>

<input type="checkbox" id="email_notifications" name="klaviyo_wp_meta_sync_settings[email_notifications]" <?php checked( $email_notifications, 'on' ); ?>>
<label for="email_notifications"><?php esc_html_e( 'Enable email notifications', 'klaviyo-wp-meta-sync' ); ?></label>
