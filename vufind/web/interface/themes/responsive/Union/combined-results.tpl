{strip}
	<h2>
		{$pageTitleShort}
	</h2>
	<div class="result-head">
		{* User's viewing mode toggle switch *}
		{include file="Union/results-displayMode-toggle.tpl"}

		<div class="clearer"></div>
	</div>

	<div class="combined-results-container">
	{foreach from=$combinedResultSections item=combinedResultSection name=searchSection}
		<div class="combined-results-section {*col-tn-12 col-md-6*}{*{if ($smarty.foreach.searchSection.iteration%2)!=0} col-md-pull-6{else} col-md-push-6{/if}*}">
			<h3 class="combined-results-section-title">
				{*{$smarty.foreach.searchSection.iteration} *}<a href="{$combinedResultSection->getResultsLink($lookfor, $basicSearchType)}" target='_blank'>{$combinedResultSection->displayName}</a>
			</h3>
			<div class="combined-results-section-results" id="combined-results-section-results-{$combinedResultSection->id}">
				<img src="{$path}/images/loading.gif" alt="loading">
			</div>
			<script type="text/javascript">
				VuFind.Searches.getCombinedResults('{$combinedResultSection|get_class}:{$combinedResultSection->id}', '{$combinedResultSection->id}', '{$combinedResultSection->source}', '{$lookfor}', '{$basicSearchType}', {$combinedResultSection->numberOfResultsToShow});
			</script>
		</div>
	{/foreach}
	</div>

	<script type="text/javascript">
		function reloadCombinedResults(){ldelim}
			{foreach from=$combinedResultSections item=combinedResultSection}
				VuFind.Searches.getCombinedResults('{$combinedResultSection|get_class}:{$combinedResultSection->id}', '{$combinedResultSection->id}', '{$combinedResultSection->source}', '{$lookfor}', '{$basicSearchType}', {$combinedResultSection->numberOfResultsToShow});
			{/foreach}
		{rdelim}
	</script>
{/strip}

{literal}
	<style type="text/css">
	</style>
{/literal}
