<?php
/**
 * Settings.
 *
 * @package Klaviyo_WP_Meta_Sync/Templates/Admin
 * @author  Mahmoud Basiony.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Available tabs.
$plugin_tabs = array( 'general' );

// Current tab.
$plugin_tab = isset( $_GET['tab'] ) && in_array( $_GET['tab'], $plugin_tabs, true ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

?>

<div class="klaviyo-wp-meta-sync" id="klaviyo-wp-meta-sync">
	<nav class="nav-tab-wrapper nav-tab-wrapper">
		<a href="admin.php?page=klaviyo-wp-meta-sync&tab=general" class="nav-tab <?php echo 'general' === $plugin_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Settings', 'klaviyo-wp-meta-sync' ); ?></a>
	</nav>

	<div class="klaviyo-wp-meta-sync-inside-tabs">
		<div class="inside tab tab-content <?php echo esc_attr( $plugin_tab ); ?>" id="tab-<?php echo esc_attr( $plugin_tab ); ?>">
			<?php require_once 'settings-' . $plugin_tab . '.php'; ?>
		</div>
	</div>
</div>