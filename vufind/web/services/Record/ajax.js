function getSaveStatus(id, elemId) {
	var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";
	var params = "method=GetSaveStatus";
	$.ajax( {
		url : url + '?' + params,
		success : function(data) {
			var response = $(data);
			var result = response.find('result');
			if (result.text() == 'Saved') {
				$('#' + elemId).addClass('savedFavorite');
			}
		}
	});
}

function saveRecord(id, formElem, strings) {
	successCallback = function() {
		// Highlight the save link to indicate that the content is saved:
		$('#saveLink').addClass('savedFavorite');
	};
	performSaveRecord(id, formElem, strings, 'VuFind', successCallback);
}

function SendEmail(id, to, from, message, strings) {
	var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";
	var params = "method=SendEmail&" + "from=" + encodeURIComponent(from) + "&" + "to=" + encodeURIComponent(to) + "&" + "message=" + encodeURIComponent(message);
	sendAJAXEmail(url, params, strings);
}

function SendSMS(id, to, provider, strings) {
	var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";
	var params = "method=SendSMS&" + "to=" + encodeURIComponent(to) + "&" + "provider=" + encodeURIComponent(provider);
	sendAJAXSMS(url, params, strings);
}

function SaveTag(id, formElem, strings) {
	var tags = formElem.elements['tag'].value;

	var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";
	var params = "method=SaveTag&tag=" + encodeURIComponent(tags);

	var callback = {
		success : function(transaction) {
			var response = transaction.responseXML ? transaction.responseXML.documentElement : false;
			var result = response ? response.getElementsByTagName('result') : false;
			if (result && result.length > 0) {
				if (result.item(0).firstChild.nodeValue == "Unauthorized") {
					document.forms['loginForm'].elements['followup'].value = 'SaveRecord';
					popupMenu('loginBox');
				} else {
					GetTags(id, 'tagList', strings);
					document.getElementById('popupbox').innerHTML = '<h3>' + strings.success + '</h3>';
					setTimeout("hideLightbox();", 3000);
				}
			} else {
				document.getElementById('popupbox').innerHTML = strings.save_error;
			}
		},
		failure : function(transaction) {
			document.getElementById('popupbox').innerHTML = strings.save_error;
		}
	};
	var transaction = YAHOO.util.Connect.asyncRequest('GET', url + '?' + params, callback, null);
}

function GetTags(id, elemId, strings) {
	var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";
	var params = "method=GetTags";
	var callback = {
		success : function(transaction) {
			var response = transaction.responseXML ? transaction.responseXML.documentElement : false;
			if (response && response.getElementsByTagName('result')) {
				var tags = response.getElementsByTagName("Tag");
				var output = "";
				if (tags && tags.length > 0) {
					for (i = 0; i < tags.length; i++) {
						if (i > 0) {
							output = output + ", ";
						}
						output = output + '<a href="' + path + '/Search/Results?tag=' + encodeURIComponent(tags.item(i).childNodes[0].nodeValue) + '">'
								+ jsEntityEncode(tags.item(i).childNodes[0].nodeValue) + '</a> (' + tags.item(i).getAttribute('count') + ")";
					}
				}
				document.getElementById(elemId).innerHTML = output;
			} else {
				document.getElementById(elemId).innerHTML = strings.load_error;
			}
		},
		failure : function(transaction) {
			document.getElementById(elemId).innerHTML = strings.load_error;
		}
	};
	var transaction = YAHOO.util.Connect.asyncRequest('GET', url + '?' + params, callback, null);
}

function SaveComment(id, strings) {
	$('#userreview' + id).slideUp();
	var comment = $('#comment' + id).val();

	var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";
	var params = "method=SaveComment&comment=" + encodeURIComponent(comment);
	var callback = {
		success : function(transaction) {
			var response = transaction.responseXML ? transaction.responseXML.documentElement : false;
			var result = false;
			if (response) {
				result = response.getElementsByTagName('result')
			}
			if (result && result.length > 0) {
				result = result.item(0).firstChild.nodeValue;
				if (result == "Done") {
					$('#comment' + id).val('');
					if ($('#commentList').length > 0) {
						LoadComments(id, strings);
					} else {
						alert('Thank you for your review.');
					}
				} else {
					getLightbox('AJAX', 'Login', id, null, strings.save_title, '', '', '', '10%', '80%', '50%', '300');
				}
			} else {
				alert(strings.save_error);
			}
		},
		failure : function(transaction) {
			if (strings.save_error.length == 0){
				alert("Unable to save your comment.");
			}else{
				alert(strings.save_error);
			}
		}
	};
	var transaction = YAHOO.util.Connect.asyncRequest('GET', url + '?' + params, callback, null);
}

function deleteComment(id, commentId, strings) {
	var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX?method=DeleteComment&commentId=" + encodeURIComponent(commentId);
	$.ajax( {
		url : url,
		success : function(data) {
			LoadComments(id, strings);
		}
	});
}

function LoadComments(id, strings) {
	var output = '';

	var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";
	var params = "method=GetComments";
	var output = '';

	var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";
	var params = "method=GetComments";
	$.getJSON(url + "?" + params, function(data) {
		var result = data.userComments;
		if (result && result.length > 0) {
			$("#commentList").html(result);
		} else {
			$("#commentList").html(strings.load_error);
		}
		
		var staffComments = data.staffComments;
		if (staffComments && staffComments.length > 0) {
			$("#staffCommentList").html(staffComments);
		} else {
			$("#staffCommentList").html(strings.load_error);
		}
	});
}

function checkPurchaseLinks(id) {
	var output = '';

	var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";
	var params = "method=checkPurchaseLinks";

	$.getJSON(url + "?" + params, function(data) {
		var buttonTatteredCover = data.tatteredCover;
		var buttonAmazon = data.amazon;
		var buttonBarnesAndNoble = data.barnesAndNoble;
		
		if (buttonTatteredCover || buttonAmazon || buttonBarnesAndNoble) {
				$("#purchaseLinkButtons").append("<h3>Get a copy for yourself</h3>");
		}
		
		if (buttonTatteredCover && buttonTatteredCover.length > 0) {
			$("#purchaseLinkButtons").append(buttonTatteredCover);
		} 
		
		if (buttonAmazon && buttonAmazon.length > 0) {
			$("#purchaseLinkButtons").append(buttonAmazon);
		} 
		
		if (buttonBarnesAndNoble && buttonBarnesAndNoble.length > 0) {
			$("#purchaseLinkButtons").append(buttonBarnesAndNoble);
		} 
		
	});
}

function GetPreferredBranches() {
	var username = document.forms['placeHoldForm'].elements['username'].value;
	var barcode = document.forms['placeHoldForm'].elements['password'].value;
	if (username.length == 0 || barcode.length == 0) {
		return;
	}

	var url = path + "/MyResearch/AJAX";
	var params = "method=GetPreferredBranches&username="
	    + encodeURIComponent(username) + "&barcode="
	    + encodeURIComponent(barcode);
	
	$.ajax(url + "?" + params, {
	  success : function(data) {
		  if ($(data).find('result')) {

			  var locations = $(data).find('Location');
			  if (locations.length > 0) {
				  $('#loginButton').hide();
				  $('#holdOptions').show();
				  // Remove the old options
				  var campus = document.placeHoldForm.campus;
				  campus.options.length = 0;
				  for (i = 0; i < locations.length; i++) {
					  campus.options[campus.options.length] = new Option($(locations[i]).text(), 
					      $(locations[i]).attr('id'), 
					      $(locations[i]).attr('selected'));
				  }
				  // Check to see if the user can cancel the hold
				  /*var allowHoldCancellation = $(data).find("AllowHoldCancellation").text();
				  if (allowHoldCancellation == 1) {
					  $('#cancelHoldDate').show();
				  } else {
					  $('#cancelHoldDate').hide();
				  }*/
				  // Enable the place hold button
				  $("#requestTitleButton").removeAttr('disabled');
			  } else {
				  $('#loginButton').show();
				  // document.getElementById('holdOptions').style.display = 'none';
				  alert('Invalid Login, please try again.');
			  }
		  } else {
			  alert('Error: Call to GetPreferredBranches failed.');
		  }
	  },
	  failure : function(transaction) {
		  alert('Failure: Could Not Load Preferred Branches');
	  },
	  error : function(jqXHR, textStatus, errorThrown) {
		  alert('Error: Could Not Load Preferred Branches');
	  }
	});
	
	return false;
}

function getGoDeeperData(dataType, id, isbn, upc) {
	var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";
	var params = "method=GetGoDeeperData&dataType=" + encodeURIComponent(dataType) + "&isbn=" + encodeURIComponent(isbn) + "&upc=" + encodeURIComponent(upc);
	var fullUrl = url + "?" + params;
	$.ajax( {
		url : fullUrl,
		success : function(data) {
			$('#goDeeperOutput').html(data);
		}
	});
}

var seriesScroller;

function GetEnrichmentInfo(id, isbn, upc) {
	var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";
	var params = "method=GetEnrichmentInfo&isbn=" + encodeURIComponent(isbn) + "&upc=" + encodeURIComponent(upc);
	var fullUrl = url + "?" + params;
	$.ajax( {
		url : fullUrl,
		success : function(data) {
			var similarAuthorData = $(data).find("SimilarAuthors").text();
			if (similarAuthorData) {
				if (similarAuthorData.length > 0) {
					$("#similarAuthorPlaceholder").html(similarAuthorData);
					$("#similarAuthorsSidegroup").show();

				}
			}
			var similarTitleData = $(data).find("SimilarTitles").text();
			if (similarTitleData) {
				if (similarTitleData.length > 0) {
					$("#similarTitlePlaceholder").html(similarTitleData);
					$("#relatedTitles").hide();
					$("#similarTitles").show();
					$("#similarTitlePlaceholder").show();
					$("#similarTitlesSidegroup").show();
				}
			}
			var seriesData = $(data).find("SeriesInfo").text();
			if (seriesData && seriesData.length > 0) {
				
				seriesScroller = new TitleScroller('titleScrollerSeries', 'Series', 'seriesList');

				seriesData = $.parseJSON(seriesData);
				if (seriesData.titles.length > 0){
					$('#list-series-tab').show();
					seriesScroller.loadTitlesFromJsonData(seriesData);
				}
			}
			var showGoDeeperData = $(data).find("ShowGoDeeperData").text();
			if (showGoDeeperData) {
				$('#goDeeperLink').show();
			}
		}
	});
}

function GetProspectorInfo(id) {
	var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";
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
			}
			var prospectorCopies = $(data).find("OwningLibrariesFormatted").text();
			if (prospectorCopies && prospectorCopies.length > 0) {
				$("#prospectorHoldingsPlaceholder").html(prospectorCopies);
			}
		}
	});
}

function GetHoldingsInfo(id) {
	var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";
	var params = "method=GetHoldingsInfo";
	var fullUrl = url + "?" + params;
	$.ajax( {
		url : fullUrl,
		success : function(data) {
			var holdingsData = $(data).find("Holdings").text();
			if (holdingsData) {
				if (holdingsData.length > 0) {
					if (holdingsData.match(/No Copies Found/i)){
						$("#moredetails-tabs").tabs("select", 1);
						$("#moredetails-tabs").tabs("remove", 0);
					}else{
						$("#holdingsPlaceholder").html(holdingsData);
					}
				}
			}
			var holdingsSummary = $(data).find("HoldingsSummary").text();
			if (holdingsSummary) {
				if (holdingsSummary.length > 0) {
					$("#holdingsSummaryPlaceholder").html(holdingsSummary);
				}
			}
			var showPlaceHold = $(data).find("ShowPlaceHold").text();
			if (showPlaceHold) {
				if (showPlaceHold.length > 0 && showPlaceHold == 1) {
					$(".requestThisLink").show();
				}
			}
			var eAudioLink = $(data).find("EAudioLink").text();
			if (eAudioLink) {
				if (eAudioLink.length > 0) {
					$("#eAudioLink" + id).html("<a href='" + eAudioLink + "'><img src='" + path + "/interface/themes/wcpl/images/access_eaudio.png' alt='Access eAudio'/></a>");
					$("#eAudioLink" + id).show();
				}
			}
			var eBookLink = $(data).find("EBookLink").text();
			if (eBookLink) {
				if (eBookLink.length > 0) {
					$("#eBookLink" + id).html("<a href='" + eBookLink + "'><img src='" + path + "/interface/themes/wcpl/images/access_ebook.png' alt='Access eBook'/></a>");
					$("#eBookLink" + id).show();
				}
			}
		}
	});
}

function GetHoldingsInfoMSC(id) {
	var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";
	var params = "method=GetHoldingsInfo";
	var fullUrl = url + "?" + params;
	$.ajax( {
		url : fullUrl,
		success : function(data) {
			var holdingsData = $(data).find("Holdings").text();
			if (holdingsData) {
				if (holdingsData.length > 0) {
					$("#holdingsPlaceholder").html(holdingsData);
				}
			}
			var summaryDetails = $(data).find("SummaryDetails");
			var showPlaceHold = summaryDetails.find("showplacehold").text();
			if (showPlaceHold == 1) {
				$(".requestThisLink").show();
			}
			var callNumber = summaryDetails.find("callnumber").text();
			$("#callNumberValue").html(callNumber);
			var location = summaryDetails.find("availableAt").text();
			$("#locationValue").html(location);
			var status = summaryDetails.find("status").text();
			if (status == "Available At"){
				status = "Available";
			}
			$("#statusValue").html(status);
			$("#statusValue").addClass(summaryDetails.find("class").text());
			if (summaryDetails.find("isDownloadable").text() == "1"){
				$("#downloadLinkValue").html("<a href='" + decodeURIComponent(summaryDetails.find("downloadLink").text()) + "'>" + summaryDetails.find("downloadText").text() + "</a>");
				$("#downloadLink").show();
			}
		}
	});
}

function GetReviewInfo(id, isbn) {
	var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";
	var params = "method=GetReviewInfo&isbn=" + encodeURIComponent(isbn);
	var fullUrl = url + "?" + params;
	$.ajax( {
		url : fullUrl,
		success : function(data) {
			var reviewsData = $(data).find("Reviews").text();
			if (reviewsData) {
				if (reviewsData.length > 0) {
					$("#reviewPlaceholder").html(reviewsData);
				}
			}
		}
	});
}

function GetDescription(id) {
	var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX/";
	var params = "method=getDescription";
	var fullUrl = url + "?" + params;
	var placeholder = "#descriptionPlaceholder" + id.replace(".", "");
	$.ajax( {
		url : fullUrl,
		success : function(data) {
			var descriptionData = $(data).find("description").text();
			if (descriptionData) {
				if (descriptionData.length > 0) {
					// TODO: this will need to have the id attached to it so that the id
					// is unique.
					$(placeholder).html(descriptionData);
				}
			}
		}
	});
}

libraryThingWidgetsLoaded = function(){
	var ltfl_tagbrowse_content = $('#ltfl_tagbrowse').html();
	if (!ltfl_tagbrowse_content.match(/loading_small\.gif/)){
		 $("#ltfl_tagbrowse_button").show();
	}
	var ltfl_series_content = $('#ltfl_series').html();
	if (!ltfl_series_content.match(/loading_small\.gif/)){
		 $("#ltfl_series_button").show();
	}
	var ltfl_awards_content = $('#ltfl_awards').html();
	if (!ltfl_awards_content.match(/loading_small\.gif/)){
		 $("#ltfl_awards_button").show();
	}
	var ltfl_similars_content = $('#ltfl_similars').html();
	if (!ltfl_similars_content.match(/loading_small\.gif/)){
		 $("#ltfl_similars_button").show();
	}
	var ltfl_related_content = $('#ltfl_related').html();
	if (!ltfl_related_content.match(/loading_small\.gif/)){
		 $("#ltfl_related_button").show();
	}
}
