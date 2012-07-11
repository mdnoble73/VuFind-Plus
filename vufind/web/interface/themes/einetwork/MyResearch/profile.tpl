<div id="page-content" class="content">
	<div id="sidebar">
    {include file="MyResearch/menu.tpl"}
      
    {include file="Admin/menu.tpl"}
  </div>
	
  <div id="main-content">
    {if $user->cat_username}
      <div class="resulthead">
      <h3>{translate text='Your Profile'}</h3></div>
      {if $profileUpdateErrors}
      <div class="error">{$profileUpdateErrors}</div>
      {/if}
      
      <div class="page">
      <form action='' method='post'>
      <table class="citation" width="100%">
        <tr><th width="100px">{translate text='Full Name'}:</th><td>{$profile.fullname|escape}</td></tr>
        <tr><th>{translate text='Address'}:</th><td>{$profile.address1|escape}</td></tr>
        <tr><th>{translate text='City'}:</th><td>{$profile.city|escape}</td></tr>
        <tr><th>{translate text='State'}:</th><td>{$profile.state|escape}</td></tr>
        <tr><th>{translate text='Zip'}:</th><td>{$profile.zip|escape}</td></tr>
        <tr><th>{translate text='Phone Number'}:</th><td>{if $edit == true}<input name='phone' value='{$profile.phone|escape}' size='50' maxlength='75' />{else}{$profile.phone|escape}{/if}</td></tr>
        <tr><th>{translate text='E-mail'}:</th><td>{if $edit == true}<input name='email' value='{$profile.email|escape}' size='50' maxlength='75' />{else}{$profile.email|escape}{/if}</td></tr>
        <tr><th>{translate text='Receive Notices By'}:</th>
        	<td>
        		{if $edit == true}
        			<input type="radio" name='notices' value='p' id='phoneNotice' {if $profile.notices == 'p'}checked='checked'{/if}/><label for='phoneNotice'>Phone</label>
        			<input type="radio" name='notices' value='z' id='emailNotice' {if $profile.notices == 'z'}checked='checked'{/if}/><label for='emalNotice'>Email</label>
        		{else}
        			{if $profile.notices == 'p'}Phone{else}Email{/if}
        		{/if}
        	</td>
        </tr>
        <tr><th>{translate text='Fines'}:</th><td>{$profile.fines|escape}</td></tr>
        <tr><th>{translate text='Expiration Date'}:</th><td>{$profile.expires|escape}</td></tr>
        <tr><th>{translate text='Home Library'}:</th><td>{$profile.homeLocation|escape}</td></tr>
        <tr><th>{translate text='My First Alternate Library'}:</th><td>{if $edit == true}{html_options name="myLocation1" options=$locationList selected=$profile.myLocation1Id}{else}{$profile.myLocation1|escape}{/if}</td></tr>
        <tr><th>{translate text='My Second Alternate Library'}:</th><td>{if $edit == true}{html_options name="myLocation2" options=$locationList selected=$profile.myLocation2Id}{else}{$profile.myLocation2|escape}{/if}</td></tr>
        {if $userIsStaff}
        <tr><th>{translate text='Bypass Automatic Logout'}:</th><td>{if $edit == true}<input type='radio' name="bypassAutoLogout" value='yes' {if $profile.bypassAutoLogout==1}checked='checked'{/if}/>Yes&nbsp;&nbsp;<input type='radio' name="bypassAutoLogout" value='no' {if $profile.bypassAutoLogout==0}checked='checked'{/if}/>No{else}{if $profile.bypassAutoLogout==0}No{else}Yes{/if}{/if}</td></tr>
        {/if}        
      </table>
      
      {if $canUpdate}
	      {if $edit == true}
	      <input type='submit' value='Update Profile' name='update'/>
	      {else}
	      <input type='submit' value='Edit Personal Information' name='edit'/>
	      {/if}
      {/if}
      </form>
      
      <a href="#" onclick="ajaxLightbox('/MyResearch/AJAX?method=getPinUpdateForm');return false;" class="button">Modify PIN Number</a>
      
      {* Display user roles if the user has any roles*}
      {if count($user->roles) > 0}
      <h3>{translate text='Your Roles'}</h3>
      <table class='citation'>
        {foreach from=$user->roles item=role}
          <tr><td>{$role}</td></tr>
        {/foreach} 
      </table>
      {/if}
      
    {else}
      <div class="page">
      You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
    {/if}</div>
    <b class="bbot"><b></b></b>
    </div>
  
	</div>
</div>
