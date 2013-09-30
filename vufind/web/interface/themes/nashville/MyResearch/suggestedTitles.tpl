{strip}
<script type="text/javascript" src="{$path}/services/MyResearch/ajax.js"></script>

<div id="page-content" class="content">
	<div id="sidebar">
		{include file="MyResearch/menu.tpl"}

		{include file="Admin/menu.tpl"}
	</div>

	<div id="main-content">
		{if $profile.web_note}
			<div id="web_note">{$profile.web_note}</div>
		{/if}

		{* Internal Grid *}
		<div class="myAccountTitle">{translate text='Recommended for you'}</div>

		{if $userNoticeFile}
			{include file=$userNoticeFile}
		{/if}

		{foreach from=$resourceList item=suggestion name=recordLoop}
			<div class="result {if ($smarty.foreach.recordLoop.iteration % 2) == 0}alt{/if} record{$smarty.foreach.recordLoop.iteration}">
				{$suggestion}
			</div>
		{foreachelse}
			<div class="error">
            Find your next favorite - rate titles to get personalized recommendations. 
            Compelling ad and graphic goes here - with design so the text isn't all red.
            
            </div>
		{/foreach}
	</div>
	{* Load Ratings *}
	<script type="text/javascript">
	$(document).ready(function() {literal} { {/literal}
		doGetStatusSummaries();
		{if $user}
		doGetSaveStatuses();
		{/if}
	{literal} }); {/literal}
	</script>
	{* End of first Body *}
</div>
{/strip}