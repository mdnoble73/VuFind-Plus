// Update sidebar heights
$(document).ready(function() {
	setSidebarHeight();
});

var minSidebarHeight = -1;
function setSidebarHeight() {
	var sidebar = $("#sidebar");
	var mainContent = $("#main-content");
	var sidebarHeight = sidebar.height();
	var mainContentHeight = mainContent.height();
	if (minSidebarHeight == -1) {
		minSidebarHeight = sidebarHeight;
	}
	if (mainContentHeight > minSidebarHeight) {
		sidebar.height(mainContentHeight);
	}

}
