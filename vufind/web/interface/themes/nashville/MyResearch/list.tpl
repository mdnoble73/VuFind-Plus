<script type="text/javascript" src="{$path}/js/validate/jquery.validate.min.js"></script>
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
					<h3 id='listTitle'><span class="silk list">&nbsp;</span>{$favList->title|escape:"html"}</h3>
					{if $notes}
						<div id="listNotes">
						{foreach from=$notes item="note"}
							<div class="listNote">{$note}</div>
						{/foreach}
						</div>
					{/if}
					
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
							<button value="editList" id="FavEdit" class="button listButton" onclick='return editListAction()'>Edit List</button>
							<button value="batchAdd" id="FavBatchAdd" class="button listButton" onclick='return batchAddToListAction({$favList->id})'>Batch Add Titles</button>
							<button value="saveList" id="FavSave" class="button listButton" style="display:none" onclick='return updateListAction()'>Save Changes</button>
							{if $favList->public == 0}
								<button value="makePublic" id="FavPublic" class="button listButton" onclick='return makeListPublicAction()'>Make Public</button>
							{else}
								<button value="makePrivate" id="FavPrivate" class="button listButton" onclick='return makeListPrivateAction()'>Make Private</button>
								{if $user && ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin') || $user->hasRole('contentEditor'))}
									<a href="#" class="button listButton" id="FavCreateWidget" onclick="return createWidgetFromList('{$favList->id}')">Create Widget</a>
								{/if}
							{/if}
							<button value="deleteList" id="FavDelete" class="button listButton" onclick='return deleteListAction()'>Delete List</button>
						{/if}
						<button value="emailList" id="FavEmail" class="button listButton" onclick='return emailListAction("{$favList->id}")'>Email List</button>
						<button value="printList" id="FavPrint" class="button listButton" onclick='return printListAction();'>Print List</button>
						<button value="citeList" id="FavCite" class="button listButton" onclick='return citeListAction("{$favList->id}");'>Generate List Citations</button>
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
					{foreach from=$resourceList item=resource name="recordLoop"}
						<div class="result{if ($smarty.foreach.recordLoop.iteration % 2) == 0} alt{/if}">
							{* This is raw HTML -- do not escape it: *}
							{$resource}
						</div>
					{/foreach}
					
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
</div>

<script type="text/javascript">
$(document).ready(function() {literal} { {/literal}
	doGetStatusSummaries();
{literal} }); {/literal}
</script>
{/strip}