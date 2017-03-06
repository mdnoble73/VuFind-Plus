{strip}
	<div class="archiveComponentContainer nopadding col-sm-12 col-md-6">
		<div class="archiveComponent browseFilterContainer">
			<div class="archiveComponentBody">
				<div class="archiveComponentBox">
					<a href="#" onclick="return VuFind.Archive.showBrowseFilterPopup('{$pid}', '{$browseFilterFacetName}', '{$browseFilterLabel}')">
						<img src="{$browseFilterImage}" width="100" height="100" alt="{$browseFilterLabel}" class="archiveComponentImage">
						<div class="archiveComponentControls">
							<div class="archiveComponentHeader">{$browseFilterLabel}</div>
						</div>
					</a>
				</div>
			</div>
		</div>
	</div>
{/strip}