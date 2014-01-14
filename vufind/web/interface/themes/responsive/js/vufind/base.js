/**
 * Created by mark on 1/14/14.
 */
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
			var titleSelect = $("input.titleSelect");
			titleSelect.attr('checked', 'checked');
			selectedTitles = titleSelect.map(function() {
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
};

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

VuFind.showElementInPopup = function(title, elementId){
	VuFind.closeLightbox();
	$("#modal-title").html(title);
	var elementText = $(elementId).html();
	$(".modal-body").html(elementText);
	var modalDialog = $("#modalDialog");
	modalDialog.modal('show');
};

VuFind.closeLightbox = function(){
	var modalDialog = $("#modalDialog");
	if (modalDialog.is(":visible")){
		modalDialog.modal('hide');
		$(".modal-backdrop").remove();
	}
};