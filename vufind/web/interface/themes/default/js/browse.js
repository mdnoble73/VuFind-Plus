function ShowLoadMessage(elem)
{
    var text = document.getElementById('browseLoadMessage').value;
    var loadingHTML = '<div id="narrowLoading">' +
        '<img src="' + path + '/images/loading.gif" alt="' + 
        text + '"><br>' + text + '...' + 
        '</div>';
    document.getElementById(elem).innerHTML = loadingHTML;
}

function LoadOptions(query, field, elem, nextElem, nextField, facetPrefix)
{
    ShowLoadMessage(elem);

    var callback =
    {
        success: function(transaction) {
            var response = eval('(' + transaction.responseText + ')');
            if (response && response.data) {
                document.getElementById(elem).innerHTML = response.data;
            }
        }
    };

    var url = path + "/AJAX/JSON_Browse?method=getOptionsAsHTML&query=" + query + "&facet_field=" + field;
    if (facetPrefix) {
        url += '&facet_prefix=' + facetPrefix;
    }
    if (nextElem) {
        url += '&next_target='  + encodeURIComponent(nextElem);
    }
    if (nextField) {
        url += '&next_query_field=1&next_facet_field=' + encodeURIComponent(nextField);
    }
    var transaction = YAHOO.util.Connect.asyncRequest('GET', url, callback, null);
}

function LoadAlphabet(field, column, lookfor, includeNumbers)
{
    ShowLoadMessage(column);

    var callback =
    {
        success: function(transaction) {
            var response = eval('(' + transaction.responseText + ')');
            if (response.status == 'OK') {
                document.getElementById(column).innerHTML = response.data;
            }
        }
    };
    var url = path + "/AJAX/JSON_Browse?method=getAlphabetAsHTML&facet_field=" +
        encodeURIComponent(field) + "&query_field=" + encodeURIComponent(lookfor);
    if (includeNumbers) {
        url += "&include_numbers=1";
    }
    var transaction = YAHOO.util.Connect.asyncRequest('GET', url, callback, null);
}

function LoadSubject(field, column, lookfor)
{
    ShowLoadMessage(column);

    var callback =
    {
        success: function(transaction) {
            var response = eval('(' + transaction.responseText + ')');
            if (response && response.data) {
                document.getElementById(column).innerHTML = response.data;
            }
        }
    };
    var url = path + "/AJAX/JSON_Browse?method=getSubjectsAsHTML&facet_field=" + 
        encodeURIComponent(field) + "&query_field=" + encodeURIComponent(lookfor) +
        "&query=" + encodeURIComponent(lookfor) + ":[*+TO+*]";
    var transaction = YAHOO.util.Connect.asyncRequest('GET', url, callback, null);
}

/* The browse lists are represented as a <ul> tag containing a series of <li> tags
 * containing <a> tags.  The currently selected <a> tag is highlighted by setting
 * the active class on its <li> container.  This function deselects all of the <li>
 * tags in the link's parent <ul> container, then highlights just the specified link
 * element.
 *
 * linkToHighlight = the <a> element we want to highlight.
 */
function highlightBrowseLink(linkToHighlight)
{
    // Create shortcut to YUI library for readability:
    var yui = YAHOO.util.Dom;
    
    // Remove highlight from existing links:
    var linkContainer = yui.getAncestorByTagName(linkToHighlight, 'ul');
    if (linkContainer) {
        var children = yui.getChildren(linkContainer);
        for (var i = 0; i < children.length; i++) {
            yui.removeClass(children[i], 'active');
        }
    }
    
    // Add highlight to newly selected link:
    var ancestor = yui.getAncestorByTagName(linkToHighlight, 'li');
    if (ancestor) {
        yui.addClass(ancestor, 'active');
    }
}
