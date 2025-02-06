<?php
/**
 * The Klaviyo_WP_Meta_Sync_Utilities class.
 *
 * @package Klaviyo_WP_Meta_Sync
 * @author Mahmoud Basiony
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Klaviyo_WP_Meta_Sync_Utilities' ) ) {
	/**
	 * Utilities.
	 *
	 * @since 1.0.0
	 */
	class Klaviyo_WP_Meta_Sync_Utilities {
		/**
		 * Register the loader image to the media library.
		 *
		 * @param string $path The path to the image.
		 *
		 * @since 1.0.0
		 *
		 * @return integer|boolean
		 */
		public static function register_loader_image( $path ) {
			$upload_dir = wp_upload_dir();
			$full_path  = trailingslashit( KLAVIYO_WP_META_SYNC_ROOT_PATH . '/assets/dist/images/' ) . $path;

			// Ensure the file exists.
			if ( ! file_exists( $full_path ) ) {
				return false;
			}

			// Check file type.
			$file_type = wp_check_filetype( basename( $full_path ) );

			// Check if the image is already registered.
			$args = array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => '_klaviyo_loader',
						'value'   => $path,
						'compare' => '=',
					),
				),
				'posts_per_page' => 1,
			);

			$query = new WP_Query( $args );
			if ( $query->have_posts() ) {
				return $query->posts[0]->ID;
			}

			// Copy the image to the uploads directory.
			$destination_path = trailingslashit( $upload_dir['path'] ) . basename( $full_path );
			if ( ! copy( $full_path, $destination_path ) ) {
				return false;
			}

			// Create the attachment.
			$attachment = array(
				'guid'           => trailingslashit( $upload_dir['url'] ) . basename( $full_path ),
				'post_mime_type' => $file_type['type'],
				'post_title'     => sanitize_file_name( basename( $full_path ) ),
				'post_status'    => 'inherit',
			);

			$attach_id = wp_insert_attachment( $attachment, $destination_path );
			if ( ! is_wp_error( $attach_id ) ) {
				require_once ABSPATH . 'wp-admin/includes/image.php';
				wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $destination_path ) );
				update_post_meta( $attach_id, '_klaviyo_loader', $path );
				return $attach_id;
			}

			return false;
		}

		/**
		 * Get the loader image HTML.
		 *
		 * @since 1.0.0
		 *
		 * @return string
		 */
		public static function get_loader_image_html() {
			$attachment_id = self::register_loader_image( 'loader.gif' );

			if ( $attachment_id ) {
				return wp_get_attachment_image(
					$attachment_id,
					array( 30, 30 ),
					false,
					array(
						'class' => 'klaviyo_loader_margin',
						'alt'   => __( 'Klaviyo WP Meta Sync loader', 'cklaviyo-wp-meta-sync' ),
					)
				);
			}

			// Fallback to the URL -- added an empty string for now.
			return '';
		}
	}
}
