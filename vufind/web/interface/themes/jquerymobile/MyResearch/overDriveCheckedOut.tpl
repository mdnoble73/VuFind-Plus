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
		{if count($overDriveCheckedOutItems) > 0}
			<ul class="results checkedout-list" data-role="listview">
			{foreach from=$overDriveCheckedOutItems item=record}
				<li>
					{if !empty($record.recordId)}<a rel="external" href="{$path}/EcontentRecord/{$record.recordId|escape}">{/if}
					<div class="result">
						<h3>
							{$record.title}
							{if $record.subTitle}<br/>{$record.subTitle}{/if}
						</h3>
						{if strlen($record.record->author) > 0}<p>by: {$record.record->author}</p>{/if}
						<p><strong>Checked Out:</strong> {$record.checkedOutOn}</p>
						<p><strong>Expires:</strong> {$record.expiresOn}</p>
					</div>
					{if !empty($record.recordId)}</a>{/if}
					<div><a href="{$record.downloadLink}" data-role="button" rel="external">Download {$record.format}</a><div>
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
</div>
