{strip}
<div id="record{$summId|escape}" class="resultsList row-fluid">
	<div class="imageColumn span3">
		<div class="selectTitle hidden-phone span4">
			<label for="selected{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}" class="resultIndex checkbox"><strong>{$resultIndex}</strong>
				<input type="checkbox" name="selected[{$summShortId|escape:"url"}]" id="selected{$summShortId|escape:"url"}" style="display:none" />&nbsp;
			</label>
		</div>

		<div class="span7 text-center">
			<a href="{$path}/Person/{$summShortId}">
			{if $summPicture}
			<img src="{$path}/files/thumbnail/{$summPicture}" class="alignleft listResultImage" alt="{translate text='Picture'}"/><br />
			{else}
			<img src="{$path}/interface/themes/default/images/person.png" class="alignleft listResultImage" alt="{translate text='No Cover Image'}"/><br />
			{/if}
			</a>
		</div>
	</div>

	<div class="span9">
		<div class="row-fluid">
			{if $summScore}({$summScore}) {/if}
			<strong>
				<a href="{$path}/Person/{$summShortId}" class="title">{if !$summTitle}{translate text='Title not available'}{else}{$summTitle|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}{/if}</a>
				{if $summTitleStatement}
					<div class="searchResultSectionInfo">
					{$summTitleStatement|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}
					</div>
				{/if}
			</strong>
		</div>

		<div class="row-fluid">
			<div class="resultDetails span9">
				{if $birthDate}
					<div class="row-fluid">
						<div class='result-label span3'>Born: </div>
						<div class="span9 result-value">{$birthDate}</div>
					</div>
				{/if}
				{if $deathDate}
					<div class="row-fluid">
						<div class='result-label span3'>Died: </div>
						<div class="span9 result-value">{$deathDate}</div>
					</div>
				{/if}
				{if $numObits}
					<div class="row-fluid">
						<div class='result-label span3'>Num. Obits: </div>
						<div class="span9 result-value">{$numObits}</div>
					</div>
				{/if}
				{if $dateAdded}
					<div class="row-fluid">
						<div class='result-label span3'>Added: </div>
						<div class="span9 result-value">{$dateAdded|date_format}</div>
					</div>
				{/if}
				{if $lastUpdate}
					<div class="row-fluid">
						<div class='result-label span3'>Last Update: </div>
						<div class="span9 result-value">{$lastUpdate|date_format}</div>
					</div>
				{/if}
				
			</div>
		</div>
	</div>
</div>
{/strip}