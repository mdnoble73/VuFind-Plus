/**
 * Create a title scroller object for display
 * 
 * @param scrollerId - the id of the scroller which will hold the titles
 * @param scrollerShortName
 * @param container - a container to display if any titles are found
 * @return
 */
function TitleScroller(scrollerId, scrollerShortName, container,
		enableDescription, onSelectCallback, autoScroll, showMultipleTitles) {
	this.scrollerTitles = new Array();
	this.currentScrollerIndex = 0;
	this.numScrollerTitles = 0;
	this.scrollerId = scrollerId;
	this.scrollerShortName = scrollerShortName;
	this.container = container;
	this.scrollInterval = 0;
	
	if (typeof enableDescription == "undefined") {
		this.enableDescription = true;
	} else {
		this.enableDescription = enableDescription;
	}
	if (typeof onSelectCallback == "undefined") {
		this.onSelectCallback = '';
	} else {
		this.onSelectCallback = onSelectCallback;
	}
	if (typeof autoScroll == "undefined") {
		this.autoScroll = false;
	} else {
		this.autoScroll = autoScroll;
	}
	if (typeof showMultipleTitles == "undefined") {
		this.showMultipleTitles = true;
	} else {
		this.showMultipleTitles = showMultipleTitles;
	}
}

TitleScroller.prototype.loadTitlesFrom = function(jsonUrl) {
	jsonUrl = decodeURIComponent(jsonUrl);
	var scroller = this;
	var scrollerBody = $('#' + this.scrollerId + " .scrollerBodyContainer .scrollerBody");
	scrollerBody.hide();
	$("#titleScrollerSelectedTitle" + this.scrollerShortName).html("");
	$("#titleScrollerSelectedAuthor" + this.scrollerShortName).html("");
	$(".scrollerLoadingContainer").show();
	$.getJSON(jsonUrl, function(data) {
		scroller.loadTitlesFromJsonData(data);
	}).error(function(){
		scrollerBody.html("Unable to load titles. Please try again later.");
		scrollerBody.show();
		$(".scrollerLoadingContainer").hide();
	});
};

TitleScroller.prototype.loadTitlesFromJsonData = function(data) {
	var scroller = this;
	var scrollerBody = $('#' + this.scrollerId + " .scrollerBodyContainer .scrollerBody");
	try {
		if (data.titles.length == 0){
			scrollerBody.html("No titles were found for this list. Please try again later.");
			scrollerBody.show();
		}else{
			scroller.scrollerTitles = new Array();
			var i = 0;
			$.each(data.titles, function(key, val) {
				scroller.scrollerTitles[i++] = val;
			});
			if (scroller.container && data.titles.length > 0) {
				$("#" + scroller.container).fadeIn();
			}
			scroller.numScrollerTitles = data.titles.length;
			if (this.showMultipleTitles){
				scroller.currentScrollerIndex = data.currentIndex;
			}else{
				scroller.currentScrollerIndex = 0;
			}
			
	
			TitleScroller.prototype.updateScroller.call(scroller);
		}
	} catch (err) {
		//alert("error loading titles from data " + err.description);
		if (scrollerBody != null){
			scrollerBody.html("error loading titles from data " + err.description + ". Please try again later.");
			scrollerBody.show();
			$(".scrollerLoadingContainer").hide();
		}else{
			//alert("Could not find scroller body for " + this.scrollerId);
		}
	}
}

TitleScroller.prototype.updateScroller = function() {
	var scrollerBody = $('#' + this.scrollerId + " .scrollerBodyContainer .scrollerBody");
	try {
		if (this.showMultipleTitles){
			var scrollerBodyContents = "";
			for ( var i in this.scrollerTitles) {
				scrollerBodyContents += this.scrollerTitles[i]['formattedTitle'];
			}
			scrollerBody.html(scrollerBodyContents);
			scrollerBody.width(this.scrollerTitles.length * 140);
	
			var curScroller = this;
			scrollerBody.waitForImages(function() {
				TitleScroller.prototype.finishLoadingScroller.call(curScroller);
			});
		}else{
			var scrollerBodyContents = "";
			this.currentScrollerIndex = 0;
			scrollerBody.html(this.scrollerTitles[this.currentScrollerIndex]['formattedTitle']);
			TitleScroller.prototype.finishLoadingScroller.call(this);
		}
		
	} catch (err) {
		//alert("error in updateScroller " + err.description);
		scrollerBody.html("error loading titles from data " + err + ". Please try again later.");
		scrollerBody.show();
		$(".scrollerLoadingContainer").hide();
	}

};

TitleScroller.prototype.finishLoadingScroller = function() {
	$(".scrollerLoadingContainer").hide();
	var scrollerBody = $('#' + this.scrollerId + " .scrollerBodyContainer .scrollerBody");
	scrollerBody.show();
	TitleScroller.prototype.activateCurrentTitle.call(this);
	var curScroller = this;

	// Whether we are hovering over an individual title or not.
	$('.scrollerTitle').bind('mouseover', {scroller: curScroller}, function() {
		curScroller.hovered = true;
		//console.log('over');
	}).bind('mouseout', {scroller: curScroller}, function() {
		curScroller.hovered = false;
		//console.log('out');
	});

	// Set initial state.
	curScroller.hovered = false;

	if (this.autoScroll && this.scrollInterval == 0){
		this.scrollInterval = setInterval(function() {
			// Only proceed if not hovering.
			if (!curScroller.hovered) {
				curScroller.scrollToRight();
			}
		}, 5000);
	}
	if (this.enableDescription) {
		for ( var i in this.scrollerTitles) {
			resultDescription(this.scrollerTitles[i]['id'],
					this.scrollerTitles[i]['id']);
		}
	}
};

TitleScroller.prototype.scrollToRight = function() {
	this.currentScrollerIndex++;
	if (this.currentScrollerIndex > this.numScrollerTitles - 1)
		this.currentScrollerIndex = 0;
	TitleScroller.prototype.activateCurrentTitle.call(this);
};

TitleScroller.prototype.scrollToLeft = function() {
	this.currentScrollerIndex--;
	if (this.currentScrollerIndex < 0)
		this.currentScrollerIndex = this.numScrollerTitles - 1;
	TitleScroller.prototype.activateCurrentTitle.call(this);
};

TitleScroller.prototype.activateCurrentTitle = function() {
	if (this.numScrollerTitles == 0) {
		return;
	}
	var scrollerTitles = this.scrollerTitles;
	var scrollerShortName = this.scrollerShortName;
	var currentScrollerIndex = this.currentScrollerIndex;
	if (typeof this.onSelectCallback == "undefined" || this.onSelectCallback == '') {
		$("#titleScrollerSelectedTitle" + scrollerShortName).html(
				scrollerTitles[currentScrollerIndex]['title']);
		$("#titleScrollerSelectedAuthor" + scrollerShortName).html(
				scrollerTitles[currentScrollerIndex]['author']);
	} else {
		var callback = window[this.onSelectCallback];
		callback(scrollerTitles[currentScrollerIndex]);
	}
	var scrollerBody = $('#' + this.scrollerId
			+ " .scrollerBodyContainer .scrollerBody");
	//Make sure to clear the current tooltip if any
	$("#tooltip").hide();
	//Update the actual display
	if (this.showMultipleTitles){
		var scrollerTitleId = "#scrollerTitle" + this.scrollerShortName
				+ currentScrollerIndex;
		if ($(scrollerTitleId).length != 0) {
				var widthItemsLeft = $(scrollerTitleId).position().left;
				var widthCurrent = $(scrollerTitleId).width();
				var containerWidth = $('#' + this.scrollerId + " .scrollerBodyContainer")
						.width();
				// center the book in the container
				var leftPosition = -((widthItemsLeft + widthCurrent / 2) - (containerWidth / 2));
				scrollerBody.animate( {
					left : leftPosition + "px"
				}, 400, function() {
					for ( var i in scrollerTitles) {
						var scrollerTitleId2 = "#scrollerTitle" + scrollerShortName + i;
						$(scrollerTitleId2).removeClass('selected');
					}
					$(scrollerTitleId).addClass('selected');
				});
		}
	}else{
		var scrollerBodyContents = "";
		scrollerBody.left = "0px";
		scrollerBody.html(this.scrollerTitles[currentScrollerIndex]['formattedTitle']);
	}
};
