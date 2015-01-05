VuFind.Browse = (function(){
	return {
		curPage: 1,
		curCategory: '',
		//selectedCategory: '',
		addToHomePage: function(searchId){
			VuFind.Account.ajaxLightbox(Globals.path + '/Browse/AJAX?method=getAddBrowseCategoryForm&searchId=' + searchId, true);
			return false;
		},

		changeBrowseCategory: function(categoryTextId){
			var url = Globals.path + '/Browse/AJAX?method=getBrowseCategoryInfo&textId=' + categoryTextId,
					newLabel = $('#browse-category-'+categoryTextId+' div').text(); // get label from corresponding li div

			$('.browse-category').removeClass('selected');
			$('#browse-category-' + categoryTextId).addClass('selected');
			$('.selected-browse-label-text, .selected-browse-label-search-text').html(newLabel);

			$.getJSON(url, function(data){
				if (data.result == false){
					VuFind.showMessage("Error loading browse information", "Sorry, we were not able to find titles for that category");
				}else{
					$('.selected-browse-label-text, .selected-browse-label-search-text').html(data.label);
					VuFind.Browse.curPage = 1;
					VuFind.Browse.curCategory = data.textId;
					$('#home-page-browse-thumbnails').html(data.records);
					$('#selected-browse-search-link').attr('href', data.searchUrl);
				}
			});
			return false;
		},

		createBrowseCategory: function(){
			var url = Globals.path + "/Browse/AJAX?method=createBrowseCategory" ;
			url += "&searchId=" + $('#searchId').val();
			url += "&categoryName=" + $('#categoryName').val();
			$.getJSON(url, function(data){
				//console.log('Got a Response for getting covers');
				if (data.result == false){
					VuFind.showMessage("Unable to create category", data.message);
				}else{
					VuFind.showMessage("Successfully added", "This search was added to the homepage successfully.", true);
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

$(document).ready(function() {
	var browseCategoryCarousel = $("#browse-category-carousel");

	// resize the browse category carousel for different screen sizes
	browseCategoryCarousel.on('jcarousel:create jcarousel:reload', function () {
		var element = $(this), width = element.innerWidth();

		if (width > 700) {
			width = width / 4;
		} else if (width > 5500) {
			width = width / 3;
		} else if (width > 400) {
			width = width / 2;
		}

		element.jcarousel('items').css('width', width + 'px');
	})

	// connect the browse catalog functions to the jcarousel controls
	.on('jcarousel:targetin', 'li', function(){
				console.log('targetin called');
		var categoryId = $(this).data('category-id');
		//VuFind.Browse.selectedCategory = $(this).data('category-id');
		VuFind.Browse.changeBrowseCategory(categoryId);
	})
	//.on('jcarousel:scroll', 'li', function(){
	//	console.log('scroll event called. with li.');
	//	console.log('data-id now is: '+ $(this).data('category-id'));
	//	$('.browse-category').removeClass('selected');
	//})
	//.on('jcarousel:scrollend', function(event, carousel) {
	//			console.log('scroll-end event called.');
	//
	//})
	//.on('jcarousel:animate', function(event, carousel) {
	//	console.log('animate event called.');
	//})
	//.on('jcarousel:animateend', function(event, carousel) {
	//	console.log('animate-end event called.');
	//})
	;

	if ($('#browse-category-picker .jcarousel-control-prev').css('display') != 'none') {
		// only enable if the carousel features are being used.
		// as of now, basalt & vail are not. plb 12-1-2014
		// TODO: when disabling the carousel feature is turned into an option, change this code to check that setting.

		// attach jcarousel navigation to clicking on a category
		browseCategoryCarousel.find('li').click(function(){
			browseCategoryCarousel.jcarousel('scroll', $(this));
		});

		// Incorporate swiping gestures into the browse category selector. pascal 11-26-2014
		var scrollFactor = 15; // swipe size per item to scroll.
		browseCategoryCarousel.touchwipe({
			wipeLeft: function (dx) {
				var scrollInterval = Math.round(dx / scrollFactor); // vary scroll interval based on wipe length
				browseCategoryCarousel.jcarousel('scroll', '+=' + scrollInterval);
			},
			wipeRight: function (dx) {
				var scrollInterval = Math.round(dx / scrollFactor); // vary scroll interval based on wipe length
				browseCategoryCarousel.jcarousel('scroll', '-=' + scrollInterval);
			}
		});

	// implements functions for libraries not using the carousel functionality
	} else {
		// bypass jcarousel navigation on a category click
		browseCategoryCarousel.find('li').click(function(){
			$(this).trigger('jcarousel:targetin');
		});
	}


});