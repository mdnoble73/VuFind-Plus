{strip}
{if (isset($title)) }
	<script type="text/javascript">
		alert("{$title}");
	</script>
{/if}
<div id="page-content" class="row">
	<div id="sidebar" class="col-md-3">
		{include file="MyResearch/menu.tpl"}
	</div>
	<div id="main-content" class="col-md-9">
		<div data-role="content">
			{if $user->cat_username}
				{if $profile.web_note}
					<div id="web_note" class="text-info text-center alert alert-warning"><strong>{$profile.web_note}</strong></div>
				{/if}

				<h3>{translate text='Account Summary'}</h3>
				<p>
					You currently have a total of <strong>{$profile.numCheckedOutTotal}</strong> titles <a href="{$path}/MyAccount/CheckedOut">checked out</a>, and <strong>{$profile.numHoldsTotal}</strong> titles on <a href="{$path}/MyAccount/Holds">hold</a>.
					{* TODO: Show an alert if any titles are expired or are going to expire *}
					{* TODO: Show an alert if any titles ready for pickup *}
				</p>

				<h3>{translate text='Recommended for you'}</h3>
				{if !$hasRatings}
					<p>
						You have not rated any titles.
						If you rate titles, we can provide you with suggestions for titles you might like to read.
						Suggestions are based on titles you like (rated 4 or 5 stars) and information within the catalog.
						Library staff does not have access to your suggestions.
					</p>
				{else}
					<p>Based on the titles you have rated so far, these titles may be of interest to you.  To improve your suggestions keep rating more titles.</p>
					{foreach from=$suggestions item=suggestion name=recordLoop}
						<div class="result {if ($smarty.foreach.recordLoop.iteration % 2) == 0}alt{/if} record{$smarty.foreach.recordLoop.iteration}">
							{$suggestion}
						</div>
					{/foreach}
				{/if}
			{else}
				You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
			{/if}
		</div>
	</div>
</div>
{/strip}
<script type="text/javascript">
	$(document).ready(function() {literal} { {/literal}
		VuFind.ResultsList.loadStatusSummaries();
		VuFind.ResultsList.loadSeriesInfo();
		{literal} }); {/literal}
</script>