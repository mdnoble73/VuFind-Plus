function getSaveStatus(id, elemId)
{
    var url = path + "/AJAX/JSON";
    var params = "method=getSaveStatuses&id[]=" + encodeURIComponent(id);
    var callback =
    {
        success: function(transaction) {
            var response = eval('(' + transaction.responseText + ')');
            if (response && response.status == 'OK') {
                if (response.data && response.data.length > 0) {
                    YAHOO.util.Dom.addClass(document.getElementById(elemId), 'savedFavorite');
                }
            }
        }
    };
    var transaction = YAHOO.util.Connect.asyncRequest('GET', url+'?'+params, callback, null);
}

function saveRecord(id, formElem, strings)
{
    successCallback = function() {
        // Highlight the save link to indicate that the content is saved:
        YAHOO.util.Dom.addClass(document.getElementById('saveLink'), 'savedFavorite');

        // Redraw tag list:
        GetTags(id, 'tagList', strings);
    };
    performSaveRecord(id, formElem, strings, 'VuFind', successCallback);
}

function SaveTag(id, formElem, strings)
{
    var tags = formElem.elements['tag'].value;

    var url = path + "/AJAX/JSON";
    var params = "method=tagRecord&tag=" + encodeURIComponent(tags) + "&id=" + encodeURIComponent(id);
    var callback =
    {
        success: function(transaction) {
            var result = eval('(' + transaction.responseText + ')');
            if (result && result.status == 'OK') {
                GetTags(id, 'tagList', strings);
                document.getElementById('popupbox').innerHTML = '<h3>' + strings.success +'</h3>';
                setTimeout("hideLightbox();", 3000);
            } else if (result && result.data.length > 0) {
                document.getElementById('popupbox').innerHTML = result.data;
            } else {
                document.getElementById('popupbox').innerHTML = strings.save_error;
            }
        },
        failure: function(transaction) {
            document.getElementById('popupbox').innerHTML = strings.save_error;
        }
    };
    var transaction = YAHOO.util.Connect.asyncRequest('GET', url+'?'+params, callback, null);
}

function GetTags(id, elemId, strings)
{
    var url = path + "/AJAX/JSON";
    var params = "method=getRecordTags&id=" + encodeURIComponent(id);
    var callback =
    {
        success: function(transaction) {
            var response = eval('(' + transaction.responseText + ')');
            if (response && response.status == 'OK') {
                var output = "";
                if (response.data && response.data.length > 0) {
                    for(i = 0; i < response.data.length; i++) {
                        if (i > 0) {
                            output = output + ", ";
                        }
                        output = output + '<a href="' + path + '/Search/Results?tag=' +
                                 encodeURIComponent(response.data[i].tag) + '">' +
                                 jsEntityEncode(response.data[i].tag) + '</a> (' +
                                 response.data[i].cnt + ")";
                    }
                }
                document.getElementById(elemId).innerHTML = output;
            } else if (response.data && response.data.length > 0) {
                document.getElementById(elemId).innerHTML = response.data;
            } else {
                document.getElementById(elemId).innerHTML = strings.load_error;
            }
        },
        failure: function(transaction) {
            document.getElementById(elemId).innerHTML = strings.load_error;
        }
    };
    var transaction = YAHOO.util.Connect.asyncRequest('GET', url+'?'+params, callback, null);
}

function SaveComment(id, strings)
{
    comment = document.forms['commentForm'].elements['comment'].value;

    var url = path + "/AJAX/JSON";
    var params = "method=commentRecord&id=" + encodeURIComponent(id) + "&comment=" + encodeURIComponent(comment);
    var callback =
    {
        success: function(transaction) {
            var result = eval('(' + transaction.responseText + ')');
            if (result && result.status == "OK") {
                document.forms['commentForm'].elements['comment'].value = '';
                LoadComments(id, strings);
            } else if (result && result.status == "NEED_AUTH") {
                getLightbox('AJAX', 'Login', id, null, strings.save_title);
            } else if (result && result.data && result.data.length > 0) {
                alert(result.data);
            } else {
                alert(strings.save_error);
            }
        },
        failure: function(transaction) {
            alert(strings.save_error);
        }
    };
    var transaction = YAHOO.util.Connect.asyncRequest('GET', url+'?'+params, callback, null);
}

function LoadComments(id, strings)
{
    var output = '';
    
    var url = path + "/AJAX/JSON";
    var params = "method=getRecordCommentsAsHTML&id=" + encodeURIComponent(id);
    var callback =
    {
        success: function(transaction) {
            var result = eval('(' + transaction.responseText + ')');
            if (result && result.data && result.data.length > 0) {
                document.getElementById('commentList').innerHTML = result.data;
            } else {
                document.getElementById('commentList').innerHTML = strings.load_error;
            }
        },
        failure: function(transaction) {
            document.getElementById('commentList').innerHTML = strings.load_error;
        }
    };
    var transaction = YAHOO.util.Connect.asyncRequest('GET', url+'?'+params, callback, null);
}
