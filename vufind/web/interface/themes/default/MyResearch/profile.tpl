<div id="page-content" class="content">
  <div id="sidebar">
    {include file="MyResearch/menu.tpl"}
    
    {include file="Admin/menu.tpl"}
  </div>
  
  <div id="main-content">
    {if $user->cat_username}
      <div class="resulthead">
      <h3>{translate text='Your Profile'}</h3></div>
      {if $userNoticeFile}
        {include file=$userNoticeFile}
      {/if}
      
      <div class="page">
      {if $profileUpdateErrors}
      	<div class='profileUpdateErrors'>
	      {foreach from=$profileUpdateErrors item=error}
	      	<div class='profileUpdateError'>{$error}</div>
	      {/foreach}
      	</div>
      {/if}
      <form action='' method='post'>
      <table class="citation" width="100%">
      	<thead>
      	<tr><th colspan='2'>Personal Information - can only be changed by the library</th></tr>
      	</thead>
        <tr><th width="100px">{translate text='Full Name'}:</th><td>{$profile.fullname|escape}</td></tr>
        <tr><th width="100px">{translate text='Display Name'}:</th><td><input type ='text' name='displayName' id='displayName' value='{$profile.displayName|escape}' /> <span class='fieldHelp'>A name to display when you are logged in and with any reviews you have submitted.</span></td></tr>
        <tr><th>{translate text='Address'}:</th><td>{if false && $edit == true}<input name='address1' value='{$profile.address1|escape}' size='50' maxlength='75' />{else}{$profile.address1|escape}{/if}</td></tr>
        <tr><th>{translate text='City'}:</th><td>{if false && $edit == true}<input name='city' value='{$profile.city|escape}' size='50' maxlength='75' />{else}{$profile.city|escape}{/if}</td></tr>
        <tr><th>{translate text='State'}:</th><td>{if false && $edit == true}<input name='state' value='{$profile.state|escape}' size='50' maxlength='75' />{else}{$profile.state|escape}{/if}</td></tr>
        <tr><th>{translate text='Zip'}:</th><td>{if false && $edit == true}<input name='zip' value='{$profile.zip|escape}' size='50' maxlength='75' />{else}{$profile.zip|escape}{/if}</td></tr>
        <tr><th>{translate text='Phone Number'}:</th><td>{if false && $edit == true}<input name='phone' value='{$profile.phone|escape}' size='50' maxlength='75' />{else}{$profile.phone|escape}{/if}</td></tr>
        <tr><th>{translate text='Expiration Date'}:</th><td>{$profile.expires|escape}</td></tr>
        <tr><th>{translate text='Home Library'}:</th><td>{$profile.homeLocationName|escape}</td></tr>
        <tr><th>{translate text='Show Recommendations'}:</th><td><input type="radio" name='disableRecommendations' {if $user->disableRecommendations == 1}checked="checked"{/if} value="1"/>No <input type="radio" name='disableRecommendations' {if $user->disableRecommendations == 0}checked="checked"{/if} value="0"/> Yes</td></tr>
        <tr><th>{translate text='Show Cover Art'}:</th><td><input type="radio" name='disableCoverArt' {if $user->disableCoverArt == 1}checked="checked"{/if} value="1"/>No <input type="radio" name='disableCoverArt' {if $user->disableCoverArt == 0}checked="checked"{/if} value="0"/> Yes</td></tr>
        {if $onInternalIP || $profile.bypassAutoLogout==1}
        <tr><th>{translate text='Bypass Automatic Logout'}:</th><td><input type='radio' name="bypassAutoLogout" value='yes' {if $profile.bypassAutoLogout==1}checked='checked'{/if}/>Yes&nbsp;&nbsp;<input type='radio' name="bypassAutoLogout" value='no' {if $profile.bypassAutoLogout==0}checked='checked'{/if}/>No<br/><em>Warning: If this is set to yes, you must manually logout of your account.  You should not use this setting if you regularly access the catalog from public computers.</em></td></tr>
        {/if}
        <tr><th colspan='2'><input type='submit' value='Update' name='update'/></th></tr>

      	<thead>
      	<tr><th colspan='2'>Email Information</th></tr>
        </thead>
      	<tr><th>{translate text='E-mail'}:</th><td>{if true || $edit == true}<input name='email' value='{$profile.email|escape}' size='50' maxlength='75' />{else}{$profile.email|escape}{/if}</th></tr>
      	<tr><th colspan='2'><input type='submit' value='Update' name='update'/></th></tr>
      	
      	<thead>
      	<tr><th colspan='2'>Personal Identification Number (PIN) or the last 4 digits of your telephone no.</th></tr>
        </thead>
        <tr><th>{translate text='Old PIN'}:</th><td><input type='password' name='oldPin' value='' size='4' maxlength='4' /></td></tr>
        <tr><th>{translate text='New PIN'}:</th><td><input type='password' name='newPin' value='' size='4' maxlength='4' /></td></tr>
        <tr><th>{translate text='Re-enter New PIN'}:</th><td><input type='password' name='verifyPin' value='' size='4' maxlength='4' /></td></tr>
      	<tr><th colspan='2'><input type='submit' value='Update' name='update'/></th></tr>
      	
      </table>
      
      </form>
    {else}
      <div class="page">
      You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
    {/if}</div>
    </div>
  
</div>

