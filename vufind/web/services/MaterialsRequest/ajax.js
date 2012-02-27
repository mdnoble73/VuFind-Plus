function cancelMaterialsRequest(id){
	if (confirm("Are you sure you want to cancel this request?")){
		var url = path + "/MaterialsRequest/AJAX?method=CancelRequest&id=" + id;
		$.getJSON(
			url,
			function(data){
				if (data.success){
					alert("Your request was cancelled successfully.");
					window.location.href = window.location.href;
				}else{
					alert(data.error);
				}
				
			}
		);
		return false;
	}else{
		return false;
	}
}

function showMaterialsRequestDetails(id){
	ajaxLightbox(path + "/MaterialsRequest/AJAX?method=MaterialsRequestDetails&id=" +id );
}

function updateSelectedRequests(){
	var selectedRequests = getSelectedRequests();
	if (selectedRequests.length == 0){
		return false;
	}
	$.("#updateRequests").submit();
	return true;
}

function getSelectedRequests(){
	var selectedRequests = $("input.select:checked").map(function() {
		return $(this).attr('name') + "=" + $(this).val();
	}).get().join("&");
	if (selectedRequests.length == 0){
		var ret = confirm('You have not selected any requests, process all requests?');
		if (ret == true){
			selectedRequests = $("input.select").map(function() {
				return $(this).attr('name') + "=on";
			}).get().join("&");
			$('.select').attr('checked', 'checked');
		}
	}
	return selectedRequests;
}