VuFind.Lists = (function(){
	return {
		editListAction: function (){
			$('#listDescription').hide();
			$('#listTitle').hide();
			$('#listEditControls').show();
			$('#FavEdit').hide();
			$('#FavSave').show();
			return false;
		},

		makeListPublicAction: function (){
			$('#myListActionHead').val('makePublic');
			$('#myListFormHead').submit();
			return false;
		},

		makeListPrivateAction: function (showError){
			$('#myListActionHead').val('makePrivate');
			$('#myListFormHead').submit();
			return false;
		},

		deleteListAction: function (){
			$('#myListActionHead').val('deleteList');
			$('#myListFormHead').submit();
			return false;
		},

		updateListAction: function (){
			$('#myListActionHead').val('saveList');
			$('#myListFormHead').submit();
			return false;
		},

		requestMarkedAction: function (){
			$('#myListFormItem').attr('action', path + "/MyResearch/HoldMultiple");
			$('#myListFormItem').submit();
			return false;
		},
		deletedMarkedListItemsAction: function (){
			$('#myListActionItem').val('deleteMarked');
			$('#myListFormItem').submit();
			return false;
		},
		moveMarkedAction: function (){
			alert("Not implemented yet.");
			return false;
		},
		deleteAllListItemsAction: function (){
			$('#myListActionItem').val('deleteAll');
			$('#myListFormItem').submit();
			return false;
		},
		emailListAction: function (id) {
			ajaxLightbox(path + '/MyResearch/EmailList/' + id);
			return false;
		},
		citeListAction: function (id) {
			VuFind.showMessage("Citations for List Titles", path + '/MyResearch/AJAX?method=getCitationFormatsForm&listId=' + id);
			return false;
		},

		SendMyListEmail: function (to, from, message, id, strings) {
			var url = path + "/MyResearch/EmailList";
			var params = "method=SendEmail&" + "url=" + URLEncode(window.location.href) + "&" + "from=" + encodeURIComponent(from) + "&" + "to=" + encodeURIComponent(to)
					+ "&" + "message=" + encodeURIComponent(message) + "&listId=" + id;
			sendAJAXEmail(url, params, strings);
		},
		batchAddToListAction: function (id){
			ajaxLightbox(path + '/MyResearch/AJAX/?method=getBulkAddToListForm&listId=' + id);
			return false;
		},

		changeList: function (){
			var availableLists = $("#availableLists");
			window.location = path + "/MyResearch/MyList/" + availableLists.val();
		},

		printListAction: function (){
			window.print();
			return false;
		},

		importListsFromClassic: function (){
			if (confirm("This will import any lists you had defined in the old catalog.  This may take several minutes depending on the size of your lists. Are you sure you want to continue?")){
				window.location = path + "/MyResearch/ImportListsFromClassic";
			}
			return false;
		}
	};
}(VuFind.Lists || {}));