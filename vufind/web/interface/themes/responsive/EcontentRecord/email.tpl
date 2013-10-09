<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal">×</button>
	<h3 id="modal-title">{translate text='Email Title'}</h3>
</div>
<div class="modal-body">
	{if $message}<div class="error">{$message|translate}</div>{/if}

	<form action="{$path}/EContentRecord/{$id|escape:"url"}/Email" method="post" id="emailForm" name="emailForm" class="form-horizontal">
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
	<input type="submit" class="btn btn-primary" value="{translate text='Save'}"  onclick="VuFind.Record.sendEmail('{$id|escape}', 'eContent'); return false;">
</div>