/**
 * Custom Javascript for VuFind integration
 * User: Mark Noble
 * Date: 6/17/13
 * Time: 3:47 PM
 */

var Globals = Globals || {};
Globals.path = '/';
Globals.url = '/';
Globals.loggedIn = false;
Globals.automaticTimeoutLength = 0;
Globals.automaticTimeoutLengthLoggedOut = 0;

var VuFind = VuFind || {};
VuFind.initializeModalDialogs = function() {
	$(".modalDialogTrigger").each(function(){
		$(this).click(function(){
			var trigger = $(this);
			var dialogTitle = trigger.attr("title") ? trigger.attr("title") : trigger.data("title");
			var dialogDestination = trigger.attr("href");
			$("#modal-title").text(dialogTitle);
			$(".modal-body").load(dialogDestination);
			$("#modalDialog").modal("show");
			return false;
		});
	});
};

VuFind.pwdToText = function(fieldId){
	var elem = document.getElementById(fieldId);
	var input = document.createElement('input');
	input.id = elem.id;
	input.name = elem.name;
	input.value = elem.value;
	input.size = elem.size;
	input.onfocus = elem.onfocus;
	input.onblur = elem.onblur;
	input.className = elem.className;
	if (elem.type == 'text' ){
		input.type = 'password';
	} else {
		input.type = 'text';
	}

	elem.parentNode.replaceChild(input, elem);
	return input;
};

VuFind.toggleHiddenElementWithButton = function(button){
	var hiddenElementName = $(button).data('hidden_element');
	var hiddenElement = $(hiddenElementName);
	hiddenElement.val($(button).hasClass('active') ? '1' : '0');
	return false;
};

VuFind.ajaxLightbox = function(url){
	var modalDialog = $("#modalDialog");
	if (modalDialog.is(":visible")){
		modalDialog.modal('hide');
	}
	$(".modal-body").html("Loading");

	modalDialog.load(url, function(){
		modalDialog.modal('show');
	});

	return false;
};

VuFind.Account = {
	ajaxCallback: null,
	closeModalOnAjaxSuccess: false,

	ajaxLogin: function(trigger, ajaxCallback, closeModalOnAjaxSuccess){
		if (Globals.loggedIn){
			if (ajaxCallback != undefined && typeof(ajaxCallback) === "function"){
				ajaxCallback();
			}else if (VuFind.Account.ajaxCallback != null && typeof(VuFind.Account.ajaxCallback) === "function"){
				VuFind.Account.ajaxCallback();
				VuFind.Account.ajaxCallback = null;
			}
		}else{
			VuFind.Account.ajaxCallback = ajaxCallback;
			VuFind.Account.closeModalOnAjaxSuccess = closeModalOnAjaxSuccess;
			var dialogTitle = trigger.attr("title") ? trigger.attr("title") : trigger.data("title");
			var dialogDestination = Globals.path + '/MyResearch/AJAX?method=LoginForm';
			var modalDialog = $("#modalDialog");
			modalDialog.load(dialogDestination);
			$("#modal-title").text(dialogTitle);
			modalDialog.modal("show");
		}
		return false;
	},

	followLinkIfLoggedIn: function(trigger, linkDestination){
		if (this == undefined){
			alert("You must provide the trigger to follow a link after logging in.");
		}
		$trigger = $(trigger);
		if (linkDestination == undefined){
			linkDestination = $trigger.attr("href");
		}
		VuFind.Account.ajaxLogin($trigger, function(){
			document.location = linkDestination;
		}, true);
		return false;
	},

	processAjaxLogin: function(ajaxCallback){
		var username = $("#username").val();
		var password = $("#password").val();
		var rememberMe = $("#rememberMe").val();
		var loginErrorElem = $('#loginError');
		if (!username || !password){
			loginErrorElem.text("Please enter both your name and library card number");
			loginErrorElem.show();
			return false;
		}
		loginErrorElem.hide();
		var url = Globals.path + "/AJAX/JSON?method=loginUser";
		$.ajax({url: url,
			data: {username: username, password: password, rememberMe: rememberMe},
			success: function(response){
				if (response.result.success == true){
					// Hide "log in" options and show "log out" options:
					$('.loginOptions').hide();
					$('.logoutOptions').show();
					$('#loginOptions').hide();
					$('#logoutOptions').show();
					$('#myAccountNameLink').html(response.result.name);
					if (VuFind.Account.closeModalOnAjaxSuccess){
						$("#modalDialog").modal('hide');
					}

					Globals.loggedIn = true;
					if (ajaxCallback != undefined && typeof(ajaxCallback) === "function"){
						ajaxCallback();
					}else if (VuFind.Account.ajaxCallback != undefined && typeof(VuFind.Account.ajaxCallback) === "function"){
						VuFind.Account.ajaxCallback();
						VuFind.Account.ajaxCallback = null;
					}
				}else{
					loginErrorElem.text(response.result.message);
					loginErrorElem.show();
				}
			},
			error: function(){
				loginErrorElem.text("There was an error processing your login, please try again.");
				loginErrorElem.show();
			},
			dataType: 'json',
			type: 'post'
		});

		return false;
	}
};

VuFind.Responsive = {
	adjustLayout: function(){
		// get resolution
		var resolution = document.documentElement.clientWidth;

		// Handle Mobile layout
		if (resolution <= 980) {
			//Convert tabs to dropdown lists for phone
			if( $('.select-menu').length === 0 ) {

				// create select menu
				var select = $('<select></select>');

				// add classes to select menu
				select.addClass('select-menu input-block-level');

				// each link to option tag
				$('.nav-tabs li a').each(function(){
					// create element option
					var option = $('<option></option>');

					// add href value to jump
					$this = $(this);
					option.val($this.attr('href'));

					// add text
					option.text($this.text());

					// append to select menu
					select.append(option);
				});

				// add change event to select
				select.change(function(){
					//Show the correct tab
					$('.nav-tabs').parent().children('.tab-content').children('.tab-pane').removeClass('active');
					var selectedTabId = $('.select-menu').val();
					$(selectedTabId).addClass('active');
				});

				// add select element to dom, hide the .nav-tabs
				$('.nav-tabs').before(select).hide();
			}
		}

		// max width 979px
		if (resolution > 979) {
			$('.select-menu').remove();
			$('.nav-tabs').show();
		}
	}
};

VuFind.ResultsList = {
	statusList: [],
	seriesList: [],

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

	addIdToSeriesList: function(isbn){
		this.seriesList[this.seriesList.length] = isbn;
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

	loadDescription: function(descriptionContentClass){
		var contentHolder = $(descriptionContentClass);
		return contentHolder[0].innerHTML;
	}
};

VuFind.Ratings = {
	initializeRaters: function(){
		$(".rater").each(function(){
			var ratingElement = $(this);
			//Add additional elements to the div

			var module = ratingElement.data("module");
			var userRating = ratingElement.data("user_rating");
			//Setup the rater
			var options = {
				module: module,
				recordId: ratingElement.data("short_id"),
				rating: parseFloat(userRating > 0 ? userRating : ratingElement.data("average_rating")) ,
				postHref: Globals.path + "/" + module + "/" + ratingElement.data("record_id") + "/AJAX?method=RateTitle"
			};
			ratingElement.rater(options);
		});
	},

	doRatingReview: function (rating, module, id){
		if (rating <= 2){
			msg = "We're sorry you didn't like this title.  Would you like to add a review explaining why to help other users?";
		}else{
			msg = "We're glad you liked this title.  Would you like to add a review explaining why to help other users?";
		}
		if (confirm(msg)){
			var reviewForm;
			if (module == 'EcontentRecord'){
				reviewForm = $("#userecontentreview" + id);

			}else{
				reviewForm = $("#userreview" + id);
			}
			reviewForm.find(".rateTitle").hide();
			reviewForm.show();
		}
	}
};

VuFind.OverDrive = {
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
	}
};

VuFind.Record = {
	getSaveToListForm: function getSaveToListForm(trigger, id, source){
		if (Globals.loggedIn){
			var url = Globals.path + "/Resource/Save?lightbox=true&id=" + id + "&source=" + source;
			var $trigger = $(trigger);
			$("#modal-title").text($trigger.attr("title"));
			var modalDialog = $("#modalDialog");
			modalDialog.load(url);
			modalDialog.modal('show');
		}else{
			trigger = $(trigger);
			VuFind.Account.ajaxLogin(trigger, function (){
				VuFind.Record.getSaveToListForm(trigger, id, source);
			});
		}
		return false;
	},

	loadEnrichmentInfo: function (id, isbn, upc, econtent) {
		var url = Globals.path + "/Record/" + encodeURIComponent(id) + "/AJAX";
		var params = "method=GetEnrichmentInfo&isbn=" + encodeURIComponent(isbn) + "&upc=" + encodeURIComponent(upc);
		var fullUrl = url + "?" + params;
		$.ajax( {
			url : fullUrl,
			success : function(data) {
				try{
					var seriesData = $(data).find("SeriesInfo").text();
					if (seriesData && seriesData.length > 0) {

						seriesScroller = new TitleScroller('titleScrollerSeries', 'Series', 'seriesList');

						seriesData = $.parseJSON(seriesData);
						if (seriesData.titles.length > 0){
							$('#list-series-tab').show();
							$('#relatedTitleInfo').show();
							seriesScroller.loadTitlesFromJsonData(seriesData);
						}
					}
					var showGoDeeperData = $(data).find("ShowGoDeeperData").text();
					if (showGoDeeperData) {
						$('#goDeeperLink').show();
					}
					var relatedContentData = $(data).find("RelatedContent").text();
					if (relatedContentData && relatedContentData.length > 0) {
						$("#relatedContentPlaceholder").html(relatedContentData);
					}
				} catch (e) {
					alert("error loading enrichment: " + e);
				}
			},
			failure : function(jqXHR, textStatus, errorThrown) {
				alert('Error: Could Not Load Holdings information.  Please try again in a few minutes');
			}
		});
	},

	loadHoldingsInfo: function (id) {
		var url = Globals.path + "/Record/" + encodeURIComponent(id) + "/AJAX";
		var params = "method=GetHoldingsInfo";
		var fullUrl = url + "?" + params;
		$.ajax( {
			url : fullUrl,
			success : function(data) {
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
				var eAudioLink = $(data).find("EAudioLink").text();
				if (eAudioLink) {
					if (eAudioLink.length > 0) {
						$("#eAudioLink" + id).html("<a href='" + eAudioLink + "'><img src='" + path + "/interface/themes/wcpl/images/access_eaudio.png' alt='Access eAudio'/></a>").show();
					}
				}
				var eBookLink = $(data).find("EBookLink").text();
				if (eBookLink) {
					if (eBookLink.length > 0) {
						$("#eBookLink" + id).html("<a href='" + eBookLink + "'><img src='" + path + "/interface/themes/wcpl/images/access_ebook.png' alt='Access eBook'/></a>").show();
					}
				}
			}
		});
	},

	loadReviewInfo: function (id, isbn) {
		var url = Globals.path + "/Record/" + encodeURIComponent(id) + "/AJAX";
		var params = "method=GetReviewInfo&isbn=" + encodeURIComponent(isbn);
		var fullUrl = url + "?" + params;
		$.ajax( {
			url : fullUrl,
			success : function(data) {
				var reviewsData = $(data).find("Reviews").text();
				if (reviewsData) {
					if (reviewsData.length > 0) {
						$("#reviewPlaceholder").html(reviewsData);
					}else{
						//$("#reviewPlaceholder").html("There are no reviews for this title.");
					}
				}else{
					//$("#reviewPlaceholder").html("There are no reviews for this title.");
				}
			}
		});
	},

	showReviewForm: function(trigger, id, source){
		if (Globals.loggedIn){
			var $trigger = $(trigger);
			$("#modal-title").text($trigger.attr("title"));
			var modalDialog = $("#modalDialog");
			//$(".modal-body").html($('#userreview' + id).html());
			modalDialog.load(Globals.path + "/Resource/AJAX?method=GetReviewForm&id=" + id + "&source=" + source);
			modalDialog.modal('show');
		}else{
			var $trigger = $(trigger);
			VuFind.Account.ajaxLogin($trigger, function (){
				return VuFind.Record.showReviewForm($trigger, id, source);
			});
		}
		return false;
	}

};

VuFind.Searches = {
	searchGroups: [],

	addAdvancedGroup: function(button){
		var currentRow;
		if (button == undefined){
			currentRow = $(".advancedRow").last();
		}else{
			currentRow = $(button).closest(".advancedRow");
		}

		//Clone the current row and reset data and ids as needed.
		var clonedData = currentRow.clone();
		clonedData.find(".btn").removeClass('active');
		clonedData.find('.lookfor').val("");
		clonedData.insertAfter(currentRow);

		VuFind.Searches.resetAdvancedRowIds();
		return false;
	},

	deleteAdvancedGroup: function(button){
		var currentRow = $(button).closest(".advancedRow");
		currentRow.remove();

		VuFind.Searches.resetAdvancedRowIds();
		return false;
	},

	enableSearchTypes: function(){
		var searchTypeElement = $("#searchSource");
		var selectedSearchType = $(searchTypeElement.find(":selected"));
		var catalogType = selectedSearchType.data("catalog_type");
		if (catalogType == "catalog"){
			$(".catalogType").show();
			$(".genealogyType").hide();
		}else{
			$(".catalogType").hide();
			$(".genealogyType").show();
		}
	},

	lastSpellingTimer: undefined,
	getSpellingSuggestion: function(query, process, isAdvanced){
		if (VuFind.Searches.lastSpellingTimer != undefined){
			clearTimeout(VuFind.Searches.lastSpellingTimer);
			VuFind.Searches.lastSpellingTimer = undefined;
		}

		var url = Globals.path + "/Search/AJAX?method=GetAutoSuggestList&searchTerm=" + query;
		//Get the search source
		if (isAdvanced){
			//Add the search type
		}
		VuFind.Searches.lastSpellingTimer = setTimeout(
				function(){
					$.get(url,
							function(data){
								process(data);
							},
							'json'
					)
				},
			500
		);
	},

	loadSearchGroups: function(){
		var searchGroups = VuFind.Searches.searchGroups;
		for (var i = 0; i < searchGroups.length; i++){
			if (i > 0){
				VuFind.Searches.addAdvancedGroup();
			}
			var searchGroup = searchGroups[i];
			var groupIndex = i+1;
			var searchGroupElement = $("#group" + groupIndex);
			searchGroupElement.find(".groupStartInput").val(searchGroup.groupStart);
			if (searchGroup.groupStart == 1){
				searchGroupElement.find(".groupStartButton").addClass("active");
			}
			searchGroupElement.find(".searchType").val(searchGroup.searchType);
			searchGroupElement.find(".lookfor").val(searchGroup.lookfor);
			searchGroupElement.find(".groupEndInput").val(searchGroup.groupEnd);
			if (searchGroup.groupEnd == 1){
				searchGroupElement.find(".groupEndButton").addClass("active");
			}
			searchGroupElement.find(".joinOption").val(searchGroup.join);
		}
		if (searchGroups.length == 0){
			VuFind.Searches.resetAdvancedRowIds();
		}
	},


	processSearchForm: function(){
		//Get the selected search type submit the form
		var searchSource = $("#searchSource");
		if (searchSource.val() == 'existing'){
			$(".existingFilter").prop('checked', true);
			var originalSearchSource = $("#existing_search_option").data('original_type');
			searchSource.val(originalSearchSource);
		}
	},

	resetAdvancedRowIds: function(){
		var searchRows = $(".advancedRow");
		searchRows.each(function(index, element){
			var indexVal = index + 1;
			var curRow = $(element);
			curRow.attr("id", "group" + indexVal);
			curRow.find(".groupStartInput")
					.prop("name", "groupStart[" + indexVal + "]")
					.attr("id", "groupStart" + indexVal + "Input");

			curRow.find(".groupStartButton")
					.data("hidden_element", "groupStart" + indexVal + "Input")
					.attr("id", "groupStart" + indexVal);

			curRow.find(".searchType")
					.attr("name", "searchType[" + indexVal + "]");

			curRow.find(".lookfor")
					.attr("name", "lookfor[" + indexVal + "]");

			curRow.find(".groupEndInput")
					.prop("name", "groupEnd[" + indexVal + "]")
					.attr("id", "groupEnd" + indexVal + "Input");

			curRow.find(".groupEndButton")
					.data("hidden_element", "groupEnd" + indexVal + "Input")
					.attr("id", "groupEnd" + indexVal);

			curRow.find(".joinOption")
					.attr("name", "join[" + indexVal + "]");
		});
		if (searchRows.length == 1){
			$(".deleteCriteria").hide();
			$(".groupStartButton").hide();
			$(".groupEndButton").hide();
		}else{
			$(".deleteCriteria").show();
			$(".groupStartButton").show();
			$(".groupEndButton").show();
		}
		var joinOptions = $(".joinOption");
		joinOptions.show();
		joinOptions.last().hide();
	},

	resetSearchType: function(){
		if ($("#lookfor").val() == ""){
			$("#searchSource").val($("#default_search_type").val());
		}
		return true;
	},

	updateSearchTypes: function(catalogType, searchType, searchFormId){
		if (catalogType == 'catalog'){
			$("#basicType").val(searchType);
			$("#genealogyType").remove();
		}else{
			$("#genealogyType").val(searchType);
			$("#basicType").remove();
		}
		$(searchFormId).submit();
		return false;
	}

};

VuFind.Prospector = {
	getProspectorResults: function(prospectorNumTitlesToLoad, prospectorSavedSearchId){
		var url = Globals.path + "/Search/AJAX";
		var params = "method=getProspectorResults&prospectorNumTitlesToLoad=" + encodeURIComponent(prospectorNumTitlesToLoad) + "&prospectorSavedSearchId=" + encodeURIComponent(prospectorSavedSearchId);
		var fullUrl = url + "?" + params;
		$.ajax({
			url: fullUrl,
			success: function(data) {
				var prospectorSearchResults = $(data).find("ProspectorSearchResults").text();
				if (prospectorSearchResults) {
					if (prospectorSearchResults.length > 0){
						$("#prospectorSearchResultsPlaceholder").html(prospectorSearchResults);
					}
				}
			}
		});
	},

	loadRelatedProspectorTitles: function (id) {
		var url = Globals.path + "/Record/" + encodeURIComponent(id) + "/AJAX";
		var params = "method=GetProspectorInfo";
		var fullUrl = url + "?" + params;
		$.ajax( {
			url : fullUrl,
			success : function(data) {
				var inProspectorData = $(data).find("InProspector").text();
				if (inProspectorData) {
					if (inProspectorData.length > 0) {
						$("#inProspectorPlaceholder").html(inProspectorData);
					}
					var prospectorCopies = $(data).find("OwningLibrariesFormatted").text();
					if (prospectorCopies && prospectorCopies.length > 0) {
						$("#prospectorHoldingsPlaceholder").html(prospectorCopies);
					}
					$("#inProspectorSidegroup").show();
				}else{
					if ($("#prospectortab_label")){
						$("#prospectortab_label").hide();
						if ($("#holdingstab_label").is(":visible")){
							$("#moredetails-tabs").tabs("option", "active", 0);
						}else{
							$("#moredetails-tabs").tabs("option", "active", 2);
						}
					}
				}
			}
		});
	}
};

VuFind.Wikipedia = {
	getWikipediaArticle: function(articleName){
		var url = Globals.path + "/Author/AJAX?method=getWikipediaData&articleName=" + articleName;
		$.getJSON(url, function(data){
			if (data.success)
				$("#wikipedia_placeholder").html(data.formatted_article);
		});
	}
};

$(document).ready(function(){
	VuFind.Searches.enableSearchTypes();
	VuFind.Ratings.initializeRaters();
	VuFind.initializeModalDialogs();
	$(window).resize(VuFind.Responsive.adjustLayout);
	$(window).trigger('resize');

	$("#modalDialog").modal({show:false});
	var lookfor = $("#lookfor");
	if (lookfor.length > 0){
		lookfor.focus().select();
		var typeaheadOptions = {
			minLength: 3,
			source: function(query, process){
				VuFind.Searches.getSpellingSuggestion(query, process, false);
			},
			matcher: function(item){
				return true;
			},
			highlighter: function(item){
				var query = this.query;
				var queryParts = query.split(/[\W]+/);
				for (var i = 0; i < queryParts.length; i++){
					var index = item.indexOf(queryParts[i]);
					if ( index >= 0 ){
						item = item.substring(0,index) +
								"<strong>" +
								item.substring(index,index + queryParts[i].length) +
								"</strong>" +
								item.substring(index + queryParts[i].length);
					}
				}
				return item;
			}
		};
		lookfor.typeahead(typeaheadOptions);
	}
});

