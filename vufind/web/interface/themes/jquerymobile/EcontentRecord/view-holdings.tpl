<script type="text/javascript" src="{$path}/services/EcontentRecord/ajax.js"></script>
<script type="text/javascript" src="{$path}/js/overdrive_mobile.js"></script>
{if count($holdings) > 0}
	{foreach from=$holdings item=eContentItem}
		<div id="itemRow{$eContentItem->id}" data-role="collapsible">
			<h3>{if $eContentItem->externalFormat}{$eContentItem->externalFormat}{else}{translate text=$eContentItem->item_type}{/if}</h3>
			<h4>{if $eContentItem->getAccessType() == 'free'}No Usage Restrictions{elseif $eContentItem->getAccessType() == 'acs' || $eContentItem->getAccessType() == 'singleUse'}Must be checked out to read{/if}</h4>
			{if $showEContentNotes}<td>{$eContentItem->notes}</td>{/if}
			<div data-role="controlgroup">
			
				{* Options for the user to view online or download *}
				{foreach from=$eContentItem->links item=link}
					<a href="{if $link.url}{$link.url}{else}#{/if}" {if $link.onclick}onclick="{$link.onclick}"{/if} data-role="button" >{$link.text}</a>
				{/foreach}
			</div>
		</div>
	{/foreach}
{else}
	No Copies Found
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

{if $sourceUrl}
	<div>
		<a href="{$sourceUrl}" data-role="button" rel="external">Access original files</a>
	</div>
{/if}