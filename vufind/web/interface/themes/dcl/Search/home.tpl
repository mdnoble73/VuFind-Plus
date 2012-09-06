<div id="catalogHome">
	<div id="homePageLists">
		{include file='API/listWidgetTabs.tpl'}
	</div>	
	
	{if $user}
		{include file="MyResearch/menu.tpl"}
	{else}
	<div id="homeLoginForm">
		<form id="loginForm" action="{$path}/MyResearch/Home" method="post">
			<div id="loginFormContents">
				<div id="loginTitleHome">Login to view your account, renew books, and more.</div>
				<div class="loginLabelHome">Barcode from your library card</div>
				<input class="loginFormInput" type="text" name="username" value="{$username|escape}" size="15"/>
				<div class="loginLabelHome">{translate text='Password'}</div>
				<input class="loginFormInput" type="password" name="password" size="15" id="password"/>
				<div class="loginLabelHome"><input type="checkbox" id="showPwd" name="showPwd" onclick="return pwdToText('password')"/><label for="showPwd">{translate text="Reveal Password"}</label></div>
				{if !$inLibrary}
				<div class="loginLabelHome"><input type="checkbox" id="rememberMe" name="rememberMe"/><label for="rememberMe">{translate text="Remember Me"}</label></div>
				{/if}
				<input id="loginButtonHome" type="image" name="submit" value="Login" src='{$path}/interface/themes/default/images/login.png' alt='{translate text="Login"}' />
			</div>
      <div id="loginOptions">
        <div id='needCardLabel'>
          <a href="http://getacard.org">I need a Library Card</a>
        </div>
        <div id='forgottenCardLabel' class='loginFormRow'>
          <a href="http://getacard.org/account_lookup_form_internal.php" >I forgot my card number</a>
        </div>
        <div id='emailPinLabel' class='loginFormRow'>
          <a href="http://getacard.org/pin_emailer_form.php">I forgot my PIN number</a>
        </div>
      </div>
		</form>
	</div>
	{/if}
</div>

