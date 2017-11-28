{strip}
	<h2>
		{$pageTitleShort}
	</h2>
	<div class="result-head">
		{* User's viewing mode toggle switch *}
		{include file="Union/results-displayMode-toggle.tpl"}

		<div class="clearer"></div>
	</div>

	{foreach from=$combinedResultSections item=combinedResultSection}
		<div class="combined-results-section col col-xs-12 col-sm-6">
			<h3 class="combined-results-section-title">
				<a href="{$combinedResultSection->getResultsLink($lookfor, $basicSearchType)}">{$combinedResultSection->displayName}</a>
			</h3>
			<div class="combined-results-section-results" id="combined-results-section-results-{$combinedResultSection->id}">
				<img src="{$path}/images/loading.gif" alt="loading">
			</div>
			<script type="text/javascript">
				VuFind.Searches.getCombinedResults('{$combinedResultSection|get_class}:{$combinedResultSection->id}', '{$combinedResultSection->id}', '{$combinedResultSection->source}', '{$lookfor}', '{$basicSearchType}', {$combinedResultSection->numberOfResultsToShow});
			</script>
		</div>
	{/foreach}

	<script type="text/javascript">
		function reloadCombinedResults(){ldelim}
			{foreach from=$combinedResultSections item=combinedResultSection}
				VuFind.Searches.getCombinedResults('{$combinedResultSection|get_class}:{$combinedResultSection->id}', '{$combinedResultSection->id}', '{$combinedResultSection->source}', '{$lookfor}', '{$basicSearchType}', {$combinedResultSection->numberOfResultsToShow});
			{/foreach}
		{rdelim}
	</script>
{/strip}