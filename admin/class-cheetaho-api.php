<?php

/**
 * Handle requests and other stuff with CheetahO api.
 *
 * @link       https://cheetaho.com
 * @since      1.4.3
 * @package    CheetahO
 * @subpackage CheetahO/admin
 * @author     CheetahO <support@cheetaho.com>
 */
class CheetahO_API {

	protected $auth = array();

	public function __construct( $settings = array(), $params = array() ) {
		$this->auth = array( 'key' => ( isset( $settings['api_key'] ) && '' != $settings['api_key'] ) ? $settings['api_key'] : '' );

		if ( isset( $settings['authUser'] ) && isset( $settings['authPass'] ) ) {
			$this->auth = array_merge(
				$this->auth,
				array(
					'authUser' => $settings['authUser'],
					'authPass' => $settings['authPass'],
				)
			);
		}
	}

	/**
	 *
	 * @param array $opts
	 * @return multitype:boolean string
	 */
	public function status() {
		$data = array();

		$response = self::request( $data, 'http://api.cheetaho.com/api/v1/userstatus', 'get' );
		update_option( '_cheetaho_api_status', $response );

		return $response;
	}

	/**
	 *
	 * @param unknown $data
	 * @param unknown $url
	 * @return multitype:boolean string
	 */
	private function request( $data, $url, $type = 'post' ) {
		global $wp_version;

		$auth = $this->auth;

		$args = array(
			'timeout'     => 400,
			'redirection' => 5,
			'httpversion' => '1.0',
			'user-agent'  => 'cheetahoapi Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.85 Safari/537.36 WordPress/' . $wp_version . '; ' . home_url(),
			'blocking'    => true,
			'headers'     => array(
				'Content-Type' => 'application/json',
				'key'          => $auth['key'],
			),
			'cookies'     => array(),
			'body'        => null,
			'compress'    => false,
			'decompress'  => true,
			'sslverify'   => true,
			'stream'      => false,
			'filename'    => null,
		);

		if ( 'post' == $type ) {
			$args['body'] = $data;

			$args['body'] = json_encode( array_merge( $this->auth, $args['body'] ) );

			$response = wp_remote_post( $url, $args );
		} else {
			$response = wp_remote_get( $url, $args );
		}

		$response_code    = wp_remote_retrieve_response_code( $response );
		$response_message = wp_remote_retrieve_response_message( $response );

		$response_data = json_decode( $response['body'], true );

		if ( null === $response || is_wp_error( $response ) || $response_code != 200 ) {
			$response_data = array(
				'data' => array(
					'error' => array(
						'fatal'   => true,
						'code'    => $response_code,
						'message' => 'cURL Error: ' . $response_code . ' Message:' . $response_message,
					),
				),
			);
		}

		return $response_data;
	}

	/**
	 * @param array $params
	 * @return multitype:boolean string
	 */
	public function url( $params = array() ) {
		$data = $params;

		$response = self::request( $data, 'http://api.cheetaho.com/api/v1/media', 'post' );

		return $response;
	}

	public function send_feedback($params)
    {
        $data = array(
            'reason' => $params['reason'],
            'message' => $params['message'],
            'server_name' => $_SERVER['SERVER_NAME']
        );

        self::request( $data, 'http://api.cheetaho.com/api/v2/feedback', 'post' );
    }
}
