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
			'local_path'          => self::get_abs_path($full_sub_dir . self::get_base_name( $original_image_path ))
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
	 * @param $temp_image_file
	 * @param $image_path
	 */
	public static function rename_file( $temp_image_file, $image_path ) {
		clearstatcache();
		$perms = array();

		if ( file_exists( $image_path ) ) {
			$perms = fileperms( $image_path ) & 0777;
		}

		// Replace the file.
		$success = @rename( $temp_image_file, $image_path );

		// If file renaming failed.
		if ( ! $success ) {
			@copy( $temp_image_file, $image_path );
		}

		// If tempfile still exists, unlink it.
		if ( file_exists( $temp_image_file ) ) {
			@unlink( $temp_image_file );
		}

		// Some servers are having issue with file permission, this should fix it.
		if ( empty( $perms ) || ! $perms ) {
			// Source: WordPress Core.
			$stat  = stat( dirname( $image_path ) );
			$perms = $stat['mode'] & 0000666; // Same permissions as parent folder, strip off the executable bits.
		}

		@chmod( $image_path, $perms );

		return true;
	}

	/**
	 * Reset image metadata
	 *
	 * @param int   $image_id
	 * @param array $cheetaho_data
	 */
	public static function reset_image_size_meta( $image_id, $cheetaho_data ) {
		$image_data = wp_get_attachment_metadata( $image_id );

		if ( $image_data['width'] < $cheetaho_data->width || $image_data['height'] < $cheetaho_data->height ) {
			$image_data['width']  = $cheetaho_data->width;
			$image_data['height'] = $cheetaho_data->height;

			wp_update_attachment_metadata( $image_id, $image_data );
		}
	}

	/**
	 * @since 1.4.3
	 * @param $path
	 * @return bool
	 */
	public static function is_processable_path( $path, $exclude_files_patterns = array() ) {
		$path_parts = pathinfo( $path );

        // Check if the path is valid.
        if (!file_exists($path)) {
            return false;
        }

        // Check if the path is valid.
        if (!self::is_correct_file($path)) {
            return false;
        }

        // Used in place of Skip Dots, For php 5.2 compatibility.
        if (basename($path) === '..' || basename($path) === '.') {
            return false;
        }

        if (strpos($path, '.bak') !== false) {
            return false;
        }

		if ( isset( $path_parts['extension'] ) && self::is_image_from_extension($path) && self::is_excluded_file($path, $exclude_files_patterns) === false) {
			return true;
		} else {
			return false;
		}
	}

    /**
     * @since 1.5
     * @param $path
     * @param array $exclude_files_patterns
     * @return bool
     */
	public static function is_excluded_file($path, $exclude_files_patterns = array())
    {
        if(!$exclude_files_patterns || !is_array($exclude_files_patterns)) {
            return false;
        }

        foreach($exclude_files_patterns as $item) {
            if (isset($item["type"])) {
                $type = trim($item["type"]);

                if (in_array($type, array("name", "path"))) {
                    $pattern = trim($item["value"]);
                    $target = $type == "name" ? self::get_base_name($path) : $path;

                    if (self::match_exclude_pattern($target, $pattern)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @since 1.5
     * @param $path
     * @return bool
     */
    public static function is_image_from_extension($path)
    {
        $supported_image = self::$allowed_extensions;
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($ext, $supported_image, true)) {
            return true;
        }

        return false;
    }

    /**
     * Check whether the given path is a image or not.
     *
     * Do not include backup files.
     *
     * @param  string  $path  Image path.
     *
     * @return bool
     */
    private static function is_correct_file($path)
    {
        if (false !== stripos($path, 'phar://')) {
            return false;
        }

        $a = @getimagesize($path);

        // If a is not set.
        if (!$a || empty($a)) {
            return false;
        }

        $image_type = $a[2];

        if (in_array($image_type, array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG))) {
            return true;
        }

        return false;
    }

    /**
     * @since 1.5
     * @param $settings
     * @return array
     */
	static public function convert_exclude_file_patterns_to_array($settings)
    {
        $items = [];

        if (isset($settings[CheetahO_Settings::EXCLUDE_FILES_BY_PATTERN])){
            $data = explode(',', $settings[CheetahO_Settings::EXCLUDE_FILES_BY_PATTERN]);

            foreach ($data as $item) {
                $patternData = explode(':', $item);

                if (isset($patternData[0]) && isset($patternData[1])) {
                    $items[] = [
                        'type' => $patternData[0],
                        'value' => $patternData[1],
                    ];
                }
            }
        }

        return $items;
    }

    /**
     * @since 1.5
     * @param $target
     * @param $pattern
     * @return bool
     */
    static public function match_exclude_pattern($target, $pattern) {
        if(strlen($pattern) == 0) {
            return false;
        }

        $first = substr($pattern, 0,1);

        if ($first == '/')
        {
            if (@preg_match($pattern, false) !== false)
            {
                $m = preg_match($pattern,  $target);
                if ($m !== false && $m > 0)
                {
                    return true;
                }
            }
        } else {
            return strpos($target, $pattern) !== false;
        }

        return false;
     }

	/**
	 * Show results after optimization
	 *
	 * @param int $id
	 * @return string
	 */
	public static function output_result( $meta, $has_cheetaho_meta = false ) {

		$attachment_id = ( isset( $meta['attachment_id'] ) ) ? $meta['attachment_id'] : null;
		$image_size = ( isset( $meta['image_size'] ) ) ? $meta['image_size']  : 0;
        $cheetaho_size = self::convert_to_kb( $image_size );
		$type          = ( isset( $meta['level'] ) ) ? $meta['level'] : '';
		$thumbs_count  = ( isset( $meta['thumbs_images_count'] ) ) ? $meta['thumbs_images_count'] : 0;
		$retina_count  = ( isset( $meta['retina_images_count'] ) ) ? $meta['retina_images_count'] : 0;
        $original_images_size = ( isset( $meta['original_images_size'] ) ) ?  $meta['original_images_size'] : 0;
        $original_size = self::convert_to_kb($original_images_size);
		$savings_percentage = (( isset( $meta['savings_percentage'] ) ) ? $meta['savings_percentage'] : 0);
		$image_meta['original_size_front'] = $original_size;
		$image_meta['cheetaho_size_front'] = $cheetaho_size;
		$image_meta['saved_bytes_front'] = self::convert_to_kb(round($original_images_size - $image_size, 2));

		if ($has_cheetaho_meta == false) {
			include CHEETAHO_PLUGIN_ROOT . 'admin/views/parts/column-results.php';
		} else {
			include CHEETAHO_PLUGIN_ROOT . 'admin/views/parts/column-results-old.php';
		}

		$image_meta['html'] = $html;

		$original_size = ( isset( $meta['original_images_size'] ) ) ? $meta['original_images_size'] : 0;
		$cheetaho_size = ( isset( $meta['image_size'] ) ) ? $meta['image_size'] : 0;

		$image_meta['success'] = true;
		$image_meta['optimized_image'] = 1;
		$image_meta['original_size'] = $original_size;
		$image_meta['cheetaho_size'] = $cheetaho_size;
		$image_meta['saved_percent'] = $savings_percentage;
		$image_meta['saved_bytes'] = round($original_images_size - $image_size, 2);
		$image_meta['optimized_images'] = $thumbs_count + $retina_count + 1;

		return json_encode( $image_meta );
	}

	/**
	 * @since 1.4.3
	 * @param $id
	 * @return bool
	 */
	public static function is_processable( $id ) {
		$path = get_attached_file( $id );// get the full file PATH

		return self::is_processable_path( $path, array() );
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

    /**
     * @param $settings
     * @return bool
     * @since 1.4.3
     */
	public static function can_do_backup ($settings)
	{
		if ( isset( $settings['backup'] ) && 1 == $settings['backup'] || ! isset( $settings['backup'] ) ) {
			return true;
		}

		return false;
	}

    /**
     * @param $settings
     * @return string
     * @since 1.4.3
     */
	public static function get_abs_path ($img_path)
	{
		return ABSPATH.$img_path;
	}

    /**s
     * @param $image
     * @return float|int
     * @since 1.4.5
     */
	public static function count_savings($image)
    {
        $saved_bytes  = $image->original_size - $image->image_size;

        return round(( $saved_bytes > 0 ) ? $saved_bytes / (int) $image->original_size * 100 : 0, 2);
    }
}
