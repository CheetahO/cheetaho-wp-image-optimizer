<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$html = "<div class='buttonWrap'>
            <strong>". __( 'Optimized Size', 'cheetaho-image-optimizer' ).": ". CheetahO_Helpers::convert_to_kb($image->image_size)."</strong>
                <div>".__( 'Original Size', 'cheetaho-image-optimizer' ).": ".  CheetahO_Helpers::convert_to_kb($image->original_size)."</div>
                <small>
                    ".__( 'Type', 'cheetaho-image-optimizer' ).":&nbsp;". $image->level."
                </small>
                <br />
                <small>".__( 'Savings', 'cheetaho-image-optimizer' ).":&nbsp;". CheetahO_Helpers::count_savings($image) ."%</small><br/>
                <a class='cheetaho-custom-restore' href='javascript:void(0)'  data-item_id='".$image->id."' data-action='restore' title='".__( 'Removes CheetahO metadata associated with this image', 'cheetaho-image-optimizer' )."'>
                    ".__( 'Reset', 'cheetaho-image-optimizer' )."
                </a>
            </div>";
