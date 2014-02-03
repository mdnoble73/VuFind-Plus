{strip}
	<div id="more-details-accordion" class="panel-group">
		{foreach from=$moreDetailsOptions key="moreDetailsKey" item="moreDetailsOption"}
			<div class="panel" id="{$moreDetailsKey}Panel" {if $moreDetailsOption.hideByDefault}style="display:none"{/if}>
				<a data-toggle="collapse" href="#{$moreDetailsKey}PanelBody">
					<div class="panel-heading">
						<div class="panel-title">
							{$moreDetailsOption.label}
						</div>
					</div>
				</a>
				<div id="{$moreDetailsKey}PanelBody" class="panel-collapse collapse">
					<div class="panel-body">
						{$moreDetailsOption.body}
					</div>
				</div>
			</div>
		{/foreach}
	</div> {* End of tabs*}
{/strip}
{literal}
<script type="text/javascript">
	$('#excerptPanel').on('show.bs.collapse', function (e) {
		VuFind.GroupedWork.getGoDeeperData({/literal}'{$recordDriver->getPermanentId()}'{literal}, 'excerpt');
	});
	$('#tableOfContentsPanel').on('show.bs.collapse', function (e) {
		VuFind.GroupedWork.getGoDeeperData({/literal}'{$recordDriver->getPermanentId()}'{literal}, 'tableOfContents');
	});
</script>
{/literal}
