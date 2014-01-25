VuFind.Ratings = (function(){
	$(document).ready(function(){
		VuFind.Ratings.initializeRaters();
	});
	return{
		initializeRaters: function(){
			$(".rater").each(function(){
				var ratingElement = $(this);
				//Add additional elements to the div

				var module = ratingElement.data("module");
				var userRating = ratingElement.data("user_rating");
				//Setup the rater
				var options = {
					module: module,
					recordId: ratingElement.data("short_id"),
					rating: parseFloat(userRating > 0 ? userRating : ratingElement.data("average_rating")) ,
					postHref: Globals.path + "/" + module + "/" + ratingElement.data("record_id") + "/AJAX?method=RateTitle"
				};
				ratingElement.rater(options);
			});
		},

		doRatingReview: function (rating, module, id){
			if (rating <= 2){
				msg = "We're sorry you didn't like this title.  Would you like to add a review explaining why to help other users?";
			}else{
				msg = "We're glad you liked this title.  Would you like to add a review explaining why to help other users?";
			}
			if (confirm(msg)){
				var reviewForm;
				if (module == 'EcontentRecord'){
					reviewForm = $("#userecontentreview" + id);

				}else{
					reviewForm = $("#userreview" + id);
				}
				reviewForm.find(".rateTitle").hide();
				reviewForm.show();
			}
		}
	};
}(VuFind.Ratings));
