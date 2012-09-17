{* This is a text-only email template; do not include HTML! *}
{translate text="This email was sent from"}: {$from}
------------------------------------------------------------

{$emailDetails}  {translate text="email_link"}: {$path}/EcontentRecord/{$id|escape:"url"}
------------------------------------------------------------

{if !empty($message)}
{translate text="Message From Sender"}:
{$message}
{/if}
