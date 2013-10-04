<div class="result">
	<h3>
		{if !$record.title|removeTrailingPunctuation}{translate text='Title not available'}{else}{$record.title|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}{/if}
	</h3>
	{if $record.volume}
		<p>{$record.series} {$record.volume}</p>
	{/if}
	
	<p>
		{if $record.author}
			{translate text='by'}
			{if is_array($record.author)}
				{foreach from=$summAuthor item=author}
					{$author|highlight:$lookfor}
				{/foreach}
			{else}
				{$record.author|highlight:$lookfor}
			{/if}
		{/if}

		{if $record.publicationDate}{translate text='Published'} {$record.publicationDate|escape}{/if}
	</p>
</div>
