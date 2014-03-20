{strip}
<div id="page-content" class="content">
        

		{* Removed Sidebar content *}

	<div id="main-content">
		<div id="noResultsPageBox">
            {* Recommendations *}
            {if $topRecommendations}
                {foreach from=$topRecommendations item="recommendations"}
                    {include file=$recommendations}
                {/foreach}
            {/if}
            {*
            {if $numUnscopedResults && $numUnscopedResults != $recordCount}
                <h1>No Results for </h1>
            {else}
                <h1>{translate text='nohit_heading'}</h1>
            {/if}
            *}
                
            <h2 class="error">{translate text='nohit_prefix'} - <b>{if $lookfor}{$lookfor|escape:"html"}{else}&lt;empty&gt;{/if}</b> - {translate text='nohit_suffix'}</h2>
            
            {if $numUnscopedResults && $numUnscopedResults != $recordCount}
                <div class="unscopedResultCount">
                    There are <b>{$numUnscopedResults}</b> results in the entire Marmot collection. <span style="font-size:15px"><a href="{$unscopedSearchUrl}">Search the entire collection.</a></span>
                </div>
            {/if}
            <div>
                {if $parseError}
                    {$parseError}
                {/if}
                
                {if $spellingSuggestions}
                    <div class="correction"><h3>{translate text='nohit_spelling'}:</h3>
                    	<div class="correctionSuggestionIndent">{foreach from=$spellingSuggestions item=details key=term name=termLoop}
                        {$term|escape} &raquo; {foreach from=$details.suggestions item=data key=word name=suggestLoop}<a href="{$data.replace_url|escape}">{$word|escape}</a>{if $data.expand_url} <a href="{$data.expand_url|escape}"><img src="/images/silk/expand.png" alt="{translate text='spell_expand_alt'}"/></a> {/if}{if !$smarty.foreach.suggestLoop.last}, {/if}{/foreach}{if !$smarty.foreach.termLoop.last}<br/>{/if}
                    {/foreach}
                    	</div>
                    </div>
                    <br/>
                {/if}
                
                {if $searchSuggestions}
                    <div id="searchSuggestions">
                        <h2>Similar Searches</h2>
                        <p>These searches are similar to the search you tried. Would you like to try one of these instead?</p> 
                        <ul> 
                        {foreach from=$searchSuggestions item=suggestion}
                            <li class="searchSuggestion"><a href="/Search/Results?lookfor={$suggestion.phrase|escape:url}&basicType={$searchIndex|escape:url}">{$suggestion.phrase}</a></li>
                        {/foreach}
                        </ul>
                    </div>
                {/if}
                
                {if $unscopedResults}
                    <h2>Results from the entire Marmot Catalog</h2>
                    {foreach from=$unscopedResults item=record name="recordLoop"}
                        <div class="result {if ($smarty.foreach.recordLoop.iteration % 2) == 0}alt{/if} record{$smarty.foreach.recordLoop.iteration}">
                            {* This is raw HTML -- do not escape it: *}
                            {$record}
                        </div>
                    {/foreach}
                {/if}
    
                {if $prospectorNumTitlesToLoad > 0}
                    <script type="text/javascript">getProspectorResults({$prospectorNumTitlesToLoad}, {$prospectorSavedSearchId});</script>
                    {* Prospector Results *}
                    <div id='prospectorSearchResultsPlaceholder'></div>
                {/if}
                
                
                {* Display Repeat this search links *}
                {if strlen($lookfor) > 0 && count($repeatSearchOptions) > 0}
                    <div class='repeatSearchHead'><h4>Try another catalog</h4></div>
                        <div class='repeatSearchList'>
                        {foreach from=$repeatSearchOptions item=repeatSearchOption}
                            <div class='repeatSearchItem'>
                                <a href="{$repeatSearchOption.link}" class='repeatSearchName' target='_blank'>{$repeatSearchOption.name}</a>{if $repeatSearchOption.description} - {$repeatSearchOption.description}{/if}
                            </div>
                        {/foreach}
                    </div>
                {/if}
    
                {if $enableMaterialsRequest}
                    <h2>Didn't find it?</h2>
                    <p>Can't find what you are looking for? <a href="{$path}/MaterialsRequest/NewRequest?lookfor={$lookfor}&basicType={$searchIndex}">Suggest a purchase</a>.</p>
                {/if}
                
                {* deleted RSS and save search b/c this is a no results screen *}
            </div>
		</div>
        
        <div id="noResultsWorldcat">
        <img src="{$path}/interface/themes/nashville/images/noResultsImage_OrangeMangifier.png" align="left" alt="Didn't what you were looking for icon" class="noResultsImage">    
            <h2>Didn't find what you were looking for?</h2>
				<ul class="correctionSuggestionIndent">
					<li><a href="http://www.library.nashville.org/bmm/bmm_books_suggestionform.asp" onClick="_gaq.push(['_trackEvent','Link','Click','Didn't Find Links - Suggest Purchase']);">Suggest a title for the library to purchase.</a></li>
                    <li><a href="http://npl.worldcat.org/search?q={$lookfor|escape:"html"}" onClick="_gaq.push(['_trackEvent','Link','Click','Didn't Find Links - NPL Worldcat Search']);">Repeat your search on npl.worldcat.org - we'll try to borrow the item for you.</a></li>
                </ul>
            </div>
            
	</div>
    

	
    
</div>
<script type="text/javascript">
	$(document).ready(function() {literal} { {/literal}
		doGetStatusSummaries();
		{if $user}
		doGetSaveStatuses();
		{/if}
		doGetSeriesInfo();
		{literal} }); {/literal}
</script>
{/strip}
