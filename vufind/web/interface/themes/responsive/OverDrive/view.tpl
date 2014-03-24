<script type="text/javascript">
{literal}$(document).ready(function(){{/literal}
	VuFind.Record.loadHoldingsInfo('{$id|escape:"url"}', '{$id|escape:"url"}', 'OverDrive');
	VuFind.GroupedWork.loadEnrichmentInfo('{$recordDriver->getPermanentId()|escape:"url"}');
	VuFind.GroupedWork.loadReviewInfo('{$recordDriver->getPermanentId()|escape:"url"}');
	VuFind.Prospector.loadRelatedProspectorTitles('{$recordDriver->getPermanentId()|escape:"url"}');
{literal}});{/literal}
</script>
{strip}
	{* Display Title *}
	<h2>
		{$recordDriver->getTitle()|removeTrailingPunctuation|escape}{if $recordDriver->getSubTitle()}: {$recordDriver->getSubTitle()|removeTrailingPunctuation|escape}{/if}
		{if $recordDriver->getFormats()}
			&nbsp;<small>({implode subject=$recordDriver->getFormats() glue=", "})</small>
		{/if}
	</h2>

	{* Display more information about the title*}
	{if $recordDriver->getAuthor()}
		<div class="row">
			<div class="result-label col-md-3">Author: </div>
			<div class="col-md-9 result-value">
				<a href="{$path}/Author/Home?author={$recordDriver->getAuthor()|escape:"url"}">{$recordDriver->getAuthor()|highlight:$lookfor}</a>
			</div>
		</div>
	{/if}

	<div id="main-content" class="col-md-12">
		<div class="row">
			<div id="record-details-column" class="col-md-9">
				<div id="record-details-header">
					<div id="holdingsSummaryPlaceholder" class="holdingsSummaryRecord">Loading...</div>
				</div>

				{if $recordDriver->getDescription()}
					<dl>
						<dt>{translate text='Description'}</dt>
						<dd class="recordDescription">
							{$recordDriver->getDescription()}
						</dd>
					</dl>
				{/if}
			</div>

			<div id="recordTools" class="col-md-3">
				{include file="OverDrive/result-tools.tpl" showMoreInfo=false summShortId=$shortId summId=$id summTitle=$title recordUrl=$recordUrl}

			</div>
		</div>

		{include file=$moreDetailsTemplate}

		<hr/>
	</div>
{/strip}