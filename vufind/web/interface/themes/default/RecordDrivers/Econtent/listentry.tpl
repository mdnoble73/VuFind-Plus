<div id="econtentRecord{$listId|escape}" class="resultsList">
<div class="selectTitle">
  <input type="checkbox" name="selected[{$listId|escape:"url"}]" id="selected{$listId|escape:"url"}" />&nbsp;
</div>
        
<div class="imageColumn"> 
   {if $user->disableCoverArt != 1}
    <a href="{$path}/EcontentRecord/{$listId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" id="descriptionTrigger{$listId|escape:"url"}">
    <img src="{$path}/bookcover.php?id={$listId}&amp;isn={$listISBN|@formatISBN}&amp;size=small&amp;upc={$listUPC}&amp;category={$listFormatCategory.0|escape:"url"}" class="listResultImage" alt="{translate text='Cover Image'}"/>
    </a>
    <div id='descriptionPlaceholder{$listId|escape}' style='display:none'></div>
   {/if}
    
    {* Place hold link *}
    <div class='requestThisLink' id="placeHold{$listId|escape:"url"}" style="display:none">
      <a href="{$path}/EcontentRecord/{$listId|escape:"url"}/Hold"><img src="{$path}/interface/themes/default/images/place_hold.png" alt="Place Hold"/></a>
    </div>
</div>

<div class="resultDetails">
  <div class="resultItemLine1">
	<a href="{$path}/EcontentRecord/{$listId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" class="title">{if !$listTitle|regex_replace:"/(\/|:)$/":""}{translate text='Title not available'}{else}{$listTitle|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}{/if}</a>
	{if $listTitleStatement}
    <div class="searchResultSectionInfo">
      {$listTitleStatement|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}
    </div>
    {/if}
  </div>

  <div class="resultItemLine2">
    {if $listAuthor}
      {translate text='by'}
      {if is_array($listAuthor)}
        {foreach from=$listAuthor item=author}
          <a href="{$path}/Author/Home?author={$author|escape:"url"}">{$author|highlight:$lookfor}</a>
        {/foreach}
      {else}
        <a href="{$path}/Author/Home?author={$listAuthor|escape:"url"}">{$listAuthor|highlight:$lookfor}</a>
      {/if}
    {/if}
 
    {if $listDate}{translate text='Published'} {$listDate.0|escape}{/if}
  </div>

  {if is_array($listFormats)}
    {foreach from=$listFormats item=format}
      <span class="iconlabel {$format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$format}</span>
    {/foreach}
  {else}
    <span class="iconlabel {$listFormats|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$listFormats}</span>
  {/if}
  <div id = "holdingsEContentSummary{$listId|escape:"url"}" class="holdingsSummary">
    <div class="statusSummary" id="statusSummary{$listId|escape:"url"}">
      <span class="unknown" style="font-size: 8pt;">{translate text='Loading'}...</span>
    </div>
  </div>
</div>

<div id ="searchStars{$listId|escape}" class="resultActions">
  <div class="rateEContent{$listId|escape} stat">
    <div id="saveLink{$listId|escape}">
      {if $listEditAllowed}
          <a href="{$path}/MyResearch/Edit?id={$listId|escape:"url"}{if !is_null($listSelected)}&amp;list_id={$listSelected|escape:"url"}{/if}" class="edit tool">{translate text='Edit'}</a>
          {* Use a different delete URL if we're removing from a specific list or the overall favorites: *}
          <a
          {if is_null($listSelected)}
            href="{$path}/MyResearch/Home?delete={$listId|escape:"url"}"
          {else}
            href="{$path}/MyResearch/MyList/{$listSelected|escape:"url"}?delete={$listId|escape:"url"}"
          {/if}
          class="delete tool" onclick="return confirm('Are you sure you want to delete this?');">{translate text='Delete'}</a>
      {/if}
    </div>
	  <div class="statVal">
	    <span class="ui-rater">
	      <span class="ui-rater-starsOff" style="width:90px;"><span class="ui-rater-starsOn" style="width:0px"></span></span>
	      (<span class="ui-rater-rateCount-{$listId|escape} ui-rater-rateCount">0</span>)
	    </span>
	  </div>
    {assign var=id value=$listId scope="global"}
    {include file="EcontentRecord/title-review.tpl"}
      
    </div>
    <script type="text/javascript">
      $(
         function() {literal} { {/literal}
             $('.rateEContent{$listId|escape}').rater({literal}{ {/literal}module: 'EcontentRecord', recordId: {$listId},  rating:0.0, postHref: '{$path}/EcontentRecord/{$listId|escape}/AJAX?method=RateTitle'{literal} } {/literal});
         {literal} } {/literal}
      );
    </script>
      
  </div>
<script type="text/javascript">
  addRatingId('{$listId|escape:"javascript"}', 'eContent');
  $(document).ready(function(){literal} { {/literal}
      addIdToStatusList('{$listId|escape:"javascript"}', 'eContent');
      resultDescription('{$listId}','{$listId}', 'eContent');
  {literal} }); {/literal}
  
</script>

</div>

