{strip}
	<div class="col-xs-12">
		<h2>
			{$title|escape}
		</h2>

		<img src="{$medium_image}" class="img-responsive">
		<audio width="100%" controls id="player">
			<source src="{$audioLink}" type="audio/mpeg">
		</audio>

		<div id="image-download-options">
			{if $allowRequestsForArchiveMaterials}
				<a class="btn btn-default" href="{$path}/Archive/RequestCopy?pid={$pid}">Request Copy</a>
			{/if}
		</div>

{*//Moved to accordion
		{if $description}
			<div class="row">
				<div class="result-label col-sm-4">Description: </div>
				<div class="col-sm-8 result-value">
					{$description}
				</div>
			</div>
		{/if}
*}

{*//Moved to accordion
		{if $transcription}
			<div class="row">
				<div class="result-label col-xs-12">Transcription: </div>
				<div class="col-xs-12 result-value">
					{$transcription.text}
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