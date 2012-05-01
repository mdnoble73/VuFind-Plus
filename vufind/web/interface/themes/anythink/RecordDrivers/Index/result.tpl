<div id="record-{$summId|escape}" class="record" data-summId="{$summId|escape}" data-type="VuFind">
  <!--<div class="selectTitle">
    <input type="checkbox" class="titleSelect" name="selected[{$summId|escape:"url"}]" id="selected{$summId|escape:"url"}" {if $enableBookCart}onclick="toggleInBag('{$summId|escape:"url"}', '{$summTitle|regex_replace:"/(\/|:)$/":""|escape:"javascript"}', this);"{/if} />&nbsp;
  </div>-->
  <div class="cover"> 
      {if $user->disableCoverArt != 1}  
      <img src="{$bookCoverUrl}" class="listResultImage" alt="{translate text='Cover Image'}"/>
      {/if}
      <div class="rating" id="rating-{$summId|escape}"></div>
      <div class='requestThisLink' id="placeHold{$summId|escape:"url"}"><a class="button" href="{$url}/Record/{$summId|escape:"url"}/Hold">Hold</a></div>
  </div>
  <div class="actions">
    <div id="rate-{$summId|escape}" class="rate"></div>
    <div id="saveLink{$summId|escape}">
      {if $user}
        <div id="lists{$summId|escape}"></div>
        <script type="text/javascript">
          getSaveStatuses('{$summId|escape:"javascript"}');
        </script>
      {/if}
      {if $showFavorites == 1} 
        <a class="button" href="{$url}/Resource/Save?id={$summId|escape:"url"}&amp;source=VuFind" onclick="getSaveToListForm('{$summId|escape}', 'VuFind'); return false;">{translate text='Add to list...'}</a>
      {/if}
    </div>
    {assign var=id value=$summId scope="global"}
    {assign var=shortId value=$summShortId scope="global"}
    {*include file="Record/title-review.tpl"*}
  </div>
  <div class="details">
    <div>
      <h2><a href="{$url}/Record/{$summId|escape:"url"}/Home?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" class="title">{if !$summTitle|regex_replace:"/(\/|:)$/":""}{translate text='Title not available'}{else}{$summTitle|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}{/if}
        {if $summTitleStatement}
          {$summTitleStatement|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}
        {/if}</a>
        </h2>
    </div>
    <div class="byline">
      {if is_array($summFormats)}
        {foreach from=$summFormats item=format}
          <span class="icon-{$format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$format}</span>
        {/foreach}
      {else}
        <span class="icon-{$summFormats|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$summFormats}</span>
      {/if}
      {if $summAuthor}
        {translate text='by'}
        {if is_array($summAuthor)}
          {foreach from=$summAuthor item=author}
            <a href="{$url}/Author/Home?author={$author|escape:"url"}">{$author|highlight:$lookfor}</a>
          {/foreach}
        {else}
          <a href="{$url}/Author/Home?author={$summAuthor|escape:"url"}">{$summAuthor|highlight:$lookfor}</a>
        {/if}
      {/if}
      {if $summDate}{translate text='Published'} {$summDate.0|escape}{/if}
    </div>
    {if !empty($summSnippetCaption) || !empty($summSnippet)}
      <div class="summary">
        {if !empty($summSnippetCaption)}<strong>{translate text=$summSnippetCaption}:</strong>{/if}
        {if !empty($summSnippet)}<span class="quotestart">&#8220;</span>...{$summSnippet|highlight}...<span class="quoteend">&#8221;</span>{/if}
      </div>
    {/if}
    <div id="holdings-summary-{$summId|escape:'url'}" class="holdings-summary"></div>
    <div id="description-{$summId|escape:'url'}" class="description"></div>
  </div>
</div>