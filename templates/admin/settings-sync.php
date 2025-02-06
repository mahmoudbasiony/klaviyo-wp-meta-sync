<?php
/**
 * Settings - Scan.
 *
 * @package Klaviyo_WP_Meta_Sync/Templates/Admin
 * @author Mahmoud Basiony.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>

<div class="klaviyo-wp-meta-sync-sync section" id="klaviyo-wp-meta-sync-sync">
	<div class="klaviyo-wp-meta-sync-sync-header-wrap">
		<div class="klaviyo-wp-meta-sync-sync-headers">
			<div class="klaviyo-wp-meta-sync-headline">
				<h2 class="headline"><?php esc_html_e( 'Perform a sync for Custom meta on your WordPress site.', 'klaviyo-wp-meta-sync' ); ?></h2>
				<p><?php esc_html_e( ' by simply clicking on the sync button.', 'klaviyo-wp-meta-sync' ); ?></p>
			</div>

			<div class="klaviyo-wp-meta-sync-sync-actions">
				<input type="button" class="button button-primary" id="manual-sync" value="<?php esc_html_e( 'Start Manual Sync', 'klaviyo-wp-meta-sync' ); ?>" />
			</div>
		</div>
	</div>
	<div class="klaviyo-wp-meta-sync-sync-wrap">
		<!-- The loader -->
		<?php require_once KLAVIYO_WP_META_SYNC_TEMPLATES_PATH . 'admin/views/loader.php'; ?>
	</div>
</div>
