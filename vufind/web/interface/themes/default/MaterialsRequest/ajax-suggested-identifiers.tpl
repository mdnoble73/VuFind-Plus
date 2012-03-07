{strip}
<div class='suggestedIdentifier'>
{if count($suggestedIdentifiers) == 0}
	Sorry, we couldn't find an ISBN or OCLC Number fo rthat title, please try changing the title and author and searching again.
{else}
	<table class="suggestedIdentifierTable">
		<thead>
			<tr><td>Title</td><td>Author</td><td>ISBN</td><td>OCLC Number</td><td>&nbsp;</td></tr>
		</thead>
		<tbody>
			{foreach from=$suggestedIdentifiers item=suggestion}
			<tr>
				<td>{$suggestion.title}</td>
				<td>{$suggestion.author|truncate:60}</td>
				<td>{$suggestion.isbn}</td>
				<td>{$suggestion.oclcNumber}</td>
				<td><input type="button" value="Use This" onclick="setIsbnAndOclcNumber('{$suggestion.isbn}', '{$suggestion.oclcNumber}')" /></td>
			</tr>
			{/foreach}
		</tbody>
	</table>
{/if}
</div>
{/strip}