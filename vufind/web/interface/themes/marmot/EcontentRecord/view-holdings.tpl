{if count($holdings) > 0}
	{foreach from=$holdings item=eContentItem key=index}
	<div class="eContentHolding">
		{if get_class($eContentItem) == 'OverdriveItem'}
			<div class="eContentHoldingHeader">
				<span class="eContentHoldingFormat">{translate text=$eContentItem->format}</span> from {$eContentItem->source}
				<div class="eContentHoldingUsage">
					{$eContentItem->getUsageNotes()}
				</div>
			</div>
			<div class="eContentHoldingNotes">
					{if $showEContentNotes}{$eContentItem->notes}{/if}
					{if $showSize}
					Size: {$eContentItem->size}<br/>
					{/if}
			</div>
			<div class="eContentHoldingActions">
			{* Options for the user to view online or download *}
			{foreach from=$eContentItem->links item=link}
				<a href="{if $link.url}{$link.url}{else}#{/if}" {if $link.onclick}onclick="{$link.onclick}"{/if} class="button">{$link.text}</a>
			{/foreach}
			</div>
		{else}
			<div class="eContentHoldingHeader">
				<span class="eContentHoldingFormat">{translate text=$eContentItem->item_type}</span>{if $showEContentNotes} {$eContentItem->notes}{/if} from {$eContentItem->source}
				<div class="eContentHoldingUsage">
					{$eContentItem->getUsageNotes()}
				</div>
			</div>
			<div class="eContentHoldingNotes">
					{if $showSize}
					Size: {$eContentItem->getSize()|file_size}<br/>
					{/if}
			</div>
			<div class="eContentHoldingActions">
				{* Options for the user to view online or download *}
				{foreach from=$eContentItem->links item=link}
					<a href="{if $link.url}{$link.url}{else}#{/if}" {if $link.onclick}onclick="{$link.onclick}"{/if} class="button">{$link.text}</a>
				{/foreach}
				{if $user && $user->hasRole('epubAdmin')}
					<a href="#" onclick="return editItem('{$id}', '{$eContentItem->id}')" class="button">Edit</a>
					<a href="#" onclick="return deleteItem('{$id}', '{$eContentItem->id}')" class="button">Delete</a>
				{/if}
			</div>
		{/if}
	</div>
	{/foreach}
{else}
	No Copies Found
{/if}

{assign var=firstItem value=$holdings.0}
{if strcasecmp($source, 'OverDrive') == 0}
	<a href="#" onclick="return addOverDriveRecordToWishList('{$id}')" class="button">Add&nbsp;to&nbsp;Wish&nbsp;List</a>
{/if}
{if strcasecmp($source, 'OverDrive') != 0 && $user && $user->hasRole('epubAdmin')}
	<a href="#" onclick="return addItem('{$id}');" class="button">Add Item</a>
{/if}

{if strcasecmp($source, 'OverDrive') == 0}
	<div id='overdriveMediaConsoleInfo'>
		<img src="{$path}/images/overdrive.png" width="125" height="42" alt="Powered by Overdrive" class="alignleft"/>
		<p>This title requires the <a href="http://www.overdrive.com/software/omc/">OverDrive&reg; Media Console&trade;</a> to use the title.  
		If you do not already have the OverDrive Media Console, you may download it <a href="http://www.overdrive.com/software/omc/">here</a>.</p>
		<div class="clearer">&nbsp;</div> 
		<p>Need help transferring a title to your device or want to know whether or not your device is compatible with a particular format?
		Click <a href="http://help.overdrive.com">here</a> for more information. 
		</p>
		 
	</div>
{/if}

