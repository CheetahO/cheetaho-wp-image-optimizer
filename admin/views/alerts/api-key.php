<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php
$current_screen  = get_current_screen();
$ignored_notices = get_user_meta( $GLOBALS['current_user']->ID, '_cheetaho_ignore_notices', true );

if ( ( isset( $current_screen ) && ( 'settings_page_cheetaho' === $current_screen->base || 'settings_page_cheetaho-network' === $current_screen->base ) ) || in_array( 'welcome', (array) $ignored_notices ) || ( isset( $settings['api_key'] ) && '' != $settings['api_key'] ) ) {
	return;
}

?>
<div class="cheetaho-welcome">
	<div class="cheetaho-title">
					<span class="baseline">
						<?php _e( 'Welcome to CheetahO image optimization!', 'cheetaho-image-optimizer' ); ?>
					</span>
		<a href="<?php echo CheetahO_Alert::get_close_alert_url( 'welcome' ); ?>" class="cheetaho-notice-close" title="<?php _e( 'Dismiss this notice', 'cheetaho-image-optimizer' ); ?>"><span class="dashicons dashicons-dismiss"></span></a>
	</div>
	<div class="cheetaho-settings-section">
		<div class="cheetaho-columns counter">
			<div class="col-1-3">
				<div class="cheetaho-col-content">
					<p class="cheetaho-col-title"><?php _e( 'Create CheetahO Account', 'cheetaho-image-optimizer' ); ?></p>
					<p class="cheetaho-col-desc"><?php _e( 'Don\'t have an CheetahO account? Create account in few seconds and optimize your images!', 'cheetaho-image-optimizer' ); ?></p>
					<p><a target="_blank" href="<?php echo CHEETAHO_APP_URL; ?>register/?utm_source=wordpress-plugin&utm_medium=alert-message" class="button button-primary"><?php _e( 'Sign up, It\'s FREE!', 'cheetaho-image-optimizer' ); ?></a></p>
				</div>
			</div>
			<div class="col-1-3">
				<div class="cheetaho-col-content">
					<p class="cheetaho-col-title"><?php _e( 'Get API Key', 'cheetaho-image-optimizer' ); ?></p>
					<p class="cheetaho-col-desc"><?php printf( __( 'Go to CheetahO API key page. Copy key and come back here.', 'cheetaho-image-optimizer' ) ); ?></p>
					<p>
						<a href="<?php echo CHEETAHO_APP_URL; ?>api-credentials/?utm_source=wordpress-plugin&utm_medium=alert-message" class="button button-primary"><?php _e( 'Get API key', 'cheetaho-image-optimizer' ); ?></a></p>
				</div>
			</div>
			<div class="col-1-3">
				<div class="cheetaho-col-content">
					<p class="cheetaho-col-title"><?php _e( 'Configure it', 'cheetaho-image-optimizer' ); ?></p>
					<p class="cheetaho-col-desc"><?php _e( 'It’s almost done! Now you need to configure your plugin.', 'cheetaho-image-optimizer' ); ?></p>
					<p><a href="<?php echo CHEETAHO_SETTINGS_LINK; ?>" class="button button-primary"><?php _e( 'Go to Settings', 'cheetaho-image-optimizer' ); ?></a></p>
				</div>
			</div>
		</div>
        <div class="cheetaho-columns counter">
            <div class="col-1-12">
                <hr />
                <h4><?php _e( 'Referral program', 'cheetaho-image-optimizer')?></h4>
                <p><?php printf( __( 'Tell your friends about CheetahO by sharing your <a target="_blank" href="%1$s">referral url</a> and we will add for <b>both 50 images</b> for new user registration and <b>200 images</b> for every referred your friend that signs up for a paid plan. There is no limit to the amount of images you can earn.', 'cheetaho-image-optimizer'), 'https://app.cheetaho.com/referral?utm_source=wordpress-plugin&utm_medium=alert-message')?></p>
            </div>
        </div>
	</div>
</div>
