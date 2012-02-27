function doGenealogyReindex(){
	var moreData = true;
	var pathToCall = path + "/Admin/JSON?method=reindexGenealogy";
	$.getJSON(pathToCall, function(data){
		//update the progress bar
		var percentComplete = data.result.percentComplete;
		var moreData = data.result.moreData;
		var currentRecord = data.result.currentRecord;
		$("#progressbar").progressbar( "option", "value", percentComplete );
		$("#currentRecord").text(currentRecord);
		if (moreData){
			//If there is more data, call this method again. 
			doGenealogyReindex();
		}else{
			//If there is no more data, indicate that we are done and hide the progress bar.
			$("#progressbar").hide();
			$("#completionMessage").show();
		}
	});
}