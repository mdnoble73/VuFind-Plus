{strip}
	<div class="row-fluid bold">
		<div class="span3">
			Format
		</div>
		<div class="span3">
			Call Number
		</div>
		<div class="span3">
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
				<div class="span3">{$relatedManifestation.format}</div>
				<div class="span3">{$relatedManifestation.callNumber}</div>
				<div class="span3">{if $relatedManifestation.availabile}Available{else}Not Available{/if}</div>
				<div class="span1">{$relatedManifestation.copies}</div>
				<div class="span2">
					<a href="#" class="btn btn-small" onclick="VuFind.GroupedWork.showRelatedManifestations('{$id}', '{$relatedManifestation.format}');">more</a>
				</div>
			</div>

		{/foreach}
	</div>
{/strip}