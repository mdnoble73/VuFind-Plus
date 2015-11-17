VuFind.Record = (function(){
	return {
		loadHoldingsInfo: function (module, id, shortId, source) {
			var url;
			if (source == 'VuFind'){
				url = Globals.path + "/" + module + "/" + encodeURIComponent(id) + "/AJAX";
			}else{
				url = Globals.path + "/EcontentRecord/" + encodeURIComponent(id) + "/AJAX";
			}
			var params = "method=GetHoldingsInfo";
			var fullUrl = url + "?" + params;
			$.ajax( {
				url : fullUrl,
				success : function(data) {
					if (source == 'VuFind'){
						/*var holdingsData = $(data).find("Holdings").text();
						if (holdingsData) {
							if (holdingsData.length > 0) {
								if (holdingsData.match(/No Copies Found/i)){
									try{
										if ($("#prospectortab_label").is(":visible")){
											$("#moredetails-tabs").tabs("option", "active", 1);
										}else{
											$("#moredetails-tabs").tabs("option", "active", 2);
										}
										$("#holdingstab_label").hide();
									}catch(e){

									}
								}else{
									$("#holdingsPlaceholder").html(holdingsData);
								}
							}
						}*/
						var summaryDetails = $(data).find("SummaryDetails");
						var callNumber = summaryDetails.find("callnumber").text();
						if (callNumber.length > 0){
							$("#callNumberValue").html(callNumber);
						}else{
							$("#callNumberRow").hide();
						}
						var location = summaryDetails.find("availableAt").text();
						if (location.length > 0){
							$("#locationValue").html(location);
						}else{
							location = summaryDetails.find("location").text();
							if (location.length > 0){
								$("#locationValue").html(location);
							}else{
								$("#locationRow").hide();
							}
						}
						var status = summaryDetails.find("status").text();
						if (status == "Available At"){
							status = "Available";
						}else if(status == "Marmot"){
							status = "Available from another library";
						}
						$("#statusValue").html(status).addClass(summaryDetails.find("class").text());
					}else{
						var formatsData = $(data).find("Formats").text();
						if (formatsData) {
							if (formatsData.length > 0) {
								$("#formatsPlaceholder").html(formatsData).trigger("create");
							}else{
								$("#formatsPlaceholder").html("No Formats Information found, please try again later.");
							}
						}
						var copiesData = $(data).find("Copies").text();
						var formatsTabLink = $("#formatstabLink").find("a");
						if (copiesData) {
							if (copiesData.length > 0) {
								$("#copiesPlaceholder").html(copiesData).trigger("create");
							}else{
								$("#copiestabLink").hide();
								$("#copiesPlaceholder").html("No Copies Information found, please try again later.");
								formatsTabLink.text("Copies");
							}
						}else{
							$("#copiestabLink").hide();
							$("#copiesPlaceholder").html("No Copies Information found, please try again later.");
							formatsTabLink.text("Copies");
						}
					}
					var holdingsSummary = $(data).find("HoldingsSummary").text();
					if (holdingsSummary) {
						if (holdingsSummary.length > 0) {
							$("#holdingsSummaryPlaceholder").html(holdingsSummary);
						}
					}
					var showPlaceHold = $(data).find("ShowPlaceHold").text();
					if (showPlaceHold) {
						if (showPlaceHold.length > 0 && showPlaceHold == 1) {
							//$(".requestThisLink").show();
							$("#placeHold" + shortId).show();
						}
					}
					var showCheckout = $(data).find("ShowCheckout").text();
					if (showCheckout) {
						if (showCheckout.length > 0 && showCheckout == 1) {
							$("#checkout" + shortId).show();
						}
					}
					var showBookMaterial = $(data).find("showBookMaterial").text();
					if (showBookMaterial) {
						if (showBookMaterial.length > 0 && showBookMaterial == 1) {
							//$("#bookMaterialButton" + shortId).show(); // needed?, don't know of a
							$("#bookMaterialButton").show(); // full record view
						}
					}
					var showAccessOnline = $(data).find("ShowAccessOnline").text();
					if (showAccessOnline) {
						if (showAccessOnline.length > 0 && showAccessOnline == 1) {
							if ($(data).find('AccessOnlineUrl').length > 0){
								var url = $(data).find('AccessOnlineUrl').text();
								var text = $(data).find('AccessOnlineText').text();
								var accessOnlineLink = $("#accessOnline" + id);
								accessOnlineLink.attr("href", url);
								accessOnlineLink.text($("<div/>").html(text).text());
							}
							$(".accessOnlineLink").show();
						}
					}
					var showAddToWishList = $(data).find("ShowAddToWishlist").text();
					if (showAddToWishList) {
						if (showAddToWishList.length > 0 && showAddToWishList == 1) {
							$("#addToWishList" + id).show();
						}
					}
				}
			});
		},

		showPlaceHold: function(module, id){
			if (Globals.loggedIn){
				var source;
				if (id.indexOf(":") > 0){
					var idParts = id.split(":", 2);
					source = idParts[0];
					id = idParts[1];
				}else{
					source = 'ils';
				}
				var url = Globals.path + "/" + module + "/" + id + "/AJAX?method=getPlaceHoldForm&recordSource=" + source;
				//VuFind.showMessage('Loading...', 'Loading, please wait.');
				$.getJSON(url, function(data){
					VuFind.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(VuFind.ajaxFail);
			}else{
				VuFind.Account.ajaxLogin(null, function(){
					VuFind.Record.showPlaceHold(module, id);
				}, false);
			}
			return false;
		},

		showBookMaterial: function(module, id){
			if (Globals.loggedIn){
				VuFind.loadingMessage();
				//var source; // source not used for booking at this time
				if (id.indexOf(":") > 0){
					var idParts = id.split(":", 2);
					//source = idParts[0];
					id = idParts[1];
				//}else{
				//	source = 'ils';
				}
				$.getJSON(Globals.path + "/" + module + "/" + id + "/AJAX?method=getBookMaterialForm", function(data){
					VuFind.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(VuFind.ajaxFail)
			}else{
				VuFind.Account.ajaxLogin(null, function(){
					VuFind.Record.showBookMaterial(id);
				}, false)
			}
			return false;
		},

		submitBookMaterialForm: function(){
			var params = $('#bookMaterialForm').serialize();
			var module = $('#module').val();
			VuFind.showMessage('Scheduling', 'Processing, please wait.');
			$.getJSON(Globals.path + "/" + module +"/AJAX", params+'&method=bookMaterial', function(data){
				if (data.modalBody) VuFind.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
					// For errors that can be fixed by the user, the form will be re-displayed
				if (data.success) VuFind.showMessage('Success', data.message/*, true*/);
				else if (data.message) VuFind.showMessage('Error', data.message);
			}).fail(VuFind.ajaxFail);
		},

		submitHoldForm: function(){
			var id = $('#id').val()
					,autoLogOut = $('#autologout').prop('checked')
					,selectedItem = $('#selectedItem')
					,module = $('#module').val()
					,params = {
						'method': 'placeHold'
						,campus: $('#campus').val()
						,selectedUser: $('#user').val()
						,cancelHoldDate: $('#canceldate').text()
						,recordSource: $('#recordSource').val()
						,account: $('#account').val()
					};
			if (autoLogOut){
				params['autologout'] = true;
			}
			if (selectedItem.length > 0){
				params['selectedItem'] = selectedItem.val();
			}
			$.getJSON(Globals.path + "/" + module +  "/" + id + "/AJAX", params, function(data){
				if (data.success){
					if (data.needsItemLevelHold){
						$('.modal-body').html(data.message);
					}else{
						VuFind.showMessage('Hold Placed Successfully', data.message, false, autoLogOut);
					}
				}else{
					VuFind.showMessage('Hold Failed', data.message, false, autoLogOut);
				}
			}).fail(VuFind.ajaxFail);
		},

		reloadCover: function(module, id){
			var url = Globals.path + '/' +module + '/' + id + '/AJAX?method=reloadCover';
			$.getJSON(url, function (data){
						VuFind.showMessage("Success", data.message, true, true);
						setTimeout("VuFind.closeLightbox();", 3000);
					}
			).fail(VuFind.ajaxFail);
			return false;
		},

		moreContributors: function(){
			document.getElementById('showAdditionalContributorsLink').style.display="none";
			document.getElementById('additionalContributors').style.display="block";
		},

		lessContributors: function(){
			document.getElementById('showAdditionalContributorsLink').style.display="block";
			document.getElementById('additionalContributors').style.display="none";
		}

	};
}(VuFind.Record || {}));