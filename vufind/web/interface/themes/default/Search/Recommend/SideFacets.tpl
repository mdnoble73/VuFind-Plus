{if $recordCount > 0 || $filterList || ($sideFacetSet && $recordCount > 0)}
<div class="sidegroup">
	<h4>{translate text='Narrow Search'}</h4>
	{if isset($checkboxFilters) && count($checkboxFilters) > 0}
	<p>
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
	{if $sideFacetSet && $recordCount > 0}
		{foreach from=$sideFacetSet item=cluster key=title name=facetSet}
			{if $title == 'publishDate' || $title == 'birthYear' || $title == 'deathYear'}
				<dl class="narrowList navmenu narrow_begin">
					<dt>{translate text=$cluster.label}</dt>
					<dd>
						<form id='{$title}Filter' action='{$fullPath}' onsubmit="trackEvent('Apply Facet', '{$cluster.label}', '');">
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
								<a onclick="$('#{$title}yearfrom').val('2005');$('#{$title}yearto').val('');" href='javascript:void(0);'>since&nbsp;2005</a>
								&bull;<a onclick="$('#{$title}yearfrom').val('2000');$('#{$title}yearto').val('');" href='javascript:void(0);'>since&nbsp;2000</a>
								&bull;<a onclick="$('#{$title}yearfrom').val('1995');$('#{$title}yearto').val('');" href='javascript:void(0);'>since&nbsp;1995</a>
								</div>
							{/if}
							</div>
						</form>
					</dd>
				</dl>
			{elseif $title == 'rating_facet'}
				<dl class="narrowList navmenu narrow_begin">
					<dt>{translate text=$cluster.label}</dt>
					{foreach from=$ratingLabels item=curLabel}
						{assign var=thisFacet value=$cluster.list.$curLabel}
						{if $thisFacet.isApplied}
							{if $curLabel == 'Unrated'}
								<dd>{$thisFacet.value|escape} <img src="{$path}/images/silk/tick.png" alt="Selected" onclick="trackEvent('Remove Facet', '{$cluster.label}', '{$curLabel|translate}');"/> <a href="{$thisFacet.removalUrl|escape}" class="removeFacetLink">(remove)</a></dd>
							{else}
								<dd><img src="{$path}/images/{$curLabel}.png" alt="{$curLabel|translate} &amp; Up" title="{$curLabel|translate} &amp; up" onclick="trackEvent('Remove Facet', '{$curLabel|translate}', '{$curLabel|translate}');"/> <img src="{$path}/images/silk/tick.png" alt="Selected" /> <a href="{$thisFacet.removalUrl|escape}" class="removeFacetLink">(remove)</a></dd>
							{/if}
						{else}
							{if $curLabel == 'Unrated'}
								<dd>{if $thisFacet.url !=null}<a href="{$thisFacet.url|escape}" onclick="trackEvent('Apply Facet', '{$cluster.label}', '{$curLabel|translate}');">{/if}{$thisFacet.display|escape}{if $thisFacet.url !=null}</a>{/if} ({$thisFacet.count})</dd>
							{else}
								<dd>{if $thisFacet.url !=null}<a href="{$thisFacet.url|escape}" onclick="trackEvent('Apply Facet', '{$cluster.label}', '{$curLabel|translate}');">{/if}<img src="{$path}/images/{$curLabel}.png" alt="{$curLabel|translate} &amp; Up" title="{$curLabel|translate} &amp; Up"/>{if $thisFacet.url !=null}</a>{/if} ({if $thisFacet.count}{$thisFacet.count}{else}0{/if})</dd>
							{/if}
						{/if}
					{/foreach}
				</dl>
			{elseif $title == 'lexile_score' || $title == 'accelerated_reader_reading_level' || $title == 'accelerated_reader_point_value'}
				<dl class="narrowList navmenu narrowbegin">
					<dt>{translate text=$cluster.label}</dt>
					<dd>
						<form id='{$title}Filter' action='{$fullPath}' onsubmit="trackEvent('Apply Facet', '{$cluster.label}', '');">
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
					</dd>
				</dl>
			{else}
				<dl class="narrowList navmenu narrowbegin">
					<dt>{translate text=$cluster.label}</dt>
					{foreach from=$cluster.list item=thisFacet name="narrowLoop"}
						{if $smarty.foreach.narrowLoop.iteration == ($cluster.valuesToShow + 1)}
						<dd id="more{$title}"><a href="#" onclick="moreFacets('{$title}'); return false;">{translate text='more'} ...</a></dd>
					</dl>
					<dl class="narrowList navmenu narrowGroupHidden" id="narrowGroupHidden_{$title}">
						{/if}
						{if $thisFacet.isApplied}
							<dd>{$thisFacet.display|escape} <img src="{$path}/images/silk/tick.png" alt="Selected" /> <a href="{$thisFacet.removalUrl|escape}" class="removeFacetLink" onclick="trackEvent('Remove Facet', '{$cluster.label}', '{$thisFacet.display|escape}');">(remove)</a></dd>
						{else}
							<dd>{if $thisFacet.url !=null}<a href="{$thisFacet.url|escape}" onclick="trackEvent('Apply Facet', '{$cluster.label}', '{$thisFacet.display|escape}');">{/if}{$thisFacet.display|escape}{if $thisFacet.url !=null}</a>{/if}{if $thisFacet.count != ''}&nbsp;({$thisFacet.count}){/if}</dd>
						{/if}
					{/foreach}
					{if $smarty.foreach.narrowLoop.total > $cluster.valuesToShow}<dd><a href="#" onclick="lessFacets('{$title}'); return false;">{translate text='less'} ...</a></dd>{/if}
				</dl>
			{/if}
			{if !$smarty.foreach.facetSet.last}
			<hr class="facetSeparator"/>
			{/if}
		{/foreach}
	{/if}
</div>
{/if}