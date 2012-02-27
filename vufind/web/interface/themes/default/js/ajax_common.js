/* This file contains AJAX routines that are shared by multiple VuFind modules.
 */

/* Create a new list for storing favorites:
 */
function addList(form, failMsg)
{
    for (var i = 0; i < form.public.length; i++) {
        if (form.public[i].checked) {
            var isPublic = form.public[i].value;
        }
    }

    var url = path + "/AJAX/JSON";
    var params = "method=addList&" +
        "title=" + encodeURIComponent(form.title.value) + "&" +
        "public=" + isPublic + "&" +
        "desc=" + encodeURIComponent(form.desc.value) + "&" +
        "followupModule=" + form.followupModule.value + "&" +
        "followupAction=" + form.followupAction.value + "&" +
        "followupId=" + form.followupId.value;

    var callback =
    {
        success: function(transaction) {
            var value = eval('(' + transaction.responseText + ')');
            if (value) {
                if (value.status == "OK") {
                    getLightbox(
                        form.followupModule.value, form.followupAction.value,
                        form.followupId.value, null, form.followupText.value
                    );
                } else {
                    alert(value.data.length > 0 ? value.data : failMsg);
                }
            } else {
                document.getElementById('popupbox').innerHTML = failMsg;
                setTimeout("hideLightbox();", 3000);
            }
        },
        failure: function(transaction) {
            document.getElementById('popupbox').innerHTML = failMsg;
            setTimeout("hideLightbox();", 3000);
        }
    };
    var transaction = YAHOO.util.Connect.asyncRequest(
        'GET', url+'?'+params, callback, null
    );
}

/* Given a base URL and a set of parameters, use AJAX to send an email; this assumes
 * that a lightbox is already open.
 */
function sendAJAXEmail(params, strings, methodName)
{
    var url = path + "/AJAX/JSON";

    // Set default for method name if not provided:
    if (methodName == null) {
        methodName = 'emailRecord';
    }
    var userParagraph = '<p class="userMsg">';
    var errorParagraph = '<p class="error">';
    var endParagraph ='</p>';

    document.getElementById('popupDetails').style.display = 'none';
    document.getElementById('popupMessages').innerHTML = userParagraph + strings.sending + endParagraph;

    var callback =
    {
        success: function(transaction) {
            var value = eval('(' + transaction.responseText + ')');
            if (value && value.status == 'OK') {
                document.getElementById('popupMessages').innerHTML = userParagraph + strings.success + endParagraph;
                setTimeout("hideLightbox();", 3000);
            } else {
                var errorDetails = strings.failure;
                if (value && value.data && value.data.length > 0) {
                    errorDetails += ': ' + value.data;
                }
                document.getElementById('popupMessages').innerHTML = errorParagraph + errorDetails + endParagraph;
                document.getElementById('popupDetails').style.display = 'block';
            }
        },
        failure: function(transaction) {
            document.getElementById('popupMessages').innerHTML = errorParagraph + strings.failure + endParagraph;
            document.getElementById('popupboxContent').style.display = 'block';
        }
    };
    var transaction = YAHOO.util.Connect.asyncRequest('POST', url+'?method=' + encodeURIComponent(methodName), callback, params);
}

/* Send the current URL in an email to a specific address, from a specific address,
 * and including some message text.
 */
function SendURLEmail(to, from, message, strings)
{
    var params = "url=" + URLEncode(window.location.href) + "&" +
                 "from=" + encodeURIComponent(from) + "&" +
                 "to=" + encodeURIComponent(to) + "&" +
                 "message=" + encodeURIComponent(message);
    sendAJAXEmail(params, strings, 'emailSearch');
}

/* Send information on the specified record in an email to a specific address, from
 * a specific address, and including some message text.
 */
function sendRecordEmail(id, to, from, message, module, strings)
{
    var params = "from=" + encodeURIComponent(from) + "&" +
                 "to=" + encodeURIComponent(to) + "&" +
                 "message=" + encodeURIComponent(message) + "&" +
                 "id=" + encodeURIComponent(id) + "&" +
                 "type=" + encodeURIComponent(module);
    sendAJAXEmail(params, strings);
}

/* Send an ID Search in an email to a specific address, from a specific address,
 * and including some message text.
 */
function SendIDEmail(to, from, ids, message, strings)
{
    var idList = [];
    if(ids.length != undefined) {
        for(i = 0; i < ids.length; i++) {
            idList[i] = ids[i].attributes['value'].nodeValue;
        }
    }
    else {
        idList[0] = ids.attributes['value'].nodeValue;
    }
    idJoin = idList.join(" ");
    var params = "url=" + URLEncode(path + "/Search/Results?lookfor=" +
                 encodeURIComponent(idJoin) + "&type=ids") + "&" +
                 "from=" + encodeURIComponent(from) + "&" +
                 "to=" + encodeURIComponent(to) + "&" +
                 "message=" + encodeURIComponent(message);
    sendAJAXEmail(params, strings, 'emailSearch');
}

/* Given a string of ids and a set of parameters, use AJAX to export favorites; this assumes
 * that a lightbox is already open.
 */
function exportIDS(ids, format, strings)
{
    var idList = '';
    if(ids.length != undefined) {
        for(i = 0; i < ids.length; i++) {
            idList += "ids[]="+encodeURIComponent(ids[i].attributes['value'].nodeValue) + "&";
        }
    }
    else {
        idList += "ids[]="+encodeURIComponent(ids.attributes['value'].nodeValue);
    }

    var methodName = 'exportFavorites';
    var url = path + '/AJAX/JSON';
    var params = idList + "&" +
                 "format=" + encodeURIComponent(format);

    var userParagraph = '<p class="userMsg">';
    var errorParagraph = '<p class="error">';
    var endParagraph ='</p>';

    document.getElementById('popupDetails').style.display = 'none';
    document.getElementById('popupMessages').innerHTML = userParagraph + strings.exporting + endParagraph;

    var callback =
    {
        success: function(transaction) {
            var value = eval('(' + transaction.responseText + ')');
            if (value && value.status == 'OK') {
                document.getElementById('popupMessages').innerHTML = userParagraph + strings.success + endParagraph;
                document.getElementById('popupDetails').innerHTML = '<p><a class="save" onClick="hideLightbox();" href="'+path+'/MyResearch/Bulk?exportInit">'+strings.download+'</a></p>';
                document.getElementById('popupDetails').style.display = 'block';
            } else if (value && value.result && value.result.length > 0) {
                var errorDetails = value.result;
                document.getElementById('popupMessages').innerHTML = errorParagraph + strings.failure + (errorDetails ? ': '+ errorDetails : '') + endParagraph;
                document.getElementById('popupDetails').style.display = 'block';
            } else {
                document.getElementById('popupMessages').innerHTML = errorParagraph + strings.failure + endParagraph;
                document.getElementById('popupDetails').style.display = 'block';
            }
        },
        failure: function(transaction) {
            document.getElementById('popupMessages').innerHTML = errorParagraph + strings.failure + endParagraph;
            document.getElementById('popupboxContent').style.display = 'block';
        }
    }
    var transaction = YAHOO.util.Connect.asyncRequest('POST', url+'?method=' + encodeURIComponent(methodName), callback, params);
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
            if (clearString[x] == ' ') {
                output += '+';
            } else {
                var charCode = clearString.charCodeAt(x);
                var hexVal = charCode.toString(16);
                output += '%' + ( hexVal.length < 2 ? '0' : '' ) + hexVal.toUpperCase();
            }
            x++;
        }
    }
    return output;
}

function sendSMS(id, to, provider, module, strings)
{
    var url = path + "/AJAX/JSON";
    var params = "id=" + encodeURIComponent(id) + "&" +
                 "method=smsRecord&" +
                 "to=" + encodeURIComponent(to) + "&" +
                 "provider=" + encodeURIComponent(provider) + "&" +
                 "type=" + encodeURIComponent(module);
    var userParagraph = '<p class="userMsg">';
    var errorParagraph = '<p class="error">';
    var endParagraph ='</p>';

    document.getElementById('popupDetails').style.display = 'none';
    document.getElementById('popupMessages').innerHTML = userParagraph + strings.sending + endParagraph;

    var callback =
    {
        success: function(transaction) {
            var value = eval('(' + transaction.responseText + ')');
            if (value && value.status == 'OK') {
                document.getElementById('popupMessages').innerHTML = userParagraph + strings.success + endParagraph;
                setTimeout("hideLightbox();", 3000);
            } else {
                document.getElementById('popupMessages').innerHTML = errorParagraph + strings.failure + endParagraph;
                document.getElementById('popupDetails').style.display = 'block';
            }
        },
        failure: function(transaction) {
            document.getElementById('popupMessages').innerHTML = errorParagraph + strings.failure + endParagraph;
            document.getElementById('popupDetails').style.display = 'block';
        }
    };
    var transaction = YAHOO.util.Connect.asyncRequest('GET', url+'?'+params, callback, null);
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

function performSaveRecord(id, formElem, strings, service, successCallback)
{
    var tags = formElem.elements['mytags'].value;
    var notes = formElem.elements['notes'].value;
    var list = formElem.elements['list'].options[formElem.elements['list'].selectedIndex].value;

    var url = path + "/AJAX/JSON";
    var params = "method=saveRecord&" +
                 "id=" + encodeURIComponent(id) + "&" +
                 "service=" + encodeURIComponent(service) + "&" +
                 "mytags=" + encodeURIComponent(tags) + "&" +
                 "list=" + list + "&" +
                 "notes=" + encodeURIComponent(notes);
    var callback =
    {
        success: function(transaction) {
            var response = eval('(' + transaction.responseText + ')');
            if (response && response.status == 'OK') {
                successCallback();
                hideLightbox();
            } else if (response && response.status == 'NEED_AUTH') {
                getLightbox('Record', 'Save', id, null, strings.add);
            } else {
                document.getElementById('popupbox').innerHTML = strings.error;
                setTimeout("hideLightbox();", 3000);
            }
        },
        failure: function(transaction) {
            document.getElementById('popupbox').innerHTML = strings.error;
            setTimeout("hideLightbox();", 3000);
        }
    };
    var transaction = YAHOO.util.Connect.asyncRequest('GET', url+'?'+params, callback, null);
}

/* function to fetch resolver links GS 20101011 */
function getResolverLinks(openURL, id, strings)
{
    // set the spinner going
    var myTarget = getElem('openUrlEmbed'+id);
    myTarget.innerHTML='<center><img src="' + path
        + '/images/loading.gif" /></center>';
    myTarget.style.display = 'block';

    var myLink = document.getElementById('openUrlLink'+id);
    myLink.style.display = 'none';

    var url = path + "/AJAX/JSON?method=getResolverLinks"
        + "&openurl=" + encodeURIComponent(openURL);

    var callback =
    {
        success: function(http) {
            var response = eval('(' + http.responseText + ')');
            if (response) {
                if (response.status == 'OK' && response.data) {
                    myTarget.innerHTML = response.data
                } else if (response.data) {
                    strings.error = response.data;
                    this.failure();
                } else {
                    this.failure();
                }
            }
        },
        failure: function(http){
            myTarget.style.display = 'none'; // remove spinner
            myLink.style.display = 'block'; // restore button
            alert(strings.error);
        }
    };

    YAHOO.util.Connect.asyncRequest('GET', url, callback, null);
}

/* Load the contents of a URL (url) into a DOM element (identified by domId).
 * Fill the DOM element with errorString if AJAX fails.
 */
function URLtoDOM(url, domId, errorString)
{
    var callback =
    {
        success: function(transaction) {
            var o = document.getElementById(domId);
            if (o) {
                if (transaction.responseText) {
                    o.innerHTML = transaction.responseText;
                } else {
                    o.innerHTML = errorString;
                }
            }
        },
        failure: function(transaction) {
            var o = document.getElementById(domId);
            if (o) {
                o.innerHTML = errorString;
            }
        }
    };
    var transaction = YAHOO.util.Connect.asyncRequest('GET', url, callback, null);
}