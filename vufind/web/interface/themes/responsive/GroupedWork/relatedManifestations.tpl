{strip}
	<div class="related-manifestations">
		<div class="row related-manifestations-header">
			<div class="col-xs-12 result-label related-manifestations-label">
				Formats
			</div>
		</div>
		{assign var=hasHiddenFormats value=false}
		{foreach from=$relatedManifestations item=relatedManifestation}
			{if $relatedManifestation.hideByDefault}
				{assign var=hasHiddenFormats value=true}
			{/if}
			<div class="row related-manifestation {if $relatedManifestation.hideByDefault}hiddenManifestation_{$summId}{/if}" {if $relatedManifestation.hideByDefault}style="display: none"{/if}>
				<div class="col-sm-12">
				  <div class="row">
						<div class="col-xs-4 col-md-3 manifestation-format">
							{if $relatedManifestation.numRelatedRecords == 1}
								<span class='manifestation-toggle-placeholder'>&nbsp;</span>
								<a href="{$relatedManifestation.url}">{$relatedManifestation.format}</a>
							{else}
								<a href="#" onclick="return VuFind.ResultsList.toggleRelatedManifestations('{$id}_{$relatedManifestation.format|escapeCSS}');">
									<span class='manifestation-toggle collapsed' id='manifestation-toggle-{$id}_{$relatedManifestation.format|escapeCSS}'>+</span> {$relatedManifestation.format}
								</a>
								<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
								<a href="#" onclick="return VuFind.ResultsList.toggleRelatedManifestations('{$id}_{$relatedManifestation.format|escapeCSS}');">
									<span class='manifestation-toggle-text label label-info' id='manifestation-toggle-text-{$id}_{$relatedManifestation.format|escapeCSS}'>Show&nbsp;Editions</span>
								</a>
							{/if}
						</div>
						<div class="col-xs-8 col-md-5 col-lg-6">
							{if $relatedManifestation.availableHere && $showItsHere}
								{if $relatedManifestation.availableOnline}
									<div class="related-manifestation-shelf-status available">Available Online</div>
								{elseif $relatedManifestation.allLibraryUseOnly}
									<div class="related-manifestation-shelf-status available">It's Here (library use only)</div>
								{else}
									<div class="related-manifestation-shelf-status available">It's Here</div>
								{/if}
							{elseif $relatedManifestation.availableLocally}
								{if $relatedManifestation.availableOnline}
									<div class="related-manifestation-shelf-status available">Available Online</div>
								{elseif $relatedManifestation.allLibraryUseOnly}
									<div class="related-manifestation-shelf-status available">On Shelf (library use only)</div>
								{elseif $onInternalIP}
									<div class="related-manifestation-shelf-status availableOther">Available at another branch</div>
								{else}
									<div class="related-manifestation-shelf-status available">On Shelf</div>
								{/if}
							{elseif $relatedManifestation.availableOnline}
								<div class="related-manifestation-shelf-status available">Available Online</div>
							{elseif $relatedManifestation.inLibraryUseOnly}
								<div class="related-manifestation-shelf-status available">In Library Use Only</div>
							{elseif $relatedManifestation.available && $relatedManifestation.hasLocalItem}
								<div class="related-manifestation-shelf-status availableOther">Checked Out/Available Elsewhere</div>
							{elseif $relatedManifestation.available}
								<div class="related-manifestation-shelf-status availableOther">Available from another library</div>
							{else}
								<div class="related-manifestation-shelf-status checked_out">Checked Out</div>
							{/if}

							{include file='GroupedWork/issueSummary.tpl' summary=$relatedManifestation.itemSummary totalCopies=$relatedManifestation.copies itemSummaryId=$id}

						</div>
						<div class="col-xs-8 col-xs-offset-4 col-md-4 col-md-offset-0 col-lg-3 manifestation-actions">
							<div class="btn-toolbar">
								<div class="btn-group btn-group-vertical btn-block">
									{foreach from=$relatedManifestation.actions item=curAction}
										{if $curAction.url && strlen($curAction.url) > 0}
											<a href="{$curAction.url}" class="btn btn-sm btn-primary" onclick="{if $curAction.requireLogin}return VuFind.Account.followLinkIfLoggedIn(this, '{$curAction.url}');{/if}">{$curAction.title}</a>
										{else}
											<a href="#" class="btn btn-sm btn-primary" onclick="{$curAction.onclick}">{$curAction.title}</a>
										{/if}
									{/foreach}
								</div>
							</div>
						</div>
				  </div>
					<div class="row">
						<div class="col-sm-12 hidden" id="relatedRecordPopup_{$id}_{$relatedManifestation.format|escapeCSS}">
							{include file="GroupedWork/relatedRecords.tpl" relatedRecords=$relatedManifestation.relatedRecords relatedManifestation=$relatedManifestation}
						</div>
					</div>
				</div>
			</div>
		{foreachelse}
			<div class="row related-manifestation">
				<div class="col-sm-12">
					No formats of this title are currently available to you.  {if !$user}You may be able to access additional titles if you login.{/if}
				</div>
			</div>
		{/foreach}
		{if $hasHiddenFormats}
			<div class="row related-manifestation" id="formatToggle_{$summId}">
				<div class="col-sm-12">
					<a href="#" onclick="$('.hiddenManifestation_{$summId}').show();$('#formatToggle_{$summId}').hide();return false;">View all Formats</a>
				</div>
			</div>
		{/if}
	</div>
{/strip}