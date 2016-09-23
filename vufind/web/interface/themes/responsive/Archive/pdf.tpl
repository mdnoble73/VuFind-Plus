{strip}
	<div class="col-xs-12">
		{* Search Navigation *}
		{include file="GroupedWork/search-results-navigation.tpl"}
		<h2>
			{$title|escape}
		</h2>
		<div class="row">
			<div id="main-content" class="col-xs-12 text-center">

				<div id="view-pdf" width="100%" height="600px">
					<object type="pdf" class="book-pdf" data="{$pdf}">
						<embed type="application/pdf" src="{$pdf}" width="100%" height="600px">
					</object>
				</div>
			</div>
		</div>

		{include file="Archive/metadata.tpl"}
	</div>
{/strip}
<script type="text/javascript">
	$().ready(function(){ldelim}
		VuFind.Archive.loadExploreMore('{$pid|urlencode}');
		{rdelim});
</script>