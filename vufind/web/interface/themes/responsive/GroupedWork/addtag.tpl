<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal">×</button>
	<h3 id="modal-title">{translate text='Add Tag'}</h3>
</div>
<div class="modal-body">
	<form class="form-horizontal" id="save-tag-form">
	<form onSubmit='SaveTag(&quot;{$id|escape}&quot;, &quot;{$source|escape}&quot;, this,
			{literal}{{/literal}success: &quot;{translate text='add_tag_success'}&quot;, load_error: &quot;{translate text='load_tag_error'}&quot;, save_error: &quot;{translate text='add_tag_error'}&quot;{literal}}{/literal}
			); return false;' method="POST">
	<input type="hidden" name="submit" value="1" />
		<div class="form-group">
			<label for="tags_to_apply" class="control-label">{translate text="Tags"}: </label>
			<div class="controls">
				<input type="text" name="tag" value="" size="50" id="tags_to_apply">
				<span class="help-block">{translate text="add_tag_note"}</span>
			</div>
		</div>
	</form>
</div>
<div class="modal-footer">
	<button class="btn" data-dismiss="modal" id="modalClose">Close</button>
	<input id="saveTag-button" type="submit" class="btn btn-primary" value="{translate text='Save'}"  onclick="VuFind.Record.saveTag('{$id|escape}', '{$source|escape}', $('#save-tag-form')); return false;">
</div>