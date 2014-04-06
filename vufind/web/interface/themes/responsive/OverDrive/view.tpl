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

		<div class="row">

			<div id="main-content" class="col-xs-12">
				<div class="row">

					<div id="record-details-column" class="col-sm-9">
						{include file="OverDrive/view-title-details.tpl"}

					</div>

					<div id="recordTools" class="col-md-3">
						<div class="btn-toolbar">
							<div class="btn-group btn-group-vertical btn-block">
								{* Show hold/checkout button as appropriate *}
								{if $holdingsSummary.showPlaceHold}
									{* Place hold link *}
									<a href="#" class="btn btn-sm btn-block btn-primary" id="placeHold{$recordDriver->getUniqueID()|escape:"url"}" onclick="return VuFind.OverDrive.placeOverDriveHold('{$recordDriver->getUniqueID()}')">{translate text="Place Hold"}</a>
								{/if}
								{if $holdingsSummary.showCheckout}
									{* Checkout link *}
									<a href="#" class="btn btn-sm btn-block btn-primary" id="checkout{$recordDriver->getUniqueID()|escape:"url"}" onclick="return VuFind.OverDrive.checkoutOverDriveItemOneClick('{$recordDriver->getUniqueID()}')">{translate text="Checkout"}</a>
								{/if}
							</div>
						</div>
					</div>
				</div>

				{include file=$moreDetailsTemplate}

			</div>
		</div>
	</div>
{/strip}