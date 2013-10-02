{strip}
<SimilarAuthors>
<![CDATA[{if $enrichment.novelist.similarAuthorCount != 0 && $showSimilarAuthors}
  <h4 title="Similar Authors from Novelist" id="similarAuthorTitle" >Similar Authors</h4>
  {foreach from=$enrichment.novelist.authors item=similarAuthor}
    <div class="sidebarLabel">
      <a href="{$similarAuthor.link}" title="{$similarAuthor.reason}" class="similarAuthor">{$similarAuthor.name}</a>
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
        <a href ={$path}/Record/{$outer.recordId|escape:"url"}>
     {elseif $outer.isbn10}
       {* Display a link to the record in amazon *}
        <a href =http://amazon.com/dp/{$outer.isbn10|escape:"url"} rel="external" onclick="window.open (this.href, 'child'); return false"}>
     {/if}
     {* Display the book jacket *}
     {if $outer.isbn}
        <img class='bookjacket' src="{$path}/bookcover.php?isn={$outer.isbn|@formatISBN}&amp;size=small" alt="{translate text='Cover Image'}"/>
     {else}
        <img class='bookjacket' src="{$path}/bookcover.php" alt="{translate text='No Cover Image'}"/>
     {/if}
     {* Show the book title *}
     <div class='seriesTitle'>{$outer.title|removeTrailingPunctuation|escape}</div>
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
<h4 title="Similar Titles from NoveList">{translate text="Similar Titles"}</h4>
<ul class="similar">
  {foreach from=$enrichment.novelist.similarTitles item=similar}
  {if $similar.recordId != -1}
  <li>
    <a href="{$path}/Record/{$similar.recordId|escape:"url"}" {if $similar.reason}title="{$similar.reason}"{/if}>{$similar.title|removeTrailingPunctuation|escape}</a>
    
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
{if $enrichment.novelist.relatedContent}
<RelatedContent><![CDATA[
{foreach from=$enrichment.novelist.relatedContent item=contentSection}
	<dt>{$contentSection.title}</dt>
	<dd>
		<ul class="unstyled">
		{foreach from=$contentSection.content item=content}
			<li><a href="{$content.contentUrl}" onclick="return ajaxLightbox('{$path}/Resource/AJAX?method=GetNovelistData&novelistUrl={$content.contentUrl|escape:"url"}')">{$content.title}{if $content.author} by {$content.author}{/if}</a></li>
	  {/foreach}
		</ul>
	</dd>
{/foreach}
]]></RelatedContent>
{/if}
{/strip}