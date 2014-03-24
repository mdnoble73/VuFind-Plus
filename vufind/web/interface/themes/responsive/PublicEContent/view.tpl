{if !empty($addThis)}
<script type="text/javascript" src="https://s7.addthis.com/js/250/addthis_widget.js?pub={$addThis|escape:"url"}"></script>
{/if}
<script type="text/javascript">
{literal}$(document).ready(function(){{/literal}
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

	{* Display more information about the title*}
	{if $recordDriver->getAuthor()}
		<div class="row">
			<div class="result-label col-md-3">Author: </div>
			<div class="col-md-9 result-value">
				<a href="{$path}/Author/Home?author={$recordDriver->getAuthor()|escape:"url"}">{$recordDriver->getAuthor()|highlight:$lookfor}</a>
			</div>
		</div>
	{/if}

	{if $error}<p class="error">{$error}</p>{/if}

	{if $user && $user->hasRole('epubAdmin')}
		<span id="eContentStatus">{$eContentRecord->status} </span>
		<div class="btn-group">
			<a href='{$path}/EcontentRecord/{$id}/Edit' class="btn btn-sm btn-default">edit</a>
			{if $eContentRecord->status != 'archived' && $eContentRecord->status != 'deleted'}
				<a href='{$path}/EcontentRecord/{$id}/Archive' onclick="return confirm('Are you sure you want to archive this record?	The record should not have any holds or checkouts when it is archived.')" class="btn btn-sm btn-default">archive</a>
			{/if}
			{if $eContentRecord->status != 'deleted'}
				<a href='{$path}/EcontentRecord/{$id}/Delete' onclick="return confirm('Are you sure you want to delete this record?	The record should not have any holds or checkouts when it is deleted.')" class="btn btn-sm btn-default">delete</a>
			{/if}
		</div>
	{/if}

	<hr/>

	<div id="main-content" class="col-md-12">
		<div class="row">
			<div id="record-details-column" class="col-md-9">
				<div id="record-details-header">
					<div id="holdingsSummaryPlaceholder" class="holdingsSummaryRecord">Loading...</div>
				</div>

				{if $cleanDescription}
					<dl>
						<dt>{translate text='Description'}</dt>
						<dd class="recordDescription">
							{$cleanDescription}
						</dd>
					</dl>
				{/if}
			</div>

			<div id="recordTools" class="col-md-3">
				{include file="PublicEContent/result-tools.tpl" showMoreInfo=false summShortId=$shortId summId=$id summTitle=$title recordUrl=$recordUrl}
			</div>
		</div>

		{include file=$moreDetailsTemplate}

		<hr/>
	</div>
{/strip}