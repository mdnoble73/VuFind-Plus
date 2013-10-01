{if !empty($addThis)}
<script type="text/javascript" src="https://s7.addthis.com/js/250/addthis_widget.js?pub={$addThis|escape:"url"}"></script>
{/if}
<script type="text/javascript">
{literal}$(document).ready(function(){{/literal}
	GetHoldingsInfo('{$id|escape:"url"}');
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
		alert("{$title}");
	{/if}
{literal}});{/literal}

function redrawSaveStatus() {literal}{{/literal}
	getSaveStatus('{$id|escape:"javascript"}', 'saveLink');
{literal}}{/literal}
</script>

<div id="page-content" class="content">
	{if $error}<p class="error">{$error}</p>{/if} 
	
	{* Hid "Return to Search Results" to see if anyone uses breadcrumb instead *}
    {*
    <div id="returnToSearch">
		<a href="{$lastsearch|escape}#record{$id|escape:"url"}">{translate text="Return to Search Results"}</a>
	</div>
	*}
    


<div id="record-header">
			
            {*
            {if isset($previousId)}
				<div id="previousRecordLink"><a href="{$path}/{$previousType}/{$previousId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$previousIndex}&amp;page={if isset($previousPage)}{$previousPage}{else}{$page}{/if}" title="{if !$previousTitle}{translate text='Previous'}{else}{$previousTitle|truncate:180:"..."|replace:"&":"&amp;"}{/if}"><img src="{$path}/interface/themes/default/images/prev.png" alt="Previous Record"/></a></div>
			{/if}
            *}
            
			<div id="recordTitleAuthorGroup">
				{* Display Title *}
				<div id='recordTitle'>{$recordTitleSubtitle|removeTrailingPunctuation|escape}</div>
				{* Display more information about the title*}
				{if $mainAuthor}
					<div class="recordAuthor">
						<span class="resultLabel">by</span>&nbsp;
						<span class="resultValue"><a href="{$path}/Author/Home?author={$mainAuthor|escape:"url"}">{$mainAuthor|escape}</a></span>
					</div>
				{/if}
					
				{if $corporateAuthor}
					<div class="recordAuthor">
						<span class="resultLabel">{translate text='Corporate Author'}:</span>
						<span class="resultValue"><a href="{$path}/Author/Home?author={$corporateAuthor|escape:"url"}">{$corporateAuthor|escape}</a></span>
					</div>
				{/if}
			</div>
			
            {*
            <div id ="recordTitleRight">
				{if isset($nextId)}
					<div id="nextRecordLink"><a href="{$path}/{$nextType}/{$nextId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$nextIndex}&amp;page={if isset($nextPage)}{$nextPage}{else}{$page}{/if}" title="{if !$nextTitle}{translate text='Next'}{else}{$nextTitle|truncate:180:"..."|replace:"&":"&amp;"}{/if}"><img src="{$path}/interface/themes/default/images/next.png" alt="Next Record"/></a></div>
				{/if}
				{if $lastsearch}
				<div id="returnToSearch">
					<a href="{$lastsearch|escape}#record{$id|escape:"url"}">{translate text="Return to Search Results"}</a>
				</div>
				{/if}
			</div>
            *}
		</div>
        
        
        <div id="sidebarRight">
        {include file="Record/view-sidebar.tpl"} {* view-sidebar moved this from the top to the bottom so the title details would display on the right side of the screen *}
		</div>
    
    <div id="main-content" class="full-result-content">
		
		
		<div id="image-column">
			{* Display Book Cover *}
			{if $user->disableCoverArt != 1}	
				<div id = "recordcover">	
					<div class="recordcoverWrapper">
						<img alt="{translate text='Book Cover'}" class="recordcover" src="{$bookCoverUrl}" />
						<div id="goDeeperLink" class="godeeper" style="display:none">
							<a href="{$path}/Record/{$id|escape:"url"}/GoDeeper" onclick="ajaxLightbox('{$path}/Record/{$id|escape}/GoDeeper?lightbox', null,'5%', '90%', 50, '85%'); return false;">
							<img alt="{translate text='Go Deeper'}" src="{$path}/images/deeper.png" /></a>
						</div>
					</div>
				</div>	
			{/if}

			{if $goldRushLink}
			<div class ="titledetails">
				<a href='{$goldRushLink}' >Check for online articles</a>
			</div>
			{/if}
			
			{* Let the user rate this title *}
			{include file="Record/title-rating.tpl" ratingClass="" recordId=$id shortId=$shortId ratingData=$ratingData showFavorites=0}

		</div> {* End image column *}
		
		<div id="record-details-column">
			<div id="record-details-header">
				<div id="holdingsSummaryPlaceholder" class="holdingsSummaryRecord"></div>

				{* Hid result-Tools here - displaying them in Record > view-sidebar.tpl *}
                {*
                <div id="recordTools">
					{include file="Record/result-tools.tpl" showMoreInfo=false summShortId=$shortId summId=$id summTitle=$title recordUrl=$recordUrl}
				</div>
                *}
                
				<div class="clearer">&nbsp;</div>
			</div>
            
            
            <div class="resultInformationLabel">{translate text='Format'}</div>
            {if is_array($recordFormat)}
             {foreach from=$recordFormat item=displayFormat name=loop}
                 <div class="sidebarValue"><span class="icon {$displayFormat|lower|regex_replace:"/[^a-z0-9]/":""}">&nbsp;</span><span class="iconlabel">{translate text=$displayFormat}</span></div>
             {/foreach}
            {else}
                <div class="sidebarValue"><span class="icon {$recordFormat|lower|regex_replace:"/[^a-z0-9]/":""}">&nbsp;</span><span class="iconlabel">{translate text=$recordFormat}</span></div>
            {/if}
			
			{if $summary}
				<div class="resultInformation">
					<div class="resultInformationLabel">{translate text='Description'}</div>
					<div class="recordDescription">
						{if strlen($summary) > 300}
							<span id="shortSummary">
							{$summary|stripTags:'<b><p><i><em><strong><ul><li><ol>'|truncate:300}{*Leave unescaped because some syndetics reviews have html in them *}
							<a href='#' onclick='$("#shortSummary").slideUp();$("#fullSummary").slideDown()'>More</a>
							</span>
							<span id="fullSummary" style="display:none">
							{$summary|stripTags:'<b><p><i><em><strong><ul><li><ol>'}{*Leave unescaped because some syndetics reviews have html in them *}
							<a href='#' onclick='$("#shortSummary").slideDown();$("#fullSummary").slideUp()'>Less</a>
							</span>
						{else}
							{$summary|stripTags:'<b><p><i><em><strong><ul><li><ol>'}{*Leave unescaped because some syndetics reviews have html in them *}
						{/if}
					</div>
				</div>
			{/if}
        
        
			{* Removed subject links list - moved to Title Details box - Jenny 9/23/13 *}           
           
		</div>       
        

	 

        
	</div>
		<div id="relatedTitleInfo" style="display:none">    
			{assign var="scrollerName" value="Series"}
			{assign var="scrollerTitle" value="Also in this Series"}
			{assign var="wrapperId" value="series"}
			{assign var="scrollerVariable" value="seriesScroller"}
			{assign var="fullListLink" value="$path/Record/$id/Series"}
			{include file='titleScroller.tpl'}
		</div>
		
     
		{include file="Record/view-tabs.tpl" isbn=$isbn upc=$upc}
		{* Hiding title details b/c added to tabbed view {include file="Record/view-title-details.tpl"} *}
</div>