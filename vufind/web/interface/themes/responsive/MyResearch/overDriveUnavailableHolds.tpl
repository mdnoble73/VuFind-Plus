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
	{if $user}
		{if $profile.web_note}
			<div id="web_note" class="text-info text-center well well-small">{$profile.web_note}</div>
		{/if}
		
		<h3>{translate text='On Hold in OverDrive'}</h3>
		{if $userNoticeFile}
			{include file=$userNoticeFile}
		{/if}

		{if $overDriveHolds}
			<div class='sortOptions'>
				Hide Covers <input type="checkbox" onclick="$('.imageColumnOverdrive').toggle();"/>
			</div>
		{/if}
		
		{if count($overDriveHolds.unavailable) > 0}
			<div class='holdSectionBody'>
				<table class="myAccountTable">
					<thead>
						<tr><th class='imageColumnOverdrive'></th><th>Title</th><th>Hold Position</th><th></th></tr>
					</thead>
					<tbody>
					{foreach from=$overDriveHolds.unavailable item=record}
						<tr>
							<td rowspan="1" class='imageColumnOverdrive'><img src="{$record.imageUrl}" alt="Cover Image" /></td>
							<td>
								{if $record.recordId != -1}<a href="{$path}/EcontentRecord/{$record.recordId}/Home">{/if}{$record.title}{if $record.recordId != -1}</a>{/if}
								{if $record.subTitle}<br/>{$record.subTitle}{/if}
								{if strlen($record.author) > 0}<br/>by: {$record.author}{/if}
							</td>
							<td>{$record.holdQueuePosition} out of {$record.holdQueueLength}</td>
							<td>
								<a href="#" onclick="cancelOverDriveHold('{$record.overDriveId}','{$record.formatId}')" class="button">Cancel&nbsp;Hold</a><br/>
							</td>
						</tr>
					{/foreach}
					</tbody>
				</table>
			</div>
		{else}
			<p>You do not have any titles on hold in OverDrive.</p>
		{/if}
	{else}
		You must login to view this information. Click <a href="{$path}/MyAccount/Login">here</a> to login.
	{/if}
	</div>
</div>
{/strip}