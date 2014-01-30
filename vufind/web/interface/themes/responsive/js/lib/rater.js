//copyright 2008 Jarrett Vance
//http://jvance.com
$.fn.rater = function(options) {
	var opts = $.extend( {}, $.fn.rater.defaults, options);
	return this.each(function() {
		var $this = $(this);
		var $on = $this.find('.ui-rater-starsOn');
		var $off = $this.find('.ui-rater-starsOff');

		if (opts.size == undefined) opts.size = $off.height();
		if (opts.rating == undefined) {
			opts.rating = $on.width() / $off.width();
		}else{
			$on.width($off.width() * (opts.rating / opts.ratings.length));
		}
		if (opts.id == undefined) opts.id = $this.attr('id');


		if (!$this.hasClass('ui-rater-bindings-done')) {
			$this.addClass('ui-rater-bindings-done');
			$off.mousemove(function(e) {
				var left = e.clientX - $off.offset().left;
				var width = $off.width() - ($off.width() - left);
				width = Math.min(Math.ceil(width / (opts.size / opts.step)) * opts.size / opts.step, opts.size * opts.ratings.length)
				$on.width(width);
				var r = Math.round($on.width() / $off.width() * (opts.ratings.length * opts.step)) / opts.step;
				$this.attr('title', 'Click to Rate "' + (opts.ratings[r - 1] == undefined ? r : opts.ratings[r - 1]) + '"') ;
			}).hover(function(e) { $on.addClass('ui-rater-starsHover'); }, function(e) {
						$on.removeClass('ui-rater-starsHover'); $on.width(opts.rating * opts.size);
					}).click(function(e) {
						var r = Math.round($on.width() / $off.width() * (opts.ratings.length * opts.step)) / opts.step;
						$.fn.rater.rate($this, opts, r);
					}).css('cursor', 'pointer'); $on.css('cursor', 'pointer');
		}

	});
};



$.fn.rater.defaults = {
	postHref : location.href,
	ratings: ['Hated It', "Didn't Like It", 'Liked It', 'Really Liked It', 'Loved It'],
	step : 1
};

$.fn.rater.rate = function($this, opts, rating) {
	var $on = $this.find('.ui-rater-starsOn');
	var $off = $this.find('.ui-rater-starsOff');
	if (Globals.loggedIn){
		$off.fadeTo(600, 0.4, function() {
			$.ajax( {
				url : opts.postHref,
				type : "POST",
				data : 'id=' + opts.id + '&rating=' + rating,
				complete : function(req) {
					if (req.status == 200) { // success
						opts.rating = parseFloat(req.responseText);
						$off.unbind('click').unbind('mousemove').unbind('mouseenter').unbind('mouseleave');
						$off.css('cursor', 'default'); $on.css('cursor', 'default');
						$off.fadeTo(600, 0.1, function() {
							$on.removeClass('ui-rater-starsHover').width(opts.rating * opts.size);
							$off.fadeTo(500, 1);
							$on.addClass('userRated');
							$this.attr('title', 'Your rating: ' + rating.toFixed(1));
							if ($this.data('show_review') == true){
								VuFind.Ratings.doRatingReview(rating, opts.module, opts.recordId);
							}
						});
					} else { // failure
						alert(req.responseText);
						$off.fadeTo(2200, 1);
					}
				}
			});
		});
	}else{
		ajaxLogin(function(){
			$.fn.rater.rate($this, opts, rating);
		});
	}
};