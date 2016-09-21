{strip}
<div class="col-xs-12">
	{if $main_image}
		<div class="main-project-image">
			<img src="{$main_image}" class="img-responsive" usemap="#map">
		</div>
	{/if}

	<h2>
		{$title|escape}
	</h2>

	<div class="lead">
		{if $thumbnail && !$main_image}
			<img src="{$thumbnail}" class="img-responsive thumbnail exhibit-thumbnail">
		{/if}
		{$description}
	</div>

	<div class="clear-both"></div>
</div>
<div class="row">
	{foreach from=$collectionTemplates item=template}
		{include file=$template}
	{/foreach}
</div>
{/strip}

<script type="text/javascript">
	$().ready(function(){ldelim}
		VuFind.Archive.loadExploreMore('{$pid|urlencode}');
		{rdelim});
</script>