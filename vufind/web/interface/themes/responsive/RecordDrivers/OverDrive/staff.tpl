{if $recordDriver}
	<div class="row">
		<div class="result-label col-xs-2">Grouped Work ID: </div>
		<div class="col-xs-10 result-value">
			{$recordDriver->getPermanentId()}
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12">
			<a href="{$path}/GroupedWork/{$recordDriver->getPermanentId()}" class="btn btn-sm btn-default">Go To Grouped Work</a>
			<button onclick="return VuFind.Record.reloadCover('{$recordDriver->getModule()}', '{$id}')" class="btn btn-sm btn-default">Reload Cover</button>
			<button onclick="return VuFind.GroupedWork.reloadEnrichment('{$recordDriver->getGroupedWorkId()}')" class="btn btn-sm btn-default" >Reload Enrichment</button>
			{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles))}
				<button onclick="return VuFind.GroupedWork.forceReindex('{$recordDriver->getGroupedWorkId()}')" class="btn btn-sm btn-default">Force Reindex</button>
				<button onclick="return VuFind.GroupedWork.forceRegrouping('{$recordDriver->getGroupedWorkId()}')" class="btn btn-sm btn-default">Force Regrouping</button>
				<button onclick="return VuFind.OverDrive.forceUpdateFromAPI('{$recordDriver->getUniqueId()}')" class="btn btn-sm btn-default">Force Update From API</button>
			{/if}
			{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('archives', $userRoles))}
				<button onclick="return VuFind.GroupedWork.reloadIslandora('{$recordDriver->getGroupedWorkId()}')" class="btn btn-sm btn-default">Clear Islandora Cache</button>
			{/if}
		</div>
	</div>

	{* QR Code *}
	{if $showQRCode}
		<div id="record-qr-code" class="text-center hidden-xs visible-md"><img src="{$recordDriver->getQRCodeUrl()}" alt="QR Code for Record"></div>
	{/if}
{/if}

{if $overDriveProductRaw}
	<div id="formattedSolrRecord">
		<h3>OverDrive Product Record</h3>
		{formatJSON subject=$overDriveProductRaw}
		<h3>OverDrive MetaData</h3>
		{formatJSON subject=$overDriveMetaDataRaw}
	</div>
{/if}