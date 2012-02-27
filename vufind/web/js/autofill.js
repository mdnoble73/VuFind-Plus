try{
$(document).ready(
function() {
	try{
		$("#lookfor").autocomplete({
			source: function(request, response){
				var url = path + "/Search/AJAX?method=GetAutoSuggestList&type=" + $("#type").val() + "&searchTerm=" +  $("#lookfor").val();
				$.ajax({
					url: url,
					dataType: "json",
					success: function(data){
						response(data);
					}
				});
			},
			position: {
				my: "left top",
				at: "left bottom",
				of: "#lookfor",
				collision: "fit"
			},
			minLength: 4,
			delay: 600
		});
	} catch (e) {
		alert("error during autocomplete setup" + e);
	}
});
} catch (e) {
	alert("error during autocomplete setup" + e);
}

