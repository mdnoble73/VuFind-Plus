{strip}
<a rel="external" href="{$path}/EcontentRecord/{$summId|escape:'url'}">
	<div class="result recordId" id="econtentRecord{$summId|escape}" data-record-id="econtentRecord{$summId}">
		<h3 class="recordTitle">
			{if !empty($summHighlightedTitle)}{$summHighlightedTitle|trim:':/'|highlight}{else}{$summTitle|trim:':/'|escape}{/if}
		</h3>
	
		{if !empty($summAuthor)}
			<p class="author">{translate text='by'} {$summAuthor}</p>
		{/if}
		{if $summPublicationDates || $summPublishers || $summPublicationPlaces}
			<p class="publisher"><strong>{translate text='Published'}:</strong> {$summPublicationPlaces.0|escape} {$summPublishers.0|escape} {$summPublicationDates.0|escape}</p>
		{/if}
		<p class="location"><strong>{translate text='Located'}:</strong> Online</p>
		{if $summAjaxStatus}
			<p class="status"><strong>{translate text='Status'}:</strong> <span class="ajax_availability hide statusecontentRecord{$summShortId|escape}">{translate text='Loading'}...</span></p>
		{elseif !empty($summCallNo)}
			<p class="callNumber"><strong>{translate text='Call Number'}:</strong> {$summCallNo|escape}</p>
		{/if}
		
		<div class="resultItemLine3">
			{if !empty($summSnippetCaption)}<b>{translate text=$summSnippetCaption}:</b>{/if}
			{if !empty($summSnippet)}<span class="quotestart">&#8220;</span>...{$summSnippet|highlight}...<span class="quoteend">&#8221;</span><br>{/if}
		</div>
	
		{if !empty($summFormats)}
			<p class="format">
			{foreach from=$summFormats item=format name=formatLoop}
				{if $smarty.foreach.formatLoop.index != 0}, {/if}
				<span class="iconlabel {$format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$format}</span>
			{/foreach}
			</p>
		{/if}
	</div>
</a>
<a href="#" data-record-id="econtentRecord{$summId|escape}" title="{translate text='Add to book bag'}" class="add_to_book_bag">{translate text="Add to book bag"}</a>
{/strip}