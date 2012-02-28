<div id="page-content" class="content">
	    
<form id='placeHoldForm' action="{$url}/MyResearch/HoldMultiple" method="post">
	<div>
    <div id="loginFormWrapper">
	  {foreach from=$ids item=id}
	     <input type="hidden" name="selected[{$id|escape:url}]" value="on" />
	  {/foreach}
	  {if (!isset($profile)) }
	  <div id='haveCardLabel' class='loginFormRow'>I have a Wake County Library Card</div>
      <div id ='loginUsernameRow' class='loginFormRow'>
        <div class='loginLabel'>{translate text='Username'}: </div>
        <div class='loginField'><input type="text" name="username" id="username" value="{$username|escape}" size="15"/></div>
      </div>
      <div id ='loginPasswordRow' class='loginFormRow'>
        <div class='loginLabel'>{translate text='Password'}: </div>
        <div class='loginField'><input type="password" name="password" id="password" size="15"/></div>
      </div>
      <div id='loginSubmitButtonRow' class='loginFormRow'>
      <input id="loginButton" type="button" onclick="GetPreferredBranches('{$id|escape}');" value="Login"/>
    </div>
	  {/if}
	  <div id='pickupOptions' {if (!isset($profile)) }style='display:none'{/if}>
        <div class='loginFormRow'>
        <div class='loginLabel'>{translate text="I want to pick this up at"}: </div>
        <div class='loginField'>
        <select name="campus" id="campus">
          {if count($pickupLocations) > 0}
            {foreach from=$pickupLocations item=location}
              <option value="{$location->code}" {if $location->selected == "selected"}selected="selected"{/if}>{$location->displayName}</option>
            {/foreach}
          {else} 
            <option>placeholder</option>
          {/if}
        </select>
        </div>
        </div>
        <div class='loginFormRow'>
        <input type="hidden" name="holdType" value="hold"/>
        <input type="submit" name="submit" id="submit" value="{translate text='Request This Title'}" {if (!isset($profile))}disabled="disabled"{/if}/>
        <input type="checkbox" name="autologout" /> Log me out after requesting the item. 
        </div>
      </div>
      </div>
      
      {if (!isset($profile)) }
        <div id='needACardWrapper'>
          <div id='needCardLabel' class='loginFormRow'>
          <a href="{$path}/MyResearch/GetCard">I need a Wake County Library Card</a>
          </div>
          <div class='loginFormRow'>
          <a href="{$path}/MyResearch/GetCard"><img src="{$path}/interface/themes/{$theme}/images/wcpl_card.jpg" alt="Get a Library Card" /></a>
          </div>
        </div>
        <div id='retreiveLoginInfo'>
          <div id='emailPinLabel' class='loginFormRow'>
            <a href="{$path}/MyResearch/EmailPin">EMAIL MY PIN</a>
          </div>
        </div>
      {/if}
      </div>
</form>
</div>
