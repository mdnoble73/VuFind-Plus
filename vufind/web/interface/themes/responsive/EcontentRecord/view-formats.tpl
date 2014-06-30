{strip}
{if count($holdings) > 0}
	{foreach from=$holdings item=eContentItem key=index}
	<div class="eContentHolding">
		<div class="eContentHoldingHeader">
			<div class="row">
				<div class="col-md-9">
					<strong>{$eContentItem->getDisplayFormat()}</strong>
					<div>
						{if $showEContentNotes} <div class="note">{$eContentItem->notes}</div>{/if}
					</div>
				</div>
				<div class="eContentFormatUsage col-md-3">
					{assign var="displayFormat" value=$eContentItem->getDisplayFormat()|substr:0:1}
					<a href="#" onclick="return VuFind.Account.ajaxLightbox('/Help/eContentHelp?lightbox=true&id={$id}&itemId={$eContentItem->id}');">
						{$eContentItem->getHelpText()}
					</a>
				</div>
			</div>

			<div class="eContentHoldingUsage muted">
				{assign var="formatNotes" value=$eContentItem->getFormatNotes()}
				{assign var="usageNotes" value=$eContentItem->getUsageNotes()}
				{$formatNotes}
				{if $formatNotes && $usageNotes}<br/>{/if}
				{$usageNotes}
			</div>
		</div>
		<div class="eContentHoldingNotes">
				{if $eContentItem->size != 0 && strcasecmp($eContentItem->size, 'unknown') != 0}
				Size: {$eContentItem->getSize()|file_size}<br/>
				{/if}
		</div>
		<div class="eContentHoldingActions">
			{if $eContentItem->sampleUrl_1}
				<a href="{$eContentItem->sampleUrl_1}" class="btn">{translate text="Sample"}: {$eContentItem->sampleName_1}</a>&nbsp;
			{/if}
			{if $eContentItem->sampleUrl_2}
				<a href="{$eContentItem->sampleUrl_2}" class="btn">{translate text="Sample"}: {$eContentItem->sampleName_2}</a>&nbsp;
			{/if}
			{* Options for the user to view online or download *}
			{foreach from=$eContentItem->links item=link}
				<a href="{if $link.url}{$link.url}{else}#{/if}" {if $link.onclick}onclick="{$link.onclick}"{/if} class="btn btn-primary">{$link.text}</a>&nbsp;
			{/foreach}
			{if $user && $user->hasRole('epubAdmin') && ($record->accessType != 'external' && strlen($record->ilsId) > 0)}
				<a href="#" onclick="return editItem('{$id}', '{$eContentItem->id}')" class="btn">Edit</a>&nbsp;
				<a href="#" onclick="return deleteItem('{$id}', '{$eContentItem->id}')" class="btn">Delete</a>&nbsp;
			{/if}
		</div>
	</div>
	{/foreach}
	
	<div id="formatHelp">
		Need help?  We have <a href="{$path}/Help/eContentHelp" onclick="return ajaxLightbox('{$path}/Help/eContentHelp?lightbox=true')">step by step instructions</a> for most formats and devices <a href="{$path}/Help/eContentHelp" onclick="return ajaxLightbox('{$path}/Help/eContentHelp?lightbox=true')">here</a>.<br/>
		If you still need help after following the instructions, please fill out this <a href="{$path}/Help/eContentSupport" onclick="return showEContentSupportForm()">support form</a>. 
	</div>
{else}
	No Copies Found
{/if}

{assign var=firstItem value=$holdings.0}
{if strcasecmp($source, 'OverDrive') != 0 && $user && $user->hasRole('epubAdmin')}
	<hr />
	<p>
	<a href="#" onclick="return addItem('{$id}');" class="button">Add Format</a>
	</p>
{/if}

{/strip}