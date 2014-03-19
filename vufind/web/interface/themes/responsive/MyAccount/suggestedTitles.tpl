{strip}
	<div id="main-content">
		{if $profile.web_note}
			<div id="web_note" class="alert alert-info text-center">{$profile.web_note}</div>
		{/if}

		{* Internal Grid *}
		<h2 class="myAccountTitle">{translate text='Recommended for you'}</h2>

		{if $userNoticeFile}
			{include file=$userNoticeFile}
		{/if}

		{foreach from=$resourceList item=suggestion name=recordLoop}
			<div class="result {if ($smarty.foreach.recordLoop.iteration % 2) == 0}alt{/if} record{$smarty.foreach.recordLoop.iteration}">
				{$suggestion}
			</div>
		{foreachelse}
			<div class="error">You have not rated any titles.  Please rate some titles so we can display suggestions for you. </div>
		{/foreach}
	</div>
{/strip}