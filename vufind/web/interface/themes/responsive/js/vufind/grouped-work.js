/**
 * Created by mark on 1/24/14.
 */
VuFind.GroupedWork = (function(){
	return {
		getGoDeeperData: function (id, dataType){
			var placeholder;
			if (dataType == 'excerpt'){
				placeholder = $("#excerptPlaceholder");
			}else if (dataType == 'avSummary'){
				placeholder = $("#avSummaryPlaceholder");
			}else if (dataType == 'tableOfContents'){
				placeholder = $("#tableOfContentsPlaceholder");
			}
			if (placeholder.hasClass("loaded")) return;
			placeholder.show();
			var url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX";
			var params = "method=GetGoDeeperData&dataType=" + encodeURIComponent(dataType);
			var fullUrl = url + "?" + params;
			$.getJSON( fullUrl,function(data) {
				placeholder.html(data.formattedData)
				placeholder.addClass('loaded');
			});
		},

		loadEnrichmentInfo: function (id) {
			var url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX";
			var params = "method=getEnrichmentInfo";
			var fullUrl = url + "?" + params;
			$.getJSON( fullUrl, function(data) {
					try{
						var seriesData = data.seriesInfo;
						if (seriesData && seriesData.titles.length > 0) {
							seriesScroller = new TitleScroller('titleScrollerSeries', 'Series', 'seriesList');
							$('#seriesInfo').show();
							seriesScroller.loadTitlesFromJsonData(seriesData);
						}
						var similarTitleData = data.similarTitles;
						if (similarTitleData && similarTitleData.titles.length > 0) {
							morelikethisScroller = new TitleScroller('titleScrollerMoreLikeThis', 'MoreLikeThis', 'morelikethisList');
							$('#moreLikeThisInfo').show();
							morelikethisScroller.loadTitlesFromJsonData(similarTitleData);
						}
						var showGoDeeperData = data.showGoDeeper;
						if (showGoDeeperData) {
							//$('#goDeeperLink').show();
							var goDeeperOptions = data.goDeeperOptions;
							//add a tab before citation for each item
							for (option in goDeeperOptions){
								if (option == 'excerpt'){
									$("#excerpttab_label").show();
									$("excerptPanel").show();
								}else if (option == 'avSummary'){
									$("#tableofcontentstab_label").show();
									$("avSummaryPlaceholder").show();
									$("tableOfContentsPanel").show();
								}else if (option == 'avSummary' || option == 'tableOfContents'){
									$("#tableofcontentstab_label").show();
									$("tableOfContentsPlaceholder").show();
									$("tableOfContentsPanel").show();
								}
							}
						}
						var relatedContentData = data.relatedContent;
						if (relatedContentData && relatedContentData.length > 0) {
							$("#relatedContentPlaceholder").html(relatedContentData);
						}
						var similarTitlesNovelist = data.similarTitlesNovelist;
						if (similarTitlesNovelist && similarTitlesNovelist.length > 0){
							$("#novelisttitlesPlaceholder").html(similarTitlesNovelist);
							$("#novelisttab_label").show();
							$("#similarTitlesPanel").show();
						}

						var similarAuthorsNovelist = data.similarAuthorsNovelist;
						if (similarAuthorsNovelist && similarAuthorsNovelist.length > 0){
							$("#novelistauthorsPlaceholder").html(similarAuthorsNovelist);
							$("#novelisttab_label").show();
							$("#similarAuthorsPanel").show();
						}

						var similarSeriesNovelist = data.similarSeriesNovelist;
						if (similarSeriesNovelist && similarSeriesNovelist.length > 0){
							$("#novelistseriesPlaceholder").html(similarSeriesNovelist);
							$("#novelisttab_label").show();
							$("#similarSeriesPanel").show();
						}
					} catch (e) {
						alert("error loading enrichment: " + e);
					}
				}
			);
		},

		loadReviewInfo: function (id) {
			var url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX?method=getReviewInfo";
			$.getJSON(url, function(data) {
				if (data.numSyndicatedReviews == 0){
					$("#syndicatedReviewsPanel").hide();
				}else{
					var syndicatedReviewsData = data.syndicatedReviewsHtml;
					if (syndicatedReviewsData && syndicatedReviewsData.length > 0) {
						$("#syndicatedReviewPlaceholder").html(syndicatedReviewsData);
					}
				}
				if (data.numEditorialReviews == 0){
					$("#editorialReviewsPanel").hide();
				}else{
					var editorialReviewsData = data.editorialReviewsHtml;
					if (editorialReviewsData && editorialReviewsData.length > 0) {
						$("#editorialReviewPlaceholder").html(editorialReviewsData);
					}
				}

				if (data.numCustomerReviews == 0){
					$("#borrowerReviewsPanel").hide();
				}else{
					var customerReviewsData = data.customerReviewsHtml;
					if (customerReviewsData && customerReviewsData.length > 0) {
						$("#borrowerReviewPlaceholder").html(customerReviewsData);
					}
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

		sendSMS: function(id){
			if (Globals.loggedIn){
				var phoneNumber = $('#sms_phone_number').val();
				var provider = $('#provider').val();

				var url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX";
				var params = "method=sendSMS&sms_phone_number=" + encodeURIComponent(phoneNumber) + "&provider=" + encodeURIComponent(provider);
				$.getJSON(url + '?' + params,
						function(data) {
							if (data.result) {
								VuFind.showMessage("Success", data.message);
							} else {
								VuFind.showMessage("Error", data.message);
							}
						}
				);
			}
			return false;
		},

		showReviewForm: function(trigger, id){
			var $trigger = $(trigger);
			if (Globals.loggedIn){
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
				VuFind.Account.ajaxLogin($trigger, function (){
					return VuFind.GroupedWork.showReviewForm($trigger, id);
				}, false);
			}
			return false;
		},

		showSaveToListForm: function (trigger, id){
			if (Globals.loggedIn){
				var url = Globals.path + "/Resource/Save?lightbox=true&id=" + id + "&source=" + source;
				var $trigger = $(trigger);
				$("#modal-title").text($trigger.attr("title"));
				var modalDialog = $("#modalDialog");
				modalDialog.load(url);
				modalDialog.modal('show');
			}else{
				trigger = $(trigger);
				VuFind.Account.ajaxLogin(trigger, function (){
					VuFind.Record.getSaveToListForm(trigger, id, source);
				});
			}
			return false;
		},

		showSmsForm: function(trigger, id){
			var $trigger = $(trigger);
			if (Globals.loggedIn){
				var modalDialog = $("#modalDialog");
				//$(".modal-body").html($('#userreview' + id).html());
				$.getJSON(Globals.path + "/GroupedWork/" + id + "/AJAX?method=getSMSForm", function(data){
					$('#myModalLabel').html(data.title);
					$('.modal-body').html(data.modalBody);
					$('.modal-buttons').html(data.modalButtons);
				});
				modalDialog.load( );
				modalDialog.modal('show');
			}else{
				VuFind.Account.ajaxLogin($trigger, function (){
					return VuFind.GroupedWork.showSmsForm(trigger, id);
				}, false);
			}
			return false;
		}
	};
}(VuFind.GroupedWork || {}));