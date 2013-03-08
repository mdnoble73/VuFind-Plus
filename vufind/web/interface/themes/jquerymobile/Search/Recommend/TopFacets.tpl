{strip}
{if $topFacetSet}
	{foreach from=$topFacetSet item=cluster key=title}
		{if $cluster.label == 'Category' || $cluster.label == 'Format Category'}
			{if ($categorySelected == false)}
				<div data-role="collapsible">
					<h4>{translate text="Pick a format"}</h4>
					<div class="formatCategories" id="formatCategories" data-role="controlgroup">
					{foreach from=$cluster.list item=thisFacet name="narrowLoop"}
						{if $thisFacet.isApplied}
							<span class='categoryValue categoryValue_{translate text=$thisFacet.value|lower|replace:' ':''}'>{$thisFacet.value|escape} <a href="{$thisFacet.removalUrl|escape}" class="removeFacetLink" onclick="trackEvent('Remove Facet', 'formatCategory', '{$thisFacet.value|escape}');">(remove filter)</a></span>
						{else}
							<a href="{$thisFacet.url|escape}" onclick="trackEvent('Apply Facet', 'formatCategory', '{$thisFacet.value|escape}');" data-role="button">{translate text=$thisFacet.value|escape} ({$thisFacet.count})</a>
						{/if}
					{/foreach}
					</div>
				</div>
			{/if}
		{elseif preg_match('/available/i', $cluster.label)}
			<div id="availabilityControl" data-role="controlgroup">
				{foreach from=$cluster.list item=thisFacet name="narrowLoop"}
					{if $thisFacet.isApplied}
						<input type="radio" id="{$thisFacet.value|escape|regex_replace:'/[()\s]/':''}" checked="checked" name="availabilityControls" /><label for="{$thisFacet.value|escape|regex_replace:'/[()\s]/':''}">{$thisFacet.value|escape} ({$thisFacet.count})</label>
					{else}
						<input type="radio" id="{$thisFacet.value|escape|regex_replace:'/[()\s]/':''}" name="availabilityControls" data-url="{$thisFacet.url|escape}" onclick="window.location = $(this).data('url')" /><label for="{$thisFacet.value|escape|regex_replace:'/[()\s]/':''}">{$thisFacet.value|escape} ({$thisFacet.count})</label>
					{/if}
				{/foreach}
			</div>
		{else}
			<div class="authorbox">
				<table class="facetsTop navmenu narrow_begin">
					<tr>
						<th colspan="{$topFacetSettings.cols}">
							{translate text=$cluster.label}<span>{translate text="top_facet_suffix"}</span>
						</th>
					</tr>
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
							<td>{$thisFacet.value|escape}</a> <img src="{$path}/images/silk/tick.png" alt="Selected" /> <a href="{$thisFacet.removalUrl|escape}" class="removeFacetLink" onclick="trackEvent('Remove Facet', '{$cluster.label}', '{$thisFacet.value|escape}');">(remove)</a></td>
						{else}
							<td><a href="{$thisFacet.url|escape}" onclick="trackEvent('Apply Facet', '{$cluster.label}', '{$thisFacet.value|escape}');">{$thisFacet.value|escape}</a> ({$thisFacet.count})</td>
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
{/strip}
