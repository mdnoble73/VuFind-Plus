<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal">×</button>
	<h3 id="modal-title">{translate text='Text Title'}</h3>
</div>
<div class="modal-body">
	<form method="post" action="{$path}{$formTargetPath|escape}" name="popupForm" class="form-horizontal">
		<div class="control-group">
			<label for="sms_phone_number" class="control-label">{translate text="Number"}: </label>
			<div class="controls">
	      <input type="text" name="to" id="sms_phone_number" value="{translate text="sms_phone_number"}"
	        onfocus="if (this.value=='{translate text="sms_phone_number"}') this.value=''"
	        onblur="if (this.value=='') this.value='{translate text="sms_phone_number"}'">
	    </div>
	  </div>
		<div class="control-group">
			<label for="provider" class="control-label">{translate text="Provider"}: </label>
			<div class="controls">
	      <select name="provider">
	        <option selected=true value="">{translate text="Select your carrier"}</option>
	        {foreach from=$carriers key=val item=details}
	        <option value="{$val}">{$details.name|escape}</option>
	        {/foreach}
	      </select>
	    </div>
	  </div>
	</form>
</div>
<div class="modal-footer">
	<button class="btn" data-dismiss="modal" id="modalClose">Close</button>
	<input type="submit" class="btn btn-primary" value="{translate text='Send'}"  onclick="'return SendSMS(&quot;{$id|escape}&quot;, this.elements[&quot;to&quot;].value,
					this.elements[&quot;provider&quot;][this.elements[&quot;provider&quot;].selectedIndex].value,
	{* Pass translated strings to Javascript -- ugly but necessary: *}
	{literal}{{/literal}sending: &quot;{translate text='sms_sending'}&quot;,
					success: &quot;{translate text='sms_success'}&quot;,
					failure: &quot;{translate text='sms_failure'}&quot;{literal}}{/literal}
					);'">
</div>
