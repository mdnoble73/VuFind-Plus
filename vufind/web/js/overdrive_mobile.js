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

function placeOverDriveHold(overDriveId, formatId){
	if (loggedIn){
		if (formatId == undefined){
			selectOverDriveFormat(overDriveId, 'hold')
		}else{
			showProcessingIndicator("Placing a hold on the title for you in OverDrive.  This may take a minute.");
			setTimeout(function(){
				var url = path + "/EcontentRecord/AJAX?method=PlaceOverDriveHold&overDriveId=" + overDriveId + "&formatId=" + formatId;
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
			}, 100);
		}
	}else{
		ajaxLogin(function(){
			placeOverDriveHold(overDriveId, formatId);
		});
	}
	return false;
}

function addOverDriveRecordToWishList(recordId){
	if (loggedIn){
		showProcessingIndicator("Adding the title to your Wish List in OverDrive.  This may take a minute.");
		var url = path + "/EcontentRecord/AJAX?method=AddOverDriveRecordToWishList&recordId=" + recordId;
		$.ajax({
			url: url,
			success: function(data){
				alert(data.message);
				if (data.result){
					window.location.href = path + "/MyResearch/OverdriveWishList";
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
			addOverDriveRecordToWishList(recordId);
		});
	}
}

function removeOverDriveRecordFromWishList(overDriveId){
	if (loggedIn){
		showProcessingIndicator("Removing the title from your Wish List in OverDrive.  This may take a minute.");
		var url = path + "/EcontentRecord/AJAX?method=RemoveOverDriveRecordFromWishList&overDriveId=" + overDriveId;
		$.ajax({
			url: url,
			success: function(data){
				alert(data.message);
				if (data.result){
					window.location.href = path + "/MyResearch/OverdriveWishList";
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
			removeOverDriveRecordFromWishList(overDriveId);
		});
	}
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