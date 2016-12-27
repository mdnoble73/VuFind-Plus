{strip}
	<div class="col-xs-12">
		<h2>
			{$title|escape}
		</h2>

		{if $canView}
			<video width="100%" controls poster="{$medium_image}" id="player">
				<source src="{$videoLink}" type="video/mp4">
			</video>

			<div id="download-options">
				{if $allowRequestsForArchiveMaterials}
					<a class="btn btn-default" href="{$path}/Archive/RequestCopy?pid={$pid}">Request Copy</a>
				{/if}
			</div>
		{else}
			{include file="Archive/noAccess.tpl"}
		{/if}

		{include file="Archive/metadata.tpl"}
	</div>
{/strip}
<script type="text/javascript">
	$().ready(function(){ldelim}
		VuFind.Archive.loadExploreMore('{$pid|urlencode}');
		{rdelim});
</script>