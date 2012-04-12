<div id="record{$summId|escape}" class="resultsList">
<div class="selectTitle">
  <input class="titleSelect" type="checkbox" name="selected[{$summId|escape:"url"}]" id="selected{$summId|escape:"url"}" {if $enableBookCart}onclick="toggleInBag('{$summId|escape:"url"}', '{$summTitle|regex_replace:"/(\/|:)$/":""|escape:"javascript"}', this);"{/if} />&nbsp;
</div>
        
<div class="imageColumn"> 
    <div id='descriptionPlaceholder{$summId|escape}' style='display:none'></div>
    <a href="{$url}/Record/{$summId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" id="descriptionTrigger{$summId|escape:"url"}">
    <img src="{$bookCoverUrl}" class="listResultImage" alt="{translate text='Cover Image'}"/>
    </a>

</div>

<div class="resultDetails">
  <div class="resultItemLine1">
	<a href="{$url}/Record/{$summId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" class="title">{if !$summTitle|regex_replace:"/(\/|:)$/":""}{translate text='Title not available'}{else}{$summTitle|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}{/if}</a>
	{if $summTitleStatement}
    <div class="searchResultSectionInfo">
      {$summTitleStatement|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}
    </div>
    {/if}
  </div>

  <div class="resultItemLine2"><span style="font-size: 8pt;">
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
  </span>
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
    {* Place hold link *}
    <div class='requestThisLink' id="placeHold{$summId|escape:"url"}" style="display:none">
      <a href="{$url}/Record/{$summId|escape:"url"}/Hold"><img src="{$path}/interface/themes/default/images/place_hold.png" alt="Place Hold"/></a>
    </div>
    {* Access eBook*}
    <div class='eBookLink' id="eBookLink{$summId|escape:"url"}" style="display:none">
    </div>
    {* Access eAudio*}
    <div class='eAudioLink' id="eAudioLink{$summId|escape:"url"}" style="display:none">
    <a href="{$eaudioLink}"><img src="{$path}/interface/themes/{$theme}/images/access_eaudio.png" alt="Access eAudio"/></a>
    </div>
    <div class="ratings">
    
      <div id="saveLink{$summId|escape}">
        <a href="{$url}/Resource/Save?id={$summId|escape:"url"}&amp;source=VuFind" style="padding-left:8px;" onclick="getSaveToListForm('{$summId|escape}', 'VuFind'); return false;" class="saveToListLink">{translate text='Add to'} <span class='myListLabel'>MyLIST</span></a>
        {if $user}
        	<div id="lists{$summId|escape}"></div>
					<script type="text/javascript">
					  getSaveStatuses('{$summId|escape:"javascript"}');
					</script>
        {/if}
      </div>
    </div>
  </div>
	<script type="text/javascript">
		addIdToStatusList('{$summId|escape:"javascript"}');
		$(document).ready(function(){literal} { {/literal}
			resultDescription('{$summId}','{$summId}');
		{literal} }); {/literal}
	</script>

</div>
