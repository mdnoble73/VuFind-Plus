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
				<button class="btn btn-default">Download Large Image</button>
			{elseif (!$user && $verifiedLcDownload)}
				<button class="btn btn-default">Login to Download Large Image</button>
			{/if}
			{if $anonymousMasterDownload || ($user && $verifiedMasterDownload)}
				<button class="btn btn-default">Download Original Image</button>
			{elseif (!$user && $verifiedLcDownload)}
				<button class="btn btn-default">Login to Download Original Image</button>
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
