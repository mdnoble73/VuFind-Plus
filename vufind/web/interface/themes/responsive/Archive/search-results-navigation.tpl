{strip}
	{* Navigate search results from within the full record views *}
	<div class="search-results-navigation text-center">
		<div class="btn-group">
			{* Collection Navigation Links*}
		{if $isFromExhibit}
			{if isset($previousUrl)}
				<div id="previousRecordLink" class="btn">
					<a href="{$previousUrl}" onclick="VuFind.Archive.setForExhibitNavigation({$previousIndex}{if $previousPage},{$previousPage}{elseif $page},{$page}{/if})" title="{if !$previousTitle}{translate text='Previous'}{else}{$previousTitle|truncate:180:"..."|escape:'html'}{/if}">
						<span class="glyphicon glyphicon-chevron-left"></span> Prev
					</a>
					{*<a href="{$path}/{$previousUrl}?collectionSearchId={$searchId}&amp;recordIndex={$previousIndex}&amp;page={if isset($previousPage)}{$previousPage}{else}{$page}{/if}" title="{if !$previousTitle}{translate text='Previous'}{else}{$previousTitle|truncate:180:"..."|escape:'html'}{/if}">*}
						{*<span class="glyphicon glyphicon-chevron-left"></span> Prev*}
					{*</a>*}
				</div>
			{/if}
			{if $lastCollection}
				<div id="returnToCollection" class="btn">
					<a href="{$lastCollection}">Return to <strong>{$collectionName}</strong> Collection</a>
				</div>
			{/if}
			{if isset($nextUrl)}
				<div id="nextRecordLink" class="btn">
					<a href="{$nextUrl}" onclick="VuFind.Archive.setForExhibitNavigation({$nextIndex}{if $nextPage},{$nextPage}{elseif $page},{$page}{/if})" title="{if !$nextTitle}{translate text='Next'}{else}{$nextTitle|truncate:180:"..."|escape:'html'}{/if}">
						Next <span class="glyphicon glyphicon-chevron-right"></span>
					</a>
					{*<a href="{$path}/{$nextUrl}?collectionSearchId={$searchId}&amp;recordIndex={$nextIndex}&amp;page={if isset($nextPage)}{$nextPage}{else}{$page}{/if}" title="{if !$nextTitle}{translate text='Next'}{else}{$nextTitle|truncate:180:"..."|escape:'html'}{/if}">*}
						{*Next <span class="glyphicon glyphicon-chevron-right"></span>*}
					{*</a>*}
				</div>
			{/if}
		{else}

			{* Search Navigation Links*}
			{if isset($previousUrl)}
				<div id="previousRecordLink" class="btn">
					<a href="{*{$path}/*}{$previousUrl}?searchId={$searchId}&amp;recordIndex={$previousIndex}&amp;page={if isset($previousPage)}{$previousPage}{else}{$page}{/if}" title="{if !$previousTitle}{translate text='Previous'}{else}{$previousTitle|truncate:180:"..."|escape:'html'}{/if}">
						<span class="glyphicon glyphicon-chevron-left"></span> Prev
					</a>
				</div>
			{/if}
			{* TODO: Keeping below?? *}
			{if $isCollectionSearch}
				<div id="returnToSearch" class="btn">
					<a href="{$lastsearch|escape}">{translate text="Return to Collection"}</a>
				</div>
			{elseif $lastsearch}
				<div id="returnToSearch" class="btn">
					<a href="{$lastsearch|escape}#record{$recordDriver->getUniqueId()|escape:"url"}">{translate text="Return to Search Results"}</a>
				</div>
			{/if}
			{if isset($nextUrl)}
				<div id="nextRecordLink" class="btn">
					<a href="{*{$path}/*}{$nextUrl}?searchId={$searchId}&amp;recordIndex={$nextIndex}&amp;page={if isset($nextPage)}{$nextPage}{else}{$page}{/if}" title="{if !$nextTitle}{translate text='Next'}{else}{$nextTitle|truncate:180:"..."|escape:'html'}{/if}">
						Next <span class="glyphicon glyphicon-chevron-right"></span>
					</a>
				</div>
			{/if}
		{/if}
		</div>
	</div>
{/strip}