var CheetahOOtherMedia = {
    init: function () {
        jQuery('.column').on('click', 'a.cheetaho-custom-restore', function(){
            CheetahOOtherMedia.doAction(jQuery(this));
            return false;
        });

        jQuery('.column').on('click', 'button.cheetaho-custom-optimize', function(){
            CheetahOOtherMedia.doAction(jQuery(this));
            return false;
        });
    },

    doAction: function(el) {
        jQuery.ajax({
            type: "POST",
            url: cheetaho_other_media_object.ajaxurl,
            data: {
                item_id:el.data('item_id'),
                action: 'cheetaho_image_action',
                type: el.data('action'),
                _wpnonce:cheetaho_other_media_object.nonce
            },
            success: function(response) {
                var data =  JSON.parse(response);

                jQuery('#status_' + el.data('item_id')).html(data.status_txt);

                if (data.error !== undefined) {
                    el.replaceWith('<span>' + data.error.message + '</span>');
                } else {

                    if (data.removed === true) {
                        jQuery('#row_' + el.data('item_id')).remove();
                    } else {
                        jQuery('#column_' + el.data('item_id')).html(data.html);
                    }
                }
            },
            async: false
        });

        return false;
    }
};

jQuery(document).ready(function() {
    CheetahOOtherMedia.init();
});
