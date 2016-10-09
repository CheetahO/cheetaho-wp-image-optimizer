<?php
class CheetahoUI {
   
	public static function renderCheetahoSettingsMenu($data)
   {
            if (! empty($_POST)) {
                $options = $_POST['_cheetaho_options'];
                $result = $data->validate_options_data($options);
              
                update_option('_cheetaho_options', $result['valid']);
            }
            
            $settings = get_option('_cheetaho_options');
           
            $lossy = isset($settings['api_lossy']) ? $settings['api_lossy'] : 'lossy';
            $auto_optimize = isset($settings['auto_optimize']) ? $settings['auto_optimize'] : 1;
            $quality = isset( $settings['quality'] ) ? $settings['quality'] : 0;
          	$sizes = get_intermediate_image_sizes();

          	foreach ($sizes as $size) {
				$valid['include_size_' . $size] = isset( $settings['include_size_' . $size]) ? $settings['include_size_' . $size] : 1;
			}
            
            $api_key = isset($settings['api_key']) ? $settings['api_key'] : '';
            // $status = $data->get_api_status( $api_key );
            
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
           
			
	<div class="cheetaho-wrap">
		<div class="cheetaho-col cheetaho-col-main">
		 
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
			
			<div class="cheetaho-title">
				CheetahO
				<p class="cheetaho-rate-us">
					<strong>Do you like this plugin?</strong><br> Please take a few seconds to <a href="https://wordpress.org/support/view/plugin-reviews/cheetaho-image-optimizer?rate=5#postform">rate it on WordPress.org</a>!					<br>
					<a class="stars" href="https://wordpress.org/support/view/plugin-reviews/cheetaho-image-optimizer?rate=5#postform"><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span></a>
				</p>
			</div>
			
			<div class="settings-tab">
				<form method="post">
				
					<div class="imagify-sub-header">
						<table class="form-table">
							<tbody>
								<tr>
									<th scope="row"><label for="api_key">API Key</label></th>
									<td>
										<input name="_cheetaho_options[api_key]" type="text" value="<?php echo esc_attr( $api_key ); ?>" size="60">
										Don't have an API Key yet? <a href="http://app.cheetaho.com/" target="_blank" title="Log in to your Cheetaho account">Create one, it's FREE</a>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				
				
					
					<table class="form-table">
						<tbody>
							
							<tr>
								<th scope="row">Optimization Type:</th>
								<td><input type="radio" id="cheetahoLossy"
									name="_cheetaho_options[api_lossy]" value="1"
									<?php checked( 1, $lossy, true ); ?> /> <label for="cheetahoLossy">Lossy</label>
									<p class="settings-info">
										<small><b>Lossy compression: </b>lossy has a better compression rate
										than lossless compression.<br> The resulting image can be not
										100% identical with the original. Works well for photos taken
										with your camera.</small>
									</p> <br /> <input type="radio" id="cheetahoLossless"
									name="_cheetaho_options[api_lossy]" value="0"
									<?php checked( 0, $lossy, true ) ?> /> <label
									for="cheetahoLossless">Lossless</label>
									<p class="settings-info">
										<small><b>Lossless compression: </b> the shrunk image will be identical
										with the original and smaller in size.<br> You can use this when
										you do not want to lose any of the original image's details.
										Choose this if you would like to optimize technical drawings,
										clip art and comics.</small>
									</p> 
									</td>
							</tr>
							<tr>
								<th scope="row">Automatically optimize uploads:</th>
								<td><input type="checkbox" id="auto_optimize"
									name="_cheetaho_options[auto_optimize]" value="1"
									<?php checked( 1, $auto_optimize, true ); ?> /></td>
							</tr>
							<tr class="with-tip">
					        	<th scope="row">JPEG quality:</th>
					        	<td>
									<select name="_cheetaho_options[quality]">
										<?php $i = 0 ?>
										
										<?php foreach ( range(100, 40) as $number ) { ?>
											<?php if ( $i === 0 ) { ?>
												<?php echo '<option value="0">Intelligent lossy (recommended)</option>'; ?>
											<?php } ?>
											<?php if ($i > 0) { ?>
												<option value="<?php echo $number ?>" <?php selected( $quality, $number, true); ?>>
												<?php echo $number; ?>
											<?php } ?>
												</option>
											<?php $i++ ?>
										<?php } ?>
									</select>
									<p class="settings-info">
										<small>Advanced users can force the quality of images. 
										Specifying a quality level of 40 will produce the lowest image quality (highest compression level).<br/>						    We therefore recommend keeping the <strong>Intelligent Lossy</strong> setting, which will not allow a resulting image of unacceptable quality.<br />
									    This setting will be ignored when using the <strong>lossless</strong> optimization mode.</small>
									</p> <br />
					        	</td>
					        </tr>
					          <tr class="cheetaho-advanced-settings">
						            <th scope="row">Image Sizes to optimize:</th>
									<td>
									<p class="cheetaho-sizes-comment">
										<small>You can choose witch image size created by WordPress you want to compress.	
										The original size is automatically optimized by CheetahO.	
										<span>Do not forget that each additional image size will affect your CheetahO monthly usage!</span>
										</small></p>
						            	<br />
										<?php $size_count = count($sizes); ?>
						            	<?php $i = 0; ?>
						            	<?php foreach($sizes as $size) { ?>
						            	<?php $size_checked = isset( $valid['include_size_' . $size] ) ? $valid['include_size_' . $size] : 1; ?>
						                <label for="<?php echo "cheetaho_size_$size" ?>"><input type="checkbox" id="cheetaho_size_<?php echo $size ?>" name="_cheetaho_options[include_size_<?php echo $size ?>]" value="1" <?php checked( 1, $size_checked, true ); ?>/>&nbsp;<?php echo $size ?></label>&nbsp;&nbsp;&nbsp;&nbsp;
						            	<?php $i++ ?>
						            	<?php if ($i % 3 == 0) { ?>
						            		<br />
						            	<?php } ?>
     							        <?php } ?>
						            </td>
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
		</div>
		<div class="cheetaho-col cheetaho-col-sidebar">
			<?=self::renderStats()?>
			<?=self::renderSupportBlock()?>
			<?=self::renderContactsBlock()?>
		</div>
</div>
<?php
        }
        
        
        public static function renderStats() {
        	?>
        	<div class="cheetaho-block stats">
				<h3>Optimization Stats</h3>
				<hr />
				<?php $data = cheetahoHelper::getStats()?>
				<ul>
					<li>Images optimized: <?=$data['total_images']?></li>
					<li>Total images original size: <?=size_format($data['total_size_orig_images'], 2)?></li>
					<li>Total images size optimized: <?=size_format($data['total_size_images'], 2)?></li>
					<li>Saved size in % using CheetahO: <?=$data['total_perc_optimized']?>%</li>
				</ul>
			</div>
			<?php 
        
        }
        
        public static function renderSupportBlock() {
        ?>
        	<div class="cheetaho-block stats">
				<h3>Support CheetahO</h3>
				<hr />
				<p>Would you like to help support development of this plugin?</p>
				<p><a target="_blank" href="https://wordpress.org/support/view/plugin-reviews/cheetaho-image-optimizer#postform">Write a review.</a></p>
				<p>Contribute directly via <a target="_blank"  href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8EBKEZMR58UK4">Paypal</a>.</p>
				<p>Or just say to world about us via: <br />
				<a class="cheetaho-btn cheetaho-twitter"  href="https://twitter.com/intent/tweet?url=http://cheetaho.com&text=Thanks for @cheetahocom for good image optimization service.&hashtags=seo%2C%20webperf%2C%20webdev" target="_blank" class="btn btn-twitter">Twitter</a>
				<a class="cheetaho-btn cheetaho-google" href="https://plus.google.com/share?url=http://cheetaho.com" target="_blank">Google+</a>
				<a class="cheetaho-btn cheetaho-facebook" href="http://www.facebook.com/sharer.php?u=http://cheetaho.com&t=Thanks for @cheetahocom for good image optimization service" target="_blank">Facebook</a>
				</p>
			</div>
        <?php 
        }
        
        public static function renderContactsBlock () {
        ?>
        	<div class="cheetaho-block stats">
				<h3>Contact Us:</h3>
				<hr />
				<p>Found Bug? Have questions or suggestions. Please write us an email: <a target="_blank"  href="mailto:support@cheetaho.com">support@cheetaho.com</a> or you can fill our contact form <a target="_blank" href="http://cheetaho.com/contact-us/">here</a>.</p>
			</div>
        <?php 
        }
}