VuFind.Browse = (function(){
	return {
		changeBrowseCategory: function(categoryTextId){
			var url = Globals.path + '/Browse/AJAX?method=getBrowseCategoryInfo&textId=' + categoryTextId;
			$.getJSON(url, function(data){
				if (data.result == false){
					VuFind.showMessage("Error loading browse information", "Sorry, we were not able to find titles for that category");
				}else{
					var label = data.label;
					$('.selected-browse-label-text').html(label);
					$('#home-page-browse-thumbnails').html(data.records);
				}
			});
		}
	}
}(VuFind.Browse || {}));