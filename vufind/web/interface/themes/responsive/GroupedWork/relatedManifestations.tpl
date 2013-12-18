{strip}
	<div class="row-fluid bold">
		<div class="span2">
			Format
		</div>
		<div class="span3">
			Call Number
		</div>
		<div class="span2">
			Availability
		</div>
		<div class="span1">
			Copies
		</div>
		<div class="span4">
			&nbsp; {* Actions *}
		</div>
	</div>
	<div class="div-striped striped">
		{foreach from=$relatedManifestations item=relatedManifestation}
			<div class="row-fluid">
				<div class="span2">
					{$relatedManifestation.format}
				</div>
				<div class="span3">{$relatedManifestation.callNumber}</div>
				<div class="span2">{if $relatedManifestation.available}Available{else}Checked Out{/if}</div>
				<div class="span1">{if $relatedManifestation.copies > 1000}Unlimited{else}{$relatedManifestation.copies}{/if}</div>
				<div class="span4 btn-group">
					{foreach from=$relatedManifestation.actions item=curAction}
						{if $curAction.id == 'ShowRelatedRecords'}
							<a href="#" class="btn btn-small" onclick="VuFind.showElementInPopup('Related Records', '#relatedRecordPopup_{$id}_{$relatedManifestation.format}');">{$curAction.title}</a>
						{else}
							<a href="{$curAction.url}" class="btn btn-small">{$curAction.title}</a>
						{/if}
					{/foreach}
				</div>
			</div>
			<div class="hidden" id="relatedRecordPopup_{$id}_{$relatedManifestation.format}">
				{include file="GroupedWork/relatedRecords.tpl" relatedRecords=$relatedManifestation.relatedRecords}
			</div>
		{/foreach}
	</div>
{/strip}