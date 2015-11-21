/**
 * Created by mark on 2/11/14.
 */
VuFind.EContent = (function(){
	return {
		submitHelpForm: function(){
			var url = Globals.path + '/Help/eContentSupport';
			$.ajax({
				type: "POST",
				url: url,
				data: $("#eContentSupport").serialize(), // serializes the form's elements.
				success: function(data){
					var jsonData = JSON.parse(data);
					VuFind.showMessage(jsonData.title, jsonData.message);
				},
				failure: function(data){
					alert("Could not submit the form");
				}
			});

			return false;
		}
	}
}(VuFind.EContent));
