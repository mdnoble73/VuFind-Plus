
function lightbox(){
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
	$('#popupbox').css('top', new_top + 200 + 'px');
	$('#popupbox').css('left', '25%');
	$('#popupbox').css('width', '50%');
	$('#popupbox').css('height', '50%');
}

function ajaxLightbox(urlToLoad){
	var loadMsg = $('#lightboxLoading').html();

	$('#popupbox').innerHTML = '<img src="' + path + '/images/loading.gif" /><br />' + loadMsg;
   
	hideSelects('hidden');

	// Find out how far down the screen the user has scrolled.
	var new_top =  document.body.scrollTop;

	// Get the height of the document
	var documentHeight = $('document').height();

	$('#lightbox').show();
	$('#lightbox').css('height', documentHeight + 'px');

	$('#popupbox').show();
	$('#popupbox').css('top', new_top + 200 + 'px');
	$('#popupbox').css('left', '25%');
	$('#popupbox').css('width', 'auto');
	$('#popupbox').css('height', 'auto');
	
	$.get(urlToLoad, function(data) {
		$('#popupbox').html(data);
	});
}

function showElementInLightbox(title, elementSelector){
	//Find out how far down the screen the user has scrolled.
	var new_top =  document.body.scrollTop;

	// Get the height of the document
	var documentHeight = $('document').height();

	$('#lightbox').show();
	$('#lightbox').css('height', documentHeight + 'px');

	$('#popupbox').show();
	$('#popupbox').css('top', new_top + 200 + 'px');
	$('#popupbox').css('left', '25%');
	$('#popupbox').css('width', 'auto');
	$('#popupbox').css('height', 'auto');
	
	var lightboxContents = "<div class='header'>" + title + "<a href='#' onclick='hideLightbox();return false;' class='closeIcon'>Close <img src='" + path + "/images/silk/cancel.png' alt='close' /></a></div>";
	lightboxContents += "<div class='content'>" + $(elementSelector).html() + "</div>";
	
	$('#popupbox').html(lightboxContents);
	
}

function hideLightbox()
{
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

function toggleMenu(elemId)
{
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
        //  Look for filters (specifically checkbox filters)
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

/* Function to check if user is logged in. It expects a function as an argument,
to which a value of TRUE or FALSE will be supplied, once it comes back from the server */
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

/* Function to check if user is logged in. Runs Synchronously and returns the value of whether it is logged in or not. */
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
	//Get the current url
	var currentLocation = window.location.href;
	//Check to see if we already have a sort parameter. .
	if (currentLocation.match(/(sort=[^&]*)/)) {
		//Replace the existing sort with the new sort parameter
		currentLocation = currentLocation.replace(/sort=[^&]*/, 'sort=' + newSort);
	} else {
		//Add the new sort parameter
		if (currentLocation.match(/\?/)) {
			currentLocation += "&sort=" + newSort;
		}else{
			currentLocation += "?sort=" + newSort;
		}
	}
	//Redirect back to this page.
	window.location.href = currentLocation;
}


/* update the sort parameter and redirect the user back to the same page */
function changeAccountSort(newSort){
	//Get the current url
	var currentLocation = window.location.href;
	//Check to see if we already have a sort parameter. .
	if (currentLocation.match(/(accountSort=[^&]*)/)) {
		//Replace the existing sort with the new sort parameter
		currentLocation = currentLocation.replace(/accountSort=[^&]*/, 'accountSort=' + newSort);
	} else {
		//Add the new sort parameter
		if (currentLocation.match(/\?/)) {
			currentLocation += "&accountSort=" + newSort;
		}else{
			currentLocation += "?accountSort=" + newSort;
		}
	}
	//Redirect back to this page.
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

function startSearch(){
	//Stop auto complete since there is a search running already
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
			//Unable to load overdrive summary
		}else{
			//Load checked out items
			$("#checkedOutItemsOverDrivePlaceholder").html(data.numCheckedOut);
			//Load available holds
			$("#availableHoldsOverDrivePlaceholder").html(data.numAvailableHolds);
			//Load unavailable holds
			$("#unavailableHoldsOverDrivePlaceholder").html(data.numUnavailableHolds);
			//Load wishlist
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
	var url = path + "/AJAX/JSON?method=loginUser"
	$.ajax({url: url,
			data: {username: $('#username').val(), password: $('#password').val()},
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
	
	document.forms.searchForm.action='/Search/Results'
	document.forms.searchForm.submit();
}