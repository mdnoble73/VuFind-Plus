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
		{if $user->cat_username}
			{if $profile.web_note}
				<div id="web_note">{$profile.web_note}</div>
			{/if}
		
			<div class="myAccountTitle">{translate text='Your eContent Wish List'}</div>
			{if $userNoticeFile}
				{include file=$userNoticeFile}
			{/if}
			
		{if count($wishList) > 0}
			<table class="myAccountTable">
				<thead>
					<tr><th>Title</th><th>Source</th><th>Date Added</th><th>&nbsp;</th></tr>
				</thead>
				<tbody>
			
				{foreach from=$wishList item=record}
					<tr>
					<td><a href="{$path}/EcontentRecord/{$record->recordId}/Home">{$record->title}</a></td>
						<td>{$record->source}</td>
						<td>{$record->dateAdded|date_format}</td>
						<td>
							{* Options for the user to view online or download *}
							{foreach from=$record->links item=link}
								<a href="{$link.url}" class="button">{$link.text}</a>
							{/foreach}
						</td>
					</tr>
				{/foreach}
				</tbody>
			</table>
		{else}
			<div class='noItems'>You do not have any eContent in your wish list.</div>
		{/if}
			
	{else}
		You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
	{/if}
	</div>
</div>
