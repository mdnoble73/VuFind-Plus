{strip}
<div id="page-content" class="content">
	{if $message}<div class="error">{$message|translate}</div>{/if}

	<div id="loginFormWrapper">
    <h3 class="myAccountTitle">{translate text='Login to your account'}</h3>
		<form method="post" action="{$path}/MyResearch/Home" id="loginForm">
			<div id='loginFormFields'>
				<div id ='loginUsernameRow' class='loginFormRow'>
					<div class='loginLabel'>{translate text='Username'}: </div>
					<div class='loginField'><input type="text" name="username" id="username" value="{$username|escape}" size="28"/></div>
				</div>
				<div id ='loginPasswordRow' class='loginFormRow'>
					<div class='loginLabel'>{translate text='Password'}: </div>
					<div class='loginField'>
						<input type="password" name="password" id="password" size="28"/>
					</div>
				</div>
				<div id ='loginPasswordRow2' class='loginFormRow'>
					<div class='loginLabel'>&nbsp;</div>
					<div class='loginField'>
						<input type="checkbox" id="showPwd" name="showPwd" onclick="return pwdToText('password')"/><label for="showPwd">{translate text="Reveal Password"}</label>
					</div>
				</div>
				{if !$inLibrary}
				<div id ='loginPasswordRow3' class='loginFormRow'>
					<div class='loginLabel'>&nbsp;</div>
					<div class='loginField'>
						<input type="checkbox" id="rememberMe" name="rememberMe"/><label for="rememberMe">{translate text="Remember Me"}</label>
					</div>
				</div>
				{/if}
				{if $enableSelfRegistration == 0}
					<a href='{$path}/MyResearch/SelfReg'>Register for a new Library Card</a>
				{/if}
				<div id='loginSubmitButtonRow' class='loginFormRow'>
					<input type="submit" name="submit" value="Login" id="loginFormSubmit"/>
					{if $followup}<input type="hidden" name="followup" value="{$followup}"/>{/if}
        	{if $followupModule}<input type="hidden" name="followupModule" value="{$followupModule}"/>{/if}
        	{if $followupAction}<input type="hidden" name="followupAction" value="{$followupAction}"/>{/if}
        	{if $recordId}<input type="hidden" name="recordId" value="{$recordId|escape:"html"}"/>{/if}
        	{if $comment}<input type="hidden" name="comment" name="comment" value="{$comment|escape:"html"}"/>{/if}
					{if $returnUrl}<input type="hidden" name="returnUrl" value="{$returnUrl}"/>{/if}
	  
					{if $comment}
						<input type="hidden" name="comment" name="comment" value="{$comment|escape:"html"}"/>
					{/if}
				</div>
			</div>
		</form>
	</div>
    
    <div id="loginFormSidebar">
    	<h3 class="myAccountTitle">Sign up for immediate access</h3>
        <ul class="loginSidebar">
            <li>Download free eBooks and Audiobooks.</li>
            <li>Stream and download new music</li>
            <li>Build a wishlist of titles to borrow</li>
            <li>Place a Hold and send any book to your nearest branch</li>
        </ul>
        <div id="loginSidebarGetCardButton">
        	<div class="getCardLink resultAction button">
        	<a href="http://www.surveymonkey.com/s/OnlineCardReg_DemographicInfo">Get a Library Card</a>
            </div>
        </div>
    </div>
    
	<script type="text/javascript">$('#username').focus();</script>
</div>
{/strip}