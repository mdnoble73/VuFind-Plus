<?xml version="1.0" encoding="windows-1250"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:fo="http://www.w3.org/1999/XSL/Format"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/"
    xmlns:METS="http://www.loc.gov/METS/"
    xmlns:xlink="http://www.w3.org/1999/xlink"
    xmlns:php="http://php.net/xsl"
    xmlns="http://www.w3.org/1999/xhtml"
    xmlns:xi="http://www.w3.org/2001/XInclude"
    >
    <xsl:param name="path">/</xsl:param>
    <xsl:output method="html"/>
    <xsl:template match="/">
    <html>
    <head>
        <title>VuDL: <xsl:value-of select="//dc:title[1]"/></title>
        <link href="{concat($path, '/css/vudl/jquery-ui.css')}" rel="stylesheet" type="text/css"/>
        <link href="{concat($path, '/css/vudl/jquery.jscrollpane.css')}" rel="stylesheet" type="text/css"  media="all"/>
        <link href="{concat($path, '/css/vudl/vudl.ui.css')}" rel="stylesheet" type="text/css"  media="all"/>

        <script type="text/javascript" src="{concat($path, '/js/vudl/jquery-1.4.4.min.js')}"></script>
        <script type="text/javascript" src="{concat($path, '/js/vudl/jquery-ui-1.8.10.custom.min.js')}"></script>
        <script type="text/javascript" src="{concat($path, '/js/vudl/jquery.layout-latest.js')}"></script>
        <script type="text/javascript" src="{concat($path, '/js/vudl/jquery.mousewheel.js')}"></script>
        <script type="text/javascript" src="{concat($path, '/js/vudl/jquery.jscrollpane.min.js')}"></script>
        <script type="text/javascript" src="{concat($path, '/js/vudl/jquery.swfobject.min.js')}"></script>
        <script type="text/javascript" src="{concat($path, '/js/vudl/jquery.scrollTo-min.js')}"></script>

        <!--
        <script src="{concat($path, '/js/vudl/swfobject.js')}"></script>
        <script src="{concat($path, '/js/vudl/jquery.min.js')}"></script>
        <script src="{concat($path, '/js/vudl/jquery-ui.min.js')}"></script>
        <script src="{concat($path, '/js/vudl/jquery.localscroll.js')}"></script>
        -->

        <script type="text/javascript">
            var fileGrpUSEs = [
                <xsl:for-each select="//METS:fileGrp">
                    <xsl:if test="count(./METS:file)">
                        '<xsl:value-of select="./@USE"/>',
                    </xsl:if>
                </xsl:for-each>
                <xsl:if test="count(//METS:fileGrp[@USE='LARGE']/METS:file)">
                              'ZOOM',
                </xsl:if>
                              'MANIFEST'
                              ];

        var myLayout;

        function toggleFullscreen() {
           myLayout.toggle('north');
           myLayout.toggle('west');
        }
        function nextItem(moveDirection) {
           var currentItem = $('#item_level_tabs .activePanel .image_thumb ul li.active');

           if (moveDirection == 'next') {
               var goTo = currentItem.next().length ? currentItem.next() : $("#item_level_tabs .activePanel .image_thumb ul li:first");
           } else if (moveDirection == 'prev') {
               var goTo = currentItem.prev().length ? currentItem.prev() : $("#item_level_tabs .activePanel .image_thumb ul li:last");
           } else if (moveDirection == 'first') {
               var goTo = $("#item_level_tabs .activePanel .image_thumb ul li:first");
           } else if (moveDirection == 'last') {
               var goTo = $("#item_level_tabs .activePanel .image_thumb ul li:last");
           }

           $(goTo).trigger('click');
        }

        function displayUSE(fileGrpUSE, url) {

            if (fileGrpUSE == 'ZOOM') {
                setTimeout(function() {
                    $('#file_src_flash').flash(
                          {
                              swf: <xsl:value-of select="concat('&quot;',$path, '/swf/vudl/PhotoNavigator.swf','&quot;')"/>,
                              width: '100%',
                              height: '100%',
                              play: true,
                              wmode: 'transparent',
                              scale: 'noscale',
                              quality: 'high',
                              menu: false,

                              flashvars: {
                                        fileName: url
                                    }

                          }
                      );
                  }, 200);
            } else {
                var htmlStr = '';
                if (fileGrpUSE == 'LARGE' || fileGrpUSE == 'MEDIUM' || fileGrpUSE == 'THUMBNAIL') {
                    htmlStr += '&lt;a href="' + url + '" title="' + fileGrpUSE + '">';
                    htmlStr += '&lt;img src="' + url + '" alt="' + url + '">';
                    htmlStr += '&lt;\/a>';
                } else if (fileGrpUSE == 'ORIGINAL') {
                    htmlStr += 'Please &lt;a href="mailto:digitallibrary@villanova.edu?subject=Hi-Res Request for ' + url + '"&gt;email us&lt;\/a&gt; for access to the Hi-Res image.';

                } else if (fileGrpUSE == 'TRANSCRIPTION') {
                    htmlStr += '&lt;h3&gt;Transcription File&lt;\/h3&gt;';
                    htmlStr += '&lt;a title="Transcription File" href="' + url + '"&gt;';
                    htmlStr += url;
                    htmlStr += '&lt;\/a&gt;';
                } else {
                    htmlStr += fileGrpUSE + ' ' + url;
                }
                $('#' + fileGrpUSE + '_view .file_src span').html(htmlStr);
            }

        }

        $(document).ready(function() {

          // Layout UI

            $(".header-footer").hover(
                function(){ $(this).addClass('ui-state-hover'); }
            ,   function(){ $(this).removeClass('ui-state-hover'); }
            );

            myLayout = $('#layout_container').layout({
              north__resizable:  false,
              west__resizable:  false,
              togglerLength_open: 0,
              togglerLength_closed: 0
          });
          myLayout.sizePane("west", 210);

          // TABS

          $("#item_level_tabs").tabs();
          $("#view_tabs").tabs();

          // set default view on page load
          var defaultSelection = $('#MANIFEST_tab').index();

          // Item level tab actions
          $("#item_level_tabs").bind("tabsshow", function(event, ui) {
              // remove all activePanels
              $("#item_level_tabs div").removeClass('activePanel');
              // set view_tabs to default selection
              $("#view_tabs").tabs("select", defaultSelection);
              // set current tab as activePanel
              $(ui.panel).addClass('activePanel');
              // Auto click first item in new activePanl list
              $("#item_level_tabs .activePanel .image_thumb  ul li:first").trigger('click');
          });

          // Item view tab actions
          // Auto Re-click item in list
          // Fixes IE / UI Tabs bug for loading flash in hidden div
          $("#view_tabs").bind("tabsshow", function(event, ui) {
              $("#item_level_tabs .activePanel .image_thumb  ul li.active").trigger('click');
          });

          // Onload action sets default view tab
          $("#view_tabs").tabs("select", defaultSelection);

          /* TODO Hash tag in URL stuff */
          /*
            if($("#view_tabs") &amp;&amp; document.location.hash){
                // alert('hash set');
              $.scrollTo(".tab-set");
          }

          $("#view_tabs ul").localScroll({
              target: "#view_tabs",
              duration: 0,
                hash: true
            });
          */

          // Image Loader

            $(".image_thumb ul li").click(function(){

                //if ($(this).is(".active")) {  //If it's already active, then...
                //    return false; // Don't click through
                //} else {
            for (var i=0; i&lt;fileGrpUSEs.length; i++) {

                var srcUrl = $(this).find('.' + fileGrpUSEs[i] + '_src span').html();
                var srcData = $(this).find('.' + fileGrpUSEs[i] + '_src div').html();

                if (srcData != null) {
                    $('#' + fileGrpUSEs[i] + '_view .file_src span').html(srcData);
                } else {
                    displayUSE(fileGrpUSEs[i], srcUrl)
                }

                // enable tab
                $("#view_tabs").tabs("enable", $('#' + fileGrpUSEs[i] + '_tab').index());

                // Disable TAB if the srcUrl doesn't exist
                if (srcUrl.length == 0 &amp;&amp; (srcData == null || srcData.length == 0)) {
                    $("#view_tabs").tabs("disable", $('#' + fileGrpUSEs[i] + '_tab').index());
                    }
            }
                //}

                $(".image_thumb ul li").removeClass('active'); //Remove class of 'active' on all lists
                $(this).addClass('active');  //add class of 'active' on this list only

                $("#file_counter span.totalItems").html($("#item_level_tabs .activePanel .image_thumb  ul li").size());
                $("#file_counter span.currentItem").html($("#item_level_tabs .activePanel .image_thumb  ul li.active").index() + 1);

                return false;

          }) .hover(function(){
                $(this).addClass('hover');
                }, function() {
                $(this).removeClass('hover');
            });

        });//Close Function

        </script>



    </head>
    <body>
      <div id="layout_container">

        <div class="ui-layout-north" style="width:100%; background-color:#000000;">
            <img src="{concat($path, '/images/vudl/vudl-website-logo.png')}"/>
        </div>

        <div class="ui-layout-west" >
            <div id="item_level_tabs" class="scroll-pane">
                <ul>
                    <li>
                        <a href="#page_level_tab" title="Page Level Items">
                            <span>Pages</span>
                        </a>
                    </li>
                    <li>
                        <a href="#document_level_tab" title="Document Level Items">
                            <span>Docs</span>
                        </a>
                    </li>
                    <li>
                        <a href="#details_tab" title="Details / Metadata">
                            <span>Details</span>
                        </a>
                    </li>
                </ul>

                <div id="page_level_tab" class="activePanel">
                    <xsl:call-template name="item_level_template">
                        <xsl:with-param name="item_level" select="string('page_level')"/>
                    </xsl:call-template>
                </div>

                <div id="document_level_tab">
                    <xsl:call-template name="item_level_template">
                        <xsl:with-param name="item_level" select="string('document_level')"/>
                    </xsl:call-template>
                </div>

                <div id="details_tab">
                    <xsl:for-each select="//dc:title">
                        <xsl:value-of select="."/>
                        <br/>
                    </xsl:for-each>
                </div>

            </div>
        </div>

        <div class="ui-layout-center">
            <div id="view_controls">

                <div id="VCR_controls">
                    <a href="javascript:nextItem('first');" title="First Item">
                        <img src="{concat($path, '/images/vudl/first.png')}"/>
                    </a>
                    <a href="javascript:nextItem('prev');" title="Previous Item">
                        <img src="{concat($path, '/images/vudl/prev.png')}"/>
                    </a>
                    <a href="javascript:nextItem('next');" title="Next Item">
                        <img src="{concat($path, '/images/vudl/next.png')}"/>
                    </a>
                    <a href="javascript:nextItem('last');" title="Last Item">
                        <img src="{concat($path, '/images/vudl/last.png')}"/>
                    </a>
                </div>

                <div id="file_counter">
                    File <span class="currentItem">X</span> of <span class="totalItems">X</span>
                </div>

                <div id="fullscreen_toggle">
                    <a href="javascript:toggleFullscreen();" title="Toggle Fullscreen">
                        <img src="{concat($path, '/images/vudl/fullscreen.png')}"/>
                    </a>
                </div>

            </div>

            <div id="view_tabs" class="tab-set">
                <ul>
                    <xsl:for-each select="//METS:fileGrp">
                        <xsl:if test="count(./METS:file)">
                            <li id="{concat(./@USE, '_tab')}">
                                <a href="{concat('#', ./@USE, '_view')}" title="{php:function('Record::capitalization', string(./@USE))}">
                                    <img src="{concat($path, '/images/vudl/icons/', ./@USE, '.png')}"/>
                                </a>
                            </li>
                        </xsl:if>
                    </xsl:for-each>

                    <li id="ZOOM_tab">
                        <a href="#ZOOM_view" title="Magnify View">
                            <img src="{concat($path, '/images/vudl/icons/ZOOM.png')}"/>
                        </a>
                    </li>

                    <li id="MANIFEST_tab">
                        <a href="#MANIFEST_view" title="File Manifest">
                            <img src="{concat($path, '/images/vudl/icons/MANIFEST.png')}"/>
                        </a>
                    </li>
                </ul>

                <xsl:for-each select="//METS:fileGrp">
                    <xsl:variable name="fileGrp_USE" select="./@USE"/>
                    <xsl:if test="count(./METS:file)">
                        <div id="{concat($fileGrp_USE, '_view')}">
                            <div class="file_src">
                                <span/>
                            </div>
                        </div>
                    </xsl:if>
                </xsl:for-each>

                <div id="ZOOM_view">
                    <div id="file_src_flash"/>
                </div>

                <div id="MANIFEST_view">
                    <div class="file_src">
                        <span/>
                    </div>
                </div>

            </div>

        </div>

      </div>



    <script type="text/javascript">
        $(document).ready(function() {
            $(".image_thumb ul li:first").trigger('click');

            /*
            var pane = $('.scroll-pane');
            pane.jScrollPane(
                        {
                            showArrows: true,
                            animateScroll: true
                        }
                    );
            */
        });
    </script>

    </body>
    </html>
    </xsl:template>

    <xsl:template name="item_level_template" match="/METS:mets">
        <xsl:param name="item_level"/>
        <div class="image_thumb">
            <ul>
            <xsl:for-each select="//METS:structMap/METS:div/METS:div[@TYPE=$item_level]/METS:div">
                <xsl:variable name="divID" select="./@ID"/>

                <li id="{$divID}">

                    <a href="{//METS:fileGrp[@USE='MEDIUM']/METS:file[@ID = //METS:div[@ID = $divID]/METS:fptr/@FILEID]/METS:FLocat/@xlink:href}" title="{./@LABEL}">
                        <xsl:variable name="thumbUse" select="//METS:fileGrp[@USE='THUMBNAIL']/METS:file[@ID = //METS:div[@ID = $divID]/METS:fptr/@FILEID]/METS:FLocat/@xlink:href"/>
                        <xsl:variable name="transcriptionUse" select="//METS:fileGrp[@USE='TRANSCRIPTION']/METS:file[@ID = //METS:div[@ID = $divID]/METS:fptr/@FILEID]/METS:FLocat/@xlink:href"/>

                        <xsl:choose>
                            <xsl:when test="string-length($thumbUse)">
                                <img src="{$thumbUse}" alt="{./@LABEL}"/>
                            </xsl:when>
                            <xsl:when test="string-length($transcriptionUse)">
                                <xsl:variable name="fileExt" select="substring($transcriptionUse, string-length($transcriptionUse) - 2)"/>
                                <img src="{concat($path, '/images/vudl/icons/', $fileExt, '.png')}" alt="{./@LABEL}"/>
                            </xsl:when>
                            <xsl:otherwise/>
                        </xsl:choose>
                    </a>

                    <br/>
                    <span>
                        <xsl:value-of select="./@LABEL"/>
                    </span>

                    <div class="ORIGINAL_src row_views">
                        <span>
                            <xsl:value-of select="//METS:fileGrp[@USE='ORIGINAL']/METS:file[@ID = //METS:div[@ID = $divID]/METS:fptr/@FILEID]/METS:FLocat/@xlink:href"/>
                        </span>
                    </div>
                    <div class="THUMBNAIL_src row_views">
                        <span>
                            <xsl:value-of select="//METS:fileGrp[@USE='THUMBNAIL']/METS:file[@ID = //METS:div[@ID = $divID]/METS:fptr/@FILEID]/METS:FLocat/@xlink:href"/>
                        </span>
                    </div>
                    <div class="MEDIUM_src row_views">
                        <span>
                            <xsl:value-of select="//METS:fileGrp[@USE='MEDIUM']/METS:file[@ID = //METS:div[@ID = $divID]/METS:fptr/@FILEID]/METS:FLocat/@xlink:href"/>
                        </span>
                    </div>
                    <div class="LARGE_src row_views">
                        <span>
                            <xsl:value-of select="//METS:fileGrp[@USE='LARGE']/METS:file[@ID = //METS:div[@ID = $divID]/METS:fptr/@FILEID]/METS:FLocat/@xlink:href"/>
                        </span>
                    </div>
                    <div class="AUDIO_src row_views">
                        <span>
                            <xsl:value-of select="//METS:fileGrp[@USE='AUDIO']/METS:file[@ID = //METS:div[@ID = $divID]/METS:fptr/@FILEID]/METS:FLocat/@xlink:href"/>
                        </span>
                    </div>
                    <div class="VIDEO_src row_views">
                        <span>
                            <xsl:value-of select="//METS:fileGrp[@USE='VIDEO']/METS:file[@ID = //METS:div[@ID = $divID]/METS:fptr/@FILEID]/METS:FLocat/@xlink:href"/>
                        </span>
                    </div>
                    <div class="OCR-DIRTY_src row_views">
                        <span>
                            <xsl:value-of select="//METS:fileGrp[@USE='OCR-DIRTY']/METS:file[@ID = //METS:div[@ID = $divID]/METS:fptr/@FILEID]/METS:FLocat/@xlink:href"/>
                        </span>
                        <div>
                            <xsl:value-of select="php:function('Record::getOCR', string(//METS:fileGrp[@USE='OCR-DIRTY']/METS:file[@ID = //METS:div[@ID = $divID]/METS:fptr/@FILEID]/METS:FLocat/@xlink:href))"/>
                        </div>
                    </div>
                    <div class="OCR-EDITED_src row_views">
                        <span>
                            <xsl:value-of select="//METS:fileGrp[@USE='OCR-EDITED']/METS:file[@ID = //METS:div[@ID = $divID]/METS:fptr/@FILEID]/METS:FLocat/@xlink:href"/>
                        </span>
                        <div>
                            <xsl:value-of select="php:function('Record::getOCR', string(//METS:fileGrp[@USE='OCR-EDITED']/METS:file[@ID = //METS:div[@ID = $divID]/METS:fptr/@FILEID]/METS:FLocat/@xlink:href))"/>
                        </div>
                    </div>
                    <div class="TRANSCRIPTION_src row_views">
                        <span>
                            <xsl:value-of select="//METS:fileGrp[@USE='TRANSCRIPTION']/METS:file[@ID = //METS:div[@ID = $divID]/METS:fptr/@FILEID]/METS:FLocat/@xlink:href"/>
                        </span>
                    </div>

                    <div class="ZOOM_src row_views">
                        <span>
                            <xsl:value-of select="//METS:fileGrp[@USE='LARGE']/METS:file[@ID = //METS:div[@ID = $divID]/METS:fptr/@FILEID]/METS:FLocat/@xlink:href"/>
                        </span>
                    </div>

                    <div class="MANIFEST_src row_views">
                        <span/>
                        <div>
                            <xsl:for-each select="//METS:fileGrp">
                                <xsl:variable name="USE" select="./@USE"/>
                                <xsl:variable name="useFile" select="//METS:fileGrp[@USE=$USE]/METS:file[@ID = //METS:div[@ID = $divID]/METS:fptr/@FILEID]/METS:FLocat/@xlink:href"/>

                                <xsl:if test="count($useFile)">
                                    <div style="height:auto;">
                                        <h3><xsl:value-of select="php:function('Record::capitalization', string(./@USE))"/></h3>
                                        <a href="{$useFile}" title="{$USE}">
                                            <xsl:value-of select="$useFile"/>
                                        </a>
                                    </div>
                                </xsl:if>
                            </xsl:for-each>
                        </div>
                    </div>

                </li>
            </xsl:for-each>
            </ul>
        </div>

    </xsl:template>

</xsl:stylesheet>