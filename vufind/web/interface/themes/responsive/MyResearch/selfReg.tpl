<script type="text/javascript" src="{$path}/js/validate/jquery.validate.js" ></script>
<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first">
      <div class="resulthead"><h3>{translate text='Register for a Library Card'}</h3></div>
        <div class="page">

<div id='selfRegDescription'>This page allows you to register as a patron of our library online.  You will have limited privileges initially.</div>
<div id='selfRegistrationFormContainer'>
{if (isset($selfRegResult) && $selfRegResult.success)}
  <div id='selfRegSuccess'>
  Congratulations, you have sucessfully registered for a new library card.  
  Your library card number is <strong>{$selfRegResult.barcode}</strong>.
  You will have limited privileges. 
  </div>
{else} 
	<form id='selfRegistrationForm' name='selfRegistrationForm' action='{$path}/MyResearch/SelfReg' method="post" >
	  <div>
      {if (isset($selfRegResult) && !$selfRegResult.success)}
        <div id='selfRegFailure'>
	      Sorry, we could not create register a library card for you with that information.  Please visit your local library to get a card.
	      </div>
      {/if}
	    <div class='selfRegRow'>
	      <label for='firstName'>First Name:</label><input id='firstName' name='firstName' type='text' maxlength="40" size="40" class='required' />
	    </div>
	    <div class='selfRegRow'>
	      <label for='lastName'>Last Name:</label><input id='lastName' name='lastName' type='text' maxlength="60" size="40" class='required' />
	    </div>
	    <div class='selfRegRow'>
	      <label for='address'>Address:</label><input id='address' name='address' type='text' maxlength="128" size="40" class='required' />
	    </div>
	    <div class='selfRegRow'>
	      <label for='city'>City:</label><input id='city' name='city' type='text' maxlength="48" size="20" class='required' />
	    </div>
	    <div class='selfRegRow'>
	      <label for='state'>State:</label><input id='state' name='state' type='text' maxlength="32" size="20" class='required' />
	    </div>
	    <div class='selfRegRow'>
	      <label for='zip'>Zip Code:</label><input id='zip' name='zip' type='text' maxlength="32" size="20" class='required' />
	    </div>
	    <div class='selfRegRow'>
	      <label for='state'>E-Mail:</label><input id='email' name='email' type='text' maxlength="128" size="40" class='email' />
	    </div>
	    <div class='selfRegRow'>
	      <input type="submit" name="submit" value="Submit"/>
	    </div>
	    <script type="text/javascript">{literal}
	      $(document).ready(function(){
	        $("#selfRegistrationForm").validate();
	      });{/literal}
			</script>
	  </div>
	</form>
{/if}
</div>

      </div>
      <b class="bbot"><b></b></b>
    </div>
  </div>
</div>
