<div id="page-content" class="content">
  <div id="sidebar">
    {include file="MyResearch/menu.tpl"}
    
    {include file="Admin/menu.tpl"}
  </div>
  
  <div id="main-content">
    <h1>Search EPUB files</h1>
    <div id='searchOptions'>
      <form id='epubSearchOptions' action='{$path}/EContent/Search'>
        <div id='sortOptions'><label for='sortOptions'>Sort by:</label>
          <select name='sortOptions' id='sortOptions'>
            <option value="id" {if $sort=='id'}selected="selected"{/if}>Id</option>
            <option value="title" {if $sort=='filename'}selected="title"{/if}>Title</option>
            <option value="source" {if $sort=='record'}selected="source"{/if}>Source</option>
          </select> 
        </div>
        <input type='submit' value='Update Search' name='submit'/>
      </form>
    </div>
    <div id='resultsTable'>
      <div id='resultsTableHeader'>
        <div id='resultsTableHeaderRow'>
          <div class='resultsId resultsCell'>Id</div>
          <div class='resultsFilename resultsCell'>Information</div>
         <div class='resultsRecords resultsCell'>Source</div>
        </div>
      </div>
      <div id='resultsTableBody'>
        {foreach from=$results item=result}
          <div id='resultsTableBodyRow'>
            <div class='resultsId resultsCell'>{$result->id}</div>
            <div class='resultsFilename resultsCell'>
              <a href='{$path}/EcontentRecord/{$result->id}/Home'>{$result->title}</a>
              {if strlen($result->cover) > 0}<br />Cover: {$result->cover}{/if}
              <br />{if $result->accessType == 'acs'}{$result->acsId}{/if}
            </div>
            <div class='resultsRecords resultsCell'>{$result->source}</div>
          </div>
        {/foreach}
      </div>
    </div>
    
    {if $pageLinks.all}<div class="pagination">Page: {$pageLinks.all}</div>{/if}
  </div>
</div>