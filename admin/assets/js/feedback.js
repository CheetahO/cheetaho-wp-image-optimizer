jQuery(document).ready(function($) {

    var deactivateLink = jQuery("#cheetaho-deactivate-link-cheetaho-image-optimizer"),
        formContainer = jQuery("#cheetaho-deactivate-form-cheetaho-image-optimizer"),
        url = deactivateLink.attr( 'href' );

    jQuery(deactivateLink).attr('onclick', "javascript:event.preventDefault();");
    jQuery(deactivateLink).click(function(e){
        e.preventDefault();
        jQuery('body').append(cheetaho_feedback_object.html);
        jQuery('body').toggleClass('cheetaho-deactivate-form-active');
        formContainer.fadeIn();
        $('html,body').animate({ scrollTop: 50 });
        
        cheetaho_uninstall_button_handlers(formContainer, url );
    });


    function cheetaho_uninstall_button_handlers(formContainer, url ) {
        jQuery('#cheetaho-deactivate-skip').click(function(){
            jQuery(this).prop( 'disabled', true );
            window.location.href = url;
        });

        // If we click outside the form, the form will close
        jQuery('.cheetaho-deactivate-form-bg').on('click',function(){
            formContainer.fadeOut();
            $('body').toggleClass('cheetaho-deactivate-form-active');
            jQuery('.cheetaho-deactivate-form-wrapper').remove();
            jQuery('.cheetaho-deactivate-form-bg').remove();
        });

        jQuery('#cheetaho-deactivate-submit-form').click(function() {
            if (jQuery('.cheetaho-reason:checked').val() !== undefined && jQuery('#cheetaho-deactivate-details').val() != '') {
                jQuery(this).prop('disabled', true);
                jQuery(this).html('...');
                jQuery('#cheetaho-deactivate-skip').hide();
                jQuery.ajax({
                    url: cheetaho_feedback_object.ajaxurl,
                    type: 'POST',
                    data: {
                        "action": "cheetaho_uninstall",
                        "nonce": cheetaho_feedback_object.nonce,
                        "reason": jQuery('.cheetaho-reason:checked').val(),
                        "msg": jQuery('#cheetaho-deactivate-details').val(),
                    }
                }).done(function () {
                     window.location.href = url
                });
            } else {
                alert(cheetaho_feedback_object.alert_error);
            }
        });
    }
});