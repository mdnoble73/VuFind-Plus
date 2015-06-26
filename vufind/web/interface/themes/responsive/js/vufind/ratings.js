VuFind.Ratings = (function(){
	//TODO only initialize on pages with ratings included.
	$(document).ready(function(){
		VuFind.Ratings.initializeRaters();
	});
	return{
		initializeRaters: function(){
			$(".rater").each(function(){
				var ratingElement = $(this),
						userRating = ratingElement.data("user_rating"),
						id = ratingElement.data("id"),
						options = {
							id: id,
							rating: parseFloat(userRating > 0 ? userRating : ratingElement.data("average_rating")) ,
							postHref: Globals.path + "GroupedWork/" + id + "/AJAX?method=RateTitle"
						};
				ratingElement.rater(options);
			});
			// old code included in this version
			//$(".rater").each(function(){
			//	var ratingElement = $(this);
			//
			//	//Add additional elements to the div
			//	// (Grouped Work is the only module the ratings will be related to now. plb 6-26-2015)
			//	//var module = ratingElement.data("module");
			//	var userRating = ratingElement.data("user_rating");
			//	//var recordId = ratingElement.data("record_id");
			//	//var shortId = ratingElement.data("short_id");
			//	var id = ratingElement.data("id");
			//	//if (shortId == null){
			//	//	shortId = id;
			//	//}
			//	//if (recordId == null){
			//	//	recordId = id;
			//	//}
			//
			//	//Setup the rater
			//	var options = {
			//		//module: module,
			//		id: id,
			//		rating: parseFloat(userRating > 0 ? userRating : ratingElement.data("average_rating")) ,
			//		//postHref: Globals.path + module + "/" + recordId + "/AJAX?method=RateTitle"
			//		postHref: Globals.path + "GroupedWork/" + id + "/AJAX?method=RateTitle"
			//	};
			//	ratingElement.rater(options);
			//});
		},

		doRatingReview: function (id){
			VuFind.showMessageWithButtons('Add a Review',
					'Would you like to add a review explaining your rating to help other users?',
					'<span class="btn btn-primary" onclick="VuFind.GroupedWork.showReviewForm('+id+')">Add a Review</span>'
			);
			//if (confirm('Would you like to add a review explaining your rating to help other users?')){
			//	VuFind.GroupedWork.showReviewForm(id);
			//}
		}

		// Older version of the doRatingReview function, signature is changed above. plb 6-26-2015
		//doRatingReview: function (rating, module, id){
		//	if (rating <= 2){
		//		msg = "We're sorry you didn't like this title.  Would you like to add a review explaining why to help other users?";
		//	}else{
		//		msg = "We're glad you liked this title.  Would you like to add a review explaining why to help other users?";
		//	}
		//	if (confirm(msg)){
		//		VuFind.GroupedWork.showReviewForm(id);
		//	}
		//	//	var reviewForm;
		//	//	if (module == 'EcontentRecord'){
		//	//		reviewForm = $("#userecontentreview" + id);
		//	//
		//	//	}else{
		//	//		reviewForm = $("#userreview" + id);
		//	//	}
		//	//	reviewForm.find(".rateTitle").hide();
		//	//	reviewForm.show();
		//	//}
		//}
	};
}(VuFind.Ratings));
