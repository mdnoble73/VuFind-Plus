{strip}
<div class="btn-toolbar">
	<div class="btn-group btn-group-vertical btn-block">
		{* Place hold link *}
		{if $showHoldButton}
				<a href="#" class="btn btn-sm btn-block btn-primary" onclick="return VuFind.Record.showPlaceHold('{$activeRecordProfileModule}', '{$recordDriver->getIdWithSource()}')" >{translate text="Place Hold"}</a>
		{/if}
		{* Book Material link *}
		{if $enableMaterialsBooking} {* TODO check bookable? *}
				<a href="#" class="btn btn-sm btn-block btn-warning" onclick="return VuFind.Record.showBookMaterial('{$summId}')" >{translate text="Book Material"}</a>
		{/if}
	</div>
</div>
{/strip}