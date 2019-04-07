<?php

/**
 * Handle CheetahO plugin retina files.
 *
 * @link       https://cheetaho.com
 * @since      1.4.3
 * @package    CheetahO
 * @subpackage CheetahO/admin
 * @author     CheetahO <support@cheetaho.com>
 */
class CheetahO_Retina {

	/**
	 * check if image is retina
	 *
	 * @param string $path
	 * @return bool
	 */
	public static function is_retina_img( $path ) {
		$base_name = pathinfo( $path, PATHINFO_FILENAME );

		return strpos( $base_name, '@2x' ) == strlen( $base_name ) - 3;
	}

	public static function get_retina_name( $file ) {
		$ext = pathinfo( $file, PATHINFO_EXTENSION );

		return substr( $file, 0, strlen( $file ) - 1 - strlen( $ext ) ) . '@2x.' . $ext;
	}
}
