function ShowLoadMessage(elem){
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

	var url = path + "/Browse/AJAX?method=GetOptions&query=" + query + "&field=" + field;
	if (facetPrefix) {
		url += '&facet_prefix=' + facetPrefix;
	}
	
	$.ajax({
		url: url,
		success: function(data) {
		var options = data.AJAXResponse[field];
		if (options) {
			var responseHTML = '';
			for (i=0; i<options.length; i++) {
				var facetText = options[i][0];
				// Skip blank strings:
				if (!facetText)
					continue;
				var facetCount = options[i][1];

				if (nextElem) {
					responseHTML += '<li>' +
									'<a style="float: right; font-size:70%;" href="' + path + '/Search/Results?lookfor=%22' + encodeURIComponent(facetText) + '%22&type=' + field + '&filter[]=' + query + '">View Records</a>' +
									'<a href="" onclick="highlightBrowseLink(\'' + elem + '\', this); LoadOptions(\'' + field + ':%22' + encodeURIComponent(facetText) + '%22+AND+' + query + '\', \'' + nextField + '\', \'' + nextElem + '\'); return false;">' +
									jsEntityEncode(facetText) + ' (' + facetCount + ')</a>' +
									'</li>';
				} else {
					// Final Column
					responseHTML += '<li>' +
									'<a style="float: right; font-size:70%;" href="' + path + '/Search/Results?lookfor=%22' + encodeURIComponent(facetText) + '%22&type=' + field + '&filter[]=' + query + '">View Records</a>' +
									'<a href="' + path + '/Search/Results?lookfor=%22' + encodeURIComponent(facetText) + '%22&type=' + field + '&filter[]=' + query + '">' +
									jsEntityEncode(facetText) + ' (' + facetCount + ')</a>' +
									'</li>';
				}
			}
			document.getElementById(elem).innerHTML = responseHTML;
		}
	}
	});
}

function LoadAlphabet(field, column, lookfor, includeNumbers) {
	// Set up list of initial characters (may be alphabetical or alphanumeric):
	var letters = [];
	if (includeNumbers)
		letters = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
	for (var i=0; i<26; i++) {
		letters[letters.length] = String.fromCharCode(65 + i);
	}
	
	// Build the HTML for the list:
	var responseHTML = '';
	for (var i=0; i<letters.length; i++) {
		var facetText = letters[i];
		responseHTML += '<li>' +
						'<a href="" onclick="highlightBrowseLink(\'' + column + '\', this); LoadOptions(\'' + lookfor + ':' + encodeURIComponent(facetText) + '*\', \'' + lookfor + '\', \'list4\', null, null, \'' + encodeURIComponent(facetText) + '\'); return false;">' +
						jsEntityEncode(facetText) + '</a>' +
						'</li>';
	}
	document.getElementById(column).innerHTML = responseHTML;
}

function LoadSubject(field, column, lookfor)
{
	ShowLoadMessage(column);

	var url = path + "/Browse/AJAX?method=GetSubjects&field=" + field + "&query=" + lookfor + ":[*+TO+*]";
	$.ajax({
		url: url,
		success: function(data) {
			var options = data.AJAXResponse[field];
			if (options) {
				var responseHTML = '';
				for (i=0; i<options.length; i++) {
					var facetText = options[i][0];
					// Skip blank strings:
					if (!facetText)
						continue;
					var facetCount = options[i][1];
					responseHTML += '<li>' +
									'<a style="float: right; font-size:70%;" href="' + path + '/Search/Results?lookfor=%22' + encodeURIComponent(facetText) + '%22&type=' + field + '">View Records</a>' +
									'<a href="" onclick="highlightBrowseLink(\'' + column + '\', this); LoadOptions(\'' + field + ':%22' + encodeURIComponent(facetText) + '%22\', \'' + lookfor + '\', \'list4\'); return false;">' +
									jsEntityEncode(facetText) + ' (' + facetCount + ')</a>' +
									'</li>';
				}
				document.getElementById(column).innerHTML = responseHTML;
			}
		}
	});
}

/* The browse lists are represented as a <ul> tag containing a series of <li> tags
 * containing <a> tags.  The currently selected <a> tag is highlighted by setting
 * the active class on its <li> container.  This function deselects all of the <li>
 * tags in a specified <ul> container, then highlights just the specified link element.
 *
 * linkContainerID = the ID of the <ul> tag containing the relevant set of links.
 * linkToHighlight = the <a> element we want to highlight.
 */
function highlightBrowseLink(linkContainerID, linkToHighlight)
{
	// Remove highlight from existing links:
	$("#" + linkContainerID + " > ul").children().removeClass('active');
	
	// Add highlight to newly selected link:
	$(linkToHighlight).parent().addClass('active');
	
}