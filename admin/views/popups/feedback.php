<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<style type="text/css">
    .cheetaho-deactivate-form-active .cheetaho-deactivate-form-bg {
        background: rgba( 0, 0, 0, .5 );
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }
    .cheetaho-deactivate-form-wrapper {
        position: absolute;
        z-index: 9999;
        display: none;
        top: 100px;
        bottom:auto;
        left: 0;
        right: 0;
        margin-left: auto;
        margin-right: auto;
        max-width: 400px;
    }
    .cheetaho-deactivate-form-active .cheetaho-deactivate-form-wrapper {
        display: block;
    }

    .cheetaho-deactivate-form-active .cheetaho-deactivate-form {
        max-width: 400px;
        min-width: 360px;
        background: #fff;
        white-space: normal;
    }
    .cheetaho-deactivate-form-head {
        background: #3c8dbc;
        color: #fff;
        padding: 8px 18px;
    }
    .cheetaho-deactivate-form-body {
        padding: 8px 18px 0;
        color: #444;
    }
    .cheetaho-deactivate-form-body label[for="cheetaho-remove-settings"] {
        font-weight: bold;
    }

    .cheetaho-deactivate-form-footer {
        padding: 0 18px 8px;
    }
    .cheetaho-deactivate-form-footer label[for="anonymous"] {
        visibility: hidden;
    }
    .cheetaho-deactivate-form-footer p {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 0;
    }

    #cheetaho-deactivate-submit-form {
       margin-left:auto;
    }
    .cheetaho-deactivate-form.process-response .cheetaho-deactivate-form-body,
    .cheetaho-deactivate-form.process-response .cheetaho-deactivate-form-footer {
        position: relative;
    }
    .cheetaho-deactivate-form.process-response .cheetaho-deactivate-form-body:after,
    .cheetaho-deactivate-form.process-response .cheetaho-deactivate-form-footer:after {
        content: "";
        display: block;
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba( 255, 255, 255, .5 );
    }
</style>
<div class="cheetaho-deactivate-form-bg"></div>

<div class="cheetaho-deactivate-form-wrapper">
    <div class="cheetaho-deactivate-form" id="cheetaho-deactivate-form-cheetaho-image-optimizer">
        <div class="cheetaho-deactivate-form-head">
            <strong><?= esc_html( $form['heading'] ) ?></strong>
        </div>
        <div class="cheetaho-deactivate-form-body">
            <p><b><?= esc_html( $form['before_body'] ) ?></b></p>
            <?php if( is_array( $form['options'] ) ):?>
                <div class="cheetaho-deactivate-options">
                    <p><strong><?= esc_html( $form['body'] ) ?></strong></p>
                    <p>
                        <?php foreach( $form['options'] as $key => $option ):?>
                            <input type="radio" class="cheetaho-reason" name="cheetaho-deactivate-reason" id="<?= esc_attr( $key ) ?>" value="<?= esc_attr( $key ) ?>">
                            <label for="<?= esc_attr( $key ) ?>"><?= esc_attr( $option ) ?></label>
                            <br />
                        <?php endforeach;?>
                    </p>
                    <label id="cheetaho-deactivate-details-label" for="cheetaho-deactivate-reasons">
                        <strong><?= esc_html( $form['details'] ) ?></strong>
                    </label>
                    <textarea name="cheetaho-deactivate-details" id="cheetaho-deactivate-details" rows="2" style="width:100%"></textarea>
                </div>
            <?php endif;?>
            <hr/>
        </div>
        <div class="cheetaho-deactivate-form-footer">
            <p>
                <button style="margin-right: 5px;" type="button" class="button button-secondary" id="cheetaho-deactivate-skip">
                    <?php _e( 'Skip', 'cheetaho-image-optimizer' ) ?>
                </button>

                <a id="cheetaho-deactivate-submit-form" class="button button-primary" href="#">
                    <?= __( 'Submit and Deactivate', 'cheetaho-image-optimizer' ) ?>
                </a>
            </p>
        </div>
    </div>
</div>