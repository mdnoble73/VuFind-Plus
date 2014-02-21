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
     		<h3>Find your next favorite. Rate titles to get personalized recommendations.</h3> 
            	<ul class="showbullets">
                    <li>Login with your library card.</li>
                    <li>Find titles you like - Search the catalog, Browse your Checked Out items, or Browse your Reading History</li>
                    <li>Rate titles with 4 or 5 stars to get recommendations</li>
                    <li>Go to "Recommended for You" to see a personalized list</li>
                </ul>
                <h2>Get Started Rating Titles</h2> 

				<iframe src="http://catalog.library.nashville.org/API/SearchAPI?method=getListWidget&id=8" width="100%" height="250"></iframe>      
            
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