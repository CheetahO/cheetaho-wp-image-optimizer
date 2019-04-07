<?php

/**
 * The statistics of the plugin.
 *
 * @link       https://cheetaho.com
 * @since      1.4.3
 * @package    CheetahO
 * @subpackage CheetahO/admin
 * @author     CheetahO <support@cheetaho.com>
 */
class CheetahO_Stats {

	public static function get_stats() {
		$settings = get_option( '_cheetaho_options' );
		$data     = self::get_optimization_statistics( $settings );

		return array(
			'total_size_orig_images' => $data['unoptimizedSize'],
			'total_images'           => $data['optimizedImagesCount'],
			'total_size_images'      => $data['optimizedSize'],
			'total_perc_optimized'   => $data['totalPercOptimized'],
		);
	}

	public static function get_optimization_statistics( $settings, $result = null ) {
		if ( isset( $GLOBALS['cheetahoStats'] ) ) {
			return $GLOBALS['cheetahoStats'];
		}

		global $wpdb;

		if ( is_null( $result ) ) {
			// Select posts that have "_wp_attachment_metadata" image metadata
			$result = $wpdb->get_results(
				"SELECT
					$wpdb->posts.ID,
					$wpdb->posts.post_title,
					$wpdb->postmeta.meta_value,
					wp_postmeta_file.meta_value AS unique_attachment_name,
					wp_postmeta_cheetaho.meta_value AS cheetaho_meta_value,
					wp_postmeta_cheetaho_thumbs.meta_value AS cheetaho_thumbs_meta_value
				FROM $wpdb->posts
				LEFT JOIN $wpdb->postmeta
					ON $wpdb->posts.ID = $wpdb->postmeta.post_id
				LEFT JOIN $wpdb->postmeta AS wp_postmeta_file
					ON $wpdb->posts.ID = wp_postmeta_file.post_id
						AND wp_postmeta_file.meta_key = '_wp_attached_file'
				LEFT JOIN $wpdb->postmeta AS wp_postmeta_cheetaho
					ON $wpdb->posts.ID = wp_postmeta_cheetaho.post_id
						AND wp_postmeta_cheetaho.meta_key = '_cheetaho_size'
				LEFT JOIN $wpdb->postmeta AS wp_postmeta_cheetaho_thumbs
					ON $wpdb->posts.ID = wp_postmeta_cheetaho_thumbs.post_id
						AND wp_postmeta_cheetaho_thumbs.meta_key = '_cheetaho_thumbs'
				WHERE $wpdb->posts.post_type = 'attachment'
					AND (
						$wpdb->posts.post_mime_type = 'image/jpeg' OR
						$wpdb->posts.post_mime_type = 'image/png' OR
						$wpdb->posts.post_mime_type = 'image/gif'
					)
					AND $wpdb->postmeta.meta_key = '_wp_attachment_metadata'
				GROUP BY unique_attachment_name
				ORDER BY ID DESC",
				ARRAY_A
			);
		}

		$stats                                   = array();
		$stats['uploadedImages']                 = 0;
		$stats['optimizedImageSizes']            = 0;
		$stats['availableUnoptimisedSizesCount'] = 0;
		$stats['optimizedSize']                  = 0;
		$stats['unoptimizedSize']                = 0;
		$stats['optimizedImagesCount']           = 0;
		$stats['optimizedThumbsImagesCount']     = 0;
		$stats['availableForOptimization']       = array();
		$stats['thumbnail']                      = '';

		for ( $i = 0; $i < sizeof( $result ); $i++ ) {
			$wp_metadata              = @unserialize( $result[ $i ]['meta_value'] );
			$cheetaho_metadata        = @unserialize( $result[ $i ]['cheetaho_meta_value'] );
			$cheetaho_thumbs_metadata = @unserialize( $result[ $i ]['cheetaho_thumbs_meta_value'] );

			if ( ! is_array( $cheetaho_metadata ) ) {
				$cheetaho_metadata = array();
			}

			if ( ! is_array( $cheetaho_thumbs_metadata ) ) {
				$cheetaho_thumbs_metadata = array();
			}

			$image_stats = self::generate_stats(
				$result[ $i ]['ID'],
				$wp_metadata,
				$cheetaho_metadata,
				$cheetaho_thumbs_metadata,
				$settings
			);

			$stats['uploadedImages']++;
			$stats['availableUnoptimisedSizesCount'] += $image_stats['availableUnoptimisedSizesCount'];
			$stats['optimizedImageSizes']            += $image_stats['optimizedImageSizes'];
			$stats['optimizedThumbsImagesCount']     += $image_stats['optimizedThumbsImagesCount'];
			$stats['unoptimizedSize']                += $image_stats['originalSize'];
			$stats['optimizedSize']                  += $image_stats['cheetahoSize'];

			if ( count( $image_stats['availableForOptimization'] ) > 0 ) {
				$file = get_attached_file( $result[ $i ]['ID'] );
				$file = '/' . str_replace( get_home_path(), '', $file );

				if ( isset( $wp_metadata['sizes']['thumbnail']['file'] ) ) {
					$thumbnail = dirname( $file ) . '/' . $wp_metadata['sizes']['thumbnail']['file'];
				} else {
					$thumbnail = $file;
				}

				$stats['availableForOptimization'][] = array(
					'ID'        => $result[ $i ]['ID'],
					'title'     => $image_stats['title'],
					'thumbnail' => $thumbnail,
				);
			}
		}

		$stats['totalToOptimizeCount'] = count( $stats['availableForOptimization'] ) + $stats['availableUnoptimisedSizesCount'];
		$stats['optimizedImagesCount'] = $stats['uploadedImages'] - count( $stats['availableForOptimization'] ) + $stats['optimizedThumbsImagesCount'];
		$stats['totalPercOptimized']   = ( $stats['unoptimizedSize'] > 0 ) ? ceil( ( ( $stats['unoptimizedSize'] - $stats['optimizedSize'] ) / $stats['unoptimizedSize'] ) * 100 ) : 0;

		$GLOBALS['cheetahoStats'] = $stats;

		return $stats;
	}

	public static function generate_stats( $image_id, $wp_metadata, $cheetaho_metadata, $cheetaho_thumbs_metadata, $settings ) {
		$stats                                   = array();
		$stats['originalSize']                   = 0;
		$stats['cheetahoSize']                   = 0;
		$stats['availableUnoptimisedSizesCount'] = 0;
		$stats['optimizedImageSizes']            = 0;

		$thumbs_count = 0;
		if ( isset( $wp_metadata['sizes'] ) ) {
			$thumbs_count = count( $wp_metadata['sizes'] );
		}

		$allow_optimize_count = 0;

		if ( isset( $wp_metadata['sizes'] ) ) {
			foreach ( $wp_metadata['sizes'] as $key => $size ) {
				if ( isset( $settings[ 'include_size_' . $key ] ) && 1 == $settings[ 'include_size_' . $key ] ) {
					$allow_optimize_count++;
				}
			}

			$allow_optimize_count = $allow_optimize_count - count( $cheetaho_thumbs_metadata );

			if ( $allow_optimize_count < 0 ) {
				$allow_optimize_count = 0;
			}
		}

		$optimizedthumbs_count = count( $cheetaho_thumbs_metadata );

		$stats['availableUnoptimisedSizesCount'] = $allow_optimize_count;
		$stats['optimizedThumbsImagesCount']     = $optimizedthumbs_count;

		if ( isset( $cheetaho_metadata['newSize'] ) ) {
			$stats['cheetahoSize'] = $cheetaho_metadata['newSize'];
		}

		if ( isset( $cheetaho_metadata['originalImagesSize'] ) ) {
			$stats['originalSize'] = $cheetaho_metadata['originalImagesSize'];
		}

		foreach ( $cheetaho_thumbs_metadata as $cheetaho_thumb ) {
			$stats['cheetahoSize'] += $cheetaho_thumb['cheetaho_size'];
			$stats['originalSize'] += $cheetaho_thumb['original_size'];
		}

		$stats['availableForOptimization'] = array();
		if ( empty( $cheetaho_metadata ) || isset( $cheetaho_metadata['error'] ) ) {
			$stats['availableForOptimization'][] = $image_id;
		}

		$stats['title'] = get_the_title( $image_id );

		return $stats;
	}
}
