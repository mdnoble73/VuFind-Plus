{strip}
{if $allowEdit}
	<form action="{$path}/MyAccount/MyList/{$favList->id}" id="myListFormHead">
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
			<div id='listTopButtons' class="btn-group btn-group-sm">
				{if $allowEdit}
					<button value="editList" id="FavEdit" class="btn btn-sm btn-default" onclick='return VuFind.Lists.editListAction()'>Edit List</button>
					<button value="batchAdd" id="FavBatchAdd" class="btn btn-sm btn-default" onclick='return VuFind.Lists.batchAddToListAction({$favList->id})'>Batch Add Titles</button>
					<button value="saveList" id="FavSave" class="btn btn-sm btn-default" style="display:none" onclick='return VuFind.Lists.updateListAction()'>Save Changes</button>
					{if $favList->public == 0}
						<button value="makePublic" id="FavPublic" class="btn btn-sm btn-default" onclick='return VuFind.Lists.makeListPublicAction()'>Make Public</button>
					{else}
						<button value="makePrivate" id="FavPrivate" class="btn btn-sm btn-default" onclick='return VuFind.Lists.makeListPrivateAction()'>Make Private</button>
						{if $user && ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin') || $user->hasRole('contentEditor'))}
							&nbsp;&nbsp;<a href="#" class="button btn btn-sm btn-default" id="FavCreateWidget" onclick="return VuFind.Lists.createWidgetFromList('{$favList->id}')">Create Widget</a>
						{/if}
					{/if}
					<button value="deleteList" id="FavDelete" class="btn btn-sm btn-default" onclick='return VuFind.Lists.deleteListAction()'>Delete List</button>
				{/if}
				<button value="emailList" id="FavEmail" class="btn btn-sm btn-default" onclick='return VuFind.Lists.emailListAction("{$favList->id}")'>Email List</button>
				<button value="printList" id="FavPrint" class="btn btn-sm btn-default" onclick='return VuFind.Lists.printListAction();'>Print List</button>
				<button value="citeList" id="FavCite" class="btn btn-sm btn-default" onclick='return VuFind.Lists.citeListAction("{$favList->id}");'>Generate Citations</button>
			</div>
{if $allowEdit}
		</div>
	</form>
{/if}

{if $resourceList}
	<div class="resulthead">
		<div >
		{if $recordCount}
			{translate text="Showing"} <b>{$recordStart}</b> - <b>{$recordEnd}</b> {translate text='of'} <b>{$recordCount}</b>
		{/if}
		</div>
	</div>

	<div>
		<input type="hidden" name="myListActionItem" id="myListActionItem"/>
		{foreach from=$resourceList item=resource name="recordLoop"}
			<div class="result{if ($smarty.foreach.recordLoop.iteration % 2) == 0} alt{/if}">
				{* This is raw HTML -- do not escape it: *}
				{$resource}
			</div>
		{/foreach}
	</div>

	{if strlen($pageLinks.all) > 0}<div class="pagination">{$pageLinks.all}</div>{/if}
{else}
	{translate text='You do not have any saved resources'}
{/if}
{/strip}
