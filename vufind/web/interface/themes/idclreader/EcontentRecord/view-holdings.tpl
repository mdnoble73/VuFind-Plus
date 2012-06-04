
{if count($holdings) > 0}

	<div data-role="content">
	
		<ul data-role="listview" id='headerListAccessOnline'>
			<li data-role="list-divider">Copies</li>
								
			{foreach from=$holdings item=eContentItem key=index}
				<li>
					{if get_class($eContentItem) == 'OverdriveItem'}
						{foreach from=$eContentItem->links item=link}
							{if $link.action eq "CheckOut"}
								<a href="/Mobile/ODLoanPeriod?overDriveId={$link.overDriveId}&formatId={$link.formatId}">
							{else}
								<a href="/Mobile/ODPlaceHold?overDriveId={$link.overDriveId}&formatId={$link.formatId}">
							{/if}
									<span class="iconlabel {$eContentItem->format|lower}">Kindle Book</span>	
									{$link.text} @ OverDrive
								</a>
						{/foreach}
					{else}
						 	{if $eContentItem->links|@count gt 0}
								{foreach from=$eContentItem->links key=k item=link}
									<a	 {if $k eq 1}data-icon='delete'{/if}
										 href="{if $link.url}{$link.url}{else}#{/if}"
										 {if !$link.onclick}target='_blank'{/if}
										 {if $link.onclick}onclick="{$link.onclick}"{/if} class="button"
									>
									{if $k eq 0}
										<img src="/interface/themes/{$theme}/images/{$eContentItem->item_type}.png" class="ui-li-icon">
									 {/if}
									 {$link.text}&nbsp;{if $showEContentNotes}<span style='font-size:small;'>({$eContentItem->notes})</span>{/if}
									</a>
								{/foreach}
							{else}
								<img src="/interface/themes/{$theme}/images/{$eContentItem->item_type}.png" class="ui-li-icon">
								Source: {$eContentItem->source}<br>
								{if $showEContentNotes}Notes: {$eContentItem->notes}<br>{/if}
								Usage: {if $eContentItem->getAccessType() == 'free'}No Usage Restrictions{elseif $eContentItem->getAccessType() == 'acs' || $eContentItem->getAccessType() == 'singleUse'}Must be checked out to read{/if}<br>
								Size:{$eContentItem->getSize()|file_size}
							{/if}
						
					{/if}
				</li>
			{/foreach}
		</ul>
	</div>
{else}
	<ul data-role="listview" id='headerListAccessOnline'>
		<li data-role="list-divider">Copies</li>
		<li>
			No Copies Found
		</li>
	</ul>
{/if}