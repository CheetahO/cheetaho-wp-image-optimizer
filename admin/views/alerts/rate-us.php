<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<?php
$current_screen  = get_current_screen();
$ignored_notices = get_user_meta( $GLOBALS['current_user']->ID, '_cheetaho_ignore_notices', true );

if ( in_array( 'rate_us', (array) $ignored_notices ) || empty( $data ) ) {
	return;
}
?>
<br/>
<br/>
<div class="wrap cheetaho-alert-info cheetaho-message-block">
	<a href="<?php echo CheetahO_Alert::get_close_alert_url( 'rate_us' ); ?>" class="cheetaho-notice-close dark" title="<?php _e( 'Dismiss this notice', 'cheetaho-image-optimizer' ); ?>"><span class="dashicons dashicons-dismiss"></span></a>
	<h3><?php _e( 'CheetahO image optimization', 'cheetaho-image-optimizer' ); ?></h3>
	<p >
		<?php _e( 'Do you like this plugin? Please take a few seconds to rate it on WordPress.org! That will also bring me joy and motivation, and I will get back to you.', 'cheetaho-image-optimizer' ); ?>
		<p><a class="stars" target="_blank" href="https://wordpress.org/support/view/plugin-reviews/cheetaho-image-optimizer?rate=5#postform">
			<?php _e( 'Rate', 'cheetaho-image-optimizer' ); ?>
			<span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span>
            </a>
        </p>
	</p>
	<a class='button button-secondary' href='<?php echo CheetahO_Alert::get_close_alert_url( 'rate_us' ); ?>' ><?php _e( 'Skip message', 'cheetaho-image-optimizer' ); ?></a>

</div>
