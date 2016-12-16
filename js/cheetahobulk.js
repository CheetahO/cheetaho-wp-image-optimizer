(function() {
	function prepareBulkOptimization(items) {
		window.handleAllBulkOptimizationItems = items
		handleProgressBar(0)
	}

	function handleProgressBar(compressionsDone) {
		var totalToOptimize = parseInt(jQuery("div#compression-progress-bar").data("number-to-optimize"));
		var optimizedSoFar = parseInt(jQuery("#optimized-so-far").text());
		
		jQuery("#optimized-so-far").html(compressionsDone + optimizedSoFar);

		var percentage = "100%"
		if (totalToOptimize > 0) {
			percentage = Math.round((compressionsDone + optimizedSoFar) / totalToOptimize * 100, 1) + "%";
		}
		
		jQuery("div#compression-progress-bar #progress-size").css("width", percentage);
		jQuery("div#compression-progress-bar #percentage").html("(" + percentage + ")");
		
	}
	
	function cancelOptimization() {
	    window.optimizationCancelled = true;
	    jQuery(jQuery("#optimization-items tr td.status.todo")).html(cheetahoBulk.chCancelled);
	    jQuery("div#bulk-actions input").removeClass("visible");
	    jQuery("div#bulk-actions input#id-cancelling").addClass("visible");
	    
	    setTimeout( function(){
	    	jQuery("div#bulk-actions  input").removeClass("visible");
	    	jQuery("div#bulk-actions  input#id-start").addClass("visible");
	    	jQuery("td.status").html(jQuery("td.status").html().replace('Compressing',cheetahoBulk.chCancelled));
	    }, 4000);
	}
	

	function drawSomeRows(items, rowsToDraw) {
		var list = jQuery("#optimization-items tbody");
		var row;
		for ( var drawNow = window.totalRowsDrawn; drawNow < Math.min(rowsToDraw + window.totalRowsDrawn, items.length); drawNow++) {
			row = jQuery("<tr class=\"media-item\">"
					+ "<th class=\"thumbnail\" />"
					+ "<td class=\"column-primary name\" />"
					+ "<td class=\"column-author sizes-optimized\" data-colname=\""
					+ cheetahoBulk.chSizesOptimized
					+ "\" ></>"
					+ "<td class=\"column-author initial-size\" data-colname=\""
					+ cheetahoBulk.chInitialSize
					+ "\" ></>"
					+ "<td class=\"column-author optimized-size\" data-colname=\""
					+ cheetahoBulk.chCurrentSize + "\" ></>"
					+ "<td class=\"column-author savings\" data-colname=\""
					+ cheetahoBulk.chSavings + "\" ></>"
					+ "<td class=\"status todo\" data-colname=\""
					+ cheetahoBulk.chStatus + "\" />" + "</tr>");
			row.find(".status").html(cheetahoBulk.chWaiting);
			row.find(".name").html(items[drawNow].title);
			row.find(".thumbnail").html('<img src="'+items[drawNow].thumbnail+'" width="30" />');
			list.append(row);
		}
		
		window.totalRowsDrawn = drawNow;
	}
	  

	function updateRowAfterCompression(row, data) {
		
		var successFullCompressions = parseInt(data.optimizedImages);
		var successFullSaved = parseInt(data.size_change);
		var newHumanReadableLibrarySize = data.humanReadableLibrarySize;
		if (successFullCompressions == 0) {
			row.find(".status").html(cheetahoBulk.chNoActionTaken)
		} else {
			row.find(".status").html(successFullCompressions + " " + cheetahoBulk.chCompressed);
			handleProgressBar(successFullCompressions);
			updateSavings(successFullCompressions, successFullSaved, data.newSize, data.originalImagesSize);
		}
	}
	

	function formatBytes(bytes, decimals) {
		if (bytes == 0)
			return '0 Byte';
		var k = 1000; // or 1024 for binary
		var dm = decimals + 1 || 3;
		var sizes = [ 'Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' ];
		var i = Math.floor(Math.log(bytes) / Math.log(k));
		
		return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
	}
	
	function updateSavings(successFullCompressions, successFullSaved, optimizedSize, originalImagesSize) {

	    window.currentLibraryBytes = window.currentLibraryBytes + successFullSaved;

	    var imagesSizedOptimized = parseInt(jQuery("#optimized-images").text()) + successFullCompressions;
	    var initialLibraryBytes = parseInt(jQuery("#original-images-size").data("bytes"));
	    var percentage = (1 - window.currentLibraryBytes / initialLibraryBytes);
	    var newOriginalImagesSize = initialLibraryBytes + originalImagesSize;

	    jQuery("#optimized-images").html(imagesSizedOptimized);
	    jQuery("#optimized-size").attr("data-bytes", window.currentLibraryBytes);
	   
	    jQuery("#original-images-size").html(formatBytes(newOriginalImagesSize, 1));
	    jQuery("#original-images-size").attr("data-bytes", newOriginalImagesSize);

	    optimizedSize = parseInt(jQuery("#optimized-size").data("bytes"))	+ optimizedSize;
	    jQuery("#optimized-size").html(formatBytes(optimizedSize, 1));
	    jQuery("#savings-percentage").html(Math.round(percentage * 1000) / 10 + "%");

	  }

	function bulkOptimizationCallback(error, data, items, i) {
	    if (window.optimizationCancelled) {
	      handleCancellation();
	    }

	    var row = jQuery("#optimization-items tr").eq(parseInt(i)+1);

	    if (error) {
	      row.addClass("failed");
	      row.find(".status").addClass('failed').html(cheetahoBulk.chInternalError + "<br>" + error.toString());
	      row.find(".status").attr("title", error.toString());
	    } else if (data == null) {
	      row.addClass("failed");
	      row.find(".status").html(cheetahoBulk.chCancelled);
	    } else if (data.error) {
	       if (data.error.http_code == 403) {
	    		//cancelAction();
	       }
	      row.addClass("failed");
	      row.find(".status").addClass('failed').html("<b>" + cheetahoBulk.chError + ":</b>" + data.error.message);
	      row.find(".status").attr("title", data.error.message);
	    } else {
	      row.addClass("success");
	      updateRowAfterCompression(row, data);
	    }

	    row.find(".name").html(items[i].title + "<button class=\"toggle-row\" type=\"button\"><span class=\"screen-reader-text\">" + cheetahoBulk.chShowMoreDetails + "</span></button>");

	    if (!data.original_size) {
	        data.original_size = "-";
	    }
	    if (!data.saved_bytes) {
	        data.saved_bytes = "-";
	    }
	    if (!data.cheetaho_size) {
	        data.cheetaho_size = "-";
	    }
	    if (!data.saved_percent) {
	        data.saved_percent = "0 %";
	    }
	   
	   // row.find(".thumbnail").html('<img src="'+data.thumbnail+'" width="30" />');
	    row.find(".sizes-optimized").html(data.original_size);
	    row.find(".initial-size").html(data.saved_bytes);
	    row.find(".optimized-size").html(data.cheetaho_size);
	    row.find(".savings").html(data.saved_percent);

	    if (items[++i]) {
	      if (!window.optimizationCancelled) {
	        drawSomeRows(items, 1);
	      }
	      bulkOptimizeItem(items, i);
	    } else {
	      var message = jQuery("<div class=\"updated\"><p></p></div>");
	      message.find("p").html(cheetahoBulk.chAllDone);
	      message.insertAfter(jQuery("#bulk-msg"));
	      jQuery("div.progress").css("width", "100%");
	      jQuery("div#bulk-actions").hide();
	      jQuery("div.progress").css("animation", "none");
	    }
	  }


	function startBulkOptimization(items) {
		jQuery("#optimization-items tbody").html('');
		window.optimizationCancelled = false;
		window.totalRowsDrawn = 0;
		window.currentLibraryBytes = parseInt(jQuery("#optimized-size").data("bytes"));

		jQuery("div.progress").css("animation", "progress-bar 80s linear infinite");
		handleProgressBar(0);
		drawSomeRows(items, 10);
		bulkOptimizeItem(items, 0);
	}
	
	  function bulkOptimizeItem(items, i) {
	    if (window.optimizationCancelled) {
	      return;
	    }

	    var item = items[i]
	    var row = jQuery("#optimization-items tr").eq(parseInt(i)+1)
	    row.find(".status").removeClass("todo")
	    row.find(".status").html(cheetahoBulk.chCompressing)
	    jQuery.ajax({
	      url: ajaxurl,
	      type: "POST",
	      dataType: "json",
	      data: {
	        _nonce: cheetahoBulk.nonce,
	        action: "cheetaho_request",
	        id: items[i].ID, 
	       // current_size: window.currentLibraryBytes
	      },
	      success: function(data) { bulkOptimizationCallback(null, data, items, i);},
	      error: function(xhr, textStatus, errorThrown) { bulkOptimizationCallback(errorThrown, null, items, i); }
	    });
	   // jQuery("#optimized-so-far").html(i)
	  }
	

	 function startAction () {
		
		jQuery("div#bulk-actions input#id-start").removeClass("visible");
		jQuery("div#bulk-actions input#id-optimizing").addClass("visible");
		startBulkOptimization(window.handleAllBulkOptimizationItems);
			
	 }
	 
	 function cancelAction () {	
		window.lastActiveButton.addClass("visible")
		jQuery("div#bulk-actions input#id-cancel").removeClass("visible");
		cancelOptimization();	
	 }
	 
	 function optimizingAction () {	
	      window.lastActiveButton = jQuery("div#bulk-actions input.visible")
	      lastActiveButton.removeClass("visible")
	      jQuery("div#bulk-actions input#id-cancel").addClass("visible")
	
	 }



	window.bulkOptimization = prepareBulkOptimization;
	window.startAction = startAction;
	window.cancelAction = cancelAction;
	window.optimizingAction = optimizingAction;
}).call();
