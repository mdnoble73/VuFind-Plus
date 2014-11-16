{strip}
{* Display Title *}
	<h2 class="notranslate">
		{$recordDriver->getTitle()|removeTrailingPunctuation|escape}
	</h2>

	{if $recordDriver->getPrimaryAuthor()}
		<div class="row">
			<div class="result-label col-md-3">Author: </div>
			<div class="col-md-9 result-value notranslate">
				<a href="{$path}/Author/Home?author={$recordDriver->getPrimaryAuthor()|escape:"url"}">{$recordDriver->getPrimaryAuthor()|highlight:$lookfor}</a>
			</div>
		</div>
	{/if}

	{if $recordDriver->getSeries()}
		<div class="series row">
			<div class="result-label col-md-3">Series: </div>
			<div class="col-md-9 result-value">
				{assign var=summSeries value=$recordDriver->getSeries()}
				<a href="{$path}/GroupedWork/{$recordDriver->getPermanentId()}/Series">{$summSeries.seriesTitle}</a>{if $summSeries.volume} volume {$summSeries.volume}{/if}
			</div>
		</div>
	{/if}

	{if $error}<p class="error">{$error}</p>{/if}

	{assign value=$recordDriver->getRelatedManifestations() var="relatedManifestations"}
	{include file="GroupedWork/relatedManifestations.tpl"}

	{include file=$moreDetailsTemplate}
	<span class="Z3988" title="{$recordDriver->getOpenURL()|escape}" style="display:none">&nbsp;</span>
{/strip}
<script type="text/javascript">
	{literal}$(document).ready(function(){{/literal}
		VuFind.GroupedWork.loadEnrichmentInfo('{$recordDriver->getPermanentId()|escape:"url"}');
		VuFind.GroupedWork.loadReviewInfo('{$recordDriver->getPermanentId()|escape:"url"}');
		VuFind.Prospector.loadRelatedProspectorTitles('{$recordDriver->getPermanentId()|escape:"url"}');
	{literal}});{/literal}
</script>