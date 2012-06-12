(function ($) {

  $(document).ready(function() {
    // Get the ratings.
    // doGetRatingsAnythink();
    // Get status summaries.
    // doGetStatusSummariesAnythink();
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
              $this.text('Show less')
              .removeClass('toggle-more')
              .addClass('toggle-less')
              .trigger('blur');
              items_to_hide.show();
              return false;
            }
            else {
              $this.removeClass('toggle-less')
              .addClass('toggle-more')
              .text(items_to_hide.length + ' more...')
              .trigger('blur');
              items_to_hide.hide();
              return false;
            }
          });
          // Append toggle link.
          facet_group.find('ul').append(toggle);
          toggle.wrap('<li class="toggle"/>');
        };
      });

      // Prefill years.
      var publication_year_facet = $('#facet-Publication-Year');
      if (publication_year_facet.length > 0) {
        publication_year_facet.find('.prefill').each(function() {
          $(this).bind('click', function() {
            publication_year_facet.find('.year-to').val('');
            publication_year_facet.find('.year-from').val($(this).attr('data-year'));
            $(this).blur();
            return false;
          });
        });
      };
    };

    // Set up fixed position element.
    anythink.settings.fixed_wrapper = $('#fixed-wrapper');
    anythink.settings.fixed_offset = anythink.settings.fixed_wrapper.offset();

    var fixed_wrapper = anythink.settings.fixed_wrapper;

    if (fixed_wrapper.length > 0) {
      // Bag buttons.
      $('.actions-cart a').each(function() {
        var $this = $(this);
        var book = {
          id: $this.attr('data-summId'),
          title: $this.attr('data-title')
        }
        if (bookInBagAnythink(book)) {
          $this.text('In cart');
          $this.addClass('in-cart');
        };
        $this.bind('click', {book: book}, function(event) {
          var $this = $(this);
          var book = event.data.book;
          if (!$this.hasClass('in-cart')) {
            _addToBag(book);
            $this.text('In cart');
            $this.addClass('in-cart');
          }
          else {
            _removeFromBag(book);
            $this.text('Add to cart +');
            $this.removeClass('in-cart');
          }
          _saveBagAsCookie();
          updateBag();
          return false;
        });
      });
    };

    // Navigate link.
    var navigate_link = $('#navigate-link');
    if (navigate_link.length > 0) {
      var iframe = $('<div id="column-outer-wrapper"><div id="column-outer"><iframe width="200" height="600" border="0" src="http://stage.anythinklibraries.org/vufind/sidebar"></div></div>');
      // Add iFrame.
      iframe.css({width: 0});
      $('#central').prepend(iframe);
      var central_column = $('#column-central');
      var orig_text = navigate_link.text();
      navigate_link.bind('click', function() {
        if (!navigate_link.hasClass('processed')) {
          navigate_link.addClass('processed');
          navigate_link.text('Hide');
          // navigate_link.fadeOut(100, function() {
            // Scale central column.
            fixed_wrapper.hide();
            central_column.animate({marginLeft: '220px'}, {
              duration: 250,
              complete: function() {
                anythinkResize();
                fixed_wrapper.fadeIn(100);
              }
            });
            iframe.css({width: '200px'});
          // });
        }
        else {
          navigate_link.removeClass('processed');
          navigate_link.text(orig_text);
          // Re scale.
          fixed_wrapper.hide();
          central_column.animate({marginLeft: 0}, {
            duration: 250,
            complete: function() {
              anythinkResize();
              fixed_wrapper.fadeIn(100);
            }
          });
          iframe.css({width: 0});
        }
        return false;
      });
    };

    // Implement collapsible fieldsets.
    var collapsibles = $('fieldset.anythink-collapsible');
    if (collapsibles.length > 0) {
      collapsibles.each(function() {
        var collapsible = $(this);
        var legend = collapsible.find('legend:first');
        legend.addClass('anythink-collapsible-label').bind('click', {collapsible: collapsible}, function(event) {
          var collapsible = event.data.collapsible;
          if (collapsible.hasClass('anythink-collapsed')) {
            collapsible.removeClass('anythink-collapsed');
          }
          else {
            collapsible.addClass('anythink-collapsed');
          }
        });
        // Init.
        collapsible.addClass('anythink-collapsed');
      });
    }

    // Fixed position container.
    $(document).bind('scroll', function() {
      anythinkResize();
    });
    $(window).bind('resize', function() {
      anythinkResize();
    });

    // @todo Refactor this to make more sense. Unfortunately depends on
    // JS outside of the theme directory. @see /services/Record/ajax.js
    var go_deeper = $('#goDeeperLink');
    // The cover image is the sister.
    var cover = go_deeper.next();
    // On load, size the link to be commensurate.
    cover.bind('load', function() {
      go_deeper.height(cover.height() + 10);
      var position = cover.position();
      go_deeper.css({
        top: position.top + 'px',
        left: position.left + 'px'
      });
    });
  });

  // Resize the fixed-position element.
  function anythinkResize() {
    var offset = anythink.settings.fixed_offset;
    var fixed_wrapper = anythink.settings.fixed_wrapper;
    fixed_wrapper.css({width: fixed_wrapper.parent().width() + 'px'});
    if (offset.top < $(window).scrollTop() && !fixed_wrapper.hasClass('cling')) {
      fixed_wrapper.addClass('cling');
      $('#search').prependTo(fixed_wrapper);
    }
    else if (offset.top >= $(window).scrollTop() && fixed_wrapper.hasClass('cling')){
      fixed_wrapper.removeClass('cling');
      $('#header-utility-top').after($('#search'));
    }
  }

  getWorldCatIdentifiersAnythink = function() {
    var title = $("#title").val();
    var author = $("#author").val();
    var format = $("#format").val();
    if (title == '' && author == ''){
      alert("Please enter a title and author before checking for an ISBN and OCLC Number");
      return false;
    }
    else {
      var requestUrl = path + "/MaterialsRequest/AJAX?method=GetWorldCatIdentifiers&title=" + encodeURIComponent(title) + "&author=" + encodeURIComponent(author)  + "&format=" + encodeURIComponent(format);
      var suggested_ids = $('#suggestedIdentifiers');
      suggested_ids.html('<div class="loading">Loading...</div>');
      suggested_ids.slideDown();
      $.getJSON(requestUrl, function(data){
        if (data.success == true){
          // Dislay the results of the suggestions
          suggested_ids.html(data.formattedSuggestions);
        }else{
          alert(data.error);
        }
      });
    }
  }

  setIsbnAndOclcNumberAnythink = function(title, author, isbn, oclcNumber) {
  	$("#title").val(title);
  	$("#author").val(author);
    $("#isbn").val(isbn);
    $("#oclcNumber").val(oclcNumber);
    var item = $('[data-isbn_oclc="' + isbn + '--' + oclcNumber +'"]').clone();
    $("#suggestedIdentifiers").empty().append(item);
    item.find('input').remove();
  }

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


  bookInBagAnythink = function(book) {
    var bookInBag = false;
    for (var i = 0; i < bookBag.length; i++) {
      if (bookBag[i].id == book.id){
        bookInBag = true;
        break;
      }
    }
    return bookInBag;
  }

  // Reimplement getSaveToListForm().
  getSaveToListFormAnythink = function(id, source) {
    if (loggedIn) {
      var url = path + "/Resource/Save?lightbox=true&id=" + id + "&source=" + source;
      ajaxLightboxAnythink(url);
    }
    else {
      ajaxLoginAnythink(function (){
        getSaveToListFormAnythink(id, source);
      });
    }
    return false;
  }

  // Reimplement ajaxLightbox().
  ajaxLightboxAnythink = function(urlToLoad, parentId, left, width, top, height){

    var loadMsg = $('#lightboxLoading').html();

    hideSelects('hidden');

    // Find out how far down the screen the user has scrolled.
    var new_top =  document.body.scrollTop;
    var lightbox = $('#lightbox');

    lightbox.css({
      height: $(document).height() + 'px',
    });
    lightbox.show();

    var popupbox = $('#popupbox');

    popupbox.html('<img src="' + path + '/images/loading.gif" /><br />' + loadMsg);
    // $('#popupbox').show();
    // $('#popupbox').css('top', '50%');
    // $('#popupbox').css('left', '50%');

    // if (parentId) {
    //   //Automatically position the lightbox over the cursor
    //   popupbox.position({
    //     my: "top right",
    //     at: "top right",
    //     of: parentId,
    //     collision: "flip"
    //   });
    // }
    // else {
      // if (!width) 
      width = '66%';
      // if (!height) 
      height = '66%';

      popupbox.css({
        width: width,
        height: height
        });

      // if (!left) left = '100px';
      // if (!top) top = '100px';

      popupbox.css({
        top: parseInt(new_top + ($(window).height() - popupbox.height())/2) + 'px',
        left: parseInt(($(window).width() - popupbox.width())/2) + 'px'
      });

      // $(document).scrollTop(0);
    // }

    $.get(urlToLoad, function(data) {
      popupbox.html(data);

      popupbox.show();
      if ($("#popupboxHeader").length > 0){
        popupbox.draggable({ handle: "#popupboxHeader" });
      }
      else {
        popupbox.wrapInner('<div id="popupboxContent" class="content" />').prepend('<div id="popupboxHeader" class="header"><a onclick="hideLightbox(); return false;" href="">close</a></div>');
      }
    });
  }

  // Reimplement ajaxLogin().
  ajaxLoginAnythink = function(callback) {
    ajaxCallback = callback;
    ajaxLightboxAnythink(path + '/MyResearch/AJAX?method=LoginForm');
  }

  loadOtherEditionSummariesAnythink = function(id, isEcontent) {
    var url = path + "/Search/AJAX?method=getOtherEditions&id=" + id + "&isEContent=" + isEcontent;
    ajaxLightboxAnythink(url);
  }

  showMaterialsRequestDetailsAnythink = function(id) {
    ajaxLightboxAnythink(path + "/MaterialsRequest/AJAX?method=MaterialsRequestDetails&id=" +id );
  }

  updateMaterialsRequestAnythink = function(id) {
    ajaxLightboxAnythink(path + "/MaterialsRequest/AJAX?method=UpdateMaterialsRequest&id=" +id );
  }

  GetAddTagFormAnythink = function(id, source){
    if (loggedIn){
      var url = path + "/Resource/AJAX?method=GetAddTagForm&id=" + id + "&source=" + source;
      ajaxLightboxAnythink(url);
    }else{
      ajaxLogin(function(){
        GetAddTagFormAnythink(id, source);
      });
    }
  }

  // // Reimplement doGetRatings().
  // doGetRatingsAnythink = function() {
  //   // Grab record IDs.
  //   get_records_anythink();
  //   if (anythink.settings.records.length > 0) {
  //     // Parse out rating IDs.
  //     var url = anythink.settings.path + "/Search/AJAX";
  //     var data = "method=GetRatings";
  //     for (var j=0; j < anythink.settings.records.length; j++) {
  //       var item = anythink.settings.records[j];
  //       if (item.type == 'VuFind') {
  //         data += "&id[]=" + encodeURIComponent(item.id);
  //       }
  //       else if (item.type == 'eContent') {
  //         data += "&econtentId[]=" + encodeURIComponent(item.id);
  //       }
  //     }
  //     data += "&time=" + anythink.settings.request_time;
  // 
  //     $.getJSON(url, data,
  //       function(data, textStatus) {
  // 
  //         parse_ratings(data['standard']);
  // 
  //         // @todo: move dom processing elsewhere
  //         var eContentRatings = data['eContent'];
  //         for (var id in eContentRatings){
  //           // Load the rating for the title
  //           if (eContentRatings[id].user != null && eContentRatings[id].user > 0){
  //             $('.rateEContent' + id).each(function(index){
  //               $(this).rater({'rating':eContentRatings[id].user, 'doBindings':false, module:'EcontentRecord', recordId: id });
  //             });
  //           }else{
  //             $('.rateEContent' + id).each(function(index){$(this).rater({'rating':eContentRatings[id].average, 'doBindings':false, module:'EcontentRecord', recordId: id});});
  //           }
  //           $('.rateEContent' + id + ' .ui-rater-rating-' + id).each(function(index){$(this).text( eContentRatings[id].average );});
  //           $('.rateEContent' + id + ' .ui-rater-rateCount-' + id).each(function(index){$(this).text( eContentRatings[id].count );});
  //         }
  //       }
  //     );
  //   }
  // }

  // parse_ratings = function(ratings) {
  //   for (var id in ratings) {
  //     var rating = parseInt(ratings[id].average);
  //     var item = $('#rating-' + id)
  //     .addClass('rating-' + rating)
  //     .attr('title', 'Average rating: ' + rating + '. Rated ' + ratings[id].count + ' time' + (ratings[id].count != 1 ? 's':'') + '.');
  //     if (!ratings[id].user) {
  //       var link = $('<a class="rate-this" href="#">Rate this...</a>');
  //       link.bind('click', {id: id}, function(event) {
  //         var id = event.data.id;
  //         var container = $('#rate-' + id);
  //         container
  //           .empty()
  //           .append('<span class="ui-rater"><span class="ui-rater-starsOff"><span class="ui-rater-starsOn"></span></span></span>')
  //           .rater({
  //             recordId: id,
  //             postHref: '/Record/' + id + '/AJAX?method=RateTitle',
  //           });
  //         return false;
  //       });
  //       $('#rate-' + id).append(link);
  //     }
  //     else {
  //       $('#rate-' + id).append('<span class="small fine-print">Your rating:</span> <span class="rating rating-' + ratings[id].user + '"></span>');
  //     }
  //   }
  // }

  // // Reimplementation of doGetStatusSummaries()
  // doGetStatusSummariesAnythink = function() {
  //   // Get records if we have them.
  //   get_records_anythink();
  // 
  //   if (anythink.settings.records.length > 0) {
  //     var url = path + "/Search/AJAX?method=GetStatusSummaries";
  //     var eContentUrl = path + "/Search/AJAX?method=GetEContentStatusSummaries";
  // 
  //     for (var j=0; j < anythink.settings.records.length; j++) {
  //       var item = anythink.settings.records[j];
  //       if (item.type == 'VuFind') {
  //         url += "&id[]=" + encodeURIComponent(item.id);
  //       }
  //       else if (item.type == 'eContent') {
  //         eContentUrl += "&id[]=" + encodeURIComponent(item.id);
  //       }
  //     }
  // 
  //     url += "&time=" + anythink.settings.request_time;
  //     eContentUrl += "&time=" + anythink.settings.request_time;
  // 
  //     // Get status summaries
  //     prepare_status_summaries();
  // 
  //     $.ajax({
  //       url: url, 
  //       success: function(data) {
  //         var summaries = new Array();
  //         $(data).find('item').each(function() {
  //           var $this = $(this);
  //           var item = {};
  //           item.id = $this.find('id').text();
  //           item.show_hold = $this.find('showplacehold').text() == '1';
  //           item.formatted_summary = $this.find('formattedHoldingsSummary').text();
  //           summaries.push(item);
  //         });
  //         // Parse into markup.
  //         parse_status_summaries(summaries);
  //       }
  //     });

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
    // };
   // }

   // prepare_status_summaries = function() {
   //   $('.holdings-summary').append('<em class="fine-print loading">Loading holdings summary...</em>');
   // }

   // parse_status_summaries = function(summaries) {
   //   $.each(summaries, function(key, item) {
   //     // Inject AHAH.
   //     $('#holdings-summary-' + item.id).fadeOut('fast', function(){
   //       $(this).html(item.formatted_summary).slideDown();
   //     });
   //   });
   // }

})(jQuery);