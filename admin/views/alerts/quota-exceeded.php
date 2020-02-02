<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<?php
$current_screen  = get_current_screen();
$ignored_notices = get_user_meta( $GLOBALS['current_user']->ID, '_cheetaho_ignore_notices', true );

if ($ignored_notices == '' || in_array( 'quota', (array) $ignored_notices  )) {
    return;
}
?>

<br/>
<br/>
<div class="wrap cheetaho-alert-danger">
	<a href="<?php echo CheetahO_Alert::get_close_alert_url( 'quota' ); ?>" class="cheetaho-notice-close dark" title="<?php _e( 'Dismiss this notice', 'cheetaho-image-optimizer' ); ?>"><span class="dashicons dashicons-dismiss"></span></a>
	<h3><?php _e( 'CheetahO image optimization Quota Exceeded', 'cheetaho-image-optimizer' ); ?></h3>
	<p><?php _e( 'You can optimize some images for free every month. Come back on date when you will get free images to continue optimization.', 'cheetaho-image-optimizer' ); ?></p>
	<p><?php _e( 'To continue to optimize your images now, log in to your CheetahO account to upgrade your plan.', 'cheetaho-image-optimizer' ); ?></p>
	<p>
		<a class='button button-primary' href='<?php echo CHEETAHO_APP_URL; ?>billing/plans?utm_source=wordpress-plugin&utm_medium=alert-message' target='_blank'><?php _e( 'Upgrade plan now', 'cheetaho-image-optimizer' ); ?></a>
		<a class='button button-secondary' href='<?php echo CheetahO_Alert::get_close_alert_url( 'quota' ); ?>' ><?php _e( 'I upgraded plan. Close message', 'cheetaho-image-optimizer' ); ?></a>
	</p>
</div>
