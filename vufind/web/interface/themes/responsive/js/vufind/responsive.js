VuFind.Responsive = (function(){
	$(document).ready(function(){
		$(window).resize(VuFind.Responsive.adjustLayout);
		$(window).trigger('resize');
	});

	return {
		adjustLayout: function(){
			// get resolution
			var resolution = document.documentElement.clientWidth;

			var mainContentElement = $("#main-content-with-sidebar");
			var xsContentInsertionPointElement = $("#xs-main-content-insertion-point");
			var mainContent;
			if (resolution < 750) {
				// XS screen resolution
				//move content from main-content-with-sidebar to xs-main-content-insertion-point
				mainContent = mainContentElement.html();
				if (mainContent && mainContent.length){
					xsContentInsertionPointElement.html(mainContent);
					mainContentElement.html("");
					VuFind.initCarousels();
				}
			}else{
				//Sm or better resolution
				mainContent = xsContentInsertionPointElement.html();
				if (mainContent && mainContent.length){
					mainContentElement.html(mainContent);
					xsContentInsertionPointElement.html("");
					VuFind.initCarousels();
				}
			}

		}
	};
}(VuFind.Responsive || {}));