



var overDriveHoldInProgress = false;
function placeOverDriveHold(overDriveId, formatId){
	if (overDriveHoldInProgress){
		return;
	}
	overDriveHoldInProgress = true;
	if (loggedIn){
		//Get any prompts needed for placing holds (e-mail and format depending on the interface.
		var promptInfo = getOverDriveHoldPrompts(overDriveId, formatId, 'hold');
		if (!promptInfo.promptNeeded){
			doOverDriveHold(overDriveId, formatId, promptInfo.overdriveEmail, promptInfo.promptForOverdriveEmail);
		}
	}else{
		ajaxLogin(function(){
			overDriveHoldInProgress = false;
			placeOverDriveHold(overDriveId, formatId);
		});
	}
	overDriveHoldInProgress = false;
	return false;
}

function doOverDriveHold(overDriveId, formatId, overdriveEmail, promptForOverdriveEmail){
	showProcessingIndicator("Placing a hold on the title for you in OverDrive.  This may take a minute.");
	var url = path + "/EcontentRecord/AJAX?method=PlaceOverDriveHold&overDriveId=" + overDriveId + "&formatId=" + formatId + "&overdriveEmail=" + overdriveEmail + "&promptForOverdriveEmail=" + promptForOverdriveEmail;
	$.ajax({
		url: url,
		cache: false,
		success: function(data){
			if (data.availableForCheckout){
				checkoutOverDriveItem(overdriveId, formatId);
			}else{
				alert(data.message);
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
			result = data;
			if (data.promptNeeded){
				showHtmlInLightbox(data.promptTitle, data.prompts);
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
					location.reload();
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





