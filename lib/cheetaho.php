<?php
/**
 * CheetahO API php library
 * @author CheetahO
 *
 */
class CheetahO
{
    protected $auth = array();
    public function __construct($settings = array(), $params = array())
    {
        $this->auth = array(
        		"key" => (isset($settings['api_key']) && $settings['api_key'] != '') ? $settings['api_key'] : ''
        );
        
        if (isset($settings['authUser']) && isset($settings['authPass'])) {
        	$this->auth = array_merge($this->auth, array('authUser' => $settings['authUser'], 'authPass' => $settings['authPass']));
        }
    }
    
    /** 
     * @param array $opts
     * @return multitype:boolean string
     */
    public function url($params = array())
    {
        $data = json_encode(array_merge($this->auth, $params));
       
        $response = self::request($data, 'http://api.cheetaho.com/api/v1/media');
        
        return $response;
    }
    
 	/**
     * 
     * @param array $opts
     * @return multitype:boolean string
     */
    public function status()
    {
    	$data = array();
    	
        $response = self::request($data, 'http://api.cheetaho.com/api/v1/userstatus', 'get');
        
        return $response;
    }
   
    /**
     * 
     * @param unknown $data
     * @param unknown $url
     * @return multitype:boolean string
     */
    private function request($data, $url, $type = 'post')
    {
        global $wp_version;

        $auth = $this->auth;

        $args = array(
            'timeout' => 400,
            'redirection' => 5,
            'httpversion' => '1.0',
            'user-agent' => 'cheetahoapi Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.85 Safari/537.36 WordPress/' . $wp_version . '; ' . home_url(),
            'blocking' => true,
            'headers' => array(
                'Content-Type' => 'application/json',
                'key' => $auth['key']
            ),
            'cookies' => array(),
            'body' => null,
            'compress' => false,
            'decompress' => true,
            'sslverify' => true,
            'stream' => false,
            'filename' => null
        );

        if ($type == 'post') {
            $args['body'] = $data;
            $response = wp_remote_post($url, $args);
        } else {
            $response = wp_remote_get($url, $args);
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_message = wp_remote_retrieve_response_message($response);

        $responseData = json_decode($response['body'], true);

        if ($response === null || is_wp_error($response)) {
            $responseData = array('data' => array(
                "error" => array('fatal' => true, 'message' => 'cURL Error: ' . $response_code . ' Message:' . $response_message))
            );
        }

        return $responseData;
    }
}