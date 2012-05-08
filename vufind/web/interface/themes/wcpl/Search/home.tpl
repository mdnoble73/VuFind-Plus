<script type="text/javascript" src="{$path}/js/title-scroller.js"></script>
<script type="text/javascript">
  {* Create callback for title scrollers to load information above the scroller rather than inline *}
  {literal}
  var loadSelectedTitleInformation = function (titleDetails){
    $('#selectedTitleImage').html("<a href='" + path + "/Record/" + titleDetails['id'] + "/Home'><img src='" + titleDetails['image'] + "' title='cover'/></a>");
    $('#selectedTitleTitle').html("<a href='" + path + "/Record/" + titleDetails['id'] + "/Home'>" + titleDetails['title'] + "</a>");
    $('#selectedTitleAuthor').html("by " + titleDetails['author']);
    //Trim the title details by default.
    if (titleDetails['teaser'] != titleDetails['description']){
    	
    	var teaserCode = "<div class=\"teaser\" id=\"teaser\">";
    	teaserCode += titleDetails['teaser'] + "<span onclick=\"$('#teaser').hide();$('#fullDescription').show();\" class='reviewMoreLink'> (more)</span>";
    	teaserCode += "</div>";
    	teaserCode += "<div class=\"reviewTeaser\" id=\"fullDescription\" style='display:none'>";
    	teaserCode += titleDetails['description'];
    	teaserCode += "<span onclick=\"$('#teaser').show();$('#fullDescription').hide();\" class='reviewMoreLink'> (less)</span>";
    	teaserCode += "</div>";
    	$('#selectedTitleDescription').html(teaserCode);
    }else{
    	$('#selectedTitleDescription').html(titleDetails['description']);
    } 
    //$('#selectedTitleDescription').html(titleDetails['description']);
  }
  {/literal}
</script>
<div id="catalogHome">
<div id="brian_home"><img src="{$path}/interface/themes/wcpl/images/brian_home_150_b.png" alt="Blue Dog Mascot" /></div>
	<div id='titleDetails'>
    <div id="homePageLists">
			{include file='API/listWidgetTabs.tpl'}
	  </div>
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
        <input id="loginButtonHome" type="image" name="submit" value="Login" src='{$path}/interface/themes/{$theme}/images/login.png' alt='{translate text="Login"}' />
      </div>
      
    </form>
    <div id="loginOptions">
      <div id='needCardLabel'>
        <a href="{$path}/MyResearch/GetCard">I need a Library Card</a>
      </div>
      <div id='emailPinLabel' class='loginFormRow'>
        <a href="{$path}/MyResearch/EmailPin">I forgot my PIN number</a>
      </div>
    </div>
  </div>
  {/if}

	  
	
</div>

