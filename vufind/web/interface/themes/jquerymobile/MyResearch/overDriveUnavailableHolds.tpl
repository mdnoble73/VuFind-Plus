{strip}
{if (isset($title)) }
<script type="text/javascript">
	alert("{$title}");
</script>
{/if}
<div data-role="page" id="MyResearch-checkedout-overdrive">
	{include file="header.tpl"}
	<div data-role="content">
	{if $user}
		{if $profile.web_note}
			<div id="web_note">{$profile.web_note}</div>
		{/if}
		<h3 class="myAccountTitle">{translate text='On Hold in OverDrive'}</h3>
		{if count($overDriveHolds.unavailable) > 0}
			<div class='holdSection'>
				<div class='holdSectionBody'>
					<ul class="results checkedout-list" data-role="listview">
						{foreach from=$overDriveHolds.unavailable item=record}
							<li>
								<div class="result">
							<h3>
								{$record.title}
								{if $record.subTitle}<br/>{$record.subTitle}{/if}
							</h3>
							{if strlen($record.record->author) > 0}<p>by: {$record.record->author}</p>{/if}
							<p><strong>Position: {$record.holdQueuePosition} out of {$record.holdQueueLength}</p>
							</div>
							{if !empty($record.recordId)}</a>{/if}
							<div data-role="controlgroup">
								<a href="#" onclick="cancelOverDriveHold('{$record.overDriveId}','{$record.formatId}')" data-role="button" rel="external">Remove</a>
							</div>
						{/foreach}
						</tbody>
					</table>
				</div>
			</div>
		{else}
			<p>You do not have any titles on hold in OverDrive.</p>
		{/if}
	{else}
		You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
	{/if}
	</div>
	{include file="footer.tpl"}
</div>
{/strip}