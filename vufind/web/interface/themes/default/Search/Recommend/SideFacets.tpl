{strip}
{if $recordCount > 0 || $filterList || ($sideFacetSet && $recordCount > 0)}
<div class="sidegroup">
	<h4>{translate text='Narrow Search'}</h4>
	{if isset($checkboxFilters) && count($checkboxFilters) > 0}
	<p>
		{* Checkbox filters*}
		<table>
			{foreach from=$checkboxFilters item=current}
				<tr{if $recordCount < 1 && !$current.selected} style="display: none;"{/if}>
					<td style="vertical-align:top; padding: 3px;">
						<input type="checkbox" name="filter[]" value="{$current.filter|escape}"
							{if $current.selected}checked="checked"{/if}
							onclick="document.location.href='{$current.toggleUrl|escape}';" />
					</td>
					<td>
						{translate text=$current.desc}<br />
					</td>
				</tr>
			{/foreach}
		</table>
	</p>
	{/if}
	{* Filters that have been applied *}
	{if $filterList}
		<strong>{translate text='Remove Filters'}</strong>
		<ul class="filters">
		{foreach from=$filterList item=filters key=field }
			{foreach from=$filters item=filter}
				<li>{translate text=$field}: {$filter.display|translate|escape} <a href="{$filter.removalUrl|escape}" onclick="trackEvent('Remove Facet', '{$field}', '{$filter.display|escape}');"><img src="{$path}/images/silk/delete.png" alt="Delete"/></a></li>
			{/foreach}
		{/foreach}
		</ul>
	{/if}
	
	{* Available filters *}
	{if $sideFacetSet && $recordCount > 0}
		{foreach from=$sideFacetSet item=cluster key=title name=facetSet}
			{if count($cluster.list) > 0}
			<div class="facetList">
				<div class="facetTitle {if $cluster.collapseByDefault}collapsed{else}expanded{/if}" onclick="$(this).toggleClass('expanded');$(this).toggleClass('collapsed');$('#facetDetails_{$title}').toggle()">{translate text=$cluster.label}</div>
				<div id="facetDetails_{$title}" class="facetDetails" {if $cluster.collapseByDefault}style="display:none"{/if}>
				
					{if $title == 'publishDate' || $title == 'birthYear' || $title == 'deathYear'}
						<form id='{$title}Filter' action='{$fullPath}'>
						<div>
							<label for="{$title}yearfrom" class='yearboxlabel'>From:</label>
							<input type="text" size="4" maxlength="4" class="yearbox" name="{$title}yearfrom" id="{$title}yearfrom" value="" />
							<label for="{$title}yearto" class='yearboxlabel'>To:</label>
							<input type="text" size="4" maxlength="4" class="yearbox" name="{$title}yearto" id="{$title}yearto" value="" />
							{* To make sure that applying this filter does not remove existing filters we need to copy the get variables as hidden variables *}
							{foreach from=$smarty.get item=parmValue key=paramName}
								{if is_array($smarty.get.$paramName)}
									{foreach from=$smarty.get.$paramName item=parmValue2}
										{* Do not include the filter that this form is for. *}
										{if strpos($parmValue2, $title) === FALSE}
											<input type="hidden" name="{$paramName}[]" value="{$parmValue2|escape}" />
										{/if}
									{/foreach}
								{else}
									<input type="hidden" name="{$paramName}" value="{$parmValue|escape}" />
								{/if}
							{/foreach}
							<input type="submit" value="Go" class="goButton" />
							<br/>
							{if $title == 'publishDate'}
								<div id='yearDefaultLinks'>
								<a onclick="$('#{$title}yearfrom').val('2010');$('#{$title}yearto').val('');" href='javascript:void(0);'>since&nbsp;2010</a>
								&bull;<a onclick="$('#{$title}yearfrom').val('2005');$('#{$title}yearto').val('');" href='javascript:void(0);'>since&nbsp;2005</a>
								&bull;<a onclick="$('#{$title}yearfrom').val('2000');$('#{$title}yearto').val('');" href='javascript:void(0);'>since&nbsp;2000</a>
								</div>
							{/if}
							</div>
						</form>
					{elseif $title == 'rating_facet'}
						{foreach from=$ratingLabels item=curLabel}
							{assign var=thisFacet value=$cluster.list.$curLabel}
							{if $thisFacet.isApplied}
								{if $curLabel == 'Unrated'}
									<div class="facetValue">{$thisFacet.value|escape} <img src="{$path}/images/silk/tick.png" alt="Selected" onclick="trackEvent('Remove Facet', '{$cluster.label}', '{$curLabel|translate}');"/> <a href="{$thisFacet.removalUrl|escape}" class="removeFacetLink">(remove)</a></div>
								{else}
									<div class="facetValue"><img src="{$path}/images/{$curLabel}.png" alt="{$curLabel|translate} &amp; Up" title="{$curLabel|translate} &amp; up" onclick="trackEvent('Remove Facet', '{$curLabel|translate}', '{$curLabel|translate}');"/> <img src="{$path}/images/silk/tick.png" alt="Selected" /> <a href="{$thisFacet.removalUrl|escape}" class="removeFacetLink">(remove)</a></div>
								{/if}
							{else}
								{if $curLabel == 'Unrated'}
									<div class="facetValue">{if $thisFacet.url !=null}<a href="{$thisFacet.url|escape}">{/if}{$thisFacet.display|escape}{if $thisFacet.url !=null}</a>{/if} ({$thisFacet.count})</div>
								{else}
									<div class="facetValue">{if $thisFacet.url !=null}<a href="{$thisFacet.url|escape}">{/if}<img src="{$path}/images/{$curLabel}.png" alt="{$curLabel|translate} &amp; Up" title="{$curLabel|translate} &amp; Up"/>{if $thisFacet.url !=null}</a>{/if} ({if $thisFacet.count}{$thisFacet.count}{else}0{/if})</div>
								{/if}
							{/if}
						{/foreach}
					{elseif $title == 'lexile_score' || $title == 'accelerated_reader_reading_level' || $title == 'accelerated_reader_point_value'}
						<form id='{$title}Filter' action='{$fullPath}'>
							<div>
								{if $title == 'lexile_score'}
									<div id="lexile-range"></div>
								{/if}
								<label for="{$title}from" class='yearboxlabel'>From:</label>
								<input type="text" size="4" maxlength="4" class="yearbox" name="{$title}from" id="{$title}from" value="" />
								<label for="{$title}to" class='yearboxlabel'>To:</label>
								<input type="text" size="4" maxlength="4" class="yearbox" name="{$title}to" id="{$title}to" value="" />
								{* To make sure that applying this filter does not remove existing filters we need to copy the get variables as hidden variables *}
								{foreach from=$smarty.get item=parmValue key=paramName}
									{if is_array($smarty.get.$paramName)}
										{foreach from=$smarty.get.$paramName item=parmValue2}
											{* Do not include the filter that this form is for. *}
											{if strpos($parmValue2, $title) === FALSE}
												<input type="hidden" name="{$paramName}[]" value="{$parmValue2|escape}" />
											{/if}
										{/foreach}
									{else}
										<input type="hidden" name="{$paramName}" value="{$parmValue|escape}" />
									{/if}
								{/foreach}
								<input type="submit" value="Go" id="goButton" />
								{if $title == 'lexile_score'}
								<script type="text/javascript">{literal}
									$(function() {
										$( "#lexile-range" ).slider({
											range: true,
											min: 0,
											max: 2500,
											step: 10,
											values: [ 0, 2500 ],
											slide: function( event, ui ) {
												$( "#lexile_scorefrom" ).val( ui.values[ 0 ] );
												$( "#lexile_scoreto" ).val( ui.values[ 1 ] );
											}
										});
										$( "#lexile_scorefrom" ).change(function (){
											$( "#lexile-range" ).slider( "values", 0, $( "#lexile_scorefrom" ).val());
										});
										$( "#lexile_scoreto" ).change(function (){
											$( "#lexile-range" ).slider( "values", 1, $( "#lexile_scoreto" ).val());
										});
									});{/literal}
									</script>
									{/if}
							</div>
						</form>
					{elseif $cluster.showAsDropDown}
						<select class="facetDropDown" onchange="changeDropDownFacet('facetDropDown-{$title}', '{$cluster.label}')" id="facetDropDown-{$title}">
							<option selected="selected">Choose {$cluster.label}</option>
							{foreach from=$cluster.list item=thisFacet name="narrowLoop"}
								<option data-destination="{$thisFacet.url}" data-label="{$thisFacet.display|escape}">{$thisFacet.display|escape}{if $thisFacet.count != ''}&nbsp;({$thisFacet.count}){/if}</option>
							{/foreach}
						</select>
					{else}
						{if $cluster.showMoreFacetPopup}
							{foreach from=$cluster.list item=thisFacet name="narrowLoop"}
								{if $thisFacet.isApplied}
									<div class="facetValue">{$thisFacet.display|escape} <img src="{$path}/images/silk/tick.png" alt="Selected" /> <a href="{$thisFacet.removalUrl|escape}" class="removeFacetLink" onclick="trackEvent('Remove Facet', '{$cluster.label}', '{$thisFacet.display|escape}');">(remove)</a></div>
								{else}
									<div class="facetValue">{if $thisFacet.url !=null}<a href="{$thisFacet.url|escape}">{/if}{$thisFacet.display|escape}{if $thisFacet.url !=null}</a>{/if}{if $thisFacet.count != ''}&nbsp;({$thisFacet.count}){/if}</div>
								{/if}
							{/foreach}
							{* Show more list *}
							<div class="facetValue" id="more{$title}"><a href="#" onclick="moreFacetPopup('More {$cluster.label}s', '{$title}'); return false;">{translate text='more'} ...</a></div>
							<div id="moreFacetPopup_{$title}" style="display:none">
								<p>Please select one of the items below to narrow your search by {$cluster.label}.</p>
								{foreach from=$cluster.sortedList item=thisFacet name="narrowLoop"}
									{if $smarty.foreach.narrowLoop.iteration % ($smarty.foreach.narrowLoop.total / 5) == 1}
										{if !$smarty.foreach.narrowLoop.first}
											</ul></div>
										{/if}
										<div class="facetCol"><ul>
									{/if}
									<li class="facetValue">{if $thisFacet.url !=null}<a href="{$thisFacet.url|escape}">{/if}{$thisFacet.display|escape}{if $thisFacet.url !=null}</a>{/if}{if $thisFacet.count != ''}&nbsp;({$thisFacet.count}){/if}</li>
									{if $smarty.foreach.narrowLoop.last}
										</ul></div>
									{/if}
								{/foreach}
								
							</div>
						{else}
							{foreach from=$cluster.list item=thisFacet name="narrowLoop"}
								{if $smarty.foreach.narrowLoop.iteration == ($cluster.valuesToShow + 1)}
									{* Show More link*}
									<div class="facetValue" id="more{$title}"><a href="#" onclick="moreFacets('{$title}'); return false;">{translate text='more'} ...</a></div>
									{* Start div for hidden content*}
									<div class="narrowGroupHidden" id="narrowGroupHidden_{$title}">
								{/if}
								{if $thisFacet.isApplied}
									<div class="facetValue">{$thisFacet.display|escape} <img src="{$path}/images/silk/tick.png" alt="Selected" /> <a href="{$thisFacet.removalUrl|escape}" class="removeFacetLink" onclick="trackEvent('Remove Facet', '{$cluster.label}', '{$thisFacet.display|escape}');">(remove)</a></div>
								{else}
									<div class="facetValue">{if $thisFacet.url !=null}<a href="{$thisFacet.url|escape}">{/if}{$thisFacet.display|escape}{if $thisFacet.url !=null}</a>{/if}{if $thisFacet.count != ''}&nbsp;({$thisFacet.count}){/if}</div>
								{/if}
							{/foreach}
							{if $smarty.foreach.narrowLoop.total > $cluster.valuesToShow}
								<div class="facetValue"><a href="#" onclick="lessFacets('{$title}'); return false;">{translate text='less'} ...</a></div>
								</div>
							{/if}
						{/if}
					{/if}
				</div>
			</div>
			
			{* Add a line between facets for clarity*}
			{if !$smarty.foreach.facetSet.last}
			<hr class="facetSeparator"/>
			{/if}
			{/if}
		{/foreach}
	{/if}
</div>
{/if}
{/strip}