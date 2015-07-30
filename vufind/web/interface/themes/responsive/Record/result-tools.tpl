{strip}
<div class="btn-toolbar">
	<div class="btn-group btn-group-vertical btn-block">
		{* Place hold link *}
		{if $showHoldButton}
				<a href="#" class="btn btn-sm btn-block btn-primary" onclick="return VuFind.Record.showPlaceHold('{$activeRecordProfileModule}', '{$recordDriver->getIdWithSource()}')" >{translate text="Place Hold"}</a>
		{/if}
		{* Book Material link *}
		{if $enableMaterialsBooking}
		 {* hidden and only shown if bookable via the ajax call for GetHoldingsInfo *}
				<a id="bookMaterialButton" href="#" class="btn btn-sm btn-block btn-warning" onclick="return VuFind.Record.showBookMaterial('{$summId|replace:'ils:':''}')" style="display: none">{translate text="Book Material"}</a>
			{* source prefex stripped out for now. *}
		{/if}
	</div>
</div>
{/strip}