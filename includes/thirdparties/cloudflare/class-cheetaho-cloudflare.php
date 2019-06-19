<?php
/**
 *
 * The file that defines hook class for cloud flare cache purge
 *
 * @link       https://cheetaho.com
 * @since      1.4.3.4
 * @package    CheetahO
 * @subpackage CheetahO/includes/thirdparties/cloudflare
 * @author     CheetahO <support@cheetaho.com>
 */

if (!defined('ABSPATH')) {
    die('Direct access is not allowed.');
}

class CheetahO_Cloudflare extends Cheetaho_Base
{
    const ZONE_CAHCE_KEY = 'cheetaho_cloudflare_zone_id';

    private $email;

    private $api_key;

    private $zone_id;

    /**
     * Cloudflare constructor
     *
     * @since      1.4.3.4
     */
    public function __construct($cheetaho)
    {
        parent::__construct($cheetaho);
        $credentials = $this->get_credentials();

        if (false !== $credentials) {
            $this->email = $credentials['email'];
            $this->api_key = $credentials['api_key'];
            $this->zone_id = $credentials['zone'];
        }
    }

    /**
     * Check if the credentials are valid.
     *
     * @since  1.4.3.4
     * @return bool
     */
    public function valid_credentials()
    {
        return self::is_valid(array($this->email, $this->api_key, $this->zone_id));
    }

    /**
     * Clean up the domain name.
     *
     * @param $domain
     * @since  1.4.3.4
     * @return string
     */
    public static function strip_scheme($domain)
    {
        return rtrim(str_replace('https://', '', str_replace('http://', '', $domain)), '/');
    }

    /**
     * Purge files from the api cache
     *
     * @param array $files
     * @since  1.4.3.4
     * @return array|mixed|object
     */
    public function purge_files($files)
    {
        return self::purge($files, $this->email, $this->api_key, $this->zone_id);
    }

    /**
     * Returns zone Id by domain.
     *
     * @param $domain
     * @param $email
     * @param $api_key
     *
     * @since  1.4.3.4
     * @return bool
     */
    public static function get_zone_id($domain, $email, $api_key)
    {
        $result = get_transient( self::ZONE_CAHCE_KEY ) ;

        if ( false !== $result) {
            return $result;
        } else {
             $endpoint = 'https://api.cloudflare.com/client/v4/zones?name=' . $domain;
            $response = self::get($endpoint, self::get_headers($email, $api_key));

            if (false !== $response) {
                if (isset($response->result[0]->id)) {
                    set_transient( self::ZONE_CAHCE_KEY, $response->result[0]->id, 60 );

                    return $response->result[0]->id;
                }
            }
        }

        return false;
    }

    /**
     * Returns the required api headers
     *
     * @param $email
     * @param $api_key
     *
     * @since  1.4.3.4
     * @return array
     */
    public static function get_headers($email, $api_key)
    {
        return array(
            'X-Auth-Email' => $email,
            'X-Auth-Key' => $api_key,
            'Content-Type' => 'application/json'
        );
    }

    /**
     * Sends GET request to given url
     *
     * @param $endpoint
     * @param $headers
     *
     * @since  1.4.3.4
     * @return array|mixed|object
     */
    public static function get($endpoint, $headers)
    {

        $response = wp_remote_get($endpoint, array('timeout' => 30, 'headers' => $headers));

        if (is_wp_error($response)) {
            return false;
        }

        return json_decode($response['body']);

    }

    /**
     * Returns the CloudFlare credentials from the following sources
     *  1. CheetahO settings page
     *  2. If 1. is not setup, then look for them from the LittleBizzy plugin
     *  3. If 1.,2. is not setup then look for them from the CloudFlare plugin
     *
     * @since  1.4.3.4
     * @return array|bool
     */
    function get_credentials()
    {
        // Check for CheetahO credentials first.
        $email = CheetahO_Settings::instance($this->cheetaho)->get_options_value(CheetahO_Settings::CLOUDFLARE_EMAIL,
            '');
        $api_key = CheetahO_Settings::instance($this->cheetaho)->get_options_value(CheetahO_Settings::CLOUDFLARE_API_KEY,
            '');
        $zone = CheetahO_Settings::instance($this->cheetaho)->get_options_value(CheetahO_Settings::CLOUDFLARE_ZONE, '');

        if (self::is_valid(array($email, $api_key, $zone))) {
            $zone = self::strip_scheme($zone);

            if (filter_var("https://{$zone}", FILTER_VALIDATE_URL)) {
                $zone = self::get_zone_id($zone, $email, $api_key);
            }

            return array(
                'email' => $email,
                'api_key' => $api_key,
                'zone' => $zone
            );
        }

        // No CheetahO CloudFlare credentials? Check for LittleBizzy plugin maybe?
        if (!self::is_valid(array($email, $api_key, $zone))) {
            if (class_exists('\LittleBizzy\CloudFlare\Core\Data')) {
                $data = \LittleBizzy\CloudFlare\Core\Data::instance();
                $api_key = defined('CLOUDFLARE_API_KEY') ? CLOUDFLARE_API_KEY : $data->key;
                $email = defined('CLOUDFLARE_API_EMAIL') ? CLOUDFLARE_API_EMAIL : $data->email;
                $zone = isset($data->zone['id']) ? $data->zone['id'] : null;

                if (self::is_valid(array($api_key, $email, $zone))) {
                    return array(
                        'email' => $email,
                        'api_key' => $api_key,
                        'zone' => $zone
                    );
                }
            }
        }

        // Still not CloudFlare credentials? Check for CloudFlare plugin maybe?
        if (!self::is_valid(array($email, $api_key, $zone))) {
            if (class_exists('\CF\WordPress\WordPressClientAPI')) {

                $email = get_option('cloudflare_api_email');
                $api_key = get_option('cloudflare_api_key');
                $zone = get_option('cloudflare_cached_domain_name');

                if (self::is_valid(array($email, $api_key, $zone))) {
                    $zone = self::get_zone_id($zone, $email, $api_key);

                    return array(
                        'email' => $email,
                        'api_key' => $api_key,
                        'zone' => $zone
                    );
                }
            }
        }

        return false;
    }

    /**
     * Check if the credentials are valid?
     *
     * @param $arr
     * @since  1.4.3.4
     * @return bool
     */
    public static function is_valid($arr)
    {
        foreach ($arr as $value) {
            if (is_null($value) || empty($value)) {
                return false;
            }
        }

        return true;
    }


    /**
     * Purges file from the CloudFlare API.
     *
     * @param $files
     * @param $email
     * @param $api_key
     * @param $zone
     *
     * @since  1.4.3.4
     * @return array|mixed|object
     */
    public static function purge($files, $email, $api_key, $zone)
    {
        $endpoint = "https://api.cloudflare.com/client/v4/zones/" . $zone . "/purge_cache";
        $files = is_array($files) ? $files : array($files);
        $params = json_encode(array('files' => $files));
        $headers = self::get_headers($email, $api_key);

        $response = self::delete($endpoint, $params, $headers);

        return isset($response['success']) ? $response['success'] : false;
    }

    /**
     * Sends DELETE request to given url
     *
     * @param $endpoint
     * @param $json_params
     * @param $headers
     *
     * @since  1.4.3.4
     * @return array|mixed|object
     */
    public static function delete($endpoint, $json_params, $headers)
    {

        $headers_mod = array();
        foreach ($headers as $key => $value) {
            array_push($headers_mod, "{$key}:{$value}");
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_mod);
        curl_setopt($ch, CURLOPT_USERAGENT,
            '"User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.87 Safari/537.36"');

        $response = curl_exec($ch);
        $result = json_decode($response, true);
        curl_close($ch);

        return $result;
    }
}