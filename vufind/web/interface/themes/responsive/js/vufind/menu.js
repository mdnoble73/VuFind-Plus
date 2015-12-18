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
		$('.menu-bar-option').click(function(){
			$('.menu-bar-option').removeClass('menu-icon-selected');
			$(this).addClass('menu-icon-selected')
		});

		VuFind.Menu.stickyMenu('#horizontal-menu-bar-container', 'sticky-menu-bar');
		//VuFind.Menu.stickyMenu('#sidebar-content', 'sticky-sidebar');
		VuFind.Menu.stickyMenu('#vertical-menu-bar', 'sticky-sidebar');

	});
	return {
		stickyMenu: function(menuContainerSelector, stickyMenuClass){
			var menu = $(menuContainerSelector),
					switchPosition = menu.offset().top;
			/*Meant to remain constant for the event handler below.*/
			$(window).scroll(function(){
				var fixedOffset = menu.offset().top,
						notFixedScrolledPosition = $(this).scrollTop();
				/*Toggle into an embedded mode*/
				if (menu.is('.'+stickyMenuClass) && fixedOffset <= switchPosition) {
					menu.removeClass(stickyMenuClass)
				}
				/*Toggle into a fixed mode*/
				if (!menu.is('.'+stickyMenuClass) && notFixedScrolledPosition >= switchPosition) {
					menu.addClass(stickyMenuClass)
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