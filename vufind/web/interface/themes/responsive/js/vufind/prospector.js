VuFind.Prospector = (function(){
	return {
		getProspectorResults: function(prospectorNumTitlesToLoad, prospectorSavedSearchId){
			var url = Globals.path + "Search/AJAX";
			var params = "method=getProspectorResults&prospectorNumTitlesToLoad=" + encodeURIComponent(prospectorNumTitlesToLoad) + "&prospectorSavedSearchId=" + encodeURIComponent(prospectorSavedSearchId);
			var fullUrl = url + "?" + params;
			$.ajax({
				url: fullUrl,
				success: function(data) {
					var prospectorSearchResults = $(data).find("ProspectorSearchResults").text();
					if (prospectorSearchResults) {
						if (prospectorSearchResults.length > 0){
							$("#prospectorSearchResultsPlaceholder").html(prospectorSearchResults);
						}
					}
				}
			});
		},

		loadRelatedProspectorTitles: function (id, source) {
			var url;
			if (source == 'VuFind'){
				url = Globals.path + "Record/" + encodeURIComponent(id) + "/AJAX";
			}else{
				url = Globals.path + "EcontentRecord/" + encodeURIComponent(id) + "/AJAX";
			}
			var params = "method=GetProspectorInfo";
			var fullUrl = url + "?" + params;
			$.ajax( {
				url : fullUrl,
				success : function(data) {
					var inProspectorData = $(data).find("InProspector").text();
					if (inProspectorData) {
						if (inProspectorData.length > 0) {
							$("#inProspectorPlaceholder").html(inProspectorData);
						}
						var prospectorCopies = $(data).find("OwningLibrariesFormatted").text();
						if (prospectorCopies && prospectorCopies.length > 0) {
							$("#prospectorHoldingsPlaceholder").html(prospectorCopies);
						}
						$("#inProspectorSidegroup").show();
					}else{
						var prospectorLabel = $("#prospectortab_label");
						if (prospectorLabel){
							prospectorLabel.hide();
							if ($("#holdingstab_label").is(":visible")){
								$("#moredetails-tabs").tabs("option", "active", 0);
							}else{
								$("#moredetails-tabs").tabs("option", "active", 2);
							}
						}
					}
				}
			});
		}
	}
}(VuFind.Prospector || {}));