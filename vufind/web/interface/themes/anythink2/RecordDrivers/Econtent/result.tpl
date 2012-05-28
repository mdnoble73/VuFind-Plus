<div id="record{$summId|escape}" class="resultsList clearfix record" data-summId="{$summId|escape}" data-type="eContent">
  <div class="imageColumn">
    {if empty($user->disableCoverArt)}
      <div id='descriptionPlaceholder{$summId|escape}' style='display:none'></div>
      <a href="{$path}/EcontentRecord/{$summId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" id="descriptionTrigger{$summId|escape:"url"}">
      <img src="{$bookCoverUrl}" class="listResultImage" alt="{translate text='Cover Image'}"/>
      </a>
    {/if}
    <div class="requestThisLink" id="placeEcontentHold{$summId|escape:"url"}" style="display:none">
      <a class="button" href="{$path}/EcontentRecord/{$summId|escape:"url"}/Hold">Place Hold</a>
    </div>
    <div class="checkoutLink" id="checkout{$summId|escape:"url"}" style="display:none">
      <a class="button" href="{$path}/EcontentRecord/{$summId|escape:"url"}/Checkout">Checkout</a>
    </div>
    <div class="accessOnlineLink" id="accessOnline{$summId|escape:"url"}" style="display:none">
      <a class="button" href="{$path}/EcontentRecord/{$summId|escape:"url"}/Home?detail=holdingstab#detailsTab">Access Online</a>
    </div>
    <div class="addToWishListLink" id="addToWishList{$summId|escape:"url"}" style="display:none">
      <a class="button" href="{$path}/EcontentRecord/{$summId|escape:"url"}/AddToWishList">Add to Wishlist</a>
    </div>
  </div>
  <div class="resultActions" id="searchStars{$summId|escape}">
    <div class="actions-first">
      <div class="actions-rate">
        <label>Rate</label>
        <div class="rateEContent{$summId|escape} stat">
          <div class="statVal">
            <span class="ui-rater">
              <span class="ui-rater-starsOff" style="width:90px;"><span class="ui-rater-starsOn" style="width:0px"></span></span>
              (<span class="ui-rater-rateCount-{$summId|escape} ui-rater-rateCount">0</span>)
            </span>
          </div>
        </div>
      </div>
      <div class="actions-review">
        {assign var=id value=$summId scope="global"}
        {include file="EcontentRecord/title-review.tpl" id=$summId}
      </div>
    </div>
    <div class="actions-second">
      <div class="actions-save" id="saveLink{$summId|escape}">
        {if $user}
          <div id="lists{$summId|escape}"></div>
          <script type="text/javascript">
            getSaveStatuses('{$summId|escape:"javascript"}');
          </script>
        {/if}
        {if $showFavorites == 1}
          <a class="button" href="{$path}/Resource/Save?id={$summId|escape:"url"}&amp;source=eContent" onclick="getSaveToListFormAnythink('{$summId|escape}', 'eContent'); return false;">{translate text='Add to list...'}</a>
        {/if}
      </div>
    </div>
    <script type="text/javascript">
      $(
         function() {literal} { {/literal}
             $('.rateEContent{$summId|escape}').rater({literal}{ {/literal}module: 'EcontentRecord', recordId: {$summId}, rating:0.0, postHref: '{$path}/EcontentRecord/{$summId|escape}/AJAX?method=RateTitle'{literal} } {/literal});
         {literal} } {/literal}
      );
    </script>
  </div>
  <div class="resultDetails">
    <h3><a href="{$path}/EcontentRecord/{$summId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" class="title">{if !$summTitle|regex_replace:"/(\/|:)$/":""}{translate text='Title not available'}{else}{$summTitle|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}{/if}{if $summTitleStatement}
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
              <a href="{$path}/Author/Home?author={$author|escape:"url"}">{$author|highlight:$lookfor}</a>
            {/foreach}
          {else}
            <a href="{$path}/Author/Home?author={$summAuthor|escape:"url"}">{$summAuthor|highlight:$lookfor}</a>
          {/if}
        </span>
      {/if}
      {if $summDate}{translate text='Published'} {$summDate.0|escape}{/if}
    </div>
    <div id="holdingsEContentSummary{$summId|escape:"url"}" class="holdingsSummary">
      <div class="statusSummary" id="statusSummary{$summId|escape:"url"}">
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
    addRatingId('{$summId|escape:"javascript"}', 'eContent');
    addIdToStatusList('{$summId|escape:"javascript"}', {if strcasecmp($source, 'OverDrive') == 0}'OverDrive'{else}'eContent'{/if});
    // Reimplemented in anythink2.js
    // $(document).ready(function(){literal} { {/literal}
    //   resultDescription('{$summId}','{$summId}', 'eContent');
    // {literal} }); {/literal}
  </script>
</div>
