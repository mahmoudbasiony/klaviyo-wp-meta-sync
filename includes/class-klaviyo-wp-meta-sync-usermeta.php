<?php
/**
 * The Klaviyo_WP_Meta_Sync_Admin_Usermeta class.
 *
 * @package Klaviyo_WP_Meta_Sync/Admin
 * @author Mahmoud Basiony.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Klaviyo_WP_Meta_Sync_Admin_Usermeta' ) ) :
	/**
	 * Usermeta class.
	 *
	 * Sync usermeta with Klaviyo.
	 *
	 * @since 1.0.0
	 */
	class Klaviyo_WP_Meta_Sync_Admin_Usermeta {
		/**
		 * The settings.
		 *
		 * @var array
		 */
		private $settings;

		/**
		 * Meta fields to sync with klaviyo.
		 *
		 * @var array
		 */
		private static $meta_data;

		/**
		 * The constructor.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function __construct() {
			add_action( 'added_user_meta', array( $this, 'handle_user_meta' ), 10, 4 );
			add_action( 'updated_user_meta', array( $this, 'handle_user_meta' ), 10, 4 );

            self::metadata_to_sync();

			add_filter( 'bulk_actions-users', array( self::class, 'add_custom_bulk_action' ) );
			add_filter( 'handle_bulk_actions-users', array( self::class, 'handle_bulk_action' ), 10, 3 );

			add_action( 'klaviyo_sync_batch_event', array( self::class, 'handle_batch_event' ) );
			add_action( 'manage_users_extra_tablenav', array( self::class, 'add_sync_all_users_extra_tablenav' ), 10, 1 );
			add_action( 'admin_init', array( self::class, 'handle_sync_all_users_action' ) );
			add_action( 'admin_notices', array( self::class, 'bulk_actions_admin_notice' ) );
			add_action( 'wp_ajax_bulk_sync_progress', array( self::class, 'bulk_sync_progress_callback' ) );
		}

		/**
		 * Metadata to sync.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function metadata_to_sync() {
			self::$meta_data = array(
				'isEmailVerified',
				'completed_array_verification',
			);
		}

		/**
		 * Format data to sync.
		 *
		 * @param string $email      User email.
		 * @param array  $properties User properties.
		 *
		 * @since 1.0.0
		 *
		 * @return array
		 */
		public static function format_data_to_sync( $email, $properties ) {
			$data = array(
				'data' => array(
					'type'       => 'profile',
					'attributes' => array(
						'email'      => $email,
						'properties' => $properties,
					),
				),
			);

			return $data;
		}

		/**
		 * Handle user meta.
		 *
		 * @since 1.0.0
		 *
		 * @param int    $meta_id    Meta ID.
		 * @param int    $user_id    User ID.
		 * @param string $meta_key   Meta key.
		 * @param mixed  $meta_value Meta value.
		 *
		 * @return void
		 */
		public function handle_user_meta( $meta_id, $user_id, $meta_key, $meta_value ) {
			$this->settings = get_option( 'klaviyo_wp_meta_sync_settings', array() );

			if ( in_array( $meta_key, self::$meta_data ) ) {
				$user_date  = get_userdata( $user_id );
				$email      = $user_date->user_email;
				$properties = array(
					$meta_key => $meta_value,
				);

				$formatted_data = self::format_data_to_sync( $email, $properties );

				$klaviyo = Klaviyo_WP_Meta_Sync_Admin_API_Handler::get_instance();

				$response = $klaviyo->create_or_update_profile( $formatted_data );
			}
		}

		/**
		 * Handle the bulk action "Sync to Klaviyo".
		 *
		 * @param string $redirect_to URL to redirect after the action.
		 * @param string $doaction The action being performed.
		 * @param array  $user_ids The IDs of the users to sync.
		 *
		 * @since 1.0.0
		 *
		 * @return string
		 */
		public static function handle_bulk_action( $redirect_to, $doaction, $user_ids ) {
			if ( 'sync_to_klaviyo' !== $doaction ) {
				return $redirect_to;
			}

			$transient_key = 'klaviyo_wp_meta_sync_bulk_user_ids';

			// Set posts to "processing" state.
			self::set_users_to_processing_state( $user_ids );

			// Initialize transient with post IDs and processing status.
			$transient_data = array_fill_keys( $user_ids, 'processing' );
			set_transient( $transient_key, $transient_data, HOUR_IN_SECONDS );

			// Divide users into manageable batches.
			$batches = array_chunk( $user_ids, 50 ); // Process 50 users per batch.

			foreach ( $batches as $batch_index => $batch_user_ids ) {
				$batch_id = uniqid( 'klaviyo_sync_batch_' );

				foreach ( $batch_user_ids as $user_id ) {
					update_user_meta( $user_id, '_klaviyo_sync_batch', $batch_id );
				}

				// Schedule the batch event.
				wp_schedule_single_event( time() + ( $batch_index * 60 ), 'klaviyo_sync_batch_event', array( $batch_id ) );
			}

			// Generate the nonce.
			$nonce = wp_create_nonce( 'bulk_sync_nonce' );

			// Attach the nonce and klaviyo_bulk_sync as query arguments.
			return add_query_arg(
				array(
					'klaviyo_bulk_sync' => count( $user_ids ),
					'bulk_sync_nonce'   => $nonce,
				),
				$redirect_to
			);
		}

		/**
		 * Process a batch of users for klaviyo sync.
		 *
		 * @param string $batch_id The unique batch ID to process.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function handle_batch_event( $batch_id ) {
			$transient_key = 'klaviyo_wp_meta_sync_bulk_user_ids';
			$user_ids      = get_transient( $transient_key );

			if ( ! is_array( $user_ids ) ) {
				return;
			}

			$query = new WP_User_Query(
				array(
					'meta_key'   => '_klaviyo_sync_batch',
					'meta_value' => $batch_id,
					'number'     => -1,
				)
			);

			$users = $query->get_results();

			foreach ( $users as $user ) {
				$user_id = $user->ID;

				if ( isset( $user_ids[ $user_id ] ) && 'completed' === $user_ids[ $user_id ] ) {
					continue;
				}

				$status = get_user_meta( $user_id, '_klaviyo_sync_status', true );

				if ( 'processing' === $status ) {
					$sync_result = self::run_sync_per_user( $user_id, $user );

					if ( $sync_result && isset( $sync_result['status'] ) ) {
						update_user_meta( $user_id, '_klaviyo_sync_status', $sync_result['status'] );
						$user_ids[ $user_id ] = $sync_result['status'];
					}
				}
			}

			set_transient( $transient_key, $user_ids, HOUR_IN_SECONDS );
		}

		/**
		 * Run sync per user.
		 *
		 * @param int     $user_id User ID.
		 * @param WP_User $user    User object.
		 *
		 * @since 1.0.0
		 *
		 * @return bool True if sync successful, false otherwise.
		 */
		public static function run_sync_per_user( $user_id, $user ) {
			$properties = array();

			// Get all user meta for the user (faster than multiple calls to get_user_meta).
			$user_meta = get_metadata_raw( 'user', $user_id );
			$email     = $user->user_email;
			// Loop through each meta key in $this->meta_data.
			foreach ( self::$meta_data as $meta_key ) {
				if ( array_key_exists( $meta_key, $user_meta ) ) {
					$meta_value = get_user_meta( $user_id, $meta_key, true );

					// Add key even if value is empty.
					$properties[ $meta_key ] = $meta_value;
				}
			}

			// If no matching meta keys found, skip this user.
			if ( empty( $properties ) ) {
				return array(
					'status' => 'skipped',
				);
			}

			$formatted_data = self::format_data_to_sync( $email, $properties );

			$klaviyo = Klaviyo_WP_Meta_Sync_Admin_API_Handler::get_instance();

			$response = $klaviyo->create_or_update_profile( $formatted_data );

			if ( ! $response ) {
				return array(
					'status' => 'failed',
				);
			}
			return array(
				'status' => 'completed',
			);
		}

		/**
		 * Set all users to "processing" state efficiently.
		 *
		 * @param array $user_ids Array of user IDs to update.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function set_users_to_processing_state( $user_ids ) {
			global $wpdb;

			if ( empty( $user_ids ) || ! is_array( $user_ids ) ) {
				return;
			}

			// Sanitize post IDs.
			$user_ids = array_map( 'absint', $user_ids );

			// Step 1: Delete existing meta keys for these users.
			$cache_key = 'klaviyo_usermeta_processing';
			wp_cache_set( $cache_key, $user_ids, 'klaviyo_wp_meta_sync', HOUR_IN_SECONDS );

			$placeholders  = implode( ', ', array_fill( 0, count( $user_ids ), '%d' ) );
			$delete_query  = "DELETE FROM {$wpdb->usermeta} WHERE meta_key = %s AND user_id IN ($placeholders)";
			$delete_params = array_merge( array( '_klaviyo_sync_status' ), $user_ids );

			// Run the delete query with $wpdb->query().
			$wpdb->query( $wpdb->prepare( $delete_query, ...$delete_params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

			// Step 2: Insert new meta keys with "processing" state.
			$insert_placeholders = array();
			$insert_values       = array();
			foreach ( $user_ids as $user_id ) {
				$insert_placeholders[] = '(%d, %s, %s)';
				$insert_values[]       = $user_id;
				$insert_values[]       = '_klaviyo_sync_status';
				$insert_values[]       = 'processing';
			}

			if ( ! empty( $insert_placeholders ) ) {
				$insert_query = "INSERT INTO {$wpdb->usermeta} (user_id, meta_key, meta_value) VALUES " . implode( ', ', $insert_placeholders );
				$wpdb->query( $wpdb->prepare( $insert_query, ...$insert_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
			}

			// Clear the cache after processing.
			wp_cache_delete( $cache_key, 'klaviyo_wp_meta_sync' );
		}

		/**
		 * Add custom bulk action.
		 *
		 * @param array $bulk_actions Bulk actions.
		 *
		 * @since 1.0.0
		 *
		 * @return array
		 */
		public static function add_custom_bulk_action( $bulk_actions ) {
			$bulk_actions['sync_to_klaviyo'] = __( 'Sync to Klaviyo', 'klaviyo-wp-meta-sync' );
			return $bulk_actions;
		}

		/**
		 * Add additional button to users list screens - in the same row like bulk actions select and filter
		 *
		 * @param string $which string The position of the extra table nav markup: 'top' or 'bottom'.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function add_sync_all_users_extra_tablenav( $which ) {
			if ( 'top' === $which ) {
				$button_text = __( 'Klaviyo Sync All Users', 'klaviyo-wp-meta-sync' );
				$nonce       = wp_create_nonce( 'sync_all_users_nonce' );

				echo '<div class="alignleft actions">';
				echo '<input type="submit" name="sync_all_users" class="button action" value="' . esc_attr( $button_text ) . '">';
				echo '<input type="hidden" name="_sync_all_users_nonce" value="' . esc_attr( $nonce ) . '">';
				echo '</div>';
			}
		}

		/**
		 * Handle the "Sync All Users" action for all users.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function handle_sync_all_users_action() {
			if ( isset( $_REQUEST['sync_all_users'] ) && ! isset( $_REQUEST['taxonomy'] ) ) {
				// Verify the nonce for security.
				if ( ! isset( $_REQUEST['_sync_all_users_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_sync_all_users_nonce'] ) ), 'sync_all_users_nonce' ) ) {
					wp_die( esc_html__( 'Security check failed. Please try again.', 'klaviyo-wp-meta-sync' ) );
				}

				// Check if the user has the capability to edit posts.
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'klaviyo-wp-meta-sync' ) );
				}

				// Query all users.
				$query = new WP_User_Query(
					array(
						'number' => -1,
					)
				);

				$users = $query->get_results();

				$user_ids = wp_list_pluck( $users, 'ID' );

				// Redirect URL for the bulk action.
				$redirect_url = admin_url( 'users.php' );

				// Schedule posts for a scan and append query arg for bulk scans.
				$redirect_url = static::handle_bulk_action( $redirect_url, 'sync_to_klaviyo', $user_ids );

				// Safely redirect to the new URL.
				wp_safe_redirect( esc_url_raw( $redirect_url ) );
				exit;
			}
		}

		/**
		 * Display an admin notice after performing a bulk action.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function bulk_actions_admin_notice() {
			if ( isset( $_REQUEST['klaviyo_bulk_sync'], $_REQUEST['bulk_sync_nonce'] ) ) {
				// Verify the nonce to ensure the request is secure.
				check_admin_referer( 'bulk_sync_nonce', 'bulk_sync_nonce' );

				// Check if the user has the capability to manage options.
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'klaviyo-wp-meta-sync' ) );
				}

				$total_users = intval( $_REQUEST['klaviyo_bulk_sync'] );
				echo '<div id="bulk-sync-notice" class="updated notice is-dismissible">';
				echo '<p><strong>' . esc_html__( 'Klaviyo syncing in progress...', 'klaviyo-wp-meta-sync' ) . '</strong></p>';
				printf(
					'<p id="bulk-sync-progress"><strong>%s</strong></p>',
					esc_html__( 'Initializing Sync...', 'klaviyo-wp-meta-sync' )
				);
				echo '</div>';
			}
		}

		/**
		 * AJAX callback for bulk scan progress.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function bulk_sync_progress_callback() {

			check_ajax_referer( 'bulk_sync_nonce', 'bulk_sync_nonce' );

			// Check if the user has the capability to manage options.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => 'You do not have sufficient permissions to access this page.' ) );
			}

			$transient_key = 'klaviyo_wp_meta_sync_bulk_user_ids';
			$user_ids      = get_transient( $transient_key );

			if ( ! is_array( $user_ids ) ) {
				wp_send_json_error( array( 'message' => 'No users in progress.' ) );
			}

			$completed_users  = array();
			$skipped_users    = array();
			$failed_users     = array();
			$processing_count = 0;
			$all_processed    = true;

			foreach ( $user_ids as $user_id => $status ) {
				if ( 'completed' === $status ) {
					$completed_users[] = array(
						'user_id'     => $user_id,
						'sync_result' => '',
					);
				} elseif ( 'skipped' === $status ) {
					$skipped_users[] = array(
						'user_id'       => $user_id,
						'error_message' => __( 'No matching meta keys found.', 'klaviyo-wp-meta-sync' ),
					);
				} elseif ( 'failed' === $status ) {
					$failed_users[] = array(
						'user_id'       => $user_id,
						'error_message' => __( 'Failed to sync!.', 'klaviyo-wp-meta-sync' ),
					);
				} elseif ( 'processing' === $status ) {
					++$processing_count;
					$all_processed = false;
				}
			}

			$total_users     = count( $user_ids );
			$remaining_users = $processing_count;

			// Delete the transient if all posts are processed.
			if ( $all_processed ) {
				delete_transient( $transient_key );
			}

			wp_send_json_success(
				array(
					'total_users'     => $total_users,
					'completed_users' => $completed_users,
					'skipped_users'   => $skipped_users,
					'failed_users'    => $failed_users,
					'remaining_users' => $remaining_users,
				)
			);
		}
	}

	new Klaviyo_WP_Meta_Sync_Admin_Usermeta();

endif;
