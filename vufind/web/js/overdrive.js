function checkoutOverDriveItem(overdriveId, formatId){
	if (loggedIn){
		if (formatId == undefined){
			selectOverDriveFormat(overdriveId, 'checkout')
		}else{
			var ajaxUrl = path + "/EcontentRecord/AJAX?method=GetOverDriveLoanPeriod&overDriveId=" + overdriveId + "&formatId=" + formatId;
			ajaxLightbox(ajaxUrl);
		}
	}else{
		ajaxLogin(function(){
			checkoutOverDriveItem(overdriveId, formatId);
		});
	}
	return false;
}

function selectOverDriveFormat(overdriveId, nextAction){
	var ajaxUrl = path + "/EcontentRecord/AJAX?method=SelectOverDriveFormat&overDriveId=" + overdriveId + "&nextAction=" + nextAction;
	ajaxLightbox(ajaxUrl);
}

function checkoutOverDriveItemStep2(overdriveId, formatId){
	var lendingPeriod = $("#loanPeriod option:selected").val();
	showProcessingIndicator("Checking out the title for you in OverDrive.  This may take a minute.");
	var ajaxUrl = path + "/EcontentRecord/AJAX?method=CheckoutOverDriveItem&overDriveId=" + overdriveId + "&formatId=" + formatId + "&lendingPeriod=" + lendingPeriod;
	$.ajax({
		url: ajaxUrl,
		cache: false,
		success: function(data){
			hideLightbox();
			if (data.result == true){
				alert(data.message);
				window.location.href = path + "/MyResearch/OverdriveCheckedOut";
			}else{
				if (data.noCopies == true){
					ret = confirm(data.message)
					if (ret == true){
						placeOverDriveHold(overdriveId, formatId);
					}
				}else{
					alert(data.message);
				}
			}
		},
		dataType: 'json',
		async: false,
		error: function(jqXHR, textStatus, errorThrown){
			alert("An error occurred processing your request in OverDrive.  Please try again in a few minutes.");
			alert("ajaxUrl = " + ajaxUrl);
			hideLightbox();
		}
	});
}

function processOverDriveHoldPrompts(){
	var overdriveId = $("#overdriveHoldPromptsForm input[name=overdriveId]").val();
	var formatId = -1;
	if ($("#overdriveHoldPromptsForm input[name=formatId]") && $("#overdriveHoldPromptsForm input[name=formatId]").val() != undefined){
		formatId = $("#overdriveHoldPromptsForm input[name=formatId]").val();
		if (formatId == undefined){
			formatId = "";
		}
	}else if($('#formatId :selected') && $('#formatId :selected').val() != undefined){
		formatId = $('#formatId :selected').val();
	}
	if ($("#overdriveHoldPromptsForm input[name=promptForOverdriveEmail]").is(":checked")){
		promptForOverdriveEmail = 0;
	}else{
		promptForOverdriveEmail = 1;
	}
	var overdriveEmail = $("#overdriveHoldPromptsForm input[name=overdriveEmail]").val();
	doOverDriveHold(overdriveId, formatId, overdriveEmail, promptForOverdriveEmail);
}

function placeOverDriveHold(overDriveId, formatId, overdriveEmail, promptForOverdriveEmail){
	if (loggedIn){
		//Get any prompts needed for placing holds (e-mail and format depending on the interface.
		if (!getOverDriveHoldPrompts(overDriveId, formatId, 'hold')){
			doOverDriveHold(overDriveId, formatId, overdriveEmail, promptForOverdriveEmail);
		}
	}else{
		ajaxLogin(function(){
			placeOverDriveHold(overDriveId, formatId);
		});
	}
	return false;
}

function doOverDriveHold(overDriveId, formatId, overdriveEmail, promptForOverdriveEmail){
	showProcessingIndicator("Placing a hold on the title for you in OverDrive.  This may take a minute.");
	var url = path + "/EcontentRecord/AJAX?method=PlaceOverDriveHold&overDriveId=" + overDriveId + "&formatId=" + formatId + "&overdriveEmail=" + overdriveEmail + "&promptForOverdriveEmail=" + promptForOverdriveEmail;
	$.ajax({
		url: url,
		cache: false,
		success: function(data){
			alert(data.message);
			hideLightbox();
		},
		dataType: 'json',
		async: false,
		error: function(){
			alert("An error occurred processing your request in OverDrive.  Please try again in a few minutes.");
			hideLightbox();
		}
	});
}

function getOverDriveHoldPrompts(overDriveId, formatId, nextAction){
	var url = path + "/EcontentRecord/AJAX?method=GetOverDriveHoldPrompts&overDriveId=" + overDriveId;
	if (formatId != undefined){
		url += "&formatId=" + formatId;
	}
	var result = true;
	$.ajax({
		url: url,
		cache: false,
		success: function(data){
			if (data.promptNeeded){
				showHtmlInLightbox(data.promptTitle, data.prompts);
				result = true;
			}else{
				result = false;
			}
		},
		dataType: 'json',
		async: false,
		error: function(){
			alert("An error occurred processing your request in OverDrive.  Please try again in a few minutes.");
			hideLightbox();
		}
	});
	return result;
}

function addOverDriveRecordToWishList(recordId){
	if (loggedIn){
		showProcessingIndicator("Adding the title to your Wish List in OverDrive.  This may take a minute.");
		var url = path + "/EcontentRecord/AJAX?method=AddOverDriveRecordToWishList&recordId=" + recordId;
		$.ajax({
			url: url,
			cache: false,
			success: function(data){
				alert(data.message);
				if (data.result){
					window.location.href = path + "/MyResearch/OverdriveWishList";
				}else{
					hideLightbox();
				}
			},
			dataType: 'json',
			async: false,
			error: function(){
				alert("An error occurred processing your request in OverDrive.  Please try again in a few minutes.");
				hideLightbox();
			}
		});
	}else{
		ajaxLogin(function(){
			addOverDriveRecordToWishList(recordId);
		});
	}
}

function removeOverDriveRecordFromWishList(overDriveId){
	if (loggedIn){
		showProcessingIndicator("Removing the title from your Wish List in OverDrive.  This may take a minute.");
		var ajaxUrl = path + "/EcontentRecord/AJAX?method=RemoveOverDriveRecordFromWishList&overDriveId=" + overDriveId;
		$.ajax({
			url: ajaxUrl,
			cache: false,
			success: function(data){
				alert(data.message);
				if (data.result){
					window.location.href = path + "/MyResearch/OverdriveWishList";
				}else{
					hideLightbox();
				}
			},
			dataType: 'json',
			async: false,
			error: function(){
				alert("An error occurred processing your request in OverDrive.  Please try again in a few minutes.");
				hideLightbox();
			}
		});
	}else{
		ajaxLogin(function(){
			removeOverDriveRecordFromWishList(overDriveId);
		});
	}
}

function cancelOverDriveHold(overDriveId, formatId){
	if (loggedIn){
		showProcessingIndicator("Cancelling your hold in OverDrive.  This may take a minute.");
		var ajaxUrl = path + "/EcontentRecord/AJAX?method=CancelOverDriveHold&overDriveId=" + overDriveId + "&formatId=" + formatId;
		$.ajax({
			url: ajaxUrl,
			cache: false,
			success: function(data){
				alert(data.message);
				if (data.result){
					window.location.href = window.location.href ;
				}else{
					hideLightbox();
				}
			},
			dataType: 'json',
			async: false,
			error: function(){
				alert("An error occurred processing your request in OverDrive.  Please try again in a few minutes.");
				hideLightbox();
			}
		});
	}else{
		ajaxLogin(function(){
			cancelOverDriveHold(overDriveId, formatId);
		});
	}
}