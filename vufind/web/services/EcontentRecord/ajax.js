function saveEContentRecord(id, formElem, strings) {
	successCallback = function() {
		// Highlight the save link to indicate that the content is saved:
		$('#saveLink').addClass('savedFavorite');
	};
	performSaveRecord(id, formElem, strings, 'eContent', successCallback);
}

function SendEContentEmail(id, to, from, message, strings) {
	var url = path + "/EcontentRecord/" + encodeURIComponent(id) + "/AJAX";
	var params = "method=SendEmail&" + "from=" + encodeURIComponent(from) + "&" + "to=" + encodeURIComponent(to) + "&" + "message=" + encodeURIComponent(message);
	sendAJAXEmail(url, params, strings);
}

function SendEContentSMS(id, to, provider, strings) {
	var url = path + "/EcontentRecord/" + encodeURIComponent(id) + "/AJAX";
	var params = "method=SendSMS&" + "to=" + encodeURIComponent(to) + "&" + "provider=" + encodeURIComponent(provider);
	sendAJAXSMS(url, params, strings);
}

function GetEContentHoldingsInfo(id, type, callback) {
	var url = path + "/EcontentRecord/" + encodeURIComponent(id) + "/AJAX";
	var params = "method=GetHoldingsInfo";
	var fullUrl = url + "?" + params;
	$.ajax( {
		url : fullUrl,
		success : function(data) {
			var holdingsData = $(data).find("Holdings").text();
			if (holdingsData) {
				if (holdingsData.length > 0) {
					$("#holdingsPlaceholder").html(holdingsData);
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
					$(".accessOnlineLink").show();
				}
			}
			var showAddToWishList = $(data).find("ShowAddToWishlist").text();
			if (showAddToWishList) {
				if (showAddToWishList.length > 0 && showAddToWishList == 1) {
					$(".addToWishListLink").show();
				}
			}
			
			if (typeof callback === 'function')
			{
				callback();
			}
			
		}
	});
}

function SaveEContentComment(id, strings) {
	if (loggedIn){
		$('#userecontentreview' + id).slideUp();
		var comment = $('#econtentcomment' + id).val();
	
		var url = path + "/EcontentRecord/" + encodeURIComponent(id) + "/AJAX";
		var params = "method=SaveComment&comment=" + encodeURIComponent(comment);
		var fullUrl = url + "?" + params;
		$.ajax({
			url: fullUrl,
			dataType: 'json',
			success : function(data) {
				if (data.result == 'Unauthorized'){
					getLightbox('AJAX', 'Login', id, null, strings.save_title, '', '', '', '10%', '80%', '50%', '300');
				}else if (data.result == 'true') {
					$('#userreview' + id).slideUp();
					$('#econtentcomment' + id).val('');
					if ($('#commentList').length > 0) {
						LoadEContentComments(id, strings);
					} else {
						alert('Thank you for your review.');
					}
				} else {
					alert(strings.save_error);
				}
			},
			error : function() {
				if (strings.save_error.length == 0){
					alert("Unable to save your comment.");
				}else{
					alert(strings.save_error);
				}
			}
		});
	}else{
		ajaxLogin(function(){
			SaveEContentComment(id, strings);
		});
	}
}

function deleteEContentComment(id, commentId, strings) {
	var url = path + "/EcontentRecord/" + encodeURIComponent(id) + "/AJAX?method=DeleteComment&commentId=" + encodeURIComponent(commentId);
	$.ajax( {
		url : url,
		success : function(data) {
		LoadEContentComments(id, strings);
		}
	});
}

function LoadEContentComments(id, strings) {
	var output = '';

	var url = path + "/EcontentRecord/" + encodeURIComponent(id) + "/AJAX";
	var params = "method=GetComments";
	var output = '';

	$.getJSON(url + "?" + params, function(data) {
		var result = data.userComments;
		if (result && result.length > 0) {
			$("#commentList").html(result);
		} else {
			$("#commentList").html(strings.load_error);
		}
		
		var staffComments = data.staffComments;
		if (staffComments && staffComments.length > 0) {
			$("#staffCommentList").html(staffComments);
		} else {
			$("#staffCommentList").html(strings.load_error);
		}
	});
}

function deleteItem(id, itemId){
	if (confirm("Are you sure you want to delete this item?")){
		var url = path + "/EcontentRecord/" + encodeURIComponent(id) + "/AJAX";
		var params = "method=DeleteItem&itemId=" + encodeURIComponent(itemId);
		$.getJSON(url+ "?" + params, function(data) {
			var result = data.result;
			if (result == true){
				alert("The item was deleted successfully.");
				$("#itemRow" + itemId).hide();
			}else{
				var message = data.message;
				alert("The item could not be deleted. " + message);
			}
		});
	}
	return false;
}
function addItem(id){
	var url = path + "/EcontentRecord/" + encodeURIComponent(id) + "/AJAX";
	var params = "method=AddItem";
	ajaxLightbox(url+ "?" + params);
	return false;
}
function editItem(id, itemId){
	var url = path + "/EcontentRecord/" + encodeURIComponent(id) + "/AJAX";
	var params = "method=EditItem&itemId=" + encodeURIComponent(itemId);
	ajaxLightbox(url+ "?" + params);
	return false;
}
function showEcontentPurchaseOptions(id){
	var url = path + "/EContentRecord/" + id + "/AJAX?method=getPurchaseOptions";
	ajaxLightbox(url)
}