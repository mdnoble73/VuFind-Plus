{if count($holdings) > 0}
	{foreach from=$holdings item=eContentItem key=index}
	<div class="eContentHolding">
		<div class="eContentHoldingHeader">
			<span class="eContentHoldingFormat">{if $eContentItem->externalFormat}{$eContentItem->externalFormat}{else}{translate text=$eContentItem->item_type}{/if}</span>{if $showEContentNotes} {$eContentItem->notes}{/if} from {$eContentItem->getSource()}
			<div class="eContentHoldingUsage">
				{$eContentItem->getUsageNotes()}
			</div>
		</div>
		<div class="eContentHoldingNotes">
				{if $eContentItem->size != 0 && strcasecmp($eContentItem->size, 'unknown') != 0}
				Size: {$eContentItem->getSize()|file_size}<br/>
				{/if}
		</div>
		<div class="eContentHoldingActions">
			{if $eContentItem->sampleUrl_1}
				<a href="{$eContentItem->sampleUrl_1}" class="button">{translate text="Sample"}: {$eContentItem->sampleName_1}</a>
			{/if}
			{if $eContentItem->sampleUrl_2}
				<a href="{$eContentItem->sampleUrl_2}" class="button">{translate text="Sample"}: {$eContentItem->sampleName_2}</a>
			{/if}
			{* Options for the user to view online or download *}
			{foreach from=$eContentItem->links item=link}
				<a href="{if $link.url}{$link.url}{else}#{/if}" {if $link.onclick}onclick="{$link.onclick}"{/if} class="button">{$link.text}</a>
			{/foreach}
			{if $user && $user->hasRole('epubAdmin') && ($record->accessType != 'external' && strlen($record->ilsId) > 0)}
				<a href="#" onclick="return editItem('{$id}', '{$eContentItem->id}')" class="button">Edit</a>
				<a href="#" onclick="return deleteItem('{$id}', '{$eContentItem->id}')" class="button">Delete</a>
			{/if}
		</div>
	</div>
	{/foreach}
{else}
	No Copies Found
{/if}

{* Add availability as needed *}
{if $availability && count($availability) > 0}
	<div class="availabilitySection">
		Owned by the following libraries:
		<table class="holdingsTable">
			<thead>
				<tr><th>Library</th><th>Owned</th><th>Available</th></tr>
			</thead>
			<tbody>
				{foreach from=$availability item=availabilityItem}
					<tr><td>{$availabilityItem->getLibraryName()}</td><td>{$availabilityItem->copiesOwned}</td><td>{$availabilityItem->availableCopies}</td></tr>
				{/foreach}
			</tbody>
		</table>
		<div class="note">
			{if strcasecmp($source, 'OverDrive') == 0}
				Note: Copies owned by the Digital library are available to patrons of any Marmot Library.  Titles owned by a specific library are only available for use by patrons of that library. 
			{/if}
		</div>
	</div>
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

