<script type="text/javascript" src="{$path}/js/lists.js"></script>
	<div id="sidebar-wrapper"><div id="sidebar">
		{include file="MyResearch/menu.tpl"}
	</div></div>
	<div id="main-content">
		<form action="{$url}/MyResearch/MyList/{$favList->id}" id="myListFormHead">
			<div>
				<input type="hidden" name="myListActionHead" id="myListActionHead"/>
				<h1 id='listTitle'>{$favList->title|escape:"html"}</h1>
				{if $favList->description}<div class="listDescription alignleft" id="listDescription">{$favList->description|escape}</div>{/if}
				{if $allowEdit}
					<div id='listEditControls' style="display:none">
						<label for='listTitleEdit'>Title: </label><br />
						<input type='text' id='listTitleEdit' name='newTitle' value="{$favList->title|escape:"html"}" maxlength="255" size="80"/><br />
						<label for='listDescriptionEdit'>Description: </label><br />
						<textarea name='newDescription' id='listDescriptionEdit' rows="3" cols="80">{$favList->description|escape:"html"}</textarea>
					</div>
				{/if}
				<div id='listTopButtons'>
					{if $allowEdit}
						<button value="editList" id="FavEdit" onclick='return editListAction()'>Edit List</button>
						<button value="batchAdd" id="FavBatchAdd" onclick='return batchAddToListAction({$favList->id})'>Batch Add Titles</button>
						<button value="saveList" id="FavSave" style="display:none" onclick='return updateListAction()'>Save Changes</button>
						{if $favList->public == 0}
							<button value="makePublic" id="FavPublic" onclick='return makeListPublicAction()'>Make Public</button>
						{else}
							<button value="makePrivate" id="FavPrivate" onclick='return makeListPrivateAction()'>Make Private</button>
						{/if}
						<button value="deleteList" id="FavDelete" onclick='return deleteListAction()'>Delete List</button>
					{/if}
					<button value="emailList" id="FavEmail" class="listButton" onclick='return emailListAction({$favList->id})'>Email List</button>
          <button value="printList" id="FavPrint" class="listButton" onclick='return printListAction();'>Print List</button>
				</div>
			</div>
		</form>
		
		{if $resourceList}
		<div id="searchInfo">
			{if $recordCount}
				{translate text="Showing"}
				<b>{$recordStart}</b> - <b>{$recordEnd}</b>
				{translate text='of'} <b>{$recordCount}</b>
			{/if}

		</div>
		<form action="{$url}/MyResearch/MyList/{$favList->id}" id="myListFormItem">
			<div>
				<input type="hidden" name="myListActionItem" id="myListActionItem"/>
				{foreach from=$resourceList item=resource name="recordLoop"}
				<div class="result{if ($smarty.foreach.recordLoop.iteration % 2) == 0} alt{/if}">
					{* This is raw HTML -- do not escape it: *}
					{$resource}
				</div>
				{/foreach}
				<div class="list-actions">
					<button value="placeHolds" id="FavPlaceHolds" onclick='return requestMarkedAction()'>Request Marked</button>
					{if $allowEdit}
					<button value="deleteMarked" id="FavDeleteMarked" onclick='return deletedMarkedAction()'>Delete Marked</button>
					<button value="deleteAll" id="FavDeleteAll" onclick='return deleteAllAction()'>Delete All</button>
					{/if}
				</div>
			</div>
		</form>

		{if $pageLinks.all}<div class="pagination">{$pageLinks.all}</div>{/if}
		{else}
		{translate text='You do not have any saved resources'}
		{/if}
	</div>

	<div class="yui-u">
		{if $tagList}
		<h3 class="tag">{translate text='Your Tags'}</h3>

		<ul>
		{foreach from=$tags item=tag}
			<li>{translate text='Tag'}: {$tag|escape:"html"}
			<a href="{$url}/MyResearch/MyList/{$favList->id}&amp;{foreach from=$tags item=mytag}{if $tag != $mytag}tag[]={$mytag|escape:"url"}&amp;{/if}{/foreach}">X</a>
			</li>
		{/foreach}
		</ul>

		<ul>
		{foreach from=$tagList item=tag}
			<li>
				<a href="{$url}/MyResearch/MyList/{$favList->id}&amp;tag[]={$tag->tag|escape:"url"}{foreach from=$tags item=mytag}&amp;tag[]={$mytag|escape:"url"}{/foreach}">{$tag->tag|escape:"html"}</a> ({$tag->cnt})
			</li>
		{/foreach}
		</ul>
		{/if}
	</div>
<script type="text/javascript">
$(document).ready(function() {literal} { {/literal}
	doGetStatusSummaries();
	doGetRatings();
{literal} }); {/literal}
</script>
