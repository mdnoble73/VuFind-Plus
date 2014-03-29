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

VuFind.getSelectedTitles = function(){
	var selectedTitles = $("input.titleSelect:checked ").map(function() {
		return $(this).attr('name') + "=" + $(this).val();
	}).get().join("&");
	if (selectedTitles.length == 0){
		var ret = confirm('You have not selected any items, process all items?');
		if (ret == true){
			$("input.titleSelect").attr('checked', 'checked');
			selectedTitles = $("input.titleSelect").map(function() {
				return $(this).attr('name') + "=" + $(this).val();
			}).get().join("&");
		}
	}
	return selectedTitles;
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

VuFind.showMessage = function(title, body, autoClose){
	if (autoClose == undefined){
		autoClose = false;
	}
	$("#modal-title").html(title);
	$(".modal-body").html(body)
	var modalDialog = $("#modalDialog");
	modalDialog.modal('show');
	if (autoClose){
		setTimeout("VuFind.closeLightbox();", 3000);
	}
}

VuFind.toggleHiddenElementWithButton = function(button){
	var hiddenElementName = $(button).data('hidden_element');
	var hiddenElement = $(hiddenElementName);
	hiddenElement.val($(button).hasClass('active') ? '1' : '0');
	return false;
};

VuFind.ajaxLightbox = function(urlToDisplay, requireLogin){
	if (requireLogin == undefined){
		requireLogin = false;
	}
	if (requireLogin && !Globals.loggedIn){
		VuFind.Account.ajaxLogin(null, function(){
			VuFind.ajaxLightbox(urlToDisplay, requireLogin);
		}, false);
	}else{
		VuFind.closeLightbox();
		$(".modal-body").html("Loading");
		var modalDialog = $("#modalDialog");
		modalDialog.load(urlToDisplay, function(){
			modalDialog.modal('show');
		});
	}
	return false;
};

VuFind.closeLightbox = function(){
	var modalDialog = $("#modalDialog");
	if (modalDialog.is(":visible")){
		modalDialog.modal('hide');
		$(".modal-backdrop").remove();
	}
};

VuFind.Account = {
	ajaxCallback: null,
	closeModalOnAjaxSuccess: false,

	/**
	 * Creates a new list in the system for the active user.
	 *
	 * Called from list-form.tpl
	 * @returns {boolean}
	 */
	addList:function(){
		var form = $("#addListForm");
		var isPublic = form.find("#public").prop("checked");
		var recordId = form.find("input[name=recordId]").val();
		var source = form.find("input[name=source]").val();
		var title = form.find("input[name=title]").val();
		var desc = form.find("input[name=desc]").val();

		var url = Globals.path + "/MyResearch/AJAX";
		var params = "method=AddList&" +
				"title=" + encodeURIComponent(title) + "&" +
				"public=" + isPublic + "&" +
				"desc=" + encodeURIComponent(desc) + "&";

		$.ajax({
			url: url+'?'+params,
			dataType: "json",
			success: function(data) {
				var value = data.result;
				if (value) {
					if (value == "Done") {
						var newId = data.newId;
						//Save the record to the list
						var url = Globals.path + "/Resource/Save?lightbox=true&selectedList=" + newId + "&id=" + recordId + "&source=" + source;
						VuFind.ajaxLightbox(url);
					} else {
						$("#modal-title").html("Error creating list");
						$(".modal-body").html("<div class='alert alert-error'>There was a error creating your list<br/>" + value + "</div>")
					}
				} else {
					$("#modal-title").html("Error creating list");
					$(".modal-body").html("<div class='alert alert-error'>There was a error creating your list</div>")
				}
			},
			error: function() {
				$("#modal-title").html("Error creating list");
				$(".modal-body").html("<div class='alert alert-error'>There was an unexpected error creating your list<br/>" + textStatus + "</div>")
			}
		});

		return false;
	},

	/**
	 * Do an ajax process, but only if the user is logged in.
	 * If the user is not logged in, force them to login and then do the process.
	 * Can also be called without the ajax callback to just login and not go anywhere
	 *
	 * @param trigger
	 * @param ajaxCallback
	 * @param closeModalOnAjaxSuccess
	 * @returns {boolean}
	 */
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
			if (trigger != undefined && trigger != null){
				var dialogTitle = trigger.attr("title") ? trigger.attr("title") : trigger.data("title");
			}
			var dialogDestination = Globals.path + '/MyResearch/AJAX?method=LoginForm';
			var modalDialog = $("#modalDialog");
			modalDialog.load(dialogDestination);
			$("#modal-title").text(dialogTitle);
			modalDialog.modal("show");
		}
		return false;
	},

	followLinkIfLoggedIn: function(trigger, linkDestination){
		if (trigger == undefined){
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
						VuFind.closeLightbox();
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
	},

	renewSelectedTitles: function(){
		var selectedTitles = VuFind.getSelectedTitles();
		if (selectedTitles.length == 0){
			return false;
		}
		$('#renewForm').submit();
		return false;
	}
};

VuFind.Admin = {
	showReindexNotes: function (id){
		VuFind.ajaxLightbox("/Admin/AJAX?method=getReindexNotes&id=" + id);
		return false;
	},
	showReindexProcessNotes: function (id){
		VuFind.ajaxLightbox("/Admin/AJAX?method=getReindexProcessNotes&id=" + id);
		return false;
	},
	toggleReindexProcessInfo: function (id){
		$("#reindexEntry" + id).toggleClass("expanded collapsed");
		$("#processInfo" + id).toggle();
	},
	showReindexProcessNotes: function (id){
		VuFind.ajaxLightbox("/Admin/AJAX?method=getReindexProcessNotes&id=" + id);
		return false;
	},
	showCronNotes: function (id){
		VuFind.ajaxLightbox("/Admin/AJAX?method=getCronNotes&id=" + id);
		return false;
	},
	showCronProcessNotes: function (id){
		VuFind.ajaxLightbox("/Admin/AJAX?method=getCronProcessNotes&id=" + id);
		return false;
	},
	toggleCronProcessInfo: function (id){
		$("#cronEntry" + id).toggleClass("expanded collapsed");
		$("#processInfo" + id).toggle();
	},
	showOverDriveExtractNotes: function (id){
		VuFind.ajaxLightbox("/Admin/AJAX?method=getOverDriveExtractNotes&id=" + id);
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
	cancelOverDriveHold: function(overdriveId){
		var ajaxUrl = Globals.path + "/EcontentRecord/AJAX?method=CancelOverDriveHold&overDriveId=" + overDriveId + "&formatId=" + formatId;
		$.ajax({
			url: ajaxUrl,
			cache: false,
			success: function(data){
				if (data.result){
					VuFind.showMessage("Hold Cancelled", data.message, true);
					//remove the row from the holds list
					$("#overDriveHold_" + overDriveId).hide();
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
							ret = confirm(data.message)
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
				error: function(jqXHR, textStatus, errorThrown){
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
					VuFind.OverDrive.checkoutOverDriveItem(overdriveId, formatId);
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

	getOverDriveHoldPrompts: function(overDriveId, formatId, nextAction){
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
};

VuFind.Record = {
	getAddTagForm: function(trigger, id, source){
		if (Globals.loggedIn){
			var url = Globals.path + "/Resource/AJAX?method=GetAddTagForm&id=" + id + "&source=" + source;
			var $trigger = $(trigger);
			$("#modal-title").text($trigger.attr("title"));
			var modalDialog = $("#modalDialog");
			modalDialog.load(url);
			modalDialog.modal('show');
		}else{
			trigger = $(trigger);
			VuFind.Account.ajaxLogin(trigger, function (){
				VuFind.Record.getAddTagForm(trigger, id, source);
			});
		}
		return false;
	},

	getSaveToListForm: function (trigger, id, source){
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
		if (source == 'VuFind'){
			var url = Globals.path + "/Record/" + encodeURIComponent(id) + "/AJAX";
		}else{
			var url = Globals.path + "/EcontentRecord/" + encodeURIComponent(id) + "/AJAX";
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
							$("#accessOnline" + id).attr("href", url);
							$("#accessOnline" + id).text($("<div/>").html(text).text());
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

	saveTag: function(id, source, form){
		var tag = $("#tags_to_apply").val();
		$("#saveToList-button").prop('disabled', true);

		var url = Globals.path + "/Resource/AJAX";
		var params = "method=SaveTag&" +
				"tag=" + encodeURIComponent(tag) + "&" +
				"id=" + id + "&" +
				"source=" + source;
		$.ajax({
			url: url+'?'+params,
			dataType: "json",
			success: function(data) {
				var value = data.result;
				if (value == "Done") {
					$("#modal-title").html("Add to Tag Result");
					$(".modal-body").html("<div class='alert alert-success'>" + data.message + "</div>")
					setTimeout("VuFind.closeLightbox();", 3000);
				} else {
					$("#modal-title").html("Error adding tags");
					$(".modal-body").html("<div class='alert alert-error'>There was an unexpected error adding tags to this title.<br/>" + data.message + "</div>")
				}

			},
			error: function(jqXHR, textStatus, errorThrown) {
				$("#modal-title").html("Error adding tags");
				$(".modal-body").html("<div class='alert alert-error'>There was an unexpected error adding tags to this title.<br/>" + textStatus + "</div>")
			}
		});
	},

	saveToList: function(id, source, form){
		var notes = $("#addToList-notes").val();
		var list = $("#addToList-list").val();
		$("#saveToList-button").prop('disabled', true);

		var url = Globals.path + "/Resource/AJAX";
		var params = "method=SaveRecord&" +
				"list=" + list + "&" +
				"notes=" + encodeURIComponent(notes) + "&" +
				"id=" + id + "&" +
				"source=" + source;
		$.ajax({
			url: url+'?'+params,
			dataType: "json",
			success: function(data) {
				var value = data.result;
				if (value == "Done") {
					$("#modal-title").html("Add to List Result");
					$(".modal-body").html("<div class='alert alert-success'>" + data.message + "</div>")
					setTimeout("VuFind.closeLightbox();", 3000);
				} else {
					$("#modal-title").html("Error adding to list");
					$(".modal-body").html("<div class='alert alert-error'>There was an unexpected error adding the title to the list.<br/>" + data.message + "</div>")
				}

			},
			error: function(jqXHR, textStatus, errorThrown) {
				$("#modal-title").html("Error adding to list");
				$(".modal-body").html("<div class='alert alert-error'>There was an unexpected error adding the title to the list.<br/>" + textStatus + "</div>")
			}
		});
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

	loadRelatedProspectorTitles: function (id, source) {
		if (source == 'VuFind'){
			var url = Globals.path + "/Record/" + encodeURIComponent(id) + "/AJAX";
		}else{
			var url = Globals.path + "/EcontentRecord/" + encodeURIComponent(id) + "/AJAX";
		}
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

/**
 * Created by MNoble; copied over by bmcfadden; then copied into this file from its own file (js/vufind/responsive.js  on 3/25/14.
 */

VuFind.Responsive = (function(){
    $(document).ready(function(){
        $(window).resize(VuFind.Responsive.adjustLayout);
        $(window).trigger('resize');
    });

    return {
        adjustLayout: function(){
            // get resolution
            var resolution = document.documentElement.clientWidth;

            var mainContentElement = $("#main-content-with-sidebar");
            var xsContentInsertionPointElement = $("#xs-main-content-insertion-point");
            var mainContent;
            if (resolution < 750) {
                // XS screen resolution
                //move content from main-content-with-sidebar to xs-main-content-insertion-point
                mainContent = mainContentElement.html();
                if (mainContent && mainContent.length){
                    xsContentInsertionPointElement.html(mainContent);
                    mainContentElement.html("");
                }
            }else{
                //Sm or better resolution
                mainContent = xsContentInsertionPointElement.html();
                if (mainContent && mainContent.length){
                    mainContentElement.html(mainContent);
                    xsContentInsertionPointElement.html("");
                }
            }
        }
    };
}(VuFind.Responsive || {}));

$(document).ready(function(){
    $(document).scroll(function(){
    var docH = $(document).height();
    var scrH = $(document).scrollTop();
    var halfit = docH/2;
    //if( scrH >= halfit){
        $("#scrollupdown").removeClass("trans5").addClass("trans2");
    //}
    });
    $(document).scroll(function() {
        clearTimeout($.data(this, 'scrollTimer'));
        $.data(this, 'scrollTimer', setTimeout(function() {
            // do something
            $("#scrollupdown").removeClass("trans2").addClass("trans5");
            //console.log("Haven't scrolled in 250ms!");
        }, 500));
    });
    $("#scrolldown").click(function(){
        $("html,body").animate({scrollTop:$(document).height()}, 1000);
        //$("#scrollup").show();
        //$(this).hide();
    });
    $("#scrollup").click(function(){
        $("html,body").animate({scrollTop:0}, 1000);
        //$("#scrolldown").show();
        //$(this).hide();
    });

});