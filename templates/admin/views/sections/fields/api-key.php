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

<input type="password" id="api_key" name="klaviyo_wp_meta_sync_settings[klaviyo_api_key]" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" />
<p class="description"><?php esc_html_e( 'Enter your Klaviyo API key. It will be hidden for security.', 'klaviyo-wp-meta-sync' ); ?></p>