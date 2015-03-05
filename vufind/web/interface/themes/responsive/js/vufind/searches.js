VuFind.Searches = (function(){
	$(document).ready(function(){
		VuFind.Searches.enableSearchTypes();
		VuFind.Searches.initAutoComplete();
	});
	return{
		searchGroups: [],

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
			var searchTypeElement = $("#searchSource");
			var selectedSearchType = $(searchTypeElement.find(":selected"));
			var catalogType = selectedSearchType.data("catalog_type");
			if (catalogType == "catalog"){
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


		processSearchForm: function(){
			//Get the selected search type submit the form
			var searchSource = $("#searchSource");
			if (searchSource.val() == 'existing'){
				$(".existingFilter").prop('checked', true);
				var originalSearchSource = $("#existing_search_option").data('original_type');
				searchSource.val(originalSearchSource);
			}
		},

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

		submitAdvancedSearch: function(){
			$('#advancedPopup').submit();
			return false;
		}
	}
}(VuFind.Searches || {}));