<div id="moredetails-tabs" class="tabbable">
	{* Define tabs for the display *}
	<ul class="nav nav-tabs">
		<li id="holdingstab_label" class="active"><a href="#holdingstab" data-toggle="tab">{translate text="Copies"}</a></li>
		{if $enableMaterialsRequest || is_array($otherEditions) }
			<li id="otherEditionsTab_label"><a href="#otherEditionsTab" data-toggle="tab">{translate text="Other Formats"}</a></li>
		{/if}
		{if $enablePospectorIntegration == 1 && $showProspectorTitlesAsTab == 1}
			<li id="prospectortab_label"><a href="#prospectorTab" data-toggle="tab">{translate text="In Prospector"}</a></li>
		{/if}
		{if $tableOfContents}
			<li><a href="#tableofcontentstab" data-toggle="tab">{translate text="Contents"}</a></li>
		{/if}
		{if $notes}
			<li><a href="#notestab" data-toggle="tab">{translate text=$notesTabName}</a></li>
		{/if}
		{if $internetLinks && $show856LinksAsTab == 1}
			<li><a href="#linkstab" data-toggle="tab">{translate text="Links"}</a></li>
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

			{if $internetLinks && $show856LinksAsTab == 0}
				<h3>{translate text="Internet"}</h3>
				<div>
					{foreach from=$internetLinks item=internetLink}
						{if $proxy}
						<a href="{$proxy}/login?url={$internetLink.link|escape:"url"}">{$internetLink.linkText|escape}</a><br/>
						{else}
						<a href="{$internetLink.link|escape}">{$internetLink.linkText|escape}</a><br/>
						{/if}
					{/foreach}
				</div>
			{/if}

			{if $enablePurchaseLinks == 1 && !$purchaseLinks}
				<br/>
				<div class='purchaseTitle button'><a href="#" onclick="return showPurchaseOptions('{$id}');">{translate text='Buy a Copy'}</a></div>
			{/if}

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

		{if $tableOfContents}
			<div id ="tableofcontentstab" class="tab-pane">
				<ul class='notesList'>
					{foreach from=$tableOfContents item=note}
						<li>{$note}</li>
					{/foreach}
				</ul>
			</div>
		{/if}

		{if $notes}
			<div id ="notestab" class="tab-pane">

				<ul class='notesList'>
					{foreach from=$notes item=note}
						<li>{$note}</li>
					{/foreach}
					<li>
					<div id="relatedContentPlaceholder"></div>
					</li>
				</ul>
			</div>
		{/if}

		{if $internetLinks && $show856LinksAsTab ==1}
			<div id ="linkstab" class="tab-pane">
				{foreach from=$internetLinks item=internetLink}
				{if $proxy}
				<a href="{$proxy}/login?url={$internetLink.link|escape:"url"}">{$internetLink.linkText|escape}</a><br/>
				{else}
				<a href="{$internetLink.link|escape}">{$internetLink.linkText|escape}</a><br/>
				{/if}
				{/foreach}
			</div>
		{/if}

		{foreach from=$editorialReviews key=key item=reviewTabInfo}
			<div id="{$key}" class="tab-pane">
				{if $user && ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin') || $user->hasRole('contentEditor'))}
					<div>
						<span class="button"><a href='{$path}/EditorialReview/Edit?recordId={$id}'>Add Editorial Review</a></span>
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
