/**
 * Created by mark on 1/14/14.
 */
VuFind.Account = (function(){

	return {
		ajaxCallback: null,
		closeModalOnAjaxSuccess: false,
		//haslocalStorage: null, // disable by default

		/**
		 * Creates a new list in the system for the active user.
		 *
		 * Called from list-form.tpl
		 * @returns {boolean}
		 */
		addList: function () {
			var form = $("#addListForm"),
					isPublic = form.find("#public").prop("checked"),
					recordId = form.find("input[name=recordId]").val(),
					source = form.find("input[name=source]").val(),
					title = form.find("input[name=title]").val(),
					desc = $("#listDesc").val(),
					url = Globals.path + "/MyAccount/AJAX",
					params = {
							'method':'AddList',
							title: title,
							public: isPublic,
							desc: desc,
							recordId: recordId
						};
			$.getJSON(url, params,function (data) {
					if (data.result) {
						VuFind.showMessage("Added Successfully", data.message, true);
					} else {
						VuFind.showMessage("Error", data.message);
					}
			}).fail(function(){
					VuFind.showMessage("Error creating list", "There was an unexpected error creating your list")
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
		ajaxLogin: function (trigger, ajaxCallback, closeModalOnAjaxSuccess) {
			if (Globals.loggedIn) {
				if (ajaxCallback != undefined && typeof(ajaxCallback) === "function") {
					ajaxCallback();
				} else if (VuFind.Account.ajaxCallback != null && typeof(VuFind.Account.ajaxCallback) === "function") {
					VuFind.Account.ajaxCallback();
					VuFind.Account.ajaxCallback = null;
				}
			} else {
				var multistep = false,
						loginLink = false;
				if (ajaxCallback != undefined && typeof(ajaxCallback) === "function") {
					multistep = true;
				}
				VuFind.Account.ajaxCallback = ajaxCallback;
				VuFind.Account.closeModalOnAjaxSuccess = closeModalOnAjaxSuccess;
				if (trigger != undefined && trigger != null) {
					var dialogTitle = trigger.attr("title") ? trigger.attr("title") : trigger.data("title");
					loginLink = trigger.data('login');
					/*
					  Set the trigger html element attribute data-login="true" to cause the pop-up login dialog
					  to act as if the only action is login, ie not a multi-step process.

					 */
				}
				var dialogDestination = Globals.path + '/MyAccount/AJAX?method=LoginForm';
				if (multistep && !loginLink){
					dialogDestination += "&multistep=true";
				}
				var modalDialog = $("#modalDialog");
				$('.modal-body').html("Loading...");
				//var modalBody = $(".modal-content");
				//modalBody.load(dialogDestination);
				$(".modal-content").load(dialogDestination);
				$(".modal-title").text(dialogTitle);
				modalDialog.modal("show");
			}
			return false;
		},

		followLinkIfLoggedIn: function (trigger, linkDestination) {
			if (trigger == undefined) {
				alert("You must provide the trigger to follow a link after logging in.");
			}
			var jqTrigger = $(trigger);
			if (linkDestination == undefined) {
				linkDestination = jqTrigger.attr("href");
			}
			this.ajaxLogin(jqTrigger, function () {
				document.location = linkDestination;
			}, true);
			return false;
		},

		preProcessLogin: function (){
			var username = $("#username").val(),
				password = $("#password").val(),
				loginErrorElem = $('#loginError');
			if (!username || !password) {
				loginErrorElem
						.text($("#missingLoginPrompt").text())
						.show();
				return false;
			}
			if (VuFind.hasLocalStorage()){
				//var rememberMeCtl = $("#rememberMe");
				var rememberMe = $("#rememberMe").prop('checked'),
						showPwd = $('#showPwd').prop('checked');
				if (rememberMe){
					window.localStorage.setItem('lastUserName', username);
					window.localStorage.setItem('lastPwd', password);
					window.localStorage.setItem('showPwd', showPwd);
					window.localStorage.setItem('rememberMe', rememberMe);
				}else{
					window.localStorage.removeItem('lastUserName');
					window.localStorage.removeItem('lastPwd');
					window.localStorage.removeItem('showPwd');
					window.localStorage.removeItem('rememberMe');
				}
			}
			return true;
		},

		processAjaxLogin: function (ajaxCallback) {
			if(this.preProcessLogin()) {
				var username = $("#username").val(),
						password = $("#password").val(),
						rememberMe = $("#rememberMe").prop('checked'),
						loginErrorElem = $('#loginError'),
						url = Globals.path + "/AJAX/JSON?method=loginUser";
				loginErrorElem.hide();
				$.ajax({
					url: url,
					data: {username: username, password: password, rememberMe: rememberMe},
					success: function (response) {
						if (response.result.success == true) {
							// Hide "log in" options and show "log out" options:
							$('.loginOptions, #loginOptions').hide();
							$('.logoutOptions, #logoutOptions').show();

							// Show user name on page in case page doesn't reload
							var name = response.result.name.trim();
							$('#header-container #myAccountNameLink').html(name);
							name = 'Logged In As ' + name.slice(0, name.lastIndexOf(' ') + 2) + '.';
							$('#side-bar #myAccountNameLink').html(name);

							if (VuFind.Account.closeModalOnAjaxSuccess) {
								VuFind.closeLightbox();
							}

							Globals.loggedIn = true;
							if (ajaxCallback != undefined && typeof(ajaxCallback) === "function") {
								ajaxCallback();
							} else if (VuFind.Account.ajaxCallback != undefined && typeof(VuFind.Account.ajaxCallback) === "function") {
								VuFind.Account.ajaxCallback();
								VuFind.Account.ajaxCallback = null;
							}
						} else {
							loginErrorElem.text(response.result.message);
							loginErrorElem.show();
						}
					},
					error: function () {
						loginErrorElem.text("There was an error processing your login, please try again.")
								.show();
					},
					dataType: 'json',
					type: 'post'
				});
			}
			return false;
		},

		removeTag: function(tag){
			if (confirm("Are you sure you want to remove the tag \"" + tag + "\" from all titles?")){
				//var url = Globals.path + "/MyAccount/AJAX?method=removeTag&tag=" + encodeURI(tag);
				var url = Globals.path + "/MyAccount/AJAX",
						params = {method:'removeTag', tag: tag};
				//$.getJSON(url, function(data){
				$.getJSON(url, params, function(data){
					if (data.result == true){
						VuFind.showMessage('Tag Deleted', data.message, true, true);
						//setTimeout(function(){window.location.reload()}, 3000);
					}else{
						VuFind.showMessage('Tag Not Deleted', data.message);
					}
				});
			}
			return false;
		},

		renewTitle: function(renewIndicator) {
			if (!Globals.loggedIn) {
				this.ajaxLogin(null, function () {
					this.renewTitle(renewIndicator);
				}, false);
			} else {
				VuFind.showMessage('Loading', 'Loading, please wait');
				$.getJSON(Globals.path + "/MyAccount/AJAX?method=renewItem&renewIndicator="+renewIndicator, function(data){
					VuFind.showMessage(data.title, data.modalBody, data.success, data.success); // autoclose when successful
				}).fail(function(){
					VuFind.showMessage('Request Failed', 'There was an error with this AJAX Request.');
				});
			}
			return false;
		},

		renewAll: function() {
			if (!Globals.loggedIn) {
				this.ajaxLogin(null, function () {
					this.renewAll();
				}, false);
			} else if (confirm('Renew All Items?')) {
					VuFind.showMessage('Loading', 'Loading, please wait');
					$.getJSON(Globals.path + "/MyAccount/AJAX?method=renewAll", function (data) {
						VuFind.showMessage(data.title, data.modalBody, data.success);
						// autoclose when all successful
						if (data.success || data.renewed > 0) {
							// Refresh page on close when a item has been successfully renewed, otherwise stay
							$("#modalDialog").on('hidden.bs.modal', function (e) {
								location.reload(true);
							});
						}
					}).fail(function(){
						VuFind.showMessage('Request Failed', 'There was an error with this AJAX Request.');
					});
				}
			return false;
		},

		//// old form submission method. Replacing with ajax calls.
		//renewSelectedTitles: function () {
		//	var selectedTitles = VuFind.getSelectedTitles();
		//	if (selectedTitles.length == 0) {
		//		return false;
		//	}
		//	$('#renewForm').submit();
		//	return false;
		//},
		//
		renewSelectedTitles: function () {
			if (!Globals.loggedIn) {
				this.ajaxLogin(null, function () {
					this.renewSelectedTitles();
				}, false);
			} else {
				var selectedTitles = VuFind.getSelectedTitles();
				if (selectedTitles) {
					if (confirm('Renew selected Items?')) {
						VuFind.showMessage('Loading', 'Loading, please wait');
						$.getJSON(Globals.path + "/MyAccount/AJAX?method=renewSelectedItems&"+selectedTitles, function (data) {
							var reload = data.success || data.renewed > 0;
							VuFind.showMessage(data.title, data.modalBody, data.success, reload);
							// autoclose when all successful
							//if (data.success || data.renewed > 0) {
							//	// Refresh page on close when a item has been successfully renewed, otherwise stay
							//	$("#modalDialog").on('hidden.bs.modal', function (e) {
							//		location.reload(true);
							//	});
							//}
						}).fail(function(){
							VuFind.showMessage('Request Failed', 'There was an error with this AJAX Request.');
						});
					}
				}
			}
			return false
		},

		resetPin: function(){
			var barcode = $('#card_number').val();
			if (barcode.length == 0){
				alert("Please enter your library card number");
			}else{
				var url = path + '/MyAccount/AJAX?method=requestPinReset&barcode=' + barcode;
				$.getJSON(url, function(data){
					if (data.error == false){
						alert(data.message);
						if (data.result == true){
							hideLightbox();
						}
					}else{
						alert("There was an error requesting your pin reset information.  Please contact the library for additional information.");
					}
				});
			}
			return false;
		},

		ajaxLightbox: function (urlToDisplay, requireLogin) {
			if (requireLogin == undefined) {
				requireLogin = false;
			}
			if (requireLogin && !Globals.loggedIn) {
				VuFind.Account.ajaxLogin(null, function () {
					VuFind.Account.ajaxLightbox(urlToDisplay, requireLogin);
				}, false);
			} else {
				var modalDialog = $("#modalDialog");
				$('#myModalLabel').html("Loading, please wait");
				$('.modal-body').html("...");
				$.getJSON(urlToDisplay, function(data){
					if (data.result){
						data = data.result;
					}
					$('#myModalLabel').html(data.title);
					$('.modal-body').html(data.modalBody);
					$('.modal-buttons').html(data.modalButtons);
				});
				//modalDialog.load( );
				modalDialog.modal('show');
			}
			return false;
		},

		cancelHold: function(holdIdToCancel){
			if (confirm("Are you sure you want to cancel this hold?")){
				if (!Globals.loggedIn) {
					this.ajaxLogin(null, function () {
						VuFind.Account.cancelHold(holdIdToCancel);
					}, false);
				} else {
					VuFind.showMessage('Loading', 'Loading, please wait');
					$.getJSON(Globals.path + "/MyAccount/AJAX?method=cancelHold&cancelId="+holdIdToCancel, function(data){
						VuFind.showMessage(data.title, data.modalBody, data.success); // autoclose when successful
						if (data.success) {
							// remove canceled item from page
							var escapedHoldId = holdIdToCancel.replace("~", "\\~"); // needed for jquery selector to work correctly
							// first backslash for javascript escaping, second for css escaping (within jquery)
							$('div.result').has('#selected'+escapedHoldId).remove();
						}
					}).fail(function(){
						VuFind.showMessage('Request Failed', 'There was an error with this AJAX Request.');
					});
				}
			}

			return false;
		},

		cancelSelectedHolds: function() {
			if (!Globals.loggedIn) {
				this.ajaxLogin(null, function () {
					VuFind.Account.cancelSelectedHolds();
				}, false);
			} else {
				var selectedTitles = this.getSelectedTitles()
								.replace(/waiting|available/g, ''),// strip out of name for now.
						numHolds = $("input.titleSelect:checked").length;
				// if numHolds equals 0, quit because user has canceled in getSelectedTitles()
				if (numHolds > 0 && confirm('Cancel ' + numHolds + ' selected hold' + (numHolds > 1 ? 's' : '') + '?')) {
					VuFind.showMessage('Loading', 'Loading, please wait');
					$.getJSON(Globals.path + "/MyAccount/AJAX?method=cancelHolds&"+selectedTitles, function(data){
						VuFind.showMessage(data.title, data.modalBody, data.success); // autoclose when successful
							if (data.success) {
								// remove canceled items from page
								$("input.titleSelect:checked").closest('div.result').remove();
							} else if (data.failed) { // remove items that didn't fail
								var searchArray = data.failed.map(function(ele){return ele.toString()});
								 // convert any number values to string, this is needed bcs inArray() below does strict comparisons
								 // & id will be a string. (sometimes the id values are of type number )
								$("input.titleSelect:checked").each(function(){
									var id = $(this).attr('id').replace(/selected/g, ''); //strip down to just the id part
									if ($.inArray(id, searchArray) == -1) // if the item isn't one of the failed cancels, get rid of its containing div.
										$(this).closest('div.result').remove();
								});
							}
					}).fail(function(){
						VuFind.showMessage('Request Failed', 'There was an error with this AJAX Request.');
					});
				}
		}
		return false;
	},
/*
		cancelPendingHold: function(holdIdToCancel, recordId){
			if (!confirm("Are you sure you want to cancel this hold?")){
				return false;
			}
			var url = Globals.path + '/MyAccount/Holds?multiAction=cancelSelected&waitingholdselected[]=' + holdIdToCancel;
			url += '&recordId[' + holdIdToCancel + ']=' + recordId;
			var queryParams = VuFind.getQuerystringParameters();
			if ($.inArray('section', queryParams) && queryParams['section'] != 'undefined'){
				url += '&section=' + queryParams['section'];
			}
			window.location = url;
			return false;
		},

		cancelAvailableHold: function(holdIdToCancel, recordId){
			if (!confirm("Are you sure you want to cancel this hold?")){
				return false;
			}
			var url = Globals.path + '/MyAccount/Holds?multiAction=cancelSelected&availableholdselected[]=' + holdIdToCancel;
			url += '&recordId[' + holdIdToCancel + ']=' + recordId;
			var queryParams = VuFind.getQuerystringParameters();
			if ($.inArray('section', queryParams) && queryParams['section'] != 'undefined'){
				url += '&section=' + queryParams['section'];
			}
			window.location = url;
			return false;
		},

		cancelSelectedHolds: function(){
			var numHolds = $("input.titleSelect:checked ").length;
			if (numHolds == 0){
				alert('Please select one or more titles to cancel.');
				return false;
			} else if (!confirm('Cancel '+numHolds +' selected hold'+(numHolds > 1?'s':'') +'?')) {
					return false;
			}
			var selectedTitles = this.getSelectedTitles(false),
					url = Globals.path + '/MyAccount/Holds?multiAction=cancelSelected&' + selectedTitles,
					queryParams = VuFind.getQuerystringParameters();
			if ($.inArray('section', queryParams) && queryParams['section'] != 'undefined'){
				url += '&section=' + queryParams['section'];
			}
			window.location = url;
			return false;

		},
*/

		cancelBooking: function(cancelId){
			// TODO: code this
			alert('Cancel Booking!')

		},

		cancelSelectedBookings: function(cancelId){
			// TODO: code this
			alert('Cancel Booking!')

		},

		/* update the sort parameter and redirect the user back to the same page */
		changeAccountSort: function (newSort){
			// Get the current url
			var currentLocation = window.location.href;
			// Check to see if we already have a sort parameter. .
			if (currentLocation.match(/(accountSort=[^&]*)/)) {
				// Replace the existing sort with the new sort parameter
				currentLocation = currentLocation.replace(/accountSort=[^&]*/, 'accountSort=' + newSort);
			} else {
				// Add the new sort parameter
				if (currentLocation.match(/\?/)) {
					currentLocation += "&accountSort=" + newSort;
				}else{
					currentLocation += "?accountSort=" + newSort;
				}
			}
			// Redirect back to this page.
			window.location.href = currentLocation;
		},

		changeHoldPickupLocation: function (holdId){
			if (Globals.loggedIn){
				var modalDialog = $("#modalDialog");
				$('#myModalLabel').html('Loading');
				$('.modal-body').html('');
				$.getJSON(Globals.path + "/MyAccount/AJAX?method=getChangeHoldLocationForm&holdId=" + holdId, function(data){
					$('#myModalLabel').html(data.title);
					$('.modal-body').html(data.modalBody);
					$('.modal-buttons').html(data.modalButtons);
				});
				//modalDialog.load( );
				modalDialog.modal('show');
			}else{
				VuFind.Account.ajaxLogin(null, function (){
					return VuFind.Account.changeHoldPickupLocation(holdId);
				}, false);
			}
			return false;
		},

		deleteSearch: function(searchId){
			if (!Globals.loggedIn){
				VuFind.Account.ajaxLogin(null, function () {
					VuFind.Searches.saveSearch(searchId);
				}, false);
			}else{
				var url = Globals.path + "/MyAccount/AJAX";
				var params = "method=deleteSearch&searchId=" + encodeURIComponent(searchId);
				$.getJSON(url + '?' + params,
						function(data) {
							if (data.result) {
								VuFind.showMessage("Success", data.message);
							} else {
								VuFind.showMessage("Error", data.message);
							}
						}
				);
			}
			return false;
		},

		doChangeHoldLocation: function(){
			var holdId = $('#holdId').val();
			var newLocation = $('#newPickupLocation').val();
			var url = Globals.path + "/MyAccount/AJAX?method=changeHoldLocation&holdId=" + encodeURIComponent(holdId) + "&newLocation=" + encodeURIComponent(newLocation);
			// TODO: use getJSON data parameter for query string, does encoding
			$.getJSON(url,
					function(data) {
						if (data.result) {
							VuFind.showMessage("Success", data.message, true, true);
						} else {
							VuFind.showMessage("Error", data.message);
						}
					}
			);
		},

		freezeHold: function(holdId, promptForReactivationDate, caller){
			if (promptForReactivationDate){
				//Prompt the user for the date they want to reactivate the hold
				var modalDialog = $("#modalDialog");
				//$(".modal-body").html($('#userreview' + id).html());
				$.getJSON(Globals.path + "/MyAccount/AJAX?method=getReactivationDateForm&holdId=" + holdId, function(data){
					$('#myModalLabel').html(data.title);
					$('.modal-body').html(data.modalBody);
					$('.modal-buttons').html(data.modalButtons);
				});
				modalDialog.load( );
				modalDialog.modal('show');
			}else{
				var popUpBoxTitle = $(caller).text() || "Freezing Hold"; // freezing terminology can be customized, so grab text from click button: caller
				VuFind.showMessage(popUpBoxTitle, "Updating your hold.  This may take a minute.");
				var url = Globals.path + '/MyAccount/AJAX?method=freezeHold&holdId=' + holdId;
				$.getJSON(url, function(data){
					if (data.result) {
						VuFind.showMessage("Success", data.message, true, true);
					} else {
						VuFind.showMessage("Error", data.message);
					}
				});
			}
		},

// called by ReactivationDateForm when fn freezeHold above has promptForReactivationDate is set
		doFreezeHoldWithReactivationDate: function(caller){
			var popUpBoxTitle = $(caller).text() || "Freezing Hold"; // freezing terminology can be customized, so grab text from click button: caller
			var holdId = $("#holdId").val();
			var reactivationDate = $("#reactivationDate").val();
			var url = Globals.path + '/MyAccount/AJAX?method=freezeHold&holdId=' + holdId + '&reactivationDate=' + reactivationDate;
			VuFind.showMessage(popUpBoxTitle, "Updating your hold.  This may take a minute.");
			$.getJSON(url, function(data){
				if (data.result) {
					VuFind.showMessage("Success", data.message, true, true);
				} else {
					VuFind.showMessage("Error", data.message);
				}
			});
		},

		freezeSelectedHolds: function (){
			//TODO: simplified, should be same functionality, double check. plb 5-29-2015
			var selectedTitles = this.getSelectedTitles();
			if (selectedTitles.length == 0){
				return false;
			}
			var suspendDate = '',
					suspendDateTop = $('#suspendDateTop'),
					url = '',
					queryParams = '';
			if (suspendDateTop.length) { //Check to see whether or not we are using a suspend date.
				if (suspendDateTop.val().length > 0) {
					suspendDate = suspendDateTop.val();
				} else {
					suspendDate = $('#suspendDateBottom').val();
				}
				if (suspendDate.length == 0) {
					alert("Please select the date when the hold should be reactivated.");
					return false;
				}
			}
			url = Globals.path + '/MyAccount/Holds?multiAction=freezeSelected&' + selectedTitles + '&suspendDate=' + suspendDate;
			queryParams = VuFind.getQuerystringParameters();
			if ($.inArray('section', queryParams)){
				url += '&section=' + queryParams['section'];
			}
			window.location = url;
			return false;
		},


		getSelectedTitles: function(promptForSelectAll){
			if (promptForSelectAll == undefined){
				promptForSelectAll = true;
			}
			var selectedTitles = $("input.titleSelect:checked ");
			if (selectedTitles.length == 0 && promptForSelectAll && confirm('You have not selected any items, process all items?')) {
				selectedTitles = $("input.titleSelect")
					.attr('checked', 'checked');
			}
			var queryString = selectedTitles.map(function() {
				return $(this).attr('name') + "=" + $(this).val();
			}).get().join("&");

			return queryString;
		},

		/* old version, in case I broke something. plb 2-3-2015
		getSelectedTitles: function(promptForSelectAll){
			if (promptForSelectAll == undefined){
				promptForSelectAll = true;
			}
			var selectedTitles = $("input.titleSelect:checked ").map(function() {
				return $(this).attr('name') + "=" + $(this).val();
			}).get().join("&");
			if (selectedTitles.length == 0 && promptForSelectAll){
				var ret = confirm('You have not selected any items, process all items?');
				if (ret == true){
					var titleSelect = $("input.titleSelect");
					titleSelect.attr('checked', 'checked');
					selectedTitles = titleSelect.map(function() {
						return $(this).attr('name') + "=" + $(this).val();
					}).get().join("&");
				}
			}
			return selectedTitles;
		},*/
		saveSearch: function(searchId){
			if (!Globals.loggedIn){
				VuFind.Account.ajaxLogin(null, function () {
					VuFind.Searches.saveSearch(searchId);
				}, false);
			}else{
				var url = Globals.path + "/MyAccount/AJAX";
				var params = {method :'saveSearch', searchId :searchId};
				//$.getJSON(url + '?' + params,
				$.getJSON(url, params,
						function(data) {
							if (data.result) {
								VuFind.showMessage("Success", data.message);
							} else {
								VuFind.showMessage("Error", data.message);
							}
						}
				);
			}
			return false;
		},

		showCreateListForm: function(id){
			if (Globals.loggedIn){
				var modalDialog = $("#modalDialog");
				//$(".modal-body").html($('#userreview' + id).html());
				//var url = Globals.path + "/MyAccount/AJAX?method=getCreateListForm";
				//if (id != undefined){
				//	url += '&recordId=' + encodeURIComponent(id);
				//}
				var url = Globals.path + "/MyAccount/AJAX",
						params = {method:"getCreateListForm"};
				if (id != undefined){
					params.recordId= id;
				}
				$.getJSON(url, params, function(data){
					$('#myModalLabel').html(data.title);
					$('.modal-body').html(data.modalBody);
					$('.modal-buttons').html(data.modalButtons);
				});
				//modalDialog.load( );
				modalDialog.modal('show');
			}else{
				VuFind.Account.ajaxLogin($trigger, function (){
					return VuFind.GroupedWork.showEmailForm(trigger, id);
				}, false);
			}
			return false;
		},

		thawHold: function(holdId, caller){
			var popUpBoxTitle = $(caller).text() || "Thawing Hold";  // freezing terminology can be customized, so grab text from click button: caller
			VuFind.showMessage(popUpBoxTitle, "Updating your hold.  This may take a minute.");
			var url = Globals.path + '/MyAccount/AJAX?method=thawHold&holdId=' + holdId;
			$.getJSON(url, function(data){
				if (data.result) {
					VuFind.showMessage("Success", data.message, true, true);
				} else {
					VuFind.showMessage("Error", data.message);
				}
			});
		}

	};
}(VuFind.Account || {}));