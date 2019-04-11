<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$html = "<div class='buttonWrap'><strong>" . __( 'Optimized Size', 'cheetaho-image-optimizer' ) . ': ' . $cheetaho_size . '</strong>
<div>
    ' . __( 'Original Size', 'cheetaho-image-optimizer' ) . ': ' . $original_size . '
</div>
<small>
    ' . __( 'Type', 'cheetaho-image-optimizer' ) . ':&nbsp;' . $type . '
</small>
<br/>
<small>
    ' . __( 'Savings', 'cheetaho-image-optimizer' ) . ':&nbsp;' . $savings_percentage . '
</small>';?>
<?php if ( ! empty( $thumbs_meta ) ) : ?>
	<?php
	$html .= '<br/>
    <small>
        ' . $thumbs_count . ' ' . __( 'thumbs optimized', 'cheetaho-image-optimizer' ) . '
    </small>';
	?>
<?php endif ?>
	<?php
	$html .= '<br/>
    <small>
        ' . $retina_count . ' ' . __( 'retina images optimized', 'cheetaho-image-optimizer' ) . '
    </small>';
	?>
<?php
$html .= "<br/>
<small class='cheetahoReset' data-id='" . $id . "' title='" . __( 'Removes Cheetaho metadata associated with this image', 'cheetaho-image-optimizer' ) . "'>
    " . __( 'Reset', 'cheetaho-image-optimizer' ) . "<span class='cheetahoSpinner'></span>
</small>
</div>";
