              /*!
* jquery.fixedHeaderTable. The jQuery fixedHeaderTable plugin
*
* Copyright (c) 2009 Mark Malek
* http://fixedheadertable.mmalek.com
*
* Licensed under MIT
* http://www.opensource.org/licenses/mit-license.php
* 
* http://docs.jquery.com/Plugins/Authoring
* jQuery authoring guidelines
*
* Launch  : October 2009
* Version : 1.0 beta
* Released: TBA
*
* 
* all CSS sizing (width,height) is done in pixels (px)
*/
(function($)
{

	$.fn.fixedHeaderTable = function(options) {
		var defaults = {
			loader: false,
			footer: false,
			colBorder: true,
			cloneHeaderToFooter: false,
			autoResize: false,
			fixCol1: false
		};
		
		var options = $.extend(defaults, options); // get the defaults or any user set options
		
		return this.each(function() {
			var obj = $(this); // the jQuery object the user calls fixedHeaderTable on
			
			buildTable(obj,options);
			
			/*
			 * When the browser is resized set a javascript timeout (setTimeout()) on the build table function.
			 * If the browser is continuously being resized, reset the timeout. If the browser hasn't been resized
			 * for 200ms then exectue buildTable.
			*/
			if(options.autoResize == true) {
				// if true resize the table when the browser resizes
				$(window).resize( function() {
					if (table.resizeTable) {
						// if a timeOut is active cancel it because the browser is still being resized
						clearTimeout(table.resizeTable);
					}
				
					// setTimeout is used for resizing because some browsers will call resize() while the browser is still being resized which causes performance issues.
					// if the browser hasn't been resized for 200ms then resize the table
					table.resizeTable = setTimeout(function() {
					
						buildTable(obj,options);
						
					}, 200);
				});
			}
		});
	};
	
	var table = function() {
		this.resizeTable; // stores the value of the resize javascript timeout (setTimeout)
	}
	
	function buildTable(obj,options) {
		var objClass = obj.attr('class');
			
		var hasTable = obj.find("table").size() > 0; // returns true if there is a table
		var hasTHead = obj.find("thead").size() > 0; // returns true if there is a thead
		var hasTBody = obj.find("tbody").size() > 0; // returns true if there is a tbody
			
		if(hasTable && hasTHead && hasTBody) {
			var parentDivWidth = obj.width() - 2; // get the width of the parent DIV (subtract 2 for the outside border)
			var parentDivHeight = obj.height() - 4; // get the height of the parent DIV (subtract 4 for the outside border)
			var tableBodyWidth = parentDivWidth; // width of the div surrounding the tbody (overflow:auto)
			
			obj.css('position', 'relative'); // set the jQuery object the user passsed in to position:relative (just incase they did not set it in their stylesheet)
			
			if (obj.find('.fht_parent').size() == 0) {
				// if returns false then the plugin has not been used on this jQuery object
				obj.find('table').wrap('<div class="fht_parent"><div class="fht_table_body"></div></div>');
			}
			obj.find('.fht_parent').css('width', parentDivWidth+'px'); // set the width of the parent div
			obj.find('.fht_table_body').css('width', parentDivWidth+'px'); // set the width of the main table body (where the data will be displayed)
			
			var tableWidthNoScroll = parentDivWidth; // this is the width of the table with no scrollbar (used for the fixed header)
			
			obj.find('.fht_parent .fht_table_body table').addClass('fht_orig_table'); // add a class to identify the orignal table later on
			
			if(options.loader) {
				// if true display a loading image while the table renders (default is false)
				obj.find('.fht_parent').prepend('<div class="fht_loader"></div>');
				obj.find('.fht_loader').css({'width':parentDivWidth+'px', 'height': parentDivHeight+'px'});
			}
			
			var tableWidthScroll = parentDivWidth; // the width of the table minus the scroll bar
			
			if(options.fixCol1) {
				if (obj.find('.fht_parent .fht_fixed_col_fulltable').size() > 0 == false) {
					obj.find('.fht_parent').prepend('<div class="fht_fixed_col_fulltable"></div>');
					obj.find('.fht_parent').prepend('<div class="fht_fixed_col"></div>');
					
					obj.find('.fht_parent .fht_table_body').prependTo('.fht_parent .fht_fixed_col_fulltable');
				}
			}
			
			obj.attr('id', obj.attr('class')); // add a unique ID to the table
			
			if (options.fixCol1) {
				tableWidthScroll = tableWidthScroll - obj.find('.fht_parent .fht_orig_table tbody tr:first td:first-child').width();
			}
			else {
				if ($.browser.msie == true) {
					// if IE subtract 20px to compensate for the scrollbar
					tableWidthScroll = tableWidthScroll - 20; // default width of scrollbar for IE
				}
				else if (jQuery.browser.safari == true) {
					// if Safari subtract 16px to compensate for the scrollbar
					tableWidthScroll = tableWidthScroll - 16; // default width of scrollbar for Safari
				}
				else {
					// if everything else subtract 19px to compensate for the scrollbar (FireFox, Chrome, Opera etc)
					tableWidthScroll = tableWidthScroll - 19; // default width of scrollbar for everyone else
				}
			}

			obj.find('table.fht_orig_table').css({'width': tableWidthScroll+'px'}); // set the width of the table minus the scrollbar
			obj.find('table tbody tr:even td').addClass('even'); // add class 'even' for every row with an even index (for alternating row colors)
			obj.find('table tbody tr:odd td').addClass('odd'); // add class 'odd' for every row with an odd index (for alternating row colors)

			if (obj.find('table tbody tr td div.tableData').size() > 0 == false) {
				// if div.tableData exists then this was triggered by browser reload
				// div.tableData only needs to be wrapped around each table cells data once
				obj.find('table tbody tr td').wrapInner('<div class="tableData"><p class="tableData"></p></div>');
			}
			else {
				// div.tableData already exists. Clear out the old widths by setting width to auto
				obj.find('table tbody tr td div.tableData').css('width','auto');
			}
			
			obj.find('table.fht_orig_table thead tr').css('display', ''); // if the thead is hidden then unhide it. display type '' is used isntead of table-row-group to allow the browser to determine which display type it needs. IE does not support table-row-group
			
			if (obj.find('table thead tr th div.tableHeader').size() > 0 == false) {
				// if div.tableHeader does not exist then wrap all header cells with div.tableHeader and p.tableHeader (this is triggere by a browser reload
				obj.find('table thead th').wrapInner('<div class="tableHeader"><p class="tableHeader"></p></div>');
			}
			else {
				// div.tableHeader already exists. Clear out the old widths by setting width to auto
				obj.find('div.tableHeader').css('width', 'auto');
			}
			
			if (options.colBorder) {
				// if true add a border to the right of each cell except the last cell in each row (:last-child)
				obj.find('.fht_parent table tr td:not(:last-child)').addClass('borderRight');
				obj.find('.fht_parent table tr th:not(:last-child)').addClass('borderRight');
			}
			
			obj.find('.fht_fixed_header_table_parent').remove(); // remove div.fht_fixed_header_table_parent if it exists and children
			
			var html = "";
			html += "<div class='fht_fixed_header_table_parent'>"; // wraps around the entire fixed header
			html += "<!--[if IE]><div class='fht_top_right_header'></div><![endif]-->"; // adds a rounded corner to the top right of the header for IE only
			html += "<!--[if IE]><div class='fht_top_left_header'></div><![endif]-->"; // adds a rounded corner to the top left of the header for IE only
			html += "<div class='fht_fixed_header_table_border'>"; // creates the border for the header and the header table will be the child
			html += "<table class='fht_fixed_header_table'>"; // holds the thead that is cloned from the original table body
			html += "</table></div></div>"; // close all open div's and table tags
			
			if (options.fixCol1) {
				obj.find('.fht_fixed_col_fulltable').prepend(html);
			}
			else {
				obj.find('.fht_parent').prepend(html); // add the html output to the beginning of the parent div
			}
			
			obj.find('.fht_fixed_header_table_border').css('width', tableWidthScroll + 'px'); // set the width of the fixed header table's parent div (minus the scrollbar)
			// Although the scrollbar does not extend to the header, the header table must be equal to the body table
			
			
			obj.find('.fht_fixed_header_table_parent').css('width', parentDivWidth+'px'); // set the width of the parent div for the fixed header including the scrollbar 
			obj.find('table.fht_fixed_header_table').empty(); // empty all child nodes from the fixed header table
			
			obj.find('.fht_parent .fht_orig_table thead').clone().prependTo('.' + objClass + ' .fht_fixed_header_table'); // clone all child nodes from the body table header and add them to the fixed header table
			
			obj.find('table.fht_fixed_header_table').css({'width': tableWidthScroll+'px'}); // set the width of the fixed table header
			
			// block comment what you are doing
			var i = 0;
			var widthHidden = new Array();
			obj.find('.fht_parent table.fht_orig_table th').each(function() {
				if($(this).hasClass('th'+i) == false) {
					$(this).addClass('th'+i); // used to identify which column we are looking at
				}
				
				widthHidden[i] = $(this).width();
				i++;
			});
			
			var i = 0;
			var width = new Array();
			obj.find('.fht_parent table.fht_fixed_header_table th').each(function() {
				if($(this).hasClass('th'+i) == false) {
					$(this).addClass('th'+i); // add a class name with the column count to each header cell
				}
				//width[i] = widthHidden[i];
				i++;
			});
			
			if(obj.find('table.fht_orig_table tbody tr td:first-child').hasClass('firstCell') == false) {
				// if the first cell in each row does not already have 'firstCell' class, add it.
				obj.find('table.fht_orig_table tbody tr td:first-child').addClass('firstCell');
			}
			
			var thCount = 0;
			var thWidth;
			var tdWidth;
			obj.find('table.fht_orig_table tbody tr td').each(function() {
				
				if ($(this).hasClass('firstCell')) {
					// if the current element has class firstCell then we are at the begginning of a new row
					thCount = 0; // reset the counter
				}
				
				thWidth = widthHidden[thCount];
				//tdWidth = $(this).width(); // not being used yet
		
				$(this).children('div.tableData').css('width',thWidth+'px'); // set the width of each div.tableData.
				// Set the width on div.tableData vs. the td so that the inner div forces the td to be the desired width.  widths on td isn't a sure thing and sometimes is ignored
				
				obj.find('.fht_parent table.fht_fixed_header_table th.th'+thCount+' div.tableHeader').css('width', thWidth+'px'); // set the width of each header cell's inner div.
				
				thCount++;
			});
			
			var footerHeight = 0; // default height of the footer. By default footers are off. Used later to determine the allowed height for scrolling the table
			
			if (options.footer && !options.cloneHeaderToFooter) {
				// if footer is true and its not a cloned footer
				if (!options.footerId) {
						// notify the developer they wanted a footer and didn't provide content
						$('body').css('background', '#f00');
						alert('Footer ID required');
				}
				else {
					var footerId = options.footerId;
					if (obj.find('.fht_fixed_footer_border').size() == 1) {
						// store the user created content
						var footerContent = obj.find('.fht_fixed_footer_border').html();
					}
					else {
						$('#'+footerId).appendTo('.fht_parent'); // move the user created footer inside the table parent div.
						
						var footerContent = obj.find('#'+footerId).html(); // store the user created content
					}
						obj.find('#'+footerId).empty(); // remove all child nodes and content
						obj.find('#'+footerId).prepend('<div class="fht_cloned_footer"><!--[if IE 6]><div class="fht_bottom_left_header"></div><div class="fht_bottom_right_header"></div><![endif]--><div class="fht_fixed_footer_border"></div></div>'); // create a parent footer div that wraps around the user created footer and add rounded corners
						obj.find('.fht_fixed_footer_border').html(footerContent); // add the previously stored user content
						obj.find('.fht_cloned_footer').css('width', obj.find('.fht_fixed_header_table_parent').width()+'px'); // set the width of the footer = to the fixed header width
						obj.find('#'+footerId).css({'height': obj.find('#'+footerId).height() + 'px', 'width': obj.find('.fht_fixed_header_table_parent').width()+'px'}); // set the height of the footer equal to the height of the user content
						footerHeight = obj.find('#'+footerId).height(); // store the footer height.  Used later to determine the allowed height for scrolling the table
				}
			}
			else if (options.footer && options.cloneHeaderToFooter) {
				// if footer is true and cloneHeaderToFooter is true. Clone the fixed header as a fixed footer
				obj.find('.fht_parent .fht_cloned_footer').remove(); // remove any previously genereated cloned footer
				
				var html = "";
				html += "<div class='fht_cloned_footer'>"; // wraps around the entire fixed header
				html += "<!--[if IE]><div class='fht_bottom_right_header'></div><![endif]-->"; // adds a rounded corner to the top right of the header for IE
				html += "<!--[if IE]><div class='fht_bottom_left_header'></div><![endif]-->"; // adds a rounded corner to the top left of the header for IE
				html += "<div class='fht_fixed_footer_border'>"; // creates the border for the header
				html += "</div></div>"; // close all open div's and table tags
	
				obj.find('.fht_parent').append(html); // add the generated footer HTML to the bottom of the parent div

				obj.find('.fht_parent .fht_fixed_header_table_parent .fht_fixed_header_table_border table').clone().prependTo('.' + objClass + ' .fht_cloned_footer .fht_fixed_footer_border'); // clone the fixed header into the footer
				obj.find('.fht_cloned_footer').css({'width': obj.find('.fht_parent .fht_fixed_header_table_parent').width()+'px', 'height': (obj.find('.fht_parent .fht_fixed_header_table_parent').height()-1)+'px'}); // set the width of the cloned footer
	
				footerHeight = obj.find('.fht_cloned_footer').height(); // store the height of the cloned footer. Used later to determine the allowed height for scrolling the table
			}
			
			var headerHeight = obj.find('.fht_parent .fht_fixed_header_table_parent').height(); // store the height of the fixed header. Used later to determine the allowed height for scrolling the table
			var scrollDivHeight = parentDivHeight - footerHeight - headerHeight; // determine the available space for displaying the data and scrolling the table
	
			obj.find('.fht_table_body').css({'width': tableBodyWidth+'px','height': scrollDivHeight+'px'}); // set the height of the main table body (where the data will be displayed) this also determines how much of the data is visible before a scroll bar is needed
			
			obj.find('table.fht_orig_table thead tr').css('display', 'none'); // hide the table body's header (orig. table header)
			
			if (options.fixCol1) {
				if (obj.find('.fht_fixed_col_fixed_header').size() > 0 == false) {
					obj.find('.fht_parent .fht_fixed_col').prepend('<div class="fht_fixed_col_fixed_header"><table><thead><tr></tr></thead></table></div>');
					obj.find('.fht_parent .fht_fixed_header_table thead tr th:first').prependTo('.fht_parent .fht_fixed_col_fixed_header table thead tr');
				}
				
				obj.find('.fht_parent .fht_fixed_col_fixed_header table thead tr th').css({'height':obj.find('.fht_parent .fht_fixed_header_table thead tr th:first').height()+'px'});
				
				if (obj.find('.fht_fixed_col_body').size() > 0 == false ) {
					obj.find('.fht_parent .fht_fixed_col').append('<div class="fht_fixed_col_body"><table><tbody></tbody></table></div>');
				
				
					var rowCount = 1;
					obj.find('.fht_parent .fht_fixed_col_fulltable .fht_table_body table tbody tr td:first-child').each(function() {
						obj.find('.fht_parent .fht_fixed_col_body table tbody').append('<tr class="row'+rowCount+'"></tr>');
						$(this).appendTo('.fht_parent .fht_fixed_col_body table tbody tr.row'+rowCount);
						rowCount++;
					});
				}
				var firstRowTableData = obj.find('.fht_parent .fht_fixed_col_body tr.row1 td div.tableData').width();
				var rowHeight = obj.find('.fht_parent .fht_table_body table tbody tr td').height();
				obj.find('.fht_parent .fht_fixed_col_body tr td div.tableData').css({'width':firstRowTableData+'px'});
				obj.find('.fht_parent .fht_fixed_col_body tr td').css({'height':rowHeight+'px'});
				
				var fixedColTableWidthScroll = tableWidthScroll - obj.find('.fht_parent .fht_fixed_col').width();
				obj.find('.fht_parent .fht_fixed_col_fulltable').css({'width': fixedColTableWidthScroll+'px'});
				obj.find('.fht_parent .fht_fixed_header_table_parent').css({'width': fixedColTableWidthScroll+'px'});
				obj.find('.fht_parent .fht_fixed_col_body').css({'width': fixedColTableWidthScroll+'px'});
				obj.find('.fht_parent .fht_fixed_col').css({'width': firstRowTableData+'px'});
				obj.find('.fht_parent .fht_fixed_col_body').css({'width': firstRowTableData+'px'});
				obj.find('.fht_parent .fht_fixed_col .fht_fixed_col_fixed_header').css({'width': firstRowTableData+'px'});
				obj.find('.fht_fixed_col').css({'height':scrollDivHeight+'px'});
				tableBodyWidth = tableBodyWidth - obj.find('.fht_fixed_col').width();
				
				obj.find('.fht_fixed_header_table_parent').css({'width': tableBodyWidth+'px'});
				obj.find('.fht_table_body').css({'width': tableBodyWidth+'px','height': scrollDivHeight+'px'});
			}
	
			if(options.loader) {
				// if true hide the loader
				obj.find('.fht_loader').css('display', 'none');
			}
			
			obj.find('.fht_table_body').scroll(function() {
				// if a horizontal scrollbar is present
				obj.find('.fht_fixed_header_table_border').css('margin-left',(-this.scrollLeft)+'px'); // scroll the fixed header equal to the table body's scroll offset
				if (options.footer && options.cloneHeaderToFooter) {
					// if a cloned footer is visible it needs to be scrolled too
					obj.find('.fht_fixed_footer_border').css('margin-left',(-this.scrollLeft)+'px'); // scroll the fixed footer equal to the table body's scroll offset
				}
				
				obj.find('.fht_fixed_col_body table').css('margin-top',(-this.scrollTop)+'px'); // scroll the fixed header equal to the table body's scroll offset
			});
		}
		else {
			// you did something wrong.
			$('body').css('background', '#f00');
			alert('Invalid HTML. A table, thead, and tbody are required');
			// For the future: build a dialog window that indicates an error in implementation with the specific error
		}
	}	
})(jQuery);