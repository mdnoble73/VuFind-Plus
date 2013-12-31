{strip}
	<div class="row-fluid bold">
		<div class="span3">
			Format
		</div>
		<div class="span4">
			Call Number
		</div>
		<div class="span2">
			Availability
		</div>
		<div class="span1">
			Copies
		</div>
		<div class="span2">
			&nbsp; {* Actions *}
		</div>
	</div>
	<div class="div-striped striped">
		{foreach from=$relatedManifestations item=relatedManifestation}
			<div class="row-fluid">
				<div class="span3">
					{if $relatedManifestation.numRelatedRecords == 1}
						<a href="{$relatedManifestation.url}">{$relatedManifestation.format}</a>
					{else}
						<a href="#" onclick="VuFind.showElementInPopup('Related Records', '#relatedRecordPopup_{$id}_{$relatedManifestation.format|escapeCSS}');">{$relatedManifestation.format}</a>
					{/if}
				</div>
				<div class="span4">{$relatedManifestation.callNumber}</div>
				<div class="span2">{if $relatedManifestation.available}Available{else}Checked Out{/if}</div>
				<div class="span1">{if $relatedManifestation.copies > 1000}Unlimited{else}{$relatedManifestation.copies}{/if}</div>
				<div class="span2 btn-group">
					{foreach from=$relatedManifestation.actions item=curAction}
						<a href="{$curAction.url}" class="btn btn-small">{$curAction.title}</a>
					{/foreach}
				</div>
			</div>
			<div class="hidden" id="relatedRecordPopup_{$id}_{$relatedManifestation.format|escapeCSS}">
				{include file="GroupedWork/relatedRecords.tpl" relatedRecords=$relatedManifestation.relatedRecords}
			</div>
		{/foreach}
	</div>
{/strip}