(function ($) {

  $(document).ready(function() {
    // Get the ratings.
    doGetRatingsAnythink();
    // Get status summaries.
    doGetStatusSummariesAnythink();
    // Setup descriptions
    doResultDescriptionsAnythink();

    // Do facet collapse.
    var facet_groups = $('.facet-group');
    if (facet_groups.length > 0) {
      facet_groups.each(function() {
        var facet_group = $(this);
        var items_to_hide = facet_group.find('.less');
        if (items_to_hide.length > 0) {
          // Hide items.
          items_to_hide.hide();
          // Create toggle link.
          var toggle = $('<a class="facet-toggle toggle-more" href="#' + this.id + '">' + items_to_hide.length + ' more...</a>');
          // Event handlers to toggle link.
          toggle.bind('click', {items_to_hide: items_to_hide}, function(event) {
            var $this = $(this);
            var items_to_hide = event.data.items_to_hide;
            var facet_id = event.data.facet_id;
            if ($this.hasClass('toggle-more')) {
              $this.text('Show less');
              $this.removeClass('toggle-more');
              $this.addClass('toggle-less');
              items_to_hide.show();
              $this.blur();
              return false;
            }
            else {
              $this.removeClass('toggle-less');
              $this.addClass('toggle-more');
              $this.text(items_to_hide.length + ' more...');
              items_to_hide.hide();
              $this.blur();
              return false;
            }
          });
          // Append toggle link.
          facet_group.append(toggle);
        };
      });

      // Prefill years.
      var publication_year_facet = $('#facet-Publication-Year');
      if (publication_year_facet.length > 0) {
        publication_year_facet.find('.prefill').each(function() {
          var $this = $(this);
          $this.bind('click', function() {
            publication_year_facet.find('.year-to').val('');
            publication_year_facet.find('.year-from').val($this.attr('data-year'));
            $(this).blur();
            return false;
          });
        });
      };
    };
  });


  // Get a list of the records on the page.
  get_records_anythink = function() {
    if (!anythink.settings.records) {
      anythink.settings.records = new Array();
      var records = $('.record');
      if (records.length > 0) {
        records.each(function() {
          var type = $(this).attr('data-type');
          var id = $(this).attr('data-summId');
          anythink.settings.records.push({
            id: id,
            type: type});
        });
      };
    };
    return anythink.settings.records;
  }

  doResultDescriptionsAnythink = function() {
    // Grab record IDs.
    get_records_anythink();
    if (anythink.settings.records.length > 0) {
      for (var i=0; i < anythink.settings.records.length; i++) {
        var item = anythink.settings.records[i];
        var container = $('#description-' + item.id);
        // Create link.
        var link = $('<a class="read-description" href="#">Read description</a>');
        link.bind('click', {item: item, container: container}, function(event) {
          var id = event.data.item.id;
          var container = event.data.container;
          $(this).remove();
          var loader = $('<em class="fine-print loading">Loading description...</em>');
          loader.hide().appendTo(container).fadeIn('fast', 
            function() {
              var info = resultDescriptionAnythink(id, id);
              container.empty();
              var stuff = $('<div><div><strong>Description</strong>: ' + info.description + '</div>' +
              '<div><strong>Length</strong>: ' + info.length + '</div>' +
              '<div><strong>Publisher</strong>: ' + info.publisher + '</div></div>');
              stuff.hide().appendTo(container).slideDown();
            });
          return false;
        });
        container.append(link);
      };
    };
  }

  // Reimplement resultDescription().
  resultDescriptionAnythink = function(shortid, id, type) {
    if (!type) {
      var type = 'VuFind';
    };
    if (type != 'VuFind'){
      var loadDescription = path + "/EcontentRecord/" + id + "/AJAX/?method=getDescription";
    }
    else {
      var loadDescription = path + "/Record/" + id + "/AJAX/?method=getDescription";
    }

    var rawData = $.ajax(loadDescription,{
      async: false
    }).responseText;
    var xmlDoc = $.parseXML(rawData);
    var data = $(xmlDoc);

    return {
      id: id,
      description: data.find('description').text(),
      length: data.find('length').text(),
      publisher: data.find('publisher').text(),
    };
  };

  // Reimplement doGetRatings().
  doGetRatingsAnythink = function() {
    // Grab record IDs.
    get_records_anythink();
    if (anythink.settings.records.length > 0) {
      // Parse out rating IDs.
      var url = anythink.settings.path + "/Search/AJAX";
      var data = "method=GetRatings";
      for (var j=0; j < anythink.settings.records.length; j++) {
        var item = anythink.settings.records[j];
        if (item.type == 'VuFind') {
          data += "&id[]=" + encodeURIComponent(item.id);
        }
        else if (item.type == 'eContent') {
          data += "&econtentId[]=" + encodeURIComponent(item.id);
        }
      }
      data += "&time=" + anythink.settings.request_time;

      $.getJSON(url, data,
        function(data, textStatus) {

          parse_ratings(data['standard']);

          // @todo: move dom processing elsewhere
          var eContentRatings = data['eContent'];
          for (var id in eContentRatings){
            // Load the rating for the title
            if (eContentRatings[id].user != null && eContentRatings[id].user > 0){
              $('.rateEContent' + id).each(function(index){
                $(this).rater({'rating':eContentRatings[id].user, 'doBindings':false, module:'EcontentRecord', recordId: id });
              });
            }else{
              $('.rateEContent' + id).each(function(index){$(this).rater({'rating':eContentRatings[id].average, 'doBindings':false, module:'EcontentRecord', recordId: id});});
            }
            $('.rateEContent' + id + ' .ui-rater-rating-' + id).each(function(index){$(this).text( eContentRatings[id].average );});
            $('.rateEContent' + id + ' .ui-rater-rateCount-' + id).each(function(index){$(this).text( eContentRatings[id].count );});
          }
        }
      );
    }
  }

  parse_ratings = function(ratings) {
    for (var id in ratings) {
      var rating = parseInt(ratings[id].average);
      var item = $('#rating-' + id)
      .addClass('rating-' + rating)
      .attr('title', 'Average rating: ' + rating + '. Rated ' + ratings[id].count + ' time' + (ratings[id].count != 1 ? 's':'') + '.');
      if (!ratings[id].user) {
        var link = $('<a class="rate-this" href="#">Rate this...</a>');
        link.bind('click', {id: id}, function(event) {
          var id = event.data.id;
          var container = $('#rate-' + id);
          container
            .empty()
            .append('<span class="ui-rater"><span class="ui-rater-starsOff"><span class="ui-rater-starsOn"></span></span></span>')
            .rater({
              recordId: id,
              postHref: '/Record/' + id + '/AJAX?method=RateTitle',
            });
          return false;
        });
        $('#rate-' + id).append(link);
      }
      else {
        $('#rate-' + id).append('<span class="small fine-print">Your rating:</span> <span class="rating rating-' + ratings[id].user + '"></span>');
      }
    }
  }

  // Reimplementation of doGetStatusSummaries()
  doGetStatusSummariesAnythink = function() {
    // Get records if we have them.
    get_records_anythink();

    if (anythink.settings.records.length > 0) {
      var url = path + "/Search/AJAX?method=GetStatusSummaries";
      var eContentUrl = path + "/Search/AJAX?method=GetEContentStatusSummaries";

      for (var j=0; j < anythink.settings.records.length; j++) {
        var item = anythink.settings.records[j];
        if (item.type == 'VuFind') {
          url += "&id[]=" + encodeURIComponent(item.id);
        }
        else if (item.type == 'eContent') {
          eContentUrl += "&id[]=" + encodeURIComponent(item.id);
        }
      }

      url += "&time=" + anythink.settings.request_time;
      eContentUrl += "&time=" + anythink.settings.request_time;

      // Get status summaries
      prepare_status_summaries();

      $.ajax({
        url: url, 
        success: function(data) {
          var summaries = new Array();
          $(data).find('item').each(function() {
            var $this = $(this);
            var item = {};
            item.id = $this.find('id').text();
            item.show_hold = $this.find('showplacehold').text() == '1';
            item.formatted_summary = $this.find('formattedHoldingsSummary').text();
            summaries.push(item);
          });
          // Parse into markup.
          parse_status_summaries(summaries);
        }
      });

      // $.ajax({
      //   url: eContentUrl, 
      //   success: function(data){
      //     var items = $(data).find('item');
      //     $(items).each(function(index, item){
      //       var elemId = $(item).attr("id") ;
      //       $('#holdingsEContentSummary' + elemId).html($(item).find('formattedHoldingsSummary').text());
      //       if ($(item).find('showplacehold').text() == 1){
      //         $("#placeEcontentHold" + elemId).show();
      //       }else if ($(item).find('showcheckout').text() == 1){
      //         $("#checkout" + elemId).show();
      //       }else if ($(item).find('showaccessonline').text() == 1){
      //         $("#accessOnline" + elemId).show();
      //       }else if ($(item).find('showaddtowishlist').text() == 1){
      //         $("#addToWishList" + elemId).show();
      //       }
      //     });
      //   }
      // });

      // Get OverDrive status summaries one at a time since they take several seconds to load
      // for (var j=0; j < anythink.settings.records.length; j++) {
      //   var item = anythink.settings.records[j];
      //   if (item.type == 'OverDrive') {
      //     var overDriveUrl = path + "/Search/AJAX?method=GetEContentStatusSummaries";
      //     overDriveUrl += "&id[]=" + encodeURIComponent(item.id);
      //     $.ajax({
      //       url: overDriveUrl, 
      //       success: function(data){
      //         var items = $(data).find('item');
      //         $(items).each(function(index, item){
      //           var elemId = $(item).attr("id") ;
      //           $('#holdingsEContentSummary' + elemId).html($(item).find('formattedHoldingsSummary').text());
      //           if ($(item).find('showplacehold').text() == 1){
      //             $("#placeEcontentHold" + elemId).show();
      //           }else if ($(item).find('showcheckout').text() == 1){
      //             $("#checkout" + elemId).show();
      //           }else if ($(item).find('showaccessonline').text() == 1){
      //             $("#accessOnline" + elemId).show();
      //           }else if ($(item).find('showaddtowishlist').text() == 1){
      //             $("#addToWishList" + elemId).show();
      //           }
      //         });
      //       }
      //     });
      //   };
      // }
    };
   }

   prepare_status_summaries = function() {
     $('.holdings-summary').append('<em class="fine-print loading">Loading holdings summary...</em>');
   }

   parse_status_summaries = function(summaries) {
     $.each(summaries, function(key, item) {
       // Inject AHAH.
       $('#holdings-summary-' + item.id).fadeOut('fast', function(){
         $(this).html(item.formatted_summary).slideDown();
       });
     });
   }

})(jQuery);