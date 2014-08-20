{strip}
	{if $error}<p class="error">{$error}</p>{/if}

	<h2>
		{$person->firstName|escape} {$person->middleName|escape}{if $person->nickName} "{$person->nickName|escape}"{/if}{if $person->maidenName} ({$person->maidenName}){/if} {$person->lastName|escape}
		{if $userIsAdmin}
			<a href='{$path}/Admin/People?objectAction=edit&amp;id={$id}' ><span title='Edit this person' class='silk user_edit'>&nbsp;</span></a>
			<a href='{$path}/Admin/People?objectAction=delete&amp;id={$id}' onclick='return confirm("Removing this person will permanently remove them from the system.	Are you sure?")'><span title='Delete this person' class='silk user_delete'>&nbsp;</span></a>
		{/if}
	</h2>
	{if $person->otherName}
		<div class='personDetail'><span class='result-label'>Other Names:</span><span class='personDetailValue'>{$person->otherName|escape}</span></div>
	{/if}
	{if $birthDate}
		<div class='personDetail'><span class='result-label'>Birth Date:</span><span class='personDetailValue'>{$birthDate}</span></div>
	{/if}
	{if $deathDate}
		<div class='personDetail'><span class='result-label'>Death Date:</span><span class='personDetailValue'>{$deathDate}</span></div>
	{/if}
	{if $ageAtDeath}
		<div class='personDetail'><span class='result-label'>Age at Death:</span><span class='personDetailValue'>{$person->ageAtDeath|escape}</span></div>
	{/if}
	{if $person->sex}
		<div class='personDetail'><span class='result-label'>Sex:</span><span class='personDetailValue'>{$person->sex|escape}</span></div>
	{/if}
	{if $person->race}
		<div class='personDetail'><span class='result-label'>Race:</span><span class='personDetailValue'>{$person->race|escape}</span></div>
	{/if}
	{if $person->veteranOf}
		{implode subject=$person->veteranOf glue=", " assign='veteranOf'}
		<div class='personDetail'><span class='result-label'>Veteran Of:</span><span class='personDetailValue'>{$veteranOf}</span></div>
	{/if}
	{if $person->causeOfDeath}
		<div class='personDetail'><span class='result-label'>Cause of Death:</span><span class='personDetailValue'>{$person->causeOfDeath|escape}</span></div>
	{/if}

	{if count($marriages) > 0 || $userIsAdmin}
		<h3>Marriages
		{if $userIsAdmin}
			<a href='{$path}/Admin/Marriages?objectAction=add&amp;personId={$id}' title='Add a Marriage'><span class='silk group_add' title='Add a Marriage'>&nbsp;</span></a>
		{/if}
		</h3>
		{foreach from=$marriages item=marriage}
			<div class="marriageTitle">
				 {$marriage.spouseName}{if $marriage.formattedMarriageDate} - {$marriage.formattedMarriageDate}{/if}
				 {if $userIsAdmin}
						<a href='{$path}/Admin/Marriages?objectAction=edit&amp;id={$marriage.marriageId}' title='Edit this Marriage'><span class='silk group_edit' title='Edit this Marriage'>&nbsp;</span></a>
						<a href='{$path}/Admin/Marriages?objectAction=delete&amp;id={$marriage.marriageId}' title='Delete this Marriage' onclick='return confirm("Removing this marriage will permanently remove it from the system.	Are you sure?")'><span class='silk group_delete' title='Delete this Marriage'>&nbsp;</span></a>
				 {/if}
			</div>
			{if $marriage.comments}
				<div class="marriageComments">{$marriage.comments|escape}</div>
			{/if}
		{/foreach}

	{/if}
	{if $person->cemeteryName || $person->cemeteryLocation || $person->mortuaryName || $person->cemeteryAvenue || $person->lot || $person->block || $person->grave || $person->addition}
		<h3>Burial Details</h3>
		{if $person->cemeteryName}
		<div class='personDetail'><span class='result-label'>Cemetery Name:</span><span class='personDetailValue'>{$person->cemeteryName}</span></div>
		{/if}
		{if $person->cemeteryLocation}
		<div class='personDetail'><span class='result-label'>Cemetery Location:</span><span class='personDetailValue'>{$person->cemeteryLocation}</span></div>
		{/if}
		{if $person->cemeteryAvenue}
			<div class='personDetail'><span class='result-label'>Cemetery Avenue:</span><span class='personDetailValue'>{$person->cemeteryAvenue}</span></div>
		{/if}
		{if $person->addition || $person->lot || $person->block || $person->grave}
		<div class='personDetail'><span class='result-label'>Burial Location:</span>
		<span class='personDetailValue'>
			{if $person->addition}Addition {$person->addition}{if $person->block || $person->lot || $person->grave}, {/if}{/if}
			{if $person->block}Block {$person->block}{if $person->lot || $person->grave}, {/if}{/if}
			{if $person->lot}Lot {$person->lot}{if $person->grave}, {/if}{/if}
			{if $person->grave}Grave {$person->grave}{/if}
		</span></div>
		{if $person->tombstoneInscription}
		<div class='personDetail'><span class='result-label'>Tombstone Inscription:</span><div class='personDetailValue'>{$person->tombstoneInscription}</div></div>
		{/if}
		{/if}
		{if $person->mortuaryName}
		<div class='personDetail'><span class='result-label'>Mortuary Name:</span><span class='personDetailValue'>{$person->mortuaryName}</span></div>
		{/if}
	{/if}
	{if count($obituaries) > 0 || $userIsAdmin}
		<h3>Obituaries
		{if $userIsAdmin}
			<a href='{$path}/Admin/Obituaries?objectAction=add&amp;personId={$id}' title='Add an Obituary'><img src='{$path}/images/silk/report_add.png' alt='Add a Marriage' /></a>
		{/if}
		</h3>
		{foreach from=$obituaries item=obituary}
			<div class="obituaryTitle">
			{$obituary.source}{if $obituary.sourcePage} page {$obituary.sourcePage}{/if}{if $obituary.formattedObitDate} - {$obituary.formattedObitDate}{/if}
			{if $userIsAdmin}
				 <a href='{$path}/Admin/Obituaries?objectAction=edit&amp;id={$obituary.obituaryId}' title='Edit this Obituary'><span class='silk report_edit' title='Edit this Obituary'>&nbsp;</span></a>
				 <a href='{$path}/Admin/Obituaries?objectAction=delete&amp;id={$obituary.obituaryId}' title='Delete this Obituary' onclick='return confirm("Removing this obituary will permanently remove it from the system.	Are you sure?")'><span class='silk report_delete' title='Delete this Obituary'>&nbsp;</span></a>
			{/if}
			</div>
			{if $obituary.contents && $obituary.picture}
				<div class="obituaryText">{if $obituary.picture|escape}<a href='{$path}/files/original/{$obituary.picture|escape}'><img class='obitPicture' src='{$path}/files/medium/{$obituary.picture|escape}'/></a>{/if}{$obituary.contents|escape}</div>
				<div class="clearer"></div>
			{elseif $obituary.contents}
				<div class="obituaryText">{$obituary.contents|escape|replace:"\r":"<br/>"}</div>
				<div class="clearer"></div>
			{elseif $obituary.picture}
				<div class="obituaryPicture">{if $obituary.picture|escape}<a href='{$path}/files/original/{$obituary.picture|escape}'><img class='obitPicture' src='{$path}/files/medium/{$obituary.picture|escape}'/></a>{/if}</div>
				<div class="clearer"></div>
			{/if}

		{/foreach}

	{/if}
	{if $person->ledgerVolume || $person->ledgerYear || $person->ledgerEntry}
		<h3>Ledger Information</h3>
		{if $person->ledgerVolume}
			<div class='personDetail'><span class='result-label'>Volume:</span><span class='personDetailValue'>{$person->ledgerVolume}</span></div>
		{/if}
		{if $person->ledgerYear}
			<div class='personDetail'><span class='result-label'>Year:</span><span class='personDetailValue'>{$person->ledgerYear}</span></div>
		{/if}
		{if $person->ledgerYear}
			<div class='personDetail'><span class='result-label'>Entry:</span><span class='personDetailValue'>{$person->ledgerEntry}</span></div>
		{/if}
	{/if}
	<h3>Comments</h3>
	{if $person->comments}
	<div class='personComments'>{$person->comments|escape}</div>
	{else}
	<div class='personComments'>No comments found.</div>
	{/if}
{/strip}