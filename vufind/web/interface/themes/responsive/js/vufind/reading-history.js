VuFind.Account.ReadingHistory = (function(){
	return {
		deletedMarkedAction: function (){
			if (confirm('The marked items will be irreversibly deleted.  Proceed?')){
				$('#readingHistoryAction').val('deleteMarked');
				$('#readingListForm').submit();
			}
			return false;
		},

		deleteAllAction: function (){
			if (confirm('Your entire reading history will be irreversibly deleted.  Proceed?')){
				$('#readingHistoryAction').val('deleteAll');
				$('#readingListForm').submit();
			}
			return false;
		},

		optOutAction: function (showError){
			if (showError){
				alert('Your reading history must be deleted before you can Opt Out.');
				return false;
			}
			$('#readingHistoryAction').val('optOut');
			$('#readingListForm').submit();
			return false;
		},

		optInAction: function (){
			$('#readingHistoryAction').val('optIn');
			$('#readingListForm').submit();
			return false;
		},

		exportListAction: function (){
			$('#readingHistoryAction').val('exportToExcel');
			$('#readingListForm').submit();
			return false;
		}
	};
}(VuFind.Account.ReadingHistory || {}));
