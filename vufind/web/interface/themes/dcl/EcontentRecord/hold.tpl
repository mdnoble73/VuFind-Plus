<script type="text/javascript" src="{$path}/services/Record/ajax.js"></script>
<div id="page-content" class="content">
    <div class="resulthead">
      <h3>{translate text='Place a Hold'}</h3>
    </div>
    
    <div id="loginFormWrapper">
  	<form id='placeHoldForm' action="{$path}/EcontentRecord/{$id|escape:"url"}/Hold" method="post">
  		<div>
        
		    {if (!isset($user)) }
          <div id='haveCardLabel' class='loginFormRow'>I have a Douglas County Library Card</div>
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
		    	<input type="submit" name="submit" id="submit" value="{translate text='Request This Title'}"/>
	        <input type="checkbox" name="autologout" /> Log me out after requesting the item. 
	        </div>
				</div>
	    
	  </form>
	  </div>
      
      {if (!isset($profile)) }
        <div id='needACardWrapper'>
          <div id='needCardLabel' class='loginFormRow'>
          <a href="http://getacard.org">I need a Douglas County Libraries Card</a>
          </div>
          <div class='loginFormRow'>
          <a href="http://getacard.org"><img src="{$path}/interface/themes/dcl/images/library_card.gif" alt="Get a Library Card" /></a>
          </div>
        </div>
        <div id='retreiveLoginInfo'>
          <div id='forgottenCardLabel' class='loginFormRow'>
            <a href="http://getacard.org/account_lookup_form_internal.php" >FORGOTTEN YOUR CARD NUMBER?</a>
          </div>
          <div id='emailPinLabel' class='loginFormRow'>
            <a href="http://getacard.org/pin_emailer_form.php">EMAIL MY PIN</a>
          </div>
        </div>
      {/if}
      </div>
	</form>
</div>
