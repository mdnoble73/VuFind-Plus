/**
 * Create a title scroller object for display
 * 
 * @param scrollerId - the id of the scroller which will hold the titles
 * @param scrollerShortName
 * @param container - a container to display if any titles are found
 * @return
 */
function TitleScroller(scrollerId, scrollerShortName, container, enableDescription, onSelectCallback){
	this.scrollerTitles = new Array();
	this.currentScrollerIndex = 0;
	this.numScrollerTitles = 0;
	this.scrollerId = scrollerId;
	this.scrollerShortName = scrollerShortName;
	this.container = container;
	if (typeof enableDescription == "undefined") {
		this.enableDescription = true;
	}else{
		this.enableDescription = enableDescription;
	}
	if (typeof onSelectCallback == "undefined") {
		this.onSelectCallback = '';
	}else{
		this.onSelectCallback = onSelectCallback;
	}
}

TitleScroller.prototype.loadTitlesFrom = function (jsonUrl){
	var scroller = this;
	$.getJSON(jsonUrl,
		function(data){
			scroller.loadTitlesFromJsonData(data);
		}
	);
};

TitleScroller.prototype.loadTitlesFromJsonData = function(data){
	var scroller = this;
	try{
		scroller.scrollerTitles = new Array();
		var i = 0;
		$.each(data.titles, function(key, val){
			scroller.scrollerTitles[i++] = val;
		});
		if (scroller.container && data.titles.length > 0){
			$("#" + scroller.container).fadeIn();
		}
		scroller.numScrollerTitles = data.titles.length;
		scroller.currentScrollerIndex = data.currentIndex;
		
		TitleScroller.prototype.updateScroller.call(scroller);
    }catch (err){
    	"error loading titles from data " + err.description;
    }
}

TitleScroller.prototype.updateScroller = function(){
	try{
		var scrollerBody = $('#' + this.scrollerId + " .scrollerBodyContainer .scrollerBody");
		scrollerBody.hide();
		$("#titleScrollerSelectedTitle" + this.scrollerShortName).html("");
		$("#titleScrollerSelectedAuthor" + this.scrollerShortName).html("");
		$(".scrollerLoadingContainer").show();
		
		var scrollerBodyContents = "";
		for (var i in this.scrollerTitles){
			scrollerBodyContents += this.scrollerTitles[i]['formattedTitle'];
		}
		scrollerBody.html(scrollerBodyContents);
		scrollerBody.width(this.scrollerTitles.length * 140);
		
		var curScroller = this;
		
		scrollerBody.waitForImages(function(){TitleScroller.prototype.finishLoadingScroller.call(curScroller)});
	}catch (err){
    	"error in updateScroller " + err.description;
    }
	
};
TitleScroller.prototype.finishLoadingScroller = function(){
	$(".scrollerLoadingContainer").hide();
	var scrollerBody = $('#' + this.scrollerId + " .scrollerBodyContainer .scrollerBody");
	scrollerBody.show();
	TitleScroller.prototype.activateCurrentTitle.call(this);
	if (this.enableDescription){
		for (var i in this.scrollerTitles){
			resultDescription(this.scrollerTitles[i]['id'],this.scrollerTitles[i]['id']);
		}
	}
};

TitleScroller.prototype.scrollToRight = function(){
	this.currentScrollerIndex++;
	if (this.currentScrollerIndex > this.numScrollerTitles -1) this.currentScrollerIndex = 0;
	TitleScroller.prototype.activateCurrentTitle.call(this);
};

TitleScroller.prototype.scrollToLeft = function(){
	this.currentScrollerIndex--;
	if (this.currentScrollerIndex < 0) this.currentScrollerIndex = this.numScrollerTitles -1;
	TitleScroller.prototype.activateCurrentTitle.call(this);
};

TitleScroller.prototype.activateCurrentTitle = function (){
	if (this.numScrollerTitles == 0){
		return;
	}
	var scrollerTitles = this.scrollerTitles;
	var scrollerShortName = this.scrollerShortName;
	var currentScrollerIndex = this.currentScrollerIndex;
	if (this.onSelectCallback == ''){
		$("#titleScrollerSelectedTitle" + scrollerShortName).html(scrollerTitles[currentScrollerIndex]['title']);
		$("#titleScrollerSelectedAuthor" + scrollerShortName).html(scrollerTitles[currentScrollerIndex]['author']);
	}else{
		var callback = window[this.onSelectCallback];
		callback(scrollerTitles[currentScrollerIndex]);
	}
	var scrollerBody = $('#' + this.scrollerId + " .scrollerBodyContainer .scrollerBody");
	var scrollerTitleId = "#scrollerTitle" + this.scrollerShortName + currentScrollerIndex;
	if ($(scrollerTitleId).length != 0){
		var widthItemsLeft = $(scrollerTitleId).position().left;
		var widthCurrent = $(scrollerTitleId).width();
		var containerWidth = $('#' + this.scrollerId + " .scrollerBodyContainer").width();
		//center the book in the container
		var leftPosition = -((widthItemsLeft + widthCurrent/2) - (containerWidth / 2)) ;
		scrollerBody.animate({
		    	left: leftPosition + "px"
			}, 400, 
		  function(){
				for (var i in scrollerTitles){
					var scrollerTitleId2 = "#scrollerTitle" + scrollerShortName + i;
					$(scrollerTitleId2).removeClass('selected');
				}
				$(scrollerTitleId).addClass('selected');
			}
		);
	}
};
