{strip}
	{* Navigate search results from within the full record views *}
	<div class="search-results-navigation text-center">
		{*{if $lastsearch}*}
			{*<div id="returnToSearch">*}
				{*<a href="{$lastsearch|escape}#record{$id|escape:"url"}">&laquo; {translate text="Return to Search Results"|strtoupper}</a>*}
				{*<a href="{$lastsearch|escape}#record{$id|escape:"url"}">&laquo; {translate text="Return to Search Results"}</a>*}
			{*</div>*}
		{*{/if}*}
		<div class="btn-group">
			{if isset($previousId)}
				<div id="previousRecordLink" class="btn">
					<a href="{$path}/{$previousType}/{$previousId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$previousIndex}&amp;page={if isset($previousPage)}{$previousPage}{else}{$page}{/if}" title="{if !$previousTitle}{translate text='Previous'}{else}{$previousTitle|truncate:180:"..."|replace:"&":"&amp;"}{/if}">
						{*<img src="{$path}/interface/themes/default/images/prev.png" alt="Previous Record">*}
						<span class="glyphicon glyphicon-chevron-left"></span> Prev
					</a>
				</div>
			{/if}
			{if $lastsearch}
				<div id="returnToSearch" class="btn">
					<a href="{$lastsearch|escape}#record{$id|escape:"url"}">{translate text="Return to Search Results"}</a>
				</div>
			{/if}
			{if isset($nextId)}
				<div id="nextRecordLink" class="btn">
					<a href="{$path}/{$nextType}/{$nextId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$nextIndex}&amp;page={if isset($nextPage)}{$nextPage}{else}{$page}{/if}" title="{if !$nextTitle}{translate text='Next'}{else}{$nextTitle|truncate:180:"..."|replace:"&":"&amp;"}{/if}">
						{*<img src="{$path}/interface/themes/default/images/next.png" alt="Next Record">*}
						Next <span class="glyphicon glyphicon-chevron-right"></span>
					</a>
				</div>
			{/if}
		</div>
	</div>
{/strip}