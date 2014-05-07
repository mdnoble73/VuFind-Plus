{strip}
{if count($items) > 0}
	{foreach from=$items item=item key=index}
	<div class="eContentHolding">
		<div class="eContentHoldingHeader">
			<div class="row">
				<div class="col-md-9">
					<strong>{$item.locationLabel}</strong>
				</div>
			</div>
		</div>
		<div class="eContentHoldingActions">
			{* Options for the user to view online or download *}
			{foreach from=$item.actions item=link}
				<a href="{if $link.url}{$link.url}{else}#{/if}" {if $link.onclick}onclick="{$link.onclick}"{/if} class="btn btn-sm btn-primary">{$link.title}</a>&nbsp;
			{/foreach}
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


{/strip}