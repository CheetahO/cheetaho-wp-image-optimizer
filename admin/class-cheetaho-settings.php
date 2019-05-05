<?php

/**
 * The admin-settings functionality of the plugin.
 *
 * @link       https://cheetaho.com
 * @since      1.4.3
 * @package    CheetahO
 * @subpackage CheetahO/admin
 * @author     CheetahO <support@cheetaho.com>
 */
class CheetahO_Settings {


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
	 * Add settings action link to the plugins page.
	 *
	 * @since  1.4.3
	 * @param  string $links
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		return array_merge(
			array(
				'settings' => $this->get_settings_url(),
			),
			$links
		);
	}

	/**
	 * Get settings action link.
	 *
	 * @since    1.4.3
	 */
	public function get_settings_url() {
		return '<a href="' . CHEETAHO_SETTINGS_LINK . '">' . __( 'Settings', 'cheetaho-image-optimizer' ) . '</a>';
	}

	/**
	 * Register and show settings page
	 */
	function register_settings_page() {
		add_options_page(
			'CheetahO Settings',
			'CheetahO',
			'manage_options',
			CHEETAHO_PLUGIN_NAME,
			array(
				$this,
				'render_cheetaho_settings_menu',
			)
		);
	}

	/**
	 * Update settings data
	 *
	 * @param array $data
	 */
	function update_settings( $data ) {
		update_option( '_cheetaho_options', $data );
	}

	/**
	 * Update cheetaho settings options
	 *
	 * @param string $key
	 * @param int    $value
	 */
	function update_options_value( $key, $value ) {
		$settings = get_option( '_cheetaho_options' );

		if ( isset( $settings[ $key ] ) ) {
			$settings[ $key ] = $value;
		} else {
			$settings = array_merge( $settings, array( $key => $value ) );
		}

		$this->update_settings( $settings );
	}

	/**
	 * Render settings page html
	 *
	 * @return mixed
	 */
	function render_cheetaho_settings_menu() {
		$settings = $this->cheetaho_settings;

		if ( isset( $_POST['cheetahoSaveAction'] ) && ! empty( $_POST ) ) {
			$result = $this->validate_options_data( $_POST['_cheetaho_options'] );
			$this->update_settings( $result['valid'] );

			// get new settings version
			$this->cheetaho_settings = $this->cheetaho->get_cheetaho_settings_data();
			$settings                = $this->cheetaho_settings;
		}

		// empty backup
		if ( isset( $_POST['empty_backup'] ) ) {
			$backup_obj = new CheetahO_Backup( $this->cheetaho );
			$backup_obj->empty_backup();
		}

		$lossy             = ( isset( $settings['api_lossy'] ) && '' != $settings['api_lossy'] ) ? $settings['api_lossy'] : true;
		$auto_optimize     = isset( $settings['auto_optimize'] ) ? $settings['auto_optimize'] : 1;
		$backup            = isset( $settings['backup'] ) ? $settings['backup'] : 1;
		$quality           = isset( $settings['quality'] ) ? $settings['quality'] : 0;
		$sizes             = get_intermediate_image_sizes();
		$backupfolder_size = size_format( CheetahO_Helpers::folder_size( CHEETAHO_BACKUP_FOLDER ), 2 );
		$optimize_retina   = isset( $settings['optimize_retina'] ) ? $settings['optimize_retina'] : 1;
		$create_webp       = isset( $settings['create_webp'] ) ? $settings['create_webp'] : 1;
		$keep_exif         = isset( $settings['keep_exif'] ) ? $settings['keep_exif'] : 1;
		$resize            = isset( $settings['resize'] ) ? (int) $settings['resize'] : 0;
		$max_height        = isset( $settings['maxHeight'] ) ? $settings['maxHeight'] : 0;
		$max_width         = isset( $settings['maxWidth'] ) ? $settings['maxWidth'] : 0;
		$api_key           = isset( $settings['api_key'] ) ? trim($settings['api_key']) : '';
		$auth_user         = isset( $settings['authUser'] ) ? trim($settings['authUser']) : '';
		$auth_pass         = isset( $settings['authPass'] ) ? trim($settings['authPass']) : '';

		foreach ( $sizes as $size ) {
			$valid[ 'include_size_' . $size ] = isset( $settings[ 'include_size_' . $size ] ) ? $settings[ 'include_size_' . $size ] : 1;
		}

		include_once CHEETAHO_PLUGIN_ROOT . 'admin/views/settings.php';
	}

	/**
	 * Validate plugins settings
	 *
	 * @param array $input
	 * @return array
	 */
	function validate_options_data( $input ) {
		$valid                    = array();
		$error                    = array();
		$valid['api_lossy']       = $input['api_lossy'];
		$valid['auto_optimize']   = isset( $input['auto_optimize'] ) ? 1 : 0;
		$valid['quality']         = isset( $input['quality'] ) ? (int) $input['quality'] : 0;
		$valid['backup']          = isset( $input['backup'] ) ? 1 : 0;
		$valid['keep_exif']       = isset( $input['keep_exif'] ) ? 1 : 0;
		$valid['optimize_retina'] = isset( $input['optimize_retina'] ) ? 1 : 0;
		$valid['resize']          = ( isset( $input['resize'] ) && 1 == $input['resize'] ) ? 1 : 0;
		$valid['create_webp']     = isset( $input['create_webp'] ) ? 1 : 0;
		$valid['authUser']        = $input['authUser'];
		$valid['authPass']        = $input['authPass'];

		if ( 1 == $valid['resize'] ) {
			$valid['maxWidth']  = ( isset( $input['maxWidth'] ) && $input['maxWidth'] > 0 ) ? (int) $input['maxWidth'] : 0;
			$valid['maxHeight'] = ( isset( $input['maxHeight'] ) && $input['maxHeight'] > 0 ) ? (int) $input['maxHeight'] : 0;

			if ( 0 == $valid['maxWidth'] || 0 == $valid['maxHeight'] ) {
				$error[] = __( 'If you would like to resize image, the max height and max width fields must be filled', 'cheetaho-image-optimizer' );
			}
		}

		$sizes = get_intermediate_image_sizes();

		foreach ( $sizes as $size ) {
			$valid[ 'include_size_' . $size ] = ( isset( $input[ 'include_size_' . $size ] ) && 1 == $input[ 'include_size_' . $size ] ) ? 1 : 0;
		}

		if ( empty( $input['api_key'] ) ) {
			$error[] = __( 'Please enter API Credentials', 'cheetaho-image-optimizer' );
		} else {
			$cheetaho = new CheetahO_API( array( 'api_key' => $input['api_key'] ) );
			$status   = $cheetaho->status();

			if ( isset( $status['error'] ) ) {
				$error[] = __( 'Your API key is invalid. Check it here', 'cheetaho-image-optimizer' ) . ' https://app.cheetaho.com/admin/api-credentials';
			} else {
				$alert = new CheetahO_Alert();

				if ( isset( $status['data']['quota']['exceeded'] ) && false == $status['data']['quota']['exceeded'] ) {
					$alert->cheetaho_update_notice( 'quota', 0, 1 );
				} else {
					$alert->cheetaho_update_notice( 'quota', 0, 2 );
				}
			}

			$valid['api_key'] = $input['api_key'];
		}

		if ( ! file_exists( CHEETAHO_BACKUP_FOLDER ) && ! @mkdir( CHEETAHO_BACKUP_FOLDER, 0777, true ) ) {
			$error[] = __( 'There is something preventing us to create a new folder for backing up your original files. Please make sure that folder', 'cheetaho-image-optimizer' ) .
				' <b>' .
				WP_CONTENT_DIR . '/' . CHEETAHO_UPLOADS_NAME . '</b> ' . __( 'has the necessary write and read rights.', 'cheetaho-image-optimizer' );
		}

		if ( ! empty( $error ) ) {
			return array(
				'success' => false,
				'error'   => $error,
				'valid'   => $valid,
			);
		} else {
			return array(
				'success' => true,
				'valid'   => $valid,
			);
		}
	}
}
