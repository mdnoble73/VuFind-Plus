VuFind.ResultsList = (function(){
	return {
		statusList: [],
		seriesList: [],

		addIdToSeriesList: function(isbn){
			this.seriesList[this.seriesList.length] = isbn;
		},

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

		initializeDescriptions: function(){
			$(".descriptionTrigger").each(function(){
				var descElement = $(this);
				var descriptionContentClass = descElement.data("content_class");
				options = {
					html: true,
					trigger: 'hover',
					title: 'Description',
					content: VuFind.ResultsList.loadDescription(descriptionContentClass)
				};
				descElement.popover(options);
			});
		},

		lessFacets: function(name){
			document.getElementById("more" + name).style.display="block";
			document.getElementById("narrowGroupHidden_" + name).style.display="none";
		},

		loadDescription: function(descriptionContentClass){
			var contentHolder = $(descriptionContentClass);
			return contentHolder[0].innerHTML;
		},

		loadSeriesInfo: function(){
			var now = new Date();
			var ts = Date.UTC(now.getFullYear(),now.getMonth(),now.getDay(),now.getHours(),now.getMinutes(),now.getSeconds(),now.getMilliseconds());

			var url = Globals.path + "/Search/AJAX?method=GetSeriesInfo";
			for (var i=0; i < this.seriesList.length; i++) {
				url += "&isbn[]=" + encodeURIComponent(this.seriesList[i]);
			}
			url += "&time="+ts;
			$.getJSON(url,function(data){
				if (data.success){
					$.each(data.series, function(key, val){
						$(".series" + key).find(".result-value").html(val);
					});
				}
			});
		},

		loadStatusSummaries: function (){
			var now = new Date();
			var ts = Date.UTC(now.getFullYear(),now.getMonth(),now.getDay(),now.getHours(),now.getMinutes(),now.getSeconds(),now.getMilliseconds());

			var callGetEContentStatusSummaries = false;
			var eContentUrl = Globals.path + "/Search/AJAX?method=GetEContentStatusSummaries";
			for (var j=0; j< this.statusList.length; j++) {
				if (this.statusList[j]['type'] == 'eContent'){
					eContentUrl += "&id[]=" + encodeURIComponent(this.statusList[j]['id']);
					if (this.statusList[j]['useUnscopedHoldingsSummary']){
						eContentUrl += "&useUnscopedHoldingsSummary=true";
					}
					callGetEContentStatusSummaries = true;
				}else if (this.statusList[j]['type'] == 'VuFind'){
					var url = Globals.path + "/Search/AJAX?method=GetStatusSummaries";
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
								var statusClass = undefined;
								if (statusSpan.length > 0){
									var status = items[i].statusText;
									if (status){
										if (status == "Available At"){
											status = "Available";
										}
										statusSpan.html(status);
									}else{
										statusSpan.html("Unknown");
									}

									statusClass = items[i]['class'];
									if (statusClass){
										statusSpan.addClass(statusClass);
									}
								}

								// Load status
								var copiesSpan= $('#copiesValue' + elemId);
								if (copiesSpan.length > 0){
									var copies = items[i].copies;
									if (copies){
										copiesSpan.html(copies);
									}else{
										copies.html("No copies found");
									}

									statusClass = items[i]['class'];
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
					var overDriveUrl = Globals.path + "/Search/AJAX?method=GetEContentStatusSummaries";
					overDriveUrl += "&id[]=" + encodeURIComponent(this.statusList[j]['id']);
					$.ajax({
						url: overDriveUrl,
						success: function(data){
							var items = $(data).find('item');
							$(items).each(function(index, item){
								var elemId = $(item).attr("id") ;
								var holdingsSummaryId = '#holdingsEContentSummary' + elemId;
								$(holdingsSummaryId).replaceWith($(item).find('formattedHoldingsSummary').text());
								if ($(item).find('showplacehold').text() == 1){
									$("#placeEcontentHold" + elemId).show();
								}else if ($(item).find('showcheckout').text() == 1){
									$("#checkout" + elemId).show();
								}else if ($(item).find('showaccessonline').text() == 1){
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
										$(statusId).addClass(statusClass);
									}
								}
								$(holdingsSummaryId).addClass('loaded');
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
			this.statusList = [];
		},

		moreFacets: function(name){
			document.getElementById("more" + name).style.display="none";
			document.getElementById("narrowGroupHidden_" + name).style.display="block";
		},

		moreFacetPopup: function(title, name){
			VuFind.showMessage(title, $("#moreFacetPopup_" + name).html());
		},

		toggleFacetVisibility: function(){
			$facetsSection = $("#collapse-side-facets");
		},

		toggleRelatedManifestations: function(manifestationId){
			$('#relatedRecordPopup_' + manifestationId).toggleClass('hidden');
			var manifestationToggle = $('#manifestation-toggle-' + manifestationId);
			manifestationToggle.toggleClass('collapsed');
			if (manifestationToggle.hasClass('collapsed')){
				manifestationToggle.html('+');
			}else{
				manifestationToggle.html('-');
			}
			return false;

		}

	};
}(VuFind.ResultsList || {}));
