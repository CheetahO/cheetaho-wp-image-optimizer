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

	public static function restore_original_image( $attachment_id, $meta = null ) {
		$file = get_attached_file( $attachment_id );

		$meta = wp_get_attachment_metadata( $attachment_id );

		$paths = CheetahO_Helpers::get_image_paths( $file );

		$bk_file = $paths['backup_file'];

		// first check if the file is readable by the current user - otherwise it will be unaccessible for the web browser
		if ( ! CheetahO_Helpers::set_file_perms( $bk_file ) ) {
			return false;
		}

		$thumbs_paths = array();
		if ( ! empty( $meta['file'] ) && is_array( $meta['sizes'] ) ) {
			foreach ( $meta['sizes'] as $size => $image_data ) {
				$original_image_path = dirname( $file ) . '/' . $image_data['file'];

				$paths = CheetahO_Helpers::get_image_paths( $original_image_path );

				$source = $paths['backup_file'];
				if ( ! file_exists( $source ) ) {
					continue; // if thumbs were not optimized, then the backups will not be there.
				}
				$thumbs_paths[ $source ] = $paths['original_image_path'];
				if ( ! CheetahO_Helpers::set_file_perms( $source ) ) {
					return false;
				}
			}
		}

		if ( file_exists( $bk_file ) ) {
			try {
				// main file
				CheetahO_Helpers::rename_file( $bk_file, $file );

				// overwriting thumbnails
				foreach ( $thumbs_paths as $source => $destination ) {
					CheetahO_Helpers::rename_file( $source, $destination );
				}
			} catch ( Exception $e ) {
				return false;
			}
		} else {
			return false;
		}

		return true;
	}

	/**
	 * @param $image_path
	 * @param $image_id
	 * @param $settings
	 * @return WP_Error
	 */
	static function make_backup( $image_path, $image_id, $settings ) {
		if ( isset( $settings['backup'] ) && 1 == $settings['backup'] || ! isset( $settings['backup'] ) ) {
			$original_image_path = get_attached_file( $image_id );

			// reformat image path, because sometimes no thumbs
			$file = dirname( $original_image_path ) . '/' . basename( $image_path );

			$paths = CheetahO_Helpers::get_image_paths( $file );

			if ( ! file_exists( CHEETAHO_BACKUP_FOLDER . '/' . $paths['full_sub_dir'] ) && ! @mkdir( CHEETAHO_BACKUP_FOLDER . '/' . $paths['full_sub_dir'], 0777, true ) ) {// creates backup folder if it doesn't exist

				return new WP_Error(
					'cheetaho',
					__( 'Backup folder does not exist and it cannot be created', 'cheetaho-image-optimizer' )
				);
			}

			if ( ! file_exists( $paths['backup_file'] ) ) {
				@copy( $paths['original_image_path'], $paths['backup_file'] );
			}

			if ( ! file_exists( CheetahO_Retina::get_retina_name( $paths['backup_file'] ) ) ) {
				@copy( CheetahO_Retina::get_retina_name( $paths['original_image_path'] ), CheetahO_Retina::get_retina_name( $paths['backup_file'] ) );
			}
		}
	}
}
