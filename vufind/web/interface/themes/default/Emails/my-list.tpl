{* This is a text-only email template; do not include HTML! *}
{$list->title}
{$list->description}
------------------------------------------------------------
{if !empty($message)}
{translate text="Message From Sender"}:
{$message}
{/if}
{if $error}
	{$error}
{else}
------------------------------------------------------------
{foreach from=$titles item=title}
{$title.title} {$title.author} ({$path}/Record/{$title.id}/Home)
{/foreach}
{/if}

