<script type="text/javascript" src="{$path}/services/EcontentRecord/ajax.js"></script>
<script type="text/javascript" src="{$path}/js/overdrive.js"></script>
{if count($holdings) > 0}
	{foreach from=$holdings item=eContentItem}
		<div id="itemRow{$eContentItem->id}">
			{if get_class($eContentItem) == 'OverdriveItem'}
				<h3>{$eContentItem->format}</h3>
			{else}
				<h3>{$eContentItem->source} {translate text=$eContentItem->item_type}</h3>
				<h4>{if $eContentItem->getAccessType() == 'free'}No Usage Restrictions{elseif $eContentItem->getAccessType() == 'acs' || $eContentItem->getAccessType() == 'singleUse'}Must be checked out to read{/if}</h4>
				{if $showEContentNotes}<td>{$eContentItem->notes}</td>{/if}
			{/if}
			<div data-role="controlgroup">
			
				{* Options for the user to view online or download *}
				{foreach from=$eContentItem->links item=link}
					<a href="{if $link.url}{$link.url}{else}#{/if}" {if $link.onclick}onclick="{$link.onclick}"{/if} data-role="button" rel="external">{$link.text}</a>
				{/foreach}
			</div>
		</div>
	{/foreach}
{else}
	No Copies Found
{/if}

{if $sourceUrl}
  <div>
  	<a href="{$sourceUrl}" data-role="button" rel="external">Access original files</a>
  </div>
{/if}