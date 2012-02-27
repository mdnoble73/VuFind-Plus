var homePageScroller;
$(document).ready(function(){
	homePageScroller = new TitleScroller('titleScrollerHome', 'Home');
	changeSelectedList();
});

function changeSelectedList(){
	var selectedListKey = $("#listSelection").val();
	var callback = eval(listCallbacks[selectedListKey]);
	homePageScroller.loadTitlesFrom(callback);
}

function randomSysListTitles(name){
	return path + '/Search/AJAX?method=RandomSysListTitles&name=' + name + '&scrollerName=Home';
}
