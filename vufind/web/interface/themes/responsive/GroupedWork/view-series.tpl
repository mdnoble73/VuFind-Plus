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
				<div class="row">
					<div class="result-label  col-md-3">Series Name</div>
					<div class="col-xs-8">{$seriesTitle}</div>
				</div>
				<div class="row">
					<div class="result-label  col-md-3">Author</div>
					<div class="col-xs-8">
						{foreach from=$seriesAuthors item=author}
							<span class="sidebarValue">{$author} </span>
						{/foreach}
					</div>
				</div>
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
	{literal} }); {/literal}
	</script>
	
	{if $pageLinks.all}<div class="pagination">{$pageLinks.all}</div>{/if}
	</div>
</div>