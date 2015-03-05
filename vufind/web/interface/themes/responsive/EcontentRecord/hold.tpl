<script type="text/javascript" src="{$path}/Record/ajax.js"></script>
<div id="page-content" class="content" data-role="page" >
	<div class="resulthead">
		<h3>{translate text='Place a Hold'}</h3>
	</div>

	<div id="loginFormWrapper">
		<p class="note">
			Holds put you in line for a title that is currently checked out by someone else.
			When it is your turn to use the title you will receive an e-mail with instructions to checkout the title.
		</p>
		<form id='placeHoldForm' action="{$path}/EcontentRecord/{$id|escape:'url'}/Hold" method="post">
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
					<input type="submit" name="submit" id="submit" value="{translate text='Submit Hold Request'}"/>
				</div>
			</div>
		</form>
	</div>
</div>
