var suggestionScroller;
function getSuggestions(){
	suggestionScroller = new TitleScroller('titleScrollerSuggestion', 'Suggestion', 'suggestionList');
	
	var url = path + "/MyResearch/AJAX";
	var params = "method=GetSuggestions";
	var fullUrl = url + "?" + params;
	suggestionScroller.loadTitlesFrom(fullUrl);
}

function getListTitles(listId){
	var url = path + "/MyResearch/AJAX";
	var params = "method=GetListTitles&listId=" + listId;
	var fullUrl = url + "?" + params;
    $.ajax({
	  url: fullUrl,
	  success: function(data) {
    	var listTitles = $(data).find("ListTitles").text();
    	if (listTitles) {
        	if (listTitles.length > 0){
        		$("#listPlaceholder" + listId).html(listTitles);
        	}
        }
      }
	});
}

function changePageSize(){
	var url = window.location.href;
	if (url.match(/[&?]pagesize=\d+/)) {
		url = url.replace(/pagesize=\d+/, "pagesize=" + $("#pagesize").val());
	} else {
		if (url.indexOf("?", 0) > 0){
			url = url+ "&pagesize=" + $("#pagesize").val();
		}else{
			url = url+ "?pagesize=" + $("#pagesize").val();
		}
	}
	window.location.href = url;
}

function changeSortOption(){
	var url = window.location.href;
	if (url.match(/[&?]sort=[^=]+/)) {
		url = url.replace(/sort=[^=]+/, "sort=" + $("#sortMethod").val());
	} else {
		if (url.indexOf("?", 0) > 0){
			url = url+ "&sort=" + $("#sortMethod").val();
		}else{
			url = url+ "?sort=" + $("#sortMethod").val();
		}
	}
	window.location.href = url;
}
function suspendSelectedEContentHolds(){
	var selectedTitles = getSelectedUnavailableHolds();
	if (selectedTitles.length == 0){
		return false;
	}
	var suspendDate = '';
	if ($('#suspendDateTop').val().length > 0){
		var suspendDate = $('#suspendDateTop').val();
	}else{
		var suspendDate = $('#suspendDateBottom').val();
	}	
	
	if (suspendDate.length == 0){
		alert("Please select the date when the hold should be reactivated.");
		return false;
	}
	var url = path + '/MyResearch/MyEContent?multiAction=suspendSelected&' + selectedTitles + '&suspendDate=' + suspendDate;
	window.location = url;
	return false;
}

function getSelectedUnavailableHolds(){
	var selectedTitles = $("input.unavailableHoldSelect:checked ").map(function() {
		return $(this).attr('name') + "=" + $(this).val();
	}).get().join("&");
	if (selectedTitles.length == 0){
		var ret = confirm('You have not selected any items, process all items?');
		if (ret == true){
			selectedTitles = $("input.titleSelect").map(function() {
				return $(this).attr('name') + "=on";
			}).get().join("&");
		}
	}
	return selectedTitles;
}
