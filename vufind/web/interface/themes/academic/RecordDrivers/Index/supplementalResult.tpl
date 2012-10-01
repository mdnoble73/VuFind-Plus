<div id="record{$summId|escape}">
	<div class="resultsList">
		<div id='descriptionPlaceholder{$summShortId|escape}'	style='display:none'></div>
		<a href="{$path}/Record/{$summId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}&amp;searchSource={$searchSource}" id="pretty{$summShortId|escape:"url"}">
			<img src="{$path}/bookcover.php?id={$summId}isn={$summISBN|@formatISBN}&amp;size=small&amp;upc={$summUPC}&amp;category={$summFormatCategory.0|escape:"url"}&amp;format={$summFormats.0|escape:"url"}" class="alignleft listResultImage" alt="{translate text='Cover Image'}"/>
		</a>


		<div class="resultitem">
			<div class="resultItemLine1">
				{if $summScore}({$summScore}) {/if}
				<a href="{$path}/Record/{$summId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}&amp;searchSource={$searchSource}" class="title">{if !$summTitle|regex_replace:"/(\/|:)$/":""}{translate text='Title not available'}{else}{$summTitle|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}{/if}</a>
				{if $summTitleStatement}
					<div class="searchResultSectionInfo">
					{$summTitleStatement|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}
					</div>
				{/if}
				{if $showRatings == 1}
					{* Let the user rate this title *}
					{include file="Record/title-rating.tpl" ratingClass="searchStars" recordId=$summId shortId=$summShortId}
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
								<a href="{$path}/Author/Home?author={$author|escape:"url"}">{$author|highlight:$lookfor}</a>
							{/foreach}
						{else}
							<a href="{$path}/Author/Home?author={$summAuthor|escape:"url"}">{$summAuthor|highlight:$lookfor}</a>
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
					<span class="iconlabel {$format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$format}</span>
				{/foreach}
			{else}
				<span class="iconlabel {$summFormats|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$summFormats}</span>
			{/if}
			</span></div>
			{if $summPhysical}
			<div class="resultInformation"><span class="resultLabel">{translate text='Physical Desc'}:</span><span class="resultValue">{$summPhysical.0|escape}</span></div>
			{/if}

		</div>

	</div>

	<script type="text/javascript">
		$(document).ready(function(){literal} { {/literal}
			resultDescription('{$summShortId}','{$summId}');
		{literal} }); {/literal}
	</script>

	{* Clear floats so the record displays as a block*}
	<div class='clearer'></div>
</div>