{strip}
	{* Navigate search results from within the full record views *}
	<div class="search-results-navigation text-center">
		<div class="btn-group">
			{if isset($previousUrl)}
				<div id="previousRecordLink" class="btn">
					<a href="{*{$path}/*}{$previousUrl}?searchId={$searchId}&amp;recordIndex={$previousIndex}&amp;page={if isset($previousPage)}{$previousPage}{else}{$page}{/if}" title="{if !$previousTitle}{translate text='Previous'}{else}{$previousTitle|truncate:180:"..."|replace:"&":"&amp;"}{/if}">
						<span class="glyphicon glyphicon-chevron-left"></span> Prev
					</a>
				</div>
			{/if}
			{if $lastsearch}
				<div id="returnToSearch" class="btn">
					<a href="{$lastsearch|escape}#record{$recordDriver->getUniqueId()|escape:"url"}">{translate text="Return to Search Results"}</a>
				</div>
			{/if}
			{if isset($nextUrl)}
				<div id="nextRecordLink" class="btn">
					<a href="{*{$path}/*}{$nextUrl}?searchId={$searchId}&amp;recordIndex={$nextIndex}&amp;page={if isset($nextPage)}{$nextPage}{else}{$page}{/if}" title="{if !$nextTitle}{translate text='Next'}{else}{$nextTitle|truncate:180:"..."|replace:"&":"&amp;"}{/if}">
						Next <span class="glyphicon glyphicon-chevron-right"></span>
					</a>
				</div>
			{/if}
		</div>
	</div>
{/strip}