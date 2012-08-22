<div id="header">
  
  {*controls while logged out*}
  <div class="loginOptions" {if $user != false}style='display:none'{/if}>
  <a href="http://www.wakegov.com/libraries/default.htm" alt="Wake County Public Libraries Home">
    <img src="{$path}/interface/themes/wcpl/images/header.png" width="1000" height="86" usemap="#top-logo-map" alt="Wake County Public Libraries"/>
    </a>
      	<div id="login_div">
	    <a href="{$path}/MyResearch/Home">
	      {translate text='Login to My Account'}
	    </a>
	  </div>
      
  </div>
  
  {*controls while logged in*}
  <div class="logoutOptions" {if $user == false}style='display:none'{/if}>
  <a href="http://www.wakegov.com/libraries/default.htm" alt="Wake County Public Libraries Home">
	  <img src="{$path}/interface/themes/wcpl/images/header.png" width="1000" height="86" usemap="#top-logo-map2" alt="Wake County Public Libraries"/>
    </a>
        <div id="loggedInName" class="menu-account-link logoutOptions">{translate text='Logged in as:'} <a href="{$path}/MyResearch/Home" id="myAccountNameLink">{if strlen($user->displayName) > 0}{$user->displayName}{else}{$user->firstname|capitalize} {$user->lastname|capitalize}{/if}</a></div> 
    
    <div id="account_div" class="menu-account-link logoutOptions"><a href="{$path}/MyResearch/Home">{translate text='My Account'}</a></div>
    <div id="logout_div" >
		  <a href="{$path}/MyResearch/Logout" class="logout_class">
	      {translate text='Logout'}
	    </a>
	  </div>
    <div id="loggedInName" class="menu-account-link logoutOptions">{translate text='Logged in as:'} <a href="{$path}/MyResearch/Home" id="myAccountNameLink">{if strlen($user->displayName) > 0}{$user->displayName}{else}{$user->firstname|capitalize} {$user->lastname|capitalize}{/if}</a></div> 
    
    
  </div>
  
  <!--<map id="top-logo-map">
    <area shape="rect" coords="0,0,305,80" href="http://www.wakegov.com/libraries/default.htm" alt="Wake County Public Libraries Home" />
  </map>-->
    
           <div id="menu-header">
    <div id="menu-header-links">
     <!--<span class="top-menu-item"><a href="{$url}">{translate text="New Search"}</a></span>-->
      <span class="top-menu-item"><a href="{$path}/Search/Advanced">{translate text="Advanced Search"}</a></span>
      <span class="top-menu-item"><a href="{$url}/Help/Home?topic=faq" title="{translate text='Help'}" onclick="window.open('{$url}/Help/Home?topic=faq', 'Help', 'width=720, height=430'); return false;">{translate text='Help'}<!--&nbsp;<img id='searchHelpIcon' src="{$url}/interface/themes/{$theme}/images/help.png" alt="{translate text='Search Tips'}" />--></a></span>
      <span class="top-menu-item"><a href="{$path}/MyResearch/GetCard">{translate text='Get Library Card'}</a></span>
      <div class="searchTools-link">
        <form method="post" id="langForm" action="">
        	<div>
          <select name="mylang" id="mylang" onchange="this.form.submit();">
            {foreach from=$allLangs key=langCode item=langName}
              <option value="{$langCode}"{if $userLang == $langCode} selected="selected"{/if}>{translate text=$langName}</option>
            {/foreach}
          </select>
          </div>
        </form>
      </div>
    </div>
    
  </div>

  <div id="searchheader">
  
  <!--{if $pageTemplate != 'home.tpl'}
  	<div id="brian_lies"><img src="{$path}/interface/themes/wcpl/images/brian_lies.png" height="75px" /></div>
     {/if}-->
    
    <div class="searchcontent">
      
        {if $pageTemplate != 'advanced.tpl'}
          {if $module=="Summon"}
            {include file="Summon/searchbox.tpl"}
          {elseif $module=="WorldCat"}
            {include file="WorldCat/searchbox.tpl"}
          {else}
            {include file="Search/searchbox.tpl"}
          {/if}
        {/if}
  
    </div><!-- end searchcontent-->
    
  
  
  </div><!-- end searchheader-->


<!--<a href="http://www.surveymonkey.com/s/brian_feedback2" rel="external" onclick="window.open (this.href, 'child'); return false"><div id="survey">What do you think<br />of our new catalog?</div><img src="{$path}/interface/themes/wcpl/images/survey_arrow.png" id="surv_arrow" /></a>-->
  
</div><!-- end header-->

