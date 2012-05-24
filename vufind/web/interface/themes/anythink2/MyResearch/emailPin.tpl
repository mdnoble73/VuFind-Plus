<script type="text/javascript" src="{$path}/js/validate/jquery.validate.js"></script>
<div id="main-content">
  <h1>{translate text='Forgot Your PIN?'}</h1>
  <div class="page">
    <p>{translate text='Please enter your Anythink card number'}:</p>
    <form id="emailPin" method="POST" action="{$path}/MyResearch/EmailPin">
    <div class="emailPinRow">
      <div class="form-item">
        <div><label for="barcode">{translate text="Anythink Card Number"}</label><span class="required">*</span></div>
        <div><input id="barcode" name="barcode" type="text" class="required"/></div>
      </div>
      <div class="form-item"><input id="emailPinSubmit" name="submit" class="button" type="submit" value="Email My Pin" /></div>
    </div>
    <p>{translate text="Your current PIN number will be sent to the email address we have on file for you."}</p> 
    </form>
  </div>
</div>
<script type="text/javascript">
{literal}
  $(document).ready(function(){
    $("#emailPin").validate();
  });
{/literal}
</script>
