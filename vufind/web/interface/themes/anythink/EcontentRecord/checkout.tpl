<script type="text/javascript" src="{$path}/services/Record/ajax.js"></script>
<div id="page-content" class="content">
    <div class="resulthead">
      <h3>{translate text='Checkout eContent'}</h3>
    </div>
    
    <div id="loginFormWrapper">
  	<form id='checkoutItemForm' action="{$path}/EcontentRecord/{$id|escape:"url"}/Checkout" method="post">
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
		    	<input type="submit" name="submit" id="submit" value="{translate text='Checkout This Title'}"/>
	        <input type="checkbox" name="autologout" /> Log me out after requesting the item. 
	        </div>
				</div>
	    
	  </form>
	  </div>
      
      {if (!isset($profile)) }
        <div id='needACardWrapper'>
					<div id='needCardLabel' class='loginFormRow'>
					<a href="{$path}/MyResearch/GetCard">I need an Anythink Library Card</a>
					</div>
					<div class='loginFormRow'>
					<a href="{$path}/MyResearch/GetCard"><img border="0" src="{$path}/interface/themes/{$theme}/images/library_card.jpg"></a>
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
