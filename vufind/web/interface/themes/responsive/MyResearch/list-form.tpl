<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal">×</button>
	<h3 id="modal-title">{translate text='Create a new List'}</h3>
</div>
<div class="modal-body">
	{if $listError}<p class="error">{$listError|translate}</p>{/if}
	<form method="post" action="{$path}/MyResearch/ListEdit" name="listForm"
	      class="form-horizontal">
		<div class="control-group">
			<label for="listTitle" class="control-label">{translate text="List"}:</label>
			<div class="controls">
				<input type="text" id="listTitle" name="title" value="{$list->title|escape:"html"}" size="50"><br />
			</div>
		</div>
		<div class="control-group">
		  <label for="listDesc" class="control-label">{translate text="Description"}:</label>
			<div class="controls">
		    <textarea name="desc" id="listDesc" rows="3" cols="50" class="input-xxlarge">{$list->desc|escape:"html"}</textarea><br />
			</div>
		</div>
		<div class="control-group">
			<label for="public" class="control-label">{translate text="Access"}:</label>
			<div class="controls">
				<div class="switch" id="public-switch" data-on-label="Public" data-off-label="Private">
					<input type='checkbox' name='public' id='public'/>
				</div>
			</div>
		</div>
	  <input type="hidden" name="recordId" value="{$recordId}">
	  <input type="hidden" name="source" value="{$source}">
	  <input type="hidden" name="followupModule" value="{$followupModule}">
	  <input type="hidden" name="followupAction" value="{$followupAction}">
	  <input type="hidden" name="followupId" value="{$followupId}">
	  <input type="hidden" name="followupText" value="{translate text='Add to Favorites'}">
	</form>
</div>
<div class="modal-footer">
	<button class="btn" data-dismiss="modal" id="modalClose">Close</button>
	<input type="submit" class="btn btn-primary" value="{translate text='Save'}"  onclick="return addList(this, &quot;{translate text='add_list_fail'}&quot;);">
</div>
<script type="text/javascript">
	$('#public-switch').bootstrapSwitch();
</script>