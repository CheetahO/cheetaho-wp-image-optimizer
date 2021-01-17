<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( CheetahO_Helpers::is_processable_path(CheetahO_Helpers::get_abs_path($image->path), $exclude_file_patterns ) === true ) {
    $html = "<button type=button' data-item_id='" . $image->id . "' data-action='optimize' class='button cheetaho-custom-optimize'>" . __('Optimize This Image', 'cheetaho-image-optimizer') . "</button>";
} else {
    $html = __( 'This file can not be optimized', 'cheetaho-image-optimizer' );
}
