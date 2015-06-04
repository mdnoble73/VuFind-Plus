{strip}
<div class="btn-toolbar">
	<div class="btn-group btn-group-vertical btn-block">
		{* Place hold link *}
		{if $showHoldButton}
			{if $id=='.b43088739' && $consortiumName == 'Marmot'} {* Trail Museum Pass Hack *}
				<a href="#" class="btn btn-sm btn-block btn-primary" onclick="return VuFind.showMessage('TrailHead', 'Visit a Gunnison County Library District library to obtain a Trailhead Museum Pass today!')" >{translate text="Place Hold"}</a>
			{else}
				<a href="#" class="btn btn-sm btn-block btn-primary" onclick="return VuFind.Record.showPlaceHold('{$summId}')" >{translate text="Place Hold"}</a>
			{/if}
		{/if}
	</div>
</div>
{/strip}