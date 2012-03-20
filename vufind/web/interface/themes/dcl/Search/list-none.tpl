<div id="page-content" class="content">
  {* Narrow Search Options *}
  <div id="sidebar">
    {* Display spelling suggestions if any *}
	{if $spellingSuggestions}
	  <div class="sidegroup" id="spellingSuggestions">
	  	<h4>{translate text='spell_suggest'}</h4>
	  	<div class="sidegroupContents">
	  	  <dl class="narrowList navmenu narrow_begin">
	      {foreach from=$spellingSuggestions item=details key=term name=termLoop}
	        <dd>{$term|escape} &raquo; {foreach from=$details.suggestions item=data key=word name=suggestLoop}<a href="{$data.replace_url|escape}">{$word|escape}</a>{if $data.expand_url} <a href="{$data.expand_url|escape}"><img src="/images/silk/expand.png" alt="{translate text='spell_expand_alt'}"/></a> {/if}{if !$smarty.foreach.suggestLoop.last}, {/if}{/foreach}</dd>
	      {/foreach}
	      </dl>
	    </div>
	  </div>
	{/if}
      
    {if $sideRecommendations}
      {foreach from=$sideRecommendations item="recommendations"}
        {include file=$recommendations}
      {/foreach}
    {/if}
  </div>
  
  <div id="main-content">
    {* Recommendations *}
    {if $topRecommendations}
      {foreach from=$topRecommendations item="recommendations"}
        {include file=$recommendations}
      {/foreach}
    {/if}
    <div class="resulthead"><h3>{translate text='nohit_heading'}</h3></div>
      
      <p class="error">{translate text='nohit_prefix'} - <b>{$lookfor|escape:"html"}</b> - {translate text='nohit_suffix'}</p>

    <div>
    <ul id="noResultsSuggest">
    <li>Check the spelling of your search terms.</li>
    <li>Restate your query by using more, other or broader terms.</li>
    </ul>

      {if $parseError}
          <p class="error">{translate text='nohit_parse_error'}</p>
      {/if}
      
      {if $spellingSuggestions}
        <div class="correction">{translate text='nohit_spelling'}:<br/>
        {foreach from=$spellingSuggestions item=details key=term name=termLoop}
          {$term|escape} &raquo; {foreach from=$details.suggestions item=data key=word name=suggestLoop}<a href="{$data.replace_url|escape}">{$word|escape}</a>{if $data.expand_url} <a href="{$data.expand_url|escape}"><img src="/images/silk/expand.png" alt="{translate text='spell_expand_alt'}"/></a> {/if}{if !$smarty.foreach.suggestLoop.last}, {/if}{/foreach}{if !$smarty.foreach.termLoop.last}<br/>{/if}
        {/foreach}
        </div>
        <br/>
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
		<div id="materialsRequestPrefilterBlock">
			<div id="materialsRequestPrefilterHeader">Can't Find It In Our Catalog?</div>
			
			<div id="materialsRequestPrefilterUnpublished" class="materialsRequestLine">If this item has not been published, please recheck our catalog closer to the publication date.</div>
			<div id="materialsRequestPrefilterSubmitLine" class="materialsRequestLine">If you are a <em>Douglas County resident</em>, please <a href="{$path}/MaterialsRequest/NewRequest">submit a request</a>.</div>
			<div id="materialsRequestPrefilterAssist" class="materialsRequestLine">
				We will do our best to assist you by: 
				<ul>
					<li>Purchasing it for our collection per our guidelines.</li>
					<li>Borrowing it from another library (Interlibrary Loan).</li>
				</ul>
			</div>
			
			<div id="materialsRequestPrefilterLimit" class="materialsRequestLine">Please limit your requests to 5 per week and submit only 1 title per request form.</div>
		</div>
		{/if}
		
    </div>
</div>