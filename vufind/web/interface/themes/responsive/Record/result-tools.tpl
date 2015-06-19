{strip}
<div class="btn-toolbar">
	<div class="btn-group btn-group-vertical btn-block">
		{* Place hold link *}
		{if $showHoldButton}
				<a href="#" class="btn btn-sm btn-block btn-primary" onclick="return VuFind.Record.showPlaceHold('{$summId}')" >{translate text="Place Hold"}</a>
		{/if}
	</div>
</div>
{/strip}