<div id="supplementalRecord{$summId|escape}">
	<div class="resultsList">
		<div class="listResultImage">
			{if !isset($user->disableCoverArt) ||$user->disableCoverArt != 1}	
				<a href="{$path}/Record/{$summId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}&amp;searchSource={$searchSource}" id="pretty{$summShortId|escape:"url"}">
					<img src="{$bookCoverUrl}" alt="{translate text='Cover Image'}"/>
				</a>
			{/if}
			{* Let the user rate this title *}
			{include file="Record/title-rating.tpl" ratingClass="" recordId=$summId shortId=$summShortId ratingData=$summRating}
		</div>
		
		<div class="resultitem">
			<div class="resultItemLine1">
				{if $summScore}({$summScore}) {/if}
				<a href="{$path}/Record/{$summId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}&amp;searchSource={$searchSource}" class="title">{if !$summTitle|removeTrailingPunctuation}{translate text='Title not available'}{else}{$summTitle|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}{/if}</a>
				{if $summTitleStatement}
					<div class="searchResultSectionInfo">
					{$summTitleStatement|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}
					</div>
				{/if}
			</div>
			{if $summEditions}
			<div class="resultInformation"><span class="resultLabel">{translate text='Edition'}:</span><span class="resultValue">{$summEditions.0|escape}</span></div>
			{/if}

			{if $summAuthor}
				<div class="resultInformation"><span class="resultLabel">{translate text='Author'}:</span>
					<span class="resultValue">
						{if is_array($summAuthor)}
							{foreach from=$summAuthor item=author}
								<a href="{$path}/Author/Home?author={$author|escape:"url"}&amp;searchSource=marmot">{$author|highlight:$lookfor}</a>
							{/foreach}
						{else}
							<a href="{$path}/Author/Home?author={$summAuthor|escape:"url"}&amp;searchSource=marmot">{$summAuthor|highlight:$lookfor}</a>
						{/if}
					</span>
				</div>
			{/if}
			{if $summPublicationDates || $summPublishers || $summPublicationPlaces}
			<div class="resultInformation"><span class="resultLabel">{translate text='Published'}:</span><span class="resultValue">{$summPublicationPlaces.0|escape}{$summPublishers.0|escape}{$summPublicationDates.0|escape}</span></div>
			{/if}

			<div class="resultInformation"><span class="resultLabel">{translate text='Format'}:</span><span class="resultValue">
			{if is_array($summFormats)}
				{foreach from=$summFormats item=format}
					<span class="icon {$format|lower|regex_replace:"/[^a-z0-9]/":""}">&nbsp;</span><span class="iconlabel">{translate text=$format}</span>
				{/foreach}
			{else}
				<span class="icon {$summFormats|lower|regex_replace:"/[^a-z0-9]/":""}">&nbsp;</span><span class="iconlabel">{translate text=$summFormats}</span>
			{/if}
			</span></div>
			{if $summPhysical}
			<div class="resultInformation"><span class="resultLabel">{translate text='Physical Desc'}:</span><span class="resultValue">{$summPhysical.0|escape}</span></div>
			{/if}

		</div>

	</div>

	{* Clear floats so the record displays as a block*}
	<div class='clearer'></div>
</div>