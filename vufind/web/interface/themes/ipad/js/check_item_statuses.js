$('.results-page').live('pageshow', function() {
	checkItemStatuses();
});

function checkItemStatuses() {
	var id = $.map($('.recordId'), function(i) {
		return $(i).attr('id').substr('record'.length);
	});
	if (id.length) {
		$(".ajax_availability").show();
		$.ajax( {
		  dataType : 'json',
		  url : path + '/AJAX/JSON?method=getItemStatuses',
		  data : {
			  id : id
		  },
		  success : function(response) {
			  if (response.status == 'OK') {
				  $.each(response.data, function(i, result) {
					  $('.callnumber' + result.shortId).empty().append(result.callnumber);
					  $('.location' + result.shortId).empty().append(result.reserve == 'true' ? result.reserve_message : result.location);
					  $('.status' + result.shortId).empty().append(result.availability_message);
				  });
			  } else {
				  // display the error message on each of the ajax status place holder
				  $(".ajax_availability").empty().append(response.data);
			  }
			  $(".ajax_availability").removeClass('ajax_availability');
		  }
		});
	}
}
