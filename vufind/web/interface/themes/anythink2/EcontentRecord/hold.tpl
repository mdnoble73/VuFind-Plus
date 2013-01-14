<script type="text/javascript" src="{$path}/services/Record/ajax.js"></script>
{if $materials_request}
<h1>{translate text='Materials Request Found - Place a Hold'}</h1>
{else}
<h1>{translate text='Place a Hold'}</h1>
{/if}
<div id="loginFormWrapper">
  <form id='placeHoldForm' action="{$path}/EcontentRecord/{$id|escape:"url"}/Hold" method="post">
    <div>
      {if (!isset($user)) }
         <div id ='loginUsernameRow' class='loginFormRow'>
           <div class='loginLabel'>{translate text='Username'}: </div>
           <div class='loginField'><input type="text" name="username" id="username" value="{$username|escape}" size="15"/></div>
         </div>
         <div id ='loginPasswordRow' class='loginFormRow'>
           <div class='loginLabel'>{translate text='Password'}: </div>
           <div class='loginField'><input type="password" name="password" id="password" size="15"/></div>
         </div>
    {/if}
        {if $materials_request}
        <div>
          <h4>{translate text='Good news, we already have this title.'}</h4>
          <p>{translate text='This title is already available in our catalog. Place a hold on this item by selecting your pick-up location.'}</p>
        </div>
        {/if}
        <div class='loginFormRow'>
        <input type="hidden" name="type" value="hold"/>
        <div class="form-item"><input type="submit" name="submit" id="submit" value="{translate text='Request This Title'}"/></div>
        <div class="form-item"><input type="checkbox" name="autologout" /> Log me out after requesting the item.</div>
        </div>
      </div>
  </form>
</div>
