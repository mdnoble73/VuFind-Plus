{strip}
<a rel="external" href="{$path}/Record/{$summId|escape:'url'}/Holdings" id="recordLink{$summShortId|escape}">
	<div class="result recordId" id="record{$summShortId|escape}" data-record-id="{$summId}">
		<h3 class="recordTitle">
			{if !empty($summHighlightedTitle)}{$summHighlightedTitle|trim:':/'|highlight}{else}{$summTitle|trim:':/'|escape}{/if}
			{if $summTitleStatement}
				{$summTitleStatement|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}
			{/if}
		</h3>
		{if !empty($summAuthor)}
			<p class="author">{translate text='by'} {$summAuthor}</p>
		{/if}
		{if $summPublicationDates || $summPublishers || $summPublicationPlaces}
			<p class="publisher"><strong>{translate text='Published'}:</strong> {$summPublicationPlaces.0|escape} {$summPublishers.0|escape} {$summPublicationDates.0|escape}</p>
		{/if}
		{if $summAjaxStatus}
			<p class="callNumber"><strong>{translate text='Call Number'}:</strong> <span class="ajax_availability hide callnumber{$summShortId|escape}">{translate text='Loading'}...</span></p>
			<p class="location"><strong>{translate text='Located'}:</strong> <span class="ajax_availability hide location{$summShortId|escape}">{translate text='Loading'}...</span></p>
			<p class="status"><strong>{translate text='Status'}:</strong> <span class="ajax_availability hide status{$summShortId|escape}">{translate text='Loading'}...</span></p>
		{elseif !empty($summCallNo)}
			<p class="callNumber"><strong>{translate text='Call Number'}:</strong> {$summCallNo|escape}</p>
		{/if}
		{if !empty($summFormats)}
			<p class="format">
			{foreach from=$summFormats item=format}
				<span class="iconlabel {$format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$format}</span>
			{/foreach}
			{if false && !$summOpenUrl && empty($summURLs)}
				<span class="ajax_availability hide status{$summShortId|escape}">{translate text='Loading'}...</span>
			{/if}
			</p>
		{/if}
	</div>
</a>
<a id="selected{$summShortId|escape}" href="#" data-record-id="{$summId|escape}" title="{translate text='Add to book bag'}" class="add_to_book_bag">{translate text="Add to book bag"}</a>
{/strip}