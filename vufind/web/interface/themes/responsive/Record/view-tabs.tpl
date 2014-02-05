{strip}
	<div id="moredetails-tabs" class="tabbable">
		<div class="result-label visible-phone">Show:</div>
		{* Define tabs for the display *}
		<ul class="nav nav-tabs">
			<li id="holdingstab_label" class="active"><a href="#holdingstab" data-toggle="tab">{translate text="Copies"}</a></li>
			<li id="detailstab_label"><a href="#detailstab" data-toggle="tab">{translate text="Details"}</a></li>
			<li id="excerpttab_label" style="display:none"><a href="#excerpttab" data-toggle="tab">{translate text="Excerpt"}</a></li>
			{if $enableMaterialsRequest || is_array($otherEditions) }
				<li id="otherEditionsTab_label"><a href="#otherEditionsTab" data-toggle="tab">{translate text="Other Formats"}</a></li>
			{/if}
			{if $enablePospectorIntegration == 1 && $showProspectorTitlesAsTab == 1}
				<li id="prospectortab_label"><a href="#prospectorTab" data-toggle="tab">{translate text="In Prospector"}</a></li>
			{/if}

			<li id="tableofcontentstab_label" {if !$tableOfContents}style="display:none"{/if}><a href="#tableofcontentstab" data-toggle="tab">{translate text="Contents"}</a></li>

			{if $notes}
				<li><a href="#notestab" data-toggle="tab">{translate text=$notesTabName}</a></li>
			{/if}

			{if $showAmazonReviews || $showStandardReviews || $showComments}
				{foreach from=$editorialReviews key=key item=reviewTabInfo}
					<li><a href="#{$key}" data-toggle="tab">{translate text=$reviewTabInfo.tabName}</a></li>
					{foreachelse}
					<li><a href="#reviewtab" data-toggle="tab">{translate text="Reviews"}</a></li>
				{/foreach}
			{/if}
			<li><a href="#citetab" data-toggle="tab">{translate text="Citation"}</a></li>
			<li><a href="#stafftab" data-toggle="tab">{translate text="Staff View"}</a></li>
		</ul>

		<div class="tab-content">
			<div id = "holdingstab" class="tab-pane active">
				<a name="holdings"></a>
				<div id="holdingsPlaceholder"></div>

				{if $enablePurchaseLinks == 1 && !$purchaseLinks}
					<br/>
					<div class='purchaseTitle button'><a href="#" onclick="return showPurchaseOptions('{$id}');">{translate text='Buy a Copy'}</a></div>
				{/if}

			</div>

			<div id = "detailstab" class="tab-pane">
				{include file="Record/view-title-details.tpl"}
			</div>

			<div id = "excerpttab" class="tab-pane">
				<div id="excerptPlaceholder">Loading Excerpt...</div>
			</div>

			{if $enableMaterialsRequest || is_array($otherEditions) }
				<div id="otherEditionsTab" class="tab-pane">
					{include file='Resource/otherEditions.tpl' otherEditions=$editionResources}
				</div>
			{/if}

			{if $enablePospectorIntegration == 1 && $showProspectorTitlesAsTab == 1}
				<div id="prospectorTab" class="tab-pane">
					{* Display in Prospector Sidebar *}
					<div id="inProspectorPlaceholder"></div>
				</div>
			{/if}

			<div id ="tableofcontentstab" class="tab-pane">
				<div id="tableOfContentsPlaceholder" style="display:none"></div>

				{if $tableOfContents}
					<ul class='notesList'>
						{foreach from=$tableOfContents item=note}
							<li>{$note}</li>
						{/foreach}
					</ul>
				{/if}
			</div>

			{if $notes}
				<div id ="notestab" class="tab-pane">

					<dl class='notesList'>
						{foreach from=$notes item=note}
							{$note}
						{/foreach}
						<div id="relatedContentPlaceholder"></div>

						{if $internetLinks}
							<dt>Links</dt>
							{foreach from=$internetLinks item=internetLink}
								{if $proxy}
									<dd><a href="{$proxy}/login?url={$internetLink.link|escape:"url"}">{$internetLink.linkText|escape}</a></dd>
								{else}
									<dd><a href="{$internetLink.link|escape}">{$internetLink.linkText|escape}</a></dd>
								{/if}
							{/foreach}
						{/if}
					</dl>
				</div>
			{/if}

			{foreach from=$editorialReviews key=key item=reviewTabInfo}
				<div id="{$key}" class="tab-pane">
					{if $user && ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin') || $user->hasRole('contentEditor'))}
						<div>
							<span class="btn"><a href='{$path}/EditorialReview/Edit?recordId={$id}'>Add Editorial Review</a></span>
						</div>
					{/if}

					{if $key == 'reviews'}
						{if $showComments}
							{include file="$module/view-comments.tpl"}

							<div id = "staffReviewtab" >
								{include file="$module/view-staff-reviews.tpl"}
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
				{include file="$module/cite.tpl"}
			</div>

			<div id = "stafftab" class="tab-pane">
				{include file=$staffDetails}

				<br/>
				{if $user && $user->hasRole('opacAdmin')}
					<a href="{$path}/Record/{$id|escape:"url"}/AJAX?method=downloadMarc" class="btn">{translate text="Download Marc"}</a>
				{/if}
				{if $classicId}
				<a href ="{$classicUrl}/record={$classicId|escape:"url"}&amp;searchscope={$millenniumScope}" rel="external" class="btn" onclick="trackEvent('Outgoing Link', 'Classic', '{$classicId}');window.open (this.href, 'child'); return false">View In Classic</a></div>
			{/if}
		</div>
	</div>
	</div> {* End of tabs*}
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
