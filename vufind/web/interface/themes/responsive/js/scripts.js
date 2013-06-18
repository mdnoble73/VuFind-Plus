/**
 * Custom Javascript for VuFind integration
 * User: Mark Noble
 * Date: 6/17/13
 * Time: 3:47 PM
 */

var VuFind = VuFind || {};
VuFind.Holdings = {
	statusList: [],

	addIdToStatusList: function(id, type, useUnscopedHoldingsSummary) {
		if (type == undefined){
			type = 'VuFind';
		}
		var idVal = [];
		idVal['id'] = id;
		idVal['useUnscopedHoldingsSummary'] = useUnscopedHoldingsSummary;
		idVal['type'] = type;
		this.statusList[this.statusList.length] = idVal;
	},

	loadStatusSummaries: function (){
		var now = new Date();
		var ts = Date.UTC(now.getFullYear(),now.getMonth(),now.getDay(),now.getHours(),now.getMinutes(),now.getSeconds(),now.getMilliseconds());

		var callGetEContentStatusSummaries = false;
		var eContentUrl = path + "/Search/AJAX?method=GetEContentStatusSummaries";
		for (var j=0; j< this.statusList.length; j++) {
			if (this.statusList['type'] == 'eContent'){
				eContentUrl += "&id[]=" + encodeURIComponent(statusList[j]['id']);
				if (this.statusList[j]['useUnscopedHoldingsSummary']){
					eContentUrl += "&useUnscopedHoldingsSummary=true";
				}
				callGetEContentStatusSummaries = true;
			}else if (this.statusList['type'] == 'VuFind'){
				var url = path + "/Search/AJAX?method=GetStatusSummaries";
				url += "&id[]=" + encodeURIComponent(this.statusList[j]['id']);
				if (this.statusList[j]['useUnscopedHoldingsSummary']){
					url += "&useUnscopedHoldingsSummary=true";
				}
				url += "&time="+ts;
				$.getJSON(url, function(data){
					var items = data.items;

					var elemId;
					var showPlaceHold;
					var numHoldable = 0;

					for (var i=0; i<items.length; i++) {
						try{
							elemId = items[i].shortId;

							// Place hold link
							if (items[i].showPlaceHold == null){
								showPlaceHold = 0;
							}else{
								showPlaceHold = items[i].showPlaceHold;
							}

							// Multi select place hold options
							if (showPlaceHold == '1' || showPlaceHold == true){
								numHoldable++;
								// show the place hold button
								var placeHoldButton = $('#placeHold' + elemId );
								if (placeHoldButton.length > 0){
									placeHoldButton.show();
								}
							}

							// Change outside border class.
							var holdingsSummaryId = '#holdingsSummary' + elemId;
							var holdingSum= $(holdingsSummaryId);
							if (holdingSum.length > 0){
								divClass = items[i]['class'];
								holdingSum.addClass(divClass);
								var formattedHoldingsSummary = items[i].formattedHoldingsSummary;
								holdingSum.replaceWith(formattedHoldingsSummary);
							}

							// Load call number
							var callNumberSpan= $('#callNumberValue' + elemId);
							if (callNumberSpan.length > 0){
								var callNumber = items[i].callnumber;
								if (callNumber){
									callNumberSpan.html(callNumber);
								}else{
									callNumberSpan.html("N/A");
								}
							}

							// Load location
							var locationSpan= $('#locationValue' + elemId);
							if (locationSpan.length > 0){
								var availableAt = items[i].availableAt;
								if (availableAt){
									locationSpan.html(availableAt);
								}else{
									var location = items[i].location;
									if (location){
										locationSpan.html(location);
									}else{
										locationSpan.html("N/A");
									}
								}
							}

							// Load status
							var statusSpan= $('#statusValue' + elemId);
							if (statusSpan.length > 0){
								var status = items[i].status;
								if (status){
									if (status == "Available At"){
										status = "Available";
									}
									statusSpan.html(status);
								}else{
									statusSpan.html("Unknown");
								}

								var statusClass = items[i]['class'];
								if (statusClass){
									statusSpan.addClass(statusClass);
								}
							}

							// Load Download Link
							var downloadLinkSpan= $('#downloadLinkValue' + elemId);
							if (downloadLinkSpan.length > 0){
								var isDownloadable = items[i].isDownloadable;
								if (isDownloadable == 1){
									var downloadLink = items[i].downloadLink;
									var downloadText = items[i].downloadText;
									$("#downloadLinkValue" + elemId).html("<a href='" + decodeURIComponent(downloadLink) + "'>" + downloadText + "</a>");
									$("#downloadLink" + elemId).show();
								}
							}
							$('#holdingsSummary' + elemId).addClass('loaded');
						}catch (err){
							//alert("Unexpected error " + err);
						}
					}
					// Check to see if the Request selected button should show
					if (numHoldable > 0){
						$('.requestSelectedItems').show();
					}
				}).error(function(jqXHR, textStatus, errorThrown){
							//alert("Unexpected error trying to get status " + textStatus);
				});
			}else{
				//OverDrive record
				var overDriveUrl = path + "/Search/AJAX?method=GetEContentStatusSummaries";
				overDriveUrl += "&id[]=" + encodeURIComponent(GetOverDriveStatusList[j]['id']);
				$.ajax({
					url: overDriveUrl,
					success: function(data){
						var items = $(data).find('item');
						$(items).each(function(index, item){
							var elemId = $(item).attr("id") ;
							$('#holdingsEContentSummary' + elemId).replaceWith($(item).find('formattedHoldingsSummary').text());
							if ($(item).find('showplacehold').text() == 1){
								$("#placeEcontentHold" + elemId).show();
							}else if ($(item).find('showcheckout').text() == 1){
								$("#checkout" + elemId).show();
							}else if ($(item).find('showaccessonline').text() == 1){
								$("#accessOnline" + elemId).show();
							}else if ($(item).find('showaddtowishlist').text() == 1){
								$("#addToWishList" + elemId).show();
							}
							if ($("#statusValue" + elemId).length > 0){
								var status = $(item).find('status').text();
								$("#statusValue" + elemId).text(status);
								var statusClass = $(item).find('class').text();
								if (statusClass){
									$("#statusValue" + elemId).addClass(statusClass);
								}
							}
							$('#holdingsEContentSummary' + elemId).addClass('loaded');
						});
					}
				});
			}
		}
		eContentUrl += "&time=" +ts;

		if (callGetEContentStatusSummaries) {
			$.ajax({
				url: eContentUrl,
				success: function(data){
					var items = $(data).find('item');
					$(items).each(function(index, item){
						var elemId = $(item).attr("id") ;
						var eContentElementId = '#holdingsEContentSummary' + elemId;
						$(eContentElementId).replaceWith($(item).find('formattedHoldingsSummary').text());
						if ($(item).find('showplacehold').text() == 1){
							$("#placeEcontentHold" + elemId).show();
						}else if ($(item).find('showcheckout').text() == 1){
							$("#checkout" + elemId).show();
						}else if ($(item).find('showaccessonline').text() == 1){
							if ($(item).find('accessonlineurl').length > 0){
								var url = $(item).find('accessonlineurl').text();
								var text = $(item).find('accessonlinetext').text();
								$("#accessOnline" + elemId + " a").attr("href", url).text($("<div/>").html(text).text());
							}
							$("#accessOnline" + elemId).show();

						}else if ($(item).find('showaddtowishlist').text() == 1){
							$("#addToWishList" + elemId).show();
						}

						var statusId = "#statusValue" + elemId;
						if ($(statusId).length > 0){
							var status = $(item).find('status').text();
							$(statusId).text(status);
							var statusClass = $(item).find('class').text();
							if (statusClass){
								$("#statusValue" + elemId).addClass(statusClass);
							}
						}
						$('#holdingsEContentSummary' + elemId).addClass('loaded');
					});
				}
			});
		}

		//Clear the status lists so we don't reprocess later if we need more status summaries..
		statusList = [];
	}
};

