<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="cheetaho-wrap">
	<div class="cheetaho-col cheetaho-col-main">

		<?php if ( isset( $result['error'] ) ) { ?>
			<div class="cheetaho error mb-30">
				<?php foreach ( $result['error'] as $error ) { ?>
					<p><?php echo htmlspecialchars( $error ); ?></p>
				<?php } ?>
			</div>
			<?php
		} else {
			if ( isset( $result['success'] ) ) {
				?>
				<div class="cheetaho updated mb-30">
					<p><?php _e( 'Settings saved.', 'cheetaho-image-optimizer' ); ?></p>
				</div>
				<?php
			}
		}
		?>

		<?php if ( ! function_exists( 'curl_init' ) ) { ?>
			<p class="curl-warning mb-30">
				<strong><?php _e( 'Warning:', 'cheetaho-image-optimizer' ); ?> </strong>
				<?php _e( 'CURL is not available. If you would like to use this plugin please install CURL', 'cheetaho-image-optimizer' ); ?>
			</p>
		<?php } ?>

		<div class="cheetaho-title">
			CheetahO v<?php echo CHEETAHO_VERSION; ?>
			<p class="cheetaho-rate-us">
				<strong><?php _e( 'Do you like this plugin?', 'cheetaho-image-optimizer' ); ?></strong><br>
					<?php _e( 'Please take a few seconds to', 'cheetaho-image-optimizer' ); ?>
				<a href="https://wordpress.org/support/view/plugin-reviews/cheetaho-image-optimizer?rate=5#postform"><?php _e( 'rate it on WordPress.org', 'cheetaho-image-optimizer' ); ?></a>! <br>
				<a class="stars" target="_blank" href="https://wordpress.org/support/view/plugin-reviews/cheetaho-image-optimizer?rate=5#postform">
					<span class="dashicons dashicons-star-filled"></span>
					<span class="dashicons dashicons-star-filled"></span>
					<span class="dashicons dashicons-star-filled"></span>
					<span class="dashicons dashicons-star-filled"></span>
					<span class="dashicons dashicons-star-filled"></span>
				</a>
			</p>
		</div>

        <?php require_once CHEETAHO_PLUGIN_ROOT . 'admin/views/settings/tabs.php'; ?>

		<div class="settings-tab">
			<form method="post" autocomplete="off" id="settingsForm">
                <?php require_once CHEETAHO_PLUGIN_ROOT . 'admin/views/settings/tab-general.php'; ?>
                <?php require_once CHEETAHO_PLUGIN_ROOT . 'admin/views/settings/tab-advanced.php'; ?>
                <?php require_once CHEETAHO_PLUGIN_ROOT . 'admin/views/settings/tab-resources.php'; ?>
			</form>
		</div>
	</div>
	<div class="cheetaho-col cheetaho-col-sidebar">
		<?php require_once CHEETAHO_PLUGIN_ROOT . 'admin/views/blocks/stats.php'; ?>
		<?php require_once CHEETAHO_PLUGIN_ROOT . 'admin/views/blocks/support.php'; ?>
		<?php require_once CHEETAHO_PLUGIN_ROOT . 'admin/views/blocks/contacts.php'; ?>
	</div>
</div>
