var VuFind = (function(){
	$(document).ready(function(){
		VuFind.initializeModalDialogs();
		VuFind.setupFieldSetToggles();
		VuFind.initCarousels();

		$("#modalDialog").modal({show:false});

		var panels = $('.panel');
		panels.on('show.bs.collapse', function () {
			$(this).addClass('active');
		});

		panels.on('hide.bs.collapse', function () {
			$(this).removeClass('active');
		});
	});
	/**
	 * Created by mark on 1/14/14.
	 */
	return {
		changePageSize: function(){
			var url = window.location.href;
			if (url.match(/[&?]pagesize=\d+/)) {
				url = url.replace(/pagesize=\d+/, "pagesize=" + $("#pagesize").val());
			} else {
				if (url.indexOf("?", 0) > 0){
					url = url+ "&pagesize=" + $("#pagesize").val();
				}else{
					url = url+ "?pagesize=" + $("#pagesize").val();
				}
			}
			window.location.href = url;
		},

		closeLightbox: function(callback){
			var modalDialog = $("#modalDialog");
			if (modalDialog.is(":visible")){
				modalDialog.modal('hide');
				if (callback != undefined){
					var closeLightboxListener = modalDialog.on('hidden.bs.modal', function (e) {
						modalDialog.off('hidden.bs.modal');
						callback();
					});
				}
			}
		},

		initCarousels:function(){
			var jcarousel = $('.jcarousel');

			jcarousel.on('jcarousel:reload jcarousel:create', function () {
				var element = $(this);
				var width = element.innerWidth();
				var itemWidth = width;
				if (width >= 600) {
					itemWidth = width / 4;
				}else if (width >= 400) {
					itemWidth = width / 3;
				}else if (width >= 300) {
					itemWidth = width / 2;
				}

				element.jcarousel('items').css('width', Math.floor(itemWidth) + 'px');
			})
			.jcarousel({
				wrap: 'circular'
			});

			$('.jcarousel-control-prev')
					.jcarouselControl({
						target: '-=1'
					});

			$('.jcarousel-control-next')
					.jcarouselControl({
						target: '+=1'
					});

			$('.jcarousel-pagination')
					.on('jcarouselpagination:active', 'a', function() {
						$(this).addClass('active');
					})
					.on('jcarouselpagination:inactive', 'a', function() {
						$(this).removeClass('active');
					})
					.on('click', function(e) {
						e.preventDefault();
					})
					.jcarouselPagination({
						perPage: 1,
						item: function(page) {
							return '<a href="#' + page + '">' + page + '</a>';
						}
					});

			// If Browse Category js is set, initialize those functions
			if (typeof VuFind.Browse.initializeBrowseCategory == 'function') {
				VuFind.Browse.initializeBrowseCategory(); }
		},

		initializeModalDialogs: function() {
			$(".modalDialogTrigger").each(function(){
				$(this).click(function(){
					var trigger = $(this);
					var dialogTitle = trigger.attr("title") ? trigger.attr("title") : trigger.data("title");
					var dialogDestination = trigger.attr("href");
					$("#myModalLabel").text(dialogTitle);
					$(".modal-body").load(dialogDestination);
					$(".extraModalButton").hide();
					$("#modalDialog").modal("show");
					return false;
				});
			});
		},

		getQuerystringParameters: function(){
			var vars = [],
					q = location.search.substr(1);
			if(q != undefined){
				q = q.split('&');
				for(var i = 0; i < q.length; i++){
					var hash = q[i].split('=');
					vars[hash[0]] = hash[1];
				}
			}
			return vars;
		},

		//// Quick Way to get a single URL parameter value (parameterName must be in the url query string)
		//getQueryParameterValue: function (parameterName) {
		//	return location.search.split(parameterName + '=')[1].split('&')[0]
		//},

		getSelectedTitles: function(){
			var selectedTitles = $("input.titleSelect:checked ").map(function() {
				return $(this).attr('name') + "=" + $(this).val();
			}).get().join("&");
			if (selectedTitles.length == 0){
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
		},

		pwdToText: function(fieldId){
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
		},

		setupFieldSetToggles: function (){
			$('legend.collapsible').each(function(){
				$(this).siblings().hide()
				.addClass("collapsed")
				.click(function() {
					$(this).toggleClass("expanded collapsed")
					.siblings().slideToggle();
				//$(this).siblings().hide();
				//$(this).addClass("collapsed");
				//$(this).click(function() {
				//	$(this).toggleClass("expanded");
				//	$(this).toggleClass("collapsed");
				//	$(this).siblings().slideToggle();
					return false;
				});
			});

			$('fieldset.fieldset-collapsible').each(function() {
				var collapsible = $(this);
				var legend = collapsible.find('legend:first');
				legend.addClass('fieldset-collapsible-label').bind('click', {collapsible: collapsible}, function(event) {
					var collapsible = event.data.collapsible;
					if (collapsible.hasClass('fieldset-collapsed')) {
						collapsible.removeClass('fieldset-collapsed');
					} else {
						collapsible.addClass('fieldset-collapsed');
					}
				});
				// Init.
				collapsible.addClass('fieldset-collapsed');
			});
		},

		showMessage: function(title, body, autoClose, refreshAfterClose){
			// if autoclose is set as number greater than 1 autoClose will be the custom timeout interval in milliseconds, otherwise
			//     autoclose is treated as an on/off switch. Default timeout interval of 3 seconds.
			// if refreshAfterClose is set but not autoClose, the page will reload when the box is closed by the user.
			if (autoClose == undefined){
				autoClose = false;
			}
			if (refreshAfterClose == undefined){
				refreshAfterClose = false;
			}
			$("#myModalLabel").html(title);
			$(".modal-body").html(body);
			$('.modal-buttons').html('');
			var modalDialog = $("#modalDialog");
			modalDialog.modal('show');
			if (autoClose) {
				setTimeout(function(){
							if (refreshAfterClose) location.reload(true);
							else VuFind.closeLightbox();
						}
						, autoClose > 1 ? autoClose : 3000);
			}else if (refreshAfterClose) {
				modalDialog.on('hide.bs.modal', function(){
					location.reload(true)
				})
			}
			//
			//if (autoClose && refreshAfterClose){
			//	setTimeout(function(){
			//		location.reload(true);
			//	}
			//	, autoClose > 1 ? autoClose : 3000);
			//}else if (autoClose) {
			//	setTimeout(function(){
			//		VuFind.closeLightbox();
			//	}
			//	, autoClose > 1 ? autoClose : 3000);
			//}
		},

		showMessageWithButtons: function(title, body, buttons){
			$("#myModalLabel").html(title);
			$(".modal-body").html(body);
			$('.modal-buttons').html(buttons);
			var modalDialog = $("#modalDialog");
			modalDialog.modal('show');
		},

		toggleHiddenElementWithButton: function(button){
			var hiddenElementName = $(button).data('hidden_element');
			var hiddenElement = $(hiddenElementName);
			hiddenElement.val($(button).hasClass('active') ? '1' : '0');
			return false;
		},

		showElementInPopup: function(title, elementId){
			var modalDialog = $("#modalDialog");
			if (modalDialog.is(":visible")){
				VuFind.closeLightbox(function(){VuFind.showElementInPopup(title, elementId)});
			}else{
				$(".modal-title").html(title);
				var elementText = $(elementId).html();
				$(".modal-body").html(elementText);
				var modalDialog = $("#modalDialog");
				modalDialog.modal('show');
				return false;
			}
		},

		showLocationHoursAndMap: function(){
			var selectedId = $("#selectLibrary").find(":selected").val();
			$(".locationInfo").hide();
			$("#locationAddress" + selectedId).show();
			return false;
		},

		toggleCheckboxes: function (checkboxSelector, toggleSelector){
			var toggle = $(toggleSelector);
			var value = toggle.prop('checked');
			$(checkboxSelector).prop('checked', value);
		},

		submitOnEnter: function(event, formToSubmit){
			if (event.keyCode == 13){
				$(formToSubmit).submit();
			}
		},

		hasLocalStorage: function () {
			// arguments.callee.haslocalStorage is the function's "static" variable for whether or not we have tested the
			// that the localStorage system is available to us.

			//console.log(typeof arguments.callee.haslocalStorage);
			if(typeof arguments.callee.haslocalStorage == "undefined") {
				if ("localStorage" in window) {
					try {
						window.localStorage.setItem('_tmptest', 'temp');
						arguments.callee.haslocalStorage = (window.localStorage.getItem('_tmptest') == 'temp');
						// if we get the same info back, we are good. Otherwise, we don't have localStorage.
						window.localStorage.removeItem('_tmptest');
					} catch(error) { // something failed, so we don't have localStorage available.
						arguments.callee.haslocalStorage = false;
					}
				} else arguments.callee.haslocalStorage = false;
			}
			return arguments.callee.haslocalStorage;
		}
	}

}(VuFind || {}));

jQuery.validator.addMethod("multiemail", function (value, element) {
	if (this.optional(element)) {
		return true;
	}
	var emails = value.split(/[,;]/),
			valid = true;
	for (var i = 0, limit = emails.length; i < limit; i++) {
		value = emails[i];
		valid = valid && jQuery.validator.methods.email.call(this, value, element);
	}
	return valid;
}, "Invalid email format: please use a comma to separate multiple email addresses.");

/**
 *  Modified from above code, for Pika self registration form.
 *
 * Return true, if the value is a valid date, also making this formal check mm-dd-yyyy.
 *
 * @example jQuery.validator.methods.date("01-01-1900")
 * @result true
 *
 * @example jQuery.validator.methods.date("01-13-1990")
 * @result false
 *
 * @example jQuery.validator.methods.date("01.01.1900")
 * @result false
 *
 * @example <input name="pippo" class="{datePika:true}" />
 * @desc Declares an optional input element whose value must be a valid date.
 *
 * @name jQuery.validator.methods.datePika
 * @type Boolean
 * @cat Plugins/Validate/Methods
 */
jQuery.validator.addMethod(
		"datePika",
		function(value, element) {
			var check = false;
			var re = /^\d{1,2}(-)\d{1,2}(-)\d{4}$/;
			if( re.test(value)){
				var adata = value.split('-');
				var mm = parseInt(adata[0],10);
				var dd = parseInt(adata[1],10);
				var aaaa = parseInt(adata[2],10);
				var xdata = new Date(aaaa,mm-1,dd);
				if ( ( xdata.getFullYear() == aaaa ) && ( xdata.getMonth () == mm - 1 ) && ( xdata.getDate() == dd ) )
					check = true;
				else
					check = false;
			} else
				check = false;
			return this.optional(element) || check;
		},
		"Please enter a correct date"
);

