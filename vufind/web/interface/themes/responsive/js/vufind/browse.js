VuFind.Browse = (function(){
	return {
		curPage: 1,
		curCategory: '',
		browseMode: 'covers',
		//opac: false, // true prevents browser storage of browse mode // Moved to Globals
		browseModeClasses: { // browse mode to css class correspondence
			covers:'home-page-browse-thumbnails',
			grid:'home-page-browse-lists' //TODO Rename class
		},

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
					width /= 4;
				} else if (width > 500) {
					width /= 3;
				} else if (width > 400) {
					width /= 2;
				}
				Carousel.jcarousel('items').css('width', Math.floor(width) + 'px');// Set Width
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

		toggleBrowseMode : function(selectedMode){
			var mode = this.browseModeClasses.hasOwnProperty(selectedMode) ? selectedMode : this.browseMode, // check that selected mode is a valid option
					categoryTextId = this.curCategory || $('#browse-category-carousel .selected').data('category-id');
			this.browseMode = mode; // set the mode officially
			if (!Globals.opac && VuFind.hasLocalStorage() ) { // store setting in browser if not an opac computer
				window.localStorage.setItem('browseMode', this.browseMode);
			}
			return this.changeBrowseCategory(categoryTextId); // re-load the browse category
		},

		changeBrowseCategory: function(categoryTextId){
			var url = Globals.path + '/Browse/AJAX',
					params = {
						method : 'getBrowseCategoryInfo'
						,textId : categoryTextId || VuFind.Browse.curCategory
						,browseMode : this.browseMode
					},
					classes = (function(){ // return list of all associated css classes (class list can be expanded without changing this code.)
						var str = '', object = VuFind.Browse.browseModeClasses;
						for (property in object) { str += object[property]+' ' }
						return str;
					})(),
					selectedClass = this.browseModeClasses[this.browseMode],

					newLabel = $('#browse-category-'+categoryTextId+' div').first().text(); // get label from corresponding li div
			// the carousel clones these divs sometimes, so grab only the text from the first one.

			// Set selected Carousel
			$('.browse-category').removeClass('selected');
			$('#browse-category-' + categoryTextId).addClass('selected');

			// Set the new browse category label (below the carousel)
			$('.selected-browse-label-search-text').fadeOut(function(){
				$(this).html(newLabel).attr('href', '').fadeIn()
			});

			// hide current results while fetching new results
			$('#home-page-browse-results').children().fadeOut(function(){
				$('#home-page-browse-results').children().slice(1).remove(); // remove all but the first div, also removes the <hr>s between the thumbnail divs
				$('#home-page-browse-results div.row').removeClass(classes) // remove all browse mode classes
						.addClass(selectedClass); // add selected browse mode class
			});

			//if (VuFind.Browse.reload) params.reload = ''; // Reload browse category
			$.getJSON(url, params, function(data){
				if (data.result == false){
					VuFind.showMessage("Error loading browse information", "Sorry, we were not able to find titles for that category");
				}else{
					//if (data.label != newLabel)
						$('.selected-browse-label-search-text').html(data.label);

					VuFind.Browse.curPage = 1;
					VuFind.Browse.curCategory = data.textId;
					$('#home-page-browse-results div.row') //.hide() // should be the first div only
							.html(data.records).fadeIn('slow');

					$('#selected-browse-search-link').attr('href', data.searchUrl);
				}
			}).fail(function(){
				VuFind.showMessage('Request Failed', 'There was an error with this AJAX Request.');
				$('#home-page-browse-results div').html('').show(); // should be first div
				//$('.home-page-browse-thumbnails').html('').show();
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

		getMoreResults: function(){
			var url = Globals.path + '/Browse/AJAX',
					params = {
						method : 'getMoreBrowseResults'
						,textId :  this.curCategory
						,pageToLoad : this.curPage + 1
						,browseMode : this.browseMode
					},
					divClass = this.browseModeClasses[this.browseMode];
			$.getJSON(url, params, function(data){
				if (data.result == false){
					VuFind.showMessage("Error loading browse information", "Sorry, we were not able to find titles for that category");
				}else{
					var newDiv = $('<div class="'+divClass+' row" />').hide().append(data.records);
					$('.'+divClass).filter(':last').after(newDiv).after('<hr>');
					newDiv.fadeIn('slow');
					VuFind.Browse.curPage++;
				}
			}).fail(function(){
				VuFind.showMessage('Request Failed', 'There was an error with this AJAX Request.');
			});
			return false;
		}

	}
}(VuFind.Browse || {}));
