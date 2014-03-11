{* This is a text-only email template; do not include HTML! *}
{if $from}
{translate text="This email was sent from"}: {$from}
{/if}
------------------------------------------------------------
{$recordDriver->getTitle()}
{if $recordDriver->getPrimaryAuthor()}
  By {$recordDriver->getPrimaryAuthor()}
{/if}
{if $callnumber}
	{translate text="Call Number"}: {$callnumber}
{/if}
{if $shelfLocation}
	{translate text="Shelf Location"}: {$availableAt}
{/if}

{translate text="email_link"}: {$recordDriver->getLinkUrl()}
------------------------------------------------------------

{if !empty($message)}
{translate text="Message From Sender"}:
{$message}
{/if}
