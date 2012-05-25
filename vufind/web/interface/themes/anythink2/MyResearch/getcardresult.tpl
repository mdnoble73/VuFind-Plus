<div id="page-content" class="content">
  <div id="main-content">
    {if $registrationResult.result}
    <h1>{translate text='Confirmation'}</h1>
    {else}
    <h1>{translate text='oops'}</h1>
    {/if}
    <div class="page">
    {if $registrationResult.result}
    <p>{translate text="You did it! The next step is to bring your photo ID to"} <a href="http://www.anythinklibraries.org/locations" title="{translate text="Anythink locations"}">{translate text="your local Anythink"}</a> {translate text="within 30 days to get your full access library card, giving you access to thousands of ideas right at your fingertips."}</p>
      <p>{translate text="Your temporary library card number is"}:&nbsp;{$registrationResult.tempBarcode}.</p>
      <form method="post" action="{$url}/MyResearch/Home" id="loginForm">
        <div id='loginFormFields'>
          <div id='haveCardLabel' class='loginFormRow'>Login now</div>
          <div id ='loginUsernameRow' class='loginFormRow'>
            <div class='loginLabel'>{translate text='Username'}: </div>
            <div class='loginField'><input type="text" name="username" id="username" value="{$registrationResult.tempBarcode|escape}" size="15"/></div>
          </div>
          <div id ='loginPasswordRow' class='loginFormRow'>
            <div class='loginLabel'>{translate text='Password'}: </div>
            <div class='loginField'><input type="password" name="password" size="15"/></div>
          </div>
          <div id='loginSubmitButtonRow' class='loginFormRow'>
            <input id="loginButton" type="image" name="submit" value="Login" src='{$path}/interface/themes/default/images/login.png' alt='{translate text="Login to your account"}' />
          </div>
        </div>
      </form>
    {else}
      <p>{translate text="We could not process your card application at this time. Please visit"} <a href="http://www.anythinklibraries.org/locations" title="{translate text="Anythink locations"}">{translate text="your local Anythink"}</a> {translate text="to get a card today!"}</p> 
    {/if}
    </div>
  </div>
</div>
