/* AJAX Functions using YUI Connection Manager Functionality
 *
 * @todo: Please rewrite me as a class!!!
 */

function getLightbox(module, action, id, lookfor, message, followupModule, followupAction, followupId, postParams)
{
    // Set Post or Get
    var ajaxMethod = (postParams == undefined || postParams == '') ? 'GET' : 'POST';
    var postDetails = (postParams == undefined || postParams == '') ? null : postParams;

    // Optional parameters
    if (followupModule === undefined) {followupModule = '';}
    if (followupAction === undefined) {followupAction = '';}
    if (followupId     === undefined) {followupId     = '';}

    if ((module == '') || (action == '')) {
        hideLightbox();
        return 0;
    }

    // Popup Lightbox
    lightbox();

    // Load Popup Box Content from AJAX Server
    var url = path + "/AJAX/JSON";
    var params = 'method=GetLightbox' +
                 '&lightbox=true'+
                 '&submodule=' + encodeURIComponent(module) +
                 '&subaction=' + encodeURIComponent(action) +
                 '&id=' + encodeURIComponent(id) +
                 '&lookfor=' +encodeURIComponent(lookfor) +
                 '&message=' + encodeURIComponent(message) +
                 '&followupModule=' + encodeURIComponent(followupModule) +
                 '&followupAction=' + encodeURIComponent(followupAction) +
                 '&followupId=' + encodeURIComponent(followupId);
    var callback =
    {
        success: function(transaction) {
            var response = (transaction && transaction.responseText)
                ? transaction.responseText
                : document.getElementById('lightboxError').innerHTML;
            document.getElementById('popupbox').innerHTML = response;

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
        failure: function(transaction) {
            document.getElementById('popupbox').innerHTML =
                document.getElementById('lightboxError').innerHTML;
        }
    };
    var transaction = YAHOO.util.Connect.asyncRequest(ajaxMethod, url+'?'+params, callback, postDetails);

    // Make Popup Box Draggable
    var dd = new YAHOO.util.DD("popupbox");
    dd.setHandleElId("popupboxHeader");
}

function SaltedLogin(elems, module, action, id, lookfor, message)
{
    // Load Popup Box Content from AJAX Server
    var url = path + "/AJAX/JSON";
    var params = 'method=getSalt';
    var callback =
    {
        success: function(transaction) {
            var result = eval('(' + transaction.responseText + ')');
            if (result && result.status == 'OK' && result.data) {
                Login(elems, result.data, module, action, id, lookfor, message);
            }
        }
    };
    var transaction = YAHOO.util.Connect.asyncRequest('GET', url+'?'+params, callback, null);
}

function Login(elems, salt, module, action, id, lookfor, message)
{
    var username = elems['username'].value;
    var password = elems['password'].value;

    // Encrypt Password
    password = rc4Encrypt(salt, password);

    // Process Login via AJAX
    var url = path + "/AJAX/JSON";
    var params = 'method=login' +
                 '&username=' + username +
                 '&password=' + hexEncode(password);
    var callback =
    {
        success: function(transaction) {
            var result = eval('(' + transaction.responseText + ')');
            if (result && result.status == 'OK') {
                // Hide "log in" options and show "log out" options:
                var login = document.getElementById('loginOptions');
                var logout = document.getElementById('logoutOptions');
                if (login) {
                    login.style.display = 'none';
                }
                if (logout) {
                    logout.style.display = 'block';
                }

                // Update user save statuses if the current context calls for it:
                if (typeof(doGetSaveStatuses) == 'function') {
                    doGetSaveStatuses();
                } else if (typeof(redrawSaveStatus) == 'function') {
                    redrawSaveStatus();
                }

                // Load the post-login action:
                getLightbox(module, action, id, lookfor, message);
            } else if (result && result.data) {
                alert(result.data);
            }
        }
    };
    var transaction = YAHOO.util.Connect.asyncRequest('GET', url+'?'+params, callback, null);
}

function initAutocomplete(inputBox, suggestionBox, typeDropDown)
{
    // Build the data source for retrieving suggestions:
    dataSource = new YAHOO.util.XHRDataSource(path + "/AJAX/Autocomplete");
    dataSource.responseType = YAHOO.util.XHRDataSource.TYPE_TEXT;
    dataSource.responseSchema = {
        fieldDelim : "---NOTAPPLICABLE---",
        recordDelim : "\n"
    };

    // Build the autocomplete control:
    var autoComp = new YAHOO.widget.AutoComplete(inputBox, suggestionBox, dataSource);

    // Disable auto-highlighting (it interferes with the user's ability to submit
    // their query string by hitting Enter):
    autoComp.autoHighlight = false;

    // Ensure that the autocomplete control sends the current search type as a
    // parameter to the data source:
    autoComp.generateRequest = function(query) {
        var typeParam = "";
        var o = document.getElementById(typeDropDown);
        if (o && o.value) {
            typeParam = "&type=" + encodeURIComponent(o.value);
        }
        // query is already URL-encoded when it is passed in -- don't double-encode!
        return "?q=" + query + typeParam;
    }
}