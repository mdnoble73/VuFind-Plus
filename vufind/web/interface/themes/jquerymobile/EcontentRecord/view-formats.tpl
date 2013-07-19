{strip}

<div data-role="collapsible-set"></div>
{if count($holdings) > 0}
	{foreach from=$holdings item=eContentItem key=index}
	<div id="itemRow{$eContentItem->id}" data-role="collapsible">
		<h3 class="eContentHoldingFormat">{$eContentItem->getDisplayFormat()}</h3>
		{if $showEContentNotes} {$eContentItem->notes}{/if}
		<div class="eContentHoldingUsage">
			{$eContentItem->getFormatNotes()}
		</div>
		<div class="eContentFormatUsage">
			{assign var="displayFormat" value=$eContentItem->getDisplayFormat()|substr:0:1}
			<a href='/Help/eContentHelp?id={$id}&itemId={$eContentItem->id}' data-ajax="false">How to use a{if preg_match("/[aeiou]/i", $displayFormat)}n{/if} {$eContentItem->getDisplayFormat()}</a>
		</div>
		<div class="eContentHoldingNotes">
				{if $eContentItem->size != 0 && strcasecmp($eContentItem->size, 'unknown') != 0}
				Size: {$eContentItem->getSize()|file_size}<br/>
				{/if}
		</div>
		<div class="eContentHoldingActions">
			{if $eContentItem->sampleUrl_1}
				<a href="{$eContentItem->sampleUrl_1}" data-role="button">{translate text="Sample"}: {$eContentItem->sampleName_1}</a>
			{/if}
			{if $eContentItem->sampleUrl_2}
				<a href="{$eContentItem->sampleUrl_2}" data-role="button">{translate text="Sample"}: {$eContentItem->sampleName_2}</a>
			{/if}
			{* Options for the user to view online or download *}
			{foreach from=$eContentItem->links item=link}
				<a href="{if $link.url}{$link.url}{else}#{/if}" {if $link.onclick}onclick="{$link.onclick}"{/if} data-role="button">{$link.text}</a>
			{/foreach}
		</div>
	</div>
	{/foreach}

	<div id="formatHelp">
		Need help?  We have <a href="{$path}/Help/eContentHelp">step by step instructions</a> for most devices <a href="{$path}/Help/eContentHelp">here</a>.<br/>
		If you still need help after following the instructions, please fill out this <a href="{$path}/Help/eContentSupport" rel="external" onclick="return showEContentSupportForm()">support form</a>.
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