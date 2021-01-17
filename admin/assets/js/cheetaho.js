function removeInputValsIfNotSet() {
	
	if(jQuery("#authUser").data('val') == ''){
		jQuery("#authUser").val("");
	}
	
	if(jQuery("#authPass").data('val') == ''){
		jQuery("#authPass").val("");
	}
}

jQuery(document).ready(function($) {
	
	setTimeout(removeInputValsIfNotSet(), 1000);

	setTimeout(function () {
		if( jQuery('div.media-frame.mode-grid').length > 0) { 
	    //the media table is not in the list mode, alert the user
	    	jQuery('div.media-frame.mode-grid').before('<div id="cheetaho-media-alert" class="notice notice-warning"><p>' 
	    		+ cheetaho_object.changeMLToListMode 
	    		+ '<a href="upload.php?mode=list" class="view-list">' 
	    		+ cheetaho_object.changeMLToListMode1 
	    		+' </a>',
	            + '</p></div>');
		 }
	}, 2000);
	
	 var data = {
			 action: 'cheetaho_request'
	        },
     errorTpl = '<div class="cheetahoErrorWrap"><a class="cheetahoError">Failed! Hover here<span></span></a></div>',
     $btnApplyBulkAction = $("#doaction"),
     $btnApplyBulkAction2 = $("#doaction2"),
     $topActionDropdown = $(".tablenav.top .bulkactions select[name='action']"),
     $bottomActionDropdown = $(".tablenav.bottom .bulkactions select[name='action2']");


	 var requestSuccess = function(data) {
        var $button = $(this),
            $parent = $(this).parent(),
            $cell = $(this).closest("td");

        if (data.success && typeof data.error === 'undefined') {

            $button.text("Image optimized");

            var originalSize = data.original_size,
                $originalSizeColumn = $(this).parent().prev("td.original_size");

            $parent.fadeOut("fast", function() {
                $cell.find(".noSavings, .cheetahoErrorWrap").remove();
                $(this).replaceWith(data.html);
                $originalSizeColumn.html(originalSize);
                $parent.remove();
            });

        } else if (data.error) {

            var $error = $(errorTpl).attr("title", data.error.message);
            $error
            .find("span").html( data.error.message);
            
            $parent
                .closest("td")
                .find(".cheetahoErrorWrap")
                .remove();
            
            $parent.after($error);

            $button
                .text("Retry request")
                .removeAttr("disabled")
                .css({
                    opacity: 1
                });
        }
    };
    
    var requestFail = function() {
        $(this).removeAttr("disabled");
    };
    
    var requestComplete = function() {
        $(this).removeAttr("disabled");
        $(this)
            .parent()
            .find(".cheetahoSpinner")
            .css("display", "none");
    };

    var opts = '<option value="cheetaho-bulk-lossy">Optimize all with CheetahO</option>';

    $topActionDropdown.find("option:last-child").before(opts);
    $bottomActionDropdown.find("option:last-child").before(opts);
    
    var getBulkImageData = function() {
        var array = [];
        $("tr[id^='post-']").each(function() {
            var $row = $(this);
            var postId = this.id.replace(/^\D+/g, '');  
            if ($row.find("input[type='checkbox'][value='" + postId + "']:checked").length) {
                var Btn = $row.find(".cheetaho_req");

                if (Btn.length) {
                    var btnData = Btn.data();
                  
                    var originalSize = $.trim($row.find('td.original_size').text());
                    btnData.originalSize = originalSize;
                    array.push(btnData);
                }
            }
        });
       
        return array;
    };
    
    var imageOptimization = function(bulkImageData) {
    		
    		var selectedFiles = bulkImageData.length;
    		
    		if (selectedFiles > 0) {
	    		var mask = $('body').append('<div class="cheetaho-mask"></div>');
	    		var popup =  $('<div id="cheetaho-bulk-modal" class="cheetaho-modal"></div>').html("<h2>Cheetaho bulk image optimization</h2>");
	    		
	    		var progressBar = $('<div  id="cheetaho-bulk-progressbar"><div id="cheetaho-bulk-progressbar-status">0%</div></div>');
	    		var error = $('<div class="msg-err"></div>');
	
	    		bulkAction(bulkImageData);
	    		popup.append(progressBar);
	    		popup.append(error);
	    		mask.prepend(popup);
    		} else {
    			alert('No need to optimize selected files anymore.');
    		}
    };
    
    var bulkAction = function(bulkImageData) {
    	var selectedFiles = bulkImageData.length;
    	var processed = 0;

        var q = async.queue(function(task, callback) {
            var id = task.id;

            $.ajax({
                url: cheetaho_object.url,
                data: {
                    'action': 'cheetaho_request',
                    'id': id      
                },
                type: "post",
                dataType: "json",
                timeout: 360000
            }).done(function(data) {
                    if (typeof data.error === 'undefined') {
                    	processed++;  
                    	var percents = processed*100/selectedFiles;
                    	$('#cheetaho-bulk-progressbar-status').html(Math.round(percents) +'%').css({'width': percents+'%'});
                    	
                    	if (processed == selectedFiles) {
                    		setTimeout(function(){
                    			$('.cheetaho-mask').remove();
                    			$('#cheetaho-bulk-modal').remove();
                    			location.reload();
                    		}, 1000);
                    		
                    	}

                    } else if (data.error) {
                         $('.msg-err').html('Some images can not be optimized. Message from server:<br />' + data.error.message);
                         setTimeout(function(){
                 			$('.cheetaho-mask').remove();
                 			$('#cheetaho-bulk-modal').remove();
                 		}, 3000);
                    }

            }).fail(function() {

            }).always(function() {
                callback();
            });
        }, 1);

      

        // add some items to the queue (batch-wise)
        q.push(bulkImageData, function() {});
    };
    
    $btnApplyBulkAction.add($btnApplyBulkAction2)
    .click(function(e) {
        if ($(this).prev("select").val() === 'cheetaho-bulk-lossy') {
            e.preventDefault();
            var bulkImageData = getBulkImageData();
          
            imageOptimization(bulkImageData);         
        }
    });  
    
	$('body').on('click', 'small.cheetahoReset', function(e) {
	    e.preventDefault();
	    var $resetButton = $(this);
	    var resetData = {
	        action: 'cheetaho_reset'
	    };
	    resetData.id = $(this).data("id");

        $resetButton.find('.cheetahoSpinner').show();
	
	    $.ajax({
	            url: cheetaho_object.url,
	            data: resetData,
	            type: "post",
	            dataType: "json",
	            timeout: 360000
	        }).done(function(data) {
	            if (data.success == true) {
	                $resetButton
	                    .closest('.buttonWrap')
	                    .hide()
	                    .html(data.html).fadeIn();
	            }
	        });
	});
	 
	$('body').on("click", ".cheetaho_req", function(e) {
	     e.preventDefault();
	     var $button = $(this),
	         $parent = $(this).parent();
	
	     data.id = $(this).data("id");
	
	     $button
	         .text("Optimizing image...")
	         .attr("disabled", true)
	         .css({
	             opacity: 0.5
	         });
	
	
	     $parent
	         .find(".cheetahoSpinner")
	         .css("display", "inline");
	
	
	     $.ajax({
	         url: cheetaho_object.url,
	         data: data,
	         type: "post",
	         dataType: "json",
	         timeout: 360000,
	         context: $button
	     }).done(requestSuccess).fail(requestFail).always(requestComplete);
	
	 });
	
	var resizeAlert = false;
	
	jQuery(".resize-sizes").blur(function(e){
        var elm = jQuery(this);
        
        if(resizeAlert == elm.val()) return;
        resizeAlert = elm.val();
        
        var minSize = jQuery("#min-" + elm.data('type')).val();
        if(elm.val() < Math.min(minSize, 1024)) {
            alert( cheetaho_object.resizeAlert1.replace('{type}', elm.data('type')));
            
            e.preventDefault();
            elm.focus();
        }
        else {
            this.defaultValue = elm.val();
        }
    });
});