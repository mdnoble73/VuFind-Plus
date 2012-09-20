<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta
			name="viewport"
			content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"
		/>
		<title>{$bookTitle} - {$libraryName} E-Book Reader</title>

		<script type="text/javascript" src="{$path}/js/jquery-1.7.1.min.js"></script>
		<script type="text/javascript" src="{$path}/js/bookcart/json2.js"></script>
		<script type="text/javascript" src="{$path}/js/jquery.plugins.js"></script>
		<script type="text/javascript" src="{$path}/js/mousewheel/jquery.mousewheel.min.js"></script>
		
		{css filename="styles.css"}
		{css filename="basicHtml.css"}
		{css filename="top-menu.css"}
		{css filename="viewer-custom.css"}
		
		{if !$errorOccurred}
		{literal}
			<script type="text/javascript">
				var manifest = new Array();
				{/literal}
				{foreach from=$manifest key=href item=manifestId}
					manifest['{$href}'] = '{$manifestId}';
				{/foreach}
				
				var tableOfContents = new Array();
				{foreach from=$contents item=tocEntry}
					tableOfContents['{if strlen($tocEntry->src) > 0}{$tocEntry->src}{else}{$tocEntry->location}{/if}'] = "{$tocEntry->title}";
					{foreach from=$tocEntry->children item=tocEntry2}
						tableOfContents['{if strlen($tocEntry->src) > 0}{$tocEntry->src}{else}{$tocEntry->location}{/if}'] = "{$tocEntry2->title}";
					{/foreach}
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
						$('#readerPosition').hide();
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
						$('#readerPosition').show();
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
				

				function prevPage(){
					//Check to see if we are already at the top of the page and if so, go back to the end of the previous item in the manifest
					if ($('#reader').scrollTop() == 0){
						var prevComponent = '';
						for (var manifestItem in manifest){
							if (manifest[manifestItem] == curComponent){
								break;
							}
							prevComponent = manifest[manifestItem]; 
						}
						if (prevComponent == ''){
							//alert ("At the top of the title");
						}else{
							showTocEntry(prevComponent, function (){
								var documentEnd = document.getElementById('reader').scrollHeight; 
								$('#reader').animate({
									scrollTop: documentEnd
								}, 500, saveCurrentPosition())
							});
						}
					}else{
						$('#reader').animate({
							scrollTop: -$('#reader').height() + $('#reader').scrollTop()
						}, 500, saveCurrentPosition());
					}
				}

				function nextPage(){
					//Check to see if we are already at the end of the page and if so, go back to the next item in the manifest
					if (Math.abs($('#reader').scrollTop() - (document.getElementById('reader').scrollHeight - document.getElementById('reader').offsetHeight)) < 20){
						//Get the current item in the manifest
						var nextComponent = '';
						var curComponentFound = false; 
						for (var manifestItem in manifest){
							if (curComponentFound){
								nextComponent = manifest[manifestItem];
								break;
							}
							if (manifest[manifestItem] == curComponent){
								curComponentFound = true;
							}
						}
						if (nextComponent == ''){
							//alert ("End of title reached");
						}else{
							showTocEntry(nextComponent);
						}
					}else{
						$('#reader').animate({
							scrollTop: $('#reader').height() + $('#reader').scrollTop()
						}, 500, saveCurrentPosition());
					}
				}

				var curComponent;
				function showTocEntry(componentId, completeCallback){
					componentParts = componentId.split('#', 2);
					component = componentParts[0];
					anchor = componentParts[1];
					//Get the entry via AJAX callback 
					if (curComponent != component){
						curComponent = component; 
						$('#reader').html("Loading...");
						var jsonString = $.ajax({
							url: '{/literal}{$path}{literal}/EContent/{/literal}{$id}{literal}/JSON',
							data: {method : "getComponentCustom", component: componentId, item: "{/literal}{$item}{literal}"},
							async: false,
							dataType: 'json'
							});
						var jsonData = $.parseJSON(jsonString.responseText);
						var jsonResult = jsonData.result; 
						if (jsonResult.length == 0){
							$('#reader').html("Could not find content for " + componentId);
						} else{
							$('#reader').html(jsonResult);
						}
					}

					if (typeof(completeCallback) != 'undefined'){
						completeCallback.call();
					}else{
						if ($('#' + anchor).offset() != null){
							$('#reader').animate({
								scrollTop: $('#' + anchor).offset().top + $('#reader').scrollTop() - $('#reader').offset().top
							}, 500, saveCurrentPosition());
						}else{
							saveCurrentPosition();
						}
					}
				}

				var ebookCookie = 'vufind_epub_{/literal}{$id}{literal}';
				function saveCurrentPosition(){
					setTimeout(function(){
						var curAnchor = '';
						$('#reader [id]').each(function(){
							var anchorId = this.id;
							var anchorTop = $(this).position().top;
							//console.log(anchorId + " : " + $(this).offset().top + " : " + $(this).position().top) + " : " + $('#reader').scrollTop();
							if (anchorTop < 0 ){
								curAnchor = anchorId;
							}else{
								//The next element is the one we want.
								return false;
							}
						});
						
						var currentPosition = {
								component: curComponent,
								topAnchor: curAnchor
						}
	
						$.cookie(ebookCookie, JSON.stringify(currentPosition), {path: '/', expires: 30});

						//Highlight the current component in the table of contents.
						$(".tocEntry").removeClass("selected");
						var currentTocEntry = null;
						for (var tocItem in tableOfContents){
							if (tocItem.indexOf(curComponent) == 0){
								if (currentTocEntry == null){
									currentTocEntry = tocItem;
								}else{
									//Check to see if the anchor is higher than the current page position. 
									tocParts = tocItem.split('#', 2);
									tocAnchor = tocParts[1];
									if (curAnchor != "" && $("#" + tocAnchor).position().top <= $("#" + curAnchor).position().top){
										currentTocEntry = tocItem;
									}
								} 
							} 
						}
						if (currentTocEntry != null){
							$("#toc" + currentTocEntry.replace('#', '_')).addClass("selected");
						}

						//Update the indicator showing how far through the section we are. 
						var percentComplete = (($("#reader").scrollTop()) / document.getElementById('reader').scrollHeight) * 100;
						$("#readerPositionIndicator").width(percentComplete + "%");
						
					}, 500);
				}

				var currentMagnification = 0;
				function toggleMagnification(){
					var currentFontSize = $('html').css('font-size');
					var currentFontSizeNum = parseFloat(currentFontSize, 10);
					if (currentMagnification == 0){
						var newFontSize = currentFontSizeNum*1.1;
						$("#reader").css('font-size', newFontSize);
						currentMagnification = 1;
					}else{
						var newFontSize = currentFontSizeNum*0.9;
						$("#reader").css('font-size', newFontSize);
						currentMagnification = 0;
					}
					//Reload the current position since the position will have changed with the font size change.
					var currentPositionCookie = $.cookie(ebookCookie);
					if (currentPositionCookie == null){
						showTocEntry('{/literal}{$contents.0->src}{literal}');
					}else{
						currentPosition = JSON.parse(currentPositionCookie);
						showTocEntry(currentPosition.component + "#" + currentPosition.topAnchor);
					}
				}
				
				$(document).ready(function(){
					$('#reader').click(function (e){
						//check to see where the user clicked 
						var y = e.pageY - this.offsetTop;
						var height = $('#reader').height();
						if (y < height / 6){
							prevPage();
						}else if (y > 5 * height / 6){
							nextPage();
						}
						
						var x = e.pageX - this.offsetLeft;
						var width = $('#reader').width();
						if (x < width / 6){
							prevPage();
						}else if (x > 5 * width / 6){
							nextPage();
						}
					});
					$('#reader a').live('click', function (e){
						//Split the link on the # symbol
						var href = $(this).attr('href');
						componentParts = href.split('#', 2);
						component = componentParts[0];
						//Strip of the current page url if it exists. 
						anchor = componentParts[1];
						//Translate the component based on the manifest
						component = manifest[component];
						showTocEntry(component + "#" + anchor);
						return false;
					});
					$('#reader').mousewheel(function(event, delta){
						if (delta > 0){
							prevPage();
						}else if (delta < 0){
							nextPage();
						}
						return false;
					});
					$("#reader").touchwipe({
						wipeLeft: function() { nextPage(); },
						wipeRight: function() { prevPage(); },
						wipeUp: function() { prevPage(); },
						wipeDown: function() { nextPage(); },
						min_move_x: 20,
						min_move_y: 20,
						preventDefaultEvents: true
					});
					

					document.getElementById('reader').onselectstart = function() {return false;}
					document.getElementById('reader').onmousedown = function() {return false;}

					//Show the correct initial component
					var currentPositionCookie = $.cookie(ebookCookie);
					if (currentPositionCookie == null){
						showTocEntry('{/literal}{$contents.0->src}{literal}');
					}else{
						currentPosition = JSON.parse(currentPositionCookie);
						showTocEntry(currentPosition.component + "#" + currentPosition.topAnchor);
					}
				});
		{/literal}						
		</script>
		
	 {/if}
		
	</head>

	<body oncontextmenu="return false;">
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
				<a href="{if $homeLink}{$homeLink}{else}{$path}{/if}"><img src="{img filename="logo_ereader.png"}" alt="{$libraryName}" /></a>
				<div id="returnToCatalogLink"><a title="Return to Catalog" href="{$url}">Return To Catalog</a></div>
			</div>
		</div>
		
		<div id="epubToolbarTop">
			<div id="epubTitle">{$bookTitle}{if $bookCreator} by {$bookCreator}{/if}</div>
			<div id="epubToolbarTopRight">
				{* Need to correctly get the component to search that contents the text.
				<div id="searchEpub">
					<form id="searchEpubForm" action="#" method="get" onsubmit="searchEPub();return false;">
						<div id="searchEpubFormContents">
							<input type="text" id="searchEPubValue" />
							<input type="submit" value="Search" />
						</div>
					</form>
				</div>
				*}
			</div>
		</div>
		
		{if $showLogin}
			<div id="epubLoginForm">
				<form id="loginForm" action="{$path}/MyResearch/Home" method="post">
					<div id="loginFormContents">
						<input type="hidden" name="id" value="{$id}"/>
						<input type="hidden" name="returnUrl" value="{$path}{$fullPath}"/>
						<div id="loginTitleEPub">Please Login to read this book.</div>
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
			{foreach from=$contents item=tocEntry}
				<div class='tocEntry' id='toc{if strlen($tocEntry->src) > 0}{$tocEntry->src|replace:'#':'_'}{else}{$tocEntry->location|replace:'#':'_'}{/if}'><a href="#" onclick="return showTocEntry('{if strlen($tocEntry->src) > 0}{$tocEntry->src|replace:'#':'_'}{else}{$tocEntry->location|replace:'#':'_'}{/if}');">{$tocEntry->title}</a>
					{foreach from=$tocEntry->children item=tocEntry2}
						<div class='tocEntry' id='toc{if strlen($tocEntry->src) > 0}{$tocEntry->src|replace:'#':'_'}{else}{$tocEntry->location|replace:'#':'_'}{/if}'>
							<a href="#" onclick="return showTocEntry('{if strlen($tocEntry2->src) > 0}{$tocEntry2->src|replace:'#':'_'}{else}{$tocEntry2->location|replace:'#':'_'}{/if}');">{$tocEntry2->title}</a>
						</div>
					{/foreach}
				</div>
			{/foreach}
		</div>
		
		<div id="readerCntr">
			<div id="readerPosition">
				<div id="readerPositionIndicator" >&nbsp;</div>
			</div>
			<div id="reader" class="swipe">
			
			</div>
		</div>
		
		<div id="toolbarToggle" class="controlsVisible" onclick="toggleControls();">Hide Controls</div>
		<div id="epubToolbarBottom">
			<div id="textSize" onclick="toggleMagnification();" class="normal"><span class="controls_magnifier_a">A</span><span class="controls_magnifier_A">A</span></div>
			<div id="toggleTOC" onclick="toggleToc();">Hide TOC</div>
			<div id="epubToolbarBottomRight">
				<div id="pagePrev" onclick="prevPage();">◄</div>
				<div id="pageNext" onclick="nextPage();">►</div>
			</div>
		</div>
		{/if}
		
	</body>

</html>
