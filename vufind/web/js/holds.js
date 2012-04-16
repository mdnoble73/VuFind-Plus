function updateSelectedHolds(){
	var selectedTitles = getSelectedTitles();
	if (selectedTitles.length == 0){
		return false;
	}
	var newLocation = $('select:[name=withSelectedLocation]').val();
	var url = path + '/MyResearch/Holds?multiAction=updateSelected&location=' + newLocation + "&" + selectedTitles;
	window.location = url;
	return false;
}
function cancelSelectedHolds(){
	var selectedTitles = getSelectedTitles();
	if (selectedTitles.length == 0){
		return false;
	}
	var url = path + '/MyResearch/Holds?multiAction=cancelSelected&' + selectedTitles;
	window.location = url;
	return false;
}
function freezeSelectedHolds(){
	var selectedTitles = getSelectedTitles();
	if (selectedTitles.length == 0){
		return false;
	}
	var suspendDate = '';
	//Check to se whether or not we are using a suspend date.
	if ($('#suspendDateTop').length){
		if ($('#suspendDateTop').val().length > 0){
			var suspendDate = $('#suspendDateTop').val();
		}else{
			var suspendDate = $('#suspendDateBottom').val();
		}	
		
		if (suspendDate.length == 0){
			alert("Please select the date when the hold should be reactivated.");
			return false;
		}
		var url = path + '/MyResearch/Holds?multiAction=freezeSelected&' + selectedTitles + '&suspendDate=' + suspendDate;
		window.location = url;
	}else{
		var url = path + '/MyResearch/Holds?multiAction=freezeSelected&' + selectedTitles + '&suspendDate=' + suspendDate;
		window.location = url;
	}
	return false;
}
function thawSelectedHolds(){
	var selectedTitles = getSelectedTitles();
	if (selectedTitles.length == 0){
		return false;
	}
	var url = path + '/MyResearch/Holds?multiAction=thawSelected&' + selectedTitles;
	window.location = url;
	return false;
}
function getSelectedTitles(){
	var selectedTitles = $("input.titleSelect:checked ").map(function() {
		return $(this).attr('name') + "=" + $(this).val();
	}).get().join("&");
	if (selectedTitles.length == 0){
		var ret = confirm('You have not selected any items, process all items?');
		if (ret == true){
			selectedTitles = $("input.titleSelect").map(function() {
				return $(this).attr('name') + "=on";
			}).get().join("&");
			$('.titleSelect').attr('checked', 'checked');
		}
	}
	return selectedTitles;
}
function renewSelectedTitles(){
	var selectedTitles = getSelectedTitles();
	$('#renewForm').submit()
	return false;
}