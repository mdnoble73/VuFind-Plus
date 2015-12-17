/**
 * Created by pbrammeier on 12/16/2015.
 */
VuFind.Menu = (function(){
	$(function(){
		// Page Initializations

		// Highlight Selected Menu Icon
		$('.menu-icon').click(function(){
			$('.menu-icon').removeClass('menu-icon-selected');
			$(this).addClass('menu-icon-selected')
		});

		// Switching Horizontal Menu Between Fixed and in Document
		var mobileMenu = $('#horizontal-menu-bar-container'),
				switchPosition = mobileMenu.offset().top;
		/*Meant to remain constant for the event handler below.*/
		$(window).scroll(function(){
			var fixedOffset = mobileMenu.offset().top,
					notFixedScrolledPosition = $(this).scrollTop();
			/*Toggle into an embedded mode*/
			if (mobileMenu.is('.sticky-menu-bar') && fixedOffset <= switchPosition) {
				mobileMenu.removeClass('sticky-menu-bar')
			}
			/*Toggle into a fixed mode*/
			if (!mobileMenu.is('.sticky-menu-bar') && notFixedScrolledPosition >= switchPosition) {
				mobileMenu.addClass('sticky-menu-bar')
			}
		})

	});
	return {
		temp: function(){
			alert('Click');
			return false;
		},

		hideAll: function(){
			return $('#home-page-search,#horizontal-search-container,#home-account-links,#home-page-library-section').filter(':visible').slideUp()
		},

		showMenuSection: function(sectionSelector){
			$.when( this.hideAll() ).done(function(){
				$(sectionSelector).slideDown()
			})
		},

		showSearch: function(){
			this.showMenuSection('#home-page-search,#horizontal-search-container')
		},

		showMenu: function(){
			this.showMenuSection('#home-page-library-section')
		},

		showAccount: function(){
			this.showMenuSection('#home-account-links')
		}
	}
}(VuFind.Menu || {}));