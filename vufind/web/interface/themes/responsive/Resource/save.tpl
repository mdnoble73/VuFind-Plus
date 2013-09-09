<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal">×</button>
	<h3 id="modal-title">{translate text='add_favorite_prefix'} {$record->title|escape:"html"} {translate text='add_favorite_suffix'}</h3>
</div>
<div class="modal-body">
	<form class="form-horizontal">
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
			  <label for="selectList" class="control-label">{translate text='Choose a List'}</label>
			  <div class="controls">
				  <select name="list" id="selectList">
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
		    <label for="mytags" class="control-label">{translate text='Add Tags'}</label>
				<div class="controls">
					<input type="text" name="mytags" id="mytags" value="" size="50" maxlength="255" class="input-xxlarge">
					<span class="help-block">{translate text='add_tag_note'}</span>
				</div>
			</div>

			<div class="control-group">
				<label for="mytags" class="control-label">{translate text='Add a Note'}</label>
				<div class="controls">
					<textarea name="notes" rows="3" cols="50" class="input-xxlarge"></textarea>
				</div>
			</div>

	  {/if}
	</form>
</div>
<div class="modal-footer">
	<button class="btn" data-dismiss="modal" id="modalClose">Close</button>
	<input type="submit" class="btn btn-primary" value="{translate text='Save'}"  onclick="saveRecord('{$id|escape}', '{$source|escape}', this, {literal}{{/literal}add: '{translate text='Add to favorites'}', error: '{translate text='add_favorite_fail'}'{literal}}{/literal}); return false;">
</div>