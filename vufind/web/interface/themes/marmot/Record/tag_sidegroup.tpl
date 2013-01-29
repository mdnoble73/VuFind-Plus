{if $showTagging == 1}
	<div class="sidegroup" id="tagsSidegroup">
		<h4>{translate text="Tags"}</h4>
		<div id="tagList">
		{if $tagList}
			{foreach from=$tagList item=tag name=tagLoop}
				<div class="sidebarValue">
					<a href="{$path}/Search/Results?tag={$tag->tag|escape:"url"}">{$tag->tag|escape:"html"}</a> ({$tag->cnt})
					{if $tag->userAddedThis}
						<a href='{$path}/MyResearch/RemoveTag?tagId={$tag->id}&amp;resourceId={$id}' onclick='return confirm("Are you sure you want to remove the tag \"{$tag->tag|escape:"javascript"}\" from this title?");'>
							<span class="silk tag_blue_delete">&nbsp;</span>
						</a>
					{/if}
				</div> 
			{/foreach}
		{else}
			<div class="sidebarValue">{translate text='No Tags'}, {translate text='Be the first to tag this record'}!</div>
		{/if}
		</div>
		<div class="sidebarValue">
			<a href="{$path}/Resource/AddTag?id={$id|escape:"url"}&amp;source=VuFind" onclick="GetAddTagForm('{$id|escape}', 'VuFind'); return false;"><span class="silk add">&nbsp;</span>{translate text="Add Tag"}</a>
		</div>
	</div>
{/if}