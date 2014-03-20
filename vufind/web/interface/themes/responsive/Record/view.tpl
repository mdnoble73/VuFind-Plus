{if !empty($addThis)}
<script type="text/javascript" src="https://s7.addthis.com/js/250/addthis_widget.js?pub={$addThis|escape:"url"}"></script>
{/if}
<script type="text/javascript" src="{$path}/interface/themes/responsive/js/vufind/title-scroller.js"></script>
<script type="text/javascript">
{literal}$(document).ready(function(){{/literal}
	VuFind.Record.loadHoldingsInfo('{$id|escape:"url"}', '{$shortId}', 'VuFind');
	VuFind.GroupedWork.loadEnrichmentInfo('{$recordDriver->getPermanentId()|escape:"url"}');
	VuFind.GroupedWork.loadReviewInfo('{$recordDriver->getPermanentId()|escape:"url"}');
	{if $enablePospectorIntegration == 1}
		VuFind.Prospector.loadRelatedProspectorTitles('{$id|escape:"url"}', 'VuFind');
	{/if}
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

	{if $error}<p class="error">{$error}</p>{/if}

	<div class="row">

		{*
		<div class="col-md-3">
			{include file="Record/view-sidebar.tpl"}
		</div>
		*}

		<div id="main-content" class="col-xs-12">
			<div class="row">

				<div id="record-details-column" class="col-sm-9">
					{include file="Record/view-title-details.tpl"}

				</div>

				<div id="recordTools" class="col-md-3">
					{include file="Record/result-tools.tpl" showMoreInfo=false summShortId=$shortId summId=$id summTitle=$title recordUrl=$recordUrl}
				</div>
			</div>

			{include file=$moreDetailsTemplate}

		</div>
	</div>
	{literal}
	<script type="text/javascript">
		$(document).ready(function(){
			VuFind.ResultsList.loadStatusSummaries();
		});
	</script>
	{/literal}
{/strip}