{if (isset($title)) }
<script type="text/javascript">
alert("{$title}");
</script>
{/if}
<script type="text/javascript" src="{$path}/js/holds.js"></script>
<script type="text/javascript" src="{$path}/services/MyResearch/ajax.js"></script>
<script type="text/javascript" src="{$path}/js/tablesorter/jquery.tablesorter.min.js"></script>
<div id="sidebar-wrapper"><div id="sidebar">
  {include file="MyResearch/menu.tpl"}
  {include file="Admin/menu.tpl"}
</div></div>
<div id="main-content">
  <h1>{if strlen($user->displayName) > 0}{$user->displayName}{else}{$user->firstname|capitalize} {$user->lastname|capitalize}{/if}</h1>
  {if $user->cat_username}
    {if $showStrands && $user->disableRecommendations == 0}
      {* Display recommendations for the user *}
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
    <h2>{translate text='Holds'}</h2>
    {if $userNoticeFile}
      {include file=$userNoticeFile}
    {/if}
    {foreach from=$recordList item=recordData key=sectionKey}
      {* Check to see if there is data for the secion *}
      <div class='holdSection'>
        {if $sectionKey=='available'}
          <a name="availableHoldsSection" rel="section"></a>
        {else}
          <a name="unavailableHoldsSection" rel="section"></a>
        {/if}
        <div class='holdSectionTitle'>{if $sectionKey=='available'}Arrived at pickup location{else}Requested items not yet available:{/if}</div>
          <div class='holdSectionBody'>
            {if is_array($recordList.$sectionKey) && count($recordList.$sectionKey) > 0}
              {if $sectionKey=='available' && $libraryHoursMessage}
                <div class='libraryHours'>{$libraryHoursMessage}</div>
              {/if}
              {* Form to update holds at one time *}
              <div id='holdsWithSelected{$sectionKey}Top' class='holdsWithSelected{$sectionKey}'>
                <form id='withSelectedHoldsFormTop{$sectionKey}' action='{$fullPath}'>
                  <div>
                    <input type="hidden" name="withSelectedAction" value="" />
                    <div id='holdsUpdateSelected{$sectionKey}'>
                      {if $allowFreezeHolds && $sectionKey=='unavailable'}
                        {if $showDateWhenSuspending}
                          Suspend until (MM/DD/YYYY):
                          <input type="text" size="10" name="suspendDateTop" id="suspendDateTop" value="" />
                          <script type="text/javascript">{literal}
                            $(function() {
                              $( "#suspendDateTop" ).datepicker({ minDate: 0, showOn: "both", buttonImage: "{/literal}{$path}{literal}/images/silk/calendar.png", numberOfMonths: 2,  buttonImageOnly: true});
                            });{/literal}
                          </script>
                        {/if}
                        <input type="submit" class="button" name="freezeSelected" value="Suspend Selected" title="Suspending a hold prevents the hold from being filled, but keeps your place in queue. This is great if you are going on vacation or want to space out your holds." onclick="return freezeSelectedHolds();"/>
                        <input type="submit" class="button" name="thawSelected" value="Activate Selected" title="Activate the hold to allow the hold to be filled again." onclick="return thawSelectedHolds();"/>
                      {/if}
                      <input type="submit" class="button" name="cancelSelected" value="Cancel Selected" onclick="return cancelSelectedHolds();"/>
                      <input type="submit" class="button" id="exportToExcel{if $sectionKey=='available'}Available{else}Unavailable{/if}" name="exportToExcel{if $sectionKey=='available'}Available{else}Unavailable{/if}" value="Export to Excel">
                    </div>
                  </div>
                </form> {* End with selected controls for holds *}
              </div>
              <div id="pager" class="pager">
                {if $sectionKey=="unavailable"}
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
                {/if}
                <div class='sortOptions'>
                  {if $sectionKey=="unavailable"}
                  {translate text='Sort'}
                  <select name="accountSort" id="sort{$sectionKey}" onchange="changeAccountSort($(this).val());">
                  {foreach from=$sortOptions item=sortDesc key=sortVal}
                    <option value="{$sortVal}"{if $defaultSortOption == $sortVal} selected="selected"{/if}>{translate text=$sortDesc}</option>
                  {/foreach}
                  </select>
                  {/if}
                  Hide Covers <input type="checkbox" onclick="$('.imageColumn').toggle();"/>
                </div>
              </div>
              {* Make sure there is a break between the form and the table *}
              <div class='clearer'></div>
              <table class="myAccountTable" id="holdsTable{$sectionKey}">
                <thead>
                  <tr>
                    <th><input id='selectAll{$sectionKey}' type='checkbox' onclick="toggleCheckboxes('.titleSelect{$sectionKey}', $(this).attr('checked'));" title="Select All/Deselect All"/></th>
                    <th>{translate text='Title'}</th>
                    <th>{translate text='Format'}</th>
                    {if $showPlacedColumn}
                    <th>{translate text='Placed'}</th>
                    {/if}
                    <th>{translate text='Pickup'}</th>
                    {if $sectionKey=='available'}
                      <th>{translate text='Available'}</th>
                      <th>{translate text='Expires'}</th>
                    {else}
                      {if $showPosition}
                      <th>{translate text='Position'}</th>
                      {/if}
                      <th>{translate text='Status'}</th>
                    {/if}
                    <th>{translate text='Rating'}</th>
                  </tr>
                </thead>
                <tbody>
                  {foreach from=$recordList.$sectionKey item=record name="recordLoop"}
                    {if ($smarty.foreach.recordLoop.iteration % 2) == 0}
                      <tr id="record{$record.recordId|escape}" class="result alt record{$smarty.foreach.recordLoop.iteration}">
                    {else}
                      <tr id="record{$record.recordId|escape}" class="result record{$smarty.foreach.recordLoop.iteration}">
                    {/if}
                    <td class="titleSelectCheckedOut myAccountCell">
                      {if $sectionKey=='available'}
                        <input type="checkbox" name="availableholdselected[]" value="{$record.cancelId}" id="selected{$record.cancelId|escape:"url"}" class="titleSelect{$sectionKey} titleSelect"/>&nbsp;
                      {else}
                        <input type="checkbox" name="waitingholdselected[]" value="{$record.cancelId}" id="selected{$record.cancelId|escape:"url"}" class="titleSelect{$sectionKey} titleSelect"/>&nbsp;
                      {/if}
                    </td>
                    <td class="myAccountCell">
                      {if $user->disableCoverArt != 1}
                      <div class="imageColumn">
                        <div id='descriptionPlaceholder{$record.recordId|escape}' style='display:none'></div>
                        <a href="{$url}/Record/{$record.recordId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" id="descriptionTrigger{$record.recordId|escape:"url"}">
                        <img src="{$coverUrl}/bookcover.php?id={$record.recordId}&amp;isn={$record.isbn|@formatISBN}&amp;size=small&amp;upc={$record.upc}&amp;category={$record.format_category.0|escape:"url"}" class="listResultImage" alt="{translate text='Cover Image'}"/>
                        </a>
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
                    {if $showPlacedColumn}
                    <td class="myAccountCell">
                      {$record.create|date_format}
                    </td>
                    {/if}
                    <td class="myAccountCell">
                      {if $sectionKey=='available' && $record.hasEpub}
                        {* Display checkout link *}
                        {foreach from=$record.links item=link}
                          <a href="{$link.url}" class="button">{$link.text}</a>
                        {/foreach}
                      {else}
                          {$record.location}
                      {/if}
                    </td>
                    {if $sectionKey=='unavailable'}
                      {if $showPosition}
                      <td class="myAccountCell">
                        {$record.position}
                      </td>
                      {/if}
                      <td class="myAccountCell">
                          {if $record.frozen}
                            <span class='frozenHold'>
                          {/if}{$record.status}
                          {if $record.frozen && $showDateWhenSuspending}until {$record.reactivate|date_format}</span>{/if}
                          {if strlen($record.freezeMessage) > 0}
                            <div class='{if $record.freezeResult == true}freezePassed{else}freezeFailed{/if}'>
                              {$record.freezeMessage|escape}
                            </div>
                          {/if}
                      </td>
                    {/if}
                    {if $sectionKey=='available'}
                      <td class="myAccountCell">
                      {if $record.availableTime}
                        {$record.availableTime|date_format:"%b %d, %Y at %l:%M %p"}
                      {else}
                        Now
                      {/if}
                      </td>
                      <td class="myAccountCell">
                      {$record.expire|date_format:"%b %d, %Y"}
                      </td>
                    {/if}
                    <td class="myAccountCell">
                      <div id ="searchStars{$record.shortId|escape}" class="resultActions">
                        <div class="rate{$record.shortId|escape} stat">
                          <div class="statVal">
                            <span class="ui-rater">
                              <span class="ui-rater-starsOff" style="width:90px;"><span class="ui-rater-starsOn" style="width:0px"></span></span>
                              (<span class="ui-rater-rateCount-{$record.recordId|escape} ui-rater-rateCount">0</span>)
                            </span>
                          </div>
                            <div id="saveLink{$record.shortId|escape}">
                              {if $showFavorites == 1}
                              <a href="{$url}/Resource/Save?id={$record.recordId|escape:"url"}&amp;source=VuFind" style="padding-left:8px;" onclick="getSaveToListForm('{$record.recordId|escape}', 'VuFind'); return false;">{translate text='Add to'} <span class='myListLabel'>MyLIST</span></a>
                              {/if}
                              {if $user}
                                <div id="lists{$record.shortId|escape}"></div>
                            <script type="text/javascript">
                              getSaveStatuses('{$record.recordId|escape:"javascript"}');
                            </script>
                              {/if}
                            </div>
                          </div>
                          <script type="text/javascript">
                            $(
                               function() {literal} { {/literal}
                                   $('.rate{$record.shortId|escape}').rater({literal}{ {/literal}module: 'Record', recordId: '{$record.recordId}',  rating:0.0, postHref: '{$url}/Record/{$record.recordId|escape}/AJAX?method=RateTitle'{literal} } {/literal});
                               {literal} } {/literal}
                            );
                          </script>
                          {assign var=id value=$record.recordId}
                          {assign var=shortId value=$record.shortId}
                          {include file="Record/title-review.tpl"}
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
            {* Code to handle updating multiple holds at one time *}
            <div class='holdsWithSelected{$sectionKey}'>
              <form id='withSelectedHoldsFormBottom{$sectionKey}' action='{$fullPath}'>
                <div>
                  <input type="hidden" name="withSelectedAction" value="" />
                  <div id='holdsUpdateSelected{$sectionKey}Bottom' class='holdsUpdateSelected{$sectionKey}'>
                    {if $allowFreezeHolds && $sectionKey=='unavailable'}
                      {if $showDateWhenSuspending}
                        Suspend until (MM/DD/YYYY):
                        <input type="text" size="10" name="suspendDateBottom" id="suspendDateBottom" value="" />
                        <script type="text/javascript">{literal}
                          $(function() {
                            $( "#suspendDateBottom" ).datepicker({ minDate: 0, showOn: "both", buttonImage: "{/literal}{$path}{literal}/images/silk/calendar.png", numberOfMonths: 2, buttonImageOnly: true});
                          });{/literal}
                        </script>
                      {/if}
                      <input type="submit" class="button" name="freezeSelected" value="Suspend Selected" title="Suspending a hold prevents the hold from being filled, but keeps your place in queue. This is great if you are going on vacation or want to space out your holds." onclick="return freezeSelectedHolds();"/>
                      <input type="submit" class="button" name="thawSelected" value="Activate Selected" title="Activate the hold to allow the hold to be filled again." onclick="return thawSelectedHolds();"/>
                    {/if}
                    <input type="submit" class="button" name="cancelSelected" value="Cancel Selected" onclick="return cancelSelectedHolds();"/>
                    {if $allowChangeLocation}
                      <div id='holdsUpdateBranchSelction'>
                        Change Pickup Location for Selected Items to:
                        {html_options name="withSelectedLocation" options=$pickupLocations selected=$resource.currentPickupId}
                        <input type="submit" name="updateSelected" value="Go" onclick="return updateSelectedHolds();"/>
                      </div>
                    {/if}
                    <input type="submit" class="button" id="exportToExcel{if $sectionKey=='available'}Available{else}Unavailable{/if}" name="exportToExcel{if $sectionKey=='available'}Available{else}Unavailable{/if}" value="Export to Excel">
                  </div>
                </div>
              </form>
            </div>
          {else} {* Check to see if records are available *}
            {if $sectionKey=='available'}
              {translate text='You do not have any holds that are ready to be picked up'}.
            {else}
              {translate text='You do not have any holds that are not available yet'}.
            {/if}
          {/if}
        </div>
      </div>
    {/foreach} {* End loop through sections *}
    <script type="text/javascript">
      $(document).ready(function() {literal} { {/literal}
        doGetRatings();
        $("#holdsTableavailable").tablesorter({literal}{cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', headers: { 0: { sorter: false}, 3: {sorter : 'date'}, 4: {sorter : 'date'}, 7: { sorter: false} } }{/literal});
      {literal} }); {/literal}
    </script>
  {else} {* Check to see if user is logged in *}
    You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
  {/if}
</div>
