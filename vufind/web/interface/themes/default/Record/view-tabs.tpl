<div id="moredetails-tabs">
	{* Define tabs for the display *}
	<ul>
		<li id="holdingstab_label"><a href="#holdingstab">{translate text="Copies"}</a></li>
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
		{if $showComments}
			<li><a href="#readertab">{translate text="Reader Comments"}</a></li>
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
				<li>{$note|escape}</li>
			{/foreach}
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
			{if $showAmazonReviews || $showStandardReviews || $showComments}
				{if $key == 'reviews'}
					<div id = "staffReviewtab" >
					{include file="$module/view-staff-reviews.tpl"}
					</div>

					<div id='reviewPlaceholder'></div>
				{/if}
			{/if}

			{if $showComments}
				{foreach from=$reviewTabInfo.reviews item=review}
					{assign var=review value=$review}
					{include file="Resource/view-review.tpl"}
				{/foreach}

				{if $user && ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin') || $user->hasRole('contentEditor'))}
					<div>
						<span class="button"><a href='{$path}/EditorialReview/Edit?recordId={$id}'>Add Editorial Review</a></span>
					</div>
				{/if}
			{/if}
		</div>
	{foreachelse}
		<div id="reviewtab">
			{if $showComments}
			<div id = "staffReviewtab" >
			{include file="$module/view-staff-reviews.tpl"}
			</div>
			{/if}

			{if $showAmazonReviews || $showStandardReviews}
			<div id='reviewPlaceholder'></div>
			{/if}
		</div>
	{/foreach}

	{if $showComments == 1}
		<div id = "readertab" >
			<div style ="font-size:12px;" class ="alignright" id="addReview"><span id="userreviewlink" onclick="$('#userreview{$shortId}').slideDown();"><span class="silk add">&nbsp;</span>Add a Review</span></div>
			<div id="userreview{$shortId}" class="userreview">
				<span class ="alignright unavailable closeReview" onclick="$('#userreview{$shortId}').slideUp();" >Close</span>
				<div class='addReviewTitle'>Add your Review</div>
				{assign var=id value=$id}
				{include file="$module/submit-comments.tpl"}
			</div>
			{include file="$module/view-comments.tpl"}
		</div>
	{/if}

	<div id = "citetab" >
		{include file="$module/cite.tpl"}
	</div>

	<div id = "stafftab">
		{include file=$staffDetails}

		{if $user && $user->hasRole('opacAdmin')}
			<br/>
			<a href="{$path}/Record/{$id|escape:"url"}/AJAX?method=downloadMarc" class="button">{translate text="Download Marc"}</a>
		{/if}
	</div>
</div> {* End of tabs*}

{literal}
<script type="text/javascript">
	$(function() {
		$("#moredetails-tabs").tabs();
	});
</script>
{/literal}