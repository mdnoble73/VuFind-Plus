{strip}
	<div class="col-xs-12">
		<h2>
			{$title|escape}
		</h2>

		<video width="100%" controls poster="{$medium_image}" id="player">
			<source src="{$videoLink}" type="video/mp4">
		</video>

		{include file="Archive/metadata.tpl"}
	</div>
{/strip}
<script type="text/javascript">
	$().ready(function(){ldelim}
		VuFind.Archive.loadExploreMore('{$pid|urlencode}');
		{rdelim});
</script>