<?php
/**
 * CheetahO API php library
 * @author CheetahO
 *
 */
class CheetahO
{
    protected $auth = array();
    public function __construct($key = '')
    {
        $this->auth = array(
                "key" => $key
        );
    }
    /**
     * 
     * @param array $opts
     * @return multitype:boolean string
     */
    public function url($params = array())
    {
        $data = json_encode(array_merge($this->auth, $params));
        $response = self::request($data, 'http://app.cheetaho.com/api/v1/media');
        
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
    	
        $response = self::request($data, 'http://app.cheetaho.com/api/v1/userstatus', 'get');
        
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
        $curl = curl_init();
        
    	$auth = $this->auth;
    	
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
        	'key: '.$auth['key']
        ));
               
        curl_setopt($curl, CURLOPT_URL, $url);
               
        curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.85 Safari/537.36");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if ($type == 'post') {
        	curl_setopt($curl, CURLOPT_POST, 1);
        	curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_FAILONERROR, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 400);  
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0); 

        
        $response = json_decode(curl_exec($curl), true);
		$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        if ($response === null ) {
   		 	$response = array('data' => array(
               	"error" => array('fatal' => true, 'message' => 'cURL Error: '.$httpcode.' Message:' . $error))
          	);
    	} 
      
   
        curl_close($curl);
         
        return $response;
    }
}