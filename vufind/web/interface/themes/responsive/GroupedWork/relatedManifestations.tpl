{strip}
	<div class="related-manifestations">
		<div class="row related-manifestations-header">
			<div class="col-xs-12 result-label related-manifestations-label">
				Formats
			</div>
		</div>
		{foreach from=$relatedManifestations item=relatedManifestation}
			<div class="row related-manifestation">
				<div class="col-sm-12">
				  <div class="row">
						<div class="col-sm-3 manifestation-format">
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
						<div class="col-sm-7">
							{if $relatedManifestation.availableLocally}
								<div class="related-manifestation-shelf-status available">On Shelf</div>
							{elseif $relatedManifestation.availableOnline}
								<div class="related-manifestation-shelf-status available">Available Online</div>
							{elseif $relatedManifestation.available}
								<div class="related-manifestation-shelf-status availableOther">Available from another library</div>
							{else}
								<div class="related-manifestation-shelf-status checked_out">Checked Out</div>
							{/if}
							<div class="related-manifestation-copies">{$relatedManifestation.availableCopies} of {$relatedManifestation.copies} copies available.</div>
							{if false && $relatedManifestation.numRelatedRecords > 1}
								<div class="related-manifestation-editions">
							    {$relatedManifestation.numRelatedRecords} editions.
								</div>
							{/if}
							{if $relatedManifestation.shelfLocation}
								<div class="related-manifestation-shelf-location">
									Shelf Location: <span class="notranslate">{$relatedManifestation.shelfLocation}</span>
								</div>
							{/if}
							{if $relatedManifestation.callNumber}
								<div class="related-manifestation-call-number">Call Number: <span class="notranslate">{$relatedManifestation.callNumber}</span></div>
							{/if}

						</div>
						<div class="col-sm-2 btn-group manifestation-actions">
							{foreach from=$relatedManifestation.actions item=curAction}
								{if $curAction.url}
									<a href="{$curAction.url}" class="btn btn-sm" onclick="return VuFind.Account.followLinkIfLoggedIn(this, '{$curAction.url}')">{$curAction.title}</a>
								{else}
									<a href="#" class="btn btn-sm" onclick="{$curAction.onClick}">{$curAction.title}</a>
								{/if}
							{/foreach}
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
	</div>
{/strip}