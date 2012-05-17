(function($){
	startIdleTimer();

})(jQuery);

var autoLogoutTimer;
function showLogoutMessage(){
	lightbox('33%', '33%', 100, 100);
	var message = "<div id='autoLogoutMessage'>Are you still there?  Click Continue to keep using the catalog or Logout to end your session immediately.</div>";
	message += "<div id='autoLogoutActions'>";
	message += "<div id='continueSession' class='autoLogoutButton' onclick='continueSession();'>Continue</div>";
	message += "<div id='endSession' class='autoLogoutButton' onclick='endSession();'>Logout</div>";
	message += "</div>";
	$("#popupbox").html(message);
	autoLogoutTimer = setTimeout("endSession()", 10000);
}

function startIdleTimer(){
	var timeout = 90000;
	$.idleTimer(timeout);
	
	$(document).on("idle.idleTimer", function(){
		showLogoutMessage();
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