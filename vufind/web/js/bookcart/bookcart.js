// JavaScript Document
/** book cart */
var bookBag = new Array();
var bagErrors = new Array();
var BAG_COOKIE = "book_BAG_COOKIE_vufind";

$(document).ready(function() { 
	// if bag not used on current page, then don't do anything
	if ($("#book_bag #bag_items").length == 0) {
		return;
	}
	
	// if we are printing, ignore update bag
	var url = window.location.href;
	if(url.indexOf('?' + 'print' + '=') != -1  || url.indexOf('&' + 'print' + '=') != -1) {
		window.print();
		$("link[media='print']").attr("media", "all");
		return;
	}
	// run the first check to see if we have anything in the bag
	updateBag();
	
	// Attach all of the actions to appropriate links 
	$("#bag_summary_holder").click(function() {  toggleBagCanvas(); /* display or hide bag canvas */});
	$("#book_bag_header").click(function() {  toggleBagCanvas(); /* display or hide bag canvas */});
	
	// email buttons
	$("#bag_email_button").click(function() { changeBagAction("email_to_box"); return false; });
	$("#bag_email_submit").click(function() { emailBag(); return false;});
	$(".bag_hide_button").click(function() { changeBagAction("bag_items");  return false;});
	$(".bag_clear_button").click(function() { emptyBag();  return false;});
	
	// Add to my list buttons
	$("#bag_add_to_my_list_button").click( function() {  changeBagAction("bag_choose_list"); return false; });
	$("#bag_save_to_list_submit").click(function() { saveToMyList(); });	
	$("#new_list").click(function(){changeBagAction("create_list"); return false;});
	$("#choose_existing_list").click(function(){changeBagAction("bag_choose_list"); return false;});
	$("#bag_create_list_button").click(function(){bagAddList(); return false;});
	// export button
	$("#bag_request_button").click(function() { requestBag(); return false; });
	
	// print button
	$("#bag_print_button").click(function() { printBag(); return false; });
	
	// login button
	$("#login_bag").click(function() { 
		changeBagAction("bookcart_login")
		return false; 
	});
	$("#bag_login_cancel").click(function() { 
		changeBagAction("bag_items");
		return false;
	});
	$("#bag_login_submit").click(function(){bagLoginUser(); return false;});
	
	// bag action processes 
	$("#bag_action_in_progress .bag_hide_button").click(function() { changeBagAction("bag_items");  return false; });
	
	$("#bag_empty_button").click(function() { if (confirm("Remove all items in your book cart. Are you sure?")) { emptyBag(); } return false; });
	$("#bag_empty_button_header").click(function() { if (confirm("Remove all items in your book cart. Are you sure?")) { emptyBag(); } return false; });
	
	// check if logged in and show the proper buttons 
	if (loggedIn) {			
		$(".logged-in-button, .email-search").show(); $(".logged-out-button, .login-button").hide();		
	} else {
		$(".logged-in-button, .email-search").hide(); $(".logged-out-button, .login-button").show();	
	}
	
});

function bagLoginUser(){
	var url = path + "/AJAX/JSON?method=loginUser"
	$.ajax({url: url,
			data: {username: $('#bag_username').val(), password: $('#bag_password').val()},
			success: function(response){
				if (response.result.success == true){
					//Get all of the lists for the user. 
					loadListsForUser(); 
					//Update the main display to show the user is logged in
					// Hide "log in" options and show "log out" options:
					$('.loginOptions').hide();
					$('.logoutOptions').show();
					$('#myAccountNameLink').html(response.result.name);
					// Update user save statuses if the current context calls for it:
					if (typeof(doGetSaveStatuses) == 'function') {
						doGetSaveStatuses();
					} else if (typeof(redrawSaveStatus) == 'function') {
						redrawSaveStatus();
					}

					//Update the book cart display to show that the user is logged in 
					$(".logged-in-button, .email-search").show(); $(".logged-out-button, .login-button").hide();
					//show controls to add to a list
					changeBagAction("bag_choose_list"); 
					
				}else{
					alert("That login was not recognized.  Please try again.");
				}
			},
			error: function(){
				alert("We were unable to process your information.  Please try again.");
			},
			dataType: 'json',
			type: 'post' 
	});
}

function loadListsForUser(){
	try{
		var url = path + "/AJAX/JSON?method=getUserLists";
		$.getJSON(url,
				function(data){
					var sel = $("#bookbag_list_select");
					sel.empty();
					for (var i=0; i<data.result.length; i++) {
					  sel.append('<option value="' + data.result[i].id + '">' + data.result[i].title + '</option>');
					}
				} 
		);
	}catch (err){
		alert("Error loading lists for user " + err);
	}	
}

/* Toggle the Display of the Cart Actions */
var currentTopPanel = "bag_items";
function changeBagAction(idToShow) {
	if (idToShow == currentTopPanel){
		return;
	}
	$("#" + currentTopPanel).fadeOut("slow");
	$("#bag_actions_" + currentTopPanel).fadeOut("slow");
	$("#" + idToShow).fadeIn("slow"); 
	$("#bag_actions_" + idToShow).fadeIn("slow"); 
	
	currentTopPanel = idToShow;
	
}

function toggleBagCanvas() {
	/// for now just toggle, later animate
	$('#book_bag_canvas').slideToggle('fast');
}

/** Adds or removes an item from to bag, when the check box is clicked 
 TODO <- refactor this to make it unobtrusive */
function toggleInBag(id, title, checkBox) {
	
	book = new Object();
	book.id = id;
	book.title = title; 
	
	if ($(checkBox).is(':checked')) {
		_addToBag(book);
	} else {
		_removeFromBag(book);	
	}
	
	_saveBagAsCookie();
	
	updateBag();	
}

/**
 * Add a title to the book bag
 * 
 * @param id
 * @param title
 */
function addToBag(id, title) {
	book = new Object();
	book.id = id;
	book.title = title; 
	
	_addToBag(book);
	
	_saveBagAsCookie();
	
	updateBag();	
}

/** Create a list and then save all items in the book cart to it */
function bagAddList(){
	var isPublic = $("#bagListPublic").is(':checked') ? '1' : '0';
	var title = $("#listTitleBag").val();
	var desc = $("#listDesc").val();
	
	var url = path + "/MyResearch/AJAX";
	if (title == ''){
		alert("Please enter a title for the list");
		return false;
	}
	
	_bagActionInProgress("Saving List " + title);
	var params = "method=AddList&" +
		"title=" + encodeURIComponent(title) + "&" +
		"public=" + isPublic + "&" +
		"desc=" + encodeURIComponent(desc);
	try{
		$.ajax({
			url: url + "?" + params, 
			dataType: "json", 
			success: function(data){
				var result = data.result;
				if (result == "Done"){
					_bagActionInProgress("Created new list successfully, adding selected titles to the list.");
					//Get the new id of the list
					var newId = data.newId;
					//Add the new list to the list of valid lists, and select it
					var sel = $("#bookbag_list_select");
					sel.append('<option value="' + newId + '" selected="selected">' + title + '</option>');
					//Add all items to the list
					saveToMyList();
				}else{
					_bagActionInProgress(result > 0 ? result : "Unable to create the specified list.");
				}
			}
		});
	}catch (err){
		_bagActionInProgress("Error adding list in book cart " + err);
	}
}

/** Add book to Bag */
function _addToBag(book) {
	if (bookBag == null) bookBag = new Array();
	
	var bookInBag = false;
	for(var i = 0; i < bookBag.length; i++) {
	  if(bookBag[i].id == book.id){
		  bookInBag = true;
	  }
	}
	
	if (bookInBag == false){
	// add to bag 
	bookBag.push(book);				
}
}


// Remove a Book From Bag
function _removeFromBag(book) {	
	var j = 0;
	var current_book; 
	
	while (j < bookBag.length) {
		// alert(originalArray[j]);
		current_book = bookBag[j];
		
		if (current_book.id == book.id) {
			bookBag.splice(j, 1);
		} else { j++; }

	} 
}

//Remove a Book From Bag
function removeFromBagById(bookid) {	
	var j = 0;
	var current_book; 
	
	while (j < bookBag.length) {
		// alert(originalArray[j]);
		current_book = bookBag[j];
		
		if (current_book.id == bookid) {
			bookBag.splice(j, 1);
		} else { j++; }

	} 
	_saveBagAsCookie();
	
	updateBag();	
}

/** SAVE THE BAG as A COOKIE for LATER PROCESSING */
function _saveBagAsCookie() {
	var bag = JSON.stringify(bookBag);
	var date = new Date();
	//Save as a session cookie
	$.cookie(BAG_COOKIE, bag, {path: '/'});
}

/** Empties the bag */
function emptyBag() {
	changeBagAction("bag_items");
	$(bookBag).each(function (i, book) {
		_removeFromBag(book);
	});
	_saveBagAsCookie();
	
	updateBag();
	return false;
}

/* Update the bag */
function updateBag(){	
	// read from cookie
	var cookie = $.cookie(BAG_COOKIE);
	
	if (cookie != null) {
		bookBag = JSON.parse(cookie);
	}
	
	if (bookBag == null) {
		bookBag = new Array();
	}
		
	// update array view
	if (bookBag.length > 0) {		
		// show array view
		$("#book_bag").show();
		
		// update book count
		_updateBookCount();
		
		// clear the bag items page
		$("#bag_items").empty();

		
		// go through the book list and make sure the checkboxes are checked properly
		var j = 0;
		var current_book;
		while (j < bookBag.length) {
			current_book = bookBag[j];
			$("#export" + current_book.id).attr("checked", "checked");
			j++;
		
			// update the list of bag items
			var bagItem = "<div class=\"bag_book_title\">" +
					"<a href ='" + path + "/Record/" + current_book.id + "' class=\"bag_title_link\">#" + j + ". " + current_book.title + "</a>" +
					"<div class=\"deleteIcon\">" + "<a href=\"#\" onClick=\"removeFromBagById('" + current_book.id + "');return false;\"><img src='" + path + "/images/silk/delete.png' alt='Remove' title='Remove from book cart'></a>" +
					"</div></div>";
			$("#bag_items").append(bagItem);
		}				
		
		
	} else {	
		$("#bag_summary").text("0 items");
		$("#bag_summary_header").text("0 items");
		$("#bag_items").empty();
		$("#book_bag, #book_bag_canvas").hide();
		$(".save_export_checkboxes").removeAttr("checked");
	}

	//checkItemSaveStatuses();
}

/** Checks the number of books in the bag and updates the count */
function _updateBookCount() {
	// update summary
	var item_text = "items in book cart";
	if (bookBag.length == 1) 
		item_text = "item in book cart";
		
	$("#bag_summary").text(bookBag.length + " " + item_text);	
	$("#bag_summary_header").text(bookBag.length + " " + item_text);
}

function _displayBagErrors() {
	if (bagErrors.length == 0) {				
		_bagActionInProgress(null);
	} else {
		var i = 0;
		//alert(bagErrors.length);
		while( i < bagErrors.length) {
			var error = bagErrors[i];
			$("#bag_error_message").append(error + "<br/>");
			bagErrors.splice(i, 1);
			i++;
		}
		changeBagAction("bag_errors");
	}
}

function _bagActionInProgress(message) {
	changeBagAction("bag_action_in_progress");
	if (message == null || message == "undefined") {
		// hide it	
		$("#bag_action_in_progress").hide();
	} else {
		
		// show the message
		$("#bag_action_in_progress").show();
		$("#bag_action_in_progress_text").text(message);
	}
}

/** Saves books into my list one by one */
function saveToMyList() {
	$("#bag_errors").hide();
	$("#bag_error_message").empty();
	
	var mytags = $("#save_tags_field").val();
	var listnum = $("#bookbag_list_select").val();
	var listname = $("#bookbag_list_select option:selected").text();
	
	_bagActionInProgress("Saving to " + listname);
	
	var url = "";
	$(bookBag).each(function (i, book) {
		// call the save, and remove it from bag on successful return			
		url = "id[]=" + book.id + "&" + url;
	});
	
	var url = path + "/AJAX/JSON?method=saveToMyList&list=" + listnum + "&mytags=" + mytags + "&notes=&" + url;
	$.getJSON(url, function(response) {
		// get the response, if good, return the user to the book bag
		// so they can do other stuff like placing holds, printing, etc. 
		if (response.result.status  == "OK") {
			$('#save_to_my_list_tags').hide();
			_bagActionInProgress("Titles saved successfully");
		} else {
			bagErrors.push("<strong>" + book.title + "</strong>: " + response.message);	
			_displayBagErrors();
		}
	});
}

/** EMail the list of items to a specified email */
function emailBag() {
	var url = "";
	$(bookBag).each(function (i, book) {
		// call the save, and remove it from bag on successful return			
		url = "id[]=" + book.id + "&" + url;
	});
	var to = $("#email_to_field").val();
	
	if (!checkEmail(to)) {
		return;
	}
	
	var url = path + '/AJAX/JSON?method=emailCartItems&to=' + to + '&' + url;
	
	// do the sending progress thing
	_bagActionInProgress("Sending email to ... " + to);
	$("#email_to_field").empty();
	$("#email_to_box").hide();
	
	$.getJSON(url, function(response) {
			// get the response, if good, remove item from bag, else, add to error messages
			if (response.status  == "OK") {
				bookBag = new Array();
				// email was sent to the user, inform them				
				updateBag();
				_bagActionInProgress("Email sent.");	
				$("#email_to_box").hide();
			} else {
				bagErrors.push("<strong>" + response.message + "</strong>");	
				$("#email_to_box").hide();
				_displayBagErrors();
			}
	});
}


/** Export to Refworks. It constructs a url to send to for refworks export */
function exportBag() {
	_bagActionInProgress("Exporting to Refworks");
	var ids = "";
	$(bookBag).each(function (i, book) {
		ids = "id[]=" + book.id + "&" + ids;
	});
	
	var url = path + '/Export/RefWorks?' + ids;
	bookBag = new Array();
	updateBag();
	_bagActionInProgress(null);
	
	// open in a new window
	window.open(url);
}


/** Sends the bag to be printed */
function printBag() {
	var url ="";
	$(bookBag).each(function (i, book) {
		if (url.length > 0){
			url += "+OR+";
		}
		url += "id%3A" + book.id;
	});
	
	url =  path + "/Search/Results?lookfor=" + url + "&type=keyword&print=true";
	window.open(url);
}

/** Requests all items in the bag **/
function requestBag(){
	//redirect to the Hold Multiple page;
	var url = path + "/MyResearch/HoldMultiple?fromCart=true"
	$(bookBag).each(function (i, book) {
		url += "&";
		var shortId = book.id;
		url += "selected[" + shortId + "]=on";
	});
	window.location = url;
}

function checkEmail(address){
	if (address.match(/^[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i)) {
		return true;
	}else{
		return false;
	}
}
