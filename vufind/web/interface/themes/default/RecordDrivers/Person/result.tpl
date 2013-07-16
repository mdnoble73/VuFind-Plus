{strip}
<div id="record{$summId|escape}">
	<div class="selectTitle">
		<input type="checkbox" name="selected[{$summShortId|escape:"url"}]" id="selected{$summShortId|escape:"url"}" style="display:none" />&nbsp;
	</div>
	
	<div class="resultsList">
		<div class="selectTitle">
			<input type="checkbox" name="selected[{$summId|escape:"url"}]" id="selected{$summId|escape:"url"}" style="display:none" />&nbsp;
		</div>
		
		<div class="imageColumn">
			<a href="{$path}/Person/{$summShortId}">
			{if $summPicture}
			<img src="{$path}/files/thumbnail/{$summPicture}" class="alignleft listResultImage" alt="{translate text='Picture'}"/><br />
			{else}
			<img src="{$path}/interface/themes/default/images/person.png" class="alignleft listResultImage" alt="{translate text='No Cover Image'}"/><br />
			{/if}
			</a>
		</div>
		
		<div class="resultDetails">
			<div class="resultItemLine1 title">
				<a href="{$path}/Person/{$summShortId}" class="title">{if !$summTitle}{translate text='Title not available'}{else}{$summTitle|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}{/if}</a>
				{if $summTitleStatement}
					<div class="searchResultSectionInfo">
					{$summTitleStatement|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}
					</div>
				{/if}
				
			</div>
	
			<div class="resultItemLine2">
				{if $birthDate}
					<div class='birthDate'>Born: {$birthDate}</div>
				{/if}
				{if $deathDate}
					<div class='deathDate'>Died: {$deathDate}</div>
				{/if}
				{if $numObits}
					<div class='numObits'>Number of Obituaries: {$numObits}</div>
				{/if}
				{if $dateAdded}
					<div class='dateAdded'>Added: {$dateAdded|date_format}</div>
				{/if}
				{if $lastUpdate}
					<div class='lastUpdate'>Last Update: {$lastUpdate|date_format}</div>
				{/if}
				
			</div>
		</div>
	</div>
	{* Clear floats so the record displays as a block*}
	<div class='clearer'></div>
</div>
{/strip}