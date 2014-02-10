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
        <div class="resulthead">
            <div class="myAccountTitle">{translate text='Recommended for you'} {if ($smarty.foreach.recordLoop.iteration % 2) == 0}<span id='readingListWhatsThis' onclick="$('#readingListDisclaimer').toggle();">(What's This?)</span>{/if}
            </div>
            
            {if $userNoticeFile}
                {include file=$userNoticeFile}
            {/if}
    
    
            {if !$offline}
            <div id='readingListDisclaimer' {if ($smarty.foreach.recordLoop.iteration % 2) == 0}style='display: none'{/if}>
            Recommendations are based on your 5 most recent checked out items and on the last 10 items you rated with 3 or more stars. Titles you have marked Not Interested will no longer appear under Recommended for You. Titles marked Not Interested will also be excluded from the search results list when you are logged in.
            </div>
            {/if}
		</div>




		{foreach from=$resourceList item=suggestion name=recordLoop}
            <div class="result {if ($smarty.foreach.recordLoop.iteration % 2) == 0}alt{/if} record{$smarty.foreach.recordLoop.iteration}">
				{$suggestion}
			</div>
		{foreachelse} 
     		<h3>Find your next favorite. Rate titles to get personalized recommendations.</h3> 
            	<ul class="showbullets">
                    <li>Login with your library card</li>
                    <li>Find titles you like</li>
                    <li>Rate favorite title with 4 or 5 stars</li>
                    <li>Go to "Recommended for You" to see a personalized list</li>
                </ul>
             
            <h3>Start Rating Titles</h3> 

				<p><a href="http://catalog.library.nashville.org/MyResearch/CheckedOut">Your Checked Out Items</a> | <a href="http://catalog.library.nashville.org/MyResearch/ReadingHistory">Your Reading History</a> | <a href="http://catalog.library.nashville.org/">New and Popular Titles</a></p>
            
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