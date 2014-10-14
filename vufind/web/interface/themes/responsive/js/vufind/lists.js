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

		makeListPrivateAction: function (){
			$('#myListActionHead').val('makePrivate');
			$('#myListFormHead').submit();
			return false;
		},

		deleteListAction: function (){
			if (confirm("Are you sure you want to delete this list?")){
				$('#myListActionHead').val('deleteList');
				$('#myListFormHead').submit();
			}
			return false;
		},

		updateListAction: function (){
			$('#myListActionHead').val('saveList');
			$('#myListFormHead').submit();
			return false;
		},

		requestMarkedAction: function (){
			var myListFormItem = $('#myListFormItem');
			myListFormItem.attr('action', Globals.path + "/MyAccount/HoldMultiple");
			myListFormItem.submit();
			return false;
		},
		deletedMarkedListItemsAction: function (){
			if (confirm("Are you sure you want to delete the selected titles from this list?  This cannot be undone.")){
				$('#myListActionItem').val('deleteMarked');
				$('#myListFormItem').submit();
			}
			return false;
		},
		moveMarkedAction: function (){
			alert("Not implemented yet.");
			return false;
		},
		deleteAllListItemsAction: function (){
			if (confirm("Are you sure you want to delete all titles from this list?  This cannot be undone.")){
				$('#myListActionItem').val('deleteAll');
				$('#myListFormItem').submit();
			}
			return false;
		},
		emailListAction: function (id) {
			VuFind.Account.ajaxLightbox(Globals.path + '/MyAccount/EmailList/' + id);
			return false;
		},
		citeListAction: function (id) {
			VuFind.showMessage("Citations for List Titles", Globals.path + '/MyAccount/AJAX?method=getCitationFormatsForm&listId=' + id);
			return false;
		},

		SendMyListEmail: function (to, from, message, id, strings) {

			console.log('SendMyListEmail function was called.'); // plb debugging only, REMOVE_DEBUG
			var url = Globals.path + "/MyAccount/EmailList";

			//console.log('about to encode URL.');
			//var params = "method=SendEmail" + "&from=" + encodeURIComponent(from) + "&to=" + encodeURIComponent(to)
			//		+ "&message=" + encodeURIComponent(message) + "&listId=" + id;
			//console.log('params : '+params);
			//console.log('about to call sendAJAXEmail');
			//sendAJAXEmail(url, params, strings);
			// this function is deprecated. plb 10-13-14

			/*VuFind.Lists.SendMyListEmail(this.elements[&quot;to&quot;].value,
			this.elements[&quot;from&quot;].value, this.elements[&quot;message&quot;].value,this.elements[&quot;listId&quot;].value,
			{* Pass translated strings to Javascript -- ugly but necessary: * DEBUG}
			{ldelim}sending: &quot;{translate text='email_sending'}&quot;,
			success: &quot;{translate text='email_success'}&quot;,
			failure: &quot;{translate text='email_failure'}&quot;{rdelim}
			) */


			$.getJSON(url,
				{ // form inputs passed as data
					listId   : $('#emailListForm input[name="listId"]').val()
					,to      : $('#emailListForm input[name="to"]').val()
					,from    : $('#emailListForm input[name="from"]').val()
					,message : $('#emailListForm textarea[name="message"]').val()
					//,method  : 'SendEmail' //Doesn't look as if it is used.  plb 10-14-2014
				},
				function(data) {
					console.log(data); // REMOVE_DEBUG
					if (data.result) {
						VuFind.showMessage("Success", data.message);
					} else {
						VuFind.showMessage("Error", data.message);
					}
				}
			);

			console.log('Finished calling getJSON'); // REMOVE_DEBUG
		},

		batchAddToListAction: function (id){
			VuFind.Account.ajaxLightbox(Globals.path + '/MyAccount/AJAX/?method=getBulkAddToListForm&listId=' + id);
			return false;
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
		}
	};
}(VuFind.Lists || {}));