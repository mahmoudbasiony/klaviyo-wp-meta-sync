<?php
/**
 * Plugin Name: Klaviyo WP Meta Sync
 * Description: Seamlessly Sync Custom User Data from WordPress to Klaviyo.
 * Version: 1.0.0
 * Author: Mahmoud Basiony
 * Author URI: https://github.com/mahmoudbasiony
 * Requires at least: 5.4
 * Tested up to: 6.7
 *
 * Text Domain: klaviyo-wp-meta-sync
 * Domain Path: /languages/
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package Klaviyo_WP_Meta_Sync
 * @author Mahmoud Basiony
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/*
 * Globals constants.
 */
define( 'KLAVIYO_WP_META_SYNC_PLUGIN_NAME', 'Klaviyo WP Meta Sync' );
define( 'KLAVIYO_WP_META_SYNC_PLUGIN_VERSION', '1.0.0' );
define( 'KLAVIYO_WP_META_SYNC_MIN_PHP_VER', '7.3' );
define( 'KLAVIYO_WP_META_SYNC_MIN_WP_VER', '5.5' );
define( 'KLAVIYO_WP_META_SYNC_ROOT_PATH', __DIR__ );
define( 'KLAVIYO_WP_META_SYNC_ROOT_URL', plugin_dir_url( __FILE__ ) );
define( 'KLAVIYO_WP_META_SYNC_TEMPLATES_PATH', __DIR__ . '/templates/' );

if ( ! class_exists( 'Klaviyo_WP_Meta_Sync' ) ) :

	/**
	 * The main class.
	 *
	 * @since 1.0.0
	 */
	class Klaviyo_WP_Meta_Sync {
		/**
		 * Plugin version.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $version = '1.0.0';

		/**
		 * Database version.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private static $db_version = '1.0.0';

		/**
		 * The singelton instance of Klaviyo_WP_Meta_Sync.
		 *
		 * @since 1.0.0
		 *
		 * @var Klaviyo_WP_Meta_Sync
		 */
		private static $instance = null;

		/**
		 * Returns the singelton instance of Klaviyo_WP_Meta_Sync.
		 *
		 * Ensures only one instance of Klaviyo_WP_Meta_Sync is/can be loaded.
		 *
		 * @since 1.0.0
		 *
		 * @return Klaviyo_WP_Meta_Sync
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * The constructor.
		 *
		 * Private constructor to make sure it can not be called directly from outside the class.
		 *
		 * @since 1.0.0
		 */
		private function __construct() {
			$this->includes();
			$this->hooks();

			do_action( 'klaviyo_wp_meta_sync_loaded' );
		}

		/**
		 * Includes the required files.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function includes() {
			/*
			 * Global includes.
			 */
			include_once KLAVIYO_WP_META_SYNC_ROOT_PATH . '/includes/class-klaviyo-wp-meta-sync-utilities.php';
			include_once KLAVIYO_WP_META_SYNC_ROOT_PATH . '/includes/class-klaviyo-wp-meta-sync-usermeta.php';
			include_once KLAVIYO_WP_META_SYNC_ROOT_PATH . '/includes/class-klaviyo-wp-meta-sync-log.php';
			include_once KLAVIYO_WP_META_SYNC_ROOT_PATH . '/includes/class-klaviyo-wp-meta-sync-api-handler.php';

			/*
			 * Back-end includes.
			 */
			if ( is_admin() ) {
				include_once KLAVIYO_WP_META_SYNC_ROOT_PATH . '/includes/admin/class-klaviyo-wp-meta-sync-admin-notices.php';
				include_once KLAVIYO_WP_META_SYNC_ROOT_PATH . '/includes/admin/class-klaviyo-wp-meta-sync-admin-assets.php';
				include_once KLAVIYO_WP_META_SYNC_ROOT_PATH . '/includes/admin/class-klaviyo-wp-meta-sync-admin-settings.php';
			}
		}

		/**
		 * Plugin hooks.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function hooks() {
		}

		/**
		 * Activation hooks.
		 *
		 * @since   1.0.0
		 *
		 * @return void
		 */
		public static function activate() {
			/*
			 * Set default settings.
			 */
			$settings['email_notifications'] = 'off';
			$settings['email_addresses']     = '';

			add_option( 'klaviyo_wp_meta_sync_settings', $settings );
		}

		/**
		 * Deactivation hooks.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function deactivate() {
			// Nothing to do for now.
		}

		/**
		 * Uninstall hooks.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function uninstall() {
			include_once KLAVIYO_WP_META_SYNC_ROOT_PATH . 'uninstall.php';
		}
	}

	// Plugin hooks.
	register_activation_hook( __FILE__, array( 'Klaviyo_WP_Meta_Sync', 'activate' ) );
	register_deactivation_hook( __FILE__, array( 'Klaviyo_WP_Meta_Sync', 'deactivate' ) );
	register_uninstall_hook( __FILE__, array( 'Klaviyo_WP_Meta_Sync', 'uninstall' ) );

endif;

/**
 * Init plugin.
 *
 * @since 1.0.0
 */
function klaviyo_wp_meta_sync_init() {
	// Global for backwards compatibility.
	$GLOBALS['klaviyo_wp_meta_sync'] = Klaviyo_WP_Meta_Sync::get_instance();
}

add_action( 'plugins_loaded', 'klaviyo_wp_meta_sync_init', 0 );
