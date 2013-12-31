{strip}
	<div id="moredetails-tabs" class="tabbable">
		<div class="result-label visible-phone">Show:</div>
		{* Define tabs for the display *}
		<ul class="nav nav-tabs">
			<li id="detailstab_label"><a href="#detailstab" data-toggle="tab">{translate text="Details"}</a></li>
			<li id="excerpttab_label" style="display:none"><a href="#excerpttab" data-toggle="tab">{translate text="Excerpt"}</a></li>
			<li id="formatstabLink" class="active"><a href="#formatstab" data-toggle="tab">{translate text="Formats"}</a></li>
			{if $enableMaterialsRequest || is_array($otherEditions) }
				<li id="otherEditionsTab_label"><a href="#otherEditionsTab" data-toggle="tab">{translate text="Other Formats"}</a></li>
			{/if}
			{if $enablePospectorIntegration == 1 && $showProspectorTitlesAsTab == 1}
				<li><a href="#prospectorTab" data-toggle="tab">{translate text="In Prospector"}</a></li>
			{/if}
			{if $notes}
				<li><a href="#notestab" data-toggle="tab">{translate text="Notes"}</a></li>
			{/if}
			{if $showAmazonReviews || $showStandardReviews || $showComments}
				{foreach from=$reviews key=key item=reviewTabInfo}
					<li><a href="#{$key}" data-toggle="tab">{translate text=$reviewTabInfo.tabName}</a></li>
				{/foreach}
			{/if}
			<li><a href="#citetab" data-toggle="tab">{translate text="Citation"}</a></li>
			<li id="copiestabLink"><a href="#copiestab" data-toggle="tab">{translate text="Copies"}</a></li>
			{if $staffDetails != null}
				<li><a href="#stafftab" data-toggle="tab">{translate text="Staff View"}</a></li>
			{/if}
		</ul>

		<div class="tab-content">
			<div id = "detailstab" class="tab-pane">
				{include file="OverDrive/view-title-details.tpl"}
			</div>

			<div id = "excerpttab" class="tab-pane">
				<div id="excerptPlaceholder">Loading Excerpt...</div>
			</div>

			<div id="formatstab" class="tab-pane active">
				<div id="formatsPlaceholder">Loading...</div>

				<div id="additionalFormatActions">
					{if $enablePurchaseLinks == 1}
						<div class='purchaseTitle button'><a href="#" onclick="return showEcontentPurchaseOptions('{$id}');">{translate text='Buy a Copy'}</a></div>
					{/if}
				</div>
			</div>

			{if $enableMaterialsRequest || is_array($otherEditions) }
				<div id="otherEditionsTab" class="tab-pane">
					{include file='Resource/otherEditions.tpl' otherEditions=$editionResources}
				</div>
			{/if}

			{if $enablePospectorIntegration == 1 && $showProspectorTitlesAsTab == 1}
				<div id="prospectorTab" class="tab-pane">
					<div id="inProspectorPlaceholder"></div>
				</div>
			{/if}

			{* Display the content of individual tabs *}
			{if $notes}
				<div id ="notestab" class="tab-pane">
					<dl class='notesList'>
						{foreach from=$notes item=note}
							{$note}
						{/foreach}
					</dl>
				</div>
			{/if}

			{foreach from=$reviews key=key item=reviewTabInfo}
				<div id="{$key}" class="tab-pane">
					{if $user && ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin') || $user->hasRole('contentEditor'))}
						<div>
							<span class="button"><a href='{$path}/EditorialReview/Edit?recordId=econtentRecord{$id}'>Add Editorial Review</a></span>
						</div>
					{/if}

					{if $key == 'reviews'}
						{if $showComments}
							{include file="$module/view-comments.tpl"}

							<div id = "staffReviewtab" >
								{include file="Record/view-staff-reviews.tpl"}
							</div>
						{/if}
						{if $showStandardReviews}
							<div id='reviewPlaceholder'></div>
						{/if}
					{/if}

					{foreach from=$reviewTabInfo.reviews item=review}
						{assign var=review value=$review}
						{include file="Resource/view-review.tpl"}
					{/foreach}
				</div>
			{/foreach}

			<div id = "citetab" class="tab-pane">
				{include file="Record/cite.tpl"}
			</div>

			<div id = "copiestab" class="tab-pane">
				<div id="copiesPlaceholder">Loading...</div>
			</div>

			{if $staffDetails}
				<div id="stafftab" class="tab-pane">
					{include file=$staffDetails}

					{if $user && $user->hasRole('opacAdmin')}
						<a href="{$path}/EcontentRecord/{$id|escape:"url"}/AJAX?method=downloadMarc" class="btn">{translate text="Download Marc"}</a>
					{/if}
					{if $classicId}
						&nbsp;<a class="btn" href ="{$classicUrl}/record={$classicId|escape:"url"}&amp;searchscope={$millenniumScope}" rel="external" onclick="trackEvent('Outgoing Link', 'Classic', '{$classicId}');window.open (this.href, 'child'); return false">Classic View</a>
					{/if}
					{if $eContentRecord->sourceUrl}
						&nbsp;<a class="btn" href="{$eContentRecord->sourceUrl|replace:'&':'&amp;'}">Access original files</a>
					{/if}
				</div>
			{/if}
		</div> {* End of tabs*}
	</div>
{/strip}
{literal}
	<script type="text/javascript">
		$('#excerpttab_label a').on('shown', function (e) {
			VuFind.Record.GetGoDeeperData({/literal}'{$id}', '{$isbn}', '{$upc}'{literal}, 'excerpt');
		});
		$('#tableofcontentstab_label a').on('shown', function (e) {
			VuFind.Record.GetGoDeeperData({/literal}'{$id}', '{$isbn}', '{$upc}'{literal}, 'tableOfContents');
		});
	</script>
{/literal}

