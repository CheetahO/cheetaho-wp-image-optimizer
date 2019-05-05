<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="cheetaho-block stats">
	<h3><?php _e( 'Optimization Stats', 'cheetaho-image-optimizer' ); ?></h3>
	<hr />
	<?php $data = CheetahO_Stats::get_stats(); ?>
	<ul>
		<li><?php _e( 'Image sizes optimized:', 'cheetaho-image-optimizer' ); ?> <span id="optimized-images"><?php echo $data['total_images']; ?></span></li>
		<li><?php _e( 'Total images initial size:', 'cheetaho-image-optimizer' ); ?>  <span data-bytes="<?php echo $data['total_size_orig_images']; ?>" id="original-images-size"><?php echo size_format( $data['total_size_orig_images'], 2 ); ?></span></li>
		<li><?php _e( 'Total images current size:', 'cheetaho-image-optimizer' ); ?>   <span data-bytes="<?php echo $data['total_size_images']; ?>" id="optimized-size"><?php echo size_format( $data['total_size_images'], 2 ); ?></span></li>
		<li><?php _e( 'Saved size in % using CheetahO:', 'cheetaho-image-optimizer' ); ?>  <span id="savings-percentage"> <?php echo $data['total_percents_optimized']; ?>%</span></li>
	</ul>
</div>
