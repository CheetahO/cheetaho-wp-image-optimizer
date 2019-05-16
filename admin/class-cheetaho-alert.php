<?php

/**
 * Handle CheetahO plugin alerts.
 *
 * @link       https://cheetaho.com
 * @since      1.4.3
 * @package    CheetahO
 * @subpackage CheetahO/admin
 * @author     CheetahO <support@cheetaho.com>
 */
class CheetahO_Alert {

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
	 * @since    1.4.3
	 */
	public function __construct($cheetaho) {
        $this->cheetaho_settings = $cheetaho->get_cheetaho_settings();

		add_action( 'all_admin_notices', array( &$this, 'display_quota_exceeded_alert' ) );
		add_action( 'admin_post_cheetahOCloseNotice', array( &$this, 'cheetaho_close_notice' ) );
		add_action( 'all_admin_notices', array( &$this, 'display_api_key_alert' ) );
		add_action( 'all_admin_notices', array( &$this, 'display_rate_us_alert' ) );
	}

	public function display_quota_exceeded_alert() {
        $settings = $this->cheetaho_settings;
		include CHEETAHO_PLUGIN_ROOT . 'admin/views/alerts/quota-exceeded.php';
	}

	public function display_api_key_alert() {
        $settings = $this->cheetaho_settings;
		include CHEETAHO_PLUGIN_ROOT . 'admin/views/alerts/api-key.php';
	}

	public function display_rate_us_alert() {
        $settings = $this->cheetaho_settings;
		include CHEETAHO_PLUGIN_ROOT . 'admin/views/alerts/rate-us.php';
	}


	/**
	 * Close notice
	 *
	 * @param int $notice
	 * @param int $user_id
	 */
	public function cheetaho_close_notice( $notice, $user_id = 0 ) {
		global $current_user;
		$notice = $_GET['notice'];

		$user_id = ( 0 === $user_id ) ? $current_user->ID : $user_id;
		$notices = get_user_meta( $user_id, '_cheetaho_ignore_notices', true );

		if ( ! is_array( $notices ) ) {
			$notices = array();
		}

		$notices[] = $notice;

		$notices = array_filter( $notices );
		$notices = array_unique( $notices );

		update_user_meta( $user_id, '_cheetaho_ignore_notices', $notices );

		wp_safe_redirect( wp_get_referer() );

		die();
	}

	/**
	 * Update notices settings if action  = 1 - set, else is action = 2 - delete
	 *
	 * @param string $notice
	 * @param int    $user_id
	 * @param int    $action
	 */
	function cheetaho_update_notice( $notice, $user_id = 0, $action = 1 ) {
		global $current_user;

		$user_id = ( 0 === $user_id ) ? $current_user->ID : $user_id;
		$notices = get_user_meta( $user_id, '_cheetaho_ignore_notices', true );

		if ( 2 == $action ) {
			$newitems = array();

			if ( ! empty( $notices ) ) {
				foreach ( $notices as $item ) {
					if ( $item != $notice ) {
						$newitems[] = $item;
					}
				}
			}

			update_user_meta( $user_id, '_cheetaho_ignore_notices', $newitems );
		} elseif ( 1 == $action ) {
			if ( ! is_array( $notices ) ) {
				$notices = array();
			}

			$notices[] = $notice;
			$notices   = array_filter( $notices );
			$notices   = array_unique( $notices );
			update_user_meta( $user_id, '_cheetaho_ignore_notices', $notices );
		}
	}

	/**
	 * Close alert url
	 *
	 * @param $arg
	 * @return mixed
	 */
	public static function get_close_alert_url( $arg ) {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=cheetahOCloseNotice&notice=' . $arg ),
			'cheetaho_close_notice'
		);
	}

}
