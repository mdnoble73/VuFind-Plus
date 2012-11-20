(function($){
	startIdleTimer();

})(jQuery);

var autoLogoutTimer;
function showLogoutMessage(){
	lightbox('33%', '33%', 100, 100);
	var message = "<div id='popupboxHeader' class='header'>Still there?<a href='#' onclick='hideLightbox();return false;' id='popup_close_link'>Close</a></div>";
	message += "<div id='popupboxContent' class='content'>";
	message += "<div id='autoLogoutMessage'>Are you still there?  Click Continue to keep using the catalog or Logout to end your session immediately.</div>";
	message += "<div id='autoLogoutActions'>";
	message += "<div id='continueSession' class='button' onclick='continueSession();'>Continue</div>";
	message += "<div id='endSession' class='button' onclick='endSession();'>Logout</div>";
	message += "</div>";
	message += "</div>";
	$("#popupbox").html(message);
	autoLogoutTimer = setTimeout("endSession()", 10000);
}

function showRedirectToHomeMessage(){
	lightbox('33%', '33%', 100, 100);
	var message = "<div id='popupboxHeader' class='header'>Still there?<a href='#' onclick='hideLightbox();return false;' id='popup_close_link'>Close</a></div>";
	message += "<div id='popupboxContent' class='content'>";
	message += "<div id='autoLogoutMessage'>Are you still there?  Click Continue to keep using the catalog.</div>";
	message += "<div id='autoLogoutActions'>";
	message += "<div id='continueSession' class='button' onclick='continueSession();'>Continue</div>";
	message += "</div>";
	message += "</div>";
	$("#popupbox").html(message);
	autoLogoutTimer = setTimeout("endSession()", 10000);
}

function startIdleTimer(){
	var timeout = automaticTimeoutLength * 1000;
	$.idleTimer(timeout);
	
	$(document).on("idle.idleTimer", function(){
		if (loggedIn){
			showLogoutMessage();
		}else{
			showRedirectToHomeMessage();
		}
	});
}

function continueSession(){
	clearTimeout(autoLogoutTimer);
	hideLightbox();
	startIdleTimer();
}

function endSession(){
	//Redirect to logout page
	window.location = path + "/MyResearch/Logout";
}