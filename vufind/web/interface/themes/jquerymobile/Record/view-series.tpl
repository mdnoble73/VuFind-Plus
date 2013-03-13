{* Main Listing *}
{if (isset($title)) }
<script type="text/javascript">
	alert("{$title}");
</script>
{/if}
<div data-role="page" id="Series-list" class="results-page">
	{include file="header.tpl"}
	
	<h3>Series Information</h3>
	{if $seriesTitle}
	<p><strong>Series Name: </strong>{$seriesTitle}</p>
	{/if}
	{if $seriesAuthors}
	<p><strong>Author(s):</strong>
		{foreach from=$seriesAuthors item=author}
			{$author}
		{/foreach}
	</p>
	{/if}
	{* Eventually, we will put the series title here*}
	
	<div id="searchInfo">
		{if $recordCount}
			{translate text="Showing"}
			<b>{$recordStart}</b> - <b>{$recordEnd}</b>
			{translate text='of'} <b>{$recordCount}</b>
		{else}
			<p>Sorry, we could not find series information for this title.</p>
		{/if}
	</div>
	{* End Listing Options *}
	
	{* Display series information *}
	<ul class="results" data-role="listview" data-split-icon="plus" data-split-theme="c">
		{foreach from=$resourceList item=resource name="recordLoop"}
			<li>
				{* This is raw HTML -- do not escape it: *}
				{$resource}
			</li>

		{/foreach}
	</ul>
	{if !$enableBookCart}
	<input type="submit" name="placeHolds" value="Request Selected Items" class="requestSelectedItems"/>
	{/if}
	
	<script type="text/javascript">
	$(document).ready(function() {literal} { {/literal}
		doGetStatusSummaries();
	{literal} }); {/literal}
	</script>
	
	{if $pageLinks.all}<div class="pagination">{$pageLinks.all}</div>{/if}
	</div>
	{include file="footer.tpl"}
</div>