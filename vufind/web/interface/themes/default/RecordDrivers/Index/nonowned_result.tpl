<div class="selectTitle">&nbsp;</div>
<div class="imageColumn">
	<img src="{$path}/bookcover.php?isn={$record.isbn|@formatISBN}&amp;size=small&amp;upc={$record.upc}" class="listResultImage" alt="{translate text='Cover Image'}"/>
</div>
<div class="resultDetails">
	<div class="resultItemLine1">
		{if !$record.title|regex_replace:"/(\/|:)$/":""}{translate text='Title not available'}{else}{$record.title|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}{/if}
		{if $record.volume}
			<br/>{$record.series} {$record.volume}&nbsp; 
		{/if}
	</div>
	
	<div class="resultItemLine2">
		{if $record.author}
			{translate text='by'}
			{if is_array($record.author)}
				{foreach from=$summAuthor item=author}
					<a href="{$path}/Author/Home?author={$author|escape:"url"}">{$author|highlight:$lookfor}</a>
				{/foreach}
			{else}
				<a href="{$path}/Author/Home?author={$record.author|escape:"url"}">{$record.author|highlight:$lookfor}</a>
			{/if}
		{/if}

		{if $record.publicationDate}{translate text='Published'} {$record.publicationDate|escape}{/if}
	</div>
</div>
