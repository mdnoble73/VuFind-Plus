<script type="text/javascript" src="{$path}/services/Record/ajax.js"></script>
<div id="page-content" class="content">
	<div class="resulthead">
		<h3>{translate text='Checkout eContent'}</h3>
	</div>
		
	<div id="loginFormWrapper">
		<form id='checkoutItemForm' action="{$path}/EcontentRecord/{$id|escape:"url"}/Checkout" method="post">
			<div id='loginFormFields'>
				{if (!isset($user)) }
					<div id ='loginUsernameRow' class='loginFormRow'>
						<div class='loginLabel'>{translate text='Username'}: </div>
						<div class='loginField'><input type="text" name="username" id="username" value="{$username|escape}" size="15"/></div>
					</div>
					<div id ='loginPasswordRow' class='loginFormRow'>
						<div class='loginLabel'>{translate text='Password'}: </div>
						<div class='loginField'><input type="password" name="password" id="password" size="15"/></div>
					</div>
					<div id ='loginPasswordRow2' class='loginFormRow'>
						<div class='loginLabel'>&nbsp;</div>
						<div class='loginField'>
							<input type="checkbox" id="showPwd" name="showPwd" onclick="return pwdToText('password')"/><label for="showPwd">{translate text="Reveal Password"}</label>
						</div>
					</div>
				{/if}
				<div class='loginFormRow'>
					<input type="hidden" name="type" value="hold"/>
					<input type="submit" name="submit" id="submit" value="{translate text='Checkout Title'}"/>
					<input type="checkbox" name="autologout" /> Log me out after checking out the title. 
				</div>
			</div>
		</form>
	</div>
</div>
