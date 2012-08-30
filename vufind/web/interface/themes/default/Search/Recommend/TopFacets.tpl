    {if $topFacetSet}
      {foreach from=$topFacetSet item=cluster key=title}
        {if $cluster.label == 'Category'}
            {if ($categorySelected == false)}
	            <div id="formatCategories">
	            <div id='categoryValues'>
	            {foreach from=$cluster.list item=thisFacet name="narrowLoop"}
	                {* Do not show Other facet in top facets*}
		                {if $thisFacet.value != 'Other'}
				        {if $thisFacet.isApplied}
				        <span class='categoryValue categoryValue{translate text=$thisFacet.value|escape}'><img class='categoryIcon' src='{$path}/interface/themes/default/images/{$thisFacet.imageName|escape}.png' alt='{$thisFacet.value|escape}'/> <a href="{$thisFacet.removalUrl|escape}" class="removeFacetLink">(remove)</a></span>
				        {else}
				        <a href="{$thisFacet.url|escape}"><span class='categoryValue categoryValue{$thisFacet.value|escape}' ><img class='categoryIcon' src='{$path}/interface/themes/default/images/{$thisFacet.imageName|escape}.png' alt='{$thisFacet.value|escape}'/>({$thisFacet.count})</span></a>
				        {/if}
			        {/if}
		        {/foreach}
	            </div>
	            </div>
            {/if}
        {else}
  <div class="authorbox">
  <table class="facetsTop navmenu narrow_begin">
    <tr><th colspan="{$topFacetSettings.cols}">{translate text=$cluster.label}<span>{translate text="top_facet_suffix"}</span></th></tr>
        {foreach from=$cluster.list item=thisFacet name="narrowLoop"}
        {if $smarty.foreach.narrowLoop.iteration == ($topFacetSettings.rows * $topFacetSettings.cols) + 1}
    <tr id="more{$title}"><td><a href="#" onclick="moreFacets('{$title}'); return false;">{translate text='more'} ...</a></td></tr>
  </table>
  <table class="facetsTop navmenu narrowGroupHidden" id="narrowGroupHidden_{$title}">
    <tr><th colspan="{$topFacetSettings.cols}"><div class="top_facet_additional_text">{translate text="top_facet_additional_prefix"}{translate text=$cluster.label}<span>{translate text="top_facet_suffix"}</span></div></th></tr>
        {/if}
    {if $smarty.foreach.narrowLoop.iteration % $topFacetSettings.cols == 1}
    <tr>
    {/if}
        {if $thisFacet.isApplied}
        <td>{$thisFacet.value|escape}</a> <img src="/images/silk/tick.png" alt="Selected"> <a href="{$thisFacet.removalUrl|escape}" class="removeFacetLink">(remove)</a></td>
        {else}
        <td><a href="{$thisFacet.url|escape}">{$thisFacet.value|escape}</a> ({$thisFacet.count})</td>
        {/if}
    {if $smarty.foreach.narrowLoop.iteration % $topFacetSettings.cols == 0 || $smarty.foreach.narrowLoop.last}
    </tr>
    {/if}
        {if $smarty.foreach.narrowLoop.total > ($topFacetSettings.rows * $topFacetSettings.cols) && $smarty.foreach.narrowLoop.last}
    <tr><td><a href="#" onclick="lessFacets('{$title}'); return false;">{translate text='less'} ...</a></td></tr>
        {/if}
        {/foreach}
  </table>
  </div>
        {/if}
  {/foreach}
{/if}