<script type="text/javascript" src="{$path}/services/MyResearch/ajax.js"></script>
{if (isset($title)) }
<script type="text/javascript">
	alert("{$title}");
</script>
{/if}
<div id="page-content" class="content">
	<div id="sidebar">
		{include file="MyResearch/menu.tpl"}
		{include file="Admin/menu.tpl"}
	</div>
  
	<div id="main-content">
	{if $user}
		<div class="myAccountTitle">{translate text='Your OverDrive Wish List'}</div>
		{if $userNoticeFile}
			{include file=$userNoticeFile}
		{/if}

		{if $overDriveWishList}
			<div class='sortOptions'>
				Hide Covers <input type="checkbox" onclick="$('.imageColumnOverdrive').toggle();"/>
			</div>
		{/if}

		{if count($overDriveWishList) > 0}
			<table class="myAccountTable">
				<thead>
					<tr><th class='imageColumnOverdrive'></th><th>Title</th><th>Author</th><th>Date Added</th><th></th></tr>
				</thead>
				<tbody>
				{foreach from=$overDriveWishList item=record}
					<tr>
						<td rowspan="{$record.numRows}" class='imageColumnOverdrive'><img src="{$record.imageUrl}"></td>
						<td>{if $record.recordId != -1}<a href="{$path}/EcontentRecord/{$record.recordId}/Home">{/if}{$record.title}{if $record.recordId != -1}</a>{/if}{if $record.subTitle}<br/>{$record.subTitle}{/if}</td>
						<td>{$record.author}</td>
						<td>{$record.dateAdded}</td>
						<td>
							<a href="#" onclick="removeOverDriveRecordFromWishList('{$record.overDriveId}')" class="button">Remove</a><br/>
						</td>
					</tr>
					{foreach from=$record.formats item=format}
					<tr class="overDriveFormat">
						<td colspan="3">{$format.name}</td>
						<td>
							{if $format.available}
							<a href="#" onclick="checkoutOverDriveItem('{$record.overDriveId}', '{$format.formatId}')" class="button">Check&nbsp;Out</a><br/>
							{else}
							<a href="#" onclick="placeOverDriveHold('{$record.overDriveId}', '{$format.formatId}')" class="button">Place&nbsp;Hold</a><br/>
							{/if}
						</td>
					</tr>
					{/foreach}
				{/foreach}
				</tbody>
			</table>
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
