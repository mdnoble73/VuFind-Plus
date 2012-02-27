function deletedMarkedAction(){
	if (confirm('The marked items will be irreversibly deleted.  Proceed?')){
		$('#readingHistoryAction').val('deleteMarked');
		$('#readingListForm').submit();
	}
	return false;
};

function deleteAllAction(){
	if (confirm('Your entire reading history will be irreversibly deleted.  Proceed?')){
		$('#readingHistoryAction').val('deleteAll');
		$('#readingListForm').submit();
	}
	return false;
};

function optOutAction(showError){
	if (showError){
		alert('Your reading history must be deleted before you can Opt Out.');
		return false;
	}
	$('#readingHistoryAction').val('optOut');
	$('#readingListForm').submit();
	return false;
};

function optInAction(){
	$('#readingHistoryAction').val('optIn');
	$('#readingListForm').submit();
	return false;
};

function exportListAction(){
	$('#readingHistoryAction').val('exportToExcel');
	$('#readingListForm').submit();
	return false;
};

