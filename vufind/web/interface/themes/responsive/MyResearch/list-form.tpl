{strip}
<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal">×</button>
	<h3 id="modal-title">{translate text='Create a new List'}</h3>
</div>
<div class="modal-body">
	{if $listError}<p class="error">{$listError|translate}</p>{/if}
	<form method="post" action="{$path}/MyResearch/ListEdit" name="listForm"
	      class="form-horizontal" id="addListForm">
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
	</form>
</div>
<div class="modal-footer">
	<button class="btn" data-dismiss="modal" id="modalClose">Close</button>
	<input type="submit" class="btn btn-primary" value="{translate text='Save'}"  onclick="return VuFind.Account.addList();">
</div>
{/strip}
<script type="text/javascript">{literal}
	$(document).ready(function(){
		var publicSwitch = $('#public-switch');
		if (!publicSwitch.hasClass("has-switch")){
			publicSwitch['bootstrapSwitch']();
		}
	});
{/literal}</script>