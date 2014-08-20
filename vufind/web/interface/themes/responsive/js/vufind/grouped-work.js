/**
 * Created by mark on 1/24/14.
 */
VuFind.GroupedWork = (function(){
	return {
		clearUserRating: function (groupedWorkId){
			var url = Globals.path + '/GroupedWork/' + groupedWorkId + '/AJAX?method=clearUserRating';
			$.getJSON(url, function(data){
				if (data.result == true){
					$('.rate' + groupedWorkId).find('.ui-rater-starsOn').width(0);
					$('#myRating' + groupedWorkId).hide();
					VuFind.showMessage('Success', data.message, true);
				}else{
					VuFind.showMessage('Sorry', data.message);
				}
			});
			return false;
		},

		clearNotInterested: function (notInterestedId){
			var url = Globals.path + '/GroupedWork/' + notInterestedId + '/AJAX?method=clearNotInterested';
			$.getJSON(
					url, function(data){
						if (data.result == false){
							VuFind.showMessage('Sorry', "There was an error updating the title.");
						}else{
							$("#notInterested" + notInterestedId).hide();
						}
					}
			);
		},

		deleteReview: function(id, reviewId){
			if (confirm("Are you sure you want to delete this review?")){
				var url = Globals.path + '/GroupedWork/' + id + '/AJAX?method=deleteUserReview';
				$.getJSON(url, function(data){
					if (data.result == true){
						$('#review_' + reviewId).hide();
						VuFind.showMessage('Success', data.message, true);
					}else{
						VuFind.showMessage('Sorry', data.message);
					}
				});
			}
			return false;
		},

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
				placeholder.html(data.formattedData);
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
						}else{
							$('#seriesPanel').hide();
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
							for (var option in goDeeperOptions){
								if (option == 'excerpt'){
									$("#excerpttab_label").show();
									$("#excerptPanel").show();
								}else if (option == 'avSummary'){
									$("#tableofcontentstab_label").show();
									$("#avSummaryPlaceholder").show();
									$("#tableOfContentsPanel").show();
								}else if (option == 'avSummary' || option == 'tableOfContents'){
									$("#tableofcontentstab_label").show();
									$("#tableOfContentsPlaceholder").show();
									$("#tableOfContentsPanel").show();
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
						$("#customerReviewPlaceholder").html(customerReviewsData);
					}
				}
			});
		},

		markNotInterested: function (recordId){
			if (Globals.loggedIn){
				var url = Globals.path + '/GroupedWork/' + recordId + '/AJAX?method=markNotInterested';
				$.getJSON(
						url, function(data){
							if (data.result == true){
								VuFind.showMessage('Success', data.message);
							}else{
								VuFind.showMessage('Sorry', data.message);
							}
						}
				);
				return false;
			}else{
				return VuFind.Account.ajaxLogin(null, function(){markNotInterested(source, recordId)}, false);
			}
		},

		reloadCover: function (id){
			var url = Globals.path + '/GroupedWork/' + id + '/AJAX?method=reloadCover';
			$.getJSON(url, function (data){
						VuFind.showMessage("Success", data.message, true, true);
						setTimeout("VuFind.closeLightbox();", 3000);
					}
			);
			return false;
		},

		removeTag:function(id, tag){
			if (confirm("Are you sure you want to remove the tag \"" + tag + "\" from this title?")){
				var url = Globals.path + '/GroupedWork/' + id + '/AJAX?method=removeTag';
				url += "&tag=" + encodeURIComponent(tag);
				$.getJSON(
						url, function(data){
							if (data.result == true){
								VuFind.showMessage('Success', data.message);
							}else{
								VuFind.showMessage('Sorry', data.message);
							}
						}
				);
				return false;
			}
			return false;
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

		saveTag: function(id){
			var tag = $("#tags_to_apply").val();
			$("#saveToList-button").prop('disabled', true);

			var url = Globals.path + "/GroupedWork/" + id + "/AJAX";
			var params = "method=SaveTag&" +
					"tag=" + encodeURIComponent(tag);
			$.ajax({
				url: url+'?'+params,
				dataType: "json",
				success: function(data) {
					if (data.success) {
						VuFind.showMessage("Success", data.message);
						setTimeout("VuFind.closeLightbox();", 3000);
					} else {
						VuFind.showMessage("Error adding tags", "There was an unexpected error adding tags to this title.<br/>" + data.message);
					}

				},
				error: function(jqXHR, textStatus) {
					VuFind.showMessage("Error adding tags", "There was an unexpected error adding tags to this title.<br/>" + textStatus);
				}
			});
		},

		saveToList: function(id){
			if (Globals.loggedIn){
				var listId = $('#addToList-list').val();
				var notes = $('#addToList-notes').val();

				var url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX";
				var params = "method=saveToList&notes=" + encodeURIComponent(notes) + "&listId=" + encodeURIComponent(listId);
				$.getJSON(url + '?' + params,
						function(data) {
							if (data.result) {
								VuFind.showMessage("Added Successfully", data.message);
							} else {
								VuFind.showMessage("Error", data.message);
							}
						}
				);
			}
			return false;
		},

		sendEmail: function(id){
			if (Globals.loggedIn){
				var from = $('#from').val();
				var to = $('#to').val();
				var message = $('#message').val();
				var related_record = $('#related_record').val();

				var url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX";
				var params = "method=sendEmail&from=" + encodeURIComponent(from) + "&to=" + encodeURIComponent(to) + "&message=" + encodeURIComponent(message) + "&related_record=" + encodeURIComponent(related_record);
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

		sendSMS: function(id){
			if (Globals.loggedIn){
				var phoneNumber = $('#sms_phone_number').val();
				var provider = $('#provider').val();
				var related_record = $('#related_record').val();

				var url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX";
				var params = "method=sendSMS&sms_phone_number=" + encodeURIComponent(phoneNumber) + "&provider=" + encodeURIComponent(provider) + "&related_record=" + encodeURIComponent(related_record);
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

		showEmailForm: function(trigger, id){
			var $trigger = $(trigger);
			if (Globals.loggedIn){
				var modalDialog = $("#modalDialog");
				//$(".modal-body").html($('#userreview' + id).html());
				$.getJSON(Globals.path + "/GroupedWork/" + id + "/AJAX?method=getEmailForm", function(data){
					$('#myModalLabel').html(data.title);
					$('.modal-body').html(data.modalBody);
					$('.modal-buttons').html(data.modalButtons);
				});
				modalDialog.load( );
				modalDialog.modal('show');
			}else{
				VuFind.Account.ajaxLogin($trigger, function (){
					return VuFind.GroupedWork.showEmailForm(trigger, id);
				}, false);
			}
			return false;
		},

		showGroupedWorkInfo:function(id, browseCategoryId){
			var modalDialog = $("#modalDialog");
			//$(".modal-body").html($('#userreview' + id).html());
			var url = Globals.path + "/GroupedWork/AJAX?method=getWorkInfo&id=" + id;
			if (browseCategoryId != undefined){
				url += "&browseCategoryId=" + browseCategoryId;
			}
			$.getJSON(url, function(data){
				$('#myModalLabel').html(data.title);
				$('.modal-body').html(data.modalBody);
				$('.modal-buttons').html(data.modalButtons);
			});
			modalDialog.load( );
			modalDialog.modal('show');
			return false;
		},

		showReviewForm: function(trigger, id){
			var $trigger = $(trigger);
			if (Globals.loggedIn){
				var modalDialog = $("#modalDialog");
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
				var modalDialog = $("#modalDialog");
				$.getJSON(Globals.path + "/GroupedWork/" + id + "/AJAX?method=getSaveToListForm", function(data){
					$('#myModalLabel').html(data.title);
					$('.modal-body').html(data.modalBody);
					$('.modal-buttons').html(data.modalButtons);
				});
				modalDialog.load( );
				modalDialog.modal('show');
			}else{
				trigger = $(trigger);
				VuFind.Account.ajaxLogin(trigger, function (){
					VuFind.GroupedWork.showSaveToListForm(trigger, id);
				});
			}
			return false;
		},

		showSmsForm: function(trigger, id){
			var $trigger = $(trigger);
			if (Globals.loggedIn){
				var modalDialog = $("#modalDialog");
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
		},

		showTagForm: function(trigger, id, source){
			if (Globals.loggedIn){
				var modalDialog = $("#modalDialog");
				$.getJSON(Globals.path + "/GroupedWork/" + id + "/AJAX?method=getAddTagForm", function(data){
					$('#myModalLabel').html(data.title);
					$('.modal-body').html(data.modalBody);
					$('.modal-buttons').html(data.modalButtons);
				});
				modalDialog.load( );
				modalDialog.modal('show');
			}else{
				trigger = $(trigger);
				VuFind.Account.ajaxLogin(trigger, function (){
					VuFind.GroupedWork.showTagForm(trigger, id, source);
				}, false);
			}
			return false;
		}
	};
}(VuFind.GroupedWork || {}));