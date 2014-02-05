/**
 * Created by mark on 1/14/14.
 */
VuFind.Account = (function(){

	return {
		ajaxCallback: null,
		closeModalOnAjaxSuccess: false,

		/**
		 * Creates a new list in the system for the active user.
		 *
		 * Called from list-form.tpl
		 * @returns {boolean}
		 */
		addList: function () {
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
				url: url + '?' + params,
				dataType: "json",
				success: function (data) {
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
				error: function () {
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
		ajaxLogin: function (trigger, ajaxCallback, closeModalOnAjaxSuccess) {
			if (Globals.loggedIn) {
				if (ajaxCallback != undefined && typeof(ajaxCallback) === "function") {
					ajaxCallback();
				} else if (VuFind.Account.ajaxCallback != null && typeof(VuFind.Account.ajaxCallback) === "function") {
					VuFind.Account.ajaxCallback();
					VuFind.Account.ajaxCallback = null;
				}
			} else {
				VuFind.Account.ajaxCallback = ajaxCallback;
				VuFind.Account.closeModalOnAjaxSuccess = closeModalOnAjaxSuccess;
				if (trigger != undefined && trigger != null) {
					var dialogTitle = trigger.attr("title") ? trigger.attr("title") : trigger.data("title");
				}
				var dialogDestination = Globals.path + '/MyResearch/AJAX?method=LoginForm';
				var modalDialog = $("#modalDialog");
				var modalBody = $(".modal-content");
				modalBody.load(dialogDestination);
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

		processAjaxLogin: function (ajaxCallback) {
			var username = $("#username").val();
			var password = $("#password").val();
			var rememberMe = $("#rememberMe").val();
			var loginErrorElem = $('#loginError');
			if (!username || !password) {
				loginErrorElem.text("Please enter both your name and library card number");
				loginErrorElem.show();
				return false;
			}
			loginErrorElem.hide();
			var url = Globals.path + "/AJAX/JSON?method=loginUser";
			$.ajax({url: url,
				data: {username: username, password: password, rememberMe: rememberMe},
				success: function (response) {
					if (response.result.success == true) {
						// Hide "log in" options and show "log out" options:
						$('.loginOptions').hide();
						$('.logoutOptions').show();
						$('#loginOptions').hide();
						$('#logoutOptions').show();
						$('#myAccountNameLink').html(response.result.name);
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
					loginErrorElem.text("There was an error processing your login, please try again.");
					loginErrorElem.show();
				},
				dataType: 'json',
				type: 'post'
			});

			return false;
		},

		renewSelectedTitles: function () {
			var selectedTitles = VuFind.getSelectedTitles();
			if (selectedTitles.length == 0) {
				return false;
			}
			$('#renewForm').submit();
			return false;
		},

		ajaxLightbox: function (urlToDisplay, requireLogin) {
			if (requireLogin == undefined) {
				requireLogin = false;
			}
			if (requireLogin && !Globals.loggedIn) {
				ajaxLogin(null, function () {
					ajaxLightbox(urlToDisplay, requireLogin);
				}, false);
			} else {
				closeLightbox();
				$(".modal-body").html("Loading");
				var modalDialog = $("#modalDialog");
				modalDialog.load(urlToDisplay, function () {
					modalDialog.modal('show');
				});
			}
			return false;
		}

	};
}(VuFind.Account || {}));