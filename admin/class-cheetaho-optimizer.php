<?php

/**
 * Class responsible for image optimization tasks.
 *
 * @link       https://cheetaho.com
 * @since      1.4.3
 * @package    CheetahO
 * @subpackage CheetahO/admin
 * @author     CheetahO <support@cheetaho.com>
 */
class CheetahO_Optimizer {

	/**
	 * The version of this plugin.
	 *
	 * @since    1.4.3
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */

	private $version;

	/**
	 * For main cheetaho data object
	 *
	 * @var object $cheetaho
	 */
	private $cheetaho;

	/**
	 * Settings data.
	 *
	 * @since    1.4.3
	 * @access   private
	 * @var      array $cheetaho_settings
	 */
	private $cheetaho_settings;

	/**
	 * Optimized image ID.
	 *
	 * @since    1.4.3
	 * @access   private
	 * @var      int $image_id
	 */
	private $image_id;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since   1.4.3
	 * @param object $cheetaho
	 */

	public function __construct( $cheetaho ) {
		$this->version           = $cheetaho->get_version();
		$this->cheetaho_settings = $cheetaho->get_cheetaho_settings();
		$this->cheetaho          = $cheetaho;
	}

	/**
	 * optimize images on upload
	 *
	 * @since   1.4.3
	 */
	public function cheetaho_ajax_callback() {
		$image_id       = (int) $_POST['id'];
		$this->image_id = $image_id;

		if ( wp_attachment_is_image( $image_id ) ) {
			$image_path = $this->get_image_path( $image_id );

			$result = $this->optimize_image( $image_path, $image_id );

			if ( is_wp_error( $result ) ) {
				$this->update_image_cheetaho_sizes_meta_with_error( $image_id, $result->get_error_message() );

				echo json_encode(
					array(
						'error' => array(
							'message' => $result->get_error_message(),
						),
					)
				);

				wp_die();
			}

			$this->optimize_thumbnails( wp_get_attachment_metadata( $image_id ) );

			echo CheetahO_Helpers::output_result( $image_id );

			die();
		}

		wp_die();
	}

	/**
	 * @since   1.4.3
	 * @param $image_id
	 * @param $data
	 */
	function update_image_cheetaho_sizes_meta( $image_id, $data ) {
		update_post_meta( $image_id, '_cheetaho_size', $data );
	}

	/**
	 * @since   1.4.3
	 * @param $image_id
	 * @param $data
	 */
	function update_image_cheetaho_sizes_meta_with_error( $image_id, $msg ) {
		$data = get_post_meta( $image_id, '_cheetaho_size', true );

		if ( false == $data || ! is_array( $data ) ) {
			$data = array();
		}

		$data['error'] = array( 'message' => $msg );

		$this->update_image_cheetaho_sizes_meta( $image_id, $data );
	}

	function optimize_thumbnails_filter ($image_id)
	{
		$result = $this->optimize_thumbnails(  $image_id );
	}


	/**
	 * Optimize thumbnail images
	 *
	 * @since   1.4.3
	 * @param array $image_data
	 * @param array $data
	 * @return mixed
	 */
	public function optimize_thumbnails( $image_data ) {
		if ( isset( $image_data['file'] ) ) {
			$image_id = $this->image_id;
			if ( empty( $image_id ) ) {
				global $wpdb;
				$post     = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT post_id FROM $wpdb->postmeta WHERE meta_value = %s LIMIT 1",
						$image_data['file']
					)
				);
				$image_id = $post->post_id;
			}

			$path_parts = pathinfo( $image_data['file'] );

			// e.g. 04/02, for use in getting correct path or URL
			$upload_subdir = $path_parts['dirname'];

			$upload_dir = wp_upload_dir();

			// all the way up to /uploads
			$upload_base_path = $upload_dir['basedir'];
			$upload_full_path = $upload_base_path . '/' . $upload_subdir;

			$sizes = array();

			if ( isset( $image_data['sizes'] ) ) {
				$sizes = $image_data['sizes'];
			}

			if ( ! empty( $sizes ) ) {
				$thumbs_optimized_store = array();
				$sizes_to_optimize      = $this->get_sizes_to_optimize();
				$settings               = $this->cheetaho_settings;

				foreach ( $sizes as $key => $size ) {
					if ( ! in_array( "include_size_$key", $sizes_to_optimize ) ) {
						continue;
					}

					$thumb_path = $upload_full_path . '/' . $size['file'];

					if ( file_exists( $thumb_path ) !== false ) {
						$path = wp_get_attachment_image_src( $image_id, $key );
						$file = $path[0];

						if ( false == $path[3] ) {
							$file = dirname( $path[0] ) . '/' . $size['file'];
						}

						$result = $this->optimize( $file, $image_id, $settings );

						if ( is_wp_error( $result ) ) {
							return new WP_Error( 'cheetaho', $result->get_error_message() );
						}

						$result   = $result['data'];
						$dest_url = $result['destURL'];

						$optimization_status = $this->replace_new_image( $thumb_path, $dest_url );

						if ( ! is_wp_error( $optimization_status ) ) {
							$retina_data = $this->optimize_retina( $image_id, $file, $thumb_path );

							$this_thumb = array(
								'thumb'         => $key,
								'file'          => $size['file'],
								'original_size' => $result['originalSize'],
								'cheetaho_size' => $result['newSize'],
								'type'          => $settings['api_lossy'],
								'retina'        => $retina_data,
							);

							$thumbs_optimized_store[] = $this_thumb;
						}
					}
				}
			}

			$this->update_thumbnails_meta( $thumbs_optimized_store, $image_id );

			return $image_data;
		}
	}

	/**
	 * @param $thumbs_optimized_store
	 * @param $image_id
	 */
	public function update_thumbnails_meta( $thumbs_optimized_store, $image_id ) {
		if ( ! empty( $thumbs_optimized_store ) ) {
			$data = get_post_meta( $image_id, '_cheetaho_size', true );

			$data['thumbs_data']     = $thumbs_optimized_store;
			$data['optimizedImages'] = $data['optimizedImages'] + count( $thumbs_optimized_store );

			foreach ( $thumbs_optimized_store as &$th ) {
				$data['size_change']        = $data['size_change'] + ( $th['original_size'] - $th['cheetaho_size'] );
				$data['originalImagesSize'] = $data['originalImagesSize'] + $th['original_size'];
				$data['newSize']            = $data['newSize'] + $th['cheetaho_size'];

				// sum retina info to image meta
				if ( ! empty( $th['retina'] ) ) {
					$data['size_change']        = $data['size_change'] + ( $th['retina']['data']['originalSize'] - $th['retina']['data']['newSize'] );
					$data['originalImagesSize'] = $data['originalImagesSize'] + $th['retina']['data']['originalSize'];
					$data['newSize']            = $data['newSize'] + $th['retina']['data']['newSize'];
				}
			}

			$data['humanReadableLibrarySize'] = size_format( $data['size_change'], 2 );

			$this->update_image_cheetaho_sizes_meta( $image_id, $data );
			update_post_meta( $image_id, '_cheetaho_thumbs', $thumbs_optimized_store, false );
		}
	}


	/**
	 * Return image size to optimize
	 *
	 * @since   1.4.3
	 * @return array
	 */
	function get_sizes_to_optimize() {
		$settings = $this->cheetaho_settings;
		$rv       = array();

		if ( is_array( $settings ) ) {
			foreach ( $settings as $key => $value ) {
				if ( strpos( $key, 'include_size' ) === 0 && ! empty( $value ) ) {
					$rv[] = $key;
				}
			}
		}

		return $rv;
	}

	/**
	 * Handle events after image optimization
	 *
	 * @since   1.4.3
	 * @param array $settings
	 * @param int   $image_id
	 * @param array $cheetaho_data
	 */
	function event_image_optimized( $settings, $image_id, $cheetaho_data, $data ) {
		$this->update_image_size_meta( $settings, $image_id, $cheetaho_data );

		$this->update_image_cheetaho_sizes_meta( $image_id, $data );
	}

	/**
	 * Update image metadata
	 *
	 * @since   1.4.3
	 * @param array $settings
	 * @param int   $image_id
	 * @param array $cheetaho_data
	 */
	function update_image_size_meta( $settings, $image_id, $cheetaho_data ) {
		if ( isset( $settings['resize'] ) && 1 == $settings['resize'] ) {
			$image_data = wp_get_attachment_metadata( $image_id );
			if ( ( isset( $image_data['width'] ) && isset( $image_data['height'] ) && (int) $image_data['width'] > 0 && (int) $image_data['height'] > 0 && $image_data['width'] > $cheetaho_data['imageWidth'] && $image_data['height'] > $cheetaho_data['imageHeight'] ) || ( ! isset( $image_data['width'] ) && ! isset( $image_data['height'] ) ) ) {
				$image_data['width']  = $cheetaho_data['imageWidth'];
				$image_data['height'] = $cheetaho_data['imageHeight'];
				wp_update_attachment_metadata( $image_id, $image_data );
			}
		}
	}

	/**
	 * Assign names to types
	 *
	 * @since   1.4.3
	 * @param int $type
	 * @return string
	 */
	static function optimization_type_to_text( $type ) {
		if ( 1 == $type ) {
			return 'Lossy';
		}

		if ( 0 == $type ) {
			return 'Lossless';
		}
	}

	/**
	 * @since   1.4.3
	 * @param $image_path
	 * @param $image_id
	 * @param $settings
	 * @return WP_Error
	 */
	private function optimize( $image_path, $image_id, $settings ) {
		// make image backup if not exist
		CheetahO_Backup::make_backup( $image_path, $image_id, $settings );

		$cheetaho = new CheetahO_API( $settings );

		$params = array(
			'url'   => $image_path,
			'lossy' => $settings['api_lossy'],
		);

		if ( isset( $settings['quality'] ) && $settings['quality'] > 0 ) {
			$params['quality'] = (int) $settings['quality'];
		}

		if ( isset( $settings['keep_exif'] ) && 1 == $settings['keep_exif'] ) {
			$params['strip_options'] = array( 'keep_exif' => (int) $settings['keep_exif'] );
		}

		if ( isset( $settings['resize'] ) && 1 == $settings['resize'] ) {
			$params['resize'] = array(
				'width'    => (int) $settings['maxWidth'],
				'height'   => (int) $settings['maxHeight'],
				'strategy' => 'auto',
			);
		}

		if ( isset( $settings['create_webp'] ) && 1 == $settings['create_webp'] ) {
			$params['createWebP'] = 1;
		}

		set_time_limit( 400 );

		$data = $cheetaho->url( $params );

		$validation_data = $this->validate_if_image_optimized_successfully( $data );

		if ( is_wp_error( $validation_data ) ) {
			return new WP_Error( 'cheetaho', $validation_data->get_error_message() );
		}

		return $data;
	}

	/**
	 * @since   1.4.3
	 * @param $image_meta_data
	 * @param $new_image_meta_data
	 * @return mixed
	 */
	public static function update_image_meta_stats( $image_meta_data, $new_image_meta_data ) {
		if ( ! empty( $new_image_meta_data ) && $new_image_meta_data['data']['originalSize'] > $new_image_meta_data['data']['newSize'] ) {
			$image_meta_data['optimizedImages']    = $image_meta_data['optimizedImages'] + 1;
			$image_meta_data['size_change']        = $image_meta_data['size_change'] + ( $new_image_meta_data['data']['originalSize'] - $new_image_meta_data['data']['newSize'] );
			$image_meta_data['originalImagesSize'] = $image_meta_data['originalImagesSize'] + $new_image_meta_data['data']['originalSize'];
			$image_meta_data['newSize']            = $image_meta_data['newSize'] + $new_image_meta_data['data']['newSize'];
		}

		return $image_meta_data;
	}

	/**
	 * Oprimize image
	 *
	 * @since   1.4.3
	 * @param string $image_path
	 * @param string $type
	 * @param int    $image_id
	 * @param bool   $throw_exception
	 * @return mixed
	 * @throws Exception
	 */
	private function optimize_image( $image_path, $image_id ) {
		$first_img_time = get_option( '_cheetaho_first_opt_images' );

		if ( false == $first_img_time ) {
			update_option( '_cheetaho_first_opt_images', time() );
		}

		$settings        = $this->cheetaho_settings;
		$validation_data = $this->validate_image_before_optimize( $image_path );

		if ( is_wp_error( $validation_data ) ) {
			return new WP_Error( 'cheetaho', $validation_data->get_error_message() );
		}

		$data = $this->optimize( $image_path, $image_id, $settings );

		if ( is_wp_error( $data ) ) {
			return new WP_Error( 'cheetaho', $data->get_error_message() );
		}

		$local_image_path    = get_attached_file( $image_id );
		$image_meta_data     = $this->generate_image_meta( $data, $image_id, $local_image_path );
		$optimization_status = $this->replace_new_image( $local_image_path, $data['data']['destURL'] );

		if ( ! is_wp_error( $optimization_status ) ) {
			$retina_data               = $this->optimize_retina( $image_id, $image_path, $local_image_path );
			$image_meta_data['retina'] = $retina_data;

			$image_meta_data = $this->update_image_meta_stats( $image_meta_data, $retina_data );

			$this->event_image_optimized( $settings, $image_id, $data, $image_meta_data );
		} else {
			return new WP_Error(
				'cheetaho',
				__( 'Could not overwrite original file. Please check your files permisions.', 'cheetaho-image-optimizer' )
			);
		}

		return $data;
	}

	/**
	 * @since   1.4.3
	 * @param $image_id
	 * @param $image_path
	 * @param $local_image_path
	 * @return array|WP_Error
	 */
	public function optimize_retina( $image_id, $image_path, $local_image_path ) {
		$settings = $this->cheetaho_settings;

		if ( CheetahO_Retina::is_retina_img( $local_image_path ) === false ) {
			$local_image_path = CheetahO_Retina::get_retina_name( $local_image_path );
		}

		$data = array();

		if ( isset( $settings['optimize_retina'] ) && 1 == $settings['optimize_retina'] && file_exists( $local_image_path ) == true ) {
			unset( $settings['resize'] );
			$settings['create_webp'] = 0;
			$image_path              = CheetahO_Retina::get_retina_name( $image_path );

			$data = $this->optimize( $image_path, $image_id, $settings );

			if ( ! is_wp_error( $data ) && isset( $data['data']['destURL'] ) && '' != $data['data']['destURL'] ) {
				$optimization_status = $this->replace_new_image( $local_image_path, $data['data']['destURL'] );

				if ( is_wp_error( $optimization_status ) ) {
					return array();
				}
			} else {
				return array();
			}
		}

		return $data;
	}

	/**
	 * Validate before optimizing
	 *
	 * @since   1.4.3
	 * @return WP_Error
	 */
	function validate_image_before_optimize( $image_path ) {
		$settings = $this->cheetaho_settings;

		if ( isset( $settings['api_key'] ) && '' == $settings['api_key'] ) {
			return new WP_Error(
				'cheetaho',
				__( 'There is a problem with your credentials. Please check them in the CheetahO settings section and try again.', 'cheetaho-image-optimizer' )
			);
		}

		if ( CheetahO_Helpers::is_processable_path( $image_path ) === false ) {
			return new WP_Error( 'cheetaho', __( 'This type of file can not be optimized', 'cheetaho-image-optimizer' ) );
		}
	}

	/**
	 * @since   1.4.3
	 * show quota exeeded message fo user
	 */
	public function set_quota_exceeded_message() {
		$settings = $this->cheetaho_settings;

		$cheetaho = new CheetahO_API( array( 'api_key' => $settings['api_key'] ) );
		$status   = $cheetaho->status();

		$alert = new CheetahO_Alert();

		if ( true == $status['data']['quota']['exceeded'] ) {
			$alert->cheetaho_update_notice( 'quota', 0, 2 );
		} else {
			$alert->cheetaho_update_notice( 'quota', 0, 1 );
		}
	}

	/**
	 * @since   1.4.3
	 * @param $data
	 * @return WP_Error
	 */
	function validate_if_image_optimized_successfully( $data ) {
		// few checks
		if ( isset( $data['error'] ) ) {
			if ( isset( $data['error']['message'] ) ) {
				$msg = $data['error']['message'];
			} else {
				$msg = __( 'System error!', 'cheetaho-image-optimizer' );
			}

			if ( 403 == $data['error']['http_code'] && 'Upps! Your subscription quota exceeded.' == $data['error']['message'] ) {
				$this->set_quota_exceeded_message();
			}

			return new WP_Error( 'cheetaho', $msg );
		}

		if ( isset( $data['data']['originalSize'] ) && isset( $data['data']['newSize'] ) && 0 == (int) $data['data']['originalSize'] && 0 == (int) $data['data']['newSize'] ) {
			return new WP_Error( 'cheetaho', __( 'Error while we optimized image', 'cheetaho-image-optimizer' ) );
		}

		if ( ! isset( $data['data']['originalSize'] ) || 0 == (int) $data['data']['originalSize'] ) {
			return new WP_Error(
				'cheetaho',
				__( 'Could not optimize image. CheetahO can not optimize image. File size 0kb.', 'cheetaho-image-optimizer' )
			);
		}
	}

	/**
	 * Get image public path
	 *
	 * @since   1.4.3
	 * @param $image_id
	 * @return array|false|string
	 */
	private function get_image_path( $image_id ) {
		$mode = getenv( 'CHEETAHO_TEST_MODE' );

		if ( true == $mode ) {
			$image_path = getenv( 'TEST_JPG_IMAGE_REMOTE_PATH' );
		} else {
			if ( ! parse_url( WP_CONTENT_URL, PHP_URL_SCHEME ) ) { // no absolute URLs used -> we implement a hack
				$image_path = get_site_url() . wp_get_attachment_url( $image_id ); // get the file URL
			} else {
				$image_path = wp_get_attachment_url( $image_id ); // get the file URL
			}
		}

		return $image_path;
	}

	/**
	 * Replace old image to new one
	 *
	 * @since   1.4.3
	 * @param string $image_path
	 * @param string $new_url
	 * @return bool
	 */
	function replace_new_image( $image_path, $new_url ) {
		$status = false;

		if ( ! file_exists( $image_path ) ) {
			return new WP_Error( 'cheetaho', __( 'File not exist in your website.', 'cheetaho-image-optimizer' ) );
		}

		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
		}

		$temp_downloaded_file = download_url( $new_url );

		if ( ! is_wp_error( $temp_downloaded_file ) ) {
			clearstatcache();
			$perms = fileperms( $image_path ) & 0777;

			// Replace the file.
			$success = @rename( $temp_downloaded_file, $image_path );

			// If tempfile still exists, unlink it.
			if ( file_exists( $temp_downloaded_file ) ) {
				@unlink( $temp_downloaded_file );
			}

			// If file renaming failed.
			if ( ! $success ) {
				@copy( $temp_downloaded_file, $image_path );
				@unlink( $temp_downloaded_file );
			}

			// Some servers are having issue with file permission, this should fix it.
			if ( empty( $perms ) || ! $perms ) {
				// Source: WordPress Core.
				$stat  = stat( dirname( $image_path ) );
				$perms = $stat['mode'] & 0000666; // Same permissions as parent folder, strip off the executable bits.
			}
			@chmod( $image_path, $perms );

			$status = true;
		}

		return $status;
	}

	/**
	 * Handles optimizing images upload through media uploader.
	 *
	 * @since   1.4.3
	 * @param int $image_id
	 */
	function cheetaho_uploader_callback( $image_id ) {
		try {
			$this->image_id = (int)$image_id;

			if ( wp_attachment_is_image( $image_id ) ) {
				$image_path = $this->get_image_path( $image_id );
				$result     = $this->optimize_image( $image_path, $image_id );

				if ( is_wp_error( $result ) ) {
					$this->update_image_cheetaho_sizes_meta_with_error( $image_id, $result->get_error_message() );
				}
			}
		} catch ( Exception $e ) {
			new WP_Error( 'cheetaho', $e->getMessage() );
		}
	}


	/**
	 * return generated image meta data
	 *
	 * @since   1.4.3
	 * @param array  $result
	 * @param int    $image_id
	 * @param string $image_path
	 * @return mixed
	 */
	function generate_image_meta( array $result, int $image_id, string $image_path ) {
		$settings                         = $this->cheetaho_settings;
		$result                           = $result['data'];
		$savings_percentage               = (int) $result['savedBytes'] / (int) $result['originalSize'] * 100;
		$data['original_size']            = CheetahO_Helpers::convert_to_kb( $result['originalSize'] );
		$data['cheetaho_size']            = CheetahO_Helpers::convert_to_kb( $result['newSize'] );
		$data['saved_bytes']              = CheetahO_Helpers::convert_to_kb( $result['savedBytes'] );
		$data['newSize']                  = $result['newSize'];
		$data['saved_percent']            = round( $savings_percentage, 2 ) . '%';
		$data['type']                     = $this->optimization_type_to_text( $settings['api_lossy'] );
		$data['success']                  = true;
		$data['optimizedImages']          = 1;
		$data['size_change']              = $result['savedBytes'];
		$data['originalImagesSize']       = $result['originalSize'];
		$data['meta']                     = wp_get_attachment_metadata( $image_id );
		$data['humanReadableLibrarySize'] = size_format( $data['size_change'], 2 );

		if ( '' == $data['meta'] ) {
			$imagemeta = getimagesize( $image_path );

			if ( isset( $imagemeta[0] ) && $imagemeta[1] ) {
				$data['meta'] = array(
					'width'  => $imagemeta[0],
					'height' => $imagemeta[1],
				);
			}
		}

		return $data;
	}

	/**
	 * @param $attachment_id
	 * @param bool          $local_image_path
	 * @param $size_name
	 */
	function optimize_after_wr2x_retina_file_added( $attachment_id, $local_image_path = false, $size_name ) {
		if ( false !== $local_image_path ) {
			$image_path = wp_get_attachment_image_src( $attachment_id, $size_name ); // get the file URL

			if ( ! empty( $image_path ) && isset( $image_path[0] ) ) {
				$need_update_meta = false;
				$retina_data      = $this->optimize_retina( $attachment_id, $image_path[0], $local_image_path );
				$image_meta_data  = get_post_meta( $attachment_id, '_cheetaho_thumbs', true );

				foreach ( $image_meta_data as &$data ) {
					if ( $data['thumb'] == $size_name ) {
						$data['retina']   = $retina_data['data'];
						$need_update_meta = true;
					}
				}

				if ( true === $need_update_meta ) {
					$this->update_thumbnails_meta( $image_meta_data, $attachment_id );
				}
			}
		}
	}
}
