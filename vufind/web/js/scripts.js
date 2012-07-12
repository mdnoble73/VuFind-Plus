$(document).ready(function(){
	if($("#searchForm") != null && $("#lookfor") != null){
		$("#lookfor").focus();
	}
	if($("#loginForm") != null){
		$("#username").focus();
	}
});

function getLightbox(module, action, id, lookfor, message, followupModule,
		followupAction, followupId, left, width, top, height) {
	// Optional parameters
	if (followupModule === undefined) {
		followupModule = '';
	}
	if (followupAction === undefined) {
		followupAction = '';
	}
	if (followupId === undefined) {
		followupId = '';
	}

	if ((module == '') || (action == '')) {
		hideLightbox();
		return 0;
	}

	// Popup Lightbox
	lightbox(left, width, top, height);

	// Load Popup Box Content from AJAX Server
	var url = path + "/AJAX/Home";
	var params = 'method=GetLightbox' + '&lightbox=true' + '&submodule='
			+ encodeURIComponent(module) + '&subaction=' + encodeURIComponent(action)
			+ '&id=' + encodeURIComponent(id) + '&lookfor='
			+ encodeURIComponent(lookfor) + '&message=' + encodeURIComponent(message)
			+ '&followupModule=' + encodeURIComponent(followupModule)
			+ '&followupAction=' + encodeURIComponent(followupAction)
			+ '&followupId=' + encodeURIComponent(followupId);
	
	$.ajax({
		url: url + '?' + params,
		success : function(data) {
			if (data && data.result) {
				if (data.redirect && data.redirect.length() > 0) {
					window.location = data.redirect;
				} else {
					$('$popupbox').innerHTML = data.result;

					// set focus to the default location
					if (document.loginForm != null) {
						document.loginForm.username.focus();
					}
				}
			} else {
				document.getElementById('popupbox').innerHTML = document
						.getElementById('lightboxError').innerHTML;
			}

			// Check to see if an element within the lightbox needs to be given focus.
			// Note that we need to introduce a slight delay before taking focus due
			// to IE sensitivity.
			var focusIt = function() {
				var o = document.getElementById('mainFocus');
				if (o) {
					o.focus();
				}
			}
			setTimeout(focusIt, 250);

		},
		error : function() {
			document.getElementById('popupbox').innerHTML = document
					.getElementById('lightboxError').innerHTML;
		}
	});
}

function SaltedLogin(elems, module, action, id, lookfor, message) {
	// Load Popup Box Content from AJAX Server
	var url = path + "/AJAX/Home";
	var params = 'method=GetSalt';
	$.ajax({
		url: url + '?' + params,
		success : function(transaction) {
			var response = transaction.responseXML.documentElement;
			if (response.getElementsByTagName('result')) {
				Login(
						elems,
						response.getElementsByTagName('result').item(0).firstChild.nodeValue,
						module, action, id, lookfor, message);

			}
		}
	})
}

function Login(elems, salt, module, action, id, lookfor, message) {
	var url = path + "/AJAX/JSON?method=loginUser"
	$.ajax( {
		url : url,
		data : {
			username : $('#username').val(),
			password : $('#password').val()
		},
		success : function(response) {
			if (response.result.success == true) {
				// Update the main display to show the user is logged in
				// Hide "log in" options and show "log out" options:
				$('.loginOptions').hide();
				$('.logoutOptions').show();
				$('#myAccountNameLink').html(response.result.name);
				// Update user save statuses if the current context calls for it:
				if (typeof (doGetSaveStatuses) == 'function') {
					doGetSaveStatuses();
				} else if (typeof (redrawSaveStatus) == 'function') {
					redrawSaveStatus();
				}

				// Load the post-login action:
				getLightbox(module, action, id, lookfor, message);

			} else {
				alert("That login was not recognized.  Please try again.");
			}
		},
		dataType : 'json',
		type : 'post'
	});

}

function lightbox(left, width, top, height){
	if (!left) left = '100px';
	if (!top) top = '100px';
	if (!width) width = 'auto';
	if (!height) height = 'auto';
	
	var loadMsg = $('#lightboxLoading').html();

	$('#popupbox').html('<div class="lightboxLoadingContents"><div class="lightboxLoadingMessage">' + loadMsg + '</div><img src="' + path + '/images/loading_bar.gif" class="lightboxLoadingImage"/></div>');
   
	hideSelects('hidden');

	// Find out how far down the screen the user has scrolled.
	var new_top =  document.body.scrollTop;

	// Get the height of the document
	var documentHeight = $(document).height();

	$('#lightbox').show();
	$('#lightbox').css('height', documentHeight + 'px');

	$('#popupbox').show();
	$('#popupbox').css('top', top);
	$('#popupbox').css('left', left);
	$('#popupbox').css('width', width);
	$('#popupbox').css('height', height);
}

function ajaxLightbox(urlToLoad, parentId, left, width, top, height){
	
	var loadMsg = $('#lightboxLoading').html();

	hideSelects('hidden');

	// Find out how far down the screen the user has scrolled.
	var new_top =  document.body.scrollTop;

	// Get the height of the document
	var documentHeight = $(document).height();

	$('#lightbox').show();
	$('#lightbox').css('height', documentHeight + 'px');
	
	$('#popupbox').html('<img src="' + path + '/images/loading.gif" /><br />' + loadMsg);
	$('#popupbox').show();
	$('#popupbox').css('top', '50%');
	$('#popupbox').css('left', '50%');
	
	$.get(urlToLoad, function(data) {
		$('#popupbox').html(data);
		
		$('#popupbox').show();
		if (parentId){
			//Automatically position the lightbox over the cursor
			$("#popupbox").position({
				my: "top right",
				at: "top right",
				of: parentId,
				collision: "flip"
			});
		}else{
			if (!left) left = '100px';
			if (!top) top = '100px';
			if (!width) width = 'auto';
			if (!height) height = 'auto';
			
			$('#popupbox').css('top', top);
			$('#popupbox').css('left', left);
			$('#popupbox').css('width', width);
			$('#popupbox').css('height', height);
			
			$(document).scrollTop(0);
		}
		if ($("#popupboxHeader").length > 0){
			$("#popupbox").draggable({ handle: "#popupboxHeader" });
		}
	});
}

function showElementInLightbox(title, elementSelector){
	// Find out how far down the screen the user has scrolled.
	var new_top =  document.body.scrollTop;

	// Get the height of the document
	var documentHeight = $(document).height();

	$('#lightbox').show();
	$('#lightbox').css('height', documentHeight + 'px');

	$('#popupbox').show();
	$('#popupbox').css('top', '100px');
	$('#popupbox').css('left', '100px');
	$('#popupbox').css('width', 'auto');
	$('#popupbox').css('height', 'auto');
	
	var lightboxContents = "<div class='header'>" + title + "<a href='#' onclick='hideLightbox();return false;' class='closeIcon'>Close <img src='" + path + "/images/silk/cancel.png' alt='close' /></a></div>";
	lightboxContents += "<div class='content'>" + $(elementSelector).html() + "</div>";
	
	$('#popupbox').html(lightboxContents);
	
}

function hideLightbox(){
	var lightbox = $('#lightbox');
	var popupbox = $('#popupbox');

	hideSelects('visible');
	lightbox.hide();
	popupbox.hide();
}

function hideSelects(visibility)
{
	selects = document.getElementsByTagName('select');
	for(i = 0; i < selects.length; i++) {
		selects[i].style.visibility = visibility;
	}
}

function toggleMenu(elemId){
	var o = document.getElementById(elemId);
	o.style.display = o.style.display == 'block' ? 'none' : 'block';
}

function getElem(id)
{
    if (document.getElementById) {
        return document.getElementById(id);
    } else if (document.all) {
        return document.all[id];
    }
}

function filterAll(element)
{
    // Go through all elements
    var e = getElem('searchForm').elements;
    var len = e.length;
    for (var i = 0; i < len; i++) {
        // Look for filters (specifically checkbox filters)
        if (e[i].name == 'filter[]' && e[i].checked != undefined) {
            e[i].checked = element.checked;
        }
    }
}

function jsEntityEncode(str)
{
    var new_str = str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    return new_str;
}

/*
 * Function to check if user is logged in. It expects a function as an argument,
 * to which a value of TRUE or FALSE will be supplied, once it comes back from
 * the server
 */
function isLoggedIn(logged_in_function) {
	var url = path + '/AJAX/Home?method=isLoggedIn';
	
	$.get(url, function(response) {
		var logged_in = $(response).find('result').text();
		if (logged_in == "1") {
			logged_in = true;
		}else{
			logged_in = false;
		}
		logged_in_function(logged_in);
	});
	return false; 
}

/*
 * Function to check if user is logged in. Runs Synchronously and returns the
 * value of whether it is logged in or not.
 */
function isLoggedInSync() {
	var url = path + '/AJAX/Home?method=isLoggedIn';
	
	var response = $.ajax({
		url: url,
		async: false,
		cache: false
	}).responseText;
	
	var logged_in = $(response).find('result').text();
	if (logged_in == "1") {
		logged_in = true;
	}else{
		logged_in = false;
	}

	return logged_in; 
}

/* update the sort parameter and redirect the user back to the same page */
function changeSort(newSort){
	// Get the current url
	var currentLocation = window.location.href;
	// Check to see if we already have a sort parameter. .
	if (currentLocation.match(/(sort=[^&]*)/)) {
		// Replace the existing sort with the new sort parameter
		currentLocation = currentLocation.replace(/sort=[^&]*/, 'sort=' + newSort);
	} else {
		// Add the new sort parameter
		if (currentLocation.match(/\?/)) {
			currentLocation += "&sort=" + newSort;
		}else{
			currentLocation += "?sort=" + newSort;
		}
	}
	// Redirect back to this page.
	window.location.href = currentLocation;
}


/* update the sort parameter and redirect the user back to the same page */
function changeAccountSort(newSort){
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
}

function checkAll(){
	
	for (var i=0;i<document.forms[1].elements.length;i++)
	{
		var e=document.forms[1].elements[i];
		var cbName = e.name;
		
		if (document.forms[1].elements[i].type == 'checkbox')
		{
			if (cbName.substring(0,8)== 'selected')
			{
				e.checked = document.forms[1].selectAll.checked;
			}
		}
	}
}
function enableSearchTypes(){
	var searchSource = $("#searchSource");
	if (searchSource.val() != 'genealogy'){
		$("#basicSearchTypes").show();
		$("#genealogySearchTypes").hide();
	}else{
		$("#genealogySearchTypes").show();
		$("#basicSearchTypes").hide();
	}
}

function startSearch(){
	// Stop auto complete since there is a search running already
	$('#lookfor').autocomplete( "disable" );
}


function returnEpub(returnUrl){
  $.getJSON(returnUrl, function (data){
    if (data.success == false){
      alert("Error returning EPUB file\r\n" + data.message);
    }else{
      alert("The file was returned successfully.");
      window.location.reload();
    }
    
  });
}

function cancelEContentHold(cancelUrl){
	$.getJSON(cancelUrl, function (data){
    if (data.result == false){
      alert("Error cancelling hold.\r\n" + data.message);
    }else{
      alert(data.message);
      window.location.reload();
    }
    
  });
}

function reactivateEContentHold(reactivateUrl){
	$.getJSON(reactivateUrl, function (data){
    if (data.error){
      alert("Error reactivating hold.\r\n" + data.error);
    }else{
      alert("The hold was activated successfully.");
      window.location.reload();
    }
    
  });
}

function getOverDriveSummary(){
	$.getJSON(path + '/MyResearch/AJAX?method=getOverDriveSummary', function (data){
		if (data.error){
			// Unable to load overdrive summary
		}else{
			// Load checked out items
			$("#checkedOutItemsOverDrivePlaceholder").html(data.numCheckedOut);
			// Load available holds
			$("#availableHoldsOverDrivePlaceholder").html(data.numAvailableHolds);
			// Load unavailable holds
			$("#unavailableHoldsOverDrivePlaceholder").html(data.numUnavailableHolds);
			// Load wishlist
			$("#wishlistOverDrivePlaceholder").html(data.numWishlistItems);
		}
	});
}

var ajaxCallback = null;
function ajaxLogin(callback){
	ajaxCallback = callback;
	ajaxLightbox(path + '/MyResearch/AJAX?method=LoginForm');
}

function processAjaxLogin(){
	var username = $("#username").val();
	var password = $("#password").val();
	if (!username || !password){
		alert("Please enter both the username and password");
		return false;
	}
	var url = path + "/AJAX/JSON?method=loginUser"
	$.ajax({url: url,
			data: {username: username, password: password},
			success: function(response){
				if (response.result.success == true){
					loggedIn = true;
					// Hide "log in" options and show "log out" options:
					$('.loginOptions').hide();
					$('.logoutOptions').show();
					$('#myAccountNameLink').html(response.result.name);
					hideLightbox();
					if (ajaxCallback  && typeof(ajaxCallback) === "function"){
						ajaxCallback();
					}
				}else{
					alert("That login information was not recognized.  Please try again.");
				}
			},
			error: function(){
				alert("There was an error processing your login, please try again.");
			},
			dataType: 'json',
			type: 'post' 
	});
	
	return false;
}

function showProcessingIndicator(message){
	if (message != undefined){
		$('#lightboxLoading').html(message);
	}
	lightbox();
}

function searchSubmit(){
	// Stop auto complete since there is a search running already
	$('#lookfor').autocomplete( "disable" );
	
	document.forms.searchForm.action='/Union/Search'
	document.forms.searchForm.submit();
}

function setupFieldsetToggles(){
	$('legend.collapsible').siblings().hide();
	$('legend.collapsible').addClass("collapsed");
	$('legend.collapsible').click(function() {
		$(this).toggleClass("expanded");
		$(this).toggleClass("collapsed");
		$(this).siblings().slideToggle();
		return false;
	});
}

function pwdToText(fieldId){
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
}

function toggleCheckboxes(checkboxSelector, value){
	if (value == undefined){
		$(checkboxSelector).removeAttr('checked');
	}else{
		$(checkboxSelector).attr('checked', value);
	}
}

/**
 * Login function for logging in while the user is adding a rating.
 * @param id
 * @param rating
 * @param module
 */
function ratingLogin(id, rating, module) {
	var url = path + "/AJAX/JSON?method=loginUser"
	$.ajax( {
		url : url,
		data : {
			username : $('#username').val(),
			password : $('#password').val()
		},
		success : function(response) {
			if (response.result.success == true) {
				// Update the main display to show the user is logged in
				// Hide "log in" options and show "log out" options:
				$('.loginOptions').hide();
				$('.logoutOptions').show();
				$('#myAccountNameLink').html(response.result.name);
				
				// update the rating in the database
				$.get(path + "/" + module + "/" + id + "/Rate?rating=" + rating + "&submit=true", function() {
					window.location.reload(true);
				});
			} else {
				alert("That login was not recognized.  Please try again.");
			}
		},
		dataType : 'json',
		type : 'post'
	});
}

/* Setup autocomplete for search box */
try{
	$(document).ready(
	function() {
		try{
			if ($("#lookfor").length==1){
				$("#lookfor").autocomplete({
					source: function(request, response){
						var url = path + "/Search/AJAX?method=GetAutoSuggestList&type=" + $("#type").val() + "&searchTerm=" +  $("#lookfor").val();
						$.ajax({
							url: url,
							dataType: "json",
							success: function(data){
								response(data);
							}
						});
					},
					position: {
						my: "left top",
						at: "left bottom",
						of: "#lookfor",
						collision: "fit"
					},
					minLength: 4,
					delay: 600
				});
			}
		} catch (e) {
			alert("error during autocomplete setup" + e);
		}
	});
} catch (e) {
	alert("error during autocomplete setup" + e);
}

/**
 * Attaches a description popup to an element and handles the ajax call to fetch the description 
 * @param shortid
 * @param id
 * @param type
 */
function resultDescription(shortid,id, type){
  //Attach the tooltip function to the HTML element with the id pretty + short record id
  //this will show the description when the user hovers over the element. 
var divId = "#descriptionTrigger" + shortid;
if (type == undefined){
	type = 'VuFind';
}
if (type == 'VuFind'){
	var loadDescription = path + "/Record/" + id + "/AJAX/?method=getDescription";
}else{
	var loadDescription = path + "/EcontentRecord/" + id + "/AJAX/?method=getDescription";
}
$(divId).tooltip({
	  track: false,
	  delay: 250,
	  showURL: false,
	  extraClass: "descriptionTooltip",
	  top:0,
	  bodyHandler: function() {
		if ($("#descriptionPlaceholder" + shortid).hasClass('loaded')){
			return $("#descriptionPlaceholder" + shortid).html();
		}else{
		  $("#descriptionPlaceholder" + shortid).addClass('loaded');
		  var rawData = $.ajax(loadDescription,{
			  async: false
		  }).responseText;
		  var xmlDoc = $.parseXML(rawData);
		  var data = $(xmlDoc);
		  //parses the xml and sets variables to call later
		  var descriptAjax = data.find('description').text();
		  var lengthAjax = data.find('length').text();
		  var publishAjax =data.find('publisher').text();
		  var toolTip = "<h3>Description</h3> <div class='description-element'>" + descriptAjax + "</div><div class='description-element'><div class='description-element-label'>Length: </div>" + lengthAjax + "</div><div class='description-element'><div class='description-element-label'>Publisher: </div>" + publishAjax + "</div>";
		  $("#descriptionPlaceholder" + shortid).html(toolTip);
		  return toolTip;
		}
	  }
});
};

/* This file contains AJAX routines that are shared by multiple VuFind modules.
 */

/*
 * Create a new list for storing favorites:
 */
function addList(form, failMsg)
{
	for (var i = 0; i < form.public.length; i++) {
		if (form.public[i].checked) {
			var isPublic = form.public[i].value;
		}
	}

	var url = path + "/MyResearch/AJAX";
	var recordId = form.recordId.value;
	var source = form.source.value;
	var params = "method=AddList&" +
							 "title=" + encodeURIComponent(form.title.value) + "&" +
							 "public=" + isPublic + "&" +
							 "desc=" + encodeURIComponent(form.desc.value) + "&" +
							 "followupModule=" + form.followupModule.value + "&" +
							 "followupAction=" + form.followupAction.value + "&" +
							 "followupId=" + form.followupId.value;

	$.ajax({
		url: url+'?'+params,
		dataType: "json",
		success: function(data) {
			var value = data.result;
			if (value) {
				if (value == "Done") {
					var newId = data.newId;
					//Save the record to the list
					var url = path + "/Resource/Save?lightbox=true&selectedList=" + newId + "&id=" + recordId + "&source=" + source;
					ajaxLightbox(url);
				} else {
					alert(value.length > 0 ? value : failMsg);
				}
			} else {
				$('#popupbox').html(failMsg);
				setTimeout("hideLightbox();", 3000);
			}
		},
		error: function() {
			$('#popupbox').html(failMsg);
			setTimeout("hideLightbox();", 3000);
		}
	});
}

/*
 * Given a base URL and a set of parameters, use AJAX to send an email; this
 * assumes that a lightbox is already open.
 */
function sendAJAXEmail(url, params, strings){
	$('#popupbox').html('<h3>' + strings.sending + '</h3>');

	$.ajax({
		url: url+'?'+params,
		success: function(data) {
			var value = $(data).find('result');
			if (value) {
					if (value.text() == "Done") {
							document.getElementById('popupbox').innerHTML = '<h3>' + strings.success + '</h3>';
							setTimeout("hideLightbox();", 3000);
					} else {
							var errorDetails = data.details;
							document.getElementById('popupbox').innerHTML = '<h3>' + strings.failure + '</h3>' +
									(errorDetails ? '<h3>' + errorDetails + '</h3>' : '');
					}
			} else {
					document.getElementById('popupbox').innerHTML = '<h3>' + strings.failure + '</h3>';
			}
		},
		error: function(transaction) {
				document.getElementById('popupbox').innerHTML = strings.failure;
		}
	});
}

/*
 * Send the current URL in an email to a specific address, from a specific
 * address, and including some message text.
 */
function SendURLEmail(to, from, message, strings){
	var url = path + "/Search/AJAX";
	var params = "method=SendEmail&" +
							 "url=" + URLEncode(window.location.href) + "&" +
							 "from=" + encodeURIComponent(from) + "&" +
							 "to=" + encodeURIComponent(to) + "&" +
							 "message=" + encodeURIComponent(message);
	sendAJAXEmail(url, params, strings);
}

function URLEncode(clearString) {
	var output = '';
	var x = 0;
	clearString = clearString.toString();
	var regex = /(^[a-zA-Z0-9_.]*)/;
	while (x < clearString.length) {
			var match = regex.exec(clearString.substr(x));
			if (match != null && match.length > 1 && match[1] != '') {
					output += match[1];
					x += match[1].length;
			} else {
					if (clearString[x] == ' ')
							output += '+';
					else {
							var charCode = clearString.charCodeAt(x);
							var hexVal = charCode.toString(16);
							output += '%' + ( hexVal.length < 2 ? '0' : '' ) + hexVal.toUpperCase();
					}
					x++;
			}
	}
	return output;
}

function sendAJAXSMS(url, params, strings) {
	document.getElementById('popupbox').innerHTML = '<h3>' + strings.sending + '</h3>';

	$.ajax({
		url: url+'?'+params,
		
		success: function(data) {
			var value = $(data).find('result');
			if (value) {
					if (value.text() == "Done") {
							document.getElementById('popupbox').innerHTML = '<h3>' + strings.success + '</h3>';
							setTimeout("hideLightbox();", 3000);
					} else {
							document.getElementById('popupbox').innerHTML = strings.failure;
					}
			} else {
					document.getElementById('popupbox').innerHTML = strings.failure;
			}
		},
		error: function() {
				document.getElementById('popupbox').innerHTML = strings.failure;
		}
	});
}

function moreFacets(name)
{
	document.getElementById("more" + name).style.display="none";
	document.getElementById("narrowGroupHidden_" + name).style.display="block";
}
								
function lessFacets(name)
{
	document.getElementById("more" + name).style.display="block";
	document.getElementById("narrowGroupHidden_" + name).style.display="none";
}

function getSaveToListForm(id, source){
	if (loggedIn){
		var url = path + "/Resource/Save?lightbox=true&id=" + id + "&source=" + source;
		ajaxLightbox(url);
	}else{
		ajaxLogin(function (){
			getSaveToListForm(id, source);
		});
	}
	return false;
}

function saveRecord(id, source, formElem, strings) {
	successCallback = function() {
		// Highlight the save link to indicate that the content is saved:
		$('#saveLink').addClass('savedFavorite');

		// Redraw tag list:
		GetTags(id, source, 'tagList', strings);
	};
	performSaveRecord(id, source, formElem, strings, 'VuFind', successCallback);
	return false;
}
function performSaveRecord(id, source, formElem, strings, service, successCallback)
{
	document.body.style.cursor = 'wait';
	var tags = formElem.elements['mytags'].value;
	var notes = formElem.elements['notes'].value;
	var list = formElem.elements['list'].options[formElem.elements['list'].selectedIndex].value;

	var url = path + "/Resource/AJAX";
	var params = "method=SaveRecord&" +
							 "mytags=" + encodeURIComponent(tags) + "&" +
							 "list=" + list + "&" +
							 "notes=" + encodeURIComponent(notes) + "&" +
							 "id=" + id + "&" +
							 "source=" + source;
	$.ajax({
		url: url+'?'+params,
		dataType: "json",
		success: function(data) {
			if (data.result) {
					var value = data.result;
					if (value == "Done") {
							successCallback();
							hideLightbox();
					} else {
							getLightbox('Record', 'Save', id, null, strings.add);
					}
			} else {
					document.getElementById('popupbox').innerHTML = strings.error;
					setTimeout("hideLightbox();", 3000);
			}
			document.body.style.cursor = 'default';
			
	},
	error: function() {
			document.getElementById('popupbox').innerHTML = strings.error;
			setTimeout("hideLightbox();", 3000);
			document.body.style.cursor = 'default';
	}
	});
}

function GetAddTagForm(id, source){
	if (loggedIn){
		var url = path + "/Resource/AJAX?method=GetAddTagForm&id=" + id + "&source=" + source;
		ajaxLightbox(url);
	}else{
		ajaxLogin(function(){
			GetAddTagForm(id, source);
		});
	}
}

function SaveTag(id, source, formElem, strings) {
	if (loggedIn){
		var tags = formElem.elements['tag'].value;
	
		var url = path + "/Resource/AJAX";
		var params = "method=SaveTag&tag=" + encodeURIComponent(tags) + "&id=" + id + "&source=" + source ;
	
		$.ajax({
			url: url + '?' + params,
			dataType: 'json',
			success : function(data) {
				var result = data ? data.result : false;
				if (result && result.length > 0) {
					if (result == "Unauthorized") {
						alert("You must be logged in to add tags");
					} else {
						GetTags(id, source, 'tagList', strings);
						document.getElementById('popupbox').innerHTML = '<h3>' + strings.success + '</h3>';
						setTimeout("hideLightbox();", 3000);
					}
				} else {
					document.getElementById('popupbox').innerHTML = strings.save_error;
				}
			},
			error : function() {
				document.getElementById('popupbox').innerHTML = strings.save_error;
			}
		});
	}else{
		ajaxLogin(function(){
			SaveTag(id, formElem, strings);
		});
	}
}

function GetTags(id, elemId, strings) {
	var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";
	var params = "method=GetTags";
	$.ajax({
		url: url + '?' + params,
		dataType: 'json',
		success : function(data) {
			if (data.result) {
				var tags = data.result.tags;
				var output = "";
				if (tags && tags.length > 0) {
					for (i = 0; i < tags.length; i++) {
						output = output + '<div class="sidebarValue"><a href="' + path + '/Search/Results?tag=' + encodeURIComponent(tags[i].tag) + '">'
								+ jsEntityEncode(tags[i].tag) + '</a> (' + tags[i].count + ")</div>";
					}
				}
				$("#" + elemId).html(output);
			} else {
				$("#" + elemId).html(strings.load_error);
			}
		},
		error : function() {
			$("#" + elemId).html(strings.load_error);
		}
	});
}

function loadOtherEditionSummaries(id, isEcontent){
	var url = path + "/Search/AJAX?method=getOtherEditions&id=" + id + "&isEContent=" + isEcontent;
	ajaxLightbox(url);
}