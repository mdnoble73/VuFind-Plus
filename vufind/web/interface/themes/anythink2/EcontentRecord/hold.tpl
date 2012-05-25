<script type="text/javascript" src="{$path}/services/Record/ajax.js"></script>
<h1>{translate text='Place a Hold'}</h1>
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
        <div class='loginFormRow'>
        <input type="hidden" name="type" value="hold"/>
        <div class="form-item"><input type="submit" name="submit" id="submit" value="{translate text='Request This Title'}"/></div>
        <div class="form-item"><input type="checkbox" name="autologout" /> Log me out after requesting the item.</div>
        </div>
      </div>
  </form>
</div>
