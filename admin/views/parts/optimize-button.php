<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (isset($meta['results']) && isset($meta['results']->type) && 'nosavings' == $meta['results']->type) {
    $html = '<div class="buttonWrap">
    <b>' . __( 'Do not need to optimize', 'cheetaho-image-optimizer' ) .'</b></div>';
} else {

    $html = '<div class="buttonWrap">
        <button type="button"
                data-setting="' . $type . '"
                class="cheetaho_req"
                data-id="' . $id . '"
                id="cheetahoid-' . $id . '"
                data-filename="' . $filename . '"
                data-url="' . $image_url . '">
            ' . __('Optimize This Image', 'cheetaho-image-optimizer') . '
        </button>
        <small class="cheetahoOptimizationType" style="display:none">' . $type . '</small>
        <span class="cheetahoSpinner loading-icon"></span>
    </div>';
}
