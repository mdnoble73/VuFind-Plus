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
	$("#updateRequests").submit();
	return true;
}

function updateSelectedRequests(){
	var newStatus = $("#newStatus").val();
	if (newStatus == "unselected"){
		alert("Please select a status to update the requests to.");
		return false;
	}
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

function setIsbnAndOclcNumber(title, author, isbn, oclcNumber){
	$("#title").val(title);
	$("#author").val(author);
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
		$("#authorFieldLabel").html("Actor / Director <span class='requiredIndicator'>*</span>:");
	}else if (selectedFormat == 'cdMusic'){
		$("#authorFieldLabel").html("Artist / Composer <span class='requiredIndicator'>*</span>:");
	}else{
		$("#authorFieldLabel").html("Author <span class='requiredIndicator'>*</span>:");
	}
	
	if (selectedFormat == 'article'){
		$("#magazineTitle").addClass('required');
		$("#magazineDate").addClass('required');
		$("#magazineVolume").addClass('required');
		$("#magazineNumber").addClass('required');
		$("#magazinePageNumbers").addClass('required');
		$("#acceptCopyrightYes").addClass('required');
		$("#supplementalDetails").hide();
		$("#titleLabel").html("Article Title <span class='requiredIndicator'>*</span>:");
	}else{
		$("#magazineTitle").removeClass('required');
		$("#magazineDate").removeClass('required');
		$("#magazineVolume").removeClass('required');
		$("#magazineNumber").removeClass('required');
		$("#magazinePageNumbers").removeClass('required');
		$("#acceptCopyrightYes").removeClass('required');
		$("#supplementalDetails").show();
		$("#titleLabel").html("Title <span class='requiredIndicator'>*</span>:");
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

function materialsRequestLogin(){
	var url = path + "/AJAX/JSON?method=loginUser"
	$.ajax({url: url,
		data: {username: $('#username').val(), password: $('#password').val()},
		success: function(response){
			if (response.result.success == true){
				//Update the main display to show the user is logged in
				// Hide "log in" options and show "log out" options:
				$('.loginOptions').hide();
        $('.logoutOptions').show();
				$('#myAccountNameLink').html(response.result.name);
				if (response.result.enableMaterialsRequest){
					$('#materialsRequestLogin').hide();
					$('.materialsRequestLoggedInFields').show();
					if (response.result.phone){
						$('#phone').val(response.result.phone);
					}
					if (response.result.email){
						$('#email').val(response.result.email);
					}
					if (response.result.homeLocation){
						$("#pickupLocation").val(response.result.homeLocation);
					}
				}else{
					alert("Sorry, materials request functionality is only available to residents at this time.");
				}
			}else{
				alert("That login was not recognized.  Please try again.");
				return false;
			}
		},
		error: function(jqXHR, textStatus, errorThrown){
			alert("That login was not recognized.  Please try again.");
			return false;
		},
		dataType: 'json',
		type: 'post' 
	});
	return false;
}

function printRequestBody(){
	$("#request_details_body").printElement();
}