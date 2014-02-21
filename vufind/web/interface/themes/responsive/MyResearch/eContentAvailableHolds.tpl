{strip}
{if (isset($title)) }
<script type="text/javascript">
	alert("{$title}");
</script>
{/if}
<div id="page-content" class="row">
	<div id="sidebar" class="col-md-3">
		{include file="MyResearch/menu.tpl"}
	</div>

	<div id="main-content" class="col-md-9">
		{if $user->cat_username}
			{if $profile.web_note}
				<div id="web_note">{$profile.web_note}</div>
			{/if}

			<div class="myAccountTitle">{translate text='Available eContent Holds'}</div>
			{if $userNoticeFile}
				{include file=$userNoticeFile}
			{/if}

			{if count($holds.available) > 0}
				<table class="myAccountTable">
					<thead>
						<tr><th>Title</th><th>Source</th><th>Placed</th><th>Expires</th><th>Read</th></tr>
					</thead><tbody>
					{foreach from=$holds.available item=record}
						<tr>
							<td><a href="{$path}/EcontentRecord/{$record.id}/Home">{$record.title}</a></td>
							<td>{$record.source}</td>
							<td>{$record.create|date_format}</td>
							<td>{$record.expire|date_format}</td>
							<td>
								{* Options for the user to view online or download *}
								{foreach from=$record.links item=link}
									<a href="{if $link.url}{$link.url}{else}#{/if}" {if $link.onclick}onclick="{$link.onclick}"{/if} class="button">{$link.text}</a>
								{/foreach}
							</td>
						</tr>
					{/foreach}
					</tbody>
				</table>
			{else}
				<div class='noItems'>You do not have any available holds on eContent.</div>
			{/if}

		{else}
			You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
		{/if}
	</div>
</div>
{/strip}