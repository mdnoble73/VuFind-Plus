<div id="record{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}" class="resultsList clearfix record" data-summId="{$summId|escape}" data-type="VuFind">
  <div class="imageColumn">
    {if empty($user->disableCoverArt)}
      <div id='descriptionPlaceholder{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}' style='display:none'></div>
      <a href="{$url}/Record/{$summId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" id="descriptionTrigger{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}">
      <img src="{$bookCoverUrl}" class="listResultImage" alt="{translate text='Cover Image'}"/>
      </a>
    {/if}
    <div class="requestThisLink" id="placeHold{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}" style="display:none">
      <a class="button" href="{$url}/Record/{$summId|escape:"url"}/Hold">Hold</a>
    </div>
  </div>
  <div class="resultActions" id="searchStars{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}">
    <div class="actions-first">
      <div class="actions-rate">
        <label>Rate</label>
        <div class="rate{if $summShortId}{$summShortId}{else}{$summId|escape}{/if} stat">
          <div class="statVal">
            <span class="ui-rater">
              <span class="ui-rater-starsOff" style="width:90px;"><span class="ui-rater-starsOn" style="width:0px"></span></span>
              (<span class="ui-rater-rateCount-{if $summShortId}{$summShortId}{else}{$summId|escape}{/if} ui-rater-rateCount">0</span>)
            </span>
          </div>
        </div>
      </div>
      <div class="actions-review">
        {assign var=id value=$summId scope="global"}
        {assign var=shortId value=$summShortId scope="global"}
        {include file="Record/title-review.tpl"}
      </div>
    </div>
    <div class="actions-second">
      <div class="actions-save" id="saveLink{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}">
        {if $user}
          <div id="lists{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}"></div>
          <script type="text/javascript">
            getSaveStatuses('{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}');
          </script>
        {/if}
        {if $showFavorites == 1}
          <a class="button" href="{$url}/Resource/Save?id={$summId|escape:"url"}&amp;source=VuFind" onclick="getSaveToListFormAnythink('{$summId}', 'VuFind'); return false;">{translate text='Add to list...'}</a>
        {/if}
      </div>
      {if $enableBookCart}
        <div class="actions-cart">
          <a href="#" class="button" data-summId="{$summId|escape}" data-title="{$summTitle|regex_replace:"/(\/|:)$/":""|escape:"javascript"}">Add to cart +</a>
        </div>
      {/if}
    </div>
    <script type="text/javascript">
      $(
         function() {literal} { {/literal}
             $('.rate{if $summShortId}{$summShortId|escape}{else}{$summId|escape}{/if}').rater({literal}{ {/literal}module: 'Record', recordId: '{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}',  rating:0.0, postHref: '{$url}/Record/{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}/AJAX?method=RateTitle'{literal} } {/literal});
         {literal} } {/literal}
      );
    </script>
  </div>
  <div class="resultDetails">
    <h3>{if $summScore}({$summScore}) {/if}<a href="{$url}/Record/{$summId|escape:"url"}/Home?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}">{if !$summTitle|regex_replace:"/(\/|:)$/":""}{translate text='Title not available'}{else}{$summTitle|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}{/if} {if $summTitleStatement}
      <em>{$summTitleStatement|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}</em>
    {/if}</a></h3>
    <div class="details">
      {if !empty($summFormats)}
        <span class="format">
          {if is_array($summFormats)}
            {foreach from=$summFormats item=format}
              <span class="icon-{$format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$format}</span>
            {/foreach}
          {else}
            <span class="icon-{$summFormats|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$summFormats}</span>
          {/if}
        </span>
      {/if}
      {if !empty($summAuthor)}
        <span class="author">
          {if is_array($summAuthor)}
            {foreach from=$summAuthor item=author}
              <a href="{$url}/Author/Home?author={$author|escape:"url"}">{$author|highlight:$lookfor}</a>
            {/foreach}
          {else}
            <a href="{$url}/Author/Home?author={$summAuthor|escape:"url"}">{$summAuthor|highlight:$lookfor}</a>
          {/if}
        </span>
      {/if}
      {if $summDate}{translate text='Published'} {$summDate.0|escape}{/if}
    </div>
    <div id="holdingsSummary{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}" class="holdingsSummary">
      <div class="statusSummary" id="statusSummary{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}">
        <span class="unknown">{translate text='Loading'}...</span>
      </div>
    </div>
    {if !empty($summSnippetCaption) || !empty($summSnippet)}
      <div class="fine-print">
        {if !empty($summSnippetCaption)}{translate text=$summSnippetCaption}:{/if}
        {if !empty($summSnippet)}<span class="quotestart">&#8220;</span>...{$summSnippet|highlight}...<span class="quoteend">&#8221;</span>{/if}
      </div>
    {/if}
    <div id="description-{$summId|escape:'url'}" class="description"></div>
  </div>
  <script type="text/javascript">
    addRatingId('{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}');
    addIdToStatusList('{$summId|escape}');
    // Reimplemented in anythink2.js
    // $(document).ready(function(){literal} { {/literal}
      // resultDescription('{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}','{$summId}');
    // {literal} }); {/literal}
  </script>
</div>
