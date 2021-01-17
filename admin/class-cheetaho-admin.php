<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://cheetaho.com
 * @since      1.4.3
 * @package    CheetahO
 * @subpackage CheetahO/admin
 * @author     CheetahO <support@cheetaho.com>
 */
class CheetahO_Admin {

	/**
	 * The version of this plugin.
	 *
	 * @since    1.4.3
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Settings data.
	 *
	 * @since    1.4.3
	 * @access   private
	 * @var      array $cheetaho_settings
	 */
	private $cheetaho_settings;

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
	 * @since    1.4.3
	 * @param    string $plugin_name The name of this plugin.
	 * @param    string $version The version of this plugin.
	 */
	public function __construct( $cheetaho ) {
		$this->version           = $cheetaho->get_version();
		$this->cheetaho_settings = $cheetaho->get_cheetaho_settings();
		$this->image_meta        = new CheetahO_Image_Metadata( new CheetahO_DB() );

	}

	/**
	 * Do stuff when plugin loaded
	 *
	 * @since    1.4.3
	 */
	public function plugin_loaded () {
		//check DB upgrade
		CheetahO_DB::check_db_update();
	}

	/**
	 * Generate additional columns in media library
	 *
	 * @param array $columns
	 * @return mixed
	 */
	function add_media_columns( $columns ) {
		$columns['cheetaho'] = __( 'CheetahO', 'cheetaho-image-optimizer' );

		return $columns;
	}

	/**
	 * Fill media columns
	 *
	 * @param string $column_name
	 * @param int    $id
	 */
	function fill_media_columns( $column_name, $id ) {
		$options = get_option( '_cheetaho_options' );
		$type    = isset( $options['api_lossy'] ) ? $options['api_lossy'] : 0;

		if ( strcmp( $column_name, 'cheetaho' ) === 0 ) {
			if ( wp_attachment_is_image( $id ) ) {
				$meta = $this->image_meta->get_image_stats($id);

				//migration tweak from older versions
				$has_cheetaho_meta = false;
				if ($meta == null) {
					$meta = get_post_meta($id, '_cheetaho_size', true);

					// Is it optimized? Show some stats
					if (isset($meta['cheetaho_size'])) {
						$has_cheetaho_meta = true;
					}
				}

				// Is it optimized? Show some stats
				if ( $meta != null && (isset($meta['status']) && 'success' == $meta['status'])  || $has_cheetaho_meta == true) {
					$output = CheetahO_Helpers::output_result( $meta, $has_cheetaho_meta );

					$output = json_decode( $output, true );

					echo $output['html'];
				} else {
					// Were there no savings, or was there an error?
					$image_url = wp_get_attachment_url( $id );
					$filename  = basename( $image_url );

					echo $this->show_optimize_button( $type, $id, $filename, $image_url, $meta );

					if ( ! empty( $meta['no_savings'] ) ) {
						echo '<div class="noSavings"><strong>' . __( 'No savings found', 'cheetaho-image-optimizer' ) . '</strong><br /><small>' . __( 'Type', 'cheetaho-image-optimizer' ) . ':&nbsp;' . $meta['type'] . '</small></div>';
					} else {
						if ( isset( $meta['error'] ) ) {
							$error = $meta['error']['message'];
							$txt   = __( 'Failed! Hover here', 'cheetaho-image-optimizer' );
							echo '<div class="cheetahoErrorWrap"><a class="cheetahoError" title="' . $error . '">' . $txt . '<span>' . $error . '</span></a></div>';
						}
					}
				}
			} else {
				echo 'n/a';
			}
		}
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since   1.4.3
	 */
	public function enqueue_styles() {
		wp_enqueue_style( CHEETAHO_PLUGIN_NAME, plugin_dir_url( __FILE__ ) . 'assets/css/cheetaho.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.4.3
	 * @param $hook
	 */
	public function enqueue_scripts( $hook ) {
		// For option page
        $showScripts = false;

        if ('post.php' == $hook && get_post_type() === 'attachment'){
            $showScripts = true;
        }

        if ( 'options-media.php' == $hook || 'upload.php' == $hook || 'settings_page_cheetaho' == $hook || $showScripts === true) {
			wp_enqueue_script( CHEETAHO_PLUGIN_NAME . '-async', plugin_dir_url( __FILE__ ) . 'assets/js/async.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( CHEETAHO_PLUGIN_NAME . '-cheetaho', plugin_dir_url( __FILE__ ) . 'assets/js/cheetaho.js', array( 'jquery' ), $this->version, false );

			wp_localize_script(
				CHEETAHO_PLUGIN_NAME . '-cheetaho',
				'cheetaho_object',
				array(
					'url'                 => admin_url( 'admin-ajax.php' ),
					'changeMLToListMode'  => __( 'In order to access the CheetahO Optimization actions and info, please change to ', 'cheetaho-image-optimizer' ),
					'changeMLToListMode1' => __( 'List View', 'cheetaho-image-optimizer' ),
					'changeMLToListMode2' => __( 'Dismiss', 'cheetaho-image-optimizer' ),
					'resizeAlert1'        => __( 'Please do not set a {type} less than your largest website thumbnail. If you will do this you will need regenerate all your thumbnails in case you will ever need this.', 'cheetaho-image-optimizer' ),
				)
			);
		}

		// For media bulk page
		if ( 'media_page_cheetaho-bulk' == $hook ) {
			wp_enqueue_script( CHEETAHO_PLUGIN_NAME . '-bulk', plugin_dir_url( __FILE__ ) . 'assets/js/cheetahobulk.js', array( 'jquery' ), $this->version, false );

			wp_localize_script(
				CHEETAHO_PLUGIN_NAME . '-bulk',
				'cheetahoBulk',
				array(
					'chAllDone'         => __( 'All images are processed', 'cheetaho-image-optimizer' ),
					'chNoActionTaken'   => __( 'No action taken', 'cheetaho-image-optimizer' ),
					'chBulkAction'      => __( 'Compress Images', 'cheetaho-image-optimizer' ),
					'chCancelled'       => __( 'Cancelled', 'cheetaho-image-optimizer' ),
					'chCompressing'     => __( 'Compressing', 'cheetaho-image-optimizer' ),
					'chCompressed'      => __( 'compressed', 'cheetaho-image-optimizer' ),
					'chFile'            => __( 'File', 'cheetaho-image-optimizer' ),
					'chSizesOptimized'  => __( 'Sizes optimized', 'cheetaho-image-optimizer' ),
					'chInitialSize'     => __( 'Initial size', 'cheetaho-image-optimizer' ),
					'chCurrentSize'     => __( 'Current size', 'cheetaho-image-optimizer' ),
					'chSavings'         => __( 'Savings', 'cheetaho-image-optimizer' ),
					'chStatus'          => __( 'Status', 'cheetaho-image-optimizer' ),
					'chShowMoreDetails' => __( 'Show more details', 'cheetaho-image-optimizer' ),
					'chError'           => __( 'Error', 'cheetaho-image-optimizer' ),
					'chLatestError'     => __( 'Latest error', 'cheetaho-image-optimizer' ),
					'chInternalError'   => __( 'Internal error', 'cheetaho-image-optimizer' ),
					'chOutOf'           => __( 'out of', 'cheetaho-image-optimizer' ),
					'chWaiting'         => __( 'Waiting', 'cheetaho-image-optimizer' ),
				)
			);
		}
	}

	/**
	 * Reset media library images
	 */
	function media_library_reset() {
		$image_id = (int) $_POST['id'];
		$local_image_path    = get_attached_file( $image_id );

		$identifier = $this->image_meta->get_identifier( $image_id, array('path' => $local_image_path, 'image_size_name' => 'full') );
		$image_meta = $this->image_meta->get_item($image_id, $identifier);

		if($image_meta === null) {
            $image_meta = $this->image_meta->get_item_by_size($image_id, 'full');
        }

		if ( ! empty( $image_meta ) ) {
			CheetahO_Backup::restore_images( $image_id );
			CheetahO_Helpers::reset_image_size_meta( $image_id, $image_meta );
			$this->image_meta->delete_image_meta($image_id);

            do_action( 'cheetaho_attachment_reset', $image_id);
        }

		echo json_encode(
			array(
				'success'       => true,
				'html'          => $this->optimize_button_html_by_image_id( $image_id ),
			)
		);

		wp_die();
	}

	function cheetaho_media_library_reset_batch(){}

	function optimize_button_html_by_image_id( $id ) {
		$image_url = wp_get_attachment_url( $id );
		$filename  = basename( $image_url );
		$settings  = $this->cheetaho_settings;

		return $this->show_optimize_button( $settings['api_lossy'], $id, $filename, $image_url );
	}

	function show_optimize_button( $type, $id, $filename, $image_url, $meta = array() ) {

        if ( CheetahO_Helpers::is_excluded_file($image_url, CheetahO_Helpers::convert_exclude_file_patterns_to_array($this->cheetaho_settings) ) === false ) {

            include CHEETAHO_PLUGIN_ROOT . 'admin/views/parts/optimize-button.php';

            return $html;
        }

        return __( 'Image can not be optimized', 'cheetaho-image-optimizer' );
	}

	/**
	 * Delete attachment by its ID
	 *
	 * @param int $id
	 */
	function delete_attachment_img( $id ) {
		CheetahO_Helpers::handle_delete_attachment_in_backup( $id );
		CheetahO_Helpers::handle_delete_attachment_web_p( $id );
        $this->image_meta->delete_image_meta($id);
	}

	/**
	 * redirect after activation
	 */
	function cheetaho_redirect() {
		if ( get_option( 'cheetaho_activation_redirect', false ) ) {
			delete_option( 'cheetaho_activation_redirect' );

			if ( ! isset( $_GET['activate-multi'] ) ) {
				exit( wp_redirect( CHEETAHO_SETTINGS_LINK ) );
			}
		}
	}


    /** Meta box for cheetaho in view image
     * @hook add_meta_boxes
     */
    function cheetaho_info_box()
    {
        if(get_post_type( ) == 'attachment') {
            add_meta_box(
                'cheetaho_info_box',          // this is HTML id of the box on edit screen
                __('CheetahO Info', 'cheetaho-image-optimizer'),    // title of the box
                array( &$this, 'cheetaho_info_box_content'),   // function to be called to display the info
                null, // on which edit screen the box should appear
                'side'//'normal',      // part of page where the box should appear
            );
        }
    }

    /**
     * Meta box for view image
     */
    function cheetaho_info_box_content($post)
    {
        $this->fill_media_columns( 'cheetaho', $post->ID, true );
    }
}
