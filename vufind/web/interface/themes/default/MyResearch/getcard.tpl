<script type="text/javascript" src="{$path}/js/validate/jquery.validate.js"></script>
<div id="page-content" class="content">
  
  <div id="main-content">
    <div class="resulthead"><h3>{translate text='Get a Library Card'}</h3></div>
    <div class="page">
    	Please enter the following information:
    	<form id="getacard" method="POST" action="{$path}/MyResearch/GetCard">
    	<div class="getacardRow">
      	<div class="getacardLabel">First Name<span class="required">*</span></div><div class="getacardInput"><input name="firstName" type="text" size="50" maxlength="50" class="required"/></div>
      </div>
      <div class="getacardRow">
      	<div class="getacardLabel">Last Name<span class="required">*</span></div><div class="getacardInput"><input name="lastName" type="text" size="50" maxlength="50" class="required" /></div>
      </div>
      <div class="getacardRow">
      	<div class="getacardLabel">Preferred Language</span></div>
	      <div class="getacardInput">
	      	<select name="language">
	      		{foreach from=$selfRegLanguages key=value item=label}
	      			<option value="{$value}">{$label}</option>
	      		{/foreach}
	      	</select>
	      </div>
			</div>
			<div class="getacardRow">
      	<div class="getacardLabel">Birthdate<span class="required">*</span></div><div class="getacardInput"><input name="borrowerNote" type="text" size="80" maxlength="80" class="required"/></div>
      </div>
      <div class="getacardRow">
      	<div class="getacardLabel">Phone Number</div><div class="getacardInput"><input name="phone" type="text" size="20" maxlength="20" /></div>
      </div>
      <div class="getacardRow">
      	<div class="getacardLabel">Phone Type<span class="required">*</span></div>
	      <div class="getacardInput">
	      	<select name="phoneType" type="text">
	      		{foreach from=$selfRegPhoneType key=value item=label}
	      			<option value="{$value}">{$label}</option>
	      		{/foreach}
	      	</select>
	      </div>
			</div>
      <div class="getacardRow">
      	<div class="getacardLabel">Address<span class="required">*</span></div><div class="getacardInput"><input name="address1" type="text" size="80" maxlength="80" class="required"/></div>
      </div>
      <div class="getacardRow">
      	<div class="getacardLabel">Address 2</div><div class="getacardInput"><input name="address2" type="text" size="80" maxlength="80"/></div>
      </div>
      <div class="getacardRow">
      	<div class="getacardLabel">City, ST<span class="required">*</span></div>
	      <div class="getacardInput">
	      	<select name="citySt" type="text">
	      		{foreach from=$selfRegCityStates key=value item=label}
	      			<option value={$value}>{$label}</option>
	      		{/foreach}
	      	</select>
	      </div>
			</div>
      <div class="getacardRow">
      	<div class="getacardLabel">Zip Code<span class="required">*</span></div><div class="getacardInput"><input name="zip" type="text" size="10" maxlength="10" class="required" /></div>
      </div>
      <div class="getacardRow">
      	<div class="getacardLabel">Which anythink library will you visit the most?<span class="required">*</span></div>
	      <div class="getacardInput">
	      	<select name="location" type="text">
	      		{foreach from=$selfRegLocations key=value item=label}
	      			<option value={$value}>{$label}</option>
	      		{/foreach}
	      	</select>
	      </div>
			</div>
      <div class="getacardRow">
      	<div class="getacardLabel">Choose PIN#<span class="required">*</span></div><div class="getacardInput"><input name="pin" type="text" size="10" maxlength="10" class="required" /></div>
      </div>
      <div class="getacardRow">
      	<div class="getacardLabel">Confirm PIN#<span class="required">*</span></div><div class="getacardInput"><input name="confirmPin" type="text" size="10" maxlength="10" class="required" /></div>
      </div>
      <div class="getacardRow">
      	<div class="getacardLabel">Notice By<span class="required">*</span></div><div class="getacardInput"><select name="sendNoticeBy"><option value="1">E-mail</option><option value="0">Phone</option></select></div>
      </div>
      <div class="getacardRow">
      	<div class="getacardLabel">Email address<span class="required">*</span></div><div class="getacardInput"><input name="email" type="text" size="80" maxlength="80" class="required email" /></div>
      </div>
      <p class="selfRegTerms">I agree to be responsible for material borrowed with this card, and to pay any fees incurred on material charged on this card.</p>
      <p class="selfRegTerms">I am aware that there are no age restrictions on borrowing any library materials, and I accept the responsibility for my child's selection of materials and use of the Internet.</p>
      <input id="getacardSubmit" name="submit" class="button" type="submit" onclick="return checkNewCard()" value="I agree" />
      </form>
    </div>
  </div>
</div>
<script type="text/javascript">
{literal}
	$(document).ready(function(){
		$("#getacard").validate();
	});
{/literal}
</script>
