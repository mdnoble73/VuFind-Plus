<script type="text/javascript" src="{$url}/services/MyResearch/ajax.js"></script>
<script type="text/javascript" src="{$url}/js/overdrive.js"></script>
{if (isset($title)) }
<script type="text/javascript">
	alert("{$title}");
</script>
{/if}
<div data-role="page" id="MyResearch-checkedout-overdrive">
	{include file="header.tpl"}
	<div data-role="content">
	{if $user}

		{if count($overDriveWishList) > 0}
			<ul class="results checkedout-list" data-role="listview">
			{foreach from=$overDriveWishList item=record}
				<li>
					{if !empty($record.recordId)}<a rel="external" href="{$path}/EcontentRecord/{$record.recordId|escape}">{/if}
					<div class="result">
					<h3>
						{$record.title}
						{if $record.subTitle}<br/>{$record.subTitle}{/if}
					</h3>
					{if strlen($record.record->author) > 0}<p>by: {$record.record->author}</p>{/if}
					<p><strong>Added:</strong> {$record.dateAdded}</p>
					</div>
					{if !empty($record.recordId)}</a>{/if}
					<div data-role="controlgroup">
						<a href="#" onclick="removeOverDriveRecordFromWishList('{$record.overDriveId}')" data-role="button" rel="external">Remove</a>
						{foreach from=$record.formats item=format}
							{if $format.available}
							<a href="#" onclick="checkoutOverDriveItem('{$record.overDriveId}', '{$format.formatId}')" data-role="button" rel="external">Check&nbsp;Out {$format.name}</a>
							{else}
							<a href="#" onclick="placeOverDriveHold('{$record.overDriveId}', '{$format.formatId}')" data-role="button" rel="external">Place&nbsp;Hold on {$format.name}</a>
							{/if}
						{/foreach}
					</div>
				</li>
			{/foreach}
			</ul>
		{elseif $error}
			<div class='error'>{$error}</div>
		{else}
			<div class='noItems'>You have not added any titles to your wishlist.</div>
		{/if}
	{else}
		You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
	{/if}
	</div>
</div>
