<div id="general" class="cheetaho-tab-content <?php echo (!isset($_GET['tab']) || (isset($_GET['tab']) && $_GET['tab'] == 'general') ? 'active' : '') ?>">
    <div class="cheetaho-sub-header">
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row"><label for="api_key"><?php _e( 'API Key', 'cheetaho-image-optimizer' ); ?></label></th>
                <td>
                    <input name="_cheetaho_options[api_key]" type="text" value="<?php echo esc_attr( $api_key ); ?>" size="60" /> <br/>
                    <?php _e( 'Do not have an API Key yet?', 'cheetaho-image-optimizer' ); ?>
                    <a href="https://app.cheetaho.com/?utm_source=wordpress-plugin&utm_medium=settings-page" target="_blank" title="<?php _e( 'Log in to your Cheetaho account', 'cheetaho-image-optimizer' ); ?>">
                        <?php _e( 'Create one, it is FREE', 'cheetaho-image-optimizer' ); ?>
                    </a>
                </td>
            </tr>
            </tbody>
        </table>
    </div>


    <table class="form-table">
        <tbody>
        <tr>
            <th scope="row">
                <?php _e( 'Optimization Type:', 'cheetaho-image-optimizer' ); ?>
            </th>
            <td>
                <input type="radio" id="cheetahoLossy" name="_cheetaho_options[api_lossy]" value="1" <?php checked( 1, $lossy, true ); ?> />
                <label for="cheetahoLossy"><?php _e( 'Lossy', 'cheetaho-image-optimizer' ); ?></label>
                <p class="settings-info">
                    <small>
                        <b>
                            <?php _e( 'Lossy compression:', 'cheetaho-image-optimizer' ); ?>
                        </b>
                        <?php _e( 'lossy has a better compression rate than lossless compression.<br> The resulting image can be not 100% identical with the original. Works well for photos taken with your camera.', 'cheetaho-image-optimizer' ); ?>
                    </small>
                </p>
                <br/>
                <input type="radio" id="cheetahoLossless" name="_cheetaho_options[api_lossy]" value="0" <?php checked( 0, $lossy, true ); ?> />
                <label for="cheetahoLossless">
                    <?php _e( 'Lossless', 'cheetaho-image-optimizer' ); ?>
                </label>
                <p class="settings-info">
                    <small>
                        <b>
                            <?php _e( 'Lossless compression:', 'cheetaho-image-optimizer' ); ?>
                        </b>
                        <?php _e( 'the shrunk image will be identical with the original and smaller in size.', 'cheetaho-image-optimizer' ); ?>
                        <br/>
                        <?php _e( 'You can use this when you do not want to lose any of the original images details. Choose this if you would like to optimize technical drawings, clip art and comics.', 'cheetaho-image-optimizer' ); ?>
                    </small>
                </p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <?php _e( 'Keep EXIF metadata:', 'cheetaho-image-optimizer' ); ?>
                <br/>
                <small>
                    <a target="_blank" href="https://cheetaho.com/?p=378&utm_source=wordpress-plugin&utm_medium=settings-page">
                        <?php _e( 'What is EXIF metadata?', 'cheetaho-image-optimizer' ); ?>
                    </a>
                </small>
            </th>
            <td>
                <input type="checkbox" id="keep_exif" name="_cheetaho_options[keep_exif]" value="1" <?php checked( 1, $keep_exif, true ); ?> />
            </td>
        </tr>

        <?php
        /*
        <tr>
                    <th scope="row">
                        <?php _e( 'WebP Images:', CHEETAHO_PLUGIN_NAME)?>
                    </th>
                    <td>
                        <input type="checkbox" id="create_webp" name="_cheetaho_options[create_webp]" value="1" <?php checked( 1, $create_webp, true ); ?> />
                        <small><?php _e( 'Create WebP versions of the images. WebP images can be about 25% smaller than PNGs or JPGs. Choosing this option does not use up additional credits.', CHEETAHO_PLUGIN_NAME)?></small>
                    </td>
                </tr> */
        ?>
        <tr class="with-tip">
            <th scope="row">
                <?php _e( 'JPEG quality:', 'cheetaho-image-optimizer' ); ?>
            </th>
            <td>
                <select name="_cheetaho_options[quality]">
                    <?php $i = 0; ?>
                    <?php foreach ( range( 100, 70 ) as $number ) { ?>
                        <?php if ( 0 === $i ) : ?>
                            <?php echo '<option value="0">' . __( 'Intelligent lossy (recommended)', 'cheetaho-image-optimizer' ) . '</option>'; ?>
                        <?php endif; ?>
                        <?php if ( $i > 0 ) : ?>
                            <option value="<?php echo $number; ?>" <?php selected( $quality, $number, true ); ?> >
                            <?php echo $number; ?>
                        <?php endif; ?>
                        </option>
                        <?php $i++; ?>
                    <?php } ?>
                </select>
                <p class="settings-info">
                    <small>
                        <?php _e( 'Advanced users can force the quality of images. Specifying a quality level of 40 will produce the lowest image quality (highest compression level).', 'cheetaho-image-optimizer' ); ?>
                        <br/>
                        <?php _e( 'We therefore recommend keeping the <strong>Intelligent Lossy</strong> setting, which will not allow a resulting image of unacceptable quality.', 'cheetaho-image-optimizer' ); ?>
                        <br/>
                        <?php _e( 'This setting will be ignored when using the <strong>lossless</strong> optimization mode.', 'cheetaho-image-optimizer' ); ?>
                    </small>
                </p>
                <br/>
            </td>
        </tr>

        <tr>
            <th scope="row"><?php _e( 'Resize my full size images:', 'cheetaho-image-optimizer' ); ?></th>
            <td>
                <?php $max_image_sizes = CheetahO_Helpers::get_max_intermediate_image_size(); ?>
                <input type="hidden" id="min-width" value="<?php echo $max_image_sizes['width']; ?>">
                <input type="hidden" id="min-height" value="<?php echo $max_image_sizes['height']; ?>">
                <input type="checkbox" id="resize" name="_cheetaho_options[resize]" value="1" <?php checked( 1, $resize, true ); ?> />
                <small>
                    <?php _e( 'Set a maximum height and width for all images uploaded to your site so that any unnecessarily large images are automatically resized before they are added to the media gallery. The original aspect ratio is preserved and image is not cropped.', 'cheetaho-image-optimizer' ); ?>
                </small>
                <div class="resize-inputs">
                    <input type="text" class="resize-sizes" value="<?php echo ( $max_width > 0 ) ? $max_width : $max_image_sizes['width']; ?>" data-type="width" name="_cheetaho_options[maxWidth]"/>
                    <?php _e( 'pixels wide', 'cheetaho-image-optimizer' ); ?>
                    x
                    <input data-type="height" class="resize-sizes" type="text" value="<?php echo ( $max_height > 0 ) ? $max_height : $max_image_sizes['height']; ?>" name="_cheetaho_options[maxHeight]"/>
                    <?php _e( 'pixels high', 'cheetaho-image-optimizer' ); ?>
                </div>
            </td>
        </tr>


        <tr>
            <th scope="row">
                <?php _e( 'Images backups:', 'cheetaho-image-optimizer' ); ?>
            </th>
            <td>
                <input type="checkbox" id="backup" name="_cheetaho_options[backup]" value="1" <?php checked( 1, $backup, true ); ?> />
                <small class="cheetaho-sizes-comment">
                                    <span>
                                        <?php _e( 'You need to have backup active in order to be able to restore images to originals.', 'cheetaho-image-optimizer' ); ?>
                                    </span>
                </small>
                <p>
                    <?php _e( 'Your backup folder size is now:', 'cheetaho-image-optimizer' ); ?>
                <form action="" method="POST">
                    <?php echo( $backupfolder_size ); ?>
                    <input type="submit" style="margin-left: 15px; vertical-align: middle;" class="button button-secondary" name="empty_backup" onclick="return confirm('<?php _e( 'Are you sure want to remove images from backup folder?', 'cheetaho-image-optimizer' ); ?>');" value="<?php _e( 'Empty backups', 'cheetaho-image-optimizer' ); ?>"/>
                </form>
                </p>
            </td>
        </tr>
        </tbody>
    </table>
    <input type="submit" name="cheetahoSaveAction" class="button button-primary" value="<?php _e( 'Save Settings', 'cheetaho-image-optimizer' ); ?>"/>
</div>
