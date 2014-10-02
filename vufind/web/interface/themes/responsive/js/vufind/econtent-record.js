/**
 * Created by mark on 2/11/14.
 */
VuFind.EContent = (function(){
	return {
		submitHelpForm: function(){
			var url = Globals.path + '/Help/eContentSupport';
			$.ajax({
				type: "POST",
				url: url,
				data: $("#eContentSupport").serialize(), // serializes the form's elements.
				success: function(data){
					var jsonData = JSON.parse(data);
					VuFind.showMessage(jsonData.title, jsonData.message);
				},
				failure: function(data){
					alert("Could not submit the form");
				}
			});

			return false;
		}
	}
}(VuFind.EContent));

VuFind.ExternalEContentRecord = (function(){
	return {
		loadHoldingsInfo: function(id, type, callback){
			var url = Globals.path + "/ExternalEContent/" + encodeURIComponent(id) + "/AJAX";
			var params = "method=GetHoldingsInfo";
			var fullUrl = url + "?" + params;
			$.ajax( {
				url : fullUrl,
				success : function(data) {
					var holdingsData = $(data).find("Formats").text();
					if (holdingsData) {
						var formatsPlaceholder = $("#formatsPlaceholder");
						if (holdingsData.length > 0) {
							formatsPlaceholder.html(holdingsData);
							formatsPlaceholder.trigger("create");
						}else{
							formatsPlaceholder.html("No Formats Information found, please try again later.");
						}
					}
					var copiesData = $(data).find("Copies").text();
					var copiesPlaceholder = $("#copiesPlaceholder");
					if (copiesData) {
						if (copiesData.length > 0) {
							copiesPlaceholder.html(copiesData);
							copiesPlaceholder.trigger("create");
						}else{
							$("#copiestabLink").hide();
							$("#copiesPlaceholder").html("No Copies Information found, please try again later.");
							$("#formatstabLink a").text("Copies");
						}
					}else{
						$("#copiestabLink").hide();
						copiesPlaceholder.html("No Copies Information found, please try again later.");
						$("#formatstabLink a").text("Copies");
					}

					var holdingsSummary = $(data).find("HoldingsSummary").text();
					if (holdingsSummary) {
						if (holdingsSummary.length > 0) {
							$("#holdingsSummaryPlaceholder").html(holdingsSummary);
							$("#holdingsSummaryPlaceholder").trigger("create");
						}
					}
					var showPlaceHold = $(data).find("ShowPlaceHold").text();
					if (showPlaceHold) {
						if (showPlaceHold.length > 0 && showPlaceHold == 1) {
							$(".requestThisLink").show();
						}
					}
					var showCheckout = $(data).find("ShowCheckout").text();
					if (showCheckout) {
						if (showCheckout.length > 0 && showCheckout == 1) {
							$(".checkoutLink").show();
						}
					}
					var showAccessOnline = $(data).find("ShowAccessOnline").text();
					if (showAccessOnline) {
						if (showAccessOnline.length > 0 && showAccessOnline == 1) {
							if ($(data).find('AccessOnlineUrl').length > 0){
								var url = $(data).find('AccessOnlineUrl').text();
								var text = $(data).find('AccessOnlineText').text();
								$("#accessOnline" + id + " a").attr("href", url);
								$("#accessOnline" + id + " a").text($("<div/>").html(text).text());
							}
							$(".accessOnlineLink").show();
						}
					}
					var showAddToWishList = $(data).find("ShowAddToWishlist").text();
					if (showAddToWishList) {
						if (showAddToWishList.length > 0 && showAddToWishList == 1) {
							$(".addToWishListLink").show();
						}
					}
					var status = $(data).find("status").text();
					$("#statusValue").html(status);
					$("#statusValue").addClass($(data).find("class").text());

					if (typeof callback === 'function') {
						callback();
					}

				}
			});
		}
	}
}(VuFind.ExternalEContentRecord));

VuFind.LocalEContent = (function(){
	return {
		cancelHold: function(recordId, itemId){
			if (Globals.loggedIn){
				var returnUrl = Globals.path + '/RestrictedEContent/' + recordId + '/AJAX?method=cancelHold&itemId=' + itemId;
				$.getJSON(returnUrl, function(data){
					if (data.result) {
						VuFind.showMessage("Success", data.message, true, true);
					} else {
						VuFind.showMessage("Error", data.message);
					}
				});
			}else{
				VuFind.Account.ajaxLogin(null, function(){
					VuFind.LocalEContent.cancelHold(recordId, itemId);
				});
			}
		},

		checkoutPublicEContent: function(recordId, itemId){
			if (Globals.loggedIn){
				var checkoutUrl = Globals.path + '/PublicEContent/' + recordId + '/AJAX?method=checkout&itemId=' + itemId;
				$.getJSON(checkoutUrl, function(data){
					if (data.result) {
						VuFind.showMessage("Success", data.message, true, true);
					} else {
						VuFind.showMessage("Error", data.message);
					}
				});
			}else{
				VuFind.Account.ajaxLogin(null, function(){
					VuFind.LocalEContent.checkoutPublicEContent(recordId, itemId);
				}, false);
			}
		},

		checkoutRestrictedEContent: function(recordId, itemId){
			if (Globals.loggedIn){
				var checkoutUrl = Globals.path + '/RestrictedEContent/' + recordId + '/AJAX?method=checkout&itemId=' + itemId;
				$.getJSON(checkoutUrl, function(data){
					if (data.result) {
						VuFind.showMessage("Success", data.message, true, true);
					} else {
						VuFind.showMessage("Error", data.message);
					}
				});
			}else{
				VuFind.Account.ajaxLogin(null, function(){
					VuFind.LocalEContent.checkoutRestrictedEContent(recordId, itemId);
				});
			}
		},

		placeHoldOnRestrictedEContent: function(recordId, itemId){
			if (Globals.loggedIn){
				var returnUrl = Globals.path + '/RestrictedEContent/' + recordId + '/AJAX?method=placeHold&itemId=' + itemId;
				$.getJSON(returnUrl, function(data){
					if (data.result) {
						VuFind.showMessage("Success", data.message, true, true);
					} else {
						VuFind.showMessage("Error", data.message);
					}
				});
			}else{
				VuFind.Account.ajaxLogin(null, function(){
					VuFind.LocalEContent.placeHoldOnRestrictedEContent(recordId, itemId);
				});
			}
		},

		returnPublicEContent: function(recordId, itemId){
			if (Globals.loggedIn){
				var returnUrl = Globals.path + '/PublicEContent/' + recordId + '/AJAX?method=returnTitle&itemId=' + itemId;
				$.getJSON(returnUrl, function(data){
					if (data.result) {
						VuFind.showMessage("Success", data.message, true, true);
					} else {
						VuFind.showMessage("Error", data.message);
					}
				});
			}else{
				VuFind.Account.ajaxLogin(null, function(){
					VuFind.LocalEContent.returnPublicEContent(recordId, itemId);
				});
			}
		},

		returnRestrictedEContent: function(recordId, itemId){
			if (Globals.loggedIn){
				var returnUrl = Globals.path + '/RestrictedEContent/' + recordId + '/AJAX?method=returnTitle&itemId=' + itemId;
				$.getJSON(returnUrl, function(data){
					if (data.result) {
						VuFind.showMessage("Success", data.message, true, true);
					} else {
						VuFind.showMessage("Error", data.message);
					}
				});
			}else{
				VuFind.Account.ajaxLogin(null, function(){
					VuFind.LocalEContent.returnRestrictedEContent(recordId, itemId);
				});
			}
		}
	}
}(VuFind.LocalEContent));