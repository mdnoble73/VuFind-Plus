<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal">×</button>
	<h3 id="modal-title">{translate text='add_favorite_prefix'} {$record->title|escape:"html"} {translate text='add_favorite_suffix'}</h3>
</div>
<div class="modal-body">
	<form class="form-horizontal" id="save-to-list-form">
		<input type="hidden" name="submit" value="1" />
		<input type="hidden" name="record_id" value="{$id|escape}" />
		<input type="hidden" name="source" value="{$source|escape}" />
		{if !empty($containingLists)}
		  <p>
		  {translate text='This item is already part of the following list/lists'}:<br />
		  {foreach from=$containingLists item="list"}
		    <a href="{$path}/MyResearch/MyList/{$list.id}">{$list.title|escape:"html"}</a><br />
		  {/foreach}
		  </p>
		{/if}

		{* Only display the list drop-down if the user has lists that do not contain
		 this item OR if they have no lists at all and need to create a default list *}
		{if (!empty($nonContainingLists) || (empty($containingLists) && empty($nonContainingLists))) }
		  {assign var="showLists" value="true"}
		{/if}

	  {if $showLists}
		  <div class="control-group">
			  <label for="addToList-list" class="control-label">{translate text='Choose a List'}</label>
			  <div class="controls">
				  <select name="list" id="addToList-list">
					  {foreach from=$nonContainingLists item="list"}
						  <option value="{$list.id}">{$list.title|escape:"html"}</option>
						  {foreachelse}
						  <option value="">{translate text='My Favorites'}</option>
					  {/foreach}
				  </select>
				  &nbsp;or&nbsp;
				  <a class="btn" href="{$path}/MyResearch/ListEdit?id={$id|escape:"url"}&amp;source={$source|escape}&lightbox"
				     onclick="return VuFind.ajaxLightbox('{$url}/MyResearch/ListEdit?id={$id|escape}&source={$source|escape}&lightbox');">{translate text="Create a New List"}</a>
			  </div>
			</div>
		{else}
		  <a class="btn" href="{$path}/MyResearch/ListEdit?id={$id|escape:"url"}&amp;source={$source|escape}"
		     onclick="return VuFind.ajaxLightbox('{$url}/MyResearch/ListEdit?id={$id|escape}&source={$source|escape}&lightbox');">{translate text="Create a New List"}</a>
	  {/if}

	  {if $showLists}
			<div class="control-group">
				<label for="addToList-notes" class="control-label">{translate text='Add a Note'}</label>
				<div class="controls">
					<textarea name="notes" rows="3" cols="50" class="input-xxlarge" id="addToList-notes"></textarea>
				</div>
			</div>

	  {/if}
	</form>
</div>
<div class="modal-footer">
	<button class="btn" data-dismiss="modal" id="modalClose">Close</button>
	<input id="saveToList-button" type="submit" class="btn btn-primary" value="{translate text='Save'}"  onclick="VuFind.Record.saveToList('{$id|escape}', '{$source|escape}', $('#save-to-list-form')); return false;">
</div>