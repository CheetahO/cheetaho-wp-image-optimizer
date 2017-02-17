<?php
class CheetahoUI {
	function __construct() {
	}
   
	public static function renderCheetahoSettingsMenu($data)
   {
            if (isset($_POST['cheetahoSaveAction']) && ! empty($_POST)) {
                $options = $_POST['_cheetaho_options'];
                $result = $data->validate_options_data($options);
              
                update_option('_cheetaho_options', $result['valid']);
            }
            
	    	//empty backup
	        if(isset($_POST['emptyBackup'])) {
	            $data->emptyBackup();
	        }
            
            $settings = get_option('_cheetaho_options');
          
            $lossy = (isset($settings['api_lossy']) && $settings['api_lossy'] != '') ? $settings['api_lossy'] : true;
            $auto_optimize = isset($settings['auto_optimize']) ? $settings['auto_optimize'] : 1;
            $backup = isset($settings['backup']) ? $settings['backup'] : 1;
            $quality = isset( $settings['quality'] ) ? $settings['quality'] : 0;
          	$sizes = get_intermediate_image_sizes();
          	$backupFolderSize =  size_format(WPCheetahO::folderSize(CHEETAHO_BACKUP_FOLDER), 2);
          	

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
				<p><?php _e( 'Settings saved.', 'CheetahO')?></p>
			</div>
			<?php } ?>
			
								<?php if ( !function_exists( 'curl_init' ) ) { ?>
			<p class="curl-warning mb-30">
				<strong><?php _e( 'Warning:', 'CheetahO' )?> </strong><?php _e( 'CURL is not available. If you would like to use this plugin please install CURL', 'CheetahO')?>
			</p>
			<?php } ?>
			
			<div class="cheetaho-title">
				CheetahO v<?=CHEETAHO_VERSION?>
				<p class="cheetaho-rate-us">
					<strong><?php _e( 'Do you like this plugin?', 'CheetahO')?></strong><br> <?php _e( 'Please take a few seconds to', 'CheetahO')?> <a href="https://wordpress.org/support/view/plugin-reviews/cheetaho-image-optimizer?rate=5#postform"><?php _e( 'rate it on WordPress.org', 'CheetahO')?></a>!					<br>
					<a class="stars" href="https://wordpress.org/support/view/plugin-reviews/cheetaho-image-optimizer?rate=5#postform"><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span></a>
				</p>
			</div>
			
			<div class="settings-tab">
				<form method="post">
				
					<div class="cheetaho-sub-header">
						<table class="form-table">
							<tbody>
								<tr>
									<th scope="row"><label for="api_key"><?php _e( 'API Key', 'CheetahO')?></label></th>
									<td>
										<input name="_cheetaho_options[api_key]" type="text" value="<?php echo esc_attr( $api_key ); ?>" size="60">
										<?php _e( 'Do not have an API Key yet?', 'CheetahO')?> <a href="http://app.cheetaho.com/" target="_blank" title="Log in to your Cheetaho account"><?php _e( 'Create one, it is FREE', 'CheetahO' )?></a>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				
				
					
					<table class="form-table">
						<tbody>
							
							<tr>
								<th scope="row"><?php _e( 'Optimization Type:', 'CheetahO')?></th>
								<td>
								<input type="radio" id="cheetahoLossy" name="_cheetaho_options[api_lossy]" value="1" <?php checked( 1, $lossy, true ); ?> /> <label for="cheetahoLossy"><?php _e( 'Lossy', 'CheetahO')?></label>
									<p class="settings-info">
										<small><b><?php _e( 'Lossy compression:', 'CheetahO')?> </b><?php _e( 'lossy has a better compression rate than lossless compression.<br> The resulting image can be not 100% identical with the original. Works well for photos taken with your camera.', 'CheetahO')?></small>
									</p> <br /> 
									<input type="radio" id="cheetahoLossless" name="_cheetaho_options[api_lossy]" value="0" <?php checked( 0, $lossy, true ) ?> /> 
									<label for="cheetahoLossless"><?php _e( 'Lossless', 'CheetahO')?></label>
									<p class="settings-info">
										<small><b><?php _e( 'Lossless compression:', 'CheetahO')?> </b> <?php _e( 'the shrunk image will be identical with the original and smaller in size.', 'CheetahO');?><br /> 
										<?php _e( 'You can use this when you do not want to lose any of the original images details. Choose this if you would like to optimize technical drawings, clip art and comics.', 'CheetahO')?></small>
									</p> 
									</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'Automatically optimize uploads:', 'CheetahO')?></th>
								<td>
									<input type="checkbox" id="auto_optimize" name="_cheetaho_options[auto_optimize]" value="1" <?php checked( 1, $auto_optimize, true ); ?> />
								</td>
							</tr>
							<tr class="with-tip">
					        	<th scope="row"><?php _e( 'JPEG quality:', 'CheetahO')?></th>
					        	<td>
									<select name="_cheetaho_options[quality]">
										<?php $i = 0 ?>
										
										<?php foreach ( range(100, 40) as $number ) { ?>
											<?php if ( $i === 0 ) { ?>
												<?php echo '<option value="0">'.__('Intelligent lossy (recommended)', 'Cheetaho').'</option>'; ?>
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
										<small><?php _e( 'Advanced users can force the quality of images. Specifying a quality level of 40 will produce the lowest image quality (highest compression level).', 'CheetahO')?><br/>
										<?php _e( 'We therefore recommend keeping the <strong>Intelligent Lossy</strong> setting, which will not allow a resulting image of unacceptable quality.', 'CheetahO')?><br />
									    <?php _e( 'This setting will be ignored when using the <strong>lossless</strong> optimization mode.', 'CheetahO')?>
									    </small>
									</p> <br />
					        	</td>
					        </tr>
					          <tr class="cheetaho-advanced-settings">
						            <th scope="row"><?php _e( 'Image Sizes to optimize:', 'CheetahO')?></th>
									<td>
									<p class="cheetaho-sizes-comment">
										<small><?php _e( 'You can choose witch image size created by WordPress you want to compress. The original size is automatically optimized by CheetahO.', 'CheetahO')?>	
										<span><?php _e( 'Do not forget that each additional image size will affect your CheetahO monthly usage!', 'CheetahO')?></span>
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
					        				        
			            
				              <tr>
				              <th scope="row"><?php _e( 'Images backups:', 'CheetahO')?></th>
				              <td>
				              <input type="checkbox" id="backup"
									name="_cheetaho_options[backup]" value="1"
									<?php checked( 1, $backup, true ); ?> />
									
										<small class="cheetaho-sizes-comment">	
										<span><?php _e( 'You need to have backup active in order to be able to restore images to originals.', 'CheetahO')?></span>
										</small>
										<p>
										<?php _e( 'Your backup folder size is now:', 'CheetahO')?>
										<form action="" method="POST">
				                            <?php echo($backupFolderSize);?>
				                            <input type="submit"  style="margin-left: 15px; vertical-align: middle;" class="button button-secondary" name="emptyBackup" onclick="confirm('<?php _e('Are you sure want to remove images from backup folder?', 'CheetahO')?>');" value="Empty backups"/>
				                        </form>
										</p>
				              </td>
				              </tr>
			             
									      				        
									    </tbody>
					</table>
					<input type="submit" name="cheetahoSaveAction" class="button button-primary" value="Save Settings" />
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
				<h3><?php _e( 'Optimization Stats', 'CheetahO');?></h3>
				<hr />
				<?php $data = cheetahoHelper::getStats()?>
				<ul>
					<li><?php _e( 'Images optimized:', 'CheetahO')?> <span id="optimized-images"><?=$data['total_images']?></span></li>
					<li><?php _e( 'Total images original size:', 'CheetahO')?>  <span data-bytes="<?=$data['total_size_orig_images']?>" id="original-images-size"><?=size_format($data['total_size_orig_images'], 2)?></span></li>
					<li><?php _e( 'Total images size optimized:', 'CheetahO')?>   <span data-bytes="<?=$data['total_size_images']?>" id="optimized-size"><?=size_format($data['total_size_images'], 2)?></span></li>
					<li><?php _e( 'Saved size in % using CheetahO:', 'CheetahO')?>  <span id="savings-percentage"> <?=$data['total_perc_optimized']?>%</span></li>
				</ul>
			</div>
			<?php 
        
        }
        
        public static function renderSupportBlock() {
        ?>
        	<div class="cheetaho-block stats">
				<h3><?php _e( 'Support CheetahO', 'CheetahO')?></h3>
				<hr />
				<p><?php _e( 'Would you like to help support development of this plugin?', 'CheetahO')?></p>
				<p><a target="_blank" href="https://wordpress.org/support/view/plugin-reviews/cheetaho-image-optimizer#postform"><?php _e( 'Write a review.', 'CheetahO')?></a></p>
				<p><?php _e( 'Contribute directly via', 'CheetahO')?> <a target="_blank"  href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8EBKEZMR58UK4">Paypal</a>.</p>
				<p><?php _e( 'Or just say to world about us via:', 'CheetahO')?> <br />
				<a class="cheetaho-btn cheetaho-twitter"  href="https://twitter.com/intent/tweet?url=http://cheetaho.com&text=Thanks for @cheetahocom for good image optimization service.&hashtags=seo%2C%20webperf%2C%20webdev%2C%20" target="_blank" class="btn btn-twitter">Twitter</a>
				<a class="cheetaho-btn cheetaho-google" href="https://plus.google.com/share?url=http://cheetaho.com" target="_blank">Google+</a>
				<a class="cheetaho-btn cheetaho-facebook" href="https://www.facebook.com/dialog/feed?app_id=714993091988558&display=popup&caption=Thanks%20for%20@cheetahocom%20for%20good%20image%20optimization%20service&link=http://cheetaho.com&redirect_uri=http://cheetaho.com" target="_blank">Facebook</a>
				</p>
			</div>
        <?php 
        }
        
        public static function renderContactsBlock () {
        ?>
        	<div class="cheetaho-block stats">
				<h3><?php _e( 'Contact Us', 'CheetahO')?>:</h3>
				<hr />
				<p><?php _e( 'Found Bug? Have questions or suggestions. Please write us an email:', 'CheetahO')?> <a target="_blank"  href="mailto:support@cheetaho.com">support@cheetaho.com</a> <?php _e( 'or you can fill our contact form', 'CheetahO')?> <a target="_blank" href="http://cheetaho.com/contact-us/"><?php _e( 'here', 'CheetahO')?></a>.</p>
			</div>
        <?php 
        }
        
        
		public static function displayQuotaExceededAlert($data = array()) 
	    { 
        	$current_screen  = get_current_screen();
			$ignored_notices = get_user_meta( $GLOBALS['current_user']->ID, '_cheetaho_ignore_notices', true );

			if (in_array( 'quota', (array) $ignored_notices  ) || empty($data)) {
				return;
			}
			?>    
	        <br/>
	        <br/>
	        <div class="wrap cheetaho-alert-danger">
	        	<a href="<?= getCheetahoUrl( 'closeNotice', 'quota' ); ?>" class="cheetaho-notice-close dark" title="<?php _e( 'Dismiss this notice', 'CheetahO' ); ?>"><span class="dashicons dashicons-dismiss"></span></a>
	            <h3><?php _e( 'CheetahO image optimization Quota Exceeded', 'CheetahO' ); ?></h3>
	            <p><?php _e( 'The plugin has optimized', 'CheetahO' ); ?> <strong><?=(isset($data['data']['quota']['optimizedImages']) ? $data['data']['quota']['optimizedImages'] : 0)?> <?php _e( 'images', 'CheetahO' ); ?></strong>. <?php _e( 'Come back on this date to continue optimization.', 'CheetahO' ); ?></p>
	            <p><?php _e( 'To continue to optimize your images now, log in to your CheetahO account to upgrade your plan.', 'CheetahO' ); ?></p>
	            <p>
	             	<a class='button button-primary' href='<?= CHEETAHO_APP_URL?>admin/billing/plans' target='_blank'><?php _e( 'Upgrade plan now', 'CheetahO' ); ?></a>
	             	<a class='button button-secondary' href='<?= getCheetahoUrl( 'closeNotice', 'quota' ); ?>' ><?php _e( 'I upgraded plan. Close message', 'CheetahO' ); ?></a>
	            </p>  
	        </div> <?php 
	    }
	    
		public static function displayApiKeyAlert($settings) 
	    { 
			$current_screen  = get_current_screen();
			$ignored_notices = get_user_meta( $GLOBALS['current_user']->ID, '_cheetaho_ignore_notices', true );

			if (( isset( $current_screen ) && ( 'settings_page_cheetaho' === $current_screen->base || 'settings_page_cheetaho-network' === $current_screen->base ) ) || in_array( 'welcome', (array) $ignored_notices ) || (isset($settings['api_key']) && $settings['api_key'] != '')  ) {
				return;
			}
			?>
			<div class="cheetaho-welcome">
				<div class="cheetaho-title">
					<span class="baseline">
						<?php _e( 'Welcome to CheetahO image optimization!', 'CheetahO' ); ?>
					</span>
					<a href="<?= getCheetahoUrl( 'closeNotice', 'welcome' ); ?>" class="cheetaho-notice-close" title="<?php _e( 'Dismiss this notice', 'CheetahO' ); ?>"><span class="dashicons dashicons-dismiss"></span></a>
				</div>
				<div class="cheetaho-settings-section">
					<div class="cheetaho-columns counter">
						<div class="col-1-3">
							<div class="cheetaho-col-content">
								<p class="cheetaho-col-title"><?php _e( 'Create CheetahO Account', 'CheetahO' ); ?></p>
								<p class="cheetaho-col-desc"><?php _e( 'Don\'t have an CheetahO account? Create account in few seconds and optimize your images!', 'CheetahO' ); ?></p>
								<p><a target="_blank" href="<?php echo CHEETAHO_APP_URL; ?>register" class="button button-primary"><?php _e( 'Sign up, It\'s FREE!', 'CheetahO' ); ?></a></p>
							</div>
						</div>
						<div class="col-1-3">
							<div class="cheetaho-col-content">
								<p class="cheetaho-col-title"><?php _e( 'Get API Key', 'CheetahO' ); ?></p>
								<p class="cheetaho-col-desc"><?php printf( __( 'Go to CheetahO API key page. Copy key and come back here.', 'CheetahO' )); ?></p>
								<p>
									<a href="<?= CHEETAHO_APP_URL?>admin/api-credentials" class="button button-primary"><?php _e( 'Get API key', 'CheetahO' ); ?></a></p>
							</div>
						</div>
						<div class="col-1-3">
							<div class="cheetaho-col-content">
								<p class="cheetaho-col-title"><?php _e( 'Configure it', 'CheetahO' ); ?></p>
								<p class="cheetaho-col-desc"><?php _e( 'It’s almost done! Now you need to configure your plugin.', 'CheetahO' ); ?></p>
								<p><a href="<?=CHEETAHO_SETTINGS_LINK?>" class="button button-primary"><?php _e( 'Go to Settings', 'CheetahO' ); ?></a></p>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php
	}
	
	public static function displayBulkForm ($data, $images) {
		?>
		<div id="bulk-msg"></div>
		<div class="cheetaho-wrap cheetaho-bulk">
		<div class="cheetaho-col cheetaho-col-main">
			<div class="cheetaho-title">CheetahO v<?=CHEETAHO_VERSION?> <p class="cheetaho-rate-us">
					<strong><?php _e( 'Do you like this plugin?', 'CheetahO')?></strong><br /> <?php _e( 'Please take a few seconds to', 'CheetahO')?> <a href="https://wordpress.org/support/view/plugin-reviews/cheetaho-image-optimizer?rate=5#postform"><?php _e( 'rate it on WordPress.org', 'CheeahO')?></a>! <br />
					<a class="stars" href="https://wordpress.org/support/view/plugin-reviews/cheetaho-image-optimizer?rate=5#postform"><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span></a>
				</p>
			</div>
			<div class="settings-tab">
			 	<?php $totalToOptimize= $images['uploadedImages']?>
				<?php if ($totalToOptimize == 0 || count($images['uploaded_images']) == 0):?>
					<div class="cheetaho-alert-success"><?php _e( 'Congratulations! Your media library has been successfully optimized! Come back here when you will have new images to optimize', 'CheetahO')?></div>
				<?php else:?>
				<div class="cheetaho-bulk-info"><?php _e( 'Here you can start optimizing your entire library. Press the big button to start improving your website speed instantly! We can optimize your original images size and', 'CheetahO')?> <b><?php _e('thumbnails', 'CheetahO')?></b> <a href="#"  class="info-btn"><i>i</i></a>.
					<span class="popup-container hide">
						<h3><?php _e( 'What are Thumbnails?', 'CheetahO')?></h3>
						<?php _e('Thumbnails are smaller images generated by your WP theme. Most themes generate between 3 and 6 thumbnails for each Media Library image.', 'CheetahO')?><br/><br/>
						<?php _e('The thumbnails also generate traffic on your website pages and they influence your websites speed', 'CheetahO')?>.<br/><br/>
						<?php _e('It is highly recommended that you include thumbnails in the optimization as well.', 'CheetahO')?><br/>
					</span><?php _e('Please check CheetahO setings', 'CheetahO')?> <a href="<?=CHEETAHO_SETTINGS_LINK?>"><?php _e('page', 'CheetahO')?></a> <?php _e('for available plugin options', 'CheetahO')?>. 
				 </div>
				<p>&nbsp;</p>
					
			
				 <div class="optimize">
					<div class="progressbar" id="compression-progress-bar" data-number-to-optimize="<?=$totalToOptimize?>" data-amount-optimized="0">
						<div id="progress-size" class="progress" style="width: 60%;">
						</div>
						<div class="numbers">
							<span id="optimized-so-far">0</span>/<span><?=$totalToOptimize?></span>
							<span id="percentage">(0%)</span>
						</div>
					</div>
					<div id="bulk-actions" class="optimization-buttons">
						<input type="submit" name="id-start" id="id-start"  onclick="startAction(); return false;" class="button button-primary button-hero visible" value="<?php _e('Start Bulk Optimization', 'CheetahO')?>">
						<input type="submit" name="id-optimizing" id="id-optimizing" onmouseover="optimizingAction(); return false;" class="button button-primary button-hero" value="<?php _e('Optimizing...', 'CheetahO')?>">
						<input type="submit" name="id-cancel" onclick="cancelAction(); return false;" id="id-cancel" class="button button-primary button-hero red" value="<?php _e('Cancel', 'CheetahO')?>">
						<input type="submit" name="id-cancelling" id="id-cancelling" class="button button-primary button-hero red" value="<?php _e('Cancelling...', 'CheetahO')?>">	
					</div>
				</div>	
				<p><b><?php _e('Remember', 'CheetahO')?>:</b> <?php _e('For the plugin to do the work, you need to keep this page open. But no worries: if ir will stop, you can continue where you left off!', 'CheetahO')?></p>
				       
		        <?php endif;?>
				
			</div>
			<?php if ($totalToOptimize > 0 && count($images['uploaded_images']) > 0):?>
			<script type="text/javascript">
			<?php
			
			echo 'jQuery(function() { bulkOptimization(' . json_encode( $images['uploaded_images'] ) . ')})';
			
			?>
			</script>
			
			 <table class="wp-list-table widefat fixed striped media whitebox" id="optimization-items" >
					<thead>
						<tr>
							<?php // column-author WP 3.8-4.2 mobile view ?>
							<th class="thumbnail"></th>
							<th class="column-primary" ><?php esc_html_e( 'File', 'CheetahO' ) ?></th>
							<th class="column"><?php esc_html_e( 'Original size', 'CheetahO' ) ?></th>
							<th class="column"><?php esc_html_e( 'Size decreased by', 'CheetahO' ) ?></th>
							<th class="column"><?php esc_html_e( 'Current Size', 'CheetahO' ) ?></th>
							<th class="column savings" ><?php esc_html_e( 'Savings', 'CheetahO' ) ?></th>
							<th class="status" ><?php esc_html_e( 'Status', 'CheetahO' ) ?></th>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			<?php endif;?>
		</div>
		
		<div class="cheetaho-col cheetaho-col-sidebar">
			<?=self::renderStats()?>
			<?=self::renderSupportBlock()?>
			<?=self::renderContactsBlock()?>
		</div>
		</div>	
		
		<?php 
	}
}
