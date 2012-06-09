(function ($) {
  $(document).ready(function() {
    if (!($.cookie('anythink_survey_closed') || false)) {
      var anythink_survey = new anythink.anythink_survey();
      anythink_survey.init();
    };
  });

  // Trigger survey fly-in.
  anythink.anythink_survey = function() {

    anythink.settings.anythink_survey = {};
    anythink.settings.anythink_survey.filter_ips = new Array(
      '67.148.54.142',  // Commerce City
      '67.148.54.110',  // Pearl Mack
      '173.14.21.221',  // Bennett
      '67.148.118.130', // Washington St
      '205.168.66.250', // Brighton
      '67.131.236.198', // Wright Farms
      '67.134.224.110', // Huron
      '68.177.85.174',  // Proxy 1
      '67.135.167.165'  // Proxy 2
    );

    // We'll need to rewrite these vars.
    anythink.settings.anythink_survey.ip_script = 'http://www.anythinklibraries.org/sites/all/modules/custom/anythink_survey/get-ip.php'

    var _self = this;

    // Return unix timestamp.
    this.unix = function() {
      return Math.round(new Date().getTime() / 1000);
    }

    this.init = function() {
        // Survey starts as being invisible.
        anythink.settings.anythink_survey.visible = false;
        // Number of seconds to wait before invoking test.
        anythink.settings.anythink_survey.timeout = 1;
        // Fraction of page loads for which to load the test.
        anythink.settings.anythink_survey.frequency = 1;

        // Fake this out for getScript.
        Drupal = {settings: {anythink_survey: {ip: 0}}}

        // Load IP address check server-side, outside of Drupal bootstrap.
        $.getScript(anythink.settings.anythink_survey.ip_script, function() {
          // This could probably be refactored.
          anythink.settings.anythink_survey.ip = Drupal.settings.anythink_survey.ip;
          // Check IP address.
          anythink.settings.anythink_survey.internal_ip = $.inArray(anythink.settings.anythink_survey.ip, anythink.settings.anythink_survey.filter_ips) > -1;
          // See if this is a subsequent request.
          var initiated = ($.cookie('anythink_survey_init') || false);
          // Invoke for given frequency on the frontpage.
          if (Math.random() <= anythink.settings.anythink_survey.frequency || initiated) {
            // Start timer.
            if (!initiated) {
              $.cookie('anythink_survey_init', 1);
              $.cookie('anythink_survey_time', _self.unix());
              var survey_time = _self.unix();
            }
            else {
              var survey_time = $.cookie('anythink_survey_time');
            }
            setInterval(function() {
              // If the survey is not presently visible, invoke the slide-in.
              // after being on the site.
              if (!anythink.settings.anythink_survey.visible
                &&
                _self.unix() - anythink.settings.anythink_survey.timeout > survey_time) {
                _self.invoke1();
              };
            }, 1000);
          };
        });
    }

    // Bring in the survey.
    this.invoke1 = function() {
      var box = $('<div class="survey-container"><div class="survey-faux-wrapper"></div><div class="survey"><div class="survey-inner"><p>Help us improve our website.</p><p><a id="survey-link" target="_blank" title="Anythink website survey" href="http://www.surveymonkey.com/s/anythink-catalog" class="button">Take our survey</a></p></div></div></div>');

      // Add close link.
      box.find('.survey-inner').append('<a id="survey-close" title="Close" href="/"><span class="text">Close</span><span class="x">&times;</span></a>');

      // Initialize display.
      box.css({
        display: 'none',
        position: 'fixed',
        bottom: '100px',
        right: '-200px',
        zIndex: 100
      });

      // Place in DOM.
      box.appendTo($('body'));

      // Use .survey to get both sidebar block and hover div.
      $('.survey').find('a').bind('click', function() {
        // Hide the box.
        box.fadeOut('fast');
        // Set cookie to not show again for 30 days, only if it is a
        // non-internal IP.
        if (!anythink.settings.anythink_survey.internal_ip) {
          $.cookie('anythink_survey_closed', 1, {expires: 30});
        }
        // Close link has dummy href.
        if (this.id == 'survey-close') {
          return false;
        };
      });

      // Slide in.
      box.show().animate({
        right: '0px'
      });

      // Mark as visible to prevent further invocation.
      anythink.settings.anythink_survey.visible = true;
    }
  }
})(jQuery);
