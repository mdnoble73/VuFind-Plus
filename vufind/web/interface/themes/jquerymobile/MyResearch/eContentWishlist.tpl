<script type="text/javascript" src="{$path}/services/MyResearch/ajax.js"></script>
{if (isset($title)) }
<script type="text/javascript">
    alert("{$title}");
</script>
{/if}
<div data-role="page" id="MyResearch-checkedout-overdrive">
	{include file="header.tpl"}
	<div data-role="content">
	{if $user}
		{if count($wishList) > 0}
			<ul class="results checkedout-list" data-role="listview">
			{foreach from=$wishList item=record}
				<li>
					{if !empty($record->recordId)}<a rel="external" href="{$path}/EcontentRecord/{$record->recordId|escape}">{/if}
					<div class="result">
					<h3>
						{$record->title}
						{if $record->subTitle}<br/>{$record->subTitle}{/if}
					</h3>
					<p><strong>Source: </strong>{$record->source}</p>
					<p><strong>Date Added: </strong>{$record->dateAdded|date_format}</p>
					</div>
					{if !empty($record->recordId)}</a>{/if}
					{* Options for the user to view online or download *}
					<div data-role="controlgroup">
					{foreach from=$record->links item=link}
						<a href="{$link.url}" data-role="button" rel="external">{$link.text}</a>
					{/foreach}
					</div>
				</li>
			{/foreach}
			</ul>
		{else}
			<div class='noItems'>You do not have any eContent in your wish list.</div>
		{/if}
	{else}
		You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
	{/if}
	</div>
</div>
