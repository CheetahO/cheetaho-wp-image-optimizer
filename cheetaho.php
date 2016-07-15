<?php
/**
 * Plugin Name: CheetahO Image Optimizer
 * Plugin URI: http://cheetaho.com/
 * Description: CheetahO optimizes images automatically. Check your <a href="options-general.php?page=cheetaho" target="_blank">Settings &gt; CheetahO</a> page on how to start optimizing your image library and make your website load faster. 
 * Version: 1.0
 * Author: CheetahO
 * Author URI: http://cheetaho.com
 */


if (! class_exists('WPCheetahO')) {

    class WPCheetahO
    {

        private $image_id;

        private $cheetaho_settings = array();

        private $thumbs_data = array();

        private $cheetaho_optimization_type = 'lossy';

        public static $plugin_version = '1.0';

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
                add_filter('wp_generate_attachment_metadata', array(
                    &$this,
                    'optimize_thumbnails'
                ));
                add_action('add_attachment', array(
                    &$this,
                    'cheetaho_uploader_callback'
                ));
            }
            
            // add_action( 'admin_menu', array( &$this, 'cheetahoMenu' ) );
            add_action('admin_menu', array(
                &$this,
                'registerSettingsPage'
            )); // display SP in Settings menu
        }

        public function registerSettingsPage()
        {
            add_options_page('CheetahO Settings', 'CheetahO', 'manage_options', 'cheetaho', array(
                $this,
                'renderCheetahoSettingsMenu'
            ));
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
                
                $result = $this->optimizeImage($image_path, $type);
                
                $image_path = get_attached_file($image_id);
                
                if (! isset($result['error'])) {
                    $result = $result['data'];
                    
                    $savings_percentage = (int) $result['savedBytes'] / (int) $result['originalSize'] * 100;
                    $data['original_size'] = self::convert_to_kb($result['originalSize']);
                    $data['cheetaho_size'] = self::convert_to_kb($result['newSize']);
                    $data['saved_bytes'] = self::convert_to_kb($result['savedBytes']);
                    $data['saved_percent'] = round($savings_percentage, 2) . '%';
                    $data['type'] = $this->type_toText($this->cheetaho_optimization_type);
                    $data['success'] = true;
                    $data['meta'] = wp_get_attachment_metadata($image_id);
                    $saved_bytes = (int) $data['saved_bytes'];
                    
                    if ($this->replace_new_image($image_path, $result['destURL'])) {
                        update_post_meta($image_id, '_cheetaho_size', $data);
                    } else {
                        // writing image failed
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
            if ($hook == 'options-media.php' || $hook == 'upload.php' || $hook == 'settings_page_cheetaho') {
                wp_enqueue_script('jquery');
                wp_enqueue_script( 'async-js', plugins_url( '/js/async.js', __FILE__ ) );
                wp_enqueue_script('cheetaho-js', plugins_url('/js/cheetaho.js', __FILE__), array(
                    'jquery'
                ));
                wp_localize_script('cheetaho-js', 'cheetaho_object', array(
                    'url' => admin_url('admin-ajax.php')
                ));
                wp_enqueue_style('cheetaho-css', plugins_url('css/cheetaho.css', __FILE__));
            }
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
                
                $status = $this->get_api_status($api_key);
                
                if ($status === false) {
                    $data['error'] = 'There is a problem with your credentials. Please check settings section.';
                    update_post_meta($image_id, '_cheetaho_size', $data);
                    echo json_encode(array(
                        'error' => $data['error']
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
                
                $result = $this->optimizeImage($image_path, $type);
                
                $data = array();
                
                if (! isset($result['error'])) {
                    $result = $result['data'];
                    $savings_percentage = (int) $result['savedBytes'] / (int) $result['originalSize'] * 100;
                    $data['original_size'] = self::convert_to_kb($result['originalSize']);
                    $data['cheetaho_size'] = self::convert_to_kb($result['newSize']);
                    $data['saved_bytes'] = self::convert_to_kb($result['savedBytes']);
                    $data['saved_percent'] = round($savings_percentage, 2) . '%';
                    $data['type'] = $this->type_toText($this->cheetaho_optimization_type);
                    $data['success'] = true;
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
                    }
                    echo json_encode($result);
                }
            }
            die();
        }

        function optimize_thumbnails($image_data)
        {
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
                
                foreach ($sizes as $key => $size) {
                    
                    $thumb_path = $upload_full_path . '/' . $size['file'];
                    
                    if (file_exists($thumb_path) !== false) {
                        
                        $path = wp_get_attachment_image_src($image_id, $key);
                        
                        $result = $this->optimizeImage($path[0], $this->cheetaho_optimization_type);
                        
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

        function replace_new_image($image_path, $new_url)
        {
            $fc = false;
            
            $ch = curl_init($new_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_USERAGENT, 'WordPress/' . get_bloginfo('version') . ' CheetahoPlugin/' . self::$plugin_version);
            $result = curl_exec($ch);
            $fc = file_put_contents($image_path, $result);
            return $fc !== false;
        }

        function optimizeImage($image_path, $type)
        {
            $settings = $this->cheetaho_settings;
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
            
            $data = $Cheetaho->url($params);
            
            $data['type'] = ! empty($type) ? $type : $settings['api_lossy'];
            
            return $data;
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
            $columns['cheetago_size'] = 'Optimized Size';
            return $columns;
        }

        function cheetaho_media_library_reset()
        {
            $image_id = (int) $_POST['id'];
            $image_meta = get_post_meta($image_id, '_cheetago_size', true);
            $original_size = $image_meta['cheetago_size'];
            delete_post_meta($image_id, '_cheetago_size');
            delete_post_meta($image_id, '_cheetago_thumbs');
            echo json_encode(array(
                'success' => true,
                'original_size' => $original_size,
                'html' => $this->optimize_button_html($image_id)
            ));
            die();
        }

        static function convert_to_kb($bytes)
        {
            return round(($bytes / 1024), 2) . ' kB';
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
                if (strcmp($column_name, 'cheetago_size') === 0) {
                    
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
				<?php if ( !empty( $this->cheetaho_settings['show_reset'] ) ) { ?>
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

        public function renderCheetahoSettingsMenu()
        {
            if (! empty($_POST)) {
                $options = $_POST['_cheetaho_options'];
                $result = $this->validate_options_data($options);
                update_option('_cheetaho_options', $result['valid']);
            }
            
            $settings = get_option('_cheetaho_options');
            $lossy = isset($settings['api_lossy']) ? $settings['api_lossy'] : 'lossy';
            $auto_optimize = isset($settings['auto_optimize']) ? $settings['auto_optimize'] : 1;
            
            $api_key = isset($settings['api_key']) ? $settings['api_key'] : '';
            // $status = $this->get_api_status( $api_key );
            
            $icon_url = admin_url() . 'images/';
            /*
             * if ( $status !== false && isset( $status['active'] ) && $status['active'] === true ) {
             * $icon_url .= 'yes.png';
             * $status_html = '<p class="apiStatus">Your credentials are valid <span class="apiValid" style="background:url(' . "'$icon_url') no-repeat 0 0" . '"></span></p>';
             * } else {
             * $icon_url .= 'no.png';
             * $status_html = '<p class="apiStatus">There is a problem with your credentials <span class="apiInvalid" style="background:url(' . "'$icon_url') no-repeat 0 0" . '"></span></p>';
             * }
             */
            
            ?>
<h1 class="cheetaho-title">Cheetaho Settings</h1>
<?php if ( isset( $result['error'] ) ) { ?>
<div class="cheetaho error mb-30">
						<?php foreach( $result['error'] as $error ) { ?>
							<p><?php echo $error; ?></p>
						<?php } ?>
						</div>
<?php } else if ( isset( $result['success'] ) ) { ?>
<div class="cheetaho updated mb-30">
	<p>Settings saved.</p>
</div>
<?php } ?>

					<?php if ( !function_exists( 'curl_init' ) ) { ?>
<p class="curl-warning mb-30">
	<strong>Warning: </strong>CURL is not available. If you would like to
	use this plugin please install CURL
</p>
<?php } ?>

<div class="settings-tab">
	<form method="post">
		<a href="http://app.cheetaho.com/" target="_blank"
			title="Log in to your Cheetaho account">Cheetaho API settings</a>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">API Key:</th>
					<td><input name="_cheetaho_options[api_key]" type="text"
						value="<?php echo esc_attr( $api_key ); ?>" size="60"></td>
				</tr>
				<tr>
					<th scope="row">Optimization Type:</th>
					<td><input type="radio" id="cheetahoLossy"
						name="_cheetaho_options[api_lossy]" value="1"
						<?php checked( 1, $lossy, true ); ?> /> <label for="cheetahoLossy">Lossy</label>
						<p class="settings-info">
							<b>Lossy compression: </b>lossy has a better compression rate
							than lossless compression.<br> The resulting image can be not
							100% identical with the original. Works well for photos taken
							with your camera.
						</p> <br /> <input type="radio" id="cheetahoLossless"
						name="_cheetaho_options[api_lossy]" value="0"
						<?php checked( 0, $lossy, true ) ?> /> <label
						for="cheetahoLossless">Lossless</label>
						<p class="settings-info">
							<b>Lossless compression: </b> the shrunk image will be identical
							with the original and smaller in size.<br> You can use this when
							you do not want to lose any of the original image's details.
							Choose this if you would like to optimize technical drawings,
							clip art and comics.
						</p> <br /></td>
				</tr>
				<tr>
					<th scope="row">Automatically optimize uploads:</th>
					<td><input type="checkbox" id="auto_optimize"
						name="_cheetaho_options[auto_optimize]" value="1"
						<?php checked( 1, $auto_optimize, true ); ?> /></td>
				</tr>					        
						      <?php
            /*
             * <tr>
             * <th scope="row">API status:</th>
             * <td>
             * <?php echo $status_html ?>
             * </td>
             * </tr>
             */
            ?>
						      				        
						    </tbody>
		</table>
		<input type="submit" name="cheetahoSaveAction"
			class="button button-primary" value="Save Settings" />
	</form>
</div>

<?php
        }

        function add_settings_link($links)
        {
            $mylinks = array(
                '<a href="' . admin_url('options-general.php?page=cheetaho') . '">Settings</a>'
            );
            return array_merge($links, $mylinks);
        }

        function validate_options_data($input)
        {
            $valid = array();
            $error = array();
            $valid['api_lossy'] = $input['api_lossy'];
            $valid['auto_optimize'] = isset($input['auto_optimize']) ? 1 : 0;
            
            if (empty($input['api_key'])) {
                $error[] = 'Please enter API Credentials';
            } else {
                
                // $status = $this->get_api_status( $input['api_key']);
                
                // if ( $status !== false ) {
                
                // if ( isset($status['active']) && $status['active'] === true ) {
                $valid['api_key'] = $input['api_key'];
                // } else {
                // $error[] = 'There is a problem with your credentials. Please check them from your CheetahO account.';
                // }
                
                // } else {
                // $error[] = 'Your API key is invalid. Check it here http://app.cheetaho.com/admin/api-credentials';
                // }
            }
            
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
    }
}

new WPCheetahO();
