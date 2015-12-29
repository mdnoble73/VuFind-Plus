{if !empty($addThis)}
<script type="text/javascript" src="https://s7.addthis.com/js/250/addthis_widget.js?pub={$addThis|escape:"url"}"></script>
{/if}
<script type="text/javascript">
{literal}$(function(){{/literal}
	{*//VuFind.Record.loadHoldingsInfo('{$activeRecordProfileModule}', '{$id|escape:"url"}', '{$shortId}', 'VuFind');*}
	VuFind.GroupedWork.loadEnrichmentInfo('{$recordDriver->getPermanentId()|escape:"url"}');
	VuFind.GroupedWork.loadReviewInfo('{$recordDriver->getPermanentId()|escape:"url"}');
	{if $enablePospectorIntegration == 1}
		VuFind.Prospector.loadRelatedProspectorTitles('{$recordDriver->getPermanentId()|escape:"url"}');
	{/if}
{literal}});{/literal}
</script>
{strip}
	<div class="col-xs-12">
		{* Display Title *}
		<h2>
			{$recordDriver->getTitle()|escape}
			{if $recordDriver->getTitleSection()} {$recordDriver->getTitleSection()|escape}{/if}
			{if $recordDriver->getFormats()}
				<br><small>({implode subject=$recordDriver->getFormats() glue=", "})</small>
			{/if}
		</h2>

		<div class="row">
			<div class="col-xs-12 col-sm-5 col-md-4 col-lg-3 text-center">
				{if $user->disableCoverArt != 1}
					<div id="recordcover" class="text-center row">
						<img alt="{translate text='Book Cover'}" class="img-thumbnail" src="{$recordDriver->getBookcoverUrl('medium')}">
					</div>
				{/if}
				{if $showRatings}
					{include file="GroupedWork/title-rating-full.tpl" ratingClass="" showFavorites=0 ratingData=$recordDriver->getRatingData() showNotInterested=false hideReviewButton=true}
				{/if}
			</div>


			{*<div class="row">*}{* Replaced with row div above cover art *}

			<div id="main-content" class="col-xs-12 col-sm-7 col-md-8 col-lg-9">

				{if $error}{* TODO: Does this get used? *}
					<div class="row">
						<div class="alert alert-danger">
							{$error}
						</div>
					</div>
				{/if}

				<div class="row">

					<div id="record-details-column" class="col-xs-12 col-sm-12 col-md-9">
						{include file="Record/view-title-details.tpl"}
					</div>

					<br>

					<div id="recordTools" class="col-xs-12 col-sm-6 col-md-3">
						{include file="Record/result-tools.tpl" showMoreInfo=false summShortId=$shortId module=$activeRecordProfileModule summId=$id summTitle=$title recordUrl=$recordUrl}
					</div>
				</div>

				<div class="row">
					<div class="col-xs-12">
					{include file='GroupedWork/result-tools-horizontal.tpl' summId=$recordDriver->getPermanentId() summShortId=$recordDriver->getPermanentId() ratingData=$recordDriver->getRatingData() recordUrl=$recordDriver->getLinkUrl() showMoreInfo=false}
					</div>
				</div>

			</div>
		</div>

		<div class="row">
			{include file=$moreDetailsTemplate}
		</div>

		<span class="Z3988" title="{$recordDriver->getOpenURL()|escape}" style="display:none">&nbsp;</span>
	</div>
{/strip}
