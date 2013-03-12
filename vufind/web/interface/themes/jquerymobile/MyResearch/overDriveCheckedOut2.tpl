{strip}
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
		{if $profile.web_note}
			<div id="web_note">{$profile.web_note}</div>
		{/if}

		{if count($overDriveCheckedOutItems) > 0}
			<ul class="results checkedout-list" data-role="listview">
				{foreach from=$overDriveCheckedOutItems item=record}
					<li>
						{if !empty($record.recordId) && $record.recordId != -1}<a rel="external" href="{$path}/EcontentRecord/{$record.recordId|escape}">{/if}
							<div class="result">
							<h3>
								{$record.title}
								{if $record.subTitle}<br/>{$record.subTitle}{/if}
							</h3>
							{if strlen($record.record->author) > 0}<p>by: {$record.record->author}</p>{/if}
							<p><strong>Expires:</strong> {$record.expiresOn|replace:' ':'&nbsp;'}</p>
							</div>
						{if !empty($record.recordId)}</a>{/if}
						<div data-role="controlgroup">
							{if $record.formatSelected}
								<a href="{$record.downloadUrl|replace:'&':'&amp;'}" data-role="button" rel="external">Download&nbsp;{$record.selectedFormat.name}&nbsp;Again</a>
							{else}
								<label for="downloadFormat_{$record.overDriveId}">Select one format to download.</label>
								<select name="downloadFormat_{$record.overDriveId}" id="downloadFormat_{$record.overDriveId}">
									<option value="-1">Select a Format</option>
									{foreach from=$record.formats item=format}
										<option value="{$format.id}">{$format.name}</option>
									{/foreach}
								</select>
								<a href="#" onclick="selectOverDriveDownloadFormat('{$record.overDriveId}')" data-role="button" rel="external">Download</a>
							{/if}
							{if $record.earlyreturn}
								<a href="#" onclick="returnOverDriveTitle('{$record.overDriveId}', '{$record.transactionId}');" data-role="button" rel="external">Return&nbsp;Now</a>
							{/if}
							{if $record.overdriveRead}
								<a href="{$record.overdriveReadUrl}" data-role="button" rel="external">Read&nbsp;Online with OverDrive&nbsp;Read</a>
							{/if}
						</div>
					</li>
				{/foreach}
			</ul>
		{else}
			<div class='noItems'>You do not have any titles from OverDrive checked out</div>
		{/if}
	{else}
		You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
	{/if}
	</div>
	{include file="footer.tpl"}
</div>
{/strip}