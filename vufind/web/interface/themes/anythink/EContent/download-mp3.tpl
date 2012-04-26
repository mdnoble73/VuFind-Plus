<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta
			name="viewport"
			content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"
		/>
		<title>{$bookTitle} - Douglas County Libraries E-Book Reader</title>

		<script type="text/javascript" src="{$path}/js/jquery-1.7.1.min.js"></script>
		<script type="text/javascript" src="{$path}/js/bookcart/json2.js"></script>
		<script type="text/javascript" src="{$path}/js/jquery.plugins.js"></script>
		<script type="text/javascript" src="{$path}/js/jplayer/jquery.jplayer.min.js"></script>
	  
		{css filename="styles.css"}
		{css filename="basicHtml.css"}
		{css filename="top-menu.css"}
		{css filename="viewer-custom.css"}

  </head>

  <body>
  	<div id='hideableHeader'>
	  	<div id="menu-header">
				
				<div id="menu-header-links">
					<div id="menu-account-links">
					<span class="menu-account-link logoutOptions top-menu-item"{if !$user} style="display: none;"{/if}><a href="{$path}/MyResearch/Home">{translate text="My Account"}</a></span>
					<span class="menu-account-link logoutOptions top-menu-item"{if !$user} style="display: none;"{/if}><a href="{$path}/MyResearch/Logout">{translate text="Log Out"}</a></span>
					{if $showLoginButton == 1}
					  <span class="menu-account-link loginOptions top-menu-item" {if $user} style="display: none;"{/if}><a href="{$path}/MyResearch/Home" class='loginLink'>{translate text="My Account"}</a></span>
					{/if}
					</div>
				</div>
			</div>
	  	
	  	<div id="ereaderlogo" class="searchheader">
	  		<a href="{if $homeLink}{$homeLink}{else}{$url}{/if}"><img src="{$path}/interface/themes/anythink/images/logo_ereader.png" alt="Anythink Libraries" /></a>
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
  					<div id="loginTitleEPub">Please Login to download this audio book.</div>
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
  	<div id="downloadableFiles">
  		<div id='downloadableFilesHeader'>Available Files</div>
  		<div id='downloadableFileNotes'>Please select a file to download.</div>
  		{foreach from=$mp3Filenames key=index item=mp3File}
  			<div class='downloadableFile' id='downloadableFile{$index}'><div class="downloadableSegementName"><a href="{$url}/EContent/{$id}/GetMedia?item={$item}&segment={$index}&download=true">{$mp3File.name}</a></div><div class="downloadableSegmentSize">({$mp3File.size|file_size})</div></div>
  		{/foreach}
  	</div>
    
    {/if}
    
  </body>

</html>