<SimilarAuthors>
<![CDATA[{if $enrichment.novelist.similarAuthorCount != 0}
  <h4 id="similarAuthorTitle" >Similar Authors</h4>
  {foreach from=$enrichment.novelist.authors item=similarAuthor}
    <div class="sidebarLabel">
      <a href={$path}/Author/Home?author={$similarAuthor|escape:"url"}&lookfor=>{$similarAuthor}</a>
    </div>
  {/foreach}
{/if}]]>
</SimilarAuthors>
<SeriesInfo><![CDATA[{$seriesInfo}]]></SeriesInfo>
<Series>
<![CDATA[{if $enrichment.novelist.series}
<div id = "seriestab">
  {*Display the title of the section*}
  
  {if $showSeriesAsTab == 1}
  <div class = "blockhead">Also in this series <a href='{$path}/Record/{$id}/Series'><span class='seriesLink'>View as List</span></a></div>
  {/if}
  
  {* Display the series *}
  <ul id="seriesList" {if $showSeriesAsTab == 0}class="jcarousel-skin-tango"{/if}>
  {foreach from=$enrichment.novelist.series item=outer}
    <li class="seriesItem">
     {if $outer.recordId != -1}
        {* Display a link to the record in our catalog *}
        <a href ={$url}/Record/{$outer.recordId|escape:"url"}>
     {else if $outer.isbn10}
       {* Display a link to the record in amazon *}
        <a href =http://amazon.com/dp/{$outer.isbn10|escape:"url" target="_blank"}>
     {/if}
     {* Display the book jacket *}
     {if $outer.isbn}
        <img class='bookjacket' src="{$path}/bookcover.php?isn={$outer.isbn|@formatISBN}&amp;size=small" alt="{translate text='Cover Image'}"/>
      {else}
        <img class='bookjacket' src="{$path}/bookcover.php" alt="{translate text='No Cover Image'}"/>
      {/if}
      {* Show the book title *}
      <div class='seriesTitle'>{$outer.title|regex_replace:"/(\/|:)$/":""|escape}</div> 
      {if $outer.recordId != -1 || $outer.isbn10}
        {* close the link *}
        </a>
      {/if}
    </li>
  {/foreach} 
  </ul>
  
  {if $showSeriesAsTab == 0}
  <div><a href='{$path}/Record/{$id}/Series'>View as List</a></div>
  {/if}
</div>
{/if}]]></Series>
<SeriesDefaultIndex>{$enrichment.novelist.seriesDefaultIndex}</SeriesDefaultIndex>
<SimilarTitles><![CDATA[{if $showSimilarTitles}
<h4>{translate text="NoveList Recommends"}</h4>
<ul class="similar">
  {foreach from=$enrichment.novelist.similarTitles item=similar}
  {if $similar.recordId != -1}
  <li>
    <a href="{$url}/Record/{$similar.recordId|escape:"url"}">{$similar.title|regex_replace:"/(\/|:)$/":""|escape}</a>
    
    <span style="font-size: 80%">
    {if $similar.author}<br />{translate text='By'}: {$similar.author|escape}{/if}
    {if $similar.publishDate} {translate text='Published'}: ({$similar.publishDate.0|escape}){/if}
    </span>
  </li>
  {/if}
  {/foreach}
</ul>
{/if}]]></SimilarTitles>
<ShowGoDeeperData>{$showGoDeeper}</ShowGoDeeperData>
