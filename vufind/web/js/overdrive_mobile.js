function checkoutOverDriveItem(overdriveId, formatId){
	if (loggedIn){
		if (formatId == undefined){
			selectOverDriveFormat(overdriveId, 'checkout')
		}else{
			var url = path + "/EcontentRecord/AJAX?method=GetOverDriveLoanPeriod&overDriveId=" + overdriveId + "&formatId=" + formatId;
			ajaxLightbox(url);
		}
	}else{
		ajaxLogin(function(){
			checkoutOverDriveItem(overdriveId, formatId);
		});
	}
	return false;
}

function selectOverDriveFormat(overdriveId, nextAction){
	hideLightbox();
	var ajaxUrl = path + "/EcontentRecord/AJAX?method=SelectOverDriveFormat&overDriveId=" + overdriveId + "&nextAction=" + nextAction;
	ajaxLightbox(ajaxUrl);
}

function checkoutOverDriveItemOneClick(overdriveId){
	showProcessingIndicator("Checking out the title for you in OverDrive.  This may take a minute.");
	var ajaxUrl = path + "/EcontentRecord/AJAX?method=CheckoutOverDriveItem&overDriveId=" + overdriveId;
	$.ajax({
		url: ajaxUrl,
		cache: false,
		success: function(data){
			hideLightbox();
			if (data.result == true){
				alert(data.message);
				$.mobile.changePage( path + "/MyResearch/OverdriveCheckedOut");
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

function checkoutOverDriveItemStep2(overdriveId, formatId){
	var lendingPeriod = $("#loanPeriod option:selected").val();
	showProcessingIndicator("Checking out the title for you in OverDrive.  This may take a minute.");
	var url = path + "/EcontentRecord/AJAX?method=CheckoutOverDriveItem&overDriveId=" + overdriveId + "&formatId=" + formatId + "&lendingPeriod=" + lendingPeriod;
	$.ajax({
		url: url,
		success: function(data){
			if (data.result){
				alert(data.message);
				$.mobile.changePage( path + "/MyResearch/OverdriveCheckedOut");
			}else{
				if (data.noCopies == true){
					ret = confirm(data.message)
					if (ret == true){
						placeOverDriveHold(overdriveId, formatId);
					}
				}else{
					alert(data.message);
					hideLightbox();
				}
			}
		},
		dataType: 'json',
		error: function(jqXHR, textStatus, errorThrown){
			alert("An error occurred processing your request in OverDrive. " + errorThrown + " Please try again in a few minutes.");
			hideLightbox();
		}
	});
	return false;
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

function placeOverDriveHold(overDriveId, formatId){
	if (loggedIn){
		//Get any prompts needed for placing holds (e-mail and format depending on the interface.
		var promptInfo = getOverDriveHoldPrompts(overDriveId, formatId, 'hold');
		if (!promptInfo.promptNeeded){
			doOverDriveHold(overDriveId, formatId, promptInfo.overdriveEmail, promptInfo.promptForOverdriveEmail);
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
			if (data.availableForCheckout){
				checkoutOverDriveItem(overdriveId, formatId);
			}else{
				alert(data.message);
				hideLightbox();
				hideProcessingIndicator();
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
		var url = path + "/EcontentRecord/AJAX?method=CancelOverDriveHold&overDriveId=" + overDriveId + "&formatId=" + formatId;
		$.ajax({
			url: url,
			success: function(data){
				alert(data.message);
				if (data.result){
					window.location.href = path + "/MyResearch/OverdriveHolds";
				}else{
					hideLightbox();
				}
			},
			dataType: 'json',
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


function returnOverDriveTitle(overDriveId, transactionId){
	if (confirm('Are you sure you want to return this title?')){
		showProcessingIndicator("Returning your title in OverDrive.  This may take a minute.");
		var ajaxUrl = path + "/EcontentRecord/AJAX?method=ReturnOverDriveItem&overDriveId=" + overDriveId + "&transactionId=" + transactionId;
		$.ajax({
			url: ajaxUrl,
			cache: false,
			success: function(data){
				alert(data.message);
				if (data.result){
					//Reload the page
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
	}
	return false;
}

function selectOverDriveDownloadFormat(overDriveId){
	var selectedFormatId = $("#downloadFormat_" + overDriveId + " option:selected").val();
	var selectedFormatText = $("#downloadFormat_" + overDriveId + " option:selected").text();
	if (selectedFormatId == -1){
		alert("Please select a format to download.");
	}else{
		if (confirm("Are you sure you want to download the " + selectedFormatText + " format? You cannot change format after downloading.")){
			var ajaxUrl = path + "/EcontentRecord/AJAX?method=SelectOverDriveDownloadFormat&overDriveId=" + overDriveId + "&formatId=" + selectedFormatId;
			$.ajax({
				url: ajaxUrl,
				cache: false,
				success: function(data){
					if (data.result){
						//Reload the page
						window.location.href = data.downloadUrl;
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
	}
	return false;
}