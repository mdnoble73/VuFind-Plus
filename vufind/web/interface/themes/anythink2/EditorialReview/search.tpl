<div id="page-content" class="content">
  <div id="sidebar">
    {include file="MyResearch/menu.tpl"}
    
    {include file="Admin/menu.tpl"}
  </div>
  
  <div id="main-content">
    <h1>Search Editorial Reviews</h1>
    <div id='searchOptions'>
      <form id='editorialReviewSearchOptions' action='{$path}/EditorialReview/Search'>
        <div id='sortOptions'><label for='sortOptions'>Sort by:</label>
          <select name='sortOptions' id='sortOptions'>
	          <option value="editorialReviewId" {if $sort=='editorialReviewId'}selected="selected"{/if}>Id</option>
						<option value="source" {if $sort=='source'}selected="selected"{/if}>Source</option>
						<option value="title" {if $sort=='title'}selected="selected"{/if}>Title</option>
						<option value="pubDate" {if $sort=='pubDate'}selected="selected"{/if}>Date</option>
          </select> 
        </div>
        <input type='submit' value='Update Search' name='submit'/>
      </form>
    </div>
    
<table class="datatable">
<thead>
<tr><th>Id</th><th>Source</th><th>Title</th><th>Date</th></tr>

</thead>
<tbody>
{foreach from=$results item=result}
<tr>
<td>{$result->editorialReviewId}</td>
<td><a href='{$path}/EditorialReview/{$result->editorialReviewId}/View'>{$result->source}</a></td>
<td><a href='{$path}/EditorialReview/{$result->editorialReviewId}/View'>{$result->title}</a></td>
<td>{$result->formattedPubDate()}</td>

{/foreach}
</tbody>
</table>
    
    {if $pageLinks.all}<div class="pagination">Page: {$pageLinks.all}</div>{/if}
  </div>
</div>