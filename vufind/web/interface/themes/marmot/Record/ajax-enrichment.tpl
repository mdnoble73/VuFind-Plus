<SimilarAuthors>
<![CDATA[{if $enrichment.novelist.similarAuthorCount != 0}
  <a href ="#" class="cite" id="similarAuthorLink" rel="similarAuthor">Similar Authors</a>
  <DIV id="similarAuthor" style="position:absolute; -moz-border-radius: 5px; -webkit-border-radius: 5px; -webkit-box-shadow: 5px 5px 7px 0 #888; padding: 5px; -moz-box-shadow: 5px 5px 7px 0 #888; visibility: hidden; border: 2px solid darkgrey; background-color: white; width: 300px; height:150px;z-index:100;">
  <span class ="alignright"><a href="javascript:dropdowncontent.hidediv('similarAuthor')" class="unavailable">Close</a></span><br />
    <center>
      <table width="290">
      <tr><td colspan="2"><center>Similar Authors</center></td></tr>
        <tr>
          <td><a href={$url}/Author/Home?author={$enrichment.novelist.authors.0|escape:"url"}>{$enrichment.novelist.authors.0}</a></td>
          <td><a href={$url}/Author/Home?author={$enrichment.novelist.authors.1|escape:"url"}>{$enrichment.novelist.authors.1}</a></td>
        </tr>
        <tr>
          <td><a href={$url}/Author/Home?author={$enrichment.novelist.authors.2|escape:"url"}>{$enrichment.novelist.authors.2}</a></td>
          <td><a href={$url}/Author/Home?author={$enrichment.novelist.authors.3|escape:"url"}>{$enrichment.novelist.authors.3}</a></td>
        </tr>
        <tr>
          <td><a href={$url}/Author/Home?author={$enrichment.novelist.authors.4|escape:"url"}>{$enrichment.novelist.authors.4}</a></td>
          <td><a href={$url}/Author/Home?author={$enrichment.novelist.authors.5|escape:"url"}>{$enrichment.novelist.authors.5}</a></td>
        </tr>                 
        <tr>
          <td><a href={$url}/Author/Home?author={$enrichment.novelist.authors.6|escape:"url"}>{$enrichment.novelist.authors.6}</a></td>
          <td><a href={$url}/Author/Home?author={$enrichment.novelist.authors.7|escape:"url"}>{$enrichment.novelist.authors.7}</a></td>
        </tr>                 
        <tr>
          <td><a href={$url}/Author/Home?author={$enrichment.novelist.authors.8|escape:"url"}>{$enrichment.novelist.authors.8}</a></td>
          <td><a href={$url}/Author/Home?author={$enrichment.novelist.authors.9|escape:"url"}>{$enrichment.novelist.authors.9}</a></td>
        </tr>
      </table>
    </center>
  </div>
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
<h4>{translate text="Similar Titles"}</h4>
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
