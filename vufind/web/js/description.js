function resultDescription(shortid,id, type){
	  //Attach the tooltip function to the HTML element with the id pretty + short record id
	  //this will show the description when the user hovers over the element. 
	var divId = "#descriptionTrigger" + shortid;
	if (type == undefined){
		type = 'VuFind';
	}
	if (type == 'VuFind'){
		var loadDescription = path + "/Record/" + id + "/AJAX/?method=getDescription";
	}else{
		var loadDescription = path + "/EcontentRecord/" + id + "/AJAX/?method=getDescription";
	}
	$(divId).tooltip({
		  track: false,
		  delay: 250,
		  showURL: false,
		  extraClass: "descriptionTooltip",
		  top:0,
		  bodyHandler: function() {
			if ($("#descriptionPlaceholder" + shortid).hasClass('loaded')){
				return $("#descriptionPlaceholder" + shortid).html();
			}else{
			  $("#descriptionPlaceholder" + shortid).addClass('loaded');
			  var rawData = $.ajax(loadDescription,{
				  async: false
			  }).responseText;
			  var xmlDoc = $.parseXML(rawData);
			  var data = $(xmlDoc);
			  //parses the xml and sets variables to call later
			  var descriptAjax = data.find('description').text();
			  var lengthAjax = data.find('length').text();
			  var publishAjax =data.find('publisher').text();
			  var toolTip = "<h3>Description</h3> <div class='description-element'>" + descriptAjax + "</div><div class='description-element'><div class='description-element-label'>Length: </div>" + lengthAjax + "</div><div class='description-element'><div class='description-element-label'>Publisher: </div>" + publishAjax + "</div>";
			  $("#descriptionPlaceholder" + shortid).html(toolTip);
			  return toolTip;
			}
		  }
	});
};