VuFind.Lists = (function(){
	return {
		editListAction: function (){
			$('#listDescription,#listTitle,#FavEdit').hide();
			$('#listEditControls,#FavSave').show();
			return false;
		},
		//editListAction: function (){
		//	$('#listDescription').hide();
		//	$('#listTitle').hide();
		//	$('#listEditControls').show();
		//	$('#FavEdit').hide();
		//	$('#FavSave').show();
		//	return false;
		//},

		submitListForm: function(action){
			$('#myListActionHead').val(action);
			$('#myListFormHead').submit();
			return false;
		},

		makeListPublicAction: function (){
			return this.submitListForm('makePublic');
			//$('#myListActionHead').val('makePublic');
			//$('#myListFormHead').submit();
			//return false;
		},

		makeListPrivateAction: function (){
			return this.submitListForm('makePrivate');
			//$('#myListActionHead').val('makePrivate');
			//$('#myListFormHead').submit();
			//return false;
		},

		deleteListAction: function (){
			if (confirm("Are you sure you want to delete this list?")){
				this.submitListForm('deleteList');
				//$('#myListActionHead').val('deleteList');
				//$('#myListFormHead').submit();
			}
			return false;
		},

		updateListAction: function (){
			return this.submitListForm('saveList');
			//$('#myListActionHead').val('saveList');
			//$('#myListFormHead').submit();
			//return false;
		},

/* Multiple Holds deprecated. plb 12-04-2014
		requestMarkedAction: function (){
			var myListFormItem = $('#myListFormItem');
			myListFormItem.attr('action', Globals.path + "/MyAccount/HoldMultiple");
			myListFormItem.submit();
			return false;
		},
 */
		/* No References to these functions for now, commented out plb 5-19-2014
		deletedMarkedListItemsAction: function (){
			if (confirm("Are you sure you want to delete the selected titles from this list?  This cannot be undone.")){
				this.submitListForm('deleteMarked');
				//$('#myListActionItem').val('deleteMarked');
				//$('#myListFormItem').submit();
			}
			return false;
		},
		moveMarkedAction: function (){
			alert("Not implemented yet.");
			return false;
		}, */

		deleteAllListItemsAction: function (){
			if (confirm("Are you sure you want to delete all titles from this list?  This cannot be undone.")){
				this.submitListForm('deleteAll');
				//$('#myListActionItem').val('deleteAll');
				//$('#myListFormItem').submit();
			}
			return false;
		},

		emailListAction: function (listId) {
			var urlToDisplay = Globals.path + '/MyAccount/AJAX';
			VuFind.showMessage("Loading, please wait", "...");
			$.getJSON(urlToDisplay, {
					method  : 'getEmailMyListForm'
					,listId : listId
				},
					function(data){
						VuFind.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			});
			return false;
		},

		SendMyListEmail: function () {
			var url = Globals.path + "/MyAccount/AJAX";

			$.getJSON(url,
				{ // form inputs passed as data
					listId   : $('#emailListForm input[name="listId"]').val()
					,to      : $('#emailListForm input[name="to"]').val()
					,from    : $('#emailListForm input[name="from"]').val()
					,message : $('#emailListForm textarea[name="message"]').val()
					,method  : 'sendMyListEmail' // serverside method
				},
				function(data) {
					if (data.result) {
						VuFind.showMessage("Success", data.message);
					} else {
						VuFind.showMessage("Error", data.message);
					}
				}
			);
		},

		citeListAction: function (id) {
			return VuFind.Account.ajaxLightbox("Citations for List Titles", Globals.path + '/MyAccount/AJAX?method=getCitationFormatsForm&listId=' + id);
			//return false;
			//TODO: ajax call not working
		},

		batchAddToListAction: function (id){
			return VuFind.Account.ajaxLightbox(Globals.path + '/MyAccount/AJAX/?method=getBulkAddToListForm&listId=' + id);
			//return false;
		},

		processBulkAddForm: function(){
			$("#bulkAddToList").submit();
		},

		changeList: function (){
			var availableLists = $("#availableLists");
			window.location = Globals.path + "/MyAccount/MyList/" + availableLists.val();
		},

		printListAction: function (){
			window.print();
			return false;
		},

		importListsFromClassic: function (){
			if (confirm("This will import any lists you had defined in the old catalog.  This may take several minutes depending on the size of your lists. Are you sure you want to continue?")){
				window.location = Globals.path + "/MyAccount/ImportListsFromClassic";
			}
			return false;
		}//,

		//setDefaultSort: function(selectedElement, selectedValue) {
		//	$('#default-sort').val(selectedValue);
		//	$('#default-sort + div>ul li').css('background-color', 'inherit');
		//	$(selectedElement).css('background-color', 'gray');
		//}
	};
}(VuFind.Lists || {}));