{strip}
<div class="btn-toolbar">
	<div class="btn-group btn-group-vertical btn-block">
		{* Show hold/checkout button as appropriate *}
		{if $showHoldButton}
			{* Place hold link *}
			<a href="#" class="btn btn-sm btn-block btn-primary" id="placeHold{$summId|escape:"url"}" style="display:none" onclick="return VuFind.OverDrive.placeOverDriveHold('{$eContentRecord->externalId}')">{translate text="Place Hold"}</a>

			{* Checkout link *}
			<a href="#" class="btn btn-sm btn-block btn-primary" id="checkout{$summId|escape:"url"}" style="display:none" onclick="return {if overDriveVersion==1}VuFind.OverDrive.checkoutOverDriveItem{else}VuFind.OverDrive.checkoutOverDriveItemOneClick{/if}('{$eContentRecord->externalId}')">{translate text="Checkout"}</a>
		{/if}
	</div>
</div>
{/strip}