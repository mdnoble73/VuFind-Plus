<div id="sidebar-wrapper"><div id="sidebar">
{if $user}
  {include file="MyResearch/menu.tpl"}
{else}
  <form id="loginForm" action="{$path}/MyResearch/Home" method="post">
    <h3>{translate text="Catalog login"}</h3>
    <p>{translate text="Login to view your account, renew books, and more."}</p>
    <div class="form-item">
    <label for="username">{translate text='Library card number'}</label>
    <input id="username" type="text" name="username" value="{$username|escape}" />
    </div>
    <div class="form-item">
    <label for="password">{translate text='Password'}</label>
    <input id="password" type="password" name="password" />
    </div>
    <div class="form-item">
    <input type="checkbox" id="showPwd" name="showPwd" onclick="return pwdToText('password')"/><label for="showPwd">{translate text="Reveal Password"}</label>
    </div>
    {if !$inLibrary}
    	<div class="form-item">
	    <input type="checkbox" id="rememberMe" name="rememberMe"/><label for="rememberMe">{translate text="Remember Me"}</label>
	    </div>
    {/if}
    <div class="form-item" id="submit-wrapper">
    <input type="submit" name="submit" value="Login" />
    </div>
  </form>
  <ul>
    <li><a href="{$path}/MyResearch/GetCard">I need a Library Card</a></li>
    <li><a href="{$path}/MyResearch/EmailPin">I forgot my PIN number</a></li>
  </ul>
{/if}
</div></div>

<div id="main-content">
  {include file='API/listWidgetTabs.tpl'}
</div>
