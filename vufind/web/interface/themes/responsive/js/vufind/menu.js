/**
 * Created by pbrammeier on 12/16/2015.
 */
VuFind.Menu = (function(){
	$(function(){
		// Page Initializations

		// Highlight Selected Menu Icon
		$('.menu-icon,.menu-bar-option').click(function(){
			$('.menu-icon,.menu-bar-option').removeClass('menu-icon-selected');
			$(this).addClass('menu-icon-selected')
		});

		// Set up Sticky Menus
		VuFind.Menu.stickyMenu('#horizontal-menu-bar-container', 'sticky-menu-bar');
		//VuFind.Menu.stickyMenu('#sidebar-content', 'sticky-sidebar');
		VuFind.Menu.stickyMenu('#vertical-menu-bar', 'sticky-sidebar');

	});
	return {
		stickyMenu: function(menuContainerSelector, stickyMenuClass){
			var menu = $(menuContainerSelector),
					viewportHeight = $(window).height(),
					switchPosition; // Meant to remain constant for the event handler below
			if (menu.is(':visible')) switchPosition = menu.offset().top;
			$(window).resize(function(){
				viewportHeight = $(window).height()
			});
			$(window).scroll(function(){
				if (menu.is(':visible') && viewportHeight < $('#main-content-with-sidebar').height()) { // only do this if the menu is visible & the page is larger than the viewport
					if (typeof switchPosition == 'undefined') {
						switchPosition = menu.offset().top
					}
					var fixedOffset = menu.offset().top,
							notFixedScrolledPosition = $(this).scrollTop();
					//console.log('Selector :', menuContainerSelector, 'fixedOffset : ', fixedOffset, ' notFixedScrolledPosition : ', notFixedScrolledPosition, 'switch position : ', switchPosition);

					/*Toggle into an embedded mode*/
					if (menu.is('.' + stickyMenuClass) && fixedOffset <= switchPosition) {
						menu.removeClass(stickyMenuClass)
					}
					/*Toggle into a fixed mode*/
					if (!menu.is('.' + stickyMenuClass) && notFixedScrolledPosition >= switchPosition) {
						menu.addClass(stickyMenuClass)
					}
				}
			})
		},

		hideAll: function(){
			return $('#home-page-search,#horizontal-search-container,#home-account-links,#home-page-library-section,#narrow-search-label,#facet-accordion').filter(':visible').slideUp()
		},

		showMenuSection: function(sectionSelector){
			$.when( this.hideAll() ).done(function(){
				$(sectionSelector).slideDown()
			})
		},

		showSearch: function(){
			this.showMenuSection('#home-page-search,#horizontal-search-container,#narrow-search-label,#facet-accordion')
		},

		showMenu: function(){
			this.showMenuSection('#home-page-library-section')
		},

		showAccount: function(){
			this.showMenuSection('#home-account-links')
		}
	}
}(VuFind.Menu || {}));