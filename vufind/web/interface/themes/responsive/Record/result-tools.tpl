{strip}
<div class="btn-toolbar">
	<div class="btn-group btn-group-vertical btn-block">
		{* actions *}
		{foreach from=$actions item=curAction}
			<a href="{$curAction.url}" {if $curAction.onclick}onclick="{$curAction.onclick}"{/if} class="btn btn-sm btn-primary">{$curAction.title}</a>
		{/foreach}
		{* Book Material link *}
		{if $enableMaterialsBooking}
		 {* hidden and only shown if bookable via the ajax call for GetHoldingsInfo *}
				<a id="bookMaterialButton" href="#" class="btn btn-sm btn-block btn-warning" onclick="return VuFind.Record.showBookMaterial('{$summId|replace:'ils:':''}')" style="display: none">{translate text="Schedule Item"}</a>
			{* source prefex stripped out for now. *}
		{/if}
	</div>
</div>
{/strip}