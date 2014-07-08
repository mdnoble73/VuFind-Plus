{strip}
	{if count($items) > 0}
		{foreach from=$items item=eContentItem key=index}
			<div class="eContentHolding">
				<div class="eContentHoldingHeader">
					<div class="row">
						<div class="col-md-9">
							<strong>{$eContentItem.format}</strong>
						</div>
						<div class="eContentFormatUsage col-md-3">
							{assign var="displayFormat" value=$eContentItem.format|substr:0:1}
							<a href="#" onclick="return VuFind.Account.ajaxLightbox('/Help/eContentHelp?lightbox=true&id={$id}&itemId={$eContentItem->id}');">
								{$eContentItem.helpText}
							</a>
						</div>
					</div>

					<div class="eContentHoldingUsage muted">
						{assign var="formatNotes" value=$eContentItem.formatNotes}
						{assign var="usageNotes" value=$eContentItem.usageNotes}
						{$formatNotes}
						{if $formatNotes && $usageNotes}<br/>{/if}
						{$usageNotes}
					</div>
				</div>
				<div class="eContentHoldingNotes">
					{if $eContentItem.size != 0 && strcasecmp($eContentItem.size, 'unknown') != 0}
						Size: {$eContentItem.size|file_size}<br/>
					{/if}
				</div>
				<div class="eContentHoldingActions">
					{* Options for the user to view online or download *}
					{foreach from=$eContentItem.actions item=link}
						{if $link.showInFormats || !$link.showInSummary}
							<a href="{if $link.url}{$link.url}{else}#{/if}" {if $link.onclick && strlen($link.onclick) > 0}onclick="{$link.onclick}"{/if} class="btn btn-xs btn-primary">{$link.title}</a>&nbsp;
						{/if}
					{/foreach}
				</div>
			</div>
		{/foreach}
	{else}
		<p class="alert alert-warning">
			No Formats Found
		</p>
	{/if}
	<div id="formatHelp" class="alert alert-info">
		Need help?  We have <a href="{$path}/Help/eContentHelp" onclick="return VuFind.Account.ajaxLightbox('{$path}/Help/eContentHelp?lightbox=true', false)">step by step instructions</a> for most formats and devices <a href="{$path}/Help/eContentHelp"  onclick="return VuFind.Account.ajaxLightbox('{$path}/Help/eContentHelp?lightbox=true', false)">here</a>.<br/>
		If you still need help after following the instructions, please fill out this <a href="{$path}/Help/eContentSupport" onclick="return VuFind.Account.ajaxLightbox('{$path}/Help/eContentSupport?lightbox=true', true)">support form</a>.
	</div>
{/strip}