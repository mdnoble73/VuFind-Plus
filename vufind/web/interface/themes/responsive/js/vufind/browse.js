VuFind.Browse = (function(){
	return {
		curPage: 1,
		curCategory: '',
		addToHomePage: function(searchId){
			VuFind.Account.ajaxLightbox(Globals.path + '/Browse/AJAX?method=getAddBrowseCategoryForm&searchId=' + searchId, true);
			return false;
		},

		initializeBrowseCategory: function(){
			// wrapper for setting events and connecting w/ VuFind.initCarousels() in base.js

			var browseCategoryCarousel = $("#browse-category-carousel");

			// resize the browse category carousel for different screen sizes
			browseCategoryCarousel.on('jcarousel:create jcarousel:reload', function () {
				var Carousel = $(this), width = Carousel.innerWidth();

				if (width > 700) {
					width = width / 4;
				} else if (width > 500) {
					width = width / 3;
				} else if (width > 400) {
					width = width / 2;
				}

				// Set Width
				Carousel.jcarousel('items').css('width', Math.floor(width) + 'px');
			});

			// connect the browse catalog functions to the jcarousel controls
			browseCategoryCarousel.on('jcarousel:targetin', 'li', function(){
				var categoryId = $(this).data('category-id');
				VuFind.Browse.changeBrowseCategory(categoryId);
			});


			if ($('#browse-category-picker .jcarousel-control-prev').css('display') != 'none') {
				// only enable if the carousel features are being used.
				// as of now, basalt & vail are not. plb 12-1-2014
				// TODO: when disabling the carousel feature is turned into an option, change this code to check that setting.

				// attach jcarousel navigation to clicking on a category
				browseCategoryCarousel.find('li').click(function(){
					$("#browse-category-carousel").jcarousel('scroll', $(this));
				});

				// Incorporate swiping gestures into the browse category selector. pascal 11-26-2014
				var scrollFactor = 15; // swipe size per item to scroll.
				browseCategoryCarousel.touchwipe({
					wipeLeft: function (dx) {
						var scrollInterval = Math.round(dx / scrollFactor); // vary scroll interval based on wipe length
						$("#browse-category-carousel").jcarousel('scroll', '+=' + scrollInterval);
					},
					wipeRight: function (dx) {
						var scrollInterval = Math.round(dx / scrollFactor); // vary scroll interval based on wipe length
						$("#browse-category-carousel").jcarousel('scroll', '-=' + scrollInterval);
					}
				});

				// implements functions for libraries not using the carousel functionality
			} else {
				// bypass jcarousel navigation on a category click
				browseCategoryCarousel.find('li').click(function(){
					$(this).trigger('jcarousel:targetin');
				});
			}

		},

		changeBrowseCategory: function(categoryTextId){
			var url = Globals.path + '/Browse/AJAX?method=getBrowseCategoryInfo&textId=' + categoryTextId,
					newLabel = $('#browse-category-'+categoryTextId+' div').first().text(); // get label from corresponding li div
			// the carousel clones these divs sometimes, so grab only the text from the first one.

			$('.browse-category').removeClass('selected');
			$('#browse-category-' + categoryTextId).addClass('selected');
			//$('.selected-browse-label-search-text').html(newLabel)
			$('.selected-browse-label-search-text').fadeOut(function(){
				$(this).html(newLabel).fadeIn()
			});


			$.getJSON(url, function(data){
				if (data.result == false){
					VuFind.showMessage("Error loading browse information", "Sorry, we were not able to find titles for that category");
				}else{
					if (data.label != newLabel) $('.selected-browse-label-search-text').html(data.label);

					VuFind.Browse.curPage = 1;
					VuFind.Browse.curCategory = data.textId;
					//$('#home-page-browse-thumbnails').html(data.records); // original
					$('.home-page-browse-thumbnails, .home-page-browse-thumbnails ~ hr').slice(1).remove();
					// remove all but the first thumbnail divs, also removes the <hr>s between the thumbnail divs

					//$('.home-page-browse-thumbnails').html(data.records);
					$('.home-page-browse-thumbnails').hide().html(data.records).fadeIn('slow');
					$('#selected-browse-search-link').attr('href', data.searchUrl);
				}
			}).fail(function(){
				VuFind.showMessage('Request Failed', 'There was an error with this AJAX Request.');
			});
			return false;
		},

		createBrowseCategory: function(){
			var url = Globals.path + "/Browse/AJAX?method=createBrowseCategory" ;
			url += "&searchId=" + $('#searchId').val();
			url += "&categoryName=" + $('#categoryName').val();
			$.getJSON(url, function(data){
				if (data.result == false){
					VuFind.showMessage("Unable to create category", data.message);
				}else{
					VuFind.showMessage("Successfully added", "This search was added to the homepage successfully.", true);
				}
			});
			return false;
		},


		//getMoreResults: function(){
		//	var url = Globals.path + '/Browse/AJAX?method=getMoreBrowseResults&textId=' + this.curCategory + "&pageToLoad=" + (this.curPage + 1);
		//	$.getJSON(url, function(data){
		//		if (data.result == false){
		//			VuFind.showMessage("Error loading browse information", "Sorry, we were not able to find titles for that category");
		//		}else{
		//			$('#home-page-browse-thumbnails').append(data.records);
		//			VuFind.Browse.curPage++;
		//		}
		//	});
		//	return false;
		//},

		getMoreResults: function(){
			var url = Globals.path + '/Browse/AJAX',
					params = {
						method : 'getMoreBrowseResults'
						,textId :  this.curCategory
						,pageToLoad : this.curPage + 1
					};
			$.getJSON(url, params, function(data){
				if (data.result == false){
					VuFind.showMessage("Error loading browse information", "Sorry, we were not able to find titles for that category");
				}else{
					var newDiv = $('<div class="home-page-browse-thumbnails row" />').hide().append(data.records);
					// Below is for when records are returned as an array
					//var newDiv = $('<div class="home-page-browse-thumbnails row" />').hide();
					//$.each(data.records, function(i, record){
					//	newDiv.append(record);
					//});
					//newDiv.before('<hr>');
					$('.home-page-browse-thumbnails').filter(':last').after(newDiv).after('<hr>');
					newDiv.fadeIn('slow');
					VuFind.Browse.curPage++;
				}
			}).fail(function(){
				VuFind.showMessage('Request Failed', 'There was an error with this AJAX Request.');
			});
			return false;
		}
		//getMoreResults: function(){
		//	var url = Globals.path + '/Browse/AJAX',
		//			params = {
		//				method : 'getMoreBrowseResults'
		//				,textId :  this.curCategory
		//				,pageToLoad : this.curPage + 1
		//			};
		//	$.getJSON(url, params, function(data){
		//		if (data.result == false){
		//			VuFind.showMessage("Error loading browse information", "Sorry, we were not able to find titles for that category");
		//		}else{
		//			var browseDiv = $('#home-page-browse-thumbnails'),
		//					columns =  parseInt(browseDiv.css('column-count')) || parseInt(browseDiv.css('-moz-column-count')) || parseInt(browseDiv.css('-webkit-column-count')) || 6,
		//					tiles, totalTiles, itemsPerCol, // define these variables so that they exist for each iteration of the callback function below
		//					unevenCols = 0; // (assuming larger columns will be on the left)
		//			$.each(data.records, function(i, record){
		//				var colToAddTo = unevenCols + i%columns + 1;
		//				if (colToAddTo == 1 || tiles == undefined) {
		//					// recalculate after each row that has been added.
		//					tiles = browseDiv.children('div.browse-title');
		//					totalTiles = tiles.length;
		//					itemsPerCol = totalTiles/columns;
		//					unevenCols = totalTiles % columns;  //(0 when even)
		//					if (unevenCols) colToAddTo = unevenCols + i%columns + 1; // offset insert point when the columns are uneven.
		//				}
		//				var objectToAddAfter = itemsPerCol*colToAddTo - 1;
		//				record = $(record).hide();
		//				tiles.eq(objectToAddAfter).after(record);
		//			});
		//			//browseDiv.children('div.browse-title').filter(':hidden').show(2000);
		//			var temp = browseDiv.children('div.browse-title').filter(':hidden');
		//
		//		temp.fadeIn('slow');
		//			VuFind.Browse.curPage++;
		//		}
		//	}).fail(function(){
		//		VuFind.showMessage('Request Failed', 'There was an error with this AJAX Request.');
		//	});
		//	return false;
		//}

		// development version //
		//getMoreResults: function(){
		//	var url = Globals.path + '/Browse/AJAX',
		//			params = {
		//				method : 'getMoreBrowseResults'
		//				,textId :  this.curCategory
		//				,pageToLoad : this.curPage + 1
		//			};
		//	$.getJSON(url, params, function(data){
		//		if (data.result == false){
		//			VuFind.showMessage("Error loading browse information", "Sorry, we were not able to find titles for that category");
		//		}else{
		//			var browseDiv = $('#home-page-browse-thumbnails'),
		//					columns =  parseInt(browseDiv.css('column-count')) || parseInt(browseDiv.css('-moz-column-count')) || parseInt(browseDiv.css('-webkit-column-count')) || 6;
		//
		//			// verbose way for the above
		//			//var columns =  parseInt(browseDiv.css('column-count'));
		//			//if (!columns) {
		//			//	columns = parseInt(browseDiv.css('-moz-column-count'));
		//			//	if (!columns) {
		//			//		columns = parseInt(browseDiv.css('-webkit-column-count'));
		//			//		if (!columns) columns = 6;
		//			//	}
		//			//}
		//			console.log('columns : '+columns);
		//			//console.log(data.records);
		//
		//			var tiles, totalTiles, itemsPerCol, // define these variables so that they exist for each iteration of the callback function below
		//					unevenCols = 0; // (assuming larger columns will be on the left)
		//			$.each(data.records, function(i, record){
		//				var colToAddTo = unevenCols + i%columns + 1;
		//				if (colToAddTo == 1 || tiles == undefined) {
		//					// recalculate after each row that has been added.
		//					tiles = browseDiv.children('div.browse-title');
		//					totalTiles = tiles.length;
		//					itemsPerCol = totalTiles/columns;
		//					unevenCols = totalTiles % columns;  //(0 when even)
		//					if (unevenCols) colToAddTo = unevenCols + i%columns + 1; // // offset insert point when the columns are uneven.
		//				}
		//				var objectToAddAfter = itemsPerCol*colToAddTo - 1;
		//				console.log('object to add to index: '+objectToAddAfter);
		//				//console.log(tiles.eq(objectToAddAfter));
		//
		//				tiles.eq(objectToAddAfter).after(record);
		//			});
		//			VuFind.Browse.curPage++;
		//		}
		//	}).fail(function(){
		//		VuFind.showMessage('Request Failed', 'There was an error with this AJAX Request.');
		//	});
		//	return false;
		//}
	}
}(VuFind.Browse || {}));
