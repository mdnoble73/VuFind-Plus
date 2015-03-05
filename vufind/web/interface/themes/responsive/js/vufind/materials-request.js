/**
 * Created by mark on 5/19/14.
 */
VuFind.MaterialsRequest = (function(){
	return {
		getWorldCatIdentifiers: function(){
			var title = $("#title").val();
			var author = $("#author").val();
			var format = $("#format").val();
			if (title == '' && author == ''){
				alert("Please enter a title and author before checking for an ISBN and OCLC Number");
			}else{
				var requestUrl = Globals.path + "/MaterialsRequest/AJAX?method=GetWorldCatIdentifiers&title=" + encodeURIComponent(title) + "&author=" + encodeURIComponent(author)  + "&format=" + encodeURIComponent(format);
				$.getJSON(requestUrl, function(data){
					if (data.success == true){
						//Dislay the results of the suggestions
						var suggestedIdentifiers = $("#suggestedIdentifiers");
						suggestedIdentifiers.html(data.formattedSuggestions);
						suggestedIdentifiers.slideDown();
					}else{
						alert(data.error);
					}
				});
			}
			return false;
		},

		cancelMaterialsRequest: function(id){
			if (confirm("Are you sure you want to cancel this request?")){
				var url = Globals.path + "/MaterialsRequest/AJAX?method=CancelRequest&id=" + id;
				$.getJSON(
						url,
						function(data){
							if (data.success){
								alert("Your request was cancelled successfully.");
								window.location.reload();
							}else{
								alert(data.error);
							}
						}
				);
				return false;
			}else{
				return false;
			}
		},

		showMaterialsRequestDetails: function(id){
			VuFind.Account.ajaxLightbox(Globals.path + "/MaterialsRequest/AJAX?method=MaterialsRequestDetails&id=" +id, true);
		},

		updateMaterialsRequest: function(id){
			VuFind.Account.ajaxLightbox(Globals.path + "/MaterialsRequest/AJAX?method=UpdateMaterialsRequest&id=" +id, true);
		},

		exportSelectedRequests: function(){
			var selectedRequests = this.getSelectedRequests(true);
			if (selectedRequests.length == 0){
				return false;
			}
			$("#updateRequests").submit();
			return true;
		},

		updateSelectedRequests: function(){
			var newStatus = $("#newStatus").val();
			if (newStatus == "unselected"){
				alert("Please select a status to update the requests to.");
				return false;
			}
			var selectedRequests = this.getSelectedRequests(false);
			if (selectedRequests.length != 0){
				$("#updateRequests").submit();
			}
			return false;
		},

		getSelectedRequests: function(promptToSelectAll){
			var selectedRequests = $("input.select:checked").map(function() {
				return $(this).attr('name') + "=" + $(this).val();
			}).get().join("&");
			if (selectedRequests.length == 0){
				if (promptToSelectAll){
					var ret = confirm('You have not selected any requests, process all requests?');
					if (ret == true){
						selectedRequests = $("input.select").map(function() {
							return $(this).attr('name') + "=on";
						}).get().join("&");
						$('.select').attr('checked', 'checked');
					}
				}else{
					alert("Please select one or more requests to update");
				}
			}
			return selectedRequests;
		},

		setIsbnAndOclcNumber: function(title, author, isbn, oclcNumber){
			$("#title").val(title);
			$("#author").val(author);
			$("#isbn").val(isbn);
			$("#oclcNumber").val(oclcNumber);
			$("#suggestedIdentifiers").slideUp();
		},

		setFieldVisibility: function(){
			$(".formatSpecificField").hide();
			//Get the selected format
			var selectedFormat = $("#format").find("option:selected").val();
			$("." + selectedFormat + "Field").show();

			//Update labels as neded
			var author = $("#author");
			author.addClass("required");
			if (selectedFormat == 'bluray' || selectedFormat == 'dvd' || selectedFormat == 'vhs'){
				$("#authorFieldLabel").html("Actor / Director:");
				author.removeClass("required");
			}else if (selectedFormat == 'cdMusic'){
				$("#authorFieldLabel").html("Artist / Composer <span class='requiredIndicator'>*</span>");
			}else{
				$("#authorFieldLabel").html("Author <span class='requiredIndicator'>*</span>");
			}

			if (selectedFormat == 'article'){
				$("#magazineTitle").addClass('required');
				//$("#magazineDate").addClass('required');
				//$("#magazineVolume").addClass('required');
				//$("#magazineNumber").addClass('required');
				//$("#magazinePageNumbers").addClass('required');
				$("#copyright").show();
				$("#acceptCopyrightYes").addClass('required');
				$("#supplementalDetails").hide();
				$("#titleLabel").html("Article Title <span class='requiredIndicator'>*</span>");
			}else{
				$("#magazineTitle").removeClass('required');
				//$("#magazineDate").removeClass('required');
				//$("#magazineVolume").removeClass('required');
				//$("#magazineNumber").removeClass('required');
				//$("#magazinePageNumbers").removeClass('required');
				$("#copyright").hide();
				$("#acceptCopyrightYes").removeClass('required');
				$("#supplementalDetails").show();
				$("#titleLabel").html("Title <span class='requiredIndicator'>*</span>");
			}
			if (selectedFormat == 'ebook' || selectedFormat == 'eaudio'){
				$("#illInfo").hide();
				$("#pickupLocationField").hide();
			}else{
				$("#illInfo").show();
				$("#pickupLocationField").show();
			}
		},

		updateHoldOptions: function(){
			var placeHold = $("input[name=placeHoldWhenAvailable]:checked").val();
			if (placeHold == 1){
				$("#pickupLocationField").show();
				if ($("#pickupLocation").find("option:selected").val() == 'bookmobile'){
					$("#bookmobileStopField").show();
				}else{
					$("#bookmobileStopField").hide();
				}
			}else{
				$("#bookmobileStopField").hide();
				$("#pickupLocationField").hide();
			}
		},

		materialsRequestLogin: function(){
			var url = Globals.path + "/AJAX/JSON?method=loginUser";
			$.ajax({url: url,
				data: {username: $('#username').val(), password: $('#password').val()},
				success: function(response){
					if (response.result.success == true){
						//Update the main display to show the user is logged in
						// Hide "log in" options and show "log out" options:
						$('.loginOptions').hide();
						$('.logoutOptions').show();
						$('#myAccountNameLink').html(response.result.name);
						if (response.result.enableMaterialsRequest == 1){
							$('#materialsRequestLogin').hide();
							$('.materialsRequestLoggedInFields').show();
							if (response.result.phone){
								$('#phone').val(response.result.phone);
							}
							if (response.result.email){
								$('#email').val(response.result.email);
							}
							if (response.result.homeLocationId){
								var optionToSelect = $("#pickupLocation").find("option[value=" + response.result.homeLocationId + "]");
								optionToSelect.attr("selected", "selected");
							}
						}else{
							alert("Sorry, materials request functionality is only available to residents at this time.");
						}
					}else{
						alert("That login was not recognized.  Please try again.");
					}
				},
				error: function(){
					alert("That login was not recognized.  Please try again.");
				},
				dataType: 'json',
				type: 'post'
			});
			return false;
		},

		printRequestBody: function(){
			$("#request_details_body").printElement();
		}
	};
}(VuFind.MaterialsRequest || {}));