<InProspector><![CDATA[{if is_array($prospectorResults) && count($prospectorResults) > 0}
<div id='prospectorSidebarResults'>
<img id='prospectorMan' src='{$path}/interface/themes/marmot/images/prospector_man_sidebar.png'/>
<div id='prospectorSearchResultsTitle'>{translate text="In Prospector"}</div>
<div class='clearer'>&nbsp;</div>
</div>
<ul class="similar">
  {foreach from=$prospectorResults item=prospectorTitle}
  {if $similar.recordId != -1}
  <li class="prospectorTitle {if $prospectorTitle.isCurrent}currentRecord{/if}">
    <a href="{$prospectorTitle.link}" rel="external" onclick="window.open (this.href, 'child'); return false">{$prospectorTitle.title|regex_replace:"/(\/|:)$/":""|escape}</a>
    
    <span style="font-size: 80%">
    {if $prospectorTitle.author}<br />{translate text='By'}: {$prospectorTitle.author|escape}{/if}
    {if $prospectorTitle.pubDate}<br />{translate text='Published'}: {$prospectorTitle.pubDate|escape}{/if}
    </span>
  </li>
  {/if}
  {/foreach}
</ul>
{/if}]]></InProspector>
<ProspectorRecordId>
{*The id of the record within Prospector or blank if it is not available in prospector.*}
{$prospectorDetails.recordId}
</ProspectorRecordId>
<NumOwningLibraries>
{*The number of libraries that hold the record within prospector.*}
{$prospectorDetails.numLibraries}
</NumOwningLibraries>
<OwningLibraries>
{*The libraries that hold the record within prospector.*}
{foreach from=$prospectorDetails.owningLibraries item=owningLibrary}
  <OwningLibrary>{$owningLibrary}</OwningLibrary>
{/foreach}
</OwningLibraries>
<OwningLibrariesFormatted><![CDATA[
{if strlen($prospectorDetails.recordId) > 0}
<div id='prospectorAvailabilityTitle'>Other Sources</div>
<div id='prospectorAvailability'>
  {if $prospectorDetails.prospectorEncoreUrl}<a href="{$prospectorDetails.prospectorEncoreUrl}" rel="external" onclick="window.open (this.href, 'child'); return false">{/if}Available in Prospector{if $prospectorTitle.link}</a>{/if}
  <span class='prospectorRequest'><a class='holdRequest' href='#' onclick="createInnreachRequestWindow('{$prospectorDetails.requestUrl}')" rel="external" onclick="window.open (this.href, 'child'); return false">Request from Prospector</a></span>
</div>
<div id='prospectorItemCount'>
{$prospectorDetails.owningLibraries|@count} Prospector libraries have this item 
</div> 
<div id='prospectorLibraries'>
{foreach from=$prospectorDetails.owningLibraries item=owningLibrary}
  <span class='prospectorLibrary'>♦&nbsp;{$owningLibrary}</span>
{/foreach}
</div>
{/if}
]]></OwningLibrariesFormatted>
<ProspectorClassicUrl>{$prospectorDetails.prospectorClassicUrl}</ProspectorClassicUrl>
<ProspectorEncoreUrl>{$prospectorDetails.prospectorEncoreUrl}</ProspectorEncoreUrl>
