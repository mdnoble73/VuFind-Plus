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
		},

		loadReviewInfo: function (id, isbn, source) {
			var url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX?method=GetReviewInfo";
			$.getJSON(url, function(data) {
				var syndicatedReviewsData = data.syndicatedReviewsHtml;
				if (syndicatedReviewsData && syndicatedReviewsData.length > 0) {
					$("#syndicatedReviewPlaceholder").html(syndicatedReviewsData);
				}
				var editorialReviewsData = data.editorialReviewsHtml;
				if (editorialReviewsData && editorialReviewsData.length > 0) {
					$("#editorialReviewPlaceholder").html(editorialReviewsData);
				}

				var customerReviewsData = data.customerReviewsHtml;
				if (customerReviewsData && customerReviewsData.length > 0) {
					$("#customerReviewPlaceholder").html(customerReviewsData);
				}
			});
		},

		saveReview: function(id){
			if (Globals.loggedIn){
				var comment = $('#comment' + id).val();
				var rating = $('#rating' + id).val();

				var url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX";
				var params = "method=saveReview&comment=" + encodeURIComponent(comment) + "&rating=" + encodeURIComponent(rating);
				$.getJSON(url + '?' + params,
					function(data) {
						if (data.result) {
							if (data.newReview){
								$("#customerReviewPlaceholder").append(data.reviewHtml);
							}else{
								$("#review_" + data.reviewId).replaceWith(data.reviewHtml);
							}
							VuFind.closeLightbox();
						} else {
							VuFind.showMessage("Error", data.message);
						}
					}
				);
			}
			return false;
		},

		showReviewForm: function(trigger, id){
			if (Globals.loggedIn){
				var $trigger = $(trigger);
				$("#modal-title").text($trigger.attr("title"));
				var modalDialog = $("#modalDialog");
				//$(".modal-body").html($('#userreview' + id).html());
				$.getJSON(Globals.path + "/GroupedWork/AJAX?method=getReviewForm&id=" + id, function(data){
					$('#myModalLabel').html(data.title);
					$('.modal-body').html(data.modalBody);
					$('.modal-buttons').html(data.modalButtons);
				});
				modalDialog.load( );
				modalDialog.modal('show');
			}else{
				var $trigger = $(trigger);
				VuFind.Account.ajaxLogin($trigger, function (){
					return VuFind.GroupedWork.showReviewForm($trigger, id);
				}, false);
			}
			return false;
		}
	};
}(VuFind.GroupedWork || {}));