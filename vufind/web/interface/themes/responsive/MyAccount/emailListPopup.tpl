<div align="left">
	{if $message}<div class="error">{$message|translate}</div>{/if}

	<form id="emailListForm" action="{$path}/MyAccount/EmailList" method="post"
		class="form form-horizontal"
		onSubmit='VuFind.Lists.SendMyListEmail(this.elements[&quot;to&quot;].value,
		this.elements[&quot;from&quot;].value, this.elements[&quot;message&quot;].value,this.elements[&quot;listId&quot;].value
		{* Pass translated strings to Javascript -- ugly but necessary: *}
		{ldelim}sending: &quot;{translate text='email_sending'}&quot;,
		 success: &quot;{translate text='email_success'}&quot;,
		 failure: &quot;{translate text='email_failure'}&quot;{rdelim}
		); return false;'>
		<div class="form-group">
			<input type="hidden" name="listId" value="{$listId|escape}">
			<label for="to" class="control-label col-xs-2">{translate text='To'}</label>
			<div class="col-xs-10">
				<input type="text" name="to" id="to" size="40" class="required email form-control">
			</div>
		</div>
		<div class="form-group">
			<label for="from" class="control-label col-xs-2">{translate text='From'}</label>
			<div class="col-xs-10">
				<input type="text" name="from" id="from" size="40" class="required email form-control">
			</div>
		</div>
		<div class="form-group">
			<label for="message" class="control-label col-xs-2">{translate text='Message'}</label>
			<div class="col-xs-10">
				<textarea name="message" id="message" rows="3" cols="40" class="form-control"></textarea>
			</div>
		</div>
	</form>
</div>
<script type="text/javascript">
	$("#emailListForm").validate();
</script>