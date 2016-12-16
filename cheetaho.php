<?php
/**
 * Plugin Name: CheetahO Image Optimizer
 * Plugin URI: http://cheetaho.com/
 * Description: CheetahO optimizes images automatically. Check your <a href="options-general.php?page=cheetaho" target="_blank">Settings &gt; CheetahO</a> page on how to start optimizing your image library and make your website load faster. Do not forget to update these settings after plugin update. 
 * Version: 1.2.5
 * Author: CheetahO
 * Author URI: http://cheetaho.com
 */

define( 'CHEETAHO_ASSETS_IMG_URL'   			 , realpath( plugin_dir_url( __FILE__  ) . 'img/' ) . '/img' );
define( 'CHEETAHO_VERSION'   					 , '1.2.5' );
define( 'CHEETAHO_APP_URL'						 , 'http://app.cheetaho.com/');
define( 'CHEETAHO_SETTINGS_LINK'				 , admin_url( 'options-general.php?page=cheetaho' ));
$uploads = wp_upload_dir();
define( 'CHEETAHO_UPLOADS_BASE'					 , $uploads['basedir']);

$backupBase = is_main_site() ? CHEETAHO_UPLOADS_BASE : dirname(dirname(CHEETAHO_UPLOADS_BASE));
$siteID = get_current_blog_id();

define( 'CHEETAHO_BACKUP'								 , 'CheetahoBackups');
define( 'CHEETAHO_BACKUP_FOLDER' 				 ,  $backupBase . '/' . CHEETAHO_BACKUP . '/' . $siteID);

if (! class_exists('WPCheetahO')) {
    require_once ( dirname(__FILE__) . '/lib/functions/cheetaho-ui.php');
    require_once ( dirname(__FILE__) . '/lib/classes/cheetahoHelper.php');
    
	
    class WPCheetahO
    {

        private $image_id;

        private $cheetaho_settings = array();

        private $thumbs_data = array();
        
        private $status = array();

        private $cheetaho_optimization_type = 'lossy';

        public static $plugin_version = CHEETAHO_VERSION;
                

        /*
         * public function WPCheetahO() {
         * $this->__construct();
         * }
         */
        public function __construct()
        {
        	
            $plugin_dir_path = dirname(__FILE__);
            require_once ($plugin_dir_path . '/lib/cheetaho.php');
            
            $this->cheetaho_settings = get_option('_cheetaho_options');
            $this->status = get_option('_cheetaho_api_status');
            
           
            $this->cheetaho_optimization_type = $this->cheetaho_settings['api_lossy'];
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(
                &$this,
                'add_settings_link'
            ));
            add_action('admin_enqueue_scripts', array(
                &$this,
                'cheetaho_enqueue'
            ));
            add_filter('manage_media_columns', array(
                &$this,
                'add_media_columns'
            ));
            add_action('manage_media_custom_column', array(
                &$this,
                'fill_media_columns'
            ), 10, 2);
            add_action('wp_ajax_cheetaho_reset', array(
                &$this,
                'cheetaho_media_library_reset'
            ));
            add_action('wp_ajax_cheetaho_request', array(
                &$this,
                'cheetaho_ajax_callback'
            ));
            add_action('wp_ajax_cheetaho_reset_all', array(
                &$this,
                'cheetaho_media_library_reset_batch'
            ));
            if ((! empty($this->cheetaho_settings) && ! empty($this->cheetaho_settings['auto_optimize'])) || ! isset($this->cheetaho_settings['auto_optimize'])) {
               
                add_action('add_attachment', array(
                    &$this,
                    'cheetaho_uploader_callback'
                ));
                 add_filter('wp_generate_attachment_metadata', array(
                    &$this,
                    'optimize_thumbnails'
                ));
            }
            
            // add_action( 'admin_menu', array( &$this, 'cheetahoMenu' ) );
            add_action('admin_menu', array(
                &$this,
                'registerSettingsPage'
            )); // display SP in Settings menu
            
            add_action( 'all_admin_notices', array(  &$this, 'displayQuotaExceededAlert' ) );
            add_action( 'all_admin_notices', array(  &$this, 'displayApiKeyAlert' ) );
             
           	add_action( 'delete_attachment', array( &$this, 'deleteAttachmentInBackup' ) );
           	add_action( 'admin_menu', array( &$this, 'registerBulkPage' ) );

            
        }
        
      	public function renderBulkProcess () {
        	$settings = $this->cheetaho_settings;
      		$images = cheetahoHelper::getNotOptimizedImagesIDs($settings);
	              		
        	return CheetahoUI::displayBulkForm($this, $images);
        }
        
        function registerBulkPage( ) {
	        add_media_page( 'CheetahO Bulk Process', 'Bulk CheetahO', 'edit_others_posts', 'cheetaho-bulk', array( &$this, 'renderBulkProcess' ) );
	    }
	    
	    
        function deleteAttachmentInBackup ($id) {
        	cheetahoHelper::handleDeleteAttachmentInBackup($id);
        }
        
	    function checkStatus ($apiKey = false) {
		 	$settings = $this->cheetaho_settings;
		 	$apiKey = ($apiKey == false) ? $settings['api_key'] : $apiKey;
	        $Cheetaho = new Cheetaho($apiKey);
	        
	        $status = $Cheetaho->status();
	        update_option('_cheetaho_api_status', $status);
	        $this->status = $status;
	        return $status;
	          
		}
        
        public  function displayApiKeyAlert() {
        	
        	$settings = $this->cheetaho_settings;
        	return CheetahoUI::displayApiKeyAlert($settings);
        	if ($settings['api_key'] == ''){
        		return CheetahoUI::displayApiKeyAlert($settings);
        	} else {
        		return '';
        	}
        } 
        
        public  function displayQuotaExceededAlert() {
        	return CheetahoUI::displayQuotaExceededAlert( $this->status);
        } 

        public function registerSettingsPage()
        {
            add_options_page('CheetahO Settings', 'CheetahO', 'manage_options', 'cheetaho', array(
                $this,
                'renderCheetahoSettingsMenu'
            ));
            
        }
        
        public function renderCheetahoSettingsMenu () {
        
        	return CheetahoUI::renderCheetahoSettingsMenu($this);
        }

        /**
         * Handles optimizing images uploade through media uploader.
         */
        function cheetaho_uploader_callback($image_id)
        {
            $this->image_id = $image_id;
            
            if (wp_attachment_is_image($image_id)) {
                
                $settings = $this->cheetaho_settings;
                $type = $settings['api_lossy'];
                
                if (! parse_url(WP_CONTENT_URL, PHP_URL_SCHEME)) { // no absolute URLs used -> we implement a hack
                    $image_path = get_site_url() . wp_get_attachment_url($image_id); // get the file URL
                } else {
                    $image_path = wp_get_attachment_url($image_id); // get the file URL
                }
                
                $result = $this->optimizeImage($image_path, $type, $image_id);
                
                $image_path = get_attached_file($image_id);
                
                if (! isset($result['error']) && ! isset($result['data']['error'])  && isset($result['data']['destURL']) ) {
                    
                    if (isset($result['data']['originalSize']) && (int)$result['data']['originalSize'] > 0) {
          				$result = $result['data'];
	                    $savings_percentage = (int) $result['savedBytes'] / (int) $result['originalSize'] * 100;
	                    $data['original_size'] = self::convert_to_kb($result['originalSize']);
	                    $data['cheetaho_size'] = self::convert_to_kb($result['newSize']);
	                    $data['saved_bytes'] = self::convert_to_kb($result['savedBytes']);
	                    $data['newSize'] = $result['newSize'];
	                    $data['saved_percent'] = round($savings_percentage, 2) . '%';
	                    $data['type'] = $this->type_toText($this->cheetaho_optimization_type);
	                    $data['success'] = true;
	                    $data['optimizedImages'] = 1;
	                    $data['size_change'] = $result['savedBytes'];
	                    $data['originalImagesSize'] = $result['originalSize'];
	                    $data['meta'] = wp_get_attachment_metadata($image_id);
	                    $data['humanReadableLibrarySize'] = size_format($data['size_change'], 2);
	                    $saved_bytes = (int) $data['saved_bytes'];
	                    
	                    if ($this->replace_new_image($image_path, $result['destURL'])) {
	                        update_post_meta($image_id, '_cheetaho_size', $data);
	                    } else {
	                        // writing image failed
	                    }
                    }
                } else {
                    
                    // error or no optimization
                    if (file_exists($image_path)) {
                        
                        $data['original_size'] = self::convert_to_kb(filesize($image_path));
                        $data['error'] = $result['error'];
                        $data['type'] = $result['type'];
                        
                        if ($data['error'] == 'This image can not be optimized any further') {
                            $data['cheetaho_size'] = 'No savings found';
                            $data['no_savings'] = true;
                        }
                        
                        update_post_meta($image_id, '_cheetaho_size', $data);
                    } else {
                        // file not found
                    }
                }
            }
        }

        public function cheetaho_enqueue($hook)
        {
            if ($hook == 'options-media.php' || $hook == 'upload.php' || $hook == 'settings_page_cheetaho' ) {
                wp_enqueue_script('jquery');
                wp_enqueue_script( 'async-js', plugins_url( '/js/async.js', __FILE__ ) );
                wp_enqueue_script('cheetaho-js', plugins_url('/js/cheetaho.js', __FILE__), array(
                    'jquery'
                ));
                wp_localize_script('cheetaho-js', 'cheetaho_object', array(
                    'url' => admin_url('admin-ajax.php'),
                    'changeMLToListMode' => __( 'In order to access the CheetahO Optimization actions and info, please change to ', 'cheetaho' ),
                	'changeMLToListMode1' => __( 'List View', 'cheetaho' ),
                	'changeMLToListMode2' => __( 'Dismiss', 'cheetaho' ),
            
                ));
               
                
            }

            if ( $hook == 'media_page_cheetaho-bulk' ) {
             	 wp_enqueue_script('cheetaho-js-bulk', plugins_url('/js/cheetahobulk.js', __FILE__), array(
                    'jquery'
                ));
             	 
             	 wp_localize_script( 'cheetaho-js-bulk', 'cheetahoBulk', array(  
             	 		'chAllDone' => __( 'All images are processed', 'cheetaho' ),
             	 		'chNoActionTaken' => __( 'No action taken', 'cheetaho' ),
             	 		'chBulkAction' => __( 'Compress Images', 'cheetaho' ),
             	 		'chCancelled' => __( 'Cancelled', 'cheetaho' ),
             	 		'chCompressing' => __( 'Compressing', 'cheetaho' ),
             	 		'chCompressed' => __( 'compressed', 'cheetaho' ),
             	 		'chFile' => __( 'File', 'cheetaho' ),
             	 		'chSizesOptimized' => __( 'Sizes optimized', 'cheetaho' ),
             	 		'chInitialSize' => __( 'Initial size', 'cheetaho' ),
             	 		'chCurrentSize' => __( 'Current size', 'cheetaho' ),
             	 		'chSavings' => __( 'Savings', 'cheetaho' ),
             	 		'chStatus' => __( 'Status', 'cheetaho' ),
             	 		'chShowMoreDetails' => __( 'Show more details', 'cheetaho' ),
             	 		'chError' => __( 'Error', 'cheetaho' ),
             	 		'chLatestError' => __( 'Latest error', 'cheetaho' ),
             	 		'chInternalError' => __( 'Internal error', 'cheetaho' ),
             	 		'chOutOf' => __( 'out of', 'cheetaho' ),
             	 		'chWaiting' => __( 'Waiting', 'cheetaho' ),
             	 ));
             	 
            }
            
            wp_enqueue_style('cheetaho-css', plugins_url('css/cheetaho.css', __FILE__));
        }

        public function cheetaho_ajax_callback()
        {
            $image_id = (int) $_POST['id'];
            $type = false;
            if (isset($_POST['type'])) {
                $type = $_POST['type'];
            }
            
            $this->image_id = $image_id;
            
            if (wp_attachment_is_image($image_id)) {
                if (! parse_url(WP_CONTENT_URL, PHP_URL_SCHEME)) { // no absolute URLs used -> we implement a hack
                    $image_path = get_site_url() . wp_get_attachment_url($image_id); // get the file URL
                } else {
                    $image_path = wp_get_attachment_url($image_id); // get the file URL
                }
                
                $local_image_path = get_attached_file($image_id);
                
                $settings = $this->cheetaho_settings;
                $api_key = isset($settings['api_key']) ? $settings['api_key'] : '';
               
                $data= array();
            	if ( empty( $api_key ) ) {
					$data['error'] = 'There is a problem with your credentials. Please check them in the CheetahO settings section and try again.';
					echo json_encode( array( 'error' => array('message'=>$data['error'] ) ));
					exit;
				}
                
                $status = $this->get_api_status($api_key);
                
                if ($status === false) {
                    $data['error'] = 'There is a problem with your cheetaho account. Maybe quota exceeded.';
                    update_post_meta($image_id, '_cheetaho_size', $data);
                    echo json_encode(array(
                        'error' => array('message'=>$data['error'])
                    ));
                    exit();
                }
                 
                /*
                 * if ( isset( $status['active'] ) && $status['active'] === true ) {
                 *
                 * } else {
                 * echo json_encode( array( 'error' => 'Your account is inactive. Check your account settings' ) );
                 * die();
                 * }
                 */
                
                $result = $this->optimizeImage($image_path, $type, $image_id);
            
                $data = array();
               
                if (! isset($result['error']) && ! isset($result['data']['error']) && isset($result['data']['destURL'])) {
                	
                	if (! isset($result['data']['originalSize']) || (int)$result['data']['originalSize'] == 0) {
                		 echo json_encode(array(
                            'error' => array('message'=>'Could not optimize image. CheetahO can not optimize image. File size 0kb.')
                        ));
                        exit();
                	}
                	
                	
                	$result = $result['data'];
                    $savings_percentage = (int) $result['savedBytes'] / (int) $result['originalSize'] * 100;
                    $data['original_size'] = self::convert_to_kb($result['originalSize']);
                    $data['cheetaho_size'] = self::convert_to_kb($result['newSize']);
                    $data['saved_bytes'] = self::convert_to_kb($result['savedBytes']);
                    $data['newSize'] = $result['newSize'];
                    $data['saved_percent'] = round($savings_percentage, 2) . '%';
                    $data['type'] = $this->type_toText($this->cheetaho_optimization_type);
                    $data['success'] = true;
                    $data['thumbnail'] = $result['destURL'];
                    $data['optimizedImages'] = 1;
                    $data['size_change'] = $result['savedBytes'];
                    $data['originalImagesSize'] = $result['originalSize'];
                    $data['humanReadableLibrarySize'] = size_format($data['size_change'], 2);
                    $data['meta'] = wp_get_attachment_metadata($image_id);
                    $saved_bytes = (int) $data['saved_bytes'];
                     
                    if ($this->replace_new_image($local_image_path, $result['destURL'])) {
                        
                        // get metadata for thumbnails
                        $image_data = wp_get_attachment_metadata($image_id);
                        $this->optimize_thumbnails($image_data);
                        
                        // store info to DB
                        update_post_meta($image_id, '_cheetaho_size', $data);
                       
                        // process thumbnails and store that data too. This can be unset when there are no thumbs
                        $thumbs_data = get_post_meta($image_id, '_cheetaho_thumbs', true);
                       
                        if (! empty($thumbs_data)) {
                            $data['thumbs_data'] = $thumbs_data;
                            $data['optimizedImages'] = $data['optimizedImages'] + count($thumbs_data);
                            foreach ($thumbs_data as $th) {
                            	$data['size_change'] = $data['size_change'] + ($th['original_size'] - $th['cheetaho_size']);
                            	$data['originalImagesSize'] = $data['originalImagesSize'] + $th['original_size'];
                            	$data['newSize'] = $data['newSize'] + $th['cheetaho_size'];
                            }
                             $data['humanReadableLibrarySize'] = size_format($data['size_change'], 2);
                        }
                        
                        $data['html'] = $this->output_result($image_id);
                        echo json_encode($data);
                    } else {
                        echo json_encode(array(
                            'error' => 'Could not overwrite original file. Please check your files permisions.'
                        ));
                        exit();
                    }
                } else {

                	// error or no optimization
                    if (file_exists($image_path)) {
                        
                        $data['original_size'] = self::convert_to_kb(filesize($image_path));
                        $data['error'] = $result['error'];
                        $data['type'] = $result['type'];
                        
                        if ($data['error'] == 'This image can not be optimized') {
                            $data['cheetaho_size'] = 'No savings found';
                            $data['no_savings'] = true;
                        }
                        
                        update_post_meta($image_id, '_cheetaho_size', $data);
                    } else {
                        // file not found
                        $data['error'] = 'File not found';
                    }
                    
                    if (isset($result['data']['error'])) {
                    	$result = array();
                    	$result['error'] = array();
                    	$result['error']['message'] = 'Can not optimize image. Try later or contact CheetahO';
                    }
                    echo json_encode($result);
                }
            }
            die();
        }

        function optimize_thumbnails($image_data)
        {
        	
        	if (isset($image_data['file'])) {
	            $image_id = $this->image_id;
	            if (empty($image_id)) {
	                global $wpdb;
	                $post = $wpdb->get_row($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_value = %s LIMIT 1", $image_data['file']));
	                $image_id = $post->post_id;
	            }
	            
	           
	            $path_parts = pathinfo($image_data['file']);
	            
	            // e.g. 04/02, for use in getting correct path or URL
	            $upload_subdir = $path_parts['dirname'];
	            
	            $upload_dir = wp_upload_dir();
	            
	            // all the way up to /uploads
	            $upload_base_path = $upload_dir['basedir'];
	            $upload_full_path = $upload_base_path . '/' . $upload_subdir;
	            
	            $sizes = array();
	            
	            if (isset($image_data['sizes'])) {
	                $sizes = $image_data['sizes'];
	            }
	            
	            if (! empty($sizes)) {
	                
	                $thumb_path = '';
	                
	                $thumbs_optimized_store = array();
	                $this_thumb = array();
	                $sizes_to_optimize = $this->get_sizes_to_optimize();
	               
	                foreach ($sizes as $key => $size) {
		                if ( !in_array("include_size_$key", $sizes_to_optimize) ) {
							continue;
						}
					
	                    $thumb_path = $upload_full_path . '/' . $size['file'];
	                    
	                    if (file_exists($thumb_path) !== false) {
	                        
	                        $path = wp_get_attachment_image_src($image_id, $key);
	                        $file = $path[0];
	                        
	                        if ($path[3] == false) {
	                        	$file = dirname($path[0]). '/' . $size['file'];
	                        }
	                        
	                        $result = $this->optimizeImage($file, $this->cheetaho_optimization_type, $image_id);
	                        
	                        if (! empty($result) && ! isset($result['error']) && isset($result['data']['destURL'])) {
	                            $result = $result['data'];
	                            $destURL = $result["destURL"];
	                            
	                            if ($this->replace_new_image($thumb_path, $destURL)) {
	                                $this_thumb = array(
	                                    'thumb' => $key,
	                                    'file' => $size['file'],
	                                    'original_size' => $result['originalSize'],
	                                    'cheetaho_size' => $result['newSize'],
	                                    'type' => $this->cheetaho_optimization_type
	                                );
	                                $thumbs_optimized_store[] = $this_thumb;
	                            }
	                        }
	                    }
	                }
	            }
	            
	            if (! empty($thumbs_optimized_store)) {
	                update_post_meta($image_id, '_cheetaho_thumbs', $thumbs_optimized_store, false);
	            }
	            return $image_data;
        	}
        }
        
    	function preg_array_key_exists( $pattern, $array ) {
		    $keys = array_keys( $array );    
		    return (int) preg_grep( $pattern,$keys );
		}
        
        function get_sizes_to_optimize () {
        	$settings = $this->cheetaho_settings;
			$rv = array();

			foreach( $settings as $key => $value ) {
				if ( strpos( $key, 'include_size' ) === 0 && !empty( $value ) ) {
					$rv[] = $key;
				}
			}
			
			return $rv;
        }

        function replace_new_image($image_path, $new_url)
        {
            $fc = false;
            
            $ch = curl_init($new_url);
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
        	curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
        	curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 0 ); 
        	curl_setopt( $ch, CURLOPT_TIMEOUT, 120 ); 
            curl_setopt($ch, CURLOPT_USERAGENT, 'WordPress/' . get_bloginfo('version') . ' CheetahoPlugin/' . self::$plugin_version);
            $result = curl_exec($ch);
            
            if ( $result ) {
            	$fc = file_put_contents($image_path, $result);
            }
            return $fc !== false;
        }

        function optimizeImage($image_path, $type, $image_id)
        {
        	
        	$settings = $this->cheetaho_settings;
        	
        	if (isset($settings['api_key']) && $settings['api_key'] != '') {
        		
        	
	        	if (cheetahoHelper::isProcessablePath($image_path) === false) {
	        		echo json_encode(array(
	                           'error' => array('message'=>'This type of file can not be optimized')
	                ));
	            	exit();
	        	}
	        	
	        	
	        	
	        	if (!isset($settings['api_key']) || $settings['api_key'] == ''){
	        		echo json_encode(array(
	                           'error' => array('message' => 'API key is required. Check CheetahO plugin settings.')
	                ));
	            	exit();
	        	}
	        	

	        	//make image backup if not exist
	        	cheetahoHelper::makeBackup($image_path, $image_id, $settings);
	        	
	            $Cheetaho = new Cheetaho($settings['api_key']);
	            
	            if (! empty($type)) {
	                $lossy = $type;
	            } else {
	                $lossy = $settings['api_lossy'];
	            }
	            
	            $params = array(
	                "url" => $image_path,
	                "lossy" => $lossy
	            );
	           
	       		if ( isset( $settings['quality'] ) && $settings['quality'] > 0 ) {
					$params['quality'] = (int) $settings['quality'];
				}
	            
				set_time_limit(400);
	            $data = $Cheetaho->url($params);
	            
	            
	            //few checks
	        	if (isset($data['data']['error']['fatal']) ) {
	           		echo json_encode(array(
	                           'error' => array('message'=>'System error!')
	                ));
	            	exit(); 
	            }
	             
	           if (isset($data['data']['originalSize']) && isset($data['data']['newSize']) && (int)$data['data']['originalSize'] == 0 && (int)$data['data']['newSize'] == 0) {
	            	echo json_encode(array(
	                           'error' => array('message'=>'Error while we optimized image')
	                ));
	            	exit(); 
	            }
	            
	            $data['type'] = ! empty($type) ? $type : $settings['api_lossy'];
	            
	            if (isset($data['error']) && isset($data['error']['http_code']) && $data['error']['http_code'] == 403) {
	            	$status =  $this->checkStatus();
	            
	                if($status['data']['quota']['exceeded'] == false){
	                	self::cheetahOUpdateNotice('quota', 0, 1);
	                } else { 
	                	self::cheetahOUpdateNotice('quota', 0, 2);
	                }
	            } else {
	            	$status = $this->status;
	            	
	            	if (isset($status['data']['quota']['exceeded']) && $status['data']['quota']['exceeded'] == true) {
	            		self::cheetahOUpdateNotice('quota', 0, 1);
	            	}
	            }
	           
	            
	            return $data;
        	}
        }
        
 

        function get_api_status($api_key)
        {
            return true;
            if (! empty($api_key)) {
                $Cheetaho = new Cheetaho($api_key);
                $status = $Cheetaho->status();
                return $status;
            }
            return false;
        }

        function add_media_columns($columns)
        {
            $columns['original_size'] = 'Original Size';
            $columns['cheetaho_size'] = 'Optimized Size';
            return $columns;
        }

        function cheetaho_media_library_reset()
        {
            $image_id = (int) $_POST['id'];
           
            $image_meta = get_post_meta($image_id, '_cheetaho_size', true);
            
            $original_size = $image_meta['cheetaho_size'];
            delete_post_meta($image_id, '_cheetaho_size');
            delete_post_meta($image_id, '_cheetaho_thumbs');
            
            cheetahoHelper::restoreOriginalImage($image_id);

            echo json_encode(array(
                'success' => true,
                'original_size' => $original_size,
                'html' => $this->optimize_button_html($image_id)
            ));
            wp_die();
        }

        static function convert_to_kb($bytes)
        {
            return round(($bytes / 1024), 2) . ' kB';
        }

        static function folderSize( $path = false) {
	        $total_size = 0;
	        if(file_exists($path)) {
	            $files = scandir($path);
	        } else {
	            return $total_size;
	        }
	        $cleanPath = rtrim($path, '/'). '/';
	        foreach($files as $t) {
	            if ($t<>"." && $t<>"..") 
	            {
	                $currentFile = $cleanPath . $t;
	                if (is_dir($currentFile)) {
	                    $size = self::folderSize($currentFile);
	                    $total_size += $size;
	                }
	                else {
	                    $size = filesize($currentFile);
	                    $total_size += $size;
	                }
	            }
	        }
	        return $total_size;
	    }
	    
        static function type_toText($type)
        {
            if ($type == 1) {
                return 'Lossy';
            }
            
            if ($type == 0) {
                return 'Lossless';
            }
        }

        function fill_media_columns($column_name, $id)
        {
            $original_size = filesize(get_attached_file($id));
            $original_size = self::convert_to_kb($original_size);
            
            $options = get_option('_cheetaho_options');
            $type = isset($options['api_lossy']) ? $options['api_lossy'] : 0;
            
            if (strcmp($column_name, 'original_size') === 0) {
                if (wp_attachment_is_image($id)) {
                    
                    $meta = get_post_meta($id, '_cheetaho_size', true);
                    
                    if (isset($meta['original_size'])) {
                        echo $meta['original_size'];
                    } else {
                        echo $original_size;
                    }
                } else {
                    echo $original_size;
                }
            } else 
                if (strcmp($column_name, 'cheetaho_size') === 0) {
                    
                    if (wp_attachment_is_image($id)) {
                        
                        $meta = get_post_meta($id, '_cheetaho_size', true);
                        
                        // Is it optimized? Show some stats
                        if (isset($meta['cheetaho_size']) && empty($meta['no_savings'])) {
                            echo $this->output_result($id);
                            
                            // Were there no savings, or was there an error?
                        } else {
                            $image_url = wp_get_attachment_url($id);
                            $filename = basename($image_url);
                            echo '<div class="buttonWrap"><button data-setting="' . $type . '" type="button" class="cheetaho_req" data-id="' . $id . '" id="cheetahoid-' . $id . '" data-filename="' . $filename . '" data-url="' . $image_url . '">Optimize This Image</button><span class="cheetahoSpinner"></span></div>';
                            if (! empty($meta['no_savings'])) {
                                echo '<div class="noSavings"><strong>No savings found</strong><br /><small>Type:&nbsp;' . $meta['type'] . '</small></div>';
                            } else 
                                if (isset($meta['error'])) {
                                    $error = $meta['error']['message'];
                                    echo '<div class="cheetahoErrorWrap"><a class="cheetahoError" title="' . $error . '">Failed! Hover here</a></div>';
                                }
                        }
                    } else {
                        echo 'n/a';
                    }
                }
        }

        function output_result($id)
        {
            $image_meta = get_post_meta($id, '_cheetaho_size', true);
            $thumbs_meta = get_post_meta($id, '_cheetaho_thumbs', true);
            $cheetaho_size = $image_meta['cheetaho_size'];
            $type = $image_meta['type'];
            $thumbs_count = count($thumbs_meta);
            $savings_percentage = $image_meta['saved_percent'];
            
            ob_start();
            ?>
<strong><?php echo $cheetaho_size; ?></strong>
<br />
<small>Type:&nbsp;<?php echo $type; ?></small>
<br />
<small>Savings:&nbsp;<?php echo $savings_percentage; ?></small>
<?php if ( !empty( $thumbs_meta ) ) { ?>
<br />
<small><?php echo $thumbs_count; ?> thumbs optimized</small>
<?php } ?>
				<?php if ( empty( $this->cheetaho_settings['show_reset'] ) ) { ?>
<br />
<small class="cheetahoReset" data-id="<?php echo $id; ?>"
	title="Removes Cheetaho metadata associated with this image"> Reset </small>
<span class="cheetahoSpinner"></span>
<?php } ?>
			<?php
            $html = ob_get_clean();
            return $html;
        }

        function optimize_button_html($id)
        {
            $image_url = wp_get_attachment_url($id);
            $filename = basename($image_url);
            
            $html = <<<EOD
	<div class="buttonWrap">
		<button type="button"
				data-setting="$this->cheetaho_optimization_type "
				class="cheetaho_req"
				data-id="$id"
				id="cheetahoid-$id"
				data-filename="$filename"
				data-url="<$image_url">
			Optimize This Image
		</button>
		<small class="cheetahoOptimizationType" style="display:none">$this->cheetaho_optimization_type</small>
		<span class="cheetahoSpinner"></span>
	</div>
EOD;
            
            return $html;
        }

        function cheetaho_media_library_reset_batch()
        {
            $result = null;
            delete_post_meta_by_key('_cheetaho_thumbs');
            delete_post_meta_by_key('_cheetaho_size');
            $result = json_encode(array(
                'success' => true
            ));
            echo $result;
            die();
        }

        function add_settings_link($links)
        {
            $mylinks = array(
                '<a href="' . admin_url('options-general.php?page=cheetaho') . '">Settings</a>'
            );
            return array_merge($links, $mylinks);
        }
        
        /**
         * update notices settings if action  = 1 - set, else is action = 2 - delete
         * Enter description here ...
         * @param unknown_type $notice
         * @param unknown_type $user_id
         * @param unknown_type $action
         */
        
        
	    function cheetahOUpdateNotice( $notice, $user_id = 0, $action = 1 ) {
			global $current_user;
			
			$user_id   = ( 0 === $user_id ) ? $current_user->ID : $user_id;
			$notices   = get_user_meta( $user_id, '_cheetaho_ignore_notices', true );
			
			if ($action == 2) {
				$newitems = array();
				if (!empty($notices)) {
					foreach ($notices  as $item) {
						if ($item != $notice) {
							$newitems[] = $item;
						}
					}
				}
				update_user_meta( $user_id, '_cheetaho_ignore_notices', $newitems );
			} elseif ($action == 1) {
				$notices[] = $notice;
				$notices   = array_filter( $notices );
				$notices   = array_unique( $notices );
				update_user_meta( $user_id, '_cheetaho_ignore_notices', $notices );
				
				$status = $this->status;
				$status['data']['quota']['exceeded'] = false;
				$this->status = $status;
			}
		}
		
		function emptyBackup () {
			cheetahoHelper::emptyBackup();
		}

        function validate_options_data($input)
        {
            $valid = array();
            $error = array();
            $settings = get_option('_cheetaho_options');
            $valid['api_lossy'] = $input['api_lossy'];
            $valid['auto_optimize'] = isset($input['auto_optimize']) ? 1 : 0;
            $valid['quality'] = isset( $input['quality'] ) ? (int) $input['quality'] : 0;
        	$valid['backup'] = isset( $input['backup'] ) ? 1 : 0;
            $sizes = get_intermediate_image_sizes();
            
			foreach ($sizes as $size) {				
				$valid['include_size_' . $size] = isset( $input['include_size_' . $size] ) ? 1 : 0;
			}
            
            if (empty($input['api_key'])) {
                $error[] = 'Please enter API Credentials';
            } else {
                      	
                $status =  $this->checkStatus($input['api_key']);
             
                if(isset($status['error'])){
                	$error[] = 'Your API key is invalid. Check it here http://app.cheetaho.com/admin/api-credentials';
                } else {
	                if(isset($status['data']['quota']['exceeded']) && $status['data']['quota']['exceeded'] == false){
	                	self::cheetahOUpdateNotice('quota', 0, 1);	                	
	                } else {
	                	self::cheetahOUpdateNotice('quota', 0, 2);
	                }
                }
               
                $valid['api_key'] = $input['api_key'];
                
            }
            
            if ( !file_exists(CHEETAHO_BACKUP_FOLDER) && !@mkdir(CHEETAHO_BACKUP_FOLDER, 0777, true) )
                $error[] = "There is something preventing us to create a new folder for backing up your original files. Please make sure that folder <b>" . WP_CONTENT_DIR . '/' . CHEETAHO_UPLOADS_NAME . "</b> has the necessary write and read rights.";
            
            if (! empty($error)) {
                return array(
                    'success' => false,
                    'error' => $error,
                    'valid' => $valid
                );
            } else {
                return array(
                    'success' => true,
                    'valid' => $valid
                );
            }
        }
        
        function updateOptionsValue ($key, $value) {
        	$settings = get_option('_cheetaho_options');

        	if (isset($settings[$key])) {
        		
        		$settings[$key] = $value;
        	} else {
        		$settings = array_merge($settings, array($key=>$value));
        	}

        	update_option('_cheetaho_options', $settings);
        }
    }
    
	function getCheetahoUrl( $action = 'options-general', $arg = array() ) {
		
		switch( $action ) {
	
			case 'closeNotice':
				$url = wp_nonce_url( admin_url( 'admin-post.php?action=cheetahOCloseNotice&notice=' . $arg ), 'cheetahOCloseNotice' );
			break;
	
			case 'options-general':
			default :
				$url  = CHEETAHO_SETTINGS_LINK;
			break;
		}
	
		return $url;
	}
	
	function cheetahOCloseNotice( $notice, $user_id = 0 ) {
		global $current_user;
		$notice = $_GET['notice'];
		
		$user_id   = ( 0 === $user_id ) ? $current_user->ID : $user_id;
		$notices   = get_user_meta( $user_id, '_cheetaho_ignore_notices', true );
		$notices[] = $notice;
		$notices   = array_filter( $notices );
		$notices   = array_unique( $notices );
	
		update_user_meta( $user_id, '_cheetaho_ignore_notices', $notices );
		
		wp_safe_redirect( wp_get_referer() );
		die();
	}
	

	
	
	new WPCheetahO();
}
add_action( 'admin_post_cheetahOCloseNotice', 'cheetahOCloseNotice' );

register_activation_hook(__FILE__, 'cheetahoActivate');
add_action('admin_init', 'CheetahoRedirect');


function cheetahoActivate() {
	add_option('cheetaho_activation_redirect', true);
}

function CheetahoRedirect() {
if (get_option('cheetaho_activation_redirect', false)) {
    delete_option('cheetaho_activation_redirect');
    if(!isset($_GET['activate-multi']))
    {
        exit( wp_redirect(CHEETAHO_SETTINGS_LINK ) );
    	    }
 }
}


