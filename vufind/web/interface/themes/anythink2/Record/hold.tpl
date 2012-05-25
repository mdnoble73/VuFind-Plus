<script type="text/javascript" src="{$path}/services/Record/ajax.js"></script>
<h1>{translate text='Place a Hold'}</h1>
<form id='placeHoldForm' name='placeHoldForm' action="{$path}/Record/{$id|escape:"url"}/Hold" method="post">
  <div>
    <div id="loginFormWrapper">
      {if (!isset($profile)) }
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
      <div id='holdOptions' {if (!isset($profile)) }style='display:none'{/if}>
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
          <input type="hidden" name="type" value="hold"/>
          <div class="form-item"><input type="submit" name="submit" id="requestTitleButton" value="{translate text='Request This Title'}" {if (!isset($profile))}disabled="disabled"{/if}/></div>
          <div class="form-item"><input type="checkbox" name="autologout" /> Log me out after requesting the item.</div>
        </div>
      </div>
    </div>
  </div>
</form>
