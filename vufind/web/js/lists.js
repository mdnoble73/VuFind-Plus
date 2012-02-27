function editListAction(){
	$('#listDescription').hide();
	$('#listTitle').hide();
	$('#listEditControls').show();
	$('#FavEdit').hide();
	$('#FavSave').show();
	return false;
}

function makeListPublicAction(){
	$('#myListActionHead').val('makePublic');
	$('#myListFormHead').submit();
	return false;
}

function makeListPrivateAction(showError){
	$('#myListActionHead').val('makePrivate');
	$('#myListFormHead').submit();
	return false;
}

function deleteListAction(){
	$('#myListActionHead').val('deleteList');
	$('#myListFormHead').submit();
	return false;
}

function updateListAction(){
	$('#myListActionHead').val('saveList');
	$('#myListFormHead').submit();
	return false;
}

function requestMarkedAction(){
	$('#myListFormItem').attr('action', path + "/MyResearch/HoldMultiple");
	$('#myListFormItem').submit();
	return false;
}
function deletedMarkedAction(){
	$('#myListActionItem').val('deleteMarked');
	$('#myListFormItem').submit();
	return false;
}
function moveMarkedAction(){
	alert("Not implemented yet.");
	return false;
}
function deleteAllAction(){
	$('#myListActionItem').val('deleteAll');
	$('#myListFormItem').submit();
	return false;
}
function emailListAction(id) {
	getLightbox('MyResearch', 'EmailList', id, null, 'Email list');
	return false;
}

function SendMyListEmail(to, from, message, id, strings) {
	var url = path + "/MyResearch/EmailList";
	var params = "method=SendEmail&" + "url=" + URLEncode(window.location.href) + "&" + "from=" + encodeURIComponent(from) + "&" + "to=" + encodeURIComponent(to)
	    + "&" + "message=" + encodeURIComponent(message) + "&listId=" + id;
	sendAJAXEmail(url, params, strings);
}
