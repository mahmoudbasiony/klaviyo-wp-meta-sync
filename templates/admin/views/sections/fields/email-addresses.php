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

<div style="margin-top: 10px;">
	<input type="text" id="email_addresses" class="regular-text" name="klaviyo_wp_meta_sync_settings[email_addresses]" value="<?php echo esc_attr( $email_addresses ); ?>" <?php echo ( 'on' === $email_notifications ? '' : 'disabled' ); ?>>
	<p class="description"><?php esc_html_e( 'Enter email addresses, separated by commas.', 'klaviyo-wp-meta-sync' ); ?></p>
</div>
