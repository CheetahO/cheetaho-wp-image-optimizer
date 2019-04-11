<?php

/**
 * CheetahO helper functions for plugin.
 *
 * @link       https://cheetaho.com
 * @since      1.4.3
 * @package    CheetahO
 * @subpackage CheetahO/includes
 * @author     CheetahO <support@cheetaho.com>
 */
class CheetahO_Helpers {


	public static $allowed_extensions = array( 'jpg', 'jpeg', 'gif', 'png' );

	/**
	 * Get folder size
	 *
	 * @since      1.4.3
	 * @param string $path
	 * @return int
	 */
	public static function folder_size( $path = false ) {
		$total_size = 0;
		if ( file_exists( $path ) ) {
			$files = scandir( $path );
		} else {
			return $total_size;
		}
		$clean_path = rtrim( $path, '/' ) . '/';
		foreach ( $files as $t ) {
			if ( '.' <> $t && '..' <> $t ) {
				$current_file = $clean_path . $t;
				if ( is_dir( $current_file ) ) {
					$size        = self::folder_size( $current_file );
					$total_size += $size;
				} else {
					$size        = filesize( $current_file );
					$total_size += $size;
				}
			}
		}

		return $total_size;
	}

	/**
	 * Get max image size (widthxheight)
	 *
	 * @since      1.4.3
	 * @return array
	 */
	public static function get_max_intermediate_image_size() {
		global $_wp_additional_image_sizes;

		$width                        = 0;
		$height                       = 0;
		$get_intermediate_image_sizes = get_intermediate_image_sizes();

		// Create the full array with sizes and crop info
		if ( is_array( $get_intermediate_image_sizes ) ) {
			foreach ( $get_intermediate_image_sizes as $_size ) {
				if ( in_array(
					$_size,
					array(
						'thumbnail',
						'medium',
						'large',
					)
				) ) {
					$width  = max( $width, get_option( $_size . '_size_w' ) );
					$height = max( $height, get_option( $_size . '_size_h' ) );
				} elseif ( isset( $_wp_additional_image_sizes [ $_size ] ) ) {
					$width  = max( $width, $_wp_additional_image_sizes [ $_size ] ['width'] );
					$height = max( $height, $_wp_additional_image_sizes [ $_size ] ['height'] );
				}
			}
		}

		return array(
			'width'  => max( 250, $width ),
			'height' => max( 250, $height ),
		);
	}

	/**
	 * Convert to kb format
	 *
	 * @since      1.4.3
	 * @param int $bytes
	 * @return string
	 */
	static function convert_to_kb( $bytes ) {
		return round( ( $bytes / 1024 ), 2 ) . ' kB';
	}


	/**
	 * @since 1.4.3
	 * @param $path
	 * @return mixed|string
	 */
	public static function get_base_name( $path ) {
		$separator = ' qq ';
		$path      = preg_replace( '/[^ ]/u', $separator . '$0' . $separator, $path );
		$base      = basename( $path );
		$base      = str_replace( $separator, '', $base );

		return $base;
	}

	/**
	 * @since 1.4.3
	 * @param $original_image_path
	 * @return array
	 */
	static function get_image_paths( $original_image_path ) {
		$full_sub_dir = str_replace( get_home_path(), '', dirname( $original_image_path ) ) . '/';
		$backup_file  = CHEETAHO_BACKUP_FOLDER . '/' . $full_sub_dir . self::get_base_name( $original_image_path );

		return array(
			'backup_file'         => $backup_file,
			'original_image_path' => $original_image_path,
			'full_sub_dir'        => $full_sub_dir,
		);
	}

	/**
	 * @since 1.4.3
	 * @param $file
	 * @return bool
	 */
	public static function set_file_perms( $file ) {
		$perms = @fileperms( $file );
		if ( ! ( $perms & 0x0100 ) || ! ( $perms & 0x0080 ) ) {
			if ( ! @chmod( $file, $perms | 0x0100 | 0x0080 ) ) {
				return false;
			}
		}
		return true;
	}


	/**
	 * @since 1.4.3
	 * @param $bk_file
	 * @param $file
	 */
	public static function rename_file( $bk_file, $file ) {
		@rename( $bk_file, $file );
		@rename( CheetahO_Retina::get_retina_name( $bk_file ), CheetahO_Retina::get_retina_name( $file ) );
	}

	/**
	 * Reset image metadata
	 *
	 * @param int   $image_id
	 * @param array $cheetaho_data
	 */
	public static function reset_image_size_meta( $image_id, $cheetaho_data ) {
		$image_data = wp_get_attachment_metadata( $image_id );

		if ( $image_data['width'] < $cheetaho_data['meta']['width'] || $image_data['height'] < $cheetaho_data['meta']['height'] ) {
			$image_data['width']  = $cheetaho_data['meta']['width'];
			$image_data['height'] = $cheetaho_data['meta']['height'];

			wp_update_attachment_metadata( $image_id, $image_data );
		}
	}

	/**
	 * @since 1.4.3
	 * @param $path
	 * @return bool
	 */
	public static function is_processable_path( $path ) {
		$path_parts = pathinfo( $path );

		if ( isset( $path_parts['extension'] ) && in_array( strtolower( $path_parts['extension'] ), self::$allowed_extensions ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Show results after optimization
	 *
	 * @param int $id
	 * @return string
	 */
	public static function output_result( $id ) {
		$image_meta    = get_post_meta( $id, '_cheetaho_size', true );
		$thumbs_meta   = get_post_meta( $id, '_cheetaho_thumbs', true );
		$cheetaho_size = self::convert_to_kb( $image_meta['newSize'] );
		$type          = $image_meta['type'];
		$thumbs_count  = count( $thumbs_meta );

		$retina_count = ( isset( $image_meta['retina'] ) && ! empty( $image_meta['retina'] ) ) ? 1 : 0;

		if ( is_array( $thumbs_meta ) ) {
			foreach ( $thumbs_meta as $item ) {
				if ( isset( $item['retina'] ) && ! empty( $item['retina'] ) ) {
					$retina_count++;
				}
			}
		}

		if ( isset( $image_meta['originalImagesSize'] ) ) {
			$original_size = self::convert_to_kb( $image_meta['originalImagesSize'] );
		} else {
			$file = get_attached_file( $id );

			$original_size = ( file_exists( $file ) != false ) ? filesize( $file ) : 0;
			$original_size = self::convert_to_kb( $original_size );
		}
		$saved_bytes        = $image_meta['originalImagesSize'] - $image_meta['newSize'];
		$savings_percentage = round( $saved_bytes / (int) $image_meta['originalImagesSize'] * 100, 2 ) . '%';

		include CHEETAHO_PLUGIN_ROOT . 'admin/views/parts/column-results.php';
		$image_meta['html'] = $html;

		return json_encode( $image_meta );
	}

	/**
	 * @since 1.4.3
	 * @param $settings
	 * @return array
	 */
	public static function get_not_optimized_images_ids( $settings ) {
		$data = CheetahO_Stats::get_optimization_statistics( $settings );

		return array(
			'uploadedImages'  => $data['totalToOptimizeCount'],
			'uploaded_images' => $data['availableForOptimization'],
		);
	}

	/**
	 * @since 1.4.3
	 * @param $id
	 * @return bool
	 */
	public static function is_processable( $id ) {
		$path = get_attached_file( $id );// get the full file PATH
		return self::is_processable_path( $path );
	}

	/**
	 * @since 1.4.3
	 * @param $id
	 */
	public static function handle_delete_attachment_in_backup( $id ) {
		$file = get_attached_file( $id );
		$meta = wp_get_attachment_metadata( $id );

		if ( self::is_processable( $id ) != false ) {
			try {
				$paths   = self::get_image_paths( $file );
				$bk_file = $paths['backup_file'];

				if ( file_exists( $bk_file ) ) {
					@unlink( $bk_file );
				}

				if ( ! empty( $meta['file'] ) ) {

					// remove thumbs thumbnails
					if ( isset( $meta['sizes'] ) ) {
						foreach ( $meta['sizes'] as $size => $image_data ) {
							$original_image_path = dirname( $file ) . '/' . $image_data['file'];
							$paths_thumb         = self::get_image_paths( $original_image_path );

							if ( file_exists( $paths_thumb['backup_file'] ) ) {
								@unlink( $paths_thumb['backup_file'] );// remove thumbs
							}
						}
					}
				}

				$backup_file = CHEETAHO_BACKUP_FOLDER . '/' . $paths['full_sub_dir'];
				@rmdir( $backup_file );
			} catch ( Exception $e ) {
				// what to do, what to do?
			}
		}
	}

	/**
	 * @since 1.4.3
	 * @param $id
	 */
	public static function handle_delete_attachment_web_p( $id ) {
		$file = get_attached_file( $id );

		if ( self::is_processable( $id ) != false ) {
			try {
				$paths = self::get_image_paths( $file );

				$backup_file = CHEETAHO_BACKUP_FOLDER . '/' . $paths['full_sub_dir'];
				@rmdir( $backup_file );
			} catch ( Exception $e ) {
				// what to do, what to do?
			}
		}
	}
}
