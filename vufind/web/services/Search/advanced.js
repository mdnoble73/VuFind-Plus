var nextGroupNumber = 0;
var groupSearches = new Array();

function addSearch(group, term, field)
{
	if (term  == undefined) {term  = '';}
	if (field == undefined) {field = '';}

	// Keep form content
	//protectForm();

	var newSearch = "";

	newSearch += "<div class='advRow'>";
	// Label
	if (groupSearches[group] == 0) {
			newSearch += "<div class='searchLabel'>" + searchLabel + " :</div>";
	} else {
			newSearch += "<div class='searchLabel'>&nbsp;</div>";
	}
	// Terms
	newSearch += "<div class='terms'><input type='text' name='lookfor" + group + "[]' size='50' value='" + jsEntityEncode(term) + "'></div>";

	// Field
	newSearch += "<div class='field'>" + searchFieldLabel + " ";
	newSearch += "<select name='type" + group + "[]'>";
	for (key in searchFields) {
			newSearch += "<option value='" + key + "'";
			if (key == field) {
					newSearch += " selected='selected'";
			}
			newSearch += ">" + searchFields[key] + "</option>";
	}
	newSearch += "</select>";
	newSearch += "</div>";

	// Handle floating nonsense
	newSearch += "<span class='clearer'></span>";
	newSearch += "</div>";

	// Done
	var searchHolder = $('#group' + group + 'SearchHolder');
	searchHolder.append(newSearch);

	// Actual value doesn't matter once it's not zero.
	groupSearches[group]++;
}

function addGroup(firstTerm, firstField, join)
{
	if (firstTerm  == undefined) {firstTerm  = '';}
	if (firstField == undefined) {firstField = '';}
	if (join       == undefined) {join       = '';}

	// Keep form content
	//protectForm();

	var newGroup = "";
	newGroup += "<div id='group" + nextGroupNumber + "' class='group group" + (nextGroupNumber % 2) + " well well-sm'>";

	newGroup += "<div class='groupSearchDetails'>";
	// Boolean operator drop-down
	newGroup += "<div class='join'>" + searchMatch + " : ";
	newGroup += "<select name='bool" + nextGroupNumber + "[]'>";
	for (key in searchJoins) {
			newGroup += "<option value='" + key + "'";
			if (key == join) {
					newGroup += " selected='selected'";
			}
			newGroup += ">" + searchJoins[key] + "</option>";
	}
	newGroup += "</select>";
	newGroup += "</div>";
	// Delete link
	newGroup += "<a href='javascript:void(0);' class='delete btn btn-sm btn-warning' id='delete_link_" + nextGroupNumber + "' onclick='deleteGroupJS(this);'>" + deleteSearchGroupString + "</a>";
	newGroup += "</div>";

	// Holder for all the search fields
	newGroup += "<div id='group" + nextGroupNumber + "SearchHolder' class='groupSearchHolder'></div>";
	// Add search term link
	newGroup += "<div class='addSearch'><a href='javascript:void(0);' class='add btn btn-sm btn-default' id='add_search_link_" + nextGroupNumber + "' onclick='addSearchJS(this);'>" + addSearchString + "</a></div>";

	newGroup += "</div>";

	// Set to 0 so adding searches knows
	// which one is first.
	groupSearches[nextGroupNumber] = 0;

	// Add the new group into the page
	var search = $('#searchHolder');
	search.append(newGroup);

	// Add the first search field
	addSearch(nextGroupNumber, firstTerm, firstField);
	// Keep the page in order
	reSortGroups();

	// Pass back the number of this group
	return nextGroupNumber - 1;
}


// Fired by onclick event
function deleteGroupJS(elem)
{
	$(elem).parents('.group').remove();
	reSortGroups();
	return false;
}

// Fired by onclick event
function addSearchJS(group)
{
	var groupNum = group.id.replace("add_search_link_", "");
	addSearch(groupNum);
	return false;
}

function reSortGroups()
{
	// Loop through all groups
	var groups = 0;
	$('#searchHolder').children().each(function(){
		if (this.id != undefined) {
			if (this.id != 'group'+groups) {
				reNumGroup(this, groups);
			}
			groups++;
		}
	});
	nextGroupNumber = groups;

	// Hide some group-related controls if there is only one group:
	if (nextGroupNumber == 1){
		$('#groupJoin').show();
	}else{
		$('#groupJoin').hide();
	}

	// Hide Delete when only one search group is present
	$('#delete_link_0').css('display', (nextGroupNumber == 1 ? 'none' : 'inline') );

	// If the last group was removed, add an empty group
	if (nextGroupNumber == 0) {
			addGroup();
	}
}

function reNumGroup(oldGroup, newNum)
{
	// Keep the old details for use
	var oldId  = oldGroup.id;
	var oldNum = oldId.substring(5, oldId.length);
	// Which alternating row we're on
	var alt = newNum % 2;

	// Make sure the function was called correctly
	if (oldNum != newNum) {
		// Set the new details
		$(oldGroup).attr({
			id:"group" + newNum,
			class:"group group" + newNum % 2
		});

		// Update the delete link with the new ID
		$('.delete', oldGroup).attr('id', "delete_link_" + newNum);

		// Update the bool[] parameter number
		$('[name="bool' + oldNum + '[]"]', oldGroup).attr('name', 'bool' + newNum + '[]');

		// Update the add term link with the new ID
		$('.add', oldGroup).attr('id', 'add_search_link_' + newNum);

		// Update search holder ID
		var sHolder = $('.groupSearchHolder', oldGroup).attr('id', 'group' + newNum + 'SearchHolder');

		// Update all lookfor[] and type[] parameters
		$('.terms', sHolder).attr('name', 'lookfor' + newNum + '[]');
		$('.field', sHolder).attr('name', 'type' + newNum + '[]');


	}
}



// Only IE will keep the form values in tact
// after modifying innerHTML unless you run this
function protectForm()
{
	var e = $('#advSearchForm').elements;
	var len = e.length;
	var j, jlen;

	for (var i = 0; i < len; i++) {
			if (e[i].value != e[i].getAttribute('value')) {
					e[i].setAttribute('value', e[i].value);
			}
			if (e[i].type == 'select-one' && e[i].selectedIndex > 0) {
					jlen = e[i].options.length;
					for (j = 0; j < jlen; j++) {
							if (e[i].selectedIndex == j) {
									e[i].options[j].setAttribute('selected', 'selected');
							} else {
									e[i].options[j].removeAttribute('selected');
							}
					}
			}
	}
}

// Match all checkbox filters to the 'all' box
function filterAll(element) {
	//$('[name="filter[]"]', '#advSearchForm').prop('checked', $(element).is(':checked'));
		// not in advSearchForm, could be an issue.
	$('[name="filter[]"]').prop('checked', $(element).is(':checked'));
}

function jsEntityEncode(str)
{
	var new_str = str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
	return new_str;
}