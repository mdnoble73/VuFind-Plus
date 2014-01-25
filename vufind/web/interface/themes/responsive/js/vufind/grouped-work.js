/**
 * Created by mark on 1/24/14.
 */
VuFind.GroupedWork = (function(){
	return {
		getGoDeeperData: function (id, dataType){
			if (dataType == 'excerpt'){
				var placeholder = $("#excerptPlaceholder");
			}else if (dataType == 'avSummary'){
				var placeholder = $("#avSummaryPlaceholder");
			}else if (dataType == 'tableOfContents'){
				var placeholder = $("#tableOfContentsPlaceholder");
			}
			if (placeholder.hasClass("loaded")) return;
			placeholder.show();
			var url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX";
			var params = "method=GetGoDeeperData&dataType=" + encodeURIComponent(dataType);
			var fullUrl = url + "?" + params;
			$.ajax( {
				url : fullUrl,
				success : function(data) {
					placeholder.html(data);
					placeholder.addClass('loaded');
				},
				failure : function(jqXHR, textStatus, errorThrown) {
					alert('Error: Could Not Load Syndetics information.');
				}
			});
		},

		getRelatedRecords: function(groupedId){
			VuFind.ajaxLightbox(Globals.path + "/GroupedWork/" + groupedId + "/AJAX?method=getRelatedRecords");
		},

		loadEnrichmentInfo: function (id) {
			var url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX";
			var params = "method=GetEnrichmentInfoJSON";
			var fullUrl = url + "?" + params;
			$.ajax( {
				url : fullUrl,
				dataType: 'json',
				success : function(data) {
					try{
						var seriesData = data.seriesInfo;
						if (seriesData && seriesData.titles.length > 0) {

							seriesScroller = new TitleScroller('titleScrollerSeries', 'Series', 'seriesList');

							$('#list-series-tab').show();
							$('#relatedTitleInfo').show();
							seriesScroller.loadTitlesFromJsonData(seriesData);
						}
						var showGoDeeperData = data.showGoDeeper;
						if (showGoDeeperData) {
							//$('#goDeeperLink').show();
							var goDeeperOptions = data.goDeeperOptions;
							//add a tab before citation for each item
							for (option in goDeeperOptions){
								if (option == 'excerpt'){
									$("#excerpttab_label").show();
								}else if (option == 'avSummary'){
									$("#tableofcontentstab_label").show();
									$("avSummaryPlaceholder").show();
								}else if (option == 'avSummary' || option == 'tableOfContents'){
									$("#tableofcontentstab_label").show();
									$("tableOfContentsPlaceholder").show();
								}
							}
						}
						var relatedContentData = data.relatedContent;
						if (relatedContentData && relatedContentData.length > 0) {
							$("#relatedContentPlaceholder").html(relatedContentData);
						}
					} catch (e) {
						alert("error loading enrichment: " + e);
					}
				},
				failure : function(jqXHR, textStatus, errorThrown) {
					alert('Error: Could Not Load Enrichment information.');
				}
			});
		}
	};
}(VuFind.GroupedWork || {}));