<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta
			name="viewport"
			content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"
		/>
		<title>{$bookTitle} - {$libraryName} MP-3 Player</title>

		<script type="text/javascript" src="{$path}/js/jquery-1.7.1.min.js"></script>
		<script type="text/javascript" src="{$path}/js/bookcart/json2.js"></script>
		<script type="text/javascript" src="{$path}/js/jquery.plugins.js"></script>
		<script type="text/javascript" src="{$path}/js/jplayer/jquery.jplayer.min.js"></script>
	  
		{css filename="styles.css"}
		{css filename="basicHtml.css"}
		{css filename="top-menu.css"}
		{css filename="viewer-custom.css"}
		{css filename="jplayer.blue.monday.css"}
    
		{if !$errorOccurred}
			{literal}
			<script type="text/javascript">
				var filenames = new Array();
				var curSegmentIndex = 0;
				{/literal}
				{foreach from=$mp3Filenames key=index item=filename}
					filenames[{$index}] = '{$filename}';
				{/foreach}
				{literal}

				var tocVisible = true;
				function toggleControls(){
					if ($('#toolbarToggle').hasClass('controlsVisible')){
						$('#toolbarToggle').removeClass('controlsVisible');
						$('#toolbarToggle').addClass('controlsHidden');
						$('#hideableHeader').hide();
						$('#epubToolbarTop').hide();
						$('#epubToolbarBottom').hide();
						$('#reader').css('top', '0px');
						$('#reader').css('bottom', '0px');
						$('#reader').css('left', '0px');
						$('#tableOfContents').hide();
						$('#toolbarToggle').html('Show Controls');
					}else{
						$('#toolbarToggle').removeClass('controlsHidden');
						$('#toolbarToggle').addClass('controlsVisible');
						$('#hideableHeader').show();
						$('#epubToolbarTop').show();
						$('#epubToolbarBottom').show();
						$('#reader').css('top', '104px');
						$('#reader').css('bottom', '23px');
						if (tocVisible){
							$('#reader').css('left', '200px');
							$('#tableOfContents').show();
						}else{
							$('#reader').css('left', '0px');
							$('#tableOfContents').hide();
						}
						$('#toolbarToggle').html('Hide Controls');
					}
				}

				function toggleToc(){
          if (tocVisible){
	        	tocVisible = false;
	        	$('#tableOfContents').hide();
	        	$('#reader').css('left', '0px');
	        	$('#toggleTOC').html("Show TOC");
          }else{
          	tocVisible = true;
	        	$('#tableOfContents').show();
	        	$('#reader').css('left', '200px');
	        	$('#toggleTOC').html("Hide TOC");
          }
				}
        

				function prevSegment(){
          //Check to see if we are already at the top of the page and if so, go back to the end of the previous item in the manifest
        	curSegmentIndex--;
        	playFile(curSegmentIndex);
				}

				function nextSegment(){
					//Check to see if we are already at the end of the page and if so, go back to the next item in the manifest
					curSegmentIndex++;
					if (curSegmentIndex < filenames.length){
						playFile(curSegmentIndex);
					}
				}

				function playFile(segmentIndex, headPosition){
        	curSegmentIndex = segmentIndex;
          $('.jp-title').html(filenames[segmentIndex]);
          $("#jquery_jplayer_1").jPlayer("clearMedia");
          
          $("#jquery_jplayer_1").jPlayer("setMedia", {
        		mp3: "{/literal}{$url}/EContent/{$id}/GetMedia?item={$item}&segment=" + segmentIndex{literal}
      		});
      		if (headPosition == "undefined"){
          	$("#jquery_jplayer_1").jPlayer("play");
      		}else{
      			$("#jquery_jplayer_1").jPlayer("play", headPosition);
      		}
          saveCurrentPosition();
				}

        var ebookCookie = 'vufind_epub_{/literal}{$id}{literal}';
        function saveCurrentPosition(){
        	var currentPosition = {
              currentSegment: curSegmentIndex,
              headPosition: $("#jquery_jplayer_1").data("jPlayer").status.currentTime
          }

          $.cookie(ebookCookie, JSON.stringify(currentPosition), {path: '/', expires: 30});

        	//Highlight the current component in the table of contents.
        	$(".tocEntry").removeClass("selected");
        	$("#tocEntry" + curSegmentIndex).addClass("selected");
        }
        
        $(document).ready(function(){
        	$("#jquery_jplayer_1").jPlayer({
        		ready: function (event) {
							var currentPositionCookie = $.cookie(ebookCookie);
							if (currentPositionCookie == null){
								playFile(curSegmentIndex);
							}else{
								currentPosition = JSON.parse(currentPositionCookie);
								playFile(currentPosition.currentSegment, currentPosition.headPosition);
							}
						},
        		ended: function (event){
        			nextSegment();
        		},
        		stop: function (event){
          		saveCurrentPosition();
        		},
        		pause: function (event){
          		saveCurrentPosition();
        		},
        		solution: "html, flash", 
        		supplied: "mp3",
        		swfPath: "{/literal}{$url}{literal}/js/jplayer/Jplayer.swf",
        		wmode: "window"
        		});
        	
        });
    {/literal}		    	  
    </script>
    
   {/if}
    
  </head>

  <body>
  	<div id='hideableHeader'>
	  	<div id="menu-header">
				
				<div id="menu-header-links">
					<div id="menu-account-links">
					<span class="menu-account-link logoutOptions top-menu-item"{if !$user} style="display: none;"{/if}><a href="{$path}/MyResearch/EContentCheckedOut">{translate text="My Account"}</a></span>
					<span class="menu-account-link logoutOptions top-menu-item"{if !$user} style="display: none;"{/if}><a href="{$path}/MyResearch/Logout">{translate text="Log Out"}</a></span>
					{if $showLoginButton == 1}
					  <span class="menu-account-link loginOptions top-menu-item" {if $user} style="display: none;"{/if}><a href="{$path}/MyResearch/Home" class='loginLink'>{translate text="My Account"}</a></span>
					{/if}
					</div>
				</div>
			</div>
	  	
	  	<div id="ereaderlogo" class="searchheader">
	  		<a href="{if $homeLink}{$homeLink}{else}{$url}{/if}"><img src="{$path}/interface/themes/{$theme}/images/logo_ereader.png" alt="{$libraryName}" /></a>
	  		<div id="returnToCatalogLink"><a title="Return to Catalog" href="{$url}">Return To Catalog</a></div>
	    </div>
    </div>
    
    <div id="epubToolbarTop">
      <div id="epubTitle">{$bookTitle}{if $bookCreator} by {$bookCreator}{/if}</div>
      <div id="epubToolbarTopRight">
      </div>
    </div>
  	
  	{if $showLogin}
      <div id="epubLoginForm">
  			<form id="loginForm" action="{$path}/MyResearch/Home" method="post">
  				<div id="loginFormContents">
            <input type="hidden" name="id" value="{$id}"/>
            <input type="hidden" name="returnUrl" value="{$url}{$fullPath}"/>
  					<div id="loginTitleEPub">Please Login to listen to this book.</div>
  					<div class="loginLabelEPub">Barcode from your library card</div>
  					<input class="loginFormInput" type="text" name="username" value="{$username|escape}" size="15"/>
  					<div class="loginLabelEPub">{translate text='Password'}</div>
  					<input class="loginFormInput" type="password" name="password" size="15"/>
  					<input id="loginButtonEPub" type="image" name="submit" value="Login" src='{$path}/interface/themes/default/images/login.png' alt='{translate text="Login"}' />
  				</div>
  			</form>
  		</div>
    {elseif $errorOccurred}
      <div id="epubError">{$errorMessage}</div>
    {else}
  	<div id="tableOfContents">
  		<div id='tableOfContentsHeader'>Table of Contents </div>
  		{foreach from=$mp3Filenames key=index item=mp3File}
  			<div class='tocEntry' id='tocEntry{$index}'><a href="#" onclick="return playFile('{$index}');">{$mp3File}</a></div>
  		{/foreach}
  	</div>
  	
    <div id="readerCntr">
      <div id="reader" class="swipe">
      	<div id="jquery_jplayer_1"></div>
      	<div class="jp-audio" id="jp_container_1">
			<div class="jp-type-single">
				<div class="jp-gui jp-interface">
					<ul class="jp-controls">
						<li><a tabindex="1" class="jp-play" href="javascript:;">play</a></li>
						<li><a tabindex="1" class="jp-pause" href="javascript:;" style="display: none;">pause</a></li>
						<li><a tabindex="1" class="jp-stop" href="javascript:;">stop</a></li>
						<li><a title="mute" tabindex="1" class="jp-mute" href="javascript:;">mute</a></li>
						<li><a title="unmute" tabindex="1" class="jp-unmute" href="javascript:;" style="display: none;">unmute</a></li>
						<li><a title="max volume" tabindex="1" class="jp-volume-max" href="javascript:;">max volume</a></li>
					</ul>
					<div class="jp-progress">
						<div class="jp-seek-bar" style="width: 100%;">
							<div class="jp-play-bar" style="width: 0%;"></div>
						</div>
					</div>
					<div class="jp-volume-bar">
						<div class="jp-volume-bar-value" style="width: 80%;"></div>
					</div>
					<div class="jp-time-holder">
						<div class="jp-current-time">00:00</div>
						<div class="jp-duration">04:27</div>
					</div>
				</div>
				<div class="jp-title"></div>
				<div class="jp-no-solution" style="display: none;">
					<span>Update Required</span>
					To play the media you will need to either update your browser to a recent version or update your <a rel="external" onclick="window.open (this.href, 'child'); return false" href="http://get.adobe.com/flashplayer/">Flash plugin</a>.
				</div>
			</div>
		</div>
      </div>
    </div>
    
    <div id="toolbarToggle" class="controlsVisible" onclick="toggleControls();">Hide Controls</div>
    <div id="epubToolbarBottom">
      <div id="toggleTOC" onclick="toggleToc();">Hide TOC</div>
      <div id="epubToolbarBottomRight">
        <div id="pageSegment" onclick="prevSegment();">◄</div>
        <div id="pageSegment" onclick="nextSegment();">►</div>
    	</div>
    </div>
    {/if}
    
  </body>

</html>
