{strip}
	<div class="related-manifestations">
		<div class="row bold">
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
					{$relatedManifestation.availableCopies} of {$relatedManifestation.copies} copies available.
					{if $relatedManifestation.numRelatedRecords > 1}
					 &nbsp; {$relatedManifestation.numRelatedRecords} editions.
					{/if}
				</div>
				{*
				<div class="col-sm-4">{$relatedManifestation.callNumber}</div>
				<div class="col-sm-2">{if $relatedManifestation.available}Available{else}Checked Out{/if}</div>
				<div class="col-sm-1">{if $relatedManifestation.copies > 1000}Unlimited{else}{$relatedManifestation.copies}{/if}</div>
				*}
				<div class="col-sm-2 btn-group">
					{foreach from=$relatedManifestation.actions item=curAction}
						<a href="{$curAction.url}" class="btn btn-sm">{$curAction.title}</a>
					{/foreach}
				</div>
			</div>
			<div class="hidden" id="relatedRecordPopup_{$id}_{$relatedManifestation.format|escapeCSS}">
				{include file="GroupedWork/relatedRecords.tpl" relatedRecords=$relatedManifestation.relatedRecords}
			</div>
		{/foreach}
	</div>
{/strip}