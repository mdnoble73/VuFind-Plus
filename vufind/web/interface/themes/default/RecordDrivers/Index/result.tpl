<div id="record{$summId|escape}" class="resultsList">
<div class="selectTitle">
  <input type="checkbox" class="titleSelect" name="selected[{$summId|escape:"url"}]" id="selected{$summId|escape:"url"}" {if $enableBookCart}onclick="toggleInBag('{$summId|escape:"url"}', '{$summTitle|regex_replace:"/(\/|:)$/":""|escape:"javascript"}', this);"{/if} />&nbsp;
</div>
        
<div class="imageColumn"> 
    {if $user->disableCoverArt != 1}  
    <div id='descriptionPlaceholder{$summId|escape}' style='display:none'></div>
    <a href="{$url}/Record/{$summId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" id="descriptionTrigger{$summId|escape:"url"}">
    <img src="{$bookCoverUrl}" class="listResultImage" alt="{translate text='Cover Image'}"/>
    </a>
    {/if}
    {* Place hold link *}
    <div class='requestThisLink' id="placeHold{$summId|escape:"url"}" style="display:none">
      <a href="{$url}/Record/{$summId|escape:"url"}/Hold"><img src="{$path}/interface/themes/default/images/place_hold.png" alt="Place Hold"/></a>
    </div>
</div>

<div class="resultDetails">
  <div class="resultItemLine1">
	<a href="{$url}/Record/{$summId|escape:"url"}/Home?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" class="title">{if !$summTitle|regex_replace:"/(\/|:)$/":""}{translate text='Title not available'}{else}{$summTitle|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}{/if}</a>
	{if $summTitleStatement}
    <div class="searchResultSectionInfo">
      {$summTitleStatement|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}
    </div>
    {/if}
  </div>

  <div class="resultItemLine2">
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
  
  <div class="resultItemLine3">
    {if !empty($summSnippetCaption)}<b>{translate text=$summSnippetCaption}:</b>{/if}
    {if !empty($summSnippet)}<span class="quotestart">&#8220;</span>...{$summSnippet|highlight}...<span class="quoteend">&#8221;</span><br />{/if}
  </div>

  {if is_array($summFormats)}
    {foreach from=$summFormats item=format}
      <span class="iconlabel {$format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$format}</span>
    {/foreach}
  {else}
    <span class="iconlabel {$summFormats|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$summFormats}</span>
  {/if}
  <div id = "holdingsSummary{$summId|escape:"url"}" class="holdingsSummary">
    <div class="statusSummary" id="statusSummary{$summId|escape:"url"}">
      <span class="unknown" style="font-size: 8pt;">{translate text='Loading'}...</span>
    </div>
  </div>
</div>

<div id ="searchStars{$summId|escape}" class="resultActions">
  <div class="rate{$summId|escape} stat">
	  <div class="statVal">
	    <span class="ui-rater">
	      <span class="ui-rater-starsOff" style="width:90px;"><span class="ui-rater-starsOn" style="width:0px"></span></span>
	      (<span class="ui-rater-rateCount-{$summId|escape} ui-rater-rateCount">0</span>)
	    </span>
	  </div>
    <div id="saveLink{$summId|escape}">
      {if $user}
      	<div id="lists{$summId|escape}"></div>
    		<script type="text/javascript">
    		  getSaveStatuses('{$summId|escape:"javascript"}');
    		</script>
      {/if}
      {if $showFavorites == 1} 
        <a href="{$url}/Record/{$summId|escape:"url"}/Save" style="padding-left:8px;" onclick="getLightbox('Record', 'Save', '{$summId|escape}', '', '{translate text='Add to favorites'}', 'Record', 'Save', '{$summId|escape}'); return false;">{translate text='Add to'} <span class='myListLabel'>MyLIST</span></a>
      {/if}
    </div>
    {assign var=id value=$summId}
    {include file="Record/title-review.tpl"}
  </div>
  <script type="text/javascript">
    $(
       function() {literal} { {/literal}
           $('.rate{$summId|escape}').rater({literal}{ {/literal}module: 'Record', recordId: {$summId},  rating:0.0, postHref: '{$url}/Record/{$summId|escape}/AJAX?method=RateTitle'{literal} } {/literal});
       {literal} } {/literal}
    );
  </script>
    
</div>


<script type="text/javascript">
  addRatingId('{$summId|escape:"javascript"}');
  addIdToStatusList('{$summId|escape:"javascript"}');
  $(document).ready(function(){literal} { {/literal}
  	resultDescription('{$summId}','{$summId}');
  {literal} }); {/literal}
  
</script>

</div>