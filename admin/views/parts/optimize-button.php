<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$html = '<div class="buttonWrap">
    <button type="button"
            data-setting="' . $type . '"
            class="cheetaho_req"
            data-id="' . $id . '"
            id="cheetahoid-' . $id . '"
            data-filename="' . $filename . '"
            data-url="' . $image_url . '">
        ' . __( 'Optimize This Image', 'cheetaho-image-optimizer' ) . '
    </button>
    <small class="cheetahoOptimizationType" style="display:none">' . $type . '</small>
    <span class="cheetahoSpinner loading-icon"></span>
</div>';
