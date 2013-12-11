{strip}
	<div id="moredetails-tabs" class="tabbable">
		<div class="result-label visible-phone">Show:</div>
		{* Define tabs for the display *}
		<ul class="nav nav-tabs">
			{assign var='firstTab' value=true}
			{if $showAmazonReviews || $showStandardReviews || $showComments}
				<li><a href="#reviewtab" data-toggle="tab">{translate text="Reviews"}</a></li>
			{/if}
			<li id="tableofcontentstab_label" {if !$tableOfContents}style="display:none"{/if}><a href="#tableofcontentstab" data-toggle="tab">{translate text="Contents"}</a></li>
			<li id="excerpttab_label" style="display:none"><a href="#excerpttab" data-toggle="tab">{translate text="Excerpt"}</a></li>
			<li id="detailstab_label"><a href="#detailstab" data-toggle="tab">{translate text="Details"}</a></li>

			<li><a href="#stafftab" data-toggle="tab">{translate text="Staff View"}</a></li>
		</ul>

		<div class="tab-content">
			<div id="reviewtab" class="tab-pane active">
				{if $user && ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin') || $user->hasRole('contentEditor'))}
					<div>
						<span class="btn"><a href='{$path}/EditorialReview/Edit?recordId={$id}'>Add Editorial Review</a></span>
					</div>
				{/if}

				{if $showComments}
					{include file="GroupedWork/view-user-review.tpl"}
				{/if}

				{if $showStandardReviews}
					<div id='reviewPlaceholder'></div>
				{/if}

				{foreach from=$reviewTabInfo.reviews item=review}
					{assign var=review value=$review}
					{include file="Resource/view-review.tpl"}
				{/foreach}
			</div>

			<div id = "detailstab" class="tab-pane">
				{include file="GroupedWork/view-title-details.tpl"}
			</div>

			<div id = "excerpttab" class="tab-pane">
				<div id="excerptPlaceholder">Loading Excerpt...</div>
			</div>

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

			<div id = "stafftab" class="tab-pane">
				{include file=$recordDriver->getStaffView()}
			</div>
		</div>
	</div> {* End of tabs*}
{/strip}
{literal}
<script type="text/javascript">
	$('#excerpttab_label a').on('shown', function (e) {
		VuFind.GroupedWork.getGoDeeperData({/literal}'{$recordDriver->getPermanentId()}'{literal}, 'excerpt');
	});
	$('#tableofcontentstab_label a').on('shown', function (e) {
		VuFind.GroupedWork.getGoDeeperData({/literal}'{$recordDriver->getPermanentId()}'{literal}, 'tableOfContents');
	});
</script>
{/literal}
