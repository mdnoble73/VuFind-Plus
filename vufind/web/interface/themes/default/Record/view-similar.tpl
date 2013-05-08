{* Main Listing *}
{if (isset($title)) }
<script type="text/javascript">
	alert("{$title}");
</script>
{/if}
<div id="page-content" class="content">

	{* Listing Options *}
	<div id="searchInfo">
		<h2>Similar to <a href="/Record/{$id}/Home">{$recordTitleSubtitle}</a></h2>
	</div>
	{* End Listing Options *}

	<div id="similarTitleAccordion">
		<ul>
			{if $enrichment.similarTitleCount > 0}
				<li><a href="#similarTitlesNovelist">Titles Recommended by NoveList</a></li>
			{/if}
			{if $enrichment.similarAuthorCount > 0}
			<li><a href="#similarAuthorsNoveList">Similar Authors from NoveList</a></li>
			{/if}
			{if $enrichment.similarSeriesCount > 0}
			<li><a href="#similarSeriesNoveList">Similar Series from NoveList</a></li>
			{/if}
			{if $recordCount > 0}
			<li><a href="#similarTitlesVuFind">Similar Titles In The Catalog</a></li>
			{/if}
		</ul>
		{if $enrichment.similarTitleCount > 0}
			<div id="similarTitlesNovelist">
				{foreach from=$enrichment.similarTitles item=similarTitle name="recordLoop"}
					<div class="result{if ($smarty.foreach.recordLoop.iteration % 2) == 0} alt{/if}">
						{* This is raw HTML -- do not escape it: *}
						{if $similarTitle.fullRecordLink}<a href='{$similarTitle.fullRecordLink}'>{/if}{$similarTitle.title}{if $similarTitle.fullRecordLink}</a>{/if}
						by <a href="/Search/Results?lookfor={$similarTitle.author|escape:url}">{$similarTitle.author}</a>
						<div class="reason">
						{$similarTitle.reason}
						</div>
					</div>
				{/foreach}
			</div>
		{/if}

		{if $enrichment.similarAuthorCount > 0}
			<div id="similarAuthorsNoveList">
				{foreach from=$enrichment.authors item=author name="recordLoop"}
					<div class="result{if ($smarty.foreach.recordLoop.iteration % 2) == 0} alt{/if}">
						{* This is raw HTML -- do not escape it: *}
						<a href="{$author.link}">{$author.name}</a>
						<div class="reason">
							{$author.reason}
						</div>
					</div>
				{/foreach}
			</div>
		{/if}

		{if $enrichment.similarSeriesCount > 0}
			<div id="similarSeriesNoveList">
				{foreach from=$enrichment.similarSeries item=series name="recordLoop"}
					<div class="result{if ($smarty.foreach.recordLoop.iteration % 2) == 0} alt{/if}">
						{* This is raw HTML -- do not escape it: *}
						<a href="/Search/Results?lookfor={$series.title|escape:url}">{$series.title}</a> by <a href="Search/Results?lookfor={$series.author|escape:url}">{$series.author}</a>
						<div class="reason">
							{$series.reason}
						</div>
					</div>
				{/foreach}
			</div>
		{/if}

		{if $recordCount > 0}
			<div id="similarTitlesVuFind">
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
		{/if}
	</div>

	<script type="text/javascript">
	$(document).ready(function() {literal} {
		doGetStatusSummaries();
		doGetSaveStatuses();
		doGetSeriesInfo();
		$("#similarTitleAccordion").tabs();
	}); {/literal}
	</script>
	
	{if $pageLinks.all}<div class="pagination">{$pageLinks.all}</div>{/if}
</div>