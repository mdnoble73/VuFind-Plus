<script type="text/javascript" src="{$url}/services/MyResearch/ajax.js"></script>
<script type="text/javascript" src="{$path}/js/holds.js"></script>
{if (isset($title)) }
<script type="text/javascript">
    alert("{$title}");
</script>
{/if}
<div id="page-content" class="content">
  <div id="sidebar-wrapper"><div id="sidebar">
    {include file="MyResearch/menu.tpl"}

    {include file="Admin/menu.tpl"}
  </div></div>

  <div id="main-content">
    {if $user->cat_username}

      {* Display recommendations for the user *}
      {if $strandsAPID && $user->disableRecommendations == 0}
        {assign var="scrollerName" value="Recommended"}
        {assign var="wrapperId" value="recommended"}
        {assign var="scrollerVariable" value="recommendedScroller"}
        {assign var="scrollerTitle" value="Recommended for you"}
        {include file=titleScroller.tpl}

        <script type="text/javascript">
          var recommendedScroller;

          recommendedScroller = new TitleScroller('titleScrollerRecommended', 'Recommended', 'recommended');
          recommendedScroller.loadTitlesFrom('{$url}/Search/AJAX?method=GetListTitles&id=strands:HOME-3&scrollerName=Recommended', false);
        </script>
      {/if}

      <div class="myAccountTitle">{translate text='Your Checked Out Items'}</div>
      {if $userNoticeFile}
        {include file=$userNoticeFile}
      {/if}

      {if $transList}

        <form id="renewForm" action="{$path}/MyResearch/RenewMultiple">
          <div>
            <a href="#" onclick="return renewSelectedTitles();" class="button">Renew Selected Items</a>
            <a href="{$path}/MyResearch/RenewAll" class="button">Renew All</a>
            <a href="{$path}/MyResearch/CheckedOut?exportToExcel" class="button" id="exportToExcel" >Export to Excel</a>
          </div>

          <div id="pager" class="pager">
            {if $pageLinks.all}<div class="myAccountPagination pagination">Page: {$pageLinks.all}</div>{/if}

            <span id="recordsPerPage">
            Records Per Page:
            <select id="pagesize" class="pagesize" onchange="changePageSize()">
              <option value="10" {if $recordsPerPage == 10}selected="selected"{/if}>10</option>
              <option value="25" {if $recordsPerPage == 25}selected="selected"{/if}>20</option>
              <option value="50" {if $recordsPerPage == 50}selected="selected"{/if}>30</option>
              <option value="75" {if $recordsPerPage == 75}selected="selected"{/if}>40</option>
              <option value="100" {if $recordsPerPage == 100}selected="selected"{/if}>50</option>
            </select>
            </span>
            <div class='sortOptions'>
              {translate text='Sort by'}
              <select name="accountSort" id="sort" onchange="changeAccountSort($(this).val());">
              {foreach from=$sortOptions item=sortDesc key=sortVal}
                <option value="{$sortVal}"{if $defaultSortOption == $sortVal} selected="selected"{/if}>{translate text=$sortDesc}</option>
              {/foreach}
              </select>
              Hide Covers <input type="checkbox" onclick="$('.imageColumn').toggle();"/>
            </div>
          </div>

          <div class='clearer'></div>
          <table class="myAccountTable" id="checkedOutTable">
            <thead>
              <tr>
                <th><input id='selectAll' type='checkbox' onclick="toggleCheckboxes('.titleSelect', $(this).attr('checked'));" title="Select All/Deselect All"/></th>
                <th>{translate text='Title'}</th>
                <th>{translate text='Format'}</th>
                <th>{translate text='Out'}</th>
                <th>{translate text='Due'}</th>
                <th>{translate text='Renewed'}</th>
                <th>{translate text='Wait List'}</th>
                <th>{translate text='Rating'}</th>
              </tr>
            </thead>
            <tbody>
          {foreach from=$transList item=record name="recordLoop"}
            <tr id="record{$record.recordId|escape}" class="result {if ($smarty.foreach.recordLoop.iteration % 2) == 0}alt{/if} {if ($smarty.foreach.recordLoop.iteration % 16) == 0}newpage{/if} record{$smarty.foreach.recordLoop.iteration}">
            <td class="titleSelectCheckedOut myAccountCell">
              <input type="checkbox" name="selected[{$record.itemid|escape:"url"}|{$record.itemindex}]" class="titleSelect" id="selected{$record.itemid|escape:"url"}" />
            </td>

            <td class="myAccountCell">
              {if $user->disableCoverArt != 1}
              <div class="imageColumn"> 

                <a href="{$url}/Record/{$record.recordId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" id="descriptionTrigger{$record.recordId|escape:"url"}">
                <img src="{$coverUrl}/bookcover.php?id={$record.recordId}&amp;isn={$record.isbn|@formatISBN}&amp;size=small&amp;upc={$record.upc}&amp;category={$record.format_category.0|escape:"url"}" class="listResultImage" alt="{translate text='Cover Image'}"/>
                </a>

                <div id='descriptionPlaceholder{$record.recordId|escape}' style='display:none'></div>
              </div>
              {/if}

              <div class="myAccountTitleDetails">
              <div class="resultItemLine1">
              <a href="{$url}/Record/{$record.recordId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" class="title">{if !$record.title|regex_replace:"/(\/|:)$/":""}{translate text='Title not available'}{else}{$record.title|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}{/if}</a>
              {if $record.title2}
                <div class="searchResultSectionInfo">
                  {$record.title2|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}
                </div>
                {/if}
              </div>

              <div class="resultItemLine2">
                {if $record.author}
                  {translate text='by'}
                  {if is_array($record.author)}
                    {foreach from=$summAuthor item=author}
                      <a href="{$url}/Author/Home?author={$author|escape:"url"}">{$author|highlight:$lookfor}</a>
                    {/foreach}
                  {else}
                    <a href="{$url}/Author/Home?author={$record.author|escape:"url"}">{$record.author|highlight:$lookfor}</a>
                  {/if}
                {/if}

                {if $record.publicationDate}{translate text='Published'} {$record.publicationDate|escape}{/if}
              </div>

              {if $record.hasEpub} 
                <div id='epubPickupOptions'>
                  {foreach from=$record.links item=link}
                  <div class='button'><a href="{$link.url}">{$link.text}</a></div>
                  {/foreach}
                </div>
              {/if}
              </div>
            </td>
            <td class="myAccountCell">
              {if is_array($record.format)}
                {foreach from=$record.format item=format}
                  {translate text=$format}
                {/foreach}
              {else}
                {translate text=$record.format}
              {/if}
            </td>
            <td class="myAccountCell">      
               {$record.checkoutdate|date_format}
            </td>            
            <td class="myAccountCell">
              {$record.duedate|date_format}
              {if $record.overdue}
                <span class='overdueLabel'>OVERDUE</span>
              {elseif $record.daysUntilDue == 0}
                <span class='dueSoonLabel'>(Due today)</span>
              {elseif $record.daysUntilDue == 1}
                <span class='dueSoonLabel'>(Due tomorrow)</span>
              {elseif $record.daysUntilDue <= 7}
                <span class='dueSoonLabel'>(Due in {$record.daysUntilDue} days)</span>
              {/if}
            </td>  

            <td class="myAccountCell">
              {$record.renewCount}
              {if $record.renewMessage}
                <div class='{if $record.renewResult == true}renewPassed{else}renewFailed{/if}'>
                  {$record.renewMessage|escape}
                </div>
              {/if}
            </td>

            <td class="myAccountCell">
              {* Wait List goes here *}
              {$record.holdQueueLength}
            </td>

            <td class="myAccountCell">                        
            <div id ="searchStars{$record.recordId|escape}" class="resultActions">
              <div class="rate{$record.recordId|escape} stat">
                <div class="statVal">
                  <span class="ui-rater">
                    <span class="ui-rater-starsOff" style="width:90px;"><span class="ui-rater-starsOn" style="width:0px"></span></span>
                    (<span class="ui-rater-rateCount-{$record.recordId|escape} ui-rater-rateCount">0</span>)
                  </span>
                </div>
                  <div id="saveLink{$record.recordId|escape}">
                    {if $showFavorites == 1} 
                    <a href="{$url}/Resource/Save?id={$record.recordId|escape:"url"}&amp;source=VuFind" onclick="getSaveToListForm('{$record.recordId|escape}', 'VuFind'); return false;">{translate text='Add to'} <span class='myListLabel'>MyLIST</span></a>
                    {/if}
                    {if $user}
                      <div id="lists{$record.recordId|escape}"></div>
                      <script type="text/javascript">
                        getSaveStatuses('{$record.recordId|escape:"javascript"}');
                      </script>
                      {/if}
                  </div>
                  {assign var=id value=$record.recordId}
                  {include file="Record/title-review.tpl"}
                </div>
                <script type="text/javascript">
                  $(
                     function() {literal} { {/literal}
                         $('.rate{$record.recordId|escape}').rater({literal}{ {/literal}recordId: {$record.recordId}, rating:0.0, postHref: '{$url}/Record/{$record.recordId|escape}/AJAX?method=RateTitle'{literal} } {/literal});
                     {literal} } {/literal}
                  );
                </script>

              </div>

            {if $record.recordId != -1}
            <script type="text/javascript">
              addRatingId('{$record.recordId|escape:"javascript"}');
              $(document).ready(function(){literal} { {/literal}
                  resultDescription('{$record.recordId}','{$record.recordId}');
              {literal} }); {/literal}
            </script>
            {/if}
            </td>
          </tr>
        {/foreach}
        </tbody>
      </table>

        <div>
          <a href="#" onclick="return renewSelectedTitles();" class="button">Renew Selected Items</a>
          <a href="{$path}/MyResearch/RenewAll" class="button">Renew All</a>
          <a href="{$path}/MyResearch/CheckedOut?exportToExcel" class="button" id="exportToExcel" >Export to Excel</a>
        </div>
      </form>
      
      <script type="text/javascript">
        $(document).ready(function() {literal} { {/literal}
          doGetRatings();
        {literal} }); {/literal}
      </script>
    {else}
      {translate text='You do not have any items checked out'}.
    {/if}
  {else}
    You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
  {/if}
  </div>
</div>