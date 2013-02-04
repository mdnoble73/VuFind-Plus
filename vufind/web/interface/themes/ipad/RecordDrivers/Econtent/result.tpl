<a rel="external" href="{$path}/EcontentRecord/{$summId|escape:'url'}">

<div class="result recordId" id="econtentRecord{$summId|escape}">
	<h3>
    {if !empty($summHighlightedTitle)}{$summHighlightedTitle|trim:':/'|highlight}{else}{$summTitle|trim:':/'|escape}{/if}
  </h3>

  {if !empty($summAuthor)}
    <p>{translate text='by'} {$summAuthor}</p>
  {/if}
  {if $summAjaxStatus}
    <p><strong>{translate text='Call Number'}:</strong> <span class="ajax_availability hide callnumber{$summShortId|escape}">{translate text='Loading'}...</span></p>
    <p><strong>{translate text='Located'}:</strong> <span class="ajax_availability hide location{$summShortId|escape}">{translate text='Loading'}...</span></p>
  {elseif !empty($summCallNo)}
    <p><strong>{translate text='Call Number'}:</strong> {$summCallNo|escape}</p>
  {/if}
  
  <div class="resultItemLine3">
    {if !empty($summSnippetCaption)}<b>{translate text=$summSnippetCaption}:</b>{/if}
    {if !empty($summSnippet)}<span class="quotestart">&#8220;</span>...{$summSnippet|highlight}...<span class="quoteend">&#8221;</span><br>{/if}
  </div>

  {if !empty($summFormats)}
    <p>
    {foreach from=$summFormats item=format}
      <span class="iconlabel {$format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$format}</span>
    {/foreach}
    {if false && !$summOpenUrl && empty($summURLs)}
      <span class="ajax_availability hide status{$summShortId|escape}">{translate text='Loading'}...</span>
    {/if}
    </p>
  {/if}
</div>

</a>
<a href="#" data-record-id="econtentRecord{$summId|escape}" title="{translate text='Add to book bag'}" class="add_to_book_bag">{translate text="Add to book bag"}</a>