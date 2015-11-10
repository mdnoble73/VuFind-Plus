VuFind.Searches = (function(){
	$(document).ready(function(){
		VuFind.Searches.enableSearchTypes();
		VuFind.Searches.initAutoComplete();
	});
	return{
		searchGroups: [],
		curPage: 1,
		displayMode: 'list', // default display Mode for results
		displayModeClasses: { // browse mode to css class correspondence
			covers:'home-page-browse-thumbnails',
			list:''
		},

		getPreferredDisplayMode: function(){
			if (!Globals.opac && VuFind.hasLocalStorage()){
				temp = window.localStorage.getItem('searchResultsDisplayMode');
				if (VuFind.Searches.displayModeClasses.hasOwnProperty(temp)) {
					VuFind.Searches.displayMode = temp; // if stored value is empty or a bad value, fall back on default setting ("null" is returned from local storage when not set)
					$('input[name="view"]','#searchForm').val(VuFind.Searches.displayMode); // set the user's preferred search view mode on the search box.
				}
			}
		},

		toggleDisplayMode : function(selectedMode){
			var mode = this.displayModeClasses.hasOwnProperty(selectedMode) ? selectedMode : this.displayMode, // check that selected mode is a valid option
					searchBoxView = $('input[name="view"]','#searchForm'), // display mode variable associated with the search box
					paramString = this.replaceQueryParam('page', '', this.replaceQueryParam('view',mode)); // set view in url and unset page variable
			this.displayMode = mode; // set the mode officially
			this.curPage = 1; // reset js page counting
			if (searchBoxView) searchBoxView.val(this.displayMode); // set value in search form, if present
			if (!Globals.opac && VuFind.hasLocalStorage() ) { // store setting in browser if not an opac computer
				window.localStorage.setItem('searchResultsDisplayMode', this.displayMode);
			}
			location.replace(location.pathname + paramString); // reloads page without adding entry to history
		},

		replaceQueryParam : function (param, newValue, search) {
			if (typeof search == 'undefined') search = location.search;
			var regex = new RegExp("([?;&])" + param + "[^&;]*[;&]?"),
					query = search.replace(regex, "$1").replace(/&$/, '');
			return newValue ? (query.length > 2 ? query + "&" : "?") + param + "=" + newValue : query;
	},

		getMoreResults: function(){
			var url = Globals.path + '/Search/AJAX',
					params = this.replaceQueryParam('page', this.curPage+1)+'&method=getMoreSearchResults',
					divClass = this.displayModeClasses[this.displayMode];
			params = this.replaceQueryParam('view', this.displayMode, params); // set the view url parameter just in case.
			if (params.search(/[?;&]replacementTerm=/) != -1) {
				var searchTerm = location.search.split('replacementTerm=')[1].split('&')[0];
				params = this.replaceQueryParam('lookfor', searchTerm, params);
			}
			$.getJSON(url+params, function(data){
				if (data.success == false){
					VuFind.showMessage("Error loading search information", "Sorry, we were not able to retrieve additional results.");
				}else{
					var newDiv = $(data.records).hide();
					$('.'+divClass).filter(':last').after(newDiv);
					newDiv.fadeIn('slow');
					if (data.lastPage) $('#more-browse-results').hide(); // hide the load more results
					else VuFind.Searches.curPage++;
				}
			}).fail(VuFind.ajaxFail);
			return false;
		},

		initAutoComplete: function(){
			try{
				$("#lookfor").autocomplete({
					source:function(request,response){
						var url=Globals.path+"/Search/AJAX?method=GetAutoSuggestList&searchTerm=" + $("#lookfor").val();
						$.ajax({
							url:url,
							dataType:"json",
							success:function(data){
								response(data);
							}
						});
					},
					position:{
						my:"left top",
						at:"left bottom",
						of:"#lookfor",
						collision:"none"
					},
					minLength:4,
					delay:600
				});
			}catch(e){
				alert("error during autocomplete setup"+e);
			}
		},

		/* Advanced Popup has been turned off. plb 10-22-2015
		addAdvancedGroup: function(button){
			var currentRow;
			if (button == undefined){
				currentRow = $(".advancedRow").last();
			}else{
				currentRow = $(button).closest(".advancedRow");
			}

			//Clone the current row and reset data and ids as needed.
			var clonedData = currentRow.clone();
			clonedData.find(".btn").removeClass('active');
			clonedData.find('.lookfor').val("");
			clonedData.insertAfter(currentRow);

			VuFind.Searches.resetAdvancedRowIds();
			return false;
		},

		deleteAdvancedGroup: function(button){
			var currentRow = $(button).closest(".advancedRow");
			currentRow.remove();

			VuFind.Searches.resetAdvancedRowIds();
			return false;
		},
*/
		sendEmail: function(){
			if (Globals.loggedIn){
				var from = $('#from').val();
				var to = $('#to').val();
				var message = $('#message').val();
				var related_record = $('#related_record').val();
				//var sourceUrl = encodeURIComponent(window.location.href);
				var sourceUrl = window.location.href;

				var url = Globals.path + "/Search/AJAX";
				//var params = "method=sendEmail&from=" + encodeURIComponent(from) + "&to=" + encodeURIComponent(to) + "&message=" + encodeURIComponent(message) + "&url=" + sourceUrl;
				//passing through getJSON() data array instead
				$.getJSON(url,
						{ // pass parameters as data
							method     : 'sendEmail'
							,from      : from
							,to        : to
							,message   : message
							,sourceUrl : sourceUrl
						},
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

		enableSearchTypes: function(){
			var searchTypeElement = $("#searchSource"); //Not The horizontal search source. TODO need to?
			var catalogType = "catalog";
			if (searchTypeElement){
				var selectedSearchType = $(searchTypeElement.find(":selected"));
				if (selectedSearchType){
					catalogType = selectedSearchType.data("catalog_type");
				}
			}
			if (catalogType == "catalog" || catalogType == null){
				$(".catalogType").show();
				$(".genealogyType").hide();
			}else{
				$(".catalogType").hide();
				$(".genealogyType").show();
			}
		},

		lastSpellingTimer: undefined,
		getSpellingSuggestion: function(query, process, isAdvanced){
			if (VuFind.Searches.lastSpellingTimer != undefined){
				clearTimeout(VuFind.Searches.lastSpellingTimer);
				VuFind.Searches.lastSpellingTimer = undefined;
			}

			var url = Globals.path + "/Search/AJAX?method=GetAutoSuggestList&searchTerm=" + query;
			//Get the search source
			if (isAdvanced){
				//Add the search type
			}
			VuFind.Searches.lastSpellingTimer = setTimeout(
					function(){
						$.get(url,
								function(data){
									process(data);
								},
								'json'
						)
					},
					500
			);
		},

		/* Advanced Popup has been turned off. plb 10-22-2015
		loadSearchGroups: function(){
			var searchGroups = VuFind.Searches.searchGroups;
			for (var i = 0; i < searchGroups.length; i++){
				if (i > 0){
					VuFind.Searches.addAdvancedGroup();
				}
				var searchGroup = searchGroups[i];
				var groupIndex = i+1;
				var searchGroupElement = $("#group" + groupIndex);
				searchGroupElement.find(".groupStartInput").val(searchGroup.groupStart);
				if (searchGroup.groupStart == 1){
					searchGroupElement.find(".groupStartButton").addClass("active");
				}
				searchGroupElement.find(".searchType").val(searchGroup.searchType);
				searchGroupElement.find(".lookfor").val(searchGroup.lookfor);
				searchGroupElement.find(".groupEndInput").val(searchGroup.groupEnd);
				if (searchGroup.groupEnd == 1){
					searchGroupElement.find(".groupEndButton").addClass("active");
				}
				searchGroupElement.find(".joinOption").val(searchGroup.join);
			}
			if (searchGroups.length == 0){
				VuFind.Searches.resetAdvancedRowIds();
			}
		},
*/

		processSearchForm: function(){
		// Check for Set Display Mode
		//	this.getPreferredDisplayMode();

			//Get the selected search type submit the form
			var searchSource = $("#searchSource");
			if (searchSource.val() == 'existing'){
				$(".existingFilter").prop('checked', true);
				var originalSearchSource = $("#existing_search_option").data('original_type');
				searchSource.val(originalSearchSource);
			}
		},

		/* Advanced Popup has been turned off. plb 10-22-2015
		resetAdvancedRowIds: function(){
			var searchRows = $(".advancedRow");
			searchRows.each(function(index, element){
				var indexVal = index + 1;
				var curRow = $(element);
				curRow.attr("id", "group" + indexVal);
				curRow.find(".groupStartInput")
						.prop("name", "groupStart[" + indexVal + "]")
						.attr("id", "groupStart" + indexVal + "Input");

				curRow.find(".groupStartButton")
						.data("hidden_element", "groupStart" + indexVal + "Input")
						.attr("id", "groupStart" + indexVal);

				curRow.find(".searchType")
						.attr("name", "searchType[" + indexVal + "]");

				curRow.find(".lookfor")
						.attr("name", "lookfor[" + indexVal + "]");

				curRow.find(".groupEndInput")
						.prop("name", "groupEnd[" + indexVal + "]")
						.attr("id", "groupEnd" + indexVal + "Input");

				curRow.find(".groupEndButton")
						.data("hidden_element", "groupEnd" + indexVal + "Input")
						.attr("id", "groupEnd" + indexVal);

				curRow.find(".joinOption")
						.attr("name", "join[" + indexVal + "]");
			});
			if (searchRows.length == 1){
				$(".deleteCriteria").hide();
				$(".groupStartButton").hide();
				$(".groupEndButton").hide();
			}else{
				$(".deleteCriteria").show();
				$(".groupStartButton").show();
				$(".groupEndButton").show();
			}
			var joinOptions = $(".joinOption");
			joinOptions.show();
			joinOptions.last().hide();
		},
*/
		resetSearchType: function(){
			if ($("#lookfor").val() == ""){
				$("#searchSource").val($("#default_search_type").val());
			}
			return true;
		},

		updateSearchTypes: function(catalogType, searchType, searchFormId){
			if (catalogType == 'catalog'){
				$("#basicType").val(searchType);
				$("#genealogyType").remove();
			}else{
				$("#genealogyType").val(searchType);
				$("#basicType").remove();
			}
			$(searchFormId).submit();
			return false;
		},

		filterAll: function(){
			// Go through all elements
			$(".existingFilter").prop('checked', true);
		},

/* Advanced Popup has been turned off. plb 10-22-2015
		submitAdvancedSearch: function(){
			$('#advancedPopup').submit();
			return false;
		}
*/
	}
}(VuFind.Searches || {}));