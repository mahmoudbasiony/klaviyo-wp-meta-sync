<?php
/**
 * Settings - Admin - Views.
 *
 * @package Klaviyo_WP_Meta_Sync/Templates/Admin/Views
 * @author Mahmoud Basiony.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

<div id="klaviyo_stop_sync_div" class="klaviyo_none wpcbl-is-syncing">
	<br>
	<h2 id="progress_message" class="klaviyo_success_div">
		<?php echo wp_kses_post( Klaviyo_WP_Meta_Sync_Utilities::get_loader_image_html() ); ?>
		<?php esc_html_e( 'Syncing user meta data to Klaviyo... Please hold on, this might take a few moments.', 'klaviyo-wp-meta-sync' ); ?>
		<br>
		<?php esc_html_e( 'Please do not close this page while the sync is in progress.', 'klaviyo-wp-meta-sync' ); ?>
	</h2>
	<br>
</div>
