
var CheetahOSettings =  {

      initFolderSelector: function () {
        jQuery(".cheetaho-select-folder").click(function(){
            jQuery('body').append('<div class="cheetaho-mask"></div>');
            jQuery(".cheetaho-mask").fadeIn(100);
            jQuery(".cheetaho-modal.modal-folder-picker").show();

            var picker = jQuery(".cheetaho-folder-picker");
            picker.fileTree({
                script: CheetahOSettings.browseContent,
                multiFolder: false,
                loadMessage:'Loading...'
            });

            return false;
        });

        jQuery(".cheetaho-modal input.select-folder-cancel").click(function(){
            jQuery(".cheetaho-mask").fadeOut(100);
            jQuery('.cheetaho-mask').remove();
            jQuery(".cheetaho-modal.modal-folder-picker").hide();
        });

        jQuery(".cheetaho-modal-footer").on('click', 'input.select-folder', function(){

            // check if selected item is a directory. If so, we are good.
            var selected = jQuery('ul.jqueryFileTree li.directory.selected');

            // if not a file might be selected, check the nearest directory.
            if (jQuery(selected).length == 0 )
                var selected = jQuery('ul.jqueryFileTree li.selected').parents('.directory');

            // fail-saif check if there is really a rel.
            var subPath = jQuery(selected).children('a').attr('rel');

            if (typeof subPath === 'undefined') // nothing is selected
                return;

            subPath = subPath.trim();

            if(subPath) {
                var fullPath = jQuery("#customFolderBase").val() + subPath;

                if(fullPath.slice(-1) == '/') {
                    fullPath = fullPath.slice(0, -1);
                }

                jQuery("#addCustomFolderView").val(fullPath);
                jQuery(".cheetaho-mask").fadeOut(100);
                jQuery('.cheetaho-mask').remove();
                jQuery(".cheetaho-modal.modal-folder-picker").css("display", "none");
                jQuery('#saveAdvAddFolder').removeClass('hidden');
            } else {
                alert("Please select a folder from the list.");
            }
        });
    },

    removeFolder: function(id) {
        var r = confirm(cheetaho_folder_object.confirm_msg);

        if (r == true) {
            jQuery.ajax({
                type: "POST",
                url: cheetaho_folder_object.ajaxurl,
                data: {id:id, action: 'cheetaho_remove_folders'},
                success: function() {
                    location.reload();
                },
                async: false
            });
        }
    },

    browseContent:function(browseData) {
        browseData.action = 'cheetaho_browse_folders';

        var responseData = "<ul class='jqueryFileTree'>";

        jQuery.ajax({
            type: "POST",
            url: cheetaho_folder_object.ajaxurl,
            data: browseData,
            success: function(response) {
                response.forEach(function(item) {
                    responseData += "<li class='directory collapsed "+(item.unselectable == true ? 'unselectable' : '')+"'><a href='#' rel=' /"+item.key+ "/'><i class='dashicons dashicons-category'></i>"+item.title+ "</a></li>";
                });
            },
            async: false
        });

        console.log(responseData);

        responseData += "</ul>";

        return responseData;
    }
};

jQuery(document).ready(function() {
    jQuery('.cheetaho-tabs a').on('click', function(){
        jQuery('.cheetaho-tabs li').removeClass('active');
        jQuery('.cheetaho-tab-content').removeClass('active');
        jQuery(this).parent().addClass('active');
        jQuery(jQuery(this).attr('href')).addClass('active');

        return false;
    });

    CheetahOSettings.initFolderSelector();
});
