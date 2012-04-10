<script type="text/javascript" src="{$path}/js/lists.js" /></script>

<div id="page-content" class="content">
	<div id="sidebar">
    {include file="MyResearch/menu.tpl"}
      
    {include file="Admin/menu.tpl"}
  </div>
	
  <div id="main-content">
          {if $allowEdit}
            <form name="myListFormHead" action="{$url}/MyResearch/MyList/{$favList->id}" id="myListFormHead">
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
          <button value="emailList" id="FavEmail" class="listButton" onclick='return emailListAction({$favList->id})'>Email List</button>
          {if $allowEdit}
	          <button value="editList" id="FavEdit" class="listButton" onclick='return editListAction()'>Edit List</button>
            <button value="saveList" id="FavSave" class="listButton" style="display:none" onclick='return updateListAction()'>Save Changes</button>
            {if $favList->public == 0}
	            <button value="makePublic" id="FavPublic" class="listButton" onclick='return makeListPublicAction()'>Make Public</button>
	          {else}
	            <button value="makePrivate" id="FavPrivate" class="listButton" onclick='return makeListPrivateAction()'>Make Private</button>
	          {/if}
	          <button value="deleteList" id="FavDelete" class="listButton" onclick='return deleteListAction()'>Delete List</button>
          {/if}
          </div>
          {if $allowEdit}
          </form>
          {/if}
	        {if $resourceList}
          <div class="resulthead">
            <div >
            {if $recordCount}
              {translate text="Showing"}
              <b>{$recordStart}</b> - <b>{$recordEnd}</b>
              {translate text='of'} <b>{$recordCount}</b>
            {/if}
            </div>
    
            <div class="toggle">
              {translate text='Sort'}
              <select id="sort" name="sort" onchange="document.location.href = this.options[this.selectedIndex].value;">
              {foreach from=$sortList item=sortData key=sortLabel}
                <option value="{$sortData.sortUrl|escape}"{if $sortData.selected} selected{/if}>{translate text=$sortData.desc}</option>
              {/foreach}
              </select>
            </div>
    
          </div>
          <form name="myListFormItem" action="{$url}/MyResearch/MyList/{$favList->id}" id="myListFormItem">
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
          <button value="deleteMarked" id="FavDeleteMarked" class="listButton" onclick='return deletedMarkedAction()'>Delete Marked</button>
          <button value="deleteAll" id="FavDeleteAll" class="listButton" onclick='return deleteAllAction()'>Delete All</button>
          {*
          <div id='listToMoveToPanel' style='display:none'>
          <button value="moveMarked" id="FavMoveMarked" class="listButton" onclick='return moveMarkedGo()'>Move Marked</button>
          </div>
          *}
          {/if}
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
      <!-- End of Internal Grid -->

</div>

<script type="text/javascript">
$(document).ready(function() {literal} { {/literal}
  doGetStatusSummaries();
  doGetRatings();
{literal} }); {/literal}
</script>

