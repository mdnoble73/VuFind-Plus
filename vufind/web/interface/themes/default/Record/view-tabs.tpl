<div id="moredetails-tabs">
	{* Define tabs for the display *}
	<ul>
		<li id="holdingstab_label"><a href="#holdingstab">{translate text="Copies"}</a></li>
		{if $enableMaterialsRequest || is_array($otherEditions) }
			<li id="otherEditionsTab_label"><a href="#otherEditionsTab">{translate text="Other Formats"}</a></li>
		{/if}
		{if $enablePospectorIntegration == 1 && $showProspectorTitlesAsTab == 1}
			<li id="prospectortab_label"><a href="#prospectorTab">{translate text="In Prospector"}</a></li>
		{/if}
		{if $tableOfContents}
			<li><a href="#tableofcontentstab">{translate text="Contents"}</a></li>
		{/if}
		{if $notes}
			<li><a href="#notestab">{translate text=$notesTabName}</a></li>
		{/if}
		{if $internetLinks && $show856LinksAsTab == 1}
			<li><a href="#linkstab">{translate text="Links"}</a></li>
		{/if}
		{if $showAmazonReviews || $showStandardReviews || $showComments}
			{foreach from=$editorialReviews key=key item=reviewTabInfo}
				<li><a href="#{$key}">{translate text=$reviewTabInfo.tabName}</a></li>
			{foreachelse}
				<li><a href="#reviewtab">{translate text="Reviews"}</a></li>
			{/foreach}
		{/if}
		<li><a href="#citetab">{translate text="Citation"}</a></li>
		<li><a href="#stafftab">{translate text="Staff View"}</a></li>
	</ul>

	<div id = "holdingstab">
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
		<div id="otherEditionsTab">
			{include file='Resource/otherEditions.tpl' otherEditions=$editionResources}
		</div>
	{/if}

	{if $enablePospectorIntegration == 1 && $showProspectorTitlesAsTab == 1}
		<div id="prospectorTab">
			{* Display in Prospector Sidebar *}
			<div id="inProspectorPlaceholder"></div>
		</div>
	{/if}

	{if $tableOfContents}
		<div id ="tableofcontentstab">
			<ul class='notesList'>
				{foreach from=$tableOfContents item=note}
					<li>{$note}</li>
				{/foreach}
			</ul>
		</div>
	{/if}

	{if $notes}
		<div id ="notestab">

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
		<div id ="linkstab">
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
		<div id="{$key}">
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

	<div id = "citetab" >
		{include file="$module/cite.tpl"}
	</div>

	<div id = "stafftab">
		{include file=$staffDetails}

		<br/>
		{if $user && $user->hasRole('opacAdmin')}
			<a href="{$path}/Record/{$id|escape:"url"}/AJAX?method=downloadMarc" class="button">{translate text="Download Marc"}</a>
		{/if}
		{if $classicId}
			<a href ="{$classicUrl}/record={$classicId|escape:"url"}&amp;searchscope={$millenniumScope}" rel="external" class="button" onclick="trackEvent('Outgoing Link', 'Classic', '{$classicId}');window.open (this.href, 'child'); return false">View In Classic</a></div>
		{/if}
	</div>
</div> {* End of tabs*}

<script type="text/javascript">
	$(function() {literal}{
		$("#moredetails-tabs").tabs();
	});
	{/literal}
</script>