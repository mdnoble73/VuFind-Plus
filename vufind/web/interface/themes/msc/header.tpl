<div id="banner_span"><!-- #BeginLibraryItem "/Library/cmu_banner.lbi" -->
<div id="banner_bg">
<div id="banner_left"><a href="http://www.coloradomesa.edu/index.html"><img width="300" height="87" alt="Colorado Mesa University" src="http://www.coloradomesa.edu/css/images/cmu_logo.png" /></a></div>
<div id="banner_right">
    <div class="alignright" style="text-align:right;">
       <div id="logoutOptions"{if !$user} style="display: none;"{/if}>
         <a href="{$path}/MyResearch/Home" id="myAccountNameLink">{$user->firstname|capitalize} {$user->lastname|capitalize}</a> | <a href="{$path}/MyResearch/Home">{translate text="Your Account"}</a> |
         <a href="{$path}/MyResearch/Logout">{translate text="Log Out"}</a>
       </div>
       <div id="loginOptions"{if $user} style="display: none;"{/if}>
         {if $authMethod == 'Shibboleth'}
           <a href="{$sessionInitiator}">{translate text="Institutional Login"}</a>
         {elseif $showLoginButton == 1}
           <a href="{$path}/MyResearch/Home" class='loginLink'>{translate text="login_link"}</a>
         {/if}
       </div>
       {if is_array($allLangs) && count($allLangs) > 1}
         {foreach from=$allLangs key=langCode item=langName}
           <a class='languageLink {if $userLang == $langCode} selected{/if}' href="{$fullPath}{if $requestHasParams}&amp;{else}?{/if}mylang={$langCode}">{translate text=$langName}</a>
         {/foreach}
       {/if}
     </div>
</div>
</div><!-- #EndLibraryItem --></div>
<div id="nav1_span">
<div id="nav1_bg">

</div></div>