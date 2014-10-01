{strip}
{if count($items) > 0}
	{foreach from=$items item=item key=index}
	<div class="eContentHolding">
		<div class="eContentHoldingHeader">
			<div class="row">
				<div class="col-md-9">
					<strong>{$item.locationLabel}</strong>
				</div>
			</div>
		</div>
		<div class="eContentHoldingActions">
			{* Options for the user to view online or download *}
			{foreach from=$item.actions item=link}
				<a href="{if $link.url}{$link.url}{else}#{/if}" {if $link.onclick}onclick="{$link.onclick}"{/if} class="btn btn-sm btn-primary">{$link.title}</a>&nbsp;
			{/foreach}
		</div>
	</div>
	{/foreach}
{else}
	No Copies Found
{/if}
{/strip}