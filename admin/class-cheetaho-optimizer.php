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
	 * Image meta db table instance.
	 *
	 * @since    1.4.3
	 * @access   private
	 */
	private $image_meta;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since   1.4.3
	 *
	 * @param object $cheetaho
	 */

	public function __construct( $cheetaho ) {
		$this->version           = $cheetaho->get_version();
		$this->cheetaho_settings = $cheetaho->get_cheetaho_settings();
		$this->cheetaho          = $cheetaho;
		$this->image_meta        = new CheetahO_Image_Metadata( new CheetahO_DB() );

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

				$this->update_image_cheetaho_sizes_meta_with_error( $image_id, $result->get_error_message(), $result->get_error_data('cheetaho') );

				echo json_encode(
					array(
						'error' => array_merge(
							array(
								'message' => $result->get_error_message()
							),
							($result->get_error_data('cheetaho') != null)  ? $result->get_error_data('cheetaho') : array()
						),
					)
				);

				wp_die();
			}

            $this->optimize_thumbnails( wp_get_attachment_metadata( $image_id ) );

			$meta = $this->image_meta->get_image_stats($image_id);

            do_action( 'cheetaho_attachment_optimized', $image_id);

            echo CheetahO_Helpers::output_result( $meta );

			die();
		}

		wp_die();
	}

	/**
	 * @since   1.4.3
	 *
	 * @param $image_id
	 * @param $data
	 */
	function update_image_cheetaho_sizes_meta( $image_id, $data ) {
		$this->image_meta->add_or_update_image_meta( $image_id, $data );
	}

	/**
	 * @since   1.4.3
	 *
	 * @param $image_id
	 * @param $data
	 */
	function update_image_cheetaho_sizes_meta_with_error( $image_id, $msg, $error_data = array() ) {

		$local_image_path    = get_attached_file( $image_id );
		$data           = $this->generate_image_meta( array(), $local_image_path );
		$data['results']    = array_merge_recursive(array( 'message' => $msg),  $error_data );
		$data['status'] = isset($error_data['type']) ? $error_data['type'] : 'error';

		$this->update_image_cheetaho_sizes_meta( $image_id, $data );
	}

	function optimize_thumbnails_filter( $image_data, $image_id ) {

        if ( wp_attachment_is_image( $image_id ) ) {
            $image_path = $this->get_image_path( $image_id );
            $result     = $this->optimize_image( $image_path, $image_id, $image_data );

            if ( is_wp_error( $result ) ) {
                $this->update_image_cheetaho_sizes_meta_with_error( $image_id, $result->get_error_message(), $result->get_error_data('cheetaho')  );
                remove_filter( 'wp_generate_attachment_metadata', array( &$this, 'optimize_thumbnails_filter'), 999, 2 );

                return $image_data;
            }
        }

		$image_data_response = $this->optimize_thumbnails( $image_data );

		if ( is_wp_error( $image_data_response ) ) {
            remove_filter( 'wp_generate_attachment_metadata', array( &$this, 'optimize_thumbnails_filter'), 999, 2 );

            return $image_data;
		}

		if(is_numeric($image_id)) {
            do_action('cheetaho_attachment_optimized', $image_id);
        }

        return $image_data;
	}


	/**
	 * Optimize thumbnail images
	 *
	 * @since   1.4.3
	 *
	 * @param array $image_data
	 * @param array $data
	 *
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
				$sizes_to_optimize = $this->get_sizes_to_optimize();
				$settings          = $this->cheetaho_settings;

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

						$result = $this->optimize( $file, $image_id, $settings, $key );

						if ( is_wp_error( $result ) ) {
							$error_data = $result->get_error_data('cheetaho');

							if (isset($error_data['type']) && $error_data['type'] == 'nosavings') {
								continue;
							}

							return new WP_Error( 'cheetaho', $result->get_error_message(),
								$error_data
							);
						}

						$dest_url = $result['data']['destURL'];

						$optimization_status = $this->replace_new_image( $thumb_path, $dest_url );

						$result['image_size_name'] = $key;
						$result['width'] = $size['width'];
						$result['height'] = $size['height'];
						$image_meta_data           = $this->generate_image_meta( $result, $thumb_path );

						$this->update_image_cheetaho_sizes_meta( $image_id, $image_meta_data );

						if ( ! is_wp_error( $optimization_status ) ) {
							$this->optimize_retina( $image_id, $file, $thumb_path, $key );
						}
					}
				}
			}

			return $image_data;
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
	 *
	 * @param array $settings
	 * @param int $image_id
	 * @param array $cheetaho_data
	 */
	function event_image_optimized( $settings, $image_id, $cheetaho_data, $data, $wp_image_meta_data = array() ) {
		$this->update_image_size_meta( $settings, $image_id, $cheetaho_data, $wp_image_meta_data );

		$this->update_image_cheetaho_sizes_meta( $image_id, $data );
	}

	/**
	 * Update image metadata
	 *
	 * @since   1.4.3
	 *
	 * @param array $settings
	 * @param int $image_id
	 * @param array $cheetaho_data
	 */
	function update_image_size_meta( $settings, $image_id, $cheetaho_data, $image_data = array() ) {
		if ( isset( $settings['resize'] ) && 1 == $settings['resize'] ) {
		    if (empty($image_data)) {
                $image_data = wp_get_attachment_metadata($image_id);
            }

			if ( ( isset( $image_data['width'] ) && isset( $image_data['height'] ) && (int) $image_data['width'] > 0 && (int) $image_data['height'] > 0 && $image_data['width'] > $cheetaho_data['imageWidth'] && $image_data['height'] > $cheetaho_data['imageHeight'] ) || ( ! isset( $image_data['width'] ) && ! isset( $image_data['height'] ) ) ) {

			    if (!isset($image_data['width']) && !isset($image_data['height'])) {
                    $image_data = array();
                }

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
	 *
	 * @param int $type
	 *
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
	 *
	 * @param $image_path
	 * @param $image_id
	 * @param $settings
	 *
	 * @return WP_Error
	 */
	private function optimize( $image_path, $image_id, $settings, $image_size_name = false, $galery_type = 'media' ) {
		// make image backup if not exist
        if ($galery_type === 'custom' ) {
            $file = CheetahO_Helpers::get_abs_path(wp_make_link_relative($image_path));
        } else {
            $original_image_path = get_attached_file($image_id);
            $file = dirname($original_image_path) . '/' . basename($image_path);
        }

		CheetahO_Backup::make_backup( $file, $settings );

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
        $data['image_size_name'] = $image_size_name;

		$validation_data = $this->validate_if_image_optimized_successfully( $data );

		if ( is_wp_error( $validation_data ) ) {

			return new WP_Error( 'cheetaho', $validation_data->get_error_message(), $validation_data->get_error_data('cheetaho') );
		}

		return $data;
	}


	/**
	 * Oprimize image
	 *
	 * @since   1.4.3
	 *
	 * @param string $image_path
	 * @param string $type
	 * @param int $image_id
	 * @param bool $throw_exception
	 *
	 * @return mixed
	 * @throws Exception
	 */
	private function optimize_image( $image_path, $image_id, $wp_image_meta_data = array() )
    {
        $local_image_path    = get_attached_file( $image_id );
        $this->removeWCommerceFilters();

		$first_img_time = get_option( '_cheetaho_first_opt_images' );

		if ( false == $first_img_time ) {
			update_option( '_cheetaho_first_opt_images', time() );
		}

		$settings        = $this->cheetaho_settings;
		$validation_data = $this->validate_image_before_optimize( $local_image_path );

		if ( is_wp_error( $validation_data ) ) {
			return new WP_Error( 'cheetaho', $validation_data->get_error_message(), $validation_data->get_error_data('cheetaho') );
		}

		$image_size_name = 'full';

		$data = $this->optimize( $image_path, $image_id, $settings, $image_size_name);

		if ( is_wp_error( $data ) ) {
			return new WP_Error( 'cheetaho', $data->get_error_message(), $data->get_error_data('cheetaho'));
		}

		if (empty($wp_image_meta_data)) {
            $wp_image_meta_data = wp_get_attachment_metadata($image_id);
        }

        $data['image_size_name'] = $image_size_name;
        $data['width'] = isset($wp_image_meta_data['width']) ? $wp_image_meta_data['width'] : $data['data']['imageWidth'];
        $data['height'] = isset($wp_image_meta_data['height']) ? $wp_image_meta_data['height'] : $data['data']['imageHeight'];

		$image_meta_data     = $this->generate_image_meta( $data, $local_image_path );
		$optimization_status = $this->replace_new_image( $local_image_path, $data['data']['destURL'] );

		if ( ! is_wp_error( $optimization_status ) ) {
			$this->optimize_retina( $image_id, $image_path, $local_image_path, 'full' );

			$this->event_image_optimized( $settings, $image_id, $data['data'], $image_meta_data, $wp_image_meta_data );
		} else {
			return new WP_Error(
				'cheetaho',
				__( 'Could not overwrite original file. Please check your files permisions.', 'cheetaho-image-optimizer' ),
				array('type' => 'bad_permissions')
			);
		}

		return $data;
	}

	/**
	 * @since   1.4.3
	 *
	 * @param $image_id
	 * @param $image_path
	 * @param $local_image_path
	 *
	 * @return array|WP_Error
	 */
	public function optimize_retina( $image_id, $image_path, $local_image_path, $image_size_name ) {
		$settings = $this->cheetaho_settings;

		if ( CheetahO_Retina::is_retina_img( $local_image_path ) === false ) {
			$local_image_path = CheetahO_Retina::get_retina_name( $local_image_path );
		}

		$data = array();

		if ( isset( $settings['optimize_retina'] ) && 1 == $settings['optimize_retina'] && file_exists( $local_image_path ) == true ) {
			unset( $settings['resize'] );
			$settings['create_webp'] = 0;

            if ( CheetahO_Retina::is_retina_img( $image_path ) === false ) {
                $image_path              = CheetahO_Retina::get_retina_name( $image_path );
            }

			$data = $this->optimize( $image_path, $image_id, $settings, $image_size_name );

			if ( ! is_wp_error( $data ) && isset( $data['data']['destURL'] ) && '' != $data['data']['destURL'] ) {
				$optimization_status = $this->replace_new_image( $local_image_path, $data['data']['destURL'] );

				if ( is_wp_error( $optimization_status ) ) {
					return array();
				}

				$data['image_size_name'] = $image_size_name;
				$data['is_retina']       = true;
				$image_meta_data         = $this->generate_image_meta( $data, $local_image_path );

				$this->update_image_cheetaho_sizes_meta( $image_id, $image_meta_data );

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
				__( 'There is a problem with your credentials. Please check them in the CheetahO settings section and try again.', 'cheetaho-image-optimizer' ),
				array('type' => 'credential')
			);
		}

		if ( CheetahO_Helpers::is_processable_path( $image_path, CheetahO_Helpers::convert_exclude_file_patterns_to_array($settings) ) === false ) {
			return new WP_Error( 'cheetaho',
				__( 'This file can not be optimized', 'cheetaho-image-optimizer' ),
				array('type' => 'filestore')
			);
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

		$alert = new CheetahO_Alert($this->cheetaho);

		if ( true == $status['data']['quota']['exceeded'] ) {
			$alert->cheetaho_update_notice( 'quota', 0, 2 );
		} else {
			$alert->cheetaho_update_notice( 'quota', 0, 1 );
		}
	}

	/**
	 * @since   1.4.3
	 *
	 * @param $data
	 *
	 * @return WP_Error
	 */
	function validate_if_image_optimized_successfully( $data ) {
		// few checks
		if ( isset( $data['error'] ) ) {
			if ( isset( $data['error']['message'] ) ) {
				$msg = $data['error']['message'];

				if (strpos($msg, 'You do not need to optimize this') !== false) {
					$error_type = 'nosavings';
				} else {
					$error_type = 'error';
				}

			} else {
				$msg = __( 'System error!', 'cheetaho-image-optimizer' );
				$error_type = 'system_error';
			}

			if ( 403 == $data['error']['http_code'] && 'Upps! Your subscription quota exceeded.' == $data['error']['message'] ) {
				$this->set_quota_exceeded_message();
				$error_type = 'quota_exceeded';
			}

			return new WP_Error( 'cheetaho', $msg, array('type' => $error_type) );
		}

		if ( isset( $data['data']['originalSize'] ) && isset( $data['data']['newSize'] ) && 0 == (int) $data['data']['originalSize'] && 0 == (int) $data['data']['newSize'] ) {
			return new WP_Error( 'cheetaho', __( 'Error while we optimized image', 'cheetaho-image-optimizer' ),
				array('type' => 'optimization_fail'));
		}

		if ( ! isset( $data['data']['originalSize'] ) || 0 == (int) $data['data']['originalSize'] ) {
			return new WP_Error(
				'cheetaho',
				__( 'Could not optimize image. CheetahO can not optimize image. File size 0kb.', 'cheetaho-image-optimizer' ),
				array('type' => 'optimization_fail')
			);
		}
	}

	/**
	 * Get image public path
	 *
	 * @since   1.4.3
	 *
	 * @param $image_id
	 *
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
	 *
	 * @param string $image_path
	 * @param string $new_url
	 *
	 * @return bool
	 */
	function replace_new_image( $image_path, $new_url ) {
		$status = false;

		if ( ! file_exists( $image_path ) ) {
			return new WP_Error( 'cheetaho', __( 'File not exist in your website.', 'cheetaho-image-optimizer' ),
				array('type' => 'filestore'));
		}

		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
		}

		$temp_downloaded_file = download_url( $new_url );

		if ( ! is_wp_error( $temp_downloaded_file ) ) {
			$status = CheetahO_Helpers::rename_file($temp_downloaded_file, $image_path);
		}

		return $status;
	}

	/**
	 * Handles optimizing images upload through media uploader.
	 *
	 * @since   1.4.3
	 *
	 * @param int $image_id
	 */
	function cheetaho_uploader_callback( $image_id ) {

        $this->image_id = (int) $image_id;


        if ( wp_attachment_is_image( $image_id ) ) {
            $image_path = $this->get_image_path( $image_id );
            $image_data = wp_get_attachment_metadata( $image_id );

            $result     = $this->optimize_image( $image_path, $image_id, $image_data );

            if ( is_wp_error( $result ) ) {
                $this->update_image_cheetaho_sizes_meta_with_error( $image_id, $result->get_error_message(), $result->get_error_data('cheetaho')  );
                remove_filter( 'wp_generate_attachment_metadata', array( &$this, 'optimize_thumbnails_filter'), 999, 2 );

                return $image_data;
            }
        }

	}


	/**
	 * return generated image meta data
	 *
	 * @since   1.4.3
	 *
	 * @param array $result
	 * @param int $image_id
	 * @param string $image_path
	 *
	 * @return mixed
	 */
	function generate_image_meta( array $result, $image_path ) {

		$not_abs_image_path = str_replace( ABSPATH, '', $image_path );

		$settings                     = $this->cheetaho_settings;
		$data['image_size_name']      = ( isset( $result['image_size_name'] ) ) ? trim( $result['image_size_name'] ) : 'full';
		$data['new_size']             = isset($result['data']['newSize']) ? $result['data']['newSize'] : 0;
		$data['type']                 = strtolower( $this->optimization_type_to_text( $settings['api_lossy'] ) );
		$data['success']              = true;
		$data['status']               = isset($result['status']) ? $result['status'] : 'success';
		$data['size_change']          = isset($result['data']['savedBytes']) ? $result['data']['savedBytes'] : 0;
		$data['original_images_size'] = isset($result['data']['originalSize']) ? $result['data']['originalSize'] : 0;
		$data['path']                 = $not_abs_image_path;
		$data['is_retina']            = ( isset( $result['is_retina'] ) ) ? 1 : 0;
		$data['results']              = array();
		$data['log']                  = null;
		$data['width']                = isset($result['width']) ? $result['width'] : null;
		$data['height']               = isset($result['height']) ? $result['height'] : null;
		$backup_path                  = '';

		if ( CheetahO_Helpers::can_do_backup( $settings ) ) {
			$paths               = CheetahO_Helpers::get_image_paths( $image_path );
			$backup_path         = $paths['backup_file'];
			$not_abs_backup_path = str_replace( ABSPATH, '', $backup_path );
		}

		$data['backup_path'] = $not_abs_backup_path;

		if ( isset( $settings['resize'] ) && 1 == $settings['resize']) {
			$data['width']  = isset($result['data']['imageWidth']) ? $result['data']['imageWidth'] : 0;
			$data['height'] = isset($result['data']['imageHeight']) ? $result['data']['imageHeight'] : 0;
		}

		return $data;
	}

	/**
	 * @param $attachment_id
	 * @param $local_image_path
	 * @param $size_name
	 */
	function optimize_after_wr2x_retina_file_added( $attachment_id, $local_image_path, $size_name ) {
		if ( false !== $local_image_path ) {
		    print_R('asd');
            $this->removeWCommerceFilters();
			$image_path = wp_get_attachment_image_src( $attachment_id, $size_name ); // get the file URL

			if ( ! empty( $image_path ) && isset( $image_path[0] ) ) {
				$this->optimize_retina( $attachment_id, $image_path[0], $local_image_path, $size_name );
			}
		}
	}

	function removeWCommerceFilters()
    {
        add_filter( 'woocommerce_resize_images', '__return_false' );
        add_filter( 'woocommerce_background_image_regeneration', '__return_false' );
    }

    public function optimize_custom_image($meta_id)
    {
        $meta = $this->image_meta->get_image_meta_by_meta_id($meta_id);

        if ($meta->status !== CheetahO_Image_Metadata::STATUS_SUCCESS)  // do we need to optimize?
        {
            $paths = CheetahO_Helpers::get_image_paths( $meta->path );
            $image_path = get_site_url().'/'.$paths['original_image_path'];

            $validation_data = $this->validate_image_before_optimize( $paths['local_path'] );

            if ( is_wp_error( $validation_data )) {
                return json_encode(
                    array(
                        'success'       => false,
                        'status_txt'    => __( 'Failed','cheetaho-image-optimizer' ),
                        'html' => $validation_data->get_error_message()
                    )
                );
            }
            $result = $this->optimize( $image_path, $meta_id, $this->cheetaho_settings, 'full', 'custom');

            if ( !is_wp_error( $result ) ) {

                $optimization_status = $this->replace_new_image($paths['local_path'], $result['data']['destURL']);

                $meta_data = [];
                $meta_data['width'] = isset($result['data']['imageWidth']) ? $result['data']['imageWidth'] : 0;
                $meta_data['height'] = isset($result['data']['imageHeight']) ? $result['data']['imageHeight'] : 0;
                $meta_data['image_size'] = isset($result['data']['newSize']) ? $result['data']['newSize'] : 0;
                $meta_data['level'] = strtolower( $this->optimization_type_to_text( $this->cheetaho_settings['api_lossy'] ) );

                if (CheetahO_Helpers::can_do_backup($this->cheetaho_settings) === true) {
                    $meta_data['backup_path'] =  str_replace( ABSPATH, '', $paths['backup_file'] );;
                }

                if (!is_wp_error($optimization_status)) {
                    $meta_data['status'] = 'success';
                    $meta_data['results'] = [];
                    $this->image_meta->update_optimized_custom_meta($meta_id, $meta_data);
                    do_action( 'cheetaho_custom_file_optimized', $meta_id);

                    $image = $meta;
                    $image->level = $meta_data['level'];
                    $image->image_size = $meta_data['image_size'];

                    include CHEETAHO_PLUGIN_ROOT . 'admin/views/parts/column-custom-results.php';

                    return json_encode(
                        array(
                            'status_txt'    => __( 'Optimized','cheetaho-image-optimizer' ),
                            'success'       => true,
                            'html' => $html
                        )
                    );

                } else {
                    $meta_data['status'] = 'error';
                    $meta_data['results'] = $result;
                    $this->image_meta->update_optimized_custom_meta($meta_id, $meta_data);

                    return json_encode(
                        array(
                            'error' => array_merge(
                                array(
                                    'message' => $optimization_status->get_error_message()
                                ),
                                ($optimization_status->get_error_data('cheetaho') != null)  ? $optimization_status->get_error_data('cheetaho') : array()
                            ),
                        )
                    );
                }
            } else {
                return json_encode(
                    array(
                        'error' => array_merge(
                            array(
                                'message' => $result->get_error_message()
                            ),
                            ($result->get_error_data('cheetaho') != null)  ? $result->get_error_data('cheetaho') : array()
                        ),
                    )
                );
            }
        }
    }
}
