<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal">×</button>
	<h3 id="modal-title">{translate text='Email Title'}</h3>
</div>
<div class="modal-body">
	{if $message}<div class="error">{$message|translate}</div>{/if}

	<form action="{$path}/Record/{$id|escape:"url"}/Email" method="post" id="popupForm" name="popupForm" class="form-horizontal">
		<div class="control-group">
			<label for="to" class="control-label">{translate text='To'}:</label>
			<div class="controls">
				<input type="text" name="to" id="to" size="40" class="input-xxlarge"><br />
			</div>
		</div>
		<div class="control-group">
			<label for="from" class="control-label">{translate text='From'}:</label>
			<div class="controls">
				<input type="text" name="from" size="40" class="input-xxlarge"><br />
			</div>
		</div>
		<div class="control-group">
			<label for="message" class="control-label">{translate text='Message'}:</label>
			<div class="controls">
				<textarea name="message" rows="3" cols="40" class="input-xxlarge"></textarea><br />
			</div>
		</div>
	</form>
</div>
<div class="modal-footer">
	<button class="btn" data-dismiss="modal" id="modalClose">Close</button>
	<input type="submit" class="btn btn-primary" value="{translate text='Save'}"  onclick='SendEmail(&quot;{$id|escape}&quot;, this.elements[&quot;to&quot;].value,
					this.elements[&quot;from&quot;].value, this.elements[&quot;message&quot;].value,
	{* Pass translated strings to Javascript -- ugly but necessary: *}
	{literal}{{/literal}sending: &quot;{translate text='email_sending'}&quot;,
					success: &quot;{translate text='email_success'}&quot;,
					failure: &quot;{translate text='email_failure'}&quot;{literal}}{/literal}
					); return false;'>
</div>