{if $lastsearch}
&nbsp;<a href="{$lastsearch|escape}#record{$id|escape:"url"}">{translate text="Return to Search Results"}</a> <span class="divider">&raquo;</span>
{/if}
&nbsp;{if !$lastsearch}Catalog {/if}<a href="{$path}/GroupedWork/{$recordDriver->getPermanentId()}">{$recordDriver->getTitle()|truncate:30:"..."|escape}</a> <span class="divider">&raquo;</span>
&nbsp;<em>{$groupedWorkDriver->getFormatCategory()}</em> <span class="divider">&raquo;</span>