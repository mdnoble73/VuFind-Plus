function getWorldCatIdentifiers(){
	var title = $("#title").val();
	var author = $("#author").val();
	var format = $("#format").val();
	if (title == '' && author == ''){
		alert("Please enter a title and author before checking for an ISBN and OCLC Number");
		return false;
	}else{
		var requestUrl = path + "/MaterialsRequest/AJAX?method=GetWorldCatIdentifiers&title=" + encodeURIComponent(title) + "&author=" + encodeURIComponent(author)  + "&format=" + encodeURIComponent(format);
		$.getJSON(requestUrl, function(data){
			if (data.success == true){
				//Dislay the results of the suggestions
				$("#suggestedIdentifiers").html(data.formattedSuggestions);
				$("#suggestedIdentifiers").slideDown();
			}else{
				alert(data.error);
			}
		});
	}
}

function cancelMaterialsRequest(id){
	if (confirm("Are you sure you want to cancel this request?")){
		var url = path + "/MaterialsRequest/AJAX?method=CancelRequest&id=" + id;
		$.getJSON(
			url,
			function(data){
				if (data.success){
					alert("Your request was cancelled successfully.");
					window.location.reload();
					return true;
				}else{
					alert(data.error);
				}
				
			}
		);
		return false;
	}else{
		return false;
	}
}

function showMaterialsRequestDetails(id){
	ajaxLightbox(path + "/MaterialsRequest/AJAX?method=MaterialsRequestDetails&id=" +id );
}

function updateMaterialsRequest(id){
	ajaxLightbox(path + "/MaterialsRequest/AJAX?method=UpdateMaterialsRequest&id=" +id );
}

function exportSelectedRequests(){
	var selectedRequests = getSelectedRequests();
	if (selectedRequests.length == 0){
		return false;
	}
	$.("#updateRequests").submit();
	return true;
}

function updateSelectedRequests(){
	var selectedRequests = getSelectedRequests();
	if (selectedRequests.length == 0){
		return false;
	}
	return true;
}

function getSelectedRequests(){
	var selectedRequests = $("input.select:checked").map(function() {
		return $(this).attr('name') + "=" + $(this).val();
	}).get().join("&");
	if (selectedRequests.length == 0){
		var ret = confirm('You have not selected any requests, process all requests?');
		if (ret == true){
			selectedRequests = $("input.select").map(function() {
				return $(this).attr('name') + "=on";
			}).get().join("&");
			$('.select').attr('checked', 'checked');
		}
	}
	return selectedRequests;
}

function setIsbnAndOclcNumber(isbn, oclcNumber){
	$("#isbn").val(isbn);
	$("#oclcNumber").val(oclcNumber);
	$("#suggestedIdentifiers").slideUp();
}

function setFieldVisibility(){
	$(".formatSpecificField").hide();
	//Get the selected format 
	var selectedFormat = $("#format option:selected").val();
	$("." + selectedFormat + "Field").show();
	
	//Update labels as neded 
	if (selectedFormat == 'dvd' || selectedFormat == 'vhs'){
		$("#authorFieldLabel").html("Actor / Director: ");
	}else if (selectedFormat == 'cdMusic'){
		$("#authorFieldLabel").html("Artist / Composer: ");
	}else{
		$("#authorFieldLabel").html("Author: ");
	}
	
	if (selectedFormat == 'article'){
		$("#magazineTitle").addClass('required');
		$("#magazineDate").addClass('required');
		$("#magazineVolume").addClass('required');
		$("#magazinePageNumbers").addClass('required');
		$("#supplementalDetails").hide();
	}else{
		$("#magazineTitle").removeClass('required');
		$("#magazineDate").removeClass('required');
		$("#magazineVolume").removeClass('required');
		$("#magazinePageNumbers").removeClass('required');
		$("#supplementalDetails").show();
	}
}

function updateHoldOptions(){
	var placeHold = $("input[name=placeHoldWhenAvailable]:checked").val();
	if (placeHold == 1){
		$("#pickupLocationField").show();
		if ($("#pickupLocation option:selected").val() == 'bookmobile'){
			$("#bookmobileStopField").show();
		}else{
			$("#bookmobileStopField").hide();
		}
	}else{
		$("#bookmobileStopField").hide();
		$("#pickupLocationField").hide();
	}
	
} 
