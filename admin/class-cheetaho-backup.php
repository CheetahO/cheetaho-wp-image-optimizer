<?php

/**
 * The CheetahO backups functionality of the plugin.
 *
 * @link       https://cheetaho.com
 * @since      1.4.3
 * @package    CheetahO
 * @subpackage CheetahO/admin
 * @author     CheetahO <support@cheetaho.com>
 */
class CheetahO_Backup {

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
	 * Initialize the class and set its properties.
	 *
	 * @since   1.4.3
	 * @param   string $plugin_name The name of this plugin.
	 * @param   string $version The version of this plugin.
	 * @param   array  $settings
	 */
	public function __construct( $cheetaho ) {
		$this->version           = $cheetaho->get_version();
		$this->cheetaho_settings = $cheetaho->get_cheetaho_settings();
		$this->cheetaho          = $cheetaho;
	}

	/**
	 * Empty backup folder
	 */
	public function empty_backup() {
		$dir_path = CHEETAHO_BACKUP_FOLDER;
		$this->delete_dir( $dir_path );
	}

	/**
	 * Remove dir with files
	 *
	 * @param string $dir_path
	 */
	public function delete_dir( $dir_path ) {
		if ( file_exists( $dir_path ) ) {
			if ( substr( $dir_path, strlen( $dir_path ) - 1, 1 ) != '/' ) {
				$dir_path .= '/';
			}
			$files = glob( $dir_path . '*', GLOB_MARK );
			foreach ( $files as $file ) {
				if ( is_dir( $file ) ) {
					$this->delete_dir( $file );
					@rmdir( $file );// remove empty dir
				} else {
					@unlink( $file );// remove file
				}
			}
		}
	}

	public static function restore_images ($image_id) {
		$images = (new CheetahO_Image_Metadata( new CheetahO_DB() ))->get_items($image_id);

		if (is_array($images) && count($images) > 0) {
			foreach ( $images as $image ) {
			    self::restore_single_file($image);
			}
		}

		return true;
	}

	public static function restore_single_file($image)
    {
        if ( $image->backup_path != '' && file_exists( CheetahO_Helpers::get_abs_path( $image->backup_path ) ) ) {

            // first check if the file is readable by the current user - otherwise it will be unaccessible for the web browser
            if (!CheetahO_Helpers::set_file_perms(CheetahO_Helpers::get_abs_path($image->backup_path))) {
                return false;
            }

            try {
                // main file
                CheetahO_Helpers::rename_file(CheetahO_Helpers::get_abs_path($image->backup_path),
                    CheetahO_Helpers::get_abs_path($image->path));

            } catch (Exception $e) {
                return false;
            }
        }
    }

	/**
	 * @param $image_path
	 * @param $image_id
	 * @param $settings
	 * @return WP_Error
	 */
	static function make_backup( $image_path, $settings ) {
		if ( CheetahO_Helpers::can_do_backup($settings) === true) {
			$paths = CheetahO_Helpers::get_image_paths( $image_path );

			if ( ! file_exists( CHEETAHO_BACKUP_FOLDER . '/' . $paths['full_sub_dir'] ) && ! @mkdir( CHEETAHO_BACKUP_FOLDER . '/' . $paths['full_sub_dir'], 0777, true ) ) {// creates backup folder if it doesn't exist

				return new WP_Error(
					'cheetaho',
					__( 'Backup folder does not exist and it cannot be created', 'cheetaho-image-optimizer' )
				);
			}

			if ( ! file_exists( $paths['backup_file'] ) ) {
				@copy( $paths['original_image_path'], $paths['backup_file'] );
			}
		}
	}
}
