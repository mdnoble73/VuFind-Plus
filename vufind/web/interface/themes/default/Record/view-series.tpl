{* Main Listing *}
{if (isset($title)) }
<script type="text/javascript">
	alert("{$title}");
</script>
{/if}
<div id="page-content" class="content">
	<div id="sidebar">
		{if $seriesTitle || $seriesAuthors}
		<div class="sidegroup">
			<h4>Series Information</h4>
			<div class="sidebarLabel">Series Name</div>
			<div class="sidebarValue">{$seriesTitle}</div>
			<div class="sidebarLabel">Author</div>
			{foreach from=$seriesAuthors item=author}
				<div class="sidebarValue">{$author}</div>
			{/foreach}
		</div>
		{/if}
	</div>
		
	{* Eventually, we will put the series title here*}
	
	<div id="main-content">
	{* Listing Options *}
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
		<form id="addForm" action="{$path}/MyResearch/HoldMultiple">
			<div id="seriesTitles">
				{foreach from=$resourceList item=resource name="recordLoop"}
					<div class="result{if ($smarty.foreach.recordLoop.iteration % 2) == 0} alt{/if}">
						{* This is raw HTML -- do not escape it: *}
						{$resource}
					</div>

				{/foreach}
				{if !$enableBookCart}
				<input type="submit" name="placeHolds" value="Request Selected Items" class="requestSelectedItems"/>
				{/if}
			</div>
		</form>
	
	<script type="text/javascript">
	$(document).ready(function() {literal} { {/literal}
		doGetStatusSummaries();
		doGetSaveStatuses();
	{literal} }); {/literal}
	</script>
	
	{if $pageLinks.all}<div class="pagination">{$pageLinks.all}</div>{/if}
	</div>
</div>