<div id="record{$summId|escape}" class="gridRecordBox" onmouseover="YAHOO.util.Dom.addClass(this, 'gridMouseOver');" onmouseout="YAHOO.util.Dom.removeClass(this, 'gridMouseOver');">
    <span class="gridImageBox" >
    <a href="{$url}/Record/{$summId|escape:"url"}">
    {if $summThumbLarge}
    <img src="{$summThumbLarge|escape}" class="gridImage" alt="{translate text='Cover Image'}" />
    {elseif $summThumb}
    <img src="{$summThumb|escape}" class="gridImage" alt="{translate text='Cover Image'}" />
    {else}
    <img src="{$path}/bookcover.php" class="gridImage" alt="{translate text='No Cover Image'}"/>
    {/if}
    </a>
    </span>
    <div class="gridTitleBox" >
      <a class="gridTitle" href="{$url}/Record/{$summId|escape:"url"}" >
        {if !$summTitle}{translate text='Title not available'}{elseif !empty($summHighlightedTitle)}{$summHighlightedTitle|addEllipsis:$summTitle|highlight}{else}{$summTitle|truncate:80:"..."|escape}{/if}
      </a>
      {if $summOpenUrl || !empty($summURLs)}
        {if $summOpenUrl}
          {include file="Search/openurl.tpl" openUrl=$summOpenUrl}
        {/if}
        {foreach from=$summURLs key=recordurl item=urldesc}
        <div>
          <a href="{if $proxy}{$proxy}/login?qurl={$recordurl|escape:"url"}{else}{$recordurl|escape}{/if}"  target="new">{if $recordurl == $urldesc}{translate text='Get full text'}{else}{$urldesc|escape}{/if}</a>
        </div>
        {/foreach}
      {else}
        <div class="status" id="status{$summId|escape}">
          <span class="unknown" style="font-size: 8pt;">{translate text='Loading'}...</span>
        </div>
      {/if}
    </div>
</div>

{if $summCOinS}<span class="Z3988" title="{$summCOinS|escape}"></span>{/if}

{if $summAjaxStatus}
<script type="text/javascript">
  getStatuses('{$summId|escape:"javascript"}');
</script>
{/if}
{if $showPreviews}
<script type="text/javascript">
  {if $summISBN}getExtIds('ISBN{$summISBN|escape:"javascript"}');{/if}
  {if $summLCCN}getExtIds('LCCN{$summLCCN|escape:"javascript"}');{/if}
  {if $summOCLC}{foreach from=$summOCLC item=OCLC}getExtIds('OCLC{$OCLC|escape:"javascript"}');{/foreach}{/if}
  {if (!empty($summLCCN)|!empty($summISBN)|!empty($summOCLC))}
    getHTIds('id:HT{$summId|escape:"javascript"};{if $summISBN}isbn:{$summISBN|escape:"javascript"}{/if}{if $summLCCN}{if $summISBN};{/if}lccn:{$summLCCN|escape:"javascript"}{/if}{if $summOCLC}{if $summISBN|$summLCCN};{/if}{foreach from=$summOCLC item=OCLC name=oclcLoop}oclc:{$OCLC|escape:"javascript"}{if !$smarty.foreach.oclcLoop.last};{/if}{/foreach}{/if}')
  {/if}
</script>
{/if}
