{strip}
	{if count($items) > 0}
		{foreach from=$items item=eContentItem key=index}
			<div class="eContentHolding">
				<div class="eContentHoldingHeader">
					<div class="row">
						<div class="col-md-9">
							<strong>{$eContentItem.format}</strong>
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
		<div id="formatHelp">
			Need Help?<br />
			If you are having problem transferring a title to your device, please fill out <a href="#" rel="external" onclick="return VuFind.Account.ajaxLightbox('/Help/AJAX?method=getSupportForm', true)">this support form</a> or visit the library so we can help you to use our eBooks and eAudio Books.
		</div>
	{else}
		<p class="alert alert-warning">
			No Formats Found
		</p>
	{/if}
{/strip}