{strip}
{if (isset($title)) }
<script type="text/javascript">
		alert("{$title}");
</script>
{/if}
<div id="page-content" class="row-fluid">
	<div id="sidebar" class="span3">
		{include file="MyResearch/menu.tpl"}
			
		{include file="Admin/menu.tpl"}
	</div>
	
	<div id="main-content" class="span9">
		{if $user->cat_username}
			{if $profile.web_note}
				<div id="web_note" class="text-info text-center well well-small">{$profile.web_note}</div>
			{/if}
					
			<div class="myAccountTitle">{translate text='Your Checked Out eContent'}</div>
			{if $userNoticeFile}
				{include file=$userNoticeFile}
			{/if}
			
			{if $transList}
				<div class='sortOptions'>
					{*
					{translate text='Sort by'}
					<select name="sort" id="sort" onchange="changeSort($(this).val());">
					{foreach from=$sortOptions item=sortDesc key=sortVal}
						<option value="{$sortVal}"{if $defaultSortOption == $sortVal} selected="selected"{/if}>{translate text=$sortDesc}</option>
					{/foreach}
					</select> *}
					Hide Covers <input type="checkbox" onclick="$('.imageColumn').toggle();"/>
				</div>
				
			{/if}
			
			{if count($checkedOut) > 0}
				<table class="myAccountTable">
					<thead>
						<tr><th>Title</th><th>Source</th><th>Out</th><th>Due</th><th>Wait List</th><th>Rating</th><th>Read</th></tr>
					</thead>
					<tbody>
					{foreach from=$checkedOut item=record}
						<tr>
							<td><a href="{$path}/EcontentRecord/{$record.id}/Home">{$record.title}</a></td>
							<td>{$record.source}</td>
							<td>{$record.checkoutdate|date_format}</td>
							<td>
								{$record.duedate|date_format}
								{if $record.overdue}
									<span class='overdueLabel'>OVERDUE</span>
								{elseif $record.daysUntilDue == 0}
									<span class='dueSoonLabel'>(Due today)</span>
								{elseif $record.daysUntilDue == 1}
									<span class='dueSoonLabel'>(Due tomorrow)</span>
								{elseif $record.daysUntilDue <= 7}
									<span class='dueSoonLabel'>(Due in {$record.daysUntilDue} days)</span>
								{/if}
							</td>
							<td>{$record.holdQueueLength}</td>
							<td>
								<div class="resultActions">
									{include file="EcontentRecord/title-rating.tpl" ratingClass="" recordId=$record.id shortId=$record.id ratingData=$record.ratingData}
									{assign var=id value=$record.recordId}
									{include file="EcontentRecord/title-review.tpl"}
								</div>
							</td>
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
				<div class='noItems'>You do not have any eContent checked out</div>
			{/if}
	{else}
		You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
	{/if}
	</div>
</div>
{/strip}