VuFind.Record = (function(){
	return {
		GetGoDeeperData: function (id, isbn, upc, dataType){
			if (dataType == 'excerpt'){
				var placeholder = $("#excerptPlaceholder");
			}else if (dataType == 'avSummary'){
				var placeholder = $("#avSummaryPlaceholder");
			}else if (dataType == 'tableOfContents'){
				var placeholder = $("#tableOfContentsPlaceholder");
			}
			if (placeholder.hasClass("loaded")) return;
			placeholder.show();
			var url = Globals.path + "/Record/" + encodeURIComponent(id) + "/AJAX";
			var params = "method=GetGoDeeperData&dataType=" + encodeURIComponent(dataType) + "&isbn=" + encodeURIComponent(isbn) + "&upc=" + encodeURIComponent(upc);
			var fullUrl = url + "?" + params;
			$.ajax( {
				url : fullUrl,
				success : function(data) {
					placeholder.html(data);
					placeholder.addClass('loaded');
				},
				failure : function(jqXHR, textStatus, errorThrown) {
					alert('Error: Could Not Load Syndetics information.');
				}
			});
		},

		loadEnrichmentInfo: function (id, isbn, upc, source) {
			if (source = 'VuFind'){
				var url = Globals.path + "/Record/" + encodeURIComponent(id) + "/AJAX";
			}else{
				var url = Globals.path + "/EcontentRecord/" + encodeURIComponent(id) + "/AJAX";
			}
			var params = "method=GetEnrichmentInfoJSON&isbn=" + encodeURIComponent(isbn) + "&upc=" + encodeURIComponent(upc);
			var fullUrl = url + "?" + params;
			$.ajax( {
				url : fullUrl,
				dataType: 'json',
				success : function(data) {
					try{
						var seriesData = data.seriesInfo;
						if (seriesData && seriesData.length > 0) {

							seriesScroller = new TitleScroller('titleScrollerSeries', 'Series', 'seriesList');

							seriesData = $.parseJSON(seriesData);
							if (seriesData.titles.length > 0){
								$('#list-series-tab').show();
								$('#relatedTitleInfo').show();
								seriesScroller.loadTitlesFromJsonData(seriesData);
							}
						}
						var showGoDeeperData = data.showGoDeeper;
						if (showGoDeeperData) {
							//$('#goDeeperLink').show();
							var goDeeperOptions = data.goDeeperOptions;
							//add a tab before citation for each item
							for (option in goDeeperOptions){
								if (option == 'excerpt'){
									$("#excerpttab_label").show();
								}else if (option == 'avSummary'){
									$("#tableofcontentstab_label").show();
									$("avSummaryPlaceholder").show();
								}else if (option == 'avSummary' || option == 'tableOfContents'){
									$("#tableofcontentstab_label").show();
									$("tableOfContentsPlaceholder").show();
								}
							}
						}
						var relatedContentData = data.relatedContent;
						if (relatedContentData && relatedContentData.length > 0) {
							$("#relatedContentPlaceholder").html(relatedContentData);
						}
					} catch (e) {
						alert("error loading enrichment: " + e);
					}
				},
				failure : function(jqXHR, textStatus, errorThrown) {
					alert('Error: Could Not Load Enrichment information.');
				}
			});
		},

		loadHoldingsInfo: function (id, shortId, source) {
			var url;
			if (source == 'VuFind'){
				url = Globals.path + "/Record/" + encodeURIComponent(id) + "/AJAX";
			}else if (source == 'OverDrive'){
				url = Globals.path + "/OverDrive/" + encodeURIComponent(id) + "/AJAX";
			}else{
				url = Globals.path + "/EcontentRecord/" + encodeURIComponent(id) + "/AJAX";
			}
			var params = "method=GetHoldingsInfo";
			var fullUrl = url + "?" + params;
			$.ajax( {
				url : fullUrl,
				success : function(data) {
					if (source == 'VuFind'){
						var holdingsData = $(data).find("Holdings").text();
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
						}
						var summaryDetails = $(data).find("SummaryDetails");
						var callNumber = summaryDetails.find("callnumber").text();
						$("#callNumberValue").html(callNumber);
						var location = summaryDetails.find("availableAt").text();
						if (location.length > 0){
							$("#locationValue").html(location);
						}else{
							location = summaryDetails.find("location").text();
							$("#locationValue").html(location);
						}
						var status = summaryDetails.find("status").text();
						if (status == "Available At"){
							status = "Available";
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
						if (copiesData) {
							if (copiesData.length > 0) {
								$("#copiesPlaceholder").html(copiesData).trigger("create");
							}else{
								$("#copiestabLink").hide();
								$("#copiesPlaceholder").html("No Copies Information found, please try again later.");
								$("#formatstabLink a").text("Copies");
							}
						}else{
							$("#copiestabLink").hide();
							$("#copiesPlaceholder").html("No Copies Information found, please try again later.");
							$("#formatstabLink a").text("Copies");
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

		loadReviewInfo: function (id, isbn, source) {
			var url;
			if (source == 'VuFind'){
				url = Globals.path + "/Record/" + encodeURIComponent(id) + "/AJAX";
			}else{
				url = Globals.path + "/EcontentRecord/" + encodeURIComponent(id) + "/AJAX";
			}
			var params = "method=GetReviewInfo&isbn=" + encodeURIComponent(isbn);
			var fullUrl = url + "?" + params;
			$.ajax( {
				url : fullUrl,
				success : function(data) {
					var reviewsData = $(data).find("Reviews").text();
					if (reviewsData && reviewsData.length > 0) {
						$("#reviewPlaceholder").html(reviewsData);
					}
				}
			});
		},

		saveReview: function(id, shortId){
			if (Globals.loggedIn){
				if (shortId == null || shortId == ''){
					shortId = id;
				}
				var comment = $('#comment' + shortId).val();

				var url = Globals.path + "/Record/" + encodeURIComponent(id) + "/AJAX";
				var params = "method=SaveComment&comment=" + encodeURIComponent(comment);
				$.ajax({
					url: url + '?' + params,
					dataType: 'json',
					success : function(data) {
						var result = false;
						if (data) {
							result = data.result;
						}
						if (result && result.length > 0) {
							if (result == "Done") {
								$('#comment' + shortId).val('');
								if ($('#commentList').length > 0) {
									LoadComments(id);
								} else {
									alert('Thank you for your review.');
									VuFind.closeLightbox();
								}
							}else{
								alert("Error: Your review was not saved successfully");
							}
						} else {
							alert("Error: Your review was not saved successfully");
						}
					},
					error : function() {
						alert("Unable to save your comment.");
					}
				});
			}
			return false;
		},

		/**
		 * Used to send a text message related to a specific record.
		 * Includes title, author, call number, etc.
		 * @param id
		 */
		sendEmail: function(id, source){
			var emailForm = $("#emailForm");
			var to = emailForm.find("input[name=to]").val();
			var from = emailForm.find("input[name=from]").val();
			var message = emailForm.find("input[name=message]").val();
			if (source == 'VuFind'){
				var url = Globals.path + "/Record/" + encodeURIComponent(id) + "/AJAX";
			}else{
				var url = Globals.path + "/EcontentRecord/" + encodeURIComponent(id) + "/AJAX";
			}
			var params = "method=SendEmail&" + "to=" + encodeURIComponent(to) + "&" + "from=" + encodeURIComponent(from) + "&" + "message=" + encodeURIComponent(message);

			$.ajax({
				url: url+'?'+params,

				success: function(data) {
					var value = $(data).find('result');
					if (value) {
						if (value.text() == "Done") {
							$(".modal-body").html("<div class='alert alert-success'>Your e-mail has been sent</div>");
							setTimeout("VuFind.closeLightbox();", 3000);
						} else {
							$(".modal-body").html("<div class='alert alert-error'>Could not send e-mail</div>");
						}
					} else {
						$(".modal-body").html("<div class='alert alert-error'>Failed to send e-mail</div>");
					}
				},
				error: function() {
					$(".modal-body").html("<div class='alert alert-error'>Unexpected error sending e-mail</div>");
				}
			});
		},

		/**
		 * Used to send a text message related to a specific record.
		 * Includes title, author, call number, etc.
		 * @param id
		 */
		sendSMS: function(id, source){
			var smsForm = $("#smsForm");
			var to = smsForm.find("input[name=to]").val();
			var provider = smsForm.find("input[name=provider]").val();
			if (source == 'VuFind'){
				var url = Globals.path + "/Record/" + encodeURIComponent(id) + "/AJAX";
			}else{
				var url = Globals.path + "/EcontentRecord/" + encodeURIComponent(id) + "/AJAX";
			}
			var params = "method=SendSMS&" + "to=" + encodeURIComponent(to) + "&" + "provider=" + encodeURIComponent(provider);

			$.ajax({
				url: url+'?'+params,

				success: function(data) {
					var value = $(data).find('result');
					if (value) {
						if (value.text() == "Done") {
							$(".modal-body").html("<div class='alert alert-success'>Your Text Message has been sent</div>");
							setTimeout("VuFind.closeLightbox();", 3000);
						} else {
							$(".modal-body").html("<div class='alert alert-error'>Could not send text message</div>");
						}
					} else {
						$(".modal-body").html("<div class='alert alert-error'>Failed to send text message</div>");
					}
				},
				error: function() {
					$(".modal-body").html("<div class='alert alert-error'>Unexpected error sending text message</div>");
				}
			});
		}
	};
}(VuFind.Record || {}));