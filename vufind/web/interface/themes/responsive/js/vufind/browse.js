VuFind.Browse = (function(){
	return {
		curPage: 1,
		curCategory: '',
		changeBrowseCategory: function(categoryTextId){
			var url = Globals.path + '/Browse/AJAX?method=getBrowseCategoryInfo&textId=' + categoryTextId;
			$.getJSON(url, function(data){
				if (data.result == false){
					VuFind.showMessage("Error loading browse information", "Sorry, we were not able to find titles for that category");
				}else{
					var label = data.label;
					$('.selected-browse-label-text').html(label);
					$('.selected-browse-label-search-text').html(label);
					$('#home-page-browse-thumbnails').html(data.records);
					$('#selected-browse-search-link').attr('href', data.searchUrl);
					VuFind.Browse.curPage = 1;
					VuFind.Browse.curCategory = data.textId;
				}
			});
		},

		getMoreResults: function(){
			var url = Globals.path + '/Browse/AJAX?method=getMoreBrowseResults&textId=' + this.curCategory + "&pageToLoad=" + (this.curPage + 1);
			$.getJSON(url, function(data){
				if (data.result == false){
					VuFind.showMessage("Error loading browse information", "Sorry, we were not able to find titles for that category");
				}else{
					$('#home-page-browse-thumbnails').append(data.records);
					VuFind.Browse.curPage++;
				}
			});
			return false;
		}
	}
}(VuFind.Browse || {}));