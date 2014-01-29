{strip}
	<div class="related-manifestations">
		<div class="row related-manifestations-header">
			<div class="col-xs-12 result-label related-manifestations-label">
				Formats
			</div>
		</div>
		{foreach from=$relatedManifestations item=relatedManifestation}
			<div class="row related-manifestation">
				<div class="col-sm-3">
					{if $relatedManifestation.numRelatedRecords == 1}
						<span class='manifestation-toggle-placeholder'>&nbsp;</span>
						<a href="{$relatedManifestation.url}">{$relatedManifestation.format}</a>
					{else}
						<a href="#" onclick="return VuFind.ResultsList.toggleRelatedManifestations('{$id}_{$relatedManifestation.format|escapeCSS}');">
							<span class='manifestation-toggle collapsed' id='manifestation-toggle-{$id}_{$relatedManifestation.format|escapeCSS}'>+</span> {$relatedManifestation.format}
						</a>
					{/if}
				</div>
				<div class="col-sm-7">
					{if $relatedManifestation.available && $relatedManifestation.locationLabel}
						<div class="related-manifestation-shelf-status">On Shelf At {$relatedManifestation.locationLabel}</div>
					{/if}
					<div class="related-manifestation-copies">{$relatedManifestation.availableCopies} of {$relatedManifestation.copies} copies available.</div>
					{if false && $relatedManifestation.numRelatedRecords > 1}
						<div class="related-manifestation-editions">
					    {$relatedManifestation.numRelatedRecords} editions.
						</div>
					{/if}
					{if $relatedManifestation.shelfLocation}
						<div class="related-manifestation-shelf-location">
							Shelf Location: {$relatedManifestation.shelfLocation}
						</div>
					{/if}
					{if $relatedManifestation.callNumber}
						<div class="related-manifestation-call-number">Call Number: {$relatedManifestation.callNumber}</div>
					{/if}
				</div>
				{*
				<div class="col-sm-4">{$relatedManifestation.callNumber}</div>
				<div class="col-sm-2">{if $relatedManifestation.available}Available{else}Checked Out{/if}</div>
				<div class="col-sm-1">{if $relatedManifestation.copies > 1000}Unlimited{else}{$relatedManifestation.copies}{/if}</div>
				*}
				<div class="col-sm-2 btn-group manifestation-actions">
					{foreach from=$relatedManifestation.actions item=curAction}
						<a href="{$curAction.url}" class="btn btn-sm">{$curAction.title}</a>
					{/foreach}
				</div>

				<div class="hidden" id="relatedRecordPopup_{$id}_{$relatedManifestation.format|escapeCSS}">
					{include file="GroupedWork/relatedRecords.tpl" relatedRecords=$relatedManifestation.relatedRecords}
				</div>
			</div>
		{/foreach}
	</div>
{/strip}