{strip}
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

			{* Display recommendations for the user *}
			{if $showStrands && $user->disableRecommendations == 0}
				{assign var="scrollerName" value="Recommended"}
				{assign var="wrapperId" value="recommended"}
				{assign var="scrollerVariable" value="recommendedScroller"}
				{assign var="scrollerTitle" value="Recommended for you"}
				{include file=titleScroller.tpl}

				<script type="text/javascript">
					var recommendedScroller;

					recommendedScroller = new TitleScroller('titleScrollerRecommended', 'Recommended', 'recommended');
					recommendedScroller.loadTitlesFrom('{$path}/Search/AJAX?method=GetListTitles&id=strands:HOME-3&scrollerName=Recommended', false);
				</script>
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
<script type="text/javascript">
	$(document).ready(function() {literal} { {/literal}
		doGetRatings();
	{literal} }); {/literal}
</script>
{/strip}