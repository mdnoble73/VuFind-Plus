VuFind.OverDrive = (function(){
	return {
		cancelOverDriveHold: function(overdriveId){
			var ajaxUrl = Globals.path + "/EcontentRecord/AJAX?method=CancelOverDriveHold&overDriveId=" + overdriveId;
			$.ajax({
				url: ajaxUrl,
				cache: false,
				success: function(data){
					if (data.result){
						VuFind.showMessage("Hold Cancelled", data.message, true);
						//remove the row from the holds list
						$("#overDriveHold_" + overdriveId).hide();
					}else{
						VuFind.showMessage("Error Cancelling Hold", data.message, false);
					}
				},
				dataType: 'json',
				async: false,
				error: function(){
					VuFind.showMessage("Error Cancelling Hold", "An error occurred processing your request in OverDrive.  Please try again in a few minutes.", false);
				}
			});
			return false;
		},

		checkoutOverDriveItemOneClick: function(overdriveId){
			if (Globals.loggedIn){
				var ajaxUrl = Globals.path + "/EcontentRecord/AJAX?method=CheckoutOverDriveItem&overDriveId=" + overdriveId;
				$.ajax({
					url: ajaxUrl,
					cache: false,
					success: function(data){
						if (data.result == true){
							VuFind.showMessage("Title Checked Out Successfully", data.message, true);
							window.location.href = Globals.path + "/MyResearch/OverdriveCheckedOut";
						}else{
							if (data.noCopies == true){
								VuFind.closeLightbox();
								ret = confirm(data.message);
								if (ret == true){
									VuFind.OverDrive.placeOverDriveHold(overdriveId, formatId);
								}
							}else{
								VuFind.showMessage("Error Checking Out Title", data.message, false);
							}
						}
					},
					dataType: 'json',
					async: false,
					error: function(){
						alert("An error occurred processing your request in OverDrive.  Please try again in a few minutes.");
						alert("ajaxUrl = " + ajaxUrl);
						hideLightbox();
					}
				});
			}else{
				VuFind.Account.ajaxLogin(null, function(){
					checkoutOverDriveItemOneClick(overdriveId);
				}, false);
			}
		},

		doOverDriveHold: function(overDriveId, formatId, overdriveEmail, promptForOverdriveEmail){
			var url = Globals.path + "/EcontentRecord/AJAX?method=PlaceOverDriveHold&overDriveId=" + overDriveId + "&formatId=" + formatId + "&overdriveEmail=" + overdriveEmail + "&promptForOverdriveEmail=" + promptForOverdriveEmail;
			$.ajax({
				url: url,
				cache: false,
				success: function(data){
					if (data.availableForCheckout){
						VuFind.OverDrive.checkoutOverDriveItemOneClick(overdriveId);
					}else{
						VuFind.showMessage("Placed Hold", data.message, true);
					}
				},
				dataType: 'json',
				async: false,
				error: function(){
					VuFind.showMessage("Error Placing Hold", "An error occurred processing your request in OverDrive.  Please try again in a few minutes.", false);
				}
			});
		},

		getOverDriveHoldPrompts: function(overDriveId, formatId){
			var url = Globals.path + "/EcontentRecord/AJAX?method=GetOverDriveHoldPrompts&overDriveId=" + overDriveId;
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
						VuFind.showMessage(data.promptTitle, data.prompts, false);
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
		},

		getOverDriveSummary: function(){
			$.getJSON(Globals.path + '/MyResearch/AJAX?method=getOverDriveSummary', function (data){
				if (data.error){
					// Unable to load overdrive summary
				}else{
					// Load checked out items
					$(".checkedOutItemsOverDrivePlaceholder").html(data.numCheckedOut);
					// Load available holds
					$(".availableHoldsOverDrivePlaceholder").html(data.numAvailableHolds);
					// Load unavailable holds
					$(".unavailableHoldsOverDrivePlaceholder").html(data.numUnavailableHolds);
					// Load wishlist
					$(".wishlistOverDrivePlaceholder").html(data.numWishlistItems);
				}
			});
		},

		placeOverDriveHold: function(overDriveId, formatId){
			if (Globals.loggedIn){
				//Get any prompts needed for placing holds (e-mail and format depending on the interface.
				var promptInfo = VuFind.OverDrive.getOverDriveHoldPrompts(overDriveId, formatId, 'hold');
				if (!promptInfo.promptNeeded){
					VuFind.OverDrive.doOverDriveHold(overDriveId, formatId, promptInfo.overdriveEmail, promptInfo.promptForOverdriveEmail);
				}
			}else{
				VuFind.Account.ajaxLogin(null, function(){
					VuFind.OverDrive.placeOverDriveHold(overDriveId, formatId);
				});
			}
		}
	}
}(VuFind.OverDrive || {}));