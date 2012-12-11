<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"
    />
    <title>EPUB Download error</title>
    {css filename="styles.css"}
    {css filename="basicHtml.css"}
    {css filename="top-menu.css"}
    <link rel="stylesheet" type="text/css" href="{$path}/interface/themes/dcl/css/viewer.css" />
    
  {* This page only displays if an error occurred*}
    
  </head>

  <body>
  	<div id='hideableHeader'>
	  	{include file="top-menu.tpl"}
	  	
	  	<div class="searchheader">
	      <div class="searchcontent">
	        
	        {if $showTopSearchBox}
	          <a href="{if $homeLink}{$homeLink}{else}{$url}{/if}"><img src="{$path}{$smallLogo}" alt="VuFind" class="alignleft" /></a>
              {include file="Search/searchbox.tpl"}
	        {/if}
	
	        <div class="clearer">&nbsp;</div>
	      </div>
	    </div>
    </div>
    
    <div id="readerCntr">
    <div id="reader">
    {if $showLogin}
        <div id="epubLoginForm">
      <form id="loginForm" action="{$path}/MyResearch/Home" method="post">
        <div id="loginFormContents">
          <input type="hidden" name="id" value="{$id}"/>
          <input type="hidden" name="followup" value="Download"/>
          <input type="hidden" name="followupModule" value="EContent"/>
          <div id="loginTitleEPub">Please Login to download this book.</div>
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
      {/if}
   </div>
   </div>
  </body>

</html>
