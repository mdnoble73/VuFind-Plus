VuFind.Responsive = (function(){
	$(document).ready(function(){
		$(window).resize(VuFind.Responsive.adjustLayout);
		$(window).trigger('resize');
	});

	return {
		adjustLayout: function(){
			// get resolution
			var resolution = document.documentElement.clientWidth;

			// Handle Mobile layout
			if (resolution <= 980) {
				//Convert tabs to dropdown lists for phone
				if( $('.select-menu').length === 0 ) {

					// create select menu
					var select = $('<select></select>');

					// add classes to select menu
					select.addClass('select-menu input-block-level');

					// each link to option tag
					$('.nav-tabs li a').each(function(){
						// create element option
						var option = $('<option></option>');

						// add href value to jump
						$this = $(this);
						option.val($this.attr('href'));

						// add text
						option.text($this.text());

						// append to select menu
						select.append(option);
					});

					// add change event to select
					select.change(function(){
						//Show the correct tab
						$('.nav-tabs').parent().children('.tab-content').children('.tab-pane').removeClass('active');
						var selectedTabId = $('.select-menu').val();
						$(selectedTabId).addClass('active');
					});

					// add select element to dom, hide the .nav-tabs
					$('.nav-tabs').before(select).hide();
				}
			}

			// max width 979px
			if (resolution > 979) {
				$('.select-menu').remove();
				$('.nav-tabs').show();
			}
		}
	};
}(VuFind.Responsive || {}));