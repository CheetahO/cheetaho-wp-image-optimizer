<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="cheetaho-wrap cheetaho-other-media">
        <div class="cheetaho-title cheetaho-d-flex cheetaho-align-center">CheetahO v<?php echo CHEETAHO_VERSION; ?>
            <div class="toolbar-in-title ">
                <a href="upload.php?page=cheetaho-other-media&amp;action=refresh&amp;_wpnonce=<?php echo $nonce ?>" id="refresh" class="button button-secondary" title="<?php esc_html_e( 'Refresh custom folders content', 'cheetaho-image-optimizer' ); ?>">
                    <?php esc_html_e( 'Refresh folders', 'cheetaho-image-optimizer' ); ?>
                </a>
            </div>
            <p class="cheetaho-rate-us">
                <strong><?php _e( 'Do you like this plugin?', 'cheetaho-image-optimizer' ); ?></strong><br /> <?php _e( 'Please take a few seconds to', 'cheetaho-image-optimizer' ); ?> <a href="https://wordpress.org/support/view/plugin-reviews/cheetaho-image-optimizer?rate=5#postform"><?php _e( 'rate it on WordPress.org', 'cheetaho-image-optimizer' ); ?></a>! <br />
                <a class="stars" target="_blank" href="https://wordpress.org/support/view/plugin-reviews/cheetaho-image-optimizer?rate=5#postform"><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span></a>
            </p>
        </div>


        <table class="wp-list-table widefat fixed striped media whitebox" id="optimization-items" >
            <thead>
            <tr>
                <th class="thumbnail" width="110"><?php esc_html_e( 'Thumbnail', 'cheetaho-image-optimizer' ); ?></th>
                <th class="column-primary" ><?php esc_html_e( 'Image path', 'cheetaho-image-optimizer' ); ?></th>
                <th class="column"><?php esc_html_e( 'Status', 'cheetaho-image-optimizer' ); ?></th>
                <th class="column"><?php esc_html_e( 'Action', 'cheetaho-image-optimizer' ); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if (count($images) > 0):?>
                <?php foreach ($images as $image):?>
                    <tr id="row_<?=$image->id?>">
                        <th class="thumbnail"><img src="/<?php echo $image->path?>"/></th>
                        <th class="column-primary" ><?php echo $image->path?></th>
                        <th class="column" id="status_<?=$image->id?>"><?php echo ($image->status == CheetahO_Image_Metadata::STATUS_SUCCESS ? esc_html_e( 'Optimized', 'cheetaho-image-optimizer' ) : esc_html_e( 'Not optimized', 'cheetaho-image-optimizer' ) )?></th>
                        <th class="column" id="column_<?=$image->id?>">
                            <?php if ($image->status == CheetahO_Image_Metadata::STATUS_SUCCESS):?>
                                <?php include CHEETAHO_PLUGIN_ROOT . 'admin/views/parts/column-custom-results.php';?>
                                <?php echo $html?>
                            <?php else:?>
                                <?php include CHEETAHO_PLUGIN_ROOT . 'admin/views/parts/column-custom-optimize-btn.php';?>
                                <?php echo $html?>
                            <?php endif;?>
                        </th>
                    </tr>

                <?php endforeach;?>
            <?php else:?>
                <td colspan="4" class="p-20">
                    <?php echo(__('No images available. Go to <a href="options-general.php?page=cheetaho-image-optimizer&tab=advanced">Advanced Settings</a> to configure additional folders to be optimized.', 'cheetaho-image-optimizer'));?>
                </td>
            <?php endif;?>
            </tbody>
        </table>

        <div class="cheetaho-navigation">
          <?php echo $this->getPagination()?>
        </div>

</div>
