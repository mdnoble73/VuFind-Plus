{* This is a text-only email template; do not include HTML! *}
{$title}
{if $author}
	By {$author}
{/if}
{$url}/GroupedWork/{$recordId|escape:"url"}
{if $callnumber}
{translate text="Call Number"}: {$callnumber}
{/if}
{translate text="Shelf Location"}: {$availableAt}
