var bookBag = new Array();
var bagErrors = new Array();
var BAG_COOKIE = "book_BAG_COOKIE_vufind";

// Pre-handler styling.
$('html').addClass('pre-ready');

$(document).ready(function() {
  // if bag not used on current page, then don't do anything
  if ($("#book_bag #bag_items").length > 0) {

    // if we are printing, ignore update bag
    var url = window.location.href;
    if(url.indexOf('?' + 'print' + '=') != -1  || url.indexOf('&' + 'print' + '=') != -1) {
      window.print();
      $("link[media='print']").attr("media", "all");
    } else {
      // run the first check to see if we have anything in the bag
      updateBag(true);

      // Attach all of the actions to appropriate links
      $("#bag_summary_holder").css({cursor: 'pointer'}).click(function() {  toggleBagCanvas(); /* display or hide bag canvas */});
      $("#book_bag_header").css({cursor: 'pointer'}).click(function() {  toggleBagCanvas(); /* display or hide bag canvas */});

      // email buttons
      $("#bag_email_button").click(function() {
        toggleBagActionItems(true);
        $('#email_to_box').show();
        return false;
      });
      $("#email_to_box .bag_perform_action_button").click(function() {
        emailBag();
      });
      $("#email_to_box .bag_hide_button").click(function() {
        $('#email_to_box').hide();
        toggleBagActionItems();
        return false;
      });

      // Add to my list buttons
      $("#bag_add_to_my_list_button").click( function() {
        toggleBagActionItems(true);
        $('#save_to_my_list_tags').show();
        return false;
      });

      $("#save_to_my_list_tags .bag_perform_action_button").click(function() {
        saveToMyList();
      });

      $("#save_to_my_list_tags .bag_hide_button").click(function() {
        $('#save_to_my_list_tags').hide();
        toggleBagActionItems();
        return false;
      });

      $("#new_list").click(function(){
        $('#existing_list_controls').hide();$('#new_list').hide();
        $('#new_list_controls').fadeIn();$('#listForm').fadeIn()
      });

      $("#choose_existing_list").click(function(){
        $('#new_list_controls').hide();
        $('#existing_list_controls').fadeIn();
        $('#new_list').fadeIn();
      });

      // export button
      $("#bag_request_button").click(function() {
        requestBag();
        return false;
      });

      // print button
      $("#bag_print_button").click(function() {
        printBag();
        return false;
      });

      // login button
      $("#login_bag").click(function() {
        $('#bag_actions').height('175px');
        toggleBagActionItems(true);
        $('#bookcart_login').show();
        return false;
      });

      $("#bag_login_cancel").click(function() {
        $('#bag_actions').height('150px');
        toggleBagActionItems(false);
        return false;
      });

      $("#bag_login_submit").click(function(){
        bagLoginUser();
        return false;
      });

      // bag action processes
      $("#bag_action_in_progress .bag_hide_button").click(function() {
        toggleBagActionItems();
        return false;
      });

      $("#bag_empty_button").click(function() {
        if (confirm("Remove all items in your book cart. Are you sure?")) { emptyBag(); }
        return false;
      });

      $("#bag_empty_button_header").click(function() {
        if (confirm("Remove all items in your book cart. Are you sure?")) { emptyBag(); }
        return false;
      });

      //$(".logged-in-button, .email-search").show();
      //$(".logged-out-button, .login-button").hide();

      // check if logged in and show the proper buttons
      if (loggedIn) {
        $(".logged-in-button, .email-search").show(); $(".logged-out-button, .login-button").hide();
      } else {
        $(".logged-in-button, .email-search").hide(); $(".logged-out-button, .login-button").show();
      }
    }
  }
  $('html').removeClass('pre-ready');
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
          toggleBagActionItems(true);
          $('#save_to_my_list_tags').show();
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
function toggleBagActionItems(show) {
  if (show) {
    $("#bag_items").slideUp();
    $("#bag_actions").slideDown();

    $('.bag_box').hide();
    $('#bag_links').fadeOut();
  } else {

    $("#bag_actions").slideUp();
    $("#bag_items").slideDown();

    $('.bag_box').hide();
    $('#bag_links').fadeIn();
  }

}

function toggleBagCanvas() {
  $('#bag-content').slideToggle('fast');
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

/** Create a list and then save all items in the book cart to it */
function bagAddList(form, failMsg){
  for (var i = 0; i < form.public.length; i++) {
    if (form.public[i].checked) {
      var isPublic = form.public[i].value;
    }
  }
  var url = path + "/MyResearch/AJAX";
  var params = "method=AddList&" +
    "title=" + encodeURIComponent(form.title.value) + "&" +
    "public=" + isPublic + "&" +
    "desc=" + encodeURIComponent(form.desc.value);
  try{
    $.ajax({
      url: url + "?" + params,
      dataType: "json",
      success: function(data){
        var result = data.result;
        if (result == "Done"){
          //Get the new id of the list
          var newId = data.newId;
          //Add the new list to the list of valid lists, and select it
          var sel = $("#bookbag_list_select");
          sel.append('<option value="' + newId + '" selected="selected">' + form.title.value + '</option>');
          //Add all items to the list
          saveToMyList();
        }else{
          alert(result > 0 ? result : failMsg);
        }
      }
    });
  }catch (err){
    alert("Error adding list in book cart " + err);
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
      $('.in-cart[data-summId="' + book.id +'"]').text('Add to cart +').removeClass('in-cart');
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
      $('.in-cart[data-summId="' + bookid +'"]').text('Add to cart +').removeClass('in-cart');
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
  $(bookBag).each(function (i, book) {
    _removeFromBag(book);
  });
  _saveBagAsCookie();

  updateBag(true);
  return false;
}

/* Update the bag */
function updateBag(collapse){
  // read from cookie
  var cookie = $.cookie(BAG_COOKIE);
  if (cookie != null) {
    bookBag = JSON.parse(cookie);
  }
  if (bookBag == null) {
    bookBag = new Array();
  }
  _updateBookCount();
  // update array view
  if (bookBag.length > 0) {
    // update book count
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
          "<a class=\"book-title-cart\" href ='" + path + "/Record/" + current_book.id + "'>#" + j + ". " + current_book.title + "</a>" +
          "<a class=\"icon-delete\" href=\"#\" onClick=\"removeFromBagById('" + current_book.id + "');return false;\">Remove</a>"
          "</div>";
      $("#bag_items").append(bagItem);
    }
  } else {
    $("#bag_items").empty();
    $(".save_export_checkboxes").removeAttr("checked");
  }
  if (collapse == true) {
    $('#bag-content').hide();
  };
}

/** Checks the number of books in the bag and updates the count */
function _updateBookCount() {
  var units = bookBag.length == 1 ? 'item':'items';
  $("#bag_summary_header .count").text(bookBag.length + ' ' + units);
}

function _displayBagErrors() {
  if (bagErrors.length == 0) {
    _bagActionInProgress(null);
  } else {
    var i = 0;
    //alert(bagErrors.length);
    while( i < bagErrors.length) {
      var error = bagErrors[i];
      $("#bag_errors").show();
      $("#bag_error_message").append(error + "<br/>");
      bagErrors.splice(i, 1);
      i++;
    }
  }
}

function _bagActionInProgress(message) {
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
  var listnum = $("#list_select").val();

  _bagActionInProgress("Saving to My List");

  var url = "";
  $(bookBag).each(function (i, book) {
    // call the save, and remove it from bag on successful return
    url = "id[]=" + book.id + "&" + url;
  });

  var listnum = $("#bookbag_list_select").val();

  var url = path + "/AJAX/JSON?method=saveToMyList&list=" + listnum + "&mytags=" + mytags + "&notes=&" + url;
  $.getJSON(url, function(response) {
    // get the response, if good, return the user to the book bag
    // so they can do other stuff like placing holds, printing, etc.
    if (response.result.status  == "OK") {
      $('#save_to_my_list_tags').hide();
      toggleBagActionItems();
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
  var url = path + "/MyResearch/HoldMultiple"
  $(bookBag).each(function (i, book) {
    if (i == 0){
      url += "?";
    }else{
      url += "&";
    }
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
