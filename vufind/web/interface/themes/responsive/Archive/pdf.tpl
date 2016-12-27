{strip}
	<div class="col-xs-12">
		{* Search Navigation *}
		{include file="GroupedWork/search-results-navigation.tpl"}
		<h2>
			{$title|escape}
		</h2>
		<div class="row">
			<div id="main-content" class="col-xs-12 hidden-tn hidden-xs text-center">
				{if $canView}
					<div id="pdfContainer">
						<div id="pdfContainerBody">
							<div id="pdfComponentBox">
								<object type="pdf" data="{$pdf}" id="view-pdf">
									<embed type="application/pdf" src="{$pdf}">
								</object>
							</div>
						</div>
					</div>
				{else}
					{include file="Archive/noAccess.tpl"}
				{/if}
			</div>
		</div>

		{if $canView}
			<div id="download-options" class="row">
				<div class="col-xs-12">
					<a class="btn btn-default" href="/Archive/{$pid}/DownloadPDF">Download PDF</a>
					{if $allowRequestsForArchiveMaterials}
						<a class="btn btn-default" href="{$path}/Archive/RequestCopy?pid={$pid}">Request Copy</a>
					{/if}
				</div>
			</div>
		{/if}

		{include file="Archive/metadata.tpl"}
	</div>
{/strip}
<script type="text/javascript">
	$().ready(function(){ldelim}
		VuFind.Archive.loadExploreMore('{$pid|urlencode}');
		{rdelim});
</script>