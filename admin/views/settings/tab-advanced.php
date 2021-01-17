<pdfdiv id="advanced" class="cheetaho-tab-content <?php echo ((isset($_GET['tab']) && $_GET['tab'] == 'advanced') ? 'active' : '') ?>">
    <table class="form-table">
        <tbody>
        <tr>
            <th scope="row">
                <label for="additional-media"><?php _e('Additional media folders', 'cheetaho-image-optimizer');?></label>
            </th>
            <td>
                <?php if(count($customFolders) > 0) { ?>
                    <table class="cheetaho-folders-list">
                        <tr style="font-weight: bold;">
                            <th><?php _e('Folder name', 'cheetaho-image-optimizer');?></th>
                            <th><?php _e('Status', 'cheetaho-image-optimizer');?></th>
                            <th><?php _e('Files', 'cheetaho-image-optimizer');?></th>
                            <th><?php _e('Last change', 'cheetaho-image-optimizer');?></th>
                            <th>&nbsp;</th>
                        </tr>
                        <?php foreach($customFolders as $folder) {
                            $folder_status = $folder->status;
                            $cnt = $folder->file_count;

                            $st =  ($folder_status == CheetahO_Folders::STATUS_OPTIMIZED
                                    ? __("Optimized",'cheetaho-image-optimizer')
                                    : __("Waiting",'cheetaho-image-optimizer'));

                            $action = ($st == __("Optimized",'cheetaho-image-optimizer') || $st == __("Empty",'cheetaho-image-optimizer') ? __("Stop monitoring",'cheetaho-image-optimizer') : __("Stop optimizing",'cheetaho-image-optimizer'));

                            ?>
                            <tr>
                                <td class='folder folder-<?php echo $folder->id ?>'>
                                    <?php echo($folder->path); ?>
                                </td>
                                <td>
                                    <?php echo $st?>
                                </td>
                                <td>
                                    <?php echo($cnt); ?>
                                </td>
                                <td>
                                    <?php echo( date('Y-m-d', strtotime($folder->updated_at))); ?>
                                </td>
                                <td>
                                    <?php if ($action): ?>
                                        <input type="button" class="button remove-folder-button" data-value="<?php echo $folder->id; ?>" onclick="CheetahOSettings.removeFolder(<?php echo $folder->id; ?>); return false;" title="<?php echo $action; ?>" value="<?php echo $action;?>">
                                    <?php endif; ?>
                                    <input type="button" style="display:none;" class="button button-alert recheck-folder-button" data-value="<?php echo $folder->id; ?>"
                                           title="<?php _e('Full folder refresh, check each file of the folder if it changed since it was optimized. Might take up to 1 min. for big folders.', 'cheetaho-image-optimizer');?>"
                                           value="<?php _e('Refresh', 'cheetaho-image-optimizer');?>">
                                </td>
                            </tr>
                        <?php }?>
                    </table>
                <?php } ?>

                <div class='addCustomFolder'>
                    <input type="hidden" name="removeFolder" id="removeFolder"/>
                    <p class='add-folder-text'><strong><?php _e('Add a custom folder', 'cheetaho-image-optimizer'); ?></strong></p>
                    <input type="text" name="_cheetaho_options[custom_folder]" id="addCustomFolderView" class="regular-text" value="" readonly style="">
                    <input type="hidden" id="customFolderBase" value="<?php echo $root_path; ?>">

                    <a class="button cheetaho-select-folder" title="<?php _e('Select the images folder on your server.','cheetaho-image-optimizer');?>" href="javascript:void(0);">
                        <?php _e('Select ...', 'cheetaho-image-optimizer');?>
                    </a>
                    <input type="submit" name="cheetahoSaveAction" id="saveAdvAddFolder" class="button button-primary hidden" title="<?php _e('Add this Folder','cheetaho-image-optimizer');?>" value="<?php _e('Add this Folder','cheetaho-image-optimizer');?>">
                    <div>
                    <small><?php _e('Use the Select... button to select site folders. CheetahO will optimize images from the specified folders and their subfolders. The optimization status for each image in these folders can be seen in the <a href="upload.php?page=cheetaho-other-media">Other Media list</a>, under the Media menu.','cheetaho-image-optimizer');?></small>
                    </div>

                    <div class="cheetaho-modal modal-folder-picker hide">
                        <div class="cheetaho-modal-title"><?php _e('Choose directory','cheetaho-image-optimizer');?></div>
                        <p><?php _e('Choose which folder you wish to optimize and CheetahO will automatically include any images in subfolders of your selected folder.','cheetaho-image-optimizer')?></p>
                        <div class="cheetaho-folder-picker"></div>
                        <div class="cheetaho-modal-footer cheetaho-content-right">
                            <input type="button" class="button button-info select-folder-cancel" value="<?php _e('Cancel','cheetaho-image-optimizer');?>">
                            <input type="button" class="button button-primary select-folder" value="<?php _e('Select','cheetaho-image-optimizer');?>">
                        </div>
                    </div>

                </div> <!-- end of AddCustomFolder -->
            </td>
        </tr>




            <tr>
                <th scope="row"><?php _e( 'Automatically optimize uploads:', 'cheetaho-image-optimizer' ); ?></th>
                <td>
                    <input type="checkbox" id="auto_optimize" name="_cheetaho_options[auto_optimize]" value="1" <?php checked( 1, $auto_optimize, true ); ?> />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <?php _e( 'Optimize the Retina images', 'cheetaho-image-optimizer' ); ?>
                </th>
                <td>
                    <input type="checkbox" id="optimize_retina" name="_cheetaho_options[optimize_retina]" value="1" <?php checked( 1, $optimize_retina, true ); ?> />
                    <small>
                        <?php _e( 'If you have a Retina plugin that generates Retina-specific images (@2x), CheetahO plugin will try to find retina images and optimize them. Also if backup option is enabled, retina image backup will be done before optimization', 'cheetaho-image-optimizer' ); ?>
                        <a target="_blank" href="https://cheetaho.com/?p=1116&utm_source=wordpress-plugin&utm_medium=settings-page">
                            <?php _e( 'Read more', 'cheetaho-image-optimizer' ); ?>
                        </a>
                    </small>
                </td>
            </tr>
            <tr class="cheetaho-advanced-settings">
                <th scope="row">
                    <?php _e( 'Image Sizes to optimize:', 'cheetaho-image-optimizer' ); ?>
                </th>
                <td>
                    <p class="cheetaho-sizes-comment">
                        <small>
                            <?php _e( 'You can choose witch image size created by WordPress you want to compress. The original size is automatically optimized by CheetahO.', 'cheetaho-image-optimizer' ); ?>
                            <span>
                                            <?php _e( 'Do not forget that each additional image size will affect your CheetahO monthly usage!', 'cheetaho-image-optimizer' ); ?>
                                        </span>
                        </small>
                    </p>
                    <br/>
                    <?php $size_count = count( $sizes ); ?>
                    <?php $i = 0; ?>
                    <?php foreach ( $sizes as $size ) { ?>
                        <?php $size_checked = isset( $valid[ 'include_size_' . $size ] ) ? $valid[ 'include_size_' . $size ] : 1; ?>
                        <label for="<?php echo "cheetaho_size_$size"; ?>">
                            <input type="checkbox" id="cheetaho_size_<?php echo $size; ?>" name="_cheetaho_options[include_size_<?php echo $size; ?>]" value="1" <?php checked( 1, $size_checked, true ); ?>/>&nbsp;<?php echo $size; ?>
                        </label>&nbsp;&nbsp;&nbsp;&nbsp;
                        <?php $i++; ?>
                        <?php if ( 0 == $i % 3 ) : ?>
                            <br/>
                        <?php endif; ?>
                    <?php } ?>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <?php _e( 'HTTP AUTH credentials:', 'cheetaho-image-optimizer' ); ?>
                </th>
                <td>
                    <input autocomplete="off" name="_cheetaho_options[authUser]" type="text" id="authUser" value="<?php echo $auth_user; ?>" data-val="<?php echo $auth_user; ?>" placeholder="<?php _e( 'User', 'cheetaho-image-optimizer' ); ?>" class="cheetaho-mb-5">
                    <br/>
                    <input autocomplete="off" name="_cheetaho_options[authPass]" type="password" id="authPass" value="<?php echo $auth_pass; ?>" data-val="<?php echo $auth_pass; ?>" placeholder="<?php _e( 'Password', 'cheetaho-image-optimizer' ); ?>" class="cheetaho-mb-5">
                    <p class="settings-info">
                        <small>
                            <?php _e( 'Fill these fields if your site (front-end) is not publicly accessible and visitors need a user/pass to connect to it. If you do not know what is this or site is public then leave the fields empty', 'cheetaho-image-optimizer' ); ?>
                        </small>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <?php _e( 'Exclude images using patterns:', 'cheetaho-image-optimizer' ); ?>
                </th>
                <td>
                    <input name="_cheetaho_options[<?php echo CheetahO_Settings::EXCLUDE_FILES_BY_PATTERN ?>]" type="text" value="<?php echo $file_exclude_pattern; ?>" class="regular-text" style="width:100%" placeholder="path:/ignore_regex/i, name:image_name" />
                    <p class="settings-info">
                        <small><?php _e( 'Exclude images from being optimized, based on patterns', 'cheetaho-image-optimizer' ); ?></small>
                        <small><?php _e( 'A file will be not optimized if it matches any of the patterns.', 'cheetaho-image-optimizer' ); ?></small>
                        <br/>
                        <small><?php _e( 'Few patterns can be separated by comma. A pattern consist of a type:value pair. Supported types are: name, path', 'cheetaho-image-optimizer' ); ?></small>
                        <br/>
                        <small><?php _e( 'For a "name" patter only the file name will be matched, but for the "path" all the path all the path will be matched', 'cheetaho-image-optimizer' ); ?></small>
                        <br>
                        <small><?php _e( 'If a pattern will starts with "/" and is valid it will be considered as a regex. You can also use regular expressions accepted by preg_match, but without "," or ":"', 'cheetaho-image-optimizer' ); ?></small>
                    </p>
                </td>
        </tr>

            <tr>
                <th scope="row">
                    <?php _e( 'CloudFlare credentials:', 'cheetaho-image-optimizer' ); ?>
                </th>
                <td>
                    <input  autocomplete="off" type="text" name="_cheetaho_options[<?php echo CheetahO_Settings::CLOUDFLARE_EMAIL; ?>]" id="<?php echo CheetahO_Settings::CLOUDFLARE_EMAIL; ?>" value="<?php echo $cloudflare_email; ?>" class="option-control form-control cheetaho-mb-5" placeholder="<?php _e( 'Email', 'cheetaho-image-optimizer' ); ?>">
                    <br/>
                    <input  autocomplete="off" type="text" name="_cheetaho_options[<?php echo CheetahO_Settings::CLOUDFLARE_API_KEY; ?>]" id="<?php echo CheetahO_Settings::CLOUDFLARE_API_KEY; ?>" value="<?php echo $cloudflare_api_key; ?>" class="option-control form-control cheetaho-mb-5" placeholder="<?php _e( 'API Key', 'cheetaho-image-optimizer' ); ?>">
                    <br/>
                    <input  autocomplete="off" type="text" name="_cheetaho_options[<?php echo CheetahO_Settings::CLOUDFLARE_ZONE; ?>]" id="<?php echo CheetahO_Settings::CLOUDFLARE_ZONE; ?>" value="<?php echo $cloudflare_zone; ?>" class="option-control form-control cheetaho-mb-5" placeholder="<?php _e( 'Zone ID', 'cheetaho-image-optimizer' ); ?>">

                    <p class="settings-info">
                        <small>
                            <?php _e( 'If you are running using CloudFlare APIs and or any WordPress CloudFlare plugins enter your details to be able to purge the cached urls of the files that CheetahO optimizes.', 'cheetaho-image-optimizer' ); ?>
                        </small>
                    </p>
                </td>
            </tr>

        </tbody>
    </table>
    <input type="submit" name="cheetahoSaveAction" class="button button-primary" value="<?php _e( 'Save Settings', 'cheetaho-image-optimizer' ); ?>"/>

</pdfdiv>
