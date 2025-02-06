<?php
/**
 * Settings - General.
 *
 * @var array $settings - The plugin settings array
 *
 * @package Klaviyo_WP_Meta_Sync/Templates/Admin
 * @author Mahmoud Basiony.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// General settings.
$settings = get_option( 'klaviyo_wp_meta_sync_settings', array() );
?>

	<div class="klaviyo-wp-meta-sync-general wrap">
		<form action="options.php" method="post">
			<?php
			settings_fields( 'klaviyo_wp_meta_sync_settings' );
			do_settings_sections( 'klaviyo-wp-meta-sync' );
			submit_button( 'Save Settings' );
			?>
		</form>
	</div>
