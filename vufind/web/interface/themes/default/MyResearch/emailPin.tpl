<script type="text/javascript" src="{$path}/js/validate/jquery.validate.js"></script>
<div id="page-content" class="content">
  
  <div id="main-content">
    <div class="resulthead"><h3>{translate text='Forget Your PIN?'}</h3></div>
    <div class="page">
    	<p>Please enter your complete Wake County Libraries' card number.:</p>
    	<form id="emailPin" method="POST" action="{$path}/MyResearch/EmailPin">
    	<div class="emailPinRow">
      	<div class="emailPinLabel">Card Number<span class="required">*</span></div><div class="emailPinInput"><input name="barcode" type="text" size="14" maxlength="14" class="required"/><input id="emailPinSubmit" name="submit" class="button" type="submit" value="Email My Pin" /></div>
      </div>
			<p>Your current PIN number will be sent to the email addres on file for your account.</p> 
      </form>
    </div>
  </div>
</div>
<script type="text/javascript">
{literal}
	$(document).ready(function(){
		$("#emailPin").validate();
	});
{/literal}
</script>
