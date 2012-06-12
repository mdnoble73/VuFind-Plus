<script type="text/javascript" src="{$path}/services/EcontentRecord/ajax.js"></script>
{if !empty($addThis)}
<script type="text/javascript" src="https://s7.addthis.com/js/250/addthis_widget.js?pub={$addThis|escape:"url"}"></script>
{/if}
<script type="text/javascript">
{literal}$(document).ready(function(){{/literal}
  GetEContentHoldingsInfo('{$id|escape:"url"}');
  {if $isbn || $upc}
    GetEnrichmentInfo('{$id|escape:"url"}', '{$isbn10|escape:"url"}', '{$upc|escape:"url"}');
  {/if}
  {if $isbn}
    GetReviewInfo('{$id|escape:"url"}', '{$isbn|escape:"url"}');
  {/if}
    {if $enablePospectorIntegration == 1}
    GetProspectorInfo('{$id|escape:"url"}');
  {/if}
  {if $user}
    redrawSaveStatus();
  {/if}
  {if (isset($title)) }
    //alert("{$title}");
  {/if}
{literal}});{/literal}

function redrawSaveStatus() {literal}{{/literal}
    getSaveStatus('{$id|escape:"javascript"}', 'saveLink');
{literal}}{/literal}
</script>

{if $error}<p class="error">{$error}</p>{/if}
<div id="sidebar-wrapper"><div id="sidebar">
  <div class="sidegroup" id="titleDetailsSidegroup">
    <div id="image-column">
      {if $user->disableCoverArt != 1}
        <div id="cover">
          <a id="goDeeperLink" style="display:none" href="{$path}/Record/{$id|escape:"url"}/GoDeeper" onclick="ajaxLightboxAnythink('{$path}/Record/{$id|escape}/GoDeeper?lightbox', null,'5%', '90%', 50, '85%'); return false;"></a>
          <img alt="{translate text='Book Cover'}" class="recordcover" src="{$bookCoverUrl}" />
        </div>
      {/if}
      <div class='requestThisLink' id="placeHold{$id|escape:"url"}" style="display:none">
        <a class="button" href="{$path}/EcontentRecord/{$id|escape:"url"}/Hold">{translate text="Place Hold"}</a>
      </div>
      <div class='checkoutLink' id="checkout{$id|escape:"url"}" style="display:none">
        <a class="button" href="{$path}/EcontentRecord/{$id|escape:"url"}/Checkout">{translate text="Checkout"}</a>
      </div>
      <div class='accessOnlineLink' id="accessOnline{$id|escape:"url"}" style="display:none">
        <a class="button" href="{$path}/EcontentRecord/{$id|escape:"url"}/Home?detail=holdingstab#detailsTab">{translate text="Access Online"}</a>
      </div>
      <div class='addToWishListLink' id="addToWishList{$id|escape:"url"}" style="display:none">
        <a class="button" href="{$path}/EcontentRecord/{$id|escape:"url"}/AddToWishList">{translate text="Add to List..."}</a>
      </div>
      {if $showOtherEditionsPopup}
      <div id="otherEditionCopies">
        <div style="font-weight:bold"><a href="#" onclick="loadOtherEditionSummariesAnythink('{$id}', true)">{translate text="Other Formats and Languages"}</a></div>
      </div>
      {/if}
      {if $goldRushLink}
      <div class="titledetails">
        <a href="{$goldRushLink}">{translate text="Check for online articles"}</a>
      </div>
      {/if}
      <div id="myrating" class="stat">
        <div class="statVal">
          <div class="ui-rater">
            <span class="ui-rater-starsOff" style="width:90px;"><span class="ui-rater-starsOn" style="width:63px"></span></span>
          </div>
        </div>
        <script type="text/javascript">
        $(
         function() {literal} { {/literal}
           $('#myrating').rater({literal}{ {/literal} module:'EcontentRecord', rating:'{if $user}{$ratingData.user}{else}{$ratingData.average}{/if}', recordId: '{$id}', postHref: '{$path}/EcontentRecord/{$id}/AJAX?method=RateTitle'{literal} } {/literal});
         {literal} } {/literal}
        );
        </script>
      </div>
    </div>

    {if $eContentRecord->author}
    <h4>{translate text='Main Author'}:</h4>
    <ul>
      <li><a href="{$path}/Author/Home?author={$eContentRecord->author|escape:"url"}">{$eContentRecord->author|escape}</a></li>
    </ul>
    {/if}

    {if count($additionalAuthorsList) > 0}
    <h4>{translate text='Additional Authors'}:</h4>
    <ul>
    {foreach from=$additionalAuthorsList item=additionalAuthorsListItem name=loop}
      <li><a href="{$path}/Author/Home?author={$additionalAuthorsListItem|escape:"url"}">{$additionalAuthorsListItem|escape}</a></li>
    {/foreach}
    </ul>
    {/if}

    {if $eContentRecord->publisher}
    <h4>{translate text='Publisher'}:</h4>
    <ul>
      <li>{$eContentRecord->publisher|escape}</li>
    </ul>
    {/if}

    {if $eContentRecord->publishDate}
    <h4>{translate text='Published'}:</h4>
    <ul>
      <li>{$eContentRecord->publishDate|escape}</li>
    </ul>
    {/if}

    <h4>{translate text='Format'}:</h4>
    <ul>
    {if is_array($eContentRecord->format())}
    {foreach from=$eContentRecord->format() item=displayFormat name=loop}
      <li><span class="iconlabel {$displayFormat|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$displayFormat}</span></li>
    {/foreach}
    {else}
      <li><span class="iconlabel {$eContentRecord->format()|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$eContentRecord->format}</span></li>
    {/if}
    </ul>

    <h4>{translate text='Language'}:</h4>
    <ul>
      <li>{$eContentRecord->language|escape}</li>
    </ul>

    {if $eContentRecord->edition}
    <h4>{translate text='Edition'}:</h4>
    <ul>
      <li>{$eContentRecord->edition|escape}</li>
    </ul>
    {/if}

    {if count($lccnList) > 0}
    <h4>{translate text='LCCN'}:</h4>
    <ul>
    {foreach from=$lccnList item=lccnListItem name=loop}
      <li>{$lccnListItem|escape}</li>
    {/foreach}
    </ul>
    {/if}

    {if count($isbnList) > 0}
    <h4>{translate text='ISBN'}:</h4>
    <ul>
    {foreach from=$isbnList item=isbnListItem name=loop}
      <li>{$isbnListItem|escape}</li>
    {/foreach}
    </ul>
    {/if}

    {if count($issnList) > 0}
    <h4>{translate text='ISSN'}:</h4>
    <ul>
    {foreach from=$issnList item=issnListItem name=loop}
      <li>{$issnListItem|escape}</li>
    {/foreach}
    </ul>
    {/if}

    {if count($upcList) > 0}
    <h4>{translate text='UPC'}:</h4>
    <ul>
    {foreach from=$upcList item=upcListItem name=loop}
      <li>{$upcListItem|escape}</li>
    {/foreach}
    </ul>
    {/if}

    {if count($seriesList) > 0}
    <h4>{translate text='Series'}:</h4>
    <ul>
    {foreach from=$seriesList item=seriesListItem name=loop}
      <li><a href="{$path}/Search/Results?lookfor=%22{$seriesListItem|escape:"url"}%22&amp;type=Series">{$seriesListItem|escape}</a></li>
    {/foreach}
    </ul>
    {/if}

    {if count($topicList) > 0}
    <h4>{translate text='Topic'}:</h4>
    <ul>
    {foreach from=$topicList item=topicListItem name=loop}
      <li>{$topicListItem|escape}</li>
    {/foreach}
    </ul>
    {/if}

    {if count($genreList) > 0}
    <h4>{translate text='Genre'}:</h4>
    <ul>
    {foreach from=$genreList item=genreListItem name=loop}
      <li>{$genreListItem|escape}</li>
    {/foreach}
    </ul>
    {/if}

    {if count($regionList) > 0}
    <h4>{translate text='Region'}:</h4>
    <ul>
    {foreach from=$regionList item=regionListItem name=loop}
      <li>{$regionListItem|escape}</li>
    {/foreach}
    </ul>
    {/if}

    {if count($eraList) > 0}
    <h4>{translate text='Era'}:</h4>
    <ul>
    {foreach from=$eraList item=eraListItem name=loop}
      <li>{$eraListItem|escape}</li>
    {/foreach}
    </ul>
    {/if}
  </div>

  <div class="sidegroup" id="similarTitlesSidegroup">
   {* Display either similar tiles from novelist or from the catalog*}
   <div id="similarTitlePlaceholder"></div>
   {if is_array($similarRecords)}
   <div id="relatedTitles">
    <h4>{translate text="Other Titles"}</h4>
    <ul class="similar">
      {foreach from=$similarRecords item=similar}
      <li>
        {if is_array($similar.format)}
          <span class="icon-{$similar.format[0]|lower|regex_replace:"/[^a-z0-9]/":""}">
        {else}
          <span class="icon-{$similar.format|lower|regex_replace:"/[^a-z0-9]/":""}">
        {/if}
        <a href="{$path}/Record/{$similar.id|escape:"url"}">{$similar.title|regex_replace:"/(\/|:)$/":""|escape}</a>
        </span>
        {if $similar.author}<div class="fine-print">{translate text='By'}: {$similar.author|escape}</div>{/if}
      </li>
      {/foreach}
    </ul>
   </div>
   {/if}
  </div>

  <div class="sidegroup" id="similarAuthorsSidegroup">
    <div id="similarAuthorPlaceholder"></div>
  </div>

  {if is_array($editions) && !$showOtherEditionsPopup}
  <div class="sidegroup" id="otherEditionsSidegroup">
    <h4>{translate text="Other Editions"}</h4>
      {foreach from=$editions item=edition}
        <h4>
          {if $edition.recordtype == 'econtentRecord'}
          <a href="{$path}/EcontentRecord/{$edition.id|replace:'econtentRecord':''|escape:"url"}">{$edition.title|regex_replace:"/(\/|:)$/":""|escape}</a>
          {else}
          <a href="{$path}/Record/{$edition.id|escape:"url"}">{$edition.title|regex_replace:"/(\/|:)$/":""|escape}</a>
          {/if}
        </h4>
        {if is_array($edition.format)}
          {foreach from=$edition.format item=format}
            <span class="icon-{$format|lower|regex_replace:"/[^a-z0-9]/":""}">{$format}</span>
          {/foreach}
        {else}
          <span class="icon-{$edition.format|lower|regex_replace:"/[^a-z0-9]/":""}">{$edition.format}</span>
        {/if}
        {$edition.edition|escape}
        {if $edition.publishDate}({$edition.publishDate.0|escape}){/if}
      {/foreach}
  </div>
  {/if}

  {if $enablePospectorIntegration == 1}
  <div class="sidegroup">
    {* Display in Prospector Sidebar *}
    <div id="inProspectorPlaceholder"></div>
  </div>
  {/if}

  {if $linkToAmazon == 1 && $isbn}
  <div class="sidegroup">
    <h4>{translate text="Elsewhere"}:</h4>
    <ul><li><a href="http://amazon.com/dp/{$isbn|@formatISBN}"> {translate text="View on Amazon"}</a></li></ul>
  </div>
  {/if}
</div></div>
<div id="main-content" class="full-result-content">
  <div id="record-header">
    <div id="title-container">
      {* Display Title *}
      <h1>{$eContentRecord->title|regex_replace:"/(\/|:)$/":""|escape}</h1>
      {if $user && $user->hasRole('epubAdmin')}
      <div>
        {if $eContentRecord->status != 'active'}<h4>({$eContentRecord->status})</h4>{/if}
        <ul>
          <li><a href='{$path}/EcontentRecord/{$id}/Edit'>(edit)</a></li>
          {if $eContentRecord->status != 'archived' && $eContentRecord->status != 'deleted'}
            <li><a href='{$path}/EcontentRecord/{$id}/Archive' onclick="return confirm('Are you sure you want to archive this record?  The record should not have any holds or checkouts when it is archived.')">(archive)</a></li>
          {/if}
          {if $eContentRecord->status != 'deleted'}
            <li><a href='{$path}/EcontentRecord/{$id}/Delete' onclick="return confirm('Are you sure you want to delete this record?  The record should not have any holds or checkouts when it is deleted.')">(delete)</a></li>
          {/if}
        </ul>
      </div>
      {/if}
      {* Display more information about the title*}
      {if $eContentRecord->author}
        <h3>by <a href="{$path}/Author/Home?author={$eContentRecord->author|escape:"url"}">{$eContentRecord->author|escape}</a></h3>
      {/if}
    </div>
    <div id="record-title-nav-wrapper"><div id="record-title-nav">
      {if isset($previousId) || isset($nextId)}
        <div id="nav-buttons">
          {if isset($previousId)}
            <a class="button" href="{$path}/{$previousType}/{$previousId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$previousIndex}&amp;page={if isset($previousPage)}{$previousPage}{else}{$page}{/if}" title="{if !$previousTitle}{translate text='Previous'}{else}{$previousTitle|truncate:180:"..."}{/if}">&lt; Prev</a>
          {/if}
          {if isset($nextId)}
            <a class="button" href="{$path}/{$nextType}/{$nextId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$nextIndex}&amp;page={if isset($nextPage)}{$nextPage}{else}{$page}{/if}" title="{if !$nextTitle}{translate text='Next'}{else}{$nextTitle|truncate:180:"..."}{/if}">Next &gt;</a>
          {/if}
        </div>
      {/if}
      {if $lastsearch}
      <div id="returnToSearch">
        <a href="{$lastsearch|escape}#record{$id|escape:"url"}">{translate text="Return to Search Results"}</a>
      </div>
      {/if}
    </div></div>
  </div>
  <div id="tools-column">
    <div class="actions-first">
      {if $showFavorites == 1}
        <div id="saveLink" class="actions-save"><a class="button" href="{$path}/Resource/Save?id={$id|escape:"url"}&amp;source=eContent" class="fav" onclick="getSaveToListForm('{$id|escape}', 'eContent'); return false;">{translate text="Add to favorites"}</a></div>
      {/if}
    </div>
    <div class="actions-second" id="recordTools">
        {if !$tabbedDetails}
          <div><a href="{$path}/EcontentRecord/{$id|escape:"url"}/Cite" class="cite" id="citeLink" onclick='ajaxLightboxAnythink("{$path}/EcontentRecord/{$id|escape}/Cite?lightbox", "#citeLink"); return false;'>{translate text="Cite this"}</a></div>
        {/if}
        {if $showTextThis == 1}
          <div><a href="{$path}/EcontentRecord/{$id|escape:"url"}/SMS" class="sms" id="smsLink" onclick="ajaxLightboxAnythink('{$path}/EcontentRecord/{$id|escape}/SMS?lightbox', '#citeLink'); return false;">{translate text="Text this"}</a></div>
        {/if}
        {if $showEmailThis == 1}
          <div><a href="{$path}/EcontentRecord/{$id|escape:"url"}/Email" class="mail" id="mailLink" onclick="ajaxLightboxAnythink('{$path}/EcontentRecord/{$id|escape}/Email?lightbox', '#citeLink'); return false;">{translate text="Email this"}</a></div>
        {/if}
        {if is_array($exportFormats) && count($exportFormats) > 0}
          <div><a href="{$path}/EcontentRecord/{$id|escape:"url"}/Export?style={$exportFormats.0|escape:"url"}" class="export" onclick="toggleMenu('exportMenu'); return false;">{translate text="Export Record"}</a>
          <ul class="menu" id="exportMenu">
            {foreach from=$exportFormats item=exportFormat}
              <li><a {if $exportFormat=="RefWorks"} {/if}href="{$path}/EcontentRecord/{$id|escape:"url"}/Export?style={$exportFormat|escape:"url"}">{translate text="Export to"} {$exportFormat|escape}</a></li>
            {/foreach}
          </ul></div>
        {/if}
        {if !empty($addThis)}
          <div id="addThis"><a class="addThis addthis_button" href="https://www.addthis.com/bookmark.php?v=250&amp;pub={$addThis|escape:"url"}">{translate text='Bookmark'}</a></div>
        {/if}
    </div>
    {if $showTagging == 1}
    <div class="actions-third">
      <h4>{translate text="Tags"}</h4>
      <div id="tagList">
        {if $tagList}
        <ul>
          {foreach from=$tagList item=tag name=tagLoop}
            <li><a href="{$path}/Search/Results?tag={$tag->tag|escape:"url"}">{$tag->tag|escape:"html"}</a> ({$tag->cnt})</li>
          {/foreach}
        </ul>
        {else}
          {translate text='No Tags'}, {translate text='Be the first to tag this record'}!
        {/if}
          <a href="{$path}/Resource/AddTag?id={$id|escape:"url"}&amp;source=eContent" class="tool add"
            onclick="GetAddTagFormAnythink('{$id|escape}', 'eContent'); return false;">{translate text="Add Tag"}</a>
      </div>
    </div>
    {/if}
  </div>
  <div id="record-details-column">
    <div id="record-details-header">
      <div id="holdingsSummaryPlaceholder" class="holdingsSummaryRecord"></div>
      {if $enableProspectorIntegration == 1}
      <div id="prospectorHoldingsPlaceholder"></div>
      {/if}
    </div>
    {if $eContentRecord->description}
    <div class="resultInformation">
      <h4>{translate text='Description'}</h4>
      <div class="recordDescription">
        {$eContentRecord->description|escape}
      </div>
    </div>
    {/if}
    {if count($subjectList) > 0}
    <div class="resultInformation">
      <h4>{translate text='Subjects'}</h4>
      <ul>
        {foreach from=$subjectList item=subjectListItem name=loop}
          <li><a href="{$path}/Search/Results?lookfor=%22{$subjectListItem|escape:'url'}%22&amp;type=Subject">{$subjectListItem|escape}</a></li>
        {/foreach}
      </ul>
    </div>
    {/if}

    {if $showStrands}
      <div id="relatedTitleInfo" class="ui-tabs">
        <ul>
          <li><a href="#list-similar-titles">Similar Titles</a></li>
          <li><a href="#list-also-viewed">People who viewed this also viewed</a></li>
          <li><a id="list-series-tab" href="#list-series" style="display:none">Also in this series</a></li>
        </ul>

        {assign var="scrollerName" value="SimilarTitles"}
        {assign var="wrapperId" value="similar-titles"}
        {assign var="scrollerVariable" value="similarTitleScroller"}
        {include file=titleScroller.tpl}

        {assign var="scrollerName" value="AlsoViewed"}
        {assign var="wrapperId" value="also-viewed"}
        {assign var="scrollerVariable" value="alsoViewedScroller"}
        {include file=titleScroller.tpl}


        {assign var="scrollerName" value="Series"}
        {assign var="wrapperId" value="series"}
        {assign var="scrollerVariable" value="seriesScroller"}
        {assign var="fullListLink" value="$path/Record/$id/Series"}
        {include file=titleScroller.tpl}

      </div>
      <script type="text/javascript">
      {literal}
        var similarTitleScroller;
        var alsoViewedScroller;

        $(function() {
          $("#relatedTitleInfo").tabs();
          $("#moredetails-tabs").tabs();

          {/literal}
          {if $defaultDetailsTab}
            $("#moredetails-tabs").tabs('select', '{$defaultDetailsTab}');
          {/if}

          similarTitleScroller = new TitleScroller('titleScrollerSimilarTitles', 'SimilarTitles', 'similar-titles');
          similarTitleScroller.loadTitlesFrom('{$url}/Search/AJAX?method=GetListTitles&id=strands:PROD-2&recordId={$id}&scrollerName=SimilarTitles', false);

          {literal}
          $('#relatedTitleInfo').bind('tabsshow', function(event, ui) {
            if (ui.index == 0) {
              similarTitleScroller.activateCurrentTitle();
            }else if (ui.index == 1) {
              if (alsoViewedScroller == null){
                {/literal}
                alsoViewedScroller = new TitleScroller('titleScrollerAlsoViewed', 'AlsoViewed', 'also-viewed');
                alsoViewedScroller.loadTitlesFrom('{$url}/Search/AJAX?method=GetListTitles&id=strands:PROD-1&recordId={$id}&scrollerName=AlsoViewed', false);
              {literal}
              }else{
                alsoViewedScroller.activateCurrentTitle();
              }
            }
          });
        });
        {/literal}
      </script>
    {elseif $showSimilarTitles}
      <div id="relatedTitleInfo" class="ui-tabs">
        <ul>
          <li><a href="#list-similar-titles">Similar Titles</a></li>
          <li><a id="list-series-tab" href="#list-series" style="display:none">Also in this series</a></li>
        </ul>

        {assign var="scrollerName" value="SimilarTitlesVuFind"}
        {assign var="wrapperId" value="similar-titles-vufind"}
        {assign var="scrollerVariable" value="similarTitleVuFindScroller"}
        {include file=titleScroller.tpl}

        {assign var="scrollerName" value="Series"}
        {assign var="wrapperId" value="series"}
        {assign var="scrollerVariable" value="seriesScroller"}
        {assign var="fullListLink" value="$path/Record/$id/Series"}
        {include file=titleScroller.tpl}

      </div>
      <script type="text/javascript">
      {literal}
        var similarTitleScroller;
        var alsoViewedScroller;

        $(function() {
          $("#relatedTitleInfo").tabs();
          $("#moredetails-tabs").tabs();

          {/literal}
          {if $defaultDetailsTab}
            $("#moredetails-tabs").tabs('select', '{$defaultDetailsTab}');
          {/if}

          similarTitleVuFindScroller = new TitleScroller('titleScrollerSimilarTitles', 'SimilarTitles', 'similar-titles');
          similarTitleVuFindScroller.loadTitlesFrom('{$url}/Search/AJAX?method=GetListTitles&id=similarTitles&recordId={$id}&scrollerName=SimilarTitles', false);

          {literal}
          $('#relatedTitleInfo').bind('tabsshow', function(event, ui) {
            if (ui.index == 0) {
              similarTitleVuFindScroller.activateCurrentTitle();
            }
          });
        });
        {/literal}
      </script>
    {else}
      <div id="relatedTitleInfo" style="display:none">
        {assign var="scrollerName" value="Series"}
        {assign var="wrapperId" value="series"}
        {assign var="scrollerVariable" value="seriesScroller"}
        {assign var="fullListLink" value="$path/Record/$id/Series"}
        {include file=titleScroller.tpl}
      </div>
    {/if}

    <div id="moredetails-tabs">
      {* Define tabs for the display *}
      <ul>
        <li><a href="#holdingstab">{translate text="Copies"}</a></li>
        {if $notes}
          <li><a href="#notestab">{translate text="Notes"}</a></li>
        {/if}
        {if $showAmazonReviews || $showStandardReviews}
          <li><a href="#reviewtab">{translate text="Reviews and Trailers"}</a></li>
        {/if}
        <li><a href="#readertab">{translate text="Comments"}</a></li>
        <li><a href="#citetab">{translate text="Citations"}</a></li>
        <li><a href="#stafftab">{translate text="Staff View"}</a></li>
      </ul>

      {* Display the content of individual tabs *}
      {if $notes}
        <div id="notestab">
          <ul class='notesList'>
          {foreach from=$notes item=note}
            <li>{$note}</li>
          {/foreach}
          </ul>
        </div>
      {/if}

      <div id="reviewtab">
        <div id="staffReviewtab" >
        {include file="Record/view-staff-reviews.tpl"}
        </div>

        {if $showAmazonReviews || $showStandardReviews}
        <h4>Professional Reviews</h4>
        <div id='reviewPlaceholder'></div>
        {/if}
      </div>

      {if $showComments == 1}
        <div id="readertab">
          <div class="alignright" id="addReview"><span id="userreviewlink" class="add" onclick="$('#userreview{$id}').slideDown();">{translate text="Add a Comment"}</span></div>
          <div id="userreview{$id}" class="userreview">
            <span class="alignright unavailable closeReview" onclick="$('#userreview{$id}').slideUp();" >Close</span>
            <div class='addReviewTitle'>Add your Review</div>
            {assign var=id value=$id}
            {include file="EcontentRecord/submit-comments.tpl"}
          </div>
          {include file="EcontentRecord/view-comments.tpl"}

          {* Chili Fresh Reviews *}
          {if $chiliFreshAccount && ($isbn || $upc || $issn)}
            <h4>Chili Fresh Reviews</h4>
            {if $isbn}
            <div class="chili_review" id="isbn_{$isbn10}"></div>
            <div id="chili_review_{$isbn10}" style="display:none" align="center" width="100%"></div>
            {elseif $upc}
            <div class="chili_review_{$upc}" id="isbn"></div>
            <div id="chili_review_{$upc}" style="display:none" align="center" width="100%"></div>
            {elseif $issn}
            <div class="chili_review_{$issn}" id="isbn"></div>
            <div id="chili_review_{$issn}" style="display:none" align="center" width="100%"></div>
            {/if}
          {/if}
        </div>
      {/if}

      <div id="citetab" >
        {include file="Record/cite.tpl"}
      </div>

      <div id="holdingstab">
        <div id="holdingsPlaceholder">Loading...</div>
        {if $showOtherEditionsPopup}
        <div id="otherEditionCopies">
          <div style="font-weight:bold"><a href="#" onclick="loadOtherEditionSummaries('{$id}', true)">{translate text="Other Formats and Languages"}</a></div>
        </div>
        {/if}
        {if $enablePurchaseLinks == 1}
          <div class='purchaseTitle button'><a href="#" onclick="return showEcontentPurchaseOptions('{$id}');">{translate text='Buy a Copy'}</a></div>
        {/if}
       {if $eContentRecord->sourceUrl}
        <div id="econtentSource">
          <a href="{$eContentRecord->sourceUrl}">Access original files</a>
        </div>
        {/if}
      </div>

      {if $eContentRecord->marcRecord}
        <div id="stafftab">
          <pre style="overflow:auto">{strip}
          {$eContentRecord->marcRecord}
          {/strip}</pre>
        </div>
      {/if}
    </div> {* End of tabs*}
  </div>
  <script type="text/javascript">
  {literal}
    $(function() {
      $("#moredetails-tabs").tabs();
    });
  {/literal}
  </script>
</div>

{if $showStrands}
<!-- Event definition to be included in the body before the Strands js library -->
<script type="text/javascript">
{literal}
if (typeof StrandsTrack=="undefined"){StrandsTrack=[];}
StrandsTrack.push({
   event:"visited",
   item: "{/literal}econtentRecord{$id|escape}{literal}"
});
{/literal}
</script>
{/if}
     