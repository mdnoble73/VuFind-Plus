/* Given a string of ids and a set of parameters, use AJAX to delete favorites; this assumes
 * that a lightbox is already open.
 */
function deleteFavorites(ids, listID, strings)
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
    var methodName = 'deleteFavorites';
    var url = path + "/AJAX/JSON";
    var params = idList;
    if(listID != "false") { params += "&listID=" +listID; }

    var userParagraph = '<p class="userMsg">';
    var errorParagraph = '<p class="error">';
    var endParagraph = '</p>';

    document.getElementById('popupDetails').style.display = 'none';
    document.getElementById('popupMessages').innerHTML = userParagraph + strings.deleting + endParagraph;

    var callback =
    {
        success: function(transaction) {
            var value = eval('(' + transaction.responseText + ')');
            if (value && value.status == 'OK') {
                document.getElementById('popupMessages').innerHTML = userParagraph + strings.success + endParagraph;
                setTimeout("hideLightbox(); window.location.reload();", 3000);
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
    };
    var transaction = YAHOO.util.Connect.asyncRequest('POST', url+'?method=' + encodeURIComponent(methodName), callback, params);
}

function confirmOperation(params, module, action, id, lookfor, message, followupModule, followupAction, followupId)
{
    var paramsArray = [];
    var x = 0;
    for (var i in params) {
        paramsArray[x] = encodeURIComponent(i)+"="+encodeURIComponent(params[i]);
        x++; 
    }
    var postParams = paramsArray.join('&');
    getLightbox(module, action, id, lookfor, message, followupModule, followupAction, followupId, postParams);
}
