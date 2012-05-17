{if (isset($title)) }
<script type="text/javascript">
  alert("{$title}");
</script>
{/if}
<script type="text/javascript" src="{$path}/js/holds.js"></script>
<script type="text/javascript" src="{$path}/js/tablesorter/jquery.tablesorter.min.js"></script>
<div id="page-content" class="content">
  <div id="sidebar">
    {include file="MyResearch/menu.tpl"}
    
    {include file="Admin/menu.tpl"}
  </div>
  
  <div id="main-content">
    {if $user->cat_username}
      <div class="myAccountTitle">{translate text='Holds'}</div>
      {if $userNoticeFile}
        {include file=$userNoticeFile}
      {/if}
      
      {foreach from=$recordList item=recordData key=sectionKey}
      
        {* Check to see if there is data for the secion *}
        {if is_array($recordList.$sectionKey)}
          <div class='holdSection'>
            <div class='holdSectionTitle'>{if $sectionKey=='available'}Arrived at pickup location{else}Requested items not yet available:{/if}</div>
              <div class='holdSectionBody'>
              {if $sectionKey=='available'}
    	        	<div class='libraryHours'>{$libraryHoursMessage}</div>
    	        {/if}
          	
              <div class='sortOptions'>
                {*
                {translate text='Sort'}
                <select name="sort" id="sort{$sectionKey}" onchange="changeSort($(this).val());">
                {foreach from=$sortOptions item=sortDesc key=sortVal}
                  <option value="{$sortVal}"{if $defaultSortOption == $sortVal} selected="selected"{/if}>{translate text=$sortDesc}</option>
                {/foreach}
                </select> *}
                {translate text='Hide Covers'} <input type="checkbox" onclick="$('.imageColumn').toggle();"/>
              </div>
            
              {* Form to update holds at one time *}
              <div id='holdsWithSelected{$sectionKey}Top' class='holdsWithSelected{$sectionKey}'>
                <form id='withSelectedHoldsFormTop{$sectionKey}' action='{$fullPath}'>
                  <div>
                    <input type="hidden" name="withSelectedAction" value="" />
                    <div id='holdsUpdateSelected{$sectionKey}'>
                      {if $allowFreezeHolds && $sectionKey=='unavailable'}
                        Suspend until (MM/DD/YYYY): 
                        <input type="text" size="10" name="suspendDateTop" id="suspendDateTop" value="" />
                        <script type="text/javascript">{literal}
                          $(function() {
                            $( "#suspendDateTop" ).datepicker({ minDate: 0, showOn: "both", buttonImage: "{/literal}{$path}{literal}/images/silk/calendar.png", numberOfMonths: 2,  buttonImageOnly: true});
                          });{/literal}
                        </script>
                        <input type="submit" class="button" name="freezeSelected" value="Suspend Selected" title="Suspending a hold prevents the hold from being filled, but keeps your place in queue. This is great if you are going on vacation or want to space out your holds." onclick="return freezeSelectedHolds();"/>
                        <input type="submit" class="button" name="thawSelected" value="Activate Selected" title="Activate the hold to allow the hold to be filled again." onclick="return thawSelectedHolds();"/>
                      {/if}
                      <input type="submit" class="button" name="cancelSelected" value="Cancel Selected" onclick="return cancelSelectedHolds();"/>
                    </div>
                  </div>
                </form> {* End with selected controls for holds *}
              </div>
                
              {* Make sure there is a break between the form and the table *}  
              <div class='clearer'></div>
            
          <table class="myAccountTable" id="holdsTable{$sectionKey}">
            <thead>
              <tr>
                <th><input id='selectAll{$sectionKey}' type='checkbox' onclick="$('.titleSelect{$sectionKey}').attr('checked', $('#selectAll{$sectionKey}').attr('checked'));" title="Select All/Deselect All"/></th>
                <th>{translate text='Title'}</th>
                <th>{translate text='Format'}</th>
                <th>{translate text='Placed'}</th>
                <th>{translate text='Pickup'}</th>
                {if $sectionKey=='available'}
                  <th>{translate text='Available'}</th>
                  <th>{translate text='Expires'}</th>
                {else}
                  <th>{translate text='Position'}</th>
                  <th>{translate text='Status'}</th>
                {/if}
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
    				    	<div class="imageColumn"> 
    						    <div id='descriptionPlaceholder{$record.recordId|escape}' style='display:none'></div>
    						    <a href="{$url}/Record/{$record.recordId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" id="descriptionTrigger{$record.recordId|escape:"url"}">
    						    <img src="{$path}/bookcover.php?id={$record.recordId}&amp;isn={$record.isbn|@formatISBN}&amp;size=small&amp;upc={$record.upc}&amp;category={$record.format_category.0|escape:"url"}" class="listResultImage" alt="{translate text='Cover Image'}"/>
    						    </a>
    						  </div>
  				  
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
                   
  				      <td class="myAccountCell">
  				        {$record.createdate|date_format}
  				      </td>
                
  					    <td class="myAccountCell">
                  {$record.location}
                </td>
                
                {if $sectionKey=='unavailable'}
                  <td class="myAccountCell">
    			          {$record.position}
    			        </td>
                
                  <td class="myAccountCell">
  	                  {if $record.frozen}<span class='frozenHold'>{/if}{$record.status} {if $record.frozen}until {$record.reactivatedate|date_format}</span>{/if}
                      {if strlen($record.freezeMessage) > 0}
                        <div class='{if $record.freezeResult == true}freezePassed{else}freezeFailed{/if}'>
                          {$record.freezeMessage|escape}
                        </div>
                      {/if}
                  </td>
  	            {/if}
                
                {if $sectionKey=='available' && !$record.hasEpub}
                  <td class="myAccountCell">
                  {if $record.availableTime} 
                    {$record.availableTime|date_format:"%b %d, %Y at %l:%M %p"}
                  {else}
                    Now
                  {/if}
                  </td>
                  
                  <td class="myAccountCell">
                  {$record.expiredate|date_format:"%b %d, %Y"}
                  </td>
                {/if}
  						

                    
        				{if $record.recordId != -1}
        				<script type="text/javascript">
        				  addRatingId('{$record.recordId|escape:"javascript"}');
        				  $(document).ready(function(){literal} { {/literal}
        				      resultDescription('{$record.recordId}','{$record.recordId}');
        				  {literal} }); {/literal}
        				</script>
        				{/if}
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
	                Suspend until (MM/DD/YYYY): 
                	<input type="text" size="10" name="suspendDateBottom" id="suspendDateBottom" value="" />
                	<script type="text/javascript">{literal}
        					  $(function() {
        					    $( "#suspendDateBottom" ).datepicker({ minDate: 0, showOn: "both", buttonImage: "{/literal}{$path}{literal}/images/silk/calendar.png", numberOfMonths: 2,  buttonImageOnly: true});
        					  });{/literal}
					        </script>
				    <input type="submit" class="button" name="freezeSelected" value="Suspend Selected" title="Suspending a hold prevents the hold from being filled, but keeps your place in queue. This is great if you are going on vacation or want to space out your holds." onclick="return freezeSelectedHolds();"/>
	                <input type="submit" class="button" name="thawSelected" value="Activate Selected" title="Activate the hold to allow the hold to be filled again." onclick="return thawSelectedHolds();"/>
                  {/if}
                  <input type="submit" class="button" name="cancelSelected" value="Cancel Selected" onclick="return cancelSelectedHolds();"/>
				  </div>
			  </div>
		      </form>
	        </div>
	        </div>
	        </div>
          <script type="text/javascript">
            $(document).ready(function() {literal} { {/literal}
              doGetRatings();
              {if $sectionKey=='available'}
                $("#holdsTable{$sectionKey}").tablesorter({literal}{cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', headers: { 0: { sorter: false}, 3: {sorter : 'date'}, 4: {sorter : 'date'}, 7: { sorter: false} } }{/literal});
              {else}
                $("#holdsTable{$sectionKey}").tablesorter({literal}{cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', headers: { 0: { sorter: false}, 3: {sorter : 'date'}, 7: { sorter: false} } }{/literal});
              {/if}
            {literal} }); {/literal}
          </script>
        {else}
          {translate text='You do not have any holds placed'}.
        {/if}
	    {/foreach}
      {else}
        You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
      {/if}
    </div>
  </div>
