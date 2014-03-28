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
				<div id="web_note" class="text-info text-center well well-small">{$profile.web_note}</div>
			{/if}
			
			<h3>{translate text='eContent On Hold'}</h3>
			{if $userNoticeFile}
				{include file=$userNoticeFile}
			{/if}

			{if count($holds.unavailable) > 0}
				<div id="holdsUpdateSelected">
					Suspend until (MM/DD/YYYY):
					<input type="text" size="10" name="suspendDateTop" id="suspendDateTop" value="" />
					<script type="text/javascript">{literal}
					$(function() {
						$( "#suspendDateTop" ).datepicker({ minDate: 0, showOn: "both", buttonImage: "{/literal}{$path}{literal}/images/silk/calendar.png", numberOfMonths: 2,	buttonImageOnly: true});
					});{/literal}
					</script>
					<input type="submit" class="button" name="suspendSelected" value="Suspend Selected" title="Suspending a hold prevents the hold from being filled, but keeps your place in queue. This is great if you are going on vacation or want to space out your holds." onclick="return suspendSelectedEContentHolds();"/>
				</div>

				<table class="myAccountTable">
					<thead>
						<tr><th>&nbsp;</th><th>Title</th><th>Source</th><th>Placed</th><th>Position</th><th>Status</th><th>&nbsp;</th></tr>
					</thead>
					<tbody>
					{foreach from=$holds.unavailable item=record}
						<tr>
							<td><input type="checkbox" class="unavailableHoldSelect" name="unavailableHold[{$record.id}]" /></td>
							<td><a href="{$path}/EcontentRecord/{$record.id}/Home">{$record.title}</a></td>
							<td>{$record.source}</td>
							<td>{$record.createTime|date_format}</td>
							<td>{$record.position}</td>
							<td>
								{if $record.frozen}<span class='frozenHold'>{/if}{$record.status} {if $record.frozen}until {$record.reactivateDate|date_format}</span>{/if}
								{if strlen($record.freezeMessage) > 0}
									<div class='{if $record.freezeResult == true}freezePassed{else}freezeFailed{/if}'>
										{$record.freezeMessage|escape}
									</div>
								{/if}
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
				<div class='noItems'>You do not have any eContent on hold</div>
			{/if}

	{else}
		You must login to view this information. Click <a href="{$path}/MyAccount/Login">here</a> to login.
	{/if}
	</div>
</div>
{/strip}