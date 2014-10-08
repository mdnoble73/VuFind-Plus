{* This is a text-only email template; do not include HTML! *}
{$list->title}
{$list->description}
------------------------------------------------------------
{if !empty($message)}
{translate text="Message From Sender"}:
{$message}
------------------------------------------------------------
{/if}
{if $error}
{$error}
------------------------------------------------------------
{else}
{foreach from=$titles item=title}

{$title.title_display}
{$title.author_display}
{$url}/GroupedWork/{$title.id}/Home

{section name=listEntry loop=$listEntries}
{*If the listEntry has a note see if it is the same work*}
{if $listEntries[listEntry]->notes && $listEntries[listEntry]->groupedWorkPermanentId == $title.id}
Notes: {$listEntries[listEntry]->notes}

{/if}
{/section}
---------------------
{/foreach}
{/if}

