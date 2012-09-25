{strip}
<div id="page-content" class="content">
	<div id="sidebar">
		{include file="MyResearch/menu.tpl"}
			
		{include file="Admin/menu.tpl"}
	</div>
	
	<div id="main-content">
		{if $allowEdit}
			<form action="{$path}/MyResearch/MyList/{$favList->id}" id="myListFormHead">
				<div>
					<input type="hidden" name="myListActionHead" id="myListActionHead"/>
		{/if}
					<h3 class="list" id='listTitle'>{$favList->title|escape:"html"}</h3>
					{if $favList->description}<div class="listDescription alignleft" id="listDescription">{$favList->description|escape}</div>{/if}
					{if $allowEdit}
						<div id='listEditControls' style="display:none">
							<label for='listTitleEdit'>Title: </label><br />
							<input type='text' id='listTitleEdit' name='newTitle' value="{$favList->title|escape:"html"}" maxlength="255" size="80"/><br />
							<label for='listDescriptionEdit'>Description: </label><br />
							<textarea name='newDescription' id='listDescriptionEdit' rows="3" cols="80">{$favList->description|escape:"html"}</textarea>
						</div>
					{/if}
					<div class="clearer"></div>
					<div id='listTopButtons'>
						{if $allowEdit}
							<button value="editList" id="FavEdit" class="listButton" onclick='return editListAction()'>Edit List</button>
							<button value="batchAdd" id="FavBatchAdd" onclick='return batchAddToListAction({$favList->id})'>Batch Add Titles</button>
							<button value="saveList" id="FavSave" class="listButton" style="display:none" onclick='return updateListAction()'>Save Changes</button>
							{if $favList->public == 0}
								<button value="makePublic" id="FavPublic" class="listButton" onclick='return makeListPublicAction()'>Make Public</button>
							{else}
								<button value="makePrivate" id="FavPrivate" class="listButton" onclick='return makeListPrivateAction()'>Make Private</button>
							{/if}
							<button value="deleteList" id="FavDelete" class="listButton" onclick='return deleteListAction()'>Delete List</button>
						{/if}
						<button value="emailList" id="FavEmail" class="listButton" onclick='return emailListAction("{$favList->id}")'>Email List</button>
						<button value="printList" id="FavPrint" class="listButton" onclick='return printListAction();'>Print List</button>
						<button value="citeList" id="FavCite" class="listButton" onclick='return citeListAction("{$favList->id}");'>Generate List Citations</button>
					</div>
		{if $allowEdit}
				</div>
			</form>
		{/if}
		{if $resourceList}
			<div class="resulthead">
				<div >
				{if $recordCount}
					{translate text="Showing"}
					<b>{$recordStart}</b> - <b>{$recordEnd}</b> {translate text='of'} <b>{$recordCount}</b>
				{/if}
				</div>
			</div>
			
			<form action="{$path}/MyResearch/MyList/{$favList->id}" id="myListFormItem">
				<div>
					<input type="hidden" name="myListActionItem" id="myListActionItem"/>
					<ul>
					{foreach from=$resourceList item=resource name="recordLoop"}
						<li class="result{if ($smarty.foreach.recordLoop.iteration % 2) == 0} alt{/if}">
							{* This is raw HTML -- do not escape it: *}
							{$resource}
						</li>
					{/foreach}
					</ul>
					<button value="placeHolds" id="FavPlaceHolds" class="listButton" onclick='return requestMarkedAction()'>Request Marked</button>
					{if $allowEdit}
					{*
					<button value="moveMarked" id="FavMoveMarked" class="listButton" onclick='return moveMarkedAction()'>Move Marked</button>
					*}
					<button value="deleteMarked" id="FavDeleteMarked" class="listButton" onclick='return deletedMarkedListItemsAction()'>Delete Marked</button>
					<button value="deleteAll" id="FavDeleteAll" class="listButton" onclick='return deleteAllListItemsAction()'>Delete All</button>
					{*
					<div id='listToMoveToPanel' style='display:none'>
					<button value="moveMarked" id="FavMoveMarked" class="listButton" onclick='return moveMarkedGo()'>Move Marked</button>
					</div>
					*}
					{/if}
				</div>
			</form>
		
			{if strlen($pageLinks.all) > 0}<div class="pagination">{$pageLinks.all}</div>{/if}
		{else}
			{translate text='You do not have any saved resources'}
		{/if}
	</div>

	{if $tagList}
		<div>
			<h3 class="tag">{translate text='Your Tags'}</h3>
			<ul>
			{foreach from=$tags item=tag}
				<li>{translate text='Tag'}: {$tag|escape:"html"}
				<a href="{$path}/MyResearch/MyList/{$favList->id}&amp;{foreach from=$tags item=mytag}{if $tag != $mytag}tag[]={$mytag|escape:"url"}&amp;{/if}{/foreach}">X</a>
				</li>
			{/foreach}
			</ul>
			
			<ul>
			{foreach from=$tagList item=tag}
				<li>
					<a href="{$path}/MyResearch/MyList/{$favList->id}&amp;tag[]={$tag->tag|escape:"url"}{foreach from=$tags item=mytag}&amp;tag[]={$mytag|escape:"url"}{/foreach}">{$tag->tag|escape:"html"}</a> ({$tag->cnt})
				</li>
			{/foreach}
			</ul>
		</div>
	{/if}
</div>

<script type="text/javascript">
$(document).ready(function() {literal} { {/literal}
	doGetStatusSummaries();
	doGetRatings();
{literal} }); {/literal}
</script>
{/strip}