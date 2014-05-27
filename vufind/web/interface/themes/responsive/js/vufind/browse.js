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
			return false;
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

$(document).ready(function(){
	var browseCategoryCarousel = $("#browse-category-carousel");
	browseCategoryCarousel.on('jcarousel:create jcarousel:reload', function() {
		var element = $(this), width = element.innerWidth();

		if (width > 700) {
			width = width / 4;
		} else if (width > 5500) {
			width = width / 3;
		} else if (width > 400) {
			width = width / 2;
		}


		element.jcarousel('items').css('width', width + 'px');
	});
	browseCategoryCarousel.on('jcarousel:targetin', 'li', function(event, carousel){
		var categoryId = $(this).data('category-id');
		VuFind.Browse.changeBrowseCategory(categoryId);
	});

});