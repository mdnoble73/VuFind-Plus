{if !empty($addThis)}
<script type="text/javascript" src="https://s7.addthis.com/js/250/addthis_widget.js?pub={$addThis|escape:"url"}"></script>
{/if}
<script type="text/javascript">
{literal}$(document).ready(function(){{/literal}
	VuFind.GroupedWork.loadEnrichmentInfo('{$recordDriver->getPermanentId()|escape:"url"}');
	VuFind.GroupedWork.loadReviewInfo('{$recordDriver->getPermanentId()|escape:"url"}');
	VuFind.Prospector.loadRelatedProspectorTitles('{$recordDriver->getPermanentId()|escape:"url"}');
{literal}});{/literal}
</script>
{strip}
	<div class="col-xs-12">
		{* Display Title *}
		<h2>
			{$recordDriver->getTitle()|removeTrailingPunctuation|escape}{if $recordDriver->getSubTitle()}: {$recordDriver->getSubTitle()|removeTrailingPunctuation|escape}{/if}
			{if $recordDriver->getFormats()}
				<br/><small>({implode subject=$recordDriver->getFormats() glue=", "})</small>
			{/if}
		</h2>

		{if $error}<p class="error">{$error}</p>{/if}

		<div class="row">

			<div id="main-content" class="col-xs-12">
				<div class="row">

					<div id="record-details-column" class="col-sm-9">
						{include file="RestrictedEContent/view-title-details.tpl"}
					</div>

					<div id="recordTools" class="col-md-3">
						<div class="btn-toolbar">
							<div class="btn-group btn-group-vertical btn-block">
								{* Options for the user to view online or download *}
								{foreach from=$summaryActions item=link}
									{if $link.showInSummary == true}
										<a href="{if $link.url}{$link.url}{else}#{/if}" {if $link.onclick && strlen($link.onclick) > 0}onclick="{$link.onclick}"{/if} class="btn btn-sm btn-primary">{$link.title}</a>&nbsp;
									{/if}
								{/foreach}
							</div>
						</div>
					</div>
				</div>

				{include file=$moreDetailsTemplate}

			</div>
		</div>
	</div>
	<span class="Z3988" title="{$recordDriver->getOpenURL()|escape}" style="display:none">&nbsp;</span>
{/strip}