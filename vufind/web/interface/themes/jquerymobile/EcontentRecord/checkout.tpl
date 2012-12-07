<script type="text/javascript" src="{$path}/services/Record/ajax.js"></script>
<div id="checkout-econtent" class="content" data-role="dialog">
	<div data-role="header" data-theme="d" data-position="inline">
		<h1>{translate text='Checkout eContent'}</h1>
	</div>
	
	<div data-role="content">
		<div id="loginFormWrapper">
			<form id='checkoutItemForm' action="{$path}/EcontentRecord/{$id|escape:"url"}/Checkout" method="post" data-ajax="false">
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
						<input type="submit" name="submit" id="submit" value="{translate text='Checkout This Title'}"/>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
