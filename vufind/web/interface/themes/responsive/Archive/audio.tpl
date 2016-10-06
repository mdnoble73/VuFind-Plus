{strip}
	<div class="col-xs-12">
		<h2>
			{$title|escape}
		</h2>

		<img src="{$medium_image}" class="img-responsive">
		<audio width="100%" controls id="player">
			<source src="{$audioLink}" type="audio/mpeg">
		</audio>

		<div id="download-options">
			{if $allowRequestsForArchiveMaterials}
				<a class="btn btn-default" href="{$path}/Archive/RequestCopy?pid={$pid}">Request Copy</a>
			{/if}
		</div>

		{include file="Archive/metadata.tpl"}
	</div>
{/strip}
<script type="text/javascript">
	$().ready(function(){ldelim}
		VuFind.Archive.loadExploreMore('{$pid|urlencode}');
		{rdelim});
</script>