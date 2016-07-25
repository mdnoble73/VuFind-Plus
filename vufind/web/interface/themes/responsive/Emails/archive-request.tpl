New request for a copy of materials in the Local Digital Archive.



Name: {$requestResult->name}
Address:
{$requestResult->address}
{$requestResult->address2}
{$requestResult->city} {$requestResult->state}, {$requestResult->zip} {$requestResult->country}

Phone: {$requestResult->phone}
{if $requestResult->alternatePhone}
	{$requestResult->alternatePhone} (alternate)
{/if}

E-mail: {$requestResult->email}

Format Requested:
{$requestResult->format}

Purpose:
{$requestResult->purpose}

Object Requested:
{$requestedObject->getTitle()}
{$requestedObject->getRecordUrl()}