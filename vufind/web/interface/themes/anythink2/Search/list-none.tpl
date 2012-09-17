{if $recordCount > 0 && (!empty($spellingSuggestions) || !empty($sideRecommendations))}
<div id="sidebar-wrapper"><div id="sidebar">
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
</div></div>
{/if}
<div id="main-content">
  {* Recommendations *}
  {if $topRecommendations}
    {foreach from=$topRecommendations item="recommendations"}
      {include file=$recommendations}
    {/foreach}
  {/if}
  <h1>{translate text='nohit_heading'}</h1>

  <p class="error">{translate text="Your search"} <strong>{$lookfor|escape:"html"}</strong> {translate text="did not match any of our items."}</p>

  <p>{translate text="Try these search tips:"}</p>
  <ul>
    <li>{translate text="Check the spelling of your search terms."}</li>
    <li>{translate text="Use more search terms or try searching by using a broader term."}</li>
    <li>{translate text="Try our"} <a href="{$path}/Search/Advanced">{translate text="advanced search"}</a> {translate text="to narrow your search by keyword, format and more."}</li>
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
  <p>{translate text="Can't find what you're looking for?"} <a href="{$path}/MaterialsRequest/NewRequest?lookfor={$smarty.request.lookfor|escape:url}&basicType={$smarty.request.basicType|escape:url}">{translate text="Request it!"}</a></p>
  {/if}

  </div>
