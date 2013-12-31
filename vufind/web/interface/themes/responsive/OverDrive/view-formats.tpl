{strip}

<div data-role="collapsible-set"></div>
{if count($holdings) > 0}
	{foreach from=$holdings item=overDriveFormat key=index}
	<div id="itemRow{$overDriveFormat->id}" data-role="collapsible">
		<h3 class="eContentHoldingFormat">{$overDriveFormat->name}</h3>
		{if $showEContentNotes} {$overDriveFormat->notes}{/if}
		<div class="eContentHoldingUsage">
			{$overDriveFormat->getFormatNotes()}
		</div>
		<div class="eContentFormatUsage">
			{assign var="displayFormat" value=$overDriveFormat->name|substr:0:1}
			<a href='/Help/eContentHelp?id={$id}&itemId={$overDriveFormat->id}' data-ajax="false">How to use a{if preg_match("/[aeiou]/i", $displayFormat)}n{/if} {$overDriveFormat->name}</a>
		</div>
		<div class="eContentHoldingNotes">
				{if $overDriveFormat->size != 0 && strcasecmp($overDriveFormat->size, 'unknown') != 0}
				Size: {$overDriveFormat->fileSize|file_size}<br/>
				{/if}
		</div>
		<div class="eContentHoldingActions">
			{if $overDriveFormat->sampleUrl_1}
				<a href="{$overDriveFormat->sampleUrl_1}" data-role="button">{translate text="Sample"}: {$overDriveFormat->sampleName_1}</a>
			{/if}
			{if $overDriveFormat->sampleUrl_2}
				<a href="{$overDriveFormat->sampleUrl_2}" data-role="button">{translate text="Sample"}: {$overDriveFormat->sampleName_2}</a>
			{/if}
			{* Options for the user to view online or download *}
			{foreach from=$overDriveFormat->links item=link}
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

{/strip}