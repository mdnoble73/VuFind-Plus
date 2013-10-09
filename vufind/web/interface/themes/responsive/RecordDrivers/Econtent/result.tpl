{strip}
<div id="record{$summId|escape}" class="resultsList row-fluid">
	<div class="imageColumn span3">
		<div class="row-fluid">
			<div class="selectTitle hidden-phone span4">
				<label for="selectedEcontentRecord{$summId|escape:"url"}" class="resultIndex checkbox"><strong>{$resultIndex}</strong>
					<input type="checkbox" name="selected[econtentRecord{$summId|escape:"url"}]" class="titleSelect" id="selectedEcontentRecord{$summId|escape:"url"}" {if $enableBookCart}onclick="toggleInBag('econtentRecord{$summId|escape:"url"}', '{$summTitle|replace:'"':''|replace:'&':'&amp;'|escape:'javascript'}', this);"{/if} />&nbsp;
				</label>
			</div>
			<div class="span7 text-center">
				{if !isset($user->disableCoverArt) ||$user->disableCoverArt != 1}
					<a href="{$summUrl}">
						<img src="{$bookCoverUrl}" class="listResultImage img-polaroid" alt="{translate text='Cover Image'}"/>
					</a>
				{/if}
				{include file="EcontentRecord/title-rating.tpl" ratingClass="" recordId=$summId shortId=$summShortId ratingData=$summRating}
			</div>
		</div>
	</div>

	<div class="span9">
		<div class="row-fluid">
			{if $summScore}({$summScore}) {/if}
			<a href="{$summUrl}" class="title">{if !$summTitle|removeTrailingPunctuation}{translate text='Title not available'}{else}{$summTitle|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}{/if}</a>
			{if $summTitleStatement}
				<div class="searchResultSectionInfo">
					{$summTitleStatement|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}
				</div>
			{/if}
		</div>

		<div class="row-fluid">
			<div class="resultDetails span9">
				{if $summAuthor}
					<div class="row-fluid">
						<div class="result-label span3">Author: </div>
						<div class="span9 result-value">
							{if is_array($summAuthor)}
								{foreach from=$summAuthor item=author}
									<a href="{$path}/Author/Home?author={$author|escape:"url"}">{$author|highlight:$lookfor}</a>
								{/foreach}
							{else}
								<a href="{$path}/Author/Home?author={$summAuthor|escape:"url"}">{$summAuthor|highlight:$lookfor}</a>
							{/if}
						</div>
					</div>
				{/if}

				{if $summISBN}
					<div class="series{$summISBN} row-fluid">
						<div class="result-label span3">Series: </div>
						<div class="span9 result-value">Loading...</div>
					</div>
				{/if}

				{if $summEditions}
					<div class="row-fluid">
						<div class="result-label span3" id="resultInformationEdition{$summShortId|escape}">{translate text='Edition'}:</div>
						<div class="span9 result-value">{$summEditions.0|escape}</div>
					</div>
				{/if}

				{if $summPublicationDates || $summPublishers || $summPublicationPlaces}
					<div class="row-fluid">

						<div class="result-label span3">Published: </div>
						<div class="span9 result-value">
							{$summPublicationPlaces.0|escape}{$summPublishers.0|escape}{$summPublicationDates.0|escape}
						</div>
					</div>
				{/if}

				{* Highlighted term *}
				<div class="resultItemLine3">
					{if !empty($summSnippetCaption)}<b>{translate text=$summSnippetCaption}:</b>{/if}
					{if !empty($summSnippet)}<span class="quotestart">&#8220;</span>...{$summSnippet|highlight}...<span class="quoteend">&#8221;</span><br />{/if}
				</div>

				<div class="row-fluid">
					<div class="result-label span3">Format: </div>
					<div class="span9 result-value">
						<strong>
							{implode subject=$summFormats glue=", "}
						</strong>
					</div>
				</div>

				<div class="row-fluid">
					<div class="result-label span3">{translate text='Status'}:</div>
					<div class="span9 bold statusValue result-value" id="statusValue{$summShortId|escape}">Loading...</div>
				</div>

				<div class="row-fluid">
					<div class="span12 result-value" id="descriptionValue{$summShortId|escape}">{$summDescription}</div>
				</div>
				{*
				<div class="row-fluid">
					<div class="result-label span3">{translate text='Copies'}:</div>
					<div class="span9 result-value" id="copiesValue{$summShortId|escape}">Loading...</div>
				</div>
				*}
			</div>

			<div class="resultActions span3">
				{include file='EcontentRecord/result-tools.tpl' id=$summId shortId=$shortId summTitle=$summTitle ratingData=$summRating recordUrl=$summUrl}
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	VuFind.ResultsList.addIdToStatusList('{$summId|escape:"javascript"}', {if strcasecmp($source, 'OverDrive') == 0}'OverDrive'{else}'eContent'{/if}, '{$useUnscopedHoldingsSummary}');
	{if $summISBN}
	VuFind.ResultsList.addIdToSeriesList('{$summISBN}');
	{/if}
</script>
{/strip}