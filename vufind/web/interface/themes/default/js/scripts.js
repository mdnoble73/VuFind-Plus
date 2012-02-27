function lightbox()
{
    var lightbox = document.getElementById('lightbox');
    var popupbox = document.getElementById('popupbox');
    var loadMsg = document.getElementById('lightboxLoading').innerHTML;

    popupbox.innerHTML = '<img src="' + path + '/images/loading.gif" /><br />' + loadMsg;

    hideSelects('hidden');

    // Find out how far down the screen the user has scrolled.
    var new_top = YAHOO.util.Dom.getDocumentScrollTop();

    //Get the height of the document
    var documentHeight = YAHOO.util.Dom.getDocumentHeight();

    lightbox.style.display='block';
    lightbox.style.height= documentHeight + 'px';

    popupbox.style.display='block';
    popupbox.style.top = new_top + 200 + 'px';
    popupbox.style.left='25%';
    popupbox.style.width='50%';
}

function hideLightbox()
{
    var lightbox = document.getElementById('lightbox');
    var popupbox = document.getElementById('popupbox');

    hideSelects('visible');
    lightbox.style.display='none';
    popupbox.style.display='none';
}

function hideSelects(visibility)
{
    selects = document.getElementsByTagName('select');
    for(i = 0; i < selects.length; i++) {
        selects[i].style.visibility = visibility;
    }
}

function toggleMenu(elemId)
{
    var o = document.getElementById(elemId);
    o.style.display = o.style.display == 'block' ? 'none' : 'block';
}

function getElem(id)
{
    if (document.getElementById) {
        return document.getElementById(id);
    } else if (document.all) {
        return document.all[id];
    }
}

function filterAll(element)
{
    // Go through all elements
    var e = getElem('searchForm').elements;
    var len = e.length;
    for (var i = 0; i < len; i++) {
        //  Look for filters (specifically checkbox filters)
        if (e[i].name == 'filter[]' && e[i].checked != undefined) {
            e[i].checked = element.checked;
        }
    }
}

function jsEntityEncode(str)
{
    var new_str = str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    return new_str;
}

// Taken from http://stackoverflow.com/questions/1933602/how-to-getelementbyclass-instead-of-getelementbyid-with-javscript and http://www.dustindiaz.com/top-ten-javascript/
function getElementsByClassName(node,classname) {
  if (node.getElementsByClassName) { // use native implementation if available
    return node.getElementsByClassName(classname);
  } else {
    return (function getElementsByClass(searchClass,node) {
        if ( node == null ) {
          node = document;
        }
        var classElements = [],
            els = node.getElementsByTagName("*"),
            elsLen = els.length,
            pattern = new RegExp("(^|\\s)"+searchClass+"(\\s|$)"), i, j;

        for (i = 0, j = 0; i < elsLen; i++) {
          if ( pattern.test(els[i].className) ) {
              classElements[j] = els[i];
              j++;
          }
        }
        return classElements;
    })(classname, node);
  }
}

// Process Google Book Search json response & update the DOM.
function ProcessGBSBookInfo(booksInfo) {
    ProcessBookInfo(booksInfo, 'gbs');
}

// Process Open Library json response & update the DOM.
function ProcessOLBookInfo(booksInfo) {
    ProcessBookInfo(booksInfo, 'ol');
}

// Function shared between GBS and Open Library
function ProcessBookInfo(booksInfo, provider) {
    var expandedProvider = "";
    if (provider == "gbs") {
        expandedProvider = "Google Book Search";
    } else {
        expandedProvider = "the Open Library";
    }
    for (isbn in booksInfo) {
        var bookInfo = booksInfo[isbn];
        if (bookInfo) {
            if (bookInfo.preview == "full" || bookInfo.preview == "partial") {
                var classNameConcat = provider + isbn;
                var elements = getElementsByClassName(document, classNameConcat), n = elements.length;
                for (var i = 0; i < n; i++) {
                    var e = elements[i];
                    if(e.style.display == 'none') {
                        // set the link
                        e.href = bookInfo.preview_url;

                        // Set a tool-tip indicating the preview level
                        if (bookInfo.preview == "full") {
                            e.setAttribute('title', 'View online: Full view Book Preview from ' + expandedProvider);
                        } else {
                            e.setAttribute('title', 'View online: Limited preview from ' + expandedProvider );
                        }

                        //show the element
                        e.style.display = '';
                    }
                }
            }
        }
    }
}

// Function to process Hathi Trust json response & update the DOM.
function ProcessHTBookInfo(booksInfo) {
    for (a in booksInfo) {
        var bookInfo = booksInfo[a];
        var itemsArray = bookInfo.items;
        var e = document.getElementById(a);
        if (e != null && e != undefined) {
            for (var i = 0; i < itemsArray.length; i++) {
                if (e.style.display == 'none') {
                    if (bookInfo.items[i].rightsCode == "pd" || bookInfo.items[i].rightsCode == "world") {
                        e.href = bookInfo.items[i].itemURL;
                        e.style.display = '';
                    }
                }
            }
        }
    }
}

// Function to check all a form's checkboxes
// Supply form ID and input class
function checkAll(form, field)
{
    var getForm = getElem(form).getElementsByTagName('input');
    for (i = 0; i < getForm.length; i++) {
        if(getForm[i].attributes['class'] && getForm[i].attributes['class'].nodeValue == field) {
            getForm[i].checked = true ;
        }
    }
}

// Function to uncheck all a form's checkboxes
// Supply form ID and input class
function uncheckAll(form, field)
{
    var getForm = getElem(form).getElementsByTagName('input');
    for (i = 0; i < getForm.length; i++) {
        if(getForm[i].attributes['class'] && getForm[i].attributes['class'].nodeValue == field) {
            getForm[i].checked = false;
        }
    }
}

// Toggle check on all a form's checkboxes
// Supply form ID and input class
function toggleCheck(selector, form, field)
{
    var toggle = (selector.checked == true)?true:false;
    var getForm = getElem(form).getElementsByTagName('input');
    for (i = 0; i < getForm.length; i++) {
        if(getForm[i].attributes['class'] && getForm[i].attributes['class'].nodeValue == field) {
            getForm[i].checked = toggle;
        }
    }
}

// Gets all the checked checkboxes from a form, creates a search url and passes to lightbox
// Supply form ID, input class and mode (makeString = ids are concatenated with '+', ,makeArray = ids are passed as an array)
function processIds(form, field, mode, module, action, id, lookfor, message, followupModule, followupAction, followupId) {
    var setMode = (mode != '' && mode != 'undefined')?mode:'makeString';
    var getForm = getElem(form).getElementsByTagName('input');
    var postParams = [];
    if(getForm) {
        var ids = [];
        var x = 0;
        for (i = 0; i < getForm.length; i++) {
            if(getForm[i].attributes['class'] && getForm[i].attributes['class'].nodeValue == field && getForm[i].checked == true) {
            ids[x] = getForm[i].attributes['value'].nodeValue;
            x++;
            }
        }
        if(ids.length > 0) {
            var idValue = '';
            if(setMode == 'makeString') {
                postParams = ids.join('+');
            }
            else {
               for(i=0; i<ids.length; i++) {
                    postParams += "ids[]=" + ids[i] + "&";
                }
            }
        }
        else {
            postParams = "";
            message = 'no_items_selected';
        }
    }
    getLightbox(module, action, id, lookfor, message, followupModule, followupAction, followupId, postParams);
}
