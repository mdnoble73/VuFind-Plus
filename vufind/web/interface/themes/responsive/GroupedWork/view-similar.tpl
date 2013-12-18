{* Main Listing *}
{if (isset($title)) }
<script type="text/javascript">
	alert("{$title}");
</script>
{/if}
<div id="page-content" class="content">

	{* Listing Options *}
	<div id="searchInfo">
		<h2>Similar to <a href="/GroupedWork/{$id}/Home">{$recordDriver->getTitle()}</a></h2>
	</div>
	{* End Listing Options *}

	<div id="similarTitleTabs" class="tabbable">
		<ul class="nav nav-tabs">
			{if $enrichment->similarTitleCount > 0}
				<li class="active"><a href="#similarTitlesNovelist" data-toggle="tab">Titles Recommended by NoveList</a></li>
			{/if}
			{if $enrichment->similarAuthorCount > 0}
				<li><a href="#similarAuthorsNoveList" data-toggle="tab">Similar Authors from NoveList</a></li>
			{/if}
			{if $enrichment->similarSeriesCount > 0}
				<li><a href="#similarSeriesNoveList" data-toggle="tab">Similar Series from NoveList</a></li>
			{/if}
			{if $recordCount > 0}
				<li><a href="#similarTitlesVuFind" data-toggle="tab">Similar Titles</a></li>
			{/if}
		</ul>

		<div class="tab-content">
			{if $enrichment->similarTitleCount > 0}
				<div id="similarTitlesNovelist" class="tab-pane active">
					{foreach from=$enrichment->similarTitles item=similarTitle name="recordLoop"}
						<div class="result{if ($smarty.foreach.recordLoop.iteration % 2) == 0} alt{/if}">
							{* This is raw HTML -- do not escape it: *}
							<h3>{if $similarTitle.fullRecordLink}<a href='{$similarTitle.fullRecordLink}'>{/if}{$similarTitle.title}{if $similarTitle.fullRecordLink}</a>{/if}
							by <a href="/Search/Results?lookfor={$similarTitle.author|escape:url}">{$similarTitle.author}</a></h3>
							<div class="reason">
							{$similarTitle.reason}
							</div>
						</div>
					{/foreach}
				</div>
			{/if}

			{if $enrichment->similarAuthorCount > 0}
				<div id="similarAuthorsNoveList" class="tab-pane">
					{foreach from=$enrichment->authors item=author name="recordLoop"}
						<div class="result{if ($smarty.foreach.recordLoop.iteration % 2) == 0} alt{/if}">
							{* This is raw HTML -- do not escape it: *}
							<h3><a href="{$author.link}">{$author.name}</a></h3>
							<div class="reason">
								{$author.reason}
							</div>
						</div>
					{/foreach}
				</div>
			{/if}

			{if $enrichment->similarSeriesCount > 0}
				<div id="similarSeriesNoveList" class="tab-pane">
					{foreach from=$enrichment->similarSeries item=series name="recordLoop"}
						<div class="result{if ($smarty.foreach.recordLoop.iteration % 2) == 0} alt{/if}">
							{* This is raw HTML -- do not escape it: *}
							<h3><a href="/Search/Results?lookfor={$series.title|escape:url}">{$series.title}</a> by <a href="Search/Results?lookfor={$series.author|escape:url}">{$series.author}</a></h3>
							<div class="reason">
								{$series.reason}
							</div>
						</div>
					{/foreach}
				</div>
			{/if}

			{if $recordCount > 0}
				<div id="similarTitlesVuFind" class="tab-pane">
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
	</div>

	<script type="text/javascript">
	$(document).ready(function() {literal} {
		//VuFind.ResultsList.loadStatusSummaries();
		//VuFind.ResultsList.loadSeriesInfo();
		//doGetStatusSummaries();
		//doGetSaveStatuses();
		//doGetSeriesInfo();
	}); {/literal}
	</script>
	
	{if $pageLinks.all}<div class="pagination">{$pageLinks.all}</div>{/if}
</div>