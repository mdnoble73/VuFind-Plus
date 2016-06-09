{strip}
	<div class="col-xs-12">
		<h2>
			{$title|escape}
		</h2>

		<div class="main-project-image">
			{* TODO: restrict access to original image *}
			{if $anonymousMasterDownload || ($user && $verifiedMasterDownload)}
				<a href="{$original_image}">
			{/if}
				<img src="{$large_image}" class="img-responsive">
			{if $anonymousMasterDownload || ($user && $verifiedMasterDownload)}
				</a>
			{/if}
		</div>

		<div id="image-download-options">
			{if $anonymousLcDownload || ($user && $verifiedLcDownload)}
				<a class="btn btn-default" href="/Archive/{$pid}/DownloadLC">Download Large Image</a>
			{elseif (!$user && $verifiedLcDownload)}
				<a class="btn btn-default" onclick="return VuFind.Account.followLinkIfLoggedIn(this)" href="/Archive/{$pid}/DownloadLC">Login to Download Large Image</a>
			{/if}
			{if $anonymousMasterDownload || ($user && $verifiedMasterDownload)}
				<a class="btn btn-default" href="/Archive/{$pid}/DownloadOriginal">Download Original Image</a>
			{elseif (!$user && $verifiedLcDownload)}
				<a class="btn btn-default" onclick="return VuFind.Account.followLinkIfLoggedIn(this)" href="/Archive/{$pid}/DownloadOriginal">Login to Download Original Image</a>
			{/if}
		</div>

{* //Moved to accordion
		{if $description}
			<div class="row">
				<div class="result-label col-sm-4">Description: </div>
				<div class="col-sm-8 result-value">
					{$description}
				</div>
			</div>
		{/if}
*}

		{include file="Archive/metadata.tpl"}
	</div>
{/strip}
<script type="text/javascript">
	$().ready(function(){ldelim}
		VuFind.Archive.loadExploreMore('{$pid|urlencode}');
		{rdelim});
</script>
